<?php
$isEdit = isset($os);
$tipos  = $tipos  ?? [];
$marcas = $marcas ?? [];
$relatosRapidos = $relatosRapidos ?? [];
$statusGrouped = $statusGrouped ?? [];
$statusDefault = $statusDefault ?? ($isEdit ? (string)($os['status'] ?? 'triagem') : 'triagem');

$statusFlat = [];
foreach ($statusGrouped as $macro => $itemês) {
    if (!is_array($itemês)) {
        continue;
    }
    foreach ($itemês as $item) {
        $codigo = (string) ($item['codigo'] ?? '');
        if ($codigo === '') {
            continue;
        }
        $statusFlat[$codigo] = [
            'nãome' => (string) ($item['nãome'] ?? $codigo),
            'cor' => (string) ($item['cor'] ?? 'secondary'),
            'grupo' => (string) $macro,
        ];
    }
}
$statusDefaultLabel = (string) ($statusFlat[$statusDefault]['nãome'] ?? 'Triagem');

$origemConversaId = (int) ($origemConversaId ?? 0);
$origemContatoId = (int) ($origemContatoId ?? 0);
$origemConversa = (isset($origemConversa) && is_array($origemConversa)) ? $origemConversa : null;
$origemContato = (isset($origemContato) && is_array($origemContato)) ? $origemContato : null;
$clientePreSelecionado = (int) ($clientePreSelecionado ?? 0);

$origemNãomeHint = trim((string) ($origemNãomeHint ?? ''));
if ($origemNãomeHint === '') {
    $origemNãomeHint = trim((string) ($origemContato['nãome'] ?? $origemContato['whatsapp_nãome_perfil'] ?? $origemConversa['nãome_contato'] ?? ''));
}

$origemTelefoneHint = preg_replace('/\D+/', '', (string) ($origemTelefoneHint ?? '')) ?? '';
if ($origemTelefoneHint === '') {
    $origemTelefoneHint = preg_replace('/\D+/', '', (string) ($origemContato['telefone_nãormalizado'] ?? $origemContato['telefone'] ?? $origemConversa['telefone'] ?? '')) ?? '';
}

$isOrigemCentralWhatsapp = !$isEdit
    && ($origemConversaId > 0 || $origemContatoId > 0 || $clientePreSelecionado > 0 || $origemTelefoneHint !== '' || $origemNãomeHint !== '');

$clienteSelecionadoNãoForm = $isEdit
    ? (int) ($os['cliente_id'] ?? 0)
    : ($clientePreSelecionado > 0 ? $clientePreSelecionado : 0);
?>

<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header d-flex justify-content-between align-itemês-center">
    <div class="d-flex align-itemês-center gap-3">
        <h2><i class="bi bi-<?= $isEdit ? 'pencil' : 'plus-lg' ?> me-2"></i><?= $title ?></h2>
        <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('ordens-de-servico')">Ajuda</button>
    </div>
    <a href="<?= base_url('os') ?>" class="btn btn-outline-secondary" data-back-default="<?= base_url('os') ?>">
        <i class="bi bi-arrow-left me-1"></i>Voltar
    </a>
</div>

<!-- LAYOUT PRINCIPAL: SIDEBAR (foto) + CONTE�DO -->
<div class="os-form-page">
<div class="row g-4 ds-split-layout">

    <!-- SIDEBAR: Painel da foto do equipamento -->
    <div class="col-12 col-xl-4 col-xxl-3 ds-split-sidebar" id="sidebarEquipamento">
        <div class="d-flex flex-column gap-3 ds-sticky-panel">
            <div class="card glass-card">
                <div class="card-body p-3">
                    <h6 class="fw-bold mb-3 text-uppercase text-muted" style="font-size:0.7rem; letter-spacing:1px;">
                        <i class="bi bi-image me-1"></i>Foto do Equipamento
                    </h6>
                    <!-- Foto Principal -->
                    <div id="fotoPrincipalWrap" class="mb-3 text-center">
                        <div id="fotoMainBox" class="rounded overflow-hidden d-nãone"
                             style="height: 200px; background: #111; border: 2px sãolid rgba(255,255,255,0.1); position:relative;">
                            <a href="javascript:void(0)" id="fotoPrincipalLink" data-bs-toggle="modal" data-bs-target="#imageModal" data-img-src="" class="d-block w-100 h-100" style="cursãor: zoom-in;">
                                <img id="fotoPrincipalImg" src="" alt="Foto do equipamento"
                                     class="w-100 h-100"
                                     style="object-fit: contain; transition: opacity 0.2s;">
                            </a>
                        </div>
                        <div id="fotoPlaceholder" class="rounded align-itemês-center justify-content-center d-flex"
                             style="height: 200px; background: rgba(255,255,255,0.04); border: 2px dashed rgba(255,255,255,0.1);">
                            <div class="text-center text-muted">
                                <i class="bi bi-image" style="font-size: 2rem;"></i>
                                <p class="small mt-2 mb-0">Selecione um equipamento</p>
                            </div>
                        </div>
                    </div>

                    <div id="equipColorInfo" class="d-flex align-itemês-center gap-2 small text-muted mb-2 d-nãone">
                        <span id="equipColorSwatch" class="d-inline-block rounded-circle border" style="width: 14px; height: 14px; background: #333;"></span>
                        <span id="equipColorName">Cor n�o informada</span>
                    </div>

                    <!-- Miniaturas -->
                    <div id="fotosMiniaturas" class="d-flex flex-wrap gap-2 justify-content-center"></div>

                    <!-- Info do Equipamento -->
                    <div id="equipInfoBox" class="mt-3 p-2 rounded" style="background: rgba(255,255,255,0.04); font-size: 0.78rem; display:nãone;">
                        <div id="equipInfoContent" class="text-muted"></div>
                    </div>
                    <?php if (can('equipamentos', 'editar')): ?>
                    <div class="mt-2">
                        <button class="btn btn-outline-primary btn-sm w-100 d-nãone" type="button" id="btnEditarEquipamento"
                                title="Editar equipamento selecionado">
                            <i class="bi bi-pencil-square me-1"></i>Editar equipamento
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card glass-card" id="resumoOsCard">
                <div class="card-body p-3">
                    <h6 class="fw-bold mb-3 text-uppercase text-muted" style="font-size:0.7rem; letter-spacing:1px;">
                        <i class="bi bi-clipboard2-check me-1"></i>Resumo da OS
                    </h6>
                    <div class="d-flex flex-column gap-2 small">
                        <div class="d-flex justify-content-between align-itemês-center">
                            <span class="text-muted">Cliente</span>
                            <span class="d-flex align-itemês-center gap-2">
                                <span id="resumoCliente" class="text-white-50">N�o selecionado</span>
                                <span id="statusCliente" class="text-danger">?</span>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-itemês-center">
                            <span class="text-muted">Equipamento</span>
                            <span class="d-flex align-itemês-center gap-2">
                                <span id="resumoEquipamento" class="text-white-50">N�o selecionado</span>
                                <span id="statusEquipamento" class="text-danger">?</span>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-itemês-center">
                            <span class="text-muted">T�cnico</span>
                            <span class="d-flex align-itemês-center gap-2">
                                <span id="resumoTecnico" class="text-white-50">N�o atribu�do</span>
                                <span id="statusTecnico" class="text-danger">?</span>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-itemês-center">
                            <span class="text-muted">Prioridade</span>
                            <span class="d-flex align-itemês-center gap-2">
                                <span id="resumoPrioridade" class="badge text-bg-secondary">Nãormal</span>
                                <span id="statusPrioridade" class="text-success">??</span>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-itemês-center">
                            <span class="text-muted">Status</span>
                            <span class="d-flex align-itemês-center gap-2">
                                <span id="resumoStatus" class="badge text-bg-secondary"><?= esc($statusDefaultLabel) ?></span>
                                <span id="statusStatus" class="text-success">??</span>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-itemês-center">
                            <span class="text-muted">Entrada</span>
                            <span class="d-flex align-itemês-center gap-2">
                                <span id="resumoEntrada" class="text-white-50">-</span>
                                <span id="statusEntrada" class="text-danger">?</span>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-itemês-center">
                            <span class="text-muted">Previs�o</span>
                            <span class="d-flex align-itemês-center gap-2">
                                <span id="resumoPrevisao" class="text-white-50">-</span>
                                <span id="statusPrevisao" class="text-danger">?</span>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-itemês-center">
                            <span class="text-muted">Relato</span>
                            <span class="d-flex align-itemês-center gap-2">
                                <span id="resumoRelato" class="text-white-50">Vazio</span>
                                <span id="statusRelato" class="text-danger">?</span>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-itemês-center">
                            <span class="text-muted">Acess�rios</span>
                            <span class="d-flex align-itemês-center gap-2">
                                <span id="resumoAcessãorios" class="text-white-50">N�o informado</span>
                                <span id="statusAcessãorios" class="text-danger">?</span>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-itemês-center">
                            <span class="text-muted">Estado f�sico</span>
                            <span class="d-flex align-itemês-center gap-2">
                                <span id="resumoEstadoFisico" class="text-white-50">N�o informado</span>
                                <span id="statusEstadoFisico" class="text-danger">?</span>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-itemês-center">
                            <span class="text-muted">Fotos de entrada</span>
                            <span class="d-flex align-itemês-center gap-2">
                                <span id="resumoFotosEntrada" class="text-white-50">0</span>
                                <span id="statusFotos" class="text-danger">?</span>
                            </span>
                        </div>
                        <?php if ($isEdit): ?>
                        <div class="d-flex justify-content-between align-itemês-center">
                            <span class="text-muted">Defeitos marcados</span>
                            <span class="d-flex align-itemês-center gap-2">
                                <span id="resumoDefeitos" class="text-white-50">0</span>
                                <span id="statusDefeitos" class="text-danger">?</span>
                            </span>
                        </div>
                        <?php endif; ?>
                        <div class="d-flex justify-content-between align-itemês-center">
                            <span class="text-muted">Rascunho</span>
                            <span id="resumoRascunho" class="text-white-50">N�o salvo</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- �REA PRINCIPAL DO FORMUL�RIO -->
    <div class="col-12 col-xl-8 col-xxl-9 ds-split-main" id="formCol">
        <div class="card glass-card">
            <div class="card-body">
                <form action="<?= $isEdit ? base_url('os/atualizar/' . $os['id']) : base_url('os/salvar') ?>"
                      method="POST" enctype="multipart/form-data" id="formOs" nãovalidate>
                    <?= csrf_field() ?>
                    <?php if (!$isEdit): ?>
                    <input type="hidden" name="origem_conversa_id" value="<?= $origemConversaId > 0 ? $origemConversaId : '' ?>">
                    <input type="hidden" name="origem_contato_id" value="<?= $origemContatoId > 0 ? $origemContatoId : '' ?>">
                    <?php if ($isOrigemCentralWhatsapp): ?>
                    <div class="alert alert-primary d-flex flex-wrap justify-content-between align-itemês-center gap-2">
                        <div class="small mb-0">
                            <i class="bi bi-whatsapp me-1"></i>
                            <strong>Origem Central WhatsApp:</strong>
                            <?= esc($origemNãomeHint !== '' ? $origemNãomeHint : 'Contato sem nãome') ?>
                            <?= $origemTelefoneHint !== '' ? ' (' . esc($origemTelefoneHint) . ')' : '' ?>
                            <?php if ($clienteSelecionadoNãoForm > 0): ?>
                                <span class="badge text-bg-success-subtle text-success-emphasis border mês-2">Cliente ERP pre-selecionado</span>
                            <?php else: ?>
                                <span class="badge text-bg-info text-dark mês-2">Contato ainda sem vinculo em clientes</span>
                            <?php endif; ?>
                        </div>
                        <a href="<?= base_url('atendimento-whatsapp') ?>" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-arrow-left me-1"></i>Voltar para Central
                        </a>
                    </div>
                    <?php endif; ?>
                    <div id="osDraftAlert" class="alert alert-info d-flex align-itemês-center justify-content-between gap-3 d-nãone">
                        <div class="small mb-0">
                            <i class="bi bi-clock-history me-1"></i>Encontramos um rascunho salvo automaticamente para esta OS.
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-light" id="btnDescartarRascunho">Descartar</button>
                            <button type="button" class="btn btn-sm btn-info" id="btnRestaurarRascunho">Restaurar</button>
                        </div>
                    </div>
                    <?php endif; ?>

                    <ul class="nav nav-tabs ds-tabs-scroll mb-3" id="osTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active fw-bold" id="tab-dados-btn" data-bs-toggle="tab" data-bs-target="#tab-dados" type="button" role="tab" aria-controls="tab-dados" aria-selected="true">Dados</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold" id="tab-relato-btn" data-bs-toggle="tab" data-bs-target="#tab-relato" type="button" role="tab" aria-controls="tab-relato" aria-selected="false"><?= $isEdit ? 'Relato e Defeitos' : 'Relato do Cliente' ?></button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold" id="tab-fotos-btn" data-bs-toggle="tab" data-bs-target="#tab-fotos" type="button" role="tab" aria-controls="tab-fotos" aria-selected="false">Fotos</button>
                        </li>
                        <?php if ($isEdit): ?>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold" id="tab-financeiro-btn" data-bs-toggle="tab" data-bs-target="#tab-financeiro" type="button" role="tab" aria-controls="tab-financeiro" aria-selected="false">Pe�as e Or�amento</button>
                        </li>
                        <?php endif; ?>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="tab-dados" role="tabpanel" aria-labelledby="tab-dados-btn" tabindex="0">

                    <div class="os-data-section mb-4">
                        <div class="os-data-section-title">
                            <i class="bi bi-people me-1"></i>Cliente, Equipamento e T�cnico Respons�vel
                        </div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label d-flex align-itemês-center gap-2">
                                Cliente *
                                <?php if (can('clientes', 'criar')): ?>
                                <button class="btn btn-warning btn-sm py-0 px-2" type="button" id="btnNãovoCliente"
                                        title="Cadastrar nãovo cliente" style="font-size:0.75rem; border-radius:6px; line-height:1.6;">
                                    <i class="bi bi-plus-lg"></i> Nãovo
                                </button>
                                <?php endif; ?>
                            </label>
                            <select name="cliente_id" id="clienteOsSelect" class="form-select select2-clientes" required>
                                <option value="">Selecione o cliente...</option>
                                <?php foreach ($clientes as $c): ?>
                                <option value="<?= $c['id'] ?>"
                                    <?= ($clienteSelecionadoNãoForm === (int) $c['id']) ? 'selected' : '' ?>>
                                    <?= esc($c['nãome_razao']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (!$isEdit && $isOrigemCentralWhatsapp && $clienteSelecionadoNãoForm <= 0): ?>
                            <div class="form-text text-warning">
                                Este contato ainda nao esta vinculado ao cadastro de clientes. Selecione o cliente para abrir a OS.
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label d-flex align-itemês-center gap-2">
                                Equipamento *
                                <?php if (can('equipamentos', 'criar')): ?>
                                <button class="btn btn-warning btn-sm py-0 px-2" type="button" id="btnNãovoEquipamento"
                                        title="Cadastrar nãovo equipamento" style="font-size:0.75rem; border-radius:6px; line-height:1.6;">
                                    <i class="bi bi-plus-lg"></i> Nãovo
                                </button>
                                <?php endif; ?>
                            </label>
                            <select name="equipamento_id" id="equipamentoSelect" class="form-select select2-equip" required>
                                <option value="">Selecione o cliente primeiro...</option>
                                <?php if ($isEdit && !empty($equipamentos)): foreach ($equipamentos as $eq): ?>
                                <option value="<?= $eq['id'] ?>"
                                    data-tipo="<?= $eq['tipo_id'] ?? '' ?>"
                                    data-marca="<?= esc($eq['marca_nãome'] ?? $eq['marca'] ?? '') ?>"
                                    data-modelo="<?= esc($eq['modelo_nãome'] ?? $eq['modelo'] ?? '') ?>"
                                    data-serie="<?= esc($eq['numero_serie'] ?? '') ?>"
                                    data-cor="<?= esc($eq['cor'] ?? '') ?>"
                                    data-cor_hex="<?= esc($eq['cor_hex'] ?? '') ?>"
                                    data-tipo_nãome="<?= esc($eq['tipo_nãome'] ?? $eq['tipo'] ?? '') ?>"
                                    data-marca_id="<?= esc($eq['marca_id'] ?? '') ?>"
                                    data-modelo_id="<?= esc($eq['modelo_id'] ?? '') ?>"
                                    data-cliente_id="<?= esc($eq['cliente_id'] ?? '') ?>"
                                    data-senha_acessão="<?= esc($eq['senha_acessão'] ?? '') ?>"
                                    data-estado_fisico="<?= esc($eq['estado_fisico'] ?? '') ?>"
                                    data-acessãorios="<?= esc($eq['acessãorios'] ?? '') ?>"
                                    <?= $os['equipamento_id'] == $eq['id'] ? 'selected' : '' ?>>
                                    <?= esc(($eq['marca_nãome'] ?? $eq['marca'] ?? '') . ' ' . ($eq['modelo_nãome'] ?? $eq['modelo'] ?? '') . ' (' . ($eq['tipo_nãome'] ?? $eq['tipo'] ?? '') . ')') ?>
                                </option>
                                <?php endforeach; endif; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">T�cnico Respons�vel</label>
                            <select name="tecnico_id" class="form-select">
                                <option value="">N�o atribu�do</option>
                                <?php foreach ($tecnicos as $t): ?>
                                <option value="<?= $t['id'] ?>"
                                    <?= ($isEdit && ($os['tecnico_id'] ?? '') == $t['id']) ? 'selected' : '' ?>>
                                    <?= esc($t['nãome']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    </div>

                    <div class="os-data-section mb-4">
                        <div class="os-data-section-title">
                            <i class="bi bi-calendar-check me-1"></i>Prioridade, Entrada, Previs�o e Status
                        </div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label class="form-label">Prioridade</label>
                            <select name="prioridade" class="form-select">
                                <option value="baixa"   <?= ($isEdit && $os['prioridade'] === 'baixa')   ? 'selected' : '' ?>>Baixa</option>
                                <option value="nãormal"  <?= (!$isEdit || $os['prioridade'] === 'nãormal')  ? 'selected' : '' ?>>Nãormal</option>
                                <option value="alta"    <?= ($isEdit && $os['prioridade'] === 'alta')    ? 'selected' : '' ?>>Alta</option>
                                <option value="urgente" <?= ($isEdit && $os['prioridade'] === 'urgente') ? 'selected' : '' ?>>Urgente</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Data de Entrada *</label>
                            <input type="datetime-local" name="data_entrada" class="form-control"
                                   value="<?= $isEdit ? ($os['data_entrada'] ?? date('Y-m-d\TH:i')) : date('Y-m-d\TH:i') ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Previs�o de Entrega</label>
                            <select id="prazoEntregaSelect" class="form-select mb-2">
                                <option value="">Prazo (dias)</option>
                                <option value="1">1 dia</option>
                                <option value="3">3 dias</option>
                                <option value="7">7 dias</option>
                                <option value="30">30 dias</option>
                            </select>
                            <input type="date" name="data_previsao" class="form-control"
                                   value="<?= $isEdit ? ($os['data_previsao'] ?? '') : '' ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <?php if (!empty($statusGrouped)): ?>
                                    <?php foreach ($statusGrouped as $macro => $itemês): ?>
                                        <?php if (empty($itemês) || !is_array($itemês)) continue; ?>
                                        <optgroup label="<?= esc(ucwords(str_replace('_', ' ', (string) $macro))) ?>">
                                            <?php foreach ($itemês as $item): ?>
                                                <?php $codigo = (string) ($item['codigo'] ?? ''); ?>
                                                <?php if ($codigo === '') continue; ?>
                                                <option value="<?= esc($codigo) ?>" data-status-cor="<?= esc((string) ($item['cor'] ?? 'secondary')) ?>" <?= ((string) ($os['status'] ?? $statusDefault) === $codigo) ? 'selected' : '' ?>>
                                                    <?= esc((string) ($item['nãome'] ?? $codigo)) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <?php $currStatus = (string) ($os['status'] ?? $statusDefault); ?>
                                    <option value="triagem" <?= $currStatus === 'triagem' ? 'selected' : '' ?>>Triagem</option>
                                    <option value="diagnãostico" <?= $currStatus === 'diagnãostico' ? 'selected' : '' ?>>Diagnãostico Tecnico</option>
                                    <option value="aguardando_orcamento" <?= $currStatus === 'aguardando_orcamento' ? 'selected' : '' ?>>Aguardando Orcamento</option>
                                    <option value="aguardando_autorizacao" <?= $currStatus === 'aguardando_autorizacao' ? 'selected' : '' ?>>Aguardando Autorizacao</option>
                                    <option value="reparo_execucao" <?= $currStatus === 'reparo_execucao' ? 'selected' : '' ?>>Em Execucao</option>
                                    <option value="reparado_disponivel_loja" <?= $currStatus === 'reparado_disponivel_loja' ? 'selected' : '' ?>>Pronto para retirada</option>
                                    <option value="entregue_reparado" <?= $currStatus === 'entregue_reparado' ? 'selected' : '' ?>>Entregue</option>
                                    <option value="cancelado" <?= $currStatus === 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>

                    </div>

                    <!-- LINHA EXTRA (edi��o): Garantia -->
                    <?php if ($isEdit): ?>
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label class="form-label">Garantia (dias)</label>
                            <input type="number" name="garantia_dias" class="form-control"
                                   value="<?= $os['garantia_dias'] ?? 90 ?>">
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="os-data-section mb-4">
                        <div class="os-data-section-title">
                            <i class="bi bi-shield-exclamation me-1"></i>Estado fisico do equipamento
                        </div>
                        <div class="border rounded-3 p-3 bg-white bg-opacity-10">
                            <div class="d-flex flex-wrap gap-2 mb-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-estado-key="tela_trincada">+ Tela trincada</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-estado-key="arranhoes">+ Arranhoes</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-estado-key="carcaca_quebrada">+ Carcaca quebrada</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-estado-key="vidro_traseiro_quebrado">+ Vidro traseiro quebrado</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-estado-key="amassado">+ Amassado</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-estado-key="botao_quebrado">+ Botao quebrado</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-estado-key="outro">+ Outro danão</button>
                            </div>
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="estadoFisicoSemAvarias" value="1">
                                <label class="form-check-label" for="estadoFisicoSemAvarias">Sem avarias aparentes na entrada</label>
                            </div>
                            <div id="estadoFisicoQuickForm" class="border rounded p-3 bg-body-tertiary mb-3 d-nãone">
                                <div class="d-flex justify-content-between align-itemês-center mb-2">
                                    <strong id="estadoFisicoQuickTitle"></strong>
                                    <button type="button" class="btn-close" id="estadoFisicoQuickClose"></button>
                                </div>
                                <div id="estadoFisicoQuickFields" class="row g-2"></div>
                                <div class="mt-3">
                                    <button type="button" class="btn btn-sm btn-primary" id="estadoFisicoQuickSave">Salvar item</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="estadoFisicoQuickCancel">Cancelar</button>
                                </div>
                            </div>
                            <div id="estadoFisicoList" class="list-group"></div>
                            <small class="form-text text-muted mt-3">Registre danãos observados na recepcao com foto para evidenciar o estado de entrada.</small>
                            <textarea name="estado_fisico" id="estadoFisicoInput" class="d-nãone"><?= $isEdit ? esc($os['estado_fisico'] ?? '') : old('estado_fisico') ?></textarea>
                            <input type="hidden" name="estado_fisico_data" id="estadoFisicoDataInput">
                            <input type="file" id="estadoFisicoPhotoInput" class="d-nãone" accept="image/jpeg,image/png,image/webp" multiple>
                            <div id="estadoFisicoFilesInputs" class="d-nãone"></div>
                        </div>
                    </div>

                    <div class="os-data-section mb-4">
                            <div class="os-data-section-title">
                                <i class="bi bi-box-seam me-1"></i>Acess�rios e Componentes (na entrada)
                            </div>
                            <div class="border rounded-3 p-3 bg-white bg-opacity-10">
                                <div class="d-flex flex-wrap gap-2 mb-2">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-acessãorio-key="chip">+ Chip</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-acessãorio-key="capinha">+ Capinha celular</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-acessãorio-key="capa">+ Capa</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-acessãorio-key="mochila">+ Mochila</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-acessãorio-key="bolsa">+ Bolsa nãotebook</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-acessãorio-key="cabo">+ Cabo</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-acessãorio-key="carregador">+ Carregador</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-acessãorio-key="outro">+ Outro acess�rio</button>
                                </div>
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="acessãoriosSemItens" name="acessãorios_sem_itens" value="1" <?= old('acessãorios_sem_itens') ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="acessãoriosSemItens">Equipamento recebido sem acess�rios</label>
                                </div>
                                <div id="acessãoriosQuickForm" class="border rounded p-3 bg-body-tertiary mb-3 d-nãone">
                                    <div class="d-flex justify-content-between align-itemês-center mb-2">
                                        <strong id="acessãoriosQuickTitle"></strong>
                                        <button type="button" class="btn-close" id="acessãoriosQuickClose"></button>
                                    </div>
                                    <div id="acessãoriosQuickFields" class="row g-2"></div>
                                    <div class="mt-3">
                                        <button type="button" class="btn btn-sm btn-primary" id="acessãoriosQuickSave">Salvar item</button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" id="acessãoriosQuickCancel">Cancelar</button>
                                    </div>
                                </div>
                                <div id="acessãoriosList" class="list-group"></div>
                                <small class="form-text text-muted mt-3">Padrãonize rapidamente o registro de acess�rios comuns.</small>
                                <textarea name="acessãorios" id="acessãoriosInput" class="d-nãone"><?= $isEdit ? esc($os['acessãorios'] ?? '') : old('acessãorios') ?></textarea>
                                <input type="hidden" name="acessãorios_data" id="acessãoriosDataInput">
                                <input type="file" id="acessãoriosPhotoInput" class="d-nãone" accept="image/jpeg,image/png,image/webp" multiple>
                                <div id="acessãoriosFilesInputs" class="d-nãone"></div>
                            </div>
                    </div>

                        </div>
                        <div class="tab-pane fade" id="tab-relato" role="tabpanel" aria-labelledby="tab-relato-btn" tabindex="0">
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <label class="form-label">Relato do Cliente *</label>
                            <?php if (!$isEdit): ?>
                            <div class="mb-3">
                                <div id="relatoQuickButtons" class="d-flex flex-wrap gap-2 relato-quick-grid">
                                    <?php if (!empty($relatosRapidos)): ?>
                                        <?php foreach ($relatosRapidos as $categoria): ?>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <?= esc($categoria['icone'] ?? '?') ?> <?= esc($categoria['categoria'] ?? 'Relatos') ?>
                                                </button>
                                                <ul class="dropdown-menu shadow-sm">
                                                    <?php foreach (($categoria['itens'] ?? []) as $item): ?>
                                                        <li>
                                                            <button type="button" class="dropdown-item btn-relato-opcao" data-relato-opcao="<?= esc($item['texto_relato'] ?? '') ?>">
                                                                <?= esc($item['texto_relato'] ?? '') ?>
                                                            </button>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span class="text-muted small">
                                            Nenhum relato r�pido ativo. Cadastre em
                                            <a href="<?= base_url('defeitosrelatados') ?>">Defeitos Relatados</a>.
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <small class="text-muted d-block mt-2">Clique em uma op��o para inserir não relato.</small>
                            </div>
                            <?php endif; ?>
                            <textarea name="relato_cliente" id="relatoClienteInput" class="form-control" rows="6"><?= $isEdit ? esc($os['relato_cliente']) : old('relato_cliente') ?></textarea>
                            <?php if (!$isEdit): ?>
                            <small class="text-muted d-block mt-2">Você� pode complementar manualmente o relato a qualquer momento.</small>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($isEdit): ?>
                    <div class="row g-3 mb-4" id="defeitosSection" style="display:nãone;">
                        <div class="col-12">
                            <div class="card" style="background: rgba(255,255,255,0.04); border: 1px sãolid rgba(255,255,255,0.1); border-radius: 10px;">
                                <div class="card-header py-2" style="background: transparent; border-bottom: 1px sãolid rgba(255,255,255,0.1);">
                                    <strong><i class="bi bi-bug me-2 text-warning"></i>Defeitos Comuns do Tipo de Equipamento</strong>
                                    <small class="text-muted mês-2">(opcional ? selecione os que se aplicam)</small>
                                </div>
                                <div class="card-body" id="defeitosContainer">
                                    <span class="text-muted small">Selecione o equipamento para carregar os defeitos...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                        </div>
                        <div class="tab-pane fade" id="tab-fotos" role="tabpanel" aria-labelledby="tab-fotos-btn" tabindex="0">
                    <!-- FOTOS DE ENTRADA DO EQUIPAMENTO -->
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                    <div class="card" style="background: rgba(255,255,255,0.04); border: 1px sãolid rgba(255,255,255,0.1); border-radius: 16px;">
                        <div class="card-header py-3 d-flex flex-column flex-md-row justify-content-between gap-2" style="background: transparent; border-bottom: 1px sãolid rgba(255,255,255,0.1);">
                            <div>
                                <strong><i class="bi bi-camera me-2 text-info"></i>Fotos de Entrada do Equipamento</strong>
                                <small class="text-muted mês-2">(opcional ? acess�rios, estado f�sico, placa interna, etc.)</small>
                            </div>
                            <div class="d-flex justify-content-center justify-content-md-end gap-2 flex-wrap">
                                <button type="button" class="btn btn-outline-light btn-sm d-nãone" id="btnFotosEscolher">
                                    <i class="bi bi-folder2-open me-1"></i>Escolher Arquivos
                                </button>
                                <button type="button" class="btn btn-primary btn-sm rounded-pill px-3" id="btnFotosEntradaCamera">
                                    <i class="bi bi-camera-fill me-1"></i>Capturar Foto
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3" id="btnFotosEntradaGaleria">
                                    <i class="bi bi-images me-1"></i>Abrir Galeria
                                </button>
                                <button type="button" class="btn btn-outline-warning btn-sm" id="btnLimparFotos">
                                    <i class="bi bi-trash me-1"></i>Limpar
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <input type="file" id="fotosEntradaGaleriaInput" accept="image/*" multiple class="d-nãone">
                            <input type="file" name="fotos_entrada[]" id="fotosEntradaInput"
                                   accept="image/jpeg,image/png,image/webp"
                                   multiple class="d-nãone">
                            <div class="p-3 border rounded bg-light bg-opacity-10 mb-4 text-center py-4" id="fotosEntradaEmptyState" style="display:nãone;">
                                <i class="bi bi-cloud-upload display-5 text-muted opacity-25"></i>
                                <h6 class="mt-3 text-muted mb-1">Nenhuma foto anexada</h6>
                                <p class="text-muted small mb-0">Use Capturar Foto ou Abrir Galeria para adicionar as imagens da entrada.</p>
                            </div>
                            <div class="alert alert-info border-0 shadow-sm d-flex align-itemês-center mb-3 mx-auto" style="max-width: 680px;">
                                <i class="bi bi-info-circle-fill fs-5 me-2"></i>
                                <div class="small">At&eacute; <strong>4 fotos</strong>, 2MB cada. O sistema abre o ajuste de corte antes de importar.</div>
                            </div>
                            <div id="osFotosDropzone" class="border rounded-4 d-nãone align-itemês-center justify-content-center flex-column gap-2 text-center py-4 mb-3"
                                 style="min-height: 180px; transition: background 0.2s;">
                                <i class="bi bi-cloud-upload display-4 text-muted"></i>
                                <p class="text-muted mb-0 fw-semibold">Clique para selecionar ou arraste arquivos aqui.</p>
                                <small class="text-muted">At� 4 fotos, 2MB cada.</small>
                            </div>
                            <div id="osFotosPreview" class="d-flex flex-wrap justify-content-center gap-3"></div>
                            <div id="osFotosExisting" class="d-flex flex-wrap justify-content-center gap-3 mt-3"></div>
                        </div>
                    </div>
                        </div>
                    </div>

                        </div>
                        <?php if ($isEdit): ?>
                        <div class="tab-pane fade" id="tab-financeiro" role="tabpanel" aria-labelledby="tab-financeiro-btn" tabindex="0">
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <div class="card" style="background: rgba(255,255,255,0.04); border: 1px sãolid rgba(255,255,255,0.1); border-radius: 10px;">
                                <div class="card-header py-2" style="background: transparent; border-bottom: 1px sãolid rgba(255,255,255,0.1);">
                                    <strong><i class="bi bi-box-seam me-2 text-primary"></i>Pe�as e Servi�os</strong>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted small mb-2">Adicione pe�as e servi�os na tela de visualiza��o da OS.</p>
                                    <a href="<?= base_url('os/visualizar/' . $os['id']) ?>" class="btn btn-sm btn-outline-info">Abrir OS e lan�ar itens</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">Forma de Pagamento</label>
                            <select name="forma_pagamento" class="form-select">
                                <?php
                                $formas = [
                                    '' => 'N�o definido',
                                    'dinheiro' => 'Dinheiro',
                                    'pix' => 'Pix',
                                    'cartao_credito' => 'Cart�o de Cr�dito',
                                    'cartao_debito' => 'Cart�o de D�bito',
                                    'transferencia' => 'Transfer�ncia',
                                    'boleto' => 'Boleto',
                                ];
                                foreach ($formas as $val => $label): ?>
                                <option value="<?= $val ?>" <?= ($isEdit && ($os['forma_pagamento'] ?? '') === $val) ? 'selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Diagn�stico -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Diagn�stico T�cnico</label>
                            <textarea name="diagnãostico_tecnico" class="form-control" rows="3"><?= esc($os['diagnãostico_tecnico'] ?? '') ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Sãolu��o Aplicada</label>
                            <textarea name="sãolucao_aplicada" class="form-control" rows="3"><?= esc($os['sãolucao_aplicada'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <!-- Valores -->
                    <h6 class="text-uppercase text-muted mb-3"><i class="bi bi-currency-dollar me-1"></i>Valores</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label class="form-label">M�o de Obra (R$)</label>
                            <input type="number" step="0.01" name="valor_mao_obra" class="form-control" value="<?= $os['valor_mao_obra'] ?? 0 ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Pe�as (R$)</label>
                            <input type="number" step="0.01" name="valor_pecas" class="form-control" readonly value="<?= $os['valor_pecas'] ?? 0 ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Desconto (R$)</label>
                            <input type="number" step="0.01" name="desconto" class="form-control" value="<?= $os['desconto'] ?? 0 ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Valor Final (R$)</label>
                            <input type="number" step="0.01" name="valor_final" class="form-control" readonly value="<?= $os['valor_final'] ?? 0 ?>">
                        </div>
                    </div>

                    <!-- Observa��es -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Observa��es Internas</label>
                            <textarea name="observacoes_internas" class="form-control" rows="2"><?= esc($os['observacoes_internas'] ?? '') ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Observa��es para o Cliente</label>
                            <textarea name="observacoes_cliente" class="form-control" rows="2"><?= esc($os['observacoes_cliente'] ?? '') ?></textarea>
                        </div>
                    </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="os-form-actions">
                        <button type="submit" class="btn btn-glow">
                            <i class="bi bi-check-lg me-1"></i><?= $isEdit ? 'Atualizar' : 'Abrir OS' ?>
                        </button>
                        <a href="<?= base_url('os') ?>" class="btn btn-outline-secondary">Cancelar</a>
                        <?php if (!$isEdit): ?>
                        <button type="button" class="btn btn-outline-warning" id="btnLimparRascunho">
                            <i class="bi bi-trash3 me-1"></i>Limpar rascunho
                        </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
</div><!-- /formCol -->
</div><!-- /row -->
</div>

<!-- ===== MODAL: CADASTRAR NOVO CLIENTE ===== -->
<div class="modal fade" id="modalNãovoCliente" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content glass-card">
            <div class="modal-header border-bottom">
                <h5 class="modal-title">
                    <i class="bi bi-persãon-plus text-warning me-2"></i>Cadastro R�pido de Cliente
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formNãovoClienteAjax">
                    <?= csrf_field() ?>
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Nãome / Raz�o Sãocial *</label>
                            <input type="text" name="nãome_razao" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Telefone 1 *</label>
                            <input type="text" name="telefone1" class="form-control mask-telefone" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted">Email (Opcional)</label>
                            <input type="email" name="email" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted">CPF / CNPJ (Opcional)</label>
                            <input type="text" name="cpf_cnpj" class="form-control">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label text-muted">Nãome Contato (Opcional)</label>
                            <input type="text" name="nãome_contato" class="form-control" placeholder="Esposa, Fllho...">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted">Telefone do Contato (Opcional)</label>
                            <input type="text" name="telefone_contato" class="form-control mask-telefone">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label text-muted">CEP</label>
                            <input type="text" name="cep" class="form-control mask-cep">
                        </div>
                        <div class="col-md-7">
                            <label class="form-label text-muted">Endere�o</label>
                            <input type="text" name="endereco" class="form-control js-logradouro">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label text-muted">N�</label>
                            <input type="text" name="numero" class="form-control js-numero">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label text-muted">Bairro</label>
                            <input type="text" name="bairro" class="form-control js-bairro">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label text-muted">Cidade</label>
                            <input type="text" name="cidade" class="form-control js-cidade">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label text-muted">UF</label>
                            <input type="text" name="uf" class="form-control js-uf" maxlength="2">
                        </div>
                    </div>
                    <div id="modalClienteErrors" class="alert alert-danger mt-3 d-nãone"></div>
                </form>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-glow" id="btnSalvarNãovoCliente">
                    <i class="bi bi-check-lg me-1"></i>Cadastrar Cliente
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ===== MODAL: CADASTRAR NOVO EQUIPAMENTO ===== -->
<div class="modal fade" id="modalNãovoEquipamento" tabindex="-1" aria-labelledby="labelModalNãovoEquip">
    <div class="modal-dialog modal-lg">
        <div class="modal-content glass-card">
            <div class="modal-header border-bottom">
                <h5 class="modal-title" id="labelModalNãovoEquip">
                    <i class="bi bi-plus-circle text-warning me-2"></i>Cadastrar Nãovo Equipamento
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formNãovoEquipAjax" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    
                    <!-- Navega��o por Abas não Modal -->
                    <ul class="nav nav-pills nav-fill mb-3 bg-light p-1 rounded-3" id="modalEquipTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active small py-1" id="m-info-tab" data-bs-toggle="tab" data-bs-target="#m-info-pane" type="button" role="tab"><i class="bi bi-info-circle me-1"></i>Info</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link small py-1" id="m-cor-tab" data-bs-toggle="tab" data-bs-target="#m-cor-pane" type="button" role="tab"><i class="bi bi-palette me-1"></i>Cor</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link small py-1" id="m-foto-tab" data-bs-toggle="tab" data-bs-target="#m-foto-pane" type="button" role="tab"><i class="bi bi-camera me-1"></i>Foto</button>
                        </li>
                    </ul>

                    <div class="tab-content" id="modalEquipTabsContent">
                        <!-- ABA 1: INFO -->
                        <div class="tab-pane fade show active" id="m-info-pane" role="tabpanel">
                            <div class="row g-2">
                                <div class="col-md-6 text-start">
                                    <label class="form-label mb-1 small fw-bold">Tipo *</label>
                                    <select name="tipo_id" id="nãovoEquipTipo" class="form-select form-select-sm" required>
                                        <option value="">Escolha...</option>
                                        <?php foreach ($tipos as $t): ?>
                                        <option value="<?= $t['id'] ?>"><?= esc($t['nãome']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 text-start">
                                    <label class="form-label mb-1 small fw-bold">Marca *</label>
                                    <div class="input-group input-group-sm">
                                        <select name="marca_id" id="nãovoEquipMarca" class="form-select select2-modal" required>
                                            <option value="">Marca...</option>
                                            <?php foreach ($marcas as $m): ?>
                                            <option value="<?= $m['id'] ?>"><?= esc($m['nãome']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button class="btn btn-warning" type="button" id="btnNãovaMarcaOS"><i class="bi bi-plus"></i></button>
                                    </div>
                                </div>
                                <div class="col-md-6 text-start mt-2">
                                    <label class="form-label mb-1 small fw-bold">Modelo *</label>
                                    <div class="input-group input-group-sm">
                                        <select name="modelo_id" id="nãovoEquipModelo" class="form-select" required>
                                            <option value="">Modelo...</option>
                                        </select>
                                        <button class="btn btn-warning" type="button" id="btnNãovoModeloOS"><i class="bi bi-plus"></i></button>
                                    </div>
                                    <input type="hidden" name="modelo_nãome_ext" id="nãovoEquipModeloNãomeExt">
                                </div>
                                <div class="col-md-6 text-start mt-2">
                                    <label class="form-label mb-1 small fw-bold">N� de S�rie</label>
                                    <input type="text" name="numero_serie" class="form-control form-control-sm" placeholder="IMEI ou S�rie">
                                </div>
                                <div class="col-12 text-start mt-2">
                                    <label class="form-label mb-1 small d-flex justify-content-between">
                                        <span class="fw-bold">Senha de Acessão</span>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-light border py-0 px-2 btn-senha-tipo-os" data-placeholder="Num�rico (PIN)" title="PIN/Desenho" style="font-size:0.65rem;">PIN</button>
                                            <button type="button" class="btn btn-light border py-0 px-2 btn-senha-tipo-os" data-placeholder="Alfanum�rico" title="Texto" style="font-size:0.65rem;">TEXTO</button>
                                        </div>
                                    </label>
                                    <input type="text" name="senha_acessão" id="inputSenhaAcessãoOS" class="form-control form-control-sm" placeholder="Senha do aparelho">
                                </div>
                                <div class="col-md-6 text-start mt-2">
                                    <label class="form-label mb-1 small fw-bold text-muted">Estado F�sico</label>
                                    <textarea name="estado_fisico" class="form-control form-control-sm" rows="2" placeholder="Ex: Tela riscada..."></textarea>
                                </div>
                                <div class="col-md-6 text-start mt-2">
                                    <label class="form-label mb-1 small fw-bold text-muted d-flex justify-content-between">
                                        Acess�rios
                                        <span style="font-size:0.6rem;">+ R�pido</span>
                                    </label>
                                    <textarea name="acessãorios" id="textareaAcessãoriosOS" class="form-control form-control-sm mb-1" rows="2" placeholder="Cabos, capas..."></textarea>
                                    <div class="d-flex flex-wrap gap-1">
                                        <button type="button" class="badge btn btn-light border p-1 fw-nãormal btn-quick-acessãorio-os" style="font-size:0.6rem; color:#666;">+ Carregador</button>
                                        <button type="button" class="badge btn btn-light border p-1 fw-nãormal btn-quick-acessãorio-os" style="font-size:0.6rem; color:#666;">+ Cabo</button>
                                        <button type="button" class="badge btn btn-light border p-1 fw-nãormal btn-quick-acessãorio-os" style="font-size:0.6rem; color:#666;">+ Capa</button>
                                        <button type="button" class="badge btn btn-light border p-1 fw-nãormal btn-quick-acessãorio-os" style="font-size:0.6rem; color:#666;">+ Chip</button>
                                        <button type="button" class="badge btn btn-light border p-1 fw-nãormal btn-quick-acessãorio-os" style="font-size:0.6rem; color:#666;">+ Cart�o</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ABA 2: COR -->
                        <div class="tab-pane fade" id="m-cor-pane" role="tabpanel">
                            <div class="p-2 border rounded bg-light bg-opacity-25">
                                <input type="hidden" name="cor_hex" id="corHexRealOS" value="#1A1A1A">
                                <input type="hidden" name="cor_rgb" id="corRgbRealOS" value="26,26,26">
                                <input type="hidden" name="cor" id="corNãomeRealOS" value="Preto">

                                <!-- Smart Detection -->
                                <div class="p-2 mb-2 rounded border border-warning border-opacity-50 bg-warning bg-opacity-10 d-nãone" id="smartColorContainerOS">
                                    <div class="d-flex justify-content-between align-itemês-center mb-1">
                                        <span style="font-size: 0.65rem;" class="text-warning fw-semibold"><i class="bi bi-magic me-1"></i>Sugerido da foto:</span>
                                        <button type="button" class="btn btn-sm text-success p-0 border-0 fw-bold" id="btnAcceptColorOS" style="font-size: 0.7rem;">Aplicar <i class="bi bi-check2-circle mês-1"></i></button>
                                    </div>
                                    <div class="d-flex align-itemês-center gap-2">
                                        <div id="smartColorSwatchOS" class="rounded-circle shadow border" style="width: 20px; height: 20px;"></div>
                                        <strong id="smartColorNameOS" style="font-size: 0.8rem;">Nenhuma</strong>
                                    </div>
                                </div>

                                <div class="row g-2">
                                    <div class="col-md-5">
                                        <div id="colorPreviewBoxOS" class="rounded-3 shadow-sm border mb-2 d-flex flex-column align-itemês-center justify-content-center" style="height: 80px; background: #1A1A1A; transition: background 0.3s ease;">
                                            <span id="colorPreviewHexOS" class="fw-bold font-monãospace" style="font-size: 0.85rem; color: #fff;">#1A1A1A</span>
                                            <span id="colorPreviewNameOS" class="mt-1" style="font-size: 0.7rem; color: rgba(255,255,255,0.8);">Preto</span>
                                        </div>
                                        <div class="d-flex gap-2 mb-2">
                                            <input type="color" id="corHexPickerOS" class="form-control form-control-color p-1" value="#1A1A1A" style="width: 40px; height: 32px;">
                                            <input type="text" id="corNãomeInputOS" class="form-control form-control-sm" placeholder="Nãome" value="Preto">
                                        </div>
                                        <div id="coresProximasGridOS" class="d-flex flex-wrap gap-1 mb-2"></div>
                                    </div>
                                    <div class="col-md-7">
                                        <div id="colorCatalogOS" style="max-height: 180px; overflow-y: auto;" class="pe-1 custom-scrollbar"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ABA 3: FOTO -->
                        <div class="tab-pane fade text-center py-2" id="m-foto-pane" role="tabpanel">
                            <div class="d-flex justify-content-center gap-2 mb-3">
                                <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3" id="btnAbrirCamera">
                                    <i class="bi bi-camera me-1"></i>Tirar Foto
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3" id="btnAbrirGaleria">
                                    <i class="bi bi-images me-1"></i>Galeria
                                </button>
                                <input type="file" name="fotos[]" id="nãovoEquipFoto" class="d-nãone" accept="image/jpeg,image/png,image/webp" multiple>
                            </div>

                            <div id="nãovoEquipFotoPreview" class="mt-2" style="display:nãone;">
                                <div id="nãovoEquipFotosNãovasList" class="d-flex flex-wrap gap-2 justify-content-center"></div>
                                <div class="mt-2 small text-muted">A foto de perfil ajuda na identifica��o visual r�pida.</div>
                            </div>
                            
                            <div id="fotoVaziaOS" class="py-4 text-muted opacity-50">
                                <i class="bi bi-image fs-1 d-block"></i>
                                <span class="small font-monãospace">Nenhuma imagem selecionada</span>
                            </div>

                            <div id="modalEquipFotosExistentesWrap" class="mt-3 d-nãone">
                                <div class="small text-muted mb-2">Fotos j� cadastradas neste equipamento</div>
                                <div id="modalEquipFotosExistentes" class="d-flex flex-wrap gap-2 justify-content-center"></div>
                            </div>
                    </div>
                    <div id="modalEquipErrors" class="alert alert-danger mt-3 d-nãone p-2 small"></div>
                </form>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-glow" id="btnSalvarNãovoEquip">
                    <i class="bi bi-check-lg me-1"></i>Cadastrar Equipamento
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ===== MODAL: NOVA MARCA (AUXILIAR) ===== -->
<div class="modal fade" id="modalNãovaMarcaOS" tabindex="-1" style="z-index: 1070;">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content glass-card shadow-lg">
            <div class="modal-header border-bottom">
                <h5 class="modal-title font-title"><i class="bi bi-tag text-warning me-2"></i>Nãova Marca</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="text" id="inputNãovaMarcaOS" class="form-control" placeholder="Ex: Samêsung, Apple...">
                <div id="errorNãovaMarcaOS" class="text-danger small mt-2 d-nãone"></div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-glow w-100" id="btnSalvarMarcaOS">Salvar Marca</button>
            </div>
        </div>
    </div>
</div>

<!-- ===== MODAL: NOVO MODELO (AUXILIAR) ===== -->
<div class="modal fade" id="modalNãovoModeloOS" tabindex="-1" style="z-index: 1070;">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content glass-card shadow-lg">
            <div class="modal-header border-bottom">
                <h5 class="modal-title font-title"><i class="bi bi-cpu text-warning me-2"></i>Nãovo Modelo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="small text-muted">Marca Selecionada:</label>
                    <input type="text" id="displayMarcaOS" class="form-control form-control-sm bg-transparent" readonly>
                </div>
                <div class="mb-2">
                    <label class="form-label fw-bold mb-1">Nãome do Modelo *</label>
                    <div class="position-relative">
                        <input type="text" id="inputNãovoModeloOS" class="form-control"
                               placeholder="Ex: Galaxy S24, iPhone 15, Moto G84..."
                               autocomplete="off">
                        <div id="spinnerNãovoModeloOS" class="position-absãolute top-50 end-0 translate-middle-y me-2 d-nãone">
                            <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                        </div>
                    </div>
                    <!-- Dropdown de sugest�es -->
                    <div id="sugestoesNãovoModeloOS" class="list-group shadow-lg mt-1 d-nãone"
                         style="max-height: 220px; overflow-y: auto; border-radius: 8px; z-index: 9999; position: relative;"></div>
                    <div class="form-text mt-1">
                        <i class="bi bi-globe2 me-1 text-info"></i>
                        Digite 3+ caracteres para ver sugest�es da internet
                    </div>
                </div>
                <div id="errorNãovoModeloOS" class="text-danger small mt-2 d-nãone"></div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-glow" id="btnSalvarModeloOS">
                    <i class="bi bi-check-lg me-1"></i>Salvar Modelo
                </button>
            </div>
        </div>
    </div>
</div>


<!-- ===== MODAL: C�MERA (AUXILIAR) ===== -->
<div class="modal fade" id="modalCamera" tabindex="-1" style="z-index: 2000;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-card border-0 shadow-lg">
            <div class="modal-header border-bottom border-light">
                <h5 class="modal-title"><i class="bi bi-camera me-2 text-warning"></i>Capturar Foto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-0 overflow-hidden bg-black" style="min-height: 300px;">
                <video id="videoCamera" class="w-100 h-100" style="object-fit: cover;" autoplay playsinline></video>
                <canvas id="canvasCamera" class="d-nãone"></canvas>
            </div>
            <div class="modal-footer border-top border-light justify-content-center p-3">
                <button type="button" class="btn btn-glow btn-lg rounded-pill px-5" id="btnCapturar">
                   <i class="bi bi-record-circle me-2"></i>Capturar Agora
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ===== MODAL: EDITOR DE IMAGEM (CROP) ===== -->
<div class="modal fade" id="modalCropEquip" tabindex="-1" style="z-index: 2100;">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content glass-card shadow-lg">
            <div class="modal-header border-bottom">
                <h5 class="modal-title font-title" id="modalCropTitle"><i class="bi bi-crop text-warning me-2"></i>Ajustar Foto do Equipamento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0 overflow-hidden bg-black" style="max-height: 70vh;">
                <img id="imgToCrop" src="" style="max-width: 100%; display: block;">
            </div>
            <div class="modal-footer border-top d-flex justify-content-between">
                <div class="btn-group">
                    <button type="button" class="btn btn-outline-light btn-sm" id="btnRotateLeft"><i class="bi bi-arrow-counterclockwise"></i></button>
                    <button type="button" class="btn btn-outline-light btn-sm" id="btnRotateRight"><i class="bi bi-arrow-clockwise"></i></button>
                </div>
                <button type="button" class="btn btn-glow" id="btnConfirmCrop">
                    <i class="bi bi-check-lg me-1"></i>Finalizar Corte
                </button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<!-- SweetAlert2 (confirm dialogs) -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- Cropper.js -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
<style>
    .custom-color-accordion .accordion-button { transition: all 0.2s ease; }
    .custom-color-accordion .accordion-button:nãot(.collapsed) {
        color: var(--bs-primary) !important;
        background-color: rgba(var(--bs-primary-rgb), 0.05) !important;
    }
    .custom-color-accordion .list-group-item { transition: all 0.15s ease; cursãor: pointer; }
    .custom-color-accordion .list-group-item:hover { background-color: rgba(0,0,0,0.03); transform: translateX(3px); }
    .custom-color-accordion .list-group-item.active { border-left: 3px sãolid var(--bs-primary) !important; }
    .relato-quick-grid .dropdown-menu {
        max-height: 280px;
        overflow-y: auto;
    }
    .os-data-section {
        border: 1px sãolid rgba(99, 91, 255, 0.2);
        border-radius: 12px;
        padding: 14px;
        background: rgba(255, 255, 255, 0.03);
        box-shadow: 0 2px 8px rgba(12, 22, 44, 0.04);
    }
    .os-data-section-title {
        font-size: 0.78rem;
        letter-spacing: 0.08rem;
        text-transform: uppercase;
        font-weight: 700;
        color: #5f6c86;
        margin-bottom: 0.75rem;
        display: flex;
        align-itemês: center;
    }
    .os-data-section + .os-data-section {
        margin-top: 0.2rem;
    }
    .os-data-section .row:last-child {
        margin-bottom: 0;
    }
    .os-form-page .relato-quick-grid .btn-group {
        flex: 0 0 auto;
    }
    .os-form-page #estadoFisicoList .list-group-item,
    .os-form-page #acessãoriosList .list-group-item {
        padding: 0.8rem;
    }
    @media (max-width: 1199.98px) {
        .os-form-page .os-data-section {
            padding: 12px;
        }
    }
    @media (max-width: 767.98px) {
        .os-form-page .os-data-section {
            border-radius: 10px;
            padding: 10px;
        }
        .os-form-page .os-data-section-title {
            font-size: 0.72rem;
            letter-spacing: 0.06rem;
        }
        .os-form-page .relato-quick-grid {
            overflow-x: auto;
            flex-wrap: nãowrap !important;
            padding-bottom: 4px;
        }
        .os-form-page .relato-quick-grid .btn-group {
            flex: 0 0 auto;
        }
    }
</style>

<script>
const BASE_URL = document.querySelector('meta[name="base-url"]').content;
const isEdit   = <?= $isEdit ? 'true' : 'false' ?>;
<?php if ($isEdit && !empty($defeitosSelected)): ?>
var defeitosSelecionados = <?= jsãon_encode(array_column($defeitosSelected, 'defeito_id')) ?>;
<?php else: ?>
var defeitosSelecionados = [];
<?php endif; ?>
const existingFotosCount = <?= (int)(count($fotos_entrada ?? [])) ?>;
const estadoFisicoEntriesServer = <?= jsãon_encode(array_map(static function ($entry) {
    $values = [];
    if (!empty($entry['valores'])) {
        $decoded = jsãon_decode((string) $entry['valores'], true);
        if (is_array($decoded)) {
            $values = $decoded;
        }
    }
    return [
        'id' => 'est_srv_' . ($entry['id'] ?? uniqid()),
        'text' => trim((string)($entry['descricao_danão'] ?? '')),
        'key' => $entry['tipo'] ?? 'outro',
        'values' => $values,
    ];
}, $estadoFisicoEntries ?? []), JSON_UNESCAPED_UNICODE) ?>;
let pendingEquipId = null;
let pendingDefeitos = null;
const DRAFT_KEY = 'osDraft_v1';
const DRAFT_TTL_MS = 1000 * 60 * 60 * 24 * 7;
let draftSaveTimer = null;

const statusMeta = <?= jsãon_encode($statusFlat, JSON_UNESCAPED_UNICODE) ?> || {};
const statusLabels = Object.keys(statusMeta).reduce((acc, key) => {
    acc[key] = statusMeta[key]?.nãome || key;
    return acc;
}, {});

const statusBadgeClassMap = Object.keys(statusMeta).reduce((acc, key) => {
    const raw = String(statusMeta[key]?.cor || 'secondary').toLowerCase();
    const nãormalized = ({
        indigo: 'primary',
        purple: 'primary',
        orange: 'warning',
        dark: 'dark',
        light: 'light text-dark',
        secondary: 'secondary',
        primary: 'primary',
        success: 'success',
        warning: 'warning',
        danger: 'danger',
        info: 'info'
    })[raw] || 'secondary';
    acc[key] = 'text-bg-' + nãormalized;
    return acc;
}, {});

const prioridadeLabels = {
    baixa: 'Baixa',
    nãormal: 'Nãormal',
    alta: 'Alta',
    urgente: 'Urgente'
};

// ??? Select2 ????????????????????????????????????????????????????????????????
if (typeof $.fn.select2 !== 'undefined') {
    $('#clienteOsSelect').select2({
        theme: 'bootstrap-5',
        placeholder: 'Buscar cliente...',
        allowClear: true,
        width: '100%'
    }).on('select2:open', function() {
        // Adiciona um listener para detectar quando o usu�rio pressiona Enter na busca vazia
        // ou quando n�o h� resultados. Mas vamos focar não bot�o fixo.
    });

    // Se quiser bot�o de Add dentro do dropdown Select2, � complexo.
    // O bot�o '+ Nãovo' j� resãolve bem.
}

// ??? Modal: Cadastrar Nãovo Cliente ??????????????????????????????????????????
const btnNãovoCliente = document.getElementById('btnNãovoCliente');
if (btnNãovoCliente) {
    btnNãovoCliente.addEventListener('click', function() {
        new bootstrap.Modal(document.getElementById('modalNãovoCliente')).show();
    });
}

document.getElementById('btnSalvarNãovoCliente')?.addEventListener('click', function() {
    const form = document.getElementById('formNãovoClienteAjax');
    const errors = document.getElementById('modalClienteErrors');
    errors.classList.add('d-nãone');

    const formData = new FormData(form);

    fetch(`${BASE_URL}clientes/salvar_ajax`, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.jsãon())
    .then(res => {
        if (!res.success) {
            errors.innerHTML = res.message || 'Erro ao cadastrar cliente.';
            errors.classList.remove('d-nãone');
            return;
        }

        // Adiciona ao Select2
        const sel = $('#clienteOsSelect');
        const opt = new Option(res.nãome, res.id, true, true);
        sel.append(opt).trigger('change');

        // Fecha modal
        bootstrap.Modal.getInstance(document.getElementById('modalNãovoCliente'))?.hide();
        form.reset();
        
        // Dispara o change para carregar equipamentos (que vir�o vazios, claro, mas reseta o combo)
        _onClienteChange(res.id);
    })
    .catch(() => {
        errors.innerHTML = 'Erro inesperado. Tente nãovamente.';
        errors.classList.remove('d-nãone');
    });
});

// ??? Sidebar layout toggling ???????????????????????????????????????????????
function showSidebar() {
    const sidebar = document.getElementById('sidebarEquipamento');
    const formCol = document.getElementById('formCol');
    if (sidebar) sidebar.style.display = '';
    if (formCol) formCol.className = 'col-12 col-xl-8 col-xxl-9 ds-split-main';
}
function hideSidebar() {
    const mainBox     = document.getElementById('fotoMainBox');
    const placeholder = document.getElementById('fotoPlaceholder');
    const minis       = document.getElementById('fotosMiniaturas');
    const infoBox     = document.getElementById('equipInfoBox');
    const infoContent = document.getElementById('equipInfoContent');
    const colorInfo   = document.getElementById('equipColorInfo');

    if (mainBox) mainBox.classList.add('d-nãone');
    if (placeholder) {
        placeholder.classList.remove('d-nãone');
        placeholder.classList.add('d-flex');
        placeholder.style.background = 'rgba(255,255,255,0.04)';
        placeholder.style.color = '';
    }
    if (minis) minis.innerHTML = '';
    if (infoBox) infoBox.style.display = 'nãone';
    if (infoContent) infoContent.innerHTML = '';
    if (colorInfo) colorInfo.classList.add('d-nãone');
    showSidebar();
}

function _getSelectedText(selectEl, fallback) {
    if (!selectEl || !selectEl.value) return fallback;
    const opt = selectEl.options[selectEl.selectedIndex];
    return opt ? opt.text : fallback;
}

function _formatDateTime(value) {
    if (!value) return '-';
    const dt = new Date(value);
    if (Number.isNaN(dt.getTime())) return value;
    return dt.toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' });
}

function _formatDate(value) {
    if (!value) return '-';
    const dt = new Date(value + 'T00:00:00');
    if (Number.isNaN(dt.getTime())) return value;
    return dt.toLocaleDateString('pt-BR');
}

function _setResumoBadge(id, text, cls) {
    const el = document.getElementById(id);
    if (!el) return;
    el.textContent = text;
    el.className = 'badge ' + cls;
}

function _setFieldStatus(id, ok) {
    const el = document.getElementById(id);
    if (!el) return;
    el.textContent = ok ? '??' : '?';
    el.className = ok ? 'text-success' : 'text-danger';
}

function updateResumo() {
    const clienteSel = document.getElementById('clienteOsSelect');
    const equipSel   = document.getElementById('equipamentoSelect');
    const tecnicoSel = document.querySelector('select[name="tecnico_id"]');
    const prioridadeSel = document.querySelector('select[name="prioridade"]');
    const statusSel  = document.querySelector('select[name="status"]');
    const entradaInp = document.querySelector('input[name="data_entrada"]');
    const previsaoInp = document.querySelector('input[name="data_previsao"]');
    const relatoInp  = document.getElementById('relatoClienteInput') || document.querySelector('textarea[name="relato_cliente"]');
    const acessãoriosInp = document.querySelector('textarea[name="acessãorios"]');
    const estadoFisicoInp = document.getElementById('estadoFisicoInput');
    const estadoFisicoSemAvarias = document.getElementById('estadoFisicoSemAvarias');

    const clienteText = _getSelectedText(clienteSel, 'N�o selecionado');
    const equipText   = _getSelectedText(equipSel, 'N�o selecionado');
    const tecnicoText = _getSelectedText(tecnicoSel, 'N�o atribu�do');
    const prioridadeVal = prioridadeSel?.value || 'nãormal';
    const statusVal = statusSel?.value || 'triagem';
    const relatoVal = relatoInp?.value?.trim() || '';
    const acessãoriosVal = acessãoriosInp?.value?.trim() || '';
    const estadoFisicoVal = estadoFisicoInp?.value?.trim() || '';

    document.getElementById('resumoCliente').textContent = clienteText;
    document.getElementById('resumoEquipamento').textContent = equipText;
    document.getElementById('resumoTecnico').textContent = tecnicoText;
    document.getElementById('resumoEntrada').textContent = _formatDateTime(entradaInp?.value);
    document.getElementById('resumoPrevisao').textContent = _formatDate(previsaoInp?.value);
    const semAcessãorios = acessãoriosVal.toLowerCase() === 'sem acess�rios';
    const semAvarias = Boolean(estadoFisicoSemAvarias?.checked) || estadoFisicoVal.toLowerCase() === 'sem avarias aparentes';
    document.getElementById('resumoRelato').textContent = relatoVal ? 'Preenchido' : 'Vazio';
    document.getElementById('resumoAcessãorios').textContent = semAcessãorios ? 'Sem acess�rios' : (acessãoriosVal ? 'Informado' : 'N�o informado');
    document.getElementById('resumoEstadoFisico').textContent = semAvarias ? 'Sem avarias' : (estadoFisicoVal ? 'Informado' : 'N�o informado');

    const prioridadeBadgeClass = {
        baixa: 'text-bg-secondary',
        nãormal: 'text-bg-primary',
        alta: 'text-bg-warning',
        urgente: 'text-bg-danger'
    }[prioridadeVal] || 'text-bg-secondary';
    _setResumoBadge('resumoPrioridade', prioridadeLabels[prioridadeVal] || 'Nãormal', prioridadeBadgeClass);

    const statusBadgeClass = statusBadgeClassMap[statusVal] || 'text-bg-secondary';
    _setResumoBadge('resumoStatus', statusLabels[statusVal] || statusVal || 'Triagem', statusBadgeClass);

    const defeitosCount = document.querySelectorAll('.chk-defeito-comum:checked').length;
    const resumoDefeitos = document.getElementById('resumoDefeitos');
    if (resumoDefeitos) resumoDefeitos.textContent = defeitosCount.toString();

    const totalFotos = (typeof getTotalFotosEntradaResumo === 'function')
        ? getTotalFotosEntradaResumo()
        : ((document.getElementById('fotosEntradaInput')?.files?.length || 0) + existingFotosCount);
    document.getElementById('resumoFotosEntrada').textContent = totalFotos.toString();

    _setFieldStatus('statusCliente', Boolean(clienteSel?.value));
    _setFieldStatus('statusEquipamento', Boolean(equipSel?.value));
    _setFieldStatus('statusTecnico', Boolean(tecnicoSel?.value));
    _setFieldStatus('statusPrioridade', Boolean(prioridadeSel?.value));
    _setFieldStatus('statusStatus', Boolean(statusSel?.value));
    _setFieldStatus('statusEntrada', Boolean(entradaInp?.value));
    _setFieldStatus('statusPrevisao', Boolean(previsaoInp?.value));
    _setFieldStatus('statusRelato', Boolean(relatoVal));
    _setFieldStatus('statusAcessãorios', semAcessãorios || Boolean(acessãoriosVal));
    _setFieldStatus('statusEstadoFisico', semAvarias || Boolean(estadoFisicoVal));
    _setFieldStatus('statusFotos', totalFotos > 0);
    if (document.getElementById('statusDefeitos')) {
        _setFieldStatus('statusDefeitos', defeitosCount > 0);
    }
}

const relatoClienteInput = document.getElementById('relatoClienteInput') || document.querySelector('textarea[name="relato_cliente"]');
const relatoQuickButtons = document.getElementById('relatoQuickButtons');

function relatoNãormalizarTexto(texto) {
    let valor = String(texto || '').trim();
    valor = valor.replace(/^Cliente relata:\s*/i, '');
    valor = valor.replace(/[.;:,\s]+$/g, '').trim();
    return valor;
}
function initRelatoRapidoModule() {
    if (!relatoClienteInput) return;
    relatoClienteInput.addEventListener('input', () => {
        updateResumo();
        scheduleDraftSave();
    });

    if (!isEdit && relatoQuickButtons) {
        relatoQuickButtons.addEventListener('click', event => {
            const btn = event.target.closest('.btn-relato-opcao');
            if (!btn) return;
            const texto = relatoNãormalizarTexto(btn.dataset.relatoOpcao || '');
            if (!texto) return;
            const linha = /[.!?]$/.test(texto) ? texto : `${texto}.`;
            const atual = relatoClienteInput.value.trim();
            relatoClienteInput.value = atual ? `${atual}\n${linha}` : linha;
            updateResumo();
            scheduleDraftSave();
        });
    }
}

initRelatoRapidoModule();

const COMMON_ACCESSORY_COLORS = [
    { hex: '#000000', name: 'Preto' },
    { hex: '#6F4E37', name: 'Marrom' },
    { hex: '#87CEFA', name: 'Azul claro' },
    { hex: '#90EE90', name: 'Verde claro' },
    { hex: '#FFC0CB', name: 'Rosa' },
    { hex: '#FF0000', name: 'Vermelho' },
    { hex: '#FFA500', name: 'Laranja' },
    { hex: '#FFFF00', name: 'Amarelo' },
    { hex: '#008000', name: 'Verde' },
    { hex: '#0000FF', name: 'Azul' },
    { hex: '#8A2BE2', name: 'Roxo/Violeta' },
    { hex: '#FFFFFF', name: 'Branco' }
];

const colorNameMap = COMMON_ACCESSORY_COLORS.reduce((acc, color) => {
    acc[color.hex.toLowerCase()] = color.name;
    return acc;
}, {});

function nãormalizeHexColor(value) {
    const raw = (value || '').trim();
    if (/^#[0-9a-fA-F]{6}$/.test(raw)) return raw.toUpperCase();
    if (/^[0-9a-fA-F]{6}$/.test(raw)) return `#${raw.toUpperCase()}`;
    return '';
}

function extractHexFromAccessãoryColor(value) {
    const raw = (value || '').trim();
    const directHex = nãormalizeHexColor(raw);
    if (directHex) return directHex;
    const match = raw.match(/#([0-9a-fA-F]{6})/);
    return match ? `#${match[1].toUpperCase()}` : '';
}

function getAccessãoryNamedColors() {
    const list = [];
    Object.entries(colorNameMap).forEach(([hex, name]) => {
        const nãormalizedHex = nãormalizeHexColor(hex);
        if (nãormalizedHex) list.push({ hex: nãormalizedHex, name });
    });

    if (typeof PROFESSIONAL_COLORS_OS !== 'undefined' && Array.isArray(PROFESSIONAL_COLORS_OS)) {
        PROFESSIONAL_COLORS_OS.forEach(group => {
            (group.colors || []).forEach(color => {
                const nãormalizedHex = nãormalizeHexColor(color.hex);
                if (nãormalizedHex) list.push({ hex: nãormalizedHex, name: color.name || nãormalizedHex });
            });
        });
    }

    const unique = new Map();
    list.forEach(item => {
        if (!unique.has(item.hex)) unique.set(item.hex, item);
    });
    return Array.from(unique.values());
}

function getAccessãoryHexByName(name) {
    const needle = (name || '').trim().toLowerCase();
    if (!needle) return '';
    const exact = getAccessãoryNamedColors().find(c => (c.name || '').trim().toLowerCase() === needle);
    return exact ? exact.hex : '';
}

function getClosestAccessãoryColorName(hex) {
    const nãormalizedHex = nãormalizeHexColor(hex);
    if (!nãormalizedHex) return '';
    const colors = getAccessãoryNamedColors();
    if (!colors.length) return nãormalizedHex;

    let best = colors[0];
    let minDistance = Number.POSITIVE_INFINITY;
    colors.forEach(color => {
        const distance = (typeof colorDistanceOS === 'function')
            ? colorDistanceOS(nãormalizedHex, color.hex)
            : (color.hex === nãormalizedHex ? 0 : Number.POSITIVE_INFINITY);
        if (distance < minDistance) {
            minDistance = distance;
            best = color;
        }
    });
    return best?.name || nãormalizedHex;
}

function formatAccessãoryColorValue(hex) {
    const nãormalizedHex = nãormalizeHexColor(hex);
    if (!nãormalizedHex) return '';
    const name = getClosestAccessãoryColorName(nãormalizedHex);
    return name || '';
}

function composeAccessãoryText(base, detail = '') {
    const cleanDetail = (detail || '').trim();
    return cleanDetail ? `${base} ${cleanDetail}` : base;
}

const acessãoriosConfig = {
    chip: {
        title: 'Chip',
        fields: [{ name: 'chip_digits', label: '�ltimos 6 d�gitos do chip', placeholder: '123456', max: 6 }],
        format: values => composeAccessãoryText('Chip', values.chip_digits ? ('final ' + values.chip_digits) : '')
    },
    capinha: {
        title: 'Capinha celular',
        fields: [{ name: 'cor', label: 'Cor da capinha', placeholder: 'Preta', type: 'color_text' }],
        format: values => composeAccessãoryText('Capinha celular', values.cor)
    },
    capa: {
        title: 'Capa',
        fields: [],
        format: () => 'Capa'
    },
    mochila: {
        title: 'Mochila',
        fields: [{ name: 'cor', label: 'Cor da mochila', placeholder: 'Preta', type: 'color_text' }],
        format: values => composeAccessãoryText('Mochila', values.cor)
    },
    bolsa: {
        title: 'Bolsa nãotebook',
        fields: [{ name: 'cor', label: 'Cor da bolsa', placeholder: 'Cinza', type: 'color_text' }],
        format: values => composeAccessãoryText('Bolsa nãotebook', values.cor)
    },
    cabo: {
        title: 'Cabo',
        fields: [{
            name: 'tipo',
            label: 'Tipo de cabo',
            type: 'select_with_other',
            otherName: 'tipo_outro',
            otherPlaceholder: 'Especifique o tipo de cabo',
            options: [
                { value: '', label: 'Selecionar tipo (opcional)' },
                { value: 'USB-C', label: 'USB-C' },
                { value: 'Micro USB', label: 'Micro USB' },
                { value: 'Lightning', label: 'Lightning' },
                { value: 'HDMI', label: 'HDMI' },
                { value: 'Cabo de for�a', label: 'Cabo de for�a' },
                { value: 'Outro', label: 'Outro' }
            ]
        }],
        format: values => composeAccessãoryText('Cabo', values.tipo)
    },
    carregador: {
        title: 'Carregador',
        fields: [{
            name: 'tipo_equip',
            label: 'Tipo de equipamento',
            type: 'select',
            options: [
                { value: '', label: 'Selecionar tipo (opcional)' },
                { value: 'Celular', label: 'Celular' },
                { value: 'Nãotebook', label: 'Nãotebook' },
                { value: 'Tablet', label: 'Tablet' },
                { value: 'Outro', label: 'Outro' }
            ]
        }],
        format: values => composeAccessãoryText('Carregador', values.tipo_equip)
    },
    outro: {
        title: 'Outro acess�rio',
        fields: [{ name: 'descricao', label: 'Descri��o', placeholder: 'Ex: cabo adaptador' }],
        format: values => `${values.descricao || 'Outro acess�rio'}`
    }
};

const acessãoriosInput = document.getElementById('acessãoriosInput');
const acessãoriosDataInput = document.getElementById('acessãoriosDataInput');
const acessãoriosList = document.getElementById('acessãoriosList');
const acessãoriosSemItensCheckbox = document.getElementById('acessãoriosSemItens');
const acessãoriosQuickForm = document.getElementById('acessãoriosQuickForm');
const acessãoriosQuickTitle = document.getElementById('acessãoriosQuickTitle');
const acessãoriosQuickFields = document.getElementById('acessãoriosQuickFields');
const acessãoriosQuickSave = document.getElementById('acessãoriosQuickSave');
const acessãoriosQuickCancel = document.getElementById('acessãoriosQuickCancel');
const acessãoriosQuickClose = document.getElementById('acessãoriosQuickClose');
const acessãoriosPhotoInput = document.getElementById('acessãoriosPhotoInput');
const acessãoriosFilesInputs = document.getElementById('acessãoriosFilesInputs');
const acessãoriosPhotos = {};
const acessãoriosFileInputs = {};
let acessãoriosEntries = [];
let acessãoriosEditing = null;
let acessãoriosCurrentKey = null;
let acessãoriosPhotoTarget = null;
let acessãorioCropQueue = [];
let acessãorioCropEntryId = null;
const ACCESSORIOS_SEM_ITENS_TEXT = 'Sem acess�rios';

const initialAcessãoriosText = acessãoriosInput?.value?.trim() || '';
if (acessãoriosSemItensCheckbox && initialAcessãoriosText.toLowerCase() === ACCESSORIOS_SEM_ITENS_TEXT.toLowerCase()) {
    acessãoriosSemItensCheckbox.checked = true;
}
if (initialAcessãoriosText && initialAcessãoriosText.toLowerCase() !== ACCESSORIOS_SEM_ITENS_TEXT.toLowerCase()) {
    initialAcessãoriosText.split(/\r?\n/).filter(Boolean).forEach(text => {
        acessãoriosEntries.push({ id: `acc_${Date.nãow()}_${Math.random().toString(36).slice(2)}`, text, key: 'outro' });
    });
}

function generateEntryId() {
    return `acc_${Date.nãow()}_${Math.random().toString(36).substring(2, 8)}`;
}

function isAcessãoriosSemItensChecked() {
    return Boolean(acessãoriosSemItensCheckbox?.checked);
}

function clearAllAcessãorios() {
    acessãoriosEntries.forEach(entry => removeAcessãorioFileInput(entry.id));
    acessãoriosEntries = [];
}

function refreshAcessãoriosSemItensUi() {
    const isSemItens = isAcessãoriosSemItensChecked();
    document.querySelectorAll('[data-acessãorio-key]').forEach(btn => {
        btn.disabled = isSemItens;
    });
    if (isSemItens) {
        closeAcessãoriosForm();
    }
}

function syncAcessãoriosInput() {
    if (!acessãoriosInput) return;
    if (isAcessãoriosSemItensChecked()) {
        acessãoriosInput.value = ACCESSORIOS_SEM_ITENS_TEXT;
        if (acessãoriosDataInput) {
            acessãoriosDataInput.value = JSON.stringify([]);
        }
        updateResumo();
        scheduleDraftSave();
        return;
    }

    acessãoriosInput.value = acessãoriosEntries.map(entry => entry.text).join('\n');
    if (acessãoriosDataInput) {
        acessãoriosDataInput.value = JSON.stringify(acessãoriosEntries.map(entry => ({
            id: entry.id,
            text: entry.text,
            key: entry.key || 'outro',
            values: entry.values || {}
        })));
    }
    updateResumo();
    scheduleDraftSave();
}

function ensureAcessãorioFileInput(entryId) {
    if (!acessãoriosFilesInputs) return null;
    let input = acessãoriosFileInputs[entryId];
    if (!input) {
        input = document.createElement('input');
        input.type = 'file';
        input.multiple = true;
        input.name = `fotos_acessãorios[${entryId}][]`;
        input.id = `acessãorio_files_${entryId}`;
        input.className = 'd-nãone';
        acessãoriosFilesInputs.appendChild(input);
        acessãoriosFileInputs[entryId] = input;
    }
    const dt = acessãoriosPhotos[entryId];
    if (dt) {
        input.files = dt.files;
    }
    return input;
}

function removeAcessãorioFileInput(entryId) {
    const input = acessãoriosFileInputs[entryId];
    if (input) {
        input.remove();
        delete acessãoriosFileInputs[entryId];
    }
    delete acessãoriosPhotos[entryId];
}

function renderAcessãoriosPhotos(entryId, container) {
    if (!container) return;
    container.innerHTML = '';
    const dt = acessãoriosPhotos[entryId];
    if (!dt) return;
    Array.from(dt.files).forEach((file, index) => {
        const reader = new FileReader();
        reader.onload = function(e) {
            const thumb = document.createElement('div');
            thumb.className = 'border rounded overflow-hidden position-relative';
            thumb.style.cssText = 'width:70px; height:70px;';

            const preview = document.createElement('div');
            preview.className = 'w-100 h-100 overflow-hidden position-relative image-preview';
            preview.style.cursãor = 'zoom-in';
            preview.setAttribute('data-bs-toggle', 'modal');
            preview.setAttribute('data-bs-target', '#imageModal');
            preview.setAttribute('data-img-src', e.target.result);
            preview.innerHTML = `
                <img src="${e.target.result}" class="w-100 h-100 object-fit-cover">
            `;

            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'btn btn-sm btn-outline-light position-absãolute top-0 end-0 m-1 btn-remove-foto-accessãorio';
            removeBtn.dataset.entry = entryId;
            removeBtn.dataset.index = index;
            removeBtn.innerHTML = '<i class="bi bi-x"></i>';

            thumb.appendChild(preview);
            thumb.appendChild(removeBtn);
            container.appendChild(thumb);
        };
        reader.readAsDataURL(file);
    });
}

function renderAcessãoriosList() {
    if (!acessãoriosList) return;
    acessãoriosList.innerHTML = '';
    if (isAcessãoriosSemItensChecked()) {
        const item = document.createElement('div');
        item.className = 'list-group-item text-muted';
        item.textContent = 'Marcado como sem acess�rios.';
        acessãoriosList.appendChild(item);
        return;
    }

    acessãoriosEntries.forEach((entry, index) => {
        const cleanText = (entry.text || '').replace(/\s*\(#[0-9a-fA-F]{6}\)/g, '');
        if (cleanText !== entry.text) {
            entry.text = cleanText;
        }
        const item = document.createElement('div');
        item.className = 'list-group-item';
        item.innerHTML = `
            <div class="d-flex justify-content-between align-itemês-center">
                <span class="fw-semibold">${cleanText}</span>
                <div class="d-flex gap-1">
                    <button type="button" class="btn btn-outline-info btn-sm btn-add-foto" data-entry="${entry.id}"><i class="bi bi-camera"></i> Adicionar foto</button>
                    <button type="button" class="btn btn-outline-primary btn-sm btn-add-foto-camera" data-entry="${entry.id}"><i class="bi bi-camera-video"></i> C�mera</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm btn-edit-acessãorio" data-index="${index}"><i class="bi bi-pencil"></i></button>
                    <button type="button" class="btn btn-outline-danger btn-sm btn-remove-acessãorio" data-index="${index}"><i class="bi bi-trash"></i></button>
                </div>
            </div>
            <div class="d-flex gap-2 flex-wrap mt-2" data-photos-container="${entry.id}"></div>
        `;
        acessãoriosList.appendChild(item);
        const photosContainer = item.querySelector(`[data-photos-container="${entry.id}"]`);
        ensureAcessãorioFileInput(entry.id);
        renderAcessãoriosPhotos(entry.id, photosContainer);
    });
    updateResumo();
}

function closeAcessãoriosForm() {
    acessãoriosQuickForm?.classList.add('d-nãone');
    acessãoriosQuickFields.innerHTML = '';
    acessãoriosEditing = null;
}

function openAcessãoriosForm(key, index = null) {
    const config = acessãoriosConfig[key];
    if (!config) return;
    acessãoriosCurrentKey = key;
    acessãoriosQuickTitle.textContent = config.title;
    acessãoriosQuickFields.innerHTML = '';
    config.fields.forEach(field => {
        const wrapper = document.createElement('div');
        wrapper.className = 'col-md-6';
        const label = document.createElement('label');
        label.className = 'form-label small';
        label.textContent = field.label;
        let input;
        if (field.type === 'select') {
            input = document.createElement('select');
            input.className = 'form-select form-select-sm';
            field.options.forEach(opt => {
                const option = document.createElement('option');
                option.value = opt.value;
                option.textContent = opt.label;
                input.appendChild(option);
            });
        } else if (field.type === 'select_with_other') {
            input = document.createElement('select');
            input.className = 'form-select form-select-sm';
            input.name = field.name;

            field.options.forEach(opt => {
                const option = document.createElement('option');
                option.value = opt.value;
                option.textContent = opt.label;
                input.appendChild(option);
            });

            const otherName = field.otherName || `${field.name}_outro`;
            const otherInput = document.createElement('input');
            otherInput.type = 'text';
            otherInput.className = 'form-control form-control-sm mt-2 d-nãone';
            otherInput.name = otherName;
            otherInput.placeholder = field.otherPlaceholder || 'Especifique';

            input.addEventListener('change', () => {
                const isOther = input.value === 'Outro';
                otherInput.classList.toggle('d-nãone', !isOther);
                if (!isOther) otherInput.value = '';
            });

            wrapper.appendChild(label);
            wrapper.appendChild(input);
            wrapper.appendChild(otherInput);
            acessãoriosQuickFields.appendChild(wrapper);
            return;
        } else if (field.type === 'color_text') {
            wrapper.className = 'col-12';
            const group = document.createElement('div');
            group.className = 'd-flex gap-2 align-itemês-center';

            const colorInput = document.createElement('input');
            colorInput.type = 'color';
            colorInput.className = 'form-control form-control-color';
            colorInput.value = '#000000';
            colorInput.title = 'Selecionar cor';
            colorInput.setAttribute('data-color-picker-for', field.name);

            input = document.createElement('input');
            input.type = 'text';
            input.className = 'form-control form-control-sm';
            input.placeholder = field.placeholder || '';
            input.name = field.name;

            colorInput.addEventListener('input', () => {
                const selectedHex = nãormalizeHexColor(colorInput.value);
                input.value = formatAccessãoryColorValue(selectedHex);
            });

            input.addEventListener('blur', () => {
                const rawValue = (input.value || '').trim();
                if (!rawValue) return;
                const hexFromText = extractHexFromAccessãoryColor(rawValue) || getAccessãoryHexByName(rawValue);
                if (hexFromText) {
                    colorInput.value = hexFromText;
                    input.value = formatAccessãoryColorValue(hexFromText);
                }
            });

            wrapper.appendChild(label);
            group.appendChild(colorInput);
            group.appendChild(input);
            wrapper.appendChild(group);

            const applyQuickColor = (color) => {
                colorInput.value = color.hex;
                input.value = color.name;
            };

            const quickColorsDesktop = document.createElement('div');
            quickColorsDesktop.className = 'd-nãone d-md-flex flex-nãowrap gap-1 mt-2 w-100';
            COMMON_ACCESSORY_COLORS.forEach(color => {
                const quickBtn = document.createElement('button');
                quickBtn.type = 'button';
                quickBtn.className = 'btn btn-sm btn-outline-secondary d-inline-flex align-itemês-center gap-1 text-nãowrap px-2 py-1';
                quickBtn.style.fontSize = '0.82rem';
                quickBtn.innerHTML = `
                    <span class="rounded-circle border" style="width:12px;height:12px;background:${color.hex};"></span>
                    <span>${color.name}</span>
                `;
                quickBtn.addEventListener('click', () => applyQuickColor(color));
                quickColorsDesktop.appendChild(quickBtn);
            });
            wrapper.appendChild(quickColorsDesktop);

            const quickColorsMobile = document.createElement('div');
            quickColorsMobile.className = 'dropdown d-md-nãone mt-2';
            const dropdownId = `acessãorioColorQuick_${field.name}_${Date.nãow()}_${Math.random().toString(36).slice(2, 6)}`;
            quickColorsMobile.innerHTML = `
                <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle w-100 text-start" id="${dropdownId}" data-bs-toggle="dropdown" aria-expanded="false">
                    Cores r�pidas
                </button>
                <ul class="dropdown-menu w-100" aria-labelledby="${dropdownId}"></ul>
            `;
            const mobileMenu = quickColorsMobile.querySelector('.dropdown-menu');
            COMMON_ACCESSORY_COLORS.forEach(color => {
                const li = document.createElement('li');
                const mobileBtn = document.createElement('button');
                mobileBtn.type = 'button';
                mobileBtn.className = 'dropdown-item d-flex align-itemês-center gap-2';
                mobileBtn.innerHTML = `
                    <span class="rounded-circle border" style="width:12px;height:12px;background:${color.hex};"></span>
                    <span>${color.name}</span>
                `;
                mobileBtn.addEventListener('click', () => applyQuickColor(color));
                li.appendChild(mobileBtn);
                mobileMenu.appendChild(li);
            });
            wrapper.appendChild(quickColorsMobile);

            acessãoriosQuickFields.appendChild(wrapper);
            return;
        } else {
            input = document.createElement('input');
            input.type = 'text';
            input.className = 'form-control form-control-sm';
            input.placeholder = field.placeholder || '';
            if (field.max) input.maxLength = field.max;
        }
        input.name = field.name;
        wrapper.appendChild(label);
        wrapper.appendChild(input);
        acessãoriosQuickFields.appendChild(wrapper);
    });
    if (index !== null) {
        acessãoriosEditing = index;
        const values = acessãoriosEntries[index].values || {};
        config.fields.forEach(field => {
            const el = acessãoriosQuickFields.querySelector(`[name="${field.name}"]`);
            if (el) el.value = values[field.name] || '';
            if (field.type === 'select_with_other') {
                const otherName = field.otherName || `${field.name}_outro`;
                const otherEl = acessãoriosQuickFields.querySelector(`[name="${otherName}"]`);
                const savedValue = (values[field.name] || '').trim();
                const knãownOption = (field.options || []).sãome(opt => opt.value === savedValue && opt.value !== 'Outro');

                if (el) {
                    if (!savedValue || knãownOption) {
                        el.value = savedValue;
                    } else {
                        el.value = 'Outro';
                    }
                }

                if (otherEl) {
                    const showOther = el && el.value === 'Outro';
                    otherEl.classList.toggle('d-nãone', !showOther);
                    if (showOther) {
                        otherEl.value = values[otherName] || (!knãownOption ? savedValue : '');
                    }
                }
            }
            if (field.type === 'color_text') {
                const picker = acessãoriosQuickFields.querySelector(`[data-color-picker-for="${field.name}"]`);
                const rawColor = values[field.name] || '';
                const hex = extractHexFromAccessãoryColor(rawColor) || getAccessãoryHexByName(rawColor);
                if (picker && hex) picker.value = hex;
                if (el && hex && !extractHexFromAccessãoryColor(rawColor)) {
                    el.value = formatAccessãoryColorValue(hex);
                }
            }
        });
    }
    acessãoriosQuickForm?.classList.remove('d-nãone');
}

function handleAcessãoriosButtonClick(event) {
    if (isAcessãoriosSemItensChecked()) return;
    const key = event.currentTarget.dataset.acessãorioKey;
    if (!key) return;
    openAcessãoriosForm(key);
}

function collectFormValues() {
    const values = {};
    acessãoriosQuickFields.querySelectorAll('input, select').forEach(input => {
        if (!input.name) return;
        values[input.name] = input.value.trim();
    });
    return values;
}

function handleAcessãoriosSave() {
    if (isAcessãoriosSemItensChecked()) return;
    const key = acessãoriosCurrentKey;
    const config = acessãoriosConfig[key];
    if (!config) return;
    const values = collectFormValues();
    (config.fields || []).forEach(field => {
        if (field.type === 'select_with_other') {
            const otherName = field.otherName || `${field.name}_outro`;
            const selected = (values[field.name] || '').trim();
            if (selected === 'Outro') {
                values[field.name] = (values[otherName] || '').trim();
            } else {
                values[field.name] = selected;
                values[otherName] = '';
            }
        }
        if (field.type !== 'color_text') return;
        const rawColor = values[field.name] || '';
        const hex = extractHexFromAccessãoryColor(rawColor) || getAccessãoryHexByName(rawColor);
        if (hex) values[field.name] = formatAccessãoryColorValue(hex);
    });
    const text = config.format(values);
    if (acessãoriosEditing !== null) {
        acessãoriosEntries[acessãoriosEditing] = { ...acessãoriosEntries[acessãoriosEditing], text, values, key };
    } else {
        acessãoriosEntries.push({ id: generateEntryId(), text, values, key });
    }
    renderAcessãoriosList();
    syncAcessãoriosInput();
    closeAcessãoriosForm();
}

function handleAcessãoriosCancel() {
    closeAcessãoriosForm();
}

function handleRemoveAcessãorio(event) {
    const index = parseInt(event.currentTarget.dataset.index, 10);
    if (Number.isNaN(index)) return;
    const entry = acessãoriosEntries[index];
    removeAcessãorioFileInput(entry.id);
    acessãoriosEntries.splice(index, 1);
    renderAcessãoriosList();
    syncAcessãoriosInput();
}

function handleEditAcessãorio(event) {
    const index = parseInt(event.currentTarget.dataset.index, 10);
    const entry = acessãoriosEntries[index];
    if (!entry) return;
    const key = entry.key || 'outro';
    openAcessãoriosForm(key, index);
}

function openAcessãorioPhotoInput(entryId) {
    closeImageModalIfOpen();
    acessãoriosPhotoTarget = entryId;
    acessãoriosPhotoInput.dataset.entryId = entryId;
    acessãoriosPhotoInput?.click();
}

function openAcessãorioCameraCapture(entryId) {
    if (!entryId) return;
    acessãorioCropEntryId = entryId;
    acessãorioCropQueue = [];
    openCameraCapture({ type: 'acessãorio', entryId });
}

function readFileAsDataUrl(file) {
    return new Promise((resãolve, reject) => {
        const reader = new FileReader();
        reader.onload = e => resãolve(e.target.result);
        reader.onerror = reject;
        reader.readAsDataURL(file);
    });
}

async function processNextAcessãorioCrop() {
    if (!acessãorioCropEntryId) return;
    if (!acessãorioCropQueue.length) {
        hideModalSafe(modalCrop, '#modalCropEquip');
        return;
    }
    const nextFile = acessãorioCropQueue.shift();
    try {
        const sãource = await readFileAsDataUrl(nextFile);
        openCropper(sãource, { type: 'acessãorio' });
    } catch (e) {
        processNextAcessãorioCrop();
    }
}

function handlePhotoInputChange() {
    const entryId = this.dataset.entryId;
    if (!entryId) return;
    const files = Array.from(this.files || []).filter(file => (file.type || '').startsWith('image/'));
    if (!files.length) {
        this.value = '';
        return;
    }
    acessãorioCropEntryId = entryId;
    acessãorioCropQueue = files.slice();
    processNextAcessãorioCrop();
    this.value = '';
}

function handleRemovePhoto(event) {
    const entryId = event.currentTarget.dataset.entry;
    const index = parseInt(event.currentTarget.dataset.index, 10);
    const dt = acessãoriosPhotos[entryId];
    if (!dt) return;
    const newDt = new DataTransfer();
    Array.from(dt.files).forEach((file, idx) => {
        if (idx !== index) newDt.itemês.add(file);
    });
    if (newDt.files.length === 0) {
        delete acessãoriosPhotos[entryId];
        removeAcessãorioFileInput(entryId);
    } else {
        acessãoriosPhotos[entryId] = newDt;
        ensureAcessãorioFileInput(entryId);
    }
    renderAcessãoriosList();
}

document.querySelectorAll('[data-acessãorio-key]').forEach(btn => {
    btn.addEventListener('click', handleAcessãoriosButtonClick);
});
acessãoriosSemItensCheckbox?.addEventListener('change', () => {
    const enableSemItens = Boolean(acessãoriosSemItensCheckbox.checked);
    if (!enableSemItens) {
        refreshAcessãoriosSemItensUi();
        renderAcessãoriosList();
        syncAcessãoriosInput();
        return;
    }

    if (!acessãoriosEntries.length) {
        refreshAcessãoriosSemItensUi();
        renderAcessãoriosList();
        syncAcessãoriosInput();
        return;
    }

    const applySemItens = () => {
        clearAllAcessãorios();
        refreshAcessãoriosSemItensUi();
        renderAcessãoriosList();
        syncAcessãoriosInput();
    };

    if (window.Swal && typeof window.Swal.fire === 'function') {
        Swal.fire({
            icon: 'warning',
            title: 'Marcar como sem acess�rios?',
            text: 'Os acess�rios j� adicionados ser�o removidos.',
            showCancelButton: true,
            confirmButtonText: 'Sim, marcar',
            cancelButtonText: 'Cancelar',
            reverseButtons: true,
            customClass: { popup: 'glass-card' }
        }).then((result) => {
            if (result.isConfirmed) {
                applySemItens();
                return;
            }
            acessãoriosSemItensCheckbox.checked = false;
            refreshAcessãoriosSemItensUi();
        });
        return;
    }

    const confirmed = confirm('Marcar como sem acess�rios vai remover os acess�rios j� adicionados. Deseja continuar?');
    if (confirmed) {
        applySemItens();
        return;
    }
    acessãoriosSemItensCheckbox.checked = false;
    refreshAcessãoriosSemItensUi();
});
acessãoriosQuickSave?.addEventListener('click', handleAcessãoriosSave);
acessãoriosQuickCancel?.addEventListener('click', handleAcessãoriosCancel);
acessãoriosQuickClose?.addEventListener('click', handleAcessãoriosCancel);
document.addEventListener('click', event => {
    const removeBtn = event.target.closest('.btn-remove-acessãorio');
    if (removeBtn) handleRemoveAcessãorio({ currentTarget: removeBtn });
    const editBtn = event.target.closest('.btn-edit-acessãorio');
    if (editBtn) handleEditAcessãorio({ currentTarget: editBtn });
    const addPhotoBtn = event.target.closest('.btn-add-foto');
    if (addPhotoBtn) openAcessãorioPhotoInput(addPhotoBtn.dataset.entry);
    const addPhotoCameraBtn = event.target.closest('.btn-add-foto-camera');
    if (addPhotoCameraBtn) openAcessãorioCameraCapture(addPhotoCameraBtn.dataset.entry);
    const removePhotoBtn = event.target.closest('.btn-remove-foto-accessãorio');
    if (removePhotoBtn) handleRemovePhoto({ currentTarget: removePhotoBtn });
    const removeEstadoBtn = event.target.closest('.btn-remove-estado');
    if (removeEstadoBtn) handleRemoveEstadoFisico({ currentTarget: removeEstadoBtn });
    const editEstadoBtn = event.target.closest('.btn-edit-estado');
    if (editEstadoBtn) handleEditEstadoFisico({ currentTarget: editEstadoBtn });
    const addEstadoPhotoBtn = event.target.closest('.btn-add-foto-estado');
    if (addEstadoPhotoBtn) openEstadoFisicoPhotoInput(addEstadoPhotoBtn.dataset.entry);
    const addEstadoPhotoCameraBtn = event.target.closest('.btn-add-foto-camera-estado');
    if (addEstadoPhotoCameraBtn) openEstadoFisicoCameraCapture(addEstadoPhotoCameraBtn.dataset.entry);
    const removeEstadoPhotoBtn = event.target.closest('.btn-remove-foto-estado');
    if (removeEstadoPhotoBtn) handleRemoveEstadoFisicoPhoto({ currentTarget: removeEstadoPhotoBtn });
});
acessãoriosPhotoInput?.addEventListener('change', handlePhotoInputChange);

const estadoFisicoConfig = {
    tela_trincada: {
        title: 'Tela trincada',
        fields: [{ name: 'detalhe', label: 'Detalhe (opcional)', placeholder: 'Ex: canto superior direito' }],
        format: values => composeAccessãoryText('Tela trincada', values.detalhe)
    },
    arranhoes: {
        title: 'Arranhoes',
        fields: [{ name: 'detalhe', label: 'Detalhe (opcional)', placeholder: 'Ex: tampa e lateral' }],
        format: values => composeAccessãoryText('Arranhoes', values.detalhe)
    },
    carcaca_quebrada: {
        title: 'Carcaca quebrada',
        fields: [{ name: 'detalhe', label: 'Detalhe (opcional)', placeholder: 'Ex: quina inferior' }],
        format: values => composeAccessãoryText('Carcaca quebrada', values.detalhe)
    },
    vidro_traseiro_quebrado: {
        title: 'Vidro traseiro quebrado',
        fields: [{ name: 'detalhe', label: 'Detalhe (opcional)', placeholder: 'Ex: fissura central' }],
        format: values => composeAccessãoryText('Vidro traseiro quebrado', values.detalhe)
    },
    amassado: {
        title: 'Amassado',
        fields: [{ name: 'detalhe', label: 'Detalhe (opcional)', placeholder: 'Ex: lateral esquerda' }],
        format: values => composeAccessãoryText('Amassado', values.detalhe)
    },
    botao_quebrado: {
        title: 'Botao quebrado',
        fields: [{ name: 'detalhe', label: 'Qual botao?', placeholder: 'Ex: power' }],
        format: values => composeAccessãoryText('Botao quebrado', values.detalhe)
    },
    outro: {
        title: 'Outro danão',
        fields: [{ name: 'descricao', label: 'Descricao', placeholder: 'Ex: camera traseira quebrada' }],
        format: values => values.descricao || 'Outro danão'
    }
};

const estadoFisicoInput = document.getElementById('estadoFisicoInput');
const estadoFisicoDataInput = document.getElementById('estadoFisicoDataInput');
const estadoFisicoList = document.getElementById('estadoFisicoList');
const estadoFisicoSemAvariasCheckbox = document.getElementById('estadoFisicoSemAvarias');
const estadoFisicoQuickForm = document.getElementById('estadoFisicoQuickForm');
const estadoFisicoQuickTitle = document.getElementById('estadoFisicoQuickTitle');
const estadoFisicoQuickFields = document.getElementById('estadoFisicoQuickFields');
const estadoFisicoQuickSave = document.getElementById('estadoFisicoQuickSave');
const estadoFisicoQuickCancel = document.getElementById('estadoFisicoQuickCancel');
const estadoFisicoQuickClose = document.getElementById('estadoFisicoQuickClose');
const estadoFisicoPhotoInput = document.getElementById('estadoFisicoPhotoInput');
const estadoFisicoFilesInputs = document.getElementById('estadoFisicoFilesInputs');
const estadoFisicoPhotos = {};
const estadoFisicoFileInputs = {};
let estadoFisicoEntries = [];
let estadoFisicoEditing = null;
let estadoFisicoCurrentKey = null;
let estadoFisicoCropQueue = [];
let estadoFisicoCropEntryId = null;
const ESTADO_FISICO_SEM_AVARIAS_TEXT = 'Sem avarias aparentes';

const initialEstadoFisicoText = estadoFisicoInput?.value?.trim() || '';
if (Array.isArray(estadoFisicoEntriesServer) && estadoFisicoEntriesServer.length) {
    estadoFisicoEntries = estadoFisicoEntriesServer
        .filter(entry => String(entry?.text || '').trim() !== '')
        .map(entry => ({
            id: entry.id || generateEstadoFisicoEntryId(),
            text: String(entry.text || '').trim(),
            key: entry.key || 'outro',
            values: entry.values || {}
        }));
}
if (estadoFisicoSemAvariasCheckbox && initialEstadoFisicoText.toLowerCase() === ESTADO_FISICO_SEM_AVARIAS_TEXT.toLowerCase()) {
    estadoFisicoSemAvariasCheckbox.checked = true;
}
if (!estadoFisicoEntries.length && initialEstadoFisicoText && initialEstadoFisicoText.toLowerCase() !== ESTADO_FISICO_SEM_AVARIAS_TEXT.toLowerCase()) {
    initialEstadoFisicoText.split(/\r?\n/).filter(Boolean).forEach(text => {
        estadoFisicoEntries.push({ id: `est_${Date.nãow()}_${Math.random().toString(36).slice(2)}`, text, key: 'outro' });
    });
}

function generateEstadoFisicoEntryId() {
    return `est_${Date.nãow()}_${Math.random().toString(36).substring(2, 8)}`;
}

function isEstadoFisicoSemAvariasChecked() {
    return Boolean(estadoFisicoSemAvariasCheckbox?.checked);
}

function clearAllEstadoFisico() {
    estadoFisicoEntries.forEach(entry => removeEstadoFisicoFileInput(entry.id));
    estadoFisicoEntries = [];
}

function refreshEstadoFisicoSemAvariasUi() {
    const isSemAvarias = isEstadoFisicoSemAvariasChecked();
    document.querySelectorAll('[data-estado-key]').forEach(btn => {
        btn.disabled = isSemAvarias;
    });
    if (isSemAvarias) {
        closeEstadoFisicoForm();
    }
}

function syncEstadoFisicoInput() {
    if (!estadoFisicoInput) return;
    if (isEstadoFisicoSemAvariasChecked()) {
        estadoFisicoInput.value = ESTADO_FISICO_SEM_AVARIAS_TEXT;
        if (estadoFisicoDataInput) {
            estadoFisicoDataInput.value = JSON.stringify([{
                id: 'sem_avarias',
                text: ESTADO_FISICO_SEM_AVARIAS_TEXT,
                key: 'sem_avarias',
                values: {}
            }]);
        }
        updateResumo();
        scheduleDraftSave();
        return;
    }

    estadoFisicoInput.value = estadoFisicoEntries.map(entry => entry.text).join('\n');
    if (estadoFisicoDataInput) {
        estadoFisicoDataInput.value = JSON.stringify(estadoFisicoEntries.map(entry => ({
            id: entry.id,
            text: entry.text,
            key: entry.key || 'outro',
            values: entry.values || {}
        })));
    }
    updateResumo();
    scheduleDraftSave();
}

function ensureEstadoFisicoFileInput(entryId) {
    if (!estadoFisicoFilesInputs) return null;
    let input = estadoFisicoFileInputs[entryId];
    if (!input) {
        input = document.createElement('input');
        input.type = 'file';
        input.multiple = true;
        input.name = `fotos_estado_fisico[${entryId}][]`;
        input.id = `estado_fisico_files_${entryId}`;
        input.className = 'd-nãone';
        estadoFisicoFilesInputs.appendChild(input);
        estadoFisicoFileInputs[entryId] = input;
    }
    const dt = estadoFisicoPhotos[entryId];
    if (dt) {
        input.files = dt.files;
    }
    return input;
}

function removeEstadoFisicoFileInput(entryId) {
    const input = estadoFisicoFileInputs[entryId];
    if (input) {
        input.remove();
        delete estadoFisicoFileInputs[entryId];
    }
    delete estadoFisicoPhotos[entryId];
}

function renderEstadoFisicoPhotos(entryId, container) {
    if (!container) return;
    container.innerHTML = '';
    const dt = estadoFisicoPhotos[entryId];
    if (!dt) return;

    Array.from(dt.files).forEach((file, index) => {
        const reader = new FileReader();
        reader.onload = function(e) {
            const thumb = document.createElement('div');
            thumb.className = 'border rounded overflow-hidden position-relative';
            thumb.style.cssText = 'width:70px; height:70px;';

            const preview = document.createElement('div');
            preview.className = 'w-100 h-100 overflow-hidden position-relative image-preview';
            preview.style.cursãor = 'zoom-in';
            preview.setAttribute('data-bs-toggle', 'modal');
            preview.setAttribute('data-bs-target', '#imageModal');
            preview.setAttribute('data-img-src', e.target.result);
            preview.innerHTML = `<img src="${e.target.result}" class="w-100 h-100 object-fit-cover">`;

            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'btn btn-sm btn-outline-light position-absãolute top-0 end-0 m-1 btn-remove-foto-estado';
            removeBtn.dataset.entry = entryId;
            removeBtn.dataset.index = index;
            removeBtn.innerHTML = '<i class="bi bi-x"></i>';

            thumb.appendChild(preview);
            thumb.appendChild(removeBtn);
            container.appendChild(thumb);
        };
        reader.readAsDataURL(file);
    });
}

function renderEstadoFisicoList() {
    if (!estadoFisicoList) return;
    estadoFisicoList.innerHTML = '';

    if (isEstadoFisicoSemAvariasChecked()) {
        const item = document.createElement('div');
        item.className = 'list-group-item text-muted';
        item.textContent = 'Marcado como sem avarias aparentes.';
        estadoFisicoList.appendChild(item);
        updateResumo();
        return;
    }

    estadoFisicoEntries.forEach((entry, index) => {
        const item = document.createElement('div');
        item.className = 'list-group-item';
        item.innerHTML = `
            <div class="d-flex justify-content-between align-itemês-center">
                <span class="fw-semibold">${entry.text}</span>
                <div class="d-flex gap-1">
                    <button type="button" class="btn btn-outline-info btn-sm btn-add-foto-estado" data-entry="${entry.id}"><i class="bi bi-camera"></i> Adicionar foto</button>
                    <button type="button" class="btn btn-outline-primary btn-sm btn-add-foto-camera-estado" data-entry="${entry.id}"><i class="bi bi-camera-video"></i> C�mera</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm btn-edit-estado" data-index="${index}"><i class="bi bi-pencil"></i></button>
                    <button type="button" class="btn btn-outline-danger btn-sm btn-remove-estado" data-index="${index}"><i class="bi bi-trash"></i></button>
                </div>
            </div>
            <div class="d-flex gap-2 flex-wrap mt-2" data-estado-photos-container="${entry.id}"></div>
        `;
        estadoFisicoList.appendChild(item);
        const photosContainer = item.querySelector(`[data-estado-photos-container="${entry.id}"]`);
        ensureEstadoFisicoFileInput(entry.id);
        renderEstadoFisicoPhotos(entry.id, photosContainer);
    });
    updateResumo();
}

function closeEstadoFisicoForm() {
    estadoFisicoQuickForm?.classList.add('d-nãone');
    estadoFisicoQuickFields.innerHTML = '';
    estadoFisicoEditing = null;
}

function openEstadoFisicoForm(key, index = null) {
    const config = estadoFisicoConfig[key];
    if (!config) return;
    estadoFisicoCurrentKey = key;
    estadoFisicoQuickTitle.textContent = config.title;
    estadoFisicoQuickFields.innerHTML = '';

    config.fields.forEach(field => {
        const wrapper = document.createElement('div');
        wrapper.className = 'col-md-8';
        const label = document.createElement('label');
        label.className = 'form-label small';
        label.textContent = field.label;
        const input = document.createElement('input');
        input.type = 'text';
        input.className = 'form-control form-control-sm';
        input.placeholder = field.placeholder || '';
        input.name = field.name;
        wrapper.appendChild(label);
        wrapper.appendChild(input);
        estadoFisicoQuickFields.appendChild(wrapper);
    });

    if (index !== null) {
        estadoFisicoEditing = index;
        const values = estadoFisicoEntries[index].values || {};
        config.fields.forEach(field => {
            const el = estadoFisicoQuickFields.querySelector(`[name="${field.name}"]`);
            if (el) el.value = values[field.name] || '';
        });
    }

    estadoFisicoQuickForm?.classList.remove('d-nãone');
}

function collectEstadoFisicoFormValues() {
    const values = {};
    estadoFisicoQuickFields.querySelectorAll('input, select').forEach(input => {
        if (!input.name) return;
        values[input.name] = input.value.trim();
    });
    return values;
}

function handleEstadoFisicoSave() {
    if (isEstadoFisicoSemAvariasChecked()) return;
    const key = estadoFisicoCurrentKey;
    const config = estadoFisicoConfig[key];
    if (!config) return;
    const values = collectEstadoFisicoFormValues();
    const text = config.format(values).trim();
    if (!text) return;

    if (estadoFisicoEditing !== null) {
        estadoFisicoEntries[estadoFisicoEditing] = { ...estadoFisicoEntries[estadoFisicoEditing], text, values, key };
    } else {
        estadoFisicoEntries.push({ id: generateEstadoFisicoEntryId(), text, values, key });
    }
    renderEstadoFisicoList();
    syncEstadoFisicoInput();
    closeEstadoFisicoForm();
}

function handleRemoveEstadoFisico(event) {
    const index = parseInt(event.currentTarget.dataset.index, 10);
    if (Number.isNaN(index)) return;
    const entry = estadoFisicoEntries[index];
    if (!entry) return;
    removeEstadoFisicoFileInput(entry.id);
    estadoFisicoEntries.splice(index, 1);
    renderEstadoFisicoList();
    syncEstadoFisicoInput();
}

function handleEditEstadoFisico(event) {
    const index = parseInt(event.currentTarget.dataset.index, 10);
    const entry = estadoFisicoEntries[index];
    if (!entry) return;
    const key = entry.key || 'outro';
    openEstadoFisicoForm(key, index);
}

function openEstadoFisicoPhotoInput(entryId) {
    closeImageModalIfOpen();
    estadoFisicoPhotoInput.dataset.entryId = entryId;
    estadoFisicoPhotoInput?.click();
}

function openEstadoFisicoCameraCapture(entryId) {
    if (!entryId) return;
    estadoFisicoCropEntryId = entryId;
    estadoFisicoCropQueue = [];
    openCameraCapture({ type: 'estado_fisico', entryId });
}

async function processNextEstadoFisicoCrop() {
    if (!estadoFisicoCropEntryId) return;
    if (!estadoFisicoCropQueue.length) {
        hideModalSafe(modalCrop, '#modalCropEquip');
        return;
    }

    const nextFile = estadoFisicoCropQueue.shift();
    try {
        const sãource = await readFileAsDataUrl(nextFile);
        openCropper(sãource, { type: 'estado_fisico' });
    } catch (e) {
        processNextEstadoFisicoCrop();
    }
}

function handleEstadoFisicoPhotoInputChange() {
    const entryId = this.dataset.entryId;
    if (!entryId) return;
    const files = Array.from(this.files || []).filter(file => (file.type || '').startsWith('image/'));
    if (!files.length) {
        this.value = '';
        return;
    }
    estadoFisicoCropEntryId = entryId;
    estadoFisicoCropQueue = files.slice();
    processNextEstadoFisicoCrop();
    this.value = '';
}

function handleRemoveEstadoFisicoPhoto(event) {
    const entryId = event.currentTarget.dataset.entry;
    const index = parseInt(event.currentTarget.dataset.index, 10);
    const dt = estadoFisicoPhotos[entryId];
    if (!dt) return;

    const newDt = new DataTransfer();
    Array.from(dt.files).forEach((file, idx) => {
        if (idx !== index) newDt.itemês.add(file);
    });

    if (!newDt.files.length) {
        delete estadoFisicoPhotos[entryId];
        removeEstadoFisicoFileInput(entryId);
    } else {
        estadoFisicoPhotos[entryId] = newDt;
        ensureEstadoFisicoFileInput(entryId);
    }
    renderEstadoFisicoList();
    syncEstadoFisicoInput();
}

document.querySelectorAll('[data-estado-key]').forEach(btn => {
    btn.addEventListener('click', event => {
        if (isEstadoFisicoSemAvariasChecked()) return;
        const key = event.currentTarget.dataset.estadoKey;
        if (!key) return;
        openEstadoFisicoForm(key);
    });
});

estadoFisicoSemAvariasCheckbox?.addEventListener('change', () => {
    const enableSemAvarias = Boolean(estadoFisicoSemAvariasCheckbox.checked);
    if (!enableSemAvarias) {
        refreshEstadoFisicoSemAvariasUi();
        renderEstadoFisicoList();
        syncEstadoFisicoInput();
        return;
    }

    if (!estadoFisicoEntries.length) {
        refreshEstadoFisicoSemAvariasUi();
        renderEstadoFisicoList();
        syncEstadoFisicoInput();
        return;
    }

    const applySemAvarias = () => {
        clearAllEstadoFisico();
        refreshEstadoFisicoSemAvariasUi();
        renderEstadoFisicoList();
        syncEstadoFisicoInput();
    };

    if (window.Swal && typeof window.Swal.fire === 'function') {
        Swal.fire({
            icon: 'warning',
            title: 'Marcar como sem avarias?',
            text: 'Os registros de estado fisico ja adicionados serao removidos.',
            showCancelButton: true,
            confirmButtonText: 'Sim, marcar',
            cancelButtonText: 'Cancelar',
            reverseButtons: true,
            customClass: { popup: 'glass-card' }
        }).then((result) => {
            if (result.isConfirmed) {
                applySemAvarias();
                return;
            }
            estadoFisicoSemAvariasCheckbox.checked = false;
            refreshEstadoFisicoSemAvariasUi();
        });
        return;
    }

    const confirmed = confirm('Marcar como sem avarias remove os registros adicionados. Deseja continuar?');
    if (confirmed) {
        applySemAvarias();
        return;
    }
    estadoFisicoSemAvariasCheckbox.checked = false;
    refreshEstadoFisicoSemAvariasUi();
});

estadoFisicoQuickSave?.addEventListener('click', handleEstadoFisicoSave);
estadoFisicoQuickCancel?.addEventListener('click', closeEstadoFisicoForm);
estadoFisicoQuickClose?.addEventListener('click', closeEstadoFisicoForm);
estadoFisicoPhotoInput?.addEventListener('change', handleEstadoFisicoPhotoInputChange);

refreshEstadoFisicoSemAvariasUi();
renderEstadoFisicoList();
syncEstadoFisicoInput();
refreshAcessãoriosSemItensUi();
renderAcessãoriosList();
syncAcessãoriosInput();

function getTotalAcessãoriosFotos() {
    return Object.keys(acessãoriosPhotos).reduce((sum, id) => sum + (acessãoriosPhotos[id]?.files?.length || 0), 0);
}

function getTotalEstadoFisicoFotos() {
    return Object.keys(estadoFisicoPhotos).reduce((sum, id) => sum + (estadoFisicoPhotos[id]?.files?.length || 0), 0);
}

function getTotalFotosEntradaResumo() {
    const fotosEntradaNãovas = document.getElementById('fotosEntradaInput')?.files?.length || 0;
    const fotosEntradaExistentes = existingFotosCount || 0;
    return fotosEntradaNãovas + fotosEntradaExistentes + getTotalAcessãoriosFotos() + getTotalEstadoFisicoFotos();
}

function _setResumoRascunho(text) {
    const el = document.getElementById('resumoRascunho');
    if (el) el.textContent = text;
}

function _collectDraft() {
    const clienteSel = document.getElementById('clienteOsSelect');
    const equipSel   = document.getElementById('equipamentoSelect');
    const tecnicoSel = document.querySelector('select[name="tecnico_id"]');
    const prioridadeSel = document.querySelector('select[name="prioridade"]');
    const statusSel  = document.querySelector('select[name="status"]');
    const entradaInp = document.querySelector('input[name="data_entrada"]');
    const previsaoInp = document.querySelector('input[name="data_previsao"]');
    const relatoInp  = document.getElementById('relatoClienteInput') || document.querySelector('textarea[name="relato_cliente"]');
    const acessãoriosInp = document.querySelector('textarea[name="acessãorios"]');
    const estadoFisicoInp = document.querySelector('textarea[name="estado_fisico"]');
    const formaPagamentoSel = document.querySelector('select[name="forma_pagamento"]');

    return {
        savedAt: new Date().toISOString(),
        cliente_id: clienteSel?.value || '',
        equipamento_id: equipSel?.value || '',
        tecnico_id: tecnicoSel?.value || '',
        prioridade: prioridadeSel?.value || 'nãormal',
        status: statusSel?.value || 'triagem',
        data_entrada: entradaInp?.value || '',
        data_previsao: previsaoInp?.value || '',
        relato_cliente: relatoInp?.value || '',
        acessãorios: acessãoriosInp?.value || '',
        acessãorios_sem_itens: acessãoriosSemItensCheckbox?.checked ? '1' : '0',
        estado_fisico: estadoFisicoInp?.value || '',
        estado_fisico_sem_avarias: estadoFisicoSemAvariasCheckbox?.checked ? '1' : '0',
        forma_pagamento: formaPagamentoSel?.value || '',
        defeitos: Array.from(document.querySelectorAll('.chk-defeito-comum:checked')).map(el => el.value)
    };
}

function _hasDraftData(data) {
    if (!data) return false;
    return Boolean(
        data.cliente_id ||
        data.equipamento_id ||
        data.tecnico_id ||
        data.data_previsao ||
        data.relato_cliente?.trim() ||
        data.acessãorios?.trim() ||
        data.acessãorios_sem_itens === '1' ||
        data.estado_fisico?.trim() ||
        data.estado_fisico_sem_avarias === '1' ||
        data.forma_pagamento?.trim() ||
        (data.defeitos && data.defeitos.length)
    );
}

function saveDraftNãow() {
    if (isEdit) return;
    const data = _collectDraft();
    if (!_hasDraftData(data)) {
        localStorage.removeItem(DRAFT_KEY);
        _setResumoRascunho('N�o salvo');
        return;
    }
    localStorage.setItem(DRAFT_KEY, JSON.stringify(data));
    const savedTime = new Date(data.savedAt).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
    _setResumoRascunho('Salvo ' + savedTime);
}

function scheduleDraftSave() {
    if (isEdit) return;
    if (draftSaveTimer) clearTimeout(draftSaveTimer);
    draftSaveTimer = setTimeout(() => {
        saveDraftNãow();
    }, 800);
}

function _loadDraft() {
    if (isEdit) return null;
    const raw = localStorage.getItem(DRAFT_KEY);
    if (!raw) return null;
    try {
        const data = JSON.parse(raw);
        if (!data?.savedAt) return null;
        const savedAt = new Date(data.savedAt);
        if (Number.isNaN(savedAt.getTime())) return null;
        if (Date.nãow() - savedAt.getTime() > DRAFT_TTL_MS) {
            localStorage.removeItem(DRAFT_KEY);
            return null;
        }
        return data;
    } catch {
        return null;
    }
}

function _applyDraft(data) {
    if (!data) return;
    const tecnicoSel = document.querySelector('select[name="tecnico_id"]');
    const prioridadeSel = document.querySelector('select[name="prioridade"]');
    const statusSel  = document.querySelector('select[name="status"]');
    const entradaInp = document.querySelector('input[name="data_entrada"]');
    const previsaoInp = document.querySelector('input[name="data_previsao"]');
    const relatoInp  = document.getElementById('relatoClienteInput') || document.querySelector('textarea[name="relato_cliente"]');
    const acessãoriosInp = document.querySelector('textarea[name="acessãorios"]');
    const estadoFisicoInp = document.querySelector('textarea[name="estado_fisico"]');
    const formaPagamentoSel = document.querySelector('select[name="forma_pagamento"]');

    if (tecnicoSel) tecnicoSel.value = data.tecnico_id || '';
    if (prioridadeSel) prioridadeSel.value = data.prioridade || 'nãormal';
    if (statusSel) statusSel.value = data.status || 'triagem';
    if (entradaInp && data.data_entrada) entradaInp.value = data.data_entrada;
    if (previsaoInp) previsaoInp.value = data.data_previsao || '';
    if (relatoInp) relatoInp.value = data.relato_cliente || '';
    if (acessãoriosInp) acessãoriosInp.value = data.acessãorios || '';
    if (estadoFisicoInp) estadoFisicoInp.value = data.estado_fisico || '';
    if (acessãoriosSemItensCheckbox) {
        const semItens = String(data.acessãorios_sem_itens || '') === '1'
            || String(data.acessãorios || '').trim().toLowerCase() === ACCESSORIOS_SEM_ITENS_TEXT.toLowerCase();
        acessãoriosSemItensCheckbox.checked = semItens;
        clearAllAcessãorios();
        if (!semItens) {
            const draftAcessãorios = String(data.acessãorios || '').trim();
            if (draftAcessãorios) {
                draftAcessãorios.split(/\r?\n/).filter(Boolean).forEach(text => {
                    acessãoriosEntries.push({ id: generateEntryId(), text, key: 'outro' });
                });
            }
        }
        refreshAcessãoriosSemItensUi();
        renderAcessãoriosList();
        syncAcessãoriosInput();
    }
    if (estadoFisicoSemAvariasCheckbox) {
        const semAvarias = String(data.estado_fisico_sem_avarias || '') === '1'
            || String(data.estado_fisico || '').trim().toLowerCase() === ESTADO_FISICO_SEM_AVARIAS_TEXT.toLowerCase();
        estadoFisicoSemAvariasCheckbox.checked = semAvarias;
        clearAllEstadoFisico();
        if (!semAvarias) {
            const draftEstadoFisico = String(data.estado_fisico || '').trim();
            if (draftEstadoFisico) {
                draftEstadoFisico.split(/\r?\n/).filter(Boolean).forEach(text => {
                    estadoFisicoEntries.push({ id: generateEstadoFisicoEntryId(), text, key: 'outro' });
                });
            }
        }
        refreshEstadoFisicoSemAvariasUi();
        renderEstadoFisicoList();
        syncEstadoFisicoInput();
    }
    if (formaPagamentoSel) formaPagamentoSel.value = data.forma_pagamento || '';

    pendingDefeitos = Array.isArray(data.defeitos) ? data.defeitos : [];

    if (data.cliente_id) {
        const clienteSel = document.getElementById('clienteOsSelect');
        if (clienteSel) {
            clienteSel.value = data.cliente_id;
            if (typeof $.fn.select2 !== 'undefined') {
                $('#clienteOsSelect').val(String(data.cliente_id)).trigger('change');
            } else {
                _onClienteChange(data.cliente_id);
            }
        }
        if (data.equipamento_id) {
            pendingEquipId = data.equipamento_id;
        }
    }
}

function _applyPendingDefeitos() {
    if (!pendingDefeitos || !pendingDefeitos.length) return;
    pendingDefeitos.forEach(id => {
        const chk = document.getElementById('def_' + id);
        if (chk) chk.checked = true;
    });
    pendingDefeitos = null;
}

// Rascunho autom�tico para nãova OS
if (!isEdit) {
    const draftData = _loadDraft();
    const draftAlert = document.getElementById('osDraftAlert');
    if (draftData && draftAlert) {
        draftAlert.classList.remove('d-nãone');
        const savedAtLabel = new Date(draftData.savedAt).toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' });
        _setResumoRascunho('Rascunho em ' + savedAtLabel);
        document.getElementById('btnRestaurarRascunho')?.addEventListener('click', () => {
            draftAlert.classList.add('d-nãone');
            _applyDraft(draftData);
            updateResumo();
            scheduleDraftSave();
        });
        document.getElementById('btnDescartarRascunho')?.addEventListener('click', () => {
            localStorage.removeItem(DRAFT_KEY);
            draftAlert.classList.add('d-nãone');
            _setResumoRascunho('N�o salvo');
        });
    } else {
        _setResumoRascunho('N�o salvo');
    }

    document.getElementById('btnLimparRascunho')?.addEventListener('click', () => {
        localStorage.removeItem(DRAFT_KEY);
        _setResumoRascunho('N�o salvo');
    });
}

function clearValidationMarks() {
    document.querySelectorAll('.is-invalid, .border-danger, .border-warning').forEach(el => {
        el.classList.remove('is-invalid', 'border-danger', 'border-warning');
    });
}

function markInvalid(el) {
    if (!el) return;
    el.classList.add('is-invalid', 'border', 'border-danger');
}

function markWarning(el) {
    if (!el) return;
    el.classList.add('border', 'border-warning');
}

function getTotalEntradaFotos() {
    try {
        if (typeof getTotalFotosEntradaResumo === 'function') {
            return getTotalFotosEntradaResumo();
        }
        return (osFotosExistingData?.length || 0) + (osDataTransfer?.files?.length || 0);
    } catch (_) {
        return 0;
    }
}

const formOs = document.getElementById('formOs');
if (formOs) {
    formOs.addEventListener('submit', (e) => {
        if (formOs.dataset.bypassValidation === '1') return;
        e.preventDefault();
        clearValidationMarks();

        const goToField = (el, tabBtnId) => {
            const tabBtn = document.getElementById(tabBtnId);
            tabBtn?.click();
            el?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            el?.focus({ preventScroll: true });
        };

        const requiredFields = [
            { selector: '#clienteOsSelect', label: 'Cliente', tabBtnId: 'tab-dados-btn' },
            { selector: '#equipamentoSelect', label: 'Equipamento', tabBtnId: 'tab-dados-btn' },
            { selector: 'select[name="tecnico_id"]', label: 'Tecnico', tabBtnId: 'tab-dados-btn' },
            { selector: 'input[name="data_entrada"]', label: 'Data de Entrada', tabBtnId: 'tab-dados-btn' },
            { selector: '#relatoClienteInput', label: 'Relato do Cliente', tabBtnId: 'tab-relato-btn' },
        ];

        const optionalChecks = [
            { selector: 'input[name="data_previsao"]', label: 'Previsao de Entrega', tabBtnId: 'tab-dados-btn', isMissing: (el) => !el?.value },
            {
                selector: '#acessãoriosSemItens',
                label: 'Acessãorios/Componentes',
                tabBtnId: 'tab-dados-btn',
                isMissing: () => !isAcessãoriosSemItensChecked() && !((acessãoriosInput?.value || '').trim())
            },
            {
                selector: '#estadoFisicoSemAvarias',
                label: 'Estado fisico',
                tabBtnId: 'tab-dados-btn',
                isMissing: () => !isEstadoFisicoSemAvariasChecked() && !((estadoFisicoInput?.value || '').trim())
            },
            { selector: '#osFotosPreview', label: 'Fotos de Entrada', tabBtnId: 'tab-fotos-btn', isMissing: () => getTotalEntradaFotos() === 0 },
        ];

        const missingRequired = [];
        let firstFocus = null;
        let firstTabBtn = null;

        requiredFields.forEach((field) => {
            const el = document.querySelector(field.selector);
            const empty = !el || !String(el.value || '').trim();
            if (empty) {
                missingRequired.push(field.label);
                markInvalid(el);
                if (!firstFocus) firstFocus = el;
                if (!firstTabBtn) firstTabBtn = document.getElementById(field.tabBtnId);
            }
        });

        if (missingRequired.length) {
            const openRequiredFocus = () => {
                if (firstFocus) {
                    goToField(firstFocus, firstTabBtn?.id || 'tab-dados-btn');
                    markInvalid(firstFocus);
                }
            };
            if (window.Swal && typeof window.Swal.fire === 'function') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Complete os obrigatorios',
                    html: `Faltam: <strong>${missingRequired.join(', ')}</strong>.`,
                    confirmButtonText: 'Ir para o campo',
                    customClass: { popup: 'glass-card' }
                }).then(openRequiredFocus);
            } else {
                alert(`Campos obrigatorios pendentes: ${missingRequired.join(', ')}.`);
                openRequiredFocus();
            }
            return;
        }

        const missingOptional = optionalChecks.filter((check) => {
            const el = document.querySelector(check.selector);
            return check.isMissing(el);
        });

        if (missingOptional.length) {
            const labels = missingOptional.map((m) => m.label).join(', ');
            const firstMissing = missingOptional[0];
            const target = document.querySelector(firstMissing.selector);
            const proceedWithoutOptional = () => {
                formOs.dataset.bypassValidation = '1';
                localStorage.removeItem(DRAFT_KEY);
                _setResumoRascunho('Nao salvo');
                formOs.submit();
            };
            const fillOptional = () => {
                markWarning(target);
                goToField(target, firstMissing.tabBtnId);
            };
            if (window.Swal && typeof window.Swal.fire === 'function') {
                Swal.fire({
                    icon: 'info',
                    title: 'Itens pendentes',
                    html: `${labels}.<br>Quer preencher agora?`,
                    showCancelButton: true,
                    confirmButtonText: 'Ir para pendencia',
                    cancelButtonText: 'Prosseguir assim',
                    reverseButtons: true,
                    customClass: { popup: 'glass-card' }
                }).then((result) => {
                    if (result.isConfirmed) {
                        fillOptional();
                        return;
                    }
                    proceedWithoutOptional();
                });
            } else {
                const wantsFill = confirm(`Ha itens pendentes: ${labels}. Deseja ir para a pendencia agora?`);
                if (wantsFill) {
                    fillOptional();
                } else {
                    proceedWithoutOptional();
                }
            }
            return;
        }

        formOs.dataset.bypassValidation = '1';
        localStorage.removeItem(DRAFT_KEY);
        _setResumoRascunho('Nao salvo');
        formOs.submit();
    });
}

const prazoEntregaSelect = document.getElementById('prazoEntregaSelect');
prazoEntregaSelect?.addEventListener('change', function() {
    const days = parseInt(this.value, 10);
    if (!days) return;
    const entradaVal = document.querySelector('input[name="data_entrada"]')?.value;
    const baseDate = entradaVal ? new Date(entradaVal) : new Date();
    if (Number.isNaN(baseDate.getTime())) return;
    baseDate.setDate(baseDate.getDate() + days);
    const yyyy = baseDate.getFullYear();
    const mm = String(baseDate.getMonth() + 1).padStart(2, '0');
    const dd = String(baseDate.getDate()).padStart(2, '0');
    const previsaoInp = document.querySelector('input[name="data_previsao"]');
    if (previsaoInp) {
        previsaoInp.value = `${yyyy}-${mm}-${dd}`;
        updateResumo();
        scheduleDraftSave();
    }
});

// ??? Carrega fotos do equipamento ??????????????????????????????????????????
function carregarFotosEquipamentoLegacy(equipId, equipData) {
    const mainBox     = document.getElementById('fotoMainBox');
    const img         = document.getElementById('fotoPrincipalImg');
    const placeholder = document.getElementById('fotoPlaceholder');
    const minis       = document.getElementById('fotosMiniaturas');
    const infoBox     = document.getElementById('equipInfoBox');
    const infoContent = document.getElementById('equipInfoContent');
    const colorInfo   = document.getElementById('equipColorInfo');
    const colorSwatch = document.getElementById('equipColorSwatch');
    const colorName   = document.getElementById('equipColorName');

    // Mostra sidebar
    showSidebar();

    // Info do equipamento
    if (equipData) {
        infoBox.style.display = '';
        infoContent.innerHTML = `
            <div><i class="bi bi-tag me-1"></i><strong>${equipData.marca || ''} ${equipData.modelo || ''}</strong></div>
            ${equipData.serie ? `<div class="mt-1"><i class="bi bi-upc me-1"></i>S/N: ${equipData.serie}</div>` : ''}
            ${equipData.tipo  ? `<div class="mt-1"><i class="bi bi-cpu me-1"></i>${equipData.tipo}</div>` : ''}
        `;
        const corHex = equipData.cor_hex || '#2a2a2a';
        const corNãome = equipData.cor || 'Cor n�o informada';
        if (colorSwatch) colorSwatch.style.background = corHex;
        if (colorName) colorName.textContent = corNãome;
        if (colorInfo) colorInfo.classList.remove('d-nãone');
    }

    // Busca fotos via AJAX
    fetch(`${BASE_URL}equipamentos/fotos/${equipId}`)
    .then(r => r.jsãon())
    .then(fotos => {
        minis.innerHTML = '';
        if (fotos.length === 0) {
            mainBox.classList.add('d-nãone');
            placeholder.classList.remove('d-nãone');
            placeholder.classList.add('d-flex');
            if (equipData?.cor_hex) {
                placeholder.style.background = equipData.cor_hex;
                placeholder.style.border = '2px sãolid rgba(0,0,0,0.2)';
                placeholder.style.color = '#fff';
            }
            return;
        }

        // Foto principal
        const principal = fotos.find(f => f.is_principal == 1) || fotos[0];
        img.src = principal.url;
        document.getElementById('fotoPrincipalLink').setAttribute('data-img-src', principal.url);
        mainBox.classList.remove('d-nãone');
        placeholder.classList.add('d-nãone');
        placeholder.classList.remove('d-flex');
        placeholder.style.background = 'rgba(255,255,255,0.04)';
        placeholder.style.color = '';

        // Miniaturas
                fotos.forEach((f, i) => {
                    const el = document.createElement('div');
                    el.className = 'border rounded overflow-hidden shadow-sm hover-elevate cursãor-pointer';
                    el.style.cssText = 'width: 45px; height: 45px; cursãor: pointer; transition: all 0.2s;';
                    el.innerHTML = `<img src="${f.url}" class="w-100 h-100 object-fit-cover">`;
            el.addEventListener('click', () => {
                img.style.opacity = '0.4';
                setTimeout(() => {
                    img.src = f.url;
                    document.getElementById('fotoPrincipalLink').setAttribute('data-img-src', f.url);
                    img.style.opacity = '1';
                }, 150);
                minis.querySelectorAll('div').forEach(m => m.style.borderColor = 'rgba(255,255,255,0.1)');
                el.style.borderColor = 'var(--primary)';
            });
            minis.appendChild(el);
        });
    });
}

// ??? Select de cliente ? carrega equipamentos ?????????????????????????????
// Override com renderizacao reativa e anti-cache para fotos do equipamento.
let equipamentoFotosVersion = Date.nãow();
function bumpEquipamentoFotosVersion() {
    equipamentoFotosVersion = Date.nãow();
}

function withFotoVersion(url, version = equipamentoFotosVersion) {
    if (!url) return '';
    const value = String(url);
    const separator = value.includes('?') ? '&' : '?';
    return `${value}${separator}v=${version}`;
}

function renderFotosEquipamentoSidebar(fotos, equipData) {
    const mainBox     = document.getElementById('fotoMainBox');
    const img         = document.getElementById('fotoPrincipalImg');
    const placeholder = document.getElementById('fotoPlaceholder');
    const minis       = document.getElementById('fotosMiniaturas');
    const infoBox     = document.getElementById('equipInfoBox');
    const infoContent = document.getElementById('equipInfoContent');
    const colorInfo   = document.getElementById('equipColorInfo');
    const colorSwatch = document.getElementById('equipColorSwatch');
    const colorName   = document.getElementById('equipColorName');

    showSidebar();

    if (equipData) {
        infoBox.style.display = '';
        infoContent.innerHTML = `
            <div><i class="bi bi-tag me-1"></i><strong>${equipData.marca || ''} ${equipData.modelo || ''}</strong></div>
            ${equipData.serie ? `<div class="mt-1"><i class="bi bi-upc me-1"></i>S/N: ${equipData.serie}</div>` : ''}
            ${equipData.tipo  ? `<div class="mt-1"><i class="bi bi-cpu me-1"></i>${equipData.tipo}</div>` : ''}
        `;
        const corHex = equipData.cor_hex || '#2a2a2a';
        const corNãome = equipData.cor || 'Cor nao informada';
        if (colorSwatch) colorSwatch.style.background = corHex;
        if (colorName) colorName.textContent = corNãome;
        if (colorInfo) colorInfo.classList.remove('d-nãone');
    }

    minis.innerHTML = '';
    const lista = Array.isArray(fotos) ? fotos : [];
    if (!lista.length) {
        mainBox.classList.add('d-nãone');
        placeholder.classList.remove('d-nãone');
        placeholder.classList.add('d-flex');
        if (equipData?.cor_hex) {
            placeholder.style.background = equipData.cor_hex;
            placeholder.style.border = '2px sãolid rgba(0,0,0,0.2)';
            placeholder.style.color = '#fff';
        }
        return;
    }

    const principal = lista.find(f => Number(f.is_principal) === 1) || lista[0];
    const principalUrl = withFotoVersion(principal.url);
    img.src = principalUrl;
    document.getElementById('fotoPrincipalLink')?.setAttribute('data-img-src', principalUrl);
    mainBox.classList.remove('d-nãone');
    placeholder.classList.add('d-nãone');
    placeholder.classList.remove('d-flex');
    placeholder.style.background = 'rgba(255,255,255,0.04)';
    placeholder.style.color = '';

    lista.forEach((foto) => {
        const thumbUrl = withFotoVersion(foto.url);
        const isPrincipal = Number(foto.is_principal) === 1;
        const el = document.createElement('div');
        el.className = 'border rounded overflow-hidden shadow-sm hover-elevate cursãor-pointer';
        el.style.cssText = `width:45px;height:45px;cursãor:pointer;transition:all 0.2s;border-color:${isPrincipal ? 'var(--primary)' : 'rgba(255,255,255,0.1)'};`;
        el.innerHTML = `<img src="${thumbUrl}" class="w-100 h-100 object-fit-cover" alt="Miniatura do equipamento">`;
        el.addEventListener('click', () => {
            img.style.opacity = '0.4';
            setTimeout(() => {
                img.src = thumbUrl;
                document.getElementById('fotoPrincipalLink')?.setAttribute('data-img-src', thumbUrl);
                img.style.opacity = '1';
            }, 120);
            minis.querySelectorAll('div').forEach(m => { m.style.borderColor = 'rgba(255,255,255,0.1)'; });
            el.style.borderColor = 'var(--primary)';
        });
        minis.appendChild(el);
    });
}

function carregarFotosEquipamento(equipId, equipData, fotosOverride = null) {
    if (Array.isArray(fotosOverride)) {
        bumpEquipamentoFotosVersion();
        renderFotosEquipamentoSidebar(fotosOverride, equipData);
        return;
    }
    fetch(`${BASE_URL}equipamentos/fotos/${equipId}?v=${Date.nãow()}`)
        .then(r => r.jsãon())
        .then(fotos => {
            bumpEquipamentoFotosVersion();
            renderFotosEquipamentoSidebar(fotos, equipData);
        })
        .catch(() => {
            renderFotosEquipamentoSidebar([], equipData);
        });
}

function _onClienteChange(clienteId) {
    const equipamentoSelect = document.getElementById('equipamentoSelect');
    if (!equipamentoSelect) return;

    // Destroi Select2 do equipamento antes de popular (apenas se estiver inicializado)
    if (typeof $.fn.select2 !== 'undefined' && $('#equipamentoSelect').hasClass("select2-hidden-accessible")) {
        try { $('#equipamentoSelect').select2('destroy'); } catch(e) {}
    }

    equipamentoSelect.innerHTML = '<option value="">Carregando equipamentos...</option>';
    equipamentoSelect.disabled = true;
    hideSidebar();

    if (!clienteId) {
        equipamentoSelect.innerHTML = '<option value="">Selecione o cliente primeiro...</option>';
        equipamentoSelect.disabled = false;
        if (typeof setEquipamentoEditButtonState === 'function') setEquipamentoEditButtonState();
        updateResumo();
        scheduleDraftSave();
        return;
    }

    // Atualiza cliente_id para o modal
    const hiddenCli = document.getElementById('nãovoEquipClienteId');
    if (hiddenCli) hiddenCli.value = clienteId;
    window._osClienteId = clienteId;

    fetch(`${BASE_URL}equipamentos/por-cliente/${clienteId}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.jsãon())
    .then(equipamentos => {
        if (window._osEquipamentosCache) {
            Object.keys(window._osEquipamentosCache).forEach(key => delete window._osEquipamentosCache[key]);
        }
        const autoSelectId = equipamentos.length === 1 ? equipamentos[0].id : null;
        if (equipamentos.length === 0) {
            equipamentoSelect.innerHTML = '<option value="">Nenhum equipamento vinculado</option>';
        } else {
            equipamentoSelect.innerHTML = '<option value="">Selecione o equipamento...</option>';
            equipamentos.forEach(eq => {
                if (window._osEquipamentosCache) {
                    window._osEquipamentosCache[String(eq.id)] = eq;
                }
                const nãome = (eq.marca_nãome || '') + ' ' + (eq.modelo_nãome || '') + ' (' + (eq.tipo_nãome || eq.tipo || '') + ')';
                const opt  = new Option(nãome, eq.id);
                opt.dataset.tipo      = eq.tipo_id || '';
                opt.dataset.marca     = eq.marca_nãome || '';
                opt.dataset.modelo    = eq.modelo_nãome || '';
                opt.dataset.serie     = eq.numero_serie || '';
                opt.dataset.cor       = eq.cor || '';
                opt.dataset.cor_hex   = eq.cor_hex || '';
                opt.dataset.tipo_nãome = eq.tipo_nãome || '';
                opt.dataset.marca_id  = eq.marca_id || '';
                opt.dataset.modelo_id = eq.modelo_id || '';
                opt.dataset.cliente_id = eq.cliente_id || '';
                opt.dataset.senha_acessão = eq.senha_acessão || '';
                opt.dataset.estado_fisico = eq.estado_fisico || '';
                opt.dataset.acessãorios = eq.acessãorios || '';
                equipamentoSelect.appendChild(opt);
            });
        }
        equipamentoSelect.disabled = false;
        // Re-inicializa Select2 não equipamento
        if (typeof $.fn.select2 !== 'undefined') {
            $('#equipamentoSelect').select2({
                theme: 'bootstrap-5',
                placeholder: 'Selecione o equipamento...',
                allowClear: true,
                width: '100%'
            }).on('change', function() {
                _onEquipamentoChange(this.value, this.options[this.selectedIndex]);
            });
            const targetId = pendingEquipId || autoSelectId;
            if (targetId) {
                $('#equipamentoSelect').val(String(targetId)).trigger('change');
                pendingEquipId = null;
            }
        } else {
            const targetId = pendingEquipId || autoSelectId;
            if (targetId) {
                equipamentoSelect.value = String(targetId);
                _onEquipamentoChange(equipamentoSelect.value, equipamentoSelect.options[equipamentoSelect.selectedIndex]);
                pendingEquipId = null;
            }
        }
        if (typeof setEquipamentoEditButtonState === 'function') setEquipamentoEditButtonState();
        updateResumo();
        scheduleDraftSave();
    })
    .catch(() => {
        equipamentoSelect.innerHTML = '<option value="">Erro ao carregar.</option>';
        equipamentoSelect.disabled = false;
        if (typeof setEquipamentoEditButtonState === 'function') setEquipamentoEditButtonState();
    });
}

// Ouve via Select2 (ou fallback vanilla)
if (typeof $.fn.select2 !== 'undefined') {
    $('#clienteOsSelect').on('change', function() {
        _onClienteChange(this.value);
    });
} else {
    document.getElementById('clienteOsSelect')?.addEventListener('change', function() {
        _onClienteChange(this.value);
    });
}

// ??? Handler de mudan�a de equipamento ??????????????????????????????????
function _onEquipamentoChange(id, opt) {
    const tipoId = opt ? opt.getAttribute('data-tipo') : null;
    carregarDefeitos(tipoId);
    if (id) {
        carregarFotosEquipamento(id, {
            marca:  opt?.dataset?.marca,
            modelo: opt?.dataset?.modelo,
            serie:  opt?.dataset?.serie,
            tipo:   opt?.dataset?.tipo_nãome,
            cor:    opt?.dataset?.cor,
            cor_hex: opt?.dataset?.cor_hex
        });
    } else {
        hideSidebar();
    }
    if (typeof setEquipamentoEditButtonState === 'function') setEquipamentoEditButtonState();
    updateResumo();
    scheduleDraftSave();
}

// ??? Listener vanilla do equipamentoSelect (usado quando Select2 ainda n�o foi inicializado) ???
const equipSelect = document.getElementById('equipamentoSelect');
if (equipSelect) {
    equipSelect.addEventListener('change', function() {
        // Apenas disparado quando Select2 n�o est� ativo
        if (!$(this).data('select2')) {
            _onEquipamentoChange(this.value, this.options[this.selectedIndex]);
        }
    });

    // Na edi��o, carrega automaticamente
    if (isEdit && equipSelect.value) {
        const opt = equipSelect.options[equipSelect.selectedIndex];
        const tipoId = opt ? opt.getAttribute('data-tipo') : null;
        if (tipoId) carregarDefeitos(tipoId);
        if (equipSelect.value) {
            carregarFotosEquipamento(equipSelect.value, {
                marca:  opt?.dataset.marca,
                modelo: opt?.dataset.modelo,
                serie:  opt?.dataset.serie,
                tipo:   opt?.dataset.tipo_nãome,
                cor:    opt?.dataset.cor,
                cor_hex: opt?.dataset.cor_hex
            });
        }
    }
}

// Atualiza resumo e rascunho conforme altera��es não formul�rio
['input', 'change'].forEach(evt => {
    document.querySelector('textarea[name="acessãorios"]')?.addEventListener(evt, () => {
        updateResumo();
        scheduleDraftSave();
    });
    document.querySelector('select[name="tecnico_id"]')?.addEventListener(evt, () => {
        updateResumo();
        scheduleDraftSave();
    });
    document.querySelector('select[name="prioridade"]')?.addEventListener(evt, () => {
        updateResumo();
        scheduleDraftSave();
    });
    document.querySelector('select[name="status"]')?.addEventListener(evt, () => {
        updateResumo();
        scheduleDraftSave();
    });
    document.querySelector('input[name="data_entrada"]')?.addEventListener(evt, () => {
        updateResumo();
        scheduleDraftSave();
        if (prazoEntregaSelect?.value) {
            prazoEntregaSelect.dispatchEvent(new Event('change'));
        }
    });
    document.querySelector('input[name="data_previsao"]')?.addEventListener(evt, () => {
        updateResumo();
        scheduleDraftSave();
    });
    document.querySelector('select[name="forma_pagamento"]')?.addEventListener(evt, () => {
        updateResumo();
        scheduleDraftSave();
    });
});

// ??? Preview fotos de entrada ?????????????????????????????????????????????
const osFotosExistingData = <?= jsãon_encode(array_map(fn($f) => ['url' => $f['url']], $fotos_entrada ?? [])) ?>;
const osFotosMaxFiles = 4;
const osFotoMaxSizeMb = 2;
const fotosEntradaInput = document.getElementById('fotosEntradaInput');
const fotosEntradaGaleriaInput = document.getElementById('fotosEntradaGaleriaInput');
const osFotosPreview = document.getElementById('osFotosPreview');
const osFotosExisting = document.getElementById('osFotosExisting');
const osFotosDropzone = document.getElementById('osFotosDropzone');
const fotosEntradaEmptyState = document.getElementById('fotosEntradaEmptyState');
const btnFotosEscolher = document.getElementById('btnFotosEscolher');
const btnFotosEntradaCamera = document.getElementById('btnFotosEntradaCamera');
const btnFotosEntradaGaleria = document.getElementById('btnFotosEntradaGaleria');
const btnLimparFotos = document.getElementById('btnLimparFotos');
const osDataTransfer = new DataTransfer();
let fotosEntradaCropQueue = [];

function syncFotosEntradaInput() {
    if (fotosEntradaInput) {
        fotosEntradaInput.files = osDataTransfer.files;
    }
}

function toggleFotosEntradaEmptyState() {
    if (!fotosEntradaEmptyState) return;
    const totalPhotos = osFotosExistingData.length + osDataTransfer.files.length;
    fotosEntradaEmptyState.style.display = totalPhotos > 0 ? 'nãone' : 'block';
}

function queueFotosEntradaFromFiles(files) {
    const incoming = Array.from(files || []).filter(file => file.type?.startsWith('image/'));
    if (!incoming.length) return;

    const disponivel = osFotosMaxFiles - osDataTransfer.files.length;
    if (disponivel <= 0) {
        showWarningDialog(`Vocêe pode enviar ate ${osFotosMaxFiles} fotos não total.`);
        return;
    }

    fotosEntradaCropQueue = incoming.slice(0, disponivel);
    if (incoming.length > disponivel) {
        showWarningDialog(`Sãomente ${disponivel} foto(s) cabem agora (limite de ${osFotosMaxFiles}).`);
    }
    processNextFotoEntradaCrop();
}

function processNextFotoEntradaCrop() {
    if (!fotosEntradaCropQueue.length) {
        hideModalSafe(modalCrop, '#modalCropEquip');
        return;
    }
    const nextFile = fotosEntradaCropQueue.shift();
    if (nextFile.size > (osFotoMaxSizeMb * 1024 * 1024)) {
        showWarningDialog(`Cada foto deve ter não maximo ${osFotoMaxSizeMb}MB.`);
        processNextFotoEntradaCrop();
        return;
    }
    const reader = new FileReader();
    reader.onload = e => openCropper(e.target.result, { type: 'entrada' });
    reader.readAsDataURL(nextFile);
}

function renderExistingFotos() {
    if (!osFotosExisting) return;
    osFotosExisting.innerHTML = '';
    osFotosExistingData.forEach((foto, idx) => {
        const thumb = document.createElement('div');
        thumb.className = 'position-relative border rounded overflow-hidden cursãor-pointer';
        thumb.style.cssText = 'width:90px; height:90px;';
        thumb.innerHTML = `
            <img src="${foto.url}" class="w-100 h-100 object-fit-cover">
        `;
        thumb.setAttribute('data-bs-toggle', 'modal');
        thumb.setAttribute('data-bs-target', '#imageModal');
        thumb.setAttribute('data-img-src', foto.url);
        osFotosExisting.appendChild(thumb);
    });
}

function renderNewFotos() {
    if (!osFotosPreview) return;
    osFotosPreview.innerHTML = '';
    Array.from(osDataTransfer.files).forEach((file, index) => {
        const reader = new FileReader();
        reader.onload = e => {
            const thumb = document.createElement('div');
            thumb.className = 'position-relative border rounded overflow-hidden';
            thumb.style.cssText = 'width:110px; height:110px;';
            thumb.innerHTML = `
                <img src="${e.target.result}" class="w-100 h-100 object-fit-cover">
                <button type="button" class="btn btn-sm btn-outline-danger position-absãolute top-0 end-0 m-1 btn-remover-foto-nãova" data-index="${index}">
                    <i class="bi bi-x"></i>
                </button>
            `;
            thumb.setAttribute('data-bs-toggle', 'modal');
            thumb.setAttribute('data-bs-target', '#imageModal');
            thumb.setAttribute('data-img-src', e.target.result);
            osFotosPreview.appendChild(thumb);
        };
        reader.readAsDataURL(file);
    });
}

function updatePhotoState() {
    toggleFotosEntradaEmptyState();
    updateResumo();
}

function clearNewFotos() {
    osDataTransfer.itemês.clear();
    fotosEntradaCropQueue = [];
    if (fotosEntradaInput) fotosEntradaInput.value = '';
    if (fotosEntradaGaleriaInput) fotosEntradaGaleriaInput.value = '';
    renderNewFotos();
    updatePhotoState();
    scheduleDraftSave();
}

osFotosDropzone?.addEventListener('click', () => fotosEntradaGaleriaInput?.click());
osFotosDropzone?.addEventListener('dragover', e => {
    e.preventDefault();
    osFotosDropzone.classList.add('border-primary');
});
osFotosDropzone?.addEventListener('dragleave', () => {
    osFotosDropzone.classList.remove('border-primary');
});
osFotosDropzone?.addEventListener('drop', e => {
    e.preventDefault();
    osFotosDropzone.classList.remove('border-primary');
    queueFotosEntradaFromFiles(e.dataTransfer.files);
});
btnFotosEscolher?.addEventListener('click', () => fotosEntradaGaleriaInput?.click());
btnFotosEntradaGaleria?.addEventListener('click', () => fotosEntradaGaleriaInput?.click());
btnFotosEntradaCamera?.addEventListener('click', () => openCameraCapture({ type: 'entrada', entryId: null }));
btnLimparFotos?.addEventListener('click', clearNewFotos);

fotosEntradaGaleriaInput?.addEventListener('change', function() {
    queueFotosEntradaFromFiles(this.files);
    this.value = '';
});
osFotosPreview?.addEventListener('click', function(event) {
    const remover = event.target.closest('.btn-remover-foto-nãova');
    if (!remover) return;
    const index = parseInt(remover.dataset.index, 10);
    const dt = new DataTransfer();
    Array.from(osDataTransfer.files).forEach((file, idx) => {
        if (idx !== index) dt.itemês.add(file);
    });
    osDataTransfer.itemês.clear();
    Array.from(dt.files).forEach(f => osDataTransfer.itemês.add(f));
    syncFotosEntradaInput();
    renderNewFotos();
    updatePhotoState();
    scheduleDraftSave();
});
renderExistingFotos();
renderNewFotos();
updatePhotoState();

// ??? Modal: Cadastrar Nãovo Equipamento ?????????????????????????????????????
const osEquipamentosCache = window._osEquipamentosCache || (window._osEquipamentosCache = {});
const btnNãovoEquip = document.getElementById('btnNãovoEquipamento');
const btnEditarEquip = document.getElementById('btnEditarEquipamento');
const modalNãovoEquipamentoEl = document.getElementById('modalNãovoEquipamento');
const modalNãovoEquipamento = modalNãovoEquipamentoEl ? new bootstrap.Modal(modalNãovoEquipamentoEl) : null;
const formNãovoEquipAjax = document.getElementById('formNãovoEquipAjax');
const labelModalNãovoEquip = document.getElementById('labelModalNãovoEquip');
const btnSalvarNãovoEquip = document.getElementById('btnSalvarNãovoEquip');
const modalEquipFotosExistentesWrap = document.getElementById('modalEquipFotosExistentesWrap');
const modalEquipFotosExistentes = document.getElementById('modalEquipFotosExistentes');
const nãovoEquipFotosNãovasList = document.getElementById('nãovoEquipFotosNãovasList');
let equipamentoModalMode = 'create';
let equipamentoEditId = null;
let modalEquipExistingFotos = [];
let modalEquipFotosVersion = Date.nãow();
const nãovoEquipFotosMaxFiles = 4;
const nãovoEquipFotosDataTransfer = new DataTransfer();
let nãovoEquipFotoCropQueue = [];

function bumpModalEquipFotosVersion() {
    modalEquipFotosVersion = Date.nãow();
}

function showWarningDialog(message, title = 'Aten��o') {
    if (window.Swal && typeof window.Swal.fire === 'function') {
        Swal.fire({
            icon: 'warning',
            title,
            text: message,
            confirmButtonText: 'OK',
            customClass: { popup: 'glass-card' }
        });
        return;
    }
    alert(message);
}

function ensureModalEquipSelect2() {
    $('.select2-modal').select2({
        theme: 'bootstrap-5',
        dropdownParent: $('#modalNãovoEquipamento'),
        width: '100%',
        placeholder: 'Escolha...'
    });
}

function ensureNãovoEquipClienteInput(clienteId) {
    if (!formNãovoEquipAjax) return;
    let hiddenInput = document.getElementById('nãovoEquipClienteId');
    if (!hiddenInput) {
        hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'cliente_id';
        hiddenInput.id = 'nãovoEquipClienteId';
        formNãovoEquipAjax.appendChild(hiddenInput);
    }
    hiddenInput.value = clienteId || '';
}

function setEquipamentoEditButtonState() {
    if (!btnEditarEquip) return;
    const equipId = document.getElementById('equipamentoSelect')?.value || '';
    const hasEquipamento = Boolean(String(equipId).trim());
    btnEditarEquip.classList.toggle('d-nãone', !hasEquipamento);
}

function syncNãovoEquipFotosInput() {
    if (!nãovoEquipFoto) return;
    nãovoEquipFoto.files = nãovoEquipFotosDataTransfer.files;
}

function resetNãovoEquipPreview() {
    nãovoEquipFotosDataTransfer.itemês.clear();
    nãovoEquipFotoCropQueue = [];
    if (nãovoEquipFoto) {
        nãovoEquipFoto.value = '';
        syncNãovoEquipFotosInput();
    }
    renderNãovoEquipFotosNãovas();
    const fotoVazia = document.getElementById('fotoVaziaOS');
    if (fotoVazia) fotoVazia.style.display = (modalEquipExistingFotos.length || nãovoEquipFotosDataTransfer.files.length) ? 'nãone' : 'block';
}

function getTotalModalEquipFotos() {
    return (modalEquipExistingFotos?.length || 0) + (nãovoEquipFotosDataTransfer?.files?.length || 0);
}

function renderNãovoEquipFotosNãovas() {
    if (!previewDiv || !nãovoEquipFotosNãovasList) return;
    const files = Array.from(nãovoEquipFotosDataTransfer.files || []);
    nãovoEquipFotosNãovasList.innerHTML = '';

    if (!files.length) {
        previewDiv.style.display = 'nãone';
        const fotoVazia = document.getElementById('fotoVaziaOS');
        if (fotoVazia) fotoVazia.style.display = modalEquipExistingFotos.length ? 'nãone' : 'block';
        return;
    }

    previewDiv.style.display = 'block';
    const fotoVazia = document.getElementById('fotoVaziaOS');
    if (fotoVazia) fotoVazia.style.display = 'nãone';

    files.forEach((file, index) => {
        const objectUrl = URL.createObjectURL(file);
        const isPrincipal = index === 0 && !modalEquipExistingFotos.sãome(f => Number(f.is_principal) === 1);
        const thumb = document.createElement('div');
        thumb.className = 'position-relative d-inline-block shadow rounded border p-1 bg-white';
        thumb.style.cssText = `width:96px;height:96px;border-color:${isPrincipal ? 'var(--primary)' : 'rgba(0,0,0,.1)'};`;
        thumb.innerHTML = `
            <img src="${objectUrl}" class="w-100 h-100" style="object-fit:cover; border-radius:4px;" alt="Nãova foto do equipamento">
            ${isPrincipal ? '<span class="badge text-bg-primary position-absãolute top-0 start-0 m-1" style="font-size:0.55rem;">Principal</span>' : ''}
            <button type="button" class="btn btn-danger btn-sm position-absãolute top-0 end-0 m-1 p-1 py-0 shadow btn-remover-foto-nãova-equip" data-index="${index}" style="border-radius:50%;">
                <i class="bi bi-x"></i>
            </button>
        `;
        const img = thumb.querySelector('img');
        img?.addEventListener('load', () => URL.revokeObjectURL(objectUrl), { once: true });
        nãovoEquipFotosNãovasList.appendChild(thumb);
    });
}

function processNextNãovoEquipCrop() {
    if (!nãovoEquipFotoCropQueue.length) {
        hideModalSafe(modalCrop, '#modalCropEquip');
        return;
    }
    const nextFile = nãovoEquipFotoCropQueue.shift();
    const reader = new FileReader();
    reader.onload = e => openCropper(e.target.result, { type: 'equipamento' });
    reader.onerror = () => processNextNãovoEquipCrop();
    reader.readAsDataURL(nextFile);
}

function queueNãovoEquipFotosFromFiles(files) {
    const incoming = Array.from(files || []).filter(file => (file.type || '').startsWith('image/'));
    if (!incoming.length) return;

    const available = nãovoEquipFotosMaxFiles - getTotalModalEquipFotos();
    if (available <= 0) {
        showWarningDialog(`Vocêe pode manter ate ${nãovoEquipFotosMaxFiles} fotos por equipamento.`);
        return;
    }

    nãovoEquipFotoCropQueue = incoming.slice(0, available);
    if (incoming.length > available) {
        showWarningDialog(`Sãomente ${available} foto(s) cabem agora (limite de ${nãovoEquipFotosMaxFiles} por equipamento).`);
    }

    processNextNãovoEquipCrop();
}

function renderModalEquipFotosExistentes(fotos = []) {
    if (!modalEquipFotosExistentesWrap || !modalEquipFotosExistentes) return;

    modalEquipFotosExistentes.innerHTML = '';
    const lista = Array.isArray(fotos) ? fotos : [];
    modalEquipExistingFotos = lista;
    if (equipamentoModalMode !== 'edit' || !lista.length) {
        modalEquipFotosExistentesWrap.classList.add('d-nãone');
        return;
    }

    modalEquipFotosExistentesWrap.classList.remove('d-nãone');
    lista.forEach((foto, index) => {
        const fotoUrl = withFotoVersion(foto.url || '', modalEquipFotosVersion);
        const isPrincipal = Number(foto.is_principal) === 1 || index === 0;
        const wrapper = document.createElement('div');
        wrapper.className = 'position-relative border rounded overflow-hidden';
        wrapper.style.cssText = `width:84px;height:84px;border-color:${isPrincipal ? 'var(--primary)' : 'rgba(255,255,255,0.15)'};`;

        const thumb = document.createElement('a');
        thumb.href = 'javascript:void(0)';
        thumb.className = 'd-block w-100 h-100';
        thumb.style.cssText = 'cursãor:zoom-in;';
        thumb.setAttribute('data-bs-toggle', 'modal');
        thumb.setAttribute('data-bs-target', '#imageModal');
        thumb.setAttribute('data-img-src', fotoUrl);

        thumb.innerHTML = `
            <img src="${fotoUrl}" class="w-100 h-100 object-fit-cover" alt="Foto do equipamento">
            ${isPrincipal ? '<span class="badge text-bg-primary position-absãolute top-0 start-0 m-1" style="font-size:0.55rem;">Principal</span>' : ''}
        `;

        wrapper.appendChild(thumb);

        const fotoId = Number(foto.id || 0);
        if (fotoId > 0) {
            if (!isPrincipal) {
                const btnPrincipal = document.createElement('button');
                btnPrincipal.type = 'button';
                btnPrincipal.className = 'btn btn-sm btn-primary position-absãolute bottom-0 end-0 m-1 py-0 px-1 btn-definir-principal-foto-existente-equip';
                btnPrincipal.dataset.fotoId = String(fotoId);
                btnPrincipal.title = 'Definir como principal';
                btnPrincipal.innerHTML = '<i class="bi bi-star"></i>';
                wrapper.appendChild(btnPrincipal);
            }

            const btnDelete = document.createElement('button');
            btnDelete.type = 'button';
            btnDelete.className = 'btn btn-sm btn-danger position-absãolute top-0 end-0 m-1 py-0 px-1 btn-remover-foto-existente-equip';
            btnDelete.dataset.fotoId = String(fotoId);
            btnDelete.title = 'Excluir foto';
            btnDelete.innerHTML = '<i class="bi bi-trash"></i>';
            wrapper.appendChild(btnDelete);
        }

        modalEquipFotosExistentes.appendChild(wrapper);
    });
}

async function reloadModalEquipFotosExistentes() {
    if (!equipamentoEditId) return;
    try {
        const response = await fetch(`${BASE_URL}equipamentos/fotos/${equipamentoEditId}?v=${Date.nãow()}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const fotos = await response.jsãon();
        bumpModalEquipFotosVersion();
        bumpEquipamentoFotosVersion();
        renderModalEquipFotosExistentes(fotos);
        renderNãovoEquipFotosNãovas();

        const selectedEq = getSelectedEquipamentoData();
        if (selectedEq && String(selectedEq.id || '') === String(equipamentoEditId)) {
            carregarFotosEquipamento(equipamentoEditId, {
                marca: selectedEq.marca_nãome || selectedEq.marca || '',
                modelo: selectedEq.modelo_nãome || selectedEq.modelo || '',
                serie: selectedEq.numero_serie || selectedEq.serie || '',
                tipo: selectedEq.tipo_nãome || selectedEq.tipo || '',
                cor: selectedEq.cor || '',
                cor_hex: selectedEq.cor_hex || ''
            }, fotos);
        }
    } catch (_) {
        showWarningDialog('Nao foi possivel atualizar a lista de fotos do equipamento.', 'Falha ao atualizar');
    }
}

function setNãovoEquipModalMode(mode) {
    equipamentoModalMode = mode === 'edit' ? 'edit' : 'create';
    if (equipamentoModalMode === 'edit') {
        if (labelModalNãovoEquip) {
            labelModalNãovoEquip.innerHTML = '<i class="bi bi-pencil-square text-primary me-2"></i>Editar Equipamento';
        }
        if (btnSalvarNãovoEquip) {
            btnSalvarNãovoEquip.innerHTML = '<i class="bi bi-check2-circle me-1"></i>Salvar Altera��es';
        }
        return;
    }
    equipamentoEditId = null;
    if (labelModalNãovoEquip) {
        labelModalNãovoEquip.innerHTML = '<i class="bi bi-plus-circle text-warning me-2"></i>Cadastrar Nãovo Equipamento';
    }
    if (btnSalvarNãovoEquip) {
        btnSalvarNãovoEquip.innerHTML = '<i class="bi bi-check-lg me-1"></i>Cadastrar Equipamento';
    }
    renderModalEquipFotosExistentes([]);
}
setEquipamentoEditButtonState();

function resetNãovoEquipModalForm() {
    if (!formNãovoEquipAjax) return;
    formNãovoEquipAjax.reset();
    $('#nãovoEquipModeloNãomeExt').val('');
    $('#nãovoEquipModelo').html('<option value="">Modelo...</option>');
    $('#nãovoEquipMarca').val('').trigger('change');
    $('#nãovoEquipTipo').val('');
    updateColorUIOS('#1A1A1A', 'Preto');
    resetNãovoEquipPreview();
    renderModalEquipFotosExistentes([]);
    const errors = document.getElementById('modalEquipErrors');
    if (errors) {
        errors.classList.add('d-nãone');
        errors.innerHTML = '';
    }
}

function fillNãovoEquipModalFromData(eq) {
    if (!eq || !formNãovoEquipAjax) return;
    const clienteAtual = document.getElementById('clienteOsSelect')?.value || '';
    ensureNãovoEquipClienteInput(eq.cliente_id || clienteAtual);

    $('#nãovoEquipTipo').val(eq.tipo_id ? String(eq.tipo_id) : '');
    $('#nãovoEquipMarca').val(eq.marca_id ? String(eq.marca_id) : '').trigger('change');
    initModeloSelect2();

    setTimeout(() => {
        const modeloSelect = $('#nãovoEquipModelo');
        const modeloId = eq.modelo_id ? String(eq.modelo_id) : '';
        const modeloNãome = eq.modelo_nãome || eq.modelo || '';
        if (modeloId) {
            if (!modeloSelect.find(`option[value="${modeloId}"]`).length) {
                modeloSelect.append(new Option(modeloNãome || 'Modelo', modeloId, false, false));
            }
            modeloSelect.val(modeloId).trigger('change');
        } else if (modeloNãome) {
            modeloSelect.val(modeloNãome).trigger('change');
        }
    }, 120);

    const numeroSerie = formNãovoEquipAjax.querySelector('input[name="numero_serie"]');
    const senhaAcessão = formNãovoEquipAjax.querySelector('input[name="senha_acessão"]');
    const estadoFisico = formNãovoEquipAjax.querySelector('textarea[name="estado_fisico"]');
    const acessãoriosEquip = formNãovoEquipAjax.querySelector('textarea[name="acessãorios"]');

    if (numeroSerie) numeroSerie.value = eq.numero_serie || '';
    if (senhaAcessão) senhaAcessão.value = eq.senha_acessão || '';
    if (estadoFisico) estadoFisico.value = eq.estado_fisico || '';
    if (acessãoriosEquip) acessãoriosEquip.value = eq.acessãorios || '';

    updateColorUIOS(eq.cor_hex || '#1A1A1A', eq.cor || 'Preto');
    resetNãovoEquipPreview();

    fetch(`${BASE_URL}equipamentos/fotos/${eq.id}?v=${Date.nãow()}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.jsãon())
    .then(fotos => {
        bumpModalEquipFotosVersion();
        renderModalEquipFotosExistentes(fotos);
        renderNãovoEquipFotosNãovas();
        if (!Array.isArray(fotos) || !fotos.length) return;
        const principal = fotos.find(f => Number(f.is_principal) === 1) || fotos[0];
        if (!principal?.url) return;
        const fotoVazia = document.getElementById('fotoVaziaOS');
        if (fotoVazia) fotoVazia.style.display = 'nãone';
    })
    .catch(() => {
        renderModalEquipFotosExistentes([]);
        renderNãovoEquipFotosNãovas();
    });
}

function getSelectedEquipamentoData() {
    const equipSelect = document.getElementById('equipamentoSelect');
    const selectedId = equipSelect?.value ? String(equipSelect.value) : '';
    if (!selectedId) return null;

    if (osEquipamentosCache[selectedId]) {
        return osEquipamentosCache[selectedId];
    }

    const opt = equipSelect?.options?.[equipSelect.selectedIndex];
    if (!opt) return null;
    return {
        id: selectedId,
        cliente_id: opt.dataset.cliente_id || document.getElementById('clienteOsSelect')?.value || '',
        tipo_id: opt.dataset.tipo || '',
        marca_id: opt.dataset.marca_id || '',
        modelo_id: opt.dataset.modelo_id || '',
        marca_nãome: opt.dataset.marca || '',
        modelo_nãome: opt.dataset.modelo || '',
        tipo_nãome: opt.dataset.tipo_nãome || '',
        numero_serie: opt.dataset.serie || '',
        cor: opt.dataset.cor || '',
        cor_hex: opt.dataset.cor_hex || '',
        senha_acessão: opt.dataset.senha_acessão || '',
        estado_fisico: opt.dataset.estado_fisico || '',
        acessãorios: opt.dataset.acessãorios || ''
    };
}

function openNãovoEquipamentoModal() {
    const clienteId = document.getElementById('clienteOsSelect')?.value || '';
    if (!clienteId) {
        showWarningDialog('Selecione um cliente primeiro para cadastrar o equipamento.');
        return;
    }
    setNãovoEquipModalMode('create');
    resetNãovoEquipModalForm();
    ensureNãovoEquipClienteInput(clienteId);
    ensureModalEquipSelect2();
    initModeloSelect2();
    modalNãovoEquipamento?.show();
}

function openEditarEquipamentoModal() {
    const selectedEq = getSelectedEquipamentoData();
    if (!selectedEq || !selectedEq.id) {
        showWarningDialog('Selecione um equipamento para editar.');
        return;
    }
    equipamentoEditId = selectedEq.id;
    setNãovoEquipModalMode('edit');
    resetNãovoEquipModalForm();
    ensureModalEquipSelect2();
    initModeloSelect2();
    fillNãovoEquipModalFromData(selectedEq);
    modalNãovoEquipamento?.show();
}

btnNãovoEquip?.addEventListener('click', openNãovoEquipamentoModal);
btnEditarEquip?.addEventListener('click', openEditarEquipamentoModal);
modalNãovoEquipamentoEl?.addEventListener('hidden.bs.modal', () => {
    setNãovoEquipModalMode('create');
    resetNãovoEquipModalForm();
});

// ??? Cadastro R�pido de Marcas e Modelos (Dentro da OS) ????????????????????
const modalNãovaMarca = new bootstrap.Modal(document.getElementById('modalNãovaMarcaOS'));
const modalNãovoModelo = new bootstrap.Modal(document.getElementById('modalNãovoModeloOS'));

document.getElementById('btnNãovaMarcaOS')?.addEventListener('click', () => modalNãovaMarca.show());
document.getElementById('btnNãovoModeloOS')?.addEventListener('click', () => {
    const marcaId = $('#nãovoEquipMarca').val();
    if (!marcaId) { showWarningDialog('Selecione uma marca primeiro!'); return; }
    
    // Mostra o nãome da marca não modal para confer�ncia
    const marcaNãome = $('#nãovoEquipMarca option:selected').text();
    document.getElementById('displayMarcaOS').value = marcaNãome;
    
    modalNãovoModelo.show();
});

document.getElementById('btnSalvarMarcaOS')?.addEventListener('click', function() {
    const nãome = document.getElementById('inputNãovaMarcaOS').value.trim();
    if (!nãome) return;

    this.disabled = true;
    const fd = new FormData();
    fd.append('nãome', nãome);
    fd.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

    fetch(`${BASE_URL}equipamentosmarcas/salvar_ajax`, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
    .then(r => r.jsãon())
    .then(res => {
        if (res.success) {
            const opt = new Option(res.nãome, res.id, true, true);
            $('#nãovoEquipMarca').append(opt).trigger('change');
            modalNãovaMarca.hide();
            document.getElementById('inputNãovaMarcaOS').value = '';
        } else {
            const err = document.getElementById('errorNãovaMarcaOS');
            err.innerText = res.message;
            err.classList.remove('d-nãone');
        }
    })
    .finally(() => this.disabled = false);
});

document.getElementById('btnSalvarModeloOS')?.addEventListener('click', function() {
    const nãome = document.getElementById('inputNãovoModeloOS').value.trim();
    const marcaId = $('#nãovoEquipMarca').val();
    if (!nãome || !marcaId) return;

    this.disabled = true;
    const fd = new FormData();
    fd.append('nãome', nãome);
    fd.append('marca_id', marcaId);
    fd.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

    fetch(`${BASE_URL}equipamentosmodelos/salvar_ajax`, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
    .then(r => r.jsãon())
    .then(res => {
        if (res.success) {
            const opt = new Option(res.nãome, res.id, true, true);
            $('#nãovoEquipModelo').append(opt).trigger('change');
            modalNãovoModelo.hide();
            document.getElementById('inputNãovoModeloOS').value = '';
            document.getElementById('sugestoesNãovoModeloOS').classList.add('d-nãone');
        } else {
            const err = document.getElementById('errorNãovoModeloOS');
            err.innerText = res.message;
            err.classList.remove('d-nãone');
        }
    })
    .finally(() => this.disabled = false);
});

// ??? Autocomplete inteligente não modal "Nãovo Modelo" ?????????????????????????
(function() {
    let debounceTimerModelo = null;
    const inputModelo    = document.getElementById('inputNãovoModeloOS');
    const sugestoesBox   = document.getElementById('sugestoesNãovoModeloOS');
    const spinnerModelo  = document.getElementById('spinnerNãovoModeloOS');
    const errorModelo    = document.getElementById('errorNãovoModeloOS');

    if (!inputModelo) return;

    function renderSugestoes(groups) {
        sugestoesBox.innerHTML = '';
        let total = 0;

        groups.forEach(group => {
            if (!group.children || group.children.length === 0) return;

            // Cabe�alho do grupo
            const header = document.createElement('div');
            header.className = 'list-group-item list-group-item-secondary py-1 px-3';
            header.style.cssText = 'font-size:0.7rem; font-weight:700; letter-spacing:0.5px; text-transform:uppercase; pointer-events:nãone;';
            const icon = group.text.includes('Cadastrados') ? '?' : '?';
            header.textContent = icon + ' ' + group.text.replace(/^[??] /, '');
            sugestoesBox.appendChild(header);

            // Itens do grupo
            group.children.forEach(item => {
                let parts = [];
                if (item.marca) parts.push(item.marca);
                if (item.tipo) parts.push(item.tipo);
                let subtitle = parts.length > 0 ? `<div style="font-size:0.75rem; color:#6c757d; margin-top:-2px;">(${parts.join(' - ')})</div>` : '';

                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'list-group-item list-group-item-action py-2 px-3 d-flex align-itemês-start gap-2';
                btn.style.fontSize = '0.88rem';
                btn.innerHTML = `
                    <div class="mt-1"><i class="bi bi-${group.text.includes('Cadastrados') ? 'check-circle text-success' : 'globe2 text-info'}" style="font-size:0.8rem;"></i></div>
                    <div>
                        <strong style="color:var(--bs-heading-color);">${item.text}</strong>
                        ${subtitle}
                    </div>
                `;
                btn.addEventListener('click', () => {
                    inputModelo.value = item.text;
                    sugestoesBox.classList.add('d-nãone');
                    inputModelo.focus();
                    el.setAttribute('data-bs-toggle', 'modal');
                    el.setAttribute('data-bs-target', '#imageModal');
                    el.setAttribute('data-img-src', f.url);
                    el.addEventListener('click', function() {
                        const modal = document.getElementById('imageModal');
                        const modalImg = modal.querySelector('#modalImagePreview');
                        modalImg.src = f.url;
                    });
                });
                sugestoesBox.appendChild(btn);
                total++;
            });
        });

        if (total > 0) {
            sugestoesBox.classList.remove('d-nãone');
        } else {
            sugestoesBox.innerHTML = '<div class="list-group-item text-muted small py-2 px-3"><i class="bi bi-info-circle me-1"></i>Nenhuma sugest�o. Digite e salve manualmente.</div>';
            sugestoesBox.classList.remove('d-nãone');
        }
    }

    inputModelo.addEventListener('input', function() {
        const q = this.value.trim();
        clearTimeout(debounceTimerModelo);
        errorModelo.classList.add('d-nãone');

        if (q.length < 3) {
            sugestoesBox.classList.add('d-nãone');
            spinnerModelo.classList.add('d-nãone');
            return;
        }

        spinnerModelo.classList.remove('d-nãone');
        sugestoesBox.classList.add('d-nãone');

        debounceTimerModelo = setTimeout(() => {
            const marcaId   = $('#nãovoEquipMarca').val();
            const marcaNãome = $('#nãovoEquipMarca option:selected').text().trim();
            const tipoNãome  = $('#nãovoEquipTipo option:selected').text().trim();

            const paramês = new URLSearchParamês({
                q:        q,
                marca_id: marcaId || '',
                marca:    marcaNãome || '',
                tipo:     tipoNãome !== 'Selecione o Tipo...' ? tipoNãome : ''
            });

            fetch(`${BASE_URL}api/modelos/buscar?${paramês}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.jsãon())
            .then(data => {
                spinnerModelo.classList.add('d-nãone');
                if (data.results && data.results.length > 0) {
                    renderSugestoes(data.results);
                } else {
                    sugestoesBox.classList.add('d-nãone');
                }
            })
            .catch(() => spinnerModelo.classList.add('d-nãone'));
        }, 400);
    });

    // Fecha dropdown ao clicar fora
    document.addEventListener('click', e => {
        if (!inputModelo.contains(e.target) && !sugestoesBox.contains(e.target)) {
            sugestoesBox.classList.add('d-nãone');
        }
    });

    // Limpa ao fechar o modal
    document.getElementById('modalNãovoModeloOS')?.addEventListener('hidden.bs.modal', () => {
        inputModelo.value = '';
        sugestoesBox.classList.add('d-nãone');
        errorModelo.classList.add('d-nãone');
    });
})();


// L�gica de Cores não Modal (Igual ao cadastro de equipamentos)
// ???????????????????????????????????????????????????????????
// SELETOR DE COR PROFISSIONAL (OS Modal)
// ???????????????????????????????????????????????????????????

const PROFESSIONAL_COLORS_OS = [
    { category: 'Neutras (Preto, Branco, Cinza)', colors: [
        { hex: '#000000', name: 'Preto' }, { hex: '#2F4F4F', name: 'Grafite' }, { hex: '#41464D', name: 'Graphite' },
        { hex: '#5C5B57', name: 'Titanium' }, { hex: '#696969', name: 'Cinza Escuro' }, { hex: '#BEBEBE', name: 'Cinza' },
        { hex: '#FFFFFF', name: 'Branco' }, { hex: '#F8F8FF', name: 'Branco Gelo' }, { hex: '#FFFFF0', name: 'Marfim' },
    ]},
    { category: 'Azuis e Marinhos', colors: [
        { hex: '#191970', name: 'Azul Meia-Nãoite' }, { hex: '#000080', name: 'Azul Marinho' }, { hex: '#0000FF', name: 'Azul Puro' },
        { hex: '#4169E1', name: 'Azul Real' }, { hex: '#1E90FF', name: 'Azul C�u' }, { hex: '#87CEEB', name: 'Azul Celeste' },
        { hex: '#5F9EA0', name: 'Azul Petr�leo' },
    ]},
    { category: 'Verdes e Mentas', colors: [
        { hex: '#006400', name: 'Verde Escuro' }, { hex: '#2E8B57', name: 'Verde Floresta' }, { hex: '#008000', name: 'Verde Puro' },
        { hex: '#32CD32', name: 'Verde Vivo' }, { hex: '#98FB98', name: 'Verde Claro' }, { hex: '#F5FFFA', name: 'Verde Menta' },
    ]},
    { category: 'Vermelhos e Corais', colors: [
        { hex: '#8B0000', name: 'Vermelho Escuro' }, { hex: '#B22222', name: 'Vermelho Tijolo' }, { hex: '#FF0000', name: 'Vermelho' },
        { hex: '#FF4500', name: 'V. Alaranjado' }, { hex: '#FF6347', name: 'Tomate' }, { hex: '#FFA500', name: 'Laranja' },
    ]},
    { category: 'Amarelos e Dourados', colors: [
        { hex: '#DAA520', name: 'Dourado' }, { hex: '#FFD700', name: 'Dourado Vivo' }, { hex: '#FFFF00', name: 'Amarelo' },
        { hex: '#F5F5DC', name: 'Bege' }, { hex: '#FFF8DC', name: 'Marfim' },
    ]},
    { category: 'Roxos, Pinks e Lil�s', colors: [
        { hex: '#4B0082', name: '�ndigo' }, { hex: '#2D1B69', name: 'Violeta' }, { hex: '#800080', name: 'Roxo Puro' },
        { hex: '#DA70D6', name: 'Lil�s' }, { hex: '#FF1493', name: 'Pink' }, { hex: '#AA336A', name: 'Rose Gold' },
    ]},
];

function hexToRgbOS(hex) {
    var result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    return result ? { r: parseInt(result[1], 16), g: parseInt(result[2], 16), b: parseInt(result[3], 16) } : null;
}

function colorDistanceOS(hex1, hex2) {
    const a = hexToRgbOS(hex1), b = hexToRgbOS(hex2);
    if (!a || !b) return Infinity;
    return Math.sqrt(Math.pow(a.r - b.r, 2) + Math.pow(a.g - b.g, 2) + Math.pow(a.b - b.b, 2));
}

function getTextColorOS(hex) {
    const rgb = hexToRgbOS(hex);
    if (!rgb) return '#fff';
    const lum = (0.299 * rgb.r + 0.587 * rgb.g + 0.114 * rgb.b) / 255;
    return lum > 0.6 ? '#1a1a1a' : '#ffffff';
}

window.updateColorUIOS = function(hex, name) {
    const rgb = hexToRgbOS(hex);
    const rgbStr = rgb ? `${rgb.r},${rgb.g},${rgb.b}` : '';
    const textColor = getTextColorOS(hex);

    $('#corHexRealOS').val(hex);
    $('#corRgbRealOS').val(rgbStr);
    $('#corNãomeRealOS').val(name);

    $('#corHexPickerOS').val(hex);
    $('#corNãomeInputOS').val(name);

    const preview = document.getElementById('colorPreviewBoxOS');
    if (preview) {
        preview.style.background = hex;
        document.getElementById('colorPreviewHexOS').style.color = textColor;
        document.getElementById('colorPreviewHexOS').textContent = hex.toUpperCase();
        document.getElementById('colorPreviewNameOS').style.color = textColor === '#ffffff' ? 'rgba(255,255,255,0.7)' : 'rgba(0,0,0,0.5)';
        document.getElementById('colorPreviewNameOS').textContent = name;
    }

    // Similar colors
    let all = [];
    PROFESSIONAL_COLORS_OS.forEach(cat => cat.colors.forEach(c => all.push({ ...c, d: colorDistanceOS(hex, c.hex) })));
    const nearest = all.sãort((a,b) => a.d - b.d).slice(0, 6);
    
    const grid = document.getElementById('coresProximasGridOS');
    if (grid) {
        grid.innerHTML = '';
        nearest.forEach(c => {
            const b = document.createElement('button');
            b.type = 'button'; b.className = 'rounded-circle border';
            b.style.cssText = `width:24px;height:24px;background:${c.hex};cursãor:pointer;`;
            b.onclick = () => updateColorUIOS(c.hex, c.name);
            grid.appendChild(b);
        });
    }

    // Refresh Catalog Selection
    buildCatalogOS();
}

window.buildCatalogOS = function() {
    const catalog = document.getElementById('colorCatalogOS');
    if (!catalog) return;
    catalog.innerHTML = '';

    const accordionId = 'accordionColorFamiliesOS';
    const accordion = document.createElement('div');
    accordion.className = 'accordion accordion-flush custom-color-accordion';
    accordion.id = accordionId;

    PROFESSIONAL_COLORS_OS.forEach((cat, index) => {
        const itemId = `flush-collapse-os-${index}`;
        const headerId = `flush-heading-os-${index}`;

        const accordionItem = document.createElement('div');
        accordionItem.className = 'accordion-item bg-transparent border-bottom border-light';

        accordionItem.innerHTML = `
            <h2 class="accordion-header" id="${headerId}">
                <button class="accordion-button collapsed py-2 px-1 bg-transparent shadow-nãone fw-bold text-muted" type="button" data-bs-toggle="collapse" data-bs-target="#${itemId}" aria-expanded="false" aria-controls="${itemId}" style="font-size: 0.8rem;">
                    <i class="bi bi-circle-fill me-2" style="color: ${cat.colors[0].hex}; font-size: 0.8rem;"></i>
                    ${cat.category}
                </button>
            </h2>
            <div id="${itemId}" class="accordion-collapse collapse" aria-labelledby="${headerId}" data-bs-parent="#${accordionId}">
                <div class="accordion-body p-0 pb-2">
                    <div class="list-group list-group-flush rounded-3 overflow-hidden border">
                        ${cat.colors.map(c => {
                            const isSelected = $('#corHexRealOS').val().toUpperCase() === c.hex.toUpperCase();
                            return `
                                <button type="button" class="list-group-item list-group-item-action py-2 px-3 d-flex align-itemês-center gap-3 border-0 ${isSelected ? 'active bg-primary bg-opacity-10 text-primary fw-bold' : ''}" 
                                        onclick="updateColorUIOS('${c.hex}', '${c.name}')" style="font-size: 0.82rem;">
                                    <div class="rounded-circle shadow-sm border border-light" 
                                         style="width: 24px; height: 24px; background: ${c.hex}; flex-shrink: 0;"></div>
                                    <span class="flex-grow-1 text-start">${c.name}</span>
                                    <small class="text-muted font-monãospace opacity-50" style="font-size: 0.7rem;">${c.hex}</small>
                                </button>
                            `;
                        }).join('')}
                    </div>
                </div>
            </div>
        `;
        accordion.appendChild(accordionItem);
    });

    catalog.appendChild(accordion);
}

$('#corHexPickerOS').on('input', function() {
    const hex = this.value.toUpperCase();
    let best = null, minDist = Infinity;
    PROFESSIONAL_COLORS_OS.forEach(cat => cat.colors.forEach(c => {
        const d = colorDistanceOS(hex, c.hex);
        if (d < minDist) { minDist = d; best = c; }
    }));
    updateColorUIOS(hex, best ? best.name : hex);
});

$('#corNãomeInputOS').on('input', function() {
    $('#corNãomeRealOS').val(this.value);
});

// Init OS Color
buildCatalogOS();
updateColorUIOS('#1A1A1A', 'Preto');

// ??? L�GICA DE DETEC��O DE COR INTELIGENTE NA IMAGEM (OS Modal) ???????????????
const smartColorMapOS = {
    '#1C1C1E': 'Midnight',
    '#F2F2F4': 'Starlight',
    '#5C5B57': 'Titanium',
    '#41464D': 'Graphite',
    '#202020': 'Preto Phantom',
    '#E1E1E1': 'Prata',
    '#1A1A1A': 'Preto',
    '#FFFFFF': 'Branco',
    '#808080': 'Cinza',
    '#A0B8C8': 'Sierra Blue',
    '#51596A': 'Pacific Blue',
    '#B4C8B5': 'Alpine Green',
    '#FFC0CB': 'Rose Gold',
    '#FFD700': 'Dourado',
    '#FF0000': 'Vermelho',
    '#CCA01D': 'Mostarda',
    '#00FF00': 'Verde Vivo',
    '#24458D': 'Azul Escuro'
};

function rgbToHexStrOS(r, g, b) {
    return "#" + (1 << 24 | r << 16 | g << 8 | b).toString(16).slice(1).toUpperCase();
}

function detectDominantColorOS(sãourceCanvas) {
    try {
        const ctx = sãourceCanvas.getContext('2d', { willReadFrequently: true });
        const w = sãourceCanvas.width;
        const h = sãourceCanvas.height;
        const startX = Math.floor(w * 0.3);
        const startY = Math.floor(h * 0.3);
        const width = Math.floor(w * 0.4);
        const height = Math.floor(h * 0.4);
        
        if(width <= 0 || height <= 0) return;

        const imageData = ctx.getImageData(startX, startY, width, height);
        const data = imageData.data;
        const colorCounts = {};
        
        for (let i = 0; i < data.length; i += 16) {
            const r = Math.round(data[i] / 20) * 20;
            const g = Math.round(data[i+1] / 20) * 20;
            const b = Math.round(data[i+2] / 20) * 20;
            const a = data[i+3];
            
            if (a < 128) continue;
            
            let weight = 1;
            if ((r < 25 && g < 25 && b < 25) || (r > 235 && g > 235 && b > 235)) {
                weight = 0.05; 
            }
            
            const hex = rgbToHexStrOS(r, g, b);
            colorCounts[hex] = (colorCounts[hex] || 0) + weight;
        }
        
        let dominantHex = '#000000';
        let maxCount = 0;
        for (const hex in colorCounts) {
            if (colorCounts[hex] > maxCount) {
                maxCount = colorCounts[hex];
                dominantHex = hex;
            }
        }
        
        const dominantRgb = hexToRgbOS(dominantHex);
        if (!dominantRgb) return;

        let bestMatch = { hex: dominantHex, name: 'Persãonalizada' };
        let minDistance = Infinity;
        
        PROFESSIONAL_COLORS_OS.forEach(cat => cat.colors.forEach(c => {
            const d = colorDistanceOS(dominantHex, c.hex);
            if (d < minDistance) {
                minDistance = d;
                bestMatch = c;
            }
        }));
        
        $('#smartColorSwatchOS').css('background-color', dominantHex);
        $('#smartColorNameOS').text(bestMatch.name);
        $('#btnAcceptColorOS').data('hex', bestMatch.hex).data('name', bestMatch.name);
        $('#smartColorContainerOS').removeClass('d-nãone');

    } catch (e) {
        consãole.warn('Erro na detec��o de cor: ', e);
    }
}

// ??? L�GICA DE SENHA E ACESS�RIOS (MODAL OS) ???????????????????????
$(document).on('click', '.btn-senha-tipo-os', function() {
    const placeholder = $(this).data('placeholder');
    $('#inputSenhaAcessãoOS').attr('placeholder', placeholder).focus();
    $('.btn-senha-tipo-os').removeClass('btn-secondary text-white').addClass('btn-light border');
    $(this).removeClass('btn-light border').addClass('btn-secondary text-white');
});

$(document).on('click', '.btn-quick-acessãorio-os', function() {
    const value = $(this).text().replace('+ ', '').trim();
    const textarea = $('#textareaAcessãoriosOS');
    const current = textarea.val().trim();
    if (current.includes(value)) return;
    textarea.val(current === '' ? value : current + ', ' + value).focus();
    $(this).addClass('bg-primary text-white').delay(300).queue(function(next){
        $(this).removeClass('bg-primary text-white');
        next();
    });
});

$('#btnAcceptColorOS').click(function() {
    const hex = $(this).data('hex');
    const name = $(this).data('name');
    updateColorUIOS(hex, name);
    
    // Efeito
    const btn = $(this);
    const originalHtml = btn.html();
    btn.html('<i class="bi bi-check-all"></i> Aplicado!');
    btn.removeClass('text-primary').addClass('text-success');
    setTimeout(() => {
        btn.html(originalHtml);
        btn.removeClass('text-success').addClass('text-primary');
    }, 1500);
});

// ??? L�gica de C�mera, Galeria e Cropper ?????????????????????????????
const modalCameraEl  = document.getElementById('modalCamera');
const modalCropEl    = document.getElementById('modalCropEquip');

function hoistModalToBody(modalEl, zIndex = null) {
    if (!modalEl) return null;
    if (modalEl.parentElement !== document.body) {
        document.body.appendChild(modalEl);
    }
    if (zIndex !== null) {
        modalEl.style.zIndex = String(zIndex);
    }
    return modalEl;
}

hoistModalToBody(modalCameraEl, 2000);
hoistModalToBody(modalCropEl, 2100);

const modalCamera    = modalCameraEl ? bootstrap.Modal.getOrCreateInstance(modalCameraEl) : null;
const modalCrop      = modalCropEl ? bootstrap.Modal.getOrCreateInstance(modalCropEl) : null;
const modalCropTitle = document.getElementById('modalCropTitle');
const videoCamera    = document.getElementById('videoCamera');
const canvasCamera   = document.getElementById('canvasCamera');
const btnCapturar     = document.getElementById('btnCapturar');
const nãovoEquipFoto  = document.getElementById('nãovoEquipFoto');
const previewDiv     = document.getElementById('nãovoEquipFotoPreview');
const imgToCrop      = document.getElementById('imgToCrop');
let streamCamera     = null;
let cropper          = null;
let cropContext      = { type: 'equipamento' };
let cameraCaptureContext = { type: 'equipamento', entryId: null };
let cropperUnavailableWarned = false;
let cropModalFailureWarned = false;
let activeCropToken = 0;

function cleanupStuckModalArtifacts() {
    const openModals = Array.from(document.querySelectorAll('.modal.show'));
    const backdrops = Array.from(document.querySelectorAll('.modal-backdrop'));

    if (!openModals.length) {
        backdrops.forEach(el => el.remove());
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('padding-right');
        document.body.style.removeProperty('overflow');
        return;
    }

    const allowedBackdrops = openModals.length;
    if (backdrops.length > allowedBackdrops) {
        backdrops.slice(0, backdrops.length - allowedBackdrops).forEach(el => el.remove());
    }
}

function scheduleModalCleanup() {
    window.setTimeout(cleanupStuckModalArtifacts, 140);
}

function resetModalNãodeState(modalEl) {
    if (!modalEl) return;
    modalEl.classList.remove('show');
    modalEl.style.display = 'nãone';
    modalEl.setAttribute('aria-hidden', 'true');
    modalEl.removeAttribute('aria-modal');
}

function hideModalSafe(modalInstance, modalSelector) {
    try {
        const active = document.activeElement;
        const modalEl = modalSelector ? document.querySelector(modalSelector) : null;
        if (active && modalEl && modalEl.contains(active) && typeof active.blur === 'function') {
            active.blur();
        }
    } catch (_) {}
    modalInstance?.hide();
    scheduleModalCleanup();
}

function closeImageModalIfOpen() {
    const imageModalEl = document.getElementById('imageModal');
    if (!imageModalEl) {
        scheduleModalCleanup();
        return;
    }

    try {
        const active = document.activeElement;
        if (active && imageModalEl.contains(active) && typeof active.blur === 'function') {
            active.blur();
        }
    } catch (_) {}

    try {
        const imageModalInstance = bootstrap.Modal.getInstance(imageModalEl);
        imageModalInstance?.hide();
    } catch (err) {
        consãole.error('[OS Nãova] falha ao ocultar imageModal', err);
    }

    imageModalEl.classList.remove('show');
    imageModalEl.style.display = 'nãone';
    imageModalEl.setAttribute('aria-hidden', 'true');
    imageModalEl.removeAttribute('aria-modal');
    document.body.classList.remove('modal-open');
    document.body.style.removeProperty('padding-right');
    document.body.style.removeProperty('overflow');
    document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
    scheduleModalCleanup();
}

document.addEventListener('hidden.bs.modal', scheduleModalCleanup);

document.getElementById('btnAbrirGaleria')?.addEventListener('click', () => nãovoEquipFoto.click());

async function openCameraCapture(context = { type: 'equipamento', entryId: null }) {
    closeImageModalIfOpen();
    cameraCaptureContext = context;
    try {
        if (!navigator.mediaDevices?.getUserMedia) {
            consãole.error('[OS Nãova] navigator.mediaDevices.getUserMedia indisponivel');
            showWarningDialog('Este dispositivo ou navegador nao permite acessão a camera.', 'Camera indisponivel');
            return;
        }

        if (streamCamera) {
            streamCamera.getTracks().forEach(track => track.stop());
            streamCamera = null;
        }

        streamCamera = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
        if (videoCamera) {
            videoCamera.srcObject = streamCamera;
            const playPromise = videoCamera.play?.();
            if (playPromise && typeof playPromise.catch === 'function') {
                playPromise.catch(err => consãole.error('[OS Nãova] falha ao iniciar preview da camera', err));
            }
        }

        resetModalNãodeState(modalCameraEl);
        try {
            bootstrap.Modal.getInstance(modalCameraEl)?.dispose();
        } catch (error) {
            consãole.error('[OS Nãova] falha ao descartar instancia anterior do modal da camera', error);
        }

        const cameraModalInstance = modalCameraEl ? new bootstrap.Modal(modalCameraEl) : null;
        cameraModalInstance?.show();

        window.setTimeout(() => {
            if (!modalCameraEl) return;
            if (modalCameraEl.classList.contains('show') && window.getComputedStyle(modalCameraEl).display !== 'nãone') {
                return;
            }
            consãole.error('[OS Nãova] modal da camera nao abriu corretamente', {
                context,
                display: modalCameraEl.style.display,
                computedDisplay: window.getComputedStyle(modalCameraEl).display,
                classes: modalCameraEl.className
            });
            showWarningDialog('Nao foi possivel abrir a interface da camera. Tente pela galeria enquanto ajustamos este fluxo.', 'Falha ao abrir camera');
        }, 1000);
    } catch (err) {
        consãole.error('[OS Nãova] falha ao acessar camera', err);
        showWarningDialog('Nao foi possivel acessar a camera: ' + err.message, 'Camera indisponivel');
    }
}

document.getElementById('btnAbrirCamera')?.addEventListener('click', async () => {
    openCameraCapture({ type: 'equipamento', entryId: null });
});

modalCameraEl?.addEventListener('shown.bs.modal', () => {
    consãole.info('[OS Nãova] modal da camera exibido com sucessão');
});

modalCameraEl?.addEventListener('hidden.bs.modal', () => {
    if (streamCamera) {
        streamCamera.getTracks().forEach(track => track.stop());
        streamCamera = null;
    }
    if (videoCamera) {
        videoCamera.srcObject = null;
    }
    if (cameraCaptureContext.type === 'acessãorio' && cropContext.type !== 'acessãorio') {
        acessãorioCropEntryId = null;
        acessãorioCropQueue = [];
    }
    if (cameraCaptureContext.type === 'estado_fisico' && cropContext.type !== 'estado_fisico') {
        estadoFisicoCropEntryId = null;
        estadoFisicoCropQueue = [];
    }
    if (cameraCaptureContext.type === 'entrada' && cropContext.type !== 'entrada') {
        fotosEntradaCropQueue = [];
    }
    cameraCaptureContext = { type: 'equipamento', entryId: null };
    scheduleModalCleanup();
});

function setCropContext(context = { type: 'equipamento' }) {
    cropContext = context || { type: 'equipamento' };
    if (modalCropTitle) {
        if (cropContext.type === 'acessãorio') {
            modalCropTitle.innerHTML = '<i class="bi bi-crop text-warning me-2"></i>Ajustar Foto do Acessãorio';
        } else if (cropContext.type === 'estado_fisico') {
            modalCropTitle.innerHTML = '<i class="bi bi-crop text-warning me-2"></i>Ajustar Foto do Estado Fisico';
        } else if (cropContext.type === 'entrada') {
            modalCropTitle.innerHTML = '<i class="bi bi-crop text-warning me-2"></i>Ajustar Foto de Entrada da OS';
        } else {
            modalCropTitle.innerHTML = '<i class="bi bi-crop text-warning me-2"></i>Ajustar Foto do Equipamento';
        }
    }
}

function createCropperInstance() {
    if (cropper) {
        cropper.destroy();
        cropper = null;
    }
    cropper = new Cropper(imgToCrop, {
        viewMode: 1,
        dragMode: 'move',
        autoCropArea: 0.8,
        restore: false,
        guides: true,
        center: true,
        highlight: false,
        cropBoxMovable: true,
        cropBoxResizable: true,
        toggleDragModeOnDblclick: false,
    });
}

function isCropModalVisible() {
    if (!modalCropEl) return false;
    const dialog = modalCropEl.querySelector('.modal-dialog');
    if (!modalCropEl.classList.contains('show') || !dialog) return false;
    const rect = dialog.getBoundingClientRect();
    return rect.width > 0 && rect.height > 0;
}

function appendBlobToCurrentPhotoContext(blob, canvas) {
    if (!blob) {
        consãole.error('[OS Nãova] blob vazio ao anexar foto', cropContext);
        showWarningDialog('Nao foi possivel gerar a imagem selecionada.');
        return;
    }

    if (cropContext.type === 'acessãorio' && acessãorioCropEntryId) {
        const entryId = acessãorioCropEntryId;
        const dt = acessãoriosPhotos[entryId] || new DataTransfer();
        const fileName = `acessãorio_${Date.nãow()}_${Math.random().toString(36).slice(2, 6)}.jpg`;
        const file = new File([blob], fileName, { type: 'image/jpeg' });
        dt.itemês.add(file);
        acessãoriosPhotos[entryId] = dt;
        ensureAcessãorioFileInput(entryId);
        renderAcessãoriosList();
        scheduleDraftSave();

        if (acessãorioCropQueue.length > 0) {
            processNextAcessãorioCrop();
        } else {
            acessãorioCropEntryId = null;
            hideModalSafe(modalCrop, '#modalCropEquip');
        }
        return;
    }

    if (cropContext.type === 'estado_fisico' && estadoFisicoCropEntryId) {
        const entryId = estadoFisicoCropEntryId;
        const dt = estadoFisicoPhotos[entryId] || new DataTransfer();
        const fileName = `estado_fisico_${Date.nãow()}_${Math.random().toString(36).slice(2, 6)}.jpg`;
        const file = new File([blob], fileName, { type: 'image/jpeg' });
        dt.itemês.add(file);
        estadoFisicoPhotos[entryId] = dt;
        ensureEstadoFisicoFileInput(entryId);
        renderEstadoFisicoList();
        syncEstadoFisicoInput();
        scheduleDraftSave();

        if (estadoFisicoCropQueue.length > 0) {
            processNextEstadoFisicoCrop();
        } else {
            estadoFisicoCropEntryId = null;
            hideModalSafe(modalCrop, '#modalCropEquip');
        }
        return;
    }

    if (cropContext.type === 'entrada') {
        if (osDataTransfer.files.length >= osFotosMaxFiles) {
            showWarningDialog(`Vocêe pode enviar ate ${osFotosMaxFiles} fotos não total.`);
            hideModalSafe(modalCrop, '#modalCropEquip');
            return;
        }

        const fileName = `entrada_${Date.nãow()}_${Math.random().toString(36).slice(2, 6)}.jpg`;
        const file = new File([blob], fileName, { type: 'image/jpeg' });
        osDataTransfer.itemês.add(file);
        syncFotosEntradaInput();
        renderNewFotos();
        updatePhotoState();
        scheduleDraftSave();

        if (fotosEntradaCropQueue.length > 0) {
            processNextFotoEntradaCrop();
        } else {
            hideModalSafe(modalCrop, '#modalCropEquip');
        }
        return;
    }

    if (getTotalModalEquipFotos() >= nãovoEquipFotosMaxFiles) {
        showWarningDialog(`Vocêe pode manter ate ${nãovoEquipFotosMaxFiles} fotos por equipamento.`);
        hideModalSafe(modalCrop, '#modalCropEquip');
        return;
    }

    const fileName = `equipamento_${Date.nãow()}_${Math.random().toString(36).slice(2, 6)}.jpg`;
    const file = new File([blob], fileName, { type: 'image/jpeg' });
    nãovoEquipFotosDataTransfer.itemês.add(file);
    syncNãovoEquipFotosInput();
    detectDominantColorOS(canvas);
    renderNãovoEquipFotosNãovas();

    if (nãovoEquipFotoCropQueue.length > 0) {
        processNextNãovoEquipCrop();
        return;
    }

    hideModalSafe(modalCrop, '#modalCropEquip');
}

function fallbackCropperFromSãource(sãource, context, warnMessage = null) {
    setCropContext(context);

    if (warnMessage && !cropModalFailureWarned) {
        cropModalFailureWarned = true;
        showWarningDialog(warnMessage);
    }

    const img = new Image();
    img.onload = () => {
        const canvas = document.createElement('canvas');
        canvas.width = img.naturalWidth || img.width || 1024;
        canvas.height = img.naturalHeight || img.height || 1024;
        const ctx = canvas.getContext('2d');
        if (!ctx) {
            consãole.error('[OS Nãova] Canvas nao disponivel não fallback de imagem', context);
            showWarningDialog('Nao foi possivel processar a imagem selecionada.');
            hideModalSafe(modalCrop, '#modalCropEquip');
            return;
        }

        ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
        canvas.toBlob((blob) => appendBlobToCurrentPhotoContext(blob, canvas), 'image/jpeg', 0.9);
    };
    img.onerror = () => {
        consãole.error('[OS Nãova] erro ao carregar imagem não fallback visual', context);
        showWarningDialog('Nao foi possivel carregar a imagem para envio.');
        hideModalSafe(modalCrop, '#modalCropEquip');
    };
    img.src = sãource;
}

function openCropper(sãource, context = { type: 'equipamento' }) {
    closeImageModalIfOpen();
    const cropToken = ++activeCropToken;
    if (!sãource) {
        consãole.error('[OS Nãova] openCropper chamado sem sãource', context);
        return;
    }
    if (!imgToCrop || !modalCropEl) {
        consãole.error('[OS Nãova] elementos do editor de corte indisponiveis', { hasImage: Boolean(imgToCrop), hasModal: Boolean(modalCropEl), context });
        fallbackCropperFromSãource(sãource, context, 'Editor visual indisponivel não momento. A foto sera adicionada sem corte.');
        return;
    }
    if (typeof window.Cropper === 'undefined') {
        consãole.error('[OS Nãova] Cropper nao disponivel, ativando fallback');
        if (!cropperUnavailableWarned) {
            cropperUnavailableWarned = true;
            showWarningDialog('Editor de corte indisponivel. A foto sera adicionada sem corte.');
        }
        fallbackCropperFromSãource(sãource, context);
        return;
    }

    setCropContext(context);
    try {
        cropper?.destroy();
    } catch (error) {
        consãole.error('[OS Nãova] falha ao destruir cropper anterior', error);
    }
    cropper = null;
    imgToCrop.onload = null;
    imgToCrop.onerror = null;
    imgToCrop.src = sãource;
    imgToCrop.dataset.cropToken = String(cropToken);

    const cropModalInstance = bootstrap.Modal.getOrCreateInstance(modalCropEl);
    cropModalInstance.show();

    window.setTimeout(() => {
        if (cropToken !== activeCropToken) return;
        if (cropper || isCropModalVisible()) return;
        consãole.error('[OS Nãova] modal de crop nao abriu corretamente, ativando fallback', {
            context,
            display: modalCropEl.style.display,
            computedDisplay: window.getComputedStyle(modalCropEl).display,
            classes: modalCropEl.className
        });
        hideModalSafe(cropModalInstance, '#modalCropEquip');
        fallbackCropperFromSãource(sãource, context, 'Editor visual indisponivel não momento. A foto sera adicionada sem corte.');
    }, 1200);
}

document.getElementById('modalCropEquip').addEventListener('shown.bs.modal', () => {
    if (typeof window.Cropper === 'undefined') {
        return;
    }

    const initCropperWhenReady = () => {
        try {
            createCropperInstance();
        } catch (error) {
            consãole.error('[OS Nãova] falha ao inicializar cropper não modal visivel', error);
            hideModalSafe(bootstrap.Modal.getOrCreateInstance(modalCropEl), '#modalCropEquip');
            fallbackCropperFromSãource(imgToCrop?.src || '', cropContext, 'Falha não editor visual. A foto sera adicionada sem corte.');
        }
    };

    if (imgToCrop?.complete && Number(imgToCrop?.naturalWidth || 0) > 0) {
        initCropperWhenReady();
        return;
    }

    imgToCrop.onload = () => {
        imgToCrop.onload = null;
        initCropperWhenReady();
    };
    imgToCrop.onerror = (error) => {
        imgToCrop.onerror = null;
        consãole.error('[OS Nãova] falha ao carregar imagem para o cropper', error);
        hideModalSafe(bootstrap.Modal.getOrCreateInstance(modalCropEl), '#modalCropEquip');
        fallbackCropperFromSãource(imgToCrop?.src || '', cropContext, 'Falha ao carregar a imagem para corte. A foto sera adicionada sem corte.');
    };
});

document.getElementById('modalCropEquip').addEventListener('hidden.bs.modal', () => {
    if (cropper) {
        cropper.destroy();
        cropper = null;
    }
    if (cropContext.type === 'acessãorio') {
        acessãorioCropQueue = [];
        acessãorioCropEntryId = null;
    }
    if (cropContext.type === 'estado_fisico') {
        estadoFisicoCropQueue = [];
        estadoFisicoCropEntryId = null;
    }
    if (cropContext.type === 'entrada') {
        fotosEntradaCropQueue = [];
    }
    if (cropContext.type === 'equipamento') {
        nãovoEquipFotoCropQueue = [];
    }
    setCropContext({ type: 'equipamento' });
    scheduleModalCleanup();
});

document.getElementById('btnRotateLeft')?.addEventListener('click', () => {
    if (cropper && typeof cropper.rotate === 'function') {
        cropper.rotate(-90);
    }
});
document.getElementById('btnRotateRight')?.addEventListener('click', () => {
    if (cropper && typeof cropper.rotate === 'function') {
        cropper.rotate(90);
    }
});

btnCapturar?.addEventListener('click', () => {
    const context = canvasCamera.getContext('2d');
    if (!context || !videoCamera) {
        consãole.error('[OS Nãova] camera indisponivel para captura');
        showWarningDialog('Nao foi possivel capturar a foto pela camera.', 'Camera indisponivel');
        return;
    }
    canvasCamera.width  = videoCamera.videoWidth || 1280;
    canvasCamera.height = videoCamera.videoHeight || 720;
    context.drawImage(videoCamera, 0, 0, canvasCamera.width, canvasCamera.height);
    
    const dataUrl = canvasCamera.toDataURL('image/jpeg');
    hideModalSafe(bootstrap.Modal.getOrCreateInstance(modalCameraEl), '#modalCamera');
    if (cameraCaptureContext.type === 'acessãorio' && cameraCaptureContext.entryId) {
        acessãorioCropEntryId = cameraCaptureContext.entryId;
        acessãorioCropQueue = [];
        openCropper(dataUrl, { type: 'acessãorio' });
        return;
    }
    if (cameraCaptureContext.type === 'estado_fisico' && cameraCaptureContext.entryId) {
        estadoFisicoCropEntryId = cameraCaptureContext.entryId;
        estadoFisicoCropQueue = [];
        openCropper(dataUrl, { type: 'estado_fisico' });
        return;
    }
    if (cameraCaptureContext.type === 'entrada') {
        openCropper(dataUrl, { type: 'entrada' });
        return;
    }
    openCropper(dataUrl, { type: 'equipamento' });
});

document.getElementById('btnConfirmCrop')?.addEventListener('click', () => {
    if (!cropper) return;
    const canvas = cropper.getCroppedCanvas({
        width: 1024, // Limita o tamanho para n�o sãobrecarregar
        height: 1024,
        imageSmoothingEnabled: true,
        imageSmoothingQuality: 'high',
    });

    canvas.toBlob((blob) => {
        if (!blob) return;
        if (cropContext.type === 'acessãorio' && acessãorioCropEntryId) {
            const entryId = acessãorioCropEntryId;
            const dt = acessãoriosPhotos[entryId] || new DataTransfer();
            const fileName = `acessãorio_${Date.nãow()}_${Math.random().toString(36).slice(2, 6)}.jpg`;
            const file = new File([blob], fileName, { type: 'image/jpeg' });
            dt.itemês.add(file);
            acessãoriosPhotos[entryId] = dt;
            ensureAcessãorioFileInput(entryId);
            renderAcessãoriosList();
            scheduleDraftSave();

            if (acessãorioCropQueue.length > 0) {
                processNextAcessãorioCrop();
            } else {
                acessãorioCropEntryId = null;
                hideModalSafe(modalCrop, '#modalCropEquip');
            }
            return;
        }

        if (cropContext.type === 'estado_fisico' && estadoFisicoCropEntryId) {
            const entryId = estadoFisicoCropEntryId;
            const dt = estadoFisicoPhotos[entryId] || new DataTransfer();
            const fileName = `estado_fisico_${Date.nãow()}_${Math.random().toString(36).slice(2, 6)}.jpg`;
            const file = new File([blob], fileName, { type: 'image/jpeg' });
            dt.itemês.add(file);
            estadoFisicoPhotos[entryId] = dt;
            ensureEstadoFisicoFileInput(entryId);
            renderEstadoFisicoList();
            syncEstadoFisicoInput();
            scheduleDraftSave();

            if (estadoFisicoCropQueue.length > 0) {
                processNextEstadoFisicoCrop();
            } else {
                estadoFisicoCropEntryId = null;
                hideModalSafe(modalCrop, '#modalCropEquip');
            }
            return;
        }

    if (cropContext.type === 'entrada') {
        if (osDataTransfer.files.length >= osFotosMaxFiles) {
            showWarningDialog(`Vocêe pode enviar ate ${osFotosMaxFiles} fotos não total.`);
            hideModalSafe(modalCrop, '#modalCropEquip');
            return;
        }

            const fileName = `entrada_${Date.nãow()}_${Math.random().toString(36).slice(2, 6)}.jpg`;
            const file = new File([blob], fileName, { type: 'image/jpeg' });
            osDataTransfer.itemês.add(file);
            syncFotosEntradaInput();
            renderNewFotos();
            updatePhotoState();
            scheduleDraftSave();

            if (fotosEntradaCropQueue.length > 0) {
                processNextFotoEntradaCrop();
            } else {
                hideModalSafe(modalCrop, '#modalCropEquip');
            }
            return;
        }

        if (getTotalModalEquipFotos() >= nãovoEquipFotosMaxFiles) {
            showWarningDialog(`Vocêe pode manter ate ${nãovoEquipFotosMaxFiles} fotos por equipamento.`);
            hideModalSafe(modalCrop, '#modalCropEquip');
            return;
        }

        const fileName = `equipamento_${Date.nãow()}_${Math.random().toString(36).slice(2, 6)}.jpg`;
        const file = new File([blob], fileName, { type: 'image/jpeg' });
        nãovoEquipFotosDataTransfer.itemês.add(file);
        syncNãovoEquipFotosInput();
        
        detectDominantColorOS(canvas); // <--- Inicia a detec��o de cor autom�tica na OS

        // Preview Final
        renderNãovoEquipFotosNãovas();

        if (nãovoEquipFotoCropQueue.length > 0) {
            processNextNãovoEquipCrop();
            return;
        }
        hideModalSafe(modalCrop, '#modalCropEquip');
    }, 'image/jpeg', 0.9);
});

const btnConfirmCropOriginal = document.getElementById('btnConfirmCrop');
if (btnConfirmCropOriginal && btnConfirmCropOriginal.parentNãode) {
    const btnConfirmCropSafe = btnConfirmCropOriginal.cloneNãode(true);
    btnConfirmCropOriginal.parentNãode.replaceChild(btnConfirmCropSafe, btnConfirmCropOriginal);
    btnConfirmCropSafe.addEventListener('click', () => {
        if (!cropper) return;
        const canvas = cropper.getCroppedCanvas({
            width: 1024,
            height: 1024,
            imageSmoothingEnabled: true,
            imageSmoothingQuality: 'high',
        });

        if (!canvas) {
            consãole.error('[OS Nãova] getCroppedCanvas retornãou vazio', cropContext);
            showWarningDialog('Nao foi possivel preparar a imagem selecionada.');
            return;
        }

        canvas.toBlob((blob) => {
            appendBlobToCurrentPhotoContext(blob, canvas);
        }, 'image/jpeg', 0.9);
    });
}

nãovoEquipFoto?.addEventListener('change', function() {
    queueNãovoEquipFotosFromFiles(this.files);
    this.value = '';
});

async function deleteModalEquipFotoExistente(fotoId) {
    if (!fotoId) return;

    let confirmado = false;
    if (window.Swal && typeof window.Swal.fire === 'function') {
        const result = await Swal.fire({
            icon: 'warning',
            title: 'Excluir foto?',
            text: 'Essa foto sera removida permanentemente do equipamento.',
            showCancelButton: true,
            confirmButtonText: 'Sim, excluir',
            cancelButtonText: 'Cancelar',
            reverseButtons: true,
            customClass: { popup: 'glass-card' }
        });
        confirmado = !!result.isConfirmed;
    } else {
        confirmado = confirm('Essa foto sera removida permanentemente. Deseja continuar?');
    }

    if (!confirmado) return;

    const previousFotos = Array.isArray(modalEquipExistingFotos) ? [...modalEquipExistingFotos] : [];
    const semFoto = previousFotos.filter(f => Number(f.id || 0) !== Number(fotoId));
    if (semFoto.length && !semFoto.sãome(f => Number(f.is_principal) === 1)) {
        semFoto[0] = { ...semFoto[0], is_principal: 1 };
    }
    bumpModalEquipFotosVersion();
    renderModalEquipFotosExistentes(semFoto);
    renderNãovoEquipFotosNãovas();
    syncSidebarFotosFromModal(semFoto);

    const fd = new FormData();
    fd.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

    try {
        const response = await fetch(`${BASE_URL}equipamentos/deletar-foto/${fotoId}`, {
            method: 'POST',
            body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const res = await response.jsãon();
        if (!res || res.success !== true) {
            throw new Error(res?.message || 'Nao foi possivel excluir a foto.');
        }

        if (Array.isArray(res.fotos)) {
            bumpModalEquipFotosVersion();
            bumpEquipamentoFotosVersion();
            renderModalEquipFotosExistentes(res.fotos);
            renderNãovoEquipFotosNãovas();
            syncSidebarFotosFromModal(res.fotos);
        } else {
            await reloadModalEquipFotosExistentes();
        }
        if (window.Swal && typeof window.Swal.fire === 'function') {
            Swal.fire({
                icon: 'success',
                title: 'Foto excluida',
                timer: 1200,
                showConfirmButton: false,
                customClass: { popup: 'glass-card' }
            });
        }
    } catch (error) {
        bumpModalEquipFotosVersion();
        renderModalEquipFotosExistentes(previousFotos);
        renderNãovoEquipFotosNãovas();
        syncSidebarFotosFromModal(previousFotos);
        showWarningDialog(error?.message || 'Nao foi possivel excluir a foto.', 'Falha na exclusao');
    }
}

function syncSidebarFotosFromModal(fotos) {
    const selectedEq = getSelectedEquipamentoData();
    if (!selectedEq || !selectedEq.id) return;
    if (equipamentoEditId && String(selectedEq.id) !== String(equipamentoEditId)) return;

    bumpEquipamentoFotosVersion();
    carregarFotosEquipamento(selectedEq.id, {
        marca: selectedEq.marca_nãome || selectedEq.marca || '',
        modelo: selectedEq.modelo_nãome || selectedEq.modelo || '',
        serie: selectedEq.numero_serie || selectedEq.serie || '',
        tipo: selectedEq.tipo_nãome || selectedEq.tipo || '',
        cor: selectedEq.cor || '',
        cor_hex: selectedEq.cor_hex || ''
    }, fotos);
}

async function definirModalEquipFotoPrincipal(fotoId) {
    if (!fotoId) return;
    const fd = new FormData();
    fd.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

    try {
        const response = await fetch(`${BASE_URL}equipamentos/foto-principal/${fotoId}`, {
            method: 'POST',
            body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const res = await response.jsãon();
        if (!res || res.success !== true) {
            throw new Error(res?.message || 'Nao foi possivel definir a foto principal.');
        }

        const fotos = Array.isArray(res.fotos) ? res.fotos : [];
        bumpModalEquipFotosVersion();
        bumpEquipamentoFotosVersion();
        renderModalEquipFotosExistentes(fotos);
        renderNãovoEquipFotosNãovas();
        syncSidebarFotosFromModal(fotos);
    } catch (error) {
        showWarningDialog(error?.message || 'Nao foi possivel definir a foto principal.', 'Falha ao atualizar');
    }
}

document.addEventListener('click', async function(event) {
    const definirPrincipalBtn = event.target.closest('.btn-definir-principal-foto-existente-equip');
    if (definirPrincipalBtn) {
        event.preventDefault();
        event.stopPropagation();
        const fotoId = parseInt(definirPrincipalBtn.dataset.fotoId, 10);
        if (!Number.isNaN(fotoId)) {
            await definirModalEquipFotoPrincipal(fotoId);
        }
        return;
    }

    const removeExistingFotoBtn = event.target.closest('.btn-remover-foto-existente-equip');
    if (removeExistingFotoBtn) {
        event.preventDefault();
        event.stopPropagation();
        const fotoId = parseInt(removeExistingFotoBtn.dataset.fotoId, 10);
        if (!Number.isNaN(fotoId)) {
            await deleteModalEquipFotoExistente(fotoId);
        }
        return;
    }

    const removeNãovoEquipFotoBtn = event.target.closest('.btn-remover-foto-nãova-equip');
    if (!removeNãovoEquipFotoBtn) return;

    const index = parseInt(removeNãovoEquipFotoBtn.dataset.index, 10);
    if (Number.isNaN(index)) return;

    const nextDt = new DataTransfer();
    Array.from(nãovoEquipFotosDataTransfer.files).forEach((file, fileIndex) => {
        if (fileIndex !== index) nextDt.itemês.add(file);
    });

    nãovoEquipFotosDataTransfer.itemês.clear();
    Array.from(nextDt.files).forEach(file => nãovoEquipFotosDataTransfer.itemês.add(file));
    syncNãovoEquipFotosInput();
    renderNãovoEquipFotosNãovas();
});

// ??? Select2 H�brido: Modelos via API ??????????????????????????????????????
function initModeloSelect2() {
    var modeloSel = $('#nãovoEquipModelo');

    if (modeloSel.hasClass("select2-hidden-accessible")) {
        modeloSel.select2('destroy').off('change');
    }

    modeloSel.select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Busque ou selecione o modelo...',
        allowClear: true,
        dropdownParent: $('#modalNãovoEquipamento'),
        tags: true, // HABILITA EDI��O E NOVAS TAGS LIVRES
        createTag: function(paramês) {
            var term = $.trim(paramês.term);
            if (term === '') return null;
            return {
                id: term,
                text: term,
                newTag: true
            };
        },
        ajax: {
            url: BASE_URL + 'api/modelos/buscar',
            dataType: 'jsãon',
            delay: 250,
            data: function (paramês) {
                var tipoNãome = $('#nãovoEquipTipo option:selected').text().trim();
                return {
                    q:        paramês.term || '',
                    marca_id: $('#nãovoEquipMarca').val(),
                    marca:    $('#nãovoEquipMarca option:selected').text().trim(),
                    tipo:     tipoNãome !== 'Selecione o Tipo...' ? tipoNãome : ''
                };
            },
            processResults: function (data) {
                return data;
            },
            cache: true
        },
        minimumInputLength: 0,
        language: {
            inputTooShort: function (args) {
                var restante = args.minimum - args.input.length;
                return `Digite mais ${restante} caractere(s) para buscar...`;
            },
            searching: function() { return '<i class="bi bi-search me-1"></i> Buscando modelos...'; },
            nãoResults: function()  { return 'Nenhuma sugest�o encontrada. Use o bot�o <strong>+ Nãovo</strong> para cadastrar manualmente.'; },
            errorLoading: function() { return 'Erro ao consultar. Verifique sua conex�o.'; }
        },
        templateResult: function (data) {
            if (data.loading) return data.text;
            if (data.children) return data.text;
            
            if (data.newTag) {
                return $(`
                <div>
                    <strong class="d-block text-primary"><i class="bi bi-pencil-square me-1"></i> "${data.text}"</strong>
                    <small class="text-muted" style="font-size: 0.75rem;">Usar este nãome (edi��o manual)</small>
                </div>`);
            }

            var $container = $(`
                <div>
                    <strong class="d-block">${data.text}</strong>
                    ${(data.marca || data.tipo) ? `<small class="text-muted" style="font-size: 0.75rem;">(${[data.marca, data.tipo].filter(Boolean).join(' - ')})</small>` : ''}
                </div>
            `);
            return $container;
        },
        templateSelection: function (data) {
            return data.text;
        }
    }).on('select2:select', function (e) {
        var data = e.paramês.data;
        // Armazena o nãome real do modelo externão para auto-cadastro não backend
        // Se for newTag, j� vai salvar pelo pr�prio texto sendo o ID
        if (data.id && String(data.id).indexOf('EXT|') === 0) {
            $('#nãovoEquipModeloNãomeExt').val(data.text);
        } else {
            $('#nãovoEquipModeloNãomeExt').val('');
        }
    }).on('select2:open', function () {
        // Ação abrir, preenche a barra de pesquisa com o modelo atualmente selecionado
        var selecionado = $(this).select2('data')[0];
        if (selecionado && selecionado.id && selecionado.id !== '') {
            var searchField = document.querySelector('.select2-search__field');
            if (searchField && !searchField.value) {
                searchField.value = selecionado.text;
            }
        }
    });
}

// Reinicializa ao trocar marca
$('#nãovoEquipMarca').on('change', function() {
    var marcaId = $(this).val();
    if (marcaId) {
        initModeloSelect2();
    } else {
        if ($('#nãovoEquipModelo').hasClass("select2-hidden-accessible")) {
            $('#nãovoEquipModelo').select2('destroy').html('<option value="">Selecione a marca primeiro...</option>');
        }
    }
});

// Salvar equipamento via AJAX
document.getElementById('btnSalvarNãovoEquip')?.addEventListener('click', function() {
    const form = document.getElementById('formNãovoEquipAjax');
    const errors = document.getElementById('modalEquipErrors');
    if (!form || !errors) return;
    errors.classList.add('d-nãone');

    const formData = new FormData(form);

    const modeloId = $('#nãovoEquipModelo').val();
    if (modeloId && String(modeloId).startsWith('EXT|')) {
        formData.append('modelo_nãome_ext', $('#nãovoEquipModelo option:selected').text());
    }

    const isEditMode = equipamentoModalMode === 'edit' && !!equipamentoEditId;
    const url = isEditMode
        ? `${BASE_URL}equipamentos/atualizar-ajax/${equipamentoEditId}`
        : `${BASE_URL}equipamentos/salvar-ajax`;

    fetch(url, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.jsãon())
    .then(res => {
        if (res.status !== 'success') {
            errors.innerHTML = Object.values(res.errors || {}).join('<br>') || (res.message || 'Erro ao salvar equipamento.');
            errors.classList.remove('d-nãone');
            return;
        }

        const eq = res.equipamento || {};
        const eqId = String(eq.id || equipamentoEditId || '');
        if (!eqId) {
            throw new Error('Resposta sem identificador do equipamento.');
        }

        const nãome = `${eq.marca_nãome || ''} ${eq.modelo_nãome || ''} (${eq.tipo_nãome || ''})`.trim();
        const sel = document.getElementById('equipamentoSelect');
        if (!sel) return;

        let opt = Array.from(sel.options).find(o => String(o.value) === eqId);
        if (!opt) {
            opt = new Option(nãome, eqId, true, true);
            sel.appendChild(opt);
        }
        opt.text = nãome;
        opt.value = eqId;
        opt.dataset.tipo = eq.tipo_id || '';
        opt.dataset.marca = eq.marca_nãome || '';
        opt.dataset.modelo = eq.modelo_nãome || '';
        opt.dataset.serie = eq.numero_serie || '';
        opt.dataset.cor = eq.cor || '';
        opt.dataset.cor_hex = eq.cor_hex || '';
        opt.dataset.tipo_nãome = eq.tipo_nãome || '';
        opt.dataset.marca_id = eq.marca_id || '';
        opt.dataset.modelo_id = eq.modelo_id || '';
        opt.dataset.cliente_id = eq.cliente_id || '';
        opt.dataset.senha_acessão = eq.senha_acessão || '';
        opt.dataset.estado_fisico = eq.estado_fisico || '';
        opt.dataset.acessãorios = eq.acessãorios || '';

        osEquipamentosCache[eqId] = eq;
        const fotosAtualizadas = Array.isArray(res.fotos) ? res.fotos : null;

        if (typeof $.fn.select2 !== 'undefined' && $('#equipamentoSelect').hasClass('select2-hidden-accessible')) {
            $('#equipamentoSelect').val(eqId).trigger('change');
        } else {
            sel.value = eqId;
            _onEquipamentoChange(eqId, opt);
        }

        if (fotosAtualizadas) {
            bumpModalEquipFotosVersion();
            bumpEquipamentoFotosVersion();
            renderModalEquipFotosExistentes(fotosAtualizadas);
            renderNãovoEquipFotosNãovas();
        }

        carregarFotosEquipamento(eqId, {
            marca: eq.marca_nãome,
            modelo: eq.modelo_nãome,
            tipo: eq.tipo_nãome,
            cor: eq.cor,
            cor_hex: eq.cor_hex
        }, fotosAtualizadas);

        if (eq.tipo_id) carregarDefeitos(eq.tipo_id);

        bootstrap.Modal.getInstance(document.getElementById('modalNãovoEquipamento'))?.hide();

        if (window.Swal && typeof window.Swal.fire === 'function') {
            const hasWarning = Boolean(res.warning);
            Swal.fire({
                icon: hasWarning ? 'warning' : 'success',
                title: isEditMode ? 'Equipamento atualizado' : 'Equipamento cadastrado',
                text: hasWarning ? String(res.warning) : undefined,
                timer: hasWarning ? undefined : 1400,
                showConfirmButton: hasWarning,
                customClass: { popup: 'glass-card' }
            });
        }
    })
    .catch(() => {
        errors.innerHTML = 'Erro inesperado. Tente nãovamente.';
        errors.classList.remove('d-nãone');
    });
});

// ??? carregarDefeitos ??????????????????????????????????????????????????????
function carregarDefeitos(tipoId) {
    const section   = document.getElementById('defeitosSection');
    const container = document.getElementById('defeitosContainer');
    if (!section || !container) return;
    if (!tipoId) { section.style.display = 'nãone'; return; }

    container.innerHTML = '<div class="text-muted small"><i class="bi bi-hourglass-split me-1"></i>Carregando defeitos...</div>';
    section.style.display = '';

    const fd = new FormData();
    fd.append('tipo_id', tipoId);
    fd.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

    fetch(BASE_URL + 'equipamentosdefeitos/por-tipo', { method: 'POST', body: fd })
    .then(r => r.jsãon())
    .then(defeitos => {
        if (defeitos.length === 0) {
            container.innerHTML = `<span class="text-muted small"><i class="bi bi-info-circle me-1"></i>Nenhum defeito comum cadastrado para este tipo. <a href="${BASE_URL}equipamentosdefeitos" target="_blank">Cadastrar defeitos</a></span>`;
            return;
        }
        const hw = defeitos.filter(d => d.classificacao === 'hardware');
        const sw = defeitos.filter(d => d.classificacao === 'sãoftware');
        let html = '<div class="row g-2">';

        [{ list: hw, cls: 'text-danger', icon: 'bi-cpu', label: 'HARDWARE' },
         { list: sw, cls: 'text-primary', icon: 'bi-code-slash', label: 'SOFTWARE' }].forEach(({ list, cls, icon, label }) => {
            if (!list.length) return;
            html += `<div class="col-md-6"><p class="${cls} fw-bold mb-2 small"><i class="bi ${icon} me-1"></i>${label}</p>`;
            list.forEach(d => {
                const chk = defeitosSelecionados.includes(parseInt(d.id)) ? 'checked' : '';
                html += `<div class="form-check mb-1">
                    <input class="form-check-input chk-defeito-comum" type="checkbox" name="defeitos[]"
                           value="${d.id}" id="def_${d.id}" ${chk}
                           data-nãome="${d.nãome.replace(/"/g,'&quot;')}"
                           data-desc="${(d.descricao||'').replace(/"/g,'&quot;')}">
                    <label class="form-check-label d-flex align-itemês-center" for="def_${d.id}">
                        <div class="flex-grow-1">
                            <strong style="font-size:0.85rem;">${d.nãome}</strong>
                            ${d.descricao ? `<br><small class="text-muted">${d.descricao}</small>` : ''}
                        </div>
                        <button type="button" class="btn btn-sm btn-link p-0 text-warning mês-2 btn-ver-procedimentos-os"
                                data-id="${d.id}" data-nãome="${d.nãome.replace(/"/g,'&quot;')}" title="Ver Procedimentos">
                            <i class="bi bi-info-circle"></i>
                        </button>
                    </label>
                </div>`;
            });
            html += '</div>';
        });
        html += '</div>';
        container.innerHTML = html;

        // Auto-fill relato
        container.querySelectorAll('.chk-defeito-comum').forEach(chk => {
            chk.addEventListener('change', function() {
                const relato = document.getElementById('relatoClienteInput') || document.querySelector('textarea[name="relato_cliente"]');
                if (!relato) return;
                const nãome   = this.getAttribute('data-nãome');
                const desc   = this.getAttribute('data-desc');
                const tag    = `[DEFEITO: ${nãome}]${desc ? ' - ' + desc : ''}`;
                if (this.checked) {
                    if (relato.value.trim()) relato.value += '\n';
                    relato.value += tag;
                } else {
                    relato.value = relato.value.replace(tag, '').replace(/\n\n/g, '\n').trim();
                }
                updateResumo();
                scheduleDraftSave();
            });
        });

        // Bot�o de procedimentos
        container.querySelectorAll('.btn-ver-procedimentos-os').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                abrirProcedimentosViewOnly(this.dataset.id, this.dataset.nãome);
            });
        });
        _applyPendingDefeitos();
        updateResumo();
        scheduleDraftSave();
    })
    .catch(() => { container.innerHTML = '<span class="text-danger small">Erro ao carregar defeitos.</span>'; });
}

// ??? Modal de visualiza��o de procedimentos ???????????????????????????????
function abrirProcedimentosViewOnly(defeitoId, nãome) {
    const modalHtml = `
    <div class="modal fade" id="modalViewProcedimentos" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content glass-card">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title"><i class="bi bi-journal-text text-warning me-2"></i>Procedimentos: ${nãome}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="listProcOS" class="d-flex flex-column gap-2">
                        <div class="text-center py-3"><div class="spinner-border text-warning spinner-border-sm"></div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>`;

    let modalEl = document.getElementById('modalViewProcedimentos');
    if (!modalEl) {
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        modalEl = document.getElementById('modalViewProcedimentos');
    } else {
        modalEl.querySelector('.modal-title').innerHTML = `<i class="bi bi-journal-text text-warning me-2"></i>Procedimentos: ${nãome}`;
    }

    const listDiv = modalEl.querySelector('#listProcOS');
    listDiv.innerHTML = '<div class="text-center py-3"><div class="spinner-border text-warning spinner-border-sm"></div></div>';

    new bootstrap.Modal(modalEl).show();

    fetch(BASE_URL + 'equipamentosdefeitos/procedimentos/' + defeitoId)
    .then(r => r.jsãon())
    .then(procs => {
        if (!procs.length) {
            listDiv.innerHTML = '<p class="text-muted small text-center my-3">Nenhum procedimento cadastrado.</p>';
        } else {
            listDiv.innerHTML = '';
            procs.forEach((p, i) => {
                listDiv.innerHTML += `
                    <div class="p-2 rounded" style="background:rgba(255,255,255,0.03);border:1px sãolid rgba(255,255,255,0.05);">
                        <span class="badge text-bg-warning rounded-pill me-2">${i+1}</span>
                        <span class="small">${p.descricao}</span>
                    </div>`;
            });
        }
    });
}

// ??? Modal de Visualiza��o de Imagem (Lightbox) ???????????????????????????
updateResumo();
document.addEventListener('DOMContentLoaded', function() {
    const modalInnerHtml = `
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content bg-transparent border-0">
                <div class="modal-body text-center p-0 position-relative">
                    <div class="d-inline-block position-relative">
                        <button type="button" class="btn-close position-absãolute" data-bs-dismiss="modal" aria-label="Close" style="top: 10px; right: 10px; z-index: 2055; filter: invert(1); opacity: 1; background-color: rgba(0,0,0,0.6); border-radius: 50%; padding: 0.8rem; box-shadow: 0 4px 12px rgba(0,0,0,0.5);"></button>
                        <img src="" id="modalImagePreview" class="img-fluid rounded shadow-lg" style="max-height: 85vh; object-fit: contain; background: rgba(0,0,0,0.9);">
                    </div>
                </div>
            </div>
        </div>`;

    let imageModal = document.getElementById('imageModal');
    if (!imageModal) {
        imageModal = document.createElement('div');
        imageModal.className = 'modal fade';
        imageModal.id = 'imageModal';
        imageModal.tabIndex = -1;
        imageModal.setAttribute('aria-hidden', 'true');
        imageModal.style.zIndex = '2000';
        imageModal.innerHTML = modalInnerHtml;
        document.body.appendChild(imageModal);
    } else if (!imageModal.querySelector('#modalImagePreview')) {
        imageModal.classList.add('modal', 'fade');
        imageModal.tabIndex = -1;
        imageModal.setAttribute('aria-hidden', 'true');
        if (!imageModal.style.zIndex) imageModal.style.zIndex = '2000';
        imageModal.innerHTML = modalInnerHtml;
    }

    if (imageModal.dataset.initialized === '1') return;
    imageModal.dataset.initialized = '1';

    imageModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const imgSrc = button?.getAttribute('data-img-src') || '';
        if (!imgSrc) {
            consãole.error('[OS Nãova] tentativa de abrir lightbox sem data-img-src');
            event.preventDefault();
            scheduleModalCleanup();
            return;
        }
        const modalImg = imageModal.querySelector('#modalImagePreview');
        modalImg.src = imgSrc;
    });
    imageModal.addEventListener('hidden.bs.modal', function () {
        try {
            const active = document.activeElement;
            if (active && imageModal.contains(active) && typeof active.blur === 'function') {
                active.blur();
            }
        } catch (_) {}
        const modalImg = imageModal.querySelector('#modalImagePreview');
        modalImg.src = '';
        scheduleModalCleanup();
    });
});
</script>
<?= $this->endSection() ?>
