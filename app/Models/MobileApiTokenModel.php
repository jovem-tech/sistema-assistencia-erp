<?php

namespace App\Models;

use CodeIgniter\Model;

class MobileApiTokenModel extends Model
{
    protected $table = 'mobile_api_tokens';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'usuario_id',
        'token_hash',
        'token_name',
        'scope',
        'ultimo_uso_em',
        'expira_em',
        'revogado_em',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}

