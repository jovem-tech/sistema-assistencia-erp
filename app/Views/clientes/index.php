<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-center gap-3">
        <h2 class="mb-0"><i class="bi bi-people-fill me-2"></i>Clientes</h2>
        <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('06-modulos-do-sistema/clientes.md')" title="Ajuda sobre este módulo">
            <i class="bi bi-question-circle me-1"></i> Ajuda
        </button>
    </div>
    <div>
        <?php if (can('clientes', 'importar')): ?>
        <button type="button" class="btn btn-outline-info me-2 btn-glow" data-bs-toggle="modal" data-bs-target="#importModal">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i>Importar Lote
        </button>
        <?php endif; ?>
        <?php if (can('clientes', 'criar')): ?>
        <a href="<?= base_url('clientes/novo') ?>" class="btn btn-primary btn-glow">
            <i class="bi bi-plus-lg me-1"></i>Novo Cliente
        </a>
        <?php endif; ?>
    </div>
</div>

<div class="card glass-card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover datatable" id="tabelaClientes">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nome / Razão Social</th>
                        <th>CPF / CNPJ</th>
                        <th>Telefone</th>
                        <th>Email</th>
                        <th>Cidade/UF</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($clientes)): ?>
                        <?php foreach ($clientes as $c): ?>
                        <tr>
                            <td><?= $c['id'] ?></td>
                            <td><strong><?= esc($c['nome_razao']) ?></strong></td>
                            <td><?= esc($c['cpf_cnpj'] ?? '-') ?></td>
                            <td><?= esc($c['telefone1']) ?></td>
                            <td><?= esc($c['email'] ?? '-') ?></td>
                            <td><?= esc(($c['cidade'] ?? '') . ($c['uf'] ? '/' . $c['uf'] : '')) ?></td>
                            <td>
                                <div class="action-btns">
                                    <a href="<?= base_url('clientes/visualizar/' . $c['id']) ?>" class="btn btn-sm btn-outline-secondary" title="Visualizar">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <?php if (can('clientes', 'editar')): ?>
                                    <a href="<?= base_url('clientes/editar/' . $c['id']) ?>" class="btn btn-sm btn-outline-secondary" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?php endif; ?>
                                    <?php if (can('clientes', 'excluir')): ?>
                                    <a href="<?= base_url('clientes/excluir/' . $c['id']) ?>" class="btn btn-sm btn-outline-danger btn-delete" data-nome="<?= esc($c['nome_razao']) ?>" title="Excluir">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal de Importação CSV -->
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content card-custom">
            <div class="modal-header border-bottom">
                <h5 class="modal-title" id="importModalLabel"><i class="bi bi-cloud-arrow-up me-2"></i>Importar Clientes em Lote (CSV)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('clientes/importar') ?>" method="POST" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        Para importar múltiplos clientes ao mesmo tempo, baixe o nosso modelo em CSV e faça o upload através deste formulário.
                    </div>
                    
                    <div class="text-center mb-4">
                        <a href="<?= base_url('clientes/modelo-csv') ?>" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-download me-2"></i>Baixar Modelo de Tabela (CSV)
                        </a>
                    </div>
                    
                    <hr class="my-3">

                    <div class="mb-3">
                        <label class="form-label fw-bold">Selecione o arquivo preenchido (.csv)</label>
                        <input class="form-control" type="file" name="arquivo_csv" accept=".csv" required>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success btn-glow">
                        <i class="bi bi-upload me-2"></i>Iniciar Importação
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

