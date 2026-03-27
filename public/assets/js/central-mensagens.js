(function () {
    if (window.__CM_JS_BOOTED__) {
        return;
    }
    window.__CM_JS_BOOTED__ = true;

    const cfg = window.CM_CFG || {};
    const listEl = document.getElementById('conversaList');
    const threadMessages = document.getElementById('threadMessages');
    const threadTitle = document.getElementById('threadTitle');
    const threadSubtitle = document.getElementById('threadSubtitle');
    const threadStatusBadge = document.getElementById('threadStatusBadge');
    const formEnviar = document.getElementById('formEnviarMensagem');
    const conversaIdInput = document.getElementById('cmConversaId');
    const msgInput = document.getElementById('cmMensagem');
    const tipoMensagemInput = document.getElementById('cmTipoMensagem');
    const documentoSelect = document.getElementById('cmDocumentoId');
    const contextoEl = document.getElementById('contextoConversa');

    if (!cfg.endpointConversas || !cfg.endpointConversaPrefix || !listEl || !threadMessages || !formEnviar) {
        return;
    }

    const filtroQ = document.getElementById('filtroConversaQ');
    const filtroStatus = document.getElementById('filtroConversaStatus');
    const filtroResponsavel = document.getElementById('filtroConversaResponsavel');
    const filtroTag = document.getElementById('filtroConversaTag');
    const filtroNaoLidas = document.getElementById('filtroConversaNaoLidas');
    const filtroOsAberta = document.getElementById('filtroConversaOsAberta');
    const filtroClientesNovos = document.getElementById('filtroConversaClientesNovos');
    const btnFiltrar = document.getElementById('btnFiltrarConversas');
    const btnLimparFiltros = document.getElementById('btnLimparFiltros');
    const btnSyncInbound = document.getElementById('btnSyncInbound');
    const btnNovaConversa = document.getElementById('btnNovaConversa');
    const btnAtualizarConversa = document.getElementById('btnAtualizarConversa');
    const btnAssumirConversa = document.getElementById('btnAssumirConversa');
    const btnAtribuirConversa = document.getElementById('btnAtribuirConversa');
    const btnEncerrarConversa = document.getElementById('btnEncerrarConversa');
    const btnSyncInboundMobile = document.getElementById('btnSyncInboundMobile');
    const btnNovaConversaMobile = document.getElementById('btnNovaConversaMobile');
    const btnAnexarMidia = document.getElementById('btnAnexarMidia');
    const btnEmojiPicker = document.getElementById('btnEmojiPicker');
    const anexoInput = document.getElementById('cmAnexoInput');
    const anexoPreview = document.getElementById('cmAnexoPreview');
    const sendButton = formEnviar.querySelector('button[type="submit"]');
    const jumpBottomBtn = document.getElementById('cmJumpBottomBtn');
    const filterFeedbackEl = document.getElementById('cmFilterFeedback');
    const conversaCountEl = document.getElementById('cmConversaCount');
    const naoLidasCountEl = document.getElementById('cmNaoLidasCount');
    const realtimeBadgeEl = document.getElementById('cmRealtimeBadge');

    const imageModalEl = document.getElementById('imageModal');
    const imageModalImg = document.getElementById('imageModalImg');
    const imagePrevBtn = document.getElementById('cmImgPrevBtn');
    const imageNextBtn = document.getElementById('cmImgNextBtn');
    const gatewayAccountNumberEl = document.getElementById('gatewayAccountNumber');
    const attachMenu = document.getElementById('cmAttachMenu');
    const emojiMenu = document.getElementById('cmEmojiMenu');
    const composeActions = document.getElementById('cmComposeActions');
    const composeMetaPanel = document.getElementById('cmComposeMetaPanel');
    const capturePanel = document.getElementById('cmCapturePanel');
    const cameraPhotoInput = document.getElementById('cmCameraPhotoInput');
    const cameraVideoInput = document.getElementById('cmCameraVideoInput');
    const imageModalInstance = (window.bootstrap && imageModalEl)
        ? window.bootstrap.Modal.getOrCreateInstance(imageModalEl)
        : null;

    const autoSyncIntervalMs = Math.max(5000, Number(cfg.autoSyncSeconds || 15) * 1000);
    const slaPrimeiraRespostaMin = Math.max(1, Number(cfg.slaPrimeiraRespostaMin || 60));
    const defaultRequestTimeoutMs = Math.max(8000, Number(cfg.requestTimeoutMs || 20000));
    const sseEnabledByConfig = (() => {
        const raw = String(cfg.enableSse ?? '1').trim().toLowerCase();
        return !['0', 'false', 'no', 'nao', 'off'].includes(raw);
    })();
    const normalizedBasePath = (() => {
        const raw = String(cfg.basePath || '/').trim();
        if (!raw || raw === '/') {
            return '/';
        }
        return '/' + raw.replace(/^\/+|\/+$/g, '') + '/';
    })();
    const loginUrl = (() => {
        try {
            return new URL('login', window.location.origin + normalizedBasePath).toString();
        } catch (error) {
            return window.location.origin + '/login';
        }
    })();
    const currentUserId = Math.max(0, Number(cfg.currentUserId || 0));
    const currentUserName = String(cfg.currentUserName || '').trim();

    const state = {
        currentConversaId: null,
        currentList: [],
        listSignature: '',
        renderedActiveConversationId: null,
        mensagens: [],
        latestMessageId: 0,
        activeConversationUnread: 0,
        pollTimer: null,
        pollRunning: false,
        streamSource: null,
        streamForConversaId: null,
        streamReady: false,
        streamOpenedAt: 0,
        streamRetryTimer: null,
        streamProbeBlockedUntil: 0,
        streamDisabledUntil: 0,
        selectedFile: null,
        imageItems: [],
        imageIndex: -1,
        currentContext: null,
        authRedirectInProgress: false,
        lastPollErrorLogAt: 0,
        recording: {
            active: false,
            type: null, // 'audio' | 'video'
            recorder: null,
            chunks: [],
            stream: null,
            blob: null,
            startTime: 0,
            timer: null
        }
    };

    const swal = (options) => {
        if (window.Swal) {
            return window.Swal.fire(options);
        }
        const title = options?.title || 'Aviso';
        const text = options?.text || '';
        // Fallback tecnico caso SweetAlert2 indisponivel.
        alert((title ? title + '\n' : '') + text);
        return Promise.resolve();
    };

    const escapeHtml = (value) => {
        const div = document.createElement('div');
        div.textContent = value == null ? '' : String(value);
        return div.innerHTML;
    };

    const parseDate = (value) => {
        if (!value) {
            return null;
        }
        const dt = new Date(String(value).replace(' ', 'T'));
        return Number.isNaN(dt.getTime()) ? null : dt;
    };

    const formatDateTime = (value) => {
        if (!value) {
            return '';
        }
        return String(value).replace('T', ' ').substring(0, 16);
    };

    const toSortableTimestamp = (value) => {
        const dt = parseDate(value);
        return dt ? dt.getTime() : 0;
    };

    const sortConversasByRecency = (items) => {
        if (!Array.isArray(items)) {
            return [];
        }

        return [...items].sort((a, b) => {
            const aUnread = Number(a?.nao_lidas || 0) > 0 ? 1 : 0;
            const bUnread = Number(b?.nao_lidas || 0) > 0 ? 1 : 0;
            if (aUnread !== bUnread) {
                return bUnread - aUnread;
            }

            const aTs = toSortableTimestamp(a?.ultima_mensagem_em);
            const bTs = toSortableTimestamp(b?.ultima_mensagem_em);
            if (aTs !== bTs) {
                return bTs - aTs;
            }

            const aMsgId = Number(a?.ultima_mensagem_id || 0);
            const bMsgId = Number(b?.ultima_mensagem_id || 0);
            if (aMsgId !== bMsgId) {
                return bMsgId - aMsgId;
            }

            const aId = Number(a?.id || 0);
            const bId = Number(b?.id || 0);
            return bId - aId;
        });
    };

    const appendQuery = (url, key, value) => {
        if (!url) {
            return '';
        }
        const join = url.indexOf('?') >= 0 ? '&' : '?';
        return url + join + encodeURIComponent(key) + '=' + encodeURIComponent(String(value));
    };

    const resolveEndpointUrl = (path) => {
        const raw = String(path || '').trim();
        if (!raw) {
            return '';
        }
        if (/^https?:\/\//i.test(raw)) {
            return raw;
        }
        return new URL(raw, window.location.origin).toString();
    };

    const resolveArquivoUrl = (arquivo, cacheToken) => {
        const raw = String(arquivo || '').trim();
        if (!raw) {
            return '';
        }
        const version = cacheToken || Date.now();
        if (/^https?:\/\//i.test(raw)) {
            return appendQuery(raw, 'v', version);
        }
        const path = raw.replace(/^\/+/, '');
        const basePathRaw = String(cfg.basePath || '/').trim();
        const basePath = basePathRaw === '/' ? '' : basePathRaw.replace(/\/+$/, '');
        const full = window.location.origin + basePath + '/' + path;
        return appendQuery(full, 'v', version);
    };

    const detectContentType = (message) => {
        const tipo = String(message?.tipo_conteudo || '').toLowerCase();
        const mime = String(message?.mime_type || '').toLowerCase();
        const arquivo = String(message?.arquivo || message?.anexo_path || '').toLowerCase();

        if (tipo === 'imagem' || tipo === 'image') return 'imagem';
        if (tipo === 'audio') return 'audio';
        if (tipo === 'video') return 'video';
        if (tipo === 'pdf') return 'pdf';
        if (tipo === 'arquivo') return 'arquivo';
        if (tipo && tipo !== 'texto') return 'arquivo';

        if (mime.startsWith('image/')) return 'imagem';
        if (mime.startsWith('audio/')) return 'audio';
        if (mime.startsWith('video/')) return 'video';
        if (mime === 'application/pdf') return 'pdf';
        if (mime) return 'arquivo';

        if (/\.(png|jpe?g|webp|gif|bmp)$/i.test(arquivo)) return 'imagem';
        if (/\.(mp3|ogg|wav|m4a|aac|opus)$/i.test(arquivo)) return 'audio';
        if (/\.(mp4|webm|mov|mkv)$/i.test(arquivo)) return 'video';
        if (/\.pdf$/i.test(arquivo)) return 'pdf';
        if (arquivo) return 'arquivo';
        return 'texto';
    };

    const toBool = (value) => {
        if (value === true || value === 1 || value === '1') return true;
        if (typeof value === 'string') {
            const raw = value.trim().toLowerCase();
            return ['true', 'yes', 'sim', 'on'].includes(raw);
        }
        return false;
    };

    const sseStorageKey = 'cm:sse-disabled-until';
    const syncSseDisableFromStorage = () => {
        try {
            const stored = Number(window.sessionStorage.getItem(sseStorageKey) || 0);
            if (Number.isFinite(stored) && stored > state.streamDisabledUntil) {
                state.streamDisabledUntil = stored;
            }
        } catch (error) {
            // sessionStorage pode estar indisponivel; segue sem persistencia.
        }
    };
    const disableSseTemporarily = (ms, reason) => {
        const until = Date.now() + Math.max(5000, Number(ms || 0));
        state.streamDisabledUntil = Math.max(state.streamDisabledUntil, until);
        state.streamProbeBlockedUntil = Math.max(state.streamProbeBlockedUntil, until);
        try {
            window.sessionStorage.setItem(sseStorageKey, String(state.streamDisabledUntil));
        } catch (error) {
            // Ignora erro de armazenamento.
        }
        if (reason) {
            console.warn('[CentralMensagens] SSE temporariamente desativado:', reason);
        }
    };

    const getGatewayAccountNumber = () => {
        const domValue = String(gatewayAccountNumberEl?.textContent || '').replace(/\D+/g, '');
        if (domValue) {
            return domValue;
        }

        const cfgValue = String(cfg.gatewayAccountNumber || '').replace(/\D+/g, '');
        if (cfgValue) {
            return cfgValue;
        }

        return '';
    };

    const bytesToHuman = (bytes) => {
        const value = Number(bytes || 0);
        if (!Number.isFinite(value) || value <= 0) return '';
        if (value < 1024) return value + ' B';
        if (value < 1024 * 1024) return (value / 1024).toFixed(1) + ' KB';
        return (value / (1024 * 1024)).toFixed(1) + ' MB';
    };

    const iconByType = (type) => {
        switch (type) {
            case 'imagem': return 'bi-image';
            case 'video': return 'bi-film';
            case 'audio': return 'bi-mic';
            case 'pdf': return 'bi-file-earmark-pdf';
            default: return 'bi-paperclip';
        }
    };

    const currentFilters = () => ({
        q: (filtroQ?.value || '').trim(),
        status: (filtroStatus?.value || '').trim(),
        responsavel_id: (filtroResponsavel?.value || '').trim(),
        tag_id: (filtroTag?.value || '').trim(),
        nao_lidas: filtroNaoLidas?.checked ? '1' : '0',
        com_os_aberta: filtroOsAberta?.checked ? '1' : '0',
        clientes_novos: filtroClientesNovos?.checked ? '1' : '0',
    });

    const firstInitial = (value) => {
        const clean = String(value || '').trim();
        if (!clean) {
            return '?';
        }
        const parts = clean.split(/\s+/).filter(Boolean);
        const first = parts[0]?.[0] || '';
        const second = parts[1]?.[0] || '';
        return (first + second).trim().toUpperCase() || clean.substring(0, 2).toUpperCase();
    };

    const normalizeStatusLabel = (value) => {
        const status = String(value || 'aberta').toLowerCase();
        if (status === 'resolvida') return 'Resolvida';
        if (status === 'aguardando') return 'Aguardando';
        if (status === 'arquivada') return 'Arquivada';
        return 'Aberta';
    };

    const priorityBadgeClass = (priority) => {
        const normalized = String(priority || 'normal').toLowerCase();
        if (normalized === 'urgente') return 'text-bg-danger';
        if (normalized === 'alta') return 'text-bg-warning text-dark';
        if (normalized === 'baixa') return 'text-bg-secondary';
        return 'text-bg-light border text-secondary';
    };

    const updateFilterFeedback = () => {
        if (!filterFeedbackEl) {
            return;
        }
        const filters = currentFilters();
        const chips = [];
        if (filters.q) chips.push(`Busca: ${filters.q}`);
        if (filters.status) chips.push(`Status: ${filters.status}`);
        if (filters.responsavel_id) chips.push('Responsavel filtrado');
        if (filters.tag_id) chips.push('Tag filtrada');
        if (filters.nao_lidas === '1') chips.push('Nao lidas');
        if (filters.com_os_aberta === '1') chips.push('Com OS aberta');
        if (filters.clientes_novos === '1') chips.push('Clientes novos');

        if (chips.length === 0) {
            filterFeedbackEl.textContent = 'Sem filtros ativos.';
            return;
        }
        filterFeedbackEl.textContent = 'Filtros ativos: ' + chips.join(' • ');
    };

    const updateConversationCounters = (items) => {
        if (!Array.isArray(items)) {
            return;
        }
        const total = items.length;
        const naoLidas = items.reduce((acc, item) => acc + Number(item?.nao_lidas || 0), 0);

        if (conversaCountEl) {
            conversaCountEl.textContent = `${total} conversa${total === 1 ? '' : 's'}`;
        }
        if (naoLidasCountEl) {
            naoLidasCountEl.textContent = String(naoLidas);
        }
    };

    const setRealtimeBadge = (mode) => {
        if (!realtimeBadgeEl) {
            return;
        }
        realtimeBadgeEl.classList.remove('live', 'polling', 'warn');
        if (mode === 'live') {
            realtimeBadgeEl.classList.add('live');
            realtimeBadgeEl.innerHTML = '<i class="bi bi-broadcast-pin me-1"></i>Tempo real';
            return;
        }
        if (mode === 'warn') {
            realtimeBadgeEl.classList.add('warn');
            realtimeBadgeEl.innerHTML = '<i class="bi bi-exclamation-triangle me-1"></i>Instavel';
            return;
        }
        realtimeBadgeEl.classList.add('polling');
        realtimeBadgeEl.innerHTML = '<i class="bi bi-arrow-repeat me-1"></i>Polling';
    };

    const computeListSignature = (items) => {
        if (!Array.isArray(items) || items.length === 0) {
            return '';
        }
        return items.map((item) => [
            Number(item.id || 0),
            Number(item.cliente_id || 0),
            Number(item.contato_cliente_id || 0),
            Number(item.contato_id || 0),
            String(item.contato_status_relacionamento || ''),
            Number(item.nao_lidas || 0),
            String(item.status || ''),
            String(item.prioridade || ''),
            String(item.ultima_mensagem_em || ''),
            String(item.ultima_mensagem_tipo || ''),
            String(item.ultima_mensagem_direcao || ''),
            String(item.nome_contato || ''),
            Number(item.automacao_ativa ?? 1),
            Number(item.aguardando_humano || 0),
        ].join(':')).join('|');
    };

    const toQueryString = (obj) => {
        const params = new URLSearchParams();
        Object.entries(obj || {}).forEach(([k, v]) => {
            params.set(k, String(v ?? ''));
        });
        return params.toString();
    };

    const makeRequestError = (message, metadata) => {
        const error = new Error(message || 'Falha na requisicao.');
        if (metadata && typeof metadata === 'object') {
            Object.assign(error, metadata);
        }
        return error;
    };

    const parseJsonSafe = (rawText) => {
        if (typeof rawText !== 'string' || rawText.trim() === '') {
            return null;
        }
        try {
            return JSON.parse(rawText);
        } catch (error) {
            return null;
        }
    };

    const getPayloadMessage = (payload) => {
        if (!payload || typeof payload !== 'object') {
            return '';
        }
        const value = payload.message;
        return typeof value === 'string' ? value.trim() : '';
    };

    const looksLikeHtml = (rawText) => {
        const text = String(rawText || '').trim().toLowerCase();
        return text.startsWith('<!doctype html') || text.startsWith('<html') || text.startsWith('<body');
    };

    const fallbackMessageByStatus = (status) => {
        switch (Number(status || 0)) {
            case 401:
            case 403:
                return 'Sessao expirada. Entre novamente para continuar.';
            case 404:
                return 'Recurso nao encontrado no servidor.';
            case 409:
                return 'Conflito de dados. Atualize a tela e tente novamente.';
            case 422:
                return 'Dados invalidos para esta operacao.';
            case 500:
                return 'Erro interno no servidor.';
            case 502:
            case 503:
                return 'Servico temporariamente indisponivel. Verifique API/gateway e tente novamente.';
            case 504:
                return 'Tempo de resposta excedido no servidor.';
            default:
                return 'Falha na requisicao.';
        }
    };

    const buildHttpErrorMessage = (status, payloadMessage, rawText) => {
        if (payloadMessage) {
            return payloadMessage;
        }
        if (looksLikeHtml(rawText)) {
            return 'Resposta invalida do servidor (HTML inesperado).';
        }
        return fallbackMessageByStatus(status);
    };

    const stopPollingLoop = () => {
        if (state.pollTimer) {
            clearTimeout(state.pollTimer);
        }
        state.pollTimer = null;
        state.pollRunning = false;
    };

    const handleAuthExpired = async (message) => {
        if (state.authRedirectInProgress) {
            return;
        }
        state.authRedirectInProgress = true;
        stopPollingLoop();
        if (typeof closeMessageStream === 'function') {
            closeMessageStream();
        } else if (state.streamSource) {
            try {
                state.streamSource.close();
            } catch (error) {
                // Ignora falha no fechamento forçado.
            }
            state.streamSource = null;
        }

        await swal({
            icon: 'warning',
            title: 'Sessao expirada',
            text: message || 'Sua sessao expirou. Entre novamente para continuar.',
            allowOutsideClick: false,
            allowEscapeKey: false,
            confirmButtonText: 'Ir para login',
        });

        window.location.href = loginUrl;
    };

    const requestJson = async (url, init, timeoutMs) => {
        const requestTimeoutMs = Number(timeoutMs || defaultRequestTimeoutMs);
        const controller = new AbortController();
        const timeoutHandle = setTimeout(() => controller.abort(), requestTimeoutMs);

        let response;
        try {
            response = await fetch(url, {
                cache: 'no-store',
                ...init,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    ...(init?.headers || {}),
                },
                signal: controller.signal,
            });
        } catch (error) {
            const timeoutMessage = requestTimeoutMs >= 1000
                ? `Tempo limite excedido (${Math.round(requestTimeoutMs / 1000)}s).`
                : 'Tempo limite excedido.';
            if (error?.name === 'AbortError') {
                throw makeRequestError(timeoutMessage, { code: 'request_timeout', url });
            }
            if (error instanceof TypeError) {
                throw makeRequestError('Falha de rede/CORS ao comunicar com o backend.', {
                    code: 'network_error',
                    url,
                });
            }
            throw makeRequestError(error?.message || 'Falha de comunicacao com o servidor.', {
                code: 'request_error',
                url,
            });
        } finally {
            clearTimeout(timeoutHandle);
        }

        const rawText = await response.text().catch(() => '');
        const payload = parseJsonSafe(rawText);
        const hasPayload = payload && typeof payload === 'object';
        const hasOkFalse = hasPayload
            && Object.prototype.hasOwnProperty.call(payload, 'ok')
            && payload.ok === false;
        const payloadMessage = getPayloadMessage(payload);

        if (!response.ok || hasOkFalse || !hasPayload) {
            const message = buildHttpErrorMessage(response.status, payloadMessage, rawText);
            const requestError = makeRequestError(message, {
                status: response.status,
                url,
                payload,
                rawText,
            });
            if (response.status === 401 || response.status === 403) {
                await handleAuthExpired(message);
            }
            throw requestError;
        }

        return payload;
    };

    const getJson = async (url, timeoutMs) => requestJson(url, { method: 'GET' }, timeoutMs);

    const postForm = async (url, payload, file, timeoutMs) => {
        const fd = new FormData();
        Object.entries(payload || {}).forEach(([k, v]) => {
            if (v == null) {
                return;
            }
            fd.append(k, v);
        });
        if (file) {
            fd.append('anexo', file, file.name || 'anexo.bin');
        }
        if (cfg.csrfName && cfg.csrfHash) {
            fd.append(cfg.csrfName, cfg.csrfHash);
        }
        return requestJson(url, { method: 'POST', body: fd }, timeoutMs);
    };

    const setButtonLoading = (btn, loading, loadingHtml) => {
        if (!btn) return;
        if (loading) {
            btn.dataset.originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = loadingHtml || '<span class="spinner-border spinner-border-sm me-1"></span>Processando...';
            return;
        }
        btn.disabled = false;
        if (btn.dataset.originalHtml) {
            btn.innerHTML = btn.dataset.originalHtml;
            delete btn.dataset.originalHtml;
        }
    };

    const renderConversaItem = (item) => {
        const isActive = state.currentConversaId === Number(item.id);
        const nome = item.cliente_nome
            || item.contato_nome
            || item.contato_perfil_nome
            || item.nome_contato
            || item.telefone
            || 'Contato sem nome';
        const unread = Number(item.nao_lidas || 0);
        const automacaoAtiva = Number(item.automacao_ativa ?? 1) === 1;
        const aguardandoHumano = Number(item.aguardando_humano || 0) === 1;
        const prioridade = String(item.prioridade || 'normal').toLowerCase();
        const hasOs = !!item.numero_os;
        const clienteId = Number(item.cliente_id || item.contato_cliente_id || 0);
        const isClienteNovo = clienteId <= 0;
        const contatoStatus = String(item.contato_status_relacionamento || '').toLowerCase();
        const isLeadQualificado = isClienteNovo && contatoStatus === 'lead_qualificado';
        const ultimaMensagemAt = parseDate(item.ultima_mensagem_em);
        const diffMs = ultimaMensagemAt ? (Date.now() - ultimaMensagemAt.getTime()) : 0;
        const slaEstourado = unread > 0 && diffMs > (slaPrimeiraRespostaMin * 60 * 1000);
        const subtitle = [item.telefone, item.numero_os ? ('OS ' + item.numero_os) : null].filter(Boolean).join(' | ');
        const statusLabel = normalizeStatusLabel(item.status || 'aberta');
        const avatar = firstInitial(nome);

        const lastDirection = String(item.ultima_mensagem_direcao || '').toLowerCase();
        const lastBot = Number(item.ultima_mensagem_bot || 0) === 1;
        const lastTipoMensagem = String(item.ultima_mensagem_tipo_mensagem || '').toLowerCase();
        const lastOutboundExterno = lastDirection === 'outbound' && !lastBot && lastTipoMensagem === 'outbound_externo';
        const previewPrefix = lastDirection === 'inbound'
            ? '<span class="cm-preview-prefix inbound">Cliente:</span>'
            : (lastBot
                ? '<span class="cm-preview-prefix bot">Bot:</span>'
                : `<span class="cm-preview-prefix outbound">${lastOutboundExterno ? 'Externo:' : 'Voce:'}</span>`);
        const ultimaMensagemBruta = item.ultima_mensagem_texto
            ? item.ultima_mensagem_texto
            : (item.ultima_mensagem_tipo && item.ultima_mensagem_tipo !== 'texto'
                ? `[${item.ultima_mensagem_tipo}]`
                : 'Sem mensagens');
        const ultimaMensagem = `<span class="cm-conversa-preview">${previewPrefix}${escapeHtml(ultimaMensagemBruta)}</span>`;
        const ultimaData = formatDateTime(item.ultima_mensagem_em);
        const responsavel = item.responsavel_nome || 'Nao atribuido';

        const prioridadeBadge = `<span class="badge ${priorityBadgeClass(prioridade)}">${escapeHtml(prioridade)}</span>`;
        const flags = [
            automacaoAtiva
                ? '<span class="badge text-bg-success-subtle text-success-emphasis border">Bot ativo</span>'
                : '<span class="badge text-bg-secondary">Bot off</span>',
            aguardandoHumano ? '<span class="badge text-bg-warning text-dark">Aguard. humano</span>' : '',
            hasOs ? '<span class="badge text-bg-primary">OS vinculada</span>' : '',
            isClienteNovo ? '<span class="badge text-bg-info text-dark">Cliente novo</span>' : '',
            isLeadQualificado ? '<span class="badge text-bg-warning text-dark">Lead qualificado</span>' : '',
            slaEstourado ? '<span class="badge text-bg-danger">SLA estourado</span>' : '',
            prioridadeBadge,
        ].filter(Boolean).join(' ');

        const cadastroContatoBtn = (cfg.canCreateContato && isClienteNovo)
            ? `<button type="button" class="btn btn-sm btn-outline-success py-0 px-2 cm-btn-cadastrar-contato" data-conversa-id="${item.id}">Salvar contato</button>`
            : '';

        return `
            <div class="cm-conversa-item ${isActive ? 'active' : ''}" data-id="${item.id}">
                <div class="cm-conversa-head">
                    <div class="cm-conversa-main">
                        <div class="cm-conversa-avatar">${escapeHtml(avatar)}</div>
                        <div class="min-w-0 flex-grow-1">
                            <div class="cm-conversa-title">${escapeHtml(nome)}</div>
                            <div class="cm-conversa-subtitle">${escapeHtml(subtitle || 'Sem telefone/OS vinculada')}</div>
                        </div>
                    </div>
                    <div class="text-end d-flex flex-column align-items-end gap-1">
                        ${unread > 0 ? `<span class="badge bg-danger cm-unread-pill">${unread}</span>` : ''}
                        <span class="small text-muted">${escapeHtml(ultimaData)}</span>
                    </div>
                </div>
                ${ultimaMensagem}
                <div class="cm-conversa-badges">${flags}</div>
                <div class="cm-conversa-foot">
                    <span class="cm-conversa-status">
                        ${unread > 0 ? '<span class="cm-unread-dot"></span>' : '<i class="bi bi-dot text-muted"></i>'}
                        ${escapeHtml(statusLabel)}
                    </span>
                    <span class="text-truncate ms-2">${escapeHtml(responsavel)}</span>
                </div>
                ${cadastroContatoBtn ? `<div class="small mt-1 d-flex justify-content-end">${cadastroContatoBtn}</div>` : ''}
            </div>
        `;
    };

    const isLikelyPhoneLabel = (value) => {
        const raw = String(value || '').trim();
        if (!raw) {
            return false;
        }
        const digits = raw.replace(/\D+/g, '');
        if (!digits) {
            return false;
        }
        const nonDigits = raw.replace(/[0-9+\-().\s]/g, '');
        return digits.length >= 8 && nonDigits.length <= 2;
    };

    const buildCadastroContatoUrl = (conversaId) => {
        const prefix = String(cfg.endpointCadastrarContatoPrefix || cfg.endpointConversaPrefix || '').replace(/\/+$/, '');
        return `${prefix}/${conversaId}/cadastrar-contato`;
    };

    const openCadastrarContatoModal = async (conversaId) => {
        if (!window.Swal) {
            return;
        }

        const conversa = (state.currentList || []).find((item) => Number(item?.id || 0) === Number(conversaId || 0));
        if (!conversa) {
            await swal({
                icon: 'warning',
                title: 'Conversa nao encontrada',
                text: 'Nao foi possivel localizar os dados da conversa para cadastro.',
            });
            return;
        }

        const telefone = String(conversa.telefone || '').trim();
        const nomeSugeridoRaw = String(
            conversa.contato_nome
            || conversa.contato_perfil_nome
            || conversa.nome_contato
            || ''
        ).trim();
        const nomeSugerido = isLikelyPhoneLabel(nomeSugeridoRaw) ? '' : nomeSugeridoRaw;

        const result = await window.Swal.fire({
            title: 'Salvar contato da conversa',
            html: `
                <div class="text-start">
                    <label class="form-label small mb-1" for="swCadastroTelefone">Telefone</label>
                    <input id="swCadastroTelefone" class="swal2-input" value="${escapeHtml(telefone)}" readonly>
                    <label class="form-label small mb-1 mt-2" for="swCadastroNome">Nome do contato</label>
                    <input id="swCadastroNome" class="swal2-input" placeholder="Ex.: Joao da Silva" value="${escapeHtml(nomeSugerido)}">
                    <div class="small text-muted mt-2">
                        Este registro vai para a agenda de contatos. O selo <strong>Cliente novo</strong> permanece ate existir vinculo em OS/Cliente.
                    </div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Salvar contato',
            cancelButtonText: 'Cancelar',
            focusConfirm: false,
            preConfirm: () => {
                const nome = String(document.getElementById('swCadastroNome')?.value || '').trim();
                return { nome };
            },
        });

        if (!result?.value) {
            return;
        }

        const url = buildCadastroContatoUrl(conversaId);
        try {
            await postForm(url, {
                nome: String(result.value.nome || '').trim(),
            });

            await swal({
                icon: 'success',
                title: 'Contato salvo',
                text: 'Contato vinculado com sucesso a esta conversa.',
            });

            await safeLoadConversas(true);
            if (state.currentConversaId === conversaId) {
                await openConversa(conversaId, false);
            }
        } catch (error) {
            await swal({
                icon: 'error',
                title: 'Falha ao salvar contato',
                text: error?.message || 'Nao foi possivel salvar o contato nesta conversa.',
            });
        }
    };

    const bindConversaListClicks = () => {
        listEl.querySelectorAll('.cm-btn-cadastrar-contato').forEach((btn) => {
            if (btn.dataset.bound === '1') {
                return;
            }
            btn.dataset.bound = '1';
            btn.addEventListener('click', async (event) => {
                event.preventDefault();
                event.stopPropagation();
                const conversaId = Number(btn.getAttribute('data-conversa-id') || 0);
                if (conversaId > 0) {
                    await openCadastrarContatoModal(conversaId);
                }
            });
        });

        listEl.querySelectorAll('.cm-conversa-item').forEach((el) => {
            el.addEventListener('click', async () => {
                await openConversa(Number(el.dataset.id), true);
                if (window.bootstrap && window.matchMedia('(max-width: 991.98px)').matches) {
                    const canvas = document.getElementById('cmConversasCanvas');
                    if (canvas && canvas.classList.contains('show')) {
                        const offcanvas = window.bootstrap.Offcanvas.getOrCreateInstance(canvas);
                        offcanvas.hide();
                    }
                }
            });
        });
    };

    const renderConversaList = (items, preserveScrollTop) => {
        if (!Array.isArray(items) || items.length === 0) {
            listEl.innerHTML = `
                <div class="cm-empty-state">
                    <i class="bi bi-search"></i>
                    <p class="mb-0">Nenhuma conversa encontrada com os filtros atuais.</p>
                </div>
            `;
            state.renderedActiveConversationId = null;
            updateConversationCounters([]);
            return;
        }

        listEl.innerHTML = items.map(renderConversaItem).join('');
        bindConversaListClicks();
        state.renderedActiveConversationId = state.currentConversaId;
        updateConversationCounters(items);
        if (typeof preserveScrollTop === 'number') {
            listEl.scrollTop = preserveScrollTop;
        }
    };

    const loadConversas = async (silent) => {
        const preserveScroll = listEl.scrollTop;
        updateFilterFeedback();
        if (!silent && (!Array.isArray(state.currentList) || state.currentList.length === 0)) {
            listEl.innerHTML = `
                <div class="cm-empty-state">
                    <i class="bi bi-arrow-repeat"></i>
                    <p class="mb-0">Carregando conversas...</p>
                </div>
            `;
        }

        const query = toQueryString(currentFilters());
        const url = cfg.endpointConversas + '?' + query;

        const data = await getJson(url);
        const items = sortConversasByRecency(data.items || []);
        const signature = computeListSignature(items);
        const shouldRender =
            signature !== state.listSignature
            || state.renderedActiveConversationId !== state.currentConversaId
            || !silent;

        state.currentList = items;
        state.listSignature = signature;
        if (shouldRender) {
            renderConversaList(items, preserveScroll);
        }
        setRealtimeBadge(state.streamReady ? 'live' : 'polling');
        return items;
    };

    const safeLoadConversas = async (silent) => {
        try {
            return await loadConversas(silent);
        } catch (error) {
            setRealtimeBadge('warn');
            listEl.innerHTML = `
                <div class="cm-empty-state">
                    <i class="bi bi-exclamation-triangle text-danger"></i>
                    <p class="mb-0 text-danger">${escapeHtml(error?.message || 'Nao foi possivel carregar as conversas no momento.')}</p>
                </div>
            `;
            return [];
        }
    };

    const closeMessageStream = () => {
        if (state.streamRetryTimer) {
            clearTimeout(state.streamRetryTimer);
            state.streamRetryTimer = null;
        }
        if (state.streamSource) {
            state.streamSource.close();
        }
        state.streamSource = null;
        state.streamForConversaId = null;
        state.streamReady = false;
        state.streamOpenedAt = 0;
        if (!state.authRedirectInProgress) {
            setRealtimeBadge('polling');
        }
    };

    const scheduleStreamReconnect = () => {
        if (state.streamRetryTimer) {
            clearTimeout(state.streamRetryTimer);
            state.streamRetryTimer = null;
        }
        if (!state.currentConversaId || state.currentConversaId <= 0) {
            return;
        }
        const now = Date.now();
        const delay = now < state.streamProbeBlockedUntil ? 6000 : 1500;
        state.streamRetryTimer = setTimeout(() => {
            state.streamRetryTimer = null;
            if (!document.hidden) {
                startMessageStream();
            }
        }, delay);
    };

    const startMessageStream = async () => {
        closeMessageStream();
        if (!sseEnabledByConfig || !('EventSource' in window) || !cfg.endpointConversaPrefix || !state.currentConversaId) {
            return;
        }

        syncSseDisableFromStorage();
        if (Date.now() < state.streamDisabledUntil) {
            return;
        }

        if (Date.now() < state.streamProbeBlockedUntil) {
            return;
        }

        const endpointPrefix = resolveEndpointUrl(cfg.endpointConversaPrefix);
        if (!endpointPrefix) {
            return;
        }

        const probeUrl = endpointPrefix
            + '/'
            + state.currentConversaId
            + '/stream?probe=1&_='
            + Date.now();

        try {
            await getJson(probeUrl);
        } catch (error) {
            state.streamReady = false;
            state.streamProbeBlockedUntil = Date.now() + 30000;
            console.warn('[CentralMensagens] stream indisponivel, mantendo polling incremental.', error);
            return;
        }

        const handshakeUrl = endpointPrefix
            + '/'
            + state.currentConversaId
            + '/stream?handshake=1&_='
            + Date.now();

        try {
            const handshakeResponse = await fetch(handshakeUrl, {
                cache: 'no-store',
                headers: {
                    Accept: 'text/event-stream',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            const contentType = String(handshakeResponse.headers.get('content-type') || '').toLowerCase();
            if (!handshakeResponse.ok || contentType.indexOf('text/event-stream') === -1) {
                disableSseTemporarily(10 * 60 * 1000, 'handshake sem text/event-stream (' + contentType + ')');
                throw new Error('Endpoint SSE retornou tipo invalido: ' + contentType);
            }
            await handshakeResponse.text();
        } catch (error) {
            state.streamReady = false;
            state.streamProbeBlockedUntil = Date.now() + 60 * 1000;
            console.warn('[CentralMensagens] handshake SSE falhou, mantendo polling incremental.', error);
            return;
        }

        const streamUrl = endpointPrefix
            + '/'
            + state.currentConversaId
            + '/stream?after_id='
            + encodeURIComponent(String(state.latestMessageId || 0))
            + '&_='
            + Date.now();

        const source = new EventSource(streamUrl);
        state.streamSource = source;
        state.streamForConversaId = state.currentConversaId;
        state.streamOpenedAt = Date.now();

        source.addEventListener('ready', () => {
            state.streamReady = true;
            state.streamProbeBlockedUntil = 0;
            state.streamDisabledUntil = 0;
            setRealtimeBadge('live');
            try {
                window.sessionStorage.removeItem(sseStorageKey);
            } catch (error) {
                // Ignora erro de armazenamento.
            }
        });

        source.addEventListener('mensagens', async (event) => {
            let payload = {};
            try {
                payload = JSON.parse(event.data || '{}');
            } catch (error) {
                return;
            }
            const novas = Array.isArray(payload.mensagens) ? payload.mensagens : [];
            if (novas.length > 0) {
                state.activeConversationUnread = 0;
                appendMensagens(novas);
                updateLatestMessageId();
                await safeLoadConversas(true);
            }
            if (payload.conversa && threadStatusBadge) {
                applyThreadStatusBadge(payload.conversa.status || threadStatusBadge.textContent);
            }
        });

        source.addEventListener('error', () => {
            const openedAgoMs = Date.now() - Number(state.streamOpenedAt || Date.now());
            if (!state.streamReady && openedAgoMs < 4000) {
                disableSseTemporarily(15 * 60 * 1000, 'stream abortado antes de ready (possivel MIME/text-html)');
            }
            state.streamReady = false;
            state.streamProbeBlockedUntil = Math.max(Date.now() + 25000, state.streamDisabledUntil);
            setRealtimeBadge('warn');
            if (state.streamSource === source) {
                source.close();
                state.streamSource = null;
            }
            console.warn('[CentralMensagens] stream SSE interrompido, fallback para polling incremental.');
            scheduleStreamReconnect();
        });

        source.addEventListener('close', () => {
            if (state.streamSource === source) {
                source.close();
                state.streamSource = null;
            }
            state.streamReady = false;
            setRealtimeBadge('polling');
            scheduleStreamReconnect();
        });
    };

    const applyThreadStatusBadge = (statusValue) => {
        if (!threadStatusBadge) {
            return;
        }
        const status = String(statusValue || 'aberta').toLowerCase();
        threadStatusBadge.textContent = normalizeStatusLabel(status);
        threadStatusBadge.classList.remove('bg-secondary', 'bg-success', 'bg-warning', 'text-dark', 'bg-primary', 'bg-danger');
        if (status === 'resolvida') {
            threadStatusBadge.classList.add('bg-success');
            return;
        }
        if (status === 'aguardando') {
            threadStatusBadge.classList.add('bg-warning', 'text-dark');
            return;
        }
        if (status === 'arquivada') {
            threadStatusBadge.classList.add('bg-secondary');
            return;
        }
        threadStatusBadge.classList.add('bg-primary');
    };

    const updateThreadHeader = (conversa) => {
        const nome = conversa?.cliente_nome
            || conversa?.contato_nome
            || conversa?.contato_perfil_nome
            || conversa?.nome_contato
            || conversa?.telefone
            || 'Conversa';
        const responsavel = String(conversa?.responsavel_nome || '').trim();
        const telefone = String(conversa?.telefone || '').trim();
        threadTitle.textContent = nome;
        threadSubtitle.textContent = [telefone, responsavel ? ('Responsavel: ' + responsavel) : 'Nao atribuida']
            .filter(Boolean)
            .join(' • ');
        applyThreadStatusBadge(conversa?.status || 'aberta');
    };

    const unreadSeparatorIndex = () => {
        if (!state.activeConversationUnread || state.activeConversationUnread <= 0) {
            return -1;
        }
        return Math.max(0, state.mensagens.length - state.activeConversationUnread);
    };

    const dayKeyFromValue = (value) => {
        const dt = parseDate(value);
        if (!dt) {
            return '';
        }
        const year = dt.getFullYear();
        const month = String(dt.getMonth() + 1).padStart(2, '0');
        const day = String(dt.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    };

    const formatDayLabel = (dayKey) => {
        if (!dayKey) {
            return '';
        }
        const today = new Date();
        const y = today.getFullYear();
        const m = String(today.getMonth() + 1).padStart(2, '0');
        const d = String(today.getDate()).padStart(2, '0');
        const todayKey = `${y}-${m}-${d}`;
        if (dayKey === todayKey) {
            return 'Hoje';
        }
        const yesterday = new Date(today.getTime() - 24 * 60 * 60 * 1000);
        const y2 = yesterday.getFullYear();
        const m2 = String(yesterday.getMonth() + 1).padStart(2, '0');
        const d2 = String(yesterday.getDate()).padStart(2, '0');
        if (dayKey === `${y2}-${m2}-${d2}`) {
            return 'Ontem';
        }
        const dt = parseDate(dayKey + ' 00:00:00');
        if (!dt) {
            return dayKey;
        }
        return dt.toLocaleDateString('pt-BR', { day: '2-digit', month: 'short', year: 'numeric' });
    };

    const resolveOutboundDelivery = (msg) => {
        const status = String(msg?.status || '').toLowerCase();
        if (String(msg?.erro || '').trim() !== '' || status.includes('fail') || status.includes('erro')) {
            return { className: 'is-failed', icon: 'bi-exclamation-circle', label: 'Falha' };
        }
        if (msg?.lida_em || status.includes('read') || status.includes('lida')) {
            return { className: 'is-read', icon: 'bi-check2-all', label: 'Lida' };
        }
        if (msg?.recebida_em || status.includes('deliver') || status.includes('entreg')) {
            return { className: '', icon: 'bi-check2-all', label: 'Entregue' };
        }
        if (msg?.enviada_em || status.includes('send') || status.includes('enviad')) {
            return { className: '', icon: 'bi-check2', label: 'Enviada' };
        }
        return { className: '', icon: 'bi-check2', label: 'Enviada' };
    };

    const messageBodyText = (msg) => {
        const text = String(msg?.mensagem || '').trim();
        if (text !== '') {
            return escapeHtml(text).replace(/\n/g, '<br>');
        }
        if (detectContentType(msg) !== 'texto') {
            return '<span class="text-muted">[mensagem sem texto]</span>';
        }
        return '<span class="text-muted">[mensagem vazia]</span>';
    };

    const mediaCaptionLabel = (msg) => {
        const arquivo = String(msg?.arquivo || msg?.anexo_path || '');
        if (!arquivo) return '';
        const parts = arquivo.split('/');
        return parts[parts.length - 1] || arquivo;
    };

    const mediaSizeLabel = (msg) => {
        const direct = Number(msg?.tamanho_bytes || msg?.arquivo_tamanho || 0);
        if (Number.isFinite(direct) && direct > 0) {
            return bytesToHuman(direct);
        }

        const sources = [msg?.payload, msg?.resposta_api];
        for (const source of sources) {
            if (!source) {
                continue;
            }
            try {
                const parsed = typeof source === 'string' ? JSON.parse(source) : source;
                const size = Number(
                    parsed?.media_size_bytes
                    || parsed?.file_size
                    || parsed?.size
                    || parsed?.data?.media_size_bytes
                    || 0
                );
                if (Number.isFinite(size) && size > 0) {
                    return bytesToHuman(size);
                }
            } catch (error) {
                // payload pode nao ser JSON valido.
            }
        }

        return '';
    };

    function messagePayload(msg) {
        const sources = [msg?.payload, msg?.resposta_api];
        for (const source of sources) {
            if (!source) {
                continue;
            }
            if (typeof source === 'object' && source !== null) {
                return source;
            }
            if (typeof source === 'string') {
                try {
                    const parsed = JSON.parse(source);
                    if (parsed && typeof parsed === 'object') {
                        return parsed;
                    }
                } catch (error) {
                    // payload pode nao estar em JSON valido; ignora e segue.
                }
            }
        }
        return {};
    }
    // Compatibilidade defensiva para scripts legados que possam chamar helper global.
    if (typeof window.messagePayload !== 'function') {
        window.messagePayload = messagePayload;
    }

    const resolveMessageOrigem = (msg, payload) => {
        const raw = String(msg?.origem || '').trim().toLowerCase();
        if (raw === 'sistema' || raw === 'externo' || raw === 'chatbot') {
            return raw;
        }

        const outbound = String(msg?.direcao || '').toLowerCase() === 'outbound';
        const enviadaPorBot = Number(msg?.enviada_por_bot || 0) === 1;
        const tipoMensagem = String(msg?.tipo_mensagem || '').toLowerCase();

        if (
            enviadaPorBot
            || tipoMensagem.includes('chatbot')
            || tipoMensagem.includes('bot')
        ) {
            return 'chatbot';
        }

        if (outbound) {
            if (tipoMensagem === 'outbound_externo' || tipoMensagem.includes('externo')) {
                return 'externo';
            }
            return 'sistema';
        }

        return 'externo';
    };

    const renderMessageOrigin = (msg, payload) => {
        const outbound = String(msg?.direcao || '').toLowerCase() === 'outbound';
        const origem = resolveMessageOrigem(msg, payload);

        if (!outbound) {
            return {
                origem,
                labelVia: 'via app externo',
                remetente: 'Cliente',
                icon: 'bi-person-circle',
                viaClass: 'cm-via-externo',
            };
        }

        switch (origem) {
            case 'sistema':
                return {
                    origem,
                    labelVia: 'via sistema',
                    remetente: String(msg?.usuario_nome || '').trim() || 'Sistema',
                    icon: 'bi-person-badge',
                    viaClass: 'cm-via-sistema',
                };
            case 'chatbot':
                return {
                    origem,
                    labelVia: 'chatbot',
                    remetente: 'Chatbot',
                    icon: 'bi-robot',
                    viaClass: 'cm-via-chatbot',
                };
            case 'externo':
            default: {
                const gatewayAccount = getGatewayAccountNumber();
                return {
                    origem: 'externo',
                    labelVia: 'via app externo',
                    remetente: gatewayAccount || 'Numero externo',
                    icon: 'bi-phone',
                    viaClass: 'cm-via-externo',
                };
            }
        }
    };

    const renderMedia = (msg, outbound) => {
        const mediaAvailable = Number(msg?.arquivo_disponivel ?? 1) === 1;
        const arquivo = String(msg?.arquivo || msg?.anexo_path || '').trim();
        if (!mediaAvailable) {
            const rawMissingName = String(msg?.arquivo_original || msg?.anexo_path_original || msg?.arquivo || msg?.anexo_path || '').trim();
            const filename = escapeHtml(rawMissingName || mediaCaptionLabel(msg));
            return `
                <div class="alert alert-warning py-2 px-3 mt-2 mb-0 small">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    Arquivo indisponivel no servidor.
                    ${filename ? `<span class="d-block text-truncate mt-1">${filename}</span>` : ''}
                </div>
            `;
        }

        if (!arquivo) {
            return '';
        }

        const type = detectContentType(msg);
        const url = resolveArquivoUrl(arquivo, msg.id || Date.now());
        const filename = escapeHtml(mediaCaptionLabel(msg));
        const mime = escapeHtml(String(msg?.mime_type || '').toLowerCase());
        const sizeLabel = escapeHtml(mediaSizeLabel(msg));
        const shellClass = outbound ? 'outbound' : 'inbound';

        if (type === 'imagem') {
            const imageIndex = state.imageItems.push({
                src: url,
                caption: filename,
            }) - 1;
            return `
                <div class="mt-2">
                    <button type="button" class="btn p-0 border-0 bg-transparent cm-media-image-link" data-cm-image-index="${imageIndex}">
                        <img src="${url}" alt="${filename}" class="cm-media-image-thumb">
                    </button>
                </div>
            `;
        }

        if (type === 'video') {
            return `
                <div class="cm-video-box mt-2">
                    <video controls preload="metadata" src="${url}" playsinline></video>
                </div>
                <div class="small text-muted mt-1">
                    ${filename}
                    ${sizeLabel ? ` <span class="ms-1">(${sizeLabel})</span>` : ''}
                </div>
            `;
        }

        if (type === 'audio') {
            return `
                <div class="mt-2">
                    <div class="cm-audio-shell ${shellClass}" data-audio-shell>
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" class="cm-audio-play" data-audio-play aria-label="Reproduzir audio">
                                <i class="bi bi-play-fill"></i>
                            </button>
                            <input type="range" class="cm-audio-range" min="0" max="0" step="0.1" value="0" data-audio-range>
                            <span class="cm-audio-time" data-audio-time>0:00</span>
                        </div>
                        <audio preload="metadata" src="${url}" data-audio></audio>
                    </div>
                    <div class="small text-muted mt-1">
                        ${filename}
                        ${sizeLabel ? ` <span class="ms-1">(${sizeLabel})</span>` : ''}
                    </div>
                </div>
            `;
        }

        const label = type === 'pdf' ? 'Abrir PDF' : 'Abrir anexo';
        return `
            <div class="cm-file-card mt-2">
                <div class="d-flex align-items-center justify-content-between gap-2">
                    <div class="text-truncate">
                        <i class="bi ${iconByType(type)} me-1"></i>
                        <span class="fw-semibold">${filename}</span>
                        ${mime ? `<div class="small text-muted">${mime}</div>` : ''}
                        ${sizeLabel ? `<div class="small text-muted">${sizeLabel}</div>` : ''}
                    </div>
                    <div class="d-flex gap-1">
                        <a href="${url}" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener">
                            ${label}
                        </a>
                        <a href="${url}" class="btn btn-sm btn-outline-secondary" download>
                            Baixar
                        </a>
                    </div>
                </div>
            </div>
        `;
    };

    const renderMensagem = (msg, idx, opts) => {
        const options = opts || {};
        const outbound = String(msg?.direcao || '').toLowerCase() === 'outbound';
        const payloadFn = (typeof messagePayload === 'function')
            ? messagePayload
            : ((typeof window.messagePayload === 'function') ? window.messagePayload : (() => ({})));
        const payload = payloadFn(msg);
        const origemData = renderMessageOrigin(msg, payload);
        const when = formatDateTime(msg?.created_at || msg?.enviada_em || msg?.recebida_em || '');
        const body = messageBodyText(msg);
        const media = renderMedia(msg, outbound);
        const rawMsg = encodeURIComponent(String(msg?.mensagem || ''));
        const replyButton = (!outbound && String(msg?.mensagem || '').trim() !== '')
            ? `<button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 cm-reply-btn" data-reply="${rawMsg}">Responder</button>`
            : '';

        const unreadIdx = unreadSeparatorIndex();
        const unreadSep = (!outbound && unreadIdx === idx)
            ? '<div class="cm-msg-unread-sep"><span>Mensagens nao lidas</span></div>'
            : '';
        const messageStatus = outbound ? resolveOutboundDelivery(msg) : null;
        const dateSeparator = options.showDateSeparator
            ? `<div class="cm-day-separator"><span>${escapeHtml(formatDayLabel(options.dayKey || ''))}</span></div>`
            : '';
        const rowClass = ['cm-msg-row', outbound ? 'outbound' : 'inbound', options.isNew ? 'cm-msg-row-new' : '']
            .filter(Boolean)
            .join(' ');

        return `
            ${dateSeparator}
            ${unreadSep}
            <div class="${rowClass}" data-message-id="${Number(msg?.id || 0)}">
                <div class="cm-bubble ${outbound ? 'outbound' : 'inbound'} ${outbound ? ('cm-origin-' + origemData.origem) : ''}">
                    <div class="cm-msg-head">
                        <span class="cm-msg-origin"><i class="bi ${origemData.icon}"></i>${escapeHtml(origemData.remetente)}</span>
                        ${outbound ? `<span class="cm-msg-via badge ${origemData.viaClass}">${escapeHtml(origemData.labelVia)}</span>` : ''}
                        ${replyButton}
                    </div>
                    <div>${body}</div>
                    ${media}
                    <div class="cm-msg-meta">
                        <span>${escapeHtml(when)}</span>
                        ${outbound && messageStatus ? `<span class="cm-msg-status ${messageStatus.className}"><i class="bi ${messageStatus.icon}"></i>${escapeHtml(messageStatus.label)}</span>` : '<span class="cm-msg-status"><i class="bi bi-reply-fill"></i>Recebida</span>'}
                    </div>
                </div>
            </div>
        `;
    };

    const isNearBottom = (el) => (el.scrollHeight - el.scrollTop - el.clientHeight) < 100;

    const updateJumpBottomVisibility = () => {
        if (!jumpBottomBtn || !threadMessages) {
            return;
        }
        const distance = threadMessages.scrollHeight - threadMessages.scrollTop - threadMessages.clientHeight;
        jumpBottomBtn.classList.toggle('d-none', distance < 260);
    };

    const scrollThreadToBottom = (focusComposer) => {
        if (!threadMessages) {
            return;
        }
        threadMessages.scrollTop = threadMessages.scrollHeight;
        requestAnimationFrame(() => {
            threadMessages.scrollTop = threadMessages.scrollHeight;
            updateJumpBottomVisibility();
        });
        if (focusComposer && msgInput) {
            msgInput.focus();
        }
    };

    const renderMensagens = (options) => {
        const opts = options || {};
        const forceBottom = !!opts.forceBottom;
        const keepBottom = forceBottom ? true : isNearBottom(threadMessages);
        const newMessageIds = new Set(Array.isArray(opts.newMessageIds) ? opts.newMessageIds.map((id) => Number(id || 0)) : []);
        state.imageItems = [];

        if (!Array.isArray(state.mensagens) || state.mensagens.length === 0) {
            threadMessages.innerHTML = `
                <div class="cm-empty-state cm-empty-state-sm">
                    <i class="bi bi-chat-square-text"></i>
                    <p class="mb-0">Sem mensagens nesta conversa.</p>
                </div>
            `;
            updateJumpBottomVisibility();
            return;
        }

        let prevDayKey = '';
        const html = state.mensagens.map((msg, idx) => {
            try {
                const dayKey = dayKeyFromValue(msg?.created_at || msg?.enviada_em || msg?.recebida_em || '');
                const showDateSeparator = dayKey !== '' && dayKey !== prevDayKey;
                prevDayKey = dayKey || prevDayKey;
                const msgId = Number(msg?.id || 0);
                return renderMensagem(msg, idx, {
                    showDateSeparator,
                    dayKey,
                    isNew: msgId > 0 && newMessageIds.has(msgId),
                });
            } catch (error) {
                console.error('[CentralMensagens] falha ao renderizar mensagem', {
                    mensagem_id: Number(msg?.id || 0),
                    conversa_id: state.currentConversaId,
                    detail: error?.message || error,
                });
                const outbound = String(msg?.direcao || '').toLowerCase() === 'outbound';
                const when = formatDateTime(msg?.created_at || msg?.enviada_em || msg?.recebida_em || '');
                const safeText = escapeHtml(String(msg?.mensagem || '[mensagem indisponivel]')).replace(/\n/g, '<br>');
                return `
                    <div class="cm-msg-row ${outbound ? 'outbound' : 'inbound'}" data-message-id="${Number(msg?.id || 0)}">
                        <div class="cm-bubble ${outbound ? 'outbound' : 'inbound'}">
                            <div>${safeText}</div>
                            <div class="cm-msg-meta">
                                <span>${escapeHtml(when)}</span>
                                <span class="cm-msg-status"><i class="bi bi-shield-exclamation"></i>Fallback</span>
                            </div>
                        </div>
                    </div>
                `;
            }
        }).join('');
        threadMessages.innerHTML = html;
        bindThreadActions();
        bindAudioPlayers(threadMessages);

        if (keepBottom) {
            scrollThreadToBottom(false);
        } else {
            updateJumpBottomVisibility();
        }
    };

    const appendMensagens = (novas) => {
        if (!Array.isArray(novas) || novas.length === 0) {
            return;
        }

        const keepBottom = isNearBottom(threadMessages);
        const newIds = novas.map((m) => Number(m?.id || 0)).filter((id) => id > 0);
        state.mensagens = state.mensagens.concat(novas);
        renderMensagens({ newMessageIds: newIds });

        if (keepBottom) {
            scrollThreadToBottom(false);
        } else {
            updateJumpBottomVisibility();
        }
    };

    const formatAudioTime = (seconds) => {
        const s = Math.max(0, Math.floor(Number(seconds || 0)));
        const min = Math.floor(s / 60);
        const sec = s % 60;
        return min + ':' + String(sec).padStart(2, '0');
    };

    const bindAudioPlayers = (scope) => {
        if (!scope) return;
        const shells = scope.querySelectorAll('[data-audio-shell]');
        shells.forEach((shell) => {
            if (shell.dataset.bound === '1') {
                return;
            }
            shell.dataset.bound = '1';

            const audio = shell.querySelector('[data-audio]');
            const playBtn = shell.querySelector('[data-audio-play]');
            const range = shell.querySelector('[data-audio-range]');
            const timeEl = shell.querySelector('[data-audio-time]');
            if (!audio || !playBtn || !range || !timeEl) {
                return;
            }

            const updatePlayIcon = (isPlaying) => {
                playBtn.innerHTML = isPlaying
                    ? '<i class="bi bi-pause-fill"></i>'
                    : '<i class="bi bi-play-fill"></i>';
            };

            const updateTime = () => {
                const current = Number(audio.currentTime || 0);
                const duration = Number(audio.duration || 0);
                range.value = String(current);
                if (duration > 0) {
                    range.max = String(duration);
                    timeEl.textContent = formatAudioTime(current) + ' / ' + formatAudioTime(duration);
                } else {
                    timeEl.textContent = formatAudioTime(current);
                }
            };

            audio.addEventListener('loadedmetadata', updateTime);
            audio.addEventListener('timeupdate', updateTime);
            audio.addEventListener('ended', () => {
                updatePlayIcon(false);
                updateTime();
            });
            audio.addEventListener('pause', () => updatePlayIcon(false));
            audio.addEventListener('play', () => updatePlayIcon(true));

            playBtn.addEventListener('click', () => {
                if (audio.paused) {
                    scope.querySelectorAll('[data-audio]').forEach((other) => {
                        if (other !== audio && !other.paused) {
                            other.pause();
                        }
                    });
                    audio.play().catch(() => {});
                } else {
                    audio.pause();
                }
            });

            range.addEventListener('input', () => {
                const target = Number(range.value || 0);
                if (Number.isFinite(target)) {
                    audio.currentTime = target;
                    updateTime();
                }
            });
        });
    };

    const showImageByIndex = (idx) => {
        if (!imageModalImg || !imageModalEl) {
            return;
        }
        if (!Array.isArray(state.imageItems) || state.imageItems.length === 0) {
            return;
        }
        const max = state.imageItems.length - 1;
        const safeIdx = Math.max(0, Math.min(max, idx));
        state.imageIndex = safeIdx;

        const item = state.imageItems[safeIdx];
        imageModalImg.src = item?.src || '';
        imageModalImg.alt = item?.caption || 'Imagem';
        if (imagePrevBtn) {
            imagePrevBtn.classList.toggle('d-none', safeIdx <= 0);
        }
        if (imageNextBtn) {
            imageNextBtn.classList.toggle('d-none', safeIdx >= max);
        }
    };

    const openImageModal = (idx) => {
        if (!imageModalInstance) {
            return;
        }
        showImageByIndex(idx);
        imageModalInstance.show();
    };

    const bindImageModalActions = () => {
        if (imagePrevBtn) {
            imagePrevBtn.addEventListener('click', () => showImageByIndex(state.imageIndex - 1));
        }
        if (imageNextBtn) {
            imageNextBtn.addEventListener('click', () => showImageByIndex(state.imageIndex + 1));
        }
        if (imageModalEl) {
            imageModalEl.addEventListener('hidden.bs.modal', () => {
                if (imageModalImg) {
                    imageModalImg.removeAttribute('src');
                }
            });
        }
    };

    const insertReplyQuote = (text) => {
        const trecho = text.length > 160 ? (text.substring(0, 160) + '...') : text;
        const quote = `Respondendo cliente: "${trecho}"\n`;
        msgInput.value = msgInput.value ? (msgInput.value + '\n' + quote) : quote;
        msgInput.focus();
    };

    const bindThreadActions = () => {
        threadMessages.querySelectorAll('.cm-media-image-link').forEach((btn) => {
            if (btn.dataset.bound === '1') {
                return;
            }
            btn.dataset.bound = '1';
            btn.addEventListener('click', () => {
                const idx = Number(btn.dataset.cmImageIndex || -1);
                if (idx >= 0) {
                    openImageModal(idx);
                }
            });
        });

        threadMessages.querySelectorAll('.cm-reply-btn').forEach((btn) => {
            if (btn.dataset.bound === '1') {
                return;
            }
            btn.dataset.bound = '1';
            btn.addEventListener('click', () => {
                const raw = btn.getAttribute('data-reply') || '';
                let text = '';
                try {
                    text = decodeURIComponent(raw);
                } catch (error) {
                    text = raw;
                }
                insertReplyQuote(text);
            });
        });
    };

    const contextMetaPayload = () => {
        const metaBase = state.currentContext?.meta || {};
        const statusEl = document.getElementById('contextStatusSelect');
        const responsavelEl = document.getElementById('contextResponsavelSelect');
        const prioridadeEl = document.getElementById('contextPrioridadeSelect');
        const automacaoEl = document.getElementById('contextAutomacaoAtiva');
        const aguardandoEl = document.getElementById('contextAguardandoHumano');

        const status = String(statusEl?.value || metaBase.status || 'aberta');
        const responsavelId = Number(responsavelEl?.value || metaBase.responsavel_id || 0);
        const prioridade = String(prioridadeEl?.value || metaBase.prioridade || 'normal');
        const automacaoAtiva = automacaoEl ? (automacaoEl.checked ? 1 : 0) : Number(metaBase.automacao_ativa || 0);
        const aguardandoHumano = aguardandoEl ? (aguardandoEl.checked ? 1 : 0) : Number(metaBase.aguardando_humano || 0);
        const tagsChecked = Array.from(document.querySelectorAll('#contextTagWrap .cm-tag-check:checked'))
            .map((el) => Number(el.value || 0))
            .filter((id) => id > 0);
        const tags = tagsChecked.length
            ? tagsChecked
            : (Array.isArray(metaBase.tags) ? metaBase.tags.map((id) => Number(id || 0)).filter((id) => id > 0) : []);

        return {
            status,
            responsavel_id: responsavelId > 0 ? responsavelId : '',
            prioridade,
            automacao_ativa: automacaoAtiva,
            aguardando_humano: aguardandoHumano,
            tag_ids: JSON.stringify(tags),
        };
    };

    const bindContextActions = () => {
        const btnVincular = document.getElementById('btnVincularOs');
        const btnSalvarMeta = document.getElementById('btnSalvarMetaConversa');
        const checkBot = document.getElementById('contextAutomacaoAtiva');
        const checkHumano = document.getElementById('contextAguardandoHumano');

        btnVincular?.addEventListener('click', async () => {
            const osId = Number(document.getElementById('contextOsSelect')?.value || 0);
            if (!state.currentConversaId || !osId) {
                await swal({ icon: 'warning', title: 'Vinculo incompleto', text: 'Selecione uma OS para vincular.' });
                return;
            }
            try {
                await postForm(cfg.endpointVincularOs, {
                    conversa_id: state.currentConversaId,
                    os_id: osId,
                });
                await swal({ icon: 'success', title: 'OS vinculada', text: 'Conversa vinculada com sucesso.' });
                await openConversa(state.currentConversaId, false);
                await safeLoadConversas(true);
            } catch (error) {
                await swal({ icon: 'error', title: 'Falha ao vincular', text: error.message || 'Erro inesperado.' });
            }
        });

        btnSalvarMeta?.addEventListener('click', async () => {
            if (!state.currentConversaId) {
                await swal({ icon: 'warning', title: 'Conversa nao selecionada', text: 'Abra uma conversa para atualizar o contexto.' });
                return;
            }
            try {
                await postForm(cfg.endpointAtualizarMeta, {
                    conversa_id: state.currentConversaId,
                    ...contextMetaPayload(),
                });
                await swal({ icon: 'success', title: 'Atualizado', text: 'Contexto da conversa atualizado com sucesso.' });
                msgInput.value = '';
                documentoSelect.value = '';
                if (composeMetaPanel) composeMetaPanel.classList.add('d-none');
                state.selectedFile = null;
                if (anexoInput) anexoInput.value = '';
                renderAnexoSelection(null);
                
                await openConversa(state.currentConversaId, false);
                await safeLoadConversas(true);
            } catch (error) {
                await swal({ icon: 'error', title: 'Falha ao atualizar', text: error.message || 'Erro inesperado.' });
            }
        });

        checkBot?.addEventListener('change', () => {
            if (checkBot.checked && checkHumano) {
                checkHumano.checked = false;
            }
        });
        checkHumano?.addEventListener('change', () => {
            if (checkHumano.checked && checkBot) {
                checkBot.checked = false;
            }
        });
    };

    const quickUpdateMeta = async (partialPayload, successMessage) => {
        if (!state.currentConversaId) {
            await swal({
                icon: 'warning',
                title: 'Selecione uma conversa',
                text: 'Abra uma conversa antes de executar esta acao.',
            });
            return false;
        }

        const basePayload = contextMetaPayload();
        const payload = {
            ...basePayload,
            ...(partialPayload || {}),
            conversa_id: state.currentConversaId,
        };

        await postForm(cfg.endpointAtualizarMeta, payload);
        if (successMessage) {
            await swal({
                icon: 'success',
                title: 'Conversa atualizada',
                text: successMessage,
                timer: 1500,
                showConfirmButton: false,
            });
        }
        await openConversa(state.currentConversaId, false);
        await safeLoadConversas(true);
        return true;
    };

    const renderContexto = (ctx) => {
        const cliente = ctx?.cliente || null;
        const contato = ctx?.contato || null;
        const osList = Array.isArray(ctx?.os) ? ctx.os : [];
        const docs = Array.isArray(ctx?.documentos) ? ctx.documentos : [];
        const followups = Array.isArray(ctx?.followups) ? ctx.followups : [];
        const osVinculadas = Array.isArray(ctx?.os_vinculadas) ? ctx.os_vinculadas : [];
        const meta = ctx?.meta || {};
        const statusAtual = String(meta.status || 'aberta');
        const responsavelAtual = Number(meta.responsavel_id || 0);
        const statusOptions = Array.isArray(meta.status_options) && meta.status_options.length
            ? meta.status_options
            : ['aberta', 'aguardando', 'resolvida', 'arquivada'];
        const responsaveis = Array.isArray(meta.responsaveis) ? meta.responsaveis : [];
        const tagCatalogo = Array.isArray(meta.tag_catalogo) ? meta.tag_catalogo : [];
        const tagsSelecionadas = (Array.isArray(meta.tags) ? meta.tags : []).map((v) => Number(v));

        const contatoHtml = contato ? `
            <div class="mb-2">
                <div class="fw-semibold">${escapeHtml(contato.nome || contato.whatsapp_nome_perfil || 'Contato sem nome')}</div>
                <div>${escapeHtml(contato.telefone || contato.telefone_normalizado || '')}</div>
                ${contato.email ? `<div>${escapeHtml(contato.email)}</div>` : ''}
                ${Number(contato.cliente_id || 0) > 0
                    ? '<span class="badge text-bg-primary mt-1">Vinculado a cliente</span>'
                    : '<span class="badge text-bg-info text-dark mt-1">Cliente novo</span>'}
            </div>
        ` : '<div class="text-muted mb-2">Contato nao identificado automaticamente.</div>';

        const clienteHtml = cliente ? `
            <div class="mb-2">
                <div class="fw-semibold">${escapeHtml(cliente.nome_razao || '')}</div>
                <div>${escapeHtml(cliente.telefone1 || '')}</div>
                <div>${escapeHtml(cliente.email || '')}</div>
            </div>
        ` : '<div class="text-muted mb-2">Cliente nao identificado automaticamente.</div>';

        const osHtml = osList.length
            ? osList.map((os) => `<option value="${os.id}">OS ${escapeHtml(os.numero_os)} - ${escapeHtml(os.status || '-')}</option>`).join('')
            : '<option value="">Sem OS vinculadas</option>';

        const docsHtml = docs.length
            ? docs.map((d) => `<option value="${d.id}">${escapeHtml((d.tipo_documento || 'documento') + ' - ' + (d.arquivo || ''))}</option>`).join('')
            : '';
        if (documentoSelect) {
            documentoSelect.innerHTML = '<option value="">Sem PDF</option>' + docsHtml;
        }

        const vinculadasHtml = osVinculadas.length
            ? osVinculadas.map((v) => `<li>OS ${escapeHtml(v.numero_os || String(v.os_id))} (${escapeHtml(v.status || '-')})</li>`).join('')
            : '<li class="text-muted">Sem vinculos.</li>';

        const followupsHtml = followups.length
            ? followups.map((f) => `<li>${escapeHtml(f.titulo || '-')}: ${escapeHtml(formatDateTime(f.data_prevista || ''))}</li>`).join('')
            : '<li class="text-muted">Sem follow-ups pendentes.</li>';

        const clienteUrl = cliente?.id ? (cfg.urlClienteVisualizarPrefix + '/' + cliente.id) : '';
        const osPrincipalId = Number((osVinculadas[0] && osVinculadas[0].os_id) || (osList[0] && osList[0].id) || 0);
        const osUrl = osPrincipalId > 0 ? (cfg.urlOsVisualizarPrefix + '/' + osPrincipalId) : '';
        const novaOsUrl = (() => {
            const raw = String(cfg.urlOsNova || '').trim();
            if (!raw) {
                return '#';
            }
            try {
                const target = new URL(raw, window.location.origin);
                if (state.currentConversaId) {
                    target.searchParams.set('origem_conversa_id', String(state.currentConversaId));
                }
                const contatoId = Number(contato?.id || 0);
                if (contatoId > 0) {
                    target.searchParams.set('origem_contato_id', String(contatoId));
                }
                const clienteId = Number(cliente?.id || 0);
                if (clienteId > 0) {
                    target.searchParams.set('cliente_id', String(clienteId));
                }
                const telefone = String(contato?.telefone_normalizado || contato?.telefone || '').replace(/\D+/g, '');
                if (telefone) {
                    target.searchParams.set('telefone', telefone);
                }
                const nomeHint = String(contato?.nome || contato?.whatsapp_nome_perfil || '').trim();
                if (nomeHint) {
                    target.searchParams.set('nome_hint', nomeHint);
                }
                return target.toString();
            } catch (error) {
                return raw;
            }
        })();

        const statusOptionsHtml = statusOptions.map((s) => {
            const selected = statusAtual === s ? 'selected' : '';
            const label = s ? (s.charAt(0).toUpperCase() + s.slice(1)) : s;
            return `<option value="${escapeHtml(s)}" ${selected}>${escapeHtml(label)}</option>`;
        }).join('');

        const responsaveisHtml = ['<option value="">Nao atribuido</option>'].concat(
            responsaveis.map((u) => {
                const id = Number(u.id || 0);
                const selected = id === responsavelAtual ? 'selected' : '';
                return `<option value="${id}" ${selected}>${escapeHtml(u.nome || ('Usuario #' + id))}</option>`;
            })
        ).join('');

        const automacaoAtiva = Number(meta.automacao_ativa ?? 1) === 1;
        const aguardandoHumano = Number(meta.aguardando_humano || 0) === 1;
        const prioridadeAtual = String(meta.prioridade || 'normal').toLowerCase();
        const prioridadeOptionsHtml = ['baixa', 'normal', 'alta', 'urgente']
            .map((p) => `<option value="${p}" ${p === prioridadeAtual ? 'selected' : ''}>${escapeHtml(p.charAt(0).toUpperCase() + p.slice(1))}</option>`)
            .join('');

        const tagsHtml = tagCatalogo.length
            ? tagCatalogo.map((t) => {
                const tagId = Number(t.id || 0);
                const checked = tagsSelecionadas.includes(tagId) ? 'checked' : '';
                const color = t.cor ? `style="background:${escapeHtml(t.cor)}22;border-color:${escapeHtml(t.cor)}"` : '';
                return `
                    <label class="form-check form-check-inline border rounded px-2 py-1 me-1 mb-1 small" ${color}>
                        <input class="form-check-input me-1 cm-tag-check" type="checkbox" value="${tagId}" ${checked}>
                        <span class="form-check-label">${escapeHtml(t.nome || 'Tag')}</span>
                    </label>
                `;
            }).join('')
            : '<div class="text-muted small">Sem tags CRM cadastradas.</div>';

        contextoEl.innerHTML = `
            <div class="cm-context-section">
                <div class="cm-context-section-title">Contato (agenda)</div>
                ${contatoHtml}
            </div>
            <div class="cm-context-section">
                <div class="cm-context-section-title">Cliente ERP</div>
                ${clienteHtml}
            </div>
            <div class="cm-context-section">
                <div class="cm-context-section-title">Gestao da conversa</div>
                <div class="mb-2">
                    <label class="form-label form-label-sm mb-1">Status da conversa</label>
                    <select class="form-select form-select-sm" id="contextStatusSelect">${statusOptionsHtml}</select>
                </div>
                <div class="mb-2">
                    <label class="form-label form-label-sm mb-1">Responsavel</label>
                    <select class="form-select form-select-sm" id="contextResponsavelSelect">${responsaveisHtml}</select>
                </div>
                <div class="mb-2">
                    <label class="form-label form-label-sm mb-1">Prioridade</label>
                    <select class="form-select form-select-sm" id="contextPrioridadeSelect">${prioridadeOptionsHtml}</select>
                </div>
                <div class="mb-2 d-flex flex-wrap gap-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="contextAutomacaoAtiva" ${automacaoAtiva ? 'checked' : ''}>
                        <label class="form-check-label small" for="contextAutomacaoAtiva">Bot ativo</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="contextAguardandoHumano" ${aguardandoHumano ? 'checked' : ''}>
                        <label class="form-check-label small" for="contextAguardandoHumano">Aguardando humano</label>
                    </div>
                </div>
                <div class="mb-2">
                    <label class="form-label form-label-sm mb-1">Tags</label>
                    <div id="contextTagWrap">${tagsHtml}</div>
                </div>
                <button class="btn btn-sm btn-outline-primary w-100" id="btnSalvarMetaConversa">Salvar contexto</button>
            </div>
            <div class="cm-context-section">
                <div class="cm-context-section-title">Vinculo de OS</div>
                <div class="d-flex gap-1 mb-2">
                    <select class="form-select form-select-sm" id="contextOsSelect">${osHtml}</select>
                    <button class="btn btn-sm btn-outline-primary" id="btnVincularOs">Vincular</button>
                </div>
                <div class="small fw-semibold mb-1">OS vinculadas</div>
                <ul class="cm-context-list">${vinculadasHtml}</ul>
            </div>
            <div class="cm-context-section">
                <div class="cm-context-section-title">Follow-ups pendentes</div>
                <ul class="cm-context-list">${followupsHtml}</ul>
            </div>
            <div class="cm-context-actions mt-2">
                ${clienteUrl ? `<a class="btn btn-sm btn-outline-primary" href="${clienteUrl}" target="_blank" rel="noopener">Abrir cliente</a>` : ''}
                ${osUrl ? `<a class="btn btn-sm btn-outline-secondary" href="${osUrl}" target="_blank" rel="noopener">Abrir OS</a>` : ''}
                <a class="btn btn-sm btn-outline-success" href="${escapeHtml(novaOsUrl)}" target="_blank" rel="noopener">Nova OS</a>
            </div>
        `;

        bindContextActions();
    };

    const updateLatestMessageId = () => {
        if (!Array.isArray(state.mensagens) || state.mensagens.length === 0) {
            state.latestMessageId = 0;
            return;
        }
        const last = state.mensagens[state.mensagens.length - 1];
        state.latestMessageId = Number(last?.id || 0);
    };

    const openConversa = async (id, showErrors) => {
        if (!id || id <= 0) {
            return;
        }

        closeMessageStream();
        state.currentConversaId = id;
        if (conversaIdInput) {
            conversaIdInput.value = String(id);
        }
        threadMessages.classList.add('is-live-updating');
        threadMessages.innerHTML = `
            <div class="cm-empty-state cm-empty-state-sm">
                <i class="bi bi-arrow-repeat"></i>
                <p class="mb-0">Carregando conversa...</p>
            </div>
        `;

        try {
            const data = await getJson(cfg.endpointConversaPrefix + '/' + id);
            const conversa = data.conversa || {};
            state.activeConversationUnread = Number(data.unread_before || conversa.nao_lidas || 0);
            updateThreadHeader(conversa);
            state.mensagens = Array.isArray(data.mensagens) ? data.mensagens : [];
            updateLatestMessageId();
            renderMensagens({ forceBottom: true });
            state.currentContext = data.contexto || null;
            renderContexto(data.contexto || {});
            state.activeConversationUnread = 0;
            await safeLoadConversas(true);
            startMessageStream();
        } catch (error) {
            console.error('[CentralMensagens] falha ao abrir conversa', {
                conversa_id: id,
                detail: error?.message || error,
            });
            state.currentContext = null;
            threadTitle.textContent = 'Falha ao abrir conversa';
            threadSubtitle.textContent = '';
            applyThreadStatusBadge('arquivada');
            threadMessages.innerHTML = `
                <div class="cm-empty-state cm-empty-state-sm">
                    <i class="bi bi-exclamation-triangle text-danger"></i>
                    <p class="mb-0 text-danger">Nao foi possivel carregar esta conversa.</p>
                </div>
            `;
            if (showErrors) {
                await swal({ icon: 'error', title: 'Erro', text: error.message || 'Falha ao abrir conversa.' });
            }
        } finally {
            threadMessages.classList.remove('is-live-updating');
        }
    };

    const pullNovasMensagens = async () => {
        if (!state.currentConversaId || state.currentConversaId <= 0) {
            return 0;
        }
        const url = cfg.endpointConversaPrefix
            + '/'
            + state.currentConversaId
            + '/novas?after_id='
            + encodeURIComponent(String(state.latestMessageId || 0))
            + '&limit=120';

        const data = await getJson(url);
        const conversa = data.conversa || {};
        if (conversa && Object.keys(conversa).length > 0) {
            applyThreadStatusBadge(conversa.status || threadStatusBadge?.textContent || 'aberta');
        }
        const novas = Array.isArray(data.mensagens) ? data.mensagens : [];
        if (novas.length > 0) {
            state.activeConversationUnread = 0;
            appendMensagens(novas);
            updateLatestMessageId();
        }
        return novas.length;
    };

    const clearAnexoSelection = () => {
        state.selectedFile = null;
        if (anexoInput) anexoInput.value = '';
        if (cameraPhotoInput) cameraPhotoInput.value = '';
        if (cameraVideoInput) cameraVideoInput.value = '';
        if (anexoPreview) {
            anexoPreview.innerHTML = '';
            anexoPreview.classList.add('d-none');
        }
        if (composeMetaPanel) {
            composeMetaPanel.classList.add('d-none');
        }
    };

    const renderAnexoSelection = (file) => {
        if (!anexoPreview) return;
        if (!file) {
            clearAnexoSelection();
            return;
        }

        const type = detectContentType({ mime_type: file.type || '', arquivo: file.name || '' });
        const icon = iconByType(type);
        const info = bytesToHuman(file.size || 0);
        
        anexoPreview.innerHTML = `
            <div class="cm-anexo-chip mb-2">
                <i class="bi ${icon} text-primary fs-5"></i>
                <div class="cm-anexo-chip-info">
                    <div class="fw-semibold text-truncate" style="max-width: 250px;">${escapeHtml(file.name)}</div>
                    <div class="x-small text-muted">${info}</div>
                </div>
                <button type="button" class="btn-close ms-2" style="font-size: .65rem;" id="btnRemoveAnexo"></button>
            </div>
        `;
        anexoPreview.classList.remove('d-none');
        document.getElementById('btnRemoveAnexo')?.addEventListener('click', clearAnexoSelection);
    };

    const toggleAttachMenu = () => {
        if (!attachMenu) return;
        attachMenu.classList.toggle('d-none');
    };

    const hideAttachMenu = () => {
        if (!attachMenu) return;
        attachMenu.classList.add('d-none');
    };

    const handleAttachAction = (action) => {
        hideAttachMenu();
        switch (action) {
            case 'upload-file':
                anexoInput?.click();
                break;
            case 'system-pdf':
                toggleMetaPanel('pdf');
                break;
            case 'message-type':
                toggleMetaPanel('type');
                break;
            case 'capture-photo':
                cameraPhotoInput?.click();
                break;
            case 'record-audio':
                startMediaCapture('audio');
                break;
            case 'record-video':
                startMediaCapture('video');
                break;
        }
    };

    const toggleMetaPanel = (type) => {
        if (!composeMetaPanel) return;
        const pdfWrap = document.getElementById('cmPdfPickerWrap');
        const typeWrap = document.getElementById('cmTipoMensagemWrap');
        
        const isHidden = composeMetaPanel.classList.contains('d-none');
        
        if (isHidden) {
            composeMetaPanel.classList.remove('d-none');
        }

        if (type === 'pdf') {
            pdfWrap?.classList.remove('d-none');
            typeWrap?.classList.add('d-none');
        } else {
            typeWrap?.classList.remove('d-none');
            pdfWrap?.classList.add('d-none');
        }
    };

    const startMediaCapture = async (type) => {
        if (!capturePanel) return;
        state.recording.active = true;
        state.recording.type = type;
        state.recording.chunks = [];
        
        capturePanel.innerHTML = `
            <div class="text-center p-4">
                <div class="mb-3">
                    <i class="bi bi-${type === 'audio' ? 'mic' : 'camera-video'} fs-1 text-primary"></i>
                </div>
                <h5>Gravando ${type === 'audio' ? 'Áudio' : 'Vídeo'}</h5>
                <div class="mb-3 h4"><span class="btn-record-dot"></span> <span id="cmRecordTimer">00:00</span></div>
                ${type === 'video' ? '<video id="cmRecordPreview" autoplay muted class="cm-capture-video border"></video>' : ''}
                <div class="cm-capture-controls">
                    <button type="button" class="btn btn-danger btn-lg px-4" id="btnStopRecord">
                        <i class="bi bi-stop-circle me-2"></i>Parar
                    </button>
                    <button type="button" class="btn btn-link text-muted" id="btnCancelCapture">
                        Cancelar
                    </button>
                </div>
            </div>
        `;
        capturePanel.classList.remove('d-none');
        formEnviar?.classList.add('is-recording');
        hideAttachMenu();
        if (composeMetaPanel) composeMetaPanel.classList.add('d-none');

        try {
            const constraints = { 
                audio: true, 
                video: type === 'video' ? { facingMode: 'user' } : false 
            };
            const stream = await navigator.mediaDevices.getUserMedia(constraints);
            state.recording.stream = stream;
            
            if (type === 'video') {
                const videoEl = document.getElementById('cmRecordPreview');
                if (videoEl) videoEl.srcObject = stream;
            }            const mimeType = type === 'video' 
                ? 'video/webm' 
                : (MediaRecorder.isTypeSupported('audio/ogg; codecs=opus') ? 'audio/ogg; codecs=opus' : 'audio/webm');

            const recorder = new MediaRecorder(stream, { mimeType });
            state.recording.recorder = recorder;
            state.recording.startTime = Date.now();
            state.recording.mimeUsed = mimeType;
            
            recorder.ondataavailable = (e) => {
                if (e.data.size > 0) state.recording.chunks.push(e.data);
            };
 
            recorder.onstop = () => {
                const blob = new Blob(state.recording.chunks, { type: state.recording.mimeUsed });
                state.recording.blob = blob;
                renderCaptureReview();
            };
;

            recorder.start();
            state.recording.timer = setInterval(updateRecordTimer, 1000);

            document.getElementById('btnStopRecord')?.addEventListener('click', stopRecording);
            document.getElementById('btnCancelCapture')?.addEventListener('click', cancelCapture);

        } catch (error) {
            cancelCapture();
            swal({ icon: 'error', title: 'Acesso Negado', text: 'Não foi possível acessar a câmera ou microfone. Verifique as permissões do navegador.' });
        }
    };

    const updateRecordTimer = () => {
        const timerEl = document.getElementById('cmRecordTimer');
        if (!timerEl) return;
        const diff = Math.floor((Date.now() - state.recording.startTime) / 1000);
        const min = String(Math.floor(diff / 60)).padStart(2, '0');
        const sec = String(diff % 60).padStart(2, '0');
        timerEl.textContent = `${min}:${sec}`;
    };

    const stopRecording = () => {
        if (state.recording.recorder && state.recording.recorder.state !== 'inactive') {
            state.recording.recorder.stop();
        }
        if (state.recording.timer) {
            clearInterval(state.recording.timer);
            state.recording.timer = null;
        }
        if (state.recording.stream) {
            state.recording.stream.getTracks().forEach(t => t.stop());
        }
    };

    const cancelCapture = () => {
        stopRecording();
        state.recording.active = false;
        capturePanel?.classList.add('d-none');
        formEnviar?.classList.remove('is-recording');
    };

    const renderCaptureReview = () => {
        const type = state.recording.type;
        const blob = state.recording.blob;
        const url = URL.createObjectURL(blob);
        
        capturePanel.innerHTML = `
            <div class="text-center w-100 p-4">
                <h5 class="mb-4">Revisar ${type === 'audio' ? 'Áudio' : 'Vídeo'}</h5>
                <div class="mb-4">
                    ${type === 'video' 
                        ? `<video controls src="${url}" class="cm-capture-video border"></video>` 
                        : `<audio controls src="${url}" class="w-100"></audio>`}
                </div>
                <div class="cm-capture-controls">
                    <button type="button" class="btn btn-success btn-lg px-4" id="btnConfirmCapture">
                        <i class="bi bi-send-check me-2"></i>Usar e Anexar
                    </button>
                    <button type="button" class="btn btn-outline-danger" id="btnDiscardCapture">
                        Descartar
                    </button>
                </div>
            </div>
        `;
        
        document.getElementById('btnConfirmCapture')?.addEventListener('click', () => {
            const ext = (state.recording.mimeUsed || '').includes('ogg') ? 'ogg' : 'webm';
            const file = new File([blob], `gravacao_${Date.now()}.${ext}`, { type: blob.type });
            state.selectedFile = file;
            
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);
            if (anexoInput) anexoInput.files = dataTransfer.files;
            
            renderAnexoSelection(file);
            cancelCapture();
        });

        document.getElementById('btnDiscardCapture')?.addEventListener('click', cancelCapture);
    };

    const sendCurrentMessage = async () => {
        if (!state.currentConversaId) {
            await swal({ icon: 'warning', title: 'Selecione uma conversa', text: 'Escolha uma conversa antes de enviar.' });
            return;
        }

        const payload = {
            conversa_id: state.currentConversaId,
            mensagem: (msgInput?.value || '').trim(),
            tipo_mensagem: tipoMensagemInput?.value || 'manual',
            documento_id: documentoSelect?.value || '',
        };

        if (!payload.mensagem && !payload.documento_id && !state.selectedFile) {
            await swal({ icon: 'warning', title: 'Conteudo vazio', text: 'Digite uma mensagem, selecione um PDF ou anexe um arquivo.' });
            return;
        }

        setButtonLoading(sendButton, true, '<span class="spinner-border spinner-border-sm me-1"></span>Enviando...');
        const sendTimeoutMs = 16000;
        try {
            const response = await postForm(cfg.endpointEnviar, payload, state.selectedFile, sendTimeoutMs);
            if (msgInput) msgInput.value = '';
            clearAnexoSelection();
            scrollThreadToBottom(true);

            Promise.resolve().then(async () => {
                const count = await pullNovasMensagens().catch(() => 0);
                if (count === 0 && response?.conversa_id) {
                    await openConversa(Number(response.conversa_id), false);
                }
                await safeLoadConversas(true);
            });
        } catch (error) {
            const timeoutAbort = (error && (error.name === 'AbortError' || String(error.message || '').toLowerCase().includes('timeout')));
            const providerUnavailable = Number(error?.status || 0) === 503
                || String(error?.payload?.code || '') === 'CM_ENVIO_PROVIDER_UNAVAILABLE';
            await swal({
                icon: 'error',
                title: providerUnavailable ? 'Gateway indisponivel' : 'Falha no envio',
                text: timeoutAbort
                    ? 'O envio excedeu o tempo limite de 16s. Verifique o gateway e tente novamente.'
                    : (error.message || 'Nao foi possivel enviar.'),
                footer: providerUnavailable
                    ? 'Verifique Configuracoes > WhatsApp e confirme se o gateway esta em execucao.'
                    : undefined,
            });
        } finally {
            setButtonLoading(sendButton, false);
        }
    };

    const syncInbound = async () => {
        if (!btnSyncInbound) {
            return;
        }
        setButtonLoading(btnSyncInbound, true, '<span class="spinner-border spinner-border-sm me-1"></span>Sincronizando...');
        try {
            const data = await postForm(cfg.endpointSyncInbound, {}, null);
            await swal({
                icon: 'success',
                title: 'Sincronizado',
                text: `Mensagens processadas: ${Number(data.count || 0)}.`,
            });
            await safeLoadConversas(true);
            if (state.currentConversaId) {
                await openConversa(state.currentConversaId, false);
            }
        } catch (error) {
            await swal({ icon: 'error', title: 'Falha na sincronizacao', text: error.message || 'Erro inesperado.' });
        } finally {
            setButtonLoading(btnSyncInbound, false);
        }
    };

    const createNewConversation = async () => {
        if (!window.Swal) {
            return;
        }

        const response = await window.Swal.fire({
            title: 'Nova conversa',
            html: `
                <input id="swTelefone" class="swal2-input" placeholder="Telefone (55...)">
                <textarea id="swMensagem" class="swal2-textarea" placeholder="Mensagem inicial"></textarea>
            `,
            focusConfirm: false,
            showCancelButton: true,
            confirmButtonText: 'Iniciar',
            cancelButtonText: 'Cancelar',
            preConfirm: function () {
                const telefone = (document.getElementById('swTelefone')?.value || '').trim();
                const mensagem = (document.getElementById('swMensagem')?.value || '').trim();
                if (!telefone || !mensagem) {
                    window.Swal.showValidationMessage('Informe telefone e mensagem inicial.');
                    return false;
                }
                return { telefone, mensagem };
            },
        });

        const values = response?.value;
        if (!values) {
            return;
        }

        try {
            const data = await postForm(cfg.endpointEnviar, {
                telefone: values.telefone,
                mensagem: values.mensagem,
                tipo_mensagem: 'manual',
            }, null);
            await safeLoadConversas(true);
            if (data.conversa_id) {
                await openConversa(Number(data.conversa_id), false);
            }
        } catch (error) {
            await swal({ icon: 'error', title: 'Falha', text: error.message || 'Nao foi possivel iniciar conversa.' });
        }
    };

    const pollTick = async () => {
        if (state.authRedirectInProgress) {
            return;
        }
        if (state.pollRunning) {
            return;
        }
        state.pollRunning = true;
        try {
            const streamIsLive = state.streamReady
                && state.streamForConversaId === state.currentConversaId
                && state.currentConversaId > 0;

            if (state.currentConversaId && !streamIsLive) {
                await pullNovasMensagens();
            }
            await safeLoadConversas(true);
        } catch (error) {
            setRealtimeBadge('warn');
            const now = Date.now();
            if ((now - Number(state.lastPollErrorLogAt || 0)) > 15000) {
                state.lastPollErrorLogAt = now;
                console.error('[CentralMensagens] falha no polling incremental', error);
            }
        } finally {
            state.pollRunning = false;
        }
    };

    const startPolling = () => {
        if (state.pollTimer) {
            clearTimeout(state.pollTimer);
        }
        const tick = async () => {
            if (document.hidden) {
                state.pollTimer = setTimeout(tick, autoSyncIntervalMs);
                return;
            }
            await pollTick();
            const hasOpen = !!state.currentConversaId;
            const delay = hasOpen
                ? Math.max(1500, Math.min(3000, Math.floor(autoSyncIntervalMs / 2)))
                : autoSyncIntervalMs;
            state.pollTimer = setTimeout(tick, delay);
        };
        state.pollTimer = setTimeout(tick, autoSyncIntervalMs);
    };

    const shutdownRuntime = () => {
        stopPollingLoop();
        closeMessageStream();
    };

    const bindStaticEvents = () => {
        btnFiltrar?.addEventListener('click', () => {
            updateFilterFeedback();
            safeLoadConversas(true);
        });
        btnLimparFiltros?.addEventListener('click', () => {
            if (filtroQ) filtroQ.value = '';
            if (filtroStatus) filtroStatus.value = '';
            if (filtroResponsavel) filtroResponsavel.value = '';
            if (filtroTag) filtroTag.value = '';
            if (filtroNaoLidas) filtroNaoLidas.checked = false;
            if (filtroOsAberta) filtroOsAberta.checked = false;
            if (filtroClientesNovos) filtroClientesNovos.checked = false;
            updateFilterFeedback();
            safeLoadConversas(true);
        });
        filtroQ?.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                safeLoadConversas(true);
            }
        });
        [filtroStatus, filtroResponsavel, filtroTag, filtroNaoLidas, filtroOsAberta, filtroClientesNovos].forEach((el) => {
            el?.addEventListener('change', () => {
                updateFilterFeedback();
                safeLoadConversas(true);
            });
        });

        msgInput?.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                sendCurrentMessage();
            }
        });
        formEnviar?.addEventListener('submit', (event) => {
            event.preventDefault();
            sendCurrentMessage();
        });

        btnSyncInbound?.addEventListener('click', syncInbound);
        btnNovaConversa?.addEventListener('click', createNewConversation);
        btnAtualizarConversa?.addEventListener('click', async () => {
            if (!state.currentConversaId) {
                await safeLoadConversas(true);
                return;
            }
            await openConversa(state.currentConversaId, false);
            await safeLoadConversas(true);
        });
        btnAssumirConversa?.addEventListener('click', async () => {
            if (!currentUserId) {
                await swal({
                    icon: 'warning',
                    title: 'Usuario invalido',
                    text: 'Nao foi possivel identificar seu usuario para assumir a conversa.',
                });
                return;
            }
            try {
                const msg = currentUserName
                    ? `Conversa atribuida para ${currentUserName}.`
                    : 'Conversa assumida com sucesso.';
                await quickUpdateMeta({
                    responsavel_id: currentUserId,
                    aguardando_humano: 1,
                    automacao_ativa: 0,
                }, msg);
            } catch (error) {
                await swal({
                    icon: 'error',
                    title: 'Falha ao assumir',
                    text: error?.message || 'Nao foi possivel assumir a conversa no momento.',
                });
            }
        });
        btnEncerrarConversa?.addEventListener('click', async () => {
            if (!state.currentConversaId) {
                await swal({
                    icon: 'warning',
                    title: 'Sem conversa ativa',
                    text: 'Abra uma conversa antes de encerrar.',
                });
                return;
            }
            const confirm = await swal({
                icon: 'question',
                title: 'Encerrar conversa?',
                text: 'O status sera atualizado para Resolvida.',
                showCancelButton: true,
                confirmButtonText: 'Encerrar',
                cancelButtonText: 'Cancelar',
                reverseButtons: true,
            });
            if (!confirm?.isConfirmed) {
                return;
            }
            try {
                await quickUpdateMeta({
                    status: 'resolvida',
                    aguardando_humano: 0,
                }, 'Conversa encerrada como resolvida.');
            } catch (error) {
                await swal({
                    icon: 'error',
                    title: 'Falha ao encerrar',
                    text: error?.message || 'Nao foi possivel encerrar a conversa.',
                });
            }
        });
        btnAtribuirConversa?.addEventListener('click', async () => {
            if (!state.currentConversaId) {
                await swal({
                    icon: 'warning',
                    title: 'Sem conversa ativa',
                    text: 'Abra uma conversa para atribuir responsavel.',
                });
                return;
            }
            if (window.bootstrap && window.matchMedia('(max-width: 1199.98px)').matches) {
                const canvas = document.getElementById('cmContextoCanvas');
                if (canvas) {
                    const offcanvas = window.bootstrap.Offcanvas.getOrCreateInstance(canvas);
                    offcanvas.show();
                }
            }
            setTimeout(() => {
                document.getElementById('contextResponsavelSelect')?.focus();
            }, 220);
        });
        btnSyncInboundMobile?.addEventListener('click', () => syncInbound());
        btnNovaConversaMobile?.addEventListener('click', () => createNewConversation());

        btnAnexarMidia?.addEventListener('click', (e) => {
            e.stopPropagation();
            toggleAttachMenu();
        });
        btnEmojiPicker?.addEventListener('click', (e) => {
            e.stopPropagation();
            emojiMenu?.classList.toggle('d-none');
            hideAttachMenu();
        });

        document.getElementById('cmAttachMenu')?.querySelectorAll('.cm-attach-item').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                handleAttachAction(btn.dataset.action);
            });
        });
        emojiMenu?.querySelectorAll('.cm-emoji-btn').forEach((btn) => {
            btn.addEventListener('click', () => {
                const emoji = String(btn.getAttribute('data-emoji') || '');
                if (!emoji || !msgInput) {
                    return;
                }
                msgInput.value += emoji;
                msgInput.focus();
                emojiMenu.classList.add('d-none');
                msgInput.dispatchEvent(new Event('input'));
            });
        });

        document.addEventListener('click', (e) => {
            if (attachMenu && !attachMenu.contains(e.target) && e.target !== btnAnexarMidia) {
                hideAttachMenu();
            }
            if (emojiMenu && !emojiMenu.contains(e.target) && e.target !== btnEmojiPicker) {
                emojiMenu.classList.add('d-none');
            }
        });

        anexoInput?.addEventListener('change', () => {
            const file = anexoInput.files && anexoInput.files.length ? anexoInput.files[0] : null;
            state.selectedFile = file;
            renderAnexoSelection(file);
        });

        const handleInjectFile = (input) => {
            const file = input.files && input.files.length ? input.files[0] : null;
            if (file) {
                state.selectedFile = file;
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                if (anexoInput) anexoInput.files = dataTransfer.files;
                renderAnexoSelection(file);
            }
        };

        cameraPhotoInput?.addEventListener('change', () => handleInjectFile(cameraPhotoInput));
        cameraVideoInput?.addEventListener('change', () => handleInjectFile(cameraVideoInput));

        document.querySelectorAll('.btn-resposta-rapida').forEach((btn) => {
            btn.addEventListener('click', () => {
                let msg = btn.getAttribute('data-msg') || '';
                if (!msgInput || !msg) {
                    return;
                }

                // Substituicao dinamica de tags
                if (state.currentContext) {
                    const ctx = state.currentContext;
                    const cliente = ctx.cliente || {};
                    const os = ctx.os_principal || (Array.isArray(ctx.os) ? ctx.os[0] : null) || {};

                    const replacements = {
                        '{{cliente_nome}}': cliente.nome_razao || cliente.nome || 'cliente',
                        '{{numero_os}}': os.numero_os || '',
                        '{{equipamento}}': trimStrings((os.equip_marca || '') + ' ' + (os.equip_modelo || '')),
                        '{{marca}}': os.equip_marca || '',
                        '{{modelo}}': os.equip_modelo || '',
                        '{{status}}': os.status || '',
                        '{{valor_final}}': os.valor_final ? ('R$ ' + Number(os.valor_final).toLocaleString('pt-BR', { minimumFractionDigits: 2 })) : '',
                        '{{data_previsao}}': os.data_previsao ? new Date(os.data_previsao).toLocaleDateString('pt-BR') : '',
                        '{{garantia_dias}}': os.garantia_dias || '',
                        '{{defeito}}': os.relato_cliente || '',
                        '{{empresa_endereco}}': cfg.empresaEndereco || ''
                    };

                    function trimStrings(str) { return str.trim() || ''; }

                    Object.keys(replacements).forEach(tag => {
                        const regex = new RegExp(tag.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'gi');
                        msg = msg.replace(regex, replacements[tag] || '');
                    });
                }

                msgInput.value = msgInput.value ? (msgInput.value + '\n' + msg) : msg;
                msgInput.focus();
                
                // Dispara evento para auto-resize do textarea se existir
                msgInput.dispatchEvent(new Event('input'));
            });
        });

        jumpBottomBtn?.addEventListener('click', () => {
            scrollThreadToBottom(true);
        });

        threadMessages?.addEventListener('scroll', updateJumpBottomVisibility);

        document.querySelectorAll('.cm-mobile-list-trigger').forEach((btn) => {
            btn.addEventListener('click', (event) => {
                const target = btn.getAttribute('data-bs-target') || '#cmConversasCanvas';
                const canvas = document.querySelector(target);
                if (!canvas || !window.bootstrap || window.matchMedia('(min-width: 992px)').matches) {
                    return;
                }
                event.preventDefault();
                event.stopPropagation();
                try {
                    const offcanvas = window.bootstrap.Offcanvas.getOrCreateInstance(canvas);
                    offcanvas.show();
                } catch (error) {
                    console.error('[CentralMensagens] falha ao abrir painel de conversas mobile', error);
                }
            });
        });

        document.querySelectorAll('.cm-mobile-context-trigger').forEach((btn) => {
            btn.addEventListener('click', (event) => {
                const target = btn.getAttribute('data-bs-target') || '#cmContextoCanvas';
                const canvas = document.querySelector(target);
                if (!canvas || !window.bootstrap || window.matchMedia('(min-width: 992px)').matches) {
                    return;
                }
                event.preventDefault();
                event.stopPropagation();
                try {
                    const offcanvas = window.bootstrap.Offcanvas.getOrCreateInstance(canvas);
                    offcanvas.show();
                } catch (error) {
                    console.error('[CentralMensagens] falha ao abrir painel de contexto mobile', error);
                }
            });
        });
    };

    const bootstrapCentral = async () => {
        const urlParams = new URLSearchParams(window.location.search);
        const initialQ = (urlParams.get('q') || '').trim();
        const initialConversaId = Number(urlParams.get('conversa_id') || 0);

        if (initialQ && filtroQ) {
            filtroQ.value = initialQ;
        }
        updateFilterFeedback();
        setRealtimeBadge('polling');

        let items = [];
        try {
            items = await safeLoadConversas(false);
        } catch (error) {
            setRealtimeBadge('warn');
            listEl.innerHTML = `
                <div class="cm-empty-state">
                    <i class="bi bi-exclamation-triangle text-danger"></i>
                    <p class="mb-0 text-danger">${escapeHtml(error.message || 'Erro ao carregar conversas')}</p>
                </div>
            `;
            return;
        }

        if (initialConversaId > 0) {
            await openConversa(initialConversaId, false);
            return;
        }
        if (items.length > 0) {
            await openConversa(Number(items[0].id), false);
        }
    };

    bindImageModalActions();
    bindStaticEvents();
    bootstrapCentral();
    startPolling();
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            closeMessageStream();
            return;
        }
        if (!document.hidden) {
            pollTick();
            if (state.currentConversaId && !state.streamSource) {
                startMessageStream();
            }
        }
    });
    window.addEventListener('beforeunload', shutdownRuntime);
    window.addEventListener('pagehide', shutdownRuntime);
})();
