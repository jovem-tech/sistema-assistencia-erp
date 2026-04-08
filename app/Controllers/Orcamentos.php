<?php

namespace App\Controllers;

use App\Models\ClienteModel;
use App\Models\ConversaWhatsappModel;
use App\Models\EquipamentoModel;
use App\Models\LogModel;
use App\Models\OrcamentoAprovacaoModel;
use App\Models\OrcamentoEnvioModel;
use App\Models\OrcamentoItemModel;
use App\Models\OrcamentoModel;
use App\Models\OrcamentoStatusHistoricoModel;
use App\Models\OsModel;
use App\Services\OrcamentoConversaoService;
use App\Services\OrcamentoLifecycleService;
use App\Services\OrcamentoMailService;
use App\Services\OrcamentoPdfService;
use App\Services\OrcamentoService;
use App\Services\WhatsAppService;

class Orcamentos extends BaseController
{
    private OrcamentoModel $orcamentoModel;
    private OrcamentoItemModel $itemModel;
    private OrcamentoStatusHistoricoModel $historicoModel;
    private OrcamentoEnvioModel $envioModel;
    private OrcamentoAprovacaoModel $aprovacaoModel;
    private OrcamentoService $orcamentoService;
    private OrcamentoPdfService $pdfService;
    private OrcamentoMailService $mailService;
    private OrcamentoLifecycleService $lifecycleService;
    private OrcamentoConversaoService $conversaoService;

    public function __construct()
    {
        requirePermission('orcamentos');

        $this->orcamentoModel = new OrcamentoModel();
        $this->itemModel = new OrcamentoItemModel();
        $this->historicoModel = new OrcamentoStatusHistoricoModel();
        $this->envioModel = new OrcamentoEnvioModel();
        $this->aprovacaoModel = new OrcamentoAprovacaoModel();
        $this->orcamentoService = new OrcamentoService();
        $this->pdfService = new OrcamentoPdfService();
        $this->mailService = new OrcamentoMailService();
        $this->lifecycleService = new OrcamentoLifecycleService();
        $this->conversaoService = new OrcamentoConversaoService();
    }

    public function index()
    {
        $this->syncLifecycleIfDue();

        $statusFilter = trim((string) $this->request->getGet('status'));
        $q = trim((string) $this->request->getGet('q'));

        $builder = $this->orcamentoModel
            ->select('orcamentos.*, clientes.nome_razao as cliente_nome, os.numero_os')
            ->join('clientes', 'clientes.id = orcamentos.cliente_id', 'left')
            ->join('os', 'os.id = orcamentos.os_id', 'left');

        if ($statusFilter !== '') {
            $builder->where('orcamentos.status', $statusFilter);
        }

        if ($q !== '') {
            $builder->groupStart()
                ->like('orcamentos.numero', $q)
                ->orLike('clientes.nome_razao', $q)
                ->orLike('orcamentos.cliente_nome_avulso', $q)
                ->orLike('os.numero_os', $q)
                ->groupEnd();
        }

        $orcamentos = $builder
            ->orderBy('orcamentos.created_at', 'DESC')
            ->findAll();

        $resumo = array_fill_keys(array_keys($this->orcamentoModel->statusLabels()), 0);
        $rowsResumo = $this->orcamentoModel
            ->select('status, COUNT(*) as total')
            ->groupBy('status')
            ->findAll();
        foreach ($rowsResumo as $row) {
            $status = (string) ($row['status'] ?? '');
            $resumo[$status] = (int) ($row['total'] ?? 0);
        }

        return view('orcamentos/index', [
            'title' => 'Orcamentos',
            'orcamentos' => $orcamentos,
            'statusLabels' => $this->orcamentoModel->statusLabels(),
            'statusFilter' => $statusFilter,
            'q' => $q,
            'resumo' => $resumo,
        ]);
    }

    public function create()
    {
        requirePermission('orcamentos', 'criar');

        $prefill = $this->prefillFromRequest();
        $prefill['status'] = OrcamentoModel::STATUS_RASCUNHO;

        return view('orcamentos/form', $this->buildFormData([
            'title' => 'Novo Orcamento',
            'orcamento' => $prefill,
            'itens' => [],
            'isEdit' => false,
            'actionUrl' => base_url('orcamentos/salvar'),
        ]));
    }

    public function store()
    {
        requirePermission('orcamentos', 'criar');

        $payload = $this->extractOrcamentoPayload();
        $itens = $this->extractItensPayload();

        if (empty($itens)) {
            return redirect()->back()->withInput()->with('error', 'Adicione pelo menos um item no orcamento.');
        }

        $usuarioId = (int) (session()->get('user_id') ?? 0);
        $payload['criado_por'] = $usuarioId > 0 ? $usuarioId : null;
        $payload['atualizado_por'] = $usuarioId > 0 ? $usuarioId : null;
        $payload['status'] = $payload['status'] ?: OrcamentoModel::STATUS_RASCUNHO;
        $payload['status'] = $this->resolveApprovedStatus($payload, $payload['status']);
        $payload['token_publico'] = $this->orcamentoService->generateToken();
        $payload['token_expira_em'] = date('Y-m-d H:i:s', strtotime('+30 days'));
        $payload = array_merge($payload, $this->statusTimestampColumns($payload['status'], $now));

        $this->orcamentoModel->db->transStart();

        $this->orcamentoModel->insert($payload);
        $orcamentoId = (int) $this->orcamentoModel->getInsertID();

        if ($orcamentoId <= 0) {
            $this->orcamentoModel->db->transRollback();
            return redirect()->back()->withInput()->with('error', 'Nao foi possivel salvar o orcamento.');
        }

        $numero = $this->orcamentoService->ensureNumero($this->orcamentoModel, $orcamentoId);
        $this->persistItens($orcamentoId, $itens);
        $this->orcamentoService->registrarHistoricoStatus(
            $this->historicoModel,
            $orcamentoId,
            null,
            (string) $payload['status'],
            $usuarioId > 0 ? $usuarioId : null,
            'Criacao do orcamento',
            'interno'
        );

        $this->orcamentoModel->db->transComplete();
        if (!$this->orcamentoModel->db->transStatus()) {
            return redirect()->back()->withInput()->with('error', 'Falha ao concluir gravacao do orcamento.');
        }

        LogModel::registrar('orcamento_criado', 'Orcamento ' . $numero . ' criado.');
        return redirect()->to('/orcamentos/visualizar/' . $orcamentoId)->with('success', 'Orcamento criado com sucesso.');
    }

    public function show($id)
    {
        $this->syncLifecycleIfDue();

        $orcamento = $this->findOrcamento((int) $id);
        if (!$orcamento) {
            return redirect()->to('/orcamentos')->with('error', 'Orcamento nao encontrado.');
        }

        $orcamento = $this->ensurePublicToken($orcamento);

        $itens = $this->itemModel->byOrcamento((int) $id);
        $historico = $this->historicoModel->timeline((int) $id);
        $envios = $this->envioModel->byOrcamento((int) $id);
        $aprovacoes = $this->aprovacaoModel->byOrcamento((int) $id);
        $defaultWhatsappMessage = $this->buildDefaultWhatsAppMessage($orcamento);
        $defaultEmailSubject = $this->buildDefaultEmailSubject($orcamento);

        $lastPdfEnvio = $this->envioModel->latestByCanal((int) $id, 'pdf', 'gerado');
        $lastPdfRelative = trim((string) ($lastPdfEnvio['documento_path'] ?? ''));
        $lastPdfUrl = '';
        if ($lastPdfRelative !== '' && is_file(FCPATH . ltrim($lastPdfRelative, '/\\'))) {
            $lastPdfUrl = base_url($lastPdfRelative);
        }

        return view('orcamentos/show', [
            'title' => 'Visualizar Orcamento',
            'orcamento' => $orcamento,
            'itens' => $itens,
            'historico' => $historico,
            'envios' => $envios,
            'aprovacoes' => $aprovacoes,
            'statusLabels' => $this->orcamentoModel->statusLabels(),
            'defaultWhatsappMessage' => $defaultWhatsappMessage,
            'defaultEmailSubject' => $defaultEmailSubject,
            'lastPdfUrl' => $lastPdfUrl,
        ]);
    }

    public function generatePdf($id)
    {
        requirePermission('orcamentos', 'visualizar');

        $orcamentoId = (int) $id;
        $orcamento = $this->findOrcamento($orcamentoId);
        if (!$orcamento) {
            return redirect()->to('/orcamentos')->with('error', 'Orcamento nao encontrado.');
        }

        $usuarioId = (int) (session()->get('user_id') ?? 0);
        $forceNew = (string) $this->request->getPost('force_new') === '1';
        $pdfResult = $this->resolvePdfDocument(
            $orcamentoId,
            $usuarioId > 0 ? $usuarioId : null,
            'geracao_manual',
            $forceNew
        );

        if (empty($pdfResult['ok'])) {
            $error = (string) ($pdfResult['message'] ?? 'Falha ao gerar PDF do orcamento.');
            return redirect()->to('/orcamentos/visualizar/' . $orcamentoId)->with('error', $error);
        }

        LogModel::registrar('orcamento_pdf_gerado', 'PDF gerado para o orcamento ID ' . $orcamentoId . '.');
        return redirect()->to('/orcamentos/visualizar/' . $orcamentoId)->with('success', 'PDF do orcamento gerado com sucesso.');
    }

    public function downloadPdf($id)
    {
        requirePermission('orcamentos', 'visualizar');

        $orcamentoId = (int) $id;
        $orcamento = $this->findOrcamento($orcamentoId);
        if (!$orcamento) {
            return redirect()->to('/orcamentos')->with('error', 'Orcamento nao encontrado.');
        }

        $usuarioId = (int) (session()->get('user_id') ?? 0);
        $pdfResult = $this->resolvePdfDocument(
            $orcamentoId,
            $usuarioId > 0 ? $usuarioId : null,
            'download',
            false
        );

        if (empty($pdfResult['ok'])) {
            $error = (string) ($pdfResult['message'] ?? 'Falha ao preparar o PDF do orcamento.');
            return redirect()->to('/orcamentos/visualizar/' . $orcamentoId)->with('error', $error);
        }

        $filePath = (string) ($pdfResult['path'] ?? '');
        $fileName = (string) ($pdfResult['nome_arquivo'] ?? ('orcamento_' . $orcamentoId . '.pdf'));
        if ($filePath === '' || !is_file($filePath)) {
            return redirect()->to('/orcamentos/visualizar/' . $orcamentoId)->with('error', 'Arquivo PDF do orcamento nao encontrado no servidor.');
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            return redirect()->to('/orcamentos/visualizar/' . $orcamentoId)->with('error', 'Nao foi possivel carregar o arquivo PDF do orcamento.');
        }

        return $this->response
            ->setContentType('application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . $fileName . '"')
            ->setBody($content);
    }

    public function sendWhatsApp($id)
    {
        requirePermission('orcamentos', 'editar');

        $orcamentoId = (int) $id;
        $orcamento = $this->findOrcamento($orcamentoId);
        if (!$orcamento) {
            return redirect()->to('/orcamentos')->with('error', 'Orcamento nao encontrado.');
        }

        $statusAtual = (string) ($orcamento['status'] ?? OrcamentoModel::STATUS_RASCUNHO);
        if (!$this->canDispatchByStatus($statusAtual)) {
            return redirect()->to('/orcamentos/visualizar/' . $orcamentoId)
                ->with('error', 'Este orcamento esta bloqueado para envio no status atual.');
        }

        $orcamento = $this->ensurePublicToken($orcamento);
        $usuarioId = (int) (session()->get('user_id') ?? 0);
        $telefone = trim((string) $this->request->getPost('telefone_contato'));
        if ($telefone === '') {
            $telefone = trim((string) ($orcamento['telefone_contato'] ?? $orcamento['conversa_telefone'] ?? ''));
        }

        if (!$this->isPhoneValid($telefone)) {
            $this->saveEnvioError(
                $orcamentoId,
                'whatsapp',
                $telefone,
                null,
                'Telefone invalido para envio do orcamento.',
                $usuarioId
            );
            return redirect()->to('/orcamentos/visualizar/' . $orcamentoId)
                ->with('error', 'Telefone invalido para envio no WhatsApp.');
        }

        $mensagem = trim((string) $this->request->getPost('mensagem_whatsapp'));
        if ($mensagem === '') {
            $mensagem = $this->buildDefaultWhatsAppMessage($orcamento);
        }

        $incluirPdf = (string) $this->request->getPost('incluir_pdf') !== '0';
        $dispatch = $this->dispatchWhatsAppMessage(
            $orcamento,
            $telefone,
            $mensagem,
            $incluirPdf,
            $usuarioId
        );

        if (!empty($dispatch['ok'])) {
            LogModel::registrar('orcamento_whatsapp', 'Orcamento ID ' . $orcamentoId . ' enviado por WhatsApp.');
            $success = !empty($dispatch['duplicate'])
                ? 'Envio duplicado evitado: mensagem ja registrada recentemente no WhatsApp.'
                : 'Orcamento enviado por WhatsApp com sucesso.';
            return redirect()->to('/orcamentos/visualizar/' . $orcamentoId)->with('success', $success);
        }

        $error = (string) ($dispatch['message'] ?? 'Falha ao enviar orcamento por WhatsApp.');
        LogModel::registrar('orcamento_whatsapp_erro', 'Falha no envio WhatsApp do orcamento ID ' . $orcamentoId . '.');
        return redirect()->to('/orcamentos/visualizar/' . $orcamentoId)->with('error', $error);
    }

    public function sendEmail($id)
    {
        requirePermission('orcamentos', 'editar');

        $orcamentoId = (int) $id;
        $orcamento = $this->findOrcamento($orcamentoId);
        if (!$orcamento) {
            return redirect()->to('/orcamentos')->with('error', 'Orcamento nao encontrado.');
        }

        $statusAtual = (string) ($orcamento['status'] ?? OrcamentoModel::STATUS_RASCUNHO);
        if (!$this->canDispatchByStatus($statusAtual)) {
            return redirect()->to('/orcamentos/visualizar/' . $orcamentoId)
                ->with('error', 'Este orcamento esta bloqueado para envio no status atual.');
        }

        $orcamento = $this->ensurePublicToken($orcamento);
        $usuarioId = (int) (session()->get('user_id') ?? 0);
        $emailDestino = trim((string) $this->request->getPost('email_contato'));
        if ($emailDestino === '') {
            $emailDestino = trim((string) ($orcamento['email_contato'] ?? ''));
        }

        if ($emailDestino === '' || !filter_var($emailDestino, FILTER_VALIDATE_EMAIL)) {
            $this->saveEnvioError(
                $orcamentoId,
                'email',
                $emailDestino,
                null,
                'Email de destino invalido para envio do orcamento.',
                $usuarioId
            );
            return redirect()->to('/orcamentos/visualizar/' . $orcamentoId)
                ->with('error', 'Email de destino invalido para envio do orcamento.');
        }

        $assunto = trim((string) $this->request->getPost('assunto_email'));
        if ($assunto === '') {
            $assunto = $this->buildDefaultEmailSubject($orcamento);
        }

        $mensagemLivre = trim((string) $this->request->getPost('mensagem_email'));
        $htmlBody = $this->buildDefaultEmailBody($orcamento, $mensagemLivre);
        $incluirPdf = (string) $this->request->getPost('incluir_pdf') !== '0';
        $pdfPath = null;
        $pdfRelative = null;

        $envioId = $this->startEnvioTrace(
            $orcamentoId,
            'email',
            $emailDestino,
            $mensagemLivre !== '' ? $mensagemLivre : strip_tags($htmlBody),
            null,
            $usuarioId
        );

        if ($incluirPdf) {
            $pdfResult = $this->resolvePdfDocument(
                $orcamentoId,
                $usuarioId > 0 ? $usuarioId : null,
                'anexo_email',
                false
            );

            if (empty($pdfResult['ok'])) {
                $error = (string) ($pdfResult['message'] ?? 'Falha ao gerar PDF para envio por email.');
                $this->finishEnvioTrace($envioId, 'erro', 'dompdf', null, $error, null);
                return redirect()->to('/orcamentos/visualizar/' . $orcamentoId)->with('error', $error);
            }

            $pdfPath = (string) ($pdfResult['path'] ?? '');
            $pdfRelative = (string) ($pdfResult['relative'] ?? '');
        }

        $mailResult = $this->mailService->send($emailDestino, $assunto, $htmlBody, $pdfPath);
        $ok = !empty($mailResult['ok']);
        $provider = (string) ($mailResult['provider'] ?? 'email');
        $erroDetalhe = $ok ? null : (string) ($mailResult['error'] ?? $mailResult['message'] ?? 'Falha ao enviar email.');

        $this->finishEnvioTrace(
            $envioId,
            $ok ? 'enviado' : 'erro',
            $provider,
            null,
            $erroDetalhe,
            $pdfRelative
        );

        if ($ok) {
            $this->markAsDispatched($orcamento, 'email', $usuarioId > 0 ? $usuarioId : null);
            LogModel::registrar('orcamento_email', 'Orcamento ID ' . $orcamentoId . ' enviado por email.');
            return redirect()->to('/orcamentos/visualizar/' . $orcamentoId)->with('success', 'Orcamento enviado por email com sucesso.');
        }

        LogModel::registrar('orcamento_email_erro', 'Falha no envio de email do orcamento ID ' . $orcamentoId . '.');
        return redirect()->to('/orcamentos/visualizar/' . $orcamentoId)
            ->with('error', (string) ($mailResult['message'] ?? 'Falha ao enviar orcamento por email.'));
    }

    public function edit($id)
    {
        requirePermission('orcamentos', 'editar');

        $orcamento = $this->findOrcamento((int) $id);
        if (!$orcamento) {
            return redirect()->to('/orcamentos')->with('error', 'Orcamento nao encontrado.');
        }

        $itens = $this->itemModel->byOrcamento((int) $id);

        return view('orcamentos/form', $this->buildFormData([
            'title' => 'Editar Orcamento',
            'orcamento' => $orcamento,
            'itens' => $itens,
            'isEdit' => true,
            'actionUrl' => base_url('orcamentos/atualizar/' . (int) $id),
        ]));
    }

    public function update($id)
    {
        requirePermission('orcamentos', 'editar');

        $orcamentoId = (int) $id;
        $current = $this->orcamentoModel->find($orcamentoId);
        if (!$current) {
            return redirect()->to('/orcamentos')->with('error', 'Orcamento nao encontrado.');
        }

        if ($this->orcamentoModel->isLockedStatus((string) ($current['status'] ?? ''))) {
            return redirect()->to('/orcamentos/visualizar/' . $orcamentoId)
                ->with('error', 'Este orcamento esta bloqueado para edicao por ja estar aprovado/convertido.');
        }

        $payload = $this->extractOrcamentoPayload();
        $itens = $this->extractItensPayload();
        if (empty($itens)) {
            return redirect()->back()->withInput()->with('error', 'Adicione pelo menos um item no orcamento.');
        }

        $usuarioId = (int) (session()->get('user_id') ?? 0);
        $now = date('Y-m-d H:i:s');
        $payload['atualizado_por'] = $usuarioId > 0 ? $usuarioId : null;
        $payload['status'] = $payload['status'] ?: (string) ($current['status'] ?? OrcamentoModel::STATUS_RASCUNHO);
        $payload['status'] = $this->resolveApprovedStatus(array_merge($current, $payload), $payload['status']);
        $payload = array_merge($payload, $this->statusTimestampColumns($payload['status'], $now, $current));

        $statusAnterior = (string) ($current['status'] ?? OrcamentoModel::STATUS_RASCUNHO);
        $statusNovo = (string) $payload['status'];
        if (!$this->orcamentoService->canTransition($statusAnterior, $statusNovo)) {
            return redirect()->back()->withInput()->with('error', 'Transicao de status do orcamento nao permitida.');
        }

        $this->orcamentoModel->db->transStart();
        $this->orcamentoModel->update($orcamentoId, $payload);
        $this->itemModel->where('orcamento_id', $orcamentoId)->delete();
        $this->persistItens($orcamentoId, $itens);

        if ($statusAnterior !== $statusNovo) {
            $this->orcamentoService->registrarHistoricoStatus(
                $this->historicoModel,
                $orcamentoId,
                $statusAnterior,
                $statusNovo,
                $usuarioId > 0 ? $usuarioId : null,
                'Status alterado na edicao do orcamento',
                'interno'
            );
        }

        $this->orcamentoModel->db->transComplete();
        if (!$this->orcamentoModel->db->transStatus()) {
            return redirect()->back()->withInput()->with('error', 'Falha ao atualizar o orcamento.');
        }

        LogModel::registrar('orcamento_atualizado', 'Orcamento ID ' . $orcamentoId . ' atualizado.');
        return redirect()->to('/orcamentos/visualizar/' . $orcamentoId)->with('success', 'Orcamento atualizado com sucesso.');
    }

    public function updateStatus($id)
    {
        requirePermission('orcamentos', 'editar');

        $orcamentoId = (int) $id;
        $orcamento = $this->orcamentoModel->find($orcamentoId);
        if (!$orcamento) {
            return redirect()->to('/orcamentos')->with('error', 'Orcamento nao encontrado.');
        }

        $statusNovo = trim((string) $this->request->getPost('status'));
        if (!array_key_exists($statusNovo, $this->orcamentoModel->statusLabels())) {
            return redirect()->back()->with('error', 'Status informado para o orcamento e invalido.');
        }

        $statusAnterior = (string) ($orcamento['status'] ?? OrcamentoModel::STATUS_RASCUNHO);
        $statusNovo = $this->resolveApprovedStatus($orcamento, $statusNovo);
        if ($statusAnterior === $statusNovo) {
            return redirect()->back()->with('success', 'Status mantido sem alteracoes.');
        }
        if (!$this->orcamentoService->canTransition($statusAnterior, $statusNovo)) {
            return redirect()->back()->with('error', 'Transicao de status do orcamento nao permitida.');
        }

        $usuarioId = (int) (session()->get('user_id') ?? 0);
        $now = date('Y-m-d H:i:s');
        $update = [
            'status' => $statusNovo,
            'atualizado_por' => $usuarioId > 0 ? $usuarioId : null,
        ];
        $update = array_merge($update, $this->statusTimestampColumns($statusNovo, $now, $orcamento));

        $this->orcamentoModel->update($orcamentoId, $update);
        $this->orcamentoService->registrarHistoricoStatus(
            $this->historicoModel,
            $orcamentoId,
            $statusAnterior,
            $statusNovo,
            $usuarioId > 0 ? $usuarioId : null,
            'Status alterado manualmente',
            'interno'
        );

        LogModel::registrar('orcamento_status', 'Orcamento ID ' . $orcamentoId . ' alterado para ' . $statusNovo . '.');
        return redirect()->to('/orcamentos/visualizar/' . $orcamentoId)->with('success', 'Status atualizado com sucesso.');
    }

    public function runAutomation()
    {
        requirePermission('orcamentos', 'editar');

        $usuarioId = (int) (session()->get('user_id') ?? 0);
        $summary = $this->lifecycleService->runAutomations($usuarioId > 0 ? $usuarioId : null);
        $mensagem = sprintf(
            'Automacao executada. Vencidos: %d | Follow-ups aguardando: %d | Follow-ups vencidos: %d | Pendente OS: %d.',
            (int) ($summary['orcamentos_vencidos'] ?? 0),
            (int) ($summary['followups_aguardando'] ?? 0),
            (int) ($summary['followups_vencidos'] ?? 0),
            (int) ($summary['followups_pendente_os'] ?? 0)
        );

        LogModel::registrar('orcamento_automacao_manual', $mensagem);

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'ok' => true,
                'message' => $mensagem,
                'summary' => $summary,
            ]);
        }

        return redirect()->to('/orcamentos')->with('success', $mensagem);
    }

    public function convert($id)
    {
        requirePermission('orcamentos', 'editar');

        $orcamentoId = (int) $id;
        $orcamento = $this->findOrcamento($orcamentoId);
        if (!$orcamento) {
            return redirect()->to('/orcamentos')->with('error', 'Orcamento nao encontrado.');
        }

        $statusAtual = (string) ($orcamento['status'] ?? OrcamentoModel::STATUS_RASCUNHO);
        if (!in_array($statusAtual, [OrcamentoModel::STATUS_APROVADO, OrcamentoModel::STATUS_PENDENTE_OS], true)) {
            return redirect()->to('/orcamentos/visualizar/' . $orcamentoId)
                ->with('error', 'A conversao exige orcamento aprovado.');
        }

        $tipo = strtolower(trim((string) $this->request->getPost('tipo')));
        if (!in_array($tipo, ['os', 'venda'], true)) {
            return redirect()->to('/orcamentos/visualizar/' . $orcamentoId)
                ->with('error', 'Tipo de conversao invalido.');
        }

        $usuarioId = (int) (session()->get('user_id') ?? 0);
        $now = date('Y-m-d H:i:s');

        if ($tipo === 'os') {
            $itens = $this->itemModel->byOrcamento($orcamentoId);
            $conversion = $this->conversaoService->convertToOs($orcamento, $itens, $usuarioId > 0 ? $usuarioId : null);
            if (empty($conversion['ok'])) {
                return redirect()->to('/orcamentos/visualizar/' . $orcamentoId)
                    ->with('error', (string) ($conversion['message'] ?? 'Falha ao converter orcamento para OS.'));
            }

            $osId = (int) ($conversion['os_id'] ?? 0);
            $this->orcamentoModel->update($orcamentoId, [
                'status' => OrcamentoModel::STATUS_CONVERTIDO,
                'os_id' => $osId > 0 ? $osId : ((int) ($orcamento['os_id'] ?? 0) ?: null),
                'convertido_tipo' => 'os',
                'convertido_id' => $osId > 0 ? $osId : null,
                'atualizado_por' => $usuarioId > 0 ? $usuarioId : null,
            ]);

            $this->orcamentoService->registrarHistoricoStatus(
                $this->historicoModel,
                $orcamentoId,
                $statusAtual,
                OrcamentoModel::STATUS_CONVERTIDO,
                $usuarioId > 0 ? $usuarioId : null,
                'Orcamento convertido para OS #' . ($osId > 0 ? $osId : '-'),
                'interno'
            );

            LogModel::registrar('orcamento_convertido_os', 'Orcamento ID ' . $orcamentoId . ' convertido para OS.');

            $mensagem = trim((string) ($conversion['message'] ?? 'Orcamento convertido para OS com sucesso.'));
            return redirect()->to('/orcamentos/visualizar/' . $orcamentoId)->with('success', $mensagem);
        }

        $this->orcamentoModel->update($orcamentoId, [
            'status' => OrcamentoModel::STATUS_CONVERTIDO,
            'convertido_tipo' => 'venda_manual',
            'convertido_id' => null,
            'atualizado_por' => $usuarioId > 0 ? $usuarioId : null,
        ]);

        $this->orcamentoService->registrarHistoricoStatus(
            $this->historicoModel,
            $orcamentoId,
            $statusAtual,
            OrcamentoModel::STATUS_CONVERTIDO,
            $usuarioId > 0 ? $usuarioId : null,
            'Orcamento convertido para venda manual.',
            'interno'
        );

        LogModel::registrar('orcamento_convertido_venda', 'Orcamento ID ' . $orcamentoId . ' convertido para venda manual.');
        return redirect()->to('/orcamentos/visualizar/' . $orcamentoId)
            ->with('success', 'Orcamento convertido para venda manual com sucesso.');
    }

    public function quickCreateAndSendFromConversa()
    {
        requirePermission('orcamentos', 'criar');
        requirePermission('orcamentos', 'editar');

        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(405)->setJSON([
                'ok' => false,
                'message' => 'Metodo nao permitido para este endpoint.',
            ]);
        }

        $conversaId = (int) ($this->request->getPost('conversa_id') ?? 0);
        if ($conversaId <= 0) {
            return $this->response->setStatusCode(422)->setJSON([
                'ok' => false,
                'message' => 'Conversa invalida para gerar orcamento rapido.',
            ]);
        }

        $conversa = (new ConversaWhatsappModel())->find($conversaId);
        if (!$conversa) {
            return $this->response->setStatusCode(404)->setJSON([
                'ok' => false,
                'message' => 'Conversa nao encontrada.',
            ]);
        }

        $itemDescricao = trim((string) $this->request->getPost('item_descricao'));
        if ($itemDescricao === '') {
            return $this->response->setStatusCode(422)->setJSON([
                'ok' => false,
                'message' => 'Descricao do item obrigatoria para orcamento rapido.',
            ]);
        }

        $itemValor = max(0, $this->orcamentoService->normalizeMoney($this->request->getPost('item_valor')));
        if ($itemValor <= 0) {
            return $this->response->setStatusCode(422)->setJSON([
                'ok' => false,
                'message' => 'Valor do item deve ser maior que zero.',
            ]);
        }

        $telefone = trim((string) $this->request->getPost('telefone_contato'));
        if ($telefone === '') {
            $telefone = trim((string) ($conversa['telefone'] ?? ''));
        }
        if (!$this->isPhoneValid($telefone)) {
            return $this->response->setStatusCode(422)->setJSON([
                'ok' => false,
                'message' => 'Telefone da conversa invalido para envio automatico.',
            ]);
        }

        $usuarioId = (int) (session()->get('user_id') ?? 0);
        $quantidade = max(1, $this->orcamentoService->normalizeQuantity($this->request->getPost('item_quantidade') ?? 1));
        $itemTotal = round($quantidade * $itemValor, 2);
        $itens = [[
            'tipo_item' => trim((string) ($this->request->getPost('item_tipo') ?? 'servico')) ?: 'servico',
            'descricao' => $itemDescricao,
            'quantidade' => $quantidade,
            'valor_unitario' => round($itemValor, 2),
            'desconto' => 0,
            'acrescimo' => 0,
            'total' => $itemTotal,
            'ordem' => 1,
            'observacoes' => null,
        ]];
        $totais = $this->orcamentoService->calculateTotals($itens, 0, 0);

        $clienteId = (int) ($this->request->getPost('cliente_id') ?? 0);
        if ($clienteId <= 0) {
            $clienteId = (int) ($conversa['cliente_id'] ?? 0);
        }

        $osId = (int) ($this->request->getPost('os_id') ?? 0);
        if ($osId <= 0) {
            $osId = (int) ($conversa['os_id_principal'] ?? 0);
        }
        $equipamentoId = (int) ($this->request->getPost('equipamento_id') ?? 0);
        if ($osId > 0) {
            $osVinculada = (new OsModel())->find($osId);
            if ($osVinculada) {
                if ($clienteId <= 0) {
                    $clienteId = (int) ($osVinculada['cliente_id'] ?? 0);
                }
                if ($equipamentoId <= 0) {
                    $equipamentoId = (int) ($osVinculada['equipamento_id'] ?? 0);
                }
            }
        }

        $titulo = trim((string) $this->request->getPost('titulo'));
        if ($titulo === '') {
            $titulo = $osId > 0
                ? 'Orcamento rapido para OS #' . $osId
                : 'Orcamento rapido via conversa #' . $conversaId;
        }

        $validadeDias = (int) ($this->request->getPost('validade_dias') ?? 7);
        if ($validadeDias < 1) {
            $validadeDias = 7;
        }
        if ($validadeDias > 60) {
            $validadeDias = 60;
        }

        $clienteNomeAvulso = trim((string) $this->request->getPost('cliente_nome_avulso'));
        if ($clienteNomeAvulso === '') {
            $clienteNomeAvulso = trim((string) ($conversa['nome_contato'] ?? 'Cliente eventual'));
        }

        $payload = [
            'status' => OrcamentoModel::STATUS_RASCUNHO,
            'origem' => 'conversa_rapida',
            'cliente_id' => $clienteId > 0 ? $clienteId : null,
            'cliente_nome_avulso' => $clienteId > 0 ? null : $clienteNomeAvulso,
            'telefone_contato' => $telefone,
            'email_contato' => trim((string) $this->request->getPost('email_contato')) ?: null,
            'os_id' => $osId > 0 ? $osId : null,
            'equipamento_id' => $equipamentoId > 0 ? $equipamentoId : null,
            'conversa_id' => $conversaId,
            'responsavel_id' => $usuarioId > 0 ? $usuarioId : null,
            'criado_por' => $usuarioId > 0 ? $usuarioId : null,
            'atualizado_por' => $usuarioId > 0 ? $usuarioId : null,
            'titulo' => $titulo,
            'validade_dias' => $validadeDias,
            'validade_data' => date('Y-m-d', strtotime('+' . $validadeDias . ' days')),
            'subtotal' => $totais['subtotal'],
            'desconto' => 0,
            'acrescimo' => 0,
            'total' => $totais['total'],
            'prazo_execucao' => null,
            'observacoes' => trim((string) $this->request->getPost('observacoes')) ?: null,
            'condicoes' => trim((string) $this->request->getPost('condicoes')) ?: null,
            'motivo_rejeicao' => null,
            'token_publico' => $this->orcamentoService->generateToken(),
            'token_expira_em' => date('Y-m-d H:i:s', strtotime('+30 days')),
        ];

        $this->orcamentoModel->db->transStart();
        $this->orcamentoModel->insert($payload);
        $orcamentoId = (int) $this->orcamentoModel->getInsertID();
        if ($orcamentoId <= 0) {
            $this->orcamentoModel->db->transRollback();
            return $this->response->setStatusCode(500)->setJSON([
                'ok' => false,
                'message' => 'Nao foi possivel salvar o orcamento rapido.',
            ]);
        }

        $numero = $this->orcamentoService->ensureNumero($this->orcamentoModel, $orcamentoId);
        $this->persistItens($orcamentoId, $itens);
        $this->orcamentoService->registrarHistoricoStatus(
            $this->historicoModel,
            $orcamentoId,
            null,
            OrcamentoModel::STATUS_RASCUNHO,
            $usuarioId > 0 ? $usuarioId : null,
            'Criacao rapida pela Central de Mensagens',
            'interno'
        );
        $this->orcamentoModel->db->transComplete();

        if (!$this->orcamentoModel->db->transStatus()) {
            return $this->response->setStatusCode(500)->setJSON([
                'ok' => false,
                'message' => 'Falha ao concluir gravacao do orcamento rapido.',
            ]);
        }

        $orcamento = $this->findOrcamento($orcamentoId);
        if (!$orcamento) {
            return $this->response->setStatusCode(500)->setJSON([
                'ok' => false,
                'message' => 'Orcamento criado, mas nao foi possivel carregar o registro.',
                'orcamento_id' => $orcamentoId,
            ]);
        }

        $orcamento = $this->ensurePublicToken($orcamento);
        $mensagem = trim((string) $this->request->getPost('mensagem_whatsapp'));
        if ($mensagem === '') {
            $mensagem = $this->buildDefaultWhatsAppMessage($orcamento);
        }
        $incluirPdf = (string) $this->request->getPost('incluir_pdf') !== '0';
        $dispatch = $this->dispatchWhatsAppMessage(
            $orcamento,
            $telefone,
            $mensagem,
            $incluirPdf,
            $usuarioId
        );

        if (empty($dispatch['ok'])) {
            return $this->response->setStatusCode(502)->setJSON([
                'ok' => false,
                'message' => (string) ($dispatch['message'] ?? 'Orcamento criado, mas houve falha no envio por WhatsApp.'),
                'orcamento_id' => $orcamentoId,
                'numero' => $numero,
                'view_url' => base_url('orcamentos/visualizar/' . $orcamentoId),
            ]);
        }

        LogModel::registrar('orcamento_conversa_rapida', 'Orcamento rapido ' . $numero . ' criado e enviado pela conversa #' . $conversaId . '.');

        return $this->response->setJSON([
            'ok' => true,
            'message' => !empty($dispatch['duplicate'])
                ? 'Orcamento ' . $numero . ' gerado. Envio duplicado foi evitado automaticamente.'
                : 'Orcamento ' . $numero . ' gerado e enviado com sucesso.',
            'orcamento_id' => $orcamentoId,
            'numero' => $numero,
            'view_url' => base_url('orcamentos/visualizar/' . $orcamentoId),
            'public_url' => !empty($orcamento['token_publico']) ? base_url('orcamento/' . $orcamento['token_publico']) : null,
        ]);
    }

    public function delete($id)
    {
        requirePermission('orcamentos', 'excluir');

        $orcamentoId = (int) $id;
        $orcamento = $this->orcamentoModel->find($orcamentoId);
        if (!$orcamento) {
            return redirect()->to('/orcamentos')->with('error', 'Orcamento nao encontrado.');
        }

        $status = (string) ($orcamento['status'] ?? OrcamentoModel::STATUS_RASCUNHO);
        if (!in_array($status, [OrcamentoModel::STATUS_RASCUNHO, OrcamentoModel::STATUS_CANCELADO, OrcamentoModel::STATUS_REJEITADO], true)) {
            return redirect()->to('/orcamentos/visualizar/' . $orcamentoId)
                ->with('error', 'Somente orcamentos em rascunho, cancelado ou rejeitado podem ser excluidos.');
        }

        $this->orcamentoModel->delete($orcamentoId);
        LogModel::registrar('orcamento_excluido', 'Orcamento ID ' . $orcamentoId . ' excluido.');
        return redirect()->to('/orcamentos')->with('success', 'Orcamento excluido com sucesso.');
    }

    private function canDispatchByStatus(string $status): bool
    {
        return !in_array($status, [
            OrcamentoModel::STATUS_APROVADO,
            OrcamentoModel::STATUS_PENDENTE_OS,
            OrcamentoModel::STATUS_CANCELADO,
            OrcamentoModel::STATUS_CONVERTIDO,
        ], true);
    }

    /**
     * @return array<string,mixed>
     */
    private function dispatchWhatsAppMessage(
        array $orcamento,
        string $telefone,
        string $mensagem,
        bool $incluirPdf,
        int $usuarioId = 0
    ): array {
        $orcamentoId = (int) ($orcamento['id'] ?? 0);
        if ($orcamentoId <= 0) {
            return [
                'ok' => false,
                'message' => 'Orcamento invalido para envio no WhatsApp.',
            ];
        }

        $pdfPath = null;
        $pdfRelative = null;
        $envioId = $this->startEnvioTrace(
            $orcamentoId,
            'whatsapp',
            $telefone,
            $mensagem,
            null,
            $usuarioId
        );

        if ($incluirPdf) {
            $pdfResult = $this->resolvePdfDocument(
                $orcamentoId,
                $usuarioId > 0 ? $usuarioId : null,
                'anexo_whatsapp',
                false
            );

            if (empty($pdfResult['ok'])) {
                $error = (string) ($pdfResult['message'] ?? 'Falha ao gerar PDF para envio no WhatsApp.');
                $this->finishEnvioTrace($envioId, 'erro', 'dompdf', null, $error, null);
                return [
                    'ok' => false,
                    'message' => $error,
                ];
            }

            $pdfPath = (string) ($pdfResult['path'] ?? '');
            $pdfRelative = (string) ($pdfResult['relative'] ?? '');
        }

        $whatsService = new WhatsAppService();
        $result = $whatsService->sendRaw(
            (int) ($orcamento['os_id'] ?? 0),
            (int) ($orcamento['cliente_id'] ?? 0),
            $telefone,
            $mensagem,
            'orcamento_envio',
            null,
            $usuarioId > 0 ? $usuarioId : null,
            [
                'arquivo_path' => $pdfPath,
                'arquivo' => $pdfRelative,
                'conversa_id' => (int) ($orcamento['conversa_id'] ?? 0),
            ]
        );

        $ok = !empty($result['ok']);
        $duplicate = !empty($result['duplicate']);
        $statusEnvio = $ok ? ($duplicate ? 'duplicado' : 'enviado') : 'erro';
        $provider = (string) ($result['provider'] ?? get_config('whatsapp_direct_provider', get_config('whatsapp_provider', 'menuia')));
        $referencia = (string) ($result['message_id'] ?? '');
        $erroDetalhe = $ok ? null : (string) ($result['message'] ?? 'Falha ao enviar WhatsApp.');

        $this->finishEnvioTrace(
            $envioId,
            $statusEnvio,
            $provider,
            $referencia !== '' ? $referencia : null,
            $erroDetalhe,
            $pdfRelative
        );

        if ($ok) {
            $this->markAsDispatched($orcamento, 'whatsapp', $usuarioId > 0 ? $usuarioId : null);
        }

        return [
            'ok' => $ok,
            'duplicate' => $duplicate,
            'message' => $erroDetalhe ?: '',
            'raw' => $result,
        ];
    }

    private function markAsDispatched(array $orcamento, string $canal, ?int $usuarioId = null): void
    {
        $orcamentoId = (int) ($orcamento['id'] ?? 0);
        if ($orcamentoId <= 0) {
            return;
        }

        $statusAtual = (string) ($orcamento['status'] ?? OrcamentoModel::STATUS_RASCUNHO);
        $statusNovo = $statusAtual;
        if (in_array($statusAtual, [
            OrcamentoModel::STATUS_RASCUNHO,
            OrcamentoModel::STATUS_ENVIADO,
            OrcamentoModel::STATUS_AGUARDANDO,
            OrcamentoModel::STATUS_REJEITADO,
            OrcamentoModel::STATUS_VENCIDO,
        ], true)) {
            $statusNovo = OrcamentoModel::STATUS_AGUARDANDO;
        }

        $update = [
            'atualizado_por' => $usuarioId,
        ];
        if ($statusNovo !== $statusAtual) {
            $update['status'] = $statusNovo;
        }
        if (empty($orcamento['enviado_em'])) {
            $update['enviado_em'] = date('Y-m-d H:i:s');
        }

        $this->orcamentoModel->update($orcamentoId, $update);

        if ($statusNovo !== $statusAtual) {
            $this->orcamentoService->registrarHistoricoStatus(
                $this->historicoModel,
                $orcamentoId,
                $statusAtual,
                $statusNovo,
                $usuarioId,
                'Status atualizado apos envio via ' . strtoupper($canal),
                'interno'
            );
        }
    }

    private function syncLifecycleIfDue(): void
    {
        $lastRun = (int) (session()->get('orcamentos_lifecycle_last_run') ?? 0);
        if ($lastRun > 0 && (time() - $lastRun) < 180) {
            return;
        }

        try {
            $usuarioId = (int) (session()->get('user_id') ?? 0);
            $this->lifecycleService->runAutomations($usuarioId > 0 ? $usuarioId : null);
            session()->set('orcamentos_lifecycle_last_run', time());
        } catch (\Throwable $e) {
            log_message('warning', '[Orcamentos] automacao de vencimento/follow-up falhou: ' . $e->getMessage());
        }
    }

    /**
     * @param array<string,mixed> $scope
     */
    private function resolveApprovedStatus(array $scope, string $status): string
    {
        if ($status !== OrcamentoModel::STATUS_APROVADO) {
            return $status;
        }

        $osId = (int) ($scope['os_id'] ?? 0);
        return $osId > 0 ? OrcamentoModel::STATUS_APROVADO : OrcamentoModel::STATUS_PENDENTE_OS;
    }

    private function ensurePublicToken(array $orcamento): array
    {
        $orcamentoId = (int) ($orcamento['id'] ?? 0);
        if ($orcamentoId <= 0) {
            return $orcamento;
        }

        $token = trim((string) ($orcamento['token_publico'] ?? ''));
        $expiraEm = trim((string) ($orcamento['token_expira_em'] ?? ''));
        $expirado = $expiraEm !== '' && strtotime($expiraEm) < time();

        if ($token !== '' && !$expirado) {
            return $orcamento;
        }

        $novoToken = $this->orcamentoService->generateToken();
        $novoExpiraEm = date('Y-m-d H:i:s', strtotime('+30 days'));

        $this->orcamentoModel->update($orcamentoId, [
            'token_publico' => $novoToken,
            'token_expira_em' => $novoExpiraEm,
        ]);

        $orcamento['token_publico'] = $novoToken;
        $orcamento['token_expira_em'] = $novoExpiraEm;
        return $orcamento;
    }

    private function resolvePdfDocument(
        int $orcamentoId,
        ?int $usuarioId,
        string $contexto,
        bool $forceGenerate = false
    ): array {
        if (!$forceGenerate) {
            $latest = $this->envioModel->latestByCanal($orcamentoId, 'pdf', 'gerado');
            $relative = trim((string) ($latest['documento_path'] ?? ''));
            if ($relative !== '') {
                $fullPath = FCPATH . ltrim($relative, '/\\');
                if (is_file($fullPath)) {
                    return [
                        'ok' => true,
                        'path' => $fullPath,
                        'relative' => $relative,
                        'url' => base_url($relative),
                        'nome_arquivo' => basename($fullPath),
                        'generated' => false,
                    ];
                }
            }
        }

        $envioId = $this->startEnvioTrace(
            $orcamentoId,
            'pdf',
            'interno:' . $contexto,
            'Geracao de PDF do orcamento (' . $contexto . ')',
            null,
            $usuarioId ?? 0
        );

        $result = $this->pdfService->gerar($orcamentoId, $usuarioId);
        if (empty($result['ok'])) {
            $error = (string) ($result['message'] ?? 'Falha ao gerar PDF do orcamento.');
            $this->finishEnvioTrace($envioId, 'erro', 'dompdf', null, $error, null);
            return [
                'ok' => false,
                'message' => $error,
            ];
        }

        $documentoPath = (string) ($result['relative'] ?? '');
        $referencia = 'v' . (string) ($result['versao'] ?? '1');
        $this->finishEnvioTrace(
            $envioId,
            'gerado',
            'dompdf',
            $referencia,
            null,
            $documentoPath
        );

        return [
            'ok' => true,
            'path' => (string) ($result['path'] ?? ''),
            'relative' => $documentoPath,
            'url' => (string) ($result['url'] ?? ''),
            'nome_arquivo' => (string) ($result['nome_arquivo'] ?? ''),
            'generated' => true,
        ];
    }

    private function startEnvioTrace(
        int $orcamentoId,
        string $canal,
        ?string $destino,
        ?string $mensagem,
        ?string $documentoPath,
        int $usuarioId = 0
    ): ?int {
        $insertId = $this->envioModel->insert([
            'orcamento_id' => $orcamentoId,
            'canal' => trim($canal),
            'destino' => $destino !== null && trim($destino) !== '' ? trim($destino) : null,
            'mensagem' => $mensagem !== null && trim($mensagem) !== '' ? trim($mensagem) : null,
            'documento_path' => $documentoPath !== null && trim($documentoPath) !== '' ? trim($documentoPath) : null,
            'status' => 'pendente',
            'provedor' => null,
            'referencia_externa' => null,
            'erro_detalhe' => null,
            'enviado_por' => $usuarioId > 0 ? $usuarioId : null,
            'enviado_em' => date('Y-m-d H:i:s'),
        ], true);

        return $insertId ? (int) $insertId : null;
    }

    private function finishEnvioTrace(
        ?int $envioId,
        string $status,
        ?string $provedor = null,
        ?string $referenciaExterna = null,
        ?string $erroDetalhe = null,
        ?string $documentoPath = null
    ): void {
        if (empty($envioId) || $envioId <= 0) {
            return;
        }

        $update = [
            'status' => trim($status) !== '' ? trim($status) : 'erro',
            'provedor' => $provedor !== null && trim($provedor) !== '' ? trim($provedor) : null,
            'referencia_externa' => $referenciaExterna !== null && trim($referenciaExterna) !== '' ? trim($referenciaExterna) : null,
            'erro_detalhe' => $erroDetalhe !== null && trim($erroDetalhe) !== '' ? trim($erroDetalhe) : null,
            'enviado_em' => date('Y-m-d H:i:s'),
        ];

        if ($documentoPath !== null && trim($documentoPath) !== '') {
            $update['documento_path'] = trim($documentoPath);
        }

        $this->envioModel->update($envioId, $update);
    }

    private function saveEnvioError(
        int $orcamentoId,
        string $canal,
        ?string $destino,
        ?string $mensagem,
        string $erroDetalhe,
        int $usuarioId = 0
    ): void {
        $envioId = $this->startEnvioTrace(
            $orcamentoId,
            $canal,
            $destino,
            $mensagem,
            null,
            $usuarioId
        );
        $this->finishEnvioTrace($envioId, 'erro', null, null, $erroDetalhe, null);
    }

    private function buildDefaultWhatsAppMessage(array $orcamento): string
    {
        $cliente = $this->resolveClienteNome($orcamento);
        $numero = trim((string) ($orcamento['numero'] ?? '#'));
        $total = formatMoney($orcamento['total'] ?? 0);
        $validade = formatDate($orcamento['validade_data'] ?? null);

        $mensagem = 'Ola ' . $cliente . ', segue o orcamento ' . $numero . ' no valor total de ' . $total . '.';
        if ($validade !== '-') {
            $mensagem .= "\nValidade: " . $validade . '.';
        }

        if (!empty($orcamento['token_publico'])) {
            $mensagem .= "\nAprovacao online: " . base_url('orcamento/' . $orcamento['token_publico']);
        }

        $mensagem .= "\nFico a disposicao para qualquer duvida.";
        return $mensagem;
    }

    private function buildDefaultEmailSubject(array $orcamento): string
    {
        $numero = trim((string) ($orcamento['numero'] ?? '#'));
        $empresa = trim((string) get_config('empresa_nome', 'Assistencia Tecnica'));
        return 'Orcamento ' . $numero . ' - ' . $empresa;
    }

    private function buildDefaultEmailBody(array $orcamento, string $mensagemLivre = ''): string
    {
        $cliente = htmlspecialchars($this->resolveClienteNome($orcamento), ENT_QUOTES, 'UTF-8');
        $numero = htmlspecialchars((string) ($orcamento['numero'] ?? '#'), ENT_QUOTES, 'UTF-8');
        $total = htmlspecialchars(formatMoney($orcamento['total'] ?? 0), ENT_QUOTES, 'UTF-8');
        $validade = htmlspecialchars(formatDate($orcamento['validade_data'] ?? null), ENT_QUOTES, 'UTF-8');
        $mensagemPersonalizada = trim($mensagemLivre);
        $mensagemHtml = '';
        if ($mensagemPersonalizada !== '') {
            $mensagemHtml = '<p style="margin:0 0 12px;">' .
                nl2br(htmlspecialchars($mensagemPersonalizada, ENT_QUOTES, 'UTF-8')) .
                '</p>';
        }

        $linkPublico = '';
        if (!empty($orcamento['token_publico'])) {
            $safeLink = htmlspecialchars(base_url('orcamento/' . $orcamento['token_publico']), ENT_QUOTES, 'UTF-8');
            $linkPublico = '<p style="margin:12px 0 0;"><strong>Aprovacao online:</strong> <a href="' . $safeLink . '">' . $safeLink . '</a></p>';
        }

        return
            '<div style="font-family:Arial,sans-serif;color:#1f2937;font-size:14px;line-height:1.5;">' .
                '<h2 style="margin:0 0 12px;color:#0f172a;">Orcamento ' . $numero . '</h2>' .
                '<p style="margin:0 0 12px;">Ola <strong>' . $cliente . '</strong>, segue seu orcamento em anexo.</p>' .
                $mensagemHtml .
                '<table style="border-collapse:collapse;width:100%;max-width:520px;">' .
                    '<tr><td style="padding:6px;border:1px solid #e5e7eb;"><strong>Total</strong></td><td style="padding:6px;border:1px solid #e5e7eb;">' . $total . '</td></tr>' .
                    '<tr><td style="padding:6px;border:1px solid #e5e7eb;"><strong>Validade</strong></td><td style="padding:6px;border:1px solid #e5e7eb;">' . $validade . '</td></tr>' .
                '</table>' .
                $linkPublico .
                '<p style="margin:12px 0 0;">Agradecemos o contato.</p>' .
            '</div>';
    }

    private function resolveClienteNome(array $orcamento): string
    {
        $cliente = trim((string) ($orcamento['cliente_nome'] ?? ''));
        if ($cliente !== '') {
            return $cliente;
        }

        $avulso = trim((string) ($orcamento['cliente_nome_avulso'] ?? ''));
        if ($avulso !== '') {
            return $avulso;
        }

        return 'cliente';
    }

    private function isPhoneValid(string $phone): bool
    {
        $digits = preg_replace('/\\D+/', '', $phone) ?? '';
        return strlen($digits) >= 8;
    }

    private function findOrcamento(int $orcamentoId): ?array
    {
        $row = $this->orcamentoModel
            ->select('orcamentos.*, clientes.nome_razao as cliente_nome, os.numero_os, conversas_whatsapp.telefone as conversa_telefone')
            ->join('clientes', 'clientes.id = orcamentos.cliente_id', 'left')
            ->join('os', 'os.id = orcamentos.os_id', 'left')
            ->join('conversas_whatsapp', 'conversas_whatsapp.id = orcamentos.conversa_id', 'left')
            ->where('orcamentos.id', $orcamentoId)
            ->first();

        return $row ?: null;
    }

    private function buildFormData(array $overrides = []): array
    {
        $clientes = (new ClienteModel())
            ->select('id, nome_razao, telefone1, email')
            ->orderBy('nome_razao', 'ASC')
            ->findAll();

        $defaults = [
            'clientes' => $clientes,
            'statusLabels' => $this->orcamentoModel->statusLabels(),
        ];

        return array_merge($defaults, $overrides);
    }

    private function prefillFromRequest(): array
    {
        $prefill = [
            'origem' => trim((string) $this->request->getGet('origem')) ?: 'manual',
            'cliente_id' => (int) ($this->request->getGet('cliente_id') ?? 0) ?: null,
            'os_id' => (int) ($this->request->getGet('os_id') ?? 0) ?: null,
            'equipamento_id' => (int) ($this->request->getGet('equipamento_id') ?? 0) ?: null,
            'conversa_id' => (int) ($this->request->getGet('conversa_id') ?? 0) ?: null,
            'telefone_contato' => trim((string) $this->request->getGet('telefone')),
            'email_contato' => trim((string) $this->request->getGet('email')),
            'cliente_nome_avulso' => trim((string) ($this->request->getGet('nome_hint') ?? $this->request->getGet('nome'))),
            'validade_dias' => 7,
            'validade_data' => date('Y-m-d', strtotime('+7 days')),
            'desconto' => 0,
            'acrescimo' => 0,
            'subtotal' => 0,
            'total' => 0,
            'titulo' => '',
            'prazo_execucao' => '',
            'observacoes' => '',
            'condicoes' => '',
        ];

        $osId = (int) ($prefill['os_id'] ?? 0);
        if ($osId > 0) {
            $os = (new OsModel())->getComplete($osId);
            if ($os) {
                $prefill['cliente_id'] = (int) ($os['cliente_id'] ?? 0) ?: $prefill['cliente_id'];
                $prefill['equipamento_id'] = (int) ($os['equipamento_id'] ?? 0) ?: $prefill['equipamento_id'];
                $prefill['telefone_contato'] = (string) ($os['cliente_telefone'] ?? $prefill['telefone_contato']);
                $prefill['email_contato'] = (string) ($os['cliente_email'] ?? $prefill['email_contato']);
                $prefill['titulo'] = 'Orcamento para OS ' . (string) ($os['numero_os'] ?? ('#' . $osId));
                $prefill['observacoes'] = (string) ($os['diagnostico_tecnico'] ?? '');
            }
        }

        $conversaId = (int) ($prefill['conversa_id'] ?? 0);
        if ($conversaId > 0) {
            $conversa = (new ConversaWhatsappModel())->find($conversaId);
            if ($conversa) {
                if ((int) ($conversa['cliente_id'] ?? 0) > 0 && empty($prefill['cliente_id'])) {
                    $prefill['cliente_id'] = (int) $conversa['cliente_id'];
                }
                if ((int) ($conversa['os_id_principal'] ?? 0) > 0 && empty($prefill['os_id'])) {
                    $prefill['os_id'] = (int) $conversa['os_id_principal'];
                }
                if (trim((string) ($prefill['telefone_contato'] ?? '')) === '') {
                    $prefill['telefone_contato'] = (string) ($conversa['telefone'] ?? '');
                }
                if (trim((string) ($prefill['cliente_nome_avulso'] ?? '')) === '') {
                    $prefill['cliente_nome_avulso'] = (string) ($conversa['nome_contato'] ?? '');
                }
            }
        }

        $clienteId = (int) ($prefill['cliente_id'] ?? 0);
        if ($clienteId > 0) {
            $cliente = (new ClienteModel())->find($clienteId);
            if ($cliente) {
                if (trim((string) ($prefill['telefone_contato'] ?? '')) === '') {
                    $prefill['telefone_contato'] = (string) ($cliente['telefone1'] ?? '');
                }
                if (trim((string) ($prefill['email_contato'] ?? '')) === '') {
                    $prefill['email_contato'] = (string) ($cliente['email'] ?? '');
                }
                $prefill['cliente_nome_avulso'] = '';
            }
        }

        $equipamentoId = (int) ($prefill['equipamento_id'] ?? 0);
        if ($equipamentoId > 0 && empty($prefill['os_id'])) {
            $equipamento = (new EquipamentoModel())->find($equipamentoId);
            if ($equipamento && empty($prefill['cliente_id'])) {
                $prefill['cliente_id'] = (int) ($equipamento['cliente_id'] ?? 0) ?: null;
            }
        }

        return $prefill;
    }

    private function extractOrcamentoPayload(): array
    {
        $status = trim((string) $this->request->getPost('status'));
        if (!array_key_exists($status, $this->orcamentoModel->statusLabels())) {
            $status = OrcamentoModel::STATUS_RASCUNHO;
        }

        $clienteId = (int) ($this->request->getPost('cliente_id') ?? 0);
        $desconto = max(0, $this->orcamentoService->normalizeMoney($this->request->getPost('desconto')));
        $acrescimo = max(0, $this->orcamentoService->normalizeMoney($this->request->getPost('acrescimo')));
        $itens = $this->extractItensPayload();
        $totais = $this->orcamentoService->calculateTotals($itens, $desconto, $acrescimo);

        $validadeDias = (int) ($this->request->getPost('validade_dias') ?? 7);
        if ($validadeDias <= 0) {
            $validadeDias = 7;
        }

        $validadeData = trim((string) $this->request->getPost('validade_data'));
        if ($validadeData === '') {
            $validadeData = date('Y-m-d', strtotime('+' . $validadeDias . ' days'));
        }

        return [
            'status' => $status,
            'origem' => trim((string) $this->request->getPost('origem')) ?: 'manual',
            'cliente_id' => $clienteId > 0 ? $clienteId : null,
            'cliente_nome_avulso' => trim((string) $this->request->getPost('cliente_nome_avulso')) ?: null,
            'telefone_contato' => trim((string) $this->request->getPost('telefone_contato')) ?: null,
            'email_contato' => trim((string) $this->request->getPost('email_contato')) ?: null,
            'os_id' => (int) ($this->request->getPost('os_id') ?? 0) ?: null,
            'equipamento_id' => (int) ($this->request->getPost('equipamento_id') ?? 0) ?: null,
            'conversa_id' => (int) ($this->request->getPost('conversa_id') ?? 0) ?: null,
            'responsavel_id' => (int) ($this->request->getPost('responsavel_id') ?? 0) ?: null,
            'titulo' => trim((string) $this->request->getPost('titulo')) ?: null,
            'validade_dias' => $validadeDias,
            'validade_data' => $validadeData,
            'subtotal' => $totais['subtotal'],
            'desconto' => $desconto,
            'acrescimo' => $acrescimo,
            'total' => $totais['total'],
            'prazo_execucao' => trim((string) $this->request->getPost('prazo_execucao')) ?: null,
            'observacoes' => trim((string) $this->request->getPost('observacoes')) ?: null,
            'condicoes' => trim((string) $this->request->getPost('condicoes')) ?: null,
            'motivo_rejeicao' => trim((string) $this->request->getPost('motivo_rejeicao')) ?: null,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function extractItensPayload(): array
    {
        $tipos = (array) $this->request->getPost('item_tipo');
        $descricoes = (array) $this->request->getPost('item_descricao');
        $quantidades = (array) $this->request->getPost('item_quantidade');
        $valores = (array) $this->request->getPost('item_valor_unitario');
        $descontos = (array) $this->request->getPost('item_desconto');
        $acrescimos = (array) $this->request->getPost('item_acrescimo');
        $observacoes = (array) $this->request->getPost('item_observacao');

        $totalLinhas = max(count($descricoes), count($tipos), count($quantidades), count($valores));
        $itens = [];

        for ($i = 0; $i < $totalLinhas; $i++) {
            $descricao = trim((string) ($descricoes[$i] ?? ''));
            if ($descricao === '') {
                continue;
            }

            $tipo = trim((string) ($tipos[$i] ?? 'servico'));
            if ($tipo === '') {
                $tipo = 'servico';
            }

            $quantidade = $this->orcamentoService->normalizeQuantity($quantidades[$i] ?? 1);
            $valorUnitario = max(0, $this->orcamentoService->normalizeMoney($valores[$i] ?? 0));
            $desconto = max(0, $this->orcamentoService->normalizeMoney($descontos[$i] ?? 0));
            $acrescimo = max(0, $this->orcamentoService->normalizeMoney($acrescimos[$i] ?? 0));
            $total = ($quantidade * $valorUnitario) - $desconto + $acrescimo;
            if ($total < 0) {
                $total = 0;
            }

            $itens[] = [
                'tipo_item' => $tipo,
                'descricao' => $descricao,
                'quantidade' => $quantidade,
                'valor_unitario' => $valorUnitario,
                'desconto' => $desconto,
                'acrescimo' => $acrescimo,
                'total' => round($total, 2),
                'ordem' => count($itens) + 1,
                'observacoes' => trim((string) ($observacoes[$i] ?? '')) ?: null,
            ];
        }

        return $itens;
    }

    /**
     * @param array<int, array<string, mixed>> $itens
     */
    private function persistItens(int $orcamentoId, array $itens): void
    {
        foreach ($itens as $item) {
            $item['orcamento_id'] = $orcamentoId;
            $this->itemModel->insert($item);
        }
    }

    private function statusTimestampColumns(string $status, string $now, ?array $current = null): array
    {
        $columns = [];
        $current = $current ?? [];

        if ($status === OrcamentoModel::STATUS_ENVIADO && empty($current['enviado_em'])) {
            $columns['enviado_em'] = $now;
        }
        if (in_array($status, [OrcamentoModel::STATUS_APROVADO, OrcamentoModel::STATUS_PENDENTE_OS], true) && empty($current['aprovado_em'])) {
            $columns['aprovado_em'] = $now;
        }
        if ($status === OrcamentoModel::STATUS_REJEITADO && empty($current['rejeitado_em'])) {
            $columns['rejeitado_em'] = $now;
        }
        if ($status === OrcamentoModel::STATUS_CANCELADO && empty($current['cancelado_em'])) {
            $columns['cancelado_em'] = $now;
        }

        return $columns;
    }
}
