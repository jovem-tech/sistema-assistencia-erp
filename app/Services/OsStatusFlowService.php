<?php

namespace App\Services;

use App\Models\OsModel;
use App\Models\OsStatusHistoricoModel;
use App\Models\OsStatusModel;
use App\Models\OsStatusTransicaoModel;

class OsStatusFlowService
{
    private OsStatusModel $statusModel;
    private OsStatusTransicaoModel $transicaoModel;
    private OsStatusHistoricoModel $historicoModel;
    private OsModel $osModel;

    public function __construct()
    {
        $this->statusModel = new OsStatusModel();
        $this->transicaoModel = new OsStatusTransicaoModel();
        $this->historicoModel = new OsStatusHistoricoModel();
        $this->osModel = new OsModel();
    }

    public function getStatusGrouped(): array
    {
        if (! $this->statusModel->db->tableExists('os_status')) {
            return $this->legacyStatusGrouped();
        }

        return $this->statusModel->getActiveGrouped();
    }

    public function getStatusByCode(string $codigo): ?array
    {
        if (! $this->statusModel->db->tableExists('os_status')) {
            return null;
        }
        return $this->statusModel->byCode($codigo);
    }

    public function isTransitionAllowed(?string $fromCode, string $toCode): bool
    {
        if ($fromCode === null || $fromCode === '') {
            return true;
        }
        if ($fromCode === $toCode) {
            return true;
        }
        if (! $this->statusModel->db->tableExists('os_status_transicoes')) {
            return true;
        }

        $from = $this->statusModel->byCode($fromCode);
        $to = $this->statusModel->byCode($toCode);
        if (!$from || !$to) {
            return true;
        }

        $count = $this->transicaoModel
            ->where('status_origem_id', $from['id'])
            ->where('status_destino_id', $to['id'])
            ->where('ativo', 1)
            ->countAllResults();

        return $count > 0;
    }

    public function resolveEstadoFluxo(string $statusCode): string
    {
        $status = $this->getStatusByCode($statusCode);
        if (!empty($status['estado_fluxo_padrao'])) {
            return $status['estado_fluxo_padrao'];
        }

        return match ($statusCode) {
            'cancelado' => 'cancelado',
            'entregue_reparado', 'devolvido_sem_reparo', 'descartado', 'reparo_recusado', 'irreparavel' => 'encerrado',
            'reparado_disponivel_loja', 'garantia_concluida' => 'pronto',
            'aguardando_peca', 'pagamento_pendente', 'entregue_pagamento_pendente', 'aguardando_autorizacao' => 'pausado',
            'reparo_execucao', 'retrabalho', 'testes_operacionais', 'testes_finais', 'aguardando_reparo' => 'em_execucao',
            default => 'em_atendimento',
        };
    }

    public function applyStatus(int $osId, string $novoStatus, ?int $usuarioId = null, ?string $observacao = null): array
    {
        $os = $this->osModel->find($osId);
        if (!$os) {
            return ['ok' => false, 'message' => 'OS nao encontrada.'];
        }

        $statusAtual = (string)($os['status'] ?? '');
        if (!$this->isTransitionAllowed($statusAtual, $novoStatus)) {
            return [
                'ok' => false,
                'message' => "Transicao invalida: {$statusAtual} -> {$novoStatus}.",
            ];
        }

        $estadoFluxo = $this->resolveEstadoFluxo($novoStatus);
        $updateData = [
            'status' => $novoStatus,
            'estado_fluxo' => $estadoFluxo,
            'status_atualizado_em' => date('Y-m-d H:i:s'),
        ];

        if (in_array($novoStatus, ['reparo_concluido', 'reparado_disponivel_loja', 'garantia_concluida'], true)) {
            $updateData['data_conclusao'] = date('Y-m-d H:i:s');
            $updateData['garantia_validade'] = date('Y-m-d', strtotime('+' . ($os['garantia_dias'] ?? 90) . ' days'));
        }

        if (in_array($novoStatus, ['entregue_reparado', 'devolvido_sem_reparo'], true)) {
            $updateData['data_entrega'] = date('Y-m-d H:i:s');
        }

        $this->osModel->update($osId, $updateData);

        if ($this->historicoModel->db->tableExists('os_status_historico')) {
            $this->historicoModel->insert([
                'os_id' => $osId,
                'status_anterior' => $statusAtual ?: null,
                'status_novo' => $novoStatus,
                'estado_fluxo' => $estadoFluxo,
                'usuario_id' => $usuarioId,
                'observacao' => $observacao,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        return [
            'ok' => true,
            'os' => $this->osModel->find($osId),
            'status_anterior' => $statusAtual,
            'status_novo' => $novoStatus,
            'estado_fluxo' => $estadoFluxo,
        ];
    }

    public function buildTransitionHints(?string $statusAtual): array
    {
        $grouped = $this->getStatusGrouped();
        $flat = [];
        foreach ($grouped as $items) {
            foreach ($items as $s) {
                $flat[] = $s;
            }
        }

        if (!$statusAtual) {
            return $flat;
        }

        return array_values(array_filter($flat, function (array $status) use ($statusAtual): bool {
            return $status['codigo'] === $statusAtual || $this->isTransitionAllowed($statusAtual, $status['codigo']);
        }));
    }

    private function legacyStatusGrouped(): array
    {
        return [
            'diagnostico' => [
                ['codigo' => 'aguardando_analise', 'nome' => 'Aguardando Analise', 'grupo_macro' => 'diagnostico', 'cor' => 'secondary'],
            ],
            'orcamento' => [
                ['codigo' => 'aguardando_orcamento', 'nome' => 'Aguardando Orcamento', 'grupo_macro' => 'orcamento', 'cor' => 'info'],
                ['codigo' => 'aguardando_aprovacao', 'nome' => 'Aguardando Aprovacao', 'grupo_macro' => 'orcamento', 'cor' => 'purple'],
            ],
            'execucao' => [
                ['codigo' => 'aprovado', 'nome' => 'Aprovado', 'grupo_macro' => 'execucao', 'cor' => 'primary'],
                ['codigo' => 'em_reparo', 'nome' => 'Em Reparo', 'grupo_macro' => 'execucao', 'cor' => 'warning'],
                ['codigo' => 'aguardando_peca', 'nome' => 'Aguardando Peca', 'grupo_macro' => 'interrupcao', 'cor' => 'orange'],
            ],
            'concluido' => [
                ['codigo' => 'pronto', 'nome' => 'Pronto', 'grupo_macro' => 'concluido', 'cor' => 'success'],
                ['codigo' => 'entregue', 'nome' => 'Entregue', 'grupo_macro' => 'encerrado', 'cor' => 'dark'],
                ['codigo' => 'cancelado', 'nome' => 'Cancelado', 'grupo_macro' => 'cancelado', 'cor' => 'secondary'],
            ],
        ];
    }
}

