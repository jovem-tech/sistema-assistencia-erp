<?php

namespace App\Controllers;

use App\Models\ClienteModel;
use App\Models\EquipamentoModel;
use App\Models\OsModel;
use App\Models\LogModel;

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

        $dados = $this->request->getPost();
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
                ->with('error', 'Cliente não encontrado.');
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

        $dados = $this->request->getPost();
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
            LogModel::registrar('cliente_excluido', 'Cliente excluído: ' . $cliente['nome_razao']);
        }

        return redirect()->to('/clientes')
            ->with('success', 'Cliente excluído com sucesso!');
    }

    public function show($id)
    {
        $cliente = $this->model->find($id);
        if (!$cliente) {
            return redirect()->to('/clientes')
                ->with('error', 'Cliente não encontrado.');
        }

        $equipamentoModel = new EquipamentoModel();
        $osModel = new OsModel();

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
            return $this->response->setJSON(['error' => 'Cliente não encontrado']);
        }
        return $this->response->setJSON($cliente);
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
                'message' => 'Verifique se os campos obrigatórios (Nome e Telefone) foram preenchidos corretamente.'
            ]);
        }

        $dados = $this->request->getPost();
        
        try {
            $this->model->insert($dados);
            $insertId = $this->model->getInsertID();

            LogModel::registrar('cliente_criado_ajax', 'Cliente cadastrado via Ajax: ' . $dados['nome_razao']);

            return $this->response->setJSON([
                'success' => true,
                'id'      => $insertId,
                'nome'    => $dados['nome_razao']
            ]);
            
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Ocorreu um erro ao salvar (Verifique se o CPF/CNPJ já existe).'
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
            ['fisica', 'João da Silva', '111.222.333-44', '12345678', 'joao@email.com', '(11) 99999-8888', '', '01001-000', 'Praça da Sé', '1', '', 'Sé', 'São Paulo', 'SP', 'Cliente de demonstração importado'],
            ['juridica', 'Empresa Modelo Ltda', '11.222.333/0001-44', '123456789012', 'contato@empresa.com', '(11) 3333-4444', '', '01310-100', 'Avenida Paulista', '1000', 'Andar 1', 'Bela Vista', 'São Paulo', 'SP', 'Empresa de demonstração importada']
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
            return redirect()->to('/clientes')->with('error', 'Arquivo inválido. Por favor, envie um arquivo CSV.');
        }

        $filepath = $file->getTempName();
        $fileStream = fopen($filepath, 'r');
        if (!$fileStream) {
            return redirect()->to('/clientes')->with('error', 'Não foi possível ler o arquivo.');
        }

        // Pula o BOM se existir
        $bom = fread($fileStream, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($fileStream); // Retorna ao início se não for BOM
        }

        // Lê a primeira linha (cabeçalhos) para identificar o delimitador correto
        $headerLine = fgets($fileStream);
        $delimiter = strpos($headerLine, ';') !== false ? ';' : ',';
        rewind($fileStream); // Volta para ler com o fputcsv agora sabendo o delimitador
        if ($bom === "\xEF\xBB\xBF") {
            fread($fileStream, 3); // Pula o BOM novamente
        }

        $headers = fgetcsv($fileStream, 1000, $delimiter);
        if (!$headers) {
            return redirect()->to('/clientes')->with('error', 'O arquivo CSV está vazio ou em formato incorreto.');
        }
        
        $expectedHeaders = [
            'tipo_pessoa', 'nome_razao', 'cpf_cnpj', 'rg_ie', 'email', 
            'telefone1', 'telefone2', 'cep', 'endereco', 'numero', 
            'complemento', 'bairro', 'cidade', 'uf', 'observacoes'
        ];
        
        // Remove quaisquer espaços extras dos cabeçalhos importados
        $headers = array_map('trim', $headers);

        $importedCount = 0;
        $errorCount = 0;

        while (($row = fgetcsv($fileStream, 1000, $delimiter)) !== false) {
            if (count($row) < 2 || empty(trim($row[1]))) {
                continue; // Pula linhas em branco ou onde o nome não foi preenchido
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

            // Validação mínima via código antes do insert
            if (empty($clienteData['nome_razao']) || empty($clienteData['telefone1'])) {
                $errorCount++;
                continue;
            }

            try {
                $this->model->insert($clienteData);
                $importedCount++;
            } catch (\Exception $e) {
                $errorCount++;
            }
        }
        fclose($fileStream);

        LogModel::registrar('cliente_importacao', "Importação CSV realizada: $importedCount sucessos, $errorCount falhas.");

        $msg = "Importação concluída. $importedCount clientes cadastrados.";
        if ($errorCount > 0) {
            $msg .= " $errorCount registros não puderam ser importados (verifique preenchimento do Nome e Telefone1 e se os documentos não são duplicados no sistema).";
            return redirect()->to('/clientes')->with('success', $msg)->with('warning', true);
        }

        return redirect()->to('/clientes')->with('success', $msg);
    }
}
