<?php

namespace App\Services;

use App\Models\ConfiguracaoModel;
use App\Models\PrecificacaoCategoriaModel;
use App\Models\PrecificacaoComponenteModel;
use App\Models\PrecificacaoServicoOverrideModel;
use Config\Database;

class ServicoPrecificacaoService
{
    private const CFG_CUSTO_HORA = 'precificacao_servico_custo_hora_produtiva';
    private const CFG_MARGEM = 'precificacao_servico_margem_percentual';
    private const CFG_TAXA = 'precificacao_servico_taxa_recebimento_percentual';
    private const CFG_IMPOSTO = 'precificacao_servico_imposto_percentual';
    private const CFG_TEMPO_PADRAO = 'precificacao_servico_tempo_padrao_horas';
    private const CFG_USA_COMPONENTES = 'precificacao_servico_usa_componentes';
    private const CFG_APLICA_CATALOGO = 'precificacao_servico_aplicar_catalogo';
    private const CFG_APLICA_PISO = 'precificacao_servico_aplicar_piso';

    private const GROUP_CUSTO_DIRETO = 'custo_servico_fixo';
    private const GROUP_RISCO_PERCENTUAL = 'risco_servico_percentual';

    private ConfiguracaoModel $configuracaoModel;
    private PrecificacaoComponenteModel $componenteModel;
    private PrecificacaoCategoriaModel $categoriaModel;
    private PrecificacaoServicoOverrideModel $servicoOverrideModel;
    /** @var array<string,mixed>|null */
    private ?array $rulesCache = null;

    public function __construct()
    {
        $this->configuracaoModel = new ConfiguracaoModel();
        $this->componenteModel = new PrecificacaoComponenteModel();
        $this->categoriaModel = new PrecificacaoCategoriaModel();
        $this->servicoOverrideModel = new PrecificacaoServicoOverrideModel();
    }

    /**
     * @return array<string,mixed>
     */
    public function getRules(): array
    {
        if ($this->rulesCache !== null) {
            return $this->rulesCache;
        }

        $custoHora = $this->normalizeDecimal(
            $this->configuracaoModel->get(self::CFG_CUSTO_HORA, '40'),
            40.0
        );
        $margem = $this->normalizePercent(
            $this->configuracaoModel->get(self::CFG_MARGEM, '25'),
            25.0
        );
        $taxa = $this->normalizePercent(
            $this->configuracaoModel->get(self::CFG_TAXA, '3.5'),
            3.5
        );
        $imposto = $this->normalizePercent(
            $this->configuracaoModel->get(self::CFG_IMPOSTO, '0'),
            0.0
        );
        $tempoPadrao = $this->normalizeDecimal(
            $this->configuracaoModel->get(self::CFG_TEMPO_PADRAO, '1'),
            1.0
        );
        if ($tempoPadrao <= 0) {
            $tempoPadrao = 1.0;
        }

        $usaComponentes = $this->isTruthy($this->configuracaoModel->get(self::CFG_USA_COMPONENTES, '1'));
        $aplicaCatalogo = $this->isTruthy($this->configuracaoModel->get(self::CFG_APLICA_CATALOGO, '1'));
        $aplicaPiso = $this->isTruthy($this->configuracaoModel->get(self::CFG_APLICA_PISO, '0'));

        $custoDiretoComponente = 0.0;
        $riscoPercentComponente = 0.0;
        if ($usaComponentes && $this->componenteTableReady()) {
            $custoDiretoComponente = max(
                0.0,
                (float) $this->componenteModel->somarValorAtivoPorGrupo(self::GROUP_CUSTO_DIRETO, 'valor')
            );
            $riscoPercentComponente = $this->normalizePercent(
                $this->componenteModel->somarValorAtivoPorGrupo(self::GROUP_RISCO_PERCENTUAL, 'percentual'),
                0.0
            );
        }

        $this->rulesCache = [
            'custo_hora_produtiva' => $custoHora,
            'margem_percentual' => $margem,
            'taxa_recebimento_percentual' => $taxa,
            'imposto_percentual' => $imposto,
            'tempo_padrao_horas' => $tempoPadrao,
            'usa_componentes' => $usaComponentes,
            'custo_direto_componente' => $custoDiretoComponente,
            'risco_percentual_componente' => $riscoPercentComponente,
            'aplicar_catalogo' => $aplicaCatalogo,
            'aplicar_piso' => $aplicaPiso,
        ];

        return $this->rulesCache;
    }

    public function shouldApplyCatalogPrice(): bool
    {
        return (bool) ($this->getRules()['aplicar_catalogo'] ?? false);
    }

    public function shouldApplyMinimumPrice(): bool
    {
        return (bool) ($this->getRules()['aplicar_piso'] ?? false);
    }

    /**
     * @param array<string,mixed> $servicoRow
     * @return array<string,mixed>
     */
    public function buildQuote(array $servicoRow): array
    {
        $rules = $this->getRules();
        $servicoId = (int) ($servicoRow['id'] ?? 0);
        $categoriaOverrideNome = $this->resolveCategoriaOverrideNome($servicoRow);
        $categoriaOverride = $this->getCategoriaOverride($categoriaOverrideNome);
        if ($categoriaOverride !== null) {
            $rules['margem_percentual'] = (float) ($categoriaOverride['margem_percentual'] ?? $rules['margem_percentual'] ?? 0);
            $rules['risco_percentual_componente'] = max(0.0, (float) ($categoriaOverride['encargos_percentual'] ?? 0));
        }
        $servicoOverride = $this->getServicoOverride($servicoId);
        if ($servicoOverride !== null) {
            $rules['custo_hora_produtiva'] = max(0.0, (float) ($servicoOverride['custo_hora_produtiva'] ?? $rules['custo_hora_produtiva'] ?? 0));
            $rules['margem_percentual'] = max(0.0, (float) ($servicoOverride['margem_percentual'] ?? $rules['margem_percentual'] ?? 0));
            $rules['taxa_recebimento_percentual'] = max(0.0, (float) ($servicoOverride['taxa_recebimento_percentual'] ?? $rules['taxa_recebimento_percentual'] ?? 0));
            $rules['imposto_percentual'] = max(0.0, (float) ($servicoOverride['imposto_percentual'] ?? $rules['imposto_percentual'] ?? 0));
            $rules['tempo_padrao_horas'] = max(0.0, (float) ($servicoOverride['tempo_tecnico_horas'] ?? $rules['tempo_padrao_horas'] ?? 0));
            $rules['risco_percentual_componente'] = max(0.0, (float) ($servicoOverride['risco_percentual'] ?? $rules['risco_percentual_componente'] ?? 0));
        }

        $tempoPadrao = $this->normalizeDecimal(
            $servicoRow['tempo_padrao_horas'] ?? $rules['tempo_padrao_horas'],
            (float) ($rules['tempo_padrao_horas'] ?? 1.0)
        );
        if ($tempoPadrao <= 0) {
            $tempoPadrao = (float) ($rules['tempo_padrao_horas'] ?? 1.0);
        }

        $custoDiretoPadrao = max(
            0.0,
            $this->normalizeDecimal($servicoRow['custo_direto_padrao'] ?? 0, 0.0)
        );
        $custoDiretoComponente = max(0.0, (float) ($rules['custo_direto_componente'] ?? 0));
        if ($servicoOverride !== null) {
            $custoDiretoPadrao = max(0.0, (float) ($servicoOverride['custos_diretos_total'] ?? $custoDiretoPadrao));
            $custoDiretoComponente = 0.0;
        }
        $custoDiretoTotal = round($custoDiretoPadrao + $custoDiretoComponente, 2);

        $custoHora = max(0.0, (float) ($rules['custo_hora_produtiva'] ?? 0));
        $custoMaoObra = round($tempoPadrao * $custoHora, 2);
        $custoBase = round($custoMaoObra + $custoDiretoTotal, 2);

        $riscoPercentual = max(0.0, (float) ($rules['risco_percentual_componente'] ?? 0));
        $valorRisco = round($custoBase * ($riscoPercentual / 100), 2);
        $custoTotal = round($custoBase + $valorRisco, 2);

        $margem = max(0.0, (float) ($rules['margem_percentual'] ?? 0));
        $taxa = max(0.0, (float) ($rules['taxa_recebimento_percentual'] ?? 0));
        $imposto = max(0.0, (float) ($rules['imposto_percentual'] ?? 0));

        $divisor = 1 - (($margem + $taxa + $imposto) / 100);
        if ($divisor <= 0.01) {
            $divisor = 0.01;
        }

        $precoMinimo = round($custoTotal / $divisor, 2);
        $valorCadastro = max(0.0, (float) ($servicoRow['valor'] ?? 0));
        if ($servicoOverride !== null) {
            $valorCadastro = max($valorCadastro, (float) ($servicoOverride['preco_tabela_referencia'] ?? 0));
        }
        $valorRecomendado = max($precoMinimo, $valorCadastro);

        return [
            'tempo_padrao_horas' => round($tempoPadrao, 2),
            'custo_hora_produtiva' => round($custoHora, 2),
            'custo_mao_obra' => $custoMaoObra,
            'custo_direto_padrao' => round($custoDiretoPadrao, 2),
            'custo_direto_componente' => round($custoDiretoComponente, 2),
            'custo_direto_total' => $custoDiretoTotal,
            'risco_percentual' => round($riscoPercentual, 2),
            'valor_risco' => $valorRisco,
            'custo_total' => $custoTotal,
            'margem_percentual' => round($margem, 2),
            'taxa_recebimento_percentual' => round($taxa, 2),
            'imposto_percentual' => round($imposto, 2),
            'divisor_preco' => round($divisor, 4),
            'preco_minimo' => $precoMinimo,
            'valor_cadastro' => round($valorCadastro, 2),
            'valor_recomendado' => round($valorRecomendado, 2),
            'modo_precificacao' => $valorRecomendado > $valorCadastro ? 'servico_auto_recomendado' : 'servico_cadastro',
            'categoria_override' => $categoriaOverride !== null ? [
                'categoria' => $categoriaOverrideNome,
                'risco_percentual' => round((float) ($categoriaOverride['encargos_percentual'] ?? 0), 2),
                'margem_percentual' => round((float) ($categoriaOverride['margem_percentual'] ?? 0), 2),
            ] : null,
            'servico_override' => $servicoOverride !== null ? [
                'servico_id' => $servicoId,
                'custo_hora_produtiva' => round((float) ($servicoOverride['custo_hora_produtiva'] ?? 0), 2),
                'custos_diretos_total' => round((float) ($servicoOverride['custos_diretos_total'] ?? 0), 2),
                'tempo_tecnico_horas' => round((float) ($servicoOverride['tempo_tecnico_horas'] ?? 0), 2),
                'risco_percentual' => round((float) ($servicoOverride['risco_percentual'] ?? 0), 2),
                'margem_percentual' => round((float) ($servicoOverride['margem_percentual'] ?? 0), 2),
                'taxa_recebimento_percentual' => round((float) ($servicoOverride['taxa_recebimento_percentual'] ?? 0), 2),
                'imposto_percentual' => round((float) ($servicoOverride['imposto_percentual'] ?? 0), 2),
                'preco_tabela_referencia' => round((float) ($servicoOverride['preco_tabela_referencia'] ?? 0), 2),
            ] : null,
        ];
    }

    /**
     * @param array<string,mixed> $servicoRow
     * @return array<string,mixed>
     */
    public function applyMinimumPrice(array $servicoRow, float $valorInformado): array
    {
        $quote = $this->buildQuote($servicoRow);
        $valorInformado = max(0.0, $valorInformado);
        $precoMinimo = (float) ($quote['preco_minimo'] ?? 0);
        $valorAplicado = max($valorInformado, $precoMinimo);

        $quote['valor_original_digitado'] = round($valorInformado, 2);
        $quote['valor_aplicado'] = round($valorAplicado, 2);
        $quote['modo_precificacao'] = $valorInformado >= $precoMinimo
            ? 'manual_acima_minimo'
            : 'servico_auto_minimo';

        return $quote;
    }

    private function isTruthy($value): bool
    {
        return !in_array(strtolower(trim((string) $value)), ['0', 'false', 'nao', 'no'], true);
    }

    private function normalizePercent($value, float $fallback): float
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return $fallback;
        }
        $raw = str_replace(',', '.', $raw);
        if (!is_numeric($raw)) {
            return $fallback;
        }
        $percent = (float) $raw;
        if ($percent < 0) {
            return 0;
        }
        if ($percent > 500) {
            return 500;
        }
        return $percent;
    }

    private function normalizeDecimal($value, float $fallback): float
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return $fallback;
        }
        $raw = str_replace(',', '.', $raw);
        if (!is_numeric($raw)) {
            return $fallback;
        }
        return (float) $raw;
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
     * @param array<string,mixed> $servicoRow
     */
    private function resolveCategoriaOverrideNome(array $servicoRow): string
    {
        $candidatos = [
            (string) ($servicoRow['categoria_servico'] ?? ''),
            (string) ($servicoRow['categoria'] ?? ''),
            (string) ($servicoRow['tipo_equipamento'] ?? ''),
        ];

        foreach ($candidatos as $nome) {
            $nome = trim($nome);
            if ($nome === '') {
                continue;
            }
            return function_exists('mb_strtolower') ? mb_strtolower($nome) : strtolower($nome);
        }

        return '';
    }

    /**
     * @return array<string,mixed>|null
     */
    private function getCategoriaOverride(string $categoriaNome): ?array
    {
        if ($categoriaNome === '') {
            return null;
        }
        if (! $this->categoriaModel->isTableReady()) {
            return null;
        }

        $map = $this->categoriaModel->getMapaPorTipo('servico');
        if (!isset($map[$categoriaNome])) {
            return null;
        }

        return $map[$categoriaNome];
    }

    /**
     * @return array<string,mixed>|null
     */
    private function getServicoOverride(int $servicoId): ?array
    {
        if ($servicoId <= 0) {
            return null;
        }

        return $this->servicoOverrideModel->getAtivoByServicoId($servicoId);
    }
}
