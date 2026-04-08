<?php

namespace App\Models;

use CodeIgniter\Model;

class OrcamentoStatusHistoricoModel extends Model
{
    protected $table      = 'orcamento_status_historico';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'orcamento_id',
        'status_anterior',
        'status_novo',
        'observacao',
        'origem',
        'alterado_por',
        'created_at',
    ];

    public function timeline(int $orcamentoId, int $limit = 100): array
    {
        return $this->select('orcamento_status_historico.*, usuarios.nome as usuario_nome')
            ->join('usuarios', 'usuarios.id = orcamento_status_historico.alterado_por', 'left')
            ->where('orcamento_status_historico.orcamento_id', $orcamentoId)
            ->orderBy('orcamento_status_historico.created_at', 'DESC')
            ->findAll($limit);
    }
}
