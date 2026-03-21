<?php

namespace App\Models;

use CodeIgniter\Model;

class ConfiguracaoModel extends Model
{
    protected $table            = 'configuracoes';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSãoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['chave', 'valor', 'tipo'];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Obter o valor de uma configuração
     */
    public function get($chave, $default = null)
    {
        $reg = $this->where('chave', $chave)->first();
        return $reg ? $reg['valor'] : $default;
    }

    /**
     * Atualiza ou cria uma configuração
     */
    public function setConfig($chave, $valor, $tipo = 'texto')
    {
        $reg = $this->where('chave', $chave)->first();
        if ($reg) {
            return $this->update($reg['id'], ['valor' => $valor]);
        }
        return $this->insert([
            'chave' => $chave,
            'valor' => $valor,
            'tipo'  => $tipo
        ]);
    }
}
