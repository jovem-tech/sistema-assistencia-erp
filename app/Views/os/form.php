<?php
$isEdit = isset($os);
$tipos  = $tipos  ?? [];
$marcas = $marcas ?? [];
?>

<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div class="d-flex align-items-center gap-3">
        <h2><i class="bi bi-<?= $isEdit ? 'pencil' : 'plus-lg' ?> me-2"></i><?= $title ?></h2>
        <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('ordens-de-servico')">Ajuda</button>
    </div>
    <a href="<?= base_url('os') ?>" class="btn btn-outline-secondary" data-back-default="<?= base_url('os') ?>">
        <i class="bi bi-arrow-left me-1"></i>Voltar
    </a>
</div>

<!-- LAYOUT PRINCIPAL: SIDEBAR (foto) + CONTEÚDO -->
<div class="row g-4">

    <!-- SIDEBAR: Painel da foto do equipamento -->
    <div class="col-md-3" id="sidebarEquipamento">
        <div class="d-flex flex-column gap-3 sticky-top" style="top: 80px;">
            <div class="card glass-card">
                <div class="card-body p-3">
                    <h6 class="fw-bold mb-3 text-uppercase text-muted" style="font-size:0.7rem; letter-spacing:1px;">
                        <i class="bi bi-image me-1"></i>Foto do Equipamento
                    </h6>
                    <!-- Foto Principal -->
                    <div id="fotoPrincipalWrap" class="mb-3 text-center">
                        <div id="fotoMainBox" class="rounded overflow-hidden d-none"
                             style="height: 200px; background: #111; border: 2px solid rgba(255,255,255,0.1); position:relative;">
                            <a href="javascript:void(0)" id="fotoPrincipalLink" data-bs-toggle="modal" data-bs-target="#imageModal" data-img-src="" class="d-block w-100 h-100" style="cursor: zoom-in;">
                                <img id="fotoPrincipalImg" src="" alt="Foto do equipamento"
                                     class="w-100 h-100"
                                     style="object-fit: contain; transition: opacity 0.2s;">
                            </a>
                        </div>
                        <div id="fotoPlaceholder" class="rounded align-items-center justify-content-center d-flex"
                             style="height: 200px; background: rgba(255,255,255,0.04); border: 2px dashed rgba(255,255,255,0.1);">
                            <div class="text-center text-muted">
                                <i class="bi bi-image" style="font-size: 2rem;"></i>
                                <p class="small mt-2 mb-0">Selecione um equipamento</p>
                            </div>
                        </div>
                    </div>

                    <div id="equipColorInfo" class="d-flex align-items-center gap-2 small text-muted mb-2 d-none">
                        <span id="equipColorSwatch" class="d-inline-block rounded-circle border" style="width: 14px; height: 14px; background: #333;"></span>
                        <span id="equipColorName">Cor não informada</span>
                    </div>

                    <!-- Miniaturas -->
                    <div id="fotosMiniaturas" class="d-flex flex-wrap gap-2 justify-content-center"></div>

                    <!-- Info do Equipamento -->
                    <div id="equipInfoBox" class="mt-3 p-2 rounded" style="background: rgba(255,255,255,0.04); font-size: 0.78rem; display:none;">
                        <div id="equipInfoContent" class="text-muted"></div>
                    </div>
                </div>
            </div>

            <div class="card glass-card" id="resumoOsCard">
                <div class="card-body p-3">
                    <h6 class="fw-bold mb-3 text-uppercase text-muted" style="font-size:0.7rem; letter-spacing:1px;">
                        <i class="bi bi-clipboard2-check me-1"></i>Resumo da OS
                    </h6>
                    <div class="d-flex flex-column gap-2 small">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Cliente</span>
                            <span class="d-flex align-items-center gap-2">
                                <span id="resumoCliente" class="text-white-50">Não selecionado</span>
                                <span id="statusCliente" class="text-danger">❌</span>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Equipamento</span>
                            <span class="d-flex align-items-center gap-2">
                                <span id="resumoEquipamento" class="text-white-50">Não selecionado</span>
                                <span id="statusEquipamento" class="text-danger">❌</span>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Técnico</span>
                            <span class="d-flex align-items-center gap-2">
                                <span id="resumoTecnico" class="text-white-50">Não atribuído</span>
                                <span id="statusTecnico" class="text-danger">❌</span>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Prioridade</span>
                            <span class="d-flex align-items-center gap-2">
                                <span id="resumoPrioridade" class="badge text-bg-secondary">Normal</span>
                                <span id="statusPrioridade" class="text-success">✔️</span>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Status</span>
                            <span class="d-flex align-items-center gap-2">
                                <span id="resumoStatus" class="badge text-bg-secondary">Aguard. Análise</span>
                                <span id="statusStatus" class="text-success">✔️</span>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Entrada</span>
                            <span class="d-flex align-items-center gap-2">
                                <span id="resumoEntrada" class="text-white-50">-</span>
                                <span id="statusEntrada" class="text-danger">❌</span>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Previsão</span>
                            <span class="d-flex align-items-center gap-2">
                                <span id="resumoPrevisao" class="text-white-50">-</span>
                                <span id="statusPrevisao" class="text-danger">❌</span>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Relato</span>
                            <span class="d-flex align-items-center gap-2">
                                <span id="resumoRelato" class="text-white-50">Vazio</span>
                                <span id="statusRelato" class="text-danger">❌</span>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Acessórios</span>
                            <span class="d-flex align-items-center gap-2">
                                <span id="resumoAcessorios" class="text-white-50">Não informado</span>
                                <span id="statusAcessorios" class="text-danger">❌</span>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Fotos de entrada</span>
                            <span class="d-flex align-items-center gap-2">
                                <span id="resumoFotosEntrada" class="text-white-50">0</span>
                                <span id="statusFotos" class="text-danger">❌</span>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Defeitos marcados</span>
                            <span class="d-flex align-items-center gap-2">
                                <span id="resumoDefeitos" class="text-white-50">0</span>
                                <span id="statusDefeitos" class="text-danger">❌</span>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Rascunho</span>
                            <span id="resumoRascunho" class="text-white-50">Não salvo</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ÁREA PRINCIPAL DO FORMULÁRIO -->
    <div class="col-md-9" id="formCol">
        <div class="card glass-card">
            <div class="card-body">
                <form action="<?= $isEdit ? base_url('os/atualizar/' . $os['id']) : base_url('os/salvar') ?>"
                      method="POST" enctype="multipart/form-data" id="formOs">
                    <?= csrf_field() ?>
                    <?php if (!$isEdit): ?>
                    <div id="osDraftAlert" class="alert alert-info d-flex align-items-center justify-content-between gap-3 d-none">
                        <div class="small mb-0">
                            <i class="bi bi-clock-history me-1"></i>Encontramos um rascunho salvo automaticamente para esta OS.
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-light" id="btnDescartarRascunho">Descartar</button>
                            <button type="button" class="btn btn-sm btn-info" id="btnRestaurarRascunho">Restaurar</button>
                        </div>
                    </div>
                    <?php endif; ?>

                    <ul class="nav nav-tabs mb-3" id="osTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active fw-bold" id="tab-dados-btn" data-bs-toggle="tab" data-bs-target="#tab-dados" type="button" role="tab" aria-controls="tab-dados" aria-selected="true">Dados</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold" id="tab-relato-btn" data-bs-toggle="tab" data-bs-target="#tab-relato" type="button" role="tab" aria-controls="tab-relato" aria-selected="false">Relato e Defeitos</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold" id="tab-fotos-btn" data-bs-toggle="tab" data-bs-target="#tab-fotos" type="button" role="tab" aria-controls="tab-fotos" aria-selected="false">Fotos</button>
                        </li>
                        <?php if ($isEdit): ?>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold" id="tab-financeiro-btn" data-bs-toggle="tab" data-bs-target="#tab-financeiro" type="button" role="tab" aria-controls="tab-financeiro" aria-selected="false">Peças e Orçamento</button>
                        </li>
                        <?php endif; ?>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="tab-dados" role="tabpanel" aria-labelledby="tab-dados-btn" tabindex="0">

                    <!-- LINHA 1: Cliente + Equipamento + Técnico -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label d-flex align-items-center gap-2">
                                Cliente *
                                <?php if (can('clientes', 'criar')): ?>
                                <button class="btn btn-warning btn-sm py-0 px-2" type="button" id="btnNovoCliente"
                                        title="Cadastrar novo cliente" style="font-size:0.75rem; border-radius:6px; line-height:1.6;">
                                    <i class="bi bi-plus-lg"></i> Novo
                                </button>
                                <?php endif; ?>
                            </label>
                            <select name="cliente_id" id="clienteOsSelect" class="form-select select2-clientes" required>
                                <option value="">Selecione o cliente...</option>
                                <?php foreach ($clientes as $c): ?>
                                <option value="<?= $c['id'] ?>"
                                    <?= ($isEdit && $os['cliente_id'] == $c['id']) ? 'selected' : '' ?>>
                                    <?= esc($c['nome_razao']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label d-flex align-items-center gap-2">
                                Equipamento *
                                <?php if (can('equipamentos', 'criar')): ?>
                                <button class="btn btn-warning btn-sm py-0 px-2" type="button" id="btnNovoEquipamento"
                                        title="Cadastrar novo equipamento" style="font-size:0.75rem; border-radius:6px; line-height:1.6;">
                                    <i class="bi bi-plus-lg"></i> Novo
                                </button>
                                <?php endif; ?>
                            </label>
                            <select name="equipamento_id" id="equipamentoSelect" class="form-select select2-equip" required>
                                <option value="">Selecione o cliente primeiro...</option>
                                <?php if ($isEdit && !empty($equipamentos)): foreach ($equipamentos as $eq): ?>
                                <option value="<?= $eq['id'] ?>"
                                    data-tipo="<?= $eq['tipo_id'] ?? '' ?>"
                                    data-marca="<?= esc($eq['marca_nome'] ?? $eq['marca'] ?? '') ?>"
                                    data-modelo="<?= esc($eq['modelo_nome'] ?? $eq['modelo'] ?? '') ?>"
                                    data-serie="<?= esc($eq['numero_serie'] ?? '') ?>"
                                    data-cor="<?= esc($eq['cor'] ?? '') ?>"
                                    data-cor_hex="<?= esc($eq['cor_hex'] ?? '') ?>"
                                    data-tipo_nome="<?= esc($eq['tipo_nome'] ?? $eq['tipo'] ?? '') ?>"
                                    <?= $os['equipamento_id'] == $eq['id'] ? 'selected' : '' ?>>
                                    <?= esc(($eq['marca_nome'] ?? $eq['marca'] ?? '') . ' ' . ($eq['modelo_nome'] ?? $eq['modelo'] ?? '') . ' (' . ($eq['tipo_nome'] ?? $eq['tipo'] ?? '') . ')') ?>
                                </option>
                                <?php endforeach; endif; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Técnico Responsável</label>
                            <select name="tecnico_id" class="form-select">
                                <option value="">Não atribuído</option>
                                <?php foreach ($tecnicos as $t): ?>
                                <option value="<?= $t['id'] ?>"
                                    <?= ($isEdit && ($os['tecnico_id'] ?? '') == $t['id']) ? 'selected' : '' ?>>
                                    <?= esc($t['nome']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- LINHA 2: Prioridade + Data Previsão + Status + Data Entrada -->  
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label class="form-label">Prioridade</label>
                            <select name="prioridade" class="form-select">
                                <option value="baixa"   <?= ($isEdit && $os['prioridade'] === 'baixa')   ? 'selected' : '' ?>>Baixa</option>
                                <option value="normal"  <?= (!$isEdit || $os['prioridade'] === 'normal')  ? 'selected' : '' ?>>Normal</option>
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
                            <label class="form-label">Previsão de Entrega</label>
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
                                <?php
                                $statuses = [
                                    'aguardando_analise'   => 'Aguard. Análise',
                                    'aguardando_orcamento' => 'Aguard. Orçamento',
                                    'aguardando_aprovacao' => 'Aguard. Aprovação',
                                    'aprovado'    => 'Aprovado',
                                    'reprovado'   => 'Reprovado',
                                    'em_reparo'   => 'Em Reparo',
                                    'aguardando_peca' => 'Aguard. Peça',
                                    'pronto'      => 'Pronto',
                                    'entregue'    => 'Entregue',
                                    'cancelado'   => 'Cancelado',
                                ];
                                foreach ($statuses as $val => $label): ?>
                                <option value="<?= $val ?>"
                                    <?= ($isEdit ? $os['status'] : 'aguardando_analise') === $val ? 'selected' : '' ?>>
                                    <?= $label ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- LINHA EXTRA (edição): Garantia -->
                    <?php if ($isEdit): ?>
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label class="form-label">Garantia (dias)</label>
                            <input type="number" name="garantia_dias" class="form-control"
                                   value="<?= $os['garantia_dias'] ?? 90 ?>">
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <label class="form-label">Acessórios e Componentes (na entrada)</label>
                            <div class="border rounded-3 p-3 bg-white bg-opacity-10">
                                <div class="d-flex flex-wrap gap-2 mb-2">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-acessorio-key="chip">+ Chip</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-acessorio-key="capinha">+ Capinha celular</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-acessorio-key="capa">+ Capa</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-acessorio-key="mochila">+ Mochila</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-acessorio-key="bolsa">+ Bolsa notebook</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-acessorio-key="cabo">+ Cabo</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-acessorio-key="carregador">+ Carregador</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-acessorio-key="outro">+ Outro acessório</button>
                                </div>
                                <div id="acessoriosQuickForm" class="border rounded p-3 bg-body-tertiary mb-3 d-none">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <strong id="acessoriosQuickTitle"></strong>
                                        <button type="button" class="btn-close" id="acessoriosQuickClose"></button>
                                    </div>
                                    <div id="acessoriosQuickFields" class="row g-2"></div>
                                    <div class="mt-3">
                                        <button type="button" class="btn btn-sm btn-primary" id="acessoriosQuickSave">Salvar item</button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" id="acessoriosQuickCancel">Cancelar</button>
                                    </div>
                                </div>
                                <div id="acessoriosList" class="list-group"></div>
                                <small class="form-text text-muted mt-3">Padronize rapidamente o registro de acessórios comuns.</small>
                                <textarea name="acessorios" id="acessoriosInput" class="d-none"><?= $isEdit ? esc($os['acessorios'] ?? '') : '' ?></textarea>
                                <input type="hidden" name="acessorios_data" id="acessoriosDataInput">
                                <input type="file" id="acessoriosPhotoInput" class="d-none" accept="image/jpeg,image/png,image/webp" multiple>
                                <div id="acessoriosFilesInputs" class="d-none"></div>
                            </div>
                        </div>
                    </div>

                        </div>
                        <div class="tab-pane fade" id="tab-relato" role="tabpanel" aria-labelledby="tab-relato-btn" tabindex="0">
                    <!-- Relato do Cliente -->
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <label class="form-label">Relato do Cliente *</label>
                            <textarea name="relato_cliente" class="form-control" rows="3" required><?= $isEdit ? esc($os['relato_cliente']) : old('relato_cliente') ?></textarea>
                        </div>
                    </div>

                    <!-- Defeitos Comuns -->
                    <div class="row g-3 mb-4" id="defeitosSection" style="display:none;">
                        <div class="col-12">
                            <div class="card" style="background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.1); border-radius: 10px;">
                                <div class="card-header py-2" style="background: transparent; border-bottom: 1px solid rgba(255,255,255,0.1);">
                                    <strong><i class="bi bi-bug me-2 text-warning"></i>Defeitos Comuns do Tipo de Equipamento</strong>
                                    <small class="text-muted ms-2">(opcional — selecione os que se aplicam)</small>
                                </div>
                                <div class="card-body" id="defeitosContainer">
                                    <span class="text-muted small">Selecione o equipamento para carregar os defeitos...</span>
                                </div>
                            </div>
                        </div>
                    </div>

                        </div>
                        <div class="tab-pane fade" id="tab-fotos" role="tabpanel" aria-labelledby="tab-fotos-btn" tabindex="0">
                    <!-- FOTOS DE ENTRADA DO EQUIPAMENTO -->
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                    <div class="card" style="background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px;">
                        <div class="card-header py-3 d-flex flex-column flex-md-row justify-content-between gap-2" style="background: transparent; border-bottom: 1px solid rgba(255,255,255,0.1);">
                            <div>
                                <strong><i class="bi bi-camera me-2 text-info"></i>Fotos de Entrada do Equipamento</strong>
                                <small class="text-muted ms-2">(opcional — acessórios, estado físico, placa interna, etc.)</small>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-light btn-sm" id="btnFotosEscolher">
                                    <i class="bi bi-folder2-open me-1"></i>Escolher Arquivos
                                </button>
                                <button type="button" class="btn btn-outline-warning btn-sm" id="btnLimparFotos">
                                    <i class="bi bi-trash me-1"></i>Limpar
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <input type="file" name="fotos_entrada[]" id="fotosEntradaInput"
                                   accept="image/jpeg,image/png,image/webp"
                                   multiple class="d-none">
                            <div id="osFotosDropzone" class="border rounded-4 d-flex align-items-center justify-content-center flex-column gap-2 text-center py-4 mb-3"
                                 style="min-height: 180px; transition: background 0.2s;">
                                <i class="bi bi-cloud-upload display-4 text-muted"></i>
                                <p class="text-muted mb-0 fw-semibold">Clique para selecionar ou arraste arquivos aqui.</p>
                                <small class="text-muted">Até 4 fotos, 2MB cada.</small>
                            </div>
                            <div id="osFotosPreview" class="d-flex flex-wrap gap-3"></div>
                            <div id="osFotosExisting" class="d-flex flex-wrap gap-3 mt-3"></div>
                        </div>
                    </div>
                        </div>
                    </div>

                        </div>
                        <?php if ($isEdit): ?>
                        <div class="tab-pane fade" id="tab-financeiro" role="tabpanel" aria-labelledby="tab-financeiro-btn" tabindex="0">
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <div class="card" style="background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.1); border-radius: 10px;">
                                <div class="card-header py-2" style="background: transparent; border-bottom: 1px solid rgba(255,255,255,0.1);">
                                    <strong><i class="bi bi-box-seam me-2 text-primary"></i>Peças e Serviços</strong>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted small mb-2">Adicione peças e serviços na tela de visualização da OS.</p>
                                    <a href="<?= base_url('os/visualizar/' . $os['id']) ?>" class="btn btn-sm btn-outline-info">Abrir OS e lançar itens</a>
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
                                    '' => 'Não definido',
                                    'dinheiro' => 'Dinheiro',
                                    'pix' => 'Pix',
                                    'cartao_credito' => 'Cartão de Crédito',
                                    'cartao_debito' => 'Cartão de Débito',
                                    'transferencia' => 'Transferência',
                                    'boleto' => 'Boleto',
                                ];
                                foreach ($formas as $val => $label): ?>
                                <option value="<?= $val ?>" <?= ($isEdit && ($os['forma_pagamento'] ?? '') === $val) ? 'selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Diagnóstico -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Diagnóstico Técnico</label>
                            <textarea name="diagnostico_tecnico" class="form-control" rows="3"><?= esc($os['diagnostico_tecnico'] ?? '') ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Solução Aplicada</label>
                            <textarea name="solucao_aplicada" class="form-control" rows="3"><?= esc($os['solucao_aplicada'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <!-- Valores -->
                    <h6 class="text-uppercase text-muted mb-3"><i class="bi bi-currency-dollar me-1"></i>Valores</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label class="form-label">Mão de Obra (R$)</label>
                            <input type="number" step="0.01" name="valor_mao_obra" class="form-control" value="<?= $os['valor_mao_obra'] ?? 0 ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Peças (R$)</label>
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

                    <!-- Observações -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Observações Internas</label>
                            <textarea name="observacoes_internas" class="form-control" rows="2"><?= esc($os['observacoes_internas'] ?? '') ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Observações para o Cliente</label>
                            <textarea name="observacoes_cliente" class="form-control" rows="2"><?= esc($os['observacoes_cliente'] ?? '') ?></textarea>
                        </div>
                    </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="d-flex gap-3 align-items-center flex-wrap">
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

<!-- ===== MODAL: CADASTRAR NOVO CLIENTE ===== -->
<div class="modal fade" id="modalNovoCliente" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content glass-card">
            <div class="modal-header border-bottom">
                <h5 class="modal-title">
                    <i class="bi bi-person-plus text-warning me-2"></i>Cadastro Rápido de Cliente
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formNovoClienteAjax">
                    <?= csrf_field() ?>
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Nome / Razão Social *</label>
                            <input type="text" name="nome_razao" class="form-control" required>
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
                            <label class="form-label text-muted">Nome Contato (Opcional)</label>
                            <input type="text" name="nome_contato" class="form-control" placeholder="Esposa, Fllho...">
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
                            <label class="form-label text-muted">Endereço</label>
                            <input type="text" name="endereco" class="form-control js-logradouro">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label text-muted">Nº</label>
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
                    <div id="modalClienteErrors" class="alert alert-danger mt-3 d-none"></div>
                </form>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-glow" id="btnSalvarNovoCliente">
                    <i class="bi bi-check-lg me-1"></i>Cadastrar Cliente
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ===== MODAL: CADASTRAR NOVO EQUIPAMENTO ===== -->
<div class="modal fade" id="modalNovoEquipamento" tabindex="-1" aria-labelledby="labelModalNovoEquip">
    <div class="modal-dialog modal-lg">
        <div class="modal-content glass-card">
            <div class="modal-header border-bottom">
                <h5 class="modal-title" id="labelModalNovoEquip">
                    <i class="bi bi-plus-circle text-warning me-2"></i>Cadastrar Novo Equipamento
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formNovoEquipAjax" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    
                    <!-- Navegação por Abas no Modal -->
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
                                    <select name="tipo_id" id="novoEquipTipo" class="form-select form-select-sm" required>
                                        <option value="">Escolha...</option>
                                        <?php foreach ($tipos as $t): ?>
                                        <option value="<?= $t['id'] ?>"><?= esc($t['nome']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 text-start">
                                    <label class="form-label mb-1 small fw-bold">Marca *</label>
                                    <div class="input-group input-group-sm">
                                        <select name="marca_id" id="novoEquipMarca" class="form-select select2-modal" required>
                                            <option value="">Marca...</option>
                                            <?php foreach ($marcas as $m): ?>
                                            <option value="<?= $m['id'] ?>"><?= esc($m['nome']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button class="btn btn-warning" type="button" id="btnNovaMarcaOS"><i class="bi bi-plus"></i></button>
                                    </div>
                                </div>
                                <div class="col-md-6 text-start mt-2">
                                    <label class="form-label mb-1 small fw-bold">Modelo *</label>
                                    <div class="input-group input-group-sm">
                                        <select name="modelo_id" id="novoEquipModelo" class="form-select" required>
                                            <option value="">Modelo...</option>
                                        </select>
                                        <button class="btn btn-warning" type="button" id="btnNovoModeloOS"><i class="bi bi-plus"></i></button>
                                    </div>
                                    <input type="hidden" name="modelo_nome_ext" id="novoEquipModeloNomeExt">
                                </div>
                                <div class="col-md-6 text-start mt-2">
                                    <label class="form-label mb-1 small fw-bold">Nº de Série</label>
                                    <input type="text" name="numero_serie" class="form-control form-control-sm" placeholder="IMEI ou Série">
                                </div>
                                <div class="col-12 text-start mt-2">
                                    <label class="form-label mb-1 small d-flex justify-content-between">
                                        <span class="fw-bold">Senha de Acesso</span>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-light border py-0 px-2 btn-senha-tipo-os" data-placeholder="Numérico (PIN)" title="PIN/Desenho" style="font-size:0.65rem;">PIN</button>
                                            <button type="button" class="btn btn-light border py-0 px-2 btn-senha-tipo-os" data-placeholder="Alfanumérico" title="Texto" style="font-size:0.65rem;">TEXTO</button>
                                        </div>
                                    </label>
                                    <input type="text" name="senha_acesso" id="inputSenhaAcessoOS" class="form-control form-control-sm" placeholder="Senha do aparelho">
                                </div>
                                <div class="col-md-6 text-start mt-2">
                                    <label class="form-label mb-1 small fw-bold text-muted">Estado Físico</label>
                                    <textarea name="estado_fisico" class="form-control form-control-sm" rows="2" placeholder="Ex: Tela riscada..."></textarea>
                                </div>
                                <div class="col-md-6 text-start mt-2">
                                    <label class="form-label mb-1 small fw-bold text-muted d-flex justify-content-between">
                                        Acessórios
                                        <span style="font-size:0.6rem;">+ Rápido</span>
                                    </label>
                                    <textarea name="acessorios" id="textareaAcessoriosOS" class="form-control form-control-sm mb-1" rows="2" placeholder="Cabos, capas..."></textarea>
                                    <div class="d-flex flex-wrap gap-1">
                                        <button type="button" class="badge btn btn-light border p-1 fw-normal btn-quick-acessorio-os" style="font-size:0.6rem; color:#666;">+ Carregador</button>
                                        <button type="button" class="badge btn btn-light border p-1 fw-normal btn-quick-acessorio-os" style="font-size:0.6rem; color:#666;">+ Cabo</button>
                                        <button type="button" class="badge btn btn-light border p-1 fw-normal btn-quick-acessorio-os" style="font-size:0.6rem; color:#666;">+ Capa</button>
                                        <button type="button" class="badge btn btn-light border p-1 fw-normal btn-quick-acessorio-os" style="font-size:0.6rem; color:#666;">+ Chip</button>
                                        <button type="button" class="badge btn btn-light border p-1 fw-normal btn-quick-acessorio-os" style="font-size:0.6rem; color:#666;">+ Cartão</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ABA 2: COR -->
                        <div class="tab-pane fade" id="m-cor-pane" role="tabpanel">
                            <div class="p-2 border rounded bg-light bg-opacity-25">
                                <input type="hidden" name="cor_hex" id="corHexRealOS" value="#1A1A1A">
                                <input type="hidden" name="cor_rgb" id="corRgbRealOS" value="26,26,26">
                                <input type="hidden" name="cor" id="corNomeRealOS" value="Preto">

                                <!-- Smart Detection -->
                                <div class="p-2 mb-2 rounded border border-warning border-opacity-50 bg-warning bg-opacity-10 d-none" id="smartColorContainerOS">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span style="font-size: 0.65rem;" class="text-warning fw-semibold"><i class="bi bi-magic me-1"></i>Sugerido da foto:</span>
                                        <button type="button" class="btn btn-sm text-success p-0 border-0 fw-bold" id="btnAcceptColorOS" style="font-size: 0.7rem;">Aplicar <i class="bi bi-check2-circle ms-1"></i></button>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <div id="smartColorSwatchOS" class="rounded-circle shadow border" style="width: 20px; height: 20px;"></div>
                                        <strong id="smartColorNameOS" style="font-size: 0.8rem;">Nenhuma</strong>
                                    </div>
                                </div>

                                <div class="row g-2">
                                    <div class="col-md-5">
                                        <div id="colorPreviewBoxOS" class="rounded-3 shadow-sm border mb-2 d-flex flex-column align-items-center justify-content-center" style="height: 80px; background: #1A1A1A; transition: background 0.3s ease;">
                                            <span id="colorPreviewHexOS" class="fw-bold font-monospace" style="font-size: 0.85rem; color: #fff;">#1A1A1A</span>
                                            <span id="colorPreviewNameOS" class="mt-1" style="font-size: 0.7rem; color: rgba(255,255,255,0.8);">Preto</span>
                                        </div>
                                        <div class="d-flex gap-2 mb-2">
                                            <input type="color" id="corHexPickerOS" class="form-control form-control-color p-1" value="#1A1A1A" style="width: 40px; height: 32px;">
                                            <input type="text" id="corNomeInputOS" class="form-control form-control-sm" placeholder="Nome" value="Preto">
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
                                <input type="file" name="foto_perfil" id="novoEquipFoto" class="d-none" accept="image/*">
                            </div>

                            <div id="novoEquipFotoPreview" class="mt-2" style="display:none;">
                                <div class="position-relative d-inline-block shadow rounded border p-1 bg-white">
                                    <img id="novoEquipFotoImg" src="" style="height:140px; width: 140px; object-fit:cover; border-radius:4px;">
                                    <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1 p-1 py-0 shadow" id="btnRemoverFotoNovoEquip" style="border-radius: 50%;">
                                        <i class="bi bi-x"></i>
                                    </button>
                                </div>
                                <div class="mt-2 small text-muted">A foto de perfil ajuda na identificação visual rápida.</div>
                            </div>
                            
                            <div id="fotoVaziaOS" class="py-4 text-muted opacity-50">
                                <i class="bi bi-image fs-1 d-block"></i>
                                <span class="small font-monospace">Nenhuma imagem selecionada</span>
                            </div>
                        </div>
                    </div>
                    <div id="modalEquipErrors" class="alert alert-danger mt-3 d-none p-2 small"></div>
                </form>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-glow" id="btnSalvarNovoEquip">
                    <i class="bi bi-check-lg me-1"></i>Cadastrar Equipamento
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ===== MODAL: NOVA MARCA (AUXILIAR) ===== -->
<div class="modal fade" id="modalNovaMarcaOS" tabindex="-1" style="z-index: 1070;">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content glass-card shadow-lg">
            <div class="modal-header border-bottom">
                <h5 class="modal-title font-title"><i class="bi bi-tag text-warning me-2"></i>Nova Marca</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="text" id="inputNovaMarcaOS" class="form-control" placeholder="Ex: Samsung, Apple...">
                <div id="errorNovaMarcaOS" class="text-danger small mt-2 d-none"></div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-glow w-100" id="btnSalvarMarcaOS">Salvar Marca</button>
            </div>
        </div>
    </div>
</div>

<!-- ===== MODAL: NOVO MODELO (AUXILIAR) ===== -->
<div class="modal fade" id="modalNovoModeloOS" tabindex="-1" style="z-index: 1070;">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content glass-card shadow-lg">
            <div class="modal-header border-bottom">
                <h5 class="modal-title font-title"><i class="bi bi-cpu text-warning me-2"></i>Novo Modelo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="small text-muted">Marca Selecionada:</label>
                    <input type="text" id="displayMarcaOS" class="form-control form-control-sm bg-transparent" readonly>
                </div>
                <div class="mb-2">
                    <label class="form-label fw-bold mb-1">Nome do Modelo *</label>
                    <div class="position-relative">
                        <input type="text" id="inputNovoModeloOS" class="form-control"
                               placeholder="Ex: Galaxy S24, iPhone 15, Moto G84..."
                               autocomplete="off">
                        <div id="spinnerNovoModeloOS" class="position-absolute top-50 end-0 translate-middle-y me-2 d-none">
                            <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                        </div>
                    </div>
                    <!-- Dropdown de sugestões -->
                    <div id="sugestoesNovoModeloOS" class="list-group shadow-lg mt-1 d-none"
                         style="max-height: 220px; overflow-y: auto; border-radius: 8px; z-index: 9999; position: relative;"></div>
                    <div class="form-text mt-1">
                        <i class="bi bi-globe2 me-1 text-info"></i>
                        Digite 3+ caracteres para ver sugestões da internet
                    </div>
                </div>
                <div id="errorNovoModeloOS" class="text-danger small mt-2 d-none"></div>
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


<!-- ===== MODAL: CÂMERA (AUXILIAR) ===== -->
<div class="modal fade" id="modalCamera" tabindex="-1" style="z-index: 2000;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-card border-0 shadow-lg">
            <div class="modal-header border-bottom border-light">
                <h5 class="modal-title"><i class="bi bi-camera me-2 text-warning"></i>Capturar Foto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-0 overflow-hidden bg-black" style="min-height: 300px;">
                <video id="videoCamera" class="w-100 h-100" style="object-fit: cover;" autoplay playsinline></video>
                <canvas id="canvasCamera" class="d-none"></canvas>
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
<!-- Cropper.js -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
<style>
    .custom-color-accordion .accordion-button { transition: all 0.2s ease; }
    .custom-color-accordion .accordion-button:not(.collapsed) {
        color: var(--bs-primary) !important;
        background-color: rgba(var(--bs-primary-rgb), 0.05) !important;
    }
    .custom-color-accordion .list-group-item { transition: all 0.15s ease; cursor: pointer; }
    .custom-color-accordion .list-group-item:hover { background-color: rgba(0,0,0,0.03); transform: translateX(3px); }
    .custom-color-accordion .list-group-item.active { border-left: 3px solid var(--bs-primary) !important; }
</style>

<script>
const BASE_URL = document.querySelector('meta[name="base-url"]').content;
const isEdit   = <?= $isEdit ? 'true' : 'false' ?>;
<?php if ($isEdit && !empty($defeitosSelected)): ?>
var defeitosSelecionados = <?= json_encode(array_column($defeitosSelected, 'defeito_id')) ?>;
<?php else: ?>
var defeitosSelecionados = [];
<?php endif; ?>
const existingFotosCount = <?= (int)(count($fotos_entrada ?? [])) ?>;
let pendingEquipId = null;
let pendingDefeitos = null;
const DRAFT_KEY = 'osDraft_v1';
const DRAFT_TTL_MS = 1000 * 60 * 60 * 24 * 7;
let draftSaveTimer = null;

const statusLabels = {
    aguardando_analise: 'Aguard. Análise',
    aguardando_orcamento: 'Aguard. Orçamento',
    aguardando_aprovacao: 'Aguard. Aprovação',
    aprovado: 'Aprovado',
    reprovado: 'Reprovado',
    em_reparo: 'Em Reparo',
    aguardando_peca: 'Aguard. Peça',
    pronto: 'Pronto',
    entregue: 'Entregue',
    cancelado: 'Cancelado'
};

const prioridadeLabels = {
    baixa: 'Baixa',
    normal: 'Normal',
    alta: 'Alta',
    urgente: 'Urgente'
};

// ─── Select2 ────────────────────────────────────────────────────────────────
if (typeof $.fn.select2 !== 'undefined') {
    $('#clienteOsSelect').select2({
        theme: 'bootstrap-5',
        placeholder: 'Buscar cliente...',
        allowClear: true,
        width: '100%'
    }).on('select2:open', function() {
        // Adiciona um listener para detectar quando o usuário pressiona Enter na busca vazia
        // ou quando não há resultados. Mas vamos focar no botão fixo.
    });

    // Se quiser botão de Add dentro do dropdown Select2, é complexo.
    // O botão '+ Novo' já resolve bem.
}

// ─── Modal: Cadastrar Novo Cliente ──────────────────────────────────────────
const btnNovoCliente = document.getElementById('btnNovoCliente');
if (btnNovoCliente) {
    btnNovoCliente.addEventListener('click', function() {
        new bootstrap.Modal(document.getElementById('modalNovoCliente')).show();
    });
}

document.getElementById('btnSalvarNovoCliente')?.addEventListener('click', function() {
    const form = document.getElementById('formNovoClienteAjax');
    const errors = document.getElementById('modalClienteErrors');
    errors.classList.add('d-none');

    const formData = new FormData(form);

    fetch(`${BASE_URL}clientes/salvar_ajax`, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(res => {
        if (!res.success) {
            errors.innerHTML = res.message || 'Erro ao cadastrar cliente.';
            errors.classList.remove('d-none');
            return;
        }

        // Adiciona ao Select2
        const sel = $('#clienteOsSelect');
        const opt = new Option(res.nome, res.id, true, true);
        sel.append(opt).trigger('change');

        // Fecha modal
        bootstrap.Modal.getInstance(document.getElementById('modalNovoCliente'))?.hide();
        form.reset();
        
        // Dispara o change para carregar equipamentos (que virão vazios, claro, mas reseta o combo)
        _onClienteChange(res.id);
    })
    .catch(() => {
        errors.innerHTML = 'Erro inesperado. Tente novamente.';
        errors.classList.remove('d-none');
    });
});

// ─── Sidebar layout toggling ───────────────────────────────────────────────
function showSidebar() {
    const sidebar = document.getElementById('sidebarEquipamento');
    const formCol = document.getElementById('formCol');
    if (sidebar) sidebar.style.display = '';
    if (formCol) formCol.className = 'col-md-9';
}
function hideSidebar() {
    const mainBox     = document.getElementById('fotoMainBox');
    const placeholder = document.getElementById('fotoPlaceholder');
    const minis       = document.getElementById('fotosMiniaturas');
    const infoBox     = document.getElementById('equipInfoBox');
    const infoContent = document.getElementById('equipInfoContent');
    const colorInfo   = document.getElementById('equipColorInfo');

    if (mainBox) mainBox.classList.add('d-none');
    if (placeholder) {
        placeholder.classList.remove('d-none');
        placeholder.classList.add('d-flex');
        placeholder.style.background = 'rgba(255,255,255,0.04)';
        placeholder.style.color = '';
    }
    if (minis) minis.innerHTML = '';
    if (infoBox) infoBox.style.display = 'none';
    if (infoContent) infoContent.innerHTML = '';
    if (colorInfo) colorInfo.classList.add('d-none');
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
    el.textContent = ok ? '✔️' : '❌';
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
    const relatoInp  = document.querySelector('textarea[name="relato_cliente"]');
    const acessoriosInp = document.querySelector('textarea[name="acessorios"]');

    const clienteText = _getSelectedText(clienteSel, 'Não selecionado');
    const equipText   = _getSelectedText(equipSel, 'Não selecionado');
    const tecnicoText = _getSelectedText(tecnicoSel, 'Não atribuído');
    const prioridadeVal = prioridadeSel?.value || 'normal';
    const statusVal = statusSel?.value || 'aguardando_analise';
    const relatoVal = relatoInp?.value?.trim() || '';
    const acessoriosVal = acessoriosInp?.value?.trim() || '';

    document.getElementById('resumoCliente').textContent = clienteText;
    document.getElementById('resumoEquipamento').textContent = equipText;
    document.getElementById('resumoTecnico').textContent = tecnicoText;
    document.getElementById('resumoEntrada').textContent = _formatDateTime(entradaInp?.value);
    document.getElementById('resumoPrevisao').textContent = _formatDate(previsaoInp?.value);
    document.getElementById('resumoRelato').textContent = relatoVal ? 'Preenchido' : 'Vazio';
    document.getElementById('resumoAcessorios').textContent = acessoriosVal ? 'Informado' : 'Não informado';

    const prioridadeBadgeClass = {
        baixa: 'text-bg-secondary',
        normal: 'text-bg-primary',
        alta: 'text-bg-warning',
        urgente: 'text-bg-danger'
    }[prioridadeVal] || 'text-bg-secondary';
    _setResumoBadge('resumoPrioridade', prioridadeLabels[prioridadeVal] || 'Normal', prioridadeBadgeClass);

    const statusBadgeClass = {
        aguardando_analise: 'text-bg-secondary',
        aguardando_orcamento: 'text-bg-info',
        aguardando_aprovacao: 'text-bg-info',
        aprovado: 'text-bg-success',
        reprovado: 'text-bg-danger',
        em_reparo: 'text-bg-warning',
        aguardando_peca: 'text-bg-warning',
        pronto: 'text-bg-success',
        entregue: 'text-bg-primary',
        cancelado: 'text-bg-dark'
    }[statusVal] || 'text-bg-secondary';
    _setResumoBadge('resumoStatus', statusLabels[statusVal] || 'Aguard. Análise', statusBadgeClass);

    const defeitosCount = document.querySelectorAll('.chk-defeito-comum:checked').length;
    document.getElementById('resumoDefeitos').textContent = defeitosCount.toString();

    const fotosInput = document.getElementById('fotosEntradaInput');
    const fotosCount = fotosInput?.files ? fotosInput.files.length : 0;
    const totalFotos = existingFotosCount + fotosCount;
    document.getElementById('resumoFotosEntrada').textContent = totalFotos.toString();

    _setFieldStatus('statusCliente', Boolean(clienteSel?.value));
    _setFieldStatus('statusEquipamento', Boolean(equipSel?.value));
    _setFieldStatus('statusTecnico', Boolean(tecnicoSel?.value));
    _setFieldStatus('statusPrioridade', Boolean(prioridadeSel?.value));
    _setFieldStatus('statusStatus', Boolean(statusSel?.value));
    _setFieldStatus('statusEntrada', Boolean(entradaInp?.value));
    _setFieldStatus('statusPrevisao', Boolean(previsaoInp?.value));
    _setFieldStatus('statusRelato', Boolean(relatoVal));
    _setFieldStatus('statusAcessorios', Boolean(acessoriosVal));
    _setFieldStatus('statusFotos', totalFotos > 0);
    _setFieldStatus('statusDefeitos', defeitosCount > 0);
}

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

function normalizeHexColor(value) {
    const raw = (value || '').trim();
    if (/^#[0-9a-fA-F]{6}$/.test(raw)) return raw.toUpperCase();
    if (/^[0-9a-fA-F]{6}$/.test(raw)) return `#${raw.toUpperCase()}`;
    return '';
}

function extractHexFromAccessoryColor(value) {
    const raw = (value || '').trim();
    const directHex = normalizeHexColor(raw);
    if (directHex) return directHex;
    const match = raw.match(/#([0-9a-fA-F]{6})/);
    return match ? `#${match[1].toUpperCase()}` : '';
}

function getAccessoryNamedColors() {
    const list = [];
    Object.entries(colorNameMap).forEach(([hex, name]) => {
        const normalizedHex = normalizeHexColor(hex);
        if (normalizedHex) list.push({ hex: normalizedHex, name });
    });

    if (typeof PROFESSIONAL_COLORS_OS !== 'undefined' && Array.isArray(PROFESSIONAL_COLORS_OS)) {
        PROFESSIONAL_COLORS_OS.forEach(group => {
            (group.colors || []).forEach(color => {
                const normalizedHex = normalizeHexColor(color.hex);
                if (normalizedHex) list.push({ hex: normalizedHex, name: color.name || normalizedHex });
            });
        });
    }

    const unique = new Map();
    list.forEach(item => {
        if (!unique.has(item.hex)) unique.set(item.hex, item);
    });
    return Array.from(unique.values());
}

function getAccessoryHexByName(name) {
    const needle = (name || '').trim().toLowerCase();
    if (!needle) return '';
    const exact = getAccessoryNamedColors().find(c => (c.name || '').trim().toLowerCase() === needle);
    return exact ? exact.hex : '';
}

function getClosestAccessoryColorName(hex) {
    const normalizedHex = normalizeHexColor(hex);
    if (!normalizedHex) return '';
    const colors = getAccessoryNamedColors();
    if (!colors.length) return normalizedHex;

    let best = colors[0];
    let minDistance = Number.POSITIVE_INFINITY;
    colors.forEach(color => {
        const distance = (typeof colorDistanceOS === 'function')
            ? colorDistanceOS(normalizedHex, color.hex)
            : (color.hex === normalizedHex ? 0 : Number.POSITIVE_INFINITY);
        if (distance < minDistance) {
            minDistance = distance;
            best = color;
        }
    });
    return best?.name || normalizedHex;
}

function formatAccessoryColorValue(hex) {
    const normalizedHex = normalizeHexColor(hex);
    if (!normalizedHex) return '';
    const name = getClosestAccessoryColorName(normalizedHex);
    return name || '';
}

function composeAccessoryText(base, detail = '') {
    const cleanDetail = (detail || '').trim();
    return cleanDetail ? `${base} ${cleanDetail}` : base;
}

const acessoriosConfig = {
    chip: {
        title: 'Chip',
        fields: [{ name: 'chip_digits', label: 'Últimos 6 dígitos do chip', placeholder: '123456', max: 6 }],
        format: values => composeAccessoryText('Chip', values.chip_digits ? ('final ' + values.chip_digits) : '')
    },
    capinha: {
        title: 'Capinha celular',
        fields: [{ name: 'cor', label: 'Cor da capinha', placeholder: 'Preta', type: 'color_text' }],
        format: values => composeAccessoryText('Capinha celular', values.cor)
    },
    capa: {
        title: 'Capa',
        fields: [],
        format: () => 'Capa'
    },
    mochila: {
        title: 'Mochila',
        fields: [{ name: 'cor', label: 'Cor da mochila', placeholder: 'Preta', type: 'color_text' }],
        format: values => composeAccessoryText('Mochila', values.cor)
    },
    bolsa: {
        title: 'Bolsa notebook',
        fields: [{ name: 'cor', label: 'Cor da bolsa', placeholder: 'Cinza', type: 'color_text' }],
        format: values => composeAccessoryText('Bolsa notebook', values.cor)
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
                { value: 'Cabo de força', label: 'Cabo de força' },
                { value: 'Outro', label: 'Outro' }
            ]
        }],
        format: values => composeAccessoryText('Cabo', values.tipo)
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
                { value: 'Notebook', label: 'Notebook' },
                { value: 'Tablet', label: 'Tablet' },
                { value: 'Outro', label: 'Outro' }
            ]
        }],
        format: values => composeAccessoryText('Carregador', values.tipo_equip)
    },
    outro: {
        title: 'Outro acessório',
        fields: [{ name: 'descricao', label: 'Descrição', placeholder: 'Ex: cabo adaptador' }],
        format: values => `${values.descricao || 'Outro acessório'}`
    }
};

const acessoriosInput = document.getElementById('acessoriosInput');
const acessoriosDataInput = document.getElementById('acessoriosDataInput');
const acessoriosList = document.getElementById('acessoriosList');
const acessoriosQuickForm = document.getElementById('acessoriosQuickForm');
const acessoriosQuickTitle = document.getElementById('acessoriosQuickTitle');
const acessoriosQuickFields = document.getElementById('acessoriosQuickFields');
const acessoriosQuickSave = document.getElementById('acessoriosQuickSave');
const acessoriosQuickCancel = document.getElementById('acessoriosQuickCancel');
const acessoriosQuickClose = document.getElementById('acessoriosQuickClose');
const acessoriosPhotoInput = document.getElementById('acessoriosPhotoInput');
const acessoriosFilesInputs = document.getElementById('acessoriosFilesInputs');
const acessoriosPhotos = {};
const acessoriosFileInputs = {};
let acessoriosEntries = [];
let acessoriosEditing = null;
let acessoriosCurrentKey = null;
let acessoriosPhotoTarget = null;
let acessorioCropQueue = [];
let acessorioCropEntryId = null;

const initialAcessoriosText = acessoriosInput?.value?.trim() || '';
if (initialAcessoriosText) {
    initialAcessoriosText.split(/\r?\n/).filter(Boolean).forEach(text => {
        acessoriosEntries.push({ id: `acc_${Date.now()}_${Math.random().toString(36).slice(2)}`, text, key: 'outro' });
    });
}

function generateEntryId() {
    return `acc_${Date.now()}_${Math.random().toString(36).substring(2, 8)}`;
}

function syncAcessoriosInput() {
    if (!acessoriosInput) return;
    acessoriosInput.value = acessoriosEntries.map(entry => entry.text).join('\n');
    if (acessoriosDataInput) {
        acessoriosDataInput.value = JSON.stringify(acessoriosEntries.map(entry => ({
            id: entry.id,
            text: entry.text,
            key: entry.key || 'outro',
            values: entry.values || {}
        })));
    }
    updateResumo();
    scheduleDraftSave();
}

function ensureAcessorioFileInput(entryId) {
    if (!acessoriosFilesInputs) return null;
    let input = acessoriosFileInputs[entryId];
    if (!input) {
        input = document.createElement('input');
        input.type = 'file';
        input.multiple = true;
        input.name = `fotos_acessorios[${entryId}][]`;
        input.id = `acessorio_files_${entryId}`;
        input.className = 'd-none';
        acessoriosFilesInputs.appendChild(input);
        acessoriosFileInputs[entryId] = input;
    }
    const dt = acessoriosPhotos[entryId];
    if (dt) {
        input.files = dt.files;
    }
    return input;
}

function removeAcessorioFileInput(entryId) {
    const input = acessoriosFileInputs[entryId];
    if (input) {
        input.remove();
        delete acessoriosFileInputs[entryId];
    }
    delete acessoriosPhotos[entryId];
}

function renderAcessoriosPhotos(entryId, container) {
    if (!container) return;
    container.innerHTML = '';
    const dt = acessoriosPhotos[entryId];
    if (!dt) return;
    Array.from(dt.files).forEach((file, index) => {
        const reader = new FileReader();
        reader.onload = function(e) {
            const thumb = document.createElement('div');
            thumb.className = 'border rounded overflow-hidden position-relative';
            thumb.style.cssText = 'width:70px; height:70px;';

            const preview = document.createElement('div');
            preview.className = 'w-100 h-100 overflow-hidden position-relative image-preview';
            preview.style.cursor = 'zoom-in';
            preview.setAttribute('data-bs-toggle', 'modal');
            preview.setAttribute('data-bs-target', '#imageModal');
            preview.setAttribute('data-img-src', e.target.result);
            preview.innerHTML = `
                <img src="${e.target.result}" class="w-100 h-100 object-fit-cover">
            `;

            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'btn btn-sm btn-outline-light position-absolute top-0 end-0 m-1 btn-remove-foto-accessorio';
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

function renderAcessoriosList() {
    if (!acessoriosList) return;
    acessoriosList.innerHTML = '';
    acessoriosEntries.forEach((entry, index) => {
        const cleanText = (entry.text || '').replace(/\s*\(#[0-9a-fA-F]{6}\)/g, '');
        if (cleanText !== entry.text) {
            entry.text = cleanText;
        }
        const item = document.createElement('div');
        item.className = 'list-group-item';
        item.innerHTML = `
            <div class="d-flex justify-content-between align-items-center">
                <span class="fw-semibold">${cleanText}</span>
                <div class="d-flex gap-1">
                    <button type="button" class="btn btn-outline-info btn-sm btn-add-foto" data-entry="${entry.id}"><i class="bi bi-camera"></i> Adicionar foto</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm btn-edit-acessorio" data-index="${index}"><i class="bi bi-pencil"></i></button>
                    <button type="button" class="btn btn-outline-danger btn-sm btn-remove-acessorio" data-index="${index}"><i class="bi bi-trash"></i></button>
                </div>
            </div>
            <div class="d-flex gap-2 flex-wrap mt-2" data-photos-container="${entry.id}"></div>
        `;
        acessoriosList.appendChild(item);
        const photosContainer = item.querySelector(`[data-photos-container="${entry.id}"]`);
        ensureAcessorioFileInput(entry.id);
        renderAcessoriosPhotos(entry.id, photosContainer);
    });
    const totalPhotos = Object.keys(acessoriosPhotos).reduce((sum, id) => sum + (acessoriosPhotos[id].files.length || 0), 0);
    document.getElementById('resumoFotosEntrada').textContent = totalPhotos.toString();
    _setFieldStatus('statusFotos', totalPhotos > 0);
}

function closeAcessoriosForm() {
    acessoriosQuickForm?.classList.add('d-none');
    acessoriosQuickFields.innerHTML = '';
    acessoriosEditing = null;
}

function openAcessoriosForm(key, index = null) {
    const config = acessoriosConfig[key];
    if (!config) return;
    acessoriosCurrentKey = key;
    acessoriosQuickTitle.textContent = config.title;
    acessoriosQuickFields.innerHTML = '';
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
            otherInput.className = 'form-control form-control-sm mt-2 d-none';
            otherInput.name = otherName;
            otherInput.placeholder = field.otherPlaceholder || 'Especifique';

            input.addEventListener('change', () => {
                const isOther = input.value === 'Outro';
                otherInput.classList.toggle('d-none', !isOther);
                if (!isOther) otherInput.value = '';
            });

            wrapper.appendChild(label);
            wrapper.appendChild(input);
            wrapper.appendChild(otherInput);
            acessoriosQuickFields.appendChild(wrapper);
            return;
        } else if (field.type === 'color_text') {
            wrapper.className = 'col-12';
            const group = document.createElement('div');
            group.className = 'd-flex gap-2 align-items-center';

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
                const selectedHex = normalizeHexColor(colorInput.value);
                input.value = formatAccessoryColorValue(selectedHex);
            });

            input.addEventListener('blur', () => {
                const rawValue = (input.value || '').trim();
                if (!rawValue) return;
                const hexFromText = extractHexFromAccessoryColor(rawValue) || getAccessoryHexByName(rawValue);
                if (hexFromText) {
                    colorInput.value = hexFromText;
                    input.value = formatAccessoryColorValue(hexFromText);
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
            quickColorsDesktop.className = 'd-none d-md-flex flex-nowrap gap-1 mt-2 w-100';
            COMMON_ACCESSORY_COLORS.forEach(color => {
                const quickBtn = document.createElement('button');
                quickBtn.type = 'button';
                quickBtn.className = 'btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1 text-nowrap px-2 py-1';
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
            quickColorsMobile.className = 'dropdown d-md-none mt-2';
            const dropdownId = `acessorioColorQuick_${field.name}_${Date.now()}_${Math.random().toString(36).slice(2, 6)}`;
            quickColorsMobile.innerHTML = `
                <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle w-100 text-start" id="${dropdownId}" data-bs-toggle="dropdown" aria-expanded="false">
                    Cores rápidas
                </button>
                <ul class="dropdown-menu w-100" aria-labelledby="${dropdownId}"></ul>
            `;
            const mobileMenu = quickColorsMobile.querySelector('.dropdown-menu');
            COMMON_ACCESSORY_COLORS.forEach(color => {
                const li = document.createElement('li');
                const mobileBtn = document.createElement('button');
                mobileBtn.type = 'button';
                mobileBtn.className = 'dropdown-item d-flex align-items-center gap-2';
                mobileBtn.innerHTML = `
                    <span class="rounded-circle border" style="width:12px;height:12px;background:${color.hex};"></span>
                    <span>${color.name}</span>
                `;
                mobileBtn.addEventListener('click', () => applyQuickColor(color));
                li.appendChild(mobileBtn);
                mobileMenu.appendChild(li);
            });
            wrapper.appendChild(quickColorsMobile);

            acessoriosQuickFields.appendChild(wrapper);
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
        acessoriosQuickFields.appendChild(wrapper);
    });
    if (index !== null) {
        acessoriosEditing = index;
        const values = acessoriosEntries[index].values || {};
        config.fields.forEach(field => {
            const el = acessoriosQuickFields.querySelector(`[name="${field.name}"]`);
            if (el) el.value = values[field.name] || '';
            if (field.type === 'select_with_other') {
                const otherName = field.otherName || `${field.name}_outro`;
                const otherEl = acessoriosQuickFields.querySelector(`[name="${otherName}"]`);
                const savedValue = (values[field.name] || '').trim();
                const knownOption = (field.options || []).some(opt => opt.value === savedValue && opt.value !== 'Outro');

                if (el) {
                    if (!savedValue || knownOption) {
                        el.value = savedValue;
                    } else {
                        el.value = 'Outro';
                    }
                }

                if (otherEl) {
                    const showOther = el && el.value === 'Outro';
                    otherEl.classList.toggle('d-none', !showOther);
                    if (showOther) {
                        otherEl.value = values[otherName] || (!knownOption ? savedValue : '');
                    }
                }
            }
            if (field.type === 'color_text') {
                const picker = acessoriosQuickFields.querySelector(`[data-color-picker-for="${field.name}"]`);
                const rawColor = values[field.name] || '';
                const hex = extractHexFromAccessoryColor(rawColor) || getAccessoryHexByName(rawColor);
                if (picker && hex) picker.value = hex;
                if (el && hex && !extractHexFromAccessoryColor(rawColor)) {
                    el.value = formatAccessoryColorValue(hex);
                }
            }
        });
    }
    acessoriosQuickForm?.classList.remove('d-none');
}

function handleAcessoriosButtonClick(event) {
    const key = event.currentTarget.dataset.acessorioKey;
    if (!key) return;
    openAcessoriosForm(key);
}

function collectFormValues() {
    const values = {};
    acessoriosQuickFields.querySelectorAll('input, select').forEach(input => {
        if (!input.name) return;
        values[input.name] = input.value.trim();
    });
    return values;
}

function handleAcessoriosSave() {
    const key = acessoriosCurrentKey;
    const config = acessoriosConfig[key];
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
        const hex = extractHexFromAccessoryColor(rawColor) || getAccessoryHexByName(rawColor);
        if (hex) values[field.name] = formatAccessoryColorValue(hex);
    });
    const text = config.format(values);
    if (acessoriosEditing !== null) {
        acessoriosEntries[acessoriosEditing] = { ...acessoriosEntries[acessoriosEditing], text, values, key };
    } else {
        acessoriosEntries.push({ id: generateEntryId(), text, values, key });
    }
    renderAcessoriosList();
    syncAcessoriosInput();
    closeAcessoriosForm();
}

function handleAcessoriosCancel() {
    closeAcessoriosForm();
}

function handleRemoveAcessorio(event) {
    const index = parseInt(event.currentTarget.dataset.index, 10);
    if (Number.isNaN(index)) return;
    const entry = acessoriosEntries[index];
    removeAcessorioFileInput(entry.id);
    acessoriosEntries.splice(index, 1);
    renderAcessoriosList();
    syncAcessoriosInput();
}

function handleEditAcessorio(event) {
    const index = parseInt(event.currentTarget.dataset.index, 10);
    const entry = acessoriosEntries[index];
    if (!entry) return;
    const key = entry.key || 'outro';
    openAcessoriosForm(key, index);
}

function openAcessorioPhotoInput(entryId) {
    acessoriosPhotoTarget = entryId;
    acessoriosPhotoInput.dataset.entryId = entryId;
    acessoriosPhotoInput?.click();
}

function handlePhotoInputChange() {
    const entryId = this.dataset.entryId;
    if (!entryId) return;
    const dt = acessoriosPhotos[entryId] || new DataTransfer();
    Array.from(this.files).forEach(file => dt.items.add(file));
    acessoriosPhotos[entryId] = dt;
    ensureAcessorioFileInput(entryId);
    renderAcessoriosList();
    this.value = '';
}

function handleRemovePhoto(event) {
    const entryId = event.currentTarget.dataset.entry;
    const index = parseInt(event.currentTarget.dataset.index, 10);
    const dt = acessoriosPhotos[entryId];
    if (!dt) return;
    const newDt = new DataTransfer();
    Array.from(dt.files).forEach((file, idx) => {
        if (idx !== index) newDt.items.add(file);
    });
    if (newDt.files.length === 0) {
        delete acessoriosPhotos[entryId];
        removeAcessorioFileInput(entryId);
    } else {
        acessoriosPhotos[entryId] = newDt;
        ensureAcessorioFileInput(entryId);
    }
    renderAcessoriosList();
}

document.querySelectorAll('[data-acessorio-key]').forEach(btn => {
    btn.addEventListener('click', handleAcessoriosButtonClick);
});
acessoriosQuickSave?.addEventListener('click', handleAcessoriosSave);
acessoriosQuickCancel?.addEventListener('click', handleAcessoriosCancel);
acessoriosQuickClose?.addEventListener('click', handleAcessoriosCancel);
document.addEventListener('click', event => {
    const removeBtn = event.target.closest('.btn-remove-acessorio');
    if (removeBtn) handleRemoveAcessorio({ currentTarget: removeBtn });
    const editBtn = event.target.closest('.btn-edit-acessorio');
    if (editBtn) handleEditAcessorio({ currentTarget: editBtn });
    const addPhotoBtn = event.target.closest('.btn-add-foto');
    if (addPhotoBtn) openAcessorioPhotoInput(addPhotoBtn.dataset.entry);
    const removePhotoBtn = event.target.closest('.btn-remove-foto-accessorio');
    if (removePhotoBtn) handleRemovePhoto({ currentTarget: removePhotoBtn });
});
acessoriosPhotoInput?.addEventListener('change', handlePhotoInputChange);
renderAcessoriosList();
syncAcessoriosInput();

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
    const relatoInp  = document.querySelector('textarea[name="relato_cliente"]');
    const acessoriosInp = document.querySelector('textarea[name="acessorios"]');
    const formaPagamentoSel = document.querySelector('select[name="forma_pagamento"]');

    return {
        savedAt: new Date().toISOString(),
        cliente_id: clienteSel?.value || '',
        equipamento_id: equipSel?.value || '',
        tecnico_id: tecnicoSel?.value || '',
        prioridade: prioridadeSel?.value || 'normal',
        status: statusSel?.value || 'aguardando_analise',
        data_entrada: entradaInp?.value || '',
        data_previsao: previsaoInp?.value || '',
        relato_cliente: relatoInp?.value || '',
        acessorios: acessoriosInp?.value || '',
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
        data.acessorios?.trim() ||
        data.forma_pagamento?.trim() ||
        (data.defeitos && data.defeitos.length)
    );
}

function saveDraftNow() {
    if (isEdit) return;
    const data = _collectDraft();
    if (!_hasDraftData(data)) {
        localStorage.removeItem(DRAFT_KEY);
        _setResumoRascunho('Não salvo');
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
        saveDraftNow();
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
        if (Date.now() - savedAt.getTime() > DRAFT_TTL_MS) {
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
    const relatoInp  = document.querySelector('textarea[name="relato_cliente"]');
    const acessoriosInp = document.querySelector('textarea[name="acessorios"]');
    const formaPagamentoSel = document.querySelector('select[name="forma_pagamento"]');

    if (tecnicoSel) tecnicoSel.value = data.tecnico_id || '';
    if (prioridadeSel) prioridadeSel.value = data.prioridade || 'normal';
    if (statusSel) statusSel.value = data.status || 'aguardando_analise';
    if (entradaInp && data.data_entrada) entradaInp.value = data.data_entrada;
    if (previsaoInp) previsaoInp.value = data.data_previsao || '';
    if (relatoInp) relatoInp.value = data.relato_cliente || '';
    if (acessoriosInp) acessoriosInp.value = data.acessorios || '';
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

// Rascunho automático para nova OS
if (!isEdit) {
    const draftData = _loadDraft();
    const draftAlert = document.getElementById('osDraftAlert');
    if (draftData && draftAlert) {
        draftAlert.classList.remove('d-none');
        const savedAtLabel = new Date(draftData.savedAt).toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' });
        _setResumoRascunho('Rascunho em ' + savedAtLabel);
        document.getElementById('btnRestaurarRascunho')?.addEventListener('click', () => {
            draftAlert.classList.add('d-none');
            _applyDraft(draftData);
            updateResumo();
            scheduleDraftSave();
        });
        document.getElementById('btnDescartarRascunho')?.addEventListener('click', () => {
            localStorage.removeItem(DRAFT_KEY);
            draftAlert.classList.add('d-none');
            _setResumoRascunho('Não salvo');
        });
    } else {
        _setResumoRascunho('Não salvo');
    }

    document.getElementById('btnLimparRascunho')?.addEventListener('click', () => {
        localStorage.removeItem(DRAFT_KEY);
        _setResumoRascunho('Não salvo');
    });
}

document.getElementById('formOs')?.addEventListener('submit', () => {
    localStorage.removeItem(DRAFT_KEY);
    _setResumoRascunho('Não salvo');
});

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

// ─── Carrega fotos do equipamento ──────────────────────────────────────────
function carregarFotosEquipamento(equipId, equipData) {
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
        const corNome = equipData.cor || 'Cor não informada';
        if (colorSwatch) colorSwatch.style.background = corHex;
        if (colorName) colorName.textContent = corNome;
        if (colorInfo) colorInfo.classList.remove('d-none');
    }

    // Busca fotos via AJAX
    fetch(`${BASE_URL}equipamentos/fotos/${equipId}`)
    .then(r => r.json())
    .then(fotos => {
        minis.innerHTML = '';
        if (fotos.length === 0) {
            mainBox.classList.add('d-none');
            placeholder.classList.remove('d-none');
            placeholder.classList.add('d-flex');
            if (equipData?.cor_hex) {
                placeholder.style.background = equipData.cor_hex;
                placeholder.style.border = '2px solid rgba(0,0,0,0.2)';
                placeholder.style.color = '#fff';
            }
            return;
        }

        // Foto principal
        const principal = fotos.find(f => f.is_principal == 1) || fotos[0];
        img.src = principal.url;
        document.getElementById('fotoPrincipalLink').setAttribute('data-img-src', principal.url);
        mainBox.classList.remove('d-none');
        placeholder.classList.add('d-none');
        placeholder.classList.remove('d-flex');
        placeholder.style.background = 'rgba(255,255,255,0.04)';
        placeholder.style.color = '';

        // Miniaturas
                fotos.forEach((f, i) => {
                    const el = document.createElement('div');
                    el.className = 'border rounded overflow-hidden shadow-sm hover-elevate cursor-pointer';
                    el.style.cssText = 'width: 45px; height: 45px; cursor: pointer; transition: all 0.2s;';
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

// ─── Select de cliente → carrega equipamentos ─────────────────────────────
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
        updateResumo();
        scheduleDraftSave();
        return;
    }

    // Atualiza cliente_id para o modal
    const hiddenCli = document.getElementById('novoEquipClienteId');
    if (hiddenCli) hiddenCli.value = clienteId;
    window._osClienteId = clienteId;

    fetch(`${BASE_URL}equipamentos/por-cliente/${clienteId}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(equipamentos => {
        const autoSelectId = equipamentos.length === 1 ? equipamentos[0].id : null;
        if (equipamentos.length === 0) {
            equipamentoSelect.innerHTML = '<option value="">Nenhum equipamento vinculado</option>';
        } else {
            equipamentoSelect.innerHTML = '<option value="">Selecione o equipamento...</option>';
            equipamentos.forEach(eq => {
                const nome = (eq.marca_nome || '') + ' ' + (eq.modelo_nome || '') + ' (' + (eq.tipo_nome || eq.tipo || '') + ')';
                const opt  = new Option(nome, eq.id);
                opt.dataset.tipo      = eq.tipo_id || '';
                opt.dataset.marca     = eq.marca_nome || '';
                opt.dataset.modelo    = eq.modelo_nome || '';
                opt.dataset.serie     = eq.numero_serie || '';
                opt.dataset.cor       = eq.cor || '';
                opt.dataset.cor_hex   = eq.cor_hex || '';
                opt.dataset.tipo_nome = eq.tipo_nome || '';
                equipamentoSelect.appendChild(opt);
            });
        }
        equipamentoSelect.disabled = false;
        // Re-inicializa Select2 no equipamento
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
        updateResumo();
        scheduleDraftSave();
    })
    .catch(() => {
        equipamentoSelect.innerHTML = '<option value="">Erro ao carregar.</option>';
        equipamentoSelect.disabled = false;
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

// ─── Handler de mudança de equipamento ──────────────────────────────────
function _onEquipamentoChange(id, opt) {
    const tipoId = opt ? opt.getAttribute('data-tipo') : null;
    carregarDefeitos(tipoId);
    if (id) {
        carregarFotosEquipamento(id, {
            marca:  opt?.dataset?.marca,
            modelo: opt?.dataset?.modelo,
            serie:  opt?.dataset?.serie,
            tipo:   opt?.dataset?.tipo_nome,
            cor:    opt?.dataset?.cor,
            cor_hex: opt?.dataset?.cor_hex
        });
    } else {
        hideSidebar();
    }
    updateResumo();
    scheduleDraftSave();
}

// ─── Listener vanilla do equipamentoSelect (usado quando Select2 ainda não foi inicializado) ───
const equipSelect = document.getElementById('equipamentoSelect');
if (equipSelect) {
    equipSelect.addEventListener('change', function() {
        // Apenas disparado quando Select2 não está ativo
        if (!$(this).data('select2')) {
            _onEquipamentoChange(this.value, this.options[this.selectedIndex]);
        }
    });

    // Na edição, carrega automaticamente
    if (isEdit && equipSelect.value) {
        const opt = equipSelect.options[equipSelect.selectedIndex];
        const tipoId = opt ? opt.getAttribute('data-tipo') : null;
        if (tipoId) carregarDefeitos(tipoId);
        if (equipSelect.value) {
            carregarFotosEquipamento(equipSelect.value, {
                marca:  opt?.dataset.marca,
                modelo: opt?.dataset.modelo,
                serie:  opt?.dataset.serie,
                tipo:   opt?.dataset.tipo_nome,
                cor:    opt?.dataset.cor,
                cor_hex: opt?.dataset.cor_hex
            });
        }
    }
}

// Atualiza resumo e rascunho conforme alterações no formulário
['input', 'change'].forEach(evt => {
    document.querySelector('textarea[name="relato_cliente"]')?.addEventListener(evt, () => {
        updateResumo();
        scheduleDraftSave();
    });
    document.querySelector('textarea[name="acessorios"]')?.addEventListener(evt, () => {
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

// ─── Preview fotos de entrada ─────────────────────────────────────────────
const osFotosExistingData = <?= json_encode(array_map(fn($f) => ['url' => $f['url']], $fotos_entrada ?? [])) ?>;
const osFotosMaxFiles = 4;
const fotosEntradaInput = document.getElementById('fotosEntradaInput');
const osFotosPreview = document.getElementById('osFotosPreview');
const osFotosExisting = document.getElementById('osFotosExisting');
const osFotosDropzone = document.getElementById('osFotosDropzone');
const btnFotosEscolher = document.getElementById('btnFotosEscolher');
const btnLimparFotos = document.getElementById('btnLimparFotos');
const osDataTransfer = new DataTransfer();

function renderExistingFotos() {
    if (!osFotosExisting) return;
    osFotosExisting.innerHTML = '';
    osFotosExistingData.forEach((foto, idx) => {
        const thumb = document.createElement('div');
        thumb.className = 'position-relative border rounded overflow-hidden cursor-pointer';
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
                <button type="button" class="btn btn-sm btn-outline-danger position-absolute top-0 end-0 m-1 btn-remover-foto-nova" data-index="${index}">
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
    const totalPhotos = osFotosExistingData.length + osDataTransfer.files.length;
    _setFieldStatus('statusFotos', totalPhotos > 0);
    document.getElementById('resumoFotosEntrada').textContent = totalPhotos.toString();
    updateResumo();
}

function clearNewFotos() {
    osDataTransfer.items.clear();
    fotosEntradaInput.value = '';
    renderNewFotos();
    updatePhotoState();
    scheduleDraftSave();
}

osFotosDropzone?.addEventListener('click', () => fotosEntradaInput?.click());
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
    const files = Array.from(e.dataTransfer.files).slice(0, osFotosMaxFiles);
    files.forEach(file => osDataTransfer.items.add(file));
    fotosEntradaInput.files = osDataTransfer.files;
    renderNewFotos();
    updatePhotoState();
    scheduleDraftSave();
});
btnFotosEscolher?.addEventListener('click', () => fotosEntradaInput?.click());
btnLimparFotos?.addEventListener('click', clearNewFotos);

fotosEntradaInput?.addEventListener('change', function() {
    const incoming = Array.from(this.files);
    if (osDataTransfer.files.length + incoming.length > osFotosMaxFiles) {
        alert(`Você pode enviar até ${osFotosMaxFiles} fotos no total.`);
        return;
    }
    incoming.forEach(file => osDataTransfer.items.add(file));
    this.files = osDataTransfer.files;
    renderNewFotos();
    updatePhotoState();
});

osFotosPreview?.addEventListener('click', function(event) {
    const remover = event.target.closest('.btn-remover-foto-nova');
    if (!remover) return;
    const index = parseInt(remover.dataset.index, 10);
    const dt = new DataTransfer();
    Array.from(osDataTransfer.files).forEach((file, idx) => {
        if (idx !== index) dt.items.add(file);
    });
    osDataTransfer.items.clear();
    Array.from(dt.files).forEach(f => osDataTransfer.items.add(f));
    fotosEntradaInput.files = osDataTransfer.files;
    renderNewFotos();
    updatePhotoState();
});

const osExistingFotosCount = osFotosExistingData.length;
const updateResumoPhotos = () => {
    const total = osExistingFotosCount + osDataTransfer.files.length;
    document.getElementById('resumoFotosEntrada').textContent = total.toString();
    _setFieldStatus('statusFotos', total > 0);
};
renderExistingFotos();
renderNewFotos();
updateResumoPhotos();

// ─── Modal: Cadastrar Novo Equipamento ─────────────────────────────────────
const btnNovoEquip = document.getElementById('btnNovoEquipamento');
if (btnNovoEquip) {
    btnNovoEquip.addEventListener('click', function() {
        // Injeta o cliente selecionado no formulário do modal
        const clienteId = document.getElementById('clienteOsSelect').value;
        if (!clienteId) {
            alert('Selecione um cliente primeiro para cadastrar o equipamento.');
            return;
        }
        let hiddenInput = document.getElementById('novoEquipClienteId');
        if (!hiddenInput) {
            hiddenInput = document.createElement('input');
            hiddenInput.type  = 'hidden';
            hiddenInput.name  = 'cliente_id';
            hiddenInput.id    = 'novoEquipClienteId';
            document.getElementById('formNovoEquipAjax').appendChild(hiddenInput);
        }
        hiddenInput.value = clienteId;

        new bootstrap.Modal(document.getElementById('modalNovoEquipamento')).show();
        
        // Inicializa Select2 para elementos dentro do modal
        $('.select2-modal').select2({
            theme: 'bootstrap-5',
            dropdownParent: $('#modalNovoEquipamento'),
            width: '100%',
            placeholder: 'Escolha...'
        });
    });
}

// ─── Cadastro Rápido de Marcas e Modelos (Dentro da OS) ────────────────────
const modalNovaMarca = new bootstrap.Modal(document.getElementById('modalNovaMarcaOS'));
const modalNovoModelo = new bootstrap.Modal(document.getElementById('modalNovoModeloOS'));

document.getElementById('btnNovaMarcaOS')?.addEventListener('click', () => modalNovaMarca.show());
document.getElementById('btnNovoModeloOS')?.addEventListener('click', () => {
    const marcaId = $('#novoEquipMarca').val();
    if (!marcaId) { alert('Selecione uma marca primeiro!'); return; }
    
    // Mostra o nome da marca no modal para conferência
    const marcaNome = $('#novoEquipMarca option:selected').text();
    document.getElementById('displayMarcaOS').value = marcaNome;
    
    modalNovoModelo.show();
});

document.getElementById('btnSalvarMarcaOS')?.addEventListener('click', function() {
    const nome = document.getElementById('inputNovaMarcaOS').value.trim();
    if (!nome) return;

    this.disabled = true;
    const fd = new FormData();
    fd.append('nome', nome);
    fd.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

    fetch(`${BASE_URL}equipamentosmarcas/salvar_ajax`, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            const opt = new Option(res.nome, res.id, true, true);
            $('#novoEquipMarca').append(opt).trigger('change');
            modalNovaMarca.hide();
            document.getElementById('inputNovaMarcaOS').value = '';
        } else {
            const err = document.getElementById('errorNovaMarcaOS');
            err.innerText = res.message;
            err.classList.remove('d-none');
        }
    })
    .finally(() => this.disabled = false);
});

document.getElementById('btnSalvarModeloOS')?.addEventListener('click', function() {
    const nome = document.getElementById('inputNovoModeloOS').value.trim();
    const marcaId = $('#novoEquipMarca').val();
    if (!nome || !marcaId) return;

    this.disabled = true;
    const fd = new FormData();
    fd.append('nome', nome);
    fd.append('marca_id', marcaId);
    fd.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

    fetch(`${BASE_URL}equipamentosmodelos/salvar_ajax`, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            const opt = new Option(res.nome, res.id, true, true);
            $('#novoEquipModelo').append(opt).trigger('change');
            modalNovoModelo.hide();
            document.getElementById('inputNovoModeloOS').value = '';
            document.getElementById('sugestoesNovoModeloOS').classList.add('d-none');
        } else {
            const err = document.getElementById('errorNovoModeloOS');
            err.innerText = res.message;
            err.classList.remove('d-none');
        }
    })
    .finally(() => this.disabled = false);
});

// ─── Autocomplete inteligente no modal "Novo Modelo" ─────────────────────────
(function() {
    let debounceTimerModelo = null;
    const inputModelo    = document.getElementById('inputNovoModeloOS');
    const sugestoesBox   = document.getElementById('sugestoesNovoModeloOS');
    const spinnerModelo  = document.getElementById('spinnerNovoModeloOS');
    const errorModelo    = document.getElementById('errorNovoModeloOS');

    if (!inputModelo) return;

    function renderSugestoes(groups) {
        sugestoesBox.innerHTML = '';
        let total = 0;

        groups.forEach(group => {
            if (!group.children || group.children.length === 0) return;

            // Cabeçalho do grupo
            const header = document.createElement('div');
            header.className = 'list-group-item list-group-item-secondary py-1 px-3';
            header.style.cssText = 'font-size:0.7rem; font-weight:700; letter-spacing:0.5px; text-transform:uppercase; pointer-events:none;';
            const icon = group.text.includes('Cadastrados') ? '📋' : '🌐';
            header.textContent = icon + ' ' + group.text.replace(/^[📋🌐] /, '');
            sugestoesBox.appendChild(header);

            // Itens do grupo
            group.children.forEach(item => {
                let parts = [];
                if (item.marca) parts.push(item.marca);
                if (item.tipo) parts.push(item.tipo);
                let subtitle = parts.length > 0 ? `<div style="font-size:0.75rem; color:#6c757d; margin-top:-2px;">(${parts.join(' - ')})</div>` : '';

                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'list-group-item list-group-item-action py-2 px-3 d-flex align-items-start gap-2';
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
                    sugestoesBox.classList.add('d-none');
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
            sugestoesBox.classList.remove('d-none');
        } else {
            sugestoesBox.innerHTML = '<div class="list-group-item text-muted small py-2 px-3"><i class="bi bi-info-circle me-1"></i>Nenhuma sugestão. Digite e salve manualmente.</div>';
            sugestoesBox.classList.remove('d-none');
        }
    }

    inputModelo.addEventListener('input', function() {
        const q = this.value.trim();
        clearTimeout(debounceTimerModelo);
        errorModelo.classList.add('d-none');

        if (q.length < 3) {
            sugestoesBox.classList.add('d-none');
            spinnerModelo.classList.add('d-none');
            return;
        }

        spinnerModelo.classList.remove('d-none');
        sugestoesBox.classList.add('d-none');

        debounceTimerModelo = setTimeout(() => {
            const marcaId   = $('#novoEquipMarca').val();
            const marcaNome = $('#novoEquipMarca option:selected').text().trim();
            const tipoNome  = $('#novoEquipTipo option:selected').text().trim();

            const params = new URLSearchParams({
                q:        q,
                marca_id: marcaId || '',
                marca:    marcaNome || '',
                tipo:     tipoNome !== 'Selecione o Tipo...' ? tipoNome : ''
            });

            fetch(`${BASE_URL}api/modelos/buscar?${params}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(data => {
                spinnerModelo.classList.add('d-none');
                if (data.results && data.results.length > 0) {
                    renderSugestoes(data.results);
                } else {
                    sugestoesBox.classList.add('d-none');
                }
            })
            .catch(() => spinnerModelo.classList.add('d-none'));
        }, 400);
    });

    // Fecha dropdown ao clicar fora
    document.addEventListener('click', e => {
        if (!inputModelo.contains(e.target) && !sugestoesBox.contains(e.target)) {
            sugestoesBox.classList.add('d-none');
        }
    });

    // Limpa ao fechar o modal
    document.getElementById('modalNovoModeloOS')?.addEventListener('hidden.bs.modal', () => {
        inputModelo.value = '';
        sugestoesBox.classList.add('d-none');
        errorModelo.classList.add('d-none');
    });
})();


// Lógica de Cores no Modal (Igual ao cadastro de equipamentos)
// ═══════════════════════════════════════════════════════════
// SELETOR DE COR PROFISSIONAL (OS Modal)
// ═══════════════════════════════════════════════════════════

const PROFESSIONAL_COLORS_OS = [
    { category: 'Neutras (Preto, Branco, Cinza)', colors: [
        { hex: '#000000', name: 'Preto' }, { hex: '#2F4F4F', name: 'Grafite' }, { hex: '#41464D', name: 'Graphite' },
        { hex: '#5C5B57', name: 'Titanium' }, { hex: '#696969', name: 'Cinza Escuro' }, { hex: '#BEBEBE', name: 'Cinza' },
        { hex: '#FFFFFF', name: 'Branco' }, { hex: '#F8F8FF', name: 'Branco Gelo' }, { hex: '#FFFFF0', name: 'Marfim' },
    ]},
    { category: 'Azuis e Marinhos', colors: [
        { hex: '#191970', name: 'Azul Meia-Noite' }, { hex: '#000080', name: 'Azul Marinho' }, { hex: '#0000FF', name: 'Azul Puro' },
        { hex: '#4169E1', name: 'Azul Real' }, { hex: '#1E90FF', name: 'Azul Céu' }, { hex: '#87CEEB', name: 'Azul Celeste' },
        { hex: '#5F9EA0', name: 'Azul Petróleo' },
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
    { category: 'Roxos, Pinks e Lilás', colors: [
        { hex: '#4B0082', name: 'Índigo' }, { hex: '#2D1B69', name: 'Violeta' }, { hex: '#800080', name: 'Roxo Puro' },
        { hex: '#DA70D6', name: 'Lilás' }, { hex: '#FF1493', name: 'Pink' }, { hex: '#AA336A', name: 'Rose Gold' },
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
    $('#corNomeRealOS').val(name);

    $('#corHexPickerOS').val(hex);
    $('#corNomeInputOS').val(name);

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
    const nearest = all.sort((a,b) => a.d - b.d).slice(0, 6);
    
    const grid = document.getElementById('coresProximasGridOS');
    if (grid) {
        grid.innerHTML = '';
        nearest.forEach(c => {
            const b = document.createElement('button');
            b.type = 'button'; b.className = 'rounded-circle border';
            b.style.cssText = `width:24px;height:24px;background:${c.hex};cursor:pointer;`;
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
                <button class="accordion-button collapsed py-2 px-1 bg-transparent shadow-none fw-bold text-muted" type="button" data-bs-toggle="collapse" data-bs-target="#${itemId}" aria-expanded="false" aria-controls="${itemId}" style="font-size: 0.8rem;">
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
                                <button type="button" class="list-group-item list-group-item-action py-2 px-3 d-flex align-items-center gap-3 border-0 ${isSelected ? 'active bg-primary bg-opacity-10 text-primary fw-bold' : ''}" 
                                        onclick="updateColorUIOS('${c.hex}', '${c.name}')" style="font-size: 0.82rem;">
                                    <div class="rounded-circle shadow-sm border border-light" 
                                         style="width: 24px; height: 24px; background: ${c.hex}; flex-shrink: 0;"></div>
                                    <span class="flex-grow-1 text-start">${c.name}</span>
                                    <small class="text-muted font-monospace opacity-50" style="font-size: 0.7rem;">${c.hex}</small>
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

$('#corNomeInputOS').on('input', function() {
    $('#corNomeRealOS').val(this.value);
});

// Init OS Color
buildCatalogOS();
updateColorUIOS('#1A1A1A', 'Preto');

// ─── LÓGICA DE DETECÇÃO DE COR INTELIGENTE NA IMAGEM (OS Modal) ───────────────
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

function detectDominantColorOS(sourceCanvas) {
    try {
        const ctx = sourceCanvas.getContext('2d', { willReadFrequently: true });
        const w = sourceCanvas.width;
        const h = sourceCanvas.height;
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

        let bestMatch = { hex: dominantHex, name: 'Personalizada' };
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
        $('#smartColorContainerOS').removeClass('d-none');

    } catch (e) {
        console.warn('Erro na detecção de cor: ', e);
    }
}

// ─── LÓGICA DE SENHA E ACESSÓRIOS (MODAL OS) ───────────────────────
$(document).on('click', '.btn-senha-tipo-os', function() {
    const placeholder = $(this).data('placeholder');
    $('#inputSenhaAcessoOS').attr('placeholder', placeholder).focus();
    $('.btn-senha-tipo-os').removeClass('btn-secondary text-white').addClass('btn-light border');
    $(this).removeClass('btn-light border').addClass('btn-secondary text-white');
});

$(document).on('click', '.btn-quick-acessorio-os', function() {
    const value = $(this).text().replace('+ ', '').trim();
    const textarea = $('#textareaAcessoriosOS');
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

// ─── Lógica de Câmera, Galeria e Cropper ─────────────────────────────
const modalCamera    = new bootstrap.Modal(document.getElementById('modalCamera'));
const modalCrop      = new bootstrap.Modal(document.getElementById('modalCropEquip'));
const videoCamera    = document.getElementById('videoCamera');
const canvasCamera   = document.getElementById('canvasCamera');
const btnCapturar     = document.getElementById('btnCapturar');
const novoEquipFoto  = document.getElementById('novoEquipFoto');
const previewImg     = document.getElementById('novoEquipFotoImg');
const previewDiv     = document.getElementById('novoEquipFotoPreview');
const imgToCrop      = document.getElementById('imgToCrop');
let streamCamera     = null;
let cropper          = null;

document.getElementById('btnAbrirGaleria')?.addEventListener('click', () => novoEquipFoto.click());

document.getElementById('btnAbrirCamera')?.addEventListener('click', async () => {
    try {
        streamCamera = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
        videoCamera.srcObject = streamCamera;
        modalCamera.show();
    } catch (err) {
        alert('Não foi possível acessar a câmera: ' + err.message);
    }
});

document.getElementById('modalCamera').addEventListener('hidden.bs.modal', () => {
    if (streamCamera) {
        streamCamera.getTracks().forEach(track => track.stop());
        streamCamera = null;
    }
});

function openCropper(source) {
    imgToCrop.src = source;
    modalCrop.show();
}

document.getElementById('modalCropEquip').addEventListener('shown.bs.modal', () => {
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
});

document.getElementById('modalCropEquip').addEventListener('hidden.bs.modal', () => {
    if (cropper) {
        cropper.destroy();
        cropper = null;
    }
});

document.getElementById('btnRotateLeft')?.addEventListener('click', () => cropper.rotate(-90));
document.getElementById('btnRotateRight')?.addEventListener('click', () => cropper.rotate(90));

btnCapturar?.addEventListener('click', () => {
    const context = canvasCamera.getContext('2d');
    canvasCamera.width  = videoCamera.videoWidth;
    canvasCamera.height = videoCamera.videoHeight;
    context.drawImage(videoCamera, 0, 0, canvasCamera.width, canvasCamera.height);
    
    const dataUrl = canvasCamera.toDataURL('image/jpeg');
    modalCamera.hide();
    openCropper(dataUrl);
});

document.getElementById('btnConfirmCrop')?.addEventListener('click', () => {
    const canvas = cropper.getCroppedCanvas({
        width: 1024, // Limita o tamanho para não sobrecarregar
        height: 1024,
        imageSmoothingEnabled: true,
        imageSmoothingQuality: 'high',
    });

    canvas.toBlob((blob) => {
        const file = new File([blob], "equipamento_perfil.jpg", { type: "image/jpeg" });
        const dt = new DataTransfer();
        dt.items.add(file);
        novoEquipFoto.files = dt.files;
        
        detectDominantColorOS(canvas); // <--- Inicia a detecção de cor automática na OS

        // Preview Final
        previewImg.src = URL.createObjectURL(blob);
        previewDiv.style.display = 'block';
        document.getElementById('fotoVaziaOS').style.display = 'none';
        modalCrop.hide();
    }, 'image/jpeg', 0.9);
});

document.getElementById('btnRemoverFotoNovoEquip')?.addEventListener('click', function() {
    novoEquipFoto.value = '';
    previewDiv.style.display = 'none';
    previewImg.src = '';
    document.getElementById('fotoVaziaOS').style.display = 'block';
});

// Preview/Editor ao escolher da galeria
novoEquipFoto?.addEventListener('change', function() {
    if (this.files && this.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            openCropper(e.target.result);
        };
        reader.readAsDataURL(this.files[0]);
    }
});

// ─── Select2 Híbrido: Modelos via API ──────────────────────────────────────
function initModeloSelect2() {
    var modeloSel = $('#novoEquipModelo');

    if (modeloSel.hasClass("select2-hidden-accessible")) {
        modeloSel.select2('destroy').off('change');
    }

    modeloSel.select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Busque ou selecione o modelo...',
        allowClear: true,
        dropdownParent: $('#modalNovoEquipamento'),
        tags: true, // HABILITA EDIÇÃO E NOVAS TAGS LIVRES
        createTag: function(params) {
            var term = $.trim(params.term);
            if (term === '') return null;
            return {
                id: term,
                text: term,
                newTag: true
            };
        },
        ajax: {
            url: BASE_URL + 'api/modelos/buscar',
            dataType: 'json',
            delay: 400,
            data: function (params) {
                var tipoNome = $('#novoEquipTipo option:selected').text().trim();
                return {
                    q:        params.term,
                    marca_id: $('#novoEquipMarca').val(),
                    marca:    $('#novoEquipMarca option:selected').text().trim(),
                    tipo:     tipoNome !== 'Selecione o Tipo...' ? tipoNome : ''
                };
            },
            processResults: function (data) {
                return data;
            },
            cache: true
        },
        minimumInputLength: 3,
        language: {
            inputTooShort: function (args) {
                var restante = args.minimum - args.input.length;
                return `Digite mais ${restante} caractere(s) para buscar...`;
            },
            searching: function() { return '<i class="bi bi-globe2 me-1"></i> Buscando modelos na internet...'; },
            noResults: function()  { return 'Nenhuma sugestão encontrada. Use o botão <strong>+ Novo</strong> para cadastrar manualmente.'; },
            errorLoading: function() { return 'Erro ao consultar. Verifique sua conexão.'; }
        },
        templateResult: function (data) {
            if (data.loading) return data.text;
            if (data.children) return data.text;
            
            if (data.newTag) {
                return $(`
                <div>
                    <strong class="d-block text-primary"><i class="bi bi-pencil-square me-1"></i> "${data.text}"</strong>
                    <small class="text-muted" style="font-size: 0.75rem;">Usar este nome (edição manual)</small>
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
        var data = e.params.data;
        // Armazena o nome real do modelo externo para auto-cadastro no backend
        // Se for newTag, já vai salvar pelo próprio texto sendo o ID
        if (data.id && String(data.id).indexOf('EXT|') === 0) {
            $('#novoEquipModeloNomeExt').val(data.text);
        } else {
            $('#novoEquipModeloNomeExt').val('');
        }
    }).on('select2:open', function () {
        // Ao abrir, preenche a barra de pesquisa com o modelo atualmente selecionado
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
$('#novoEquipMarca').on('change', function() {
    var marcaId = $(this).val();
    if (marcaId) {
        initModeloSelect2();
    } else {
        if ($('#novoEquipModelo').hasClass("select2-hidden-accessible")) {
            $('#novoEquipModelo').select2('destroy').html('<option value="">Selecione a marca primeiro...</option>');
        }
    }
});

// Salvar equipamento via AJAX
document.getElementById('btnSalvarNovoEquip')?.addEventListener('click', function() {
    const form   = document.getElementById('formNovoEquipAjax');
    const errors = document.getElementById('modalEquipErrors');
    errors.classList.add('d-none');

    const formData = new FormData(form);

    // Se for modelo externo (Ponte), enviamos o texto original para auto-cadastro
    const modeloId = $('#novoEquipModelo').val();
    if (modeloId && modeloId.startsWith('EXT|')) {
        formData.append('modelo_nome_ext', $('#novoEquipModelo option:selected').text());
    }
    fetch(`${BASE_URL}equipamentos/salvar-ajax`, {
        method: 'POST',
        body:   formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(res => {
        if (res.status !== 'success') {
            errors.innerHTML = Object.values(res.errors || {}).join('<br>');
            errors.classList.remove('d-none');
            return;
        }

        const eq = res.equipamento;
        const nome = (eq.marca_nome || '') + ' ' + (eq.modelo_nome || '') + ' (' + (eq.tipo_nome || '') + ')';

        // Adiciona ao select de equipamentos
        const sel = document.getElementById('equipamentoSelect');
        const opt = new Option(nome, eq.id, true, true);
        opt.dataset.tipo      = eq.tipo_id || '';
        opt.dataset.marca     = eq.marca_nome || '';
        opt.dataset.modelo    = eq.modelo_nome || '';
        opt.dataset.serie     = eq.numero_serie || '';
        opt.dataset.cor       = eq.cor || '';
        opt.dataset.cor_hex   = eq.cor_hex || '';
        opt.dataset.tipo_nome = eq.tipo_nome || '';
        sel.appendChild(opt);
        sel.value = eq.id;
        carregarFotosEquipamento(eq.id, { marca: eq.marca_nome, modelo: eq.modelo_nome, tipo: eq.tipo_nome, cor: eq.cor, cor_hex: eq.cor_hex });

        // Carrega defeitos para o novo tipo
        if (eq.tipo_id) carregarDefeitos(eq.tipo_id);

        // Fecha modal
        bootstrap.Modal.getInstance(document.getElementById('modalNovoEquipamento'))?.hide();
        form.reset();
        document.getElementById('novoEquipFotoPreview').style.display = 'none';
    })
    .catch(() => {
        errors.innerHTML = 'Erro inesperado. Tente novamente.';
        errors.classList.remove('d-none');
    });
});

// ─── carregarDefeitos ──────────────────────────────────────────────────────
function carregarDefeitos(tipoId) {
    const section   = document.getElementById('defeitosSection');
    const container = document.getElementById('defeitosContainer');
    if (!tipoId) { section.style.display = 'none'; return; }

    container.innerHTML = '<div class="text-muted small"><i class="bi bi-hourglass-split me-1"></i>Carregando defeitos...</div>';
    section.style.display = '';

    const fd = new FormData();
    fd.append('tipo_id', tipoId);
    fd.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

    fetch(BASE_URL + 'equipamentosdefeitos/por-tipo', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(defeitos => {
        if (defeitos.length === 0) {
            container.innerHTML = `<span class="text-muted small"><i class="bi bi-info-circle me-1"></i>Nenhum defeito comum cadastrado para este tipo. <a href="${BASE_URL}equipamentosdefeitos" target="_blank">Cadastrar defeitos</a></span>`;
            return;
        }
        const hw = defeitos.filter(d => d.classificacao === 'hardware');
        const sw = defeitos.filter(d => d.classificacao === 'software');
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
                           data-nome="${d.nome.replace(/"/g,'&quot;')}"
                           data-desc="${(d.descricao||'').replace(/"/g,'&quot;')}">
                    <label class="form-check-label d-flex align-items-center" for="def_${d.id}">
                        <div class="flex-grow-1">
                            <strong style="font-size:0.85rem;">${d.nome}</strong>
                            ${d.descricao ? `<br><small class="text-muted">${d.descricao}</small>` : ''}
                        </div>
                        <button type="button" class="btn btn-sm btn-link p-0 text-warning ms-2 btn-ver-procedimentos-os"
                                data-id="${d.id}" data-nome="${d.nome.replace(/"/g,'&quot;')}" title="Ver Procedimentos">
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
                const relato = document.querySelector('textarea[name="relato_cliente"]');
                const nome   = this.getAttribute('data-nome');
                const desc   = this.getAttribute('data-desc');
                const tag    = `[DEFEITO: ${nome}]${desc ? ' - ' + desc : ''}`;
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

        // Botão de procedimentos
        container.querySelectorAll('.btn-ver-procedimentos-os').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                abrirProcedimentosViewOnly(this.dataset.id, this.dataset.nome);
            });
        });
        _applyPendingDefeitos();
        updateResumo();
        scheduleDraftSave();
    })
    .catch(() => { container.innerHTML = '<span class="text-danger small">Erro ao carregar defeitos.</span>'; });
}

// ─── Modal de visualização de procedimentos ───────────────────────────────
function abrirProcedimentosViewOnly(defeitoId, nome) {
    const modalHtml = `
    <div class="modal fade" id="modalViewProcedimentos" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content glass-card">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title"><i class="bi bi-journal-text text-warning me-2"></i>Procedimentos: ${nome}</h5>
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
        modalEl.querySelector('.modal-title').innerHTML = `<i class="bi bi-journal-text text-warning me-2"></i>Procedimentos: ${nome}`;
    }

    const listDiv = modalEl.querySelector('#listProcOS');
    listDiv.innerHTML = '<div class="text-center py-3"><div class="spinner-border text-warning spinner-border-sm"></div></div>';

    new bootstrap.Modal(modalEl).show();

    fetch(BASE_URL + 'equipamentosdefeitos/procedimentos/' + defeitoId)
    .then(r => r.json())
    .then(procs => {
        if (!procs.length) {
            listDiv.innerHTML = '<p class="text-muted small text-center my-3">Nenhum procedimento cadastrado.</p>';
        } else {
            listDiv.innerHTML = '';
            procs.forEach((p, i) => {
                listDiv.innerHTML += `
                    <div class="p-2 rounded" style="background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.05);">
                        <span class="badge text-bg-warning rounded-pill me-2">${i+1}</span>
                        <span class="small">${p.descricao}</span>
                    </div>`;
            });
        }
    });
}

// ─── Modal de Visualização de Imagem (Lightbox) ───────────────────────────
updateResumo();
document.addEventListener('DOMContentLoaded', function() {
    const modalHtml = `
    <div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true" style="z-index: 2000;">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content bg-transparent border-0">
                <div class="modal-body text-center p-0 position-relative">
                    <div class="d-inline-block position-relative">
                        <button type="button" class="btn-close position-absolute" data-bs-dismiss="modal" aria-label="Close" style="top: 10px; right: 10px; z-index: 2055; filter: invert(1); opacity: 1; background-color: rgba(0,0,0,0.6); border-radius: 50%; padding: 0.8rem; box-shadow: 0 4px 12px rgba(0,0,0,0.5);"></button>
                        <img src="" id="modalImagePreview" class="img-fluid rounded shadow-lg" style="max-height: 85vh; object-fit: contain; background: rgba(0,0,0,0.9);">
                    </div>
                </div>
            </div>
        </div>
    </div>`;
    document.body.insertAdjacentHTML('beforeend', modalHtml);

    const imageModal = document.getElementById('imageModal');
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
});
</script>
<?= $this->endSection() ?>
