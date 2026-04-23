<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php $isEdit = isset($lancamento); ?>

<div class="page-header">
    <h2><i class="bi bi-<?= $isEdit ? 'pencil' : 'plus-lg' ?> me-2"></i><?= $title ?></h2>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('financeiro')" title="Ajuda sobre Financeiro">
            <i class="bi bi-question-circle me-1"></i>Ajuda
        </button>
        <a href="<?= base_url('financeiro') ?>" class="btn btn-outline-secondary" data-back-default="<?= base_url('financeiro') ?>"><i class="bi bi-arrow-left me-1"></i>Voltar</a>
    </div>
</div>

<div class="card glass-card">
    <div class="card-body">
        <form action="<?= $isEdit ? base_url('financeiro/atualizar/' . $lancamento['id']) : base_url('financeiro/salvar') ?>" method="POST">
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label">Tipo *</label>
                    <select name="tipo" class="form-select" required>
                        <option value="receber" <?= ($isEdit && $lancamento['tipo'] === 'receber') ? 'selected' : '' ?>>A Receber</option>
                        <option value="pagar" <?= ($isEdit && $lancamento['tipo'] === 'pagar') ? 'selected' : '' ?>>A Pagar</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Categoria *</label>
                    <input type="text" name="categoria" class="form-control" required 
                           placeholder="Ex: Serviço, Aluguel, Compra"
                           value="<?= $isEdit ? esc($lancamento['categoria']) : '' ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Descrição *</label>
                    <input type="text" name="descricao" class="form-control" required
                           value="<?= $isEdit ? esc($lancamento['descricao']) : '' ?>">
                </div>
            </div>
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label">Valor (R$) *</label>
                    <input type="number" step="0.01" name="valor" class="form-control" required
                           value="<?= $isEdit ? $lancamento['valor'] : '' ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Data Vencimento *</label>
                    <input type="date" name="data_vencimento" class="form-control" required
                           value="<?= $isEdit ? $lancamento['data_vencimento'] : date('Y-m-d') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Forma Pagamento</label>
                    <select name="forma_pagamento" class="form-select">
                        <option value="">--</option>
                        <?php $formas = ['dinheiro'=>'Dinheiro','pix'=>'PIX','cartao_credito'=>'Cartão Crédito','cartao_debito'=>'Cartão Débito','boleto'=>'Boleto','transferencia'=>'Transferência'];
                        foreach($formas as $val => $label): ?>
                        <option value="<?= $val ?>" <?= ($isEdit && ($lancamento['forma_pagamento'] ?? '') === $val) ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="pendente" <?= ($isEdit && $lancamento['status'] === 'pendente') ? 'selected' : '' ?>>Pendente</option>
                        <option value="pago" <?= ($isEdit && $lancamento['status'] === 'pago') ? 'selected' : '' ?>>Pago</option>
                        <option value="cancelado" <?= ($isEdit && $lancamento['status'] === 'cancelado') ? 'selected' : '' ?>>Cancelado</option>
                    </select>
                </div>
            </div>
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <label class="form-label">Observações</label>
                    <textarea name="observacoes" class="form-control" rows="2"><?= $isEdit ? esc($lancamento['observacoes'] ?? '') : '' ?></textarea>
                </div>
            </div>
            <div class="d-flex gap-3">
                <button type="submit" class="btn btn-glow"><i class="bi bi-check-lg me-1"></i><?= $isEdit ? 'Atualizar' : 'Cadastrar' ?></button>
                <a href="<?= base_url('financeiro') ?>" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>

