<?php

namespace App\Models;

use CodeIgniter\Model;

class ServicoModel extends Model
{
    protected $table            = 'servicos';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSãoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['nãome', 'descricao', 'valor', 'status', 'encerrado_em'];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getAtivos()
    {
        return $this->where('status', 'ativo')->orderBy('nãome', 'ASC')->findAll();
    }
}
