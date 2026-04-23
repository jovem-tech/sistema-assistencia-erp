<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php $isEdit = isset($grupo); ?>

<div class="page-header d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="bi bi-shield-lock me-2"></i><?= $title ?></h2>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('grupos')" title="Ajuda sobre Grupos e Permissões">
            <i class="bi bi-question-circle me-1"></i>Ajuda
        </button>
        <a href="<?= base_url('grupos') ?>" class="btn btn-outline-secondary" data-back-default="<?= base_url('grupos') ?>"><i class="bi bi-arrow-left me-1"></i>Voltar</a>
    </div>
</div>

<div class="card glass-card" style="max-width: 540px;">
    <div class="card-body">
        <form action="<?= $isEdit ? base_url('grupos/atualizar/' . $grupo['id']) : base_url('grupos/salvar') ?>" method="POST">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label fw-bold">Nome do Grupo *</label>
                <input type="text" name="nome" class="form-control" required maxlength="80"
                       value="<?= esc($grupo['nome'] ?? old('nome')) ?>"
                       placeholder="Ex: Vendedor, Supervisor, Caixa...">
            </div>
            <div class="mb-4">
                <label class="form-label fw-bold">Descrição <small class="text-muted">(opcional)</small></label>
                <textarea name="descricao" class="form-control" rows="2" maxlength="200"
                          placeholder="Descreva brevemente as responsabilidades deste grupo..."><?= esc($grupo['descricao'] ?? old('descricao')) ?></textarea>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-glow"><?= $isEdit ? '<i class="bi bi-check-lg me-1"></i>Salvar' : '<i class="bi bi-plus-lg me-1"></i>Criar Grupo' ?></button>
                <a href="<?= base_url('grupos') ?>" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>

