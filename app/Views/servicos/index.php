<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="bi bi-gear-wide-connected me-2"></i>Serviços</h2>
    <div>
        <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('servicos')" title="Ajuda sobre ServiÃ§os">
            <i class="bi bi-question-circle me-1"></i>Ajuda
        </button>
        <?php if (can('servicos', 'exportar')): ?>
        <a href="<?= base_url('servicos/exportar') ?>" class="btn btn-outline-success me-2 btn-glow">
            <i class="bi bi-file-earmark-excel me-1"></i>Exportar CSV
        </a>
        <?php endif; ?>
        <?php if (can('servicos', 'importar')): ?>
        <button type="button" class="btn btn-outline-info me-2 btn-glow" data-bs-toggle="modal" data-bs-target="#importModal">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i>Importar Lote
        </button>
        <?php endif; ?>
        <?php if (can('servicos', 'criar')): ?>
        <a href="<?= base_url('servicos/novo') ?>" class="btn btn-primary btn-glow">
            <i class="bi bi-plus-lg me-1"></i>Novo Serviço
        </a>
        <?php endif; ?>
    </div>
</div>

<div class="card glass-card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover datatable" id="tabelaServicos">
                <thead>
                    <tr>
                        <th width="50">ID</th>
                        <th>Nome</th>
                        <th>Descrição</th>
                        <th>Valor Padrão</th>
                        <th>Status</th>
                        <th width="150" class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($servicos)): foreach ($servicos as $s): ?>
                    <tr>
                        <td><?= $s['id'] ?></td>
                        <td><strong><?= esc($s['nome']) ?></strong></td>
                        <td><?= esc(substr($s['descricao'] ?? '', 0, 50)) ?><?= strlen($s['descricao'] ?? '') > 50 ? '...' : '' ?></td>
                        <td><?= formatMoney($s['valor']) ?></td>
                        <td>
                            <span class="badge <?= $s['status'] === 'ativo' ? 'bg-success' : 'bg-secondary' ?>">
                                <?= ucfirst($s['status']) ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <div class="action-btns">
                                <?php if (can('servicos', 'editar') && $s['status'] === 'ativo'): ?>
                                <a href="<?= base_url('servicos/editar/' . $s['id']) ?>" class="btn btn-sm btn-outline-secondary" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php endif; ?>
                                
                                <?php if (can('servicos', 'encerrar') && $s['status'] === 'ativo'): ?>
                                <button type="button" class="btn btn-sm btn-outline-warning" title="Encerrar" onclick="confirmarEncerramento('servicos/encerrar/<?= $s['id'] ?>', '<?= esc($s['nome']) ?>')">
                                    <i class="bi bi-archive"></i>
                                </button>
                                <?php endif; ?>

                                <?php if (can('servicos', 'excluir')): ?>
                                <a href="<?= base_url('servicos/excluir/' . $s['id']) ?>" class="btn btn-sm btn-outline-danger btn-delete" data-nome="<?= esc($s['nome']) ?>" title="Excluir">
                                    <i class="bi bi-trash"></i>
                                </a>
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

<!-- Modal de Importação CSV -->
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content card-custom">
            <div class="modal-header border-bottom">
                <h5 class="modal-title" id="importModalLabel"><i class="bi bi-cloud-arrow-up me-2"></i>Importar Serviços (CSV)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('servicos/importar') ?>" method="POST" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        Para importar múltiplos serviços, baixe o modelo em CSV, preencha e faça o upload.
                    </div>
                    
                    <div class="text-center mb-4">
                        <a href="<?= base_url('servicos/modelo-csv') ?>" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-download me-2"></i>Baixar Modelo (CSV)
                        </a>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Selecione o arquivo CSV</label>
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
