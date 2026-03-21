<?php

namespace App\Models;

use CodeIgniter\Model;

class CrmEventoModel extends Model
{
    protected $table = 'crm_eventos';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'cliente_id',
        'equipamento_id',
        'os_id',
        'conversa_id',
        'tipo_evento',
        'titulo',
        'descricao',
        'origem',
        'usuario_id',
        'data_evento',
        'payload_json',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function timelineByCliente(int $clienteId, int $limit = 200): array
    {
        return $this->where('cliente_id', $clienteId)
            ->orderBy('data_evento', 'DESC')
            ->findAll($limit);
    }
}

