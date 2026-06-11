<?php

namespace App\Models;

use CodeIgniter\Model;

class FinanceiroCartaoOperadoraModel extends Model
{
    protected $table = 'financeiro_cartao_operadoras';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'nome',
        'descricao',
        'ordem_exibicao',
        'prazo_padrao_dias',
        'ativo',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function ativos(): array
    {
        if (! $this->db->tableExists($this->table)) {
            return [];
        }

        return $this->where('ativo', 1)
            ->orderBy('ordem_exibicao', 'ASC')
            ->orderBy('nome', 'ASC')
            ->findAll();
    }
}
