<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        
        if (!$session->get('logged_in')) {
            helper('cookie');
            $rememberCookie = get_cookie('remember_login');
            
            if ($rememberCookie) {
                $parts = explode('|', $rememberCookie);
                if (count($parts) === 2) {
                    $model = new \App\Models\UsuarioModel();
                    $usuario = $model->find($parts[0]);
                    
                    if ($usuario && $usuario['ativo'] && hash('sha256', $usuario['senha'] . $usuario['email']) === $parts[1]) {
                        
                        $grupoNome = '';
                        if (!empty($usuario['grupo_id'])) {
                            $db = \Config\Database::connect();
                            $grupo = $db->table('grupos')->where('id', $usuario['grupo_id'])->get()->getRowArray();
                            $grupoNome = $grupo['nome'] ?? '';
                        }
                        
                        $sessionData = [
                            'user_id'         => $usuario['id'],
                            'user_nome'       => $usuario['nome'],
                            'user_email'      => $usuario['email'],
                            'user_perfil'     => $usuario['perfil'],
                            'user_foto'       => $usuario['foto'],
                            'user_grupo_id'   => $usuario['grupo_id'] ?? null,
                            'user_grupo_nome' => $grupoNome,
                            'logged_in'       => true,
                            'last_activity'   => time(),
                        ];
                        
                        $session->set($sessionData);
                        $model->update($usuario['id'], ['ultimo_acesso' => date('Y-m-d H:i:s')]);
                        
                        return; // Sessão restaurada com sucesso, pode continuar acessando a rota
                    }
                }
            }

            return redirect()->to('/login')
                ->with('error', 'Você precisa fazer login para acessar esta página.');
        }

        // Check for session timeout (30 minutes of inactivity)
        $lastActivity = $session->get('last_activity');
        
        helper('cookie');
        // Se o cookie lembrar-me existir, ignoramos o tempo de inatividade da sessão e o resetamos
        if (get_cookie('remember_login')) {
            $session->set('last_activity', time());
            return;
        }

        if ($lastActivity && (time() - $lastActivity) > 1800) {
            $session->destroy();
            return redirect()->to('/login')
                ->with('error', 'Sua sessão expirou por inatividade.');
        }

        $session->set('last_activity', time());
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Nothing to do here
    }
}
