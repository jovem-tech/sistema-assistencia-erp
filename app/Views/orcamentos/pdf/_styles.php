<?= view('os/pdf/_styles') ?>
<style>
    .pdf-page-content { position: relative; z-index: 1; }
    .pdf-watermark {
        position: fixed;
        top: 24%;
        left: 17%;
        width: 66%;
        text-align: center;
        opacity: 0.06;
        z-index: 0;
    }
    .pdf-watermark img {
        max-width: 100%;
        max-height: 420px;
    }
    .pdf-branding {
        position: relative;
        z-index: 1;
        margin-bottom: 14px;
    }
    .pdf-branding-main {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 8px;
    }
    .pdf-branding-logo-cell {
        width: 92px;
        vertical-align: middle;
    }
    .pdf-branding-copy-cell {
        vertical-align: middle;
    }
    .pdf-branding-logo {
        max-width: 76px;
        max-height: 76px;
    }
    .pdf-branding-company {
        font-size: 16px;
        font-weight: 700;
        color: #111827;
        margin: 0;
    }
    .pdf-branding-title {
        font-size: 11px;
        color: #475569;
        margin-top: 3px;
    }
    .pdf-branding-reference {
        font-size: 10px;
        color: #64748b;
        margin-top: 3px;
    }
    .pdf-branding-meta {
        font-size: 10px;
        color: #6b7280;
        line-height: 1.5;
        border-bottom: 1px solid #e5e7eb;
        padding-bottom: 10px;
        margin-bottom: 12px;
    }
</style>
