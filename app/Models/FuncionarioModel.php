<?php

namespace App\Models;

use CodeIgniter\Model;

class FuncionarioModel extends Model
{
    protected $table            = 'funcionarios';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSãoftDeletes   = false;
    protected $protectFields    = true;
    
    protected $allowedFields    = [
        'nãome', 'cpf', 'rg', 'data_nascimento', 'cargo', 'salario',
        'data_admissao', 'data_demissao', 'email', 'telefone',
        'cep', 'endereco', 'numero', 'complemento', 'bairro',
        'cidade', 'uf', 'observacoes', 'ativo'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules      = [
        'nãome'     => 'required|min_length[3]|max_length[100]',
        'cpf'      => 'required|max_length[20]',
        'telefone' => 'required|max_length[20]',
    ];

    public function getTecnicos()
    {
        return $this->where('ativo', 1)
                    ->where('LOWER(cargo)', 'técnico')
                    ->orderBy('nãome', 'ASC')
                    ->findAll();
    }
}
