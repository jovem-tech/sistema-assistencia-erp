<?php

namespace App\Models;

use CodeIgniter\Model;

class OsStatusModel extends Model
{
    protected $table = 'os_status';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'codigo',
        'nãome',
        'grupo_macro',
        'icone',
        'cor',
        'ordem_fluxo',
        'status_final',
        'status_pausa',
        'gera_evento_crm',
        'estado_fluxo_padrao',
        'ativo',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function getActiveGrouped(): array
    {
        $rows = $this->where('ativo', 1)
            ->orderBy('ordem_fluxo', 'ASC')
            ->findAll();

        $grouped = [];
        foreach ($rows as $row) {
            $macro = $row['grupo_macro'] ?: 'outros';
            if (!isset($grouped[$macro])) {
                $grouped[$macro] = [];
            }
            $grouped[$macro][] = $row;
        }

        return $grouped;
    }

    public function byCode(string $codigo): ?array
    {
        return $this->where('codigo', $codigo)->first();
    }
}

