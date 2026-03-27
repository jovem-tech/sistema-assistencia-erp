(function () {
    'use strict';

    const STORAGE_KEY = 'os_advanced_filters_v1';
    const FILTER_FIELDS = [
        'q',
        'status',
        'macrofase',
        'estado_fluxo',
        'data_inicio',
        'data_fim',
        'tecnico_id',
        'tipo_servico',
        'valor_min',
        'valor_max',
        'situacao',
    ];

    const ADVANCED_FIELDS = [
        'macrofase',
        'estado_fluxo',
        'data_inicio',
        'data_fim',
        'tecnico_id',
        'tipo_servico',
        'valor_min',
        'valor_max',
        'situacao',
    ];

    const OS_TABLE_DATA_COLUMNS = [
        { dtIndex: 1, dataIndex: 0, key: 'foto', label: 'Foto' },
        { dtIndex: 2, dataIndex: 1, key: 'numero_os', label: 'N OS' },
        { dtIndex: 3, dataIndex: 2, key: 'cliente', label: 'Cliente' },
        { dtIndex: 4, dataIndex: 3, key: 'equipamento', label: 'Equipamento' },
        { dtIndex: 5, dataIndex: 4, key: 'relato', label: 'Relato' },
        { dtIndex: 6, dataIndex: 5, key: 'datas', label: 'Datas' },
        { dtIndex: 7, dataIndex: 6, key: 'status', label: 'Status' },
        { dtIndex: 8, dataIndex: 7, key: 'valor_total', label: 'Valor Total' },
        { dtIndex: 9, dataIndex: 8, key: 'acoes', label: 'Acoes' },
    ];
    const OS_OVERFLOW_HIDE_PRIORITY = [9, 5, 7, 6, 4];

    let syncInProgress = false;

    function normalizeString(value) {
        return String(value ?? '').trim();
    }

    function normalizeStatusList(value) {
        if (Array.isArray(value)) {
            return value
                .map((item) => normalizeString(item))
                .filter((item) => item !== '' && item !== 'todos');
        }

        const raw = normalizeString(value);
        if (raw === '') {
            return [];
        }

        return raw
            .split(',')
            .map((item) => normalizeString(item))
            .filter((item) => item !== '' && item !== 'todos');
    }

    function normalizeState(rawState) {
        const source = rawState || {};
        return {
            q: normalizeString(source.q),
            status: normalizeStatusList(source.status ?? source.status_list ?? ''),
            macrofase: normalizeString(source.macrofase),
            estado_fluxo: normalizeString(source.estado_fluxo),
            data_inicio: normalizeString(source.data_inicio),
            data_fim: normalizeString(source.data_fim),
            tecnico_id: normalizeString(source.tecnico_id),
            tipo_servico: normalizeString(source.tipo_servico),
            valor_min: normalizeString(source.valor_min),
            valor_max: normalizeString(source.valor_max),
            situacao: normalizeString(source.situacao),
        };
    }

    function hasAnyFilter(state) {
        return FILTER_FIELDS.some((key) => {
            if (key === 'status') {
                return Array.isArray(state.status) && state.status.length > 0;
            }
            return normalizeString(state[key]) !== '';
        });
    }

    function hasAdvancedFilter(state) {
        return ADVANCED_FIELDS.some((key) => normalizeString(state[key]) !== '');
    }

    function debounce(fn, wait) {
        let timeout = null;
        return function debounced(...args) {
            if (timeout) {
                window.clearTimeout(timeout);
            }
            timeout = window.setTimeout(() => {
                fn.apply(this, args);
            }, wait);
        };
    }

    function readStorageState() {
        try {
            const raw = window.localStorage.getItem(STORAGE_KEY);
            if (!raw) {
                return {};
            }
            const parsed = JSON.parse(raw);
            return normalizeState(parsed);
        } catch (error) {
            console.warn('[OSFilters] Falha ao ler filtros do localStorage.', error);
            return {};
        }
    }

    function saveStorageState(state) {
        try {
            window.localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
        } catch (error) {
            console.warn('[OSFilters] Falha ao salvar filtros no localStorage.', error);
        }
    }

    function readUrlState() {
        const params = new URLSearchParams(window.location.search || '');
        const state = {};

        FILTER_FIELDS.forEach((key) => {
            const value = params.get(key);
            if (value !== null) {
                state[key] = value;
            }
        });

        if (params.has('status_list') && !params.has('status')) {
            state.status = params.get('status_list');
        }

        return normalizeState(state);
    }

    function writeUrlState(state) {
        const params = new URLSearchParams();

        FILTER_FIELDS.forEach((key) => {
            if (key === 'status') {
                if (Array.isArray(state.status) && state.status.length > 0) {
                    params.set('status', state.status.join(','));
                }
                return;
            }

            const value = normalizeString(state[key]);
            if (value !== '') {
                params.set(key, value);
            }
        });

        const nextQuery = params.toString();
        const nextUrl = nextQuery ? `${window.location.pathname}?${nextQuery}` : window.location.pathname;
        window.history.replaceState({}, '', nextUrl);
    }

    function getFieldControl(form, key) {
        if (!form) {
            return null;
        }
        return form.querySelector(`[data-filter-field="${key}"]`);
    }

    function setFieldValue(form, key, value) {
        const control = getFieldControl(form, key);
        if (!control) {
            return;
        }

        if (key === 'status') {
            const list = Array.isArray(value) ? value : [];
            Array.from(control.options || []).forEach((option) => {
                option.selected = list.includes(option.value);
            });
            if (window.jQuery && window.jQuery(control).hasClass('select2-hidden-accessible')) {
                window.jQuery(control).val(list).trigger('change.select2');
            }
            return;
        }

        control.value = value ?? '';
        if (window.jQuery && window.jQuery(control).hasClass('select2-hidden-accessible')) {
            window.jQuery(control).val(control.value).trigger('change.select2');
        }
    }

    function collectFormState(form) {
        const state = {};
        FILTER_FIELDS.forEach((key) => {
            const control = getFieldControl(form, key);
            if (!control) {
                return;
            }

            if (key === 'status') {
                state.status = Array.from(control.selectedOptions || [])
                    .map((option) => normalizeString(option.value))
                    .filter((value) => value !== '' && value !== 'todos');
                return;
            }

            state[key] = normalizeString(control.value);
        });

        return normalizeState(state);
    }

    function initSelect2() {
        if (!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.select2) {
            return;
        }

        window.jQuery('.js-os-select2').each(function initEachSelect2() {
            const element = this;
            const $element = window.jQuery(element);

            if ($element.hasClass('select2-hidden-accessible')) {
                $element.select2('destroy');
            }

            const container = element.closest('.offcanvas') || document.body;
            const isMultiple = element.multiple;
            const placeholder = element.getAttribute('data-placeholder') || 'Selecionar';

            $element.select2({
                theme: 'bootstrap-5',
                width: '100%',
                closeOnSelect: !isMultiple,
                allowClear: !isMultiple,
                placeholder: placeholder,
                dropdownParent: window.jQuery(container),
            });
        });
    }

    function setupActiveChips(state, labelsMap, chipsWrap, chipsContainer, clearAllBtn) {
        if (!chipsWrap || !chipsContainer) {
            return;
        }

        const getLabel = (group, key) => {
            const map = labelsMap?.[group] || {};
            return map[key] || key;
        };

        const chips = [];

        if (state.q) {
            chips.push({
                key: 'q',
                value: '',
                text: `Busca: ${state.q}`,
            });
        }

        (state.status || []).forEach((statusCode) => {
            chips.push({
                key: 'status',
                value: statusCode,
                text: `Status: ${getLabel('status', statusCode)}`,
            });
        });

        if (state.macrofase) {
            chips.push({
                key: 'macrofase',
                value: '',
                text: `Macrofase: ${getLabel('macrofases', state.macrofase)}`,
            });
        }

        if (state.estado_fluxo) {
            chips.push({
                key: 'estado_fluxo',
                value: '',
                text: `Fluxo: ${getLabel('estado_fluxo', state.estado_fluxo)}`,
            });
        }

        if (state.data_inicio) {
            chips.push({
                key: 'data_inicio',
                value: '',
                text: `Abertura de: ${state.data_inicio}`,
            });
        }

        if (state.data_fim) {
            chips.push({
                key: 'data_fim',
                value: '',
                text: `Abertura ate: ${state.data_fim}`,
            });
        }

        if (state.tecnico_id) {
            chips.push({
                key: 'tecnico_id',
                value: '',
                text: `Tecnico: ${getLabel('tecnicos', state.tecnico_id)}`,
            });
        }

        if (state.tipo_servico) {
            chips.push({
                key: 'tipo_servico',
                value: '',
                text: `Servico: ${state.tipo_servico}`,
            });
        }

        if (state.valor_min) {
            chips.push({
                key: 'valor_min',
                value: '',
                text: `Valor min: ${state.valor_min}`,
            });
        }

        if (state.valor_max) {
            chips.push({
                key: 'valor_max',
                value: '',
                text: `Valor max: ${state.valor_max}`,
            });
        }

        if (state.situacao) {
            chips.push({
                key: 'situacao',
                value: '',
                text: `Situacao: ${getLabel('situacao', state.situacao)}`,
            });
        }

        chipsWrap.classList.toggle('d-none', chips.length === 0);
        if (clearAllBtn) {
            clearAllBtn.classList.toggle('d-none', chips.length === 0);
        }

        chipsContainer.innerHTML = chips
            .map((chip) => (
                `<span class="os-filter-chip">` +
                `<span>${chip.text}</span>` +
                `<button type="button" class="os-chip-remove" data-chip-key="${chip.key}" data-chip-value="${chip.value}" aria-label="Remover filtro">` +
                `<i class="bi bi-x-lg"></i>` +
                `</button>` +
                `</span>`
            ))
            .join('');
    }

    function getResponsiveAvailableWidth(tableElement) {
        const host = tableElement?.closest('.table-responsive')
            || tableElement?.closest('.card-body')
            || tableElement?.parentElement
            || null;

        const measuredWidth = host?.getBoundingClientRect
            ? Math.floor(host.getBoundingClientRect().width)
            : 0;
        const fallbackWidth = Math.floor(window.innerWidth || 1280);

        if (measuredWidth > 0) {
            return Math.max(320, measuredWidth - 24);
        }

        return Math.max(
            320,
            Math.min(
                fallbackWidth,
                Math.floor(document.documentElement?.clientWidth || fallbackWidth)
            )
        );
    }

    function isCardLayoutViewport(viewport) {
        return viewport < 768;
    }

    function getResponsiveProfile(viewport) {
        if (viewport >= 1400) {
            return 'desktop-xl';
        }
        if (viewport >= 1200) {
            return 'desktop-lg';
        }
        if (viewport >= 992) {
            return 'notebook';
        }
        if (viewport >= 768) {
            return 'tablet';
        }
        if (viewport >= 576) {
            return 'mobile-lg';
        }

        return 'mobile-sm';
    }

    function getResponsiveColumnVisibility(viewport) {
        const profile = getResponsiveProfile(viewport);

        if (profile === 'mobile-lg' || profile === 'mobile-sm') {
            return {
                1: true,
                2: true,
                3: true,
                4: true,
                5: false,
                6: false,
                7: false,
                8: false,
                9: false,
            };
        }

        const visibility = {
            1: true,
            2: true,
            3: true,
            4: true,
            5: true,
            6: true,
            7: true,
            8: true,
            9: true,
        };

        switch (profile) {
            case 'desktop-lg':
                visibility[8] = false;
                if (viewport < 1360) {
                    visibility[5] = false;
                }
                if (viewport < 1280) {
                    visibility[9] = false;
                }
                break;

            case 'notebook':
                visibility[8] = false;
                visibility[5] = false;
                visibility[9] = false;
                if (viewport < 1120) {
                    visibility[4] = false;
                }
                if (viewport < 1000) {
                    visibility[6] = false;
                }
                break;

            case 'tablet':
                visibility[8] = false;
                visibility[5] = false;
                visibility[4] = false;
                visibility[1] = false;
                if (viewport < 860) {
                    visibility[6] = false;
                }
                break;

            default:
                break;
        }

        return visibility;
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function setPhotoViewerStage(ui, button) {
        if (!ui?.stageImage || !ui?.stageCaption || !button) {
            return;
        }

        ui.stageImage.src = button.getAttribute('data-photo-src') || '';
        ui.stageImage.alt = button.getAttribute('data-photo-caption') || 'Foto da OS';
        ui.stageCaption.textContent = button.getAttribute('data-photo-caption') || '';

        ui.grid?.querySelectorAll('.os-photo-thumb-btn').forEach((thumbButton) => {
            thumbButton.classList.toggle('is-active', thumbButton === button);
        });
    }

    function renderPhotoGallery(ui, photos, emptyText) {
        if (!ui?.grid || !ui?.stageWrap || !ui?.empty) {
            return;
        }

        const list = Array.isArray(photos) ? photos : [];
        ui.grid.innerHTML = '';

        if (list.length === 0) {
            ui.stageWrap.classList.add('d-none');
            ui.empty.classList.remove('d-none');
            ui.empty.innerHTML = `<i class="bi bi-image text-muted"></i><span>${escapeHtml(emptyText)}</span>`;
            return;
        }

        ui.empty.classList.add('d-none');
        ui.stageWrap.classList.remove('d-none');

        ui.grid.innerHTML = list.map((photo, index) => {
            const label = photo?.label || `Foto ${index + 1}`;
            const principalBadge = photo?.is_principal
                ? '<span class="os-photo-thumb-flag">Principal</span>'
                : '';

            return [
                `<button type="button" class="os-photo-thumb-btn${index === 0 ? ' is-active' : ''}" data-photo-src="${escapeHtml(photo?.url || '')}" data-photo-caption="${escapeHtml(label)}">`,
                `<img src="${escapeHtml(photo?.url || '')}" alt="${escapeHtml(label)}" loading="lazy">`,
                principalBadge,
                '</button>',
            ].join('');
        }).join('');

        const firstButton = ui.grid.querySelector('.os-photo-thumb-btn');
        setPhotoViewerStage(ui, firstButton);
    }

    function bindPhotoGallery(ui) {
        ui?.grid?.addEventListener('click', (event) => {
            const button = event.target.closest('.os-photo-thumb-btn');
            if (!button) {
                return;
            }

            event.preventDefault();
            setPhotoViewerStage(ui, button);
        });
    }

    function getHiddenResponsiveColumns(dataTable) {
        return OS_TABLE_DATA_COLUMNS.filter((column) => !dataTable.column(column.dtIndex).visible());
    }

    function updateResponsiveToggleButton(button, isExpanded) {
        if (!button) {
            return;
        }
        button.classList.toggle('is-expanded', Boolean(isExpanded));
        button.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
        button.setAttribute('aria-label', isExpanded ? 'Ocultar detalhes' : 'Mostrar detalhes');
        button.innerHTML = `<i class="bi ${isExpanded ? 'bi-dash-lg' : 'bi-plus-lg'}"></i>`;
    }

    function buildResponsiveDetailsHtml(rowApi, dataTable) {
        const rowData = rowApi?.data();
        if (!Array.isArray(rowData)) {
            return '';
        }

        const hiddenColumns = getHiddenResponsiveColumns(dataTable);
        const tableNode = dataTable?.table?.().node?.() || null;
        const isMobileCardLayout = Boolean(tableNode?.classList.contains('os-mobile-cards'));
        const detailEntries = [];

        if (isMobileCardLayout) {
            const equipamentoHtml = rowData[3] == null
                ? ''
                : String(rowData[3]).trim();

            if (equipamentoHtml !== '') {
                detailEntries.push({
                    key: 'equipamento_detalhes',
                    label: 'Equipamento',
                    content: equipamentoHtml,
                });
            }
        }

        if (hiddenColumns.length === 0 && detailEntries.length === 0) {
            return '';
        }

        hiddenColumns.forEach((column) => {
            const rawValue = rowData[column.dataIndex] == null
                ? ''
                : String(rowData[column.dataIndex]).trim();
            detailEntries.push({
                key: column.key,
                label: column.label,
                content: rawValue !== '' ? rawValue : '<span class="text-muted">-</span>',
            });
        });

        const detailsHtml = detailEntries.map((entry) => [
            `<div class="os-responsive-detail-row${entry.key === 'acoes' ? ' is-actions' : ''}">`,
            `<div class="os-responsive-detail-label">${entry.label}</div>`,
            `<div class="os-responsive-detail-value">${entry.content}</div>`,
            '</div>',
        ].join('')).join('');

        return `<div class="os-responsive-details">${detailsHtml}</div>`;
    }

    function fitRelatoCells(scope) {
        const root = scope instanceof Element ? scope : document;
        root.querySelectorAll('.os-relato-cell').forEach((cell) => {
            const isCardCell = Boolean(cell.closest('#osTable.os-mobile-cards'));
            const baseFontSize = isCardCell
                ? (window.innerWidth <= 575 ? 0.92 : 0.96)
                : (window.innerWidth <= 575 ? 0.82 : 0.92);
            const minimumFontSize = isCardCell ? 0.82 : 0.7;
            let currentFontSize = baseFontSize;
            let iterations = 0;

            cell.style.fontSize = `${baseFontSize}rem`;

            const resolveLineHeight = () => {
                const computed = window.getComputedStyle(cell);
                const parsed = Number.parseFloat(computed.lineHeight);
                if (Number.isFinite(parsed) && parsed > 0) {
                    return parsed;
                }
                return currentFontSize * 16 * 1.35;
            };

            let lineHeight = resolveLineHeight();
            let maxHeight = (lineHeight * 4) + 2;

            while (cell.scrollHeight > maxHeight && currentFontSize > minimumFontSize && iterations < 12) {
                currentFontSize = Number.parseFloat((currentFontSize - 0.04).toFixed(2));
                cell.style.fontSize = `${currentFontSize}rem`;
                lineHeight = resolveLineHeight();
                maxHeight = (lineHeight * 4) + 2;
                iterations += 1;
            }
        });
    }

    function syncResponsiveRowDetails(dataTable) {
        const hasHiddenColumns = getHiddenResponsiveColumns(dataTable).length > 0;

        dataTable.rows({ page: 'current' }).every(function syncEachRow() {
            const rowApi = this;
            const rowNode = rowApi.node();
            if (!rowNode) {
                return;
            }

            const toggleButton = rowNode.querySelector('.os-row-toggle');
            if (!hasHiddenColumns) {
                if (rowApi.child.isShown()) {
                    rowApi.child.hide();
                }
                rowNode.classList.remove('shown');
                updateResponsiveToggleButton(toggleButton, false);
                return;
            }

            if (rowApi.child.isShown()) {
                const detailsHtml = buildResponsiveDetailsHtml(rowApi, dataTable);
                if (detailsHtml !== '') {
                    rowApi.child(detailsHtml, 'os-responsive-child-row').show();
                    rowNode.classList.add('shown');
                    updateResponsiveToggleButton(toggleButton, true);
                    return;
                }

                rowApi.child.hide();
                rowNode.classList.remove('shown');
            }

            updateResponsiveToggleButton(toggleButton, false);
        });
    }

    function getResponsiveTableHost(tableElement) {
        return tableElement?.closest('.table-responsive')
            || tableElement?.closest('#osTable_wrapper')
            || tableElement?.closest('.card-body')
            || tableElement?.parentElement
            || null;
    }

    function hasResponsiveTableOverflow(tableElement) {
        const host = getResponsiveTableHost(tableElement);
        const hostWidth = host?.clientWidth || 0;
        const tableWidth = tableElement?.scrollWidth || 0;

        if (hostWidth <= 0 || tableWidth <= 0) {
            return false;
        }

        return (tableWidth - hostWidth) > 1;
    }

    function collapseOverflowSensitiveColumns(dataTable, tableElement, visibility) {
        if (!dataTable || !tableElement || !visibility) {
            return;
        }

        OS_OVERFLOW_HIDE_PRIORITY.forEach((columnIndex) => {
            if (!visibility[columnIndex]) {
                return;
            }

            if (!hasResponsiveTableOverflow(tableElement)) {
                return;
            }

            visibility[columnIndex] = false;
            if (dataTable.column(columnIndex).visible()) {
                dataTable.column(columnIndex).visible(false, false);
                dataTable.columns.adjust();
            }
        });
    }

    function setupResponsiveColumns(dataTable, tableElement) {
        if (!dataTable || !tableElement) {
            return;
        }

        const viewport = getResponsiveAvailableWidth(tableElement);
        const visibility = getResponsiveColumnVisibility(viewport);
        const useCardLayout = isCardLayoutViewport(viewport);
        const pageRoot = tableElement.closest('.os-list-page');
        const responsiveProfile = getResponsiveProfile(viewport);

        tableElement.classList.toggle('os-mobile-cards', useCardLayout);
        pageRoot?.classList.toggle('os-mobile-cards-mode', useCardLayout);
        if (pageRoot) {
            pageRoot.setAttribute('data-os-breakpoint', responsiveProfile);
            pageRoot.setAttribute('data-os-available-width', String(viewport));
        }

        Object.entries(visibility).forEach(([index, isVisible]) => {
            const columnIndex = Number(index);
            if (dataTable.column(columnIndex).visible() !== isVisible) {
                dataTable.column(columnIndex).visible(isVisible, false);
            }
        });

        if (useCardLayout) {
            dataTable.rows({ page: 'current' }).every(function hideResponsiveChildRows() {
                if (this.child.isShown()) {
                    this.child.hide();
                }
                const rowNode = this.node();
                if (rowNode) {
                    rowNode.classList.remove('shown');
                    updateResponsiveToggleButton(rowNode.querySelector('.os-row-toggle'), false);
                }
            });
        }

        dataTable.columns.adjust();

        if (!useCardLayout) {
            collapseOverflowSensitiveColumns(dataTable, tableElement, visibility);
        }

        const hasHiddenColumns = Object.values(visibility).some((isVisible) => !isVisible);
        const showResponsiveControl = hasHiddenColumns;
        if (dataTable.column(0).visible() !== showResponsiveControl) {
            dataTable.column(0).visible(showResponsiveControl, false);
            dataTable.columns.adjust();
        }

        syncResponsiveRowDetails(dataTable);
        fitRelatoCells(tableElement);

        const bodyRows = tableElement.querySelectorAll('tbody tr');
        bodyRows.forEach((row) => {
            const cells = row.querySelectorAll('td');
            cells.forEach((cell, idx) => {
                const isControlCell = cell.classList.contains('os-details-control');
                const columnMeta = OS_TABLE_DATA_COLUMNS.find((column) => column.dtIndex === idx) || null;
                const isVisibleColumn = columnMeta ? dataTable.column(columnMeta.dtIndex).visible() : false;
                const label = columnMeta?.label || '';
                const isActionsCell = !isControlCell && label === 'Acoes' && isVisibleColumn;

                if (isControlCell) {
                    cell.removeAttribute('data-label');
                } else if (!isVisibleColumn || !columnMeta) {
                    cell.removeAttribute('data-label');
                } else {
                    cell.setAttribute('data-label', label);
                }

                if (isActionsCell) {
                    cell.classList.add('col-acoes');
                } else {
                    cell.classList.remove('col-acoes');
                }
            });
        });
    }

    function bindResponsiveDetailsToggle(dataTable, tableElement) {
        if (!dataTable || !tableElement) {
            return;
        }

        window.jQuery(tableElement).on('click', 'tbody td.os-details-control', function handleResponsiveClick(event) {
            event.preventDefault();

            const rowElement = event.currentTarget.closest('tr');
            if (!rowElement || rowElement.classList.contains('child')) {
                return;
            }

            const rowApi = dataTable.row(rowElement);
            const detailsHtml = buildResponsiveDetailsHtml(rowApi, dataTable);
            const toggleButton = rowElement.querySelector('.os-row-toggle');

            if (detailsHtml === '') {
                if (rowApi.child.isShown()) {
                    rowApi.child.hide();
                    rowElement.classList.remove('shown');
                }
                updateResponsiveToggleButton(toggleButton, false);
                return;
            }

            if (rowApi.child.isShown()) {
                rowApi.child.hide();
                rowElement.classList.remove('shown');
                updateResponsiveToggleButton(toggleButton, false);
                return;
            }

            rowApi.child(detailsHtml, 'os-responsive-child-row').show();
            rowElement.classList.add('shown');
            updateResponsiveToggleButton(toggleButton, true);
        });
    }

    function buildResponsiveResizeHandler(dataTable, tableElement) {
        return debounce(() => {
            setupResponsiveColumns(dataTable, tableElement);
        }, 120);
    }

    document.addEventListener('DOMContentLoaded', function () {
        const tableElement = document.getElementById('osTable');
        if (!tableElement || !window.jQuery || !window.jQuery.fn || !window.jQuery.fn.DataTable) {
            return;
        }

        const config = window.osListConfig || {};
        const labelsMap = config.labels || {};
        const desktopForm = document.getElementById('osFiltersDesktopForm');
        const mobileForm = document.getElementById('osFiltersMobileForm');
        const chipsWrap = document.getElementById('osActiveFiltersWrap');
        const chipsContainer = document.getElementById('osActiveFilterChips');
        const clearAllBtn = document.getElementById('osClearAllFilters');
        const mobileFilterButton = document.getElementById('osOpenMobileFilters');
        const mobileFilterCount = document.getElementById('osMobileFilterCount');
        const loadingOverlay = document.getElementById('osTableLoading');
        const resultsCounter = document.getElementById('osResultsCounter');
        const resultsSpinner = document.getElementById('osResultsSpinner');
        const advancedCollapseElement = document.getElementById('osAdvancedFiltersCollapse');
        const advancedToggleBtn = document.getElementById('osToggleAdvanced');
        const mobileDrawerElement = document.getElementById('osFiltersDrawer');
        const mobileDrawer = mobileDrawerElement && window.bootstrap
            ? window.bootstrap.Offcanvas.getOrCreateInstance(mobileDrawerElement)
            : null;
        const statusModalElement = document.getElementById('osStatusModal');
        const statusModalForm = document.getElementById('osStatusModalForm');
        const statusModalSelect = document.getElementById('osStatusModalSelect');
        const statusModalNumero = document.getElementById('osStatusModalNumero');
        const statusModalObservacao = document.getElementById('osStatusModalObservacao');
        const statusModalSubmit = document.getElementById('osStatusModalSubmit');
        const statusModal = statusModalElement && window.bootstrap
            ? window.bootstrap.Modal.getOrCreateInstance(statusModalElement)
            : null;
        const photoModalElements = {
            element: document.getElementById('osPhotosModal'),
            title: document.getElementById('osPhotosModalTitle'),
            subtitle: document.getElementById('osPhotosModalSubtitle'),
            loading: document.getElementById('osPhotosModalLoading'),
        };
        const photoModalTabs = {
            equipmentButton: document.getElementById('osPhotosEquipTab'),
            entryButton: document.getElementById('osPhotosEntryTab'),
            equipmentCount: document.getElementById('osPhotosEquipCount'),
            entryCount: document.getElementById('osPhotosEntryCount'),
        };
        const photoViewerMap = {
            equipment: {
                stageWrap: document.getElementById('osPhotosEquipStageWrap'),
                stageImage: document.getElementById('osPhotosEquipStageImage'),
                stageCaption: document.getElementById('osPhotosEquipStageCaption'),
                grid: document.getElementById('osPhotosEquipGrid'),
                empty: document.getElementById('osPhotosEquipEmpty'),
            },
            entry: {
                stageWrap: document.getElementById('osPhotosEntryStageWrap'),
                stageImage: document.getElementById('osPhotosEntryStageImage'),
                stageCaption: document.getElementById('osPhotosEntryStageCaption'),
                grid: document.getElementById('osPhotosEntryGrid'),
                empty: document.getElementById('osPhotosEntryEmpty'),
            },
        };
        const photoModal = photoModalElements.element && window.bootstrap
            ? window.bootstrap.Modal.getOrCreateInstance(photoModalElements.element)
            : null;

        const baseState = normalizeState(config.initialFilters || {});
        const urlState = readUrlState();
        const localState = readStorageState();

        let activeState = hasAnyFilter(urlState)
            ? normalizeState({ ...baseState, ...urlState })
            : (hasAnyFilter(localState)
                ? normalizeState({ ...baseState, ...localState })
                : baseState);

        initSelect2();
        bindPhotoGallery(photoViewerMap.equipment);
        bindPhotoGallery(photoViewerMap.entry);

        const syncForms = () => {
            syncInProgress = true;
            [desktopForm, mobileForm].forEach((form) => {
                if (!form) {
                    return;
                }
                FILTER_FIELDS.forEach((key) => {
                    setFieldValue(form, key, key === 'status' ? activeState.status : activeState[key]);
                });
            });
            syncInProgress = false;
        };

        const updateMobileFilterBadge = () => {
            if (!mobileFilterCount) {
                return;
            }
            const count = FILTER_FIELDS.reduce((total, key) => {
                if (key === 'status') {
                    return total + (activeState.status?.length || 0);
                }
                return total + (normalizeString(activeState[key]) !== '' ? 1 : 0);
            }, 0);
            mobileFilterCount.textContent = count > 0 ? String(count) : '0';
            mobileFilterButton?.classList.toggle('btn-outline-primary', count === 0);
            mobileFilterButton?.classList.toggle('btn-glow', count > 0);
        };

        const toggleLoadingOverlay = (isLoading) => {
            if (!loadingOverlay) {
                if (resultsSpinner) {
                    resultsSpinner.classList.toggle('d-none', !isLoading);
                }
                return;
            }
            loadingOverlay.classList.toggle('show', Boolean(isLoading));
            if (resultsSpinner) {
                resultsSpinner.classList.toggle('d-none', !isLoading);
            }
        };

        let activeStatusOsId = null;

        const updateCsrfFromPayload = (payload) => {
            if (!payload || !payload.csrfHash || !config.csrfTokenKey) {
                return;
            }
            config.csrfTokenValue = payload.csrfHash;
        };

        const setPhotoModalLoading = (isLoading) => {
            photoModalElements.loading?.classList.toggle('d-none', !isLoading);
        };

        const resetPhotoViewer = () => {
            Object.values(photoViewerMap).forEach((ui) => {
                if (!ui) {
                    return;
                }
                ui.grid && (ui.grid.innerHTML = '');
                ui.empty?.classList.add('d-none');
                ui.stageWrap?.classList.add('d-none');
                if (ui.stageImage) {
                    ui.stageImage.removeAttribute('src');
                    ui.stageImage.alt = 'Foto da OS';
                }
                if (ui.stageCaption) {
                    ui.stageCaption.textContent = '';
                }
            });
            photoModalElements.title && (photoModalElements.title.textContent = 'Fotos da OS');
            photoModalElements.subtitle && (photoModalElements.subtitle.textContent = 'Visualizador de fotos do equipamento e da abertura.');
            photoModalTabs.equipmentCount && (photoModalTabs.equipmentCount.textContent = '0');
            photoModalTabs.entryCount && (photoModalTabs.entryCount.textContent = '0');
        };

        const openPhotosModal = async (osId) => {
            if (!photoModal || !config.photosUrlBase) {
                return;
            }

            resetPhotoViewer();
            setPhotoModalLoading(true);
            photoModal.show();

            try {
                const response = await window.fetch(`${config.photosUrlBase}/${osId}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });

                const payload = await response.json();
                updateCsrfFromPayload(payload);

                if (!response.ok || !payload.ok) {
                    throw new Error(payload.message || 'Nao foi possivel carregar as fotos da OS.');
                }

                const numeroOs = payload.os?.numero_os ? `#${payload.os.numero_os}` : '-';
                const profilePhotos = Array.isArray(payload.profilePhotos) ? payload.profilePhotos : [];
                const entryPhotos = Array.isArray(payload.entryPhotos) ? payload.entryPhotos : [];

                if (photoModalElements.title) {
                    photoModalElements.title.textContent = `Fotos da OS ${numeroOs}`;
                }
                if (photoModalElements.subtitle) {
                    photoModalElements.subtitle.textContent = `${profilePhotos.length} foto(s) de perfil e ${entryPhotos.length} foto(s) registradas na abertura.`;
                }

                photoModalTabs.equipmentCount && (photoModalTabs.equipmentCount.textContent = String(profilePhotos.length));
                photoModalTabs.entryCount && (photoModalTabs.entryCount.textContent = String(entryPhotos.length));

                renderPhotoGallery(
                    photoViewerMap.equipment,
                    profilePhotos,
                    'Nenhuma foto de perfil cadastrada para este equipamento.'
                );
                renderPhotoGallery(
                    photoViewerMap.entry,
                    entryPhotos,
                    'Nenhuma foto foi registrada na abertura desta ordem de servico.'
                );

                if (window.bootstrap && photoModalTabs.equipmentButton && photoModalTabs.entryButton) {
                    const tabButton = profilePhotos.length > 0 || entryPhotos.length === 0
                        ? photoModalTabs.equipmentButton
                        : photoModalTabs.entryButton;
                    window.bootstrap.Tab.getOrCreateInstance(tabButton).show();
                }
            } catch (error) {
                photoModal.hide();
                if (window.Swal) {
                    window.Swal.fire({
                        icon: 'error',
                        title: 'Falha ao carregar fotos',
                        text: error.message || 'Nao foi possivel carregar as fotos da OS.',
                    });
                } else {
                    alert(error.message || 'Nao foi possivel carregar as fotos da OS.');
                }
            } finally {
                setPhotoModalLoading(false);
            }
        };

        const setStatusModalLoading = (isLoading) => {
            if (!statusModalSubmit) {
                return;
            }

            statusModalSubmit.disabled = Boolean(isLoading);
            statusModalSelect && (statusModalSelect.disabled = Boolean(isLoading));
            statusModalObservacao && (statusModalObservacao.disabled = Boolean(isLoading));

            statusModalSubmit.innerHTML = isLoading
                ? '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Salvando...'
                : '<i class="bi bi-check2-circle me-1"></i>Salvar status';
        };

        const populateStatusOptions = (groupedOptions, currentStatus) => {
            if (!statusModalSelect) {
                return;
            }

            const groups = groupedOptions || {};
            const fragments = ['<option value="">Selecione um status</option>'];

            Object.entries(groups).forEach(([groupKey, items]) => {
                if (!Array.isArray(items) || items.length === 0) {
                    return;
                }

                const label = String(groupKey || 'outros').replace(/_/g, ' ');
                fragments.push(`<optgroup label="${label.charAt(0).toUpperCase() + label.slice(1)}">`);
                items.forEach((item) => {
                    const code = String(item.codigo || '').trim();
                    const name = String(item.nome || code).trim();
                    if (!code) {
                        return;
                    }
                    const selected = code === currentStatus ? ' selected' : '';
                    fragments.push(`<option value="${code}"${selected}>${name}</option>`);
                });
                fragments.push('</optgroup>');
            });

            statusModalSelect.innerHTML = fragments.join('');
        };

        const openStatusModal = async (osId) => {
            if (!statusModal || !statusModalSelect) {
                return;
            }

            activeStatusOsId = osId;
            setStatusModalLoading(true);
            statusModalNumero.textContent = '-';
            statusModalObservacao.value = '';
            statusModalSelect.innerHTML = '<option value="">Carregando...</option>';
            statusModal.show();

            try {
                const response = await window.fetch(`${config.statusMetaUrlBase}/${osId}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });

                const payload = await response.json();
                updateCsrfFromPayload(payload);

                if (!response.ok || !payload.ok) {
                    throw new Error(payload.message || 'Nao foi possivel carregar o fluxo de status.');
                }

                statusModalNumero.textContent = payload.os?.numero_os
                    ? `#${payload.os.numero_os}`
                    : '-';
                populateStatusOptions(payload.options, payload.os?.status || '');
            } catch (error) {
                statusModal.hide();
                if (window.Swal) {
                    window.Swal.fire({
                        icon: 'error',
                        title: 'Falha ao carregar status',
                        text: error.message || 'Nao foi possivel carregar o fluxo de status.',
                    });
                } else {
                    alert(error.message || 'Nao foi possivel carregar o fluxo de status.');
                }
            } finally {
                setStatusModalLoading(false);
            }
        };

        const applyState = (nextState, options = {}) => {
            activeState = normalizeState(nextState);
            syncForms();
            saveStorageState(activeState);
            writeUrlState(activeState);
            setupActiveChips(activeState, labelsMap, chipsWrap, chipsContainer, clearAllBtn);
            updateMobileFilterBadge();

            if (options.reload !== false) {
                toggleLoadingOverlay(true);
                dataTable.ajax.reload();
            }

            if (options.closeMobile && mobileDrawer) {
                mobileDrawer.hide();
            }

            if (advancedToggleBtn && advancedCollapseElement) {
                advancedToggleBtn.classList.toggle('active', hasAdvancedFilter(activeState));
            }
        };

        const buildPayload = (payload) => {
            payload.q = activeState.q;
            payload.status = activeState.status;
            payload.status_list = activeState.status.join(',');
            payload.macrofase = activeState.macrofase;
            payload.estado_fluxo = activeState.estado_fluxo;
            payload.data_inicio = activeState.data_inicio;
            payload.data_fim = activeState.data_fim;
            payload.tecnico_id = activeState.tecnico_id;
            payload.tipo_servico = activeState.tipo_servico;
            payload.valor_min = activeState.valor_min;
            payload.valor_max = activeState.valor_max;
            payload.situacao = activeState.situacao;

            if (config.csrfTokenKey && config.csrfTokenValue) {
                payload[config.csrfTokenKey] = config.csrfTokenValue;
            }
        };

        const dataTable = window.jQuery(tableElement).DataTable({
            language: {
                url: config.languageUrl || `${window.location.origin}/assets/json/pt-BR.json`,
            },
            columns: [
                {
                    data: null,
                    className: 'os-details-control',
                    orderable: false,
                    searchable: false,
                    render: function renderResponsiveControl() {
                        return '<button type="button" class="os-row-toggle" aria-label="Mostrar detalhes" aria-expanded="false"><i class="bi bi-plus-lg"></i></button>';
                    },
                    width: '42px',
                },
                {
                    data: 0,
                    orderable: false,
                    searchable: false,
                },
                { data: 1 },
                { data: 2 },
                { data: 3 },
                { data: 4 },
                { data: 5 },
                { data: 6 },
                { data: 7 },
                {
                    data: 8,
                    orderable: false,
                    searchable: false,
                },
            ],
            processing: true,
            serverSide: true,
            searching: false,
            autoWidth: false,
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            order: [[6, 'desc']],
            dom: '<"row align-items-center mb-3"<"col-12 col-md-6"l><"col-12 col-md-6 text-md-end"p>>rt<"row align-items-center mt-3"<"col-12 col-md-6"i><"col-12 col-md-6 text-md-end"p>>',
            ajax: {
                url: config.datatableUrl,
                type: 'POST',
                data: function (payload) {
                    buildPayload(payload);
                },
                error: function (xhr) {
                    toggleLoadingOverlay(false);
                    const message = xhr?.status === 0
                        ? 'Falha de conexao ao carregar OS.'
                        : 'Nao foi possivel aplicar os filtros no momento.';
                    if (window.Swal) {
                        window.Swal.fire({
                            icon: 'error',
                            title: 'Falha nos filtros',
                            text: message,
                        });
                    } else {
                        alert(message);
                    }
                },
            },
            drawCallback: function () {
                setupResponsiveColumns(this.api(), tableElement);
                toggleLoadingOverlay(false);
                fitRelatoCells(tableElement);
            },
            initComplete: function () {
                setupResponsiveColumns(this.api(), tableElement);
                toggleLoadingOverlay(false);
                fitRelatoCells(tableElement);
            },
        });

        bindResponsiveDetailsToggle(dataTable, tableElement);
        window.addEventListener('resize', buildResponsiveResizeHandler(dataTable, tableElement));

        window.osListController = {
            reload: function reload(preservePaging = true) {
                toggleLoadingOverlay(true);
                dataTable.ajax.reload(null, !preservePaging);
            },
        };

        window.jQuery(tableElement).on('preXhr.dt', function () {
            toggleLoadingOverlay(true);
        });

        window.jQuery(tableElement).on('xhr.dt', function (_event, _settings, json) {
            const filtered = Number(json?.recordsFiltered ?? 0);
            if (resultsCounter) {
                resultsCounter.textContent = `${filtered} OS encontradas`;
            }
            toggleLoadingOverlay(false);
            fitRelatoCells(tableElement);
        });

        tableElement.addEventListener('click', function (event) {
            const photoTrigger = event.target.closest('[data-os-photo-action]');
            if (photoTrigger) {
                event.preventDefault();
                event.stopPropagation();

                const osId = Number(photoTrigger.getAttribute('data-os-id') || '0');
                if (!Number.isFinite(osId) || osId <= 0) {
                    return;
                }

                openPhotosModal(osId);
                return;
            }

            const trigger = event.target.closest('[data-os-status-action]');
            if (!trigger) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();

            const osId = Number(trigger.getAttribute('data-os-id') || '0');
            if (!Number.isFinite(osId) || osId <= 0) {
                return;
            }

            openStatusModal(osId);
        });

        statusModalForm?.addEventListener('submit', async function (event) {
            event.preventDefault();

            if (!activeStatusOsId || !statusModalSelect) {
                return;
            }

            if (!statusModalSelect.value) {
                if (window.Swal) {
                    window.Swal.fire({
                        icon: 'warning',
                        title: 'Selecione um status',
                        text: 'Escolha o proximo status permitido para continuar.',
                    });
                } else {
                    alert('Escolha o proximo status permitido para continuar.');
                }
                return;
            }

            const formData = new window.FormData();
            formData.append('status', statusModalSelect.value);
            formData.append('observacao_status', statusModalObservacao?.value || '');
            if (config.csrfTokenKey && config.csrfTokenValue) {
                formData.append(config.csrfTokenKey, config.csrfTokenValue);
            }

            setStatusModalLoading(true);

            try {
                const response = await window.fetch(`${config.statusUpdateUrlBase}/${activeStatusOsId}`, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: formData,
                });

                const payload = await response.json();
                updateCsrfFromPayload(payload);

                if (!response.ok || !payload.ok) {
                    throw new Error(payload.message || 'Nao foi possivel atualizar o status.');
                }

                statusModal.hide();
                if (window.Swal) {
                    await window.Swal.fire({
                        icon: 'success',
                        title: 'Status atualizado',
                        text: payload.message || 'O status da OS foi atualizado com sucesso.',
                    });
                }

                window.osListController.reload(true);
            } catch (error) {
                if (window.Swal) {
                    window.Swal.fire({
                        icon: 'error',
                        title: 'Falha ao atualizar status',
                        text: error.message || 'Nao foi possivel atualizar o status.',
                    });
                } else {
                    alert(error.message || 'Nao foi possivel atualizar o status.');
                }
            } finally {
                setStatusModalLoading(false);
            }
        });

        statusModalElement?.addEventListener('hidden.bs.modal', function () {
            activeStatusOsId = null;
            if (statusModalForm) {
                statusModalForm.reset();
            }
            if (statusModalSelect) {
                statusModalSelect.innerHTML = '<option value="">Selecione um status</option>';
            }
            setStatusModalLoading(false);
        });

        photoModalElements.element?.addEventListener('hidden.bs.modal', function () {
            resetPhotoViewer();
            setPhotoModalLoading(false);
        });

        window.addEventListener('message', function (event) {
            if (event.origin !== window.location.origin) {
                return;
            }

            const payload = event.data || {};
            if (payload.type !== 'os:list-refresh') {
                return;
            }

            window.osListController.reload(true);
        });

        const applyFromForm = (form, options = {}) => {
            if (!form) {
                return;
            }
            const nextState = collectFormState(form);
            applyState(nextState, options);
        };

        const debouncedQuickSearchApply = debounce((form) => {
            applyFromForm(form, { reload: true, closeMobile: false });
        }, 300);

        const bindFormEvents = (form, isMobileForm) => {
            if (!form) {
                return;
            }

            form.addEventListener('click', function (event) {
                const applyTrigger = event.target.closest('[data-filter-action="apply"]');
                if (applyTrigger) {
                    event.preventDefault();
                    applyFromForm(form, { reload: true, closeMobile: Boolean(isMobileForm) });
                    return;
                }

                const clearTrigger = event.target.closest('[data-filter-action="clear"]');
                if (clearTrigger) {
                    event.preventDefault();
                    applyState(normalizeState({}), { reload: true, closeMobile: Boolean(isMobileForm) });
                }
            });

            form.addEventListener('change', function (event) {
                if (syncInProgress) {
                    return;
                }

                const target = event.target.closest('[data-filter-field]');
                if (!target) {
                    return;
                }

                applyFromForm(form, { reload: true, closeMobile: false });
            });

            const quickSearchInput = getFieldControl(form, 'q');
            if (quickSearchInput) {
                quickSearchInput.addEventListener('input', function () {
                    if (syncInProgress) {
                        return;
                    }
                    debouncedQuickSearchApply(form);
                });
            }
        };

        bindFormEvents(desktopForm, false);
        bindFormEvents(mobileForm, true);

        chipsContainer?.addEventListener('click', function (event) {
            const removeButton = event.target.closest('.os-chip-remove');
            if (!removeButton) {
                return;
            }

            const key = removeButton.getAttribute('data-chip-key') || '';
            const value = removeButton.getAttribute('data-chip-value') || '';
            if (!FILTER_FIELDS.includes(key)) {
                return;
            }

            const nextState = normalizeState(activeState);
            if (key === 'status') {
                nextState.status = (nextState.status || []).filter((item) => item !== value);
            } else {
                nextState[key] = '';
            }
            applyState(nextState, { reload: true, closeMobile: false });
        });

        clearAllBtn?.addEventListener('click', function () {
            applyState(normalizeState({}), { reload: true, closeMobile: false });
        });

        if (advancedToggleBtn && advancedCollapseElement && window.bootstrap) {
            const collapse = window.bootstrap.Collapse.getOrCreateInstance(advancedCollapseElement, {
                toggle: false,
            });

            if (hasAdvancedFilter(activeState)) {
                collapse.show();
                advancedToggleBtn.classList.add('active');
            }

            advancedCollapseElement.addEventListener('shown.bs.collapse', function () {
                advancedToggleBtn.classList.add('active');
            });

            advancedCollapseElement.addEventListener('hidden.bs.collapse', function () {
                advancedToggleBtn.classList.remove('active');
            });
        }

        let resizeTimer = null;
        window.addEventListener('resize', function () {
            if (resizeTimer) {
                window.clearTimeout(resizeTimer);
            }
            resizeTimer = window.setTimeout(function () {
                setupResponsiveColumns(dataTable, tableElement);
            }, 140);
        });

        syncForms();
        setupActiveChips(activeState, labelsMap, chipsWrap, chipsContainer, clearAllBtn);
        updateMobileFilterBadge();
        writeUrlState(activeState);
        saveStorageState(activeState);
    });
})();
