<?php

namespace App\Models;

use CodeIgniter\Model;

class CrmPipelineModel extends Model
{
    protected $table = 'crm_pipeline';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'cliente_id',
        'os_id',
        'etapa_atual',
        'data_entrada_etapa',
        'usuario_responsavel',
        'status',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}

