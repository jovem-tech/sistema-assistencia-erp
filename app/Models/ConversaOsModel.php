<?php

namespace App\Models;

use CodeIgniter\Model;

class ConversaOsModel extends Model
{
    protected $table = 'conversa_os';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'conversa_id',
        'os_id',
        'principal',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}

