<?php

namespace App\Controllers;

use App\Models\UsuarioModel;
use App\Models\LogModel;

class Perfil extends BaseController
{
    public function index()
    {
        $model = new UsuarioModel();
        $usuario = $model->find(session()->get('user_id'));

        $data = [
            'title' => 'Meu Perfil',
            'usuario' => $usuario
        ];

        return view('perfil/index', $data);
    }

    public function salvar()
    {
        $model = new UsuarioModel();
        $userId = session()->get('user_id');
        $usuario = $model->find($userId);

        $rules = [
            'nome'  => 'required|min_length[3]|max_length[100]',
            'email' => "required|valid_email|is_unique[usuarios.email,id,{$userId}]",
        ];

        $postData = $this->request->getPost();

        // Password change logic
        if (!empty($postData['senha_atual']) || !empty($postData['nova_senha'])) {
            if (empty($postData['senha_atual']) || empty($postData['nova_senha'])) {
                return redirect()->back()->withInput()->with('error', 'Para alterar a senha, informe a senha atual e a nova.');
            }

            if (!password_verify($postData['senha_atual'], $usuario['senha'])) {
                return redirect()->back()->withInput()->with('error', 'A senha atual está incorreta.');
            }

            $rules['nova_senha'] = 'min_length[6]';
            if ($postData['nova_senha'] !== $postData['confirma_senha']) {
                return redirect()->back()->withInput()->with('error', 'A nova senha e a confirmação não coincidem.');
            }
        }

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $updateData = [
            'nome'     => $postData['nome'],
            'email'    => $postData['email'],
            'telefone' => $postData['telefone'],
        ];

        if (!empty($postData['nova_senha'])) {
            $updateData['senha'] = password_hash($postData['nova_senha'], PASSWORD_DEFAULT);
        }

        // Handle photo upload
        $foto = $this->request->getFile('foto');
        if ($foto && $foto->isValid() && !$foto->hasMoved()) {
            if (in_array($foto->getExtension(), ['jpg', 'jpeg', 'png', 'gif'])) {
                $uploadPath = 'uploads/usuarios';
                if (!is_dir($uploadPath)) {
                    mkdir($uploadPath, 0777, true);
                }
                
                $newName = $foto->getRandomName();
                $foto->move($uploadPath, $newName);
                
                // Delete old photo if exists
                if ($usuario['foto'] && file_exists($uploadPath . '/' . $usuario['foto'])) {
                    unlink($uploadPath . '/' . $usuario['foto']);
                }
                
                $updateData['foto'] = $newName;
                
                // Update session variable
                session()->set('user_foto', $newName);
            } else {
                return redirect()->back()->withInput()->with('error', 'Formato de imagem inválido. Use JPG, PNG ou GIF.');
            }
        }

        $model->update($userId, $updateData);

        // Update session name for immediate reflection
        session()->set('user_nome', $postData['nome']);
        session()->set('user_email', $postData['email']);

        if (class_exists('App\Models\LogModel')) {
            LogModel::registrar('perfil', 'Usuário atualizou o próprio perfil');
        }

        return redirect()->to('/perfil')->with('success', 'Perfil atualizado com sucesso!');
    }
}
