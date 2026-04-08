<?php
$isEmbedded = (bool) ($isEmbedded ?? false);
$embedQuery = $isEmbedded ? '?embed=1' : '';
$currentStatusCode = (string) ($os['status'] ?? '');
$statusOptionsForView = !empty($statusOptions ?? []) ? $statusOptions : [];
if (empty($statusOptionsForView) && !empty($statusGrouped ?? [])) {
    foreach ($statusGrouped as $groupItems) {
        foreach ($groupItems as $statusItem) {
            $statusOptionsForView[] = $statusItem;
        }
    }
}
$nextStatusOptions = array_values(array_filter(
    $statusOptions ?? [],
    static fn (array $item): bool => ($item['codigo'] ?? '') !== '' && (string) ($item['codigo'] ?? '') !== $currentStatusCode
));
$primaryNextStatus = is_array($primaryNextStatus ?? null) ? $primaryNextStatus : null;
$hasClientPhone = trim((string) ($os['cliente_telefone'] ?? '')) !== '';
$canCancelDirectly = $currentStatusCode !== 'cancelado';
$notasLegadas = is_array($notasLegadas ?? null) ? $notasLegadas : [];
$legacyFinancialOrigins = is_array($legacyFinancialOrigins ?? null) ? $legacyFinancialOrigins : [];
$observacoesInternas = trim((string) ($os['observacoes_internas'] ?? ''));
$observacoesCliente = trim((string) ($os['observacoes_cliente'] ?? ''));
$formaPagamento = trim((string) ($os['forma_pagamento'] ?? ''));
$orcamentoQuickUrl = base_url('orcamentos/novo?' . http_build_query([
    'origem' => 'os',
    'os_id' => (int) ($os['id'] ?? 0),
    'cliente_id' => (int) ($os['cliente_id'] ?? 0),
    'equipamento_id' => (int) ($os['equipamento_id'] ?? 0),
    'telefone' => (string) ($os['cliente_telefone'] ?? ''),
    'email' => (string) ($os['cliente_email'] ?? ''),
]));
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
            <?php if (can('orcamentos', 'criar')): ?>
            <a href="<?= esc($orcamentoQuickUrl) ?>" class="btn btn-outline-warning">
                <i class="bi bi-receipt-cutoff me-1"></i>Gerar orcamento
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
                                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-itens">Itens / Servicos</a></li>
                                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-tecnico">Diagnostico</a></li>
                                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-fotos">Fotos de Entrada</a></li>
                                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-valores">Valores</a></li>
                            </ul>

                            <div class="tab-content os-show-tab-content">
                                <div class="tab-pane fade show active" id="tab-info">
                                    <div class="row g-4">
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
                                    <?php if (can('os', 'editar')): ?>
                                    <div class="card glass-card mb-4">
                                        <div class="card-header">
                                            <h5 class="card-title mb-0">Adicionar Item</h5>
                                        </div>
                                        <div class="card-body">
                                            <form action="<?= base_url('os/item/salvar') ?><?= $embedQuery ?>" method="POST">
                                                <input type="hidden" name="os_id" value="<?= esc((string) $os['id']) ?>">
                                                <div class="row g-3 align-items-end">
                                                    <div class="col-md-2">
                                                        <label class="form-label">Tipo</label>
                                                        <select name="tipo" class="form-select" required>
                                                            <option value="servico">Servico</option>
                                                            <option value="peca">Peca</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-12 col-lg-6 col-xl-4">
                                                        <label class="form-label">Descricao</label>
                                                        <input type="text" name="descricao" class="form-control" required>
                                                    </div>
                                                    <div class="col-md-1">
                                                        <label class="form-label">Qtd</label>
                                                        <input type="number" name="quantidade" class="form-control" value="1" min="1" required>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <label class="form-label">Valor Unit.</label>
                                                        <input type="number" step="0.01" name="valor_unitario" class="form-control" required>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <label class="form-label">Total</label>
                                                        <input type="number" step="0.01" name="valor_total" class="form-control" readonly>
                                                    </div>
                                                    <div class="col-md-1">
                                                        <button type="submit" class="btn btn-glow w-100"><i class="bi bi-plus-lg"></i></button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <div class="card glass-card">
                                        <div class="card-body p-0">
                                            <div class="table-responsive">
                                                <table class="table table-hover mb-0">
                                                    <thead>
                                                        <tr>
                                                            <th>Tipo</th>
                                                            <th>Descricao</th>
                                                            <th>Qtd</th>
                                                            <th>Valor Unit.</th>
                                                            <th>Total</th>
                                                            <th></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php if (empty($itens)): ?>
                                                        <tr><td colspan="6" class="text-center py-3 text-muted">Nenhum item adicionado</td></tr>
                                                        <?php else: ?>
                                                            <?php foreach ($itens as $item): ?>
                                                            <tr>
                                                                <td>
                                                                    <span class="badge <?= $item['tipo'] === 'servico' ? 'bg-info' : 'bg-warning text-dark' ?>">
                                                                        <?= $item['tipo'] === 'servico' ? 'Servico' : 'Peca' ?>
                                                                    </span>
                                                                </td>
                                                                <td>
                                                                    <div><?= esc($item['descricao']) ?></div>
                                                                    <?php if (!empty($item['observacao'])): ?>
                                                                        <small class="text-muted d-block mt-1"><?= esc($item['observacao']) ?></small>
                                                                    <?php endif; ?>
                                                                </td>
                                                                <td><?= esc((string) $item['quantidade']) ?></td>
                                                                <td><?= esc(formatMoney($item['valor_unitario'])) ?></td>
                                                                <td><strong><?= esc(formatMoney($item['valor_total'])) ?></strong></td>
                                                                <td>
                                                                    <?php if (can('os', 'editar')): ?>
                                                                    <a href="<?= base_url('os/item/excluir/' . $item['id']) ?><?= $embedQuery ?>" class="btn btn-sm btn-outline-danger btn-delete" data-nome="<?= esc($item['descricao']) ?>">
                                                                        <i class="bi bi-trash"></i>
                                                                    </a>
                                                                    <?php endif; ?>
                                                                </td>
                                                            </tr>
                                                            <?php endforeach; ?>
                                                        <?php endif; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="tab-tecnico">
                                    <div class="row g-4">
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
                                    <div class="card glass-card">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <h5 class="card-title mb-0"><i class="bi bi-camera me-2"></i>Fotos da Entrada do Equipamento</h5>
                                        </div>
                                        <div class="card-body">
                                            <?php if (empty($fotos_entrada)): ?>
                                                <div class="text-center py-5 text-muted opacity-50">
                                                    <i class="bi bi-images os-empty-icon"></i>
                                                    <p class="mt-2 text-white-50">Nenhuma foto registrada na entrada</p>
                                                </div>
                                            <?php else: ?>
                                                <div class="row g-3">
                                                    <?php foreach ($fotos_entrada as $f): ?>
                                                        <div class="col-6 col-md-3">
                                                            <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#imageModal" data-img-src="<?= esc($f['url']) ?>" class="border rounded d-block overflow-hidden shadow-sm hover-elevate transition os-show-entry-photo">
                                                                <img src="<?= esc($f['url']) ?>" class="w-100 h-100 object-fit-contain" title="Foto de entrada" alt="Foto de entrada">
                                                            </a>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (!empty($acessorios)): ?>
                                                <hr class="border-light mt-4">
                                                <div>
                                                    <h6 class="text-uppercase text-muted fw-bold mb-3 os-show-subsection-title">
                                                        <i class="bi bi-patch-check me-1"></i>Fotos dos Acessorios
                                                    </h6>
                                                    <div class="row g-3">
                                                        <?php foreach ($acessorios as $acessorio): ?>
                                                            <div class="col-12">
                                                                <div class="border rounded-3 p-3 os-show-subitem-card">
                                                                    <div class="d-flex justify-content-between align-items-center flex-wrap mb-2">
                                                                        <span class="fw-semibold"><?= esc($acessorio['descricao']) ?></span>
                                                                        <?php if (empty($acessorio['fotos'])): ?>
                                                                            <small class="text-muted">Sem fotos registradas</small>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                    <?php if (!empty($acessorio['fotos'])): ?>
                                                                        <div class="d-flex flex-wrap gap-2">
                                                                            <?php foreach ($acessorio['fotos'] as $foto): ?>
                                                                                <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#imageModal" data-img-src="<?= esc($foto['url']) ?>" class="border rounded overflow-hidden shadow-sm os-show-inline-photo">
                                                                                    <img src="<?= esc($foto['url']) ?>" class="w-100 h-100 object-fit-cover" alt="Foto do acessorio">
                                                                                </a>
                                                                            <?php endforeach; ?>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                    <p class="small text-muted mt-2 mb-0">Fotos de acessorios registradas em `<?= esc($acessorios_folder ?? 'uploads/acessorios/OS_' . $os['numero_os'] . '/') ?>`.</p>
                                                </div>
                                            <?php endif; ?>

                                            <?php
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
                                            ?>
                                            <?php if (!empty($checklistFotos)): ?>
                                                <hr class="border-light mt-4">
                                                <div>
                                                    <h6 class="text-uppercase text-muted fw-bold mb-3 os-show-subsection-title">
                                                        <i class="bi bi-ui-checks-grid me-1"></i>Fotos do Checklist de Entrada
                                                    </h6>
                                                    <div class="row g-3">
                                                        <?php foreach ($checklistFotos as $fotoChecklist): ?>
                                                            <div class="col-12">
                                                                <div class="border rounded-3 p-3 os-show-subitem-card">
                                                                    <div class="d-flex justify-content-between align-items-center flex-wrap mb-2">
                                                                        <span class="fw-semibold"><?= esc($fotoChecklist['descricao']) ?></span>
                                                                    </div>
                                                                    <div class="d-flex flex-wrap gap-2">
                                                                        <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#imageModal" data-img-src="<?= esc($fotoChecklist['url']) ?>" class="border rounded overflow-hidden shadow-sm os-show-inline-photo">
                                                                            <img src="<?= esc($fotoChecklist['url']) ?>" class="w-100 h-100 object-fit-cover" alt="Foto do checklist">
                                                                        </a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="tab-valores">
                                    <div class="row g-4">
                                        <div class="col-md-6">
                                            <div class="info-card">
                                                <div class="info-card-title"><i class="bi bi-currency-dollar"></i>Financeiro</div>
                                                <div class="finance-item">
                                                    <span class="finance-label">Mao de Obra</span>
                                                    <span class="finance-value"><?= esc(formatMoney($os['valor_mao_obra'])) ?></span>
                                                </div>
                                                <div class="finance-item">
                                                    <span class="finance-label">Pecas</span>
                                                    <span class="finance-value"><?= esc(formatMoney($os['valor_pecas'])) ?></span>
                                                </div>
                                                <div class="finance-item">
                                                    <span class="finance-label">Subtotal</span>
                                                    <span class="finance-value"><?= esc(formatMoney($os['valor_total'])) ?></span>
                                                </div>
                                                <div class="finance-item text-danger">
                                                    <span class="finance-label">Desconto</span>
                                                    <span class="finance-value">- <?= esc(formatMoney($os['desconto'])) ?></span>
                                                </div>
                                                <hr>
                                                <div class="finance-item">
                                                    <span class="finance-label"><strong>TOTAL</strong></span>
                                                    <span class="finance-value text-success"><strong><?= esc(formatMoney($os['valor_final'])) ?></strong></span>
                                                </div>
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
                                        <div class="col-md-6">
                                            <div class="info-card">
                                                <div class="info-card-title"><i class="bi bi-calendar-check"></i>Datas</div>
                                                <div class="finance-item">
                                                    <span class="finance-label">Abertura</span>
                                                    <span class="finance-value"><?= esc(formatDate($os['data_abertura'], true)) ?></span>
                                                </div>
                                                <?php if (!empty($os['data_entrada'])): ?>
                                                <div class="finance-item">
                                                    <span class="finance-label">Entrada</span>
                                                    <span class="finance-value"><?= esc(formatDate($os['data_entrada'], true)) ?></span>
                                                </div>
                                                <?php endif; ?>
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
                                                    <span class="finance-label">Aprovacao</span>
                                                    <span class="finance-value"><?= esc(formatDate($os['data_aprovacao'], true)) ?></span>
                                                </div>
                                                <?php endif; ?>
                                                <div class="finance-item">
                                                    <span class="finance-label">Orcamento aprovado</span>
                                                    <span class="finance-value"><?= !empty($os['orcamento_aprovado']) ? 'Sim' : 'Nao' ?></span>
                                                </div>
                                                <?php if ($formaPagamento !== ''): ?>
                                                <div class="finance-item">
                                                    <span class="finance-label">Forma de pagamento</span>
                                                    <span class="finance-value"><?= esc($formaPagamento) ?></span>
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

            <div class="row g-4 mb-4 os-show-status-row">
                <div class="col-12">
                    <div class="card glass-card os-current-status-card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <h6 class="mb-0"><i class="bi bi-activity me-1"></i>Status</h6>
                            <span class="small text-muted">Apos revisar cliente e equipamento, o proximo passo esta concentrado aqui.</span>
                        </div>
                        <div class="card-body d-flex justify-content-between align-items-start flex-wrap gap-3">
                            <div class="os-current-status-content">
                                <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                                    <?= getStatusBadge($os['status']) ?>
                                    <?php if (!empty($os['estado_fluxo'])): ?>
                                        <span class="badge bg-light text-dark border"><?= esc(ucwords(str_replace('_', ' ', (string) $os['estado_fluxo']))) ?></span>
                                    <?php endif; ?>
                                    <?= getPriorityBadge($os['prioridade'] ?? 'normal') ?>
                                </div>
                                <div class="os-current-status-hints">
                                    <span class="os-section-caption">Proximas etapas provaveis</span>
                                    <?php if (empty($nextStatusOptions)): ?>
                                        <p class="text-muted small mb-0">Nao ha transicoes sugeridas alem do status atual.</p>
                                    <?php else: ?>
                                        <div class="d-flex flex-wrap gap-2">
                                            <?php foreach ($nextStatusOptions as $statusHint): ?>
                                                <span class="badge bg-light text-dark border"><?= esc((string) ($statusHint['nome'] ?? $statusHint['codigo'] ?? 'Status')) ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php if (can('os', 'editar')): ?>
                            <div class="os-status-actions-panel">
                                <div class="os-status-quick-actions">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-glow os-status-quick-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#osQuickStatusModal"
                                        data-action-label="Proxima etapa"
                                        data-action-submit="Avancar etapa"
                                        data-status-code="<?= esc((string) ($primaryNextStatus['codigo'] ?? '')) ?>"
                                        data-status-name="<?= esc((string) ($primaryNextStatus['nome'] ?? '')) ?>"
                                        <?= empty($primaryNextStatus) ? 'disabled' : '' ?>
                                    >
                                        <i class="bi bi-arrow-right-circle me-1"></i>Proxima etapa
                                    </button>

                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-danger os-status-quick-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#osQuickStatusModal"
                                        data-action-label="Cancelar atendimento"
                                        data-action-submit="Cancelar OS"
                                        data-status-code="cancelado"
                                        data-status-name="Cancelado"
                                        <?= $canCancelDirectly ? '' : 'disabled' ?>
                                    >
                                        <i class="bi bi-x-circle me-1"></i>Cancelar
                                    </button>
                                </div>

                                <div class="os-status-action-note small text-muted">
                                    <?php if (!empty($primaryNextStatus)): ?>
                                        Fluxo normal configurado: <strong><?= esc((string) ($primaryNextStatus['nome'] ?? $primaryNextStatus['codigo'] ?? '')) ?></strong>.
                                    <?php else: ?>
                                        Nao ha uma proxima etapa principal disponivel no fluxo atual.
                                    <?php endif; ?>
                                </div>

                                <form action="<?= base_url('os/status/' . $os['id']) ?><?= $embedQuery ?>" method="POST" class="os-status-update-form">
                                    <?= csrf_field() ?>
                                    <select name="status" class="form-select form-select-sm">
                                        <?php foreach ($statusOptionsForView as $statusItem): ?>
                                            <?php
                                            $codigo = (string) ($statusItem['codigo'] ?? '');
                                            $nome = (string) ($statusItem['nome'] ?? $codigo);
                                            if ($codigo === '') {
                                                continue;
                                            }
                                            ?>
                                            <option value="<?= esc($codigo) ?>" <?= $currentStatusCode === $codigo ? 'selected' : '' ?>>
                                                <?= esc($nome) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">Alterar manualmente</button>
                                </form>
                            </div>
                            <?php else: ?>
                            <span class="text-muted small">Sem permissao para alterar status</span>
                            <?php endif; ?>
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

    <?php if (can('os', 'editar')): ?>
    <div class="modal fade" id="osQuickStatusModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="<?= base_url('os/status/' . $os['id']) ?><?= $embedQuery ?>" method="POST" id="osQuickStatusForm">
                    <?= csrf_field() ?>
                    <input type="hidden" name="status" id="osQuickStatusInput" value="">
                    <input type="hidden" name="controla_comunicacao_cliente" value="1">

                    <div class="modal-header">
                        <h5 class="modal-title" id="osQuickStatusTitle">Atualizar status</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>

                    <div class="modal-body">
                        <div class="os-quick-status-summary mb-3">
                            <div class="small text-muted">Destino selecionado</div>
                            <div class="fw-semibold" id="osQuickStatusTargetLabel">-</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="osQuickStatusObservacao">Observacoes</label>
                            <textarea
                                class="form-control"
                                id="osQuickStatusObservacao"
                                name="observacao_status"
                                rows="4"
                                placeholder="Registre aqui o contexto da mudanca, combinados com o cliente ou motivo do cancelamento."
                            ></textarea>
                        </div>

                        <div class="form-check form-switch">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                role="switch"
                                id="osQuickStatusComunicar"
                                name="comunicar_cliente"
                                value="1"
                                <?= $hasClientPhone ? 'checked' : 'disabled' ?>
                            >
                            <label class="form-check-label" for="osQuickStatusComunicar">
                                Comunicar a mudanca de status para o cliente
                            </label>
                        </div>

                        <?php if ($hasClientPhone): ?>
                            <div class="form-text">Telefone atual para comunicacao: <?= esc($os['cliente_telefone']) ?></div>
                        <?php else: ?>
                            <div class="form-text text-danger">Cliente sem telefone cadastrado para comunicacao automatica.</div>
                        <?php endif; ?>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fechar</button>
                        <button type="submit" class="btn btn-glow" id="osQuickStatusSubmit">Confirmar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const imageModal = document.getElementById('imageModal');
        if (imageModal) {
            imageModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const imgSrc = button.getAttribute('data-img-src');
                const modalImg = imageModal.querySelector('#modalImagePreview');
                modalImg.src = imgSrc;
            });
            imageModal.addEventListener('hidden.bs.modal', function() {
                const modalImg = imageModal.querySelector('#modalImagePreview');
                modalImg.src = '';
            });
        }

        const quickStatusModal = document.getElementById('osQuickStatusModal');
        if (quickStatusModal) {
            quickStatusModal.addEventListener('show.bs.modal', function(event) {
                const trigger = event.relatedTarget;
                if (!trigger) {
                    return;
                }

                const statusCode = trigger.getAttribute('data-status-code') || '';
                const statusName = trigger.getAttribute('data-status-name') || statusCode || 'Status';
                const actionLabel = trigger.getAttribute('data-action-label') || 'Atualizar status';
                const actionSubmit = trigger.getAttribute('data-action-submit') || 'Confirmar';

                const input = document.getElementById('osQuickStatusInput');
                const title = document.getElementById('osQuickStatusTitle');
                const targetLabel = document.getElementById('osQuickStatusTargetLabel');
                const submit = document.getElementById('osQuickStatusSubmit');
                const obs = document.getElementById('osQuickStatusObservacao');
                const communicate = document.getElementById('osQuickStatusComunicar');

                if (input) {
                    input.value = statusCode;
                }
                if (title) {
                    title.textContent = actionLabel;
                }
                if (targetLabel) {
                    targetLabel.textContent = statusName;
                }
                if (submit) {
                    submit.textContent = actionSubmit;
                }
                if (obs) {
                    obs.value = '';
                }
                if (communicate && !communicate.disabled) {
                    communicate.checked = true;
                }
            });
        }
    });

    const inputQtd = document.querySelector('input[name="quantidade"]');
    const inputUnit = document.querySelector('input[name="valor_unitario"]');
    const inputTotal = document.querySelector('input[name="valor_total"]');

    function updateItemTotal() {
        const qtd = parseFloat(inputQtd.value) || 0;
        const unit = parseFloat(inputUnit.value) || 0;
        inputTotal.value = (qtd * unit).toFixed(2);
    }

    if (inputQtd && inputUnit && inputTotal) {
        inputQtd.addEventListener('input', updateItemTotal);
        inputUnit.addEventListener('input', updateItemTotal);
    }
</script>
<?= $this->endSection() ?>
