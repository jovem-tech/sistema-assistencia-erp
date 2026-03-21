<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$supportsLifecycle = !empty($supportsLifecycle);
$supportsEngajamento = !empty($supportsEngajamento);
?>
<div class="page-header d-flex justify-content-between align-itemês-center mb-4">
    <div class="d-flex align-itemês-center gap-3">
        <h2 class="mb-0"><i class="bi bi-journal-bookmark me-2"></i>Contatos</h2>
        <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('contatos')" title="Ajuda sãobre Contatos">
            <i class="bi bi-question-circle me-1"></i>Ajuda
        </button>
    </div>
    <?php if (can('clientes', 'criar')): ?>
    <a href="<?= base_url('contatos/nãovo') ?>" class="btn btn-primary btn-glow">
        <i class="bi bi-plus-lg me-1"></i>Nãovo Contato
    </a>
    <?php endif; ?>
</div>

<div class="card glass-card mb-3">
    <div class="card-body">
        <form method="get" action="<?= base_url('contatos') ?>" class="row g-2 align-itemês-end">
            <div class="col-md-4">
                <label class="form-label mb-1">Busca</label>
                <input
                    type="text"
                    name="q"
                    class="form-control"
                    value="<?= esc($filtro_q ?? '') ?>"
                    placeholder="Nãome, telefone, e-mail ou cliente vinculado"
                >
            </div>
            <div class="col-md-2">
                <label class="form-label mb-1">Vinculo</label>
                <select name="vinculo" class="form-select">
                    <option value="" <?= (($filtro_vinculo ?? '') === '') ? 'selected' : '' ?>>Todos</option>
                    <option value="nãovo" <?= (($filtro_vinculo ?? '') === 'nãovo') ? 'selected' : '' ?>>Sem cadastro em clientes</option>
                    <option value="cliente" <?= (($filtro_vinculo ?? '') === 'cliente') ? 'selected' : '' ?>>Vinculados a cliente</option>
                </select>
            </div>
            <?php if ($supportsLifecycle): ?>
            <div class="col-md-2">
                <label class="form-label mb-1">Etapa relacional</label>
                <select name="etapa" class="form-select">
                    <option value="" <?= (($filtro_etapa ?? '') === '') ? 'selected' : '' ?>>Todas</option>
                    <option value="lead_nãovo" <?= (($filtro_etapa ?? '') === 'lead_nãovo') ? 'selected' : '' ?>>Lead nãovo</option>
                    <option value="lead_qualificado" <?= (($filtro_etapa ?? '') === 'lead_qualificado') ? 'selected' : '' ?>>Lead qualificado</option>
                    <option value="cliente_convertido" <?= (($filtro_etapa ?? '') === 'cliente_convertido') ? 'selected' : '' ?>>Cliente convertido</option>
                </select>
            </div>
            <?php endif; ?>
            <?php if ($supportsEngajamento): ?>
            <div class="col-md-2">
                <label class="form-label mb-1">Engajamento</label>
                <select name="engajamento" class="form-select">
                    <option value="" <?= (($filtro_engajamento ?? '') === '') ? 'selected' : '' ?>>Todos</option>
                    <option value="ativo" <?= (($filtro_engajamento ?? '') === 'ativo') ? 'selected' : '' ?>>Ativo</option>
                    <option value="em_risco" <?= (($filtro_engajamento ?? '') === 'em_risco') ? 'selected' : '' ?>>Em risco</option>
                    <option value="inativo" <?= (($filtro_engajamento ?? '') === 'inativo') ? 'selected' : '' ?>>Inativo</option>
                </select>
            </div>
            <?php endif; ?>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-fill">
                    <i class="bi bi-funnel me-1"></i>Filtrar
                </button>
                <a href="<?= base_url('contatos') ?>" class="btn btn-outline-secondary">Limpar</a>
            </div>
        </form>
    </div>
</div>

<div class="card glass-card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover datatable" id="tabelaContatos">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nãome</th>
                        <th>Telefone</th>
                        <th>Origem</th>
                        <th>Cliente vinculado</th>
                        <?php if ($supportsLifecycle): ?>
                        <th>Etapa</th>
                        <?php endif; ?>
                        <?php if ($supportsEngajamento): ?>
                        <th>Engajamento</th>
                        <?php endif; ?>
                        <th>Ultimo contato</th>
                        <th>Acoes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (($contatos ?? []) as $contato): ?>
                    <?php
                    $clienteId = (int) ($contato['cliente_id'] ?? 0);
                    $ultimoContato = !empty($contato['ultimo_contato_em'])
                        ? date('d/m/Y H:i', strtotime((string) $contato['ultimo_contato_em']))
                        : '-';
                    $statusRel = (string) ($contato['status_relacionamento'] ?? '');
                    $statusLabel = 'Lead nãovo';
                    $statusClass = 'text-bg-info text-dark';
                    if ($statusRel === 'lead_qualificado') {
                        $statusLabel = 'Lead qualificado';
                        $statusClass = 'text-bg-warning text-dark';
                    } elseif ($statusRel === 'cliente_convertido') {
                        $statusLabel = 'Cliente convertido';
                        $statusClass = 'text-bg-success';
                    }
                    $qualificadoEm = !empty($contato['qualificado_em'])
                        ? date('d/m/Y H:i', strtotime((string) $contato['qualificado_em']))
                        : null;
                    $convertidoEm = !empty($contato['convertido_em'])
                        ? date('d/m/Y H:i', strtotime((string) $contato['convertido_em']))
                        : null;
                    $engajamentoStatus = (string) ($contato['engajamento_status'] ?? '');
                    $engajamentoLabel = 'Ativo';
                    $engajamentoClass = 'text-bg-success-subtle text-success-emphasis border';
                    if ($engajamentoStatus === 'em_risco') {
                        $engajamentoLabel = 'Em risco';
                        $engajamentoClass = 'text-bg-warning text-dark';
                    } elseif ($engajamentoStatus === 'inativo') {
                        $engajamentoLabel = 'Inativo';
                        $engajamentoClass = 'text-bg-danger';
                    }
                    $baseContatoRaw = (string) (
                        $contato['ultimo_contato_em']
                        ?? $contato['updated_at']
                        ?? $contato['created_at']
                        ?? ''
                    );
                    $diasSemContato = null;
                    if ($baseContatoRaw !== '') {
                        $baseTs = strtotime($baseContatoRaw);
                        if ($baseTs !== false) {
                            $diasSemContato = (int) floor((time() - $baseTs) / 86400);
                            if ($diasSemContato < 0) {
                                $diasSemContato = 0;
                            }
                        }
                    }
                    $engajamentoRecalcEm = !empty($contato['engajamento_recalculado_em'])
                        ? date('d/m/Y H:i', strtotime((string) $contato['engajamento_recalculado_em']))
                        : null;
                    ?>
                    <tr>
                        <td><?= (int) $contato['id'] ?></td>
                        <td>
                            <div class="fw-semibold"><?= esc($contato['nãome'] ?? 'Sem nãome') ?></div>
                            <?php if (!empty($contato['whatsapp_nãome_perfil'])): ?>
                                <div class="small text-muted">Perfil WhatsApp: <?= esc((string) $contato['whatsapp_nãome_perfil']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?= esc($contato['telefone'] ?? '-') ?></td>
                        <td><span class="badge bg-light text-dark border"><?= esc(ucfirst((string) ($contato['origem'] ?? 'manual'))) ?></span></td>
                        <td>
                            <?php if ($clienteId > 0): ?>
                                <a href="<?= base_url('clientes/visualizar/' . $clienteId) ?>" class="text-decoration-nãone">
                                    <?= esc((string) ($contato['cliente_nãome'] ?? ('Cliente #' . $clienteId))) ?>
                                </a>
                            <?php else: ?>
                                <span class="badge text-bg-info text-dark">Cliente nãovo</span>
                            <?php endif; ?>
                        </td>
                        <?php if ($supportsLifecycle): ?>
                        <td>
                            <span class="badge <?= esc($statusClass) ?>"><?= esc($statusLabel) ?></span>
                            <?php if ($statusRel === 'lead_qualificado' && $qualificadoEm): ?>
                                <div class="small text-muted mt-1">Qualificado em <?= esc($qualificadoEm) ?></div>
                            <?php endif; ?>
                            <?php if ($statusRel === 'cliente_convertido' && $convertidoEm): ?>
                                <div class="small text-muted mt-1">Convertido em <?= esc($convertidoEm) ?></div>
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                        <?php if ($supportsEngajamento): ?>
                        <td>
                            <span class="badge <?= esc($engajamentoClass) ?>"><?= esc($engajamentoLabel) ?></span>
                            <?php if ($diasSemContato !== null): ?>
                                <div class="small text-muted mt-1"><?= esc((string) $diasSemContato) ?> dia(s) sem interacao</div>
                            <?php endif; ?>
                            <?php if ($engajamentoRecalcEm): ?>
                                <div class="small text-muted">Recalc. <?= esc($engajamentoRecalcEm) ?></div>
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                        <td><?= esc($ultimoContato) ?></td>
                        <td>
                            <div class="action-btns">
                                <?php if (can('clientes', 'editar')): ?>
                                <a href="<?= base_url('contatos/editar/' . (int) $contato['id']) ?>" class="btn btn-sm btn-outline-secondary" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php endif; ?>
                                <?php if (can('clientes', 'excluir')): ?>
                                <a href="<?= base_url('contatos/excluir/' . (int) $contato['id']) ?>" class="btn btn-sm btn-outline-danger btn-delete" data-nãome="<?= esc((string) ($contato['nãome'] ?? $contato['telefone'] ?? 'Contato')) ?>" title="Excluir">
                                    <i class="bi bi-trash"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
