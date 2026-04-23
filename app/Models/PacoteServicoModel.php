<?php

namespace App\Models;

use CodeIgniter\Model;

class PacoteServicoModel extends Model
{
    protected $table = 'pacotes_servicos';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'nome',
        'categoria',
        'tipo_equipamento',
        'servico_referencia_id',
        'descricao',
        'metodologia_origem',
        'ordem_apresentacao',
        'ativo',
    ];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function withResumoNiveis(): array
    {
        $rows = $this->orderBy('ordem_apresentacao', 'ASC')
            ->orderBy('nome', 'ASC')
            ->findAll();

        if (empty($rows)) {
            return [];
        }

        $ids = array_map(static fn (array $row): int => (int) ($row['id'] ?? 0), $rows);
        $ids = array_values(array_filter($ids, static fn (int $id): bool => $id > 0));
        if (empty($ids)) {
            return $rows;
        }

        $niveis = (new PacoteServicoNivelModel())
            ->whereIn('pacote_servico_id', $ids)
            ->orderBy('pacote_servico_id', 'ASC')
            ->orderBy('ordem', 'ASC')
            ->findAll();

        $map = [];
        foreach ($niveis as $nivel) {
            $pacoteId = (int) ($nivel['pacote_servico_id'] ?? 0);
            $nivelCode = trim((string) ($nivel['nivel'] ?? ''));
            if ($pacoteId <= 0 || $nivelCode === '') {
                continue;
            }
            $map[$pacoteId][$nivelCode] = $nivel;
        }

        foreach ($rows as &$row) {
            $row['niveis'] = $map[(int) ($row['id'] ?? 0)] ?? [];
        }
        unset($row);

        return $rows;
    }
}

