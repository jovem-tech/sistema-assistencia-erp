<?php

namespace App\Models;

use CodeIgniter\Model;

class MobileNotificationTargetModel extends Model
{
    protected $table = 'mobile_notification_targets';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'notification_id',
        'tipo_alvo',
        'alvo_id',
        'created_at',
    ];
    protected $useTimestamps = false;
}

