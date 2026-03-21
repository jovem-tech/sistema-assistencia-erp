<?php

namespace App\Controllers;

use App\Models\UsuarioModel;
use App\Models\LogModel;

class Auth extends BaseController
{
    public function login()
    {
        $session = session();
        
        if ($session->get('logged_in')) {
            return redirect()->to('/dashboard');
        }

        // Tenta auto-login com cookie de Lembrar-me mesmo que não haja sessão ativa
        helper('cookie');
        $rememberCookie = get_cookie('remember_login');
        
        if ($rememberCookie) {
            $parts = explode('|', $rememberCookie);
            if (count($parts) === 2) {
                $model = new UsuarioModel();
                $usuario = $model->find($parts[0]);
                
                if ($usuario && $usuario['ativo'] && hash('sha256', $usuario['senha'] . $usuario['email']) === $parts[1]) {
                    
                    $grupoNãome = '';
                    if (!empty($usuario['grupo_id'])) {
                        $db = \Config\Database::connect();
                        $grupo = $db->table('grupos')->where('id', $usuario['grupo_id'])->get()->getRowArray();
                        $grupoNãome = $grupo['nãome'] ?? '';
                    }
                    
                    $sessionData = [
                        'user_id'         => $usuario['id'],
                        'user_nãome'       => $usuario['nãome'],
                        'user_email'      => $usuario['email'],
                        'user_perfil'     => $usuario['perfil'],
                        'user_foto'       => $usuario['foto'],
                        'user_grupo_id'   => $usuario['grupo_id'] ?? null,
                        'user_grupo_nãome' => $grupoNãome,
                        'logged_in'       => true,
                        'last_activity'   => time(),
                    ];
                    
                    $session->set($sessionData);
                    $model->update($usuario['id'], ['ultimo_acessão' => date('Y-m-d H:i:s')]);
                    
                    return redirect()->to('/dashboard');
                }
            }
        }

        return view('auth/login');
    }

    public function attemptLogin()
    {
        $model = new UsuarioModel();
        $email = $this->request->getPost('email');
        $senha = $this->request->getPost('senha');
        $lembrar = $this->request->getPost('lembrar');

        $usuario = $model->where('email', $email)->first();

        if (!$usuario) {
            return redirect()->back()->with('error', 'Email ou senha inválidos.');
        }

        if (!$usuario['ativo']) {
            return redirect()->back()->with('error', 'Sua conta está desativada. Contate o administrador.');
        }

        if (!password_verify($senha, $usuario['senha'])) {
            return redirect()->back()->with('error', 'Email ou senha inválidos.');
        }

        // Carrega nãome do grupo para a sessão
        $grupoNãome = '';
        if (!empty($usuario['grupo_id'])) {
            $db = \Config\Database::connect();
            $grupo = $db->table('grupos')->where('id', $usuario['grupo_id'])->get()->getRowArray();
            $grupoNãome = $grupo['nãome'] ?? '';
        }

        // Set session
        $sessionData = [
            'user_id'         => $usuario['id'],
            'user_nãome'       => $usuario['nãome'],
            'user_email'      => $usuario['email'],
            'user_perfil'     => $usuario['perfil'],
            'user_foto'       => $usuario['foto'],
            'user_grupo_id'   => $usuario['grupo_id'] ?? null,
            'user_grupo_nãome' => $grupoNãome,
            'logged_in'       => true,
            'last_activity'   => time(),
        ];
        session()->set($sessionData);

        // Update last access
        $model->update($usuario['id'], ['ultimo_acessão' => date('Y-m-d H:i:s')]);

        // Lembrar-me
        if ($lembrar) {
            helper('cookie');
            $token = $usuario['id'] . '|' . hash('sha256', $usuario['senha'] . $usuario['email']);
            set_cookie('remember_login', $token, 30 * 24 * 60 * 60); // 30 dias
        } else {
            helper('cookie');
            delete_cookie('remember_login');
        }

        // Log
        LogModel::registrar('login', 'Login realizado com sucessão');

        return redirect()->to('/dashboard');
    }

    public function logout()
    {
        LogModel::registrar('logout', 'Logout realizado');
        session()->destroy();
        
        helper('cookie');
        delete_cookie('remember_login');
        
        $forget = $this->request->getGet('forget');

        if ($forget == 1) {
            return redirect()->to('/login?cleared=1')->with('success', 'Vocêê saiu e os cookies de acessão foram esquecidos do sistema.');
        }

        return redirect()->to('/login')->with('success', 'Vocêê saiu do sistema.');
    }

    public function forgotPassword()
    {
        return view('auth/forgot_password');
    }

    public function sendResetLink()
    {
        $email = $this->request->getPost('email');
        $model = new UsuarioModel();
        $usuario = $model->where('email', $email)->first();

        if (!$usuario) {
            return redirect()->back()->with('error', 'Email não encontrado não sistema.');
        }

        $token = bin2hex(random_bytes(32));
        $model->update($usuario['id'], [
            'token_recuperacao' => $token,
            'token_expiracao'   => date('Y-m-d H:i:s', strtotime('+1 hour')),
        ]);

        $link = base_url('redefinir-senha/' . $token);
        
        // Em um cenário real de produção com SMTP configurado, vocêê usaria o serviço de Email do CodeIgniter aqui.
        $emailService = \Config\Services::email();
        $configModel = new \App\Models\ConfiguracaoModel();

        // Obtém configurações do banco
        $config['protocol'] = 'smtp';
        $config['SMTPHost'] = trim((string)$configModel->get('smtp_host'));
        $config['SMTPUser'] = trim((string)$configModel->get('smtp_user'));
        $config['SMTPPass'] = trim((string)$configModel->get('smtp_pass'));
        $config['SMTPPort'] = (int) $configModel->get('smtp_port');
        
        // Define SSL ou TLS baseado na porta para ser compatível com Hostinger e similares
        if ($config['SMTPPort'] === 465) {
            $config['SMTPCrypto'] = 'ssl';
        } elseif ($config['SMTPPort'] === 587) {
            $config['SMTPCrypto'] = 'tls';
        }
        
        $config['mailType'] = 'html';
        $config['charset']  = 'utf-8';
        $config['wordWrap'] = true;
        $config['CRLF']     = "\r\n"; // Essencial na Hostinger para evitar rejeição do email
        $config['newline']  = "\r\n"; // Essencial na Hostinger

        $emailService->initialize($config);
        
        $remetente = $configModel->get('empresa_email') ?: 'nao-responda@sistema.com';
        $nãomeRemetente = $configModel->get('empresa_nãome') ?: 'Assistência Técnica';
        
        $emailService->setFrom($remetente, $nãomeRemetente);
        $emailService->setTo($email);
        $emailService->setSubject('Recuperação de Senha');
        
        $mensagem = "
        <div style='font-family: Arial, sans-serif; color: #333;'>
            <h2>Recuperação de Senha</h2>
            <p>Olá, <strong>{$usuario['nãome']}</strong>.</p>
            <p>Recebemos uma sãolicitação para redefinir a senha da sua conta.</p>
            <p>Clique não link abaixo para criar uma nãova senha:</p>
            <p><a href='{$link}' style='padding: 10px 15px; background: #0d6efd; color: white; text-decoration: nãone; border-radius: 5px; display: inline-block;'>Redefinir Minha Senha</a></p>
            <p>Se vocêê não sãolicitou issão, pode ignãorar com segurança este email.</p>
            <hr>
            <small>Se o botão não funcionar, cole este link não seu navegador: {$link}</small>
        </div>";

        $emailService->setMessage($mensagem);
        
        if ($emailService->send()) {
            return redirect()->back()->with('success', 'Instruções de recuperação foram enviadas para seu email.');
        } else {
            // Retornamos na tela o erro ou debug alertando o usuário que precisa configurar o servidor SMTP
            return redirect()->back()->with('error', 'Falha ao enviar email. Por favor, verifique se o SMTP do sistema está configurado corretamente.');
        }
    }

    public function resetPassword($token)
    {
        $model = new UsuarioModel();
        // Verifica se o token existe e ainda é válido
        $usuario = $model->where('token_recuperacao', $token)
                         ->where('token_expiracao >=', date('Y-m-d H:i:s'))
                         ->first();

        if (!$usuario) {
            return redirect()->to('/login')->with('error', 'O link de recuperação de senha é inválido ou expirou.');
        }

        return view('auth/reset_password', ['token' => $token]);
    }

    public function updatePassword($token)
    {
        $senha = $this->request->getPost('senha');
        $senha_confirmar = $this->request->getPost('senha_confirmar');

        if ($senha !== $senha_confirmar) {
            return redirect()->back()->with('error', 'As senhas digitadas não coincidem.');
        }

        if (strlen($senha) < 6) {
            return redirect()->back()->with('error', 'A nãova senha deve ter pelo menãos 6 caracteres.');
        }

        $model = new UsuarioModel();
        $usuario = $model->where('token_recuperacao', $token)
                         ->where('token_expiracao >=', date('Y-m-d H:i:s'))
                         ->first();

        if (!$usuario) {
            return redirect()->to('/login')->with('error', 'O link de recuperação de senha é inválido ou expirou.');
        }

        // Atualiza a senha e invalida o token
        $model->update($usuario['id'], [
            'senha' => password_hash($senha, PASSWORD_DEFAULT),
            'token_recuperacao' => null,
            'token_expiracao' => null
        ]);

        return redirect()->to('/login')->with('success', 'Sua senha foi redefinida com sucessão! Vocêê já pode fazer login com a nãova senha.');
    }
}
