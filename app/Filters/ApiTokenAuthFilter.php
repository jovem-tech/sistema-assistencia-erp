<?php

namespace App\Filters;

use App\Services\Mobile\ApiTokenService;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

class ApiTokenAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $header = trim((string) $request->getHeaderLine('Authorization'));
        $plainToken = '';
        if (preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            $plainToken = trim((string) ($matches[1] ?? ''));
        }

        // Fallback opcional para EventSource/SSE (nao aceita Authorization header em todos browsers).
        if ($plainToken === '') {
            $plainToken = trim((string) ($request->getGet('access_token') ?? ''));
        }

        if ($plainToken === '') {
            return $this->unauthorizedResponse('Token Bearer nao informado.', 'AUTH_TOKEN_MISSING');
        }

        $auth = (new ApiTokenService())->validateToken($plainToken);
        if ($auth === null) {
            return $this->unauthorizedResponse('Sessao mobile expirada ou invalida.', 'AUTH_TOKEN_EXPIRED');
        }

        $_SERVER['MOBILE_AUTH_USER_ID'] = (string) ((int) ($auth['user']['id'] ?? 0));
        $_SERVER['MOBILE_AUTH_USER_EMAIL'] = (string) ($auth['user']['email'] ?? '');
        $_SERVER['MOBILE_AUTH_TOKEN_RAW'] = $plainToken;
        $_SERVER['MOBILE_AUTH_TOKEN_ID'] = (string) ((int) ($auth['token']['id'] ?? 0));

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }

    private function unauthorizedResponse(string $message, string $code): ResponseInterface
    {
        return Services::response()
            ->setStatusCode(401)
            ->setJSON([
                'status' => 'error',
                'data' => null,
                'error' => [
                    'code' => $code,
                    'message' => $message,
                ],
                'meta' => [
                    'timestamp' => date('c'),
                ],
            ]);
    }
}
