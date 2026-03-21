<?php

namespace App\Models;

use CodeIgniter\Model;

class ChatbotLogModel extends Model
{
    protected $table = 'chatbot_logs';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'conversa_id',
        'cliente_id',
        'os_id',
        'mensagem_id',
        'mensagem_recebida',
        'intencao_detectada',
        'confianca',
        'resposta_gerada',
        'tipo_resposta',
        'escalado_humano',
        'usuario_responsavel',
        'payload_json',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
