<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php $isEdit = isset($peca); ?>

<div class="page-header">
    <h2><i class="bi bi-<?= $isEdit ? 'pencil' : 'plus-lg' ?> me-2"></i><?= $title ?></h2>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('estoque')" title="Ajuda sãobre Estoque">
            <i class="bi bi-question-circle me-1"></i>Ajuda
        </button>
        <a href="<?= base_url('estoque') ?>" class="btn btn-outline-secondary" data-back-default="<?= base_url('estoque') ?>"><i class="bi bi-arrow-left me-1"></i>Voltar</a>
    </div>
</div>

<div class="card glass-card">
    <div class="card-body">
        <form action="<?= $isEdit ? base_url('estoque/atualizar/' . $peca['id']) : base_url('estoque/salvar') ?>" method="POST">
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label">Código</label>
                    <input type="text" name="codigo" class="form-control" value="<?= $isEdit ? esc($peca['codigo']) : ($codigo ?? '') ?>">
                </div>
                <div class="col-md-5">
                    <label class="form-label">Nãome *</label>
                    <input type="text" name="nãome" class="form-control" required value="<?= $isEdit ? esc($peca['nãome']) : '' ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Categoria</label>
                    <input type="text" name="categoria" class="form-control" placeholder="Ex: Tela, Bateria, Conector" value="<?= $isEdit ? esc($peca['categoria'] ?? '') : '' ?>">
                </div>
            </div>
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label">Preço Custo (R$) *</label>
                    <input type="number" step="0.01" name="preco_custo" class="form-control" required value="<?= $isEdit ? $peca['preco_custo'] : '' ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Preço Venda (R$) *</label>
                    <input type="number" step="0.01" name="preco_venda" class="form-control" required value="<?= $isEdit ? $peca['preco_venda'] : '' ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Quantidade</label>
                    <input type="number" name="quantidade_atual" class="form-control" value="<?= $isEdit ? $peca['quantidade_atual'] : 0 ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Estoque Mín.</label>
                    <input type="number" name="estoque_minimo" class="form-control" value="<?= $isEdit ? $peca['estoque_minimo'] : 1 ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Estoque Máx.</label>
                    <input type="number" name="estoque_maximo" class="form-control" value="<?= $isEdit ? ($peca['estoque_maximo'] ?? '') : '' ?>">
                </div>
            </div>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label">Cód. Fabricante</label>
                    <input type="text" name="codigo_fabricante" class="form-control" value="<?= $isEdit ? esc($peca['codigo_fabricante'] ?? '') : '' ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Fornecedor</label>
                    <input type="text" name="fornecedor" class="form-control" value="<?= $isEdit ? esc($peca['fornecedor'] ?? '') : '' ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Localização</label>
                    <input type="text" name="localizacao" class="form-control" placeholder="Ex: Prateleira A3" value="<?= $isEdit ? esc($peca['localizacao'] ?? '') : '' ?>">
                </div>
            </div>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label">Modelos Compatíveis</label>
                    <textarea name="modelos_compativeis" class="form-control" rows="2"><?= $isEdit ? esc($peca['modelos_compativeis'] ?? '') : '' ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Observações</label>
                    <textarea name="observacoes" class="form-control" rows="2"><?= $isEdit ? esc($peca['observacoes'] ?? '') : '' ?></textarea>
                </div>
            </div>
            <div class="d-flex gap-3">
                <button type="submit" class="btn btn-glow"><i class="bi bi-check-lg me-1"></i><?= $isEdit ? 'Atualizar' : 'Cadastrar' ?></button>
                <a href="<?= base_url('estoque') ?>" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>

