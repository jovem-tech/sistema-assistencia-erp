<?php

namespace App\Models;

use CodeIgniter\Model;

class ChecklistFotoModel extends Model
{
    protected $table = 'checklist_fotos';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'checklist_resposta_id',
        'arquivo',
        'arquivo_original',
        'ordem',
        'created_at',
        'updated_at',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /**
     * @return list<array<string,mixed>>
     */
    public function findByRespostaIds(array $respostaIds): array
    {
        if (empty($respostaIds)) {
            return [];
        }

        return $this->whereIn('checklist_resposta_id', array_values(array_unique(array_map('intval', $respostaIds))))
            ->orderBy('ordem', 'ASC')
            ->findAll();
    }

    public function deleteByRespostaIds(array $respostaIds): void
    {
        if (empty($respostaIds)) {
            return;
        }

        $this->whereIn('checklist_resposta_id', array_values(array_unique(array_map('intval', $respostaIds))))->delete();
    }
}
