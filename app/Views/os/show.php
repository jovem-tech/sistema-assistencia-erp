<?php
$isEmbedded = (bool) ($isEmbedded ?? false);
$embedQuery = $isEmbedded ? '?embed=1' : '';
$currentStatusCode = (string) ($os['status'] ?? '');
$nextStatusOptions = array_values(array_filter(
    $statusOptions ?? [],
    static fn (array $item): bool => ($item['codigo'] ?? '') !== '' && (string) ($item['codigo'] ?? '') !== $currentStatusCode
));
$notasLegadas = is_array($notasLegadas ?? null) ? $notasLegadas : [];
$legacyFinancialOrigins = is_array($legacyFinancialOrigins ?? null) ? $legacyFinancialOrigins : [];
$observacoesInternas = trim((string) ($os['observacoes_internas'] ?? ''));
$observacoesCliente = trim((string) ($os['observacoes_cliente'] ?? ''));
$formaPagamento = trim((string) ($os['forma_pagamento'] ?? ''));
$procedimentosExecutados = array_values(array_filter(array_map(
    static fn (string $item): string => trim($item),
    preg_split('/\r\n|\r|\n/', (string) ($os['procedimentos_executados'] ?? '')) ?: []
), static fn (string $item): bool => $item !== ''));
$orcamentoQuickUrl = base_url('orcamentos/novo?' . http_build_query([
    'origem' => 'os',
    'os_id' => (int) ($os['id'] ?? 0),
    'cliente_id' => (int) ($os['cliente_id'] ?? 0),
    'equipamento_id' => (int) ($os['equipamento_id'] ?? 0),
    'telefone' => (string) ($os['cliente_telefone'] ?? ''),
    'email' => (string) ($os['cliente_email'] ?? ''),
]));
$orcamentoVinculado = is_array($orcamentoVinculado ?? null) ? $orcamentoVinculado : null;
$hasOrcamentoVinculado = !empty($orcamentoVinculado['id']);
$orcamentoItensResumo = is_array($orcamentoItensResumo ?? null) ? $orcamentoItensResumo : ['items' => [], 'groups' => [], 'total_items' => 0, 'total_quantity' => 0.0];
$orcamentoStatusLabels = is_array($orcamentoStatusLabels ?? null) ? $orcamentoStatusLabels : [];
$orcamentoTipoLabels = is_array($orcamentoTipoLabels ?? null) ? $orcamentoTipoLabels : [];
$orcamentoEditUrl = $hasOrcamentoVinculado
    ? base_url('orcamentos/editar/' . (int) ($orcamentoVinculado['id'] ?? 0)) . $embedQuery
    : '';
$orcamentoViewUrl = $hasOrcamentoVinculado
    ? base_url('orcamentos/visualizar/' . (int) ($orcamentoVinculado['id'] ?? 0)) . $embedQuery
    : '';
$canCreateOrcamento = can('orcamentos', 'criar');
$canEditOrcamento = can('orcamentos', 'editar');
$canViewOrcamento = can('orcamentos', 'visualizar');
$orcamentoActionUrl = '';
$orcamentoActionLabel = '';
$orcamentoActionClass = '';
$orcamentoActionTitle = '';
if ($hasOrcamentoVinculado) {
    if ($canEditOrcamento) {
        $orcamentoActionUrl = $orcamentoEditUrl;
        $orcamentoActionLabel = 'Editar orcamento';
        $orcamentoActionClass = 'btn btn-primary';
        $orcamentoActionTitle = 'Editar orcamento vinculado a esta OS';
    } elseif ($canViewOrcamento) {
        $orcamentoActionUrl = $orcamentoViewUrl;
        $orcamentoActionLabel = 'Visualizar orcamento';
        $orcamentoActionClass = 'btn btn-primary';
        $orcamentoActionTitle = 'Visualizar orcamento vinculado a esta OS';
    }
} elseif ($canCreateOrcamento) {
    $orcamentoActionUrl = $orcamentoQuickUrl;
    $orcamentoActionLabel = 'Gerar orcamento';
    $orcamentoActionClass = 'btn btn-outline-warning';
    $orcamentoActionTitle = 'Gerar orcamento para esta OS';
}
$checklistFotos = [];
foreach ((array) ($checklist_entrada['itens'] ?? []) as $itemChecklist) {
    foreach ((array) ($itemChecklist['fotos'] ?? []) as $fotoChecklist) {
        if (!empty($fotoChecklist['url'])) {
            $checklistFotos[] = [
                'descricao' => (string) ($itemChecklist['descricao'] ?? 'Checklist'),
                'url' => (string) $fotoChecklist['url'],
            ];
        }
    }
}
$fotosPerfilEquipamento = array_values(array_filter((array) ($fotos_equip ?? []), static fn (array $foto): bool => (int) ($foto['is_principal'] ?? 0) === 1));
$fotosGaleriaEquipamento = array_values(array_filter((array) ($fotos_equip ?? []), static fn (array $foto): bool => (int) ($foto['is_principal'] ?? 0) !== 1));
if (empty($fotosPerfilEquipamento) && !empty($fotos_equip)) {
    $fotosPerfilEquipamento = [reset($fotos_equip)];
    $fotosGaleriaEquipamento = array_values(array_slice((array) $fotos_equip, 1));
}
?>
<?= $this->extend($layout ?? 'layouts/main') ?>

<?= $this->section('content') ?>

<div class="os-show-page">
    <div class="page-header">
        <div>
            <h2><i class="bi bi-clipboard-check me-2"></i><?= esc($os['numero_os']) ?></h2>
            <span class="text-muted">Aberta em <?= esc(formatDate($os['data_abertura'], true)) ?></span>
            <?php if (!empty($os['numero_os_legado']) || !empty($os['legacy_origem'])): ?>
                <div class="small text-muted mt-2">
                    <?php if (!empty($os['numero_os_legado'])): ?>
                        <span class="me-3"><strong>Numero legado:</strong> <?= esc($os['numero_os_legado']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($os['legacy_origem'])): ?>
                        <span><strong>Origem:</strong> <?= esc($os['legacy_origem']) ?></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="os-top-actions">
            <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('ordens-de-servico')" title="Ajuda sobre Ordens de Servico">
                <i class="bi bi-question-circle me-1"></i>Ajuda
            </button>
            <?php if (can('os', 'editar')): ?>
            <a href="<?= base_url('os/editar/' . $os['id']) ?><?= $embedQuery ?>" class="btn btn-outline-secondary">
                <i class="bi bi-pencil me-1"></i>Editar
            </a>
            <?php endif; ?>
            <?php if ($orcamentoActionUrl !== '' && $orcamentoActionLabel !== ''): ?>
            <a href="<?= esc($orcamentoActionUrl) ?>" class="<?= esc($orcamentoActionClass) ?>" title="<?= esc($orcamentoActionTitle) ?>">
                <i class="bi bi-receipt-cutoff me-1"></i><?= esc($orcamentoActionLabel) ?>
            </a>
            <?php endif; ?>
            <a href="<?= base_url('os/imprimir/' . $os['id']) ?>" class="btn btn-outline-secondary" target="_blank">
                <i class="bi bi-printer me-1"></i>Imprimir
            </a>
            <?php if (!$isEmbedded): ?>
            <a href="<?= base_url('os') ?>" class="btn btn-outline-secondary" data-back-default="<?= base_url('os') ?>">
                <i class="bi bi-arrow-left me-1"></i>Voltar
            </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="row g-4 ds-split-layout">
        <div class="col-12 col-xl-4 col-xxl-3 ds-split-sidebar">
            <div class="os-show-sidebar-stack ds-sticky-panel">
                <div class="card glass-card os-show-photo-card">
                    <div class="card-body text-center p-3">
                        <h6 class="fw-bold mb-3 text-uppercase text-muted os-show-photo-title">
                            <i class="bi bi-image me-1"></i>Fotos do Equipamento
                        </h6>
                        <div class="mb-3">
                            <?php
                            $principalObj = array_filter($fotos_equip ?? [], fn ($f) => (int) ($f['is_principal'] ?? 0) === 1);
                            $principalObj = !empty($principalObj) ? array_values($principalObj)[0] : (!empty($fotos_equip) ? $fotos_equip[0] : null);
                            ?>
                            <?php if ($principalObj): ?>
                                <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#imageModal" data-img-src="<?= esc($principalObj['url']) ?>" class="rounded bg-body-tertiary d-flex align-items-center justify-content-center overflow-hidden mx-auto border text-decoration-none os-show-photo-preview">
                                    <img src="<?= esc($principalObj['url']) ?>" alt="Foto principal" class="os-show-photo-preview-img">
                                </a>
                            <?php else: ?>
                                <div class="rounded bg-body-tertiary d-flex align-items-center justify-content-center mx-auto border text-body-secondary os-show-photo-preview">
                                    <div class="text-center opacity-50">
                                        <i class="bi bi-camera fs-1"></i>
                                        <div class="small mt-1">Sem foto</div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if (count($fotos_equip ?? []) > 1): ?>
                        <div class="d-flex flex-wrap gap-2 justify-content-center border-top pt-3">
                            <?php foreach ($fotos_equip as $foto): ?>
                                <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#imageModal" data-img-src="<?= esc($foto['url']) ?>" class="border rounded d-inline-block overflow-hidden os-show-thumb-link">
                                    <img src="<?= esc($foto['url']) ?>" class="w-100 h-100 object-fit-cover" alt="Miniatura do equipamento">
                                </a>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <div class="mt-3 p-2 rounded text-start os-show-photo-meta">
                            <div class="text-white-50"><i class="bi bi-laptop me-1"></i><?= esc(trim(($os['equip_marca'] ?? '') . ' ' . ($os['equip_modelo'] ?? ''))) ?></div>
                            <div class="text-muted mt-1"><i class="bi bi-upc me-1"></i>SN: <?= esc($os['equip_serie'] ?? '-') ?></div>
                        </div>
                    </div>
                </div>

                <div class="card glass-card os-workflow-card">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h6 class="mb-0"><i class="bi bi-bezier2 me-1"></i>Historico e Progresso</h6>
                        <span class="small text-muted">Etapas percorridas, etapa atual e provaveis proximos movimentos.</span>
                    </div>
                    <div class="card-body">
                        <?php if (empty($workflowTimeline ?? [])): ?>
                            <p class="text-muted mb-0 small">Fluxo visual indisponivel para esta OS.</p>
                        <?php else: ?>
                            <div class="os-workflow-timeline">
                                <?php foreach (($workflowTimeline ?? []) as $stage): ?>
                                    <?php
                                    $stageState = (string) ($stage['state'] ?? 'upcoming');
                                    $stageBadgeClass = 'bg-light text-dark border';
                                    $stageBadgeLabel = 'Futura';

                                    if ($stageState === 'completed') {
                                        $stageBadgeClass = 'bg-success-subtle text-success-emphasis border border-success-subtle';
                                        $stageBadgeLabel = 'Concluida';
                                    } elseif ($stageState === 'current') {
                                        $stageBadgeClass = 'bg-primary-subtle text-primary-emphasis border border-primary-subtle';
                                        $stageBadgeLabel = 'Atual';
                                    } elseif ($stageState === 'probable') {
                                        $stageBadgeClass = 'bg-warning-subtle text-warning-emphasis border border-warning-subtle';
                                        $stageBadgeLabel = 'Provavel';
                                    }
                                    ?>
                                    <div class="os-workflow-step is-<?= esc($stageState) ?>">
                                        <div class="os-workflow-step-marker"></div>
                                        <div class="os-workflow-step-body">
                                            <div class="os-workflow-step-top">
                                                <div class="os-workflow-step-title"><?= esc((string) ($stage['label'] ?? 'Etapa')) ?></div>
                                                <span class="badge <?= esc($stageBadgeClass) ?>"><?= esc($stageBadgeLabel) ?></span>
                                            </div>

                                            <?php if ($stageState === 'current' && !empty($stage['current_status_name'])): ?>
                                                <div class="os-workflow-step-text">Agora em <strong><?= esc((string) $stage['current_status_name']) ?></strong>.</div>
                                            <?php elseif ($stageState === 'completed' && !empty($stage['last_status_name'])): ?>
                                                <div class="os-workflow-step-text">Passou por <strong><?= esc((string) $stage['last_status_name']) ?></strong>.</div>
                                                <?php if (!empty($stage['last_event_at'])): ?>
                                                    <div class="os-workflow-step-meta">
                                                        <?= esc(formatDate((string) $stage['last_event_at'], true)) ?>
                                                        <?php if (!empty($stage['last_user_name'])): ?>
                                                            por <?= esc((string) $stage['last_user_name']) ?>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                            <?php elseif ($stageState === 'probable' && !empty($stage['next_status_names'])): ?>
                                                <div class="os-workflow-step-text">Proximas opcoes: <?= esc(implode(', ', (array) $stage['next_status_names'])) ?>.</div>
                                            <?php else: ?>
                                                <div class="os-workflow-step-text">Etapa futura do atendimento.</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <?php if (!empty($workflowRecentHistory ?? [])): ?>
                                <div class="os-section-divider"></div>
                                <div class="os-workflow-history">
                                    <div class="os-section-caption">Ultimas movimentacoes</div>
                                    <div class="os-workflow-history-list">
                                        <?php foreach (($workflowRecentHistory ?? []) as $item): ?>
                                            <div class="os-workflow-history-item">
                                                <strong><?= esc(ucwords(str_replace('_', ' ', (string) ($item['status_novo'] ?? '-')))) ?></strong>
                                                <span><?= esc(formatDate($item['created_at'] ?? '', true)) ?></span>
                                                <?php if (!empty($item['usuario_nome'])): ?>
                                                    <small>por <?= esc($item['usuario_nome']) ?></small>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-8 col-xxl-9 ds-split-main">
            <div class="row g-4 mb-4 align-items-start os-show-primary-row">
                <div class="col-12">
                    <div class="card glass-card os-primary-workspace">
                        <div class="card-body">
                            <div class="os-primary-summary">
                                <div class="os-primary-summary-item">
                                    <span class="os-primary-summary-label">Cliente</span>
                                    <strong><?= esc($os['cliente_nome']) ?></strong>
                                    <small><?= esc($os['cliente_telefone'] ?? '-') ?></small>
                                </div>
                                <div class="os-primary-summary-item">
                                    <span class="os-primary-summary-label">Equipamento</span>
                                    <strong><?= esc(trim(($os['equip_marca'] ?? '') . ' ' . ($os['equip_modelo'] ?? ''))) ?></strong>
                                    <small><?= esc(getEquipTipo($os['equip_tipo'] ?? '')) ?><?php if (!empty($os['equip_serie'])): ?> | SN: <?= esc($os['equip_serie']) ?><?php endif; ?></small>
                                </div>
                                <div class="os-primary-summary-item">
                                    <span class="os-primary-summary-label">Tecnico</span>
                                    <strong><?= esc($os['tecnico_nome'] ?? 'Nao atribuido') ?></strong>
                                    <small>OS aberta em <?= esc(formatDate($os['data_abertura'] ?? '', true)) ?></small>
                                </div>
                            </div>

                            <ul class="nav nav-tabs ds-tabs-scroll os-show-tabs" role="tablist">
                                <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-info">Informacoes</a></li>
                                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-itens">Orcamento</a></li>
                                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-tecnico">Diagnostico</a></li>
                                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-fotos">Fotos</a></li>
                                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-valores">Valores</a></li>
                            </ul>

                            <div class="tab-content os-show-tab-content">
                                <div class="tab-pane fade show active" id="tab-info">
                                    <div class="row g-4">
                                        <div class="col-12">
                                            <div class="info-card">
                                                <div class="info-card-title"><i class="bi bi-activity"></i>Status Atual da OS</div>
                                                <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                                                    <?= getStatusBadge($os['status']) ?>
                                                    <?php if (!empty($os['estado_fluxo'])): ?>
                                                        <span class="badge bg-light text-dark border"><?= esc(ucwords(str_replace('_', ' ', (string) $os['estado_fluxo']))) ?></span>
                                                    <?php endif; ?>
                                                    <?= getPriorityBadge($os['prioridade'] ?? 'normal') ?>
                                                    <?php if ($hasOrcamentoVinculado): ?>
                                                        <span class="badge bg-light text-dark border">Orcamento <?= esc((string) ($orcamentoVinculado['numero'] ?? '#')) ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if ($hasOrcamentoVinculado): ?>
                                                    <div class="small text-muted mb-2">Status do orcamento vinculado</div>
                                                    <div class="d-flex flex-wrap gap-2 mb-3">
                                                        <span class="badge bg-primary-subtle text-primary-emphasis"><?= esc((string) ($orcamentoVinculado['status_label'] ?? ($orcamentoStatusLabels[$orcamentoVinculado['status'] ?? ''] ?? 'Orcamento vinculado'))) ?></span>
                                                        <?php if (!empty($orcamentoVinculado['tipo_label'])): ?>
                                                            <span class="badge bg-light text-dark border"><?= esc((string) $orcamentoVinculado['tipo_label']) ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="small text-muted mb-2">Proximas etapas provaveis</div>
                                                <?php if (empty($nextStatusOptions)): ?>
                                                    <p class="mb-0 text-muted">Nao ha transicoes sugeridas alem do status atual.</p>
                                                <?php else: ?>
                                                    <div class="d-flex flex-wrap gap-2">
                                                        <?php foreach ($nextStatusOptions as $statusHint): ?>
                                                            <span class="badge bg-light text-dark border"><?= esc((string) ($statusHint['nome'] ?? $statusHint['codigo'] ?? 'Status')) ?></span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="info-card">
                                                <div class="info-card-title"><i class="bi bi-chat-left-text"></i>Relato do Cliente</div>
                                                <p class="mb-0"><?= nl2br(esc($os['relato_cliente'])) ?></p>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="info-card">
                                                <div class="info-card-title"><i class="bi bi-ui-checks-grid"></i>Checklist de Entrada</div>
                                                <?php $checklistResumo = $checklist_entrada['resumo'] ?? null; ?>
                                                <?php if (empty($checklist_entrada) || empty($checklist_entrada['possui_modelo'])): ?>
                                                    <p class="mb-0 text-muted">Nenhum checklist configurado para o tipo de equipamento desta OS.</p>
                                                <?php else: ?>
                                                    <div class="mb-2">
                                                        <span class="badge <?= ($checklistResumo['variant'] ?? '') === 'success' ? 'bg-success' : ((($checklistResumo['variant'] ?? '') === 'warning') ? 'bg-warning text-dark' : 'bg-secondary') ?>">
                                                            <?= esc((string) ($checklistResumo['label'] ?? 'Checklist nao preenchido')) ?>
                                                        </span>
                                                    </div>
                                                    <?php
                                                        $checklistItens = array_values(array_filter((array) ($checklist_entrada['itens'] ?? []), static function ($item) {
                                                            return (string) ($item['status'] ?? 'nao_verificado') === 'discrepancia'
                                                                || trim((string) ($item['observacao'] ?? '')) !== '';
                                                        }));
                                                    ?>
                                                    <?php if (empty($checklistItens)): ?>
                                                        <p class="mb-0 text-muted">Nenhuma discrepancia registrada no checklist.</p>
                                                    <?php else: ?>
                                                        <div class="d-flex flex-column gap-2">
                                                            <?php foreach ($checklistItens as $item): ?>
                                                                <div class="border rounded-3 p-2 os-show-subitem-card">
                                                                    <div class="fw-semibold"><?= esc((string) ($item['descricao'] ?? '-')) ?></div>
                                                                    <?php if (!empty($item['observacao'])): ?>
                                                                        <div class="small text-muted mt-1"><?= esc((string) $item['observacao']) ?></div>
                                                                    <?php endif; ?>
                                                                    <?php if (!empty($item['fotos'])): ?>
                                                                        <div class="d-flex flex-wrap gap-2 mt-2">
                                                                            <?php foreach ($item['fotos'] as $foto): ?>
                                                                                <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#imageModal" data-img-src="<?= esc((string) ($foto['url'] ?? '')) ?>" class="border rounded overflow-hidden shadow-sm os-show-inline-photo">
                                                                                    <img src="<?= esc((string) ($foto['url'] ?? '')) ?>" class="w-100 h-100 object-fit-cover" alt="Foto do checklist">
                                                                                </a>
                                                                            <?php endforeach; ?>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="tab-pane fade" id="tab-itens">
                                    <?php if ($hasOrcamentoVinculado): ?>
                                    <div class="row g-4">
                                        <div class="col-12">
                                            <div class="info-card">
                                                <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                                                    <div>
                                                        <div class="info-card-title mb-1"><i class="bi bi-receipt"></i>Resumo do Orcamento</div>
                                                        <p class="text-muted mb-0">Este orcamento esta vinculado a esta OS e concentra pecas, servicos, pacotes e acessorios cadastrados.</p>
                                                    </div>
                                                    <?php if ($orcamentoActionUrl !== '' && $orcamentoActionLabel !== ''): ?>
                                                        <a href="<?= esc($orcamentoActionUrl) ?>" class="<?= esc(trim($orcamentoActionClass . ' btn-sm')) ?>">
                                                            <i class="bi bi-receipt-cutoff me-1"></i><?= esc($orcamentoActionLabel) ?>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>

                                                <div class="row g-3 mt-1">
                                                    <div class="col-12 col-md-4 col-xl-2">
                                                        <div class="small text-muted">Numero</div>
                                                        <div class="fw-semibold"><?= esc((string) ($orcamentoVinculado['numero'] ?? '-')) ?></div>
                                                    </div>
                                                    <div class="col-12 col-md-4 col-xl-3">
                                                        <div class="small text-muted">Status</div>
                                                        <div><?= esc((string) ($orcamentoVinculado['status_label'] ?? ($orcamentoStatusLabels[$orcamentoVinculado['status'] ?? ''] ?? '-'))) ?></div>
                                                    </div>
                                                    <div class="col-12 col-md-4 col-xl-3">
                                                        <div class="small text-muted">Tipo</div>
                                                        <div><?= esc((string) ($orcamentoVinculado['tipo_label'] ?? ($orcamentoTipoLabels[$orcamentoVinculado['tipo_orcamento'] ?? ''] ?? '-'))) ?></div>
                                                    </div>
                                                    <div class="col-12 col-md-6 col-xl-2">
                                                        <div class="small text-muted">Validade</div>
                                                        <div><?= esc(formatDate($orcamentoVinculado['validade_data'] ?? '')) ?></div>
                                                    </div>
                                                    <div class="col-12 col-md-6 col-xl-2">
                                                        <div class="small text-muted">Valor total</div>
                                                        <div class="fw-semibold text-success"><?= esc(formatMoney($orcamentoVinculado['total'] ?? 0)) ?></div>
                                                    </div>
                                                </div>

                                                <?php if (!empty($orcamentoVinculado['prazo_execucao'])): ?>
                                                    <div class="small text-muted mt-3">Prazo de execucao: <strong class="text-body"><?= esc((string) $orcamentoVinculado['prazo_execucao']) ?></strong></div>
                                                <?php endif; ?>

                                                <?php if (!empty($orcamentoVinculado['is_locked'])): ?>
                                                    <div class="alert alert-light border small mt-3 mb-0">Este orcamento esta bloqueado para edicao direta pelo status atual. Se precisar reenviar uma nova proposta, utilize o fluxo de revisao no modulo de orcamentos.</div>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <div class="row g-3">
                                                <?php if (empty($orcamentoItensResumo['groups'])): ?>
                                                    <div class="col-12">
                                                        <div class="info-card">
                                                            <div class="info-card-title"><i class="bi bi-box-seam"></i>Composicao do Orcamento</div>
                                                            <p class="mb-0 text-muted">Nenhum item foi inserido neste orcamento ainda.</p>
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <?php foreach ($orcamentoItensResumo['groups'] as $grupo): ?>
                                                        <div class="col-12 col-md-6 col-xl-3">
                                                            <div class="info-card h-100">
                                                                <div class="d-flex justify-content-between align-items-start gap-2">
                                                                    <div>
                                                                        <div class="small text-muted"><?= esc((string) ($grupo['label'] ?? 'Itens')) ?></div>
                                                                        <div class="fs-5 fw-semibold"><?= esc((string) ($grupo['count'] ?? 0)) ?> item(ns)</div>
                                                                    </div>
                                                                    <span class="badge <?= esc((string) ($grupo['badge_class'] ?? 'bg-light text-dark border')) ?>"><?= esc((string) ($grupo['label'] ?? 'Itens')) ?></span>
                                                                </div>
                                                                <div class="small text-muted mt-3">Total deste grupo</div>
                                                                <div class="fw-semibold"><?= esc(formatMoney($grupo['total'] ?? 0)) ?></div>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <div class="card glass-card">
                                                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                                                    <h5 class="card-title mb-0">Itens do Orcamento</h5>
                                                    <span class="small text-muted"><?= esc((string) ($orcamentoItensResumo['total_items'] ?? 0)) ?> item(ns) vinculado(s)</span>
                                                </div>
                                                <div class="card-body p-0">
                                                    <div class="table-responsive">
                                                        <table class="table table-hover mb-0">
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
                                                                    <tr><td colspan="7" class="text-center py-3 text-muted">Nenhum item cadastrado neste orcamento.</td></tr>
                                                                <?php else: ?>
                                                                    <?php foreach ($orcamentoItensResumo['items'] as $itemOrcamento): ?>
                                                                        <tr>
                                                                            <td>
                                                                                <span class="badge <?= esc((string) ($itemOrcamento['tipo_item_badge_class'] ?? 'bg-light text-dark border')) ?>">
                                                                                    <?= esc((string) ($itemOrcamento['tipo_item_label'] ?? ucwords((string) ($itemOrcamento['tipo_item'] ?? 'item')))) ?>
                                                                                </span>
                                                                            </td>
                                                                            <td>
                                                                                <div><?= esc((string) ($itemOrcamento['descricao'] ?? '-')) ?></div>
                                                                                <?php if (!empty($itemOrcamento['observacoes'])): ?>
                                                                                    <small class="text-muted d-block mt-1"><?= esc((string) $itemOrcamento['observacoes']) ?></small>
                                                                                <?php endif; ?>
                                                                            </td>
                                                                            <td><?= esc((string) ($itemOrcamento['quantidade'] ?? 0)) ?></td>
                                                                            <td><?= esc(formatMoney($itemOrcamento['valor_unitario'] ?? 0)) ?></td>
                                                                            <td><?= esc(formatMoney($itemOrcamento['desconto'] ?? 0)) ?></td>
                                                                            <td><?= esc(formatMoney($itemOrcamento['acrescimo'] ?? 0)) ?></td>
                                                                            <td><strong><?= esc(formatMoney($itemOrcamento['total'] ?? 0)) ?></strong></td>
                                                                        </tr>
                                                                    <?php endforeach; ?>
                                                                <?php endif; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php else: ?>
                                    <div class="info-card">
                                        <div class="info-card-title"><i class="bi bi-receipt"></i>Orcamento</div>
                                        <p class="text-muted mb-3">Esta OS ainda nao possui um orcamento vinculado.</p>
                                        <?php if ($orcamentoActionUrl !== '' && $orcamentoActionLabel !== ''): ?>
                                            <a href="<?= esc($orcamentoActionUrl) ?>" class="<?= esc($orcamentoActionClass) ?>">
                                                <i class="bi bi-receipt-cutoff me-1"></i><?= esc($orcamentoActionLabel) ?>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="tab-pane fade" id="tab-tecnico">
                                    <div class="row g-4">
                                        <div class="col-12">
                                            <div class="info-card">
                                                <div class="info-card-title"><i class="bi bi-list-check"></i>Procedimentos executados</div>
                                                <?php if (empty($procedimentosExecutados)): ?>
                                                    <p class="mb-0 text-muted">Nenhum procedimento executado registrado.</p>
                                                <?php else: ?>
                                                    <div class="d-flex flex-column gap-2">
                                                        <?php foreach ($procedimentosExecutados as $procedimento): ?>
                                                            <div class="border rounded-3 p-2 os-show-subitem-card small">
                                                                <?= esc($procedimento) ?>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="info-card">
                                                <div class="info-card-title"><i class="bi bi-search"></i>Diagnostico Tecnico</div>
                                                <p><?= nl2br(esc($os['diagnostico_tecnico'] ?? 'Nenhum diagnostico registrado.')) ?></p>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="info-card">
                                                <div class="info-card-title"><i class="bi bi-wrench"></i>Solucao Aplicada</div>
                                                <p><?= nl2br(esc($os['solucao_aplicada'] ?? 'Nenhuma solucao registrada.')) ?></p>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="info-card">
                                                <div class="info-card-title"><i class="bi bi-person-badge"></i>Tecnico</div>
                                                <p><?= esc($os['tecnico_nome'] ?? 'Nao atribuido') ?></p>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="info-card">
                                                <div class="info-card-title"><i class="bi bi-shield-check"></i>Garantia</div>
                                                <p>
                                                    <?= esc((string) ($os['garantia_dias'] ?? '0')) ?> dias
                                                    <?php if (!empty($os['garantia_validade'])): ?>
                                                        - Valida ate <?= esc(formatDate($os['garantia_validade'])) ?>
                                                    <?php endif; ?>
                                                </p>
                                            </div>
                                        </div>

                                        <?php if ($observacoesInternas !== ''): ?>
                                        <div class="col-md-6">
                                            <div class="info-card">
                                                <div class="info-card-title"><i class="bi bi-journal-text"></i>Observacoes Internas</div>
                                                <p class="mb-0"><?= nl2br(esc($observacoesInternas)) ?></p>
                                            </div>
                                        </div>
                                        <?php endif; ?>

                                        <?php if ($observacoesCliente !== ''): ?>
                                        <div class="col-md-6">
                                            <div class="info-card">
                                                <div class="info-card-title"><i class="bi bi-chat-square-quote"></i>Observacoes do Cliente</div>
                                                <p class="mb-0"><?= nl2br(esc($observacoesCliente)) ?></p>
                                            </div>
                                        </div>
                                        <?php endif; ?>

                                        <?php if (!empty($notasLegadas)): ?>
                                        <div class="col-12">
                                            <div class="info-card">
                                                <div class="info-card-title"><i class="bi bi-clock-history"></i>Notas Legadas Importadas</div>
                                                <div class="d-flex flex-column gap-2">
                                                    <?php foreach ($notasLegadas as $nota): ?>
                                                        <div class="border rounded-3 p-3 os-show-subitem-card">
                                                            <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-2">
                                                                <div class="fw-semibold"><?= esc($nota['titulo'] ?? 'Registro legado') ?></div>
                                                                <?php if (!empty($nota['created_at'])): ?>
                                                                    <small class="text-muted"><?= esc(formatDate($nota['created_at'], true)) ?></small>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="small text-muted mb-2">
                                                                <span class="me-2"><strong>Origem:</strong> <?= esc($nota['legacy_origem'] ?? 'erp') ?></span>
                                                                <?php if (!empty($nota['legacy_tabela'])): ?>
                                                                    <span><strong>Tabela:</strong> <?= esc($nota['legacy_tabela']) ?></span>
                                                                <?php endif; ?>
                                                            </div>
                                                            <p class="mb-0"><?= nl2br(esc($nota['conteudo'] ?? '')) ?></p>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endif; ?>

                                        <?php if (!empty($defeitos)): ?>
                                        <div class="col-12">
                                            <div class="info-card">
                                                <div class="info-card-title"><i class="bi bi-journal-text text-warning"></i>Base de Conhecimento: Procedimentos de Reparo</div>
                                                <div class="row g-3">
                                                    <?php foreach ($defeitos as $def): ?>
                                                    <div class="col-md-6 border-bottom pb-3 mb-2">
                                                        <h6 class="text-warning small mb-2"><i class="bi bi-tag-fill me-1"></i><?= esc($def['nome']) ?> (<?= esc(ucfirst((string) $def['classificacao'])) ?>)</h6>
                                                        <?php if (empty($def['procedimentos'])): ?>
                                                            <p class="text-muted small mb-0">Sem procedimentos especificos cadastrados.</p>
                                                        <?php else: ?>
                                                            <div class="vstack gap-2">
                                                                <?php foreach ($def['procedimentos'] as $idx => $proc): ?>
                                                                <div class="d-flex align-items-center p-2 rounded os-show-subitem-card">
                                                                    <div class="me-3">
                                                                        <span class="badge rounded-circle bg-warning text-dark os-procedimento-badge"><?= $idx + 1 ?></span>
                                                                    </div>
                                                                    <div class="small"><?= esc($proc['descricao']) ?></div>
                                                                </div>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="tab-fotos">
                                    <?php $acessoriosComFotos = array_values(array_filter((array) ($acessorios ?? []), static fn (array $acessorio): bool => !empty($acessorio['fotos']))); ?>
                                    <div class="row g-4">
                                        <div class="col-12 col-xl-6">
                                            <div class="card glass-card h-100">
                                                <div class="card-header d-flex justify-content-between align-items-center">
                                                    <h5 class="card-title mb-0"><i class="bi bi-person-square me-2"></i>Fotos de Perfil do Equipamento</h5>
                                                    <span class="badge bg-light text-dark border"><?= esc((string) count($fotosPerfilEquipamento)) ?></span>
                                                </div>
                                                <div class="card-body">
                                                    <?php if (empty($fotosPerfilEquipamento)): ?>
                                                        <p class="text-muted mb-0">Nenhuma foto de perfil do equipamento registrada.</p>
                                                    <?php else: ?>
                                                        <div class="row g-3">
                                                            <?php foreach ($fotosPerfilEquipamento as $foto): ?>
                                                                <div class="col-6">
                                                                    <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#imageModal" data-img-src="<?= esc($foto['url']) ?>" class="border rounded d-block overflow-hidden shadow-sm hover-elevate transition os-show-entry-photo">
                                                                        <img src="<?= esc($foto['url']) ?>" class="w-100 h-100 object-fit-cover" alt="Foto de perfil do equipamento">
                                                                    </a>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-12 col-xl-6">
                                            <div class="card glass-card h-100">
                                                <div class="card-header d-flex justify-content-between align-items-center">
                                                    <h5 class="card-title mb-0"><i class="bi bi-images me-2"></i>Demais Fotos do Equipamento</h5>
                                                    <span class="badge bg-light text-dark border"><?= esc((string) count($fotosGaleriaEquipamento)) ?></span>
                                                </div>
                                                <div class="card-body">
                                                    <?php if (empty($fotosGaleriaEquipamento)): ?>
                                                        <p class="text-muted mb-0">Nenhuma foto adicional do equipamento registrada.</p>
                                                    <?php else: ?>
                                                        <div class="row g-3">
                                                            <?php foreach ($fotosGaleriaEquipamento as $foto): ?>
                                                                <div class="col-6">
                                                                    <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#imageModal" data-img-src="<?= esc($foto['url']) ?>" class="border rounded d-block overflow-hidden shadow-sm hover-elevate transition os-show-entry-photo">
                                                                        <img src="<?= esc($foto['url']) ?>" class="w-100 h-100 object-fit-cover" alt="Foto adicional do equipamento">
                                                                    </a>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <div class="card glass-card">
                                                <div class="card-header d-flex justify-content-between align-items-center">
                                                    <h5 class="card-title mb-0"><i class="bi bi-camera me-2"></i>Fotos de Entrada</h5>
                                                    <span class="badge bg-light text-dark border"><?= esc((string) count($fotos_entrada ?? [])) ?></span>
                                                </div>
                                                <div class="card-body">
                                                    <?php if (empty($fotos_entrada)): ?>
                                                        <p class="text-muted mb-0">Nenhuma foto registrada na entrada.</p>
                                                    <?php else: ?>
                                                        <div class="row g-3">
                                                            <?php foreach ($fotos_entrada as $foto): ?>
                                                                <div class="col-6 col-md-3">
                                                                    <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#imageModal" data-img-src="<?= esc($foto['url']) ?>" class="border rounded d-block overflow-hidden shadow-sm hover-elevate transition os-show-entry-photo">
                                                                        <img src="<?= esc($foto['url']) ?>" class="w-100 h-100 object-fit-contain" alt="Foto de entrada">
                                                                    </a>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <div class="card glass-card">
                                                <div class="card-header d-flex justify-content-between align-items-center">
                                                    <h5 class="card-title mb-0"><i class="bi bi-patch-check me-2"></i>Fotos dos Acessorios</h5>
                                                    <span class="badge bg-light text-dark border"><?= esc((string) count($acessoriosComFotos)) ?></span>
                                                </div>
                                                <div class="card-body">
                                                    <?php if (empty($acessoriosComFotos)): ?>
                                                        <p class="text-muted mb-0">Nenhuma foto de acessorio registrada.</p>
                                                    <?php else: ?>
                                                        <div class="row g-3">
                                                            <?php foreach ($acessoriosComFotos as $acessorio): ?>
                                                                <div class="col-12 col-md-6 col-xl-4">
                                                                    <div class="border rounded-3 p-3 h-100 os-show-subitem-card">
                                                                        <div class="fw-semibold mb-3"><?= esc((string) ($acessorio['descricao'] ?? 'Acessorio')) ?></div>
                                                                        <div class="d-flex flex-wrap gap-2">
                                                                            <?php foreach ((array) ($acessorio['fotos'] ?? []) as $foto): ?>
                                                                                <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#imageModal" data-img-src="<?= esc($foto['url']) ?>" class="border rounded overflow-hidden shadow-sm os-show-inline-photo">
                                                                                    <img src="<?= esc($foto['url']) ?>" class="w-100 h-100 object-fit-cover" alt="Foto do acessorio">
                                                                                </a>
                                                                            <?php endforeach; ?>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <div class="card glass-card">
                                                <div class="card-header d-flex justify-content-between align-items-center">
                                                    <h5 class="card-title mb-0"><i class="bi bi-ui-checks-grid me-2"></i>Fotos do Checklist de Entrada</h5>
                                                    <span class="badge bg-light text-dark border"><?= esc((string) count($checklistFotos)) ?></span>
                                                </div>
                                                <div class="card-body">
                                                    <?php if (empty($checklistFotos)): ?>
                                                        <p class="text-muted mb-0">Nenhuma foto vinculada ao checklist de entrada.</p>
                                                    <?php else: ?>
                                                        <div class="row g-3">
                                                            <?php foreach ($checklistFotos as $fotoChecklist): ?>
                                                                <div class="col-12 col-md-6 col-xl-4">
                                                                    <div class="border rounded-3 p-3 h-100 os-show-subitem-card">
                                                                        <div class="fw-semibold mb-2"><?= esc((string) ($fotoChecklist['descricao'] ?? 'Checklist')) ?></div>
                                                                        <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#imageModal" data-img-src="<?= esc($fotoChecklist['url']) ?>" class="border rounded d-block overflow-hidden shadow-sm os-show-entry-photo">
                                                                            <img src="<?= esc($fotoChecklist['url']) ?>" class="w-100 h-100 object-fit-cover" alt="Foto do checklist">
                                                                        </a>
                                                                    </div>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="tab-valores">
                                    <div class="row g-4">
                                        <div class="col-12 col-xl-6">
                                            <div class="info-card h-100">
                                                <div class="info-card-title"><i class="bi bi-currency-dollar"></i>Resumo Financeiro da OS</div>
                                                <div class="finance-item">
                                                    <span class="finance-label">Mao de Obra</span>
                                                    <span class="finance-value"><?= esc(formatMoney($os['valor_mao_obra'] ?? 0)) ?></span>
                                                </div>
                                                <div class="finance-item">
                                                    <span class="finance-label">Pecas</span>
                                                    <span class="finance-value"><?= esc(formatMoney($os['valor_pecas'] ?? 0)) ?></span>
                                                </div>
                                                <div class="finance-item">
                                                    <span class="finance-label">Subtotal da OS</span>
                                                    <span class="finance-value"><?= esc(formatMoney($os['valor_total'] ?? 0)) ?></span>
                                                </div>
                                                <div class="finance-item text-danger">
                                                    <span class="finance-label">Desconto</span>
                                                    <span class="finance-value">- <?= esc(formatMoney($os['desconto'] ?? 0)) ?></span>
                                                </div>
                                                <hr>
                                                <div class="finance-item">
                                                    <span class="finance-label"><strong>Total final da OS</strong></span>
                                                    <span class="finance-value text-success"><strong><?= esc(formatMoney($os['valor_final'] ?? 0)) ?></strong></span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-12 col-xl-6">
                                            <div class="info-card h-100">
                                                <div class="info-card-title"><i class="bi bi-receipt"></i>Resumo Financeiro do Orcamento</div>
                                                <?php if (!$hasOrcamentoVinculado): ?>
                                                    <p class="mb-0 text-muted">Nenhum orcamento vinculado para detalhar nesta OS.</p>
                                                <?php else: ?>
                                                    <div class="finance-item">
                                                        <span class="finance-label">Numero</span>
                                                        <span class="finance-value"><?= esc((string) ($orcamentoVinculado['numero'] ?? '-')) ?></span>
                                                    </div>
                                                    <div class="finance-item">
                                                        <span class="finance-label">Status</span>
                                                        <span class="finance-value"><?= esc((string) ($orcamentoVinculado['status_label'] ?? '-')) ?></span>
                                                    </div>
                                                    <div class="finance-item">
                                                        <span class="finance-label">Subtotal</span>
                                                        <span class="finance-value"><?= esc(formatMoney($orcamentoVinculado['subtotal'] ?? 0)) ?></span>
                                                    </div>
                                                    <div class="finance-item text-danger">
                                                        <span class="finance-label">Desconto</span>
                                                        <span class="finance-value">- <?= esc(formatMoney($orcamentoVinculado['desconto'] ?? 0)) ?></span>
                                                    </div>
                                                    <div class="finance-item text-success">
                                                        <span class="finance-label">Acrescimo</span>
                                                        <span class="finance-value">+ <?= esc(formatMoney($orcamentoVinculado['acrescimo'] ?? 0)) ?></span>
                                                    </div>
                                                    <hr>
                                                    <div class="finance-item">
                                                        <span class="finance-label"><strong>Total do orcamento</strong></span>
                                                        <span class="finance-value text-success"><strong><?= esc(formatMoney($orcamentoVinculado['total'] ?? 0)) ?></strong></span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <div class="col-12 col-xl-6">
                                            <div class="info-card h-100">
                                                <div class="info-card-title"><i class="bi bi-calendar-check"></i>Datas e Aprovacoes</div>
                                                <div class="finance-item">
                                                    <span class="finance-label">Abertura</span>
                                                    <span class="finance-value"><?= esc(formatDate($os['data_abertura'] ?? '', true)) ?></span>
                                                </div>
                                                <div class="finance-item">
                                                    <span class="finance-label">Entrada</span>
                                                    <span class="finance-value"><?= esc(formatDate($os['data_entrada'] ?? '', true)) ?></span>
                                                </div>
                                                <div class="finance-item">
                                                    <span class="finance-label">Previsao</span>
                                                    <span class="finance-value"><?= esc(formatDate($os['data_previsao'] ?? '')) ?></span>
                                                </div>
                                                <div class="finance-item">
                                                    <span class="finance-label">Conclusao</span>
                                                    <span class="finance-value"><?= esc(formatDate($os['data_conclusao'] ?? '')) ?></span>
                                                </div>
                                                <div class="finance-item">
                                                    <span class="finance-label">Entrega</span>
                                                    <span class="finance-value"><?= esc(formatDate($os['data_entrega'] ?? '')) ?></span>
                                                </div>
                                                <?php if (!empty($os['data_aprovacao'])): ?>
                                                <div class="finance-item">
                                                    <span class="finance-label">Aprovacao da OS</span>
                                                    <span class="finance-value"><?= esc(formatDate($os['data_aprovacao'], true)) ?></span>
                                                </div>
                                                <?php endif; ?>
                                                <?php if ($hasOrcamentoVinculado): ?>
                                                    <hr>
                                                    <div class="finance-item">
                                                        <span class="finance-label">Orcamento criado</span>
                                                        <span class="finance-value"><?= esc(formatDate($orcamentoVinculado['created_at'] ?? '', true)) ?></span>
                                                    </div>
                                                    <div class="finance-item">
                                                        <span class="finance-label">Orcamento enviado</span>
                                                        <span class="finance-value"><?= esc(formatDate($orcamentoVinculado['enviado_em'] ?? '', true)) ?></span>
                                                    </div>
                                                    <div class="finance-item">
                                                        <span class="finance-label">Orcamento aprovado</span>
                                                        <span class="finance-value"><?= esc(formatDate($orcamentoVinculado['aprovado_em'] ?? '', true)) ?></span>
                                                    </div>
                                                    <div class="finance-item">
                                                        <span class="finance-label">Orcamento rejeitado</span>
                                                        <span class="finance-value"><?= esc(formatDate($orcamentoVinculado['rejeitado_em'] ?? '', true)) ?></span>
                                                    </div>
                                                    <div class="finance-item">
                                                        <span class="finance-label">Orcamento cancelado</span>
                                                        <span class="finance-value"><?= esc(formatDate($orcamentoVinculado['cancelado_em'] ?? '', true)) ?></span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <div class="col-12 col-xl-6">
                                            <div class="info-card h-100">
                                                <div class="info-card-title"><i class="bi bi-wallet2"></i>Complementos Financeiros</div>
                                                <div class="finance-item">
                                                    <span class="finance-label">Orcamento aprovado</span>
                                                    <span class="finance-value"><?= !empty($os['orcamento_aprovado']) ? 'Sim' : 'Nao' ?></span>
                                                </div>
                                                <div class="finance-item">
                                                    <span class="finance-label">Forma de pagamento</span>
                                                    <span class="finance-value"><?= esc($formaPagamento !== '' ? $formaPagamento : '-') ?></span>
                                                </div>
                                                <div class="finance-item">
                                                    <span class="finance-label">Garantia</span>
                                                    <span class="finance-value"><?= esc((string) ($os['garantia_dias'] ?? '0')) ?> dias</span>
                                                </div>
                                                <?php if (!empty($orcamentoVinculado['validade_data'])): ?>
                                                <div class="finance-item">
                                                    <span class="finance-label">Validade do orcamento</span>
                                                    <span class="finance-value"><?= esc(formatDate($orcamentoVinculado['validade_data'] ?? '')) ?></span>
                                                </div>
                                                <?php endif; ?>
                                                <?php if (!empty($legacyFinancialOrigins)): ?>
                                                <div class="alert alert-info border-0 mt-3 mb-0">
                                                    <div class="fw-semibold mb-2">Origem do valor legado</div>
                                                    <div class="d-flex flex-column gap-2">
                                                        <?php foreach ($legacyFinancialOrigins as $origin): ?>
                                                            <div>
                                                                <div class="small fw-semibold"><?= esc($origin['descricao']) ?> - <?= esc(formatMoney($origin['valor_total'])) ?></div>
                                                                <?php if (!empty($origin['observacao'])): ?>
                                                                    <div class="small text-muted"><?= esc($origin['observacao']) ?></div>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 os-show-ops-row">
                <div class="col-12 col-xl-6">
                    <div class="card glass-card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0"><i class="bi bi-file-earmark-pdf me-1"></i>Documentos PDF</h6>
                        </div>
                        <div class="card-body">
                            <form action="<?= base_url('os/pdf/' . $os['id'] . '/gerar') ?><?= $embedQuery ?>" method="POST" class="os-doc-form mb-3">
                                <?= csrf_field() ?>
                                <select name="tipo_documento" class="form-select form-select-sm" required>
                                    <option value="">Selecionar tipo...</option>
                                    <?php foreach (($pdfTipos ?? []) as $codigo => $nome): ?>
                                        <option value="<?= esc($codigo) ?>"><?= esc($nome) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn btn-sm btn-glow">Gerar</button>
                            </form>
                            <?php if (empty($documentosOs ?? [])): ?>
                                <p class="text-muted mb-0 small">Nenhum documento gerado.</p>
                            <?php else: ?>
                                <?php
                                $templateByDocumento = [
                                    'abertura' => 'os_aberta',
                                    'orcamento' => 'orcamento_enviado',
                                    'laudo' => 'laudo_concluido',
                                    'entrega' => 'entrega_concluida',
                                    'devolucao_sem_reparo' => 'devolucao_sem_reparo',
                                ];
                                ?>
                                <div class="d-flex flex-column gap-2 os-show-scroll-list">
                                    <?php foreach (($documentosOs ?? []) as $doc): ?>
                                        <div class="border rounded p-2 small d-flex justify-content-between align-items-center gap-2 flex-wrap">
                                            <div>
                                                <div class="fw-semibold"><?= esc(ucwords(str_replace('_', ' ', (string) ($doc['tipo_documento'] ?? 'documento')))) ?> v<?= esc((string) ($doc['versao'] ?? 1)) ?></div>
                                                <div class="text-muted"><?= esc(formatDate($doc['created_at'] ?? '', true)) ?></div>
                                            </div>
                                            <div class="d-flex gap-1">
                                                <a class="btn btn-sm btn-outline-primary" href="<?= base_url($doc['arquivo']) ?>" target="_blank" rel="noopener" title="Baixar PDF">
                                                    <i class="bi bi-download"></i>
                                                </a>
                                                <?php if (can('os', 'editar')): ?>
                                                    <form action="<?= base_url('os/whatsapp/' . $os['id']) ?><?= $embedQuery ?>" method="POST" class="d-inline">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="telefone" value="<?= esc($os['cliente_telefone'] ?? '') ?>">
                                                        <input type="hidden" name="documento_id" value="<?= esc((string) ($doc['id'] ?? '')) ?>">
                                                        <input type="hidden" name="template_codigo" value="<?= esc($templateByDocumento[$doc['tipo_documento']] ?? 'os_aberta') ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-success" title="Enviar PDF por WhatsApp">
                                                            <i class="bi bi-whatsapp"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-6">
                    <div class="card glass-card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0"><i class="bi bi-whatsapp me-1"></i>WhatsApp</h6>
                        </div>
                        <div class="card-body">
                            <?php if (can('os', 'editar')): ?>
                                <form action="<?= base_url('os/whatsapp/' . $os['id']) ?><?= $embedQuery ?>" method="POST" class="d-flex flex-column gap-2 mb-3">
                                    <?= csrf_field() ?>
                                    <select name="template_codigo" class="form-select form-select-sm">
                                        <option value="">Template...</option>
                                        <?php foreach (($whatsappTemplates ?? []) as $tpl): ?>
                                            <option value="<?= esc($tpl['codigo']) ?>"><?= esc($tpl['nome']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <select name="documento_id" class="form-select form-select-sm">
                                        <option value="">PDF opcional para envio...</option>
                                        <?php foreach (($documentosOs ?? []) as $doc): ?>
                                            <option value="<?= esc((string) ($doc['id'] ?? '')) ?>">
                                                <?= esc(ucwords(str_replace('_', ' ', (string) ($doc['tipo_documento'] ?? 'pdf')))) ?> v<?= esc((string) ($doc['versao'] ?? 1)) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <textarea name="mensagem_manual" class="form-control form-control-sm" rows="2" placeholder="Mensagem manual (opcional)"></textarea>
                                    <input type="text" name="telefone" class="form-control form-control-sm" value="<?= esc($os['cliente_telefone'] ?? '') ?>" placeholder="Telefone destino">
                                    <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-send me-1"></i>Enviar</button>
                                </form>
                            <?php else: ?>
                                <p class="text-muted small">Sem permissao para envio manual.</p>
                            <?php endif; ?>

                            <?php if (empty($whatsappLogs ?? [])): ?>
                                <p class="text-muted mb-0 small">Nenhuma mensagem registrada.</p>
                            <?php else: ?>
                                <div class="d-flex flex-column gap-2 os-show-scroll-list os-show-whatsapp-list">
                                    <?php foreach (($whatsappLogs ?? []) as $msg): ?>
                                        <?php
                                        $statusEnvio = (string) ($msg['status'] ?? ($msg['status_envio'] ?? '-'));
                                        $titulo = $msg['template_codigo'] ?? ($msg['template_nome'] ?? ($msg['tipo_evento'] ?? 'manual'));
                                        $tipoConteudo = $msg['tipo_conteudo'] ?? 'texto';
                                        ?>
                                        <div class="border rounded p-2 small">
                                            <div class="d-flex justify-content-between">
                                                <span class="fw-semibold"><?= esc((string) $titulo) ?></span>
                                                <span class="badge <?= $statusEnvio === 'enviado' ? 'bg-success' : 'bg-danger' ?>"><?= esc($statusEnvio) ?></span>
                                            </div>
                                            <div class="text-muted"><?= esc(strtoupper((string) $tipoConteudo)) ?></div>
                                            <div class="text-muted"><?= esc(formatDate($msg['created_at'] ?? '', true)) ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content bg-transparent border-0">
                <div class="modal-body text-center p-0 position-relative">
                    <div class="d-inline-block position-relative">
                        <button type="button" class="btn-close position-absolute os-show-modal-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        <img src="" id="modalImagePreview" class="img-fluid rounded shadow-lg os-show-modal-image" alt="Visualizacao ampliada">
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const imageModal = document.getElementById('imageModal');
        if (!imageModal) {
            return;
        }

        imageModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const imgSrc = button ? button.getAttribute('data-img-src') : '';
            const modalImg = imageModal.querySelector('#modalImagePreview');
            if (modalImg) {
                modalImg.src = imgSrc || '';
            }
        });

        imageModal.addEventListener('hidden.bs.modal', function() {
            const modalImg = imageModal.querySelector('#modalImagePreview');
            if (modalImg) {
                modalImg.src = '';
            }
        });
    });
</script>
<?= $this->endSection() ?>
