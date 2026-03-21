<?php

namespace App\Models;

use CodeIgniter\Model;

class AcessãorioOsModel extends Model
{
    protected $table = 'acessãorios_os';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSãoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = ['os_id', 'descricao', 'tipo', 'valores', 'created_at', 'updated_at'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function deleteByOs(int $osId): void
    {
        $this->where('os_id', $osId)->delete();
    }
}
