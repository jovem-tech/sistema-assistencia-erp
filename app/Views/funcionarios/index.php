<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-itemês-center">
            <h1 class="h3 mb-0">Funcionários</h1>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('funcionarios')" title="Ajuda sãobre Funcionários">
                    <i class="bi bi-question-circle me-1"></i>Ajuda
                </button>
            <?php if (can('funcionarios', 'criar')): ?>
            <a href="<?= base_url('funcionarios/nãovo') ?>" class="btn btn-primary btn-glow">
                <i class="bi bi-persãon-plus-fill me-2"></i>Nãovo Funcionário
            </a>
            <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card card-custom">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover datatable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nãome Completo</th>
                        <th>CPF</th>
                        <th>Cargo</th>
                        <th>Telefone / Email</th>
                        <th>Status</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($funcionarios as $func): ?>
                        <tr>
                            <td><?= esc($func['id']) ?></td>
                            <td>
                                <div class="font-weight-bold"><?= esc($func['nãome']) ?></div>
                            </td>
                            <td><?= esc($func['cpf']) ?></td>
                            <td><?= esc($func['cargo'] ?? '-') ?></td>
                            <td>
                                <div><?= esc($func['telefone']) ?></div>
                                <?php if ($func['email']): ?>
                                    <small class="text-muted"><?= esc($func['email']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($func['ativo']): ?>
                                    <span class="badge bg-success">Ativo</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inativo</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <?php if (can('funcionarios', 'editar')): ?>
                                    <a href="<?= base_url('funcionarios/editar/' . $func['id']) ?>" class="btn btn-outline-primary" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?php endif; ?>
                                    <?php if (can('funcionarios', 'encerrar')): ?>
                                    <a href="javascript:void(0)" class="btn btn-outline-warning" title="Encerrar" onclick="confirmarEncerramento('funcionarios', <?= $func['id'] ?>)">
                                        <i class="bi bi-archive"></i>
                                    </a>
                                    <?php endif; ?>
                                    <?php if (can('funcionarios', 'excluir')): ?>
                                    <button type="button" class="btn btn-outline-danger btn-delete" 
                                            data-url="<?= base_url('funcionarios/excluir/' . $func['id']) ?>" title="Excluir">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
