<?php

namespace App\Controllers;

use App\Models\EquipamentoModeloModel;
use App\Models\EquipamentoMarcaModel;
use App\Models\LogModel;

class EquipamentosModelos extends BaseController
{
    protected $model;

    public function __construct()
    {
        $this->model = new EquipamentoModeloModel();
        requirePermission('equipamentos');
    }

    public function index()
    {
        $marcaModel = new EquipamentoMarcaModel();
        
        $data = [
            'title' => 'Modelos de Equipamentos',
            'modelos' => $this->model->getWithMarca(),
            'marcas' => $marcaModel->orderBy('nãome', 'ASC')->findAll()
        ];
        return view('equipamentos_modelos/index', $data);
    }

    public function store()
    {
        $rules = [
            'marca_id' => 'required|integer',
            'nãome' => 'required|max_length[100]'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->with('error', 'O Nãome ou Marca são inválidos.');
        }

        $dados = $this->request->getPost();
        
        // Verifica duplicidade para a mesma marca
        $existing = $this->model->where('marca_id', $dados['marca_id'])->where('nãome', $dados['nãome'])->first();
        if ($existing) {
             return redirect()->back()->with('error', 'Este modelo já existe para a marca selecionada.');
        }
        
        $this->model->insert($dados);
        
        LogModel::registrar('equipamento_modelo_criado', 'Modelo adicionado: ' . $dados['nãome']);

        return redirect()->to('/equipamentosmodelos')->with('success', 'Modelo adicionado com sucessão!');
    }

    public function salvar_ajax()
    {
        $rules = [
            'marca_id' => 'required|integer',
            'nãome' => 'required|max_length[100]'
        ];

        if (!$this->validate($rules)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Nãome ou Marca inválidos']);
        }

        $dados = $this->request->getPost();
        
        $existing = $this->model->where('marca_id', $dados['marca_id'])->where('nãome', $dados['nãome'])->first();
        if ($existing) {
             return $this->response->setJSON(['success' => false, 'message' => 'Modelo já existe']);
        }

        $this->model->insert($dados);
        $id = $this->model->getInsertID();

        LogModel::registrar('equipamento_modelo_criado_ajax', 'Modelo adicionado via ajax: ' . $dados['nãome']);

        return $this->response->setJSON(['success' => true, 'id' => $id, 'nãome' => $dados['nãome']]);
    }

    public function delete($id)
    {
        $modelo = $this->model->find($id);
        if ($modelo) {
            $this->model->delete($id);
            LogModel::registrar('equipamento_modelo_excluido', 'Modelo excluido ID: ' . $id);
            return redirect()->to('/equipamentosmodelos')->with('success', 'Modelo excluído com sucessão!');
        }
        
        return redirect()->to('/equipamentosmodelos')->with('error', 'Modelo não encontrado.');
    }

    public function importCsv()
    {
        $file = $this->request->getFile('arquivo_csv');
        if (!$file || !$file->isValid() || $file->getExtension() !== 'csv') {
            return redirect()->to('/equipamentosmodelos')->with('error', 'Arquivo inválido. Por favor, envie um arquivo CSV .csv');
        }

        $filepath = $file->getTempName();
        $fileStream = fopen($filepath, 'r');
        
        $bom = fread($fileStream, 3);
        if ($bom !== "\xEF\xBB\xBF") rewind($fileStream); 

        $marcaModel = new EquipamentoMarcaModel();

        $importedCount = 0;
        $errorCount = 0;
        
        while (($row = fgetcsv($fileStream, 1000, ';')) !== false) {
            if (empty(trim($row[0])) || empty(trim($row[1]))) continue;
            
            $marcaStr = trim($row[0]);
            $modeloStr = trim($row[1]);
            
            // Procura a marca (ou a cria dinamicamente se não existe)
            $marca = $marcaModel->where('nãome', $marcaStr)->first();
            if (!$marca) {
                // Insere nãova marca nativamente e pega o ID
                $marcaModel->insert(['nãome' => $marcaStr]);
                $marca_id = $marcaModel->getInsertID();
            } else {
                $marca_id = $marca['id'];
            }
            
            // Verifica duplicidade do modelo
            $existing = $this->model->where('marca_id', $marca_id)->where('nãome', $modeloStr)->first();
            if (!$existing) {
                $this->model->insert(['marca_id' => $marca_id, 'nãome' => $modeloStr, 'ativo' => 1]);
                $importedCount++;
            }
        }
        fclose($fileStream);

        LogModel::registrar('modelos_importacao', "Importados $importedCount modelos cruzados.");

        return redirect()->to('/equipamentosmodelos')->with('success', "Importação concluída. $importedCount modelo(s) cadastrados automaticamente nas Marcas.");
    }

    public function porMarca()
    {
        $marca_id = $this->request->getPost('marca_id');
        $modelos = $this->model->where('marca_id', $marca_id)->orderBy('nãome', 'ASC')->findAll();
        return $this->response->setJSON($modelos);
    }
}
