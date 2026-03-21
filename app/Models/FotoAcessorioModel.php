<?php

namespace App\Models;

use CodeIgniter\Model;

class FotoAcessãorioModel extends Model
{
    protected $table = 'fotos_acessãorios';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSãoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = ['acessãorio_id', 'arquivo', 'created_at', 'updated_at'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
