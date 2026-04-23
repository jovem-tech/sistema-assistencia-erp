<?php

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use App\Models\UsuarioModel;
use App\Services\Mobile\MobilePermissionService;
use CodeIgniter\HTTP\ResponseInterface;

abstract class BaseApiController extends BaseController
{
    private ?array $cachedUser = null;
    private ?MobilePermissionService $permissionService = null;

    /**
     * @param mixed $data
     */
    protected function respondSuccess($data = null, int $statusCode = 200, array $meta = []): ResponseInterface
    {
        return $this->response
            ->setStatusCode($statusCode)
            ->setJSON([
                'status' => 'success',
                'data' => $data,
                'error' => null,
                'meta' => $this->buildMeta($meta),
            ]);
    }

    /**
     * @param mixed $details
     */
    protected function respondError(
        string $message,
        int $statusCode = 400,
        string $code = 'API_ERROR',
        $details = null,
        array $meta = []
    ): ResponseInterface {
        return $this->response
            ->setStatusCode($statusCode)
            ->setJSON([
                'status' => 'error',
                'data' => null,
                'error' => [
                    'code' => $code,
                    'message' => $message,
                    'details' => $details,
                ],
                'meta' => $this->buildMeta($meta),
            ]);
    }

    protected function currentUserId(): int
    {
        return (int) ($_SERVER['MOBILE_AUTH_USER_ID'] ?? 0);
    }

    /**
     * @return array<string,mixed>|null
     */
    protected function currentUser(): ?array
    {
        if ($this->cachedUser !== null) {
            return $this->cachedUser;
        }

        $userId = $this->currentUserId();
        if ($userId <= 0) {
            return null;
        }

        $user = (new UsuarioModel())->find($userId);
        if (!$user) {
            return null;
        }

        $this->cachedUser = $user;
        return $this->cachedUser;
    }

    protected function currentTokenRaw(): string
    {
        return trim((string) ($_SERVER['MOBILE_AUTH_TOKEN_RAW'] ?? ''));
    }

    protected function ensurePermission(string $modulo, string $acao = 'visualizar'): ?ResponseInterface
    {
        $user = $this->currentUser();
        if (!$user || (int) ($user['ativo'] ?? 0) !== 1) {
            return $this->respondError(
                'Usuario nao autenticado.',
                401,
                'AUTH_REQUIRED'
            );
        }

        $service = $this->permissionService ??= new MobilePermissionService();
        if (!$service->userCan($user, $modulo, $acao)) {
            return $this->respondError(
                'Acesso negado para esta operacao.',
                403,
                'FORBIDDEN'
            );
        }

        return null;
    }

    /**
     * @param array<string,mixed> $meta
     * @return array<string,mixed>
     */
    private function buildMeta(array $meta): array
    {
        $requestId = trim((string) $this->request->getHeaderLine('X-Request-Id'));
        if ($requestId === '') {
            $requestId = uniqid('req_', false);
        }

        return array_merge([
            'timestamp' => date('c'),
            'request_id' => $requestId,
        ], $meta);
    }
}

