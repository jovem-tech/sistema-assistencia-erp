?<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<style>
    #mainContent {
        display: flex;
        flex-direction: column;
        height: 100vh;
        overflow: hidden;
    }
    .page-content {
        padding: 0 !important;
        display: flex;
        flex-direction: column;
        flex: 1;
        overflow: hidden;
    }
    .cm-page-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        flex-wrap: wrap;
        padding: .85rem 1.25rem;
        background: transparent;
        border-bottom: 0;
        z-index: 11;
        margin: 0;
    }
    .cm-page-actions {
        display: flex;
        align-items: center;
        gap: .5rem;
        flex-wrap: wrap;
    }
    .cm-page-actions .btn {
        white-space: nowrap;
    }
    .cm-page-actions-mobile {
        display: none;
    }
    .central-mensagens-wrapper {
        display: flex;
        flex: 1 1 auto;
        width: min(1800px, 100%);
        margin: 0 auto .75rem;
        height: calc(100vh - 140px);
        min-height: 620px;
        gap: 0;
        overflow: hidden;
        background: var(--bs-body-bg);
        margin-top: 0;
        border: 1px solid var(--bs-border-color-translucent);
        border-radius: 18px;
        box-shadow: 0 6px 24px rgba(15, 23, 42, .08);
    }
    .coluna-conversas,
    .coluna-chat,
    .coluna-contexto {
        height: 100%;
        overflow-y: auto;
        min-height: 100%;
        min-width: 0;
    }
    .coluna-conversas {
        width: clamp(300px, 28%, 420px);
        max-width: clamp(300px, 28%, 420px);
        flex: 0 0 clamp(300px, 28%, 420px);
        border-right: 1px solid var(--bs-border-color-translucent);
    }
    .coluna-chat {
        flex: 1 1 clamp(520px, 44%, 940px);
        width: clamp(520px, 44%, 940px);
        max-width: 100%;
        min-width: 0;
    }
    .coluna-contexto {
        width: clamp(300px, 28%, 420px);
        max-width: clamp(300px, 28%, 420px);
        flex: 0 0 clamp(300px, 28%, 420px);
        border-left: 1px solid var(--bs-border-color-translucent);
    }
    .cm-col-left,
    .cm-col-chat,
    .cm-col-context {
        min-width: 0;
        min-height: 0;
    }
    .min-w-0 {
        min-width: 0;
    }
    .cm-side-shell {
        border: 0;
        background: transparent;
    }
    .cm-side-shell .offcanvas-body {
        min-height: 0;
        padding: 0;
    }
    .cm-panel {
        height: 100%;
        min-height: 0;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }
    .cm-panel .card-header {
        flex: 0 0 auto;
        border-bottom: 1px solid var(--bs-border-color-translucent);
        padding: .65rem .75rem;
    }
    .cm-panel .card-body {
        flex: 1 1 auto;
        min-height: 0;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        position: relative;
    }
    .cm-scroll {
        min-height: 0;
        overflow-y: auto;
    }
    .cm-list-filters {
        position: sticky;
        top: 0;
        z-index: 10;
        padding: .6rem .8rem !important;
        background: color-mix(in srgb, var(--bs-body-bg) 95%, transparent);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border-bottom: 1px solid var(--bs-border-color-translucent);
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .5rem;
    }
    .cm-filter-q { flex: 1 1 180px; min-width: 140px; }
    .cm-filter-select { flex: 1 1 110px; min-width: 100px; max-width: 180px; }
    .cm-filter-checks { 
        display: flex; 
        align-items: center; 
        gap: .75rem; 
        padding: 0 .25rem;
        white-space: nowrap;
    }
    .cm-filter-btn { flex: 0 0 auto; }
    
    @media (max-width: 575.98px) {
        .cm-filter-q { flex: 1 0 100%; }
        .cm-filter-select { flex: 1 0 45%; }
        .cm-filter-checks { flex: 1 0 100%; justify-content: flex-start; margin: .25rem 0; }
        .cm-filter-btn { flex: 1 0 100%; }
    }
    .cm-conversa-item {
        cursor: pointer;
        transition: border-color .15s ease, transform .15s ease, background-color .15s ease;
    }
    .cm-conversa-item:hover {
        transform: translateY(-1px);
    }
    .cm-conversa-item.active {
        border-color: var(--ds-color-primary, #635bff) !important;
        background: rgba(99, 91, 255, 0.08);
    }
    .cm-conversa-preview {
        line-height: 1.3;
        min-height: 34px;
        word-break: break-word;
        overflow-wrap: anywhere;
    }
    .cm-preview-prefix {
        font-weight: 600;
        margin-right: 4px;
    }
    .cm-preview-prefix.inbound { color: #4b5563; }
    .cm-preview-prefix.outbound { color: #1f6f2f; }
    .cm-preview-prefix.bot { color: #5b21b6; }
    .cm-unread-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
        background: #ef4444;
        margin-left: 6px;
    }
    .cm-thread-tools {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
    }
    .cm-mobile-list-trigger,
    .cm-mobile-context-trigger {
        display: none !important;
    }
    .cm-msg-wrap {
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto;
        overflow-x: hidden;
        scrollbar-gutter: stable both-edges;
        padding: 1.25rem .75rem;
        position: relative;
        background-color: #e5ddd5; /* Cor padrão clara */
        transition: background-color .3s ease;
    }
    
    .cm-msg-wrap::before {
        content: "";
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        background-image: url('<?= base_url('assets/img/sistema/whatsapp_chat_bg.png') ?>');
        background-repeat: repeat;
        background-size: 420px;
        opacity: 0.06; /* Efeito doodle sutil */
        pointer-events: none;
        z-index: 1;
    }

    [data-bs-theme="dark"] .cm-msg-wrap {
        background-color: #0b141a; /* Verde escuro profundo do WhatsApp */
    }
    
    [data-bs-theme="dark"] .cm-msg-wrap::before {
        opacity: 0.04;
        filter: invert(1); /* Inverte para branco em fundo escuro */
    }

    .cm-msg-row {
        display: flex;
        margin-bottom: .6rem;
        position: relative;
        z-index: 2; /* Acima do doodle */
    }
    .cm-msg-row.inbound { justify-content: flex-start; }
    .cm-msg-row.outbound { justify-content: flex-end; }
    .cm-bubble {
        max-width: min(78%, 720px);
        padding: 10px 12px;
        border-radius: 12px;
        margin-bottom: 10px;
        word-break: break-word;
        overflow-wrap: anywhere;
    }
    .cm-bubble.inbound {
        background: #f1f3f5;
        color: #111;
        border-top-left-radius: 4px;
    }
    .cm-bubble.outbound {
        background: #d9fdd3;
        color: #1f1f1f;
        border: 1px solid #cdeec7;
        border-top-right-radius: 4px;
    }
    .cm-msg-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        margin-bottom: 6px;
        font-size: 11px;
        opacity: .9;
    }
    .cm-msg-origin {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .3px;
    }
    .cm-msg-meta {
        font-size: 11px;
        opacity: .75;
        margin-top: 4px;
    }
    .cm-bubble.outbound .cm-msg-meta { color: #3f4b3f; }
    .cm-bubble.outbound .cm-msg-origin { color: #1f6f2f; }
    .cm-bubble.inbound .cm-msg-origin { color: #3f4952; }
    .cm-bubble.inbound .cm-msg-meta { color: #5f6770; }
    .cm-bubble.outbound.cm-origin-sistema {
        background: #e8f1ff;
        border-color: #cfe1ff;
    }
    .cm-bubble.outbound.cm-origin-sistema .cm-msg-origin { color: #1d4ed8; }
    .cm-bubble.outbound.cm-origin-sistema .cm-msg-meta { color: #334155; }
    .cm-bubble.outbound.cm-origin-externo {
        background: #d9fdd3;
        border-color: #cdeec7;
    }
    .cm-bubble.outbound.cm-origin-externo .cm-msg-origin { color: #1f6f2f; }
    .cm-bubble.outbound.cm-origin-chatbot {
        background: #f1eaff;
        border-color: #ddd0ff;
    }
    .cm-bubble.outbound.cm-origin-chatbot .cm-msg-origin { color: #6d28d9; }
    .cm-bubble.outbound.cm-origin-chatbot .cm-msg-meta { color: #5b21b6; }
    .cm-msg-via {
        border-radius: 999px;
        font-size: 10px;
        font-weight: 700;
        padding: 3px 10px;
        text-transform: lowercase;
        letter-spacing: .2px;
    }
    .cm-msg-via.cm-via-sistema {
        color: #1d4ed8;
        background: #dbeafe;
    }
    .cm-msg-via.cm-via-externo {
        color: #166534;
        background: #dcfce7;
    }
    .cm-msg-via.cm-via-chatbot {
        color: #6d28d9;
        background: #ede9fe;
    }
    .cm-bubble.inbound .cm-reply-btn { opacity: .85; }
    .cm-bubble.inbound:hover .cm-reply-btn { opacity: 1; }
    .cm-msg-unread-sep {
        text-align: center;
        margin: 8px 0 12px;
    }
    .cm-msg-unread-sep span {
        display: inline-block;
        font-size: 11px;
        font-weight: 600;
        color: #b42318;
        background: #fff5f5;
        border: 1px solid #fecaca;
        border-radius: 999px;
        padding: 2px 8px;
    }
    .cm-thread-empty {
        color: #6c757d;
        font-size: .9rem;
        position: relative;
        z-index: 2;
    }
    .cm-media-image-thumb {
        max-width: min(100%, 240px);
        max-height: min(46vh, 240px);
        object-fit: cover;
        border-radius: 10px;
        border: 1px solid rgba(255,255,255,.15);
        transition: transform .18s ease, box-shadow .18s ease;
    }
    .cm-media-image-link:hover .cm-media-image-thumb {
        transform: translateY(-1px);
        box-shadow: 0 .4rem 1rem rgba(0,0,0,.2);
    }
    .cm-video-box video {
        max-width: min(100%, 320px);
        max-height: min(50vh, 280px);
        border-radius: 10px;
        border: 1px solid rgba(255,255,255,.15);
        background: #000;
    }
    .cm-audio-shell {
        min-width: 230px;
        max-width: 320px;
        border: 1px solid rgba(108, 117, 125, .2);
        border-radius: 12px;
        padding: 8px 10px;
        background: rgba(255,255,255,.06);
    }
    .cm-audio-shell.outbound {
        border-color: rgba(25, 135, 84, .25);
        background: rgba(25, 135, 84, .08);
    }
    .cm-audio-play {
        width: 32px;
        height: 32px;
        border: 0;
        border-radius: 50%;
        background: var(--bs-primary);
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 32px;
    }
    .cm-audio-range { width: 100%; }
    .cm-audio-time {
        font-size: 11px;
        color: #6c757d;
        min-width: 46px;
        text-align: right;
    }
    .cm-file-card {
        border: 1px solid rgba(108, 117, 125, .25);
        border-radius: 10px;
        padding: 8px;
        background: rgba(255,255,255,.04);
    }
    .cm-file-card .btn {
        white-space: nowrap;
    }
    .cm-upload-chip {
        border: 1px dashed rgba(108, 117, 125, .45);
        border-radius: 10px;
        padding: 8px;
        background: rgba(255,255,255,.04);
    }
    .cm-context-body {
        min-height: 0;
        overflow-wrap: anywhere;
    }
    .cm-messages-form {
        margin-top: auto;
        border-top: 1px solid var(--bs-border-color-translucent);
        padding: .75rem 1rem;
        background: var(--bs-body-bg);
        position: sticky;
        bottom: 0;
        z-index: 4;
    }
    .cm-compose-bar {
        display: flex;
        align-items: flex-end;
        gap: .75rem;
        width: 100%;
        position: relative;
    }
    .cm-compose-actions-left {
        flex: 0 0 auto;
        margin-bottom: 2px;
    }
    .cm-compose-center {
        flex: 1 1 auto;
        min-width: 0;
        position: relative;
    }
    .cm-compose-textarea {
        border-radius: 22px !important;
        border: 1px solid var(--bs-border-color-translucent) !important;
        background: var(--bs-tertiary-bg) !important;
        padding: 9px 16px !important;
        min-height: 44px !important;
        max-height: 180px !important;
        resize: none !important;
        line-height: 1.5;
        font-size: .95rem;
        box-shadow: none !important;
        transition: border-color .15s ease;
    }
    .cm-compose-textarea:focus {
        border-color: var(--bs-primary-border-subtle) !important;
        background: var(--bs-body-bg) !important;
    }
    .cm-anexo-preview {
        position: absolute;
        bottom: 100%;
        left: 0;
        right: 0;
        margin-bottom: .5rem;
        padding: .5rem;
        background: var(--bs-body-bg);
        border: 1px solid var(--bs-border-color-translucent);
        border-radius: 12px;
        box-shadow: 0 -4px 12px rgba(0,0,0,.06);
        z-index: 5;
    }
    .cm-compose-meta {
        flex: 0 0 160px;
        display: flex;
        flex-direction: column;
        gap: .35rem;
        margin-bottom: 2px;
    }
    .cm-compose-meta .form-select {
        font-size: .75rem;
        padding-top: .15rem;
        padding-bottom: .15rem;
        background-color: var(--bs-tertiary-bg);
        border-color: transparent;
    }
    .cm-compose-actions-right {
        flex: 0 0 auto;
        margin-bottom: 2px;
    }
    .cm-icon-btn {
        width: 42px;
        height: 42px;
        border-radius: 50% !important;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0 !important;
        font-size: 1.25rem;
        color: #64748b;
        border: 0 !important;
        background: transparent !important;
        transition: background-color .15s ease, color .15s ease;
    }
    .cm-icon-btn:hover {
        background: var(--bs-secondary-bg) !important;
        color: #0f172a;
    }

    /* Menu de Anexos Estilizado (Glassmorphism) */
    .cm-attach-menu {
        position: absolute;
        bottom: calc(100% + 12px);
        left: 0;
        min-width: 240px;
        background: var(--bg-glass);
        backdrop-filter: blur(15px);
        -webkit-backdrop-filter: blur(15px);
        border: 1px solid var(--bs-border-color-translucent);
        border-radius: 16px;
        box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.15);
        padding: 0.6rem;
        z-index: 1060;
        display: flex;
        flex-direction: column;
        gap: 3px;
        animation: cm-fade-up 0.2s ease-out;
    }

    .cm-attach-item {
        display: flex;
        align-items: center;
        gap: 12px;
        width: 100%;
        padding: 0.65rem 1rem;
        background: transparent;
        border: 0;
        border-radius: 10px;
        color: var(--bs-body-color);
        font-size: 0.88rem;
        text-align: left;
        transition: background-color 0.2s ease, transform 0.1s ease;
    }

    .cm-attach-item i {
        font-size: 1.1rem;
        width: 20px;
        text-align: center;
    }

    .cm-attach-item:hover {
        background: rgba(0, 0, 0, 0.05);
        transform: translateX(3px);
    }

    [data-bs-theme="dark"] .cm-attach-item:hover {
        background: rgba(255, 255, 255, 0.1);
    }

    /* Animação para menus que sobem */
    @keyframes cm-fade-up {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .cm-send-btn {
        width: 44px;
        height: 44px;
        border-radius: 50% !important;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0 !important;
        background: var(--bs-primary) !important;
        border: 0 !important;
        color: #fff !important;
        font-size: 1.2rem;
        box-shadow: 0 2px 8px rgba(99, 91, 255, .32) !important;
        transition: transform .15s ease, box-shadow .15s ease, background-color .15s ease;
    }
    .cm-send-btn:hover {
        transform: scale(1.05);
        background: var(--bs-primary-text-emphasis) !important;
        box-shadow: 0 4px 12px rgba(99, 91, 255, .42) !important;
    }
    .cm-jump-bottom {
        position: absolute;
        right: 1.5rem;
        bottom: 95px;
        z-index: 100;
        width: 42px;
        height: 42px;
        border-radius: 50% !important;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0 !important;
        background: var(--bs-primary) !important;
        border: 1px solid rgba(255,255,255,.2) !important;
        box-shadow: 0 4px 12px rgba(0,0,0,.15) !important;
        color: #fff !important;
        transition: transform .15s ease, opacity .15s ease, color .15s ease;
    }
    .cm-jump-bottom:hover {
        transform: translateY(-2px);
        width: 24px;
        text-align: center;
    }
    .cm-compose-meta-panel {
        position: absolute;
        bottom: 100%;
        left: 0;
        right: 0;
        background: var(--bs-body-bg);
        border: 1px solid var(--bs-border-color-translucent);
        border-radius: 16px;
        box-shadow: 0 -4px 16px rgba(0,0,0,.1);
        padding: .85rem;
        margin-bottom: .75rem;
        z-index: 90;
        display: flex;
        gap: 1.25rem;
        animation: cm-fade-up .2s ease;
    }
    .cm-meta-group { flex: 1; min-width: 0; }
    .cm-capture-panel {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        top: 0;
        background: var(--bs-body-bg);
        z-index: 1050;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 2rem;
        border-radius: inherit;
        backdrop-filter: blur(4px);
        animation: cm-fade-in .3s ease;
    }
    @keyframes cm-fade-in {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    .card-body.d-flex.flex-column {
        position: relative;
    }
    .cm-messages-form {
        position: relative;
    }
    /* Esconder o que sobrar fora da barra quando gravando para não poluir visual */
    .cm-messages-form.is-recording .cm-compose-bar {
        visibility: hidden;
        pointer-events: none;
    }
    .cm-capture-video {
        max-width: 100%;
        max-height: 240px;
        border-radius: 12px;
        background: #000;
        margin-bottom: 1rem;
    }
    .cm-capture-controls {
        display: flex;
        align-items: center;
        gap: 1rem;
        flex-wrap: wrap;
        justify-content: center;
    }
    .btn-record-dot {
        width: 12px;
        height: 12px;
        background: #ef4444;
        border-radius: 50%;
        display: inline-block;
        margin-right: 6px;
        animation: cm-blink 1s infinite;
    }
    @keyframes cm-blink {
        0%, 100% { opacity: 1; }
        50% { opacity: .4; }
    }
    .cm-anexo-chip {
        display: flex;
        align-items: center;
        gap: .5rem;
        padding: .4rem .6rem;
        background: var(--bs-secondary-bg);
        border: 1px solid var(--bs-border-color-translucent);
        border-radius: 10px;
        font-size: .82rem;
        max-width: 100%;
        overflow: hidden;
    }
    .cm-anexo-chip-info {
        flex: 1;
        min-width: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .offcanvas.cm-side-shell {
        --bs-offcanvas-bg: var(--bs-body-bg);
        --bs-offcanvas-border-color: rgba(108,117,125,.25);
    }
    .offcanvas-lg.cm-side-shell {
        --bs-offcanvas-width: min(92vw, 360px);
    }
    .offcanvas-xl.cm-side-shell {
        --bs-offcanvas-width: min(92vw, 380px);
    }
    .offcanvas-end.cm-side-shell {
        width: clamp(300px, 28vw, 420px);
        max-width: 95vw;
    }
    [data-bs-theme="dark"] .cm-bubble.inbound {
        background: rgba(248, 249, 250, .08);
        color: #e5e7eb;
        border: 1px solid rgba(248, 249, 250, .1);
    }
    [data-bs-theme="dark"] .cm-bubble.outbound {
        background: rgba(34, 197, 94, .15);
        color: #ecfdf5;
        border-color: rgba(34, 197, 94, .35);
    }
    [data-bs-theme="dark"] .cm-bubble.outbound.cm-origin-sistema {
        background: rgba(37, 99, 235, .22);
        border-color: rgba(147, 197, 253, .45);
        color: #e2e8f0;
    }
    [data-bs-theme="dark"] .cm-bubble.outbound.cm-origin-chatbot {
        background: rgba(109, 40, 217, .22);
        border-color: rgba(196, 181, 253, .4);
        color: #ede9fe;
    }
    [data-bs-theme="dark"] .cm-msg-via.cm-via-sistema {
        color: #dbeafe;
        background: rgba(30, 64, 175, .45);
    }
    [data-bs-theme="dark"] .cm-msg-via.cm-via-externo {
        color: #dcfce7;
        background: rgba(22, 101, 52, .45);
    }
    [data-bs-theme="dark"] .cm-msg-via.cm-via-chatbot {
        color: #ede9fe;
        background: rgba(76, 29, 149, .45);
    }
    [data-bs-theme="dark"] .central-mensagens-wrapper {
        background: var(--bs-body-bg);
    }
    [data-bs-theme="dark"] .cm-msg-unread-sep span {
        background: rgba(127, 29, 29, .25);
        border-color: rgba(252, 165, 165, .45);
        color: #fecaca;
    }
    @media (max-width: 991.98px) {
        .central-mensagens-wrapper {
            flex-direction: column;
            height: calc(100vh - 132px);
            min-height: 0;
            overflow: visible;
            border-radius: 0;
            border-left: 0;
            border-right: 0;
            box-shadow: none;
        }
        .coluna-conversas,
        .coluna-chat {
            width: 100%;
            max-width: 100%;
            flex: 0 0 auto;
            border-right: 0;
            border-top: 1px solid var(--bs-border-color-translucent);
            min-height: 0;
            overflow-y: visible;
        }
        .coluna-conversas { border-top: 0; }
        .coluna-contexto {
            width: 100%;
            max-width: 100%;
            flex: 0 0 auto;
            border-left: 0;
        }
        .cm-panel {
            height: auto;
            border-radius: 0;
            min-height: 420px;
        }
        .cm-mobile-list-trigger,
        .cm-mobile-context-trigger {
            display: inline-flex !important;
            align-items: center;
            justify-content: center;
        }
        .cm-bubble {
            max-width: 88%;
        }
        .cm-media-image-thumb {
            max-width: min(72vw, 240px);
            max-height: min(60vw, 240px);
        }
        .cm-video-box video {
            max-width: min(80vw, 320px);
            max-height: 240px;
        }
    }
    @media (max-width: 767.98px) {
        .cm-page-header {
            align-items: flex-start;
        }
        .cm-page-actions-desktop {
            display: none !important;
        }
        .cm-page-actions-mobile {
            display: block;
        }
        .cm-panel .card-header {
            padding: .55rem .6rem;
        }
        .cm-panel .card-body {
            padding: .55rem !important;
        }
        .central-mensagens-wrapper {
            margin-bottom: 0;
        }
        .cm-bubble {
            max-width: 94%;
            padding: 9px 10px;
            margin-bottom: 8px;
        }
        .cm-msg-head {
            gap: 6px;
        }
        .cm-audio-shell {
            min-width: 0;
            width: min(100%, 320px);
        }
        .cm-file-card .btn {
            font-size: .72rem;
            padding: .2rem .45rem;
        }
        .cm-messages-form .col-md-7,
        .cm-messages-form .col-md-5,
        .cm-messages-form .col-xl-6,
        .cm-messages-form .col-xl-4,
        .cm-messages-form .col-xl-2 {
            width: 100%;
        }
        .cm-messages-form .btn {
            min-height: 42px;
        }
        .cm-jump-bottom {
            right: .85rem;
            bottom: 110px;
        }
        .cm-compose-bar {
            flex-wrap: wrap;
        }
        .cm-compose-meta {
            flex: 0 0 100%;
            order: 4;
            flex-direction: row;
            margin-top: .25rem;
        }
        .cm-compose-meta .form-select {
            flex: 1 1 0;
        }
    }
    @media (max-width: 1399.98px) and (min-width: 1200px) {
        .coluna-conversas {
            width: clamp(280px, 26%, 360px);
            max-width: clamp(280px, 26%, 360px);
            flex-basis: clamp(280px, 26%, 360px);
        }
        .coluna-contexto {
            width: clamp(280px, 26%, 360px);
            max-width: clamp(280px, 26%, 360px);
            flex-basis: clamp(280px, 26%, 360px);
        }
        .coluna-chat {
            flex-basis: clamp(480px, 48%, 860px);
            width: clamp(480px, 48%, 860px);
        }
    }

    /* Responsividade para novos botões no header */
    #btnSyncInbound,
    #btnNovaConversa {
        display: none !important;
    }

    @media (min-width: 768px) {
        #btnSyncInbound,
        #btnNovaConversa {
            display: inline-flex !important;
        }
    }

    /* Ajuste para botões de ícone único */
    .cm-thread-tools .btn {
        width: 32px;
        height: 32px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    /* ===== Premium SaaS Refinement ===== */
    .cm-page-subtitle {
        color: var(--bs-secondary-color);
        font-size: .8rem;
        letter-spacing: .02em;
    }
    .cm-realtime-badge {
        font-size: .72rem;
        font-weight: 700;
        color: #0f172a;
        background: #f8fafc !important;
    }
    .cm-realtime-badge.live {
        color: #166534;
        background: #dcfce7 !important;
        border-color: #86efac !important;
    }
    .cm-realtime-badge.polling {
        color: #1d4ed8;
        background: #dbeafe !important;
        border-color: #93c5fd !important;
    }
    .cm-realtime-badge.warn {
        color: #92400e;
        background: #fef3c7 !important;
        border-color: #fde68a !important;
    }
    .cm-list-summary {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .5rem;
        padding: .75rem .9rem;
        border-bottom: 1px solid var(--bs-border-color-translucent);
        background: color-mix(in srgb, var(--bs-body-bg) 92%, transparent);
    }
    .cm-list-summary-label {
        font-size: .7rem;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: var(--bs-secondary-color);
        font-weight: 600;
    }
    .cm-list-summary-value {
        font-size: .95rem;
        font-weight: 700;
        color: var(--bs-emphasis-color);
    }
    .cm-filter-btn {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
    }
    .cm-filter-feedback {
        min-height: 24px;
        border-bottom: 1px solid var(--bs-border-color-translucent);
    }
    .cm-empty-state {
        min-height: 140px;
        border: 1px dashed var(--bs-border-color-translucent);
        border-radius: 14px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        gap: .6rem;
        color: var(--bs-secondary-color);
        background: color-mix(in srgb, var(--bs-tertiary-bg) 60%, transparent);
        text-align: center;
        padding: 1.2rem;
    }
    .cm-empty-state i {
        font-size: 1.35rem;
        opacity: .75;
    }
    .cm-empty-state.cm-empty-state-sm {
        min-height: 110px;
        border-radius: 10px;
        font-size: .82rem;
    }
    .cm-conversa-item {
        border: 1px solid var(--bs-border-color-translucent);
        border-radius: 14px;
        padding: .68rem .72rem;
        margin-bottom: .55rem;
        background: color-mix(in srgb, var(--bs-body-bg) 96%, transparent);
        box-shadow: 0 1px 0 rgba(15, 23, 42, .03);
        transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease, background-color .18s ease;
    }
    .cm-conversa-item:hover {
        transform: translateY(-2px);
        border-color: color-mix(in srgb, var(--bs-primary) 45%, var(--bs-border-color-translucent));
        box-shadow: 0 8px 18px rgba(99, 91, 255, .1);
    }
    .cm-conversa-item.active {
        border-color: color-mix(in srgb, var(--bs-primary) 65%, #fff);
        box-shadow: 0 10px 22px rgba(99, 91, 255, .18);
        background: linear-gradient(135deg, rgba(99,91,255,.08), rgba(99,91,255,.02));
    }
    .cm-conversa-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: .5rem;
        min-width: 0;
    }
    .cm-conversa-main {
        min-width: 0;
        display: flex;
        align-items: flex-start;
        gap: .55rem;
    }
    .cm-conversa-avatar {
        flex: 0 0 34px;
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background: linear-gradient(135deg, rgba(99,91,255,.16), rgba(59,130,246,.14));
        color: #3730a3;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: .75rem;
        font-weight: 700;
        text-transform: uppercase;
    }
    .cm-conversa-title {
        line-height: 1.15;
        font-size: .92rem;
        font-weight: 700;
        color: var(--bs-emphasis-color);
        margin-bottom: .1rem;
        white-space: nowrap;
        text-overflow: ellipsis;
        overflow: hidden;
    }
    .cm-conversa-subtitle {
        font-size: .73rem;
        color: var(--bs-secondary-color);
        white-space: nowrap;
        text-overflow: ellipsis;
        overflow: hidden;
    }
    .cm-conversa-preview {
        font-size: .8rem;
        line-height: 1.25;
        margin-top: .3rem;
        color: var(--bs-secondary-color);
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        word-break: break-word;
    }
    .cm-conversa-foot {
        margin-top: .45rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .5rem;
        font-size: .72rem;
        color: var(--bs-secondary-color);
    }
    .cm-conversa-badges {
        display: flex;
        flex-wrap: wrap;
        gap: .28rem;
        margin-top: .42rem;
    }
    .cm-conversa-badges .badge {
        font-size: .66rem;
        font-weight: 700;
        border-radius: 999px;
        letter-spacing: .01em;
    }
    .cm-conversa-status {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        font-weight: 600;
    }
    .cm-unread-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: #ef4444;
        box-shadow: 0 0 0 3px rgba(239, 68, 68, .15);
        margin-left: 0;
    }
    .cm-unread-pill {
        border-radius: 999px;
        min-width: 24px;
        height: 24px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: .72rem;
    }
    .cm-thread-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .6rem;
        padding: .62rem .72rem !important;
    }
    .cm-thread-avatar {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        border: 1px solid var(--bs-border-color-translucent);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #64748b;
        background: color-mix(in srgb, var(--bs-tertiary-bg) 80%, transparent);
        flex: 0 0 38px;
    }
    .cm-thread-avatar i {
        font-size: 1.2rem;
    }
    .cm-thread-tools {
        flex-wrap: wrap;
        justify-content: flex-end;
    }
    .cm-thread-tools .btn {
        width: auto;
        min-height: 33px;
        padding: 0 .52rem;
        border-radius: 10px !important;
        font-size: .74rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .2rem;
    }
    .cm-thread-tools .btn i {
        font-size: .94rem;
    }
    .cm-status-pill {
        font-size: .7rem;
        text-transform: lowercase;
        letter-spacing: .02em;
        padding: .38rem .6rem;
    }
    .cm-msg-wrap {
        border: 1px solid var(--bs-border-color-translucent);
        border-radius: 14px;
        margin: .35rem;
        box-shadow: inset 0 1px 0 rgba(255,255,255,.2);
    }
    .cm-day-separator {
        position: sticky;
        top: 0;
        z-index: 3;
        text-align: center;
        margin: .5rem 0 .95rem;
    }
    .cm-day-separator span {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        padding: .2rem .7rem;
        font-size: .67rem;
        font-weight: 700;
        letter-spacing: .06em;
        text-transform: uppercase;
        color: #475569;
        background: rgba(255, 255, 255, .82);
        border: 1px solid rgba(148, 163, 184, .3);
    }
    [data-bs-theme="dark"] .cm-day-separator span {
        color: #cbd5e1;
        background: rgba(15, 23, 42, .7);
        border-color: rgba(148, 163, 184, .35);
    }
    .cm-msg-row-new .cm-bubble {
        animation: cm-msg-pop .32s ease;
    }
    @keyframes cm-msg-pop {
        from { transform: translateY(8px); opacity: .35; }
        to { transform: translateY(0); opacity: 1; }
    }
    .cm-bubble {
        border-radius: 14px;
        box-shadow: 0 2px 8px rgba(15, 23, 42, .08);
    }
    .cm-msg-meta {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: .32rem;
        line-height: 1;
    }
    .cm-msg-status {
        display: inline-flex;
        align-items: center;
        gap: .2rem;
        font-size: .65rem;
        font-weight: 700;
        color: #64748b;
    }
    .cm-msg-status.is-read {
        color: #0284c7;
    }
    .cm-msg-status.is-failed {
        color: #b91c1c;
    }
    .cm-messages-form {
        padding: .7rem .85rem;
    }
    .cm-compose-bar {
        background: color-mix(in srgb, var(--bs-tertiary-bg) 70%, transparent);
        border: 1px solid var(--bs-border-color-translucent);
        border-radius: 14px;
        padding: .3rem .45rem;
        gap: .45rem;
    }
    .cm-compose-actions-left {
        display: inline-flex;
        align-items: center;
        gap: .15rem;
    }
    .cm-icon-btn {
        width: 38px;
        height: 38px;
        font-size: 1.08rem;
        border-radius: 11px !important;
    }
    .cm-compose-textarea {
        border-radius: 14px !important;
        min-height: 42px !important;
        padding-left: .85rem !important;
        padding-right: .85rem !important;
    }
    .cm-send-btn {
        width: 40px;
        height: 40px;
    }
    .cm-emoji-menu {
        position: absolute;
        bottom: calc(100% + 12px);
        left: 52px;
        z-index: 1061;
        background: var(--bg-glass, var(--bs-body-bg));
        border: 1px solid var(--bs-border-color-translucent);
        border-radius: 12px;
        padding: .45rem;
        display: grid;
        grid-template-columns: repeat(4, minmax(30px, 1fr));
        gap: .25rem;
        box-shadow: 0 8px 20px rgba(15, 23, 42, .18);
    }
    .cm-emoji-btn {
        border: 0;
        background: transparent;
        border-radius: 8px;
        min-width: 32px;
        min-height: 32px;
        font-size: 1.05rem;
        line-height: 1;
    }
    .cm-emoji-btn:hover {
        background: rgba(99, 91, 255, .14);
    }
    .cm-context-header h6,
    .cm-quick-replies h6 {
        font-size: .9rem;
        font-weight: 700;
    }
    .cm-context-section {
        border: 1px solid var(--bs-border-color-translucent);
        border-radius: 12px;
        padding: .62rem .68rem;
        margin-bottom: .55rem;
        background: color-mix(in srgb, var(--bs-body-bg) 97%, transparent);
    }
    .cm-context-section-title {
        font-size: .72rem;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: var(--bs-secondary-color);
        margin-bottom: .45rem;
        font-weight: 700;
    }
    .cm-context-kv {
        display: grid;
        gap: .2rem;
    }
    .cm-context-kv strong {
        color: var(--bs-emphasis-color);
    }
    .cm-context-list {
        margin: 0;
        padding-left: 1rem;
        display: grid;
        gap: .12rem;
    }
    .cm-context-actions {
        display: grid;
        grid-template-columns: 1fr;
        gap: .35rem;
    }
    @media (max-width: 1399.98px) {
        .cm-thread-tools .btn span {
            display: none !important;
        }
        .cm-thread-tools .btn {
            width: 34px;
            padding: 0;
        }
    }
    @media (max-width: 991.98px) {
        .cm-list-summary {
            padding: .62rem .7rem;
        }
        .cm-list-filters {
            padding: .58rem .62rem !important;
        }
        .cm-thread-header {
            padding: .55rem .6rem !important;
        }
        .cm-msg-wrap {
            margin: .2rem;
        }
    }
    @media (max-width: 767.98px) {
        .cm-page-subtitle {
            display: none;
        }
        .cm-thread-tools {
            gap: .32rem !important;
        }
        .cm-thread-tools .btn {
            min-height: 31px;
            width: 31px;
            padding: 0;
        }
        .cm-filter-select {
            min-width: calc(50% - .5rem);
            max-width: none;
            flex: 1 1 calc(50% - .5rem);
        }
        .cm-filter-btn {
            width: 100%;
            justify-content: stretch;
        }
        .cm-filter-btn .btn {
            flex: 1 1 0;
        }
    }
</style>

<div class="page-header cm-page-header">
    <div class="d-flex align-items-center gap-3 flex-wrap">
        <div>
            <h2 class="mb-0"><i class="bi bi-whatsapp me-2"></i>Central de Mensagens</h2>
            <small class="cm-page-subtitle">Inbox operacional em tempo real para atendimento WhatsApp + ERP</small>
        </div>
        <span class="badge rounded-pill text-bg-light border cm-realtime-badge" id="cmRealtimeBadge">
            <i class="bi bi-broadcast-pin me-1"></i>Polling
        </span>
        <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('atendimento-whatsapp')">
            <i class="bi bi-question-circle me-1"></i>Ajuda
        </button>
    </div>
</div>

<span id="gatewayAccountNumber" class="d-none"><?= esc($gatewayAccountNumber ?? '') ?></span>

<div class="central-mensagens-wrapper">
    <aside class="offcanvas-lg offcanvas-start cm-side-shell cm-col-left coluna-conversas" tabindex="-1" id="cmConversasCanvas" aria-labelledby="cmConversasCanvasLabel">
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
                            <div class="cm-list-summary-label">Nao lidas</div>
                            <div class="cm-list-summary-value text-danger-emphasis" id="cmNaoLidasCount">0</div>
                        </div>
                    </div>

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
                                <option value="">Responsavel: todos</option>
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
                                <label class="form-check-label small" for="filtroConversaNaoLidas">Nao lidas</label>
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
        <div class="card glass-card cm-panel">
            <div class="card-header cm-thread-header">
                <div class="cm-thread-identity d-flex align-items-center gap-2 min-w-0">
                    <button type="button" class="btn btn-sm btn-outline-secondary cm-mobile-list-trigger d-lg-none" data-bs-toggle="offcanvas" data-bs-target="#cmConversasCanvas" aria-controls="cmConversasCanvas">
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
                <div class="cm-thread-tools d-flex align-items-center gap-2">
                    <span class="badge cm-status-pill bg-secondary" id="threadStatusBadge">-</span>
                    <button class="btn btn-sm btn-outline-secondary" id="btnAtualizarConversa" title="Atualizar conversa">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-success" id="btnAssumirConversa" title="Assumir conversa">
                        <i class="bi bi-person-check me-1"></i><span class="d-none d-lg-inline">Assumir</span>
                    </button>
                    <button class="btn btn-sm btn-outline-primary" id="btnAtribuirConversa" title="Atribuir responsavel">
                        <i class="bi bi-diagram-3 me-1"></i><span class="d-none d-lg-inline">Atribuir</span>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" id="btnEncerrarConversa" title="Encerrar conversa">
                        <i class="bi bi-check2-circle me-1"></i><span class="d-none d-lg-inline">Encerrar</span>
                    </button>

                    <button class="btn btn-sm btn-outline-secondary" id="btnSyncInbound" title="Sincronizar inbound">
                        <i class="bi bi-arrow-repeat"></i>
                    </button>
                    <button class="btn btn-sm btn-primary" id="btnNovaConversa" title="Nova conversa">
                        <i class="bi bi-plus-lg"></i>
                    </button>

                    <div class="dropdown d-md-none">
                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="dropdown" aria-label="Mais acoes">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <button class="dropdown-item" type="button" id="btnSyncInboundMobile">
                                    <i class="bi bi-arrow-repeat me-1"></i>Sincronizar
                                </button>
                            </li>
                            <li>
                                <button class="dropdown-item" type="button" id="btnNovaConversaMobile">
                                    <i class="bi bi-plus-lg me-1"></i>Nova conversa
                                </button>
                            </li>
                        </ul>
                    </div>

                    <button
                        type="button"
                        class="btn btn-sm btn-outline-secondary cm-mobile-context-trigger d-xl-none"
                        data-bs-toggle="offcanvas"
                        data-bs-target="#cmContextoCanvas"
                        aria-controls="cmContextoCanvas"
                        title="Abrir contexto"
                    >
                        <i class="bi bi-layout-sidebar-inset"></i>
                    </button>
                </div>
            </div>
            <div class="card-body d-flex flex-column">
                <div id="threadMessages" class="cm-msg-wrap mb-2">
                    <div class="cm-empty-state">
                        <i class="bi bi-chat-dots"></i>
                        <p class="mb-0">Abra uma conversa para visualizar as mensagens.</p>
                    </div>
                </div>
                <!-- Botão Ir para o fim flutuante (estilo WhatsApp) -->
                <button type="button" class="btn cm-jump-bottom d-none" id="cmJumpBottomBtn" title="Ir para mensagens mais recentes">
                    <i class="bi bi-chevron-double-down"></i>
                </button>
                <form id="formEnviarMensagem" class="mt-auto cm-messages-form">
                    <?= csrf_field() ?>
                    <input type="hidden" id="cmConversaId" name="conversa_id" value="">
                    
                    <div class="cm-compose-bar">
                        <!-- Hub de Anexos e Opções -->
                        <div class="cm-compose-actions-left position-relative" id="cmComposeActions">
                            <button type="button" class="btn cm-icon-btn" id="btnAnexarMidia" title="Mais opções de envio">
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
                                    <span>Gravar áudio agora</span>
                                </button>
                                <button type="button" class="cm-attach-item" data-action="record-video">
                                    <i class="bi bi-camera-video text-secondary"></i>
                                    <span>Gravar vídeo agora</span>
                                </button>
                            </div>
                            <div class="cm-emoji-menu d-none" id="cmEmojiMenu">
                                <button type="button" class="cm-emoji-btn" data-emoji="?">?</button>
                                <button type="button" class="cm-emoji-btn" data-emoji="?">?</button>
                                <button type="button" class="cm-emoji-btn" data-emoji="?">?</button>
                                <button type="button" class="cm-emoji-btn" data-emoji="?">?</button>
                                <button type="button" class="cm-emoji-btn" data-emoji="?">?</button>
                                <button type="button" class="cm-emoji-btn" data-emoji="?">?</button>
                                <button type="button" class="cm-emoji-btn" data-emoji="?">?</button>
                                <button type="button" class="cm-emoji-btn" data-emoji="?">?</button>
                            </div>

                            <!-- Inputs ocultos preservados e novos para capturas -->
                            <input type="file" id="cmAnexoInput" name="anexo" class="d-none"
                                   accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.xls,.xlsx,.txt,.csv,.zip,.rar,application/pdf">
                            
                            <input type="file" id="cmCameraPhotoInput" class="d-none" 
                                   accept="image/*" capture="environment">
                            
                            <input type="file" id="cmCameraVideoInput" class="d-none" 
                                   accept="video/*" capture="environment">
                        </div>

                        <!-- Centro: Campo de Mensagem e Painéis Flutuantes -->
                        <div class="cm-compose-center">
                            <!-- Painel de Meta (PDF/Tipo) - Acima da barra quando ativo -->
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
                                        <option value="orcamento">Orcamento</option>
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
                                oninput='this.style.height = "";this.style.height = this.scrollHeight + "px"'
                            ></textarea>
                        </div>

                        <!-- Lado Direito: Envio -->
                        <div class="cm-compose-actions-right">
                            <button class="btn cm-send-btn" type="submit" title="Enviar mensagem">
                                <i class="bi bi-send-fill"></i>
                            </button>
                        </div>
                    </div>

                </form>
                <!-- Painel de Captura de Mídia (MediaRecorder) - Centralizado -->
                <div id="cmCapturePanel" class="cm-capture-panel d-none"></div>
            </div>
        </div>
    </section>

    <aside class="offcanvas-xl offcanvas-end cm-side-shell cm-col-context coluna-contexto" tabindex="-1" id="cmContextoCanvas" aria-labelledby="cmContextoCanvasLabel">
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
                        <h6 class="mb-2">Respostas rapidas</h6>
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
                <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3 z-3"
                        data-bs-dismiss="modal" aria-label="Close"></button>
                <button type="button" class="btn btn-dark position-absolute top-50 start-0 translate-middle-y ms-2 z-3 d-none"
                        id="cmImgPrevBtn" aria-label="Imagem anterior">
                    <i class="bi bi-chevron-left"></i>
                </button>
                <button type="button" class="btn btn-dark position-absolute top-50 end-0 translate-middle-y me-2 z-3 d-none"
                        id="cmImgNextBtn" aria-label="Proxima imagem">
                    <i class="bi bi-chevron-right"></i>
                </button>
                <img id="imageModalImg" src="" class="w-100" style="max-height: 85vh; object-fit: contain;" alt="Visualizacao de imagem">
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
    endpointCadastrarContatoPrefix: '<?= parse_url(base_url('atendimento-whatsapp/conversa'), PHP_URL_PATH) ?: '/atendimento-whatsapp/conversa' ?>',
    endpointEnviar: '<?= parse_url(base_url('atendimento-whatsapp/enviar'), PHP_URL_PATH) ?: '/atendimento-whatsapp/enviar' ?>',
    endpointVincularOs: '<?= parse_url(base_url('atendimento-whatsapp/vincular-os'), PHP_URL_PATH) ?: '/atendimento-whatsapp/vincular-os' ?>',
    endpointAtualizarMeta: '<?= parse_url(base_url('atendimento-whatsapp/atualizar-meta'), PHP_URL_PATH) ?: '/atendimento-whatsapp/atualizar-meta' ?>',
    endpointSyncInbound: '<?= parse_url(base_url('atendimento-whatsapp/sync-inbound'), PHP_URL_PATH) ?: '/atendimento-whatsapp/sync-inbound' ?>',
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
<?php if (false): ?>
<script type="text/plain" id="cmLegacyScriptDisabled">
(() => {
    if (window.__CM_DISABLE_LEGACY_SCRIPT__) {
        return;
    }
    const listEl = document.getElementById('conversaList');
    const filtroQ = document.getElementById('filtroConversaQ');
    const filtroStatus = document.getElementById('filtroConversaStatus');
    const filtroResponsavel = document.getElementById('filtroConversaResponsavel');
    const filtroTag = document.getElementById('filtroConversaTag');
    const filtroNaoLidas = document.getElementById('filtroConversaNaoLidas');
    const filtroOsAberta = document.getElementById('filtroConversaOsAberta');
    const btnFiltrar = document.getElementById('btnFiltrarConversas');
    const btnSyncInbound = document.getElementById('btnSyncInbound');
    const btnNovaConversa = document.getElementById('btnNovaConversa');

    const threadTitle = document.getElementById('threadTitle');
    const threadSubtitle = document.getElementById('threadSubtitle');
    const threadStatusBadge = document.getElementById('threadStatusBadge');
    const threadMessages = document.getElementById('threadMessages');
    const formEnviar = document.getElementById('formEnviarMensagem');
    const conversaIdInput = document.getElementById('cmConversaId');
    const msgInput = document.getElementById('cmMensagem');
    const tipoMensagemInput = document.getElementById('cmTipoMensagem');
    const documentoSelect = document.getElementById('cmDocumentoId');
    const contextoEl = document.getElementById('contextoConversa');

    let currentConversaId = null;
    let activeConversationUnread = 0;
    let pollTimer = null;
    const autoSyncIntervalMs = Math.max(5000, Number('<?= (int) ($autoSyncSeconds ?? 15) ?>') * 1000);
    const slaPrimeiraRespostaMin = Math.max(1, Number('<?= (int) ($slaPrimeiraRespostaMin ?? 60) ?>'));
    const swal = (opts) => (window.Swal ? window.Swal.fire(opts) : Promise.resolve(alert(opts.text || opts.title || 'OK')));
    const urlParams = new URLSearchParams(window.location.search);
    const initialQ = (urlParams.get('q') || '').trim();
    const initialConversaId = Number(urlParams.get('conversa_id') || 0);

    if (initialQ) {
        filtroQ.value = initialQ;
    }

    const csrfName = '<?= csrf_token() ?>';
    const csrfHash = '<?= csrf_hash() ?>';

    const escapeHtml = (str) => {
        const div = document.createElement('div');
        div.textContent = str ?? '';
        return div.innerHTML;
    };

    const postForm = async (url, payload) => {
        const fd = new FormData();
        Object.entries(payload).forEach(([k, v]) => fd.append(k, v ?? ''));
        fd.append(csrfName, csrfHash);
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: fd
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok || !data.ok) {
            throw new Error(data.message || 'Falha na requisicao');
        }
        return data;
    };

    const getJson = async (url) => {
        const res = await fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            cache: 'no-store'
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok || !data.ok) {
            throw new Error(data.message || 'Falha na requisicao');
        }
        return data;
    };

    const resolveArquivoUrl = (arquivo) => {
        const safe = encodeURIComponent(String(arquivo || '')).replace(/%2F/g, '/');
        return `<?= base_url() ?>/${safe}?v=${Date.now()}`;
    };

    const renderArquivoHtml = (m) => {
        if (!m?.arquivo) return '';

        const arquivo = String(m.arquivo || '');
        const mime = String(m.mime_type || '').toLowerCase();
        const url = resolveArquivoUrl(arquivo);
        const label = escapeHtml(arquivo.split('/').pop() || arquivo);

        const isImage = mime.startsWith('image/') || /\.(png|jpe?g|webp|gif)$/i.test(arquivo);
        if (isImage) {
            return `
                <div class="mt-2">
                    <a href="${url}" target="_blank" rel="noopener" class="d-inline-block text-decoration-none">
                        <img src="${url}" alt="${label}" class="rounded border" style="max-width: 180px; max-height: 180px; object-fit: cover;">
                    </a>
                    <div class="small mt-1"><a href="${url}" target="_blank" rel="noopener">Abrir imagem</a></div>
                </div>
            `;
        }

        const isPdf = mime === 'application/pdf' || /\.pdf$/i.test(arquivo);
        return `
            <div class="mt-2">
                <a href="${url}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">
                    <i class="bi ${isPdf ? 'bi-file-earmark-pdf' : 'bi-paperclip'} me-1"></i>${isPdf ? 'Abrir PDF' : 'Abrir anexo'}
                </a>
                <div class="small mt-1 text-muted">${label}</div>
            </div>
        `;
    };

    const renderConversaItem = (item) => {
        const isActive = currentConversaId === Number(item.id);
        const nome = item.cliente_nome || item.nome_contato || item.telefone || 'Contato sem nome';
        const unread = Number(item.nao_lidas || 0);
        const automacaoAtiva = Number(item.automacao_ativa ?? 1) === 1;
        const aguardandoHumano = Number(item.aguardando_humano || 0) === 1;
        const prioridade = String(item.prioridade || 'normal').toLowerCase();
        const hasOs = !!item.numero_os;
        const ultimaMensagemAt = item.ultima_mensagem_em
            ? new Date(String(item.ultima_mensagem_em).replace(' ', 'T'))
            : null;
        const diffMs = ultimaMensagemAt ? (Date.now() - ultimaMensagemAt.getTime()) : 0;
        const slaEstourado = unread > 0 && diffMs > (slaPrimeiraRespostaMin * 60 * 1000);
        const subtitle = [item.telefone, item.numero_os ? ('OS ' + item.numero_os) : null].filter(Boolean).join(' | ');
        const lastDirection = String(item.ultima_mensagem_direcao || '').toLowerCase();
        const lastBot = Number(item.ultima_mensagem_bot || 0) === 1;
        const previewPrefix = lastDirection === 'inbound'
            ? '<span class="cm-preview-prefix inbound">Cliente:</span>'
            : (lastBot
                ? '<span class="cm-preview-prefix bot">Bot:</span>'
                : '<span class="cm-preview-prefix outbound">Voce:</span>');
        const ultimaMensagemBruta = item.ultima_mensagem_texto
            ? item.ultima_mensagem_texto
            : (item.ultima_mensagem_tipo && item.ultima_mensagem_tipo !== 'texto' ? `[${item.ultima_mensagem_tipo}]` : 'Sem mensagens');
        const ultimaMensagem = `<span class="cm-conversa-preview">${previewPrefix}${escapeHtml(ultimaMensagemBruta)}</span>`;
        const ultimaData = item.ultima_mensagem_em ? String(item.ultima_mensagem_em).replace('T', ' ').substring(0, 16) : '';
        const responsavel = item.responsavel_nome || 'Nao atribuido';
        const prioridadeBadge = prioridade !== 'normal'
            ? `<span class="badge text-bg-${prioridade === 'urgente' ? 'danger' : (prioridade === 'alta' ? 'warning text-dark' : 'secondary')}">${escapeHtml(prioridade)}</span>`
            : '';
        const flags = [
            automacaoAtiva
                ? '<span class="badge text-bg-success-subtle text-success-emphasis border">Bot ativo</span>'
                : '<span class="badge text-bg-secondary">Bot off</span>',
            aguardandoHumano ? '<span class="badge text-bg-warning text-dark">Aguard. humano</span>' : '',
            hasOs ? '<span class="badge text-bg-primary">OS vinculada</span>' : '',
            slaEstourado ? '<span class="badge text-bg-danger">SLA estourado</span>' : '',
            prioridadeBadge,
        ].filter(Boolean).join(' ');

        return `
            <div class="border rounded p-2 mb-2 cm-conversa-item ${isActive ? 'active' : ''}" data-id="${item.id}">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="fw-semibold">${escapeHtml(nome)}</div>
                    ${unread > 0 ? `<span class="badge bg-danger">${unread}</span>` : ''}
                </div>
                <div class="small text-muted">${escapeHtml(subtitle)}</div>
                <div class="small text-truncate">${ultimaMensagem}</div>
                <div class="small mt-1 d-flex flex-wrap gap-1">${flags}</div>
                <div class="small mt-1 d-flex justify-content-between">
                    <span>${escapeHtml(item.status || 'aberta')}${unread > 0 ? '<span class="cm-unread-dot"></span>' : ''}</span>
                    <span class="text-muted">${escapeHtml(ultimaData)}</span>
                </div>
                <div class="small text-muted">${escapeHtml(responsavel)}</div>
            </div>
        `;
    };

    const loadConversas = async () => {
        listEl.innerHTML = '<div class="text-muted small p-2">Atualizando conversas...</div>';
        const q = encodeURIComponent((filtroQ.value || '').trim());
        const status = encodeURIComponent((filtroStatus.value || '').trim());
        const responsavelId = encodeURIComponent((filtroResponsavel?.value || '').trim());
        const tagId = encodeURIComponent((filtroTag?.value || '').trim());
        const somenteNaoLidas = filtroNaoLidas?.checked ? '1' : '0';
        const comOsAberta = filtroOsAberta?.checked ? '1' : '0';
        try {
            const data = await getJson(
                `<?= base_url('atendimento-whatsapp/conversas') ?>?q=${q}&status=${status}&responsavel_id=${responsavelId}&tag_id=${tagId}&nao_lidas=${somenteNaoLidas}&com_os_aberta=${comOsAberta}`
            );
            const items = data.items || [];
            if (!items.length) {
                listEl.innerHTML = '<div class="text-muted small p-2">Nenhuma conversa encontrada.</div>';
                return [];
            }

            listEl.innerHTML = items.map(renderConversaItem).join('');
            listEl.querySelectorAll('.cm-conversa-item').forEach((el) => {
                el.addEventListener('click', () => openConversa(Number(el.dataset.id)));
            });
            return items;
        } catch (error) {
            listEl.innerHTML = `<div class="text-danger small p-2">${escapeHtml(error.message || 'Erro ao carregar conversas')}</div>`;
            return [];
        }
    };

    const renderMensagens = (mensagens) => {
        if (!mensagens || !mensagens.length) {
            threadMessages.innerHTML = '<div class="cm-thread-empty">Sem mensagens nesta conversa.</div>';
            return;
        }

        threadMessages.innerHTML = mensagens.map((m, idx) => {
            const outbound = (m.direcao || '').toLowerCase() === 'outbound';
            const enviadaPorBot = Number(m.enviada_por_bot || 0) === 1;
            const origemLabel = outbound ? (enviadaPorBot ? 'Bot' : 'Equipe') : 'Cliente';
            const origemIcon = outbound ? (enviadaPorBot ? 'bi-robot' : 'bi-person-badge') : 'bi-person-circle';
            const body = m.mensagem
                ? escapeHtml(m.mensagem).replace(/\n/g, '<br>')
                : '<span class="text-muted">[mensagem sem texto]</span>';
            const arquivo = renderArquivoHtml(m);
            const when = m.created_at || m.enviada_em || '';
            const replyAction = (!outbound && m.mensagem)
                ? `<button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 cm-reply-btn" data-reply="${encodeURIComponent(String(m.mensagem || ''))}">Responder</button>`
                : '';
            const unreadSeparator = (!outbound && activeConversationUnread > 0 && idx === Math.max(0, mensagens.length - activeConversationUnread))
                ? '<div class="cm-msg-unread-sep"><span>Mensagens nao lidas</span></div>'
                : '';

            return `
                ${unreadSeparator}
                <div class="cm-msg-row ${outbound ? 'outbound' : 'inbound'}">
                    <div class="cm-bubble ${outbound ? 'outbound' : 'inbound'}">
                        <div class="cm-msg-head">
                            <span class="cm-msg-origin"><i class="bi ${origemIcon}"></i>${origemLabel}</span>
                            ${replyAction}
                        </div>
                        <div>${body}</div>
                        ${arquivo}
                        <div class="cm-msg-meta">${escapeHtml((when || '').replace('T', ' ').substring(0, 16))} ${outbound ? '| enviada' : '| recebida'}</div>
                    </div>
                </div>
            `;
        }).join('');

        threadMessages.scrollTop = threadMessages.scrollHeight;
    };

    const renderContexto = (ctx) => {
        const cliente = ctx.cliente || null;
        const osList = ctx.os || [];
        const docs = ctx.documentos || [];
        const followups = ctx.followups || [];
        const osVinculadas = ctx.os_vinculadas || [];
        const meta = ctx.meta || {};
        const statusAtual = String(meta.status || 'aberta');
        const responsavelAtual = Number(meta.responsavel_id || 0);
        const statusOptions = Array.isArray(meta.status_options) && meta.status_options.length
            ? meta.status_options
            : ['aberta', 'aguardando', 'resolvida', 'arquivada'];
        const responsaveis = Array.isArray(meta.responsaveis) ? meta.responsaveis : [];
        const tagCatalogo = Array.isArray(meta.tag_catalogo) ? meta.tag_catalogo : [];
        const tagsSelecionadas = (Array.isArray(meta.tags) ? meta.tags : []).map((v) => Number(v));

        const clienteHtml = cliente ? `
            <div class="mb-2">
                <div class="fw-semibold">${escapeHtml(cliente.nome_razao || '')}</div>
                <div>${escapeHtml(cliente.telefone1 || '')}</div>
                <div>${escapeHtml(cliente.email || '')}</div>
            </div>
        ` : '<div class="text-muted mb-2">Cliente nao identificado automaticamente.</div>';

        const osHtml = osList.length ? osList.map((os) => `
            <option value="${os.id}">OS ${escapeHtml(os.numero_os)} - ${escapeHtml(os.status || '-')}</option>
        `).join('') : '<option value="">Sem OS vinculadas</option>';

        const docsHtml = docs.length
            ? docs.map((d) => `<option value="${d.id}">${escapeHtml((d.tipo_documento || 'documento') + ' - ' + (d.arquivo || ''))}</option>`).join('')
            : '<option value="">Sem PDF disponivel</option>';

        documentoSelect.innerHTML = '<option value="">Sem PDF</option>' + docsHtml;

        const vinculadasHtml = osVinculadas.length
            ? osVinculadas.map((v) => `<li>OS ${escapeHtml(v.numero_os || String(v.os_id))} (${escapeHtml(v.status || '-')})</li>`).join('')
            : '<li class="text-muted">Sem vinculos.</li>';

        const followupsHtml = followups.length
            ? followups.map((f) => `<li>${escapeHtml(f.titulo || '-')}: ${escapeHtml((f.data_prevista || '').replace('T', ' ').substring(0, 16))}</li>`).join('')
            : '<li class="text-muted">Sem follow-ups pendentes.</li>';

        const clienteUrl = cliente && cliente.id ? `<?= base_url('clientes/visualizar') ?>/${cliente.id}` : '';
        const osPrincipalId = Number((osVinculadas[0] && osVinculadas[0].os_id) || (osList[0] && osList[0].id) || 0);
        const osUrl = osPrincipalId > 0 ? `<?= base_url('os/visualizar') ?>/${osPrincipalId}` : '';
        const statusOptionsHtml = statusOptions.map((s) => {
            const sel = statusAtual === s ? 'selected' : '';
            return `<option value="${escapeHtml(s)}" ${sel}>${escapeHtml(s.charAt(0).toUpperCase() + s.slice(1))}</option>`;
        }).join('');
        const responsaveisHtml = ['<option value="">Nao atribuido</option>'].concat(
            responsaveis.map((u) => {
                const id = Number(u.id || 0);
                const sel = id === responsavelAtual ? 'selected' : '';
                return `<option value="${id}" ${sel}>${escapeHtml(u.nome || ('Usuario #' + id))}</option>`;
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
                const cor = t.cor ? `style="background:${escapeHtml(t.cor)}22;border-color:${escapeHtml(t.cor)}"` : '';
                return `
                    <label class="form-check form-check-inline border rounded px-2 py-1 me-1 mb-1 small" ${cor}>
                        <input class="form-check-input me-1 cm-tag-check" type="checkbox" value="${tagId}" ${checked}>
                        <span class="form-check-label">${escapeHtml(t.nome || 'Tag')}</span>
                    </label>
                `;
            }).join('')
            : '<div class="text-muted small">Sem tags CRM cadastradas.</div>';

        contextoEl.innerHTML = `
            ${clienteHtml}
            <div class="mb-2 border rounded p-2" style="background: rgba(255,255,255,0.03);">
                <div class="fw-semibold small mb-2">Gestao da conversa</div>
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
            <div class="mb-2">
                <label class="form-label form-label-sm mb-1">Vincular OS</label>
                <div class="d-flex gap-1">
                    <select class="form-select form-select-sm" id="contextOsSelect">${osHtml}</select>
                    <button class="btn btn-sm btn-outline-primary" id="btnVincularOs">Vincular</button>
                </div>
            </div>
            <div class="mb-2">
                <div class="fw-semibold small">OS vinculadas</div>
                <ul class="mb-0 ps-3">${vinculadasHtml}</ul>
            </div>
            <div class="mb-2">
                <div class="fw-semibold small">Follow-ups pendentes</div>
                <ul class="mb-0 ps-3">${followupsHtml}</ul>
            </div>
            <div class="mt-3 d-grid gap-2">
                ${clienteUrl ? `<a class="btn btn-sm btn-outline-primary" href="${clienteUrl}" target="_blank" rel="noopener">Abrir cliente</a>` : ''}
                ${osUrl ? `<a class="btn btn-sm btn-outline-secondary" href="${osUrl}" target="_blank" rel="noopener">Abrir OS</a>` : ''}
                <a class="btn btn-sm btn-outline-success" href="<?= base_url('os/nova') ?>" target="_blank" rel="noopener">Nova OS</a>
            </div>
        `;

        document.getElementById('btnVincularOs')?.addEventListener('click', async () => {
            const osId = Number(document.getElementById('contextOsSelect')?.value || 0);
            if (!currentConversaId || !osId) {
                await swal({ icon: 'warning', title: 'Vinculo incompleto', text: 'Selecione uma OS para vincular.' });
                return;
            }

            try {
                await postForm('<?= base_url('atendimento-whatsapp/vincular-os') ?>', {
                    conversa_id: currentConversaId,
                    os_id: osId
                });
                await swal({ icon: 'success', title: 'OS vinculada', text: 'Conversa vinculada com sucesso.' });
                await openConversa(currentConversaId, false);
                await loadConversas();
            } catch (error) {
                await swal({ icon: 'error', title: 'Falha ao vincular', text: error.message || 'Erro inesperado.' });
            }
        });

        document.getElementById('btnSalvarMetaConversa')?.addEventListener('click', async () => {
            const status = document.getElementById('contextStatusSelect')?.value || 'aberta';
            const responsavelId = Number(document.getElementById('contextResponsavelSelect')?.value || 0);
            const prioridade = document.getElementById('contextPrioridadeSelect')?.value || 'normal';
            const automacaoAtiva = document.getElementById('contextAutomacaoAtiva')?.checked ? 1 : 0;
            const aguardandoHumano = document.getElementById('contextAguardandoHumano')?.checked ? 1 : 0;
            const tags = Array.from(document.querySelectorAll('#contextTagWrap .cm-tag-check:checked'))
                .map((el) => Number(el.value || 0))
                .filter((id) => id > 0);

            if (!currentConversaId) {
                await swal({ icon: 'warning', title: 'Conversa nao selecionada', text: 'Abra uma conversa para atualizar o contexto.' });
                return;
            }

            try {
                await postForm('<?= base_url('atendimento-whatsapp/atualizar-meta') ?>', {
                    conversa_id: currentConversaId,
                    status: status,
                    responsavel_id: responsavelId > 0 ? responsavelId : '',
                    tag_ids: JSON.stringify(tags),
                    prioridade: prioridade,
                    automacao_ativa: automacaoAtiva,
                    aguardando_humano: aguardandoHumano,
                });
                await swal({ icon: 'success', title: 'Atualizado', text: 'Contexto da conversa atualizado com sucesso.' });
                await openConversa(currentConversaId, false);
                await loadConversas();
            } catch (error) {
                await swal({ icon: 'error', title: 'Falha ao atualizar', text: error.message || 'Erro inesperado.' });
            }
        });

        document.getElementById('contextAutomacaoAtiva')?.addEventListener('change', (e) => {
            const checked = !!e.target?.checked;
            const human = document.getElementById('contextAguardandoHumano');
            if (checked && human) {
                human.checked = false;
            }
        });

        document.getElementById('contextAguardandoHumano')?.addEventListener('change', (e) => {
            const checked = !!e.target?.checked;
            const bot = document.getElementById('contextAutomacaoAtiva');
            if (checked && bot) {
                bot.checked = false;
            }
        });
    };

    const openConversa = async (id, alertOnError = true) => {
        currentConversaId = id;
        conversaIdInput.value = String(id);
        threadMessages.innerHTML = '<div class="text-muted small">Carregando conversa...</div>';

        try {
            const data = await getJson(`<?= base_url('atendimento-whatsapp/conversa') ?>/${id}`);
            const conversa = data.conversa || {};
            activeConversationUnread = Number(data.unread_before || conversa.nao_lidas || 0);
            threadTitle.textContent = conversa.nome_contato || conversa.cliente_nome || conversa.telefone || 'Conversa';
            threadSubtitle.textContent = conversa.telefone || '';
            threadStatusBadge.textContent = conversa.status || 'aberta';
            renderMensagens(data.mensagens || []);
            renderContexto(data.contexto || {});
            loadConversas();
        } catch (error) {
            threadTitle.textContent = 'Falha ao abrir conversa';
            threadSubtitle.textContent = '';
            threadMessages.innerHTML = '<div class="text-danger small">Nao foi possivel carregar esta conversa.</div>';
            if (alertOnError) {
                await swal({ icon: 'error', title: 'Erro', text: error.message || 'Falha ao abrir conversa.' });
            }
        }
    };

    formEnviar?.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (!currentConversaId) {
            await swal({ icon: 'warning', title: 'Selecione uma conversa', text: 'Escolha uma conversa antes de enviar.' });
            return;
        }

        const payload = {
            conversa_id: currentConversaId,
            mensagem: (msgInput.value || '').trim(),
            tipo_mensagem: tipoMensagemInput.value || 'manual',
            documento_id: documentoSelect.value || '',
        };

        if (!payload.mensagem && !payload.documento_id) {
            await swal({ icon: 'warning', title: 'Conteudo vazio', text: 'Digite uma mensagem ou selecione um PDF.' });
            return;
        }

        try {
            await postForm('<?= base_url('atendimento-whatsapp/enviar') ?>', payload);
            msgInput.value = '';
            documentoSelect.value = '';
            await openConversa(currentConversaId, false);
            await loadConversas();
        } catch (error) {
            await swal({ icon: 'error', title: 'Falha no envio', text: error.message || 'Nao foi possivel enviar.' });
        }
    });

    msgInput?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            formEnviar?.requestSubmit();
        }
    });

    threadMessages?.addEventListener('click', (e) => {
        const btn = e.target.closest('.cm-reply-btn');
        if (!btn) {
            return;
        }
        const raw = btn.getAttribute('data-reply') || '';
        let texto = '';
        try {
            texto = decodeURIComponent(raw);
        } catch (_) {
            texto = raw;
        }
        const trecho = texto.length > 160 ? (texto.substring(0, 160) + '...') : texto;
        const quote = `Respondendo cliente: "${trecho}"\n`;
        msgInput.value = msgInput.value ? (msgInput.value + '\n' + quote) : quote;
        msgInput.focus();
    });

    btnSyncInbound?.addEventListener('click', async () => {
        try {
            const data = await postForm('<?= base_url('atendimento-whatsapp/sync-inbound') ?>', {});
            await swal({ icon: 'success', title: 'Sincronizado', text: `Mensagens processadas: ${data.count || 0}` });
            await loadConversas();
            if (currentConversaId) {
                await openConversa(currentConversaId, false);
            }
        } catch (error) {
            await swal({ icon: 'error', title: 'Falha na sincronizacao', text: error.message || 'Erro inesperado.' });
        }
    });

    btnFiltrar?.addEventListener('click', loadConversas);
    filtroQ?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            loadConversas();
        }
    });
    [filtroStatus, filtroResponsavel, filtroTag, filtroNaoLidas, filtroOsAberta].forEach((el) => {
        el?.addEventListener('change', loadConversas);
    });

    btnNovaConversa?.addEventListener('click', async () => {
        if (!window.Swal) {
            return;
        }

        const { value: formValues } = await window.Swal.fire({
            title: 'Nova conversa',
            html: `
                <input id="swTelefone" class="swal2-input" placeholder="Telefone (55...)">
                <textarea id="swMensagem" class="swal2-textarea" placeholder="Mensagem inicial"></textarea>
            `,
            focusConfirm: false,
            showCancelButton: true,
            confirmButtonText: 'Iniciar',
            cancelButtonText: 'Cancelar',
            preConfirm: () => {
                const telefone = document.getElementById('swTelefone').value.trim();
                const mensagem = document.getElementById('swMensagem').value.trim();
                if (!telefone || !mensagem) {
                    window.Swal.showValidationMessage('Informe telefone e mensagem inicial.');
                    return false;
                }
                return { telefone, mensagem };
            }
        });

        if (!formValues) {
            return;
        }

        try {
            const data = await postForm('<?= base_url('atendimento-whatsapp/enviar') ?>', {
                telefone: formValues.telefone,
                mensagem: formValues.mensagem,
                tipo_mensagem: 'manual',
            });
            await loadConversas();
            if (data.conversa_id) {
                await openConversa(Number(data.conversa_id), false);
            }
        } catch (error) {
            await swal({ icon: 'error', title: 'Falha', text: error.message || 'Nao foi possivel iniciar conversa.' });
        }
    });

    document.querySelectorAll('.btn-resposta-rapida').forEach((btn) => {
        btn.addEventListener('click', () => {
            const msg = btn.getAttribute('data-msg') || '';
            if (!msg) {
                return;
            }
            msgInput.value = msgInput.value ? (msgInput.value + '\n' + msg) : msg;
            msgInput.focus();
        });
    });

    const startPolling = () => {
        if (pollTimer) {
            clearInterval(pollTimer);
        }
        pollTimer = setInterval(async () => {
            await loadConversas();
            if (currentConversaId) {
                await openConversa(currentConversaId, false);
            }
        }, autoSyncIntervalMs);
    };

    const bootstrapCentral = async () => {
        const items = await loadConversas();
        if (initialConversaId > 0) {
            await openConversa(initialConversaId, false);
            return;
        }
        if (initialQ && items.length > 0) {
            await openConversa(Number(items[0].id), false);
            return;
        }
        if (!currentConversaId && items.length > 0) {
            await openConversa(Number(items[0].id), false);
        }
    };

    bootstrapCentral();
    startPolling();
})();
</script>
<?php endif; ?>
<?php $cmJsVersion = @filemtime(FCPATH . 'assets/js/central-mensagens.js') ?: '20260318'; ?>
<script src="<?= base_url('assets/js/central-mensagens.js') ?>?v=<?= $cmJsVersion ?>"></script>
<?= $this->endSection() ?>

