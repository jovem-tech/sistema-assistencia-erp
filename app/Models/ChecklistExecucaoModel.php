<?php

namespace App\Models;

use CodeIgniter\Model;

class ChecklistExecucaoModel extends Model
{
    protected $table = 'checklist_execucoes';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'os_id',
        'checklist_tipo_id',
        'checklist_modelo_id',
        'tipo_equipamento_id',
        'status',
        'total_itens',
        'total_discrepancias',
        'resumo_texto',
        'concluido_em',
        'created_at',
        'updated_at',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function findByOsAndTipo(int $osId, int $checklistTipoId): ?array
    {
        $row = $this->where('os_id', $osId)
            ->where('checklist_tipo_id', $checklistTipoId)
            ->first();

        return is_array($row) ? $row : null;
    }
}
