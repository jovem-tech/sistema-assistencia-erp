<?php

namespace App\Models;

use CodeIgniter\Model;

class MobilePushSubscriptionModel extends Model
{
    protected $table = 'mobile_push_subscriptions';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'usuario_id',
        'endpoint_hash',
        'endpoint',
        'chave_p256dh',
        'chave_auth',
        'user_agent',
        'device_label',
        'ativo',
        'ultimo_ping_em',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}

