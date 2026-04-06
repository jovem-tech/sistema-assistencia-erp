<?php

namespace App\Models;

use CodeIgniter\Model;

class OsDefeitoModel extends Model
{
    protected $table = 'os_defeitos';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'os_id',
        'defeito_id',
        'legacy_origem',
        'legacy_tabela',
        'legacy_id',
        'created_at',
    ];

    protected $useTimestamps = false;
}
