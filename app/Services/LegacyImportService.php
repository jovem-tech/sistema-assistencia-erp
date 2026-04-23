<?php

namespace App\Services;

use App\Models\ClienteModel;
use App\Models\DefeitoModel;
use App\Models\EquipamentoModel;
use App\Models\LegacyImportAliasModel;
use App\Models\LegacyImportEventModel;
use App\Models\LegacyImportRunModel;
use App\Models\OsDefeitoModel;
use App\Models\OsItemModel;
use App\Models\OsModel;
use App\Models\OsNotaLegadaModel;
use App\Models\OsStatusHistoricoModel;
use CodeIgniter\Database\BaseConnection;
use Config\Database;
use Config\LegacyImport;

class LegacyImportService
{
    private LegacyImport $config;
    private BaseConnection $sourceDb;
    private BaseConnection $targetDb;
    private LegacyRecordNormalizer $normalizer;
    private LegacyCatalogResolver $catalogResolver;
    private ClienteModel $clienteModel;
    private EquipamentoModel $equipamentoModel;
    private LegacyImportAliasModel $aliasModel;
    private OsModel $osModel;
    private OsItemModel $osItemModel;
    private OsStatusHistoricoModel $osStatusHistoricoModel;
    private OsDefeitoModel $osDefeitoModel;
    private OsNotaLegadaModel $osNotaLegadaModel;
    private DefeitoModel $defeitoModel;
    private LegacyImportRunModel $runModel;
    private LegacyImportEventModel $eventModel;
    private OsStatusFlowService $statusFlowService;
    /** @var array<int, bool> */
    private array $syntheticItemCleanupDone = [];

    public function __construct(?LegacyImport $config = null)
    {
        $this->config = $config ?? config('LegacyImport');
        $this->sourceDb = Database::connect($this->config->dbGroup);
        $this->targetDb = Database::connect();
        $this->normalizer = new LegacyRecordNormalizer();
        $this->catalogResolver = new LegacyCatalogResolver($this->normalizer, $this->config->allowCatalogAutoCreate);
        $this->clienteModel = new ClienteModel();
        $this->equipamentoModel = new EquipamentoModel();
        $this->aliasModel = new LegacyImportAliasModel();
        $this->osModel = new OsModel();
        $this->osItemModel = new OsItemModel();
        $this->osStatusHistoricoModel = new OsStatusHistoricoModel();
        $this->osDefeitoModel = new OsDefeitoModel();
        $this->osNotaLegadaModel = new OsNotaLegadaModel();
        $this->defeitoModel = new DefeitoModel();
        $this->runModel = new LegacyImportRunModel();
        $this->eventModel = new LegacyImportEventModel();
        $this->statusFlowService = new OsStatusFlowService();
    }

    public function runPreflight(bool $persist = true): array
    {
        $runId = $persist ? $this->startRun('preflight') : null;
        $summary = $this->newSummary('preflight');

        try {
            $this->assertSourceConnection();
            $this->validateQueryAliases();
            $summary = $this->inspectSourceData($summary, $runId, false);
            $summary['status'] = $summary['blocking_errors'] > 0 ? 'failed' : ($summary['warnings'] > 0 ? 'warning' : 'ok');
        } catch (\Throwable $e) {
            $summary['status'] = 'failed';
            $summary['blocking_errors']++;
            $this->registerEvent($runId, 'config', 'error', 'exception', null, $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
        }

        if ($persist && $runId !== null) {
            $this->finishRun($runId, $summary);
        }

        return $summary + ['run_id' => $runId];
    }

    public function runImport(bool $persist = true): array
    {
        $preflight = $this->runPreflight($persist);
        if (($preflight['blocking_errors'] ?? 0) > 0) {
            $preflight['import_aborted'] = true;
            return $preflight;
        }

        $runId = $persist ? $this->startRun('import') : null;
        $summary = $this->newSummary('import');

        try {
            $this->assertSourceConnection();
            $summary = $this->inspectSourceData($summary, $runId, true);
            $summary['status'] = $summary['blocking_errors'] > 0 ? 'failed' : ($summary['warnings'] > 0 ? 'warning' : 'ok');
        } catch (\Throwable $e) {
            $summary['status'] = 'failed';
            $summary['blocking_errors']++;
            $this->registerEvent($runId, 'config', 'error', 'exception', null, $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
        }

        if ($persist && $runId !== null) {
            $this->finishRun($runId, $summary);
        }

        return $summary + ['run_id' => $runId];
    }

    public function buildReport(?int $runId = null): array
    {
        $run = $runId !== null
            ? $this->runModel->find($runId)
            : $this->runModel->orderBy('id', 'DESC')->first();

        if (! $run) {
            return [
                'run' => null,
                'summary' => null,
                'aggregates' => [],
            ];
        }

        $summary = [];
        if (! empty($run['summary_json'])) {
            $decoded = json_decode((string) $run['summary_json'], true);
            if (is_array($decoded)) {
                $summary = $decoded;
            }
        }

        $aggregates = $this->targetDb->table('legacy_import_events')
            ->select('entity, severity, action, COUNT(*) as total')
            ->where('run_id', (int) $run['id'])
            ->groupBy(['entity', 'severity', 'action'])
            ->orderBy('entity', 'ASC')
            ->orderBy('severity', 'ASC')
            ->orderBy('action', 'ASC')
            ->get()
            ->getResultArray();

        return [
            'run' => $run,
            'summary' => $summary,
            'aggregates' => $aggregates,
        ];
    }

    public function prepareTarget(bool $execute = false): array
    {
        $summary = [
            'mode' => $execute ? 'cleanup' : 'cleanup_preview',
            'status' => 'ok',
            'execute' => $execute,
            'tables' => [],
            'paths' => [],
            'total_rows' => 0,
            'total_files' => 0,
            'total_directories' => 0,
            'errors' => [],
        ];

        foreach ($this->config->targetCleanupTables as $table) {
            if (! $this->targetDb->tableExists($table)) {
                $summary['tables'][] = [
                    'table' => $table,
                    'exists' => false,
                    'rows' => 0,
                    'action' => 'skipped_missing',
                ];
                continue;
            }

            $rows = (int) $this->targetDb->table($table)->countAllResults();
            $summary['total_rows'] += $rows;

            $summary['tables'][] = [
                'table' => $table,
                'exists' => true,
                'rows' => $rows,
                'action' => $execute ? 'pending_truncate' : 'preview',
            ];
        }

        foreach ($this->config->targetCleanupPaths as $path) {
            $pathSummary = $this->inspectCleanupPath($path);
            $summary['total_files'] += $pathSummary['files'];
            $summary['total_directories'] += $pathSummary['directories'];
            $summary['paths'][] = $pathSummary;
        }

        if (! $execute) {
            return $summary;
        }

        try {
            $this->targetDb->query('SET FOREIGN_KEY_CHECKS = 0');

            foreach ($summary['tables'] as &$tableSummary) {
                if (! $tableSummary['exists']) {
                    continue;
                }

                $this->targetDb->query('TRUNCATE TABLE `' . str_replace('`', '``', $tableSummary['table']) . '`');
                $tableSummary['action'] = 'truncated';
            }
            unset($tableSummary);

            foreach ($summary['paths'] as &$pathSummary) {
                $removed = $this->cleanupPathContents($pathSummary['path']);
                $pathSummary['files_removed'] = $removed['files_removed'];
                $pathSummary['directories_removed'] = $removed['directories_removed'];
                $pathSummary['action'] = 'cleaned';
            }
            unset($pathSummary);
        } catch (\Throwable $e) {
            $summary['status'] = 'failed';
            $summary['errors'][] = $e->getMessage();
        } finally {
            $this->targetDb->query('SET FOREIGN_KEY_CHECKS = 1');
        }

        return $summary;
    }

    private function inspectSourceData(array $summary, ?int $runId, bool $executeImport): array
    {
        $clienteSourceIds = [];
        $equipamentoSourceIds = [];
        $osSourceIds = [];

        foreach (['clientes', 'equipamentos', 'os', 'os_itens', 'os_status_historico', 'os_defeitos', 'os_notas_legadas'] as $entity) {
            $summary['entities'][$entity]['source_total'] = $this->countSourceRows($entity);
            $this->registerEvent(
                $runId,
                $entity,
                'info',
                'counted',
                null,
                'Total encontrado no legado para ' . $entity . ': ' . $summary['entities'][$entity]['source_total']
            );
        }

        foreach ($this->iterateSourceRows('clientes') as $row) {
            $legacyId = $this->normalizer->normalizeLegacyId($row['legacy_id'] ?? null);
            if ($legacyId === null) {
                $summary = $this->addIssue($summary, $runId, 'clientes', 'error', 'missing_legacy_id', null, 'Cliente legado sem legacy_id.');
                continue;
            }

            $clienteSourceIds[$legacyId] = true;
            $summary = $this->inspectClienteRow($row, $summary, $runId);

            if ($executeImport) {
                $summary = $this->importClienteRow($row, $summary, $runId);
            }
        }

        foreach ($this->iterateSourceRows('equipamentos') as $row) {
            $legacyId = $this->normalizer->normalizeLegacyId($row['legacy_id'] ?? null);
            $legacyClienteId = $this->normalizer->normalizeLegacyId($row['legacy_cliente_id'] ?? null);

            if ($legacyId === null) {
                $summary = $this->addIssue($summary, $runId, 'equipamentos', 'error', 'missing_legacy_id', null, 'Equipamento legado sem legacy_id.');
                continue;
            }

            $equipamentoSourceIds[$legacyId] = true;

            if ($legacyClienteId === null || ! isset($clienteSourceIds[$legacyClienteId])) {
                $summary = $this->addIssue($summary, $runId, 'equipamentos', 'error', 'orphan_client', $legacyId, 'Equipamento legado sem cliente correspondente na origem.', [
                    'legacy_cliente_id' => $legacyClienteId,
                ]);
                continue;
            }

            $summary = $this->inspectEquipamentoRow($row, $summary, $runId);

            if ($executeImport) {
                $summary = $this->importEquipamentoRow($row, $summary, $runId);
            }
        }

        foreach ($this->iterateSourceRows('os') as $row) {
            $legacyId = $this->normalizer->normalizeLegacyId($row['legacy_id'] ?? null);
            $legacyClienteId = $this->normalizer->normalizeLegacyId($row['legacy_cliente_id'] ?? null);
            $legacyEquipamentoId = $this->normalizer->normalizeLegacyId($row['legacy_equipamento_id'] ?? null);

            if ($legacyId === null) {
                $summary = $this->addIssue($summary, $runId, 'os', 'error', 'missing_legacy_id', null, 'OS legada sem legacy_id.');
                continue;
            }

            if ($legacyClienteId === null || ! isset($clienteSourceIds[$legacyClienteId])) {
                $summary = $this->addIssue($summary, $runId, 'os', 'error', 'orphan_client', $legacyId, 'OS legada sem cliente correspondente na origem.', [
                    'legacy_cliente_id' => $legacyClienteId,
                ]);
                continue;
            }

            if ($legacyEquipamentoId === null || ! isset($equipamentoSourceIds[$legacyEquipamentoId])) {
                $summary = $this->addIssue($summary, $runId, 'os', 'error', 'orphan_equipment', $legacyId, 'OS legada sem equipamento correspondente na origem.', [
                    'legacy_equipamento_id' => $legacyEquipamentoId,
                ]);
                continue;
            }

            $osSourceIds[$legacyId] = true;
            $summary = $this->inspectOsRow($row, $summary, $runId);

            if ($executeImport) {
                $summary = $this->importOsRow($row, $summary, $runId);
            }
        }

        foreach ($this->iterateSourceRows('os_itens') as $row) {
            $legacyOsId = $this->normalizer->normalizeLegacyId($row['legacy_os_id'] ?? null);
            $legacyId = $this->normalizer->normalizeLegacyId($row['legacy_id'] ?? null);

            if ($legacyId === null) {
                $summary = $this->addIssue($summary, $runId, 'os_itens', 'error', 'missing_legacy_id', null, 'Item legado sem legacy_id.');
                continue;
            }

            if ($legacyOsId === null || ! isset($osSourceIds[$legacyOsId])) {
                $summary = $this->addIssue($summary, $runId, 'os_itens', 'warning', 'orphan_os', $legacyId, 'Item legado sem OS correspondente na origem; registro sera ignorado no backfill.', [
                    'legacy_os_id' => $legacyOsId,
                ], 'ignored');
                continue;
            }

            $summary = $this->inspectOsItemRow($row, $summary, $runId);

            if ($executeImport) {
                $summary = $this->importOsItemRow($row, $summary, $runId);
            }
        }

        foreach ($this->iterateSourceRows('os_status_historico') as $row) {
            $legacyOsId = $this->normalizer->normalizeLegacyId($row['legacy_os_id'] ?? null);
            $legacyId = $this->normalizer->normalizeLegacyId($row['legacy_id'] ?? null);

            if ($legacyId === null) {
                $summary = $this->addIssue($summary, $runId, 'os_status_historico', 'error', 'missing_legacy_id', null, 'Historico legado sem legacy_id.');
                continue;
            }

            if ($legacyOsId === null || ! isset($osSourceIds[$legacyOsId])) {
                $summary = $this->addIssue($summary, $runId, 'os_status_historico', 'warning', 'orphan_os', $legacyId, 'Historico legado sem OS correspondente na origem; registro sera preservado apenas no relatorio e ignorado na carga.', [
                    'legacy_os_id' => $legacyOsId,
                ], 'ignored');
                continue;
            }

            $summary = $this->inspectOsStatusHistoricoRow($row, $summary, $runId);

            if ($executeImport) {
                $summary = $this->importOsStatusHistoricoRow($row, $summary, $runId);
            }
        }

        foreach ($this->iterateSourceRows('os_defeitos') as $row) {
            $legacyOsId = $this->normalizer->normalizeLegacyId($row['legacy_os_id'] ?? null);
            $legacyId = $this->normalizer->normalizeLegacyId($row['legacy_id'] ?? null);

            if ($legacyId === null) {
                $summary = $this->addIssue($summary, $runId, 'os_defeitos', 'error', 'missing_legacy_id', null, 'Defeito legado sem legacy_id.');
                continue;
            }

            if ($legacyOsId === null || ! isset($osSourceIds[$legacyOsId])) {
                $summary = $this->addIssue($summary, $runId, 'os_defeitos', 'warning', 'orphan_os', $legacyId, 'Defeito legado sem OS correspondente na origem; registro sera ignorado na carga.', [
                    'legacy_os_id' => $legacyOsId,
                ], 'ignored');
                continue;
            }

            $summary = $this->inspectOsDefeitoRow($row, $summary, $runId);

            if ($executeImport) {
                $summary = $this->importOsDefeitoRow($row, $summary, $runId);
            }
        }

        foreach ($this->iterateSourceRows('os_notas_legadas') as $row) {
            $legacyOsId = $this->normalizer->normalizeLegacyId($row['legacy_os_id'] ?? null);
            $legacyId = $this->normalizer->normalizeLegacyId($row['legacy_id'] ?? null);

            if ($legacyId === null) {
                $summary = $this->addIssue($summary, $runId, 'os_notas_legadas', 'error', 'missing_legacy_id', null, 'Nota legada sem legacy_id.');
                continue;
            }

            if ($legacyOsId === null || ! isset($osSourceIds[$legacyOsId])) {
                $summary = $this->addIssue($summary, $runId, 'os_notas_legadas', 'warning', 'orphan_os', $legacyId, 'Nota legada sem OS correspondente na origem; registro sera ignorado na carga.', [
                    'legacy_os_id' => $legacyOsId,
                ], 'ignored');
                continue;
            }

            $summary = $this->inspectOsNotaLegadaRow($row, $summary, $runId);

            if ($executeImport) {
                $summary = $this->importOsNotaLegadaRow($row, $summary, $runId);
            }
        }

        if ($executeImport) {
            foreach ($this->iterateSourceRows('os') as $row) {
                $summary = $this->backfillSyntheticOsItems($row, $summary, $runId);
            }
        }

        return $summary;
    }

    private function inspectClienteRow(array $row, array $summary, ?int $runId): array
    {
        $legacyId = $this->normalizer->normalizeLegacyId($row['legacy_id'] ?? null);
        $nome = $this->normalizer->normalizeString($row['nome_razao'] ?? null);
        $telefone = $this->normalizer->normalizePhone($row['telefone1'] ?? null);
        $documento = $this->normalizer->normalizeDocument($row['cpf_cnpj'] ?? null);

        if ($nome === null) {
            $summary = $this->addIssue($summary, $runId, 'clientes', 'error', 'missing_name', $legacyId, 'Cliente legado sem nome/razao social.');
        }

        if (! $this->normalizer->isValidPhone($telefone)) {
            $summary = $this->addIssue($summary, $runId, 'clientes', 'warning', 'invalid_phone', $legacyId, 'Cliente legado com telefone principal invalido ou ausente.', [
                'telefone1' => $row['telefone1'] ?? null,
            ]);
        }

        if (! $this->normalizer->isValidDocument($documento)) {
            $summary = $this->addIssue($summary, $runId, 'clientes', 'warning', 'invalid_document', $legacyId, 'Cliente legado com CPF/CNPJ fora do padrao esperado.', [
                'cpf_cnpj' => $row['cpf_cnpj'] ?? null,
            ]);
        }

        return $summary;
    }

    private function inspectEquipamentoRow(array $row, array $summary, ?int $runId): array
    {
        $legacyId = $this->normalizer->normalizeLegacyId($row['legacy_id'] ?? null);

        foreach (['tipo_nome', 'marca_nome', 'modelo_nome'] as $field) {
            if ($this->normalizer->normalizeCatalogName($row[$field] ?? null) === null) {
                $summary = $this->addIssue($summary, $runId, 'equipamentos', 'error', 'missing_catalog', $legacyId, 'Equipamento legado sem informacao obrigatoria de catalogo.', [
                    'field' => $field,
                ]);
            }
        }

        return $summary;
    }

    private function inspectOsRow(array $row, array $summary, ?int $runId): array
    {
        $legacyId = $this->normalizer->normalizeLegacyId($row['legacy_id'] ?? null);
        $statusLegado = $this->normalizeStatusCode($row['status_legado'] ?? null);

        if ($statusLegado === null || ! isset($this->config->statusMap[$statusLegado])) {
            $summary = $this->addIssue($summary, $runId, 'os', 'error', 'unknown_status', $legacyId, 'Status legado sem mapeamento explicito.', [
                'status_legado' => $row['status_legado'] ?? null,
            ]);
        }

        if ($this->normalizer->normalizeString($row['numero_os_legado'] ?? null) === null) {
            $summary = $this->addIssue($summary, $runId, 'os', 'warning', 'missing_legacy_number', $legacyId, 'OS legada sem numero de referencia legada.');
        }

        return $summary;
    }

    private function inspectOsItemRow(array $row, array $summary, ?int $runId): array
    {
        $legacyId = $this->normalizer->normalizeLegacyId($row['legacy_id'] ?? null);
        $descricao = $this->normalizer->normalizeString($row['descricao'] ?? null);
        $tipo = $this->normalizer->normalizeString($row['tipo'] ?? null);

        if ($descricao === null) {
            $summary = $this->addIssue($summary, $runId, 'os_itens', 'warning', 'missing_description', $legacyId, 'Item legado sem descricao detalhada.');
        }

        if (! in_array($tipo, ['servico', 'peca'], true)) {
            $summary = $this->addIssue($summary, $runId, 'os_itens', 'warning', 'unknown_item_type', $legacyId, 'Item legado com tipo nao reconhecido; sera normalizado conforme heuristica.', [
                'tipo' => $row['tipo'] ?? null,
            ]);
        }

        return $summary;
    }

    private function inspectOsStatusHistoricoRow(array $row, array $summary, ?int $runId): array
    {
        $legacyId = $this->normalizer->normalizeLegacyId($row['legacy_id'] ?? null);
        $statusNovo = $this->normalizeStatusCode($row['status_novo'] ?? null);
        $statusAnterior = $this->normalizeStatusCode($row['status_anterior'] ?? null);
        $hasMappedStatus = ($statusNovo !== null && isset($this->config->statusMap[$statusNovo]))
            || ($statusAnterior !== null && isset($this->config->statusMap[$statusAnterior]));

        if (! $hasMappedStatus) {
            $summary = $this->addIssue($summary, $runId, 'os_status_historico', 'warning', 'history_note_only', $legacyId, 'Historico legado sera preservado como nota por nao corresponder a um status formal do fluxo atual.', [
                'status_anterior' => $row['status_anterior'] ?? null,
                'status_novo' => $row['status_novo'] ?? null,
            ]);
        }

        return $summary;
    }

    private function inspectOsDefeitoRow(array $row, array $summary, ?int $runId): array
    {
        $legacyId = $this->normalizer->normalizeLegacyId($row['legacy_id'] ?? null);
        if ($this->normalizer->normalizeString($row['descricao'] ?? null) === null) {
            $summary = $this->addIssue($summary, $runId, 'os_defeitos', 'warning', 'missing_description', $legacyId, 'Defeito legado sem descricao.');
        }

        return $summary;
    }

    private function inspectOsNotaLegadaRow(array $row, array $summary, ?int $runId): array
    {
        $legacyId = $this->normalizer->normalizeLegacyId($row['legacy_id'] ?? null);
        if ($this->normalizer->normalizeString($row['conteudo'] ?? null) === null) {
            $summary = $this->addIssue($summary, $runId, 'os_notas_legadas', 'warning', 'missing_content', $legacyId, 'Nota legada sem conteudo util.');
        }

        return $summary;
    }

    private function importClienteRow(array $row, array $summary, ?int $runId): array
    {
        $legacyId = (string) $this->normalizer->normalizeLegacyId($row['legacy_id'] ?? null);
        $telefonePrincipal = $this->normalizer->normalizePhone($row['telefone1'] ?? null);

        $payload = [
            'tipo_pessoa'      => $this->normalizer->normalizeTipoPessoa($row['tipo_pessoa'] ?? null, $row['cpf_cnpj'] ?? null),
            'nome_razao'       => $this->normalizer->normalizeString($row['nome_razao'] ?? null),
            'cpf_cnpj'         => $this->normalizer->normalizeDocument($row['cpf_cnpj'] ?? null),
            'rg_ie'            => $this->normalizer->normalizeString($row['rg_ie'] ?? null),
            'email'            => $this->normalizer->normalizeString($row['email'] ?? null),
            'telefone1'        => $telefonePrincipal ?? '',
            'telefone2'        => $this->normalizer->normalizePhone($row['telefone2'] ?? null),
            'nome_contato'     => $this->normalizer->normalizeString($row['nome_contato'] ?? null),
            'telefone_contato' => $this->normalizer->normalizePhone($row['telefone_contato'] ?? null),
            'cep'              => $this->normalizer->normalizeString($row['cep'] ?? null),
            'endereco'         => $this->normalizer->normalizeString($row['endereco'] ?? null),
            'numero'           => $this->normalizer->normalizeString($row['numero'] ?? null),
            'complemento'      => $this->normalizer->normalizeString($row['complemento'] ?? null),
            'bairro'           => $this->normalizer->normalizeString($row['bairro'] ?? null),
            'cidade'           => $this->normalizer->normalizeString($row['cidade'] ?? null),
            'uf'               => $this->normalizer->normalizeString($row['uf'] ?? null),
            'observacoes'      => $this->normalizer->normalizeString($row['observacoes'] ?? null),
        ];
        $legacyMetadata = [
            'legacy_origem' => $this->config->sourceName,
            'legacy_id'     => $legacyId,
        ];
        $matchKeys = $this->buildClienteMatchKeys($row);

        if ($payload['nome_razao'] === null) {
            return $summary;
        }

        $existing = $this->findClienteTargetByLegacyReference($legacyId);

        if ($existing) {
            $target = $existing['target'];
            $updatePayload = $payload;
            if (empty($target['legacy_origem']) || empty($target['legacy_id'])) {
                $updatePayload = array_merge($updatePayload, $legacyMetadata);
            }

            $this->clienteModel->skipValidation(true)->update((int) $target['id'], $updatePayload);
            $this->replaceClienteAliases($legacyId, (int) $target['id'], $matchKeys, $existing['strategy']);
            $summary = $this->incrementSummary($summary, 'clientes', 'updated');
            $this->registerEvent($runId, 'clientes', 'info', 'updated', $legacyId, 'Cliente legado atualizado pelo alias legada existente.', [
                'target_id' => (int) $target['id'],
                'strategy' => $existing['strategy'],
            ]);
            return $summary;
        }

        $strongMatch = $this->findClienteTargetByStrongKeys($matchKeys);
        if (($strongMatch['target'] ?? null) !== null) {
            if (! ($strongMatch['safe'] ?? false)) {
                return $this->addIssue($summary, $runId, 'clientes', 'error', 'skipped_conflict', $legacyId, 'Cliente com identificador forte ja existe no destino sem rastreabilidade legada segura; importacao nao vai mesclar automaticamente.', [
                    'target_id' => $strongMatch['target']['id'] ?? null,
                    'match_key_type' => $strongMatch['match_key_type'] ?? null,
                    'match_key_value' => $strongMatch['match_key_value'] ?? null,
                ], 'ignored');
            }

            $merged = $this->mergeCanonicalClientePayload($strongMatch['target'], $payload, $legacyMetadata);
            $this->clienteModel->skipValidation(true)->update((int) $strongMatch['target']['id'], $merged['payload']);
            $this->replaceClienteAliases($legacyId, (int) $strongMatch['target']['id'], $matchKeys, $strongMatch['strategy'] ?? 'strong_match');

            if ($merged['conflicts'] !== []) {
                $summary = $this->addIssue($summary, $runId, 'clientes', 'warning', 'strong_match_payload_conflict', $legacyId, 'Cliente legado reutilizou registro canonico por identificador forte e preservou campos ja existentes no destino.', [
                    'target_id' => $strongMatch['target']['id'] ?? null,
                    'conflicts' => $merged['conflicts'],
                    'match_key_type' => $strongMatch['match_key_type'] ?? null,
                    'match_key_value' => $strongMatch['match_key_value'] ?? null,
                ]);
            }

            $summary = $this->incrementSummary($summary, 'clientes', 'updated');
            $this->registerEvent($runId, 'clientes', 'info', 'deduplicated', $legacyId, 'Cliente legado vinculado a cliente canonico ja importado por identificador forte.', [
                'target_id' => (int) $strongMatch['target']['id'],
                'strategy' => $strongMatch['strategy'] ?? 'strong_match',
                'match_key_type' => $strongMatch['match_key_type'] ?? null,
                'match_key_value' => $strongMatch['match_key_value'] ?? null,
            ]);
            return $summary;
        }

        $inserted = $this->clienteModel->skipValidation(true)->insert(array_merge($payload, $legacyMetadata), true);
        if (! $inserted) {
            return $this->addIssue($summary, $runId, 'clientes', 'error', 'insert_failed', $legacyId, 'Falha ao inserir cliente legado.', [
                'errors' => $this->clienteModel->errors(),
            ]);
        }

        $this->replaceClienteAliases($legacyId, (int) $inserted, $matchKeys, 'legacy_primary');
        $summary = $this->incrementSummary($summary, 'clientes', 'imported');
        $this->registerEvent($runId, 'clientes', 'info', 'inserted', $legacyId, 'Cliente legado importado com sucesso.', [
            'target_id' => (int) $inserted,
        ]);

        return $summary;
    }

    private function importEquipamentoRow(array $row, array $summary, ?int $runId): array
    {
        $legacyId = (string) $this->normalizer->normalizeLegacyId($row['legacy_id'] ?? null);
        $legacyClienteId = (string) $this->normalizer->normalizeLegacyId($row['legacy_cliente_id'] ?? null);

        $clienteDestino = $this->resolveClienteTargetForRelations($legacyClienteId);

        if (! $clienteDestino) {
            return $this->addIssue($summary, $runId, 'equipamentos', 'error', 'orphan_client', $legacyId, 'Equipamento legado nao encontrou cliente importado no destino.', [
                'legacy_cliente_id' => $legacyClienteId,
            ]);
        }

        $tipoId = $this->catalogResolver->resolveTipoId($row['tipo_nome'] ?? null);
        $marcaId = $this->catalogResolver->resolveMarcaId($row['marca_nome'] ?? null);
        $modeloId = $marcaId ? $this->catalogResolver->resolveModeloId($marcaId, $row['modelo_nome'] ?? null) : null;

        if (! $tipoId || ! $marcaId || ! $modeloId) {
            return $this->addIssue($summary, $runId, 'equipamentos', 'error', 'missing_catalog', $legacyId, 'Equipamento legado nao conseguiu resolver catalogo no destino.', [
                'tipo_nome' => $row['tipo_nome'] ?? null,
                'marca_nome' => $row['marca_nome'] ?? null,
                'modelo_nome' => $row['modelo_nome'] ?? null,
            ]);
        }

        $payload = [
            'cliente_id'     => (int) $clienteDestino['id'],
            'tipo_id'        => $tipoId,
            'marca_id'       => $marcaId,
            'modelo_id'      => $modeloId,
            'cor'            => $this->normalizer->normalizeString($row['cor'] ?? null),
            'cor_hex'        => $this->normalizer->normalizeString($row['cor_hex'] ?? null),
            'cor_rgb'        => $this->normalizer->normalizeString($row['cor_rgb'] ?? null),
            'numero_serie'   => $this->normalizer->normalizeString($row['numero_serie'] ?? null),
            'imei'           => $this->normalizer->normalizeImei($row['imei'] ?? null),
            'senha_acesso'   => $this->normalizer->normalizeString($row['senha_acesso'] ?? null),
            'estado_fisico'  => $this->normalizer->normalizeString($row['estado_fisico'] ?? null),
            'acessorios'     => $this->normalizer->normalizeString($row['acessorios'] ?? null),
            'observacoes'    => $this->normalizer->normalizeString($row['observacoes'] ?? null),
        ];
        $legacyMetadata = [
            'legacy_origem' => $this->config->sourceName,
            'legacy_id'     => $legacyId,
        ];
        $matchKeys = $this->buildEquipamentoMatchKeys($row);

        $existing = $this->findEquipamentoTargetByLegacyReference($legacyId);

        if ($existing) {
            $target = $existing['target'];
            $updatePayload = $payload;
            if (empty($target['legacy_origem']) || empty($target['legacy_id'])) {
                $updatePayload = array_merge($updatePayload, $legacyMetadata);
            }

            $this->equipamentoModel->update((int) $target['id'], $updatePayload);
            $this->replaceEquipamentoAliases($legacyId, (int) $target['id'], $matchKeys, $existing['strategy']);
            $summary = $this->incrementSummary($summary, 'equipamentos', 'updated');
            $this->registerEvent($runId, 'equipamentos', 'info', 'updated', $legacyId, 'Equipamento legado atualizado pelo alias legada existente.', [
                'target_id' => (int) $target['id'],
                'strategy' => $existing['strategy'],
            ]);
            return $summary;
        }

        $strongMatch = $this->findEquipamentoTargetByStrongKeys($matchKeys);
        if (($strongMatch['target'] ?? null) !== null) {
            if (! ($strongMatch['safe'] ?? false)) {
                return $this->addIssue($summary, $runId, 'equipamentos', 'error', 'skipped_conflict', $legacyId, 'Equipamento com identificador forte ja existe no destino sem rastreabilidade legada segura; importacao nao vai mesclar automaticamente.', [
                    'target_id' => $strongMatch['target']['id'] ?? null,
                    'match_key_type' => $strongMatch['match_key_type'] ?? null,
                    'match_key_value' => $strongMatch['match_key_value'] ?? null,
                ], 'ignored');
            }

            $merged = $this->mergeCanonicalEquipamentoPayload($strongMatch['target'], $payload, $legacyMetadata);
            $this->equipamentoModel->update((int) $strongMatch['target']['id'], $merged['payload']);
            $this->replaceEquipamentoAliases($legacyId, (int) $strongMatch['target']['id'], $matchKeys, $strongMatch['strategy'] ?? 'strong_match');

            if ($merged['conflicts'] !== []) {
                $summary = $this->addIssue($summary, $runId, 'equipamentos', 'warning', 'strong_match_payload_conflict', $legacyId, 'Equipamento legado reutilizou registro canonico por identificador forte e preservou campos ja existentes no destino.', [
                    'target_id' => $strongMatch['target']['id'] ?? null,
                    'conflicts' => $merged['conflicts'],
                    'match_key_type' => $strongMatch['match_key_type'] ?? null,
                    'match_key_value' => $strongMatch['match_key_value'] ?? null,
                ]);
            }

            $summary = $this->incrementSummary($summary, 'equipamentos', 'updated');
            $this->registerEvent($runId, 'equipamentos', 'info', 'deduplicated', $legacyId, 'Equipamento legado vinculado a equipamento canonico ja importado por identificador forte.', [
                'target_id' => (int) $strongMatch['target']['id'],
                'strategy' => $strongMatch['strategy'] ?? 'strong_match',
                'match_key_type' => $strongMatch['match_key_type'] ?? null,
                'match_key_value' => $strongMatch['match_key_value'] ?? null,
            ]);
            return $summary;
        }

        $inserted = $this->equipamentoModel->insert(array_merge($payload, $legacyMetadata), true);
        if (! $inserted) {
            return $this->addIssue($summary, $runId, 'equipamentos', 'error', 'insert_failed', $legacyId, 'Falha ao inserir equipamento legado.', [
                'errors' => $this->equipamentoModel->errors(),
            ]);
        }

        $this->replaceEquipamentoAliases($legacyId, (int) $inserted, $matchKeys, 'legacy_primary');
        $summary = $this->incrementSummary($summary, 'equipamentos', 'imported');
        $this->registerEvent($runId, 'equipamentos', 'info', 'inserted', $legacyId, 'Equipamento legado importado com sucesso.', [
            'target_id' => (int) $inserted,
        ]);

        return $summary;
    }

    private function importOsRow(array $row, array $summary, ?int $runId): array
    {
        $legacyId = (string) $this->normalizer->normalizeLegacyId($row['legacy_id'] ?? null);
        $legacyClienteId = (string) $this->normalizer->normalizeLegacyId($row['legacy_cliente_id'] ?? null);
        $legacyEquipamentoId = (string) $this->normalizer->normalizeLegacyId($row['legacy_equipamento_id'] ?? null);
        $statusLegado = $this->normalizeStatusCode($row['status_legado'] ?? null);
        $mappedStatus = $statusLegado !== null ? ($this->config->statusMap[$statusLegado] ?? null) : null;

        if ($mappedStatus === null) {
            return $summary;
        }

        $clienteDestino = $this->resolveClienteTargetForRelations($legacyClienteId);
        $equipamentoDestino = $this->resolveEquipamentoTargetForOs($legacyEquipamentoId);

        if (! $clienteDestino || ! $equipamentoDestino) {
            return $this->addIssue($summary, $runId, 'os', 'error', 'missing_related_target', $legacyId, 'OS legada nao encontrou cliente/equipamento importado no destino.', [
                'legacy_cliente_id' => $legacyClienteId,
                'legacy_equipamento_id' => $legacyEquipamentoId,
            ]);
        }

        $existing = $this->osModel
            ->where('legacy_origem', $this->config->sourceName)
            ->where('legacy_id', $legacyId)
            ->first();

        $payload = [
            'cliente_id'            => (int) $clienteDestino['id'],
            'equipamento_id'        => (int) $equipamentoDestino['id'],
            'status'                => $mappedStatus,
            'estado_fluxo'          => $this->statusFlowService->resolveEstadoFluxo($mappedStatus),
            'status_atualizado_em'  => date('Y-m-d H:i:s'),
            'legacy_origem'         => $this->config->sourceName,
            'legacy_id'             => $legacyId,
            'numero_os_legado'      => $this->normalizer->normalizeString($row['numero_os_legado'] ?? null),
            'prioridade'            => $this->normalizer->normalizePriority($row['prioridade'] ?? null),
            'relato_cliente'        => $this->normalizer->normalizeString($row['relato_cliente'] ?? null) ?? 'Relato nao informado no sistema legado.',
            'diagnostico_tecnico'   => $this->normalizer->normalizeString($row['diagnostico_tecnico'] ?? null),
            'solucao_aplicada'      => $this->normalizer->normalizeString($row['solucao_aplicada'] ?? null),
            'data_abertura'         => $this->normalizer->normalizeDateTime($row['data_abertura'] ?? null) ?? date('Y-m-d H:i:s'),
            'data_entrada'          => $this->normalizer->normalizeDateTime($row['data_entrada'] ?? null),
            'data_previsao'         => $this->normalizer->normalizeDateTime($row['data_previsao'] ?? null, true),
            'data_conclusao'        => $this->normalizer->normalizeDateTime($row['data_conclusao'] ?? null),
            'data_entrega'          => $this->normalizer->normalizeDateTime($row['data_entrega'] ?? null),
            'valor_mao_obra'        => $this->normalizer->normalizeDecimal($row['valor_mao_obra'] ?? null) ?? 0.0,
            'valor_pecas'           => $this->normalizer->normalizeDecimal($row['valor_pecas'] ?? null) ?? 0.0,
            'valor_total'           => $this->normalizer->normalizeDecimal($row['valor_total'] ?? null) ?? 0.0,
            'desconto'              => $this->normalizer->normalizeDecimal($row['desconto'] ?? null) ?? 0.0,
            'valor_final'           => $this->normalizer->normalizeDecimal($row['valor_final'] ?? null) ?? 0.0,
            'orcamento_aprovado'    => $this->normalizer->normalizeBoolean($row['orcamento_aprovado'] ?? null),
            'data_aprovacao'        => $this->normalizer->normalizeDateTime($row['data_aprovacao'] ?? null),
            'garantia_dias'         => max(0, (int) ($row['garantia_dias'] ?? 0)),
            'garantia_validade'     => $this->normalizer->normalizeDateTime($row['garantia_validade'] ?? null, true),
            'observacoes_internas'  => $this->normalizer->normalizeString($row['observacoes_internas'] ?? null),
            'observacoes_cliente'   => $this->normalizer->normalizeString($row['observacoes_cliente'] ?? null),
            'acessorios'            => $this->normalizer->normalizeString($row['acessorios'] ?? null),
            'forma_pagamento'       => $this->normalizer->normalizeString($row['forma_pagamento'] ?? null),
        ];

        if ($existing) {
            $payload['numero_os'] = $existing['numero_os'];
            $this->osModel->update((int) $existing['id'], $payload);
            $summary = $this->incrementSummary($summary, 'os', 'updated');
            $this->registerEvent($runId, 'os', 'info', 'updated', $legacyId, 'OS legada atualizada pelo par legacy_origem/legacy_id.', [
                'target_id' => (int) $existing['id'],
                'numero_os' => $existing['numero_os'] ?? null,
            ]);
            return $summary;
        }

        $conflict = null;
        if (! empty($payload['numero_os_legado'])) {
            $conflict = $this->osModel->where('numero_os_legado', $payload['numero_os_legado'])->first();
        }
        if ($conflict && empty($conflict['legacy_id'])) {
            return $this->addIssue($summary, $runId, 'os', 'error', 'skipped_conflict', $legacyId, 'OS ja existe no destino com numero legado igual, mas sem marcador legado.', [
                'target_id' => $conflict['id'] ?? null,
            ], 'ignored');
        }

        $payload['numero_os'] = $this->osModel->generateNumeroOs();

        $inserted = $this->osModel->insert($payload, true);
        if (! $inserted) {
            return $this->addIssue($summary, $runId, 'os', 'error', 'insert_failed', $legacyId, 'Falha ao inserir OS legada.', [
                'errors' => $this->osModel->errors(),
            ]);
        }

        if ($this->config->writeInitialStatusHistory && $this->targetDb->tableExists('os_status_historico')) {
            $this->osStatusHistoricoModel->insert([
                'os_id'           => (int) $inserted,
                'status_anterior' => null,
                'status_novo'     => $mappedStatus,
                'estado_fluxo'    => $payload['estado_fluxo'],
                'usuario_id'      => null,
                'observacao'      => 'Migrado do sistema legado ' . $this->config->sourceName . ' (OS legado ' . ($payload['numero_os_legado'] ?? '-') . ')',
                'created_at'      => $payload['data_entrada'] ?? $payload['data_abertura'] ?? date('Y-m-d H:i:s'),
            ]);
        }

        $summary = $this->incrementSummary($summary, 'os', 'imported');
        $this->registerEvent($runId, 'os', 'info', 'inserted', $legacyId, 'OS legada importada com sucesso.', [
            'target_id' => (int) $inserted,
            'numero_os' => $payload['numero_os'],
            'numero_os_legado' => $payload['numero_os_legado'],
        ]);

        return $summary;
    }

    private function importOsItemRow(array $row, array $summary, ?int $runId): array
    {
        $legacyId = (string) $this->normalizer->normalizeLegacyId($row['legacy_id'] ?? null);
        $legacyOsId = (string) $this->normalizer->normalizeLegacyId($row['legacy_os_id'] ?? null);
        $legacyTabela = $this->normalizer->normalizeString($row['legacy_tabela'] ?? null) ?? 'os_itens_legado';
        $targetOs = $this->resolveOsTargetByLegacyId($legacyOsId);

        if ($targetOs === null) {
            return $this->addIssue($summary, $runId, 'os_itens', 'warning', 'missing_target_os', $legacyId, 'Item legado nao encontrou OS correspondente no destino e sera ignorado.', [
                'legacy_os_id' => $legacyOsId,
            ], 'ignored');
        }

        $osId = (int) $targetOs['id'];
        $this->cleanupSyntheticOsItemsForOs($osId);

        $quantidade = $this->normalizeQuantity($row['quantidade'] ?? null);
        $valorUnitario = $this->normalizer->normalizeDecimal($row['valor_unitario'] ?? null);
        $valorTotal = $this->normalizer->normalizeDecimal($row['valor_total'] ?? null);

        if ($valorUnitario === null && $valorTotal !== null && $quantidade > 0) {
            $valorUnitario = round($valorTotal / $quantidade, 2);
        }
        if ($valorTotal === null) {
            $valorTotal = round(($valorUnitario ?? 0.0) * $quantidade, 2);
        }

        $payload = [
            'os_id' => $osId,
            'legacy_origem' => $this->config->sourceName,
            'legacy_tabela' => $legacyTabela,
            'legacy_id' => $legacyId,
            'tipo' => $this->normalizeItemType($row['tipo'] ?? null),
            'descricao' => $this->normalizer->normalizeString($row['descricao'] ?? null) ?? 'Item legado importado',
            'observacao' => $this->normalizer->normalizeString($row['observacao'] ?? null),
            'quantidade' => $quantidade,
            'valor_unitario' => $valorUnitario ?? 0.0,
            'valor_total' => $valorTotal ?? 0.0,
            'peca_id' => null,
        ];

        $existing = $this->osItemModel
            ->where('legacy_origem', $this->config->sourceName)
            ->where('legacy_tabela', $legacyTabela)
            ->where('legacy_id', $legacyId)
            ->first();

        if ($existing !== null) {
            $this->osItemModel->update((int) $existing['id'], $payload);
            $summary = $this->incrementSummary($summary, 'os_itens', 'updated');
            $this->registerEvent($runId, 'os_itens', 'info', 'updated', $legacyId, 'Item legado da OS atualizado com sucesso.', [
                'target_id' => (int) $existing['id'],
                'target_os_id' => $osId,
            ]);
            return $summary;
        }

        $inserted = $this->osItemModel->insert($payload, true);
        if (! $inserted) {
            return $this->addIssue($summary, $runId, 'os_itens', 'error', 'insert_failed', $legacyId, 'Falha ao inserir item legado da OS.', [
                'errors' => $this->osItemModel->errors(),
                'legacy_os_id' => $legacyOsId,
            ]);
        }

        $summary = $this->incrementSummary($summary, 'os_itens', 'imported');
        $this->registerEvent($runId, 'os_itens', 'info', 'inserted', $legacyId, 'Item legado da OS importado com sucesso.', [
            'target_id' => (int) $inserted,
            'target_os_id' => $osId,
        ]);

        return $summary;
    }

    private function importOsStatusHistoricoRow(array $row, array $summary, ?int $runId): array
    {
        $legacyId = (string) $this->normalizer->normalizeLegacyId($row['legacy_id'] ?? null);
        $legacyOsId = (string) $this->normalizer->normalizeLegacyId($row['legacy_os_id'] ?? null);
        $legacyTabela = $this->normalizer->normalizeString($row['legacy_tabela'] ?? null) ?? 'os_status_historico';
        $targetOs = $this->resolveOsTargetByLegacyId($legacyOsId);

        if ($targetOs === null) {
            return $this->addIssue($summary, $runId, 'os_status_historico', 'warning', 'missing_target_os', $legacyId, 'Historico legado nao encontrou OS correspondente no destino e sera ignorado.', [
                'legacy_os_id' => $legacyOsId,
            ], 'ignored');
        }

        $statusAnteriorLegado = $this->normalizeStatusCode($row['status_anterior'] ?? null);
        $statusNovoLegado = $this->normalizeStatusCode($row['status_novo'] ?? null);
        $statusAnterior = $statusAnteriorLegado !== null ? ($this->config->statusMap[$statusAnteriorLegado] ?? null) : null;
        $statusNovo = $statusNovoLegado !== null ? ($this->config->statusMap[$statusNovoLegado] ?? null) : null;

        if ($statusNovo === null && $statusAnterior !== null) {
            $statusNovo = $statusAnterior;
        }

        $observacao = $this->composeLegacyHistoryObservation($row);
        $createdAt = $this->normalizer->normalizeDateTime($row['data_alteracao'] ?? null) ?? date('Y-m-d H:i:s');

        if ($statusNovo === null) {
            return $this->upsertLegacyNote(
                (int) $targetOs['id'],
                $legacyTabela,
                $legacyId,
                'Historico legado',
                $observacao ?? 'Historico legado sem mapeamento de status.',
                $createdAt,
                $summary,
                $runId
            );
        }

        $payload = [
            'os_id' => (int) $targetOs['id'],
            'legacy_origem' => $this->config->sourceName,
            'legacy_tabela' => $legacyTabela,
            'legacy_id' => $legacyId,
            'status_anterior' => $statusAnterior,
            'status_novo' => $statusNovo,
            'estado_fluxo' => $this->statusFlowService->resolveEstadoFluxo($statusNovo),
            'usuario_id' => null,
            'observacao' => $observacao,
            'created_at' => $createdAt,
        ];

        $existing = $this->osStatusHistoricoModel
            ->where('legacy_origem', $this->config->sourceName)
            ->where('legacy_tabela', $legacyTabela)
            ->where('legacy_id', $legacyId)
            ->first();

        if ($existing !== null) {
            $this->osStatusHistoricoModel->update((int) $existing['id'], $payload);
            $summary = $this->incrementSummary($summary, 'os_status_historico', 'updated');
            $this->registerEvent($runId, 'os_status_historico', 'info', 'updated', $legacyId, 'Historico de status legado atualizado com sucesso.', [
                'target_id' => (int) $existing['id'],
                'target_os_id' => (int) $targetOs['id'],
            ]);
            return $summary;
        }

        $inserted = $this->osStatusHistoricoModel->insert($payload, true);
        if (! $inserted) {
            return $this->addIssue($summary, $runId, 'os_status_historico', 'error', 'insert_failed', $legacyId, 'Falha ao inserir historico legado da OS.', [
                'errors' => $this->osStatusHistoricoModel->errors(),
                'legacy_os_id' => $legacyOsId,
            ]);
        }

        $summary = $this->incrementSummary($summary, 'os_status_historico', 'imported');
        $this->registerEvent($runId, 'os_status_historico', 'info', 'inserted', $legacyId, 'Historico de status legado importado com sucesso.', [
            'target_id' => (int) $inserted,
            'target_os_id' => (int) $targetOs['id'],
        ]);

        return $summary;
    }

    private function importOsDefeitoRow(array $row, array $summary, ?int $runId): array
    {
        $legacyId = (string) $this->normalizer->normalizeLegacyId($row['legacy_id'] ?? null);
        $legacyOsId = (string) $this->normalizer->normalizeLegacyId($row['legacy_os_id'] ?? null);
        $legacyTabela = $this->normalizer->normalizeString($row['legacy_tabela'] ?? null) ?? 'os_defeitos';
        $targetOs = $this->resolveOsTargetByLegacyId($legacyOsId);

        if ($targetOs === null) {
            return $this->addIssue($summary, $runId, 'os_defeitos', 'warning', 'missing_target_os', $legacyId, 'Defeito legado nao encontrou OS correspondente no destino e sera ignorado.', [
                'legacy_os_id' => $legacyOsId,
            ], 'ignored');
        }

        $defeitoId = $this->resolveTargetDefeitoId($targetOs, $row);
        if ($defeitoId === null) {
            return $this->addIssue($summary, $runId, 'os_defeitos', 'warning', 'unresolved_defect', $legacyId, 'Defeito legado nao conseguiu ser resolvido no catalogo do destino.', [
                'legacy_os_id' => $legacyOsId,
                'descricao' => $row['descricao'] ?? null,
            ], 'ignored');
        }

        $payload = [
            'os_id' => (int) $targetOs['id'],
            'defeito_id' => $defeitoId,
            'legacy_origem' => $this->config->sourceName,
            'legacy_tabela' => $legacyTabela,
            'legacy_id' => $legacyId,
            'created_at' => $this->normalizer->normalizeDateTime($row['created_at'] ?? null) ?? date('Y-m-d H:i:s'),
        ];

        $existing = $this->osDefeitoModel
            ->where('legacy_origem', $this->config->sourceName)
            ->where('legacy_tabela', $legacyTabela)
            ->where('legacy_id', $legacyId)
            ->first();

        if ($existing !== null) {
            $this->osDefeitoModel->update((int) $existing['id'], $payload);
            $summary = $this->incrementSummary($summary, 'os_defeitos', 'updated');
            $this->registerEvent($runId, 'os_defeitos', 'info', 'updated', $legacyId, 'Defeito legado atualizado com sucesso.', [
                'target_id' => (int) $existing['id'],
                'target_os_id' => (int) $targetOs['id'],
            ]);
            return $summary;
        }

        $inserted = $this->osDefeitoModel->insert($payload, true);
        if (! $inserted) {
            return $this->addIssue($summary, $runId, 'os_defeitos', 'error', 'insert_failed', $legacyId, 'Falha ao inserir defeito legado da OS.', [
                'errors' => $this->osDefeitoModel->errors(),
                'legacy_os_id' => $legacyOsId,
            ]);
        }

        $summary = $this->incrementSummary($summary, 'os_defeitos', 'imported');
        $this->registerEvent($runId, 'os_defeitos', 'info', 'inserted', $legacyId, 'Defeito legado importado com sucesso.', [
            'target_id' => (int) $inserted,
            'target_os_id' => (int) $targetOs['id'],
        ]);

        return $summary;
    }

    private function importOsNotaLegadaRow(array $row, array $summary, ?int $runId): array
    {
        $legacyId = (string) $this->normalizer->normalizeLegacyId($row['legacy_id'] ?? null);
        $legacyOsId = (string) $this->normalizer->normalizeLegacyId($row['legacy_os_id'] ?? null);
        $legacyTabela = $this->normalizer->normalizeString($row['legacy_tabela'] ?? null) ?? 'os_notas_legadas';
        $targetOs = $this->resolveOsTargetByLegacyId($legacyOsId);

        if ($targetOs === null) {
            return $this->addIssue($summary, $runId, 'os_notas_legadas', 'warning', 'missing_target_os', $legacyId, 'Nota legada nao encontrou OS correspondente no destino e sera ignorada.', [
                'legacy_os_id' => $legacyOsId,
            ], 'ignored');
        }

        return $this->upsertLegacyNote(
            (int) $targetOs['id'],
            $legacyTabela,
            $legacyId,
            $this->normalizer->normalizeString($row['titulo'] ?? null) ?? 'Nota legada',
            $this->normalizer->normalizeString($row['conteudo'] ?? null) ?? 'Nota legada sem conteudo detalhado.',
            $this->normalizer->normalizeDateTime($row['created_at'] ?? null) ?? date('Y-m-d H:i:s'),
            $summary,
            $runId
        );
    }

    private function backfillSyntheticOsItems(array $row, array $summary, ?int $runId): array
    {
        $legacyOsId = (string) $this->normalizer->normalizeLegacyId($row['legacy_id'] ?? null);
        if ($legacyOsId === '') {
            return $summary;
        }

        $targetOs = $this->resolveOsTargetByLegacyId($legacyOsId);
        if ($targetOs === null) {
            return $summary;
        }

        $osId = (int) $targetOs['id'];
        $hasDetailedItems = $this->osItemModel
            ->where('os_id', $osId)
            ->where('legacy_origem', $this->config->sourceName)
            ->whereNotIn('legacy_tabela', ['os_totais_servico', 'os_totais_peca', 'os_totais_consolidado'])
            ->countAllResults() > 0;

        if ($hasDetailedItems) {
            $this->cleanupSyntheticOsItemsForOs($osId);
            return $summary;
        }

        $serviceTotal = $this->normalizer->normalizeDecimal($row['valor_mao_obra'] ?? null) ?? 0.0;
        $partsTotal = $this->normalizer->normalizeDecimal($row['valor_pecas'] ?? null) ?? 0.0;
        $legacyRawLabor = $this->normalizer->normalizeDecimal($row['legacy_mao_obra_bruta'] ?? null) ?? 0.0;
        $legacyServiceTotal = $this->normalizer->normalizeDecimal($row['legacy_total_servicos'] ?? null) ?? 0.0;
        $legacyPartsTotal = $this->normalizer->normalizeDecimal($row['legacy_total_produtos'] ?? null) ?? 0.0;
        $legacySubtotal = $this->normalizer->normalizeDecimal($row['legacy_subtotal'] ?? null) ?? 0.0;
        $legacyFinal = $this->normalizer->normalizeDecimal($row['valor_final'] ?? null) ?? 0.0;
        $legacyDiscount = $this->normalizer->normalizeDecimal($row['desconto'] ?? null) ?? 0.0;

        $serviceSource = $legacyRawLabor > 0 ? 'valor_mao_obra' : ($legacyServiceTotal > 0 ? 'total_servicos' : 'valor_mao_obra');
        $partsSource = 'valor_pecas';
        $consolidatedTotal = 0.0;
        $consolidatedSource = null;

        if ($serviceTotal <= 0 && $legacyServiceTotal > 0) {
            $serviceTotal = $legacyServiceTotal;
            $serviceSource = 'total_servicos';
        }

        if ($partsTotal <= 0 && $legacyPartsTotal > 0) {
            $partsTotal = $legacyPartsTotal;
            $partsSource = 'total_produtos';
        }

        if ($serviceTotal <= 0 && $partsTotal <= 0) {
            if ($legacySubtotal > 0) {
                $consolidatedTotal = $legacySubtotal;
                $consolidatedSource = 'subtotal';
            } elseif (($legacyFinal + $legacyDiscount) > 0) {
                $consolidatedTotal = $legacyFinal + $legacyDiscount;
                $consolidatedSource = $legacyDiscount > 0 ? 'valor_final+desconto' : 'valor_final';
            }
        }

        $summary = $this->upsertSyntheticOsItem(
            $summary,
            $runId,
            $osId,
            $legacyOsId,
            'os_totais_servico',
            'servico',
            'Servico legado importado',
            $this->buildSyntheticOsItemObservation('servicos', $serviceSource),
            $serviceTotal
        );
        $summary = $this->upsertSyntheticOsItem(
            $summary,
            $runId,
            $osId,
            $legacyOsId,
            'os_totais_peca',
            'peca',
            'Pecas legadas importadas',
            $this->buildSyntheticOsItemObservation('pecas', $partsSource),
            $partsTotal
        );
        $summary = $this->upsertSyntheticOsItem(
            $summary,
            $runId,
            $osId,
            $legacyOsId,
            'os_totais_consolidado',
            'servico',
            'Valor legado consolidado',
            $this->buildSyntheticOsItemObservation('consolidado', $consolidatedSource),
            $consolidatedTotal
        );

        return $summary;
    }

    private function buildSyntheticOsItemObservation(string $segment, ?string $sourceField): string
    {
        $segmentLabel = match ($segment) {
            'servicos' => 'servicos',
            'pecas' => 'pecas',
            default => 'valor consolidado',
        };

        $sourceLabel = match ($sourceField) {
            'valor_mao_obra' => 'os.mao_obra',
            'total_servicos' => 'os.total_servicos',
            'valor_pecas' => 'os.total_produtos',
            'total_produtos' => 'os.total_produtos',
            'subtotal' => 'os.subtotal',
            'valor_final' => 'os.valor',
            'valor_final+desconto' => 'os.valor + desconto',
            default => 'cabecalho financeiro da OS legada',
        };

        if ($segment === 'consolidado') {
            return 'Valor consolidado importado do legado a partir de ' . $sourceLabel . '; a origem nao discriminava servicos e pecas em itens estruturados.';
        }

        return 'Valor consolidado importado do legado a partir de ' . $sourceLabel . '; nao havia detalhamento estruturado de ' . $segmentLabel . ' na origem.';
    }

    private function findClienteTargetByLegacyReference(string $legacyId): ?array
    {
        $alias = $this->findLegacyAliasBySource('clientes', $legacyId, 'clientes');
        if ($alias !== null) {
            $target = $this->clienteModel->find((int) $alias['target_id']);
            if ($target !== null) {
                return [
                    'target' => $target,
                    'strategy' => $alias['resolution_strategy'] ?? 'alias_reference',
                ];
            }
        }

        $target = $this->clienteModel
            ->where('legacy_origem', $this->config->sourceName)
            ->where('legacy_id', $legacyId)
            ->first();

        if ($target === null) {
            return null;
        }

        return [
            'target' => $target,
            'strategy' => 'legacy_reference',
        ];
    }

    private function findClienteTargetByStrongKeys(array $matchKeys): array
    {
        foreach ($matchKeys as $matchKey) {
            $alias = $this->findLegacyAliasByMatchKey('clientes', 'clientes', $matchKey['type'], $matchKey['value']);
            if ($alias !== null) {
                $target = $this->clienteModel->find((int) $alias['target_id']);
                if ($target !== null) {
                    return [
                        'safe' => true,
                        'target' => $target,
                        'strategy' => 'strong_' . $matchKey['type'],
                        'match_key_type' => $matchKey['type'],
                        'match_key_value' => $matchKey['value'],
                    ];
                }
            }

            $unsafeTarget = $this->findUnsafeClienteConflict($matchKey);
            if ($unsafeTarget !== null) {
                return [
                    'safe' => false,
                    'target' => $unsafeTarget,
                    'strategy' => 'conflict_' . $matchKey['type'],
                    'match_key_type' => $matchKey['type'],
                    'match_key_value' => $matchKey['value'],
                ];
            }
        }

        return [
            'safe' => true,
            'target' => null,
        ];
    }

    private function resolveClienteTargetForRelations(string $legacyClienteId): ?array
    {
        $resolved = $this->findClienteTargetByLegacyReference($legacyClienteId);
        return $resolved['target'] ?? null;
    }

    private function buildClienteMatchKeys(array $row): array
    {
        $keys = [];
        $documento = $this->normalizer->normalizeDocument($row['cpf_cnpj'] ?? null);

        if ($this->normalizer->isValidDocument($documento)) {
            $keys[] = [
                'type' => 'cpf_cnpj',
                'value' => $documento,
            ];
        }

        return $keys;
    }

    private function mergeCanonicalClientePayload(array $existing, array $incoming, array $legacyMetadata): array
    {
        $fields = [
            'tipo_pessoa',
            'nome_razao',
            'cpf_cnpj',
            'rg_ie',
            'email',
            'telefone1',
            'telefone2',
            'nome_contato',
            'telefone_contato',
            'cep',
            'endereco',
            'numero',
            'complemento',
            'bairro',
            'cidade',
            'uf',
            'observacoes',
        ];

        $payload = $incoming;
        $conflicts = [];

        foreach ($fields as $field) {
            $existingValue = $existing[$field] ?? null;
            $incomingValue = $incoming[$field] ?? null;

            if (! $this->hasMeaningfulValue($existingValue)) {
                continue;
            }

            if (! $this->hasMeaningfulValue($incomingValue)) {
                $payload[$field] = $existingValue;
                continue;
            }

            if ((string) $existingValue !== (string) $incomingValue) {
                $payload[$field] = $existingValue;
                $conflicts[$field] = [
                    'kept' => $existingValue,
                    'ignored' => $incomingValue,
                ];
            }
        }

        if (empty($existing['legacy_origem']) || empty($existing['legacy_id'])) {
            $payload = array_merge($payload, $legacyMetadata);
        }

        return [
            'payload' => $payload,
            'conflicts' => $conflicts,
        ];
    }

    private function replaceClienteAliases(string $legacyId, int $targetId, array $matchKeys, string $strategy): void
    {
        if (! $this->targetDb->tableExists('legacy_import_aliases')) {
            return;
        }

        $this->aliasModel
            ->where('source_name', $this->config->sourceName)
            ->where('source_entity', 'clientes')
            ->where('source_legacy_id', $legacyId)
            ->where('target_entity', 'clientes')
            ->delete();

        $rows = [];
        if ($matchKeys === []) {
            $rows[] = [
                'match_key_type' => 'reference',
                'match_key_value' => '',
            ];
        } else {
            $seen = [];
            foreach ($matchKeys as $matchKey) {
                $fingerprint = $matchKey['type'] . '|' . $matchKey['value'];
                if (isset($seen[$fingerprint])) {
                    continue;
                }

                $seen[$fingerprint] = true;
                $rows[] = [
                    'match_key_type' => $matchKey['type'],
                    'match_key_value' => mb_substr($matchKey['value'], 0, 190),
                ];
            }
        }

        foreach ($rows as $row) {
            $this->aliasModel->insert([
                'source_name' => $this->config->sourceName,
                'source_entity' => 'clientes',
                'source_legacy_id' => $legacyId,
                'target_entity' => 'clientes',
                'target_id' => $targetId,
                'match_key_type' => $row['match_key_type'],
                'match_key_value' => $row['match_key_value'],
                'resolution_strategy' => mb_substr($strategy, 0, 40),
            ]);
        }
    }

    private function findEquipamentoTargetByLegacyReference(string $legacyId): ?array
    {
        $alias = $this->findLegacyAliasBySource('equipamentos', $legacyId, 'equipamentos');
        if ($alias !== null) {
            $target = $this->equipamentoModel->find((int) $alias['target_id']);
            if ($target !== null) {
                return [
                    'target' => $target,
                    'strategy' => $alias['resolution_strategy'] ?? 'alias_reference',
                ];
            }
        }

        $target = $this->equipamentoModel
            ->where('legacy_origem', $this->config->sourceName)
            ->where('legacy_id', $legacyId)
            ->first();

        if ($target === null) {
            return null;
        }

        return [
            'target' => $target,
            'strategy' => 'legacy_reference',
        ];
    }

    private function findEquipamentoTargetByStrongKeys(array $matchKeys): array
    {
        foreach ($matchKeys as $matchKey) {
            $alias = $this->findLegacyAliasByMatchKey('equipamentos', 'equipamentos', $matchKey['type'], $matchKey['value']);
            if ($alias !== null) {
                $target = $this->equipamentoModel->find((int) $alias['target_id']);
                if ($target !== null) {
                    return [
                        'safe' => true,
                        'target' => $target,
                        'strategy' => 'strong_' . $matchKey['type'],
                        'match_key_type' => $matchKey['type'],
                        'match_key_value' => $matchKey['value'],
                    ];
                }
            }

            $unsafeTarget = $this->findUnsafeEquipamentoConflict($matchKey);
            if ($unsafeTarget !== null) {
                return [
                    'safe' => false,
                    'target' => $unsafeTarget,
                    'strategy' => 'conflict_' . $matchKey['type'],
                    'match_key_type' => $matchKey['type'],
                    'match_key_value' => $matchKey['value'],
                ];
            }
        }

        return [
            'safe' => true,
            'target' => null,
        ];
    }

    private function resolveEquipamentoTargetForOs(string $legacyEquipamentoId): ?array
    {
        $resolved = $this->findEquipamentoTargetByLegacyReference($legacyEquipamentoId);
        return $resolved['target'] ?? null;
    }

    private function resolveOsTargetByLegacyId(string $legacyOsId): ?array
    {
        return $this->osModel
            ->where('legacy_origem', $this->config->sourceName)
            ->where('legacy_id', $legacyOsId)
            ->first();
    }

    private function normalizeItemType(mixed $value): string
    {
        $normalized = $this->normalizer->normalizeString($value);
        $normalized = $normalized !== null ? mb_strtolower($normalized, 'UTF-8') : '';

        return in_array($normalized, ['peca', 'produto', 'produtos'], true) ? 'peca' : 'servico';
    }

    private function normalizeQuantity(mixed $value): int
    {
        $normalized = $this->normalizer->normalizeDecimal($value);
        if ($normalized === null || $normalized <= 0) {
            return 1;
        }

        return max(1, (int) round($normalized));
    }

    private function composeLegacyHistoryObservation(array $row): ?string
    {
        $parts = [];

        $observacao = $this->normalizer->normalizeString($row['observacao'] ?? null);
        if ($observacao !== null) {
            $parts[] = $observacao;
        }

        $acaoFinanceira = $this->normalizer->normalizeString($row['acao_financeira'] ?? null);
        if ($acaoFinanceira !== null) {
            $parts[] = 'Acao financeira: ' . $acaoFinanceira;
        }

        $mensagemCliente = $this->normalizer->normalizeString($row['mensagem_cliente'] ?? null);
        if ($mensagemCliente !== null) {
            $parts[] = 'Mensagem ao cliente: ' . $mensagemCliente;
        }

        $usuarioLegado = $this->normalizer->normalizeLegacyId($row['legacy_usuario_id'] ?? null);
        if ($usuarioLegado !== null) {
            $parts[] = 'Usuario legado #' . $usuarioLegado;
        }

        return $parts !== [] ? implode("\n", $parts) : null;
    }

    private function upsertLegacyNote(
        int $osId,
        string $legacyTabela,
        string $legacyId,
        string $titulo,
        string $conteudo,
        string $createdAt,
        array $summary,
        ?int $runId
    ): array {
        if (! $this->targetDb->tableExists('os_notas_legadas')) {
            return $this->addIssue($summary, $runId, 'os_notas_legadas', 'warning', 'missing_target_table', $legacyId, 'Tabela de notas legadas ainda nao existe no destino.');
        }

        $payload = [
            'os_id' => $osId,
            'legacy_origem' => $this->config->sourceName,
            'legacy_tabela' => $legacyTabela,
            'legacy_id' => $legacyId,
            'titulo' => $titulo,
            'conteudo' => $conteudo,
            'created_at' => $createdAt,
        ];

        $existing = $this->osNotaLegadaModel
            ->where('legacy_origem', $this->config->sourceName)
            ->where('legacy_tabela', $legacyTabela)
            ->where('legacy_id', $legacyId)
            ->first();

        if ($existing !== null) {
            $this->osNotaLegadaModel->update((int) $existing['id'], $payload);
            $summary = $this->incrementSummary($summary, 'os_notas_legadas', 'updated');
            $this->registerEvent($runId, 'os_notas_legadas', 'info', 'updated', $legacyId, 'Nota legada atualizada com sucesso.', [
                'target_id' => (int) $existing['id'],
                'target_os_id' => $osId,
            ]);
            return $summary;
        }

        $inserted = $this->osNotaLegadaModel->insert($payload, true);
        if (! $inserted) {
            return $this->addIssue($summary, $runId, 'os_notas_legadas', 'error', 'insert_failed', $legacyId, 'Falha ao inserir nota legada da OS.', [
                'errors' => $this->osNotaLegadaModel->errors(),
                'target_os_id' => $osId,
            ]);
        }

        $summary = $this->incrementSummary($summary, 'os_notas_legadas', 'imported');
        $this->registerEvent($runId, 'os_notas_legadas', 'info', 'inserted', $legacyId, 'Nota legada importada com sucesso.', [
            'target_id' => (int) $inserted,
            'target_os_id' => $osId,
        ]);

        return $summary;
    }

    private function resolveTargetDefeitoId(array $targetOs, array $row): ?int
    {
        $equipamentoId = (int) ($targetOs['equipamento_id'] ?? 0);
        if ($equipamentoId <= 0) {
            return null;
        }

        $equipamento = $this->equipamentoModel->find($equipamentoId);
        $tipoId = (int) ($equipamento['tipo_id'] ?? 0);
        if ($tipoId <= 0) {
            return null;
        }

        $descricao = $this->normalizer->normalizeCatalogName($row['descricao'] ?? null)
            ?? ('Defeito legado #' . ($this->normalizer->normalizeLegacyId($row['legacy_id'] ?? null) ?? 'sem-id'));

        $found = $this->findDefeitoByTypeAndName($tipoId, $descricao);
        if ($found !== null) {
            return (int) $found['id'];
        }

        if (! $this->config->allowCatalogAutoCreate) {
            return null;
        }

        $inserted = $this->defeitoModel->insert([
            'nome' => $descricao,
            'tipo_id' => $tipoId,
            'classificacao' => 'hardware',
            'descricao' => $descricao,
            'ativo' => 1,
        ], true);

        return $inserted ? (int) $inserted : null;
    }

    private function findDefeitoByTypeAndName(int $tipoId, string $nome): ?array
    {
        $row = $this->targetDb
            ->query(
                'SELECT * FROM equipamentos_defeitos WHERE tipo_id = ? AND LOWER(nome) = LOWER(?) LIMIT 1',
                [$tipoId, $nome]
            )
            ->getRowArray();

        return is_array($row) ? $row : null;
    }

    private function cleanupSyntheticOsItemsForOs(int $osId): void
    {
        if (isset($this->syntheticItemCleanupDone[$osId])) {
            return;
        }

        $this->targetDb->table('os_itens')
            ->where('os_id', $osId)
            ->where('legacy_origem', $this->config->sourceName)
            ->whereIn('legacy_tabela', ['os_totais_servico', 'os_totais_peca', 'os_totais_consolidado'])
            ->delete();

        $this->syntheticItemCleanupDone[$osId] = true;
    }

    private function upsertSyntheticOsItem(
        array $summary,
        ?int $runId,
        int $osId,
        string $legacyOsId,
        string $legacyTabela,
        string $tipo,
        string $descricao,
        string $observacao,
        float $valor
    ): array {
        $existing = $this->osItemModel
            ->where('legacy_origem', $this->config->sourceName)
            ->where('legacy_tabela', $legacyTabela)
            ->where('legacy_id', $legacyOsId)
            ->first();

        if ($valor <= 0) {
            if ($existing !== null) {
                $this->osItemModel->delete((int) $existing['id']);
            }
            return $summary;
        }

        $payload = [
            'os_id' => $osId,
            'legacy_origem' => $this->config->sourceName,
            'legacy_tabela' => $legacyTabela,
            'legacy_id' => $legacyOsId,
            'tipo' => $tipo,
            'descricao' => $descricao,
            'observacao' => $observacao,
            'quantidade' => 1,
            'valor_unitario' => round($valor, 2),
            'valor_total' => round($valor, 2),
            'peca_id' => null,
        ];

        if ($existing !== null) {
            $this->osItemModel->update((int) $existing['id'], $payload);
            return $this->incrementSummary($summary, 'os_itens', 'updated');
        }

        $inserted = $this->osItemModel->insert($payload, true);
        if (! $inserted) {
            return $this->addIssue($summary, $runId, 'os_itens', 'warning', 'synthetic_insert_failed', $legacyOsId, 'Nao foi possivel criar item sintetico para preservar totais do legado.', [
                'legacy_tabela' => $legacyTabela,
                'target_os_id' => $osId,
            ]);
        }

        return $this->incrementSummary($summary, 'os_itens', 'imported');
    }

    private function buildEquipamentoMatchKeys(array $row): array
    {
        $keys = [];

        $numeroSerie = $this->normalizer->normalizeSerialLike($row['numero_serie'] ?? null);
        if ($numeroSerie !== null) {
            $keys[] = [
                'type' => 'numero_serie',
                'value' => $numeroSerie,
            ];
        }

        $imei = $this->normalizer->normalizeImei($row['imei'] ?? null);
        if ($imei !== null) {
            $keys[] = [
                'type' => 'imei',
                'value' => $imei,
            ];
        }

        return $keys;
    }

    private function mergeCanonicalEquipamentoPayload(array $existing, array $incoming, array $legacyMetadata): array
    {
        $fields = [
            'cliente_id',
            'tipo_id',
            'marca_id',
            'modelo_id',
            'cor',
            'cor_hex',
            'cor_rgb',
            'numero_serie',
            'imei',
            'senha_acesso',
            'estado_fisico',
            'acessorios',
            'observacoes',
        ];

        $payload = $incoming;
        $conflicts = [];

        foreach ($fields as $field) {
            $existingValue = $existing[$field] ?? null;
            $incomingValue = $incoming[$field] ?? null;

            if (! $this->hasMeaningfulValue($existingValue)) {
                continue;
            }

            if (! $this->hasMeaningfulValue($incomingValue)) {
                $payload[$field] = $existingValue;
                continue;
            }

            if ((string) $existingValue !== (string) $incomingValue) {
                $payload[$field] = $existingValue;
                $conflicts[$field] = [
                    'kept' => $existingValue,
                    'ignored' => $incomingValue,
                ];
            }
        }

        if (empty($existing['legacy_origem']) || empty($existing['legacy_id'])) {
            $payload = array_merge($payload, $legacyMetadata);
        }

        return [
            'payload' => $payload,
            'conflicts' => $conflicts,
        ];
    }

    private function hasMeaningfulValue(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        if (is_string($value)) {
            return trim($value) !== '';
        }

        if (is_int($value) || is_float($value)) {
            return $value !== 0;
        }

        return true;
    }

    private function replaceEquipamentoAliases(string $legacyId, int $targetId, array $matchKeys, string $strategy): void
    {
        if (! $this->targetDb->tableExists('legacy_import_aliases')) {
            return;
        }

        $this->aliasModel
            ->where('source_name', $this->config->sourceName)
            ->where('source_entity', 'equipamentos')
            ->where('source_legacy_id', $legacyId)
            ->where('target_entity', 'equipamentos')
            ->delete();

        $rows = [];
        if ($matchKeys === []) {
            $rows[] = [
                'match_key_type' => 'reference',
                'match_key_value' => '',
            ];
        } else {
            $seen = [];
            foreach ($matchKeys as $matchKey) {
                $fingerprint = $matchKey['type'] . '|' . $matchKey['value'];
                if (isset($seen[$fingerprint])) {
                    continue;
                }

                $seen[$fingerprint] = true;
                $rows[] = [
                    'match_key_type' => $matchKey['type'],
                    'match_key_value' => mb_substr($matchKey['value'], 0, 190),
                ];
            }
        }

        foreach ($rows as $row) {
            $this->aliasModel->insert([
                'source_name' => $this->config->sourceName,
                'source_entity' => 'equipamentos',
                'source_legacy_id' => $legacyId,
                'target_entity' => 'equipamentos',
                'target_id' => $targetId,
                'match_key_type' => $row['match_key_type'],
                'match_key_value' => $row['match_key_value'],
                'resolution_strategy' => mb_substr($strategy, 0, 40),
            ]);
        }
    }

    private function findLegacyAliasBySource(string $sourceEntity, string $sourceLegacyId, string $targetEntity): ?array
    {
        if (! $this->targetDb->tableExists('legacy_import_aliases')) {
            return null;
        }

        return $this->aliasModel
            ->where('source_name', $this->config->sourceName)
            ->where('source_entity', $sourceEntity)
            ->where('source_legacy_id', $sourceLegacyId)
            ->where('target_entity', $targetEntity)
            ->orderBy('id', 'ASC')
            ->first();
    }

    private function findLegacyAliasByMatchKey(string $sourceEntity, string $targetEntity, string $matchKeyType, string $matchKeyValue): ?array
    {
        if (! $this->targetDb->tableExists('legacy_import_aliases')) {
            return null;
        }

        return $this->aliasModel
            ->where('source_name', $this->config->sourceName)
            ->where('source_entity', $sourceEntity)
            ->where('target_entity', $targetEntity)
            ->where('match_key_type', $matchKeyType)
            ->where('match_key_value', $matchKeyValue)
            ->orderBy('id', 'ASC')
            ->first();
    }

    private function findUnsafeEquipamentoConflict(array $matchKey): ?array
    {
        $column = $matchKey['type'] === 'imei' ? 'imei' : 'numero_serie';
        $rawValue = $matchKey['type'] === 'imei'
            ? $matchKey['value']
            : $this->normalizer->normalizeString($matchKey['value']);

        if ($rawValue === null) {
            return null;
        }

        $conflict = $this->equipamentoModel
            ->where($column, $rawValue)
            ->orderBy('id', 'ASC')
            ->first();

        if ($conflict === null) {
            return null;
        }

        if (($conflict['legacy_origem'] ?? null) === $this->config->sourceName) {
            return null;
        }

        return $conflict;
    }

    private function findUnsafeClienteConflict(array $matchKey): ?array
    {
        if ($matchKey['type'] !== 'cpf_cnpj') {
            return null;
        }

        $rawValue = $this->normalizer->normalizeDocument($matchKey['value']);
        if ($rawValue === null) {
            return null;
        }

        $conflict = $this->clienteModel
            ->where('cpf_cnpj', $rawValue)
            ->orderBy('id', 'ASC')
            ->first();

        if ($conflict === null) {
            return null;
        }

        if (($conflict['legacy_origem'] ?? null) === $this->config->sourceName) {
            return null;
        }

        return $conflict;
    }

    private function assertSourceConnection(): void
    {
        $this->sourceDb->connect();

        $database = (string) ($this->sourceDb->getDatabase() ?? '');
        if ($database === '') {
            throw new \RuntimeException('Conexao legada sem database configurado.');
        }
    }

    private function validateQueryAliases(): void
    {
        foreach ($this->config->requiredAliases as $entity => $aliases) {
            $fieldNames = $this->getSourceFieldNames($entity);
            foreach ($aliases as $alias) {
                if (! in_array($alias, $fieldNames, true)) {
                    throw new \RuntimeException("Consulta base de {$entity} nao expoe o alias obrigatorio '{$alias}'.");
                }
            }
        }
    }

    /**
     * @return string[]
     */
    private function getSourceFieldNames(string $entity): array
    {
        $query = $this->wrapSourceQuery($entity, 1, 0);
        return $this->sourceDb->query($query)->getFieldNames();
    }

    private function countSourceRows(string $entity): int
    {
        $baseQuery = $this->config->queries[$entity] ?? null;
        if ($baseQuery === null) {
            throw new \RuntimeException('Consulta base nao configurada para ' . $entity . '.');
        }

        $sql = 'SELECT COUNT(*) AS total FROM (' . $baseQuery . ') legacy_source';
        $row = $this->sourceDb->query($sql)->getRowArray();
        return (int) ($row['total'] ?? 0);
    }

    /**
     * @return iterable<array<string, mixed>>
     */
    private function iterateSourceRows(string $entity): iterable
    {
        $offset = 0;
        $batchSize = $this->config->batchSize;

        while (true) {
            $sql = $this->wrapSourceQuery($entity, $batchSize, $offset);
            $rows = $this->sourceDb->query($sql)->getResultArray();
            if ($rows === []) {
                break;
            }

            foreach ($rows as $row) {
                yield $row;
            }

            if (count($rows) < $batchSize) {
                break;
            }

            $offset += $batchSize;
        }
    }

    private function wrapSourceQuery(string $entity, int $limit, int $offset): string
    {
        $baseQuery = trim((string) ($this->config->queries[$entity] ?? ''));
        if ($baseQuery === '') {
            throw new \RuntimeException('Consulta base nao configurada para ' . $entity . '.');
        }

        return 'SELECT * FROM (' . $baseQuery . ') legacy_source ORDER BY legacy_id ASC LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;
    }

    private function inspectCleanupPath(string $path): array
    {
        $normalizedPath = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
        $summary = [
            'path' => $normalizedPath,
            'exists' => is_dir($normalizedPath),
            'files' => 0,
            'directories' => 0,
            'action' => 'preview',
        ];

        if (! $summary['exists']) {
            return $summary;
        }

        $items = scandir($normalizedPath) ?: [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..' || $item === 'index.html') {
                continue;
            }

            $fullPath = $normalizedPath . DIRECTORY_SEPARATOR . $item;
            if (is_dir($fullPath)) {
                $summary['directories']++;
                $summary['files'] += $this->countFilesRecursively($fullPath);
                continue;
            }

            if (is_file($fullPath)) {
                $summary['files']++;
            }
        }

        return $summary;
    }

    private function countFilesRecursively(string $directory): int
    {
        $count = 0;
        $items = scandir($directory) ?: [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $fullPath = $directory . DIRECTORY_SEPARATOR . $item;
            if (is_dir($fullPath)) {
                $count += $this->countFilesRecursively($fullPath);
            } elseif (is_file($fullPath)) {
                $count++;
            }
        }

        return $count;
    }

    private function cleanupPathContents(string $path): array
    {
        $normalizedPath = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
        $removed = [
            'files_removed' => 0,
            'directories_removed' => 0,
        ];

        if (! is_dir($normalizedPath)) {
            return $removed;
        }

        $items = scandir($normalizedPath) ?: [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..' || $item === 'index.html') {
                continue;
            }

            $fullPath = $normalizedPath . DIRECTORY_SEPARATOR . $item;
            if (is_dir($fullPath)) {
                $removed['files_removed'] += $this->deleteDirectoryRecursively($fullPath);
                $removed['directories_removed']++;
                continue;
            }

            if (is_file($fullPath) && @unlink($fullPath)) {
                $removed['files_removed']++;
            }
        }

        return $removed;
    }

    private function deleteDirectoryRecursively(string $directory): int
    {
        $removedFiles = 0;
        $items = scandir($directory) ?: [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $fullPath = $directory . DIRECTORY_SEPARATOR . $item;
            if (is_dir($fullPath)) {
                $removedFiles += $this->deleteDirectoryRecursively($fullPath);
                continue;
            }

            if (is_file($fullPath) && @unlink($fullPath)) {
                $removedFiles++;
            }
        }

        @rmdir($directory);

        return $removedFiles;
    }

    private function startRun(string $mode): int
    {
        $runId = $this->runModel->insert([
            'source_name' => $this->config->sourceName,
            'mode'        => $mode,
            'status'      => 'running',
            'started_at'  => date('Y-m-d H:i:s'),
            'notes'       => 'Execucao iniciada em ' . date('Y-m-d H:i:s'),
        ], true);

        return (int) $runId;
    }

    private function finishRun(int $runId, array $summary): void
    {
        $this->runModel->update($runId, [
            'status'       => $summary['status'] ?? 'failed',
            'finished_at'  => date('Y-m-d H:i:s'),
            'summary_json' => json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }

    private function registerEvent(?int $runId, string $entity, string $severity, string $action, ?string $legacyId, string $message, array $details = []): void
    {
        if ($runId === null || ! $this->targetDb->tableExists('legacy_import_events')) {
            return;
        }

        $this->eventModel->insert([
            'run_id'       => $runId,
            'entity'       => $entity,
            'severity'     => $severity,
            'action'       => $action,
            'legacy_id'    => $legacyId,
            'message'      => $message,
            'details_json' => $details !== [] ? json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            'created_at'   => date('Y-m-d H:i:s'),
        ]);
    }

    private function newSummary(string $mode): array
    {
        return [
            'mode' => $mode,
            'status' => 'running',
            'blocking_errors' => 0,
            'warnings' => 0,
            'entities' => [
                'clientes' => $this->newEntitySummary(),
                'equipamentos' => $this->newEntitySummary(),
                'os' => $this->newEntitySummary(),
                'os_itens' => $this->newEntitySummary(),
                'os_status_historico' => $this->newEntitySummary(),
                'os_defeitos' => $this->newEntitySummary(),
                'os_notas_legadas' => $this->newEntitySummary(),
            ],
        ];
    }

    private function newEntitySummary(): array
    {
        return [
            'source_total' => 0,
            'imported' => 0,
            'updated' => 0,
            'ignored' => 0,
            'errors' => 0,
            'warnings' => 0,
        ];
    }

    private function addIssue(
        array $summary,
        ?int $runId,
        string $entity,
        string $severity,
        string $action,
        ?string $legacyId,
        string $message,
        array $details = [],
        ?string $counter = null
    ): array {
        if ($severity === 'error') {
            $summary['blocking_errors']++;
            $summary['entities'][$entity]['errors']++;
        }

        if ($severity === 'warning') {
            $summary['warnings']++;
            $summary['entities'][$entity]['warnings']++;
        }

        if ($counter !== null) {
            $summary['entities'][$entity][$counter]++;
        }

        $this->registerEvent($runId, $entity, $severity, $action, $legacyId, $message, $details);
        return $summary;
    }

    private function incrementSummary(array $summary, string $entity, string $field): array
    {
        $summary['entities'][$entity][$field]++;
        return $summary;
    }

    private function normalizeStatusCode(mixed $status): ?string
    {
        $normalized = $this->normalizer->normalizeString($status);
        if ($normalized === null) {
            return null;
        }

        $normalized = mb_strtolower($normalized, 'UTF-8');
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $normalized);
        $normalized = $ascii !== false ? $ascii : $normalized;
        $normalized = str_replace([' ', '-', '/', '\\'], '_', $normalized);
        $normalized = preg_replace('/_+/', '_', $normalized);

        return trim((string) $normalized, '_');
    }
}
