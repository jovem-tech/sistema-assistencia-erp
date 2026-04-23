<?php

namespace App\Controllers;

use App\Models\ClienteModel;
use App\Models\ConfiguracaoModel;
use App\Models\ContatoModel;
use App\Models\CrmEventoModel;
use App\Models\CrmFollowupModel;
use App\Models\CrmInteracaoModel;
use App\Models\CrmPipelineEtapaModel;
use App\Models\CrmPipelineModel;
use App\Models\CrmTagModel;
use App\Models\OsModel;
use App\Models\WhatsappTemplateModel;
use App\Services\CentralMensagensService;
use App\Services\CrmService;

class Crm extends BaseController
{
    // Seção CRM e Central de Mensagens

    public function clientes()
    {
        $this->syncInbound();
        
        $db = \Config\Database::connect();
        if (!$db->tableExists('clientes')) {
             return redirect()->to('/dashboard')->with('error', 'Modulo CRM ainda nao foi migrado.');
        }
        $q = trim((string) $this->request->getGet('q'));
        $status = trim((string) $this->request->getGet('status'));

        // Query para buscar clientes e a última interação
        $builder = $db->table('clientes c');
        $builder->select('c.id, c.nome_razao, c.telefone1, c.email, c.cpf_cnpj');
        $builder->select('(SELECT data_interacao FROM crm_interacoes WHERE cliente_id = c.id ORDER BY data_interacao DESC LIMIT 1) as ultima_interacao');
        $builder->select('(SELECT tipo FROM crm_interacoes WHERE cliente_id = c.id ORDER BY data_interacao DESC LIMIT 1) as ultima_interacao_tipo');

        if ($q !== '') {
            $builder->groupStart()
                ->like('c.nome_razao', $q)
                ->orLike('c.telefone1', $q)
                ->orLike('c.cpf_cnpj', $q)
                ->groupEnd();
        }

        $clientesRaw = $builder->orderBy('c.nome_razao', 'ASC')->get()->getResultArray();

        // Filtragem por Status (Calculado via PHP para ser mais flexível)
        $clientes = [];
        $agora = time();
        foreach ($clientesRaw as $c) {
            $dias = 999;
            if ($c['ultima_interacao']) {
                $dias = (int)(($agora - strtotime($c['ultima_interacao'])) / 86400);
            }

            $currentStatus = 'inativo';
            if ($dias <= 30) $currentStatus = 'ativo';
            elseif ($dias <= 90) $currentStatus = 'em_risco';

            if ($status === '' || $status === $currentStatus) {
                $clientes[] = $c;
            }
        }

        $data = [
            'title' => 'CRM - Gestão de Clientes',
            'clientes' => $clientes,
            'filtro_q' => $q,
            'filtro_status' => $status,
        ];

        return view('crm/clientes', $data);
    }

    public function timeline()
    {
        $this->syncInbound();

        $eventoModel = new CrmEventoModel();
        if (!$eventoModel->db->tableExists('crm_eventos')) {
            return redirect()->to('/dashboard')->with('error', 'Modulo CRM ainda nao foi migrado.');
        }

        $clienteId = (int) ($this->request->getGet('cliente_id') ?? 0);
        $osId = (int) ($this->request->getGet('os_id') ?? 0);
        $tipo = trim((string) $this->request->getGet('tipo_evento'));

        $builder = $eventoModel
            ->select('crm_eventos.*, clientes.nome_razao as cliente_nome, os.numero_os')
            ->join('clientes', 'clientes.id = crm_eventos.cliente_id', 'left')
            ->join('os', 'os.id = crm_eventos.os_id', 'left');

        if ($clienteId > 0) {
            $builder->where('crm_eventos.cliente_id', $clienteId);
        }
        if ($osId > 0) {
            $builder->where('crm_eventos.os_id', $osId);
        }
        if ($tipo !== '') {
            $builder->where('crm_eventos.tipo_evento', $tipo);
        }

        $eventos = $builder->orderBy('crm_eventos.data_evento', 'DESC')->findAll(300);

        $data = [
            'title' => 'CRM - Timeline',
            'clientes' => (new ClienteModel())->orderBy('nome_razao', 'ASC')->findAll(),
            'eventos' => $eventos,
            'filtro_cliente_id' => $clienteId,
            'filtro_os_id' => $osId,
            'filtro_tipo_evento' => $tipo,
            'tipos_evento' => $this->listDistinctValues('crm_eventos', 'tipo_evento'),
        ];

        return view('crm/timeline', $data);
    }

    public function interacoes()
    {
        $this->syncInbound();

        $interacaoModel = new CrmInteracaoModel();
        if (!$interacaoModel->db->tableExists('crm_interacoes')) {
            return redirect()->to('/dashboard')->with('error', 'Modulo CRM ainda nao foi migrado.');
        }

        $clienteId = (int) ($this->request->getGet('cliente_id') ?? 0);
        $builder = $interacaoModel
            ->select('crm_interacoes.*, clientes.nome_razao as cliente_nome, os.numero_os, usuarios.nome as usuario_nome')
            ->join('clientes', 'clientes.id = crm_interacoes.cliente_id', 'left')
            ->join('os', 'os.id = crm_interacoes.os_id', 'left')
            ->join('usuarios', 'usuarios.id = crm_interacoes.usuario_id', 'left');

        if ($clienteId > 0) {
            $builder->where('crm_interacoes.cliente_id', $clienteId);
        }

        $data = [
            'title' => 'CRM - Interacoes',
            'clientes' => (new ClienteModel())->orderBy('nome_razao', 'ASC')->findAll(),
            'osRecentes' => (new OsModel())->select('id, numero_os')->orderBy('id', 'DESC')->findAll(200),
            'interacoes' => $builder->orderBy('crm_interacoes.data_interacao', 'DESC')->findAll(300),
            'filtro_cliente_id' => $clienteId,
        ];

        return view('crm/interacoes', $data);
    }

    public function salvarInteracao()
    {
        $clienteId = (int) ($this->request->getPost('cliente_id') ?? 0);
        $osId = (int) ($this->request->getPost('os_id') ?? 0);
        $tipo = trim((string) $this->request->getPost('tipo'));
        $descricao = trim((string) $this->request->getPost('descricao'));
        $canal = trim((string) $this->request->getPost('canal'));

        if ($clienteId <= 0 || $descricao === '' || $tipo === '' || $canal === '') {
            return redirect()->back()->withInput()->with('error', 'Preencha cliente, tipo, canal e descricao da interacao.');
        }

        (new CrmService())->registerInteraction([
            'cliente_id' => $clienteId,
            'os_id' => $osId > 0 ? $osId : null,
            'tipo' => $tipo,
            'descricao' => $descricao,
            'canal' => $canal,
            'usuario_id' => session()->get('user_id') ?: null,
            'data_interacao' => date('Y-m-d H:i:s'),
            'payload_json' => ['origem' => 'manual'],
        ]);

        return redirect()->to('/crm/interacoes')->with('success', 'Interacao CRM registrada com sucesso.');
    }

    public function followups()
    {
        $followModel = new CrmFollowupModel();
        if (!$followModel->db->tableExists('crm_followups')) {
            return redirect()->to('/dashboard')->with('error', 'Modulo CRM ainda nao foi migrado.');
        }

        $status = trim((string) $this->request->getGet('status'));
        $builder = $followModel
            ->select('crm_followups.*, clientes.nome_razao as cliente_nome, os.numero_os, usuarios.nome as responsavel_nome')
            ->join('clientes', 'clientes.id = crm_followups.cliente_id', 'left')
            ->join('os', 'os.id = crm_followups.os_id', 'left')
            ->join('usuarios', 'usuarios.id = crm_followups.usuario_responsavel', 'left');

        if ($status !== '') {
            $builder->where('crm_followups.status', $status);
        }

        $data = [
            'title' => 'CRM - Follow-ups',
            'clientes' => (new ClienteModel())->orderBy('nome_razao', 'ASC')->findAll(),
            'osRecentes' => (new OsModel())->select('id, numero_os')->orderBy('id', 'DESC')->findAll(200),
            'followups' => $builder->orderBy('crm_followups.data_prevista', 'ASC')->findAll(300),
            'filtro_status' => $status,
        ];

        return view('crm/followups', $data);
    }

    public function salvarFollowup()
    {
        $clienteId = (int) ($this->request->getPost('cliente_id') ?? 0);
        $titulo = trim((string) $this->request->getPost('titulo'));
        $dataPrevista = trim((string) $this->request->getPost('data_prevista'));
        if ($clienteId <= 0 || $titulo === '' || $dataPrevista === '') {
            return redirect()->back()->withInput()->with('error', 'Preencha cliente, titulo e data prevista do follow-up.');
        }

        (new CrmService())->createFollowup([
            'cliente_id' => $clienteId,
            'os_id' => (int) ($this->request->getPost('os_id') ?? 0) ?: null,
            'titulo' => $titulo,
            'descricao' => trim((string) $this->request->getPost('descricao')) ?: null,
            'data_prevista' => $dataPrevista,
            'status' => 'pendente',
            'usuario_responsavel' => session()->get('user_id') ?: null,
            'origem_evento' => 'manual',
        ]);

        return redirect()->to('/crm/followups')->with('success', 'Follow-up criado com sucesso.');
    }

    public function atualizarFollowupStatus(int $id)
    {
        $status = trim((string) $this->request->getPost('status'));
        if (!in_array($status, ['pendente', 'concluido', 'cancelado'], true)) {
            return redirect()->back()->with('error', 'Status de follow-up invalido.');
        }

        $followModel = new CrmFollowupModel();
        $payload = ['status' => $status];
        if ($status === 'concluido') {
            $payload['concluido_em'] = date('Y-m-d H:i:s');
        }
        $followModel->update($id, $payload);

        return redirect()->to('/crm/followups')->with('success', 'Status do follow-up atualizado.');
    }

    public function pipeline()
    {
        $pipelineModel = new CrmPipelineModel();
        if (!$pipelineModel->db->tableExists('crm_pipeline')) {
            return redirect()->to('/dashboard')->with('error', 'Modulo CRM ainda nao foi migrado.');
        }

        $etapas = (new CrmPipelineEtapaModel())->ativas();
        $linhas = $pipelineModel
            ->select('crm_pipeline.*, clientes.nome_razao as cliente_nome, os.numero_os, os.status as os_status')
            ->join('clientes', 'clientes.id = crm_pipeline.cliente_id', 'left')
            ->join('os', 'os.id = crm_pipeline.os_id', 'left')
            ->where('crm_pipeline.status', 'ativo')
            ->orderBy('crm_pipeline.updated_at', 'DESC')
            ->findAll(500);

        $cards = [];
        foreach ($etapas as $etapa) {
            $cards[$etapa['codigo']] = [
                'meta' => $etapa,
                'items' => [],
            ];
        }
        foreach ($linhas as $row) {
            $codigo = (string) ($row['etapa_atual'] ?? '');
            if (!isset($cards[$codigo])) {
                $cards[$codigo] = [
                    'meta' => ['codigo' => $codigo, 'nome' => ucwords(str_replace('_', ' ', $codigo))],
                    'items' => [],
                ];
            }
            $cards[$codigo]['items'][] = $row;
        }

        $data = [
            'title' => 'CRM - Pipeline',
            'pipelineCards' => $cards,
        ];

        return view('crm/pipeline', $data);
    }

    public function campanhas()
    {
        $automacoes = [];
        $templates = [];
        $tagStats = [];

        $db = \Config\Database::connect();

        if ($db->tableExists('crm_automacoes')) {
            $automacoes = $db->table('crm_automacoes')
                ->orderBy('ativo', 'DESC')
                ->orderBy('nome', 'ASC')
                ->get()
                ->getResultArray();
        }

        $tplModel = new WhatsappTemplateModel();
        if ($tplModel->db->tableExists('whatsapp_templates')) {
            $templates = $tplModel->orderBy('ativo', 'DESC')->orderBy('nome', 'ASC')->findAll();
        }

        $tagModel = new CrmTagModel();
        if ($tagModel->db->tableExists('crm_tags') && $db->tableExists('crm_tags_cliente')) {
            $tagStats = $db->table('crm_tags t')
                ->select('t.id, t.nome, t.cor, COUNT(tc.id) as total_clientes')
                ->join('crm_tags_cliente tc', 'tc.tag_id = t.id', 'left')
                ->groupBy('t.id, t.nome, t.cor')
                ->orderBy('t.nome', 'ASC')
                ->get()
                ->getResultArray();
        }

        return view('crm/campanhas', [
            'title' => 'CRM - Campanhas',
            'automacoes' => $automacoes,
            'templates' => $templates,
            'tagStats' => $tagStats,
        ]);
    }

    public function metricasMarketing()
    {
        $inicioInput = trim((string) $this->request->getGet('inicio'));
        $fimInput = trim((string) $this->request->getGet('fim'));
        $periodo = strtolower(trim((string) $this->request->getGet('periodo')));
        if ($periodo === '' && $this->isDateYmd($inicioInput) && $this->isDateYmd($fimInput)) {
            $periodo = 'custom';
        }
        [$periodo, $inicio, $fim] = $this->resolveMarketingPeriodo($periodo, $inicioInput, $fimInput);

        $canal = strtolower(trim((string) $this->request->getGet('canal')));
        $responsavelId = (int) ($this->request->getGet('responsavel_id') ?? 0);
        $statusConversa = strtolower(trim((string) $this->request->getGet('status')));
        $tagId = (int) ($this->request->getGet('tag_id') ?? 0);

        $db = \Config\Database::connect();
        [$periodoAtivoDias, $periodoRiscoDias] = $this->getEngajamentoPeriodos();
        $supportsEngajamento = false;
        $contatosTableExists = $db->tableExists('contatos');
        $conversasTableExists = $db->tableExists('conversas_whatsapp');
        $mensagensTableExists = $db->tableExists('mensagens_whatsapp');
        $hasContatoOrigem = $contatosTableExists && $db->fieldExists('origem', 'contatos');
        $hasConversaCanal = $conversasTableExists && $db->fieldExists('canal', 'conversas_whatsapp');
        $hasConversaResponsavel = $conversasTableExists && $db->fieldExists('responsavel_id', 'conversas_whatsapp');
        $hasConversaStatus = $conversasTableExists && $db->fieldExists('status', 'conversas_whatsapp');
        $conversaTagsTableExists = $db->tableExists('conversa_tags');

        $cards = [
            'leads_captados' => 0,
            'leads_qualificados' => 0,
            'leads_convertidos' => 0,
            'taxa_qualificacao' => 0.0,
            'taxa_conversao' => 0.0,
            'taxa_conversao_captados' => 0.0,
            'conversas_iniciadas' => 0,
            'conversas_ativas' => 0,
            'conversas_clientes_novos' => 0,
            'mensagens_inbound' => 0,
            'mensagens_outbound' => 0,
            'mensagens_total' => 0,
            'tempo_primeira_resposta_min' => null,
            'os_origem_whatsapp' => 0,
            'contatos_base_total' => 0,
            'contatos_sem_vinculo' => 0,
            'contatos_engajamento_ativos' => 0,
            'contatos_engajamento_risco' => 0,
            'contatos_engajamento_inativos' => 0,
        ];

        $origens = [];
        $tagStats = [];
        $seriesLeadsRows = [];
        $seriesLeadsQualificadosRows = [];
        $seriesLeadsConvertidosRows = [];
        $seriesConversasRows = [];
        $canalStats = [];
        $rankingAtendimento = [];
        $kpiDeltas = [];
        $insights = [];
        $serieResumoRows = [];
        $responsavelOptions = [];
        $canalOptions = [];
        $statusOptions = [];
        $tagOptions = [];

        if ($conversasTableExists && $hasConversaCanal) {
            $canalRows = $db->table('conversas_whatsapp')
                ->select('canal')
                ->where('canal IS NOT NULL', null, false)
                ->where('canal <>', '')
                ->groupBy('canal')
                ->orderBy('canal', 'ASC')
                ->get()
                ->getResultArray();
            foreach ($canalRows as $canalRow) {
                $canalValue = strtolower(trim((string) ($canalRow['canal'] ?? '')));
                if ($canalValue === '') {
                    continue;
                }
                $canalOptions[$canalValue] = ucfirst(str_replace('_', ' ', $canalValue));
            }
        }

        if ($contatosTableExists && $hasContatoOrigem) {
            $origemRows = $db->table('contatos')
                ->select('origem')
                ->where('origem IS NOT NULL', null, false)
                ->where('origem <>', '')
                ->groupBy('origem')
                ->orderBy('origem', 'ASC')
                ->get()
                ->getResultArray();
            foreach ($origemRows as $origemRow) {
                $origemValue = strtolower(trim((string) ($origemRow['origem'] ?? '')));
                if ($origemValue === '') {
                    continue;
                }
                $canalOptions[$origemValue] = ucfirst(str_replace('_', ' ', $origemValue));
            }
        }
        ksort($canalOptions);
        if ($canal !== '' && !isset($canalOptions[$canal])) {
            $canalOptions[$canal] = ucfirst(str_replace('_', ' ', $canal));
        }

        if ($conversasTableExists && $hasConversaStatus) {
            $statusRows = $db->table('conversas_whatsapp')
                ->select('status')
                ->where('status IS NOT NULL', null, false)
                ->where('status <>', '')
                ->groupBy('status')
                ->orderBy('status', 'ASC')
                ->get()
                ->getResultArray();
            foreach ($statusRows as $statusRow) {
                $statusValue = strtolower(trim((string) ($statusRow['status'] ?? '')));
                if ($statusValue === '') {
                    continue;
                }
                $statusOptions[$statusValue] = ucfirst(str_replace('_', ' ', $statusValue));
            }
        }
        if ($statusConversa !== '' && !isset($statusOptions[$statusConversa])) {
            $statusConversa = '';
        }

        if ($db->tableExists('crm_tags')) {
            $tagRows = $db->table('crm_tags')
                ->select('id, nome')
                ->where('ativo', 1)
                ->orderBy('nome', 'ASC')
                ->get()
                ->getResultArray();
            foreach ($tagRows as $tagRow) {
                $tagOptionId = (int) ($tagRow['id'] ?? 0);
                if ($tagOptionId <= 0) {
                    continue;
                }
                $tagOptions[$tagOptionId] = (string) ($tagRow['nome'] ?? ('Tag #' . $tagOptionId));
            }
        }
        if ($tagId > 0 && !isset($tagOptions[$tagId])) {
            $tagId = 0;
        }

        if ($db->tableExists('usuarios')) {
            if ($conversasTableExists && $hasConversaResponsavel) {
                $responsavelRows = $db->table('usuarios u')
                    ->select('u.id, u.nome')
                    ->join('conversas_whatsapp c', 'c.responsavel_id = u.id', 'inner')
                    ->where('c.responsavel_id IS NOT NULL', null, false)
                    ->groupBy('u.id, u.nome')
                    ->orderBy('u.nome', 'ASC')
                    ->get()
                    ->getResultArray();
            } else {
                $responsavelRows = $db->table('usuarios')
                    ->select('id, nome')
                    ->where('ativo', 1)
                    ->orderBy('nome', 'ASC')
                    ->get()
                    ->getResultArray();
            }

            foreach ($responsavelRows as $responsavelRow) {
                $respId = (int) ($responsavelRow['id'] ?? 0);
                if ($respId <= 0) {
                    continue;
                }
                $responsavelOptions[$respId] = (string) ($responsavelRow['nome'] ?? ('Usuario #' . $respId));
            }
        }
        if ($responsavelId > 0 && !isset($responsavelOptions[$responsavelId])) {
            $responsavelId = 0;
        }

        $applyContatoFilters = static function ($builder) use ($canal, $hasContatoOrigem): void {
            if ($canal !== '' && $hasContatoOrigem) {
                $builder->where('origem', $canal);
            }
        };

        $applyConversaFilters = static function (
            $builder,
            ?string $alias = null
        ) use (
            $canal,
            $responsavelId,
            $statusConversa,
            $tagId,
            $hasConversaCanal,
            $hasConversaResponsavel,
            $hasConversaStatus,
            $conversaTagsTableExists
        ): void {
            $columnPrefix = $alias !== null && $alias !== '' ? $alias . '.' : 'conversas_whatsapp.';

            if ($canal !== '' && $hasConversaCanal) {
                $builder->where($columnPrefix . 'canal', $canal);
            }
            if ($responsavelId > 0 && $hasConversaResponsavel) {
                $builder->where($columnPrefix . 'responsavel_id', $responsavelId);
            }
            if ($statusConversa !== '' && $hasConversaStatus) {
                $builder->where($columnPrefix . 'status', $statusConversa);
            }
            if ($tagId > 0 && $conversaTagsTableExists) {
                $joinAlias = 'ct_filter';
                if ($alias !== null && $alias !== '') {
                    $joinAlias .= '_' . preg_replace('/[^a-z0-9_]/i', '', $alias);
                }
                $builder->join(
                    'conversa_tags ' . $joinAlias,
                    $joinAlias . '.conversa_id = ' . $columnPrefix . 'id',
                    'inner'
                )->where($joinAlias . '.tag_id', $tagId);
            }
        };

        if ($contatosTableExists) {
            $contatoModel = new ContatoModel();
            $supportsEngajamento = $contatoModel->supportsEngajamentoFields();
            if ($supportsEngajamento) {
                try {
                    $contatoModel->recalculateEngajamentoBulk($periodoAtivoDias, $periodoRiscoDias);
                } catch (\Throwable $e) {
                    log_message('warning', 'CRM metricas marketing: falha ao recalcular engajamento dos contatos: ' . $e->getMessage());
                }
            }

            $hasStatusRelacionamento = $db->fieldExists('status_relacionamento', 'contatos');
            $hasQualificadoEm = $db->fieldExists('qualificado_em', 'contatos');
            $hasConvertidoEm = $db->fieldExists('convertido_em', 'contatos');
            $hasEngajamentoStatus = $db->fieldExists('engajamento_status', 'contatos');

            $contatosBaseBuilder = $db->table('contatos');
            $applyContatoFilters($contatosBaseBuilder);
            $cards['contatos_base_total'] = (int) $contatosBaseBuilder->countAllResults();

            $contatosSemVinculoBuilder = $db->table('contatos')
                ->where('cliente_id IS NULL', null, false);
            $applyContatoFilters($contatosSemVinculoBuilder);
            $cards['contatos_sem_vinculo'] = (int) $contatosSemVinculoBuilder->countAllResults();

            if ($hasEngajamentoStatus) {
                $engajamentoAtivosBuilder = $db->table('contatos')
                    ->where('engajamento_status', ContatoModel::STATUS_ENGAJAMENTO_ATIVO);
                $applyContatoFilters($engajamentoAtivosBuilder);
                $cards['contatos_engajamento_ativos'] = (int) $engajamentoAtivosBuilder->countAllResults();

                $engajamentoRiscoBuilder = $db->table('contatos')
                    ->where('engajamento_status', ContatoModel::STATUS_ENGAJAMENTO_EM_RISCO);
                $applyContatoFilters($engajamentoRiscoBuilder);
                $cards['contatos_engajamento_risco'] = (int) $engajamentoRiscoBuilder->countAllResults();

                $engajamentoInativosBuilder = $db->table('contatos')
                    ->where('engajamento_status', ContatoModel::STATUS_ENGAJAMENTO_INATIVO);
                $applyContatoFilters($engajamentoInativosBuilder);
                $cards['contatos_engajamento_inativos'] = (int) $engajamentoInativosBuilder->countAllResults();
            }

            $leadsCaptadosBuilder = $db->table('contatos')
                ->where('DATE(created_at) >=', $inicio)
                ->where('DATE(created_at) <=', $fim);
            $applyContatoFilters($leadsCaptadosBuilder);
            $cards['leads_captados'] = (int) $leadsCaptadosBuilder->countAllResults();

            if ($hasQualificadoEm) {
                $leadsQualificadosBuilder = $db->table('contatos')
                    ->where('qualificado_em IS NOT NULL', null, false)
                    ->where('DATE(qualificado_em) >=', $inicio)
                    ->where('DATE(qualificado_em) <=', $fim);
                $applyContatoFilters($leadsQualificadosBuilder);
                $cards['leads_qualificados'] = (int) $leadsQualificadosBuilder->countAllResults();
            } elseif ($hasStatusRelacionamento) {
                $leadsQualificadosBuilder = $db->table('contatos')
                    ->whereIn('status_relacionamento', ['lead_qualificado', 'cliente_convertido'])
                    ->where('DATE(updated_at) >=', $inicio)
                    ->where('DATE(updated_at) <=', $fim);
                $applyContatoFilters($leadsQualificadosBuilder);
                $cards['leads_qualificados'] = (int) $leadsQualificadosBuilder->countAllResults();
            } else {
                $leadsQualificadosBuilder = $db->table('contatos')
                    ->groupStart()
                        ->where('nome IS NOT NULL', null, false)
                        ->where('nome <>', '')
                    ->groupEnd()
                    ->where('DATE(updated_at) >=', $inicio)
                    ->where('DATE(updated_at) <=', $fim);
                $applyContatoFilters($leadsQualificadosBuilder);
                $cards['leads_qualificados'] = (int) $leadsQualificadosBuilder->countAllResults();
            }

            if ($hasConvertidoEm) {
                $leadsConvertidosBuilder = $db->table('contatos')
                    ->where('cliente_id IS NOT NULL', null, false)
                    ->where('convertido_em IS NOT NULL', null, false)
                    ->where('DATE(convertido_em) >=', $inicio)
                    ->where('DATE(convertido_em) <=', $fim);
                $applyContatoFilters($leadsConvertidosBuilder);
                $cards['leads_convertidos'] = (int) $leadsConvertidosBuilder->countAllResults();
            } else {
                $leadsConvertidosBuilder = $db->table('contatos')
                    ->where('cliente_id IS NOT NULL', null, false)
                    ->groupStart()
                        ->where('DATE(updated_at) >=', $inicio)
                        ->where('DATE(updated_at) <=', $fim)
                    ->groupEnd();
                $applyContatoFilters($leadsConvertidosBuilder);
                $cards['leads_convertidos'] = (int) $leadsConvertidosBuilder->countAllResults();
            }

            $origensBuilder = $db->table('contatos')
                ->select("COALESCE(NULLIF(origem, ''), 'nao_informada') as origem, COUNT(*) as total", false)
                ->where('DATE(created_at) >=', $inicio)
                ->where('DATE(created_at) <=', $fim)
                ->groupBy("COALESCE(NULLIF(origem, ''), 'nao_informada')", false)
                ->orderBy('total', 'DESC');
            $applyContatoFilters($origensBuilder);
            $origens = $origensBuilder->get()->getResultArray();

            $seriesLeadsBuilder = $db->table('contatos')
                ->select('DATE(created_at) as dia, COUNT(*) as total', false)
                ->where('DATE(created_at) >=', $inicio)
                ->where('DATE(created_at) <=', $fim)
                ->groupBy('DATE(created_at)', false)
                ->orderBy('dia', 'ASC');
            $applyContatoFilters($seriesLeadsBuilder);
            $seriesLeadsRows = $seriesLeadsBuilder->get()->getResultArray();

            if ($hasQualificadoEm) {
                $seriesLeadsQualificadosBuilder = $db->table('contatos')
                    ->select('DATE(qualificado_em) as dia, COUNT(*) as total', false)
                    ->where('qualificado_em IS NOT NULL', null, false)
                    ->where('DATE(qualificado_em) >=', $inicio)
                    ->where('DATE(qualificado_em) <=', $fim)
                    ->groupBy('DATE(qualificado_em)', false)
                    ->orderBy('dia', 'ASC');
                $applyContatoFilters($seriesLeadsQualificadosBuilder);
                $seriesLeadsQualificadosRows = $seriesLeadsQualificadosBuilder->get()->getResultArray();
            }

            if ($hasConvertidoEm) {
                $seriesLeadsConvertidosBuilder = $db->table('contatos')
                    ->select('DATE(convertido_em) as dia, COUNT(*) as total', false)
                    ->where('convertido_em IS NOT NULL', null, false)
                    ->where('DATE(convertido_em) >=', $inicio)
                    ->where('DATE(convertido_em) <=', $fim)
                    ->groupBy('DATE(convertido_em)', false)
                    ->orderBy('dia', 'ASC');
                $applyContatoFilters($seriesLeadsConvertidosBuilder);
                $seriesLeadsConvertidosRows = $seriesLeadsConvertidosBuilder->get()->getResultArray();
            }
        }

        if ($conversasTableExists) {
            $conversasIniciadasBuilder = $db->table('conversas_whatsapp')
                ->where('DATE(created_at) >=', $inicio)
                ->where('DATE(created_at) <=', $fim);
            $applyConversaFilters($conversasIniciadasBuilder);
            $cards['conversas_iniciadas'] = (int) $conversasIniciadasBuilder->countAllResults();

            $conversasAtivasBuilder = $db->table('conversas_whatsapp')
                ->whereIn('status', ['aberta', 'aguardando']);
            $applyConversaFilters($conversasAtivasBuilder);
            $cards['conversas_ativas'] = (int) $conversasAtivasBuilder->countAllResults();

            if ($contatosTableExists) {
                $conversasNovosBuilder = $db->table('conversas_whatsapp')
                    ->join('contatos', 'contatos.id = conversas_whatsapp.contato_id', 'left')
                    ->where('conversas_whatsapp.cliente_id IS NULL', null, false)
                    ->where('contatos.cliente_id IS NULL', null, false);
                $applyConversaFilters($conversasNovosBuilder);
                $cards['conversas_clientes_novos'] = (int) $conversasNovosBuilder->countAllResults();
            } else {
                $conversasNovosBuilder = $db->table('conversas_whatsapp')
                    ->where('cliente_id IS NULL', null, false);
                $applyConversaFilters($conversasNovosBuilder);
                $cards['conversas_clientes_novos'] = (int) $conversasNovosBuilder->countAllResults();
            }

            $seriesConversasBuilder = $db->table('conversas_whatsapp')
                ->select('DATE(created_at) as dia, COUNT(*) as total', false)
                ->where('DATE(created_at) >=', $inicio)
                ->where('DATE(created_at) <=', $fim)
                ->groupBy('DATE(created_at)', false)
                ->orderBy('dia', 'ASC');
            $applyConversaFilters($seriesConversasBuilder);
            $seriesConversasRows = $seriesConversasBuilder->get()->getResultArray();

            if ($hasConversaCanal) {
                $canalStatsBuilder = $db->table('conversas_whatsapp')
                    ->select("COALESCE(NULLIF(canal, ''), 'nao_informado') as canal, COUNT(*) as total, SUM(CASE WHEN status = 'resolvida' THEN 1 ELSE 0 END) as total_resolvidas", false)
                    ->where('DATE(created_at) >=', $inicio)
                    ->where('DATE(created_at) <=', $fim)
                    ->groupBy("COALESCE(NULLIF(canal, ''), 'nao_informado')", false)
                    ->orderBy('total', 'DESC');
                $applyConversaFilters($canalStatsBuilder);
                $canalStatsRows = $canalStatsBuilder->get()->getResultArray();
                foreach ($canalStatsRows as $canalStatsRow) {
                    $canalTotal = (int) ($canalStatsRow['total'] ?? 0);
                    $canalResolvidas = (int) ($canalStatsRow['total_resolvidas'] ?? 0);
                    $canalStats[] = [
                        'canal' => (string) ($canalStatsRow['canal'] ?? 'nao_informado'),
                        'total' => $canalTotal,
                        'resolvidas' => $canalResolvidas,
                        'taxa_resolucao' => $canalTotal > 0 ? round(($canalResolvidas / $canalTotal) * 100, 1) : 0.0,
                    ];
                }
            }

            $rankingAtendimentoBuilder = $db->table('conversas_whatsapp cw')
                ->select("cw.responsavel_id, COALESCE(NULLIF(u.nome, ''), 'Nao atribuido') as responsavel_nome, COUNT(*) as total_conversas, SUM(CASE WHEN cw.status = 'resolvida' THEN 1 ELSE 0 END) as total_resolvidas, SUM(CASE WHEN cw.nao_lidas > 0 THEN 1 ELSE 0 END) as total_pendencias", false)
                ->join('usuarios u', 'u.id = cw.responsavel_id', 'left')
                ->where('DATE(cw.created_at) >=', $inicio)
                ->where('DATE(cw.created_at) <=', $fim)
                ->groupBy('cw.responsavel_id, u.nome')
                ->orderBy('total_conversas', 'DESC')
                ->limit(8);
            $applyConversaFilters($rankingAtendimentoBuilder, 'cw');
            $rankingAtendimentoRows = $rankingAtendimentoBuilder->get()->getResultArray();
            foreach ($rankingAtendimentoRows as $rankingAtendimentoRow) {
                $totalConversas = (int) ($rankingAtendimentoRow['total_conversas'] ?? 0);
                $totalResolvidas = (int) ($rankingAtendimentoRow['total_resolvidas'] ?? 0);
                $rankingAtendimento[] = [
                    'responsavel_nome' => (string) ($rankingAtendimentoRow['responsavel_nome'] ?? 'Nao atribuido'),
                    'total_conversas' => $totalConversas,
                    'total_resolvidas' => $totalResolvidas,
                    'total_pendencias' => (int) ($rankingAtendimentoRow['total_pendencias'] ?? 0),
                    'taxa_resolucao' => $totalConversas > 0 ? round(($totalResolvidas / $totalConversas) * 100, 1) : 0.0,
                ];
            }
        }

        if ($mensagensTableExists) {
            $inboundBuilder = $db->table('mensagens_whatsapp m')
                ->where('m.direcao', 'inbound')
                ->where('DATE(m.created_at) >=', $inicio)
                ->where('DATE(m.created_at) <=', $fim);
            if ($conversasTableExists) {
                $inboundBuilder->join('conversas_whatsapp cw', 'cw.id = m.conversa_id', 'left');
                $applyConversaFilters($inboundBuilder, 'cw');
            }
            $cards['mensagens_inbound'] = (int) $inboundBuilder->countAllResults();

            $outboundBuilder = $db->table('mensagens_whatsapp m')
                ->where('m.direcao', 'outbound')
                ->where('DATE(m.created_at) >=', $inicio)
                ->where('DATE(m.created_at) <=', $fim);
            if ($conversasTableExists) {
                $outboundBuilder->join('conversas_whatsapp cw', 'cw.id = m.conversa_id', 'left');
                $applyConversaFilters($outboundBuilder, 'cw');
            }
            $cards['mensagens_outbound'] = (int) $outboundBuilder->countAllResults();
            $cards['mensagens_total'] = $cards['mensagens_inbound'] + $cards['mensagens_outbound'];

            $firstReplyBuilder = $db->table('mensagens_whatsapp m')
                ->select('m.conversa_id, MIN(CASE WHEN m.direcao = "inbound" THEN m.created_at END) AS primeira_inbound, MIN(CASE WHEN m.direcao = "outbound" THEN m.created_at END) AS primeira_outbound', false)
                ->where('DATE(m.created_at) >=', $inicio)
                ->where('DATE(m.created_at) <=', $fim)
                ->groupBy('m.conversa_id');
            if ($conversasTableExists) {
                $firstReplyBuilder->join('conversas_whatsapp cw', 'cw.id = m.conversa_id', 'left');
                $applyConversaFilters($firstReplyBuilder, 'cw');
            }
            $firstReplyRows = $firstReplyBuilder->get()->getResultArray();

            $replyPairs = 0;
            $replySeconds = 0.0;
            foreach ($firstReplyRows as $firstReplyRow) {
                $primeiraInbound = strtotime((string) ($firstReplyRow['primeira_inbound'] ?? ''));
                $primeiraOutbound = strtotime((string) ($firstReplyRow['primeira_outbound'] ?? ''));
                if ($primeiraInbound === false || $primeiraOutbound === false) {
                    continue;
                }
                if ($primeiraOutbound < $primeiraInbound) {
                    continue;
                }
                $replySeconds += ($primeiraOutbound - $primeiraInbound);
                $replyPairs++;
            }
            $cards['tempo_primeira_resposta_min'] = $replyPairs > 0
                ? round(($replySeconds / $replyPairs) / 60, 1)
                : null;
        }

        if ($db->tableExists('conversa_os') && $db->tableExists('os')) {
            $osBuilder = $db->table('conversa_os co')
                ->select('COUNT(DISTINCT co.os_id) as total', false)
                ->join('os', 'os.id = co.os_id', 'inner')
                ->where('DATE(os.data_abertura) >=', $inicio)
                ->where('DATE(os.data_abertura) <=', $fim);
            if ($conversasTableExists) {
                $osBuilder->join('conversas_whatsapp cw', 'cw.id = co.conversa_id', 'left');
                $applyConversaFilters($osBuilder, 'cw');
            }
            $osRows = $osBuilder->get()->getRowArray();
            $cards['os_origem_whatsapp'] = (int) ($osRows['total'] ?? 0);
        }

        if ($db->tableExists('crm_tags') && $db->tableExists('crm_tags_cliente')) {
            $tagStats = $db->table('crm_tags t')
                ->select('t.id, t.nome, t.cor, COUNT(tc.id) as total_clientes')
                ->join('crm_tags_cliente tc', 'tc.tag_id = t.id', 'left')
                ->groupBy('t.id, t.nome, t.cor')
                ->orderBy('total_clientes', 'DESC')
                ->get()
                ->getResultArray();
        }

        $cards['taxa_qualificacao'] = $cards['leads_captados'] > 0
            ? round(($cards['leads_qualificados'] / $cards['leads_captados']) * 100, 1)
            : 0.0;
        $cards['taxa_conversao'] = $cards['leads_qualificados'] > 0
            ? round(($cards['leads_convertidos'] / $cards['leads_qualificados']) * 100, 1)
            : 0.0;
        $cards['taxa_conversao_captados'] = $cards['leads_captados'] > 0
            ? round(($cards['leads_convertidos'] / $cards['leads_captados']) * 100, 1)
            : 0.0;

        [$seriesLabels, $seriesLeads] = $this->buildDailySeries($inicio, $fim, $seriesLeadsRows);
        [, $seriesLeadsQualificados] = $this->buildDailySeries($inicio, $fim, $seriesLeadsQualificadosRows);
        [, $seriesLeadsConvertidos] = $this->buildDailySeries($inicio, $fim, $seriesLeadsConvertidosRows);
        [, $seriesConversas] = $this->buildDailySeries($inicio, $fim, $seriesConversasRows);

        $kpiDeltas = [
            'leads_captados' => $this->calculateSeriesDeltaPercent($seriesLeads, 7),
            'leads_qualificados' => $this->calculateSeriesDeltaPercent($seriesLeadsQualificados, 7),
            'leads_convertidos' => $this->calculateSeriesDeltaPercent($seriesLeadsConvertidos, 7),
            'conversas_iniciadas' => $this->calculateSeriesDeltaPercent($seriesConversas, 7),
            'taxa_conversao' => null,
        ];

        $qualificadosRecent = $this->sumSeriesWindow($seriesLeadsQualificados, 7, true);
        $qualificadosPrevious = $this->sumSeriesWindow($seriesLeadsQualificados, 7, false);
        $convertidosRecent = $this->sumSeriesWindow($seriesLeadsConvertidos, 7, true);
        $convertidosPrevious = $this->sumSeriesWindow($seriesLeadsConvertidos, 7, false);
        if ($qualificadosRecent !== null && $qualificadosPrevious !== null) {
            $taxaRecente = $qualificadosRecent > 0 ? ($convertidosRecent / max(1, $qualificadosRecent)) * 100 : 0.0;
            $taxaAnterior = $qualificadosPrevious > 0 ? ($convertidosPrevious / max(1, $qualificadosPrevious)) * 100 : 0.0;
            $kpiDeltas['taxa_conversao'] = round($taxaRecente - $taxaAnterior, 1);
        }

        foreach ($seriesLabels as $idx => $serieLabel) {
            $serieResumoRows[] = [
                'dia' => $serieLabel,
                'captados' => (int) ($seriesLeads[$idx] ?? 0),
                'qualificados' => (int) ($seriesLeadsQualificados[$idx] ?? 0),
                'convertidos' => (int) ($seriesLeadsConvertidos[$idx] ?? 0),
                'conversas' => (int) ($seriesConversas[$idx] ?? 0),
            ];
        }

        $insights = $this->buildMarketingInsights(
            $seriesLabels,
            $seriesLeads,
            $seriesLeadsQualificados,
            $seriesLeadsConvertidos,
            $seriesConversas,
            $cards,
            $origens,
            $canalStats
        );

        return view('crm/metricas_marketing', [
            'title' => 'CRM - Metricas Marketing',
            'inicio' => $inicio,
            'fim' => $fim,
            'periodo' => $periodo,
            'canal' => $canal,
            'responsavel_id' => $responsavelId,
            'status' => $statusConversa,
            'tag_id' => $tagId,
            'canalOptions' => $canalOptions,
            'responsavelOptions' => $responsavelOptions,
            'statusOptions' => $statusOptions,
            'tagOptions' => $tagOptions,
            'periodoAtivoDias' => $periodoAtivoDias,
            'periodoRiscoDias' => $periodoRiscoDias,
            'supportsEngajamento' => $supportsEngajamento,
            'cards' => $cards,
            'kpiDeltas' => $kpiDeltas,
            'insights' => $insights,
            'origens' => $origens,
            'canalStats' => $canalStats,
            'rankingAtendimento' => $rankingAtendimento,
            'tagStats' => $tagStats,
            'seriesLabels' => $seriesLabels,
            'seriesLeads' => $seriesLeads,
            'seriesLeadsQualificados' => $seriesLeadsQualificados,
            'seriesLeadsConvertidos' => $seriesLeadsConvertidos,
            'seriesConversas' => $seriesConversas,
            'serieResumoRows' => $serieResumoRows,
        ]);
    }

    public function salvarEngajamentoPeriodos()
    {
        requirePermission('clientes', 'editar');

        $ativoDiasInput = (int) ($this->request->getPost('engajamento_ativo_dias') ?? 0);
        $riscoDiasInput = (int) ($this->request->getPost('engajamento_risco_dias') ?? 0);
        [$ativoDias, $riscoDias] = ContatoModel::normalizeEngajamentoPeriodos($ativoDiasInput, $riscoDiasInput);

        $configModel = new ConfiguracaoModel();
        $okAtivo = $configModel->setConfig('crm_engajamento_ativo_dias', (string) $ativoDias, 'numero');
        $okRisco = $configModel->setConfig('crm_engajamento_risco_dias', (string) $riscoDias, 'numero');

        if ($okAtivo === false || $okRisco === false) {
            return redirect()->back()->withInput()->with('error', 'Nao foi possivel salvar os periodos de engajamento.');
        }

        $contatoModel = new ContatoModel();
        if ($contatoModel->db->tableExists('contatos') && $contatoModel->supportsEngajamentoFields()) {
            try {
                $contatoModel->recalculateEngajamentoBulk($ativoDias, $riscoDias);
            } catch (\Throwable $e) {
                log_message('warning', 'CRM salvar engajamento: falha ao recalcular contatos: ' . $e->getMessage());
            }
        }

        $inicio = trim((string) $this->request->getPost('inicio'));
        $fim = trim((string) $this->request->getPost('fim'));
        $periodo = strtolower(trim((string) $this->request->getPost('periodo')));
        $canal = strtolower(trim((string) $this->request->getPost('canal')));
        $responsavelId = (int) ($this->request->getPost('responsavel_id') ?? 0);
        $statusConversa = strtolower(trim((string) $this->request->getPost('status')));
        $tagId = (int) ($this->request->getPost('tag_id') ?? 0);
        $query = [];
        if ($periodo !== '' && in_array($periodo, ['hoje', '7d', '30d', '90d', 'mes_atual', 'mes_anterior', 'custom'], true)) {
            $query['periodo'] = $periodo;
        }
        if ($this->isDateYmd($inicio)) {
            $query['inicio'] = $inicio;
        }
        if ($this->isDateYmd($fim)) {
            $query['fim'] = $fim;
        }
        if ($canal !== '') {
            $query['canal'] = $canal;
        }
        if ($responsavelId > 0) {
            $query['responsavel_id'] = $responsavelId;
        }
        if ($statusConversa !== '') {
            $query['status'] = $statusConversa;
        }
        if ($tagId > 0) {
            $query['tag_id'] = $tagId;
        }

        $redirectUrl = '/crm/metricas-marketing';
        if (!empty($query)) {
            $redirectUrl .= '?' . http_build_query($query);
        }

        return redirect()->to($redirectUrl)->with(
            'success',
            'Periodos de engajamento salvos com sucesso. Ativo ate ' . $ativoDias . ' dias e em risco ate ' . $riscoDias . ' dias.'
        );
    }

    public function clientesInativos()
    {
        $dias = (int) ($this->request->getGet('dias') ?? 180);
        if ($dias < 30) {
            $dias = 30;
        }

        $clientes = [];
        $db = \Config\Database::connect();
        if ($db->tableExists('clientes') && $db->tableExists('os')) {
            $clientes = $db->table('clientes c')
                ->select('
                    c.id,
                    c.nome_razao,
                    c.telefone1,
                    c.email,
                    MAX(os.data_abertura) as ultima_os_em,
                    COUNT(os.id) as total_os
                ')
                ->join('os', 'os.cliente_id = c.id', 'left')
                ->groupBy('c.id, c.nome_razao, c.telefone1, c.email')
                ->having('(MAX(os.data_abertura) IS NULL OR MAX(os.data_abertura) < DATE_SUB(NOW(), INTERVAL ' . $dias . ' DAY))')
                ->orderBy('ultima_os_em', 'ASC')
                ->get()
                ->getResultArray();
        }

        return view('crm/clientes_inativos', [
            'title' => 'CRM - Clientes Inativos',
            'dias' => $dias,
            'clientes' => $clientes,
        ]);
    }

    public function criarFollowupInativo()
    {
        $clienteId = (int) ($this->request->getPost('cliente_id') ?? 0);
        $dias = (int) ($this->request->getPost('dias') ?? 180);
        if ($clienteId <= 0) {
            return redirect()->back()->with('error', 'Cliente obrigatorio para criar follow-up de reativacao.');
        }

        $cliente = (new ClienteModel())->find($clienteId);
        if (!$cliente) {
            return redirect()->back()->with('error', 'Cliente nao encontrado para follow-up.');
        }

        (new CrmService())->createFollowup([
            'cliente_id' => $clienteId,
            'os_id' => null,
            'titulo' => 'Reativacao de cliente inativo',
            'descricao' => 'Cliente sem OS ha mais de ' . $dias . ' dias. Retomar contato comercial.',
            'data_prevista' => date('Y-m-d H:i:s', strtotime('+1 day')),
            'status' => 'pendente',
            'usuario_responsavel' => session()->get('user_id') ?: null,
            'origem_evento' => 'cliente_inativo_' . $dias . 'd',
        ]);

        (new CrmService())->registerEvent([
            'cliente_id' => $clienteId,
            'tipo_evento' => 'cliente_inativo_followup',
            'titulo' => 'Follow-up de reativacao criado',
            'descricao' => 'Follow-up gerado para cliente inativo.',
            'origem' => 'crm',
            'usuario_id' => session()->get('user_id') ?: null,
            'data_evento' => date('Y-m-d H:i:s'),
            'payload_json' => [
                'dias' => $dias,
            ],
        ]);

        return redirect()->to('/crm/clientes-inativos?dias=' . $dias)->with('success', 'Follow-up de reativacao criado com sucesso.');
    }

    private function listDistinctValues(string $table, string $field): array
    {
        if (!(new CrmEventoModel())->db->tableExists($table)) {
            return [];
        }
        return array_column(
            (new CrmEventoModel())->db->table($table)
                ->select($field)
                ->where("{$field} IS NOT NULL", null, false)
                ->where("{$field} !=", '')
                ->groupBy($field)
                ->orderBy($field, 'ASC')
                ->get()
                ->getResultArray(),
            $field
        );
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array{0: array<int,string>, 1: array<int,int>}
     */
    private function buildDailySeries(string $inicio, string $fim, array $rows): array
    {
        $labels = [];
        $values = [];
        $indexByDay = [];

        $cursor = strtotime($inicio . ' 00:00:00');
        $fimTs = strtotime($fim . ' 00:00:00');
        if ($cursor === false || $fimTs === false) {
            return [[], []];
        }

        while ($cursor <= $fimTs) {
            $dayKey = date('Y-m-d', $cursor);
            $indexByDay[$dayKey] = count($values);
            $labels[] = date('d/m', $cursor);
            $values[] = 0;
            $cursor = strtotime('+1 day', $cursor);
        }

        foreach ($rows as $row) {
            $day = (string) ($row['dia'] ?? '');
            if ($day === '' || !array_key_exists($day, $indexByDay)) {
                continue;
            }
            $idx = $indexByDay[$day];
            $values[$idx] = (int) ($row['total'] ?? 0);
        }

        return [$labels, $values];
    }

    /**
     * @return array{0:string,1:string,2:string}
     */
    private function resolveMarketingPeriodo(string $periodo, string $inicioInput, string $fimInput): array
    {
        $periodoNormalizado = strtolower(trim($periodo));
        $allowed = ['hoje', '7d', '30d', '90d', 'mes_atual', 'mes_anterior', 'custom'];
        if (!in_array($periodoNormalizado, $allowed, true)) {
            $periodoNormalizado = '30d';
        }

        $hoje = date('Y-m-d');
        $inicio = $inicioInput;
        $fim = $fimInput;

        switch ($periodoNormalizado) {
            case 'hoje':
                $inicio = $hoje;
                $fim = $hoje;
                break;
            case '7d':
                $inicio = date('Y-m-d', strtotime('-6 days'));
                $fim = $hoje;
                break;
            case '90d':
                $inicio = date('Y-m-d', strtotime('-89 days'));
                $fim = $hoje;
                break;
            case 'mes_atual':
                $inicio = date('Y-m-01');
                $fim = $hoje;
                break;
            case 'mes_anterior':
                $inicio = date('Y-m-01', strtotime('first day of last month'));
                $fim = date('Y-m-t', strtotime('last day of last month'));
                break;
            case 'custom':
                if (!$this->isDateYmd($inicio) || !$this->isDateYmd($fim)) {
                    $periodoNormalizado = '30d';
                    $inicio = date('Y-m-d', strtotime('-29 days'));
                    $fim = $hoje;
                }
                break;
            case '30d':
            default:
                $inicio = date('Y-m-d', strtotime('-29 days'));
                $fim = $hoje;
                break;
        }

        if (!$this->isDateYmd($inicio)) {
            $inicio = date('Y-m-d', strtotime('-29 days'));
        }
        if (!$this->isDateYmd($fim)) {
            $fim = $hoje;
        }
        if ($fim < $inicio) {
            $tmp = $inicio;
            $inicio = $fim;
            $fim = $tmp;
        }

        return [$periodoNormalizado, $inicio, $fim];
    }

    private function calculateSeriesDeltaPercent(array $series, int $window = 7): ?float
    {
        if ($window <= 0 || count($series) < ($window * 2)) {
            return null;
        }

        $recent = array_slice($series, -$window);
        $previous = array_slice($series, -($window * 2), $window);
        $recentTotal = array_sum(array_map('intval', $recent));
        $previousTotal = array_sum(array_map('intval', $previous));

        if ($previousTotal <= 0) {
            if ($recentTotal <= 0) {
                return 0.0;
            }
            return 100.0;
        }

        return round((($recentTotal - $previousTotal) / $previousTotal) * 100, 1);
    }

    private function sumSeriesWindow(array $series, int $window, bool $recent): ?int
    {
        if ($window <= 0 || count($series) < ($window * 2)) {
            return null;
        }
        $slice = $recent
            ? array_slice($series, -$window)
            : array_slice($series, -($window * 2), $window);
        return array_sum(array_map('intval', $slice));
    }

    /**
     * @param array<int,string> $seriesLabels
     * @param array<int,int> $seriesLeads
     * @param array<int,int> $seriesLeadsQualificados
     * @param array<int,int> $seriesLeadsConvertidos
     * @param array<int,int> $seriesConversas
     * @param array<string,mixed> $cards
     * @param array<int,array<string,mixed>> $origens
     * @param array<int,array<string,mixed>> $canalStats
     * @return array<int,array<string,string>>
     */
    private function buildMarketingInsights(
        array $seriesLabels,
        array $seriesLeads,
        array $seriesLeadsQualificados,
        array $seriesLeadsConvertidos,
        array $seriesConversas,
        array $cards,
        array $origens,
        array $canalStats
    ): array {
        $insights = [];

        $deltaConversoes = $this->calculateSeriesDeltaPercent($seriesLeadsConvertidos, 7);
        if ($deltaConversoes !== null) {
            if ($deltaConversoes >= 10) {
                $insights[] = [
                    'tipo' => 'success',
                    'titulo' => 'Conversao em alta',
                    'descricao' => 'Conversoes cresceram ' . number_format($deltaConversoes, 1, ',', '.') . '% nos ultimos 7 dias vs 7 anteriores.',
                ];
            } elseif ($deltaConversoes <= -10) {
                $insights[] = [
                    'tipo' => 'warning',
                    'titulo' => 'Queda de conversao',
                    'descricao' => 'Conversoes recuaram ' . number_format(abs($deltaConversoes), 1, ',', '.') . '% nos ultimos 7 dias. Revisar follow-up e abordagem comercial.',
                ];
            }
        }

        $captadosRecent = $this->sumSeriesWindow($seriesLeads, 7, true);
        $captadosPrevious = $this->sumSeriesWindow($seriesLeads, 7, false);
        $qualificadosRecent = $this->sumSeriesWindow($seriesLeadsQualificados, 7, true);
        $qualificadosPrevious = $this->sumSeriesWindow($seriesLeadsQualificados, 7, false);
        if ($captadosRecent !== null && $captadosPrevious !== null && $captadosPrevious > 0) {
            $taxaQualificacaoRecente = $captadosRecent > 0 ? ($qualificadosRecent / max(1, $captadosRecent)) * 100 : 0.0;
            $taxaQualificacaoAnterior = $captadosPrevious > 0 ? ($qualificadosPrevious / max(1, $captadosPrevious)) * 100 : 0.0;
            $deltaTaxaQualificacao = round($taxaQualificacaoRecente - $taxaQualificacaoAnterior, 1);
            if ($deltaTaxaQualificacao <= -8) {
                $insights[] = [
                    'tipo' => 'warning',
                    'titulo' => 'Qualificacao caiu',
                    'descricao' => 'Taxa de qualificacao recuou ' . number_format(abs($deltaTaxaQualificacao), 1, ',', '.') . ' p.p. nos ultimos 7 dias.',
                ];
            } elseif ($deltaTaxaQualificacao >= 8) {
                $insights[] = [
                    'tipo' => 'success',
                    'titulo' => 'Qualificacao melhorou',
                    'descricao' => 'Taxa de qualificacao subiu ' . number_format($deltaTaxaQualificacao, 1, ',', '.') . ' p.p. nos ultimos 7 dias.',
                ];
            }
        }

        if (!empty($seriesLabels)) {
            $maxConversas = !empty($seriesConversas) ? max($seriesConversas) : 0;
            if ($maxConversas > 0) {
                $melhorDiaIdx = array_search($maxConversas, $seriesConversas, true);
                if ($melhorDiaIdx !== false && isset($seriesLabels[$melhorDiaIdx])) {
                    $insights[] = [
                        'tipo' => 'info',
                        'titulo' => 'Melhor dia de volume',
                        'descricao' => 'Pico de conversas em ' . $seriesLabels[$melhorDiaIdx] . ' com ' . $maxConversas . ' conversas.',
                    ];
                }
            }
        }

        if (!empty($canalStats)) {
            $topCanal = $canalStats[0];
            $insights[] = [
                'tipo' => 'info',
                'titulo' => 'Canal lider no periodo',
                'descricao' => $this->formatCanalLabel((string) ($topCanal['canal'] ?? 'nao_informado'))
                    . ' concentrou '
                    . (int) ($topCanal['total'] ?? 0)
                    . ' conversas e '
                    . number_format((float) ($topCanal['taxa_resolucao'] ?? 0), 1, ',', '.')
                    . '% de resolucao.',
            ];
        } elseif (!empty($origens)) {
            $topOrigem = $origens[0];
            $insights[] = [
                'tipo' => 'info',
                'titulo' => 'Origem mais forte',
                'descricao' => $this->formatCanalLabel((string) ($topOrigem['origem'] ?? 'nao_informada'))
                    . ' liderou captacao com '
                    . (int) ($topOrigem['total'] ?? 0)
                    . ' contatos.',
            ];
        }

        $tempoPrimeiraResposta = $cards['tempo_primeira_resposta_min'] ?? null;
        if ($tempoPrimeiraResposta !== null) {
            $tempoPrimeiraResposta = (float) $tempoPrimeiraResposta;
            if ($tempoPrimeiraResposta > 30) {
                $insights[] = [
                    'tipo' => 'warning',
                    'titulo' => 'Tempo de resposta elevado',
                    'descricao' => 'Primeira resposta media em ' . number_format($tempoPrimeiraResposta, 1, ',', '.') . ' min. Avalie fila e distribuicao.',
                ];
            } elseif ($tempoPrimeiraResposta <= 10) {
                $insights[] = [
                    'tipo' => 'success',
                    'titulo' => 'Boa velocidade de resposta',
                    'descricao' => 'Primeira resposta media em ' . number_format($tempoPrimeiraResposta, 1, ',', '.') . ' min.',
                ];
            }
        }

        return array_slice($insights, 0, 5);
    }

    private function formatCanalLabel(string $value): string
    {
        return ucfirst(str_replace('_', ' ', trim($value) !== '' ? $value : 'nao_informado'));
    }

    private function isDateYmd(string $value): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return false;
        }
        $dt = \DateTime::createFromFormat('Y-m-d', $value);
        return $dt !== false && $dt->format('Y-m-d') === $value;
    }

    private function syncInbound(): void
    {
        try {
            (new CentralMensagensService())->syncInboundQueue(80);
        } catch (\Throwable $e) {
            log_message('warning', 'CRM sync inbound falhou: ' . $e->getMessage());
        }
    }

    /**
     * @return array{0:int,1:int}
     */
    private function getEngajamentoPeriodos(): array
    {
        $configModel = new ConfiguracaoModel();
        $ativoDias = (int) $configModel->get('crm_engajamento_ativo_dias', '30');
        $riscoDias = (int) $configModel->get('crm_engajamento_risco_dias', '90');
        return ContatoModel::normalizeEngajamentoPeriodos($ativoDias, $riscoDias);
    }
}
