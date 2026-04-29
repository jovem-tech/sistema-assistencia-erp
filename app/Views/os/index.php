<?php
$listFilters = $listFilters ?? [];
$statusGrouped = $statusGrouped ?? [];
$statusGroupedOpen = $statusGroupedOpen ?? $statusGrouped;
$statusFlat = $statusFlat ?? [];
$statusClosedOptions = $statusClosedOptions ?? [];
$macrofases = $macrofases ?? [];
$tecnicos = $tecnicos ?? [];
$tiposServico = $tiposServico ?? [];
$situacaoOptions = $situacaoOptions ?? [];

$statusSelected = is_array($listFilters['status'] ?? null) ? $listFilters['status'] : [];
$statusClosedSelected = (string) ($listFilters['status_fechadas'] ?? '');
$statusScope = (string) ($listFilters['status_scope'] ?? '');
if ($statusClosedSelected !== '') {
    $statusSelected = [];
}
$estadoFluxoOptions = [
    'em_atendimento' => 'Em atendimento',
    'em_execucao' => 'Em execução',
    'pausado' => 'Pausado',
    'pronto' => 'Pronto',
    'encerrado' => 'Encerrado',
    'cancelado' => 'Cancelado',
];

$statusLabels = [];
foreach ($statusFlat as $statusItem) {
    $code = (string) ($statusItem['codigo'] ?? '');
    if ($code === '') {
        continue;
    }
    $statusLabels[$code] = (string) ($statusItem['nome'] ?? $code);
}

$tecnicoLabels = [];
foreach ($tecnicos as $tecnico) {
    $tecnicoId = (string) ($tecnico['id'] ?? '');
    if ($tecnicoId === '') {
        continue;
    }
    $tecnicoLabels[$tecnicoId] = (string) ($tecnico['nome'] ?? ('Técnico #' . $tecnicoId));
}

$labelsMap = [
    'status' => $statusLabels,
    'status_fechadas' => $statusClosedOptions,
    'status_scope' => [
        'all' => 'Todos os status',
    ],
    'macrofases' => $macrofases,
    'estado_fluxo' => $estadoFluxoOptions,
    'tecnicos' => $tecnicoLabels,
    'situacao' => $situacaoOptions,
    'legado' => [
        '1' => 'Somente OS legado',
    ],
];

$tableTitleText = 'Ordens em aberto';
$tableSubtitleText = 'A listagem inicia nas etapas abertas da oficina. Use "Ordens fechadas" para consultar entregas, devoluções e descartes.';

if ($statusClosedSelected !== '') {
    if ($statusClosedSelected === 'fechadas') {
        $tableTitleText = 'Ordens fechadas';
        $tableSubtitleText = 'Exibindo apenas OS encerradas por entrega, devolução sem reparo ou descarte.';
    } else {
        $tableTitleText = 'Fechadas: ' . ($statusClosedOptions[$statusClosedSelected] ?? $statusClosedSelected);
        $tableSubtitleText = 'Exibindo apenas um desfecho operacional da fila encerrada.';
    }
} elseif ($statusScope === 'all') {
    $tableTitleText = 'Todas as ordens de serviço';
    $tableSubtitleText = 'Exibindo OS abertas e fechadas sem o recorte padrão da fila.';
} elseif (count($statusSelected) === 1) {
    $selectedCode = (string) $statusSelected[0];
    $tableTitleText = 'Ordens abertas: ' . ($statusLabels[$selectedCode] ?? $selectedCode);
    $tableSubtitleText = 'Fila aberta refinada por um status detalhado.';
} elseif (count($statusSelected) > 1) {
    $tableTitleText = 'Ordens abertas filtradas';
    $tableSubtitleText = count($statusSelected) . ' status detalhados selecionados na fila aberta.';
}
?>

<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="os-list-page" data-sidebar-auto-collapse="hover">
    <div class="page-header">
        <div class="d-flex align-items-center gap-3 os-page-heading">
            <h2><i class="bi bi-clipboard-check me-2"></i>Ordens de Serviço</h2>
            <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('ordens-de-servico')" title="Ajuda sobre este módulo">
                <i class="bi bi-question-circle me-1"></i> Ajuda
            </button>
        </div>
        <?php if (can('os', 'criar')): ?>
            <button
                type="button"
                class="btn btn-glow os-page-create-btn"
                data-os-modal-role="create"
                data-os-modal-url="<?= base_url('os/nova?embed=1') ?>"
                data-os-modal-title="Nova Ordem de Serviço"
            >
                <i class="bi bi-plus-lg me-1"></i>Nova OS
            </button>
        <?php endif; ?>
    </div>

    <div class="d-md-none mb-3">
        <button type="button" class="btn btn-outline-primary os-mobile-filter-btn" id="osOpenMobileFilters" data-bs-toggle="offcanvas" data-bs-target="#osFiltersDrawer" aria-controls="osFiltersDrawer">
            <i class="bi bi-funnel"></i>
            Filtrar ordens
            <span class="badge text-bg-primary" id="osMobileFilterCount">0</span>
        </button>
    </div>

    <div class="card glass-card os-filters-card mb-3 d-none d-md-block">
        <div class="card-body">
            <form id="osFiltersDesktopForm" data-os-filter-form="desktop" novalidate>
                <input type="hidden" data-filter-field="legado" value="<?= esc((string) ($listFilters['legado'] ?? '')) ?>">
                <div class="os-filters-inline">
                    <div class="os-filter-field" data-filter-block="q">
                        <label class="form-label" for="osFilterQDesktop">Busca global</label>
                        <div class="os-input-icon">
                            <i class="bi bi-search"></i>
                            <input
                                type="text"
                                class="form-control"
                                id="osFilterQDesktop"
                                data-filter-field="q"
                                placeholder="Cliente, equipamento, número da OS ou OS legado..."
                                value="<?= esc((string) ($listFilters['q'] ?? '')) ?>"
                                autocomplete="off"
                            >
                        </div>
                        <div class="os-filter-helper">Busca cliente, equipamento, número da OS ou OS legado.</div>
                    </div>

                    <div class="os-filter-field" data-filter-block="status">
                        <label class="form-label" for="osFilterStatusDesktop">Ordens abertas</label>
                        <select
                            id="osFilterStatusDesktop"
                            data-filter-field="status"
                            class="form-select js-os-select2"
                            multiple
                            data-placeholder="Selecione etapas abertas"
                        >
                            <?php foreach ($statusGroupedOpen as $macro => $items): ?>
                                <?php if (empty($items)) continue; ?>
                                <optgroup label="<?= esc(ucwords(str_replace('_', ' ', (string) $macro))) ?>">
                                    <?php foreach ($items as $item): ?>
                                        <?php $statusCode = (string) ($item['codigo'] ?? ''); ?>
                                        <?php if ($statusCode === '') continue; ?>
                                        <option value="<?= esc($statusCode) ?>" <?= in_array($statusCode, $statusSelected, true) ? 'selected' : '' ?>>
                                            <?= esc((string) ($item['nome'] ?? $statusCode)) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endforeach; ?>
                        </select>
                        <div class="os-filter-helper">Refina a fila pelas etapas abertas da oficina.</div>
                    </div>

                    <div class="os-filter-field" data-filter-block="status_fechadas">
                        <label class="form-label" for="osFilterClosedDesktop">Ordens fechadas</label>
                        <select
                            id="osFilterClosedDesktop"
                            data-filter-field="status_fechadas"
                            class="form-select js-os-select2"
                            data-placeholder="Listar fechadas"
                        >
                            <option value="">Exibindo abertas</option>
                            <?php foreach ($statusClosedOptions as $closedStatusCode => $closedStatusName): ?>
                                <option value="<?= esc((string) $closedStatusCode) ?>" <?= $statusClosedSelected === (string) $closedStatusCode ? 'selected' : '' ?>>
                                    <?= esc((string) $closedStatusName) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="os-filter-helper">Troca a fila para OS encerradas.</div>
                    </div>

                    <div class="os-filters-actions">
                        <button type="button" class="btn btn-glow" data-filter-action="apply">
                            <i class="bi bi-funnel me-1"></i>Aplicar
                        </button>
                        <button type="button" class="btn btn-outline-secondary" data-filter-action="clear">
                            <i class="bi bi-x-circle me-1"></i>Limpar
                        </button>
                        <button
                            type="button"
                            class="btn btn-outline-primary"
                            id="osToggleAdvanced"
                            data-bs-toggle="collapse"
                            data-bs-target="#osAdvancedFiltersCollapse"
                            aria-expanded="false"
                            aria-controls="osAdvancedFiltersCollapse"
                        >
                            <i class="bi bi-sliders me-1"></i>Filtros avançados
                        </button>
                    </div>
                </div>

                <div class="collapse os-filters-advanced" id="osAdvancedFiltersCollapse">
                    <div class="row g-3">
                        <div class="col-12 col-xxl-3 col-xl-4 col-md-6">
                            <label class="form-label" for="osFilterStatusScopeDesktop">Status geral</label>
                            <select id="osFilterStatusScopeDesktop" data-filter-field="status_scope" class="form-select js-os-select2" data-placeholder="Padrão: ordens abertas">
                                <option value="">Padrão: ordens abertas</option>
                                <option value="all" <?= $statusScope === 'all' ? 'selected' : '' ?>>Todos os status</option>
                            </select>
                            <div class="form-text">Use aqui quando quiser consultar abertas e fechadas na mesma fila.</div>
                        </div>

                        <div class="col-12 col-xxl-3 col-xl-4 col-md-6">
                            <label class="form-label" for="osFilterMacrofaseDesktop">Macrofase</label>
                            <select id="osFilterMacrofaseDesktop" data-filter-field="macrofase" class="form-select js-os-select2" data-placeholder="Todas as macrofases">
                                <option value="">Todas</option>
                                <?php foreach ($macrofases as $macroCode => $macroName): ?>
                                    <option value="<?= esc((string) $macroCode) ?>" <?= (($listFilters['macrofase'] ?? '') === (string) $macroCode) ? 'selected' : '' ?>>
                                        <?= esc((string) $macroName) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 col-xxl-3 col-xl-4 col-md-6">
                            <label class="form-label" for="osFilterFluxoDesktop">Estado do fluxo</label>
                            <select id="osFilterFluxoDesktop" data-filter-field="estado_fluxo" class="form-select js-os-select2" data-placeholder="Todos os estados">
                                <option value="">Todos</option>
                                <?php foreach ($estadoFluxoOptions as $fluxoCode => $fluxoName): ?>
                                    <option value="<?= esc($fluxoCode) ?>" <?= (($listFilters['estado_fluxo'] ?? '') === $fluxoCode) ? 'selected' : '' ?>>
                                        <?= esc($fluxoName) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 col-xxl-3 col-xl-4 col-md-6">
                            <label class="form-label" for="osFilterSituacaoDesktop">Situação operacional</label>
                            <select id="osFilterSituacaoDesktop" data-filter-field="situacao" class="form-select js-os-select2" data-placeholder="Todas">
                                <option value="">Todas</option>
                                <?php foreach ($situacaoOptions as $situacaoCode => $situacaoName): ?>
                                    <option value="<?= esc((string) $situacaoCode) ?>" <?= (($listFilters['situacao'] ?? '') === (string) $situacaoCode) ? 'selected' : '' ?>>
                                        <?= esc((string) $situacaoName) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 col-xxl-3 col-xl-4 col-md-6">
                            <label class="form-label" for="osFilterTecnicoDesktop">Técnico responsável</label>
                            <select id="osFilterTecnicoDesktop" data-filter-field="tecnico_id" class="form-select js-os-select2" data-placeholder="Todos">
                                <option value="">Todos</option>
                                <?php foreach ($tecnicos as $tecnico): ?>
                                    <?php $tecnicoId = (string) ($tecnico['id'] ?? ''); ?>
                                    <?php if ($tecnicoId === '') continue; ?>
                                    <option value="<?= esc($tecnicoId) ?>" <?= ((string) ($listFilters['tecnico_id'] ?? '') === $tecnicoId) ? 'selected' : '' ?>>
                                        <?= esc((string) ($tecnico['nome'] ?? ('Técnico #' . $tecnicoId))) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 col-xxl-3 col-xl-4 col-md-6">
                            <label class="form-label" for="osFilterTipoServicoDesktop">Tipo de serviço</label>
                            <select id="osFilterTipoServicoDesktop" data-filter-field="tipo_servico" class="form-select js-os-select2" data-placeholder="Todos os serviços">
                                <option value="">Todos</option>
                                <?php foreach ($tiposServico as $servico): ?>
                                    <?php $descricao = trim((string) ($servico['descricao'] ?? '')); ?>
                                    <?php if ($descricao === '') continue; ?>
                                    <option value="<?= esc($descricao) ?>" <?= (($listFilters['tipo_servico'] ?? '') === $descricao) ? 'selected' : '' ?>>
                                        <?= esc($descricao) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-6 col-xxl-2 col-xl-4 col-md-3">
                            <label class="form-label" for="osFilterDataInicioDesktop">Abertura de</label>
                            <input type="date" id="osFilterDataInicioDesktop" data-filter-field="data_inicio" class="form-control" value="<?= esc((string) ($listFilters['data_inicio'] ?? '')) ?>">
                        </div>

                        <div class="col-6 col-xxl-2 col-xl-4 col-md-3">
                            <label class="form-label" for="osFilterDataFimDesktop">Abertura até</label>
                            <input type="date" id="osFilterDataFimDesktop" data-filter-field="data_fim" class="form-control" value="<?= esc((string) ($listFilters['data_fim'] ?? '')) ?>">
                        </div>

                        <div class="col-6 col-xxl-2 col-xl-3 col-md-3">
                            <label class="form-label" for="osFilterValorMinDesktop">Valor mínimo</label>
                            <input type="text" id="osFilterValorMinDesktop" data-filter-field="valor_min" class="form-control mask-money" placeholder="0,00" value="<?= esc((string) ($listFilters['valor_min'] ?? '')) ?>">
                        </div>

                        <div class="col-6 col-xxl-2 col-xl-3 col-md-3">
                            <label class="form-label" for="osFilterValorMaxDesktop">Valor máximo</label>
                            <input type="text" id="osFilterValorMaxDesktop" data-filter-field="valor_max" class="form-control mask-money" placeholder="0,00" value="<?= esc((string) ($listFilters['valor_max'] ?? '')) ?>">
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="offcanvas offcanvas-end" tabindex="-1" id="osFiltersDrawer" aria-labelledby="osFiltersDrawerLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="osFiltersDrawerLabel"><i class="bi bi-funnel me-2"></i>Filtros de OS</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Fechar"></button>
        </div>
        <div class="offcanvas-body">
            <form id="osFiltersMobileForm" data-os-filter-form="mobile" novalidate>
                <input type="hidden" data-filter-field="legado" value="<?= esc((string) ($listFilters['legado'] ?? '')) ?>">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label" for="osFilterQMobile">Busca global</label>
                        <div class="os-input-icon">
                            <i class="bi bi-search"></i>
                            <input
                                type="text"
                                class="form-control"
                                id="osFilterQMobile"
                                data-filter-field="q"
                                placeholder="Cliente, equipamento, número da OS ou OS legado..."
                                value="<?= esc((string) ($listFilters['q'] ?? '')) ?>"
                                autocomplete="off"
                            >
                        </div>
                        <div class="os-filter-helper">Busca cliente, equipamento, número da OS ou OS legado.</div>
                    </div>

                    <div class="col-12" data-filter-block="status">
                        <label class="form-label" for="osFilterStatusMobile">Ordens abertas</label>
                        <select
                            id="osFilterStatusMobile"
                            data-filter-field="status"
                            class="form-select js-os-select2"
                            multiple
                            data-placeholder="Selecione etapas abertas"
                        >
                            <?php foreach ($statusGroupedOpen as $macro => $items): ?>
                                <?php if (empty($items)) continue; ?>
                                <optgroup label="<?= esc(ucwords(str_replace('_', ' ', (string) $macro))) ?>">
                                    <?php foreach ($items as $item): ?>
                                        <?php $statusCode = (string) ($item['codigo'] ?? ''); ?>
                                        <?php if ($statusCode === '') continue; ?>
                                        <option value="<?= esc($statusCode) ?>" <?= in_array($statusCode, $statusSelected, true) ? 'selected' : '' ?>>
                                            <?= esc((string) ($item['nome'] ?? $statusCode)) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endforeach; ?>
                        </select>
                        <div class="os-filter-helper">Refina a fila pelas etapas abertas da oficina.</div>
                    </div>

                    <div class="col-12" data-filter-block="status_fechadas">
                        <label class="form-label" for="osFilterClosedMobile">Ordens fechadas</label>
                        <select
                            id="osFilterClosedMobile"
                            data-filter-field="status_fechadas"
                            class="form-select js-os-select2"
                            data-placeholder="Listar fechadas"
                        >
                            <option value="">Exibindo abertas</option>
                            <?php foreach ($statusClosedOptions as $closedStatusCode => $closedStatusName): ?>
                                <option value="<?= esc((string) $closedStatusCode) ?>" <?= $statusClosedSelected === (string) $closedStatusCode ? 'selected' : '' ?>>
                                    <?= esc((string) $closedStatusName) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="os-filter-helper">Use para consultar entregas, devoluções e descartes.</div>
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="osFilterStatusScopeMobile">Status geral</label>
                        <select id="osFilterStatusScopeMobile" data-filter-field="status_scope" class="form-select js-os-select2" data-placeholder="Padrão: ordens abertas">
                            <option value="">Padrão: ordens abertas</option>
                            <option value="all" <?= $statusScope === 'all' ? 'selected' : '' ?>>Todos os status</option>
                        </select>
                        <div class="form-text">Amplia a consulta para abertas + fechadas na mesma listagem.</div>
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="osFilterMacrofaseMobile">Macrofase</label>
                        <select id="osFilterMacrofaseMobile" data-filter-field="macrofase" class="form-select js-os-select2" data-placeholder="Todas as macrofases">
                            <option value="">Todas</option>
                            <?php foreach ($macrofases as $macroCode => $macroName): ?>
                                <option value="<?= esc((string) $macroCode) ?>" <?= (($listFilters['macrofase'] ?? '') === (string) $macroCode) ? 'selected' : '' ?>>
                                    <?= esc((string) $macroName) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="osFilterFluxoMobile">Estado do fluxo</label>
                        <select id="osFilterFluxoMobile" data-filter-field="estado_fluxo" class="form-select js-os-select2" data-placeholder="Todos os estados">
                            <option value="">Todos</option>
                            <?php foreach ($estadoFluxoOptions as $fluxoCode => $fluxoName): ?>
                                <option value="<?= esc($fluxoCode) ?>" <?= (($listFilters['estado_fluxo'] ?? '') === $fluxoCode) ? 'selected' : '' ?>>
                                    <?= esc($fluxoName) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="osFilterSituacaoMobile">Situação operacional</label>
                        <select id="osFilterSituacaoMobile" data-filter-field="situacao" class="form-select js-os-select2" data-placeholder="Todas">
                            <option value="">Todas</option>
                            <?php foreach ($situacaoOptions as $situacaoCode => $situacaoName): ?>
                                <option value="<?= esc((string) $situacaoCode) ?>" <?= (($listFilters['situacao'] ?? '') === (string) $situacaoCode) ? 'selected' : '' ?>>
                                    <?= esc((string) $situacaoName) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="osFilterTecnicoMobile">Técnico responsável</label>
                        <select id="osFilterTecnicoMobile" data-filter-field="tecnico_id" class="form-select js-os-select2" data-placeholder="Todos">
                            <option value="">Todos</option>
                            <?php foreach ($tecnicos as $tecnico): ?>
                                <?php $tecnicoId = (string) ($tecnico['id'] ?? ''); ?>
                                <?php if ($tecnicoId === '') continue; ?>
                                <option value="<?= esc($tecnicoId) ?>" <?= ((string) ($listFilters['tecnico_id'] ?? '') === $tecnicoId) ? 'selected' : '' ?>>
                                    <?= esc((string) ($tecnico['nome'] ?? ('Técnico #' . $tecnicoId))) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="osFilterTipoServicoMobile">Tipo de serviço</label>
                        <select id="osFilterTipoServicoMobile" data-filter-field="tipo_servico" class="form-select js-os-select2" data-placeholder="Todos os serviços">
                            <option value="">Todos</option>
                            <?php foreach ($tiposServico as $servico): ?>
                                <?php $descricao = trim((string) ($servico['descricao'] ?? '')); ?>
                                <?php if ($descricao === '') continue; ?>
                                <option value="<?= esc($descricao) ?>" <?= (($listFilters['tipo_servico'] ?? '') === $descricao) ? 'selected' : '' ?>>
                                    <?= esc($descricao) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-6">
                        <label class="form-label" for="osFilterDataInicioMobile">Abertura de</label>
                        <input type="date" id="osFilterDataInicioMobile" data-filter-field="data_inicio" class="form-control" value="<?= esc((string) ($listFilters['data_inicio'] ?? '')) ?>">
                    </div>

                    <div class="col-6">
                        <label class="form-label" for="osFilterDataFimMobile">Abertura até</label>
                        <input type="date" id="osFilterDataFimMobile" data-filter-field="data_fim" class="form-control" value="<?= esc((string) ($listFilters['data_fim'] ?? '')) ?>">
                    </div>

                    <div class="col-6">
                        <label class="form-label" for="osFilterValorMinMobile">Valor mínimo</label>
                        <input type="text" id="osFilterValorMinMobile" data-filter-field="valor_min" class="form-control mask-money" placeholder="0,00" value="<?= esc((string) ($listFilters['valor_min'] ?? '')) ?>">
                    </div>

                    <div class="col-6">
                        <label class="form-label" for="osFilterValorMaxMobile">Valor máximo</label>
                        <input type="text" id="osFilterValorMaxMobile" data-filter-field="valor_max" class="form-control mask-money" placeholder="0,00" value="<?= esc((string) ($listFilters['valor_max'] ?? '')) ?>">
                    </div>
                </div>

                <div class="os-offcanvas-actions mt-4">
                    <button type="button" class="btn btn-glow" data-filter-action="apply">
                        <i class="bi bi-funnel me-1"></i>Aplicar filtros
                    </button>
                    <button type="button" class="btn btn-outline-secondary" data-filter-action="clear">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>Limpar filtros
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="os-active-filters mb-3 d-none" id="osActiveFiltersWrap">
        <div class="os-active-filters-title">Filtros ativos</div>
        <div class="os-filter-chips" id="osActiveFilterChips"></div>
        <button type="button" class="btn btn-sm btn-outline-secondary d-none" id="osClearAllFilters">
            <i class="bi bi-x-circle me-1"></i>Limpar todos
        </button>
    </div>

    <div class="card glass-card os-table-wrap ds-table-responsive-card">
        <div class="card-body position-relative">
            <div class="os-table-loading" id="osTableLoading">
                <div class="os-table-loading-inner">
                    <div class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></div>
                    <span>Aplicando filtros...</span>
                </div>
            </div>

            <div class="os-table-header">
                <div class="os-table-heading">
                    <h5 class="os-table-title" id="osTableTitle"><i class="bi bi-list-check me-2"></i><span id="osTableTitleText"><?= esc($tableTitleText) ?></span></h5>
                    <div class="os-table-subtitle" id="osTableSubtitle"><?= esc($tableSubtitleText) ?></div>
                </div>
                <div class="os-table-meta">
                    <span class="spinner-border spinner-border-sm d-none" id="osResultsSpinner" role="status" aria-hidden="true"></span>
                    <span id="osResultsCounter">Carregando...</span>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover os-datatable-responsive" id="osTable">
                    <thead>
                        <tr>
                            <th class="os-control-heading" aria-label="Detalhes da linha"></th>
                            <th>Foto</th>
                            <th>N OS</th>
                            <th>Cliente</th>
                            <th>Equipamento</th>
                            <th>Relato</th>
                            <th>Datas</th>
                            <th>Status</th>
                            <th>Valor Total</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade dashboard-os-modal" id="osCreateModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl modal-fullscreen-md-down">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="osCreateModalTitle">Nova Ordem de Serviço</h5>
                    <button type="button" class="btn-close ms-auto" id="osCreateModalCloseBtn" aria-label="Fechar"></button>
                </div>
                <div class="modal-body p-0 position-relative">
                    <div class="dashboard-os-modal-loading" id="osCreateModalLoading">
                        <div class="spinner-border text-primary" role="status" aria-hidden="true"></div>
                        <span>Carregando...</span>
                    </div>
                    <iframe id="osCreateModalFrame" title="Nova Ordem de Serviço" class="dashboard-os-modal-frame" src="about:blank"></iframe>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade dashboard-os-modal" id="osDetailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl modal-fullscreen-md-down">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="osDetailsModalTitle">Detalhes</h5>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body p-0 position-relative">
                    <div class="dashboard-os-modal-loading" id="osDetailsModalLoading">
                        <div class="spinner-border text-primary" role="status" aria-hidden="true"></div>
                        <span>Carregando...</span>
                    </div>
                    <iframe id="osDetailsModalFrame" title="Detalhes" class="dashboard-os-modal-frame" src="about:blank"></iframe>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="osPhotosModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl modal-fullscreen-md-down">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="osPhotosModalTitle">Fotos da OS</h5>
                        <div class="small text-muted" id="osPhotosModalSubtitle">Visualizador de fotos do equipamento e da abertura.</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body position-relative">
                    <div class="os-photo-viewer-loading d-none" id="osPhotosModalLoading">
                        <div class="spinner-border text-primary" role="status" aria-hidden="true"></div>
                        <span>Carregando fotos...</span>
                    </div>

                    <ul class="nav nav-pills os-photo-tabs mb-3" id="osPhotosTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="osPhotosEquipTab" data-bs-toggle="pill" data-bs-target="#osPhotosEquipPane" type="button" role="tab" aria-controls="osPhotosEquipPane" aria-selected="true">
                                Fotos do Equipamento
                                <span class="os-photo-tab-count" id="osPhotosEquipCount">0</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="osPhotosEntryTab" data-bs-toggle="pill" data-bs-target="#osPhotosEntryPane" type="button" role="tab" aria-controls="osPhotosEntryPane" aria-selected="false">
                                Fotos da Abertura
                                <span class="os-photo-tab-count" id="osPhotosEntryCount">0</span>
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="osPhotosEquipPane" role="tabpanel" aria-labelledby="osPhotosEquipTab" tabindex="0">
                            <div class="os-photo-viewer-panel">
                                <div class="os-photo-viewer-stage-wrap d-none" id="osPhotosEquipStageWrap">
                                    <div class="os-photo-viewer-stage">
                                        <img id="osPhotosEquipStageImage" alt="Preview da foto do equipamento">
                                    </div>
                                    <div class="os-photo-viewer-stage-caption" id="osPhotosEquipStageCaption">-</div>
                                </div>
                                <div class="os-photo-viewer-empty d-none" id="osPhotosEquipEmpty">
                                    <i class="bi bi-image text-muted"></i>
                                    <span>Nenhuma foto de perfil cadastrada para este equipamento.</span>
                                </div>
                                <div class="os-photo-viewer-grid" id="osPhotosEquipGrid"></div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="osPhotosEntryPane" role="tabpanel" aria-labelledby="osPhotosEntryTab" tabindex="0">
                            <div class="os-photo-viewer-panel">
                                <div class="os-photo-viewer-stage-wrap d-none" id="osPhotosEntryStageWrap">
                                    <div class="os-photo-viewer-stage">
                                        <img id="osPhotosEntryStageImage" alt="Preview da foto de abertura da OS">
                                    </div>
                                    <div class="os-photo-viewer-stage-caption" id="osPhotosEntryStageCaption">-</div>
                                </div>
                                <div class="os-photo-viewer-empty d-none" id="osPhotosEntryEmpty">
                                    <i class="bi bi-camera text-muted"></i>
                                    <span>Nenhuma foto foi registrada na abertura desta ordem de serviço.</span>
                                </div>
                                <div class="os-photo-viewer-grid" id="osPhotosEntryGrid"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="osDatesModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable modal-fullscreen-md-down">
            <div class="modal-content">
                <form id="osDatesModalForm" class="os-status-modal-form" novalidate>
                    <div class="modal-header">
                        <h5 class="modal-title">Atualizar prazos da OS</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="os-status-modal-summary mb-4">
                            <div class="small text-muted">OS selecionada</div>
                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                                <div>
                                    <div class="fw-semibold fs-4" id="osDatesModalNumero">-</div>
                                    <div class="small text-muted mt-1">Atualize apenas a previsão sem sair da listagem. Entrada e entrega seguem o fluxo operacional correto.</div>
                                </div>
                                <div class="os-status-modal-badges" id="osDatesModalBadges"></div>
                            </div>
                            <div class="row g-3 mt-1">
                                <div class="col-12 col-lg-6">
                                    <div class="os-status-context-card">
                                        <div class="os-status-context-title">Cliente</div>
                                        <div class="os-status-context-name" id="osDatesModalClientName">-</div>
                                    </div>
                                </div>
                                <div class="col-12 col-lg-6">
                                    <div class="os-status-context-card">
                                        <div class="os-status-context-title">Equipamento</div>
                                        <div class="os-status-context-name" id="osDatesModalEquipmentName">-</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-4">
                            <div class="col-12 col-xl-7">
                                <div class="os-status-modal-panel">
                                    <div class="os-status-modal-section">
                                        <label class="form-label" for="osDatesModalEntrada">Data de entrada</label>
                                        <input type="text" class="form-control" id="osDatesModalEntrada" value="-" readonly disabled>
                                        <div class="form-text">A data de entrada e definida no momento da abertura da OS.</div>
                                    </div>

                                    <div class="row g-3">
                                        <div class="col-12 col-md-4">
                                            <label class="form-label" for="osDatesModalPreset">Atalho de prazo</label>
                                            <select class="form-select" id="osDatesModalPreset">
                                                <option value="">Manual</option>
                                                <option value="1">1 dia</option>
                                                <option value="3">3 dias</option>
                                                <option value="7">7 dias</option>
                                                <option value="15">15 dias</option>
                                                <option value="30">30 dias</option>
                                            </select>
                                        </div>
                                        <div class="col-12 col-md-8">
                                            <label class="form-label" for="osDatesModalPrevisao">Previsao</label>
                                            <input type="date" class="form-control" id="osDatesModalPrevisao" name="data_previsao">
                                        </div>
                                    </div>

                                    <div class="os-status-modal-section">
                                        <label class="form-label" for="osDatesModalEntrega">Entrega</label>
                                        <input type="text" class="form-control" id="osDatesModalEntrega" value="-" readonly disabled>
                                        <div class="form-text">A data de entrega é controlada automaticamente pela mudança de status correta da OS.</div>
                                    </div>

                                    <div class="os-status-modal-section">
                                        <label class="form-label" for="osDatesModalMotivo">Motivo da alteracao <span class="text-danger">*</span></label>
                                        <textarea
                                            class="form-control"
                                            id="osDatesModalMotivo"
                                            name="motivo_alteracao"
                                            rows="3"
                                            placeholder="Explique por que o prazo desta OS esta sendo alterado."
                                        ></textarea>
                                        <div class="form-text">Esse motivo e registrado no historico operacional da OS.</div>
                                    </div>

                                    <div class="os-status-modal-section d-none" id="osDatesModalAdminApprovalWrap">
                                        <div class="os-status-modal-section-title">Autorizacao administrativa</div>
                                        <div class="small text-muted mb-3">Informe um administrador para autorizar esta alteracao de prazo.</div>
                                        <div class="row g-3">
                                            <div class="col-12">
                                                <label class="form-label" for="osDatesModalAdminUser">Administrador</label>
                                                <input
                                                    type="text"
                                                    class="form-control"
                                                    id="osDatesModalAdminUser"
                                                    name="admin_usuario"
                                                    autocomplete="username"
                                                    placeholder="Nome ou e-mail do administrador"
                                                >
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label" for="osDatesModalAdminPass">Senha do administrador</label>
                                                <input
                                                    type="password"
                                                    class="form-control"
                                                    id="osDatesModalAdminPass"
                                                    name="admin_senha"
                                                    autocomplete="current-password"
                                                    placeholder="Digite a senha do administrador"
                                                >
                                            </div>
                                        </div>
                                    </div>

                                    <div class="os-dates-status-note">
                                        <i class="bi bi-info-circle"></i>
                                        <span>Use este modal para ajustar o prazo previsto. Para concluir, devolver ou cancelar com entrega registrada, altere o status da OS.</span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-xl-5">
                                <div class="os-status-modal-panel">
                                    <div class="os-status-modal-section-title">Resumo atual</div>
                                    <div class="os-inline-meta-list">
                                        <div class="os-inline-meta-item">
                                            <span class="os-inline-meta-label">Entrada atual</span>
                                            <strong id="osDatesModalEntradaAtual">-</strong>
                                        </div>
                                        <div class="os-inline-meta-item">
                                            <span class="os-inline-meta-label">Prazo atual</span>
                                            <strong id="osDatesModalPrevisaoAtual">-</strong>
                                        </div>
                                        <div class="os-inline-meta-item">
                                            <span class="os-inline-meta-label">Entrega atual</span>
                                            <strong id="osDatesModalEntregaAtual">-</strong>
                                        </div>
                                        <div class="os-inline-meta-item">
                                            <span class="os-inline-meta-label">Dias entre entrada e previsão</span>
                                            <strong id="osDatesModalPrazoDias">-</strong>
                                        </div>
                                    </div>
                                    <div class="form-text">A listagem será atualizada automaticamente após salvar.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-glow" id="osDatesModalSubmit">
                            <i class="bi bi-calendar-check me-1"></i>Salvar prazos
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="osBudgetModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable modal-fullscreen-md-down">
            <div class="modal-content">
                <form id="osBudgetModalForm" class="os-status-modal-form" novalidate>
                    <div class="modal-header">
            <h5 class="modal-title">Orçamento da OS</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="os-status-modal-summary mb-4">
                            <div class="small text-muted">OS selecionada</div>
                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                                <div>
                                    <div class="fw-semibold fs-4" id="osBudgetModalNumero">-</div>
                <div class="small text-muted mt-1">Gere uma nova versão do PDF de orçamento e envie para o cliente sem sair da listagem.</div>
                                </div>
                                <div class="os-status-modal-badges" id="osBudgetModalBadges"></div>
                            </div>
                            <div class="row g-3 mt-1">
                                <div class="col-12 col-lg-6">
                                    <div class="os-status-context-card">
                                        <div class="os-status-context-title">Cliente</div>
                                        <div class="os-status-context-name" id="osBudgetModalClientName">-</div>
                                        <div class="os-status-context-meta" id="osBudgetModalClientPhone">Telefone: -</div>
                                        <div class="os-status-context-meta" id="osBudgetModalClientEmail">Email: -</div>
                                    </div>
                                </div>
                                <div class="col-12 col-lg-6">
                                    <div class="os-status-context-card">
                                        <div class="os-status-context-title">Equipamento</div>
                                        <div class="os-status-context-name" id="osBudgetModalEquipmentName">-</div>
                                        <div class="os-status-context-meta" id="osBudgetModalEquipmentMeta">Tipo: -</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-4">
                            <div class="col-12 col-xl-7">
                                <div class="os-status-modal-panel">
                                    <div class="os-status-modal-section">
                                        <div class="os-status-modal-section-title">Resumo financeiro</div>
                                        <div class="os-budget-metrics">
                                            <div class="os-budget-metric-card">
                                                <span class="os-budget-metric-label">Mão de obra</span>
                                                <strong id="osBudgetModalMaoObra">R$ 0,00</strong>
                                            </div>
                                            <div class="os-budget-metric-card">
                                                <span class="os-budget-metric-label">Peças</span>
                                                <strong id="osBudgetModalPeças">R$ 0,00</strong>
                                            </div>
                                            <div class="os-budget-metric-card">
                                                <span class="os-budget-metric-label">Subtotal</span>
                                                <strong id="osBudgetModalSubtotal">R$ 0,00</strong>
                                            </div>
                                            <div class="os-budget-metric-card">
                                                <span class="os-budget-metric-label">Valor final</span>
                                                <strong id="osBudgetModalValorFinal">R$ 0,00</strong>
                                            </div>
                                        </div>
                                    <div class="small text-muted">Ao salvar, o sistema gera uma nova versão do PDF de orçamento desta OS.</div>
                                    </div>

                                    <div class="os-status-modal-section">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" role="switch" id="osBudgetModalNotify" name="enviar_cliente" value="1">
                                            <label class="form-check-label" for="osBudgetModalNotify">
                                        Enviar o orçamento ao cliente após gerar o PDF
                                            </label>
                                        </div>
                                        <div class="form-text" id="osBudgetModalNotifyHelp">O envio utiliza o telefone cadastrado do cliente.</div>
                                    </div>

                                    <div class="os-status-modal-section">
                                        <label class="form-label" for="osBudgetModalPhone">Telefone destino</label>
                                        <input type="text" class="form-control" id="osBudgetModalPhone" name="telefone" placeholder="Telefone do cliente">
                                    </div>

                                    <div class="os-status-modal-section">
                                        <label class="form-label" for="osBudgetModalMessage">Mensagem opcional</label>
                                <textarea id="osBudgetModalMessage" name="mensagem_manual" class="form-control" rows="4" placeholder="Se deixar em branco, o sistema usa o template padrão do orçamento."></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-xl-5">
                                <div class="os-status-modal-panel">
                        <div class="os-status-modal-section-title">PDFs de orçamento já gerados</div>
                                    <div id="osBudgetModalDocsList" class="os-budget-docs-list">
                                <p class="text-muted small mb-0">Nenhum orçamento PDF registrado para esta OS.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-glow" id="osBudgetModalSubmit">
                            <i class="bi bi-file-earmark-pdf me-1"></i>Gerar PDF
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="osStatusModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <form id="osStatusModalForm" class="os-status-modal-form" novalidate>
                    <input type="hidden" name="controla_comunicacao_cliente" value="1">
                    <div class="modal-header">
                        <h5 class="modal-title os-status-modal-title">
                            Alterar status da OS <span id="osStatusModalNumero" class="os-status-modal-title-number">-</span>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="os-status-modal-summary mb-4">
                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                                <div>
                                    <div class="small text-muted">Contexto da ordem selecionada</div>
                                    <div class="small text-muted mt-1">Confira o cliente, o equipamento e os badges atuais antes de mover esta OS.</div>
                                </div>
                                <div class="os-status-modal-badges" id="osStatusModalCurrentBadges"></div>
                            </div>
                            <div class="row g-3 mt-1">
                                <div class="col-12 col-lg-6">
                                    <div class="os-status-context-card">
                                        <div class="os-status-context-title">Cliente</div>
                                        <div class="os-status-context-name" id="osStatusModalClientName">-</div>
                                        <div class="os-status-context-meta" id="osStatusModalClientPhone">Telefone: -</div>
                                        <div class="os-status-context-meta" id="osStatusModalClientEmail">Email: -</div>
                                    </div>
                                </div>
                                <div class="col-12 col-lg-6">
                                    <div class="os-status-context-card">
                                        <div class="os-status-context-title">Equipamento</div>
                                        <div class="os-status-context-name" id="osStatusModalEquipmentName">-</div>
                                        <div class="os-status-context-meta" id="osStatusModalEquipmentMeta">Tipo: -</div>
                                        <div class="os-status-context-meta" id="osStatusModalEquipmentSerial">Nº de série: -</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-4">
                            <div class="col-12 col-xl-7">
                                <div class="os-status-modal-panel os-status-modal-tabs-shell">
                                    <ul class="nav nav-pills os-status-modal-tabs" id="osStatusModalTabs" role="tablist">
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link active" id="osStatusTabQuickBtn" data-bs-toggle="pill" data-bs-target="#osStatusTabQuick" type="button" role="tab" aria-controls="osStatusTabQuick" aria-selected="true">
                                                Ações rápidas
                                            </button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link" id="osStatusTabSolutionBtn" data-bs-toggle="pill" data-bs-target="#osStatusTabSolution" type="button" role="tab" aria-controls="osStatusTabSolution" aria-selected="false">
                                                Solução e diagnóstico
                                            </button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link" id="osStatusTabBudgetBtn" data-bs-toggle="pill" data-bs-target="#osStatusTabBudget" type="button" role="tab" aria-controls="osStatusTabBudget" aria-selected="false">
                                                Gerenciamento do Orçamento
                                            </button>
                                        </li>
                                    </ul>

                                    <div class="tab-content os-status-modal-tab-content" id="osStatusModalTabContent">
                                        <div class="tab-pane fade show active" id="osStatusTabQuick" role="tabpanel" aria-labelledby="osStatusTabQuickBtn" tabindex="0">
                                            <div class="os-status-modal-section">
                                                <div class="os-status-modal-section-title">Ações rápidas</div>
                                                <div class="os-status-modal-quick-actions">
                                                    <button type="button" class="btn btn-glow" id="osStatusModalQuickNext" disabled>
                                                        <i class="bi bi-arrow-right-circle me-1"></i>Próxima etapa
                                                    </button>
                                                    <button type="button" class="btn btn-outline-danger" id="osStatusModalQuickCancel" disabled>
                                                        <i class="bi bi-x-circle me-1"></i>Cancelar
                                                    </button>
                                                </div>
                                                <div class="os-status-modal-flow-hints">
                                                    <div class="small text-muted" id="osStatusModalCurrentStatusHint">Status atual da OS: aguardando contexto.</div>
                                                    <div class="small text-muted" id="osStatusModalPrimaryHint">Fluxo normal sugerido: aguardando contexto.</div>
                                                    <div class="small text-muted" id="osStatusModalTargetHint">Selecione um fluxo para continuar.</div>
                                                </div>
                                            </div>

                                            <div class="os-status-modal-section">
                                                <label class="form-label" for="osStatusModalSelect">Status de destino</label>
                                                <select id="osStatusModalSelect" name="status" class="form-select" required>
                                                    <option value="">Selecione um status</option>
                                                </select>
                                                <div class="form-text">A lista respeita o fluxo de trabalho configurado para avançar, retornar etapas ou cancelar o atendimento.</div>
                                            </div>

                                            <div class="os-status-modal-section">
                                                <label class="form-label" for="osStatusModalObservacao">Observações</label>
                                                <textarea
                                                    id="osStatusModalObservacao"
                                                    name="observacao_status"
                                                    class="form-control"
                                                    rows="4"
                                                    placeholder="Registre contexto da mudança, combinados com o cliente ou justificativa do cancelamento."
                                                ></textarea>
                                            </div>

                                            <div class="os-status-modal-section">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" role="switch" id="osStatusModalNotify" name="comunicar_cliente" value="1">
                                                    <label class="form-check-label" for="osStatusModalNotify">
                                                        Notificar o cliente sobre esta mudança
                                                    </label>
                                                </div>
                                                <div class="form-text" id="osStatusModalNotifyHelp">O cliente será comunicado apenas se você mantiver esta opção ativa.</div>
                                            </div>
                                        </div>

                                        <div class="tab-pane fade" id="osStatusTabSolution" role="tabpanel" aria-labelledby="osStatusTabSolutionBtn" tabindex="0">
                                            <div class="os-status-modal-section">
                                                <div class="os-status-modal-section-title">Solução e diagnóstico</div>
                                                <p class="small text-muted mb-0">Registre os procedimentos executados e consolide a solução aplicada sem sair da mudança de status.</p>
                                            </div>

                                            <div class="os-status-modal-solution-card">
                                                <label class="form-label fw-semibold" for="osStatusModalProcedimentoTextoInput">Procedimentos executados</label>
                                                <div class="os-status-modal-procedure-toolbar">
                                                    <input
                                                        type="text"
                                                        class="form-control"
                                                        id="osStatusModalProcedimentoTextoInput"
                                                        placeholder="Ex.: feito testes no processador">
                                                    <button type="button" class="btn btn-outline-primary" id="osStatusModalInserirProcedimento">
                                                        + Inserir novo procedimento
                                                    </button>
                                                </div>
                                                <textarea name="procedimentos_executados" id="osStatusModalProcedimentosInput" class="d-none"></textarea>
                                                <div id="osStatusModalProcedimentosLista" class="os-status-modal-procedure-list"></div>
                                                <small class="text-muted d-block mt-2">Cada inserção registra automaticamente data/hora e técnico selecionado.</small>
                                            </div>

                                            <div class="os-status-modal-solution-grid">
                                                <div class="os-status-modal-field-card">
                                                    <label class="form-label fw-semibold" for="osStatusModalSolucaoInput">Solução aplicada</label>
                                                    <textarea name="solucao_aplicada" id="osStatusModalSolucaoInput" class="form-control" rows="5"></textarea>
                                                </div>
                                                <div class="os-status-modal-field-card">
                                                    <label class="form-label fw-semibold" for="osStatusModalDiagnosticoInput">Diagnostico</label>
                                                    <textarea name="diagnostico_tecnico" id="osStatusModalDiagnosticoInput" class="form-control" rows="5"></textarea>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="tab-pane fade" id="osStatusTabBudget" role="tabpanel" aria-labelledby="osStatusTabBudgetBtn" tabindex="0">
                                            <div id="osStatusModalBudgetPanel" class="os-status-modal-budget-host">
                                                <div class="card os-tab-card os-status-modal-budget-card">
                                                    <div class="card-body p-4 text-muted small">Carregando gerenciamento do orçamento...</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-xl-5">
                                <div class="os-status-modal-panel os-status-modal-workflow">
                                    <div class="os-status-modal-section-title">Histórico e progresso</div>
                                    <p class="small text-muted mb-0">Etapas percorridas, etapa atual e provaveis proximos movimentos.</p>

                                    <div id="osStatusModalTimeline" class="os-status-modal-timeline-wrap">
                                        <p class="text-muted small mb-0">Carregando fluxo visual...</p>
                                    </div>

                                    <div class="os-status-modal-history-wrap" id="osStatusModalHistoryWrap">
                                        <div class="os-status-modal-divider"></div>
                                        <div class="os-status-modal-section-title">Últimas movimentações</div>
                                        <div id="osStatusModalHistoryList" class="os-status-modal-history-list">
                                            <p class="text-muted small mb-0">Sem histórico recente.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-glow" id="osStatusModalSubmit">
                            <i class="bi bi-check2-circle me-1"></i>Salvar status
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
window.osListConfig = {
    datatableUrl: '<?= base_url('os/datatable') ?>',
    photosUrlBase: '<?= base_url('os/fotos') ?>',
    statusMetaUrlBase: '<?= base_url('os/status-meta') ?>',
    datesMetaUrlBase: '<?= base_url('os/prazos-meta') ?>',
    datesUpdateUrlBase: '<?= base_url('os/prazos-ajax') ?>',
    budgetMetaUrlBase: '<?= base_url('os/orcamento-meta') ?>',
    budgetActionUrlBase: '<?= base_url('os/orcamento-ajax') ?>',
    statusUpdateUrlBase: '<?= base_url('os/status-ajax') ?>',
    languageUrl: '<?= base_url('assets/json/pt-BR.json') ?>',
    csrfTokenKey: '<?= csrf_token() ?>',
    csrfTokenValue: '<?= csrf_hash() ?>',
    initialFilters: <?= json_encode($listFilters, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
    labels: <?= json_encode($labelsMap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
};
</script>
<script src="<?= base_url('assets/js/os-list-filters.js') ?>?v=<?= urlencode((string) @filemtime(FCPATH . 'assets/js/os-list-filters.js')) ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof bootstrap === 'undefined') {
        return;
    }

    const confirmModalClose = async (options) => {
        const normalized = options && typeof options === 'object' ? { ...options } : {};

        if (window.DSFeedback && typeof window.DSFeedback.confirm === 'function') {
            return window.DSFeedback.confirm(normalized);
        }

        if (window.Swal && typeof window.Swal.fire === 'function') {
            const result = await window.Swal.fire({
                icon: normalized.icon || 'warning',
                title: normalized.title || 'Fechar cadastro em andamento?',
                text: normalized.text || 'Existe um registro de ordem de serviço em andamento. Deseja fechar mesmo assim?',
                showCancelButton: true,
                confirmButtonText: normalized.confirmButtonText || 'Fechar mesmo assim',
                cancelButtonText: normalized.cancelButtonText || 'Continuar cadastro',
                reverseButtons: typeof normalized.reverseButtons === 'undefined' ? true : normalized.reverseButtons,
                focusCancel: typeof normalized.focusCancel === 'undefined' ? true : normalized.focusCancel,
            });

            return Boolean(result && result.isConfirmed);
        }

        return window.confirm(
            normalized.text || 'Existe um registro de ordem de serviço em andamento. Deseja fechar mesmo assim?'
        );
    };

    const bindIframeModal = ({ modalId, frameId, loadingId, titleId, triggerSelector, defaultTitle, closeButtonSelector = null, closeConfirmOptions = null }) => {
        const modalElement = document.getElementById(modalId);
        if (!modalElement) {
            return;
        }

        const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
        const frame = document.getElementById(frameId);
        const loading = document.getElementById(loadingId);
        const title = document.getElementById(titleId);
        const closeButton = closeButtonSelector ? modalElement.querySelector(closeButtonSelector) : null;
        let loadTimeout = null;

        const setLoading = (isLoading) => {
            loading?.classList.toggle('d-none', !isLoading);
        };

        const clearLoadTimeout = () => {
            if (!loadTimeout) {
                return;
            }
            window.clearTimeout(loadTimeout);
            loadTimeout = null;
        };

        const bringModalToFront = () => {
            const stackedNodes = Array.from(document.querySelectorAll('.modal.show, .modal-backdrop'));
            const stackedZIndexes = stackedNodes
                .map((element) => {
                    const raw = window.getComputedStyle(element)?.zIndex || element.style?.zIndex || '';
                    const parsed = Number.parseInt(String(raw), 10);
                    return Number.isFinite(parsed) ? parsed : 0;
                })
                .filter((value) => value > 0);

            const topZIndex = stackedZIndexes.length > 0 ? Math.max(...stackedZIndexes) : 1055;
            const modalZIndex = topZIndex >= 1055 ? topZIndex + 20 : 1075;
            modalElement.style.zIndex = String(modalZIndex);

            const backdrops = Array.from(document.querySelectorAll('.modal-backdrop'));
            const activeBackdrop = backdrops[backdrops.length - 1];
            if (activeBackdrop) {
                activeBackdrop.style.zIndex = String(modalZIndex - 5);
            }
        };

        const openModal = (url, modalTitle) => {
            if (!url || !frame) {
                return;
            }

            clearLoadTimeout();
            setLoading(true);
            if (title) {
                title.textContent = modalTitle || defaultTitle;
            }
            frame.src = 'about:blank';
            modal.show();
            frame.src = url;

            loadTimeout = window.setTimeout(() => {
                setLoading(false);
            }, 12000);
        };

        document.addEventListener('click', (event) => {
            const trigger = event.target.closest(triggerSelector);
            if (!trigger) {
                return;
            }

            event.preventDefault();
            openModal(
                trigger.getAttribute('data-os-frame-modal-url') || trigger.getAttribute('data-os-modal-url'),
                trigger.getAttribute('data-os-frame-modal-title') || trigger.getAttribute('data-os-modal-title')
            );
        });

        frame?.addEventListener('load', () => {
            clearLoadTimeout();
            setLoading(false);
        });

        modalElement.addEventListener('shown.bs.modal', () => {
            bringModalToFront();
        });

        if (closeButton) {
            closeButton.addEventListener('click', async (event) => {
                event.preventDefault();
                event.stopPropagation();

                const confirmed = closeConfirmOptions
                    ? await confirmModalClose(closeConfirmOptions)
                    : true;

                if (!confirmed) {
                    return;
                }

                modal.hide();
            });
        }

        modalElement.addEventListener('hidden.bs.modal', () => {
            clearLoadTimeout();
            setLoading(false);
            modalElement.style.removeProperty('z-index');
            if (frame) {
                frame.src = 'about:blank';
            }
        });
    };

    bindIframeModal({
        modalId: 'osCreateModal',
        frameId: 'osCreateModalFrame',
        loadingId: 'osCreateModalLoading',
        titleId: 'osCreateModalTitle',
        triggerSelector: '[data-os-modal-role="create"][data-os-modal-url]',
        defaultTitle: 'Nova Ordem de Serviço',
        closeButtonSelector: '#osCreateModalCloseBtn',
        closeConfirmOptions: {
            icon: 'warning',
            title: 'Fechar nova OS?',
            text: 'Existe um registro de ordem de serviço em andamento. Se fechar agora, o preenchimento não salvo será perdido.',
            confirmButtonText: 'Fechar mesmo assim',
            cancelButtonText: 'Continuar preenchendo',
        },
    });

    bindIframeModal({
        modalId: 'osDetailsModal',
        frameId: 'osDetailsModalFrame',
        loadingId: 'osDetailsModalLoading',
        titleId: 'osDetailsModalTitle',
        triggerSelector: '[data-os-frame-modal-url]',
        defaultTitle: 'Detalhes',
    });
});
</script>
<?= $this->endSection() ?>
