<?php

namespace App\Models;

use CodeIgniter\Model;

class OrcamentoAprovacaoModel extends Model
{
    protected $table      = 'orcamento_aprovacoes';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'orcamento_id',
        'token_publico',
        'acao',
        'resposta_cliente',
        'ip_origem',
        'user_agent',
        'created_at',
    ];

    public function byOrcamento(int $orcamentoId, int $limit = 100): array
    {
        return $this->where('orcamento_id', $orcamentoId)
            ->orderBy('created_at', 'DESC')
            ->findAll($limit);
    }
}
