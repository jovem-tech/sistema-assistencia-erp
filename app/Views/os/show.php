<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<style>
    .os-show-page .os-top-actions {
        display: flex;
        gap: 8px;
        align-items: center;
        flex-wrap: wrap;
    }

    .os-show-page .os-show-tabs {
        margin-bottom: 1rem;
    }

    @media (max-width: 767.98px) {
        .os-show-page .os-top-actions {
            width: 100%;
        }

        .os-show-page .os-top-actions .btn {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<div class="os-show-page">

<div class="page-header">
    <div>
        <h2><i class="bi bi-clipboard-check me-2"></i><?= esc($os['numero_os']) ?></h2>
        <span class="text-muted">Aberta em <?= formatDate($os['data_abertura'], true) ?></span>
    </div>
    <div class="os-top-actions">
        <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('ordens-de-servico')" title="Ajuda sobre Ordens de Serviço">
            <i class="bi bi-question-circle me-1"></i>Ajuda
        </button>
        <?php if (can('os', 'editar')): ?>
        <a href="<?= base_url('os/editar/' . $os['id']) ?>" class="btn btn-outline-secondary">
            <i class="bi bi-pencil me-1"></i>Editar
        </a>
        <?php endif; ?>
        <a href="<?= base_url('os/imprimir/' . $os['id']) ?>" class="btn btn-outline-secondary" target="_blank">
            <i class="bi bi-printer me-1"></i>Imprimir
        </a>
        <a href="<?= base_url('os') ?>" class="btn btn-outline-secondary" data-back-default="<?= base_url('os') ?>">
            <i class="bi bi-arrow-left me-1"></i>Voltar
        </a>
    </div>
</div>

<!-- Status and Quick Actions -->
<div class="row g-4 ds-split-layout">
    <!-- Sidebar: Fotos do Equipamento (Padrão equipamentos/visualizar) -->
    <div class="col-12 col-xl-4 col-xxl-3 ds-split-sidebar">
        <div class="card glass-card h-100">
            <div class="card-body text-center p-3">
                <h6 class="fw-bold mb-3 text-uppercase text-muted" style="font-size:0.7rem; letter-spacing:1px;">
                    <i class="bi bi-image me-1"></i>Fotos do Equipamento
                </h6>
                <div class="mb-3">
                    <?php 
                    $principalObj = array_filter($fotos_equip ?? [], fn($f) => $f['is_principal'] == 1);
                    $principalObj = !empty($principalObj) ? array_values($principalObj)[0] : (!empty($fotos_equip) ? $fotos_equip[0] : null);
                    ?>
                    
                    <?php if ($principalObj): ?>
                        <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#imageModal" data-img-src="<?= $principalObj['url'] ?>" class="rounded bg-body-tertiary d-flex align-items-center justify-content-center overflow-hidden mx-auto border text-decoration-none" style="height: 180px; width: 100%; cursor: zoom-in; background: #111;">
                            <img src="<?= $principalObj['url'] ?>" alt="Foto Principal" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                        </a>
                    <?php else: ?>
                        <div class="rounded bg-body-tertiary d-flex align-items-center justify-content-center mx-auto border text-body-secondary" style="height: 180px; width: 100%;">
                            <div class="text-center opacity-50">
                                <i class="bi bi-camera fs-1"></i>
                                <div class="small mt-1">Sem foto</div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if(count($fotos_equip ?? []) > 1): ?>
                <div class="d-flex flex-wrap gap-2 justify-content-center border-top pt-3">
                    <?php foreach($fotos_equip as $foto): ?>
                        <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#imageModal" data-img-src="<?= $foto['url'] ?>" class="border rounded d-inline-block overflow-hidden" style="width: 45px; height: 45px; cursor: zoom-in;">
                            <img src="<?= $foto['url'] ?>" class="w-100 h-100 object-fit-cover">
                        </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <div class="mt-3 p-2 rounded text-start" style="background: rgba(255,255,255,0.03); font-size: 0.75rem;">
                    <div class="text-white-50"><i class="bi bi-laptop me-1"></i><?= esc($os['equip_marca'] . ' ' . $os['equip_modelo']) ?></div>
                    <div class="text-muted mt-1"><i class="bi bi-upc me-1"></i>SN: <?= esc($os['equip_serie'] ?? '-') ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Conteúdo Principal -->
    <div class="col-12 col-xl-8 col-xxl-9 ds-split-main">

<!-- Status and Quick Actions -->
<div class="row g-4 mb-4">
    <div class="col-12 col-lg-7 col-xl-8">
        <div class="card glass-card">
            <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <span class="text-muted me-2">Status:</span>
                    <?= getStatusBadge($os['status']) ?>
                    <?php if (!empty($os['estado_fluxo'])): ?>
                        <span class="badge bg-light text-dark border ms-2"><?= esc(ucwords(str_replace('_', ' ', (string) $os['estado_fluxo']))) ?></span>
                    <?php endif; ?>
                    <span class="ms-3 text-muted">Prioridade:</span>
                    <?= getPriorityBadge($os['prioridade'] ?? 'normal') ?>
                </div>
                                <?php if (can('os', 'editar')): ?>
                <form action="<?= base_url('os/status/' . $os['id']) ?>" method="POST" class="os-status-update-form">
                    <select name="status" class="form-select form-select-sm">
                        <?php
                        $statusOptionsForView = !empty($statusOptions ?? []) ? $statusOptions : [];
                        if (empty($statusOptionsForView) && !empty($statusGrouped ?? [])) {
                            foreach ($statusGrouped as $groupItems) {
                                foreach ($groupItems as $statusItem) {
                                    $statusOptionsForView[] = $statusItem;
                                }
                            }
                        }
                        foreach ($statusOptionsForView as $statusItem):
                            $codigo = (string) ($statusItem['codigo'] ?? '');
                            $nome = (string) ($statusItem['nome'] ?? $codigo);
                            if ($codigo === '') {
                                continue;
                            }
                        ?>
                            <option value="<?= esc($codigo) ?>" <?= (($os['status'] ?? '') === $codigo) ? 'selected' : '' ?>>
                                <?= esc($nome) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-sm btn-glow">Alterar Status</button>
                </form>
                <?php else: ?>
                <span class="text-muted small">Sem permissão para alterar status</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-6 col-xl-4">
        <div class="stat-card stat-card-success">
            <div class="stat-card-body">
                <div class="stat-info">
                    <span class="stat-label">Valor Final</span>
                    <h2 class="stat-value"><?= formatMoney($os['valor_final']) ?></h2>
                </div>
                <div class="stat-icon"><i class="bi bi-cash-stack"></i></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-12 col-lg-5 col-xl-4">
        <div class="card glass-card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-clock-history me-1"></i>Histórico de Status</h6>
            </div>
            <div class="card-body">
                <?php if (empty($statusHistorico ?? [])): ?>
                    <p class="text-muted mb-0 small">Sem movimentações registradas.</p>
                <?php else: ?>
                    <div class="d-flex flex-column gap-2" style="max-height: 300px; overflow:auto;">
                        <?php foreach (($statusHistorico ?? []) as $item): ?>
                            <div class="border rounded p-2 small">
                                <div class="fw-semibold"><?= esc(ucwords(str_replace('_', ' ', (string) ($item['status_novo'] ?? '-')))) ?></div>
                                <div class="text-muted"><?= esc(formatDate($item['created_at'] ?? '', true)) ?></div>
                                <?php if (!empty($item['usuario_nome'])): ?>
                                    <div class="text-muted">por <?= esc($item['usuario_nome']) ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-6 col-xl-4">
        <div class="card glass-card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-file-earmark-pdf me-1"></i>Documentos PDF</h6>
            </div>
            <div class="card-body">
                <form action="<?= base_url('os/pdf/' . $os['id'] . '/gerar') ?>" method="POST" class="os-doc-form mb-3">
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
                    <div class="d-flex flex-column gap-2" style="max-height: 220px; overflow:auto;">
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
                                        <form action="<?= base_url('os/whatsapp/' . $os['id']) ?>" method="POST" class="d-inline">
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
    <div class="col-12 col-lg-6 col-xl-4">
        <div class="card glass-card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-whatsapp me-1"></i>WhatsApp</h6>
            </div>
            <div class="card-body">
                <?php if (can('os', 'editar')): ?>
                    <form action="<?= base_url('os/whatsapp/' . $os['id']) ?>" method="POST" class="d-flex flex-column gap-2 mb-3">
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
                    <p class="text-muted small">Sem permissão para envio manual.</p>
                <?php endif; ?>
                <?php if (empty($whatsappLogs ?? [])): ?>
                    <p class="text-muted mb-0 small">Sem mensagens registradas.</p>
                <?php else: ?>
                    <div class="d-flex flex-column gap-2" style="max-height: 180px; overflow:auto;">
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

<!-- Tabs -->
<ul class="nav nav-tabs ds-tabs-scroll os-show-tabs" role="tablist">
    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-info">Informações</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-itens">Itens / Serviços</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-tecnico">Diagnóstico</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-fotos">Fotos de Entrada</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-valores">Valores</a></li>
</ul>

<div class="tab-content">
    <!-- Tab: Info -->
    <div class="tab-pane fade show active" id="tab-info">
        <div class="row g-4">
            <div class="col-md-6">
                <div class="info-card">
                    <div class="info-card-title"><i class="bi bi-person"></i>Cliente</div>
                    <div class="detail-group">
                        <div class="detail-value"><strong><?= esc($os['cliente_nome']) ?></strong></div>
                    </div>
                    <div class="detail-group">
                        <div class="detail-label">Telefone</div>
                        <div class="detail-value"><?= esc($os['cliente_telefone'] ?? '-') ?></div>
                    </div>
                    <div class="detail-group">
                        <div class="detail-label">Email</div>
                        <div class="detail-value"><?= esc($os['cliente_email'] ?? '-') ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="info-card">
                    <div class="info-card-title"><i class="bi bi-laptop"></i>Equipamento</div>
                    <div class="detail-group">
                        <div class="detail-value"><strong><?= esc($os['equip_marca'] . ' ' . $os['equip_modelo']) ?></strong></div>
                    </div>
                    <div class="detail-group">
                        <div class="detail-label">Tipo</div>
                        <div class="detail-value"><?= getEquipTipo($os['equip_tipo']) ?></div>
                    </div>
                    <div class="detail-group">
                        <div class="detail-label">Nº Série</div>
                        <div class="detail-value"><?= esc($os['equip_serie'] ?? '-') ?></div>
                    </div>
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
                    <div class="info-card-title"><i class="bi bi-shield-exclamation"></i>Estado Físico na Entrada</div>
                    <?php if (empty($estados_fisicos)): ?>
                        <p class="mb-0 text-muted">Nenhum registro de estado físico informado na abertura.</p>
                    <?php else: ?>
                        <div class="d-flex flex-column gap-2">
                            <?php foreach ($estados_fisicos as $estado): ?>
                                <div class="border rounded-3 p-2" style="background: rgba(255,255,255,0.03);">
                                    <div class="fw-semibold"><?= esc($estado['descricao_dano'] ?? '-') ?></div>
                                    <?php if (!empty($estado['fotos'])): ?>
                                        <div class="d-flex flex-wrap gap-2 mt-2">
                                            <?php foreach ($estado['fotos'] as $foto): ?>
                                                <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#imageModal" data-img-src="<?= esc($foto['url']) ?>" class="border rounded overflow-hidden shadow-sm" style="width: 90px; height: 90px; background: rgba(255,255,255,0.02); cursor: zoom-in;">
                                                    <img src="<?= esc($foto['url']) ?>" class="w-100 h-100 object-fit-cover" alt="Foto estado fisico">
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab: Items -->
    <div class="tab-pane fade" id="tab-itens">
        <!-- Add Item Form -->
        <?php if (can('os', 'editar')): ?>
        <div class="card glass-card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Adicionar Item</h5>
            </div>
            <div class="card-body">
                <form action="<?= base_url('os/item/salvar') ?>" method="POST">
                    <input type="hidden" name="os_id" value="<?= $os['id'] ?>">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-2">
                            <label class="form-label">Tipo</label>
                            <select name="tipo" class="form-select" required>
                                <option value="servico">Serviço</option>
                                <option value="peca">Peça</option>
                            </select>
                        </div>
    <div class="col-12 col-lg-6 col-xl-4">
                            <label class="form-label">Descrição</label>
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

        <!-- Items List -->
        <div class="card glass-card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Descrição</th>
                                <th>Qtd</th>
                                <th>Valor Unit.</th>
                                <th>Total</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($itens)): ?>
                            <tr><td colspan="6" class="text-center py-3 text-muted">Nenhum item adicionado</td></tr>
                            <?php else: foreach ($itens as $item): ?>
                            <tr>
                                <td>
                                    <span class="badge <?= $item['tipo'] === 'servico' ? 'bg-info' : 'bg-warning text-dark' ?>">
                                        <?= $item['tipo'] === 'servico' ? 'Serviço' : 'Peça' ?>
                                    </span>
                                </td>
                                <td><?= esc($item['descricao']) ?></td>
                                <td><?= $item['quantidade'] ?></td>
                                <td><?= formatMoney($item['valor_unitario']) ?></td>
                                <td><strong><?= formatMoney($item['valor_total']) ?></strong></td>
                                <td>
                                    <?php if (can('os', 'editar')): ?>
                                    <a href="<?= base_url('os/item/excluir/' . $item['id']) ?>" class="btn btn-sm btn-outline-danger btn-delete" data-nome="<?= esc($item['descricao']) ?>">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab: Diagnostic -->
    <div class="tab-pane fade" id="tab-tecnico">
        <div class="row g-4">
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
                    <p><?= $os['garantia_dias'] ?> dias
                    <?php if (!empty($os['garantia_validade'])): ?>
                        - Válida até <?= formatDate($os['garantia_validade']) ?>
                    <?php endif; ?>
                    </p>
                </div>
            </div>

            <?php if (!empty($defeitos)): ?>
            <div class="col-12">
                <div class="info-card">
                    <div class="info-card-title"><i class="bi bi-journal-text text-warning"></i>Base de Conhecimento: Procedimentos de Reparo</div>
                    <div class="row g-3">
                        <?php foreach($defeitos as $def): ?>
                        <div class="col-md-6 border-bottom pb-3 mb-2">
                            <h6 class="text-warning small mb-2"><i class="bi bi-tag-fill me-1"></i><?= esc($def['nome']) ?> (<?= ucfirst($def['classificacao']) ?>)</h6>
                            <?php if (empty($def['procedimentos'])): ?>
                                <p class="text-muted small mb-0">Sem procedimentos específicos cadastrados.</p>
                            <?php else: ?>
                                <div class="vstack gap-2">
                                    <?php foreach($def['procedimentos'] as $idx => $proc): ?>
                                    <div class="d-flex align-items-center p-2 rounded" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05);">
                                        <div class="me-3">
                                            <span class="badge rounded-circle bg-warning text-dark" style="width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 10px;"><?= $idx + 1 ?></span>
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

    <!-- Tab: Fotos de Entrada -->
    <div class="tab-pane fade" id="tab-fotos">
        <div class="card glass-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="bi bi-camera me-2"></i>Fotos da Entrada do Equipamento</h5>
            </div>
            <div class="card-body">
                <?php if (empty($fotos_entrada)): ?>
                    <div class="text-center py-5 text-muted opacity-50">
                        <i class="bi bi-images" style="font-size: 3rem;"></i>
                        <p class="mt-2 text-white-50">Nenhuma foto registrada na entrada</p>
                    </div>
                <?php else: ?>
                    <div class="row g-3">
                        <?php foreach($fotos_entrada as $f): ?>
                            <div class="col-6 col-md-3">
                                <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#imageModal" data-img-src="<?= $f['url'] ?>" class="border rounded d-block overflow-hidden shadow-sm hover-elevate transition" style="height: 180px; cursor: zoom-in; background: #000;">
                                    <img src="<?= $f['url'] ?>" class="w-100 h-100 object-fit-contain" title="Foto de entrada">
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($acessorios)): ?>
                    <hr class="border-light mt-4">
                    <div>
                        <h6 class="text-uppercase text-muted fw-bold mb-3" style="letter-spacing: 0.1rem; font-size: 0.7rem;">
                            <i class="bi bi-patch-check me-1"></i>Fotos dos Acessórios
                        </h6>
                        <div class="row g-3">
                            <?php foreach($acessorios as $acessorio): ?>
                                <div class="col-12">
                                    <div class="border rounded-3 p-3" style="background: rgba(255,255,255,0.03);">
                                        <div class="d-flex justify-content-between align-items-center flex-wrap mb-2">
                                            <span class="fw-semibold"><?= esc($acessorio['descricao']) ?></span>
                                            <?php if (empty($acessorio['fotos'])): ?>
                                                <small class="text-muted">Sem fotos registradas</small>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (!empty($acessorio['fotos'])): ?>
                                            <div class="d-flex flex-wrap gap-2">
                                                <?php foreach($acessorio['fotos'] as $foto): ?>
                                                    <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#imageModal" data-img-src="<?= esc($foto['url']) ?>" class="border rounded overflow-hidden shadow-sm" style="width: 90px; height: 90px; background: rgba(255,255,255,0.02); cursor: zoom-in;">
                                                        <img src="<?= esc($foto['url']) ?>" class="w-100 h-100 object-fit-cover">
                                                    </a>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <p class="small text-muted mt-2 mb-0">Todas as fotos de acessórios também ficam registradas em `<?= esc($acessorios_folder ?? 'uploads/acessorios/OS_' . $os['numero_os'] . '/') ?>`.</p>
                    </div>
                <?php endif; ?>

                <?php if (!empty($estados_fisicos)): ?>
                    <hr class="border-light mt-4">
                    <div>
                        <h6 class="text-uppercase text-muted fw-bold mb-3" style="letter-spacing: 0.1rem; font-size: 0.7rem;">
                            <i class="bi bi-shield-exclamation me-1"></i>Fotos do Estado Físico
                        </h6>
                        <div class="row g-3">
                            <?php foreach($estados_fisicos as $estado): ?>
                                <div class="col-12">
                                    <div class="border rounded-3 p-3" style="background: rgba(255,255,255,0.03);">
                                        <div class="d-flex justify-content-between align-items-center flex-wrap mb-2">
                                            <span class="fw-semibold"><?= esc($estado['descricao_dano'] ?? '-') ?></span>
                                            <?php if (empty($estado['fotos'])): ?>
                                                <small class="text-muted">Sem fotos registradas</small>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (!empty($estado['fotos'])): ?>
                                            <div class="d-flex flex-wrap gap-2">
                                                <?php foreach($estado['fotos'] as $foto): ?>
                                                    <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#imageModal" data-img-src="<?= esc($foto['url']) ?>" class="border rounded overflow-hidden shadow-sm" style="width: 90px; height: 90px; background: rgba(255,255,255,0.02); cursor: zoom-in;">
                                                        <img src="<?= esc($foto['url']) ?>" class="w-100 h-100 object-fit-cover" alt="Foto estado fisico">
                                                    </a>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <p class="small text-muted mt-2 mb-0">Fotos de estado físico registradas em `<?= esc($estado_fisico_folder ?? 'uploads/estado_fisico/OS_' . $os['numero_os'] . '/') ?>`.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Tab: Values -->
    <div class="tab-pane fade" id="tab-valores">
        <div class="row g-4">
            <div class="col-md-6">
                <div class="info-card">
                    <div class="info-card-title"><i class="bi bi-currency-dollar"></i>Financeiro</div>
                    <div class="finance-item">
                        <span class="finance-label">Mão de Obra</span>
                        <span class="finance-value"><?= formatMoney($os['valor_mao_obra']) ?></span>
                    </div>
                    <div class="finance-item">
                        <span class="finance-label">Peças</span>
                        <span class="finance-value"><?= formatMoney($os['valor_pecas']) ?></span>
                    </div>
                    <div class="finance-item">
                        <span class="finance-label">Subtotal</span>
                        <span class="finance-value"><?= formatMoney($os['valor_total']) ?></span>
                    </div>
                    <div class="finance-item text-danger">
                        <span class="finance-label">Desconto</span>
                        <span class="finance-value">- <?= formatMoney($os['desconto']) ?></span>
                    </div>
                    <hr>
                    <div class="finance-item">
                        <span class="finance-label"><strong>TOTAL</strong></span>
                        <span class="finance-value text-success"><strong><?= formatMoney($os['valor_final']) ?></strong></span>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="info-card">
                    <div class="info-card-title"><i class="bi bi-calendar-check"></i>Datas</div>
                    <div class="finance-item">
                        <span class="finance-label">Abertura</span>
                        <span class="finance-value"><?= formatDate($os['data_abertura'], true) ?></span>
                    </div>
                    <?php if (!empty($os['data_entrada'])): ?>
                    <div class="finance-item">
                        <span class="finance-label">Entrada</span>
                        <span class="finance-value"><?= formatDate($os['data_entrada'], true) ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="finance-item">
                        <span class="finance-label">Previsão</span>
                        <span class="finance-value"><?= formatDate($os['data_previsao'] ?? '') ?></span>
                    </div>
                    <div class="finance-item">
                        <span class="finance-label">Conclusão</span>
                        <span class="finance-value"><?= formatDate($os['data_conclusao'] ?? '') ?></span>
                    </div>
                    <div class="finance-item">
                        <span class="finance-label">Entrega</span>
                        <span class="finance-value"><?= formatDate($os['data_entrega'] ?? '') ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Image View Modal -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content bg-transparent border-0">
            <div class="modal-body text-center p-0 position-relative">
                <div class="d-inline-block position-relative">
                    <button type="button" class="btn-close position-absolute" data-bs-dismiss="modal" aria-label="Close" style="top: 10px; right: 10px; z-index: 1055; filter: invert(1); opacity: 1; background-color: rgba(0,0,0,0.6); border-radius: 50%; padding: 0.8rem; box-shadow: 0 4px 12px rgba(0,0,0,0.5);"></button>
                    <img src="" id="modalImagePreview" class="img-fluid rounded shadow-lg" style="max-height: 85vh; object-fit: contain; background: rgba(0,0,0,0.9);">
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
        if (imageModal) {
            imageModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const imgSrc = button.getAttribute('data-img-src');
                const modalImg = imageModal.querySelector('#modalImagePreview');
                modalImg.src = imgSrc;
            });
            imageModal.addEventListener('hidden.bs.modal', function () {
                const modalImg = imageModal.querySelector('#modalImagePreview');
                modalImg.src = '';
            });
        }
    });

    // Helper for finance logic
    const inputQtd = document.querySelector('input[name="quantidade"]');
    const inputUnit = document.querySelector('input[name="valor_unitario"]');
    const inputTotal = document.querySelector('input[name="valor_total"]');

    function updateItemTotal() {
        const qtd = parseFloat(inputQtd.value) || 0;
        const unit = parseFloat(inputUnit.value) || 0;
        inputTotal.value = (qtd * unit).toFixed(2);
    }

    if (inputQtd && inputUnit) {
        inputQtd.addEventListener('input', updateItemTotal);
        inputUnit.addEventListener('input', updateItemTotal);
    }
</script>
<?= $this->endSection() ?>
