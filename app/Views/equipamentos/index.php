<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="page-header d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="bi bi-laptop me-2"></i>Equipamentos</h2>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('equipamentos')" title="Ajuda sobre Equipamentos">
            <i class="bi bi-question-circle me-1"></i>Ajuda
        </button>
        <?php if (can('equipamentos', 'criar')): ?>
        <a href="<?= base_url('equipamentos/novo') ?>" class="btn btn-primary btn-glow">
            <i class="bi bi-plus-lg me-1"></i>Novo Equipamento
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
                        <th>N° Série</th>
                        <th width="14%">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($equipamentos)): foreach ($equipamentos as $eq): ?>
                    <?php $isEncerrado = !empty($eq['is_encerrado']); ?>
                    <?php $osAbertasCount = (int) ($eq['os_abertas_count'] ?? 0); ?>
                    <?php $temOsAbertas = !$isEncerrado && $osAbertasCount > 0; ?>
                    <tr class="<?= $isEncerrado ? 'table-secondary' : '' ?>" data-equipment-row data-equipment-id="<?= (int) $eq['id'] ?>" data-equipment-open-os-count="<?= $osAbertasCount ?>">
                        <td data-label="#"><?= $eq['id'] ?></td>
                        <td data-label="Cliente"><?= esc($eq['cliente_nome'] ?? '-') ?></td>
                        <td data-label="Tipo"><?= esc($eq['tipo_nome'] ?? '-') ?></td>
                        <td data-label="Marca"><strong><?= esc($eq['marca_nome'] ?? '-') ?></strong></td>
                        <td data-label="Modelo">
                            <div><?= esc($eq['display_name'] ?? ($eq['modelo_nome'] ?? '-')) ?></div>
                            <?php if (!empty($eq['technical_summary']) && ($eq['technical_summary'] ?? '') !== ($eq['display_name'] ?? '')): ?>
                            <div class="small text-muted"><?= esc($eq['technical_summary']) ?></div>
                            <?php endif; ?>
                            <div
                                class="small mt-2<?= ($isEncerrado || $temOsAbertas) ? '' : ' d-none' ?>"
                                data-equipment-lifecycle-badge
                                data-equipment-lifecycle-context="list"
                                data-equipment-id="<?= (int) $eq['id'] ?>"
                                data-equipment-open-os-count="<?= $osAbertasCount ?>"
                            >
                                <?php if ($isEncerrado): ?>
                                <span class="badge text-bg-dark">Encerrado</span>
                                <?php if (!empty($eq['motivo_encerramento_label'])): ?>
                                <span class="text-muted ms-2"><?= esc($eq['motivo_encerramento_label']) ?></span>
                                <?php endif; ?>
                                <?php endif; ?>
                                <?php if ($temOsAbertas): ?>
                                <span class="badge text-bg-warning text-dark"><?= $osAbertasCount ?> OS em andamento</span>
                                <span class="text-muted ms-2">Encerramento bloqueado</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td data-label="Nº Série" class="font-monospace small"><?= esc($eq['numero_serie'] ?? '-') ?></td>
                        <td data-label="Ações">
                            <div class="action-btns">
                                <?php if (can('equipamentos', 'visualizar')): ?>
                                <a href="<?= base_url('equipamentos/visualizar/' . $eq['id']) ?>" class="btn btn-sm btn-outline-info" title="Visualizar"><i class="bi bi-eye"></i></a>
                                <?php endif; ?>
                                <?php if (can('equipamentos', 'editar')): ?>
                                <a href="<?= base_url('equipamentos/editar/' . $eq['id']) ?>" class="btn btn-sm btn-outline-secondary" title="Editar"><i class="bi bi-pencil"></i></a>
                                <?php endif; ?>
                                <?php if (can('equipamentos', 'encerrar')): ?>
                                <span data-equipment-active-only data-equipment-id="<?= (int) $eq['id'] ?>" class="<?= $isEncerrado ? 'd-none' : '' ?>">
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-warning<?= $temOsAbertas ? ' disabled' : '' ?>"
                                    title="<?= $temOsAbertas ? 'Bloqueado: há ' . $osAbertasCount . ' OS em andamento' : 'Encerrar' ?>"
                                    <?= $temOsAbertas ? 'disabled aria-disabled="true"' : '' ?>
                                    data-equipment-encerrar-btn
                                    data-equipment-id="<?= (int) $eq['id'] ?>"
                                    data-equipment-open-os-count="<?= $osAbertasCount ?>"
                                    onclick="confirmarEncerramento('equipamentos', <?= (int) $eq['id'] ?>, '<?= esc(csrf_token()) ?>', '<?= esc(csrf_hash()) ?>')"
                                ><i class="bi bi-archive"></i></button>
                                </span>
                                <span data-equipment-closed-only data-equipment-id="<?= (int) $eq['id'] ?>" class="<?= $isEncerrado ? '' : 'd-none' ?>">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-success"
                                        title="Voltar a operacao"
                                        data-equipment-reativar-btn
                                        data-equipment-id="<?= (int) $eq['id'] ?>"
                                        onclick="confirmarReativacao('equipamentos', <?= (int) $eq['id'] ?>, '<?= esc(csrf_token()) ?>', '<?= esc(csrf_hash()) ?>')"
                                    ><i class="bi bi-arrow-clockwise"></i></button>
                                </span>
                                <?php endif; ?>
                                <?php if (can('equipamentos', 'excluir')): ?>
                                <a href="<?= base_url('equipamentos/excluir/' . $eq['id']) ?>" class="btn btn-sm btn-outline-danger btn-delete" data-nome="<?= esc($eq['display_name'] ?? (($eq['marca_nome'] ?? '') . ' ' . ($eq['modelo_nome'] ?? ''))) ?>"><i class="bi bi-trash"></i></a>
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
