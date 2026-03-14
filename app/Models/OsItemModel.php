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
        'os_id', 'tipo', 'descricao', 'quantidade', 'valor_unitario', 'valor_total', 'peca_id'
    ];
    protected $useTimestamps = false;
    protected $createdField = 'created_at';

    public function getByOs($osId)
    {
        return $this->select('os_itens.*, pecas.nome as peca_nome')
                    ->join('pecas', 'pecas.id = os_itens.peca_id', 'left')
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
