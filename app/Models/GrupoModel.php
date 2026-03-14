<?php

namespace App\Models;

use CodeIgniter\Model;

class GrupoModel extends Model
{
    protected $table      = 'grupos';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['nome', 'descricao'];
    protected $useTimestamps = false;

    public function getComPermissoes(int $grupoId): array
    {
        $db = \Config\Database::connect();

        $modulos = $db->table('modulos')
            ->where('ativo', 1)
            ->orderBy('ordem_menu')
            ->get()->getResultArray();

        $permissoes = $db->table('permissoes')
            ->orderBy('id')
            ->get()->getResultArray();

        $granted = $db->table('grupo_permissoes gp')
            ->select('gp.modulo_id, gp.permissao_id')
            ->where('gp.grupo_id', $grupoId)
            ->get()->getResultArray();

        $grantedMap = [];
        foreach ($granted as $g) {
            $grantedMap[$g['modulo_id']][$g['permissao_id']] = true;
        }

        return [
            'modulos'    => $modulos,
            'permissoes' => $permissoes,
            'granted'    => $grantedMap,
        ];
    }

    public function salvarPermissoes(int $grupoId, array $permissoes): void
    {
        $db = \Config\Database::connect();

        // Remove todas as permissões do grupo antes de regravar
        $db->table('grupo_permissoes')->where('grupo_id', $grupoId)->delete();

        if (empty($permissoes)) return;

        $inserts = [];
        foreach ($permissoes as $pair) {
            // Cada item: "modulo_id:permissao_id"
            [$modId, $permId] = explode(':', $pair);
            $inserts[] = [
                'grupo_id'     => $grupoId,
                'modulo_id'    => (int)$modId,
                'permissao_id' => (int)$permId,
            ];
        }

        if (!empty($inserts)) {
            $db->table('grupo_permissoes')->insertBatch($inserts);
        }

        // Invalida cache de permissões de todos os usuários deste grupo
        // (na prática o usuário precisará fazer login novamente ou refreshPermissions())
    }
}
