<?php

namespace App\Models;

use CodeIgniter\Model;

class OsStatusTransicaoModel extends Model
{
    protected $table = 'os_status_transicoes';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'status_origem_id',
        'status_destino_id',
        'ativo',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}

