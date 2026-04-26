<?php
$os = is_array($os ?? null) ? $os : [];
$orcamentoVinculado = is_array($orcamentoVinculado ?? null) ? $orcamentoVinculado : null;
$orcamentoItensResumo = is_array($orcamentoItensResumo ?? null)
    ? $orcamentoItensResumo
    : ['items' => [], 'groups' => [], 'total_items' => 0, 'total_quantity' => 0.0];
$orcamentoStatusLabels = is_array($orcamentoStatusLabels ?? null) ? $orcamentoStatusLabels : [];
$orcamentoTipoLabels = is_array($orcamentoTipoLabels ?? null) ? $orcamentoTipoLabels : [];
$orcamentoContext = trim((string) ($orcamentoContext ?? 'editor'));
$isStatusModalContext = $orcamentoContext === 'status_modal';

$osId = (int) ($os['id'] ?? 0);
$hasOrcamentoVinculado = !empty($orcamentoVinculado['id']);
$hasItensOrcamento = !empty($orcamentoItensResumo['items']);
$canCreateOrcamento = can('orcamentos', 'criar');
$canEditOrcamento = can('orcamentos', 'editar');
$canViewOrcamento = can('orcamentos', 'visualizar');
$orcamentoLocked = (bool) ($orcamentoVinculado['is_locked'] ?? false);

$orcamentoCreateParams = [
    'origem' => 'os',
    'os_id' => $osId,
    'cliente_id' => (int) ($os['cliente_id'] ?? 0),
    'equipamento_id' => (int) ($os['equipamento_id'] ?? 0),
    'telefone' => (string) ($os['cliente_telefone'] ?? ''),
    'email' => (string) ($os['cliente_email'] ?? ''),
];
$orcamentoCreateUrl = base_url('orcamentos/novo?' . http_build_query($orcamentoCreateParams));
$orcamentoCreateModalUrl = base_url('orcamentos/novo?' . http_build_query(array_merge($orcamentoCreateParams, ['embed' => 1])));
$orcamentoEditUrl = $hasOrcamentoVinculado ? base_url('orcamentos/editar/' . (int) ($orcamentoVinculado['id'] ?? 0)) : '';
$orcamentoEditModalUrl = $orcamentoEditUrl !== '' ? ($orcamentoEditUrl . '?embed=1') : '';
$orcamentoViewUrl = $hasOrcamentoVinculado ? base_url('orcamentos/visualizar/' . (int) ($orcamentoVinculado['id'] ?? 0)) : '';
$orcamentoViewModalUrl = $orcamentoViewUrl !== '' ? ($orcamentoViewUrl . '?embed=1') : '';

$orcamentoStatus = trim((string) ($orcamentoVinculado['status'] ?? ''));
$orcamentoStatusLabel = $orcamentoStatus !== ''
    ? ($orcamentoStatusLabels[$orcamentoStatus] ?? ucfirst(str_replace('_', ' ', $orcamentoStatus)))
    : '';
$orcamentoTipo = trim((string) ($orcamentoVinculado['tipo_orcamento'] ?? ''));
$orcamentoTipoLabel = $orcamentoTipo !== ''
    ? ($orcamentoTipoLabels[$orcamentoTipo] ?? ucfirst(str_replace('_', ' ', $orcamentoTipo)))
    : '';

$orcamentoActionLabel = '';
$orcamentoActionModalUrl = '';
$orcamentoActionUrl = '';
$orcamentoActionTitle = '';
$orcamentoActionButtonClass = 'btn btn-primary btn-sm';
$orcamentoActionIcon = 'bi bi-receipt-cutoff';
$editButtonTitle = $orcamentoLocked
    ? 'Abrir a tela de edicao do orcamento. Se o status atual bloquear edicao direta, o modulo orienta a revisao adequada.'
    : 'Editar o orcamento vinculado a esta OS sem sair da tela.';

if ($hasItensOrcamento) {
    if ($hasOrcamentoVinculado && $canEditOrcamento && !$orcamentoLocked) {
        $orcamentoActionLabel = 'Editar orcamento';
        $orcamentoActionModalUrl = $orcamentoEditModalUrl;
        $orcamentoActionUrl = $orcamentoEditUrl;
        $orcamentoActionTitle = 'Editar o orcamento vinculado a esta OS sem sair da tela.';
        $orcamentoActionButtonClass = 'btn btn-primary btn-sm';
        $orcamentoActionIcon = 'bi bi-pencil-square';
    } elseif ($hasOrcamentoVinculado && $canViewOrcamento) {
        $orcamentoActionLabel = 'Visualizar orcamento';
        $orcamentoActionModalUrl = $orcamentoViewModalUrl;
        $orcamentoActionUrl = $orcamentoViewUrl;
        $orcamentoActionTitle = 'Visualizar o orcamento vinculado a esta OS.';
        $orcamentoActionButtonClass = 'btn btn-outline-primary btn-sm';
        $orcamentoActionIcon = 'bi bi-eye';
    }
} else {
    if ($hasOrcamentoVinculado && $canEditOrcamento && !$orcamentoLocked) {
        $orcamentoActionLabel = 'Lancar itens no orcamento';
        $orcamentoActionModalUrl = $orcamentoEditModalUrl;
        $orcamentoActionUrl = $orcamentoEditUrl;
        $orcamentoActionTitle = 'Abrir o orcamento vinculado para inserir pecas, servicos e pacotes.';
        $orcamentoActionButtonClass = 'btn btn-primary btn-sm';
        $orcamentoActionIcon = 'bi bi-plus-circle';
    } elseif (!$hasOrcamentoVinculado && $canCreateOrcamento) {
        $orcamentoActionLabel = 'Criar orcamento';
        $orcamentoActionModalUrl = $orcamentoCreateModalUrl;
        $orcamentoActionUrl = $orcamentoCreateUrl;
        $orcamentoActionTitle = 'Abrir um novo orcamento ja vinculado a esta OS.';
        $orcamentoActionButtonClass = 'btn btn-primary btn-sm';
        $orcamentoActionIcon = 'bi bi-plus-circle';
    } elseif ($hasOrcamentoVinculado && $canViewOrcamento) {
        $orcamentoActionLabel = 'Visualizar orcamento';
        $orcamentoActionModalUrl = $orcamentoViewModalUrl;
        $orcamentoActionUrl = $orcamentoViewUrl;
        $orcamentoActionTitle = 'Visualizar o orcamento vinculado a esta OS.';
        $orcamentoActionButtonClass = 'btn btn-outline-primary btn-sm';
        $orcamentoActionIcon = 'bi bi-eye';
    }
}
?>

<div class="row g-3 <?= $isStatusModalContext ? 'mb-0' : 'mb-4' ?>">
    <div class="col-12">
        <div class="card os-tab-card<?= $isStatusModalContext ? ' os-status-modal-budget-card' : '' ?>">
            <div class="card-header os-tab-card-header py-3 d-flex flex-column flex-lg-row justify-content-between gap-3">
                <div>
                    <strong>
                        <i class="bi bi-box-seam me-2 text-primary"></i><?= $isStatusModalContext ? 'Gerenciamento do Orcamento' : 'Pecas e Orcamento' ?>
                    </strong>
                    <small class="text-muted ms-2">
                        <?= $isStatusModalContext
                            ? 'Itens vinculados ao orcamento desta OS, sem sair da alteracao de status.'
                            : 'Itens vinculados ao orcamento desta OS, sem sair da edicao.' ?>
                    </small>
                </div>
                <div class="d-flex flex-wrap gap-2 justify-content-start justify-content-lg-end">
                    <?php if ($orcamentoActionLabel !== '' && $orcamentoActionModalUrl !== ''): ?>
                        <button
                            type="button"
                            class="<?= esc($orcamentoActionButtonClass) ?>"
                            data-os-orcamento-modal-url="<?= esc($orcamentoActionModalUrl) ?>"
                            data-os-orcamento-modal-title="<?= esc($orcamentoActionLabel) ?>"
                            data-os-frame-modal-url="<?= esc($orcamentoActionModalUrl) ?>"
                            data-os-frame-modal-title="<?= esc($orcamentoActionLabel) ?>"
                            title="<?= esc($orcamentoActionTitle) ?>"
                        >
                            <i class="<?= esc($orcamentoActionIcon) ?> me-1"></i><?= esc($orcamentoActionLabel) ?>
                        </button>
                    <?php endif; ?>

                    <?php if ($hasOrcamentoVinculado && $canEditOrcamento && $orcamentoEditModalUrl !== '' && !in_array($orcamentoActionLabel, ['Editar orcamento', 'Lancar itens no orcamento'], true)): ?>
                        <button
                            type="button"
                            class="btn btn-outline-primary btn-sm"
                            data-os-orcamento-modal-url="<?= esc($orcamentoEditModalUrl) ?>"
                            data-os-orcamento-modal-title="Editar orcamento"
                            data-os-frame-modal-url="<?= esc($orcamentoEditModalUrl) ?>"
                            data-os-frame-modal-title="Editar orcamento"
                            title="<?= esc($editButtonTitle) ?>"
                        >
                            <i class="bi bi-pencil-square me-1"></i>Editar orcamento
                        </button>
                    <?php endif; ?>

                    <?php if ($hasOrcamentoVinculado && $canViewOrcamento && $orcamentoViewModalUrl !== '' && $orcamentoActionLabel !== 'Visualizar orcamento'): ?>
                        <button
                            type="button"
                            class="btn btn-outline-secondary btn-sm"
                            data-os-orcamento-modal-url="<?= esc($orcamentoViewModalUrl) ?>"
                            data-os-orcamento-modal-title="Visualizar orcamento"
                            data-os-frame-modal-url="<?= esc($orcamentoViewModalUrl) ?>"
                            data-os-frame-modal-title="Visualizar orcamento"
                            title="Abrir o orcamento vinculado em modo de consulta."
                        >
                            <i class="bi bi-eye me-1"></i>Visualizar
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <?php if (!$hasOrcamentoVinculado): ?>
                    <div class="os-empty-state p-4 border rounded text-center">
                        <i class="bi bi-receipt display-6 text-muted opacity-50"></i>
                        <h6 class="mt-3 mb-2">Nenhum orcamento vinculado</h6>
                        <p class="text-muted small mb-0">
                            <?= $isStatusModalContext
                                ? 'Crie um orcamento para inserir pecas, servicos, pacotes ou outros itens deste atendimento sem sair deste modal.'
                                : 'Crie um orcamento para inserir pecas, servicos, pacotes ou outros itens deste atendimento.' ?>
                        </p>
                    </div>
                <?php else: ?>
                    <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                        <span class="badge text-bg-light border">Orcamento <?= esc((string) ($orcamentoVinculado['numero'] ?? ('#' . (int) ($orcamentoVinculado['id'] ?? 0)))) ?></span>
                        <?php if ($orcamentoStatusLabel !== ''): ?>
                            <span class="badge text-bg-primary"><?= esc($orcamentoStatusLabel) ?></span>
                        <?php endif; ?>
                        <?php if ($orcamentoTipoLabel !== ''): ?>
                            <span class="badge text-bg-secondary"><?= esc($orcamentoTipoLabel) ?></span>
                        <?php endif; ?>
                        <span class="badge text-bg-success">Total <?= esc(formatMoney($orcamentoVinculado['total'] ?? 0)) ?></span>
                        <span class="badge text-bg-light border"><?= esc((string) ($orcamentoItensResumo['total_items'] ?? 0)) ?> item(ns)</span>
                    </div>

                    <?php if ($orcamentoLocked): ?>
                        <div class="alert alert-light border small mb-3">
                            Este orcamento esta bloqueado para edicao direta pelo status atual. Quando precisar apenas consultar, use o botao de visualizacao.
                        </div>
                    <?php endif; ?>

                    <?php if (empty($orcamentoItensResumo['groups'])): ?>
                        <div class="alert alert-info border-0 shadow-sm mb-3">
                            Nenhum item foi inserido neste orcamento ainda.
                        </div>
                    <?php else: ?>
                        <div class="row g-3 mb-3">
                            <?php foreach ($orcamentoItensResumo['groups'] as $grupo): ?>
                                <div class="col-12 col-sm-6 col-xl-3">
                                    <div class="card h-100 border-0 shadow-sm bg-light-subtle">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start gap-2">
                                                <div>
                                                    <div class="small text-muted"><?= esc((string) ($grupo['label'] ?? 'Itens')) ?></div>
                                                    <div class="fw-semibold fs-5"><?= esc((string) ($grupo['count'] ?? 0)) ?> item(ns)</div>
                                                </div>
                                                <span class="badge <?= esc((string) ($grupo['badge_class'] ?? 'bg-light text-dark border')) ?>">
                                                    <?= esc((string) ($grupo['label'] ?? 'Itens')) ?>
                                                </span>
                                            </div>
                                            <div class="small text-muted mt-3">Total do grupo</div>
                                            <div class="fw-semibold"><?= esc(formatMoney($grupo['total'] ?? 0)) ?></div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Tipo</th>
                                    <th>Descricao</th>
                                    <th>Qtd</th>
                                    <th>Valor Unit.</th>
                                    <th>Desconto</th>
                                    <th>Acrescimo</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($orcamentoItensResumo['items'])): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">Nenhum item cadastrado neste orcamento.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($orcamentoItensResumo['items'] as $itemOrcamento): ?>
                                        <tr>
                                            <td data-label="Tipo">
                                                <span class="badge <?= esc((string) ($itemOrcamento['tipo_item_badge_class'] ?? 'bg-light text-dark border')) ?>">
                                                    <?= esc((string) ($itemOrcamento['tipo_item_label'] ?? ucwords((string) ($itemOrcamento['tipo_item'] ?? 'item')))) ?>
                                                </span>
                                            </td>
                                            <td data-label="Descricao">
                                                <div><?= esc((string) ($itemOrcamento['descricao'] ?? '-')) ?></div>
                                                <?php if (!empty($itemOrcamento['observacoes'])): ?>
                                                    <small class="text-muted d-block mt-1"><?= esc((string) $itemOrcamento['observacoes']) ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td data-label="Qtd"><?= esc((string) ($itemOrcamento['quantidade'] ?? 0)) ?></td>
                                            <td data-label="Valor Unit."><?= esc(formatMoney($itemOrcamento['valor_unitario'] ?? 0)) ?></td>
                                            <td data-label="Desconto"><?= esc(formatMoney($itemOrcamento['desconto'] ?? 0)) ?></td>
                                            <td data-label="Acrescimo"><?= esc(formatMoney($itemOrcamento['acrescimo'] ?? 0)) ?></td>
                                            <td data-label="Total"><strong><?= esc(formatMoney($itemOrcamento['total'] ?? 0)) ?></strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
