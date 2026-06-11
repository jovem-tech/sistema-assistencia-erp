<?php
$truncateText = static function (?string $value, int $limit = 110): string {
    $text = trim((string) $value);
    if ($text === '') {
        return '';
    }

    $text = preg_replace('/\s+/', ' ', $text) ?? $text;
    if ($text === '') {
        return '';
    }

    if (function_exists('mb_strimwidth')) {
        return mb_strimwidth($text, 0, $limit, '...');
    }

    if (strlen($text) <= $limit) {
        return $text;
    }

    return substr($text, 0, max(0, $limit - 3)) . '...';
};

$buildResumoLancamento = static function (array $lancamento) use ($truncateText): string {
    $blocos = [
        $lancamento['os_solucao_aplicada'] ?? null,
        $lancamento['os_procedimentos_executados'] ?? null,
        $lancamento['os_diagnostico_tecnico'] ?? null,
        $lancamento['os_relato_cliente'] ?? null,
        $lancamento['observacoes'] ?? null,
    ];

    foreach ($blocos as $bloco) {
        $resumo = $truncateText(is_string($bloco) ? $bloco : null, 130);
        if ($resumo !== '') {
            return $resumo;
        }
    }

    return '';
};

$buildFinanceiroFilterUrl = static function (array $overrides = []) use ($filtro_tipo, $filtro_status, $filtro_dre_fixo_mensal): string {
    $params = [
        'tipo' => $filtro_tipo !== 'todos' ? $filtro_tipo : null,
        'status' => $filtro_status !== 'todos' ? $filtro_status : null,
        'dre_fixo_mensal' => $filtro_dre_fixo_mensal === '1' ? '1' : null,
    ];

    foreach ($overrides as $key => $value) {
        $params[$key] = $value;
    }

    $params = array_filter($params, static function ($value): bool {
        return $value !== null && $value !== '' && $value !== 'todos';
    });

    $query = http_build_query($params);

    return base_url('financeiro' . ($query !== '' ? '?' . $query : ''));
};
?>

<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="financeiro-page">
<div class="page-header">
    <h2><i class="bi bi-currency-dollar me-2"></i>Financeiro</h2>
        <div class="d-flex flex-wrap gap-2 align-items-center justify-content-end finance-header-actions">
        <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('financeiro')" title="Ajuda sobre Financeiro">
            <i class="bi bi-question-circle me-1"></i>Ajuda
        </button>
        <a href="<?= base_url('financeiro/cartoes') ?>" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-credit-card-2-front me-1"></i>Cartões e taxas
        </a>
        <a href="<?= base_url('relatorios/dre') ?>" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-bar-chart-line me-1"></i>DRE
        </a>
        <a href="<?= base_url('relatorios/fluxo-caixa') ?>" class="btn btn-sm btn-outline-success">
            <i class="bi bi-graph-up-arrow me-1"></i>Fluxo de Caixa
        </a>
        <?php if (can('financeiro', 'visualizar')): ?>
        <a href="<?= base_url('financeiro/configuracoes') ?>" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-sliders me-1"></i>Configuracoes
        </a>
        <?php endif; ?>
        <?php if (can('financeiro', 'criar')): ?>
        <a href="<?= base_url('financeiro/novo') ?>" class="btn btn-glow finance-create-btn" title="Novo lancamento">
            <span class="finance-create-plus" aria-hidden="true">+</span>
            <span class="finance-create-label">Novo lancamento</span>
        </a>
        <?php endif; ?>
    </div>
</div>

<div class="alert alert-info border-0 shadow-sm mb-4">
    <div class="d-flex flex-column gap-1">
        <strong>Visao operacional e visao gerencial agora coexistem.</strong>
        <span>Os cards abaixo mostram caixa realizado. A analise de resultado por competencia fica na DRE e a liquidez projetada fica no Fluxo de Caixa.</span>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="stat-card stat-card-success">
            <div class="stat-card-body">
                <div class="stat-info">
                    <span class="stat-label">Entradas realizadas</span>
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
                    <span class="stat-label">Saidas realizadas</span>
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
                    <span class="stat-label">Resultado de caixa</span>
                    <h2 class="stat-value" style="font-size:20px"><?= formatMoney($resumo['resultado_caixa'] ?? $resumo['lucro'] ?? 0) ?></h2>
                </div>
                <div class="stat-icon"><i class="bi bi-wallet2"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card stat-card-warning">
            <div class="stat-card-body">
                <div class="stat-info">
                    <span class="stat-label">Recebimentos pendentes</span>
                    <h2 class="stat-value" style="font-size:20px"><?= formatMoney($resumo['pendentes'] ?? 0) ?></h2>
                </div>
                <div class="stat-icon"><i class="bi bi-exclamation-circle"></i></div>
            </div>
        </div>
    </div>
</div>

<div class="card glass-card mb-4">
    <div class="card-body">
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <a href="<?= $buildFinanceiroFilterUrl(['tipo' => null, 'status' => null, 'dre_fixo_mensal' => null]) ?>" class="btn btn-sm <?= $filtro_tipo === 'todos' && $filtro_status === 'todos' && $filtro_dre_fixo_mensal !== '1' ? 'btn-glow' : 'btn-outline-secondary' ?>">Todos</a>
            <a href="<?= $buildFinanceiroFilterUrl(['tipo' => 'receber', 'dre_fixo_mensal' => null]) ?>" class="btn btn-sm <?= $filtro_tipo === 'receber' && $filtro_dre_fixo_mensal !== '1' ? 'btn-success' : 'btn-outline-secondary' ?>">A receber</a>
            <a href="<?= $buildFinanceiroFilterUrl(['tipo' => 'pagar', 'dre_fixo_mensal' => null]) ?>" class="btn btn-sm <?= $filtro_tipo === 'pagar' && $filtro_dre_fixo_mensal !== '1' ? 'btn-danger' : 'btn-outline-secondary' ?>">A pagar</a>
            <a href="<?= $buildFinanceiroFilterUrl(['tipo' => 'pagar', 'dre_fixo_mensal' => '1']) ?>" class="btn btn-sm <?= $filtro_dre_fixo_mensal === '1' ? 'btn-info' : 'btn-outline-info' ?>">Despesas fixas</a>
            <span class="mx-2 border-start border-secondary d-none d-md-inline-block" style="height: 1.6rem;"></span>
            <a href="<?= $buildFinanceiroFilterUrl(['status' => 'pendente']) ?>" class="btn btn-sm <?= $filtro_status === 'pendente' ? 'btn-warning' : 'btn-outline-secondary' ?>">Pendentes</a>
            <a href="<?= $buildFinanceiroFilterUrl(['status' => 'parcial']) ?>" class="btn btn-sm <?= $filtro_status === 'parcial' ? 'btn-warning' : 'btn-outline-secondary' ?>">Parciais</a>
            <a href="<?= $buildFinanceiroFilterUrl(['status' => 'pago']) ?>" class="btn btn-sm <?= $filtro_status === 'pago' ? 'btn-success' : 'btn-outline-secondary' ?>">Pagos</a>
            <a href="<?= $buildFinanceiroFilterUrl(['status' => 'cancelado']) ?>" class="btn btn-sm <?= $filtro_status === 'cancelado' ? 'btn-secondary' : 'btn-outline-secondary' ?>">Cancelados</a>
        </div>
        <?php if ($filtro_dre_fixo_mensal === '1'): ?>
        <div class="small text-muted mt-2">
            Exibindo apenas lancamentos A pagar marcados como Despesa fixa mensal na DRE.
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="card glass-card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover datatable stack-table finance-table">
                <colgroup>
                    <col class="finance-col-id">
                    <col class="finance-col-type">
                    <col class="finance-col-description">
                    <col class="finance-col-competencia">
                    <col class="finance-col-value">
                    <col class="finance-col-status">
                    <col class="finance-col-classification">
                    <col class="finance-col-actions">
                </colgroup>
                <thead>
                    <tr>
                        <th class="finance-col-id">#</th>
                        <th class="finance-col-type">Tipo</th>
                        <th class="finance-col-description">Descricao</th>
                        <th class="finance-col-competencia">Competencia</th>
                        <th class="finance-col-value">Valor</th>
                        <th class="finance-col-status">Status</th>
                        <th class="finance-col-classification">Classificacao</th>
                        <th class="finance-col-actions">Acoes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (! empty($lancamentos)): ?>
                        <?php foreach ($lancamentos as $l): ?>
                            <?php
                            $isReceber = ($l['tipo'] ?? '') === 'receber';
                            $equipamentoLabel = equipamento_nome_exibicao($l);
                            $resumoLancamento = $buildResumoLancamento($l);
                            $tituloModal = ($isReceber ? 'Receita' : 'Despesa') . ' #' . (int) $l['id'];
                            $statusResolvido = (string) ($l['status_resolvido'] ?? $l['status'] ?? 'pendente');
                            $valorTitulo = (float) ($l['valor_titulo'] ?? $l['valor'] ?? 0);
                            $valorMovimentado = (float) ($l['valor_movimentado'] ?? 0);
                            $valorAberto = (float) ($l['valor_aberto'] ?? max(0, $valorTitulo - $valorMovimentado));
                            $podeRegistrarBaixa = can('financeiro', 'editar')
                                && in_array($statusResolvido, ['pendente', 'parcial'], true)
                                && ((int) ($l['impacta_fluxo_caixa_resolvido'] ?? $l['impacta_fluxo_caixa'] ?? 1)) === 1
                                && $valorAberto > 0;
                            ?>
                        <tr>
                            <td data-label="#"><?= (int) $l['id'] ?></td>
                            <td data-label="Tipo">
                                <button
                                    type="button"
                                    class="finance-type-pill finance-detail-trigger <?= $isReceber ? 'finance-type-pill-success' : 'finance-type-pill-danger' ?>"
                                    data-id="<?= (int) $l['id'] ?>"
                                    data-title="<?= esc($tituloModal) ?>"
                                    title="Visualizar detalhes"
                                >
                                    <?= $isReceber ? 'Receber' : 'Pagar' ?>
                                </button>
                            </td>
                            <td data-label="Descricao">
                                <button
                                    type="button"
                                    class="finance-description-trigger finance-detail-trigger"
                                    data-id="<?= (int) $l['id'] ?>"
                                    data-title="<?= esc($tituloModal) ?>"
                                    title="Visualizar detalhes do lancamento"
                                >
                                    <div class="finance-description-header">
                                        <span class="finance-description-title"><?= esc($l['descricao'] ?? '-') ?></span>
                                        <span class="finance-description-chip">Ver detalhes</span>
                                    </div>

                                    <div class="finance-description-line text-muted">
                                        <span><?= esc($l['categoria'] ?? '-') ?></span>
                                        <?php if (! empty($l['numero_os'])): ?>
                                            <span class="finance-description-divider">|</span>
                                            <span>OS <?= esc($l['numero_os']) ?></span>
                                        <?php endif; ?>
                                    </div>

                                    <?php if (! empty($l['cliente_nome'])): ?>
                                    <div class="finance-description-line finance-description-context">
                                        <i class="bi bi-person"></i>
                                        <span><?= esc($truncateText($l['cliente_nome'] ?? null, 55)) ?></span>
                                    </div>
                                    <?php endif; ?>

                                    <?php if (! $isReceber && ! empty($l['fornecedor_nome'])): ?>
                                    <div class="finance-description-line finance-description-context">
                                        <i class="bi bi-truck"></i>
                                        <span><?= esc($truncateText($l['fornecedor_nome'] ?? null, 55)) ?></span>
                                    </div>
                                    <?php endif; ?>

                                    <?php if ($equipamentoLabel !== ''): ?>
                                    <div class="finance-description-line finance-description-context">
                                        <i class="bi bi-phone"></i>
                                        <span><?= esc($truncateText($equipamentoLabel, 65)) ?></span>
                                    </div>
                                    <?php endif; ?>

                                    <?php if ($resumoLancamento !== ''): ?>
                                    <div class="finance-description-summary">
                                        <?= esc($resumoLancamento) ?>
                                    </div>
                                    <?php endif; ?>
                                </button>
                            </td>
                            <td data-label="Competencia">
                                <div><?= formatCompetenceDate($l['data_competencia'] ?? '', $l['origem_tipo_resolvido'] ?? $l['origem_tipo'] ?? null) ?></div>
                                <div class="small text-muted">Venc. <?= formatDate($l['data_vencimento'] ?? '') ?></div>
                                <div class="small text-muted">Ult. baixa <?= formatDate($l['data_pagamento_resolvida'] ?? $l['data_pagamento'] ?? '') ?></div>
                            </td>
                            <td data-label="Valor">
                                <div class="fw-semibold"><?= formatMoney($valorTitulo) ?></div>
                                <div class="small text-success">Quitado <?= formatMoney($valorMovimentado) ?></div>
                                <div class="small <?= $valorAberto > 0 ? 'text-danger' : 'text-muted' ?>">Em aberto <?= formatMoney($valorAberto) ?></div>
                            </td>
                            <td data-label="Status">
                                <?php if ($statusResolvido === 'pago'): ?>
                                    <span class="badge bg-success">Pago</span>
                                <?php elseif ($statusResolvido === 'cancelado'): ?>
                                    <span class="badge bg-secondary">Cancelado</span>
                                <?php elseif ($statusResolvido === 'parcial'): ?>
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
                                <div class="fw-semibold"><?= esc($l['grupo_dre'] ?? 'Automatico') ?></div>
                                <div class="small text-muted"><?= esc($l['subgrupo_dre'] ?? '-') ?></div>
                                <div class="small mt-1">
                                    <span class="badge text-bg-light">DRE <?= ((int) ($l['impacta_dre'] ?? 1)) === 1 ? 'sim' : 'nao' ?></span>
                                    <span class="badge text-bg-light">Caixa <?= ((int) ($l['impacta_fluxo_caixa'] ?? 1)) === 1 ? 'sim' : 'nao' ?></span>
                                    <?php if ((int) ($l['dre_fixo_mensal'] ?? 0) === 1): ?>
                                    <span class="badge text-bg-info">Fixa mensal DRE</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td data-label="Acoes">
                                <div class="action-btns">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-primary finance-detail-trigger"
                                        data-id="<?= (int) $l['id'] ?>"
                                        data-title="<?= esc($tituloModal) ?>"
                                        title="Visualizar detalhes"
                                    >
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <?php if ($podeRegistrarBaixa): ?>
                                    <button
                                        class="btn btn-sm btn-outline-success"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalBaixa"
                                        data-finance-baixa="1"
                                        data-id="<?= (int) $l['id'] ?>"
                                        data-descricao="<?= esc($l['descricao'] ?? '-') ?>"
                                        data-tipo="<?= $isReceber ? 'recebimento' : 'pagamento' ?>"
                                        data-valor-titulo="<?= esc(number_format($valorTitulo, 2, '.', '')) ?>"
                                        data-valor-aberto="<?= esc(number_format($valorAberto, 2, '.', '')) ?>"
                                        data-valor-movimentado="<?= esc(number_format($valorMovimentado, 2, '.', '')) ?>"
                                        title="Registrar baixa"
                                    >
                                        <i class="bi bi-check-lg"></i>
                                    </button>
                                    <?php endif; ?>
                                    <?php if (can('financeiro', 'editar')): ?>
                                    <a href="<?= base_url('financeiro/editar/' . (int) $l['id']) ?>" class="btn btn-sm btn-outline-secondary" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?php endif; ?>
                                    <?php if (can('financeiro', 'encerrar')): ?>
                                    <a href="javascript:void(0)" class="btn btn-sm btn-outline-warning" title="Encerrar" onclick="confirmarEncerramento('financeiro', <?= (int) $l['id'] ?>)">
                                        <i class="bi bi-archive"></i>
                                    </a>
                                    <?php endif; ?>
                                    <?php if (can('financeiro', 'excluir')): ?>
                                    <a href="<?= base_url('financeiro/excluir/' . (int) $l['id']) ?>" class="btn btn-sm btn-outline-danger btn-delete" data-nome="<?= esc($l['descricao'] ?? '') ?>" title="Excluir">
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

<div class="modal fade" id="modalDetalheLancamento" tabindex="-1" aria-labelledby="financeDetailTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable finance-detail-modal">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header">
                <h5 class="modal-title" id="financeDetailTitle">Detalhes do lancamento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body" id="financeDetailBody">
                <div class="finance-detail-loading">
                    <div class="spinner-border text-primary" role="status" aria-hidden="true"></div>
                    <p class="mb-0">Carregando detalhamento...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalBaixa" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalBaixaTitle">Registrar baixa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form id="formBaixa" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id" id="baixa_id">
                    <input type="hidden" name="impacta_fluxo_caixa" value="1">
                    <div class="finance-baixa-context" id="baixa_contexto">
                        Selecione o valor e a data desta baixa.
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Valor desta baixa</label>
                        <input type="number" step="0.01" min="0.01" name="valor_movimento" id="baixa_valor_movimento" class="form-control" required>
                        <div class="form-text">O sistema aceita baixas parciais e recalcula o saldo em aberto automaticamente.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Data da baixa</label>
                        <input type="date" name="data_pagamento" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Forma de pagamento</label>
                        <select name="forma_pagamento" class="form-select">
                            <option value="dinheiro">Dinheiro</option>
                            <option value="pix">PIX</option>
                            <option value="cartao_credito">Cartao credito</option>
                            <option value="cartao_debito">Cartao debito</option>
                            <option value="boleto">Boleto</option>
                            <option value="transferencia">Transferencia</option>
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Observacoes da baixa</label>
                        <textarea name="observacoes_movimento" class="form-control" rows="2" placeholder="Opcional: comprovante, parcela, contexto da conciliacao"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-glow" id="baixaSubmitBtn">Confirmar baixa</button>
                </div>
            </form>
        </div>
    </div>
</div>

    </div>

<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<style>
.finance-table td {
    vertical-align: middle;
}

.finance-table {
    width: 100% !important;
    table-layout: fixed;
}

.finance-table th,
.finance-table td {
    word-break: normal;
    overflow-wrap: normal;
}

.finance-table col.finance-col-id,
.finance-table th.finance-col-id {
    width: 5%;
}

.finance-table col.finance-col-type,
.finance-table th.finance-col-type {
    width: 8%;
}

.finance-table col.finance-col-description,
.finance-table th.finance-col-description {
    width: 31%;
}

.finance-table col.finance-col-competencia,
.finance-table th.finance-col-competencia {
    width: 14%;
}

.finance-table col.finance-col-value,
.finance-table th.finance-col-value {
    width: 11%;
}

.finance-table col.finance-col-status,
.finance-table th.finance-col-status {
    width: 10%;
}

.finance-table col.finance-col-classification,
.finance-table th.finance-col-classification {
    width: 12.5%;
}

.finance-table col.finance-col-actions,
.finance-table th.finance-col-actions {
    width: 8%;
}

.finance-table td[data-label="Descricao"] {
    width: 31%;
    min-width: 0;
}

.finance-table td[data-label="Classificacao"] {
    width: 12.5%;
    min-width: 0;
}

.finance-table td[data-label="Acoes"] {
    width: 8%;
    min-width: 0;
}

.finance-table td[data-label="Acoes"] .action-btns {
    flex-wrap: wrap;
    justify-content: flex-start;
    gap: 0.35rem;
    max-width: 100%;
}

.finance-table td[data-label="Acoes"] .action-btns .btn {
    flex: 0 0 auto;
}

.financeiro-page .page-header {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
}

.financeiro-page .finance-header-actions {
    min-width: 0;
    margin-left: auto;
}

.financeiro-page .finance-header-actions .btn {
    white-space: nowrap;
}

.financeiro-page .finance-create-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.35rem;
}

.financeiro-page .finance-create-plus {
    font-size: 1.05rem;
    line-height: 1;
    font-weight: 800;
}

.financeiro-page .finance-create-label {
    white-space: nowrap;
}

.finance-type-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 92px;
    padding: 0.38rem 0.85rem;
    border: 0;
    border-radius: 999px;
    font-size: 0.78rem;
    font-weight: 700;
    line-height: 1;
    color: #fff;
    transition: transform 0.18s ease, box-shadow 0.18s ease, opacity 0.18s ease;
}

.finance-type-pill:hover,
.finance-type-pill:focus-visible {
    transform: translateY(-1px);
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.16);
    opacity: 0.96;
}

.finance-type-pill-success {
    background: linear-gradient(135deg, #16a34a, #22c55e);
}

.finance-type-pill-danger {
    background: linear-gradient(135deg, #dc2626, #f43f5e);
}

.finance-description-trigger {
    display: block;
    width: 100%;
    min-width: 0;
    max-width: 100%;
    padding: 0.9rem 1rem;
    border: 1px solid rgba(148, 163, 184, 0.22);
    border-radius: 1rem;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.96), rgba(248, 250, 252, 0.9));
    text-align: left;
    overflow: hidden;
    transition: transform 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease;
}

.finance-description-trigger:hover,
.finance-description-trigger:focus-visible {
    border-color: rgba(99, 102, 241, 0.38);
    box-shadow: 0 14px 30px rgba(99, 102, 241, 0.08);
    transform: translateY(-1px);
}

.finance-description-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 0.75rem;
    margin-bottom: 0.35rem;
}

.finance-description-title {
    display: block;
    flex: 1 1 auto;
    min-width: 0;
    font-weight: 700;
    color: #0f172a;
    white-space: normal;
    word-break: normal;
    overflow-wrap: break-word;
}

.finance-description-chip {
    flex: 0 0 auto;
    max-width: 100%;
    padding: 0.18rem 0.55rem;
    border-radius: 999px;
    background: rgba(99, 102, 241, 0.1);
    color: #4f46e5;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.02em;
}

.finance-description-line {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
    align-items: center;
    font-size: 0.8rem;
    margin-bottom: 0.25rem;
    white-space: normal;
    word-break: normal;
    overflow-wrap: break-word;
}

.finance-description-divider {
    color: #94a3b8;
}

.finance-description-context {
    color: #334155;
}

.finance-description-context i {
    color: #6366f1;
}

.finance-description-summary {
    margin-top: 0.45rem;
    color: #475569;
    font-size: 0.82rem;
    line-height: 1.45;
    white-space: normal;
    word-break: normal;
    overflow-wrap: break-word;
}

.finance-detail-modal {
    max-width: 1180px;
}

.finance-detail-loading {
    min-height: 220px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.9rem;
    color: #475569;
}

.finance-baixa-context {
    margin-bottom: 1rem;
    padding: 0.9rem 1rem;
    border-radius: 1rem;
    background: rgba(99, 102, 241, 0.08);
    color: #334155;
    font-size: 0.92rem;
    line-height: 1.45;
}

.finance-detail-layout {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.finance-detail-hero {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    gap: 1rem;
    padding: 1.1rem 1.25rem;
    border-radius: 1.25rem;
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.08), rgba(14, 165, 233, 0.08));
}

.finance-detail-amount {
    font-size: clamp(1.45rem, 2vw, 2rem);
    font-weight: 800;
    line-height: 1;
}

.finance-detail-card {
    border-radius: 1.15rem;
}

.finance-detail-box {
    padding: 0.9rem 1rem;
    border-radius: 1rem;
    background: rgba(255, 255, 255, 0.9);
    border: 1px solid rgba(148, 163, 184, 0.16);
}

.finance-detail-pair {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    padding: 0.45rem 0;
    border-bottom: 1px dashed rgba(148, 163, 184, 0.25);
    font-size: 0.92rem;
}

.finance-detail-pair:last-child {
    border-bottom: 0;
}

.finance-detail-pair span {
    color: #64748b;
}

@media (max-width: 575.98px) {
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

    .finance-description-trigger {
        padding: 0.85rem 0.9rem;
        min-width: 0;
    }

    .finance-description-header {
        flex-direction: column;
        gap: 0.4rem;
    }

    .finance-description-chip {
        align-self: flex-start;
    }

    .finance-detail-modal {
        max-width: 100%;
        margin: 0.65rem;
    }

    .finance-detail-hero {
        padding: 1rem;
    }

    .finance-detail-pair {
        flex-direction: column;
        gap: 0.25rem;
    }

    .financeiro-page .page-header {
        align-items: stretch;
    }

    .financeiro-page .finance-header-actions {
        width: 100%;
        justify-content: flex-start;
    }

    .financeiro-page .finance-header-actions .btn {
        width: 100%;
    }

    .financeiro-page .finance-create-btn {
        width: 100%;
    }
}

@media (max-width: 991.98px) {
    .finance-table col.finance-col-id,
    .finance-table th.finance-col-id {
        width: 6%;
    }

    .finance-table col.finance-col-type,
    .finance-table th.finance-col-type {
        width: 9%;
    }

    .finance-table col.finance-col-description,
    .finance-table th.finance-col-description {
        width: 29%;
    }

    .finance-table col.finance-col-competencia,
    .finance-table th.finance-col-competencia {
        width: 14%;
    }

    .finance-table col.finance-col-value,
    .finance-table th.finance-col-value {
        width: 12%;
    }

    .finance-table col.finance-col-status,
    .finance-table th.finance-col-status {
        width: 10%;
    }

    .finance-table col.finance-col-classification,
    .finance-table th.finance-col-classification {
        width: 12%;
    }

    .finance-table col.finance-col-actions,
    .finance-table th.finance-col-actions {
        width: 8%;
    }

    .finance-table td[data-label="Descricao"] {
        width: 29%;
    }

    .finance-table td[data-label="Acoes"] {
        width: 8%;
    }

    .finance-table td[data-label="Classificacao"] {
        width: 12%;
    }

    .finance-description-header {
        flex-direction: column;
        gap: 0.4rem;
    }

    .finance-description-chip {
        align-self: flex-start;
    }
}

@media (max-width: 430px) {
    .finance-type-pill {
        min-width: 84px;
        padding: 0.34rem 0.72rem;
    }

    .finance-description-title {
        font-size: 0.95rem;
    }

    .financeiro-page .finance-create-label {
        display: none;
    }

    .financeiro-page .finance-create-btn {
        width: auto;
        min-width: 44px;
        padding-inline: 0.8rem;
    }
}

@media (max-width: 390px) {
    .finance-detail-modal .modal-content {
        border-radius: 1rem;
    }

    .finance-detail-box {
        padding: 0.8rem 0.85rem;
    }
}

@media (max-width: 360px) {
    .finance-description-trigger {
        padding: 0.75rem 0.78rem;
    }

    .finance-detail-hero {
        gap: 0.8rem;
    }
}

@media (max-width: 320px) {
    .finance-type-pill {
        min-width: 78px;
        font-size: 0.72rem;
    }

    .finance-description-summary,
    .finance-description-line {
        font-size: 0.76rem;
    }
}
</style>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
    const formBaixa = document.getElementById('formBaixa');
    const baixaIdField = document.getElementById('baixa_id');
    const baixaValorField = document.getElementById('baixa_valor_movimento');
    const baixaContexto = document.getElementById('baixa_contexto');
    const baixaTitle = document.getElementById('modalBaixaTitle');
    const baixaSubmitBtn = document.getElementById('baixaSubmitBtn');
    const modalElement = document.getElementById('modalDetalheLancamento');
    const modalTitle = document.getElementById('financeDetailTitle');
    const modalBody = document.getElementById('financeDetailBody');
    const detailBaseUrl = '<?= base_url('financeiro/detalhes') ?>';

    if (!modalElement || !modalTitle || !modalBody || typeof bootstrap === 'undefined') {
        return;
    }

    const detailModal = new bootstrap.Modal(modalElement);
    const loadingMarkup = `
        <div class="finance-detail-loading">
            <div class="spinner-border text-primary" role="status" aria-hidden="true"></div>
            <p class="mb-0">Carregando detalhamento...</p>
        </div>
    `;
    let financeTableResizeTimer = null;

    function adjustFinanceTableColumns() {
        if (!(window.jQuery && window.jQuery.fn && window.jQuery.fn.dataTable)) {
            return;
        }

        const tableElement = document.querySelector('.finance-table');
        if (!tableElement || !window.jQuery.fn.dataTable.isDataTable(tableElement)) {
            return;
        }

        window.jQuery(tableElement).DataTable().columns.adjust();
    }

    window.addEventListener('load', adjustFinanceTableColumns);

    if (document.fonts && typeof document.fonts.ready?.then === 'function') {
        document.fonts.ready.then(adjustFinanceTableColumns).catch(function () {
            // Mantem a grade operando normalmente mesmo se a API de fontes falhar.
        });
    }

    window.addEventListener('resize', function () {
        if (financeTableResizeTimer) {
            window.clearTimeout(financeTableResizeTimer);
        }

        financeTableResizeTimer = window.setTimeout(adjustFinanceTableColumns, 120);
    });

    if (formBaixa && baixaIdField && baixaValorField) {
        document.addEventListener('click', function (event) {
            const trigger = event.target.closest('[data-finance-baixa]');
            if (!trigger) {
                return;
            }

            const tituloId = trigger.getAttribute('data-id') || '';
            const descricao = trigger.getAttribute('data-descricao') || 'Titulo financeiro';
            const tipo = trigger.getAttribute('data-tipo') || 'baixa';
            const valorTitulo = Number(trigger.getAttribute('data-valor-titulo') || 0);
            const valorAberto = Number(trigger.getAttribute('data-valor-aberto') || 0);
            const valorMovimentado = Number(trigger.getAttribute('data-valor-movimentado') || 0);

            baixaIdField.value = tituloId;
            baixaValorField.max = valorAberto.toFixed(2);
            baixaValorField.value = valorAberto > 0 ? valorAberto.toFixed(2) : '';

            if (baixaTitle) {
                baixaTitle.textContent = `Registrar ${tipo}`;
            }

            if (baixaSubmitBtn) {
                baixaSubmitBtn.textContent = `Confirmar ${tipo}`;
            }

            if (baixaContexto) {
                baixaContexto.innerHTML = `
                    <strong>${descricao}</strong><br>
                    Valor do titulo: <strong>R$ ${valorTitulo.toFixed(2).replace('.', ',')}</strong>
                    <span class="mx-1">|</span>
                    Ja baixado: <strong>R$ ${valorMovimentado.toFixed(2).replace('.', ',')}</strong>
                    <span class="mx-1">|</span>
                    Em aberto: <strong>R$ ${valorAberto.toFixed(2).replace('.', ',')}</strong>
                `;
            }
        });

        formBaixa.addEventListener('submit', function (event) {
            event.preventDefault();

            const id = baixaIdField.value;
            const valor = Number(baixaValorField.value || 0);
            const maximo = Number(baixaValorField.max || 0);

            if (!id || valor <= 0) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Valor invalido',
                        text: 'Informe um valor valido para registrar a baixa.'
                    });
                }
                return;
            }

            if (maximo > 0 && valor > maximo + 0.001) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Valor acima do saldo',
                        text: 'O valor informado nao pode ser maior que o saldo em aberto do titulo.'
                    });
                }
                return;
            }

            this.action = '<?= base_url('financeiro/baixar/') ?>' + id;
            this.submit();
        });
    }

    document.addEventListener('click', async function (event) {
        const trigger = event.target.closest('.finance-detail-trigger');
        if (!trigger) {
            return;
        }

        event.preventDefault();

        const lancamentoId = trigger.getAttribute('data-id');
        if (!lancamentoId) {
            return;
        }

        modalTitle.textContent = trigger.getAttribute('data-title') || 'Detalhes do lancamento';
        modalBody.innerHTML = loadingMarkup;
        detailModal.show();

        try {
            const response = await fetch(`${detailBaseUrl}/${lancamentoId}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            const payload = await response.json();

            if (!response.ok || !payload.success) {
                throw new Error(payload.message || 'Nao foi possivel carregar o detalhamento.');
            }

            modalTitle.textContent = payload.title || modalTitle.textContent;
            modalBody.innerHTML = payload.html || '<div class="text-muted">Nenhum detalhe disponivel.</div>';
        } catch (error) {
            console.error('[Financeiro] Falha ao carregar detalhamento do lancamento.', {
                lancamentoId,
                error: error instanceof Error ? error.message : error
            });

            modalBody.innerHTML = `
                <div class="alert alert-danger border-0 mb-0">
                    Nao foi possivel carregar os detalhes deste lancamento agora.
                </div>
            `;

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Falha ao carregar',
                    text: 'Nao foi possivel abrir o detalhamento deste lancamento agora.'
                });
            }
        }
    });
})();
</script>
<?= $this->endSection() ?>
