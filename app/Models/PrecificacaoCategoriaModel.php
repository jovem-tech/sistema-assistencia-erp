<?php

namespace App\Models;

use CodeIgniter\Model;

class PrecificacaoCategoriaModel extends Model
{
    protected $table            = 'precificacao_categorias';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'tipo',
        'categoria_nome',
        'encargos_percentual',
        'margem_percentual',
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
    public function getAtivosPorTipo(string $tipo): array
    {
        if (! $this->isTableReady()) {
            return [];
        }

        return $this->where('tipo', $tipo)
            ->where('ativo', 1)
            ->orderBy('ordem', 'ASC')
            ->orderBy('categoria_nome', 'ASC')
            ->findAll();
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    public function getMapaPorTipo(string $tipo): array
    {
        if (! $this->isTableReady()) {
            return [];
        }

        $rows = $this->where('tipo', $tipo)->where('ativo', 1)->findAll();
        $map = [];
        foreach ($rows as $row) {
            $nomeRaw = trim((string) ($row['categoria_nome'] ?? ''));
            $nome = function_exists('mb_strtolower') ? mb_strtolower($nomeRaw) : strtolower($nomeRaw);
            if ($nome === '') {
                continue;
            }
            $map[$nome] = $row;
        }

        return $map;
    }
}
