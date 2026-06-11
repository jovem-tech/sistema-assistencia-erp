<?php

namespace App\Models;

use CodeIgniter\Model;

class FinanceiroCartaoTaxaModel extends Model
{
    protected $table = 'financeiro_cartao_taxas';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'operadora_id',
        'bandeira_id',
        'modalidade',
        'parcelas_inicial',
        'parcelas_final',
        'taxa_percentual',
        'taxa_fixa',
        'prazo_recebimento_dias',
        'observacoes',
        'ativo',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function ativas(): array
    {
        if (! $this->db->tableExists($this->table)) {
            return [];
        }

        return $this->select('financeiro_cartao_taxas.*')
            ->where('financeiro_cartao_taxas.ativo', 1)
            ->orderBy('financeiro_cartao_taxas.operadora_id', 'ASC')
            ->orderBy('financeiro_cartao_taxas.modalidade', 'ASC')
            ->orderBy('financeiro_cartao_taxas.parcelas_inicial', 'ASC')
            ->findAll();
    }
}
