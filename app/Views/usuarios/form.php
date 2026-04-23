<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800"><?= $title ?></h1>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('usuarios')" title="Ajuda sobre Usuários e Permissões">
                    <i class="bi bi-question-circle me-1"></i>Ajuda
                </button>
                <a href="<?= base_url('usuarios') ?>" class="btn btn-outline-secondary" data-back-default="<?= base_url('usuarios') ?>">
                    <i class="bi bi-arrow-left me-2"></i>Voltar
                </a>
            </div>
        </div>
    </div>
</div>

<div class="card glass-card">
    <div class="card-body">
        <form action="<?= base_url(isset($usuario) ? 'usuarios/atualizar/' . $usuario['id'] : 'usuarios/salvar') ?>" method="POST">
            <?= csrf_field() ?>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label text-muted">Nome Completo <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="nome" value="<?= old('nome', $usuario['nome'] ?? '') ?>" required>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label text-muted">E-mail <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" name="email" value="<?= old('email', $usuario['email'] ?? '') ?>" required>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label text-muted">Telefone</label>
                    <input type="text" class="form-control telefone" name="telefone" value="<?= old('telefone', $usuario['telefone'] ?? '') ?>">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label text-muted">Grupo de Acesso <span class="text-danger">*</span></label>
                    <select class="form-select" name="grupo_id" required>
                        <option value="">Selecione o grupo...</option>
                        <?php foreach ($grupos ?? [] as $g): ?>
                        <option value="<?= $g['id'] ?>"
                            <?= old('grupo_id', $usuario['grupo_id'] ?? '') == $g['id'] ? 'selected' : '' ?>>
                            <?= esc($g['nome']) ?>
                            <?php if (!empty($g['descricao'])): ?>
                            — <?= esc($g['descricao']) ?>
                            <?php endif; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (can('grupos', 'visualizar')): ?>
                    <div class="form-text">
                        <a href="<?= base_url('grupos') ?>" target="_blank">
                            <i class="bi bi-shield-lock me-1"></i>Gerenciar grupos e permissões
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label text-muted"><?= isset($usuario) ? 'Nova Senha (deixe em branco para não alterar)' : 'Senha' ?> <?= isset($usuario) ? '' : '<span class="text-danger">*</span>' ?></label>
                    <input type="password" class="form-control" name="senha" <?= isset($usuario) ? '' : 'required' ?>>
                </div>
                
                <div class="col-md-6 mb-3 d-flex align-items-center">
                    <div class="form-check form-switch mt-4">
                        <input class="form-check-input" type="checkbox" id="ativo" name="ativo" <?= old('ativo', $usuario['ativo'] ?? 1) ? 'checked' : '' ?>>
                        <label class="form-check-label text-muted ms-2" for="ativo">Usuário Ativo</label>
                    </div>
                </div>
            </div>

            <hr>
            
            <div class="text-end">
                <button type="submit" class="btn btn-primary btn-glow">
                    <i class="bi bi-save me-2"></i><?= isset($usuario) ? 'Atualizar Usuário' : 'Salvar Usuário' ?>
                </button>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(document).ready(function() {
    var SPMaskBehavior = function (val) {
        return val.replace(/\D/g, '').length === 11 ? '(00) 00000-0000' : '(00) 0000-00009';
    },
    spOptions = {
        onKeyPress: function(val, e, field, options) {
            field.mask(SPMaskBehavior.apply({}, arguments), options);
        }
    };
    $('.telefone').mask(SPMaskBehavior, spOptions);
});
</script>
<?= $this->endSection() ?>
