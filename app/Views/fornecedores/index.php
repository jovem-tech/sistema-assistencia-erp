<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0">Fornecedores</h1>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('fornecedores')" title="Ajuda sobre Fornecedores">
                    <i class="bi bi-question-circle me-1"></i>Ajuda
                </button>
                <?php if (can('fornecedores', 'criar')): ?>
                <a href="<?= base_url('fornecedores/novo') ?>" class="btn btn-primary btn-glow">
                    <i class="bi bi-plus-lg me-2"></i>Novo Fornecedor
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
                        <th>Nome Fantasia / Razão Social</th>
                        <th>CNPJ / CPF</th>
                        <th>Telefone</th>
                        <th>Status</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($fornecedores as $forn): ?>
                        <tr>
                            <td><?= esc($forn['id']) ?></td>
                            <td>
                                <div class="font-weight-bold"><?= esc($forn['nome_fantasia']) ?></div>
                                <?php if ($forn['razao_social']): ?>
                                    <small class="text-muted"><?= esc($forn['razao_social']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= esc($forn['cnpj_cpf']) ?></td>
                            <td><?= esc($forn['telefone1']) ?></td>
                            <td>
                                <?php if ($forn['ativo']): ?>
                                    <span class="badge bg-success">Ativo</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inativo</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <?php if (can('fornecedores', 'editar')): ?>
                                    <a href="<?= base_url('fornecedores/editar/' . $forn['id']) ?>" class="btn btn-outline-primary" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?php endif; ?>
                                    <?php if (can('fornecedores', 'encerrar')): ?>
                                    <a href="javascript:void(0)" class="btn btn-outline-warning" title="Encerrar" onclick="confirmarEncerramento('fornecedores', <?= $forn['id'] ?>)">
                                        <i class="bi bi-archive"></i>
                                    </a>
                                    <?php endif; ?>
                                    <?php if (can('fornecedores', 'excluir')): ?>
                                    <button type="button" class="btn btn-outline-danger btn-delete" 
                                            data-url="<?= base_url('fornecedores/excluir/' . $forn['id']) ?>" title="Excluir">
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
