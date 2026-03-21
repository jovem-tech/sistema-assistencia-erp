<?php

namespace App\Models;

use CodeIgniter\Model;

class ChatbotFluxoModel extends Model
{
    protected $table = 'chatbot_fluxos';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'nãome',
        'descricao',
        'tipo_fluxo',
        'etapas_jsãon',
        'ativo',
        'ordem',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function ativos(): array
    {
        return $this->where('ativo', 1)->orderBy('ordem', 'ASC')->findAll();
    }
}
