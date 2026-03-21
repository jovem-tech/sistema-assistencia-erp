<?php

namespace App\Controllers;

use App\Models\FuncionarioModel;
use App\Models\LogModel;

class Funcionarios extends BaseController
{
    protected $model;

    public function __construct()
    {
        $this->model = new FuncionarioModel();
        requirePermission('funcionarios');
    }

    public function index()
    {
        $data = [
            'title'        => 'Funcionários',
            'funcionarios' => $this->model->orderBy('nome', 'ASC')->findAll(),
        ];
        return view('funcionarios/index', $data);
    }

    public function create()
    {
        $data = [
            'title' => 'Novo Funcionário',
        ];
        return view('funcionarios/form', $data);
    }

    public function store()
    {
        $rules = [
            'nome'     => 'required|min_length[3]',
            'cpf'      => 'required',
            'telefone' => 'required',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $dados = $this->request->getPost();
        $dados['ativo'] = isset($dados['ativo']) && $dados['ativo'] === 'on' ? 1 : 0;
        $this->model->insert($dados);

        LogModel::registrar('funcionario_criado', 'Funcionário cadastrado: ' . $dados['nome']);

        return redirect()->to('/funcionarios')->with('success', 'Funcionário cadastrado com sucesso!');
    }

    public function edit($id)
    {
        $funcionario = $this->model->find($id);
        if (!$funcionario) {
            return redirect()->to('/funcionarios')->with('error', 'Funcionário não encontrado.');
        }

        $data = [
            'title'       => 'Editar Funcionário',
            'funcionario' => $funcionario,
        ];
        return view('funcionarios/form', $data);
    }

    public function update($id)
    {
        $rules = [
            'nome'     => 'required|min_length[3]',
            'cpf'      => 'required',
            'telefone' => 'required',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $dados = $this->request->getPost();
        $dados['ativo'] = isset($dados['ativo']) && $dados['ativo'] === 'on' ? 1 : 0;
        $this->model->update($id, $dados);

        LogModel::registrar('funcionario_atualizado', 'Funcionário atualizado ID: ' . $id);

        return redirect()->to('/funcionarios')->with('success', 'Funcionário atualizado com sucesso!');
    }

    public function delete($id)
    {
        $funcionario = $this->model->find($id);
        if ($funcionario) {
            $this->model->delete($id);
            LogModel::registrar('funcionario_excluido', 'Funcionário excluído: ' . $funcionario['nome']);
        }

        return redirect()->to('/funcionarios')->with('success', 'Funcionário excluído com sucesso!');
    }
}
