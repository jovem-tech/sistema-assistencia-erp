<?php

namespace App\Services;

use App\Models\CrmFollowupModel;
use App\Models\OrcamentoModel;
use App\Models\OrcamentoStatusHistoricoModel;

class OrcamentoLifecycleService
{
    private OrcamentoModel $orcamentoModel;
    private OrcamentoStatusHistoricoModel $historicoModel;
    private CrmFollowupModel $followupModel;
    private CrmService $crmService;

    public function __construct()
    {
        $this->orcamentoModel = new OrcamentoModel();
        $this->historicoModel = new OrcamentoStatusHistoricoModel();
        $this->followupModel = new CrmFollowupModel();
        $this->crmService = new CrmService();
    }

    /**
     * @return array<string,int>
     */
    public function runAutomations(?int $usuarioId = null): array
    {
        $summary = [
            'orcamentos_vencidos' => 0,
            'followups_aguardando' => 0,
            'followups_vencidos' => 0,
            'followups_pendente_os' => 0,
        ];

        if (!$this->orcamentoModel->db->tableExists('orcamentos')) {
            return $summary;
        }

        $today = date('Y-m-d');
        $summary['orcamentos_vencidos'] = $this->processExpiredOrcamentos($today, $usuarioId, $summary);
        $summary['followups_aguardando'] = $this->processAguardandoFollowups($today, $usuarioId);
        $summary['followups_pendente_os'] = $this->processPendingOsFollowups($usuarioId);

        return $summary;
    }

    public function ensurePendingOsFollowup(array $orcamento, ?int $usuarioId = null): ?int
    {
        $status = (string) ($orcamento['status'] ?? '');
        if ($status !== OrcamentoModel::STATUS_PENDENTE_OS) {
            return null;
        }

        $orcamentoId = (int) ($orcamento['id'] ?? 0);
        if ($orcamentoId <= 0) {
            return null;
        }

        $numero = trim((string) ($orcamento['numero'] ?? ('#' . $orcamentoId)));
        $origin = 'orcamento_pendente_os_' . $orcamentoId;
        return $this->createFollowupIfMissing(
            $orcamento,
            $origin,
            'Abrir OS do orcamento aprovado ' . $numero,
            'Cliente aprovou o orcamento ' . $numero . '. Abrir OS para iniciar a execucao.',
            date('Y-m-d H:i:s', strtotime('+4 hours')),
            $usuarioId
        );
    }

    /**
     * @param array<string,int> $summary
     */
    private function processExpiredOrcamentos(string $today, ?int $usuarioId, array &$summary): int
    {
        $rows = $this->orcamentoModel
            ->whereIn('status', [OrcamentoModel::STATUS_ENVIADO, OrcamentoModel::STATUS_AGUARDANDO])
            ->where('validade_data IS NOT NULL', null, false)
            ->where('validade_data <', $today)
            ->findAll(1000);

        if (empty($rows)) {
            return 0;
        }

        $changed = 0;
        $now = date('Y-m-d H:i:s');
        foreach ($rows as $row) {
            $orcamentoId = (int) ($row['id'] ?? 0);
            if ($orcamentoId <= 0) {
                continue;
            }

            $statusAnterior = (string) ($row['status'] ?? '');
            if ($statusAnterior === OrcamentoModel::STATUS_VENCIDO) {
                continue;
            }

            $this->orcamentoModel->update($orcamentoId, [
                'status' => OrcamentoModel::STATUS_VENCIDO,
                'atualizado_por' => $usuarioId > 0 ? $usuarioId : null,
            ]);

            $this->historicoModel->insert([
                'orcamento_id' => $orcamentoId,
                'status_anterior' => $statusAnterior,
                'status_novo' => OrcamentoModel::STATUS_VENCIDO,
                'observacao' => 'Orcamento marcado como vencido automaticamente pela data de validade.',
                'origem' => 'automacao',
                'alterado_por' => $usuarioId > 0 ? $usuarioId : null,
                'created_at' => $now,
            ]);

            $changed++;

            $origin = 'orcamento_vencido_' . $orcamentoId;
            $numero = trim((string) ($row['numero'] ?? ('#' . $orcamentoId)));
            $followupId = $this->createFollowupIfMissing(
                $row,
                $origin,
                'Retomar orcamento vencido ' . $numero,
                'Orcamento ' . $numero . ' venceu sem resposta. Avaliar contato de follow-up.',
                date('Y-m-d H:i:s', strtotime('+1 day')),
                $usuarioId
            );
            if ($followupId) {
                $summary['followups_vencidos']++;
            }
        }

        return $changed;
    }

    private function processAguardandoFollowups(string $today, ?int $usuarioId): int
    {
        $rows = $this->orcamentoModel
            ->where('status', OrcamentoModel::STATUS_AGUARDANDO)
            ->where('validade_data IS NOT NULL', null, false)
            ->where('validade_data >=', $today)
            ->findAll(1000);

        if (empty($rows)) {
            return 0;
        }

        $created = 0;
        foreach ($rows as $row) {
            $orcamentoId = (int) ($row['id'] ?? 0);
            if ($orcamentoId <= 0) {
                continue;
            }

            $origin = 'orcamento_aguardando_' . $orcamentoId;
            $numero = trim((string) ($row['numero'] ?? ('#' . $orcamentoId)));
            $validadeData = trim((string) ($row['validade_data'] ?? ''));
            $schedule = strtotime($validadeData . ' 10:00:00 -1 day');
            if ($schedule === false || $schedule < time()) {
                $schedule = strtotime('+4 hours');
            }

            $followupId = $this->createFollowupIfMissing(
                $row,
                $origin,
                'Follow-up do orcamento ' . $numero,
                'Orcamento ' . $numero . ' segue aguardando resposta do cliente.',
                date('Y-m-d H:i:s', $schedule),
                $usuarioId
            );
            if ($followupId) {
                $created++;
            }
        }

        return $created;
    }

    private function processPendingOsFollowups(?int $usuarioId): int
    {
        $rows = $this->orcamentoModel
            ->where('status', OrcamentoModel::STATUS_PENDENTE_OS)
            ->findAll(1000);

        if (empty($rows)) {
            return 0;
        }

        $created = 0;
        foreach ($rows as $row) {
            if ($this->ensurePendingOsFollowup($row, $usuarioId)) {
                $created++;
            }
        }

        return $created;
    }

    private function createFollowupIfMissing(
        array $orcamento,
        string $origin,
        string $titulo,
        string $descricao,
        string $dataPrevista,
        ?int $usuarioId = null
    ): ?int {
        if (!$this->followupModel->db->tableExists('crm_followups')) {
            return null;
        }

        $clienteId = (int) ($orcamento['cliente_id'] ?? 0);
        $osId = (int) ($orcamento['os_id'] ?? 0);
        if ($clienteId <= 0 && $osId <= 0) {
            return null;
        }

        $exists = $this->followupModel
            ->where('origem_evento', $origin)
            ->countAllResults();
        if ($exists > 0) {
            return null;
        }

        return $this->crmService->createFollowup([
            'cliente_id' => $clienteId > 0 ? $clienteId : null,
            'os_id' => $osId > 0 ? $osId : null,
            'titulo' => $titulo,
            'descricao' => $descricao,
            'data_prevista' => $dataPrevista,
            'status' => 'pendente',
            'usuario_responsavel' => (int) ($orcamento['responsavel_id'] ?? 0) > 0
                ? (int) $orcamento['responsavel_id']
                : ($usuarioId > 0 ? $usuarioId : null),
            'origem_evento' => $origin,
        ]);
    }
}

