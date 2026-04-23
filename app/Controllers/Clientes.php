<?php

namespace App\Controllers;

use App\Models\ClienteModel;
use App\Models\ConversaWhatsappModel;
use App\Models\CrmEventoModel;
use App\Models\CrmFollowupModel;
use App\Models\CrmInteracaoModel;
use App\Models\EquipamentoModel;
use App\Models\OsModel;
use App\Models\LogModel;
use App\Services\CnpjLookupService;

class Clientes extends BaseController
{
    protected $model;

    public function __construct()
    {
        $this->model = new ClienteModel();
        requirePermission('clientes');
    }

    public function index()
    {
        $data = [
            'title'    => 'Clientes',
            'clientes' => $this->model->orderBy('nome_razao', 'ASC')->findAll(),
        ];
        return view('clientes/index', $data);
    }

    public function create()
    {
        $data = [
            'title' => 'Novo Cliente',
        ];
        return view('clientes/form', $data);
    }

    public function store()
    {
        $rules = [
            'nome_razao' => 'required|min_length[3]',
            'telefone1'  => 'required',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $dados = $this->normalizeClientePayload((array) $this->request->getPost());
        $this->model->insert($dados);

        LogModel::registrar('cliente_criado', 'Cliente cadastrado: ' . $dados['nome_razao']);

        return redirect()->to('/clientes')
            ->with('success', 'Cliente cadastrado com sucesso!');
    }

    public function edit($id)
    {
        $cliente = $this->model->find($id);
        if (!$cliente) {
            return redirect()->to('/clientes')
                ->with('error', 'Cliente nao encontrado.');
        }

        $data = [
            'title'   => 'Editar Cliente',
            'cliente' => $cliente,
        ];
        return view('clientes/form', $data);
    }

    public function update($id)
    {
        $rules = [
            'nome_razao' => 'required|min_length[3]',
            'telefone1'  => 'required',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $dados = $this->normalizeClientePayload((array) $this->request->getPost());
        $this->model->update($id, $dados);

        LogModel::registrar('cliente_atualizado', 'Cliente atualizado ID: ' . $id);

        return redirect()->to('/clientes')
            ->with('success', 'Cliente atualizado com sucesso!');
    }

    public function delete($id)
    {
        $cliente = $this->model->find($id);
        if ($cliente) {
            $this->model->delete($id);
            LogModel::registrar('cliente_excluido', 'Cliente excluido: ' . $cliente['nome_razao']);
        }

        return redirect()->to('/clientes')
            ->with('success', 'Cliente excluido com sucesso!');
    }

    public function show($id)
    {
        $cliente = $this->model->find($id);
        if (!$cliente) {
            return redirect()->to('/clientes')
                ->with('error', 'Cliente nao encontrado.');
        }

        $isEmbedded = $this->request->getGet('embed') === '1';

        $equipamentoModel = new EquipamentoModel();
        $osModel = new OsModel();

        $crmTimeline = [];
        $crmResumo = [
            'eventos' => 0,
            'interacoes' => 0,
            'followups_pendentes' => 0,
        ];
        $conversasCliente = [];

        $eventoModel = new CrmEventoModel();
        if ($eventoModel->db->tableExists('crm_eventos')) {
            $eventos = $eventoModel->where('cliente_id', $id)->orderBy('data_evento', 'DESC')->findAll(120);
            $crmResumo['eventos'] = count($eventos);
            foreach ($eventos as $e) {
                $crmTimeline[] = [
                    'origem' => 'evento',
                    'titulo' => $e['titulo'] ?? 'Evento CRM',
                    'descricao' => $e['descricao'] ?? null,
                    'canal' => $e['origem'] ?? 'crm',
                    'data' => $e['data_evento'] ?? $e['created_at'] ?? null,
                ];
            }
        }

        $interacaoModel = new CrmInteracaoModel();
        if ($interacaoModel->db->tableExists('crm_interacoes')) {
            $interacoes = $interacaoModel->where('cliente_id', $id)->orderBy('data_interacao', 'DESC')->findAll(120);
            $crmResumo['interacoes'] = count($interacoes);
            foreach ($interacoes as $it) {
                $crmTimeline[] = [
                    'origem' => 'interacao',
                    'titulo' => 'Interacao: ' . ($it['tipo'] ?? 'registro'),
                    'descricao' => $it['descricao'] ?? null,
                    'canal' => $it['canal'] ?? 'crm',
                    'data' => $it['data_interacao'] ?? $it['created_at'] ?? null,
                ];
            }
        }

        $followModel = new CrmFollowupModel();
        if ($followModel->db->tableExists('crm_followups')) {
            $followups = $followModel->where('cliente_id', $id)->orderBy('data_prevista', 'DESC')->findAll(120);
            $crmResumo['followups_pendentes'] = count(array_filter($followups, static fn ($f) => ($f['status'] ?? '') === 'pendente'));
            foreach ($followups as $f) {
                $crmTimeline[] = [
                    'origem' => 'followup',
                    'titulo' => 'Follow-up: ' . ($f['titulo'] ?? 'acompanhamento'),
                    'descricao' => $f['descricao'] ?? null,
                    'canal' => 'follow-up',
                    'data' => $f['data_prevista'] ?? $f['created_at'] ?? null,
                    'status' => $f['status'] ?? null,
                ];
            }
        }

        $conversaModel = new ConversaWhatsappModel();
        if ($conversaModel->db->tableExists('conversas_whatsapp')) {
            $conversasCliente = $conversaModel
                ->where('cliente_id', $id)
                ->orderBy('ultima_mensagem_em', 'DESC')
                ->findAll(20);
        }

        usort($crmTimeline, static function (array $a, array $b): int {
            return strtotime((string) ($b['data'] ?? '1970-01-01')) <=> strtotime((string) ($a['data'] ?? '1970-01-01'));
        });
        $crmTimeline = array_slice($crmTimeline, 0, 150);

        $data = [
            'title'        => 'Detalhes do Cliente',
            'cliente'      => $cliente,
            'equipamentos' => $equipamentoModel->getByCliente($id),
            'ordens'       => $osModel->select('os.*, equipamentos_marcas.nome as equip_marca, equipamentos_modelos.nome as equip_modelo')
                                     ->join('equipamentos', 'equipamentos.id = os.equipamento_id', 'left')
                                     ->join('equipamentos_marcas', 'equipamentos_marcas.id = equipamentos.marca_id', 'left')
                                     ->join('equipamentos_modelos', 'equipamentos_modelos.id = equipamentos.modelo_id', 'left')
                                     ->where('os.cliente_id', $id)
                                     ->orderBy('os.created_at', 'DESC')
                                     ->findAll(),
            'crmTimeline' => $crmTimeline,
            'crmResumo' => $crmResumo,
            'conversasCliente' => $conversasCliente,
            'layout' => $isEmbedded ? 'layouts/embed' : 'layouts/main',
            'isEmbedded' => $isEmbedded,
        ];
        return view('clientes/show', $data);
    }

    public function search()
    {
        $term = $this->request->getGet('q');
        $results = $this->model->search($term);
        return $this->response->setJSON($results);
    }

    public function getJson($id)
    {
        $cliente = $this->model->find($id);
        if (!$cliente) {
            return $this->response->setJSON(['error' => 'Cliente nao encontrado']);
        }
        return $this->response->setJSON($cliente);
    }

    public function consultarCnpj()
    {
        $cnpj = (string) $this->request->getGet('cnpj');
        $service = new CnpjLookupService();
        $result = $service->lookup($cnpj);

        $statusCode = (($result['status'] ?? '') === 'validation_error') ? 422 : 200;

        return $this->response
            ->setStatusCode($statusCode)
            ->setJSON($result);
    }

    public function salvar_ajax()
    {
        $rules = [
            'nome_razao' => 'required|min_length[3]',
            'telefone1'  => 'required',
        ];

        if (!$this->validate($rules)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Verifique se os campos obrigatorios (Nome e Telefone) foram preenchidos corretamente.'
            ]);
        }

        $dados = $this->normalizeClientePayload((array) $this->request->getPost());
        $id = $this->request->getPost('id');
        
        try {
            if (!empty($id)) {
                $this->model->update($id, $dados);
                $clienteAtualizado = $this->model->find($id);
                LogModel::registrar('cliente_atualizado_ajax', 'Cliente atualizado via Ajax ID: ' . $id);
                return $this->response->setJSON([
                    'success' => true,
                    'id'      => $id,
                    'nome'    => $dados['nome_razao'],
                    'cliente' => $clienteAtualizado,
                    'is_update' => true
                ]);
            } else {
                $this->model->insert($dados);
                $insertId = $this->model->getInsertID();
                $clienteCriado = $this->model->find($insertId);
                LogModel::registrar('cliente_criado_ajax', 'Cliente cadastrado via Ajax: ' . $dados['nome_razao']);
                return $this->response->setJSON([
                    'success' => true,
                    'id'      => $insertId,
                    'nome'    => $dados['nome_razao'],
                    'cliente' => $clienteCriado,
                    'is_update' => false
                ]);
            }
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Ocorreu um erro ao salvar (Verifique se o CPF/CNPJ ja existe).'
            ]);
        }
    }

    public function downloadCsvTemplate()
    {
        $filename = 'modelo_importacao_clientes.csv';
        // Define headres
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '";');
        
        // Write to output stream
        $f = fopen('php://output', 'w');
        // utf-8 BOM to Excel correctly render accents
        fputs($f, "\xEF\xBB\xBF");
        
        $fields = [
            'tipo_pessoa', 'nome_razao', 'cpf_cnpj', 'rg_ie', 'email', 
            'telefone1', 'telefone2', 'cep', 'endereco', 'numero', 
            'complemento', 'bairro', 'cidade', 'uf', 'observacoes'
        ];
        fputcsv($f, $fields, ';');
        
        $sampleData = [
            ['fisica', 'Joao da Silva', '111.222.333-44', '12345678', 'joao@email.com', '(11) 99999-8888', '', '01001-000', 'Praca da Se', '1', '', 'Se', 'Sao Paulo', 'SP', 'Cliente de demonstracao importado'],
            ['juridica', 'Empresa Modelo Ltda', '11.222.333/0001-44', '123456789012', 'contato@empresa.com', '(11) 3333-4444', '', '01310-100', 'Avenida Paulista', '1000', 'Andar 1', 'Bela Vista', 'Sao Paulo', 'SP', 'Empresa de demonstracao importada']
        ];
        
        foreach ($sampleData as $row) {
            fputcsv($f, $row, ';');
        }
        
        fclose($f);
        exit;
    }

    public function importCsv()
    {
        $file = $this->request->getFile('arquivo_csv');
        if (!$file || !$file->isValid() || $file->getExtension() !== 'csv') {
            return redirect()->to('/clientes')->with('error', 'Arquivo invalido. Por favor, envie um arquivo CSV.');
        }

        $filepath = $file->getTempName();
        $fileStream = fopen($filepath, 'r');
        if (!$fileStream) {
            return redirect()->to('/clientes')->with('error', 'Nao foi possivel ler o arquivo.');
        }

        // Pula o BOM se existir
        $bom = fread($fileStream, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($fileStream); // Retorna ao inicio se nao for BOM
        }

        // Le a primeira linha (cabecalhos) para identificar o delimitador correto
        $headerLine = fgets($fileStream);
        $delimiter = strpos($headerLine, ';') !== false ? ';' : ',';
        rewind($fileStream); // Volta para ler com o fputcsv agora sabendo o delimitador
        if ($bom === "\xEF\xBB\xBF") {
            fread($fileStream, 3); // Pula o BOM novamente
        }

        $headers = fgetcsv($fileStream, 1000, $delimiter);
        if (!$headers) {
            return redirect()->to('/clientes')->with('error', 'O arquivo CSV esta vazio ou em formato incorreto.');
        }
        
        $expectedHeaders = [
            'tipo_pessoa', 'nome_razao', 'cpf_cnpj', 'rg_ie', 'email', 
            'telefone1', 'telefone2', 'cep', 'endereco', 'numero', 
            'complemento', 'bairro', 'cidade', 'uf', 'observacoes'
        ];
        
        // Remove quaisquer espacos extras dos cabecalhos importados
        $headers = array_map('trim', $headers);

        $importedCount = 0;
        $errorCount = 0;

        while (($row = fgetcsv($fileStream, 1000, $delimiter)) !== false) {
            if (count($row) < 2 || empty(trim($row[1]))) {
                continue; // Pula linhas em branco ou onde o nome nao foi preenchido
            }

            $clienteData = [];
            foreach ($headers as $index => $headerName) {
                if (in_array($headerName, $expectedHeaders) && isset($row[$index])) {
                    $clienteData[$headerName] = trim($row[$index]);
                }
            }

            // Normaliza dados de tipo_pessoa
            if (!empty($clienteData['tipo_pessoa'])) {
                $clienteData['tipo_pessoa'] = strtolower($clienteData['tipo_pessoa']);
                if (!in_array($clienteData['tipo_pessoa'], ['fisica', 'juridica'])) {
                    $clienteData['tipo_pessoa'] = 'fisica';
                }
            } else {
                $clienteData['tipo_pessoa'] = 'fisica';
            }

            // Validacao minima via codigo antes do insert
            if (empty($clienteData['nome_razao']) || empty($clienteData['telefone1'])) {
                $errorCount++;
                continue;
            }

            $clienteData = $this->normalizeClientePayload($clienteData);

            try {
                $this->model->insert($clienteData);
                $importedCount++;
            } catch (\Exception $e) {
                $errorCount++;
            }
        }
        fclose($fileStream);

        LogModel::registrar('cliente_importacao', "Importacao CSV realizada: $importedCount sucessos, $errorCount falhas.");

        $msg = "Importacao concluida. $importedCount clientes cadastrados.";
        if ($errorCount > 0) {
            $msg .= " $errorCount registros nao puderam ser importados (verifique preenchimento do Nome e Telefone1 e se os documentos nao sao duplicados no sistema).";
            return redirect()
                ->to('/clientes')
                ->with('success', $msg)
                ->with('warning', 'Importacao finalizada com observacoes. Revise os registros nao importados.');
        }

        return redirect()->to('/clientes')->with('success', $msg);
    }

    private function normalizeClientePayload(array $dados): array
    {
        if (array_key_exists('nome_razao', $dados)) {
            $dados['nome_razao'] = $this->normalizeClienteNome((string) $dados['nome_razao']);
        }

        return $dados;
    }

    private function normalizeClienteNome(string $nome): string
    {
        $nome = preg_replace('/\s+/u', ' ', trim($nome)) ?? '';
        if ($nome === '') {
            return '';
        }

        if (function_exists('mb_strtolower') && function_exists('mb_convert_case')) {
            return mb_convert_case(mb_strtolower($nome, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
        }

        return ucwords(strtolower($nome));
    }
}

