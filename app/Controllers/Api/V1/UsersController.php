<?php

namespace App\Controllers\Api\V1;

use App\Models\UsuarioModel;

class UsersController extends BaseApiController
{
    public function index()
    {
        if ($permissionError = $this->ensurePermission('usuarios', 'visualizar')) {
            return $permissionError;
        }

        $q = trim((string) $this->request->getGet('q'));
        $limit = max(1, min(100, (int) ($this->request->getGet('limit') ?? 30)));

        $model = new UsuarioModel();
        $builder = $model->select('id, nome, email, perfil, grupo_id, ativo, ultimo_acesso')
            ->where('ativo', 1);

        if ($q !== '') {
            $builder->groupStart()
                ->like('nome', $q)
                ->orLike('email', $q)
                ->groupEnd();
        }

        $items = $builder->orderBy('nome', 'ASC')->findAll($limit);

        return $this->respondSuccess([
            'items' => array_map(static function (array $row): array {
                return [
                    'id' => (int) ($row['id'] ?? 0),
                    'nome' => (string) ($row['nome'] ?? ''),
                    'email' => (string) ($row['email'] ?? ''),
                    'perfil' => (string) ($row['perfil'] ?? ''),
                    'grupo_id' => (int) ($row['grupo_id'] ?? 0),
                    'ultimo_acesso' => $row['ultimo_acesso'] ?? null,
                ];
            }, $items),
            'count' => count($items),
        ]);
    }
}

