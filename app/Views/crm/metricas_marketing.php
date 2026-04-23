<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$cards = $cards ?? [];
$kpiDeltas = $kpiDeltas ?? [];
$insights = $insights ?? [];
$origens = $origens ?? [];
$tagStats = $tagStats ?? [];
$canalStats = $canalStats ?? [];
$rankingAtendimento = $rankingAtendimento ?? [];
$seriesLabels = $seriesLabels ?? [];
$seriesLeads = $seriesLeads ?? [];
$seriesLeadsQualificados = $seriesLeadsQualificados ?? [];
$seriesLeadsConvertidos = $seriesLeadsConvertidos ?? [];
$seriesConversas = $seriesConversas ?? [];
$serieResumoRows = $serieResumoRows ?? [];
$periodo = $periodo ?? '30d';
$canal = $canal ?? '';
$responsavelIdSelecionado = (int) ($responsavel_id ?? 0);
$statusConversaSelecionado = $status ?? '';
$tagIdSelecionada = (int) ($tag_id ?? 0);
$canalOptions = $canalOptions ?? [];
$responsavelOptions = $responsavelOptions ?? [];
$statusOptions = $statusOptions ?? [];
$tagOptions = $tagOptions ?? [];
$periodoAtivoDias = (int) ($periodoAtivoDias ?? 30);
$periodoRiscoDias = (int) ($periodoRiscoDias ?? 90);
$supportsEngajamento = !empty($supportsEngajamento);
$canEditarPeriodo = function_exists('can') ? can('clientes', 'editar') : true;

$captados = (int) ($cards['leads_captados'] ?? 0);
$qualificados = (int) ($cards['leads_qualificados'] ?? 0);
$convertidos = (int) ($cards['leads_convertidos'] ?? 0);
$baseFunil = max(1, $captados);
$funilRows = [
    ['label' => 'Captados', 'valor' => $captados, 'pct' => 100.0, 'bar' => 'bg-primary'],
    ['label' => 'Qualificados', 'valor' => $qualificados, 'pct' => round(($qualificados / $baseFunil) * 100, 1), 'bar' => 'bg-warning'],
    ['label' => 'Convertidos', 'valor' => $convertidos, 'pct' => round(($convertidos / $baseFunil) * 100, 1), 'bar' => 'bg-success'],
];

$periodoOptions = [
    'hoje' => 'Hoje',
    '7d' => 'Ultimos 7 dias',
    '30d' => 'Ultimos 30 dias',
    '90d' => 'Ultimos 90 dias',
    'mes_atual' => 'Mes atual',
    'mes_anterior' => 'Mes anterior',
    'custom' => 'Personalizado',
];

$lineChartLabels = $seriesLabels;
$lineChartSeries = [
    'captados' => array_map('intval', $seriesLeads),
    'qualificados' => array_map('intval', $seriesLeadsQualificados),
    'convertidos' => array_map('intval', $seriesLeadsConvertidos),
    'conversas' => array_map('intval', $seriesConversas),
];

$origensChartLabels = [];
$origensChartValues = [];
foreach ($origens as $origemRow) {
    $origensChartLabels[] = ucfirst(str_replace('_', ' ', (string) ($origemRow['origem'] ?? 'nao_informada')));
    $origensChartValues[] = (int) ($origemRow['total'] ?? 0);
}

$funnelChartLabels = ['Captados', 'Qualificados', 'Convertidos'];
$funnelChartValues = [$captados, $qualificados, $convertidos];

$deltaBadge = static function (?float $delta, bool $asPercentage = true): array {
    if ($delta === null) {
        return ['class' => 'text-muted', 'icon' => 'bi-dash', 'text' => 'sem base comparativa'];
    }
    if ($delta > 0) {
        return ['class' => 'text-success', 'icon' => 'bi-arrow-up-right', 'text' => '+' . number_format($delta, 1, ',', '.') . ($asPercentage ? '%' : ' p.p.')];
    }
    if ($delta < 0) {
        return ['class' => 'text-danger', 'icon' => 'bi-arrow-down-right', 'text' => number_format($delta, 1, ',', '.') . ($asPercentage ? '%' : ' p.p.')];
    }

    return ['class' => 'text-muted', 'icon' => 'bi-arrow-right', 'text' => '0,0' . ($asPercentage ? '%' : ' p.p.')];
};
?>

<style>
    .crm-mkt-grid .card {
        border-radius: 14px;
        border: 1px solid rgba(25, 35, 58, .08);
        box-shadow: 0 6px 24px rgba(17, 24, 39, .05);
    }
    .crm-kpi-title { font-size: .78rem; letter-spacing: .02em; text-transform: uppercase; color: #6b7280; }
    .crm-kpi-value { font-size: 1.7rem; font-weight: 700; line-height: 1.15; color: #111827; }
    .crm-kpi-sub { font-size: .78rem; margin-top: .25rem; }
    .crm-filter-chip { border-radius: 999px; border: 1px solid rgba(99, 102, 241, .26); background: #eef2ff; color: #3730a3; font-size: .8rem; padding: .25rem .65rem; }
    .crm-filter-chip.active { background: #4f46e5; color: #fff; border-color: #4f46e5; }
    .crm-insight { border-radius: 10px; padding: .7rem .8rem; border: 1px solid rgba(31, 41, 55, .08); }
    .crm-insight.success { background: #f0fdf4; border-color: #bbf7d0; }
    .crm-insight.warning { background: #fffbeb; border-color: #fcd34d; }
    .crm-insight.info { background: #eff6ff; border-color: #bfdbfe; }
    .crm-funnel-step .progress { height: 10px; border-radius: 999px; }
    .crm-funnel-step .progress-bar { border-radius: 999px; }
    .crm-chart { min-height: 300px; }
    .crm-chart-main { min-height: 360px; }
    .crm-table-premium thead th {
        border-bottom: 1px solid rgba(148, 163, 184, .25);
        color: #475569;
        font-size: .75rem;
        text-transform: uppercase;
        letter-spacing: .03em;
        font-weight: 700;
    }
    .crm-table-premium tbody td {
        border-bottom: 1px solid rgba(148, 163, 184, .15);
        padding-top: .7rem;
        padding-bottom: .7rem;
        vertical-align: middle;
    }
    .crm-table-premium tbody tr:last-child td {
        border-bottom: 0;
    }
    .crm-table-premium tbody tr:hover td {
        background: rgba(37, 99, 235, .03);
    }
    .crm-rank-chip {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: .72rem;
        font-weight: 700;
        color: #1d4ed8;
        background: rgba(37, 99, 235, .12);
        border: 1px solid rgba(37, 99, 235, .25);
    }
    .crm-num-chip {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 34px;
        border-radius: 999px;
        font-size: .78rem;
        font-weight: 700;
        padding: .15rem .5rem;
        color: #1f2937;
        background: rgba(148, 163, 184, .16);
        border: 1px solid rgba(148, 163, 184, .3);
    }
    .crm-num-chip.success {
        color: #166534;
        background: rgba(22, 163, 74, .12);
        border-color: rgba(22, 163, 74, .3);
    }
    .crm-num-chip.warning {
        color: #92400e;
        background: rgba(245, 158, 11, .12);
        border-color: rgba(245, 158, 11, .28);
    }
    .crm-rate-pill {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        font-size: .76rem;
        font-weight: 700;
        padding: .16rem .55rem;
        border: 1px solid transparent;
    }
    .crm-rate-pill.success {
        color: #166534;
        background: rgba(22, 163, 74, .13);
        border-color: rgba(22, 163, 74, .3);
    }
    .crm-rate-pill.warning {
        color: #92400e;
        background: rgba(245, 158, 11, .13);
        border-color: rgba(245, 158, 11, .3);
    }
    .crm-rate-pill.danger {
        color: #991b1b;
        background: rgba(239, 68, 68, .12);
        border-color: rgba(239, 68, 68, .3);
    }
    .crm-progress-mini {
        width: 92px;
        height: 6px;
        border-radius: 999px;
        background: rgba(148, 163, 184, .22);
        overflow: hidden;
    }
    .crm-progress-mini > span {
        display: block;
        height: 100%;
        width: 0;
        border-radius: 999px;
    }
    .crm-progress-mini > span.success { background: #16a34a; }
    .crm-progress-mini > span.warning { background: #f59e0b; }
    .crm-progress-mini > span.danger { background: #ef4444; }
    .crm-funnel-inline {
        display: flex;
        gap: .3rem;
        flex-wrap: wrap;
        justify-content: flex-end;
    }
    .crm-funnel-pill {
        border-radius: 8px;
        padding: .12rem .4rem;
        font-size: .72rem;
        font-weight: 700;
        border: 1px solid rgba(148, 163, 184, .3);
        background: rgba(248, 250, 252, .9);
        color: #334155;
    }
    .crm-funnel-pill.zero {
        opacity: .55;
    }
    .crm-table-box,
    .crm-summary-table-box {
        width: 100%;
        overflow: visible;
    }
    .crm-table-box table,
    .crm-summary-table-box table {
        width: 100%;
        table-layout: fixed;
        margin-bottom: 0;
    }
    .crm-summary-table-box {
        border: 1px solid rgba(148, 163, 184, .2);
        border-radius: 10px;
    }
    .crm-summary-table-box .crm-table-premium tbody td {
        padding-top: .52rem;
        padding-bottom: .52rem;
    }
    .crm-table-stack {
        width: 100%;
    }
    @media (max-width: 1399.98px) {
        .crm-summary-optional-col {
            display: none !important;
        }
    }
    @media (max-width: 991.98px) {
        .crm-chart { min-height: 260px; }
        .crm-chart-main { min-height: 300px; }
        .crm-progress-mini {
            width: 72px;
        }
        .crm-table-stack thead {
            display: none;
        }
        .crm-table-stack,
        .crm-table-stack tbody,
        .crm-table-stack tr,
        .crm-table-stack td {
            display: block;
            width: 100%;
        }
        .crm-table-stack tbody {
            display: grid;
            gap: .6rem;
        }
        .crm-table-stack tr {
            border: 1px solid rgba(148, 163, 184, .24);
            border-radius: 10px;
            padding: .5rem .6rem;
            background: #fff;
        }
        .crm-table-stack td {
            border: 0 !important;
            padding: .24rem 0 !important;
            text-align: right !important;
        }
        .crm-table-stack td::before {
            content: attr(data-label);
            float: left;
            color: #64748b;
            font-size: .68rem;
            text-transform: uppercase;
            letter-spacing: .02em;
            font-weight: 700;
            margin-right: .6rem;
        }
        .crm-table-stack td[data-label="Responsavel"],
        .crm-table-stack td[data-label="Dia"] {
            text-align: left !important;
            padding-bottom: .38rem !important;
            margin-bottom: .38rem;
            border-bottom: 1px dashed rgba(148, 163, 184, .28) !important;
        }
        .crm-table-stack td[data-label="Responsavel"]::before,
        .crm-table-stack td[data-label="Dia"]::before {
            content: '';
            display: none;
        }
        .crm-table-stack td[data-label="Taxa resolucao"],
        .crm-table-stack td[data-label="Conv. dia"],
        .crm-table-stack td[data-label="Intensidade"] {
            padding-top: .35rem !important;
        }
        .crm-table-premium tbody tr:hover td {
            background: transparent;
        }
    }
    @media (max-width: 767.98px) {
        .crm-table-premium thead th {
            font-size: .68rem;
        }
        .crm-table-premium tbody td {
            font-size: .84rem;
            padding-top: .5rem;
            padding-bottom: .5rem;
        }
        .crm-rank-chip {
            width: 21px;
            height: 21px;
            font-size: .66rem;
        }
        .crm-rate-pill,
        .crm-num-chip {
            font-size: .7rem;
        }
        .crm-progress-mini {
            width: 62px;
        }
    }
</style>

<div class="page-header d-flex justify-content-between align-items-center mb-3">
    <div class="d-flex align-items-center gap-3">
        <h2 class="mb-0"><i class="bi bi-bar-chart-line me-2"></i>CRM - Métricas Marketing</h2>
        <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('crm-metricas-marketing')">
            <i class="bi bi-question-circle me-1"></i>Ajuda
        </button>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="get" action="<?= base_url('crm/metricas-marketing') ?>" class="row g-2 align-items-end">
            <div class="col-12">
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($periodoOptions as $periodoKey => $periodoLabel): ?>
                        <button type="button" class="crm-filter-chip <?= $periodo === $periodoKey ? 'active' : '' ?>" data-periodo-chip="<?= esc($periodoKey) ?>">
                            <?= esc($periodoLabel) ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="col-12 col-md-6 col-lg-2">
                <label class="form-label mb-1">Periodo</label>
                <select name="periodo" id="filtroPeriodo" class="form-select">
                    <?php foreach ($periodoOptions as $periodoKey => $periodoLabel): ?>
                        <option value="<?= esc($periodoKey) ?>" <?= $periodo === $periodoKey ? 'selected' : '' ?>><?= esc($periodoLabel) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-3 col-lg-2">
                <label class="form-label mb-1">Inicio</label>
                <input type="date" name="inicio" id="filtroInicio" class="form-control" value="<?= esc($inicio ?? '') ?>">
            </div>
            <div class="col-12 col-md-3 col-lg-2">
                <label class="form-label mb-1">Fim</label>
                <input type="date" name="fim" id="filtroFim" class="form-control" value="<?= esc($fim ?? '') ?>">
            </div>
            <div class="col-12 col-md-6 col-lg-2">
                <label class="form-label mb-1">Canal</label>
                <select name="canal" class="form-select">
                    <option value="">Todos os canais</option>
                    <?php foreach ($canalOptions as $canalValue => $canalLabel): ?>
                        <option value="<?= esc($canalValue) ?>" <?= $canal === $canalValue ? 'selected' : '' ?>><?= esc($canalLabel) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-6 col-lg-2">
                <label class="form-label mb-1">Responsavel</label>
                <select name="responsavel_id" class="form-select">
                    <option value="">Todos os responsaveis</option>
                    <?php foreach ($responsavelOptions as $responsavelOptionId => $responsavelNome): ?>
                        <option value="<?= (int) $responsavelOptionId ?>" <?= $responsavelIdSelecionado === (int) $responsavelOptionId ? 'selected' : '' ?>>
                            <?= esc($responsavelNome) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-6 col-lg-2">
                <label class="form-label mb-1">Status</label>
                <select name="status" class="form-select">
                    <option value="">Todos os status</option>
                    <?php foreach ($statusOptions as $statusValue => $statusLabel): ?>
                        <option value="<?= esc($statusValue) ?>" <?= $statusConversaSelecionado === $statusValue ? 'selected' : '' ?>>
                            <?= esc($statusLabel) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <label class="form-label mb-1">Tag</label>
                <select name="tag_id" class="form-select">
                    <option value="">Todas as tags</option>
                    <?php foreach ($tagOptions as $tagOptionId => $tagNome): ?>
                        <option value="<?= (int) $tagOptionId ?>" <?= $tagIdSelecionada === (int) $tagOptionId ? 'selected' : '' ?>>
                            <?= esc($tagNome) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Aplicar filtros</button>
                <a href="<?= base_url('crm/metricas-marketing') ?>" class="btn btn-outline-secondary">Limpar</a>
            </div>
        </form>
    </div>
</div>

<div class="crm-mkt-grid">
    <div class="row g-3 mb-3">
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card h-100"><div class="card-body">
                <?php $delta = $deltaBadge(isset($kpiDeltas['leads_captados']) ? (float) $kpiDeltas['leads_captados'] : null, true); ?>
                <div class="crm-kpi-title">Leads captados</div>
                <div class="crm-kpi-value"><?= $captados ?></div>
                <div class="crm-kpi-sub <?= esc($delta['class']) ?>"><i class="bi <?= esc($delta['icon']) ?> me-1"></i><?= esc($delta['text']) ?> vs 7 dias anteriores</div>
            </div></div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card h-100"><div class="card-body">
                <?php $delta = $deltaBadge(isset($kpiDeltas['leads_qualificados']) ? (float) $kpiDeltas['leads_qualificados'] : null, true); ?>
                <div class="crm-kpi-title">Leads qualificados</div>
                <div class="crm-kpi-value"><?= $qualificados ?></div>
                <div class="crm-kpi-sub <?= esc($delta['class']) ?>"><i class="bi <?= esc($delta['icon']) ?> me-1"></i><?= esc($delta['text']) ?> vs 7 dias anteriores</div>
            </div></div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card h-100"><div class="card-body">
                <?php $delta = $deltaBadge(isset($kpiDeltas['leads_convertidos']) ? (float) $kpiDeltas['leads_convertidos'] : null, true); ?>
                <div class="crm-kpi-title">Leads convertidos</div>
                <div class="crm-kpi-value"><?= $convertidos ?></div>
                <div class="crm-kpi-sub <?= esc($delta['class']) ?>"><i class="bi <?= esc($delta['icon']) ?> me-1"></i><?= esc($delta['text']) ?> vs 7 dias anteriores</div>
            </div></div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card h-100"><div class="card-body">
                <?php $delta = $deltaBadge(isset($kpiDeltas['taxa_conversao']) ? (float) $kpiDeltas['taxa_conversao'] : null, false); ?>
                <div class="crm-kpi-title">Taxa conversao (qualif. > conv.)</div>
                <div class="crm-kpi-value"><?= number_format((float) ($cards['taxa_conversao'] ?? 0), 1, ',', '.') ?>%</div>
                <div class="crm-kpi-sub <?= esc($delta['class']) ?>"><i class="bi <?= esc($delta['icon']) ?> me-1"></i><?= esc($delta['text']) ?> vs 7 dias anteriores</div>
            </div></div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-md-6 col-xl-3"><div class="card h-100"><div class="card-body">
            <div class="crm-kpi-title">Conversas</div>
            <div class="crm-kpi-value"><?= (int) ($cards['conversas_iniciadas'] ?? 0) ?></div>
            <div class="small text-muted">Ativas: <?= (int) ($cards['conversas_ativas'] ?? 0) ?></div>
        </div></div></div>
        <div class="col-12 col-md-6 col-xl-3"><div class="card h-100"><div class="card-body">
            <div class="crm-kpi-title">Clientes novos na fila</div>
            <div class="crm-kpi-value"><?= (int) ($cards['conversas_clientes_novos'] ?? 0) ?></div>
            <div class="small text-muted">Sem vinculo operacional</div>
        </div></div></div>
        <div class="col-12 col-md-6 col-xl-3"><div class="card h-100"><div class="card-body">
            <div class="crm-kpi-title">Mensagens (in/out)</div>
            <div class="crm-kpi-value"><?= (int) ($cards['mensagens_total'] ?? 0) ?></div>
            <div class="small text-muted">In: <?= (int) ($cards['mensagens_inbound'] ?? 0) ?> | Out: <?= (int) ($cards['mensagens_outbound'] ?? 0) ?></div>
        </div></div></div>
        <div class="col-12 col-md-6 col-xl-3"><div class="card h-100"><div class="card-body">
            <div class="crm-kpi-title">Tempo medio 1a resposta</div>
            <div class="crm-kpi-value"><?= $cards['tempo_primeira_resposta_min'] !== null ? number_format((float) $cards['tempo_primeira_resposta_min'], 1, ',', '.') . 'm' : '-' ?></div>
            <div class="small text-muted">OS origem WhatsApp: <?= (int) ($cards['os_origem_whatsapp'] ?? 0) ?></div>
        </div></div></div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-xl-8">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bi bi-graph-up me-1"></i>Tendencia do funil e conversas</h6>
                    <span class="small text-muted"><?= esc(($inicio ?? '') . ' ate ' . ($fim ?? '')) ?></span>
                </div>
                <div class="card-body">
                    <div id="chartTendenciaMarketing" class="crm-chart crm-chart-main"></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-4">
            <div class="card h-100">
                <div class="card-header"><h6 class="mb-0"><i class="bi bi-lightbulb me-1"></i>Insights automaticos</h6></div>
                <div class="card-body d-flex flex-column gap-2">
                    <?php if (empty($insights)): ?>
                        <div class="text-muted small">Sem insights suficientes para o recorte atual.</div>
                    <?php else: ?>
                        <?php foreach ($insights as $insight): ?>
                            <div class="crm-insight <?= esc($insight['tipo'] ?? 'info') ?>">
                                <div class="fw-semibold"><?= esc($insight['titulo'] ?? 'Insight') ?></div>
                                <div class="small"><?= esc($insight['descricao'] ?? '') ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-xl-4">
            <div class="card h-100">
                <div class="card-header"><h6 class="mb-0"><i class="bi bi-funnel me-1"></i>Funil visual</h6></div>
                <div class="card-body d-flex flex-column gap-3">
                    <?php foreach ($funilRows as $funilRow): ?>
                        <div class="crm-funnel-step">
                            <div class="d-flex justify-content-between small mb-1">
                                <span class="fw-semibold"><?= esc($funilRow['label']) ?></span>
                                <span><?= (int) $funilRow['valor'] ?> (<?= number_format((float) $funilRow['pct'], 1, ',', '.') ?>%)</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar <?= esc($funilRow['bar']) ?>" style="width: <?= max(0, min(100, (float) $funilRow['pct'])) ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <div class="small text-muted">
                        Conversao total captados > convertidos:
                        <strong><?= number_format((float) ($cards['taxa_conversao_captados'] ?? 0), 1, ',', '.') ?>%</strong>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-4">
            <div class="card h-100">
                <div class="card-header"><h6 class="mb-0"><i class="bi bi-pie-chart me-1"></i>Origem dos leads</h6></div>
                <div class="card-body"><div id="chartOrigemLeads" class="crm-chart"></div></div>
            </div>
        </div>
        <div class="col-12 col-xl-4">
            <div class="card h-100">
                <div class="card-header"><h6 class="mb-0"><i class="bi bi-bar-chart-steps me-1"></i>Volume por etapa do funil</h6></div>
                <div class="card-body"><div id="chartFunnelStages" class="crm-chart"></div></div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-xl-6">
            <div class="card h-100">
                <div class="card-header"><h6 class="mb-0"><i class="bi bi-people me-1"></i>Ranking de atendimento (responsavel)</h6></div>
                <div class="card-body">
                    <?php if (empty($rankingAtendimento)): ?>
                        <div class="text-muted small">Sem dados de ranking no periodo selecionado.</div>
                    <?php else: ?>
                        <div class="crm-table-box">
                            <table class="table table-sm align-middle mb-0 crm-table-premium crm-table-stack">
                                <thead>
                                    <tr>
                                        <th>Responsavel</th>
                                        <th class="text-end">Conversas</th>
                                        <th class="text-end">Resolvidas</th>
                                        <th class="text-end">Pendencias</th>
                                        <th class="text-end">Taxa resolucao</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($rankingAtendimento as $rankingIndex => $rankingRow): ?>
                                        <?php
                                        $totalConversas = (int) ($rankingRow['total_conversas'] ?? 0);
                                        $totalResolvidas = (int) ($rankingRow['total_resolvidas'] ?? 0);
                                        $totalPendencias = (int) ($rankingRow['total_pendencias'] ?? 0);
                                        $taxaResolucao = (float) ($rankingRow['taxa_resolucao'] ?? 0);
                                        $taxaClass = $taxaResolucao >= 70 ? 'success' : ($taxaResolucao >= 40 ? 'warning' : 'danger');
                                        ?>
                                        <tr>
                                            <td data-label="Responsavel">
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="crm-rank-chip"><?= (int) $rankingIndex + 1 ?></span>
                                                    <div>
                                                        <div class="fw-semibold"><?= esc($rankingRow['responsavel_nome'] ?? 'Não atribuído') ?></div>
                                                        <div class="small text-muted">Pendencias abertas: <?= $totalPendencias ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-end" data-label="Conversas"><span class="crm-num-chip"><?= $totalConversas ?></span></td>
                                            <td class="text-end" data-label="Resolvidas"><span class="crm-num-chip success"><?= $totalResolvidas ?></span></td>
                                            <td class="text-end" data-label="Pendencias"><span class="crm-num-chip warning"><?= $totalPendencias ?></span></td>
                                            <td class="text-end" data-label="Taxa resolucao">
                                                <div class="d-flex justify-content-end align-items-center gap-2">
                                                    <span class="crm-rate-pill <?= esc($taxaClass) ?>"><?= number_format($taxaResolucao, 1, ',', '.') ?>%</span>
                                                </div>
                                                <div class="crm-progress-mini mt-1 ms-auto">
                                                    <span class="<?= esc($taxaClass) ?>" style="width: <?= max(0, min(100, $taxaResolucao)) ?>%"></span>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bi bi-table me-1"></i>Resumo diario (ultimos 14 pontos)</h6>
                    <span class="small text-muted">Tabela compacta para auditoria</span>
                </div>
                <div class="card-body">
                    <?php
                    $resumoRows = $serieResumoRows;
                    if (count($resumoRows) > 14) {
                        $resumoRows = array_slice($resumoRows, -14);
                    }
                    $maxResumoConversas = 0;
                    foreach ($resumoRows as $resumoBaseRow) {
                        $maxResumoConversas = max($maxResumoConversas, (int) ($resumoBaseRow['conversas'] ?? 0));
                    }
                    ?>
                    <?php if (empty($resumoRows)): ?>
                        <div class="text-muted small">Sem serie diaria no periodo.</div>
                    <?php else: ?>
                        <div class="crm-summary-table-box">
                            <table class="table table-sm align-middle mb-0 crm-table-premium crm-table-stack">
                                <thead>
                                    <tr>
                                        <th>Dia</th>
                                        <th class="text-end">Funil (C/Q/V)</th>
                                        <th class="text-end">Conversas</th>
                                        <th class="text-end">Conv. dia</th>
                                        <th class="text-end crm-summary-optional-col">Intensidade</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($resumoRows as $resumoRow): ?>
                                        <?php
                                        $captadosDia = (int) ($resumoRow['captados'] ?? 0);
                                        $qualificadosDia = (int) ($resumoRow['qualificados'] ?? 0);
                                        $convertidosDia = (int) ($resumoRow['convertidos'] ?? 0);
                                        $conversasDia = (int) ($resumoRow['conversas'] ?? 0);
                                        $taxaConversaoDia = $captadosDia > 0 ? round(($convertidosDia / max(1, $captadosDia)) * 100, 1) : 0.0;
                                        $taxaConversaoDiaClass = $taxaConversaoDia >= 70 ? 'success' : ($taxaConversaoDia >= 40 ? 'warning' : 'danger');
                                        $intensidadeDia = $maxResumoConversas > 0 ? round(($conversasDia / $maxResumoConversas) * 100, 1) : 0.0;
                                        ?>
                                        <tr>
                                            <td data-label="Dia"><span class="fw-semibold"><?= esc($resumoRow['dia'] ?? '-') ?></span></td>
                                            <td class="text-end" data-label="Funil (C/Q/V)">
                                                <div class="crm-funnel-inline">
                                                    <span class="crm-funnel-pill <?= $captadosDia === 0 ? 'zero' : '' ?>">C <?= $captadosDia ?></span>
                                                    <span class="crm-funnel-pill <?= $qualificadosDia === 0 ? 'zero' : '' ?>">Q <?= $qualificadosDia ?></span>
                                                    <span class="crm-funnel-pill <?= $convertidosDia === 0 ? 'zero' : '' ?>">V <?= $convertidosDia ?></span>
                                                </div>
                                            </td>
                                            <td class="text-end" data-label="Conversas"><span class="crm-num-chip"><?= $conversasDia ?></span></td>
                                            <td class="text-end" data-label="Conv. dia"><span class="crm-rate-pill <?= esc($taxaConversaoDiaClass) ?>"><?= number_format($taxaConversaoDia, 1, ',', '.') ?>%</span></td>
                                            <td class="text-end crm-summary-optional-col" data-label="Intensidade">
                                                <div class="crm-progress-mini ms-auto">
                                                    <span class="<?= esc($taxaConversaoDiaClass) ?>" style="width: <?= max(0, min(100, $intensidadeDia)) ?>%"></span>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-xl-6">
            <div class="card h-100">
                <div class="card-header"><h6 class="mb-0"><i class="bi bi-compass me-1"></i>Canais e resolucao</h6></div>
                <div class="card-body">
                    <?php if (empty($canalStats)): ?>
                        <div class="text-muted small">Sem dados de canal no recorte atual.</div>
                    <?php else: ?>
                        <div class="crm-table-box">
                            <table class="table table-sm align-middle mb-0 crm-table-premium">
                                <thead>
                                    <tr>
                                        <th>Canal</th>
                                        <th class="text-end">Conversas</th>
                                        <th class="text-end">Resolvidas</th>
                                        <th class="text-end">Taxa resolucao</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($canalStats as $canalRow): ?>
                                        <tr>
                                            <td><?= esc(ucfirst(str_replace('_', ' ', (string) ($canalRow['canal'] ?? 'nao_informado')))) ?></td>
                                            <td class="text-end"><?= (int) ($canalRow['total'] ?? 0) ?></td>
                                            <td class="text-end"><?= (int) ($canalRow['resolvidas'] ?? 0) ?></td>
                                            <td class="text-end"><?= number_format((float) ($canalRow['taxa_resolucao'] ?? 0), 1, ',', '.') ?>%</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-6">
            <div class="card h-100">
                <div class="card-header"><h6 class="mb-0"><i class="bi bi-tags me-1"></i>Segmentacao por tags</h6></div>
                <div class="card-body">
                    <?php if (empty($tagStats)): ?>
                        <div class="text-muted small">Sem tags CRM cadastradas.</div>
                    <?php else: ?>
                        <div class="crm-table-box">
                            <table class="table table-sm align-middle mb-0 crm-table-premium">
                                <thead><tr><th>Tag</th><th class="text-end">Clientes</th></tr></thead>
                                <tbody>
                                    <?php foreach ($tagStats as $tag): ?>
                                        <tr>
                                            <td>
                                                <span class="rounded-circle border d-inline-block me-2" style="width:10px;height:10px;background:<?= esc($tag['cor'] ?? '#6c757d') ?>;"></span>
                                                <?= esc($tag['nome'] ?? '-') ?>
                                            </td>
                                            <td class="text-end"><?= (int) ($tag['total_clientes'] ?? 0) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <details>
                <summary class="fw-semibold">Configuração de janelas de engajamento</summary>
                <p class="small text-muted mt-2 mb-2">
                    O lifecycle (lead_novo, lead_qualificado, cliente_convertido) continua igual.
                    Este bloco controla apenas o engajamento temporal.
                </p>
                <?php if (!$supportsEngajamento): ?>
                    <div class="alert alert-warning py-2 small mb-2">Estrutura de engajamento ainda não migrada.</div>
                <?php endif; ?>
                <form method="post" action="<?= base_url('crm/metricas-marketing/engajamento') ?>" class="row g-2 align-items-end">
                    <?= csrf_field() ?>
                    <input type="hidden" name="inicio" value="<?= esc($inicio ?? '') ?>">
                    <input type="hidden" name="fim" value="<?= esc($fim ?? '') ?>">
                    <input type="hidden" name="periodo" value="<?= esc($periodo ?? '') ?>">
                    <input type="hidden" name="canal" value="<?= esc($canal ?? '') ?>">
                    <input type="hidden" name="responsavel_id" value="<?= (int) $responsavelIdSelecionado ?>">
                    <input type="hidden" name="status" value="<?= esc($statusConversaSelecionado ?? '') ?>">
                    <input type="hidden" name="tag_id" value="<?= (int) $tagIdSelecionada ?>">

                    <div class="col-12 col-md-3">
                        <label class="form-label mb-1">Ativo ate (dias)</label>
                        <input type="number" min="7" max="365" class="form-control" name="engajamento_ativo_dias" value="<?= esc((string) $periodoAtivoDias) ?>" <?= ($canEditarPeriodo && $supportsEngajamento) ? '' : 'disabled' ?>>
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label mb-1">Em risco ate (dias)</label>
                        <input type="number" min="8" max="720" class="form-control" name="engajamento_risco_dias" value="<?= esc((string) $periodoRiscoDias) ?>" <?= ($canEditarPeriodo && $supportsEngajamento) ? '' : 'disabled' ?>>
                    </div>
                    <div class="col-12 col-md-6 d-flex gap-2">
                        <button type="submit" class="btn btn-primary" <?= ($canEditarPeriodo && $supportsEngajamento) ? '' : 'disabled' ?>>
                            <i class="bi bi-save me-1"></i>Salvar periodos
                        </button>
                        <div class="small text-muted align-self-center">
                            Acima de <?= (int) $periodoRiscoDias ?> dias sem interacao, o contato vira inativo.
                        </div>
                    </div>
                </form>
            </details>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/vendor/apexcharts/apexcharts.min.js') ?>"></script>
<script>
    (function () {
        const periodoSelect = document.getElementById('filtroPeriodo');
        const inicioInput = document.getElementById('filtroInicio');
        const fimInput = document.getElementById('filtroFim');
        const chips = document.querySelectorAll('[data-periodo-chip]');

        function syncDateDisabled() {
            const custom = periodoSelect && periodoSelect.value === 'custom';
            if (inicioInput) inicioInput.disabled = !custom;
            if (fimInput) fimInput.disabled = !custom;
        }

        if (periodoSelect) {
            periodoSelect.addEventListener('change', syncDateDisabled);
        }
        chips.forEach(function (chip) {
            chip.addEventListener('click', function () {
                if (!periodoSelect) return;
                periodoSelect.value = chip.getAttribute('data-periodo-chip') || '30d';
                syncDateDisabled();
                chips.forEach((x) => x.classList.remove('active'));
                chip.classList.add('active');
            });
        });
        syncDateDisabled();

        const labels = <?= json_encode($lineChartLabels, JSON_UNESCAPED_UNICODE) ?>;
        const lineData = <?= json_encode($lineChartSeries, JSON_UNESCAPED_UNICODE) ?>;
        const origemLabels = <?= json_encode($origensChartLabels, JSON_UNESCAPED_UNICODE) ?>;
        const origemValues = <?= json_encode($origensChartValues, JSON_UNESCAPED_UNICODE) ?>;
        const funnelLabels = <?= json_encode($funnelChartLabels, JSON_UNESCAPED_UNICODE) ?>;
        const funnelValues = <?= json_encode($funnelChartValues, JSON_UNESCAPED_UNICODE) ?>;

        if (!window.ApexCharts) {
            return;
        }

        const theme = {
            textMuted: '#6b7280',
            border: 'rgba(148, 163, 184, .25)',
            baseGrid: 'rgba(148, 163, 184, .2)',
        };

        const tendenciaEl = document.getElementById('chartTendenciaMarketing');
        if (tendenciaEl) {
            const tendenciaChart = new ApexCharts(tendenciaEl, {
                chart: {
                    type: 'line',
                    height: 360,
                    toolbar: { show: false },
                    animations: { enabled: true, speed: 320 }
                },
                series: [
                    { name: 'Captados', data: Array.isArray(lineData.captados) ? lineData.captados : [] },
                    { name: 'Qualificados', data: Array.isArray(lineData.qualificados) ? lineData.qualificados : [] },
                    { name: 'Convertidos', data: Array.isArray(lineData.convertidos) ? lineData.convertidos : [] },
                    { name: 'Conversas', data: Array.isArray(lineData.conversas) ? lineData.conversas : [] }
                ],
                colors: ['#2563eb', '#f59e0b', '#16a34a', '#7c3aed'],
                dataLabels: { enabled: false },
                stroke: { curve: 'smooth', width: 3 },
                markers: { size: 3, hover: { size: 5 } },
                legend: { position: 'top', horizontalAlign: 'left', fontSize: '12px' },
                xaxis: {
                    categories: Array.isArray(labels) ? labels : [],
                    labels: { style: { colors: theme.textMuted, fontSize: '11px' } }
                },
                yaxis: {
                    min: 0,
                    forceNiceScale: true,
                    labels: { style: { colors: theme.textMuted, fontSize: '11px' } }
                },
                grid: { borderColor: theme.baseGrid, strokeDashArray: 3 },
                tooltip: {
                    theme: 'light',
                    shared: true,
                    intersect: false
                },
                noData: {
                    text: 'Sem dados no periodo selecionado.',
                    align: 'center',
                    verticalAlign: 'middle',
                    style: { color: theme.textMuted }
                }
            });
            tendenciaChart.render();
        }

        const origemEl = document.getElementById('chartOrigemLeads');
        if (origemEl) {
            const origemSeriesRaw = Array.isArray(origemValues) ? origemValues.map((v) => Number(v) || 0) : [];
            const origemHasData = origemSeriesRaw.some((v) => v > 0);
            const origemChart = new ApexCharts(origemEl, {
                chart: { type: 'donut', height: 300 },
                series: origemHasData ? origemSeriesRaw : [],
                labels: origemHasData ? origemLabels : [],
                colors: ['#2563eb', '#06b6d4', '#22c55e', '#f59e0b', '#7c3aed', '#ef4444', '#64748b'],
                legend: {
                    position: 'bottom',
                    fontSize: '12px',
                    labels: { colors: theme.textMuted }
                },
                dataLabels: { enabled: false },
                stroke: { width: 1, colors: ['#fff'] },
                plotOptions: {
                    pie: {
                        donut: {
                            size: '66%',
                            labels: {
                                show: true,
                                total: {
                                    show: true,
                                    label: 'Total',
                                    formatter: function () {
                                        return origemHasData ? origemSeriesRaw.reduce((acc, val) => acc + val, 0) : '0';
                                    }
                                }
                            }
                        }
                    }
                },
                noData: {
                    text: 'Sem origem registrada no periodo.',
                    align: 'center',
                    verticalAlign: 'middle',
                    style: { color: theme.textMuted }
                }
            });
            origemChart.render();
        }

        const funnelEl = document.getElementById('chartFunnelStages');
        if (funnelEl) {
            const funnelSeriesRaw = Array.isArray(funnelValues) ? funnelValues.map((v) => Number(v) || 0) : [];
            const funnelHasData = funnelSeriesRaw.some((v) => v > 0);
            const funnelChart = new ApexCharts(funnelEl, {
                chart: { type: 'bar', height: 300, toolbar: { show: false } },
                series: funnelHasData ? [{ name: 'Volume', data: funnelSeriesRaw }] : [],
                plotOptions: {
                    bar: {
                        horizontal: true,
                        borderRadius: 8,
                        distributed: true,
                        barHeight: '58%'
                    }
                },
                colors: ['#2563eb', '#f59e0b', '#16a34a'],
                xaxis: {
                    categories: Array.isArray(funnelLabels) ? funnelLabels : [],
                    labels: { style: { colors: theme.textMuted, fontSize: '11px' } }
                },
                yaxis: {
                    labels: { style: { colors: theme.textMuted, fontSize: '12px' } }
                },
                dataLabels: { enabled: false },
                grid: { borderColor: theme.baseGrid, strokeDashArray: 3 },
                legend: { show: false },
                noData: {
                    text: 'Sem dados de funil no periodo.',
                    align: 'center',
                    verticalAlign: 'middle',
                    style: { color: theme.textMuted }
                }
            });
            funnelChart.render();
        }
    })();
</script>
<?= $this->endSection() ?>
