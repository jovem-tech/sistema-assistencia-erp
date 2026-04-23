<?php

namespace App\Models;

use CodeIgniter\Model;

class WhatsappInboundModel extends Model
{
    protected $table = 'whatsapp_inbound';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'provedor',
        'remetente',
        'conteudo',
        'payload',
        'processado',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
