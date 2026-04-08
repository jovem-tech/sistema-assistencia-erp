<?php

namespace App\Controllers;

use App\Models\OrcamentoAprovacaoModel;
use App\Models\OrcamentoModel;
use App\Models\OrcamentoStatusHistoricoModel;
use App\Services\OrcamentoLifecycleService;
use App\Services\OrcamentoService;

class Orcamento extends BaseController
{
    private OrcamentoModel $orcamentoModel;
    private OrcamentoAprovacaoModel $aprovacaoModel;
    private OrcamentoStatusHistoricoModel $historicoModel;
    private OrcamentoService $orcamentoService;
    private OrcamentoLifecycleService $lifecycleService;

    public function __construct()
    {
        $this->orcamentoModel = new OrcamentoModel();
        $this->aprovacaoModel = new OrcamentoAprovacaoModel();
        $this->historicoModel = new OrcamentoStatusHistoricoModel();
        $this->orcamentoService = new OrcamentoService();
        $this->lifecycleService = new OrcamentoLifecycleService();
    }

    public function visualizar($token)
    {
        $orcamento = $this->findByToken((string) $token);
        if (!$orcamento) {
            return $this->response->setStatusCode(404)->setBody('Orcamento nao encontrado ou link expirado.');
        }

        $itens = (new \App\Models\OrcamentoItemModel())->byOrcamento((int) ($orcamento['id'] ?? 0));
        return view('orcamentos/publico', [
            'orcamento' => $orcamento,
            'itens' => $itens,
            'statusLabels' => $this->orcamentoModel->statusLabels(),
        ]);
    }

    public function aprovar($token)
    {
        $orcamento = $this->findByToken((string) $token);
        if (!$orcamento) {
            return $this->response->setStatusCode(404)->setBody('Orcamento nao encontrado ou link expirado.');
        }

        $statusAtual = (string) ($orcamento['status'] ?? OrcamentoModel::STATUS_RASCUNHO);
        if (!in_array($statusAtual, [
            OrcamentoModel::STATUS_ENVIADO,
            OrcamentoModel::STATUS_AGUARDANDO,
            OrcamentoModel::STATUS_RASCUNHO,
        ], true)) {
            return redirect()->to('/orcamento/' . $token)
                ->with('error', 'Este orcamento nao permite mais aprovacao pelo link publico.');
        }

        $isAvulso = (int) ($orcamento['os_id'] ?? 0) <= 0;
        $statusAprovacao = $isAvulso
            ? OrcamentoModel::STATUS_PENDENTE_OS
            : OrcamentoModel::STATUS_APROVADO;

        $this->orcamentoModel->update((int) $orcamento['id'], [
            'status' => $statusAprovacao,
            'aprovado_em' => date('Y-m-d H:i:s'),
        ]);

        $this->aprovacaoModel->insert([
            'orcamento_id' => (int) $orcamento['id'],
            'token_publico' => (string) $token,
            'acao' => 'aprovado',
            'resposta_cliente' => trim((string) $this->request->getPost('resposta_cliente')) ?: null,
            'ip_origem' => (string) $this->request->getIPAddress(),
            'user_agent' => substr((string) $this->request->getUserAgent(), 0, 255) ?: null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->orcamentoService->registrarHistoricoStatus(
            $this->historicoModel,
            (int) $orcamento['id'],
            $statusAtual,
            $statusAprovacao,
            null,
            $isAvulso
                ? 'Aprovado via link publico (pendente de abertura de OS)'
                : 'Aprovado via link publico',
            'publico'
        );

        if ($isAvulso) {
            $this->lifecycleService->ensurePendingOsFollowup([
                'id' => (int) $orcamento['id'],
                'status' => $statusAprovacao,
                'numero' => (string) ($orcamento['numero'] ?? ''),
                'cliente_id' => (int) ($orcamento['cliente_id'] ?? 0) ?: null,
                'os_id' => (int) ($orcamento['os_id'] ?? 0) ?: null,
                'responsavel_id' => (int) ($orcamento['responsavel_id'] ?? 0) ?: null,
            ]);
        }

        return redirect()->to('/orcamento/' . $token)->with(
            'success',
            $isAvulso
                ? 'Orcamento aprovado com sucesso. Agora ele esta pendente de abertura de OS.'
                : 'Orcamento aprovado com sucesso.'
        );
    }

    public function recusar($token)
    {
        $orcamento = $this->findByToken((string) $token);
        if (!$orcamento) {
            return $this->response->setStatusCode(404)->setBody('Orcamento nao encontrado ou link expirado.');
        }

        $statusAtual = (string) ($orcamento['status'] ?? OrcamentoModel::STATUS_RASCUNHO);
        if (!in_array($statusAtual, [
            OrcamentoModel::STATUS_ENVIADO,
            OrcamentoModel::STATUS_AGUARDANDO,
            OrcamentoModel::STATUS_RASCUNHO,
        ], true)) {
            return redirect()->to('/orcamento/' . $token)
                ->with('error', 'Este orcamento nao permite mais rejeicao pelo link publico.');
        }

        $motivo = trim((string) $this->request->getPost('resposta_cliente'));
        if ($motivo === '') {
            $motivo = 'Rejeitado pelo cliente.';
        }

        $this->orcamentoModel->update((int) $orcamento['id'], [
            'status' => OrcamentoModel::STATUS_REJEITADO,
            'rejeitado_em' => date('Y-m-d H:i:s'),
            'motivo_rejeicao' => $motivo,
        ]);

        $this->aprovacaoModel->insert([
            'orcamento_id' => (int) $orcamento['id'],
            'token_publico' => (string) $token,
            'acao' => 'rejeitado',
            'resposta_cliente' => $motivo,
            'ip_origem' => (string) $this->request->getIPAddress(),
            'user_agent' => substr((string) $this->request->getUserAgent(), 0, 255) ?: null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->orcamentoService->registrarHistoricoStatus(
            $this->historicoModel,
            (int) $orcamento['id'],
            $statusAtual,
            OrcamentoModel::STATUS_REJEITADO,
            null,
            'Rejeitado via link publico',
            'publico'
        );

        return redirect()->to('/orcamento/' . $token)->with('success', 'Rejeicao registrada com sucesso.');
    }

    private function findByToken(string $token): ?array
    {
        $token = trim($token);
        if ($token === '') {
            return null;
        }

        $builder = $this->orcamentoModel
            ->select('orcamentos.*, clientes.nome_razao as cliente_nome')
            ->join('clientes', 'clientes.id = orcamentos.cliente_id', 'left')
            ->where('orcamentos.token_publico', $token)
            ->groupStart()
                ->where('orcamentos.token_expira_em IS NULL', null, false)
                ->orWhere('orcamentos.token_expira_em >=', date('Y-m-d H:i:s'))
            ->groupEnd();

        $row = $builder->first();
        return $row ?: null;
    }
}
