<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-header">
    <div class="d-flex align-items-center gap-2">
        <h2><i class="bi bi-ui-checks-grid me-2"></i>Checklist de Entrada</h2>
        <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('ordens-de-servico')">
            <i class="bi bi-question-circle me-1"></i>Ajuda
        </button>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-xxl-5">
        <div class="card glass-card h-100">
            <div class="card-body">
                <h5 class="mb-2">Modelo por tipo de equipamento</h5>
                <p class="text-muted small mb-3">
                    Cadastre ou ajuste o checklist utilizado na abertura da OS, com base no tipo de equipamento.
                </p>

                <form action="<?= base_url('checklists/entrada/salvar') ?>" method="post" class="row g-2">
                    <?= csrf_field() ?>
                    <input type="hidden" name="modelo_id" value="<?= esc((string) ($selectedModelo['id'] ?? '')) ?>">
                    <div class="col-12">
                        <label class="form-label">Tipo de equipamento *</label>
                        <select name="tipo_equipamento_id" class="form-select" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($tiposEquipamento as $tipo): ?>
                                <?php $tipoId = (int) ($tipo['id'] ?? 0); ?>
                                <option
                                    value="<?= $tipoId ?>"
                                    <?= ((int) ($selectedModelo['tipo_equipamento_id'] ?? 0) === $tipoId) ? 'selected' : '' ?>
                                >
                                    <?= esc((string) ($tipo['nome'] ?? '')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Nome do checklist *</label>
                        <input type="text" name="nome" class="form-control" required maxlength="160" value="<?= esc((string) ($selectedModelo['nome'] ?? 'Checklist de Entrada')) ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Descrição</label>
                        <textarea name="descricao" rows="2" class="form-control" placeholder="Ex: Conferencia visual inicial para equipamentos na recepcao."><?= esc((string) ($selectedModelo['descricao'] ?? '')) ?></textarea>
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label">Ordem</label>
                        <input type="number" name="ordem" min="0" class="form-control" value="<?= esc((string) ($selectedModelo['ordem'] ?? 0)) ?>">
                    </div>
                    <div class="col-sm-4 d-flex align-items-end">
                        <div class="form-check form-switch pb-2">
                            <input class="form-check-input" type="checkbox" name="ativo" value="1" id="checklistModeloAtivo" <?= ((int) ($selectedModelo['ativo'] ?? 1) === 1) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="checklistModeloAtivo">Ativo</label>
                        </div>
                    </div>
                    <div class="col-sm-4 d-flex align-items-end justify-content-sm-end">
                        <button type="submit" class="btn btn-glow w-100 w-sm-auto">
                            <i class="bi bi-save me-1"></i>Salvar modelo
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-12 col-xxl-7">
        <div class="card glass-card mb-3">
            <div class="card-body">
                <h5 class="mb-2">Modelos cadastrados</h5>
                <?php if (empty($modelos)): ?>
                    <div class="alert alert-info mb-0">Nenhum modelo de Checklist de Entrada cadastrado ainda.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Tipo de equipamento</th>
                                    <th>Nome</th>
                                    <th class="text-center">Ativo</th>
                                    <th class="text-end">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($modelos as $modelo): ?>
                                    <?php $modeloId = (int) ($modelo['id'] ?? 0); ?>
                                    <tr>
                                        <td><?= esc((string) ($modelo['tipo_equipamento_nome'] ?? '-')) ?></td>
                                        <td>
                                            <div class="fw-semibold"><?= esc((string) ($modelo['nome'] ?? '-')) ?></div>
                                            <?php if (!empty($modelo['descricao'])): ?>
                                                <small class="text-muted"><?= esc((string) $modelo['descricao']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge <?= ((int) ($modelo['ativo'] ?? 0) === 1) ? 'bg-success' : 'bg-secondary' ?>">
                                                <?= ((int) ($modelo['ativo'] ?? 0) === 1) ? 'Sim' : 'Não' ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <a class="btn btn-sm btn-outline-primary" href="<?= base_url('checklists/entrada?modelo_id=' . $modeloId) ?>">
                                                Editar itens
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card glass-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <h5 class="mb-0">Itens do checklist</h5>
                    <?php if ($selectedModeloId > 0): ?>
                        <span class="badge bg-primary-subtle text-primary-emphasis border border-primary-subtle">
                            Modelo #<?= esc((string) $selectedModeloId) ?>
                        </span>
                    <?php endif; ?>
                </div>

                <?php if ($selectedModeloId <= 0): ?>
                    <div class="alert alert-warning mb-0">Selecione um modelo para gerenciar os itens.</div>
                <?php else: ?>
                    <form action="<?= base_url('checklists/entrada/item/salvar') ?>" method="post" class="row g-2 mb-3">
                        <?= csrf_field() ?>
                        <input type="hidden" name="checklist_modelo_id" value="<?= esc((string) $selectedModeloId) ?>">
                        <div class="col-12 col-md-7">
                            <label class="form-label">Descrição do item *</label>
                            <input type="text" name="descricao" class="form-control" maxlength="255" required placeholder="Ex: Tela sem trinca ou fissura">
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label">Ordem</label>
                            <input type="number" name="ordem" min="0" class="form-control" value="<?= esc((string) (count($itens) + 1)) ?>">
                        </div>
                        <div class="col-6 col-md-2 d-flex align-items-end">
                            <div class="form-check form-switch pb-2">
                                <input class="form-check-input" type="checkbox" name="ativo" value="1" id="novoChecklistItemAtivo" checked>
                                <label class="form-check-label" for="novoChecklistItemAtivo">Ativo</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-1 d-flex align-items-end justify-content-md-end">
                            <button type="submit" class="btn btn-glow w-100 w-md-auto"><i class="bi bi-plus-lg"></i></button>
                        </div>
                    </form>

                    <?php if (empty($itens)): ?>
                        <div class="alert alert-info mb-0">Nenhum item cadastrado para este modelo.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 80px;">Ordem</th>
                                        <th>Descrição</th>
                                        <th class="text-center" style="width: 110px;">Ativo</th>
                                        <th class="text-end" style="width: 120px;">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($itens as $item): ?>
                                        <tr>
                                            <td><?= esc((string) ($item['ordem'] ?? 0)) ?></td>
                                            <td><?= esc((string) ($item['descricao'] ?? '-')) ?></td>
                                            <td class="text-center">
                                                <span class="badge <?= ((int) ($item['ativo'] ?? 0) === 1) ? 'bg-success' : 'bg-secondary' ?>">
                                                    <?= ((int) ($item['ativo'] ?? 0) === 1) ? 'Sim' : 'Não' ?>
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <form action="<?= base_url('checklists/entrada/item/remover/' . (int) ($item['id'] ?? 0)) ?>" method="post" class="d-inline js-remove-checklist-item">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">Remover</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.querySelectorAll('.js-remove-checklist-item').forEach((form) => {
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!window.Swal || typeof window.Swal.fire !== 'function') {
            form.submit();
            return;
        }
        const result = await Swal.fire({
            icon: 'warning',
            title: 'Remover item?',
            text: 'Esta ação remove o item do checklist de entrada.',
            showCancelButton: true,
            confirmButtonText: 'Remover',
            cancelButtonText: 'Cancelar',
            reverseButtons: true,
            customClass: { popup: 'glass-card' }
        });
        if (result.isConfirmed) {
            form.submit();
        }
    });
});
</script>
<?= $this->endSection() ?>

