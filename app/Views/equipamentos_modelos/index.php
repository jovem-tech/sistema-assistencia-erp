<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="bi bi-cpu me-2"></i>Modelos de Equipamento</h2>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('equipamentos-modelos')" title="Ajuda sobre Modelos">
            <i class="bi bi-question-circle me-1"></i>Ajuda
        </button>
        <?php if (can('equipamentos', 'importar')): ?>
        <button type="button" class="btn btn-outline-info me-2 btn-glow" data-bs-toggle="modal" data-bs-target="#importModal">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i>Importar Lote
        </button>
        <?php endif; ?>
        <?php if (can('equipamentos', 'criar')): ?>
        <button type="button" class="btn btn-primary btn-glow" data-bs-toggle="modal" data-bs-target="#novoModeloModal">
            <i class="bi bi-plus-lg me-1"></i>Novo Modelo
        </button>
        <?php endif; ?>
    </div>
</div>

<div class="card glass-card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover datatable">
                <thead>
                    <tr>
                        <th width="10%">#</th>
                        <th width="35%">Marca</th>
                        <th width="40%">Modelo</th>
                        <th width="15%">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($modelos)): ?>
                        <?php foreach ($modelos as $m): ?>
                        <tr>
                            <td><?= $m['id'] ?></td>
                            <td><span class="badge bg-secondary"><?= esc($m['marca_nome']) ?></span></td>
                            <td><strong><?= esc($m['nome']) ?></strong></td>
                            <td>
                                <?php if (can('equipamentos', 'excluir')): ?>
                                <a href="<?= base_url('equipamentosmodelos/excluir/' . $m['id']) ?>" class="btn btn-sm btn-outline-danger btn-delete" data-nome="<?= esc($m['nome']) ?>" title="Excluir">
                                    <i class="bi bi-trash"></i> Excluir
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Cadastrar Modelo -->
<div class="modal fade" id="novoModeloModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 460px;">
        <div class="modal-content card-custom shadow-lg">
            <div class="modal-header border-bottom">
                <h5 class="modal-title"><i class="bi bi-plus-circle text-primary me-2"></i>Cadastrar Modelo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('equipamentosmodelos/salvar') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <!-- Marca -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Selecione a Marca *</label>
                        <select name="marca_id" id="marcaSelectModelo" class="form-select" required>
                            <option value="">Selecione...</option>
                            <?php foreach($marcas as $mc): ?>
                                <option value="<?= $mc['id'] ?>"><?= esc($mc['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Modelo com autocomplete -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nome do Modelo (Ex: Galaxy S24) *</label>
                        <div class="position-relative">
                            <input type="text" class="form-control" name="nome" id="inputNomeModelo"
                                   required maxlength="100" autocomplete="off"
                                   placeholder="Ex: Galaxy S24, iPhone 15, Moto G84...">
                            <div id="spinnerInputModelo" class="position-absolute top-50 end-0 translate-middle-y me-2 d-none">
                                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                            </div>
                        </div>
                        <!-- Dropdown de sugestões -->
                        <div id="sugestoesInputModelo" class="list-group shadow mt-1 d-none"
                             style="max-height: 230px; overflow-y: auto; border-radius: 8px; z-index: 9999; position: relative;"></div>
                        <div class="form-text">
                            <i class="bi bi-globe2 me-1 text-info"></i>
                            Sugestões da internet aparecem ao digitar 3 ou mais caracteres
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success btn-glow flex-fill">
                        <i class="bi bi-check-lg me-1"></i>Salvar Registro
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- Modal Importação CSV -->
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content card-custom">
            <div class="modal-header border-bottom">
                <h5 class="modal-title"><i class="bi bi-cloud-arrow-up me-2"></i>Importar Tipos de Modelos (CSV)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('equipamentosmodelos/importar') ?>" method="POST" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="alert alert-info py-2">
                        <i class="bi bi-info-circle-fill me-2"></i> O arquivo deve usar (;) para separação, seguindo o padrão DUAS COLUNAS: <code>Nome da Marca ; Nome do Modelo</code>. As marcas que não existirem serão criadas automaticamente! Sem cabeçalho.
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Selecione o CSV</label>
                        <input class="form-control" type="file" name="arquivo_csv" accept=".csv" required>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success btn-glow">Importar Cruzados</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
// ─── Autocomplete inteligente no Modal "Cadastrar Modelo" ─────────────────────
(function () {
    const inputModelo   = document.getElementById('inputNomeModelo');
    const sugestoesBox  = document.getElementById('sugestoesInputModelo');
    const spinnerModelo = document.getElementById('spinnerInputModelo');
    const baseUrl       = '<?= base_url() ?>';

    if (!inputModelo) return;

    let debounceTimer = null;

    function renderSugestoes(groups) {
        sugestoesBox.innerHTML = '';
        let total = 0;

        groups.forEach(group => {
            if (!group.children || group.children.length === 0) return;

            // Cabeçalho do grupo (Cadastrados / Internet)
            const header = document.createElement('div');
            header.className = 'list-group-item list-group-item-secondary py-1 px-3';
            header.style.cssText = 'font-size:0.7rem; font-weight:700; letter-spacing:0.5px; text-transform:uppercase; pointer-events:none; opacity:0.8;';
            const isCadastrado = group.text.includes('Cadastrados');
            header.textContent = (isCadastrado ? '📋 ' : '🌐 ') + group.text.replace(/^[📋🌐] /, '');
            sugestoesBox.appendChild(header);

            group.children.forEach(item => {
                let parts = [];
                if (item.marca) parts.push(item.marca);
                if (item.tipo) parts.push(item.tipo);
                let subtitle = parts.length > 0 ? `<div style="font-size:0.75rem; color:#6c757d; margin-top:-2px;">(${parts.join(' - ')})</div>` : '';

                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'list-group-item list-group-item-action py-2 px-3 d-flex align-items-start gap-2';
                btn.style.fontSize = '0.88rem';
                btn.innerHTML = `
                    <div class="mt-1"><i class="bi bi-${isCadastrado ? 'check-circle text-success' : 'globe2 text-info'}" style="font-size:0.8rem;"></i></div>
                    <div>
                        <strong style="color:var(--bs-heading-color);">${item.text}</strong>
                        ${subtitle}
                    </div>
                `;
                btn.addEventListener('mousedown', e => e.preventDefault()); // evita perda de foco
                btn.addEventListener('click', () => {
                    inputModelo.value = item.text;
                    sugestoesBox.classList.add('d-none');
                    inputModelo.focus();
                });
                sugestoesBox.appendChild(btn);
                total++;
            });
        });

        if (total > 0) {
            sugestoesBox.classList.remove('d-none');
        } else {
            sugestoesBox.innerHTML = '<div class="list-group-item text-muted small py-2 px-3"><i class="bi bi-info-circle me-1"></i>Nenhuma sugestão encontrada. Digite e salve manualmente.</div>';
            sugestoesBox.classList.remove('d-none');
        }
    }

    inputModelo.addEventListener('input', function () {
        const q = this.value.trim();
        clearTimeout(debounceTimer);

        if (q.length < 3) {
            sugestoesBox.classList.add('d-none');
            spinnerModelo.classList.add('d-none');
            return;
        }

        spinnerModelo.classList.remove('d-none');
        sugestoesBox.classList.add('d-none');

        debounceTimer = setTimeout(() => {
            const marcaSel  = document.getElementById('marcaSelectModelo');
            const marcaId   = marcaSel ? marcaSel.value : '';
            const marcaNome = marcaSel ? (marcaSel.options[marcaSel.selectedIndex]?.text || '') : '';

            const params = new URLSearchParams({
                q:        q,
                marca_id: marcaId,
                marca:    marcaNome !== 'Selecione...' ? marcaNome : '',
                tipo:     ''   // sem tipo nesta tela — a API usará apenas marca + termo
            });

            fetch(`${baseUrl}api/modelos/buscar?${params}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(data => {
                spinnerModelo.classList.add('d-none');
                if (data.results && data.results.length > 0) {
                    renderSugestoes(data.results);
                } else {
                    sugestoesBox.classList.add('d-none');
                }
            })
            .catch(() => spinnerModelo.classList.add('d-none'));
        }, 400);
    });

    // Fechar ao perder foco
    inputModelo.addEventListener('blur', () => {
        setTimeout(() => sugestoesBox.classList.add('d-none'), 200);
    });

    // Limpar ao fechar o modal
    document.getElementById('novoModeloModal')?.addEventListener('hidden.bs.modal', () => {
        inputModelo.value = '';
        sugestoesBox.classList.add('d-none');
        spinnerModelo.classList.add('d-none');
    });
})();
</script>
<?= $this->endSection() ?>
