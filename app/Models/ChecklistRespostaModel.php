<?php

namespace App\Models;

use CodeIgniter\Model;

class ChecklistRespostaModel extends Model
{
    protected $table = 'checklist_respostas';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'checklist_execucao_id',
        'checklist_item_id',
        'descricao_item',
        'ordem',
        'status',
        'observacao',
        'created_at',
        'updated_at',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /**
     * @return list<array<string,mixed>>
     */
    public function findByExecucao(int $execucaoId): array
    {
        return $this->where('checklist_execucao_id', $execucaoId)
            ->orderBy('ordem', 'ASC')
            ->findAll();
    }

    public function deleteByExecucao(int $execucaoId): void
    {
        $this->where('checklist_execucao_id', $execucaoId)->delete();
    }
}
