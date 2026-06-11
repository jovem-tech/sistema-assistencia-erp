<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class BackfillEquipamentoLifecycleHistoricoFromLogs extends Migration
{
    private string $historyTable = 'equipamentos_lifecycle_historico';
    private string $logsTable = 'logs';

    /**
     * Mapa de log tecnico para evento da timeline.
     */
    private array $acaoEventoMap = [
        'equipamento_encerrado' => 'encerrado',
        'equipamento_reativado' => 'reativado',
        'equipamento_encerrado_automaticamente' => 'encerrado_automaticamente',
    ];

    private array $encerramentoMotivosMap = [
        'retirada de pecas' => 'retirada_pecas',
        'problema irreparavel' => 'irreparavel',
        'descartado' => 'descartado',
        'pecas vendidas para outros clientes' => 'pecas_vendidas',
        'outro motivo' => 'outro',
    ];

    private array $reativacaoMotivosMap = [
        'recuperado' => 'recuperado',
        'recondicionado' => 'recondicionado',
        'reparado' => 'reparado',
        'voltou a funcionar' => 'voltou_operacao',
        'outro motivo' => 'outro',
    ];

    public function up(): void
    {
        if (! $this->db->tableExists($this->logsTable) || ! $this->db->tableExists($this->historyTable)) {
            return;
        }

        if (! $this->db->fieldExists('evento', $this->historyTable)) {
            return;
        }

        $existingFingerprints = $this->loadExistingFingerprints();
        $currentStatusByEquipamento = [];
        $rowsToInsert = [];

        $logs = $this->db->table($this->logsTable)
            ->select('id, acao, descricao, usuario_id, created_at')
            ->whereIn('acao', array_keys($this->acaoEventoMap))
            ->orderBy('created_at', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        foreach ($logs as $logRow) {
            $historyRow = $this->buildHistoryRowFromLog($logRow, $currentStatusByEquipamento);
            if ($historyRow === null) {
                continue;
            }

            $fingerprint = $this->buildFingerprint($historyRow);
            if (isset($existingFingerprints[$fingerprint])) {
                continue;
            }

            $existingFingerprints[$fingerprint] = true;
            $rowsToInsert[] = $historyRow;
        }

        if ($rowsToInsert === []) {
            return;
        }

        $this->db->table($this->historyTable)->insertBatch($rowsToInsert);
    }

    public function down(): void
    {
        // Nao removemos dados historicos em rollback.
    }

    /**
     * @return array<string, true>
     */
    private function loadExistingFingerprints(): array
    {
        $fingerprints = [];

        $rows = $this->db->table($this->historyTable)
            ->select('equipamento_id, os_id, evento, motivo, observacao, status_anterior, status_novo, usuario_id, created_at')
            ->get()
            ->getResultArray();

        foreach ($rows as $row) {
            $fingerprints[$this->buildFingerprint($row)] = true;
        }

        return $fingerprints;
    }

    private function buildHistoryRowFromLog(array $logRow, array &$currentStatusByEquipamento): ?array
    {
        $acao = trim((string) ($logRow['acao'] ?? ''));
        $evento = $this->acaoEventoMap[$acao] ?? '';
        if ($evento === '') {
            return null;
        }

        $descricao = trim((string) ($logRow['descricao'] ?? ''));
        $usuarioId = (int) ($logRow['usuario_id'] ?? 0);
        $usuarioId = $usuarioId > 0 ? $usuarioId : null;
        $createdAt = trim((string) ($logRow['created_at'] ?? ''));
        $createdAt = $createdAt !== '' ? $createdAt : date('Y-m-d H:i:s');

        $equipamentoId = $this->extractEquipamentoId($descricao);
        if ($equipamentoId <= 0) {
            return null;
        }

        if (! $this->equipamentoExiste($equipamentoId)) {
            return null;
        }

        $statusAnterior = $currentStatusByEquipamento[$equipamentoId] ?? null;
        if ($statusAnterior === null || $statusAnterior === '') {
            $statusAnterior = $evento === 'reativado' ? 'encerrado' : 'ativo';
        }

        $statusNovo = $evento === 'reativado' ? 'ativo' : 'encerrado';
        $currentStatusByEquipamento[$equipamentoId] = $statusNovo;

        $osId = null;
        $motivo = null;
        $observacao = null;

        if ($evento === 'encerrado_automaticamente') {
            [$equipamentoIdAutomatico, $numeroOs] = $this->parseAutomaticClosureMessage($descricao);
            if ($equipamentoIdAutomatico > 0) {
                $equipamentoId = $equipamentoIdAutomatico;
            }

            $motivo = 'descartado';
            $osId = $this->findOsIdByNumero($numeroOs);
        } else {
            $campos = $this->parseCamposEstruturados($descricao);

            if ($evento === 'encerrado') {
                $motivo = $this->mapEncerramentoMotivo((string) ($campos['motivo'] ?? ''));
                $observacao = $this->normalizeNullableValue($campos['observacao'] ?? null);
            } elseif ($evento === 'reativado') {
                $motivo = $this->mapReativacaoMotivo((string) ($campos['motivo da reativacao'] ?? ''));
                $observacao = $this->normalizeNullableValue($campos['observacao'] ?? null);
            }
        }

        return [
            'equipamento_id' => $equipamentoId,
            'os_id' => $osId,
            'evento' => $evento,
            'motivo' => $motivo,
            'observacao' => $observacao,
            'status_anterior' => $statusAnterior,
            'status_novo' => $statusNovo,
            'usuario_id' => $usuarioId,
            'created_at' => $createdAt,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function parseCamposEstruturados(string $descricao): array
    {
        $descricao = trim($descricao);
        if ($descricao === '') {
            return [];
        }

        $partes = preg_split('/\s*\|\s*/u', $descricao) ?: [];
        $campos = [];

        foreach (array_slice($partes, 1) as $parte) {
            if (! is_string($parte) || $parte === '') {
                continue;
            }

            if (preg_match('/^([^:]+):\s*(.*)$/u', $parte, $matches) !== 1) {
                continue;
            }

            $chave = strtolower(trim($matches[1]));
            $valor = trim($matches[2]);
            if ($chave !== '') {
                $campos[$chave] = $valor;
            }
        }

        return $campos;
    }

    /**
     * @return array{0:int,1:string}
     */
    private function parseAutomaticClosureMessage(string $descricao): array
    {
        if (preg_match('/Equipamento ID\s+(\d+)\s+encerrado automaticamente porque a OS\s+(\d+)\s+foi marcada como descartada\./i', $descricao, $matches) !== 1) {
            return [0, ''];
        }

        return [(int) $matches[1], trim((string) $matches[2])];
    }

    private function extractEquipamentoId(string $descricao): int
    {
        if (preg_match('/ID\s+(\d+)/i', $descricao, $matches) !== 1) {
            return 0;
        }

        return (int) $matches[1];
    }

    private function equipamentoExiste(int $equipamentoId): bool
    {
        if ($equipamentoId <= 0 || ! $this->db->tableExists('equipamentos')) {
            return false;
        }

        $row = $this->db->table('equipamentos')
            ->select('id')
            ->where('id', $equipamentoId)
            ->limit(1)
            ->get()
            ->getRowArray();

        return is_array($row) && (int) ($row['id'] ?? 0) > 0;
    }

    private function mapEncerramentoMotivo(string $label): ?string
    {
        $label = $this->normalizeMotivoLabel($label);
        if ($label === '') {
            return null;
        }

        return $this->encerramentoMotivosMap[$label] ?? $this->slugifyLabel($label);
    }

    private function mapReativacaoMotivo(string $label): ?string
    {
        $label = $this->normalizeMotivoLabel($label);
        if ($label === '') {
            return null;
        }

        return $this->reativacaoMotivosMap[$label] ?? $this->slugifyLabel($label);
    }

    private function normalizeMotivoLabel(string $label): string
    {
        $label = trim($label);
        $label = preg_replace('/\s+/u', ' ', $label) ?: $label;

        return strtolower($label);
    }

    private function slugifyLabel(string $label): string
    {
        $label = trim($label);
        if ($label === '') {
            return '';
        }

        $label = strtolower($label);
        $label = preg_replace('/[^\pL\pN]+/u', '_', $label) ?: $label;
        $label = trim($label, '_');

        return $label !== '' ? $label : 'outro';
    }

    private function normalizeNullableValue(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value !== '' ? $value : null;
    }

    private function findOsIdByNumero(string $numeroOs): ?int
    {
        $numeroOs = trim($numeroOs);
        if ($numeroOs === '' || ! $this->db->tableExists('os') || ! $this->db->fieldExists('numero_os', 'os')) {
            return null;
        }

        $row = $this->db->table('os')
            ->select('id')
            ->where('numero_os', $numeroOs)
            ->limit(1)
            ->get()
            ->getRowArray();

        if (! is_array($row) || (int) ($row['id'] ?? 0) <= 0) {
            return null;
        }

        return (int) $row['id'];
    }

    private function buildFingerprint(array $row): string
    {
        $payload = [
            'equipamento_id' => (int) ($row['equipamento_id'] ?? 0),
            'os_id' => (int) ($row['os_id'] ?? 0),
            'evento' => trim((string) ($row['evento'] ?? '')),
            'motivo' => trim((string) ($row['motivo'] ?? '')),
            'observacao' => trim((string) ($row['observacao'] ?? '')),
            'status_anterior' => trim((string) ($row['status_anterior'] ?? '')),
            'status_novo' => trim((string) ($row['status_novo'] ?? '')),
            'usuario_id' => (int) ($row['usuario_id'] ?? 0),
            'created_at' => trim((string) ($row['created_at'] ?? '')),
        ];

        return hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '');
    }
}
