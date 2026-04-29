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
            'orcamentos_pacote_pendente' => 0,
            'followups_aguardando' => 0,
            'followups_vencidos' => 0,
            'followups_pendente_os' => 0,
            'followups_pacote_pendente' => 0,
        ];

        if (!$this->orcamentoModel->db->tableExists('orcamentos')) {
            return $summary;
        }

        $today = date('Y-m-d');
        $summary['orcamentos_pacote_pendente'] = $this->processPacoteAwaitingChoiceExpiry($usuarioId, $summary);
        $summary['orcamentos_vencidos'] = $this->processExpiredOrcamentos($today, $usuarioId, $summary);
        $summary['followups_aguardando'] = $this->processAguardandoFollowups($today, $usuarioId);
        $summary['followups_pendente_os'] = $this->processPendingOsFollowups($usuarioId);

        return $summary;
    }

    public function syncPacoteAwaitingChoiceExpiry(?int $usuarioId = null): int
    {
        $summary = [
            'followups_pacote_pendente' => 0,
        ];

        return $this->processPacoteAwaitingChoiceExpiry($usuarioId, $summary);
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
            ->whereIn('status', [
                OrcamentoModel::STATUS_ENVIADO,
                OrcamentoModel::STATUS_AGUARDANDO,
                OrcamentoModel::STATUS_REENVIAR,
            ])
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
            ->whereIn('status', [
                OrcamentoModel::STATUS_AGUARDANDO,
                OrcamentoModel::STATUS_REENVIAR,
                OrcamentoModel::STATUS_AGUARDANDO_PACOTE,
            ])
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
            $statusAtual = (string) ($row['status'] ?? '');
            $schedule = strtotime($validadeData . ' 10:00:00 -1 day');
            if ($schedule === false || $schedule < time()) {
                $schedule = strtotime('+4 hours');
            }

            $followupId = $this->createFollowupIfMissing(
                $row,
                $origin,
                $statusAtual === OrcamentoModel::STATUS_AGUARDANDO_PACOTE
                    ? ('Follow-up do pacote no orcamento ' . $numero)
                    : ($statusAtual === OrcamentoModel::STATUS_REENVIAR
                        ? ('Follow-up do orcamento revisado ' . $numero)
                        : ('Follow-up do orcamento ' . $numero)),
                $statusAtual === OrcamentoModel::STATUS_AGUARDANDO_PACOTE
                    ? ('Orcamento ' . $numero . ' aguarda escolha/aprovacao do nivel do pacote pelo cliente.')
                    : ($statusAtual === OrcamentoModel::STATUS_REENVIAR
                        ? ('Orcamento ' . $numero . ' foi revisado e precisa ser reenviado ao cliente para nova aprovacao.')
                        : ('Orcamento ' . $numero . ' segue aguardando aprovacao do cliente.')),
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

    /**
     * @param array<string,int> $summary
     */
    private function processPacoteAwaitingChoiceExpiry(?int $usuarioId, array &$summary): int
    {
        if (!$this->orcamentoModel->db->tableExists('pacotes_ofertas')) {
            return 0;
        }

        $now = date('Y-m-d H:i:s');
        $this->orcamentoModel->db->table('pacotes_ofertas')
            ->whereIn('status', ['ativo', 'enviado', 'erro_envio'])
            ->where('expira_em IS NOT NULL', null, false)
            ->where('expira_em <', $now)
            ->update([
                'status' => 'expirado',
                'updated_at' => $now,
            ]);

        $rows = $this->orcamentoModel
            ->where('status', OrcamentoModel::STATUS_AGUARDANDO_PACOTE)
            ->findAll(1000);

        if (empty($rows)) {
            return 0;
        }

        $changed = 0;
        foreach ($rows as $row) {
            $orcamentoId = (int) ($row['id'] ?? 0);
            if ($orcamentoId <= 0) {
                continue;
            }

            $latestOferta = $this->orcamentoModel->db->table('pacotes_ofertas')
                ->select('id, status, expira_em')
                ->where('orcamento_id', $orcamentoId)
                ->orderBy('id', 'DESC')
                ->get(1)
                ->getRowArray();
            if (!$latestOferta) {
                continue;
            }

            $latestStatus = trim((string) ($latestOferta['status'] ?? ''));
            if (in_array($latestStatus, ['escolhido', 'aplicado_orcamento'], true)) {
                continue;
            }

            $expiraEm = trim((string) ($latestOferta['expira_em'] ?? ''));
            $expiredByTime = $expiraEm !== '' && strtotime($expiraEm) !== false && strtotime($expiraEm) < time();
            $isExpired = $latestStatus === 'expirado' || (
                in_array($latestStatus, ['ativo', 'enviado', 'erro_envio'], true) && $expiredByTime
            );
            if (!$isExpired) {
                continue;
            }

            $statusAnterior = (string) ($row['status'] ?? '');
            if ($statusAnterior !== OrcamentoModel::STATUS_AGUARDANDO_PACOTE) {
                continue;
            }

            $this->orcamentoModel->update($orcamentoId, [
                'status' => OrcamentoModel::STATUS_PENDENTE,
                'atualizado_por' => $usuarioId > 0 ? $usuarioId : null,
            ]);

            $this->historicoModel->insert([
                'orcamento_id' => $orcamentoId,
                'status_anterior' => $statusAnterior,
                'status_novo' => OrcamentoModel::STATUS_PENDENTE,
                'observacao' => 'Prazo da oferta de pacote expirado sem escolha/aprovacao do cliente.',
                'origem' => 'automacao',
                'alterado_por' => $usuarioId > 0 ? $usuarioId : null,
                'created_at' => $now,
            ]);

            $changed++;

            $numero = trim((string) ($row['numero'] ?? ('#' . $orcamentoId)));
            $origin = 'orcamento_pacote_pendente_' . $orcamentoId;
            $followupId = $this->createFollowupIfMissing(
                $row,
                $origin,
                'Contato pendente do pacote - orçamento ' . $numero,
                'Cliente nao escolheu/aprovou o pacote dentro do prazo. Confirmar cancelamento ou seguir com orçamento customizado.',
                date('Y-m-d H:i:s', strtotime('+2 hours')),
                $usuarioId
            );
            if ($followupId) {
                $summary['followups_pacote_pendente']++;
            }
        }

        return $changed;
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
