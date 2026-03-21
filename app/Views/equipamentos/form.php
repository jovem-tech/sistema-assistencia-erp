<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php $isEdit = isset($equipamento); ?>

<div class="equip-form-page ds-form-layout">
<div class="page-header d-flex justify-content-between align-items-center">
    <div class="d-flex align-items-center gap-3">
        <h2><i class="bi bi-<?= $isEdit ? 'pencil' : 'plus-lg' ?> me-2"></i><?= $title ?></h2>
        <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('equipamentos')">Ajuda</button>
    </div>
    <a href="<?= base_url('equipamentos') ?>" class="btn btn-outline-secondary" data-back-default="<?= base_url('equipamentos') ?>"><i class="bi bi-arrow-left me-1"></i>Voltar</a>
</div>

<div class="card glass-card">
    <div class="card-body">
        <form action="<?= $isEdit ? base_url('equipamentos/atualizar/' . $equipamento['id']) : base_url('equipamentos/salvar') ?>" method="POST" enctype="multipart/form-data">
            <?= csrf_field() ?>
    <style>
        .custom-color-accordion .accordion-button {
            transition: all 0.2s ease;
        }
        .custom-color-accordion .accordion-button:not(.collapsed) {
            color: var(--bs-primary) !important;
            background-color: rgba(var(--bs-primary-rgb), 0.05) !important;
        }
        .custom-color-accordion .accordion-button::after {
            background-size: 0.75rem;
            width: 0.75rem;
        }
        .custom-color-accordion .list-group-item {
            transition: all 0.15s ease;
            cursor: pointer;
        }
        .custom-color-accordion .list-group-item:hover {
            background-color: rgba(0,0,0,0.03);
            transform: translateX(3px);
        }
        .custom-color-accordion .list-group-item.active {
            border-left: 3px solid var(--bs-primary) !important;
        }
        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: rgba(0,0,0,0.05);
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(0,0,0,0.1);
            border-radius: 10px;
        }
        .equip-form-page .equip-photo-actions .btn {
            min-width: 170px;
        }
        .equip-form-page .equip-photo-grid > div {
            flex: 0 0 auto;
        }
        @media (max-width: 991.98px) {
            .equip-form-page #colorCatalog {
                max-height: 320px !important;
            }
        }
        @media (max-width: 767.98px) {
            .equip-form-page .equip-photo-actions {
                flex-direction: column;
            }
            .equip-form-page .equip-photo-actions .btn {
                width: 100%;
                min-width: 0;
            }
            .equip-form-page .equip-photo-grid {
                justify-content: center !important;
            }
            .equip-form-page .d-flex.justify-content-between.align-items-center.mt-5.pt-3.border-top {
                flex-direction: column-reverse;
                align-items: stretch !important;
                gap: 10px;
            }
            .equip-form-page .d-flex.justify-content-between.align-items-center.mt-5.pt-3.border-top .btn {
                width: 100%;
            }
            .equip-form-page .d-flex.justify-content-between.align-items-center.mt-5.pt-3.border-top .btn-link {
                text-align: center;
            }
        }
    </style>
    <input type="hidden" name="modelo_nome_ext" id="modelo_nome_ext">

            <!-- Navegação por Abas -->
            <ul class="nav nav-tabs nav-fill ds-tabs-scroll mb-4" id="equipamentoTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-bold" id="info-tab" data-bs-toggle="tab" data-bs-target="#info-pane" type="button" role="tab" aria-controls="info-pane" aria-selected="true">
                        <i class="bi bi-info-circle me-2"></i>Informações
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold" id="cor-tab" data-bs-toggle="tab" data-bs-target="#cor-pane" type="button" role="tab" aria-controls="cor-pane" aria-selected="false">
                        <i class="bi bi-palette me-2"></i>Cor
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold" id="fotos-tab" data-bs-toggle="tab" data-bs-target="#fotos-pane" type="button" role="tab" aria-controls="fotos-pane" aria-selected="false">
                        <i class="bi bi-camera me-2"></i>Fotos
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="equipamentoTabsContent">
                
                <!-- ABA 1: INFORMAÇÕES -->
                <div class="tab-pane fade show active" id="info-pane" role="tabpanel" aria-labelledby="info-tab" tabindex="0">
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label d-flex align-items-center gap-2">
                                Cliente *
                                <button class="btn btn-warning btn-sm py-0 px-2" type="button" data-bs-toggle="modal" data-bs-target="#modalNovoCliente"
                                        title="Novo Cliente" style="font-size:0.75rem; border-radius:6px; line-height:1.6;">
                                    <i class="bi bi-plus-lg"></i> Novo
                                </button>
                            </label>
                            <select name="cliente_id" id="clienteSelect" class="form-select select2-clientes" required>
                                <option value="">Selecione ou busque um cliente...</option>
                                <?php foreach ($clientes as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= ($isEdit && $equipamento['cliente_id'] == $c['id']) ? 'selected' : '' ?>>
                                    <?= esc($c['nome_razao']) ?> <?= !empty($c['cpf_cnpj']) ? ' - ' . esc($c['cpf_cnpj']) : '' ?> <?= !empty($c['telefone1']) ? ' - ' . esc($c['telefone1']) : '' ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tipo *</label>
                            <select name="tipo_id" class="form-select" required>
                                <option value="">Selecione o Tipo...</option>
                                <?php foreach ($tipos as $t): ?>
                                <option value="<?= $t['id'] ?>" <?= ($isEdit && ($equipamento['tipo_id'] ?? '') == $t['id']) ? 'selected' : '' ?>><?= esc($t['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label d-flex align-items-center gap-2">
                                Marca *
                                <button class="btn btn-warning btn-sm py-0 px-2" type="button" data-bs-toggle="modal" data-bs-target="#modalNovaMarca"
                                        title="Nova Marca" style="font-size:0.75rem; border-radius:6px; line-height:1.6;">
                                    <i class="bi bi-plus-lg"></i> Novo
                                </button>
                            </label>
                            <select name="marca_id" id="marcaSelect" class="form-select select2-basic" required>
                                <option value="">Selecione a Marca...</option>
                                <?php foreach ($marcas as $m): ?>
                                <option value="<?= $m['id'] ?>" <?= ($isEdit && ($equipamento['marca_id'] ?? '') == $m['id']) ? 'selected' : '' ?>><?= esc($m['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label d-flex align-items-center gap-2">
                                Modelo *
                                <button class="btn btn-warning btn-sm py-0 px-2" type="button" data-bs-toggle="modal" data-bs-target="#modalNovoModelo"
                                        title="Novo Modelo" style="font-size:0.75rem; border-radius:6px; line-height:1.6;">
                                    <i class="bi bi-plus-lg"></i> Novo
                                </button>
                            </label>
                            <select name="modelo_id" id="modeloSelect" class="form-select select2-basic" required>
                                <option value="">Selecione a Marca primeiro...</option>
                                <?php if ($isEdit && !empty($modelos)): ?>
                                    <?php foreach ($modelos as $md): ?>
                                    <option value="<?= $md['id'] ?>" <?= ($equipamento['modelo_id'] ?? '') == $md['id'] ? 'selected' : '' ?>><?= esc($md['nome']) ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Nº Série</label>
                            <input type="text" name="numero_serie" class="form-control" placeholder="IMEI ou Série" value="<?= $isEdit ? esc($equipamento['numero_serie'] ?? '') : '' ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label d-flex justify-content-between align-items-center">
                                Senha de Acesso
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-outline-secondary py-0 px-2 btn-senha-tipo" data-placeholder="Numérico (PIN)" title="PIN/Desenho"><i class="bi bi-grip-vertical"></i></button>
                                    <button type="button" class="btn btn-outline-secondary py-0 px-2 btn-senha-tipo" data-placeholder="Alfanumérico" title="Texto"><i class="bi bi-fonts"></i></button>
                                </div>
                            </label>
                            <input type="text" name="senha_acesso" id="inputSenhaAcesso" class="form-control" placeholder="PIN ou senha" value="<?= $isEdit ? esc($equipamento['senha_acesso'] ?? '') : '' ?>">
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Estado Físico</label>
                            <textarea name="estado_fisico" class="form-control" rows="3" placeholder="Arranhões, tela trincada..."><?= $isEdit ? esc($equipamento['estado_fisico'] ?? '') : '' ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label d-flex justify-content-between">
                                Acessórios
                                <small class="text-muted">Clique para adicionar</small>
                            </label>
                            <textarea name="acessorios" id="textareaAcessorios" class="form-control mb-2" rows="3" placeholder="O que o cliente enviou?"><?= $isEdit ? esc($equipamento['acessorios'] ?? '') : '' ?></textarea>
                            <div class="d-flex flex-wrap gap-1">
                                <button type="button" class="btn btn-sm btn-light border py-0 px-2 btn-quick-acessorio" style="font-size: 0.75rem;">+ Carregador</button>
                                <button type="button" class="btn btn-sm btn-light border py-0 px-2 btn-quick-acessorio" style="font-size: 0.75rem;">+ Cabo USB</button>
                                <button type="button" class="btn btn-sm btn-light border py-0 px-2 btn-quick-acessorio" style="font-size: 0.75rem;">+ Capa</button>
                                <button type="button" class="btn btn-sm btn-light border py-0 px-2 btn-quick-acessorio" style="font-size: 0.75rem;">+ Chip</button>
                                <button type="button" class="btn btn-sm btn-light border py-0 px-2 btn-quick-acessorio" style="font-size: 0.75rem;">+ Cartão Memória</button>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <label class="form-label text-muted">Observações Internas (Opcional)</label>
                            <textarea name="observacoes" class="form-control" rows="2"><?= $isEdit ? esc($equipamento['observacoes'] ?? '') : '' ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- ABA 2: COR -->
                <div class="tab-pane fade" id="cor-pane" role="tabpanel" aria-labelledby="cor-tab" tabindex="0">
                    <div class="p-3 border rounded bg-light bg-opacity-10 mb-4">
                        <h6 class="mb-3 d-flex align-items-center"><i class="bi bi-brush me-2 text-primary"></i> Seletor Profissional de Cor</h6>
                        
                        <!-- HIDDEN: campos reais enviados ao banco -->
                        <input type="hidden" name="cor_hex" id="corHexReal" value="<?= $isEdit ? esc($equipamento['cor_hex'] ?? '#1A1A1A') : '#1A1A1A' ?>">
                        <input type="hidden" name="cor_rgb" id="corRgbReal" value="<?= $isEdit ? esc($equipamento['cor_rgb'] ?? '26,26,26') : '26,26,26' ?>">
                        <input type="hidden" name="cor" id="corNomeReal" value="<?= $isEdit ? esc($equipamento['cor'] ?? 'Preto') : 'Preto' ?>">

                        <div class="row g-3">
                            <!-- Coluna Esquerda: Preview + Picker -->
                            <div class="col-md-5">
                                <!-- Detecção por foto (smart) -->
                                <div class="p-2 mb-3 rounded border border-warning border-opacity-50 bg-warning bg-opacity-10 d-none" id="smartColorContainer">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span style="font-size: 0.72rem;" class="text-warning fw-semibold"><i class="bi bi-magic me-1"></i>Detectado na foto:</span>
                                        <button type="button" class="btn btn-sm text-success p-0 border-0 fw-bold" id="btnAcceptColor" style="font-size: 0.75rem;">Aplicar <i class="bi bi-check2-circle ms-1"></i></button>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <div id="smartColorSwatch" class="rounded-circle shadow border" style="width: 28px; height: 28px;"></div>
                                        <strong id="smartColorName" class="fs-6">Nenhuma</strong>
                                        <small id="smartColorHex" class="text-muted ms-auto font-monospace" style="font-size: 0.7rem;"></small>
                                    </div>
                                </div>

                                <!-- Preview Grande -->
                                <div id="colorPreviewBox" class="rounded-4 shadow border mb-3 d-flex flex-column align-items-center justify-content-center" style="height: 140px; background: #1A1A1A; transition: all 0.3s ease;">
                                    <span id="colorPreviewHex" class="fw-bold font-monospace" style="font-size: 1.2rem; filter: drop-shadow(0 1px 2px rgba(0,0,0,0.3)); letter-spacing: 1px;">#1A1A1A</span>
                                    <span id="colorPreviewName" class="mt-1 fw-semibold" style="font-size: 0.9rem; filter: drop-shadow(0 1px 2px rgba(0,0,0,0.3));">Preto</span>
                                    <span id="colorPreviewRgb" class="mt-1 opacity-75" style="font-size: 0.7rem;">RGB: 26, 26, 26</span>
                                </div>

                                <div class="row g-2 mb-3">
                                    <div class="col-auto">
                                        <input type="color" id="corHexPicker" class="form-control form-control-color p-1 shadow-sm" value="<?= $isEdit ? esc($equipamento['cor_hex'] ?? '#1A1A1A') : '#1A1A1A' ?>" title="Escolha a cor" style="width: 55px; height: 45px; cursor: pointer; border-radius: 10px;">
                                    </div>
                                    <div class="col">
                                        <input type="text" id="corHexInput" class="form-control font-monospace h-100 shadow-sm" placeholder="#000000" value="<?= $isEdit ? esc($equipamento['cor_hex'] ?? '#1A1A1A') : '#1A1A1A' ?>" maxlength="7">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label small text-muted fw-bold text-uppercase" style="letter-spacing: 0.5px;">Nome da Cor Especial</label>
                                    <input type="text" id="corNomeInput" class="form-control shadow-sm" placeholder="Ex: Vermelho Ferrari, Azul Sierra..." value="<?= $isEdit ? esc($equipamento['cor'] ?? 'Preto') : 'Preto' ?>">
                                </div>

                                <div id="coresProximasBox">
                                    <label class="form-label small text-muted fw-bold text-uppercase" style="letter-spacing: 0.5px;">Sugestões Semelhantes</label>
                                    <div id="coresProximasGrid" class="d-flex flex-wrap gap-2"></div>
                                </div>
                            </div>

                            <!-- Coluna Direita: Catálogo -->
                            <div class="col-md-7">
                                <div class="bg-white bg-opacity-50 p-3 rounded shadow-sm h-100 border">
                                    <label class="form-label small text-muted fw-bold text-uppercase mb-3" style="letter-spacing: 0.5px;"><i class="bi bi-grid-3x3-gap me-1"></i> Catálogo Profissional</label>
                                    <div id="colorCatalog" class="pe-2 custom-scrollbar" style="max-height: 480px; overflow-y: auto;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ABA 3: FOTOS -->
                <div class="tab-pane fade" id="fotos-pane" role="tabpanel" aria-labelledby="fotos-tab" tabindex="0">
                    <div class="p-3 border rounded bg-light bg-opacity-10 mb-4 text-center py-5" id="fotosContainerVazio" style="display: none;">
                        <i class="bi bi-cloud-upload display-1 text-muted opacity-25"></i>
                        <h5 class="mt-3 text-muted">Nenhuma foto anexada</h5>
                        <p class="text-muted small">Adicione fotos para documentar o estado do equipamento.</p>
                    </div>

                    <div class="d-flex justify-content-center gap-3 mb-4 equip-photo-actions">
                        <button type="button" class="btn btn-primary btn-lg rounded-pill px-4 shadow" id="btnAbrirCamera">
                            <i class="bi bi-camera-fill me-2"></i>Capturar Foto
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-lg rounded-pill px-4 shadow-sm" id="btnAbrirGaleria">
                            <i class="bi bi-images me-2"></i>Abrir Galeria
                        </button>
                        <input type="file" id="fotoInput" class="d-none" accept="image/*" multiple>
                        <input type="file" name="fotos[]" id="fotoInputReal" class="d-none" multiple>
                    </div>

                    <div class="alert alert-info border-0 shadow-sm d-flex align-items-center mb-4 mx-auto" style="max-width: 600px;">
                        <i class="bi bi-info-circle-fill fs-4 me-3"></i>
                        <div class="small">
                            Envie até <strong>4 fotos</strong> (Máximo 2MB cada). A primeira foto será usada como imagem principal do perfil do equipamento.
                        </div>
                    </div>
                    
                    <div id="fotoPreviewContainer" class="d-flex flex-wrap justify-content-center gap-4 mt-4 equip-photo-grid">
                        <!-- Fotos Existentes -->
                        <?php if($isEdit && !empty($fotos)): ?>
                            <?php foreach($fotos as $f): ?>
                            <div class="position-relative foto-item-wrapper" id="foto-existente-<?= $f['id'] ?>">
                                <div class="card shadow-sm border h-100" style="width: 160px;">
                                    <?php $urlExistente = $f['url'] ?? null; ?>
                                    <img src="<?= $urlExistente ?? base_url('assets/img/no-image.png') ?>" class="card-img-top rounded-top" style="height: 140px; object-fit: cover;">
                                    <div class="card-body p-2 text-center">
                                        <span class="badge <?= $f['is_principal'] ? 'bg-primary' : 'bg-light text-dark border' ?> w-100" style="font-size: 0.65rem;">
                                            <?= $f['is_principal'] ? 'PRINCIPAL' : 'ANEXO' ?>
                                        </span>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1 shadow-sm btn-del-foto-existente" data-id="<?= $f['id'] ?>" style="width: 24px; height: 24px; padding: 0; line-height: 1;">&times;</button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <!-- Container para Previews dinâmicos de Novas -->
                        <div id="fotoPreviewNovas" class="d-flex flex-wrap gap-4"></div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-5 pt-3 border-top">
                <a href="<?= base_url('equipamentos') ?>" class="btn btn-link text-secondary text-decoration-none"><i class="bi bi-x-lg me-1"></i> Descartar Alterações</a>
                <button type="submit" class="btn btn-glow btn-lg px-5 shadow"><i class="bi bi-save me-2 text-warning"></i><?= $isEdit ? 'Atualizar Equipamento' : 'Finalizar Cadastro' ?></button>
            </div>
        </form>
    </div>
</div>
</div>

<!-- ================= MODAIS DE CADASTRO RÁPIDO ================= -->

<!-- Modal Novo Cliente -->
<div class="modal fade" id="modalNovoCliente" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content glass-card">
            <div class="modal-header border-bottom border-light">
                <h5 class="modal-title">Novo Cliente Rápido</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formNovoCliente">
                    <div class="mb-3">
                        <label>Nome Completo *</label>
                        <input type="text" name="nome_razao" id="cNome" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Telefone/WhatsApp *</label>
                        <input type="text" name="telefone1" id="cTelefone" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>CPF/CNPJ</label>
                        <input type="text" name="cpf_cnpj" id="cCpf" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label>E-mail</label>
                        <input type="email" name="email" id="cEmail" class="form-control">
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top border-light">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-glow" id="btnSalvarCliente">Salvar Cliente</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nova Marca -->
<div class="modal fade" id="modalNovaMarca" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content glass-card">
            <div class="modal-header border-bottom border-light">
                <h5 class="modal-title">Nova Marca</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="text" id="mNome" class="form-control" placeholder="Ex: Samsung, Apple...">
            </div>
            <div class="modal-footer border-top border-light">
                <button type="button" class="btn btn-glow w-100" id="btnSalvarMarca">Salvar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Novo Modelo -->
<div class="modal fade" id="modalNovoModelo" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content glass-card">
            <div class="modal-header border-bottom border-light">
                <h5 class="modal-title">Novo Modelo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label>Marca Vinculada</label>
                    <select id="modMarcaId" class="form-select" disabled>
                        <option value="">Selecione a marca no formulário antes...</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label>Nome do Modelo *</label>
                    <div class="position-relative">
                        <input type="text" id="modNome" class="form-control" placeholder="Ex: Galaxy S21..." autocomplete="off">
                        <div id="spinnerNovoModeloForm" class="position-absolute top-50 end-0 translate-middle-y me-2 d-none">
                            <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                        </div>
                    </div>
                    <!-- Dropdown de sugestões -->
                    <div id="sugestoesNovoModeloForm" class="list-group shadow-lg mt-1 d-none"
                         style="max-height: 220px; overflow-y: auto; border-radius: 8px; z-index: 9999; position: relative;"></div>
                    <div class="form-text mt-1">
                        <i class="bi bi-globe2 me-1 text-info"></i>
                        Digite 3+ caracteres para ver sugestões da internet
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top border-light">
                <button type="button" class="btn btn-glow w-100" id="btnSalvarModelo">Salvar</button>
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
                <h5 class="modal-title font-title"><i class="bi bi-crop text-warning me-2"></i>Ajustar Foto do Equipamento</h5>
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

<?= $this->section('scripts') ?>
<!-- Cropper.js -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>

<script>
const BASE_URL = document.querySelector('meta[name="base-url"]').content;

$(document).ready(function() {
    // Inicializar Select2
    $('.select2-clientes').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: "Selecione ou busque um cliente...",
        language: {
            noResults: function() {
                return "Nenhum cliente encontrado";
            }
        }
    });

    // Select2 para Marcas (Precisa resetar o modelo ao mudar)
    $('#marcaSelect').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: "Selecione a Marca..."
    }).on('change', function() {
        // Quando a marca muda, destruímos e recriamos o select2 de modelos
        // ou pelo menos limpamos o valor dele.
        $('#modeloSelect').val(null).trigger('change');
    });

    // Select2 Híbrido: Modelos via API
    $('#modeloSelect').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Busque ou selecione o modelo...',
        allowClear: true,
        tags: true,
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
                // Envia tipo + marca para uma busca contextual precisa no Google
                var tipoNome = $('select[name="tipo_id"] option:selected').text().trim();
                return {
                    q:        params.term,
                    marca_id: $('#marcaSelect').val(),
                    marca:    $('#marcaSelect option:selected').text().trim(),
                    tipo:     tipoNome !== 'Selecione o Tipo...' ? tipoNome : ''
                };
            },
            processResults: function (data) {
                return { results: data.results };
            },
            cache: true
        },
        minimumInputLength: 3,
        language: {
            inputTooShort: function (args) {
                var restante = args.minimum - args.input.length;
                return `Digite mais ${restante} caractere(s) para buscar...`;
            },
            searching:    function() { return '<i class="bi bi-globe2 me-1"></i> Buscando modelos na internet...'; },
            noResults:    function() { return 'Nenhuma sugestão encontrada. Use o botão <strong>+ Novo</strong> para cadastrar manualmente.'; },
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
        if (data.id && data.id.toString().indexOf('EXT|') === 0) {
            $('#modelo_nome_ext').val(data.text);
        } else {
            $('#modelo_nome_ext').val('');
        }
    }).on('select2:open', function () {
        var selecionado = $(this).select2('data')[0];
        if (selecionado && selecionado.id && selecionado.id !== '') {
            var searchField = document.querySelector('.select2-search__field');
            if (searchField && !searchField.value) {
                searchField.value = selecionado.text;
            }
        }
    });


    // ???????????????????????????????????????????????????????????
    // SELETOR DE COR PROFISSIONAL
    // ???????????????????????????????????????????????????????????

    const PROFESSIONAL_COLORS = [
        { category: 'Neutras (Preto, Branco, Cinza)', colors: [
            { hex: '#000000', name: 'Preto' },
            { hex: '#2F4F4F', name: 'Grafite' },
            { hex: '#1C1C1E', name: 'Midnight' },
            { hex: '#41464D', name: 'Graphite' },
            { hex: '#5C5B57', name: 'Titanium' },
            { hex: '#696969', name: 'Cinza Escuro' },
            { hex: '#708090', name: 'Cinza Ardósia' },
            { hex: '#BEBEBE', name: 'Cinza' },
            { hex: '#D3D3D3', name: 'Cinza Claro' },
            { hex: '#FFFFFF', name: 'Branco' },
            { hex: '#F8F8FF', name: 'Branco Gelo' },
            { hex: '#F5F5F5', name: 'Branco Fumaça' },
            { hex: '#FFFFF0', name: 'Marfim' },
        ]},
        { category: 'Azuis e Marinhos', colors: [
            { hex: '#191970', name: 'Azul Meia-Noite' },
            { hex: '#000080', name: 'Azul Marinho' },
            { hex: '#00008B', name: 'Azul Escuro' },
            { hex: '#0000FF', name: 'Azul Puro' },
            { hex: '#4169E1', name: 'Azul Real' },
            { hex: '#1E90FF', name: 'Azul Céu' },
            { hex: '#87CEEB', name: 'Azul Celeste' },
            { hex: '#ADD8E6', name: 'Azul Bebê' },
            { hex: '#5F9EA0', name: 'Azul Petróleo' },
        ]},
        { category: 'Verdes e Mentas', colors: [
            { hex: '#006400', name: 'Verde Escuro' },
            { hex: '#2E8B57', name: 'Verde Floresta' },
            { hex: '#008000', name: 'Verde Puro' },
            { hex: '#32CD32', name: 'Verde Vivo' },
            { hex: '#98FB98', name: 'Verde Claro' },
            { hex: '#F5FFFA', name: 'Verde Menta' },
            { hex: '#556B2F', name: 'Verde Musgo' },
            { hex: '#6B8E23', name: 'Verde Militar' },
        ]},
        { category: 'Vermelhos e Corais', colors: [
            { hex: '#8B0000', name: 'Vermelho Escuro' },
            { hex: '#B22222', name: 'Vermelho Tijolo' },
            { hex: '#FF0000', name: 'Vermelho' },
            { hex: '#FF4500', name: 'Vermelho Alaranjado' },
            { hex: '#FF6347', name: 'Tomate' },
            { hex: '#FFA500', name: 'Laranja' },
            { hex: '#FF7F50', name: 'Coral' },
            { hex: '#FA8072', name: 'Salmão' },
        ]},
        { category: 'Amarelos e Dourados', colors: [
            { hex: '#B8860B', name: 'Dourado Escuro' },
            { hex: '#DAA520', name: 'Dourado Médio' },
            { hex: '#D4AF37', name: 'Dourado' },
            { hex: '#FFD700', name: 'Dourado Vivo' },
            { hex: '#FFFF00', name: 'Amarelo' },
            { hex: '#FFFFE0', name: 'Amarelo Claro' },
            { hex: '#F5F5DC', name: 'Bege' },
            { hex: '#FFF8DC', name: 'Marfim' },
        ]},
        { category: 'Marrons e Amadeirados', colors: [
            { hex: '#8B4513', name: 'Marrom Escuro' },
            { hex: '#A52A2A', name: 'Marrom' },
            { hex: '#CD853F', name: 'Marrom Claro' },
            { hex: '#D2691E', name: 'Chocolate' },
            { hex: '#F4A460', name: 'Areia' },
        ]},
        { category: 'Roxos, Pinks e Lilás', colors: [
            { hex: '#4B0082', name: 'Índigo' },
            { hex: '#2D1B69', name: 'Violeta Escuro' },
            { hex: '#800080', name: 'Roxo Puro' },
            { hex: '#9370DB', name: 'Roxo Médio' },
            { hex: '#DA70D6', name: 'Lilás' },
            { hex: '#FF00FF', name: 'Magenta' },
            { hex: '#FF1493', name: 'Rosa Pink' },
            { hex: '#FFC0CB', name: 'Rosa' },
            { hex: '#AA336A', name: 'Rose Gold' },
        ]},
    ];

    function hexToRgb(hex) {
        var result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
        return result ? {
            r: parseInt(result[1], 16),
            g: parseInt(result[2], 16),
            b: parseInt(result[3], 16)
        } : null;
    }

    function colorDistance(hex1, hex2) {
        const a = hexToRgb(hex1), b = hexToRgb(hex2);
        if (!a || !b) return Infinity;
        return Math.sqrt(Math.pow(a.r - b.r, 2) + Math.pow(a.g - b.g, 2) + Math.pow(a.b - b.b, 2));
    }

    function getTextColorForBg(hex) {
        const rgb = hexToRgb(hex);
        if (!rgb) return '#fff';
        const luminance = (0.299 * rgb.r + 0.587 * rgb.g + 0.114 * rgb.b) / 255;
        return luminance > 0.55 ? '#1a1a1a' : '#ffffff';
    }

    function findClosestColor(hex) {
        let best = null, minDist = Infinity;
        for (const cat of PROFESSIONAL_COLORS) {
            for (const c of cat.colors) {
                const d = colorDistance(hex, c.hex);
                if (d < minDist) { minDist = d; best = c; }
            }
        }
        return best;
    }

    function findNearestColors(hex, count = 5) {
        const all = [];
        for (const cat of PROFESSIONAL_COLORS) {
            for (const c of cat.colors) all.push({ ...c, dist: colorDistance(hex, c.hex) });
        }
        return all.sort((a, b) => a.dist - b.dist).slice(0, count);
    }

    window.updateColorUI = function(hex, name) {
        const rgb = hexToRgb(hex);
        const rgbStr = rgb ? `${rgb.r},${rgb.g},${rgb.b}` : '';
        const textColor = getTextColorForBg(hex);

        // Update hidden fields
        $('#corHexReal').val(hex);
        $('#corRgbReal').val(rgbStr);
        $('#corNomeReal').val(name);

        // Update picker and text inputs
        $('#corHexPicker').val(hex);
        $('#corHexInput').val(hex.toUpperCase());
        $('#corNomeInput').val(name);

        // Update preview box
        const previewBox = document.getElementById('colorPreviewBox');
        if (previewBox) {
            previewBox.style.background = hex;
            previewBox.style.boxShadow = `0 4px 20px ${hex}55`;
            document.getElementById('colorPreviewHex').style.color = textColor;
            document.getElementById('colorPreviewHex').textContent = hex.toUpperCase();
            document.getElementById('colorPreviewName').style.color = textColor === '#ffffff' ? 'rgba(255,255,255,0.8)' : 'rgba(0,0,0,0.65)';
            document.getElementById('colorPreviewName').textContent = name;
            document.getElementById('colorPreviewRgb').style.color = textColor === '#ffffff' ? 'rgba(255,255,255,0.5)' : 'rgba(0,0,0,0.4)';
            document.getElementById('colorPreviewRgb').textContent = rgb ? `RGB: ${rgb.r}, ${rgb.g}, ${rgb.b}` : '';
        }

        // Update similar colors
        const nearest = findNearestColors(hex, 6);
        const grid = document.getElementById('coresProximasGrid');
        if (grid) {
            grid.innerHTML = '';
            nearest.forEach(c => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.title = c.name;
                btn.style.cssText = `width:28px;height:28px;background:${c.hex};border:${c.hex.toUpperCase()===hex.toUpperCase() ? '3px solid #0d6efd' : '2px solid rgba(0,0,0,0.15)'};border-radius:50%;cursor:pointer;transition:transform 0.15s;`;
                btn.addEventListener('mouseenter', () => btn.style.transform = 'scale(1.2)');
                btn.addEventListener('mouseleave', () => btn.style.transform = 'scale(1)');
                btn.addEventListener('click', () => updateColorUI(c.hex, c.name));
                grid.appendChild(btn);
            });
        }

        // Refresh Catalog Selection
        buildCatalog();
    }

    window.buildCatalog = function() {
        const catalog = document.getElementById('colorCatalog');
        if (!catalog) return;
        catalog.innerHTML = '';

        const accordionId = 'accordionColorFamilies';
        const accordion = document.createElement('div');
        accordion.className = 'accordion accordion-flush custom-color-accordion';
        accordion.id = accordionId;

        PROFESSIONAL_COLORS.forEach((cat, index) => {
            const itemId = `flush-collapse-${index}`;
            const headerId = `flush-heading-${index}`;

            const accordionItem = document.createElement('div');
            accordionItem.className = 'accordion-item bg-transparent border-bottom border-light';

            accordionItem.innerHTML = `
                <h2 class="accordion-header" id="${headerId}">
                    <button class="accordion-button collapsed py-2 px-1 bg-transparent shadow-none fw-bold text-muted" type="button" data-bs-toggle="collapse" data-bs-target="#${itemId}" aria-expanded="false" aria-controls="${itemId}" style="font-size: 0.82rem;">
                        <i class="bi bi-circle-fill me-2" style="color: ${cat.colors[0].hex}; font-size: 0.8rem;"></i>
                        ${cat.category}
                    </button>
                </h2>
                <div id="${itemId}" class="accordion-collapse collapse" aria-labelledby="${headerId}" data-bs-parent="#${accordionId}">
                    <div class="accordion-body p-0 pb-2">
                        <div class="list-group list-group-flush rounded-3 overflow-hidden border">
                            ${cat.colors.map(c => {
                                const isSelected = $('#corHexReal').val().toUpperCase() === c.hex.toUpperCase();
                                return `
                                    <button type="button" class="list-group-item list-group-item-action py-2 px-3 d-flex align-items-center gap-3 border-0 ${isSelected ? 'active bg-primary bg-opacity-10 text-primary fw-bold' : ''}" 
                                            onclick="updateColorUI('${c.hex}', '${c.name}')" style="font-size: 0.85rem;">
                                        <div class="rounded-circle shadow-sm border border-light" 
                                             style="width: 26px; height: 26px; background: ${c.hex}; flex-shrink: 0;"></div>
                                        <span class="flex-grow-1 text-start">${c.name}</span>
                                        <small class="text-muted font-monospace opacity-50" style="font-size: 0.75rem;">${c.hex}</small>
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

    // Color picker input
    $('#corHexPicker').on('input', function() {
        const hex = this.value.toUpperCase();
        const closest = findClosestColor(hex);
        updateColorUI(hex, closest ? closest.name : hex);
    });

    // Hex text input
    $('#corHexInput').on('input change', function() {
        let hex = this.value.trim();
        if (!hex.startsWith('#')) hex = '#' + hex;
        if (/^#[0-9a-fA-F]{6}$/.test(hex)) {
            const closest = findClosestColor(hex);
            updateColorUI(hex, closest ? closest.name : hex);
        }
    });

    // Nome editável manual
    $('#corNomeInput').on('input', function() {
        $('#corNomeReal').val(this.value);
    });

    // Init
    buildCatalog();
    const initHex = $('#corHexReal').val() || '#1A1A1A';
    const initClosest = findClosestColor(initHex);
    updateColorUI(initHex, initClosest ? initClosest.name : ($('#corNomeReal').val() || 'Preto'));

    // ??? LÓGICA DE DETECÇÃO DE COR INTELIGENTE NA IMAGEM ??????????????????
// (smartColorMap removido, usando PROFESSIONAL_COLORS)

    function rgbToHexStr(r, g, b) {
        return "#" + (1 << 24 | r << 16 | g << 8 | b).toString(16).slice(1).toUpperCase();
    }

    $('#btnAcceptColor').click(function() {
        const hex = $(this).data('hex');
        const name = $(this).data('name');
        updateColorUI(hex, name);
    });

    function detectDominantColor(sourceCanvas) {
        try {
            const ctx = sourceCanvas.getContext('2d', { willReadFrequently: true });
            
            // Foca nos 40% centrais da imagem para evitar fundos brancos/pretos de estúdio
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
            
            // Amostragem (step = 4px)
            for (let i = 0; i < data.length; i += 16) {
                const r = Math.round(data[i] / 20) * 20; // Quantização grossa
                const g = Math.round(data[i+1] / 20) * 20;
                const b = Math.round(data[i+2] / 20) * 20;
                const a = data[i+3];
                
                if (a < 128) continue;
                
                // Reduz consideravelmente o peso de pixels puramente pretos/apagados (como a lente ou tela preta) 
                // e brancos puros (fundo de caixa).
                let weight = 1;
                if ((r < 25 && g < 25 && b < 25) || (r > 235 && g > 235 && b > 235)) {
                    weight = 0.05; 
                }
                
                const hex = rgbToHexStr(r, g, b);
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
            
            let bestMatch = { hex: dominantHex, name: 'Personalizada' };
            const closest = findClosestColor(dominantHex);
            if (closest) {
                bestMatch = closest;
            }
            
            const closestColorName = bestMatch.name;
            
            // Exibir no painel UI
            $('#smartColorSwatch').css('background-color', dominantHex);
            $('#smartColorName').text(closestColorName);
            $('#smartColorHex').text(dominantHex);
            $('#btnAcceptColor').data('hex', dominantHex).data('name', closestColorName);
            $('#smartColorContainer').removeClass('d-none');

        } catch (e) {
            console.warn('Erro na detecção de cor: ', e);
        }
    }

    // (Duplicado removido)

    // Cascata de Marca -> Modelo (Apenas para setar o ID no modal de novo modelo)
    $('#marcaSelect').on('change', function() {
        const marcaId = $(this).val();
        const marcaNome = $(this).find('option:selected').text();

        // Atualiza a opção no modal de Novo Modelo
        if(marcaId) {
            $('#modMarcaId').html(`<option value="${marcaId}">${marcaNome}</option>`);
            $('#modMarcaId').prop('disabled', false);
        } else {
            $('#modMarcaId').html('<option value="">Selecione a marca no formulário antes...</option>');
            $('#modMarcaId').prop('disabled', true);
        }
    });

    // ??? LÓGICA DE SENHA E ACESSÓRIOS (NOVAS ABAS) ??????????????????????
    $(document).on('click', '.btn-senha-tipo', function() {
        const placeholder = $(this).data('placeholder');
        $('#inputSenhaAcesso').attr('placeholder', placeholder).focus();
        $('.btn-senha-tipo').removeClass('btn-secondary text-white').addClass('btn-outline-secondary');
        $(this).removeClass('btn-outline-secondary').addClass('btn-secondary text-white');
    });

    $(document).on('click', '.btn-quick-acessorio', function() {
        const value = $(this).text().replace('+ ', '').trim();
        const textarea = $('#textareaAcessorios');
        const current = textarea.val().trim();
        
        if (current.includes(value)) return;
        
        textarea.val(current === '' ? value : current + ', ' + value).focus();
        
        $(this).addClass('bg-primary text-white').delay(300).queue(function(next){
            $(this).removeClass('bg-primary text-white');
            next();
        });
    });

    function checkPhotosEmptyState() {
        const total = $('.foto-existente-item, .foto-item-wrapper').length + ($('#fotoPreviewNovas').children().length);
        if (total === 0) {
            $('#fotosContainerVazio').show();
        } else {
            $('#fotosContainerVazio').hide();
        }
    }

    // Chamar no init e após mudar fotos
    setTimeout(checkPhotosEmptyState, 500);

    // Reaproveitar o renderNewPreviews para checar vazio
    const originalRenderNewPreviews = typeof renderNewPreviews !== 'undefined' ? renderNewPreviews : null;
    if (originalRenderNewPreviews) {
        window.renderNewPreviews = function() {
            originalRenderNewPreviews();
            checkPhotosEmptyState();
        };
    }

    // ??? LÓGICA DE CÂMERA, GALERIA E CROPPER (FOTOS) ?????????????????????
    const modalCameraEl  = document.getElementById('modalCamera');
    const modalCropEl    = document.getElementById('modalCropEquip');
    const modalCamera    = modalCameraEl ? new bootstrap.Modal(modalCameraEl) : null;
    const modalCrop      = modalCropEl ? new bootstrap.Modal(modalCropEl) : null;
    const videoCamera    = document.getElementById('videoCamera');
    const canvasCamera   = document.getElementById('canvasCamera');
    const btnCapturar    = document.getElementById('btnCapturar');
    const fotoInput      = document.getElementById('fotoInput');
    const fotoInputReal  = document.getElementById('fotoInputReal');
    const imgToCrop      = document.getElementById('imgToCrop');
    let streamCamera     = null;
    let cropper          = null;
    let cropperReady     = false;
    let pendingCropQueue = [];
    const dt             = new DataTransfer();
    const maxPhotos      = 4;

    function showPhotoDialog(icon, title, text) {
        if (window.Swal && typeof window.Swal.fire === 'function') {
            return window.Swal.fire({
                icon,
                title,
                text,
                confirmButtonText: 'Ok',
                customClass: {
                    confirmButton: 'btn btn-glow px-4'
                },
                buttonsStyling: false
            });
        }

        console.error('[Equipamentos Fotos] fallback nativo acionado:', { icon, title, text });
        window.alert([title, text].filter(Boolean).join('\n\n'));
        return Promise.resolve({ isConfirmed: true });
    }

    function cleanupStuckModalArtifacts() {
        document.querySelectorAll('.modal-backdrop').forEach((backdrop) => backdrop.remove());
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('padding-right');
        document.body.style.removeProperty('overflow');
    }

    function scheduleModalCleanup() {
        window.setTimeout(cleanupStuckModalArtifacts, 50);
        window.setTimeout(cleanupStuckModalArtifacts, 180);
    }

    function hideModalSafe(modalInstance, selector) {
        try {
            modalInstance?.hide();
        } catch (error) {
            console.error('[Equipamentos Fotos] erro ao ocultar modal', selector, error);
        }

        const modalNode = selector ? document.querySelector(selector) : null;
        if (modalNode) {
            modalNode.classList.remove('show');
            modalNode.style.display = 'none';
            modalNode.setAttribute('aria-hidden', 'true');
            modalNode.removeAttribute('aria-modal');
        }

        scheduleModalCleanup();
    }

    function syncFotoInputReal() {
        if (fotoInputReal) {
            fotoInputReal.files = dt.files;
        }
    }

    function processPendingCropQueue() {
        if (!pendingCropQueue.length) {
            return;
        }

        const nextSource = pendingCropQueue.shift();
        window.setTimeout(() => openCropper(nextSource), 120);
    }

    function appendPhotoBlob(blob, sourceCanvas = null) {
        const currentExisting = $('.foto-existente-item').length;
        if ((currentExisting + dt.items.length) >= maxPhotos) {
            showPhotoDialog('warning', 'Limite de fotos', `Voce pode ter no maximo ${maxPhotos} fotos no total.`);
            hideModalSafe(modalCrop, '#modalCropEquip');
            return;
        }

        const file = new File([blob], `equipamento_${Date.now()}_${Math.floor(Math.random() * 1000)}.jpg`, { type: 'image/jpeg' });
        dt.items.add(file);
        syncFotoInputReal();

        if (sourceCanvas) {
            try {
                detectDominantColor(sourceCanvas);
            } catch (error) {
                console.error('[Equipamentos Fotos] falha ao detectar cor dominante', error);
            }
        }

        renderNewPreviews();
        hideModalSafe(modalCrop, '#modalCropEquip');
        processPendingCropQueue();
    }

    document.getElementById('btnAbrirGaleria')?.addEventListener('click', () => fotoInput?.click());

    document.getElementById('btnAbrirCamera')?.addEventListener('click', async () => {
        try {
            streamCamera = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
            if (videoCamera) {
                videoCamera.srcObject = streamCamera;
            }
            modalCamera?.show();
        } catch (err) {
            console.error('[Equipamentos Fotos] falha ao acessar camera', err);
            showPhotoDialog('error', 'Camera indisponivel', 'Nao foi possivel acessar a camera deste dispositivo.');
        }
    });

    modalCameraEl?.addEventListener('hidden.bs.modal', () => {
        if (streamCamera) {
            streamCamera.getTracks().forEach(track => track.stop());
            streamCamera = null;
        }
        if (videoCamera) {
            videoCamera.srcObject = null;
        }
        scheduleModalCleanup();
    });

    function openCropper(source) {
        if (!source) {
            console.error('[Equipamentos Fotos] openCropper chamado sem source');
            return;
        }

        if (!imgToCrop) {
            console.error('[Equipamentos Fotos] imagem do cropper nao encontrada');
            return;
        }

        cropperReady = false;
        imgToCrop.src = source;

        if (typeof window.Cropper === 'undefined') {
            console.error('[Equipamentos Fotos] Cropper indisponivel, fallback sem corte sera usado');

            const fallbackImage = new Image();
            fallbackImage.onload = () => {
                const fallbackCanvas = document.createElement('canvas');
                fallbackCanvas.width = fallbackImage.naturalWidth || fallbackImage.width || 1024;
                fallbackCanvas.height = fallbackImage.naturalHeight || fallbackImage.height || 1024;
                const context = fallbackCanvas.getContext('2d');

                if (!context) {
                    console.error('[Equipamentos Fotos] fallback canvas sem contexto 2D');
                    showPhotoDialog('error', 'Falha ao processar imagem', 'Nao foi possivel preparar a foto selecionada.');
                    processPendingCropQueue();
                    return;
                }

                context.drawImage(fallbackImage, 0, 0, fallbackCanvas.width, fallbackCanvas.height);
                fallbackCanvas.toBlob((blob) => {
                    if (!blob) {
                        console.error('[Equipamentos Fotos] fallback canvas retornou blob vazio');
                        showPhotoDialog('error', 'Falha ao processar imagem', 'Nao foi possivel gerar a foto selecionada.');
                        processPendingCropQueue();
                        return;
                    }

                    appendPhotoBlob(blob, fallbackCanvas);
                }, 'image/jpeg', 0.9);
            };
            fallbackImage.onerror = (error) => {
                console.error('[Equipamentos Fotos] erro ao carregar imagem no fallback', error);
                showPhotoDialog('error', 'Falha ao carregar imagem', 'A imagem escolhida nao pode ser carregada.');
                processPendingCropQueue();
            };
            fallbackImage.src = source;
            return;
        }

        modalCrop?.show();
    }

    modalCropEl?.addEventListener('shown.bs.modal', () => {
        if (typeof window.Cropper === 'undefined') {
            return;
        }

        try {
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
            cropperReady = true;
        } catch (error) {
            console.error('[Equipamentos Fotos] falha ao inicializar cropper', error);
            cropperReady = false;
            hideModalSafe(modalCrop, '#modalCropEquip');
            showPhotoDialog('error', 'Falha no editor', 'Nao foi possivel abrir o editor de corte da foto.');
            processPendingCropQueue();
        }
    });

    modalCropEl?.addEventListener('hidden.bs.modal', () => {
        try {
            cropper?.destroy();
        } catch (error) {
            console.error('[Equipamentos Fotos] erro ao destruir cropper', error);
        }
        cropper = null;
        cropperReady = false;
        scheduleModalCleanup();
    });

    document.getElementById('btnRotateLeft')?.addEventListener('click', () => {
        if (cropperReady && cropper) {
            cropper.rotate(-90);
        }
    });

    document.getElementById('btnRotateRight')?.addEventListener('click', () => {
        if (cropperReady && cropper) {
            cropper.rotate(90);
        }
    });

    btnCapturar?.addEventListener('click', () => {
        if (!videoCamera || !canvasCamera) {
            console.error('[Equipamentos Fotos] elementos de camera indisponiveis');
            return;
        }

        const context = canvasCamera.getContext('2d');
        if (!context) {
            console.error('[Equipamentos Fotos] canvas da camera sem contexto 2D');
            showPhotoDialog('error', 'Falha na camera', 'Nao foi possivel capturar a imagem da camera.');
            return;
        }

        canvasCamera.width  = videoCamera.videoWidth || 1280;
        canvasCamera.height = videoCamera.videoHeight || 720;
        context.drawImage(videoCamera, 0, 0, canvasCamera.width, canvasCamera.height);

        const dataUrl = canvasCamera.toDataURL('image/jpeg');
        hideModalSafe(modalCamera, '#modalCamera');
        openCropper(dataUrl);
    });

    document.getElementById('btnConfirmCrop')?.addEventListener('click', () => {
        if (!cropperReady || !cropper) {
            console.error('[Equipamentos Fotos] confirmacao de crop sem cropper pronto');
            showPhotoDialog('warning', 'Editor indisponivel', 'A foto ainda nao esta pronta para corte.');
            return;
        }

        try {
            const canvas = cropper.getCroppedCanvas({ width: 1024, height: 1024, imageSmoothingQuality: 'high' });
            if (!canvas) {
                throw new Error('Canvas do cropper nao retornado.');
            }

            canvas.toBlob((blob) => {
                if (!blob) {
                    console.error('[Equipamentos Fotos] cropper retornou blob vazio');
                    showPhotoDialog('error', 'Falha ao salvar foto', 'Nao foi possivel gerar a foto cortada.');
                    return;
                }

                appendPhotoBlob(blob, canvas);
            }, 'image/jpeg', 0.9);
        } catch (error) {
            console.error('[Equipamentos Fotos] erro ao confirmar crop', error);
            showPhotoDialog('error', 'Falha ao salvar foto', 'Nao foi possivel finalizar o corte da imagem.');
        }
    });

    fotoInput?.addEventListener('change', function() {
        const selectedFiles = Array.from(this.files || []);
        if (!selectedFiles.length) {
            return;
        }

        const availableSlots = Math.max(0, maxPhotos - ($('.foto-existente-item').length + dt.items.length));
        if (availableSlots <= 0) {
            showPhotoDialog('warning', 'Limite de fotos', `Voce pode ter no maximo ${maxPhotos} fotos no total.`);
            this.value = '';
            return;
        }

        pendingCropQueue = [];
        selectedFiles.slice(0, availableSlots).forEach((file) => {
            const reader = new FileReader();
            reader.onload = (e) => {
                if (e.target?.result) {
                    pendingCropQueue.push(e.target.result);
                    if (pendingCropQueue.length === 1) {
                        processPendingCropQueue();
                    }
                }
            };
            reader.onerror = (error) => {
                console.error('[Equipamentos Fotos] erro ao ler arquivo da galeria', error);
            };
            reader.readAsDataURL(file);
        });

        if (selectedFiles.length > availableSlots) {
            showPhotoDialog('info', 'Quantidade ajustada', `Somente ${availableSlots} foto(s) puderam ser processadas por causa do limite de ${maxPhotos}.`);
        }

        this.value = '';
    });

    function renderNewPreviews() {
        const container = $('#fotoPreviewNovas');
        container.empty();

        Array.from(dt.files).forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const totalAnteriores = $('.foto-existente-item').length;
                const isPrincipalLabel = (index === 0 && totalAnteriores === 0) ? 'Nova Principal' : 'Nova';
                const badgeClass = (index === 0 && totalAnteriores === 0) ? 'bg-success' : 'bg-secondary';

                const thumb = $(`
                    <div class="position-relative border rounded p-1 border-primary" style="width:120px; height:120px;">
                        <span class="badge ${badgeClass} position-absolute bottom-0 start-0 m-1" style="font-size:0.6rem;">${isPrincipalLabel}</span>
                        <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1 p-0 px-1 btn-del-foto-nova" data-index="${index}"><i class="bi bi-x"></i></button>
                        <img src="${e.target.result}" style="width:100%; height:100%; object-fit:cover;" class="rounded">
                    </div>
                `);
                container.append(thumb);
                checkPhotosEmptyState();
            };
            reader.readAsDataURL(file);
        });

        if (!dt.files.length) {
            checkPhotosEmptyState();
        }
    }

    $(document).on('click', '.btn-del-foto-nova', function() {
        const index = $(this).data('index');
        const dtNew = new DataTransfer();
        const files = Array.from(dt.files);

        for (let i = 0; i < files.length; i++) {
            if (i !== index) {
                dtNew.items.add(files[i]);
            }
        }

        dt.items.clear();
        for (let i = 0; i < dtNew.files.length; i++) {
            dt.items.add(dtNew.files[i]);
        }

        syncFotoInputReal();
        renderNewPreviews();
    });

    $(document).on('click', '.btn-del-foto-existente', async function() {
        let confirmed = true;

        if (window.Swal && typeof window.Swal.fire === 'function') {
            const result = await window.Swal.fire({
                icon: 'warning',
                title: 'Excluir foto?',
                text: 'Esta foto sera removida do equipamento.',
                showCancelButton: true,
                confirmButtonText: 'Excluir',
                cancelButtonText: 'Cancelar',
                reverseButtons: true,
                customClass: {
                    confirmButton: 'btn btn-danger px-4',
                    cancelButton: 'btn btn-outline-secondary px-4'
                },
                buttonsStyling: false
            });
            confirmed = !!result.isConfirmed;
        } else {
            confirmed = window.confirm('Deseja realmente excluir esta foto?');
        }

        if (!confirmed) {
            return;
        }

        const btn = $(this);
        const id = btn.data('id');

        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        const fd = new FormData();
        fd.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

        fetch(BASE_URL + 'equipamentos/deletar-foto/' + id, {
            method: 'POST',
            body: fd
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                $('#foto-existente-' + id).remove();
                checkPhotosEmptyState();
            } else {
                showPhotoDialog('error', 'Erro ao excluir foto', data.message || 'Nao foi possivel excluir a foto.');
                btn.prop('disabled', false).html('<i class="bi bi-x"></i>');
            }
        })
        .catch(err => {
            console.error('[Equipamentos Fotos] erro ao excluir foto existente', err);
            showPhotoDialog('error', 'Erro de comunicacao', 'Nao foi possivel concluir a exclusao da foto.');
            btn.prop('disabled', false).html('<i class="bi bi-x"></i>');
        });
    });

    // ================= AJAX SALVAMENTO RÁPIDO ================= //

    // Salvar Cliente
    $('#btnSalvarCliente').click(function() {
        const btn = $(this);
        const nome = $('#cNome').val();
        if(!nome) { alert('Nome é obrigatório'); return; }
        
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
        
        const fd = new FormData();
        fd.append('nome_razao', nome);
        fd.append('telefone1', $('#cTelefone').val());
        fd.append('cpf_cnpj', $('#cCpf').val());
        fd.append('email', $('#cEmail').val());
        fd.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

        fetch(BASE_URL + 'clientes/salvar_ajax', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                const newOption = new Option(data.nome, data.id, true, true);
                $('#clienteSelect').append(newOption).trigger('change');
                $('#modalNovoCliente').modal('hide');
                $('#formNovoCliente')[0].reset();
            } else {
                alert(data.message || 'Erro ao salvar cliente');
            }
        })
        .catch(err => alert('Erro na comunicação'))
        .finally(() => btn.prop('disabled', false).html('Salvar Cliente'));
    });

    // Salvar Marca
    $('#btnSalvarMarca').click(function() {
        const btn = $(this);
        const nome = $('#mNome').val();
        if(!nome) return;
        
        btn.prop('disabled', true);
        const fd = new FormData();
        fd.append('nome', nome);
        fd.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

        fetch(BASE_URL + 'equipamentosmarcas/salvar_ajax', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                const newOption = new Option(data.nome, data.id, true, true);
                $('#marcaSelect').append(newOption).trigger('change');
                $('#modalNovaMarca').modal('hide');
                $('#mNome').val('');
            }
        })
        .finally(() => btn.prop('disabled', false));
    });

    // Salvar Modelo
    $('#btnSalvarModelo').click(function() {
        const btn = $(this);
        const nome = $('#modNome').val();
        const marca_id = $('#modMarcaId').val();
        if(!nome || !marca_id) { alert('Preencha os dados.'); return; }
        
        btn.prop('disabled', true);
        const fd = new FormData();
        fd.append('nome', nome);
        fd.append('marca_id', marca_id);
        fd.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

        fetch(BASE_URL + 'equipamentosmodelos/salvar_ajax', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                const newOption = new Option(data.nome, data.id, true, true);
                $('#modeloSelect').append(newOption).trigger('change');
                $('#modalNovoModelo').modal('hide');
                $('#modNome').val('');
            }
        })
        .finally(() => btn.prop('disabled', false));
    });

    // Masks para Modal Cliente
    $('#cTelefone').mask('(00) 00000-0000');

    // ??? Autocomplete Inteligente (Modal Novo Modelo) ?????????????????
    (function () {
        const inputModelo = document.getElementById('modNome');
        const sugestoesBox = document.getElementById('sugestoesNovoModeloForm');
        const spinnerModelo = document.getElementById('spinnerNovoModeloForm');
        let debounceTimer = null;

        if (!inputModelo) return;

        function renderSugestoes(groups) {
            sugestoesBox.innerHTML = '';
            let total = 0;

            groups.forEach(group => {
                if (!group.children || group.children.length === 0) return;

                const header = document.createElement('div');
                header.className = 'list-group-item list-group-item-secondary py-1 px-3';
                header.style.cssText = 'font-size:0.7rem; font-weight:700; letter-spacing:0.5px; text-transform:uppercase; pointer-events:none; opacity:0.8;';
                const isCadastrado = group.text.includes('Cadastrados');
                header.textContent = (isCadastrado ? '? ' : '? ') + group.text.replace(/^[??] /, '');
                sugestoesBox.appendChild(header);

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
                        <div class="mt-1"><i class="bi bi-${isCadastrado ? 'check-circle text-success' : 'globe2 text-info'}" style="font-size:0.8rem;"></i></div>
                        <div>
                            <strong style="color:var(--bs-heading-color);">${item.text}</strong>
                            ${subtitle}
                        </div>
                    `;
                    btn.addEventListener('mousedown', e => e.preventDefault());
                    btn.addEventListener('click', () => {
                        inputModelo.value = item.text;
                        sugestoesBox.classList.add('d-none');
                        inputModelo.focus();
                    });
                    sugestoesBox.appendChild(btn);
                    total++;
                });
            });

            if (total > 0) {
                sugestoesBox.classList.remove('d-none');
            } else {
                sugestoesBox.innerHTML = '<div class="list-group-item text-muted small py-2 px-3"><i class="bi bi-info-circle me-1"></i>Nenhuma sugestão encontrada. Salve manualmente.</div>';
                sugestoesBox.classList.remove('d-none');
            }
        }

        inputModelo.addEventListener('input', function () {
            const q = this.value.trim();
            clearTimeout(debounceTimer);

            if (q.length < 3) {
                sugestoesBox.classList.add('d-none');
                spinnerModelo.classList.add('d-none');
                return;
            }

            spinnerModelo.classList.remove('d-none');
            sugestoesBox.classList.add('d-none');

            debounceTimer = setTimeout(() => {
                const tipoNome = $('select[name="tipo_id"] option:selected').text().trim();
                const marcaSel = document.getElementById('modMarcaId');
                const marcaId = marcaSel.value;
                const marcaNome = marcaSel.options[marcaSel.selectedIndex]?.text || '';

                const params = new URLSearchParams({
                    q: q,
                    marca_id: marcaId,
                    marca: marcaNome && marcaNome.indexOf('Selecione') === -1 ? marcaNome : '',
                    tipo: tipoNome !== 'Selecione o Tipo...' ? tipoNome : ''
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

        inputModelo.addEventListener('blur', () => {
             setTimeout(() => sugestoesBox.classList.add('d-none'), 200);
        });

        document.getElementById('modalNovoModelo')?.addEventListener('hidden.bs.modal', () => {
            inputModelo.value = '';
            sugestoesBox.classList.add('d-none');
            spinnerModelo.classList.add('d-none');
        });
    })();
});
</script>
<?= $this->endSection() ?>

<?= $this->endSection() ?>

