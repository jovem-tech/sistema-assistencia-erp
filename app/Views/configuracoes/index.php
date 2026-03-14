<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0">Configurações do Sistema</h1>
            <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('configuracoes')" title="Ajuda sobre Configurações">
                <i class="bi bi-question-circle me-1"></i>Ajuda
            </button>
        </div>
    </div>
</div>

<div class="card card-custom">
    <div class="card-body">
        <form action="<?= base_url('configuracoes/salvar') ?>" method="POST" enctype="multipart/form-data">
            <?= csrf_field() ?>
            
            <h5 class="mb-3 border-bottom pb-2">Aparência</h5>
            <div class="row mb-4">
                <div class="col-md-6 mb-3">
                    <label class="form-label text-muted">Tema do Sistema <span class="text-danger">*</span></label>
                    <select class="form-select" name="tema" required>
                        <option value="dark" <?= ($configs['tema'] ?? 'dark') == 'dark' ? 'selected' : '' ?>>Escuro (Dark Theme)</option>
                        <option value="light" <?= ($configs['tema'] ?? 'dark') == 'light' ? 'selected' : '' ?>>Claro (Light Theme)</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label text-muted">Nome do Sistema na Tela de Login / Menu</label>
                    <input type="text" class="form-control" name="sistema_nome" value="<?= esc($configs['sistema_nome'] ?? 'AssistTech') ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label text-muted">Logo do Sistema (Login/Menu)</label>
                    <input type="file" class="form-control" name="sistema_logo" accept="image/png, image/jpeg, image/gif, image/jpg">
                    <?php if(!empty($configs['sistema_logo'])): ?>
                        <small class="text-muted d-block mt-1">Logo atual: <?= esc($configs['sistema_logo']) ?></small>
                    <?php endif; ?>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label text-muted">Ícone da Aba do Navegador (Favicon)</label>
                    <input type="file" class="form-control" name="sistema_icone" accept="image/png, image/jpeg, image/ico, image/x-icon">
                    <?php if(!empty($configs['sistema_icone'])): ?>
                        <small class="text-muted d-block mt-1">Ícone atual: <?= esc($configs['sistema_icone']) ?></small>
                    <?php endif; ?>
                </div>
            </div>
            
            <h5 class="mb-3 border-bottom pb-2">Dados da Empresa</h5>
            <div class="row mb-4">
                <div class="col-md-6 mb-3">
                    <label class="form-label text-muted">Nome da Empresa</label>
                    <input type="text" class="form-control" name="empresa_nome" value="<?= esc($configs['empresa_nome'] ?? '') ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label text-muted">CNPJ</label>
                    <input type="text" class="form-control cpf-cnpj" name="empresa_cnpj" value="<?= esc($configs['empresa_cnpj'] ?? '') ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label text-muted">Telefone</label>
                    <input type="text" class="form-control telefone" name="empresa_telefone" value="<?= esc($configs['empresa_telefone'] ?? '') ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label text-muted">Email</label>
                    <input type="email" class="form-control" name="empresa_email" value="<?= esc($configs['empresa_email'] ?? '') ?>">
                </div>
                <div class="col-md-12 mb-3">
                    <label class="form-label text-muted">Endereço</label>
                    <input type="text" class="form-control" name="empresa_endereco" value="<?= esc($configs['empresa_endereco'] ?? '') ?>">
                </div>
            </div>
            
            <div class="text-end">
                <button type="submit" class="btn btn-primary btn-glow">
                    <i class="bi bi-save me-2"></i>Salvar Configurações
                </button>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
