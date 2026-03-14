<?php

namespace App\Controllers;

use App\Models\EquipamentoMarcaModel;
use App\Models\LogModel;

class EquipamentosMarcas extends BaseController
{
    protected $model;

    public function __construct()
    {
        $this->model = new EquipamentoMarcaModel();
        requirePermission('equipamentos');
    }

    public function index()
    {
        $data = [
            'title' => 'Marcas de Equipamentos',
            'marcas' => $this->model->orderBy('nome', 'ASC')->findAll(),
        ];
        return view('equipamentos_marcas/index', $data);
    }

    public function store()
    {
        $rules = [
            'nome' => 'required|max_length[100]|is_unique[equipamentos_marcas.nome]'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->with('error', 'A Marca já existe ou seu nome é inválido.');
        }

        $dados = $this->request->getPost();
        
        $this->model->insert($dados);
        
        LogModel::registrar('equipamento_marca_criado', 'Marca de Equipamento adicionada: ' . $dados['nome']);

        return redirect()->to('/equipamentosmarcas')->with('success', 'Marca adicionada com sucesso!');
    }

    public function salvar_ajax()
    {
        $rules = [
            'nome' => 'required|max_length[100]|is_unique[equipamentos_marcas.nome]'
        ];

        if (!$this->validate($rules)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Nome inválido ou marca já existe']);
        }

        $nome = $this->request->getPost('nome');
        $this->model->insert(['nome' => $nome]);
        $id = $this->model->getInsertID();

        LogModel::registrar('equipamento_marca_criado_ajax', 'Marca de Equipamento adicionada via ajax: ' . $nome);

        return $this->response->setJSON(['success' => true, 'id' => $id, 'nome' => $nome]);
    }

    public function delete($id)
    {
        $marca = $this->model->find($id);
        if ($marca) {
            $this->model->delete($id);
            LogModel::registrar('equipamento_marca_excluida', 'Marca excluida ID: ' . $id);
            return redirect()->to('/equipamentosmarcas')->with('success', 'Marca excluída com sucesso!');
        }
        
        return redirect()->to('/equipamentosmarcas')->with('error', 'Marca não encontrada.');
    }

    public function importCsv()
    {
        $file = $this->request->getFile('arquivo_csv');
        if (!$file || !$file->isValid() || $file->getExtension() !== 'csv') {
            return redirect()->to('/equipamentosmarcas')->with('error', 'Arquivo inválido. Por favor, envie um arquivo CSV .csv');
        }

        $filepath = $file->getTempName();
        $fileStream = fopen($filepath, 'r');
        
        $bom = fread($fileStream, 3);
        if ($bom !== "\xEF\xBB\xBF") rewind($fileStream); 

        $importedCount = 0;
        while (($row = fgetcsv($fileStream, 1000, ';')) !== false) {
            if (empty(trim($row[0]))) continue;
            
            $marcaNome = trim($row[0]);
            
            // Verifica se a marca existe
            $existing = $this->model->where('nome', $marcaNome)->first();
            if (!$existing) {
                $this->model->insert(['nome' => $marcaNome, 'ativo' => 1]);
                $importedCount++;
            }
        }
        fclose($fileStream);

        LogModel::registrar('marcas_importacao', "Importadas $importedCount marcas.");

        return redirect()->to('/equipamentosmarcas')->with('success', "Importação concluída. $importedCount marcas(s) cadastradas.");
    }
}
