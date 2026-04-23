<?php

namespace App\Controllers;

use App\Models\ConfiguracaoModel;
use App\Models\LogModel;
use App\Models\PecaModel;
use App\Models\PrecificacaoCategoriaModel;
use App\Models\PrecificacaoCategoriaEncargoModel;
use App\Models\PrecificacaoComponenteModel;
use App\Models\PrecificacaoParametroModel;
use App\Models\PrecificacaoServicoOverrideModel;
use App\Models\ServicoModel;
use App\Services\PecaPrecificacaoService;
use App\Services\ServicoPrecificacaoService;
use Config\Database;

class Precificacao extends BaseController
{
    private ConfiguracaoModel $configModel;
    private PrecificacaoComponenteModel $componenteModel;
    private PrecificacaoParametroModel $parametroModel;
    private PrecificacaoCategoriaModel $categoriaModel;
    private PrecificacaoCategoriaEncargoModel $categoriaEncargoModel;
    private PrecificacaoServicoOverrideModel $servicoOverrideModel;
    private PecaPrecificacaoService $pecaPrecificacaoService;
    private ServicoPrecificacaoService $servicoPrecificacaoService;

    public function __construct()
    {
        requirePermission('orcamentos', 'visualizar');
        $this->configModel = new ConfiguracaoModel();
        $this->componenteModel = new PrecificacaoComponenteModel();
        $this->parametroModel = new PrecificacaoParametroModel();
        $this->categoriaModel = new PrecificacaoCategoriaModel();
        $this->categoriaEncargoModel = new PrecificacaoCategoriaEncargoModel();
        $this->servicoOverrideModel = new PrecificacaoServicoOverrideModel();
        $this->pecaPrecificacaoService = new PecaPrecificacaoService();
        $this->servicoPrecificacaoService = new ServicoPrecificacaoService();
    }

    public function index()
    {
        return redirect()->to('/precificacao/configuracao');
    }

    public function configuracao()
    {
        $parametrosTableReady = $this->parametroModel->isTableReady();
        $categorias = [
            'peca' => $parametrosTableReady ? $this->parametroModel->getAtivosPorCategoria('peca') : [],
            'servico' => $parametrosTableReady ? $this->parametroModel->getAtivosPorCategoria('servico') : [],
            'produto' => $parametrosTableReady ? $this->parametroModel->getAtivosPorCategoria('produto') : [],
        ];

        $categoriasOverrides = [
            'peca' => $this->categoriaModel->isTableReady() ? $this->categoriaModel->getAtivosPorTipo('peca') : [],
            'produto' => $this->categoriaModel->isTableReady() ? $this->categoriaModel->getAtivosPorTipo('produto') : [],
        ];
        $servicoModel = new ServicoModel();
        $servicosAtivos = $servicoModel->where('status', 'ativo')
            ->where('encerrado_em IS NULL', null, false)
            ->orderBy('nome', 'ASC')
            ->findAll();
        $servicoOverrides = $this->servicoOverrideModel->getAtivos();

        $data = [
            'title' => 'Precificação - Configuração',
            'configs' => $this->loadConfigSnapshot(),
            'parametrosTableReady' => $parametrosTableReady,
            'parametrosCategorias' => $categorias,
            'categoriasOverride' => $categoriasOverrides,
            'servicosAtivos' => $servicosAtivos,
            'servicoOverrides' => $servicoOverrides,
            'resumos' => $this->buildResumoParametros($categorias),
        ];

        return view('precificacao/configuracao', $data);
    }

    public function simulador()
    {
        $componentesTableReady = $this->componenteModel->isTableReady();

        $data = [
            'title' => 'Precificação - Simulador',
            'configs' => $this->loadConfigSnapshot(),
            'componentesTableReady' => $componentesTableReady,
            'componentesPeca' => $componentesTableReady ? $this->componenteModel->getAtivosPorGrupo('encargo_peca_percentual', 'percentual') : [],
            'componentesServicoCusto' => $componentesTableReady ? $this->componenteModel->getAtivosPorGrupo('custo_servico_fixo', 'valor') : [],
            'componentesServicoRisco' => $componentesTableReady ? $this->componenteModel->getAtivosPorGrupo('risco_servico_percentual', 'percentual') : [],
            'pecas' => (new PecaModel())->where('ativo', 1)->orderBy('nome', 'ASC')->findAll(200),
            'servicos' => (new ServicoModel())->where('status', 'ativo')->where('encerrado_em IS NULL', null, false)->orderBy('nome', 'ASC')->findAll(200),
            'rulesPeca' => $this->pecaPrecificacaoService->getRules(),
            'rulesServico' => $this->servicoPrecificacaoService->getRules(),
        ];

        return view('precificacao/index', $data);
    }

    public function saveConfiguracao()
    {
        requirePermission('orcamentos', 'editar');

        if (! $this->parametroModel->isTableReady()) {
            return redirect()->to('/precificacao/configuracao')
                ->with('warning', 'Tabela precificacao_parametros não encontrada. Execute as migrações.');
        }

        $ids = (array) $this->request->getPost('parametro_id');
        $valores = (array) $this->request->getPost('parametro_valor');

        $max = max(count($ids), count($valores));
        for ($i = 0; $i < $max; $i++) {
            $id = (int) ($ids[$i] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $row = $this->parametroModel->find($id);
            if (!is_array($row)) {
                continue;
            }
            if ((int) ($row['editavel'] ?? 0) !== 1) {
                continue;
            }

            $valor = $this->sanitizeParametroValor($row, $valores[$i] ?? '0');
            $this->parametroModel->update($id, [
                'valor' => round($valor, 4),
            ]);
        }

        $this->recalculateDetailedParametros();
        $this->syncEngineConfigFromDetailedParametros();
        $this->syncCategoriasOverrides();
        $this->syncServicoOverrides();

        LogModel::registrar('precificacao_configuracao_detalhada_salva', 'Parâmetros detalhados de precificação atualizados');

        return redirect()->to('/precificacao/configuracao')->with('success', 'Configuração detalhada de precificação atualizada com sucesso.');
    }

    public function categoriaEncargos(int $categoriaId = 0)
    {
        requirePermission('orcamentos', 'visualizar');

        if ($categoriaId <= 0) {
            return $this->response->setJSON(['ok' => false, 'message' => 'Categoria inválida']);
        }

        if (! $this->categoriaEncargoModel->isTableReady()) {
            return $this->response->setJSON(['ok' => false, 'message' => 'Tabela de encargos não encontrada']);
        }

        $rows = $this->categoriaEncargoModel->getAtivosPorCategoria($categoriaId);
        $total = 0.0;
        foreach ($rows as $row) {
            $total += (float) ($row['percentual'] ?? 0);
        }

        return $this->response->setJSON([
            'ok' => true,
            'items' => $rows,
            'total' => round($total, 2),
        ]);
    }

    public function salvarCategoriaEncargos(int $categoriaId = 0)
    {
        requirePermission('orcamentos', 'editar');

        if ($categoriaId <= 0) {
            return $this->response->setJSON(['ok' => false, 'message' => 'Categoria inválida']);
        }

        if (! $this->categoriaEncargoModel->isTableReady()) {
            return $this->response->setJSON(['ok' => false, 'message' => 'Tabela de encargos não encontrada']);
        }

        $ids = (array) $this->request->getPost('encargo_id');
        $nomes = (array) $this->request->getPost('encargo_nome');
        $valores = (array) $this->request->getPost('encargo_valor');

        $max = max(count($ids), count($nomes), count($valores));
        $keepIds = [];
        $ordem = 1;

        for ($i = 0; $i < $max; $i++) {
            $nome = trim((string) ($nomes[$i] ?? ''));
            if ($nome === '') {
                continue;
            }
            $valor = $this->toFloat($valores[$i] ?? '0');
            if ($valor < 0) {
                $valor = 0;
            }

            $payload = [
                'categoria_id' => $categoriaId,
                'nome' => function_exists('mb_substr') ? mb_substr($nome, 0, 140) : substr($nome, 0, 140),
                'percentual' => round($valor, 2),
                'ativo' => 1,
                'ordem' => $ordem,
            ];

            $id = (int) ($ids[$i] ?? 0);
            if ($id > 0) {
                $this->categoriaEncargoModel->update($id, $payload);
                $keepIds[] = $id;
            } else {
                $newId = $this->categoriaEncargoModel->insert($payload, true);
                if ($newId) {
                    $keepIds[] = (int) $newId;
                }
            }

            $ordem++;
        }

        $existentes = $this->categoriaEncargoModel->where('categoria_id', $categoriaId)->findAll();
        foreach ($existentes as $row) {
            $rowId = (int) ($row['id'] ?? 0);
            if ($rowId <= 0) {
                continue;
            }
            if (!in_array($rowId, $keepIds, true)) {
                $this->categoriaEncargoModel->update($rowId, ['ativo' => 0]);
            }
        }

        $total = $this->categoriaEncargoModel->selectSum('percentual')->where('categoria_id', $categoriaId)->where('ativo', 1)->get()->getRowArray();
        $sum = (float) ($total['percentual'] ?? 0);

        return $this->response->setJSON([
            'ok' => true,
            'total' => round($sum, 2),
        ]);
    }

    public function categoriaOverride()
    {
        requirePermission('orcamentos', 'visualizar');

        $tipo = strtolower(trim((string) $this->request->getGet('tipo')));
        $categoriaNome = trim((string) $this->request->getGet('categoria'));

        if (!in_array($tipo, ['peca', 'servico', 'produto'], true)) {
            return $this->response->setJSON(['ok' => false, 'message' => 'Tipo inválido']);
        }

        $categoriaKey = function_exists('mb_strtolower') ? mb_strtolower($categoriaNome) : strtolower($categoriaNome);
        $categoriaKey = trim($categoriaKey);

        $override = null;
        if ($categoriaKey !== '' && $this->categoriaModel->isTableReady()) {
            $map = $this->categoriaModel->getMapaPorTipo($tipo);
            if (isset($map[$categoriaKey])) {
                $override = [
                    'encargos_percentual' => (float) ($map[$categoriaKey]['encargos_percentual'] ?? 0),
                    'margem_percentual' => (float) ($map[$categoriaKey]['margem_percentual'] ?? 0),
                ];
            }
        }

        return $this->response->setJSON([
            'ok' => true,
            'override' => $override,
        ]);
    }

    public function save()
    {
        requirePermission('orcamentos', 'editar');

        $payload = $this->request->getPost();
        $componentesTableReady = $this->componenteModel->isTableReady();

        $this->configModel->setConfig('precificacao_peca_base', $this->normalizeBase($payload['precificacao_peca_base'] ?? 'custo'));
        $this->configModel->setConfig('precificacao_peca_margem_percentual', $this->formatDecimal($payload['precificacao_peca_margem_percentual'] ?? '45', 2, 0, 300));
        $this->configModel->setConfig('precificacao_peca_encargos_percentual', $this->formatDecimal($payload['precificacao_peca_encargos_percentual'] ?? '15', 2, 0, 300));
        $this->configModel->setConfig('precificacao_peca_respeitar_preco_venda', $this->normalizeBool($payload['precificacao_peca_respeitar_preco_venda'] ?? '1'));
        $this->configModel->setConfig('precificacao_peca_usa_componentes', $this->normalizeBool($payload['precificacao_peca_usa_componentes'] ?? '1'));

        $this->configModel->setConfig('precificacao_servico_custo_hora_produtiva', $this->formatDecimal($payload['precificacao_servico_custo_hora_produtiva'] ?? '40', 2, 0, 100000));
        $this->configModel->setConfig('precificacao_servico_margem_percentual', $this->formatDecimal($payload['precificacao_servico_margem_percentual'] ?? '25', 2, 0, 300));
        $this->configModel->setConfig('precificacao_servico_taxa_recebimento_percentual', $this->formatDecimal($payload['precificacao_servico_taxa_recebimento_percentual'] ?? '3.5', 2, 0, 100));
        $this->configModel->setConfig('precificacao_servico_imposto_percentual', $this->formatDecimal($payload['precificacao_servico_imposto_percentual'] ?? '0', 2, 0, 100));
        $this->configModel->setConfig('precificacao_servico_tempo_padrao_horas', $this->formatDecimal($payload['precificacao_servico_tempo_padrao_horas'] ?? '1', 2, 0.1, 200));
        $this->configModel->setConfig('precificacao_servico_usa_componentes', $this->normalizeBool($payload['precificacao_servico_usa_componentes'] ?? '1'));
        $this->configModel->setConfig('precificacao_servico_aplicar_catalogo', $this->normalizeBool($payload['precificacao_servico_aplicar_catalogo'] ?? '1'));
        $this->configModel->setConfig('precificacao_servico_aplicar_piso', $this->normalizeBool($payload['precificacao_servico_aplicar_piso'] ?? '0'));

        if ($componentesTableReady) {
            $this->syncComponents(
                'encargo_peca_percentual',
                'percentual',
                (array) ($payload['componentes_peca_id'] ?? []),
                (array) ($payload['componentes_peca_nome'] ?? []),
                (array) ($payload['componentes_peca_valor'] ?? [])
            );
            $this->syncComponents(
                'custo_servico_fixo',
                'valor',
                (array) ($payload['componentes_servico_custo_id'] ?? []),
                (array) ($payload['componentes_servico_custo_nome'] ?? []),
                (array) ($payload['componentes_servico_custo_valor'] ?? [])
            );
            $this->syncComponents(
                'risco_servico_percentual',
                'percentual',
                (array) ($payload['componentes_servico_risco_id'] ?? []),
                (array) ($payload['componentes_servico_risco_nome'] ?? []),
                (array) ($payload['componentes_servico_risco_valor'] ?? [])
            );

            $encargosPecaTotal = $this->componenteModel->somarValorAtivoPorGrupo('encargo_peca_percentual', 'percentual');
            if ($encargosPecaTotal > 0) {
                $this->configModel->setConfig('precificacao_peca_encargos_percentual', number_format($encargosPecaTotal, 2, '.', ''));
            }
        }

        LogModel::registrar('precificacao_atualizada', 'Módulo de precificação atualizado');

        $redirect = redirect()->to('/precificacao')->with('success', 'Configurações de precificação salvas com sucesso.');
        if (! $componentesTableReady) {
            return $redirect->with('warning', 'Tabela precificacao_componentes não encontrada. Execute as migrações para habilitar componentes.');
        }

        return $redirect;
    }

    public function simularPeca()
    {
        requirePermission('orcamentos', 'visualizar');

        $precoCusto = $this->toFloat($this->request->getPost('preco_custo') ?? '0');
        $precoVenda = $this->toFloat($this->request->getPost('preco_venda') ?? '0');
        $pecaId = (int) ($this->request->getPost('peca_id') ?? 0);

        $row = [
            'preco_custo' => $precoCusto,
            'preco_venda' => $precoVenda,
        ];
        if ($pecaId > 0) {
            $peca = (new PecaModel())->find($pecaId);
            if (is_array($peca)) {
                $row = array_merge($row, $peca);
            }
        }

        $quote = $this->pecaPrecificacaoService->buildQuote($row);

        return $this->response->setJSON([
            'ok' => true,
            'quote' => $quote,
        ]);
    }

    public function simularServico()
    {
        requirePermission('orcamentos', 'visualizar');

        $servicoId = (int) ($this->request->getPost('servico_id') ?? 0);
        $tempo = $this->toFloat($this->request->getPost('tempo_horas') ?? '0');
        $custoDireto = $this->toFloat($this->request->getPost('custo_direto_padrao') ?? '0');
        $valorCadastro = $this->toFloat($this->request->getPost('valor_cadastro') ?? '0');

        $row = [
            'tempo_padrao_horas' => $tempo > 0 ? $tempo : null,
            'custo_direto_padrao' => max(0.0, $custoDireto),
            'valor' => max(0.0, $valorCadastro),
        ];
        if ($servicoId > 0) {
            $servico = (new ServicoModel())->find($servicoId);
            if (is_array($servico)) {
                $row = array_merge($servico, $row);
            }
        }

        $quote = $this->servicoPrecificacaoService->buildQuote($row);

        return $this->response->setJSON([
            'ok' => true,
            'quote' => $quote,
        ]);
    }

    /**
     * @return array<string,string>
     */
    private function loadConfigSnapshot(): array
    {
        $keys = [
            'precificacao_peca_base' => 'custo',
            'precificacao_peca_margem_percentual' => '45',
            'precificacao_peca_encargos_percentual' => '15',
            'precificacao_peca_respeitar_preco_venda' => '1',
            'precificacao_peca_usa_componentes' => '1',
            'precificacao_servico_custo_hora_produtiva' => '40',
            'precificacao_servico_margem_percentual' => '25',
            'precificacao_servico_taxa_recebimento_percentual' => '3.5',
            'precificacao_servico_imposto_percentual' => '0',
            'precificacao_servico_tempo_padrao_horas' => '1',
            'precificacao_servico_usa_componentes' => '1',
            'precificacao_servico_aplicar_catalogo' => '1',
            'precificacao_servico_aplicar_piso' => '0',
        ];

        $snapshot = [];
        foreach ($keys as $key => $default) {
            $snapshot[$key] = (string) $this->configModel->get($key, $default);
        }

        return $snapshot;
    }

    /**
     * @param array<int,mixed> $ids
     * @param array<int,mixed> $nomes
     * @param array<int,mixed> $valores
     */
    private function syncComponents(string $grupo, string $tipoValor, array $ids, array $nomes, array $valores): void
    {
        $max = max(count($ids), count($nomes), count($valores));
        $keepIds = [];
        $ordem = 1;

        for ($i = 0; $i < $max; $i++) {
            $nome = trim((string) ($nomes[$i] ?? ''));
            $valor = $this->toFloat($valores[$i] ?? '0');
            $id = (int) ($ids[$i] ?? 0);

            if ($nome === '') {
                continue;
            }

            if ($valor < 0) {
                $valor = 0;
            }

            $payload = [
                'grupo' => $grupo,
                'nome' => function_exists('mb_substr') ? mb_substr($nome, 0, 120) : substr($nome, 0, 120),
                'tipo_valor' => $tipoValor,
                'valor' => round($valor, 4),
                'origem' => 'manual',
                'ativo' => 1,
                'ordem' => $ordem,
            ];

            if ($id > 0) {
                $this->componenteModel->update($id, $payload);
                $keepIds[] = $id;
            } else {
                $newId = $this->componenteModel->insert($payload, true);
                if ($newId) {
                    $keepIds[] = (int) $newId;
                }
            }

            $ordem++;
        }

        $existentes = $this->componenteModel->where('grupo', $grupo)->findAll();
        foreach ($existentes as $row) {
            $rowId = (int) ($row['id'] ?? 0);
            if ($rowId <= 0) {
                continue;
            }
            if (!in_array($rowId, $keepIds, true)) {
                $this->componenteModel->update($rowId, ['ativo' => 0]);
            }
        }
    }

    private function normalizeBase($value): string
    {
        $base = strtolower(trim((string) $value));
        return in_array($base, ['custo', 'venda'], true) ? $base : 'custo';
    }

    private function normalizeBool($value): string
    {
        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'sim', 'yes'], true) ? '1' : '0';
    }

    private function formatDecimal($value, int $precision, float $min, float $max): string
    {
        $normalized = $this->toFloat($value);
        if ($normalized < $min) {
            $normalized = $min;
        }
        if ($normalized > $max) {
            $normalized = $max;
        }

        return number_format($normalized, $precision, '.', '');
    }

    private function toFloat($value): float
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return 0.0;
        }
        if (str_contains($raw, ',') && str_contains($raw, '.')) {
            $raw = str_replace('.', '', $raw);
        }
        $raw = str_replace(',', '.', $raw);
        if (!is_numeric($raw)) {
            return 0.0;
        }

        return (float) $raw;
    }

    /**
     * @param array<string,mixed> $categorias
     * @return array<string,array<int,array<string,string>>>
     */
    private function buildResumoParametros(array $categorias): array
    {
        $resumos = [];

        foreach ($categorias as $categoria => $rows) {
            $map = [];
            foreach ((array) $rows as $row) {
                $codigo = (string) ($row['codigo'] ?? '');
                if ($codigo !== '') {
                    $map[$codigo] = (float) ($row['valor'] ?? 0);
                }
            }

            if ($categoria === 'peca') {
                $resumos[$categoria] = [
                    ['label' => 'Custo fornecedor', 'valor' => number_format((float) ($map['peca_custo_fornecedor_liquido'] ?? 0), 2, ',', '.')],
                    ['label' => 'Encargos totais', 'valor' => number_format((float) ($map['peca_encargos_total_percentual'] ?? 0), 2, ',', '.') . '%'],
                    ['label' => 'Margem', 'valor' => number_format((float) ($map['peca_margem_percentual'] ?? 0), 2, ',', '.') . '%'],
                    ['label' => 'Preço recomendado', 'valor' => number_format((float) ($map['peca_preco_instalada_recomendado'] ?? 0), 2, ',', '.')],
                ];
                continue;
            }

            if ($categoria === 'servico') {
                $resumos[$categoria] = [
                    ['label' => 'Custo hora', 'valor' => number_format((float) ($map['servico_custo_hora_produtiva'] ?? 0), 2, ',', '.')],
                    ['label' => 'Tempo técnico', 'valor' => number_format((float) ($map['servico_tempo_tecnico_horas'] ?? 0), 2, ',', '.') . 'h'],
                    ['label' => 'Preço mínimo', 'valor' => number_format((float) ($map['servico_preco_minimo_tecnico'] ?? 0), 2, ',', '.')],
                    ['label' => 'Preço tabela', 'valor' => number_format((float) ($map['servico_preco_tabela_referencia'] ?? 0), 2, ',', '.')],
                ];
                continue;
            }

            if ($categoria === 'produto') {
                $resumos[$categoria] = [
                    ['label' => 'Custo líquido', 'valor' => number_format((float) ($map['produto_custo_liquido'] ?? 0), 2, ',', '.')],
                    ['label' => 'Encargos', 'valor' => number_format((float) ($map['produto_encargos_operacionais_percentual'] ?? 0), 2, ',', '.') . '%'],
                    ['label' => 'Margem', 'valor' => number_format((float) ($map['produto_margem_percentual'] ?? 0), 2, ',', '.') . '%'],
                    ['label' => 'Preço sugerido', 'valor' => number_format((float) ($map['produto_preco_sugerido'] ?? 0), 2, ',', '.')],
                ];
                continue;
            }

            $resumos[$categoria] = [];
        }

        return $resumos;
    }

    /**
     * @param array<string,mixed> $row
     */
    private function sanitizeParametroValor(array $row, $rawValue): float
    {
        $valor = $this->toFloat($rawValue);
        $minimo = isset($row['minimo']) ? (float) $row['minimo'] : null;
        $maximo = isset($row['maximo']) ? (float) $row['maximo'] : null;

        if ($minimo !== null && $valor < $minimo) {
            $valor = $minimo;
        }
        if ($maximo !== null && $valor > $maximo) {
            $valor = $maximo;
        }

        return $valor;
    }

    private function recalculateDetailedParametros(): void
    {
        $map = $this->parametroModel->getMapaPorCodigo();
        if (!$map) {
            return;
        }

        $get = static function (array $map, string $codigo, float $default = 0.0): float {
            if (!isset($map[$codigo])) {
                return $default;
            }
            return (float) ($map[$codigo]['valor'] ?? $default);
        };

        // PECA
        $pecaCustoLiquido = $get($map, 'peca_preco_compra')
            + $get($map, 'peca_frete_compra_rateado')
            + $get($map, 'peca_impostos_nao_recuperaveis')
            + $get($map, 'peca_seguro_compra_rateado')
            + $get($map, 'peca_perdas_entrada_valor')
            - $get($map, 'peca_descontos_fornecedor');
        $this->parametroModel->updateValorByCodigo('peca_custo_fornecedor_liquido', $pecaCustoLiquido);

        $pecaEncargosTotal = $get($map, 'peca_triagem_teste_percentual')
            + $get($map, 'peca_risco_garantia_percentual')
            + $get($map, 'peca_armazenagem_obsolescencia_percentual')
            + $get($map, 'peca_processo_compra_conferencia_percentual');
        $this->parametroModel->updateValorByCodigo('peca_encargos_total_percentual', $pecaEncargosTotal);

        $pecaMargemPercentual = $get($map, 'peca_margem_percentual');
        $pecaEncargosValor = $pecaCustoLiquido * ($pecaEncargosTotal / 100);
        $pecaMargemValor = $pecaCustoLiquido * ($pecaMargemPercentual / 100);
        $this->parametroModel->updateValorByCodigo('peca_preco_instalada_recomendado', $pecaCustoLiquido + $pecaEncargosValor + $pecaMargemValor);

        // SERVICO
        $tempoTotalMin = $get($map, 'servico_tempo_desmontagem_min')
            + $get($map, 'servico_tempo_substituicao_min')
            + $get($map, 'servico_tempo_montagem_min')
            + $get($map, 'servico_tempo_teste_final_min');
        $tempoHoras = $tempoTotalMin / 60;
        $this->parametroModel->updateValorByCodigo('servico_tempo_tecnico_horas', $tempoHoras);

        $horasMensais = $get($map, 'servico_tecnicos_ativos', 1)
            * $get($map, 'servico_horas_produtivas_dia', 0)
            * $get($map, 'servico_dias_uteis_mes', 0);
        $this->parametroModel->updateValorByCodigo('servico_horas_produtivas_mensais', $horasMensais);

        $custosFixos = $get($map, 'servico_custos_fixos_mensais');
        $custoHora = $horasMensais > 0 ? $custosFixos / $horasMensais : 0.0;
        $this->parametroModel->updateValorByCodigo('servico_custo_hora_produtiva', $custoHora);

        $tempoIndiretoRateado = $get($map, 'servico_tempo_indireto_horas') * $custoHora;
        $this->parametroModel->updateValorByCodigo('servico_tempo_indireto_rateado_valor', $tempoIndiretoRateado);

        $custosDiretos = $get($map, 'servico_consumiveis_valor')
            + $tempoIndiretoRateado
            + $get($map, 'servico_reserva_garantia_valor')
            + $get($map, 'servico_perdas_pequenas_valor');
        $this->parametroModel->updateValorByCodigo('servico_custos_diretos_total', $custosDiretos);

        $maoDeObra = $tempoHoras * $custoHora;
        $baseServico = $maoDeObra + $custosDiretos;
        $riscoPercentual = $get($map, 'servico_risco_percentual');
        $valorRisco = $baseServico * ($riscoPercentual / 100);
        $custoTotalServico = $baseServico + $valorRisco;
        $this->parametroModel->updateValorByCodigo('servico_custo_servico_total', $custoTotalServico);

        $margemServico = $get($map, 'servico_margem_alvo_percentual');
        $taxaRecebimento = $get($map, 'servico_taxa_recebimento_percentual');
        $imposto = $get($map, 'servico_imposto_percentual');
        $divisor = 1 - (($margemServico + $taxaRecebimento + $imposto) / 100);
        if ($divisor < 0.01) {
            $divisor = 0.01;
        }
        $this->parametroModel->updateValorByCodigo('servico_divisor_tecnico', $divisor);
        $precoMinimo = $divisor > 0 ? $custoTotalServico / $divisor : 0.0;
        $this->parametroModel->updateValorByCodigo('servico_preco_minimo_tecnico', $precoMinimo);

        // PRODUTO
        $produtoCusto = $get($map, 'produto_preco_compra')
            + $get($map, 'produto_frete_rateado')
            + $get($map, 'produto_impostos_nao_recuperaveis');
        $this->parametroModel->updateValorByCodigo('produto_custo_liquido', $produtoCusto);

        $produtoPerdas = $produtoCusto * ($get($map, 'produto_perdas_operacionais_percentual') / 100);
        $produtoEncargos = $produtoCusto * ($get($map, 'produto_encargos_operacionais_percentual') / 100);
        $produtoMargem = $produtoCusto * ($get($map, 'produto_margem_percentual') / 100);
        $this->parametroModel->updateValorByCodigo('produto_preco_sugerido', $produtoCusto + $produtoPerdas + $produtoEncargos + $produtoMargem);
    }

    private function syncEngineConfigFromDetailedParametros(): void
    {
        $map = $this->parametroModel->getMapaPorCodigo();
        if (!$map) {
            return;
        }

        $get = static function (array $map, string $codigo, float $default = 0.0): float {
            if (!isset($map[$codigo])) {
                return $default;
            }
            return (float) ($map[$codigo]['valor'] ?? $default);
        };

        $this->configModel->setConfig('precificacao_peca_encargos_percentual', number_format($get($map, 'peca_encargos_total_percentual', 0), 2, '.', ''));
        $this->configModel->setConfig('precificacao_peca_margem_percentual', number_format($get($map, 'peca_margem_percentual', 0), 2, '.', ''));

        $this->configModel->setConfig('precificacao_servico_custo_hora_produtiva', number_format($get($map, 'servico_custo_hora_produtiva', 0), 2, '.', ''));
        $this->configModel->setConfig('precificacao_servico_margem_percentual', number_format($get($map, 'servico_margem_alvo_percentual', 0), 2, '.', ''));
        $this->configModel->setConfig('precificacao_servico_taxa_recebimento_percentual', number_format($get($map, 'servico_taxa_recebimento_percentual', 0), 2, '.', ''));
        $this->configModel->setConfig('precificacao_servico_imposto_percentual', number_format($get($map, 'servico_imposto_percentual', 0), 2, '.', ''));
        $this->configModel->setConfig('precificacao_servico_tempo_padrao_horas', number_format($get($map, 'servico_tempo_tecnico_horas', 1), 2, '.', ''));
    }

    private function syncCategoriasOverrides(): void
    {
        if (! $this->categoriaModel->isTableReady()) {
            return;
        }

        $ids = (array) $this->request->getPost('categoria_id');
        $tipos = (array) $this->request->getPost('categoria_tipo');
        $nomes = (array) $this->request->getPost('categoria_nome');
        $encargos = (array) $this->request->getPost('categoria_encargos');
        $margens = (array) $this->request->getPost('categoria_margem');

        $max = max(count($ids), count($tipos), count($nomes), count($encargos), count($margens));
        $keepIds = [];
        $ordem = 1;

        for ($i = 0; $i < $max; $i++) {
            $tipo = strtolower(trim((string) ($tipos[$i] ?? '')));
            if (!in_array($tipo, ['peca', 'produto'], true)) {
                continue;
            }

            $nome = trim((string) ($nomes[$i] ?? ''));
            if ($nome === '') {
                continue;
            }

            $encargoValor = $this->formatDecimal($encargos[$i] ?? '0', 2, 0, 300);
            $margemValor = $this->formatDecimal($margens[$i] ?? '0', 2, 0, 300);
            $id = (int) ($ids[$i] ?? 0);

            $payload = [
                'tipo' => $tipo,
                'categoria_nome' => function_exists('mb_substr') ? mb_substr($nome, 0, 120) : substr($nome, 0, 120),
                'encargos_percentual' => $encargoValor,
                'margem_percentual' => $margemValor,
                'ativo' => 1,
                'ordem' => $ordem,
            ];

            if ($id > 0) {
                $this->categoriaModel->update($id, $payload);
                $keepIds[] = $id;
            } else {
                $newId = $this->categoriaModel->insert($payload, true);
                if ($newId) {
                    $keepIds[] = (int) $newId;
                }
            }

            $ordem++;
        }

        $existentes = $this->categoriaModel->whereIn('tipo', ['peca', 'produto'])->findAll();
        foreach ($existentes as $row) {
            $rowId = (int) ($row['id'] ?? 0);
            if ($rowId <= 0) {
                continue;
            }
            if (!in_array($rowId, $keepIds, true)) {
                $this->categoriaModel->update($rowId, ['ativo' => 0]);
            }
        }
    }

    private function syncServicoOverrides(): void
    {
        if (! $this->servicoOverrideModel->isTableReady()) {
            return;
        }

        $ids = (array) $this->request->getPost('servico_override_id');
        $servicoIds = (array) $this->request->getPost('servico_override_servico_id');
        $custoHora = (array) $this->request->getPost('servico_override_custo_hora');
        $custosDiretos = (array) $this->request->getPost('servico_override_custos_diretos');
        $margem = (array) $this->request->getPost('servico_override_margem');
        $taxa = (array) $this->request->getPost('servico_override_taxa');
        $imposto = (array) $this->request->getPost('servico_override_imposto');
        $tempoTecnico = (array) $this->request->getPost('servico_override_tempo_tecnico');
        $risco = (array) $this->request->getPost('servico_override_risco');
        $precoTabela = (array) $this->request->getPost('servico_override_preco_tabela');
        $custosFixosMensais = (array) $this->request->getPost('servico_override_custos_fixos_mensais');
        $tecnicosAtivos = (array) $this->request->getPost('servico_override_tecnicos_ativos');
        $horasProdutivasDia = (array) $this->request->getPost('servico_override_horas_produtivas_dia');
        $diasUteisMes = (array) $this->request->getPost('servico_override_dias_uteis_mes');
        $consumiveisValor = (array) $this->request->getPost('servico_override_consumiveis_valor');
        $tempoIndiretoHoras = (array) $this->request->getPost('servico_override_tempo_indireto_horas');
        $reservaGarantiaValor = (array) $this->request->getPost('servico_override_reserva_garantia_valor');
        $perdasPequenasValor = (array) $this->request->getPost('servico_override_perdas_pequenas_valor');
        $tempoDesmontagemMin = (array) $this->request->getPost('servico_override_tempo_desmontagem_min');
        $tempoSubstituicaoMin = (array) $this->request->getPost('servico_override_tempo_substituicao_min');
        $tempoMontagemMin = (array) $this->request->getPost('servico_override_tempo_montagem_min');
        $tempoTesteFinalMin = (array) $this->request->getPost('servico_override_tempo_teste_final_min');
        $detalhesDisponiveis = false;
        try {
            $detalhesDisponiveis = Database::connect()->fieldExists('custos_fixos_mensais', 'precificacao_servico_overrides');
        } catch (\Throwable $e) {
            $detalhesDisponiveis = false;
        }

        $max = max(
            count($ids),
            count($servicoIds),
            count($custoHora),
            count($custosDiretos),
            count($margem),
            count($taxa),
            count($imposto),
            count($tempoTecnico),
            count($risco),
            count($precoTabela),
            count($custosFixosMensais),
            count($tecnicosAtivos),
            count($horasProdutivasDia),
            count($diasUteisMes),
            count($consumiveisValor),
            count($tempoIndiretoHoras),
            count($reservaGarantiaValor),
            count($perdasPequenasValor),
            count($tempoDesmontagemMin),
            count($tempoSubstituicaoMin),
            count($tempoMontagemMin),
            count($tempoTesteFinalMin)
        );

        $keepIds = [];
        $seenServicoIds = [];
        $existentesPorServico = $this->servicoOverrideModel->getMapaPorServicoId();

        for ($i = 0; $i < $max; $i++) {
            $servicoId = (int) ($servicoIds[$i] ?? 0);
            if ($servicoId <= 0) {
                continue;
            }
            if (isset($seenServicoIds[$servicoId])) {
                continue;
            }
            $seenServicoIds[$servicoId] = true;

            $payload = [
                'servico_id' => $servicoId,
                'custo_hora_produtiva' => $this->formatDecimal($custoHora[$i] ?? '0', 4, 0, 100000),
                'custos_diretos_total' => $this->formatDecimal($custosDiretos[$i] ?? '0', 4, 0, 100000),
                'margem_percentual' => $this->formatDecimal($margem[$i] ?? '0', 4, 0, 300),
                'taxa_recebimento_percentual' => $this->formatDecimal($taxa[$i] ?? '0', 4, 0, 100),
                'imposto_percentual' => $this->formatDecimal($imposto[$i] ?? '0', 4, 0, 100),
                'tempo_tecnico_horas' => $this->formatDecimal($tempoTecnico[$i] ?? '0', 4, 0, 999),
                'risco_percentual' => $this->formatDecimal($risco[$i] ?? '0', 4, 0, 100),
                'preco_tabela_referencia' => $this->formatDecimal($precoTabela[$i] ?? '0', 4, 0, 999999),
                'ativo' => 1,
            ];
            if ($detalhesDisponiveis) {
                $payload['custos_fixos_mensais'] = $this->formatDecimal($custosFixosMensais[$i] ?? '0', 4, 0, 9999999);
                $payload['tecnicos_ativos'] = $this->formatDecimal($tecnicosAtivos[$i] ?? '1', 4, 0.01, 1000);
                $payload['horas_produtivas_dia'] = $this->formatDecimal($horasProdutivasDia[$i] ?? '0', 4, 0, 24);
                $payload['dias_uteis_mes'] = $this->formatDecimal($diasUteisMes[$i] ?? '1', 4, 1, 31);
                $payload['consumiveis_valor'] = $this->formatDecimal($consumiveisValor[$i] ?? '0', 4, 0, 999999);
                $payload['tempo_indireto_horas'] = $this->formatDecimal($tempoIndiretoHoras[$i] ?? '0', 4, 0, 24);
                $payload['reserva_garantia_valor'] = $this->formatDecimal($reservaGarantiaValor[$i] ?? '0', 4, 0, 999999);
                $payload['perdas_pequenas_valor'] = $this->formatDecimal($perdasPequenasValor[$i] ?? '0', 4, 0, 999999);
                $payload['tempo_desmontagem_min'] = $this->formatDecimal($tempoDesmontagemMin[$i] ?? '0', 4, 0, 999);
                $payload['tempo_substituicao_min'] = $this->formatDecimal($tempoSubstituicaoMin[$i] ?? '0', 4, 0, 999);
                $payload['tempo_montagem_min'] = $this->formatDecimal($tempoMontagemMin[$i] ?? '0', 4, 0, 999);
                $payload['tempo_teste_final_min'] = $this->formatDecimal($tempoTesteFinalMin[$i] ?? '0', 4, 0, 999);
            }

            $id = (int) ($ids[$i] ?? 0);
            if ($id <= 0 && isset($existentesPorServico[(string) $servicoId])) {
                $id = (int) ($existentesPorServico[(string) $servicoId]['id'] ?? 0);
            }

            if ($id > 0) {
                $this->servicoOverrideModel->update($id, $payload);
                $keepIds[] = $id;
            } else {
                $newId = $this->servicoOverrideModel->insert($payload, true);
                if ($newId) {
                    $keepIds[] = (int) $newId;
                }
            }
        }

        foreach ($this->servicoOverrideModel->getAtivos() as $row) {
            $rowId = (int) ($row['id'] ?? 0);
            if ($rowId <= 0) {
                continue;
            }
            if (!in_array($rowId, $keepIds, true)) {
                $this->servicoOverrideModel->update($rowId, ['ativo' => 0]);
            }
        }
    }
}

