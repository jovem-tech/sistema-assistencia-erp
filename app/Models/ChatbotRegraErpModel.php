<?php

namespace App\Models;

use CodeIgniter\Model;

class ChatbotRegraErpModel extends Model
{
    protected $table = 'chatbot_regras_erp';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'nome',
        'evento_origem',
        'condicao_json',
        'acao_json',
        'ativo',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function ativasPorEvento(string $evento): array
    {
        return $this
            ->where('ativo', 1)
            ->where('evento_origem', $evento)
            ->orderBy('id', 'ASC')
            ->findAll();
    }
}
