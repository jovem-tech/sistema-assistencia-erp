<?php

namespace App\Models;

use CodeIgniter\Model;

class OrcamentoEnvioModel extends Model
{
    protected $table      = 'orcamento_envios';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'orcamento_id',
        'canal',
        'destino',
        'mensagem',
        'documento_path',
        'status',
        'provedor',
        'referencia_externa',
        'erro_detalhe',
        'enviado_por',
        'enviado_em',
    ];

    public function byOrcamento(int $orcamentoId, int $limit = 50): array
    {
        return $this->select('orcamento_envios.*, usuarios.nome as usuario_nome')
            ->join('usuarios', 'usuarios.id = orcamento_envios.enviado_por', 'left')
            ->where('orcamento_envios.orcamento_id', $orcamentoId)
            ->orderBy('orcamento_envios.created_at', 'DESC')
            ->findAll($limit);
    }

    public function latestByCanal(int $orcamentoId, string $canal, ?string $status = null): ?array
    {
        $builder = $this->where('orcamento_id', $orcamentoId)
            ->where('canal', trim($canal))
            ->orderBy('id', 'DESC');

        if ($status !== null && trim($status) !== '') {
            $builder->where('status', trim($status));
        }

        $row = $builder->first();
        return $row ?: null;
    }
}
