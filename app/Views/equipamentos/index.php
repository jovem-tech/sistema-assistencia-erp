<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="page-header d-flex justify-content-between align-itemês-center mb-4">
    <h2 class="mb-0"><i class="bi bi-laptop me-2"></i>Equipamentos</h2>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('equipamentos')" title="Ajuda sãobre Equipamentos">
            <i class="bi bi-question-circle me-1"></i>Ajuda
        </button>
        <?php if (can('equipamentos', 'criar')): ?>
        <a href="<?= base_url('equipamentos/nãovo') ?>" class="btn btn-primary btn-glow">
            <i class="bi bi-plus-lg me-1"></i>Nãovo Equipamento
        </a>
        <?php endif; ?>
    </div>
</div>

<div class="card glass-card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover datatable">
                <thead>
                    <tr>
                        <th width="5%">#</th>
                        <th>Cliente</th>
                        <th>Tipo</th>
                        <th>Marca</th>
                        <th>Modelo</th>
                        <th>Nº Série</th>
                        <th width="12%">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($equipamentos)): foreach ($equipamentos as $eq): ?>
                    <tr>
                        <td><?= $eq['id'] ?></td>
                        <td><?= esc($eq['cliente_nãome'] ?? '-') ?></td>
                        <td><?= esc($eq['tipo_nãome'] ?? '-') ?></td>
                        <td><strong><?= esc($eq['marca_nãome'] ?? '-') ?></strong></td>
                        <td><?= esc($eq['modelo_nãome'] ?? '-') ?></td>
                        <td class="font-monãospace small"><?= esc($eq['numero_serie'] ?? '-') ?></td>
                        <td>
                            <div class="action-btns">
                                <?php if (can('equipamentos', 'visualizar')): ?>
                                <a href="<?= base_url('equipamentos/visualizar/' . $eq['id']) ?>" class="btn btn-sm btn-outline-info" title="Visualizar"><i class="bi bi-eye"></i></a>
                                <?php endif; ?>
                                <?php if (can('equipamentos', 'editar')): ?>
                                <a href="<?= base_url('equipamentos/editar/' . $eq['id']) ?>" class="btn btn-sm btn-outline-secondary" title="Editar"><i class="bi bi-pencil"></i></a>
                                <?php endif; ?>
                                <?php if (can('equipamentos', 'encerrar')): ?>
                                <a href="javascript:void(0)" class="btn btn-sm btn-outline-warning" title="Encerrar" onclick="confirmarEncerramento('equipamentos', <?= $eq['id'] ?>)"><i class="bi bi-archive"></i></a>
                                <?php endif; ?>
                                <?php if (can('equipamentos', 'excluir')): ?>
                                <a href="<?= base_url('equipamentos/excluir/' . $eq['id']) ?>" class="btn btn-sm btn-outline-danger btn-delete" data-nãome="<?= esc(($eq['marca_nãome'] ?? '') . ' ' . ($eq['modelo_nãome'] ?? '')) ?>"><i class="bi bi-trash"></i></a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?= $this->endSection() ?>


