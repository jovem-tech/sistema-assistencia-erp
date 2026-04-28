<style>
    body {
        font-family: DejaVu Sans, sans-serif;
        color: #1f2937;
        font-size: 12px;
    }
    .pdf-page-content {
        position: relative;
        z-index: 1;
    }
    .pdf-watermark {
        position: fixed;
        top: 20%;
        left: 12%;
        width: 76%;
        text-align: center;
        opacity: 0.05;
        z-index: 0;
    }
    .pdf-watermark img {
        max-width: 100%;
        max-height: 480px;
    }
    .pdf-branding {
        position: relative;
        z-index: 1;
        margin-bottom: 16px;
    }
    .pdf-branding-main {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 10px;
    }
    .pdf-branding-logo-cell {
        width: 120px;
        vertical-align: middle;
        text-align: right;
    }
    .pdf-branding-copy-cell {
        vertical-align: middle;
    }
    .pdf-branding-logo {
        max-width: 88px;
        max-height: 88px;
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
    .doc-header {
        border-bottom: 2px solid #635bff;
        padding-bottom: 10px;
        margin-bottom: 16px;
    }
    .doc-title {
        font-size: 18px;
        font-weight: 700;
        margin: 0;
        color: #111827;
    }
    .doc-subtitle {
        font-size: 11px;
        color: #6b7280;
        margin-top: 4px;
    }
    .grid {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 12px;
    }
    .grid td {
        padding: 6px 8px;
        border: 1px solid #e5e7eb;
        vertical-align: top;
    }
    .grid .label {
        width: 26%;
        background: #f9fafb;
        font-weight: 600;
    }
    .section-title {
        margin: 14px 0 8px;
        font-size: 13px;
        font-weight: 700;
        color: #111827;
    }
    .table {
        width: 100%;
        border-collapse: collapse;
    }
    .table th,
    .table td {
        border: 1px solid #e5e7eb;
        padding: 7px;
    }
    .table th {
        background: #f3f4f6;
        font-weight: 700;
    }
    .highlight-box {
        background: #f8fafc;
        border: 1px solid #dbeafe;
        border-radius: 8px;
        padding: 10px;
        margin-bottom: 10px;
    }
    .doc-list {
        margin: 0 0 12px 18px;
        padding: 0;
    }
    .doc-list li {
        margin-bottom: 4px;
    }
    .muted {
        color: #6b7280;
    }
    .right {
        text-align: right;
    }
    .footer-note,
    .footer {
        margin-top: 22px;
        font-size: 10px;
        color: #6b7280;
        border-top: 1px solid #e5e7eb;
        padding-top: 8px;
    }
    .badge {
        display: inline-block;
        padding: 2px 7px;
        border-radius: 10px;
        font-size: 10px;
        background: #eef2ff;
        color: #4338ca;
    }
    .cta-button {
        display: inline-block;
        margin-top: 10px;
        padding: 9px 14px;
        border-radius: 999px;
        background: #16a34a;
        color: #ffffff;
        font-weight: 700;
        text-decoration: none;
    }
</style>
