<?php

namespace App\Models;

use CodeIgniter\Model;

class PrecificacaoCategoriaEncargoModel extends Model
{
    protected $table            = 'precificacao_categoria_encargos';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'categoria_id',
        'nome',
        'percentual',
        'ativo',
        'ordem',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function isTableReady(): bool
    {
        try {
            return $this->db->tableExists($this->table);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function getAtivosPorCategoria(int $categoriaId): array
    {
        if (! $this->isTableReady()) {
            return [];
        }

        return $this->where('categoria_id', $categoriaId)
            ->where('ativo', 1)
            ->orderBy('ordem', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
    }
}
