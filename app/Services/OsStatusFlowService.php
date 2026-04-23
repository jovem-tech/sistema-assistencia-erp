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

    public function getAllStatusesOrdered(): array
    {
        if (! $this->statusModel->db->tableExists('os_status')) {
            return [];
        }

        return $this->statusModel
            ->orderBy('ordem_fluxo', 'ASC')
            ->orderBy('nome', 'ASC')
            ->findAll();
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
        if ($toCode === 'cancelado') {
            return true;
        }
        if ($fromCode === null || $fromCode === '') {
            return true;
        }
        if ($fromCode === $toCode) {
            return true;
        }
        if (! $this->statusModel->db->tableExists('os_status_transicoes')) {
            return true;
        }

        return in_array($toCode, $this->getAllowedTransitionCodes($fromCode), true);
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
        $flat = [];
        foreach ($this->getStatusGrouped() as $items) {
            foreach ($items as $status) {
                $flat[] = $status;
            }
        }

        if (! $statusAtual) {
            return $flat;
        }

        $allowedCodes = array_merge([$statusAtual], $this->getAllowedTransitionCodes($statusAtual));

        return array_values(array_filter($flat, static function (array $status) use ($allowedCodes): bool {
            $code = (string) ($status['codigo'] ?? '');
            return $code !== '' && in_array($code, $allowedCodes, true);
        }));
    }

    public function getTransitionMap(bool $withFallback = true): array
    {
        $statuses = $this->getAllStatusesOrdered();
        if (empty($statuses)) {
            return [];
        }

        if (! $this->statusModel->db->tableExists('os_status_transicoes')) {
            return $withFallback ? $this->buildFallbackTransitionMap($statuses) : [];
        }

        if (! $this->hasConfiguredTransitions()) {
            return $withFallback ? $this->buildFallbackTransitionMap($statuses) : [];
        }

        $rows = $this->transicaoModel
            ->select('os_status_transicoes.status_origem_id, os_status_transicoes.status_destino_id, s1.codigo as origem_codigo, s2.codigo as destino_codigo')
            ->join('os_status s1', 's1.id = os_status_transicoes.status_origem_id')
            ->join('os_status s2', 's2.id = os_status_transicoes.status_destino_id')
            ->where('os_status_transicoes.ativo', 1)
            ->orderBy('s1.ordem_fluxo', 'ASC')
            ->orderBy('s2.ordem_fluxo', 'ASC')
            ->findAll();

        $map = [];
        foreach ($statuses as $status) {
            $code = (string) ($status['codigo'] ?? '');
            if ($code !== '') {
                $map[$code] = [];
            }
        }

        foreach ($rows as $row) {
            $origem = (string) ($row['origem_codigo'] ?? '');
            $destino = (string) ($row['destino_codigo'] ?? '');
            if ($origem === '' || $destino === '') {
                continue;
            }
            $map[$origem][] = $destino;
        }

        return $map;
    }

    public function hasConfiguredTransitions(): bool
    {
        if (! $this->statusModel->db->tableExists('os_status_transicoes')) {
            return false;
        }

        return $this->transicaoModel
            ->where('ativo', 1)
            ->countAllResults() > 0;
    }

    public function saveWorkflowConfig(array $statusPayload, array $transitionPayload): array
    {
        if (! $this->statusModel->db->tableExists('os_status') || ! $this->statusModel->db->tableExists('os_status_transicoes')) {
            return [
                'ok' => false,
                'message' => 'Tabelas de workflow da OS nao encontradas.',
            ];
        }

        $db = $this->statusModel->db;
        $db->transBegin();

        try {
            foreach ($this->getAllStatusesOrdered() as $status) {
                $statusId = (int) ($status['id'] ?? 0);
                if ($statusId <= 0) {
                    continue;
                }

                $payload = $statusPayload[$statusId] ?? [];
                $update = [
                    'ordem_fluxo' => (int) ($payload['ordem_fluxo'] ?? $status['ordem_fluxo'] ?? 0),
                    'ativo' => !empty($payload['ativo']) ? 1 : 0,
                    'status_final' => !empty($payload['status_final']) ? 1 : 0,
                    'status_pausa' => !empty($payload['status_pausa']) ? 1 : 0,
                ];

                $this->statusModel->update($statusId, $update);
            }

            $this->transicaoModel->where('id >', 0)->delete();

            $pairs = [];
            foreach ($transitionPayload as $originId => $destinations) {
                $originId = (int) $originId;
                if ($originId <= 0 || !is_array($destinations)) {
                    continue;
                }

                foreach ($destinations as $destinationId) {
                    $destinationId = (int) $destinationId;
                    if ($destinationId <= 0 || $destinationId === $originId) {
                        continue;
                    }

                    $pairKey = $originId . ':' . $destinationId;
                    $pairs[$pairKey] = [
                        'status_origem_id' => $originId,
                        'status_destino_id' => $destinationId,
                        'ativo' => 1,
                    ];
                }
            }

            if (!empty($pairs)) {
                $this->transicaoModel->insertBatch(array_values($pairs));
            }

            if ($db->transStatus() === false) {
                throw new \RuntimeException('Falha ao salvar o workflow da OS.');
            }

            $db->transCommit();

            return [
                'ok' => true,
                'message' => 'Fluxo de trabalho salvo com sucesso.',
            ];
        } catch (\Throwable $e) {
            $db->transRollback();

            return [
                'ok' => false,
                'message' => $e->getMessage() ?: 'Nao foi possivel salvar o workflow da OS.',
            ];
        }
    }

    private function getAllowedTransitionCodes(string $fromCode): array
    {
        $map = $this->getTransitionMap(true);
        $codes = $map[$fromCode] ?? [];
        $codes = array_values(array_unique(array_filter(array_map('strval', $codes))));
        return array_values(array_filter($codes, static fn (string $code): bool => $code !== $fromCode));
    }

    private function buildFallbackTransitionMap(array $statuses): array
    {
        $ordered = array_values(array_filter($statuses, static fn (array $status): bool => (int) ($status['ativo'] ?? 1) === 1));
        $map = [];

        foreach ($ordered as $index => $status) {
            $code = (string) ($status['codigo'] ?? '');
            if ($code === '') {
                continue;
            }

            $allowed = [];
            if (isset($ordered[$index - 1]['codigo'])) {
                $allowed[] = (string) $ordered[$index - 1]['codigo'];
            }
            if (isset($ordered[$index + 1]['codigo'])) {
                $allowed[] = (string) $ordered[$index + 1]['codigo'];
            }

            $map[$code] = array_values(array_unique(array_filter($allowed)));
        }

        return $map;
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

