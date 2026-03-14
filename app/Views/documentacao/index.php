<?php
// Função PHP para renderizar a árvore HTML no servidor
function renderTree(array $items): void {
    foreach ($items as $item) {
        if ($item['type'] === 'folder') {
            echo '<div class="tree-folder">';
            echo '<div class="tree-folder-header">';
            echo '<i class="bi bi-folder-fill folder-icon"></i>';
            echo '<span>' . esc($item['name']) . '</span>';
            echo '<i class="bi bi-chevron-right chevron"></i>';
            echo '</div>';
            echo '<div class="tree-folder-children">';
            renderTree($item['children']);
            echo '</div>';
            echo '</div>';
        } else {
            $icon = match($item['ext'] ?? 'md') {
                'html' => 'bi-filetype-html',
                'txt'  => 'bi-file-text',
                default => 'bi-file-earmark-text',
            };
            echo '<div class="tree-file" data-path="' . esc($item['path']) . '">';
            echo '<i class="bi ' . $icon . ' file-icon"></i>';
            echo '<span class="file-name">' . esc($item['name']) . '</span>';
            echo '</div>';
        }
    }
}
?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<style>
/* ── Wiki Layout ────────────────────────────────────── */
.wiki-layout {
    display: flex;
    height: calc(100vh - 120px);
    gap: 0;
    border-radius: 16px;
    overflow: hidden;
}

/* ── Painel Esquerdo (Árvore) ───────────────────────── */
.wiki-sidebar {
    width: 300px;
    min-width: 260px;
    background: var(--glass-bg, rgba(255,255,255,0.05));
    border-right: 1px solid var(--border-color, rgba(255,255,255,0.1));
    display: flex;
    flex-direction: column;
    overflow: hidden;
    transition: width 0.3s ease;
}
.wiki-sidebar.collapsed {
    width: 48px;
    min-width: 48px;
}
.wiki-sidebar.collapsed .wiki-search,
.wiki-sidebar.collapsed .wiki-tree-inner,
.wiki-sidebar.collapsed .wiki-sidebar-title span {
    display: none;
}

.wiki-sidebar-header {
    padding: 14px 16px 10px;
    border-bottom: 1px solid var(--border-color, rgba(255,255,255,0.1));
    flex-shrink: 0;
}
.wiki-sidebar-title {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--text-muted);
    margin-bottom: 10px;
}
.wiki-search {
    position: relative;
}
.wiki-search input {
    padding-right: 36px;
    background: var(--input-bg, rgba(0,0,0,0.2));
    border: 1px solid var(--border-color, rgba(255,255,255,0.1));
    border-radius: 8px;
    color: inherit;
    font-size: 0.85rem;
}
.wiki-search input:focus {
    box-shadow: 0 0 0 2px rgba(139,92,246,0.3);
    border-color: var(--primary);
    outline: none;
}
.wiki-search .search-kbd {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 0.65rem;
    background: rgba(255,255,255,0.1);
    padding: 1px 5px;
    border-radius: 4px;
    color: var(--text-muted);
}

.wiki-tree {
    flex: 1;
    overflow-y: auto;
    padding: 8px 0;
}
.wiki-tree::-webkit-scrollbar { width: 4px; }
.wiki-tree::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 4px; }

/* Itens da árvore */
.tree-folder {
    user-select: none;
}
.tree-folder-header {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 6px 16px;
    cursor: pointer;
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--text-muted);
    transition: all 0.15s;
    border-radius: 0;
}
.tree-folder-header:hover {
    background: rgba(255,255,255,0.07);
    color: var(--bs-body-color);
}
.tree-folder-header .folder-icon { font-size: 0.85rem; color: var(--warning, #f59e0b); }
.tree-folder-header .chevron {
    margin-left: auto;
    font-size: 0.7rem;
    transition: transform 0.2s;
}
.tree-folder.open > .tree-folder-header .chevron { transform: rotate(90deg); }
.tree-folder-children {
    display: none;
    border-left: 1px solid rgba(255,255,255,0.07);
    margin-left: 22px;
}
.tree-folder.open > .tree-folder-children { display: block; }

.tree-file {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 5px 16px;
    cursor: pointer;
    font-size: 0.8rem;
    color: var(--text-muted);
    transition: all 0.15s;
    border-radius: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.tree-file:hover {
    background: rgba(255,255,255,0.07);
    color: var(--bs-body-color);
}
.tree-file.active {
    background: rgba(139,92,246,0.2);
    color: var(--primary, #8b5cf6);
    font-weight: 600;
    border-right: 3px solid var(--primary, #8b5cf6);
}
.tree-file .file-icon { font-size: 0.75rem; opacity: 0.7; }
.tree-file .file-name {
    overflow: hidden;
    text-overflow: ellipsis;
}

/* ── Painel Direito (Conteúdo) ──────────────────────── */
.wiki-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    background: var(--glass-bg, rgba(255,255,255,0.02));
}

.wiki-content-header {
    padding: 16px 24px 12px;
    border-bottom: 1px solid var(--border-color, rgba(255,255,255,0.1));
    display: flex;
    align-items: center;
    gap: 12px;
    flex-shrink: 0;
}
.wiki-breadcrumb {
    font-size: 0.75rem;
    color: var(--text-muted);
}
.wiki-breadcrumb span { color: var(--text-muted); margin: 0 4px; }
.wiki-content-title {
    font-size: 1rem;
    font-weight: 700;
    margin: 0;
    flex: 1;
}
.wiki-meta {
    font-size: 0.7rem;
    color: var(--text-muted);
}

.wiki-content-body {
    flex: 1;
    overflow-y: auto;
    padding: 32px 40px;
}
.wiki-content-body::-webkit-scrollbar { width: 6px; }
.wiki-content-body::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 4px; }

/* Markdown Rendering */
.doc-render h1 { font-size: 1.8rem; font-weight: 800; margin-bottom: 1rem; border-bottom: 2px solid rgba(139,92,246,0.3); padding-bottom: 0.5rem; }
.doc-render h2 { font-size: 1.3rem; font-weight: 700; margin-top: 2rem; margin-bottom: 0.75rem; color: var(--primary, #8b5cf6); }
.doc-render h3 { font-size: 1.05rem; font-weight: 600; margin-top: 1.5rem; margin-bottom: 0.5rem; }
.doc-render p  { line-height: 1.8; margin-bottom: 1rem; }
.doc-render ul, .doc-render ol { margin-bottom: 1rem; padding-left: 1.5rem; line-height: 1.8; }
.doc-render li { margin-bottom: 0.25rem; }
.doc-render code {
    background: rgba(139,92,246,0.15);
    color: #c4b5fd;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 0.85em;
    font-family: 'Fira Code', 'Cascadia Code', monospace;
}
.doc-render pre {
    background: rgba(0,0,0,0.4);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 10px;
    padding: 1rem 1.25rem;
    overflow-x: auto;
    margin-bottom: 1.25rem;
}
.doc-render pre code {
    background: none;
    color: #e2e8f0;
    padding: 0;
    font-size: 0.82rem;
    line-height: 1.7;
}
.doc-render table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 1.25rem;
    font-size: 0.875rem;
}
.doc-render th {
    background: rgba(139,92,246,0.2);
    padding: 8px 12px;
    text-align: left;
    font-weight: 600;
    border-bottom: 2px solid rgba(139,92,246,0.3);
}
.doc-render td {
    padding: 7px 12px;
    border-bottom: 1px solid rgba(255,255,255,0.06);
}
.doc-render tr:hover td { background: rgba(255,255,255,0.03); }
.doc-render blockquote {
    border-left: 4px solid var(--primary, #8b5cf6);
    margin: 1rem 0;
    padding: 0.75rem 1rem;
    background: rgba(139,92,246,0.08);
    border-radius: 0 8px 8px 0;
    font-style: italic;
}
.doc-render a { color: var(--primary, #8b5cf6); text-decoration: none; }
.doc-render a:hover { text-decoration: underline; }
.doc-render hr { border-color: rgba(255,255,255,0.1); margin: 2rem 0; }
.doc-render strong { color: var(--bs-body-color); font-weight: 600; }

/* Estado inicial */
.wiki-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    padding: 3rem;
    text-align: center;
    color: var(--text-muted);
}
.wiki-empty-icon { font-size: 4rem; opacity: 0.2; margin-bottom: 1rem; }
.wiki-empty h4 { font-weight: 600; margin-bottom: 0.5rem; opacity: 0.5; }
.wiki-empty p  { font-size: 0.875rem; opacity: 0.4; }

/* Search results overlay */
.search-results-overlay {
    display: none;
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: var(--card-bg, #1e1e2e);
    border: 1px solid var(--border-color, rgba(255,255,255,0.1));
    border-radius: 10px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.5);
    z-index: 9999;
    max-height: 400px;
    overflow-y: auto;
    margin-top: 4px;
}
.search-results-overlay.show { display: block; }
.search-result-item {
    padding: 10px 14px;
    cursor: pointer;
    border-bottom: 1px solid rgba(255,255,255,0.05);
    transition: background 0.15s;
}
.search-result-item:hover { background: rgba(139,92,246,0.1); }
.search-result-item:last-child { border-bottom: none; }
.search-result-title { font-size: 0.85rem; font-weight: 600; }
.search-result-folder { font-size: 0.7rem; color: var(--primary, #8b5cf6); margin-bottom: 2px; }
.search-result-snippet { font-size: 0.75rem; color: var(--text-muted); overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
.search-no-results { padding: 20px; text-align: center; color: var(--text-muted); font-size: 0.85rem; }

/* Sidebar collapse btn */
.wiki-collapse-btn {
    background: none;
    border: none;
    color: var(--text-muted);
    cursor: pointer;
    padding: 2px 6px;
    border-radius: 6px;
    transition: all 0.15s;
    font-size: 0.9rem;
}
.wiki-collapse-btn:hover { background: rgba(255,255,255,0.1); color: var(--bs-body-color); }

/* Loading */
.wiki-loading {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    gap: 12px;
    color: var(--text-muted);
}

/* Responsive */
@media (max-width: 768px) {
    .wiki-sidebar {
        position: absolute;
        z-index: 1000;
        height: 100%;
        background: var(--sidebar-bg, #1e1e2e);
        left: -300px;
        transition: left 0.3s ease;
    }
    .wiki-sidebar.mobile-open { left: 0; }
    .wiki-layout { position: relative; }
}
</style>

<div class="page-header">
    <div>
        <h2><i class="bi bi-journal-richtext me-2"></i><?= $title ?></h2>
        <div class="text-muted small" id="docOriginLabel" style="display:none;"></div>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= base_url('dashboard') ?>" class="btn btn-outline-secondary btn-sm" data-back-default="<?= base_url('dashboard') ?>">
            <i class="bi bi-arrow-left me-1"></i><span class="d-none d-md-inline">Voltar</span>
        </a>
        <button class="btn btn-outline-secondary btn-sm" id="btnMapa" title="Mapa do Sistema">
            <i class="bi bi-diagram-3 me-1"></i><span class="d-none d-md-inline">Mapa</span>
        </button>
        <button class="btn btn-outline-secondary btn-sm d-md-none" id="btnMobileSidebar">
            <i class="bi bi-list"></i>
        </button>
    </div>
</div>

<div class="wiki-layout glass-card">

    <!-- ── PAINEL ESQUERDO: Árvore de Navegação ───────────────────────── -->
    <div class="wiki-sidebar" id="wikiSidebar">
        <div class="wiki-sidebar-header">
            <div class="wiki-sidebar-title">
                <span><i class="bi bi-folder2-open me-1"></i>Navegação</span>
                <button class="wiki-collapse-btn" id="btnCollapseSidebar" title="Recolher">
                    <i class="bi bi-layout-sidebar-reverse"></i>
                </button>
            </div>
            <div class="wiki-search position-relative">
                <input type="text" id="wikiSearchInput" class="form-control form-control-sm"
                       placeholder="🔎 Buscar na documentação..." autocomplete="off">
                <span class="search-kbd">⌘K</span>
                <div class="search-results-overlay" id="searchResultsOverlay"></div>
            </div>
        </div>
        <div class="wiki-tree" id="wikiTree">
            <?php renderTree($tree ?? []); ?>
        </div>
    </div>

    <!-- ── PAINEL DIREITO: Conteúdo ──────────────────────────────────── -->
    <div class="wiki-content">
        <div class="wiki-content-header" id="docHeader" style="display:none;">
            <div class="flex-1">
                <div class="wiki-breadcrumb" id="docBreadcrumb"></div>
                <h5 class="wiki-content-title" id="docTitle"></h5>
            </div>
            <div class="wiki-meta" id="docMeta"></div>
        </div>
        <div class="wiki-content-body" id="docBody">
            <div id="docLoading" class="wiki-loading" style="display:none;">
                <div class="spinner-border spinner-border-sm text-primary me-2"></div>Carregando...
            </div>
            
            <div class="wiki-empty" id="wikiEmpty">
                <div class="wiki-empty-icon"><i class="bi bi-journal-richtext"></i></div>
                <h4>Central de Documentação</h4>
                <p>Selecione um documento na árvore à esquerda<br>ou use a busca para encontrar o que precisa.</p>
                <div class="mt-4 d-flex flex-wrap gap-2 justify-content-center" id="quickLinks">
                    <button class="btn btn-sm btn-outline-secondary" onclick="loadDoc('README.md')">
                        <i class="bi bi-house me-1"></i>Início
                    </button>
                    <button class="btn btn-sm btn-outline-secondary" onclick="loadDoc('01-manual-do-usuario/ordens-de-servico.md')">
                        <i class="bi bi-clipboard-check me-1"></i>Manual de OS
                    </button>
                    <button class="btn btn-sm btn-outline-secondary" onclick="loadDoc('09-roadmap/funcionalidades-planejadas.md')">
                        <i class="bi bi-map me-1"></i>Roadmap
                    </button>
                    <button class="btn btn-sm btn-outline-secondary" onclick="loadDoc('11-padroes/boas-praticas.md')">
                        <i class="bi bi-patch-check me-1"></i>Boas Práticas
                    </button>
                </div>
            </div>
            
            <div id="docRender" class="doc-render" style="display:none;"></div>
            <div id="docError" class="wiki-empty" style="display:none;"></div>
        </div>
    </div>
</div>

<!-- Modal: Mapa do Sistema -->
<div class="modal fade" id="modalMapa" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content glass-card">
            <div class="modal-header border-bottom">
                <h5 class="modal-title"><i class="bi bi-diagram-3 me-2 text-primary"></i>Mapa do Sistema</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-4">
                    <div class="col-md-5">
                        <h6 class="text-muted text-uppercase mb-3" style="font-size:0.7rem; letter-spacing:0.1em;">Fluxo Operacional</h6>
                        <div class="d-flex flex-column gap-1">
                            <?php
                            $fluxo = [
                                ['icon'=>'bi-person-badge','label'=>'Cliente','color'=>'#8b5cf6','url'=>'clientes'],
                                ['icon'=>'bi-laptop','label'=>'Equipamento','color'=>'#3b82f6','url'=>'equipamentos'],
                                ['icon'=>'bi-clipboard-check-fill','label'=>'Ordem de Serviço','color'=>'#f59e0b','url'=>'os'],
                                ['icon'=>'bi-search','label'=>'Diagnóstico','color'=>'#f59e0b','url'=>null],
                                ['icon'=>'bi-gear-wide-connected','label'=>'Serviços','color'=>'#10b981','url'=>'servicos'],
                                ['icon'=>'bi-box-seam-fill','label'=>'Peças / Estoque','color'=>'#06b6d4','url'=>'estoque'],
                                ['icon'=>'bi-cash-stack','label'=>'Financeiro','color'=>'#22c55e','url'=>'financeiro'],
                                ['icon'=>'bi-check2-all','label'=>'Encerramento','color'=>'#a855f7','url'=>null],
                            ];
                            foreach ($fluxo as $i => $item): ?>
                            <div class="d-flex align-items-center gap-2">
                                <?php if ($i > 0): ?>
                                <div style="width:24px; text-align:center; color:rgba(255,255,255,0.2); font-size:1rem; margin-left:4px;">↓</div>
                                <?php else: ?>
                                <div style="width:24px;"></div>
                                <?php endif; ?>
                                <?php if ($item['url']): ?>
                                <a href="<?= base_url($item['url']) ?>" class="d-flex align-items-center gap-2 px-3 py-2 rounded-3 text-decoration-none flex-1"
                                   style="background:<?= $item['color'] ?>22; border:1px solid <?= $item['color'] ?>44; color:<?= $item['color'] ?>;">
                                    <i class="<?= $item['icon'] ?>"></i>
                                    <span style="font-size:0.85rem; font-weight:600;"><?= $item['label'] ?></span>
                                    <i class="bi bi-box-arrow-up-right ms-auto" style="font-size:0.65rem; opacity:0.6;"></i>
                                </a>
                                <?php else: ?>
                                <div class="d-flex align-items-center gap-2 px-3 py-2 rounded-3 flex-1"
                                     style="background:<?= $item['color'] ?>22; border:1px dashed <?= $item['color'] ?>44; color:<?= $item['color'] ?>; opacity:0.8;">
                                    <i class="<?= $item['icon'] ?>"></i>
                                    <span style="font-size:0.85rem;"><?= $item['label'] ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="col-md-7">
                        <h6 class="text-muted text-uppercase mb-3" style="font-size:0.7rem; letter-spacing:0.1em;">Estrutura de Módulos</h6>
                        <div class="doc-render" style="font-size:0.8rem;">
                            <pre style="background:rgba(0,0,0,0.3); border-radius:10px; padding:1rem; font-size:0.75rem; line-height:1.8;">Clientes
   └── Equipamentos
         └── Ordens de Serviço
               ├── Serviços (catálogo)
               ├── Peças (estoque)
               └── Financeiro (receitas)

Configurações
   ├── Dados da Empresa
   ├── Usuários
   └── Níveis de Acesso (RBAC)
         └── Permissões por módulo

Relatórios
   ├── OS por Período
   ├── Financeiro
   ├── Estoque
   └── Clientes</pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<!-- Marked.js — Markdown Renderer -->
<script src="https://cdn.jsdelivr.net/npm/marked@9.1.6/marked.min.js"></script>
<!-- DOMPurify — Sanitiza HTML do markdown -->
<script src="https://cdn.jsdelivr.net/npm/dompurify@3.0.6/dist/purify.min.js"></script>
<!-- Highlight.js — Syntax highlighting de código -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/highlight.js@11.9.0/styles/github-dark.min.css">
<script src="https://cdn.jsdelivr.net/npm/highlight.js@11.9.0/lib/highlight.min.js"></script>

<script>
const BASE_URL = document.querySelector('meta[name="base-url"]').content;
let currentPath  = null;
let searchTimer  = null;

// ── Configurar Marked.js ────────────────────────────────────────────────────
function setupMarked() {
    if (typeof marked === 'undefined') {
        console.error('Erro: Biblioteca marked não carregada.');
        return false;
    }
    
    // Configurações globais (nas versões novas algumas são depreciadas via setOptions)
    try {
        marked.setOptions({
            breaks: true,
            gfm: true,
        });
    } catch(e) { console.warn('Erro ao configurar marked:', e); }

    return true;
}

// Custom renderer para links internos
const renderer = new marked.Renderer();

renderer.link = function(href, title, text) {
    // Link interno: converte para navegação no wiki
    if (href && !href.startsWith('http') && !href.startsWith('#')) {
        return `<a href="#" class="doc-internal-link" data-path="${href}" title="${title || ''}">${text}</a>`;
    }
    return `<a href="${href}" target="_blank" rel="noopener" title="${title || ''}">${text}</a>`;
};

renderer.code = function(code, lang) {
    if (typeof hljs !== 'undefined') {
        if (lang && hljs.getLanguage(lang)) {
            try {
                return `<pre><code class="language-${lang}">${hljs.highlight(code, {language: lang}).value}</code></pre>`;
            } catch(e) {}
        }
        try {
            return `<pre><code>${hljs.highlightAuto(code).value}</code></pre>`;
        } catch(e) {}
    }
    // Fallback se hljs falhar ou não existir
    return `<pre><code>${escapeHtml(code)}</code></pre>`;
};

if (typeof marked !== 'undefined' && typeof renderer !== 'undefined') {
    try {
        marked.use({ renderer });
    } catch(e) { console.error('Erro ao usar renderer customizado:', e); }
}

// ── Carregar Documento ──────────────────────────────────────────────────────
window.loadDoc = function(path) {
    currentPath = path;

    // Marca item ativo na árvore
    document.querySelectorAll('.tree-file').forEach(el => el.classList.remove('active'));
    const link = document.querySelector(`.tree-file[data-path="${path}"]`);
    if (link) {
        link.classList.add('active');
        // Abre pastas pai
        let parent = link.parentElement;
        while (parent) {
            if (parent.classList.contains('tree-folder')) {
                parent.classList.add('open');
            }
            parent = parent.parentElement;
        }
        link.scrollIntoView({ block: 'nearest' });
    }

    // Mostra loading
    if (document.getElementById('wikiEmpty')) document.getElementById('wikiEmpty').style.display = 'none';
    if (document.getElementById('docRender')) document.getElementById('docRender').style.display = 'none';
    if (document.getElementById('docHeader')) document.getElementById('docHeader').style.display = 'none';
    if (document.getElementById('docError'))  document.getElementById('docError').style.display  = 'none';
    if (document.getElementById('docLoading')) document.getElementById('docLoading').style.display = 'flex';

    fetch(`${BASE_URL}documentacao/arquivo?path=${encodeURIComponent(path)}`)
        .then(r => r.json())
        .then(data => {
            if (document.getElementById('docLoading')) document.getElementById('docLoading').style.display = 'none';

            if (!data.success) {
                const docError = document.getElementById('docError');
                if (docError) {
                    docError.innerHTML = `<div class="wiki-empty-icon"><i class="bi bi-exclamation-circle"></i></div>
                                         <h4>Erro</h4><p>${data.message}</p>`;
                    docError.style.display = 'flex';
                }
                return;
            }

            // Título e breadcrumb
            const parts = data.path.split('/');
            const breadcrumb = parts.slice(0, -1).map(p => formatFolderName(p)).join(' › ');
            const docName    = data.filename.replace(/\.(md|markdown|html|txt)$/i, '');

            if (document.getElementById('docBreadcrumb')) document.getElementById('docBreadcrumb').innerHTML = `<i class="bi bi-journal-text me-1"></i>${breadcrumb || 'Documentação'}`;
            if (document.getElementById('docTitle'))      document.getElementById('docTitle').textContent = formatFolderName(docName);
            if (document.getElementById('docMeta'))       document.getElementById('docMeta').innerHTML = `<i class="bi bi-clock me-1"></i>Atualizado: ${data.modified}`;

            if (document.getElementById('docHeader')) document.getElementById('docHeader').style.display = '';

            // Renderiza conteúdo
            let html = '';
            try {
                if (data.type === 'markdown') {
                    if (typeof marked !== 'undefined') {
                        const rawHtml = marked.parse(data.content);
                        html = (typeof DOMPurify !== 'undefined') ? DOMPurify.sanitize(rawHtml) : rawHtml;
                    } else {
                        html = `<div class="alert alert-warning">Aguardando carregamento do motor Markdown...</div><pre style="white-space:pre-wrap;">${escapeHtml(data.content)}</pre>`;
                    }
                } else if (data.type === 'html') {
                    html = (typeof DOMPurify !== 'undefined') ? DOMPurify.sanitize(data.content) : data.content;
                } else {
                    html = `<pre style="white-space:pre-wrap;">${escapeHtml(data.content)}</pre>`;
                }
            } catch (err) {
                console.error('Erro de renderização:', err);
                html = `<div class="alert alert-danger">Erro ao renderizar o documento: ${err.message}</div><pre>${escapeHtml(data.content)}</pre>`;
            }

            const docRender = document.getElementById('docRender');
            if (docRender) {
                docRender.innerHTML = html;
                docRender.style.display = 'block';
            }

            // Vincula links internos
            document.querySelectorAll('.doc-internal-link').forEach(a => {
                a.addEventListener('click', function(e) {
                    e.preventDefault();
                    let href  = this.dataset.path;
                    // Resolve caminho relativo
                    if (!href.startsWith('/') && currentPath) {
                        const base = currentPath.substring(0, currentPath.lastIndexOf('/') + 1);
                        href = base + href;
                    }
                    loadDoc(href.replace(/^\//, ''));
                });
            });

            // Scroll ao topo
            window.scrollTo(0, 0);
        })
        .catch((err) => {
            console.error('Erro AJAX Wiki:', err);
            if (document.getElementById('docLoading')) document.getElementById('docLoading').style.display = 'none';
            const docError = document.getElementById('docError');
            if (docError) {
                docError.innerHTML = `<div class="wiki-empty-icon"><i class="bi bi-wifi-off"></i></div>
                                     <h4>Erro de carregamento</h4><p>Não foi possível carregar o documento ou as bibliotecas de visualização.<br><small>${err.message}</small></p>
                                     <button class="btn btn-sm btn-outline-primary mt-2" onclick="location.reload()">Recarregar Página</button>`;
                docError.style.display = 'flex';
            }
        });
};

// ── Árvore — Toggle pasta ───────────────────────────────────────────────────
document.getElementById('wikiTree').addEventListener('click', function(e) {
    const folder = e.target.closest('.tree-folder-header');
    const file   = e.target.closest('.tree-file');

    if (folder) {
        const li = folder.parentElement;
        li.classList.toggle('open');
    }
    if (file) {
        loadDoc(file.dataset.path);
        // Mobile: fecha sidebar
        if (window.innerWidth < 768) {
            document.getElementById('wikiSidebar').classList.remove('mobile-open');
        }
    }
});

// Abrir primeira pasta automaticamente
document.querySelectorAll('.tree-folder').forEach((el, i) => {
    if (i === 0) el.classList.add('open');
});

// ── Busca ────────────────────────────────────────────────────────────────────
const searchInput   = document.getElementById('wikiSearchInput');
const searchOverlay = document.getElementById('searchResultsOverlay');

searchInput.addEventListener('input', function() {
    clearTimeout(searchTimer);
    const q = this.value.trim();
    if (q.length < 2) {
        searchOverlay.classList.remove('show');
        searchOverlay.innerHTML = '';
        return;
    }
    searchTimer = setTimeout(() => performSearch(q), 300);
});

searchInput.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        searchOverlay.classList.remove('show');
        this.value = '';
    }
});

document.addEventListener('click', function(e) {
    if (!e.target.closest('.wiki-search')) {
        searchOverlay.classList.remove('show');
    }
});

// Atalho ⌘K / Ctrl+K
document.addEventListener('keydown', function(e) {
    if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
        e.preventDefault();
        searchInput.focus();
        searchInput.select();
    }
});

function performSearch(query) {
    searchOverlay.innerHTML = '<div class="search-no-results"><div class="spinner-border spinner-border-sm me-2"></div>Buscando...</div>';
    searchOverlay.classList.add('show');

    fetch(`${BASE_URL}documentacao/buscar?q=${encodeURIComponent(query)}`)
        .then(r => r.json())
        .then(data => {
            if (!data.results || data.results.length === 0) {
                searchOverlay.innerHTML = `<div class="search-no-results">
                    <i class="bi bi-search me-2 opacity-50"></i>Nenhum resultado para "<strong>${escapeHtml(query)}</strong>"
                </div>`;
                return;
            }

            const html = data.results.map(r => `
                <div class="search-result-item" data-path="${escapeHtml(r.path)}">
                    <div class="search-result-folder"><i class="bi bi-folder2 me-1"></i>${escapeHtml(r.folder)}</div>
                    <div class="search-result-title">${escapeHtml(r.title)}</div>
                    ${r.snippet ? `<div class="search-result-snippet">${escapeHtml(r.snippet)}</div>` : ''}
                </div>
            `).join('');

            searchOverlay.innerHTML = html;
            searchOverlay.classList.add('show');

            searchOverlay.querySelectorAll('.search-result-item').forEach(item => {
                item.addEventListener('click', function() {
                    loadDoc(this.dataset.path);
                    searchOverlay.classList.remove('show');
                    searchInput.value = '';
                });
            });
        })
        .catch(() => {
            searchOverlay.innerHTML = '<div class="search-no-results text-danger">Erro ao buscar.</div>';
        });
}

// ── Collapse da sidebar ──────────────────────────────────────────────────────
document.getElementById('btnCollapseSidebar').addEventListener('click', function() {
    const sidebar = document.getElementById('wikiSidebar');
    sidebar.classList.toggle('collapsed');
    localStorage.setItem('wikiSidebarCollapsed', sidebar.classList.contains('collapsed') ? '1' : '0');
});

// Restaura estado
if (localStorage.getItem('wikiSidebarCollapsed') === '1') {
    document.getElementById('wikiSidebar').classList.add('collapsed');
}

// Mobile sidebar
document.getElementById('btnMobileSidebar')?.addEventListener('click', function() {
    document.getElementById('wikiSidebar').classList.toggle('mobile-open');
});

// ── Mapa do Sistema ──────────────────────────────────────────────────────────
document.getElementById('btnMapa').addEventListener('click', function() {
    const modal = new bootstrap.Modal(document.getElementById('modalMapa'));
    modal.show();
});

// ── Helpers ──────────────────────────────────────────────────────────────────
function escapeHtml(text) {
    if (!text) return '';
    return String(text).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function formatFolderName(name) {
    return name
        .replace(/^\d{2,4}[-_]/, '')
        .replace(/[-_]/g, ' ')
        .replace(/\b\w/g, c => c.toUpperCase())
        .replace(/\.md$/i, '');
}

// ── Carregar README por padrão ───────────────────────────────────────────────
window.addEventListener('DOMContentLoaded', function() {
    // Verifica hash na URL para deep link
    const hash = window.location.hash.replace('#', '');
    if (hash) {
        loadDoc(decodeURIComponent(hash));
    }

    // Exibe origem da ajuda quando disponível
    const params = new URLSearchParams(window.location.search);
    const from = params.get('from');
    if (from) {
        try {
            const url = new URL(from, window.location.origin);
            if (url.origin === window.location.origin) {
                const label = document.getElementById('docOriginLabel');
                if (label) {
                    const path = url.pathname.startsWith('/') ? url.pathname.slice(1) : url.pathname;
                    const parts = path.split('/').filter(Boolean);
                    const moduleMap = {
                        os: 'Ordens de Serviço',
                        clientes: 'Clientes',
                        equipamentos: 'Equipamentos',
                        servicos: 'Serviços',
                        estoque: 'Estoque',
                        financeiro: 'Financeiro',
                        relatorios: 'Relatórios',
                        fornecedores: 'Fornecedores',
                        funcionarios: 'Funcionários',
                        usuarios: 'Usuários',
                        grupos: 'Grupos',
                        configuracoes: 'Configurações',
                        vendas: 'Vendas',
                        perfil: 'Perfil'
                    };
                    const moduleKey = parts[0] || 'dashboard';
                    const moduleName = moduleMap[moduleKey] || moduleKey;
                    const tail = parts.slice(1).join(' / ');
                    label.textContent = tail ? `Ajuda referente a: ${moduleName} → ${tail}` : `Ajuda referente a: ${moduleName}`;
                    label.style.display = 'block';
                }
            }
        } catch (e) {
            // ignora se from inválido
        }
    }
});

// ── Ajuda contextual: botões ❓ em outras páginas ──────────────────────────
window.openDocPage = function(path) {
    const from = encodeURIComponent(window.location.pathname + window.location.search + window.location.hash);
    window.location.href = `${BASE_URL}documentacao?from=${from}#${encodeURIComponent(path)}`;
};

setupMarked();
</script>
<?= $this->endSection() ?>
