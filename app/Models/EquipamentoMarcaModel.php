<?php

namespace App\Models;

use CodeIgniter\Model;

class EquipamentoMarcaModel extends Model
{
    protected $table = 'equipamentos_marcas';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = ['nome', 'ativo'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'nome' => 'required|max_length[100]|is_unique[equipamentos_marcas.nome,id,{id}]'
    ];
}
