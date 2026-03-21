<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="page-header d-flex justify-content-between align-itemês-center mb-4">
    <h2 class="mb-0"><i class="bi bi-palette2 me-2"></i>Design System</h2>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-info btn-sm rounded-pill" onclick="window.openDocPage('design-system')">
            <i class="bi bi-question-circle me-1"></i>Ajuda
        </button>
        <button type="button" class="btn btn-outline-secondary btn-sm" id="btnToggleDsTheme">
            <i class="bi bi-moon-stars me-1"></i>Alternar tema
        </button>
    </div>
</div>

<div class="card glass-card mb-4">
    <div class="card-body">
        <div class="d-flex flex-wrap gap-2 align-itemês-center">
            <span class="badge text-bg-primary">tokens</span>
            <span class="badge text-bg-secondary">componentes base</span>
            <span class="badge text-bg-info">componentes compostos</span>
            <span class="badge text-bg-success">layouts</span>
            <span class="badge text-bg-warning">patterns</span>
        </div>
        <p class="mb-0 mt-3 text-muted">
            Esta pagina consãolida os padroes visuais e comportamentais para usão em todos os modulos:
            OS, Equipamentos, Servicos, Estoque, Financeiro, Relatorios, Documentacao e Configuracoes.
        </p>
    </div>
</div>

<div class="row g-4">
    <div class="col-12">
        <div class="card glass-card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-droplet-half me-2"></i>1. Cores e Tokens</h5>
            </div>
            <div class="card-body">
                <div class="ds-token-grid">
                    <div class="ds-token-item"><div class="ds-token-swatch bg-primary"></div><small>Primary</small></div>
                    <div class="ds-token-item"><div class="ds-token-swatch bg-success"></div><small>Success</small></div>
                    <div class="ds-token-item"><div class="ds-token-swatch bg-warning"></div><small>Warning</small></div>
                    <div class="ds-token-item"><div class="ds-token-swatch bg-danger"></div><small>Danger</small></div>
                    <div class="ds-token-item"><div class="ds-token-swatch bg-info"></div><small>Info</small></div>
                    <div class="ds-token-item"><div class="ds-token-swatch ds-token-bg"></div><small>BG</small></div>
                    <div class="ds-token-item"><div class="ds-token-swatch ds-token-surface"></div><small>Surface</small></div>
                    <div class="ds-token-item"><div class="ds-token-swatch ds-token-muted"></div><small>Muted surface</small></div>
                    <div class="ds-token-item"><div class="ds-token-swatch ds-token-border"></div><small>Border</small></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="card glass-card h-100">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-type me-2"></i>2. Tipografia</h5>
            </div>
            <div class="card-body">
                <h1 class="mb-2">Heading 1</h1>
                <h2 class="mb-2">Heading 2</h2>
                <h3 class="mb-2">Heading 3</h3>
                <h4 class="mb-3">Heading 4</h4>
                <p class="mb-1">Texto base para listas, tabelas e formularios.</p>
                <p class="text-muted mb-0">Texto auxiliar para dicas, status e observacoes.</p>
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="card glass-card h-100">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-hand-index-thumb me-2"></i>3. Botoes</h5>
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-primary">Primary</button>
                    <button type="button" class="btn btn-secondary">Secondary</button>
                    <button type="button" class="btn btn-outline-secondary">Outline</button>
                    <button type="button" class="btn btn-success">Success</button>
                    <button type="button" class="btn btn-warning">Warning</button>
                    <button type="button" class="btn btn-danger">Danger</button>
                    <button type="button" class="btn btn-glow">Glow</button>
                    <button type="button" class="btn btn-light">Ghost</button>
                    <button type="button" class="btn btn-primary ds-loading" disabled>
                        <span class="spinner-border spinner-border-sm me-2"></span>Loading
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card glass-card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-ui-checks-grid me-2"></i>4. Formulario</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Input</label>
                        <input type="text" class="form-control" placeholder="Digite um valor">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Select</label>
                        <select class="form-select">
                            <option>Selecione...</option>
                            <option>Aguardando analise</option>
                            <option>Em reparo</option>
                            <option>Pronto</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Date</label>
                        <input type="date" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Textarea</label>
                        <textarea class="form-control" rows="3" placeholder="Descricao padrao"></textarea>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="dsCheckDefault">
                            <label class="form-check-label" for="dsCheckDefault">Checkbox default</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="dsSwitchDefault" checked>
                            <label class="form-check-label" for="dsSwitchDefault">Switch ativo</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <input type="text" class="form-control is-invalid" value="Valor invalido">
                        <div class="invalid-feedback d-block">Mensagem de erro padrao.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-7">
        <div class="card glass-card h-100">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-layout-text-window-reverse me-2"></i>5. Tabs e Tabela</h5>
            </div>
            <div class="card-body">
                <ul class="nav nav-tabs mb-3" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#ds-tab-dados" type="button">Dados</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#ds-tab-fotos" type="button">Fotos</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#ds-tab-valores" type="button">Valores</button>
                    </li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="ds-tab-dados">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>OS</th>
                                        <th>Cliente</th>
                                        <th>Status</th>
                                        <th class="text-end">Valor</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>#OS20260041</td>
                                        <td>Davi Oliveira</td>
                                        <td><span class="badge text-bg-warning">Aguard. analise</span></td>
                                        <td class="text-end">R$ 0,00</td>
                                    </tr>
                                    <tr>
                                        <td>#OS20260042</td>
                                        <td>Maria Silva</td>
                                        <td><span class="badge text-bg-info">Aguard. aprovacao</span></td>
                                        <td class="text-end">R$ 250,00</td>
                                    </tr>
                                    <tr>
                                        <td>#OS20260043</td>
                                        <td>Joao Santos</td>
                                        <td><span class="badge text-bg-success">Pronto</span></td>
                                        <td class="text-end">R$ 420,00</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="ds-tab-fotos">
                        <p class="mb-0 text-muted">Use a galeria padrao para fotos de equipamento, OS e acessãorios.</p>
                    </div>
                    <div class="tab-pane fade" id="ds-tab-valores">
                        <p class="mb-0 text-muted">Campos monetarios e resumo de valores seguem o mesmo padrao visual.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-5">
        <div class="card glass-card h-100">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-images me-2"></i>6. Galeria e Alertas</h5>
            </div>
            <div class="card-body">
                <div class="ds-gallery mb-3">
                    <div class="ds-gallery-thumb is-primary"><div class="ds-gallery-fake ds-gallery-fake-a"></div></div>
                    <div class="ds-gallery-thumb"><div class="ds-gallery-fake ds-gallery-fake-b"></div></div>
                    <div class="ds-gallery-thumb"><div class="ds-gallery-fake ds-gallery-fake-c"></div></div>
                    <div class="ds-gallery-thumb"><div class="ds-gallery-fake ds-gallery-fake-d"></div></div>
                </div>
                <div class="alert alert-success mb-2"><i class="bi bi-check-circle me-2"></i>Acao salva com sucessão.</div>
                <div class="alert alert-warning mb-2"><i class="bi bi-exclamation-triangle me-2"></i>Itens opcionais pendentes.</div>
                <div class="alert alert-danger mb-0"><i class="bi bi-x-circle me-2"></i>Campo obrigatorio nao preenchido.</div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    (function () {
        const toggleBtn = document.getElementById('btnToggleDsTheme');
        if (!toggleBtn) return;

        toggleBtn.addEventListener('click', function () {
            const html = document.documentElement;
            const current = html.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
            const next = current === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-theme', next);
            html.setAttribute('data-bs-theme', next);
            localStorage.setItem('theme', next);
        });
    })();
</script>
<?= $this->endSection() ?>
