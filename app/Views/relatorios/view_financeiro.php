<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header d-flex justify-content-between align-items-center">
    <h2><i class="bi bi-cash-stack me-2"></i><?= esc($title) ?></h2>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('relatorios')" title="Ajuda sobre Relatórios">
            <i class="bi bi-question-circle me-1"></i>Ajuda
        </button>
        <a href="<?= base_url('relatorios') ?>" class="btn btn-outline-secondary" data-back-default="<?= base_url('relatorios') ?>">
            <i class="bi bi-arrow-left me-1"></i>Voltar
        </a>
    </div>
</div>

<div class="card glass-card mb-4">
    <div class="card-body">
        <form method="GET" action="<?= base_url('relatorios/financial') ?>" class="row g-3">
            <div class="col-md-5">
                <label for="mes" class="form-label">Mês/Ano</label>
                <input type="month" class="form-control" id="mes" name="mes" value="<?= esc($filtro_mes) ?>">
            </div>
            <div class="col-md-7 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-filter me-1"></i>Filtrar
                </button>
                <a href="<?= base_url('relatorios/financial?mes='.$filtro_mes.'&print=1') ?>" target="_blank" class="btn btn-success">
                    <i class="bi bi-printer me-1"></i>Imprimir
                </a>
            </div>
        </form>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card glass-card border-success">
            <div class="card-body text-center">
                <h6 class="text-success mb-2 uppercase tracking-wider">Receitas (Pagas)</h6>
                <h3 class="text-body m-0">R$ <?= number_format($resumo['receitas'], 2, ',', '.') ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card glass-card border-danger">
            <div class="card-body text-center">
                <h6 class="text-danger mb-2 uppercase tracking-wider">Despesas (Pagas)</h6>
                <h3 class="text-body m-0">R$ <?= number_format($resumo['despesas'], 2, ',', '.') ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card glass-card <?= $resumo['lucro'] >= 0 ? 'border-primary' : 'border-warning' ?>">
            <div class="card-body text-center">
                <h6 class="<?= $resumo['lucro'] >= 0 ? 'text-primary' : 'text-warning' ?> mb-2 uppercase tracking-wider">Resultado Mês</h6>
                <h3 class="text-body m-0">R$ <?= number_format($resumo['lucro'], 2, ',', '.') ?></h3>
            </div>
        </div>
    </div>
</div>

<div class="card glass-card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover datatable">
                <thead>
                    <tr>
                        <th>Descrição</th>
                        <th>Tipo</th>
                        <th>Data Venc.</th>
                        <th>Valor</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($lancamentos)): ?>
                        <?php foreach ($lancamentos as $l): ?>
                        <tr>
                            <td><?= esc($l['descricao']) ?></td>
                            <td>
                                <?php if ($l['tipo'] === 'receber'): ?>
                                    <span class="text-success"><i class="bi bi-arrow-up-right-circle me-1"></i>Receita</span>
                                <?php else: ?>
                                    <span class="text-danger"><i class="bi bi-arrow-down-left-circle me-1"></i>Despesa</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('d/m/Y', strtotime($l['data_vencimento'])) ?></td>
                            <td class="font-monospace">R$ <?= number_format($l['valor'], 2, ',', '.') ?></td>
                            <td>
                                <?php if ($l['status'] === 'pago'): ?>
                                    <span class="badge bg-success">Pago</span>
                                <?php else: ?>
                                    <span class="badge bg-warning">Pendente</span>
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

<?= $this->endSection() ?>
