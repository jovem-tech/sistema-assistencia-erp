?<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<style>
    :root {
        --cm-quick-bg: #f8fafc;
        --cm-card-border: rgba(226, 232, 240, 0.8);
    }

    .cm-quick-replies-page {
        animation: fadeIn 0.4s ease-out;
        padding-top: 5px;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .card.glass-card {
        border: 1px solid var(--cm-card-border) !important;
        border-radius: 16px !important;
        background: rgba(255, 255, 255, 0.95);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.04), 0 4px 6px -2px rgba(0, 0, 0, 0.02) !important;
        backdrop-filter: blur(10px);
        margin-bottom: 1.5rem;
    }

    .card.glass-card .card-header {
        background: transparent;
        border-bottom: 2px solid #f1f5f9;
        padding: 1.25rem 1.5rem;
        font-weight: 700;
        color: var(--ds-color-primary, #635bff);
        font-size: 1.1rem;
        display: flex;
        align-items: center;
        gap: 0.6rem;
    }

    .cm-form-group {
        margin-bottom: 1.15rem;
    }

    .cm-form-label {
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
        letter-spacing: 0.03em;
        text-transform: uppercase;
        font-size: 0.72rem !important;
        font-weight: 700;
        color: #64748b;
        margin-bottom: 7px;
        display: block;
    }

    .form-control-ds {
        border: 1.5px solid #e2e8f0;
        border-radius: 10px;
        padding: 0.65rem 0.9rem;
        font-size: 0.92rem;
        transition: all 0.2s ease;
        background-color: #fbfcfd;
        color: #1e293b;
    }

    .form-control-ds:focus {
        background-color: #fff;
        border-color: var(--ds-color-primary, #635bff);
        box-shadow: 0 0 0 4px rgba(99, 91, 255, 0.08);
        outline: none;
    }

    .cm-quick-table thead th {
        background: #f8fafc;
        color: #64748b;
        font-size: 0.72rem;
        text-transform: uppercase;
        font-weight: 800;
        letter-spacing: 0.06em;
        padding: 1.1rem 1.25rem;
        border-bottom: 2px solid #f1f5f9;
        border-top: none;
    }

    .cm-quick-table tbody td {
        padding: 1.25rem;
        vertical-align: middle;
        font-size: 0.95rem;
        border-bottom: 1px solid #f1f5f9;
        color: #334155;
    }

    .cm-quick-table tr:hover {
        background-color: rgba(99, 91, 255, 0.02);
        cursor: pointer;
    }

    .cm-badge-cat {
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        color: #475569;
        padding: 5px 12px;
        border-radius: 8px;
        font-size: 0.72rem;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .cm-btn-status {
        min-width: 85px;
        border-radius: 12px;
        font-size: 0.68rem;
        font-weight: 800;
        letter-spacing: 0.06em;
        padding: 6px 14px;
        text-transform: uppercase;
        border-width: 2px;
    }

    .cm-btn-tag {
        font-weight: 700;
        font-size: 0.68rem;
        padding: 4px 10px;
        border-radius: 8px;
        letter-spacing: 0.02em;
        transition: all 0.2s ease;
    }

    .cm-btn-tag:hover {
        transform: scale(1.05);
    }

    .cm-tag-picker-wrap {
        background: #f8fafc;
        border: 1.5px dashed #e2e8f0;
        border-radius: 12px;
        padding: 12px;
    }

    .cm-msg-preview-cell {
        color: #64748b;
        line-height: 1.5;
        max-width: 380px;
        display: block;
        transition: color 0.2s ease;
    }
    
    tr:hover .cm-msg-preview-cell {
        color: #1e293b;
    }

    .cm-order-badge {
        font-family: 'JetBrains Mono', 'Courier New', monospace;
        font-size: 0.85rem;
        color: #94a3b8;
        background: #f8fafc;
        padding: 2px 8px;
        border-radius: 5px;
        border: 1px solid #eee;
    }

    .cm-btn-save {
        border-radius: 12px;
        font-weight: 700;
        letter-spacing: 0.01em;
        padding: 11px 28px;
        transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    
    .cm-btn-save:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 15px rgba(99, 91, 255, 0.25);
    }

    .cm-table-wrap {
        border-radius: 0 0 16px 16px;
        overflow: hidden;
    }

    @media (max-width: 991.98px) {
        .cm-quick-replies-page { padding-top: 0; }
        .cm-quick-table thead { display: none; }
        .cm-quick-table tr { 
            display: block; 
            border-bottom: 2px solid #f1f5f9; 
            padding: 1rem;
        }
        .cm-quick-table td { 
            display: block; 
            padding: 0.35rem 0; 
            border: none;
            width: 100% !important;
        }
    }
</style>

<div class="cm-quick-replies-page">
    <div class="page-header ps-0 mb-4 bg-transparent border-0">
        <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-primary bg-opacity-10 p-2 rounded-3 text-primary">
                    <i class="bi bi-chat-dots-fill fs-4"></i>
                </div>
                <div>
                    <h2 class="mb-0 fw-bold h4">Respostas Rápidas</h2>
                    <p class="text-muted small mb-0">Gerencie modelos de mensagens inteligentes com preenchimento automático.</p>
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-info rounded-pill px-3 shadow-sm" onclick="window.openDocPage('atendimento-whatsapp-respostas')">
                <i class="bi bi-question-circle me-1"></i>Ajuda
            </button>
        </div>
    </div>

    <?= $this->include('central_mensagens/_menu') ?>

    <div class="row g-4">
        <!-- Lado Esquerdo: Formulário de Criação -->
        <div class="col-12 col-xl-4">
            <div class="card glass-card h-100">
                <div class="card-header border-bottom-0">
                    <i class="bi bi-plus-circle-dotted"></i>
                    <span>Cadastrar modelo</span>
                </div>
                <div class="card-body p-4 pt-2">
                    <form action="<?= base_url('atendimento-whatsapp/respostas-rapidas/salvar') ?>" method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="0">
                        
                        <div class="cm-form-group">
                            <label class="cm-form-label">Título Identificador</label>
                            <input type="text" name="titulo" class="form-control form-control-ds" placeholder="Ex: Aviso de Conclusão" required>
                        </div>
                        
                        <div class="cm-form-group">
                            <label class="cm-form-label">Categoria</label>
                            <input type="text" name="categoria" class="form-control form-control-ds" placeholder="Ex: Geral, Orçamento">
                        </div>
                        
                        <div class="cm-form-group">
                            <label class="cm-form-label">Texto da Mensagem</label>
                            <textarea name="mensagem" id="create_rrMensagem" class="form-control form-control-ds" rows="6" placeholder="Escreva aqui sua resposta inteligente..." required></textarea>
                            
                            <!-- Variable Picker -->
                            <div class="cm-tag-picker-wrap mt-3">
                                <label class="cm-form-label text-primary d-flex align-items-center gap-2 mb-2">
                                    <i class="bi bi-braces"></i> Tags Dinâmicas
                                </label>
                                <div class="d-flex flex-wrap gap-1">
                                    <button type="button" class="btn btn-tag cm-btn-tag btn-outline-primary" data-tag="{{cliente_nome}}" data-target="create_rrMensagem">Cliente</button>
                                    <button type="button" class="btn btn-tag cm-btn-tag btn-outline-primary" data-tag="{{numero_os}}" data-target="create_rrMensagem">Nº OS</button>
                                    <button type="button" class="btn btn-tag cm-btn-tag btn-outline-info" data-tag="{{equipamento}}" data-target="create_rrMensagem">Equipamento</button>
                                    <button type="button" class="btn btn-tag cm-btn-tag btn-outline-info" data-tag="{{marca}}" data-target="create_rrMensagem">Marca</button>
                                    <button type="button" class="btn btn-tag cm-btn-tag btn-outline-info" data-tag="{{modelo}}" data-target="create_rrMensagem">Modelo</button>
                                    <button type="button" class="btn btn-tag cm-btn-tag btn-outline-secondary" data-tag="{{status}}" data-target="create_rrMensagem">Status</button>
                                    <button type="button" class="btn btn-tag cm-btn-tag btn-outline-success" data-tag="{{valor_final}}" data-target="create_rrMensagem">Valor R$</button>
                                    <button type="button" class="btn btn-tag cm-btn-tag btn-outline-warning" data-tag="{{data_previsao}}" data-target="create_rrMensagem">Previsão</button>
                                    <button type="button" class="btn btn-tag cm-btn-tag btn-outline-danger" data-tag="{{defeito}}" data-target="create_rrMensagem">Defeito</button>
                                </div>
                                <div class="x-small text-muted mt-2">Clique para inserir no texto.</div>
                            </div>
                        </div>
                        
                        <div class="row g-3 mb-4">
                            <div class="col-6">
                                <label class="cm-form-label">Peso / Ordem</label>
                                <input type="number" name="ordem" class="form-control form-control-ds" value="0">
                            </div>
                            <div class="col-6 d-flex align-items-end">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" name="ativo" value="1" checked>
                                    <label class="form-check-label fw-bold small text-secondary">ATIVO</label>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-glow cm-btn-save w-100">
                            <i class="bi bi-send-check me-1"></i> Criar Resposta
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Lado Direito: Listagem com Clique para Editar em Modal -->
        <div class="col-12 col-xl-8">
            <div class="card glass-card">
                <div class="card-header justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-collection-play-fill text-primary"></i> 
                        <span>Catálogo de Respostas</span>
                    </div>
                    <?php if (!empty($respostas)): ?>
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-3"><?= count($respostas) ?> cadastradas</span>
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive cm-table-wrap">
                        <table class="table cm-quick-table mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th style="width: 25%;">Título</th>
                                    <th style="width: 15%;">Categoria</th>
                                    <th style="width: 40%;">Mensagem</th>
                                    <th style="width: 10%;" class="text-center">Ordem</th>
                                    <th style="width: 10%;" class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($respostas)): ?>
                                    <?php foreach ($respostas as $r): ?>
                                        <tr onclick="showEditModal(<?= htmlspecialchars(json_encode($r)) ?>)" title="Clique para editar em modal">
                                            <td class="fw-bolder"><?= esc($r['titulo']) ?></td>
                                            <td>
                                                <span class="cm-badge-cat">
                                                    <?= esc((string) ($r['categoria'] ?: 'geral')) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="cm-msg-preview-cell text-truncate">
                                                    <?= esc($r['mensagem']) ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="cm-order-badge"><?= (int) ($r['ordem'] ?? 0) ?></span>
                                            </td>
                                            <td class="text-center">
                                                <form action="<?= base_url('atendimento-whatsapp/respostas-rapidas/toggle/' . (int) $r['id']) ?>" method="post" onclick="event.stopPropagation()">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" class="btn btn-sm cm-btn-status <?= (int) $r['ativo'] === 1 ? 'btn-success' : 'btn-outline-secondary' ?>">
                                                        <?= (int) $r['ativo'] === 1 ? 'Ativo' : 'Inativo' ?>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="py-5 text-center opacity-50">
                                            <i class="bi bi-chat-left-dots fs-1 d-block mb-3"></i>
                                            <h5>Nenhuma resposta cadastrada</h5>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Edição Profissional -->
<div class="modal fade" id="modalEditarRR" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content glass-card border-0 shadow-lg" style="background: #fff;">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2">
                    <div class="bg-warning bg-opacity-10 p-2 rounded-3 text-warning">
                        <i class="bi bi-pencil-square"></i>
                    </div>
                    Editar Resposta Rápida
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 pt-3">
                <form action="<?= base_url('atendimento-whatsapp/respostas-rapidas/salvar') ?>" method="post" id="formEditarRR">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" id="edit_rrId" value="">
                    
                    <div class="row g-3">
                        <div class="col-md-8">
                            <div class="cm-form-group">
                                <label class="cm-form-label" for="edit_rrTitulo">Título Identificador</label>
                                <input type="text" name="titulo" id="edit_rrTitulo" class="form-control form-control-ds" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="cm-form-group">
                                <label class="cm-form-label" for="edit_rrOrdem">Peso / Ordem</label>
                                <input type="number" name="ordem" id="edit_rrOrdem" class="form-control form-control-ds">
                            </div>
                        </div>
                        <div class="col-md-7">
                            <div class="cm-form-group">
                                <label class="cm-form-label" for="edit_rrCategoria">Categoria / Tag</label>
                                <input type="text" name="categoria" id="edit_rrCategoria" class="form-control form-control-ds">
                            </div>
                        </div>
                        <div class="col-md-5 d-flex align-items-center">
                            <div class="form-check form-switch mt-3">
                                <input class="form-check-input" type="checkbox" name="ativo" value="1" id="edit_rrAtivo">
                                <label class="form-check-label fw-bold small text-secondary" for="edit_rrAtivo">STATUS ATIVO</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="cm-form-group mb-0">
                                <label class="cm-form-label" for="edit_rrMensagem">Mensagem Inteligente</label>
                                <textarea name="mensagem" id="edit_rrMensagem" class="form-control form-control-ds" rows="7" required></textarea>
                                
                                <div class="cm-tag-picker-wrap mt-3 bg-light bg-opacity-50">
                                    <label class="cm-form-label text-primary d-flex align-items-center gap-2 mb-2">
                                        <i class="bi bi-lightning-charge-fill"></i> Inserir Variáveis
                                    </label>
                                    <div class="d-flex flex-wrap gap-1">
                                        <button type="button" class="btn cm-btn-tag btn-outline-primary" data-tag="{{cliente_nome}}" data-target="edit_rrMensagem">Cliente</button>
                                        <button type="button" class="btn cm-btn-tag btn-outline-primary" data-tag="{{numero_os}}" data-target="edit_rrMensagem">Nº OS</button>
                                        <button type="button" class="btn cm-btn-tag btn-outline-info" data-tag="{{equipamento}}" data-target="edit_rrMensagem">Equipamento</button>
                                        <button type="button" class="btn cm-btn-tag btn-outline-info" data-tag="{{marca}}" data-target="edit_rrMensagem">Marca</button>
                                        <button type="button" class="btn cm-btn-tag btn-outline-info" data-tag="{{modelo}}" data-target="edit_rrMensagem">Modelo</button>
                                        <button type="button" class="btn cm-btn-tag btn-outline-secondary" data-tag="{{status}}" data-target="edit_rrMensagem">Status</button>
                                        <button type="button" class="btn cm-btn-tag btn-outline-success" data-tag="{{valor_final}}" data-target="edit_rrMensagem">Valor R$</button>
                                        <button type="button" class="btn cm-btn-tag btn-outline-warning" data-tag="{{data_previsao}}" data-target="edit_rrMensagem">Previsão</button>
                                        <button type="button" class="btn cm-btn-tag btn-outline-danger" data-tag="{{defeito}}" data-target="edit_rrMensagem">Defeito</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button type="button" class="btn btn-link text-decoration-none text-muted" data-bs-dismiss="modal">Fechar</button>
                <button type="submit" form="formEditarRR" class="btn btn-primary px-4 py-2 fw-bold shadow-sm">
                    <i class="bi bi-save2 me-1"></i> Salvar Alterações
                </button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    let modalEditar;

    document.addEventListener('DOMContentLoaded', function() {
        const modalEl = document.getElementById('modalEditarRR');
        if (modalEl && window.bootstrap) {
            modalEditar = new bootstrap.Modal(modalEl);
        }

        // Delegar eventos de cliques em tags (funciona em modais dinâmicos)
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.cm-btn-tag');
            if (btn) {
                const tag = btn.getAttribute('data-tag');
                const targetId = btn.getAttribute('data-target');
                const targetEl = document.getElementById(targetId);
                if (targetEl && tag) {
                    insertAtCursor(targetEl, tag);
                }
            }
        });
    });

    /**
     * Abre o modal com os dados carregados
     */
    function showEditModal(data) {
        document.getElementById('edit_rrId').value = data.id || '0';
        document.getElementById('edit_rrTitulo').value = data.titulo || '';
        document.getElementById('edit_rrCategoria').value = data.categoria || '';
        document.getElementById('edit_rrMensagem').value = data.mensagem || '';
        document.getElementById('edit_rrOrdem').value = data.ordem || 0;
        document.getElementById('edit_rrAtivo').checked = parseInt(data.ativo) === 1;
        
        if (!modalEditar) {
            const el = document.getElementById('modalEditarRR');
            if (el && window.bootstrap) modalEditar = new bootstrap.Modal(el);
        }
        
        if (modalEditar) modalEditar.show();
    }

    /**
     * Função auxiliar para inserir tags no cursor do textarea
     */
    function insertAtCursor(myField, myValue) {
        if (!myField) return;

        if (document.selection) {
            myField.focus();
            let sel = document.selection.createRange();
            sel.text = myValue;
        } else if (myField.selectionStart || myField.selectionStart === 0) {
            let startPos = myField.selectionStart;
            let endPos = myField.selectionEnd;
            myField.value = myField.value.substring(0, startPos) + myValue + myField.value.substring(endPos, myField.value.length);
            myField.selectionStart = startPos + myValue.length;
            myField.selectionEnd = startPos + myValue.length;
        } else {
            myField.value += myValue;
        }
        myField.focus();
        myField.dispatchEvent(new Event('input', { bubbles: true }));
    }
</script>
<?= $this->endSection() ?>


