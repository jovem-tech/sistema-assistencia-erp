<?php

namespace App\Models;

use CodeIgniter\Model;

class FinanceiroDreSubgrupoModel extends Model
{
    protected $table = 'financeiro_dre_subgrupos';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = ['grupo_id', 'nome', 'descricao', 'ordem_exibicao', 'ativo'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function isTableReady(): bool
    {
        return $this->db->tableExists($this->table);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function getAllWithGroups(bool $onlyActive = false): array
    {
        if (! $this->isTableReady()) {
            return [];
        }

        $builder = $this->select('financeiro_dre_subgrupos.*, financeiro_dre_grupos.nome as grupo_nome')
            ->join('financeiro_dre_grupos', 'financeiro_dre_grupos.id = financeiro_dre_subgrupos.grupo_id', 'left');

        if ($onlyActive) {
            $builder->where('financeiro_dre_subgrupos.ativo', 1)
                ->where('financeiro_dre_grupos.ativo', 1);
        }

        return $builder
            ->orderBy('financeiro_dre_grupos.ordem_exibicao', 'ASC')
            ->orderBy('financeiro_dre_grupos.nome', 'ASC')
            ->orderBy('financeiro_dre_subgrupos.ordem_exibicao', 'ASC')
            ->orderBy('financeiro_dre_subgrupos.nome', 'ASC')
            ->findAll();
    }
}
