<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="page-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <h2 class="mb-0"><i class="bi bi-bug me-2"></i>Defeitos Comuns</h2>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('equipamentos-defeitos')" title="Ajuda sobre Defeitos Comuns">
            <i class="bi bi-question-circle me-1"></i>Ajuda
        </button>
        <?php if (can('defeitos', 'importar')): ?>
        <a href="<?= base_url('equipamentosdefeitos/modelo-csv') ?>" class="btn btn-outline-success btn-glow">
            <i class="bi bi-file-earmark-arrow-down me-1"></i>Baixar Modelo CSV
        </a>
        <button type="button" class="btn btn-outline-info btn-glow" data-bs-toggle="modal" data-bs-target="#importModal">
            <i class="bi bi-cloud-arrow-up me-1"></i>Importar CSV
        </button>
        <?php endif; ?>
        <?php if (can('defeitos', 'criar')): ?>
        <button type="button" class="btn btn-primary btn-glow" data-bs-toggle="modal" data-bs-target="#novoDefeitoModal">
            <i class="bi bi-plus-lg me-1"></i>Novo Defeito
        </button>
        <?php endif; ?>
    </div>
</div>

<!-- Campo de Busca -->
<div class="row mb-4">
    <div class="col-12 col-md-6 col-lg-5">
        <div class="input-group" style="box-shadow: 0 4px 12px rgba(0,0,0,0.05); border-radius: 8px; overflow: hidden; border: 1px solid rgba(255,255,255,0.1);">
            <span class="input-group-text bg-body-tertiary border-0"><i class="bi bi-search text-muted"></i></span>
            <input type="text" id="buscaDefeitos" class="form-control border-0 bg-body-tertiary" placeholder="Buscar defeito..." style="box-shadow: none;">
        </div>
    </div>
</div>

<!-- Lista de Defeitos agrupados por Tipo e Classificação -->
<div class="row g-4 mb-4" id="defeitosGrid">
    <?php
    $grupos = [];
    foreach ($defeitos as $d) {
        $grupos[$d['tipo_nome']][$d['classificacao']][] = $d;
    }
    
    // Mapeamento de icones por equipamento
    $iconMap = [
        'celular' => 'phone',
        'smartphone' => 'phone',
        'notebook' => 'laptop',
        'desktop' => 'pc-display',
        'computador' => 'pc-display',
        'impressora' => 'printer',
        'tablet' => 'tablet',
        'tv' => 'tv',
        'televisão' => 'tv',
        'monitor' => 'display',
        'videogame' => 'controller',
        'console' => 'controller'
    ];
    ?>
    <?php if (empty($defeitos)): ?>
        <div class="col-12">
            <div class="alert alert-info d-flex align-items-center gap-2">
                <i class="bi bi-info-circle-fill fs-5"></i>
                <span>Nenhum defeito cadastrado ainda. Clique em <strong>"Novo Defeito"</strong> ou <strong>"Importar CSV"</strong> para começar.</span>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($grupos as $tipoNome => $classificacoes): ?>
        <?php
            $tipoLower = mb_strtolower($tipoNome);
            $equipIcon = 'cpu'; // default
            foreach ($iconMap as $key => $icon) {
                if (strpos($tipoLower, $key) !== false) {
                    $equipIcon = $icon;
                    break;
                }
            }
            $countHw = isset($classificacoes['hardware']) ? count($classificacoes['hardware']) : 0;
            $countSw = isset($classificacoes['software']) ? count($classificacoes['software']) : 0;
        ?>
        <div class="col-12 equipamento-group">
            <div class="card glass-card overflow-hidden" style="border-radius: 12px;">

                <!-- Header do tipo -->
                <div class="card-header d-flex flex-wrap align-items-center gap-3 py-3 px-4"
                     style="background: linear-gradient(90deg, rgba(99,102,241,0.1) 0%, rgba(99,102,241,0.02) 100%);
                            border-bottom: 1px solid rgba(255,255,255,0.05);">
                    <div class="d-flex align-items-center gap-2">
                        <span style="background: rgba(99,102,241,0.25); border-radius: 8px; width:38px; height:38px; display:flex; align-items:center; justify-content:center;">
                            <i class="bi bi-<?= $equipIcon ?> text-info fs-5"></i>
                        </span>
                        <strong class="fs-5 mb-0 ms-1"><?= esc($tipoNome) ?></strong>
                    </div>
                    
                    <div class="d-flex gap-2 ms-auto">
                        <?php if($countHw > 0): ?>
                        <span class="badge d-flex align-items-center gap-1 px-2 py-1" style="background: rgba(239, 68, 68, 0.15); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.3);">
                            <i class="bi bi-cpu"></i> Hardware (<?= $countHw ?>)
                        </span>
                        <?php endif; ?>
                        
                        <?php if($countSw > 0): ?>
                        <span class="badge d-flex align-items-center gap-1 px-2 py-1" style="background: rgba(99, 102, 241, 0.15); color: #c3cbff; border: 1px solid rgba(99, 102, 241, 0.3);">
                            <i class="bi bi-code-slash"></i> Software (<?= $countSw ?>)
                        </span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Corpo: Grid de Defeitos -->
                <div class="card-body px-4 pb-4 pt-1">
                    <?php foreach (['hardware' => ['#ef4444', 'cpu', 'HARDWARE', 'text-danger'], 'software' => ['#6366f1', 'code-slash', 'SOFTWARE', 'text-primary']] as $class => [$hexColor, $icon, $label, $textClass]): ?>
                        <?php if (!empty($classificacoes[$class])): ?>
                        
                        <div class="classificacao-section">
                            <div class="mt-4 mb-3 d-flex align-items-center gap-2">
                                <span style="width:10px; height:10px; border-radius:50%; background:<?= $hexColor ?>; display:inline-block; box-shadow: 0 0 8px <?= $hexColor ?>;"></span>
                                <h6 class="mb-0 fw-bold <?= $textClass ?>" style="letter-spacing: .05em;"><i class="bi bi-<?= $icon ?> me-2"></i><?= $label ?></h6>
                                <div style="height: 1px; flex-grow: 1; background: linear-gradient(90deg, <?= $hexColor ?>44 0%, transparent 100%); margin-left: 10px;"></div>
                            </div>
                            
                            <div class="row g-3">
                                <?php foreach ($classificacoes[$class] as $d): ?>
                                <div class="col-md-6 col-lg-4 col-xl-3 defect-item">
                                    <div class="card h-100 border-0" style="background: rgba(255,255,255,0.03); border-radius: 12px; transition: all 0.2s; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border: 1px solid rgba(255,255,255,0.03) !important;">
                                        <div class="card-body d-flex flex-column p-3">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <h6 class="fw-semibold mb-0 defect-title" style="font-size: 0.95rem; line-height: 1.3; margin-right: 5px;"><?= esc($d['nome']) ?></h6>
                                                
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-link text-muted p-0 border-0" type="button" data-bs-toggle="dropdown">
                                                        <i class="bi bi-three-dots-vertical"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end shadow" style="font-size: 0.85rem;">
                                                        <?php if (can('defeitos', 'editar')): ?>
                                                        <li><button class="dropdown-item btn-procedimentos text-warning fw-bold" type="button" 
                                                                    data-id="<?= $d['id'] ?>" 
                                                                    data-nome="<?= esc($d['nome']) ?>">
                                                            <i class="bi bi-list-check me-2 text-warning"></i>Procedimentos
                                                        </button></li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        
                                                        <li><button class="dropdown-item btn-edit-defeito" type="button" 
                                                                    data-id="<?= $d['id'] ?>" 
                                                                    data-tipo="<?= $d['tipo_id'] ?>" 
                                                                    data-classificacao="<?= $d['classificacao'] ?>" 
                                                                    data-nome="<?= esc($d['nome']) ?>" 
                                                                    data-descricao="<?= esc($d['descricao']) ?>">
                                                            <i class="bi bi-pencil me-2 text-info"></i>Editar
                                                        </button></li>
                                                        <?php endif; ?>
                                                        <?php if (can('defeitos', 'encerrar')): ?>
                                                        <li><button class="dropdown-item text-warning" type="button" onclick="confirmarEncerramento('defeitos', <?= $d['id'] ?>)">
                                                            <i class="bi bi-archive me-2 text-warning"></i>Encerrar
                                                        </button></li>
                                                        <?php endif; ?>
                                                        <?php if (can('defeitos', 'excluir')): ?>
                                                        <li><button class="dropdown-item text-danger btn-delete-defeito" type="button" 
                                                                    data-id="<?= $d['id'] ?>" 
                                                                    data-nome="<?= esc($d['nome']) ?>">
                                                            <i class="bi bi-trash me-2 text-danger"></i>Excluir
                                                        </button></li>
                                                        <?php endif; ?>
                                                    </ul>
                                                </div>
                                            </div>
                                            
                                            <div class="flex-grow-1 mb-3">
                                                <?php if (!empty($d['descricao'])): ?>
                                                    <p class="text-muted small defect-desc mb-0" style="line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; text-overflow: ellipsis;">
                                                        <?= esc($d['descricao']) ?>
                                                    </p>
                                                <?php else: ?>
                                                    <p class="text-muted small fst-italic opacity-50 mb-0">Sem detalhes.</p>
                                                <?php endif; ?>
                                            </div>

                                            <div class="d-flex align-items-center justify-content-between mt-auto pt-2" style="border-top: 1px solid rgba(255,255,255,0.06);">
                                                <div class="d-flex gap-2 flex-wrap">
                                                    <?php if($d['qtd_procedimentos'] > 0): ?>
                                                    <span class="badge fw-normal px-2 btn-procedimentos cursor-pointer" style="background: rgba(245,158,11,0.15); color: #fbbf24; font-size: 0.72rem; border: 1px solid rgba(245,158,11,0.3); cursor:pointer;" data-id="<?= $d['id'] ?>" data-nome="<?= esc($d['nome']) ?>" title="Clique para ver procedimentos">
                                                        <i class="bi bi-list-check me-1"></i> <?= $d['qtd_procedimentos'] ?> passo(s)
                                                    </span>
                                                    <?php endif; ?>
                                                    
                                                    <span class="badge fw-normal px-2" style="background: rgba(255,255,255,0.05); color: #94a3b8; font-size: 0.72rem;" title="Tempo médio (em breve)">
                                                        <i class="bi bi-clock me-1"></i> --:--
                                                    </span>
                                                    <span class="badge fw-normal px-2" style="background: rgba(255,255,255,0.05); color: #94a3b8; font-size: 0.72rem;" title="Preço médio (em breve)">
                                                        <i class="bi bi-currency-dollar me-1"></i> --,--
                                                    </span>
                                                </div>
                                                <button onclick="window.location.href='<?= base_url('os/nova?defeito_sugerido=' . $d['id']) ?>'" class="btn btn-sm btn-outline-secondary py-0 px-2 opacity-50 border-0" style="font-size: 0.75rem; border-radius: 6px;" title="Em breve" disabled>
                                                    <i class="bi bi-plus"></i> OS
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>

            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>


<!-- Modal Novo Defeito -->
<div class="modal fade" id="novoDefeitoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content card-custom">
            <div class="modal-header border-bottom">
                <h5 class="modal-title"><i class="bi bi-bug me-2"></i>Cadastrar Defeito Comum</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= base_url('equipamentosdefeitos/salvar') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nome do Defeito *</label>
                        <input type="text" class="form-control" name="nome" placeholder="Ex: Tela não liga, Bateria não carrega..." required maxlength="150">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Tipo de Equipamento *</label>
                        <select name="tipo_id" class="form-select" required>
                            <option value="">Selecione o tipo...</option>
                            <?php foreach ($tipos as $t): ?>
                                <option value="<?= $t['id'] ?>"><?= esc($t['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Classificação *</label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="classificacao" value="hardware" id="hwCheck" checked>
                                <label class="form-check-label" for="hwCheck">
                                    <i class="bi bi-cpu text-danger me-1"></i>Hardware
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="classificacao" value="software" id="swCheck">
                                <label class="form-check-label" for="swCheck">
                                    <i class="bi bi-code-slash text-primary me-1"></i>Software
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Descrição <small class="text-muted">(opcional)</small></label>
                        <textarea class="form-control" name="descricao" rows="2" placeholder="Descreva brevemente o defeito ou procedimento padrão..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success btn-glow"><i class="bi bi-check-lg me-1"></i>Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Importar CSV -->
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content card-custom">
            <div class="modal-header border-bottom">
                <h5 class="modal-title"><i class="bi bi-cloud-arrow-up me-2"></i>Importar Defeitos em Lote (CSV)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= base_url('equipamentosdefeitos/importar') ?>" method="POST" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="alert alert-info py-2 mb-3">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        O arquivo CSV deve usar <strong>ponto-e-vírgula (;)</strong> como separador com <strong>4 colunas</strong>:
                        <br><code>tipo_equipamento ; nome_defeito ; classificacao ; descricao</code>
                        <br><small class="text-muted">A coluna <em>classificacao</em> deve conter exatamente <strong>hardware</strong> ou <strong>software</strong>. A coluna <em>descricao</em> é opcional.</small>
                    </div>
                    <div class="alert alert-success py-2 mb-3">
                        <i class="bi bi-lightbulb me-2"></i>
                        <strong>Dica:</strong> Tipos de equipamento não existentes serão criados automaticamente! Defeitos duplicados são ignorados sem erro.
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Selecione o arquivo .csv</label>
                        <input class="form-control" type="file" name="arquivo_csv" accept=".csv" required>
                    </div>
                    <p class="text-muted small mb-0">
                        <i class="bi bi-download me-1"></i>
                        Não tem o arquivo? <a href="<?= base_url('equipamentosdefeitos/modelo-csv') ?>" class="text-success fw-bold">Baixar modelo CSV com exemplos</a>
                    </p>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success btn-glow"><i class="bi bi-upload me-1"></i>Importar Defeitos</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Defeito -->
<div class="modal fade" id="editarDefeitoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content card-custom">
            <div class="modal-header border-bottom">
                <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Editar Defeito Comum</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST" id="formEditarDefeito">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nome do Defeito *</label>
                        <input type="text" class="form-control" name="nome" required maxlength="150" id="edit_nome">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Tipo de Equipamento *</label>
                        <select name="tipo_id" class="form-select" required id="edit_tipo_id">
                            <option value="">Selecione o tipo...</option>
                            <?php foreach ($tipos as $t): ?>
                                <option value="<?= $t['id'] ?>"><?= esc($t['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Classificação *</label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="classificacao" value="hardware" id="editHwCheck">
                                <label class="form-check-label" for="editHwCheck">
                                    <i class="bi bi-cpu text-danger me-1"></i>Hardware
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="classificacao" value="software" id="editSwCheck">
                                <label class="form-check-label" for="editSwCheck">
                                    <i class="bi bi-code-slash text-primary me-1"></i>Software
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Descrição <small class="text-muted">(opcional)</small></label>
                        <textarea class="form-control" name="descricao" rows="2" id="edit_descricao"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-glow"><i class="bi bi-check-lg me-1"></i>Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Excluir Defeito -->
<div class="modal fade" id="excluirDefeitoModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content card-custom text-center">
            <div class="modal-header border-0 justify-content-center pt-4 pb-0">
                <div style="width:60px; height:60px; border-radius:50%; background:rgba(239,68,68,0.1); color:#ef4444; display:flex; align-items:center; justify-content:center; font-size:2rem;">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
            </div>
            <div class="modal-body pt-3 pb-3">
                <h5 class="fw-bold mb-3">Confirmar exclusão</h5>
                <p class="text-muted mb-0">Deseja realmente excluir o defeito <br><strong class="text-white" id="delDefeitoNome"></strong>?</p>
                <div class="alert alert-warning mt-3 py-2 small border-0 text-start" style="background:rgba(245,158,11,0.1); color:#fbbf24;">
                    <i class="bi bi-info-circle me-1"></i>Esta ação não pode ser desfeita.
                </div>
            </div>
            <div class="modal-footer border-0 pb-4 d-flex justify-content-center flex-column gap-2 px-4">
                <a href="#" id="btnConfirmDelete" class="btn btn-danger w-100 m-0">Sim, Excluir Defeito</a>
                <button type="button" class="btn btn-outline-secondary w-100 m-0" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Procedimentos -->
<div class="modal fade" id="procedimentosModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content card-custom">
            <div class="modal-header border-bottom">
                <h5 class="modal-title">
                    <i class="bi bi-list-check text-warning me-2"></i>Base de Conhecimento
                    <br>
                    <small class="text-muted fs-6 fw-normal" id="procDefeitoNome">Defeito: ...</small>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-body-tertiary">
                <div class="row">
                    <!-- Form side -->
                    <div class="col-md-5 border-end border-dark-subtle">
                        <h6 class="fw-bold mb-3">Adicionar Passo</h6>
                        <form id="formProcedimento">
                            <?= csrf_field() ?>
                            <input type="hidden" id="proc_defeito_id" name="defeito_id">
                            <input type="hidden" id="proc_id" name="id">
                            <div class="mb-3">
                                <label class="form-label small">Descrição (Ação a ser executada)</label>
                                <textarea class="form-control form-control-sm" name="descricao" id="proc_descricao" rows="3" required placeholder="Ex: Medir tensão do terminal com multímetro..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-warning btn-sm btn-glow w-100 fw-bold border-0 text-dark">
                                <i class="bi bi-plus-lg me-1"></i>Salvar Procedimento
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm w-100 mt-2 d-none" id="btnCancelProcEdit">
                                Cancelar Edição
                            </button>
                        </form>
                    </div>
                    <!-- List side -->
                    <div class="col-md-7">
                        <h6 class="fw-bold mb-3">Procedimentos Operacionais (POP)</h6>
                        <div id="procedimentosLoading" class="text-center py-4 d-none">
                            <div class="spinner-border spinner-border-sm text-warning" role="status"></div>
                            <span class="ms-2 small text-muted">Carregando plano...</span>
                        </div>
                        <div id="procedimentosList" class="d-flex flex-column gap-2" style="max-height: 300px; overflow-y: auto;">
                            <!-- Items via JS -->
                        </div>
                        <div id="procedimentosVazio" class="alert py-2 text-center text-muted small border-0 d-none" style="background: rgba(255,255,255,0.02);">
                            Nenhum procedimento cadastrado para este defeito ainda.
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top py-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Fechar Painel</button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // Hover Effects em Cards
    document.querySelectorAll('.defect-item .card').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.borderColor = 'rgba(255,255,255,0.15)';
            this.style.transform = 'translateY(-2px)';
            this.style.boxShadow = '0 6px 12px rgba(0,0,0,0.1)';
        });
        card.addEventListener('mouseleave', function() {
            this.style.borderColor = 'rgba(255,255,255,0.03)';
            this.style.transform = 'none';
            this.style.boxShadow = '0 4px 6px rgba(0,0,0,0.05)';
        });
    });

    // Real-time Search
    const searchInput = document.getElementById('buscaDefeitos');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const termo = this.value.toLowerCase().trim();
            
            document.querySelectorAll('.equipamento-group').forEach(group => {
                let hasVisibleInGroup = false;
                
                group.querySelectorAll('.classificacao-section').forEach(section => {
                    let hasVisibleInSection = false;
                    
                    section.querySelectorAll('.defect-item').forEach(item => {
                        const title = item.querySelector('.defect-title')?.textContent.toLowerCase() || '';
                        const desc = item.querySelector('.defect-desc')?.textContent.toLowerCase() || '';
                        
                        if (title.includes(termo) || desc.includes(termo)) {
                            item.style.display = '';
                            hasVisibleInSection = true;
                            hasVisibleInGroup = true;
                        } else {
                            item.style.display = 'none';
                        }
                    });
                    
                    if (hasVisibleInSection) {
                        section.style.display = '';
                    } else {
                        section.style.display = 'none';
                    }
                });
                
                if (hasVisibleInGroup) {
                    group.style.display = '';
                } else {
                    group.style.display = 'none';
                }
            });
        });
    }

    // Modal Editar Defeitos Comuns
    const editModalEl = document.getElementById('editarDefeitoModal');
    let editModal = null;
    if (editModalEl) {
        editModal = new bootstrap.Modal(editModalEl);
    }
    document.querySelectorAll('.btn-edit-defeito').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.getAttribute('data-id');
            const nome = this.getAttribute('data-nome');
            const tipo = this.getAttribute('data-tipo');
            const classificacao = this.getAttribute('data-classificacao');
            const desc = this.getAttribute('data-descricao');

            const formEdit = document.getElementById('formEditarDefeito');
            // Utilize a rota do CI4 para disparar update: /equipamentosdefeitos/atualizar/(ID)
            formEdit.action = baseUrl + 'equipamentosdefeitos/atualizar/' + id;
            
            document.getElementById('edit_nome').value = nome;
            document.getElementById('edit_tipo_id').value = tipo;
            
            if(classificacao === 'hardware') {
                document.getElementById('editHwCheck').checked = true;
            } else {
                document.getElementById('editSwCheck').checked = true;
            }
            
            document.getElementById('edit_descricao').value = desc;
            
            editModal.show();
        });
    });

    // Modal Exclusão de Defeitos Comuns
    const delModalEl = document.getElementById('excluirDefeitoModal');
    let delModal = null;
    if (delModalEl) {
        delModal = new bootstrap.Modal(delModalEl);
    }
    document.querySelectorAll('.btn-delete-defeito').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.getAttribute('data-id');
            const nome = this.getAttribute('data-nome');
            
            document.getElementById('delDefeitoNome').textContent = nome;
            const confirmBtn = document.getElementById('btnConfirmDelete');
            confirmBtn.href = baseUrl + 'equipamentosdefeitos/excluir/' + id;
            
            delModal.show();
        });
    });

    // ----------------------------------------------------
    // BASE DE CONHECIMENTO (JS)
    // ----------------------------------------------------
    const procModalEl = document.getElementById('procedimentosModal');
    let procModal = null;
    if (procModalEl) {
        procModal = new bootstrap.Modal(procModalEl);
    }

    const formProc = document.getElementById('formProcedimento');
    const procList = document.getElementById('procedimentosList');
    const procVazio = document.getElementById('procedimentosVazio');
    const procLoading = document.getElementById('procedimentosLoading');

    function carregarProcedimentos(defeitoId) {
        procList.innerHTML = '';
        procList.classList.add('d-none');
        procVazio.classList.add('d-none');
        procLoading.classList.remove('d-none');

        fetch(`${baseUrl}equipamentosdefeitos/procedimentos/${defeitoId}`)
        .then(res => res.json())
        .then(data => {
            procLoading.classList.add('d-none');
            procList.classList.remove('d-none');
            
            // Atualizar contador no card original sem reload
            const cardBadge = document.querySelector(`.defect-item[data-id="${defeitoId}"] .bg-warning`);
            if (cardBadge) {
                cardBadge.innerHTML = `<i class="bi bi-card-list me-1"></i>${data.length} passo(s)`;
            }

            if (data.length === 0) {
                procVazio.classList.remove('d-none');
            } else {
                data.forEach((p, idx) => {
                    renderProcItem(p, idx + 1);
                });
            }
        });
    }

    function renderProcItem(p, stepNum) {
        const div = document.createElement('div');
        div.className = 'd-flex align-items-center justify-content-between p-2 rounded';
        div.style.background = 'rgba(255,255,255,0.03)';
        div.style.border = '1px solid rgba(255,255,255,0.05)';
        
        div.innerHTML = `
            <div class="d-flex align-items-start gap-2 pe-2">
                <span class="badge text-bg-warning rounded-pill mt-1">${stepNum}</span>
                <span class="small" style="line-height: 1.3;">${p.descricao}</span>
            </div>
            <div class="d-flex gap-1 flex-shrink-0">
                <button type="button" class="btn btn-sm btn-link text-info p-0 text-decoration-none btn-edit-proc" data-id="${p.id}" data-desc="${p.descricao.replace(/"/g, '&quot;')}">
                    <i class="bi bi-pencil"></i>
                </button>
                <button type="button" class="btn btn-sm btn-link text-danger p-0 text-decoration-none btn-del-proc" data-id="${p.id}">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        `;
        procList.appendChild(div);
    }

    // Open Modal
    document.querySelectorAll('.btn-procedimentos').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const defeitoId = this.getAttribute('data-id');
            const nomeStr = this.getAttribute('data-nome');
            
            document.getElementById('procDefeitoNome').textContent = `Defeito: ${nomeStr}`;
            document.getElementById('proc_defeito_id').value = defeitoId;
            resetProcForm();
            
            // fetch
            carregarProcedimentos(defeitoId);
            procModal.show();
        });
    });

    // Handle Form Save
    if(formProc) {
        formProc.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch(`${baseUrl}equipamentosdefeitos/procedimentos/salvar`, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success') {
                    resetProcForm();
                    carregarProcedimentos(document.getElementById('proc_defeito_id').value);
                    // Aqui poderia atualizar o contador no HTML via JS, mas no reload da pagina já estará certo
                } else {
                    alert('Erro ao salvar procedimento: ' + (res.msg || 'Erro desconhecido.'));
                }
            });
        });
    }

    // Edit and Delete Procedimentos using event delegation
    if(procList) {
        procList.addEventListener('click', function(e) {
            const btnEdit = e.target.closest('.btn-edit-proc');
            if (btnEdit) {
                document.getElementById('proc_id').value = btnEdit.getAttribute('data-id');
                document.getElementById('proc_descricao').value = btnEdit.getAttribute('data-desc');
                document.getElementById('btnCancelProcEdit').classList.remove('d-none');
            }

            const btnDel = e.target.closest('.btn-del-proc');
            if (btnDel) {
                if (confirm('Excluir este passo?')) {
                    fetch(`${baseUrl}equipamentosdefeitos/procedimentos/excluir/${btnDel.getAttribute('data-id')}`, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(res => res.json())
                    .then(res => {
                        if (res.status === 'success') {
                            carregarProcedimentos(document.getElementById('proc_defeito_id').value);
                        }
                    });
                }
            }
        });
    }

    const btnCancelEdit = document.getElementById('btnCancelProcEdit');
    if(btnCancelEdit) {
        btnCancelEdit.addEventListener('click', resetProcForm);
    }

    function resetProcForm() {
        document.getElementById('proc_id').value = '';
        document.getElementById('proc_descricao').value = '';
        if(btnCancelEdit) btnCancelEdit.classList.add('d-none');
    }

});
</script>
<?= $this->endSection() ?>

