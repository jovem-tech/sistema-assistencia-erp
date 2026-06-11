<?php

namespace App\Services;

use App\Models\EquipamentoModel;
use App\Models\EquipamentoLifecycleHistoricoModel;
use App\Models\CrmFollowupModel;
use App\Models\FinanceiroModel;
use App\Models\FinanceiroMovimentoCartaoModel;
use App\Models\LogModel;
use App\Models\OsCobrancaAgendamentoModel;
use App\Models\OsModel;
use App\Models\OsStatusHistoricoModel;
use App\Services\FinanceiroCartaoService;

class OsSettlementService
{
    private OsModel $osModel;
    private FinanceiroModel $financeiroModel;
    private OsStatusHistoricoModel $historicoModel;
    private OsCobrancaAgendamentoModel $cobrancaModel;
    private FinanceiroMovimentoCartaoModel $movimentoCartaoModel;
    private FinanceiroCartaoService $cartaoService;
    private CrmService $crmService;

    public function __construct()
    {
        $this->osModel = new OsModel();
        $this->financeiroModel = new FinanceiroModel();
        $this->historicoModel = new OsStatusHistoricoModel();
        $this->cobrancaModel = new OsCobrancaAgendamentoModel();
        $this->movimentoCartaoModel = new FinanceiroMovimentoCartaoModel();
        $this->cartaoService = new FinanceiroCartaoService();
        $this->crmService = new CrmService();
    }

    public function buildCostSummary(int $osId): array
    {
        $db = $this->osModel->db;
        if (! $db->tableExists('os_itens') || ! $db->fieldExists('preco_custo_referencia', 'os_itens')) {
            return [
                'pecas' => 0.0,
                'servicos' => 0.0,
                'total' => 0.0,
            ];
        }

        $rows = $db->table('os_itens')
            ->select('tipo, COALESCE(SUM(COALESCE(preco_custo_referencia, 0) * COALESCE(quantidade, 1)), 0) as total', false)
            ->where('os_id', $osId)
            ->groupBy('tipo')
            ->get()
            ->getResultArray();

        $summary = [
            'pecas' => 0.0,
            'servicos' => 0.0,
            'total' => 0.0,
        ];

        foreach ($rows as $row) {
            $tipo = strtolower(trim((string) ($row['tipo'] ?? '')));
            $valor = round((float) ($row['total'] ?? 0), 2);
            if ($tipo === 'peca') {
                $summary['pecas'] = $valor;
            } elseif ($tipo === 'servico') {
                $summary['servicos'] = $valor;
            }
            $summary['total'] += $valor;
        }

        $summary['total'] = round($summary['total'], 2);
        return $summary;
    }

    public function ensureReceivableTitle(int $osId, ?array $os = null): ?array
    {
        $os = $os ?? $this->osModel->find($osId);
        if (! $os) {
            return null;
        }

        $valorFinal = round((float) ($os['valor_final'] ?? 0), 2);
        if ($valorFinal <= 0) {
            return null;
        }

        $title = $this->financeiroModel
            ->where('os_id', $osId)
            ->where('tipo', 'receber')
            ->orderBy('id', 'DESC')
            ->first();

        if ($title) {
            $summary = $this->financeiroModel->hasMovementSupport()
                ? $this->financeiroModel->getMovementSummaryForTitle((int) ($title['id'] ?? 0), $title)
                : ['total_movimentos' => 0];

            if ((int) ($summary['total_movimentos'] ?? 0) <= 0 && round((float) ($title['valor'] ?? 0), 2) !== $valorFinal) {
                $this->financeiroModel->update((int) $title['id'], [
                    'valor' => $valorFinal,
                    'status' => 'pendente',
                    'data_pagamento' => null,
                    'forma_pagamento' => null,
                ]);
                $title = $this->financeiroModel->find((int) $title['id']);
            }

            return is_array($title) ? $title : null;
        }

        $this->financeiroModel->insert([
            'os_id' => $osId,
            'tipo' => 'receber',
            'categoria' => 'Serviço',
            'descricao' => 'OS ' . trim((string) ($os['numero_os'] ?? ('#' . $osId))),
            'valor' => $valorFinal,
            'status' => 'pendente',
            'data_vencimento' => date('Y-m-d'),
            'impacta_dre' => 1,
            'impacta_fluxo_caixa' => 1,
            'dre_fixo_mensal' => 0,
        ]);

        $financeiroId = (int) $this->financeiroModel->getInsertID();
        return $financeiroId > 0 ? $this->financeiroModel->find($financeiroId) : null;
    }

    public function forceStatusUpdate(
        int $osId,
        string $statusCode,
        ?int $usuarioId = null,
        ?string $observacao = null,
        array $extraData = []
    ): ?array {
        $os = $this->osModel->find($osId);
        if (! $os) {
            return null;
        }

        $statusCode = strtolower(trim($statusCode));
        if ($statusCode === '') {
            return $os;
        }

        $updateData = [
            'status' => $statusCode,
            'estado_fluxo' => (new OsStatusFlowService())->resolveEstadoFluxo($statusCode),
            'status_atualizado_em' => date('Y-m-d H:i:s'),
        ];

        if (OsStatusFlowService::shouldSetConclusaoDate($statusCode) && empty($os['data_conclusao'])) {
            $updateData['data_conclusao'] = date('Y-m-d H:i:s');
        }

        if (! empty($extraData['data_entrega'])) {
            $updateData['data_entrega'] = $this->normalizeDateTimeOrDate((string) $extraData['data_entrega']);
        } elseif (OsStatusFlowService::shouldSetEntregaDate($statusCode) && empty($os['data_entrega'])) {
            $updateData['data_entrega'] = date('Y-m-d H:i:s');
        }

        if (OsStatusFlowService::shouldUpdateGarantiaValidade($statusCode)) {
            $garantiaDias = max(0, (int) ($extraData['garantia_dias'] ?? $os['garantia_dias'] ?? 90));
            $conclusaoBase = (string) ($updateData['data_conclusao'] ?? $os['data_conclusao'] ?? date('Y-m-d H:i:s'));
            $updateData['garantia_validade'] = date('Y-m-d', strtotime(date('Y-m-d', strtotime($conclusaoBase)) . ' +' . $garantiaDias . ' days'));
        }

        foreach (['status_final_pendente_pagamento', 'baixa_tecnica_em', 'baixa_tecnica_por', 'forma_pagamento'] as $field) {
            if (array_key_exists($field, $extraData)) {
                $updateData[$field] = $extraData[$field];
            }
        }

        $this->osModel->update($osId, $updateData);

        if ($this->historicoModel->db->tableExists('os_status_historico')) {
            $this->historicoModel->insert([
                'os_id' => $osId,
                'status_anterior' => (string) ($os['status'] ?? ''),
                'status_novo' => $statusCode,
                'estado_fluxo' => (string) ($updateData['estado_fluxo'] ?? ''),
                'usuario_id' => $usuarioId,
                'observacao' => $observacao !== null && trim($observacao) !== ''
                    ? $observacao
                    : 'Baixa/encerramento da OS processado pelo sistema.',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        if ($statusCode === 'descartado') {
            $this->closeEquipmentWhenOsDiscarded($osId, $os);
        }

        return $this->osModel->find($osId);
    }

    private function closeEquipmentWhenOsDiscarded(int $osId, array $os): void
    {
        $equipamentoId = (int) ($os['equipamento_id'] ?? 0);
        if ($equipamentoId <= 0) {
            return;
        }

        $equipamentoModel = new EquipamentoModel();
        if (! $equipamentoModel->supportsOperationalLifecycle()) {
            return;
        }

        $equipamento = $equipamentoModel
            ->select('id, status_operacional, motivo_encerramento, observacao_encerramento, encerrado_em')
            ->find($equipamentoId);

        if (! is_array($equipamento) || $equipamento === []) {
            log_message(
                'warning',
                '[OsSettlementService] Nao foi possivel localizar o equipamento {equipamento_id} para encerramento automatico da OS {os_id}.',
                [
                    'equipamento_id' => $equipamentoId,
                    'os_id' => $osId,
                ]
            );
            return;
        }

        if (equipamento_esta_encerrado($equipamento)) {
            return;
        }

        $numeroOs = trim((string) ($os['numero_os'] ?? ('#' . $osId)));
        $observacao = 'Encerrado automaticamente porque a OS ' . $numeroOs . ' foi finalizada como descartada.';

        if (! $equipamentoModel->update($equipamentoId, [
            'status_operacional' => 'encerrado',
            'motivo_encerramento' => 'descartado',
            'observacao_encerramento' => $observacao,
            'encerrado_em' => date('Y-m-d H:i:s'),
        ])) {
            log_message(
                'error',
                '[OsSettlementService] Falha ao encerrar automaticamente o equipamento {equipamento_id} da OS {os_id}: {errors}',
                [
                    'equipamento_id' => $equipamentoId,
                    'os_id' => $osId,
                    'errors' => json_encode(
                        $equipamentoModel->errors(),
                        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                    ) ?: 'erro desconhecido',
                ]
            );
            return;
        }

        $historicoModel = new EquipamentoLifecycleHistoricoModel();
        if ($historicoModel->supportsLifecycleHistory()) {
            $historicoModel->registrarEvento($equipamentoId, 'encerrado_automaticamente', [
                'os_id' => $osId,
                'motivo' => 'descartado',
                'observacao' => $observacao,
                'status_anterior' => (string) ($equipamento['status_operacional'] ?? 'ativo'),
                'status_novo' => 'encerrado',
            ]);
        }

        LogModel::registrar(
            'equipamento_encerrado_automaticamente',
            'Equipamento ID ' . $equipamentoId
                . ' encerrado automaticamente porque a OS ' . $numeroOs
                . ' foi marcada como descartada.'
        );
    }

    public function registerCardMovementMeta(int $movementId, array $simulation, array $paymentRow = []): void
    {
        if ($movementId <= 0 || ! $this->movimentoCartaoModel->db->tableExists('financeiro_movimentos_cartao')) {
            return;
        }

        $this->movimentoCartaoModel->insert([
            'movimento_id' => $movementId,
            'operadora_id' => (int) ($simulation['operadora']['id'] ?? 0) > 0 ? (int) $simulation['operadora']['id'] : null,
            'bandeira_id' => (int) ($simulation['bandeira']['id'] ?? 0) > 0 ? (int) $simulation['bandeira']['id'] : null,
            'taxa_id' => (int) ($simulation['taxa']['id'] ?? 0) > 0 ? (int) $simulation['taxa']['id'] : null,
            'modalidade' => (string) ($simulation['modalidade'] ?? 'credito'),
            'parcelas' => (int) ($simulation['parcelas'] ?? 1),
            'valor_bruto' => round((float) ($simulation['valor_bruto'] ?? 0), 2),
            'taxa_percentual' => round((float) ($simulation['taxa_percentual'] ?? 0), 4),
            'taxa_fixa' => round((float) ($simulation['taxa_fixa'] ?? 0), 2),
            'valor_taxa' => round((float) ($simulation['valor_taxa'] ?? 0), 2),
            'valor_liquido' => round((float) ($simulation['valor_liquido'] ?? 0), 2),
            'prazo_recebimento_dias' => (int) ($simulation['prazo_recebimento_dias'] ?? 0),
            'data_prevista_recebimento' => (string) ($simulation['data_prevista_recebimento'] ?? ''),
            'observacoes' => trim((string) ($paymentRow['observacoes'] ?? '')) ?: null,
        ]);
    }

    public function registerCardFeeExpense(array $os, array $simulation, string $dataPagamento, ?int $usuarioId = null): ?int
    {
        $valorTaxa = round((float) ($simulation['valor_taxa'] ?? 0), 2);
        if ($valorTaxa <= 0) {
            return null;
        }

        $numeroOs = trim((string) ($os['numero_os'] ?? ('#' . (int) ($os['id'] ?? 0))));
        $operadora = trim((string) ($simulation['operadora']['nome'] ?? 'Operadora'));
        $modalidadeLabel = trim((string) ($simulation['modalidade_label'] ?? 'Cartão'));
        $parcelas = max(1, (int) ($simulation['parcelas'] ?? 1));

        $this->financeiroModel->insert([
            'os_id' => (int) ($os['id'] ?? 0) > 0 ? (int) $os['id'] : null,
            'tipo' => 'pagar',
            'categoria' => 'Taxa de cartão',
            'descricao' => sprintf(
                'Taxa %s - OS %s (%s%s)',
                $operadora,
                $numeroOs,
                $modalidadeLabel,
                $parcelas > 1 ? ' em ' . $parcelas . 'x' : ''
            ),
            'valor' => $valorTaxa,
            'status' => 'pago',
            'data_vencimento' => substr($dataPagamento, 0, 10),
            'data_pagamento' => substr($dataPagamento, 0, 10),
            'forma_pagamento' => (string) ($simulation['modalidade'] ?? '') === 'debito' ? 'cartao_debito' : 'cartao_credito',
            'observacoes' => 'Despesa criada automaticamente na baixa da OS para registrar o custo líquido da operadora.',
            'impacta_dre' => 1,
            'impacta_fluxo_caixa' => 1,
            'dre_fixo_mensal' => 0,
        ]);

        $financeiroId = (int) $this->financeiroModel->getInsertID();
        if ($financeiroId <= 0) {
            return null;
        }

        $this->financeiroModel->finalizeTitleAfterSave($financeiroId, [
            'data_pagamento' => substr($dataPagamento, 0, 10),
            'forma_pagamento' => (string) ($simulation['modalidade'] ?? '') === 'debito' ? 'cartao_debito' : 'cartao_credito',
            'impacta_fluxo_caixa' => 1,
        ]);

        return $financeiroId;
    }

    public function createReturnFollowup(int $osId, string $dataPrevista, ?int $usuarioId = null): ?int
    {
        $os = $this->osModel->getComplete($osId);
        if (! $os) {
            return null;
        }

        $origin = 'os_retorno_agendado_' . $osId . '_' . date('Ymd', strtotime($dataPrevista));
        $followupModel = new CrmFollowupModel();
        if ($followupModel->db->tableExists('crm_followups')) {
            $exists = $followupModel->where('origem_evento', $origin)->countAllResults();
            if ($exists > 0) {
                return null;
            }
        }

        return $this->crmService->createFollowup([
            'cliente_id' => (int) ($os['cliente_id'] ?? 0) > 0 ? (int) $os['cliente_id'] : null,
            'os_id' => $osId,
            'titulo' => 'Retorno pós-serviço da OS ' . trim((string) ($os['numero_os'] ?? ('#' . $osId))),
            'descricao' => 'Retorno agendado automaticamente na baixa da OS para revisar satisfação e novas necessidades do cliente.',
            'data_prevista' => $this->normalizeDateTimeOrDate($dataPrevista, '10:00:00'),
            'status' => 'pendente',
            'usuario_responsavel' => $usuarioId,
            'origem_evento' => $origin,
        ]);
    }

    public function schedulePendingCollections(int $osId, ?int $financeiroId, ?int $clienteId, array $dias = [1, 3, 5]): int
    {
        if (! $this->cobrancaModel->db->tableExists('os_cobranca_agendamentos')) {
            return 0;
        }

        $this->cancelPendingCollections($osId);
        $created = 0;

        foreach ($dias as $prazoDia) {
            $prazoDia = max(1, (int) $prazoDia);
            $this->cobrancaModel->insert([
                'os_id' => $osId,
                'financeiro_id' => $financeiroId,
                'cliente_id' => $clienteId,
                'canal' => 'whatsapp',
                'prazo_dias' => $prazoDia,
                'enviar_em' => date('Y-m-d 10:00:00', strtotime('+' . $prazoDia . ' days')),
                'status' => 'pendente',
            ]);
            $created++;
        }

        return $created;
    }

    public function cancelPendingCollections(int $osId): int
    {
        if (! $this->cobrancaModel->db->tableExists('os_cobranca_agendamentos')) {
            return 0;
        }

        return $this->cobrancaModel
            ->where('os_id', $osId)
            ->whereIn('status', ['pendente', 'erro'])
            ->set([
                'status' => 'cancelado',
                'updated_at' => date('Y-m-d H:i:s'),
            ])
            ->update();
    }

    public function syncClosureStatusFromFinanceiro(int $financeiroId, ?int $usuarioId = null): ?array
    {
        $titulo = $this->financeiroModel->find($financeiroId);
        if (! $titulo || (string) ($titulo['tipo'] ?? '') !== 'receber') {
            return null;
        }

        $osId = (int) ($titulo['os_id'] ?? 0);
        if ($osId <= 0) {
            return null;
        }

        $os = $this->osModel->find($osId);
        if (! $os) {
            return null;
        }

        $summary = $this->financeiroModel->hasMovementSupport()
            ? $this->financeiroModel->getMovementSummaryForTitle($financeiroId, $titulo)
            : [];

        if ((string) ($summary['status_resolvido'] ?? $titulo['status'] ?? '') !== 'pago') {
            return [
                'changed' => false,
                'status' => (string) ($os['status'] ?? ''),
            ];
        }

        $this->cancelPendingCollections($osId);

        if ((string) ($os['status'] ?? '') !== 'entregue_pagamento_pendente') {
            return [
                'changed' => false,
                'status' => (string) ($os['status'] ?? ''),
            ];
        }

        $statusFinal = trim((string) ($os['status_final_pendente_pagamento'] ?? ''));
        if ($statusFinal === '') {
            $statusFinal = 'entregue_reparado';
        }

        $this->forceStatusUpdate(
            $osId,
            $statusFinal,
            $usuarioId,
            'Liquidação financeira da OS concluída automaticamente após quitação total.',
            [
                'status_final_pendente_pagamento' => null,
            ]
        );

        try {
            $this->crmService->registerOsEvent(
                $osId,
                'os_pagamento_regularizado',
                'Pagamento regularizado',
                'A OS foi encerrada definitivamente após a quitação total do título financeiro.',
                $usuarioId
            );
        } catch (\Throwable $e) {
            log_message('warning', '[OsSettlementService] Falha ao registrar evento CRM de quitação da OS {os_id}: {message}', [
                'os_id' => $osId,
                'message' => $e->getMessage(),
            ]);
        }

        return [
            'changed' => true,
            'status' => $statusFinal,
        ];
    }

    public function processPendingChargeNotifications(?int $usuarioId = null): array
    {
        $summary = [
            'agendamentos_lidos' => 0,
            'agendamentos_enviados' => 0,
            'agendamentos_cancelados' => 0,
            'agendamentos_com_erro' => 0,
        ];

        if (! $this->cobrancaModel->db->tableExists('os_cobranca_agendamentos')) {
            return $summary;
        }

        $rows = $this->cobrancaModel
            ->whereIn('status', ['pendente', 'erro'])
            ->where('enviar_em <=', date('Y-m-d H:i:s'))
            ->orderBy('enviar_em', 'ASC')
            ->findAll(200);

        $summary['agendamentos_lidos'] = count($rows);

        if ($rows === []) {
            return $summary;
        }

        $whatsApp = new WhatsAppService();

        foreach ($rows as $row) {
            $osId = (int) ($row['os_id'] ?? 0);
            $financeiroId = (int) ($row['financeiro_id'] ?? 0);

            $os = $this->osModel->getComplete($osId);
            $titulo = $financeiroId > 0 ? $this->financeiroModel->find($financeiroId) : null;

            if (! $os || ! $titulo || (string) ($os['status'] ?? '') !== 'entregue_pagamento_pendente') {
                $this->cobrancaModel->update((int) $row['id'], [
                    'status' => 'cancelado',
                    'ultima_tentativa_em' => date('Y-m-d H:i:s'),
                ]);
                $summary['agendamentos_cancelados']++;
                continue;
            }

            $movementSummary = $this->financeiroModel->hasMovementSupport()
                ? $this->financeiroModel->getMovementSummaryForTitle($financeiroId, $titulo)
                : [];
            $saldoAberto = round((float) ($movementSummary['valor_aberto'] ?? $titulo['valor'] ?? 0), 2);

            if ($saldoAberto <= 0.009) {
                $this->cobrancaModel->update((int) $row['id'], [
                    'status' => 'cancelado',
                    'ultima_tentativa_em' => date('Y-m-d H:i:s'),
                ]);
                $summary['agendamentos_cancelados']++;
                continue;
            }

            $telefone = trim((string) ($os['cliente_telefone'] ?? ''));
            if ($telefone === '') {
                $this->cobrancaModel->update((int) $row['id'], [
                    'status' => 'erro',
                    'ultima_tentativa_em' => date('Y-m-d H:i:s'),
                    'retorno_payload' => json_encode(['ok' => false, 'message' => 'Cliente sem telefone cadastrado.'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ]);
                $summary['agendamentos_com_erro']++;
                continue;
            }

            $mensagem = $this->buildPendingChargeMessage($os, $saldoAberto, (int) ($row['prazo_dias'] ?? 1));
            $send = $whatsApp->sendRaw(
                (int) ($os['id'] ?? 0),
                (int) ($os['cliente_id'] ?? 0),
                $telefone,
                $mensagem,
                'os_cobranca_pagamento_pendente',
                null,
                $usuarioId,
                ['enviada_por_bot' => true]
            );

            $update = [
                'ultima_tentativa_em' => date('Y-m-d H:i:s'),
                'mensagem_enviada' => $mensagem,
                'retorno_payload' => json_encode($send, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ];

            if (! empty($send['ok'])) {
                $update['status'] = 'enviado';
                $update['enviado_em'] = date('Y-m-d H:i:s');
                $summary['agendamentos_enviados']++;
            } else {
                $update['status'] = 'erro';
                $summary['agendamentos_com_erro']++;
            }

            $this->cobrancaModel->update((int) $row['id'], $update);
        }

        return $summary;
    }

    private function buildPendingChargeMessage(array $os, float $saldoAberto, int $prazoDia): string
    {
        $cliente = trim((string) ($os['cliente_nome'] ?? 'cliente'));
        $numeroOs = trim((string) ($os['numero_os'] ?? ('#' . (int) ($os['id'] ?? 0))));

        return sprintf(
            'Olá, %s. A OS %s já foi concluída e ainda consta um saldo pendente de R$ %s. Este é um lembrete automático do %dº dia após a entrega. Se preferir, responda esta mensagem para combinarmos a quitação.',
            $cliente !== '' ? $cliente : 'cliente',
            $numeroOs,
            number_format($saldoAberto, 2, ',', '.'),
            $prazoDia
        );
    }

    private function normalizeDateTimeOrDate(string $value, string $fallbackTime = '00:00:00'): string
    {
        $raw = trim($value);
        if ($raw === '') {
            return date('Y-m-d H:i:s');
        }

        $timestamp = strtotime($raw);
        if ($timestamp === false) {
            return date('Y-m-d H:i:s');
        }

        $hasTime = preg_match('/\d{2}:\d{2}/', $raw) === 1;
        return $hasTime
            ? date('Y-m-d H:i:s', $timestamp)
            : date('Y-m-d ' . $fallbackTime, $timestamp);
    }
}
