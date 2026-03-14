<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header d-flex justify-content-between align-items-center">
    <h2><i class="bi bi-box-seam me-2"></i><?= esc($title) ?></h2>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('relatorios')" title="Ajuda sobre RelatÃ³rios">
            <i class="bi bi-question-circle me-1"></i>Ajuda
        </button>
        <a href="<?= base_url('relatorios') ?>" class="btn btn-outline-secondary" data-back-default="<?= base_url('relatorios') ?>">
            <i class="bi bi-arrow-left me-1"></i>Voltar
        </a>
    </div>
</div>

<div class="card glass-card mb-4">
    <div class="card-body">
        <form method="GET" action="<?= base_url('relatorios/stock') ?>" class="row g-3">
            <div class="col-md-6">
                <label for="tipo" class="form-label">Filtro de Estoque</label>
                <select class="form-select" id="tipo" name="tipo">
                    <option value="todos" <?= $filtro_tipo === 'todos' ? 'selected' : '' ?>>Todas as Peças</option>
                    <option value="baixo" <?= $filtro_tipo === 'baixo' ? 'selected' : '' ?>>Estoque Baixo</option>
                </select>
            </div>
            <div class="col-md-6 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-filter me-1"></i>Filtrar
                </button>
                <a href="<?= base_url('relatorios/stock?tipo='.$filtro_tipo.'&print=1') ?>" target="_blank" class="btn btn-success w-100">
                    <i class="bi bi-printer me-1"></i>Imprimir
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card glass-card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover datatable">
                <thead>
                    <tr>
                        <th>Cód/ID</th>
                        <th>Peça/Produto</th>
                        <th>Qtd. Atual</th>
                        <th>Qtd. Mínima</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($pecas)): ?>
                        <?php foreach ($pecas as $p): ?>
                        <tr>
                            <td><?= $p['id'] ?></td>
                            <td><?= esc($p['nome']) ?></td>
                            <td class="font-monospace fw-bold"><?= $p['quantidade'] ?></td>
                            <td class="font-monospace"><?= $p['quantidade_minima'] ?></td>
                            <td>
                                <?php if ($p['quantidade'] <= $p['quantidade_minima']): ?>
                                    <span class="badge bg-danger"><i class="bi bi-exclamation-triangle-fill me-1"></i>Baixo</span>
                                <?php else: ?>
                                    <span class="badge bg-success"><i class="bi bi-check-circle-fill me-1"></i>OK</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
