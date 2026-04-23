<?php

namespace App\Models;

use CodeIgniter\Model;

class EquipamentoModeloModel extends Model
{
    protected $table = 'equipamentos_modelos';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = ['marca_id', 'nome', 'ativo'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'marca_id' => 'required|integer',
        'nome'     => 'required|max_length[100]'
    ];
    
    public function getWithMarca()
    {
        return $this->select('equipamentos_modelos.*, equipamentos_marcas.nome as marca_nome')
                    ->join('equipamentos_marcas', 'equipamentos_marcas.id = equipamentos_modelos.marca_id')
                    ->findAll();
    }
}
