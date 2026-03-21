<?php

namespace App\Models;

use CodeIgniter\Model;

class MensageriaMetricaDiariaModel extends Model
{
    protected $table = 'mensageria_metricas_diarias';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'data_referencia',
        'mensagens_recebidas',
        'mensagens_enviadas',
        'mensagens_automaticas',
        'mensagens_humanas',
        'conversas_abertas',
        'conversas_finalizadas',
        'tempo_medio_primeira_resposta',
        'tempo_medio_resposta_total',
        'taxa_resãolucao_automatica',
        'taxa_escalonamento_humanão',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
