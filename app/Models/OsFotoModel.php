<?php

namespace App\Models;

use CodeIgniter\Model;

class OsFotoModel extends Model
{
    protected $table            = 'os_fotos';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'os_id', 'tipo', 'arquivo', 'created_at'
    ];
    protected $useTimestamps    = false;

    public function getByOs($os_id, $tipo = null)
    {
        $q = $this->where('os_id', $os_id);
        if ($tipo) $q = $q->where('tipo', $tipo);
        return $q->orderBy('id', 'ASC')->findAll();
    }
}
