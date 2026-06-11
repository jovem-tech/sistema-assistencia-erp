<?php

namespace App\Models;

use CodeIgniter\Model;

class FinanceiroMovimentoModel extends Model
{
    protected $table = 'financeiro_movimentos';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'financeiro_id',
        'tipo_movimento',
        'data_movimento',
        'valor_movimento',
        'forma_pagamento',
        'documento_ref',
        'observacoes',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
