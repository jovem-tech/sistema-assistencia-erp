<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="page-header">
    <h2><i class="bi bi-arrow-left-right me-2"></i><?= $title ?></h2>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('estoque-movimentacoes')" title="Ajuda sobre MovimentaÃ§Ãµes">
            <i class="bi bi-question-circle me-1"></i>Ajuda
        </button>
        <a href="<?= base_url('estoque') ?>" class="btn btn-outline-secondary" data-back-default="<?= base_url('estoque') ?>"><i class="bi bi-arrow-left me-1"></i>Voltar</a>
    </div>
</div>

<?php if ($peca): ?>
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="stat-card stat-card-primary">
            <div class="stat-card-body">
                <div class="stat-info">
                    <span class="stat-label">Qtd. Atual</span>
                    <h2 class="stat-value"><?= $peca['quantidade_atual'] ?></h2>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card stat-card-info">
            <div class="stat-card-body">
                <div class="stat-info">
                    <span class="stat-label">P. Custo</span>
                    <h2 class="stat-value" style="font-size:20px"><?= formatMoney($peca['preco_custo']) ?></h2>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card stat-card-success">
            <div class="stat-card-body">
                <div class="stat-info">
                    <span class="stat-label">P. Venda</span>
                    <h2 class="stat-value" style="font-size:20px"><?= formatMoney($peca['preco_venda']) ?></h2>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="card glass-card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover datatable mb-0">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Tipo</th>
                        <th>Quantidade</th>
                        <th>Motivo</th>
                        <th>OS</th>
                        <th>Responsável</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($movimentacoes)): foreach ($movimentacoes as $m): ?>
                    <tr>
                        <td><?= formatDate($m['created_at'], true) ?></td>
                        <td>
                            <?php if ($m['tipo'] === 'entrada'): ?>
                                <span class="badge bg-success">Entrada</span>
                            <?php elseif ($m['tipo'] === 'saida'): ?>
                                <span class="badge bg-danger">Saída</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">Ajuste</span>
                            <?php endif; ?>
                        </td>
                        <td><strong><?= $m['quantidade'] ?></strong></td>
                        <td><?= esc($m['motivo'] ?? '-') ?></td>
                        <td><?= esc($m['numero_os'] ?? '-') ?></td>
                        <td><?= esc($m['responsavel_nome'] ?? '-') ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

