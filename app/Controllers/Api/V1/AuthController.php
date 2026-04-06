<?php

namespace App\Controllers\Api\V1;

use App\Models\UsuarioModel;
use App\Services\Mobile\ApiTokenService;
use Throwable;

class AuthController extends BaseApiController
{
    public function login()
    {
        try {
            $payload = $this->payload();
            $email = strtolower(trim((string) ($payload['email'] ?? '')));
            $senha = (string) ($payload['password'] ?? $payload['senha'] ?? '');
            $deviceName = trim((string) ($payload['device_name'] ?? 'mobile-app'));

            if ($email === '' || $senha === '') {
                return $this->respondError(
                    'Email e senha sao obrigatorios.',
                    422,
                    'AUTH_LOGIN_VALIDATION'
                );
            }

            $usuario = (new UsuarioModel())->where('email', $email)->first();
            if (!$usuario || (int) ($usuario['ativo'] ?? 0) !== 1 || !password_verify($senha, (string) ($usuario['senha'] ?? ''))) {
                return $this->respondError(
                    'Credenciais invalidas.',
                    401,
                    'AUTH_INVALID_CREDENTIALS'
                );
            }

            $token = (new ApiTokenService())->issueToken((int) $usuario['id'], $deviceName);

            return $this->respondSuccess([
                'access_token' => $token['access_token'],
                'token_type' => 'Bearer',
                'expires_at' => $token['expires_at'],
                'user' => [
                    'id' => (int) $usuario['id'],
                    'nome' => (string) ($usuario['nome'] ?? ''),
                    'email' => (string) ($usuario['email'] ?? ''),
                    'perfil' => (string) ($usuario['perfil'] ?? ''),
                    'grupo_id' => (int) ($usuario['grupo_id'] ?? 0),
                ],
            ]);
        } catch (Throwable $e) {
            log_message('error', '[API V1][AUTH LOGIN] ' . $e->getMessage());
            return $this->respondError(
                'Falha inesperada ao autenticar.',
                500,
                'AUTH_LOGIN_UNEXPECTED'
            );
        }
    }

    public function me()
    {
        $user = $this->currentUser();
        if (!$user || (int) ($user['ativo'] ?? 0) !== 1) {
            return $this->respondError(
                'Usuario nao autenticado.',
                401,
                'AUTH_REQUIRED'
            );
        }

        return $this->respondSuccess([
            'id' => (int) $user['id'],
            'nome' => (string) ($user['nome'] ?? ''),
            'email' => (string) ($user['email'] ?? ''),
            'perfil' => (string) ($user['perfil'] ?? ''),
            'grupo_id' => (int) ($user['grupo_id'] ?? 0),
            'ultimo_acesso' => $user['ultimo_acesso'] ?? null,
        ]);
    }

    public function refresh()
    {
        $user = $this->currentUser();
        if (!$user) {
            return $this->respondError(
                'Usuario nao autenticado.',
                401,
                'AUTH_REQUIRED'
            );
        }

        $tokenService = new ApiTokenService();
        $tokenService->revokeToken($this->currentTokenRaw());
        $token = $tokenService->issueToken((int) $user['id'], 'mobile-refresh');

        return $this->respondSuccess([
            'access_token' => $token['access_token'],
            'token_type' => 'Bearer',
            'expires_at' => $token['expires_at'],
        ]);
    }

    public function logout()
    {
        $revoked = (new ApiTokenService())->revokeToken($this->currentTokenRaw());

        return $this->respondSuccess([
            'revoked' => $revoked,
        ]);
    }

    /**
     * @return array<string,mixed>
     */
    private function payload(): array
    {
        $json = $this->request->getJSON(true);
        if (is_array($json) && !empty($json)) {
            return $json;
        }

        $post = $this->request->getPost();
        return is_array($post) ? $post : [];
    }
}

