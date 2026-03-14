<?php

namespace App\Models;

use CodeIgniter\Model;

class UsuarioModel extends Model
{
    protected $table = 'usuarios';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'nome', 'email', 'senha', 'telefone', 'perfil', 'grupo_id',
        'foto', 'ativo', 'ultimo_acesso', 'token_recuperacao', 'token_expiracao'
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'nome'  => 'required|min_length[3]|max_length[100]',
        'email' => 'required|valid_email|max_length[100]',
    ];

    public function getTecnicos()
    {
        // Busca por grupo chamado 'Técnico' OU por perfil legado
        $db = \Config\Database::connect();
        $grupoTecnico = $db->table('grupos')->where('nome', 'Técnico')->get()->getRowArray();

        $builder = $this->where('ativo', 1)->orderBy('nome', 'ASC');

        if ($grupoTecnico) {
            // Retorna usuários do grupo Técnico OU perfil legado 'tecnico'
            return $this->groupStart()
                        ->where('grupo_id', $grupoTecnico['id'])
                        ->orWhere('perfil', 'tecnico')
                        ->groupEnd()
                        ->where('ativo', 1)
                        ->orderBy('nome', 'ASC')
                        ->findAll();
        }

        return $this->where('perfil', 'tecnico')
                    ->where('ativo', 1)
                    ->orderBy('nome', 'ASC')
                    ->findAll();
    }

    public function getAtivos()
    {
        return $this->where('ativo', 1)
                    ->orderBy('nome', 'ASC')
                    ->findAll();
    }
}
