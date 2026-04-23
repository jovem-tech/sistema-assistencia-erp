<?php

namespace App\Models;

use CodeIgniter\Model;

class WhatsappEnvioModel extends Model
{
    protected $table = 'whatsapp_envios';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'cliente_id',
        'os_id',
        'telefone',
        'tipo_envio',
        'tipo_conteudo',
        'template_codigo',
        'mensagem',
        'arquivo',
        'provedor',
        'status',
        'resposta_api',
        'usuario_id',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function byOs(int $osId, int $limit = 50): array
    {
        return $this->select('whatsapp_envios.*, usuarios.nome as usuario_nome')
            ->join('usuarios', 'usuarios.id = whatsapp_envios.usuario_id', 'left')
            ->where('whatsapp_envios.os_id', $osId)
            ->orderBy('whatsapp_envios.created_at', 'DESC')
            ->findAll($limit);
    }
}

