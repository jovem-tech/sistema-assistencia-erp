<?php

namespace App\Models;

use CodeIgniter\Model;

class ChatbotIntencaoModel extends Model
{
    protected $table = 'chatbot_intencoes';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'codigo',
        'nome',
        'descricao',
        'gatilhos_json',
        'resposta_padrao',
        'exige_consulta_erp',
        'acao_sistema',
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
