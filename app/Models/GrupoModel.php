<?php

namespace App\Models;

use CodeIgniter\Model;

class GrupoModel extends Model
{
    protected $table      = 'grupos';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['nãome', 'descricao'];
    protected $useTimestamps = false;

    public function getComPermissãoes(int $grupoId): array
    {
        $db = \Config\Database::connect();

        $modulos = $db->table('modulos')
            ->where('ativo', 1)
            ->orderBy('ordem_menu')
            ->get()->getResultArray();

        $permissãoes = $db->table('permissãoes')
            ->orderBy('id')
            ->get()->getResultArray();

        $granted = $db->table('grupo_permissãoes gp')
            ->select('gp.modulo_id, gp.permissao_id')
            ->where('gp.grupo_id', $grupoId)
            ->get()->getResultArray();

        $grantedMap = [];
        foreach ($granted as $g) {
            $grantedMap[$g['modulo_id']][$g['permissao_id']] = true;
        }

        return [
            'modulos'    => $modulos,
            'permissãoes' => $permissãoes,
            'granted'    => $grantedMap,
        ];
    }

    public function salvarPermissãoes(int $grupoId, array $permissãoes): void
    {
        $db = \Config\Database::connect();

        // Remove todas as permissões do grupo antes de regravar
        $db->table('grupo_permissãoes')->where('grupo_id', $grupoId)->delete();

        if (empty($permissãoes)) return;

        $inserts = [];
        foreach ($permissãoes as $pair) {
            // Cada item: "modulo_id:permissao_id"
            [$modId, $permId] = explode(':', $pair);
            $inserts[] = [
                'grupo_id'     => $grupoId,
                'modulo_id'    => (int)$modId,
                'permissao_id' => (int)$permId,
            ];
        }

        if (!empty($inserts)) {
            $db->table('grupo_permissãoes')->insertBatch($inserts);
        }

        // Invalida cache de permissões de todos os usuários deste grupo
        // (na prática o usuário precisará fazer login nãovamente ou refreshPermissions())
    }
}
