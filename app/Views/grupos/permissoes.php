<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="page-header d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-0"><i class="bi bi-grid-3x3-gap me-2"></i><?= esc($title) ?></h2>
        <small class="text-muted">Marque as permissões que este grupo deverá ter por módulo</small>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('grupos')" title="Ajuda sobre Grupos e PermissÃµes">
            <i class="bi bi-question-circle me-1"></i>Ajuda
        </button>
        <a href="<?= base_url('grupos') ?>" class="btn btn-outline-secondary" data-back-default="<?= base_url('grupos') ?>"><i class="bi bi-arrow-left me-1"></i>Voltar</a>
    </div>
</div>

<form action="<?= base_url('grupos/' . $grupo['id'] . '/permissoes/salvar') ?>" method="POST">
    <?= csrf_field() ?>
    <div class="card glass-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="permissaoMatrix">
                    <thead>
                        <tr>
                            <th class="ps-4 py-3" style="min-width:200px; background:rgba(0,0,0,.2);">
                                <i class="bi bi-layers me-1 text-info"></i>Módulo
                            </th>
                            <?php foreach ($permissoes as $p): ?>
                            <th class="text-center py-3" style="background:rgba(0,0,0,.2); min-width: 110px;">
                                <small class="fw-bold text-uppercase" style="letter-spacing:.06em;">
                                    <?= esc($p['nome']) ?>
                                </small>
                            </th>
                            <?php endforeach; ?>
                            <th class="text-center py-3" style="background:rgba(0,0,0,.2); min-width:100px;">
                                <small class="fw-bold text-muted text-uppercase" style="letter-spacing:.06em;">
                                    Todos
                                </small>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($modulos as $idx => $m): ?>
                        <?php $zebraStyle = $idx % 2 === 0 ? 'background:rgba(255,255,255,.025)' : 'background:rgba(255,255,255,.05)'; ?>
                        <tr style="<?= $zebraStyle ?>">
                            <td class="ps-4 fw-semibold align-middle" style="font-size:.9rem;">
                                <i class="bi <?= esc($m['icone'] ?? 'bi-app') ?> me-2 text-muted"></i>
                                <?= esc($m['nome']) ?>
                            </td>
                            <?php foreach ($permissoes as $p): ?>
                            <?php 
                                $checked = !empty($granted[$m['id']][$p['id']]) ? 'checked' : '';
                                $isDisabled = ($m['slug'] === 'clientes' && $p['slug'] === 'encerrar');
                            ?>
                            <td class="text-center align-middle">
                                <div class="form-check d-flex justify-content-center">
                                    <?php if ($isDisabled): ?>
                                        <i class="bi bi-dash-circle text-muted" title="Não aplicável a Clientes"></i>
                                    <?php else: ?>
                                        <input class="form-check-input perm-check" type="checkbox"
                                               name="permissoes[]"
                                               value="<?= $m['id'] ?>:<?= $p['id'] ?>"
                                               id="perm_<?= $m['id'] ?>_<?= $p['id'] ?>"
                                               data-modulo="<?= $m['id'] ?>"
                                               data-perm="<?= $p['slug'] ?>"
                                               <?= $checked ?>
                                               style="width:18px; height:18px; cursor:pointer;">
                                    <?php endif; ?>
                                </div>
                            </td>
                            <?php endforeach; ?>
                            <!-- Coluna "Todos" por módulo -->
                            <td class="text-center align-middle">
                                <div class="form-check d-flex justify-content-center">
                                    <?php
                                    $allChecked = true;
                                    foreach ($permissoes as $p) {
                                        if (empty($granted[$m['id']][$p['id']])) { $allChecked = false; break; }
                                    }
                                    ?>
                                    <input class="form-check-input check-all-row" type="checkbox"
                                           data-modulo="<?= $m['id'] ?>"
                                           <?= $allChecked ? 'checked' : '' ?>
                                           style="width:18px; height:18px; cursor:pointer; opacity:.7;"
                                           title="Marcar/desmarcar todas as permissões de <?= esc($m['nome']) ?>">
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr style="background:rgba(0,0,0,.15);">
                            <td class="ps-4 text-muted small py-3">Marcar coluna completa:</td>
                            <?php foreach ($permissoes as $p): ?>
                            <td class="text-center align-middle">
                                <input class="form-check-input check-all-col" type="checkbox"
                                       data-permissao="<?= $p['slug'] ?>"
                                       style="width:18px; height:18px; cursor:pointer; opacity:.7;"
                                       title="Marcar '<?= esc($p['nome']) ?>'' para todos os módulos">
                            </td>
                            <?php endforeach; ?>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-3 d-flex gap-2 justify-content-end">
        <a href="<?= base_url('grupos') ?>" class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" class="btn btn-glow px-5">
            <i class="bi bi-save me-1"></i>Salvar Permissões
        </button>
    </div>
</form>

<?= $this->section('scripts') ?>
<script>
// Marcar/desmarcar toda a linha (módulo)
document.querySelectorAll('.check-all-row').forEach(rowChk => {
    rowChk.addEventListener('change', function() {
        const moduloId = this.dataset.modulo;
        document.querySelectorAll(`.perm-check[data-modulo="${moduloId}"]`)
                .forEach(cb => cb.checked = this.checked);
    });
});

// Marcar/desmarcar toda a coluna (permissão)
document.querySelectorAll('.check-all-col').forEach(colChk => {
    colChk.addEventListener('change', function() {
        const permSlug = this.dataset.permissao;
        document.querySelectorAll(`.perm-check[data-perm="${permSlug}"]`)
                .forEach(cb => cb.checked = this.checked);
        syncRowCheckboxes();
    });
});

// Quando check individual muda → atualiza "todos" da linha
document.querySelectorAll('.perm-check').forEach(cb => {
    cb.addEventListener('change', () => syncRowCheckboxes());
});

function syncRowCheckboxes() {
    document.querySelectorAll('.check-all-row').forEach(rowChk => {
        const moduloId = rowChk.dataset.modulo;
        const cbs = document.querySelectorAll(`.perm-check[data-modulo="${moduloId}"]`);
        rowChk.checked = Array.from(cbs).every(c => c.checked);
        rowChk.indeterminate = !rowChk.checked && Array.from(cbs).some(c => c.checked);
    });
}

// Inicializa indeterminate state
syncRowCheckboxes();
</script>
<?= $this->endSection() ?>

<?= $this->endSection() ?>

