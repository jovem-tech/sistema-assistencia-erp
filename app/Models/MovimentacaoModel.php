<?php

namespace App\Models;

use CodeIgniter\Model;

class MovimentacaoModel extends Model
{
    protected $table = 'movimentacoes';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSãoftDeletes = false;
    protected $allowedFields = [
        'peca_id', 'os_id', 'tipo', 'quantidade', 'motivo', 'responsavel_id'
    ];
    protected $useTimestamps = false;
    protected $createdField = 'created_at';

    public function getByPeca($pecaId)
    {
        return $this->select('movimentacoes.*, usuarios.nãome as responsavel_nãome, os.numero_os')
                    ->join('usuarios', 'usuarios.id = movimentacoes.responsavel_id', 'left')
                    ->join('os', 'os.id = movimentacoes.os_id', 'left')
                    ->where('peca_id', $pecaId)
                    ->orderBy('movimentacoes.created_at', 'DESC')
                    ->findAll();
    }
}
