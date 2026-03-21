<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-header">
    <div class="d-flex align-items-center gap-3">
        <h2><i class="bi bi-chat-left-text me-2"></i>CRM - Interações</h2>
        <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('crm')">
            <i class="bi bi-question-circle me-1"></i>Ajuda
        </button>
    </div>
</div>

<div class="row g-3">
    <div class="col-xl-4">
        <div class="card glass-card shadow-sm border-0">
            <div class="card-body">
                <h6 class="mb-3 text-primary"><i class="bi bi-plus-circle me-1"></i>Nova interacao</h6>
                <form action="<?= base_url('crm/interacoes/salvar') ?>" method="post">
                    <?= csrf_field() ?>
                    <div class="mb-2">
                        <label class="form-label">Cliente *</label>
                        <select class="form-select select2-clientes" name="cliente_id" required>
                            <option value="">Selecione...</option>
                            <?php foreach (($clientes ?? []) as $c): ?>
                                <option value="<?= (int) $c['id'] ?>"><?= esc($c['nome_razao']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">OS (opcional)</label>
                        <select class="form-select select2-os" name="os_id">
                            <option value="">Sem vínculo</option>
                            <?php foreach (($osRecentes ?? []) as $os): ?>
                                <option value="<?= (int) $os['id'] ?>">#<?= esc($os['numero_os']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label">Tipo *</label>
                            <select class="form-select" name="tipo" required>
                                <option value="ligacao">Ligação</option>
                                <option value="whatsapp">WhatsApp</option>
                                <option value="email">E-mail</option>
                                <option value="presencial">Atendimento presencial</option>
                                <option value="nota_interna">Nota interna</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Canal *</label>
                            <select class="form-select" name="canal" required>
                                <option value="whatsapp">WhatsApp</option>
                                <option value="telefone">Telefone</option>
                                <option value="email">E-mail</option>
                                <option value="interno">Interno</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-2 mt-2">
                        <label class="form-label">Descrição *</label>
                        <textarea class="form-control" name="descricao" rows="4" required placeholder="O que foi conversado?"></textarea>
                    </div>
                    <button class="btn btn-glow w-100 mt-2" type="submit">
                        <i class="bi bi-check-circle me-1"></i>Registrar interação
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-8">
        <div class="card glass-card shadow-sm border-0">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h6 class="mb-0 text-primary fw-bold"><i class="bi bi-clock-history me-1"></i>Histórico de interações</h6>
                    <form class="d-flex gap-2" method="get" action="<?= base_url('crm/interacoes') ?>">
                        <select class="form-select form-select-sm select2-filtro" name="cliente_id" style="min-width:220px;">
                            <option value="">Todos os clientes</option>
                            <?php foreach (($clientes ?? []) as $c): ?>
                                <option value="<?= (int) $c['id'] ?>" <?= ((int) ($filtro_cliente_id ?? 0) === (int) $c['id']) ? 'selected' : '' ?>>
                                    <?= esc($c['nome_razao']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn btn-sm btn-primary" type="submit">
                            <i class="bi bi-filter"></i>
                        </button>
                    </form>
                </div>

                <?php if (empty($interacoes)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-chat-dots text-muted" style="font-size: 3rem; opacity: 0.3;"></i>
                        <p class="text-muted mt-2">Sem interações registradas para este filtro.</p>
                    </div>
                <?php else: ?>
                    <div class="timeline-interacoes">
                        <?php foreach ($interacoes as $it): ?>
                            <div class="interaction-card border rounded p-3 mb-3 bg-light-subtle shadow-sm">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h6 class="mb-0 fw-bold"><?= esc($it['cliente_nome'] ?? 'Cliente não vinculado') ?></h6>
                                        <div class="text-muted small">
                                            <i class="bi bi-person me-1"></i><?= esc($it['usuario_nome'] ?? 'Sistema') ?>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="badge bg-primary mb-1"><?= esc($it['tipo']) ?></div>
                                        <div class="small text-muted"><?= formatDate($it['data_interacao'], true) ?></div>
                                    </div>
                                </div>
                                
                                <div class="p-2 bg-white rounded border-start border-primary border-4 mb-2">
                                    <?= nl2br(esc($it['descricao'])) ?>
                                </div>

                                <div class="small d-flex gap-3">
                                    <span><i class="bi bi-share me-1 text-primary"></i> Canal: <strong><?= esc($it['canal']) ?></strong></span>
                                    <?php if (!empty($it['numero_os'])): ?>
                                        <a href="<?= base_url('os/visualizar/'.$it['os_id']) ?>" class="text-decoration-none">
                                            <i class="bi bi-stickies me-1 text-primary"></i> OS: <strong>#<?= esc($it['numero_os']) ?></strong>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.interaction-card { transition: transform 0.2s; }
.interaction-card:hover { transform: translateY(-2px); border-color: var(--bs-primary) !important; }
</style>
<?= $this->endSection() ?>
