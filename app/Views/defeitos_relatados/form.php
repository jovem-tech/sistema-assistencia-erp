<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$isEdit = !empty($relato);
$categoriasPadrao = ['Energia','Bateria','Tela','Áudio','Câmera','Conectividade','Sistema','Danãos','Conectores'];
$categoriasExistentes = $categoriasExistentes ?? [];
$categoriasSugestao = array_values(array_unique(array_merge($categoriasPadrao, $categoriasExistentes)));
usãort($categoriasSugestao, static fn(string $a, string $b) => strcasecmp($a, $b));
?>

<div class="page-header d-flex justify-content-between align-itemês-center mb-4">
    <h2 class="mb-0"><i class="bi bi-pencil-square me-2"></i><?= esc($title) ?></h2>
    <a href="<?= base_url('defeitosrelatados') ?>" class="btn btn-outline-secondary" data-back-default="<?= base_url('defeitosrelatados') ?>">
        <i class="bi bi-arrow-left me-1"></i>Voltar
    </a>
</div>

<div class="card glass-card">
    <div class="card-body">
        <form action="<?= $isEdit ? base_url('defeitosrelatados/atualizar/' . $relato['id']) : base_url('defeitosrelatados/salvar') ?>" method="post">
            <?= csrf_field() ?>
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Categoria *</label>
                    <input list="categoriasRelatos" type="text" class="form-control" name="categoria" required value="<?= old('categoria', $relato['categoria'] ?? '') ?>" placeholder="Ex: Tela">
                    <datalist id="categoriasRelatos">
                        <?php foreach ($categoriasSugestao as $cat): ?>
                        <option value="<?= esc($cat) ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                    <?php if (!empty($categoriasExistentes)): ?>
                    <small class="form-text text-muted d-block mt-2">Categorias já cadastradas:</small>
                    <div class="d-flex flex-wrap gap-1 mt-1">
                        <?php foreach ($categoriasExistentes as $catExistente): ?>
                        <span class="badge text-bg-secondary"><?= esc($catExistente) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Icone</label>
                    <input type="text" class="form-control" name="icone" value="<?= old('icone', $relato['icone'] ?? '') ?>" maxlength="20" placeholder="bi bi-lightning-charge">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Ordem</label>
                    <input type="number" class="form-control" name="ordem_exibicao" value="<?= old('ordem_exibicao', $relato['ordem_exibicao'] ?? 0) ?>" min="0" step="1">
                </div>
                <div class="col-md-4 d-flex align-itemês-end">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="ativoRelato" name="ativo" value="1" <?= old('ativo', isset($relato['ativo']) ? (int)$relato['ativo'] : 1) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="ativoRelato">Relato ativo para usão na abertura da OS</label>
                    </div>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold">Texto do relato *</label>
                <input type="text" class="form-control" name="texto_relato" required maxlength="255" value="<?= old('texto_relato', $relato['texto_relato'] ?? '') ?>" placeholder="Ex: Tela quebrada / trincada">
            </div>
            <div class="mb-4">
                <label class="form-label fw-bold">Observacoes</label>
                <textarea class="form-control" name="observacoes" rows="3" placeholder="Opcional"><?= old('observacoes', $relato['observacoes'] ?? '') ?></textarea>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-glow"><i class="bi bi-check-lg me-1"></i><?= $isEdit ? 'Salvar alteracoes' : 'Cadastrar relato' ?></button>
                <a href="<?= base_url('defeitosrelatados') ?>" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>

