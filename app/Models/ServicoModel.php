<?php

namespace App\Models;

use CodeIgniter\Model;

class ServicoModel extends Model
{
    protected $table            = 'servicos';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'nome',
        'descricao',
        'tipo_equipamento',
        'valor',
        'tempo_padrao_horas',
        'custo_direto_padrao',
        'status',
        'encerrado_em',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getAtivos()
    {
        return $this->where('status', 'ativo')
            ->where('encerrado_em IS NULL', null, false)
            ->orderBy('nome', 'ASC')
            ->findAll();
    }

    public function searchAtivos(string $term, int $limit = 5): array
    {
        $term = trim($term);
        if ($term === '') {
            return [];
        }

        $builder = $this->groupStart()
                ->like('nome', $term)
                ->orLike('descricao', $term);

        if ($this->db->fieldExists('tipo_equipamento', $this->table)) {
            $builder->orLike('tipo_equipamento', $term);
        }

        return $builder->groupEnd()
            ->where('status', 'ativo')
            ->where('encerrado_em IS NULL', null, false)
            ->orderBy('nome', 'ASC')
            ->findAll($limit);
    }

    public function getTiposEquipamentoAtivos(): array
    {
        if (! $this->db->fieldExists('tipo_equipamento', $this->table)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn (array $row): string => trim((string) ($row['tipo_equipamento'] ?? '')),
            $this->select('tipo_equipamento')
                ->where('status', 'ativo')
                ->where('encerrado_em IS NULL', null, false)
                ->where('tipo_equipamento IS NOT NULL', null, false)
                ->where("TRIM(tipo_equipamento) <> ''", null, false)
                ->groupBy('tipo_equipamento')
                ->orderBy('tipo_equipamento', 'ASC')
                ->findAll()
        )));
    }
}
