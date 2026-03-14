<?php

namespace App\Models;

use CodeIgniter\Model;

class FotoAcessorioModel extends Model
{
    protected $table = 'fotos_acessorios';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = ['acessorio_id', 'arquivo', 'created_at', 'updated_at'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
