<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * PermissionFilter — Filtro de autorização RBAC
 *
 * Interpreta o argumento passado na rota como "modulo:acao"
 * e valida via can() do sistema_helper.
 *
 * Uso em Routes.php:
 *   $routes->get('financeiro', 'Financeiro::index', ['filter' => 'permission:financeiro:visualizar']);
 *
 * Múltiplas permissões com vírgula:
 *   $routes->post('financeiro/salvar', 'Financeiro::store', ['filter' => 'permission:financeiro:criar']);
 */
class PermissionFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Carrega helper caso ainda não esteja
        if (!function_exists('can')) {
            helper('sistema');
        }

        // Não autenticado → login
        if (!session()->get('logged_in')) {
            return redirect()->to(base_url('login'))
                             ->with('error', 'Faça login para continuar.');
        }

        // Sem argumento → apenas autenticação é suficiente
        if (empty($arguments)) {
            return;
        }

        // Argumento: "modulo:acao"
        $parts  = explode(':', $arguments[0]);
        $modulo = $parts[0] ?? null;
        $acao   = $parts[1] ?? 'visualizar';

        if ($modulo && !can($modulo, $acao)) {
            // Requisição AJAX → JSON 403
            if ($request->isAJAX()) {
                return service('response')
                    ->setStatusCode(403)
                    ->setJSON(['error' => 'Acesso negado. Permissão insuficiente.']);
            }

            // Requisição normal → redirect com flash
            session()->setFlashdata(
                'error',
                "Acesso negado. Você não tem permissão para executar esta ação (<strong>{$acao}</strong>) no módulo <strong>{$modulo}</strong>."
            );

            // Log de tentativa
            try {
                \App\Models\LogModel::registrar(
                    'acesso_negado',
                    "Tentativa não autorizada: módulo={$modulo}, ação={$acao}, url=" . $request->getUri()->getPath()
                );
            } catch (\Throwable $e) {}

            return redirect()->to(base_url('dashboard'));
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Nada a fazer após a resposta
    }
}
