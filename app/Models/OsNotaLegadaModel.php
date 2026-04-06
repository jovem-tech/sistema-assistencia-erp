<?php

namespace App\Models;

use CodeIgniter\Model;

class OsNotaLegadaModel extends Model
{
    protected $table = 'os_notas_legadas';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'os_id',
        'legacy_origem',
        'legacy_tabela',
        'legacy_id',
        'titulo',
        'conteudo',
        'created_at',
    ];

    protected $useTimestamps = false;
}
