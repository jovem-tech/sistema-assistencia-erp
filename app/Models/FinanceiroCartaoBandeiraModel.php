<?php

namespace App\Models;

use CodeIgniter\Model;

class FinanceiroCartaoBandeiraModel extends Model
{
    protected $table = 'financeiro_cartao_bandeiras';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'nome',
        'ordem_exibicao',
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
