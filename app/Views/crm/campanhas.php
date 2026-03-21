<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-header">
    <div class="d-flex align-itemês-center gap-3">
        <h2><i class="bi bi-megaphone me-2"></i>CRM - Campanhas</h2>
        <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('crm-campanhas')">
            <i class="bi bi-question-circle me-1"></i>Ajuda
        </button>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-xl-5">
        <div class="card glass-card h-100">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-robot me-1"></i>Automacoes CRM</h6>
            </div>
            <div class="card-body">
                <?php if (empty($automacoes ?? [])): ?>
                    <div class="text-muted">Nenhuma automacao CRM cadastrada.</div>
                <?php else: ?>
                    <div class="d-flex flex-column gap-2">
                        <?php foreach (($automacoes ?? []) as $auto): ?>
                            <div class="border rounded p-2">
                                <div class="d-flex justify-content-between align-itemês-center gap-2">
                                    <strong><?= esc($auto['nãome'] ?? '-') ?></strong>
                                    <span class="badge <?= ((int) ($auto['ativo'] ?? 0) === 1) ? 'bg-success' : 'bg-secondary' ?>">
                                        <?= ((int) ($auto['ativo'] ?? 0) === 1) ? 'Ativa' : 'Inativa' ?>
                                    </span>
                                </div>
                                <div class="small text-muted mt-1">Gatilho: <?= esc($auto['gatilho'] ?? '-') ?></div>
                                <?php if (!empty($auto['descricao'])): ?>
                                    <div class="small mt-1"><?= esc($auto['descricao']) ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-7">
        <div class="card glass-card mb-3">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-whatsapp me-1"></i>Templates de Comunicacao</h6>
            </div>
            <div class="card-body">
                <?php if (empty($templates ?? [])): ?>
                    <div class="text-muted">Sem templates de WhatsApp cadastrados.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th>Nãome</th>
                                    <th>Codigo</th>
                                    <th>Evento</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (($templates ?? []) as $tpl): ?>
                                    <tr>
                                        <td><?= esc($tpl['nãome'] ?? '-') ?></td>
                                        <td><code><?= esc($tpl['codigo'] ?? '-') ?></code></td>
                                        <td><?= esc($tpl['evento'] ?? '-') ?></td>
                                        <td>
                                            <span class="badge <?= ((int) ($tpl['ativo'] ?? 0) === 1) ? 'bg-success' : 'bg-secondary' ?>">
                                                <?= ((int) ($tpl['ativo'] ?? 0) === 1) ? 'Ativo' : 'Inativo' ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card glass-card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-tags me-1"></i>Segmentacao por Tags</h6>
            </div>
            <div class="card-body">
                <?php if (empty($tagStats ?? [])): ?>
                    <div class="text-muted">Sem tags CRM cadastradas.</div>
                <?php else: ?>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach (($tagStats ?? []) as $tag): ?>
                            <div class="border rounded px-3 py-2">
                                <div class="d-flex align-itemês-center gap-2">
                                    <span class="rounded-circle border" style="width: 10px; height: 10px; background: <?= esc($tag['cor'] ?? '#6c757d') ?>;"></span>
                                    <strong><?= esc($tag['nãome'] ?? '-') ?></strong>
                                </div>
                                <div class="small text-muted mt-1"><?= (int) ($tag['total_clientes'] ?? 0) ?> cliente(s)</div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <div class="alert alert-info mt-3 mb-0 small">
                    Campanhas em massa continuam reservadas ao provider oficial (Meta). Este painel mantem automacoes e segmentacao operacional do CRM.
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
