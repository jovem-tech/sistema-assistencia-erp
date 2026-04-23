<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<style>
.precificacao-shell {
    border: 1px solid rgba(103, 118, 215, 0.22);
}

#precificacaoSecoesTabs.precificacao-top-tabs {
    border-bottom: 0;
    gap: 0.45rem;
}

#precificacaoSecoesTabs.precificacao-top-tabs .nav-link {
    border: 1px solid #d9dff4;
    border-radius: 999px;
    background: #f6f8ff;
    color: #515c83;
    font-weight: 600;
    padding: 0.45rem 0.9rem;
}

#precificacaoSecoesTabs.precificacao-top-tabs .nav-link.active {
    background: #5b5ce2;
    border-color: #5b5ce2;
    color: #fff;
    box-shadow: 0 4px 14px rgba(91, 92, 226, 0.22);
}

.precificacao-secao-pane {
    border: 1px solid #e5eaf7;
    border-radius: 14px;
    background: #fff;
    padding: 1rem;
    margin-bottom: 1rem;
}

.precificacao-secao-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 0.9rem;
    padding: 0.8rem 0.95rem;
    border-radius: 12px;
    margin-bottom: 1rem;
}

.secao-categorias .precificacao-secao-head {
    background: #f3f7ff;
    border: 1px solid #d7e3ff;
}

.secao-parametros .precificacao-secao-head {
    background: #f4fbf8;
    border: 1px solid #d4eee2;
}

.precificacao-secao-kicker {
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #6f79a3;
    font-weight: 700;
    margin-bottom: 0.2rem;
}

.precificacao-secao-title {
    margin: 0 0 0.2rem;
    font-size: 1.02rem;
}

.precificacao-secao-desc {
    margin: 0;
    color: #5c637f;
    font-size: 0.84rem;
}

.precificacao-secao-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    border: 1px solid #d8e0f7;
    border-radius: 999px;
    background: #fff;
    color: #4d5a84;
    font-size: 0.72rem;
    font-weight: 700;
    padding: 0.28rem 0.62rem;
    white-space: nowrap;
}

.override-tipo-tabs {
    border-bottom: 1px solid #ebeffa;
    margin-bottom: 0.95rem;
    padding-bottom: 0.6rem;
}

.override-tipo-tabs .nav-link {
    border: 1px solid transparent;
    color: #4f5e83;
    font-weight: 600;
    padding: 0.4rem 0.8rem;
}

.override-tipo-tabs .nav-link.active {
    border-color: #cfd8f7;
    background: #eef2ff;
    color: #3849a2;
}

.servico-override-list .row-empty-placeholder {
    border: 1px dashed #d7def3;
    border-radius: 12px;
    background: #fafbff;
}

.servico-override-card {
    border: 1px solid #dfe5f6;
    border-radius: 14px;
    overflow: hidden;
}

.servico-override-card .card-header {
    background: #ffffff;
    border-bottom: 1px solid #edf1fa;
}

.servico-override-summary .badge {
    font-size: 0.72rem;
}

.servico-override-sections .nav-link {
    border: 1px solid transparent;
    color: #4f5e83;
    font-weight: 600;
    padding: 0.35rem 0.75rem;
}

.servico-override-sections .nav-link.active {
    border-color: #cfd8f7;
    background: #eef2ff;
    color: #3849a2;
}

.servico-override-table .table th {
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: 0.02em;
    color: #647192;
}

.servico-override-table .value-col {
    min-width: 170px;
}

.servico-override-row .form-label {
    font-size: 0.72rem;
    color: #616d90;
    font-weight: 600;
}

.servico-override-row .text-muted.small {
    line-height: 1.25;
}

.servico-override-row .servico-info-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
}

.servico-override-row .btn-servico-info {
    --bs-btn-padding-y: 0.12rem;
    --bs-btn-padding-x: 0.52rem;
    --bs-btn-font-size: 0.67rem;
    border-radius: 999px;
    font-weight: 600;
    line-height: 1.15;
}

.servico-override-row .servico-info-collapse {
    max-width: 100%;
}

.servico-override-row .servico-info-note {
    border: 1px solid #dce3f7;
    border-radius: 0.5rem;
    background: #f7f9ff;
    color: #506089;
    font-size: 0.74rem;
    line-height: 1.3;
    padding: 0.38rem 0.52rem;
}

.servico-mini-card {
    border: 1px solid #dfe5f6;
    border-radius: 14px;
    background: #ffffff;
    box-shadow: 0 10px 28px rgba(26, 39, 74, 0.08);
}

.servico-mini-summary .badge {
    font-size: 0.74rem;
}

.servico-mini-sections .btn {
    border-radius: 999px;
    font-weight: 600;
    padding: 0.2rem 0.7rem;
}

.servico-mini-section {
    margin-top: 0.85rem;
    border: 1px dashed #d7def3;
    border-radius: 12px;
    padding: 0.85rem;
    background: #fbfcff;
}

.servico-mini-section-title {
    font-size: 0.76rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #5a678f;
    margin-bottom: 0.45rem;
}

#modalServicoDetalhes .modal-dialog {
    width: min(96vw, 1420px);
    max-width: min(96vw, 1420px);
}

#modalServicoDetalhes .modal-body {
    background: #fbfcff;
    overflow-x: hidden;
    padding: 1rem 1.15rem;
}

#modalServicoDetalhes .servico-detail-resumo-badge {
    border: 1px solid #d9e2fa;
    background: #fff;
    color: #4b587f;
    font-size: 0.74rem;
    font-weight: 600;
    border-radius: 999px;
    padding: 0.26rem 0.58rem;
}

#modalServicoDetalhes .servico-detail-table th {
    font-size: 0.72rem;
    color: #647192;
    text-transform: uppercase;
    letter-spacing: 0.02em;
    white-space: normal;
}

#modalServicoDetalhes .servico-detail-table td {
    vertical-align: top;
    white-space: normal;
    word-break: break-word;
    line-height: 1.25;
}

#modalServicoDetalhes .servico-detail-table {
    table-layout: fixed;
}

#modalServicoDetalhes .servico-detail-table col.col-parametro {
    width: 21%;
}

#modalServicoDetalhes .servico-detail-table col.col-descricao {
    width: 20%;
}

#modalServicoDetalhes .servico-detail-table col.col-unidade {
    width: 7%;
}

#modalServicoDetalhes .servico-detail-table col.col-formula {
    width: 21%;
}

#modalServicoDetalhes .servico-detail-table col.col-valor {
    width: 13%;
}

#modalServicoDetalhes .servico-detail-table col.col-minmax {
    width: 10%;
}

#modalServicoDetalhes .servico-detail-table col.col-origem {
    width: 8%;
}

#modalServicoDetalhes .servico-detail-table tbody tr:nth-child(odd) {
    background: rgba(245, 248, 255, 0.55);
}

#modalServicoDetalhes .servico-detail-table th:nth-child(5),
#modalServicoDetalhes .servico-detail-table td:nth-child(5) {
    background: rgba(237, 243, 255, 0.75);
}

#modalServicoDetalhes .servico-detail-value-input {
    min-width: 140px;
    width: 100%;
    text-align: right;
    font-weight: 700;
    color: #243257;
    background: #fff;
    border-color: #cfd8f7;
}

#modalServicoDetalhes .servico-detail-value-calc {
    display: inline-block;
    min-width: 130px;
    text-align: right;
    color: #1f2f59;
}

@media (max-width: 1366px) {
    #modalServicoDetalhes .modal-dialog {
        width: min(98vw, 1320px);
        max-width: min(98vw, 1320px);
    }
}

@media (max-width: 1024px) {
    #modalServicoDetalhes .modal-dialog {
        width: 98vw;
        max-width: 98vw;
    }

    #modalServicoDetalhes .servico-detail-table {
        font-size: 0.82rem;
    }
}

@media (max-width: 430px) {
    .precificacao-secao-pane {
        padding: 0.72rem;
    }

    .precificacao-secao-head {
        flex-direction: column;
        align-items: stretch;
        gap: 0.6rem;
        padding: 0.72rem;
    }

    #precificacaoSecoesTabs.precificacao-top-tabs .nav-link {
        font-size: 0.84rem;
        padding: 0.42rem 0.75rem;
    }
}

@media (max-width: 390px) {
    .precificacao-secao-title {
        font-size: 0.96rem;
    }
}

@media (max-width: 360px) {
    .precificacao-secao-desc {
        font-size: 0.78rem;
    }
}

@media (max-width: 320px) {
    #precificacaoSecoesTabs.precificacao-top-tabs .nav-link {
        font-size: 0.79rem;
    }
}
</style>
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php
$cfg = (array) ($configs ?? []);
$categorias = (array) ($parametrosCategorias ?? []);
$categoriasOverride = (array) ($categoriasOverride ?? []);
$servicosAtivos = (array) ($servicosAtivos ?? []);
$servicoOverrides = (array) ($servicoOverrides ?? []);
$resumos = (array) ($resumos ?? []);
$parametrosTableReady = (bool) ($parametrosTableReady ?? false);

$servicosAtivosMap = [];
foreach ($servicosAtivos as $servicoAtivo) {
    $sid = (int) ($servicoAtivo['id'] ?? 0);
    if ($sid <= 0) {
        continue;
    }
    $servicosAtivosMap[$sid] = $servicoAtivo;
}

$globalEncargos = [
    'peca' => (float) ($cfg['precificacao_peca_encargos_percentual'] ?? 0),
    'servico' => 0.0,
    'produto' => 0.0,
];
foreach (($categorias['servico'] ?? []) as $param) {
    if (($param['codigo'] ?? '') === 'servico_risco_percentual') {
        $globalEncargos['servico'] = (float) ($param['valor'] ?? 0);
        break;
    }
}
foreach (($categorias['produto'] ?? []) as $param) {
    if (($param['codigo'] ?? '') === 'produto_encargos_operacionais_percentual') {
        $globalEncargos['produto'] = (float) ($param['valor'] ?? 0);
        break;
    }
}

$labelsCategoria = [
    'peca' => 'Peça instalada',
    'servico' => 'Serviço técnico',
    'produto' => 'Produto (venda avulsa)',
];

$servicoDefaults = [
    'custos_fixos_mensais' => 0.0,
    'tecnicos_ativos' => 1.0,
    'horas_produtivas_dia' => 0.0,
    'dias_uteis_mes' => 1.0,
    'consumiveis_valor' => 0.0,
    'tempo_indireto_horas' => 0.0,
    'reserva_garantia_valor' => 0.0,
    'perdas_pequenas_valor' => 0.0,
    'margem' => 0.0,
    'taxa' => 0.0,
    'imposto' => 0.0,
    'tempo_desmontagem_min' => 0.0,
    'tempo_substituicao_min' => 0.0,
    'tempo_montagem_min' => 0.0,
    'tempo_teste_final_min' => 0.0,
    'risco' => 0.0,
    'preco_tabela' => 0.0,
];
foreach (($categorias['servico'] ?? []) as $param) {
    $codigo = (string) ($param['codigo'] ?? '');
    $valor = (float) ($param['valor'] ?? 0);
    if ($codigo === 'servico_custos_fixos_mensais') {
        $servicoDefaults['custos_fixos_mensais'] = $valor;
    } elseif ($codigo === 'servico_tecnicos_ativos') {
        $servicoDefaults['tecnicos_ativos'] = $valor;
    } elseif ($codigo === 'servico_horas_produtivas_dia') {
        $servicoDefaults['horas_produtivas_dia'] = $valor;
    } elseif ($codigo === 'servico_dias_uteis_mes') {
        $servicoDefaults['dias_uteis_mes'] = $valor;
    } elseif ($codigo === 'servico_consumiveis_valor') {
        $servicoDefaults['consumiveis_valor'] = $valor;
    } elseif ($codigo === 'servico_tempo_indireto_horas') {
        $servicoDefaults['tempo_indireto_horas'] = $valor;
    } elseif ($codigo === 'servico_reserva_garantia_valor') {
        $servicoDefaults['reserva_garantia_valor'] = $valor;
    } elseif ($codigo === 'servico_perdas_pequenas_valor') {
        $servicoDefaults['perdas_pequenas_valor'] = $valor;
    } elseif ($codigo === 'servico_margem_alvo_percentual') {
        $servicoDefaults['margem'] = $valor;
    } elseif ($codigo === 'servico_taxa_recebimento_percentual') {
        $servicoDefaults['taxa'] = $valor;
    } elseif ($codigo === 'servico_imposto_percentual') {
        $servicoDefaults['imposto'] = $valor;
    } elseif ($codigo === 'servico_tempo_desmontagem_min') {
        $servicoDefaults['tempo_desmontagem_min'] = $valor;
    } elseif ($codigo === 'servico_tempo_substituicao_min') {
        $servicoDefaults['tempo_substituicao_min'] = $valor;
    } elseif ($codigo === 'servico_tempo_montagem_min') {
        $servicoDefaults['tempo_montagem_min'] = $valor;
    } elseif ($codigo === 'servico_tempo_teste_final_min') {
        $servicoDefaults['tempo_teste_final_min'] = $valor;
    } elseif ($codigo === 'servico_risco_percentual') {
        $servicoDefaults['risco'] = $valor;
    } elseif ($codigo === 'servico_preco_tabela_referencia') {
        $servicoDefaults['preco_tabela'] = $valor;
    }
}

$formatValue = static function (array $row): string {
    $valor = (float) ($row['valor'] ?? 0);
    $tipo = (string) ($row['tipo_dado'] ?? 'valor');
    if ($tipo === 'percentual') {
        return number_format($valor, 2, ',', '.') . '%';
    }
    if ($tipo === 'horas') {
        return number_format($valor, 2, ',', '.') . 'h';
    }
    if ($tipo === 'minutos') {
        return number_format($valor, 0, ',', '.') . ' min';
    }
    if ($tipo === 'quantidade') {
        return number_format($valor, 2, ',', '.');
    }
    return number_format($valor, 2, ',', '.');
};

$formatInputValue = static function (array $row): string {
    return number_format((float) ($row['valor'] ?? 0), 4, '.', '');
};

$groupBySecao = static function (array $rows): array {
    $out = [];
    foreach ($rows as $row) {
        $secao = (string) ($row['secao'] ?? 'geral');
        if (!isset($out[$secao])) {
            $out[$secao] = [];
        }
        $out[$secao][] = $row;
    }
    return $out;
};
?>

<div class="page-header d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="bi bi-calculator me-2"></i>Precificação - Configuração</h2>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('precificacao')" title="Ajuda sobre Precificação">
            <i class="bi bi-question-circle me-1"></i>Ajuda
        </button>
        <a href="<?= base_url('precificacao/simulador') ?>" class="btn btn-outline-primary">
            <i class="bi bi-graph-up me-1"></i>Simulador
        </a>
        <a href="<?= base_url('orcamentos') ?>" class="btn btn-outline-secondary" data-back-default="<?= base_url('orcamentos') ?>">
            <i class="bi bi-arrow-left me-1"></i>Voltar
        </a>
    </div>
</div>

<?php if (!$parametrosTableReady): ?>
    <div class="alert alert-warning border-warning-subtle mb-3">
        <strong>Atenção:</strong> a tabela <code>precificacao_parametros</code> ainda não existe nesta base.
        Execute as migrações para habilitar a configuração detalhada.
    </div>
<?php endif; ?>

<?php if ($parametrosTableReady): ?>
<div class="card glass-card precificacao-shell mb-3">
    <div class="card-body">
        <form action="<?= base_url('precificacao/configuracao/salvar') ?>" method="POST" id="formPrecificacaoDetalhada">
            <?= csrf_field() ?>

            <div class="alert alert-info border-info-subtle">
                <strong>Modo detalhado:</strong> esta tela consolida todos os parâmetros que compõem o preço de
                peça instalada, serviço técnico e venda de produto. Campos calculados ficam travados e são
                recalculados automaticamente ao salvar.
            </div>

            <ul class="nav nav-pills mb-3 flex-wrap precificacao-top-tabs" id="precificacaoSecoesTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="tab-secao-categorias" data-bs-toggle="tab" data-bs-target="#pane-secao-categorias" type="button" role="tab" aria-controls="pane-secao-categorias" aria-selected="true">
                        <i class="bi bi-diagram-3 me-1"></i>Overrides por categoria
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-secao-parametros" data-bs-toggle="tab" data-bs-target="#pane-secao-parametros" type="button" role="tab" aria-controls="pane-secao-parametros" aria-selected="false">
                        <i class="bi bi-sliders me-1"></i>Parametros detalhados
                    </button>
                </li>
            </ul>

            <div id="precificacaoUnsavedIndicator" class="alert alert-warning border-warning-subtle py-2 px-3 mb-3 d-none">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <strong class="me-1">Alteracoes não salvas:</strong>
                    <span id="badge-unsaved-categorias" class="badge bg-light text-muted border">Categorias: sem alteração</span>
                    <span id="badge-unsaved-parametros" class="badge bg-light text-muted border">Parâmetros: sem alteração</span>
                </div>
            </div>

            <div class="tab-content" id="precificacaoSecoesTabContent">
                <div class="tab-pane fade show active" id="pane-secao-categorias" role="tabpanel" aria-labelledby="tab-secao-categorias">
            <div class="precificacao-secao-pane secao-categorias">
                <div class="precificacao-secao-head">
                    <div>
                        <div class="precificacao-secao-kicker">Seção 1</div>
                        <h3 class="precificacao-secao-title">Overrides por categoria</h3>
                        <p class="precificacao-secao-desc">Configure encargos e margem específicos por tipo/categoria. Se não houver override, o fallback global é aplicado automaticamente.</p>
                    </div>
                    <span class="precificacao-secao-chip"><i class="bi bi-diagram-3"></i>Categorias</span>
                </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center">
                    <div>
                        <strong>Overrides por categoria</strong>
                        <div class="text-muted small">Defina encargos e margem por categoria. Se não existir override, o sistema usa o valor global.</div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddCategoriaOverride">
                        <i class="bi bi-plus-lg me-1"></i>Adicionar categoria
                    </button>
                </div>
                <div class="card-body">
                    <?php
                    $overrideTipos = [
                        'peca' => 'Peças',
                        'servico' => 'Serviços',
                        'produto' => 'Produtos',
                    ];
                    $overrideCategoriaPlaceholder = [
                        'peca' => 'Ex.: Tela, Bateria, Conector',
                        'servico' => 'Ex.: Smartphone, Notebook, Solda',
                        'produto' => 'Ex.: Acessorio, Carregador, Cabo',
                    ];
                    $overrideCategoriaColuna = [
                        'peca' => 'Categoria da peça',
                        'servico' => 'Categoria do serviço',
                        'produto' => 'Categoria do produto',
                    ];
                    $overrideEncargoColuna = [
                        'peca' => 'Encargos (%)',
                        'servico' => 'Risco adicional (%)',
                        'produto' => 'Encargos operacionais (%)',
                    ];
                    $overrideMargemColuna = [
                        'peca' => 'Margem (%)',
                        'servico' => 'Margem alvo (%)',
                        'produto' => 'Margem (%)',
                    ];
                    ?>
                    <ul class="nav nav-pills flex-wrap override-tipo-tabs" id="overrideTipoTabs" role="tablist">
                        <?php $overrideTabIndex = 0; ?>
                        <?php foreach ($overrideTipos as $tipoOverride => $labelOverride): ?>
                            <?php $overrideActive = $overrideTabIndex === 0; ?>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link <?= $overrideActive ? 'active' : '' ?>" id="tab-override-<?= esc($tipoOverride) ?>" data-bs-toggle="tab" data-bs-target="#pane-override-<?= esc($tipoOverride) ?>" type="button" role="tab" aria-controls="pane-override-<?= esc($tipoOverride) ?>" aria-selected="<?= $overrideActive ? 'true' : 'false' ?>" data-override-tipo-tab="<?= esc($tipoOverride) ?>">
                                    <?= esc($labelOverride) ?>
                                </button>
                            </li>
                            <?php $overrideTabIndex++; ?>
                        <?php endforeach; ?>
                    </ul>

                    <div class="tab-content" id="overrideTipoTabContent">
                        <?php $overrideTabIndex = 0; ?>
                        <?php foreach ($overrideTipos as $tipoOverride => $labelOverride): ?>
                            <?php
                            $overrideActive = $overrideTabIndex === 0;
                            $rowsOverride = $tipoOverride === 'servico'
                                ? $servicoOverrides
                                : (array) ($categoriasOverride[$tipoOverride] ?? []);
                            $globalEncargoTipo = (float) ($globalEncargos[$tipoOverride] ?? 0);
                            $colCategoria = $overrideCategoriaColuna[$tipoOverride] ?? 'Categoria';
                            $colEncargo = $overrideEncargoColuna[$tipoOverride] ?? 'Encargos (%)';
                            $colMargem = $overrideMargemColuna[$tipoOverride] ?? 'Margem (%)';
                            $placeholderCategoria = $overrideCategoriaPlaceholder[$tipoOverride] ?? 'Ex.: Categoria';
                            ?>
                            <div class="tab-pane fade <?= $overrideActive ? 'show active' : '' ?>" id="pane-override-<?= esc($tipoOverride) ?>" role="tabpanel" aria-labelledby="tab-override-<?= esc($tipoOverride) ?>" data-override-tipo-pane="<?= esc($tipoOverride) ?>">
                                <?php if ($tipoOverride === 'servico'): ?>
                                    <div class="alert alert-light border small mb-3">
                                        No tipo <strong>Serviços</strong>, cadastre apenas serviços <strong>padronizados/específicos</strong>.
                                        Serviços genéricos continuam no <strong>padrão global</strong> automaticamente.
                                        Os campos abaixo seguem os mesmos blocos técnicos: capacidade, custos diretos, margem/taxas, tempo técnico e resultado.
                                    </div>
                                <?php endif; ?>
                                <?php if ($tipoOverride === 'servico'): ?>
                                    <div class="table-responsive servico-override-list">
                                        <table class="table table-sm align-middle mb-0" id="tableCategoriaOverride-servico" data-override-table="servico" data-placeholder-colspan="1">
                                            <thead>
                                            <tr>
                                                <th>Serviços específicos (mini-cards)</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <?php if ($rowsOverride): ?>
                                                <?php foreach ($rowsOverride as $rowIndex => $row): ?>
                                                    <?php
                                                    $servicoId = (int) ($row['servico_id'] ?? 0);
                                                    $servicoAtivo = $servicosAtivosMap[$servicoId] ?? null;
                                                    $custoHoraRow = (float) ($row['custo_hora_produtiva'] ?? 0);
                                                    $custosDiretosRow = (float) ($row['custos_diretos_total'] ?? 0);
                                                    $margemRow = (float) ($row['margem_percentual'] ?? 0);
                                                    $taxaRow = (float) ($row['taxa_recebimento_percentual'] ?? 0);
                                                    $impostoRow = (float) ($row['imposto_percentual'] ?? 0);
                                                    $tempoRow = (float) ($row['tempo_tecnico_horas'] ?? 0);
                                                    $riscoRow = (float) ($row['risco_percentual'] ?? 0);
                                                    $precoTabelaRow = (float) ($row['preco_tabela_referencia'] ?? 0);
                                                    $custosFixosMensaisRow = (float) (($row['custos_fixos_mensais'] ?? null) !== null ? $row['custos_fixos_mensais'] : ($servicoDefaults['custos_fixos_mensais'] ?? 0));
                                                    $tecnicosAtivosRow = (float) (($row['tecnicos_ativos'] ?? null) !== null ? $row['tecnicos_ativos'] : ($servicoDefaults['tecnicos_ativos'] ?? 1));
                                                    $horasProdutivasDiaRow = (float) (($row['horas_produtivas_dia'] ?? null) !== null ? $row['horas_produtivas_dia'] : ($servicoDefaults['horas_produtivas_dia'] ?? 0));
                                                    $diasUteisMesRow = (float) (($row['dias_uteis_mes'] ?? null) !== null ? $row['dias_uteis_mes'] : ($servicoDefaults['dias_uteis_mes'] ?? 1));
                                                    $consumiveisRow = (float) (($row['consumiveis_valor'] ?? null) !== null ? $row['consumiveis_valor'] : ($servicoDefaults['consumiveis_valor'] ?? 0));
                                                    $tempoIndiretoHorasRow = (float) (($row['tempo_indireto_horas'] ?? null) !== null ? $row['tempo_indireto_horas'] : ($servicoDefaults['tempo_indireto_horas'] ?? 0));
                                                    $reservaGarantiaValorRow = (float) (($row['reserva_garantia_valor'] ?? null) !== null ? $row['reserva_garantia_valor'] : ($servicoDefaults['reserva_garantia_valor'] ?? 0));
                                                    $perdasPequenasValorRow = (float) (($row['perdas_pequenas_valor'] ?? null) !== null ? $row['perdas_pequenas_valor'] : ($servicoDefaults['perdas_pequenas_valor'] ?? 0));
                                                    $tempoDesmontagemMinRow = (float) (($row['tempo_desmontagem_min'] ?? null) !== null ? $row['tempo_desmontagem_min'] : ($servicoDefaults['tempo_desmontagem_min'] ?? 0));
                                                    $tempoSubstituicaoMinRow = (float) (($row['tempo_substituicao_min'] ?? null) !== null ? $row['tempo_substituicao_min'] : ($servicoDefaults['tempo_substituicao_min'] ?? 0));
                                                    $tempoMontagemMinRow = (float) (($row['tempo_montagem_min'] ?? null) !== null ? $row['tempo_montagem_min'] : ($servicoDefaults['tempo_montagem_min'] ?? 0));
                                                    $tempoTesteFinalMinRow = (float) (($row['tempo_teste_final_min'] ?? null) !== null ? $row['tempo_teste_final_min'] : ($servicoDefaults['tempo_teste_final_min'] ?? 0));
                                                    $baseCalc = ($tempoRow * $custoHoraRow) + $custosDiretosRow;
                                                    $custoTotalCalc = $baseCalc + ($baseCalc * ($riscoRow / 100));
                                                    $divisorCalc = 1 - (($margemRow + $taxaRow + $impostoRow) / 100);
                                                    if ($divisorCalc <= 0.01) {
                                                        $divisorCalc = 0.01;
                                                    }
                                                    $precoMinimoCalc = $custoTotalCalc / $divisorCalc;
                                                    $precoRecomendadoCalc = max($precoMinimoCalc, $precoTabelaRow);
                                                    $infoPrefix = 'servico-info-' . (int) ($row['id'] ?? 0) . '-' . (int) $rowIndex;
                                                    ?>
                                                    <tr class="servico-override-row" data-override-row="servico">
                                                        <td class="p-0 border-0">
                                                            <div class="servico-mini-card">
                                                                <div class="card-body p-3">
                                                                    <input type="hidden" name="servico_override_id[]" value="<?= (int) ($row['id'] ?? 0) ?>">
                                                                    <input type="hidden" name="servico_override_custos_fixos_mensais[]" value="<?= esc(number_format($custosFixosMensaisRow, 2, '.', '')) ?>">
                                                                    <input type="hidden" name="servico_override_tecnicos_ativos[]" value="<?= esc(number_format($tecnicosAtivosRow, 2, '.', '')) ?>">
                                                                    <input type="hidden" name="servico_override_horas_produtivas_dia[]" value="<?= esc(number_format($horasProdutivasDiaRow, 2, '.', '')) ?>">
                                                                    <input type="hidden" name="servico_override_dias_uteis_mes[]" value="<?= esc(number_format($diasUteisMesRow, 2, '.', '')) ?>">
                                                                    <input type="hidden" name="servico_override_consumiveis_valor[]" value="<?= esc(number_format($consumiveisRow, 2, '.', '')) ?>">
                                                                    <input type="hidden" name="servico_override_tempo_indireto_horas[]" value="<?= esc(number_format($tempoIndiretoHorasRow, 2, '.', '')) ?>">
                                                                    <input type="hidden" name="servico_override_reserva_garantia_valor[]" value="<?= esc(number_format($reservaGarantiaValorRow, 2, '.', '')) ?>">
                                                                    <input type="hidden" name="servico_override_perdas_pequenas_valor[]" value="<?= esc(number_format($perdasPequenasValorRow, 2, '.', '')) ?>">
                                                                    <input type="hidden" name="servico_override_tempo_desmontagem_min[]" value="<?= esc(number_format($tempoDesmontagemMinRow, 2, '.', '')) ?>">
                                                                    <input type="hidden" name="servico_override_tempo_substituicao_min[]" value="<?= esc(number_format($tempoSubstituicaoMinRow, 2, '.', '')) ?>">
                                                                    <input type="hidden" name="servico_override_tempo_montagem_min[]" value="<?= esc(number_format($tempoMontagemMinRow, 2, '.', '')) ?>">
                                                                    <input type="hidden" name="servico_override_tempo_teste_final_min[]" value="<?= esc(number_format($tempoTesteFinalMinRow, 2, '.', '')) ?>">
                                                                    <div class="servico-mini-head d-flex flex-wrap align-items-start justify-content-between gap-2">
                                                                        <div class="flex-grow-1">
                                                                            <label class="form-label small text-muted mb-1">Serviço específico</label>
                                                                            <select name="servico_override_servico_id[]" class="form-select form-select-sm servico-override-input">
                                                                                <option value="">Selecione o serviço...</option>
                                                                                <?php foreach ($servicosAtivos as $servicoOption): ?>
                                                                                    <?php $optId = (int) ($servicoOption['id'] ?? 0); ?>
                                                                                    <option value="<?= $optId ?>" <?= $optId === $servicoId ? 'selected' : '' ?>>
                                                                                        <?= esc((string) ($servicoOption['nome'] ?? '')) ?><?= !empty($servicoOption['tipo_equipamento']) ? ' (' . esc((string) $servicoOption['tipo_equipamento']) . ')' : '' ?>
                                                                                    </option>
                                                                                <?php endforeach; ?>
                                                                            </select>
                                                                            <?php if (!$servicoAtivo): ?>
                                                                                <div class="text-danger small mt-1">Serviço não encontrado/encerrado.</div>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                        <div class="text-end">
                                                                            <button type="button" class="btn btn-sm btn-outline-secondary btn-servico-use-global me-1" title="Remover override e usar padrão global">
                                                                                <i class="bi bi-arrow-counterclockwise"></i>
                                                                            </button>
                                                                            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row" title="Remover linha">
                                                                                <i class="bi bi-trash"></i>
                                                                            </button>
                                                                        </div>
                                                                    </div>
                                                                    <div class="servico-mini-summary mt-2 d-flex flex-wrap gap-2">
                                                                        <span class="badge bg-light text-dark border">Custo total: <strong class="servico-override-custo-total"><?= esc(number_format($custoTotalCalc, 2, ',', '.')) ?></strong></span>
                                                                        <span class="badge bg-light text-dark border">Divisor técnico: <strong class="servico-override-divisor"><?= esc(number_format($divisorCalc, 4, ',', '.')) ?></strong></span>
                                                                        <span class="badge bg-light text-dark border">Preço mínimo: <strong class="servico-override-preco-minimo"><?= esc(number_format($precoMinimoCalc, 2, ',', '.')) ?></strong></span>
                                                                        <span class="badge bg-light text-dark border">Preço recomendado: <strong class="servico-override-preco-recomendado"><?= esc(number_format($precoRecomendadoCalc, 2, ',', '.')) ?></strong></span>
                                                                    </div>
                                                                    <div class="servico-mini-sections mt-3 d-flex flex-wrap gap-2">
                                                                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#<?= esc($infoPrefix) ?>-sec-capacidade" aria-expanded="false" aria-controls="<?= esc($infoPrefix) ?>-sec-capacidade">Capacidade</button>
                                                                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#<?= esc($infoPrefix) ?>-sec-custos" aria-expanded="false" aria-controls="<?= esc($infoPrefix) ?>-sec-custos">Custos Diretos</button>
                                                                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#<?= esc($infoPrefix) ?>-sec-margem" aria-expanded="false" aria-controls="<?= esc($infoPrefix) ?>-sec-margem">Margem/Taxas</button>
                                                                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#<?= esc($infoPrefix) ?>-sec-tempo" aria-expanded="false" aria-controls="<?= esc($infoPrefix) ?>-sec-tempo">Tempo Técnico</button>
                                                                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#<?= esc($infoPrefix) ?>-sec-resultado" aria-expanded="false" aria-controls="<?= esc($infoPrefix) ?>-sec-resultado">Resultado</button>
                                                                    </div>
                                                                    <div class="collapse servico-mini-section" id="<?= esc($infoPrefix) ?>-sec-capacidade">
                                                                        <div class="servico-mini-section-title">Capacidade</div>
                                                                        <label class="form-label small text-muted mb-1">Custo hora produtiva (R$)</label>
                                                                        <div class="input-group input-group-sm">
                                                                            <input type="number" step="0.01" min="0" max="100000" name="servico_override_custo_hora[]" class="form-control form-control-sm servico-override-input" value="<?= esc(number_format($custoHoraRow, 2, '.', '')) ?>" placeholder="Ex.: 12.50">
                                                                            <button type="button" class="btn btn-outline-secondary btn-servico-detail" data-servico-detail="capacidade" title="Detalhar Capacidade">
                                                                                <i class="bi bi-sliders2"></i>
                                                                            </button>
                                                                        </div>
                                                                        <div class="servico-info-actions mt-2">
                                                                            <button type="button" class="btn btn-outline-secondary btn-servico-info" data-bs-toggle="collapse" data-bs-target="#<?= esc($infoPrefix) ?>-capacidade-formula" aria-expanded="false" aria-controls="<?= esc($infoPrefix) ?>-capacidade-formula">
                                                                                <i class="bi bi-info-circle me-1"></i>Fórmula
                                                                            </button>
                                                                            <button type="button" class="btn btn-outline-secondary btn-servico-info" data-bs-toggle="collapse" data-bs-target="#<?= esc($infoPrefix) ?>-capacidade-ref" aria-expanded="false" aria-controls="<?= esc($infoPrefix) ?>-capacidade-ref">
                                                                                <i class="bi bi-info-circle me-1"></i>Referência
                                                                            </button>
                                                                        </div>
                                                                        <div class="collapse servico-info-collapse mt-1" id="<?= esc($infoPrefix) ?>-capacidade-formula">
                                                                            <div class="servico-info-note">CustoHora = CustosFixosMensais / HorasProdutivasMensais.</div>
                                                                        </div>
                                                                        <div class="collapse servico-info-collapse mt-1" id="<?= esc($infoPrefix) ?>-capacidade-ref">
                                                                            <div class="servico-info-note">Usa os mesmos componentes da seção Capacidade dos parâmetros globais.</div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="collapse servico-mini-section" id="<?= esc($infoPrefix) ?>-sec-custos">
                                                                        <div class="servico-mini-section-title">Custos Diretos</div>
                                                                        <label class="form-label small text-muted mb-1">Custos diretos totais (R$)</label>
                                                                        <div class="input-group input-group-sm mb-2">
                                                                            <input type="number" step="0.01" min="0" max="100000" name="servico_override_custos_diretos[]" class="form-control form-control-sm servico-override-input" value="<?= esc(number_format($custosDiretosRow, 2, '.', '')) ?>" placeholder="Ex.: 18.90">
                                                                            <button type="button" class="btn btn-outline-secondary btn-servico-detail" data-servico-detail="custos_diretos" title="Detalhar Custos Diretos">
                                                                                <i class="bi bi-sliders2"></i>
                                                                            </button>
                                                                        </div>
                                                                        <label class="form-label small text-muted mb-1">Risco percentual adicional (%)</label>
                                                                        <input type="number" step="0.01" min="0" max="100" name="servico_override_risco[]" class="form-control form-control-sm servico-override-input" value="<?= esc(number_format($riscoRow, 2, '.', '')) ?>" placeholder="Ex.: 2.50">
                                                                        <div class="servico-info-actions mt-2">
                                                                            <button type="button" class="btn btn-outline-secondary btn-servico-info" data-bs-toggle="collapse" data-bs-target="#<?= esc($infoPrefix) ?>-custos-formula" aria-expanded="false" aria-controls="<?= esc($infoPrefix) ?>-custos-formula">
                                                                                <i class="bi bi-info-circle me-1"></i>Fórmula
                                                                            </button>
                                                                        </div>
                                                                        <div class="collapse servico-info-collapse mt-1" id="<?= esc($infoPrefix) ?>-custos-formula">
                                                                            <div class="servico-info-note">CustoTotal = (TempoTecnico x CustoHora + CustosDiretos) + Risco percentual.</div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="collapse servico-mini-section" id="<?= esc($infoPrefix) ?>-sec-margem">
                                                                        <div class="servico-mini-section-title">Margem/Taxas</div>
                                                                        <label class="form-label small text-muted mb-1">Margem alvo (%)</label>
                                                                        <div class="input-group input-group-sm mb-2">
                                                                            <input type="number" step="0.01" min="0" max="300" name="servico_override_margem[]" class="form-control form-control-sm servico-override-input" value="<?= esc(number_format($margemRow, 2, '.', '')) ?>" placeholder="Ex.: 25.00">
                                                                            <button type="button" class="btn btn-outline-secondary btn-servico-detail" data-servico-detail="margem_taxas" title="Detalhar Margem e Taxas">
                                                                                <i class="bi bi-sliders2"></i>
                                                                            </button>
                                                                        </div>
                                                                        <label class="form-label small text-muted mb-1">Taxa de recebimento (%)</label>
                                                                        <input type="number" step="0.01" min="0" max="100" name="servico_override_taxa[]" class="form-control form-control-sm servico-override-input mb-2" value="<?= esc(number_format($taxaRow, 2, '.', '')) ?>" placeholder="Ex.: 3.50">
                                                                        <label class="form-label small text-muted mb-1">Imposto (%)</label>
                                                                        <input type="number" step="0.01" min="0" max="100" name="servico_override_imposto[]" class="form-control form-control-sm servico-override-input" value="<?= esc(number_format($impostoRow, 2, '.', '')) ?>" placeholder="Ex.: 0.00">
                                                                        <div class="servico-info-actions mt-2">
                                                                            <button type="button" class="btn btn-outline-secondary btn-servico-info" data-bs-toggle="collapse" data-bs-target="#<?= esc($infoPrefix) ?>-margem-formula" aria-expanded="false" aria-controls="<?= esc($infoPrefix) ?>-margem-formula">
                                                                                <i class="bi bi-info-circle me-1"></i>Fórmula do divisor
                                                                            </button>
                                                                        </div>
                                                                        <div class="collapse servico-info-collapse mt-1" id="<?= esc($infoPrefix) ?>-margem-formula">
                                                                            <div class="servico-info-note">DivisorTecnico = 1 - (Margem + Taxa + Imposto) / 100.</div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="collapse servico-mini-section" id="<?= esc($infoPrefix) ?>-sec-tempo">
                                                                        <div class="servico-mini-section-title">Tempo Técnico</div>
                                                                        <label class="form-label small text-muted mb-1">Tempo técnico total (h)</label>
                                                                        <div class="input-group input-group-sm">
                                                                            <input type="number" step="0.01" min="0" max="999" name="servico_override_tempo_tecnico[]" class="form-control form-control-sm servico-override-input" value="<?= esc(number_format($tempoRow, 2, '.', '')) ?>" placeholder="Ex.: 1.50">
                                                                            <button type="button" class="btn btn-outline-secondary btn-servico-detail" data-servico-detail="tempo_tecnico" title="Detalhar Tempo Técnico">
                                                                                <i class="bi bi-sliders2"></i>
                                                                            </button>
                                                                        </div>
                                                                        <div class="servico-info-actions mt-2">
                                                                            <button type="button" class="btn btn-outline-secondary btn-servico-info" data-bs-toggle="collapse" data-bs-target="#<?= esc($infoPrefix) ?>-tempo-ref" aria-expanded="false" aria-controls="<?= esc($infoPrefix) ?>-tempo-ref">
                                                                                <i class="bi bi-info-circle me-1"></i>Referência
                                                                            </button>
                                                                        </div>
                                                                        <div class="collapse servico-info-collapse mt-1" id="<?= esc($infoPrefix) ?>-tempo-ref">
                                                                            <div class="servico-info-note">Segue a mesma estrutura da seção Tempo Técnico dos parâmetros globais.</div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="collapse servico-mini-section" id="<?= esc($infoPrefix) ?>-sec-resultado">
                                                                        <div class="servico-mini-section-title">Resultado</div>
                                                                        <label class="form-label small text-muted mb-1">Preço tabela de referência (R$)</label>
                                                                        <div class="input-group input-group-sm mb-2">
                                                                            <input type="number" step="0.01" min="0" max="999999" name="servico_override_preco_tabela[]" class="form-control form-control-sm servico-override-input" value="<?= esc(number_format($precoTabelaRow, 2, '.', '')) ?>" placeholder="Ex.: 99.00">
                                                                            <button type="button" class="btn btn-outline-secondary btn-servico-detail" data-servico-detail="resultado" title="Detalhar Resultado">
                                                                                <i class="bi bi-sliders2"></i>
                                                                            </button>
                                                                        </div>
                                                                        <div class="text-muted small">Custo total: <span class="fw-semibold servico-override-custo-total"><?= esc(number_format($custoTotalCalc, 2, ',', '.')) ?></span></div>
                                                                        <div class="text-muted small">Divisor técnico: <span class="fw-semibold servico-override-divisor"><?= esc(number_format($divisorCalc, 4, ',', '.')) ?></span></div>
                                                                        <div class="text-muted small">Preço mínimo técnico: <span class="fw-semibold servico-override-preco-minimo"><?= esc(number_format($precoMinimoCalc, 2, ',', '.')) ?></span></div>
                                                                        <div class="text-muted small">Preço recomendado: <span class="fw-semibold servico-override-preco-recomendado"><?= esc(number_format($precoRecomendadoCalc, 2, ',', '.')) ?></span></div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr class="row-empty-placeholder">
                                                    <td colspan="1" class="text-center text-muted small py-4">
                                                        Nenhum serviço específico configurado.
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm align-middle mb-0" id="tableCategoriaOverride-<?= esc($tipoOverride) ?>" data-override-table="<?= esc($tipoOverride) ?>" data-placeholder-colspan="4">
                                            <thead>
                                            <tr>
                                                <th><?= esc($colCategoria) ?></th>
                                                <th style="width: 220px;"><?= esc($colEncargo) ?></th>
                                                <th style="width: 160px;"><?= esc($colMargem) ?></th>
                                                <th style="width: 80px;" class="text-center">Ação</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <?php if ($rowsOverride): ?>
                                                <?php foreach ($rowsOverride as $row): ?>
                                                    <?php $encargoAtual = (float) ($row['encargos_percentual'] ?? 0); ?>
                                                    <?php $overrideAtivo = abs($encargoAtual - $globalEncargoTipo) > 0.001; ?>
                                                    <tr data-override-row="<?= esc($tipoOverride) ?>">
                                                        <td data-label="<?= esc($colCategoria) ?>">
                                                            <input type="hidden" name="categoria_id[]" value="<?= (int) ($row['id'] ?? 0) ?>">
                                                            <input type="hidden" name="categoria_tipo[]" value="<?= esc($tipoOverride) ?>">
                                                            <input type="text" name="categoria_nome[]" class="form-control form-control-sm" value="<?= esc((string) ($row['categoria_nome'] ?? '')) ?>" placeholder="<?= esc($placeholderCategoria) ?>">
                                                        </td>
                                                        <td data-label="<?= esc($colEncargo) ?>">
                                                            <div class="input-group input-group-sm">
                                                                <input type="number" step="0.01" min="0" max="300" name="categoria_encargos[]" class="form-control form-control-sm categoria-encargo-input" value="<?= esc(number_format($encargoAtual, 2, '.', '')) ?>" data-global-encargo="<?= esc(number_format($globalEncargoTipo, 2, '.', '')) ?>">
                                                                <button type="button" class="btn btn-outline-secondary btn-encargo-config" data-categoria-id="<?= (int) ($row['id'] ?? 0) ?>" data-categoria-tipo="<?= esc($tipoOverride) ?>" title="Configurar componentes">
                                                                    <i class="bi bi-sliders2"></i>
                                                                </button>
                                                            </div>
                                                            <div class="mt-1">
                                                                <span class="badge <?= $overrideAtivo ? 'bg-primary' : 'bg-light text-muted border' ?> badge-override-status">
                                                                    <?= $overrideAtivo ? 'override ativo' : 'global' ?>
                                                                </span>
                                                            </div>
                                                        </td>
                                                        <td data-label="<?= esc($colMargem) ?>">
                                                            <input type="number" step="0.01" min="0" max="300" name="categoria_margem[]" class="form-control form-control-sm" value="<?= esc(number_format((float) ($row['margem_percentual'] ?? 0), 2, '.', '')) ?>">
                                                        </td>
                                                        <td data-label="Ação" class="text-center">
                                                            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr class="row-empty-placeholder">
                                                    <td colspan="4" class="text-center text-muted small py-4">
                                                        Nenhuma categoria cadastrada em <?= esc($labelOverride) ?>.
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php $overrideTabIndex++; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            </div>

                </div>
                <div class="tab-pane fade" id="pane-secao-parametros" role="tabpanel" aria-labelledby="tab-secao-parametros">
            <div class="precificacao-secao-pane secao-parametros">
                <div class="precificacao-secao-head">
                    <div>
                        <div class="precificacao-secao-kicker">Seção 2</div>
                        <h3 class="precificacao-secao-title">Parâmetros detalhados</h3>
                        <p class="precificacao-secao-desc">Ajuste as bases técnicas de peça, serviço e produto em abas internas, com resultados calculados em tempo real no salvamento.</p>
                    </div>
                    <span class="precificacao-secao-chip"><i class="bi bi-sliders"></i>Parâmetros</span>
                </div>

            <?php $tabIndex = 0; ?>
            <ul class="nav nav-tabs mb-3" role="tablist">
                <?php foreach ($categorias as $categoria => $rows): ?>
                    <?php $isActive = $tabIndex === 0; ?>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?= $isActive ? 'active' : '' ?>" id="tab-<?= esc($categoria) ?>" data-bs-toggle="tab" data-bs-target="#pane-<?= esc($categoria) ?>" type="button" role="tab" aria-controls="pane-<?= esc($categoria) ?>" aria-selected="<?= $isActive ? 'true' : 'false' ?>">
                            <?= esc($labelsCategoria[$categoria] ?? ucfirst($categoria)) ?>
                        </button>
                    </li>
                    <?php $tabIndex++; ?>
                <?php endforeach; ?>
            </ul>

            <div class="tab-content">
                <?php $tabIndex = 0; ?>
                <?php foreach ($categorias as $categoria => $rows): ?>
                    <?php
                    $isActive = $tabIndex === 0;
                    $titulo = $labelsCategoria[$categoria] ?? ucfirst($categoria);
                    $resumoCategoria = (array) ($resumos[$categoria] ?? []);
                    $sections = $groupBySecao($rows);
                    ?>
                    <div class="tab-pane fade <?= $isActive ? 'show active' : '' ?>" id="pane-<?= esc($categoria) ?>" role="tabpanel" aria-labelledby="tab-<?= esc($categoria) ?>">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center">
                                <div>
                                    <strong><?= esc($titulo) ?></strong>
                                    <div class="text-muted small">Parâmetros detalhados para <?= esc($titulo) ?>.</div>
                                </div>
                                <?php if ($resumoCategoria): ?>
                                    <div class="d-flex flex-wrap gap-2">
                                        <?php foreach ($resumoCategoria as $badge): ?>
                                            <span class="badge bg-light text-dark border">
                                                <?= esc((string) ($badge['label'] ?? '')) ?>:
                                                <strong><?= esc((string) ($badge['valor'] ?? '0')) ?></strong>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <?php $secIndex = 0; ?>
                                <ul class="nav nav-pills mb-3 flex-wrap" role="tablist">
                                    <?php foreach ($sections as $secao => $secRows): ?>
                                        <?php $secActive = $secIndex === 0; ?>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link <?= $secActive ? 'active' : '' ?>" id="tab-<?= esc($categoria) ?>-<?= esc($secao) ?>" data-bs-toggle="tab" data-bs-target="#pane-<?= esc($categoria) ?>-<?= esc($secao) ?>" type="button" role="tab" aria-controls="pane-<?= esc($categoria) ?>-<?= esc($secao) ?>" aria-selected="<?= $secActive ? 'true' : 'false' ?>">
                                                <?= esc(ucwords(str_replace('_', ' ', $secao))) ?>
                                            </button>
                                        </li>
                                        <?php $secIndex++; ?>
                                    <?php endforeach; ?>
                                </ul>

                                <div class="tab-content">
                                    <?php $secIndex = 0; ?>
                                    <?php foreach ($sections as $secao => $secRows): ?>
                                        <?php $secActive = $secIndex === 0; ?>
                                        <div class="tab-pane fade <?= $secActive ? 'show active' : '' ?>" id="pane-<?= esc($categoria) ?>-<?= esc($secao) ?>" role="tabpanel" aria-labelledby="tab-<?= esc($categoria) ?>-<?= esc($secao) ?>">
                                            <div class="table-responsive">
                                                <table class="table table-sm align-middle">
                                                    <thead>
                                                    <tr>
                                                        <th>Parametro</th>
                                                        <th style="min-width: 260px;">Descrição</th>
                                                        <th class="text-center" style="width: 120px;">Unidade</th>
                                                        <th style="min-width: 220px;">Fórmula</th>
                                                        <th style="width: 160px;">Valor</th>
                                                        <th style="width: 140px;">Min / Max</th>
                                                        <th style="width: 120px;">Origem</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <?php foreach ($secRows as $row): ?>
                                                        <?php
                                                        $editavel = ((int) ($row['editavel'] ?? 0) === 1);
                                                        $minimo = isset($row['minimo']) && $row['minimo'] !== null ? number_format((float) $row['minimo'], 2, ',', '.') : '-';
                                                        $maximo = isset($row['maximo']) && $row['maximo'] !== null ? number_format((float) $row['maximo'], 2, ',', '.') : '-';
                                                        $tipo = (string) ($row['tipo_dado'] ?? 'valor');
                                                        $step = $tipo === 'percentual' ? '0.01' : '0.01';
                                                        ?>
                                                        <tr>
                                                            <td data-label="Parametro">
                                                                <strong><?= esc((string) ($row['nome'] ?? '')) ?></strong>
                                                                <div class="text-muted small"><?= esc((string) ($row['codigo'] ?? '')) ?></div>
                                                            </td>
                                                            <td data-label="Descrição"><?= esc((string) ($row['descricao'] ?? '-')) ?></td>
                                                            <td data-label="Unidade" class="text-center"><?= esc((string) ($row['unidade'] ?? '-')) ?></td>
                                                            <td data-label="Fórmula">
                                                                <span class="text-muted small"><?= esc((string) ($row['formula'] ?? '-')) ?></span>
                                                            </td>
                                                            <td data-label="Valor">
                                                                <?php if ($editavel): ?>
                                                                    <input type="hidden" name="parametro_id[]" value="<?= (int) ($row['id'] ?? 0) ?>">
                                                                    <input type="number" step="<?= $step ?>" class="form-control form-control-sm" name="parametro_valor[]" value="<?= esc($formatInputValue($row)) ?>">
                                                                <?php else: ?>
                                                                    <span class="fw-semibold"><?= esc($formatValue($row)) ?></span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td data-label="Min / Max" class="text-muted small"><?= esc($minimo) ?> / <?= esc($maximo) ?></td>
                                                            <td data-label="Origem" class="text-muted small"><?= esc((string) ($row['origem'] ?? 'manual')) ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                        <?php $secIndex++; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php $tabIndex++; ?>
                <?php endforeach; ?>
            </div>
            </div>

                </div>
            </div>

            <div class="text-end">
                <button type="submit" class="btn btn-primary btn-glow px-4">
                    <i class="bi bi-save me-1"></i>Salvar configuração detalhada
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<div class="modal fade" id="modalServicoDetalhes" tabindex="-1" aria-labelledby="modalServicoDetalhesLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalServicoDetalhesLabel">Detalhar parâmetro do serviço</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex flex-wrap gap-2 mb-3" id="modalServicoDetalhesResumo"></div>
                <div class="text-muted small mb-3" id="modalServicoDetalhesHint"></div>
                <div id="modalServicoDetalhesBody"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnSalvarServicoDetalhes">
                    <i class="bi bi-save me-1"></i>Aplicar detalhes
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEncargoCategoria" tabindex="-1" aria-labelledby="modalEncargoCategoriaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEncargoCategoriaLabel">Parâmetros de encargos da categoria</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="text-muted small" id="modalEncargoHint">Defina os componentes que somam os encargos desta categoria.</div>
                    <div class="badge bg-light text-dark border">Total: <span id="encargoTotalCategoria">0.00%</span></div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                        <tr>
                            <th>Componente</th>
                            <th style="width: 140px;">Percentual (%)</th>
                            <th style="width: 60px;" class="text-center">Ação</th>
                        </tr>
                        </thead>
                        <tbody id="modalEncargoBody">
                        </tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddEncargoCategoria">
                    <i class="bi bi-plus-lg me-1"></i>Adicionar componente
                </button>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnSalvarEncargosCategoria">
                    <i class="bi bi-save me-1"></i>Salvar encargos
                </button>
            </div>
        </div>
    </div>
</div>

<script>
<?php
$servicoDefaultHorasMensais = ((float) ($servicoDefaults['tecnicos_ativos'] ?? 0))
    * ((float) ($servicoDefaults['horas_produtivas_dia'] ?? 0))
    * ((float) ($servicoDefaults['dias_uteis_mes'] ?? 0));
$servicoDefaultCustoHora = $servicoDefaultHorasMensais > 0
    ? ((float) ($servicoDefaults['custos_fixos_mensais'] ?? 0)) / $servicoDefaultHorasMensais
    : 0.0;
$servicoDefaultTempoTecnico = (
    (float) ($servicoDefaults['tempo_desmontagem_min'] ?? 0)
    + (float) ($servicoDefaults['tempo_substituicao_min'] ?? 0)
    + (float) ($servicoDefaults['tempo_montagem_min'] ?? 0)
    + (float) ($servicoDefaults['tempo_teste_final_min'] ?? 0)
) / 60;
$servicoDefaultCustosDiretos = (float) ($servicoDefaults['consumiveis_valor'] ?? 0)
    + ((float) ($servicoDefaults['tempo_indireto_horas'] ?? 0) * $servicoDefaultCustoHora)
    + (float) ($servicoDefaults['reserva_garantia_valor'] ?? 0)
    + (float) ($servicoDefaults['perdas_pequenas_valor'] ?? 0);
?>
(() => {
    const form = document.getElementById('formPrecificacaoDetalhada');
    const secaoCategorias = document.getElementById('pane-secao-categorias');
    const secaoParametros = document.getElementById('pane-secao-parametros');
    const topIndicator = document.getElementById('precificacaoUnsavedIndicator');
    const badgeCategorias = document.getElementById('badge-unsaved-categorias');
    const badgeParametros = document.getElementById('badge-unsaved-parametros');
    const tabCategorias = document.getElementById('tab-secao-categorias');
    const tabParametros = document.getElementById('tab-secao-parametros');
    const modalServicoDetalhesEl = document.getElementById('modalServicoDetalhes');
    const modalServicoDetalhesTitle = document.getElementById('modalServicoDetalhesLabel');
    const modalServicoDetalhesResumo = document.getElementById('modalServicoDetalhesResumo');
    const modalServicoDetalhesHint = document.getElementById('modalServicoDetalhesHint');
    const modalServicoDetalhesBody = document.getElementById('modalServicoDetalhesBody');
    const btnSalvarServicoDetalhes = document.getElementById('btnSalvarServicoDetalhes');
    const overrideTipoTabs = document.querySelectorAll('[data-override-tipo-tab]');
    const addBtn = document.getElementById('btnAddCategoriaOverride');
    let activeServicoDetalhesRow = null;
    let activeServicoDetalhesTipo = '';
    const globalEncargosMap = {
        peca: <?= json_encode(number_format($globalEncargos['peca'], 2, '.', '')) ?>,
        servico: <?= json_encode(number_format($globalEncargos['servico'], 2, '.', '')) ?>,
        produto: <?= json_encode(number_format($globalEncargos['produto'], 2, '.', '')) ?>,
    };
    const servicosCatalogo = <?= json_encode(array_map(static function (array $row): array {
        return [
            'id' => (int) ($row['id'] ?? 0),
            'nome' => (string) ($row['nome'] ?? ''),
            'tipo_equipamento' => (string) ($row['tipo_equipamento'] ?? ''),
            'valor' => (float) ($row['valor'] ?? 0),
            'tempo_padrao_horas' => (float) ($row['tempo_padrao_horas'] ?? 0),
        ];
    }, $servicosAtivos), JSON_UNESCAPED_UNICODE) ?>;
    const servicoDefaults = <?= json_encode([
        'custo_hora' => number_format($servicoDefaultCustoHora, 2, '.', ''),
        'custos_diretos' => number_format($servicoDefaultCustosDiretos, 2, '.', ''),
        'margem' => number_format((float) ($servicoDefaults['margem'] ?? 0), 2, '.', ''),
        'taxa' => number_format((float) ($servicoDefaults['taxa'] ?? 0), 2, '.', ''),
        'imposto' => number_format((float) ($servicoDefaults['imposto'] ?? 0), 2, '.', ''),
        'tempo_tecnico' => number_format($servicoDefaultTempoTecnico, 2, '.', ''),
        'risco' => number_format((float) ($servicoDefaults['risco'] ?? 0), 2, '.', ''),
        'preco_tabela' => number_format((float) ($servicoDefaults['preco_tabela'] ?? 0), 2, '.', ''),
        'custos_fixos_mensais' => number_format((float) ($servicoDefaults['custos_fixos_mensais'] ?? 0), 2, '.', ''),
        'tecnicos_ativos' => number_format((float) ($servicoDefaults['tecnicos_ativos'] ?? 1), 2, '.', ''),
        'horas_produtivas_dia' => number_format((float) ($servicoDefaults['horas_produtivas_dia'] ?? 0), 2, '.', ''),
        'dias_uteis_mes' => number_format((float) ($servicoDefaults['dias_uteis_mes'] ?? 1), 2, '.', ''),
        'consumiveis_valor' => number_format((float) ($servicoDefaults['consumiveis_valor'] ?? 0), 2, '.', ''),
        'tempo_indireto_horas' => number_format((float) ($servicoDefaults['tempo_indireto_horas'] ?? 0), 2, '.', ''),
        'reserva_garantia_valor' => number_format((float) ($servicoDefaults['reserva_garantia_valor'] ?? 0), 2, '.', ''),
        'perdas_pequenas_valor' => number_format((float) ($servicoDefaults['perdas_pequenas_valor'] ?? 0), 2, '.', ''),
        'tempo_desmontagem_min' => number_format((float) ($servicoDefaults['tempo_desmontagem_min'] ?? 0), 2, '.', ''),
        'tempo_substituicao_min' => number_format((float) ($servicoDefaults['tempo_substituicao_min'] ?? 0), 2, '.', ''),
        'tempo_montagem_min' => number_format((float) ($servicoDefaults['tempo_montagem_min'] ?? 0), 2, '.', ''),
        'tempo_teste_final_min' => number_format((float) ($servicoDefaults['tempo_teste_final_min'] ?? 0), 2, '.', ''),
    ], JSON_UNESCAPED_UNICODE) ?>;
    let snapshotCategorias = '';
    let snapshotParametros = '';
    let servicoInfoCounter = 0;

    const nextServicoInfoPrefix = () => {
        servicoInfoCounter += 1;
        return `servico-info-new-${servicoInfoCounter}`;
    };

    const serializeScope = (container) => {
        if (!container) return '';
        const fields = container.querySelectorAll('input[name], select[name], textarea[name]');
        const parts = [];
        fields.forEach((field) => {
            if (!field.name || field.name === 'csrf_test_name' || field.disabled) return;
            if (field.type === 'checkbox' || field.type === 'radio') {
                parts.push(`${field.name}:${field.checked ? '1' : '0'}`);
                return;
            }
            parts.push(`${field.name}:${field.value}`);
        });
        return parts.join('|');
    };

    const updateBadgeState = (badge, tab, label, dirty) => {
        if (badge) {
            badge.textContent = `${label}: ${dirty ? 'pendente' : 'sem alteração'}`;
            badge.classList.toggle('bg-warning', dirty);
            badge.classList.toggle('text-dark', dirty);
            badge.classList.toggle('border-warning', dirty);
            badge.classList.toggle('bg-light', !dirty);
            badge.classList.toggle('text-muted', !dirty);
            badge.classList.toggle('border', !dirty);
        }
        if (tab) {
            tab.classList.toggle('text-warning', dirty);
        }
    };

    const refreshUnsavedIndicator = () => {
        const dirtyCategorias = serializeScope(secaoCategorias) !== snapshotCategorias;
        const dirtyParametros = serializeScope(secaoParametros) !== snapshotParametros;
        updateBadgeState(badgeCategorias, tabCategorias, 'Categorias', dirtyCategorias);
        updateBadgeState(badgeParametros, tabParametros, 'Parâmetros', dirtyParametros);

        if (topIndicator) {
            topIndicator.classList.toggle('d-none', !(dirtyCategorias || dirtyParametros));
        }
    };

    const resetUnsavedBaseline = () => {
        snapshotCategorias = serializeScope(secaoCategorias);
        snapshotParametros = serializeScope(secaoParametros);
        refreshUnsavedIndicator();
    };

    const getActiveOverrideTipo = () => {
        const activeTab = document.querySelector('[data-override-tipo-tab].active');
        return activeTab?.dataset.overrideTipoTab || activeTab?.dataset.overrideTipo || 'peca';
    };

    const getOverrideTbodyByTipo = (tipo) => {
        return document.querySelector(`#tableCategoriaOverride-${tipo} tbody`);
    };

    const overrideTipoLabels = { peca: 'Peças', servico: 'Serviços', produto: 'Produtos' };
    const overrideCategoryPlaceholder = {
        peca: 'Ex.: Tela, Bateria, Conector',
        servico: 'Ex.: Smartphone, Notebook, Solda',
        produto: 'Ex.: Acessorio, Carregador, Cabo'
    };
    const overrideCategoriaHeader = {
        peca: 'Categoria da peça',
        servico: 'Categoria do serviço',
        produto: 'Categoria do produto'
    };
    const overrideEncargoHeader = {
        peca: 'Encargos (%)',
        servico: 'Risco adicional (%)',
        produto: 'Encargos operacionais (%)'
    };
    const overrideMargemHeader = {
        peca: 'Margem (%)',
        servico: 'Margem alvo (%)',
        produto: 'Margem (%)'
    };

    const updateAddButtonLabel = (tipo) => {
        if (!addBtn) return;
        if (tipo === 'servico') {
            addBtn.innerHTML = '<i class="bi bi-plus-lg me-1"></i>Adicionar serviço específico';
            return;
        }
        const label = overrideTipoLabels[tipo] || 'Categoria';
        addBtn.innerHTML = `<i class="bi bi-plus-lg me-1"></i>Adicionar categoria (${label})`;
    };

    const parseNumberInput = (value) => {
        if (value === null || value === undefined) return 0;
        const normalized = String(value).trim().replace(/\./g, '').replace(',', '.');
        const parsed = parseFloat(normalized);
        return Number.isNaN(parsed) ? 0 : parsed;
    };

    const updateOverrideBadge = (row) => {
        if (!row) return;
        const encargoInput = row.querySelector('.categoria-encargo-input');
        const badge = row.querySelector('.badge-override-status');
        if (!encargoInput || !badge) return;

        const valorAtual = parseNumberInput(encargoInput.value);
        const valorGlobal = parseNumberInput(encargoInput.dataset.globalEncargo || 0);
        const isOverride = Math.abs(valorAtual - valorGlobal) > 0.0001;

        badge.textContent = isOverride ? 'override ativo' : 'global';
        badge.classList.toggle('bg-primary', isOverride);
        badge.classList.toggle('bg-light', !isOverride);
        badge.classList.toggle('text-muted', !isOverride);
        badge.classList.toggle('border', !isOverride);
    };

    const toFixedString = (value, decimals = 2) => {
        const parsed = Number(value);
        if (Number.isNaN(parsed)) return (0).toFixed(decimals);
        return parsed.toFixed(decimals);
    };

    const formatBr = (value, decimals = 2) => {
        const parsed = Number(value);
        const normalized = Number.isNaN(parsed) ? 0 : parsed;
        return normalized.toLocaleString('pt-BR', { minimumFractionDigits: decimals, maximumFractionDigits: decimals });
    };

    const getRowNumericValue = (row, name, fallback = 0) => {
        if (!row || !name) return fallback;
        const input = row.querySelector(`[name="${name}[]"]`);
        if (!input) return fallback;
        return parseNumberInput(input.value);
    };

    const setRowNumericValue = (row, name, value, decimals = 2) => {
        if (!row || !name) return;
        const input = row.querySelector(`[name="${name}[]"]`);
        if (input) {
            input.value = toFixedString(value, decimals);
        }
    };

    const calculateServicoCapacity = (row) => {
        const custosFixos = getRowNumericValue(row, 'servico_override_custos_fixos_mensais', parseNumberInput(servicoDefaults.custos_fixos_mensais));
        const tecnicos = getRowNumericValue(row, 'servico_override_tecnicos_ativos', parseNumberInput(servicoDefaults.tecnicos_ativos));
        const horasDia = getRowNumericValue(row, 'servico_override_horas_produtivas_dia', parseNumberInput(servicoDefaults.horas_produtivas_dia));
        const diasUteis = getRowNumericValue(row, 'servico_override_dias_uteis_mes', parseNumberInput(servicoDefaults.dias_uteis_mes));
        const horasMensais = Math.max(0, tecnicos * horasDia * diasUteis);
        const custoHora = horasMensais > 0 ? custosFixos / horasMensais : 0;
        return { custosFixos, tecnicos, horasDia, diasUteis, horasMensais, custoHora };
    };

    const calculateServicoTempoHoras = (row) => {
        const desmontagem = getRowNumericValue(row, 'servico_override_tempo_desmontagem_min', parseNumberInput(servicoDefaults.tempo_desmontagem_min));
        const substituicao = getRowNumericValue(row, 'servico_override_tempo_substituicao_min', parseNumberInput(servicoDefaults.tempo_substituicao_min));
        const montagem = getRowNumericValue(row, 'servico_override_tempo_montagem_min', parseNumberInput(servicoDefaults.tempo_montagem_min));
        const teste = getRowNumericValue(row, 'servico_override_tempo_teste_final_min', parseNumberInput(servicoDefaults.tempo_teste_final_min));
        const totalMin = Math.max(0, desmontagem + substituicao + montagem + teste);
        return totalMin / 60;
    };

    const calculateServicoCustosDiretos = (row, custoHora) => {
        const consumiveis = getRowNumericValue(row, 'servico_override_consumiveis_valor', parseNumberInput(servicoDefaults.consumiveis_valor));
        const tempoIndireto = getRowNumericValue(row, 'servico_override_tempo_indireto_horas', parseNumberInput(servicoDefaults.tempo_indireto_horas));
        const reserva = getRowNumericValue(row, 'servico_override_reserva_garantia_valor', parseNumberInput(servicoDefaults.reserva_garantia_valor));
        const perdas = getRowNumericValue(row, 'servico_override_perdas_pequenas_valor', parseNumberInput(servicoDefaults.perdas_pequenas_valor));
        const tempoIndiretoValor = tempoIndireto * custoHora;
        const total = consumiveis + tempoIndiretoValor + reserva + perdas;
        return { consumiveis, tempoIndireto, tempoIndiretoValor, reserva, perdas, total };
    };

    const buildServicoOptionsHtml = (selected) => {
        const selectedId = Number(selected || 0);
        const options = servicosCatalogo.map((servico) => {
            const labelParts = [servico.nome || 'Serviço'];
            if (servico.tipo_equipamento) labelParts.push(servico.tipo_equipamento);
            const label = labelParts.join(' - ');
            const isSelected = Number(servico.id) === selectedId ? 'selected' : '';
            return `<option value="${servico.id}" ${isSelected}>${label}</option>`;
        });
        return `<option value="">Selecione um serviço</option>${options.join('')}`;
    };

    const getServicoDetailMeta = (tipo) => {
        const metas = {
            capacidade: {
                title: 'Capacidade - Parâmetros detalhados',
                hint: 'Mesma estrutura da tabela global: ajuste os valores manuais e acompanhe os calculados.',
                rows: [
                    { parametro: 'Custos fixos mensais', codigo: 'servico_custos_fixos_mensais', descricao: 'Estrutura mensal da empresa', unidade: 'R$', formula: 'Soma de aluguel, energia, internet, software etc', input: 'servico_override_custos_fixos_mensais', minmax: '0,00 / 9.999.999,00', origem: 'manual', decimals: 2 },
                    { parametro: 'Técnicos ativos', codigo: 'servico_tecnicos_ativos', descricao: 'Quantidade de técnicos produtivos', unidade: 'qtd', formula: 'Dado operacional', input: 'servico_override_tecnicos_ativos', minmax: '1,00 / 1.000,00', origem: 'manual', decimals: 2 },
                    { parametro: 'Horas produtivas por dia', codigo: 'servico_horas_produtivas_dia', descricao: 'Horas reais de bancada por técnico', unidade: 'h', formula: 'Métrica real sem pausas/atendimento', input: 'servico_override_horas_produtivas_dia', minmax: '0,10 / 24,00', origem: 'manual', decimals: 2 },
                    { parametro: 'Dias úteis no mês', codigo: 'servico_dias_uteis_mes', descricao: 'Dias úteis de operação no mês', unidade: 'dias', formula: 'Calendário operacional', input: 'servico_override_dias_uteis_mes', minmax: '1,00 / 31,00', origem: 'manual', decimals: 2 },
                    { parametro: 'Horas produtivas mensais', codigo: 'servico_horas_produtivas_mensais', descricao: 'Capacidade produtiva total no mês', unidade: 'h', formula: 'TécnicosAtivos * HorasDia * DiasÚteis', calculated: 'horas_mensais', minmax: '0,00 / 99.999,00', origem: 'manual', decimals: 2 },
                    { parametro: 'Custo hora produtiva', codigo: 'servico_custo_hora_produtiva', descricao: 'Custo de uma hora real de produção', unidade: 'R$', formula: 'CustosFixosMensais / HorasProdutivasMensais', calculated: 'custo_hora', minmax: '0,00 / 999.999,00', origem: 'manual', decimals: 2 },
                ],
            },
            custos_diretos: {
                title: 'Custos Diretos - Parâmetros detalhados',
                hint: 'Mesma estrutura da tabela global: ajuste os valores manuais e acompanhe os calculados.',
                rows: [
                    { parametro: 'Consumíveis', codigo: 'servico_consumiveis_valor', descricao: 'Cola, fita, limpeza e insumos', unidade: 'R$', formula: 'Soma dos consumíveis por serviço', input: 'servico_override_consumiveis_valor', minmax: '0,00 / 999.999,00', origem: 'manual', decimals: 2 },
                    { parametro: 'Tempo indireto rateado', codigo: 'servico_tempo_indireto_horas', descricao: 'Recepção técnica/checklist/comunicação', unidade: 'h', formula: 'Horas indiretas por atendimento', input: 'servico_override_tempo_indireto_horas', minmax: '0,00 / 24,00', origem: 'manual', decimals: 2 },
                    { parametro: 'Valor do tempo indireto', codigo: 'servico_tempo_indireto_rateado_valor', descricao: 'Conversão do tempo indireto em custo', unidade: 'R$', formula: 'TempoIndiretoHoras * CustoHoraProdutiva', calculated: 'tempo_indireto_valor', minmax: '0,00 / 999.999,00', origem: 'manual', decimals: 2 },
                    { parametro: 'Reserva de garantia', codigo: 'servico_reserva_garantia_valor', descricao: 'Reserva para retrabalho/garantia', unidade: 'R$', formula: 'Valor técnico definido por histórico', input: 'servico_override_reserva_garantia_valor', minmax: '0,00 / 999.999,00', origem: 'manual', decimals: 2 },
                    { parametro: 'Perdas pequenas', codigo: 'servico_perdas_pequenas_valor', descricao: 'Perdas pequenas de processo', unidade: 'R$', formula: 'Valor técnico médio', input: 'servico_override_perdas_pequenas_valor', minmax: '0,00 / 999.999,00', origem: 'manual', decimals: 2 },
                    { parametro: 'Custos diretos do serviço', codigo: 'servico_custos_diretos_total', descricao: 'Custo operacional direto total do serviço', unidade: 'R$', formula: 'Consumíveis + TempoIndireto + Reserva + Perdas', calculated: 'custos_diretos_total', minmax: '0,00 / 999.999,00', origem: 'manual', decimals: 2 },
                    { parametro: 'Risco percentual adicional', codigo: 'servico_risco_percentual', descricao: 'Risco técnico percentual adicional (opcional)', unidade: '%', formula: 'Percentual de risco extra para serviço', input: 'servico_override_risco', minmax: '0,00 / 100,00', origem: 'manual', decimals: 2 },
                ],
            },
            margem_taxas: {
                title: 'Margem e Taxas - Parâmetros detalhados',
                hint: 'Mesma estrutura da tabela global: ajuste os valores manuais e acompanhe os calculados.',
                rows: [
                    { parametro: 'Margem alvo do serviço', codigo: 'servico_margem_alvo_percentual', descricao: 'Margem comercial alvo para serviços', unidade: '%', formula: 'Percentual comercial definido pela empresa', input: 'servico_override_margem', minmax: '0,00 / 300,00', origem: 'manual', decimals: 2 },
                    { parametro: 'Taxa de recebimento', codigo: 'servico_taxa_recebimento_percentual', descricao: 'Taxa financeira de recebimento', unidade: '%', formula: 'Taxa média de cartão/recebimento', input: 'servico_override_taxa', minmax: '0,00 / 100,00', origem: 'manual', decimals: 2 },
                    { parametro: 'Imposto', codigo: 'servico_imposto_percentual', descricao: 'Carga tributária sobre venda de serviço', unidade: '%', formula: 'Percentual fiscal efetivo', input: 'servico_override_imposto', minmax: '0,00 / 100,00', origem: 'manual', decimals: 2 },
                    { parametro: 'Divisor técnico', codigo: 'servico_divisor_tecnico', descricao: 'Fator de divisão para formação do preço mínimo', unidade: 'fator', formula: '1 - (Margem + Taxa + Imposto)/100', calculated: 'divisor_tecnico', minmax: '0,01 / 1,00', origem: 'manual', decimals: 4 },
                ],
            },
            tempo_tecnico: {
                title: 'Tempo Técnico - Parâmetros detalhados',
                hint: 'Mesma estrutura da tabela global: ajuste os valores manuais e acompanhe os calculados.',
                rows: [
                    { parametro: 'Tempo desmontagem', codigo: 'servico_tempo_desmontagem_min', descricao: 'Tempo para abrir equipamento', unidade: 'min', formula: 'Direto da rotina técnica', input: 'servico_override_tempo_desmontagem_min', minmax: '0,00 / 999,00', origem: 'manual', decimals: 2 },
                    { parametro: 'Tempo substituição', codigo: 'servico_tempo_substituicao_min', descricao: 'Tempo da troca principal', unidade: 'min', formula: 'Direto da rotina técnica', input: 'servico_override_tempo_substituicao_min', minmax: '0,00 / 999,00', origem: 'manual', decimals: 2 },
                    { parametro: 'Tempo montagem', codigo: 'servico_tempo_montagem_min', descricao: 'Tempo de remontagem', unidade: 'min', formula: 'Direto da rotina técnica', input: 'servico_override_tempo_montagem_min', minmax: '0,00 / 999,00', origem: 'manual', decimals: 2 },
                    { parametro: 'Tempo teste final', codigo: 'servico_tempo_teste_final_min', descricao: 'Tempo de validação final', unidade: 'min', formula: 'Direto da rotina técnica', input: 'servico_override_tempo_teste_final_min', minmax: '0,00 / 999,00', origem: 'manual', decimals: 2 },
                    { parametro: 'Tempo técnico total', codigo: 'servico_tempo_tecnico_horas', descricao: 'Tempo técnico total em horas', unidade: 'h', formula: '(Desmontagem + Substituição + Montagem + Teste) / 60', calculated: 'tempo_tecnico_horas', minmax: '0,00 / 999,00', origem: 'manual', decimals: 2 },
                ],
            },
            resultado: {
                title: 'Resultado - Parâmetros detalhados',
                hint: 'Mesma estrutura da tabela global: ajuste os valores manuais e acompanhe os calculados.',
                rows: [
                    { parametro: 'Custo total do serviço', codigo: 'servico_custo_servico_total', descricao: 'Custo técnico antes da venda', unidade: 'R$', formula: '(TempoTecnico * CustoHora) + CustosDiretos + Risco', calculated: 'custo_total', minmax: '0,00 / 999.999,00', origem: 'manual', decimals: 2 },
                    { parametro: 'Divisor técnico', codigo: 'servico_divisor_tecnico', descricao: 'Fator de divisão para formação do preço mínimo', unidade: 'fator', formula: '1 - (Margem + Taxa + Imposto)/100', calculated: 'divisor_tecnico', minmax: '0,01 / 1,00', origem: 'manual', decimals: 4 },
                    { parametro: 'Preço mínimo técnico', codigo: 'servico_preco_minimo_tecnico', descricao: 'Menor preço sustentável para o serviço', unidade: 'R$', formula: 'CustoTotal / DivisorTécnico', calculated: 'preco_minimo', minmax: '0,00 / 999.999,00', origem: 'manual', decimals: 2 },
                    { parametro: 'Preço tabela de referência', codigo: 'servico_preco_tabela_referencia', descricao: 'Preço comercial praticado para comparação', unidade: 'R$', formula: 'Política comercial da empresa', input: 'servico_override_preco_tabela', minmax: '0,00 / 999.999,00', origem: 'manual', decimals: 2 },
                    { parametro: 'Preço recomendado', codigo: 'servico_preco_recomendado', descricao: 'Preço sugerido comparando tabela x mínimo', unidade: 'R$', formula: 'Maior entre Preço Tabela e Preço Mínimo', calculated: 'preco_recomendado', minmax: '0,00 / 999.999,00', origem: 'manual', decimals: 2 },
                ],
            },
        };
        return metas[tipo] || metas.capacidade;
    };

    const buildServicoDetailRows = (row, meta) => {
        const capacity = calculateServicoCapacity(row);
        const tempoHoras = calculateServicoTempoHoras(row);
        const custosDiretosData = calculateServicoCustosDiretos(row, capacity.custoHora);
        const margem = getRowNumericValue(row, 'servico_override_margem', parseNumberInput(servicoDefaults.margem));
        const taxa = getRowNumericValue(row, 'servico_override_taxa', parseNumberInput(servicoDefaults.taxa));
        const imposto = getRowNumericValue(row, 'servico_override_imposto', parseNumberInput(servicoDefaults.imposto));
        const divisor = 1 - (margem + taxa + imposto) / 100;
        const risco = getRowNumericValue(row, 'servico_override_risco', parseNumberInput(servicoDefaults.risco));
        const baseCusto = (tempoHoras * capacity.custoHora) + custosDiretosData.total;
        const custoTotal = baseCusto * (1 + risco / 100);
        const precoMinimo = divisor > 0 ? custoTotal / divisor : 0;
        const precoTabela = getRowNumericValue(row, 'servico_override_preco_tabela', parseNumberInput(servicoDefaults.preco_tabela));
        const precoRecomendado = Math.max(precoMinimo, precoTabela);

        const calculatedMap = {
            horas_mensais: { value: capacity.horasMensais, unit: 'h', decimals: 2 },
            custo_hora: { value: capacity.custoHora, unit: '', decimals: 2 },
            tempo_indireto_valor: { value: custosDiretosData.tempoIndiretoValor, unit: '', decimals: 2 },
            custos_diretos_total: { value: custosDiretosData.total, unit: '', decimals: 2 },
            divisor_tecnico: { value: divisor > 0 ? divisor : 0, unit: '', decimals: 4 },
            tempo_tecnico_horas: { value: tempoHoras, unit: 'h', decimals: 2 },
            custo_total: { value: custoTotal, unit: '', decimals: 2 },
            preco_minimo: { value: precoMinimo, unit: '', decimals: 2 },
            preco_recomendado: { value: precoRecomendado, unit: '', decimals: 2 },
        };

        return meta.rows.map((item) => {
            const calculated = item.calculated ? calculatedMap[item.calculated] : null;
            const valueContent = item.input
                ? `<input type="number" step="0.01" class="form-control form-control-sm servico-detail-value-input" data-servico-detail-input="${item.input}" data-servico-detail-decimals="${item.decimals || 2}" value="${toFixedString(getRowNumericValue(row, item.input, 0), item.decimals || 2)}">`
                : `<span class="fw-semibold servico-detail-value-calc">${formatBr(calculated?.value ?? 0, calculated?.decimals ?? 2)}${calculated?.unit || ''}</span>`;
            return `
                <tr>
                    <td data-label="Parâmetro">
                        <strong>${item.parametro}</strong>
                        <div class="text-muted small">${item.codigo}</div>
                    </td>
                    <td data-label="Descrição">${item.descricao}</td>
                    <td data-label="Unidade" class="text-center">${item.unidade}</td>
                    <td data-label="Fórmula"><span class="text-muted small">${item.formula}</span></td>
                    <td data-label="Valor">${valueContent}</td>
                    <td data-label="Min / Max" class="text-muted small">${item.minmax}</td>
                    <td data-label="Origem" class="text-muted small">${item.origem}</td>
                </tr>
            `;
        }).join('');
    };

    const renderServicoDetailTable = (row, meta) => {
        const rowsHtml = buildServicoDetailRows(row, meta);
        return `
            <div class="table-responsive">
                <table class="table table-sm align-middle servico-detail-table">
                    <colgroup>
                        <col class="col-parametro">
                        <col class="col-descricao">
                        <col class="col-unidade">
                        <col class="col-formula">
                        <col class="col-valor">
                        <col class="col-minmax">
                        <col class="col-origem">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Parâmetro</th>
                            <th>Descrição</th>
                            <th class="text-center">Unidade</th>
                            <th>Fórmula</th>
                            <th>Valor</th>
                            <th>Min / Max</th>
                            <th>Origem</th>
                        </tr>
                    </thead>
                    <tbody>${rowsHtml}</tbody>
                </table>
            </div>
        `;
    };

    const updateModalServicoResumo = (row) => {
        if (!modalServicoDetalhesResumo) return;
        const custoHora = getRowNumericValue(row, 'servico_override_custo_hora', parseNumberInput(servicoDefaults.custo_hora));
        const custosDiretos = getRowNumericValue(row, 'servico_override_custos_diretos', parseNumberInput(servicoDefaults.custos_diretos));
        const tempoTecnico = getRowNumericValue(row, 'servico_override_tempo_tecnico', parseNumberInput(servicoDefaults.tempo_tecnico));
        const precoTabela = getRowNumericValue(row, 'servico_override_preco_tabela', parseNumberInput(servicoDefaults.preco_tabela));
        modalServicoDetalhesResumo.innerHTML = `
            <span class="servico-detail-resumo-badge">Custo hora: <strong>${formatBr(custoHora)}</strong></span>
            <span class="servico-detail-resumo-badge">Tempo técnico: <strong>${formatBr(tempoTecnico, 2)}h</strong></span>
            <span class="servico-detail-resumo-badge">Custos diretos: <strong>${formatBr(custosDiretos)}</strong></span>
            <span class="servico-detail-resumo-badge">Preço tabela: <strong>${formatBr(precoTabela)}</strong></span>
        `;
    };

    const openServicoDetailModal = (row, tipo) => {
        if (!modalServicoDetalhesEl || !modalServicoDetalhesBody) return;
        const meta = getServicoDetailMeta(tipo);
        activeServicoDetalhesRow = row;
        activeServicoDetalhesTipo = tipo;
        if (modalServicoDetalhesTitle) modalServicoDetalhesTitle.textContent = meta.title;
        if (modalServicoDetalhesHint) modalServicoDetalhesHint.textContent = meta.hint;
        updateModalServicoResumo(row);
        modalServicoDetalhesBody.innerHTML = renderServicoDetailTable(row, meta);
        const instance = bootstrap.Modal.getOrCreateInstance(modalServicoDetalhesEl);
        instance.show();
    };

    const applyServicoDetailTipo = (row, tipo) => {
        if (!row) return;
        if (tipo === 'capacidade') {
            const capacity = calculateServicoCapacity(row);
            setRowNumericValue(row, 'servico_override_custo_hora', capacity.custoHora, 2);
        } else if (tipo === 'custos_diretos') {
            const capacity = calculateServicoCapacity(row);
            const custosDiretos = calculateServicoCustosDiretos(row, capacity.custoHora);
            setRowNumericValue(row, 'servico_override_custos_diretos', custosDiretos.total, 2);
        } else if (tipo === 'tempo_tecnico') {
            const tempoHoras = calculateServicoTempoHoras(row);
            setRowNumericValue(row, 'servico_override_tempo_tecnico', tempoHoras, 2);
        }
    };

    const updateServicoOverrideSummary = (row) => {
        if (!row) return;
        const custoHora = getRowNumericValue(row, 'servico_override_custo_hora', parseNumberInput(servicoDefaults.custo_hora));
        const custosDiretos = getRowNumericValue(row, 'servico_override_custos_diretos', parseNumberInput(servicoDefaults.custos_diretos));
        const risco = getRowNumericValue(row, 'servico_override_risco', parseNumberInput(servicoDefaults.risco));
        const tempoTecnico = getRowNumericValue(row, 'servico_override_tempo_tecnico', parseNumberInput(servicoDefaults.tempo_tecnico));
        const margem = getRowNumericValue(row, 'servico_override_margem', parseNumberInput(servicoDefaults.margem));
        const taxa = getRowNumericValue(row, 'servico_override_taxa', parseNumberInput(servicoDefaults.taxa));
        const imposto = getRowNumericValue(row, 'servico_override_imposto', parseNumberInput(servicoDefaults.imposto));
        const precoTabela = getRowNumericValue(row, 'servico_override_preco_tabela', parseNumberInput(servicoDefaults.preco_tabela));
        const baseCusto = (tempoTecnico * custoHora) + custosDiretos;
        const custoTotal = baseCusto * (1 + risco / 100);
        const divisor = 1 - (margem + taxa + imposto) / 100;
        const precoMinimo = divisor > 0 ? custoTotal / divisor : 0;
        const precoRecomendado = Math.max(precoMinimo, precoTabela);

        row.querySelectorAll('.servico-override-custo-total').forEach((el) => {
            el.textContent = formatBr(custoTotal);
        });
        row.querySelectorAll('.servico-override-divisor').forEach((el) => {
            el.textContent = formatBr(divisor > 0 ? divisor : 0, 4);
        });
        row.querySelectorAll('.servico-override-preco-minimo').forEach((el) => {
            el.textContent = formatBr(precoMinimo);
        });
        row.querySelectorAll('.servico-override-preco-recomendado').forEach((el) => {
            el.textContent = formatBr(precoRecomendado);
        });
    };

    const createServicoOverrideRow = () => {
        const tr = document.createElement('tr');
        const infoPrefix = nextServicoInfoPrefix();
        tr.className = 'servico-override-row';
        tr.innerHTML = `
            <td class="p-0 border-0">
                <div class="servico-mini-card">
                    <div class="card-body p-3">
                        <input type="hidden" name="servico_override_id[]" value="">
                        <input type="hidden" name="servico_override_custos_fixos_mensais[]" value="${servicoDefaults.custos_fixos_mensais || '0.00'}">
                        <input type="hidden" name="servico_override_tecnicos_ativos[]" value="${servicoDefaults.tecnicos_ativos || '1.00'}">
                        <input type="hidden" name="servico_override_horas_produtivas_dia[]" value="${servicoDefaults.horas_produtivas_dia || '0.00'}">
                        <input type="hidden" name="servico_override_dias_uteis_mes[]" value="${servicoDefaults.dias_uteis_mes || '1.00'}">
                        <input type="hidden" name="servico_override_consumiveis_valor[]" value="${servicoDefaults.consumiveis_valor || '0.00'}">
                        <input type="hidden" name="servico_override_tempo_indireto_horas[]" value="${servicoDefaults.tempo_indireto_horas || '0.00'}">
                        <input type="hidden" name="servico_override_reserva_garantia_valor[]" value="${servicoDefaults.reserva_garantia_valor || '0.00'}">
                        <input type="hidden" name="servico_override_perdas_pequenas_valor[]" value="${servicoDefaults.perdas_pequenas_valor || '0.00'}">
                        <input type="hidden" name="servico_override_tempo_desmontagem_min[]" value="${servicoDefaults.tempo_desmontagem_min || '0.00'}">
                        <input type="hidden" name="servico_override_tempo_substituicao_min[]" value="${servicoDefaults.tempo_substituicao_min || '0.00'}">
                        <input type="hidden" name="servico_override_tempo_montagem_min[]" value="${servicoDefaults.tempo_montagem_min || '0.00'}">
                        <input type="hidden" name="servico_override_tempo_teste_final_min[]" value="${servicoDefaults.tempo_teste_final_min || '0.00'}">
                        <div class="servico-mini-head d-flex flex-wrap align-items-start justify-content-between gap-2">
                            <div class="flex-grow-1">
                                <label class="form-label small text-muted mb-1">Serviço específico</label>
                                <select name="servico_override_servico_id[]" class="form-select form-select-sm servico-override-input">
                                    ${buildServicoOptionsHtml('')}
                                </select>
                            </div>
                            <div class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-secondary btn-servico-use-global me-1" title="Remover override e usar padrão global">
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row" title="Remover linha">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                        <div class="servico-mini-summary mt-2 d-flex flex-wrap gap-2">
                            <span class="badge bg-light text-dark border">Custo total: <strong class="servico-override-custo-total">0,00</strong></span>
                            <span class="badge bg-light text-dark border">Divisor técnico: <strong class="servico-override-divisor">0,0000</strong></span>
                            <span class="badge bg-light text-dark border">Preço mínimo: <strong class="servico-override-preco-minimo">0,00</strong></span>
                            <span class="badge bg-light text-dark border">Preço recomendado: <strong class="servico-override-preco-recomendado">0,00</strong></span>
                        </div>
                        <div class="servico-mini-sections mt-3 d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#${infoPrefix}-sec-capacidade" aria-expanded="false" aria-controls="${infoPrefix}-sec-capacidade">Capacidade</button>
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#${infoPrefix}-sec-custos" aria-expanded="false" aria-controls="${infoPrefix}-sec-custos">Custos Diretos</button>
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#${infoPrefix}-sec-margem" aria-expanded="false" aria-controls="${infoPrefix}-sec-margem">Margem/Taxas</button>
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#${infoPrefix}-sec-tempo" aria-expanded="false" aria-controls="${infoPrefix}-sec-tempo">Tempo Técnico</button>
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#${infoPrefix}-sec-resultado" aria-expanded="false" aria-controls="${infoPrefix}-sec-resultado">Resultado</button>
                        </div>
                        <div class="collapse servico-mini-section" id="${infoPrefix}-sec-capacidade">
                            <div class="servico-mini-section-title">Capacidade</div>
                            <label class="form-label small text-muted mb-1">Custo hora produtiva (R$)</label>
                            <div class="input-group input-group-sm">
                                <input type="number" step="0.01" min="0" max="100000" name="servico_override_custo_hora[]" class="form-control form-control-sm servico-override-input" value="${servicoDefaults.custo_hora || '0.00'}" placeholder="Ex.: 12.50">
                                <button type="button" class="btn btn-outline-secondary btn-servico-detail" data-servico-detail="capacidade" title="Detalhar Capacidade">
                                    <i class="bi bi-sliders2"></i>
                                </button>
                            </div>
                            <div class="servico-info-actions mt-2">
                                <button type="button" class="btn btn-outline-secondary btn-servico-info" data-bs-toggle="collapse" data-bs-target="#${infoPrefix}-capacidade-formula" aria-expanded="false" aria-controls="${infoPrefix}-capacidade-formula">
                                    <i class="bi bi-info-circle me-1"></i>Fórmula
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-servico-info" data-bs-toggle="collapse" data-bs-target="#${infoPrefix}-capacidade-ref" aria-expanded="false" aria-controls="${infoPrefix}-capacidade-ref">
                                    <i class="bi bi-info-circle me-1"></i>Referência
                                </button>
                            </div>
                            <div class="collapse servico-info-collapse mt-1" id="${infoPrefix}-capacidade-formula">
                                <div class="servico-info-note">CustoHora = CustosFixosMensais / HorasProdutivasMensais.</div>
                            </div>
                            <div class="collapse servico-info-collapse mt-1" id="${infoPrefix}-capacidade-ref">
                                <div class="servico-info-note">Usa os mesmos componentes da seção Capacidade dos parâmetros globais.</div>
                            </div>
                        </div>
                        <div class="collapse servico-mini-section" id="${infoPrefix}-sec-custos">
                            <div class="servico-mini-section-title">Custos Diretos</div>
                            <label class="form-label small text-muted mb-1">Custos diretos totais (R$)</label>
                            <div class="input-group input-group-sm mb-2">
                                <input type="number" step="0.01" min="0" max="100000" name="servico_override_custos_diretos[]" class="form-control form-control-sm servico-override-input" value="${servicoDefaults.custos_diretos || '0.00'}" placeholder="Ex.: 18.90">
                                <button type="button" class="btn btn-outline-secondary btn-servico-detail" data-servico-detail="custos_diretos" title="Detalhar Custos Diretos">
                                    <i class="bi bi-sliders2"></i>
                                </button>
                            </div>
                            <label class="form-label small text-muted mb-1">Risco percentual adicional (%)</label>
                            <input type="number" step="0.01" min="0" max="100" name="servico_override_risco[]" class="form-control form-control-sm servico-override-input" value="${servicoDefaults.risco || '0.00'}" placeholder="Ex.: 2.50">
                            <div class="servico-info-actions mt-2">
                                <button type="button" class="btn btn-outline-secondary btn-servico-info" data-bs-toggle="collapse" data-bs-target="#${infoPrefix}-custos-formula" aria-expanded="false" aria-controls="${infoPrefix}-custos-formula">
                                    <i class="bi bi-info-circle me-1"></i>Fórmula
                                </button>
                            </div>
                            <div class="collapse servico-info-collapse mt-1" id="${infoPrefix}-custos-formula">
                                <div class="servico-info-note">CustoTotal = (TempoTecnico x CustoHora + CustosDiretos) + Risco percentual.</div>
                            </div>
                        </div>
                        <div class="collapse servico-mini-section" id="${infoPrefix}-sec-margem">
                            <div class="servico-mini-section-title">Margem/Taxas</div>
                            <label class="form-label small text-muted mb-1">Margem alvo (%)</label>
                            <div class="input-group input-group-sm mb-2">
                                <input type="number" step="0.01" min="0" max="300" name="servico_override_margem[]" class="form-control form-control-sm servico-override-input" value="${servicoDefaults.margem || '0.00'}" placeholder="Ex.: 25.00">
                                <button type="button" class="btn btn-outline-secondary btn-servico-detail" data-servico-detail="margem_taxas" title="Detalhar Margem e Taxas">
                                    <i class="bi bi-sliders2"></i>
                                </button>
                            </div>
                            <label class="form-label small text-muted mb-1">Taxa de recebimento (%)</label>
                            <input type="number" step="0.01" min="0" max="100" name="servico_override_taxa[]" class="form-control form-control-sm servico-override-input mb-2" value="${servicoDefaults.taxa || '0.00'}" placeholder="Ex.: 3.50">
                            <label class="form-label small text-muted mb-1">Imposto (%)</label>
                            <input type="number" step="0.01" min="0" max="100" name="servico_override_imposto[]" class="form-control form-control-sm servico-override-input" value="${servicoDefaults.imposto || '0.00'}" placeholder="Ex.: 0.00">
                            <div class="servico-info-actions mt-2">
                                <button type="button" class="btn btn-outline-secondary btn-servico-info" data-bs-toggle="collapse" data-bs-target="#${infoPrefix}-margem-formula" aria-expanded="false" aria-controls="${infoPrefix}-margem-formula">
                                    <i class="bi bi-info-circle me-1"></i>Fórmula do divisor
                                </button>
                            </div>
                            <div class="collapse servico-info-collapse mt-1" id="${infoPrefix}-margem-formula">
                                <div class="servico-info-note">DivisorTecnico = 1 - (Margem + Taxa + Imposto) / 100.</div>
                            </div>
                        </div>
                        <div class="collapse servico-mini-section" id="${infoPrefix}-sec-tempo">
                            <div class="servico-mini-section-title">Tempo Técnico</div>
                            <label class="form-label small text-muted mb-1">Tempo técnico total (h)</label>
                            <div class="input-group input-group-sm">
                                <input type="number" step="0.01" min="0" max="999" name="servico_override_tempo_tecnico[]" class="form-control form-control-sm servico-override-input" value="${servicoDefaults.tempo_tecnico || '0.00'}" placeholder="Ex.: 1.50">
                                <button type="button" class="btn btn-outline-secondary btn-servico-detail" data-servico-detail="tempo_tecnico" title="Detalhar Tempo Técnico">
                                    <i class="bi bi-sliders2"></i>
                                </button>
                            </div>
                            <div class="servico-info-actions mt-2">
                                <button type="button" class="btn btn-outline-secondary btn-servico-info" data-bs-toggle="collapse" data-bs-target="#${infoPrefix}-tempo-ref" aria-expanded="false" aria-controls="${infoPrefix}-tempo-ref">
                                    <i class="bi bi-info-circle me-1"></i>Referência
                                </button>
                            </div>
                            <div class="collapse servico-info-collapse mt-1" id="${infoPrefix}-tempo-ref">
                                <div class="servico-info-note">Segue a mesma estrutura da seção Tempo Técnico dos parâmetros globais.</div>
                            </div>
                        </div>
                        <div class="collapse servico-mini-section" id="${infoPrefix}-sec-resultado">
                            <div class="servico-mini-section-title">Resultado</div>
                            <label class="form-label small text-muted mb-1">Preço tabela de referência (R$)</label>
                            <div class="input-group input-group-sm mb-2">
                                <input type="number" step="0.01" min="0" max="999999" name="servico_override_preco_tabela[]" class="form-control form-control-sm servico-override-input" value="${servicoDefaults.preco_tabela || '0.00'}" placeholder="Ex.: 99.00">
                                <button type="button" class="btn btn-outline-secondary btn-servico-detail" data-servico-detail="resultado" title="Detalhar Resultado">
                                    <i class="bi bi-sliders2"></i>
                                </button>
                            </div>
                            <div class="text-muted small">Custo total: <span class="fw-semibold servico-override-custo-total">0,00</span></div>
                            <div class="text-muted small">Divisor técnico: <span class="fw-semibold servico-override-divisor">0,0000</span></div>
                            <div class="text-muted small">Preço mínimo técnico: <span class="fw-semibold servico-override-preco-minimo">0,00</span></div>
                            <div class="text-muted small">Preço recomendado: <span class="fw-semibold servico-override-preco-recomendado">0,00</span></div>
                        </div>
                    </div>
                </div>
            </td>
        `;

        return tr;
    };

    const removePlaceholderRow = (tbody) => {
        if (!tbody) return;
        tbody.querySelectorAll('.row-empty-placeholder').forEach((row) => row.remove());
    };

    const ensurePlaceholderRow = (tbody, tipo) => {
        if (!tbody) return;
        const hasRealRows = Array.from(tbody.querySelectorAll('tr')).some((row) => !row.classList.contains('row-empty-placeholder'));
        if (hasRealRows) {
            removePlaceholderRow(tbody);
            return;
        }
        if (tbody.querySelector('.row-empty-placeholder')) {
            return;
        }
        const label = overrideTipoLabels[tipo] || 'este tipo';
        const table = tbody.closest('table');
        const colspan = Number(table?.dataset.placeholderColspan || 4);
        const emptyText = tipo === 'servico'
            ? 'Nenhum serviço específico configurado.'
            : `Nenhuma categoria cadastrada em ${label}.`;
        const tr = document.createElement('tr');
        tr.className = 'row-empty-placeholder';
        tr.innerHTML = `
            <td colspan="${colspan}" class="text-center text-muted small py-4">${emptyText}</td>
        `;
        tbody.appendChild(tr);
        return;
        tr.innerHTML = `
            <td class="p-0 border-0">
                <div class="servico-mini-card">
                    <div class="card-body p-3">
                        <input type="hidden" name="servico_override_id[]" value="">
                        <input type="hidden" name="servico_override_custos_fixos_mensais[]" value="${servicoDefaults.custos_fixos_mensais || '0.00'}">
                        <input type="hidden" name="servico_override_tecnicos_ativos[]" value="${servicoDefaults.tecnicos_ativos || '1.00'}">
                        <input type="hidden" name="servico_override_horas_produtivas_dia[]" value="${servicoDefaults.horas_produtivas_dia || '0.00'}">
                        <input type="hidden" name="servico_override_dias_uteis_mes[]" value="${servicoDefaults.dias_uteis_mes || '1.00'}">
                        <input type="hidden" name="servico_override_consumiveis_valor[]" value="${servicoDefaults.consumiveis_valor || '0.00'}">
                        <input type="hidden" name="servico_override_tempo_indireto_horas[]" value="${servicoDefaults.tempo_indireto_horas || '0.00'}">
                        <input type="hidden" name="servico_override_reserva_garantia_valor[]" value="${servicoDefaults.reserva_garantia_valor || '0.00'}">
                        <input type="hidden" name="servico_override_perdas_pequenas_valor[]" value="${servicoDefaults.perdas_pequenas_valor || '0.00'}">
                        <input type="hidden" name="servico_override_tempo_desmontagem_min[]" value="${servicoDefaults.tempo_desmontagem_min || '0.00'}">
                        <input type="hidden" name="servico_override_tempo_substituicao_min[]" value="${servicoDefaults.tempo_substituicao_min || '0.00'}">
                        <input type="hidden" name="servico_override_tempo_montagem_min[]" value="${servicoDefaults.tempo_montagem_min || '0.00'}">
                        <input type="hidden" name="servico_override_tempo_teste_final_min[]" value="${servicoDefaults.tempo_teste_final_min || '0.00'}">
                        <div class="servico-mini-head d-flex flex-wrap align-items-start justify-content-between gap-2">
                            <div class="flex-grow-1">
                                <label class="form-label small text-muted mb-1">Serviço específico</label>
                                <select name="servico_override_servico_id[]" class="form-select form-select-sm servico-override-input">
                                    ${buildServicoOptionsHtml('')}
                                </select>
                            </div>
                            <div class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-secondary btn-servico-use-global me-1" title="Remover override e usar padrão global">
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row" title="Remover linha">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                        <div class="servico-mini-summary mt-2 d-flex flex-wrap gap-2">
                            <span class="badge bg-light text-dark border">Custo total: <strong class="servico-override-custo-total">0,00</strong></span>
                            <span class="badge bg-light text-dark border">Divisor técnico: <strong class="servico-override-divisor">0,0000</strong></span>
                            <span class="badge bg-light text-dark border">Preço mínimo: <strong class="servico-override-preco-minimo">0,00</strong></span>
                            <span class="badge bg-light text-dark border">Preço recomendado: <strong class="servico-override-preco-recomendado">0,00</strong></span>
                        </div>
                        <div class="servico-mini-sections mt-3 d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#${infoPrefix}-sec-capacidade" aria-expanded="false" aria-controls="${infoPrefix}-sec-capacidade">Capacidade</button>
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#${infoPrefix}-sec-custos" aria-expanded="false" aria-controls="${infoPrefix}-sec-custos">Custos Diretos</button>
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#${infoPrefix}-sec-margem" aria-expanded="false" aria-controls="${infoPrefix}-sec-margem">Margem/Taxas</button>
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#${infoPrefix}-sec-tempo" aria-expanded="false" aria-controls="${infoPrefix}-sec-tempo">Tempo Técnico</button>
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#${infoPrefix}-sec-resultado" aria-expanded="false" aria-controls="${infoPrefix}-sec-resultado">Resultado</button>
                        </div>
                        <div class="collapse servico-mini-section" id="${infoPrefix}-sec-capacidade">
                            <div class="servico-mini-section-title">Capacidade</div>
                            <label class="form-label small text-muted mb-1">Custo hora produtiva (R$)</label>
                            <div class="input-group input-group-sm">
                                <input type="number" step="0.01" min="0" max="100000" name="servico_override_custo_hora[]" class="form-control form-control-sm servico-override-input" value="${servicoDefaults.custo_hora || '0.00'}" placeholder="Ex.: 12.50">
                                <button type="button" class="btn btn-outline-secondary btn-servico-detail" data-servico-detail="capacidade" title="Detalhar Capacidade">
                                    <i class="bi bi-sliders2"></i>
                                </button>
                            </div>
                            <div class="servico-info-actions mt-2">
                                <button type="button" class="btn btn-outline-secondary btn-servico-info" data-bs-toggle="collapse" data-bs-target="#${infoPrefix}-capacidade-formula" aria-expanded="false" aria-controls="${infoPrefix}-capacidade-formula">
                                    <i class="bi bi-info-circle me-1"></i>Fórmula
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-servico-info" data-bs-toggle="collapse" data-bs-target="#${infoPrefix}-capacidade-ref" aria-expanded="false" aria-controls="${infoPrefix}-capacidade-ref">
                                    <i class="bi bi-info-circle me-1"></i>Referência
                                </button>
                            </div>
                            <div class="collapse servico-info-collapse mt-1" id="${infoPrefix}-capacidade-formula">
                                <div class="servico-info-note">CustoHora = CustosFixosMensais / HorasProdutivasMensais.</div>
                            </div>
                            <div class="collapse servico-info-collapse mt-1" id="${infoPrefix}-capacidade-ref">
                                <div class="servico-info-note">Usa os mesmos componentes da seção Capacidade dos parâmetros globais.</div>
                            </div>
                        </div>
                        <div class="collapse servico-mini-section" id="${infoPrefix}-sec-custos">
                            <div class="servico-mini-section-title">Custos Diretos</div>
                            <label class="form-label small text-muted mb-1">Custos diretos totais (R$)</label>
                            <div class="input-group input-group-sm mb-2">
                                <input type="number" step="0.01" min="0" max="100000" name="servico_override_custos_diretos[]" class="form-control form-control-sm servico-override-input" value="${servicoDefaults.custos_diretos || '0.00'}" placeholder="Ex.: 18.90">
                                <button type="button" class="btn btn-outline-secondary btn-servico-detail" data-servico-detail="custos_diretos" title="Detalhar Custos Diretos">
                                    <i class="bi bi-sliders2"></i>
                                </button>
                            </div>
                            <label class="form-label small text-muted mb-1">Risco percentual adicional (%)</label>
                            <input type="number" step="0.01" min="0" max="100" name="servico_override_risco[]" class="form-control form-control-sm servico-override-input" value="${servicoDefaults.risco || '0.00'}" placeholder="Ex.: 2.50">
                            <div class="servico-info-actions mt-2">
                                <button type="button" class="btn btn-outline-secondary btn-servico-info" data-bs-toggle="collapse" data-bs-target="#${infoPrefix}-custos-formula" aria-expanded="false" aria-controls="${infoPrefix}-custos-formula">
                                    <i class="bi bi-info-circle me-1"></i>Fórmula
                                </button>
                            </div>
                            <div class="collapse servico-info-collapse mt-1" id="${infoPrefix}-custos-formula">
                                <div class="servico-info-note">CustoTotal = (TempoTecnico x CustoHora + CustosDiretos) + Risco percentual.</div>
                            </div>
                        </div>
                        <div class="collapse servico-mini-section" id="${infoPrefix}-sec-margem">
                            <div class="servico-mini-section-title">Margem/Taxas</div>
                            <label class="form-label small text-muted mb-1">Margem alvo (%)</label>
                            <div class="input-group input-group-sm mb-2">
                                <input type="number" step="0.01" min="0" max="300" name="servico_override_margem[]" class="form-control form-control-sm servico-override-input" value="${servicoDefaults.margem || '0.00'}" placeholder="Ex.: 25.00">
                                <button type="button" class="btn btn-outline-secondary btn-servico-detail" data-servico-detail="margem_taxas" title="Detalhar Margem e Taxas">
                                    <i class="bi bi-sliders2"></i>
                                </button>
                            </div>
                            <label class="form-label small text-muted mb-1">Taxa de recebimento (%)</label>
                            <input type="number" step="0.01" min="0" max="100" name="servico_override_taxa[]" class="form-control form-control-sm servico-override-input mb-2" value="${servicoDefaults.taxa || '0.00'}" placeholder="Ex.: 3.50">
                            <label class="form-label small text-muted mb-1">Imposto (%)</label>
                            <input type="number" step="0.01" min="0" max="100" name="servico_override_imposto[]" class="form-control form-control-sm servico-override-input" value="${servicoDefaults.imposto || '0.00'}" placeholder="Ex.: 0.00">
                            <div class="servico-info-actions mt-2">
                                <button type="button" class="btn btn-outline-secondary btn-servico-info" data-bs-toggle="collapse" data-bs-target="#${infoPrefix}-margem-formula" aria-expanded="false" aria-controls="${infoPrefix}-margem-formula">
                                    <i class="bi bi-info-circle me-1"></i>Fórmula do divisor
                                </button>
                            </div>
                            <div class="collapse servico-info-collapse mt-1" id="${infoPrefix}-margem-formula">
                                <div class="servico-info-note">DivisorTecnico = 1 - (Margem + Taxa + Imposto) / 100.</div>
                            </div>
                        </div>
                        <div class="collapse servico-mini-section" id="${infoPrefix}-sec-tempo">
                            <div class="servico-mini-section-title">Tempo Técnico</div>
                            <label class="form-label small text-muted mb-1">Tempo técnico total (h)</label>
                            <div class="input-group input-group-sm">
                                <input type="number" step="0.01" min="0" max="999" name="servico_override_tempo_tecnico[]" class="form-control form-control-sm servico-override-input" value="${servicoDefaults.tempo_tecnico || '0.00'}" placeholder="Ex.: 1.50">
                                <button type="button" class="btn btn-outline-secondary btn-servico-detail" data-servico-detail="tempo_tecnico" title="Detalhar Tempo Técnico">
                                    <i class="bi bi-sliders2"></i>
                                </button>
                            </div>
                            <div class="servico-info-actions mt-2">
                                <button type="button" class="btn btn-outline-secondary btn-servico-info" data-bs-toggle="collapse" data-bs-target="#${infoPrefix}-tempo-ref" aria-expanded="false" aria-controls="${infoPrefix}-tempo-ref">
                                    <i class="bi bi-info-circle me-1"></i>Referência
                                </button>
                            </div>
                            <div class="collapse servico-info-collapse mt-1" id="${infoPrefix}-tempo-ref">
                                <div class="servico-info-note">Segue a mesma estrutura da seção Tempo Técnico dos parâmetros globais.</div>
                            </div>
                        </div>
                        <div class="collapse servico-mini-section" id="${infoPrefix}-sec-resultado">
                            <div class="servico-mini-section-title">Resultado</div>
                            <label class="form-label small text-muted mb-1">Preço tabela de referência (R$)</label>
                            <div class="input-group input-group-sm mb-2">
                                <input type="number" step="0.01" min="0" max="999999" name="servico_override_preco_tabela[]" class="form-control form-control-sm servico-override-input" value="${servicoDefaults.preco_tabela || '0.00'}" placeholder="Ex.: 99.00">
                                <button type="button" class="btn btn-outline-secondary btn-servico-detail" data-servico-detail="resultado" title="Detalhar Resultado">
                                    <i class="bi bi-sliders2"></i>
                                </button>
                            </div>
                            <div class="text-muted small">Custo total: <span class="fw-semibold servico-override-custo-total">0,00</span></div>
                            <div class="text-muted small">Divisor técnico: <span class="fw-semibold servico-override-divisor">0,0000</span></div>
                            <div class="text-muted small">Preço mínimo técnico: <span class="fw-semibold servico-override-preco-minimo">0,00</span></div>
                            <div class="text-muted small">Preço recomendado: <span class="fw-semibold servico-override-preco-recomendado">0,00</span></div>
                        </div>
                    </div>
                </div>
            </td>
        `;

        return tr;
    };

    const addRow = (tipo) => {
        const tipoNormalizado = ['peca', 'servico', 'produto'].includes(tipo) ? tipo : 'peca';
        const tbody = getOverrideTbodyByTipo(tipoNormalizado);
        if (!tbody) return;
        removePlaceholderRow(tbody);
        if (tipoNormalizado === 'servico') {
            const trServico = createServicoOverrideRow();
            tbody.appendChild(trServico);
            updateServicoOverrideSummary(trServico);
            refreshUnsavedIndicator();
            return;
        }
        const globalEncargo = globalEncargosMap[tipoNormalizado] || '0.00';
        const categoriaLabel = overrideCategoriaHeader[tipoNormalizado] || 'Categoria';
        const encargoLabel = overrideEncargoHeader[tipoNormalizado] || 'Encargos (%)';
        const margemLabel = overrideMargemHeader[tipoNormalizado] || 'Margem (%)';
        const categoriaPlaceholder = overrideCategoryPlaceholder[tipoNormalizado] || 'Ex.: Categoria';
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td data-label="${categoriaLabel}">
                <input type="hidden" name="categoria_id[]" value="">
                <input type="hidden" name="categoria_tipo[]" value="${tipoNormalizado}">
                <input type="text" name="categoria_nome[]" class="form-control form-control-sm" value="" placeholder="${categoriaPlaceholder}">
            </td>
            <td data-label="${encargoLabel}">
                <div class="input-group input-group-sm">
                    <input type="number" step="0.01" min="0" max="300" name="categoria_encargos[]" class="form-control form-control-sm categoria-encargo-input" value="0.00" data-global-encargo="${globalEncargo}">
                    <button type="button" class="btn btn-outline-secondary btn-encargo-config" data-categoria-id="" data-categoria-tipo="${tipoNormalizado}" title="Configurar componentes" disabled>
                        <i class="bi bi-sliders2"></i>
                    </button>
                </div>
                <div class="mt-1">
                    <span class="badge bg-light text-muted border badge-override-status">global</span>
                </div>
            </td>
            <td data-label="${margemLabel}">
                <input type="number" step="0.01" min="0" max="300" name="categoria_margem[]" class="form-control form-control-sm" value="0.00">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(tr);
        updateOverrideBadge(tr);
        refreshUnsavedIndicator();
    };

    addBtn?.addEventListener('click', () => {
        addRow(getActiveOverrideTipo());
    });

    overrideTipoTabs.forEach((tabBtn) => {
        tabBtn.addEventListener('shown.bs.tab', () => {
            updateAddButtonLabel(getActiveOverrideTipo());
        });
    });

    document.addEventListener('click', (event) => {
        const btnDetail = event.target.closest('.btn-servico-detail');
        if (!btnDetail) return;
        const row = btnDetail.closest('tr.servico-override-row');
        if (!row) return;
        const detailTipo = (btnDetail.dataset.servicoDetail || '').trim();
        if (!detailTipo) return;
        openServicoDetailModal(row, detailTipo);
    });

    btnSalvarServicoDetalhes?.addEventListener('click', () => {
        if (!activeServicoDetalhesRow || !activeServicoDetalhesTipo) return;
        if (!modalServicoDetalhesBody) return;

        modalServicoDetalhesBody.querySelectorAll('[data-servico-detail-input]').forEach((input) => {
            const name = input.dataset.servicoDetailInput || '';
            if (!name) return;
            const decimals = parseInt(input.dataset.servicoDetailDecimals || '2', 10);
            setRowNumericValue(activeServicoDetalhesRow, name, parseNumberInput(input.value), Number.isNaN(decimals) ? 2 : decimals);
        });

        applyServicoDetailTipo(activeServicoDetalhesRow, activeServicoDetalhesTipo);
        updateServicoOverrideSummary(activeServicoDetalhesRow);
        refreshUnsavedIndicator();

        const instance = bootstrap.Modal.getInstance(modalServicoDetalhesEl);
        instance?.hide();
        activeServicoDetalhesRow = null;
        activeServicoDetalhesTipo = '';
    });

    modalServicoDetalhesEl?.addEventListener('hidden.bs.modal', () => {
        activeServicoDetalhesRow = null;
        activeServicoDetalhesTipo = '';
        if (modalServicoDetalhesBody) modalServicoDetalhesBody.innerHTML = '';
        if (modalServicoDetalhesResumo) modalServicoDetalhesResumo.innerHTML = '';
    });

    form?.addEventListener('input', () => {
        refreshUnsavedIndicator();
    });

    form?.addEventListener('change', () => {
        refreshUnsavedIndicator();
    });

    document.addEventListener('click', (event) => {
        const btnGlobal = event.target.closest('.btn-servico-use-global');
        if (!btnGlobal) return;
        const tr = btnGlobal.closest('tr');
        if (tr) {
            const tbody = tr.closest('tbody');
            tr.remove();
            ensurePlaceholderRow(tbody, 'servico');
            refreshUnsavedIndicator();
        }
    });

    document.addEventListener('click', (event) => {
        const target = event.target;
        const btn = target.closest('.btn-remove-row');
        if (!btn) return;
        const tr = btn.closest('tr');
        if (tr) {
            const tbody = tr.closest('tbody');
            const tipo = tr.closest('[data-override-table]')?.dataset.overrideTable || 'peca';
            tr.remove();
            ensurePlaceholderRow(tbody, tipo);
            refreshUnsavedIndicator();
        }
    });

    document.addEventListener('input', (event) => {
        const input = event.target.closest('.categoria-encargo-input');
        if (!input) return;
        updateOverrideBadge(input.closest('tr'));
    });

    document.addEventListener('input', (event) => {
        const input = event.target.closest('.servico-override-input');
        if (!input) return;
        updateServicoOverrideSummary(input.closest('tr'));
    });

    document.addEventListener('change', (event) => {
        const selectServico = event.target.closest('select[name="servico_override_servico_id[]"]');
        if (!selectServico) return;
        const row = selectServico.closest('tr');
        if (!row) return;
        const servicoId = parseInt(selectServico.value || '0', 10);
        const servico = servicosCatalogo.find((item) => Number(item.id) === servicoId);
        if (servico) {
            const tempoInput = row.querySelector('input[name="servico_override_tempo_tecnico[]"]');
            const precoTabelaInput = row.querySelector('input[name="servico_override_preco_tabela[]"]');
            if (tempoInput && (!tempoInput.value || parseNumberInput(tempoInput.value) <= 0)) {
                tempoInput.value = Number(servico.tempo_padrao_horas || 0).toFixed(2);
                const tempoMin = Number(servico.tempo_padrao_horas || 0) * 60;
                const detalheTempoNames = [
                    'servico_override_tempo_desmontagem_min',
                    'servico_override_tempo_substituicao_min',
                    'servico_override_tempo_montagem_min',
                    'servico_override_tempo_teste_final_min',
                ];
                const detalheAtualTotal = detalheTempoNames.reduce((acc, name) => acc + getRowNumericValue(row, name, 0), 0);
                if (detalheAtualTotal <= 0) {
                    setRowNumericValue(row, 'servico_override_tempo_desmontagem_min', tempoMin, 2);
                    setRowNumericValue(row, 'servico_override_tempo_substituicao_min', 0, 2);
                    setRowNumericValue(row, 'servico_override_tempo_montagem_min', 0, 2);
                    setRowNumericValue(row, 'servico_override_tempo_teste_final_min', 0, 2);
                }
            }
            if (precoTabelaInput && (!precoTabelaInput.value || parseNumberInput(precoTabelaInput.value) <= 0)) {
                precoTabelaInput.value = Number(servico.valor || 0).toFixed(2);
            }
        }
        updateServicoOverrideSummary(row);
    });

    const encargoModal = document.getElementById('modalEncargoCategoria');
    const encargoModalTitle = document.getElementById('modalEncargoCategoriaLabel');
    const encargoModalHint = document.getElementById('modalEncargoHint');
    const encargoModalBody = document.getElementById('modalEncargoBody');
    const encargoSaveBtn = document.getElementById('btnSalvarEncargosCategoria');
    const encargoTotalSpan = document.getElementById('encargoTotalCategoria');
    const encargoAddBtn = document.getElementById('btnAddEncargoCategoria');
    let encargoCategoriaId = null;
    let encargoTargetInput = null;
    let encargoCategoriaTipo = 'peca';

    const getCsrf = () => {
        const input = document.querySelector('input[name="csrf_test_name"]');
        return input?.value || '';
    };

    const fetchEncargos = async (categoriaId) => {
        const response = await fetch(`<?= base_url('precificacao/categoria-encargos') ?>/${categoriaId}`, {
            method: 'GET',
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        return response.json();
    };

    const postEncargos = async (categoriaId, payload) => {
        const formData = new FormData();
        payload.forEach(item => {
            formData.append('encargo_id[]', item.id || '');
            formData.append('encargo_nome[]', item.nome || '');
            formData.append('encargo_valor[]', item.valor || '0');
        });
        formData.append('csrf_test_name', getCsrf());
        const response = await fetch(`<?= base_url('precificacao/categoria-encargos') ?>/${categoriaId}`, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        return response.json();
    };

    const getDefaultEncargosByTipo = (tipo) => {
        if (tipo === 'servico') {
            return [
                { id: '', nome: 'Risco de retrabalho', percentual: 2 },
                { id: '', nome: 'Tempo indireto por atendimento', percentual: 2 },
                { id: '', nome: 'Perdas operacionais', percentual: 1 },
            ];
        }
        if (tipo === 'produto') {
            return [
                { id: '', nome: 'Perdas operacionais', percentual: 2 },
                { id: '', nome: 'Encargos operacionais', percentual: 8 },
            ];
        }
        return [
            { id: '', nome: 'Triagem e teste', percentual: 4 },
            { id: '', nome: 'Risco de garantia', percentual: 5 },
            { id: '', nome: 'Armazenagem/obsolescencia', percentual: 3 },
        ];
    };

    const getModalTitleByTipo = (tipo) => {
        if (tipo === 'servico') {
            return 'Parâmetros de risco da categoria de serviço';
        }
        if (tipo === 'produto') {
            return 'Parâmetros de encargos da categoria de produto';
        }
        return 'Parâmetros de encargos da categoria de peça';
    };

    const getModalHintByTipo = (tipo) => {
        if (tipo === 'servico') {
            return 'Defina os componentes de risco/indiretos que impactam a precificação técnica do serviço por categoria.';
        }
        if (tipo === 'produto') {
            return 'Defina os componentes operacionais da categoria de produto (perdas, encargos e custos de operação).';
        }
        return 'Defina os componentes que somam os encargos desta categoria de peça.';
    };

    const renderEncargos = (items, tipo) => {
        if (!encargoModalBody) return;
        const normalized = (items && items.length) ? items : getDefaultEncargosByTipo(tipo);
        const rows = normalized.map(item => `
            <tr>
                <td>
                    <input type="hidden" class="encargo-id" value="${item.id || ''}">
                    <input type="text" class="form-control form-control-sm encargo-nome" value="${item.nome || ''}" placeholder="Ex.: Triagem e teste">
                </td>
                <td style="width:140px;">
                    <input type="number" step="0.01" min="0" max="300" class="form-control form-control-sm encargo-valor" value="${Number(item.percentual || item.valor || 0).toFixed(2)}">
                </td>
                <td class="text-center" style="width:60px;">
                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-encargo">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `).join('');
        encargoModalBody.innerHTML = rows;
        updateEncargoTotal();
    };

    const collectEncargos = () => {
        const rows = encargoModalBody?.querySelectorAll('tr') || [];
        return Array.from(rows).map(row => ({
            id: row.querySelector('.encargo-id')?.value || '',
            nome: row.querySelector('.encargo-nome')?.value || '',
            valor: row.querySelector('.encargo-valor')?.value || '0'
        }));
    };

    const updateEncargoTotal = () => {
        const rows = encargoModalBody?.querySelectorAll('.encargo-valor') || [];
        let total = 0;
        rows.forEach(input => {
            const val = parseFloat(input.value || '0');
            if (!isNaN(val)) total += val;
        });
        if (encargoTotalSpan) encargoTotalSpan.textContent = total.toFixed(2) + '%';
    };

    document.addEventListener('input', (event) => {
        if (event.target.classList.contains('encargo-valor')) {
            updateEncargoTotal();
        }
    });

    document.addEventListener('click', (event) => {
        const btn = event.target.closest('.btn-encargo-config');
        if (!btn) return;
        const row = btn.closest('tr');
        const categoriaId = row?.querySelector('input[name="categoria_id[]"]')?.value || '';
        const categoriaTipo = (row?.querySelector('input[name="categoria_tipo[]"]')?.value || btn.dataset.categoriaTipo || 'peca').toLowerCase();
        const encargoInput = row?.querySelector('input[name="categoria_encargos[]"]');
        if (!categoriaId) {
            if (window.Swal) {
                window.Swal.fire('Salve primeiro', 'Para configurar encargos, salve a categoria e depois abra novamente.', 'info');
            }
            return;
        }
        encargoCategoriaId = categoriaId;
        encargoTargetInput = encargoInput;
        encargoCategoriaTipo = ['peca', 'servico', 'produto'].includes(categoriaTipo) ? categoriaTipo : 'peca';
        if (encargoModalTitle) {
            encargoModalTitle.textContent = getModalTitleByTipo(encargoCategoriaTipo);
        }
        if (encargoModalHint) {
            encargoModalHint.textContent = getModalHintByTipo(encargoCategoriaTipo);
        }
        fetchEncargos(categoriaId).then(data => {
            if (!data?.ok) {
                throw new Error(data?.message || 'Falha ao carregar encargos');
            }
            renderEncargos(data.items || [], encargoCategoriaTipo);
            const modal = new bootstrap.Modal(encargoModal);
            modal.show();
        }).catch(err => {
            if (window.Swal) {
                window.Swal.fire('Erro', err.message || 'Falha ao carregar encargos.', 'error');
            }
        });
    });

    encargoAddBtn?.addEventListener('click', () => {
        if (!encargoModalBody) return;
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>
                <input type="hidden" class="encargo-id" value="">
                <input type="text" class="form-control form-control-sm encargo-nome" value="" placeholder="Ex.: Componente">
            </td>
            <td style="width:140px;">
                <input type="number" step="0.01" min="0" max="300" class="form-control form-control-sm encargo-valor" value="0.00">
            </td>
            <td class="text-center" style="width:60px;">
                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-encargo">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;
        encargoModalBody.appendChild(tr);
        updateEncargoTotal();
        refreshUnsavedIndicator();
    });

    document.addEventListener('click', (event) => {
        const btn = event.target.closest('.btn-remove-encargo');
        if (!btn) return;
        const tr = btn.closest('tr');
        if (tr) tr.remove();
        updateEncargoTotal();
        refreshUnsavedIndicator();
    });

    encargoSaveBtn?.addEventListener('click', () => {
        if (!encargoCategoriaId) return;
        const payload = collectEncargos();
        postEncargos(encargoCategoriaId, payload).then(data => {
            if (!data?.ok) {
                throw new Error(data?.message || 'Falha ao salvar encargos');
            }
            if (encargoTargetInput) {
                encargoTargetInput.value = Number(data.total || 0).toFixed(2);
            }
            refreshUnsavedIndicator();
            const modal = bootstrap.Modal.getInstance(encargoModal);
            modal?.hide();
            if (window.Swal) {
                window.Swal.fire('Encargos salvos', 'Os parâmetros de encargos foram atualizados.', 'success');
            }
        }).catch(err => {
            if (window.Swal) {
                window.Swal.fire('Erro', err.message || 'Falha ao salvar encargos.', 'error');
            }
        });
    });

    document.querySelectorAll('[data-override-table] tbody tr').forEach((row) => {
        updateOverrideBadge(row);
        if (row.classList.contains('servico-override-row')) {
            updateServicoOverrideSummary(row);
        }
    });
    updateAddButtonLabel(getActiveOverrideTipo());
    resetUnsavedBaseline();
})();
</script>
<?= $this->endSection() ?>

