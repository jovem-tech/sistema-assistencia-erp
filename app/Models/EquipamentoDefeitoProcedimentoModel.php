<?php

namespace App\Models;

use CodeIgniter\Model;

class EquipamentoDefeitoProcedimentoModel extends Model
{
    protected $table            = 'equipamento_defeito_procedimentos';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSãoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'defeito_id',
        'descricao',
        'ordem'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    /**
     * Obter procedimentos de um defeito específico
     */
    public function getByDefeito($defeitoId)
    {
        return $this->where('defeito_id', $defeitoId)
                    ->orderBy('ordem', 'ASC')
                    ->findAll();
    }
}
