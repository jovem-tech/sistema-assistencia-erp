<?php

namespace App\Models;

use CodeIgniter\Model;

class FinanceiroMovimentoCartaoModel extends Model
{
    protected $table = 'financeiro_movimentos_cartao';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'movimento_id',
        'operadora_id',
        'bandeira_id',
        'taxa_id',
        'modalidade',
        'parcelas',
        'valor_bruto',
        'taxa_percentual',
        'taxa_fixa',
        'valor_taxa',
        'valor_liquido',
        'prazo_recebimento_dias',
        'data_prevista_recebimento',
        'observacoes',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
