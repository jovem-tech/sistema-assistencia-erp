<?php

namespace App\Models;

use CodeIgniter\Model;

class ChecklistModeloModel extends Model
{
    protected $table = 'checklist_modelos';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'checklist_tipo_id',
        'tipo_equipamento_id',
        'nome',
        'descricao',
        'ordem',
        'ativo',
        'created_at',
        'updated_at',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function findAtivoPorTipo(int $checklistTipoId, int $tipoEquipamentoId): ?array
    {
        $row = $this->where('checklist_tipo_id', $checklistTipoId)
            ->where('tipo_equipamento_id', $tipoEquipamentoId)
            ->where('ativo', 1)
            ->orderBy('ordem', 'ASC')
            ->first();

        return is_array($row) ? $row : null;
    }
}
