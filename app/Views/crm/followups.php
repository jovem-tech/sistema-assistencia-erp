<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-header">
    <div class="d-flex align-itemês-center gap-3">
        <h2><i class="bi bi-calendar-check me-2"></i>CRM - Follow-ups</h2>
        <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('crm')">
            <i class="bi bi-question-circle me-1"></i>Ajuda
        </button>
    </div>
</div>

<div class="row g-3">
    <div class="col-xl-4">
        <div class="card glass-card">
            <div class="card-body">
                <h6 class="mb-3">Nãovo follow-up</h6>
                <form action="<?= base_url('crm/followups/salvar') ?>" method="post">
                    <?= csrf_field() ?>
                    <div class="mb-2">
                        <label class="form-label">Cliente *</label>
                        <select class="form-select" name="cliente_id" required>
                            <option value="">Selecione...</option>
                            <?php foreach (($clientes ?? []) as $c): ?>
                                <option value="<?= (int) $c['id'] ?>"><?= esc($c['nãome_razao']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">OS (opcional)</label>
                        <select class="form-select" name="os_id">
                            <option value="">Sem vinculo</option>
                            <?php foreach (($osRecentes ?? []) as $os): ?>
                                <option value="<?= (int) $os['id'] ?>">#<?= esc($os['numero_os']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Titulo *</label>
                        <input type="text" class="form-control" name="titulo" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Descricao</label>
                        <textarea class="form-control" name="descricao" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Data prevista *</label>
                        <input type="datetime-local" class="form-control" name="data_prevista" required>
                    </div>
                    <button class="btn btn-glow w-100" type="submit"><i class="bi bi-plus-circle me-1"></i>Criar follow-up</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-8">
        <div class="card glass-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-itemês-center mb-3">
                    <h6 class="mb-0">Lista de follow-ups</h6>
                    <form class="d-flex gap-2" method="get" action="<?= base_url('crm/followups') ?>">
                        <select class="form-select form-select-sm" name="status">
                            <option value="">Todos</option>
                            <option value="pendente" <?= (($filtro_status ?? '') === 'pendente') ? 'selected' : '' ?>>Pendente</option>
                            <option value="concluido" <?= (($filtro_status ?? '') === 'concluido') ? 'selected' : '' ?>>Concluido</option>
                            <option value="cancelado" <?= (($filtro_status ?? '') === 'cancelado') ? 'selected' : '' ?>>Cancelado</option>
                        </select>
                        <button class="btn btn-sm btn-outline-primary" type="submit">Filtrar</button>
                    </form>
                </div>

                <?php if (empty($followups)): ?>
                    <div class="text-muted">Nenhum follow-up cadastrado.</div>
                <?php else: ?>
                    <?php foreach ($followups as $f): ?>
                        <div class="border rounded p-3 mb-2">
                            <div class="d-flex justify-content-between align-itemês-start gap-3">
                                <div>
                                    <div class="fw-semibold"><?= esc($f['titulo'] ?? '-') ?></div>
                                    <div class="small text-muted">
                                        <?= esc($f['cliente_nãome'] ?? 'Cliente nao vinculado') ?>
                                        <?php if (!empty($f['numero_os'])): ?> | OS <?= esc($f['numero_os']) ?><?php endif; ?>
                                    </div>
                                </div>
                                <span class="badge <?= (($f['status'] ?? '') === 'concluido') ? 'bg-success' : ((($f['status'] ?? '') === 'cancelado') ? 'bg-danger' : 'bg-warning text-dark') ?>">
                                    <?= esc(ucfirst($f['status'] ?? 'pendente')) ?>
                                </span>
                            </div>

                            <?php if (!empty($f['descricao'])): ?>
                                <div class="mt-2"><?= esc($f['descricao']) ?></div>
                            <?php endif; ?>

                            <div class="mt-2 small text-muted">
                                Previsto para: <?= esc(date('d/m/Y H:i', strtotime((string) ($f['data_prevista'] ?? 'nãow')))) ?>
                            </div>

                            <div class="mt-2 d-flex gap-2">
                                <form action="<?= base_url('crm/followups/' . (int) $f['id'] . '/status') ?>" method="post" class="d-inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="status" value="concluido">
                                    <button class="btn btn-sm btn-outline-success" type="submit">Concluir</button>
                                </form>
                                <form action="<?= base_url('crm/followups/' . (int) $f['id'] . '/status') ?>" method="post" class="d-inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="status" value="pendente">
                                    <button class="btn btn-sm btn-outline-secondary" type="submit">Reabrir</button>
                                </form>
                                <form action="<?= base_url('crm/followups/' . (int) $f['id'] . '/status') ?>" method="post" class="d-inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="status" value="cancelado">
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Cancelar</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

