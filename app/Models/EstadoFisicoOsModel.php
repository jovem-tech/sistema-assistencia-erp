<?php

namespace App\Models;

use CodeIgniter\Model;

class EstadoFisicoOsModel extends Model
{
    protected $table = 'estado_fisico_equipamento';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = ['os_id', 'descricao_dano', 'tipo', 'valores', 'created_at', 'updated_at'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function deleteByOs(int $osId): void
    {
        $this->where('os_id', $osId)->delete();
    }
}
