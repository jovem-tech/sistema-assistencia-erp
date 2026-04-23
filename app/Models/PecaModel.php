<?php

namespace App\Models;

use CodeIgniter\Model;

class PecaModel extends Model
{
    protected $table = 'pecas';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'codigo', 'codigo_fabricante', 'nome', 'categoria', 'modelos_compativeis',
        'tipo_equipamento', 'fornecedor', 'localizacao', 'preco_custo', 'preco_venda',
        'quantidade_atual', 'estoque_minimo', 'estoque_maximo',
        'foto', 'observacoes', 'ativo'
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'nome'        => 'required|max_length[100]',
        'preco_custo' => 'required|decimal',
        'preco_venda' => 'required|decimal',
    ];

    public function getEstoqueBaixo()
    {
        return $this->where('quantidade_atual <=', 'estoque_minimo', false)
                    ->where('ativo', 1)
                    ->orderBy('nome', 'ASC')
                    ->findAll();
    }

    public function search($term, int $limit = 20)
    {
        $term = trim((string) $term);
        if ($term === '') {
            return [];
        }

        return $this->groupStart()
                        ->like('nome', $term)
                        ->orLike('codigo', $term)
                        ->orLike('categoria', $term)
                        ->orLike('modelos_compativeis', $term)
                    ->groupEnd()
                    ->where('ativo', 1)
                    ->orderBy('nome', 'ASC')
                    ->findAll($limit);
    }

    public function generateCodigo()
    {
        $last = $this->orderBy('id', 'DESC')->first();
        $num = $last ? ($last['id'] + 1) : 1;
        return 'PC' . str_pad($num, 5, '0', STR_PAD_LEFT);
    }

    public function getAtivas()
    {
        return $this->where('ativo', 1)->orderBy('nome', 'ASC')->findAll();
    }

    public function getCategoriasAtivas(): array
    {
        return array_values(array_filter(array_map(
            static fn (array $row): string => trim((string) ($row['categoria'] ?? '')),
            $this->select('categoria')
                ->where('ativo', 1)
                ->where('categoria IS NOT NULL', null, false)
                ->where("TRIM(categoria) <> ''", null, false)
                ->groupBy('categoria')
                ->orderBy('categoria', 'ASC')
                ->findAll()
        )));
    }

    public function getTiposEquipamentoAtivos(): array
    {
        if (! $this->db->fieldExists('tipo_equipamento', $this->table)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn (array $row): string => trim((string) ($row['tipo_equipamento'] ?? '')),
            $this->select('tipo_equipamento')
                ->where('ativo', 1)
                ->where('tipo_equipamento IS NOT NULL', null, false)
                ->where("TRIM(tipo_equipamento) <> ''", null, false)
                ->groupBy('tipo_equipamento')
                ->orderBy('tipo_equipamento', 'ASC')
                ->findAll()
        )));
    }
}
