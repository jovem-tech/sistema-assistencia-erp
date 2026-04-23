<?php

namespace App\Models;

use CodeIgniter\Model;

class MobileEventOutboxModel extends Model
{
    protected $table = 'mobile_event_outbox';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'event_key',
        'event_type',
        'aggregate_type',
        'aggregate_id',
        'payload_json',
        'status',
        'tentativas',
        'disponivel_em',
        'processado_em',
        'ultimo_erro',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}

