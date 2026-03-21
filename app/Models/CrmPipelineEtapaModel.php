<?php

namespace App\Models;

use CodeIgniter\Model;

class CrmPipelineEtapaModel extends Model
{
    protected $table = 'crm_pipeline_etapas';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'codigo',
        'nome',
        'ordem',
        'ativo',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function ativas(): array
    {
        return $this->where('ativo', 1)->orderBy('ordem', 'ASC')->findAll();
    }
}

