<?php

namespace App\Controllers;

use App\Models\ChatbotFaqModel;
use App\Models\ChatbotFluxoModel;
use App\Models\ChatbotIntencaoModel;
use App\Models\ChatbotLogModel;
use App\Models\ChatbotRegraErpModel;
use App\Models\ClienteModel;
use App\Models\ContatoModel;
use App\Models\ConfiguracaoModel;
use App\Models\ConversaOsModel;
use App\Models\ConversaWhatsappModel;
use App\Models\CrmTagModel;
use App\Models\CrmFollowupModel;
use App\Models\LogModel;
use App\Models\MensagemWhatsappModel;
use App\Models\OrcamentoModel;
use App\Models\OsDocumentoModel;
use App\Models\OsModel;
use App\Models\RespostaRapidaWhatsappModel;
use App\Models\UsuarioModel;
use App\Services\CentralMensagensService;
use App\Services\MetricasMensageriaService;
use App\Services\WhatsAppService;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

class CentralMensagens extends BaseController
{
    public function __construct()
    {
        requirePermission('clientes', 'visualizar');
    }

    public function index()
    {
        $service = new CentralMensagensService();
        $service->syncInboundQueue(120);

        $conversaModel = new ConversaWhatsappModel();
        if (!$this->isCentralDisponivel($conversaModel)) {
            return redirect()->to('/dashboard')->with('error', 'Modulo Central de Mensagens ainda nao foi migrado.');
        }

        $respostasRapidas = [];
        $respostaModel = new RespostaRapidaWhatsappModel();
        if ($respostaModel->db->tableExists('respostas_rapidas_whatsapp')) {
            $respostasRapidas = $respostaModel->ativas();
        }

        $usuariosAtivos = [];
        $usuarioModel = new UsuarioModel();
        if ($usuarioModel->db->tableExists('usuarios')) {
            $usuariosAtivos = $usuarioModel->select('id, nome')->where('ativo', 1)->orderBy('nome', 'ASC')->findAll();
        }

        $tagsAtivas = [];
        $tagModel = new CrmTagModel();
        if ($tagModel->db->tableExists('crm_tags')) {
            $tagsAtivas = $tagModel->ativas();
        }

        $configModel = new ConfiguracaoModel();
        $autoSyncSeconds = (int) $configModel->get('central_mensagens_auto_sync_interval', '15');
        if ($autoSyncSeconds < 5) {
            $autoSyncSeconds = 5;
        }
        if ($autoSyncSeconds > 120) {
            $autoSyncSeconds = 120;
        }
        $slaPrimeiraRespostaMin = (int) $configModel->get('central_mensagens_sla_primeira_resposta_min', '60');
        if ($slaPrimeiraRespostaMin < 1) {
            $slaPrimeiraRespostaMin = 60;
        }
        $enableSse = (string) $configModel->get('central_mensagens_sse_enabled', '0') === '1';

        $gatewayAccountNumber = trim((string) get_config('whatsapp_gateway_account_number', ''));
        if ($gatewayAccountNumber === '') {
            $gatewayAccountNumber = trim((string) get_config('whatsapp_test_phone', ''));
        }

        return view('central_mensagens/index', [
            'title' => 'Central de Mensagens',
            'respostasRapidas' => $respostasRapidas,
            'statusConversaOptions' => ['aberta', 'aguardando', 'resolvida', 'arquivada'],
            'usuariosAtivos' => $usuariosAtivos,
            'tagsAtivas' => $tagsAtivas,
            'autoSyncSeconds' => $autoSyncSeconds,
            'slaPrimeiraRespostaMin' => $slaPrimeiraRespostaMin,
            'enableSse' => $enableSse,
            'gatewayAccountNumber' => preg_replace('/\D+/', '', $gatewayAccountNumber) ?: '',
            'canCreateContato' => function_exists('can')
                ? (can('clientes', 'criar') || can('clientes', 'editar'))
                : true,
            'currentUserId' => (int) (session()->get('user_id') ?? 0),
            'currentUserName' => (string) (session()->get('user_nome') ?? ''),
            'cmActive' => 'conversas',
        ]);
    }

    public function chatbot()
    {
        $this->syncInboundSafe();

        $intencaoModel = new ChatbotIntencaoModel();
        if (!$intencaoModel->db->tableExists('chatbot_intencoes')) {
            return redirect()->to('/atendimento-whatsapp')->with('error', 'Estrutura do chatbot ainda nao foi migrada.');
        }

        $intencoes = $intencaoModel->orderBy('ordem', 'ASC')->findAll();
        $regras = [];
        $regraModel = new ChatbotRegraErpModel();
        if ($regraModel->db->tableExists('chatbot_regras_erp')) {
            $regras = $regraModel->orderBy('evento_origem', 'ASC')->orderBy('id', 'ASC')->findAll();
        }

        $logs = [];
        $logModel = new ChatbotLogModel();
        if ($logModel->db->tableExists('chatbot_logs')) {
            $logs = $logModel
                ->select('chatbot_logs.*, conversas_whatsapp.telefone, clientes.nome_razao as cliente_nome')
                ->join('conversas_whatsapp', 'conversas_whatsapp.id = chatbot_logs.conversa_id', 'left')
                ->join('clientes', 'clientes.id = chatbot_logs.cliente_id', 'left')
                ->orderBy('chatbot_logs.id', 'DESC')
                ->findAll(200);
        }

        return view('central_mensagens/chatbot', [
            'title' => 'Central de Mensagens - Chatbot e Automacao',
            'cmActive' => 'chatbot',
            'intencoes' => $intencoes,
            'regras' => $regras,
            'logs' => $logs,
        ]);
    }

    public function faq()
    {
        $model = new ChatbotFaqModel();
        if (!$model->db->tableExists('chatbot_faq')) {
            return redirect()->to('/atendimento-whatsapp')->with('error', 'Estrutura do FAQ do chatbot ainda nao foi migrada.');
        }

        $faqs = $model->orderBy('categoria', 'ASC')->orderBy('ordem', 'ASC')->findAll();
        return view('central_mensagens/faq', [
            'title' => 'Central de Mensagens - FAQ',
            'cmActive' => 'faq',
            'faqs' => $faqs,
        ]);
    }

    public function respostasRapidas()
    {
        $model = new RespostaRapidaWhatsappModel();
        if (!$model->db->tableExists('respostas_rapidas_whatsapp')) {
            return redirect()->to('/atendimento-whatsapp')->with('error', 'Estrutura de respostas rapidas ainda nao foi migrada.');
        }

        $respostas = $model->orderBy('categoria', 'ASC')->orderBy('ordem', 'ASC')->findAll();
        return view('central_mensagens/respostas_rapidas', [
            'title' => 'Central de Mensagens - Respostas Rapidas',
            'cmActive' => 'respostas',
            'respostas' => $respostas,
        ]);
    }

    public function fluxos()
    {
        $model = new ChatbotFluxoModel();
        if (!$model->db->tableExists('chatbot_fluxos')) {
            return redirect()->to('/atendimento-whatsapp')->with('error', 'Estrutura de fluxos de atendimento ainda nao foi migrada.');
        }

        $fluxos = $model->orderBy('tipo_fluxo', 'ASC')->orderBy('ordem', 'ASC')->findAll();
        return view('central_mensagens/fluxos', [
            'title' => 'Central de Mensagens - Fluxos de Atendimento',
            'cmActive' => 'fluxos',
            'fluxos' => $fluxos,
        ]);
    }

    public function filas()
    {
        $this->syncInboundSafe();

        $status = trim((string) $this->request->getGet('status'));
        $prioridade = trim((string) $this->request->getGet('prioridade'));
        $responsavelId = (int) ($this->request->getGet('responsavel_id') ?? 0);
        $aguardandoHumano = (string) $this->request->getGet('aguardando_humano') === '1';

        $conversaModel = new ConversaWhatsappModel();
        if (!$this->isCentralDisponivel($conversaModel)) {
            return redirect()->to('/atendimento-whatsapp')->with('error', 'Estrutura de conversas nao encontrada.');
        }

        $builder = $conversaModel
            ->select('conversas_whatsapp.*, clientes.nome_razao as cliente_nome, os.numero_os, usuarios.nome as responsavel_nome')
            ->join('clientes', 'clientes.id = conversas_whatsapp.cliente_id', 'left')
            ->join('os', 'os.id = conversas_whatsapp.os_id_principal', 'left')
            ->join('usuarios', 'usuarios.id = conversas_whatsapp.responsavel_id', 'left');

        if ($status !== '') {
            $builder->where('conversas_whatsapp.status', $status);
        }
        if ($prioridade !== '') {
            $builder->where('conversas_whatsapp.prioridade', $prioridade);
        }
        if ($responsavelId > 0) {
            $builder->where('conversas_whatsapp.responsavel_id', $responsavelId);
        }
        if ($aguardandoHumano) {
            $builder->where('conversas_whatsapp.aguardando_humano', 1);
        }

        $conversas = $builder
            ->orderBy('conversas_whatsapp.prioridade', 'DESC')
            ->orderBy('conversas_whatsapp.ultima_mensagem_em', 'DESC')
            ->findAll(400);

        $usuariosAtivos = (new UsuarioModel())
            ->select('id, nome')
            ->where('ativo', 1)
            ->orderBy('nome', 'ASC')
            ->findAll();

        return view('central_mensagens/filas', [
            'title' => 'Central de Mensagens - Filas e Responsaveis',
            'cmActive' => 'filas',
            'conversas' => $conversas,
            'usuariosAtivos' => $usuariosAtivos,
            'filtro_status' => $status,
            'filtro_prioridade' => $prioridade,
            'filtro_responsavel_id' => $responsavelId,
            'filtro_aguardando_humano' => $aguardandoHumano,
        ]);
    }

    public function metricas()
    {
        $inicio = trim((string) $this->request->getGet('inicio'));
        $fim = trim((string) $this->request->getGet('fim'));
        if ($inicio === '') {
            $inicio = date('Y-m-d', strtotime('-7 days'));
        }
        if ($fim === '') {
            $fim = date('Y-m-d');
        }
        if ($fim < $inicio) {
            $tmp = $inicio;
            $inicio = $fim;
            $fim = $tmp;
        }

        $resumo = (new MetricasMensageriaService())->gerarResumo($inicio, $fim);
        $raw = $resumo['cards'] ?? [];

        // Enriquecer os cards com metadados para KPIs profissionais
        $resumo['kpis'] = [
            'recebidas' => [
                'titulo'   => 'Mensagens Recebidas',
                'valor'    => (int) ($raw['mensagens_recebidas'] ?? 0),
                'subtexto' => 'Demanda total inbound no período',
                'status'   => 'info',
                'icone'    => 'bi-chat-left-text',
                'tooltip'  => 'Quantidade total de mensagens enviadas pelos clientes para a central.'
            ],
            'enviadas' => [
                'titulo'   => 'Mensagens Enviadas',
                'valor'    => (int) ($raw['mensagens_enviadas'] ?? 0),
                'subtexto' => 'Resposta total da equipe/bot',
                'status'   => 'success',
                'icone'    => 'bi-send',
                'tooltip'  => 'Total de mensagens saindo da central (Bot + Atendentes).'
            ],
            'automaticas' => [
                'titulo'   => 'Respostas Automáticas',
                'valor'    => (int) ($raw['mensagens_automaticas'] ?? 0),
                'subtexto' => 'Interações resolvidas pelo Chatbot',
                'status'   => 'primary',
                'icone'    => 'bi-robot',
                'tooltip'  => 'Mensagens disparadas automaticamente pelo sistema de automação.'
            ],
            'aguardando' => [
                'titulo'   => 'Aguardando Atendimento',
                'valor'    => (int) ($raw['conversas_aguardando_humano'] ?? 0),
                'subtexto' => 'Clientes na fila de espera',
                'status'   => ($raw['conversas_aguardando_humano'] ?? 0) > 5 ? 'danger' : 'warning',
                'icone'    => 'bi-people',
                'tooltip'  => 'Conversas que solicitaram transbordo humano e ainda não foram atendidas.'
            ],
            'taxa_automacao' => [
                'titulo'   => 'Taxa de Automação',
                'valor'    => number_format((float) ($raw['taxa_automacao'] ?? 0), 1, ',', '.') . '%',
                'subtexto' => 'Eficiência do Chatbot',
                'status'   => ($raw['taxa_automacao'] ?? 0) > 70 ? 'success' : 'info',
                'icone'    => 'bi-cpu',
                'tooltip'  => 'Percentual de mensagens tratadas automaticamente em relação ao volume total.'
            ],
            'sla' => [
                'titulo'   => 'SLA Estourado',
                'valor'    => (int) ($raw['sla_estourado'] ?? 0),
                'subtexto' => 'Contatos fora do tempo limite',
                'status'   => ($raw['sla_estourado'] ?? 0) > 0 ? 'danger' : 'success',
                'icone'    => 'bi-clock-history',
                'tooltip'  => 'Número de conversas ativas que ultrapassaram o tempo limite de resposta definida.'
            ]
        ];

        return view('central_mensagens/metricas', [
            'title' => 'Central de Mensagens - Métricas',
            'cmActive' => 'metricas',
            'inicio' => $inicio,
            'fim' => $fim,
            'resumo' => $resumo,
        ]);
    }

    public function configuracoes()
    {
        $configModel = new ConfiguracaoModel();
        $keys = $this->centralConfigKeys();
        $values = [];
        foreach ($keys as $key => $default) {
            $values[$key] = $configModel->get($key, $default);
        }

        return view('central_mensagens/configuracoes', [
            'title' => 'Central de Mensagens - Configuracoes',
            'cmActive' => 'configuracoes',
            'config' => $values,
        ]);
    }

    public function salvarIntencao()
    {
        $model = new ChatbotIntencaoModel();
        if (!$model->db->tableExists('chatbot_intencoes')) {
            return $this->respondAction(false, 'Tabela de intencoes nao encontrada.', '/atendimento-whatsapp/chatbot');
        }

        $id = (int) ($this->request->getPost('id') ?? 0);
        $codigo = strtolower(trim((string) $this->request->getPost('codigo')));
        $nome = trim((string) $this->request->getPost('nome'));
        $gatilhos = $this->parseListInput((string) $this->request->getPost('gatilhos'));

        if ($codigo === '' || $nome === '') {
            return $this->respondAction(false, 'Codigo e nome da intencao sao obrigatorios.', '/atendimento-whatsapp/chatbot');
        }

        $payload = [
            'codigo' => $codigo,
            'nome' => $nome,
            'descricao' => trim((string) $this->request->getPost('descricao')) ?: null,
            'gatilhos_json' => !empty($gatilhos) ? json_encode($gatilhos, JSON_UNESCAPED_UNICODE) : null,
            'resposta_padrao' => trim((string) $this->request->getPost('resposta_padrao')) ?: null,
            'exige_consulta_erp' => (int) ($this->request->getPost('exige_consulta_erp') ? 1 : 0),
            'acao_sistema' => trim((string) $this->request->getPost('acao_sistema')) ?: null,
            'ordem' => (int) ($this->request->getPost('ordem') ?? 0),
            'ativo' => (int) ($this->request->getPost('ativo') ? 1 : 0),
        ];

        if ($id > 0) {
            $model->update($id, $payload);
            return $this->respondAction(true, 'Intencao atualizada com sucesso.', '/atendimento-whatsapp/chatbot');
        }

        $exists = $model->where('codigo', $codigo)->first();
        if ($exists) {
            return $this->respondAction(false, 'Ja existe uma intencao com este codigo.', '/atendimento-whatsapp/chatbot');
        }

        $model->insert($payload);
        return $this->respondAction(true, 'Intencao criada com sucesso.', '/atendimento-whatsapp/chatbot');
    }

    public function toggleIntencao(int $id)
    {
        $model = new ChatbotIntencaoModel();
        $row = $model->find($id);
        if (!$row) {
            return $this->respondAction(false, 'Intencao nao encontrada.', '/atendimento-whatsapp/chatbot');
        }

        $model->update($id, ['ativo' => ((int) ($row['ativo'] ?? 0) === 1 ? 0 : 1)]);
        return $this->respondAction(true, 'Status da intencao atualizado.', '/atendimento-whatsapp/chatbot');
    }

    public function deletarIntencao(int $id)
    {
        $model = new ChatbotIntencaoModel();
        if ($model->delete($id)) {
            return $this->respondAction(true, 'Intencao excluida com sucesso.', '/atendimento-whatsapp/chatbot');
        }
        return $this->respondAction(false, 'Nao foi possivel excluir a intencao.', '/atendimento-whatsapp/chatbot');
    }

    public function salvarRegraErp()
    {
        $model = new ChatbotRegraErpModel();
        if (!$model->db->tableExists('chatbot_regras_erp')) {
            return $this->respondAction(false, 'Tabela de regras ERP nao encontrada.', '/atendimento-whatsapp/chatbot');
        }

        $id = (int) ($this->request->getPost('id') ?? 0);
        $nome = trim((string) $this->request->getPost('nome'));
        $eventoOrigem = trim((string) $this->request->getPost('evento_origem'));
        if ($nome === '' || $eventoOrigem === '') {
            return $this->respondAction(false, 'Nome e evento de origem sao obrigatorios.', '/atendimento-whatsapp/chatbot');
        }

        $payload = [
            'nome' => $nome,
            'evento_origem' => $eventoOrigem,
            'condicao_json' => $this->parseJsonInput((string) $this->request->getPost('condicao_json')),
            'acao_json' => $this->parseJsonInput((string) $this->request->getPost('acao_json')),
            'ativo' => (int) ($this->request->getPost('ativo') ? 1 : 0),
        ];

        if ($id > 0) {
            $model->update($id, $payload);
            return $this->respondAction(true, 'Regra ERP atualizada com sucesso.', '/atendimento-whatsapp/chatbot');
        }

        $model->insert($payload);
        return $this->respondAction(true, 'Regra ERP criada com sucesso.', '/atendimento-whatsapp/chatbot');
    }

    public function toggleRegraErp(int $id)
    {
        $model = new ChatbotRegraErpModel();
        $row = $model->find($id);
        if (!$row) {
            return $this->respondAction(false, 'Regra ERP nao encontrada.', '/atendimento-whatsapp/chatbot');
        }

        $model->update($id, ['ativo' => ((int) ($row['ativo'] ?? 0) === 1 ? 0 : 1)]);
        return $this->respondAction(true, 'Status da regra ERP atualizado.', '/atendimento-whatsapp/chatbot');
    }

    public function deletarRegraErp(int $id)
    {
        $model = new ChatbotRegraErpModel();
        if ($model->delete($id)) {
            return $this->respondAction(true, 'Regra ERP excluida com sucesso.', '/atendimento-whatsapp/chatbot');
        }
        return $this->respondAction(false, 'Nao foi possivel excluir a regra ERP.', '/atendimento-whatsapp/chatbot');
    }

    public function salvarFaq()
    {
        $model = new ChatbotFaqModel();
        if (!$model->db->tableExists('chatbot_faq')) {
            return $this->respondAction(false, 'Tabela de FAQ nao encontrada.', '/atendimento-whatsapp/faq');
        }

        $id = (int) ($this->request->getPost('id') ?? 0);
        $pergunta = trim((string) $this->request->getPost('pergunta'));
        $resposta = trim((string) $this->request->getPost('resposta'));
        if ($pergunta === '' || $resposta === '') {
            return $this->respondAction(false, 'Pergunta e resposta sao obrigatorias.', '/atendimento-whatsapp/faq');
        }

        $palavras = $this->parseListInput((string) $this->request->getPost('palavras_chave'));
        $payload = [
            'pergunta' => $pergunta,
            'resposta' => $resposta,
            'categoria' => trim((string) $this->request->getPost('categoria')) ?: null,
            'palavras_chave_json' => !empty($palavras) ? json_encode($palavras, JSON_UNESCAPED_UNICODE) : null,
            'ordem' => (int) ($this->request->getPost('ordem') ?? 0),
            'ativo' => (int) ($this->request->getPost('ativo') ? 1 : 0),
        ];

        if ($id > 0) {
            $model->update($id, $payload);
            return $this->respondAction(true, 'FAQ atualizado com sucesso.', '/atendimento-whatsapp/faq');
        }

        $model->insert($payload);
        return $this->respondAction(true, 'FAQ criado com sucesso.', '/atendimento-whatsapp/faq');
    }

    public function toggleFaq(int $id)
    {
        $model = new ChatbotFaqModel();
        $row = $model->find($id);
        if (!$row) {
            return $this->respondAction(false, 'FAQ nao encontrado.', '/atendimento-whatsapp/faq');
        }

        $model->update($id, ['ativo' => ((int) ($row['ativo'] ?? 0) === 1 ? 0 : 1)]);
        return $this->respondAction(true, 'Status do FAQ atualizado.', '/atendimento-whatsapp/faq');
    }

    public function salvarRespostaRapida()
    {
        $model = new RespostaRapidaWhatsappModel();
        if (!$model->db->tableExists('respostas_rapidas_whatsapp')) {
            return $this->respondAction(false, 'Tabela de respostas rapidas nao encontrada.', '/atendimento-whatsapp/respostas-rapidas');
        }

        $id = (int) ($this->request->getPost('id') ?? 0);
        $titulo = trim((string) $this->request->getPost('titulo'));
        $mensagem = trim((string) $this->request->getPost('mensagem'));
        if ($titulo === '' || $mensagem === '') {
            return $this->respondAction(false, 'Titulo e mensagem sao obrigatorios.', '/atendimento-whatsapp/respostas-rapidas');
        }

        $payload = [
            'titulo' => $titulo,
            'categoria' => trim((string) $this->request->getPost('categoria')) ?: null,
            'mensagem' => $mensagem,
            'ordem' => (int) ($this->request->getPost('ordem') ?? 0),
            'ativo' => (int) ($this->request->getPost('ativo') ? 1 : 0),
        ];

        if ($id > 0) {
            $model->update($id, $payload);
            return $this->respondAction(true, 'Resposta rapida atualizada.', '/atendimento-whatsapp/respostas-rapidas');
        }

        $model->insert($payload);
        return $this->respondAction(true, 'Resposta rapida criada.', '/atendimento-whatsapp/respostas-rapidas');
    }

    public function toggleRespostaRapida(int $id)
    {
        $model = new RespostaRapidaWhatsappModel();
        $row = $model->find($id);
        if (!$row) {
            return $this->respondAction(false, 'Resposta rapida nao encontrada.', '/atendimento-whatsapp/respostas-rapidas');
        }

        $model->update($id, ['ativo' => ((int) ($row['ativo'] ?? 0) === 1 ? 0 : 1)]);
        return $this->respondAction(true, 'Status da resposta rapida atualizado.', '/atendimento-whatsapp/respostas-rapidas');
    }

    public function salvarFluxo()
    {
        $model = new ChatbotFluxoModel();
        if (!$model->db->tableExists('chatbot_fluxos')) {
            return $this->respondAction(false, 'Tabela de fluxos nao encontrada.', '/atendimento-whatsapp/fluxos');
        }

        $id = (int) ($this->request->getPost('id') ?? 0);
        $nome = trim((string) $this->request->getPost('nome'));
        $tipo = trim((string) $this->request->getPost('tipo_fluxo'));
        if ($nome === '' || $tipo === '') {
            return $this->respondAction(false, 'Nome e tipo de fluxo sao obrigatorios.', '/atendimento-whatsapp/fluxos');
        }

        $etapas = $this->parseListInput((string) $this->request->getPost('etapas'));
        $payload = [
            'nome' => $nome,
            'descricao' => trim((string) $this->request->getPost('descricao')) ?: null,
            'tipo_fluxo' => $tipo,
            'etapas_json' => !empty($etapas) ? json_encode($etapas, JSON_UNESCAPED_UNICODE) : null,
            'ordem' => (int) ($this->request->getPost('ordem') ?? 0),
            'ativo' => (int) ($this->request->getPost('ativo') ? 1 : 0),
        ];

        if ($id > 0) {
            $model->update($id, $payload);
            return $this->respondAction(true, 'Fluxo atualizado com sucesso.', '/atendimento-whatsapp/fluxos');
        }

        $model->insert($payload);
        return $this->respondAction(true, 'Fluxo criado com sucesso.', '/atendimento-whatsapp/fluxos');
    }

    public function toggleFluxo(int $id)
    {
        $model = new ChatbotFluxoModel();
        $row = $model->find($id);
        if (!$row) {
            return $this->respondAction(false, 'Fluxo nao encontrado.', '/atendimento-whatsapp/fluxos');
        }

        $model->update($id, ['ativo' => ((int) ($row['ativo'] ?? 0) === 1 ? 0 : 1)]);
        return $this->respondAction(true, 'Status do fluxo atualizado.', '/atendimento-whatsapp/fluxos');
    }

    public function atualizarFila()
    {
        $conversaId = (int) ($this->request->getPost('conversa_id') ?? 0);
        if ($conversaId <= 0) {
            return $this->respondAction(false, 'Conversa obrigatoria para atualizar fila.', '/atendimento-whatsapp/filas');
        }

        $payload = [
            'status' => trim((string) $this->request->getPost('status')),
            'responsavel_id' => (int) ($this->request->getPost('responsavel_id') ?? 0),
            'prioridade' => trim((string) $this->request->getPost('prioridade')),
            'automacao_ativa' => (int) ($this->request->getPost('automacao_ativa') ? 1 : 0),
            'aguardando_humano' => (int) ($this->request->getPost('aguardando_humano') ? 1 : 0),
        ];

        $ok = (new CentralMensagensService())->updateConversationMeta($conversaId, $payload, session()->get('user_id') ?: null);
        if (!$ok) {
            return $this->respondAction(false, 'Nao foi possivel atualizar a fila da conversa.', '/atendimento-whatsapp/filas');
        }

        return $this->respondAction(true, 'Fila da conversa atualizada com sucesso.', '/atendimento-whatsapp/filas');
    }

    public function consolidarMetricasDiarias()
    {
        $dataRef = trim((string) $this->request->getPost('data_referencia'));
        if ($dataRef === '') {
            $dataRef = date('Y-m-d');
        }

        (new MetricasMensageriaService())->atualizarAgregadoDiario($dataRef);
        return $this->respondAction(true, 'Agregado diario de metricas atualizado.', '/atendimento-whatsapp/metricas?inicio=' . $dataRef . '&fim=' . $dataRef);
    }

    public function salvarConfiguracoes()
    {
        $configModel = new ConfiguracaoModel();
        $defaults = $this->centralConfigKeys();
        $post = $this->request->getPost();

        foreach ($defaults as $key => $default) {
            // Se a chave não existe no POST e é um checkbox conhecido, valor é '0'
            if (!isset($post[$key]) && $key === 'central_mensagens_auto_bot_enabled') {
                $value = '0';
            } else {
                $value = $post[$key] ?? $default;
            }

            $configModel->setConfig($key, (string) $value, 'texto');
        }

        return $this->respondAction(true, 'Configuracoes da Central salvas com sucesso.', '/atendimento-whatsapp/configuracoes');
    }

    public function conversas()
    {
        $endpoint = 'conversas';

        try {
            $this->releaseSessionLock();

            $q = trim((string) $this->request->getGet('q'));
            $status = trim((string) $this->request->getGet('status'));
            $somenteNaoLidas = (string) $this->request->getGet('nao_lidas') === '1';
            $osAbertas = (string) $this->request->getGet('com_os_aberta') === '1';
            $clientesNovos = (string) $this->request->getGet('clientes_novos') === '1';
            $responsavelId = (int) ($this->request->getGet('responsavel_id') ?? 0);
            $tagId = (int) ($this->request->getGet('tag_id') ?? 0);
            $limit = min(300, max(20, (int) ($this->request->getGet('limit') ?? 120)));

            $model = new ConversaWhatsappModel();
            $mensagensTableExists = $model->db->tableExists('mensagens_whatsapp');
            $contatosTableExists = $model->db->tableExists('contatos');
            $contatosStatusFieldExists = $contatosTableExists && $model->db->fieldExists('status_relacionamento', 'contatos');
            $contatosSelect = $contatosTableExists
                ? (
                    'contatos.id as contato_id,
                    contatos.nome as contato_nome,
                    contatos.whatsapp_nome_perfil as contato_perfil_nome,
                    contatos.cliente_id as contato_cliente_id,
                    ' . ($contatosStatusFieldExists
                        ? 'contatos.status_relacionamento as contato_status_relacionamento'
                        : 'NULL as contato_status_relacionamento')
                )
                : 'NULL as contato_id,
                    NULL as contato_nome,
                    NULL as contato_perfil_nome,
                    NULL as contato_cliente_id,
                    NULL as contato_status_relacionamento';
            $ultimaMovimentacaoSelect = $mensagensTableExists
                ? '(SELECT COALESCE(mw.recebida_em, mw.enviada_em, mw.created_at) FROM mensagens_whatsapp mw WHERE mw.conversa_id = conversas_whatsapp.id ORDER BY mw.id DESC LIMIT 1) as ultima_movimentacao_em'
                : 'NULL as ultima_movimentacao_em';

            $builder = $model
                ->select(
                    'conversas_whatsapp.*, clientes.nome_razao as cliente_nome, os.numero_os, os.estado_fluxo, usuarios.nome as responsavel_nome,
                    ' . $contatosSelect
                    . ($mensagensTableExists ? ',
                    (SELECT mw.id FROM mensagens_whatsapp mw WHERE mw.conversa_id = conversas_whatsapp.id ORDER BY mw.id DESC LIMIT 1) as ultima_mensagem_id,
                    (SELECT mw.mensagem FROM mensagens_whatsapp mw WHERE mw.conversa_id = conversas_whatsapp.id ORDER BY mw.id DESC LIMIT 1) as ultima_mensagem_texto,
                    (SELECT mw.tipo_conteudo FROM mensagens_whatsapp mw WHERE mw.conversa_id = conversas_whatsapp.id ORDER BY mw.id DESC LIMIT 1) as ultima_mensagem_tipo,
                    (SELECT mw.direcao FROM mensagens_whatsapp mw WHERE mw.conversa_id = conversas_whatsapp.id ORDER BY mw.id DESC LIMIT 1) as ultima_mensagem_direcao,
                    (SELECT mw.tipo_mensagem FROM mensagens_whatsapp mw WHERE mw.conversa_id = conversas_whatsapp.id ORDER BY mw.id DESC LIMIT 1) as ultima_mensagem_tipo_mensagem,
                    (SELECT mw.enviada_por_bot FROM mensagens_whatsapp mw WHERE mw.conversa_id = conversas_whatsapp.id ORDER BY mw.id DESC LIMIT 1) as ultima_mensagem_bot' : ',
                    0 as ultima_mensagem_id,
                    NULL as ultima_mensagem_texto,
                    NULL as ultima_mensagem_tipo,
                    NULL as ultima_mensagem_direcao,
                    NULL as ultima_mensagem_tipo_mensagem,
                    NULL as ultima_mensagem_bot')
                    . ',
                    ' . $ultimaMovimentacaoSelect
                )
                ->join('clientes', 'clientes.id = conversas_whatsapp.cliente_id', 'left')
                ->join('os', 'os.id = conversas_whatsapp.os_id_principal', 'left')
                ->join('usuarios', 'usuarios.id = conversas_whatsapp.responsavel_id', 'left');
            if ($contatosTableExists) {
                $builder->join('contatos', 'contatos.id = conversas_whatsapp.contato_id', 'left');
            }

            if ($q !== '') {
                $builder->groupStart();
                $builder
                    ->like('conversas_whatsapp.telefone', $q)
                    ->orLike('conversas_whatsapp.nome_contato', $q)
                    ->orLike('clientes.nome_razao', $q)
                    ->orLike('os.numero_os', $q);
                if ($contatosTableExists) {
                    $builder
                        ->orLike('contatos.nome', $q)
                        ->orLike('contatos.whatsapp_nome_perfil', $q);
                }
                $builder->groupEnd();
            }
            if ($status !== '') {
                $builder->where('conversas_whatsapp.status', $status);
            }
            if ($somenteNaoLidas) {
                $builder->where('conversas_whatsapp.nao_lidas >', 0);
            }
            if ($osAbertas) {
                $builder->where('os.estado_fluxo IS NOT NULL', null, false)
                    ->whereNotIn('os.estado_fluxo', ['encerrado', 'cancelado']);
            }
            if ($clientesNovos) {
                $builder->where('conversas_whatsapp.cliente_id IS NULL', null, false);
                if ($contatosTableExists) {
                    $builder->where('contatos.cliente_id IS NULL', null, false);
                }
            }
            if ($responsavelId > 0) {
                $builder->where('conversas_whatsapp.responsavel_id', $responsavelId);
            }
            if ($tagId > 0 && $model->db->tableExists('conversa_tags')) {
                $builder->join('conversa_tags', 'conversa_tags.conversa_id = conversas_whatsapp.id', 'inner')
                    ->where('conversa_tags.tag_id', $tagId);
            }

            $items = $builder
                ->orderBy('COALESCE(ultima_movimentacao_em, conversas_whatsapp.ultima_mensagem_em, conversas_whatsapp.updated_at, conversas_whatsapp.created_at)', 'DESC', false)
                ->orderBy('ultima_mensagem_id', 'DESC')
                ->orderBy('conversas_whatsapp.id', 'DESC')
                ->findAll($limit);

            foreach ($items as &$item) {
                $ultimaMovimentacao = trim((string) ($item['ultima_movimentacao_em'] ?? ''));
                if ($ultimaMovimentacao !== '') {
                    $item['ultima_mensagem_em'] = $ultimaMovimentacao;
                    continue;
                }

                if (empty($item['ultima_mensagem_em'])) {
                    $item['ultima_mensagem_em'] = $item['updated_at'] ?? ($item['created_at'] ?? null);
                }
            }
            unset($item);

            return $this->apiSuccess('CM_CONVERSAS_LIST_OK', [
                'items' => $items,
                'count' => count($items),
            ]);
        } catch (Throwable $e) {
            return $this->apiError(
                'CM_CONVERSAS_LIST_ERROR',
                'Nao foi possivel carregar as conversas no momento.',
                500,
                [
                    'endpoint' => $endpoint,
                    'filters' => [
                        'q' => (string) $this->request->getGet('q'),
                        'status' => (string) $this->request->getGet('status'),
                        'nao_lidas' => (string) $this->request->getGet('nao_lidas'),
                        'com_os_aberta' => (string) $this->request->getGet('com_os_aberta'),
                        'clientes_novos' => (string) $this->request->getGet('clientes_novos'),
                        'responsavel_id' => (int) ($this->request->getGet('responsavel_id') ?? 0),
                        'tag_id' => (int) ($this->request->getGet('tag_id') ?? 0),
                        'contatos_table_exists' => (new ConversaWhatsappModel())->db->tableExists('contatos'),
                    ],
                ],
                $e
            );
        }
    }

    public function conversa(int $id)
    {
        $endpoint = 'conversa';

        if (!$this->request->isAJAX()) {
            $conversaModel = new ConversaWhatsappModel();
            if (!$this->isCentralDisponivel($conversaModel)) {
                return redirect()->to('/dashboard')->with('error', 'Modulo Central de Mensagens ainda nao foi migrado.');
            }

            $conversa = $conversaModel->find($id);
            if (!$conversa) {
                return redirect()->to('/atendimento-whatsapp')->with('error', 'Conversa nao encontrada.');
            }

            return redirect()->to(base_url('atendimento-whatsapp') . '?conversa_id=' . $id);
        }

        try {
            $this->releaseSessionLock();
            $service = new CentralMensagensService();

            $conversaModel = new ConversaWhatsappModel();
            $conversa = $conversaModel->find($id);
            if (!$conversa) {
                return $this->apiError(
                    'CM_CONVERSA_NOT_FOUND',
                    'Conversa nao encontrada.',
                    404,
                    [
                        'endpoint' => $endpoint,
                        'conversa_id' => $id,
                    ]
                );
            }

            $unreadBefore = (int) ($conversa['nao_lidas'] ?? 0);
            $service->markConversationRead($id);
            $conversa = $conversaModel->find($id);
            $mensagens = (new MensagemWhatsappModel())->byConversa($id, 500);
            $contexto = $this->buildConversaContext($conversa);

            return $this->apiSuccess('CM_CONVERSA_THREAD_OK', [
                'conversa' => $conversa,
                'unread_before' => $unreadBefore,
                'mensagens' => $mensagens,
                'contexto' => $contexto,
            ]);
        } catch (Throwable $e) {
            return $this->apiError(
                'CM_CONVERSA_THREAD_ERROR',
                'Nao foi possivel carregar a conversa no momento.',
                500,
                [
                    'endpoint' => $endpoint,
                    'conversa_id' => $id,
                ],
                $e
            );
        }
    }

    public function conversaNovas(int $id)
    {
        $endpoint = 'conversa_novas';

        try {
            $this->releaseSessionLock();
            $service = new CentralMensagensService();

            $conversaModel = new ConversaWhatsappModel();
            $conversa = $conversaModel->find($id);
            if (!$conversa) {
                return $this->apiError(
                    'CM_CONVERSA_NOT_FOUND',
                    'Conversa nao encontrada.',
                    404,
                    [
                        'endpoint' => $endpoint,
                        'conversa_id' => $id,
                    ]
                );
            }

            $afterId = max(0, (int) ($this->request->getGet('after_id') ?? 0));
            $limit = min(300, max(20, (int) ($this->request->getGet('limit') ?? 120)));

            $mensagemModel = new MensagemWhatsappModel();
            $mensagens = $mensagemModel->afterId($id, $afterId, $limit);

            if (!empty($mensagens)) {
                $service->markConversationRead($id);
                $conversa = $conversaModel->find($id) ?? $conversa;
            }

            $latestId = $afterId;
            if (!empty($mensagens)) {
                $latestId = (int) end($mensagens)['id'];
            }

            return $this->apiSuccess('CM_CONVERSA_NOVAS_OK', [
                'conversa' => $conversa,
                'latest_id' => $latestId,
                'count' => count($mensagens),
                'mensagens' => $mensagens,
            ]);
        } catch (Throwable $e) {
            return $this->apiError(
                'CM_CONVERSA_NOVAS_ERROR',
                'Nao foi possivel atualizar as mensagens da conversa.',
                500,
                [
                    'endpoint' => $endpoint,
                    'conversa_id' => $id,
                    'after_id' => (int) ($this->request->getGet('after_id') ?? 0),
                ],
                $e
            );
        }
    }

    public function conversaStream(int $id)
    {
        @ini_set('display_errors', '0');
        if (function_exists('session')) {
            try {
                session()->close();
            } catch (\Throwable $e) {
                // segue mesmo sem conseguir fechar sessao explicitamente.
            }
        }

        $service = new CentralMensagensService();
        $conversaModel = new ConversaWhatsappModel();
        $conversa = $conversaModel->find($id);
        if (!$conversa) {
            return $this->apiError(
                'CM_CONVERSA_NOT_FOUND',
                'Conversa nao encontrada.',
                404,
                [
                    'endpoint' => 'conversa_stream',
                    'conversa_id' => $id,
                ]
            );
        }

        $enableSse = (string) get_config('central_mensagens_sse_enabled', '0') === '1';

        if ((string) ($this->request->getGet('probe') ?? '') === '1') {
            return $this->apiSuccess('CM_CONVERSA_STREAM_PROBE_OK', [
                'sse_enabled' => $enableSse,
                'message' => $enableSse
                    ? 'SSE disponivel para esta conversa.'
                    : 'SSE desabilitado por configuracao. Usando polling incremental.',
                'conversa_id' => $id,
            ]);
        }

        if (!$enableSse) {
            return $this->apiError(
                'CM_CONVERSA_STREAM_DISABLED',
                'SSE desabilitado por configuracao.',
                409,
                [
                    'endpoint' => 'conversa_stream',
                    'conversa_id' => $id,
                    'sse_enabled' => false,
                ]
            );
        }

        $afterId = max(0, (int) ($this->request->getGet('after_id') ?? 0));
        $mensagens = [];
        try {
            $mensagemModel = new MensagemWhatsappModel();
            $mensagens = $mensagemModel->afterId($id, $afterId, 120);
        } catch (Throwable $e) {
            $this->observeEndpointFailure(
                'CM_CONVERSA_STREAM_INCREMENTAL_ERROR',
                500,
                'Falha ao carregar stream incremental da conversa.',
                [
                    'endpoint' => 'conversa_stream',
                    'conversa_id' => $id,
                    'after_id' => $afterId,
                ],
                $e
            );
            $errorPayload = json_encode([
                'ok' => false,
                'code' => 'CM_CONVERSA_STREAM_INCREMENTAL_ERROR',
                'status' => 500,
                'conversa_id' => $id,
                'message' => 'Falha ao carregar stream incremental da conversa.',
            ], JSON_UNESCAPED_UNICODE);

            $errorBody = "retry: 5000\n";
            $errorBody .= "event: error\n";
            $errorBody .= 'data: ' . $errorPayload . "\n\n";
            $errorBody .= "event: close\n";
            $errorBody .= "data: {\"ok\":false}\n\n";

            return $this->response
                ->setStatusCode(200)
                ->setHeader('Content-Type', 'text/event-stream; charset=UTF-8')
                ->setHeader('Cache-Control', 'no-cache, no-store, must-revalidate')
                ->setHeader('Pragma', 'no-cache')
                ->setHeader('Expires', '0')
                ->setHeader('X-Accel-Buffering', 'no')
                ->setBody($errorBody);
        }
        if (!empty($mensagens)) {
            $service->markConversationRead($id);
            $conversa = $conversaModel->find($id) ?? $conversa;
        }
        $latestId = $afterId;
        if (!empty($mensagens)) {
            $latestId = (int) end($mensagens)['id'];
        }

        $readyPayload = json_encode([
            'ok' => true,
            'conversa_id' => $id,
            'ts' => date('c'),
            'handshake' => (string) ($this->request->getGet('handshake') ?? '') === '1',
        ], JSON_UNESCAPED_UNICODE);

        $body = "retry: 3000\n";
        $body .= "event: ready\n";
        $body .= 'data: ' . $readyPayload . "\n\n";

        if (!empty($mensagens)) {
            $msgPayload = json_encode([
                'ok' => true,
                'conversa' => $conversa,
                'latest_id' => $latestId,
                'count' => count($mensagens),
                'mensagens' => $mensagens,
            ], JSON_UNESCAPED_UNICODE);
            $body .= "event: mensagens\n";
            $body .= 'data: ' . $msgPayload . "\n\n";
        } else {
            $pingPayload = json_encode([
                'ts' => date('c'),
                'latest_id' => $latestId,
            ], JSON_UNESCAPED_UNICODE);
            $body .= "event: ping\n";
            $body .= 'data: ' . $pingPayload . "\n\n";
        }

        $body .= "event: close\n";
        $body .= "data: {\"ok\":true}\n\n";

        if ((string) ($this->request->getGet('handshake') ?? '') === '1') {
            return $this->response
                ->setStatusCode(200)
                ->setHeader('Content-Type', 'text/event-stream; charset=UTF-8')
                ->setHeader('Cache-Control', 'no-cache, no-store, must-revalidate')
                ->setHeader('Pragma', 'no-cache')
                ->setHeader('Expires', '0')
                ->setHeader('X-Accel-Buffering', 'no')
                ->setBody($body);
        }

        return $this->response
            ->setStatusCode(200)
            ->setHeader('Content-Type', 'text/event-stream; charset=UTF-8')
            ->setHeader('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->setHeader('Pragma', 'no-cache')
            ->setHeader('Expires', '0')
            ->setHeader('X-Accel-Buffering', 'no')
            ->setBody($body);
    }

    public function enviar()
    {
        $endpoint = 'enviar';

        try {
            $sessionUserId = session()->get('user_id') ?: null;
            $this->releaseSessionLock();

            $conversaId = (int) ($this->request->getPost('conversa_id') ?? 0);
            $phone = trim((string) $this->request->getPost('telefone'));
            $mensagem = trim((string) $this->request->getPost('mensagem'));
            $tipoMensagem = trim((string) ($this->request->getPost('tipo_mensagem') ?: 'manual'));
            $osId = (int) ($this->request->getPost('os_id') ?? 0);
            $documentoId = (int) ($this->request->getPost('documento_id') ?? 0);
            $replyToMessageId = (int) ($this->request->getPost('reply_to_message_id') ?? 0);
            $replyToText = trim((string) ($this->request->getPost('reply_to_text') ?? ''));
            $replyToAuthor = trim((string) ($this->request->getPost('reply_to_author') ?? ''));
            $anexo = $this->request->getFile('anexo');
            $hasUpload = $anexo && $anexo->isValid() && !$anexo->hasMoved();

            if ($mensagem === '' && $documentoId <= 0 && !$hasUpload) {
                return $this->apiError(
                    'CM_ENVIO_EMPTY',
                    'Informe uma mensagem, selecione um PDF ou anexe um arquivo para envio.',
                    422,
                    [
                        'endpoint' => $endpoint,
                        'conversa_id' => $conversaId,
                    ]
                );
            }

            $conversaModel = new ConversaWhatsappModel();
            $conversa = $conversaId > 0 ? $conversaModel->find($conversaId) : null;
            if (!$conversa && $phone === '') {
                return $this->apiError(
                    'CM_ENVIO_TARGET_REQUIRED',
                    'Conversa ou telefone nao informado para envio.',
                    422,
                    [
                        'endpoint' => $endpoint,
                        'conversa_id' => $conversaId,
                    ]
                );
            }

            $service = new CentralMensagensService();
            if (!$conversa) {
                $conversa = $service->resolveConversationForOutgoing($phone, null, $osId > 0 ? $osId : null, (string) get_config('whatsapp_direct_provider', 'menuia'));
            }
            if (!$conversa) {
                return $this->apiError(
                    'CM_ENVIO_CONVERSA_RESOLVE_FAILED',
                    'Nao foi possivel iniciar a conversa para envio.',
                    422,
                    [
                        'endpoint' => $endpoint,
                        'telefone' => $phone,
                    ]
                );
            }

            $conversaId = (int) $conversa['id'];
            $phone = (string) ($conversa['telefone'] ?? $phone);
            $clienteId = (int) ($conversa['cliente_id'] ?? 0);
            if ($osId <= 0) {
                $osId = (int) ($conversa['os_id_principal'] ?? 0);
            }

            $arquivoPath = '';
            $arquivoRelative = '';
            $arquivoMime = '';
            $arquivoTipoConteudo = 'texto';
            $arquivoNome = '';
            $arquivoBytes = 0;

            if ($hasUpload) {
                $stored = $service->storeOutboundUpload($anexo, $phone);
                if (!$stored || empty($stored['arquivo'])) {
                    return $this->apiError(
                        'CM_ENVIO_UPLOAD_STORE_FAILED',
                        'Nao foi possivel salvar o arquivo anexado.',
                        422,
                        [
                            'endpoint' => $endpoint,
                            'conversa_id' => $conversaId,
                            'telefone' => $phone,
                        ]
                    );
                }
                $arquivoRelative = (string) $stored['arquivo'];
                $arquivoPath = FCPATH . ltrim($arquivoRelative, '/\\');
                $arquivoMime = (string) ($stored['mime_type'] ?? '');
                $arquivoTipoConteudo = (string) ($stored['tipo_conteudo'] ?? 'arquivo');
                $arquivoNome = (string) ($stored['arquivo_nome'] ?? basename($arquivoRelative));
                $arquivoBytes = (int) ($stored['tamanho_bytes'] ?? 0);
            } elseif ($documentoId > 0) {
                $doc = (new OsDocumentoModel())->where('id', $documentoId)->first();
                if (!$doc || empty($doc['arquivo'])) {
                    return $this->apiError(
                        'CM_ENVIO_PDF_NOT_FOUND',
                        'Documento PDF nao encontrado.',
                        422,
                        [
                            'endpoint' => $endpoint,
                            'conversa_id' => $conversaId,
                            'documento_id' => $documentoId,
                        ]
                    );
                }
                $sourceRelative = (string) $doc['arquivo'];
                $sourcePath = FCPATH . ltrim($sourceRelative, '/\\');
                if (!is_file($sourcePath)) {
                    return $this->apiError(
                        'CM_ENVIO_PDF_MISSING_FILE',
                        'Arquivo PDF nao encontrado no disco.',
                        422,
                        [
                            'endpoint' => $endpoint,
                            'conversa_id' => $conversaId,
                            'documento_id' => $documentoId,
                            'arquivo' => $sourceRelative,
                        ]
                    );
                }
                $stored = $service->copyFileToPhoneMedia($sourcePath, $phone, basename($sourcePath), 'application/pdf');
                if (!$stored || empty($stored['arquivo'])) {
                    return $this->apiError(
                        'CM_ENVIO_PDF_PREPARE_FAILED',
                        'Nao foi possivel preparar o PDF para envio na conversa.',
                        422,
                        [
                            'endpoint' => $endpoint,
                            'conversa_id' => $conversaId,
                            'documento_id' => $documentoId,
                        ]
                    );
                }
                $arquivoRelative = (string) $stored['arquivo'];
                $arquivoPath = FCPATH . ltrim($arquivoRelative, '/\\');
                $arquivoMime = (string) ($stored['mime_type'] ?? 'application/pdf');
                $arquivoTipoConteudo = (string) ($stored['tipo_conteudo'] ?? 'pdf');
                $arquivoNome = (string) ($stored['arquivo_nome'] ?? basename($arquivoRelative));
                $arquivoBytes = (int) ($stored['tamanho_bytes'] ?? 0);
                if ($osId <= 0) {
                    $osId = (int) ($doc['os_id'] ?? 0);
                }
            }

            $result = (new WhatsAppService())->sendRaw(
                $osId > 0 ? $osId : 0,
                $clienteId > 0 ? $clienteId : 0,
                $phone,
                $mensagem,
                $tipoMensagem,
                null,
                $sessionUserId,
                [
                    'arquivo_path' => $arquivoPath,
                    'arquivo' => $arquivoRelative,
                    'mime_type' => $arquivoMime !== '' ? $arquivoMime : null,
                    'tipo_conteudo' => $arquivoTipoConteudo !== '' ? $arquivoTipoConteudo : null,
                    'arquivo_nome' => $arquivoNome !== '' ? $arquivoNome : null,
                    'arquivo_tamanho' => $arquivoBytes > 0 ? $arquivoBytes : null,
                    'conversa_id' => $conversaId,
                    'enviada_por_bot' => false,
                    'enviada_por_usuario_id' => $sessionUserId,
                    'reply_to_message_id' => $replyToMessageId > 0 ? $replyToMessageId : null,
                    'reply_to_text' => $replyToText !== '' ? $replyToText : null,
                    'reply_to_author' => $replyToAuthor !== '' ? $replyToAuthor : null,
                ]
            );

            if (empty($result['ok'])) {
                $providerFailure = $this->resolveEnvioProviderFailure($result);

                return $this->apiError(
                    $providerFailure['code'],
                    $providerFailure['message'],
                    $providerFailure['status'],
                    [
                        'endpoint' => $endpoint,
                        'conversa_id' => $conversaId,
                        'provider_result' => $result,
                    ]
                );
            }

            return $this->apiSuccess('CM_ENVIO_OK', [
                'message' => 'Mensagem enviada com sucesso.',
                'conversa_id' => $conversaId,
                'result' => $result,
            ]);
        } catch (Throwable $e) {
            return $this->apiError(
                'CM_ENVIO_ERROR',
                'Falha inesperada ao enviar mensagem na Central.',
                500,
                [
                    'endpoint' => $endpoint,
                    'conversa_id' => (int) ($this->request->getPost('conversa_id') ?? 0),
                ],
                $e
            );
        }
    }

    public function vincularOs()
    {
        $endpoint = 'vincular_os';

        try {
            $conversaId = (int) ($this->request->getPost('conversa_id') ?? 0);
            $osId = (int) ($this->request->getPost('os_id') ?? 0);
            if ($conversaId <= 0 || $osId <= 0) {
                return $this->apiError(
                    'CM_VINCULO_INVALID_PARAMS',
                    'Conversa e OS sao obrigatorias para vinculo.',
                    422,
                    [
                        'endpoint' => $endpoint,
                        'conversa_id' => $conversaId,
                        'os_id' => $osId,
                    ]
                );
            }

            $os = (new OsModel())->getComplete($osId);
            if (!$os) {
                return $this->apiError(
                    'CM_VINCULO_OS_NOT_FOUND',
                    'OS nao encontrada para vinculo.',
                    404,
                    [
                        'endpoint' => $endpoint,
                        'conversa_id' => $conversaId,
                        'os_id' => $osId,
                    ]
                );
            }

            $service = new CentralMensagensService();
            $service->bindOsToConversa($conversaId, $osId, true);
            (new ConversaWhatsappModel())->update($conversaId, ['cliente_id' => $os['cliente_id'] ?? null]);

            return $this->apiSuccess('CM_VINCULO_OK', [
                'message' => 'Conversa vinculada a OS com sucesso.',
                'conversa_id' => $conversaId,
                'os_id' => $osId,
            ]);
        } catch (Throwable $e) {
            return $this->apiError(
                'CM_VINCULO_ERROR',
                'Falha inesperada ao vincular a OS na conversa.',
                500,
                [
                    'endpoint' => $endpoint,
                    'conversa_id' => (int) ($this->request->getPost('conversa_id') ?? 0),
                    'os_id' => (int) ($this->request->getPost('os_id') ?? 0),
                ],
                $e
            );
        }
    }

    public function syncInbound()
    {
        $endpoint = 'sync_inbound';

        try {
            $this->releaseSessionLock();
            $count = (new CentralMensagensService())->syncInboundQueue(300, true);
            return $this->apiSuccess('CM_SYNC_INBOUND_OK', [
                'message' => 'Sincronizacao concluida.',
                'count' => $count,
            ]);
        } catch (Throwable $e) {
            return $this->apiError(
                'CM_SYNC_INBOUND_ERROR',
                'Falha ao sincronizar mensagens inbound.',
                500,
                [
                    'endpoint' => $endpoint,
                ],
                $e
            );
        }
    }

    public function atualizarMeta()
    {
        $endpoint = 'atualizar_meta';

        try {
            $conversaId = (int) ($this->request->getPost('conversa_id') ?? 0);
            if ($conversaId <= 0) {
                return $this->apiError(
                    'CM_META_INVALID_PARAMS',
                    'Conversa obrigatoria para atualizar metadados.',
                    422,
                    [
                        'endpoint' => $endpoint,
                        'conversa_id' => $conversaId,
                    ]
                );
            }

            $tagIdsRaw = $this->request->getPost('tag_ids');
            if (is_string($tagIdsRaw)) {
                $decoded = json_decode($tagIdsRaw, true);
                $tagIdsRaw = is_array($decoded) ? $decoded : [];
            }
            if (!is_array($tagIdsRaw)) {
                $tagIdsRaw = [];
            }

            $payload = [
                'status' => trim((string) $this->request->getPost('status')),
                'responsavel_id' => (int) ($this->request->getPost('responsavel_id') ?? 0),
                'tag_ids' => $tagIdsRaw,
                'automacao_ativa' => (int) ($this->request->getPost('automacao_ativa') ? 1 : 0),
                'aguardando_humano' => (int) ($this->request->getPost('aguardando_humano') ? 1 : 0),
                'prioridade' => trim((string) $this->request->getPost('prioridade')),
            ];

            $ok = (new CentralMensagensService())->updateConversationMeta($conversaId, $payload, session()->get('user_id') ?: null);
            if (!$ok) {
                return $this->apiError(
                    'CM_META_UPDATE_FAILED',
                    'Nao foi possivel atualizar o contexto da conversa.',
                    422,
                    [
                        'endpoint' => $endpoint,
                        'conversa_id' => $conversaId,
                    ]
                );
            }

            return $this->apiSuccess('CM_META_UPDATED', [
                'message' => 'Contexto da conversa atualizado.',
                'conversa_id' => $conversaId,
            ]);
        } catch (Throwable $e) {
            return $this->apiError(
                'CM_META_ERROR',
                'Falha inesperada ao atualizar metadados da conversa.',
                500,
                [
                    'endpoint' => $endpoint,
                    'conversa_id' => (int) ($this->request->getPost('conversa_id') ?? 0),
                ],
                $e
            );
        }
    }

    public function cadastrarContatoConversa(int $id)
    {
        $endpoint = 'cadastrar_contato_conversa';
        $canWriteContato = function_exists('can')
            ? (can('clientes', 'criar') || can('clientes', 'editar'))
            : true;

        if (!$canWriteContato) {
            return $this->apiError(
                'CM_CONTATO_FORBIDDEN',
                'Voce nao possui permissao para salvar contatos nesta conversa.',
                403,
                [
                    'endpoint' => $endpoint,
                    'conversa_id' => $id,
                ]
            );
        }

        try {
            $conversaModel = new ConversaWhatsappModel();
            $conversa = $conversaModel->find($id);
            if (!$conversa) {
                return $this->apiError(
                    'CM_CONVERSA_NOT_FOUND',
                    'Conversa nao encontrada.',
                    404,
                    [
                        'endpoint' => $endpoint,
                        'conversa_id' => $id,
                    ]
                );
            }

            $telefone = $this->normalizePhone((string) ($conversa['telefone'] ?? ''));
            if ($telefone === '') {
                return $this->apiError(
                    'CM_CONTATO_PHONE_REQUIRED',
                    'Telefone da conversa invalido para cadastrar contato.',
                    422,
                    [
                        'endpoint' => $endpoint,
                        'conversa_id' => $id,
                    ]
                );
            }

            $nomeInput = trim((string) ($this->request->getPost('nome') ?? $this->request->getPost('nome_contato') ?? ''));
            if ($nomeInput === '') {
                $nomeInput = trim((string) ($conversa['nome_contato'] ?? ''));
            }
            if ($nomeInput !== '' && $this->isLikelyPhoneValue($nomeInput)) {
                $nomeInput = '';
            }

            $contatoModel = new ContatoModel();
            if (!$contatoModel->db->tableExists('contatos')) {
                return $this->apiError(
                    'CM_CONTATOS_SCHEMA_MISSING',
                    'Estrutura de contatos ainda nao foi migrada. Execute as migracoes do modulo Contatos.',
                    409,
                    [
                        'endpoint' => $endpoint,
                        'conversa_id' => $id,
                    ]
                );
            }
            $contato = $contatoModel->findByPhone($telefone);
            $contatoId = 0;
            $now = date('Y-m-d H:i:s');
            $clienteIdConversa = (int) ($conversa['cliente_id'] ?? 0);

            if ($contato) {
                $contatoId = (int) ($contato['id'] ?? 0);
                $updates = [
                    'ultimo_contato_em' => $now,
                ];

                if ($nomeInput !== '' && empty($contato['nome'])) {
                    $updates['nome'] = $nomeInput;
                }
                if ($nomeInput !== '' && empty($contato['whatsapp_nome_perfil'])) {
                    $updates['whatsapp_nome_perfil'] = $nomeInput;
                }

                if ($clienteIdConversa > 0 && (int) ($contato['cliente_id'] ?? 0) <= 0) {
                    $updates = $contatoModel->buildClienteConvertidoPayload($clienteIdConversa, $updates);
                } elseif ((int) ($contato['cliente_id'] ?? 0) <= 0 && $nomeInput !== '') {
                    $updates = $contatoModel->buildLeadPayload($updates, true);
                }

                if (!empty($updates)) {
                    $contatoModel->update($contatoId, $updates);
                }
            } else {
                $insert = [
                    'nome' => $nomeInput !== '' ? $nomeInput : null,
                    'telefone' => $telefone,
                    'telefone_normalizado' => $telefone,
                    'whatsapp_nome_perfil' => $nomeInput !== '' ? $nomeInput : null,
                    'origem' => 'whatsapp',
                    'ultimo_contato_em' => $now,
                ];
                if ($clienteIdConversa > 0) {
                    $insert = $contatoModel->buildClienteConvertidoPayload($clienteIdConversa, $insert);
                } else {
                    $insert = $contatoModel->buildLeadPayload($insert, $nomeInput !== '');
                }

                $contatoId = (int) $contatoModel->insert($insert, true);
                if ($contatoId <= 0) {
                    return $this->apiError(
                        'CM_CONTATO_CREATE_FAILED',
                        'Nao foi possivel cadastrar o contato para esta conversa.',
                        422,
                        [
                            'endpoint' => $endpoint,
                            'conversa_id' => $id,
                            'telefone' => $telefone,
                            'validation_errors' => $contatoModel->errors(),
                        ]
                    );
                }
            }

            if ($contatoId <= 0) {
                return $this->apiError(
                    'CM_CONTATO_SAVE_FAILED',
                    'Falha ao salvar contato da conversa.',
                    422,
                    [
                        'endpoint' => $endpoint,
                        'conversa_id' => $id,
                        'telefone' => $telefone,
                    ]
                );
            }

            $contato = $contatoModel->find($contatoId);
            if (!$contato) {
                return $this->apiError(
                    'CM_CONTATO_NOT_FOUND',
                    'Contato nao encontrado apos gravacao.',
                    404,
                    [
                        'endpoint' => $endpoint,
                        'conversa_id' => $id,
                        'contato_id' => $contatoId,
                    ]
                );
            }

            $updateConversa = [
                'contato_id' => $contatoId,
            ];
            $clienteIdContato = (int) ($contato['cliente_id'] ?? 0);
            if ((int) ($conversa['cliente_id'] ?? 0) <= 0 && $clienteIdContato > 0) {
                $updateConversa['cliente_id'] = $clienteIdContato;
            }
            if ($nomeInput !== '' && (empty($conversa['nome_contato']) || $this->isLikelyPhoneValue((string) $conversa['nome_contato']))) {
                $updateConversa['nome_contato'] = $nomeInput;
            }
            $conversaModel->update($id, $updateConversa);

            return $this->apiSuccess('CM_CONTATO_LINKED_OK', [
                'message' => 'Contato salvo e vinculado com sucesso na conversa.',
                'conversa_id' => $id,
                'contato_id' => $contatoId,
                'contato_nome' => (string) ($contato['nome'] ?? $contato['whatsapp_nome_perfil'] ?? $conversa['nome_contato'] ?? ''),
                'cliente_id' => (int) ($contato['cliente_id'] ?? 0) ?: null,
            ]);
        } catch (Throwable $e) {
            return $this->apiError(
                'CM_CONTATO_LINK_ERROR',
                'Falha inesperada ao cadastrar/vincular contato da conversa.',
                500,
                [
                    'endpoint' => $endpoint,
                    'conversa_id' => $id,
                ],
                $e
            );
        }
    }

    /**
     * @param array<string,mixed> $data
     */
    private function apiSuccess(string $code, array $data = [], int $status = 200): ResponseInterface
    {
        $payload = array_merge([
            'ok' => true,
            'status' => $status,
            'code' => $code,
        ], $data);

        return $this->response->setStatusCode($status)->setJSON($payload);
    }

    /**
     * @param array<string,mixed> $context
     */
    private function apiError(
        string $code,
        string $message,
        int $status = 500,
        array $context = [],
        ?Throwable $exception = null
    ): ResponseInterface {
        $this->observeEndpointFailure($code, $status, $message, $context, $exception);

        return $this->response->setStatusCode($status)->setJSON([
            'ok' => false,
            'status' => $status,
            'code' => $code,
            'message' => $message,
        ]);
    }

    /**
     * @param array<string,mixed> $context
     */
    private function observeEndpointFailure(
        string $code,
        int $status,
        string $message,
        array $context = [],
        ?Throwable $exception = null
    ): void {
        $ctx = $context;
        $ctx['status'] = $status;
        $ctx['code'] = $code;
        $ctx['uri'] = (string) current_url(true);
        $ctx['method'] = strtoupper((string) $this->request->getMethod());
        $ctx['request_ip'] = (string) $this->request->getIPAddress();
        if ($exception) {
            $ctx['exception'] = [
                'type' => get_class($exception),
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ];
        }

        $ctxJson = json_encode($ctx, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        log_message('error', '[CentralMensagens][' . $code . '] ' . $message . ' | context=' . (string) $ctxJson);

        try {
            $logModel = new LogModel();
            if ($logModel->db->tableExists('logs')) {
                $descricao = $message . ' | ' . (string) $ctxJson;
                $descricao = function_exists('mb_substr')
                    ? mb_substr($descricao, 0, 65500)
                    : substr($descricao, 0, 65500);

                $logModel->insert([
                    'usuario_id' => session()->get('user_id') ?: null,
                    'acao' => 'central_mensagens_' . strtolower($code),
                    'descricao' => $descricao,
                    'ip' => $this->request->getIPAddress(),
                    'user_agent' => (string) $this->request->getUserAgent()->getAgentString(),
                ]);
            }
        } catch (Throwable $e) {
            log_message('error', '[CentralMensagens][CM_OBSERVE_LOG_WRITE_ERROR] ' . $e->getMessage());
        }
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?? '';
    }

    /**
     * @param array<string,mixed> $result
     * @return array{code:string,message:string,status:int}
     */
    private function resolveEnvioProviderFailure(array $result): array
    {
        $statusCode = (int) ($result['status_code'] ?? 0);
        $provider = trim((string) ($result['provider'] ?? ''));
        $failureType = trim((string) ($result['failure_type'] ?? ''));
        $rawMessage = trim((string) ($result['message'] ?? 'Falha ao enviar mensagem.'));
        $message = function_exists('mb_strtolower')
            ? mb_strtolower($rawMessage, 'UTF-8')
            : strtolower($rawMessage);
        $providerKey = function_exists('mb_strtolower')
            ? mb_strtolower($provider, 'UTF-8')
            : strtolower($provider);
        $failureKey = function_exists('mb_strtolower')
            ? mb_strtolower($failureType, 'UTF-8')
            : strtolower($failureType);

        $isMisconfigured = in_array($failureKey, ['gateway_misconfigured', 'provider_misconfigured'], true)
            || str_contains($message, 'nao configurada')
            || str_contains($message, 'configuracao')
            || str_contains($message, 'incompleta');

        if ($isMisconfigured) {
            return [
                'code' => 'CM_ENVIO_PROVIDER_UNAVAILABLE',
                'message' => 'Configuracao do provedor de WhatsApp incompleta ou indisponivel. Revise as configuracoes e tente novamente.',
                'status' => 503,
            ];
        }

        $isUnavailable = in_array($failureKey, ['gateway_unreachable', 'gateway_timeout', 'provider_unavailable', 'provider_timeout'], true)
            || $statusCode === 0
            || $statusCode === 408
            || $statusCode === 429
            || ($statusCode >= 500 && $statusCode <= 599)
            || str_contains($message, 'falha de rede')
            || str_contains($message, 'couldn')
            || str_contains($message, 'timeout')
            || str_contains($message, 'timed out')
            || str_contains($message, 'inacessivel')
            || str_contains($message, 'indisponivel');

        if ($isUnavailable) {
            $friendlyMessage = in_array($providerKey, ['api_whats_local', 'api_whats_linux', 'local_node'], true)
                ? 'Servidor do gateway WhatsApp esta inacessivel no momento. Inicie ou reinicie o gateway e tente novamente.'
                : 'Servico de mensageria temporariamente indisponivel. Tente novamente em instantes.';

            return [
                'code' => 'CM_ENVIO_PROVIDER_UNAVAILABLE',
                'message' => $friendlyMessage,
                'status' => 503,
            ];
        }

        return [
            'code' => 'CM_ENVIO_PROVIDER_FAILED',
            'message' => $rawMessage !== '' ? $rawMessage : 'Falha ao enviar mensagem.',
            'status' => 422,
        ];
    }

    private function isLikelyPhoneValue(string $value): bool
    {
        $digits = $this->normalizePhone($value);
        if ($digits === '') {
            return false;
        }
        return strlen($digits) >= 8 && strlen(str_replace(['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'], '', $value)) <= 3;
    }

    /**
     * @param array<string,mixed> $conversa
     * @return array<string,mixed>
     */
    private function buildConversaContext(array $conversa): array
    {
        $clienteId = (int) ($conversa['cliente_id'] ?? 0);
        $contatoId = (int) ($conversa['contato_id'] ?? 0);
        $osPrincipalId = (int) ($conversa['os_id_principal'] ?? 0);
        $osPrincipal = ($osPrincipalId > 0) ? (new OsModel())->getComplete($osPrincipalId) : null;

        $contatoModel = new ContatoModel();
        $contato = null;
        if ($contatoId > 0 && $contatoModel->db->tableExists('contatos')) {
            $contato = $contatoModel->find($contatoId);
        } elseif ($contatoModel->db->tableExists('contatos')) {
            $telefone = $this->normalizePhone((string) ($conversa['telefone'] ?? ''));
            if ($telefone !== '') {
                $contato = $contatoModel->findByPhone($telefone);
                if ($contato) {
                    $contatoId = (int) ($contato['id'] ?? 0);
                    if ($contatoId > 0) {
                        (new ConversaWhatsappModel())->update((int) $conversa['id'], ['contato_id' => $contatoId]);
                        $conversa['contato_id'] = $contatoId;
                    }
                }
            }
        }

        if ($clienteId <= 0 && $contato && (int) ($contato['cliente_id'] ?? 0) > 0) {
            $clienteId = (int) $contato['cliente_id'];
            (new ConversaWhatsappModel())->update((int) $conversa['id'], ['cliente_id' => $clienteId]);
            $conversa['cliente_id'] = $clienteId;
        }

        $cliente = null;
        if ($clienteId > 0) {
            $cliente = (new ClienteModel())->find($clienteId);
        }

        $osList = [];
        if ($clienteId > 0) {
            $osList = (new OsModel())
                ->select('id, numero_os, status, estado_fluxo, data_abertura, data_previsao, valor_final')
                ->where('cliente_id', $clienteId)
                ->orderBy('id', 'DESC')
                ->findAll(30);
        }

        $osVinculadas = [];
        $conversaOsModel = new ConversaOsModel();
        if ($conversaOsModel->db->tableExists('conversa_os')) {
            $osVinculadas = $conversaOsModel
                ->select('conversa_os.*, os.numero_os, os.status, os.estado_fluxo')
                ->join('os', 'os.id = conversa_os.os_id', 'left')
                ->where('conversa_os.conversa_id', (int) $conversa['id'])
                ->orderBy('conversa_os.principal', 'DESC')
                ->orderBy('conversa_os.id', 'ASC')
                ->findAll();
        }

        $osIds = [];
        if ($osPrincipalId > 0) {
            $osIds[] = $osPrincipalId;
        }
        foreach ($osVinculadas as $row) {
            $rowOsId = (int) ($row['os_id'] ?? 0);
            if ($rowOsId > 0 && !in_array($rowOsId, $osIds, true)) {
                $osIds[] = $rowOsId;
            }
        }

        $docs = [];
        $docModel = new OsDocumentoModel();
        if ($docModel->db->tableExists('os_documentos') && !empty($osIds)) {
            $docs = $docModel
                ->select('id, os_id, tipo_documento, arquivo, created_at')
                ->whereIn('os_id', $osIds)
                ->orderBy('id', 'DESC')
                ->findAll(50);
        }

        $followups = [];
        $followupModel = new CrmFollowupModel();
        if ($followupModel->db->tableExists('crm_followups') && $clienteId > 0) {
            $followups = $followupModel
                ->select('id, cliente_id, os_id, titulo, data_prevista, status')
                ->where('cliente_id', $clienteId)
                ->where('status', 'pendente')
                ->orderBy('data_prevista', 'ASC')
                ->findAll(20);
        }

        $orcamentos = [];
        $orcamentoStatusLabels = [];
        $orcamentoModel = new OrcamentoModel();
        if ($orcamentoModel->db->tableExists('orcamentos')) {
            $builder = $orcamentoModel
                ->select('id, numero, status, total, validade_data, os_id, created_at')
                ->orderBy('id', 'DESC');

            $hasScope = false;
            $builder->groupStart();
            if ((int) ($conversa['id'] ?? 0) > 0) {
                $builder->where('conversa_id', (int) $conversa['id']);
                $hasScope = true;
            }
            if ($clienteId > 0) {
                if ($hasScope) {
                    $builder->orWhere('cliente_id', $clienteId);
                } else {
                    $builder->where('cliente_id', $clienteId);
                    $hasScope = true;
                }
            }
            if ($osPrincipalId > 0) {
                if ($hasScope) {
                    $builder->orWhere('os_id', $osPrincipalId);
                } else {
                    $builder->where('os_id', $osPrincipalId);
                    $hasScope = true;
                }
            }
            $builder->groupEnd();

            if ($hasScope) {
                $orcamentos = $builder->findAll(12);
            }

            $orcamentoStatusLabels = $orcamentoModel->statusLabels();
        }

        $service = new CentralMensagensService();

        return [
            'cliente' => $cliente,
            'contato' => $contato,
            'cliente_novo' => $clienteId <= 0,
            'os' => $osList,
            'os_principal' => $osPrincipal,
            'os_vinculadas' => $osVinculadas,
            'documentos' => $docs,
            'followups' => $followups,
            'orcamentos' => $orcamentos,
            'orcamento_status_labels' => $orcamentoStatusLabels,
            'meta' => [
                'status' => (string) ($conversa['status'] ?? 'aberta'),
                'status_options' => ['aberta', 'aguardando', 'resolvida', 'arquivada'],
                'responsavel_id' => (int) ($conversa['responsavel_id'] ?? 0),
                'responsaveis' => $service->getResponsaveisAtivos(),
                'tags' => $service->getConversaTagIds((int) $conversa['id']),
                'tag_catalogo' => $service->getTagCatalog(),
                'automacao_ativa' => (int) ($conversa['automacao_ativa'] ?? 1),
                'aguardando_humano' => (int) ($conversa['aguardando_humano'] ?? 0),
                'prioridade' => (string) ($conversa['prioridade'] ?? 'normal'),
            ],
        ];
    }

    /**
     * @return array<string,string>
     */
    private function centralConfigKeys(): array
    {
        return [
            'central_mensagens_auto_sync_interval' => '15',
            'central_mensagens_sla_primeira_resposta_min' => '60',
            'central_mensagens_default_provider' => (string) get_config('whatsapp_direct_provider', 'api_whats_local'),
            'central_mensagens_auto_bot_enabled' => '1',
            'central_mensagens_bot_confidence_threshold' => '0.20',
            'central_mensagens_horario_inicio' => '08:00',
            'central_mensagens_horario_fim' => '18:00',
            'central_mensagens_dias_uteis' => '1,2,3,4,5,6',
            'central_mensagens_bot_fallback_message' => 'Recebi sua mensagem e vou encaminhar para um atendente humano continuar o atendimento.',
        ];
    }

    private function releaseSessionLock(): void
    {
        if (!function_exists('session')) {
            return;
        }

        try {
            session()->close();
        } catch (\Throwable $e) {
            // segue sem interromper o endpoint quando nao for possivel liberar lock da sessao.
        }
    }

    private function syncInboundSafe(): void
    {
        try {
            (new CentralMensagensService())->syncInboundQueue(120);
        } catch (\Throwable $e) {
            log_message('warning', 'CentralMensagens sync inbound falhou: ' . $e->getMessage());
        }
    }

    private function isCentralDisponivel(ConversaWhatsappModel $conversaModel): bool
    {
        return $conversaModel->db->tableExists('conversas_whatsapp')
            && $conversaModel->db->tableExists('mensagens_whatsapp');
    }

    /**
     * @return array<int,string>
     */
    private function parseListInput(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $items = $decoded;
        } else {
            $items = preg_split('/[\r\n,;]+/', $raw) ?: [];
        }

        $out = [];
        foreach ($items as $item) {
            $item = trim((string) $item);
            if ($item !== '') {
                $out[] = $item;
            }
        }
        return array_values(array_unique($out));
    }

    private function parseJsonInput(string $raw): ?string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return json_encode($decoded, JSON_UNESCAPED_UNICODE);
        }

        $fallback = $this->parseListInput($raw);
        if (!empty($fallback)) {
            return json_encode($fallback, JSON_UNESCAPED_UNICODE);
        }

        return json_encode(['raw' => $raw], JSON_UNESCAPED_UNICODE);
    }

    private function respondAction(bool $ok, string $message, string $redirect)
    {
        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'ok' => $ok,
                'message' => $message,
            ]);
        }

        return redirect()->to($redirect)->with($ok ? 'success' : 'error', $message);
    }
}

