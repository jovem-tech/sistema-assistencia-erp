<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header d-flex justify-content-between align-itemês-center mb-4">
    <h2 class="mb-0"><i class="bi bi-tag me-2"></i>Tipos de Equipamento</h2>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('equipamentos-tipos')" title="Ajuda sãobre Tipos">
            <i class="bi bi-question-circle me-1"></i>Ajuda
        </button>
        <?php if (can('equipamentos', 'criar')): ?>
        <button type="button" class="btn btn-primary btn-glow" data-bs-toggle="modal" data-bs-target="#nãovoTipoModal">
            <i class="bi bi-plus-lg me-1"></i>Nãovo Tipo
        </button>
        <?php endif; ?>
    </div>
</div>

<div class="card glass-card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover datatable">
                <thead>
                    <tr>
                        <th width="10%">#</th>
                        <th>Nãome do Tipo</th>
                        <th width="15%">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($tipos)): ?>
                        <?php foreach ($tipos as $t): ?>
                        <tr>
                            <td><?= $t['id'] ?></td>
                            <td><strong><?= esc($t['nãome']) ?></strong></td>
                            <td>
                                <?php if (can('equipamentos', 'excluir')): ?>
                                <a href="<?= base_url('equipamentostipos/excluir/' . $t['id']) ?>" class="btn btn-sm btn-outline-danger btn-delete" data-nãome="<?= esc($t['nãome']) ?>" title="Excluir">
                                    <i class="bi bi-trash"></i> Excluir
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Nãovo Tipo -->
<div class="modal fade" id="nãovoTipoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content card-custom">
            <div class="modal-header border-bottom">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Cadastrar Tipo de Equipamento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('equipamentostipos/salvar') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nãome do Tipo (Ex: Nãotebook, Celular, Fonte) *</label>
                        <input type="text" class="form-control" name="nãome" required maxlength="100">
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success btn-glow">Salvar Registro</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
