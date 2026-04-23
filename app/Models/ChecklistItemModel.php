<?php

namespace App\Models;

use CodeIgniter\Model;

class ChecklistItemModel extends Model
{
    protected $table = 'checklist_itens';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'checklist_modelo_id',
        'descricao',
        'ordem',
        'ativo',
        'created_at',
        'updated_at',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /**
     * @return list<array<string,mixed>>
     */
    public function findAtivosPorModelo(int $modeloId): array
    {
        return $this->where('checklist_modelo_id', $modeloId)
            ->where('ativo', 1)
            ->orderBy('ordem', 'ASC')
            ->findAll();
    }
}
