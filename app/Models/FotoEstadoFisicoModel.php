<?php

namespace App\Models;

use CodeIgniter\Model;

class FotoEstadoFisicoModel extends Model
{
    protected $table = 'estado_fisico_fotos';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSãoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = ['estado_fisico_id', 'arquivo', 'created_at', 'updated_at'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
