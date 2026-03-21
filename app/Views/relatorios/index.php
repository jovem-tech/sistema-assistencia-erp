<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header d-flex justify-content-between align-itemês-center">
    <h2><i class="bi bi-file-earmark-bar-graph-fill me-2"></i><?= esc($title) ?></h2>
    <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('relatorios')" title="Ajuda sãobre Relatórios">
        <i class="bi bi-question-circle me-1"></i>Ajuda
    </button>
</div>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card glass-card h-100">
            <div class="card-body text-center">
                <i class="bi bi-tools text-primary mb-3" style="font-size: 3rem;"></i>
                <h5 class="card-title mb-3">Ordens de Serviço</h5>
                <p class="text-secondary mb-4">Relatório detalhado de ordens de serviço por período e status.</p>
                <a href="<?= base_url('relatorios/os') ?>" class="btn btn-outline-primary w-100">
                    <i class="bi bi-graph-up me-2"></i>Gerar Relatório
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card glass-card h-100">
            <div class="card-body text-center">
                <i class="bi bi-cash-stack text-success mb-3" style="font-size: 3rem;"></i>
                <h5 class="card-title mb-3">Financeiro</h5>
                <p class="text-secondary mb-4">Acompanhamento de receitas, despesas e lucro por período.</p>
                <a href="<?= base_url('relatorios/financeiro') ?>" class="btn btn-outline-success w-100">
                    <i class="bi bi-calendar-check me-2"></i>Gerar Relatório
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card glass-card h-100">
            <div class="card-body text-center">
                <i class="bi bi-box-seam text-warning mb-3" style="font-size: 3rem;"></i>
                <h5 class="card-title mb-3">Estoque</h5>
                <p class="text-secondary mb-4">Posição atual do estoque e peças com quantidade baixa.</p>
                <a href="<?= base_url('relatorios/estoque') ?>" class="btn btn-outline-warning w-100">
                    <i class="bi bi-clipboard-data me-2"></i>Gerar Relatório
                </a>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
