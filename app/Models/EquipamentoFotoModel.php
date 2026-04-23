<?php

namespace App\Models;

use CodeIgniter\Model;

class EquipamentoFotoModel extends Model
{
    protected $table = 'equipamentos_fotos';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'equipamento_id', 'arquivo', 'is_principal', 'created_at'
    ];
    
    // Configurações para datas
    protected $useTimestamps = false; 
    // Usaremos criado manualmente ou pelo default do banco
}
