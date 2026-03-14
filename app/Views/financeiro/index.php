<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="page-header">
    <h2><i class="bi bi-currency-dollar me-2"></i>Financeiro</h2>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('financeiro')" title="Ajuda sobre Financeiro">
            <i class="bi bi-question-circle me-1"></i>Ajuda
        </button>
    <?php if (can('financeiro', 'criar')): ?>
    <a href="<?= base_url('financeiro/novo') ?>" class="btn btn-glow">
        <i class="bi bi-plus-lg me-1"></i>Novo Lançamento
    </a>
    <?php endif; ?>
    </div>
</div>

<!-- Summary Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="stat-card stat-card-success">
            <div class="stat-card-body">
                <div class="stat-info">
                    <span class="stat-label">Receitas do Mês</span>
                    <h2 class="stat-value" style="font-size:20px"><?= formatMoney($resumo['receitas'] ?? 0) ?></h2>
                </div>
                <div class="stat-icon"><i class="bi bi-arrow-up-circle"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card stat-card-danger">
            <div class="stat-card-body">
                <div class="stat-info">
                    <span class="stat-label">Despesas do Mês</span>
                    <h2 class="stat-value" style="font-size:20px"><?= formatMoney($resumo['despesas'] ?? 0) ?></h2>
                </div>
                <div class="stat-icon"><i class="bi bi-arrow-down-circle"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card stat-card-primary">
            <div class="stat-card-body">
                <div class="stat-info">
                    <span class="stat-label">Lucro</span>
                    <h2 class="stat-value" style="font-size:20px"><?= formatMoney($resumo['lucro'] ?? 0) ?></h2>
                </div>
                <div class="stat-icon"><i class="bi bi-graph-up"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card stat-card-warning">
            <div class="stat-card-body">
                <div class="stat-info">
                    <span class="stat-label">Pendentes</span>
                    <h2 class="stat-value" style="font-size:20px"><?= formatMoney($resumo['pendentes'] ?? 0) ?></h2>
                </div>
                <div class="stat-icon"><i class="bi bi-exclamation-circle"></i></div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="mb-4 d-flex flex-wrap gap-2">
    <a href="<?= base_url('financeiro') ?>" class="btn btn-sm <?= $filtro_tipo === 'todos' ? 'btn-glow' : 'btn-outline-secondary' ?>">Todos</a>
    <a href="<?= base_url('financeiro?tipo=receber') ?>" class="btn btn-sm <?= $filtro_tipo === 'receber' ? 'btn-success' : 'btn-outline-secondary' ?>">A Receber</a>
    <a href="<?= base_url('financeiro?tipo=pagar') ?>" class="btn btn-sm <?= $filtro_tipo === 'pagar' ? 'btn-danger' : 'btn-outline-secondary' ?>">A Pagar</a>
    <span class="mx-2 border-start border-secondary"></span>
    <a href="<?= base_url('financeiro?status=pendente') ?>" class="btn btn-sm <?= $filtro_status === 'pendente' ? 'btn-warning' : 'btn-outline-secondary' ?>">Pendentes</a>
    <a href="<?= base_url('financeiro?status=pago') ?>" class="btn btn-sm <?= $filtro_status === 'pago' ? 'btn-success' : 'btn-outline-secondary' ?>">Pagos</a>
</div>

<div class="card glass-card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover datatable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Tipo</th>
                        <th>Categoria</th>
                        <th>Descrição</th>
                        <th>Valor</th>
                        <th>Vencimento</th>
                        <th>Pagamento</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($lancamentos)): foreach ($lancamentos as $l): ?>
                    <tr>
                        <td><?= $l['id'] ?></td>
                        <td>
                            <span class="badge <?= $l['tipo'] === 'receber' ? 'bg-success' : 'bg-danger' ?>">
                                <?= $l['tipo'] === 'receber' ? 'Receber' : 'Pagar' ?>
                            </span>
                        </td>
                        <td><?= esc($l['categoria']) ?></td>
                        <td>
                            <?= esc($l['descricao']) ?>
                            <?php if (!empty($l['numero_os'])): ?>
                                <small class="text-muted">(<?= esc($l['numero_os']) ?>)</small>
                            <?php endif; ?>
                        </td>
                        <td><strong><?= formatMoney($l['valor']) ?></strong></td>
                        <td><?= formatDate($l['data_vencimento']) ?></td>
                        <td><?= formatDate($l['data_pagamento'] ?? '') ?></td>
                        <td>
                            <?php if ($l['status'] === 'pago'): ?>
                                <span class="badge bg-success">Pago</span>
                            <?php elseif ($l['status'] === 'cancelado'): ?>
                                <span class="badge bg-secondary">Cancelado</span>
                            <?php else: ?>
                                <span class="badge <?= strtotime($l['data_vencimento']) < time() ? 'bg-danger' : 'bg-warning text-dark' ?>">
                                    <?= strtotime($l['data_vencimento']) < time() ? 'Vencido' : 'Pendente' ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="action-btns">
                                <?php if (can('financeiro', 'editar') && $l['status'] === 'pendente'): ?>
                                <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#modalBaixa" 
                                        onclick="document.getElementById('baixa_id').value='<?= $l['id'] ?>';" title="Dar baixa">
                                    <i class="bi bi-check-lg"></i>
                                </button>
                                <?php endif; ?>
                                <?php if (can('financeiro', 'editar')): ?>
                                <a href="<?= base_url('financeiro/editar/' . $l['id']) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                                <?php endif; ?>
                                <?php if (can('financeiro', 'encerrar')): ?>
                                <a href="javascript:void(0)" class="btn btn-sm btn-outline-warning" title="Encerrar" onclick="confirmarEncerramento('financeiro', <?= $l['id'] ?>)"><i class="bi bi-archive"></i></a>
                                <?php endif; ?>
                                <?php if (can('financeiro', 'excluir')): ?>
                                <a href="<?= base_url('financeiro/excluir/' . $l['id']) ?>" class="btn btn-sm btn-outline-danger btn-delete" data-nome="<?= esc($l['descricao']) ?>"><i class="bi bi-trash"></i></a>
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

<!-- Payment Modal -->
<div class="modal fade" id="modalBaixa" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Registrar Pagamento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formBaixa" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id" id="baixa_id">
                    <div class="mb-3">
                        <label class="form-label">Data do Pagamento</label>
                        <input type="date" name="data_pagamento" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Forma de Pagamento</label>
                        <select name="forma_pagamento" class="form-select">
                            <option value="dinheiro">Dinheiro</option>
                            <option value="pix">PIX</option>
                            <option value="cartao_credito">Cartão Crédito</option>
                            <option value="cartao_debito">Cartão Débito</option>
                            <option value="boleto">Boleto</option>
                            <option value="transferencia">Transferência</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-glow">Confirmar Pagamento</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.getElementById('formBaixa').addEventListener('submit', function(e) {
    e.preventDefault();
    const id = document.getElementById('baixa_id').value;
    this.action = '<?= base_url('financeiro/baixar/') ?>' + id;
    this.submit();
});
</script>
<?= $this->endSection() ?>

