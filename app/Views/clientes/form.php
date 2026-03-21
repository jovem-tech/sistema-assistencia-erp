<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php $isEdit = isset($cliente); ?>

<div class="page-header d-flex justify-content-between align-items-center">
    <h2><i class="bi bi-<?= $isEdit ? 'pencil' : 'plus-lg' ?> me-2"></i><?= $title ?></h2>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('clientes')" title="Ajuda sobre Clientes">
            <i class="bi bi-question-circle me-1"></i>Ajuda
        </button>
        <a href="<?= base_url('clientes') ?>" class="btn btn-outline-secondary" data-back-default="<?= base_url('clientes') ?>">
            <i class="bi bi-arrow-left me-1"></i>Voltar
        </a>
    </div>
</div>

<div class="card glass-card">
    <div class="card-body">
        <form action="<?= $isEdit ? base_url('clientes/atualizar/' . $cliente['id']) : base_url('clientes/salvar') ?>" method="POST">
            
            <!-- Tipo Pessoa -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label text-muted">Tipo de Pessoa</label>
                    <select name="tipo_pessoa" class="form-select">
                        <option value="fisica" <?= ($isEdit && $cliente['tipo_pessoa'] === 'fisica') ? 'selected' : '' ?>>Pessoa Física</option>
                        <option value="juridica" <?= ($isEdit && $cliente['tipo_pessoa'] === 'juridica') ? 'selected' : '' ?>>Pessoa Jurídica</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label text-muted" for="cpf_cnpj" id="label_cpf_cnpj">CPF</label>
                    <input type="text" name="cpf_cnpj" id="cpf_cnpj" class="form-control mask-cpf" 
                           value="<?= $isEdit ? esc($cliente['cpf_cnpj'] ?? '') : old('cpf_cnpj') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label text-muted">RG / IE</label>
                    <input type="text" name="rg_ie" class="form-control" 
                           value="<?= $isEdit ? esc($cliente['rg_ie'] ?? '') : old('rg_ie') ?>">
                </div>
            </div>

            <!-- Dados Pessoais -->
            <h6 class="text-uppercase text-muted mb-3"><i class="bi bi-person me-1"></i>Dados Pessoais</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label">Nome / Razão Social *</label>
                    <input type="text" name="nome_razao" class="form-control" required
                           value="<?= $isEdit ? esc($cliente['nome_razao']) : old('nome_razao') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Telefone 1 *</label>
                    <input type="text" name="telefone1" class="form-control mask-telefone" required
                           value="<?= $isEdit ? esc($cliente['telefone1']) : old('telefone1') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label text-muted">Telefone 2 (Opcional)</label>
                    <input type="text" name="telefone2" class="form-control mask-telefone"
                           value="<?= $isEdit ? esc($cliente['telefone2'] ?? '') : old('telefone2') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label text-muted">Email (Opcional)</label>
                    <input type="email" name="email" class="form-control"
                           value="<?= $isEdit ? esc($cliente['email'] ?? '') : old('email') ?>">
                </div>
            </div>

            <!-- Contato Adicional -->
            <h6 class="text-uppercase text-muted mb-3"><i class="bi bi-people me-1"></i>Contato Adicional <span class="text-lowercase">(Opcional)</span></h6>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label text-muted">Nome (Ex: Esposa, Filho, Vizinho)</label>
                    <input type="text" name="nome_contato" class="form-control"
                           value="<?= $isEdit ? esc($cliente['nome_contato'] ?? '') : old('nome_contato') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label text-muted">Telefone do Contato</label>
                    <input type="text" name="telefone_contato" class="form-control mask-telefone"
                           value="<?= $isEdit ? esc($cliente['telefone_contato'] ?? '') : old('telefone_contato') ?>">
                </div>
            </div>

            <!-- Endereço -->
            <h6 class="text-uppercase text-muted mb-3"><i class="bi bi-geo-alt me-1"></i>Endereço</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-2">
                    <label class="form-label text-muted">CEP</label>
                    <input type="text" name="cep" class="form-control mask-cep"
                           value="<?= $isEdit ? esc($cliente['cep'] ?? '') : old('cep') ?>">
                </div>
                <div class="col-md-5">
                    <label class="form-label text-muted">Endereço</label>
                    <input type="text" name="endereco" class="form-control js-logradouro"
                           value="<?= $isEdit ? esc($cliente['endereco'] ?? '') : old('endereco') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label text-muted">Número</label>
                    <input type="text" name="numero" class="form-control js-numero"
                           value="<?= $isEdit ? esc($cliente['numero'] ?? '') : old('numero') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label text-muted">Complemento</label>
                    <input type="text" name="complemento" class="form-control"
                           value="<?= $isEdit ? esc($cliente['complemento'] ?? '') : old('complemento') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label text-muted">Bairro</label>
                    <input type="text" name="bairro" class="form-control js-bairro"
                           value="<?= $isEdit ? esc($cliente['bairro'] ?? '') : old('bairro') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label text-muted">Cidade</label>
                    <input type="text" name="cidade" class="form-control js-cidade"
                           value="<?= $isEdit ? esc($cliente['cidade'] ?? '') : old('cidade') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label text-muted">UF</label>
                    <select name="uf" class="form-select js-uf">
                        <option value="">--</option>
                        <?php 
                        $ufs = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];
                        foreach($ufs as $uf): ?>
                            <option value="<?= $uf ?>" <?= ($isEdit && ($cliente['uf'] ?? '') === $uf) ? 'selected' : '' ?>><?= $uf ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Observações -->
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <label class="form-label text-muted">Observações</label>
                    <textarea name="observacoes" class="form-control" rows="3"><?= $isEdit ? esc($cliente['observacoes'] ?? '') : old('observacoes') ?></textarea>
                </div>
            </div>

            <!-- Buttons -->
            <div class="d-flex gap-3">
                <button type="submit" class="btn btn-glow">
                    <i class="bi bi-check-lg me-1"></i><?= $isEdit ? 'Atualizar' : 'Cadastrar' ?>
                </button>
                <a href="<?= base_url('clientes') ?>" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>

