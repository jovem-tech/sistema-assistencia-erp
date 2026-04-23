<?php

namespace App\Models;

use CodeIgniter\Model;

class PrecificacaoServicoOverrideModel extends Model
{
    protected $table            = 'precificacao_servico_overrides';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'servico_id',
        'custo_hora_produtiva',
        'custos_diretos_total',
        'margem_percentual',
        'taxa_recebimento_percentual',
        'imposto_percentual',
        'tempo_tecnico_horas',
        'risco_percentual',
        'preco_tabela_referencia',
        'custos_fixos_mensais',
        'tecnicos_ativos',
        'horas_produtivas_dia',
        'dias_uteis_mes',
        'consumiveis_valor',
        'tempo_indireto_horas',
        'reserva_garantia_valor',
        'perdas_pequenas_valor',
        'tempo_desmontagem_min',
        'tempo_substituicao_min',
        'tempo_montagem_min',
        'tempo_teste_final_min',
        'ativo',
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
    public function getAtivos(): array
    {
        if (! $this->isTableReady()) {
            return [];
        }

        return $this->where('ativo', 1)
            ->orderBy('servico_id', 'ASC')
            ->findAll();
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    public function getMapaPorServicoId(): array
    {
        $map = [];
        foreach ($this->getAtivos() as $row) {
            $servicoId = (int) ($row['servico_id'] ?? 0);
            if ($servicoId <= 0) {
                continue;
            }
            $map[(string) $servicoId] = $row;
        }

        return $map;
    }

    /**
     * @return array<string,mixed>|null
     */
    public function getAtivoByServicoId(int $servicoId): ?array
    {
        if ($servicoId <= 0 || ! $this->isTableReady()) {
            return null;
        }

        $row = $this->where('servico_id', $servicoId)
            ->where('ativo', 1)
            ->first();

        return is_array($row) ? $row : null;
    }
}
