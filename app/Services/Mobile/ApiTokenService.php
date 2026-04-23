<?php

namespace App\Services\Mobile;

use App\Models\MobileApiTokenModel;
use App\Models\UsuarioModel;

class ApiTokenService
{
    private MobileApiTokenModel $tokenModel;
    private UsuarioModel $usuarioModel;

    public function __construct()
    {
        $this->tokenModel = new MobileApiTokenModel();
        $this->usuarioModel = new UsuarioModel();
    }

    /**
     * @return array{access_token:string,expires_at:string,token_name:string}
     */
    public function issueToken(int $usuarioId, string $tokenName = 'mobile', int $ttlHours = 720): array
    {
        $plainToken = 'mb_' . bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $plainToken);

        $expiresAt = date('Y-m-d H:i:s', time() + max(1, $ttlHours) * 3600);

        $this->tokenModel->insert([
            'usuario_id' => $usuarioId,
            'token_hash' => $tokenHash,
            'token_name' => trim($tokenName) !== '' ? trim($tokenName) : 'mobile',
            'expira_em' => $expiresAt,
            'ultimo_uso_em' => date('Y-m-d H:i:s'),
        ]);

        return [
            'access_token' => $plainToken,
            'expires_at' => $expiresAt,
            'token_name' => $tokenName,
        ];
    }

    /**
     * @return array{token:array<string,mixed>,user:array<string,mixed>}|null
     */
    public function validateToken(?string $plainToken): ?array
    {
        $plainToken = trim((string) $plainToken);
        if ($plainToken === '') {
            return null;
        }

        $tokenHash = hash('sha256', $plainToken);
        $token = $this->tokenModel
            ->where('token_hash', $tokenHash)
            ->where('revogado_em', null)
            ->first();

        if (!$token) {
            return null;
        }

        $expiresAt = trim((string) ($token['expira_em'] ?? ''));
        if ($expiresAt !== '' && strtotime($expiresAt) !== false && strtotime($expiresAt) < time()) {
            $this->tokenModel->update((int) $token['id'], ['revogado_em' => date('Y-m-d H:i:s')]);
            return null;
        }

        $user = $this->usuarioModel->find((int) ($token['usuario_id'] ?? 0));
        if (!$user || (int) ($user['ativo'] ?? 0) !== 1) {
            return null;
        }

        $lastUseRaw = trim((string) ($token['ultimo_uso_em'] ?? ''));
        $shouldTouch = true;
        if ($lastUseRaw !== '' && strtotime($lastUseRaw) !== false) {
            $shouldTouch = (time() - strtotime($lastUseRaw)) >= 30;
        }
        if ($shouldTouch) {
            $this->tokenModel->update((int) $token['id'], ['ultimo_uso_em' => date('Y-m-d H:i:s')]);
        }

        return [
            'token' => $token,
            'user' => $user,
        ];
    }

    public function revokeToken(?string $plainToken): bool
    {
        $plainToken = trim((string) $plainToken);
        if ($plainToken === '') {
            return false;
        }

        $tokenHash = hash('sha256', $plainToken);
        $token = $this->tokenModel
            ->where('token_hash', $tokenHash)
            ->where('revogado_em', null)
            ->first();

        if (!$token) {
            return false;
        }

        $this->tokenModel->update((int) $token['id'], ['revogado_em' => date('Y-m-d H:i:s')]);
        return true;
    }

    public function revokeAllForUser(int $usuarioId): void
    {
        if ($usuarioId <= 0) {
            return;
        }

        $this->tokenModel
            ->where('usuario_id', $usuarioId)
            ->where('revogado_em', null)
            ->set(['revogado_em' => date('Y-m-d H:i:s')])
            ->update();
    }
}

