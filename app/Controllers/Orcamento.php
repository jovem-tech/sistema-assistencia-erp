<?php

namespace App\Controllers;

use App\Models\OrcamentoAprovacaoModel;
use App\Models\OrcamentoItemModel;
use App\Models\OrcamentoModel;
use App\Models\PacoteOfertaModel;
use App\Models\OrcamentoStatusHistoricoModel;
use App\Models\PacoteServicoNivelModel;
use App\Services\OrcamentoLifecycleService;
use App\Services\OrcamentoService;
use App\Services\OsStatusFlowService;

class Orcamento extends BaseController
{
    private OrcamentoModel $orcamentoModel;
    private OrcamentoAprovacaoModel $aprovacaoModel;
    private OrcamentoStatusHistoricoModel $historicoModel;
    private OrcamentoItemModel $itemModel;
    private PacoteOfertaModel $pacoteOfertaModel;
    private PacoteServicoNivelModel $pacoteNivelModel;
    private OrcamentoService $orcamentoService;
    private OrcamentoLifecycleService $lifecycleService;

    public function __construct()
    {
        $this->orcamentoModel = new OrcamentoModel();
        $this->aprovacaoModel = new OrcamentoAprovacaoModel();
        $this->historicoModel = new OrcamentoStatusHistoricoModel();
        $this->itemModel = new OrcamentoItemModel();
        $this->pacoteOfertaModel = new PacoteOfertaModel();
        $this->pacoteNivelModel = new PacoteServicoNivelModel();
        $this->orcamentoService = new OrcamentoService();
        $this->lifecycleService = new OrcamentoLifecycleService();
    }

    public function visualizar($token)
    {
        $orcamento = $this->findByToken((string) $token);
        if (!$orcamento) {
            return $this->response->setStatusCode(404)->setBody('Orcamento nao encontrado ou link expirado.');
        }

        $orcamentoStatus = (string) ($orcamento['status'] ?? OrcamentoModel::STATUS_RASCUNHO);
        if ($orcamentoStatus === OrcamentoModel::STATUS_AGUARDANDO_PACOTE && $this->isPacoteOfertaModuleReady()) {
            $this->refreshPacotesOfertasIfExpired();
            $ofertas = $this->pacoteOfertaModel->byOrcamento((int) ($orcamento['id'] ?? 0));
            foreach ($ofertas as $oferta) {
                $statusOferta = trim((string) ($oferta['status'] ?? ''));
                $tokenOferta = trim((string) ($oferta['token_publico'] ?? ''));
                if ($tokenOferta === '') {
                    continue;
                }
                if (in_array($statusOferta, ['ativo', 'enviado', 'escolhido', 'aplicado_orcamento'], true)) {
                    return redirect()->to('/pacote/oferta/' . $tokenOferta);
                }
            }
        }

        $orcamento['tipo_orcamento'] = $this->orcamentoModel->normalizeTipo(
            (string) ($orcamento['tipo_orcamento'] ?? ''),
            (int) ($orcamento['os_id'] ?? 0)
        );
        $itens = $this->itemModel->byOrcamento((int) ($orcamento['id'] ?? 0));
        return view('orcamentos/publico', [
            'orcamento' => $orcamento,
            'itens' => $itens,
            'statusLabels' => $this->orcamentoModel->statusLabels(),
            'tipoLabels' => $this->orcamentoModel->tipoLabels(),
        ]);
    }

    public function aprovar($token)
    {
        $token = (string) $token;
        $orcamento = $this->findByToken($token);
        if (!$orcamento) {
            return $this->response->setStatusCode(404)->setBody('Orcamento nao encontrado ou link expirado.');
        }

        $statusAtual = (string) ($orcamento['status'] ?? OrcamentoModel::STATUS_RASCUNHO);
        if (!in_array($statusAtual, [
            OrcamentoModel::STATUS_PENDENTE_ENVIO,
            OrcamentoModel::STATUS_ENVIADO,
            OrcamentoModel::STATUS_AGUARDANDO,
            OrcamentoModel::STATUS_RASCUNHO,
        ], true)) {
            return redirect()->to('/orcamento/' . $token)
                ->with('error', 'Este orcamento nao permite mais aprovacao pelo link publico.');
        }

        $tipoOrcamento = $this->orcamentoModel->normalizeTipo(
            (string) ($orcamento['tipo_orcamento'] ?? ''),
            (int) ($orcamento['os_id'] ?? 0)
        );
        $isPrevio = !$this->orcamentoModel->isTipoAssistencia($tipoOrcamento, (int) ($orcamento['os_id'] ?? 0));
        $statusAprovacao = $isPrevio
            ? OrcamentoModel::STATUS_PENDENTE_OS
            : OrcamentoModel::STATUS_APROVADO;

        $this->orcamentoModel->update((int) $orcamento['id'], [
            'status' => $statusAprovacao,
            'aprovado_em' => date('Y-m-d H:i:s'),
        ]);

        $this->aprovacaoModel->insert([
            'orcamento_id' => (int) $orcamento['id'],
            'token_publico' => $token,
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
            $isPrevio
                ? 'Orcamento previo aprovado via link publico (pendente de abertura de OS)'
                : 'Orcamento de assistencia aprovado via link publico',
            'publico'
        );

        if ($isPrevio) {
            $this->lifecycleService->ensurePendingOsFollowup([
                'id' => (int) $orcamento['id'],
                'status' => $statusAprovacao,
                'numero' => (string) ($orcamento['numero'] ?? ''),
                'cliente_id' => (int) ($orcamento['cliente_id'] ?? 0) ?: null,
                'os_id' => (int) ($orcamento['os_id'] ?? 0) ?: null,
                'responsavel_id' => (int) ($orcamento['responsavel_id'] ?? 0) ?: null,
            ]);
        }
        $this->syncLinkedOsByOrcamentoStatus(
            (int) ($orcamento['os_id'] ?? 0),
            $statusAprovacao
        );

        return redirect()->to('/orcamento/' . $token)->with(
            'success',
            $isPrevio
                ? 'Estimativa inicial aprovada com sucesso. Agora ela esta pendente de abertura de OS para analise tecnica.'
                : 'Orcamento aprovado com sucesso.'
        );
    }

    public function recusar($token)
    {
        $token = (string) $token;
        $orcamento = $this->findByToken($token);
        if (!$orcamento) {
            return $this->response->setStatusCode(404)->setBody('Orcamento nao encontrado ou link expirado.');
        }

        $statusAtual = (string) ($orcamento['status'] ?? OrcamentoModel::STATUS_RASCUNHO);
        if (!in_array($statusAtual, [
            OrcamentoModel::STATUS_PENDENTE_ENVIO,
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
            'token_publico' => $token,
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

    public function visualizarPacote($token)
    {
        $token = trim((string) $token);
        if ($token === '') {
            return $this->response->setStatusCode(404)->setBody('Oferta de pacote nao encontrada.');
        }

        if ($this->isPacoteOfertaModuleReady()) {
            $oferta = $this->pacoteOfertaModel->findByTokenWithContext($token);
            if ($oferta) {
                return redirect()->to('/pacote/oferta/' . $token);
            }
        }

        return $this->response->setStatusCode(410)->setBody('O fluxo legado de pacote foi desativado. Solicite um novo link de oferta.');
    }

    public function escolherPacote($token)
    {
        $token = trim((string) $token);
        if ($token === '') {
            return $this->response->setStatusCode(404)->setBody('Oferta de pacote nao encontrada.');
        }

        if ($this->isPacoteOfertaModuleReady()) {
            $oferta = $this->pacoteOfertaModel->findByTokenWithContext($token);
            if ($oferta) {
                return redirect()->to('/pacote/oferta/' . $token)
                    ->with('warning', 'Link legado detectado. Use o fluxo atualizado de oferta dinâmica.');
            }
        }

        return $this->response->setStatusCode(410)->setBody('O fluxo legado de pacote foi desativado. Solicite um novo link de oferta.');
    }

    public function visualizarOfertaPacote($token)
    {
        if (!$this->isPacoteOfertaModuleReady()) {
            return $this->response->setStatusCode(404)->setBody('Modulo de ofertas dinamicas nao inicializado.');
        }

        $this->refreshPacotesOfertasIfExpired();
        $oferta = $this->pacoteOfertaModel->findByTokenWithContext((string) $token);
        if (!$oferta) {
            return $this->response->setStatusCode(404)->setBody('Oferta de pacote nao encontrada ou expirou.');
        }

        $niveis = $this->findPacoteNiveisAtivos((int) ($oferta['pacote_servico_id'] ?? 0));
        $clienteNome = $this->resolveClienteNomeByOferta($oferta);
        $statusOferta = (string) ($oferta['status'] ?? 'ativo');

        return view('orcamentos/oferta_publica', [
            'oferta' => $oferta,
            'niveis' => $niveis,
            'clienteNome' => $clienteNome,
            'statusOferta' => $statusOferta,
            'canChoose' => $this->canChoosePacoteOferta($oferta),
        ]);
    }

    public function escolherOfertaPacote($token)
    {
        if (!$this->isPacoteOfertaModuleReady()) {
            return $this->response->setStatusCode(404)->setBody('Modulo de ofertas dinamicas nao inicializado.');
        }

        $this->refreshPacotesOfertasIfExpired();
        $token = trim((string) $token);
        $oferta = $this->pacoteOfertaModel->findByTokenWithContext($token);
        if (!$oferta) {
            return $this->response->setStatusCode(404)->setBody('Oferta de pacote nao encontrada ou expirou.');
        }

        if (!$this->canChoosePacoteOferta($oferta)) {
            return redirect()->to('/pacote/oferta/' . $token)
                ->with('error', 'Esta oferta nao permite nova escolha.');
        }

        $nivelEscolhido = trim((string) $this->request->getPost('nivel'));
        if ($nivelEscolhido === '') {
            return redirect()->to('/pacote/oferta/' . $token)
                ->with('error', 'Selecione um nivel para continuar.');
        }

        $niveis = $this->findPacoteNiveisAtivos((int) ($oferta['pacote_servico_id'] ?? 0));
        $nivelSelecionado = null;
        foreach ($niveis as $nivel) {
            if ((string) ($nivel['nivel'] ?? '') === $nivelEscolhido) {
                $nivelSelecionado = $nivel;
                break;
            }
        }
        if ($nivelSelecionado === null) {
            return redirect()->to('/pacote/oferta/' . $token)
                ->with('error', 'Nivel invalido para esta oferta.');
        }

        $this->pacoteOfertaModel->db->transStart();

        $builder = $this->pacoteOfertaModel->db->table('pacotes_ofertas')
            ->whereIn('status', ['ativo', 'enviado'])
            ->where('id <>', (int) ($oferta['id'] ?? 0));

        $hasIdentity = false;
        $clienteId = (int) ($oferta['cliente_id'] ?? 0);
        $contatoId = (int) ($oferta['contato_id'] ?? 0);
        $telefone = trim((string) ($oferta['telefone_destino'] ?? ''));
        $builder->groupStart();
        if ($clienteId > 0) {
            $builder->orWhere('cliente_id', $clienteId);
            $hasIdentity = true;
        }
        if ($contatoId > 0) {
            $builder->orWhere('contato_id', $contatoId);
            $hasIdentity = true;
        }
        if ($telefone !== '') {
            $builder->orWhere('telefone_destino', $telefone);
            $hasIdentity = true;
        }
        $builder->groupEnd();
        if ($hasIdentity) {
            $builder->update([
                'status' => 'cancelado',
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $ofertaId = (int) ($oferta['id'] ?? 0);
        $orcamentoId = (int) ($oferta['orcamento_id'] ?? 0);
        $valorEscolhido = max(0, (float) ($nivelSelecionado['preco_recomendado'] ?? 0));
        $ofertaUpdate = [
            'status' => 'escolhido',
            'nivel_escolhido' => $nivelEscolhido,
            'nivel_nome_exibicao' => trim((string) ($nivelSelecionado['nome_exibicao'] ?? ucfirst($nivelEscolhido))),
            'valor_escolhido' => round($valorEscolhido, 2),
            'garantia_dias' => (int) ($nivelSelecionado['garantia_dias'] ?? 0) ?: null,
            'prazo_estimado' => trim((string) ($nivelSelecionado['prazo_estimado'] ?? '')) ?: null,
            'itens_inclusos' => trim((string) ($nivelSelecionado['itens_inclusos'] ?? '')) ?: null,
            'argumento_venda' => trim((string) ($nivelSelecionado['argumento_venda'] ?? '')) ?: null,
            'escolhido_em' => date('Y-m-d H:i:s'),
            'ip_escolha' => (string) $this->request->getIPAddress(),
            'user_agent_escolha' => substr((string) $this->request->getUserAgent(), 0, 255) ?: null,
        ];

        if ($orcamentoId > 0) {
            $pacoteId = (int) ($oferta['pacote_servico_id'] ?? 0);
            $pacoteNome = trim((string) ($oferta['pacote_nome'] ?? 'Pacote de servicos'));
            if ($pacoteNome === '') {
                $pacoteNome = 'Pacote de servicos';
            }
            $nivelNome = trim((string) ($nivelSelecionado['nome_exibicao'] ?? ucfirst($nivelEscolhido)));
            $descricaoPacote = 'Pacote ' . $pacoteNome . ' - ' . $nivelNome;
            $observacoesPacote = $this->buildPacoteItemObservacao($nivelSelecionado);
            if ($ofertaId > 0) {
                $observacoesPacote = trim(($observacoesPacote !== '' ? ($observacoesPacote . ' | ') : '') . 'Oferta dinamica #' . $ofertaId);
            }
            $orcamentoItemId = (int) ($oferta['orcamento_item_id'] ?? 0);
            $ordemItem = $this->nextOrcamentoItemOrder($orcamentoId);
            $currentItem = null;
            if ($orcamentoItemId > 0) {
                $currentItem = $this->itemModel
                    ->where('id', $orcamentoItemId)
                    ->where('orcamento_id', $orcamentoId)
                    ->first();
                if ($currentItem) {
                    $ordemItem = max(1, (int) ($currentItem['ordem'] ?? $ordemItem));
                }
            }
            $itemPayload = [
                'orcamento_id' => $orcamentoId,
                'tipo_item' => 'combo',
                'referencia_id' => $pacoteId > 0 ? $pacoteId : null,
                'descricao' => $descricaoPacote,
                'quantidade' => 1,
                'valor_unitario' => round($valorEscolhido, 2),
                'desconto' => 0,
                'acrescimo' => 0,
                'total' => round($valorEscolhido, 2),
                'ordem' => $ordemItem,
                'observacoes' => $observacoesPacote !== '' ? $observacoesPacote : null,
            ];
            if ($currentItem) {
                $this->itemModel->update((int) $currentItem['id'], $itemPayload);
                $orcamentoItemId = (int) $currentItem['id'];
            } else {
                $this->itemModel->insert($itemPayload);
                $orcamentoItemId = (int) $this->itemModel->getInsertID();
            }
            $ofertaUpdate['status'] = 'aplicado_orcamento';
            $ofertaUpdate['orcamento_id'] = $orcamentoId;
            $ofertaUpdate['orcamento_item_id'] = $orcamentoItemId > 0 ? $orcamentoItemId : null;
            $ofertaUpdate['aplicado_em'] = date('Y-m-d H:i:s');
            $orcamento = $this->orcamentoModel->find($orcamentoId);
            $validadeData = trim((string) ($orcamento['validade_data'] ?? ''));
            if ($validadeData !== '' && strtotime($validadeData) !== false) {
                $ofertaUpdate['expira_em'] = date('Y-m-d 23:59:59', strtotime($validadeData));
            }
            $this->recalculateOrcamentoTotals($orcamentoId);
            $this->markOrcamentoAsPacoteAprovado(
                $orcamentoId,
                'Pacote escolhido/aprovado pelo cliente via oferta dinamica.'
            );
            $this->aprovacaoModel->insert([
                'orcamento_id' => $orcamentoId,
                'token_publico' => $token,
                'acao' => 'pacote_oferta_escolhida',
                'resposta_cliente' => 'Nivel selecionado: '
                    . trim((string) ($nivelSelecionado['nome_exibicao'] ?? ucfirst($nivelEscolhido)))
                    . ' (' . formatMoney($valorEscolhido) . ')',
                'ip_origem' => (string) $this->request->getIPAddress(),
                'user_agent' => substr((string) $this->request->getUserAgent(), 0, 255) ?: null,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $this->pacoteOfertaModel->update($ofertaId, $ofertaUpdate);

        $this->pacoteOfertaModel->db->transComplete();
        if (!$this->pacoteOfertaModel->db->transStatus()) {
            return redirect()->to('/pacote/oferta/' . $token)
                ->with('error', 'Falha ao registrar sua escolha. Tente novamente.');
        }

        $success = $orcamentoId > 0
            ? 'Escolha registrada com sucesso. O pacote foi aplicado automaticamente no orcamento.'
            : 'Escolha registrada com sucesso. Agora a equipe pode aplicar este pacote no orcamento.';
        return redirect()->to('/pacote/oferta/' . $token)
            ->with('success', $success);
    }

    private function isPacoteOfertaModuleReady(): bool
    {
        return $this->orcamentoModel->db->tableExists('pacotes_ofertas')
            && $this->orcamentoModel->db->tableExists('pacotes_servicos')
            && $this->orcamentoModel->db->tableExists('pacotes_servicos_niveis');
    }

    private function refreshPacotesOfertasIfExpired(): void
    {
        if (!$this->isPacoteOfertaModuleReady()) {
            return;
        }

        $this->pacoteOfertaModel->db->table('pacotes_ofertas')
            ->whereIn('status', ['ativo', 'enviado'])
            ->where('expira_em IS NOT NULL', null, false)
            ->where('expira_em <', date('Y-m-d H:i:s'))
            ->update([
                'status' => 'expirado',
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        $this->lifecycleService->syncPacoteAwaitingChoiceExpiry(null);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function findPacoteNiveisAtivos(int $pacoteId): array
    {
        if ($pacoteId <= 0) {
            return [];
        }

        return $this->pacoteNivelModel
            ->where('pacote_servico_id', $pacoteId)
            ->where('ativo', 1)
            ->orderBy('ordem', 'ASC')
            ->findAll();
    }


    private function canChoosePacoteOferta(array $oferta): bool
    {
        $status = (string) ($oferta['status'] ?? 'ativo');
        if (!in_array($status, ['ativo', 'enviado'], true)) {
            return false;
        }

        if (!empty($oferta['expira_em']) && strtotime((string) $oferta['expira_em']) < time()) {
            return false;
        }

        return true;
    }

    private function resolveClienteNomeByOferta(array $oferta): string
    {
        $cliente = trim((string) ($oferta['cliente_nome'] ?? ''));
        if ($cliente !== '') {
            return $cliente;
        }

        $contato = trim((string) ($oferta['contato_nome'] ?? $oferta['contato_nome_perfil'] ?? ''));
        if ($contato !== '') {
            return $contato;
        }

        $telefone = trim((string) ($oferta['telefone_destino'] ?? ''));
        if ($telefone !== '') {
            return $telefone;
        }

        return 'Cliente';
    }

    private function nextOrcamentoItemOrder(int $orcamentoId): int
    {
        $row = $this->itemModel
            ->selectMax('ordem')
            ->where('orcamento_id', $orcamentoId)
            ->first();

        return max(1, (int) ($row['ordem'] ?? 0) + 1);
    }

    private function buildPacoteItemObservacao(array $nivel): string
    {
        $partes = [];
        $prazo = trim((string) ($nivel['prazo_estimado'] ?? ''));
        if ($prazo !== '') {
            $partes[] = 'Prazo estimado: ' . $prazo;
        }

        $garantia = (int) ($nivel['garantia_dias'] ?? 0);
        if ($garantia > 0) {
            $partes[] = 'Garantia: ' . $garantia . ' dias';
        }

        $itensInclusos = trim((string) ($nivel['itens_inclusos'] ?? ''));
        if ($itensInclusos !== '') {
            $itensResumo = preg_replace('/\s*[\r\n]+\s*/', '; ', $itensInclusos);
            $partes[] = 'Inclusos: ' . trim((string) $itensResumo);
        }

        return implode(' | ', $partes);
    }

    private function recalculateOrcamentoTotals(int $orcamentoId): void
    {
        if ($orcamentoId <= 0) {
            return;
        }

        $orcamento = $this->orcamentoModel->find($orcamentoId);
        if (!$orcamento) {
            return;
        }

        $itens = $this->itemModel->byOrcamento($orcamentoId);
        $subtotal = 0.0;
        foreach ($itens as $item) {
            $subtotal += (float) ($item['total'] ?? 0);
        }

        $subtotal = round($subtotal, 2);
        $desconto = max(0, (float) ($orcamento['desconto'] ?? 0));
        $acrescimo = max(0, (float) ($orcamento['acrescimo'] ?? 0));
        $total = max(0, round($subtotal - $desconto + $acrescimo, 2));

        $this->orcamentoModel->update($orcamentoId, [
            'subtotal' => $subtotal,
            'total' => $total,
        ]);
    }
    private function markOrcamentoAsPacoteAprovado(int $orcamentoId, string $observacao): void
    {
        if ($orcamentoId <= 0) {
            return;
        }
        $orcamento = $this->orcamentoModel->find($orcamentoId);
        if (!$orcamento) {
            return;
        }
        $statusAnterior = (string) ($orcamento['status'] ?? OrcamentoModel::STATUS_RASCUNHO);
        $statusNovo = OrcamentoModel::STATUS_PACOTE_APROVADO;
        if ($statusAnterior === $statusNovo) {
            return;
        }
        $canTransition = $this->orcamentoService->canTransition($statusAnterior, $statusNovo);
        if (!$canTransition && $statusAnterior !== OrcamentoModel::STATUS_PENDENTE) {
            return;
        }
        $this->orcamentoModel->update($orcamentoId, [
            'status' => $statusNovo,
        ]);
        $this->orcamentoService->registrarHistoricoStatus(
            $this->historicoModel,
            $orcamentoId,
            $statusAnterior,
            $statusNovo,
            null,
            $observacao,
            'publico'
        );
    }

    private function syncLinkedOsByOrcamentoStatus(int $osId, string $orcamentoStatus): void
    {
        if ($osId <= 0) {
            return;
        }

        $targetStatus = match ($orcamentoStatus) {
            OrcamentoModel::STATUS_APROVADO,
            OrcamentoModel::STATUS_CONVERTIDO => 'aguardando_reparo',
            OrcamentoModel::STATUS_RASCUNHO,
            OrcamentoModel::STATUS_PENDENTE_ENVIO,
            OrcamentoModel::STATUS_ENVIADO,
            OrcamentoModel::STATUS_AGUARDANDO,
            OrcamentoModel::STATUS_AGUARDANDO_PACOTE,
            OrcamentoModel::STATUS_PACOTE_APROVADO,
            OrcamentoModel::STATUS_PENDENTE => 'aguardando_autorizacao',
            default => null,
        };
        if ($targetStatus === null) {
            return;
        }

        $db = \Config\Database::connect();
        if (!$db->tableExists('os')) {
            return;
        }

        $estadoFluxo = (new OsStatusFlowService())->resolveEstadoFluxo($targetStatus);

        $db->table('os')
            ->where('id', $osId)
            ->where('status <>', $targetStatus)
            ->groupStart()
                ->where('estado_fluxo IS NULL', null, false)
                ->orWhereNotIn('estado_fluxo', ['encerrado', 'cancelado'])
            ->groupEnd()
            ->update([
                'status' => $targetStatus,
                'estado_fluxo' => $estadoFluxo,
                'status_atualizado_em' => date('Y-m-d H:i:s'),
            ]);
    }

    private function findByToken(string $token): ?array
    {
        $token = trim($token);
        if ($token === '') {
            return null;
        }

        $builder = $this->orcamentoModel
            ->select('orcamentos.*, clientes.nome_razao as cliente_nome, os.numero_os')
            ->join('clientes', 'clientes.id = orcamentos.cliente_id', 'left')
            ->join('os', 'os.id = orcamentos.os_id', 'left')
            ->where('orcamentos.token_publico', $token)
            ->groupStart()
                ->where('orcamentos.token_expira_em IS NULL', null, false)
                ->orWhere('orcamentos.token_expira_em >=', date('Y-m-d H:i:s'))
            ->groupEnd();

        $row = $builder->first();
        return $row ?: null;
    }
}
