<?php

namespace App\Services;

use App\Models\ConfiguracaoModel;
use App\Models\PrecificacaoCategoriaModel;
use App\Models\PrecificacaoComponenteModel;
use Config\Database;

class PecaPrecificacaoService
{
    public const DEFAULT_ENCARGOS_PERCENTUAL = 15.0;
    public const DEFAULT_MARGEM_PERCENTUAL = 45.0;

    private const CFG_ENCARGOS_PERCENTUAL = 'precificacao_peca_encargos_percentual';
    private const CFG_MARGEM_PERCENTUAL = 'precificacao_peca_margem_percentual';
    private const CFG_BASE = 'precificacao_peca_base';
    private const CFG_RESPEITAR_PRECO_VENDA = 'precificacao_peca_respeitar_preco_venda';
    private const CFG_USAR_COMPONENTES = 'precificacao_peca_usa_componentes';
    private const COMPONENT_GROUP = 'encargo_peca_percentual';

    private ConfiguracaoModel $configuracaoModel;
    private PrecificacaoComponenteModel $componenteModel;
    private PrecificacaoCategoriaModel $categoriaModel;
    /** @var array<string,mixed>|null */
    private ?array $rulesCache = null;

    public function __construct()
    {
        $this->configuracaoModel = new ConfiguracaoModel();
        $this->componenteModel = new PrecificacaoComponenteModel();
        $this->categoriaModel = new PrecificacaoCategoriaModel();
    }

    /**
     * @return array<string,mixed>
     */
    public function getRules(): array
    {
        if ($this->rulesCache !== null) {
            return $this->rulesCache;
        }

        $encargos = $this->normalizePercent(
            $this->configuracaoModel->get(self::CFG_ENCARGOS_PERCENTUAL, self::DEFAULT_ENCARGOS_PERCENTUAL),
            self::DEFAULT_ENCARGOS_PERCENTUAL
        );
        $margem = $this->normalizePercent(
            $this->configuracaoModel->get(self::CFG_MARGEM_PERCENTUAL, self::DEFAULT_MARGEM_PERCENTUAL),
            self::DEFAULT_MARGEM_PERCENTUAL
        );

        $base = strtolower(trim((string) $this->configuracaoModel->get(self::CFG_BASE, 'custo')));
        if (!in_array($base, ['custo', 'venda'], true)) {
            $base = 'custo';
        }

        $respeitarPrecoVendaRaw = $this->configuracaoModel->get(self::CFG_RESPEITAR_PRECO_VENDA, '1');
        $respeitarPrecoVenda = !in_array(strtolower(trim((string) $respeitarPrecoVendaRaw)), ['0', 'false', 'nao', 'no'], true);
        $usarComponentesRaw = $this->configuracaoModel->get(self::CFG_USAR_COMPONENTES, '1');
        $usarComponentes = !in_array(strtolower(trim((string) $usarComponentesRaw)), ['0', 'false', 'nao', 'no'], true);

        $encargosComponente = 0.0;
        if ($usarComponentes && $this->componenteTableReady()) {
            $encargosComponente = $this->normalizePercent(
                $this->componenteModel->somarValorAtivoPorGrupo(self::COMPONENT_GROUP, 'percentual'),
                0.0
            );
        }
        if ($encargosComponente > 0) {
            $encargos = $encargosComponente;
        }

        $this->rulesCache = [
            'encargos_percentual' => $encargos,
            'margem_percentual' => $margem,
            'base' => $base,
            'respeitar_preco_venda' => $respeitarPrecoVenda,
            'usar_componentes' => $usarComponentes,
            'encargos_componente_percentual' => $encargosComponente,
        ];

        return $this->rulesCache;
    }

    /**
     * @param array<string,mixed> $pecaRow
     * @return array<string,mixed>
     */
    public function buildQuote(array $pecaRow): array
    {
        $rules = $this->getRules();
        $precoCusto = max(0.0, (float) ($pecaRow['preco_custo'] ?? 0));
        $precoVenda = max(0.0, (float) ($pecaRow['preco_venda'] ?? 0));

        $categoriaNome = strtolower(trim((string) ($pecaRow['categoria'] ?? '')));
        $override = $this->getCategoriaOverride($categoriaNome);
        if ($override) {
            $rules['encargos_percentual'] = $override['encargos_percentual'];
            $rules['margem_percentual'] = $override['margem_percentual'];
        }

        $basePreferida = $rules['base'] === 'venda' ? $precoVenda : $precoCusto;
        $baseAlternativa = $rules['base'] === 'venda' ? $precoCusto : $precoVenda;
        $precoBase = $basePreferida > 0 ? $basePreferida : $baseAlternativa;
        $precoBase = max(0.0, (float) $precoBase);

        $encargosPercentual = (float) $rules['encargos_percentual'];
        $margemPercentual = (float) $rules['margem_percentual'];
        $encargosValor = round($precoBase * ($encargosPercentual / 100), 2);
        $margemValor = round($precoBase * ($margemPercentual / 100), 2);
        $valorCalculado = round($precoBase + $encargosValor + $margemValor, 2);

        $valorRecomendado = $valorCalculado;
        if ((bool) $rules['respeitar_preco_venda'] && $precoVenda > $valorRecomendado) {
            $valorRecomendado = round($precoVenda, 2);
        }

        return [
            'preco_custo_referencia' => round($precoCusto, 2),
            'preco_venda_referencia' => round($precoVenda, 2),
            'preco_base' => round($precoBase, 2),
            'percentual_encargos' => round($encargosPercentual, 2),
            'valor_encargos' => $encargosValor,
            'percentual_margem' => round($margemPercentual, 2),
            'valor_margem' => $margemValor,
            'valor_recomendado' => round(max(0.0, $valorRecomendado), 2),
            'modo_precificacao' => 'peca_instalada_auto',
            'categoria_override' => $override ? [
                'categoria' => $categoriaNome,
                'encargos_percentual' => $override['encargos_percentual'],
                'margem_percentual' => $override['margem_percentual'],
            ] : null,
            'regra_base' => (string) $rules['base'],
            'regra_respeita_preco_venda' => (bool) $rules['respeitar_preco_venda'],
        ];
    }

    /**
     * @param array<string,mixed> $pecaRow
     * @return array<string,mixed>
     */
    public function applyMinimumPrice(array $pecaRow, float $valorInformado): array
    {
        $quote = $this->buildQuote($pecaRow);
        $valorInformado = max(0.0, (float) $valorInformado);
        $valorRecomendado = (float) ($quote['valor_recomendado'] ?? 0);

        $valorAplicado = max($valorInformado, $valorRecomendado);
        $modo = $valorInformado >= $valorRecomendado ? 'manual_acima_recomendado' : 'peca_instalada_auto';
        $quote['modo_precificacao'] = $modo;
        $quote['valor_aplicado'] = round($valorAplicado, 2);
        $quote['valor_original_digitado'] = round($valorInformado, 2);

        return $quote;
    }

    /**
     * @param mixed $value
     */
    private function normalizePercent($value, float $fallback): float
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return $fallback;
        }
        $normalized = str_replace(',', '.', $raw);
        if (!is_numeric($normalized)) {
            return $fallback;
        }
        $percent = (float) $normalized;
        if ($percent < 0) {
            return 0.0;
        }
        if ($percent > 500) {
            return 500.0;
        }
        return $percent;
    }

    private function componenteTableReady(): bool
    {
        try {
            return Database::connect()->tableExists('precificacao_componentes');
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * @return array<string,float>|null
     */
    private function getCategoriaOverride(string $categoriaNome): ?array
    {
        $categoriaNome = strtolower(trim($categoriaNome));
        if ($categoriaNome === '') {
            return null;
        }
        if (! $this->categoriaModel->isTableReady()) {
            return null;
        }

        $map = $this->categoriaModel->getMapaPorTipo('peca');
        if (!isset($map[$categoriaNome])) {
            return null;
        }

        return [
            'encargos_percentual' => (float) ($map[$categoriaNome]['encargos_percentual'] ?? 0),
            'margem_percentual' => (float) ($map[$categoriaNome]['margem_percentual'] ?? 0),
        ];
    }
}
