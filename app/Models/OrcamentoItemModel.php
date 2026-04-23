<?php

namespace App\Models;

use CodeIgniter\Model;

class OrcamentoItemModel extends Model
{
    protected $table      = 'orcamento_itens';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'orcamento_id',
        'tipo_item',
        'referencia_id',
        'descricao',
        'quantidade',
        'valor_unitario',
        'desconto',
        'acrescimo',
        'total',
        'ordem',
        'observacoes',
        'preco_custo_referencia',
        'preco_venda_referencia',
        'preco_base',
        'percentual_encargos',
        'valor_encargos',
        'percentual_margem',
        'valor_margem',
        'valor_recomendado',
        'modo_precificacao',
    ];

    public function byOrcamento(int $orcamentoId): array
    {
        return $this->where('orcamento_id', $orcamentoId)
            ->orderBy('ordem', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
    }
}
