<?php

namespace App\Models;

use CodeIgniter\Model;

class CrmMensagemModel extends Model
{
    protected $table = 'crm_mensagens';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'cliente_id',
        'os_id',
        'conversa_id',
        'canal',
        'provider',
        'direcao',
        'tipo_conteudo',
        'conteudo',
        'arquivo',
        'status',
        'payload_json',
        'usuario_id',
        'data_mensagem',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
