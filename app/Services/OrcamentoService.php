<?php

namespace App\Services;

use App\Models\OrcamentoModel;
use App\Models\OrcamentoStatusHistoricoModel;

class OrcamentoService
{
    public function normalizeMoney($value): float
    {
        if ($value === null) {
            return 0.0;
        }

        $raw = trim((string) $value);
        if ($raw === '') {
            return 0.0;
        }

        $raw = preg_replace('/[^\d,\.\-]/', '', $raw) ?? '0';
        if (str_contains($raw, ',') && str_contains($raw, '.')) {
            $raw = str_replace('.', '', $raw);
        }
        $raw = str_replace(',', '.', $raw);

        return (float) $raw;
    }

    public function normalizeQuantity($value): float
    {
        $qty = $this->normalizeMoney($value);
        return $qty <= 0 ? 1.0 : $qty;
    }

    public function generateToken(): string
    {
        return bin2hex(random_bytes(24));
    }

    public function ensureNumero(OrcamentoModel $model, int $orcamentoId): string
    {
        $orcamento = $model->find($orcamentoId);
        if (!$orcamento) {
            return '';
        }

        $existing = trim((string) ($orcamento['numero'] ?? ''));
        if ($existing !== '') {
            return $existing;
        }

        $numero = 'ORC-' . date('ym') . '-' . str_pad((string) $orcamentoId, 6, '0', STR_PAD_LEFT);
        $model->update($orcamentoId, ['numero' => $numero]);
        return $numero;
    }

    /**
     * @param array<int, array<string, mixed>> $items
     */
    public function calculateTotals(array $items, float $desconto, float $acrescimo): array
    {
        $subtotal = 0.0;
        foreach ($items as $item) {
            $subtotal += (float) ($item['total'] ?? 0);
        }

        $total = $subtotal - max(0.0, $desconto) + max(0.0, $acrescimo);
        if ($total < 0) {
            $total = 0.0;
        }

        return [
            'subtotal' => round($subtotal, 2),
            'total' => round($total, 2),
        ];
    }

    public function registrarHistoricoStatus(
        OrcamentoStatusHistoricoModel $historicoModel,
        int $orcamentoId,
        ?string $statusAnterior,
        string $statusNovo,
        ?int $usuarioId = null,
        ?string $observacao = null,
        string $origem = 'sistema'
    ): void {
        $historicoModel->insert([
            'orcamento_id' => $orcamentoId,
            'status_anterior' => $statusAnterior,
            'status_novo' => $statusNovo,
            'observacao' => $observacao ?: null,
            'origem' => $origem,
            'alterado_por' => $usuarioId > 0 ? $usuarioId : null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function canTransition(string $statusAnterior, string $statusNovo): bool
    {
        $statusAnterior = trim($statusAnterior);
        $statusNovo = trim($statusNovo);

        if ($statusAnterior === '' || $statusNovo === '') {
            return false;
        }
        if ($statusAnterior === $statusNovo) {
            return true;
        }

        $map = [
            OrcamentoModel::STATUS_RASCUNHO => [
                OrcamentoModel::STATUS_ENVIADO,
                OrcamentoModel::STATUS_AGUARDANDO,
                OrcamentoModel::STATUS_REJEITADO,
                OrcamentoModel::STATUS_CANCELADO,
            ],
            OrcamentoModel::STATUS_ENVIADO => [
                OrcamentoModel::STATUS_AGUARDANDO,
                OrcamentoModel::STATUS_APROVADO,
                OrcamentoModel::STATUS_PENDENTE_OS,
                OrcamentoModel::STATUS_REJEITADO,
                OrcamentoModel::STATUS_VENCIDO,
                OrcamentoModel::STATUS_CANCELADO,
            ],
            OrcamentoModel::STATUS_AGUARDANDO => [
                OrcamentoModel::STATUS_ENVIADO,
                OrcamentoModel::STATUS_APROVADO,
                OrcamentoModel::STATUS_PENDENTE_OS,
                OrcamentoModel::STATUS_REJEITADO,
                OrcamentoModel::STATUS_VENCIDO,
                OrcamentoModel::STATUS_CANCELADO,
            ],
            OrcamentoModel::STATUS_REJEITADO => [
                OrcamentoModel::STATUS_RASCUNHO,
                OrcamentoModel::STATUS_CANCELADO,
            ],
            OrcamentoModel::STATUS_VENCIDO => [
                OrcamentoModel::STATUS_AGUARDANDO,
                OrcamentoModel::STATUS_REJEITADO,
                OrcamentoModel::STATUS_CANCELADO,
            ],
            OrcamentoModel::STATUS_CANCELADO => [
                OrcamentoModel::STATUS_RASCUNHO,
            ],
            OrcamentoModel::STATUS_APROVADO => [
                OrcamentoModel::STATUS_CONVERTIDO,
            ],
            OrcamentoModel::STATUS_PENDENTE_OS => [
                OrcamentoModel::STATUS_APROVADO,
                OrcamentoModel::STATUS_CONVERTIDO,
                OrcamentoModel::STATUS_CANCELADO,
            ],
            OrcamentoModel::STATUS_CONVERTIDO => [],
        ];

        return in_array($statusNovo, $map[$statusAnterior] ?? [], true);
    }
}
