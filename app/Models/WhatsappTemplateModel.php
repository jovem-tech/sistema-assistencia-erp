<?php

namespace App\Models;

use CodeIgniter\Model;

class WhatsappTemplateModel extends Model
{
    protected $table = 'whatsapp_templates';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'codigo',
        'nãome',
        'evento',
        'conteudo',
        'ativo',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function getActive(): array
    {
        return $this->where('ativo', 1)->orderBy('nãome', 'ASC')->findAll();
    }

    public function byCode(string $codigo): ?array
    {
        return $this->where('codigo', $codigo)->where('ativo', 1)->first();
    }
}

