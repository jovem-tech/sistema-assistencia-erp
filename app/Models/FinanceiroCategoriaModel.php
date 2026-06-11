<?php

namespace App\Models;

use CodeIgniter\Model;

class FinanceiroCategoriaModel extends Model
{
    protected $table = 'financeiro_categorias';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'nome',
        'tipo',
        'dre_grupo_id',
        'dre_subgrupo_id',
        'impacta_dre_padrao',
        'impacta_fluxo_caixa_padrao',
        'dre_fixo_mensal_padrao',
        'ordem_exibicao',
        'ativo',
    ];
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
    public function getAllComDefaults(bool $onlyActive = false): array
    {
        if (! $this->isTableReady()) {
            return [];
        }

        $builder = $this->select(
            'financeiro_categorias.*,
            fg.nome as dre_grupo_nome,
            fs.nome as dre_subgrupo_nome',
            false
        )
            ->join('financeiro_dre_grupos fg', 'fg.id = financeiro_categorias.dre_grupo_id', 'left')
            ->join('financeiro_dre_subgrupos fs', 'fs.id = financeiro_categorias.dre_subgrupo_id', 'left');

        if ($onlyActive) {
            $builder->where('financeiro_categorias.ativo', 1);
        }

        return $builder
            ->orderBy('financeiro_categorias.ordem_exibicao', 'ASC')
            ->orderBy('financeiro_categorias.nome', 'ASC')
            ->findAll();
    }
}
