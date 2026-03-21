<?php

namespace App\Models;

use CodeIgniter\Model;

class CrmTagModel extends Model
{
    protected $table = 'crm_tags';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'slug',
        'nãome',
        'cor',
        'ativo',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function ativas(): array
    {
        return $this->where('ativo', 1)->orderBy('nãome', 'ASC')->findAll();
    }
}

