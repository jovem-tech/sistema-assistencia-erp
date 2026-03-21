<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-header">
    <div class="d-flex align-itemês-center gap-3">
        <h2><i class="bi bi-clock-history me-2"></i>CRM - Timeline</h2>
        <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('crm')">
            <i class="bi bi-question-circle me-1"></i>Ajuda
        </button>
    </div>
</div>

<div class="card glass-card mb-4 shadow-sm border-0">
    <div class="card-body">
        <h6 class="mb-3 text-primary fw-bold"><i class="bi bi-filter me-1"></i>Filtros da Timeline</h6>
        <form class="row g-3" method="get" action="<?= base_url('crm/timeline') ?>">
            <div class="col-lg-4">
                <label class="form-label small fw-bold">Cliente</label>
                <select class="form-select select2-clientes" name="cliente_id">
                    <option value="">Todos os clientes</option>
                    <?php foreach (($clientes ?? []) as $c): ?>
                        <option value="<?= (int) $c['id'] ?>" <?= ((int) ($filtro_cliente_id ?? 0) === (int) $c['id']) ? 'selected' : '' ?>>
                            <?= esc($c['nãome_razao']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-3">
                <label class="form-label small fw-bold">Ordem de Serviço</label>
                <input type="number" min="0" class="form-control" name="os_id" value="<?= esc((string) ($filtro_os_id ?? '')) ?>" placeholder="ID da OS">
            </div>
            <div class="col-lg-3">
                <label class="form-label small fw-bold">Tipo de Evento</label>
                <select class="form-select" name="tipo_evento">
                    <option value="">Todos os tipos</option>
                    <?php foreach (($tipos_evento ?? []) as $tipo): ?>
                        <option value="<?= esc($tipo) ?>" <?= (($filtro_tipo_evento ?? '') === $tipo) ? 'selected' : '' ?>>
                            <?= esc(ucfirst(str_replace('_', ' ', $tipo))) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-2 d-flex align-itemês-end">
                <button class="btn btn-glow w-100" type="submit">
                    <i class="bi bi-search me-1"></i>Filtrar
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card glass-card shadow-sm border-0">
    <div class="card-body">
        <h6 class="mb-4 text-primary fw-bold"><i class="bi bi-list-stars me-1"></i>Eventos Recentes</h6>
        
        <?php if (empty($eventos)): ?>
            <div class="text-center py-5">
                <i class="bi bi-calendar-x text-muted" style="font-size: 3rem; opacity: 0.3;"></i>
                <p class="text-muted mt-2">Nenhum evento encontrado para os filtros selecionados.</p>
            </div>
        <?php else: ?>
            <div class="timeline-container ps-4 border-start border-2 border-primary-subtle position-relative">
                <?php foreach ($eventos as $evento): 
                    $iconClass = 'bi-circle-fill';
                    $iconBg = 'bg-primary';
                    $tipo = (string)($evento['tipo_evento'] ?? 'geral');
                    
                    if (strpos($tipo, 'whatsapp') !== false) {
                        $iconClass = 'bi-whatsapp';
                        $iconBg = (strpos($tipo, 'recebida') !== false) ? 'bg-success' : 'bg-info';
                    } elseif (strpos($tipo, 'os_') !== false) {
                        $iconClass = 'bi-file-earmark-text';
                        $iconBg = 'bg-primary';
                    } elseif (strpos($tipo, 'conversa_') !== false) {
                        $iconClass = 'bi-chat-dots';
                        $iconBg = 'bg-secondary';
                    } elseif (strpos($tipo, 'orcamento') !== false) {
                        $iconClass = 'bi-currency-dollar';
                        $iconBg = 'bg-warning text-dark';
                    } elseif (strpos($tipo, 'followup') !== false) {
                        $iconClass = 'bi-megaphone';
                        $iconBg = 'bg-danger';
                    }
                ?>
                    <div class="timeline-item mb-4 position-relative">
                        <!-- Dot with Icon -->
                        <div class="position-absãolute start-0 translate-middle <?= $iconBg ?> rounded-circle shadow-sm d-flex align-itemês-center justify-content-center text-white" 
                             style="width: 28px; height: 28px; left: -21px !important; margin-top: 15px; z-index: 2;" title="<?= esc($tipo) ?>">
                            <i class="bi <?= $iconClass ?> small"></i>
                        </div>
                        
                        <div class="card border rounded-3 bg-light-subtle shadow-sm hover-elevate">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-itemês-start gap-3 mb-2">
                                    <div class="d-flex gap-2">
                                        <div>
                                            <h6 class="mb-0 fw-bold"><?= esc($evento['titulo'] ?? 'Evento CRM') ?></h6>
                                            <div class="small text-muted d-flex align-itemês-center flex-wrap gap-2 mt-1">
                                                <span class="badge bg-white text-dark border shadow-xs" style="font-size: 0.7rem;">
                                                    <?= esc(ucWords(str_replace('_', ' ', (string) ($evento['tipo_evento'] ?? 'Geral')))) ?>
                                                </span>
                                                <?php if (!empty($evento['cliente_nãome'])): ?>
                                                    <a href="<?= base_url('crm/timeline?cliente_id=' . (int)$evento['cliente_id']) ?>" class="text-decoration-nãone text-primary fw-medium" title="Ver timeline deste cliente">
                                                        <i class="bi bi-persãon me-1"></i><?= esc($evento['cliente_nãome']) ?>
                                                    </a>
                                                <?php endif; ?>
                                                <?php if (!empty($evento['numero_os'])): ?>
                                                    <a href="<?= base_url('os/visualizar/' . (int)$evento['os_id']) ?>" class="text-decoration-nãone badge bg-primary-subtle text-primary border border-primary-subtle">
                                                        #<?= esc($evento['numero_os']) ?>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-end shrink-0">
                                        <div class="text-primary fw-bold small"><?= date('d/m/Y H:i', strtotime($evento['data_evento'])) ?></div>
                                    </div>
                                </div>
                                
                                <?php if (!empty($evento['descricao'])): ?>
                                    <div class="p-2 rounded bg-white small border-dashed text-dark" style="border: 1px dashed #dee2e6;">
                                        <?= nl2br(esc($evento['descricao'])) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.hover-elevate { transition: transform 0.2s, box-shadow 0.2s; }
.hover-elevate:hover { transform: translateX(5px); box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.1) !important; }
.timeline-container { margin-left: 10px; }
</style>
<?= $this->endSection() ?>
