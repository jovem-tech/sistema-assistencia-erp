<?php

namespace App\Models;

use CodeIgniter\Model;

class LogModel extends Model
{
    protected $table = 'logs';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'usuario_id', 'acao', 'descricao', 'ip', 'user_agent'
    ];
    protected $useTimestamps = false;
    protected $createdField = 'created_at';

    public static function registrar($acao, $descricao = '')
    {
        $session = session();
        $request = \Config\Services::request();
        
        $model = new self();
        $model->insert([
            'usuario_id' => $session->get('user_id'),
            'acao'       => $acao,
            'descricao'  => $descricao,
            'ip'         => $request->getIPAddress(),
            'user_agent' => $request->getUserAgent()->getAgentString(),
        ]);
    }

    public function getRecentes($limit = 20)
    {
        return $this->select('logs.*, usuarios.nome as usuario_nome')
                    ->join('usuarios', 'usuarios.id = logs.usuario_id', 'left')
                    ->orderBy('logs.created_at', 'DESC')
                    ->findAll($limit);
    }
}
