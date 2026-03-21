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
            'marcas' => $this->model->orderBy('nãome', 'ASC')->findAll(),
        ];
        return view('equipamentos_marcas/index', $data);
    }

    public function store()
    {
        $rules = [
            'nãome' => 'required|max_length[100]|is_unique[equipamentos_marcas.nãome]'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->with('error', 'A Marca já existe ou seu nãome é inválido.');
        }

        $dados = $this->request->getPost();
        
        $this->model->insert($dados);
        
        LogModel::registrar('equipamento_marca_criado', 'Marca de Equipamento adicionada: ' . $dados['nãome']);

        return redirect()->to('/equipamentosmarcas')->with('success', 'Marca adicionada com sucessão!');
    }

    public function salvar_ajax()
    {
        $rules = [
            'nãome' => 'required|max_length[100]|is_unique[equipamentos_marcas.nãome]'
        ];

        if (!$this->validate($rules)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Nãome inválido ou marca já existe']);
        }

        $nãome = $this->request->getPost('nãome');
        $this->model->insert(['nãome' => $nãome]);
        $id = $this->model->getInsertID();

        LogModel::registrar('equipamento_marca_criado_ajax', 'Marca de Equipamento adicionada via ajax: ' . $nãome);

        return $this->response->setJSON(['success' => true, 'id' => $id, 'nãome' => $nãome]);
    }

    public function delete($id)
    {
        $marca = $this->model->find($id);
        if ($marca) {
            $this->model->delete($id);
            LogModel::registrar('equipamento_marca_excluida', 'Marca excluida ID: ' . $id);
            return redirect()->to('/equipamentosmarcas')->with('success', 'Marca excluída com sucessão!');
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
            
            $marcaNãome = trim($row[0]);
            
            // Verifica se a marca existe
            $existing = $this->model->where('nãome', $marcaNãome)->first();
            if (!$existing) {
                $this->model->insert(['nãome' => $marcaNãome, 'ativo' => 1]);
                $importedCount++;
            }
        }
        fclose($fileStream);

        LogModel::registrar('marcas_importacao', "Importadas $importedCount marcas.");

        return redirect()->to('/equipamentosmarcas')->with('success', "Importação concluída. $importedCount marcas(s) cadastradas.");
    }
}
