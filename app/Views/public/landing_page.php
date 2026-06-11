<?php
$pageTitle = $systemName . ' - ERP para assistência técnica';
$pageDescription = 'ERP operacional para assistência técnica com OS, fotos, checklist, orçamentos com link público, WhatsApp, CRM, financeiro gerencial e coletor de bancada.';
$contactLabel = $companyPhone !== '' ? $companyPhone : ($companyEmail !== '' ? $companyEmail : 'Fale com a equipe');
$footerContact = array_values(array_filter([$companyPhone, $companyEmail, $companyAddress]));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($pageTitle) ?></title>
    <meta name="description" content="<?= esc($pageDescription) ?>">
    <?php if (!empty($faviconUrl)): ?>
        <link rel="icon" type="image/png" href="<?= esc($faviconUrl) ?>">
    <?php endif; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@500;700;800&family=Manrope:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #08131f;
            --bg-soft: #0d1e2e;
            --panel: rgba(11, 28, 43, 0.82);
            --panel-strong: rgba(13, 34, 52, 0.96);
            --line: rgba(91, 214, 204, 0.18);
            --line-strong: rgba(91, 214, 204, 0.34);
            --text: #edf6fb;
            --muted: #a9c0cf;
            --faint: #7f98a8;
            --accent: #23c7b7;
            --accent-strong: #1095cf;
            --accent-warm: #f4b35d;
            --success: #38d39f;
            --danger: #ff7a6b;
            --shadow: 0 28px 80px rgba(1, 9, 18, 0.44);
            --radius: 24px;
            --radius-sm: 16px;
            --maxw: 1180px;
            --display: "Bricolage Grotesque", sans-serif;
            --body: "Manrope", sans-serif;
            --mono: "JetBrains Mono", monospace;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: var(--body);
            color: var(--text);
            background:
                radial-gradient(circle at top left, rgba(35, 199, 183, 0.18), transparent 32%),
                radial-gradient(circle at top right, rgba(16, 149, 207, 0.18), transparent 30%),
                linear-gradient(180deg, #06101a 0%, #08131f 34%, #091827 100%);
            line-height: 1.6;
            overflow-x: hidden;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        img {
            max-width: 100%;
            display: block;
        }

        .wrap {
            width: min(var(--maxw), calc(100% - 32px));
            margin: 0 auto;
        }

        .surface {
            background: var(--panel);
            border: 1px solid var(--line);
            box-shadow: var(--shadow);
            backdrop-filter: blur(14px);
        }

        .nav {
            position: sticky;
            top: 0;
            z-index: 30;
            background: rgba(6, 16, 26, 0.84);
            border-bottom: 1px solid rgba(91, 214, 204, 0.08);
            backdrop-filter: blur(14px);
        }

        .nav-inner {
            min-height: 74px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 14px;
            min-width: 0;
        }

        .brand-mark {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            display: grid;
            place-items: center;
            background: linear-gradient(135deg, var(--accent), var(--accent-strong));
            color: #032638;
            font-family: var(--display);
            font-size: 1.15rem;
            font-weight: 800;
            box-shadow: 0 16px 34px rgba(16, 149, 207, 0.28);
            overflow: hidden;
            flex-shrink: 0;
        }

        .brand-copy {
            min-width: 0;
        }

        .brand-copy strong {
            display: block;
            font-family: var(--display);
            font-size: 1rem;
            line-height: 1.05;
            letter-spacing: -0.02em;
        }

        .brand-copy span {
            display: block;
            color: var(--muted);
            font-size: 0.85rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .nav-links {
            display: flex;
            gap: 22px;
            color: var(--muted);
            font-size: 0.94rem;
        }

        .nav-links a:hover,
        .nav-links a:focus {
            color: var(--text);
        }

        .btn-row {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            min-height: 48px;
            padding: 0 20px;
            border-radius: 14px;
            font-weight: 700;
            font-size: 0.95rem;
            border: 1px solid transparent;
            transition: transform 0.2s ease, border-color 0.2s ease, background 0.2s ease, box-shadow 0.2s ease;
            text-align: center;
        }

        .btn:hover,
        .btn:focus {
            transform: translateY(-1px);
        }

        .btn-primary {
            color: #072237;
            background: linear-gradient(135deg, var(--accent), #7be7d9);
            box-shadow: 0 16px 34px rgba(35, 199, 183, 0.2);
        }

        .btn-secondary {
            color: var(--text);
            border-color: var(--line);
            background: rgba(255, 255, 255, 0.03);
        }

        .btn-secondary:hover,
        .btn-secondary:focus {
            border-color: var(--line-strong);
            background: rgba(255, 255, 255, 0.06);
        }

        .hero {
            padding: 76px 0 54px;
        }

        .hero-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.1fr) minmax(320px, 0.9fr);
            gap: 28px;
            align-items: stretch;
        }

        .hero-copy {
            padding: 34px 34px 38px;
            border-radius: 30px;
            position: relative;
            overflow: hidden;
        }

        .hero-copy::before {
            content: "";
            position: absolute;
            inset: -15% auto auto -10%;
            width: 220px;
            height: 220px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(35, 199, 183, 0.22), transparent 68%);
            pointer-events: none;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            border-radius: 999px;
            border: 1px solid var(--line);
            background: rgba(255, 255, 255, 0.03);
            color: var(--muted);
            font-size: 0.82rem;
            font-weight: 700;
        }

        .badge-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: var(--success);
            box-shadow: 0 0 0 0 rgba(56, 211, 159, 0.36);
            animation: pulse 2.4s infinite;
            flex-shrink: 0;
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(56, 211, 159, 0.34); }
            70% { box-shadow: 0 0 0 10px rgba(56, 211, 159, 0); }
            100% { box-shadow: 0 0 0 0 rgba(56, 211, 159, 0); }
        }

        h1,
        h2,
        h3 {
            font-family: var(--display);
            letter-spacing: -0.03em;
            line-height: 1.04;
        }

        h1 {
            margin-top: 24px;
            font-size: clamp(2.35rem, 6vw, 4.8rem);
            max-width: 10.8ch;
        }

        .hero-copy p {
            margin-top: 22px;
            color: var(--muted);
            font-size: clamp(1rem, 2vw, 1.13rem);
            max-width: 60ch;
        }

        .hero-copy .btn-row {
            margin-top: 28px;
        }

        .hero-proof {
            margin-top: 22px;
            color: var(--faint);
            font-family: var(--mono);
            font-size: 0.86rem;
        }

        .hero-stack {
            display: grid;
            gap: 18px;
        }

        .feature-panel,
        .contact-panel,
        .quote-panel {
            border-radius: 26px;
            padding: 28px;
        }

        .feature-panel h2 {
            font-size: clamp(1.5rem, 3vw, 2.2rem);
            margin-bottom: 10px;
        }

        .feature-panel p,
        .contact-panel p,
        .quote-panel p {
            color: var(--muted);
        }

        .signal-list {
            margin-top: 20px;
            display: grid;
            gap: 14px;
        }

        .signal {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 14px;
            align-items: start;
            padding: 14px 0;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
        }

        .signal:first-child {
            border-top: none;
            padding-top: 0;
        }

        .signal-mark {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            background: rgba(35, 199, 183, 0.12);
            border: 1px solid rgba(35, 199, 183, 0.24);
            color: var(--accent);
            font-family: var(--mono);
            font-size: 0.78rem;
            font-weight: 700;
        }

        .signal strong {
            display: block;
            font-size: 1rem;
            margin-bottom: 3px;
        }

        .signal span {
            color: var(--muted);
            font-size: 0.94rem;
        }

        .contact-panel {
            display: grid;
            gap: 14px;
        }

        .contact-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .contact-card {
            padding: 16px;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.06);
        }

        .contact-card small {
            display: block;
            font-family: var(--mono);
            color: var(--faint);
            font-size: 0.74rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 6px;
        }

        .contact-card strong,
        .contact-card span {
            display: block;
            overflow-wrap: anywhere;
        }

        .contact-card strong {
            font-size: 0.96rem;
        }

        .contact-card span {
            color: var(--muted);
            font-size: 0.9rem;
        }

        .quote-panel {
            border: 1px solid rgba(244, 179, 93, 0.18);
            background:
                linear-gradient(180deg, rgba(244, 179, 93, 0.08), transparent),
                var(--panel);
        }

        .quote-panel p {
            font-size: 1rem;
        }

        .eyebrow {
            display: inline-block;
            color: var(--accent);
            font-family: var(--mono);
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        section {
            padding: 82px 0;
        }

        .section-head {
            display: grid;
            gap: 14px;
            margin-bottom: 34px;
        }

        .section-head h2 {
            font-size: clamp(1.9rem, 4vw, 3.1rem);
            max-width: 16ch;
        }

        .section-head p {
            max-width: 66ch;
            color: var(--muted);
            font-size: 1.04rem;
        }

        .pain-band {
            border-radius: 28px;
            padding: 28px;
            display: grid;
            grid-template-columns: minmax(0, 1.1fr) minmax(240px, 0.9fr);
            gap: 20px;
            align-items: center;
            background:
                linear-gradient(180deg, rgba(255, 122, 107, 0.08), transparent),
                var(--panel);
            border: 1px solid rgba(255, 122, 107, 0.18);
        }

        .pain-band strong {
            display: block;
            font-size: 1.24rem;
            margin-bottom: 8px;
        }

        .pain-list {
            display: grid;
            gap: 10px;
            color: var(--muted);
        }

        .pain-list span::before {
            content: ">";
            color: var(--danger);
            margin-right: 8px;
            font-family: var(--mono);
            font-weight: 700;
        }

        .proof-grid,
        .feature-grid,
        .diff-grid,
        .faq-grid {
            display: grid;
            gap: 18px;
        }

        .proof-grid {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .proof-card,
        .feature-card,
        .diff-card,
        .faq-card,
        .cta-band {
            border-radius: 24px;
        }

        .proof-card {
            padding: 24px 22px;
        }

        .proof-card strong {
            display: block;
            font-size: 1.02rem;
            margin-bottom: 10px;
        }

        .proof-card p {
            color: var(--muted);
            font-size: 0.95rem;
        }

        .feature-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .feature-card {
            padding: 28px 24px;
        }

        .feature-no {
            display: inline-flex;
            min-width: 44px;
            min-height: 30px;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            background: rgba(35, 199, 183, 0.12);
            color: var(--accent);
            font-family: var(--mono);
            font-size: 0.8rem;
            font-weight: 700;
            margin-bottom: 16px;
        }

        .feature-card h3,
        .diff-card h3,
        .faq-card h3 {
            font-size: 1.28rem;
            margin-bottom: 10px;
        }

        .feature-card p,
        .diff-card p,
        .faq-card p {
            color: var(--muted);
            font-size: 0.96rem;
        }

        .flow-strip {
            margin-top: 30px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .flow-strip span {
            padding: 10px 14px;
            border-radius: 12px;
            background: rgba(35, 199, 183, 0.1);
            border: 1px solid rgba(35, 199, 183, 0.14);
            color: var(--muted);
            font-family: var(--mono);
            font-size: 0.82rem;
        }

        .diff-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .diff-card {
            padding: 28px 24px;
            background:
                linear-gradient(180deg, rgba(35, 199, 183, 0.06), transparent),
                var(--panel);
            border: 1px solid rgba(35, 199, 183, 0.14);
        }

        .steps {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
        }

        .step {
            padding: 24px;
            border-radius: 24px;
            position: relative;
        }

        .step-no {
            display: inline-block;
            font-family: var(--display);
            font-size: 2.6rem;
            line-height: 1;
            color: var(--accent-warm);
            margin-bottom: 14px;
        }

        .step h3 {
            font-size: 1.2rem;
            margin-bottom: 8px;
        }

        .step p {
            color: var(--muted);
            font-size: 0.95rem;
        }

        .faq-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .faq-card {
            padding: 24px;
        }

        .cta-band {
            padding: 34px;
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 20px;
            align-items: center;
            background:
                linear-gradient(135deg, rgba(35, 199, 183, 0.18), rgba(16, 149, 207, 0.12)),
                var(--panel-strong);
            border: 1px solid rgba(35, 199, 183, 0.22);
        }

        .cta-band h2 {
            font-size: clamp(1.8rem, 4vw, 2.9rem);
            margin-bottom: 12px;
            max-width: 14ch;
        }

        .cta-band p {
            color: var(--muted);
            max-width: 64ch;
        }

        footer {
            padding: 38px 0 48px;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
        }

        .footer-inner {
            display: grid;
            gap: 14px;
            color: var(--faint);
            font-size: 0.9rem;
        }

        .footer-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
        }

        .footer-meta span {
            overflow-wrap: anywhere;
        }

        .reveal {
            opacity: 0;
            transform: translateY(18px);
            transition: opacity 0.55s ease, transform 0.55s ease;
        }

        .reveal.is-visible {
            opacity: 1;
            transform: none;
        }

        @media (max-width: 1100px) {
            .proof-grid,
            .feature-grid,
            .diff-grid,
            .steps {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .hero-grid,
            .pain-band,
            .cta-band {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 820px) {
            .nav-links {
                display: none;
            }

            .hero {
                padding-top: 46px;
            }

            .hero-copy,
            .feature-panel,
            .contact-panel,
            .quote-panel,
            .proof-card,
            .feature-card,
            .diff-card,
            .step,
            .faq-card,
            .cta-band {
                padding: 24px;
            }

            .faq-grid,
            .contact-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 680px) {
            .wrap {
                width: min(var(--maxw), calc(100% - 24px));
            }

            .nav-inner {
                display: grid;
                grid-template-columns: 1fr;
                align-items: stretch;
            }

            .brand,
            .nav .btn-row {
                width: 100%;
            }

            .proof-grid,
            .feature-grid,
            .diff-grid,
            .steps,
            .faq-grid {
                grid-template-columns: 1fr;
            }

            h1 {
                max-width: 12ch;
            }

            .btn {
                width: 100%;
            }

            .btn-row {
                display: grid;
                grid-template-columns: 1fr;
            }

            .nav-inner {
                padding: 14px 0;
            }
        }

        @media (max-width: 430px) {
            .hero-copy,
            .feature-panel,
            .contact-panel,
            .quote-panel,
            .proof-card,
            .feature-card,
            .diff-card,
            .step,
            .faq-card,
            .cta-band,
            .pain-band {
                padding: 20px;
                border-radius: 20px;
            }

            section {
                padding: 68px 0;
            }
        }

        @media (max-width: 390px) {
            body {
                font-size: 15px;
            }

            .brand-copy span {
                white-space: normal;
            }
        }

        @media (max-width: 360px) {
            .wrap {
                width: min(var(--maxw), calc(100% - 20px));
            }

            .badge,
            .flow-strip span {
                font-size: 0.76rem;
            }
        }

        @media (max-width: 320px) {
            .hero-copy,
            .feature-panel,
            .contact-panel,
            .quote-panel,
            .proof-card,
            .feature-card,
            .diff-card,
            .step,
            .faq-card,
            .cta-band,
            .pain-band {
                padding: 18px;
            }

            h1 {
                font-size: 2rem;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            html {
                scroll-behavior: auto;
            }

            .badge-dot {
                animation: none;
            }

            .reveal {
                opacity: 1;
                transform: none;
                transition: none;
            }
        }
    </style>
</head>
<body>
<header class="nav">
    <div class="wrap nav-inner">
        <a class="brand" href="<?= esc(base_url('site')) ?>">
            <span class="brand-mark">
                <?php if (!empty($logoUrl)): ?>
                    <img src="<?= esc($logoUrl) ?>" alt="<?= esc($companyName) ?>" style="width:100%;height:100%;object-fit:cover;">
                <?php else: ?>
                    JT
                <?php endif; ?>
            </span>
            <span class="brand-copy">
                <strong><?= esc($companyName) ?></strong>
                <span><?= esc($systemName) ?> · versão <?= esc($systemVersion) ?></span>
            </span>
        </a>
        <nav class="nav-links" aria-label="Navegacao principal">
            <a href="#recursos">Recursos</a>
            <a href="#fluxo">Fluxo</a>
            <a href="#diferenciais">Diferenciais</a>
            <a href="#faq">FAQ</a>
        </nav>
        <div class="btn-row">
            <a href="<?= esc($loginUrl) ?>" class="btn btn-secondary">Entrar no ERP</a>
        </div>
    </div>
</header>

<main>
    <section class="hero">
        <div class="wrap hero-grid">
            <article class="hero-copy surface reveal">
                <span class="badge"><span class="badge-dot"></span>ERP operacional para assistência técnica com OS, orçamentos, WhatsApp, CRM, financeiro e PWA</span>
                <h1>Do atendimento ao fechamento da OS, sem perder contexto no caminho</h1>
                <p>
                    O ERP da <?= esc($companyName) ?> organiza a rotina real da assistência técnica:
                    abertura de OS com fotos e checklist, orçamentos com link público,
                    envio por WhatsApp, e-mail e PDF, histórico do equipamento,
                    financeiro gerencial e suporte a desktop/notebook com Coletor de Bancada.
                </p>
                <div class="btn-row">
                    <a href="<?= esc($primaryCtaHref) ?>" class="btn btn-primary"><?= esc($primaryCtaLabel) ?></a>
                    <a href="#recursos" class="btn btn-secondary">Ver módulos principais</a>
                    <a href="<?= esc($loginUrl) ?>" class="btn btn-secondary">Entrar no ERP</a>
                </div>
                <div class="hero-proof">
                    Produto real hoje: OS + link público de orçamento + WhatsApp OS + DRE/Fluxo de Caixa + app mobile/PWA.
                </div>
            </article>

            <div class="hero-stack">
                <aside class="feature-panel surface reveal">
                    <span class="eyebrow">Já disponível</span>
                    <h2>O que a landing passa a refletir do produto atual</h2>
                    <p>
                        Em vez de prometer um ERP genérico, a página destaca o que o sistema entrega hoje para o balcão,
                        a bancada, o atendimento e a gestão.
                    </p>
                    <div class="signal-list">
                        <div class="signal">
                            <span class="signal-mark">OS</span>
                            <div>
                                <strong>Fluxo de OS com fotos, checklist e status</strong>
                                <span>Recepção, diagnóstico, orçamento, execução, entrega e histórico reativo.</span>
                            </div>
                        </div>
                        <div class="signal">
                            <span class="signal-mark">PUB</span>
                            <div>
                                <strong>Orçamento com link público, PDF, e-mail e WhatsApp</strong>
                                <span>Aprovação comercial sem tirar a equipe do contexto operacional.</span>
                            </div>
                        </div>
                        <div class="signal">
                            <span class="signal-mark">BEN</span>
                            <div>
                                <strong>Coletor de Bancada para desktop e notebook</strong>
                                <span>Inventário técnico, snapshot local e enriquecimento automático do equipamento.</span>
                            </div>
                        </div>
                    </div>
                </aside>

                <aside class="contact-panel surface reveal" id="demo">
                    <span class="eyebrow">Contato comercial</span>
                    <p>
                        A página usa os dados institucionais configurados no ERP, sem rodapé fictício e sem CTA solto.
                    </p>
                    <div class="contact-grid">
                        <div class="contact-card">
                            <small>Contato principal</small>
                            <strong><?= esc($contactLabel) ?></strong>
                            <span><?= $whatsAppHref ? 'CTA pronto para WhatsApp.' : 'CTA pode ser refinado quando o número comercial estiver definido.' ?></span>
                        </div>
                        <div class="contact-card">
                            <small>Acesso atual</small>
                            <strong><a href="<?= esc($loginUrl) ?>">Portal de login do ERP</a></strong>
                            <span>Entrada operacional atual do sistema em produção ou homologação.</span>
                        </div>
                        <div class="contact-card">
                            <small>App mobile/PWA</small>
                            <strong><a href="<?= esc($mobileUrl) ?>">Acesso mobile</a></strong>
                            <span>Mesmo domínio operacional com foco em atendimento e rotina externa.</span>
                        </div>
                        <div class="contact-card">
                            <small>Release</small>
                            <strong>Versão <?= esc($systemVersion) ?></strong>
                            <span>Copy alinhada aos módulos e entregas realmente documentados.</span>
                        </div>
                    </div>
                </aside>

                <aside class="quote-panel reveal">
                    <span class="eyebrow">Posicionamento</span>
                    <p>
                        Mais forte para assistências de informática, notebook, desktop e eletrônicos que precisam ligar atendimento,
                        bancada, aprovação comercial e visão financeira no mesmo fluxo.
                    </p>
                </aside>
            </div>
        </div>
    </section>

    <section>
        <div class="wrap">
            <div class="pain-band reveal">
                <div>
                    <span class="eyebrow">O problema real</span>
                    <strong>A operação da assistência se perde quando cada etapa mora em um lugar diferente</strong>
                    <p style="color: var(--muted);">
                        Papel no balcão, conversa no WhatsApp, foto fora da OS, orçamento isolado, financeiro sem contexto e equipe sem visão única do equipamento.
                    </p>
                </div>
                <div class="pain-list">
                    <span>OS sem histórico técnico consistente</span>
                    <span>Aprovação comercial lenta ou sem rastreio</span>
                    <span>Financeiro sem leitura clara por atendimento</span>
                    <span>Desktop e notebook sem inventário padronizado</span>
                </div>
            </div>
        </div>
    </section>

    <section id="recursos">
        <div class="wrap">
            <div class="section-head reveal">
                <span class="eyebrow">Recursos reais</span>
                <h2>A página comercial agora fala a língua do produto que já existe</h2>
                <p>
                    Em vez de vender "qualquer ERP", esta landing destaca os blocos que o sistema já entrega hoje no código e na documentação:
                    OS, orçamentos, central de mensagens, CRM, financeiro gerencial, PWA e agente de bancada.
                </p>
            </div>

            <div class="flow-strip reveal">
                <span>Abertura de OS</span>
                <span>Fotos e checklist</span>
                <span>Status configurável</span>
                <span>Orçamento com link público</span>
                <span>WhatsApp e e-mail</span>
                <span>CRM e follow-up</span>
                <span>DRE e Fluxo de Caixa</span>
                <span>PWA e API interna</span>
                <span>Coletor de Bancada</span>
            </div>

            <div class="proof-grid" style="margin-top: 28px;">
                <article class="proof-card surface reveal">
                    <strong>OS com contexto completo</strong>
                    <p>Cliente, equipamento, defeito, fotos, checklist de entrada, status, histórico e documentos no mesmo fluxo.</p>
                </article>
                <article class="proof-card surface reveal">
                    <strong>Orçamento conectado à operação</strong>
                    <p>Link público, PDF oficial, envio por WhatsApp/e-mail e reflexo comercial dentro da OS.</p>
                </article>
                <article class="proof-card surface reveal">
                    <strong>Central de Mensagens + CRM</strong>
                    <p>Inbox operacional, contatos, follow-ups, automações e trilha de atendimento sem perder a conversa.</p>
                </article>
                <article class="proof-card surface reveal">
                    <strong>Financeiro com leitura gerencial</strong>
                    <p>DRE, Fluxo de Caixa, categorias configuráveis, baixas parciais e despesas fixas mensais.</p>
                </article>
            </div>

            <div class="feature-grid" style="margin-top: 20px;">
                <article class="feature-card surface reveal">
                    <span class="feature-no">01</span>
                    <h3>Ordens de serviço para balcão e bancada</h3>
                    <p>
                        Abertura e edição de OS com checklist, acessórios, fotos, fluxo de status configurável e atualização reativa sem depender de refresh manual.
                    </p>
                </article>
                <article class="feature-card surface reveal">
                    <span class="feature-no">02</span>
                    <h3>Orçamentos com aprovação pública</h3>
                    <p>
                        O cliente pode receber PDF, e-mail ou link público; a equipe acompanha a resposta e o reflexo operacional no ERP.
                    </p>
                </article>
                <article class="feature-card surface reveal">
                    <span class="feature-no">03</span>
                    <h3>WhatsApp OS e comunicação rastreável</h3>
                    <p>
                        Templates, central de mensagens, envios da OS, contexto de conversa e flexibilidade de provider no backend.
                    </p>
                </article>
                <article class="feature-card surface reveal">
                    <span class="feature-no">04</span>
                    <h3>Equipamentos com histórico técnico real</h3>
                    <p>
                        Cadastro com fotos, deduplicação por série/MAC/IMEI e resumo técnico mais forte para desktop montado, notebook e bancada.
                    </p>
                </article>
                <article class="feature-card surface reveal">
                    <span class="feature-no">05</span>
                    <h3>Financeiro gerencial conectado a OS</h3>
                    <p>
                        Receitas e despesas com classificação DRE, fluxo de caixa, fornecedor, origem automática e leitura mais clara da operação.
                    </p>
                </article>
                <article class="feature-card surface reveal">
                    <span class="feature-no">06</span>
                    <h3>PWA, API interna e notificações</h3>
                    <p>
                        Base preparada para uso web e mobile, com stream SSE, API autenticada, notificações e rotina operacional fora do escritório.
                    </p>
                </article>
            </div>
        </div>
    </section>

    <section id="fluxo">
        <div class="wrap">
            <div class="section-head reveal">
                <span class="eyebrow">Fluxo comercial</span>
                <h2>Da entrada do equipamento até o financeiro, com os módulos falando entre si</h2>
                <p>
                    A copy agora posiciona o sistema como ERP operacional de assistência técnica, sem esconder o que ele já faz melhor:
                    integrar atendimento, diagnóstico, aprovação, execução e resultado.
                </p>
            </div>

            <div class="steps">
                <article class="step surface reveal">
                    <span class="step-no">1</span>
                    <h3>Recepcione com contexto</h3>
                    <p>Abra a OS, registre defeito, acessórios, fotos, checklist de entrada e dados do cliente sem espalhar informação fora do ERP.</p>
                </article>
                <article class="step surface reveal">
                    <span class="step-no">2</span>
                    <h3>Diagnostique e orce</h3>
                    <p>Monte o orçamento, vincule peças e serviços, gere PDF e prepare o envio público com rastreabilidade comercial.</p>
                </article>
                <article class="step surface reveal">
                    <span class="step-no">3</span>
                    <h3>Comunique e aprove</h3>
                    <p>Use WhatsApp, e-mail e link público para acelerar a decisão do cliente sem quebrar o fluxo operacional interno.</p>
                </article>
                <article class="step surface reveal">
                    <span class="step-no">4</span>
                    <h3>Execute, entregue e leia o resultado</h3>
                    <p>Finalize a OS, acompanhe histórico, documentos, financeiro e a leitura gerencial do atendimento na mesma plataforma.</p>
                </article>
            </div>
        </div>
    </section>

    <section id="diferenciais">
        <div class="wrap">
            <div class="section-head reveal">
                <span class="eyebrow">Diferenciais</span>
                <h2>Onde o ERP sai do genérico e ganha argumento de venda de verdade</h2>
                <p>
                    A adaptação da landing prioriza os diferenciais documentados no sistema atual, especialmente para operações de bancada e atendimento com histórico técnico.
                </p>
            </div>

            <div class="diff-grid">
                <article class="diff-card reveal">
                    <h3>Coletor de Bancada para desktop e notebook</h3>
                    <p>
                        O sistema já trabalha com coleta técnica local, snapshot em `C:\JovemTechBenchCollector`, enriquecimento da OS digital e preenchimento técnico do equipamento.
                    </p>
                </article>
                <article class="diff-card reveal">
                    <h3>Fluxo OS + Orçamento + Documentos</h3>
                    <p>
                        Não é só "abrir uma ordem": a plataforma conecta status, PDF, envio, aprovação pública e contexto comercial dentro da rotina da equipe.
                    </p>
                </article>
                <article class="diff-card reveal">
                    <h3>CRM, RBAC e PWA no mesmo ecossistema</h3>
                    <p>
                        O sistema já opera com módulos independentes para CRM, atendimento WhatsApp e precificação, além de app mobile/PWA e API interna autenticada.
                    </p>
                </article>
            </div>
        </div>
    </section>

    <section id="faq">
        <div class="wrap">
            <div class="section-head reveal">
                <span class="eyebrow">FAQ</span>
                <h2>As dúvidas que a copy agora responde de forma mais honesta</h2>
                <p>
                    A ideia foi tirar exagero e alinhar expectativa comercial ao estado real do produto.
                </p>
            </div>

            <div class="faq-grid">
                <article class="faq-card surface reveal">
                    <h3>Serve só para celular?</h3>
                    <p>
                        Não. O sistema cobre assistência técnica em geral, mas ganha mais força quando a operação mistura celular, notebook, desktop e histórico técnico detalhado.
                    </p>
                </article>
                <article class="faq-card surface reveal">
                    <h3>Tem aprovação pública de orçamento?</h3>
                    <p>
                        Sim. A plataforma já possui link público, envio por WhatsApp/e-mail e reflexo da resposta do cliente no ERP.
                    </p>
                </article>
                <article class="faq-card surface reveal">
                    <h3>O financeiro está só no básico?</h3>
                    <p>
                        Não. O produto atual já trabalha com DRE, fluxo de caixa, grupos/subgrupos, baixas parciais, despesas fixas e origem operacional.
                    </p>
                </article>
                <article class="faq-card surface reveal">
                    <h3>O agente de bancada é obrigatório?</h3>
                    <p>
                        Não. Ele entra como diferencial para desktop e notebook; a operação principal do ERP continua funcionando sem depender desse coletor.
                    </p>
                </article>
            </div>
        </div>
    </section>

    <section>
        <div class="wrap">
            <div class="cta-band reveal">
                <div>
                    <span class="eyebrow">Pronto para vender melhor</span>
                    <h2>Uma landing que acompanha o produto real e ajuda a apresentar o ERP com mais segurança</h2>
                    <p>
                        O foco aqui foi adaptar o HTML para a situação atual do sistema, destacando o que já está de pé no ERP e evitando promessas que ainda não viraram fluxo consolidado.
                    </p>
                </div>
                <div class="btn-row">
                    <a href="<?= esc($primaryCtaHref) ?>" class="btn btn-primary"><?= esc($primaryCtaLabel) ?></a>
                    <a href="<?= esc($loginUrl) ?>" class="btn btn-secondary">Entrar no ERP</a>
                </div>
            </div>
        </div>
    </section>
</main>

<footer>
    <div class="wrap footer-inner">
        <div class="brand">
            <span class="brand-mark">
                <?php if (!empty($logoUrl)): ?>
                    <img src="<?= esc($logoUrl) ?>" alt="<?= esc($companyName) ?>" style="width:100%;height:100%;object-fit:cover;">
                <?php else: ?>
                    JT
                <?php endif; ?>
            </span>
            <span class="brand-copy">
                <strong><?= esc($companyName) ?></strong>
                <span><?= esc($systemName) ?> · versão <?= esc($systemVersion) ?></span>
            </span>
        </div>
        <div class="footer-meta">
            <?php foreach ($footerContact as $item): ?>
                <span><?= esc($item) ?></span>
            <?php endforeach; ?>
            <span>Landing pública: <a href="<?= esc(base_url('site')) ?>"><?= esc(base_url('site')) ?></a></span>
            <span>&copy; <?= date('Y') ?> <?= esc($companyName) ?></span>
        </div>
    </div>
</footer>

<script>
    (function () {
        const items = document.querySelectorAll('.reveal');
        if (!('IntersectionObserver' in window)) {
            items.forEach((item) => item.classList.add('is-visible'));
            return;
        }

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) {
                    return;
                }

                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            });
        }, { threshold: 0.12 });

        items.forEach((item, index) => {
            item.style.transitionDelay = ((index % 6) * 45) + 'ms';
            observer.observe(item);
        });
    }());
</script>
</body>
</html>
