<?php

namespace App\Models;

use CodeIgniter\Model;

class ConversaWhatsappModel extends Model
{
    protected $table = 'conversas_whatsapp';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'cliente_id',
        'contato_id',
        'os_id_principal',
        'telefone',
        'nome_contato',
        'status',
        'responsavel_id',
        'ultima_mensagem_em',
        'primeira_mensagem_em',
        'nao_lidas',
        'origem_provider',
        'canal',
        'automacao_ativa',
        'aguardando_humano',
        'prioridade',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function findByPhone(string $phone): ?array
    {
        return $this->where('telefone', $phone)->first();
    }
}
