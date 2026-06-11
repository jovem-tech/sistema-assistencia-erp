<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$receita = $dre['receita'] ?? [];
$custos = $dre['custos_diretos'] ?? [];
$outrasReceitas = $dre['outras_receitas'] ?? [];
$despesas = $dre['despesas_operacionais'] ?? [];
?>

<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h2><i class="bi bi-bar-chart-line me-2"></i><?= esc($title) ?></h2>
    <div class="d-flex flex-wrap gap-2">
        <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('financeiro')" title="Ajuda sobre DRE">
            <i class="bi bi-question-circle me-1"></i>Ajuda
        </button>
        <a href="<?= base_url('relatorios') ?>" class="btn btn-outline-secondary" data-back-default="<?= base_url('relatorios') ?>">
            <i class="bi bi-arrow-left me-1"></i>Voltar
        </a>
    </div>
</div>

<div class="card glass-card mb-4">
    <div class="card-body">
        <form method="GET" action="<?= base_url('relatorios/dre') ?>" class="row g-3">
            <div class="col-md-5">
                <label for="mes" class="form-label">Mes/ano</label>
                <input type="month" class="form-control" id="mes" name="mes" value="<?= esc($filtro_mes) ?>">
            </div>
            <div class="col-md-7 d-flex align-items-end gap-2 flex-wrap">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-filter me-1"></i>Atualizar DRE
                </button>
                <a href="<?= base_url('relatorios/fluxo-caixa?mes=' . urlencode((string) $filtro_mes)) ?>" class="btn btn-outline-warning">
                    <i class="bi bi-graph-up-arrow me-1"></i>Ver fluxo de caixa
                </a>
            </div>
        </form>
    </div>
</div>

<div class="alert alert-light border shadow-sm mb-4">
    <strong>DRE gerencial por competencia (<?= esc($dre['periodo_label'] ?? '') ?>).</strong>
    <span class="d-block mt-1">Receitas de OS entram na data de entrega. Custos diretos sao lidos dos itens da OS e demais despesas sao classificadas pela data de competencia do financeiro. Despesas marcadas como `fixa mensal na DRE` passam a se repetir automaticamente nos meses seguintes.</span>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card glass-card border-success h-100">
            <div class="card-body text-center">
                <h6 class="text-success mb-2 uppercase tracking-wider">Receita liquida</h6>
                <h3 class="m-0"><?= formatMoney($receita['receita_liquida'] ?? 0) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card glass-card border-danger h-100">
            <div class="card-body text-center">
                <h6 class="text-danger mb-2 uppercase tracking-wider">Custos diretos</h6>
                <h3 class="m-0"><?= formatMoney($custos['total'] ?? 0) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card glass-card border-primary h-100">
            <div class="card-body text-center">
                <h6 class="text-primary mb-2 uppercase tracking-wider">Lucro bruto</h6>
                <h3 class="m-0"><?= formatMoney($dre['lucro_bruto'] ?? 0) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card glass-card <?= ($dre['resultado_liquido'] ?? 0) >= 0 ? 'border-info' : 'border-warning' ?> h-100">
            <div class="card-body text-center">
                <h6 class="<?= ($dre['resultado_liquido'] ?? 0) >= 0 ? 'text-info' : 'text-warning' ?> mb-2 uppercase tracking-wider">Resultado liquido</h6>
                <h3 class="m-0"><?= formatMoney($dre['resultado_liquido'] ?? 0) ?></h3>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-xl-6">
        <div class="card glass-card h-100">
            <div class="card-header bg-transparent border-0 pb-0">
                <h5 class="mb-0">Estrutura da DRE</h5>
            </div>
            <div class="card-body">
                <div class="dre-line">
                    <span>Receita bruta de OS</span>
                    <strong><?= formatMoney($receita['receita_bruta'] ?? 0) ?></strong>
                </div>
                <div class="dre-line text-danger">
                    <span>(-) Descontos concedidos</span>
                    <strong><?= formatMoney($receita['descontos'] ?? 0) ?></strong>
                </div>
                <div class="dre-line fw-bold border-top pt-3 mt-3">
                    <span>= Receita liquida</span>
                    <strong><?= formatMoney($receita['receita_liquida'] ?? 0) ?></strong>
                </div>
                <div class="dre-line text-danger">
                    <span>(-) Custo de pecas consumidas</span>
                    <strong><?= formatMoney($custos['pecas'] ?? 0) ?></strong>
                </div>
                <div class="dre-line text-danger">
                    <span>(-) Custo direto de servicos</span>
                    <strong><?= formatMoney($custos['servicos'] ?? 0) ?></strong>
                </div>
                <div class="dre-line fw-bold border-top pt-3 mt-3">
                    <span>= Lucro bruto</span>
                    <strong><?= formatMoney($dre['lucro_bruto'] ?? 0) ?></strong>
                </div>
                <div class="dre-line text-success">
                    <span>(+) Outras receitas</span>
                    <strong><?= formatMoney($dre['outras_receitas_total'] ?? 0) ?></strong>
                </div>
                <div class="dre-line text-danger">
                    <span>(-) Despesas operacionais</span>
                    <strong><?= formatMoney($dre['despesas_operacionais_total'] ?? 0) ?></strong>
                </div>
                <div class="dre-line fw-bold border-top pt-3 mt-3">
                    <span>= Resultado liquido gerencial</span>
                    <strong><?= formatMoney($dre['resultado_liquido'] ?? 0) ?></strong>
                </div>
                <div class="small text-muted mt-3">OS consideradas no periodo: <?= (int) ($receita['total_os'] ?? 0) ?></div>
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="card glass-card mb-4">
            <div class="card-header bg-transparent border-0 pb-0">
                <h5 class="mb-0">Outras receitas</h5>
            </div>
            <div class="card-body">
                <?php if (! empty($outrasReceitas)): ?>
                    <div class="table-responsive">
                        <table class="table stack-table">
                            <thead>
                                <tr>
                                    <th>Subgrupo</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($outrasReceitas as $item): ?>
                                <tr>
                                    <td data-label="Subgrupo"><?= esc($item['label']) ?></td>
                                    <td data-label="Total" class="text-end"><?= formatMoney($item['total']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0">Nao houve outras receitas classificadas no periodo.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="card glass-card">
            <div class="card-header bg-transparent border-0 pb-0">
                <h5 class="mb-0">Despesas operacionais</h5>
            </div>
            <div class="card-body">
                <?php if (! empty($despesas)): ?>
                    <div class="table-responsive">
                        <table class="table stack-table">
                            <thead>
                                <tr>
                                    <th>Subgrupo</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($despesas as $item): ?>
                                <tr>
                                    <td data-label="Subgrupo"><?= esc($item['label']) ?></td>
                                    <td data-label="Total" class="text-end"><?= formatMoney($item['total']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0">Nao houve despesas operacionais classificadas no periodo.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<style>
.dre-line {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    padding: 0.65rem 0;
    border-bottom: 1px solid rgba(148, 163, 184, 0.16);
}

.dre-line:last-child {
    border-bottom: 0;
}

@media (max-width: 575.98px) {
    .dre-line {
        flex-direction: column;
        align-items: flex-start;
    }

    .stack-table thead {
        display: none;
    }

    .stack-table,
    .stack-table tbody,
    .stack-table tr,
    .stack-table td {
        display: block;
        width: 100%;
    }

    .stack-table tr {
        margin-bottom: 1rem;
        padding: 1rem;
        border: 1px solid rgba(148, 163, 184, 0.25);
        border-radius: 1rem;
        background: rgba(255, 255, 255, 0.82);
    }

    .stack-table td {
        border: 0;
        padding: 0.35rem 0;
        text-align: left !important;
    }

    .stack-table td::before {
        content: attr(data-label);
        display: block;
        margin-bottom: 0.25rem;
        color: #64748b;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.02em;
        text-transform: uppercase;
    }
}
</style>
<?= $this->endSection() ?>
