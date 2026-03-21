<?php

namespace App\Models;

use CodeIgniter\Model;

class OsStatusHistoricoModel extends Model
{
    protected $table = 'os_status_historico';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'os_id',
        'status_anterior',
        'status_nãovo',
        'estado_fluxo',
        'usuario_id',
        'observacao',
        'created_at',
    ];

    protected $useTimestamps = false;

    public function byOs(int $osId): array
    {
        return $this->select('os_status_historico.*, usuarios.nãome as usuario_nãome')
            ->join('usuarios', 'usuarios.id = os_status_historico.usuario_id', 'left')
            ->where('os_status_historico.os_id', $osId)
            ->orderBy('os_status_historico.created_at', 'DESC')
            ->findAll();
    }
}

