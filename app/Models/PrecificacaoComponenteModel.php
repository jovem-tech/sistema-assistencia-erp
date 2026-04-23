<?php

namespace App\Models;

use CodeIgniter\Model;

class PrecificacaoComponenteModel extends Model
{
    protected $table            = 'precificacao_componentes';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'grupo',
        'nome',
        'tipo_valor',
        'valor',
        'origem',
        'ativo',
        'ordem',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * @return array<int,array<string,mixed>>
     */
    public function getAtivosPorGrupo(string $grupo, ?string $tipoValor = null): array
    {
        if (! $this->isTableReady()) {
            return [];
        }

        $builder = $this->where('grupo', $grupo)
            ->where('ativo', 1)
            ->orderBy('ordem', 'ASC')
            ->orderBy('id', 'ASC');

        if ($tipoValor !== null) {
            $builder->where('tipo_valor', $tipoValor);
        }

        return $builder->findAll();
    }

    public function somarValorAtivoPorGrupo(string $grupo, ?string $tipoValor = null): float
    {
        if (! $this->isTableReady()) {
            return 0.0;
        }

        $builder = $this->selectSum('valor', 'soma')
            ->where('grupo', $grupo)
            ->where('ativo', 1);

        if ($tipoValor !== null) {
            $builder->where('tipo_valor', $tipoValor);
        }

        $row = $builder->first();
        return (float) ($row['soma'] ?? 0);
    }

    public function isTableReady(): bool
    {
        try {
            return $this->db->tableExists($this->table);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
