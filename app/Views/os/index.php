<?php
$listFilters = $listFilters ?? [];
$statusGrouped = $statusGrouped ?? [];
$statusFlat = $statusFlat ?? [];
$macrofases = $macrofases ?? [];
$tecnicos = $tecnicos ?? [];
$tiposServico = $tiposServico ?? [];
$situacaoOptions = $situacaoOptions ?? [];

$statusSelected = is_array($listFilters['status'] ?? null) ? $listFilters['status'] : [];
$estadoFluxoOptions = [
    'em_atendimento' => 'Em atendimento',
    'em_execucao' => 'Em execucao',
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
    $tecnicoLabels[$tecnicoId] = (string) ($tecnico['nome'] ?? ('Tecnico #' . $tecnicoId));
}

$labelsMap = [
    'status' => $statusLabels,
    'macrofases' => $macrofases,
    'estado_fluxo' => $estadoFluxoOptions,
    'tecnicos' => $tecnicoLabels,
    'situacao' => $situacaoOptions,
];
?>

<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="os-list-page" data-sidebar-auto-collapse="hover">
    <div class="page-header">
        <div class="d-flex align-items-center gap-3 os-page-heading">
            <h2><i class="bi bi-clipboard-check me-2"></i>Ordens de Servico</h2>
            <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('ordens-de-servico')" title="Ajuda sobre este modulo">
                <i class="bi bi-question-circle me-1"></i> Ajuda
            </button>
        </div>
        <?php if (can('os', 'criar')): ?>
            <button
                type="button"
                class="btn btn-glow os-page-create-btn"
                data-os-modal-role="create"
                data-os-modal-url="<?= base_url('os/nova?embed=1') ?>"
                data-os-modal-title="Nova Ordem de Servico"
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
                <div class="os-filters-inline">
                    <div class="os-filter-field">
                        <label class="form-label" for="osFilterQDesktop">Busca global</label>
                        <div class="os-input-icon">
                            <i class="bi bi-search"></i>
                            <input
                                type="text"
                                class="form-control"
                                id="osFilterQDesktop"
                                data-filter-field="q"
                                placeholder="Cliente, equipamento, numero da OS..."
                                value="<?= esc((string) ($listFilters['q'] ?? '')) ?>"
                                autocomplete="off"
                            >
                        </div>
                    </div>

                    <div class="os-filter-field">
                        <label class="form-label" for="osFilterStatusDesktop">Status detalhado</label>
                        <select
                            id="osFilterStatusDesktop"
                            data-filter-field="status"
                            class="form-select js-os-select2"
                            multiple
                            data-placeholder="Selecione status"
                        >
                            <?php foreach ($statusGrouped as $macro => $items): ?>
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
                            <i class="bi bi-sliders me-1"></i>Filtros avancados
                        </button>
                    </div>
                </div>

                <div class="collapse os-filters-advanced" id="osAdvancedFiltersCollapse">
                    <div class="row g-3">
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
                            <label class="form-label" for="osFilterSituacaoDesktop">Situacao operacional</label>
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
                            <label class="form-label" for="osFilterTecnicoDesktop">Tecnico responsavel</label>
                            <select id="osFilterTecnicoDesktop" data-filter-field="tecnico_id" class="form-select js-os-select2" data-placeholder="Todos">
                                <option value="">Todos</option>
                                <?php foreach ($tecnicos as $tecnico): ?>
                                    <?php $tecnicoId = (string) ($tecnico['id'] ?? ''); ?>
                                    <?php if ($tecnicoId === '') continue; ?>
                                    <option value="<?= esc($tecnicoId) ?>" <?= ((string) ($listFilters['tecnico_id'] ?? '') === $tecnicoId) ? 'selected' : '' ?>>
                                        <?= esc((string) ($tecnico['nome'] ?? ('Tecnico #' . $tecnicoId))) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 col-xxl-3 col-xl-4 col-md-6">
                            <label class="form-label" for="osFilterTipoServicoDesktop">Tipo de servico</label>
                            <select id="osFilterTipoServicoDesktop" data-filter-field="tipo_servico" class="form-select js-os-select2" data-placeholder="Todos os servicos">
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
                            <label class="form-label" for="osFilterDataFimDesktop">Abertura ate</label>
                            <input type="date" id="osFilterDataFimDesktop" data-filter-field="data_fim" class="form-control" value="<?= esc((string) ($listFilters['data_fim'] ?? '')) ?>">
                        </div>

                        <div class="col-6 col-xxl-2 col-xl-3 col-md-3">
                            <label class="form-label" for="osFilterValorMinDesktop">Valor minimo</label>
                            <input type="text" id="osFilterValorMinDesktop" data-filter-field="valor_min" class="form-control mask-money" placeholder="0,00" value="<?= esc((string) ($listFilters['valor_min'] ?? '')) ?>">
                        </div>

                        <div class="col-6 col-xxl-2 col-xl-3 col-md-3">
                            <label class="form-label" for="osFilterValorMaxDesktop">Valor maximo</label>
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
                                placeholder="Cliente, equipamento, numero da OS..."
                                value="<?= esc((string) ($listFilters['q'] ?? '')) ?>"
                                autocomplete="off"
                            >
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="osFilterStatusMobile">Status detalhado</label>
                        <select
                            id="osFilterStatusMobile"
                            data-filter-field="status"
                            class="form-select js-os-select2"
                            multiple
                            data-placeholder="Selecione status"
                        >
                            <?php foreach ($statusGrouped as $macro => $items): ?>
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
                        <label class="form-label" for="osFilterSituacaoMobile">Situacao operacional</label>
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
                        <label class="form-label" for="osFilterTecnicoMobile">Tecnico responsavel</label>
                        <select id="osFilterTecnicoMobile" data-filter-field="tecnico_id" class="form-select js-os-select2" data-placeholder="Todos">
                            <option value="">Todos</option>
                            <?php foreach ($tecnicos as $tecnico): ?>
                                <?php $tecnicoId = (string) ($tecnico['id'] ?? ''); ?>
                                <?php if ($tecnicoId === '') continue; ?>
                                <option value="<?= esc($tecnicoId) ?>" <?= ((string) ($listFilters['tecnico_id'] ?? '') === $tecnicoId) ? 'selected' : '' ?>>
                                    <?= esc((string) ($tecnico['nome'] ?? ('Tecnico #' . $tecnicoId))) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="osFilterTipoServicoMobile">Tipo de servico</label>
                        <select id="osFilterTipoServicoMobile" data-filter-field="tipo_servico" class="form-select js-os-select2" data-placeholder="Todos os servicos">
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
                        <label class="form-label" for="osFilterDataFimMobile">Abertura ate</label>
                        <input type="date" id="osFilterDataFimMobile" data-filter-field="data_fim" class="form-control" value="<?= esc((string) ($listFilters['data_fim'] ?? '')) ?>">
                    </div>

                    <div class="col-6">
                        <label class="form-label" for="osFilterValorMinMobile">Valor minimo</label>
                        <input type="text" id="osFilterValorMinMobile" data-filter-field="valor_min" class="form-control mask-money" placeholder="0,00" value="<?= esc((string) ($listFilters['valor_min'] ?? '')) ?>">
                    </div>

                    <div class="col-6">
                        <label class="form-label" for="osFilterValorMaxMobile">Valor maximo</label>
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
                <h5 class="os-table-title"><i class="bi bi-list-check me-2"></i>Lista de Ordens de Servico</h5>
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
                            <th>Acoes</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade dashboard-os-modal" id="osCreateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl modal-fullscreen-md-down">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="osCreateModalTitle">Nova Ordem de Servico</h5>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body p-0 position-relative">
                    <div class="dashboard-os-modal-loading" id="osCreateModalLoading">
                        <div class="spinner-border text-primary" role="status" aria-hidden="true"></div>
                        <span>Carregando...</span>
                    </div>
                    <iframe id="osCreateModalFrame" title="Nova Ordem de Servico" class="dashboard-os-modal-frame" src="about:blank"></iframe>
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
                                    <span>Nenhuma foto foi registrada na abertura desta ordem de servico.</span>
                                </div>
                                <div class="os-photo-viewer-grid" id="osPhotosEntryGrid"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="osStatusModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="osStatusModalForm" novalidate>
                    <div class="modal-header">
                        <h5 class="modal-title">Alterar status da OS</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <div class="small text-muted">OS selecionada</div>
                            <div class="fw-semibold" id="osStatusModalNumero">-</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="osStatusModalSelect">Novo status</label>
                            <select id="osStatusModalSelect" name="status" class="form-select" required>
                                <option value="">Selecione um status</option>
                            </select>
                            <div class="form-text">A lista respeita o fluxo de trabalho configurado para avancar ou retornar etapas.</div>
                        </div>
                        <div>
                            <label class="form-label" for="osStatusModalObservacao">Observacao</label>
                            <textarea id="osStatusModalObservacao" name="observacao_status" class="form-control" rows="3" placeholder="Opcional"></textarea>
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
    statusUpdateUrlBase: '<?= base_url('os/status-ajax') ?>',
    languageUrl: '<?= base_url('assets/json/pt-BR.json') ?>',
    csrfTokenKey: '<?= csrf_token() ?>',
    csrfTokenValue: '<?= csrf_hash() ?>',
    initialFilters: <?= json_encode($listFilters, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
    labels: <?= json_encode($labelsMap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
};
</script>
<script src="<?= base_url('assets/js/os-list-filters.js') ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const createModalElement = document.getElementById('osCreateModal');
    if (!createModalElement || typeof bootstrap === 'undefined') {
        return;
    }

    const createModal = bootstrap.Modal.getOrCreateInstance(createModalElement);
    const createModalFrame = document.getElementById('osCreateModalFrame');
    const createModalLoading = document.getElementById('osCreateModalLoading');
    const createModalTitle = document.getElementById('osCreateModalTitle');
    let createModalLoadTimeout = null;

    const setCreateModalLoading = (isLoading) => {
        createModalLoading?.classList.toggle('d-none', !isLoading);
    };

    const clearCreateModalLoadTimeout = () => {
        if (!createModalLoadTimeout) {
            return;
        }
        window.clearTimeout(createModalLoadTimeout);
        createModalLoadTimeout = null;
    };

    const openCreateModal = (url, title) => {
        if (!url || !createModalFrame) {
            return;
        }

        clearCreateModalLoadTimeout();
        setCreateModalLoading(true);
        if (createModalTitle) {
            createModalTitle.textContent = title || 'Nova Ordem de Servico';
        }
        createModalFrame.src = 'about:blank';
        createModal.show();
        createModalFrame.src = url;

        createModalLoadTimeout = window.setTimeout(() => {
            setCreateModalLoading(false);
        }, 12000);
    };

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-os-modal-role="create"][data-os-modal-url]');
        if (!trigger) {
            return;
        }

        event.preventDefault();
        openCreateModal(
            trigger.getAttribute('data-os-modal-url'),
            trigger.getAttribute('data-os-modal-title')
        );
    });

    createModalFrame?.addEventListener('load', () => {
        clearCreateModalLoadTimeout();
        setCreateModalLoading(false);
    });

    createModalElement.addEventListener('hidden.bs.modal', () => {
        clearCreateModalLoadTimeout();
        setCreateModalLoading(false);
        if (createModalFrame) {
            createModalFrame.src = 'about:blank';
        }
    });
});
</script>
<?= $this->endSection() ?>
