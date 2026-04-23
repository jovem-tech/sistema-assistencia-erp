<?php

namespace App\Models;

use CodeIgniter\Model;
use Throwable;

class ChecklistTipoModel extends Model
{
    protected $table = 'checklist_tipos';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'codigo',
        'nome',
        'descricao',
        'ativo',
        'created_at',
        'updated_at',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function findByCodigo(string $codigo): ?array
    {
        try {
            $row = $this->where('codigo', trim($codigo))->first();
            return is_array($row) ? $row : null;
        } catch (Throwable $e) {
            log_message(
                'error',
                '[Checklist] Falha ao buscar tipo por codigo (' . trim($codigo) . '): ' . $e->getMessage()
            );
            return null;
        }
    }
}
