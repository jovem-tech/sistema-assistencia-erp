<?php

namespace App\Models;

use CodeIgniter\Model;

class EquipamentoLifecycleHistoricoModel extends Model
{
    private const EVENTO_LABELS = [
        'encerrado' => 'Encerramento',
        'reativado' => 'Volta a operacao',
        'encerrado_automaticamente' => 'Encerramento automatico',
    ];

    private const EVENTO_BADGE_CLASSES = [
        'encerrado' => 'text-bg-warning text-dark',
        'reativado' => 'text-bg-success',
        'encerrado_automaticamente' => 'text-bg-danger',
    ];

    private const EVENTO_TITULOS = [
        'encerrado' => 'Equipamento encerrado',
        'reativado' => 'Equipamento reativado',
        'encerrado_automaticamente' => 'Equipamento encerrado automaticamente',
    ];

    protected $table = 'equipamentos_lifecycle_historico';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'equipamento_id',
        'os_id',
        'evento',
        'motivo',
        'observacao',
        'status_anterior',
        'status_novo',
        'usuario_id',
        'created_at',
    ];
    protected $useTimestamps = false;
    protected $createdField = 'created_at';

    public function supportsLifecycleHistory(): bool
    {
        return $this->db->tableExists($this->table)
            && $this->db->fieldExists('equipamento_id', $this->table)
            && $this->db->fieldExists('evento', $this->table);
    }

    public function registrarEvento(int $equipamentoId, string $evento, array $data = []): ?array
    {
        if ($equipamentoId <= 0 || ! $this->supportsLifecycleHistory()) {
            return null;
        }

        $evento = strtolower(trim($evento));
        if ($evento === '') {
            return null;
        }

        $payload = $this->buildPayload($equipamentoId, $evento, $data);

        if (! $this->insert($payload)) {
            return null;
        }

        $insertId = (int) $this->getInsertID();
        $payload['id'] = $insertId > 0 ? $insertId : null;

        return $this->enrichRow($payload);
    }

    /**
     * @return array<int, array<string,mixed>>
     */
    public function byEquipamento(int $equipamentoId): array
    {
        if ($equipamentoId <= 0 || ! $this->supportsLifecycleHistory()) {
            return [];
        }

        $rows = $this->select(
            $this->table . '.*, usuarios.nome as usuario_nome, os.numero_os as numero_os'
        )
            ->join('usuarios', 'usuarios.id = ' . $this->table . '.usuario_id', 'left')
            ->join('os', 'os.id = ' . $this->table . '.os_id', 'left')
            ->where($this->table . '.equipamento_id', $equipamentoId)
            ->orderBy($this->table . '.created_at', 'DESC')
            ->orderBy($this->table . '.id', 'DESC')
            ->findAll();

        return array_map(
            fn(array $row): array => $this->enrichRow($row),
            is_array($rows) ? $rows : []
        );
    }

    private function buildPayload(int $equipamentoId, string $evento, array $data): array
    {
        $usuarioId = $data['usuario_id'] ?? (function_exists('session') ? session()->get('user_id') : null);
        $usuarioId = (int) $usuarioId;
        $usuarioId = $usuarioId > 0 ? $usuarioId : null;

        $motivo = trim((string) ($data['motivo'] ?? ''));
        if ($motivo === '') {
            $motivo = trim((string) ($data['motivo_encerramento'] ?? $data['motivo_reativacao'] ?? ''));
        }

        $observacao = trim((string) ($data['observacao'] ?? ''));
        if ($observacao === '') {
            $observacao = trim((string) ($data['observacao_encerramento'] ?? $data['observacao_reativacao'] ?? ''));
        }

        $statusAnterior = trim((string) ($data['status_anterior'] ?? ''));
        $statusNovo = trim((string) ($data['status_novo'] ?? ''));
        $createdAt = trim((string) ($data['created_at'] ?? ''));

        return [
            'equipamento_id' => $equipamentoId,
            'os_id' => (int) ($data['os_id'] ?? 0) > 0 ? (int) $data['os_id'] : null,
            'evento' => $evento,
            'motivo' => $motivo !== '' ? $motivo : null,
            'observacao' => $observacao !== '' ? $observacao : null,
            'status_anterior' => $statusAnterior !== '' ? $statusAnterior : null,
            'status_novo' => $statusNovo !== '' ? $statusNovo : null,
            'usuario_id' => $usuarioId,
            'created_at' => $createdAt !== '' ? $createdAt : date('Y-m-d H:i:s'),
        ];
    }

    private function enrichRow(array $row): array
    {
        $evento = strtolower(trim((string) ($row['evento'] ?? '')));
        $motivo = trim((string) ($row['motivo'] ?? ''));
        $osNumero = trim((string) ($row['numero_os'] ?? ''));
        $statusAnterior = trim((string) ($row['status_anterior'] ?? ''));
        $statusNovo = trim((string) ($row['status_novo'] ?? ''));

        $row['evento'] = $evento;
        $row['evento_label'] = self::EVENTO_LABELS[$evento] ?? 'Movimentacao';
        $row['evento_badge_class'] = self::EVENTO_BADGE_CLASSES[$evento] ?? 'text-bg-secondary';
        $row['titulo'] = $this->buildTitulo($evento, $osNumero);
        $row['data_label'] = ! empty($row['created_at'])
            ? date('d/m/Y H:i', strtotime((string) $row['created_at']))
            : '';
        $row['status_transicao_label'] = $this->buildStatusTransitionLabel($statusAnterior, $statusNovo);
        $row['motivo_label'] = $this->resolveMotivoLabel($evento, $motivo);
        $row['os_label'] = $osNumero !== '' ? 'OS #' . $osNumero : '';
        $row['observacao'] = trim((string) ($row['observacao'] ?? ''));
        $row['usuario_nome'] = trim((string) ($row['usuario_nome'] ?? ''));

        return $row;
    }

    private function buildTitulo(string $evento, string $osNumero): string
    {
        $base = self::EVENTO_TITULOS[$evento] ?? 'Movimentacao de ciclo de vida';

        if ($evento === 'encerrado_automaticamente' && $osNumero !== '') {
            return $base . ' pela OS #' . $osNumero;
        }

        return $base;
    }

    private function buildStatusTransitionLabel(string $statusAnterior, string $statusNovo): string
    {
        $anterior = $this->resolveStatusLabel($statusAnterior);
        $novo = $this->resolveStatusLabel($statusNovo);

        if ($anterior === '' && $novo === '') {
            return '';
        }

        if ($anterior === '') {
            return $novo;
        }

        if ($novo === '') {
            return $anterior;
        }

        return $anterior . ' -> ' . $novo;
    }

    private function resolveStatusLabel(string $status): string
    {
        $status = strtolower(trim($status));
        if ($status === '') {
            return '';
        }

        if (function_exists('equipamento_status_operacional_label')) {
            return equipamento_status_operacional_label($status);
        }

        return $status === 'encerrado' ? 'Encerrado' : 'Ativo';
    }

    private function resolveMotivoLabel(string $evento, string $motivo): string
    {
        $motivo = strtolower(trim($motivo));
        if ($motivo === '') {
            return '';
        }

        if ($evento === 'reativado') {
            $map = [
                'recuperado' => 'Recuperado',
                'recondicionado' => 'Recondicionado',
                'reparado' => 'Reparado',
                'voltou_operacao' => 'Voltou a funcionar',
                'outro' => 'Outro motivo',
            ];

            return $map[$motivo] ?? ucfirst(str_replace('_', ' ', $motivo));
        }

        if (function_exists('equipamento_motivo_encerramento_label')) {
            return equipamento_motivo_encerramento_label($motivo) ?: ucfirst(str_replace('_', ' ', $motivo));
        }

        return ucfirst(str_replace('_', ' ', $motivo));
    }
}
