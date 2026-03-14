<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="bi bi-apple me-2"></i>Marcas de Equipamento</h2>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('equipamentos-marcas')" title="Ajuda sobre Marcas">
            <i class="bi bi-question-circle me-1"></i>Ajuda
        </button>
        <?php if (can('equipamentos', 'importar')): ?>
        <button type="button" class="btn btn-outline-info me-2 btn-glow" data-bs-toggle="modal" data-bs-target="#importModal">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i>Importar Lote
        </button>
        <?php endif; ?>
        <?php if (can('equipamentos', 'criar')): ?>
        <button type="button" class="btn btn-primary btn-glow" data-bs-toggle="modal" data-bs-target="#novaMarcaModal">
            <i class="bi bi-plus-lg me-1"></i>Nova Marca
        </button>
        <?php endif; ?>
    </div>
</div>

<div class="card glass-card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover datatable">
                <thead>
                    <tr>
                        <th width="10%">#</th>
                        <th>Nome da Marca</th>
                        <th width="15%">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($marcas)): ?>
                        <?php foreach ($marcas as $m): ?>
                        <tr>
                            <td><?= $m['id'] ?></td>
                            <td><strong><?= esc($m['nome']) ?></strong></td>
                            <td>
                                <?php if (can('equipamentos', 'excluir')): ?>
                                <a href="<?= base_url('equipamentosmarcas/excluir/' . $m['id']) ?>" class="btn btn-sm btn-outline-danger btn-delete" data-nome="<?= esc($m['nome']) ?>" title="Excluir">
                                    <i class="bi bi-trash"></i> Excluir
                                </a>
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

<!-- Modal Nova Marca -->
<div class="modal fade" id="novaMarcaModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content card-custom">
            <div class="modal-header border-bottom">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Cadastrar Marca</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('equipamentosmarcas/salvar') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nome da Marca (Ex: Dell, Samsung, Apple) *</label>
                        <input type="text" class="form-control" name="nome" required maxlength="100">
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success btn-glow">Salvar Registro</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Importação CSV -->
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content card-custom">
            <div class="modal-header border-bottom">
                <h5 class="modal-title"><i class="bi bi-cloud-arrow-up me-2"></i>Importar Lista de Marcas (CSV)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('equipamentosmarcas/importar') ?>" method="POST" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="alert alert-info py-2">
                        <i class="bi bi-info-circle-fill me-2"></i> Seu CSV deve separar colunas por (;) e conter apenas UMA COLUNA com o nome das Marcas. Sem cabeçalhos.
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Selecione o CSV</label>
                        <input class="form-control" type="file" name="arquivo_csv" accept=".csv" required>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success btn-glow">Importar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
