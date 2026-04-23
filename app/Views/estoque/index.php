<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="page-header d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="bi bi-box-seam-fill me-2"></i>Estoque de Peças</h2>
    <div>
        <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('estoque')" title="Ajuda sobre Estoque">
            <i class="bi bi-question-circle me-1"></i>Ajuda
        </button>
        <?php if (can('estoque', 'exportar')): ?>
        <a href="<?= base_url('estoque/exportar') ?>" class="btn btn-outline-success me-2 btn-glow">
            <i class="bi bi-file-earmark-excel me-1"></i>Exportar CSV
        </a>
        <?php endif; ?>
        <?php if (can('estoque', 'importar')): ?>
        <button type="button" class="btn btn-outline-info me-2 btn-glow" data-bs-toggle="modal" data-bs-target="#importModal">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i>Importar Lote
        </button>
        <?php endif; ?>
        <?php if (can('estoque', 'criar')): ?>
        <a href="<?= base_url('estoque/novo') ?>" class="btn btn-primary btn-glow">
            <i class="bi bi-plus-lg me-1"></i>Nova Peca
        </a>
        <?php endif; ?>
    </div>
</div>

<div class="card glass-card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover datatable" id="tabelaEstoque">
                <thead>
                    <tr>
                        <th>Codigo</th>
                        <th>Nome</th>
                        <th>Categoria</th>
                        <th>Tipo Equipamento</th>
                        <th>Custo</th>
                        <th>Venda</th>
                        <th>Qtd</th>
                        <th>Min.</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($pecas)): foreach ($pecas as $p): ?>
                    <tr>
                        <td data-label="Codigo"><code><?= esc((string) ($p['codigo'] ?? '-')) ?></code></td>
                        <td data-label="Nome"><strong><?= esc((string) ($p['nome'] ?? '')) ?></strong></td>
                        <td data-label="Categoria"><?= esc((string) ($p['categoria'] ?? '-')) ?></td>
                        <td data-label="Tipo Equipamento"><?= esc((string) ($p['tipo_equipamento'] ?? 'Diverso')) ?></td>
                        <td data-label="Custo"><?= formatMoney((float) ($p['preco_custo'] ?? 0)) ?></td>
                        <td data-label="Venda"><?= formatMoney((float) ($p['preco_venda'] ?? 0)) ?></td>
                        <td data-label="Qtd">
                            <span class="badge <?= ((int) ($p['quantidade_atual'] ?? 0)) <= ((int) ($p['estoque_minimo'] ?? 0)) ? 'bg-danger' : 'bg-success' ?>">
                                <?= (int) ($p['quantidade_atual'] ?? 0) ?>
                            </span>
                        </td>
                        <td data-label="Min."><?= (int) ($p['estoque_minimo'] ?? 0) ?></td>
                        <td data-label="Ações">
                            <div class="action-btns">
                                <a href="<?= base_url('estoque/movimentacoes/' . $p['id']) ?>" class="btn btn-sm btn-outline-secondary" title="Movimentacoes"><i class="bi bi-arrow-left-right"></i></a>
                                <?php if (can('estoque', 'editar')): ?>
                                <a href="<?= base_url('estoque/editar/' . $p['id']) ?>" class="btn btn-sm btn-outline-secondary" title="Editar"><i class="bi bi-pencil"></i></a>
                                <?php endif; ?>
                                <?php if (can('estoque', 'encerrar') && !empty($p['ativo'])): ?>
                                <button type="button" class="btn btn-sm btn-outline-warning" title="Encerrar" onclick="confirmarEncerramento('estoque/excluir/<?= $p['id'] ?>', '<?= esc((string) ($p['nome'] ?? '')) ?>')">
                                    <i class="bi bi-archive"></i>
                                </button>
                                <?php endif; ?>
                                <?php if (can('estoque', 'excluir')): ?>
                                <a href="<?= base_url('estoque/excluir/' . $p['id']) ?>" class="btn btn-sm btn-outline-danger btn-delete" data-nome="<?= esc((string) ($p['nome'] ?? '')) ?>"><i class="bi bi-trash"></i></a>
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

<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content card-custom">
            <div class="modal-header border-bottom">
                <h5 class="modal-title" id="importModalLabel"><i class="bi bi-cloud-arrow-up me-2"></i>Importar Estoque (CSV)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('estoque/importar') ?>" method="POST" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        Baixe o modelo CSV, preencha e envie para importar pecas em lote.
                    </div>

                    <div class="text-center mb-4">
                        <a href="<?= base_url('estoque/modelo-csv') ?>" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-download me-2"></i>Baixar Modelo de Estoque (CSV)
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
                        <i class="bi bi-upload me-2"></i>Iniciar Importacao
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
