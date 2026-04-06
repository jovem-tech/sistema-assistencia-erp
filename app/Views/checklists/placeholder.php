<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-header">
    <div class="d-flex align-items-center gap-2">
        <h2><i class="bi bi-ui-checks me-2"></i><?= esc((string) ($nomeModulo ?? 'Checklist')) ?></h2>
    </div>
</div>

<div class="card glass-card">
    <div class="card-body">
        <div class="alert alert-info mb-0">
            <div class="fw-semibold mb-1">Em desenvolvimento</div>
            Este modulo ja esta reservado no menu e preparado para expansao da arquitetura de checklist.
        </div>
    </div>
</div>
<?= $this->endSection() ?>
