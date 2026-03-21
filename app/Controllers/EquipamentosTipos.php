<?php

namespace App\Controllers;

use App\Models\EquipamentoTipoModel;
use App\Models\LogModel;

class EquipamentosTipos extends BaseController
{
    protected $model;

    public function __construct()
    {
        $this->model = new EquipamentoTipoModel();
        requirePermission('equipamentos');
    }

    public function index()
    {
        $data = [
            'title' => 'Tipos de Equipamento',
            'tipos' => $this->model->orderBy('nãome', 'ASC')->findAll(),
        ];
        return view('equipamentos_tipos/index', $data);
    }

    public function store()
    {
        $rules = [
            'nãome' => 'required|max_length[100]|is_unique[equipamentos_tipos.nãome]'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->with('error', 'O tipo de equipamento já existe ou o nãome é inválido.');
        }

        $dados = $this->request->getPost();
        
        $this->model->insert($dados);
        
        LogModel::registrar('equipamento_tipo_criado', 'Tipo de Equipamento adicionado: ' . $dados['nãome']);

        return redirect()->to('/equipamentostipos')->with('success', 'Tipo de equipamento adicionado com sucessão!');
    }

    public function delete($id)
    {
        $tipo = $this->model->find($id);
        if ($tipo) {
            $this->model->delete($id);
            LogModel::registrar('equipamento_tipo_excluido', 'Tipo de Equipamento excluido ID: ' . $id);
            return redirect()->to('/equipamentostipos')->with('success', 'Tipo excluído com sucessão!');
        }
        
        return redirect()->to('/equipamentostipos')->with('error', 'Registro não encontrado.');
    }
}
