<?php

namespace App\Models;

use CodeIgniter\Model;

class PrecificacaoParametroModel extends Model
{
    protected $table            = 'precificacao_parametros';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'categoria',
        'secao',
        'codigo',
        'nome',
        'descricao',
        'unidade',
        'tipo_dado',
        'tipo_entrada',
        'formula',
        'valor',
        'valor_padrao',
        'minimo',
        'maximo',
        'editavel',
        'origem',
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
    public function getAtivosPorCategoria(string $categoria): array
    {
        if (! $this->isTableReady()) {
            return [];
        }

        return $this->where('categoria', $categoria)
            ->where('ativo', 1)
            ->orderBy('secao', 'ASC')
            ->orderBy('ordem', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    public function getMapaPorCodigo(): array
    {
        if (! $this->isTableReady()) {
            return [];
        }

        $rows = $this->where('ativo', 1)->findAll();
        $map = [];
        foreach ($rows as $row) {
            $codigo = trim((string) ($row['codigo'] ?? ''));
            if ($codigo === '') {
                continue;
            }
            $map[$codigo] = $row;
        }

        return $map;
    }

    public function updateValorByCodigo(string $codigo, float $valor): void
    {
        if (! $this->isTableReady()) {
            return;
        }

        $this->where('codigo', $codigo)->set([
            'valor' => round($valor, 4),
            'updated_at' => date('Y-m-d H:i:s'),
        ])->update();
    }
}

