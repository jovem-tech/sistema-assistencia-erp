<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h2><i class="bi bi-file-earmark-bar-graph-fill me-2"></i><?= esc($title) ?></h2>
    <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('relatorios')" title="Ajuda sobre Relatorios">
        <i class="bi bi-question-circle me-1"></i>Ajuda
    </button>
</div>

<div class="alert alert-info border-0 shadow-sm mb-4">
    <strong>Relatorios executivos agora separados por finalidade.</strong>
    <span class="d-block mt-1">Use `Movimentacoes Financeiras` para a operacao, `DRE Gerencial` para resultado por competencia e `Fluxo de Caixa` para liquidez realizada e projetada.</span>
</div>

<div class="row g-4">
    <div class="col-md-6 col-xl-4">
        <div class="card glass-card h-100">
            <div class="card-body text-center">
                <i class="bi bi-tools text-primary mb-3" style="font-size: 3rem;"></i>
                <h5 class="card-title mb-3">Ordens de servico</h5>
                <p class="text-secondary mb-4">Analise operacional por periodo e status.</p>
                <a href="<?= base_url('relatorios/os') ?>" class="btn btn-outline-primary w-100">
                    <i class="bi bi-graph-up me-2"></i>Abrir relatorio
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-4">
        <div class="card glass-card h-100">
            <div class="card-body text-center">
                <i class="bi bi-journal-text text-success mb-3" style="font-size: 3rem;"></i>
                <h5 class="card-title mb-3">Movimentacoes financeiras</h5>
                <p class="text-secondary mb-4">Lista de titulos do mes com foco operacional, conciliacao e leitura por categoria.</p>
                <a href="<?= base_url('relatorios/financeiro') ?>" class="btn btn-outline-success w-100">
                    <i class="bi bi-receipt me-2"></i>Abrir relatorio
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-4">
        <div class="card glass-card h-100">
            <div class="card-body text-center">
                <i class="bi bi-bar-chart-line text-info mb-3" style="font-size: 3rem;"></i>
                <h5 class="card-title mb-3">DRE gerencial</h5>
                <p class="text-secondary mb-4">Resultado economico por competencia, margem bruta e despesas operacionais.</p>
                <a href="<?= base_url('relatorios/dre') ?>" class="btn btn-outline-info w-100">
                    <i class="bi bi-bar-chart me-2"></i>Abrir DRE
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-4">
        <div class="card glass-card h-100">
            <div class="card-body text-center">
                <i class="bi bi-graph-up-arrow text-warning mb-3" style="font-size: 3rem;"></i>
                <h5 class="card-title mb-3">Fluxo de caixa</h5>
                <p class="text-secondary mb-4">Entradas e saidas realizadas, previstos do mes, saldo projetado e composicao por categoria.</p>
                <a href="<?= base_url('relatorios/fluxo-caixa') ?>" class="btn btn-outline-warning w-100">
                    <i class="bi bi-cash-coin me-2"></i>Abrir fluxo
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-4">
        <div class="card glass-card h-100">
            <div class="card-body text-center">
                <i class="bi bi-box-seam text-secondary mb-3" style="font-size: 3rem;"></i>
                <h5 class="card-title mb-3">Estoque</h5>
                <p class="text-secondary mb-4">Posicao atual e itens com quantidade abaixo do ideal.</p>
                <a href="<?= base_url('relatorios/estoque') ?>" class="btn btn-outline-secondary w-100">
                    <i class="bi bi-clipboard-data me-2"></i>Abrir relatorio
                </a>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
