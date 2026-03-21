<?php

namespace App\Controllers;

use App\Models\UsuarioModel;
use App\Models\GrupoModel;
use App\Models\LogModel;

class Usuarios extends BaseController
{
    protected $usuarioModel;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->usuarioModel = new UsuarioModel();
        requirePermission('usuarios');
    }

    public function index()
    {
        $data = [
            'title'    => 'Gerenciar Usuários',
        ];

        return view('usuarios/index', $data);
    }

    public function datatable()
    {
        // JOIN com grupos para mostrar o nome real do grupo
        $builder = $this->usuarioModel
            ->select('usuarios.*, COALESCE(grupos.nome, usuarios.perfil) as grupo_nome, grupos.id as gid')
            ->join('grupos', 'grupos.id = usuarios.grupo_id', 'left');

        $columns    = ['usuarios.id', 'usuarios.nome', 'usuarios.email', 'grupos.nome', 'usuarios.ativo'];
        $searchable = ['usuarios.nome', 'usuarios.email', 'usuarios.telefone', 'grupos.nome'];

        return $this->respondDatatable($builder, $columns, $searchable, function ($row) {

            // Cor do badge baseada no nome do grupo
            $grupoNome = $row['grupo_nome'] ?? $row['perfil'] ?? '-';
            $color = match(strtolower($grupoNome)) {
                'administrador' => '#ef4444',  // vermelho
                'técnico'       => '#6366f1',  // indigo
                'atendente'     => '#0ea5e9',  // azul
                default         => '#8b5cf6',  // roxo para grupos customizados
            };
            $grupoBadge = '<span class="badge" style="background:' . $color . '22; color:' . $color . '; border:1px solid ' . $color . '44; font-size:.78rem;">'
                        . esc($grupoNome)
                        . '</span>';

            $statusBadge = $row['ativo']
                ? '<span class="badge bg-success">Ativo</span>'
                : '<span class="badge bg-secondary">Inativo</span>';

            $acoes = '<div class="btn-group btn-group-sm">';
            if (can('usuarios', 'editar')) {
                $acoes .= '<a href="' . base_url('usuarios/editar/' . $row['id']) . '" class="btn btn-outline-primary" title="Editar"><i class="bi bi-pencil"></i></a>';
            }
            if (can('usuarios', 'excluir') && $row['id'] != session('user_id')) {
                $acoes .= '<button type="button" class="btn btn-outline-danger btn-delete" data-url="' . base_url('usuarios/excluir/' . $row['id']) . '" title="Excluir"><i class="bi bi-trash"></i></button>';
            }
            $acoes .= '</div>';

            return [
                $row['id'],
                '<div class="fw-semibold">' . esc($row['nome']) . '</div><small class="text-muted">' . esc($row['telefone']) . '</small>',
                esc($row['email']),
                $grupoBadge,
                $statusBadge,
                $acoes
            ];
        });
    }


    public function create()
    {
        $grupoModel = new GrupoModel();
        $data = [
            'title'  => 'Novo Usuário',
            'grupos' => $grupoModel->orderBy('nome')->findAll(),
        ];
        return view('usuarios/form', $data);
    }

    public function store()
    {
        $rules = [
            'nome'     => 'required|min_length[3]|max_length[100]',
            'email'    => 'required|valid_email|is_unique[usuarios.email]',
            'senha'    => 'required|min_length[6]',
            'grupo_id' => 'required|is_natural_no_zero',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $post = $this->request->getPost();

        // Resolve perfil legado a partir do grupo
        $grupoModel = new GrupoModel();
        $grupo = $grupoModel->find($post['grupo_id']);
        $perfilMap = ['Administrador' => 'admin', 'Técnico' => 'tecnico', 'Atendente' => 'atendente'];
        $perfil = $perfilMap[$grupo['nome'] ?? ''] ?? 'atendente';

        $data = [
            'nome'     => $post['nome'],
            'email'    => $post['email'],
            'telefone' => $post['telefone'] ?? null,
            'senha'    => password_hash($post['senha'], PASSWORD_DEFAULT),
            'grupo_id' => (int)$post['grupo_id'],
            'perfil'   => $perfil,
            'ativo'    => isset($post['ativo']) ? 1 : 0,
        ];

        $this->usuarioModel->insert($data);
        LogModel::registrar('usuario_add', "Usuário criado: {$data['nome']}");

        return redirect()->to('/usuarios')->with('success', 'Usuário criado com sucesso!');
    }

    public function edit($id = null)
    {
        $usuario = $this->usuarioModel->find($id);
        if (!$usuario) {
            return redirect()->to('/usuarios')->with('error', 'Usuário não encontrado.');
        }

        $grupoModel = new GrupoModel();
        $data = [
            'title'   => 'Editar Usuário',
            'usuario' => $usuario,
            'grupos'  => $grupoModel->orderBy('nome')->findAll(),
        ];
        return view('usuarios/form', $data);
    }

    public function update($id = null)
    {
        $usuario = $this->usuarioModel->find($id);

        if (!$usuario) {
            return redirect()->to('/usuarios')->with('error', 'Usuário não encontrado.');
        }

        $rules = [
            'nome'     => 'required|min_length[3]|max_length[100]',
            'email'    => "required|valid_email|is_unique[usuarios.email,id,{$id}]",
            'grupo_id' => 'required|is_natural_no_zero',
        ];

        $postData = $this->request->getPost();

        if (!empty($postData['senha'])) {
            $rules['senha'] = 'min_length[6]';
        }

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // Resolve perfil legado a partir do grupo
        $grupoModel = new GrupoModel();
        $grupo = $grupoModel->find($postData['grupo_id']);
        $perfilMap = ['Administrador' => 'admin', 'Técnico' => 'tecnico', 'Atendente' => 'atendente'];
        $perfil = $perfilMap[$grupo['nome'] ?? ''] ?? 'atendente';

        $updateData = [
            'nome'     => $postData['nome'],
            'email'    => $postData['email'],
            'telefone' => $postData['telefone'] ?? null,
            'grupo_id' => (int)$postData['grupo_id'],
            'perfil'   => $perfil,
            'ativo'    => isset($postData['ativo']) ? 1 : 0,
        ];

        if (!empty($postData['senha'])) {
            $updateData['senha'] = password_hash($postData['senha'], PASSWORD_DEFAULT);
        }

        $this->usuarioModel->update($id, $updateData);

        // Invalida cache de permissões se for o próprio usuário
        if (session()->get('user_id') == $id) {
            session()->set('user_grupo_id', $updateData['grupo_id']);
            refreshPermissions();
        }

        LogModel::registrar('usuario_edit', "Usuário editado: {$postData['nome']}");

        return redirect()->to('/usuarios')->with('success', 'Usuário atualizado com sucesso!');
    }

    public function delete($id = null)
    {
        if ($id == session('user_id')) {
            return redirect()->to('/usuarios')->with('error', 'Você não pode excluir a si mesmo.');
        }

        $usuario = $this->usuarioModel->find($id);

        if (!$usuario) {
            return redirect()->to('/usuarios')->with('error', 'Usuário não encontrado.');
        }

        $this->usuarioModel->delete($id);

        if (class_exists('App\Models\LogModel')) {
            LogModel::registrar('usuario_del', "Usuário excluído: {$usuario['nome']}");
        }

        return redirect()->to('/usuarios')->with('success', 'Usuário excluído com sucesso!');
    }
}
