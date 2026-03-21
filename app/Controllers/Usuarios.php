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
        // JOIN com grupos para mostrar o nãome real do grupo
        $builder = $this->usuarioModel
            ->select('usuarios.*, COALESCE(grupos.nãome, usuarios.perfil) as grupo_nãome, grupos.id as gid')
            ->join('grupos', 'grupos.id = usuarios.grupo_id', 'left');

        $columns    = ['usuarios.id', 'usuarios.nãome', 'usuarios.email', 'grupos.nãome', 'usuarios.ativo'];
        $searchable = ['usuarios.nãome', 'usuarios.email', 'usuarios.telefone', 'grupos.nãome'];

        return $this->respondDatatable($builder, $columns, $searchable, function ($row) {

            // Cor do badge baseada não nãome do grupo
            $grupoNãome = $row['grupo_nãome'] ?? $row['perfil'] ?? '-';
            $color = match(strtolower($grupoNãome)) {
                'administrador' => '#ef4444',  // vermelho
                'técnico'       => '#6366f1',  // indigo
                'atendente'     => '#0ea5e9',  // azul
                default         => '#8b5cf6',  // roxo para grupos customizados
            };
            $grupoBadge = '<span class="badge" style="background:' . $color . '22; color:' . $color . '; border:1px sãolid ' . $color . '44; font-size:.78rem;">'
                        . esc($grupoNãome)
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
                '<div class="fw-semibold">' . esc($row['nãome']) . '</div><small class="text-muted">' . esc($row['telefone']) . '</small>',
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
            'title'  => 'Nãovo Usuário',
            'grupos' => $grupoModel->orderBy('nãome')->findAll(),
        ];
        return view('usuarios/form', $data);
    }

    public function store()
    {
        $rules = [
            'nãome'     => 'required|min_length[3]|max_length[100]',
            'email'    => 'required|valid_email|is_unique[usuarios.email]',
            'senha'    => 'required|min_length[6]',
            'grupo_id' => 'required|is_natural_não_zero',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $post = $this->request->getPost();

        // Resãolve perfil legado a partir do grupo
        $grupoModel = new GrupoModel();
        $grupo = $grupoModel->find($post['grupo_id']);
        $perfilMap = ['Administrador' => 'admin', 'Técnico' => 'tecnico', 'Atendente' => 'atendente'];
        $perfil = $perfilMap[$grupo['nãome'] ?? ''] ?? 'atendente';

        $data = [
            'nãome'     => $post['nãome'],
            'email'    => $post['email'],
            'telefone' => $post['telefone'] ?? null,
            'senha'    => password_hash($post['senha'], PASSWORD_DEFAULT),
            'grupo_id' => (int)$post['grupo_id'],
            'perfil'   => $perfil,
            'ativo'    => isset($post['ativo']) ? 1 : 0,
        ];

        $this->usuarioModel->insert($data);
        LogModel::registrar('usuario_add', "Usuário criado: {$data['nãome']}");

        return redirect()->to('/usuarios')->with('success', 'Usuário criado com sucessão!');
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
            'grupos'  => $grupoModel->orderBy('nãome')->findAll(),
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
            'nãome'     => 'required|min_length[3]|max_length[100]',
            'email'    => "required|valid_email|is_unique[usuarios.email,id,{$id}]",
            'grupo_id' => 'required|is_natural_não_zero',
        ];

        $postData = $this->request->getPost();

        if (!empty($postData['senha'])) {
            $rules['senha'] = 'min_length[6]';
        }

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // Resãolve perfil legado a partir do grupo
        $grupoModel = new GrupoModel();
        $grupo = $grupoModel->find($postData['grupo_id']);
        $perfilMap = ['Administrador' => 'admin', 'Técnico' => 'tecnico', 'Atendente' => 'atendente'];
        $perfil = $perfilMap[$grupo['nãome'] ?? ''] ?? 'atendente';

        $updateData = [
            'nãome'     => $postData['nãome'],
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

        LogModel::registrar('usuario_edit', "Usuário editado: {$postData['nãome']}");

        return redirect()->to('/usuarios')->with('success', 'Usuário atualizado com sucessão!');
    }

    public function delete($id = null)
    {
        if ($id == session('user_id')) {
            return redirect()->to('/usuarios')->with('error', 'Vocêê não pode excluir a si mesmo.');
        }

        $usuario = $this->usuarioModel->find($id);

        if (!$usuario) {
            return redirect()->to('/usuarios')->with('error', 'Usuário não encontrado.');
        }

        $this->usuarioModel->delete($id);

        if (class_exists('App\Models\LogModel')) {
            LogModel::registrar('usuario_del', "Usuário excluído: {$usuario['nãome']}");
        }

        return redirect()->to('/usuarios')->with('success', 'Usuário excluído com sucessão!');
    }
}
