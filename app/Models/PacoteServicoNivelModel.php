<?php

namespace App\Models;

use CodeIgniter\Model;

class PacoteServicoNivelModel extends Model
{
    protected $table = 'pacotes_servicos_niveis';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'pacote_servico_id',
        'nivel',
        'nome_exibicao',
        'cor_hex',
        'preco_min',
        'preco_recomendado',
        'preco_max',
        'prazo_estimado',
        'garantia_dias',
        'itens_inclusos',
        'argumento_venda',
        'destaque',
        'ordem',
        'ativo',
    ];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function byPacote(int $pacoteId): array
    {
        if ($pacoteId <= 0) {
            return [];
        }

        return $this->where('pacote_servico_id', $pacoteId)
            ->orderBy('ordem', 'ASC')
            ->findAll();
    }
}

