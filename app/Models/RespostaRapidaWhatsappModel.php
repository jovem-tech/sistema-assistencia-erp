<?php

namespace App\Models;

use CodeIgniter\Model;

class RespostaRapidaWhatsappModel extends Model
{
    protected $table = 'respostas_rapidas_whatsapp';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'titulo',
        'categoria',
        'mensagem',
        'ativo',
        'ordem',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function ativas(): array
    {
        return $this->where('ativo', 1)->orderBy('ordem', 'ASC')->findAll();
    }
}
