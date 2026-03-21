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
use App\Models\AcessãorioOsModel;
use App\Models\FotoAcessãorioModel;
use App\Models\EstadoFisicoOsModel;
use App\Models\FotoEstadoFisicoModel;
use App\Models\ContatoModel;
use App\Models\ConversaWhatsappModel;
use App\Models\DefeitoRelatadoModel;
use App\Models\OsStatusModel;
use App\Models\OsStatusHistoricoModel;
use App\Models\MensagemWhatsappModel;
use App\Models\WhatsappMensagemModel;
use App\Models\WhatsappEnvioModel;
use App\Models\OsDocumentoModel;
use App\Services\OsStatusFlowService;
use App\Services\WhatsAppService;
use App\Services\OsPdfService;
use App\Services\CrmService;
use App\Services\CentralMensagensService;

class Os extends BaseController
{
    protected $model;

    public function __construct()
    {
        $this->model = new OsModel();
        requirePermission('os');
    }

    public function index()
    {
        $status = trim((string) $this->request->getGet('status'));
        $macrofase = trim((string) $this->request->getGet('macrofase'));
        $estadoFluxo = trim((string) $this->request->getGet('estado_fluxo'));

        $statusFlowService = new OsStatusFlowService();
        $statusGrouped = $statusFlowService->getStatusGrouped();

        $macrofases = [];
        foreach (array_keys($statusGrouped) as $macro) {
            $macrofases[$macro] = ucwords(str_replace('_', ' ', (string) $macro));
        }

        $data = [
            'title'  => 'Ordens de Servico',
            'filtro_status' => $status,
            'filtro_macrofase' => $macrofase,
            'filtro_estado_fluxo' => $estadoFluxo,
            'statusGrouped' => $statusGrouped,
            'macrofases' => $macrofases,
        ];
        return view('os/index', $data);
    }

    public function datatable()
    {
        $status = trim((string) $this->request->getPost('status'));
        $macrofase = trim((string) $this->request->getPost('macrofase'));
        $estadoFluxo = trim((string) $this->request->getPost('estado_fluxo'));
        $db = \Config\Database::connect();
        $hasStatusTable = $db->tableExists('os_status');
        
        $builder = $this->model->select(
                'os.*,
                clientes.nãome_razao as cliente_nãome,
                em.nãome as equip_marca, emod.nãome as equip_modelo,
                funcionarios.nãome as tecnico_nãome' . ($hasStatusTable ? ',
                os_status.nãome as status_nãome,
                os_status.grupo_macro as status_grupo_macro' : '')
            )
            ->join('clientes', 'clientes.id = os.cliente_id')
            ->join('equipamentos', 'equipamentos.id = os.equipamento_id')
            ->join('equipamentos_marcas em', 'em.id = equipamentos.marca_id', 'left')
            ->join('equipamentos_modelos emod', 'emod.id = equipamentos.modelo_id', 'left')
            ->join('funcionarios', 'funcionarios.id = os.tecnico_id', 'left');

        if ($hasStatusTable) {
            $builder->join('os_status', 'os_status.codigo = os.status', 'left');
        }
                    
        if ($status && $status !== 'todos') {
            $builder->where('os.status', $status);
        }
        if ($macrofase && $hasStatusTable) {
            $builder->where('os_status.grupo_macro', $macrofase);
        }
        if ($estadoFluxo) {
            $builder->where('os.estado_fluxo', $estadoFluxo);
        }

        $columns = [
            'os.numero_os', 'clientes.nãome_razao', 'em.nãome', 
            'os.relato_cliente', 'os.data_abertura', 'os.status', 'os.valor_final', 'os.estado_fluxo'
        ];
        
        $searchable = ['os.numero_os', 'clientes.nãome_razao', 'em.nãome', 'emod.nãome'];

        return $this->respondDatatable($builder, $columns, $searchable, function ($row) {
            
            $statusBadge = getStatusBadge((string) ($row['status'] ?? ''));

            $valorFormatado = ($row['valor_final'] ?? 0) > 0
                ? 'R$ ' . number_format($row['valor_final'], 2, ',', '.')
                : '-';
            $dataAbertura = date('d/m/Y', strtotime($row['data_abertura']));
            $equipamento  = trim(($row['equip_marca'] ?? '') . ' ' . ($row['equip_modelo'] ?? '')) ?: '-';
            $fluxo = trim((string) ($row['estado_fluxo'] ?? ''));
            $fluxoBadge = $fluxo !== ''
                ? '<span class="badge bg-light text-dark border">' . esc(ucwords(str_replace('_', ' ', $fluxo))) . '</span>'
                : '<span class="text-muted">-</span>';

            $acoes = '<div class="btn-group btn-group-sm">
                        <a href="'.base_url('os/visualizar/'.$row['id']).'" class="btn btn-outline-info" title="Visualizar"><i class="bi bi-eye"></i></a>';
            if (can('os', 'editar')) {
                $acoes .= '<a href="'.base_url('os/editar/'.$row['id']).'" class="btn btn-outline-primary" title="Editar"><i class="bi bi-pencil"></i></a>';
            }
            if (can('os', 'encerrar')) {
                $acoes .= '<a href="javascript:void(0)" class="btn btn-outline-warning" title="Encerrar" onclick="confirmarEncerramento(\'os\', '.$row['id'].')"><i class="bi bi-archive"></i></a>';
            }
            $acoes .= '</div>';

            return [
                '<strong>#' . esc($row['numero_os']) . '</strong>',
                '<div class="fw-semibold">'.esc($row['cliente_nãome']).'</div>',
                esc($equipamento),
                '<span class="text-truncate d-inline-block" style="max-width: 150px;">'.esc($row['relato_cliente']).'</span>',
                $dataAbertura,
                $statusBadge . '<div class="mt-1">' . $fluxoBadge . '</div>',
                $valorFormatado,
                $acoes
            ];
        });
    }

    public function create()
    {
        $clienteModel    = new ClienteModel();
        $funcionarioModel = new FuncionarioModel();
        $tipoModel       = new \App\Models\EquipamentoTipoModel();
        $marcaModel      = new \App\Models\EquipamentoMarcaModel();
        $defeitoRelatadoModel = new DefeitoRelatadoModel();

        $origemConversaId = (int) ($this->request->getGet('origem_conversa_id') ?? 0);
        $origemContatoId = (int) ($this->request->getGet('origem_contato_id') ?? 0);
        $clientePreSelecionado = (int) ($this->request->getGet('cliente_id') ?? 0);
        $nãomeHint = trim((string) ($this->request->getGet('nãome_hint') ?? ''));
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
                    if ($nãomeHint === '') {
                        $nãomeConversa = trim((string) ($origemConversa['nãome_contato'] ?? ''));
                        if ($nãomeConversa !== '' && !$this->isLikelyPhoneValue($nãomeConversa)) {
                            $nãomeHint = $nãomeConversa;
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
                    if ($nãomeHint === '') {
                        $nãomeHint = trim((string) ($origemContato['nãome'] ?? $origemContato['whatsapp_nãome_perfil'] ?? ''));
                    }
                    if ($telefoneHint === '') {
                        $telefoneHint = preg_replace('/\D+/', '', (string) ($origemContato['telefone_nãormalizado'] ?? $origemContato['telefone'] ?? '')) ?? '';
                    }
                }
            }
        }

        $data = [
            'title'    => 'Nãova Ordem de Servico',
            'clientes' => $clienteModel->orderBy('nãome_razao', 'ASC')->findAll(),
            'tecnicos' => $funcionarioModel->getTecnicos(),
            'tipos'    => $tipoModel->orderBy('nãome', 'ASC')->findAll(),
            'marcas'   => $marcaModel->orderBy('nãome', 'ASC')->findAll(),
            'relatosRapidos' => $defeitoRelatadoModel->getActiveGrouped(),
            'statusGrouped' => (new OsStatusFlowService())->getStatusGrouped(),
            'statusDefault' => 'triagem',
            'origemConversaId' => $origemConversaId > 0 ? $origemConversaId : null,
            'origemContatoId' => $origemContatoId > 0 ? $origemContatoId : null,
            'origemConversa' => $origemConversa,
            'origemContato' => $origemContato ?? null,
            'clientePreSelecionado' => $clientePreSelecionado > 0 ? $clientePreSelecionado : null,
            'origemNãomeHint' => $nãomeHint,
            'origemTelefoneHint' => $telefoneHint,
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
        $nãovoStatus = strtolower(trim((string) ($dados['status'] ?? 'triagem')));
        $dados['numero_os']    = $this->model->generateNumeroOs();
        $dados['data_abertura'] = date('Y-m-d H:i:s');
        $dados['status'] = $nãovoStatus;
        $dados['estado_fluxo'] = $statusFlowService->resãolveEstadoFluxo($nãovoStatus);
        $dados['status_atualizado_em'] = date('Y-m-d H:i:s');

        $this->model->insert($dados);
        $osId = $this->model->getInsertID();

        $historicoModel = new OsStatusHistoricoModel();
        if ($historicoModel->db->tableExists('os_status_historico')) {
            $historicoModel->insert([
                'os_id' => $osId,
                'status_anterior' => null,
                'status_nãovo' => $nãovoStatus,
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
                'Ordem de servico aberta não ERP',
                session()->get('user_id') ?: null,
                ['status' => $nãovoStatus]
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
                        $img->move(FCPATH . 'uploads/os_anãormalidades', $newName);
                        
                        $fotoOsModel->insert([
                            'os_id'    => $osId,
                            'tipo'     => 'recepcao',
                            'arquivo'  => $newName,
                        ]);
                    }
                }
            }
        }

        $this->persistAccessãoryData($osId, $dados['numero_os']);
        $this->persistEstadoFisicoData($osId, $dados['numero_os']);
        $this->triggerAutomaticEventsOnStatus($osId, $nãovoStatus, session()->get('user_id') ?: null);
        $this->sincronizarOrigemWhatsappNaAbertura(
            $osId,
            (int) ($dados['cliente_id'] ?? 0),
            $origemConversaId,
            $origemContatoId
        );

        LogModel::registrar('os_criada', 'OS criada: ' . $dados['numero_os']);

        return redirect()->to('/os/visualizar/' . $osId)
            ->with('success', 'Ordem de Serviço ' . $dados['numero_os'] . ' criada com sucessão!');
    }

    public function show($id)
    {
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

        $acessãorioModel = new AcessãorioOsModel();
        $fotoAcessãorioModel = new FotoAcessãorioModel();
        $acessãoriosFolder = 'uploads/acessãorios/OS_' . $this->nãormalizeOsSlug($os['numero_os']) . '/';
        $acessãorios = $acessãorioModel->where('os_id', $id)->orderBy('id', 'ASC')->findAll();
        foreach ($acessãorios as &$acessãorio) {
            $fotos = $fotoAcessãorioModel->where('acessãorio_id', $acessãorio['id'])->findAll();
            foreach ($fotos as &$foto) {
                $fotoPath = FCPATH . $acessãoriosFolder . $foto['arquivo'];
                if (!file_exists($fotoPath)) {
                    $foto = null;
                    continue;
                }
                $foto['url'] = base_url($acessãoriosFolder . $foto['arquivo']);
            }
            $acessãorio['fotos'] = array_values(array_filter($fotos));
        }

        $estadoFisicoModel = new EstadoFisicoOsModel();
        $fotoEstadoFisicoModel = new FotoEstadoFisicoModel();
        $estadoFisicoFolder = 'uploads/estado_fisico/OS_' . $this->nãormalizeOsSlug($os['numero_os']) . '/';
        $estadosFisicos = $estadoFisicoModel->where('os_id', $id)->orderBy('id', 'ASC')->findAll();
        foreach ($estadosFisicos as &$estadoItem) {
            $fotosEstado = $fotoEstadoFisicoModel->where('estado_fisico_id', $estadoItem['id'])->findAll();
            foreach ($fotosEstado as &$fotoEstado) {
                $fotoPath = FCPATH . $estadoFisicoFolder . $fotoEstado['arquivo'];
                if (!file_exists($fotoPath)) {
                    $fotoEstado = null;
                    continue;
                }
                $fotoEstado['url'] = base_url($estadoFisicoFolder . $fotoEstado['arquivo']);
            }
            $estadoItem['fotos'] = array_values(array_filter($fotosEstado));
        }

        // Fotos do Equipamento e da OS
        $fotoEquipModel = new EquipamentoFotoModel();
        $fotoOsModel = new OsFotoModel();

        $fotos_equip = $fotoEquipModel->where('equipamento_id', $os['equipamento_id'])->findAll();
        foreach ($fotos_equip as &$f) {
            $pathPerfil = FCPATH . 'uploads/equipamentos_perfil/' . $f['arquivo'];
            $f['url'] = file_exists($pathPerfil) 
                ? base_url('uploads/equipamentos_perfil/' . $f['arquivo']) 
                : base_url('uploads/equipamentos/' . basename((string) $f['arquivo']));
        }

        $fotos_entrada = $fotoOsModel->where('os_id', $id)->where('tipo', 'recepcao')->findAll();
        foreach ($fotos_entrada as &$f) {
            $pathAnãormal = FCPATH . 'uploads/os_anãormalidades/' . $f['arquivo'];
            $f['url'] = file_exists($pathAnãormal) 
                ? base_url('uploads/os_anãormalidades/' . $f['arquivo']) 
                : base_url('uploads/os/' . $f['arquivo']);
        }

        $data = [
            'title'          => 'OS ' . $os['numero_os'],
            'os'             => $os,
            'itens'          => $itemModel->getByOs($id),
            'defeitos'       => $defeitos,
            'fotos_equip'    => $fotos_equip,
            'fotos_entrada'  => $fotos_entrada,
            'acessãorios'     => $acessãorios,
            'acessãorios_folder' => $acessãoriosFolder,
            'estados_fisicos' => $estadosFisicos,
            'estado_fisico_folder' => $estadoFisicoFolder,
            'statusGrouped' => (new OsStatusFlowService())->getStatusGrouped(),
            'statusOptions' => (new OsStatusFlowService())->buildTransitionHints((string) ($os['status'] ?? '')),
            'statusHistorico' => ((new OsStatusHistoricoModel())->db->tableExists('os_status_historico'))
                ? (new OsStatusHistoricoModel())->byOs((int) $id)
                : [],
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
        ];
        return view('os/show', $data);
    }

    public function edit($id)
    {
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
            $pathAnãormal = FCPATH . 'uploads/os_anãormalidades/' . $f['arquivo'];
            $f['url'] = file_exists($pathAnãormal) 
                ? base_url('uploads/os_anãormalidades/' . $f['arquivo']) 
                : base_url('uploads/os/' . $f['arquivo']);
        }

        $estadoFisicoEntries = (new EstadoFisicoOsModel())->where('os_id', $id)->orderBy('id', 'ASC')->findAll();

        $data = [
            'title'        => 'Editar OS ' . $os['numero_os'],
            'os'           => $os,
            'clientes'     => $clienteModel->orderBy('nãome_razao', 'ASC')->findAll(),
            'equipamentos' => $equipamentoModel->getByCliente($os['cliente_id']),
            'tecnicos'     => $funcionarioModel->getTecnicos(),
            'itens'        => $itemModel->getByOs($id),
            'defeitosSelected' => (new DefeitoModel())->getByOs($id),
            'fotos_entrada'    => $fotos_entrada,
            'relatosRapidos'   => $defeitoRelatadoModel->getActiveGrouped(),
            'estadoFisicoEntries' => $estadoFisicoEntries,
            'statusGrouped' => (new OsStatusFlowService())->getStatusGrouped(),
            'statusDefault' => (string) ($os['status'] ?? 'triagem'),
        ];
        return view('os/form', $data);
    }

    public function update($id)
    {
        $dados = $this->request->getPost();
        $osAnterior = $this->model->find($id);
        $statusNãovo = strtolower(trim((string) ($dados['status'] ?? '')));
        $statusAlterado = $statusNãovo !== '' && !empty($osAnterior) && $statusNãovo !== (string) ($osAnterior['status'] ?? '');
        $statusService = new OsStatusFlowService();
        if ($statusAlterado && !$statusService->isTransitionAllowed((string) ($osAnterior['status'] ?? ''), $statusNãovo)) {
            return redirect()->to('/os/editar/' . $id)
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
                $statusNãovo,
                session()->get('user_id') ?: null,
                'Alterado na edicao da OS'
            );
            $this->triggerAutomaticEventsOnStatus((int) $id, $statusNãovo, session()->get('user_id') ?: null);
        }
        
        // Salva nãovas fotos de estado do equipamento
        if ($files = $this->request->getFiles()) {
            if (!empty($files['fotos_entrada'])) {
                $fotoOsModel = new \App\Models\OsFotoModel();
                $osObj = $this->model->find($id);
                $slug = strtolower(url_title($osObj['numero_os'], '_', true));

                foreach ($files['fotos_entrada'] as $index => $img) {
                    if ($img && $img->isValid() && !$img->hasMoved()) {
                        $ext = $img->getExtension();
                        $newName = $slug . '_edit_' . ($index + 1) . '_' . time() . '.' . $ext;
                        $img->move(FCPATH . 'uploads/os_anãormalidades', $newName);
                        
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
            $this->persistAccessãoryData($id, $osRecord['numero_os'], true);
            $this->persistEstadoFisicoData($id, $osRecord['numero_os'], true);
        }

        LogModel::registrar('os_atualizada', 'OS atualizada ID: ' . $id);

        return redirect()->to('/os/visualizar/' . $id)
            ->with('success', 'OS atualizada com sucessão!');
    }

    public function updateStatus($id)
    {
        $status = strtolower(trim((string) $this->request->getPost('status')));
        $observacao = trim((string) $this->request->getPost('observacao_status'));
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
            return redirect()->to('/os/visualizar/' . $id)
                ->with('error', $result['message'] ?? 'Nao foi possivel atualizar o status.');
        }

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
                        'descricao'       => 'OS ' . ($osAtualizada['numero_os'] ?? $os['numero_os']),
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

        $this->triggerAutomaticEventsOnStatus((int) $id, $status, session()->get('user_id') ?: null);

        LogModel::registrar('os_status', 'Status da OS ' . $os['numero_os'] . ' alterado para: ' . $status);

        return redirect()->to('/os/visualizar/' . $id)
            ->with('success', 'Status atualizado com sucessão!');
    }

    public function sendWhatsApp($id)
    {
        $os = $this->model->getComplete((int) $id);
        if (!$os) {
            return redirect()->to('/os')->with('error', 'OS nao encontrada.');
        }

        $telefone = trim((string) ($this->request->getPost('telefone') ?: ($os['cliente_telefone'] ?? '')));
        if ($telefone === '') {
            return redirect()->to('/os/visualizar/' . $id)->with('error', 'Cliente sem telefone para envio.');
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
                return redirect()->to('/os/visualizar/' . $id)->with('error', 'Selecione um template ou informe uma mensagem manual.');
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
            return redirect()->to('/os/visualizar/' . $id)->with('success', 'Mensagem WhatsApp enviada com sucessão.');
        }

        return redirect()->to('/os/visualizar/' . $id)->with('error', $result['message'] ?? 'Falha ao enviar mensagem não WhatsApp.');
    }

    public function generatePdf($id)
    {
        $os = $this->model->find((int) $id);
        if (!$os) {
            return redirect()->to('/os')->with('error', 'OS nao encontrada.');
        }

        $tipo = trim((string) $this->request->getPost('tipo_documento'));
        if ($tipo === '') {
            return redirect()->to('/os/visualizar/' . $id)->with('error', 'Tipo de documento nao informado.');
        }

        $pdfService = new OsPdfService();
        $result = $pdfService->gerar((int) $id, $tipo, session()->get('user_id') ?: null);
        if (empty($result['ok'])) {
            return redirect()->to('/os/visualizar/' . $id)->with('error', $result['message'] ?? 'Falha ao gerar PDF.');
        }

        return redirect()->to('/os/visualizar/' . $id)->with('success', 'PDF gerado com sucessão.');
    }

    private function triggerAutomaticEventsOnStatus(int $osId, string $statusCode, ?int $userId = null): void
    {
        $statusCode = strtolower(trim($statusCode));
        try {
            (new CrmService())->applyStatusAutomation($osId, $statusCode, $userId);
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
        $nãonDigits = preg_replace('/[0-9+\-().\s]/', '', $raw) ?? '';
        return strlen($digits) >= 8 && strlen($nãonDigits) <= 2;
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

        return redirect()->to('/os/visualizar/' . $dados['os_id'])
            ->with('success', 'Item adicionado com sucessão!');
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

            return redirect()->to('/os/visualizar/' . $osId)
                ->with('success', 'Item removido com sucessão!');
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

    private function persistAccessãoryData(int $osId, string $numeroOs, bool $replaceExisting = false): void
    {
        $entries = $this->getAccessãoryEntries();
        $filesMap = $this->collectAccessãoryFiles();

        if ($replaceExisting) {
            (new AcessãorioOsModel())->deleteByOs($osId);
            $this->clearAccessãoryFolder($numeroOs);
        }

        if (empty($entries) && empty($filesMap)) {
            return;
        }

        $acessãorioModel = new AcessãorioOsModel();
        $fotoModel = new FotoAcessãorioModel();
        $slug = $this->nãormalizeOsSlug($numeroOs);
        $folder = $this->ensureAccessãoryDirectory($slug);
        $sequence = 1;

        foreach ($entries as $entry) {
            $description = trim($entry['text'] ?? '');
            if ($description === '') {
                continue;
            }

            $acessãorioModel->insert([
                'os_id' => $osId,
                'descricao' => $description,
                'tipo' => $entry['key'] ?? null,
                'valores' => !empty($entry['values']) ? jsãon_encode($entry['values'], JSON_UNESCAPED_UNICODE) : null,
            ]);

            $acessãorioId = $acessãorioModel->getInsertID();
            if (!$acessãorioId) {
                continue;
            }

            $entryFiles = $filesMap[$entry['id']] ?? [];
            foreach ($entryFiles as $file) {
                $this->saveAccessãoryPhoto($file, $folder, $slug, $sequence, $acessãorioId, $fotoModel);
            }
        }
    }

    private function getAccessãoryEntries(): array
    {
        $raw = $this->request->getPost('acessãorios_data');
        if (empty($raw)) {
            return [];
        }

        $decoded = jsãon_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        return array_values(array_filter($decoded, function ($entry) {
            return !empty(trim($entry['text'] ?? ''));
        }));
    }

    private function collectAccessãoryFiles(): array
    {
        $mapped = [];
        if (empty($_FILES['fotos_acessãorios']['name'] ?? null)) {
            return $mapped;
        }

        foreach ($_FILES['fotos_acessãorios']['name'] as $entryId => $files) {
            foreach ($files as $index => $name) {
                $error = $_FILES['fotos_acessãorios']['error'][$entryId][$index] ?? UPLOAD_ERR_NO_FILE;
                if ($error !== UPLOAD_ERR_OK) {
                    continue;
                }

                $tmpName = $_FILES['fotos_acessãorios']['tmp_name'][$entryId][$index];
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

    private function nãormalizeOsSlug(string $numeroOs): string
    {
        $clean = preg_replace('/[^A-Za-z0-9_]/', '', str_replace('-', '_', $numeroOs));
        $clean = preg_replace('/^OS_?/i', '', $clean);
        return $clean ?: 'os';
    }

    private function ensureAccessãoryDirectory(string $slug): string
    {
        $base = FCPATH . 'uploads/acessãorios/';
        if (!is_dir($base)) {
            mkdir($base, 0755, true);
        }

        $path = $base . 'OS_' . $slug . '/';
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
        return $path;
    }

    private function clearAccessãoryFolder(string $numeroOs): void
    {
        $slug = $this->nãormalizeOsSlug($numeroOs);
        $path = FCPATH . 'uploads/acessãorios/OS_' . $slug . '/';
        if (!is_dir($path)) {
            return;
        }

        foreach (glob($path . '*') as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }

    private function saveAccessãoryPhoto(array $file, string $folder, string $slug, int &$sequence, int $acessãorioId, FotoAcessãorioModel $fotoModel): void
    {
        try {
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $name = "acessãorio_{$slug}_{$sequence}";
            if ($extension) {
                $name .= '.' . $extension;
            }

            $destination = $folder . $name;
            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                throw new \RuntimeException('Falha ao mover upload');
            }

            $fotoModel->insert([
                'acessãorio_id' => $acessãorioId,
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
        $slug = $this->nãormalizeOsSlug($numeroOs);
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
                'descricao_danão' => $description,
                'tipo' => $entry['key'] ?? null,
                'valores' => !empty($entry['values']) ? jsãon_encode($entry['values'], JSON_UNESCAPED_UNICODE) : null,
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

        $decoded = jsãon_decode($raw, true);
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
        $slug = $this->nãormalizeOsSlug($numeroOs);
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
