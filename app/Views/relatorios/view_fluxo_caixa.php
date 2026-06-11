<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$realizados = $fluxo['realizados'] ?? [];
$previstos = $fluxo['previstos'] ?? [];
$linhasDiarias = $fluxo['linhas_diarias'] ?? [];
$realizadosPorCategoria = $fluxo['realizados_por_categoria'] ?? [];
$previstosPorCategoria = $fluxo['previstos_por_categoria'] ?? [];
$detalhesDiarios = $fluxo['detalhes_diarios'] ?? [];
$previstosEntradas = [];
$previstosSaidas = [];

$normalizeFilterValue = static function ($value): string {
    $value = trim((string) $value);
    $value = function_exists('mb_strtolower')
        ? mb_strtolower($value, 'UTF-8')
        : strtolower($value);

    $normalized = preg_replace('/\s+/u', ' ', $value);

    return $normalized !== null ? $normalized : $value;
};

$buildFilterKey = static function (string $tipo, string $categoria, string $grupo, string $subgrupo) use ($normalizeFilterValue): string {
    return implode('|', [
        $normalizeFilterValue($tipo),
        $normalizeFilterValue($categoria !== '' ? $categoria : '-'),
        $normalizeFilterValue($grupo !== '' ? $grupo : '-'),
        $normalizeFilterValue($subgrupo !== '' ? $subgrupo : '-'),
    ]);
};

$buildRowFilterKey = static function (array $row) use ($buildFilterKey): string {
    return $buildFilterKey(
        (string) ($row['tipo'] ?? ''),
        (string) ($row['categoria_exibicao'] ?? $row['categoria'] ?? '-'),
        (string) ($row['grupo_dre_resolvido'] ?? $row['grupo_dre'] ?? '-'),
        (string) ($row['subgrupo_dre_resolvido'] ?? $row['subgrupo_dre'] ?? '-')
    );
};

$buildBreakdownFilterKey = static function (array $row) use ($buildFilterKey): string {
    return $buildFilterKey(
        (string) ($row['tipo'] ?? ''),
        (string) ($row['categoria'] ?? '-'),
        (string) ($row['grupo_dre'] ?? '-'),
        (string) ($row['subgrupo_dre'] ?? '-')
    );
};

$buildBreakdownLabel = static function (array $row): string {
    return trim(implode(' / ', array_filter([
        (string) ($row['tipo_label'] ?? '-'),
        (string) ($row['categoria'] ?? '-'),
        (string) ($row['grupo_dre'] ?? '-'),
        (string) ($row['subgrupo_dre'] ?? '-'),
    ], static fn ($value) => trim((string) $value) !== '')));
};

$humanizePaymentMethod = static function (?string $formaPagamento): string {
    $map = [
        'dinheiro' => 'Dinheiro',
        'pix' => 'Pix',
        'cartao_credito' => 'Cartao de credito',
        'cartao_debito' => 'Cartao de debito',
        'transferencia' => 'Transferencia',
        'boleto' => 'Boleto',
        'multiplo' => 'Multiplos recebimentos',
    ];

    $raw = trim((string) ($formaPagamento ?? ''));
    if ($raw === '') {
        return '-';
    }

    if (str_contains($raw, ',')) {
        $labels = [];
        foreach (explode(',', $raw) as $item) {
            $normalizedItem = strtolower(trim((string) $item));
            $label = $map[$normalizedItem] ?? ($normalizedItem !== '' ? ucfirst(str_replace('_', ' ', $normalizedItem)) : '-');
            if ($label !== '-' && ! in_array($label, $labels, true)) {
                $labels[] = $label;
            }
        }

        return $labels !== [] ? implode(', ', $labels) : '-';
    }

    $normalized = strtolower($raw);
    return $map[$normalized] ?? ucfirst(str_replace('_', ' ', $normalized));
};

$isCardPaymentMethod = static function (?string $formaPagamento): bool {
    $normalized = strtolower(trim((string) ($formaPagamento ?? '')));

    return in_array($normalized, ['cartao_credito', 'cartao_debito'], true);
};

$detalhesDiariosJson = json_encode(
    $detalhesDiarios,
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
);

if ($detalhesDiariosJson === false) {
    $detalhesDiariosJson = '{}';
}

foreach ($previstos as $previsto) {
    $tipoPrevisto = strtolower(trim((string) ($previsto['tipo'] ?? '')));

    if ($tipoPrevisto === 'receber') {
        $previstosEntradas[] = $previsto;
    } elseif ($tipoPrevisto === 'pagar') {
        $previstosSaidas[] = $previsto;
    }
}

$dashboardDailyLabels = [];
$dashboardDailyEntradas = [];
$dashboardDailySaidas = [];

foreach ($linhasDiarias as $linha) {
    $dashboardDailyLabels[] = (string) ($linha['data_label'] ?? formatDate($linha['data'] ?? ''));
    $dashboardDailyEntradas[] = round((float) ($linha['entradas_realizadas'] ?? 0), 2);
    $dashboardDailySaidas[] = round((float) ($linha['saidas_realizadas'] ?? 0), 2);
}

$dashboardEntradasCategorias = [];
$dashboardSaidasCategorias = [];

foreach ($realizadosPorCategoria as $item) {
    $tipo = strtolower(trim((string) ($item['tipo'] ?? '')));
    $total = round((float) ($item['total'] ?? 0), 2);

    if ($total <= 0) {
        continue;
    }

    $payload = [
        'label' => (string) ($item['categoria'] ?? '-'),
        'grupo' => (string) ($item['grupo_dre'] ?? '-'),
        'subgrupo' => (string) ($item['subgrupo_dre'] ?? '-'),
        'value' => $total,
    ];

    if ($tipo === 'receber') {
        $dashboardEntradasCategorias[] = $payload;
    } elseif ($tipo === 'pagar') {
        $dashboardSaidasCategorias[] = $payload;
    }
}

$ticketOsTotal = 0.0;
$ticketOsIds = [];

foreach ($realizados as $item) {
    if (strtolower(trim((string) ($item['tipo'] ?? ''))) !== 'receber') {
        continue;
    }

    $osId = (int) ($item['os_id'] ?? 0);
    if ($osId <= 0) {
        continue;
    }

    $ticketOsTotal += (float) ($item['valor_relatorio'] ?? $item['valor'] ?? 0);
    $ticketOsIds[$osId] = true;
}

$ticketOsCount = count($ticketOsIds);
$ticketMedioOs = $ticketOsCount > 0 ? $ticketOsTotal / $ticketOsCount : null;

$titulosAbertosCount = count($previstos);
$titulosAbertosTotal = 0.0;
$titulosVencidosCount = 0;
$titulosVencidosTotal = 0.0;

foreach ($previstos as $item) {
    $valorAberto = (float) ($item['valor_relatorio'] ?? $item['valor_aberto'] ?? $item['valor'] ?? 0);
    $titulosAbertosTotal += $valorAberto;

    if ((int) ($item['esta_vencido'] ?? 0) === 1) {
        $titulosVencidosCount++;
        $titulosVencidosTotal += $valorAberto;
    }
}

$indiceInadimplencia = $titulosAbertosCount > 0
    ? ($titulosVencidosCount / $titulosAbertosCount) * 100
    : 0.0;

$dashboardPayload = [
    'daily' => [
        'labels' => $dashboardDailyLabels,
        'entradas' => $dashboardDailyEntradas,
        'saidas' => $dashboardDailySaidas,
    ],
    'entradasCategorias' => $dashboardEntradasCategorias,
    'saidasCategorias' => $dashboardSaidasCategorias,
];

$dashboardPayloadJson = json_encode(
    $dashboardPayload,
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
);

if ($dashboardPayloadJson === false) {
    $dashboardPayloadJson = '{}';
}
?>

<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h2><i class="bi bi-graph-up-arrow me-2"></i><?= esc($title) ?></h2>
    <div class="d-flex flex-wrap gap-2">
        <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('financeiro')" title="Ajuda sobre Fluxo de Caixa">
            <i class="bi bi-question-circle me-1"></i>Ajuda
        </button>
        <a href="<?= base_url('relatorios') ?>" class="btn btn-outline-secondary" data-back-default="<?= base_url('relatorios') ?>">
            <i class="bi bi-arrow-left me-1"></i>Voltar
        </a>
    </div>
</div>

<div class="card glass-card mb-4">
    <div class="card-body">
        <form method="GET" action="<?= base_url('relatorios/fluxo-caixa') ?>" class="row g-3">
            <div class="col-md-5">
                <label for="mes" class="form-label">Mes/ano</label>
                <input type="month" class="form-control" id="mes" name="mes" value="<?= esc($filtro_mes) ?>">
            </div>
            <div class="col-md-7 d-flex align-items-end gap-2 flex-wrap">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-filter me-1"></i>Atualizar fluxo
                </button>
                <a href="<?= base_url('relatorios/dre?mes=' . urlencode((string) $filtro_mes)) ?>" class="btn btn-outline-info">
                    <i class="bi bi-bar-chart me-1"></i>Ver DRE
                </a>
            </div>
        </form>
    </div>
</div>

<div class="alert alert-light border shadow-sm mb-4">
    <strong>Fluxo de caixa de <?= esc($fluxo['periodo_label'] ?? '') ?>.</strong>
    <span class="d-block mt-1">`Realizado` usa cada `movimento de baixa` pela data real. `Previsto` usa apenas o `saldo em aberto` dos titulos pendentes ou parciais pela data de vencimento. Use os filtros por categoria dentro das abas de `Movimentos` e `Titulos previstos` para refinar a leitura.</span>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card glass-card border-secondary h-100">
            <div class="card-body text-center">
                <h6 class="text-secondary mb-2 uppercase tracking-wider">Saldo inicial</h6>
                <h3 class="m-0"><?= formatMoney($fluxo['saldo_inicial'] ?? 0) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card glass-card border-success h-100">
            <div class="card-body text-center">
                <h6 class="text-success mb-2 uppercase tracking-wider">Entradas realizadas</h6>
                <h3 class="m-0"><?= formatMoney($fluxo['entradas_realizadas'] ?? 0) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card glass-card border-danger h-100">
            <div class="card-body text-center">
                <h6 class="text-danger mb-2 uppercase tracking-wider">Saidas realizadas</h6>
                <h3 class="m-0"><?= formatMoney($fluxo['saidas_realizadas'] ?? 0) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card glass-card <?= ($fluxo['saldo_projetado'] ?? 0) >= 0 ? 'border-primary' : 'border-warning' ?> h-100">
            <div class="card-body text-center">
                <h6 class="<?= ($fluxo['saldo_projetado'] ?? 0) >= 0 ? 'text-primary' : 'text-warning' ?> mb-2 uppercase tracking-wider">Saldo projetado</h6>
                <h3 class="m-0"><?= formatMoney($fluxo['saldo_projetado'] ?? 0) ?></h3>
            </div>
        </div>
    </div>
</div>

<div class="card glass-card cash-tabs-shell mb-4">
    <div class="card-body p-0">
        <div class="cash-tabs-scroller">
            <ul class="nav nav-tabs cash-tab-nav flex-nowrap" id="cashFlowTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="aba-grade-tab" data-bs-toggle="tab" data-bs-target="#aba-grade" type="button" role="tab" aria-controls="aba-grade" aria-selected="true">
                        Grade diaria
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="aba-movimentos-tab" data-bs-toggle="tab" data-bs-target="#aba-movimentos" type="button" role="tab" aria-controls="aba-movimentos" aria-selected="false">
                        Movimentos
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="aba-titulos-tab" data-bs-toggle="tab" data-bs-target="#aba-titulos" type="button" role="tab" aria-controls="aba-titulos" aria-selected="false">
                        Titulos previstos
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="aba-resumo-tab" data-bs-toggle="tab" data-bs-target="#aba-resumo" type="button" role="tab" aria-controls="aba-resumo" aria-selected="false">
                        Resumo
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="aba-dashboard-tab" data-bs-toggle="tab" data-bs-target="#aba-dashboard" type="button" role="tab" aria-controls="aba-dashboard" aria-selected="false">
                        Dashboard
                    </button>
                </li>
            </ul>
        </div>
    </div>
</div>

<div class="tab-content cash-tab-content" id="cashFlowTabsContent">
    <div class="tab-pane fade show active" id="aba-grade" role="tabpanel" aria-labelledby="aba-grade-tab" tabindex="0">
        <div class="card glass-card">
            <div class="card-header bg-transparent border-0 pb-0">
                <h5 class="mb-0">Grade diaria operacional</h5>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    A leitura abaixo acompanha o caixa realizado dia a dia. `Saldo do dia` considera apenas o movimento liquido do proprio dia, enquanto `Acumulado do mes` mostra a soma progressiva do periodo selecionado.
                    Quando houver atividade, use a coluna `Acoes` para abrir o detalhamento operacional.
                </p>
                <div class="table-responsive">
                    <table class="table stack-table cash-daily-grid" id="cashFlowDailyTable">
                        <thead>
                            <tr>
                                <th>Dia</th>
                                <th class="text-end">Entradas</th>
                                <th class="text-end">Saidas</th>
                                <th class="text-end">Saldo do dia</th>
                                <th class="text-end">Acumulado do mes</th>
                                <th class="text-end">Acoes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($linhasDiarias as $linha): ?>
                                <?php
                                $saldoDoDia = (float) ($linha['saldo_do_dia'] ?? 0);
                                $acumuladoMes = (float) ($linha['acumulado_mes'] ?? 0);
                                $linhaData = (string) ($linha['data'] ?? '');
                                $temDetalhesDia = ! empty($linha['tem_detalhes']);
                                $totalOperacoesDia = (int) ($linha['total_operacoes_count'] ?? 0);
                                ?>
                                <tr>
                                    <td data-label="Dia">
                                        <div class="fw-semibold"><?= esc(formatDateWithWeekdayPtBr($linha['data'] ?? null)) ?></div>
                                        <?php if (((float) ($linha['entradas_previstas'] ?? 0) > 0) || ((float) ($linha['saidas_previstas'] ?? 0) > 0)): ?>
                                            <div class="small text-muted">
                                                Previsao: +<?= formatMoney($linha['entradas_previstas'] ?? 0) ?> / -<?= formatMoney($linha['saidas_previstas'] ?? 0) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Entradas" class="text-end text-success fw-semibold"><?= formatMoney($linha['entradas_realizadas'] ?? 0) ?></td>
                                    <td data-label="Saidas" class="text-end text-danger fw-semibold"><?= formatMoney($linha['saidas_realizadas'] ?? 0) ?></td>
                                    <td data-label="Saldo do dia" class="text-end">
                                        <span class="fw-semibold <?= $saldoDoDia >= 0 ? 'text-success' : 'text-danger' ?>">
                                            <?= formatMoney($saldoDoDia) ?>
                                        </span>
                                    </td>
                                    <td data-label="Acumulado do mes" class="text-end">
                                        <div class="fw-semibold <?= $acumuladoMes >= 0 ? 'text-primary' : 'text-danger' ?>">
                                            <?= formatMoney($acumuladoMes) ?>
                                        </div>
                                        <div class="small text-muted">
                                            Caixa: <?= formatMoney($linha['saldo_realizado'] ?? 0) ?>
                                        </div>
                                    </td>
                                    <td data-label="Acoes" class="text-end">
                                        <?php if ($temDetalhesDia): ?>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-primary rounded-pill cash-day-action-btn"
                                                data-cash-day-detail="1"
                                                data-cash-day="<?= esc($linhaData) ?>"
                                                data-cash-day-label="<?= esc(formatDateWithWeekdayPtBr($linha['data'] ?? null)) ?>"
                                            >
                                                <i class="bi bi-eye me-1"></i>Visualizar
                                            </button>
                                            <div class="small text-muted mt-1">
                                                <?= $totalOperacoesDia ?> detalhe(s)
                                            </div>
                                        <?php else: ?>
                                            <span class="small text-muted">Sem detalhes</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="aba-movimentos" role="tabpanel" aria-labelledby="aba-movimentos-tab" tabindex="0">
        <div class="card glass-card">
            <div class="card-header bg-transparent border-0 pb-0">
                <h5 class="mb-1">Movimentos realizados</h5>
                <p class="text-muted small mb-0">Cada linha abaixo representa uma baixa real no periodo, inclusive quando o mesmo titulo teve mais de um movimento.</p>
            </div>
            <div class="card-body">
                <?php if (! empty($realizados)): ?>
                    <?php if (! empty($realizadosPorCategoria)): ?>
                        <div class="cash-category-filter-shell">
                            <div class="cash-category-filter-copy">
                                <h6 class="mb-1">Filtrar realizados por categoria</h6>
                                <p class="text-muted small mb-0">Selecione uma categoria consolidada para limitar a tabela de movimentos realizados.</p>
                            </div>
                            <div class="cash-category-filter-controls">
                                <label for="cashMovementsCategoryFilter" class="form-label">Categoria realizada</label>
                                <select class="form-select cash-category-filter-select" id="cashMovementsCategoryFilter" data-filter-select="movimentos">
                                    <option value="">Todas as categorias realizadas</option>
                                    <?php foreach ($realizadosPorCategoria as $item): ?>
                                        <option
                                            value="<?= esc($buildBreakdownFilterKey($item)) ?>"
                                            data-filter-label="<?= esc($buildBreakdownLabel($item)) ?>"
                                        >
                                            <?= esc($buildBreakdownLabel($item)) ?> - <?= esc((int) ($item['quantidade'] ?? 0)) ?> movimento(s)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="cash-table-filter-banner d-none" data-filter-banner="movimentos">
                        <div>
                            <span class="small text-muted d-block">Filtro por categoria ativo</span>
                            <strong data-filter-label="movimentos">-</strong>
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="badge text-bg-light" data-filter-count="movimentos">0 de 0</span>
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-clear-filter="movimentos">
                                <i class="bi bi-x-circle me-1"></i>Limpar filtro
                            </button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table stack-table cash-report-table" id="cashFlowMovementsTable">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Descricao</th>
                                    <th>Tipo</th>
                                    <th>Referencia</th>
                                    <th>Classificacao</th>
                                    <th class="text-end">Valor</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($realizados as $item): ?>
                                    <?php
                                    $formaPagamentoRaw = (string) ($item['forma_pagamento'] ?? $item['formas_pagamento_resumo'] ?? '');
                                    $formaPagamentoLabel = $humanizePaymentMethod($formaPagamentoRaw);
                                    $taxaOperadora = (float) ($item['cartao_valor_taxa'] ?? 0);
                                    $operadoraCartao = trim((string) ($item['cartao_operadora_nome'] ?? ''));
                                    $temTaxaCartao = $taxaOperadora > 0 && $isCardPaymentMethod($item['forma_pagamento'] ?? null);
                                    ?>
                                    <tr data-filter-key="<?= esc($buildRowFilterKey($item)) ?>">
                                        <td data-label="Data"><?= formatDate($item['data_pagamento_resolvida'] ?? $item['data_pagamento'] ?? '') ?></td>
                                        <td data-label="Descricao">
                                            <div class="fw-semibold"><?= esc($item['descricao'] ?? '-') ?></div>
                                            <div class="small text-muted">
                                                <?= esc($item['categoria_exibicao'] ?? $item['categoria'] ?? '-') ?>
                                                <?php if (! empty($item['numero_os'])): ?>
                                                    <span class="ms-1">OS <?= esc($item['numero_os']) ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="small mt-1 d-flex flex-wrap gap-1">
                                                <span class="badge text-bg-light"><?= ($item['categoria_configurada'] ?? 0) == 1 ? 'Catalogada' : 'Legado/manual' ?></span>
                                                <?php if (((int) ($item['dre_fixo_mensal_resolvido'] ?? 0)) === 1): ?>
                                                    <span class="badge text-bg-info">Fixa DRE</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td data-label="Tipo">
                                            <span class="<?= ($item['tipo'] ?? '') === 'receber' ? 'text-success' : 'text-danger' ?>">
                                                <?= ($item['tipo'] ?? '') === 'receber' ? 'Entrada' : 'Saida' ?>
                                            </span>
                                        </td>
                                        <td data-label="Referencia">
                                            <div class="small">Titulo #<?= (int) ($item['titulo_id'] ?? 0) ?></div>
                                            <div class="small text-muted"><?= esc($formaPagamentoLabel) ?></div>
                                            <?php if ($temTaxaCartao): ?>
                                                <div class="small text-muted">
                                                    Taxa <?= esc($operadoraCartao !== '' ? $operadoraCartao : 'operadora') ?> <?= formatMoney($taxaOperadora) ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td data-label="Classificacao">
                                            <div class="fw-semibold"><?= esc($item['grupo_dre_resolvido'] ?? $item['grupo_dre'] ?? '-') ?></div>
                                            <div class="small text-muted"><?= esc($item['subgrupo_dre_resolvido'] ?? $item['subgrupo_dre'] ?? '-') ?></div>
                                        </td>
                                        <td data-label="Valor" class="text-end"><?= formatMoney($item['valor_relatorio'] ?? $item['valor'] ?? 0) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="alert alert-light border mt-3 d-none" data-filter-empty="movimentos">
                        Nenhum movimento corresponde ao filtro rapido selecionado.
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0">Nenhum movimento realizado no periodo.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="aba-titulos" role="tabpanel" aria-labelledby="aba-titulos-tab" tabindex="0">
        <div class="card glass-card" id="titulos-previstos">
            <div class="card-header bg-transparent border-0 pb-0">
                <h5 class="mb-1">Titulos previstos</h5>
                <p class="text-muted small mb-0">A tabela abaixo mostra apenas o saldo em aberto dos titulos pendentes ou parciais no periodo selecionado.</p>
            </div>
            <div class="card-body">
                <?php if (! empty($previstos)): ?>
                    <?php if (! empty($previstosPorCategoria)): ?>
                        <div class="cash-category-filter-shell">
                            <div class="cash-category-filter-copy">
                                <h6 class="mb-1">Filtrar previstos por categoria</h6>
                                <p class="text-muted small mb-0">Selecione uma categoria consolidada para limitar a tabela de titulos previstos.</p>
                            </div>
                            <div class="cash-category-filter-controls">
                                <label for="cashForecastCategoryFilter" class="form-label">Categoria prevista</label>
                                <select class="form-select cash-category-filter-select" id="cashForecastCategoryFilter" data-filter-select="titulos">
                                    <option value="">Todas as categorias previstas</option>
                                    <?php foreach ($previstosPorCategoria as $item): ?>
                                        <option
                                            value="<?= esc($buildBreakdownFilterKey($item)) ?>"
                                            data-filter-label="<?= esc($buildBreakdownLabel($item)) ?>"
                                        >
                                            <?= esc($buildBreakdownLabel($item)) ?> - <?= esc((int) ($item['quantidade'] ?? 0)) ?> titulo(s)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="cash-table-filter-banner d-none" data-filter-banner="titulos">
                        <div>
                            <span class="small text-muted d-block">Filtro por categoria ativo</span>
                            <strong data-filter-label="titulos">-</strong>
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="badge text-bg-light" data-filter-count="titulos">0 de 0</span>
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-clear-filter="titulos">
                                <i class="bi bi-x-circle me-1"></i>Limpar filtro
                            </button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table stack-table cash-report-table" id="cashFlowForecastTable">
                            <thead>
                                <tr>
                                    <th>Vencimento</th>
                                    <th>Descricao</th>
                                    <th>Tipo</th>
                                    <th>Status</th>
                                    <th>Classificacao</th>
                                    <th class="text-end">Valor</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($previstos as $item): ?>
                                    <tr data-filter-key="<?= esc($buildRowFilterKey($item)) ?>">
                                        <td data-label="Vencimento"><?= formatDate($item['data_vencimento'] ?? '') ?></td>
                                        <td data-label="Descricao">
                                            <div class="fw-semibold"><?= esc($item['descricao'] ?? '-') ?></div>
                                            <div class="small text-muted">
                                                <?= esc($item['categoria_exibicao'] ?? $item['categoria'] ?? '-') ?>
                                                <?php if (! empty($item['numero_os'])): ?>
                                                    <span class="ms-1">OS <?= esc($item['numero_os']) ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="small mt-1 d-flex flex-wrap gap-1">
                                                <span class="badge text-bg-light"><?= ($item['categoria_configurada'] ?? 0) == 1 ? 'Catalogada' : 'Legado/manual' ?></span>
                                                <?php if (((int) ($item['dre_fixo_mensal_resolvido'] ?? 0)) === 1): ?>
                                                    <span class="badge text-bg-info">Fixa DRE</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td data-label="Tipo">
                                            <span class="<?= ($item['tipo'] ?? '') === 'receber' ? 'text-success' : 'text-danger' ?>">
                                                <?= ($item['tipo'] ?? '') === 'receber' ? 'Entrada' : 'Saida' ?>
                                            </span>
                                        </td>
                                        <td data-label="Status">
                                            <span class="badge <?= (($item['status_resolvido'] ?? '') === 'parcial') ? 'bg-warning text-dark' : 'bg-secondary' ?>">
                                                <?= (($item['status_resolvido'] ?? '') === 'parcial') ? 'Parcial' : 'Pendente' ?>
                                            </span>
                                            <div class="small text-success mt-1">Quitado <?= formatMoney($item['valor_movimentado'] ?? 0) ?></div>
                                        </td>
                                        <td data-label="Classificacao">
                                            <div class="fw-semibold"><?= esc($item['grupo_dre_resolvido'] ?? $item['grupo_dre'] ?? '-') ?></div>
                                            <div class="small text-muted"><?= esc($item['subgrupo_dre_resolvido'] ?? $item['subgrupo_dre'] ?? '-') ?></div>
                                        </td>
                                        <td data-label="Valor" class="text-end">
                                            <div class="fw-semibold"><?= formatMoney($item['valor_relatorio'] ?? $item['valor_aberto'] ?? 0) ?></div>
                                            <div class="small text-muted">Titulo <?= formatMoney($item['valor_titulo'] ?? $item['valor'] ?? 0) ?></div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="alert alert-light border mt-3 d-none" data-filter-empty="titulos">
                        Nenhum titulo previsto corresponde ao filtro rapido selecionado.
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0">Nenhum titulo pendente com vencimento no periodo.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="aba-resumo" role="tabpanel" aria-labelledby="aba-resumo-tab" tabindex="0">
        <div class="row g-4 justify-content-center">
            <div class="col-12 col-xxl-8">
                <div class="card glass-card">
                    <div class="card-header bg-transparent border-0 pb-0">
                        <h5 class="mb-0">Resumo do periodo</h5>
                    </div>
                    <div class="card-body">
                        <div class="cash-line">
                            <span>Saldo inicial</span>
                            <strong><?= formatMoney($fluxo['saldo_inicial'] ?? 0) ?></strong>
                        </div>
                        <div class="cash-line text-success">
                            <span>(+) Entradas realizadas</span>
                            <strong><?= formatMoney($fluxo['entradas_realizadas'] ?? 0) ?></strong>
                        </div>
                        <div class="cash-line text-danger">
                            <span>(-) Saidas realizadas</span>
                            <strong><?= formatMoney($fluxo['saidas_realizadas'] ?? 0) ?></strong>
                        </div>
                        <div class="cash-line fw-bold border-top pt-3 mt-3">
                            <span>= Saldo final realizado</span>
                            <strong><?= formatMoney($fluxo['saldo_final'] ?? 0) ?></strong>
                        </div>
                        <div class="cash-line text-success">
                            <span>(+) Entradas previstas</span>
                            <strong><?= formatMoney($fluxo['entradas_previstas'] ?? 0) ?></strong>
                        </div>
                        <div class="cash-proof-group">
                            <div class="cash-proof-heading">
                                <?= count($previstosEntradas) ?> titulo(s) compoem este total.
                                <a href="#titulos-previstos">Ver tabela completa</a>
                            </div>
                            <?php if (! empty($previstosEntradas)): ?>
                                <?php foreach (array_slice($previstosEntradas, 0, 3) as $item): ?>
                                    <?php
                                    $osId = (int) ($item['os_id'] ?? 0);
                                    $linkDestino = $osId > 0 ? base_url('os/visualizar/' . $osId) : '#titulos-previstos';
                                    ?>
                                    <a href="<?= esc($linkDestino) ?>" class="cash-proof-item">
                                        <div class="cash-proof-copy">
                                            <div class="fw-semibold text-dark">
                                                <?= esc($item['descricao'] ?? ('Titulo #' . (int) ($item['titulo_id'] ?? 0))) ?>
                                            </div>
                                            <div class="small text-muted">
                                                Venc. <?= esc(formatDate($item['data_vencimento'] ?? '')) ?>
                                                <?php if (! empty($item['numero_os'])): ?>
                                                    <span class="ms-1">OS <?= esc($item['numero_os']) ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if ((float) ($item['valor_movimentado'] ?? 0) > 0): ?>
                                                <div class="small text-success">
                                                    Ja recebido <?= formatMoney($item['valor_movimentado'] ?? 0) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-end">
                                            <div class="fw-semibold text-success">
                                                <?= formatMoney($item['valor_relatorio'] ?? 0) ?>
                                            </div>
                                            <div class="small text-muted">em aberto</div>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                                <?php if (count($previstosEntradas) > 3): ?>
                                    <div class="cash-proof-more">
                                        +<?= count($previstosEntradas) - 3 ?> outro(s) na tabela completa.
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="cash-proof-empty">Nenhum titulo previsto de entrada neste periodo.</div>
                            <?php endif; ?>
                        </div>
                        <div class="cash-line text-danger">
                            <span>(-) Saidas previstas</span>
                            <strong><?= formatMoney($fluxo['saidas_previstas'] ?? 0) ?></strong>
                        </div>
                        <div class="cash-proof-group">
                            <div class="cash-proof-heading">
                                <?= count($previstosSaidas) ?> titulo(s) compoem este total.
                                <a href="#titulos-previstos">Ver tabela completa</a>
                            </div>
                            <?php if (! empty($previstosSaidas)): ?>
                                <?php foreach (array_slice($previstosSaidas, 0, 3) as $item): ?>
                                    <?php
                                    $osId = (int) ($item['os_id'] ?? 0);
                                    $linkDestino = $osId > 0 ? base_url('os/visualizar/' . $osId) : '#titulos-previstos';
                                    ?>
                                    <a href="<?= esc($linkDestino) ?>" class="cash-proof-item">
                                        <div class="cash-proof-copy">
                                            <div class="fw-semibold text-dark">
                                                <?= esc($item['descricao'] ?? ('Titulo #' . (int) ($item['titulo_id'] ?? 0))) ?>
                                            </div>
                                            <div class="small text-muted">
                                                Venc. <?= esc(formatDate($item['data_vencimento'] ?? '')) ?>
                                                <?php if (! empty($item['numero_os'])): ?>
                                                    <span class="ms-1">OS <?= esc($item['numero_os']) ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if ((float) ($item['valor_movimentado'] ?? 0) > 0): ?>
                                                <div class="small text-danger">
                                                    Ja pago <?= formatMoney($item['valor_movimentado'] ?? 0) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-end">
                                            <div class="fw-semibold text-danger">
                                                <?= formatMoney($item['valor_relatorio'] ?? 0) ?>
                                            </div>
                                            <div class="small text-muted">em aberto</div>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                                <?php if (count($previstosSaidas) > 3): ?>
                                    <div class="cash-proof-more">
                                        +<?= count($previstosSaidas) - 3 ?> outro(s) na tabela completa.
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="cash-proof-empty">Nenhum titulo previsto de saida neste periodo.</div>
                            <?php endif; ?>
                        </div>
                        <div class="cash-line fw-bold border-top pt-3 mt-3">
                            <span>= Saldo projetado</span>
                            <strong><?= formatMoney($fluxo['saldo_projetado'] ?? 0) ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="aba-dashboard" role="tabpanel" aria-labelledby="aba-dashboard-tab" tabindex="0">
        <div class="row g-4 mb-4">
            <div class="col-12 col-xl-6">
                <div class="card glass-card h-100">
                    <div class="card-header bg-transparent border-0 pb-0">
                        <h5 class="mb-1">Entradas x Saidas por dia</h5>
                        <p class="text-muted small mb-0">Leitura diaria do caixa realizado para apoiar o fechamento operacional do mes.</p>
                    </div>
                    <div class="card-body">
                        <div class="cash-chart-shell">
                            <canvas id="cashDashboardDailyChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-6">
                <div class="card glass-card h-100">
                    <div class="card-header bg-transparent border-0 pb-0">
                        <h5 class="mb-1">Entradas realizadas por categoria</h5>
                        <p class="text-muted small mb-0">Distribuicao das entradas efetivamente recebidas no periodo.</p>
                    </div>
                    <div class="card-body">
                        <div class="cash-chart-shell cash-chart-shell-sm">
                            <canvas id="cashDashboardEntradasChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-6">
                <div class="card glass-card h-100">
                    <div class="card-header bg-transparent border-0 pb-0">
                        <h5 class="mb-1">Saidas realizadas por categoria</h5>
                        <p class="text-muted small mb-0">Distribuicao das saidas efetivamente pagas no periodo.</p>
                    </div>
                    <div class="card-body">
                        <div class="cash-chart-shell cash-chart-shell-sm">
                            <canvas id="cashDashboardSaidasChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-sm-6 col-xl-3">
                <div class="card glass-card h-100 cash-dashboard-kpi cash-dashboard-kpi-primary">
                    <div class="card-body">
                        <span class="cash-dashboard-kpi-label">Ticket medio de OS</span>
                        <strong><?= $ticketMedioOs !== null ? formatMoney($ticketMedioOs) : 'Nao disponivel' ?></strong>
                        <p class="mb-0">
                            <?php if ($ticketOsCount > 0): ?>
                                <?= $ticketOsCount ?> OS com entrada realizada no periodo.
                            <?php else: ?>
                                Nenhuma OS com recebimento realizado no periodo.
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="card glass-card h-100 cash-dashboard-kpi cash-dashboard-kpi-neutral">
                    <div class="card-body">
                        <span class="cash-dashboard-kpi-label">Titulos em aberto</span>
                        <strong><?= (int) $titulosAbertosCount ?></strong>
                        <p class="mb-0">Saldo aberto de <?= formatMoney($titulosAbertosTotal) ?> no periodo.</p>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="card glass-card h-100 cash-dashboard-kpi cash-dashboard-kpi-warning">
                    <div class="card-body">
                        <span class="cash-dashboard-kpi-label">Indice de inadimplencia</span>
                        <strong><?= number_format($indiceInadimplencia, 1, ',', '.') ?>%</strong>
                        <p class="mb-0"><?= $titulosVencidosCount ?> vencido(s), somando <?= formatMoney($titulosVencidosTotal) ?>.</p>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="card glass-card h-100 cash-dashboard-kpi <?= ($fluxo['saldo_projetado'] ?? 0) >= 0 ? 'cash-dashboard-kpi-success' : 'cash-dashboard-kpi-danger' ?>">
                    <div class="card-body">
                        <span class="cash-dashboard-kpi-label">Projecao de fechamento</span>
                        <strong><?= formatMoney($fluxo['saldo_projetado'] ?? 0) ?></strong>
                        <p class="mb-0">Saldo projetado final do periodo selecionado.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade cash-day-modal" id="cashDayDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header">
                <h5 class="modal-title" id="cashDayDetailTitle">Detalhes operacionais do dia</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body" id="cashDayDetailBody">
                <div class="cash-day-loading">
                    <div class="spinner-border text-primary" role="status" aria-hidden="true"></div>
                    <p class="mb-0">Carregando detalhes do dia...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<style>
.cash-tabs-shell {
    overflow: hidden;
}

.cash-tabs-scroller {
    overflow-x: auto;
    scrollbar-width: thin;
    padding: 0 1rem;
}

.cash-tabs-scroller::-webkit-scrollbar {
    height: 7px;
}

.cash-tab-nav {
    flex-wrap: nowrap;
    gap: 0.35rem;
    min-width: max-content;
    padding-top: 0.85rem;
    border-bottom: 0;
}

.cash-tab-nav .nav-link {
    border: 1px solid transparent;
    border-bottom: 0;
    border-radius: 1rem 1rem 0 0;
    color: #475569;
    font-weight: 600;
    white-space: nowrap;
    padding: 0.8rem 1.05rem;
}

.cash-tab-nav .nav-link:hover,
.cash-tab-nav .nav-link:focus {
    color: #0f172a;
    border-color: rgba(148, 163, 184, 0.22);
    background: rgba(255, 255, 255, 0.72);
}

.cash-tab-nav .nav-link.active {
    color: #0f172a;
    background: #fff;
    border-color: rgba(148, 163, 184, 0.24);
    box-shadow: 0 -8px 24px rgba(15, 23, 42, 0.06);
}

.cash-tab-content > .tab-pane + .tab-pane {
    margin-top: 0;
}

.cash-line {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    padding: 0.65rem 0;
    border-bottom: 1px solid rgba(148, 163, 184, 0.16);
}

.cash-line:last-child {
    border-bottom: 0;
}

.cash-category-filter-shell {
    display: flex;
    align-items: end;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem;
    margin-bottom: 1rem;
    border: 1px solid rgba(148, 163, 184, 0.22);
    border-radius: 1rem;
    background: rgba(248, 250, 252, 0.82);
}

.cash-category-filter-copy {
    min-width: 0;
    flex: 1 1 auto;
}

.cash-category-filter-controls {
    min-width: min(100%, 440px);
    flex: 0 1 440px;
}

.cash-category-filter-controls .form-label {
    margin-bottom: 0.45rem;
    font-size: 0.82rem;
    font-weight: 700;
    color: #475569;
}

.cash-category-filter-select {
    min-height: 48px;
}

.cash-table-filter-banner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 0.9rem 1rem;
    margin-bottom: 1rem;
    border: 1px solid rgba(148, 163, 184, 0.22);
    border-radius: 1rem;
    background: rgba(248, 250, 252, 0.82);
}

.cash-proof-group {
    display: flex;
    flex-direction: column;
    gap: 0.55rem;
    margin-top: -0.2rem;
    margin-bottom: 1rem;
    padding: 0.85rem 0 0.15rem;
}

.cash-proof-heading,
.cash-proof-more,
.cash-proof-empty,
.cash-day-proof {
    color: #64748b;
    font-size: 0.78rem;
}

.cash-proof-heading a {
    margin-left: 0.35rem;
    font-weight: 600;
    text-decoration: none;
}

.cash-proof-item {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.85rem;
    padding: 0.75rem 0.85rem;
    border: 1px solid rgba(148, 163, 184, 0.22);
    border-radius: 0.95rem;
    background: rgba(248, 250, 252, 0.78);
    text-decoration: none;
}

.cash-proof-item:hover {
    border-color: rgba(99, 102, 241, 0.35);
    background: rgba(238, 242, 255, 0.72);
}

.cash-proof-copy {
    min-width: 0;
}

.cash-proof-copy .fw-semibold {
    word-break: break-word;
}

.cash-report-table td,
.cash-report-table th,
.cash-daily-grid td,
.cash-daily-grid th {
    vertical-align: middle;
}

.cash-daily-grid td[data-label="Dia"] {
    min-width: 310px;
}

.cash-daily-grid td[data-label="Acoes"] {
    min-width: 150px;
}

.cash-day-action-btn {
    min-width: 118px;
}

.cash-chart-shell {
    position: relative;
    height: clamp(280px, 34vh, 360px);
    min-height: 280px;
}

.cash-chart-shell-sm {
    height: clamp(248px, 30vh, 320px);
    min-height: 248px;
}

.cash-chart-shell canvas {
    display: block;
    max-width: 100%;
}

.cash-dashboard-kpi {
    border: 1px solid rgba(148, 163, 184, 0.18);
}

.cash-dashboard-kpi .card-body {
    display: flex;
    flex-direction: column;
    gap: 0.6rem;
}

.cash-dashboard-kpi-label {
    color: #64748b;
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 0.03em;
    text-transform: uppercase;
}

.cash-dashboard-kpi strong {
    font-size: clamp(1.35rem, 2vw, 1.9rem);
    line-height: 1.1;
    color: #0f172a;
}

.cash-dashboard-kpi p {
    color: #64748b;
    font-size: 0.86rem;
    line-height: 1.5;
}

.cash-dashboard-kpi-primary {
    border-color: rgba(37, 99, 235, 0.25);
}

.cash-dashboard-kpi-neutral {
    border-color: rgba(100, 116, 139, 0.25);
}

.cash-dashboard-kpi-warning {
    border-color: rgba(245, 158, 11, 0.28);
}

.cash-dashboard-kpi-success {
    border-color: rgba(22, 163, 74, 0.24);
}

.cash-dashboard-kpi-danger {
    border-color: rgba(239, 68, 68, 0.24);
}

.cash-day-modal .modal-content {
    border-radius: 1.15rem;
}

.cash-day-loading {
    min-height: 180px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    gap: 0.9rem;
    color: #64748b;
}

.cash-day-detail-summary {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.9rem;
    margin-bottom: 1rem;
}

.cash-day-detail-summary-card {
    border: 1px solid rgba(148, 163, 184, 0.22);
    border-radius: 1rem;
    background: rgba(248, 250, 252, 0.78);
    padding: 0.95rem 1rem;
}

.cash-day-detail-summary-card span {
    display: block;
    color: #64748b;
    font-size: 0.78rem;
    margin-bottom: 0.35rem;
}

.cash-day-detail-summary-card strong {
    font-size: 1rem;
    color: #0f172a;
}

.cash-day-detail-section + .cash-day-detail-section {
    margin-top: 1rem;
}

.cash-day-detail-section-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    margin-bottom: 0.85rem;
}

.cash-day-detail-list {
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
}

.cash-day-detail-item {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    padding: 0.95rem 1rem;
    border: 1px solid rgba(148, 163, 184, 0.22);
    border-radius: 1rem;
    background: rgba(255, 255, 255, 0.88);
}

.cash-day-detail-copy {
    min-width: 0;
    flex: 1 1 auto;
}

.cash-day-detail-title {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.55rem;
    margin-bottom: 0.45rem;
}

.cash-day-detail-title strong {
    color: #0f172a;
    word-break: break-word;
}

.cash-day-detail-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem 0.8rem;
    color: #64748b;
    font-size: 0.8rem;
}

.cash-day-detail-note {
    margin-top: 0.5rem;
    color: #475569;
    font-size: 0.82rem;
    line-height: 1.5;
    word-break: break-word;
}

.cash-day-detail-value {
    flex: 0 0 auto;
    font-weight: 700;
    white-space: nowrap;
    font-size: 1rem;
}

.cash-day-detail-empty {
    border: 1px dashed rgba(148, 163, 184, 0.35);
    border-radius: 1rem;
    padding: 1rem;
    color: #64748b;
    background: rgba(248, 250, 252, 0.6);
}

@media (max-width: 767.98px) {
    .cash-tabs-scroller {
        padding: 0 0.75rem;
    }
}

@media (max-width: 575.98px) {
    .cash-line {
        flex-direction: column;
        align-items: flex-start;
    }

    .cash-category-filter-shell,
    .cash-proof-item,
    .cash-table-filter-banner {
        flex-direction: column;
    }

    .stack-table thead {
        display: none;
    }

    .stack-table,
    .stack-table tbody,
    .stack-table tr,
    .stack-table td {
        display: block;
        width: 100%;
    }

    .stack-table tr {
        margin-bottom: 1rem;
        padding: 1rem;
        border: 1px solid rgba(148, 163, 184, 0.25);
        border-radius: 1rem;
        background: rgba(255, 255, 255, 0.82);
    }

    .stack-table td {
        border: 0;
        padding: 0.35rem 0;
        text-align: left !important;
    }

    .stack-table td::before {
        content: attr(data-label);
        display: block;
        margin-bottom: 0.25rem;
        color: #64748b;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.02em;
        text-transform: uppercase;
    }

    .cash-day-detail-summary {
        grid-template-columns: 1fr;
    }

    .cash-day-detail-section-head,
    .cash-day-detail-item {
        flex-direction: column;
        align-items: flex-start;
    }

    .cash-day-detail-value {
        white-space: normal;
    }
}

@media (max-width: 430px) {
    .cash-tabs-scroller {
        padding: 0 0.55rem;
    }

    .cash-tab-nav .nav-link {
        padding: 0.72rem 0.9rem;
        font-size: 0.92rem;
    }

    .cash-day-action-btn {
        width: 100%;
    }

    .cash-chart-shell {
        height: 268px;
        min-height: 268px;
    }

    .cash-chart-shell-sm {
        height: 240px;
        min-height: 240px;
    }
}

@media (max-width: 390px) {
    .cash-chart-shell {
        height: 248px;
        min-height: 248px;
    }

    .cash-chart-shell-sm {
        height: 228px;
        min-height: 228px;
    }
}

@media (max-width: 360px) {
    .cash-day-detail-item {
        padding: 0.85rem;
    }

    .cash-chart-shell {
        height: 230px;
        min-height: 230px;
    }

    .cash-chart-shell-sm {
        height: 212px;
        min-height: 212px;
    }
}

@media (max-width: 320px) {
    .cash-day-detail-title,
    .cash-day-detail-meta,
    .cash-day-detail-note {
        font-size: 0.76rem;
    }

    .cash-tab-nav .nav-link {
        padding-inline: 0.8rem;
    }

    .cash-chart-shell {
        height: 214px;
        min-height: 214px;
    }

    .cash-chart-shell-sm {
        height: 196px;
        min-height: 196px;
    }
}
</style>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
    const modalElement = document.getElementById('cashDayDetailModal');
    const modalTitle = document.getElementById('cashDayDetailTitle');
    const modalBody = document.getElementById('cashDayDetailBody');
    const detalhesPorDia = <?= $detalhesDiariosJson ?>;
    const dashboardData = <?= $dashboardPayloadJson ?>;
    const detailModal = modalElement && modalTitle && modalBody && typeof bootstrap !== 'undefined'
        ? new bootstrap.Modal(modalElement)
        : null;

    const dashboardState = {
        initialized: false,
        charts: [],
        resizeQueued: false
    };

    const moneyFormatter = new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    });

    const chartPalette = ['#16a34a', '#2563eb', '#f59e0b', '#ef4444', '#0ea5e9', '#8b5cf6', '#14b8a6', '#f97316'];
    const tabTargets = {
        movimentos: 'aba-movimentos-tab',
        titulos: 'aba-titulos-tab',
        grade: 'aba-grade-tab',
        resumo: 'aba-resumo-tab',
        dashboard: 'aba-dashboard-tab'
    };

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatMoney(value) {
        return moneyFormatter.format(Number(value || 0));
    }

    function formatDate(value) {
        const raw = String(value || '').trim();
        if (!raw) {
            return '-';
        }

        const parts = raw.split('-');
        if (parts.length === 3) {
            return `${parts[2]}/${parts[1]}/${parts[0]}`;
        }

        return raw;
    }

    function hexToRgba(hex, alpha) {
        const normalized = String(hex || '').replace('#', '').trim();
        if (normalized.length !== 6) {
            return `rgba(148, 163, 184, ${alpha})`;
        }

        const r = parseInt(normalized.slice(0, 2), 16);
        const g = parseInt(normalized.slice(2, 4), 16);
        const b = parseInt(normalized.slice(4, 6), 16);

        return `rgba(${r}, ${g}, ${b}, ${alpha})`;
    }

    function showTabByButtonId(buttonId) {
        const button = document.getElementById(buttonId);
        if (!button) {
            return;
        }

        if (window.bootstrap && bootstrap.Tab) {
            bootstrap.Tab.getOrCreateInstance(button).show();
            return;
        }

        if (window.jQuery && typeof window.jQuery(button).tab === 'function') {
            window.jQuery(button).tab('show');
        }
    }

    function getTableElement(kind) {
        return document.getElementById(kind === 'movimentos' ? 'cashFlowMovementsTable' : 'cashFlowForecastTable');
    }

    function getDataTableApi(tableElement) {
        if (!tableElement || !window.jQuery || !window.jQuery.fn || !window.jQuery.fn.dataTable) {
            return null;
        }

        return window.jQuery.fn.dataTable.isDataTable(tableElement)
            ? window.jQuery(tableElement).DataTable()
            : null;
    }

    function ensureDataTableFilter(api) {
        if (!api || !window.jQuery || !window.jQuery.fn || !window.jQuery.fn.dataTable) {
            return;
        }

        const settings = api.settings()[0];
        if (settings._cashFlowQuickFilterBound) {
            return;
        }

        settings._cashFlowQuickFilterBound = true;
        settings._cashFlowQuickFilterKey = '';

        window.jQuery.fn.dataTable.ext.search.push(function (currentSettings, data, dataIndex) {
            if (currentSettings !== settings) {
                return true;
            }

            const activeKey = String(settings._cashFlowQuickFilterKey || '').trim();
            if (!activeKey) {
                return true;
            }

            const rowNode = api.row(dataIndex).node();
            return rowNode
                ? String(rowNode.getAttribute('data-filter-key') || '') === activeKey
                : true;
        });
    }

    function updateQuickFilterUi(kind, filterKey, filterLabel, visibleCount, totalCount) {
        const banner = document.querySelector(`[data-filter-banner="${kind}"]`);
        const labelNode = banner ? banner.querySelector(`[data-filter-label="${kind}"]`) : null;
        const countNode = banner ? banner.querySelector(`[data-filter-count="${kind}"]`) : null;
        const emptyState = document.querySelector(`[data-filter-empty="${kind}"]`);
        const select = document.querySelector(`[data-filter-select="${kind}"]`);

        if (banner) {
            const hasFilter = String(filterLabel || '').trim() !== '';
            banner.classList.toggle('d-none', !hasFilter);

            if (labelNode) {
                labelNode.textContent = filterLabel || '-';
            }

            if (countNode) {
                countNode.textContent = `${visibleCount} de ${totalCount}`;
            }
        }

        if (emptyState) {
            emptyState.classList.toggle('d-none', !(filterLabel && visibleCount === 0));
        }

        if (select) {
            if (!filterKey) {
                select.value = '';
            } else if (select.value !== filterKey) {
                select.value = filterKey;
            }
        }
    }

    function applyQuickFilter(kind, filterKey, filterLabel) {
        const tableElement = getTableElement(kind);
        if (!tableElement) {
            return;
        }

        const rows = Array.from(tableElement.querySelectorAll('tbody tr[data-filter-key]'));
        const activeKey = String(filterKey || '').trim();
        const api = getDataTableApi(tableElement);
        let visibleCount = 0;
        const totalCount = rows.length;

        if (api) {
            ensureDataTableFilter(api);
            const settings = api.settings()[0];
            settings._cashFlowQuickFilterKey = activeKey;
            api.draw();
            visibleCount = api.rows({ filter: 'applied' }).count();
        } else {
            rows.forEach(function (row) {
                const matches = !activeKey || String(row.getAttribute('data-filter-key') || '') === activeKey;
                row.classList.toggle('d-none', !matches);
                if (matches) {
                    visibleCount++;
                }
            });
        }

        updateQuickFilterUi(kind, activeKey, filterLabel, visibleCount, totalCount);
    }

    function clearQuickFilter(kind) {
        applyQuickFilter(kind, '', '');
    }

    function scrollToForecastTable() {
        const target = document.getElementById('titulos-previstos');
        if (!target) {
            return;
        }

        window.setTimeout(function () {
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 80);
    }

    function renderSummaryCard(label, value, extraClass) {
        return `
            <div class="cash-day-detail-summary-card">
                <span>${escapeHtml(label)}</span>
                <strong class="${extraClass || ''}">${escapeHtml(value)}</strong>
            </div>
        `;
    }

    function renderMovimentoItem(item) {
        const isEntrada = String(item?.tipo || '') === 'receber';
        const osInfo = item?.numero_os ? `<span>OS ${escapeHtml(item.numero_os)}</span>` : '';
        const formaInfoValue = humanizePaymentMethod(item?.forma_pagamento || '');
        const formaInfo = formaInfoValue && formaInfoValue !== '-' ? `<span>${escapeHtml(formaInfoValue)}</span>` : '';
        const taxaOperadora = Number(item?.cartao_valor_taxa || 0);
        const operadoraNome = String(item?.cartao_operadora_nome || '').trim();
        const taxaInfo = taxaOperadora > 0
            ? `<span>Taxa ${escapeHtml(operadoraNome || 'operadora')} ${escapeHtml(formatMoney(taxaOperadora))}</span>`
            : '';
        const observacoes = item?.observacoes ? `<div class="cash-day-detail-note">${escapeHtml(item.observacoes)}</div>` : '';

        return `
            <div class="cash-day-detail-item">
                <div class="cash-day-detail-copy">
                    <div class="cash-day-detail-title">
                        <span class="badge ${isEntrada ? 'text-bg-success' : 'text-bg-danger'}">${escapeHtml(item?.tipo_label || '-')}</span>
                        <strong>${escapeHtml(item?.descricao || `Movimento #${item?.movimento_id || '-'}`)}</strong>
                    </div>
                    <div class="cash-day-detail-meta">
                        <span>${escapeHtml(item?.categoria || '-')}</span>
                        ${osInfo}
                        ${formaInfo}
                        ${taxaInfo}
                    </div>
                    <div class="cash-day-detail-meta mt-1">
                        <span>${escapeHtml(item?.grupo_dre || '-')}</span>
                        <span>${escapeHtml(item?.subgrupo_dre || '-')}</span>
                        <span>Titulo #${escapeHtml(item?.titulo_id || 0)}</span>
                    </div>
                    ${observacoes}
                </div>
                <div class="cash-day-detail-value ${isEntrada ? 'text-success' : 'text-danger'}">
                    ${escapeHtml(formatMoney(item?.valor || 0))}
                </div>
            </div>
        `;
    }

    function humanizePaymentMethod(value) {
        const map = {
            dinheiro: 'Dinheiro',
            pix: 'Pix',
            cartao_credito: 'Cartao de credito',
            cartao_debito: 'Cartao de debito',
            transferencia: 'Transferencia',
            boleto: 'Boleto',
            multiplo: 'Multiplos recebimentos',
        };

        const raw = String(value || '').trim();
        if (!raw) {
            return '-';
        }

        if (raw.includes(',')) {
            const labels = [];
            raw.split(',').forEach(function (item) {
                const normalized = String(item || '').trim().toLowerCase();
                if (!normalized) {
                    return;
                }

                const label = map[normalized] || normalized.replace(/_/g, ' ').replace(/\b\w/g, function (char) {
                    return char.toUpperCase();
                });

                if (!labels.includes(label)) {
                    labels.push(label);
                }
            });

            return labels.length ? labels.join(', ') : '-';
        }

        const normalized = raw.toLowerCase();
        return map[normalized] || normalized.replace(/_/g, ' ').replace(/\b\w/g, function (char) {
            return char.toUpperCase();
        });
    }

    function renderPrevistoItem(item) {
        const isEntrada = String(item?.tipo || '') === 'receber';
        const osInfo = item?.numero_os ? `<span>OS ${escapeHtml(item.numero_os)}</span>` : '';

        return `
            <div class="cash-day-detail-item">
                <div class="cash-day-detail-copy">
                    <div class="cash-day-detail-title">
                        <span class="badge ${isEntrada ? 'text-bg-success' : 'text-bg-danger'}">${escapeHtml(item?.tipo_label || '-')}</span>
                        <strong>${escapeHtml(item?.descricao || `Titulo #${item?.titulo_id || '-'}`)}</strong>
                    </div>
                    <div class="cash-day-detail-meta">
                        <span>${escapeHtml(item?.categoria || '-')}</span>
                        ${osInfo}
                        <span>Venc. ${escapeHtml(formatDate(item?.data_vencimento || ''))}</span>
                    </div>
                    <div class="cash-day-detail-meta mt-1">
                        <span class="badge ${item?.status === 'parcial' ? 'bg-warning text-dark' : 'bg-secondary'}">${escapeHtml(item?.status_label || 'Pendente')}</span>
                        <span>${escapeHtml(item?.grupo_dre || '-')}</span>
                        <span>${escapeHtml(item?.subgrupo_dre || '-')}</span>
                    </div>
                    <div class="cash-day-detail-note">
                        Quitado ${escapeHtml(formatMoney(item?.valor_movimentado || 0))} | Titulo ${escapeHtml(formatMoney(item?.valor_titulo || 0))}
                    </div>
                </div>
                <div class="cash-day-detail-value ${isEntrada ? 'text-success' : 'text-danger'}">
                    ${escapeHtml(formatMoney(item?.valor || 0))}
                </div>
            </div>
        `;
    }

    function renderSection(title, counterLabel, items, renderer, emptyMessage) {
        return `
            <div class="cash-day-detail-section">
                <div class="cash-day-detail-section-head">
                    <h6 class="mb-0">${escapeHtml(title)}</h6>
                    <span class="badge text-bg-light">${escapeHtml(`${items.length} ${counterLabel}`)}</span>
                </div>
                ${items.length
                    ? `<div class="cash-day-detail-list">${items.map(renderer).join('')}</div>`
                    : `<div class="cash-day-detail-empty">${escapeHtml(emptyMessage)}</div>`}
            </div>
        `;
    }

    function renderDayDetail(detail) {
        if (!detail) {
            return `
                <div class="alert alert-light border mb-0">
                    Nenhum detalhe operacional disponivel para este dia.
                </div>
            `;
        }

        const movimentos = Array.isArray(detail.movimentos_realizados) ? detail.movimentos_realizados : [];
        const previstos = Array.isArray(detail.titulos_previstos) ? detail.titulos_previstos : [];
        const totalMovimentos = Number(detail.movimentos_realizados_count || movimentos.length || 0);
        const totalPrevistos = Number(detail.titulos_previstos_count || previstos.length || 0);
        const totalOperacoes = Number(detail.total_operacoes_count || (totalMovimentos + totalPrevistos));

        return `
            <div class="cash-day-detail-summary">
                ${renderSummaryCard('Movimentos realizados', `${totalMovimentos} registro(s)`)}
                ${renderSummaryCard('Titulos previstos', `${totalPrevistos} registro(s)`)}
                ${renderSummaryCard('Operacoes do dia', `${totalOperacoes} detalhe(s)`)}
            </div>
            ${renderSection(
                'Movimentos realizados',
                'movimento(s)',
                movimentos,
                renderMovimentoItem,
                'Nenhuma baixa real foi registrada neste dia.'
            )}
            ${renderSection(
                'Titulos previstos',
                'titulo(s)',
                previstos,
                renderPrevistoItem,
                'Nenhum titulo previsto compoe este dia.'
            )}
        `;
    }

    function buildDoughnutDataset(items) {
        const datasetItems = Array.isArray(items) ? items : [];
        const hasData = datasetItems.some(function (item) {
            return Number(item?.value || 0) > 0;
        });

        if (!hasData) {
            return {
                labels: ['Sem dados no periodo'],
                values: [1],
                colors: ['rgba(148, 163, 184, 0.28)'],
                hasData: false
            };
        }

        return {
            labels: datasetItems.map(function (item) {
                const baseLabel = String(item?.label || '-');
                const grupo = String(item?.grupo || '-');
                return grupo && grupo !== '-' ? `${baseLabel} (${grupo})` : baseLabel;
            }),
            values: datasetItems.map(function (item) {
                return Number(item?.value || 0);
            }),
            colors: datasetItems.map(function (item, index) {
                const color = chartPalette[index % chartPalette.length];
                return hexToRgba(color, 0.84);
            }),
            hasData: true
        };
    }

    function createDoughnutChart(canvasId, items) {
        const canvas = document.getElementById(canvasId);
        if (!canvas || typeof Chart === 'undefined') {
            return null;
        }

        const dataset = buildDoughnutDataset(items);

        return new Chart(canvas, {
            type: 'doughnut',
            data: {
                labels: dataset.labels,
                datasets: [{
                    data: dataset.values,
                    backgroundColor: dataset.colors,
                    borderColor: dataset.colors.map(function (color) {
                        return color.replace(/0\.84\)/g, '1)');
                    }),
                    borderWidth: 1.5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '62%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            padding: 16
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                if (!dataset.hasData) {
                                    return 'Sem dados no periodo';
                                }

                                return `${context.label}: ${formatMoney(context.raw)}`;
                            }
                        }
                    }
                }
            }
        });
    }

    function createDailyChart() {
        const canvas = document.getElementById('cashDashboardDailyChart');
        if (!canvas || typeof Chart === 'undefined') {
            return null;
        }

        const labels = Array.isArray(dashboardData?.daily?.labels) ? dashboardData.daily.labels : [];
        const entradas = Array.isArray(dashboardData?.daily?.entradas) ? dashboardData.daily.entradas.map(Number) : [];
        const saidas = Array.isArray(dashboardData?.daily?.saidas) ? dashboardData.daily.saidas.map(Number) : [];

        return new Chart(canvas, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Entradas',
                        data: entradas,
                        backgroundColor: 'rgba(22, 163, 74, 0.72)',
                        borderColor: '#16a34a',
                        borderRadius: 8,
                        borderSkipped: false
                    },
                    {
                        label: 'Saidas',
                        data: saidas,
                        backgroundColor: 'rgba(239, 68, 68, 0.72)',
                        borderColor: '#ef4444',
                        borderRadius: 8,
                        borderSkipped: false
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            padding: 16
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return `${context.dataset.label}: ${formatMoney(context.raw)}`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function (value) {
                                return formatMoney(value);
                            }
                        }
                    }
                }
            }
        });
    }

    function isDashboardVisible() {
        const pane = document.getElementById('aba-dashboard');

        return !!pane && pane.classList.contains('active') && pane.classList.contains('show');
    }

    function queueDashboardResize() {
        if (!dashboardState.initialized || dashboardState.charts.length === 0 || dashboardState.resizeQueued || !isDashboardVisible()) {
            return;
        }

        dashboardState.resizeQueued = true;

        const applyResize = function () {
            dashboardState.resizeQueued = false;

            dashboardState.charts.forEach(function (chart) {
                if (!chart) {
                    return;
                }

                if (typeof chart.resize === 'function') {
                    chart.resize();
                }

                if (typeof chart.update === 'function') {
                    chart.update('none');
                }
            });
        };

        if (typeof window !== 'undefined' && typeof window.requestAnimationFrame === 'function') {
            window.requestAnimationFrame(function () {
                window.requestAnimationFrame(applyResize);
            });
            return;
        }

        window.setTimeout(applyResize, 60);
    }

    function initializeDashboard() {
        if (dashboardState.initialized) {
            queueDashboardResize();
            return;
        }

        if (typeof Chart === 'undefined') {
            console.error('[FluxoCaixa] Chart.js nao esta disponivel para o dashboard.');
            return;
        }

        dashboardState.charts = [
            createDailyChart(),
            createDoughnutChart('cashDashboardEntradasChart', dashboardData?.entradasCategorias || []),
            createDoughnutChart('cashDashboardSaidasChart', dashboardData?.saidasCategorias || [])
        ].filter(Boolean);
        dashboardState.initialized = true;
        queueDashboardResize();
    }

    function adjustVisibleDataTables() {
        if (!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.dataTable) {
            return;
        }

        document.querySelectorAll('.tab-pane.active table').forEach(function (table) {
            if (window.jQuery.fn.dataTable.isDataTable(table)) {
                window.jQuery(table).DataTable().columns.adjust();
            }
        });
    }

    document.addEventListener('click', function (event) {
        const dayTrigger = event.target.closest('[data-cash-day-detail]');
        if (dayTrigger) {
            if (!detailModal) {
                return;
            }

            event.preventDefault();

            const dayKey = String(dayTrigger.getAttribute('data-cash-day') || '').trim();
            const dayLabel = String(dayTrigger.getAttribute('data-cash-day-label') || 'Detalhes operacionais do dia').trim();

            modalTitle.textContent = `Detalhes de ${dayLabel}`;
            modalBody.innerHTML = renderDayDetail(detalhesPorDia[dayKey] || null);
            detailModal.show();
            return;
        }

        const clearTrigger = event.target.closest('[data-clear-filter]');
        if (clearTrigger) {
            event.preventDefault();
            clearQuickFilter(String(clearTrigger.getAttribute('data-clear-filter') || '').trim());
            return;
        }

        const summaryLink = event.target.closest('a[href="#titulos-previstos"]');
        if (summaryLink) {
            event.preventDefault();
            showTabByButtonId(tabTargets.titulos);
            clearQuickFilter('titulos');
            scrollToForecastTable();
        }
    });

    document.addEventListener('change', function (event) {
        const select = event.target.closest('[data-filter-select]');
        if (!select) {
            return;
        }

        const kind = String(select.getAttribute('data-filter-select') || '').trim();
        const selectedOption = select.options[select.selectedIndex];
        const filterKey = String(select.value || '').trim();
        const filterLabel = filterKey
            ? String(selectedOption?.getAttribute('data-filter-label') || selectedOption?.textContent || '').trim()
            : '';

        applyQuickFilter(kind, filterKey, filterLabel);
    });

    document.querySelectorAll('[data-bs-toggle="tab"]').forEach(function (tabTrigger) {
        tabTrigger.addEventListener('shown.bs.tab', function (event) {
            adjustVisibleDataTables();

            if (event.target && event.target.id === 'aba-dashboard-tab') {
                initializeDashboard();
            }
        });
    });

    window.addEventListener('resize', function () {
        queueDashboardResize();
    });

    const hashMap = {
        '#aba-grade': 'grade',
        '#aba-movimentos': 'movimentos',
        '#aba-titulos': 'titulos',
        '#titulos-previstos': 'titulos',
        '#aba-resumo': 'resumo',
        '#aba-dashboard': 'dashboard'
    };

    const initialHash = window.location.hash;
    if (hashMap[initialHash] && tabTargets[hashMap[initialHash]]) {
        showTabByButtonId(tabTargets[hashMap[initialHash]]);

        if (initialHash === '#titulos-previstos') {
            scrollToForecastTable();
        }
    }
})();
</script>
<?= $this->endSection() ?>
