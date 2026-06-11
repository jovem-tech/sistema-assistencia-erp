<?php

namespace App\Services;

use App\Models\FinanceiroCartaoBandeiraModel;
use App\Models\FinanceiroCartaoOperadoraModel;
use App\Models\FinanceiroCartaoTaxaModel;

class FinanceiroCartaoService
{
    private FinanceiroCartaoOperadoraModel $operadoraModel;
    private FinanceiroCartaoBandeiraModel $bandeiraModel;
    private FinanceiroCartaoTaxaModel $taxaModel;

    public function __construct()
    {
        $this->operadoraModel = new FinanceiroCartaoOperadoraModel();
        $this->bandeiraModel = new FinanceiroCartaoBandeiraModel();
        $this->taxaModel = new FinanceiroCartaoTaxaModel();
    }

    public function isReady(): bool
    {
        return $this->operadoraModel->db->tableExists('financeiro_cartao_operadoras')
            && $this->operadoraModel->db->tableExists('financeiro_cartao_bandeiras')
            && $this->operadoraModel->db->tableExists('financeiro_cartao_taxas');
    }

    public function operadorasAtivas(): array
    {
        return $this->operadoraModel->ativos();
    }

    public function bandeirasAtivas(): array
    {
        return $this->bandeiraModel->ativos();
    }

    public function taxasAtivas(): array
    {
        return $this->taxaModel->ativas();
    }

    public function buildActiveDataset(): array
    {
        if (! $this->isReady()) {
            return [
                'operadoras' => [],
                'bandeiras' => [],
                'taxas' => [],
            ];
        }

        return [
            'operadoras' => array_map(static function (array $row): array {
                return [
                    'id' => (int) ($row['id'] ?? 0),
                    'nome' => (string) ($row['nome'] ?? ''),
                    'prazo_padrao_dias' => (int) ($row['prazo_padrao_dias'] ?? 0),
                ];
            }, $this->operadorasAtivas()),
            'bandeiras' => array_map(static function (array $row): array {
                return [
                    'id' => (int) ($row['id'] ?? 0),
                    'nome' => (string) ($row['nome'] ?? ''),
                ];
            }, $this->bandeirasAtivas()),
            'taxas' => array_map(static function (array $row): array {
                return [
                    'id' => (int) ($row['id'] ?? 0),
                    'operadora_id' => (int) ($row['operadora_id'] ?? 0),
                    'bandeira_id' => ! empty($row['bandeira_id']) ? (int) $row['bandeira_id'] : null,
                    'modalidade' => (string) ($row['modalidade'] ?? 'credito'),
                    'parcelas_inicial' => (int) ($row['parcelas_inicial'] ?? 1),
                    'parcelas_final' => (int) ($row['parcelas_final'] ?? 1),
                    'taxa_percentual' => round((float) ($row['taxa_percentual'] ?? 0), 4),
                    'taxa_fixa' => round((float) ($row['taxa_fixa'] ?? 0), 2),
                    'prazo_recebimento_dias' => (int) ($row['prazo_recebimento_dias'] ?? 0),
                ];
            }, $this->taxasAtivas()),
        ];
    }

    public function simulate(array $payload): array
    {
        if (! $this->isReady()) {
            throw new \RuntimeException('O módulo de recebimento em cartão ainda não está configurado.');
        }

        $valorBruto = round((float) ($payload['valor_bruto'] ?? $payload['valor'] ?? 0), 2);
        if ($valorBruto <= 0) {
            throw new \RuntimeException('Informe um valor bruto válido para simular o recebimento.');
        }

        $operadoraId = (int) ($payload['operadora_id'] ?? 0);
        if ($operadoraId <= 0) {
            throw new \RuntimeException('Selecione a operadora da maquininha.');
        }

        $bandeiraId = ! empty($payload['bandeira_id']) ? (int) $payload['bandeira_id'] : null;
        $modalidade = $this->normalizeModalidade(
            (string) ($payload['modalidade'] ?? ''),
            (string) ($payload['forma_pagamento'] ?? '')
        );

        if (! in_array($modalidade, ['credito', 'debito'], true)) {
            throw new \RuntimeException('Selecione se a venda será no crédito ou no débito.');
        }

        $parcelas = max(1, (int) ($payload['parcelas'] ?? 1));
        if ($modalidade === 'debito') {
            $parcelas = 1;
        }

        $taxa = $this->findApplicableRate($operadoraId, $modalidade, $parcelas, $bandeiraId);
        if (! $taxa) {
            throw new \RuntimeException('Não foi encontrada uma taxa ativa para a combinação de operadora, bandeira e parcelas.');
        }

        $operadora = $this->operadoraModel->find($operadoraId) ?? [];
        $bandeira = $bandeiraId ? ($this->bandeiraModel->find($bandeiraId) ?? []) : [];
        $percentual = round((float) ($taxa['taxa_percentual'] ?? 0), 4);
        $taxaFixa = round((float) ($taxa['taxa_fixa'] ?? 0), 2);
        $valorTaxa = round(($valorBruto * ($percentual / 100)) + $taxaFixa, 2);
        $valorLiquido = round($valorBruto - $valorTaxa, 2);
        $prazoRecebimentoDias = (int) ($taxa['prazo_recebimento_dias'] ?? $operadora['prazo_padrao_dias'] ?? 0);

        return [
            'ok' => true,
            'valor_bruto' => $valorBruto,
            'valor_taxa' => $valorTaxa,
            'valor_liquido' => $valorLiquido,
            'taxa_percentual' => $percentual,
            'taxa_fixa' => $taxaFixa,
            'parcelas' => $parcelas,
            'modalidade' => $modalidade,
            'modalidade_label' => $modalidade === 'debito' ? 'Cartão de débito' : 'Cartão de crédito',
            'prazo_recebimento_dias' => $prazoRecebimentoDias,
            'data_prevista_recebimento' => date('Y-m-d', strtotime('+' . max(0, $prazoRecebimentoDias) . ' days')),
            'operadora' => [
                'id' => (int) ($operadora['id'] ?? 0),
                'nome' => (string) ($operadora['nome'] ?? ''),
            ],
            'bandeira' => [
                'id' => (int) ($bandeira['id'] ?? 0),
                'nome' => (string) ($bandeira['nome'] ?? ''),
            ],
            'taxa' => $taxa,
        ];
    }

    public function normalizeModalidade(string $modalidade = '', string $formaPagamento = ''): string
    {
        $normalized = strtolower(trim($modalidade));
        if (in_array($normalized, ['credito', 'debito'], true)) {
            return $normalized;
        }

        return match (strtolower(trim($formaPagamento))) {
            'cartao_debito' => 'debito',
            'cartao_credito' => 'credito',
            default => '',
        };
    }

    public function findApplicableRate(int $operadoraId, string $modalidade, int $parcelas, ?int $bandeiraId = null): ?array
    {
        if (! $this->isReady()) {
            return null;
        }

        $rows = $this->taxaModel
            ->where('operadora_id', $operadoraId)
            ->where('modalidade', $modalidade)
            ->where('ativo', 1)
            ->findAll();

        if ($rows === []) {
            return null;
        }

        $parcelas = max(1, $parcelas);

        $rows = array_values(array_filter($rows, static function (array $row) use ($parcelas, $bandeiraId): bool {
            $inicio = max(1, (int) ($row['parcelas_inicial'] ?? 1));
            $fim = max($inicio, (int) ($row['parcelas_final'] ?? $inicio));
            $taxaBandeiraId = ! empty($row['bandeira_id']) ? (int) $row['bandeira_id'] : null;

            if ($parcelas < $inicio || $parcelas > $fim) {
                return false;
            }

            if ($taxaBandeiraId === null) {
                return true;
            }

            return $bandeiraId !== null && $taxaBandeiraId === $bandeiraId;
        }));

        if ($rows === []) {
            return null;
        }

        usort($rows, static function (array $left, array $right) use ($bandeiraId): int {
            $leftSpecific = $bandeiraId !== null && ! empty($left['bandeira_id']) ? 1 : 0;
            $rightSpecific = $bandeiraId !== null && ! empty($right['bandeira_id']) ? 1 : 0;

            if ($leftSpecific !== $rightSpecific) {
                return $rightSpecific <=> $leftSpecific;
            }

            $leftRange = max(1, (int) ($left['parcelas_final'] ?? 1)) - max(1, (int) ($left['parcelas_inicial'] ?? 1));
            $rightRange = max(1, (int) ($right['parcelas_final'] ?? 1)) - max(1, (int) ($right['parcelas_inicial'] ?? 1));

            if ($leftRange !== $rightRange) {
                return $leftRange <=> $rightRange;
            }

            return ((int) ($left['id'] ?? 0)) <=> ((int) ($right['id'] ?? 0));
        });

        return $rows[0] ?? null;
    }
}
