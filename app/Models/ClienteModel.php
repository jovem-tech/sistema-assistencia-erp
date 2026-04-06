<?php

namespace App\Models;

use CodeIgniter\Model;

class ClienteModel extends Model
{
    protected $table = 'clientes';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'tipo_pessoa', 'nome_razao', 'cpf_cnpj', 'rg_ie', 'email',
        'telefone1', 'telefone2', 'nome_contato', 'telefone_contato', 'cep', 'endereco', 'numero',
        'complemento', 'bairro', 'cidade', 'uf', 'observacoes',
        'legacy_origem', 'legacy_id',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'nome_razao' => 'required|min_length[3]|max_length[100]',
        'telefone1'  => 'required|max_length[20]',
    ];

    protected $beforeInsert = ['nullifyEmptyFields'];
    protected $beforeUpdate = ['nullifyEmptyFields'];

    protected function nullifyEmptyFields(array $data)
    {
        $fieldsToNullify = ['cpf_cnpj', 'email', 'telefone2', 'nome_contato', 'telefone_contato', 'rg_ie', 'cep', 'endereco', 'numero', 'complemento', 'bairro', 'cidade', 'uf', 'observacoes'];
        foreach ($fieldsToNullify as $field) {
            if (isset($data['data'][$field]) && trim($data['data'][$field]) === '') {
                $data['data'][$field] = null;
            }
        }
        return $data;
    }

    public function search($term)
    {
        return $this->like('nome_razao', $term)
                    ->orLike('cpf_cnpj', $term)
                    ->orLike('telefone1', $term)
                    ->orLike('email', $term)
                    ->orderBy('nome_razao', 'ASC')
                    ->findAll(20);
    }

    public function getWithOsCount()
    {
        return $this->select('clientes.*, COUNT(os.id) as total_os')
                    ->join('os', 'os.cliente_id = clientes.id', 'left')
                    ->groupBy('clientes.id')
                    ->orderBy('clientes.nome_razao', 'ASC')
                    ->findAll();
    }
}
