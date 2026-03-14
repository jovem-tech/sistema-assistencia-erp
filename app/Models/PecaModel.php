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
        'fornecedor', 'localizacao', 'preco_custo', 'preco_venda',
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

    public function search($term)
    {
        return $this->like('nome', $term)
                    ->orLike('codigo', $term)
                    ->orLike('categoria', $term)
                    ->where('ativo', 1)
                    ->orderBy('nome', 'ASC')
                    ->findAll(20);
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
}
