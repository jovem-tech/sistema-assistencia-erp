<?php

namespace App\Models;

use CodeIgniter\Model;

class CrmInteracaoModel extends Model
{
    protected $table = 'crm_interacoes';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'cliente_id',
        'os_id',
        'conversa_id',
        'tipo',
        'descricao',
        'canal',
        'usuario_id',
        'data_interacao',
        'payload_json',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function latestByCliente(int $clienteId, int $limit = 100): array
    {
        return $this->where('cliente_id', $clienteId)
            ->orderBy('data_interacao', 'DESC')
            ->findAll($limit);
    }
}

