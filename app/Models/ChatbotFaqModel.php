<?php

namespace App\Models;

use CodeIgniter\Model;

class ChatbotFaqModel extends Model
{
    protected $table = 'chatbot_faq';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'pergunta',
        'resposta',
        'categoria',
        'palavras_chave_json',
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
