<?php

namespace App\Controllers;

use App\Models\GrupoModel;
use App\Models\LogModel;

class Grupos extends BaseController
{
    protected $model;

    public function __construct()
    {
        $this->model = new GrupoModel();
        // Apenas admins gerenciam grupos
        requirePermission('grupos', 'visualizar');
    }

    public function index()
    {
        $data = [
            'title'  => 'Grupos de Acesso',
            'grupos' => $this->model->findAll(),
        ];
        return view('grupos/index', $data);
    }

    public function create()
    {
        requirePermission('grupos', 'criar');
        return view('grupos/form', ['title' => 'Novo Grupo']);
    }

    public function store()
    {
        requirePermission('grupos', 'criar');

        $rules = [
            'nome' => 'required|min_length[3]|max_length[80]',
        ];
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $this->model->insert($this->request->getPost(['nome', 'descricao']));
        LogModel::registrar('grupo_criado', 'Grupo criado: ' . $this->request->getPost('nome'));

        return redirect()->to('/grupos')->with('success', 'Grupo criado! Configure as permissões abaixo.');
    }

    public function permissoes(int $id)
    {
        requirePermission('grupos', 'editar');

        $grupo = $this->model->find($id);
        if (!$grupo) return redirect()->to('/grupos')->with('error', 'Grupo não encontrado.');

        $matrix = $this->model->getComPermissoes($id);

        return view('grupos/permissoes', [
            'title'   => 'Permissões — ' . $grupo['nome'],
            'grupo'   => $grupo,
            'modulos'    => $matrix['modulos'],
            'permissoes' => $matrix['permissoes'],
            'granted'    => $matrix['granted'],
        ]);
    }

    public function salvarPermissoes(int $id)
    {
        requirePermission('grupos', 'editar');

        $grupo = $this->model->find($id);
        if (!$grupo) return redirect()->to('/grupos')->with('error', 'Grupo não encontrado.');

        $permissoes = $this->request->getPost('permissoes') ?? [];
        $this->model->salvarPermissoes($id, $permissoes);

        // Se o usuário logado pertence a esse grupo, invalida cache
        if (session()->get('user_grupo_id') == $id) {
            refreshPermissions();
        }

        LogModel::registrar('grupo_permissoes', "Permissões do grupo '{$grupo['nome']}' atualizadas.");

        return redirect()->to('/grupos/' . $id . '/permissoes')
            ->with('success', 'Permissões salvas com sucesso!');
    }

    public function edit(int $id)
    {
        requirePermission('grupos', 'editar');
        $grupo = $this->model->find($id);
        if (!$grupo) return redirect()->to('/grupos')->with('error', 'Grupo não encontrado.');

        return view('grupos/form', ['title' => 'Editar Grupo', 'grupo' => $grupo]);
    }

    public function update(int $id)
    {
        requirePermission('grupos', 'editar');
        $this->model->update($id, $this->request->getPost(['nome', 'descricao']));
        LogModel::registrar('grupo_editado', 'Grupo editado ID: ' . $id);
        return redirect()->to('/grupos')->with('success', 'Grupo atualizado!');
    }

    public function delete(int $id)
    {
        requirePermission('grupos', 'excluir');
        $grupo = $this->model->find($id);
        if (!$grupo) return redirect()->to('/grupos')->with('error', 'Grupo não encontrado.');
        if ($grupo['sistema'] ?? false) {
            return redirect()->to('/grupos')->with('error', 'Este grupo é do sistema e não pode ser excluído.');
        }
        $this->model->delete($id);
        LogModel::registrar('grupo_excluido', 'Grupo excluído ID: ' . $id);
        return redirect()->to('/grupos')->with('success', 'Grupo excluído!');
    }
}
