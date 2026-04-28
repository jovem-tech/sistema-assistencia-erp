(function () {
    'use strict';

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function debounce(fn, wait) {
        let timer = null;
        return function debounced() {
            const args = arguments;
            const context = this;
            if (timer) {
                window.clearTimeout(timer);
            }
            timer = window.setTimeout(function () {
                fn.apply(context, args);
            }, wait);
        };
    }

    document.addEventListener('DOMContentLoaded', function () {
        const root = document.getElementById('navbarNotifications');
        if (!root) {
            return;
        }

        const listElement = document.getElementById('navbarNotificationList');
        const badgeElement = document.getElementById('navbarNotificationCount');
        const metaElement = document.getElementById('navbarNotificationMeta');
        const markAllButton = document.getElementById('navbarNotificationMarkAll');
        const dropdownToggle = root.querySelector('.navbar-notification-toggle');

        const feedUrl = String(root.dataset.feedUrl || '').trim();
        const streamUrl = String(root.dataset.streamUrl || '').trim();
        const readUrlBase = String(root.dataset.readUrlBase || '').trim();
        const readAllUrl = String(root.dataset.readAllUrl || '').trim();
        const baseUrl = String(root.dataset.baseUrl || window.location.origin + '/').trim();
        const appUrl = String(root.dataset.appUrl || baseUrl || window.location.origin + '/').trim();

        if (!feedUrl || !listElement || !badgeElement || !metaElement) {
            return;
        }

        const state = {
            items: [],
            unreadCount: 0,
            lastNotificationId: 0,
            connectionState: 'offline',
            eventSource: null,
            fallbackTimer: null,
            reconnectTimer: null,
        };

        function ensureTrailingSlash(url) {
            const normalized = String(url || '').trim();
            if (!normalized) {
                return window.location.origin + '/';
            }

            return /\/$/.test(normalized) ? normalized : (normalized + '/');
        }

        function updateMetaLabel() {
            const countLabel = state.unreadCount > 0
                ? state.unreadCount + ' nao lida(s)'
                : 'Sem pendencias';

            const connectionLabel = state.connectionState === 'live'
                ? 'Ao vivo'
                : (state.connectionState === 'polling' ? 'Atualizacao automatica' : 'Sincronizando');

            metaElement.textContent = countLabel + ' • ' + connectionLabel;
        }

        function updateBadge() {
            badgeElement.textContent = String(state.unreadCount);
            badgeElement.classList.toggle('d-none', state.unreadCount <= 0);
            markAllButton && (markAllButton.disabled = state.unreadCount <= 0);
            updateMetaLabel();
        }

        function resolveNotificationTimeLabel(item) {
            const raw = String(item?.created_at || '').trim();
            if (!raw) {
                return 'Agora';
            }

            const date = new Date(raw);
            if (Number.isNaN(date.getTime())) {
                return raw;
            }

            return new Intl.DateTimeFormat('pt-BR', {
                day: '2-digit',
                month: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
            }).format(date);
        }

        function resolveNotificationRoute(item) {
            const route = String(item?.rota_destino || '').trim();
            if (!route) {
                return '';
            }

            try {
                if (/^https?:\/\//i.test(route)) {
                    return new URL(route).toString();
                }

                const normalizedRoute = route.replace(/^\/+/, '');
                return new URL(normalizedRoute, ensureTrailingSlash(appUrl)).toString();
            } catch (error) {
                console.error('[NavbarNotifications] rota de notificacao invalida.', error);
                return '';
            }
        }

        function renderList() {
            if (!Array.isArray(state.items) || state.items.length === 0) {
                listElement.innerHTML = '<div class="navbar-notification-empty">Nenhuma notificacao recente.</div>';
                updateBadge();
                return;
            }

            listElement.innerHTML = state.items.map(function (item) {
                const isUnread = !item.lida_em;
                const route = resolveNotificationRoute(item);
                const classes = ['navbar-notification-item'];
                if (isUnread) {
                    classes.push('is-unread');
                }
                if (route) {
                    classes.push('has-route');
                }

                return '' +
                    '<button type="button" class="' + classes.join(' ') + '"' +
                        ' data-notification-id="' + Number(item.id || 0) + '"' +
                        (route ? ' data-notification-route="' + escapeHtml(route) + '"' : '') +
                    '>' +
                        '<span class="navbar-notification-item-title">' + escapeHtml(item.titulo || 'Atualizacao') + '</span>' +
                        '<span class="navbar-notification-item-body">' + escapeHtml(item.corpo || '') + '</span>' +
                        '<span class="navbar-notification-item-meta">' + escapeHtml(resolveNotificationTimeLabel(item)) + '</span>' +
                    '</button>';
            }).join('');

            updateBadge();
        }

        function syncItems(items, options) {
            const normalizedOptions = options && typeof options === 'object' ? options : {};
            const emitRealtime = normalizedOptions.emitRealtime === true;
            const showToast = normalizedOptions.showToast === true;
            const existingIds = new Set(state.items.map(function (item) {
                return Number(item.id || 0);
            }));
            const incomingItems = Array.isArray(items) ? items : [];
            const appended = [];

            incomingItems.forEach(function (item) {
                const id = Number(item?.id || 0);
                if (id <= 0) {
                    return;
                }

                state.lastNotificationId = Math.max(state.lastNotificationId, id);
                const index = state.items.findIndex(function (entry) {
                    return Number(entry.id || 0) === id;
                });

                if (index >= 0) {
                    state.items[index] = { ...state.items[index], ...item };
                    return;
                }

                state.items.unshift(item);
                if (!existingIds.has(id)) {
                    appended.push(item);
                }
            });

            state.items = state.items
                .sort(function (a, b) {
                    return Number(b.id || 0) - Number(a.id || 0);
                })
                .slice(0, 8);

            if (emitRealtime && appended.length > 0) {
                appended.forEach(function (item) {
                    const detail = {
                        notification: item,
                        tipo_evento: String(item?.tipo_evento || '').trim(),
                        payload: item?.payload && typeof item.payload === 'object' ? item.payload : {},
                    };

                    window.dispatchEvent(new CustomEvent('erp:notification', {
                        detail: detail,
                    }));

                    if (
                        showToast
                        && detail.tipo_evento === 'orcamento.public_status_changed'
                        && window.Swal
                        && typeof window.Swal.fire === 'function'
                    ) {
                        window.Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'info',
                            title: String(item?.titulo || 'Atualizacao'),
                            text: String(item?.corpo || ''),
                            timer: 4200,
                            showConfirmButton: false,
                        });
                    }
                });
            }

            renderList();
        }

        function applyFeedPayload(payload, options) {
            const normalizedOptions = options && typeof options === 'object' ? options : {};
            const items = Array.isArray(payload?.items) ? payload.items : [];
            if (typeof payload?.unread_count === 'number') {
                state.unreadCount = Math.max(0, Number(payload.unread_count || 0));
            }
            if (typeof payload?.last_notification_id === 'number') {
                state.lastNotificationId = Math.max(state.lastNotificationId, Number(payload.last_notification_id || 0));
            }
            syncItems(items, normalizedOptions);
        }

        async function fetchFeed(options) {
            const normalizedOptions = options && typeof options === 'object' ? options : {};
            const response = await window.fetch(feedUrl, {
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            const payload = await response.json();
            if (!response.ok || payload?.ok !== true) {
            throw new Error(payload?.message || 'Falha ao carregar notificações.');
            }

            applyFeedPayload(payload, normalizedOptions);
        }

        async function markAsRead(notificationId) {
            const id = Number(notificationId || 0);
            if (id <= 0 || !readUrlBase) {
                return;
            }

            const response = await window.fetch(readUrlBase.replace(/\/$/, '') + '/' + id, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            const payload = await response.json();
            if (!response.ok || payload?.ok !== true) {
                throw new Error(payload?.message || 'Falha ao marcar notificacao como lida.');
            }

            const index = state.items.findIndex(function (item) {
                return Number(item.id || 0) === id;
            });
            if (index >= 0) {
                state.items[index] = {
                    ...state.items[index],
                    lida_em: payload.lida_em || new Date().toISOString(),
                };
            }

            if (typeof payload?.unread_count === 'number') {
                state.unreadCount = Math.max(0, Number(payload.unread_count || 0));
            } else {
                state.unreadCount = Math.max(0, state.unreadCount - 1);
            }

            renderList();
        }

        async function markAllRead() {
            if (!readAllUrl) {
                return;
            }

            const response = await window.fetch(readAllUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            const payload = await response.json();
            if (!response.ok || payload?.ok !== true) {
            throw new Error(payload?.message || 'Falha ao marcar notificações como lidas.');
            }

            state.items = state.items.map(function (item) {
                return {
                    ...item,
                    lida_em: payload.updated_at || new Date().toISOString(),
                };
            });
            state.unreadCount = 0;
            renderList();
        }

        function stopFallbackPolling() {
            if (!state.fallbackTimer) {
                return;
            }
            window.clearInterval(state.fallbackTimer);
            state.fallbackTimer = null;
        }

        function startFallbackPolling() {
            if (state.fallbackTimer) {
                return;
            }

            state.connectionState = 'polling';
            updateMetaLabel();
            state.fallbackTimer = window.setInterval(function () {
                fetchFeed({
                    emitRealtime: true,
                    showToast: true,
                }).catch(function (error) {
        console.error('[NavbarNotifications] Falha no polling de notificações.', error);
                });
            }, 15000);
        }

        function scheduleStreamReconnect() {
            if (!streamUrl || state.reconnectTimer) {
                return;
            }

            state.reconnectTimer = window.setTimeout(function () {
                state.reconnectTimer = null;
                connectStream();
            }, 12000);
        }

        function connectStream() {
            if (!streamUrl || !('EventSource' in window) || state.eventSource) {
                if (!('EventSource' in window)) {
                    startFallbackPolling();
                }
                return;
            }

            try {
                const stream = new URL(streamUrl, baseUrl);
                if (state.lastNotificationId > 0) {
                    stream.searchParams.set('after_id', String(state.lastNotificationId));
                }

                state.eventSource = new window.EventSource(stream.toString());
                state.connectionState = 'live';
                updateMetaLabel();

                state.eventSource.addEventListener('delta', function (event) {
                    state.connectionState = 'live';
                    stopFallbackPolling();
                    updateMetaLabel();

                    let payload = null;
                    try {
                        payload = JSON.parse(String(event.data || '{}'));
                    } catch (error) {
                        console.error('[NavbarNotifications] payload SSE invalido.', error);
                        return;
                    }

                    if (typeof payload?.unread_count === 'number') {
                        state.unreadCount = Math.max(0, Number(payload.unread_count || 0));
                    }

                    syncItems(Array.isArray(payload?.notifications) ? payload.notifications : [], {
                        emitRealtime: true,
                        showToast: true,
                    });
                });

                state.eventSource.addEventListener('ping', function () {
                    state.connectionState = 'live';
                    updateMetaLabel();
                });

                state.eventSource.addEventListener('end', function () {
                    if (state.eventSource) {
                        state.eventSource.close();
                        state.eventSource = null;
                    }
                    startFallbackPolling();
                    scheduleStreamReconnect();
                });

                state.eventSource.onerror = function () {
                    if (state.eventSource) {
                        state.eventSource.close();
                        state.eventSource = null;
                    }
                    state.connectionState = 'polling';
                    updateMetaLabel();
                    startFallbackPolling();
                    scheduleStreamReconnect();
                };
            } catch (error) {
        console.error('[NavbarNotifications] Não foi possível abrir o stream SSE.', error);
                startFallbackPolling();
            }
        }

        const debouncedSyncOnOpen = debounce(function () {
            fetchFeed({
                emitRealtime: false,
                showToast: false,
            }).catch(function (error) {
                console.error('[NavbarNotifications] Falha ao sincronizar feed na abertura do dropdown.', error);
            });
        }, 120);

        listElement.addEventListener('click', function (event) {
            const itemButton = event.target.closest('[data-notification-id]');
            if (!itemButton) {
                return;
            }

            const notificationId = Number(itemButton.getAttribute('data-notification-id') || 0);
            const route = String(itemButton.getAttribute('data-notification-route') || '').trim();

            markAsRead(notificationId)
                .catch(function (error) {
                    console.error('[NavbarNotifications] Falha ao marcar notificacao como lida.', error);
                })
                .finally(function () {
                    if (route) {
                        window.location.href = route;
                    }
                });
        });

        markAllButton?.addEventListener('click', function () {
            markAllRead().catch(function (error) {
        console.error('[NavbarNotifications] Falha ao marcar notificações como lidas.', error);
                if (window.DSFeedback && typeof window.DSFeedback.error === 'function') {
            window.DSFeedback.error('Notificações', error?.message || 'Não foi possível marcar as notificações como lidas.');
                }
            });
        });

        root.addEventListener('show.bs.dropdown', debouncedSyncOnOpen);

        fetchFeed({
            emitRealtime: false,
            showToast: false,
        }).catch(function (error) {
        console.error('[NavbarNotifications] Falha ao carregar notificações iniciais.', error);
            state.connectionState = 'offline';
            updateMetaLabel();
        listElement.innerHTML = '<div class="navbar-notification-empty">Não foi possível carregar as notificações agora.</div>';
        }).finally(function () {
            connectStream();
            if (!('EventSource' in window)) {
                startFallbackPolling();
            }
        });
    });
})();
