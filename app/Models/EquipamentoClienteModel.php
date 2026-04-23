<?php

namespace App\Models;

use CodeIgniter\Model;

class EquipamentoClienteModel extends Model
{
    protected $table = 'equipamento_clientes';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = ['equipamento_id', 'cliente_id'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = ''; // Set to empty to disable updated_at

    public function getClientesVinculados($equipamento_id)
    {
        return $this->select('clientes.id, clientes.nome_razao, clientes.telefone1, clientes.email')
                    ->join('clientes', 'clientes.id = equipamento_clientes.cliente_id')
                    ->where('equipamento_clientes.equipamento_id', $equipamento_id)
                    ->findAll();
    }
}
