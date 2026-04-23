<?php

namespace App\Models;

use CodeIgniter\Model;

class OsItemModel extends Model
{
    protected $table = 'os_itens';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'os_id',
        'legacy_origem',
        'legacy_tabela',
        'legacy_id',
        'tipo',
        'descricao',
        'observacao',
        'quantidade',
        'valor_unitario',
        'valor_total',
        'preco_custo_referencia',
        'preco_venda_referencia',
        'preco_base',
        'percentual_encargos',
        'valor_encargos',
        'percentual_margem',
        'valor_margem',
        'valor_recomendado',
        'modo_precificacao',
        'peca_id',
        'servico_id',
        'status_item_estoque',
        'estoque_reservado',
        'pendencia_resolvida_em',
        'pendencia_observacao',
        'pendencia_fornecedor',
        'pendencia_valor_compra',
        'pendencia_data_entrada',
        'pendencia_tipo_aquisicao',
        'pendencia_destino_despesa',
    ];
    protected $useTimestamps = false;
    protected $createdField = 'created_at';

    public function getByOs($osId)
    {
        $builder = $this->select('os_itens.*, pecas.nome as peca_nome')
            ->join('pecas', 'pecas.id = os_itens.peca_id', 'left');

        if ($this->db->fieldExists('servico_id', $this->table)) {
            $builder
                ->select('servicos.nome as servico_nome')
                ->join('servicos', 'servicos.id = os_itens.servico_id', 'left');
        } else {
            $builder->select('NULL as servico_nome', false);
        }

        return $builder
            ->where('os_id', $osId)
            ->findAll();
    }

    public function getTotalByOs($osId)
    {
        $result = $this->selectSum('valor_total')
                       ->where('os_id', $osId)
                       ->first();
        return $result['valor_total'] ?? 0;
    }
}
