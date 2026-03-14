<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h1 class="h3 mb-0">Meu Perfil</h1>
        <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('perfil')" title="Ajuda sobre Perfil">
            <i class="bi bi-question-circle me-1"></i>Ajuda
        </button>
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card card-custom text-center">
            <div class="card-body py-5">
                <div class="mb-4">
                    <?php if (!empty($usuario['foto']) && file_exists('uploads/usuarios/' . $usuario['foto'])): ?>
                        <img src="<?= base_url('uploads/usuarios/' . $usuario['foto']) ?>" alt="Foto de Perfil" class="img-thumbnail rounded-circle" style="width: 150px; height: 150px; object-fit: cover;">
                    <?php else: ?>
                        <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mx-auto" style="width: 150px; height: 150px;">
                            <i class="bi bi-person text-secondary" style="font-size: 5rem;"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <h4 class="mb-1"><?= esc($usuario['nome']) ?></h4>
                <p class="text-muted mb-0"><?= esc($usuario['email']) ?></p>
                <div class="mt-3">
                    <span class="badge bg-primary px-3 py-2 text-uppercase"><?= esc($usuario['perfil']) ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card card-custom">
            <div class="card-body">
                <form action="<?= base_url('perfil/salvar') ?>" method="POST" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    
                    <h5 class="mb-4 pb-2 border-bottom">Dados Pessoais</h5>
                    
                    <div class="row mb-3">
                        <div class="col-md-12 mb-3">
                            <label class="form-label text-muted">Foto de Perfil</label>
                            <input type="file" class="form-control" name="foto" accept="image/png, image/jpeg, image/gif, image/jpg">
                            <small class="text-muted">Formatos aceitos: JPG, PNG, GIF. O upload irá substituir sua foto atual.</small>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Nome Completo <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nome" value="<?= esc(old('nome', $usuario['nome'])) ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" name="email" value="<?= esc(old('email', $usuario['email'])) ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Telefone</label>
                            <input type="text" class="form-control telefone" name="telefone" value="<?= esc(old('telefone', $usuario['telefone'])) ?>">
                        </div>
                    </div>
                    
                    <h5 class="mb-4 pb-2 border-bottom">Alterar Senha <small class="text-muted fs-6 fw-normal">(opcional)</small></h5>
                    <div class="row mb-4">
                        <div class="col-md-12 mb-3">
                            <label class="form-label text-muted">Senha Atual</label>
                            <input type="password" class="form-control" name="senha_atual" placeholder="Sua senha atual">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Nova Senha</label>
                            <input type="password" class="form-control" name="nova_senha" placeholder="A partir de 6 caracteres">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Confirmar Nova Senha</label>
                            <input type="password" class="form-control" name="confirma_senha" placeholder="Repita a nova senha">
                        </div>
                    </div>
                    
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary btn-glow">
                            <i class="bi bi-save me-2"></i>Salvar Perfil
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
