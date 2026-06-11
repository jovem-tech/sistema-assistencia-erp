function confirmarReativacao(modulo, id, csrfName = '', csrfHash = '') {
    const titulos = {
        'os': 'Ordem de Servico',
        'equipamentos': 'Equipamento',
        'estoque': 'Peca/Item'
    };

    if (modulo !== 'equipamentos' || Number(id) <= 0) {
        const nome = titulos[modulo] || 'registro';

        if (window.Swal && typeof window.Swal.fire === 'function') {
            window.Swal.fire({
                icon: 'info',
                title: 'Fluxo dedicado',
                text: `A funcionalidade de reativacao para ${nome} ainda usa um fluxo especifico neste modulo.`,
                confirmButtonText: 'Ok'
            });
        } else {
            window.alert(`A funcionalidade de reativacao para ${nome} ainda usa um fluxo especifico neste modulo.`);
        }

        return;
    }

    const csrfState = ensureEquipmentClosureCsrfState(csrfName, csrfHash);
    const baseUrl = document.querySelector('meta[name="base-url"]')?.content || window.location.origin + '/';
    const reasonOptions = getEquipmentReactivationReasonOptions();

    if (!(window.Swal && typeof window.Swal.fire === 'function')) {
        const confirmed = window.confirm('Deseja reativar este equipamento? Ele voltara a receber novas OS e novos vinculos operacionais.');
        if (confirmed) {
            window.alert('SweetAlert2 nao esta disponivel neste contexto.');
        }
        return;
    }

    const optionsHtml = buildEquipmentLifecycleReasonOptionsHtml(reasonOptions);

    window.Swal.fire({
        icon: 'question',
        title: 'Voltar à operação?',
        html: `
            <p class="text-start mb-3">
                O equipamento voltara a aceitar novas OS e novos vinculos operacionais.
            </p>
            <div class="text-start">
                <label for="swal-equipment-reactivation-reason" class="form-label fw-semibold">Motivo da reativacao *</label>
                <select id="swal-equipment-reactivation-reason" class="form-select">
                    <option value="">Selecione...</option>
                    ${optionsHtml}
                </select>
            </div>
            <div class="text-start mt-3">
                <label for="swal-equipment-reactivation-note" class="form-label fw-semibold">Observacao interna</label>
                <textarea id="swal-equipment-reactivation-note" class="form-control" rows="3" placeholder="Detalhes complementares para o historico de reativacao do equipamento."></textarea>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Voltar à operação',
        cancelButtonText: 'Cancelar',
        focusConfirm: false,
        showLoaderOnConfirm: true,
        allowOutsideClick: () => !window.Swal.isLoading(),
        preConfirm: async () => {
            const motivo = document.getElementById('swal-equipment-reactivation-reason')?.value || '';
            const observacao = document.getElementById('swal-equipment-reactivation-note')?.value || '';

            if (!motivo) {
                window.Swal.showValidationMessage('Selecione o motivo da reativacao.');
                return false;
            }

            const payload = new URLSearchParams();
            payload.set('motivo_reativacao', motivo);
            payload.set('observacao_reativacao', observacao);
            if (csrfState.name && csrfState.hash) {
                payload.set(csrfState.name, csrfState.hash);
            }

            const response = await fetch(`${baseUrl}equipamentos/reativar/${id}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: payload.toString()
            });

            const data = await response.json().catch(() => ({}));
            if (data?.csrfHash) {
                ensureEquipmentClosureCsrfState(csrfState.name, data.csrfHash);
            }

            if (!response.ok || data?.status !== 'success') {
                window.Swal.showValidationMessage(data?.message || 'Nao foi possivel reativar o equipamento agora.');
                return false;
            }

            return data;
        }
    }).then((result) => {
        if (!result?.isConfirmed || !result.value?.equipamento) {
            return;
        }

        applyEquipmentReactivationUI(result.value.equipamento);
        if (Object.prototype.hasOwnProperty.call(result.value, 'lifecycle_history_html')) {
            updateEquipmentLifecycleHistoryUI(
                result.value.equipamento?.id || id,
                result.value.lifecycle_history_html || '',
                result.value.lifecycle_history_count
            );
        }

        window.Swal.fire({
            icon: 'success',
            title: 'Equipamento reativado',
            text: result.value.message || 'O equipamento voltou a aceitar novas OS.',
            confirmButtonText: 'Ok'
        });
    });
}

window.confirmarReativacao = confirmarReativacao;

/**
 * Sistema de Assistência Técnica - Main Scripts
 */

(function (window) {
    const hasSwal = function () {
        return Boolean(window.Swal && typeof window.Swal.fire === 'function');
    };

    const buildMessage = function (options) {
        return [options.title || '', options.text || ''].filter(Boolean).join('\n\n');
    };

    const hasAriaHiddenAncestor = function (element) {
        let current = element;
        while (current && current !== window.document.body) {
            if (current.getAttribute && current.getAttribute('aria-hidden') === 'true') {
                return true;
            }
            current = current.parentElement;
        }
        return false;
    };

    const findSafeFocusTarget = function () {
        const modal = window.document.querySelector('.modal.show');
        if (!modal) {
            return null;
        }

        return modal.querySelector('[data-bs-dismiss="modal"], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [href], [tabindex]:not([tabindex="-1"])');
    };

    const restoreFocusSafely = function (previousElement) {
        window.setTimeout(function () {
            const target = previousElement && previousElement.isConnected && !hasAriaHiddenAncestor(previousElement)
                ? previousElement
                : findSafeFocusTarget();

            if (!target || typeof target.focus !== 'function' || hasAriaHiddenAncestor(target)) {
                return;
            }

            try {
                target.focus({ preventScroll: true });
            } catch (error) {
                target.focus();
            }
        }, 40);
    };

    const fire = function (options) {
        const normalized = options && typeof options === 'object' ? { ...options } : {};

        if (hasSwal()) {
            const previousElement = window.document.activeElement;
            if (previousElement && previousElement !== window.document.body && typeof previousElement.blur === 'function') {
                previousElement.blur();
            }
            if (!Object.prototype.hasOwnProperty.call(normalized, 'returnFocus')) {
                normalized.returnFocus = false;
            }

            return Promise.resolve(window.Swal.fire(normalized)).then(function (result) {
                restoreFocusSafely(previousElement);
                return result;
            }, function (error) {
                restoreFocusSafely(previousElement);
                throw error;
            });
        }

        const message = buildMessage(normalized);

        if (normalized.showCancelButton) {
            const confirmed = window.confirm(message || 'Deseja continuar?');
            return Promise.resolve({
                isConfirmed: confirmed,
                isDismissed: !confirmed,
                value: confirmed,
            });
        }

        if (message) {
            window.alert(message);
        }

        return Promise.resolve({
            isConfirmed: true,
            isDismissed: false,
            value: true,
        });
    };

    const confirm = function (options) {
        const normalized = options && typeof options === 'object' ? { ...options } : {};

        if (typeof normalized.showCancelButton === 'undefined') {
            normalized.showCancelButton = true;
        }
        if (!normalized.confirmButtonText) {
            normalized.confirmButtonText = 'Confirmar';
        }
        if (!normalized.cancelButtonText) {
            normalized.cancelButtonText = 'Cancelar';
        }

        return fire(normalized).then(function (result) {
            return Boolean(result && result.isConfirmed);
        });
    };

    const showMessage = function (icon, title, text, options) {
        const normalized = options && typeof options === 'object' ? { ...options } : {};

        return fire({
            icon: icon,
            title: title || '',
            text: text || '',
            confirmButtonText: normalized.confirmButtonText || 'Ok',
            ...normalized,
        });
    };

    const api = window.DSFeedback && typeof window.DSFeedback === 'object' ? window.DSFeedback : {};

    if (typeof api.fire !== 'function') {
        api.fire = fire;
    }

    if (typeof api.confirm !== 'function') {
        api.confirm = confirm;
    }

    if (typeof api.warning !== 'function') {
        api.warning = function (title, text, options) {
            return showMessage('warning', title, text, options);
        };
    }

    if (typeof api.error !== 'function') {
        api.error = function (title, text, options) {
            return showMessage('error', title, text, options);
        };
    }

    window.DSFeedback = api;
})(window);

$(document).ready(function () {

    // =====================================================
    // SIDEBAR TOGGLE
    // =====================================================
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const mobileToggle = document.getElementById('mobileToggle');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const sidebarDesktopBreakpoint = 992;
    const sidebarAutoCollapsePage = document.querySelector('[data-sidebar-auto-collapse="hover"]');
    let sidebarResizeTimer = null;

    const isDesktopSidebarViewport = function () {
        return window.innerWidth >= sidebarDesktopBreakpoint;
    };

    const shouldUseSidebarAutoCollapse = function () {
        return Boolean(sidebar && sidebarAutoCollapsePage) && isDesktopSidebarViewport();
    };

    const clearSidebarAutoCollapseState = function () {
        document.body.classList.remove('os-sidebar-auto-collapse');

        if (!sidebar) {
            return;
        }

        sidebar.classList.remove('os-auto-collapsed');
        sidebar.classList.remove('os-hover-expanded');
    };

    const syncSidebarBodyState = function () {
        const mobileSidebarOpen = Boolean(sidebar) && !isDesktopSidebarViewport() && sidebar.classList.contains('show');
        document.body.classList.toggle('sidebar-mobile-open', mobileSidebarOpen);
    };

    const closeMobileSidebar = function () {
        if (!sidebar) {
            document.body.classList.remove('sidebar-mobile-open');
            return;
        }

        sidebar.classList.remove('show');
        syncSidebarBodyState();
    };

    const applySidebarViewportState = function () {
        if (!sidebar) {
            return;
        }

        const storedCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';

        if (isDesktopSidebarViewport()) {
            sidebar.classList.remove('show');

            if (shouldUseSidebarAutoCollapse()) {
                document.body.classList.add('os-sidebar-auto-collapse');
                sidebar.classList.add('collapsed');
                sidebar.classList.add('os-auto-collapsed');
                sidebar.classList.remove('os-hover-expanded');
            } else {
                clearSidebarAutoCollapseState();
                sidebar.classList.toggle('collapsed', storedCollapsed);
            }
        } else {
            clearSidebarAutoCollapseState();
            sidebar.classList.remove('collapsed');
            sidebar.classList.remove('show');
        }

        syncSidebarBodyState();
    };

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function (event) {
            event.preventDefault();

            if (!sidebar) {
                return;
            }

            if (!isDesktopSidebarViewport()) {
                closeMobileSidebar();
                return;
            }

            if (shouldUseSidebarAutoCollapse()) {
                return;
            }

            sidebar.classList.toggle('collapsed');
            localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
            syncSidebarBodyState();
        });
    }

    if (mobileToggle) {
        mobileToggle.addEventListener('click', function (event) {
            event.preventDefault();

            if (!sidebar || isDesktopSidebarViewport()) {
                return;
            }

            sidebar.classList.toggle('show');
            syncSidebarBodyState();
        });
    }

    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function () {
            closeMobileSidebar();
        });
    }

    sidebar?.addEventListener('click', function (event) {
        if (isDesktopSidebarViewport()) {
            return;
        }

        const clickedLink = event.target.closest('a.nav-link[href]');
        if (!clickedLink) {
            return;
        }

        if (clickedLink.getAttribute('data-bs-toggle') === 'collapse') {
            return;
        }

        closeMobileSidebar();
    });

    sidebar?.addEventListener('mouseenter', function () {
        if (!shouldUseSidebarAutoCollapse()) {
            return;
        }

        sidebar.classList.add('os-hover-expanded');
    });

    sidebar?.addEventListener('mouseleave', function () {
        if (!shouldUseSidebarAutoCollapse()) {
            return;
        }

        sidebar.classList.remove('os-hover-expanded');
    });

    sidebar?.addEventListener('focusin', function () {
        if (!shouldUseSidebarAutoCollapse()) {
            return;
        }

        sidebar.classList.add('os-hover-expanded');
    });

    sidebar?.addEventListener('focusout', function () {
        if (!shouldUseSidebarAutoCollapse()) {
            return;
        }

        window.setTimeout(function () {
            if (!sidebar.contains(document.activeElement)) {
                sidebar.classList.remove('os-hover-expanded');
            }
        }, 0);
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeMobileSidebar();
        }
    });

    window.addEventListener('resize', function () {
        if (sidebarResizeTimer) {
            window.clearTimeout(sidebarResizeTimer);
        }

        sidebarResizeTimer = window.setTimeout(function () {
            applySidebarViewportState();
        }, 120);
    });

    applySidebarViewportState();

    // =====================================================
    // DATATABLES INITIALIZATION
    // =====================================================
    if ($.fn.DataTable) {
        $('.datatable').DataTable({
            language: {
                url: baseUrl + 'assets/json/pt-BR.json',
                search: '',
                searchPlaceholder: 'Buscar...',
            },
            pageLength: 25,
            responsive: true,
            order: [],
            dom: '<"row"<"col-md-6"l><"col-md-6"f>>rtip',
        });
    }

    // =====================================================
    // INPUT MASKS
    // =====================================================
    if ($.fn.mask) {
        $('.mask-cpf').mask('000.000.000-00');
        $('.mask-cnpj').mask('00.000.000/0000-00');
        $('.mask-telefone').mask('(00) 00000-0000');
        $('.mask-cep').mask('00000-000');
        $('.mask-money').mask('#.##0,00', { reverse: true });
    }

    const autoTitleCaseSelector = '[data-auto-title-case="person-name"]';

    function normalizePersonNameValue(value, trimEdges) {
        const raw = String(value || '').replace(/^\s+/g, '');
        const hasTrailingSpace = !trimEdges && /\s$/.test(raw);
        const core = raw.replace(/\s+/g, ' ').trim();

        if (core === '') {
            return '';
        }

        let normalized = core
            .toLocaleLowerCase('pt-BR')
            .replace(/(^|[\s\-'])([A-Za-zÀ-ÖØ-öø-ÿ])/g, function (match, prefix, letter) {
                return prefix + letter.toLocaleUpperCase('pt-BR');
            });

        if (hasTrailingSpace) {
            normalized += ' ';
        }

        return normalized;
    }

    function applyAutoTitleCaseField(input, trimEdges) {
        if (!input) {
            return;
        }

        const previousValue = input.value || '';
        const normalizedValue = normalizePersonNameValue(previousValue, !!trimEdges);

        if (previousValue === normalizedValue) {
            return;
        }

        const cursorStart = typeof input.selectionStart === 'number' ? input.selectionStart : null;
        const cursorEnd = typeof input.selectionEnd === 'number' ? input.selectionEnd : null;
        input.value = normalizedValue;

        if (document.activeElement === input && cursorStart !== null && cursorEnd !== null) {
            const delta = normalizedValue.length - previousValue.length;
            const nextStart = Math.max(0, cursorStart + delta);
            const nextEnd = Math.max(0, cursorEnd + delta);
            input.setSelectionRange(nextStart, nextEnd);
        }
    }

    document.querySelectorAll(autoTitleCaseSelector).forEach(function (input) {
        applyAutoTitleCaseField(input, true);
    });

    $(document).on('input', autoTitleCaseSelector, function () {
        applyAutoTitleCaseField(this, false);
    });

    $(document).on('blur change', autoTitleCaseSelector, function () {
        applyAutoTitleCaseField(this, true);
    });

    $(document).on('submit', 'form', function () {
        this.querySelectorAll(autoTitleCaseSelector).forEach(function (input) {
            applyAutoTitleCaseField(input, true);
        });
    });

    // Dynamic CPF/CNPJ mask based on person type
    $('select[name="tipo_pessoa"]').on('change', function () {
        const campo = $('input[name="cpf_cnpj"]');
        if ($(this).val() === 'juridica') {
            campo.mask('00.000.000/0000-00');
            $('label[for="cpf_cnpj"]').text('CNPJ');
        } else {
            campo.mask('000.000.000-00');
            $('label[for="cpf_cnpj"]').text('CPF');
        }
    });

    // =====================================================
    // CEP LOOKUP (VIA CEP API)
    // =====================================================
    const resolveCepLookupContainer = function ($input) {
        const $form = $input.closest('form');
        if ($form.length) {
            return $form;
        }

        const $modal = $input.closest('.modal-content, .modal-body, .modal');
        if ($modal.length) {
            return $modal.first();
        }

        const $row = $input.closest('.row');
        if ($row.length) {
            return $row;
        }

        return $input.parent();
    };

    const clearCepLookupSpinner = function ($input) {
        $input.siblings('.js-cep-lookup-spinner').remove();
        $input.removeClass('loading-input');
        $input.removeData('cepLookupInFlight');
    };

    const notifyCepLookupWarning = function (message) {
        if (window.Swal && typeof window.Swal.fire === 'function') {
            Swal.fire({
                icon: 'warning',
                title: 'CEP não encontrado',
                text: message,
                confirmButtonText: 'Fechar',
                customClass: { popup: 'glass-card' }
            });
            return;
        }

        alert(message);
    };

    const handleCepLookup = function (el) {
        const $input = $(el);
        const cep = String($input.val() || '').replace(/\D/g, '');

        if (cep.length !== 8) {
            if (cep.length < 8) {
                $input.removeData('cepLookupResolved');
            }
            clearCepLookupSpinner($input);
            return;
        }

        if ($input.data('cepLookupInFlight') === true || $input.data('cepLookupResolved') === cep) {
            return;
        }

        const $lookupContainer = resolveCepLookupContainer($input);

        $input.addClass('loading-input').parent().addClass('position-relative');
        $input.data('cepLookupInFlight', true);
        $input.siblings('.js-cep-lookup-spinner').remove();

        const $lookupSpinner = $('<div class="spinner-border spinner-border-sm position-absolute js-cep-lookup-spinner" style="right: 10px; top: 12px; z-index: 5;" role="status" aria-hidden="true"></div>');
        $input.after($lookupSpinner);

        $.getJSON(`https://viacep.com.br/ws/${cep}/json/`, function (data) {
            clearCepLookupSpinner($input);

            if (!data.erro) {
                $lookupContainer.find('[name="endereco"], .js-logradouro').first().val(data.logradouro || '').trigger('change');
                $lookupContainer.find('[name="bairro"], .js-bairro').first().val(data.bairro || '').trigger('change');
                $lookupContainer.find('[name="cidade"], .js-cidade').first().val(data.localidade || '').trigger('change');
                $lookupContainer.find('[name="uf"], .js-uf').first().val(data.uf || '').trigger('change');

                const $numeroField = $lookupContainer.find('[name="numero"], .js-numero').first();
                if ($numeroField.length) {
                    $numeroField.trigger('focus');
                }

                $input.data('cepLookupResolved', cep);
                return;
            }

            $input.removeData('cepLookupResolved');
            notifyCepLookupWarning('O CEP informado nao foi encontrado. Confira os digitos e tente novamente.');
            $input.val('').trigger('change').focus();
        }).fail(function() {
            clearCepLookupSpinner($input);
            $input.removeData('cepLookupResolved');
            console.warn('[CEP Lookup] Servico de CEP temporariamente indisponivel.');
        });

        return;
        /*

        if (cep.length !== 8) {
            if (cep.length < 8) {
                $input.removeData('cepLookupResolved');
            }
            clearCepLookupSpinner($input);
            return;
        }

        if ($input.data('cepLookupInFlight') === true || $input.data('cepLookupResolved') === cep) {
            return;
        }

        const $container = resolveCepLookupContainer($input);

        $input.addClass('loading-input').parent().addClass('position-relative');
        $input.data('cepLookupInFlight', true);
        $input.siblings('.js-cep-lookup-spinner').remove();
        const $spinner = $('<div class="spinner-border spinner-border-sm position-absolute js-cep-lookup-spinner" style="right: 10px; top: 12px; z-index: 5;" role="status" aria-hidden="true"></div>');
        $input.after($spinner);

        $.getJSON(`https://viacep.com.br/ws/${cep}/json/`, function (data) {
            clearCepLookupSpinner($input);

                if (!data.erro) {
                    // Preenchimento inteligente baseado em nomes ou classes
                    $container.find('[name="endereco"], .js-logradouro').val(data.logradouro).trigger('change');
                    $container.find('[name="bairro"], .js-bairro').val(data.bairro).trigger('change');
                    $container.find('[name="cidade"], .js-cidade').val(data.localidade).trigger('change');
                    $container.find('[name="uf"], .js-uf').val(data.uf).trigger('change');
                    
                    // Foco no número após preenchimento
                    $container.find('[name="numero"], .js-numero').focus();
                } else {
                    alert('CEP não encontrado.');
                    $input.val('').focus();
                }
            }).fail(function() {
                $spinner.remove();
                $input.removeClass('loading-input');
                console.warn('Serviço de CEP temporariamente indisponível.');
            });
        }
        */
    };

    // Gatilho no Blur
    $(document).on('blur', '.mask-cep, input[name="cep"]', function () {
        handleCepLookup(this);
    });
    $(document).on('input', '.mask-cep, input[name="cep"]', function () {
        const cep = String($(this).val() || '').replace(/\D/g, '');
        if (cep.length < 8) {
            $(this).removeData('cepLookupResolved');
        }
        if (cep.length === 8) {
            handleCepLookup(this);
        }
    });

    // Gatilho automático ao completar os 8 dígitos (via mask callback se disponível)
    if ($.fn.mask) {
        $('.mask-cep').mask('00000-000', {
            onComplete: function(cep, e, field) {
                handleCepLookup(field);
            }
        });
    }



    // =====================================================
    // SESSION MONITOR
    // =====================================================
    const sessionTimeoutMeta = document.querySelector('meta[name="session-timeout-minutes"]');
    const sessionHeartbeatMeta = document.querySelector('meta[name="session-heartbeat-url"]');
    const sessionLoginMeta = document.querySelector('meta[name="session-login-url"]');
    const sessionRememberMeta = document.querySelector('meta[name="session-remember-active"]');

    const sessionMonitor = {
        timeoutMinutes: Math.max(0, parseInt(sessionTimeoutMeta?.content || '0', 10)),
        heartbeatUrl: String(sessionHeartbeatMeta?.content || '').trim(),
        loginUrl: String(sessionLoginMeta?.content || '').trim(),
        rememberActive: String(sessionRememberMeta?.content || '0') === '1',
        timeoutMs: 0,
        heartbeatIntervalMs: 0,
        heartbeatTimeoutMs: 10000,
        lastActivityAt: Date.now(),
        lastHeartbeatAt: Date.now(),
        lastTransportActivityAt: 0,
        activityDirty: false,
        heartbeatInFlight: false,
        heartbeatFailureCount: 0,
        pendingSameOriginRequests: 0,
        expired: false,
        alertOpen: false,
        enabled: false
    };

    const resolveSessionRedirectTarget = function () {
        try {
            if (window.top && window.top.location && window.top.location.origin === window.location.origin) {
                return window.top;
            }
        } catch (error) {
            console.warn('[SessionMonitor] sem acesso ao contexto superior', error);
        }

        return window;
    };

    const parseSessionPayload = function (raw) {
        if (!raw) {
            return null;
        }

        try {
            return JSON.parse(raw);
        } catch (error) {
            return null;
        }
    };

    const showSessionExpiredAlert = function (message) {
        if (sessionMonitor.alertOpen) {
            return;
        }

        sessionMonitor.expired = true;
        sessionMonitor.alertOpen = true;

        const redirectTarget = resolveSessionRedirectTarget();
        const redirectToLogin = function () {
            redirectTarget.location.href = sessionMonitor.loginUrl || (baseUrl + 'login');
        };

        if (window.Swal && typeof window.Swal.fire === 'function') {
            Swal.fire({
                icon: 'warning',
                title: 'Sessao expirada',
                text: message || 'Sua sessao expirou. Faca login novamente para continuar.',
                confirmButtonText: 'Ir para login',
                allowOutsideClick: false,
                allowEscapeKey: false,
                customClass: { popup: 'glass-card' }
            }).then(redirectToLogin);
            return;
        }

        alert(message || 'Sua sessao expirou. Faca login novamente para continuar.');
        redirectToLogin();
    };

    const handleSessionAuthFailure = function (payload) {
        if (sessionMonitor.expired) {
            return;
        }

        const message = String(payload?.message || 'Sua sessao expirou. Faca login novamente para continuar.');
        showSessionExpiredAlert(message);
    };

    const touchSessionActivity = function (isHighFrequency) {
        if (!sessionMonitor.enabled || sessionMonitor.expired) {
            return;
        }

        const now = Date.now();
        if (isHighFrequency && (now - sessionMonitor.lastActivityAt) < 1000) {
            return;
        }

        sessionMonitor.lastActivityAt = now;
        sessionMonitor.activityDirty = true;
    };

    const resolveAbsoluteUrl = function (rawUrl) {
        if (!rawUrl) {
            return '';
        }

        try {
            return new URL(rawUrl, window.location.origin).href;
        } catch (error) {
            return '';
        }
    };

    const isSameOriginUrl = function (rawUrl) {
        const absoluteUrl = resolveAbsoluteUrl(rawUrl);
        if (!absoluteUrl) {
            return false;
        }

        try {
            return new URL(absoluteUrl).origin === window.location.origin;
        } catch (error) {
            return false;
        }
    };

    const isHeartbeatUrl = function (rawUrl) {
        const absoluteUrl = resolveAbsoluteUrl(rawUrl);
        const heartbeatUrl = resolveAbsoluteUrl(sessionMonitor.heartbeatUrl);

        return absoluteUrl !== '' && heartbeatUrl !== '' && absoluteUrl === heartbeatUrl;
    };

    const noteTransportStart = function (rawUrl) {
        if (!isSameOriginUrl(rawUrl) || isHeartbeatUrl(rawUrl)) {
            return;
        }

        sessionMonitor.pendingSameOriginRequests += 1;
        sessionMonitor.lastTransportActivityAt = Date.now();
    };

    const noteTransportEnd = function (rawUrl) {
        if (!isSameOriginUrl(rawUrl) || isHeartbeatUrl(rawUrl)) {
            return;
        }

        sessionMonitor.pendingSameOriginRequests = Math.max(0, sessionMonitor.pendingSameOriginRequests - 1);
        sessionMonitor.lastTransportActivityAt = Date.now();
    };

    const sendSessionHeartbeat = function () {
        if (!sessionMonitor.enabled || sessionMonitor.expired || sessionMonitor.heartbeatInFlight) {
            return;
        }

        if (sessionMonitor.pendingSameOriginRequests > 0) {
            return;
        }

        if (sessionMonitor.lastTransportActivityAt > 0 && (Date.now() - sessionMonitor.lastTransportActivityAt) < 5000) {
            return;
        }

        sessionMonitor.heartbeatInFlight = true;
        const heartbeatAbortController = typeof AbortController === 'function' ? new AbortController() : null;
        const heartbeatTimeoutId = heartbeatAbortController
            ? window.setTimeout(function () {
                heartbeatAbortController.abort();
            }, sessionMonitor.heartbeatTimeoutMs)
            : null;

        const heartbeatConfig = {
            method: 'GET',
            cache: 'no-store',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };

        if (heartbeatAbortController) {
            heartbeatConfig.signal = heartbeatAbortController.signal;
        }

        fetch(sessionMonitor.heartbeatUrl, heartbeatConfig).then(function (response) {
            if (!response.ok) {
                if (response.status === 401) {
                    return null;
                }

                throw new Error('HTTP ' + response.status);
            }

            return response.json();
        }).then(function (payload) {
            if (!payload || payload.ok !== true) {
                return;
            }

            sessionMonitor.lastHeartbeatAt = Date.now();
            sessionMonitor.heartbeatFailureCount = 0;
            sessionMonitor.activityDirty = false;
        }).catch(function (error) {
            if (sessionMonitor.expired) {
                return;
            }

            sessionMonitor.lastHeartbeatAt = Date.now();
            sessionMonitor.heartbeatFailureCount += 1;

            if (error && error.name === 'AbortError') {
                console.warn('[SessionMonitor] heartbeat abortado por timeout');
                return;
            }

            console.error('[SessionMonitor] falha no heartbeat', error);
        }).finally(function () {
            if (heartbeatTimeoutId) {
                window.clearTimeout(heartbeatTimeoutId);
            }

            sessionMonitor.heartbeatInFlight = false;
        });
    };

    const initSessionMonitor = function () {
        if (sessionMonitor.timeoutMinutes <= 0 || sessionMonitor.heartbeatUrl === '' || sessionMonitor.rememberActive) {
            return;
        }

        sessionMonitor.enabled = true;
        sessionMonitor.timeoutMs = sessionMonitor.timeoutMinutes * 60 * 1000;
        sessionMonitor.heartbeatIntervalMs = Math.min(
            Math.max(45000, Math.floor(sessionMonitor.timeoutMs / 3)),
            120000
        );

        const checkSessionState = function () {
            if (!sessionMonitor.enabled || sessionMonitor.expired) {
                return;
            }

            const now = Date.now();
            const inactiveMs = now - sessionMonitor.lastActivityAt;

            if (inactiveMs >= sessionMonitor.timeoutMs) {
                handleSessionAuthFailure({
                    session_expired: true,
                    message: 'Sua sessao expirou por inatividade. Faca login novamente.'
                });
                return;
            }

            if ((now - sessionMonitor.lastHeartbeatAt) >= sessionMonitor.heartbeatIntervalMs
                && sessionMonitor.activityDirty === true) {
                sendSessionHeartbeat();
            }
        };

        const trackedEvents = ['click', 'keydown', 'input', 'focusin', 'touchstart'];
        trackedEvents.forEach(function (eventName) {
            document.addEventListener(eventName, function () {
                touchSessionActivity(false);
            }, true);
        });

        document.addEventListener('mousemove', function () {
            touchSessionActivity(true);
        }, { passive: true });

        document.addEventListener('scroll', function () {
            touchSessionActivity(true);
        }, { passive: true, capture: true });

        $(document).on('submit', 'form', function (event) {
            if (sessionMonitor.expired) {
                event.preventDefault();
                handleSessionAuthFailure({
                    session_expired: true,
                    message: 'Sua sessao expirou. Faca login novamente antes de salvar.'
                });
                return false;
            }

            touchSessionActivity(false);
        });

        if (window.fetch && !window.__ERP_SESSION_FETCH_PATCHED__) {
            const nativeFetch = window.fetch.bind(window);
            window.__ERP_SESSION_FETCH_PATCHED__ = true;

            window.fetch = function (input, init) {
                const rawUrl = typeof input === 'string' ? input : (input?.url || '');
                let sameOrigin = false;

                try {
                    sameOrigin = new URL(rawUrl, window.location.origin).origin === window.location.origin;
                } catch (error) {
                    sameOrigin = false;
                }

                const nextInit = init ? Object.assign({}, init) : {};

                if (sameOrigin) {
                    const headers = new Headers(nextInit.headers || (input && input.headers) || {});
                    if (!headers.has('X-Requested-With')) {
                        headers.set('X-Requested-With', 'XMLHttpRequest');
                    }
                    if (!headers.has('Accept')) {
                        headers.set('Accept', 'application/json, text/plain, */*');
                    }

                    nextInit.headers = headers;
                    if (!nextInit.credentials) {
                        nextInit.credentials = 'same-origin';
                    }
                }

                if (sameOrigin) {
                    noteTransportStart(rawUrl);
                }

                return nativeFetch(input, nextInit).then(function (response) {
                    if (!sameOrigin || response.status !== 401) {
                        return response;
                    }

                    const contentType = String(response.headers.get('content-type') || '').toLowerCase();
                    if (contentType.indexOf('application/json') !== -1) {
                        response.clone().json().then(function (payload) {
                            if (payload?.auth_required || payload?.session_expired) {
                                handleSessionAuthFailure(payload);
                            }
                        }).catch(function () {
                            handleSessionAuthFailure({});
                        });
                    } else {
                        handleSessionAuthFailure({});
                    }

                    return response;
                }).finally(function () {
                    if (sameOrigin) {
                        noteTransportEnd(rawUrl);
                    }
                });
            };
        }

        if (window.jQuery && !window.__ERP_SESSION_JQUERY_PATCHED__) {
            window.__ERP_SESSION_JQUERY_PATCHED__ = true;

            $(document).ajaxSend(function (_event, jqXHR, settings) {
                const requestUrl = settings?.url || window.location.href;
                noteTransportStart(requestUrl);

                jqXHR.always(function () {
                    noteTransportEnd(requestUrl);
                });
            });
        }

        $(document).ajaxError(function (_event, jqXHR) {
            if (!jqXHR || jqXHR.status !== 401) {
                return;
            }

            const payload = jqXHR.responseJSON || parseSessionPayload(jqXHR.responseText);
            if (payload?.auth_required || payload?.session_expired) {
                handleSessionAuthFailure(payload);
                return;
            }

            handleSessionAuthFailure({});
        });

        window.addEventListener('pageshow', function () {
            sessionMonitor.expired = false;
            sessionMonitor.alertOpen = false;
            sessionMonitor.lastActivityAt = Date.now();
            sessionMonitor.lastHeartbeatAt = Date.now();
            sessionMonitor.activityDirty = false;
        });

        setInterval(checkSessionState, 15000);
    };

    initSessionMonitor();

    // =====================================================
    // CONFIRM DELETE
    // =====================================================
    $(document).on('click', '.btn-delete', function (e) {
        e.preventDefault();
        const url = $(this).attr('href') || $(this).data('url') || '';
        const nome = $(this).data('nome') || 'este registro';

        if (!url) {
            console.error('[DeleteFlow] URL de exclusao ausente no botao.', {
                element: this,
                classes: this.className,
                path: window.location.pathname,
            });

            window.DSFeedback.fire({
                icon: 'error',
                title: 'Nao foi possivel excluir',
                text: 'A acao de exclusao esta sem rota configurada. Atualize a pagina e tente novamente.',
                confirmButtonText: 'Entendi',
            });
            return;
        }

        window.DSFeedback.confirm({
            icon: 'warning',
            title: 'Confirmar exclusao',
            text: `Tem certeza que deseja excluir "${nome}"? Esta acao nao pode ser desfeita.`,
            confirmButtonText: 'Sim, excluir',
            cancelButtonText: 'Cancelar',
            reverseButtons: true,
            focusCancel: true,
        }).then(function (confirmed) {
            if (confirmed) {
                window.location.href = url;
            }
        });
    });

    // =====================================================
    // OS ITEM CALCULATIONS
    // =====================================================
    $(document).on('input', 'input[name="quantidade"], input[name="valor_unitario"]', function () {
        const form = $(this).closest('form, .item-row');
        const qtd = parseFloat(form.find('input[name="quantidade"]').val()) || 0;
        const unitario = parseFloat(form.find('input[name="valor_unitario"]').val()) || 0;
        const total = qtd * unitario;
        form.find('input[name="valor_total"], .item-total').val(total.toFixed(2)).text('R$ ' + total.toFixed(2).replace('.', ','));
    });

    // =====================================================
    // FLASH MESSAGE AUTO-DISMISS
    // =====================================================
    setTimeout(function () {
        $('.alert-dismissible').fadeOut(500, function () {
            $(this).remove();
        });
    }, 5000);

    // =====================================================
    // TOOLTIP INIT
    // =====================================================
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // =====================================================
    // ULTRA RESPONSIVE ENHANCEMENTS (GLOBAL)
    // =====================================================
    initUltraResponsiveLayout();
    clearStuckModalState();
    document.addEventListener('hidden.bs.modal', function () {
        setTimeout(clearStuckModalState, 60);
    });

});

// Global base URL
var baseUrl = document.querySelector('meta[name="base-url"]')?.content ||
    window.location.origin + '/';

/**
 * Voltar padronizado: usa histórico se disponível, senão vai para URL padrão.
 * @param {string} defaultUrl
 * @returns {boolean} false para evitar navegação padrão
 */
function resolveFromParam() {
    try {
        const params = new URLSearchParams(window.location.search);
        const from = params.get('from');
        if (!from) return null;
        const url = new URL(from, window.location.origin);
        if (url.origin !== window.location.origin) return null;
        return url.href;
    } catch (e) {
        return null;
    }
}

function goBack(defaultUrl) {
    const fromTarget = resolveFromParam();
    if (fromTarget) {
        window.location.href = fromTarget;
        return false;
    }

    try {
        const ref = document.referrer || '';
        const sameOrigin = ref && ref.indexOf(window.location.origin) === 0;
        if (window.history.length > 1 && sameOrigin) {
            window.history.back();
            return false;
        }
    } catch (e) {
        // fallback abaixo
    }

    const target = defaultUrl || (baseUrl + 'dashboard');
    window.location.href = target;
    return false;
}

// Delegated handler for buttons/links with data-back-default
document.addEventListener('click', function (e) {
    const btn = e.target.closest('[data-back-default]');
    if (!btn) return;
    e.preventDefault();
    const fallback = btn.getAttribute('data-back-default') || (baseUrl + 'dashboard');
    goBack(fallback);
});

/**
 * Função para confirmar o encerramento de registros
 * @param {string} modulo - O slug do módulo (os, equipamentos, estoque)
 * @param {number} id - O ID do registro
 */
function escapeEquipmentLifecycleHtml(value) {
    return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function getEquipmentClosureReasonOptions() {
    return {
        retirada_pecas: 'Retirada de pecas',
        irreparavel: 'Problema irreparavel',
        descartado: 'Descartado',
        pecas_vendidas: 'Pecas vendidas para outros clientes',
        outro: 'Outro motivo'
    };
}

function getEquipmentReactivationReasonOptions() {
    return {
        recuperado: 'Recuperado',
        recondicionado: 'Recondicionado',
        reparado: 'Reparado',
        voltou_operacao: 'Voltou a funcionar',
        outro: 'Outro motivo'
    };
}

function buildEquipmentLifecycleReasonOptionsHtml(reasonOptions) {
    return Object.entries(reasonOptions || {}).map(([value, label]) => (
        `<option value="${escapeEquipmentLifecycleHtml(value)}">${escapeEquipmentLifecycleHtml(label)}</option>`
    )).join('');
}

function ensureEquipmentClosureCsrfState(name, hash) {
    if (!window.__ERP_EQUIPAMENTO_ENCERRAMENTO_CSRF) {
        window.__ERP_EQUIPAMENTO_ENCERRAMENTO_CSRF = { name: '', hash: '' };
    }

    if (name) {
        window.__ERP_EQUIPAMENTO_ENCERRAMENTO_CSRF.name = String(name);
    }
    if (hash) {
        window.__ERP_EQUIPAMENTO_ENCERRAMENTO_CSRF.hash = String(hash);
    }

    return window.__ERP_EQUIPAMENTO_ENCERRAMENTO_CSRF;
}

function renderEquipmentLifecycleBadgeHtml(equipment, context = 'detail', openOsCount = 0) {
    const isEncerrado = Boolean(equipment?.is_encerrado);
    const count = Number.isFinite(openOsCount) ? Number(openOsCount) : 0;
    const motivo = String(equipment?.motivo_encerramento_label || '').trim();

    if (isEncerrado) {
        return `
            <span class="badge text-bg-dark">Encerrado</span>
            ${motivo ? `<span class="text-muted ms-2">${escapeEquipmentLifecycleHtml(motivo)}</span>` : ''}
        `;
    }

    if (context === 'detail') {
        return `
            <span class="badge text-bg-success">Ativo</span>
            ${count > 0 ? `<span class="badge text-bg-warning text-dark ms-2">${count === 1 ? '1 OS em andamento' : `${count} OS em andamento`}</span>` : ''}
        `;
    }

    if (count > 0) {
        return `
            <span class="badge text-bg-warning text-dark">${count === 1 ? '1 OS em andamento' : `${count} OS em andamento`}</span>
            <span class="text-muted ms-2">Encerramento bloqueado</span>
        `;
    }

    return '';
}

function getEquipmentOpenOsCount(equipmentId) {
    const normalizedId = String(equipmentId || '').trim();
    if (!normalizedId) {
        return 0;
    }

    const element = document.querySelector(`[data-equipment-open-os-count][data-equipment-id="${normalizedId}"]`);
    if (!element) {
        return 0;
    }

    const parsedCount = Number.parseInt(element.dataset.equipmentOpenOsCount || '0', 10);
    return Number.isFinite(parsedCount) && parsedCount > 0 ? parsedCount : 0;
}

function buildEquipmentOpenOsBlockMessage(openOsCount) {
    const count = Number.isFinite(openOsCount) ? Number(openOsCount) : 0;
    if (count <= 0) {
        return '';
    }

    const label = count === 1 ? '1 OS em andamento' : `${count} OS em andamento`;
    return `Este equipamento possui ${label}. Finalize ou cancele as ordens antes de encerrar sua vida util.`;
}

function applyEquipmentLifecycleUI(equipment) {
    const equipmentId = String(equipment?.id || '').trim();
    if (!equipmentId) {
        return;
    }

    const isEncerrado = Boolean(equipment?.is_encerrado);
    const openOsCount = getEquipmentOpenOsCount(equipmentId);

    document.querySelectorAll(`[data-equipment-lifecycle-badge][data-equipment-id="${equipmentId}"]`).forEach((element) => {
        const context = String(element.dataset.equipmentLifecycleContext || 'detail').trim() || 'detail';
        const lifecycleHtml = renderEquipmentLifecycleBadgeHtml(equipment, context, openOsCount);
        if (lifecycleHtml) {
            element.classList.remove('d-none');
            element.innerHTML = lifecycleHtml;
        } else {
            element.classList.add('d-none');
            element.innerHTML = '';
        }
    });

    document.querySelectorAll(`[data-equipment-active-only][data-equipment-id="${equipmentId}"]`).forEach((element) => {
        element.classList.toggle('d-none', isEncerrado);
    });

    document.querySelectorAll(`[data-equipment-closed-only][data-equipment-id="${equipmentId}"]`).forEach((element) => {
        element.classList.toggle('d-none', !isEncerrado);
    });

    document.querySelectorAll(`[data-equipment-row][data-equipment-id="${equipmentId}"]`).forEach((element) => {
        element.classList.toggle('table-secondary', isEncerrado);
    });

    const fields = {
        motivo_label: String(equipment?.motivo_encerramento_label || '').trim(),
        encerrado_em_label: String(equipment?.encerrado_em_label || '').trim(),
        observacao: String(equipment?.observacao_encerramento || '').trim()
    };

    Object.entries(fields).forEach(([field, value]) => {
        document.querySelectorAll(`[data-equipment-field="${field}"][data-equipment-id="${equipmentId}"]`).forEach((element) => {
            element.textContent = value;
        });
    });

    document.querySelectorAll(`[data-equipment-field-wrap="observacao"][data-equipment-id="${equipmentId}"]`).forEach((element) => {
        if (fields.observacao) {
            element.classList.remove('d-none');
        } else {
            element.classList.add('d-none');
        }
    });
}

function applyEquipmentClosureUI(equipment) {
    applyEquipmentLifecycleUI(equipment);
}

function applyEquipmentReactivationUI(equipment) {
    applyEquipmentLifecycleUI(equipment);
}

function updateEquipmentLifecycleHistoryUI(equipmentId, historyHtml = '', historyCount = null) {
    const normalizedId = String(equipmentId || '').trim();
    if (!normalizedId) {
        return;
    }

    const containerSelector = `[data-equipment-lifecycle-history-container][data-equipment-id="${normalizedId}"]`;
    document.querySelectorAll(containerSelector).forEach((element) => {
        if (typeof historyHtml === 'string') {
            element.innerHTML = historyHtml;
        }
    });

    if (historyCount !== null && historyCount !== undefined) {
        const normalizedCount = Number.isFinite(Number(historyCount))
            ? String(Number(historyCount))
            : String(historyCount);

        document.querySelectorAll(`[data-equipment-lifecycle-history-count][data-equipment-id="${normalizedId}"]`).forEach((element) => {
            element.textContent = normalizedCount;
        });
    }
}

window.updateEquipmentLifecycleHistoryUI = updateEquipmentLifecycleHistoryUI;

function confirmarEncerramento(modulo, id, csrfName = '', csrfHash = '') {
    const titulos = {
        'os': 'Ordem de Servico',
        'equipamentos': 'Equipamento',
        'estoque': 'Peca/Item'
    };

    if (modulo !== 'equipamentos' || Number(id) <= 0) {
        const nome = titulos[modulo] || 'registro';

        if (window.Swal && typeof window.Swal.fire === 'function') {
            window.Swal.fire({
                icon: 'info',
                title: 'Fluxo dedicado',
                text: `A funcionalidade de processamento de encerramento para ${nome} esta em fase de implementacao tecnica. O controle de acesso atual ja valida sua permissao para esta acao.`,
                confirmButtonText: 'Ok'
            });
        } else {
            window.alert(`A funcionalidade de processamento de encerramento para ${nome} esta em fase de implementacao tecnica. O controle de acesso atual ja valida sua permissao para esta acao.`);
        }

        return;
    }

    const openOsCount = getEquipmentOpenOsCount(id);
    if (openOsCount > 0) {
        const blockMessage = buildEquipmentOpenOsBlockMessage(openOsCount);

        if (window.Swal && typeof window.Swal.fire === 'function') {
            window.Swal.fire({
                icon: 'warning',
                title: 'Encerramento bloqueado',
                text: blockMessage,
                confirmButtonText: 'Ok'
            });
        } else {
            window.alert(blockMessage);
        }

        return;
    }

    const csrfState = ensureEquipmentClosureCsrfState(csrfName, csrfHash);
    const baseUrl = document.querySelector('meta[name="base-url"]')?.content || window.location.origin + '/';
    const reasonOptions = getEquipmentClosureReasonOptions();

    if (!(window.Swal && typeof window.Swal.fire === 'function')) {
        const confirmed = window.confirm('Deseja encerrar este equipamento? Ele permanecera no historico, mas nao podera receber novas OS.');
        if (confirmed) {
            window.alert('SweetAlert2 nao esta disponivel neste contexto.');
        }
        return;
    }

    const optionsHtml = Object.entries(reasonOptions).map(([value, label]) => (
        `<option value="${escapeEquipmentLifecycleHtml(value)}">${escapeEquipmentLifecycleHtml(label)}</option>`
    )).join('');

    window.Swal.fire({
        icon: 'warning',
        title: 'Encerrar equipamento?',
        html: `
            <p class="text-start mb-3">
                O equipamento continuara no historico do cliente, mas ficara indisponivel para novas OS e novos vinculos operacionais.
            </p>
            <div class="text-start">
                <label for="swal-equipment-close-reason" class="form-label fw-semibold">Motivo do encerramento *</label>
                <select id="swal-equipment-close-reason" class="form-select">
                    <option value="">Selecione...</option>
                    ${optionsHtml}
                </select>
            </div>
            <div class="text-start mt-3">
                <label for="swal-equipment-close-note" class="form-label fw-semibold">Observacao interna</label>
                <textarea id="swal-equipment-close-note" class="form-control" rows="3" placeholder="Detalhes complementares para o historico do equipamento."></textarea>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Encerrar equipamento',
        cancelButtonText: 'Cancelar',
        focusConfirm: false,
        showLoaderOnConfirm: true,
        allowOutsideClick: () => !window.Swal.isLoading(),
        preConfirm: async () => {
            const motivo = document.getElementById('swal-equipment-close-reason')?.value || '';
            const observacao = document.getElementById('swal-equipment-close-note')?.value || '';

            if (!motivo) {
                window.Swal.showValidationMessage('Selecione o motivo do encerramento.');
                return false;
            }

            const payload = new URLSearchParams();
            payload.set('motivo_encerramento', motivo);
            payload.set('observacao_encerramento', observacao);
            if (csrfState.name && csrfState.hash) {
                payload.set(csrfState.name, csrfState.hash);
            }

            const response = await fetch(`${baseUrl}equipamentos/encerrar/${id}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: payload.toString()
            });

            const data = await response.json().catch(() => ({}));
            if (data?.csrfHash) {
                ensureEquipmentClosureCsrfState(csrfState.name, data.csrfHash);
            }

            if (!response.ok || data?.status !== 'success') {
                window.Swal.showValidationMessage(data?.message || 'Nao foi possivel encerrar o equipamento agora.');
                return false;
            }

            return data;
        }
    }).then((result) => {
        if (!result?.isConfirmed || !result.value?.equipamento) {
            return;
        }

        applyEquipmentClosureUI(result.value.equipamento);
        if (Object.prototype.hasOwnProperty.call(result.value, 'lifecycle_history_html')) {
            updateEquipmentLifecycleHistoryUI(
                result.value.equipamento?.id || id,
                result.value.lifecycle_history_html || '',
                result.value.lifecycle_history_count
            );
        }

        window.Swal.fire({
            icon: 'success',
            title: 'Equipamento encerrado',
            text: result.value.message || 'O equipamento foi encerrado com sucesso.',
            confirmButtonText: 'Ok'
        });
    });
}

window.confirmarEncerramento = confirmarEncerramento;

/**
 * Abre a página de documentação correspondente na mesma aba.
 * @param {string} page - Slugs ou caminhos curtos (ex: 'equipamentos', 'os')
 */
function openDocPage(page) {
    const baseUrl = document.querySelector('meta[name="base-url"]')?.content || window.location.origin + '/';
    let path = page;

    // Mapeamento de atalhos para caminhos reais da documentação
    const mapping = {
        'equipamentos': '01-manual-do-usuario/equipamentos.md',
        'ordens-de-servico': '01-manual-do-usuario/ordens-de-servico.md',
        'dashboard': '01-manual-do-usuario/dashboard.md',
        'clientes': '01-manual-do-usuario/clientes.md',
        'contatos': '01-manual-do-usuario/contatos.md',
        'estoque': '01-manual-do-usuario/estoque.md',
        'financeiro': '01-manual-do-usuario/financeiro.md',
        'relatorios': '01-manual-do-usuario/relatorios.md',
        'perfil': '01-manual-do-usuario/perfil.md',
        'fornecedores': '01-manual-do-usuario/fornecedores.md',
        'funcionarios': '01-manual-do-usuario/funcionarios.md',
        'servicos': '01-manual-do-usuario/servicos.md',
        'orcamentos': '01-manual-do-usuario/orcamentos.md',
        'pacotes-servicos': '06-modulos-do-sistema/pacotes-servicos.md',
        'precificacao': '06-modulos-do-sistema/precificacao.md',
        'checklists': '06-modulos-do-sistema/checklists.md',
        'usuarios': '02-manual-administrador/usuarios-e-permissoes.md',
        'grupos': '02-manual-administrador/usuarios-e-permissoes.md',
        'configuracoes': '02-manual-administrador/configuracao-do-sistema.md',
        'migracao-legado-sql': '02-manual-administrador/migracao-legado-sql.md',
        'legacy-migration': '02-manual-administrador/migracao-legado-sql.md',
        'legacy-migration-architecture': '03-arquitetura-tecnica/migracao-legado-sql.md',
        'equipamentos-tipos': '06-modulos-do-sistema/equipamentos-tipos.md',
        'equipamentos-marcas': '06-modulos-do-sistema/equipamentos-marcas.md',
        'equipamentos-modelos': '06-modulos-do-sistema/equipamentos-modelos.md',
        'equipamentos-defeitos': '06-modulos-do-sistema/defeitos-comuns.md',
        'defeitos-relatados': '06-modulos-do-sistema/defeitos-relatados.md',
        'os-workflow': '02-manual-administrador/fluxo-de-trabalho-os.md',
        'crm': '06-modulos-do-sistema/crm.md',
        'crm-campanhas': '06-modulos-do-sistema/crm.md#campanhas',
        'crm-metricas-marketing': '06-modulos-do-sistema/crm.md#metricas-marketing',
        'crm-clientes-inativos': '06-modulos-do-sistema/crm.md#clientes-inativos',
        'whatsapp': '06-modulos-do-sistema/whatsapp.md',
        'atendimento-whatsapp': '06-modulos-do-sistema/central-de-mensagens.md',
        'atendimento-mobile': '12-app-mobile-pwa/README.md',
        'atendimento-whatsapp-chatbot': '06-modulos-do-sistema/central-de-mensagens.md#chatbot',
        'atendimento-whatsapp-metricas': '06-modulos-do-sistema/central-de-mensagens.md#metricas',
        'atendimento-whatsapp-filas': '06-modulos-do-sistema/central-de-mensagens.md#filas',
        'atendimento-whatsapp-faq': '06-modulos-do-sistema/central-de-mensagens.md#faq',
        'atendimento-whatsapp-fluxos': '06-modulos-do-sistema/central-de-mensagens.md#fluxos',
        'atendimento-whatsapp-respostas': '06-modulos-do-sistema/central-de-mensagens.md#respostas-rapidas',
        'atendimento-whatsapp-config': '06-modulos-do-sistema/central-de-mensagens.md#configuracoes',
        'central-mobile': '12-app-mobile-pwa/README.md',
        'app-mobile-pwa': '12-app-mobile-pwa/README.md',
        'app-mobile-versionamento': '12-app-mobile-pwa/09-versionamento-e-releases/politica-de-versoes.md',
        'app-mobile-design-system': '12-app-mobile-pwa/README.md',
        'central-mensagens': '06-modulos-do-sistema/central-de-mensagens.md',
        'central-mensagens-chatbot': '06-modulos-do-sistema/central-de-mensagens.md#chatbot',
        'central-mensagens-metricas': '06-modulos-do-sistema/central-de-mensagens.md#metricas',
        'central-mensagens-filas': '06-modulos-do-sistema/central-de-mensagens.md#filas',
        'central-mensagens-faq': '06-modulos-do-sistema/central-de-mensagens.md#faq',
        'central-mensagens-fluxos': '06-modulos-do-sistema/central-de-mensagens.md#fluxos',
        'central-mensagens-respostas': '06-modulos-do-sistema/central-de-mensagens.md#respostas-rapidas',
        'central-mensagens-config': '06-modulos-do-sistema/central-de-mensagens.md#configuracoes',
        'design-system': '06-modulos-do-sistema/design-system.md',
        'estoque-movimentacoes': '01-manual-do-usuario/estoque.md#movimentacoes',
        'modelos-pdf-os': '06-modulos-do-sistema/ordens-de-servico.md#modelos-pdf-da-os',
        'templates-whatsapp-os': '06-modulos-do-sistema/ordens-de-servico.md#templates-de-whatsapp-para-documentos',
        'deploy-vps': '10-deploy/manual-tecnico-oficial-vps-ubuntu-24-ci4.md',
        'deploy-vps-script': '10-deploy/scripts/install_erp.sh',
        'deploy-vemfazer-setup': '10-deploy/integracao-setup-vemfazer.md',
        'deploy-vemfazer-stack': '10-deploy/integracao-setup-vemfazer.md',
        'deploy-vps-guia': '10-deploy/linux-vps-deployment.md',
        'deploy-vps-atualizacao': '10-deploy/atualizacao-vps-sem-downtime.md',
        'deploy-vps-ubuntu26': '10-deploy/atualizacao-vps-sem-downtime.md',
        'upgrade-vps': '10-deploy/atualizacao-vps-sem-downtime.md',
        'deploy-agente-autonomo': '10-deploy/agente-autonomo-devops-engenharia-fullstack.md',
        'agente-devops': '10-deploy/agente-autonomo-devops-engenharia-fullstack.md',
        'padrao-responsividade': '11-padroes/boas-praticas.md#responsividade-ultra-compatibilidade-obrigatorio',
        'vendas': '06-modulos-do-sistema/vendas.md'
    };

    if (mapping[page]) {
        path = mapping[page];
    }

    const from = encodeURIComponent(window.location.pathname + window.location.search + window.location.hash);
    window.location.href = `${baseUrl}documentacao?from=${from}#${encodeURIComponent(path)}`;
}

// =====================================================
// ULTRA RESPONSIVE CORE
// =====================================================
let ultraResponsiveObserver = null;
let ultraResponsiveScheduleToken = null;
let chartReflowTimer = null;

function scheduleUltraResponsiveApply() {
    if (ultraResponsiveScheduleToken) {
        return;
    }

    ultraResponsiveScheduleToken = window.requestAnimationFrame(function () {
        ultraResponsiveScheduleToken = null;
        applyUltraResponsiveTables(document);
    });
}

function applyUltraResponsiveTables(root) {
    const scope = root || document;
    const tables = scope.querySelectorAll('.page-content table:not(.no-mobile-stack)');

    tables.forEach(function (table) {
        if (!table.closest('.table-responsive') && !table.classList.contains('dataTable')) {
            const wrapper = document.createElement('div');
            wrapper.className = 'table-responsive ds-ultra-table-scroll';
            table.parentNode.insertBefore(wrapper, table);
            wrapper.appendChild(table);
        }

        const headCells = Array.from(table.querySelectorAll('thead th'));
        if (!headCells.length) {
            return;
        }

        const labels = headCells.map(function (th, index) {
            const text = (th.textContent || '').replace(/\s+/g, ' ').trim();
            return text !== '' ? text : ('Campo ' + (index + 1));
        });

        const rows = table.querySelectorAll('tbody tr');
        rows.forEach(function (row) {
            const cells = Array.from(row.children).filter(function (cell) {
                return cell.tagName === 'TD' || cell.tagName === 'TH';
            });

            cells.forEach(function (cell, index) {
                if (!cell.hasAttribute('data-label') || cell.getAttribute('data-label').trim() === '') {
                    const label = labels[index] || ('Campo ' + (index + 1));
                    cell.setAttribute('data-label', label);
                }
            });
        });
    });
}

function reflowAllCharts() {
    try {
        if (window.Chart && window.Chart.instances) {
            const instancesRaw = window.Chart.instances;
            const instances = Array.isArray(instancesRaw)
                ? instancesRaw
                : Object.keys(instancesRaw).map(function (key) { return instancesRaw[key]; });

            instances.forEach(function (chart) {
                if (!chart || typeof chart.resize !== 'function') {
                    return;
                }
                chart.resize();
                if (typeof chart.update === 'function') {
                    chart.update('none');
                }
            });
        }
    } catch (error) {
        console.error('[UltraResponsive] falha no reflow de Chart.js', error);
    }
}

function scheduleChartsReflow() {
    if (chartReflowTimer) {
        clearTimeout(chartReflowTimer);
    }

    chartReflowTimer = setTimeout(function () {
        reflowAllCharts();
    }, 180);
}

function initUltraResponsiveLayout() {
    scheduleUltraResponsiveApply();

    if (ultraResponsiveObserver) {
        ultraResponsiveObserver.disconnect();
    }

    const pageContent = document.querySelector('.page-content');
    if (pageContent) {
        ultraResponsiveObserver = new MutationObserver(function () {
            scheduleUltraResponsiveApply();
        });

        ultraResponsiveObserver.observe(pageContent, {
            childList: true,
            subtree: true,
        });
    }

    window.addEventListener('resize', function () {
        scheduleUltraResponsiveApply();
        scheduleChartsReflow();
    }, { passive: true });

    window.addEventListener('orientationchange', function () {
        scheduleUltraResponsiveApply();
        scheduleChartsReflow();
    }, { passive: true });

    if (window.visualViewport) {
        window.visualViewport.addEventListener('resize', function () {
            scheduleUltraResponsiveApply();
            scheduleChartsReflow();
        }, { passive: true });
    }
}

function clearStuckModalState() {
    const body = document.body;
    if (!body || !body.classList.contains('modal-open')) {
        return;
    }

    const hasVisibleModal = document.querySelector('.modal.show');
    if (hasVisibleModal) {
        return;
    }

    body.classList.remove('modal-open');
    body.style.removeProperty('overflow');
    body.style.removeProperty('padding-right');

    document.querySelectorAll('.modal-backdrop').forEach(function (el) {
        el.remove();
    });
}

window.initPatternPasswordField = function initPatternPasswordField(config) {
    const settings = config || {};
    const root = typeof settings.root === 'string'
        ? document.querySelector(settings.root)
        : settings.root;

    if (!root) {
        return null;
    }

    if (root._patternPasswordController) {
        return root._patternPasswordController;
    }

    const hiddenInput = root.querySelector('[data-password-hidden]');
    const modeInput = root.querySelector('[data-password-mode-input]');
    const patternInput = root.querySelector('[data-password-pattern-input]');
    const textInput = root.querySelector('[data-password-text-input]');
    const textWrap = root.querySelector('[data-password-text-wrap]');
    const patternWrap = root.querySelector('[data-password-pattern-wrap]');
    const preview = root.querySelector('[data-password-preview]');
    const clearButton = root.querySelector('[data-password-clear]');
    const nodes = Array.from(root.querySelectorAll('[data-pattern-node]'));
    const buttons = Array.from(root.querySelectorAll('[data-password-mode]'));

    if (!hiddenInput || !modeInput || !patternInput || !textInput || !textWrap || !patternWrap || !nodes.length) {
        return null;
    }

    let mode = 'desenho';
    let pointerActive = false;
    let drawingSequence = [];
    let committedSequence = [];

    const normalizeSequence = function (input) {
        return String(input || '')
            .split(/[^0-9]+/)
            .map(function (part) { return part.trim(); })
            .filter(function (part) { return /^[1-9]$/.test(part); })
            .filter(function (part, index, list) { return list.indexOf(part) === index; });
    };

    const renderNodes = function (sequence) {
        nodes.forEach(function (node) {
            node.classList.remove('is-active');
            node.removeAttribute('data-step');
        });

        sequence.forEach(function (value, index) {
            const node = root.querySelector('[data-pattern-node="' + value + '"]');
            if (!node) {
                return;
            }

            node.classList.add('is-active');
            node.setAttribute('data-step', String(index + 1));
        });
    };

    const syncStoredValue = function () {
        modeInput.value = mode;

        if (mode === 'desenho') {
            const serialized = committedSequence.join('-');
            patternInput.value = serialized;
            hiddenInput.value = serialized ? ('desenho_' + serialized) : '';
            preview.textContent = serialized
                ? ('Desenho salvo: ' + serialized)
                : 'Nenhum desenho definido.';
            return;
        }

        patternInput.value = '';
        hiddenInput.value = String(textInput.value || '').trim();
        preview.textContent = hiddenInput.value !== ''
            ? 'Senha em texto pronta para salvar.'
            : 'Nenhuma senha informada.';
    };

    const setMode = function (nextMode, shouldFocus) {
        mode = nextMode === 'texto' ? 'texto' : 'desenho';
        root.dataset.passwordMode = mode;

        buttons.forEach(function (button) {
            const isActive = button.getAttribute('data-password-mode') === mode;
            button.classList.toggle('active', isActive);
            button.classList.toggle('btn-outline-secondary', !isActive);
            button.classList.toggle('btn-secondary', isActive);
            button.classList.toggle('text-white', isActive);
        });

        textWrap.hidden = mode !== 'texto';
        patternWrap.hidden = mode !== 'desenho';
        syncStoredValue();

        if (shouldFocus && mode === 'texto') {
            textInput.focus();
        }
    };

    const clearDrawing = function () {
        committedSequence = [];
        drawingSequence = [];
        renderNodes(committedSequence);
        syncStoredValue();
    };

    const finalizeDrawing = function () {
        if (!pointerActive) {
            return;
        }

        pointerActive = false;
        root.classList.remove('is-drawing');
        committedSequence = drawingSequence.slice();
        renderNodes(committedSequence);
        syncStoredValue();
    };

    const registerNode = function (node) {
        const value = String(node.getAttribute('data-pattern-node') || '').trim();
        if (!/^[1-9]$/.test(value) || drawingSequence.indexOf(value) !== -1) {
            return;
        }

        drawingSequence.push(value);
        renderNodes(drawingSequence);
    };

    buttons.forEach(function (button) {
        button.addEventListener('click', function () {
            setMode(button.getAttribute('data-password-mode'), true);
        });
    });

    textInput.addEventListener('input', function () {
        if (mode === 'texto') {
            syncStoredValue();
        }
    });

    nodes.forEach(function (node) {
        node.addEventListener('pointerdown', function (event) {
            if (mode !== 'desenho') {
                return;
            }

            event.preventDefault();
            pointerActive = true;
            drawingSequence = [];
            root.classList.add('is-drawing');
            registerNode(node);
        });

        node.addEventListener('pointerenter', function () {
            if (mode !== 'desenho' || !pointerActive) {
                return;
            }

            registerNode(node);
        });

        node.addEventListener('click', function (event) {
            if (mode === 'desenho') {
                event.preventDefault();
            }
        });
    });

    document.addEventListener('pointerup', finalizeDrawing);
    clearButton?.addEventListener('click', clearDrawing);

    const controller = {
        clear: function () {
            textInput.value = '';
            clearDrawing();
            setMode(settings.defaultMode === 'texto' ? 'texto' : 'desenho', false);
        },
        setValue: function (value) {
            const raw = String(value || '').trim();

            if (raw.startsWith('desenho_')) {
                committedSequence = normalizeSequence(raw.replace(/^desenho_/, ''));
                drawingSequence = committedSequence.slice();
                renderNodes(committedSequence);
                textInput.value = '';
                setMode('desenho', false);
                syncStoredValue();
                return;
            }

            textInput.value = raw;
            committedSequence = [];
            drawingSequence = [];
            renderNodes([]);
            setMode(raw !== '' ? 'texto' : (settings.defaultMode === 'texto' ? 'texto' : 'desenho'), false);
            syncStoredValue();
        },
        getValue: function () {
            return String(hiddenInput.value || '');
        },
        getMode: function () {
            return mode;
        }
    };

    root._patternPasswordController = controller;
    controller.setValue(hiddenInput.value || '');

    return controller;
};
