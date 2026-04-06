<?php

namespace App\Services\Mobile;

class MobilePermissionService
{
    /**
     * Cache por grupo para reduzir consultas repetidas.
     *
     * @var array<int,array<string,array<int,string>>>
     */
    private static array $groupPermissionCache = [];

    /**
     * @param array<string,mixed> $user
     */
    public function userCan(array $user, string $modulo, string $acao = 'visualizar'): bool
    {
        $perfil = strtolower(trim((string) ($user['perfil'] ?? '')));
        if ($perfil === 'admin') {
            return true;
        }

        $grupoId = (int) ($user['grupo_id'] ?? 0);
        if ($grupoId <= 0) {
            return false;
        }

        $permissions = $this->loadPermissionsForGroup($grupoId);
        if (isset($permissions['*']) && in_array('*', $permissions['*'], true)) {
            return true;
        }

        return isset($permissions[$modulo]) && in_array($acao, $permissions[$modulo], true);
    }

    /**
     * @return array<string,array<int,string>>
     */
    private function loadPermissionsForGroup(int $grupoId): array
    {
        if ($grupoId <= 0) {
            return [];
        }

        if (isset(self::$groupPermissionCache[$grupoId])) {
            return self::$groupPermissionCache[$grupoId];
        }

        $db = \Config\Database::connect();
        if (
            !$db->tableExists('grupo_permissoes')
            || !$db->tableExists('modulos')
            || !$db->tableExists('permissoes')
        ) {
            self::$groupPermissionCache[$grupoId] = [];
            return [];
        }

        $rows = $db->table('grupo_permissoes gp')
            ->select('m.slug AS modulo, p.slug AS permissao')
            ->join('modulos m', 'm.id = gp.modulo_id', 'inner')
            ->join('permissoes p', 'p.id = gp.permissao_id', 'inner')
            ->where('gp.grupo_id', $grupoId)
            ->get()
            ->getResultArray();

        $map = [];
        foreach ($rows as $row) {
            $modulo = trim((string) ($row['modulo'] ?? ''));
            $permissao = trim((string) ($row['permissao'] ?? ''));
            if ($modulo === '' || $permissao === '') {
                continue;
            }

            if (!isset($map[$modulo])) {
                $map[$modulo] = [];
            }
            if (!in_array($permissao, $map[$modulo], true)) {
                $map[$modulo][] = $permissao;
            }
        }

        self::$groupPermissionCache[$grupoId] = $map;
        return $map;
    }
}

