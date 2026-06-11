<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$operadoras = $operadoras ?? [];
$bandeiras = $bandeiras ?? [];
$taxas = $taxas ?? [];
$simuladorDataset = $simuladorDataset ?? ['operadoras' => [], 'bandeiras' => [], 'taxas' => []];
$taxasOperadorasFiltro = [];

foreach ($taxas as $row) {
    $operadoraId = (int) ($row['operadora_id'] ?? 0);
    if ($operadoraId <= 0) {
        continue;
    }

    $operadoraNome = trim((string) ($row['operadora_nome'] ?? ''));
    if ($operadoraNome === '') {
        $operadoraNome = 'Operadora ' . $operadoraId;
    }

    $taxasOperadorasFiltro[$operadoraId] = $operadoraNome;
}

asort($taxasOperadorasFiltro, SORT_NATURAL | SORT_FLAG_CASE);
?>
<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h2 class="mb-1"><i class="bi bi-credit-card-2-front me-2"></i>CartÃµes e Taxas</h2>
            <p class="text-muted mb-0">Configure operadoras, bandeiras, parcelas e simule o faturamento lÃ­quido das vendas em cartÃ£o.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="<?= base_url('financeiro') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Voltar ao Financeiro
            </a>
            <button type="button" class="btn btn-outline-info" onclick="window.openDocPage('financeiro')">
                <i class="bi bi-question-circle me-1"></i>Ajuda
            </button>
        </div>
    </div>

    <?php if (empty($cartaoConfigReady)): ?>
        <div class="alert alert-warning border-0 shadow-sm">
            O cadastro de cartÃµes ainda depende das migraÃ§Ãµes desta entrega. Depois de migrar o banco, esta tela passa a operar normalmente.
        </div>
    <?php endif; ?>

    <ul class="nav nav-pills finance-tabs flex-nowrap overflow-auto mb-4" id="financeiroCartoesTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="aba-operadora-tab" data-bs-toggle="tab" data-bs-target="#aba-operadora" type="button" role="tab" aria-controls="aba-operadora" aria-selected="true">Operadora de maquininha</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="aba-bandeiras-tab" data-bs-toggle="tab" data-bs-target="#aba-bandeiras" type="button" role="tab" aria-controls="aba-bandeiras" aria-selected="false">Bandeiras</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="aba-taxa-parcela-tab" data-bs-toggle="tab" data-bs-target="#aba-taxa-parcela" type="button" role="tab" aria-controls="aba-taxa-parcela" aria-selected="false">Taxa por parcela</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="aba-simulador-tab" data-bs-toggle="tab" data-bs-target="#aba-simulador" type="button" role="tab" aria-controls="aba-simulador" aria-selected="false">Simulador de faturamento lÃ­quido</button>
        </li>
    </ul>

    <div class="tab-content w-100">
        <div class="tab-pane fade show active" id="aba-operadora" role="tabpanel" aria-labelledby="aba-operadora-tab" tabindex="0">
            <div class="card shadow-sm h-100 w-100">
                <div class="card-header bg-white">
                    <strong>Operadoras de Maquininha</strong>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= base_url('financeiro/cartoes/operadoras/salvar') ?>" class="row g-3 mb-4" id="operadoraForm">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" id="operadoraIdInput">
                        <div class="col-12">
                            <label class="form-label">Nome</label>
                            <input type="text" name="nome" id="operadoraNomeInput" class="form-control" placeholder="Ex.: Stone" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">DescriÃ§Ã£o</label>
                            <input type="text" name="descricao" id="operadoraDescricaoInput" class="form-control" placeholder="Contexto interno opcional">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Ordem</label>
                            <input type="number" min="0" name="ordem_exibicao" id="operadoraOrdemInput" class="form-control" value="0">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Prazo padrÃ£o (dias)</label>
                            <input type="number" min="0" name="prazo_padrao_dias" id="operadoraPrazoInput" class="form-control" value="30">
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" name="ativo" id="operadoraAtivoInput" value="1" checked>
                                <label class="form-check-label" for="operadoraAtivoInput">Operadora ativa</label>
                            </div>
                        </div>
                        <div class="col-12 d-flex gap-2">
                            <button type="submit" class="btn btn-glow">Salvar operadora</button>
                            <button type="button" class="btn btn-outline-secondary" data-reset-form="operadora">Limpar</button>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table align-middle finance-card-table">
                            <thead>
                                <tr>
                                    <th>Operadora</th>
                                    <th>Prazo</th>
                                    <th>Status</th>
                                    <th class="text-end">AÃ§Ãµes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($operadoras as $row): ?>
                                    <tr>
                                        <td data-label="Operadora">
                                            <div class="fw-semibold"><?= esc((string) ($row['nome'] ?? '')) ?></div>
                                            <?php if (!empty($row['descricao'])): ?>
                                                <small class="text-muted"><?= esc((string) $row['descricao']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td data-label="Prazo"><?= esc((string) ($row['prazo_padrao_dias'] ?? 0)) ?> dias</td>
                                        <td data-label="Status">
                                            <span class="badge <?= ((int) ($row['ativo'] ?? 0)) === 1 ? 'text-bg-success' : 'text-bg-secondary' ?>">
                                                <?= ((int) ($row['ativo'] ?? 0)) === 1 ? 'Ativa' : 'Inativa' ?>
                                            </span>
                                        </td>
                                        <td data-label="AÃ§Ãµes" class="text-end">
                                            <div class="d-inline-flex gap-2">
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-outline-primary js-edit-operadora"
                                                    data-id="<?= (int) ($row['id'] ?? 0) ?>"
                                                    data-nome="<?= esc((string) ($row['nome'] ?? ''), 'attr') ?>"
                                                    data-descricao="<?= esc((string) ($row['descricao'] ?? ''), 'attr') ?>"
                                                    data-ordem="<?= (int) ($row['ordem_exibicao'] ?? 0) ?>"
                                                    data-prazo="<?= (int) ($row['prazo_padrao_dias'] ?? 0) ?>"
                                                    data-ativo="<?= (int) ($row['ativo'] ?? 0) ?>"
                                                >
                                                    Editar
                                                </button>
                                                <?php if ((int) ($row['ativo'] ?? 0) === 1): ?>
                                                    <form method="POST" action="<?= base_url('financeiro/cartoes/operadoras/desativar/' . (int) ($row['id'] ?? 0)) ?>" class="js-confirm-disable">
                                                        <?= csrf_field() ?>
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">Desativar</button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="aba-bandeiras" role="tabpanel" aria-labelledby="aba-bandeiras-tab" tabindex="0">
            <div class="card shadow-sm h-100 w-100">
                <div class="card-header bg-white">
                    <strong>Bandeiras</strong>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= base_url('financeiro/cartoes/bandeiras/salvar') ?>" class="row g-3 mb-4" id="bandeiraForm">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" id="bandeiraIdInput">
                        <div class="col-12">
                            <label class="form-label">Nome</label>
                            <input type="text" name="nome" id="bandeiraNomeInput" class="form-control" placeholder="Ex.: Visa" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Ordem</label>
                            <input type="number" min="0" name="ordem_exibicao" id="bandeiraOrdemInput" class="form-control" value="0">
                        </div>
                        <div class="col-6 d-flex align-items-end">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" role="switch" name="ativo" id="bandeiraAtivoInput" value="1" checked>
                                <label class="form-check-label" for="bandeiraAtivoInput">Ativa</label>
                            </div>
                        </div>
                        <div class="col-12 d-flex gap-2">
                            <button type="submit" class="btn btn-glow">Salvar bandeira</button>
                            <button type="button" class="btn btn-outline-secondary" data-reset-form="bandeira">Limpar</button>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table align-middle finance-card-table">
                            <thead>
                                <tr>
                                    <th>Bandeira</th>
                                    <th>Status</th>
                                    <th class="text-end">AÃ§Ãµes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bandeiras as $row): ?>
                                    <tr>
                                        <td data-label="Bandeira">
                                            <div class="fw-semibold"><?= esc((string) ($row['nome'] ?? '')) ?></div>
                                            <small class="text-muted">Ordem <?= (int) ($row['ordem_exibicao'] ?? 0) ?></small>
                                        </td>
                                        <td data-label="Status">
                                            <span class="badge <?= ((int) ($row['ativo'] ?? 0)) === 1 ? 'text-bg-success' : 'text-bg-secondary' ?>">
                                                <?= ((int) ($row['ativo'] ?? 0)) === 1 ? 'Ativa' : 'Inativa' ?>
                                            </span>
                                        </td>
                                        <td data-label="AÃ§Ãµes" class="text-end">
                                            <div class="d-inline-flex gap-2">
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-outline-primary js-edit-bandeira"
                                                    data-id="<?= (int) ($row['id'] ?? 0) ?>"
                                                    data-nome="<?= esc((string) ($row['nome'] ?? ''), 'attr') ?>"
                                                    data-ordem="<?= (int) ($row['ordem_exibicao'] ?? 0) ?>"
                                                    data-ativo="<?= (int) ($row['ativo'] ?? 0) ?>"
                                                >
                                                    Editar
                                                </button>
                                                <?php if ((int) ($row['ativo'] ?? 0) === 1): ?>
                                                    <form method="POST" action="<?= base_url('financeiro/cartoes/bandeiras/desativar/' . (int) ($row['id'] ?? 0)) ?>" class="js-confirm-disable">
                                                        <?= csrf_field() ?>
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">Desativar</button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="aba-taxa-parcela" role="tabpanel" aria-labelledby="aba-taxa-parcela-tab" tabindex="0">
            <div class="card shadow-sm h-100 w-100">
                <div class="card-header bg-white">
                    <strong>Taxas por Parcela</strong>
                </div>
                <div class="card-body">
                    <div class="row g-4 align-items-stretch">
                        <div class="col-12 col-xxl-5">
                            <div class="card shadow-sm h-100 w-100">
                                <div class="card-body">
                    <form method="POST" action="<?= base_url('financeiro/cartoes/taxas/salvar') ?>" class="row g-3 mb-4" id="taxaForm">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" id="taxaIdInput">
                        <div class="col-12">
                            <label class="form-label">Operadora</label>
                            <select name="operadora_id" id="taxaOperadoraInput" class="form-select" required>
                                <option value="">Selecione</option>
                                <?php foreach ($operadoras as $operadora): ?>
                                    <option value="<?= (int) ($operadora['id'] ?? 0) ?>"><?= esc((string) ($operadora['nome'] ?? '')) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Bandeira</label>
                            <select name="bandeira_id" id="taxaBandeiraInput" class="form-select">
                                <option value="">Todas as bandeiras</option>
                                <?php foreach ($bandeiras as $bandeira): ?>
                                    <option value="<?= (int) ($bandeira['id'] ?? 0) ?>"><?= esc((string) ($bandeira['nome'] ?? '')) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Modalidade</label>
                            <select name="modalidade" id="taxaModalidadeInput" class="form-select" required>
                                <option value="credito">CrÃ©dito</option>
                                <option value="debito">DÃ©bito</option>
                            </select>
                        </div>
                        <div class="col-3">
                            <label class="form-label">Parcelas de</label>
                            <input type="number" min="1" max="12" name="parcelas_inicial" id="taxaParcelasInicialInput" class="form-control" value="1">
                        </div>
                        <div class="col-3">
                            <label class="form-label">atÃ©</label>
                            <input type="number" min="1" max="12" name="parcelas_final" id="taxaParcelasFinalInput" class="form-control" value="1">
                        </div>
                        <div class="col-4">
                            <label class="form-label">Taxa (%)</label>
                            <input type="number" step="0.0001" min="0" name="taxa_percentual" id="taxaPercentualInput" class="form-control" value="0">
                        </div>
                        <div class="col-4">
                            <label class="form-label">Taxa fixa (R$)</label>
                            <input type="number" step="0.01" min="0" name="taxa_fixa" id="taxaFixaInput" class="form-control" value="0">
                        </div>
                        <div class="col-4">
                            <label class="form-label">Recebimento (dias)</label>
                            <input type="number" min="0" name="prazo_recebimento_dias" id="taxaPrazoInput" class="form-control" value="30">
                        </div>
                        <div class="col-12">
                            <label class="form-label">ObservaÃ§Ãµes</label>
                            <input type="text" name="observacoes" id="taxaObservacoesInput" class="form-control" placeholder="Ex.: taxa promocional da campanha">
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" name="ativo" id="taxaAtivoInput" value="1" checked>
                                <label class="form-check-label" for="taxaAtivoInput">Taxa ativa</label>
                            </div>
                        </div>
                        <div class="col-12 d-flex gap-2">
                            <button type="submit" class="btn btn-glow">Salvar taxa</button>
                            <button type="button" class="btn btn-outline-secondary" data-reset-form="taxa">Limpar</button>
                        </div>
                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-xxl-7">
                            <div class="card shadow-sm h-100 w-100">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <strong>Taxas cadastradas</strong>
                            <span class="text-muted small">Use as taxas daqui tanto na baixa da OS quanto nas simulaÃ§Ãµes internas.</span>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($taxasOperadorasFiltro)): ?>
                                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                                    <span class="text-muted small">Filtrar por operadora</span>
                                    <div class="taxa-operadora-filters" role="group" aria-label="Filtrar taxas cadastradas por operadora">
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-secondary taxa-operadora-filter-btn is-active"
                                            data-taxa-operadora-filter="all"
                                            aria-pressed="true"
                                        >
                                            Todas
                                        </button>
                                        <?php foreach ($taxasOperadorasFiltro as $operadoraId => $operadoraNome): ?>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-secondary taxa-operadora-filter-btn"
                                                data-taxa-operadora-filter="<?= (int) $operadoraId ?>"
                                                aria-pressed="false"
                                            >
                                                <?= esc($operadoraNome) ?>
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <div class="table-responsive">
                                <table class="table align-middle finance-card-table" id="taxasCadastradasTable">
                                    <thead>
                                        <tr>
                                            <th>Operadora</th>
                                            <th>Bandeira</th>
                                            <th>Modalidade</th>
                                            <th>Faixa</th>
                                            <th>Taxa</th>
                                            <th>LiquidaÃ§Ã£o</th>
                                            <th>Status</th>
                                            <th class="text-end">AÃ§Ãµes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($taxas)): ?>
                                            <tr class="js-taxas-empty-row">
                                                <td colspan="8" class="text-center text-muted py-4">Nenhuma taxa cadastrada.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($taxas as $row): ?>
                                            <tr data-operadora-id="<?= (int) ($row['operadora_id'] ?? 0) ?>">
                                                <td data-label="Operadora"><?= esc((string) ($row['operadora_nome'] ?? '')) ?></td>
                                                <td data-label="Bandeira"><?= esc((string) ($row['bandeira_nome'] ?? 'Todas')) ?></td>
                                                <td data-label="Modalidade"><?= (string) ($row['modalidade'] ?? '') === 'debito' ? 'DÃ©bito' : 'CrÃ©dito' ?></td>
                                                <td data-label="Faixa">
                                                    <?= (int) ($row['parcelas_inicial'] ?? 1) ?>x
                                                    <?php if ((int) ($row['parcelas_final'] ?? 1) !== (int) ($row['parcelas_inicial'] ?? 1)): ?>
                                                        a <?= (int) ($row['parcelas_final'] ?? 1) ?>x
                                                    <?php endif; ?>
                                                </td>
                                                <td data-label="Taxa">
                                                    <div><?= number_format((float) ($row['taxa_percentual'] ?? 0), 4, ',', '.') ?>%</div>
                                                    <small class="text-muted">+ R$ <?= number_format((float) ($row['taxa_fixa'] ?? 0), 2, ',', '.') ?></small>
                                                </td>
                                                <td data-label="LiquidaÃ§Ã£o"><?= (int) ($row['prazo_recebimento_dias'] ?? 0) ?> dias</td>
                                                <td data-label="Status">
                                                    <span class="badge <?= ((int) ($row['ativo'] ?? 0)) === 1 ? 'text-bg-success' : 'text-bg-secondary' ?>">
                                                        <?= ((int) ($row['ativo'] ?? 0)) === 1 ? 'Ativa' : 'Inativa' ?>
                                                    </span>
                                                </td>
                                                <td data-label="AÃ§Ãµes" class="text-end">
                                                    <div class="d-inline-flex gap-2">
                                                        <button
                                                            type="button"
                                                            class="btn btn-sm btn-outline-primary js-edit-taxa"
                                                            data-id="<?= (int) ($row['id'] ?? 0) ?>"
                                                            data-operadora-id="<?= (int) ($row['operadora_id'] ?? 0) ?>"
                                                            data-bandeira-id="<?= !empty($row['bandeira_id']) ? (int) $row['bandeira_id'] : '' ?>"
                                                            data-modalidade="<?= esc((string) ($row['modalidade'] ?? 'credito'), 'attr') ?>"
                                                            data-parcelas-inicial="<?= (int) ($row['parcelas_inicial'] ?? 1) ?>"
                                                            data-parcelas-final="<?= (int) ($row['parcelas_final'] ?? 1) ?>"
                                                            data-taxa-percentual="<?= esc(number_format((float) ($row['taxa_percentual'] ?? 0), 4, '.', ''), 'attr') ?>"
                                                            data-taxa-fixa="<?= esc(number_format((float) ($row['taxa_fixa'] ?? 0), 2, '.', ''), 'attr') ?>"
                                                            data-prazo="<?= (int) ($row['prazo_recebimento_dias'] ?? 0) ?>"
                                                            data-observacoes="<?= esc((string) ($row['observacoes'] ?? ''), 'attr') ?>"
                                                            data-ativo="<?= (int) ($row['ativo'] ?? 0) ?>"
                                                        >
                                                            Editar
                                                        </button>
                                                        <?php if ((int) ($row['ativo'] ?? 0) === 1): ?>
                                                            <form method="POST" action="<?= base_url('financeiro/cartoes/taxas/desativar/' . (int) ($row['id'] ?? 0)) ?>" class="js-confirm-disable">
                                                                <?= csrf_field() ?>
                                                                <button type="submit" class="btn btn-sm btn-outline-danger">Desativar</button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                            <tr class="js-taxas-empty-row d-none">
                                                <td colspan="8" class="text-center text-muted py-4">Nenhuma taxa encontrada para a operadora selecionada.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </div>
        </div>

<?php if (false): ?>
        <div class="tab-pane fade" id="aba-taxas-cadastradas" role="tabpanel" aria-labelledby="aba-taxas-cadastradas-tab" tabindex="0">
            <div class="card shadow-sm w-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <strong>Taxas cadastradas</strong>
                    <span class="text-muted small">Use as taxas daqui tanto na baixa da OS quanto nas simulaÃ§Ãµes internas.</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle finance-card-table">
                            <thead>
                                <tr>
                                    <th>Operadora</th>
                                    <th>Bandeira</th>
                                    <th>Modalidade</th>
                                    <th>Faixa</th>
                                    <th>Taxa</th>
                                    <th>LiquidaÃ§Ã£o</th>
                                    <th>Status</th>
                                    <th class="text-end">AÃ§Ãµes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($taxas as $row): ?>
                                    <tr>
                                        <td data-label="Operadora"><?= esc((string) ($row['operadora_nome'] ?? '')) ?></td>
                                        <td data-label="Bandeira"><?= esc((string) ($row['bandeira_nome'] ?? 'Todas')) ?></td>
                                        <td data-label="Modalidade"><?= (string) ($row['modalidade'] ?? '') === 'debito' ? 'DÃ©bito' : 'CrÃ©dito' ?></td>
                                        <td data-label="Faixa">
                                            <?= (int) ($row['parcelas_inicial'] ?? 1) ?>x
                                            <?php if ((int) ($row['parcelas_final'] ?? 1) !== (int) ($row['parcelas_inicial'] ?? 1)): ?>
                                                a <?= (int) ($row['parcelas_final'] ?? 1) ?>x
                                            <?php endif; ?>
                                        </td>
                                        <td data-label="Taxa">
                                            <div><?= number_format((float) ($row['taxa_percentual'] ?? 0), 4, ',', '.') ?>%</div>
                                            <small class="text-muted">+ R$ <?= number_format((float) ($row['taxa_fixa'] ?? 0), 2, ',', '.') ?></small>
                                        </td>
                                        <td data-label="LiquidaÃ§Ã£o"><?= (int) ($row['prazo_recebimento_dias'] ?? 0) ?> dias</td>
                                        <td data-label="Status">
                                            <span class="badge <?= ((int) ($row['ativo'] ?? 0)) === 1 ? 'text-bg-success' : 'text-bg-secondary' ?>">
                                                <?= ((int) ($row['ativo'] ?? 0)) === 1 ? 'Ativa' : 'Inativa' ?>
                                            </span>
                                        </td>
                                        <td data-label="AÃ§Ãµes" class="text-end">
                                            <div class="d-inline-flex gap-2">
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-outline-primary js-edit-taxa"
                                                    data-id="<?= (int) ($row['id'] ?? 0) ?>"
                                                    data-operadora-id="<?= (int) ($row['operadora_id'] ?? 0) ?>"
                                                    data-bandeira-id="<?= !empty($row['bandeira_id']) ? (int) $row['bandeira_id'] : '' ?>"
                                                    data-modalidade="<?= esc((string) ($row['modalidade'] ?? 'credito'), 'attr') ?>"
                                                    data-parcelas-inicial="<?= (int) ($row['parcelas_inicial'] ?? 1) ?>"
                                                    data-parcelas-final="<?= (int) ($row['parcelas_final'] ?? 1) ?>"
                                                    data-taxa-percentual="<?= esc(number_format((float) ($row['taxa_percentual'] ?? 0), 4, '.', ''), 'attr') ?>"
                                                    data-taxa-fixa="<?= esc(number_format((float) ($row['taxa_fixa'] ?? 0), 2, '.', ''), 'attr') ?>"
                                                    data-prazo="<?= (int) ($row['prazo_recebimento_dias'] ?? 0) ?>"
                                                    data-observacoes="<?= esc((string) ($row['observacoes'] ?? ''), 'attr') ?>"
                                                    data-ativo="<?= (int) ($row['ativo'] ?? 0) ?>"
                                                >
                                                    Editar
                                                </button>
                                                <?php if ((int) ($row['ativo'] ?? 0) === 1): ?>
                                                    <form method="POST" action="<?= base_url('financeiro/cartoes/taxas/desativar/' . (int) ($row['id'] ?? 0)) ?>" class="js-confirm-disable">
                                                        <?= csrf_field() ?>
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">Desativar</button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

<?php endif; ?>
        <div class="tab-pane fade" id="aba-simulador" role="tabpanel" aria-labelledby="aba-simulador-tab" tabindex="0">
            <div class="card shadow-sm w-100">
                <div class="card-header bg-white">
                    <strong>Simulador de Faturamento LÃ­quido</strong>
                </div>
                <div class="card-body">
                    <div class="row g-3 align-items-end">
                        <div class="col-12 col-lg-3">
                            <label class="form-label">Valor bruto da venda</label>
                            <input type="number" min="0.01" step="0.01" id="simuladorValorInput" class="form-control" value="130.00">
                        </div>
                        <div class="col-12 col-lg-3">
                            <label class="form-label">Operadora</label>
                            <select id="simuladorOperadoraInput" class="form-select">
                                <option value="">Selecione</option>
                                <?php foreach ($operadoras as $operadora): ?>
                                    <?php if ((int) ($operadora['ativo'] ?? 0) !== 1) continue; ?>
                                    <option value="<?= (int) ($operadora['id'] ?? 0) ?>"><?= esc((string) ($operadora['nome'] ?? '')) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-lg-2">
                            <label class="form-label">Modalidade</label>
                            <select id="simuladorModalidadeInput" class="form-select">
                                <option value="credito">CrÃ©dito</option>
                                <option value="debito">DÃ©bito</option>
                            </select>
                        </div>
                        <div class="col-12 col-lg-2">
                            <label class="form-label">Parcelas</label>
                            <select id="simuladorParcelasInput" class="form-select">
                                <?php for ($i = 1; $i <= 12; $i++): ?>
                                    <option value="<?= $i ?>"><?= $i ?>x</option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-12 col-lg-2">
                            <label class="form-label">Bandeira</label>
                            <select id="simuladorBandeiraInput" class="form-select">
                                <option value="">Todas</option>
                                <?php foreach ($bandeiras as $bandeira): ?>
                                    <?php if ((int) ($bandeira['ativo'] ?? 0) !== 1) continue; ?>
                                    <option value="<?= (int) ($bandeira['id'] ?? 0) ?>"><?= esc((string) ($bandeira['nome'] ?? '')) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <button type="button" class="btn btn-glow" id="simularCartaoBtn">
                                <i class="bi bi-calculator me-1"></i>Simular recebimento
                            </button>
                        </div>
                    </div>

                    <div class="row g-3 mt-2" id="simuladorResultadoWrap">
                        <div class="col-12 col-md-6 col-xl-3">
                            <div class="simulator-metric">
                                <span class="simulator-label">Taxa total</span>
                                <strong id="simuladorTaxaTotal">R$ 0,00</strong>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-xl-3">
                            <div class="simulator-metric">
                                <span class="simulator-label">Valor lÃ­quido</span>
                                <strong id="simuladorValorLiquido">R$ 0,00</strong>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-xl-3">
                            <div class="simulator-metric">
                                <span class="simulator-label">Percentual aplicado</span>
                                <strong id="simuladorPercentual">0,0000%</strong>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-xl-3">
                            <div class="simulator-metric">
                                <span class="simulator-label">PrevisÃ£o de recebimento</span>
                                <strong id="simuladorRecebimento">-</strong>
                            </div>
                        </div>
                    </div>
                    <div class="text-muted small mt-3" id="simuladorResumoTexto">
                        Preencha os dados da venda para estimar quanto a assistÃªncia realmente vai faturar apÃ³s as taxas da maquininha.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<style>
.finance-card-table td,
.finance-card-table th {
    vertical-align: middle;
}

.simulator-metric {
    border: 1px solid rgba(15, 23, 42, 0.08);
    border-radius: 1rem;
    padding: 1rem 1.1rem;
    background: linear-gradient(180deg, rgba(255,255,255,0.92), rgba(248,250,252,0.95));
    min-height: 100%;
}

.simulator-label {
    display: block;
    font-size: 0.82rem;
    color: #64748b;
    margin-bottom: 0.45rem;
}

.finance-tabs {
    gap: 0.5rem;
}

.finance-tabs .nav-link {
    white-space: nowrap;
    border-radius: 999px;
}

.tab-content {
    width: 100%;
}

.tab-pane[id^="aba-"] {
    width: 100%;
}

.tab-pane[id^="aba-"] .card {
    width: 100%;
}

.tab-pane[id^="aba-"] .table-responsive {
    width: 100%;
}

.taxa-operadora-filters {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.taxa-operadora-filter-btn {
    border-radius: 999px;
    white-space: nowrap;
}

.taxa-operadora-filter-btn.is-active {
    background-color: #6d5efc;
    border-color: #6d5efc;
    color: #fff;
    box-shadow: 0 0.35rem 0.85rem rgba(109, 94, 252, 0.2);
}

.taxa-operadora-filter-btn.is-active:hover,
.taxa-operadora-filter-btn.is-active:focus,
.taxa-operadora-filter-btn.is-active:focus-visible {
    background-color: #6d5efc;
    border-color: #6d5efc;
    color: #fff;
}

@media (max-width: 576px) {
    .finance-tabs {
        padding-bottom: 0.25rem;
    }
}

@media (max-width: 768px) {
    .finance-card-table thead {
        display: none;
    }

    .finance-card-table,
    .finance-card-table tbody,
    .finance-card-table tr,
    .finance-card-table td {
        display: block;
        width: 100%;
    }

    .finance-card-table tr {
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 1rem;
        padding: 0.85rem;
        margin-bottom: 0.85rem;
        background: #fff;
    }

    .finance-card-table td {
        border: 0;
        padding: 0.35rem 0;
    }

    .finance-card-table td::before {
        content: attr(data-label);
        display: block;
        font-size: 0.72rem;
        text-transform: uppercase;
        color: #64748b;
        margin-bottom: 0.2rem;
        letter-spacing: 0.04em;
    }

    .finance-card-table td.text-end {
        text-align: left !important;
    }
}
</style>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
window.financeiroCartoesDataset = <?= json_encode($simuladorDataset, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

document.addEventListener('DOMContentLoaded', function () {
    const operadoraForm = document.getElementById('operadoraForm');
    const bandeiraForm = document.getElementById('bandeiraForm');
    const taxaForm = document.getElementById('taxaForm');
    const simuladorValorInput = document.getElementById('simuladorValorInput');
    const simuladorOperadoraInput = document.getElementById('simuladorOperadoraInput');
    const simuladorModalidadeInput = document.getElementById('simuladorModalidadeInput');
    const simuladorParcelasInput = document.getElementById('simuladorParcelasInput');
    const simuladorBandeiraInput = document.getElementById('simuladorBandeiraInput');
    const simuladorTaxaTotal = document.getElementById('simuladorTaxaTotal');
    const simuladorValorLiquido = document.getElementById('simuladorValorLiquido');
    const simuladorPercentual = document.getElementById('simuladorPercentual');
    const simuladorRecebimento = document.getElementById('simuladorRecebimento');
    const simuladorResumoTexto = document.getElementById('simuladorResumoTexto');
    const taxasCadastradasTable = document.getElementById('taxasCadastradasTable');
    const taxaOperadoraFilterButtons = Array.from(document.querySelectorAll('[data-taxa-operadora-filter]'));
    const taxaOperadoraRows = taxasCadastradasTable ? Array.from(taxasCadastradasTable.querySelectorAll('tbody tr[data-operadora-id]')) : [];
    const taxaOperadoraEmptyRow = taxasCadastradasTable?.querySelector('.js-taxas-empty-row');

    const money = (value) => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(Number(value || 0));

    const applyTaxaOperadoraFilter = (filterValue = 'all') => {
        taxaOperadoraFilterButtons.forEach((button) => {
            const isActive = (button.dataset.taxaOperadoraFilter || 'all') === filterValue;
            button.classList.toggle('is-active', isActive);
            button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });

        if (!taxaOperadoraRows.length && !taxaOperadoraEmptyRow) {
            return;
        }

        let visibleRows = 0;
        taxaOperadoraRows.forEach((row) => {
            const rowOperadoraId = row.dataset.operadoraId || '';
            const isVisible = filterValue === 'all' || rowOperadoraId === filterValue;
            row.classList.toggle('d-none', !isVisible);
            if (isVisible) {
                visibleRows += 1;
            }
        });

        if (taxaOperadoraEmptyRow) {
            taxaOperadoraEmptyRow.classList.toggle('d-none', visibleRows > 0);
        }
    };

    const resetOperadoraForm = () => {
        operadoraForm?.reset();
        document.getElementById('operadoraIdInput').value = '';
        document.getElementById('operadoraPrazoInput').value = '30';
        document.getElementById('operadoraAtivoInput').checked = true;
    };

    const resetBandeiraForm = () => {
        bandeiraForm?.reset();
        document.getElementById('bandeiraIdInput').value = '';
        document.getElementById('bandeiraAtivoInput').checked = true;
    };

    const resetTaxaForm = () => {
        taxaForm?.reset();
        document.getElementById('taxaIdInput').value = '';
        document.getElementById('taxaModalidadeInput').value = 'credito';
        document.getElementById('taxaParcelasInicialInput').value = '1';
        document.getElementById('taxaParcelasFinalInput').value = '1';
        document.getElementById('taxaAtivoInput').checked = true;
    };

    document.querySelectorAll('[data-reset-form="operadora"]').forEach((button) => {
        button.addEventListener('click', resetOperadoraForm);
    });
    document.querySelectorAll('[data-reset-form="bandeira"]').forEach((button) => {
        button.addEventListener('click', resetBandeiraForm);
    });
    document.querySelectorAll('[data-reset-form="taxa"]').forEach((button) => {
        button.addEventListener('click', resetTaxaForm);
    });

    taxaOperadoraFilterButtons.forEach((button) => {
        button.addEventListener('click', () => {
            applyTaxaOperadoraFilter(button.dataset.taxaOperadoraFilter || 'all');
        });
    });

    applyTaxaOperadoraFilter('all');

    document.querySelectorAll('.js-edit-operadora').forEach((button) => {
        button.addEventListener('click', () => {
            document.getElementById('operadoraIdInput').value = button.dataset.id || '';
            document.getElementById('operadoraNomeInput').value = button.dataset.nome || '';
            document.getElementById('operadoraDescricaoInput').value = button.dataset.descricao || '';
            document.getElementById('operadoraOrdemInput').value = button.dataset.ordem || '0';
            document.getElementById('operadoraPrazoInput').value = button.dataset.prazo || '30';
            document.getElementById('operadoraAtivoInput').checked = button.dataset.ativo === '1';
            operadoraForm?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });

    document.querySelectorAll('.js-edit-bandeira').forEach((button) => {
        button.addEventListener('click', () => {
            document.getElementById('bandeiraIdInput').value = button.dataset.id || '';
            document.getElementById('bandeiraNomeInput').value = button.dataset.nome || '';
            document.getElementById('bandeiraOrdemInput').value = button.dataset.ordem || '0';
            document.getElementById('bandeiraAtivoInput').checked = button.dataset.ativo === '1';
            bandeiraForm?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });

    document.querySelectorAll('.js-edit-taxa').forEach((button) => {
        button.addEventListener('click', () => {
            document.getElementById('taxaIdInput').value = button.dataset.id || '';
            document.getElementById('taxaOperadoraInput').value = button.dataset.operadoraId || '';
            document.getElementById('taxaBandeiraInput').value = button.dataset.bandeiraId || '';
            document.getElementById('taxaModalidadeInput').value = button.dataset.modalidade || 'credito';
            document.getElementById('taxaParcelasInicialInput').value = button.dataset.parcelasInicial || '1';
            document.getElementById('taxaParcelasFinalInput').value = button.dataset.parcelasFinal || '1';
            document.getElementById('taxaPercentualInput').value = button.dataset.taxaPercentual || '0';
            document.getElementById('taxaFixaInput').value = button.dataset.taxaFixa || '0';
            document.getElementById('taxaPrazoInput').value = button.dataset.prazo || '0';
            document.getElementById('taxaObservacoesInput').value = button.dataset.observacoes || '';
            document.getElementById('taxaAtivoInput').checked = button.dataset.ativo === '1';
            taxaForm?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });

    document.querySelectorAll('.js-confirm-disable').forEach((form) => {
        form.addEventListener('submit', async (event) => {
            if (!window.Swal || typeof window.Swal.fire !== 'function') {
                return;
            }

            event.preventDefault();
            const result = await window.Swal.fire({
                icon: 'warning',
                title: 'Desativar cadastro?',
                text: 'O registro serÃ¡ mantido para histÃ³rico, mas deixarÃ¡ de ser usado nas novas simulaÃ§Ãµes e baixas.',
                showCancelButton: true,
                confirmButtonText: 'Desativar',
                cancelButtonText: 'Cancelar',
                reverseButtons: true,
            });

            if (result.isConfirmed) {
                form.submit();
            }
        });
    });

    if (simuladorModalidadeInput) {
        simuladorModalidadeInput.addEventListener('change', () => {
            const isDebito = simuladorModalidadeInput.value === 'debito';
            simuladorParcelasInput.value = '1';
            simuladorParcelasInput.disabled = isDebito;
        });
    }

    document.getElementById('simularCartaoBtn')?.addEventListener('click', async () => {
        try {
            const body = new URLSearchParams();
            body.set('valor_bruto', simuladorValorInput?.value || '');
            body.set('operadora_id', simuladorOperadoraInput?.value || '');
            body.set('modalidade', simuladorModalidadeInput?.value || 'credito');
            body.set('parcelas', simuladorParcelasInput?.value || '1');
            body.set('bandeira_id', simuladorBandeiraInput?.value || '');

            const response = await fetch('<?= base_url('financeiro/cartoes/simular') ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: body.toString(),
            });

            const payload = await response.json();
            if (!response.ok || !payload.ok) {
                throw new Error(payload.message || 'NÃ£o foi possÃ­vel simular a venda em cartÃ£o.');
            }

            const simulation = payload.simulation || {};
            simuladorTaxaTotal.textContent = money(simulation.valor_taxa || 0);
            simuladorValorLiquido.textContent = money(simulation.valor_liquido || 0);
            simuladorPercentual.textContent = `${Number(simulation.taxa_percentual || 0).toFixed(4).replace('.', ',')}%`;
            simuladorRecebimento.textContent = `${simulation.prazo_recebimento_dias || 0} dias`;
            simuladorResumoTexto.textContent =
                `Venda bruta de ${money(simulation.valor_bruto || 0)} em ${simulation.modalidade_label || 'cartÃ£o'}`
                + ` pela operadora ${simulation.operadora?.nome || '-'}`
                + `${simulation.parcelas > 1 ? ` em ${simulation.parcelas}x` : ''}.`
                + ` Valor lÃ­quido estimado: ${money(simulation.valor_liquido || 0)}.`;
        } catch (error) {
            simuladorTaxaTotal.textContent = 'R$ 0,00';
            simuladorValorLiquido.textContent = 'R$ 0,00';
            simuladorPercentual.textContent = '0,0000%';
            simuladorRecebimento.textContent = '-';
            simuladorResumoTexto.textContent = error.message || 'NÃ£o foi possÃ­vel simular a venda.';

            if (window.Swal && typeof window.Swal.fire === 'function') {
                window.Swal.fire('Falha na simulaÃ§Ã£o', simuladorResumoTexto.textContent, 'error');
            }
        }
    });
});
</script>
<?= $this->endSection() ?>
