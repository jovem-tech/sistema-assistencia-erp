<?php

namespace App\Controllers\Api\V1;

use App\Models\AcessorioOsModel;
use App\Models\ClienteModel;
use App\Models\DefeitoModel;
use App\Models\DefeitoRelatadoModel;
use App\Models\EquipamentoModel;
use App\Models\EquipamentoFotoModel;
use App\Models\FotoAcessorioModel;
use App\Models\FuncionarioModel;
use App\Models\OsFotoModel;
use App\Models\OsModel;
use App\Models\OsStatusHistoricoModel;
use App\Services\ChecklistService;
use App\Services\Mobile\MobileNotificationService;
use App\Services\OsPdfService;
use App\Services\OsStatusFlowService;
use App\Services\WhatsAppService;
use Throwable;

class OrdersController extends BaseApiController
{
    public function index()
    {
        if ($permissionError = $this->ensurePermission('os', 'visualizar')) {
            return $permissionError;
        }

        $q = trim((string) $this->request->getGet('q'));
        $status = trim((string) $this->request->getGet('status'));
        $page = max(1, (int) ($this->request->getGet('page') ?? 1));
        $perPage = max(1, min(100, (int) ($this->request->getGet('per_page') ?? 25)));
        $offset = ($page - 1) * $perPage;

        $db = \Config\Database::connect();
        $builder = $db->table('os')
            ->select('os.id, os.numero_os, os.numero_os_legado, os.status, os.estado_fluxo, os.prioridade, os.relato_cliente, os.observacoes_cliente, os.observacoes_internas, os.data_abertura, os.data_entrada, os.data_previsao, os.data_conclusao, os.data_entrega, os.valor_final, os.created_at, clientes.nome_razao AS cliente_nome, clientes.telefone1 AS cliente_telefone, clientes.email AS cliente_email, et.nome AS equip_tipo, em.nome AS equip_marca, emod.nome AS equip_modelo, equipamentos.id AS equipamento_id, equipamentos.numero_serie AS equip_serie, equipamentos.imei AS equip_imei, funcionarios.nome AS tecnico_nome, (SELECT ef.arquivo FROM equipamentos_fotos ef WHERE ef.equipamento_id = equipamentos.id ORDER BY ef.is_principal DESC, ef.id ASC LIMIT 1) AS equip_foto_arquivo')
            ->join('clientes', 'clientes.id = os.cliente_id', 'left')
            ->join('equipamentos', 'equipamentos.id = os.equipamento_id', 'left')
            ->join('equipamentos_tipos et', 'et.id = equipamentos.tipo_id', 'left')
            ->join('equipamentos_marcas em', 'em.id = equipamentos.marca_id', 'left')
            ->join('equipamentos_modelos emod', 'emod.id = equipamentos.modelo_id', 'left')
            ->join('funcionarios', 'funcionarios.id = os.tecnico_id', 'left');

        if ($q !== '') {
            $normalizedDigits = preg_replace('/\D+/', '', $q) ?? '';
            $builder->groupStart()
                ->like('os.numero_os', $q)
                ->orLike('os.numero_os_legado', $q)
                ->orLike('os.status', $q)
                ->orLike('os.estado_fluxo', $q)
                ->orLike('os.prioridade', $q)
                ->orLike('os.relato_cliente', $q)
                ->orLike('os.diagnostico_tecnico', $q)
                ->orLike('os.solucao_aplicada', $q)
                ->orLike('os.acessorios', $q)
                ->orLike('os.observacoes_cliente', $q)
                ->orLike('os.observacoes_internas', $q)
                ->orLike('os.forma_pagamento', $q)
                ->orLike('clientes.nome_razao', $q)
                ->orLike('clientes.telefone1', $q)
                ->orLike('clientes.email', $q)
                ->orLike('et.nome', $q)
                ->orLike('em.nome', $q)
                ->orLike('emod.nome', $q)
                ->orLike('equipamentos.numero_serie', $q)
                ->orLike('equipamentos.imei', $q)
                ->orLike('equipamentos.cor', $q)
                ->orLike('equipamentos.estado_fisico', $q)
                ->orLike('equipamentos.acessorios', $q)
                ->orLike('equipamentos.observacoes', $q)
                ->orLike('funcionarios.nome', $q)
                ->orLike('os.data_abertura', $q)
                ->orLike('os.data_entrada', $q)
                ->orLike('os.data_previsao', $q)
                ->orLike('os.data_conclusao', $q)
                ->orLike('os.data_entrega', $q);

            if ($normalizedDigits !== '') {
                $escapedDigits = $db->escapeLikeString($normalizedDigits);
                $telefoneSql = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(clientes.telefone1, '(', ''), ')', ''), '-', ''), ' ', ''), '+', ''), '.', '')";
                $imeiSql = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(equipamentos.imei, '(', ''), ')', ''), '-', ''), ' ', ''), '+', ''), '.', '')";

                $builder->orGroupStart()
                    ->where($telefoneSql . " LIKE '%" . $escapedDigits . "%'", null, false)
                    ->orWhere($imeiSql . " LIKE '%" . $escapedDigits . "%'", null, false);

                if (ctype_digit($normalizedDigits)) {
                    $builder->orWhere('os.id', (int) $normalizedDigits);
                }

                $builder->groupEnd();
            }

            $builder->groupEnd();
        }

        if ($status !== '') {
            $builder->where('os.status', $status);
        }

        $totalBuilder = clone $builder;
        $total = (int) $totalBuilder->countAllResults();
        $items = $builder->orderBy('os.id', 'DESC')->get($perPage, $offset)->getResultArray();
        $items = $this->hydrateOrderEquipmentPhotos($items);
        $items = array_map(function (array $item): array {
            $item['equip_foto_url'] = $this->buildEquipmentPhotoUrl((string) ($item['equip_foto_arquivo'] ?? ''));
            return $item;
        }, $items);

        return $this->respondSuccess([
            'items' => $items,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => (int) ceil(max(1, $total) / $perPage),
            ],
        ]);
    }

    public function meta()
    {
        if ($permissionError = $this->ensurePermission('os', 'criar')) {
            return $permissionError;
        }

        $clientQuery = trim((string) $this->request->getGet('q'));
        $clienteId = (int) ($this->request->getGet('cliente_id') ?? 0);
        $equipamentoId = (int) ($this->request->getGet('equipamento_id') ?? 0);
        $tipoId = (int) ($this->request->getGet('tipo_id') ?? 0);

        $clientsBuilder = (new ClienteModel())
            ->select('id, nome_razao, telefone1, email')
            ->orderBy('nome_razao', 'ASC');

        $clients = [];
        if ($clientQuery !== '') {
            $queryDigits = preg_replace('/\D+/', '', $clientQuery) ?? '';
            $clientsBuilder->groupStart()
                ->like('nome_razao', $clientQuery)
                ->orLike('email', $clientQuery);

            if ($queryDigits !== '') {
                $db = \Config\Database::connect();
                $escapedDigits = $db->escapeLikeString($queryDigits);
                $telefoneSql = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(telefone1, '(', ''), ')', ''), '-', ''), ' ', ''), '+', ''), '.', '')";
                $clientsBuilder->orWhere($telefoneSql . " LIKE '%" . $escapedDigits . "%'", null, false);
            } else {
                $clientsBuilder->orLike('telefone1', $clientQuery);
            }

            $clientsBuilder->groupEnd();
            $clients = $clientsBuilder->findAll(120);
        } else {
            $clients = $clientsBuilder->findAll(80);
        }

        if ($clienteId > 0) {
            $alreadyInList = false;
            foreach ($clients as $clientItem) {
                if ((int) ($clientItem['id'] ?? 0) === $clienteId) {
                    $alreadyInList = true;
                    break;
                }
            }

            if (!$alreadyInList) {
                $selectedClient = (new ClienteModel())
                    ->select('id, nome_razao, telefone1, email')
                    ->find($clienteId);
                if ($selectedClient) {
                    array_unshift($clients, $selectedClient);
                }
            }
        }

        $equipamentos = [];
        if ($clienteId > 0) {
            $equipmentRows = (new EquipamentoModel())->getByCliente($clienteId);
            $equipmentPhotoMap = $this->groupEquipmentPhotosByEquipmentIds(array_column($equipmentRows, 'id'));
            $equipamentos = array_map(
                function (array $equipamento) use ($equipmentPhotoMap): array {
                    $marcaNome = trim((string) ($equipamento['marca_nome'] ?? ''));
                    $modeloNome = trim((string) ($equipamento['modelo_nome'] ?? ''));
                    $tipoNome = trim((string) ($equipamento['tipo_nome'] ?? ''));
                    $labelParts = array_values(array_filter([$marcaNome, $modeloNome, $tipoNome]));
                    $equipamentoId = (int) ($equipamento['id'] ?? 0);
                    $fotos = $equipmentPhotoMap[$equipamentoId] ?? [];
                    $fotoPrincipalUrl = $fotos[0]['url'] ?? '';

                    if ($fotoPrincipalUrl === '' && trim((string) ($equipamento['foto_principal_arquivo'] ?? '')) !== '') {
                        $fotoPrincipalUrl = $this->buildEquipmentPhotoUrl((string) $equipamento['foto_principal_arquivo']);
                    }

                    return [
                        'id' => $equipamentoId,
                        'tipo_id' => (int) ($equipamento['tipo_id'] ?? 0),
                        'marca_id' => (int) ($equipamento['marca_id'] ?? 0),
                        'modelo_id' => (int) ($equipamento['modelo_id'] ?? 0),
                        'cliente_id' => (int) ($equipamento['cliente_id'] ?? 0),
                        'tipo_nome' => $tipoNome,
                        'marca_nome' => $marcaNome,
                        'modelo_nome' => $modeloNome,
                        'cor' => trim((string) ($equipamento['cor'] ?? '')),
                        'numero_serie' => (string) ($equipamento['numero_serie'] ?? ''),
                        'imei' => (string) ($equipamento['imei'] ?? ''),
                        'foto_url' => $fotoPrincipalUrl,
                        'fotos' => $fotos,
                        'label' => !empty($labelParts) ? implode(' - ', $labelParts) : ('Equipamento #' . $equipamentoId),
                    ];
                },
                $equipmentRows
            );
        }

        if ($tipoId <= 0 && $equipamentoId > 0) {
            $selectedEquipamento = (new EquipamentoModel())
                ->select('id, tipo_id')
                ->find($equipamentoId);
            $tipoId = (int) ($selectedEquipamento['tipo_id'] ?? 0);
        }

        $defeitos = [];
        if ($tipoId > 0) {
            $defeitos = array_map(
                static fn (array $defeito): array => [
                    'id' => (int) ($defeito['id'] ?? 0),
                    'nome' => (string) ($defeito['nome'] ?? ''),
                    'classificacao' => (string) ($defeito['classificacao'] ?? ''),
                    'descricao' => (string) ($defeito['descricao'] ?? ''),
                ],
                (new DefeitoModel())->getByTipo($tipoId)
            );
        }
        $reportedDefects = [];
        try {
            $reportedDefects = array_values(array_filter(array_map(
                static function (array $group): array {
                    $items = array_values(array_map(
                        static fn (array $item): array => [
                            'id' => (int) ($item['id'] ?? 0),
                            'texto_relato' => trim((string) ($item['texto_relato'] ?? '')),
                            'categoria' => (string) ($item['categoria'] ?? ''),
                        ],
                        array_filter(
                            (array) ($group['itens'] ?? []),
                            static fn (array $item): bool => trim((string) ($item['texto_relato'] ?? '')) !== ''
                        )
                    ));

                    return [
                        'categoria' => (string) ($group['categoria'] ?? 'Outros'),
                        'icone' => (string) ($group['icone'] ?? ''),
                        'itens' => $items,
                    ];
                },
                (new DefeitoRelatadoModel())->getActiveGrouped()
            ), static fn (array $group): bool => !empty($group['itens'])));
        } catch (Throwable $e) {
            log_message('error', '[Mobile API][Orders Meta] falha ao carregar defeitos relatados: {message}', [
                'message' => $e->getMessage(),
            ]);
        }

        $statusService = new OsStatusFlowService();
        $statusGrouped = $statusService->getStatusGrouped();
        $checklistEntrada = null;
        if ($tipoId > 0 && $this->isChecklistInfraReady()) {
            try {
                $checklistEntrada = (new ChecklistService())->getPayloadForOs(0, 'entrada', $tipoId);
            } catch (Throwable $e) {
                log_message('error', '[Mobile API][Checklist] falha ao carregar meta de checklist: ' . $e->getMessage());
                $checklistEntrada = null;
            }
        }
        $statusFlat = [];
        foreach ($statusGrouped as $macro => $items) {
            if (!is_array($items)) {
                continue;
            }

            foreach ($items as $item) {
                $code = trim((string) ($item['codigo'] ?? ''));
                if ($code === '') {
                    continue;
                }

                $statusFlat[] = [
                    'codigo' => $code,
                    'nome' => (string) ($item['nome'] ?? $code),
                    'grupo_macro' => (string) ($macro ?? ($item['grupo_macro'] ?? 'outros')),
                    'cor' => (string) ($item['cor'] ?? 'secondary'),
                    'ordem_fluxo' => (int) ($item['ordem_fluxo'] ?? 0),
                ];
            }
        }

        $tecnicos = (new FuncionarioModel())
            ->select('id, nome, cargo')
            ->where('ativo', 1)
            ->orderBy('nome', 'ASC')
            ->findAll();

        return $this->respondSuccess([
            'clients' => $clients,
            'equipments' => $equipamentos,
            'technicians' => $tecnicos,
            'statuses_grouped' => $statusGrouped,
            'statuses' => $statusFlat,
            'priorities' => [
                ['codigo' => 'baixa', 'nome' => 'Baixa'],
                ['codigo' => 'normal', 'nome' => 'Normal'],
                ['codigo' => 'alta', 'nome' => 'Alta'],
                ['codigo' => 'urgente', 'nome' => 'Urgente'],
            ],
            'defects' => $defeitos,
            'reported_defects' => $reportedDefects,
            'checklist_entrada' => $checklistEntrada,
            'selected' => [
                'cliente_id' => $clienteId > 0 ? $clienteId : null,
                'equipamento_id' => $equipamentoId > 0 ? $equipamentoId : null,
                'tipo_id' => $tipoId > 0 ? $tipoId : null,
            ],
        ]);
    }

    public function show($id = null)
    {
        if ($permissionError = $this->ensurePermission('os', 'visualizar')) {
            return $permissionError;
        }

        $osId = (int) $id;
        if ($osId <= 0) {
            return $this->respondError('OS invalida.', 422, 'ORDER_INVALID_ID');
        }

        $item = (new OsModel())->getComplete($osId);
        if (!$item) {
            return $this->respondError('OS nao encontrada.', 404, 'ORDER_NOT_FOUND');
        }

        $defeitos = (new DefeitoModel())->getByOs($osId);
        $item['defeitos'] = $defeitos;
        $item['defeitos_ids'] = array_values(array_map(
            static fn (array $row): int => (int) ($row['defeito_id'] ?? 0),
            $defeitos
        ));
        $item['checklist_entrada'] = $this->resolveChecklistEntradaPayload($osId, $item);

        return $this->respondSuccess($item);
    }

    public function create()
    {
        if ($permissionError = $this->ensurePermission('os', 'criar')) {
            return $permissionError;
        }

        $payload = $this->payload();
        $clienteId = (int) ($payload['cliente_id'] ?? 0);
        $equipamentoId = (int) ($payload['equipamento_id'] ?? 0);
        $relatoCliente = trim((string) ($payload['relato_cliente'] ?? ''));

        if ($clienteId <= 0 || $equipamentoId <= 0 || $relatoCliente === '') {
            return $this->respondError(
                'cliente_id, equipamento_id e relato_cliente sao obrigatorios.',
                422,
                'ORDER_CREATE_VALIDATION'
            );
        }

        $statusService = new OsStatusFlowService();
        $statusCode = $this->normalizeStatusCode((string) ($payload['status'] ?? 'triagem'));
        if ($statusCode === '') {
            $statusCode = 'triagem';
        }

        if (!$statusService->getStatusByCode($statusCode)) {
            $statusCode = 'triagem';
        }

        $prioridade = $this->normalizePriority((string) ($payload['prioridade'] ?? 'normal'));
        $notificarCliente = (int) ($payload['notificar_cliente'] ?? 0) === 1;
        $notificacaoClienteModo = trim((string) ($payload['notificacao_cliente_modo'] ?? 'message'));
        if (!in_array($notificacaoClienteModo, ['message', 'message_pdf'], true)) {
            $notificacaoClienteModo = 'message';
        }
        $valorMaoObra = $this->normalizeDecimalValue($payload['valor_mao_obra'] ?? null) ?? 0.0;
        $valorPecas = $this->normalizeDecimalValue($payload['valor_pecas'] ?? null) ?? 0.0;
        $desconto = $this->normalizeDecimalValue($payload['desconto'] ?? null) ?? 0.0;
        $valorTotalInput = $this->normalizeDecimalValue($payload['valor_total'] ?? null);
        $valorFinalInput = $this->normalizeDecimalValue($payload['valor_final'] ?? null);

        $valorTotal = $valorTotalInput ?? ($valorMaoObra + $valorPecas);
        $valorFinal = $valorFinalInput ?? ($valorTotal - $desconto);
        if ($valorFinal < 0) {
            $valorFinal = 0.0;
        }

        $osModel = new OsModel();
        $numeroOs = $osModel->generateNumeroOs();
        $now = date('Y-m-d H:i:s');

        $data = [
            'numero_os' => $numeroOs,
            'cliente_id' => $clienteId,
            'equipamento_id' => $equipamentoId,
            'tecnico_id' => $this->normalizePositiveInt($payload['tecnico_id'] ?? null),
            'status' => $statusCode,
            'estado_fluxo' => $statusService->resolveEstadoFluxo($statusCode),
            'status_atualizado_em' => $now,
            'prioridade' => $prioridade,
            'relato_cliente' => $relatoCliente,
            'diagnostico_tecnico' => $this->normalizeNullableString($payload['diagnostico_tecnico'] ?? null),
            'solucao_aplicada' => $this->normalizeNullableString($payload['solucao_aplicada'] ?? null),
            'data_abertura' => $now,
            'data_entrada' => $this->normalizeDateTimeInput($payload['data_entrada'] ?? null, $now),
            'data_previsao' => $this->normalizeDateTimeInput($payload['data_previsao'] ?? null),
            'data_conclusao' => $this->normalizeDateTimeInput($payload['data_conclusao'] ?? null),
            'data_entrega' => $this->normalizeDateTimeInput($payload['data_entrega'] ?? null),
            'valor_mao_obra' => $valorMaoObra,
            'valor_pecas' => $valorPecas,
            'valor_total' => $valorTotal,
            'desconto' => $desconto,
            'valor_final' => $valorFinal,
            'forma_pagamento' => $this->normalizeNullableString($payload['forma_pagamento'] ?? null),
            'garantia_dias' => $this->normalizePositiveInt($payload['garantia_dias'] ?? null),
            'garantia_validade' => $this->normalizeDateTimeInput($payload['garantia_validade'] ?? null),
            'observacoes_cliente' => $this->normalizeNullableString($payload['observacoes_cliente'] ?? null),
            'observacoes_internas' => $this->normalizeNullableString($payload['observacoes_internas'] ?? null),
        ];

        $db = \Config\Database::connect();

        try {
            $db->transBegin();

            $newId = (int) $osModel->insert($data, true);
            if ($newId <= 0) {
                $db->transRollback();
                return $this->respondError(
                    'Nao foi possivel criar a OS.',
                    422,
                    'ORDER_CREATE_FAILED',
                    $osModel->errors()
                );
            }

            $this->insertStatusHistory($newId, $statusCode, (string) ($data['estado_fluxo'] ?? ''), $now);
            $this->persistDefects($newId, $payload['defeitos'] ?? []);
            $this->persistEntryPhotos($newId, $numeroOs);
            $this->persistAccessories($newId, $payload);
            $this->persistChecklistEntrada($newId, $numeroOs, $equipamentoId, $payload);

            if ($db->transStatus() === false) {
                $db->transRollback();
                return $this->respondError('Falha ao persistir dados da OS.', 500, 'ORDER_CREATE_TRANSACTION_FAILED');
            }

            $db->transCommit();

            $user = $this->currentUser();
            $notificationService = new MobileNotificationService();
            $notificationService->createNotification(
                (int) ($user['id'] ?? 0),
                'order.created',
                'Nova OS criada',
                'A ordem ' . $numeroOs . ' foi criada com sucesso.',
                [
                    'order_id' => $newId,
                ],
                '/os/' . $newId,
                [
                    ['tipo' => 'order', 'id' => $newId],
                    ['tipo' => 'client', 'id' => $clienteId],
                ]
            );
            $notificationService->enqueueOutbox(
                'order.created',
                'order',
                $newId,
                [
                    'order_id' => $newId,
                    'cliente_id' => $clienteId,
                    'numero_os' => $numeroOs,
                ]
            );
            $clientNotification = null;
            if ($notificarCliente) {
                $clientNotification = $this->dispatchClientOpenNotification($newId, $notificacaoClienteModo);
                $notificationService->createNotification(
                    (int) ($user['id'] ?? 0),
                    !empty($clientNotification['ok'])
                        ? 'order.client_notification.sent'
                        : 'order.client_notification.failed',
                    !empty($clientNotification['ok'])
                        ? 'Notificacao WhatsApp enviada'
                        : 'Falha na notificacao WhatsApp',
                    !empty($clientNotification['ok'])
                        ? 'Cliente notificado com sucesso na abertura da OS.'
                        : ((string) ($clientNotification['message'] ?? 'Nao foi possivel notificar o cliente na abertura da OS.')),
                    [
                        'order_id' => $newId,
                        'cliente_id' => $clienteId,
                        'numero_os' => $numeroOs,
                        'mode' => $notificacaoClienteModo,
                        'dispatch' => $clientNotification,
                    ],
                    '/os/' . $newId,
                    [
                        ['tipo' => 'order', 'id' => $newId],
                        ['tipo' => 'client', 'id' => $clienteId],
                    ]
                );
            }

            return $this->respondSuccess([
                'id' => $newId,
                'numero_os' => $numeroOs,
                'notificacao_cliente' => $clientNotification,
            ], 201);
        } catch (Throwable $e) {
            if ($db->transStatus() !== false) {
                $db->transRollback();
            }
            log_message('error', '[API V1][ORDERS CREATE] ' . $e->getMessage());
            return $this->respondError(
                'Falha inesperada ao criar OS.',
                500,
                'ORDER_CREATE_UNEXPECTED'
            );
        }
    }

    public function update($id = null)
    {
        if ($permissionError = $this->ensurePermission('os', 'editar')) {
            return $permissionError;
        }

        $osId = (int) $id;
        if ($osId <= 0) {
            return $this->respondError('OS invalida.', 422, 'ORDER_INVALID_ID');
        }

        $payload = $this->payload();
        $osModel = new OsModel();
        $existing = $osModel->find($osId);
        if (!$existing) {
            return $this->respondError('OS nao encontrada.', 404, 'ORDER_NOT_FOUND');
        }

        $statusService = new OsStatusFlowService();
        $allowed = [];
        $statusChanged = false;

        if (array_key_exists('status', $payload)) {
            $statusCode = $this->normalizeStatusCode((string) $payload['status']);
            if ($statusCode === '' || !$statusService->getStatusByCode($statusCode)) {
                return $this->respondError(
                    'Status invalido para atualizacao da OS.',
                    422,
                    'ORDER_UPDATE_INVALID_STATUS'
                );
            }

            $allowed['status'] = $statusCode;
            $allowed['estado_fluxo'] = $statusService->resolveEstadoFluxo($statusCode);
            $allowed['status_atualizado_em'] = date('Y-m-d H:i:s');
            $statusChanged = $statusCode !== (string) ($existing['status'] ?? '');
        }
        if (isset($payload['estado_fluxo']) && trim((string) $payload['estado_fluxo']) !== '' && !isset($allowed['estado_fluxo'])) {
            $allowed['estado_fluxo'] = trim((string) $payload['estado_fluxo']);
        }
        if (array_key_exists('prioridade', $payload)) {
            $allowed['prioridade'] = $this->normalizePriority((string) $payload['prioridade']);
        }
        if (array_key_exists('tecnico_id', $payload)) {
            $allowed['tecnico_id'] = $this->normalizePositiveInt($payload['tecnico_id'] ?? null);
        }
        if (array_key_exists('relato_cliente', $payload)) {
            $relato = trim((string) ($payload['relato_cliente'] ?? ''));
            if ($relato === '') {
                return $this->respondError(
                    'O relato do cliente nao pode ficar vazio.',
                    422,
                    'ORDER_UPDATE_EMPTY_REPORT'
                );
            }
            $allowed['relato_cliente'] = $relato;
        }
        if (array_key_exists('diagnostico_tecnico', $payload)) {
            $allowed['diagnostico_tecnico'] = $this->normalizeNullableString($payload['diagnostico_tecnico'] ?? null);
        }
        if (array_key_exists('solucao_aplicada', $payload)) {
            $allowed['solucao_aplicada'] = $this->normalizeNullableString($payload['solucao_aplicada'] ?? null);
        }
        if (array_key_exists('data_entrada', $payload)) {
            $allowed['data_entrada'] = $this->normalizeDateTimeInput($payload['data_entrada'] ?? null);
        }
        if (array_key_exists('data_previsao', $payload)) {
            $allowed['data_previsao'] = $this->normalizeDateTimeInput($payload['data_previsao'] ?? null);
        }
        if (array_key_exists('data_conclusao', $payload)) {
            $allowed['data_conclusao'] = $this->normalizeDateTimeInput($payload['data_conclusao'] ?? null);
        }
        if (array_key_exists('data_entrega', $payload)) {
            $allowed['data_entrega'] = $this->normalizeDateTimeInput($payload['data_entrega'] ?? null);
        }

        $hasCostInputs = false;
        if (array_key_exists('valor_mao_obra', $payload)) {
            $allowed['valor_mao_obra'] = $this->normalizeDecimalValue($payload['valor_mao_obra'] ?? null) ?? 0.0;
            $hasCostInputs = true;
        }
        if (array_key_exists('valor_pecas', $payload)) {
            $allowed['valor_pecas'] = $this->normalizeDecimalValue($payload['valor_pecas'] ?? null) ?? 0.0;
            $hasCostInputs = true;
        }
        if (array_key_exists('desconto', $payload)) {
            $allowed['desconto'] = $this->normalizeDecimalValue($payload['desconto'] ?? null) ?? 0.0;
            $hasCostInputs = true;
        }
        if (array_key_exists('valor_total', $payload)) {
            $allowed['valor_total'] = $this->normalizeDecimalValue($payload['valor_total'] ?? null);
        }
        if (array_key_exists('valor_final', $payload)) {
            $allowed['valor_final'] = $this->normalizeDecimalValue($payload['valor_final'] ?? null);
        }

        if ($hasCostInputs) {
            $valorMaoObra = (float) ($allowed['valor_mao_obra'] ?? $existing['valor_mao_obra'] ?? 0.0);
            $valorPecas = (float) ($allowed['valor_pecas'] ?? $existing['valor_pecas'] ?? 0.0);
            $desconto = (float) ($allowed['desconto'] ?? $existing['desconto'] ?? 0.0);
            if (!array_key_exists('valor_total', $allowed) || $allowed['valor_total'] === null) {
                $allowed['valor_total'] = $valorMaoObra + $valorPecas;
            }
            if (!array_key_exists('valor_final', $allowed) || $allowed['valor_final'] === null) {
                $allowed['valor_final'] = max(0.0, ((float) $allowed['valor_total']) - $desconto);
            }
        }

        if (array_key_exists('forma_pagamento', $payload)) {
            $allowed['forma_pagamento'] = $this->normalizeNullableString($payload['forma_pagamento'] ?? null);
        }
        if (array_key_exists('garantia_dias', $payload)) {
            $allowed['garantia_dias'] = $this->normalizePositiveInt($payload['garantia_dias'] ?? null);
        }
        if (array_key_exists('garantia_validade', $payload)) {
            $allowed['garantia_validade'] = $this->normalizeDateTimeInput($payload['garantia_validade'] ?? null);
        }
        if (array_key_exists('observacoes_cliente', $payload)) {
            $allowed['observacoes_cliente'] = $this->normalizeNullableString($payload['observacoes_cliente'] ?? null);
        }
        if (array_key_exists('observacoes_internas', $payload)) {
            $allowed['observacoes_internas'] = $this->normalizeNullableString($payload['observacoes_internas'] ?? null);
        }

        $syncDefects = array_key_exists('defeitos', $payload);
        $syncChecklistEntrada = array_key_exists('checklist_entrada_data', $payload)
            || !empty($this->collectChecklistFiles('fotos_checklist_entrada'));
        if (empty($allowed) && !$syncDefects && !$syncChecklistEntrada) {
            return $this->respondError(
                'Nenhum campo permitido para atualizacao foi enviado.',
                422,
                'ORDER_UPDATE_EMPTY'
            );
        }

        $db = \Config\Database::connect();
        $db->transBegin();

        if (!empty($allowed)) {
            $updated = $osModel->update($osId, $allowed);
            if (!$updated) {
                $db->transRollback();
                return $this->respondError(
                    'Falha ao atualizar OS.',
                    422,
                    'ORDER_UPDATE_FAILED',
                    $osModel->errors()
                );
            }
        }

        if ($syncDefects) {
            $this->persistDefects($osId, $payload['defeitos'] ?? [], true);
        }

        if ($syncChecklistEntrada) {
            $this->persistChecklistEntrada(
                $osId,
                (string) ($existing['numero_os'] ?? ''),
                (int) ($existing['equipamento_id'] ?? 0),
                $payload
            );
        }

        if ($db->transStatus() === false) {
            $db->transRollback();
            return $this->respondError('Falha ao persistir atualizacao da OS.', 500, 'ORDER_UPDATE_TRANSACTION_FAILED');
        }

        $db->transCommit();

        if ($statusChanged) {
            $notificationService = new MobileNotificationService();
            $notificationService->enqueueOutbox(
                'order.status_changed',
                'order',
                $osId,
                [
                    'order_id' => $osId,
                    'status_old' => $existing['status'] ?? null,
                    'status_new' => $allowed['status'] ?? null,
                ]
            );
        }

        $updatedFields = array_keys($allowed);
        if ($syncDefects) {
            $updatedFields[] = 'defeitos';
        }
        if ($syncChecklistEntrada) {
            $updatedFields[] = 'checklist_entrada';
        }

        return $this->respondSuccess([
            'id' => $osId,
            'updated_fields' => array_values(array_unique($updatedFields)),
        ]);
    }

    /**
     * @return array<string,mixed>
     */
    private function payload(): array
    {
        $contentType = strtolower((string) $this->request->getHeaderLine('Content-Type'));

        if (str_contains($contentType, 'application/json')) {
            try {
                $json = $this->request->getJSON(true);
                if (is_array($json) && !empty($json)) {
                    return $json;
                }
            } catch (Throwable $e) {
                log_message('warning', '[OrdersController] falha ao interpretar payload JSON: {message}', [
                    'message' => $e->getMessage(),
                ]);
            }
        }

        $post = $this->request->getPost();
        if (is_array($post) && !empty($post)) {
            return $post;
        }

        $raw = $this->request->getRawInput();
        return is_array($raw) ? $raw : [];
    }

    private function insertStatusHistory(int $osId, string $statusCode, string $estadoFluxo, string $now): void
    {
        $db = \Config\Database::connect();
        if (!$db->tableExists('os_status_historico')) {
            return;
        }

        $historicoModel = new OsStatusHistoricoModel();

        $historicoModel->insert([
            'os_id' => $osId,
            'status_anterior' => null,
            'status_novo' => $statusCode,
            'estado_fluxo' => $estadoFluxo !== '' ? $estadoFluxo : null,
            'usuario_id' => $this->currentUserId() > 0 ? $this->currentUserId() : null,
            'observacao' => 'OS aberta via mobile',
            'created_at' => $now,
        ]);
    }

    /**
     * @param mixed $rawIds
     */
    private function persistDefects(int $osId, $rawIds, bool $syncWhenEmpty = false): void
    {
        $defeitoIds = $this->normalizeIntegerList($rawIds);
        if (empty($defeitoIds) && !$syncWhenEmpty) {
            return;
        }

        (new DefeitoModel())->saveOsDefeitos($osId, $defeitoIds);
    }

    private function persistEntryPhotos(int $osId, string $numeroOs): void
    {
        $files = $this->request->getFileMultiple('fotos_entrada');
        if (empty($files)) {
            $files = $this->request->getFileMultiple('fotos_entrada[]');
        }
        if (empty($files)) {
            $single = $this->request->getFile('fotos_entrada');
            if ((!$single || !$single->isValid())) {
                $single = $this->request->getFile('fotos_entrada[]');
            }
            if ($single && $single->isValid()) {
                $files = [$single];
            }
        }

        if (empty($files)) {
            return;
        }

        $targetDir = rtrim(FCPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'os_anormalidades';
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0775, true);
        }
        if (!is_dir($targetDir)) {
            throw new \RuntimeException('Diretorio de fotos da OS indisponivel.');
        }

        $slug = $this->normalizeOsSlug($numeroOs);
        $fotoModel = new OsFotoModel();

        foreach ($files as $index => $file) {
            if (!$file || !$file->isValid() || $file->hasMoved()) {
                continue;
            }

            $ext = strtolower((string) $file->getExtension());
            if ($ext === '') {
                $ext = 'jpg';
            }

            $newName = $slug . '_entrada_' . ($index + 1) . '_' . time() . '_' . random_int(1000, 9999) . '.' . $ext;
            $file->move($targetDir, $newName);

            $fotoModel->insert([
                'os_id' => $osId,
                'tipo' => 'recepcao',
                'arquivo' => $newName,
            ]);
        }
    }

    /**
     * @param array<string,mixed> $payload
     */
    private function persistAccessories(int $osId, array $payload): void
    {
        $entries = $this->decodeCollectionInput($payload['acessorios_data'] ?? null, $payload['acessorios'] ?? null);
        $numeroOs = (string) ((new OsModel())->find($osId)['numero_os'] ?? '');
        $filesMap = $this->collectAccessoryFiles();

        if (empty($entries) && empty($filesMap)) {
            return;
        }

        $model = new AcessorioOsModel();
        $fotoModel = new FotoAcessorioModel();
        $slug = $this->normalizeOsSlug($numeroOs);
        $folder = $this->ensureAccessoryDirectory($slug);
        $sequenceByType = [];

        foreach ($entries as $entry) {
            $description = trim((string) ($entry['text'] ?? ''));
            if ($description === '') {
                continue;
            }

            $values = $entry['values'] ?? null;
            $model->insert([
                'os_id' => $osId,
                'descricao' => $description,
                'tipo' => trim((string) ($entry['key'] ?? '')) !== '' ? trim((string) ($entry['key'] ?? '')) : null,
                'valores' => is_array($values) && !empty($values)
                    ? json_encode($values, JSON_UNESCAPED_UNICODE)
                    : null,
            ]);

            $acessorioId = (int) $model->getInsertID();
            if ($acessorioId <= 0) {
                continue;
            }

            $entryId = trim((string) ($entry['id'] ?? ''));
            $entryTypeSlug = $this->normalizeAccessoryTypeSlug((string) ($entry['key'] ?? ''), $description);
            if (!isset($sequenceByType[$entryTypeSlug])) {
                $sequenceByType[$entryTypeSlug] = $this->nextAccessorySequence($folder, $entryTypeSlug);
            }

            $entryFiles = $filesMap[$entryId] ?? [];
            foreach ($entryFiles as $file) {
                $this->saveAccessoryPhoto(
                    $file,
                    $folder,
                    $entryTypeSlug,
                    $sequenceByType[$entryTypeSlug],
                    $acessorioId,
                    $fotoModel
                );
            }
        }
    }

    /**
     * @return array<string,array<int,array{name:string,tmp_name:string}>>
     */
    private function collectAccessoryFiles(): array
    {
        $mapped = [];
        if (empty($_FILES['fotos_acessorios']['name'] ?? null)) {
            return $mapped;
        }

        foreach ($_FILES['fotos_acessorios']['name'] as $entryId => $files) {
            foreach ((array) $files as $index => $name) {
                $error = $_FILES['fotos_acessorios']['error'][$entryId][$index] ?? UPLOAD_ERR_NO_FILE;
                if ($error !== UPLOAD_ERR_OK) {
                    continue;
                }

                $tmpName = $_FILES['fotos_acessorios']['tmp_name'][$entryId][$index] ?? '';
                if ($tmpName === '' || !is_uploaded_file($tmpName)) {
                    continue;
                }

                $mapped[(string) $entryId][] = [
                    'name' => (string) $name,
                    'tmp_name' => $tmpName,
                ];
            }
        }

        return $mapped;
    }

    private function ensureAccessoryDirectory(string $slug): string
    {
        $base = rtrim(FCPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'acessorios' . DIRECTORY_SEPARATOR;
        if (!is_dir($base)) {
            @mkdir($base, 0775, true);
        }

        $path = $base . $slug . DIRECTORY_SEPARATOR;
        if (!is_dir($path)) {
            @mkdir($path, 0775, true);
        }

        if (!is_dir($path)) {
            throw new \RuntimeException('Diretorio de acessorios indisponivel.');
        }

        return $path;
    }

    /**
     * @param array{name:string,tmp_name:string} $file
     */
    private function saveAccessoryPhoto(array $file, string $folder, string $typeSlug, int &$sequence, int $acessorioId, FotoAcessorioModel $fotoModel): void
    {
        try {
            $extension = strtolower((string) pathinfo($file['name'], PATHINFO_EXTENSION));
            if ($extension === '') {
                $extension = 'jpg';
            }

            $name = '';
            $destination = '';
            while (true) {
                $seq = str_pad((string) $sequence, 2, '0', STR_PAD_LEFT);
                $name = "{$typeSlug}_{$seq}.{$extension}";
                $destination = $folder . $name;
                if (!is_file($destination)) {
                    break;
                }
                $sequence++;
            }

            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                throw new \RuntimeException('Falha ao mover foto de acessorio.');
            }

            $fotoModel->insert([
                'acessorio_id' => $acessorioId,
                'arquivo' => $name,
            ]);
            $sequence++;
        } catch (Throwable $e) {
            log_message('warning', '[OrdersController] erro ao salvar foto de acessorio: {message}', [
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @param mixed $encoded
     * @param mixed $fallback
     * @return array<int,array{text:string,key?:string,values?:array<string,mixed>}>
     */
    private function decodeCollectionInput($encoded, $fallback): array
    {
        $items = [];

        if (is_string($encoded) && trim($encoded) !== '') {
            $decoded = json_decode($encoded, true);
            if (is_array($decoded)) {
                $items = $decoded;
            }
        }

        if (empty($items) && is_array($fallback)) {
            $items = $fallback;
        }

        if (empty($items)) {
            return [];
        }

        $normalized = [];
        foreach ($items as $item) {
            if (is_string($item)) {
                $text = trim($item);
                if ($text === '') {
                    continue;
                }
                $normalized[] = ['text' => $text];
                continue;
            }

            if (!is_array($item)) {
                continue;
            }

            $text = trim((string) ($item['text'] ?? $item['descricao'] ?? $item['label'] ?? ''));
            if ($text === '') {
                continue;
            }

            $entry = ['text' => $text];
            $id = trim((string) ($item['id'] ?? ''));
            if ($id !== '') {
                $entry['id'] = $id;
            }
            $key = trim((string) ($item['key'] ?? $item['tipo'] ?? ''));
            if ($key !== '') {
                $entry['key'] = $key;
            }
            if (is_array($item['values'] ?? null)) {
                $entry['values'] = $item['values'];
            } elseif (is_array($item['valores'] ?? null)) {
                $entry['values'] = $item['valores'];
            }

            $normalized[] = $entry;
        }

        return $normalized;
    }

    /**
     * @param mixed $value
     */
    private function normalizePositiveInt($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $normalized = (int) $value;
        return $normalized > 0 ? $normalized : null;
    }

    private function normalizeStatusCode(string $status): string
    {
        return strtolower(trim($status));
    }

    private function normalizePriority(string $priority): string
    {
        $normalized = strtolower(trim($priority));
        return in_array($normalized, ['baixa', 'normal', 'alta', 'urgente'], true)
            ? $normalized
            : 'normal';
    }

    /**
     * @param mixed $value
     */
    private function normalizeNullableString($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);
        return $text !== '' ? $text : null;
    }

    /**
     * @param mixed $value
     */
    private function normalizeDateTimeInput($value, ?string $default = null): ?string
    {
        if ($value === null || $value === '') {
            return $default;
        }

        $raw = trim((string) $value);
        if ($raw === '') {
            return $default;
        }

        $timestamp = strtotime($raw);
        if ($timestamp === false) {
            return $default;
        }

        return date('Y-m-d H:i:s', $timestamp);
    }

    /**
     * @param mixed $value
     */
    private function normalizeDecimalValue($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $stringValue = trim((string) $value);
        if ($stringValue === '') {
            return null;
        }

        $normalized = str_replace(' ', '', $stringValue);
        $hasComma = str_contains($normalized, ',');
        $hasDot = str_contains($normalized, '.');
        if ($hasComma && $hasDot) {
            $normalized = str_replace('.', '', $normalized);
            $normalized = str_replace(',', '.', $normalized);
        } elseif ($hasComma) {
            $normalized = str_replace(',', '.', $normalized);
        }

        if (!is_numeric($normalized)) {
            return null;
        }

        return (float) $normalized;
    }

    /**
     * @param mixed $value
     * @return array<int,int>
     */
    private function normalizeIntegerList($value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (!is_array($value)) {
            $value = [$value];
        }

        $list = array_values(array_unique(array_filter(array_map(
            static fn ($item): int => (int) $item,
            $value
        ), static fn (int $id): bool => $id > 0)));

        return $list;
    }

    /**
     * @param array<string,mixed> $payload
     */
    private function persistChecklistEntrada(int $osId, string $numeroOs, int $equipamentoId, array $payload): void
    {
        if (!$this->isChecklistInfraReady()) {
            return;
        }

        $equipmentModel = new EquipamentoModel();
        $equipment = $equipmentModel->getWithCliente($equipamentoId) ?? $equipmentModel->find($equipamentoId);
        $tipoEquipamentoId = (int) ($equipment['tipo_id'] ?? 0);
        if ($tipoEquipamentoId <= 0) {
            return;
        }

        $rawChecklist = $payload['checklist_entrada_data'] ?? null;
        $filesByItem = $this->collectChecklistFiles('fotos_checklist_entrada');
        if ((empty($rawChecklist) || trim((string) $rawChecklist) === '') && empty($filesByItem)) {
            return;
        }

        $decoded = is_array($rawChecklist) ? $rawChecklist : json_decode((string) $rawChecklist, true);
        $checklistPayload = is_array($decoded) ? $decoded : [];
        if (!isset($checklistPayload['itens']) || !is_array($checklistPayload['itens'])) {
            $checklistPayload['itens'] = [];
        }
        $checklistPayload['tipo_equipamento_nome'] = (string) ($equipment['tipo_nome'] ?? '');

        try {
            (new ChecklistService())->saveExecution(
                $osId,
                $numeroOs,
                'entrada',
                $tipoEquipamentoId,
                $checklistPayload,
                $filesByItem
            );
        } catch (Throwable $e) {
            log_message('error', '[Mobile API][Checklist] falha ao salvar checklist na OS ' . $osId . ': ' . $e->getMessage());
        }
    }

    /**
     * @return array<string,array<int,\CodeIgniter\HTTP\Files\UploadedFile>>
     */
    private function collectChecklistFiles(string $field): array
    {
        $mapped = [];
        $allFiles = $this->request->getFiles();
        if (empty($allFiles[$field]) || !is_array($allFiles[$field])) {
            return $mapped;
        }

        foreach ($allFiles[$field] as $itemId => $files) {
            foreach ((array) $files as $file) {
                if ($file instanceof \CodeIgniter\HTTP\Files\UploadedFile && $file->isValid() && !$file->hasMoved()) {
                    $mapped[(string) $itemId][] = $file;
                }
            }
        }

        return $mapped;
    }

    private function normalizeAccessoryTypeSlug(string $entryKey, string $description = ''): string
    {
        $base = trim($entryKey);
        if ($base === '' || strtolower($base) === 'outro') {
            $base = trim($description);
        }
        if ($base === '') {
            $base = 'acessorio';
        }

        $normalized = str_replace(['-', ' '], '_', $base);
        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $normalized);
            if (is_string($converted) && $converted !== '') {
                $normalized = $converted;
            }
        }

        $slug = strtolower($normalized);
        $slug = preg_replace('/[^a-z0-9_]/', '', $slug) ?: '';
        $slug = trim($slug, '_');
        return $slug !== '' ? $slug : 'acessorio';
    }

    private function nextAccessorySequence(string $folder, string $typeSlug): int
    {
        $max = 0;
        foreach (glob($folder . $typeSlug . '_*') as $file) {
            $name = pathinfo($file, PATHINFO_FILENAME);
            if (!preg_match('/^' . preg_quote($typeSlug, '/') . '_(\\d+)$/', $name, $matches)) {
                continue;
            }
            $max = max($max, (int) ($matches[1] ?? 0));
        }
        return $max + 1;
    }

    private function normalizeOsSlug(string $numeroOs): string
    {
        $clean = preg_replace('/[^A-Za-z0-9_]/', '', str_replace('-', '_', $numeroOs)) ?? '';
        $clean = preg_replace('/^OS_?/i', '', $clean) ?? $clean;
        $clean = strtolower(trim($clean));
        return $clean !== '' ? $clean : 'os';
    }

    private function buildEquipmentPhotoUrl(string $arquivo): ?string
    {
        $arquivo = str_replace('\\', '/', ltrim($arquivo, '/'));
        if ($arquivo === '') {
            return null;
        }

        $perfilPath = rtrim(FCPATH, '\\/') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'equipamentos_perfil' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $arquivo);
        if (is_file($perfilPath)) {
            return base_url('uploads/equipamentos_perfil/' . $arquivo);
        }

        $legacyPerfil = rtrim(FCPATH, '\\/') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'equipamentos_perfil' . DIRECTORY_SEPARATOR . basename($arquivo);
        if (is_file($legacyPerfil)) {
            return base_url('uploads/equipamentos_perfil/' . basename($arquivo));
        }

        $legacyUpload = rtrim(FCPATH, '\\/') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'equipamentos' . DIRECTORY_SEPARATOR . basename($arquivo);
        if (is_file($legacyUpload)) {
            return base_url('uploads/equipamentos/' . basename($arquivo));
        }

        return null;
    }

    /**
     * @param array<int,array<string,mixed>> $items
     * @return array<int,array<string,mixed>>
     */
    private function hydrateOrderEquipmentPhotos(array $items): array
    {
        $equipmentIds = array_values(array_unique(array_filter(array_map(
            static fn (array $item): int => (int) ($item['equipamento_id'] ?? 0),
            $items
        ))));

        if (empty($equipmentIds)) {
            return array_map(static function (array $item): array {
                $item['equip_fotos'] = [];
                return $item;
            }, $items);
        }

        $grouped = $this->groupEquipmentPhotosByEquipmentIds($equipmentIds);

        foreach ($items as &$item) {
            $equipamentoId = (int) ($item['equipamento_id'] ?? 0);
            $item['equip_fotos'] = array_map(
                static fn (array $photo): array => [
                    'url' => (string) ($photo['url'] ?? ''),
                    'is_principal' => (int) ($photo['is_principal'] ?? 0),
                ],
                $grouped[$equipamentoId] ?? []
            );
        }
        unset($item);

        return $items;
    }

    /**
     * @param array<int,mixed> $equipmentIds
     * @return array<int,array<int,array<string,mixed>>>
     */
    private function groupEquipmentPhotosByEquipmentIds(array $equipmentIds): array
    {
        $equipmentIds = array_values(array_unique(array_filter(array_map(
            static fn ($value): int => (int) $value,
            $equipmentIds
        ))));

        if (empty($equipmentIds)) {
            return [];
        }

        $rows = (new EquipamentoFotoModel())
            ->select('equipamento_id, arquivo, is_principal, id')
            ->whereIn('equipamento_id', $equipmentIds)
            ->orderBy('is_principal', 'DESC')
            ->orderBy('id', 'ASC')
            ->findAll();

        $grouped = [];
        foreach ($rows as $row) {
            $equipamentoId = (int) ($row['equipamento_id'] ?? 0);
            if ($equipamentoId <= 0) {
                continue;
            }

            $grouped[$equipamentoId][] = [
                'id' => (int) ($row['id'] ?? 0),
                'url' => $this->buildEquipmentPhotoUrl((string) ($row['arquivo'] ?? '')),
                'is_principal' => (int) ($row['is_principal'] ?? 0),
            ];
        }

        return $grouped;
    }

    /**
     * @param array<string,mixed> $item
     * @return array<string,mixed>|null
     */
    private function resolveChecklistEntradaPayload(int $osId, array $item): ?array
    {
        if (!$this->isChecklistInfraReady()) {
            return null;
        }

        $equipamentoId = (int) ($item['equipamento_id'] ?? 0);
        if ($equipamentoId <= 0) {
            return null;
        }

        $equipmentModel = new EquipamentoModel();
        $equipment = $equipmentModel->getWithCliente($equipamentoId) ?? $equipmentModel->find($equipamentoId);
        $tipoEquipamentoId = (int) ($equipment['tipo_id'] ?? 0);
        if ($tipoEquipamentoId <= 0) {
            return null;
        }

        try {
            return (new ChecklistService())->getPayloadForOs(
                $osId,
                'entrada',
                $tipoEquipamentoId,
                (string) ($item['numero_os'] ?? '')
            );
        } catch (Throwable $e) {
            log_message('error', '[Mobile API][Checklist] falha ao montar payload da OS ' . $osId . ': ' . $e->getMessage());
            return null;
        }
    }

    private function isChecklistInfraReady(): bool
    {
        try {
            $db = \Config\Database::connect();
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
        } catch (Throwable $e) {
            log_message('error', '[Mobile API][Checklist] falha ao validar infraestrutura: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * @return array<string,mixed>
     */
    private function dispatchClientOpenNotification(int $osId, string $mode): array
    {
        $order = (new OsModel())->getComplete($osId);
        if (!$order) {
            return [
                'ok' => false,
                'mode' => $mode,
                'message' => 'OS nao encontrada para enviar notificacao ao cliente.',
            ];
        }

        $phone = trim((string) ($order['cliente_telefone'] ?? ''));
        if ($phone === '') {
            return [
                'ok' => false,
                'mode' => $mode,
                'message' => 'Cliente sem telefone cadastrado para notificacao via WhatsApp.',
            ];
        }

        $requestedMode = in_array($mode, ['message', 'message_pdf'], true) ? $mode : 'message';
        $effectiveMode = $requestedMode;
        $userId = $this->currentUserId() > 0 ? $this->currentUserId() : null;
        $whatsAppService = new WhatsAppService();

        $extra = [];
        $pdfUrl = '';
        $pdfResultPayload = null;
        if ($requestedMode === 'message_pdf') {
            $pdfResult = (new OsPdfService())->gerar($osId, 'abertura', $userId);
            if (empty($pdfResult['ok'])) {
                $effectiveMode = 'message';
                $pdfResultPayload = [
                    'ok' => false,
                    'message' => (string) ($pdfResult['message'] ?? 'Falha ao gerar PDF da OS para envio ao cliente.'),
                ];
            } else {
                $pdfUrl = (string) ($pdfResult['url'] ?? '');
                $extra = [
                    'pdf_url' => $pdfUrl,
                    'arquivo_path' => (string) ($pdfResult['path'] ?? ''),
                    'arquivo' => (string) ($pdfResult['relative'] ?? ''),
                ];
                $pdfResultPayload = [
                    'ok' => true,
                    'url' => $pdfUrl !== '' ? $pdfUrl : null,
                ];
            }
        }

        $templateResult = $whatsAppService->sendByTemplate($order, 'os_aberta', $userId, $extra);
        $pdfWarning = ($requestedMode === 'message_pdf' && $effectiveMode !== 'message_pdf' && !empty($pdfResultPayload['message']))
            ? ' PDF nao gerado automaticamente: ' . (string) $pdfResultPayload['message']
            : '';
        if (!empty($templateResult['ok'])) {
            return [
                'ok' => true,
                'mode' => $requestedMode,
                'effective_mode' => $effectiveMode,
                'method' => 'template',
                'provider' => (string) ($templateResult['provider'] ?? ''),
                'message_id' => $templateResult['message_id'] ?? null,
                'message' => (string) (($templateResult['message'] ?? 'Cliente notificado com sucesso.') . $pdfWarning),
                'pdf_url' => $pdfUrl !== '' ? $pdfUrl : null,
                'pdf' => $pdfResultPayload,
            ];
        }

        $fallbackMessage = $this->buildClientOpenNotificationFallbackMessage($order, $pdfUrl);
        $fallbackResult = $whatsAppService->sendRaw(
            $osId,
            (int) ($order['cliente_id'] ?? 0),
            $phone,
            $fallbackMessage,
            'os_aberta',
            null,
            $userId,
            $extra
        );

        if (!empty($fallbackResult['ok'])) {
            return [
                'ok' => true,
                'mode' => $requestedMode,
                'effective_mode' => $effectiveMode,
                'method' => 'fallback_manual',
                'provider' => (string) ($fallbackResult['provider'] ?? ''),
                'message_id' => $fallbackResult['message_id'] ?? null,
                'message' => (string) (($fallbackResult['message'] ?? 'Cliente notificado com sucesso (fallback).') . $pdfWarning),
                'pdf_url' => $pdfUrl !== '' ? $pdfUrl : null,
                'template_error' => (string) ($templateResult['message'] ?? ''),
                'pdf' => $pdfResultPayload,
            ];
        }

        return [
            'ok' => false,
            'mode' => $requestedMode,
            'effective_mode' => $effectiveMode,
            'method' => 'fallback_manual',
            'message' => (string) ($fallbackResult['message'] ?? $templateResult['message'] ?? 'Falha ao notificar cliente via WhatsApp.'),
            'provider' => (string) ($fallbackResult['provider'] ?? $templateResult['provider'] ?? ''),
            'pdf_url' => $pdfUrl !== '' ? $pdfUrl : null,
            'template_error' => (string) ($templateResult['message'] ?? ''),
            'fallback_error' => (string) ($fallbackResult['message'] ?? ''),
            'pdf' => $pdfResultPayload,
        ];
    }

    /**
     * @param array<string,mixed> $order
     */
    private function buildClientOpenNotificationFallbackMessage(array $order, string $pdfUrl = ''): string
    {
        $clientName = trim((string) ($order['cliente_nome'] ?? ''));
        $orderNumber = trim((string) ($order['numero_os'] ?? ''));
        $orderId = (int) ($order['id'] ?? 0);
        $entryDate = trim((string) ($order['data_abertura'] ?? ''));

        $message = $clientName !== ''
            ? 'Ola, ' . $clientName . '.'
            : 'Ola.';
        $message .= "\nSua OS " . ($orderNumber !== '' ? $orderNumber : ('#' . $orderId)) . ' foi aberta com sucesso.';

        if ($entryDate !== '') {
            $timestamp = strtotime($entryDate);
            if ($timestamp !== false) {
                $message .= "\nData de abertura: " . date('d/m/Y H:i', $timestamp) . '.';
            }
        }

        if ($pdfUrl !== '') {
            $message .= "\nPDF da OS: " . $pdfUrl;
        }

        return $message;
    }
}
