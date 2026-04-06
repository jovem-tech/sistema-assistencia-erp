<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<?php $cmCssVersion = @filemtime(FCPATH . 'assets/css/design-system/layouts/central-mensagens.css') ?: '20260401'; ?>
<link rel="stylesheet" href="<?= base_url('assets/css/design-system/layouts/central-mensagens.css') ?>?v=<?= $cmCssVersion ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="cm-page-shell" data-sidebar-auto-collapse="hover">
    <header class="page-header cm-page-header">
        <div class="cm-page-title-wrap">
            <h2 class="cm-page-title mb-0"><i class="bi bi-whatsapp me-2"></i>Central de Mensagens</h2>
            <small class="cm-page-subtitle">Inbox operacional em tempo real para atendimento WhatsApp + ERP</small>
        </div>
        <div class="cm-page-actions">
            <span class="badge rounded-pill text-bg-light border cm-realtime-badge" id="cmRealtimeBadge">
                <i class="bi bi-broadcast-pin me-1"></i>Polling
            </span>
            <span class="badge rounded-pill text-bg-light border cm-sync-badge" id="cmInboundBadge">
                <i class="bi bi-arrow-down-up me-1"></i>Inbound ocioso
            </span>
            <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('atendimento-whatsapp')">
                <i class="bi bi-question-circle me-1"></i>Ajuda
            </button>
        </div>
    </header>

    <span id="gatewayAccountNumber" class="d-none"><?= esc($gatewayAccountNumber ?? '') ?></span>

    <div class="central-mensagens-wrapper" id="cmWorkspace">
        <aside
            class="offcanvas-lg offcanvas-start cm-side-shell cm-col-left coluna-conversas"
            tabindex="-1"
            id="cmConversasCanvas"
            aria-labelledby="cmConversasCanvasLabel"
        >
            <div class="offcanvas-header d-lg-none border-bottom">
                <h5 class="offcanvas-title" id="cmConversasCanvasLabel">Conversas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Fechar"></button>
            </div>
            <div class="offcanvas-body p-0">
                <div class="card glass-card cm-panel mb-0">
                    <div class="card-body p-0 d-flex flex-column gap-0">
                        <div class="cm-list-summary">
                            <div>
                                <div class="cm-list-summary-label">Fila de atendimento</div>
                                <div class="cm-list-summary-value" id="cmConversaCount">0 conversas</div>
                            </div>
                            <div class="text-end">
                                <div class="cm-list-summary-label">N&atilde;o lidas</div>
                                <div class="cm-list-summary-value text-danger-emphasis" id="cmNaoLidasCount">0</div>
                            </div>
                        </div>

                        <div class="cm-filter-toggle-wrap">
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-secondary cm-filter-toggle-btn"
                                id="btnToggleQueueFilters"
                                data-bs-toggle="collapse"
                                data-bs-target="#cmAdvancedFilters"
                                aria-expanded="false"
                                aria-controls="cmAdvancedFilters"
                            >
                                <i class="bi bi-funnel me-1"></i>Filtros avancados
                            </button>
                        </div>
                        <div class="collapse" id="cmAdvancedFilters">
                            <div class="cm-list-filters">
                                <div class="cm-filter-q">
                                    <input type="text" class="form-control form-control-sm" id="filtroConversaQ" placeholder="Buscar cliente, telefone, OS...">
                                </div>
                                <div class="cm-filter-select">
                                    <select class="form-select form-select-sm" id="filtroConversaStatus">
                                        <option value="">Status: todos</option>
                                        <?php foreach (($statusConversaOptions ?? []) as $s): ?>
                                            <option value="<?= esc($s) ?>"><?= esc(ucfirst($s)) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="cm-filter-select">
                                    <select class="form-select form-select-sm" id="filtroConversaResponsavel">
                                        <option value="">Respons&aacute;vel: todos</option>
                                        <?php foreach (($usuariosAtivos ?? []) as $u): ?>
                                            <option value="<?= (int) $u['id'] ?>"><?= esc($u['nome']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="cm-filter-select">
                                    <select class="form-select form-select-sm" id="filtroConversaTag">
                                        <option value="">Tag: todas</option>
                                        <?php foreach (($tagsAtivas ?? []) as $t): ?>
                                            <option value="<?= (int) $t['id'] ?>"><?= esc($t['nome']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="cm-filter-checks">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="filtroConversaNaoLidas">
                                        <label class="form-check-label small" for="filtroConversaNaoLidas">N&atilde;o lidas</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="filtroConversaOsAberta">
                                        <label class="form-check-label small" for="filtroConversaOsAberta">Com OS aberta</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="filtroConversaClientesNovos">
                                        <label class="form-check-label small" for="filtroConversaClientesNovos">Clientes novos</label>
                                    </div>
                                </div>
                                <div class="cm-filter-btn">
                                    <button class="btn btn-sm btn-primary px-3" id="btnFiltrarConversas">Aplicar</button>
                                    <button class="btn btn-sm btn-outline-secondary px-3" id="btnLimparFiltros">Limpar</button>
                                </div>
                            </div>
                        </div>
                        <div class="cm-quick-filter-bar px-3 pt-2 pb-1">
                            <button type="button" class="btn btn-sm btn-outline-secondary cm-quick-filter-btn active" data-cm-quick-filter="all">Todas</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary cm-quick-filter-btn" data-cm-quick-filter="unread">Nao lidas</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary cm-quick-filter-btn" data-cm-quick-filter="open">Abertas</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary cm-quick-filter-btn" data-cm-quick-filter="archived">Arquivadas</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary cm-quick-filter-btn" data-cm-quick-filter="os">Com OS</button>
                        </div>
                        <div id="cmFilterFeedback" class="cm-filter-feedback small text-muted px-3 pb-2">
                            Sem filtros ativos.
                        </div>
                        <div id="conversaList" class="cm-scroll flex-grow-1 p-2">
                            <div class="cm-empty-state">
                                <i class="bi bi-chat-left-text"></i>
                                <p class="mb-0">Carregando conversas...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </aside>

        <section class="cm-col-chat coluna-chat">
            <div class="card glass-card cm-panel mb-0">
                <div class="card-header cm-thread-header">
                    <div class="cm-thread-header-top">
                        <div class="cm-thread-identity">
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-secondary cm-mobile-list-trigger d-lg-none"
                                data-bs-toggle="offcanvas"
                                data-bs-target="#cmConversasCanvas"
                                aria-controls="cmConversasCanvas"
                            >
                                <i class="bi bi-chat-left-text me-1"></i>Conversas
                            </button>
                            <div class="cm-thread-avatar">
                                <i class="bi bi-person-circle"></i>
                            </div>
                            <div class="min-w-0">
                                <div class="fw-semibold text-truncate" id="threadTitle">Selecione uma conversa</div>
                                <small class="text-muted text-truncate d-block" id="threadSubtitle">Sem conversa ativa</small>
                            </div>
                        </div>

                        <div class="cm-thread-header-actions" id="cmChatActionsBar">
                            <button
                                type="button"
                                class="btn cm-header-chip cm-header-chip--status is-status-aberta"
                                id="threadStatusBadge"
                                data-label-prefix="Status"
                                title="Alterar status da conversa"
                                disabled
                            >Status: -</button>

                            <button
                                type="button"
                                class="btn cm-header-chip cm-header-chip--priority is-priority-normal"
                                id="btnPrioridadeConversa"
                                title="Definir prioridade"
                            >
                                <i class="bi bi-flag me-1" aria-hidden="true"></i>
                                <span id="btnPrioridadeConversaLabel">Prioridade: Normal</span>
                            </button>

                            <button
                                type="button"
                                class="btn btn-sm btn-outline-secondary cm-thread-context-toggle"
                                id="btnToggleContextDock"
                                title="Ocultar painel de contexto"
                                aria-pressed="false"
                            >
                                <i class="bi bi-layout-sidebar-inset-reverse"></i>
                                <span class="visually-hidden">Ocultar contexto</span>
                            </button>

                            <div class="dropdown cm-thread-actions-dropdown">
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-secondary cm-thread-actions-trigger"
                                    id="btnMaisAcoesConversa"
                                    data-bs-toggle="dropdown"
                                    data-bs-auto-close="outside"
                                    title="Acoes da conversa"
                                    aria-label="Abrir menu de acoes da conversa"
                                >
                                    <i class="bi bi-list"></i>
                                </button>

                                <ul class="dropdown-menu dropdown-menu-end cm-chat-actions-menu cm-thread-actions-menu">
                                    <li><h6 class="dropdown-header">Modo de atendimento</h6></li>
                                    <li>
                                        <button class="dropdown-item" type="button" id="btnModoAtendimento" title="Alternar bot ativo" aria-pressed="false">
                                            <i class="bi bi-robot me-2"></i><span id="btnModoAtendimentoLabel">Bot desativado</span>
                                        </button>
                                    </li>
                                    <li>
                                        <button class="dropdown-item" type="button" id="btnModoAguardandoHumano" title="Alternar aguardando atendimento humano" aria-pressed="true">
                                            <i class="bi bi-person me-2"></i><span>Aguardando atendimento humano</span>
                                        </button>
                                    </li>

                                    <li><hr class="dropdown-divider"></li>
                                    <li><h6 class="dropdown-header">Acoes operacionais</h6></li>
                                    <li>
                                        <button class="dropdown-item" type="button" id="btnAssumirConversa" title="Assumir conversa">
                                            <i class="bi bi-person-check me-2"></i>Assumir
                                        </button>
                                    </li>
                                    <li>
                                        <button class="dropdown-item" type="button" id="btnAtribuirConversa" title="Atribuir responsavel">
                                            <i class="bi bi-diagram-3 me-2"></i>Atribuir
                                        </button>
                                    </li>
                                    <li>
                                        <button class="dropdown-item text-danger" type="button" id="btnEncerrarConversa" title="Encerrar conversa">
                                            <i class="bi bi-x-circle me-2"></i>Encerrar
                                        </button>
                                    </li>

                                    <li><hr class="dropdown-divider"></li>
                                    <li><h6 class="dropdown-header">Acoes de fila</h6></li>
                                    <li>
                                        <button class="dropdown-item" type="button" id="btnNovaConversa" title="Nova conversa">
                                            <i class="bi bi-plus-lg me-2"></i>Nova conversa
                                        </button>
                                    </li>
                                    <li>
                                        <button class="dropdown-item" type="button" id="btnSyncInbound" title="Sincronizar inbound">
                                            <i class="bi bi-arrow-repeat me-2"></i>Sincronizar inbound
                                        </button>
                                    </li>
                                    <li>
                                        <button class="dropdown-item" type="button" id="btnAtualizarConversa" title="Atualizar conversa ativa">
                                            <i class="bi bi-arrow-clockwise me-2"></i>Atualizar conversa
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body d-flex flex-column">
                    <div class="cm-connection-strip" id="cmConnectionStrip" aria-live="polite" hidden>
                        <span class="cm-connection-dot" aria-hidden="true"></span>
                        <span id="cmConnectionText">Conectado ao servidor</span>
                    </div>

                    <div id="threadMessages" class="cm-msg-wrap mb-2">
                        <div class="cm-empty-state">
                            <i class="bi bi-chat-dots"></i>
                            <p class="mb-0">Abra uma conversa para visualizar as mensagens.</p>
                        </div>
                    </div>

                    <button type="button" class="btn cm-jump-bottom d-none" id="cmJumpBottomBtn" title="Ir para mensagens mais recentes">
                        <i class="bi bi-chevron-double-down"></i>
                    </button>

                    <form id="formEnviarMensagem" class="mt-auto cm-messages-form">
                        <?= csrf_field() ?>
                        <input type="hidden" id="cmConversaId" name="conversa_id" value="">

                        <div id="cmReplyPreview" class="cm-reply-preview d-none" aria-live="polite">
                            <div class="cm-reply-preview-content">
                                <div class="cm-reply-preview-label">Respondendo</div>
                                <div class="cm-reply-preview-text" id="cmReplyPreviewText"></div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="cmReplyCancel" title="Cancelar resposta">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>

                        <div class="cm-compose-bar">
                            <div class="cm-compose-actions-left position-relative" id="cmComposeActions">
                                <button type="button" class="btn cm-icon-btn" id="btnAnexarMidia" title="Mais op&ccedil;&otilde;es de envio">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                                <button type="button" class="btn cm-icon-btn" id="btnEmojiPicker" title="Inserir emoji">
                                    <i class="bi bi-emoji-smile"></i>
                                </button>

                                <div class="cm-attach-menu d-none" id="cmAttachMenu">
                                    <button type="button" class="cm-attach-item" data-action="upload-file">
                                        <i class="bi bi-paperclip text-primary"></i>
                                        <span>Enviar arquivo</span>
                                    </button>
                                    <button type="button" class="cm-attach-item" data-action="system-pdf">
                                        <i class="bi bi-file-earmark-pdf text-danger"></i>
                                        <span>Enviar PDF do sistema</span>
                                    </button>
                                    <button type="button" class="cm-attach-item" data-action="message-type">
                                        <i class="bi bi-sliders text-info"></i>
                                        <span>Tipo da mensagem</span>
                                    </button>
                                    <button type="button" class="cm-attach-item" data-action="capture-photo">
                                        <i class="bi bi-camera text-success"></i>
                                        <span>Tirar foto agora</span>
                                    </button>
                                    <button type="button" class="cm-attach-item" data-action="record-audio">
                                        <i class="bi bi-mic text-warning"></i>
                                        <span>Gravar &aacute;udio agora</span>
                                    </button>
                                    <button type="button" class="cm-attach-item" data-action="record-video">
                                        <i class="bi bi-camera-video text-secondary"></i>
                                        <span>Gravar v&iacute;deo agora</span>
                                    </button>
                                </div>

                                <div class="cm-emoji-menu d-none" id="cmEmojiMenu">
                                    <button type="button" class="cm-emoji-btn" data-emoji="&#128578;">&#128578;</button>
                                    <button type="button" class="cm-emoji-btn" data-emoji="&#128521;">&#128521;</button>
                                    <button type="button" class="cm-emoji-btn" data-emoji="&#128077;">&#128077;</button>
                                    <button type="button" class="cm-emoji-btn" data-emoji="&#128079;">&#128079;</button>
                                    <button type="button" class="cm-emoji-btn" data-emoji="&#128591;">&#128591;</button>
                                    <button type="button" class="cm-emoji-btn" data-emoji="&#128640;">&#128640;</button>
                                    <button type="button" class="cm-emoji-btn" data-emoji="&#9989;">&#9989;</button>
                                    <button type="button" class="cm-emoji-btn" data-emoji="&#128206;">&#128206;</button>
                                </div>

                                <input type="file" id="cmAnexoInput" name="anexo" class="d-none" accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.xls,.xlsx,.txt,.csv,.zip,.rar,application/pdf">
                                <input type="file" id="cmCameraPhotoInput" class="d-none" accept="image/*" capture="environment">
                                <input type="file" id="cmCameraVideoInput" class="d-none" accept="video/*" capture="environment">
                            </div>

                            <div class="cm-compose-center">
                                <div class="cm-compose-meta-panel d-none" id="cmComposeMetaPanel">
                                    <div class="cm-meta-group" id="cmPdfPickerWrap">
                                        <label class="form-label small mb-1">Selecionar PDF</label>
                                        <select class="form-select form-select-sm" id="cmDocumentoId" name="documento_id">
                                            <option value="">Sem PDF</option>
                                        </select>
                                    </div>
                                    <div class="cm-meta-group" id="cmTipoMensagemWrap">
                                        <label class="form-label small mb-1">Tipo da mensagem</label>
                                        <select class="form-select form-select-sm" id="cmTipoMensagem" name="tipo_mensagem">
                                            <option value="manual">Manual</option>
                                            <option value="orcamento">Or&ccedil;amento</option>
                                            <option value="laudo">Laudo</option>
                                            <option value="status_os">Status OS</option>
                                        </select>
                                    </div>
                                </div>

                                <div id="cmAnexoPreview" class="cm-anexo-preview d-none"></div>

                                <textarea
                                    class="form-control cm-compose-textarea"
                                    name="mensagem"
                                    id="cmMensagem"
                                    rows="1"
                                    placeholder="Digite uma mensagem"
                                ></textarea>
                            </div>

                            <div class="cm-compose-actions-right">
                                <button class="btn cm-send-btn" type="submit" title="Enviar mensagem">
                                    <i class="bi bi-send-fill"></i>
                                </button>
                            </div>
                        </div>
                    </form>

                    <div id="cmCapturePanel" class="cm-capture-panel d-none"></div>
                </div>
            </div>
        </section>

        <aside
            class="offcanvas-xl offcanvas-end cm-side-shell cm-col-context coluna-contexto"
            tabindex="-1"
            id="cmContextoCanvas"
            aria-labelledby="cmContextoCanvasLabel"
        >
            <div class="offcanvas-header border-bottom">
                <h5 class="offcanvas-title" id="cmContextoCanvasLabel">Contexto do Cliente / OS</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Fechar"></button>
            </div>
            <div class="offcanvas-body p-0">
                <div class="card glass-card cm-panel mb-0">
                    <div class="card-body p-0 d-flex flex-column">
                        <div class="cm-context-header px-3 py-2 border-bottom">
                            <h6 class="mb-0">Contexto do Cliente / OS</h6>
                        </div>
                        <div id="contextoConversa" class="cm-scroll cm-context-body small text-muted flex-grow-1 p-2">
                            <div class="cm-empty-state cm-empty-state-sm">
                                <i class="bi bi-person-vcard"></i>
                                <p class="mb-0">Selecione uma conversa para ver dados do cliente, OS e documentos.</p>
                            </div>
                        </div>
                        <div class="cm-quick-replies px-3 py-2 border-top">
                            <h6 class="mb-2">Respostas r&aacute;pidas</h6>
                            <div class="d-flex flex-wrap gap-1" id="respostasRapidasWrap">
                                <?php foreach (($respostasRapidas ?? []) as $r): ?>
                                    <button type="button" class="btn btn-sm btn-outline-secondary btn-resposta-rapida" data-msg="<?= esc($r['mensagem']) ?>">
                                        <?= esc($r['titulo']) ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </aside>
    </div>

    <div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content bg-dark border-0">
                <div class="modal-body p-0 position-relative">
                    <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3 z-3" data-bs-dismiss="modal" aria-label="Close"></button>
                    <button type="button" class="btn btn-dark position-absolute top-50 start-0 translate-middle-y ms-2 z-3 d-none" id="cmImgPrevBtn" aria-label="Imagem anterior">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                    <button type="button" class="btn btn-dark position-absolute top-50 end-0 translate-middle-y me-2 z-3 d-none" id="cmImgNextBtn" aria-label="Pr&oacute;xima imagem">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                    <img id="imageModalImg" src="" class="w-100" style="max-height: 85vh; object-fit: contain;" alt="Visualiza&ccedil;&atilde;o de imagem">
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
window.__CM_DISABLE_LEGACY_SCRIPT__ = true;
window.CM_CFG = {
    baseUrl: window.location.origin,
    basePath: '<?= parse_url(base_url('/'), PHP_URL_PATH) ?: '/' ?>',
    csrfName: '<?= csrf_token() ?>',
    csrfHash: '<?= csrf_hash() ?>',
    autoSyncSeconds: <?= (int) ($autoSyncSeconds ?? 15) ?>,
    slaPrimeiraRespostaMin: <?= (int) ($slaPrimeiraRespostaMin ?? 60) ?>,
    endpointConversas: '<?= parse_url(base_url('atendimento-whatsapp/conversas'), PHP_URL_PATH) ?: '/atendimento-whatsapp/conversas' ?>',
    endpointConversaPrefix: '<?= parse_url(base_url('atendimento-whatsapp/conversa'), PHP_URL_PATH) ?: '/atendimento-whatsapp/conversa' ?>',
    endpointConversaNovasPrefix: '<?= parse_url(base_url('atendimento-whatsapp/conversa-novas'), PHP_URL_PATH) ?: '/atendimento-whatsapp/conversa-novas' ?>',
    endpointCadastrarContatoPrefix: '<?= parse_url(base_url('atendimento-whatsapp/conversa'), PHP_URL_PATH) ?: '/atendimento-whatsapp/conversa' ?>',
    endpointEnviar: '<?= parse_url(base_url('atendimento-whatsapp/enviar'), PHP_URL_PATH) ?: '/atendimento-whatsapp/enviar' ?>',
    endpointVincularOs: '<?= parse_url(base_url('atendimento-whatsapp/vincular-os'), PHP_URL_PATH) ?: '/atendimento-whatsapp/vincular-os' ?>',
    endpointAtualizarMeta: '<?= parse_url(base_url('atendimento-whatsapp/atualizar-meta'), PHP_URL_PATH) ?: '/atendimento-whatsapp/atualizar-meta' ?>',
    endpointSyncInbound: '<?= parse_url(base_url('atendimento-whatsapp/sync-inbound'), PHP_URL_PATH) ?: '/atendimento-whatsapp/sync-inbound' ?>',
    endpointTransferirConversa: '<?= parse_url(base_url('atendimento-whatsapp/transferir-conversa'), PHP_URL_PATH) ?: '/atendimento-whatsapp/transferir-conversa' ?>',
    endpointAtribuirConversa: '<?= parse_url(base_url('atendimento-whatsapp/atribuir-conversa'), PHP_URL_PATH) ?: '/atendimento-whatsapp/atribuir-conversa' ?>',
    endpointEncerrarConversa: '<?= parse_url(base_url('atendimento-whatsapp/encerrar-conversa'), PHP_URL_PATH) ?: '/atendimento-whatsapp/encerrar-conversa' ?>',
    urlClienteVisualizarPrefix: '<?= parse_url(base_url('clientes/visualizar'), PHP_URL_PATH) ?: '/clientes/visualizar' ?>',
    urlOsVisualizarPrefix: '<?= parse_url(base_url('os/visualizar'), PHP_URL_PATH) ?: '/os/visualizar' ?>',
    urlOsNova: '<?= parse_url(base_url('os/nova'), PHP_URL_PATH) ?: '/os/nova' ?>',
    gatewayAccountNumber: '<?= esc($gatewayAccountNumber ?? '') ?>',
    empresaEndereco: '<?= esc(get_config('empresa_endereco', '')) ?>',
    currentUserId: <?= (int) ($currentUserId ?? 0) ?>,
    currentUserName: '<?= esc($currentUserName ?? '') ?>',
    canCreateContato: <?= !empty($canCreateContato) ? 'true' : 'false' ?>,
    enableSse: <?= !empty($enableSse) ? 'true' : 'false' ?>
};
</script>
<?php $cmJsVersion = @filemtime(FCPATH . 'assets/js/central-mensagens.js') ?: '20260401'; ?>
<script src="<?= base_url('assets/js/central-mensagens.js') ?>?v=<?= $cmJsVersion ?>"></script>
<?= $this->endSection() ?>
