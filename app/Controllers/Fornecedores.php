<?php

namespace App\Controllers;

use App\Models\FornecedorModel;
use App\Models\LogModel;

class Fornecedores extends BaseController
{
    protected $model;

    public function __construct()
    {
        $this->model = new FornecedorModel();
        requirePermission('fornecedores');
    }

    public function index()
    {
        $data = [
            'title'        => 'Fornecedores',
            'fornecedores' => $this->model->orderBy('nome_fantasia', 'ASC')->findAll(),
        ];
        return view('fornecedores/index', $data);
    }

    public function create()
    {
        $data = [
            'title' => 'Novo Fornecedor',
        ];
        return view('fornecedores/form', $data);
    }

    public function store()
    {
        $rules = [
            'nome_fantasia' => 'required|min_length[3]',
            'telefone1'     => 'required',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $dados = $this->request->getPost();
        $this->model->insert($dados);

        LogModel::registrar('fornecedor_criado', 'Fornecedor cadastrado: ' . $dados['nome_fantasia']);

        return redirect()->to('/fornecedores')->with('success', 'Fornecedor cadastrado com sucesso!');
    }

    public function edit($id)
    {
        $fornecedor = $this->model->find($id);
        if (!$fornecedor) {
            return redirect()->to('/fornecedores')->with('error', 'Fornecedor não encontrado.');
        }

        $data = [
            'title'      => 'Editar Fornecedor',
            'fornecedor' => $fornecedor,
        ];
        return view('fornecedores/form', $data);
    }

    public function update($id)
    {
        $rules = [
            'nome_fantasia' => 'required|min_length[3]',
            'telefone1'     => 'required',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $dados = $this->request->getPost();
        $this->model->update($id, $dados);

        LogModel::registrar('fornecedor_atualizado', 'Fornecedor atualizado ID: ' . $id);

        return redirect()->to('/fornecedores')->with('success', 'Fornecedor atualizado com sucesso!');
    }

    public function delete($id)
    {
        $fornecedor = $this->model->find($id);
        if ($fornecedor) {
            $this->model->delete($id);
            LogModel::registrar('fornecedor_excluido', 'Fornecedor excluído: ' . $fornecedor['nome_fantasia']);
        }

        return redirect()->to('/fornecedores')->with('success', 'Fornecedor excluído com sucesso!');
    }
}
