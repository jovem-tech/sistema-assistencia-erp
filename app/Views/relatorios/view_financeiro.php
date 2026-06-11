<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$resumoCategorias = $resumo_por_categoria ?? [];
$categoriasReceita = array_values(array_filter($resumoCategorias, static fn (array $item): bool => ($item['tipo'] ?? '') === 'receber'));
$categoriasDespesa = array_values(array_filter($resumoCategorias, static fn (array $item): bool => ($item['tipo'] ?? '') === 'pagar'));
?>

<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h2><i class="bi bi-journal-text me-2"></i><?= esc($title) ?></h2>
    <div class="d-flex flex-wrap gap-2">
        <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('relatorios')" title="Ajuda sobre Relatorios">
            <i class="bi bi-question-circle me-1"></i>Ajuda
        </button>
        <a href="<?= base_url('relatorios') ?>" class="btn btn-outline-secondary" data-back-default="<?= base_url('relatorios') ?>">
            <i class="bi bi-arrow-left me-1"></i>Voltar
        </a>
    </div>
</div>

<div class="card glass-card mb-4">
    <div class="card-body">
        <form method="GET" action="<?= base_url('relatorios/financeiro') ?>" class="row g-3">
            <div class="col-md-5">
                <label for="mes" class="form-label">Mes/ano</label>
                <input type="month" class="form-control" id="mes" name="mes" value="<?= esc($filtro_mes) ?>">
            </div>
            <div class="col-md-7 d-flex align-items-end gap-2 flex-wrap">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-filter me-1"></i>Filtrar
                </button>
                <a href="<?= base_url('relatorios/financeiro?mes=' . urlencode((string) $filtro_mes) . '&print=1') ?>" target="_blank" class="btn btn-success">
                    <i class="bi bi-printer me-1"></i>Imprimir
                </a>
                <a href="<?= base_url('relatorios/dre?mes=' . urlencode((string) $filtro_mes)) ?>" class="btn btn-outline-info">
                    <i class="bi bi-bar-chart me-1"></i>Ver DRE
                </a>
                <a href="<?= base_url('relatorios/fluxo-caixa?mes=' . urlencode((string) $filtro_mes)) ?>" class="btn btn-outline-warning">
                    <i class="bi bi-graph-up-arrow me-1"></i>Ver fluxo
                </a>
            </div>
        </form>
    </div>
</div>

<div class="alert alert-light border shadow-sm mb-4">
    <strong>Este relatorio e operacional.</strong>
    <span class="d-block mt-1">Os cards abaixo mostram caixa realizado no mes. A tabela detalha o `valor do titulo`, o `ja quitado` e o `saldo em aberto`, inclusive quando houve baixas parciais. A composicao por categoria segue o catalogo configurado em `Financas -> Configuracoes`.</span>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card glass-card border-success h-100">
            <div class="card-body text-center">
                <h6 class="text-success mb-2 uppercase tracking-wider">Entradas realizadas</h6>
                <h3 class="text-body m-0"><?= formatMoney($resumo['receitas'] ?? 0) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card glass-card border-danger h-100">
            <div class="card-body text-center">
                <h6 class="text-danger mb-2 uppercase tracking-wider">Saidas realizadas</h6>
                <h3 class="text-body m-0"><?= formatMoney($resumo['despesas'] ?? 0) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card glass-card <?= ($resumo['resultado_caixa'] ?? 0) >= 0 ? 'border-primary' : 'border-warning' ?> h-100">
            <div class="card-body text-center">
                <h6 class="<?= ($resumo['resultado_caixa'] ?? 0) >= 0 ? 'text-primary' : 'text-warning' ?> mb-2 uppercase tracking-wider">Resultado de caixa</h6>
                <h3 class="text-body m-0"><?= formatMoney($resumo['resultado_caixa'] ?? $resumo['lucro'] ?? 0) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card glass-card border-secondary h-100">
            <div class="card-body text-center">
                <h6 class="text-secondary mb-2 uppercase tracking-wider">Saldo final do mes</h6>
                <h3 class="text-body m-0"><?= formatMoney($resumo['saldo_final'] ?? 0) ?></h3>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-6">
        <div class="card glass-card h-100">
            <div class="card-header bg-transparent border-0 pb-0">
                <h5 class="mb-0">Entradas por categoria</h5>
            </div>
            <div class="card-body">
                <?php if (! empty($categoriasReceita)): ?>
                    <div class="operational-summary-list">
                        <?php foreach ($categoriasReceita as $item): ?>
                            <div class="operational-summary-item">
                                <div>
                                    <div class="fw-semibold"><?= esc($item['categoria'] ?? '-') ?></div>
                                    <div class="small text-muted">
                                        <?= esc($item['grupo_dre'] ?? '-') ?>
                                        <span class="mx-1">/</span>
                                        <?= esc($item['subgrupo_dre'] ?? '-') ?>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="fw-semibold text-success"><?= formatMoney($item['total'] ?? 0) ?></div>
                                    <div class="small text-muted"><?= (int) ($item['quantidade'] ?? 0) ?> titulo(s)</div>
                                    <div class="small text-success">Quitado <?= formatMoney($item['quitado_total'] ?? 0) ?></div>
                                    <div class="small text-danger">Aberto <?= formatMoney($item['aberto_total'] ?? 0) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0">Nenhuma entrada cadastrada para o periodo.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="card glass-card h-100">
            <div class="card-header bg-transparent border-0 pb-0">
                <h5 class="mb-0">Saidas por categoria</h5>
            </div>
            <div class="card-body">
                <?php if (! empty($categoriasDespesa)): ?>
                    <div class="operational-summary-list">
                        <?php foreach ($categoriasDespesa as $item): ?>
                            <div class="operational-summary-item">
                                <div>
                                    <div class="fw-semibold"><?= esc($item['categoria'] ?? '-') ?></div>
                                    <div class="small text-muted">
                                        <?= esc($item['grupo_dre'] ?? '-') ?>
                                        <span class="mx-1">/</span>
                                        <?= esc($item['subgrupo_dre'] ?? '-') ?>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="fw-semibold text-danger"><?= formatMoney($item['total'] ?? 0) ?></div>
                                    <div class="small text-muted"><?= (int) ($item['quantidade'] ?? 0) ?> titulo(s)</div>
                                    <div class="small text-success">Quitado <?= formatMoney($item['quitado_total'] ?? 0) ?></div>
                                    <div class="small text-danger">Aberto <?= formatMoney($item['aberto_total'] ?? 0) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0">Nenhuma saida cadastrada para o periodo.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card glass-card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover datatable stack-table">
                        <thead>
                            <tr>
                                <th>Descricao</th>
                                <th>Tipo</th>
                                <th>Competencia</th>
                                <th>Vencimento</th>
                                <th>Ultima baixa</th>
                                <th>Valor</th>
                                <th>Status</th>
                                <th>Classificacao</th>
                            </tr>
                </thead>
                <tbody>
                    <?php if (! empty($lancamentos)): ?>
                        <?php foreach ($lancamentos as $l): ?>
                        <tr>
                            <td data-label="Descricao">
                                <div class="fw-semibold"><?= esc($l['descricao'] ?? '-') ?></div>
                                <div class="small text-muted">
                                    <?= esc($l['categoria_exibicao'] ?? $l['categoria'] ?? '-') ?>
                                    <?php if (! empty($l['numero_os'])): ?>
                                        <span class="ms-1">OS <?= esc($l['numero_os']) ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="small mt-1 d-flex flex-wrap gap-1">
                                    <span class="badge text-bg-light"><?= ($l['categoria_configurada'] ?? 0) == 1 ? 'Catalogada' : 'Legado/manual' ?></span>
                                    <span class="badge text-bg-light">Origem <?= esc($l['origem_tipo_label_resolvido'] ?? $l['origem_tipo_resolvido'] ?? '-') ?></span>
                                </div>
                            </td>
                            <td data-label="Tipo">
                                <?php if (($l['tipo'] ?? '') === 'receber'): ?>
                                    <span class="text-success"><i class="bi bi-arrow-up-right-circle me-1"></i>Receita</span>
                                <?php else: ?>
                                    <span class="text-danger"><i class="bi bi-arrow-down-left-circle me-1"></i>Despesa</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Competencia"><?= formatCompetenceDate($l['data_competencia_resolvida'] ?? $l['data_competencia'] ?? '', $l['origem_tipo_resolvido'] ?? $l['origem_tipo'] ?? null) ?></td>
                            <td data-label="Vencimento"><?= formatDate($l['data_vencimento'] ?? '') ?></td>
                            <td data-label="Ultima baixa"><?= formatDate($l['data_pagamento_resolvida'] ?? $l['data_pagamento'] ?? '') ?></td>
                            <td data-label="Valor" class="font-monospace">
                                <div class="fw-semibold"><?= formatMoney($l['valor_titulo'] ?? $l['valor'] ?? 0) ?></div>
                                <div class="small text-success">Quitado <?= formatMoney($l['valor_movimentado'] ?? 0) ?></div>
                                <div class="small text-danger">Aberto <?= formatMoney($l['valor_aberto'] ?? 0) ?></div>
                            </td>
                            <td data-label="Status">
                                <?php if (($l['status_resolvido'] ?? $l['status'] ?? '') === 'pago'): ?>
                                    <span class="badge bg-success">Pago</span>
                                <?php elseif (($l['status_resolvido'] ?? $l['status'] ?? '') === 'cancelado'): ?>
                                    <span class="badge bg-secondary">Cancelado</span>
                                <?php elseif (($l['status_resolvido'] ?? $l['status'] ?? '') === 'parcial'): ?>
                                    <span class="badge <?= ((int) ($l['esta_vencido'] ?? 0)) === 1 ? 'bg-danger' : 'bg-warning text-dark' ?>">
                                        <?= ((int) ($l['esta_vencido'] ?? 0)) === 1 ? 'Parcial vencido' : 'Parcial' ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge <?= ((int) ($l['esta_vencido'] ?? 0)) === 1 ? 'bg-danger' : 'bg-warning text-dark' ?>">
                                        <?= ((int) ($l['esta_vencido'] ?? 0)) === 1 ? 'Vencido' : 'Pendente' ?>
                                    </span>
                                <?php endif; ?>
                                <div class="small text-muted mt-1"><?= (int) ($l['total_movimentos'] ?? 0) ?> baixa(s)</div>
                            </td>
                            <td data-label="Classificacao">
                                <div class="fw-semibold"><?= esc($l['grupo_dre_resolvido'] ?? $l['grupo_dre'] ?? '-') ?></div>
                                <div class="small text-muted"><?= esc($l['subgrupo_dre_resolvido'] ?? $l['subgrupo_dre'] ?? '-') ?></div>
                                <div class="small mt-1 d-flex flex-wrap gap-1">
                                    <span class="badge text-bg-light">DRE <?= ((int) ($l['impacta_dre_resolvido'] ?? 1)) === 1 ? 'sim' : 'nao' ?></span>
                                    <span class="badge text-bg-light">Caixa <?= ((int) ($l['impacta_fluxo_caixa_resolvido'] ?? 1)) === 1 ? 'sim' : 'nao' ?></span>
                                    <?php if (((int) ($l['dre_fixo_mensal_resolvido'] ?? 0)) === 1): ?>
                                        <span class="badge text-bg-info">Fixa DRE</span>
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

<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<style>
.operational-summary-list {
    display: flex;
    flex-direction: column;
    gap: 0.9rem;
}

.operational-summary-item {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    padding-bottom: 0.9rem;
    border-bottom: 1px solid rgba(148, 163, 184, 0.16);
}

.operational-summary-item:last-child {
    border-bottom: 0;
    padding-bottom: 0;
}

@media (max-width: 575.98px) {
    .operational-summary-item {
        flex-direction: column;
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
