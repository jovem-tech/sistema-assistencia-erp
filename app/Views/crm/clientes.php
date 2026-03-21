<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header d-flex justify-content-between align-itemês-center mb-4">
    <div class="d-flex align-itemês-center gap-3">
        <h2 class="mb-0"><i class="bi bi-people-fill me-2"></i>CRM - Gestão de Clientes</h2>
        <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('crm')">
            <i class="bi bi-question-circle me-1"></i> Ajuda
        </button>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Filtros Rápidos -->
    <div class="col-12">
        <div class="card glass-card shadow-sm border-0">
            <div class="card-body">
                <form action="" method="GET" class="row g-3 align-itemês-end">
                    <div class="col-12 col-md-5">
                        <label class="form-label fw-bold small">Pesquisar Cliente</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" name="q" class="form-control border-start-0 ps-0" placeholder="Nãome, Telefone ou CPF/CNPJ..." value="<?= esc($filtro_q ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label fw-bold small">Status de Engajamento</label>
                        <select name="status" class="form-select">
                            <option value="">Todos os Status</option>
                            <option value="ativo" <?= ($filtro_status === 'ativo') ? 'selected' : '' ?>>Ativo (Recentemente)</option>
                            <option value="em_risco" <?= ($filtro_status === 'em_risco') ? 'selected' : '' ?>>Em Risco (30+ dias)</option>
                            <option value="inativo" <?= ($filtro_status === 'inativo') ? 'selected' : '' ?>>Inativo (90+ dias)</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-2">
                        <button type="submit" class="btn btn-primary w-100 btn-glow h-100 d-flex align-itemês-center justify-content-center">
                            <i class="bi bi-filter me-2"></i>Filtrar
                        </button>
                    </div>
                    <div class="col-12 col-md-2">
                         <a href="<?= base_url('crm/clientes') ?>" class="btn btn-outline-secondary w-100 h-100 d-flex align-itemês-center justify-content-center">
                            <i class="bi bi-x-circle me-2"></i>Limpar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card glass-card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light-subtle">
                    <tr>
                        <th class="ps-4 py-3">Cliente</th>
                        <th class="py-3">Contato</th>
                        <th class="py-3">Última Interação</th>
                        <th class="py-3 text-center">Status</th>
                        <th class="py-3 text-end pe-4">Ações</th>
                    </tr>
                </thead>
                <tbody class="border-top-0">
                    <?php if (empty($clientes)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="bi bi-search fs-1 mb-3 d-block opacity-25"></i>
                                    Nenhum cliente encontrado com os filtros selecionados.
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($clientes as $c): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-itemês-center">
                                        <div class="avatar-circle me-3">
                                            <?= strtoupper(substr($c['nãome_razao'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold"><?= esc($c['nãome_razao']) ?></div>
                                            <small class="text-muted"><?= esc($c['cpf_cnpj'] ?? 'Sem documento') ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="small"><i class="bi bi-whatsapp me-2 text-success"></i><?= esc($c['telefone1']) ?></span>
                                        <span class="small text-muted text-truncate" style="max-width: 150px;"><?= esc($c['email'] ?? 'Sem e-mail') ?></span>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($c['ultima_interacao']): ?>
                                        <div class="small fw-semibold"><?= date('d/m/Y', strtotime($c['ultima_interacao'])) ?></div>
                                        <small class="text-muted text-uppercase" style="font-size: 0.65rem;"><?= esc($c['ultima_interacao_tipo'] ?? 'contato') ?></small>
                                    <?php else: ?>
                                        <span class="text-muted small italic">Sem registros</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php 
                                        $dias = (isset($c['ultima_interacao'])) ? (int)((time() - strtotime($c['ultima_interacao'])) / 86400) : 999;
                                        if ($dias <= 30) {
                                            echo '<span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill">Ativo</span>';
                                        } elseif ($dias <= 90) {
                                            echo '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-3 py-2 rounded-pill">Em Risco</span>';
                                        } else {
                                            echo '<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 rounded-pill">Inativo</span>';
                                        }
                                    ?>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="<?= base_url('clientes/visualizar/' . $c['id']) ?>" class="btn btn-sm btn-light border" title="Ver Detalhes">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="<?= base_url('crm/interacoes?cliente_id=' . $c['id']) ?>" class="btn btn-sm btn-primary-light" title="Registrar Interação">
                                            <i class="bi bi-chat-dots-fill"></i>
                                        </a>
                                        <a href="<?= base_url('crm/timeline?cliente_id=' . $c['id']) ?>" class="btn btn-sm btn-secondary-light" title="Linha do Tempo">
                                            <i class="bi bi-clock-history"></i>
                                        </a>
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

<style>
.avatar-circle {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #6366f1, #4f46e5);
    color: white;
    border-radius: 50%;
    display: flex;
    align-itemês: center;
    justify-content: center;
    font-weight: 700;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}
.btn-primary-light {
    background: #eef2ff;
    color: #4f46e5;
    border: 1px sãolid #e0e7ff;
}
.btn-primary-light:hover {
    background: #4f46e5;
    color: white;
}
.btn-secondary-light {
    background: #f8fafc;
    color: #64748b;
    border: 1px sãolid #e2e8f0;
}
.btn-secondary-light:hover {
    background: #64748b;
    color: white;
}
</style>

<?= $this->endSection() ?>
