<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="page-header d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="bi bi-pencil-square me-2"></i><?= $title ?></h2>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('equipamentos-defeitos')" title="Ajuda sobre Defeitos Comuns">
            <i class="bi bi-question-circle me-1"></i>Ajuda
        </button>
        <a href="<?= base_url('equipamentosdefeitos') ?>" class="btn btn-outline-secondary" data-back-default="<?= base_url('equipamentosdefeitos') ?>"><i class="bi bi-arrow-left me-1"></i>Voltar</a>
    </div>
</div>

<div class="card glass-card">
    <div class="card-body">
        <form action="<?= base_url('equipamentosdefeitos/atualizar/' . $defeito['id']) ?>" method="POST">
            <?= csrf_field() ?>
            <div class="row g-3 mb-3">
                <div class="col-md-7">
                    <label class="form-label fw-bold">Nome do Defeito *</label>
                    <input type="text" class="form-control" name="nome" value="<?= esc($defeito['nome']) ?>" required maxlength="150">
                </div>
                <div class="col-md-5">
                    <label class="form-label fw-bold">Tipo de Equipamento *</label>
                    <select name="tipo_id" class="form-select" required>
                        <option value="">Selecione...</option>
                        <?php foreach ($tipos as $t): ?>
                            <option value="<?= $t['id'] ?>" <?= $defeito['tipo_id'] == $t['id'] ? 'selected' : '' ?>><?= esc($t['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold">Classificação *</label>
                <div class="d-flex gap-3">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="classificacao" value="hardware" id="hwEdit" <?= $defeito['classificacao'] === 'hardware' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="hwEdit"><i class="bi bi-cpu text-danger me-1"></i>Hardware</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="classificacao" value="software" id="swEdit" <?= $defeito['classificacao'] === 'software' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="swEdit"><i class="bi bi-code-slash text-primary me-1"></i>Software</label>
                    </div>
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label fw-bold">Descrição <small class="text-muted">(opcional)</small></label>
                <textarea class="form-control" name="descricao" rows="3"><?= esc($defeito['descricao'] ?? '') ?></textarea>
            </div>
            <div class="d-flex gap-3">
                <button type="submit" class="btn btn-glow"><i class="bi bi-check-lg me-1"></i>Salvar Alterações</button>
                <a href="<?= base_url('equipamentosdefeitos') ?>" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>

