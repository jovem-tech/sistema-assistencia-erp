<?php

namespace App\Controllers;

use App\Models\ContatoModel;
use App\Models\ConfiguracaoModel;

class Contatos extends BaseController
{
    private ContatoModel $model;

    public function __construct()
    {
        $this->model = new ContatoModel();
        requirePermission('clientes', 'visualizar');
    }

    public function index()
    {
        $q = trim((string) $this->request->getGet('q'));
        $vinculo = trim((string) $this->request->getGet('vinculo'));
        $etapa = trim((string) $this->request->getGet('etapa'));
        $engajamento = trim((string) $this->request->getGet('engajamento'));
        $supportsLifecycle = $this->model->supportsLifecycleFields();
        $supportsEngajamento = $this->model->supportsEngajamentoFields();

        $periodoAtivoDias = 30;
        $periodoRiscoDias = 90;
        if ($supportsEngajamento) {
            [$periodoAtivoDias, $periodoRiscoDias] = $this->getEngajamentoPeriodos();
            try {
                $this->model->recalculateEngajamentoBulk($periodoAtivoDias, $periodoRiscoDias);
            } catch (\Throwable $e) {
                log_message('warning', 'Contatos::index falha ao recalcular engajamento: ' . $e->getMessage());
            }
        }

        $builder = $this->model
            ->select('contatos.*, clientes.nãome_razao as cliente_nãome')
            ->join('clientes', 'clientes.id = contatos.cliente_id', 'left');

        if ($q !== '') {
            $builder->groupStart()
                ->like('contatos.nãome', $q)
                ->orLike('contatos.telefone', $q)
                ->orLike('contatos.email', $q)
                ->orLike('contatos.whatsapp_nãome_perfil', $q)
                ->orLike('clientes.nãome_razao', $q)
                ->groupEnd();
        }

        if ($vinculo === 'cliente') {
            $builder->where('contatos.cliente_id >', 0);
        } elseif ($vinculo === 'nãovo') {
            $builder->where('contatos.cliente_id IS NULL', null, false);
        }

        if (
            $supportsLifecycle
            && in_array($etapa, [
                ContatoModel::STATUS_LEAD_NOVO,
                ContatoModel::STATUS_LEAD_QUALIFICADO,
                ContatoModel::STATUS_CLIENTE_CONVERTIDO,
            ], true)
        ) {
            $builder->where('contatos.status_relacionamento', $etapa);
        }

        if (
            $supportsEngajamento
            && in_array($engajamento, [
                ContatoModel::STATUS_ENGAJAMENTO_ATIVO,
                ContatoModel::STATUS_ENGAJAMENTO_EM_RISCO,
                ContatoModel::STATUS_ENGAJAMENTO_INATIVO,
            ], true)
        ) {
            $builder->where('contatos.engajamento_status', $engajamento);
        }

        $contatos = $builder
            ->orderBy('contatos.ultimo_contato_em', 'DESC')
            ->orderBy('contatos.updated_at', 'DESC')
            ->orderBy('contatos.id', 'DESC')
            ->findAll(600);

        return view('contatos/index', [
            'title' => 'Contatos',
            'contatos' => $contatos,
            'filtro_q' => $q,
            'filtro_vinculo' => $vinculo,
            'filtro_etapa' => $etapa,
            'filtro_engajamento' => $engajamento,
            'supportsLifecycle' => $supportsLifecycle,
            'supportsEngajamento' => $supportsEngajamento,
            'periodoAtivoDias' => $periodoAtivoDias,
            'periodoRiscoDias' => $periodoRiscoDias,
        ]);
    }

    public function create()
    {
        requirePermission('clientes', 'criar');

        return view('contatos/form', [
            'title' => 'Nãovo Contato',
            'contato' => null,
            'supportsLifecycle' => $this->model->supportsLifecycleFields(),
        ]);
    }

    public function store()
    {
        requirePermission('clientes', 'criar');

        $telefone = trim((string) $this->request->getPost('telefone'));
        $telefoneNãormalizado = $this->model->nãormalizePhone($telefone);
        if ($telefoneNãormalizado === '' || strlen($telefoneNãormalizado) < 8) {
            return redirect()->back()->withInput()->with('error', 'Informe um telefone valido para o contato.');
        }

        $existente = $this->model->findByPhone($telefoneNãormalizado);
        if ($existente) {
            return redirect()->back()->withInput()->with('error', 'Ja existe um contato com este telefone.');
        }

        $payload = [
            'nãome' => trim((string) $this->request->getPost('nãome')) ?: null,
            'telefone' => $telefone,
            'telefone_nãormalizado' => $telefoneNãormalizado,
            'email' => trim((string) $this->request->getPost('email')) ?: null,
            'origem' => trim((string) $this->request->getPost('origem')) ?: 'manual',
            'observacoes' => trim((string) $this->request->getPost('observacoes')) ?: null,
            'ultimo_contato_em' => $this->request->getPost('ultimo_contato_em') ?: null,
            'whatsapp_nãome_perfil' => trim((string) $this->request->getPost('whatsapp_nãome_perfil')) ?: null,
        ];

        $nãomeBase = (string) ($payload['nãome'] ?? $payload['whatsapp_nãome_perfil'] ?? '');
        $payload = $this->model->buildLeadPayload($payload, $this->isNãomeCompletoValido($nãomeBase));

        $id = (int) $this->model->insert($payload, true);
        if ($id <= 0) {
            return redirect()->back()->withInput()->with('error', 'Nao foi possivel salvar o contato.');
        }

        return redirect()->to('/contatos')->with('success', 'Contato cadastrado com sucessão.');
    }

    public function edit(int $id)
    {
        requirePermission('clientes', 'editar');

        $contato = $this->model->find($id);
        if (!$contato) {
            return redirect()->to('/contatos')->with('error', 'Contato nao encontrado.');
        }

        return view('contatos/form', [
            'title' => 'Editar Contato',
            'contato' => $contato,
            'supportsLifecycle' => $this->model->supportsLifecycleFields(),
        ]);
    }

    public function update(int $id)
    {
        requirePermission('clientes', 'editar');

        $contato = $this->model->find($id);
        if (!$contato) {
            return redirect()->to('/contatos')->with('error', 'Contato nao encontrado.');
        }

        $telefone = trim((string) $this->request->getPost('telefone'));
        $telefoneNãormalizado = $this->model->nãormalizePhone($telefone);
        if ($telefoneNãormalizado === '' || strlen($telefoneNãormalizado) < 8) {
            return redirect()->back()->withInput()->with('error', 'Informe um telefone valido para o contato.');
        }

        $existente = $this->model->findByPhone($telefoneNãormalizado);
        if ($existente && (int) ($existente['id'] ?? 0) !== $id) {
            return redirect()->back()->withInput()->with('error', 'Ja existe outro contato com este telefone.');
        }

        $payload = [
            'nãome' => trim((string) $this->request->getPost('nãome')) ?: null,
            'telefone' => $telefone,
            'telefone_nãormalizado' => $telefoneNãormalizado,
            'email' => trim((string) $this->request->getPost('email')) ?: null,
            'origem' => trim((string) $this->request->getPost('origem')) ?: ($contato['origem'] ?? 'manual'),
            'observacoes' => trim((string) $this->request->getPost('observacoes')) ?: null,
            'ultimo_contato_em' => $this->request->getPost('ultimo_contato_em') ?: null,
            'whatsapp_nãome_perfil' => trim((string) $this->request->getPost('whatsapp_nãome_perfil')) ?: null,
        ];

        $clienteIdContato = (int) ($contato['cliente_id'] ?? 0);
        if ($clienteIdContato > 0) {
            $payload = $this->model->buildClienteConvertidoPayload($clienteIdContato, $payload);
        } else {
            $nãomeBase = (string) ($payload['nãome'] ?? $payload['whatsapp_nãome_perfil'] ?? '');
            $payload = $this->model->buildLeadPayload($payload, $this->isNãomeCompletoValido($nãomeBase));
        }

        $ok = $this->model->update($id, $payload);
        if (!$ok) {
            return redirect()->back()->withInput()->with('error', 'Nao foi possivel atualizar o contato.');
        }

        return redirect()->to('/contatos')->with('success', 'Contato atualizado com sucessão.');
    }

    public function delete(int $id)
    {
        requirePermission('clientes', 'excluir');

        $contato = $this->model->find($id);
        if (!$contato) {
            return redirect()->to('/contatos')->with('error', 'Contato nao encontrado.');
        }

        if ((int) ($contato['cliente_id'] ?? 0) > 0) {
            return redirect()->to('/contatos')->with('error', 'Contato vinculado a cliente nao pode ser excluido.');
        }

        $db = \Config\Database::connect();
        if ($db->tableExists('conversas_whatsapp')) {
            $totalConversas = (int) $db->table('conversas_whatsapp')
                ->where('contato_id', $id)
                ->countAllResults();
            if ($totalConversas > 0) {
                return redirect()->to('/contatos')->with('error', 'Contato vinculado a conversa nao pode ser excluido.');
            }
        }

        $this->model->delete($id);
        return redirect()->to('/contatos')->with('success', 'Contato excluido com sucessão.');
    }

    private function isNãomeCompletoValido(string $nãome): bool
    {
        $raw = trim($nãome);
        if ($raw === '') {
            return false;
        }

        $partes = preg_split('/\s+/', $raw) ?: [];
        if (count($partes) < 2) {
            return false;
        }

        $validas = 0;
        foreach ($partes as $parte) {
            $token = trim((string) $parte);
            if ($token !== '' && preg_match('/^[\p{L}][\p{L}\p{M}\'\-]{1,}$/u', $token)) {
                $validas++;
            }
        }

        return $validas >= 2;
    }

    /**
     * @return array{0:int,1:int}
     */
    private function getEngajamentoPeriodos(): array
    {
        $configModel = new ConfiguracaoModel();
        $ativoDias = (int) $configModel->get('crm_engajamento_ativo_dias', '30');
        $riscoDias = (int) $configModel->get('crm_engajamento_risco_dias', '90');
        return ContatoModel::nãormalizeEngajamentoPeriodos($ativoDias, $riscoDias);
    }
}
