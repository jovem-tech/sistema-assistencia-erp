<?php

namespace App\Models;

use CodeIgniter\Model;

class MobileNotificationModel extends Model
{
    protected $table = 'mobile_notifications';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'usuario_id',
        'tipo_evento',
        'titulo',
        'corpo',
        'rota_destino',
        'payload_json',
        'lida_em',
        'enviada_push_em',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}

