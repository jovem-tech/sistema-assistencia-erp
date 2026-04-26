(function () {
    'use strict';

    const STORAGE_KEY = 'os_advanced_filters_v3';
    const FILTER_FIELDS = [
        'q',
        'status',
        'status_fechadas',
        'status_scope',
        'legado',
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
        'status_scope',
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
    const OS_AUTO_FIT_COLUMNS = [
        { dtIndex: 3, nthChild: 4, contentSelector: '.os-cliente-cell', minWidth: 88, paddingOffset: 14, measureMode: 'intrinsic' },
        { dtIndex: 4, nthChild: 5, contentSelector: '.os-equipamento-cell', minWidth: 92, paddingOffset: 18, measureMode: 'equipment-longest-word', textSelector: '.os-equipamento-measure', labelSelector: '.os-equipamento-label', inlineGap: 10 },
        { dtIndex: 8, nthChild: 9, contentSelector: '.os-valor-cell', minWidth: 72, paddingOffset: 18, measureMode: 'intrinsic' },
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
        const statusScope = normalizeString(source.status_scope) === 'all' ? 'all' : '';
        const closedStatus = statusScope === 'all' ? '' : normalizeString(source.status_fechadas);
        return {
            q: normalizeString(source.q),
            status: closedStatus !== '' || statusScope === 'all' ? [] : normalizeStatusList(source.status ?? source.status_list ?? ''),
            status_fechadas: closedStatus,
            status_scope: statusScope,
            legado: normalizeString(source.legado),
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
            if (key === 'status_scope') {
                return normalizeString(state.status_scope) === 'all';
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

        if (state.status_fechadas) {
            chips.push({
                key: 'status_fechadas',
                value: '',
                text: `Fechadas: ${getLabel('status_fechadas', state.status_fechadas)}`,
            });
        }

        if (state.status_scope) {
            chips.push({
                key: 'status_scope',
                value: '',
                text: `Status geral: ${getLabel('status_scope', state.status_scope)}`,
            });
        }

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

        if (state.legado) {
            chips.push({
                key: 'legado',
                value: '',
                text: `Origem: ${getLabel('legado', state.legado)}`,
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

    function isClosedQueueActive(state) {
        return normalizeString(state?.status_fechadas) !== '';
    }

    function isAllQueueActive(state) {
        return normalizeString(state?.status_scope) === 'all';
    }

    function resolveStatusModeState(state, sourceKey = '') {
        const normalized = normalizeState(state);
        if (sourceKey === 'status' && (normalized.status || []).length > 0) {
            normalized.status_fechadas = '';
            normalized.status_scope = '';
            return normalized;
        }

        if (sourceKey === 'status_fechadas' && normalized.status_fechadas) {
            normalized.status = [];
            normalized.status_scope = '';
            return normalized;
        }

        if (sourceKey === 'status_scope' && normalized.status_scope === 'all') {
            normalized.status = [];
            normalized.status_fechadas = '';
            return normalized;
        }

        if (normalized.status_fechadas) {
            normalized.status = [];
            normalized.status_scope = '';
            return normalized;
        }

        if (normalized.status_scope === 'all') {
            normalized.status = [];
            normalized.status_fechadas = '';
            return normalized;
        }

        if ((normalized.status || []).length > 0) {
            normalized.status_scope = '';
        }

        return normalized;
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

    function clearAutoFitColumnWidth(tableElement, nthChild) {
        if (!tableElement || !nthChild) {
            return;
        }

        tableElement
            .querySelectorAll(`thead th:nth-child(${nthChild}), tbody td:nth-child(${nthChild})`)
            .forEach((cell) => {
                cell.style.removeProperty('width');
                cell.style.removeProperty('min-width');
                cell.style.removeProperty('max-width');
            });
    }

    function applyAutoFitColumnWidth(tableElement, nthChild, widthPx) {
        if (!tableElement || !nthChild || !Number.isFinite(widthPx) || widthPx <= 0) {
            return;
        }

        const widthValue = `${Math.ceil(widthPx)}px`;
        tableElement
            .querySelectorAll(`thead th:nth-child(${nthChild}), tbody td:nth-child(${nthChild})`)
            .forEach((cell) => {
                cell.style.width = widthValue;
                cell.style.minWidth = widthValue;
                cell.style.maxWidth = widthValue;
            });
    }

    function measureIntrinsicElementWidth(element) {
        if (!(element instanceof Element)) {
            return 0;
        }

        const sandbox = document.createElement('div');
        sandbox.style.position = 'absolute';
        sandbox.style.left = '-99999px';
        sandbox.style.top = '-99999px';
        sandbox.style.visibility = 'hidden';
        sandbox.style.pointerEvents = 'none';
        sandbox.style.width = 'max-content';
        sandbox.style.maxWidth = 'none';
        sandbox.style.minWidth = '0';

        const clone = element.cloneNode(true);
        if (clone instanceof HTMLElement) {
            clone.style.width = 'max-content';
            clone.style.maxWidth = 'none';
            clone.style.minWidth = '0';
        }

        sandbox.appendChild(clone);
        document.body.appendChild(sandbox);
        const measuredWidth = Math.ceil(sandbox.getBoundingClientRect().width);
        sandbox.remove();
        return measuredWidth;
    }

    function measureTextLikeElement(element, text) {
        if (!(element instanceof Element)) {
            return 0;
        }

        const clone = element.cloneNode(false);
        clone.textContent = String(text ?? '');
        return measureIntrinsicElementWidth(clone);
    }

    function measureAutoFitCellWidth(cell, column) {
        const mode = String(column.measureMode || 'intrinsic');
        const content = cell.querySelector(column.contentSelector) || cell;

        if (mode === 'equipment-longest-word') {
            let labelWidth = 0;
            content.querySelectorAll(column.labelSelector || '.os-equipamento-label').forEach((label) => {
                labelWidth = Math.max(labelWidth, measureIntrinsicElementWidth(label));
            });

            let longestWordWidth = 0;
            Array.from(content.querySelectorAll(column.textSelector || '.os-equipamento-measure')).forEach((target) => {
                const rawText = String(target.textContent || '').trim();
                if (rawText === '') {
                    return;
                }

                longestWordWidth = Math.max(longestWordWidth, measureTextLikeElement(target, rawText));
            });

            return Math.ceil(
                longestWordWidth
                + labelWidth
                + Number(column.inlineGap || 0)
                + Number(column.paddingOffset || 0)
            );
        }

        return Math.ceil(
            measureIntrinsicElementWidth(content) + Number(column.paddingOffset || 0)
        );
    }

    function syncAutoFitColumns(dataTable, tableElement) {
        if (!dataTable || !tableElement) {
            return;
        }

        OS_AUTO_FIT_COLUMNS.forEach((column) => {
            if (!dataTable.column(column.dtIndex).visible()) {
                clearAutoFitColumnWidth(tableElement, column.nthChild);
                return;
            }

            const cells = Array.from(
                tableElement.querySelectorAll(`tbody tr:not(.child) td:nth-child(${column.nthChild})`)
            ).filter((cell) => window.getComputedStyle(cell).display !== 'none');

            let targetWidth = Number(column.minWidth || 0);
            cells.forEach((cell) => {
                targetWidth = Math.max(
                    targetWidth,
                    measureAutoFitCellWidth(cell, column)
                );
            });

            if (targetWidth > 0) {
                applyAutoFitColumnWidth(tableElement, column.nthChild, targetWidth);
            } else {
                clearAutoFitColumnWidth(tableElement, column.nthChild);
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

        syncAutoFitColumns(dataTable, tableElement);
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
        const legacyToggleButtons = Array.from(document.querySelectorAll('.js-os-legacy-toggle'));
        const loadingOverlay = document.getElementById('osTableLoading');
        const tableTitle = document.getElementById('osTableTitle');
        const tableTitleText = document.getElementById('osTableTitleText');
        const tableSubtitle = document.getElementById('osTableSubtitle');
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
        const statusModalCurrentBadges = document.getElementById('osStatusModalCurrentBadges');
        const statusModalCurrentStatusHint = document.getElementById('osStatusModalCurrentStatusHint');
        const statusModalPrimaryHint = document.getElementById('osStatusModalPrimaryHint');
        const statusModalClientName = document.getElementById('osStatusModalClientName');
        const statusModalClientPhone = document.getElementById('osStatusModalClientPhone');
        const statusModalClientEmail = document.getElementById('osStatusModalClientEmail');
        const statusModalEquipmentName = document.getElementById('osStatusModalEquipmentName');
        const statusModalEquipmentMeta = document.getElementById('osStatusModalEquipmentMeta');
        const statusModalEquipmentSerial = document.getElementById('osStatusModalEquipmentSerial');
        const statusModalQuickNext = document.getElementById('osStatusModalQuickNext');
        const statusModalQuickCancel = document.getElementById('osStatusModalQuickCancel');
        const statusModalTargetHint = document.getElementById('osStatusModalTargetHint');
        const statusModalNotify = document.getElementById('osStatusModalNotify');
        const statusModalNotifyHelp = document.getElementById('osStatusModalNotifyHelp');
        const statusModalTimeline = document.getElementById('osStatusModalTimeline');
        const statusModalHistoryWrap = document.getElementById('osStatusModalHistoryWrap');
        const statusModalHistoryList = document.getElementById('osStatusModalHistoryList');
        const statusModalProcedimentoTextoInput = document.getElementById('osStatusModalProcedimentoTextoInput');
        const statusModalInserirProcedimento = document.getElementById('osStatusModalInserirProcedimento');
        const statusModalProcedimentosInput = document.getElementById('osStatusModalProcedimentosInput');
        const statusModalProcedimentosLista = document.getElementById('osStatusModalProcedimentosLista');
        const statusModalSolucaoInput = document.getElementById('osStatusModalSolucaoInput');
        const statusModalDiagnosticoInput = document.getElementById('osStatusModalDiagnosticoInput');
        const statusModalBudgetPanel = document.getElementById('osStatusModalBudgetPanel');
        const statusModalTabQuickBtn = document.getElementById('osStatusTabQuickBtn');
        const statusModalTabSolutionBtn = document.getElementById('osStatusTabSolutionBtn');
        const statusModalTabBudgetBtn = document.getElementById('osStatusTabBudgetBtn');
        const statusModal = statusModalElement && window.bootstrap
            ? window.bootstrap.Modal.getOrCreateInstance(statusModalElement)
            : null;
        const datesModalElement = document.getElementById('osDatesModal');
        const datesModalForm = document.getElementById('osDatesModalForm');
        const datesModalNumero = document.getElementById('osDatesModalNumero');
        const datesModalBadges = document.getElementById('osDatesModalBadges');
        const datesModalClientName = document.getElementById('osDatesModalClientName');
        const datesModalEquipmentName = document.getElementById('osDatesModalEquipmentName');
        const datesModalEntrada = document.getElementById('osDatesModalEntrada');
        const datesModalPreset = document.getElementById('osDatesModalPreset');
        const datesModalPrevisao = document.getElementById('osDatesModalPrevisao');
        const datesModalEntrega = document.getElementById('osDatesModalEntrega');
        const datesModalEntradaAtual = document.getElementById('osDatesModalEntradaAtual');
        const datesModalPrevisaoAtual = document.getElementById('osDatesModalPrevisaoAtual');
        const datesModalEntregaAtual = document.getElementById('osDatesModalEntregaAtual');
        const datesModalPrazoDias = document.getElementById('osDatesModalPrazoDias');
        const datesModalSubmit = document.getElementById('osDatesModalSubmit');
        const datesModal = datesModalElement && window.bootstrap
            ? window.bootstrap.Modal.getOrCreateInstance(datesModalElement)
            : null;
        const budgetModalElement = document.getElementById('osBudgetModal');
        const budgetModalForm = document.getElementById('osBudgetModalForm');
        const budgetModalNumero = document.getElementById('osBudgetModalNumero');
        const budgetModalBadges = document.getElementById('osBudgetModalBadges');
        const budgetModalClientName = document.getElementById('osBudgetModalClientName');
        const budgetModalClientPhone = document.getElementById('osBudgetModalClientPhone');
        const budgetModalClientEmail = document.getElementById('osBudgetModalClientEmail');
        const budgetModalEquipmentName = document.getElementById('osBudgetModalEquipmentName');
        const budgetModalEquipmentMeta = document.getElementById('osBudgetModalEquipmentMeta');
        const budgetModalMaoObra = document.getElementById('osBudgetModalMaoObra');
        const budgetModalPecas = document.getElementById('osBudgetModalPecas');
        const budgetModalSubtotal = document.getElementById('osBudgetModalSubtotal');
        const budgetModalValorFinal = document.getElementById('osBudgetModalValorFinal');
        const budgetModalNotify = document.getElementById('osBudgetModalNotify');
        const budgetModalNotifyHelp = document.getElementById('osBudgetModalNotifyHelp');
        const budgetModalPhone = document.getElementById('osBudgetModalPhone');
        const budgetModalMessage = document.getElementById('osBudgetModalMessage');
        const budgetModalDocsList = document.getElementById('osBudgetModalDocsList');
        const budgetModalSubmit = document.getElementById('osBudgetModalSubmit');
        const budgetModal = budgetModalElement && window.bootstrap
            ? window.bootstrap.Modal.getOrCreateInstance(budgetModalElement)
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

        const setSelect2DisabledState = (control, disabled) => {
            if (!control) {
                return;
            }

            control.disabled = Boolean(disabled);
            if (window.jQuery && window.jQuery(control).hasClass('select2-hidden-accessible')) {
                window.jQuery(control).prop('disabled', Boolean(disabled)).trigger('change.select2');
            }
        };

        const buildListContext = () => {
            const statusLabels = labelsMap?.status || {};
            const closedLabels = labelsMap?.status_fechadas || {};
            const closedValue = normalizeString(activeState.status_fechadas);

            if (closedValue !== '') {
                const closedLabel = closedLabels[closedValue] || closedValue;
                if (closedValue === 'fechadas') {
                    return {
                        title: 'Ordens fechadas',
                        subtitle: 'Exibindo apenas OS encerradas por entrega, devolucao sem reparo ou descarte.',
                        counterLabel: 'OS fechadas encontradas',
                    };
                }

                return {
                    title: `Fechadas: ${closedLabel}`,
                    subtitle: 'Exibindo apenas um desfecho operacional da fila encerrada.',
                    counterLabel: 'OS fechadas encontradas',
                };
            }

            if (isAllQueueActive(activeState)) {
                return {
                    title: 'Todas as ordens de servico',
                    subtitle: 'Exibindo OS abertas e fechadas sem o recorte padrao da fila.',
                    counterLabel: 'OS encontradas',
                };
            }

            if ((activeState.status || []).length === 1) {
                const statusCode = activeState.status[0];
                const statusLabel = statusLabels[statusCode] || statusCode;
                return {
                    title: `Ordens abertas: ${statusLabel}`,
                    subtitle: 'Fila aberta refinada por um status detalhado.',
                    counterLabel: 'OS abertas encontradas',
                };
            }

            if ((activeState.status || []).length > 1) {
                return {
                    title: 'Ordens abertas filtradas',
                    subtitle: `${activeState.status.length} status detalhados selecionados na fila aberta.`,
                    counterLabel: 'OS abertas encontradas',
                };
            }

            return {
                title: 'Ordens em aberto',
                subtitle: 'A listagem inicia nas etapas abertas da oficina. Use "Ordens fechadas" para consultar entregas, devolucoes e descartes.',
                counterLabel: 'OS abertas encontradas',
            };
        };

        const updateListContextUi = (filteredCount = null) => {
            const context = buildListContext();
            if (tableTitleText) {
                tableTitleText.textContent = context.title;
            } else if (tableTitle) {
                tableTitle.textContent = context.title;
            }
            if (tableSubtitle) {
                tableSubtitle.textContent = context.subtitle;
            }
            if (resultsCounter && filteredCount !== null) {
                resultsCounter.textContent = `${filteredCount} ${context.counterLabel}`;
            }
        };

        const updateStatusModeUi = () => {
            const closedQueueActive = isClosedQueueActive(activeState);
            [desktopForm, mobileForm].forEach((form) => {
                if (!form) {
                    return;
                }

                const statusControl = getFieldControl(form, 'status');
                const statusBlock = statusControl?.closest('[data-filter-block="status"]');
                setSelect2DisabledState(statusControl, closedQueueActive);
                statusBlock?.classList.toggle('is-disabled', closedQueueActive);
            });
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

        const updateLegacyToggleButtons = () => {
            if (!legacyToggleButtons.length) {
                return;
            }

            legacyToggleButtons.forEach((button) => {
                const buttonValue = normalizeString(button.getAttribute('data-legacy-value'));
                const isActive = buttonValue === normalizeString(activeState.legado);
                button.classList.toggle('active', isActive);
                button.classList.toggle('btn-primary', isActive);
                button.classList.toggle('btn-outline-secondary', !isActive);
                button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            });
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
        let activeStatusModalMeta = null;
        let activeStatusSelectionSource = 'manual';
        let activeDatesOsId = null;
        let activeBudgetOsId = null;

        const updateCsrfFromPayload = (payload) => {
            if (!payload || !payload.csrfHash || !config.csrfTokenKey) {
                return;
            }
            config.csrfTokenValue = payload.csrfHash;
        };

        const formatStatusDateTime = (value) => {
            const raw = String(value || '').trim();
            if (!raw) {
                return '';
            }

            const match = raw.match(/^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2}))?/);
            if (!match) {
                return raw;
            }

            const [, year, month, day, hour = '', minute = ''] = match;
            return `${day}/${month}/${year}${hour && minute ? ` ${hour}:${minute}` : ''}`;
        };

        const formatCurrencyValue = (value) => {
            const numeric = Number.parseFloat(value ?? 0);
            const safeValue = Number.isFinite(numeric) ? numeric : 0;
            return safeValue.toLocaleString('pt-BR', {
                style: 'currency',
                currency: 'BRL',
            });
        };

        const computeDateFromPreset = (entryValue, days) => {
            const rawEntry = String(entryValue || '').trim();
            const safeDays = Number.parseInt(days, 10);
            if (!rawEntry || !Number.isFinite(safeDays)) {
                return '';
            }

            const base = new Date(rawEntry);
            if (Number.isNaN(base.getTime())) {
                return '';
            }

            base.setHours(0, 0, 0, 0);
            base.setDate(base.getDate() + safeDays);

            const year = base.getFullYear();
            const month = String(base.getMonth() + 1).padStart(2, '0');
            const day = String(base.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        };

        const setDatesModalLoading = (isLoading) => {
            if (datesModalSubmit) {
                datesModalSubmit.disabled = Boolean(isLoading);
                datesModalSubmit.innerHTML = isLoading
                    ? '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Salvando...'
                    : '<i class="bi bi-calendar-check me-1"></i>Salvar prazos';
            }

            datesModalEntrada && (datesModalEntrada.disabled = Boolean(isLoading));
            datesModalPreset && (datesModalPreset.disabled = Boolean(isLoading));
            datesModalPrevisao && (datesModalPrevisao.disabled = Boolean(isLoading));
            datesModalEntrega && (datesModalEntrega.disabled = Boolean(isLoading));
        };

        const setBudgetModalSubmitLabel = () => {
            if (!budgetModalSubmit) {
                return;
            }

            const shouldSend = Boolean(budgetModalNotify?.checked) && budgetModalNotify?.dataset.available === '1';
            budgetModalSubmit.innerHTML = shouldSend
                ? '<i class="bi bi-whatsapp me-1"></i>Gerar e enviar'
                : '<i class="bi bi-file-earmark-pdf me-1"></i>Gerar PDF';
        };

        const setBudgetModalLoading = (isLoading) => {
            if (budgetModalSubmit) {
                budgetModalSubmit.disabled = Boolean(isLoading);
                budgetModalSubmit.innerHTML = isLoading
                    ? '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Processando...'
                    : budgetModalSubmit.innerHTML;
            }

            budgetModalNotify && (budgetModalNotify.disabled = Boolean(isLoading) || budgetModalNotify.dataset.available !== '1');
            budgetModalPhone && (budgetModalPhone.disabled = Boolean(isLoading));
            budgetModalMessage && (budgetModalMessage.disabled = Boolean(isLoading));

            if (!isLoading) {
                setBudgetModalSubmitLabel();
            }
        };

        const renderBudgetDocuments = (documents) => {
            if (!budgetModalDocsList) {
                return;
            }

            if (!Array.isArray(documents) || documents.length === 0) {
                budgetModalDocsList.innerHTML = '<p class="text-muted small mb-0">Nenhum orçamento PDF registrado para esta OS.</p>';
                return;
            }

            budgetModalDocsList.innerHTML = documents.map((doc) => {
                const downloadUrl = String(doc?.url || '').trim();
                const version = Number.parseInt(doc?.versao || 1, 10) || 1;
                const createdAt = String(doc?.created_at_label || '').trim() || 'Sem data';

                return [
                    '<div class="os-budget-doc-item">',
                    '<div>',
                    `<strong>Orçamento v${version}</strong>`,
                    `<span>${escapeHtml(createdAt)}</span>`,
                    '</div>',
                    downloadUrl
                        ? `<a href="${escapeHtml(downloadUrl)}" class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener"><i class="bi bi-download"></i></a>`
                        : '<span class="badge bg-light text-dark border">Sem arquivo</span>',
                    '</div>',
                ].join('');
            }).join('');
        };

        const hydrateDatesModal = (payload) => {
            const osMeta = payload?.os || {};
            const datesMeta = payload?.dates || {};
            const entryBaseValue = String(datesMeta?.data_entrada || '').trim();

            datesModalNumero && (datesModalNumero.textContent = osMeta?.numero_os ? `#${osMeta.numero_os}` : '-');
            datesModalBadges && (datesModalBadges.innerHTML = [
                String(osMeta?.statusBadgeHtml || '').trim(),
                String(osMeta?.flowBadgeHtml || '').trim(),
                String(osMeta?.priorityBadgeHtml || '').trim(),
            ].filter(Boolean).join(''));
            datesModalClientName && (datesModalClientName.textContent = String(osMeta?.cliente_nome || '').trim() || '-');
            datesModalEquipmentName && (datesModalEquipmentName.textContent = String(osMeta?.equipamento_nome || '').trim() || '-');
            datesModalElement && (datesModalElement.dataset.entryBase = entryBaseValue);
            datesModalEntrada && (datesModalEntrada.value = String(datesMeta?.data_entrada_label || '-'));
            datesModalPrevisao && (datesModalPrevisao.value = String(datesMeta?.data_previsao || '').trim());
            datesModalEntrega && (datesModalEntrega.value = String(datesMeta?.data_entrega_label || '-'));
            datesModalPreset && (datesModalPreset.value = '');
            datesModalEntradaAtual && (datesModalEntradaAtual.textContent = String(datesMeta?.data_entrada_label || '-'));
            datesModalPrevisaoAtual && (datesModalPrevisaoAtual.textContent = String(datesMeta?.data_previsao_label || '-'));
            datesModalEntregaAtual && (datesModalEntregaAtual.textContent = String(datesMeta?.data_entrega_label || '-'));
            datesModalPrazoDias && (datesModalPrazoDias.textContent = Number.isFinite(Number(datesMeta?.prazo_dias))
                ? `${Number(datesMeta.prazo_dias)} dia(s)`
                : '-');
        };

        const openDatesModal = async (osId) => {
            if (!datesModal || !config.datesMetaUrlBase) {
                return;
            }

            activeDatesOsId = osId;
            datesModalNumero && (datesModalNumero.textContent = '-');
            datesModalBadges && (datesModalBadges.innerHTML = '');
            datesModalClientName && (datesModalClientName.textContent = '-');
            datesModalEquipmentName && (datesModalEquipmentName.textContent = '-');
            datesModalElement && (datesModalElement.dataset.entryBase = '');
            datesModalEntrada && (datesModalEntrada.value = '-');
            datesModalEntrega && (datesModalEntrega.value = '-');
            datesModalEntradaAtual && (datesModalEntradaAtual.textContent = '-');
            datesModalPrevisaoAtual && (datesModalPrevisaoAtual.textContent = '-');
            datesModalEntregaAtual && (datesModalEntregaAtual.textContent = '-');
            datesModalPrazoDias && (datesModalPrazoDias.textContent = '-');
            datesModalForm?.reset();
            setDatesModalLoading(true);
            datesModal.show();

            try {
                const response = await window.fetch(`${config.datesMetaUrlBase}/${osId}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                const payload = await response.json();
                updateCsrfFromPayload(payload);

                if (!response.ok || !payload.ok) {
                    throw new Error(payload.message || 'Nao foi possivel carregar os prazos da OS.');
                }

                hydrateDatesModal(payload);
            } catch (error) {
                datesModal.hide();
                if (window.Swal) {
                    window.Swal.fire({
                        icon: 'error',
                        title: 'Falha ao carregar prazos',
                        text: error.message || 'Nao foi possivel carregar os prazos da OS.',
                    });
                } else {
                    alert(error.message || 'Nao foi possivel carregar os prazos da OS.');
                }
            } finally {
                setDatesModalLoading(false);
            }
        };

        const hydrateBudgetModal = (payload) => {
            const osMeta = payload?.os || {};
            const budgetMeta = payload?.budget || {};
            const tipo = String(osMeta?.equip_tipo_label || osMeta?.equip_tipo || '').trim() || '-';
            const marca = String(osMeta?.equip_marca || '').trim() || '-';
            const modelo = String(osMeta?.equip_modelo || '').trim() || '-';
            const phone = String(budgetMeta?.telefone || osMeta?.cliente_telefone || '').trim();
            const canSend = Boolean(budgetMeta?.can_send_whatsapp);
            const hasPhone = phone !== '';

            budgetModalNumero && (budgetModalNumero.textContent = osMeta?.numero_os ? `#${osMeta.numero_os}` : '-');
            budgetModalBadges && (budgetModalBadges.innerHTML = [
                String(osMeta?.statusBadgeHtml || '').trim(),
                String(osMeta?.flowBadgeHtml || '').trim(),
                String(osMeta?.priorityBadgeHtml || '').trim(),
            ].filter(Boolean).join(''));
            budgetModalClientName && (budgetModalClientName.textContent = String(osMeta?.cliente_nome || '').trim() || '-');
            budgetModalClientPhone && (budgetModalClientPhone.textContent = `Telefone: ${phone || '-'}`);
            budgetModalClientEmail && (budgetModalClientEmail.textContent = `Email: ${String(osMeta?.cliente_email || '').trim() || '-'}`);
            budgetModalEquipmentName && (budgetModalEquipmentName.textContent = String(osMeta?.equipamento_nome || '').trim() || '-');
            budgetModalEquipmentMeta && (budgetModalEquipmentMeta.textContent = `Tipo: ${tipo} | Marca: ${marca} | Modelo: ${modelo}`);
            budgetModalMaoObra && (budgetModalMaoObra.textContent = String(budgetMeta?.valor_mao_obra_label || formatCurrencyValue(budgetMeta?.valor_mao_obra)));
            budgetModalPecas && (budgetModalPecas.textContent = String(budgetMeta?.valor_pecas_label || formatCurrencyValue(budgetMeta?.valor_pecas)));
            budgetModalSubtotal && (budgetModalSubtotal.textContent = String(budgetMeta?.valor_total_label || formatCurrencyValue(budgetMeta?.valor_total)));
            budgetModalValorFinal && (budgetModalValorFinal.textContent = String(budgetMeta?.valor_final_label || formatCurrencyValue(budgetMeta?.valor_final)));
            budgetModalPhone && (budgetModalPhone.value = phone);
            budgetModalMessage && (budgetModalMessage.value = '');

            if (budgetModalNotify) {
                budgetModalNotify.dataset.available = canSend ? '1' : '0';
                budgetModalNotify.checked = canSend && hasPhone;
                budgetModalNotify.disabled = !canSend;
            }

            if (budgetModalNotifyHelp) {
                if (!canSend) {
                    budgetModalNotifyHelp.textContent = 'Seu perfil atual não possui permissão para envio do orçamento ao cliente.';
                    budgetModalNotifyHelp.classList.add('text-danger');
                } else if (!hasPhone) {
                    budgetModalNotifyHelp.textContent = 'Cliente sem telefone cadastrado. Informe um número abaixo se quiser enviar o orçamento agora.';
                    budgetModalNotifyHelp.classList.remove('text-danger');
                } else {
                    budgetModalNotifyHelp.textContent = `Telefone atual para envio: ${phone}`;
                    budgetModalNotifyHelp.classList.remove('text-danger');
                }
            }

            renderBudgetDocuments(budgetMeta?.documents || []);
            setBudgetModalSubmitLabel();
        };

        const openBudgetModal = async (osId) => {
            if (!budgetModal || !config.budgetMetaUrlBase) {
                return;
            }

            activeBudgetOsId = osId;
            budgetModalNumero && (budgetModalNumero.textContent = '-');
            budgetModalBadges && (budgetModalBadges.innerHTML = '');
            budgetModalClientName && (budgetModalClientName.textContent = '-');
            budgetModalClientPhone && (budgetModalClientPhone.textContent = 'Telefone: -');
            budgetModalClientEmail && (budgetModalClientEmail.textContent = 'Email: -');
            budgetModalEquipmentName && (budgetModalEquipmentName.textContent = '-');
            budgetModalEquipmentMeta && (budgetModalEquipmentMeta.textContent = 'Tipo: -');
            budgetModalMaoObra && (budgetModalMaoObra.textContent = 'R$ 0,00');
            budgetModalPecas && (budgetModalPecas.textContent = 'R$ 0,00');
            budgetModalSubtotal && (budgetModalSubtotal.textContent = 'R$ 0,00');
            budgetModalValorFinal && (budgetModalValorFinal.textContent = 'R$ 0,00');
            budgetModalPhone && (budgetModalPhone.value = '');
            budgetModalMessage && (budgetModalMessage.value = '');
            budgetModalDocsList && (budgetModalDocsList.innerHTML = '<p class="text-muted small mb-0">Carregando orçamentos...</p>');
            budgetModalForm?.reset();
            if (budgetModalNotify) {
                budgetModalNotify.dataset.available = '0';
                budgetModalNotify.checked = false;
            }
            setBudgetModalLoading(true);
            budgetModal.show();

            try {
                const response = await window.fetch(`${config.budgetMetaUrlBase}/${osId}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                const payload = await response.json();
                updateCsrfFromPayload(payload);

                if (!response.ok || !payload.ok) {
                    throw new Error(payload.message || 'Não foi possível carregar o orçamento da OS.');
                }

                hydrateBudgetModal(payload);
            } catch (error) {
                budgetModal.hide();
                if (window.Swal) {
                    window.Swal.fire({
                        icon: 'error',
                        title: 'Falha ao carregar orçamento',
                        text: error.message || 'Não foi possível carregar o orçamento da OS.',
                    });
                } else {
                    alert(error.message || 'Não foi possível carregar o orçamento da OS.');
                }
            } finally {
                setBudgetModalLoading(false);
            }
        };

        const resolveTimelineBadge = (state) => {
            const key = String(state || 'upcoming').trim();
            if (key === 'completed') {
                return {
                    label: 'Concluida',
                    className: 'bg-success-subtle text-success-emphasis border border-success-subtle',
                };
            }
            if (key === 'current') {
                return {
                    label: 'Atual',
                    className: 'bg-primary-subtle text-primary-emphasis border border-primary-subtle',
                };
            }
            if (key === 'probable') {
                return {
                    label: 'Provavel',
                    className: 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
                };
            }

            return {
                label: 'Futura',
                className: 'bg-light text-dark border',
            };
        };

        const resolveStatusName = (groupedOptions, code) => {
            const normalizedCode = String(code || '').trim();
            if (!normalizedCode) {
                return '';
            }

            const groups = groupedOptions || {};
            for (const items of Object.values(groups)) {
                if (!Array.isArray(items)) {
                    continue;
                }

                const match = items.find((item) => String(item?.codigo || '').trim() === normalizedCode);
                if (match) {
                    return String(match?.nome || normalizedCode).trim();
                }
            }

            return normalizedCode;
        };

        const setStatusModalSubmitLabel = (label) => {
            if (!statusModalSubmit) {
                return;
            }

            const actionLabel = String(label || '').trim() || 'Salvar status';
            statusModalSubmit.dataset.label = actionLabel;
            statusModalSubmit.innerHTML = `<i class="bi bi-check2-circle me-1"></i>${escapeHtml(actionLabel)}`;
        };

        const setStatusQuickButtonState = (button, enabled, code, name, submitLabel) => {
            if (!button) {
                return;
            }

            button.disabled = !enabled;
            button.dataset.statusCode = enabled ? String(code || '') : '';
            button.dataset.statusName = enabled ? String(name || '') : '';
            button.dataset.submitLabel = enabled ? String(submitLabel || '') : '';
            button.classList.remove('active');
        };

        const setSelectedStatusTarget = (statusCode, statusName, options = {}) => {
            const code = String(statusCode || '').trim();
            const name = String(statusName || code || '').trim();
            const submitLabel = String(options.submitLabel || '').trim() || 'Salvar status';
            const source = String(options.source || 'manual').trim() || 'manual';

            activeStatusSelectionSource = source;

            if (statusModalSelect) {
                statusModalSelect.value = code;
            }

            if (statusModalTargetHint) {
                if (!code) {
                    statusModalTargetHint.textContent = 'Selecione um fluxo para continuar.';
                } else if (source === 'quick-cancel') {
                    statusModalTargetHint.innerHTML = 'Fluxo selecionado: <strong>Cancelado</strong>.';
                } else {
                    statusModalTargetHint.innerHTML = `Fluxo selecionado: <strong>${escapeHtml(name)}</strong>.`;
                }
            }

            setStatusModalSubmitLabel(submitLabel);

            statusModalQuickNext?.classList.toggle('active', code !== '' && code === String(statusModalQuickNext?.dataset.statusCode || ''));
            statusModalQuickCancel?.classList.toggle('active', code !== '' && code === String(statusModalQuickCancel?.dataset.statusCode || ''));
        };

        const getStatusBudgetLoadingMarkup = (message) => `
            <div class="card os-tab-card os-status-modal-budget-card">
                <div class="card-body p-4 text-muted small">${escapeHtml(message || 'Carregando gerenciamento do orcamento...')}</div>
            </div>
        `;

        const setStatusBudgetPanelHtml = (html, emptyMessage = 'Nenhuma informacao de orcamento disponivel para esta OS.') => {
            if (!statusModalBudgetPanel) {
                return;
            }

            const markup = String(html || '').trim();
            statusModalBudgetPanel.innerHTML = markup !== '' ? markup : getStatusBudgetLoadingMarkup(emptyMessage);
        };

        const getStatusModalActiveTabTarget = () => {
            const activeButton = statusModalElement?.querySelector('.os-status-modal-tabs .nav-link.active');
            return String(activeButton?.getAttribute('data-bs-target') || '#osStatusTabQuick').trim() || '#osStatusTabQuick';
        };

        const activateStatusModalTab = (targetSelector) => {
            if (!window.bootstrap) {
                return;
            }

            const normalizedTarget = String(targetSelector || '#osStatusTabQuick').trim() || '#osStatusTabQuick';
            const button = statusModalElement?.querySelector(`.os-status-modal-tabs .nav-link[data-bs-target="${normalizedTarget}"]`)
                || statusModalTabQuickBtn;
            if (!button) {
                return;
            }

            window.bootstrap.Tab.getOrCreateInstance(button).show();
        };

        const parseStatusModalProcedures = (value) => {
            const raw = String(value || '');
            if (!raw.trim()) {
                return [];
            }

            return raw
                .split(/\r?\n/)
                .map((item) => item.trim())
                .filter((item) => item !== '');
        };

        const formatStatusProcedureTimestamp = (date = new Date()) => {
            const dt = date instanceof Date ? date : new Date(date);
            if (Number.isNaN(dt.getTime())) {
                return '';
            }

            const dd = String(dt.getDate()).padStart(2, '0');
            const mm = String(dt.getMonth() + 1).padStart(2, '0');
            const yy = String(dt.getFullYear()).slice(-2);
            const hh = String(dt.getHours()).padStart(2, '0');
            const min = String(dt.getMinutes()).padStart(2, '0');
            return `${dd}/${mm}/${yy}-${hh}:${min}`;
        };

        const getStatusModalTechnicianLabel = () => {
            const label = String(activeStatusModalMeta?.os?.tecnico_nome || '').trim();
            return label || 'Nao atribuido';
        };

        const renderStatusModalProceduresList = (items) => {
            if (!statusModalProcedimentosLista) {
                return;
            }

            statusModalProcedimentosLista.innerHTML = '';
            if (!Array.isArray(items) || items.length === 0) {
                const empty = document.createElement('div');
                empty.className = 'text-muted small';
                empty.textContent = 'Nenhum procedimento inserido.';
                statusModalProcedimentosLista.appendChild(empty);
                return;
            }

            items.forEach((item) => {
                const line = document.createElement('div');
                line.className = 'os-status-modal-procedure-item';
                line.textContent = item;
                statusModalProcedimentosLista.appendChild(line);
            });
        };

        const syncStatusModalProcedures = (items) => {
            if (statusModalProcedimentosInput) {
                statusModalProcedimentosInput.value = Array.isArray(items) ? items.join('\n') : '';
            }
            renderStatusModalProceduresList(Array.isArray(items) ? items : []);
        };

        const insertStatusModalProcedure = () => {
            if (!statusModalProcedimentosInput || !statusModalProcedimentoTextoInput) {
                return;
            }

            const procedimentoBase = String(statusModalProcedimentoTextoInput.value || '').trim();
            if (!procedimentoBase) {
                if (window.Swal) {
                    window.Swal.fire({
                        icon: 'warning',
                        title: 'Procedimento vazio',
                        text: 'Informe o procedimento antes de inserir.',
                    });
                } else {
                    alert('Informe o procedimento antes de inserir.');
                }
                statusModalProcedimentoTextoInput.focus();
                return;
            }

            const tecnicoNome = getStatusModalTechnicianLabel();
            const stamp = formatStatusProcedureTimestamp(new Date());
            const line = `[${procedimentoBase} - ${stamp} - tecnico: ${tecnicoNome}]`;
            const items = parseStatusModalProcedures(statusModalProcedimentosInput.value);
            items.push(line);
            syncStatusModalProcedures(items);

            statusModalProcedimentoTextoInput.value = '';
            statusModalProcedimentoTextoInput.focus();
        };

        const captureStatusModalDraft = () => ({
            activeTabTarget: getStatusModalActiveTabTarget(),
            selectedStatus: String(statusModalSelect?.value || '').trim(),
            selectionSource: String(activeStatusSelectionSource || 'manual').trim() || 'manual',
            submitLabel: String(statusModalSubmit?.dataset.label || 'Salvar status').trim() || 'Salvar status',
            observacao: String(statusModalObservacao?.value || ''),
            comunicarCliente: Boolean(statusModalNotify?.checked),
            procedimentos: String(statusModalProcedimentosInput?.value || ''),
            solucao: String(statusModalSolucaoInput?.value || ''),
            diagnostico: String(statusModalDiagnosticoInput?.value || ''),
        });

        const restoreStatusModalDraft = (draft) => {
            if (!draft || typeof draft !== 'object') {
                return;
            }

            activateStatusModalTab(String(draft.activeTabTarget || '#osStatusTabQuick'));

            if (statusModalObservacao) {
                statusModalObservacao.value = String(draft.observacao || '');
            }
            syncStatusModalProcedures(parseStatusModalProcedures(draft.procedimentos || ''));
            if (statusModalSolucaoInput) {
                statusModalSolucaoInput.value = String(draft.solucao || '');
            }
            if (statusModalDiagnosticoInput) {
                statusModalDiagnosticoInput.value = String(draft.diagnostico || '');
            }
            if (statusModalNotify && statusModalNotify.dataset.available === '1') {
                statusModalNotify.checked = Boolean(draft.comunicarCliente);
            }

            const selectedStatus = String(draft.selectedStatus || '').trim();
            if (!selectedStatus || !statusModalSelect) {
                return;
            }

            const hasOption = Array.from(statusModalSelect.options || []).some((option) => String(option.value || '').trim() === selectedStatus);
            if (!hasOption) {
                return;
            }

            const selectedName = resolveStatusName(activeStatusModalMeta?.options || {}, selectedStatus);
            setSelectedStatusTarget(selectedStatus, selectedName || selectedStatus, {
                source: String(draft.selectionSource || 'manual').trim() || 'manual',
                submitLabel: String(draft.submitLabel || 'Salvar status').trim() || 'Salvar status',
            });
        };

        const refreshStatusModalContext = async (options = {}) => {
            if (!activeStatusOsId) {
                return null;
            }

            const preserveDraft = Boolean(options.preserveDraft);
            const draft = preserveDraft ? captureStatusModalDraft() : null;
            const response = await window.fetch(`${config.statusMetaUrlBase}/${activeStatusOsId}`, {
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

            hydrateStatusModal(payload);
            if (draft) {
                restoreStatusModalDraft(draft);
            }

            return payload;
        };

        const renderStatusTimeline = (timeline) => {
            if (!statusModalTimeline) {
                return;
            }

            if (!Array.isArray(timeline) || timeline.length === 0) {
                statusModalTimeline.innerHTML = '<p class="text-muted small mb-0">Fluxo visual indisponivel para esta OS.</p>';
                return;
            }

            const html = timeline.map((stage) => {
                const stageState = String(stage?.state || 'upcoming').trim() || 'upcoming';
                const badgeMeta = resolveTimelineBadge(stageState);
                const label = escapeHtml(stage?.label || 'Etapa');
                const currentStatusName = String(stage?.current_status_name || '').trim();
                const lastStatusName = String(stage?.last_status_name || '').trim();
                const nextStatusNames = Array.isArray(stage?.next_status_names) ? stage.next_status_names.filter(Boolean) : [];
                const lastEventAt = formatStatusDateTime(stage?.last_event_at || '');
                const lastUserName = String(stage?.last_user_name || '').trim();

                let description = 'Etapa futura do fluxo da ordem de servico.';
                if (stageState === 'current' && currentStatusName) {
                    description = `Etapa atual: ${escapeHtml(currentStatusName)}.`;
                } else if (stageState === 'completed' && lastStatusName) {
                    description = `Passou por ${escapeHtml(lastStatusName)}.`;
                } else if (stageState === 'probable' && nextStatusNames.length > 0) {
                    description = `Proximos movimentos provaveis: ${escapeHtml(nextStatusNames.join(', '))}.`;
                }

                const metaParts = [];
                if (lastEventAt) {
                    metaParts.push(lastEventAt);
                }
                if (lastUserName) {
                    metaParts.push(`por ${escapeHtml(lastUserName)}`);
                }

                return [
                    `<div class="os-workflow-step is-${escapeHtml(stageState)}">`,
                    '<div class="os-workflow-step-marker"></div>',
                    '<div class="os-workflow-step-body">',
                    '<div class="os-workflow-step-top">',
                    `<div class="os-workflow-step-title">${label}</div>`,
                    `<span class="badge ${badgeMeta.className}">${badgeMeta.label}</span>`,
                    '</div>',
                    `<div class="os-workflow-step-text">${description}</div>`,
                    (metaParts.length > 0 ? `<div class="os-workflow-step-meta">${metaParts.join(' ')}</div>` : ''),
                    '</div>',
                    '</div>',
                ].join('');
            }).join('');

            statusModalTimeline.innerHTML = `<div class="os-workflow-timeline">${html}</div>`;
        };

        const renderStatusHistory = (history) => {
            if (!statusModalHistoryWrap || !statusModalHistoryList) {
                return;
            }

            if (!Array.isArray(history) || history.length === 0) {
                statusModalHistoryWrap.classList.add('is-empty');
                statusModalHistoryList.innerHTML = '<p class="text-muted small mb-0">Sem historico recente para esta OS.</p>';
                return;
            }

            statusModalHistoryWrap.classList.remove('is-empty');
            statusModalHistoryList.innerHTML = history.map((item) => {
                const statusName = escapeHtml(String(item?.status_novo || '-').replace(/_/g, ' ').replace(/\b\w/g, (letter) => letter.toUpperCase()));
                const createdAt = formatStatusDateTime(item?.created_at || '');
                const userName = String(item?.usuario_nome || '').trim();

                return [
                    '<div class="os-workflow-history-item">',
                    `<strong>${statusName}</strong>`,
                    (createdAt ? `<span>${escapeHtml(createdAt)}</span>` : ''),
                    (userName ? `<small>por ${escapeHtml(userName)}</small>` : ''),
                    '</div>',
                ].join('');
            }).join('');
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
            statusModalQuickNext && (statusModalQuickNext.disabled = Boolean(isLoading) || !statusModalQuickNext.dataset.statusCode);
            statusModalQuickCancel && (statusModalQuickCancel.disabled = Boolean(isLoading) || !statusModalQuickCancel.dataset.statusCode);
            statusModalNotify && (statusModalNotify.disabled = Boolean(isLoading) || statusModalNotify.dataset.available !== '1');
            statusModalProcedimentoTextoInput && (statusModalProcedimentoTextoInput.disabled = Boolean(isLoading));
            statusModalInserirProcedimento && (statusModalInserirProcedimento.disabled = Boolean(isLoading));
            statusModalSolucaoInput && (statusModalSolucaoInput.disabled = Boolean(isLoading));
            statusModalDiagnosticoInput && (statusModalDiagnosticoInput.disabled = Boolean(isLoading));

            statusModalSubmit.innerHTML = isLoading
                ? '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Salvando...'
                : `<i class="bi bi-check2-circle me-1"></i>${escapeHtml(statusModalSubmit.dataset.label || 'Salvar status')}`;
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

        const hydrateStatusModal = (payload) => {
            activeStatusModalMeta = payload || null;
            const osMeta = payload?.os || {};
            const groupedOptions = payload?.options || {};
            const primaryNextStatus = payload?.primaryNextStatus || null;
            const hasClientPhone = Boolean(payload?.hasClientPhone);
            const phoneLabel = String(osMeta?.cliente_telefone || '').trim();
            const currentStatusName = String(osMeta?.status_nome || resolveStatusName(groupedOptions, osMeta?.status || '') || '').trim() || '-';

            statusModalNumero.textContent = osMeta?.numero_os
                ? `#${osMeta.numero_os}`
                : '-';

            if (statusModalClientName) {
                statusModalClientName.textContent = String(osMeta?.cliente_nome || '').trim() || '-';
            }
            if (statusModalClientPhone) {
                statusModalClientPhone.textContent = `Telefone: ${String(osMeta?.cliente_telefone || '').trim() || '-'}`;
            }
            if (statusModalClientEmail) {
                statusModalClientEmail.textContent = `Email: ${String(osMeta?.cliente_email || '').trim() || '-'}`;
            }
            if (statusModalEquipmentName) {
                statusModalEquipmentName.textContent = String(osMeta?.equipamento_nome || '').trim() || '-';
            }
            if (statusModalEquipmentMeta) {
                const tipo = String(osMeta?.equip_tipo_label || osMeta?.equip_tipo || '').trim() || '-';
                const marca = String(osMeta?.equip_marca || '').trim() || '-';
                const modelo = String(osMeta?.equip_modelo || '').trim() || '-';
                statusModalEquipmentMeta.textContent = `Tipo: ${tipo} | Marca: ${marca} | Modelo: ${modelo}`;
            }
            if (statusModalEquipmentSerial) {
                statusModalEquipmentSerial.textContent = `N de serie: ${String(osMeta?.equip_serie || '').trim() || '-'}`;
            }

            if (statusModalCurrentBadges) {
                statusModalCurrentBadges.innerHTML = [
                    String(osMeta?.statusBadgeHtml || '').trim(),
                    String(osMeta?.flowBadgeHtml || '').trim(),
                    String(osMeta?.priorityBadgeHtml || '').trim(),
                ].filter(Boolean).join('');
            }

            populateStatusOptions(groupedOptions, osMeta?.status || '');

            if (statusModalCurrentStatusHint) {
                statusModalCurrentStatusHint.innerHTML = `Status atual da OS: <strong>${escapeHtml(currentStatusName)}</strong>.`;
            }
            if (statusModalPrimaryHint) {
                statusModalPrimaryHint.innerHTML = primaryNextStatus?.nome
                    ? `Fluxo normal sugerido: <strong>${escapeHtml(primaryNextStatus.nome)}</strong>.`
                    : 'Fluxo normal sugerido: <strong>indisponivel no momento</strong>.';
            }

            syncStatusModalProcedures(parseStatusModalProcedures(osMeta?.procedimentos_executados || ''));
            if (statusModalSolucaoInput) {
                statusModalSolucaoInput.value = String(osMeta?.solucao_aplicada || '');
            }
            if (statusModalDiagnosticoInput) {
                statusModalDiagnosticoInput.value = String(osMeta?.diagnostico_tecnico || '');
            }
            setStatusBudgetPanelHtml(payload?.budgetPanelHtml || '');

            setStatusQuickButtonState(
                statusModalQuickNext,
                Boolean(primaryNextStatus?.codigo),
                primaryNextStatus?.codigo || '',
                primaryNextStatus?.nome || '',
                'Avancar etapa'
            );

            const canCancel = Object.values(groupedOptions).some((items) => Array.isArray(items) && items.some((item) => String(item?.codigo || '').trim() === 'cancelado'));
            const cancelStatusName = resolveStatusName(groupedOptions, 'cancelado') || 'Cancelado';
            setStatusQuickButtonState(
                statusModalQuickCancel,
                canCancel,
                canCancel ? 'cancelado' : '',
                cancelStatusName,
                'Cancelar OS'
            );

            if (statusModalNotify) {
                statusModalNotify.dataset.available = hasClientPhone ? '1' : '0';
                statusModalNotify.checked = hasClientPhone;
                statusModalNotify.disabled = !hasClientPhone;
            }

            if (statusModalNotifyHelp) {
                statusModalNotifyHelp.textContent = hasClientPhone
                    ? `Telefone atual para comunicacao: ${phoneLabel || 'nao informado'}.`
                    : 'Cliente sem telefone cadastrado para comunicacao automatica.';
                statusModalNotifyHelp.classList.toggle('text-danger', !hasClientPhone);
            }

            renderStatusTimeline(payload?.workflowTimeline || []);
            renderStatusHistory(payload?.workflowRecentHistory || []);

            if (primaryNextStatus?.codigo) {
                setSelectedStatusTarget(primaryNextStatus.codigo, primaryNextStatus.nome || primaryNextStatus.codigo, {
                    source: 'quick-next',
                    submitLabel: 'Avancar etapa',
                });
            } else {
                setSelectedStatusTarget('', '', {
                    source: 'manual',
                    submitLabel: 'Salvar status',
                });
            }
        };

        const openStatusModal = async (osId) => {
            if (!statusModal || !statusModalSelect) {
                return;
            }

            activeStatusOsId = osId;
            activeStatusModalMeta = null;
            activeStatusSelectionSource = 'manual';
            setStatusModalSubmitLabel('Salvar status');
            setStatusModalLoading(true);
            statusModalNumero.textContent = '-';
            statusModalObservacao.value = '';
            statusModalSelect.innerHTML = '<option value="">Carregando...</option>';
            statusModalClientName && (statusModalClientName.textContent = '-');
            statusModalClientPhone && (statusModalClientPhone.textContent = 'Telefone: -');
            statusModalClientEmail && (statusModalClientEmail.textContent = 'Email: -');
            statusModalEquipmentName && (statusModalEquipmentName.textContent = '-');
            statusModalEquipmentMeta && (statusModalEquipmentMeta.textContent = 'Tipo: -');
            statusModalEquipmentSerial && (statusModalEquipmentSerial.textContent = 'N de serie: -');
            statusModalCurrentBadges && (statusModalCurrentBadges.innerHTML = '');
            statusModalCurrentStatusHint && (statusModalCurrentStatusHint.textContent = 'Status atual da OS: aguardando contexto.');
            statusModalPrimaryHint && (statusModalPrimaryHint.textContent = 'Fluxo normal sugerido: aguardando contexto.');
            statusModalTargetHint && (statusModalTargetHint.textContent = 'Selecione um fluxo para continuar.');
            statusModalTimeline && (statusModalTimeline.innerHTML = '<p class="text-muted small mb-0">Carregando fluxo visual...</p>');
            statusModalHistoryList && (statusModalHistoryList.innerHTML = '<p class="text-muted small mb-0">Carregando historico recente...</p>');
            statusModalHistoryWrap?.classList.remove('is-empty');
            syncStatusModalProcedures([]);
            statusModalProcedimentoTextoInput && (statusModalProcedimentoTextoInput.value = '');
            statusModalSolucaoInput && (statusModalSolucaoInput.value = '');
            statusModalDiagnosticoInput && (statusModalDiagnosticoInput.value = '');
            setStatusBudgetPanelHtml('', 'Carregando gerenciamento do orcamento...');
            activateStatusModalTab('#osStatusTabQuick');
            if (statusModalNotify) {
                statusModalNotify.checked = false;
                statusModalNotify.disabled = true;
                statusModalNotify.dataset.available = '0';
            }
            if (statusModalNotifyHelp) {
                statusModalNotifyHelp.textContent = 'Verificando disponibilidade de comunicacao com o cliente...';
                statusModalNotifyHelp.classList.remove('text-danger');
            }
            setStatusQuickButtonState(statusModalQuickNext, false, '', '', '');
            setStatusQuickButtonState(statusModalQuickCancel, false, '', '', '');
            statusModal.show();

            try {
                await refreshStatusModalContext();
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
            activeState = resolveStatusModeState(nextState, options.sourceKey || '');
            syncForms();
            saveStorageState(activeState);
            writeUrlState(activeState);
            setupActiveChips(activeState, labelsMap, chipsWrap, chipsContainer, clearAllBtn);
            updateMobileFilterBadge();
            updateLegacyToggleButtons();
            updateStatusModeUi();
            updateListContextUi();

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
            payload.status_fechadas = activeState.status_fechadas;
            payload.status_scope = activeState.status_scope;
            payload.legado = activeState.legado;
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
            updateListContextUi(filtered);
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

            const datesTrigger = event.target.closest('[data-os-dates-action]');
            if (datesTrigger) {
                event.preventDefault();
                event.stopPropagation();

                const osId = Number(datesTrigger.getAttribute('data-os-id') || '0');
                if (!Number.isFinite(osId) || osId <= 0) {
                    return;
                }

                openDatesModal(osId);
                return;
            }

            const budgetTrigger = event.target.closest('[data-os-budget-action]');
            if (budgetTrigger) {
                event.preventDefault();
                event.stopPropagation();

                const osId = Number(budgetTrigger.getAttribute('data-os-id') || '0');
                if (!Number.isFinite(osId) || osId <= 0) {
                    return;
                }

                openBudgetModal(osId);
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

        datesModalPreset?.addEventListener('change', function () {
            const previewValue = computeDateFromPreset(datesModalElement?.dataset.entryBase || '', this.value);
            if (previewValue && datesModalPrevisao) {
                datesModalPrevisao.value = previewValue;
            }
        });

        budgetModalNotify?.addEventListener('change', function () {
            setBudgetModalSubmitLabel();
        });

        statusModalInserirProcedimento?.addEventListener('click', insertStatusModalProcedure);

        statusModalProcedimentoTextoInput?.addEventListener('keydown', function (event) {
            if (event.key !== 'Enter') {
                return;
            }

            event.preventDefault();
            insertStatusModalProcedure();
        });

        statusModalProcedimentosInput?.addEventListener('input', function () {
            renderStatusModalProceduresList(parseStatusModalProcedures(this.value));
        });

        statusModalQuickNext?.addEventListener('click', function () {
            const code = String(this.dataset.statusCode || '').trim();
            const name = String(this.dataset.statusName || '').trim() || resolveStatusName(activeStatusModalMeta?.options || {}, code);
            if (!code) {
                return;
            }

            setSelectedStatusTarget(code, name, {
                source: 'quick-next',
                submitLabel: String(this.dataset.submitLabel || 'Avancar etapa'),
            });
        });

        statusModalQuickCancel?.addEventListener('click', function () {
            const code = String(this.dataset.statusCode || '').trim();
            const name = String(this.dataset.statusName || '').trim() || 'Cancelado';
            if (!code) {
                return;
            }

            setSelectedStatusTarget(code, name, {
                source: 'quick-cancel',
                submitLabel: String(this.dataset.submitLabel || 'Cancelar OS'),
            });
        });

        statusModalSelect?.addEventListener('change', function () {
            const code = String(this.value || '').trim();
            const name = resolveStatusName(activeStatusModalMeta?.options || {}, code);
            setSelectedStatusTarget(code, name, {
                source: 'manual',
                submitLabel: 'Salvar status',
            });
        });

        datesModalForm?.addEventListener('submit', async function (event) {
            event.preventDefault();

            if (!activeDatesOsId) {
                return;
            }

            const formData = new window.FormData();
            formData.append('data_previsao', datesModalPrevisao?.value || '');

            if (config.csrfTokenKey && config.csrfTokenValue) {
                formData.append(config.csrfTokenKey, config.csrfTokenValue);
            }

            setDatesModalLoading(true);

            try {
                const response = await window.fetch(`${config.datesUpdateUrlBase}/${activeDatesOsId}`, {
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
                    throw new Error(payload.message || 'Nao foi possivel atualizar os prazos.');
                }

                datesModal.hide();
                if (window.Swal) {
                    await window.Swal.fire({
                        icon: 'success',
                        title: 'Prazos atualizados',
                        text: payload.message || 'Os prazos da OS foram atualizados com sucesso.',
                    });
                }

                window.osListController.reload(true);
            } catch (error) {
                if (window.Swal) {
                    window.Swal.fire({
                        icon: 'error',
                        title: 'Falha ao atualizar prazos',
                        text: error.message || 'Nao foi possivel atualizar os prazos da OS.',
                    });
                } else {
                    alert(error.message || 'Nao foi possivel atualizar os prazos da OS.');
                }
            } finally {
                setDatesModalLoading(false);
            }
        });

        budgetModalForm?.addEventListener('submit', async function (event) {
            event.preventDefault();

            if (!activeBudgetOsId) {
                return;
            }

            const formData = new window.FormData();
            formData.append('telefone', budgetModalPhone?.value || '');
            formData.append('mensagem_manual', budgetModalMessage?.value || '');
            if (budgetModalNotify?.checked && budgetModalNotify.dataset.available === '1') {
                formData.append('enviar_cliente', '1');
            }

            if (config.csrfTokenKey && config.csrfTokenValue) {
                formData.append(config.csrfTokenKey, config.csrfTokenValue);
            }

            setBudgetModalLoading(true);

            try {
                const response = await window.fetch(`${config.budgetActionUrlBase}/${activeBudgetOsId}`, {
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
                    throw new Error(payload.message || 'Não foi possível gerar o orçamento da OS.');
                }

                budgetModal.hide();
                if (window.Swal) {
                    await window.Swal.fire({
                        icon: payload.warning ? 'warning' : 'success',
                        title: payload.warning ? 'Orçamento gerado com ressalvas' : 'Orçamento pronto',
                        text: payload.warning
                            ? `${payload.message || 'O PDF foi gerado.'} ${payload.warning}`
                            : (payload.message || 'O PDF do orçamento foi gerado com sucesso.'),
                    });
                }

                window.osListController.reload(true);
            } catch (error) {
                if (window.Swal) {
                    window.Swal.fire({
                        icon: 'error',
                        title: 'Falha ao gerar orçamento',
                        text: error.message || 'Não foi possível gerar o orçamento da OS.',
                    });
                } else {
                    alert(error.message || 'Não foi possível gerar o orçamento da OS.');
                }
            } finally {
                setBudgetModalLoading(false);
            }
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
            formData.append('procedimentos_executados', statusModalProcedimentosInput?.value || '');
            formData.append('solucao_aplicada', statusModalSolucaoInput?.value || '');
            formData.append('diagnostico_tecnico', statusModalDiagnosticoInput?.value || '');
            formData.append('controla_comunicacao_cliente', '1');
            if (statusModalNotify?.checked && statusModalNotify.dataset.available === '1') {
                formData.append('comunicar_cliente', '1');
            }
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
                        text: payload.warning
                            ? `${payload.message || 'O status da OS foi atualizado com sucesso.'} ${payload.warning}`
                            : (payload.message || 'O status da OS foi atualizado com sucesso.'),
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
            activeStatusModalMeta = null;
            activeStatusSelectionSource = 'manual';
            if (statusModalForm) {
                statusModalForm.reset();
            }
            if (statusModalSelect) {
                statusModalSelect.innerHTML = '<option value="">Selecione um status</option>';
            }
            statusModalClientName && (statusModalClientName.textContent = '-');
            statusModalClientPhone && (statusModalClientPhone.textContent = 'Telefone: -');
            statusModalClientEmail && (statusModalClientEmail.textContent = 'Email: -');
            statusModalEquipmentName && (statusModalEquipmentName.textContent = '-');
            statusModalEquipmentMeta && (statusModalEquipmentMeta.textContent = 'Tipo: -');
            statusModalEquipmentSerial && (statusModalEquipmentSerial.textContent = 'N de serie: -');
            statusModalCurrentBadges && (statusModalCurrentBadges.innerHTML = '');
            statusModalCurrentStatusHint && (statusModalCurrentStatusHint.textContent = 'Status atual da OS: aguardando contexto.');
            statusModalPrimaryHint && (statusModalPrimaryHint.textContent = 'Fluxo normal sugerido: aguardando contexto.');
            statusModalTargetHint && (statusModalTargetHint.textContent = 'Selecione um fluxo para continuar.');
            statusModalTimeline && (statusModalTimeline.innerHTML = '<p class="text-muted small mb-0">Fluxo visual indisponivel para esta OS.</p>');
            statusModalHistoryList && (statusModalHistoryList.innerHTML = '<p class="text-muted small mb-0">Sem historico recente para esta OS.</p>');
            statusModalHistoryWrap?.classList.remove('is-empty');
            syncStatusModalProcedures([]);
            statusModalProcedimentoTextoInput && (statusModalProcedimentoTextoInput.value = '');
            statusModalSolucaoInput && (statusModalSolucaoInput.value = '');
            statusModalDiagnosticoInput && (statusModalDiagnosticoInput.value = '');
            setStatusBudgetPanelHtml('', 'Gerenciamento do orcamento sera exibido aqui.');
            activateStatusModalTab('#osStatusTabQuick');
            setStatusQuickButtonState(statusModalQuickNext, false, '', '', '');
            setStatusQuickButtonState(statusModalQuickCancel, false, '', '', '');
            if (statusModalNotify) {
                statusModalNotify.checked = false;
                statusModalNotify.disabled = true;
                statusModalNotify.dataset.available = '0';
            }
            if (statusModalNotifyHelp) {
                statusModalNotifyHelp.textContent = 'O cliente sera comunicado apenas se voce mantiver esta opcao ativa.';
                statusModalNotifyHelp.classList.remove('text-danger');
            }
            setStatusModalSubmitLabel('Salvar status');
            setStatusModalLoading(false);
        });

        datesModalElement?.addEventListener('hidden.bs.modal', function () {
            activeDatesOsId = null;
            datesModalElement.dataset.entryBase = '';
            datesModalForm?.reset();
            datesModalNumero && (datesModalNumero.textContent = '-');
            datesModalBadges && (datesModalBadges.innerHTML = '');
            datesModalClientName && (datesModalClientName.textContent = '-');
            datesModalEquipmentName && (datesModalEquipmentName.textContent = '-');
            datesModalEntrada && (datesModalEntrada.value = '-');
            datesModalEntrega && (datesModalEntrega.value = '-');
            datesModalEntradaAtual && (datesModalEntradaAtual.textContent = '-');
            datesModalPrevisaoAtual && (datesModalPrevisaoAtual.textContent = '-');
            datesModalEntregaAtual && (datesModalEntregaAtual.textContent = '-');
            datesModalPrazoDias && (datesModalPrazoDias.textContent = '-');
            setDatesModalLoading(false);
        });

        budgetModalElement?.addEventListener('hidden.bs.modal', function () {
            activeBudgetOsId = null;
            budgetModalForm?.reset();
            budgetModalNumero && (budgetModalNumero.textContent = '-');
            budgetModalBadges && (budgetModalBadges.innerHTML = '');
            budgetModalClientName && (budgetModalClientName.textContent = '-');
            budgetModalClientPhone && (budgetModalClientPhone.textContent = 'Telefone: -');
            budgetModalClientEmail && (budgetModalClientEmail.textContent = 'Email: -');
            budgetModalEquipmentName && (budgetModalEquipmentName.textContent = '-');
            budgetModalEquipmentMeta && (budgetModalEquipmentMeta.textContent = 'Tipo: -');
            budgetModalMaoObra && (budgetModalMaoObra.textContent = 'R$ 0,00');
            budgetModalPecas && (budgetModalPecas.textContent = 'R$ 0,00');
            budgetModalSubtotal && (budgetModalSubtotal.textContent = 'R$ 0,00');
            budgetModalValorFinal && (budgetModalValorFinal.textContent = 'R$ 0,00');
            budgetModalDocsList && (budgetModalDocsList.innerHTML = '<p class="text-muted small mb-0">Nenhum orçamento PDF registrado para esta OS.</p>');
            if (budgetModalNotify) {
                budgetModalNotify.checked = false;
                budgetModalNotify.disabled = true;
                budgetModalNotify.dataset.available = '0';
            }
            if (budgetModalNotifyHelp) {
                budgetModalNotifyHelp.textContent = 'O envio utiliza o telefone cadastrado do cliente.';
                budgetModalNotifyHelp.classList.remove('text-danger');
            }
            setBudgetModalLoading(false);
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
            if (payload.type === 'os:list-refresh') {
                window.osListController.reload(true);
                return;
            }

            if (payload.type !== 'os:orcamento-updated') {
                return;
            }

            window.osListController.reload(true);

            const detailsModalElement = document.getElementById('osDetailsModal');
            const detailsModal = detailsModalElement && window.bootstrap
                ? window.bootstrap.Modal.getInstance(detailsModalElement)
                : null;

            const updatedOsId = Number(payload.osId || 0);
            if (!activeStatusOsId || updatedOsId <= 0 || updatedOsId !== Number(activeStatusOsId)) {
                detailsModal?.hide();
                return;
            }

            refreshStatusModalContext({ preserveDraft: true })
                .then(() => {
                    detailsModal?.hide();
                    if (window.Swal && payload.message) {
                        window.Swal.fire({
                            icon: 'success',
                            title: 'Orcamento atualizado',
                            text: String(payload.message || 'O resumo do orcamento foi sincronizado na OS.'),
                            timer: 1800,
                            showConfirmButton: false,
                        });
                    }
                })
                .catch((error) => {
                    console.error('[OS status modal] Falha ao sincronizar o orcamento apos edicao.', error);
                    detailsModal?.hide();
                    if (window.Swal) {
                        window.Swal.fire({
                            icon: 'warning',
                            title: 'Orcamento salvo com ressalvas',
                            text: error?.message || 'O orcamento foi salvo, mas o resumo da OS nao foi atualizado automaticamente.',
                        });
                    }
                });
        });

        const handleRealtimeBudgetNotification = debounce(function (event) {
            const detail = event?.detail || {};
            const eventType = String(detail.tipo_evento || detail.notification?.tipo_evento || '').trim();
            if (eventType !== 'orcamento.public_status_changed') {
                return;
            }

            const payload = detail.payload && typeof detail.payload === 'object'
                ? detail.payload
                : (detail.notification?.payload && typeof detail.notification.payload === 'object' ? detail.notification.payload : {});
            const osId = Number(payload.os_id || 0);
            if (osId <= 0) {
                return;
            }

            if (window.osListController && typeof window.osListController.reload === 'function') {
                window.osListController.reload(true);
            }

            if (activeStatusOsId && Number(activeStatusOsId) === osId) {
                refreshStatusModalContext({ preserveDraft: true }).catch(function (error) {
                    console.error('[OS status modal] Falha ao reidratar contexto apos notificacao publica de orcamento.', error);
                });
            }
        }, 300);

        window.addEventListener('erp:notification', handleRealtimeBudgetNotification);

        const applyFromForm = (form, options = {}) => {
            if (!form) {
                return;
            }
            const sourceKey = options.sourceKey || '';
            const nextState = collectFormState(form);
            applyState(nextState, {
                ...options,
                sourceKey: sourceKey,
            });
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
                    applyState(normalizeState({}), {
                        reload: true,
                        closeMobile: Boolean(isMobileForm),
                        sourceKey: 'clear',
                    });
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

                const sourceKey = normalizeString(target.getAttribute('data-filter-field'));
                applyFromForm(form, { reload: true, closeMobile: false, sourceKey: sourceKey });
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
            applyState(normalizeState({}), {
                reload: true,
                closeMobile: false,
                sourceKey: 'clear',
            });
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
        updateLegacyToggleButtons();
        updateStatusModeUi();
        updateListContextUi();
        writeUrlState(activeState);
        saveStorageState(activeState);

        legacyToggleButtons.forEach((button) => {
            button.addEventListener('click', function () {
                const nextState = normalizeState({
                    ...activeState,
                    legado: normalizeString(this.getAttribute('data-legacy-value')),
                });
                applyState(nextState, { reload: true });
            });
        });
    });
})();

