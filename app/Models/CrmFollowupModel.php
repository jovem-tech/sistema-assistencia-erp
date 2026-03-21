<?php

namespace App\Models;

use CodeIgniter\Model;

class CrmFollowupModel extends Model
{
    protected $table = 'crm_followups';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'cliente_id',
        'os_id',
        'titulo',
        'descricao',
        'data_prevista',
        'status',
        'usuario_responsavel',
        'origem_evento',
        'concluido_em',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function pendentes(int $limit = 100): array
    {
        return $this->where('status', 'pendente')
            ->orderBy('data_prevista', 'ASC')
            ->findAll($limit);
    }
}

