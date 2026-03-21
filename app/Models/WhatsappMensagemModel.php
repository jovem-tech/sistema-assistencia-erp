<?php

namespace App\Models;

use CodeIgniter\Model;

class WhatsappMensagemModel extends Model
{
    protected $table = 'whatsapp_mensagens';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'os_id',
        'cliente_id',
        'template_id',
        'provedor',
        'tipo_evento',
        'telefone',
        'conteudo',
        'status_envio',
        'api_message_id',
        'api_response',
        'erro_detalhe',
        'enviado_por',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function byOs(int $osId, int $limit = 30): array
    {
        return $this->select('whatsapp_mensagens.*, usuarios.nome as enviado_por_nome, whatsapp_templates.nome as template_nome')
            ->join('usuarios', 'usuarios.id = whatsapp_mensagens.enviado_por', 'left')
            ->join('whatsapp_templates', 'whatsapp_templates.id = whatsapp_mensagens.template_id', 'left')
            ->where('whatsapp_mensagens.os_id', $osId)
            ->orderBy('whatsapp_mensagens.created_at', 'DESC')
            ->findAll($limit);
    }
}

