<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="page-header d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="bi bi-shield-lock me-2"></i>Grupos de Acesso</h2>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('grupos')" title="Ajuda sobre Grupos e Permissões">
            <i class="bi bi-question-circle me-1"></i>Ajuda
        </button>
        <?php if (can('grupos', 'criar')): ?>
        <a href="<?= base_url('grupos/novo') ?>" class="btn btn-primary btn-glow">
            <i class="bi bi-plus-lg me-1"></i>Novo Grupo
        </a>
        <?php endif; ?>
    </div>
</div>

<div class="row g-4">
    <?php foreach ($grupos as $g): ?>
    <div class="col-md-6 col-lg-4">
        <div class="card glass-card h-100">
            <div class="card-body d-flex flex-column">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <span style="background: rgba(99,102,241,.2); border-radius:10px; width:46px; height:46px;
                                 display:flex; align-items:center; justify-content:center;">
                        <i class="bi bi-people-fill text-info fs-5"></i>
                    </span>
                    <div>
                        <h5 class="mb-0 fw-bold"><?= esc($g['nome']) ?></h5>
                        <?php if ($g['sistema'] ?? false): ?>
                            <span class="badge" style="background:rgba(99,102,241,.3); color:#c7d2fe; font-size:.7rem;">Sistema</span>
                        <?php endif; ?>
                    </div>
                </div>
                <p class="text-muted small flex-grow-1"><?= esc($g['descricao'] ?: 'Sem descrição.') ?></p>
                <div class="d-flex gap-2 mt-2">
                    <?php if (can('grupos', 'editar')): ?>
                    <a href="<?= base_url('grupos/' . $g['id'] . '/permissoes') ?>" class="btn btn-sm btn-glow flex-grow-1">
                        <i class="bi bi-grid-3x3-gap me-1"></i>Permissões
                    </a>
                    <a href="<?= base_url('grupos/editar/' . $g['id']) ?>" class="btn btn-sm btn-outline-secondary" title="Editar nome">
                        <i class="bi bi-pencil"></i>
                    </a>
                    <?php endif; ?>
                    <?php if (can('grupos', 'excluir') && !($g['sistema'] ?? false)): ?>
                    <a href="<?= base_url('grupos/excluir/' . $g['id']) ?>" class="btn btn-sm btn-outline-danger btn-delete" data-nome="<?= esc($g['nome']) ?>">
                        <i class="bi bi-trash"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?= $this->endSection() ?>

