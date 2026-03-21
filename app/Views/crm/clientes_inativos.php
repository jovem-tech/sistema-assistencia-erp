<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-header">
    <div class="d-flex align-items-center gap-3">
        <h2><i class="bi bi-person-x me-2"></i>CRM - Clientes Inativos</h2>
        <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('crm-clientes-inativos')">
            <i class="bi bi-question-circle me-1"></i>Ajuda
        </button>
    </div>
</div>

<div class="card glass-card mb-3">
    <div class="card-body">
        <form method="get" action="<?= base_url('crm/clientes-inativos') ?>" class="row g-2 align-items-end">
            <div class="col-12 col-md-4 col-lg-3">
                <label class="form-label mb-1">Dias sem OS</label>
                <input type="number" min="30" class="form-control" name="dias" value="<?= esc((string) ($dias ?? 180)) ?>">
            </div>
            <div class="col-12 col-md-3 col-lg-2">
                <button class="btn btn-glow w-100" type="submit"><i class="bi bi-funnel me-1"></i>Aplicar</button>
            </div>
        </form>
    </div>
</div>

<div class="card glass-card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Telefone</th>
                        <th>Email</th>
                        <th>Ultima OS</th>
                        <th>Total OS</th>
                        <th class="text-end">Acoes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($clientes ?? [])): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Nenhum cliente inativo encontrado para o periodo selecionado.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach (($clientes ?? []) as $c): ?>
                            <tr>
                                <td class="fw-semibold"><?= esc($c['nome_razao'] ?? '-') ?></td>
                                <td><?= esc($c['telefone1'] ?? '-') ?></td>
                                <td><?= esc($c['email'] ?? '-') ?></td>
                                <td>
                                    <?php if (!empty($c['ultima_os_em'])): ?>
                                        <?= esc(date('d/m/Y H:i', strtotime((string) $c['ultima_os_em']))) ?>
                                    <?php else: ?>
                                        <span class="text-muted">Sem OS</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= (int) ($c['total_os'] ?? 0) ?></td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        <a href="<?= base_url('clientes/visualizar/' . (int) $c['id']) ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-person-badge"></i>
                                        </a>
                                        <form action="<?= base_url('crm/clientes-inativos/followup') ?>" method="post" class="d-inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="cliente_id" value="<?= (int) $c['id'] ?>">
                                            <input type="hidden" name="dias" value="<?= esc((string) ($dias ?? 180)) ?>">
                                            <button class="btn btn-sm btn-outline-success" type="submit" title="Criar follow-up de reativacao">
                                                <i class="bi bi-calendar-plus"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
