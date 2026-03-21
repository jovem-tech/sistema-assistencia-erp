?<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="row align-itemês-center mb-4">
    <div class="col-md">
        <div class="d-flex align-itemês-center gap-3">
            <div class="bg-primary bg-opacity-10 p-2 rounded-3">
                <i class="bi bi-graph-up text-primary fs-4"></i>
            </div>
            <div>
                <h2 class="h4 mb-1">Métricas da Central</h2>
                <p class="text-muted small mb-0">
                    Acompanhe o desempenho operacional da central com indicadores, produtividade e automação não período selecionado.
                </p>
            </div>
        </div>
    </div>
    <div class="col-md-auto mt-3 mt-md-0">
        <button type="button" class="btn btn-sm btn-outline-info rounded-pill px-3" onclick="window.openDocPage('atendimento-whatsapp-metricas')">
            <i class="bi bi-question-circle me-2"></i>Ajuda
        </button>
    </div>
</div>

<?= $this->include('central_mensagens/_menu') ?>

<!-- Painel de Filtros -->
<div class="card glass-card border-0 mb-4 shadow-sm">
    <div class="card-body p-3">
        <div class="row g-3 align-itemês-end">
            <div class="col-12 col-md-auto">
                <form class="row g-2 align-itemês-end" method="get" action="<?= base_url('atendimento-whatsapp/metricas') ?>">
                    <div class="col-6 col-sm-auto" style="min-width: 160px;">
                        <label class="form-label small fw-semibold text-muted mb-1">Início</label>
                        <input type="date" class="form-control form-control-sm border-0 bg-light" name="inicio" value="<?= esc((string) ($inicio ?? date('Y-m-d', strtotime('-7 day')))) ?>">
                    </div>
                    <div class="col-6 col-sm-auto" style="min-width: 160px;">
                        <label class="form-label small fw-semibold text-muted mb-1">Fim</label>
                        <input type="date" class="form-control form-control-sm border-0 bg-light" name="fim" value="<?= esc((string) ($fim ?? date('Y-m-d'))) ?>">
                    </div>
                    <div class="col-12 col-sm-auto d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-primary px-3 shadow-sm">
                            <i class="bi bi-filter me-1"></i>Aplicar período
                        </button>
                        <a href="<?= base_url('atendimento-whatsapp/metricas') ?>" class="btn btn-sm btn-outline-secondary px-3">
                            Limpar
                        </a>
                    </div>
                </form>
            </div>
            <div class="col-12 col-md d-flex justify-content-md-end">
                <form method="post" action="<?= base_url('atendimento-whatsapp/metricas/consãolidar-diario') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="data_referencia" value="<?= esc((string) ($fim ?? date('Y-m-d'))) ?>">
                    <button type="submit" class="btn btn-sm btn-outline-primary border-dashed px-3">
                        <i class="bi bi-arrow-repeat me-1"></i>Consãolidar dia final
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- KPIs -->
<div class="row g-3 mb-4">
    <?php foreach (($resumo['kpis'] ?? []) as $kpi): ?>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card glass-card border-0 h-100 shadow-sm border-start border-4 border-<?= esc($kpi['status']) ?>" 
                 title="<?= esc($kpi['tooltip']) ?>" data-bs-toggle="tooltip">
                <div class="card-body p-3">
                    <div class="d-flex align-itemês-center justify-content-between mb-1">
                        <div class="text-uppercase text-muted fw-bold extra-small"><?= esc($kpi['titulo']) ?></div>
                        <i class="bi <?= esc($kpi['icone']) ?> text-<?= esc($kpi['status']) ?> opacity-75"></i>
                    </div>
                    <div class="h3 mb-0 fw-bold"><?= $kpi['valor'] ?></div>
                    <div class="text-muted extra-small mt-1 text-truncate" style="max-width: 100%;">
                        <?= esc($kpi['subtexto']) ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Área de Gráficos (Reservada) -->
<div class="row g-4 mb-4 d-nãone">
    <div class="col-12 col-xl-8">
        <div class="card glass-card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-0 pt-3 px-3">
                <h5 class="card-title h6 mb-0 fw-bold">Evolução Diária</h5>
                <p class="text-muted extra-small mb-0">Total de mensagens por tipo ao longo do tempo.</p>
            </div>
            <div class="card-body d-flex align-itemês-center justify-content-center" style="min-height: 300px;">
                <div class="text-center">
                    <div class="spinner-border spinner-border-sm text-primary mb-2" role="status"></div>
                    <div class="text-muted small">Processando dados gráficos...</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-4">
        <div class="card glass-card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-0 pt-3 px-3">
                <h5 class="card-title h6 mb-0 fw-bold">Distribuição de Intenções</h5>
            </div>
            <div class="card-body d-flex align-itemês-center justify-content-center" style="min-height: 300px;">
                <div class="text-muted small">Aguardando dados...</div>
            </div>
        </div>
    </div>
</div>

<!-- Tabelas de Detalhamento -->
<div class="row g-4">
    <div class="col-12 col-xl-7">
        <div class="card glass-card border-0 shadow-sm">
            <div class="card-header bg-transparent py-3 px-4">
                <div class="d-flex align-itemês-center justify-content-between">
                    <div>
                        <h5 class="card-title h6 mb-1 fw-bold">Volume por Dia</h5>
                        <p class="text-muted extra-small mb-0">Distribuição quantitativa de mensagens registradas.</p>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light bg-opacity-50">
                            <tr class="text-muted extra-small text-uppercase">
                                <th class="ps-4 border-0">Dia</th>
                                <th class="border-0">Recebidas</th>
                                <th class="border-0">Enviadas</th>
                                <th class="border-0 text-end pe-4">Automáticas</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            <?php foreach (($resumo['por_dia'] ?? []) as $d): ?>
                                <tr>
                                    <td class="ps-4 fw-medium"><?= esc(date('d/m/Y', strtotime((string) $d['dia']))) ?></td>
                                    <td><span class="badge bg-light text-dark fw-nãormal px-2"><?= (int) $d['recebidas'] ?></span></td>
                                    <td><span class="badge bg-light text-dark fw-nãormal px-2"><?= (int) $d['enviadas'] ?></span></td>
                                    <td class="text-end pe-4"><span class="badge bg-primary bg-opacity-10 text-primary fw-nãormal px-2"><?= (int) $d['automaticas'] ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($resumo['por_dia'])): ?>
                                <tr><td colspan="4" class="text-center text-muted py-5 px-4">Nenhum dado encontrado para o período.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-12 col-xl-5">
        <!-- Produtividade -->
        <div class="card glass-card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent py-3 px-4 border-0">
                <h5 class="card-title h6 mb-1 fw-bold">Produtividade por Atendente</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light bg-opacity-50">
                            <tr class="text-muted extra-small text-uppercase">
                                <th class="ps-4 border-0">Atendente</th>
                                <th class="border-0 text-end pe-4">Total Enviados</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            <?php foreach (($resumo['por_atendente'] ?? []) as $a): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-itemês-center gap-2">
                                            <div class="avatar-xxs rounded-circle bg-secondary bg-opacity-10 text-secondary text-center" style="width: 24px; height: 24px; line-height: 24px;">
                                                <i class="bi bi-persãon fs-6"></i>
                                            </div>
                                            <?= esc((string) ($a['usuario_nãome'] ?: 'Não identificado')) ?>
                                        </div>
                                    </td>
                                    <td class="text-end pe-4 fw-bold text-primary"><?= (int) ($a['total'] ?? 0) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($resumo['por_atendente'])): ?>
                                <tr><td colspan="2" class="text-center text-muted py-4 px-4">Sem dados de produtividade.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Top Intenções -->
        <div class="card glass-card border-0 shadow-sm">
            <div class="card-header bg-transparent py-3 px-4 border-0">
                <h5 class="card-title h6 mb-1 fw-bold">Top Intenções Detectadas</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light bg-opacity-50">
                            <tr class="text-muted extra-small text-uppercase">
                                <th class="ps-4 border-0">Intenção</th>
                                <th class="border-0 text-end pe-4">Incidências</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            <?php foreach (($resumo['top_intencoes'] ?? []) as $t): ?>
                                <tr>
                                    <td class="ps-4"><code class="text-purple bg-purple bg-opacity-10 px-2 py-1 rounded small"><?= esc((string) $t['intencao_detectada']) ?></code></td>
                                    <td class="text-end pe-4 fw-bold"><?= (int) ($t['total'] ?? 0) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($resumo['top_intencoes'])): ?>
                                <tr><td colspan="2" class="text-center text-muted py-4 px-4">Nenhuma intenção detectada.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .extra-small { font-size: 0.65rem; }
    .border-dashed { border-style: dashed !important; }
    .avatar-xxs { display: inline-block; vertical-align: middle; }
    .text-purple { color: #6f42c1 !important; }
    .bg-purple { background-color: #6f42c1 !important; }
    
    .nav-pills .nav-link { 
        color: var(--bs-secondary); 
        border: 1px sãolid transparent;
        transition: all 0.3s ease;
    }
    .nav-pills .nav-link.active { 
        background: var(--bs-primary); 
        color: white !important; 
        font-weight: 500;
    }
    .nav-pills .nav-link:hover:nãot(.active) {
        background: rgba(0,0,0,0.05);
    }
    
    [data-bs-theme="dark"] .nav-pills .nav-link:hover:nãot(.active) {
        background: rgba(255,255,255,0.05);
    }
</style>

<?= $this->endSection() ?>


