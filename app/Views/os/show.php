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
$orcamentoQuickEmbedUrl = base_url('orcamentos/novo?' . http_build_query([
    'origem' => 'os',
    'os_id' => (int) ($os['id'] ?? 0),
    'cliente_id' => (int) ($os['cliente_id'] ?? 0),
    'equipamento_id' => (int) ($os['equipamento_id'] ?? 0),
    'telefone' => (string) ($os['cliente_telefone'] ?? ''),
    'email' => (string) ($os['cliente_email'] ?? ''),
    'embed' => 1,
]));
$orcamentoVinculado = is_array($orcamentoVinculado ?? null) ? $orcamentoVinculado : null;
$hasOrçamentoVinculado = !empty($orcamentoVinculado['id']);
$orcamentoItensResumo = is_array($orcamentoItensResumo ?? null) ? $orcamentoItensResumo : ['items' => [], 'groups' => [], 'total_items' => 0, 'total_quantity' => 0.0];
$orcamentoStatusLabels = is_array($orcamentoStatusLabels ?? null) ? $orcamentoStatusLabels : [];
$orcamentoTipoLabels = is_array($orcamentoTipoLabels ?? null) ? $orcamentoTipoLabels : [];
$orcamentoEditUrl = $hasOrçamentoVinculado
    ? base_url('orcamentos/editar/' . (int) ($orcamentoVinculado['id'] ?? 0)) . $embedQuery
    : '';
$orcamentoViewUrl = $hasOrçamentoVinculado
    ? base_url('orcamentos/visualizar/' . (int) ($orcamentoVinculado['id'] ?? 0)) . $embedQuery
    : '';
$canCreateOrçamento = can('orcamentos', 'criar');
$canEditOrçamento = can('orcamentos', 'editar');
$canViewOrçamento = can('orcamentos', 'visualizar');
$orcamentoActionUrl = '';
$orcamentoActionLabel = '';
$orcamentoActionClass = '';
$orcamentoActionTitle = '';
if ($hasOrçamentoVinculado) {
    if ($canEditOrçamento) {
        $orcamentoActionUrl = $orcamentoEditUrl;
        $orcamentoActionLabel = 'Editar orçamento';
        $orcamentoActionClass = 'btn btn-primary';
        $orcamentoActionTitle = 'Editar orçamento vinculado a esta OS';
    } elseif ($canViewOrçamento) {
        $orcamentoActionUrl = $orcamentoViewUrl;
        $orcamentoActionLabel = 'Visualizar orçamento';
        $orcamentoActionClass = 'btn btn-primary';
        $orcamentoActionTitle = 'Visualizar orçamento vinculado a esta OS';
    }
} elseif ($canCreateOrçamento) {
    $orcamentoActionUrl = $orcamentoQuickUrl;
    $orcamentoActionLabel = 'Gerar orçamento';
    $orcamentoActionClass = 'btn btn-outline-warning';
    $orcamentoActionTitle = 'Gerar orçamento para esta OS';
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
$documentosDaOs = array_values((array) ($documentosOs ?? []));
$primeiroDocumentoOsId = (int) ($documentosDaOs[0]['id'] ?? 0);
$pdfTipos = is_array($pdfTipos ?? null) ? $pdfTipos : [];
$emailDefaultTo = trim((string) ($emailDefaultTo ?? ''));
$emailDefaultSubject = trim((string) ($emailDefaultSubject ?? ''));
$emailDefaultMessage = trim((string) ($emailDefaultMessage ?? ''));
$orcamentoWhatsappDefaultMessage = trim((string) ($orcamentoWhatsappDefaultMessage ?? ''));
$orcamentoEmailDefaultSubject = trim((string) ($orcamentoEmailDefaultSubject ?? ''));
$orcamentoDispatchBlocked = (bool) ($orcamentoDispatchBlocked ?? false);
$orcamentoWhatsappSendUrl = $hasOrçamentoVinculado ? base_url('orcamentos/whatsapp/' . (int) ($orcamentoVinculado['id'] ?? 0) . '/enviar') . $embedQuery : '';
$orcamentoEmailSendUrl = $hasOrçamentoVinculado ? base_url('orcamentos/email/' . (int) ($orcamentoVinculado['id'] ?? 0) . '/enviar') . $embedQuery : '';
$templateByDocumento = [
    'abertura' => 'os_aberta',
    'orcamento' => 'orcamento_enviado',
    'laudo' => 'laudo_concluido',
    'entrega' => 'entrega_concluida',
    'devolucao_sem_reparo' => 'devolucao_sem_reparo',
];
$documentLabelByCode = $pdfTipos;
$printPreviewBaseUrl = base_url('os/imprimir/' . (int) ($os['id'] ?? 0));
$printWhatsAppUrl = base_url('os/whatsapp/' . (int) ($os['id'] ?? 0)) . $embedQuery;
$knowledgeWhatsappTemplatesUrl = base_url('conhecimento/templates-whatsapp');
?>
<?= $this->extend($layout ?? 'layouts/main') ?>

<?= $this->section('content') ?>

<style>
    .os-print-preview-layout {
        min-height: min(82vh, 920px);
    }

    .os-print-preview-frame-wrap {
        position: relative;
        background:
            radial-gradient(circle at top, rgba(59, 130, 246, 0.08), transparent 38%),
            linear-gradient(180deg, #e2e8f0 0%, #f8fafc 100%);
        min-width: 0;
    }

    .os-print-preview-frame {
        width: 100%;
        height: min(78vh, 900px);
        border: 0;
        background: #fff;
    }

    .os-print-preview-loading {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(248, 250, 252, 0.82);
        z-index: 2;
    }

    .os-print-preview-header-tools {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 10px;
        flex-wrap: wrap;
    }

    .os-print-preview-format-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 0.45rem 0.8rem;
        border-radius: 999px;
        border: 1px solid rgba(15, 23, 42, 0.08);
        background: #f8fafc;
        color: #0f172a;
        font-size: 0.82rem;
        font-weight: 700;
    }

    .os-print-preview-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
        padding: 14px 18px;
        border-top: 1px solid rgba(15, 23, 42, 0.08);
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    }

    .os-print-preview-footer-meta {
        display: flex;
        align-items: center;
        gap: 14px;
        flex-wrap: wrap;
        min-width: 0;
    }

    .os-print-preview-photo-hint {
        max-width: 560px;
        font-size: 0.82rem;
        color: #64748b;
    }

    .os-print-preview-footer-actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 10px;
        flex-wrap: wrap;
    }

    .os-print-whatsapp-panel {
        border: 1px solid rgba(22, 163, 74, 0.14);
        border-radius: 18px;
        background: linear-gradient(180deg, rgba(240, 253, 244, 0.95) 0%, rgba(255, 255, 255, 1) 100%);
    }

    @media (max-width: 1199.98px) {
        .os-print-preview-frame {
            height: 70vh;
        }
    }

    @media (max-width: 430px) {
        .os-print-preview-frame {
            height: 56vh;
        }

        .os-print-preview-footer {
            padding: 12px;
        }

        .os-print-preview-footer-actions,
        .os-print-preview-footer-actions .btn,
        .os-print-preview-header-tools,
        .os-print-preview-header-tools .btn {
            width: 100%;
        }

        .os-print-preview-header-tools .os-print-preview-format-pill {
            justify-content: center;
        }
    }
</style>

<div class="os-show-page">
    <div class="page-header">
        <div>
            <h2><i class="bi bi-clipboard-check me-2"></i><?= esc($os['numero_os']) ?></h2>
            <span class="text-muted">Aberta em <?= esc(formatDate($os['data_abertura'], true)) ?></span>
            <?php if (!empty($os['numero_os_legado']) || !empty($os['legacy_origem'])): ?>
                <div class="small text-muted mt-2">
                    <?php if (!empty($os['numero_os_legado'])): ?>
                        <span class="me-3"><strong>Número legado:</strong> <?= esc($os['numero_os_legado']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($os['legacy_origem'])): ?>
                        <span><strong>Origem:</strong> <?= esc($os['legacy_origem']) ?></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="os-top-actions">
            <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('ordens-de-servico')" title="Ajuda sobre Ordens de Serviço">
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
            <div class="dropdown">
                <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-printer me-1"></i>Imprimir
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <?php foreach (($printFormats ?? []) as $formatCode => $formatLabel): ?>
                        <li>
                            <button
                                type="button"
                                class="dropdown-item d-flex align-items-center gap-2"
                                data-os-print-trigger
                                data-print-format="<?= esc((string) $formatCode) ?>"
                                data-print-label="<?= esc((string) $formatLabel) ?>"
                            >
                                <i class="bi bi-file-earmark-text"></i>
                                <span><?= esc((string) $formatLabel) ?></span>
                            </button>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
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
                                        <i class="bi bi-câmera fs-1"></i>
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
                        <h6 class="mb-0"><i class="bi bi-bezier2 me-1"></i>Histórico e Progresso</h6>
                        <span class="small text-muted">Etapas percorridas, etapa atual e prováveis próximos movimentos.</span>
                    </div>
                    <div class="card-body">
                        <?php if (empty($workflowTimeline ?? [])): ?>
                            <p class="text-muted mb-0 small">Fluxo visual indisponível para esta OS.</p>
                        <?php else: ?>
                            <div class="os-workflow-timeline">
                                <?php foreach (($workflowTimeline ?? []) as $stage): ?>
                                    <?php
                                    $stageState = (string) ($stage['state'] ?? 'upcoming');
                                    $stageBadgeClass = 'bg-light text-dark border';
                                    $stageBadgeLabel = 'Futura';

                                    if ($stageState === 'completed') {
                                        $stageBadgeClass = 'bg-success-subtle text-success-emphasis border border-success-subtle';
                                        $stageBadgeLabel = 'Concluída';
                                    } elseif ($stageState === 'current') {
                                        $stageBadgeClass = 'bg-primary-subtle text-primary-emphasis border border-primary-subtle';
                                        $stageBadgeLabel = 'Atual';
                                    } elseif ($stageState === 'probable') {
                                        $stageBadgeClass = 'bg-warning-subtle text-warning-emphasis border border-warning-subtle';
                                        $stageBadgeLabel = 'Provável';
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
                                                <div class="os-workflow-step-text">Próximas opções: <?= esc(implode(', ', (array) $stage['next_status_names'])) ?>.</div>
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
                                    <div class="os-section-caption">Últimas movimentações</div>
                                    <div class="os-workflow-history-list">
                                        <?php foreach (($workflowRecentHistory ?? []) as $item): ?>
                                            <div class="os-workflow-history-item">
                                                <strong><?= esc((string) ($item['status_novo_nome'] ?? '-')) ?></strong>
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
                                    <span class="os-primary-summary-label">Técnico</span>
                                <strong><?= esc($os['tecnico_nome'] ?? 'Não atribuído') ?></strong>
                                    <small>OS aberta em <?= esc(formatDate($os['data_abertura'] ?? '', true)) ?></small>
                                </div>
                            </div>

                            <ul class="nav nav-tabs ds-tabs-scroll os-show-tabs" role="tablist">
                                <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-info">Informações</a></li>
                                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-itens">Orçamento</a></li>
                                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-técnico">Diagnóstico</a></li>
                                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-fotos">Fotos</a></li>
                                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-documentos">Documentos</a></li>
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
                                                        <span class="badge bg-light text-dark border"><?= esc((string) ($estadoFluxoLabel ?? $os['estado_fluxo'])) ?></span>
                                                    <?php endif; ?>
                                                    <?= getPriorityBadge($os['prioridade'] ?? 'normal') ?>
                                                    <?php if ($hasOrçamentoVinculado): ?>
                                                        <span class="badge bg-light text-dark border">Orçamento <?= esc((string) ($orcamentoVinculado['numero'] ?? '#')) ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if ($hasOrçamentoVinculado): ?>
                                                    <div class="small text-muted mb-2">Status do orçamento vinculado</div>
                                                    <div class="d-flex flex-wrap gap-2 mb-3">
                                                        <span class="badge bg-primary-subtle text-primary-emphasis"><?= esc((string) ($orcamentoVinculado['status_label'] ?? ($orcamentoStatusLabels[$orcamentoVinculado['status'] ?? ''] ?? 'Orçamento vinculado'))) ?></span>
                                                        <?php if (!empty($orcamentoVinculado['tipo_label'])): ?>
                                                            <span class="badge bg-light text-dark border"><?= esc((string) $orcamentoVinculado['tipo_label']) ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="small text-muted mb-2">Próximas etapas prováveis</div>
                                                <?php if (empty($nextStatusOptions)): ?>
                                                    <p class="mb-0 text-muted">Não há transições sugeridas além do status atual.</p>
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
                                                            <?= esc((string) ($checklistResumo['label'] ?? 'Checklist não preenchido')) ?>
                                                        </span>
                                                    </div>
                                                    <?php
                                                        $checklistItens = array_values(array_filter((array) ($checklist_entrada['itens'] ?? []), static function ($item) {
                                                            return (string) ($item['status'] ?? 'nao_verificado') === 'discrepancia'
                                                                || trim((string) ($item['observacao'] ?? '')) !== '';
                                                        }));
                                                    ?>
                                                    <?php if (empty($checklistItens)): ?>
                                                        <p class="mb-0 text-muted">Nenhuma discrepância registrada no checklist.</p>
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
                                    <?php if ($hasOrçamentoVinculado): ?>
                                    <div class="row g-4">
                                        <div class="col-12">
                                            <div class="info-card">
                                                <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                                                    <div>
                                                        <div class="info-card-title mb-1"><i class="bi bi-receipt"></i>Resumo do Orçamento</div>
                                                        <p class="text-muted mb-0">Este orçamento está vinculado a esta OS e concentra peças, serviços, pacotes e acessórios cadastrados.</p>
                                                    </div>
                                                    <?php if ($orcamentoActionUrl !== '' && $orcamentoActionLabel !== ''): ?>
                                                        <a href="<?= esc($orcamentoActionUrl) ?>" class="<?= esc(trim($orcamentoActionClass . ' btn-sm')) ?>">
                                                            <i class="bi bi-receipt-cutoff me-1"></i><?= esc($orcamentoActionLabel) ?>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>

                                                <div class="row g-3 mt-1">
                                                    <div class="col-12 col-md-4 col-xl-2">
                                                        <div class="small text-muted">Número</div>
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
                                                    <div class="small text-muted mt-3">Prazo de execução: <strong class="text-body"><?= esc((string) $orcamentoVinculado['prazo_execucao']) ?></strong></div>
                                                <?php endif; ?>

                                                <?php if (!empty($orcamentoVinculado['is_locked'])): ?>
                                                    <div class="alert alert-light border small mt-3 mb-0">Este orçamento está bloqueado para edição direta pelo status atual. Se precisar reenviar uma nova proposta, utilize o fluxo de revisão no módulo de orçamentos.</div>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <div class="row g-3">
                                                <?php if (empty($orcamentoItensResumo['groups'])): ?>
                                                    <div class="col-12">
                                                        <div class="info-card">
                                                            <div class="info-card-title"><i class="bi bi-box-seam"></i>Composição do Orçamento</div>
                                                            <p class="mb-0 text-muted">Nenhum item foi inserido neste orçamento ainda.</p>
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
                                                    <h5 class="card-title mb-0">Itens do Orçamento</h5>
                                                    <span class="small text-muted"><?= esc((string) ($orcamentoItensResumo['total_items'] ?? 0)) ?> item(ns) vinculado(s)</span>
                                                </div>
                                                <div class="card-body p-0">
                                                    <div class="table-responsive">
                                                        <table class="table table-hover mb-0">
                                                            <thead>
                                                                <tr>
                                                                    <th>Tipo</th>
                                                                    <th>Descrição</th>
                                                                    <th>Qtd</th>
                                                                    <th>Valor Unit.</th>
                                                                    <th>Desconto</th>
                                                                    <th>Acréscimo</th>
                                                                    <th>Total</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php if (empty($orcamentoItensResumo['items'])): ?>
                                                            <tr><td colspan="7" class="text-center py-3 text-muted">Nenhum item cadastrado neste orçamento.</td></tr>
                                                                <?php else: ?>
                                                                    <?php foreach ($orcamentoItensResumo['items'] as $itemOrçamento): ?>
                                                                        <tr>
                                                                            <td>
                                                                                <span class="badge <?= esc((string) ($itemOrçamento['tipo_item_badge_class'] ?? 'bg-light text-dark border')) ?>">
                                                                                    <?= esc((string) ($itemOrçamento['tipo_item_label'] ?? ucwords((string) ($itemOrçamento['tipo_item'] ?? 'item')))) ?>
                                                                                </span>
                                                                            </td>
                                                                            <td>
                                                                                <div><?= esc((string) ($itemOrçamento['descricao'] ?? '-')) ?></div>
                                                                                <?php if (!empty($itemOrçamento['observacoes'])): ?>
                                                                                    <small class="text-muted d-block mt-1"><?= esc((string) $itemOrçamento['observacoes']) ?></small>
                                                                                <?php endif; ?>
                                                                            </td>
                                                                            <td><?= esc((string) ($itemOrçamento['quantidade'] ?? 0)) ?></td>
                                                                            <td><?= esc(formatMoney($itemOrçamento['valor_unitario'] ?? 0)) ?></td>
                                                                            <td><?= esc(formatMoney($itemOrçamento['desconto'] ?? 0)) ?></td>
                                                                            <td><?= esc(formatMoney($itemOrçamento['acrescimo'] ?? 0)) ?></td>
                                                                            <td><strong><?= esc(formatMoney($itemOrçamento['total'] ?? 0)) ?></strong></td>
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
                                            <div class="info-card-title"><i class="bi bi-receipt"></i>Orçamento</div>
                                            <p class="text-muted mb-3">Esta OS ainda não possui um orçamento vinculado.</p>
                                        <?php if ($orcamentoActionUrl !== '' && $orcamentoActionLabel !== ''): ?>
                                            <a href="<?= esc($orcamentoActionUrl) ?>" class="<?= esc($orcamentoActionClass) ?>">
                                                <i class="bi bi-receipt-cutoff me-1"></i><?= esc($orcamentoActionLabel) ?>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="tab-pane fade" id="tab-técnico">
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
                                                <div class="info-card-title"><i class="bi bi-search"></i>Diagnóstico Técnico</div>
                                                <p><?= nl2br(esc($os['diagnostico_tecnico'] ?? 'Nenhum diagnóstico registrado.')) ?></p>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="info-card">
                                                <div class="info-card-title"><i class="bi bi-wrench"></i>Solução Aplicada</div>
                                                <p><?= nl2br(esc($os['solucao_aplicada'] ?? 'Nenhuma solução registrada.')) ?></p>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="info-card">
                                                <div class="info-card-title"><i class="bi bi-person-badge"></i>Técnico</div>
                                                <p><?= esc($os['tecnico_nome'] ?? 'Não atribuído') ?></p>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="info-card">
                                                <div class="info-card-title"><i class="bi bi-shield-check"></i>Garantia</div>
                                                <p>
                                                    <?= esc((string) ($os['garantia_dias'] ?? '0')) ?> dias
                                                    <?php if (!empty($os['garantia_validade'])): ?>
                                                        - Válida até <?= esc(formatDate($os['garantia_validade'])) ?>
                                                    <?php endif; ?>
                                                </p>
                                            </div>
                                        </div>

                                        <?php if ($observacoesInternas !== ''): ?>
                                        <div class="col-md-6">
                                            <div class="info-card">
                                                <div class="info-card-title"><i class="bi bi-journal-text"></i>Observações Internas</div>
                                                <p class="mb-0"><?= nl2br(esc($observacoesInternas)) ?></p>
                                            </div>
                                        </div>
                                        <?php endif; ?>

                                        <?php if ($observacoesCliente !== ''): ?>
                                        <div class="col-md-6">
                                            <div class="info-card">
                                                <div class="info-card-title"><i class="bi bi-chat-square-quote"></i>Observações do Cliente</div>
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
                                                    <h5 class="card-title mb-0"><i class="bi bi-câmera me-2"></i>Fotos de Entrada</h5>
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
                                                    <h5 class="card-title mb-0"><i class="bi bi-patch-check me-2"></i>Fotos dos Acessórios</h5>
                                                    <span class="badge bg-light text-dark border"><?= esc((string) count($acessoriosComFotos)) ?></span>
                                                </div>
                                                <div class="card-body">
                                                    <?php if (empty($acessoriosComFotos)): ?>
                                                        <p class="text-muted mb-0">Nenhuma foto de acessório registrada.</p>
                                                    <?php else: ?>
                                                        <div class="row g-3">
                                                            <?php foreach ($acessoriosComFotos as $acessorio): ?>
                                                                <div class="col-12 col-md-6 col-xl-4">
                                                                    <div class="border rounded-3 p-3 h-100 os-show-subitem-card">
                                                                        <div class="fw-semibold mb-3"><?= esc((string) ($acessorio['descricao'] ?? 'Acessório')) ?></div>
                                                                        <div class="d-flex flex-wrap gap-2">
                                                                            <?php foreach ((array) ($acessorio['fotos'] ?? []) as $foto): ?>
                                                                                <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#imageModal" data-img-src="<?= esc($foto['url']) ?>" class="border rounded overflow-hidden shadow-sm os-show-inline-photo">
                                                                                    <img src="<?= esc($foto['url']) ?>" class="w-100 h-100 object-fit-cover" alt="Foto do acessório">
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
                                <div class="tab-pane fade" id="tab-documentos">
                                    <div class="row g-4">
                                        <div class="col-12 col-xl-6 col-xxl-4">
                                            <div class="card glass-card h-100">
                                                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                                                    <h5 class="card-title mb-0"><i class="bi bi-file-earmark-pdf me-2"></i>Documentos PDF</h5>
                                                    <span class="badge bg-light text-dark border"><?= esc((string) count($documentosDaOs)) ?></span>
                                                </div>
                                                <div class="card-body">
                                                    <p class="text-muted small mb-3">Gere novas versões dos documentos da OS e centralize os arquivos prontos para envio.</p>
                                                    <form action="<?= base_url('os/pdf/' . $os['id'] . '/gerar') ?><?= $embedQuery ?>" method="POST" class="os-doc-form mb-3" id="osPdfGenerateForm" data-budget-create-url="<?= esc($orcamentoQuickEmbedUrl) ?>" data-has-budget="<?= $hasOrçamentoVinculado ? '1' : '0' ?>">
                                                        <?= csrf_field() ?>
                                                        <select name="tipo_documento" class="form-select form-select-sm" id="osPdfTipoSelect" required>
                                                            <option value="">Selecionar tipo...</option>
                                                            <?php foreach ($pdfTipos as $codigo => $nome): ?>
                                                                <option value="<?= esc($codigo) ?>"><?= esc($nome) ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <button type="submit" class="btn btn-sm btn-glow">Gerar</button>
                                                    </form>

                                                    <?php if (empty($documentosDaOs)): ?>
                                                        <p class="text-muted mb-0 small">Nenhum documento gerado. Gere um PDF para liberar os envios por WhatsApp e e-mail.</p>
                                                    <?php else: ?>
                                                        <div class="d-flex flex-column gap-2 os-show-scroll-list">
                                                            <?php foreach ($documentosDaOs as $doc): ?>
                                                                <?php
                                                                $docType = (string) ($doc['tipo_documento'] ?? 'documento');
                                                                $docLabel = $documentLabelByCode[$docType] ?? ucwords(str_replace('_', ' ', $docType));
                                                                $isBudgetDoc = $docType === 'orcamento' && $hasOrçamentoVinculado;
                                                                ?>
                                                                <div class="border rounded p-2 small d-flex justify-content-between align-items-center gap-2 flex-wrap">
                                                                    <div>
                                                                        <div class="fw-semibold"><?= esc($docLabel) ?> v<?= esc((string) ($doc['versao'] ?? 1)) ?></div>
                                                                        <div class="text-muted"><?= esc(formatDate($doc['created_at'] ?? '', true)) ?></div>
                                                                    </div>
                                                                    <div class="d-flex gap-1">
                                                                        <button type="button" class="btn btn-sm btn-outline-secondary" data-pdf-preview-url="<?= esc(base_url($doc['arquivo'])) ?>" data-pdf-preview-title="<?= esc($docLabel . ' v' . (string) ($doc['versao'] ?? 1)) ?>" title="Visualizar PDF">
                                                                            <i class="bi bi-eye"></i>
                                                                        </button>
                                                                        <a class="btn btn-sm btn-outline-primary" href="<?= base_url($doc['arquivo']) ?>" target="_blank" rel="noopener" title="Baixar PDF">
                                                                            <i class="bi bi-download"></i>
                                                                        </a>
                                                                        <?php if (can('os', 'editar')): ?>
                                                                            <form action="<?= $isBudgetDoc ? esc($orcamentoWhatsappSendUrl) : esc(base_url('os/whatsapp/' . $os['id']) . $embedQuery) ?>" method="POST" class="d-inline">
                                                                                <?= csrf_field() ?>
                                                                                <?php if ($isBudgetDoc): ?>
                                                                                    <input type="hidden" name="telefone_contato" value="<?= esc((string) ($orcamentoVinculado['telefone_contato'] ?? $os['cliente_telefone'] ?? '')) ?>">
                                                                                <?php else: ?>
                                                                                    <input type="hidden" name="telefone" value="<?= esc($os['cliente_telefone'] ?? '') ?>">
                                                                                    <input type="hidden" name="documento_id" value="<?= esc((string) ($doc['id'] ?? '')) ?>">
                                                                                    <input type="hidden" name="template_codigo" value="<?= esc($templateByDocumento[$docType] ?? 'os_aberta') ?>">
                                                                                <?php endif; ?>
                                                                                <button type="submit" class="btn btn-sm btn-outline-success" title="Enviar PDF por WhatsApp" <?= $isBudgetDoc && $orcamentoDispatchBlocked ? 'disabled' : '' ?>>
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

                                        <div class="col-12 col-xl-6 col-xxl-4">
                                            <div class="card glass-card h-100">
                                                <div class="card-header d-flex justify-content-between align-items-center">
                                                    <h5 class="card-title mb-0"><i class="bi bi-whatsapp me-2"></i>Enviar por WhatsApp</h5>
                                                </div>
                                                <div class="card-body">
                                                    <?php if (can('os', 'editar')): ?>
                                                        <form action="<?= base_url('os/whatsapp/' . $os['id']) ?><?= $embedQuery ?>" method="POST" class="d-flex flex-column gap-2 mb-3" id="osWhatsappForm" data-os-action="<?= esc(base_url('os/whatsapp/' . $os['id']) . $embedQuery) ?>" data-budget-action="<?= esc($orcamentoWhatsappSendUrl) ?>" data-budget-blocked="<?= $orcamentoDispatchBlocked ? '1' : '0' ?>" data-os-default-message="" data-budget-default-message="<?= esc($orcamentoWhatsappDefaultMessage) ?>">
                                                            <?= csrf_field() ?>
                                                            <div id="osWhatsappTemplateGroup">
                                                                <select name="template_codigo" class="form-select form-select-sm" id="osWhatsappTemplateSelect">
                                                                    <option value="">Template...</option>
                                                                    <?php foreach (($whatsappTemplates ?? []) as $tpl): ?>
                                                                        <option value="<?= esc($tpl['codigo']) ?>"><?= esc($tpl['nome']) ?></option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                            <select name="documento_id" class="form-select form-select-sm" id="osWhatsappDocumentoSelect">
                                                                <option value="">Gerar PDF consolidado da impressao (A4)</option>
                                                                <?php foreach ($documentosDaOs as $doc): ?>
                                                                    <?php
                                                                    $docType = (string) ($doc['tipo_documento'] ?? '');
                                                                    $docLabel = $documentLabelByCode[$docType] ?? ucwords(str_replace('_', ' ', $docType ?: 'pdf'));
                                                                    ?>
                                                                    <option value="<?= esc((string) ($doc['id'] ?? '')) ?>" data-document-type="<?= esc($docType) ?>" <?= (int) ($doc['id'] ?? 0) === $primeiroDocumentoOsId ? 'selected' : '' ?>>
                                                                        <?= esc($docLabel) ?> v<?= esc((string) ($doc['versao'] ?? 1)) ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                            <textarea name="mensagem_manual" id="osWhatsappMensagemInput" class="form-control form-control-sm" rows="2" placeholder="Mensagem manual (opcional)"></textarea>
                                                            <input type="text" name="telefone" id="osWhatsappTelefoneInput" class="form-control form-control-sm" value="<?= esc($os['cliente_telefone'] ?? '') ?>" placeholder="Telefone destino">
                                                            <div class="small text-muted" id="osWhatsappDispatchHint">Se nenhum PDF salvo for selecionado, o sistema gera automaticamente o PDF consolidado no mesmo padrao da impressao A4.</div>
                                                            <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-send me-1"></i>Enviar</button>
                                                        </form>
                                                    <?php else: ?>
                                                        <p class="text-muted small">Sem permissão para envio manual.</p>
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

                                        <div class="col-12 col-xxl-4">
                                            <div class="card glass-card h-100">
                                                <div class="card-header d-flex justify-content-between align-items-center">
                                                    <h5 class="card-title mb-0"><i class="bi bi-envelope me-2"></i>Enviar por E-mail</h5>
                                                </div>
                                                <div class="card-body">
                                                    <?php if (can('os', 'editar')): ?>
                                                        <form action="<?= base_url('os/email/' . $os['id'] . '/enviar') ?><?= $embedQuery ?>" method="POST" class="d-flex flex-column gap-2" id="osEmailForm" data-os-action="<?= esc(base_url('os/email/' . $os['id'] . '/enviar') . $embedQuery) ?>" data-budget-action="<?= esc($orcamentoEmailSendUrl) ?>" data-budget-blocked="<?= $orcamentoDispatchBlocked ? '1' : '0' ?>" data-os-default-subject="<?= esc($emailDefaultSubject) ?>" data-budget-default-subject="<?= esc($orcamentoEmailDefaultSubject) ?>" data-os-default-message="<?= esc($emailDefaultMessage) ?>">
                                                            <?= csrf_field() ?>
                                                            <input type="email" name="email_destino" id="osE-mailDestinoInput" class="form-control form-control-sm" value="<?= esc($emailDefaultTo) ?>" placeholder="E-mail destino" required>
                                                            <select name="documento_id" class="form-select form-select-sm" id="osE-mailDocumentoSelect" required>
                                                                <option value="">Selecione o PDF...</option>
                                                                <?php foreach ($documentosDaOs as $doc): ?>
                                                                    <?php
                                                                    $docType = (string) ($doc['tipo_documento'] ?? '');
                                                                    $docLabel = $documentLabelByCode[$docType] ?? ucwords(str_replace('_', ' ', $docType ?: 'pdf'));
                                                                    ?>
                                                                    <option value="<?= esc((string) ($doc['id'] ?? '')) ?>" data-document-type="<?= esc($docType) ?>" <?= (int) ($doc['id'] ?? 0) === $primeiroDocumentoOsId ? 'selected' : '' ?>>
                                                                        <?= esc($docLabel) ?> v<?= esc((string) ($doc['versao'] ?? 1)) ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                            <input type="text" name="assunto_email" id="osE-mailSubjectInput" class="form-control form-control-sm" value="<?= esc($emailDefaultSubject) ?>" placeholder="Assunto do e-mail">
                                                            <textarea name="mensagem_email" id="osE-mailMessageInput" class="form-control form-control-sm" rows="5" placeholder="Mensagem do e-mail"><?= esc($emailDefaultMessage) ?></textarea>
                                                            <div class="small text-muted" id="osE-mailDispatchHint">O PDF selecionado será anexado ao e-mail usando a configuração SMTP do ERP.</div>
                                                            <button type="submit" class="btn btn-sm btn-primary" <?= empty($documentosDaOs) ? 'disabled' : '' ?>>
                                                                <i class="bi bi-envelope-paper me-1"></i>Enviar E-mail
                                                            </button>
                                                        </form>
                                                        <?php if (empty($documentosDaOs)): ?>
                                                            <p class="text-muted mb-0 small mt-3">Gere ao menos um PDF da OS para habilitar o envio por e-mail.</p>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <p class="text-muted small mb-0">Sem permissão para envio manual por e-mail.</p>
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
                                                    <span class="finance-label">Mão de Obra</span>
                                                    <span class="finance-value"><?= esc(formatMoney($os['valor_mao_obra'] ?? 0)) ?></span>
                                                </div>
                                                <div class="finance-item">
                                                    <span class="finance-label">Peças</span>
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
                                                <div class="info-card-title"><i class="bi bi-receipt"></i>Resumo Financeiro do Orçamento</div>
                                                <?php if (!$hasOrçamentoVinculado): ?>
                                                    <p class="mb-0 text-muted">Nenhum orçamento vinculado para detalhar nesta OS.</p>
                                                <?php else: ?>
                                                    <div class="finance-item">
                                                        <span class="finance-label">Número</span>
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
                                                        <span class="finance-label">Acréscimo</span>
                                                        <span class="finance-value">+ <?= esc(formatMoney($orcamentoVinculado['acrescimo'] ?? 0)) ?></span>
                                                    </div>
                                                    <hr>
                                                    <div class="finance-item">
                                                        <span class="finance-label"><strong>Total do orçamento</strong></span>
                                                        <span class="finance-value text-success"><strong><?= esc(formatMoney($orcamentoVinculado['total'] ?? 0)) ?></strong></span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <div class="col-12 col-xl-6">
                                            <div class="info-card h-100">
                                                <div class="info-card-title"><i class="bi bi-calendar-check"></i>Datas e Aprovações</div>
                                                <div class="finance-item">
                                                    <span class="finance-label">Abertura</span>
                                                    <span class="finance-value"><?= esc(formatDate($os['data_abertura'] ?? '', true)) ?></span>
                                                </div>
                                                <div class="finance-item">
                                                    <span class="finance-label">Entrada</span>
                                                    <span class="finance-value"><?= esc(formatDate($os['data_entrada'] ?? '', true)) ?></span>
                                                </div>
                                                <div class="finance-item">
                                                    <span class="finance-label">Previsão</span>
                                                    <span class="finance-value"><?= esc(formatDate($os['data_previsao'] ?? '')) ?></span>
                                                </div>
                                                <div class="finance-item">
                                                    <span class="finance-label">Conclusão</span>
                                                    <span class="finance-value"><?= esc(formatDate($os['data_conclusao'] ?? '')) ?></span>
                                                </div>
                                                <div class="finance-item">
                                                    <span class="finance-label">Entrega</span>
                                                    <span class="finance-value"><?= esc(formatDate($os['data_entrega'] ?? '')) ?></span>
                                                </div>
                                                <?php if (!empty($os['data_aprovacao'])): ?>
                                                <div class="finance-item">
                                                    <span class="finance-label">Aprovação da OS</span>
                                                    <span class="finance-value"><?= esc(formatDate($os['data_aprovacao'], true)) ?></span>
                                                </div>
                                                <?php endif; ?>
                                                <?php if ($hasOrçamentoVinculado): ?>
                                                    <hr>
                                                    <div class="finance-item">
                                                        <span class="finance-label">Orçamento criado</span>
                                                        <span class="finance-value"><?= esc(formatDate($orcamentoVinculado['created_at'] ?? '', true)) ?></span>
                                                    </div>
                                                    <div class="finance-item">
                                                        <span class="finance-label">Orçamento enviado</span>
                                                        <span class="finance-value"><?= esc(formatDate($orcamentoVinculado['enviado_em'] ?? '', true)) ?></span>
                                                    </div>
                                                    <div class="finance-item">
                                                        <span class="finance-label">Orçamento aprovado</span>
                                                        <span class="finance-value"><?= esc(formatDate($orcamentoVinculado['aprovado_em'] ?? '', true)) ?></span>
                                                    </div>
                                                    <div class="finance-item">
                                                        <span class="finance-label">Orçamento rejeitado</span>
                                                        <span class="finance-value"><?= esc(formatDate($orcamentoVinculado['rejeitado_em'] ?? '', true)) ?></span>
                                                    </div>
                                                    <div class="finance-item">
                                                        <span class="finance-label">Orçamento cancelado</span>
                                                        <span class="finance-value"><?= esc(formatDate($orcamentoVinculado['cancelado_em'] ?? '', true)) ?></span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <div class="col-12 col-xl-6">
                                            <div class="info-card h-100">
                                                <div class="info-card-title"><i class="bi bi-wallet2"></i>Complementos Financeiros</div>
                                                <div class="finance-item">
                                                    <span class="finance-label">Orçamento aprovado</span>
                                                    <span class="finance-value"><?= !empty($os['orcamento_aprovado']) ? 'Sim' : 'Não' ?></span>
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
                                                        <span class="finance-label">Validade do orçamento</span>
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

        </div>
    </div>

    <div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content bg-transparent border-0">
                <div class="modal-body text-center p-0 position-relative">
                    <div class="d-inline-block position-relative">
                        <button type="button" class="btn-close position-absolute os-show-modal-close" data-bs-dismiss="modal" aria-label="Close"></button>
    <img src="" id="modalImagePreview" class="img-fluid rounded shadow-lg os-show-modal-image" alt="Visualização ampliada">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="pdfPreviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl modal-fullscreen-lg-down">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header">
                    <h5 class="modal-title" id="pdfPreviewModalTitle">Visualizar PDF</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body p-0 bg-body-tertiary">
                    <iframe id="pdfPreviewFrame" title="Pré-visualização do PDF" style="width:100%;height:min(80vh,900px);border:0;"></iframe>
                </div>
            </div>
        </div>
    </div>

    <div
        class="modal fade"
        id="osPrintPreviewModal"
        tabindex="-1"
        aria-hidden="true"
        data-preview-base-url="<?= esc($printPreviewBaseUrl) ?>"
        data-whatsapp-url="<?= esc($printWhatsAppUrl) ?>"
    >
        <div class="modal-dialog modal-dialog-centered modal-xl modal-fullscreen-xl-down">
            <div class="modal-content border-0 shadow-lg overflow-hidden">
                <div class="modal-header">
                    <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap w-100">
                        <div>
                            <h5 class="modal-title mb-1" id="osPrintPreviewModalTitle">Impressao consolidada da OS</h5>
                            <div class="small text-muted">A pre-visualizacao abaixo replica o mesmo documento consolidado usado na impressao final.</div>
                        </div>
                        <div class="os-print-preview-header-tools">
                            <span class="os-print-preview-format-pill" id="osPrintPreviewFormatBadge">
                                <i class="bi bi-file-earmark-text"></i>
                                <span>Folha A4</span>
                            </span>
                            <a href="<?= esc($printPreviewBaseUrl) ?>" target="_blank" rel="noopener" class="btn btn-outline-secondary btn-sm" id="osPrintPreviewOpenButton">
                                <i class="bi bi-box-arrow-up-right me-1"></i>Abrir em nova guia
                            </a>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                        </div>
                    </div>
                </div>
                <div class="modal-body p-0">
                    <div class="os-print-preview-layout">
                        <section class="os-print-preview-frame-wrap">
                            <div class="os-print-preview-loading" id="osPrintPreviewLoading">
                                <div class="text-center">
                                    <div class="spinner-border text-primary" role="status"></div>
                                    <div class="small text-muted mt-2">Montando a pre-visualizacao da impressao...</div>
                                </div>
                            </div>
                            <iframe
                                id="osPrintPreviewFrame"
                                class="os-print-preview-frame"
                                title="Pre-visualizacao da impressao da ordem de servico"
                                src="about:blank"
                            ></iframe>
                        </section>
                    </div>
                </div>
                <div class="modal-footer os-print-preview-footer">
                    <div class="os-print-preview-footer-meta">
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input" type="checkbox" role="switch" id="osPrintPreviewIncludePhotos">
                            <label class="form-check-label fw-semibold" for="osPrintPreviewIncludePhotos">Incluir fotos no documento</label>
                        </div>
                        <div class="os-print-preview-photo-hint" id="osPrintPreviewPhotoHint">
                            No modelo A4, a foto principal de perfil aparece ao lado esquerdo do bloco de equipamento quando habilitada. As demais fotos entram ao fim do documento por tipo.
                        </div>
                    </div>
                    <div class="os-print-preview-footer-actions">
                        <?php if (can('os', 'editar')): ?>
                            <button type="button" class="btn btn-outline-success" id="osPrintPreviewWhatsappButton">
                                <i class="bi bi-whatsapp me-1"></i>Enviar PDF por WhatsApp
                            </button>
                        <?php endif; ?>
                        <button type="button" class="btn btn-primary" id="osPrintPreviewPrintButton">
                            <i class="bi bi-printer me-1"></i>Imprimir agora
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (can('os', 'editar')): ?>
        <div class="modal fade" id="osPrintWhatsappModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg modal-fullscreen-sm-down">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title mb-1">Enviar PDF por WhatsApp</h5>
                            <div class="small text-muted">A mensagem pode ser personalizada a partir dos templates cadastrados na Gestao do Conhecimento.</div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <a href="<?= esc($knowledgeWhatsappTemplatesUrl) ?>" class="btn btn-sm btn-outline-success" target="_blank" rel="noopener">
                                Templates
                            </a>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                        </div>
                    </div>
                    <div class="modal-body p-3 p-lg-4 bg-body-tertiary">
                        <section class="os-print-whatsapp-panel p-3 p-lg-4">
                            <form action="<?= esc($printWhatsAppUrl) ?>" method="POST" id="osPrintWhatsappForm" class="d-grid gap-2">
                                <?= csrf_field() ?>
                                <input type="hidden" name="print_formato" id="osPrintWhatsappFormatInput" value="a4">
                                <input type="hidden" name="print_incluir_fotos" id="osPrintWhatsappIncludePhotosInput" value="0">
                                <div>
                                    <label for="osPrintWhatsappTemplateSelect" class="form-label small fw-semibold mb-1">Template base</label>
                                    <select name="template_codigo" id="osPrintWhatsappTemplateSelect" class="form-select form-select-sm">
                                        <option value="">Selecione um template ou escreva manualmente</option>
                                        <?php foreach (($whatsappTemplates ?? []) as $tpl): ?>
                                            <option
                                                value="<?= esc((string) ($tpl['codigo'] ?? '')) ?>"
                                                data-template-content="<?= esc((string) ($tpl['conteudo'] ?? '')) ?>"
                                            >
                                                <?= esc((string) ($tpl['nome'] ?? $tpl['codigo'] ?? 'Template')) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label for="osPrintWhatsappMessageInput" class="form-label small fw-semibold mb-1">Mensagem</label>
                                    <textarea name="mensagem_manual" id="osPrintWhatsappMessageInput" class="form-control form-control-sm" rows="5" placeholder="Escolha um template acima ou escreva a mensagem personalizada."></textarea>
                                </div>
                                <div>
                                    <label for="osPrintWhatsappPhoneInput" class="form-label small fw-semibold mb-1">Telefone de destino</label>
                                    <input type="text" name="telefone" id="osPrintWhatsappPhoneInput" class="form-control form-control-sm" value="<?= esc((string) ($os['cliente_telefone'] ?? '')) ?>" placeholder="DDD + numero">
                                </div>
                                <div class="small text-muted" id="osPrintWhatsappHint">O PDF sera gerado no formato exibido na pre-visualizacao e enviado como anexo.</div>
                                <button type="submit" class="btn btn-success" id="osPrintWhatsappSubmitButton">
                                    <i class="bi bi-send me-1"></i>Enviar PDF pelo WhatsApp
                                </button>
                            </form>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="modal fade" id="orcamentoFrameModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl modal-fullscreen-lg-down">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header">
                    <h5 class="modal-title" id="orcamentoFrameModalTitle">Orçamento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body p-0 position-relative">
                    <div id="orcamentoFrameModalLoading" class="position-absolute top-50 start-50 translate-middle z-3 text-center">
                        <div class="spinner-border text-primary" role="status"></div>
                        <div class="small text-muted mt-2">Abrindo orçamento...</div>
                    </div>
                    <iframe id="orcamentoFrameModalFrame" title="Fluxo de orçamento" style="width:100%;height:min(84vh,980px);border:0;"></iframe>
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
        const pdfPreviewModal = document.getElementById('pdfPreviewModal');
        const pdfPreviewFrame = document.getElementById('pdfPreviewFrame');
        const pdfPreviewTitle = document.getElementById('pdfPreviewModalTitle');
        const budgetFrameModalElement = document.getElementById('orcamentoFrameModal');
        const budgetFrame = document.getElementById('orcamentoFrameModalFrame');
        const budgetFrameLoading = document.getElementById('orcamentoFrameModalLoading');
        const budgetFrameTitle = document.getElementById('orcamentoFrameModalTitle');
        const generateForm = document.getElementById('osPdfGenerateForm');
        const generateTypeSelect = document.getElementById('osPdfTipoSelect');
        const whatsappForm = document.getElementById('osWhatsappForm');
        const whatsappTemplateGroup = document.getElementById('osWhatsappTemplateGroup');
        const whatsappTemplateSelect = document.getElementById('osWhatsappTemplateSelect');
        const whatsappDocumentSelect = document.getElementById('osWhatsappDocumentoSelect');
        const whatsappMessageInput = document.getElementById('osWhatsappMensagemInput');
        const whatsappPhoneInput = document.getElementById('osWhatsappTelefoneInput');
        const whatsappHint = document.getElementById('osWhatsappDispatchHint');
        const emailForm = document.getElementById('osEmailForm');
        const emailDocumentSelect = document.getElementById('osE-mailDocumentoSelect');
        const emailDestinationInput = document.getElementById('osE-mailDestinoInput');
        const emailSubjectInput = document.getElementById('osE-mailSubjectInput');
        const emailMessageInput = document.getElementById('osE-mailMessageInput');
        const emailHint = document.getElementById('osE-mailDispatchHint');
        const printPreviewModalElement = document.getElementById('osPrintPreviewModal');
        const printPreviewFrame = document.getElementById('osPrintPreviewFrame');
        const printPreviewLoading = document.getElementById('osPrintPreviewLoading');
        const printPreviewOpenButton = document.getElementById('osPrintPreviewOpenButton');
        const printPreviewPrintButton = document.getElementById('osPrintPreviewPrintButton');
        const printPreviewFormatBadge = document.getElementById('osPrintPreviewFormatBadge');
        const printPreviewIncludePhotos = document.getElementById('osPrintPreviewIncludePhotos');
        const printPreviewPhotoHint = document.getElementById('osPrintPreviewPhotoHint');
        const printTriggerButtons = Array.from(document.querySelectorAll('[data-os-print-trigger]'));
        const printPreviewWhatsappButton = document.getElementById('osPrintPreviewWhatsappButton');
        const printWhatsappModalElement = document.getElementById('osPrintWhatsappModal');
        const printWhatsappForm = document.getElementById('osPrintWhatsappForm');
        const printWhatsappTemplateSelect = document.getElementById('osPrintWhatsappTemplateSelect');
        const printWhatsappMessageInput = document.getElementById('osPrintWhatsappMessageInput');
        const printWhatsappPhoneInput = document.getElementById('osPrintWhatsappPhoneInput');
        const printWhatsappHint = document.getElementById('osPrintWhatsappHint');
        const printWhatsappSubmitButton = document.getElementById('osPrintWhatsappSubmitButton');
        const printWhatsappFormatInput = document.getElementById('osPrintWhatsappFormatInput');
        const printWhatsappIncludePhotosInput = document.getElementById('osPrintWhatsappIncludePhotosInput');
        const csrfTokenName = <?= json_encode(csrf_token()) ?>;
        let csrfTokenValue = <?= json_encode(csrf_hash()) ?>;
        const printTemplatePreviewVars = <?= json_encode([
            'numero_os' => (string) ($os['numero_os'] ?? ''),
            'data_abertura' => !empty($os['data_abertura']) ? formatDate($os['data_abertura'], true) : '',
            'equipamento' => trim((string) (($os['equip_marca'] ?? '') . ' ' . ($os['equip_modelo'] ?? ''))),
            'cliente' => (string) ($os['cliente_nome'] ?? ''),
            'valor_final' => formatMoney($os['valor_final'] ?? 0),
            'status' => (string) ($os['status'] ?? ''),
            'pdf_url' => 'PDF em anexo nesta mensagem.',
        ], JSON_UNESCAPED_UNICODE) ?>;
        const printFormatLabels = <?= json_encode($printFormats ?? [], JSON_UNESCAPED_UNICODE) ?>;

        if (imageModal) {
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
        }

        const budgetFrameModal = budgetFrameModalElement && window.bootstrap
            ? window.bootstrap.Modal.getOrCreateInstance(budgetFrameModalElement)
            : null;
        const pdfModal = pdfPreviewModal && window.bootstrap
            ? window.bootstrap.Modal.getOrCreateInstance(pdfPreviewModal)
            : null;
        const printPreviewModal = printPreviewModalElement && window.bootstrap
            ? window.bootstrap.Modal.getOrCreateInstance(printPreviewModalElement)
            : null;
        const printWhatsappModal = printWhatsappModalElement && window.bootstrap
            ? window.bootstrap.Modal.getOrCreateInstance(printWhatsappModalElement)
            : null;
        let printPreviewTransitionToWhatsapp = false;
        let reopenPrintPreviewAfterWhatsapp = false;

        const setBudgetFrameLoading = (isLoading) => {
            if (budgetFrameLoading) {
                budgetFrameLoading.classList.toggle('d-none', !isLoading);
            }
        };

        const openBudgetFrameModal = (url, title) => {
            if (!budgetFrameModal || !budgetFrame || !url) {
                window.location.href = url;
                return;
            }

            setBudgetFrameLoading(true);
            budgetFrameTitle && (budgetFrameTitle.textContent = title || 'Orçamento');
            budgetFrame.src = 'about:blank';
            budgetFrameModal.show();
            budgetFrame.src = url;
        };

        budgetFrame?.addEventListener('load', function() {
            setBudgetFrameLoading(false);
        });

        budgetFrameModalElement?.addEventListener('hidden.bs.modal', function() {
            setBudgetFrameLoading(false);
            if (budgetFrame) {
                budgetFrame.src = 'about:blank';
            }
        });

        document.addEventListener('click', function(event) {
            const previewTrigger = event.target.closest('[data-pdf-preview-url]');
            if (previewTrigger && pdfModal && pdfPreviewFrame) {
                event.preventDefault();
                const previewUrl = previewTrigger.getAttribute('data-pdf-preview-url') || '';
                const previewTitleText = previewTrigger.getAttribute('data-pdf-preview-title') || 'Visualizar PDF';
                if (pdfPreviewTitle) {
                    pdfPreviewTitle.textContent = previewTitleText;
                }
                pdfPreviewFrame.src = previewUrl;
                pdfModal.show();
                return;
            }

            const budgetTrigger = event.target.closest('[data-open-budget-modal-url]');
            if (budgetTrigger) {
                event.preventDefault();
                openBudgetFrameModal(
                    budgetTrigger.getAttribute('data-open-budget-modal-url') || '',
                    budgetTrigger.getAttribute('data-open-budget-modal-title') || 'Orçamento'
                );
            }
        });

        pdfPreviewModal?.addEventListener('hidden.bs.modal', function() {
            if (pdfPreviewFrame) {
                pdfPreviewFrame.src = 'about:blank';
            }
        });

        const ensureHiddenInput = (form, name, value) => {
            if (!form) {
                return;
            }
            let input = form.querySelector('input[type="hidden"][name="' + name + '"]');
            if (!input) {
                input = document.createElement('input');
                input.type = 'hidden';
                input.name = name;
                form.appendChild(input);
            }
            input.value = value;
        };

        const appendCsrfToFormData = (formData) => {
            if (formData instanceof FormData && csrfTokenName && csrfTokenValue && !formData.has(csrfTokenName)) {
                formData.append(csrfTokenName, csrfTokenValue);
            }
            return formData;
        };

        const syncCsrfHashFromPayload = (payload) => {
            const nextHash = typeof payload?.csrfHash === 'string' ? payload.csrfHash.trim() : '';
            if (!nextHash) {
                return;
            }

            csrfTokenValue = nextHash;
            document.querySelectorAll(`input[name="${csrfTokenName}"]`).forEach((input) => {
                input.value = nextHash;
            });
        };

        const showAlert = async (options) => {
            if (window.Swal && typeof window.Swal.fire === 'function') {
                return window.Swal.fire(options);
            }

            const fallbackMessage = typeof options === 'string'
                ? options
                : `${options?.title || 'Aviso'}\n\n${options?.text || ''}`.trim();
            window.alert(fallbackMessage);
            return { isConfirmed: true };
        };

        const setButtonBusy = (button, isBusy, busyHtml) => {
            if (!button) {
                return;
            }

            if (!button.dataset.originalHtml) {
                button.dataset.originalHtml = button.innerHTML;
            }

            button.disabled = isBusy;
            button.innerHTML = isBusy
                ? busyHtml
                : (button.dataset.originalHtml || button.innerHTML);
        };

        const selectedDocumentType = (select) => {
            const option = select?.options?.[select.selectedIndex];
            return String(option?.dataset?.documentType || '').trim();
        };

        const printPreviewState = {
            format: 'a4',
            includePhotos: false,
        };

        const setPrintPreviewLoading = (isLoading) => {
            if (printPreviewLoading) {
                printPreviewLoading.classList.toggle('d-none', !isLoading);
            }
        };

        const renderPrintTemplateMessage = (templateContent) => {
            let message = String(templateContent || '');
            Object.entries(printTemplatePreviewVars || {}).forEach(([key, value]) => {
                message = message.split(`{{${key}}}`).join(String(value ?? ''));
            });
            return message.trim();
        };

        const buildPrintDocumentUrl = (formatCode, includePhotos, extraParams = {}) => {
            const previewBaseUrl = printPreviewModalElement?.dataset?.previewBaseUrl || '<?= esc($printPreviewBaseUrl) ?>';
            const previewUrl = new URL(previewBaseUrl, window.location.origin);
            previewUrl.searchParams.set('formato', String(formatCode || 'a4'));
            previewUrl.searchParams.set('incluir_fotos', includePhotos ? '1' : '0');
            Object.entries(extraParams || {}).forEach(([key, value]) => {
                if (value === null || typeof value === 'undefined') {
                    return;
                }
                previewUrl.searchParams.set(String(key), String(value));
            });
            if (!previewUrl.searchParams.has('_ts')) {
                previewUrl.searchParams.set('_ts', String(Date.now()));
            }
            return previewUrl.toString();
        };

        const buildPrintPreviewUrl = () => buildPrintDocumentUrl(
            printPreviewState.format || 'a4',
            !!printPreviewState.includePhotos
        );

        const syncPrintPreviewControls = () => {
            const activeFormat = printPreviewState.format || 'a4';
            printTriggerButtons.forEach((button) => {
                const isActive = (button.getAttribute('data-print-format') || 'a4') === activeFormat;
                button.classList.toggle('active', isActive);
                button.setAttribute('aria-current', isActive ? 'true' : 'false');
            });

            if (printPreviewFormatBadge) {
                const label = String(printFormatLabels?.[activeFormat] || 'Folha A4');
                printPreviewFormatBadge.innerHTML = `<i class="bi bi-file-earmark-text"></i><span>${label}</span>`;
            }
            if (printPreviewIncludePhotos) {
                printPreviewIncludePhotos.checked = !!printPreviewState.includePhotos;
            }
            if (printWhatsappFormatInput) {
                printWhatsappFormatInput.value = activeFormat;
            }
            if (printWhatsappIncludePhotosInput) {
                printWhatsappIncludePhotosInput.value = printPreviewState.includePhotos ? '1' : '0';
            }
            if (printPreviewPhotoHint) {
                printPreviewPhotoHint.textContent = activeFormat === '80mm'
                    ? 'Na bobina 80mm as fotos entram na galeria final quando habilitadas, priorizando legibilidade e rolagem continua.'
                    : 'No modelo A4, a foto principal de perfil aparece ao lado esquerdo do bloco de equipamento quando habilitada. As demais fotos entram ao fim do documento por tipo.';
            }
            if (printWhatsappHint) {
                const baseHint = 'O PDF sera gerado no formato exibido na pre-visualizacao e enviado como anexo.';
                printWhatsappHint.textContent = printPreviewState.includePhotos
                    ? `${baseHint} As fotos selecionadas tambem acompanham o PDF.`
                    : baseHint;
            }
        };

        const refreshPrintPreview = () => {
            if (!printPreviewFrame) {
                return;
            }

            syncPrintPreviewControls();
            const previewUrl = buildPrintPreviewUrl();
            if (printPreviewOpenButton) {
                printPreviewOpenButton.href = previewUrl;
            }
            setPrintPreviewLoading(true);
            printPreviewFrame.src = previewUrl;
        };

        const openPrintPreviewModal = (formatCode) => {
            if (formatCode) {
                printPreviewState.format = String(formatCode);
            }

            printPreviewTransitionToWhatsapp = false;
            reopenPrintPreviewAfterWhatsapp = false;
            syncPrintPreviewControls();
            if (!printPreviewModal || !printPreviewFrame) {
                window.open(buildPrintPreviewUrl(), '_blank', 'noopener');
                return;
            }
            if (printPreviewModal) {
                printPreviewModal.show();
            }
            refreshPrintPreview();
        };

        const openThermalPrintDialog = () => {
            const thermalUrl = buildPrintDocumentUrl('80mm', false, {
                auto_print: 1,
            });

            const thermalWindow = window.open(thermalUrl, '_blank', 'noopener');
            if (!thermalWindow) {
                window.location.href = thermalUrl;
            }
        };

        printPreviewIncludePhotos?.addEventListener('change', function() {
            printPreviewState.includePhotos = !!this.checked;
            refreshPrintPreview();
        });

        printTriggerButtons.forEach((button) => {
            button.addEventListener('click', function() {
                const formatCode = this.getAttribute('data-print-format') || 'a4';
                if (formatCode === '80mm') {
                    openThermalPrintDialog();
                    return;
                }

                openPrintPreviewModal(formatCode);
            });
        });

        printPreviewFrame?.addEventListener('load', function() {
            setPrintPreviewLoading(false);
        });

        printPreviewModalElement?.addEventListener('hidden.bs.modal', function() {
            setPrintPreviewLoading(false);
            if (printPreviewFrame && !printPreviewTransitionToWhatsapp) {
                printPreviewFrame.src = 'about:blank';
            }
            if (printPreviewTransitionToWhatsapp && printWhatsappModal) {
                printPreviewTransitionToWhatsapp = false;
                printWhatsappModal.show();
            }
        });

        printPreviewWhatsappButton?.addEventListener('click', function() {
            if (!printWhatsappModal) {
                return;
            }

            if (printWhatsappFormatInput) {
                printWhatsappFormatInput.value = printPreviewState.format || 'a4';
            }
            if (printWhatsappIncludePhotosInput) {
                printWhatsappIncludePhotosInput.value = printPreviewState.includePhotos ? '1' : '0';
            }

            reopenPrintPreviewAfterWhatsapp = true;
            printPreviewTransitionToWhatsapp = true;
            if (printPreviewModal) {
                printPreviewModal.hide();
                return;
            }

            printPreviewTransitionToWhatsapp = false;
            printWhatsappModal.show();
        });

        printWhatsappModalElement?.addEventListener('hidden.bs.modal', function() {
            if (reopenPrintPreviewAfterWhatsapp && printPreviewModal) {
                reopenPrintPreviewAfterWhatsapp = false;
                printPreviewModal.show();
                return;
            }

            reopenPrintPreviewAfterWhatsapp = false;
        });

        printPreviewPrintButton?.addEventListener('click', function() {
            if (!printPreviewFrame?.contentWindow) {
                window.open(buildPrintPreviewUrl(), '_blank', 'noopener');
                return;
            }

            try {
                printPreviewFrame.contentWindow.focus();
                printPreviewFrame.contentWindow.print();
            } catch (error) {
                console.error('[OS print preview] Falha ao acionar a impressao pelo iframe.', error);
                window.open(buildPrintPreviewUrl(), '_blank', 'noopener');
            }
        });

        printWhatsappTemplateSelect?.addEventListener('change', function() {
            const selectedOption = this.options?.[this.selectedIndex];
            const templateContent = selectedOption?.dataset?.templateContent || '';
            if (!printWhatsappMessageInput) {
                return;
            }

            printWhatsappMessageInput.value = templateContent
                ? renderPrintTemplateMessage(templateContent)
                : '';
        });

        printWhatsappForm?.addEventListener('submit', async function(event) {
            event.preventDefault();

            const phoneValue = String(printWhatsappPhoneInput?.value || '').trim();
            const messageValue = String(printWhatsappMessageInput?.value || '').trim();
            const templateValue = String(printWhatsappTemplateSelect?.value || '').trim();

            if (phoneValue === '') {
                await showAlert({
                    icon: 'warning',
                    title: 'Telefone obrigatorio',
                    text: 'Informe o numero que recebera o PDF da ordem de servico.',
                });
                return;
            }

            if (messageValue === '' && templateValue === '') {
                await showAlert({
                    icon: 'warning',
                    title: 'Mensagem obrigatoria',
                    text: 'Escolha um template ou escreva uma mensagem antes de enviar o PDF pelo WhatsApp.',
                });
                return;
            }

            if (printWhatsappFormatInput) {
                printWhatsappFormatInput.value = printPreviewState.format || 'a4';
            }
            if (printWhatsappIncludePhotosInput) {
                printWhatsappIncludePhotosInput.value = printPreviewState.includePhotos ? '1' : '0';
            }

            const formData = appendCsrfToFormData(new FormData(printWhatsappForm));
            setButtonBusy(
                printWhatsappSubmitButton,
                true,
                '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Enviando PDF...'
            );

            try {
                const response = await fetch(printWhatsappForm.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: formData,
                });

                let payload = null;
                try {
                    payload = await response.json();
                } catch (parseError) {
                    payload = null;
                }

                syncCsrfHashFromPayload(payload);

                if (!response.ok || !payload?.ok) {
                    await showAlert({
                        icon: 'error',
                        title: 'Falha no envio',
                        text: String(payload?.message || 'Nao foi possivel enviar o PDF da OS pelo WhatsApp.'),
                    });
                    return;
                }

                await showAlert({
                    icon: payload?.duplicate ? 'info' : 'success',
                    title: payload?.duplicate ? 'Envio ja processado' : 'PDF enviado',
                    text: String(payload?.message || 'Mensagem WhatsApp enviada com sucesso.'),
                });
            } catch (error) {
                console.error('[OS print preview] Falha no envio do PDF por WhatsApp.', error);
                await showAlert({
                    icon: 'error',
                    title: 'Erro de comunicacao',
                    text: 'Nao foi possivel concluir o envio do PDF pelo WhatsApp.',
                });
            } finally {
                setButtonBusy(printWhatsappSubmitButton, false);
            }
        });

        const syncWhatsappFormMode = () => {
            if (!whatsappForm || !whatsappDocumentSelect) {
                return;
            }

            const isBudget = selectedDocumentType(whatsappDocumentSelect) === 'orcamento' && whatsappForm.dataset.budgetAction;
            const submitButton = whatsappForm.querySelector('button[type="submit"]');
            const budgetBlocked = String(whatsappForm.dataset.budgetBlocked || '0') === '1';
            whatsappForm.action = isBudget ? whatsappForm.dataset.budgetAction : whatsappForm.dataset.osAction;

            if (whatsappTemplateGroup) {
                whatsappTemplateGroup.classList.toggle('d-none', isBudget);
            }
            if (whatsappTemplateSelect) {
                whatsappTemplateSelect.disabled = isBudget;
            }

            if (isBudget) {
                ensureHiddenInput(whatsappForm, 'telefone_contato', whatsappPhoneInput?.value || '');
                ensureHiddenInput(whatsappForm, 'mensagem_whatsapp', whatsappMessageInput?.value || '');
                if (whatsappHint) {
                    whatsappHint.textContent = budgetBlocked
                        ? 'Este orçamento está bloqueado para novo envio no status atual.'
                        : 'Para orçamento, o envio segue o mesmo fluxo oficial do módulo de orçamentos, incluindo o link público de aprovação.';
                }
                if (whatsappMessageInput && whatsappMessageInput.value.trim() === '' && whatsappForm.dataset.budgetDefaultMessage) {
                    whatsappMessageInput.placeholder = whatsappForm.dataset.budgetDefaultMessage;
                }
                if (submitButton) {
                    submitButton.disabled = budgetBlocked;
                }
            } else if (whatsappHint) {
                whatsappHint.textContent = 'Se nenhum PDF salvo for selecionado, o sistema gera automaticamente o PDF consolidado no mesmo padrao da impressao A4.';
                if (submitButton) {
                    submitButton.disabled = false;
                }
            }
        };

        const syncEmailFormMode = () => {
            if (!emailForm || !emailDocumentSelect) {
                return;
            }

            const isBudget = selectedDocumentType(emailDocumentSelect) === 'orcamento' && emailForm.dataset.budgetAction;
            const submitButton = emailForm.querySelector('button[type="submit"]');
            const budgetBlocked = String(emailForm.dataset.budgetBlocked || '0') === '1';
            emailForm.action = isBudget ? emailForm.dataset.budgetAction : emailForm.dataset.osAction;

            if (isBudget) {
                ensureHiddenInput(emailForm, 'email_contato', emailDestinationInput?.value || '');

                if (emailSubjectInput) {
                    const osDefaultSubject = emailForm.dataset.osDefaultSubject || '';
                    const budgetDefaultSubject = emailForm.dataset.budgetDefaultSubject || '';
                    if (emailSubjectInput.value.trim() === '' || emailSubjectInput.value === osDefaultSubject) {
                        emailSubjectInput.value = budgetDefaultSubject;
                    }
                }

                if (emailMessageInput && (emailMessageInput.value.trim() === '' || emailMessageInput.value === (emailForm.dataset.osDefaultMessage || ''))) {
                    emailMessageInput.value = '';
                }

                if (emailHint) {
                    emailHint.textContent = budgetBlocked
                        ? 'Este orçamento está bloqueado para novo envio no status atual.'
                        : 'Ao selecionar orçamento, o envio usa o mesmo fluxo oficial do módulo de orçamentos e anexa o PDF oficial.';
                }
                if (submitButton) {
                    submitButton.disabled = budgetBlocked;
                }
            } else if (emailHint) {
                emailHint.textContent = 'O PDF selecionado será anexado ao e-mail usando a configuração SMTP do ERP.';
                if (emailSubjectInput) {
                    const budgetDefaultSubject = emailForm.dataset.budgetDefaultSubject || '';
                    if (emailSubjectInput.value === budgetDefaultSubject) {
                        emailSubjectInput.value = emailForm.dataset.osDefaultSubject || '';
                    }
                }
                if (submitButton && <?= empty($documentosDaOs) ? 'true' : 'false' ?> === false) {
                    submitButton.disabled = false;
                }
            }
        };

        whatsappDocumentSelect?.addEventListener('change', syncWhatsappFormMode);
        whatsappMessageInput?.addEventListener('input', function() {
            if (whatsappForm && selectedDocumentType(whatsappDocumentSelect) === 'orcamento') {
                ensureHiddenInput(whatsappForm, 'mensagem_whatsapp', whatsappMessageInput.value || '');
            }
        });
        whatsappPhoneInput?.addEventListener('input', function() {
            if (whatsappForm && selectedDocumentType(whatsappDocumentSelect) === 'orcamento') {
                ensureHiddenInput(whatsappForm, 'telefone_contato', whatsappPhoneInput.value || '');
            }
        });
        emailDocumentSelect?.addEventListener('change', syncEmailFormMode);
        emailDestinationInput?.addEventListener('input', function() {
            if (emailForm && selectedDocumentType(emailDocumentSelect) === 'orcamento') {
                ensureHiddenInput(emailForm, 'email_contato', emailDestinationInput.value || '');
            }
        });

        syncPrintPreviewControls();
        syncWhatsappFormMode();
        syncEmailFormMode();

        generateForm?.addEventListener('submit', async function(event) {
            const selectedType = String(generateTypeSelect?.value || '').trim();
            const hasBudget = String(generateForm.dataset.hasBudget || '0') === '1';
            if (selectedType !== 'orcamento' || hasBudget) {
                return;
            }

            event.preventDefault();
            if (!window.Swal || typeof window.Swal.fire !== 'function') {
                openBudgetFrameModal(generateForm.dataset.budgetCreateUrl || '', 'Criar orçamento');
                return;
            }

            const result = await window.Swal.fire({
                icon: 'warning',
                title: 'Crie primeiro o orçamento',
                text: 'Esta ordem de serviço ainda não possui orçamento vinculado. Deseja elaborar o orçamento agora?',
                showCancelButton: true,
                confirmButtonText: 'Sim, criar orçamento',
                cancelButtonText: 'Agora não',
            });

            if (result.isConfirmed) {
                openBudgetFrameModal(generateForm.dataset.budgetCreateUrl || '', 'Criar orçamento');
            }
        });

        window.addEventListener('message', function(event) {
            if (event.origin !== window.location.origin) {
                return;
            }

            const payload = event.data || {};
            if (payload.type !== 'os:orcamento-updated') {
                return;
            }

            budgetFrameModal?.hide();

            if (window.Swal && payload.message) {
                window.Swal.fire({
                    icon: 'success',
                    title: 'Orçamento atualizado',
                    text: String(payload.message || 'O orçamento foi salvo com sucesso.'),
                    timer: 1800,
                    showConfirmButton: false,
                }).then(function() {
                    window.location.reload();
                });
                return;
            }

            window.location.reload();
        });
    });
</script>
<?= $this->endSection() ?>
