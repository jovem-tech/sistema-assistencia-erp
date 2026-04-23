<?php

namespace App\Models;

use CodeIgniter\Model;

class OsDocumentoModel extends Model
{
    protected $table = 'os_documentos';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'os_id',
        'tipo_documento',
        'arquivo',
        'versao',
        'hash_sha1',
        'gerado_por',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function byOs(int $osId): array
    {
        return $this->select('os_documentos.*, usuarios.nome as gerado_por_nome')
            ->join('usuarios', 'usuarios.id = os_documentos.gerado_por', 'left')
            ->where('os_documentos.os_id', $osId)
            ->orderBy('os_documentos.created_at', 'DESC')
            ->findAll();
    }
}

