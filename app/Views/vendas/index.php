<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="row align-items-center justify-content-center" style="min-height: 60vh;">
    <div class="col-md-6 text-center">
        <div class="card glass-card p-5">
            <div class="mb-4">
                <i class="bi bi-cart-check-fill text-primary" style="font-size: 5rem; opacity: 0.5;"></i>
            </div>
            <div class="mb-3">
                <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('vendas')" title="Ajuda sobre Vendas">
                    <i class="bi bi-question-circle me-1"></i>Ajuda
                </button>
            </div>
            <h2 class="mb-3">Módulo de Vendas em Desenvolvimento</h2>
            <p class="text-secondary mb-4">
                Estamos preparando este módulo para gerenciar vendas de produtos e peças com integração total ao estoque e financeiro.
            </p>
            <div class="d-flex justify-content-center gap-3">
                <span class="badge bg-primary px-3 py-2">Pedidos</span>
                <span class="badge bg-info px-3 py-2">Faturamento</span>
                <span class="badge bg-success px-3 py-2">Estoque</span>
            </div>
            <hr class="my-4 border-secondary opacity-20">
            <a href="<?= base_url('dashboard') ?>" class="btn btn-outline-primary">
                <i class="bi bi-house-door me-1"></i>Voltar ao Dashboard
            </a>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
