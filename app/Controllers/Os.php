<?php

namespace App\Controllers;

use App\Models\OsModel;
use App\Models\OsItemModel;
use App\Models\ClienteModel;
use App\Models\EquipamentoModel;
use App\Models\UsuarioModel;
use App\Models\FuncionarioModel;
use App\Models\PecaModel;
use App\Models\MovimentacaoModel;
use App\Models\FinanceiroModel;
use App\Models\DefeitoModel;
use App\Models\LogModel;
use App\Models\OsFotoModel;
use App\Models\EquipamentoFotoModel;
use App\Models\AcessorioOsModel;
use App\Models\FotoAcessorioModel;
use App\Models\EstadoFisicoOsModel;
use App\Models\FotoEstadoFisicoModel;
use App\Models\ContatoModel;
use App\Models\ConversaWhatsappModel;
use App\Models\DefeitoRelatadoModel;
use App\Models\OsStatusModel;
use App\Models\OsStatusHistoricoModel;
use App\Models\OsNotaLegadaModel;
use App\Models\MensagemWhatsappModel;
use App\Models\WhatsappMensagemModel;
use App\Models\WhatsappEnvioModel;
use App\Models\OsDocumentoModel;
use App\Services\OsStatusFlowService;
use App\Services\WhatsAppService;
use App\Services\OsPdfService;
use App\Services\CrmService;
use App\Services\CentralMensagensService;
use App\Services\ChecklistService;
use Config\Database;

class Os extends BaseController
{
    protected $model;
    protected array $osListSearchMatcherCache = [];
    protected ?bool $osRelatoFulltextAvailable = null;

    public function __construct()
    {
        $this->model = new OsModel();
        requirePermission('os');
    }

    public function index()
    {
        $filters = $this->collectListFilters('get');

        $statusFlowService = new OsStatusFlowService();
        $statusGrouped = $statusFlowService->getStatusGrouped();
        $statusFlat = [];
        foreach ($statusGrouped as $macro => $items) {
            foreach ($items as $item) {
                $statusFlat[] = [
                    'codigo' => (string) ($item['codigo'] ?? ''),
                    'nome' => (string) ($item['nome'] ?? $item['codigo'] ?? ''),
                    'macro' => (string) ($macro ?? ($item['grupo_macro'] ?? 'outros')),
                ];
            }
        }

        $macrofases = [];
        foreach (array_keys($statusGrouped) as $macro) {
            $macrofases[$macro] = ucwords(str_replace('_', ' ', (string) $macro));
        }

        $tecnicos = (new FuncionarioModel())
            ->select('id, nome, cargo')
            ->where('ativo', 1)
            ->orderBy('nome', 'ASC')
            ->findAll();

        $tiposServico = [];
        $db = \Config\Database::connect();
        if ($db->tableExists('os_itens')) {
            $tiposServico = $db->table('os_itens')
                ->select('descricao')
                ->where('tipo', 'servico')
                ->where('descricao IS NOT NULL', null, false)
                ->where("TRIM(descricao) <> ''", null, false)
                ->groupBy('descricao')
                ->orderBy('descricao', 'ASC')
                ->limit(300)
                ->get()
                ->getResultArray();
        }

        $situacaoOptions = [
            'em_triagem' => 'Em triagem',
            'em_atendimento' => 'Em atendimento',
            'finalizado' => 'Finalizado',
            'equipamento_entregue' => 'Equipamento entregue',
        ];

        $data = [
            'title'  => 'Ordens de Servico',
            'filtro_status' => $filters['status'][0] ?? '',
            'filtro_status_list' => $filters['status'],
            'filtro_macrofase' => $filters['macrofase'],
            'filtro_estado_fluxo' => $filters['estado_fluxo'],
            'statusGrouped' => $statusGrouped,
            'statusFlat' => $statusFlat,
            'macrofases' => $macrofases,
            'tecnicos' => $tecnicos,
            'tiposServico' => $tiposServico,
            'situacaoOptions' => $situacaoOptions,
            'listFilters' => $filters,
        ];
        return view('os/index', $data);
    }

    public function datatable()
    {
        $draw = max(0, (int) $this->request->getPostGet('draw'));
        $start = max(0, (int) $this->request->getPostGet('start'));
        $length = (int) $this->request->getPostGet('length');
        $length = $length < 1 ? 10 : min($length, 100);

        $filters = $this->collectListFilters('post');
        $db = \Config\Database::connect();
        $hasStatusTable = $db->tableExists('os_status');

        $totalRecords = (int) $this->model->countAll();
        $filteredRecords = $totalRecords;

        if ($this->hasActiveListFilters($filters)) {
            $filteredBuilder = $this->buildOsListCountBuilder($filters, $hasStatusTable, $db);
            $filteredRecords = (int) $filteredBuilder->countAllResults();
        }

        $order = $this->request->getPostGet('order') ?? [];
        $pageIds = $this->buildOsListPageIds($filters, $hasStatusTable, $db, $order, $start, $length);

        $rows = [];
        if (!empty($pageIds)) {
            $rows = $this->buildOsListRowsBuilder($pageIds)
                ->get()
                ->getResultArray();
        }

        $photoContext = $this->buildOsListPhotoContext($rows);
        $data = array_map(fn (array $row) => $this->formatOsDatatableRow($row, $photoContext), $rows);

        return $this->response->setJSON([
            'draw' => $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
    }

    public function photos($id)
    {
        $id = (int) $id;
        $os = $this->model
            ->select('os.id, os.numero_os, os.equipamento_id')
            ->where('os.id', $id)
            ->first();

        if (!$os) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'ok' => false,
                    'message' => 'OS nao encontrada.',
                ]);
        }

        $profilePhotosSource = $this->getEquipamentoFotosWithUrls((int) ($os['equipamento_id'] ?? 0));
        $entryPhotosSource = $this->getOsEntradaFotosWithUrls($id);

        $profilePhotos = array_map(
            fn (array $photo, int $index): array => $this->mapOsViewerPhotoPayload(
                $photo,
                'equipamento',
                $index + 1,
                false
            ),
            $profilePhotosSource,
            array_keys($profilePhotosSource)
        );

        $entryPhotos = array_map(
            fn (array $photo, int $index): array => $this->mapOsViewerPhotoPayload(
                $photo,
                'abertura',
                $index + 1,
                false
            ),
            $entryPhotosSource,
            array_keys($entryPhotosSource)
        );

        return $this->response->setJSON([
            'ok' => true,
            'os' => [
                'id' => $id,
                'numero_os' => (string) ($os['numero_os'] ?? ''),
            ],
            'profilePhotos' => $profilePhotos,
            'entryPhotos' => $entryPhotos,
        ]);
    }

    private function collectListFilters(string $method = 'get'): array
    {
        $read = static function (string $key) use ($method) {
            return $method === 'post'
                ? service('request')->getPost($key)
                : service('request')->getGet($key);
        };

        $statusRaw = $read('status');
        if ($statusRaw === null || $statusRaw === '') {
            $statusRaw = $read('status_list');
        }

        return [
            'q' => trim((string) ($read('q') ?? '')),
            'status' => $this->normalizeStringList($statusRaw),
            'legado' => $this->normalizeToggleValue($read('legado')),
            'macrofase' => trim((string) ($read('macrofase') ?? '')),
            'estado_fluxo' => trim((string) ($read('estado_fluxo') ?? '')),
            'data_inicio' => $this->normalizeDateValue($read('data_inicio')),
            'data_fim' => $this->normalizeDateValue($read('data_fim')),
            'tecnico_id' => $this->normalizeIntValue($read('tecnico_id')),
            'tipo_servico' => trim((string) ($read('tipo_servico') ?? '')),
            'valor_min' => $this->normalizeDecimalValue($read('valor_min')),
            'valor_max' => $this->normalizeDecimalValue($read('valor_max')),
            'situacao' => trim((string) ($read('situacao') ?? '')),
        ];
    }

    private function applyListFilters($builder, array $filters, bool $hasStatusTable, $db): void
    {
        if (!empty($filters['q'])) {
            $this->applyOsGlobalSearchFilter($builder, (string) $filters['q'], $db);
        }

        if (!empty($filters['status'])) {
            $builder->whereIn('os.status', $filters['status']);
        }

        if (!empty($filters['legado'])) {
            $builder->groupStart()
                ->where('os.legacy_origem IS NOT NULL', null, false)
                ->orWhere("TRIM(COALESCE(os.numero_os_legado, '')) <> ''", null, false)
            ->groupEnd();
        }

        if (!empty($filters['macrofase']) && $hasStatusTable) {
            $builder->where('os_status.grupo_macro', $filters['macrofase']);
        }

        if (!empty($filters['estado_fluxo'])) {
            $builder->where('os.estado_fluxo', $filters['estado_fluxo']);
        }

        if (!empty($filters['data_inicio'])) {
            $builder->where('os.data_abertura >=', $this->buildDayStart($filters['data_inicio']));
        }

        if (!empty($filters['data_fim'])) {
            $builder->where('os.data_abertura <', $this->buildNextDayStart($filters['data_fim']));
        }

        if (!empty($filters['tecnico_id'])) {
            $builder->where('os.tecnico_id', (int) $filters['tecnico_id']);
        }

        if ($filters['valor_min'] !== null) {
            $minValue = (float) $filters['valor_min'];
            if ($minValue <= 0) {
                $builder->groupStart()
                    ->where('os.valor_final >=', $minValue)
                    ->orWhere('os.valor_final IS NULL', null, false)
                ->groupEnd();
            } else {
                $builder->where('os.valor_final >=', $minValue);
            }
        }

        if ($filters['valor_max'] !== null) {
            $builder->groupStart()
                ->where('os.valor_final <=', (float) $filters['valor_max'])
                ->orWhere('os.valor_final IS NULL', null, false)
            ->groupEnd();
        }

        if (!empty($filters['tipo_servico']) && $db->tableExists('os_itens')) {
            $escapedTipo = $db->escape($filters['tipo_servico']);
            $builder->where(
                "os.id IN (
                    SELECT oi.os_id
                    FROM os_itens oi
                    WHERE oi.tipo = 'servico'
                      AND oi.descricao = {$escapedTipo}
                )",
                null,
                false
            );
        }

        if (!empty($filters['situacao'])) {
            $this->applySituacaoFilter($builder, (string) $filters['situacao']);
        }
    }

    private function applySituacaoFilter($builder, string $situacao): void
    {
        $situacao = trim($situacao);
        if ($situacao === '') {
            return;
        }

        $deliveredStatuses = ['entregue_reparado', 'entregue'];
        $finalStatuses = [
            'irreparavel',
            'irreparavel_disponivel_loja',
            'reparo_recusado',
            'devolvido_sem_reparo',
            'descartado',
            'cancelado',
        ];

        if ($situacao === 'em_triagem') {
            $builder->groupStart()
                ->whereIn('os.status', ['triagem', 'aguardando_analise'])
            ->groupEnd();
            return;
        }

        if ($situacao === 'equipamento_entregue') {
            $builder->whereIn('os.status', $deliveredStatuses);
            return;
        }

        if ($situacao === 'finalizado') {
            $builder->groupStart()
                ->groupStart()
                    ->whereIn('os.estado_fluxo', ['encerrado', 'cancelado'])
                    ->orWhereIn('os.status', $finalStatuses)
                ->groupEnd()
                ->whereNotIn('os.status', $deliveredStatuses)
            ->groupEnd();
            return;
        }

        if ($situacao === 'em_atendimento') {
            $builder->groupStart()
                ->whereIn('os.estado_fluxo', ['em_atendimento', 'em_execucao', 'pausado', 'pronto'])
                ->whereNotIn('os.status', array_merge($deliveredStatuses, $finalStatuses))
            ->groupEnd();
        }
    }

    private function buildOsListCountBuilder(array $filters, bool $hasStatusTable, $db)
    {
        $builder = $this->model->builder();
        $builder->select('os.id');

        if ($hasStatusTable && $this->requiresStatusLookupJoin($filters)) {
            $builder->join('os_status', 'os_status.codigo = os.status', 'left');
        }

        $this->applyListFilters($builder, $filters, $hasStatusTable, $db);

        return $builder;
    }

    private function buildOsListPageIds(array $filters, bool $hasStatusTable, $db, array $order, int $start, int $length): array
    {
        $builder = $this->model->builder();
        $builder->select('os.id');

        if ($hasStatusTable && $this->requiresStatusLookupJoin($filters)) {
            $builder->join('os_status', 'os_status.codigo = os.status', 'left');
        }

        $this->joinOsListOrderingDependencies($builder, $order);
        $this->applyListFilters($builder, $filters, $hasStatusTable, $db);
        $this->applyOsListOrdering($builder, $order);

        $rows = $builder
            ->limit($length, $start)
            ->get()
            ->getResultArray();

        return array_map(
            static fn (array $row): int => (int) ($row['id'] ?? 0),
            array_filter($rows, static fn (array $row): bool => isset($row['id']))
        );
    }

    private function buildOsListRowsBuilder(array $ids)
    {
        $normalizedIds = array_values(array_unique(array_filter(array_map('intval', $ids))));
        $builder = $this->model->builder();
        $builder->select(
            'os.id,
            os.numero_os,
            os.numero_os_legado,
            os.legacy_origem,
            os.cliente_id,
            os.equipamento_id,
            os.status,
            os.estado_fluxo,
            os.data_abertura,
            os.data_entrada,
            os.data_previsao,
            os.data_entrega,
            os.relato_cliente,
            os.valor_final,
            clientes.nome_razao as cliente_nome,
            et.nome as equip_tipo,
            em.nome as equip_marca,
            emod.nome as equip_modelo'
        );

        $builder->join('clientes', 'clientes.id = os.cliente_id', 'left');
        $builder->join('equipamentos', 'equipamentos.id = os.equipamento_id', 'left');
        $builder->join('equipamentos_tipos et', 'et.id = equipamentos.tipo_id', 'left');
        $builder->join('equipamentos_marcas em', 'em.id = equipamentos.marca_id', 'left');
        $builder->join('equipamentos_modelos emod', 'emod.id = equipamentos.modelo_id', 'left');
        $builder->whereIn('os.id', $normalizedIds);

        $idList = implode(',', $normalizedIds);
        $builder->orderBy("FIELD(os.id, {$idList})", '', false);

        return $builder;
    }

    private function joinOsListOrderingDependencies($builder, array $order): void
    {
        $columnIndex = isset($order[0]['column']) ? (int) $order[0]['column'] : 6;

        if ($columnIndex === 3) {
            $builder->join('clientes', 'clientes.id = os.cliente_id', 'left');
            return;
        }

        if ($columnIndex === 4) {
            $builder->join('equipamentos', 'equipamentos.id = os.equipamento_id', 'left');
            $builder->join('equipamentos_tipos et', 'et.id = equipamentos.tipo_id', 'left');
            $builder->join('equipamentos_marcas em', 'em.id = equipamentos.marca_id', 'left');
            $builder->join('equipamentos_modelos emod', 'emod.id = equipamentos.modelo_id', 'left');
        }
    }

    private function applyOsListOrdering($builder, array $order): void
    {
        $columnIndex = isset($order[0]['column']) ? (int) $order[0]['column'] : 6;
        $direction = (($order[0]['dir'] ?? 'desc') === 'asc') ? 'asc' : 'desc';

        switch ($columnIndex) {
            case 2:
                $builder->orderBy('os.numero_os', $direction);
                break;

            case 3:
                $builder->orderBy('clientes.nome_razao', $direction);
                break;

            case 4:
                $builder->orderBy('et.nome', $direction)
                    ->orderBy('em.nome', $direction)
                    ->orderBy('emod.nome', $direction);
                break;

            case 5:
                $builder->orderBy('os.relato_cliente', $direction);
                break;

            case 7:
                $builder->orderBy('os.status', $direction)
                    ->orderBy('os.estado_fluxo', $direction);
                break;

            case 8:
                $builder->orderBy('os.valor_final', $direction);
                break;

            case 6:
            default:
                $builder->orderBy('COALESCE(os.data_entrada, os.data_abertura)', $direction, false)
                    ->orderBy('os.id', $direction);
                break;
        }
    }

    private function applyOsGlobalSearchFilter($builder, string $query, $db): void
    {
        $query = trim($query);
        if ($query === '') {
            return;
        }

        if ($this->isLikelyOsNumberSearch($query)) {
            $normalizedOsNumber = $this->normalizeOsNumberSearchTerm($query);
            $digitsOnly = preg_replace('/\D+/', '', $query);

            $builder->groupStart()
                ->like('os.numero_os', $normalizedOsNumber, 'after')
                ->orLike('os.numero_os_legado', $normalizedOsNumber, 'after');

            if ($digitsOnly !== '' && $digitsOnly !== $normalizedOsNumber) {
                $builder->orGroupStart()
                    ->like('os.numero_os', $digitsOnly, 'after')
                    ->orLike('os.numero_os_legado', $digitsOnly, 'after')
                ->groupEnd();
            }

            $builder->groupEnd();
            return;
        }

        $searchPlan = $this->resolveOsGlobalSearchPlan($query, $db);
        $hasStructuredMatch = false;

        $builder->groupStart();

        if ($searchPlan['cliente'] !== null) {
            $builder->where("os.cliente_id IN ({$searchPlan['cliente']})", null, false);
            $hasStructuredMatch = true;
        }

        if ($searchPlan['equipamento'] !== null) {
            if ($hasStructuredMatch) {
                $builder->orWhere("os.equipamento_id IN ({$searchPlan['equipamento']})", null, false);
            } else {
                $builder->where("os.equipamento_id IN ({$searchPlan['equipamento']})", null, false);
                $hasStructuredMatch = true;
            }
        }

        if ($searchPlan['tecnico'] !== null) {
            if ($hasStructuredMatch) {
                $builder->orWhere("os.tecnico_id IN ({$searchPlan['tecnico']})", null, false);
            } else {
                $builder->where("os.tecnico_id IN ({$searchPlan['tecnico']})", null, false);
                $hasStructuredMatch = true;
            }
        }

        if ($searchPlan['relato_mode'] === 'fulltext') {
            $fulltextExpression = $this->buildRelatoFulltextExpression($query, $db);
            if ($hasStructuredMatch) {
                $builder->orWhere($fulltextExpression, null, false);
            } else {
                $builder->where($fulltextExpression, null, false);
                $hasStructuredMatch = true;
            }
        } elseif ($searchPlan['relato_mode'] === 'like') {
            if ($hasStructuredMatch) {
                $builder->orLike('os.relato_cliente', $query);
            } else {
                $builder->like('os.relato_cliente', $query);
                $hasStructuredMatch = true;
            }
        }

        if (!$hasStructuredMatch) {
            $builder->where('1 = 0', null, false);
        }

        $builder->groupEnd();
    }

    private function resolveOsGlobalSearchPlan(string $query, $db): array
    {
        $cacheKey = mb_strtolower(trim($query));
        if (isset($this->osListSearchMatcherCache[$cacheKey])) {
            return $this->osListSearchMatcherCache[$cacheKey];
        }

        $allowContains = mb_strlen($query) >= 4;

        $plan = [
            'cliente' => $this->resolveSearchSubquery('clientes', 'c', 'id', 'nome_razao', $query, $db, $allowContains),
            'equipamento' => $this->resolveEquipmentSearchSubquery($query, $db, $allowContains),
            'tecnico' => $this->resolveSearchSubquery('funcionarios', 'f', 'id', 'nome', $query, $db, $allowContains),
            'relato_mode' => null,
        ];

        if ($plan['cliente'] === null && $plan['equipamento'] === null && $plan['tecnico'] === null && mb_strlen($query) >= 4) {
            $plan['relato_mode'] = $this->canUseRelatoFulltext($query, $db) ? 'fulltext' : 'like';
        }

        $this->osListSearchMatcherCache[$cacheKey] = $plan;

        return $plan;
    }

    private function resolveSearchSubquery(
        string $table,
        string $alias,
        string $idColumn,
        string $searchColumn,
        string $query,
        $db,
        bool $allowContains = true
    ): ?string {
        if ($this->hasLikeMatch($table, $alias, $searchColumn, $query, 'after', $db)) {
            return $this->buildLikeSubquery($table, $alias, $idColumn, $searchColumn, $query, 'after', $db);
        }

        if ($allowContains && $this->hasLikeMatch($table, $alias, $searchColumn, $query, 'both', $db)) {
            return $this->buildLikeSubquery($table, $alias, $idColumn, $searchColumn, $query, 'both', $db);
        }

        return null;
    }

    private function resolveEquipmentSearchSubquery(string $query, $db, bool $allowContains = true): ?string
    {
        $brandPrefixMatch = $this->hasLikeMatch('equipamentos_marcas', 'em', 'nome', $query, 'after', $db);
        $modelPrefixMatch = $this->hasLikeMatch('equipamentos_modelos', 'emod', 'nome', $query, 'after', $db);

        if ($brandPrefixMatch || $modelPrefixMatch) {
            return $this->buildEquipmentSearchSubquery($query, 'after', $db, $brandPrefixMatch, $modelPrefixMatch);
        }

        if (!$allowContains) {
            return null;
        }

        $brandContainsMatch = $this->hasLikeMatch('equipamentos_marcas', 'em', 'nome', $query, 'both', $db);
        $modelContainsMatch = $this->hasLikeMatch('equipamentos_modelos', 'emod', 'nome', $query, 'both', $db);

        if ($brandContainsMatch || $modelContainsMatch) {
            return $this->buildEquipmentSearchSubquery($query, 'both', $db, $brandContainsMatch, $modelContainsMatch);
        }

        return null;
    }

    private function buildLikeSubquery(
        string $table,
        string $alias,
        string $idColumn,
        string $searchColumn,
        string $query,
        string $side,
        $db
    ): string {
        return $db->table("{$table} {$alias}")
            ->select("{$alias}.{$idColumn}", false)
            ->like("{$alias}.{$searchColumn}", $query, $side)
            ->getCompiledSelect();
    }

    private function buildEquipmentSearchSubquery(string $query, string $side, $db, bool $includeBrand, bool $includeModel): string
    {
        $builder = $db->table('equipamentos e')
            ->select('e.id', false);

        if ($includeBrand && $includeModel) {
            $brandSubquery = $this->buildLikeSubquery('equipamentos_marcas', 'em', 'id', 'nome', $query, $side, $db);
            $modelSubquery = $this->buildLikeSubquery('equipamentos_modelos', 'emod', 'id', 'nome', $query, $side, $db);
            $builder->groupStart()
                ->where("e.marca_id IN ({$brandSubquery})", null, false)
                ->orWhere("e.modelo_id IN ({$modelSubquery})", null, false)
            ->groupEnd();
        } elseif ($includeBrand) {
            $brandSubquery = $this->buildLikeSubquery('equipamentos_marcas', 'em', 'id', 'nome', $query, $side, $db);
            $builder->where("e.marca_id IN ({$brandSubquery})", null, false);
        } elseif ($includeModel) {
            $modelSubquery = $this->buildLikeSubquery('equipamentos_modelos', 'emod', 'id', 'nome', $query, $side, $db);
            $builder->where("e.modelo_id IN ({$modelSubquery})", null, false);
        } else {
            return '';
        }

        return $builder->getCompiledSelect();
    }

    private function hasLikeMatch(string $table, string $alias, string $column, string $query, string $side, $db): bool
    {
        $row = $db->table("{$table} {$alias}")
            ->select('1', false)
            ->like("{$alias}.{$column}", $query, $side)
            ->limit(1)
            ->get()
            ->getFirstRow('array');

        return $row !== null;
    }

    private function canUseRelatoFulltext(string $query, $db): bool
    {
        if (!$this->hasOsRelatoFulltextIndex($db)) {
            return false;
        }

        return $this->buildRelatoFulltextQuery($query) !== null;
    }

    private function hasOsRelatoFulltextIndex($db): bool
    {
        if ($this->osRelatoFulltextAvailable !== null) {
            return $this->osRelatoFulltextAvailable;
        }

        $sql = "
            SELECT 1
            FROM information_schema.statistics
            WHERE table_schema = DATABASE()
              AND table_name = 'os'
              AND index_type = 'FULLTEXT'
              AND column_name = 'relato_cliente'
            LIMIT 1
        ";

        $row = $db->query($sql)->getFirstRow('array');
        $this->osRelatoFulltextAvailable = $row !== null;

        return $this->osRelatoFulltextAvailable;
    }

    private function buildRelatoFulltextExpression(string $query, $db): string
    {
        $fulltextQuery = $this->buildRelatoFulltextQuery($query);
        if ($fulltextQuery === null) {
            $escaped = $db->escapeLikeString($query);
            return "os.relato_cliente LIKE '%{$escaped}%'";
        }

        return "MATCH(os.relato_cliente) AGAINST (" . $db->escape($fulltextQuery) . " IN BOOLEAN MODE)";
    }

    private function buildRelatoFulltextQuery(string $query): ?string
    {
        $tokens = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower(trim($query))) ?: [];
        $tokens = array_values(array_unique(array_filter($tokens, static fn (string $token): bool => mb_strlen($token) >= 3)));

        if ($tokens === []) {
            return null;
        }

        $tokens = array_map(static fn (string $token): string => '+' . $token . '*', $tokens);

        return implode(' ', $tokens);
    }

    private function buildOsListPhotoContext(array $rows): array
    {
        $context = [
            'profileThumbByEquipamento' => [],
            'profileCountByEquipamento' => [],
            'entryCountByOs' => [],
        ];

        if (empty($rows)) {
            return $context;
        }

        $equipamentoIds = array_values(array_unique(array_filter(array_map(
            static fn (array $row): int => (int) ($row['equipamento_id'] ?? 0),
            $rows
        ))));

        $osIds = array_values(array_unique(array_filter(array_map(
            static fn (array $row): int => (int) ($row['id'] ?? 0),
            $rows
        ))));

        if (!empty($equipamentoIds)) {
            $equipamentoFotos = (new EquipamentoFotoModel())
                ->select('id, equipamento_id, arquivo, is_principal, created_at')
                ->whereIn('equipamento_id', $equipamentoIds)
                ->orderBy('equipamento_id', 'ASC')
                ->orderBy('is_principal', 'DESC')
                ->orderBy('id', 'ASC')
                ->findAll();

            foreach ($equipamentoFotos as $foto) {
                $equipamentoId = (int) ($foto['equipamento_id'] ?? 0);
                if ($equipamentoId <= 0) {
                    continue;
                }

                $context['profileCountByEquipamento'][$equipamentoId] = ($context['profileCountByEquipamento'][$equipamentoId] ?? 0) + 1;
                if (!isset($context['profileThumbByEquipamento'][$equipamentoId])) {
                    $context['profileThumbByEquipamento'][$equipamentoId] = $this->appendAssetVersion(
                        $this->resolveEquipamentoFotoPublicUrl((string) ($foto['arquivo'] ?? '')),
                        (string) ($foto['id'] ?? '')
                    );
                }
            }
        }

        if (!empty($osIds)) {
            $entradaFotos = (new OsFotoModel())
                ->select('id, os_id, arquivo, created_at')
                ->whereIn('os_id', $osIds)
                ->where('tipo', 'recepcao')
                ->orderBy('os_id', 'ASC')
                ->orderBy('id', 'ASC')
                ->findAll();

            foreach ($entradaFotos as $foto) {
                $osId = (int) ($foto['os_id'] ?? 0);
                if ($osId <= 0) {
                    continue;
                }

                $context['entryCountByOs'][$osId] = ($context['entryCountByOs'][$osId] ?? 0) + 1;
            }
        }

        return $context;
    }

    private function formatOsDatatableRow(array $row, array $photoContext = []): array
    {
        $valorFormatado = ($row['valor_final'] ?? 0) > 0
            ? 'R$ ' . number_format((float) $row['valor_final'], 2, ',', '.')
            : '-';

        $acoes = '<div class="btn-group btn-group-sm">
                    <a href="' . base_url('os/visualizar/' . $row['id']) . '" class="btn btn-outline-info" title="Visualizar"><i class="bi bi-eye"></i></a>';
        if (can('os', 'editar')) {
            $acoes .= '<a href="' . base_url('os/editar/' . $row['id']) . '" class="btn btn-outline-primary" title="Editar"><i class="bi bi-pencil"></i></a>';
        }
        if (can('os', 'encerrar')) {
            $acoes .= '<a href="javascript:void(0)" class="btn btn-outline-warning" title="Encerrar" onclick="confirmarEncerramento(\'os\', ' . $row['id'] . ')"><i class="bi bi-archive"></i></a>';
        }
        $acoes .= '</div>';

        $numeroOsHtml = '<span class="os-numero-cell"><strong>#' . esc($row['numero_os']) . '</strong>';
        if (!empty($row['numero_os_legado'])) {
            $numeroOsHtml .= '<small class="os-numero-meta">Legado: ' . esc((string) $row['numero_os_legado']) . '</small>';
        }
        if (!empty($row['legacy_origem'])) {
            $numeroOsHtml .= '<small class="os-numero-meta">Origem: ' . esc((string) $row['legacy_origem']) . '</small>';
        }
        $numeroOsHtml .= '</span>';

        return [
            $this->formatOsPhotoCell($row, $photoContext),
            '<a href="' . base_url('os/visualizar/' . $row['id']) . '" class="os-numero-link" title="Abrir visualizacao da OS">' . $numeroOsHtml . '</a>',
            $this->formatOsClientCell($row),
            $this->formatOsEquipmentCell($row),
            $this->formatOsRelatoCell($row),
            $this->formatOsDatesCell($row),
            $this->formatOsStatusCell($row),
            $this->formatOsValueCell($row, $valorFormatado),
            $acoes,
        ];
    }

    private function formatOsClientCell(array $row): string
    {
        $clientName = (string) ($row['cliente_nome'] ?? '');
        $clientName = trim($clientName);
        if ($clientName === '') {
            return '<div class="fw-semibold os-cliente-cell text-muted">-</div>';
        }

        $parts = preg_split('/\s+/u', $clientName) ?: [];
        $parts = array_values(array_filter($parts, static fn (string $part): bool => $part !== ''));

        if (count($parts) >= 4) {
            $firstLine = implode(' ', array_slice($parts, 0, 2));
            $secondLine = implode(' ', array_slice($parts, 2));

            $content = implode('', [
                '<div class="fw-semibold os-cliente-cell">',
                '<span class="os-cliente-line">' . esc($firstLine) . '</span>',
                '<span class="os-cliente-line">' . esc($secondLine) . '</span>',
                '</div>',
            ]);
        } else {
            $content = '<div class="fw-semibold os-cliente-cell"><span class="os-cliente-line">' . esc($clientName) . '</span></div>';
        }

        $clienteId = (int) ($row['cliente_id'] ?? 0);
        if ($clienteId <= 0 || !can('clientes', 'visualizar')) {
            return $content;
        }

        return '<button type="button" class="btn btn-link p-0 text-start os-cell-link os-cell-link-client" data-os-frame-modal-url="' . esc(base_url('clientes/visualizar/' . $clienteId . '?embed=1')) . '" data-os-frame-modal-title="' . esc('Cliente: ' . $clientName) . '" title="Abrir ficha completa e historico do cliente">' . $content . '</button>';
    }

    private function formatOsPhotoCell(array $row, array $photoContext = []): string
    {
        $osId = (int) ($row['id'] ?? 0);
        $equipamentoId = (int) ($row['equipamento_id'] ?? 0);
        $thumbUrl = (string) ($photoContext['profileThumbByEquipamento'][$equipamentoId] ?? $this->missingImageDataUri());
        $profileCount = (int) ($photoContext['profileCountByEquipamento'][$equipamentoId] ?? 0);
        $entryCount = (int) ($photoContext['entryCountByOs'][$osId] ?? 0);
        $totalPhotos = $profileCount + $entryCount;

        $tooltipParts = [];
        if ($profileCount > 0) {
            $tooltipParts[] = $profileCount . ' foto(s) de perfil';
        }
        if ($entryCount > 0) {
            $tooltipParts[] = $entryCount . ' foto(s) da abertura';
        }
        if (empty($tooltipParts)) {
            $tooltipParts[] = 'Sem fotos cadastradas';
        }

        return implode('', [
            '<div class="os-foto-cell">',
            '<button type="button" class="btn btn-link p-0 os-foto-trigger" data-os-photo-action data-os-id="' . $osId . '" data-os-numero="' . esc((string) ($row['numero_os'] ?? '')) . '" title="' . esc('Visualizar fotos: ' . implode(' | ', $tooltipParts)) . '">',
            '<span class="os-foto-thumb-wrap">',
            '<img src="' . esc($thumbUrl) . '" alt="' . esc('Foto do equipamento da OS ' . (string) ($row['numero_os'] ?? '')) . '" class="os-foto-thumb" loading="lazy">',
            '<span class="os-foto-zoom-badge"><i class="bi bi-images"></i></span>',
            ($totalPhotos > 1 ? '<span class="os-foto-count-badge">' . $totalPhotos . '</span>' : ''),
            '</span>',
            '</button>',
            '</div>',
        ]);
    }

    private function formatOsEquipmentCell(array $row): string
    {
        $type = trim((string) ($row['equip_tipo'] ?? '')) ?: '-';
        $brand = trim((string) ($row['equip_marca'] ?? '')) ?: '-';
        $model = trim((string) ($row['equip_modelo'] ?? '')) ?: '-';

        $content = implode('', [
            '<div class="os-equipamento-cell">',
            '<div class="os-equipamento-line"><span class="os-equipamento-label">Tipo:</span><span class="os-equipamento-value">' . esc($type) . '</span></div>',
            '<div class="os-equipamento-line"><span class="os-equipamento-label">Marca:</span><span class="os-equipamento-value">' . esc($brand) . '</span></div>',
            '<div class="os-equipamento-line"><span class="os-equipamento-label">Modelo:</span><span class="os-equipamento-value">' . esc($model) . '</span></div>',
            '</div>',
        ]);

        $equipamentoId = (int) ($row['equipamento_id'] ?? 0);
        if ($equipamentoId <= 0 || !can('equipamentos', 'visualizar')) {
            return $content;
        }

        $modalTitle = trim((string) (($row['equip_marca'] ?? '') . ' ' . ($row['equip_modelo'] ?? '')));
        if ($modalTitle === '') {
            $modalTitle = 'Equipamento';
        }

        return '<button type="button" class="btn btn-link p-0 text-start os-cell-link os-cell-link-equipment" data-os-frame-modal-url="' . esc(base_url('equipamentos/visualizar/' . $equipamentoId . '?embed=1')) . '" data-os-frame-modal-title="' . esc('Equipamento: ' . $modalTitle) . '" title="Abrir detalhes do equipamento">' . $content . '</button>';
    }

    private function formatOsRelatoCell(array $row): string
    {
        $relato = trim((string) ($row['relato_cliente'] ?? ''));
        if ($relato === '') {
            return '<span class="text-muted">-</span>';
        }

        return '<div class="os-relato-cell">' . nl2br(esc($relato)) . '</div>';
    }

    private function formatOsDatesCell(array $row): string
    {
        $entradaRaw = !empty($row['data_entrada']) ? (string) $row['data_entrada'] : (string) ($row['data_abertura'] ?? '');
        $previsaoRaw = trim((string) ($row['data_previsao'] ?? ''));
        $entregaRaw = trim((string) ($row['data_entrega'] ?? ''));

        $entrada = $entradaRaw !== '' ? date('d/m/Y', strtotime($entradaRaw)) : '-';
        $prazoLabel = '<span class="text-muted">-</span>';
        $entregaLabel = '<span class="text-muted">-</span>';

        if ($previsaoRaw !== '') {
            $prazoClass = $this->resolvePrazoClass($previsaoRaw, $entregaRaw);
            $prazoText = $this->buildPrazoIndicatorText($entradaRaw, $previsaoRaw, $entregaRaw);
            $prazoLabel = '<span class="os-date-indicator ' . esc($prazoClass) . '">' . esc($prazoText) . '</span>';
        }

        if ($entregaRaw !== '') {
            $entregaClass = (!empty($previsaoRaw) && strtotime($entregaRaw) > strtotime($previsaoRaw))
                ? 'is-danger'
                : 'is-success';
            $entregaLabel = '<span class="os-date-indicator ' . esc($entregaClass) . '">' . esc(date('d/m/Y', strtotime($entregaRaw))) . '</span>';
        }

        $content = implode('', [
            '<div class="os-dates-cell">',
            '<div class="os-date-line"><span class="os-date-label">Entrada:</span><span class="os-date-value">' . esc($entrada) . '</span></div>',
            '<div class="os-date-line"><span class="os-date-label">Prazo:</span><span class="os-date-value">' . $prazoLabel . '</span></div>',
            '<div class="os-date-line"><span class="os-date-label">Entrega:</span><span class="os-date-value">' . $entregaLabel . '</span></div>',
            '</div>',
        ]);

        if (!can('os', 'editar')) {
            return $content;
        }

        return '<button type="button" class="btn btn-link p-0 text-start os-cell-link os-cell-link-dates" data-os-dates-action data-os-id="' . (int) ($row['id'] ?? 0) . '" title="Atualizar prazos da OS">' . $content . '</button>';
    }

    private function formatOsValueCell(array $row, string $valorFormatado): string
    {
        $content = '<span class="os-valor-cell">' . esc($valorFormatado) . '</span>';

        if (!can('os', 'editar')) {
            return $content;
        }

        return '<button type="button" class="btn btn-link p-0 text-start os-cell-link os-cell-link-value" data-os-budget-action data-os-id="' . (int) ($row['id'] ?? 0) . '" title="Gerar e enviar orcamento da OS">' . $content . '</button>';
    }

    private function formatOsStatusCell(array $row): string
    {
        $statusBadge = getStatusBadge((string) ($row['status'] ?? ''));
        $fluxo = trim((string) ($row['estado_fluxo'] ?? ''));
        $fluxoBadge = $fluxo !== ''
            ? '<span class="badge bg-light text-dark border">' . esc(ucwords(str_replace('_', ' ', $fluxo))) . '</span>'
            : '<span class="text-muted">-</span>';

        $content = implode('', [
            '<div class="os-status-content">',
            '<div>' . $statusBadge . '</div>',
            '<div class="mt-1">' . $fluxoBadge . '</div>',
            '</div>',
        ]);

        if (!can('os', 'editar')) {
            return $content;
        }

        return '<button type="button" class="btn btn-link p-0 text-start os-status-trigger" data-os-status-action data-os-id="' . (int) ($row['id'] ?? 0) . '" data-os-numero="' . esc((string) ($row['numero_os'] ?? '')) . '" title="Alterar status da OS">' . $content . '<span class="os-status-trigger-hint"><i class="bi bi-arrow-left-right me-1"></i>Alterar status</span></button>';
    }

    private function resolvePrazoClass(string $previsaoRaw, string $entregaRaw): string
    {
        if ($previsaoRaw === '') {
            return 'is-muted';
        }

        if ($entregaRaw !== '') {
            return strtotime($entregaRaw) > strtotime($previsaoRaw) ? 'is-danger' : 'is-success';
        }

        $today = strtotime(date('Y-m-d'));
        $previsao = strtotime(date('Y-m-d', strtotime($previsaoRaw)));
        $diffDays = (int) floor(($previsao - $today) / 86400);

        if ($diffDays < 0) {
            return 'is-danger';
        }
        if ($diffDays === 0) {
            return 'is-orange';
        }
        if ($diffDays <= 2) {
            return 'is-warning';
        }

        return 'is-success';
    }

    private function calculatePrazoDays(string $entradaRaw, string $previsaoRaw): ?int
    {
        if ($previsaoRaw === '') {
            return null;
        }

        $entradaBase = $entradaRaw !== '' ? strtotime(date('Y-m-d', strtotime($entradaRaw))) : null;
        $previsaoBase = strtotime(date('Y-m-d', strtotime($previsaoRaw)));

        if ($entradaBase === null || $previsaoBase === false) {
            return null;
        }

        return max(0, (int) round(($previsaoBase - $entradaBase) / 86400));
    }

    private function calculateElapsedDays(string $startRaw, string $endRaw): ?int
    {
        $start = trim($startRaw);
        $end = trim($endRaw);
        if ($start === '' || $end === '') {
            return null;
        }

        $startBase = strtotime(date('Y-m-d', strtotime($start)));
        $endBase = strtotime(date('Y-m-d', strtotime($end)));

        if ($startBase === false || $endBase === false) {
            return null;
        }

        return max(0, (int) round(($endBase - $startBase) / 86400));
    }

    private function buildPrazoIndicatorText(string $entradaRaw, string $previsaoRaw, string $entregaRaw): string
    {
        $previsaoLabel = date('d/m/Y', strtotime($previsaoRaw));
        $prazoDays = $this->calculatePrazoDays($entradaRaw, $previsaoRaw);
        $prazoBaseLabel = $prazoDays !== null
            ? ($prazoDays . ' dia' . ($prazoDays === 1 ? '' : 's'))
            : 'Sem prazo';

        if ($entregaRaw !== '') {
            $delayDays = $this->calculateElapsedDays($previsaoRaw, $entregaRaw);
            if ($delayDays !== null && $delayDays > 0) {
                return 'Atraso de ' . $delayDays . ' dia' . ($delayDays === 1 ? '' : 's') . ' - ' . $previsaoLabel;
            }

            return $prazoBaseLabel . ' - ' . $previsaoLabel;
        }

        $overdueDays = $this->calculateElapsedDays($previsaoRaw, date('Y-m-d'));
        if ($overdueDays !== null && $overdueDays > 0) {
            return 'Atrasado ha ' . $overdueDays . ' dia' . ($overdueDays === 1 ? '' : 's') . ' - ' . $previsaoLabel;
        }

        return $prazoBaseLabel . ' - ' . $previsaoLabel;
    }

    public function statusMeta($id)
    {
        $id = (int) $id;
        $os = $this->model->getComplete($id);
        if (!$os) {
            $os = $this->model->find($id);
        }

        if (!$os) {
            return $this->response->setStatusCode(404)->setJSON([
                'ok' => false,
                'message' => 'OS nao encontrada.',
            ]);
        }

        $service = new OsStatusFlowService();
        $statusGrouped = $service->getStatusGrouped();
        $allowed = $service->buildTransitionHints((string) ($os['status'] ?? ''));
        $grouped = [];
        foreach ($allowed as $status) {
            $macro = (string) ($status['grupo_macro'] ?? 'outros');
            $grouped[$macro][] = $status;
        }

        $statusHistorico = ((new OsStatusHistoricoModel())->db->tableExists('os_status_historico'))
            ? (new OsStatusHistoricoModel())->byOs($id)
            : [];
        $estadoFluxo = trim((string) ($os['estado_fluxo'] ?? ''));

        return $this->response->setJSON([
            'ok' => true,
            'os' => [
                'id' => (int) $os['id'],
                'numero_os' => (string) ($os['numero_os'] ?? ''),
                'status' => (string) ($os['status'] ?? ''),
                'estado_fluxo' => (string) ($os['estado_fluxo'] ?? ''),
                'status_nome' => $this->humanizeOsStatus((string) ($os['status'] ?? '')),
                'prioridade' => (string) ($os['prioridade'] ?? 'normal'),
                'cliente_nome' => (string) ($os['cliente_nome'] ?? ''),
                'cliente_telefone' => (string) ($os['cliente_telefone'] ?? ''),
                'cliente_email' => (string) ($os['cliente_email'] ?? ''),
                'equipamento_nome' => trim((string) (($os['equip_marca'] ?? '') . ' ' . ($os['equip_modelo'] ?? ''))),
                'equip_tipo' => (string) ($os['equip_tipo'] ?? ''),
                'equip_tipo_label' => getEquipTipo((string) ($os['equip_tipo'] ?? '')),
                'equip_marca' => (string) ($os['equip_marca'] ?? ''),
                'equip_modelo' => (string) ($os['equip_modelo'] ?? ''),
                'equip_serie' => (string) ($os['equip_serie'] ?? ''),
                'statusBadgeHtml' => getStatusBadge((string) ($os['status'] ?? '')),
                'flowBadgeHtml' => $estadoFluxo !== ''
                    ? '<span class="badge bg-light text-dark border">' . esc(ucwords(str_replace('_', ' ', $estadoFluxo))) . '</span>'
                    : '',
                'priorityBadgeHtml' => getPriorityBadge((string) ($os['prioridade'] ?? 'normal')),
            ],
            'options' => $grouped,
            'primaryNextStatus' => $this->resolvePrimaryNextStatus(
                $service,
                (string) ($os['status'] ?? ''),
                $allowed
            ),
            'workflowTimeline' => $this->buildOsWorkflowTimeline(
                $statusGrouped,
                $statusHistorico,
                (string) ($os['status'] ?? ''),
                $allowed
            ),
            'workflowRecentHistory' => array_slice($statusHistorico, 0, 4),
            'hasClientPhone' => trim((string) ($os['cliente_telefone'] ?? '')) !== '',
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function updateStatusAjax($id)
    {
        $os = $this->model->find((int) $id);
        if (!$os) {
            return $this->response->setStatusCode(404)->setJSON([
                'ok' => false,
                'message' => 'OS nao encontrada.',
                'csrfHash' => csrf_hash(),
            ]);
        }

        $status = strtolower(trim((string) $this->request->getPost('status')));
        $observacao = trim((string) $this->request->getPost('observacao_status'));
        $controlaComunicacaoCliente = !empty($this->request->getPost('controla_comunicacao_cliente'));
        $comunicarCliente = $controlaComunicacaoCliente && !empty($this->request->getPost('comunicar_cliente'));

        if ($status === '') {
            return $this->response->setStatusCode(422)->setJSON([
                'ok' => false,
                'message' => 'Selecione um novo status.',
                'csrfHash' => csrf_hash(),
            ]);
        }

        $statusService = new OsStatusFlowService();
        $result = $statusService->applyStatus(
            (int) $id,
            $status,
            session()->get('user_id') ?: null,
            $observacao !== '' ? $observacao : null
        );

        if (empty($result['ok'])) {
            return $this->response->setStatusCode(422)->setJSON([
                'ok' => false,
                'message' => $result['message'] ?? 'Nao foi possivel atualizar o status.',
                'csrfHash' => csrf_hash(),
            ]);
        }

        $this->finalizeStatusSideEffects((int) $id, $os, $status, !$controlaComunicacaoCliente);

        $warningMessage = null;
        if ($comunicarCliente) {
            $notifyResult = $this->sendStatusChangeNotification(
                (int) $id,
                $status,
                $observacao !== '' ? $observacao : null,
                session()->get('user_id') ?: null
            );

            if (empty($notifyResult['ok'])) {
                $warningMessage = $notifyResult['message'] ?? 'O status foi atualizado, mas nao foi possivel comunicar o cliente.';
            }
        }

        LogModel::registrar('os_status', 'Status da OS ' . $os['numero_os'] . ' alterado para: ' . $status . ' via listagem.');

        $response = [
            'ok' => true,
            'message' => 'Status atualizado com sucesso.',
            'csrfHash' => csrf_hash(),
        ];

        if ($warningMessage !== null) {
            $response['warning'] = $warningMessage;
        }

        return $this->response->setJSON($response);
    }

    public function datesMeta($id)
    {
        $os = $this->model->getComplete((int) $id);
        if (!$os) {
            return $this->response->setStatusCode(404)->setJSON([
                'ok' => false,
                'message' => 'OS nao encontrada.',
                'csrfHash' => csrf_hash(),
            ]);
        }

        return $this->response->setJSON([
            'ok' => true,
            'os' => $this->buildListOsContextPayload($os),
            'dates' => [
                'data_entrada' => $this->formatDateTimeInputValue($os['data_entrada'] ?? $os['data_abertura'] ?? null),
                'data_previsao' => $this->formatDateInputValue($os['data_previsao'] ?? null),
                'data_entrega' => $this->formatDateInputValue($os['data_entrega'] ?? null),
                'data_entrada_label' => $this->formatDateDisplay($os['data_entrada'] ?? $os['data_abertura'] ?? null, true),
                'data_previsao_label' => $this->formatDateDisplay($os['data_previsao'] ?? null),
                'data_entrega_label' => $this->formatDateDisplay($os['data_entrega'] ?? null),
                'prazo_dias' => $this->calculatePrazoDays(
                    (string) ($os['data_entrada'] ?? $os['data_abertura'] ?? ''),
                    (string) ($os['data_previsao'] ?? '')
                ),
            ],
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function updateDatesAjax($id)
    {
        $osId = (int) $id;
        $os = $this->model->find($osId);
        if (!$os) {
            return $this->response->setStatusCode(404)->setJSON([
                'ok' => false,
                'message' => 'OS nao encontrada.',
                'csrfHash' => csrf_hash(),
            ]);
        }

        $dataEntradaInformada = trim((string) $this->request->getPost('data_entrada'));
        $dataEntregaInformada = trim((string) $this->request->getPost('data_entrega'));
        if ($dataEntradaInformada !== '' || $dataEntregaInformada !== '') {
            return $this->response->setStatusCode(422)->setJSON([
                'ok' => false,
                'message' => 'Neste modal, apenas a previsao pode ser alterada. A entrada e a entrega seguem o fluxo operacional correto da OS.',
                'csrfHash' => csrf_hash(),
            ]);
        }

        try {
            $previsao = $this->normalizeNullableDateInput((string) $this->request->getPost('data_previsao'));
        } catch (\InvalidArgumentException $e) {
            return $this->response->setStatusCode(422)->setJSON([
                'ok' => false,
                'message' => $e->getMessage(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $entradaAtual = (string) ($os['data_entrada'] ?? $os['data_abertura'] ?? '');
        $entradaComparacao = $this->extractDateOnly($entradaAtual);
        if ($entradaComparacao !== null && $previsao !== null && strtotime($previsao) < strtotime($entradaComparacao)) {
            return $this->response->setStatusCode(422)->setJSON([
                'ok' => false,
                'message' => 'A previsao nao pode ser anterior a data de entrada.',
                'csrfHash' => csrf_hash(),
            ]);
        }

        $this->model->update($osId, [
            'data_previsao' => $previsao,
        ]);

        LogModel::registrar(
            'os_prazos_atualizados',
            'Prazos da OS ' . ($os['numero_os'] ?? ('#' . $osId)) . ' atualizados via listagem.'
        );

        return $this->response->setJSON([
            'ok' => true,
            'message' => 'Prazos da OS atualizados com sucesso.',
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function budgetMeta($id)
    {
        $os = $this->model->getComplete((int) $id);
        if (!$os) {
            return $this->response->setStatusCode(404)->setJSON([
                'ok' => false,
                'message' => 'OS nao encontrada.',
                'csrfHash' => csrf_hash(),
            ]);
        }

        $documents = [];
        $documentoModel = new OsDocumentoModel();
        if ($documentoModel->db->tableExists('os_documentos')) {
            $documents = array_map(function (array $doc): array {
                return [
                    'id' => (int) ($doc['id'] ?? 0),
                    'tipo' => (string) ($doc['tipo_documento'] ?? ''),
                    'versao' => (int) ($doc['versao'] ?? 1),
                    'arquivo' => (string) ($doc['arquivo'] ?? ''),
                    'url' => !empty($doc['arquivo']) ? base_url((string) $doc['arquivo']) : '',
                    'created_at' => (string) ($doc['created_at'] ?? ''),
                    'created_at_label' => $this->formatDateDisplay($doc['created_at'] ?? null, true),
                ];
            }, $documentoModel
                ->where('os_id', (int) $id)
                ->where('tipo_documento', 'orcamento')
                ->orderBy('created_at', 'DESC')
                ->findAll(10));
        }

        return $this->response->setJSON([
            'ok' => true,
            'os' => $this->buildListOsContextPayload($os),
            'budget' => [
                'telefone' => (string) ($os['cliente_telefone'] ?? ''),
                'valor_mao_obra' => (float) ($os['valor_mao_obra'] ?? 0),
                'valor_pecas' => (float) ($os['valor_pecas'] ?? 0),
                'valor_total' => (float) ($os['valor_total'] ?? 0),
                'desconto' => (float) ($os['desconto'] ?? 0),
                'valor_final' => (float) ($os['valor_final'] ?? 0),
                'valor_mao_obra_label' => 'R$ ' . number_format((float) ($os['valor_mao_obra'] ?? 0), 2, ',', '.'),
                'valor_pecas_label' => 'R$ ' . number_format((float) ($os['valor_pecas'] ?? 0), 2, ',', '.'),
                'valor_total_label' => 'R$ ' . number_format((float) ($os['valor_total'] ?? 0), 2, ',', '.'),
                'desconto_label' => 'R$ ' . number_format((float) ($os['desconto'] ?? 0), 2, ',', '.'),
                'valor_final_label' => 'R$ ' . number_format((float) ($os['valor_final'] ?? 0), 2, ',', '.'),
                'can_send_whatsapp' => can('os', 'editar'),
                'has_client_phone' => trim((string) ($os['cliente_telefone'] ?? '')) !== '',
                'documents' => $documents,
            ],
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function budgetAjax($id)
    {
        $osId = (int) $id;
        $os = $this->model->getComplete($osId);
        if (!$os) {
            return $this->response->setStatusCode(404)->setJSON([
                'ok' => false,
                'message' => 'OS nao encontrada.',
                'csrfHash' => csrf_hash(),
            ]);
        }

        $pdfService = new OsPdfService();
        $pdfResult = $pdfService->gerar($osId, 'orcamento', session()->get('user_id') ?: null);
        if (empty($pdfResult['ok'])) {
            return $this->response->setStatusCode(422)->setJSON([
                'ok' => false,
                'message' => $pdfResult['message'] ?? 'Nao foi possivel gerar o PDF do orcamento.',
                'csrfHash' => csrf_hash(),
            ]);
        }

        $warningMessage = null;
        $sendRequested = !empty($this->request->getPost('enviar_cliente'));
        if ($sendRequested) {
            if (!can('os', 'editar')) {
                return $this->response->setStatusCode(403)->setJSON([
                    'ok' => false,
                    'message' => 'Sem permissao para enviar o orcamento ao cliente.',
                    'csrfHash' => csrf_hash(),
                ]);
            }

            $telefone = trim((string) ($this->request->getPost('telefone') ?: ($os['cliente_telefone'] ?? '')));
            if ($telefone === '') {
                $warningMessage = 'O PDF foi gerado, mas o cliente nao possui telefone cadastrado para envio.';
            } else {
                $mensagem = trim((string) $this->request->getPost('mensagem_manual'));
                $os['cliente_telefone'] = $telefone;
                $whatsService = new WhatsAppService();

                if ($mensagem !== '') {
                    $sendResult = $whatsService->sendRaw(
                        $osId,
                        (int) ($os['cliente_id'] ?? 0),
                        $telefone,
                        $mensagem,
                        'orcamento_manual',
                        null,
                        session()->get('user_id') ?: null,
                        [
                            'arquivo_path' => (string) ($pdfResult['path'] ?? ''),
                            'arquivo' => (string) ($pdfResult['relative'] ?? ''),
                        ]
                    );
                } else {
                    $sendResult = $whatsService->sendByTemplate(
                        $os,
                        'orcamento_enviado',
                        session()->get('user_id') ?: null,
                        [
                            'pdf_url' => (string) ($pdfResult['url'] ?? ''),
                            'arquivo_path' => (string) ($pdfResult['path'] ?? ''),
                            'arquivo' => (string) ($pdfResult['relative'] ?? ''),
                        ]
                    );
                }

                if (empty($sendResult['ok'])) {
                    $warningMessage = $sendResult['message'] ?? 'O PDF foi gerado, mas houve falha ao enviar para o cliente.';
                }
            }
        }

        LogModel::registrar(
            'os_orcamento_pdf',
            'Orcamento PDF da OS ' . ($os['numero_os'] ?? ('#' . $osId)) . ' gerado via listagem.'
        );

        return $this->response->setJSON([
            'ok' => true,
            'message' => $sendRequested
                ? ($warningMessage === null ? 'Orcamento gerado e enviado com sucesso.' : 'Orcamento gerado com ressalvas.')
                : 'Orcamento PDF gerado com sucesso.',
            'warning' => $warningMessage,
            'documentUrl' => (string) ($pdfResult['url'] ?? ''),
            'csrfHash' => csrf_hash(),
        ]);
    }

    private function buildListOsContextPayload(array $os): array
    {
        $estadoFluxo = trim((string) ($os['estado_fluxo'] ?? ''));

        return [
            'id' => (int) ($os['id'] ?? 0),
            'numero_os' => (string) ($os['numero_os'] ?? ''),
            'status' => (string) ($os['status'] ?? ''),
            'estado_fluxo' => $estadoFluxo,
            'prioridade' => (string) ($os['prioridade'] ?? 'normal'),
            'cliente_id' => (int) ($os['cliente_id'] ?? 0),
            'cliente_nome' => (string) ($os['cliente_nome'] ?? ''),
            'cliente_telefone' => (string) ($os['cliente_telefone'] ?? ''),
            'cliente_email' => (string) ($os['cliente_email'] ?? ''),
            'equipamento_id' => (int) ($os['equipamento_id'] ?? 0),
            'equipamento_nome' => trim((string) (($os['equip_marca'] ?? '') . ' ' . ($os['equip_modelo'] ?? ''))),
            'equip_tipo' => (string) ($os['equip_tipo'] ?? ''),
            'equip_tipo_label' => getEquipTipo((string) ($os['equip_tipo'] ?? '')),
            'equip_marca' => (string) ($os['equip_marca'] ?? ''),
            'equip_modelo' => (string) ($os['equip_modelo'] ?? ''),
            'equip_serie' => (string) ($os['equip_serie'] ?? ''),
            'statusBadgeHtml' => getStatusBadge((string) ($os['status'] ?? '')),
            'flowBadgeHtml' => $estadoFluxo !== ''
                ? '<span class="badge bg-light text-dark border">' . esc(ucwords(str_replace('_', ' ', $estadoFluxo))) . '</span>'
                : '',
            'priorityBadgeHtml' => getPriorityBadge((string) ($os['prioridade'] ?? 'normal')),
        ];
    }

    private function formatDateTimeInputValue($value): string
    {
        $raw = trim((string) ($value ?? ''));
        if ($raw === '') {
            return '';
        }

        $timestamp = strtotime($raw);
        return $timestamp ? date('Y-m-d\TH:i', $timestamp) : '';
    }

    private function formatDateInputValue($value): string
    {
        $raw = trim((string) ($value ?? ''));
        if ($raw === '') {
            return '';
        }

        $timestamp = strtotime($raw);
        return $timestamp ? date('Y-m-d', $timestamp) : '';
    }

    private function formatDateDisplay($value, bool $withTime = false): string
    {
        $raw = trim((string) ($value ?? ''));
        if ($raw === '') {
            return '-';
        }

        $timestamp = strtotime($raw);
        if (!$timestamp) {
            return '-';
        }

        return $withTime ? date('d/m/Y H:i', $timestamp) : date('d/m/Y', $timestamp);
    }

    private function normalizeNullableDateTimeInput(string $value): ?string
    {
        $raw = trim($value);
        if ($raw === '') {
            return null;
        }

        $timestamp = strtotime($raw);
        if (!$timestamp) {
            throw new \InvalidArgumentException('Informe uma data de entrada valida.');
        }

        return date('Y-m-d H:i:s', $timestamp);
    }

    private function normalizeNullableDateInput(string $value): ?string
    {
        $raw = trim($value);
        if ($raw === '') {
            return null;
        }

        $timestamp = strtotime($raw);
        if (!$timestamp) {
            throw new \InvalidArgumentException('Informe uma data valida.');
        }

        return date('Y-m-d', $timestamp);
    }

    private function extractDateOnly(?string $value): ?string
    {
        $raw = trim((string) ($value ?? ''));
        if ($raw === '') {
            return null;
        }

        return substr($raw, 0, 10);
    }

    private function hasActiveListFilters(array $filters): bool
    {
        foreach ($filters as $value) {
            if (is_array($value) && !empty($value)) {
                return true;
            }

            if (!is_array($value) && $value !== null && $value !== '') {
                return true;
            }
        }

        return false;
    }

    private function requiresStatusLookupJoin(array $filters): bool
    {
        return trim((string) ($filters['macrofase'] ?? '')) !== '';
    }

    private function isLikelyOsNumberSearch(string $query): bool
    {
        $normalized = strtoupper(str_replace(['#', ' '], '', trim($query)));
        if ($normalized === '') {
            return false;
        }

        if (str_starts_with($normalized, 'OS')) {
            return preg_match('/^OS\d+$/', $normalized) === 1;
        }

        return ctype_digit($normalized) && strlen($normalized) >= 4;
    }

    private function normalizeOsNumberSearchTerm(string $query): string
    {
        $normalized = strtoupper(str_replace(['#', ' '], '', trim($query)));
        if (str_starts_with($normalized, 'OS')) {
            return $normalized;
        }

        $digits = preg_replace('/\D+/', '', $normalized);
        return $digits !== '' ? 'OS' . $digits : $normalized;
    }

    private function buildDayStart(string $date): string
    {
        return $date . ' 00:00:00';
    }

    private function buildNextDayStart(string $date): string
    {
        return date('Y-m-d H:i:s', strtotime($date . ' +1 day'));
    }

    private function normalizeStringList($raw): array
    {
        if ($raw === null) {
            return [];
        }

        $values = [];
        if (is_array($raw)) {
            $values = $raw;
        } else {
            $rawString = trim((string) $raw);
            if ($rawString === '') {
                return [];
            }
            $values = preg_split('/\s*,\s*/', $rawString) ?: [];
        }

        $values = array_map(static fn ($value) => trim((string) $value), $values);
        $values = array_filter($values, static fn ($value) => $value !== '' && $value !== 'todos');

        return array_values(array_unique($values));
    }

    private function normalizeDateValue($raw): ?string
    {
        $value = trim((string) ($raw ?? ''));
        if ($value === '') {
            return null;
        }

        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return null;
        }

        return date('Y-m-d', $timestamp);
    }

    private function normalizeIntValue($raw): ?int
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        $value = (int) $raw;
        return $value > 0 ? $value : null;
    }

    private function normalizeToggleValue($raw): string
    {
        $value = strtolower(trim((string) ($raw ?? '')));
        return in_array($value, ['1', 'true', 'sim', 'yes', 'on'], true) ? '1' : '';
    }

    private function normalizeDecimalValue($raw): ?float
    {
        $value = trim((string) ($raw ?? ''));
        if ($value === '') {
            return null;
        }

        $normalized = str_replace(['R$', ' '], '', $value);
        if (str_contains($normalized, ',') && str_contains($normalized, '.')) {
            $normalized = str_replace('.', '', $normalized);
            $normalized = str_replace(',', '.', $normalized);
        } elseif (str_contains($normalized, ',')) {
            $normalized = str_replace(',', '.', $normalized);
        }

        if (!is_numeric($normalized)) {
            return null;
        }

        return (float) $normalized;
    }

    public function create()
    {
        $isEmbedded = $this->isEmbedRequest();
        $clienteModel    = new ClienteModel();
        $funcionarioModel = new FuncionarioModel();
        $tipoModel       = new \App\Models\EquipamentoTipoModel();
        $marcaModel      = new \App\Models\EquipamentoMarcaModel();
        $defeitoRelatadoModel = new DefeitoRelatadoModel();

        $origemConversaId = (int) ($this->request->getGet('origem_conversa_id') ?? 0);
        $origemContatoId = (int) ($this->request->getGet('origem_contato_id') ?? 0);
        $clientePreSelecionado = (int) ($this->request->getGet('cliente_id') ?? 0);
        $nomeHint = trim((string) ($this->request->getGet('nome_hint') ?? ''));
        $telefoneHint = preg_replace('/\D+/', '', (string) ($this->request->getGet('telefone') ?? '')) ?? '';

        $origemConversa = null;
        if ($origemConversaId > 0) {
            $conversaModel = new ConversaWhatsappModel();
            if ($conversaModel->db->tableExists('conversas_whatsapp')) {
                $origemConversa = $conversaModel->find($origemConversaId);
                if ($origemConversa) {
                    if ($clientePreSelecionado <= 0) {
                        $clientePreSelecionado = (int) ($origemConversa['cliente_id'] ?? 0);
                    }
                    if ($origemContatoId <= 0) {
                        $origemContatoId = (int) ($origemConversa['contato_id'] ?? 0);
                    }
                    if ($telefoneHint === '') {
                        $telefoneHint = preg_replace('/\D+/', '', (string) ($origemConversa['telefone'] ?? '')) ?? '';
                    }
                    if ($nomeHint === '') {
                        $nomeConversa = trim((string) ($origemConversa['nome_contato'] ?? ''));
                        if ($nomeConversa !== '' && !$this->isLikelyPhoneValue($nomeConversa)) {
                            $nomeHint = $nomeConversa;
                        }
                    }
                }
            }
        }

        if ($origemContatoId > 0) {
            $contatoModel = new ContatoModel();
            if ($contatoModel->db->tableExists('contatos')) {
                $origemContato = $contatoModel->find($origemContatoId);
                if ($origemContato) {
                    if ($clientePreSelecionado <= 0) {
                        $clientePreSelecionado = (int) ($origemContato['cliente_id'] ?? 0);
                    }
                    if ($nomeHint === '') {
                        $nomeHint = trim((string) ($origemContato['nome'] ?? $origemContato['whatsapp_nome_perfil'] ?? ''));
                    }
                    if ($telefoneHint === '') {
                        $telefoneHint = preg_replace('/\D+/', '', (string) ($origemContato['telefone_normalizado'] ?? $origemContato['telefone'] ?? '')) ?? '';
                    }
                }
            }
        }

        $data = [
            'title'    => 'Nova Ordem de Servico',
            'clientes' => $clienteModel->orderBy('nome_razao', 'ASC')->findAll(),
            'tecnicos' => $funcionarioModel->getTecnicos(),
            'tipos'    => $tipoModel->orderBy('nome', 'ASC')->findAll(),
            'marcas'   => $marcaModel->orderBy('nome', 'ASC')->findAll(),
            'relatosRapidos' => $defeitoRelatadoModel->getActiveGrouped(),
            'statusGrouped' => (new OsStatusFlowService())->getStatusGrouped(),
            'statusDefault' => 'triagem',
            'origemConversaId' => $origemConversaId > 0 ? $origemConversaId : null,
            'origemContatoId' => $origemContatoId > 0 ? $origemContatoId : null,
            'origemConversa' => $origemConversa,
            'origemContato' => $origemContato ?? null,
            'clientePreSelecionado' => $clientePreSelecionado > 0 ? $clientePreSelecionado : null,
            'origemNomeHint' => $nomeHint,
            'origemTelefoneHint' => $telefoneHint,
            'checklistEntrada' => null,
            'layout' => $isEmbedded ? 'layouts/embed' : 'layouts/main',
            'isEmbedded' => $isEmbedded,
        ];
        return view('os/form', $data);
    }

    public function store()
    {
        $rules = [
            'cliente_id'     => 'required|integer',
            'equipamento_id' => 'required|integer',
            'relato_cliente' => 'required|min_length[5]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $dados = $this->request->getPost();
        $origemConversaId = (int) ($dados['origem_conversa_id'] ?? 0);
        $origemContatoId = (int) ($dados['origem_contato_id'] ?? 0);
        unset($dados['origem_conversa_id'], $dados['origem_contato_id']);
        $statusFlowService = new OsStatusFlowService();
        $novoStatus = strtolower(trim((string) ($dados['status'] ?? 'triagem')));
        $dados['numero_os']    = $this->model->generateNumeroOs();
        $dados['data_abertura'] = date('Y-m-d H:i:s');
        $dados['status'] = $novoStatus;
        $dados['estado_fluxo'] = $statusFlowService->resolveEstadoFluxo($novoStatus);
        $dados['status_atualizado_em'] = date('Y-m-d H:i:s');

        $this->model->insert($dados);
        $osId = $this->model->getInsertID();

        $historicoModel = new OsStatusHistoricoModel();
        if ($historicoModel->db->tableExists('os_status_historico')) {
            $historicoModel->insert([
                'os_id' => $osId,
                'status_anterior' => null,
                'status_novo' => $novoStatus,
                'estado_fluxo' => $dados['estado_fluxo'],
                'usuario_id' => session()->get('user_id') ?: null,
                'observacao' => 'OS aberta',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        try {
            $crm = new CrmService();
            $crm->registerOsEvent(
                $osId,
                'os_aberta',
                'OS aberta',
                'Ordem de servico aberta no ERP',
                session()->get('user_id') ?: null,
                ['status' => $novoStatus]
            );
        } catch (\Throwable $e) {
            log_message('warning', 'Falha ao registrar evento CRM na abertura da OS: ' . $e->getMessage());
        }

        // Salva defeitos selecionados
        $defeitoIds = $this->request->getPost('defeitos') ?? [];
        if (!empty($defeitoIds)) {
            $defeitoModel = new DefeitoModel();
            $defeitoModel->saveOsDefeitos($osId, $defeitoIds);
        }

        // Salva fotos de estado do equipamento na abertura
        if ($files = $this->request->getFiles()) {
            if (!empty($files['fotos_entrada'])) {
                $fotoOsModel = new \App\Models\OsFotoModel();
                $osNumero = $dados['numero_os'];
                $slug = strtolower(url_title($osNumero, '_', true));

                foreach ($files['fotos_entrada'] as $index => $img) {
                    if ($img->isValid() && !$img->hasMoved()) {
                        $ext = $img->getExtension();
                        $newName = $slug . '_entrada_' . ($index + 1) . '_' . time() . '.' . $ext;
                        $img->move(FCPATH . 'uploads/os_anormalidades', $newName);
                        
                        $fotoOsModel->insert([
                            'os_id'    => $osId,
                            'tipo'     => 'recepcao',
                            'arquivo'  => $newName,
                        ]);
                    }
                }
            }
        }

        $this->persistAccessoryData($osId, $dados['numero_os']);
        $this->persistChecklistEntradaData(
            (int) $osId,
            (string) ($dados['numero_os'] ?? ''),
            (int) ($dados['equipamento_id'] ?? 0)
        );
        $this->triggerAutomaticEventsOnStatus($osId, $novoStatus, session()->get('user_id') ?: null);
        $this->sincronizarOrigemWhatsappNaAbertura(
            $osId,
            (int) ($dados['cliente_id'] ?? 0),
            $origemConversaId,
            $origemContatoId
        );

        LogModel::registrar('os_criada', 'OS criada: ' . $dados['numero_os']);

        return redirect()->to($this->osViewUrl((int) $osId))
            ->with('success', 'Ordem de Serviço ' . $dados['numero_os'] . ' criada com sucesso!');
    }

    public function show($id)
    {
        $isEmbedded = $this->isEmbedRequest();
        $os = $this->model->getComplete($id);
        if (!$os) {
            return redirect()->to('/os')
                ->with('error', 'OS não encontrada.');
        }

        $itemModel = new OsItemModel();
        $defeitoModel = new \App\Models\DefeitoModel();
        $procedimentoModel = new \App\Models\EquipamentoDefeitoProcedimentoModel();

        $defeitos = $defeitoModel->getByOs($id);
        foreach ($defeitos as &$defeito) {
            $defeito['procedimentos'] = $procedimentoModel->getByDefeito($defeito['defeito_id']);
        }

        $acessorioModel = new AcessorioOsModel();
        $fotoAcessorioModel = new FotoAcessorioModel();
        $acessoriosFolder = 'uploads/acessorios/OS_' . $this->normalizeOsSlug($os['numero_os']) . '/';
        $acessorios = $acessorioModel->where('os_id', $id)->orderBy('id', 'ASC')->findAll();
        foreach ($acessorios as &$acessorio) {
            $fotos = $fotoAcessorioModel->where('acessorio_id', $acessorio['id'])->findAll();
            foreach ($fotos as &$foto) {
                $fotoPath = FCPATH . $acessoriosFolder . $foto['arquivo'];
                if (!file_exists($fotoPath)) {
                    $foto = null;
                    continue;
                }
                $foto['url'] = base_url($acessoriosFolder . $foto['arquivo']);
            }
            $acessorio['fotos'] = array_values(array_filter($fotos));
        }

        $fotos_equip = $this->getEquipamentoFotosWithUrls((int) ($os['equipamento_id'] ?? 0));
        $fotos_entrada = $this->getOsEntradaFotosWithUrls((int) $id);
        $checklistEntrada = $this->resolveChecklistEntradaPayloadForOs((int) $id, $os);
        $statusFlowService = new OsStatusFlowService();
        $statusGrouped = $statusFlowService->getStatusGrouped();
        $statusOptions = $statusFlowService->buildTransitionHints((string) ($os['status'] ?? ''));
        $statusHistorico = ((new OsStatusHistoricoModel())->db->tableExists('os_status_historico'))
            ? (new OsStatusHistoricoModel())->byOs((int) $id)
            : [];
        $notasLegadas = ((new OsNotaLegadaModel())->db->tableExists('os_notas_legadas'))
            ? (new OsNotaLegadaModel())
                ->where('os_id', (int) $id)
                ->orderBy('created_at', 'DESC')
                ->orderBy('id', 'DESC')
                ->findAll()
            : [];

        $itens = $itemModel->getByOs($id);
        $legacyFinancialOrigins = array_values(array_filter(array_map(
            static function (array $item): ?array {
                $legacyTabela = trim((string) ($item['legacy_tabela'] ?? ''));
                if (! in_array($legacyTabela, ['os_totais_servico', 'os_totais_peca', 'os_totais_consolidado'], true)) {
                    return null;
                }

                return [
                    'descricao' => trim((string) ($item['descricao'] ?? 'Valor legado importado')),
                    'observacao' => trim((string) ($item['observacao'] ?? '')),
                    'valor_total' => (float) ($item['valor_total'] ?? 0),
                    'legacy_tabela' => $legacyTabela,
                ];
            },
            $itens
        )));

        $data = [
            'title'          => 'OS ' . $os['numero_os'],
            'os'             => $os,
            'itens'          => $itens,
            'legacyFinancialOrigins' => $legacyFinancialOrigins,
            'defeitos'       => $defeitos,
            'fotos_equip'    => $fotos_equip,
            'fotos_entrada'  => $fotos_entrada,
            'acessorios'     => $acessorios,
            'acessorios_folder' => $acessoriosFolder,
            'checklist_entrada' => $checklistEntrada,
            'statusGrouped' => $statusGrouped,
            'statusOptions' => $statusOptions,
            'statusHistorico' => $statusHistorico,
            'notasLegadas' => $notasLegadas,
            'workflowTimeline' => $this->buildOsWorkflowTimeline(
                $statusGrouped,
                $statusHistorico,
                (string) ($os['status'] ?? ''),
                $statusOptions
            ),
            'workflowRecentHistory' => array_slice($statusHistorico, 0, 4),
            'primaryNextStatus' => $this->resolvePrimaryNextStatus(
                $statusFlowService,
                (string) ($os['status'] ?? ''),
                $statusOptions
            ),
            'whatsappTemplates' => (new WhatsAppService())->getTemplates(),
            'whatsappLogs' => ((new MensagemWhatsappModel())->db->tableExists('mensagens_whatsapp'))
                ? (new MensagemWhatsappModel())->byOs((int) $id, 100)
                : (((new WhatsappEnvioModel())->db->tableExists('whatsapp_envios'))
                    ? (new WhatsappEnvioModel())->byOs((int) $id, 100)
                    : (((new WhatsappMensagemModel())->db->tableExists('whatsapp_mensagens'))
                        ? (new WhatsappMensagemModel())->byOs((int) $id, 50)
                        : [])),
            'documentosOs' => ((new OsDocumentoModel())->db->tableExists('os_documentos'))
                ? (new OsDocumentoModel())->byOs((int) $id)
                : [],
            'pdfTipos' => (new OsPdfService())->tiposDisponiveis(),
            'layout' => $isEmbedded ? 'layouts/embed' : 'layouts/main',
            'isEmbedded' => $isEmbedded,
        ];
        return view('os/show', $data);
    }

    private function resolvePrimaryNextStatus(
        OsStatusFlowService $statusFlowService,
        string $currentStatus,
        array $statusOptions
    ): ?array {
        $candidates = array_values(array_filter($statusOptions, static function (array $status) use ($currentStatus): bool {
            $code = trim((string) ($status['codigo'] ?? ''));
            return $code !== '' && $code !== $currentStatus && $code !== 'cancelado';
        }));

        if (empty($candidates)) {
            return null;
        }

        $orderByCode = [];
        foreach ($statusFlowService->getAllStatusesOrdered() as $status) {
            $code = trim((string) ($status['codigo'] ?? ''));
            if ($code === '') {
                continue;
            }
            $orderByCode[$code] = (int) ($status['ordem_fluxo'] ?? 0);
        }

        $currentOrder = $orderByCode[$currentStatus] ?? null;

        usort($candidates, static function (array $a, array $b) use ($orderByCode): int {
            $codeA = trim((string) ($a['codigo'] ?? ''));
            $codeB = trim((string) ($b['codigo'] ?? ''));
            $orderA = $orderByCode[$codeA] ?? (int) ($a['ordem_fluxo'] ?? PHP_INT_MAX);
            $orderB = $orderByCode[$codeB] ?? (int) ($b['ordem_fluxo'] ?? PHP_INT_MAX);

            if ($orderA === $orderB) {
                return strcmp(
                    (string) ($a['nome'] ?? $codeA),
                    (string) ($b['nome'] ?? $codeB)
                );
            }

            return $orderA <=> $orderB;
        });

        if ($currentOrder !== null) {
            foreach ($candidates as $candidate) {
                $code = trim((string) ($candidate['codigo'] ?? ''));
                $candidateOrder = $orderByCode[$code] ?? (int) ($candidate['ordem_fluxo'] ?? PHP_INT_MAX);
                if ($candidateOrder > $currentOrder) {
                    return $candidate;
                }
            }
        }

        return $candidates[0] ?? null;
    }

    private function buildOsWorkflowTimeline(
        array $statusGrouped,
        array $statusHistorico,
        string $currentStatus,
        array $statusOptions = []
    ): array {
        if (empty($statusGrouped)) {
            return [];
        }

        $statusMetaByCode = [];
        $statusMacroByCode = [];
        $groupKeys = array_keys($statusGrouped);
        $currentMacro = null;

        foreach ($statusGrouped as $macro => $items) {
            foreach ($items as $item) {
                $code = trim((string) ($item['codigo'] ?? ''));
                if ($code === '') {
                    continue;
                }

                $statusMetaByCode[$code] = $item;
                $statusMacroByCode[$code] = $macro;

                if ($code === $currentStatus) {
                    $currentMacro = $macro;
                }
            }
        }

        $visitedCodes = [];
        $latestEventByMacro = [];

        foreach ($statusHistorico as $entry) {
            $code = trim((string) ($entry['status_novo'] ?? ''));
            if ($code === '') {
                continue;
            }

            $visitedCodes[$code] = true;
            $macro = $statusMacroByCode[$code] ?? null;
            if ($macro !== null && !isset($latestEventByMacro[$macro])) {
                $latestEventByMacro[$macro] = $entry;
            }
        }

        if ($currentStatus !== '') {
            $visitedCodes[$currentStatus] = true;
        }

        $nextCodes = [];
        foreach ($statusOptions as $statusOption) {
            $code = trim((string) ($statusOption['codigo'] ?? ''));
            if ($code !== '' && $code !== $currentStatus) {
                $nextCodes[$code] = true;
            }
        }

        $currentIndex = $currentMacro !== null ? array_search($currentMacro, $groupKeys, true) : false;
        $timeline = [];

        foreach ($statusGrouped as $macro => $items) {
            $macroIndex = array_search($macro, $groupKeys, true);
            $codes = [];
            $nextStatusNames = [];
            $containsCurrent = false;

            foreach ($items as $item) {
                $code = trim((string) ($item['codigo'] ?? ''));
                if ($code === '') {
                    continue;
                }

                $codes[] = $code;

                if ($code === $currentStatus) {
                    $containsCurrent = true;
                }

                if (isset($nextCodes[$code])) {
                    $nextStatusNames[] = (string) ($item['nome'] ?? $code);
                }
            }

            $visitedInGroup = array_values(array_filter($codes, static fn (string $code): bool => isset($visitedCodes[$code])));
            $state = 'upcoming';

            if ($containsCurrent) {
                $state = 'current';
            } elseif (!empty($visitedInGroup) && ($currentIndex === false || ($macroIndex !== false && $macroIndex < $currentIndex))) {
                $state = 'completed';
            } elseif (!empty($nextStatusNames)) {
                $state = 'probable';
            } elseif (!empty($visitedInGroup)) {
                $state = 'completed';
            }

            $latestEntry = $latestEventByMacro[$macro] ?? null;
            $currentMeta = $containsCurrent ? ($statusMetaByCode[$currentStatus] ?? null) : null;

            $timeline[] = [
                'key' => (string) $macro,
                'label' => ucwords(str_replace('_', ' ', (string) $macro)),
                'state' => $state,
                'current_status_name' => $containsCurrent
                    ? (string) ($currentMeta['nome'] ?? ucfirst(str_replace('_', ' ', $currentStatus)))
                    : '',
                'last_status_name' => $latestEntry
                    ? (string) (($statusMetaByCode[$latestEntry['status_novo'] ?? '']['nome'] ?? null) ?: ucfirst(str_replace('_', ' ', (string) ($latestEntry['status_novo'] ?? ''))))
                    : '',
                'last_event_at' => $latestEntry['created_at'] ?? null,
                'last_user_name' => $latestEntry['usuario_nome'] ?? null,
                'next_status_names' => array_values(array_unique($nextStatusNames)),
            ];
        }

        return $timeline;
    }

    public function edit($id)
    {
        $isEmbedded = $this->isEmbedRequest();
        $os = $this->model->getComplete($id);
        if (!$os) {
            return redirect()->to('/os')
                ->with('error', 'OS não encontrada.');
        }

        $clienteModel = new ClienteModel();
        $equipamentoModel = new EquipamentoModel();
        $funcionarioModel = new FuncionarioModel();
        $itemModel = new OsItemModel();
        $defeitoRelatadoModel = new DefeitoRelatadoModel();

        // Fotos da OS (entrada)
        $fotoOsModel = new OsFotoModel();
        $fotos_entrada = $fotoOsModel->where('os_id', $id)->where('tipo', 'recepcao')->findAll();
        foreach ($fotos_entrada as &$f) {
            $f['url'] = $this->resolveOsEntradaFotoPublicUrl((string) ($f['arquivo'] ?? ''));
        }

        $checklistEntrada = $this->resolveChecklistEntradaPayloadForOs((int) $id, $os);

        $data = [
            'title'        => 'Editar OS ' . $os['numero_os'],
            'os'           => $os,
            'clientes'     => $clienteModel->orderBy('nome_razao', 'ASC')->findAll(),
            'equipamentos' => $equipamentoModel->getByCliente($os['cliente_id']),
            'tecnicos'     => $funcionarioModel->getTecnicos(),
            'itens'        => $itemModel->getByOs($id),
            'defeitosSelected' => (new DefeitoModel())->getByOs($id),
            'fotos_entrada'    => $fotos_entrada,
            'relatosRapidos'   => $defeitoRelatadoModel->getActiveGrouped(),
            'checklistEntrada' => $checklistEntrada,
            'statusGrouped' => (new OsStatusFlowService())->getStatusGrouped(),
            'statusDefault' => (string) ($os['status'] ?? 'triagem'),
            'layout' => $isEmbedded ? 'layouts/embed' : 'layouts/main',
            'isEmbedded' => $isEmbedded,
        ];
        return view('os/form', $data);
    }

    public function update($id)
    {
        $dados = $this->request->getPost();
        $osAnterior = $this->model->find($id);
        $statusNovo = strtolower(trim((string) ($dados['status'] ?? '')));
        $statusAlterado = $statusNovo !== '' && !empty($osAnterior) && $statusNovo !== (string) ($osAnterior['status'] ?? '');
        $statusService = new OsStatusFlowService();
        if ($statusAlterado && !$statusService->isTransitionAllowed((string) ($osAnterior['status'] ?? ''), $statusNovo)) {
            return redirect()->to($this->osEditUrl((int) $id))
                ->withInput()
                ->with('error', 'Transicao de status invalida para esta OS.');
        }
        if ($statusAlterado) {
            unset($dados['status']);
        }
        
        // Calculate totals
        if (isset($dados['valor_mao_obra']) || isset($dados['valor_pecas'])) {
            $maoObra = (float)($dados['valor_mao_obra'] ?? 0);
            $pecas = (float)($dados['valor_pecas'] ?? 0);
            $desconto = (float)($dados['desconto'] ?? 0);
            $dados['valor_total'] = $maoObra + $pecas;
            $dados['valor_final'] = $dados['valor_total'] - $desconto;
        }

        $this->model->update($id, $dados);

        if ($statusAlterado) {
            $statusService->applyStatus(
                (int) $id,
                $statusNovo,
                session()->get('user_id') ?: null,
                'Alterado na edicao da OS'
            );
            $this->triggerAutomaticEventsOnStatus((int) $id, $statusNovo, session()->get('user_id') ?: null);
        }
        
        // Salva novas fotos de estado do equipamento
        if ($files = $this->request->getFiles()) {
            if (!empty($files['fotos_entrada'])) {
                $fotoOsModel = new \App\Models\OsFotoModel();
                $osObj = $this->model->find($id);
                $slug = strtolower(url_title($osObj['numero_os'], '_', true));

                foreach ($files['fotos_entrada'] as $index => $img) {
                    if ($img && $img->isValid() && !$img->hasMoved()) {
                        $ext = $img->getExtension();
                        $newName = $slug . '_edit_' . ($index + 1) . '_' . time() . '.' . $ext;
                        $img->move(FCPATH . 'uploads/os_anormalidades', $newName);
                        
                        $fotoOsModel->insert([
                            'os_id'    => $id,
                            'tipo'     => 'recepcao',
                            'arquivo'  => $newName,
                        ]);
                    }
                }
            }
        }

        // Salva defeitos selecionados
        $defeitoIds = $this->request->getPost('defeitos') ?? [];
        $defeitoModel = new DefeitoModel();
        $defeitoModel->saveOsDefeitos($id, $defeitoIds);

        $osRecord = $this->model->find($id);
        if ($osRecord) {
            $this->persistAccessoryData($id, $osRecord['numero_os'], true);
            $this->persistChecklistEntradaData(
                (int) $id,
                (string) ($osRecord['numero_os'] ?? ''),
                (int) ($osRecord['equipamento_id'] ?? 0)
            );
        }

        LogModel::registrar('os_atualizada', 'OS atualizada ID: ' . $id);

        return redirect()->to($this->osViewUrl((int) $id))
            ->with('success', 'OS atualizada com sucesso!');
    }

    public function updateStatus($id)
    {
        $status = strtolower(trim((string) $this->request->getPost('status')));
        $observacao = trim((string) $this->request->getPost('observacao_status'));
        $controlaComunicacaoCliente = (string) ($this->request->getPost('controla_comunicacao_cliente') ?? '') === '1';
        $comunicarCliente = $controlaComunicacaoCliente && !empty($this->request->getPost('comunicar_cliente'));
        $os = $this->model->find($id);

        if (!$os) {
            return redirect()->to('/os')->with('error', 'OS nao encontrada.');
        }

        $statusService = new OsStatusFlowService();
        $result = $statusService->applyStatus(
            (int) $id,
            $status,
            session()->get('user_id') ?: null,
            $observacao !== '' ? $observacao : null
        );

        if (empty($result['ok'])) {
            return redirect()->to($this->osViewUrl((int) $id))
                ->with('error', $result['message'] ?? 'Nao foi possivel atualizar o status.');
        }

        $this->finalizeStatusSideEffects((int) $id, $os, $status, !$controlaComunicacaoCliente);

        $warningMessage = null;
        if ($comunicarCliente) {
            $notifyResult = $this->sendStatusChangeNotification(
                (int) $id,
                $status,
                $observacao !== '' ? $observacao : null,
                session()->get('user_id') ?: null
            );

            if (empty($notifyResult['ok'])) {
                $warningMessage = $notifyResult['message'] ?? 'O status foi atualizado, mas nao foi possivel comunicar o cliente.';
            }
        }

        LogModel::registrar('os_status', 'Status da OS ' . $os['numero_os'] . ' alterado para: ' . $status);

        $redirect = redirect()->to($this->osViewUrl((int) $id))
            ->with('success', 'Status atualizado com sucesso!');

        if ($warningMessage !== null) {
            $redirect = $redirect->with('warning', $warningMessage);
        }

        return $redirect;
    }

    private function finalizeStatusSideEffects(int $id, array $os, string $status, bool $allowTemplateCommunication = true): void
    {
        if (in_array($status, ['entregue_reparado', 'entregue_pagamento_pendente'], true)) {
            $osAtualizada = $this->model->find($id);
            if (!empty($osAtualizada['valor_final']) && (float) $osAtualizada['valor_final'] > 0) {
                $finModel = new FinanceiroModel();
                $exists = $finModel
                    ->where('os_id', $id)
                    ->where('tipo', 'receber')
                    ->countAllResults();
                if ($exists === 0) {
                    $finModel->insert([
                        'os_id'           => $id,
                        'tipo'            => 'receber',
                        'categoria'       => 'Servico',
                        'descricao'       => 'OS ' . (($osAtualizada['numero_os'] ?? '') ?: ($os['numero_os'] ?? '')),
                        'valor'           => $osAtualizada['valor_final'],
                        'status'          => 'pendente',
                        'data_vencimento' => date('Y-m-d'),
                    ]);
                }
            }
        }

        if ($status === 'aguardando_reparo') {
            $this->model->update($id, [
                'orcamento_aprovado' => 1,
                'data_aprovacao' => date('Y-m-d H:i:s'),
            ]);
        }

        $this->triggerAutomaticEventsOnStatus(
            $id,
            $status,
            session()->get('user_id') ?: null,
            $allowTemplateCommunication
        );
    }

    private function sendStatusChangeNotification(int $osId, string $statusCode, ?string $observacao = null, ?int $userId = null): array
    {
        $os = $this->model->getComplete($osId);
        if (!$os) {
            return [
                'ok' => false,
                'message' => 'A OS nao foi encontrada para envio da notificacao.',
            ];
        }

        $telefone = trim((string) ($os['cliente_telefone'] ?? ''));
        if ($telefone === '') {
            return [
                'ok' => false,
                'message' => 'O status foi atualizado, mas o cliente nao possui telefone cadastrado para notificacao.',
            ];
        }

        $statusNome = $this->humanizeOsStatus($statusCode);
        $mensagem = $statusCode === 'cancelado'
            ? 'Atualizacao da sua OS ' . ($os['numero_os'] ?? '') . ': o atendimento foi cancelado conforme solicitado.'
            : 'Atualizacao da sua OS ' . ($os['numero_os'] ?? '') . ': novo status "' . $statusNome . '".';

        if ($observacao !== null && trim($observacao) !== '') {
            $mensagem .= "\nObservacoes: " . trim($observacao);
        }

        return (new WhatsAppService())->sendRaw(
            $osId,
            (int) ($os['cliente_id'] ?? 0),
            $telefone,
            $mensagem,
            'status_manual',
            null,
            $userId
        );
    }

    private function humanizeOsStatus(string $statusCode): string
    {
        $statusCode = strtolower(trim($statusCode));
        if ($statusCode === '') {
            return 'Status atualizado';
        }

        $status = (new OsStatusFlowService())->getStatusByCode($statusCode);
        if (!empty($status['nome'])) {
            return (string) $status['nome'];
        }

        return ucwords(str_replace('_', ' ', $statusCode));
    }

    public function sendWhatsApp($id)
    {
        $os = $this->model->getComplete((int) $id);
        if (!$os) {
            return redirect()->to('/os')->with('error', 'OS nao encontrada.');
        }

        $telefone = trim((string) ($this->request->getPost('telefone') ?: ($os['cliente_telefone'] ?? '')));
        if ($telefone === '') {
            return redirect()->to($this->osViewUrl((int) $id))->with('error', 'Cliente sem telefone para envio.');
        }

        $templateCode = trim((string) $this->request->getPost('template_codigo'));
        $mensagem = trim((string) $this->request->getPost('mensagem_manual'));
        $documentoId = (int) ($this->request->getPost('documento_id') ?? 0);
        $whatsService = new WhatsAppService();
        $os['cliente_telefone'] = $telefone;
        $pdfUrl = '';
        $pdfPath = '';
        $pdfRelative = '';

        if ($documentoId > 0) {
            $doc = (new OsDocumentoModel())
                ->where('id', $documentoId)
                ->where('os_id', (int) $id)
                ->first();
            if ($doc && !empty($doc['arquivo'])) {
                $pdfRelative = (string) $doc['arquivo'];
                $pdfUrl = base_url($pdfRelative);
                $candidatePath = FCPATH . ltrim($pdfRelative, '/\\');
                if (is_file($candidatePath)) {
                    $pdfPath = $candidatePath;
                }
            }
        }

        if ($mensagem !== '') {
            $result = $whatsService->sendRaw(
                (int) $id,
                (int) ($os['cliente_id'] ?? 0),
                $telefone,
                $mensagem,
                'manual',
                null,
                session()->get('user_id') ?: null,
                [
                    'arquivo_path' => $pdfPath,
                    'arquivo' => $pdfRelative,
                ]
            );
        } else {
            if ($templateCode === '') {
                return redirect()->to($this->osViewUrl((int) $id))->with('error', 'Selecione um template ou informe uma mensagem manual.');
            }
            $extra = [];
            if ($pdfUrl !== '') {
                $extra['pdf_url'] = $pdfUrl;
            }
            if ($pdfPath !== '') {
                $extra['arquivo_path'] = $pdfPath;
                $extra['arquivo'] = $pdfRelative;
            }
            $result = $whatsService->sendByTemplate($os, $templateCode, session()->get('user_id') ?: null, $extra);
        }

        if (!empty($result['ok'])) {
            return redirect()->to($this->osViewUrl((int) $id))->with('success', 'Mensagem WhatsApp enviada com sucesso.');
        }

        return redirect()->to($this->osViewUrl((int) $id))->with('error', $result['message'] ?? 'Falha ao enviar mensagem no WhatsApp.');
    }

    public function generatePdf($id)
    {
        $os = $this->model->find((int) $id);
        if (!$os) {
            return redirect()->to('/os')->with('error', 'OS nao encontrada.');
        }

        $tipo = trim((string) $this->request->getPost('tipo_documento'));
        if ($tipo === '') {
            return redirect()->to($this->osViewUrl((int) $id))->with('error', 'Tipo de documento nao informado.');
        }

        $pdfService = new OsPdfService();
        $result = $pdfService->gerar((int) $id, $tipo, session()->get('user_id') ?: null);
        if (empty($result['ok'])) {
            return redirect()->to($this->osViewUrl((int) $id))->with('error', $result['message'] ?? 'Falha ao gerar PDF.');
        }

        return redirect()->to($this->osViewUrl((int) $id))->with('success', 'PDF gerado com sucesso.');
    }

    private function triggerAutomaticEventsOnStatus(
        int $osId,
        string $statusCode,
        ?int $userId = null,
        bool $allowTemplateCommunication = true
    ): void
    {
        $statusCode = strtolower(trim($statusCode));
        try {
            (new CrmService())->applyStatusAutomation($osId, $statusCode, $userId, $allowTemplateCommunication);
        } catch (\Throwable $e) {
            log_message('warning', 'Falha ao aplicar automacoes CRM para OS ' . $osId . ': ' . $e->getMessage());
        }
    }

    private function sincronizarOrigemWhatsappNaAbertura(int $osId, int $clienteId, int $conversaId = 0, int $contatoId = 0): void
    {
        if ($osId <= 0 || $clienteId <= 0) {
            return;
        }

        try {
            $db = \Config\Database::connect();
            $contatoModel = new ContatoModel();
            $conversaModel = new ConversaWhatsappModel();

            if ($contatoId > 0 && $db->tableExists('contatos')) {
                $contato = $contatoModel->find($contatoId);
                if ($contato && (int) ($contato['cliente_id'] ?? 0) <= 0) {
                    $contatoModel->update(
                        $contatoId,
                        $contatoModel->buildClienteConvertidoPayload($clienteId, [
                            'ultimo_contato_em' => date('Y-m-d H:i:s'),
                        ])
                    );
                }
            }

            if ($conversaId > 0 && $db->tableExists('conversas_whatsapp')) {
                $updates = ['cliente_id' => $clienteId];

                $conversa = $conversaModel->find($conversaId);
                if ($contatoId <= 0) {
                    $contatoId = (int) ($conversa['contato_id'] ?? 0);
                }

                if ($contatoId > 0 && $db->fieldExists('contato_id', 'conversas_whatsapp')) {
                    $updates['contato_id'] = $contatoId;
                }

                $conversaModel->update($conversaId, $updates);
                (new CentralMensagensService())->bindOsToConversa($conversaId, $osId, true);

                if ($contatoId > 0 && $db->tableExists('contatos')) {
                    $contato = $contatoModel->find($contatoId);
                    if ($contato && (int) ($contato['cliente_id'] ?? 0) <= 0) {
                        $contatoModel->update(
                            $contatoId,
                            $contatoModel->buildClienteConvertidoPayload($clienteId, [
                                'ultimo_contato_em' => date('Y-m-d H:i:s'),
                            ])
                        );
                    }
                }
            }
        } catch (\Throwable $e) {
            log_message('warning', 'Falha ao sincronizar origem WhatsApp na abertura da OS ' . $osId . ': ' . $e->getMessage());
        }
    }

    private function isLikelyPhoneValue(string $value): bool
    {
        $raw = trim($value);
        if ($raw === '') {
            return false;
        }
        $digits = preg_replace('/\D+/', '', $raw) ?? '';
        if ($digits === '') {
            return false;
        }
        $nonDigits = preg_replace('/[0-9+\-().\s]/', '', $raw) ?? '';
        return strlen($digits) >= 8 && strlen($nonDigits) <= 2;
    }

    public function addItem()
    {
        $itemModel = new OsItemModel();
        $dados = $this->request->getPost();
        
        $dados['valor_total'] = $dados['quantidade'] * $dados['valor_unitario'];
        $itemModel->insert($dados);

        // If it's a part, update stock
        if ($dados['tipo'] === 'peca' && !empty($dados['peca_id'])) {
            $pecaModel = new PecaModel();
            $peca = $pecaModel->find($dados['peca_id']);
            if ($peca) {
                $pecaModel->update($dados['peca_id'], [
                    'quantidade_atual' => $peca['quantidade_atual'] - $dados['quantidade']
                ]);
                
                // Register movement
                $movModel = new MovimentacaoModel();
                $movModel->insert([
                    'peca_id'        => $dados['peca_id'],
                    'os_id'          => $dados['os_id'],
                    'tipo'           => 'saida',
                    'quantidade'     => $dados['quantidade'],
                    'motivo'         => 'Consumo em OS',
                    'responsavel_id' => session()->get('user_id'),
                ]);
            }
        }

        // Update OS totals
        $this->recalcularTotaisOs($dados['os_id']);

        return redirect()->to($this->osViewUrl((int) $dados['os_id']))
            ->with('success', 'Item adicionado com sucesso!');
    }

    public function removeItem($id)
    {
        $itemModel = new OsItemModel();
        $item = $itemModel->find($id);
        
        if ($item) {
            $osId = $item['os_id'];
            
            // Reverse stock if it's a part
            if ($item['tipo'] === 'peca' && !empty($item['peca_id'])) {
                $pecaModel = new PecaModel();
                $peca = $pecaModel->find($item['peca_id']);
                if ($peca) {
                    $pecaModel->update($item['peca_id'], [
                        'quantidade_atual' => $peca['quantidade_atual'] + $item['quantidade']
                    ]);
                }
            }
            
            $itemModel->delete($id);
            $this->recalcularTotaisOs($osId);

            return redirect()->to($this->osViewUrl((int) $osId))
                ->with('success', 'Item removido com sucesso!');
        }

        return redirect()->back()->with('error', 'Item não encontrado.');
    }

    private function recalcularTotaisOs($osId)
    {
        $itemModel = new OsItemModel();
        $db = \Config\Database::connect();

        $servicos = $db->table('os_itens')
            ->selectSum('valor_total')
            ->where('os_id', $osId)
            ->where('tipo', 'servico')
            ->get()->getRow()->valor_total ?? 0;

        $pecas = $db->table('os_itens')
            ->selectSum('valor_total')
            ->where('os_id', $osId)
            ->where('tipo', 'peca')
            ->get()->getRow()->valor_total ?? 0;

        $os = $this->model->find($osId);
        $desconto = $os['desconto'] ?? 0;
        $total = (float)$servicos + (float)$pecas;

        $this->model->update($osId, [
            'valor_mao_obra' => (float)$servicos,
            'valor_pecas'    => (float)$pecas,
            'valor_total'    => $total,
            'valor_final'    => $total - $desconto,
        ]);
    }

    private function persistAccessoryData(int $osId, string $numeroOs, bool $replaceExisting = false): void
    {
        $entries = $this->getAccessoryEntries();
        $filesMap = $this->collectAccessoryFiles();

        if ($replaceExisting) {
            (new AcessorioOsModel())->deleteByOs($osId);
            $this->clearAccessoryFolder($numeroOs);
        }

        if (empty($entries) && empty($filesMap)) {
            return;
        }

        $acessorioModel = new AcessorioOsModel();
        $fotoModel = new FotoAcessorioModel();
        $slug = $this->normalizeOsSlug($numeroOs);
        $folder = $this->ensureAccessoryDirectory($slug);
        $sequence = 1;

        foreach ($entries as $entry) {
            $description = trim($entry['text'] ?? '');
            if ($description === '') {
                continue;
            }

            $acessorioModel->insert([
                'os_id' => $osId,
                'descricao' => $description,
                'tipo' => $entry['key'] ?? null,
                'valores' => !empty($entry['values']) ? json_encode($entry['values'], JSON_UNESCAPED_UNICODE) : null,
            ]);

            $acessorioId = $acessorioModel->getInsertID();
            if (!$acessorioId) {
                continue;
            }

            $entryFiles = $filesMap[$entry['id']] ?? [];
            foreach ($entryFiles as $file) {
                $this->saveAccessoryPhoto($file, $folder, $slug, $sequence, $acessorioId, $fotoModel);
            }
        }
    }

    public function checklistMeta()
    {
        if (!$this->isChecklistInfraReady()) {
            return $this->response->setJSON([
                'ok' => true,
                'data' => $this->buildChecklistEntradaUnavailablePayload(),
            ]);
        }

        $equipamentoId = (int) ($this->request->getGet('equipamento_id') ?? 0);
        $osId = (int) ($this->request->getGet('os_id') ?? 0);

        if ($equipamentoId <= 0) {
            return $this->response->setStatusCode(422)->setJSON([
                'ok' => false,
                'message' => 'Selecione um equipamento valido para carregar o checklist.',
            ]);
        }

        $equipamento = (new EquipamentoModel())
            ->select('equipamentos.id, equipamentos.tipo_id, tipos.nome AS tipo_nome')
            ->join('equipamentos_tipos tipos', 'tipos.id = equipamentos.tipo_id', 'left')
            ->where('equipamentos.id', $equipamentoId)
            ->first();

        if (!$equipamento) {
            return $this->response->setStatusCode(404)->setJSON([
                'ok' => false,
                'message' => 'Equipamento nao encontrado.',
            ]);
        }

        $tipoEquipamentoId = (int) ($equipamento['tipo_id'] ?? 0);
        if ($tipoEquipamentoId <= 0) {
            return $this->response->setStatusCode(422)->setJSON([
                'ok' => false,
                'message' => 'Tipo de equipamento nao identificado para o checklist.',
            ]);
        }

        $numeroOs = '';
        if ($osId > 0) {
            $os = $this->model->select('id, numero_os')->find($osId);
            $numeroOs = (string) ($os['numero_os'] ?? '');
        }

        try {
            $payload = (new ChecklistService())->getPayloadForOs(
                $osId,
                'entrada',
                $tipoEquipamentoId,
                $numeroOs
            );

            $payload['tipo_equipamento_nome'] = trim((string) ($equipamento['tipo_nome'] ?? ''));

            return $this->response->setJSON([
                'ok' => true,
                'data' => $payload,
            ]);
        } catch (\Throwable $e) {
            log_message('error', '[Checklist] falha no checklistMeta da OS: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'ok' => false,
                'message' => 'Nao foi possivel carregar o checklist agora.',
            ]);
        }
    }

    private function getAccessoryEntries(): array
    {
        $raw = $this->request->getPost('acessorios_data');
        if (empty($raw)) {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        return array_values(array_filter($decoded, function ($entry) {
            return !empty(trim($entry['text'] ?? ''));
        }));
    }

    private function collectAccessoryFiles(): array
    {
        $mapped = [];
        if (empty($_FILES['fotos_acessorios']['name'] ?? null)) {
            return $mapped;
        }

        foreach ($_FILES['fotos_acessorios']['name'] as $entryId => $files) {
            foreach ($files as $index => $name) {
                $error = $_FILES['fotos_acessorios']['error'][$entryId][$index] ?? UPLOAD_ERR_NO_FILE;
                if ($error !== UPLOAD_ERR_OK) {
                    continue;
                }

                $tmpName = $_FILES['fotos_acessorios']['tmp_name'][$entryId][$index];
                if (!is_uploaded_file($tmpName)) {
                    continue;
                }

                $mapped[$entryId][] = [
                    'name'     => $name,
                    'tmp_name' => $tmpName,
                ];
            }
        }

        return $mapped;
    }

    private function resolveChecklistEntradaPayloadForOs(int $osId, array $os): ?array
    {
        $equipamentoId = (int) ($os['equipamento_id'] ?? 0);
        if ($equipamentoId <= 0) {
            return null;
        }

        if (!$this->isChecklistInfraReady()) {
            return null;
        }

        $equipamento = (new EquipamentoModel())
            ->select('equipamentos.id, equipamentos.tipo_id, tipos.nome AS tipo_nome')
            ->join('equipamentos_tipos tipos', 'tipos.id = equipamentos.tipo_id', 'left')
            ->where('equipamentos.id', $equipamentoId)
            ->first();

        if (!$equipamento) {
            return null;
        }

        $tipoEquipamentoId = (int) ($equipamento['tipo_id'] ?? 0);
        if ($tipoEquipamentoId <= 0) {
            return null;
        }

        try {
            $payload = (new ChecklistService())->getPayloadForOs(
                $osId,
                'entrada',
                $tipoEquipamentoId,
                (string) ($os['numero_os'] ?? '')
            );

            $payload['tipo_equipamento_nome'] = trim((string) ($equipamento['tipo_nome'] ?? ''));
            return $payload;
        } catch (\Throwable $e) {
            log_message('error', '[Checklist] Falha ao montar payload da OS ' . $osId . ': ' . $e->getMessage());
            return null;
        }
    }

    private function persistChecklistEntradaData(int $osId, string $numeroOs, int $equipamentoId): void
    {
        if (!$this->isChecklistInfraReady()) {
            return;
        }

        $rawPayload = trim((string) ($this->request->getPost('checklist_entrada_data') ?? ''));
        $filesByItem = $this->collectChecklistFilesForOs();

        if ($rawPayload === '' && empty($filesByItem)) {
            return;
        }

        if ($equipamentoId <= 0) {
            log_message('warning', '[Checklist] OS ' . $osId . ' sem equipamento valido para salvar checklist.');
            return;
        }

        $equipamento = (new EquipamentoModel())
            ->select('equipamentos.id, equipamentos.tipo_id, tipos.nome AS tipo_nome')
            ->join('equipamentos_tipos tipos', 'tipos.id = equipamentos.tipo_id', 'left')
            ->where('equipamentos.id', $equipamentoId)
            ->first();

        $tipoEquipamentoId = (int) ($equipamento['tipo_id'] ?? 0);
        if ($tipoEquipamentoId <= 0) {
            log_message('warning', '[Checklist] OS ' . $osId . ' com tipo de equipamento invalido.');
            return;
        }

        $payload = [];
        if ($rawPayload !== '') {
            $decoded = json_decode($rawPayload, true);
            if (is_array($decoded)) {
                $payload = $decoded;
            }
        }
        $payload['tipo_equipamento_nome'] = trim((string) ($equipamento['tipo_nome'] ?? ''));

        try {
            (new ChecklistService())->saveExecution(
                $osId,
                $numeroOs,
                'entrada',
                $tipoEquipamentoId,
                $payload,
                $filesByItem
            );
        } catch (\Throwable $e) {
            log_message('error', '[Checklist] falha ao salvar checklist de entrada da OS ' . $osId . ': ' . $e->getMessage());
        }
    }

    /**
     * @return array<string,mixed>
     */
    private function buildChecklistEntradaUnavailablePayload(): array
    {
        return [
            'tipo' => [
                'codigo' => 'entrada',
                'nome' => 'Checklist de Entrada',
            ],
            'modelo' => null,
            'numero_os' => '',
            'possui_modelo' => false,
            'execucao' => null,
            'itens' => [],
            'resumo' => [
                'preenchido' => false,
                'total_discrepancias' => 0,
                'label' => 'Checklist indisponivel',
                'variant' => 'secondary',
            ],
            'tipo_equipamento_nome' => '',
        ];
    }

    private function isChecklistInfraReady(): bool
    {
        try {
            $db = Database::connect();
            foreach ([
                'checklist_tipos',
                'checklist_modelos',
                'checklist_itens',
                'checklist_execucoes',
                'checklist_respostas',
                'checklist_fotos',
            ] as $table) {
                if (!$db->tableExists($table)) {
                    return false;
                }
            }

            return true;
        } catch (\Throwable $e) {
            log_message('error', '[Checklist] Falha ao validar infraestrutura no modulo OS: ' . $e->getMessage());
            return false;
        }
    }

    private function collectChecklistFilesForOs(): array
    {
        $mapped = [];
        if (empty($_FILES['fotos_checklist_entrada']['name'] ?? null)) {
            return $mapped;
        }

        foreach ($_FILES['fotos_checklist_entrada']['name'] as $itemId => $files) {
            foreach ((array) $files as $index => $name) {
                $error = $_FILES['fotos_checklist_entrada']['error'][$itemId][$index] ?? UPLOAD_ERR_NO_FILE;
                if ($error !== UPLOAD_ERR_OK) {
                    continue;
                }

                $tmpName = $_FILES['fotos_checklist_entrada']['tmp_name'][$itemId][$index] ?? '';
                if ($tmpName === '' || !is_uploaded_file($tmpName)) {
                    continue;
                }

                $mapped[(string) $itemId][] = new \CodeIgniter\HTTP\Files\UploadedFile(
                    $tmpName,
                    (string) ($name ?: ('checklist_' . time() . '.jpg')),
                    (string) ($_FILES['fotos_checklist_entrada']['type'][$itemId][$index] ?? null),
                    (int) ($_FILES['fotos_checklist_entrada']['size'][$itemId][$index] ?? 0),
                    $error
                );
            }
        }

        return $mapped;
    }

    private function normalizeOsSlug(string $numeroOs): string
    {
        $clean = preg_replace('/[^A-Za-z0-9_]/', '', str_replace('-', '_', $numeroOs));
        $clean = preg_replace('/^OS_?/i', '', $clean);
        return $clean ?: 'os';
    }

    private function ensureAccessoryDirectory(string $slug): string
    {
        $base = FCPATH . 'uploads/acessorios/';
        if (!is_dir($base)) {
            mkdir($base, 0755, true);
        }

        $path = $base . 'OS_' . $slug . '/';
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
        return $path;
    }

    private function clearAccessoryFolder(string $numeroOs): void
    {
        $slug = $this->normalizeOsSlug($numeroOs);
        $path = FCPATH . 'uploads/acessorios/OS_' . $slug . '/';
        if (!is_dir($path)) {
            return;
        }

        foreach (glob($path . '*') as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }

    private function saveAccessoryPhoto(array $file, string $folder, string $slug, int &$sequence, int $acessorioId, FotoAcessorioModel $fotoModel): void
    {
        try {
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $name = "acessorio_{$slug}_{$sequence}";
            if ($extension) {
                $name .= '.' . $extension;
            }

            $destination = $folder . $name;
            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                throw new \RuntimeException('Falha ao mover upload');
            }

            $fotoModel->insert([
                'acessorio_id' => $acessorioId,
                'arquivo' => $name,
            ]);
            $sequence++;
        } catch (\Throwable $e) {
            log_message('warning', 'Erro ao salvar foto de acessório: ' . $e->getMessage());
        }
    }

    private function persistEstadoFisicoData(int $osId, string $numeroOs, bool $replaceExisting = false): void
    {
        $entries = $this->getEstadoFisicoEntries();
        $filesMap = $this->collectEstadoFisicoFiles();

        if (!$replaceExisting && empty($entries) && empty($filesMap)) {
            return;
        }

        $estadoModel = new EstadoFisicoOsModel();
        $fotoModel = new FotoEstadoFisicoModel();
        $slug = $this->normalizeOsSlug($numeroOs);
        $legacyPhotosByIndex = [];
        $savedFiles = [];

        if ($replaceExisting) {
            $legacyRows = $estadoModel->where('os_id', $osId)->orderBy('id', 'ASC')->findAll();
            foreach ($legacyRows as $legacyIndex => $legacyRow) {
                $legacyPhotosByIndex[$legacyIndex] = $fotoModel
                    ->where('estado_fisico_id', $legacyRow['id'])
                    ->orderBy('id', 'ASC')
                    ->findAll();
            }
            $estadoModel->deleteByOs($osId);
        }

        if (empty($entries) && empty($filesMap)) {
            if ($replaceExisting) {
                $this->clearEstadoFisicoFolder($numeroOs);
            }
            return;
        }

        $folder = $this->ensureEstadoFisicoDirectory($slug);
        $sequence = $this->nextEstadoFisicoSequence($folder, $slug);

        foreach ($entries as $entryIndex => $entry) {
            $description = trim($entry['text'] ?? '');
            if ($description === '') {
                continue;
            }

            $estadoModel->insert([
                'os_id' => $osId,
                'descricao_dano' => $description,
                'tipo' => $entry['key'] ?? null,
                'valores' => !empty($entry['values']) ? json_encode($entry['values'], JSON_UNESCAPED_UNICODE) : null,
            ]);

            $estadoItemId = $estadoModel->getInsertID();
            if (!$estadoItemId) {
                continue;
            }

            $entryFiles = $filesMap[$entry['id']] ?? [];
            if (!empty($entryFiles)) {
                foreach ($entryFiles as $file) {
                    $savedName = $this->saveEstadoFisicoPhoto($file, $folder, $slug, $sequence, $estadoItemId, $fotoModel);
                    if ($savedName) {
                        $savedFiles[$savedName] = true;
                    }
                }
                continue;
            }

            if ($replaceExisting && !empty($legacyPhotosByIndex[$entryIndex])) {
                foreach ($legacyPhotosByIndex[$entryIndex] as $legacyPhoto) {
                    $legacyPath = $folder . ($legacyPhoto['arquivo'] ?? '');
                    if (!is_file($legacyPath)) {
                        continue;
                    }
                    $fotoModel->insert([
                        'estado_fisico_id' => $estadoItemId,
                        'arquivo' => $legacyPhoto['arquivo'],
                    ]);
                    $savedFiles[$legacyPhoto['arquivo']] = true;
                }
            }
        }

        if ($replaceExisting && is_dir($folder)) {
            foreach (glob($folder . '*') as $filePath) {
                if (!is_file($filePath)) {
                    continue;
                }
                $name = basename($filePath);
                if (!isset($savedFiles[$name])) {
                    @unlink($filePath);
                }
            }
        }
    }

    private function getEstadoFisicoEntries(): array
    {
        $raw = $this->request->getPost('estado_fisico_data');
        if (empty($raw)) {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        return array_values(array_filter($decoded, static function ($entry) {
            return !empty(trim($entry['text'] ?? ''));
        }));
    }

    private function collectEstadoFisicoFiles(): array
    {
        $mapped = [];
        if (empty($_FILES['fotos_estado_fisico']['name'] ?? null)) {
            return $mapped;
        }

        foreach ($_FILES['fotos_estado_fisico']['name'] as $entryId => $files) {
            foreach ($files as $index => $name) {
                $error = $_FILES['fotos_estado_fisico']['error'][$entryId][$index] ?? UPLOAD_ERR_NO_FILE;
                if ($error !== UPLOAD_ERR_OK) {
                    continue;
                }

                $tmpName = $_FILES['fotos_estado_fisico']['tmp_name'][$entryId][$index];
                if (!is_uploaded_file($tmpName)) {
                    continue;
                }

                $mapped[$entryId][] = [
                    'name'     => $name,
                    'tmp_name' => $tmpName,
                ];
            }
        }

        return $mapped;
    }

    private function ensureEstadoFisicoDirectory(string $slug): string
    {
        $base = FCPATH . 'uploads/estado_fisico/';
        if (!is_dir($base)) {
            mkdir($base, 0755, true);
        }

        $path = $base . 'OS_' . $slug . '/';
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
        return $path;
    }

    private function clearEstadoFisicoFolder(string $numeroOs): void
    {
        $slug = $this->normalizeOsSlug($numeroOs);
        $path = FCPATH . 'uploads/estado_fisico/OS_' . $slug . '/';
        if (!is_dir($path)) {
            return;
        }

        foreach (glob($path . '*') as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }

    private function saveEstadoFisicoPhoto(array $file, string $folder, string $slug, int &$sequence, int $estadoItemId, FotoEstadoFisicoModel $fotoModel): ?string
    {
        try {
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $name = "estado_{$slug}_{$sequence}";
            if ($extension) {
                $name .= '.' . $extension;
            }

            $destination = $folder . $name;
            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                throw new \RuntimeException('Falha ao mover upload');
            }

            $fotoModel->insert([
                'estado_fisico_id' => $estadoItemId,
                'arquivo' => $name,
            ]);
            $sequence++;
            return $name;
        } catch (\Throwable $e) {
            log_message('warning', 'Erro ao salvar foto de estado fisico: ' . $e->getMessage());
            return null;
        }
    }

    private function nextEstadoFisicoSequence(string $folder, string $slug): int
    {
        $max = 0;
        foreach (glob($folder . 'estado_' . $slug . '_*') as $file) {
            $name = pathinfo($file, PATHINFO_FILENAME);
            if (preg_match('/^estado_' . preg_quote($slug, '/') . '_(\d+)$/', $name, $matches)) {
                $max = max($max, (int) $matches[1]);
            }
        }
        return $max + 1;
    }

    private function resolveEquipamentoFotoPublicUrl(string $arquivo): string
    {
        $arquivo = str_replace('\\', '/', ltrim($arquivo, '/'));
        if ($arquivo === '') {
            return $this->missingImageDataUri();
        }

        $arquivoFs = str_replace('/', DIRECTORY_SEPARATOR, $arquivo);
        $basename = basename($arquivo);
        $candidates = [
            [
                'path' => FCPATH . 'uploads' . DIRECTORY_SEPARATOR . 'equipamentos_perfil' . DIRECTORY_SEPARATOR . $arquivoFs,
                'url'  => base_url('uploads/equipamentos_perfil/' . $arquivo),
            ],
            [
                'path' => FCPATH . 'uploads' . DIRECTORY_SEPARATOR . 'equipamentos_perfil' . DIRECTORY_SEPARATOR . $basename,
                'url'  => base_url('uploads/equipamentos_perfil/' . $basename),
            ],
            [
                'path' => FCPATH . 'uploads' . DIRECTORY_SEPARATOR . 'equipamentos' . DIRECTORY_SEPARATOR . $basename,
                'url'  => base_url('uploads/equipamentos/' . $basename),
            ],
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate['path'])) {
                return $candidate['url'];
            }
        }

        return $this->missingImageDataUri();
    }

    private function getEquipamentoFotosWithUrls(int $equipamentoId): array
    {
        if ($equipamentoId <= 0) {
            return [];
        }

        $fotos = (new EquipamentoFotoModel())
            ->where('equipamento_id', $equipamentoId)
            ->orderBy('is_principal', 'DESC')
            ->orderBy('id', 'ASC')
            ->findAll();

        foreach ($fotos as &$foto) {
            $foto['url'] = $this->appendAssetVersion(
                $this->resolveEquipamentoFotoPublicUrl((string) ($foto['arquivo'] ?? '')),
                (string) ($foto['id'] ?? '')
            );
        }

        return $fotos;
    }

    private function resolveOsEntradaFotoPublicUrl(string $arquivo): string
    {
        $arquivo = str_replace('\\', '/', ltrim($arquivo, '/'));
        if ($arquivo === '') {
            return $this->missingImageDataUri();
        }

        $arquivoFs = str_replace('/', DIRECTORY_SEPARATOR, $arquivo);
        $basename = basename($arquivo);
        $candidates = [
            [
                'path' => FCPATH . 'uploads' . DIRECTORY_SEPARATOR . 'os_anormalidades' . DIRECTORY_SEPARATOR . $arquivoFs,
                'url'  => base_url('uploads/os_anormalidades/' . $arquivo),
            ],
            [
                'path' => FCPATH . 'uploads' . DIRECTORY_SEPARATOR . 'os_anormalidades' . DIRECTORY_SEPARATOR . $basename,
                'url'  => base_url('uploads/os_anormalidades/' . $basename),
            ],
            [
                'path' => FCPATH . 'uploads' . DIRECTORY_SEPARATOR . 'os' . DIRECTORY_SEPARATOR . $basename,
                'url'  => base_url('uploads/os/' . $basename),
            ],
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate['path'])) {
                return $candidate['url'];
            }
        }

        return $this->missingImageDataUri();
    }

    private function getOsEntradaFotosWithUrls(int $osId): array
    {
        if ($osId <= 0) {
            return [];
        }

        $fotos = (new OsFotoModel())
            ->where('os_id', $osId)
            ->where('tipo', 'recepcao')
            ->orderBy('id', 'ASC')
            ->findAll();

        foreach ($fotos as &$foto) {
            $foto['url'] = $this->appendAssetVersion(
                $this->resolveOsEntradaFotoPublicUrl((string) ($foto['arquivo'] ?? '')),
                (string) ($foto['id'] ?? '')
            );
        }

        return $fotos;
    }

    private function appendAssetVersion(string $url, string $version = ''): string
    {
        if ($url === '' || str_starts_with($url, 'data:') || trim($version) === '') {
            return $url;
        }

        return $url . (str_contains($url, '?') ? '&' : '?') . 'v=' . rawurlencode($version);
    }

    private function mapOsViewerPhotoPayload(array $photo, string $type, int $position, bool $defaultPrincipal = false): array
    {
        $isPrincipal = ((int) ($photo['is_principal'] ?? 0) === 1) || ($defaultPrincipal && $position === 1);
        $prefix = $type === 'equipamento' ? 'Foto do equipamento' : 'Foto da abertura';
        $label = $prefix . ' ' . $position;
        if ($isPrincipal && $type === 'equipamento') {
            $label .= ' (principal)';
        }

        return [
            'id' => (int) ($photo['id'] ?? 0),
            'url' => (string) ($photo['url'] ?? $this->missingImageDataUri()),
            'is_principal' => $isPrincipal,
            'label' => $label,
        ];
    }

    private function missingImageDataUri(): string
    {
        static $uri = null;
        if ($uri !== null) {
            return $uri;
        }

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="180" height="120" viewBox="0 0 180 120"><rect width="180" height="120" rx="10" fill="#eef2ff"/><rect x="62" y="34" width="56" height="36" rx="6" fill="#c7d2fe"/><circle cx="90" cy="52" r="10" fill="#818cf8"/><text x="90" y="96" text-anchor="middle" font-size="12" fill="#64748b">sem foto</text></svg>';
        $uri = 'data:image/svg+xml;base64,' . base64_encode($svg);
        return $uri;
    }

    private function isEmbedRequest(): bool
    {
        $embed = strtolower(trim((string) $this->request->getGet('embed')));
        return in_array($embed, ['1', 'true', 'yes', 'sim'], true);
    }

    private function osViewUrl(int $osId): string
    {
        $url = '/os/visualizar/' . $osId;
        if ($this->isEmbedRequest()) {
            $url .= '?embed=1';
        }
        return $url;
    }

    private function osEditUrl(int $osId): string
    {
        $url = '/os/editar/' . $osId;
        if ($this->isEmbedRequest()) {
            $url .= '?embed=1';
        }
        return $url;
    }

    public function print($id)
    {
        $os = $this->model->getComplete($id);
        if (!$os) {
            return redirect()->back()->with('error', 'OS não encontrada.');
        }

        $itemModel = new OsItemModel();
        $defeitoModel = new \App\Models\DefeitoModel();
        $procedimentoModel = new \App\Models\EquipamentoDefeitoProcedimentoModel();

        $defeitos = $defeitoModel->getByOs($id);
        foreach ($defeitos as &$defeito) {
            $defeito['procedimentos'] = $procedimentoModel->getByDefeito($defeito['defeito_id']);
        }

        $data = [
            'os'       => $os,
            'itens'    => $itemModel->getByOs($id),
            'defeitos' => $defeitos
        ];
        return view('os/print', $data);
    }
}
