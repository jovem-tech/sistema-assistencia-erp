<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$statusLabels = $statusLabels ?? [];
$tipoLabels = $tipoLabels ?? [];
$resumo = $resumo ?? [];
$statusBadgeMap = [
    'rascunho' => 'bg-secondary',
    'pendente_envio' => 'bg-secondary-subtle text-secondary-emphasis',
    'enviado' => 'bg-primary',
    'aguardando_resposta' => 'bg-info text-dark',
    'aguardando_pacote' => 'bg-primary-subtle text-primary-emphasis',
    'pacote_aprovado' => 'bg-success-subtle text-success-emphasis',
    'pendente' => 'bg-warning text-dark',
    'aprovado' => 'bg-success',
    'pendente_abertura_os' => 'bg-warning text-dark',
    'rejeitado' => 'bg-danger',
    'vencido' => 'bg-warning text-dark',
    'cancelado' => 'bg-dark',
    'convertido' => 'bg-success',
];
?>

<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h2 class="mb-0"><i class="bi bi-receipt-cutoff me-2"></i>Orçamentos</h2>
    <div class="d-flex gap-2 flex-wrap">
        <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('orcamentos')" title="Ajuda sobre Orçamentos">
            <i class="bi bi-question-circle me-1"></i>Ajuda
        </button>
        <?php if (can('orcamentos', 'criar')): ?>
            <a href="<?= base_url('orcamentos/novo') ?>" class="btn btn-primary btn-glow">
                <i class="bi bi-plus-lg me-1"></i>Novo orçamento rápido
            </a>
        <?php endif; ?>
        <?php if (can('orcamentos', 'editar')): ?>
            <form method="POST" action="<?= base_url('orcamentos/automação/executar') ?>" class="d-inline-flex" data-orc-run-automation>
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-repeat me-1"></i>Executar automação
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="row g-3 mb-4">
    <?php foreach ($statusLabels as $statusCode => $statusName): ?>
        <div class="col-6 col-md-3">
            <div class="card glass-card h-100">
                <div class="card-body py-3">
                    <div class="small text-muted"><?= esc($statusName) ?></div>
                    <div class="fs-4 fw-semibold"><?= (int) ($resumo[$statusCode] ?? 0) ?></div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="card glass-card mb-4">
    <div class="card-body">
        <form method="GET" action="<?= base_url('orcamentos') ?>">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-md-4">
                    <label for="filtroStatus" class="form-label">Status</label>
                    <select id="filtroStatus" name="status" class="form-select">
                        <option value="">Todos</option>
                        <?php foreach ($statusLabels as $statusCode => $statusName): ?>
                            <option value="<?= esc($statusCode) ?>" <?= ($statusFilter ?? '') === $statusCode ? 'selected' : '' ?>>
                                <?= esc($statusName) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label for="filtroTipo" class="form-label">Tipo</label>
                    <select id="filtroTipo" name="tipo" class="form-select">
                        <option value="">Todos</option>
                        <?php foreach ($tipoLabels as $tipoCode => $tipoName): ?>
                            <option value="<?= esc($tipoCode) ?>" <?= ($tipoFilter ?? '') === $tipoCode ? 'selected' : '' ?>>
                                <?= esc($tipoName) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label for="filtroQ" class="form-label">Busca</label>
                    <input id="filtroQ" name="q" type="text" class="form-control" value="<?= esc($q ?? '') ?>" placeholder="Número, cliente, cliente avulso ou OS">
                </div>
                <div class="col-12 col-md-2 d-grid">
                    <button type="submit" class="btn btn-glow">Filtrar</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card glass-card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover mb-0 orcamentos-table">
                <thead>
                    <tr>
                        <th>Número</th>
                        <th>Cliente</th>
                        <th>Tipo</th>
                        <th>Origem</th>
                        <th>Vinculos</th>
                        <th>Status</th>
                        <th>Validade</th>
                        <th>Total</th>
                        <th class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($orcamentos ?? [])): ?>
                    <tr>
                    <td colspan="9" class="text-center text-muted py-4">Nenhum orçamento encontrado.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach (($orcamentos ?? []) as $orcamento): ?>
                        <?php
                        $status = (string) ($orcamento['status'] ?? 'rascunho');
                        $statusClass = $statusBadgeMap[$status] ?? 'bg-secondary';
                        $tipoCode = (string) ($orcamento['tipo_orcamento'] ?? 'previo');
                        $clienteNome = trim((string) ($orcamento['cliente_nome'] ?? ''));
                        if ($clienteNome === '') {
                            $clienteNome = (string) ($orcamento['cliente_nome_avulso'] ?? 'Cliente eventual');
                        }
                        $vinculos = [];
                        if (!empty($orcamento['os_id']) && !empty($orcamento['numero_os'])) {
                            $vinculos[] = 'OS ' . $orcamento['numero_os'];
                        }
                        if (!empty($orcamento['conversa_id'])) {
                            $vinculos[] = 'Conversa #' . $orcamento['conversa_id'];
                        }
                        if (!empty($orcamento['equipamento_id'])) {
                            $vinculos[] = 'Equipamento #' . $orcamento['equipamento_id'];
                        }
                        ?>
                        <tr>
                            <td data-label="Número" class="fw-semibold"><?= esc((string) ($orcamento['numero'] ?? '#')) ?></td>
                            <td data-label="Cliente">
                                <div class="fw-semibold"><?= esc($clienteNome) ?></div>
                                <?php if (!empty($orcamento['telefone_contato'])): ?>
                                    <div class="small text-muted"><?= esc((string) $orcamento['telefone_contato']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td data-label="Tipo">
                                <span class="badge text-bg-light border"><?= esc($tipoLabels[$tipoCode] ?? ucfirst($tipoCode)) ?></span>
                            </td>
                            <td data-label="Origem"><?= esc(ucfirst(str_replace('_', ' ', (string) ($orcamento['origem'] ?? 'manual')))) ?></td>
                            <td data-label="Vinculos" class="small text-muted">
                                <?= !empty($vinculos) ? esc(implode(' | ', $vinculos)) : '-' ?>
                            </td>
                            <td data-label="Status">
                                <span class="badge <?= esc($statusClass) ?>"><?= esc($statusLabels[$status] ?? ucfirst($status)) ?></span>
                            </td>
                            <td data-label="Validade"><?= esc(formatDate($orcamento['validade_data'] ?? null)) ?></td>
                            <td data-label="Total" class="fw-semibold"><?= esc(formatMoney($orcamento['total'] ?? 0)) ?></td>
                            <td data-label="Ações" class="text-center">
                                <div class="d-flex justify-content-center gap-1 flex-wrap">
                                    <a href="<?= base_url('orcamentos/visualizar/' . (int) $orcamento['id']) ?>" class="btn btn-sm btn-outline-primary" title="Visualizar">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <?php if (can('orcamentos', 'editar')): ?>
                                        <a href="<?= base_url('orcamentos/editar/' . (int) $orcamento['id']) ?>" class="btn btn-sm btn-outline-secondary" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    <?php endif; ?>
                                    <?php if (can('orcamentos', 'excluir')): ?>
                    <a href="<?= base_url('orcamentos/excluir/' . (int) $orcamento['id']) ?>" class="btn btn-sm btn-outline-danger btn-delete" data-nome="<?= esc((string) ($orcamento['numero'] ?? 'orçamento')) ?>" title="Excluir">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    <?php endif; ?>
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
@media (max-width: 430px) {
    .orcamentos-table thead {
        display: none;
    }
    .orcamentos-table tbody tr {
        display: block;
        margin-bottom: .75rem;
        border: 1px solid rgba(0, 0, 0, .08);
        border-radius: .75rem;
        overflow: hidden;
    }
    .orcamentos-table tbody td {
        display: flex;
        justify-content: space-between;
        gap: .75rem;
        align-items: flex-start;
        border: 0;
        border-bottom: 1px solid rgba(0, 0, 0, .06);
        padding: .65rem .75rem;
        white-space: normal;
    }
    .orcamentos-table tbody td::before {
        content: attr(data-label);
        font-weight: 600;
        color: #6c757d;
        min-width: 88px;
    }
    .orcamentos-table tbody td:last-child {
        border-bottom: 0;
    }
}
@media (max-width: 390px) {
    .orcamentos-table tbody td::before {
        min-width: 80px;
        font-size: .78rem;
    }
}
@media (max-width: 360px) {
    .orcamentos-table tbody td {
        padding: .55rem .6rem;
        font-size: .85rem;
    }
}
@media (max-width: 320px) {
    .orcamentos-table tbody td {
        padding: .5rem .5rem;
        gap: .5rem;
    }
    .orcamentos-table tbody td::before {
        min-width: 74px;
        font-size: .74rem;
    }
}
</style>
<script>
(function () {
    const form = document.querySelector('form[data-orc-run-automation]');
    if (!form) {
        return;
    }
    form.addEventListener('submit', async (event) => {
        if (form.dataset.confirmed === '1') {
            return;
        }
        event.preventDefault();
        const confirmed = await window.DSFeedback.confirm({
            icon: 'question',
                    title: 'Executar automação agora?',
                    text: 'Esta ação processa vencimentos e follow-ups pendentes de orçamentos.',
            showCancelButton: true,
            confirmButtonText: 'Executar',
            cancelButtonText: 'Cancelar',
        });
        if (!confirmed) {
            return;
        }
        form.dataset.confirmed = '1';
        form.submit();
    });
})();
</script>
<?= $this->endSection() ?>


