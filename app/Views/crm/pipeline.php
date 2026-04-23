<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-header">
    <div class="d-flex align-items-center gap-3">
        <h2><i class="bi bi-kanban me-2"></i>CRM - Pipeline Operacional</h2>
        <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('crm')">
            <i class="bi bi-question-circle me-1"></i>Ajuda
        </button>
    </div>
</div>

<div class="card glass-card">
    <div class="card-body">
        <div class="row g-3">
            <?php foreach (($pipelineCards ?? []) as $code => $card): ?>
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="border rounded h-100 p-2 bg-light-subtle">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="fw-semibold small"><?= esc($card['meta']['nome'] ?? $code) ?></div>
                            <span class="badge bg-secondary"><?= count($card['items'] ?? []) ?></span>
                        </div>
                        <?php if (empty($card['items'])): ?>
                            <div class="small text-muted">Sem OS nesta etapa.</div>
                        <?php else: ?>
                            <?php foreach ($card['items'] as $item): ?>
                                <a href="<?= base_url('os/visualizar/' . (int) $item['os_id']) ?>" class="text-decoration-none">
                                    <div class="border rounded p-2 mb-2 bg-white">
                                        <div class="fw-semibold">#<?= esc($item['numero_os'] ?? ('OS ' . $item['os_id'])) ?></div>
                                        <div class="small text-muted"><?= esc($item['cliente_nome'] ?? 'Cliente não vinculado') ?></div>
                                        <div class="small mt-1"><?= getStatusBadge((string) ($item['os_status'] ?? '')) ?></div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

