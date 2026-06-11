<?php

namespace App\Models;

use CodeIgniter\Model;

class FinanceiroDreGrupoModel extends Model
{
    protected $table = 'financeiro_dre_grupos';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = ['nome', 'descricao', 'ordem_exibicao', 'ativo'];
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
    public function getActiveForSelect(): array
    {
        if (! $this->isTableReady()) {
            return [];
        }

        return $this->where('ativo', 1)
            ->orderBy('ordem_exibicao', 'ASC')
            ->orderBy('nome', 'ASC')
            ->findAll();
    }
}
