<?php

namespace App\Models;

use CodeIgniter\Model;

class EquipamentoModel extends Model
{
    protected $table = 'equipamentos';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'cliente_id', 'tipo_id', 'marca_id', 'modelo_id', 'cor', 'cor_hex', 'cor_rgb', 'numero_serie',
        'imei', 'senha_acesso', 'estado_fisico', 'acessorios', 'observacoes'
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'cliente_id' => 'required|integer',
        'tipo_id'    => 'required|integer',
        'marca_id'   => 'required|integer',
        'modelo_id'  => 'required|integer',
    ];

    public function getByCliente($clienteId)
    {
        return $this->select('equipamentos.*, tipos.nome as tipo_nome, marcas.nome as marca_nome, modelos.nome as modelo_nome')
                    ->join('equipamentos_tipos tipos', 'tipos.id = equipamentos.tipo_id', 'left')
                    ->join('equipamentos_marcas marcas', 'marcas.id = equipamentos.marca_id', 'left')
                    ->join('equipamentos_modelos modelos', 'modelos.id = equipamentos.modelo_id', 'left')
                    ->join('equipamento_clientes ec', 'ec.equipamento_id = equipamentos.id', 'left')
                    ->groupStart()
                        ->where('equipamentos.cliente_id', $clienteId)
                        ->orWhere('ec.cliente_id', $clienteId)
                    ->groupEnd()
                    ->groupBy('equipamentos.id')
                    ->orderBy('equipamentos.created_at', 'DESC')
                    ->findAll();
    }

    public function getWithCliente($id = null)
    {
        $builder = $this->select('equipamentos.*, clientes.nome_razao as cliente_nome, tipos.nome as tipo_nome, marcas.nome as marca_nome, modelos.nome as modelo_nome')
                        ->join('clientes', 'clientes.id = equipamentos.cliente_id', 'left')
                        ->join('equipamentos_tipos tipos', 'tipos.id = equipamentos.tipo_id', 'left')
                        ->join('equipamentos_marcas marcas', 'marcas.id = equipamentos.marca_id', 'left')
                        ->join('equipamentos_modelos modelos', 'modelos.id = equipamentos.modelo_id', 'left');
        
        if ($id) {
            return $builder->where('equipamentos.id', $id)->first();
        }
        
        return $builder->orderBy('equipamentos.created_at', 'DESC')->findAll();
    }
}
