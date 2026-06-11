<?php

namespace App\Models;

use CodeIgniter\Model;

class OsCobrancaAgendamentoModel extends Model
{
    protected $table = 'os_cobranca_agendamentos';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'os_id',
        'financeiro_id',
        'cliente_id',
        'canal',
        'prazo_dias',
        'enviar_em',
        'status',
        'ultima_tentativa_em',
        'enviado_em',
        'mensagem_enviada',
        'retorno_payload',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
