<?php

namespace App\Models;

use CodeIgniter\Model;

class FornecedorModel extends Model
{
    protected $table            = 'fornecedores';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    
    protected $allowedFields    = [
        'tipo_pessoa', 'nome_fantasia', 'razao_social', 'cnpj_cpf', 'ie_rg',
        'email', 'telefone1', 'telefone2', 'cep', 'endereco', 'numero',
        'complemento', 'bairro', 'cidade', 'uf', 'observacoes', 'ativo'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules      = [
        'nome_fantasia' => 'required|min_length[3]|max_length[100]',
        'telefone1'     => 'required|max_length[20]',
    ];
}
