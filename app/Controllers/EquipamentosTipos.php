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
            'tipos' => $this->model->orderBy('nome', 'ASC')->findAll(),
        ];
        return view('equipamentos_tipos/index', $data);
    }

    public function store()
    {
        $rules = [
            'nome' => 'required|max_length[100]|is_unique[equipamentos_tipos.nome]'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->with('error', 'O tipo de equipamento já existe ou o nome é inválido.');
        }

        $dados = $this->request->getPost();
        
        $this->model->insert($dados);
        
        LogModel::registrar('equipamento_tipo_criado', 'Tipo de Equipamento adicionado: ' . $dados['nome']);

        return redirect()->to('/equipamentostipos')->with('success', 'Tipo de equipamento adicionado com sucesso!');
    }

    public function delete($id)
    {
        $tipo = $this->model->find($id);
        if ($tipo) {
            $this->model->delete($id);
            LogModel::registrar('equipamento_tipo_excluido', 'Tipo de Equipamento excluido ID: ' . $id);
            return redirect()->to('/equipamentostipos')->with('success', 'Tipo excluído com sucesso!');
        }
        
        return redirect()->to('/equipamentostipos')->with('error', 'Registro não encontrado.');
    }
}
