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

        helper(['cookie', 'sistema']);

        if (!$session->get('logged_in')) {
            if ($this->restoreRememberedSession($session)) {
                return;
            }

            return $this->buildUnauthenticatedResponse(
                $request,
                'Sua sessao nao esta mais ativa. Faca login novamente.'
            );
        }

        if (get_cookie('remember_login')) {
            $session->set('last_activity', time());
            return;
        }

        $lastActivity = (int) $session->get('last_activity');
        $timeoutSeconds = get_session_inactivity_seconds(30);

        if ($lastActivity > 0 && (time() - $lastActivity) > $timeoutSeconds) {
            $session->destroy();

            return $this->buildExpiredResponse(
                $request,
                'Sua sessao expirou por inatividade. Faca login novamente.'
            );
        }

        $session->set('last_activity', time());
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Nothing to do here
    }

    private function restoreRememberedSession($session): bool
    {
        $rememberCookie = get_cookie('remember_login');
        if (!$rememberCookie) {
            return false;
        }

        $parts = explode('|', $rememberCookie);
        if (count($parts) !== 2) {
            return false;
        }

        $model = new \App\Models\UsuarioModel();
        $usuario = $model->find($parts[0]);

        if (!$usuario || !$usuario['ativo']) {
            return false;
        }

        $hash = hash('sha256', $usuario['senha'] . $usuario['email']);
        if (!hash_equals($hash, (string) $parts[1])) {
            return false;
        }

        $grupoNome = '';
        if (!empty($usuario['grupo_id'])) {
            $db = \Config\Database::connect();
            $grupo = $db->table('grupos')->where('id', $usuario['grupo_id'])->get()->getRowArray();
            $grupoNome = $grupo['nome'] ?? '';
        }

        $session->set([
            'user_id'         => $usuario['id'],
            'user_nome'       => $usuario['nome'],
            'user_email'      => $usuario['email'],
            'user_perfil'     => $usuario['perfil'],
            'user_foto'       => $usuario['foto'],
            'user_grupo_id'   => $usuario['grupo_id'] ?? null,
            'user_grupo_nome' => $grupoNome,
            'logged_in'       => true,
            'last_activity'   => time(),
        ]);

        $model->update($usuario['id'], ['ultimo_acesso' => date('Y-m-d H:i:s')]);

        return true;
    }

    private function buildUnauthenticatedResponse(RequestInterface $request, string $message)
    {
        if ($this->expectsJson($request)) {
            return $this->jsonResponse($message, false);
        }

        return redirect()->to('/login')->with('error', $message);
    }

    private function buildExpiredResponse(RequestInterface $request, string $message)
    {
        if ($this->expectsJson($request)) {
            return $this->jsonResponse($message, true);
        }

        return redirect()->to('/login')->with('error', $message);
    }

    private function expectsJson(RequestInterface $request): bool
    {
        $accept = strtolower($request->getHeaderLine('Accept'));
        $requestedWith = strtolower($request->getHeaderLine('X-Requested-With'));

        return $request->isAJAX()
            || $requestedWith === 'xmlhttprequest'
            || str_contains($accept, 'application/json');
    }

    private function jsonResponse(string $message, bool $expired): ResponseInterface
    {
        return service('response')
            ->setStatusCode(401)
            ->setJSON([
                'ok' => false,
                'auth_required' => true,
                'session_expired' => $expired,
                'message' => $message,
                'redirect_url' => base_url('login'),
            ]);
    }
}
