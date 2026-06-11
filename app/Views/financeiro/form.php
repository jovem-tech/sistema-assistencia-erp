<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$isEdit = isset($lancamento) && is_array($lancamento);
$movementSummary = is_array($movement_summary ?? null) ? $movement_summary : null;
$movements = is_array($movements ?? null) ? $movements : [];
$hasMovements = is_array($movementSummary) && ((int) ($movementSummary['total_movimentos'] ?? 0)) > 0;
$fornecedores = is_array($fornecedores ?? null) ? $fornecedores : [];
$supportsFornecedorLink = (bool) ($supports_fornecedor_link ?? false);
$originTypeLabels = is_array($origin_type_labels ?? null) ? $origin_type_labels : [];
$tipoSelecionado = (string) old('tipo', (string) ($lancamento['tipo'] ?? 'receber'));
$statusSelecionado = (string) old('status', (string) ($lancamento['status'] ?? 'pendente'));
$fornecedorSelecionado = (string) old('fornecedor_id', (string) ($lancamento['fornecedor_id'] ?? ''));
$origemPreviewInicial = trim((string) ($lancamento['origem_tipo_label_resolvido'] ?? ''));
$origemPreviewInicial = $origemPreviewInicial !== '' ? $origemPreviewInicial : 'Sera definida automaticamente ao salvar';
$competenciaAtual = (string) old('data_competencia', (string) ($lancamento['data_competencia'] ?? ''));
$competenciaMesAtual = (string) old('data_competencia_mes', $competenciaAtual !== '' ? substr($competenciaAtual, 0, 7) : '');
$origemTipoAtual = trim((string) ($lancamento['origem_tipo_resolvido'] ?? $lancamento['origem_tipo'] ?? ''));
$competenciaAutomaticaOs = $isEdit && $origemTipoAtual === 'os' && ! empty($lancamento['os_id']) && $competenciaAtual !== '';
$formas = [
    'dinheiro' => 'Dinheiro',
    'pix' => 'PIX',
    'cartao_credito' => 'Cartao credito',
    'cartao_debito' => 'Cartao debito',
    'boleto' => 'Boleto',
    'transferencia' => 'Transferencia',
];
?>

<div class="page-header">
    <h2><i class="bi bi-<?= $isEdit ? 'pencil' : 'plus-lg' ?> me-2"></i><?= esc($title) ?></h2>
    <div class="d-flex flex-wrap gap-2">
        <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('financeiro')" title="Ajuda sobre Financeiro">
            <i class="bi bi-question-circle me-1"></i>Ajuda
        </button>
        <?php if (can('financeiro', 'visualizar')): ?>
        <a href="<?= base_url('financeiro/configuracoes') ?>" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-sliders me-1"></i>Configuracoes financeiras
        </a>
        <?php endif; ?>
        <a href="<?= base_url('financeiro') ?>" class="btn btn-outline-secondary" data-back-default="<?= base_url('financeiro') ?>">
            <i class="bi bi-arrow-left me-1"></i>Voltar
        </a>
    </div>
</div>

<div class="alert alert-light border shadow-sm mb-4">
    <strong>Como o sistema le este lancamento:</strong>
    <span class="d-block mt-1">`Mes/ano de competencia` entra na DRE pelo periodo a que a receita ou despesa pertence. Ex.: conta de junho paga em julho continua em junho. Nos lancamentos manuais, o sistema salva internamente como o dia `01` do mes escolhido. Em receitas automaticas de `OS`, a competencia continua usando a `data de entrega` completa. A `origem` e preenchida automaticamente pelo sistema.</span>
</div>

<?php if ($hasMovements): ?>
<div class="alert alert-warning border shadow-sm mb-4">
    <strong>Este titulo ja possui baixas registradas.</strong>
    <span class="d-block mt-1">O `status`, o `saldo em aberto` e a `ultima data de pagamento` passam a ser recalculados automaticamente pelos movimentos abaixo. Para novas baixas parciais, use o botao `Registrar baixa` na listagem do Financeiro.</span>
</div>
<?php endif; ?>

<div class="card glass-card">
    <div class="card-body">
        <form action="<?= $isEdit ? base_url('financeiro/atualizar/' . (int) $lancamento['id']) : base_url('financeiro/salvar') ?>" method="POST">
            <?php if ($isEdit && ! empty($lancamento['os_id'])): ?>
            <input type="hidden" name="os_id" value="<?= (int) $lancamento['os_id'] ?>">
            <?php endif; ?>

            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label">Tipo *</label>
                    <select name="tipo" id="tipoLancamento" class="form-select" required>
                        <option value="receber" <?= $tipoSelecionado === 'receber' ? 'selected' : '' ?>>A receber</option>
                        <option value="pagar" <?= $tipoSelecionado === 'pagar' ? 'selected' : '' ?>>A pagar</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label d-flex justify-content-between align-items-center gap-2">
                        <span>Categoria *</span>
                        <a href="<?= base_url('financeiro/configuracoes#tab-categorias') ?>" class="btn btn-sm btn-outline-primary py-0 px-2">Configurar</a>
                    </label>
                    <select name="categoria" id="categoriaLancamento" class="form-select" required></select>
                    <div class="form-text">Categorias sao cadastradas em `Financas -> Configuracoes`.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Descricao *</label>
                    <input
                        type="text"
                        name="descricao"
                        class="form-control"
                        required
                        value="<?= esc($lancamento['descricao'] ?? '') ?>"
                    >
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-4 col-lg-3">
                    <label class="form-label">Valor (R$) *</label>
                    <input type="number" step="0.01" min="0" name="valor" class="form-control" required value="<?= esc((string) ($lancamento['valor'] ?? '')) ?>">
                </div>
                <div class="col-md-4 col-lg-3">
                    <label class="form-label">Status</label>
                    <select name="status" id="statusLancamento" class="form-select">
                        <option value="pendente" <?= $statusSelecionado === 'pendente' ? 'selected' : '' ?>>Pendente</option>
                        <option value="parcial" <?= $statusSelecionado === 'parcial' ? 'selected' : '' ?>>Parcial</option>
                        <option value="pago" <?= $statusSelecionado === 'pago' ? 'selected' : '' ?>>Pago</option>
                        <option value="cancelado" <?= $statusSelecionado === 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
                    </select>
                    <?php if ($hasMovements): ?>
                    <div class="form-text text-warning">Com movimentos registrados, este campo vira apenas referencia visual.</div>
                    <?php endif; ?>
                </div>
                <div class="col-md-4 col-lg-3">
                    <label class="form-label">Forma de pagamento</label>
                    <select name="forma_pagamento" class="form-select">
                        <option value="">Selecione</option>
                        <?php foreach ($formas as $valor => $label): ?>
                        <option value="<?= esc($valor) ?>" <?= (($lancamento['forma_pagamento'] ?? '') === $valor) ? 'selected' : '' ?>><?= esc($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-3">
                    <label class="form-label">Origem automatica</label>
                    <div class="form-control bg-body-tertiary" id="origemAutomaticaPreview"><?= esc($origemPreviewInicial) ?></div>
                    <div class="form-text" id="origemAutomaticaHelp">O sistema define a origem conforme o tipo do lancamento, o vinculo com `OS` e a categoria selecionada.</div>
                </div>
            </div>

            <?php if ($supportsFornecedorLink): ?>
            <div class="row g-3 mb-4 <?= $tipoSelecionado === 'pagar' ? '' : 'd-none' ?>" id="fornecedorFieldWrapper" aria-hidden="<?= $tipoSelecionado === 'pagar' ? 'false' : 'true' ?>">
                <div class="col-md-6 col-lg-4">
                    <label class="form-label">Fornecedor</label>
                    <select name="fornecedor_id" id="fornecedorLancamento" class="form-select" <?= $tipoSelecionado === 'pagar' ? '' : 'disabled' ?>>
                        <option value=""><?= empty($fornecedores) ? 'Nenhum fornecedor cadastrado' : 'Selecione um fornecedor' ?></option>
                        <?php foreach ($fornecedores as $fornecedor): ?>
                            <?php
                            $nomeFornecedor = trim((string) ($fornecedor['nome_fantasia'] ?? ''));
                            if ($nomeFornecedor === '') {
                                $nomeFornecedor = trim((string) ($fornecedor['razao_social'] ?? 'Fornecedor #' . (int) ($fornecedor['id'] ?? 0)));
                            }
                            $labelFornecedor = $nomeFornecedor;
                            if ((int) ($fornecedor['ativo'] ?? 1) !== 1) {
                                $labelFornecedor .= ' (inativo)';
                            }
                            ?>
                        <option value="<?= (int) ($fornecedor['id'] ?? 0) ?>" <?= $fornecedorSelecionado === (string) ($fornecedor['id'] ?? '') ? 'selected' : '' ?>>
                            <?= esc($labelFornecedor) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Campo exibido apenas em lancamentos `A pagar` para vincular a despesa ao fornecedor correto.</div>
                </div>
            </div>
            <?php endif; ?>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label">Data vencimento *</label>
                    <input type="date" name="data_vencimento" class="form-control" required value="<?= esc($lancamento['data_vencimento'] ?? date('Y-m-d')) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Mes/ano de competencia</label>
                    <?php if ($competenciaAutomaticaOs): ?>
                        <input type="hidden" name="data_competencia" value="<?= esc($competenciaAtual) ?>">
                        <div class="form-control bg-body-tertiary"><?= esc(formatDate($competenciaAtual)) ?></div>
                        <div class="form-text">Competencia automatica da `OS`, usando a data exata de entrega.</div>
                        <div class="form-text">Para a DRE, esse lancamento continua pertencendo a <?= esc(formatCompetenceDate($competenciaAtual, 'manual')) ?>.</div>
                    <?php else: ?>
                        <input type="month" name="data_competencia_mes" class="form-control" value="<?= esc($competenciaMesAtual) ?>">
                        <div class="form-text">Usada na DRE. Informe apenas o mes e o ano a que a receita ou despesa pertence.</div>
                        <div class="form-text">Ex.: uma conta de luz de junho paga em julho continua com competencia em `06/2026`.</div>
                    <?php endif; ?>
                </div>
                <div class="col-md-4" id="dataPagamentoWrapper">
                    <label class="form-label">Data de pagamento</label>
                    <input type="date" name="data_pagamento" class="form-control" value="<?= esc($lancamento['data_pagamento'] ?? '') ?>">
                    <div class="form-text"><?= $hasMovements ? 'Com baixas parciais, este campo reflete a ultima baixa registrada.' : 'Usada no caixa realizado.' ?></div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label d-flex justify-content-between align-items-center gap-2">
                        <span>Grupo DRE</span>
                        <a href="<?= base_url('financeiro/configuracoes#tab-grupos') ?>" class="btn btn-sm btn-outline-primary py-0 px-2">Configurar</a>
                    </label>
                    <select name="grupo_dre" id="grupoDreLancamento" class="form-select"></select>
                </div>
                <div class="col-md-8">
                    <label class="form-label d-flex justify-content-between align-items-center gap-2">
                        <span>Subgrupo DRE</span>
                        <a href="<?= base_url('financeiro/configuracoes#tab-subgrupos') ?>" class="btn btn-sm btn-outline-primary py-0 px-2">Configurar</a>
                    </label>
                    <select name="subgrupo_dre" id="subgrupoDreLancamento" class="form-select"></select>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-lg-4">
                    <div class="border rounded-4 p-3 h-100">
                        <input type="hidden" name="impacta_dre" value="0">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="impactaDre" name="impacta_dre" value="1" <?= ((int) ($lancamento['impacta_dre'] ?? 1)) === 1 ? 'checked' : '' ?>>
                            <label class="form-check-label fw-semibold" for="impactaDre">Impacta DRE</label>
                        </div>
                        <div class="form-text mt-2">Desative em movimentos patrimoniais ou ajustes que nao devem entrar no resultado.</div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="border rounded-4 p-3 h-100">
                        <input type="hidden" name="impacta_fluxo_caixa" value="0">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="impactaFluxoCaixa" name="impacta_fluxo_caixa" value="1" <?= ((int) ($lancamento['impacta_fluxo_caixa'] ?? 1)) === 1 ? 'checked' : '' ?>>
                            <label class="form-check-label fw-semibold" for="impactaFluxoCaixa">Impacta fluxo de caixa</label>
                        </div>
                        <div class="form-text mt-2">Desative quando o registro for apenas gerencial e nao representar entrada ou saida real de dinheiro.</div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="border rounded-4 p-3 h-100" id="dreFixoMensalCard">
                        <input type="hidden" name="dre_fixo_mensal" value="0">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="dreFixoMensal" name="dre_fixo_mensal" value="1" <?= ((int) ($lancamento['dre_fixo_mensal'] ?? 0)) === 1 ? 'checked' : '' ?>>
                            <label class="form-check-label fw-semibold" for="dreFixoMensal">Despesa fixa mensal na DRE</label>
                        </div>
                        <div class="form-text mt-2">
                            Repete esta despesa automaticamente na DRE em todos os meses a partir da competencia informada. Use para aluguel, energia, internet e outras despesas fixas.
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-12">
                    <label class="form-label">Observacoes</label>
                    <textarea name="observacoes" class="form-control" rows="3"><?= esc($lancamento['observacoes'] ?? '') ?></textarea>
                </div>
            </div>

            <?php if ($isEdit): ?>
            <div class="row g-3 mb-4">
                <div class="col-xl-5">
                    <div class="border rounded-4 p-3 h-100 bg-light-subtle">
                        <div class="d-flex justify-content-between align-items-start gap-3 mb-3 flex-wrap">
                            <div>
                                <div class="small text-uppercase text-muted fw-semibold">Posicao do titulo</div>
                                <h6 class="mb-1">Resumo de quitacao</h6>
                            </div>
                            <span class="badge <?= (($lancamento['status_resolvido'] ?? $lancamento['status'] ?? 'pendente') === 'pago') ? 'bg-success' : ((($lancamento['status_resolvido'] ?? $lancamento['status'] ?? 'pendente') === 'parcial') ? 'bg-warning text-dark' : 'bg-secondary') ?>">
                                <?= esc(ucfirst((string) ($lancamento['status_resolvido'] ?? $lancamento['status'] ?? 'pendente'))) ?>
                            </span>
                        </div>
                        <div class="movement-summary-line">
                            <span>Valor total do titulo</span>
                            <strong><?= formatMoney($movementSummary['valor_titulo'] ?? $lancamento['valor'] ?? 0) ?></strong>
                        </div>
                        <div class="movement-summary-line">
                            <span>Ja quitado</span>
                            <strong class="text-success"><?= formatMoney($movementSummary['valor_movimentado'] ?? 0) ?></strong>
                        </div>
                        <div class="movement-summary-line">
                            <span>Saldo em aberto</span>
                            <strong class="<?= ((float) ($movementSummary['valor_aberto'] ?? 0)) > 0 ? 'text-danger' : 'text-success' ?>"><?= formatMoney($movementSummary['valor_aberto'] ?? 0) ?></strong>
                        </div>
                        <div class="movement-summary-line">
                            <span>Total de baixas</span>
                            <strong><?= (int) ($movementSummary['total_movimentos'] ?? 0) ?></strong>
                        </div>
                        <div class="movement-summary-line border-0 pb-0">
                            <span>Ultima baixa</span>
                            <strong><?= formatDate($movementSummary['data_pagamento_resolvida'] ?? '') ?></strong>
                        </div>
                    </div>
                </div>
                <div class="col-xl-7">
                    <div class="border rounded-4 p-3 h-100">
                        <div class="d-flex justify-content-between align-items-start gap-3 mb-3 flex-wrap">
                            <div>
                                <div class="small text-uppercase text-muted fw-semibold">Historico</div>
                                <h6 class="mb-1">Movimentos do titulo</h6>
                            </div>
                            <span class="badge text-bg-light"><?= count($movements) ?> registro(s)</span>
                        </div>
                        <?php if (! empty($movements)): ?>
                        <div class="table-responsive">
                            <table class="table table-sm stack-table movement-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Forma</th>
                                        <th class="text-end">Valor</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($movements as $movement): ?>
                                    <tr>
                                        <td data-label="Data">
                                            <div class="fw-semibold"><?= formatDate($movement['data_movimento'] ?? '') ?></div>
                                            <?php if (! empty($movement['observacoes'])): ?>
                                            <div class="small text-muted"><?= esc($movement['observacoes']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td data-label="Forma"><?= esc($movement['forma_pagamento'] ?? '-') ?></td>
                                        <td data-label="Valor" class="text-end"><?= formatMoney($movement['valor_movimento'] ?? 0) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <p class="text-muted mb-0">Este titulo ainda nao possui movimentacoes registradas.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="d-flex flex-wrap gap-3">
                <button type="submit" class="btn btn-glow">
                    <i class="bi bi-check-lg me-1"></i><?= $isEdit ? 'Atualizar' : 'Cadastrar' ?>
                </button>
                <a href="<?= base_url('financeiro') ?>" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<style>
.movement-summary-line {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    padding: 0.55rem 0;
    border-bottom: 1px dashed rgba(148, 163, 184, 0.35);
}

.movement-summary-line span {
    color: #64748b;
}

@media (max-width: 575.98px) {
    .movement-summary-line {
        flex-direction: column;
        gap: 0.25rem;
    }

    .movement-table thead {
        display: none;
    }

    .movement-table,
    .movement-table tbody,
    .movement-table tr,
    .movement-table td {
        display: block;
        width: 100%;
    }

    .movement-table tr {
        padding: 0.85rem 0;
        border-bottom: 1px solid rgba(148, 163, 184, 0.18);
    }

    .movement-table tr:last-child {
        border-bottom: 0;
    }

    .movement-table td {
        border: 0;
        padding: 0.22rem 0;
        text-align: left !important;
    }

    .movement-table td::before {
        content: attr(data-label);
        display: block;
        color: #64748b;
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        margin-bottom: 0.15rem;
    }
}
</style>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
    const categoriasCatalogo = <?= json_encode(array_values($financeiro_categories ?? []), JSON_UNESCAPED_UNICODE) ?>;
    const gruposCatalogo = <?= json_encode(array_values($dre_groups ?? []), JSON_UNESCAPED_UNICODE) ?>;
    const subgruposCatalogo = <?= json_encode(array_values($dre_subgroups ?? []), JSON_UNESCAPED_UNICODE) ?>;
    const originTypeLabels = <?= json_encode($originTypeLabels, JSON_UNESCAPED_UNICODE) ?>;
    const osId = <?= json_encode((int) ($lancamento['os_id'] ?? 0)) ?>;
    const currentValues = {
        categoria: <?= json_encode((string) ($lancamento['categoria'] ?? ''), JSON_UNESCAPED_UNICODE) ?>,
        grupo: <?= json_encode((string) ($lancamento['grupo_dre'] ?? ''), JSON_UNESCAPED_UNICODE) ?>,
        subgrupo: <?= json_encode((string) ($lancamento['subgrupo_dre'] ?? ''), JSON_UNESCAPED_UNICODE) ?>,
    };

    const tipoField = document.getElementById('tipoLancamento');
    const categoriaField = document.getElementById('categoriaLancamento');
    const grupoDreField = document.getElementById('grupoDreLancamento');
    const subgrupoDreField = document.getElementById('subgrupoDreLancamento');
    const statusField = document.getElementById('statusLancamento');
    const paymentWrapper = document.getElementById('dataPagamentoWrapper');
    const fornecedorWrapper = document.getElementById('fornecedorFieldWrapper');
    const fornecedorField = document.getElementById('fornecedorLancamento');
    const origemAutomaticaPreview = document.getElementById('origemAutomaticaPreview');
    const origemAutomaticaHelp = document.getElementById('origemAutomaticaHelp');
    const impactaDreField = document.getElementById('impactaDre');
    const impactaFluxoCaixaField = document.getElementById('impactaFluxoCaixa');
    const dreFixoMensalField = document.getElementById('dreFixoMensal');
    const dreFixoMensalCard = document.getElementById('dreFixoMensalCard');
    let skipCategoryDefaults = false;

    function buildOption(select, value, label, selected, attrs = {}) {
        const option = document.createElement('option');
        option.value = value;
        option.textContent = label;
        option.selected = selected;
        Object.entries(attrs).forEach(([key, attrValue]) => {
            if (attrValue !== null && attrValue !== undefined && attrValue !== '') {
                option.dataset[key] = String(attrValue);
            }
        });
        select.appendChild(option);
    }

    function normalizeText(value) {
        const normalized = String(value || '')
            .toLowerCase();

        return (typeof normalized.normalize === 'function' ? normalized.normalize('NFD') : normalized)
            .replace(/[\u0300-\u036f]/g, '')
            .trim();
    }

    function resolveAutomaticOriginCode() {
        if (!tipoField) {
            return '';
        }

        const tipo = String(tipoField.value || '').trim().toLowerCase();
        const categoriaNormalizada = normalizeText(categoriaField ? categoriaField.value : '');
        const isCompraPeca = categoriaNormalizada.includes('compra') && categoriaNormalizada.includes('peca');

        if (tipo === 'receber' && osId > 0) {
            return 'os';
        }

        if (tipo === 'pagar' && isCompraPeca) {
            return osId > 0 ? 'os_item_pendencia' : 'estoque';
        }

        if (tipo === 'pagar' && osId > 0) {
            return 'financeiro_os';
        }

        if (tipo === 'receber' || tipo === 'pagar') {
            return 'manual';
        }

        return '';
    }

    function buildAutomaticOriginHelp(originCode) {
        switch (originCode) {
            case 'os':
                return 'Receita vinculada automaticamente a uma ordem de servico entregue.';
            case 'os_item_pendencia':
                return 'Despesa gerada como compra de peca para uma OS com pendencia de aquisicao.';
            case 'estoque':
                return 'Despesa classificada como compra de pecas sem OS vinculada, tratada como origem de estoque.';
            case 'financeiro_os':
                return 'Despesa lancada no Financeiro com vinculo direto a uma ordem de servico.';
            case 'manual':
                return 'Lancamento criado diretamente no Financeiro, sem origem operacional automatica externa.';
            default:
                return 'O sistema define a origem conforme o tipo do lancamento, o vinculo com OS e a categoria selecionada.';
        }
    }

    function syncAutomaticOriginPreview() {
        if (!origemAutomaticaPreview) {
            return;
        }

        const originCode = resolveAutomaticOriginCode();
        const label = originTypeLabels[originCode] || 'Sera definida automaticamente ao salvar';
        origemAutomaticaPreview.textContent = label;

        if (origemAutomaticaHelp) {
            origemAutomaticaHelp.textContent = buildAutomaticOriginHelp(originCode);
        }
    }

    function populateCategoriaOptions() {
        if (!categoriaField || !tipoField) {
            return;
        }

        const selectedValue = categoriaField.value || currentValues.categoria;
        const tipoAtual = tipoField.value;
        const filtered = categoriasCatalogo.filter((item) => item.ativo == 1 && (item.tipo === tipoAtual || item.tipo === 'ambos'));

        categoriaField.innerHTML = '';
        buildOption(categoriaField, '', filtered.length ? 'Selecione uma categoria' : 'Cadastre categorias nas configuracoes', selectedValue === '');

        filtered.forEach((item) => {
            buildOption(categoriaField, item.nome, item.nome, item.nome === selectedValue, {
                defaultGroup: item.dre_grupo_nome || '',
                defaultSubgroup: item.dre_subgrupo_nome || '',
                impactaDre: item.impacta_dre_padrao ?? 1,
                impactaCaixa: item.impacta_fluxo_caixa_padrao ?? 1,
                dreFixoMensal: item.dre_fixo_mensal_padrao ?? 0,
            });
        });

        if (selectedValue && !filtered.some((item) => item.nome === selectedValue)) {
            buildOption(categoriaField, selectedValue, `${selectedValue} (legado)`, true);
        }
    }

    function populateGrupoOptions() {
        if (!grupoDreField) {
            return;
        }

        const selectedValue = grupoDreField.value || currentValues.grupo;
        grupoDreField.innerHTML = '';
        buildOption(grupoDreField, '', 'Classificar automaticamente', selectedValue === '');

        gruposCatalogo.forEach((item) => {
            const label = item.nome || item.label || '';
            if (label === '') {
                return;
            }
            buildOption(grupoDreField, label, label, label === selectedValue);
        });

        if (selectedValue && !gruposCatalogo.some((item) => (item.nome || item.label || '') === selectedValue)) {
            buildOption(grupoDreField, selectedValue, `${selectedValue} (legado)`, true);
        }
    }

    function populateSubgrupoOptions(preferredValue = null) {
        if (!subgrupoDreField || !grupoDreField) {
            return;
        }

        const selectedGroup = grupoDreField.value;
        const selectedValue = preferredValue !== null ? preferredValue : (subgrupoDreField.value || currentValues.subgrupo);
        subgrupoDreField.innerHTML = '';
        buildOption(subgrupoDreField, '', selectedGroup ? 'Selecione um subgrupo' : 'Selecione primeiro o grupo DRE', selectedValue === '');

        const filtered = subgruposCatalogo.filter((item) => {
            if (item.ativo != 1) {
                return false;
            }
            if (!selectedGroup) {
                return false;
            }
            return (item.grupo_nome || '') === selectedGroup;
        });

        filtered.forEach((item) => {
            buildOption(subgrupoDreField, item.nome, item.nome, item.nome === selectedValue);
        });

        if (selectedValue && !filtered.some((item) => item.nome === selectedValue)) {
            buildOption(subgrupoDreField, selectedValue, `${selectedValue} (legado)`, true);
        }
    }

    function applyCategoriaDefaults() {
        if (!categoriaField || skipCategoryDefaults) {
            return;
        }

        const selectedOption = categoriaField.options[categoriaField.selectedIndex];
        if (!selectedOption || !selectedOption.dataset) {
            return;
        }

        const defaultGroup = selectedOption.dataset.defaultGroup || '';
        const defaultSubgroup = selectedOption.dataset.defaultSubgroup || '';

        if (defaultGroup && grupoDreField) {
            grupoDreField.value = defaultGroup;
            currentValues.grupo = defaultGroup;
        }

        populateSubgrupoOptions(defaultSubgroup);
        currentValues.subgrupo = defaultSubgroup || '';

        if (selectedOption.dataset.impactaDre !== undefined && impactaDreField) {
            impactaDreField.checked = selectedOption.dataset.impactaDre === '1';
        }

        if (selectedOption.dataset.impactaCaixa !== undefined && impactaFluxoCaixaField) {
            impactaFluxoCaixaField.checked = selectedOption.dataset.impactaCaixa === '1';
        }

        if (selectedOption.dataset.dreFixoMensal !== undefined && dreFixoMensalField && tipoField.value === 'pagar') {
            dreFixoMensalField.checked = selectedOption.dataset.dreFixoMensal === '1';
        }

        syncRecurringExpenseState();
    }

    function syncPaymentVisibility() {
        if (!statusField || !paymentWrapper) {
            return;
        }

        paymentWrapper.classList.toggle('opacity-75', statusField.value !== 'pago');
    }

    function syncFornecedorVisibility() {
        if (!tipoField || !fornecedorWrapper || !fornecedorField) {
            return;
        }

        const isPagar = tipoField.value === 'pagar';

        fornecedorWrapper.classList.toggle('d-none', !isPagar);
        fornecedorWrapper.setAttribute('aria-hidden', isPagar ? 'false' : 'true');
        fornecedorField.disabled = !isPagar;

        if (!isPagar) {
            fornecedorField.value = '';
        }
    }

    function syncRecurringExpenseState() {
        if (!tipoField || !dreFixoMensalField || !dreFixoMensalCard) {
            return;
        }

        const isPagar = tipoField.value === 'pagar';

        dreFixoMensalField.disabled = !isPagar;
        dreFixoMensalCard.classList.toggle('opacity-75', !isPagar);
        dreFixoMensalCard.classList.toggle('border-info', isPagar && dreFixoMensalField.checked);

        if (!isPagar) {
            dreFixoMensalField.checked = false;
        }

        if (dreFixoMensalField.checked && impactaDreField) {
            impactaDreField.checked = true;
        }
    }

    if (statusField) {
        statusField.addEventListener('change', syncPaymentVisibility);
        syncPaymentVisibility();
    }

    if (tipoField) {
        tipoField.addEventListener('change', function () {
            currentValues.categoria = '';
            populateCategoriaOptions();
            applyCategoriaDefaults();
            syncFornecedorVisibility();
            syncRecurringExpenseState();
            syncAutomaticOriginPreview();
        });
    }

    if (categoriaField) {
        categoriaField.addEventListener('change', function () {
            currentValues.categoria = categoriaField.value;
            applyCategoriaDefaults();
            syncAutomaticOriginPreview();
        });
    }

    if (grupoDreField) {
        grupoDreField.addEventListener('change', function () {
            skipCategoryDefaults = true;
            currentValues.grupo = grupoDreField.value;
            currentValues.subgrupo = '';
            populateSubgrupoOptions('');
            setTimeout(() => {
                skipCategoryDefaults = false;
            }, 0);
        });
    }

    if (subgrupoDreField) {
        subgrupoDreField.addEventListener('change', function () {
            currentValues.subgrupo = subgrupoDreField.value;
        });
    }

    if (dreFixoMensalField) {
        dreFixoMensalField.addEventListener('change', syncRecurringExpenseState);
    }

    if (impactaDreField) {
        impactaDreField.addEventListener('change', function () {
            if (dreFixoMensalField && dreFixoMensalField.checked && !impactaDreField.checked) {
                impactaDreField.checked = true;
            }
        });
    }

    populateCategoriaOptions();
    populateGrupoOptions();
    populateSubgrupoOptions();
    syncFornecedorVisibility();
    syncRecurringExpenseState();
    syncAutomaticOriginPreview();
})();
</script>
<?= $this->endSection() ?>
