<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
$totalAbertas = (int) ($stats['total_abertas'] ?? 0);
$faturamentoMes = (float) ($stats['faturamento_mes'] ?? 0);
$equipamentoEntregue = (int) ($stats['equipamento_entregue'] ?? 0);
$totalEquipamentos = (int) ($total_equipamentos ?? 0);
$totalClientes = (int) ($total_clientes ?? 0);
$totalOs = (int) ($total_os ?? 0);
$anoDashboard = (int) ($ano_dashboard ?? date('Y'));
$anosDashboard = array_values(array_unique(array_map(
    static fn($ano): int => (int) $ano,
    (array) ($anos_dashboard ?? [])
)));
if ($anosDashboard === []) {
    $anosDashboard[] = $anoDashboard;
}
if (!in_array($anoDashboard, $anosDashboard, true)) {
    $anosDashboard[] = $anoDashboard;
}
rsort($anosDashboard, SORT_NUMERIC);
$statusEntregueCodigo = (string) ($status_entregue_codigo ?? 'entregue_reparado');
$resumoFinanceiroInicial = [
    'receitas' => (float) ($resumo_financeiro['receitas'] ?? 0),
    'despesas' => (float) ($resumo_financeiro['despesas'] ?? 0),
    'lucro' => (float) ($resumo_financeiro['lucro'] ?? 0),
    'pendentes' => (float) ($resumo_financeiro['pendentes'] ?? 0),
];
?>

<div class="dashboard-page ds-dashboard-layout">
    <div class="page-header d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0"><i class="bi bi-speedometer2 me-2"></i>Dashboard</h2>
        <button type="button" class="btn btn-sm btn-outline-info rounded-pill ds-dashboard-help-btn" onclick="window.openDocPage('dashboard')" title="Ajuda sobre o Dashboard">
            <i class="bi bi-question-circle me-1"></i>Ajuda
        </button>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-12 col-sm-6 col-xxl-3">
            <div class="stat-card stat-card-primary h-100">
                <div class="stat-card-body">
                    <div class="stat-info">
                        <span class="stat-label">OS abertas</span>
                        <h2 class="stat-value"><?= number_format($totalAbertas, 0, ',', '.') ?></h2>
                    </div>
                    <div class="stat-icon">
                        <i class="bi bi-clipboard-check"></i>
                    </div>
                </div>
                <div class="stat-card-footer">
                    <a href="<?= base_url('os') ?>"><i class="bi bi-arrow-right me-1"></i>Ver detalhes</a>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xxl-3">
            <div class="stat-card stat-card-success h-100">
                <div class="stat-card-body">
                    <div class="stat-info">
                        <span class="stat-label">Faturamento mes</span>
                        <h2 class="stat-value">R$ <?= number_format($faturamentoMes, 2, ',', '.') ?></h2>
                    </div>
                    <div class="stat-icon">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                </div>
                <div class="stat-card-footer">
                    <a href="<?= base_url('financeiro') ?>"><i class="bi bi-arrow-right me-1"></i>Ver financeiro</a>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xxl-3">
            <div class="stat-card stat-card-warning h-100">
                <div class="stat-card-body">
                    <div class="stat-info">
                        <span class="stat-label">Equipamento entregue</span>
                        <h2 class="stat-value"><?= number_format($equipamentoEntregue, 0, ',', '.') ?></h2>
                    </div>
                    <div class="stat-icon">
                        <i class="bi bi-check2-circle"></i>
                    </div>
                </div>
                <div class="stat-card-footer">
                    <a href="<?= base_url('os?status=' . urlencode($statusEntregueCodigo)) ?>"><i class="bi bi-arrow-right me-1"></i>Ver OS entregues</a>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xxl-3">
            <div class="stat-card stat-card-info stat-card-multimetric h-100">
                <div class="stat-card-body">
                    <div class="stat-info w-100">
                        <span class="stat-label">Resumo geral</span>
                        <div class="stat-metric-grid">
                            <div class="stat-metric-item">
                                <span class="stat-metric-title">Equipamentos</span>
                                <strong class="stat-metric-value"><?= number_format($totalEquipamentos, 0, ',', '.') ?></strong>
                            </div>
                            <div class="stat-metric-item">
                                <span class="stat-metric-title">Clientes</span>
                                <strong class="stat-metric-value"><?= number_format($totalClientes, 0, ',', '.') ?></strong>
                            </div>
                            <div class="stat-metric-item">
                                <span class="stat-metric-title">OS total</span>
                                <strong class="stat-metric-value"><?= number_format($totalOs, 0, ',', '.') ?></strong>
                            </div>
                        </div>
                    </div>
                    <div class="stat-icon">
                        <i class="bi bi-diagram-3"></i>
                    </div>
                </div>
                <div class="stat-card-footer">
                                <a href="<?= base_url('os') ?>"><i class="bi bi-arrow-right me-1"></i>Ver operação</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card glass-card ds-dashboard-main-chart-card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h5 class="card-title mb-0"><i class="bi bi-graph-up-arrow me-2"></i>OS abertas por mes</h5>
                            <small class="text-muted">Evolução de janeiro a dezembro de <span id="dashboardAnoRef"><?= $anoDashboard ?></span></small>
                    </div>
                    <div class="ds-dashboard-year-filter">
                        <label class="small text-muted mb-0" for="dashboardAnoSelect">Ano</label>
                        <select id="dashboardAnoSelect" class="form-select form-select-sm">
                            <?php foreach ($anosDashboard as $anoOption): ?>
                                <option value="<?= (int) $anoOption ?>" <?= (int) $anoOption === $anoDashboard ? 'selected' : '' ?>>
                                    <?= (int) $anoOption ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    <div class="ds-chart-wrap ds-chart-wrap-main">
                        <canvas id="chartOsAbertasAno"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-12 col-xl-4">
            <div class="card glass-card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="bi bi-pie-chart me-2"></i>OS por status</h5>
                </div>
                <div class="card-body">
                    <div class="ds-chart-wrap ds-chart-wrap-status">
                        <canvas id="chartStatus"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-8">
            <div class="card glass-card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="bi bi-bar-chart-line me-2"></i>Resumo financeiro</h5>
                </div>
                <div class="card-body">
                    <div class="ds-chart-wrap ds-chart-wrap-finance">
                        <canvas id="chartResumoFinanceiro"></canvas>
                    </div>
                    <div class="row g-2 mt-2 ds-finance-kpis" id="dashboardFinanceKpis">
                        <div class="col-6 col-md-3">
                            <div class="ds-finance-kpi">
                                <span>Receitas</span>
                                <strong id="financeReceitas">R$ 0,00</strong>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="ds-finance-kpi">
                                <span>Despesas</span>
                                <strong id="financeDespesas">R$ 0,00</strong>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="ds-finance-kpi">
                                <span>Lucro</span>
                                <strong id="financeLucro">R$ 0,00</strong>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="ds-finance-kpi">
                                <span>Pendentes</span>
                                <strong id="financePendentes">R$ 0,00</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card glass-card h-100 ds-dashboard-table-card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <h5 class="card-title mb-0"><i class="bi bi-clock-history me-2"></i>Últimas Ordens de Serviço</h5>
                    <?php if (can('os', 'criar')): ?>
                        <button
                            type="button"
                            class="btn btn-glow btn-sm"
                            data-os-modal-url="<?= base_url('os/nova?embed=1') ?>"
                                            data-os-modal-title="Nova Ordem de Serviço"
                            data-os-open-full-url="<?= base_url('os/nova') ?>"
                        >
                            <i class="bi bi-plus-lg me-1"></i>Nova OS
                        </button>
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 ds-mobile-stack-table">
                            <thead>
                                <tr>
                                    <th>No OS</th>
                                    <th>Cliente</th>
                                    <th>Equipamento</th>
                                    <th>Status</th>
                                    <th>Data</th>
                                    <th class="text-end">Ação</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($os_recentes)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">
                                            <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                                            <p class="mt-2 mb-0">Nenhuma OS encontrada.</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($os_recentes as $os): ?>
                                        <tr>
                                            <td data-label="No OS"><strong><?= esc($os['numero_os']) ?></strong></td>
                                            <td data-label="Cliente"><?= esc($os['cliente_nome']) ?></td>
                                            <td data-label="Equipamento"><?= esc(trim(($os['equip_marca'] ?? '') . ' ' . ($os['equip_modelo'] ?? ''))) ?></td>
                                            <td data-label="Status"><?= getStatusBadge($os['status']) ?></td>
                                            <td data-label="Data"><?= date('d/m/Y', strtotime($os['created_at'])) ?></td>
                                        <td data-label="Ação" class="text-end">
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-outline-secondary"
                                                    data-os-modal-url="<?= base_url('os/visualizar/' . (int) $os['id'] . '?embed=1') ?>"
                                                    data-os-modal-title="OS <?= esc($os['numero_os']) ?>"
                                                    data-os-open-full-url="<?= base_url('os/visualizar/' . (int) $os['id']) ?>"
                                                >
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($estoque_baixo)): ?>
        <div class="row g-4">
            <div class="col-12">
                <div class="card glass-card border-warning ds-table-responsive-card">
                    <div class="card-header bg-warning bg-opacity-10">
                        <h5 class="card-title mb-0 text-warning">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>Alerta de estoque baixo
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Codigo</th>
                                        <th>Peca</th>
                                        <th>Qtd. atual</th>
                                        <th>Minimo</th>
                                    <th class="text-end">Ação</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($estoque_baixo as $peca): ?>
                                        <tr>
                                            <td><?= esc($peca['codigo']) ?></td>
                                            <td><?= esc($peca['nome']) ?></td>
                                            <td><span class="badge bg-danger"><?= (int) $peca['quantidade_atual'] ?></span></td>
                                            <td><?= (int) $peca['estoque_minimo'] ?></td>
                                            <td class="text-end">
                                                <?php if (can('estoque', 'editar')): ?>
                                                    <a href="<?= base_url('estoque/editar/' . (int) $peca['id']) ?>" class="btn btn-sm btn-outline-warning">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
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
        </div>
    <?php endif; ?>

    <div class="modal fade dashboard-os-modal" id="dashboardOsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl modal-fullscreen-md-down">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="dashboardOsModalTitle">Ordem de Serviço</h5>
                    <div class="d-flex align-items-center gap-2 ms-auto">
                        <a href="#" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary d-none" id="dashboardOsModalOpenFull">
                            <i class="bi bi-box-arrow-up-right me-1"></i>Abrir pagina
                        </a>
                        <button type="button" class="btn-close ms-0" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                </div>
                <div class="modal-body p-0 position-relative">
                    <div class="dashboard-os-modal-loading" id="dashboardOsModalLoading">
                        <div class="spinner-border text-primary" role="status" aria-hidden="true"></div>
                        <span>Carregando...</span>
                    </div>
                    <iframe id="dashboardOsModalFrame" title="Conteudo da OS" class="dashboard-os-modal-frame" src="about:blank"></iframe>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const currencyFormatter = new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
        minimumFractionDigits: 2,
    });

    const baseFinanceData = <?= json_encode($resumoFinanceiroInicial, JSON_UNESCAPED_UNICODE) ?>;
    const statsBaseUrl = '<?= base_url('admin/stats') ?>';
    const noDataColor = 'rgba(148, 163, 184, 0.35)';
    const dashboardYearSelect = document.getElementById('dashboardAnoSelect');
    const dashboardYearLabel = document.getElementById('dashboardAnoRef');
    let chartStatus = null;
    let chartAbertasAno = null;
    let chartFinanceiro = null;
    let lastPayload = null;
    let resizeTimeoutId = null;
    let statsRequestToken = 0;

    const macroLabels = {
        recepcao: 'Recepcao',
        diagnostico: 'Diagnostico',
        orcamento: 'Orçamento',
        execucao: 'Execucao',
        interrupcao: 'Interrupcao',
        qualidade: 'Qualidade',
        concluido: 'Concluido',
        finalizado_sem_reparo: 'Finalizado sem reparo',
        encerrado: 'Encerrado',
        cancelado: 'Cancelado',
        outros: 'Outros',
    };

    function formatMoney(value) {
        return currencyFormatter.format(Number(value || 0));
    }

    function isMobileViewport() {
        return window.matchMedia('(max-width: 575.98px)').matches;
    }

    function isNarrowPhoneViewport() {
        return window.matchMedia('(max-width: 390px)').matches;
    }

    function isUltraNarrowViewport() {
        return window.matchMedia('(max-width: 360px)').matches;
    }

    function scheduleChartRerender() {
        if (!lastPayload) {
            return;
        }

        window.clearTimeout(resizeTimeoutId);
        resizeTimeoutId = window.setTimeout(function () {
            renderCharts(lastPayload);
        }, 140);
    }

    function formatAxisMoney(value) {
        const amount = Number(value || 0);
        if (isMobileViewport()) {
            if (Math.abs(amount) >= 1000000) {
                return 'R$ ' + (amount / 1000000).toFixed(1).replace('.', ',') + 'M';
            }
            if (Math.abs(amount) >= 1000) {
                return 'R$ ' + (amount / 1000).toFixed(1).replace('.', ',') + 'k';
            }
            return 'R$ ' + amount.toFixed(0).replace('.', ',');
        }

        return formatMoney(amount);
    }

    function updateFinanceSummary(financeData) {
        const data = financeData || {};
        const receitas = Number(data.receitas || 0);
        const despesas = Number(data.despesas || 0);
        const lucro = Number(data.lucro || 0);
        const pendentes = Number(data.pendentes || 0);

        document.getElementById('financeReceitas').textContent = formatMoney(receitas);
        document.getElementById('financeDespesas').textContent = formatMoney(despesas);
        document.getElementById('financeLucro').textContent = formatMoney(lucro);
        document.getElementById('financePendentes').textContent = formatMoney(pendentes);
    }

    function buildStatusChart(payload) {
        const ctx = document.getElementById('chartStatus');
        if (!ctx) {
            return;
        }

        const macroData = Array.isArray(payload.macro_count) && payload.macro_count.length > 0
            ? payload.macro_count
            : (Array.isArray(payload.status_count) ? payload.status_count : []);

        const labels = macroData.map(function (item) {
            const key = (item.macrofase || item.status || 'outros').toString();
            return macroLabels[key] || key;
        });

        const totals = macroData.map(function (item) {
            return Number(item.total || 0);
        });

        const hasData = totals.some(function (value) { return value > 0; });
        const dataset = hasData ? totals : [1];
        const isMobile = isMobileViewport();
        const isNarrowPhone = isNarrowPhoneViewport();
        const isUltraNarrow = isUltraNarrowViewport();

        if (chartStatus) {
            chartStatus.destroy();
        }

        chartStatus = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: hasData ? labels : ['Sem dados'],
                datasets: [{
                    data: dataset,
                    backgroundColor: hasData
                        ? ['#6366f1', '#3b82f6', '#8b5cf6', '#22c55e', '#f59e0b', '#ef4444', '#0ea5e9', '#64748b']
                        : [noDataColor],
                    borderWidth: 0,
                    hoverOffset: 8,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '66%',
                plugins: {
                    legend: {
                        display: !isUltraNarrow,
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            padding: isNarrowPhone ? 6 : (isMobile ? 8 : 12),
                            color: '#64748b',
                            font: { size: isUltraNarrow ? 9 : (isNarrowPhone ? 9 : (isMobile ? 10 : 11)) },
                            usePointStyle: true,
                        },
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                if (!hasData) {
                                    return 'Sem dados para o periodo';
                                }
                                return context.label + ': ' + context.formattedValue;
                            },
                        },
                    },
                },
            },
        });
    }

    function buildAnnualOsChart(payload) {
        const ctx = document.getElementById('chartOsAbertasAno');
        if (!ctx) {
            return;
        }

        const series = Array.isArray(payload.os_abertas_ano) ? payload.os_abertas_ano : [];
        const labels = series.map(function (item) {
            return item.label || '';
        });
        const totals = series.map(function (item) {
            return Number(item.total || 0);
        });
        const isMobile = isMobileViewport();
        const isNarrowPhone = isNarrowPhoneViewport();
        const isUltraNarrow = isUltraNarrowViewport();

        if (chartAbertasAno) {
            chartAbertasAno.destroy();
        }

        chartAbertasAno = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels.length ? labels : ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'],
                datasets: [{
                    label: 'OS abertas',
                    data: totals.length ? totals : [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.16)',
                    fill: true,
                    tension: 0.35,
                    pointRadius: isMobile ? 2 : 3,
                    pointHoverRadius: isMobile ? 4 : 5,
                    pointBackgroundColor: '#6366f1',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointHitRadius: 12,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index',
                },
                plugins: {
                    legend: {
                        display: !isNarrowPhone,
                        position: 'top',
                        labels: {
                            color: '#475569',
                            usePointStyle: true,
                            font: { size: isMobile ? 10 : 12 },
                        },
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return context.dataset.label + ': ' + context.formattedValue;
                            },
                        },
                    },
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: {
                            color: '#64748b',
                            maxRotation: 0,
                            autoSkip: true,
                            maxTicksLimit: isUltraNarrow ? 3 : (isNarrowPhone ? 4 : (isMobile ? 6 : 12)),
                            font: { size: isUltraNarrow ? 9 : (isMobile ? 10 : 12) },
                        },
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(148, 163, 184, 0.2)' },
                        ticks: {
                            precision: 0,
                            color: '#64748b',
                            maxTicksLimit: isUltraNarrow ? 4 : undefined,
                            font: { size: isUltraNarrow ? 9 : (isMobile ? 10 : 12) },
                        },
                    },
                },
            },
        });
    }

    function buildFinancialChart(financeData) {
        const ctx = document.getElementById('chartResumoFinanceiro');
        if (!ctx) {
            return;
        }

        const receitas = Number(financeData.receitas || 0);
        const despesas = Number(financeData.despesas || 0);
        const lucro = Number(financeData.lucro || 0);
        const pendentes = Number(financeData.pendentes || 0);
        const data = [receitas, despesas, lucro, pendentes];
        const hasData = data.some(function (value) { return value > 0; });
        const isMobile = isMobileViewport();
        const isNarrowPhone = isNarrowPhoneViewport();
        const isUltraNarrow = isUltraNarrowViewport();
        const labels = isUltraNarrow
            ? ['Rec.', 'Desp.', 'Lucro', 'Pend.']
            : ['Receitas', 'Despesas', 'Lucro', 'Pendentes'];

        if (chartFinanceiro) {
            chartFinanceiro.destroy();
        }

        chartFinanceiro = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Valor (R$)',
                    data: hasData ? data : [0, 0, 0, 0],
                    borderRadius: 10,
                    borderSkipped: false,
                    backgroundColor: hasData
                        ? ['rgba(34, 197, 94, 0.8)', 'rgba(239, 68, 68, 0.8)', 'rgba(99, 102, 241, 0.8)', 'rgba(245, 158, 11, 0.8)']
                        : [noDataColor, noDataColor, noDataColor, noDataColor],
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return ' ' + formatMoney(context.raw);
                            },
                        },
                    },
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: { color: 'rgba(148, 163, 184, 0.2)' },
                        ticks: {
                            color: '#64748b',
                            callback: function (value) {
                                return formatAxisMoney(value);
                            },
                            maxTicksLimit: isUltraNarrow ? 2 : (isNarrowPhone ? 3 : (isMobile ? 4 : 6)),
                            font: { size: isNarrowPhone ? 9 : (isMobile ? 10 : 12) },
                        },
                        grace: '6%',
                    },
                    y: {
                        grid: { display: false },
                        ticks: {
                            color: '#64748b',
                            font: { size: isNarrowPhone ? 9 : (isMobile ? 10 : 12) },
                        },
                    },
                },
            },
        });
    }

    function renderCharts(payload) {
        const safePayload = payload || {};
        buildAnnualOsChart(safePayload);
        buildStatusChart(safePayload);
        buildFinancialChart(safePayload.resumo_financeiro || baseFinanceData);
    }

    function buildStatsUrl(year) {
        const params = new URLSearchParams();
        const parsedYear = Number(year || 0);
        if (Number.isFinite(parsedYear) && parsedYear > 0) {
            params.set('ano', String(parsedYear));
        }
        const query = params.toString();
        return query ? `${statsBaseUrl}?${query}` : statsBaseUrl;
    }

    function normalizeYears(years) {
        const source = Array.isArray(years) ? years : [];
        const list = Array.from(new Set(source
            .map(function (value) { return Number(value || 0); })
            .filter(function (value) { return Number.isFinite(value) && value > 0; })));

        list.sort(function (a, b) { return b - a; });
        return list;
    }

    function syncYearOptions(years, preferredYear) {
        if (!dashboardYearSelect) {
            return;
        }

        const normalizedYears = normalizeYears(years);
        if (normalizedYears.length === 0) {
            return;
        }

        const fallbackYear = Number(preferredYear || dashboardYearSelect.value || normalizedYears[0]);
        dashboardYearSelect.innerHTML = '';

        normalizedYears.forEach(function (year) {
            const option = document.createElement('option');
            option.value = String(year);
            option.textContent = String(year);
            dashboardYearSelect.appendChild(option);
        });

        const selectedYear = normalizedYears.includes(fallbackYear)
            ? fallbackYear
            : normalizedYears[0];
        dashboardYearSelect.value = String(selectedYear);
    }

    function updateYearLabel(yearValue) {
        if (!dashboardYearLabel) {
            return;
        }
        dashboardYearLabel.textContent = String(yearValue || '');
    }

    function loadDashboardStats(year) {
        const requestToken = ++statsRequestToken;
        if (dashboardYearSelect) {
            dashboardYearSelect.disabled = true;
        }

        return fetch(buildStatsUrl(year))
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Falha ao carregar dados do dashboard');
                }
                return response.json();
            })
            .then(function (payload) {
                if (requestToken !== statsRequestToken) {
                    return;
                }

                const anoRef = Number(payload.ano_referencia || year || <?= $anoDashboard ?>);
                syncYearOptions(payload.anos_disponiveis, anoRef);
                updateYearLabel(anoRef);

                const financeData = payload.resumo_financeiro || baseFinanceData;
                updateFinanceSummary(financeData);
                lastPayload = payload;
                renderCharts(payload);
            })
            .catch(function (error) {
                if (requestToken !== statsRequestToken) {
                    return;
                }
            console.error('[Dashboard] erro ao carregar métricas:', error);
                lastPayload = {
                    os_abertas_ano: [],
                    macro_count: [],
                    status_count: [],
                    resumo_financeiro: baseFinanceData,
                };
                renderCharts(lastPayload);
            })
            .finally(function () {
                if (requestToken === statsRequestToken && dashboardYearSelect) {
                    dashboardYearSelect.disabled = false;
                }
            });
    }

    updateFinanceSummary(baseFinanceData);
    buildFinancialChart(baseFinanceData);

    const initialYear = dashboardYearSelect
        ? Number(dashboardYearSelect.value || <?= $anoDashboard ?>)
        : <?= $anoDashboard ?>;
    loadDashboardStats(initialYear);

    if (dashboardYearSelect) {
        dashboardYearSelect.addEventListener('change', function () {
            const selectedYear = Number(dashboardYearSelect.value || 0);
            loadDashboardStats(selectedYear);
        });
    }

    window.addEventListener('resize', function () {
        scheduleChartRerender();
    });

    window.addEventListener('orientationchange', function () {
        scheduleChartRerender();
    });

    if (window.visualViewport && typeof window.visualViewport.addEventListener === 'function') {
        window.visualViewport.addEventListener('resize', function () {
            scheduleChartRerender();
        });
    }

    const modalElement = document.getElementById('dashboardOsModal');
    if (!modalElement || typeof bootstrap === 'undefined') {
        return;
    }

    const modal = new bootstrap.Modal(modalElement);
    const modalFrame = document.getElementById('dashboardOsModalFrame');
    const modalLoading = document.getElementById('dashboardOsModalLoading');
    const modalTitle = document.getElementById('dashboardOsModalTitle');
    const openFullButton = document.getElementById('dashboardOsModalOpenFull');
    let loadTimeoutId = null;

    function setModalLoading(isLoading) {
        modalLoading.classList.toggle('d-none', !isLoading);
    }

    function clearLoadTimeout() {
        if (loadTimeoutId) {
            window.clearTimeout(loadTimeoutId);
            loadTimeoutId = null;
        }
    }

    function openDashboardModal(url, title, fullUrl) {
        if (!url) {
            return;
        }

        clearLoadTimeout();
        setModalLoading(true);
        modalTitle.textContent = title || 'Ordem de Serviço';
        modalFrame.src = 'about:blank';

        if (fullUrl) {
            openFullButton.href = fullUrl;
            openFullButton.classList.remove('d-none');
        } else {
            openFullButton.href = '#';
            openFullButton.classList.add('d-none');
        }

        modal.show();
        modalFrame.src = url;

        loadTimeoutId = window.setTimeout(function () {
            setModalLoading(false);
        }, 12000);
    }

    document.addEventListener('click', function (event) {
        const trigger = event.target.closest('[data-os-modal-url]');
        if (!trigger) {
            return;
        }

        event.preventDefault();
        openDashboardModal(
            trigger.getAttribute('data-os-modal-url'),
            trigger.getAttribute('data-os-modal-title'),
            trigger.getAttribute('data-os-open-full-url')
        );
    });

    modalFrame.addEventListener('load', function () {
        clearLoadTimeout();
        setModalLoading(false);
    });

    modalElement.addEventListener('hidden.bs.modal', function () {
        clearLoadTimeout();
        setModalLoading(false);
        modalFrame.src = 'about:blank';
        openFullButton.href = '#';
        openFullButton.classList.add('d-none');
    });
});
</script>
<?= $this->endSection() ?>
