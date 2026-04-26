<?php
$isEdit = isset($os);
$tipos  = $tipos  ?? [];
$marcas = $marcas ?? [];
$relatosRapidos = $relatosRapidos ?? [];
$statusGrouped = $statusGrouped ?? [];
$statusDefault = $statusDefault ?? ($isEdit ? (string)($os['status'] ?? 'triagem') : 'triagem');

$statusFlat = [];
foreach ($statusGrouped as $macro => $items) {
    if (!is_array($items)) {
        continue;
    }
    foreach ($items as $item) {
        $codigo = (string) ($item['codigo'] ?? '');
        if ($codigo === '') {
            continue;
        }
        $statusFlat[$codigo] = [
            'nome' => (string) ($item['nome'] ?? $codigo),
            'cor' => (string) ($item['cor'] ?? 'secondary'),
            'grupo' => (string) $macro,
        ];
    }
}
$statusSelecionado = (string) old('status', $statusDefault);
$statusDefaultLabel = (string) ($statusFlat[$statusSelecionado]['nome'] ?? $statusFlat[$statusDefault]['nome'] ?? 'Triagem');

$origemConversaId = (int) ($origemConversaId ?? 0);
$origemContatoId = (int) ($origemContatoId ?? 0);
$origemConversa = (isset($origemConversa) && is_array($origemConversa)) ? $origemConversa : null;
$origemContato = (isset($origemContato) && is_array($origemContato)) ? $origemContato : null;
$clientePreSelecionado = (int) ($clientePreSelecionado ?? 0);

$origemNomeHint = trim((string) ($origemNomeHint ?? ''));
if ($origemNomeHint === '') {
    $origemNomeHint = trim((string) ($origemContato['nome'] ?? $origemContato['whatsapp_nome_perfil'] ?? $origemConversa['nome_contato'] ?? ''));
}

$origemTelefoneHint = preg_replace('/\D+/', '', (string) ($origemTelefoneHint ?? '')) ?? '';
if ($origemTelefoneHint === '') {
    $origemTelefoneHint = preg_replace('/\D+/', '', (string) ($origemContato['telefone_normalizado'] ?? $origemContato['telefone'] ?? $origemConversa['telefone'] ?? '')) ?? '';
}

$isOrigemCentralWhatsapp = !$isEdit
    && ($origemConversaId > 0 || $origemContatoId > 0 || $clientePreSelecionado > 0 || $origemTelefoneHint !== '' || $origemNomeHint !== '');

$clienteSelecionadoDefault = $isEdit
    ? (int) ($os['cliente_id'] ?? 0)
    : ($clientePreSelecionado > 0 ? $clientePreSelecionado : 0);
$clienteSelecionadoNoForm = (int) old('cliente_id', $clienteSelecionadoDefault);
$equipamentoSelecionadoNoForm = (int) old('equipamento_id', (int) ($os['equipamento_id'] ?? 0));
$tecnicoSelecionadoNoForm = (string) old('tecnico_id', (string) ($os['tecnico_id'] ?? ''));
$prioridadeSelecionada = (string) old('prioridade', (string) ($os['prioridade'] ?? 'normal'));
$relatoClienteValue = (string) old('relato_cliente', (string) ($os['relato_cliente'] ?? ''));

$dataEntradaRaw = trim((string) old('data_entrada', $isEdit ? (string) ($os['data_entrada'] ?? '') : ''));
if ($dataEntradaRaw === '') {
    $dataEntradaRaw = date('Y-m-d H:i:s');
}
$dataEntradaTimestamp = strtotime($dataEntradaRaw);
$dataEntradaInputValue = $dataEntradaTimestamp ? date('Y-m-d\TH:i', $dataEntradaTimestamp) : date('Y-m-d\TH:i');

$dataPrevisaoRaw = trim((string) old('data_previsao', (string) ($os['data_previsao'] ?? '')));
$dataPrevisaoTimestamp = $dataPrevisaoRaw !== '' ? strtotime($dataPrevisaoRaw) : false;
$dataPrevisaoInputValue = $dataPrevisaoTimestamp ? date('Y-m-d', $dataPrevisaoTimestamp) : '';
$prazoEntregaPresets = [
    '1' => '1 dia',
    '3' => '3 dias',
    '7' => '7 dias',
    '30' => '30 dias',
];
$prazoEntregaSelecionado = '';
if ($dataEntradaTimestamp && $dataPrevisaoTimestamp) {
    $entradaBaseTimestamp = strtotime(date('Y-m-d', $dataEntradaTimestamp));
    $previsaoBaseTimestamp = strtotime(date('Y-m-d', $dataPrevisaoTimestamp));
    if ($entradaBaseTimestamp !== false && $previsaoBaseTimestamp !== false) {
        $prazoCalculado = (int) round(($previsaoBaseTimestamp - $entradaBaseTimestamp) / 86400);
        if ($prazoCalculado > 0) {
            $prazoEntregaSelecionado = (string) $prazoCalculado;
        }
    }
}

$clientes = $clientes ?? [];
$clientesMeta = [];
foreach ($clientes as $clienteItem) {
    $clienteId = (string) ($clienteItem['id'] ?? '');
    if ($clienteId === '') {
        continue;
    }

    $clienteNome = trim((string) ($clienteItem['nome_razao'] ?? ''));
    $clienteTelefone = trim((string) ($clienteItem['telefone1'] ?? $clienteItem['telefone'] ?? ''));
    $clienteEnderecoPartes = array_filter([
        trim((string) ($clienteItem['endereco'] ?? '')),
        trim((string) ($clienteItem['numero'] ?? '')),
        trim((string) ($clienteItem['bairro'] ?? '')),
        trim((string) ($clienteItem['cidade'] ?? '')),
        trim((string) ($clienteItem['uf'] ?? '')),
    ], static fn ($value) => $value !== '');

    $clientesMeta[$clienteId] = [
        'id' => $clienteId,
        'nome' => $clienteNome,
        'telefone' => $clienteTelefone,
        'endereco' => implode(', ', $clienteEnderecoPartes),
    ];
}

$isEmbedded = (bool) ($isEmbedded ?? false);
$embedQuery = $isEmbedded ? '?embed=1' : '';
$resolveEquipamentoFotoOptionUrl = static function ($rawPath): string {
    $arquivo = str_replace('\\', '/', ltrim(trim((string) $rawPath), '/'));
    if ($arquivo === '') {
        return '';
    }

    if (preg_match('/^https?:\/\//i', $arquivo)) {
        return $arquivo;
    }

    $basename = basename($arquivo);
    $candidates = [
        'uploads/equipamentos_perfil/' . $arquivo,
        'uploads/equipamentos_perfil/' . $basename,
        'uploads/equipamentos/' . $basename,
        $arquivo,
    ];

    foreach ($candidates as $candidate) {
        $publicPath = ltrim($candidate, '/');
        $absolutePath = FCPATH . str_replace('/', DIRECTORY_SEPARATOR, $publicPath);
        if (is_file($absolutePath)) {
            return base_url($publicPath) . '?v=' . filemtime($absolutePath);
        }
    }

    return '';
};
?>

<?= $this->extend($layout ?? 'layouts/main') ?>

<?= $this->section('content') ?>

<style>
.os-equip-select-result {
    display: grid;
    grid-template-columns: 52px minmax(0, 1fr);
    gap: 10px;
    align-items: center;
}

.os-equip-select-thumb {
    width: 52px;
    height: 52px;
    border-radius: 12px;
    border: 1px solid #dbe4f3;
    background: #eef2ff;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 auto;
}

.os-equip-select-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.os-equip-select-fallback {
    font-weight: 800;
    font-size: 1rem;
    color: #1e3a8a;
}

.os-equip-select-copy {
    min-width: 0;
    display: grid;
    gap: 2px;
}

.os-equip-select-copy strong {
    display: block;
    font-size: 0.92rem;
    line-height: 1.2;
    color: #0f172a;
}

.os-equip-select-copy small {
    display: block;
    line-height: 1.2;
    color: #64748b;
}

.select2-container--bootstrap-5 .select2-results__option .os-equip-select-result {
    padding: 2px 0;
}

.select2-container--bootstrap-5 .select2-selection--single .os-equip-select-result {
    grid-template-columns: 40px minmax(0, 1fr);
    gap: 8px;
}

.select2-container--bootstrap-5 .select2-selection--single .os-equip-select-thumb {
    width: 40px;
    height: 40px;
    border-radius: 10px;
}

.select2-container--bootstrap-5 .select2-selection--single .os-equip-select-copy strong {
    font-size: 0.86rem;
}

.select2-container--bootstrap-5 .select2-selection--single .os-equip-select-copy small {
    font-size: 0.72rem;
}

.select2-container--bootstrap-5 .select2-selection--single {
    min-height: 68px;
    padding-top: 6px;
    padding-bottom: 6px;
}

.select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
    white-space: normal;
    line-height: 1.2;
    padding-left: 0;
}

.select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow {
    top: 50%;
    transform: translateY(-50%);
}

.checklist-item-card {
    border: 1px solid #dbe4f3;
    border-radius: 12px;
    background: #f8fbff;
    padding: 0.85rem;
}

.checklist-item-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.65rem;
}

.checklist-item-title {
    margin: 0;
    font-size: 0.95rem;
    color: #0f172a;
}

.checklist-status-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
}

.checklist-status-btn {
    border-radius: 999px;
    padding: 0.2rem 0.55rem;
    font-size: 0.74rem;
    line-height: 1.2;
}

.checklist-photo-thumb {
    width: 64px;
    height: 64px;
    border-radius: 10px;
    border: 1px solid #dbe4f3;
    overflow: hidden;
    position: relative;
    background: #e2e8f0;
}

.checklist-photo-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.checklist-photo-remove {
    position: absolute;
    top: 4px;
    right: 4px;
    z-index: 2;
}

.checklist-entry-card {
    border: 1px solid rgba(226, 232, 240, 0.9);
    border-radius: 14px;
    background: rgba(255, 255, 255, 0.84);
    padding: 0.65rem;
}

.os-defeitos-selected-list {
    display: flex;
    flex-direction: column;
    gap: 0.65rem;
}

.os-defeitos-empty {
    border: 1px dashed #cbd5e1;
    border-radius: 10px;
    background: rgba(248, 250, 252, 0.8);
    color: #64748b;
    font-size: 0.84rem;
    padding: 0.75rem;
}

.os-defeito-item {
    border: 1px solid #dbe4f3;
    border-radius: 10px;
    background: #ffffff;
    padding: 0.6rem 0.7rem;
}

.os-defeito-item__title {
    margin: 0;
    color: #0f172a;
    font-size: 0.87rem;
    font-weight: 600;
}

.os-defeito-item__desc {
    margin: 0.15rem 0 0;
    color: #64748b;
    font-size: 0.78rem;
    line-height: 1.3;
}

.os-defeito-item__actions {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
}

.os-defeitos-modal-group {
    border: 1px solid #dbe4f3;
    border-radius: 12px;
    background: #f8fbff;
    padding: 0.75rem;
}

.os-cliente-tecnico-grid .os-inline-label {
    min-height: 38px;
    display: flex;
    align-items: center;
}

.os-cliente-tecnico-grid .os-inline-actions {
    margin-left: auto;
}

.os-cliente-tech-helper {
    min-height: 1.25rem;
}

.os-required-mark {
    color: #dc2626;
    font-weight: 700;
}

.os-operational-card {
    border: 1px solid #dbe4f3;
    border-radius: 14px;
    background: #f8fbff;
}

.procedimento-executado-item {
    border: 1px solid #dbe4f3;
    border-radius: 10px;
    background: #ffffff;
    padding: 0.6rem 0.7rem;
    font-size: 0.88rem;
    line-height: 1.35;
    word-break: break-word;
}

@media (max-width: 430px) {
    .procedimento-executado-toolbar .btn {
        width: 100%;
    }

    .os-cliente-tecnico-grid .os-inline-actions {
        width: 100%;
        justify-content: flex-start;
    }
}

@media (max-width: 390px) {
    .procedimento-executado-item {
        font-size: 0.83rem;
    }
}

@media (max-width: 360px) {
    .procedimento-executado-toolbar {
        gap: 0.45rem !important;
    }
}

@media (max-width: 320px) {
    .procedimento-executado-item {
        padding: 0.55rem 0.55rem;
    }
}

@media (max-width: 1199.98px) {
    .os-cliente-tecnico-grid .os-inline-label {
        min-height: auto;
    }

    .os-cliente-tech-helper {
        min-height: auto;
    }
}

/* SweetAlert2 deve sempre ficar acima dos modais tecnicos da OS (checklist/camera/crop). */
.swal2-container {
    z-index: 2600 !important;
}
</style>

<div class="page-header d-flex justify-content-between align-items-center">
    <div class="d-flex align-items-center gap-3">
        <h2><i class="bi bi-<?= $isEdit ? 'pencil' : 'plus-lg' ?> me-2"></i><?= $title ?></h2>
        <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('ordens-de-servico')">Ajuda</button>
    </div>
    <?php if (!$isEmbedded): ?>
    <a href="<?= base_url('os') ?>" class="btn btn-outline-secondary" data-back-default="<?= base_url('os') ?>">
        <i class="bi bi-arrow-left me-1"></i>Voltar
    </a>
    <?php endif; ?>
</div>

<!-- LAYOUT PRINCIPAL: SIDEBAR (foto) + CONTEÚDO -->
<div class="os-form-page">
<div class="row g-4 ds-split-layout align-items-start" style="align-items: flex-start;">

    <!-- SIDEBAR: Painel da foto do equipamento -->
    <div class="col-12 col-xl-4 col-xxl-3 ds-split-sidebar align-self-start" id="sidebarEquipamento" style="align-self: flex-start;">
        <div class="d-flex flex-column gap-3 ds-sticky-panel">
            <div class="card glass-card os-sidebar-card">
                <div class="card-body p-3">
                    <h6 class="os-panel-title">
                        <i class="bi bi-image me-1"></i>Foto do Equipamento
                    </h6>
                    <!-- Foto Principal -->
                    <div id="fotoPrincipalWrap" class="mb-3 text-center">
                        <div id="fotoMainBox" class="os-photo-mainbox rounded overflow-hidden d-none">
                            <a href="javascript:void(0)" id="fotoPrincipalLink" data-bs-toggle="modal" data-bs-target="#imageModal" data-img-src="" class="os-photo-mainlink d-block w-100 h-100">
                                <img id="fotoPrincipalImg" src="" alt="Foto do equipamento"
                                     class="os-photo-mainimg w-100 h-100">
                            </a>
                        </div>
                        <div id="fotoPlaceholder" class="os-photo-placeholder rounded align-items-center justify-content-center d-flex">
                            <div class="text-center text-muted">
                                <i class="bi bi-image os-photo-placeholder-icon"></i>
                                <p class="small mt-2 mb-0">Selecione um equipamento</p>
                            </div>
                        </div>
                    </div>

                    <div id="equipColorInfo" class="d-flex align-items-center gap-2 small text-muted mb-2 d-none">
                        <span id="equipColorSwatch" class="os-color-swatch d-inline-block rounded-circle border"></span>
                        <span id="equipColorName">Cor não informada</span>
                    </div>

                    <!-- Miniaturas -->
                    <div id="fotosMiniaturas" class="d-flex flex-wrap gap-2 justify-content-center"></div>

                    <!-- Info do Equipamento -->
                    <div id="equipInfoBox" class="os-equipment-info mt-3 p-2 rounded d-none">
                        <div id="equipInfoContent" class="text-muted"></div>
                    </div>
                    <?php if (can('equipamentos', 'editar')): ?>
                    <div class="mt-2">
                        <button class="btn btn-outline-primary btn-sm w-100 d-none" type="button" id="btnEditarEquipamento"
                                title="Editar equipamento selecionado">
                            <i class="bi bi-pencil-square me-1"></i>Editar equipamento
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card glass-card os-sidebar-card" id="resumoOsCard">
                <div class="card-body p-3">
                    <h6 class="os-panel-title">
                        <i class="bi bi-clipboard2-check me-1"></i>Resumo da OS
                    </h6>
                    <div class="d-flex flex-column gap-2 small">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Cliente</span>
                            <span class="d-flex align-items-center gap-2">
                                <span id="resumoCliente" class="os-summary-value">Não selecionado</span>
                                <span id="statusCliente" class="text-danger" title="Pendente"><i class="bi bi-x-circle-fill" aria-hidden="true"></i><span class="visually-hidden">Pendente</span></span>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Equipamento</span>
                            <span class="d-flex align-items-center gap-2">
                                <span id="resumoEquipamento" class="os-summary-value">Não selecionado</span>
                                <span id="statusEquipamento" class="text-danger" title="Pendente"><i class="bi bi-x-circle-fill" aria-hidden="true"></i><span class="visually-hidden">Pendente</span></span>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Técnico</span>
                            <span class="d-flex align-items-center gap-2">
                                <span id="resumoTecnico" class="os-summary-value">Não atribuído</span>
                                <span id="statusTecnico" class="text-danger" title="Pendente"><i class="bi bi-x-circle-fill" aria-hidden="true"></i><span class="visually-hidden">Pendente</span></span>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Prioridade</span>
                            <span class="d-flex align-items-center gap-2">
                                <span id="resumoPrioridade" class="badge text-bg-secondary">Normal</span>
                                <span id="statusPrioridade" class="text-success" title="Preenchido"><i class="bi bi-check-circle-fill" aria-hidden="true"></i><span class="visually-hidden">Preenchido</span></span>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Status</span>
                            <span class="d-flex align-items-center gap-2">
                                <span id="resumoStatus" class="badge text-bg-secondary"><?= esc($statusDefaultLabel) ?></span>
                                <span id="statusStatus" class="text-success" title="Preenchido"><i class="bi bi-check-circle-fill" aria-hidden="true"></i><span class="visually-hidden">Preenchido</span></span>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Entrada</span>
                            <span class="d-flex align-items-center gap-2">
                                <span id="resumoEntrada" class="os-summary-value">-</span>
                                <span id="statusEntrada" class="text-danger" title="Pendente"><i class="bi bi-x-circle-fill" aria-hidden="true"></i><span class="visually-hidden">Pendente</span></span>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Previsão</span>
                            <span class="d-flex align-items-center gap-2">
                                <span id="resumoPrevisao" class="os-summary-value">-</span>
                                <span id="statusPrevisao" class="text-danger" title="Pendente"><i class="bi bi-x-circle-fill" aria-hidden="true"></i><span class="visually-hidden">Pendente</span></span>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Relato</span>
                            <span class="d-flex align-items-center gap-2">
                                <span id="resumoRelato" class="os-summary-value">Vazio</span>
                                <span id="statusRelato" class="text-danger" title="Pendente"><i class="bi bi-x-circle-fill" aria-hidden="true"></i><span class="visually-hidden">Pendente</span></span>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Acessórios</span>
                            <span class="d-flex align-items-center gap-2">
                                <span id="resumoAcessorios" class="os-summary-value">Não informado</span>
                                <span id="statusAcessorios" class="text-danger" title="Pendente"><i class="bi bi-x-circle-fill" aria-hidden="true"></i><span class="visually-hidden">Pendente</span></span>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Checklist de entrada</span>
                            <span class="d-flex align-items-center gap-2">
                                <span id="resumoEstadoFisico" class="os-summary-value">Nao preenchido</span>
                                <span id="statusEstadoFisico" class="text-danger" title="Pendente"><i class="bi bi-x-circle-fill" aria-hidden="true"></i><span class="visually-hidden">Pendente</span></span>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Fotos de entrada</span>
                            <span class="d-flex align-items-center gap-2">
                                <span id="resumoFotosEntrada" class="os-summary-value">0</span>
                                <span id="statusFotos" class="text-danger" title="Pendente"><i class="bi bi-x-circle-fill" aria-hidden="true"></i><span class="visually-hidden">Pendente</span></span>
                            </span>
                        </div>
                        <?php if ($isEdit): ?>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Defeitos marcados</span>
                            <span class="d-flex align-items-center gap-2">
                                <span id="resumoDefeitos" class="os-summary-value">0</span>
                                <span id="statusDefeitos" class="text-danger" title="Pendente"><i class="bi bi-x-circle-fill" aria-hidden="true"></i><span class="visually-hidden">Pendente</span></span>
                            </span>
                        </div>
                        <?php endif; ?>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Rascunho</span>
                            <span id="resumoRascunho" class="os-summary-value">Não salvo</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ÁREA PRINCIPAL DO FORMULÁRIO -->
    <div class="col-12 col-xl-8 col-xxl-9 ds-split-main align-self-start" id="formCol" style="align-self: flex-start;">
        <div class="card glass-card os-form-shell h-auto" style="height: auto;">
            <div class="card-body" style="height: auto;">
                <form action="<?= $isEdit ? base_url('os/atualizar/' . $os['id']) : base_url('os/salvar') ?><?= $embedQuery ?>"
                      method="POST" enctype="multipart/form-data" id="formOs" class="h-auto" style="height: auto;" novalidate>
                    <?= csrf_field() ?>
                    <div id="osSubmitLoading" class="os-form-loading" aria-hidden="true">
                        <div class="os-form-loading-inner" role="status" aria-live="polite">
                            <div class="spinner-border spinner-border-sm text-primary" aria-hidden="true"></div>
                            <span><?= $isEdit ? 'Atualizando a OS...' : 'Abrindo a OS...' ?></span>
                        </div>
                    </div>
                    <?php if (!$isEdit): ?>
                    <input type="hidden" name="origem_conversa_id" value="<?= $origemConversaId > 0 ? $origemConversaId : '' ?>">
                    <input type="hidden" name="origem_contato_id" value="<?= $origemContatoId > 0 ? $origemContatoId : '' ?>">
                    <?php if ($isOrigemCentralWhatsapp): ?>
                    <div class="alert alert-primary d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div class="small mb-0">
                            <i class="bi bi-whatsapp me-1"></i>
                            <strong>Origem Central WhatsApp:</strong>
                            <?= esc($origemNomeHint !== '' ? $origemNomeHint : 'Contato sem nome') ?>
                            <?= $origemTelefoneHint !== '' ? ' (' . esc($origemTelefoneHint) . ')' : '' ?>
                            <?php if ($clienteSelecionadoNoForm > 0): ?>
                                <span class="badge text-bg-success-subtle text-success-emphasis border ms-2">Cliente ERP pre-selecionado</span>
                            <?php else: ?>
                                <span class="badge text-bg-info text-dark ms-2">Contato ainda sem vinculo em clientes</span>
                            <?php endif; ?>
                        </div>
                        <a href="<?= base_url('atendimento-whatsapp') ?>" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-arrow-left me-1"></i>Voltar para Central
                        </a>
                    </div>
                    <?php endif; ?>
                    <div id="osDraftAlert" class="alert alert-info os-draft-alert d-flex align-items-center justify-content-between gap-3 d-none">
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
                            <button class="nav-link active fw-bold" id="tab-cliente-btn" data-bs-toggle="tab" data-bs-target="#tab-cliente" type="button" role="tab" aria-controls="tab-cliente" aria-selected="true">Cliente</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold" id="tab-equipamento-btn" data-bs-toggle="tab" data-bs-target="#tab-equipamento" type="button" role="tab" aria-controls="tab-equipamento" aria-selected="false">Equipamento</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold" id="tab-defeito-btn" data-bs-toggle="tab" data-bs-target="#tab-defeito" type="button" role="tab" aria-controls="tab-defeito" aria-selected="false">Defeito</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold" id="tab-relato-btn" data-bs-toggle="tab" data-bs-target="#tab-relato" type="button" role="tab" aria-controls="tab-relato" aria-selected="false">Dados Operacionais</button>
                        </li>
                        <?php if ($isEdit): ?>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold" id="tab-solucao-btn" data-bs-toggle="tab" data-bs-target="#tab-solucao" type="button" role="tab" aria-controls="tab-solucao" aria-selected="false">Solução</button>
                        </li>
                        <?php endif; ?>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold" id="tab-fotos-btn" data-bs-toggle="tab" data-bs-target="#tab-fotos" type="button" role="tab" aria-controls="tab-fotos" aria-selected="false">Fotos</button>
                        </li>
                        <?php if ($isEdit): ?>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold" id="tab-financeiro-btn" data-bs-toggle="tab" data-bs-target="#tab-financeiro" type="button" role="tab" aria-controls="tab-financeiro" aria-selected="false">Peças e Orçamento</button>
                        </li>
                        <?php endif; ?>
                    </ul>

                    <div class="tab-content" id="osTabsContent">
                        <div class="tab-pane fade show active" id="tab-cliente" role="tabpanel" aria-labelledby="tab-cliente-btn" tabindex="0">
                            <div class="os-data-section mb-4">
                                <div class="os-data-section-title">
                                    <i class="bi bi-person-badge me-1"></i>Dados do cliente
                                </div>
                                <div class="os-tab-helper">
                                    Defina quem trouxe o equipamento e visualize rapidamente os dados de contato para atendimento.
                                </div>
                                <div class="row g-3 align-items-start os-cliente-tecnico-grid">
                                    <div class="col-12 col-xl-8 os-cliente-tech-col os-cliente-tech-col--cliente">
                                        <div class="os-inline-label">
                                            <label for="clienteOsSelect" class="form-label mb-0">
                                                <span>Cliente <span class="os-required-mark" aria-hidden="true">*</span></span>
                                            </label>
                                            <span class="os-inline-actions">
                                                <?php if (can('clientes', 'criar')): ?>
                                                <button class="btn btn-warning btn-sm os-inline-action-btn" type="button" id="btnNovoCliente" title="Cadastrar novo cliente">
                                                    <i class="bi bi-plus-lg"></i><span>Novo</span>
                                                </button>
                                                <?php endif; ?>
                                                <?php if (can('clientes', 'editar')): ?>
                                                <button class="btn btn-outline-info btn-sm os-inline-action-btn d-none" type="button" id="btnEditarClienteOS" title="Editar cliente selecionado">
                                                    <i class="bi bi-pencil"></i><span>Editar</span>
                                                </button>
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                        <select name="cliente_id" id="clienteOsSelect" class="form-select select2-clientes" required>
                                            <option value="">Selecione o cliente...</option>
                                            <?php foreach ($clientes as $c): ?>
                                            <?php $clienteMeta = $clientesMeta[(string) ($c['id'] ?? '')] ?? ['nome' => trim((string) ($c['nome_razao'] ?? '')), 'telefone' => '', 'endereco' => '']; ?>
                                            <option value="<?= $c['id'] ?>"
                                                data-nome="<?= esc($clienteMeta['nome']) ?>"
                                                data-telefone="<?= esc($clienteMeta['telefone']) ?>"
                                                data-endereco="<?= esc($clienteMeta['endereco']) ?>"
                                                <?= ($clienteSelecionadoNoForm === (int) $c['id']) ? 'selected' : '' ?>>
                                                <?= esc($clienteMeta['nome']) ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php if (!$isEdit && $isOrigemCentralWhatsapp && $clienteSelecionadoNoForm <= 0): ?>
                                        <div class="form-text text-warning">
                                            Este contato ainda nao esta vinculado ao cadastro de clientes. Selecione o cliente para abrir a OS.
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-12 col-xl-4 os-cliente-tech-col os-cliente-tech-col--tecnico">
                                        <div class="os-inline-label">
                                            <label for="tecnicoResponsavelSelect" class="form-label mb-0">
                                                <span>Tecnico Responsavel</span>
                                            </label>
                                        </div>
                                        <select name="tecnico_id" id="tecnicoResponsavelSelect" class="form-select">
                                            <option value="">Nao atribuido</option>
                                            <?php foreach ($tecnicos as $t): ?>
                                            <option value="<?= $t['id'] ?>" <?= ($tecnicoSelecionadoNoForm !== '' && $tecnicoSelecionadoNoForm === (string) $t['id']) ? 'selected' : '' ?>><?= esc($t['nome']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <small class="text-muted d-block mt-2 os-cliente-tech-helper">
                                            Defina o tecnico responsavel pela OS.
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <div class="os-data-section mb-4">
                                <div class="os-data-section-title">
                                    <i class="bi bi-card-text me-1"></i>Resumo do cliente selecionado
                                </div>
                                <div class="os-client-card" id="clienteInfoCard">
                                    <div class="os-client-card__empty" id="clienteInfoEmpty">Selecione um cliente para visualizar nome, telefone e endereco de atendimento.</div>
                                    <div class="os-client-card__content d-none" id="clienteInfoContent">
                                        <div class="os-client-card__name" id="clienteInfoNome">-</div>
                                        <div class="os-client-card__meta-list">
                                            <div class="os-client-card__meta-item d-none" id="clienteInfoTelefoneWrap"><i class="bi bi-telephone"></i><span id="clienteInfoTelefone">-</span></div>
                                            <div class="os-client-card__meta-item d-none" id="clienteInfoEnderecoWrap"><i class="bi bi-geo-alt"></i><span id="clienteInfoEndereco">-</span></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="tab-equipamento" role="tabpanel" aria-labelledby="tab-equipamento-btn" tabindex="0">

                            <div class="os-data-section mb-4">
                                <div class="os-data-section-title">
                                    <i class="bi bi-laptop me-1"></i>Equipamento em atendimento
                                </div>
                                <div class="os-tab-helper">
                                    Escolha o aparelho do cliente e registre as condicoes tecnicas observadas na entrada.
                                </div>
                                <div class="row g-3">
                                    <div class="col-12">
                                        <div class="os-inline-label">
                                            <label for="equipamentoSelect" class="form-label mb-0">
                                                <span>Equipamento <span class="os-required-mark" aria-hidden="true">*</span></span>
                                            </label>
                                            <span class="os-inline-actions">
                                                <?php if (can('equipamentos', 'criar')): ?>
                                                <button class="btn btn-warning btn-sm os-inline-action-btn" type="button" id="btnNovoEquipamento" title="Cadastrar novo equipamento">
                                                    <i class="bi bi-plus-lg"></i><span>Novo</span>
                                                </button>
                                                <?php endif; ?>
                                                <?php if (can('equipamentos', 'editar')): ?>
                                                <button class="btn btn-outline-info btn-sm os-inline-action-btn d-none" type="button" id="btnEditarEquipamentoInline" title="Editar equipamento selecionado">
                                                    <i class="bi bi-pencil-square"></i><span>Editar</span>
                                                </button>
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                        <select name="equipamento_id" id="equipamentoSelect" class="form-select select2-equip" required>
                                            <option value="">Selecione o cliente primeiro...</option>
                                            <?php if ($isEdit && !empty($equipamentos)): foreach ($equipamentos as $eq): ?>
                                            <?php
                                                $eqFotoUrl = $resolveEquipamentoFotoOptionUrl($eq['foto_principal_arquivo'] ?? '');
                                            ?>
                                            <option value="<?= $eq['id'] ?>"
                                                data-tipo="<?= $eq['tipo_id'] ?? '' ?>"
                                                data-marca="<?= esc($eq['marca_nome'] ?? $eq['marca'] ?? '') ?>"
                                                data-modelo="<?= esc($eq['modelo_nome'] ?? $eq['modelo'] ?? '') ?>"
                                                data-serie="<?= esc($eq['numero_serie'] ?? '') ?>"
                                                data-imei="<?= esc($eq['imei'] ?? '') ?>"
                                                data-cor="<?= esc($eq['cor'] ?? '') ?>"
                                                data-cor_hex="<?= esc($eq['cor_hex'] ?? '') ?>"
                                                data-tipo_nome="<?= esc($eq['tipo_nome'] ?? $eq['tipo'] ?? '') ?>"
                                                data-marca_id="<?= esc($eq['marca_id'] ?? '') ?>"
                                                data-modelo_id="<?= esc($eq['modelo_id'] ?? '') ?>"
                                                data-cliente_id="<?= esc($eq['cliente_id'] ?? '') ?>"
                                                data-senha_acesso="<?= esc($eq['senha_acesso'] ?? '') ?>"
                                                data-estado_fisico="<?= esc($eq['estado_fisico'] ?? '') ?>"
                                                data-acessorios="<?= esc($eq['acessorios'] ?? '') ?>"
                                                data-foto_url="<?= esc($eqFotoUrl) ?>"
                                                <?= ($equipamentoSelecionadoNoForm === (int) $eq['id']) ? 'selected' : '' ?>>
                                                <?= esc(($eq['marca_nome'] ?? $eq['marca'] ?? '') . ' ' . ($eq['modelo_nome'] ?? $eq['modelo'] ?? '') . ' (' . ($eq['tipo_nome'] ?? $eq['tipo'] ?? '') . ')') ?>
                                            </option>
                                            <?php endforeach; endif; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3 os-equip-panels-row">
                                <div class="col-12 col-xxl-6">
                                    <div class="os-data-section os-equip-checklist-section mb-4 h-100">
                                        <div class="os-data-section-title">
                                            <i class="bi bi-ui-checks-grid me-1"></i>Checklist de entrada
                                        </div>
                                        <div class="border rounded-3 p-3 bg-white bg-opacity-10 h-100 d-flex flex-column gap-2 os-equip-panel-card">
                                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                                                <button type="button" class="btn btn-outline-primary btn-sm" id="btnChecklistEntrada">
                                                    <i class="bi bi-ui-checks-grid me-1"></i>Checklist
                                                </button>
                                                <span class="badge text-bg-secondary" id="checklistEntradaBadge">Pendente</span>
                                            </div>
                                            <div class="os-checklist-status-card os-checklist-status-pending" id="checklistEntradaStatusCard">
                                                <div class="os-checklist-status-icon" id="checklistEntradaStatusIcon">
                                                    <i class="bi bi-hourglass-split"></i>
                                                </div>
                                                <div class="os-checklist-status-body">
                                                    <div class="os-checklist-status-title" id="checklistEntradaStatusTitle">Checklist pendente de preenchimento</div>
                                                    <div id="checklistEntradaInlineInfo" class="os-checklist-status-helper">
                                                        Abra o checklist e marque todos os itens para concluir a entrada.
                                                    </div>
                                                </div>
                                            </div>
                                            <input type="hidden" name="checklist_entrada_data" id="checklistEntradaDataInput">
                                            <input type="file" id="checklistEntradaPhotoInput" class="d-none" accept="image/jpeg,image/png,image/webp" multiple>
                                            <div id="checklistEntradaFilesInputs" class="d-none"></div>
                                            <textarea name="estado_fisico" id="estadoFisicoInput" class="d-none"><?= $isEdit ? esc($os['estado_fisico'] ?? '') : old('estado_fisico') ?></textarea>
                                            <input type="hidden" name="estado_fisico_data" id="estadoFisicoDataInput">
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12 col-xxl-6">
                                    <div class="os-data-section os-equip-acessorios-section mb-4 h-100">
                                        <div class="os-data-section-title">
                                            <i class="bi bi-box-seam me-1"></i>Acessorios e Componentes (na entrada)
                                        </div>
                                        <div class="border rounded-3 p-3 bg-white bg-opacity-10 h-100 os-equip-panel-card">
                                            <div class="d-flex flex-wrap gap-2 mb-2">
                                                <button type="button" class="btn btn-sm btn-outline-secondary" data-acessorio-key="chip">+ Chip</button>
                                                <button type="button" class="btn btn-sm btn-outline-secondary" data-acessorio-key="capinha">+ Capinha celular</button>
                                                <button type="button" class="btn btn-sm btn-outline-secondary" data-acessorio-key="capa">+ Capa</button>
                                                <button type="button" class="btn btn-sm btn-outline-secondary" data-acessorio-key="mochila">+ Mochila</button>
                                                <button type="button" class="btn btn-sm btn-outline-secondary" data-acessorio-key="bolsa">+ Bolsa notebook</button>
                                                <button type="button" class="btn btn-sm btn-outline-secondary" data-acessorio-key="cabo">+ Cabo</button>
                                                <button type="button" class="btn btn-sm btn-outline-secondary" data-acessorio-key="carregador">+ Carregador</button>
                                                <button type="button" class="btn btn-sm btn-outline-secondary" data-acessorio-key="outro">+ Outro acessorio</button>
                                            </div>
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" id="acessoriosSemItens" name="acessorios_sem_itens" value="1" <?= old('acessorios_sem_itens') ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="acessoriosSemItens">Equipamento recebido sem acessorios</label>
                                            </div>
                                            <div id="acessoriosQuickForm" class="border rounded p-3 bg-body-tertiary mb-3 d-none">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <strong id="acessoriosQuickTitle"></strong>
                                                    <button type="button" class="btn-close" id="acessoriosQuickClose"></button>
                                                </div>
                                                <div id="acessoriosQuickFields" class="row g-2"></div>
                                                <div id="acessoriosQuickPhotosBlock" class="mt-3 border rounded p-2 bg-white">
                                                    <div class="small fw-semibold text-uppercase text-muted mb-2">Fotos do acessorio</div>
                                                    <div class="d-flex flex-wrap gap-2">
                                                        <button type="button" class="btn btn-sm btn-outline-info" id="acessoriosQuickAddFoto">
                                                            <i class="bi bi-images me-1"></i>Galeria
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-primary" id="acessoriosQuickAddFotoCamera">
                                                            <i class="bi bi-camera-fill me-1"></i>Camera
                                                        </button>
                                                    </div>
                                                    <div id="acessoriosQuickPhotosHint" class="small text-muted mt-2">Adicione fotos antes de salvar o item.</div>
                                                    <div id="acessoriosQuickPhotosPreview" class="d-flex gap-2 flex-wrap mt-2"></div>
                                                </div>
                                                <div class="mt-3">
                                                    <button type="button" class="btn btn-sm btn-primary" id="acessoriosQuickSave">Salvar item</button>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="acessoriosQuickCancel">Cancelar</button>
                                                </div>
                                            </div>
                                            <div id="acessoriosList" class="list-group"></div>
                                            <small class="form-text text-muted mt-3">Padronize rapidamente o registro de acessorios comuns.</small>
                                            <textarea name="acessorios" id="acessoriosInput" class="d-none"><?= $isEdit ? esc($os['acessorios'] ?? '') : old('acessorios') ?></textarea>
                                            <input type="hidden" name="acessorios_data" id="acessoriosDataInput">
                                            <input type="file" id="acessoriosPhotoInput" class="d-none" accept="image/jpeg,image/png,image/webp" multiple>
                                            <div id="acessoriosFilesInputs" class="d-none"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div class="tab-pane fade" id="tab-defeito" role="tabpanel" aria-labelledby="tab-defeito-btn" tabindex="0">
                            <div class="os-data-section mb-4">
                                <div class="os-data-section-title">
                                    <i class="bi bi-chat-left-text me-1"></i>Relato do cliente
                                </div>
                                <div class="os-tab-helper">
                                    Registre o que foi informado na entrada antes de detalhar o equipamento e as condicoes tecnicas.
                                </div>
                                <label for="relatoClienteInput" class="form-label">Relato do Cliente <span class="os-required-mark" aria-hidden="true">*</span></label>
                                <?php if (!$isEdit): ?>
                                <div class="mb-3">
                                    <div id="relatoQuickButtons" class="d-flex flex-wrap gap-2 relato-quick-grid">
                                        <?php if (!empty($relatosRapidos)): ?>
                                            <?php foreach ($relatosRapidos as $categoria): ?>
                                                <?php $itensCategoria = array_values(array_filter((array) ($categoria['itens'] ?? []), static function ($item) {
                                                    return trim((string) ($item['texto_relato'] ?? '')) !== '';
                                                })); ?>
                                                <?php if (empty($itensCategoria)) continue; ?>
                                                <div class="dropdown">
                                                    <button
                                                        class="btn btn-sm btn-outline-secondary dropdown-toggle os-relato-dropdown-btn"
                                                        type="button"
                                                        data-bs-toggle="dropdown"
                                                        aria-expanded="false"
                                                        title="<?= esc((string) ($categoria['categoria'] ?? 'Relatos')) ?>">
                                                        <?= esc($categoria['icone'] ?? '?') ?> <?= esc($categoria['categoria'] ?? 'Relatos') ?>
                                                    </button>
                                                    <ul class="dropdown-menu shadow-sm os-relato-dropdown-menu">
                                                        <?php foreach ($itensCategoria as $item): ?>
                                                            <?php $relatoTexto = trim((string) ($item['texto_relato'] ?? '')); ?>
                                                            <li>
                                                                <button
                                                                    type="button"
                                                                    class="dropdown-item btn-relato-opcao"
                                                                    data-relato-opcao="<?= esc($relatoTexto) ?>"
                                                                    title="<?= esc($relatoTexto) ?>">
                                                                    <?= esc($relatoTexto) ?>
                                                                </button>
                                                            </li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <span class="text-muted small">
                                                Nenhum relato rapido ativo. Cadastre em
                                                <a href="<?= base_url('defeitosrelatados') ?>">Defeitos Relatados</a>.
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <small class="text-muted d-block mt-2">Escolha a categoria e clique em um item rapido para inserir no relato.</small>
                                </div>
                                <?php endif; ?>
                                <textarea name="relato_cliente" id="relatoClienteInput" class="form-control" rows="6"><?= esc($relatoClienteValue) ?></textarea>
                                <?php if (!$isEdit): ?>
                                <small class="text-muted d-block mt-2">Voce pode complementar manualmente o relato a qualquer momento.</small>
                                <?php endif; ?>
                            </div>

                            <?php if ($isEdit): ?>
                            <div class="os-data-section mb-4" id="defeitosSection" style="display:none;">
                                <div class="os-data-section-title">
                                    <i class="bi bi-bug me-1"></i>Defeitos comuns do tipo de equipamento
                                </div>
                                <div class="border rounded-3 p-3 bg-white bg-opacity-10">
                                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                                        <div class="small text-muted" id="defeitosHelperText">
                                            Selecione os defeitos que se aplicam ao diagnostico atual.
                                        </div>
                                        <button type="button" class="btn btn-outline-primary btn-sm" id="btnAdicionarDefeitosComuns" disabled>
                                            <i class="bi bi-plus-circle me-1"></i>Adicionar defeitos comuns
                                        </button>
                                    </div>
                                    <div id="defeitosContainer" class="os-defeitos-selected-list">
                                        <span class="text-muted small">Selecione o equipamento para carregar os defeitos...</span>
                                    </div>
                                </div>
                            </div>

                            <div class="modal fade" id="modalDefeitosComuns" tabindex="-1" aria-hidden="true" style="z-index: 2100;">
                                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                                    <div class="modal-content glass-card">
                                        <div class="modal-header border-bottom">
                                            <h5 class="modal-title">
                                                <i class="bi bi-bug me-2 text-warning"></i>Defeitos comuns do equipamento
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p class="small text-muted mb-3" id="modalDefeitosHint">
                                                Selecione os defeitos para adicionar ao card da OS.
                                            </p>
                                            <div id="modalDefeitosComunsBody">
                                                <span class="text-muted small">Selecione o equipamento para carregar os defeitos.</span>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Fechar</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="tab-pane fade" id="tab-relato" role="tabpanel" aria-labelledby="tab-relato-btn" tabindex="0">
                            <div class="os-data-section mb-4">
                                <div class="os-data-section-title">
                                    <i class="bi bi-calendar-check me-1"></i>Dados Operacionais
                                </div>
                                <div class="os-tab-helper">
                                    Gerencie prioridade, datas, garantia e status operacional sem misturar esse fluxo com cadastro, relato e diagnostico.
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label">Prioridade</label>
                                        <select name="prioridade" class="form-select">
                                            <option value="baixa"   <?= $prioridadeSelecionada === 'baixa' ? 'selected' : '' ?>>Baixa</option>
                                            <option value="normal"  <?= $prioridadeSelecionada === 'normal' ? 'selected' : '' ?>>Normal</option>
                                            <option value="alta"    <?= $prioridadeSelecionada === 'alta' ? 'selected' : '' ?>>Alta</option>
                                            <option value="urgente" <?= $prioridadeSelecionada === 'urgente' ? 'selected' : '' ?>>Urgente</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Data de Entrada <span class="os-required-mark" aria-hidden="true">*</span></label>
                                        <input type="datetime-local" name="data_entrada" class="form-control" value="<?= esc($dataEntradaInputValue) ?>" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Previsao de Entrega</label>
                                        <select id="prazoEntregaSelect" class="form-select mb-2">
                                            <option value="" <?= $prazoEntregaSelecionado === '' ? 'selected' : '' ?>>Prazo (dias)</option>
                                            <?php foreach ($prazoEntregaPresets as $prazoDias => $prazoLabel): ?>
                                            <option value="<?= esc($prazoDias) ?>" <?= $prazoEntregaSelecionado === (string) $prazoDias ? 'selected' : '' ?>>
                                                <?= esc($prazoLabel) ?>
                                            </option>
                                            <?php endforeach; ?>
                                            <?php if ($prazoEntregaSelecionado !== '' && !isset($prazoEntregaPresets[$prazoEntregaSelecionado])): ?>
                                            <option value="<?= esc($prazoEntregaSelecionado) ?>" selected data-dynamic-option="1">
                                                <?= esc($prazoEntregaSelecionado . ((int) $prazoEntregaSelecionado === 1 ? ' dia' : ' dias')) ?>
                                            </option>
                                            <?php endif; ?>
                                        </select>
                                        <input type="date" name="data_previsao" class="form-control" value="<?= esc($dataPrevisaoInputValue) ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Status</label>
                                        <select name="status" class="form-select">
                                            <?php if (!empty($statusGrouped)): ?>
                                                <?php foreach ($statusGrouped as $macro => $items): ?>
                                                    <?php if (empty($items) || !is_array($items)) continue; ?>
                                                    <optgroup label="<?= esc(ucwords(str_replace('_', ' ', (string) $macro))) ?>">
                                                        <?php foreach ($items as $item): ?>
                                                            <?php $codigo = (string) ($item['codigo'] ?? ''); ?>
                                                            <?php if ($codigo === '') continue; ?>
                                                            <option value="<?= esc($codigo) ?>" data-status-cor="<?= esc((string) ($item['cor'] ?? 'secondary')) ?>" <?= ($statusSelecionado === $codigo) ? 'selected' : '' ?>>
                                                                <?= esc((string) ($item['nome'] ?? $codigo)) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </optgroup>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <?php $currStatus = $statusSelecionado; ?>
                                                <option value="triagem" <?= $currStatus === 'triagem' ? 'selected' : '' ?>>Triagem</option>
                                                <option value="diagnostico" <?= $currStatus === 'diagnostico' ? 'selected' : '' ?>>Diagnostico Tecnico</option>
                                                <option value="aguardando_orcamento" <?= $currStatus === 'aguardando_orcamento' ? 'selected' : '' ?>>Aguardando Orcamento</option>
                                                <option value="aguardando_autorizacao" <?= $currStatus === 'aguardando_autorizacao' ? 'selected' : '' ?>>Aguardando Autorizacao</option>
                                                <option value="reparo_execucao" <?= $currStatus === 'reparo_execucao' ? 'selected' : '' ?>>Em Execucao</option>
                                                <option value="reparado_disponivel_loja" <?= $currStatus === 'reparado_disponivel_loja' ? 'selected' : '' ?>>Pronto para retirada</option>
                                                <option value="entregue_reparado" <?= $currStatus === 'entregue_reparado' ? 'selected' : '' ?>>Entregue</option>
                                                <option value="cancelado" <?= $currStatus === 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
                                            <?php endif; ?>
                                        </select>
                                        <?php if ($isEdit): ?>
                                        <small class="text-muted d-block mt-2">A edicao permite ajustar qualquer status operacional cadastrado para esta OS.</small>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($isEdit): ?>
                                    <div class="col-md-3">
                                        <label class="form-label">Garantia (dias)</label>
                                        <input type="number" name="garantia_dias" class="form-control" value="<?= $os['garantia_dias'] ?? 90 ?>">
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php if ($isEdit): ?>
                        <div class="tab-pane fade" id="tab-solucao" role="tabpanel" aria-labelledby="tab-solucao-btn" tabindex="0">
                            <div class="os-data-section mb-4">
                                <div class="os-data-section-title">
                                    <i class="bi bi-activity me-1"></i>Solução e Diagnóstico
                                </div>
                                <div class="os-tab-helper">
                                    Registre os procedimentos executados e consolide a solução aplicada e o diagnóstico técnico da OS.
                                </div>
                                <div class="row g-3">
                                    <div class="col-12">
                                        <div class="card os-operational-card h-100">
                                            <div class="card-body p-3">
                                                <label class="form-label fw-semibold mb-2">Procedimentos executados</label>
                                                <div class="procedimento-executado-toolbar d-flex flex-column flex-md-row gap-2 mb-2">
                                                    <input
                                                        type="text"
                                                        class="form-control"
                                                        id="procedimentoExecutadoTextoInput"
                                                        placeholder="Ex.: feito testes no processador">
                                                    <button type="button" class="btn btn-outline-primary" id="btnInserirProcedimentoExecutado">
                                                        + Inserir novo procedimento
                                                    </button>
                                                </div>
                                                <textarea name="procedimentos_executados" id="procedimentosExecutadosInput" class="d-none"><?= esc($os['procedimentos_executados'] ?? '') ?></textarea>
                                                <div id="procedimentosExecutadosLista" class="d-flex flex-column gap-2"></div>
                                                <small class="text-muted d-block mt-2">
                                                    Cada inserção registra automaticamente data/hora e técnico selecionado.
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12 col-xl-6">
                                        <div class="card os-operational-card h-100">
                                            <div class="card-body p-3">
                                                <label class="form-label fw-semibold">Solução aplicada</label>
                                                <textarea name="solucao_aplicada" id="solucaoAplicadaInput" class="form-control" rows="5"><?= esc($os['solucao_aplicada'] ?? '') ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12 col-xl-6">
                                        <div class="card os-operational-card h-100">
                                            <div class="card-body p-3">
                                                <label class="form-label fw-semibold">Diagnóstico</label>
                                                <textarea name="diagnostico_tecnico" id="diagnosticoTecnicoInput" class="form-control" rows="5"><?= esc($os['diagnostico_tecnico'] ?? '') ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if (false): ?>
                    <fieldset class="d-none" disabled aria-hidden="true">
                    <div class="os-data-section mb-4">
                        <div class="os-data-section-title">
                            <i class="bi bi-people me-1"></i>Cliente, Equipamento e Técnico Responsável
                        </div>
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
                                <?php if (can('clientes', 'editar')): ?>
                                <button class="btn btn-outline-info btn-sm py-0 px-2 d-none" type="button" id="btnEditarClienteOS"
                                        title="Editar cliente selecionado" style="font-size:0.75rem; border-radius:6px; line-height:1.6;">
                                    <i class="bi bi-pencil"></i> Editar
                                </button>
                                <?php endif; ?>
                            </label>
                            <select name="cliente_id" id="clienteOsSelect" class="form-select select2-clientes" required>
                                <option value="">Selecione o cliente...</option>
                                <?php foreach ($clientes as $c): ?>
                                <option value="<?= $c['id'] ?>"
                                    <?= ($clienteSelecionadoNoForm === (int) $c['id']) ? 'selected' : '' ?>>
                                    <?= esc($c['nome_razao']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (!$isEdit && $isOrigemCentralWhatsapp && $clienteSelecionadoNoForm <= 0): ?>
                            <div class="form-text text-warning">
                                Este contato ainda nao esta vinculado ao cadastro de clientes. Selecione o cliente para abrir a OS.
                            </div>
                            <?php endif; ?>
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
                                    data-marca_id="<?= esc($eq['marca_id'] ?? '') ?>"
                                    data-modelo_id="<?= esc($eq['modelo_id'] ?? '') ?>"
                                    data-cliente_id="<?= esc($eq['cliente_id'] ?? '') ?>"
                                    data-senha_acesso="<?= esc($eq['senha_acesso'] ?? '') ?>"
                                    data-estado_fisico="<?= esc($eq['estado_fisico'] ?? '') ?>"
                                    data-acessorios="<?= esc($eq['acessorios'] ?? '') ?>"
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

                    </div>
                    </fieldset>
                    </fieldset>

                    <fieldset class="d-none" disabled aria-hidden="true">
                    <div class="os-data-section mb-4">
                        <div class="os-data-section-title">
                            <i class="bi bi-calendar-check me-1"></i>Prioridade, Entrada, Previsão e Status
                        </div>
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
                                <?php if (!empty($statusGrouped)): ?>
                                    <?php foreach ($statusGrouped as $macro => $items): ?>
                                        <?php if (empty($items) || !is_array($items)) continue; ?>
                                        <optgroup label="<?= esc(ucwords(str_replace('_', ' ', (string) $macro))) ?>">
                                            <?php foreach ($items as $item): ?>
                                                <?php $codigo = (string) ($item['codigo'] ?? ''); ?>
                                                <?php if ($codigo === '') continue; ?>
                                                <option value="<?= esc($codigo) ?>" data-status-cor="<?= esc((string) ($item['cor'] ?? 'secondary')) ?>" <?= ((string) ($os['status'] ?? $statusDefault) === $codigo) ? 'selected' : '' ?>>
                                                    <?= esc((string) ($item['nome'] ?? $codigo)) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <?php $currStatus = (string) ($os['status'] ?? $statusDefault); ?>
                                    <option value="triagem" <?= $currStatus === 'triagem' ? 'selected' : '' ?>>Triagem</option>
                                    <option value="diagnostico" <?= $currStatus === 'diagnostico' ? 'selected' : '' ?>>Diagnostico Tecnico</option>
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
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-estado-key="outro">+ Outro dano</button>
                            </div>
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="estadoFisicoSemAvariasLegacy" value="1">
                                <label class="form-check-label" for="estadoFisicoSemAvariasLegacy">Sem avarias aparentes na entrada</label>
                            </div>
                            <div id="estadoFisicoQuickFormLegacy" class="border rounded p-3 bg-body-tertiary mb-3 d-none">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <strong id="estadoFisicoQuickTitleLegacy"></strong>
                                    <button type="button" class="btn-close" id="estadoFisicoQuickCloseLegacy"></button>
                                </div>
                                <div id="estadoFisicoQuickFieldsLegacy" class="row g-2"></div>
                                <div class="mt-3">
                                    <button type="button" class="btn btn-sm btn-primary" id="estadoFisicoQuickSaveLegacy">Salvar item</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="estadoFisicoQuickCancelLegacy">Cancelar</button>
                                </div>
                            </div>
                            <div id="estadoFisicoListLegacy" class="list-group"></div>
                            <small class="form-text text-muted mt-3">Registre danos observados na recepcao com foto para evidenciar o estado de entrada.</small>
                            <textarea name="estado_fisico_legacy" id="estadoFisicoInputLegacy" class="d-none"><?= $isEdit ? esc($os['estado_fisico'] ?? '') : old('estado_fisico') ?></textarea>
                            <input type="hidden" name="estado_fisico_data_legacy" id="estadoFisicoDataInputLegacy">
                            <input type="file" id="estadoFisicoPhotoInputLegacy" class="d-none" accept="image/jpeg,image/png,image/webp" multiple>
                            <div id="estadoFisicoFilesInputsLegacy" class="d-none"></div>
                        </div>
                    </div>

                    <div class="os-data-section mb-4">
                            <div class="os-data-section-title">
                                <i class="bi bi-box-seam me-1"></i>Acessórios e Componentes (na entrada)
                            </div>
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
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="acessoriosSemItensLegacy" name="acessorios_sem_itens_legacy" value="1" <?= old('acessorios_sem_itens_legacy') ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="acessoriosSemItens">Equipamento recebido sem acessórios</label>
                                </div>
                                <div id="acessoriosQuickFormLegacy" class="border rounded p-3 bg-body-tertiary mb-3 d-none">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <strong id="acessoriosQuickTitleLegacy"></strong>
                                        <button type="button" class="btn-close" id="acessoriosQuickCloseLegacy"></button>
                                    </div>
                                    <div id="acessoriosQuickFieldsLegacy" class="row g-2"></div>
                                    <div class="mt-3">
                                        <button type="button" class="btn btn-sm btn-primary" id="acessoriosQuickSaveLegacy">Salvar item</button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" id="acessoriosQuickCancelLegacy">Cancelar</button>
                                    </div>
                                </div>
                                <div id="acessoriosListLegacy" class="list-group"></div>
                                <small class="form-text text-muted mt-3">Padronize rapidamente o registro de acessórios comuns.</small>
                                <textarea name="acessorios_legacy" id="acessoriosInputLegacy" class="d-none"><?= $isEdit ? esc($os['acessorios'] ?? '') : old('acessorios') ?></textarea>
                                <input type="hidden" name="acessorios_data_legacy" id="acessoriosDataInputLegacy">
                                <input type="file" id="acessoriosPhotoInputLegacy" class="d-none" accept="image/jpeg,image/png,image/webp" multiple>
                                <div id="acessoriosFilesInputsLegacy" class="d-none"></div>
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
                                            Nenhum relato rápido ativo. Cadastre em
                                            <a href="<?= base_url('defeitosrelatados') ?>">Defeitos Relatados</a>.
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <small class="text-muted d-block mt-2">Clique em uma opção para inserir no relato.</small>
                            </div>
                            <?php endif; ?>
                            <textarea name="relato_cliente" id="relatoClienteInput" class="form-control" rows="6"><?= $isEdit ? esc($os['relato_cliente']) : old('relato_cliente') ?></textarea>
                            <?php if (!$isEdit): ?>
                            <small class="text-muted d-block mt-2">Você pode complementar manualmente o relato a qualquer momento.</small>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($isEdit): ?>
                    <div class="row g-3 mb-4" id="defeitosSection" style="display:none;">
                        <div class="col-12">
                            <div class="card" style="background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.1); border-radius: 10px;">
                                <div class="card-header py-2" style="background: transparent; border-bottom: 1px solid rgba(255,255,255,0.1);">
                                    <strong><i class="bi bi-bug me-2 text-warning"></i>Defeitos Comuns do Tipo de Equipamento</strong>
                                    <small class="text-muted ms-2">(opcional ? selecione os que se aplicam)</small>
                                </div>
                                <div class="card-body" id="defeitosContainer">
                                    <span class="text-muted small">Selecione o equipamento para carregar os defeitos...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                        <?php endif; ?>
                        <div class="tab-pane fade" id="tab-fotos" role="tabpanel" aria-labelledby="tab-fotos-btn" tabindex="0">
                    <!-- FOTOS DE ENTRADA DO EQUIPAMENTO -->
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                    <div class="card os-tab-card os-photo-card">
                        <div class="card-header os-tab-card-header py-3 d-flex flex-column flex-md-row justify-content-between gap-2">
                            <div>
                                <strong><i class="bi bi-camera me-2 text-info"></i>Fotos de Entrada do Equipamento</strong>
                                <small class="text-muted ms-2">(opcional: acessórios, estado físico, placa interna, etc.)</small>
                            </div>
                            <div class="os-photo-card-actions d-flex justify-content-center justify-content-md-end gap-2 flex-wrap">
                                <button type="button" class="btn btn-outline-light btn-sm d-none" id="btnFotosEscolher">
                                    <i class="bi bi-folder2-open me-1"></i>Escolher Arquivos
                                </button>
                                <button type="button" class="btn btn-primary btn-sm" id="btnFotosEntradaCamera">
                                    <i class="bi bi-camera-fill me-1"></i>Capturar Foto
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="btnFotosEntradaGaleria">
                                    <i class="bi bi-images me-1"></i>Abrir Galeria
                                </button>
                                <button type="button" class="btn btn-outline-warning btn-sm" id="btnLimparFotos">
                                    <i class="bi bi-trash me-1"></i>Limpar
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <input type="file" id="fotosEntradaGaleriaInput" accept="image/*" multiple class="d-none">
                            <input type="file" name="fotos_entrada[]" id="fotosEntradaInput"
                                   accept="image/jpeg,image/png,image/webp"
                                   multiple class="d-none">
                            <div class="os-empty-state p-3 border rounded mb-4 text-center py-4 d-none" id="fotosEntradaEmptyState">
                                <i class="bi bi-cloud-upload display-5 text-muted opacity-25"></i>
                                <h6 class="mt-3 text-muted mb-1">Nenhuma foto anexada</h6>
                                <p class="text-muted small mb-0">Use Capturar Foto ou Abrir Galeria para adicionar as imagens da entrada.</p>
                            </div>
                            <div class="alert alert-info os-photo-info-banner border-0 shadow-sm d-flex align-items-center mb-3 mx-auto">
                                <i class="bi bi-info-circle-fill fs-5 me-2"></i>
                                <div class="small">At&eacute; <strong>4 fotos</strong>, 2MB cada. O sistema abre o ajuste de corte antes de importar.</div>
                            </div>
                            <div id="osFotosDropzone" class="os-photo-dropzone border rounded-4 d-none align-items-center justify-content-center flex-column gap-2 text-center py-4 mb-3">
                                <i class="bi bi-cloud-upload display-4 text-muted"></i>
                                <p class="text-muted mb-0 fw-semibold">Clique para selecionar ou arraste arquivos aqui.</p>
                                <small class="text-muted">At? 4 fotos, 2MB cada.</small>
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
                    <div id="osBudgetSummaryPanel">
                        <?= view('os/partials/orcamento_editor_panel', [
                            'os' => $os,
                            'orcamentoVinculado' => $orcamentoVinculado ?? null,
                            'orcamentoItensResumo' => $orcamentoItensResumo ?? [],
                            'orcamentoStatusLabels' => $orcamentoStatusLabels ?? [],
                            'orcamentoTipoLabels' => $orcamentoTipoLabels ?? [],
                        ]) ?>
                    </div>
                    <div class="row g-3 mb-4 d-none">
                        <div class="col-12">
                            <div class="card" style="background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.1); border-radius: 10px;">
                                <div class="card-header py-2" style="background: transparent; border-bottom: 1px solid rgba(255,255,255,0.1);">
                                    <strong><i class="bi bi-box-seam me-2 text-primary"></i>Peças e Serviços</strong>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted small mb-2">Adicione peças e serviços na tela de visualização da OS.</p>
                                    <a href="<?= base_url('os/visualizar/' . $os['id']) ?><?= $embedQuery ?>" class="btn btn-sm btn-outline-info">Abrir OS e lancar itens</a>
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
                    <?php endif; ?>
                    </div>

                    <div class="os-form-actions" id="osFormActions">
                        <button type="submit" form="formOs" class="btn btn-glow" id="btnSubmitOs" data-loading-text="<?= esc($isEdit ? 'Atualizando OS...' : 'Abrindo OS...') ?>">
                            <i class="bi bi-check-lg me-1"></i><?= $isEdit ? 'Atualizar' : 'Abrir OS' ?>
                        </button>
                        <?php if (!$isEmbedded): ?>
                        <a href="<?= base_url('os') ?>" class="btn btn-outline-secondary">Cancelar</a>
                        <?php endif; ?>
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

<?php if ($isEdit): ?>
<div class="modal fade dashboard-os-modal" id="osBudgetEditorModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="osBudgetEditorModalTitle">Orcamento da OS</h5>
                <button type="button" class="btn-close ms-auto" id="osBudgetEditorModalCloseBtn" aria-label="Fechar"></button>
            </div>
            <div class="modal-body p-0 position-relative">
                <div class="dashboard-os-modal-loading" id="osBudgetEditorModalLoading">
                    <div class="spinner-border text-primary" role="status" aria-hidden="true"></div>
                    <span>Carregando orcamento...</span>
                </div>
                <iframe id="osBudgetEditorModalFrame" title="Orcamento da OS" class="dashboard-os-modal-frame" src="about:blank"></iframe>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ===== MODAL: CADASTRAR NOVO CLIENTE ===== -->
<div class="modal fade" id="modalNovoCliente" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content glass-card">
            <div class="modal-header border-bottom">
                <h5 class="modal-title">
                    <i class="bi bi-person-plus text-warning me-2"></i>Cadastro Rápido de Cliente
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <form id="formNovoClienteAjax">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" id="modalNovoClienteId">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Nome / Razão Social *</label>
                            <input type="text" name="nome_razao" class="form-control" data-auto-title-case="person-name" required>
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
                                    <label class="form-label mb-1 small fw-bold d-flex align-items-center justify-content-between gap-2">
                                        <span>Marca *</span>
                                        <button class="btn btn-success btn-sm ds-inline-add-btn" type="button" id="btnNovaMarcaOS">
                                            <i class="bi bi-plus-lg"></i><span>Adicionar</span>
                                        </button>
                                    </label>
                                    <select name="marca_id" id="novoEquipMarca" class="form-select select2-modal" required>
                                        <option value="">Marca...</option>
                                        <?php foreach ($marcas as $m): ?>
                                        <option value="<?= $m['id'] ?>"><?= esc($m['nome']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 text-start mt-2">
                                    <label class="form-label mb-1 small fw-bold d-flex align-items-center justify-content-between gap-2">
                                        <span>Modelo *</span>
                                        <button class="btn btn-success btn-sm ds-inline-add-btn" type="button" id="btnNovoModeloOS">
                                            <i class="bi bi-plus-lg"></i><span>Adicionar</span>
                                        </button>
                                    </label>
                                    <select name="modelo_id" id="novoEquipModelo" class="form-select" required>
                                        <option value="">Modelo...</option>
                                    </select>
                                    <input type="hidden" name="modelo_nome_ext" id="novoEquipModeloNomeExt">
                                </div>
                                <div class="col-md-6 text-start mt-2">
                                    <label class="form-label mb-1 small fw-bold">Nº de Série</label>
                                    <input type="text" name="numero_serie" class="form-control form-control-sm" placeholder="IMEI ou Série">
                                </div>
                                <div class="col-12 text-start mt-2">
                                    <div class="ds-password-field" id="novoEquipSenhaBoxOS">
                                        <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">
                                            <label class="form-label mb-0 small fw-bold" for="inputSenhaAcessoOSText">Senha de Acesso</label>
                                            <div class="btn-group btn-group-sm ds-password-mode-switch" role="group">
                                                <button type="button" class="btn btn-outline-secondary active" data-password-mode="desenho">DESENHO</button>
                                                <button type="button" class="btn btn-outline-secondary" data-password-mode="texto">TEXTO</button>
                                            </div>
                                        </div>
                                        <input type="hidden" name="senha_acesso" id="inputSenhaAcessoOS" data-password-hidden>
                                        <input type="hidden" name="senha_tipo" value="desenho" data-password-mode-input>
                                        <input type="hidden" name="senha_desenho" data-password-pattern-input>
                                        <div class="ds-pattern-password__pattern" data-password-pattern-wrap>
                                            <div class="ds-pattern-password__toolbar">
                                                <span class="ds-pattern-password__hint">Passe o mouse pelos 9 pontos para montar o desenho.</span>
                                                <button type="button" class="btn btn-outline-secondary btn-sm" data-password-clear>Limpar desenho</button>
                                            </div>
                                            <div class="ds-pattern-password__grid" data-pattern-grid>
                                                <?php for ($patternIndex = 1; $patternIndex <= 9; $patternIndex++): ?>
                                                <button type="button" class="ds-pattern-node" data-pattern-node="<?= $patternIndex ?>" aria-label="Ponto <?= $patternIndex ?>"></button>
                                                <?php endfor; ?>
                                            </div>
                                            <div class="ds-pattern-password__preview" data-password-preview>Nenhum desenho definido.</div>
                                        </div>
                                        <div class="ds-pattern-password__text" data-password-text-wrap hidden>
                                            <input type="text" id="inputSenhaAcessoOSText" class="form-control form-control-sm" placeholder="Digite a senha do aparelho" data-password-text-input>
                                        </div>
                                    </div>
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
                                <input type="hidden" name="cor_hex" id="corHexRealOS" value="">
                                <input type="hidden" name="cor_rgb" id="corRgbRealOS" value="">
                                <input type="hidden" name="cor" id="corNomeRealOS" value="">

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
                                            <input type="text" id="corNomeInputOS" class="form-control form-control-sm" placeholder="Nome" value="">
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
                                <input type="file" name="fotos[]" id="novoEquipFoto" class="d-none" accept="image/jpeg,image/png,image/webp" multiple>
                            </div>

                            <div id="novoEquipFotoPreview" class="mt-2" style="display:none;">
                                <div id="novoEquipFotosNovasList" class="d-flex flex-wrap gap-2 justify-content-center"></div>
                                <div class="mt-2 small text-muted">A foto de perfil ajuda na identificação visual rápida.</div>
                            </div>
                            
                            <div id="fotoVaziaOS" class="py-4 text-muted opacity-50">
                                <i class="bi bi-image fs-1 d-block"></i>
                                <span class="small font-monospace">Nenhuma imagem selecionada</span>
                            </div>

                            <div id="modalEquipFotosExistentesWrap" class="mt-3 d-none">
                                <div class="small text-muted mb-2">Fotos já cadastradas neste equipamento</div>
                                <div id="modalEquipFotosExistentes" class="d-flex flex-wrap gap-2 justify-content-center"></div>
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
<div class="modal fade" id="modalCamera" tabindex="-1" style="z-index: 2250;">
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
<div class="modal fade" id="modalCropEquip" tabindex="-1" style="z-index: 2350;">
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

<!-- ===== MODAL: CHECKLIST DE ENTRADA ===== -->
<div class="modal fade" id="modalChecklistEntrada" tabindex="-1" aria-hidden="true" style="z-index: 2150;">
    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content glass-card shadow-lg">
            <div class="modal-header border-bottom">
                <h5 class="modal-title font-title">
                    <i class="bi bi-ui-checks-grid text-primary me-2"></i>Checklist de Entrada
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div id="checklistEntradaModalAlert" class="alert alert-warning d-none mb-3"></div>
                <div id="checklistEntradaModalMeta" class="small text-muted mb-3"></div>
                <div id="checklistEntradaModalList" class="d-grid gap-3"></div>
            </div>
            <div class="modal-footer border-top d-flex justify-content-between align-items-center">
                <small id="checklistEntradaModalResumo" class="text-muted"></small>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fechar</button>
                    <button type="button" class="btn btn-glow" id="btnChecklistEntradaSalvar">
                        <i class="bi bi-check2-circle me-1"></i>Salvar checklist
                    </button>
                </div>
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
<script>
const BASE_URL = document.querySelector('meta[name="base-url"]').content;
const isEdit   = <?= $isEdit ? 'true' : 'false' ?>;
<?php if ($isEdit && !empty($defeitosSelected)): ?>
var defeitosSelecionados = <?= json_encode(array_column($defeitosSelected, 'defeito_id')) ?>;
<?php else: ?>
var defeitosSelecionados = [];
<?php endif; ?>
const existingFotosCount = <?= (int)(count($fotos_entrada ?? [])) ?>;
const checklistEntradaServer = <?= json_encode($checklistEntrada ?? null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
const estadoFisicoEntriesServer = [];
let pendingEquipId = null;
let pendingDefeitos = null;
const DRAFT_KEY = 'osDraft_v1';
const DRAFT_TTL_MS = 1000 * 60 * 60 * 24 * 7;
let draftSaveTimer = null;

const statusMeta = <?= json_encode($statusFlat, JSON_UNESCAPED_UNICODE) ?> || {};
const statusLabels = Object.keys(statusMeta).reduce((acc, key) => {
    acc[key] = statusMeta[key]?.nome || key;
    return acc;
}, {});

const statusBadgeClassMap = Object.keys(statusMeta).reduce((acc, key) => {
    const raw = String(statusMeta[key]?.cor || 'secondary').toLowerCase();
    const normalized = ({
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
    acc[key] = 'text-bg-' + normalized;
    return acc;
}, {});

const prioridadeLabels = {
    baixa: 'Baixa',
    normal: 'Normal',
    alta: 'Alta',
    urgente: 'Urgente'
};

const osIdAtual = <?= (int) ($os['id'] ?? 0) ?>;
if (typeof window.elevateLatestBackdrop !== 'function') {
    window.elevateLatestBackdrop = function(zIndex) {
        const backdrops = Array.from(document.querySelectorAll('.modal-backdrop'));
        const lastBackdrop = backdrops[backdrops.length - 1] || null;
        if (lastBackdrop && Number.isFinite(Number(zIndex))) {
            lastBackdrop.style.zIndex = String(zIndex);
        }
    };
}
var elevateLatestBackdrop = window.elevateLatestBackdrop;
const checklistMetaEndpoint = `${BASE_URL}os/checklist-meta`;
const checklistEntradaBtn = document.getElementById('btnChecklistEntrada');
const checklistEntradaBadge = document.getElementById('checklistEntradaBadge');
const checklistEntradaStatusCard = document.getElementById('checklistEntradaStatusCard');
const checklistEntradaStatusIcon = document.getElementById('checklistEntradaStatusIcon');
const checklistEntradaStatusTitle = document.getElementById('checklistEntradaStatusTitle');
const checklistEntradaInlineInfo = document.getElementById('checklistEntradaInlineInfo');
const checklistEntradaDataInput = document.getElementById('checklistEntradaDataInput');
const checklistEntradaFilesInputs = document.getElementById('checklistEntradaFilesInputs');
const checklistEntradaPhotoInput = document.getElementById('checklistEntradaPhotoInput');
const checklistEntradaModalEl = document.getElementById('modalChecklistEntrada');
const checklistEntradaModalList = document.getElementById('checklistEntradaModalList');
const checklistEntradaModalAlert = document.getElementById('checklistEntradaModalAlert');
const checklistEntradaModalMeta = document.getElementById('checklistEntradaModalMeta');
const checklistEntradaModalResumo = document.getElementById('checklistEntradaModalResumo');
const btnChecklistEntradaSalvar = document.getElementById('btnChecklistEntradaSalvar');
let checklistEntradaModal = null;
const checklistEntradaStatusOptions = ['ok', 'discrepancia', 'nao_verificado'];
let checklistEntradaCommitted = normalizeChecklistEntradaPayload(checklistEntradaServer);
let checklistEntradaDraft = cloneChecklistEntradaState(checklistEntradaCommitted);
let checklistEntradaCommittedFiles = {};
let checklistEntradaDraftFiles = {};
let checklistEntradaCropQueue = [];
let checklistEntradaCropItemId = null;
let checklistMetaLoadToken = 0;
let pendingChecklistEntradaDraftPayload = null;
let pendingChecklistEntradaDraftEquipId = '';
const procedimentosExecutadosInput = document.getElementById('procedimentosExecutadosInput');
const procedimentosExecutadosLista = document.getElementById('procedimentosExecutadosLista');
const procedimentoExecutadoTextoInput = document.getElementById('procedimentoExecutadoTextoInput');
const btnInserirProcedimentoExecutado = document.getElementById('btnInserirProcedimentoExecutado');

function toInt(value) {
    const parsed = Number.parseInt(String(value ?? ''), 10);
    return Number.isFinite(parsed) ? parsed : 0;
}

function checklistBadgeVariant(variant) {
    return ({
        success: 'text-bg-success',
        warning: 'text-bg-warning',
        danger: 'text-bg-danger',
        info: 'text-bg-info',
        primary: 'text-bg-primary'
    })[String(variant || '').toLowerCase()] || 'text-bg-secondary';
}

function cloneChecklistEntradaState(state) {
    if (!state || typeof state !== 'object') {
        return normalizeChecklistEntradaPayload(null);
    }
    return {
        possuiModelo: Boolean(state.possuiModelo),
        tipoCodigo: String(state.tipoCodigo || 'entrada'),
        tipoNome: String(state.tipoNome || 'Checklist de Entrada'),
        modeloNome: String(state.modeloNome || ''),
        tipoEquipamentoNome: String(state.tipoEquipamentoNome || ''),
        resumo: {
            preenchido: Boolean(state.resumo?.preenchido),
            total_discrepancias: Number(state.resumo?.total_discrepancias || 0),
            label: String(state.resumo?.label || 'Checklist nao preenchido'),
            variant: String(state.resumo?.variant || 'secondary')
        },
        itens: Array.isArray(state.itens)
            ? state.itens.map((item) => ({
                id: toInt(item.id),
                descricao: String(item.descricao || ''),
                ordem: toInt(item.ordem),
                status: checklistEntradaStatusOptions.includes(String(item.status || ''))
                    ? String(item.status)
                    : 'nao_verificado',
                observacao: String(item.observacao || ''),
                retainedPhotoIds: Array.isArray(item.retainedPhotoIds)
                    ? item.retainedPhotoIds.map((photoId) => toInt(photoId)).filter((photoId) => photoId > 0)
                    : [],
                deletedPhotoIds: Array.isArray(item.deletedPhotoIds)
                    ? item.deletedPhotoIds.map((photoId) => toInt(photoId)).filter((photoId) => photoId > 0)
                    : [],
                existingPhotos: Array.isArray(item.existingPhotos)
                    ? item.existingPhotos.map((photo) => ({
                        id: toInt(photo.id),
                        url: String(photo.url || ''),
                        nome: String(photo.nome || '')
                    }))
                    : []
            }))
            : []
    };
}

function cloneChecklistFilesMap(source) {
    const next = {};
    Object.entries(source || {}).forEach(([itemId, dt]) => {
        if (!dt || !dt.files || !dt.files.length) {
            return;
        }
        const cloned = new DataTransfer();
        Array.from(dt.files).forEach((file) => cloned.items.add(file));
        next[itemId] = cloned;
    });
    return next;
}

function normalizeChecklistEntradaPayload(payload, tipoEquipamentoNomeFallback = '') {
    const normalized = {
        possuiModelo: false,
        tipoCodigo: 'entrada',
        tipoNome: 'Checklist de Entrada',
        modeloNome: '',
        tipoEquipamentoNome: String(tipoEquipamentoNomeFallback || ''),
        resumo: {
            preenchido: false,
            total_discrepancias: 0,
            label: 'Checklist nao preenchido',
            variant: 'secondary'
        },
        itens: []
    };

    if (!payload || typeof payload !== 'object') {
        return normalized;
    }

    normalized.tipoCodigo = String(payload.tipo?.codigo || payload.tipoCodigo || 'entrada');
    normalized.tipoNome = String(payload.tipo?.nome || payload.tipoNome || 'Checklist de Entrada');
    normalized.modeloNome = String(payload.modelo?.nome || payload.modeloNome || '');
    normalized.tipoEquipamentoNome = String(
        payload.tipo_equipamento_nome
        || payload.tipoEquipamentoNome
        || tipoEquipamentoNomeFallback
        || ''
    );
    normalized.possuiModelo = Boolean(payload.possui_modelo ?? payload.possuiModelo ?? normalized.modeloNome !== '');

    if (Array.isArray(payload.itens)) {
        normalized.itens = payload.itens.map((item) => {
            const existingPhotos = Array.isArray(item.fotos)
                ? item.fotos.map((foto) => ({
                    id: toInt(foto.id),
                    url: String(foto.url || ''),
                    nome: String(foto.arquivo_original || foto.arquivo || '')
                })).filter((foto) => foto.url !== '')
                : [];
            const retainedPhotoIds = existingPhotos.map((foto) => foto.id).filter((photoId) => photoId > 0);
            return {
                id: toInt(item.id),
                descricao: String(item.descricao || ''),
                ordem: toInt(item.ordem),
                status: checklistEntradaStatusOptions.includes(String(item.status || ''))
                    ? String(item.status)
                    : 'nao_verificado',
                observacao: String(item.observacao || ''),
                retainedPhotoIds,
                deletedPhotoIds: [],
                existingPhotos
            };
        });
    }

    if (payload.resumo && typeof payload.resumo === 'object') {
        normalized.resumo = {
            preenchido: Boolean(payload.resumo.preenchido),
            total_discrepancias: Number(payload.resumo.total_discrepancias || 0),
            label: String(payload.resumo.label || 'Checklist nao preenchido'),
            variant: String(payload.resumo.variant || 'secondary')
        };
    } else {
        normalized.resumo = computeChecklistResumo(normalized);
    }

    return normalized;
}

function computeChecklistResumo(state) {
    if (!state?.possuiModelo) {
        return {
            preenchido: false,
            total_discrepancias: 0,
            label: 'Sem modelo',
            variant: 'secondary'
        };
    }

    const itens = Array.isArray(state.itens) ? state.itens : [];
    const totalDiscrepancias = itens.filter((item) => item.status === 'discrepancia').length;
    const todosPreenchidos = itens.length > 0 && itens.every((item) => item.status !== 'nao_verificado');

    if (!todosPreenchidos) {
        return {
            preenchido: false,
            total_discrepancias: totalDiscrepancias,
            label: 'Pendente',
            variant: 'warning'
        };
    }

    if (totalDiscrepancias <= 0) {
        return {
            preenchido: true,
            total_discrepancias: 0,
            label: 'Tudo OK',
            variant: 'success'
        };
    }

    return {
        preenchido: true,
        total_discrepancias: totalDiscrepancias,
        label: totalDiscrepancias === 1 ? '1 discrepancia' : `${totalDiscrepancias} discrepancias`,
        variant: 'warning'
    };
}

function getSelectedEquipamentoTipoNome(optionEl = null) {
    const opt = optionEl || document.querySelector('#equipamentoSelect option:checked');
    return String(opt?.dataset?.tipo_nome || '').trim();
}

function updateChecklistInlineSummary() {
    checklistEntradaCommitted.resumo = computeChecklistResumo(checklistEntradaCommitted);
    const equipamentoSelecionado = String(document.getElementById('equipamentoSelect')?.value || '').trim();
    const hasEquipamentoSelecionado = equipamentoSelecionado !== '';
    const itens = Array.isArray(checklistEntradaCommitted?.itens) ? checklistEntradaCommitted.itens : [];
    const totalItens = itens.length;
    const totalPendentes = itens.filter((item) => item.status === 'nao_verificado').length;
    const totalDiscrepancias = Number(checklistEntradaCommitted?.resumo?.total_discrepancias || 0);

    let statusVariantClass = 'os-checklist-status-pending';
    let statusIcon = 'bi-hourglass-split';
    let statusTitle = 'Checklist pendente de preenchimento';
    let statusInfo = totalItens > 0
        ? `Faltam ${totalPendentes} de ${totalItens} item(ns) para concluir o checklist.`
        : 'Abra o checklist e marque todos os itens para concluir a entrada.';

    if (!hasEquipamentoSelecionado) {
        statusVariantClass = 'os-checklist-status-unavailable';
        statusIcon = 'bi-phone';
        statusTitle = 'Selecione um equipamento para liberar o checklist';
        statusInfo = 'Depois de selecionar o equipamento, o modelo de checklist sera carregado automaticamente.';
    } else if (!checklistEntradaCommitted?.possuiModelo) {
        statusVariantClass = 'os-checklist-status-unavailable';
        statusIcon = 'bi-slash-circle';
        statusTitle = 'Checklist indisponivel para este tipo de equipamento';
        statusInfo = 'Nao ha modelo configurado para este equipamento em Gestao de Conhecimento.';
    } else if (checklistEntradaCommitted.resumo.preenchido && totalDiscrepancias <= 0) {
        statusVariantClass = 'os-checklist-status-success';
        statusIcon = 'bi-check-circle-fill';
        statusTitle = 'Checklist concluido: tudo OK';
        statusInfo = 'Todos os itens foram verificados sem discrepancias.';
    } else if (checklistEntradaCommitted.resumo.preenchido && totalDiscrepancias > 0) {
        statusVariantClass = 'os-checklist-status-warning';
        statusIcon = 'bi-exclamation-triangle-fill';
        statusTitle = 'Checklist concluido com discrepancias';
        statusInfo = totalDiscrepancias === 1
            ? '1 item foi marcado com discrepancia. Reabra o checklist para revisar.'
            : `${totalDiscrepancias} itens foram marcados com discrepancia. Reabra o checklist para revisar.`;
    }

    if (checklistEntradaBadge) {
        if (!hasEquipamentoSelecionado) {
            checklistEntradaBadge.textContent = 'Aguardando equipamento';
            checklistEntradaBadge.className = `badge ${checklistBadgeVariant('secondary')}`;
        } else {
            checklistEntradaBadge.textContent = checklistEntradaCommitted.resumo.label;
            checklistEntradaBadge.className = `badge ${checklistBadgeVariant(checklistEntradaCommitted.resumo.variant)}`;
        }
    }
    if (checklistEntradaStatusCard) {
        checklistEntradaStatusCard.classList.remove(
            'os-checklist-status-pending',
            'os-checklist-status-success',
            'os-checklist-status-warning',
            'os-checklist-status-unavailable'
        );
        checklistEntradaStatusCard.classList.add(statusVariantClass);
    }
    if (checklistEntradaStatusIcon) {
        checklistEntradaStatusIcon.innerHTML = `<i class="bi ${statusIcon}"></i>`;
    }
    if (checklistEntradaStatusTitle) {
        checklistEntradaStatusTitle.textContent = statusTitle;
    }
    if (checklistEntradaInlineInfo) {
        checklistEntradaInlineInfo.textContent = statusInfo;
    }
}

function buildChecklistPayloadForSubmit(state) {
    if (!state?.possuiModelo) {
        return null;
    }

    return {
        tipo_equipamento_nome: state.tipoEquipamentoNome || getSelectedEquipamentoTipoNome(),
        itens: (state.itens || []).map((item) => ({
            item_id: item.id,
            status: checklistEntradaStatusOptions.includes(String(item.status || ''))
                ? String(item.status)
                : 'nao_verificado',
            observacao: String(item.observacao || '').trim(),
            retained_photo_ids: Array.isArray(item.retainedPhotoIds) ? item.retainedPhotoIds : [],
            deleted_photo_ids: Array.isArray(item.deletedPhotoIds) ? item.deletedPhotoIds : []
        }))
    };
}

function buildLegacyEstadoFisicoFromChecklist(state) {
    if (!state?.possuiModelo) {
        return '';
    }
    const discrepancias = (state.itens || []).filter((item) => item.status === 'discrepancia');
    if (!discrepancias.length) {
        return 'Sem avarias aparentes';
    }
    return discrepancias
        .map((item) => item.descricao)
        .filter((desc) => String(desc || '').trim() !== '')
        .join('\n');
}

function syncChecklistEntradaFileInputs() {
    if (!checklistEntradaFilesInputs) {
        return;
    }
    checklistEntradaFilesInputs.innerHTML = '';

    Object.entries(checklistEntradaCommittedFiles).forEach(([itemId, dt]) => {
        if (!dt || !dt.files || !dt.files.length) {
            return;
        }
        const input = document.createElement('input');
        input.type = 'file';
        input.multiple = true;
        input.className = 'd-none';
        input.name = `fotos_checklist_entrada[${itemId}][]`;
        input.files = dt.files;
        checklistEntradaFilesInputs.appendChild(input);
    });
}

function syncChecklistEntradaInputs() {
    const payload = buildChecklistPayloadForSubmit(checklistEntradaCommitted);
    if (checklistEntradaDataInput) {
        checklistEntradaDataInput.value = payload ? JSON.stringify(payload) : '';
    }

    const estadoFisicoInput = document.getElementById('estadoFisicoInput');
    if (estadoFisicoInput) {
        estadoFisicoInput.value = buildLegacyEstadoFisicoFromChecklist(checklistEntradaCommitted);
    }

    const estadoFisicoDataInput = document.getElementById('estadoFisicoDataInput');
    if (estadoFisicoDataInput) {
        const legacyLines = String(estadoFisicoInput?.value || '').trim();
        estadoFisicoDataInput.value = legacyLines
            ? JSON.stringify(legacyLines.split(/\r?\n/).map((text, index) => ({
                id: `chk_${index + 1}`,
                text,
                key: 'checklist'
            })))
            : '';
    }

    syncChecklistEntradaFileInputs();
}

function parseChecklistDraftPayload(rawValue) {
    if (!rawValue) {
        return null;
    }

    if (typeof rawValue === 'object' && Array.isArray(rawValue.itens)) {
        return rawValue;
    }

    const raw = String(rawValue || '').trim();
    if (!raw) {
        return null;
    }

    try {
        const parsed = JSON.parse(raw);
        return parsed && Array.isArray(parsed.itens) ? parsed : null;
    } catch (error) {
        console.error('[ChecklistEntrada] falha ao interpretar rascunho salvo', error);
        return null;
    }
}

function applyChecklistDraftPayloadToCommitted(payload) {
    if (!payload || !Array.isArray(payload.itens) || !checklistEntradaCommitted?.possuiModelo) {
        return false;
    }

    const payloadMap = new Map();
    payload.itens.forEach((item) => {
        const itemId = toInt(item?.item_id ?? item?.id);
        if (itemId > 0) {
            payloadMap.set(itemId, item);
        }
    });

    if (!payloadMap.size || !Array.isArray(checklistEntradaCommitted.itens) || !checklistEntradaCommitted.itens.length) {
        return false;
    }

    checklistEntradaCommitted.itens = checklistEntradaCommitted.itens.map((item) => {
        const incoming = payloadMap.get(toInt(item.id));
        if (!incoming) {
            return item;
        }

        const status = checklistEntradaStatusOptions.includes(String(incoming.status || ''))
            ? String(incoming.status)
            : 'nao_verificado';
        const observacao = String(incoming.observacao || '');
        const existingPhotos = Array.isArray(item.existingPhotos) ? item.existingPhotos : [];

        return {
            ...item,
            status,
            observacao: status === 'ok' ? '' : observacao,
            retainedPhotoIds: existingPhotos.map((photo) => toInt(photo.id)).filter((photoId) => photoId > 0),
            deletedPhotoIds: []
        };
    });

    checklistEntradaCommittedFiles = {};
    checklistEntradaCommitted.resumo = computeChecklistResumo(checklistEntradaCommitted);
    return true;
}

function getChecklistResumoState() {
    const resumo = checklistEntradaCommitted?.resumo || computeChecklistResumo(checklistEntradaCommitted || {});
    return {
        ok: !checklistEntradaCommitted?.possuiModelo || Boolean(resumo.preenchido),
        label: String(resumo.label || 'Checklist nao preenchido')
    };
}

function isChecklistEntradaRequiredAndMissing() {
    if (!checklistEntradaCommitted?.possuiModelo) {
        return false;
    }
    const resumo = checklistEntradaCommitted?.resumo || computeChecklistResumo(checklistEntradaCommitted);
    return !Boolean(resumo.preenchido);
}

function getTotalChecklistEntradaFotos() {
    const existingCount = (checklistEntradaCommitted?.itens || []).reduce((total, item) => {
        const retained = Array.isArray(item.retainedPhotoIds) ? item.retainedPhotoIds : [];
        return total + retained.length;
    }, 0);
    const newCount = Object.values(checklistEntradaCommittedFiles).reduce((total, dt) => total + (dt?.files?.length || 0), 0);
    return existingCount + newCount;
}

function ensureChecklistDraftItem(itemId) {
    if (!checklistEntradaDraft?.itens?.length) {
        return null;
    }
    return checklistEntradaDraft.itens.find((item) => toInt(item.id) === toInt(itemId)) || null;
}

function ensureChecklistDraftTransfer(itemId) {
    const key = String(itemId);
    if (!checklistEntradaDraftFiles[key]) {
        checklistEntradaDraftFiles[key] = new DataTransfer();
    }
    return checklistEntradaDraftFiles[key];
}

function renderChecklistItemPhotos(item, container) {
    if (!container) {
        return;
    }
    container.innerHTML = '';

    const retainedSet = new Set((item.retainedPhotoIds || []).map((photoId) => toInt(photoId)));
    (item.existingPhotos || []).forEach((photo) => {
        if (!retainedSet.has(toInt(photo.id))) {
            return;
        }
        const thumb = document.createElement('div');
        thumb.className = 'checklist-photo-thumb';
        thumb.innerHTML = `
            <a href="javascript:void(0)" class="d-block w-100 h-100 image-preview" data-bs-toggle="modal" data-bs-target="#imageModal" data-img-src="${escapeEquipamentoHtml(photo.url)}">
                <img src="${escapeEquipamentoHtml(photo.url)}" alt="">
            </a>
            <button type="button" class="btn btn-sm btn-outline-light checklist-photo-remove" data-checklist-remove-existing="${toInt(photo.id)}" title="Remover foto">
                <i class="bi bi-x"></i>
            </button>
        `;
        container.appendChild(thumb);
    });

    const transfer = checklistEntradaDraftFiles[String(item.id)];
    if (!transfer || !transfer.files || !transfer.files.length) {
        return;
    }

    Array.from(transfer.files).forEach((file, index) => {
        const objectUrl = URL.createObjectURL(file);
        const thumb = document.createElement('div');
        thumb.className = 'checklist-photo-thumb';
        thumb.innerHTML = `
            <a href="javascript:void(0)" class="d-block w-100 h-100 image-preview" data-bs-toggle="modal" data-bs-target="#imageModal" data-img-src="${escapeEquipamentoHtml(objectUrl)}">
                <img src="${escapeEquipamentoHtml(objectUrl)}" alt="">
            </a>
            <button type="button" class="btn btn-sm btn-outline-light checklist-photo-remove" data-checklist-remove-new="${index}" title="Remover foto">
                <i class="bi bi-x"></i>
            </button>
        `;
        container.appendChild(thumb);
    });
}

function renderChecklistEntradaModal() {
    if (!checklistEntradaModalList || !checklistEntradaModalAlert || !checklistEntradaModalMeta) {
        return;
    }

    checklistEntradaModalList.innerHTML = '';
    checklistEntradaModalAlert.classList.add('d-none');
    checklistEntradaModalAlert.textContent = '';

    if (!checklistEntradaDraft?.possuiModelo) {
        checklistEntradaModalAlert.classList.remove('d-none');
        checklistEntradaModalAlert.classList.add('alert-warning');
        checklistEntradaModalAlert.textContent = 'Nao existe checklist cadastrado para este tipo de equipamento.';
        checklistEntradaModalMeta.textContent = '';
        if (checklistEntradaModalResumo) {
            checklistEntradaModalResumo.textContent = 'Sem modelo configurado';
        }
        return;
    }

    const tipoNome = checklistEntradaDraft.tipoEquipamentoNome || getSelectedEquipamentoTipoNome() || 'Equipamento';
    checklistEntradaModalMeta.textContent = `Modelo: ${checklistEntradaDraft.modeloNome || 'Checklist de entrada'} | Tipo: ${tipoNome}`;

    const itensOrdenados = [...(checklistEntradaDraft.itens || [])].sort((a, b) => Number(a.ordem || 0) - Number(b.ordem || 0));
    itensOrdenados.forEach((item) => {
        const card = document.createElement('article');
        card.className = 'checklist-item-card';
        card.dataset.checklistItem = String(item.id);

        const status = checklistEntradaStatusOptions.includes(String(item.status || '')) ? String(item.status) : 'nao_verificado';
        const precisaObs = status === 'discrepancia';
        const fotosCount = (item.retainedPhotoIds?.length || 0) + (checklistEntradaDraftFiles[String(item.id)]?.files?.length || 0);

        card.innerHTML = `
            <div class="checklist-item-head">
                <div>
                    <p class="checklist-item-title">${escapeEquipamentoHtml(item.descricao || 'Item')}</p>
                    <small class="text-muted">Fotos: ${fotosCount}</small>
                </div>
                <div class="checklist-status-actions">
                    <button type="button" class="btn btn-sm checklist-status-btn ${status === 'ok' ? 'btn-success' : 'btn-outline-success'}" data-checklist-status="ok">OK</button>
                    <button type="button" class="btn btn-sm checklist-status-btn ${status === 'discrepancia' ? 'btn-warning' : 'btn-outline-warning'}" data-checklist-status="discrepancia">Discrepancia</button>
                    <button type="button" class="btn btn-sm checklist-status-btn ${status === 'nao_verificado' ? 'btn-secondary' : 'btn-outline-secondary'}" data-checklist-status="nao_verificado">Nao verificado</button>
                </div>
            </div>
            <div class="mt-2 ${precisaObs ? '' : 'd-none'}" data-checklist-observacao-wrap>
                <label class="form-label form-label-sm mb-1">Observacao da discrepancia</label>
                <textarea class="form-control form-control-sm" rows="2" data-checklist-observacao placeholder="Descreva a divergencia encontrada...">${escapeEquipamentoHtml(item.observacao || '')}</textarea>
            </div>
            <div class="d-flex flex-wrap gap-2 mt-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-checklist-action="galeria">
                    <i class="bi bi-image me-1"></i>Galeria
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" data-checklist-action="camera">
                    <i class="bi bi-camera me-1"></i>Camera
                </button>
            </div>
            <div class="d-flex flex-wrap gap-2 mt-2" data-checklist-photos></div>
        `;

        checklistEntradaModalList.appendChild(card);
        const photosContainer = card.querySelector('[data-checklist-photos]');
        renderChecklistItemPhotos(item, photosContainer);
    });

    const resumo = computeChecklistResumo(checklistEntradaDraft);
    if (checklistEntradaModalResumo) {
        checklistEntradaModalResumo.textContent = resumo.label;
    }
}

function openChecklistEntradaModal() {
    const equipamentoSelecionado = document.getElementById('equipamentoSelect')?.value || '';
    if (!equipamentoSelecionado) {
        showWarningDialog('Selecione o equipamento antes de preencher o checklist.', 'Checklist indisponivel');
        return;
    }

    checklistEntradaDraft = cloneChecklistEntradaState(checklistEntradaCommitted);
    checklistEntradaDraftFiles = cloneChecklistFilesMap(checklistEntradaCommittedFiles);
    renderChecklistEntradaModal();
    closeImageModalIfOpen();
    hoistModalToBody(checklistEntradaModalEl, CHECKLIST_MODAL_Z_INDEX);
    cleanupStuckModalArtifacts();
    resetModalNodeState(checklistEntradaModalEl);

    try {
        bootstrap.Modal.getInstance(checklistEntradaModalEl)?.dispose();
    } catch (error) {
        console.error('[ChecklistEntrada] falha ao descartar instancia anterior do modal', error);
    }

    checklistEntradaModal = checklistEntradaModalEl
        ? new bootstrap.Modal(checklistEntradaModalEl, { backdrop: true, focus: true, keyboard: true })
        : null;
    checklistEntradaModal?.show();
}

function saveChecklistEntradaModal() {
    if (!checklistEntradaDraft?.possuiModelo) {
        checklistEntradaModal?.hide();
        return;
    }

    const resumo = computeChecklistResumo(checklistEntradaDraft);
    if (!resumo.preenchido) {
        showWarningDialog('Marque todos os itens do checklist antes de salvar.', 'Checklist incompleto');
        return;
    }

    checklistEntradaCommitted = cloneChecklistEntradaState(checklistEntradaDraft);
    checklistEntradaCommitted.resumo = resumo;
    checklistEntradaCommittedFiles = cloneChecklistFilesMap(checklistEntradaDraftFiles);
    updateChecklistInlineSummary();
    syncChecklistEntradaInputs();
    updateResumo();
    scheduleDraftSave();
    checklistEntradaModal?.hide();
}

async function loadChecklistEntradaMeta(equipamentoId, optionEl = null) {
    const equipId = toInt(equipamentoId);
    if (!equipId) {
        checklistEntradaCommitted = normalizeChecklistEntradaPayload(null, getSelectedEquipamentoTipoNome(optionEl));
        checklistEntradaCommittedFiles = {};
        updateChecklistInlineSummary();
        syncChecklistEntradaInputs();
        updateResumo();
        return;
    }

    const token = ++checklistMetaLoadToken;
    if (checklistEntradaBtn) {
        checklistEntradaBtn.disabled = true;
    }

    try {
        const params = new URLSearchParams();
        params.set('equipamento_id', String(equipId));
        if (osIdAtual > 0) {
            params.set('os_id', String(osIdAtual));
        }

        const response = await fetch(`${checklistMetaEndpoint}?${params.toString()}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const payload = await response.json();

        if (token !== checklistMetaLoadToken) {
            return;
        }

        if (!response.ok || !payload?.ok) {
            throw new Error(payload?.message || 'Nao foi possivel carregar o checklist.');
        }

        checklistEntradaCommitted = normalizeChecklistEntradaPayload(payload.data || null, getSelectedEquipamentoTipoNome(optionEl));
        checklistEntradaCommittedFiles = {};
        const shouldApplyPendingDraft = Boolean(
            pendingChecklistEntradaDraftPayload
            && (
                String(pendingChecklistEntradaDraftEquipId || '') === ''
                || String(pendingChecklistEntradaDraftEquipId || '') === String(equipId)
            )
        );
        if (shouldApplyPendingDraft && applyChecklistDraftPayloadToCommitted(pendingChecklistEntradaDraftPayload)) {
            pendingChecklistEntradaDraftPayload = null;
            pendingChecklistEntradaDraftEquipId = '';
        }
        updateChecklistInlineSummary();
        syncChecklistEntradaInputs();
        updateResumo();
    } catch (error) {
        console.error('[ChecklistEntrada] falha ao carregar metadados', error);
        checklistEntradaCommitted = normalizeChecklistEntradaPayload(null, getSelectedEquipamentoTipoNome(optionEl));
        checklistEntradaCommittedFiles = {};
        updateChecklistInlineSummary();
        syncChecklistEntradaInputs();
        updateResumo();
    } finally {
        if (checklistEntradaBtn) {
            checklistEntradaBtn.disabled = false;
        }
    }
}

function processNextChecklistEntradaCrop() {
    if (!checklistEntradaCropItemId) {
        return;
    }
    if (!checklistEntradaCropQueue.length) {
        hideModalSafe(modalCrop, '#modalCropEquip');
        return;
    }

    const nextFile = checklistEntradaCropQueue.shift();
    readFileAsDataUrl(nextFile)
        .then((source) => openCropper(source, { type: 'checklist_entrada' }))
        .catch((error) => {
            console.error('[ChecklistEntrada] falha no preparo da foto para crop', error);
            processNextChecklistEntradaCrop();
        });
}

if (checklistEntradaBtn) {
    checklistEntradaBtn.addEventListener('click', openChecklistEntradaModal);
}

btnChecklistEntradaSalvar?.addEventListener('click', saveChecklistEntradaModal);

checklistEntradaModalEl?.addEventListener('shown.bs.modal', () => {
    window.elevateLatestBackdrop(CHECKLIST_MODAL_Z_INDEX - 10);
    if (checklistEntradaModalEl) {
        checklistEntradaModalEl.style.zIndex = String(CHECKLIST_MODAL_Z_INDEX);
    }
});

checklistEntradaModalEl?.addEventListener('hidden.bs.modal', () => {
    checklistEntradaDraft = cloneChecklistEntradaState(checklistEntradaCommitted);
    checklistEntradaDraftFiles = cloneChecklistFilesMap(checklistEntradaCommittedFiles);
    scheduleModalCleanup();
});

checklistEntradaModalList?.addEventListener('click', (event) => {
    const target = event.target.closest('button');
    if (!target) {
        return;
    }

    const itemCard = target.closest('[data-checklist-item]');
    const itemId = itemCard?.dataset?.checklistItem || '';
    const item = ensureChecklistDraftItem(itemId);
    if (!item) {
        return;
    }

    const statusAction = target.dataset.checklistStatus;
    if (statusAction) {
        item.status = checklistEntradaStatusOptions.includes(statusAction) ? statusAction : 'nao_verificado';
        if (item.status !== 'discrepancia') {
            item.observacao = '';
        }
        renderChecklistEntradaModal();
        return;
    }

    const action = target.dataset.checklistAction;
    if (action === 'galeria') {
        if (checklistEntradaPhotoInput) {
            checklistEntradaPhotoInput.dataset.itemId = String(item.id);
            checklistEntradaPhotoInput.removeAttribute('capture');
            checklistEntradaPhotoInput.click();
        }
        return;
    }

    if (action === 'camera') {
        checklistEntradaCropItemId = String(item.id);
        checklistEntradaCropQueue = [];
        openCameraCapture({ type: 'checklist_entrada', entryId: String(item.id) });
        return;
    }

    if (target.dataset.checklistRemoveExisting) {
        const photoId = toInt(target.dataset.checklistRemoveExisting);
        item.retainedPhotoIds = (item.retainedPhotoIds || []).filter((id) => toInt(id) !== photoId);
        if (!item.deletedPhotoIds.includes(photoId)) {
            item.deletedPhotoIds.push(photoId);
        }
        renderChecklistEntradaModal();
        return;
    }

    if (target.dataset.checklistRemoveNew !== undefined) {
        const index = toInt(target.dataset.checklistRemoveNew);
        const transfer = checklistEntradaDraftFiles[String(item.id)];
        if (!transfer || !transfer.files || !transfer.files.length) {
            return;
        }
        const nextTransfer = new DataTransfer();
        Array.from(transfer.files).forEach((file, fileIndex) => {
            if (fileIndex !== index) {
                nextTransfer.items.add(file);
            }
        });
        checklistEntradaDraftFiles[String(item.id)] = nextTransfer;
        renderChecklistEntradaModal();
    }
});

checklistEntradaModalList?.addEventListener('input', (event) => {
    const textarea = event.target.closest('textarea[data-checklist-observacao]');
    if (!textarea) {
        return;
    }
    const itemCard = textarea.closest('[data-checklist-item]');
    const item = ensureChecklistDraftItem(itemCard?.dataset?.checklistItem || '');
    if (!item) {
        return;
    }
    item.observacao = textarea.value || '';
});

checklistEntradaPhotoInput?.addEventListener('change', function handleChecklistPhotoChange() {
    const itemId = this.dataset.itemId || checklistEntradaCropItemId || '';
    if (!itemId) {
        this.value = '';
        return;
    }

    const files = Array.from(this.files || []).filter((file) => String(file.type || '').startsWith('image/'));
    if (!files.length) {
        this.value = '';
        return;
    }

    checklistEntradaCropItemId = String(itemId);
    checklistEntradaCropQueue = files.slice();
    processNextChecklistEntradaCrop();
    this.value = '';
});

updateChecklistInlineSummary();
syncChecklistEntradaInputs();

// --- Select2 ---
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

function escapeEquipamentoHtml(value) {
    return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function normalizeEquipamentoSearch(value) {
    return String(value || '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase();
}

function buildEquipamentoPrimary(meta) {
    const tipo = String(meta.tipo_nome || meta.tipo || '').trim();
    const marca = String(meta.marca_nome || meta.marca || '').trim();
    const parts = [tipo, marca].filter(Boolean);
    return parts.length ? parts.join(' - ') : 'Equipamento';
}

function buildEquipamentoSecondary(meta) {
    const modelo = String(meta.modelo_nome || meta.modelo || '').trim();
    const cor = String(meta.cor || '').trim();
    const parts = [modelo, cor].filter(Boolean);
    return parts.length ? parts.join(' - ') : 'Modelo ou cor não informados';
}

function buildEquipamentoIdentity(meta) {
    const serie = String(meta.numero_serie || meta.serie || '').trim();
    const imei = String(meta.imei || '').trim();
    if (serie && imei) {
        return `Nº série: ${serie} | IMEI: ${imei}`;
    }
    if (serie) {
        return `Nº série: ${serie}`;
    }
    if (imei) {
        return `IMEI: ${imei}`;
    }
    return 'Sem número de série ou IMEI';
}

function buildEquipamentoFallback(meta) {
    const raw = String(meta.tipo_nome || meta.tipo || meta.marca_nome || meta.marca || meta.modelo_nome || meta.modelo || 'EQ').trim();
    return raw ? raw.charAt(0).toUpperCase() : 'E';
}

function getEquipamentoOptionMeta(source) {
    const element = source?.element || source;
    const dataset = element?.dataset || {};
    return {
        id: String(source?.id || element?.value || ''),
        tipo: String(dataset.tipo || ''),
        tipo_nome: String(dataset.tipo_nome || ''),
        marca: String(dataset.marca || ''),
        marca_nome: String(dataset.marca || ''),
        modelo: String(dataset.modelo || ''),
        modelo_nome: String(dataset.modelo || ''),
        cor: String(dataset.cor || ''),
        numero_serie: String(dataset.serie || ''),
        imei: String(dataset.imei || ''),
        foto_url: String(dataset.foto_url || '')
    };
}

function renderEquipamentoOptionTemplate(data) {
    if (data.loading) return data.text;
    if (!data.id) {
        return `<span class="text-muted">${escapeEquipamentoHtml(data.text || 'Selecione o equipamento...')}</span>`;
    }

    const meta = getEquipamentoOptionMeta(data);
    const thumbHtml = meta.foto_url
        ? `<img src="${escapeEquipamentoHtml(meta.foto_url)}" alt="">`
        : `<span class="os-equip-select-fallback">${escapeEquipamentoHtml(buildEquipamentoFallback(meta))}</span>`;

    return `
        <div class="os-equip-select-result">
            <div class="os-equip-select-thumb">${thumbHtml}</div>
            <div class="os-equip-select-copy">
                <strong>${escapeEquipamentoHtml(buildEquipamentoPrimary(meta))}</strong>
                <small>${escapeEquipamentoHtml(buildEquipamentoSecondary(meta))}</small>
                <small>${escapeEquipamentoHtml(buildEquipamentoIdentity(meta))}</small>
            </div>
        </div>
    `;
}

function equipamentoSelectMatcher(params, data) {
    const term = String(params.term || '').trim();
    if (!term) {
        return data;
    }

    if (!data.id || !data.element) {
        return null;
    }

    const meta = getEquipamentoOptionMeta(data);
    const haystack = normalizeEquipamentoSearch([
        buildEquipamentoPrimary(meta),
        buildEquipamentoSecondary(meta),
        buildEquipamentoIdentity(meta),
        meta.tipo_nome,
        meta.marca_nome,
        meta.modelo_nome,
        meta.cor,
        meta.numero_serie,
        meta.imei
    ].join(' '));
    const needle = normalizeEquipamentoSearch(term);

    return haystack.includes(needle) ? data : null;
}

function initEquipamentoSelect2() {
    if (typeof $.fn.select2 === 'undefined') {
        return;
    }

    const $equipamentoSelect = $('#equipamentoSelect');
    if (!$equipamentoSelect.length) {
        return;
    }

    if ($equipamentoSelect.hasClass('select2-hidden-accessible')) {
        try {
            $equipamentoSelect.off('.osEquipSelect2');
            $equipamentoSelect.select2('destroy');
        } catch (e) {}
    }

    $equipamentoSelect.select2({
        theme: 'bootstrap-5',
        placeholder: 'Selecione o equipamento...',
        allowClear: true,
        width: '100%',
        escapeMarkup: function (markup) { return markup; },
        templateResult: renderEquipamentoOptionTemplate,
        templateSelection: renderEquipamentoOptionTemplate,
        matcher: equipamentoSelectMatcher
    }).on('change.osEquipSelect2', function() {
        _onEquipamentoChange(this.value, this.options[this.selectedIndex]);
    });
}

if (typeof $.fn.select2 !== 'undefined') {
    initEquipamentoSelect2();
}

// --- Modal: Cadastrar ou Editar Cliente ---
const btnNovoCliente = document.getElementById('btnNovoCliente');
const btnEditarClienteOS = document.getElementById('btnEditarClienteOS');
const clienteOsSelect = $('#clienteOsSelect');
const modalClienteElement = document.getElementById('modalNovoCliente');
const clientesMeta = <?= json_encode($clientesMeta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?> || {};

function normalizeClienteMeta(cliente = {}) {
    const id = String(cliente.id || '');
    const nome = String(cliente.nome || cliente.nome_razao || '').trim();
    const telefone = String(cliente.telefone || cliente.telefone1 || '').trim();
    const endereco = String(
        cliente.endereco_resumo
        || cliente.endereco
        || [
            cliente.endereco,
            cliente.numero,
            cliente.bairro,
            cliente.cidade,
            cliente.uf
        ].filter((value) => String(value || '').trim() !== '').join(', ')
    ).trim();

    return { id, nome, telefone, endereco };
}

function syncClienteOptionDataset(optionElement, meta) {
    if (!optionElement || !meta) {
        return;
    }

    optionElement.dataset.nome = meta.nome || '';
    optionElement.dataset.telefone = meta.telefone || '';
    optionElement.dataset.endereco = meta.endereco || '';
}

function renderClienteInfoCard(clienteId) {
    const emptyState = document.getElementById('clienteInfoEmpty');
    const content = document.getElementById('clienteInfoContent');
    const nomeEl = document.getElementById('clienteInfoNome');
    const telefoneWrap = document.getElementById('clienteInfoTelefoneWrap');
    const telefoneEl = document.getElementById('clienteInfoTelefone');
    const enderecoWrap = document.getElementById('clienteInfoEnderecoWrap');
    const enderecoEl = document.getElementById('clienteInfoEndereco');

    if (!emptyState || !content || !nomeEl || !telefoneWrap || !telefoneEl || !enderecoWrap || !enderecoEl) {
        return;
    }

    const option = document.querySelector(`#clienteOsSelect option[value="${String(clienteId || '')}"]`);
    const meta = clientesMeta[String(clienteId || '')] || (option ? {
        id: String(clienteId || ''),
        nome: option.dataset.nome || option.textContent.trim(),
        telefone: option.dataset.telefone || '',
        endereco: option.dataset.endereco || ''
    } : null);
    if (!meta || !meta.nome) {
        emptyState.classList.remove('d-none');
        content.classList.add('d-none');
        nomeEl.textContent = '-';
        telefoneEl.textContent = '-';
        enderecoEl.textContent = '-';
        telefoneWrap.classList.add('d-none');
        enderecoWrap.classList.add('d-none');
        return;
    }

    emptyState.classList.add('d-none');
    content.classList.remove('d-none');
    nomeEl.textContent = meta.nome;
    telefoneEl.textContent = meta.telefone || 'Telefone nao informado';
    enderecoEl.textContent = meta.endereco || 'Endereco nao informado';
    telefoneWrap.classList.toggle('d-none', !meta.telefone);
    enderecoWrap.classList.toggle('d-none', !meta.endereco);
}

function initClienteOsSelect2() {
    if (typeof $.fn.select2 === 'undefined' || !clienteOsSelect.length) {
        return false;
    }

    if (clienteOsSelect.hasClass('select2-hidden-accessible')) {
        clienteOsSelect.off('select2:open.osClienteSelect2');
        clienteOsSelect.select2('destroy');
    }

    clienteOsSelect
        .select2({
            theme: 'bootstrap-5',
            placeholder: 'Buscar cliente...',
            allowClear: true,
            width: '100%'
        })
        .off('select2:open.osClienteSelect2')
        .on('select2:open.osClienteSelect2', function() {
            // Mantido para futuras extensoes do dropdown do cliente.
        });

    return true;
}

// Inicializa a instância do modal uma unica vez para evitar conflitos de backdrop
let modalClienteInstance;

document.addEventListener('DOMContentLoaded', () => {
    if (!modalClienteElement) return;
    if (typeof bootstrap !== 'undefined') {
        modalClienteInstance = bootstrap.Modal.getOrCreateInstance(modalClienteElement);
    }
});

function syncClienteOption(cliente, options = {}) {
    if (!cliente || !cliente.id) {
        return;
    }

    const meta = normalizeClienteMeta(cliente);
    const clienteId = meta.id;
    const clienteNome = meta.nome;
    const shouldReloadDependents = Boolean(options.reloadDependents);
    const select2WasActive = typeof $.fn.select2 !== 'undefined' && clienteOsSelect.hasClass('select2-hidden-accessible');
    let option = clienteOsSelect.find(`option[value="${clienteId}"]`);
    let optionElement = option.get(0) || null;
    clientesMeta[clienteId] = meta;

    if (!option.length) {
        option = $(new Option(clienteNome, clienteId, true, true));
        clienteOsSelect.append(option);
        optionElement = option.get(0) || null;
    } else {
        option.text(clienteNome);
        option.prop('selected', true);
        optionElement = option.get(0) || null;
        if (optionElement) {
            optionElement.text = clienteNome;
            optionElement.label = clienteNome;
            optionElement.selected = true;
        }
    }

    syncClienteOptionDataset(optionElement, meta);

    const nativeSelect = document.getElementById('clienteOsSelect');
    if (nativeSelect) {
        nativeSelect.value = clienteId;
    }

    if (select2WasActive) {
        initClienteOsSelect2();
    }

    if (typeof $.fn.select2 !== 'undefined' && clienteOsSelect.hasClass('select2-hidden-accessible')) {
        clienteOsSelect.val(clienteId);
        clienteOsSelect.trigger('change.select2');

        const select2Instance = clienteOsSelect.data('select2');
        const renderedSelection = select2Instance?.$container?.find('.select2-selection__rendered');
        if (renderedSelection?.length) {
            renderedSelection.text(clienteNome);
            renderedSelection.attr('title', clienteNome);
        }

        const renderedContainerId = clienteOsSelect.attr('id')
            ? `#select2-${clienteOsSelect.attr('id')}-container`
            : null;
        if (renderedContainerId) {
            $(renderedContainerId).text(clienteNome).attr('title', clienteNome);
        }

        const openResult = select2Instance?.$dropdown?.find(`[id$="-${clienteId}"]`);
        if (openResult?.length) {
            openResult.text(clienteNome);
        }

        if (optionElement) {
            clienteOsSelect.trigger({
                type: 'select2:select',
                params: {
                    data: {
                        id: clienteId,
                        text: clienteNome,
                        element: optionElement,
                    },
                },
            });
        }
    }

    if (shouldReloadDependents) {
        clienteOsSelect.val(clienteId).trigger('change');
    } else {
        renderClienteInfoCard(clienteId);
        updateResumo();
        scheduleDraftSave();
    }

    if (btnEditarClienteOS) {
        btnEditarClienteOS.classList.toggle('d-none', clienteId === '');
    }
}

function notifyParentClienteUpdated(cliente) {
    if (!window.parent || window.parent === window) {
        return;
    }

    try {
        window.parent.postMessage({
            type: 'os:list-refresh',
            reason: 'cliente-updated',
            cliente: {
                id: Number(cliente.id || 0),
                nome: String(cliente.nome || cliente.nome_razao || ''),
            },
        }, window.location.origin);
    } catch (error) {
        console.error('[OSForm] Falha ao notificar janela pai sobre atualizacao de cliente.', error);
    }
}

if (btnNovoCliente) {
    btnNovoCliente.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        document.getElementById('formNovoClienteAjax').reset();
        document.getElementById('modalNovoClienteId').value = '';
        document.querySelector('#modalNovoCliente .modal-title').innerHTML = '<i class="bi bi-person-plus text-warning me-2"></i>Cadastro Rápido de Cliente';
        document.getElementById('btnSalvarNovoCliente').innerHTML = '<i class="bi bi-check-lg me-1"></i>Cadastrar Cliente';
        document.getElementById('modalClienteErrors')?.classList.add('d-none');
        modalClienteInstance?.show();
    });
}

if (btnEditarClienteOS) {
    btnEditarClienteOS.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const clienteId = clienteOsSelect.val();
        if(!clienteId) return;

        fetch(`${BASE_URL}clientes/json/${clienteId}`)
            .then(r => r.json())
            .then(res => {
                if(res.error) {
                    Swal.fire('Erro', res.error, 'error');
                    return;
                }
                const form = document.getElementById('formNovoClienteAjax');
                form.reset();
                document.getElementById('modalNovoClienteId').value = res.id;
                form.elements['nome_razao'].value = res.nome_razao || '';
                form.elements['telefone1'].value = res.telefone1 || '';
                form.elements['email'].value = res.email || '';
                form.elements['cpf_cnpj'].value = res.cpf_cnpj || '';
                form.elements['nome_contato'].value = res.nome_contato || '';
                form.elements['telefone_contato'].value = res.telefone_contato || '';
                form.elements['cep'].value = res.cep || '';
                form.elements['endereco'].value = res.endereco || '';
                form.elements['numero'].value = res.numero || '';
                form.elements['bairro'].value = res.bairro || '';
                form.elements['cidade'].value = res.cidade || '';
                form.elements['uf'].value = res.uf || '';

                document.querySelector('#modalNovoCliente .modal-title').innerHTML = '<i class="bi bi-pencil text-warning me-2"></i>Editar Cliente';
                document.getElementById('btnSalvarNovoCliente').innerHTML = '<i class="bi bi-check-lg me-1"></i>Atualizar Cliente';
                document.getElementById('modalClienteErrors')?.classList.add('d-none');
                modalClienteInstance?.show();
            })
            .catch((err) => {
                console.error("Erro no fetch Editar Cliente:", err);
                Swal.fire('Erro', 'Não foi possível buscar ou renderizar os dados do cliente: ' + err.message, 'error');
            });
    });
}

// Toggle do botao editar com base na selecao
clienteOsSelect.on('change', function() {
    if(btnEditarClienteOS) {
        if($(this).val()) {
            btnEditarClienteOS.classList.remove('d-none');
        } else {
            btnEditarClienteOS.classList.add('d-none');
        }
    }
});
// Dispara no carregamento
clienteOsSelect.trigger('change');

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
            errors.innerHTML = res.message || 'Erro ao salvar cliente.';
            errors.classList.remove('d-none');
            return;
        }

        const clientePayload = res.cliente || {
            id: res.id,
            nome: res.nome,
            telefone1: res.telefone1 || '',
            endereco: res.endereco || '',
            numero: res.numero || '',
            bairro: res.bairro || '',
            cidade: res.cidade || '',
            uf: res.uf || ''
        };

        if (res.is_update) {
            syncClienteOption(clientePayload, { reloadDependents: false });
        } else {
            syncClienteOption(clientePayload, { reloadDependents: true });
            if (false) {
            // Adiciona novo ao Select2
            const opt = new Option(res.nome, res.id, true, true);
            clienteOsSelect.append(opt).trigger('change');
            
            // Dispara o mudou(cliente) para recarregar equipamentos do novo cliente (virão vazios mas reseta combobox)
            _onClienteChange(res.id);
            }
        }

        notifyParentClienteUpdated(clientePayload);
        modalClienteInstance?.hide();
        form.reset();
        if (window.Swal && typeof window.Swal.fire === 'function') {
            Swal.fire({
                icon: 'success',
                title: res.is_update ? 'Cliente atualizado' : 'Cliente cadastrado',
                text: res.is_update
                    ? 'Os dados do cliente foram salvos com sucesso.'
                    : 'Cliente cadastrado com sucesso.',
                timer: 1800,
                showConfirmButton: false
            });
        }
        
    })
    .catch(() => {
        errors.innerHTML = 'Erro inesperado. Tente novamente.';
        errors.classList.remove('d-none');
    });
});

// --- Sidebar layout toggling ---
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
    el.innerHTML = ok
        ? '<i class="bi bi-check-circle-fill" aria-hidden="true"></i><span class="visually-hidden">Preenchido</span>'
        : '<i class="bi bi-x-circle-fill" aria-hidden="true"></i><span class="visually-hidden">Pendente</span>';
    el.className = ok ? 'text-success' : 'text-danger';
    el.title = ok ? 'Preenchido' : 'Pendente';
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
    const acessoriosInp = document.querySelector('textarea[name="acessorios"]');
    const checklistResumo = getChecklistResumoState();

    const clienteText = _getSelectedText(clienteSel, 'Não selecionado');
    const equipText   = _getSelectedText(equipSel, 'Não selecionado');
    const tecnicoText = _getSelectedText(tecnicoSel, 'Não atribuído');
    const prioridadeVal = prioridadeSel?.value || 'normal';
    const statusVal = statusSel?.value || 'triagem';
    const relatoVal = relatoInp?.value?.trim() || '';
    const acessoriosVal = acessoriosInp?.value?.trim() || '';

    document.getElementById('resumoCliente').textContent = clienteText;
    document.getElementById('resumoEquipamento').textContent = equipText;
    document.getElementById('resumoTecnico').textContent = tecnicoText;
    document.getElementById('resumoEntrada').textContent = _formatDateTime(entradaInp?.value);
    document.getElementById('resumoPrevisao').textContent = _formatDate(previsaoInp?.value);
    const semAcessorios = acessoriosVal.toLowerCase() === 'sem acessorios';
    document.getElementById('resumoRelato').textContent = relatoVal ? 'Preenchido' : 'Vazio';
    document.getElementById('resumoAcessorios').textContent = semAcessorios ? 'Sem acessorios' : (acessoriosVal ? 'Informado' : 'Nao informado');
    document.getElementById('resumoEstadoFisico').textContent = checklistResumo.label;

    const prioridadeBadgeClass = {
        baixa: 'text-bg-secondary',
        normal: 'text-bg-primary',
        alta: 'text-bg-warning',
        urgente: 'text-bg-danger'
    }[prioridadeVal] || 'text-bg-secondary';
    _setResumoBadge('resumoPrioridade', prioridadeLabels[prioridadeVal] || 'Normal', prioridadeBadgeClass);

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
    _setFieldStatus('statusAcessorios', semAcessorios || Boolean(acessoriosVal));
    _setFieldStatus('statusEstadoFisico', checklistResumo.ok);
    _setFieldStatus('statusFotos', totalFotos > 0);
    if (document.getElementById('statusDefeitos')) {
        _setFieldStatus('statusDefeitos', defeitosCount > 0);
    }
}

const relatoClienteInput = document.getElementById('relatoClienteInput') || document.querySelector('textarea[name="relato_cliente"]');
const relatoQuickButtons = document.getElementById('relatoQuickButtons');

function relatoNormalizarTexto(texto) {
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
            const texto = relatoNormalizarTexto(btn.dataset.relatoOpcao || '');
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

function parseProcedimentosExecutados(value) {
    const raw = String(value || '');
    if (!raw.trim()) return [];
    return raw
        .split(/\r?\n/)
        .map(item => item.trim())
        .filter(item => item !== '');
}

function formatProcedimentoTimestamp(date = new Date()) {
    const dt = (date instanceof Date) ? date : new Date();
    if (Number.isNaN(dt.getTime())) {
        return '';
    }

    const dd = String(dt.getDate()).padStart(2, '0');
    const mm = String(dt.getMonth() + 1).padStart(2, '0');
    const yy = String(dt.getFullYear()).slice(-2);
    const hh = String(dt.getHours()).padStart(2, '0');
    const min = String(dt.getMinutes()).padStart(2, '0');
    return `${dd}/${mm}/${yy}-${hh}:${min}`;
}

function getTecnicoLabelForProcedimento() {
    const tecnicoSel = document.querySelector('select[name="tecnico_id"]');
    if (!tecnicoSel || !tecnicoSel.value) {
        return 'Nao atribuido';
    }

    const selected = tecnicoSel.options[tecnicoSel.selectedIndex];
    const label = String(selected?.textContent || '').trim();
    return label || 'Nao atribuido';
}

function renderProcedimentosExecutadosList(items) {
    if (!procedimentosExecutadosLista) {
        return;
    }

    procedimentosExecutadosLista.innerHTML = '';

    if (!items.length) {
        const empty = document.createElement('div');
        empty.className = 'text-muted small';
        empty.textContent = 'Nenhum procedimento inserido.';
        procedimentosExecutadosLista.appendChild(empty);
        return;
    }

    items.forEach((item) => {
        const line = document.createElement('div');
        line.className = 'procedimento-executado-item';
        line.textContent = item;
        procedimentosExecutadosLista.appendChild(line);
    });
}

function syncProcedimentosExecutadosList(items) {
    if (procedimentosExecutadosInput) {
        procedimentosExecutadosInput.value = items.join('\n');
    }
    renderProcedimentosExecutadosList(items);
}

function inserirProcedimentoExecutado() {
    if (!procedimentosExecutadosInput || !procedimentoExecutadoTextoInput) {
        return;
    }

    const procedimentoBase = String(procedimentoExecutadoTextoInput.value || '').trim();
    if (!procedimentoBase) {
        showWarningDialog('Informe o procedimento antes de inserir.', 'Procedimento vazio');
        procedimentoExecutadoTextoInput.focus();
        return;
    }

    const tecnicoNome = getTecnicoLabelForProcedimento();
    const stamp = formatProcedimentoTimestamp(new Date());
    const linha = `[${procedimentoBase} - ${stamp} - tecnico: ${tecnicoNome}]`;
    const items = parseProcedimentosExecutados(procedimentosExecutadosInput.value);
    items.push(linha);
    syncProcedimentosExecutadosList(items);

    procedimentoExecutadoTextoInput.value = '';
    procedimentoExecutadoTextoInput.focus();
    updateResumo();
    scheduleDraftSave();
}

function initProcedimentosExecutadosEditor() {
    if (!procedimentosExecutadosInput || !procedimentosExecutadosLista) {
        return;
    }

    const items = parseProcedimentosExecutados(procedimentosExecutadosInput.value);
    syncProcedimentosExecutadosList(items);

    btnInserirProcedimentoExecutado?.addEventListener('click', inserirProcedimentoExecutado);
    procedimentoExecutadoTextoInput?.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter') {
            return;
        }
        event.preventDefault();
        inserirProcedimentoExecutado();
    });

    procedimentosExecutadosInput.addEventListener('input', () => {
        renderProcedimentosExecutadosList(parseProcedimentosExecutados(procedimentosExecutadosInput.value));
    });

    document.querySelector('textarea[name="diagnostico_tecnico"]')?.addEventListener('input', () => {
        updateResumo();
        scheduleDraftSave();
    });
    document.querySelector('textarea[name="solucao_aplicada"]')?.addEventListener('input', () => {
        updateResumo();
        scheduleDraftSave();
    });
}

initProcedimentosExecutadosEditor();

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
const acessoriosSemItensCheckbox = document.getElementById('acessoriosSemItens');
const acessoriosQuickForm = document.getElementById('acessoriosQuickForm');
const acessoriosQuickTitle = document.getElementById('acessoriosQuickTitle');
const acessoriosQuickFields = document.getElementById('acessoriosQuickFields');
const acessoriosQuickSave = document.getElementById('acessoriosQuickSave');
const acessoriosQuickCancel = document.getElementById('acessoriosQuickCancel');
const acessoriosQuickClose = document.getElementById('acessoriosQuickClose');
const acessoriosQuickAddFoto = document.getElementById('acessoriosQuickAddFoto');
const acessoriosQuickAddFotoCamera = document.getElementById('acessoriosQuickAddFotoCamera');
const acessoriosQuickPhotosPreview = document.getElementById('acessoriosQuickPhotosPreview');
const acessoriosQuickPhotosHint = document.getElementById('acessoriosQuickPhotosHint');
const acessoriosPhotoInput = document.getElementById('acessoriosPhotoInput');
const acessoriosFilesInputs = document.getElementById('acessoriosFilesInputs');
document.querySelector('#acessoriosSemItensLegacy + label')?.setAttribute('for', 'acessoriosSemItensLegacy');
const acessoriosPhotos = {};
const acessoriosFileInputs = {};
let acessoriosEntries = [];
let acessoriosEditing = null;
let acessoriosCurrentKey = null;
let acessoriosQuickEntryId = null;
let acessoriosPhotoTarget = null;
let acessorioCropQueue = [];
let acessorioCropEntryId = null;
const ACCESSORIOS_SEM_ITENS_TEXT = 'Sem acessórios';

const initialAcessoriosText = acessoriosInput?.value?.trim() || '';
if (acessoriosSemItensCheckbox && initialAcessoriosText.toLowerCase() === ACCESSORIOS_SEM_ITENS_TEXT.toLowerCase()) {
    acessoriosSemItensCheckbox.checked = true;
}
if (initialAcessoriosText && initialAcessoriosText.toLowerCase() !== ACCESSORIOS_SEM_ITENS_TEXT.toLowerCase()) {
    initialAcessoriosText.split(/\r?\n/).filter(Boolean).forEach(text => {
        acessoriosEntries.push({ id: `acc_${Date.now()}_${Math.random().toString(36).slice(2)}`, text, key: 'outro' });
    });
}

function generateEntryId() {
    return `acc_${Date.now()}_${Math.random().toString(36).substring(2, 8)}`;
}

function isAcessoriosSemItensChecked() {
    return Boolean(acessoriosSemItensCheckbox?.checked);
}

function clearAllAcessorios() {
    acessoriosEntries.forEach(entry => removeAcessorioFileInput(entry.id));
    if (acessoriosQuickEntryId && !acessoriosEntries.some(entry => entry.id === acessoriosQuickEntryId)) {
        removeAcessorioFileInput(acessoriosQuickEntryId);
    }
    acessoriosQuickEntryId = null;
    acessoriosEntries = [];
}

function refreshAcessoriosSemItensUi() {
    const isSemItens = isAcessoriosSemItensChecked();
    document.querySelectorAll('[data-acessorio-key]').forEach(btn => {
        btn.disabled = isSemItens;
    });
    if (isSemItens) {
        closeAcessoriosForm();
    }
}

function syncAcessoriosInput() {
    if (!acessoriosInput) return;
    if (isAcessoriosSemItensChecked()) {
        acessoriosInput.value = ACCESSORIOS_SEM_ITENS_TEXT;
        if (acessoriosDataInput) {
            acessoriosDataInput.value = JSON.stringify([]);
        }
        updateResumo();
        scheduleDraftSave();
        return;
    }

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

function getCurrentAcessorioEntryId() {
    if (acessoriosEditing !== null && acessoriosEntries[acessoriosEditing]?.id) {
        return acessoriosEntries[acessoriosEditing].id;
    }
    if (acessoriosQuickEntryId) {
        return acessoriosQuickEntryId;
    }
    acessoriosQuickEntryId = generateEntryId();
    return acessoriosQuickEntryId;
}

function updateAcessoriosQuickPhotosHint(entryId = null) {
    if (!acessoriosQuickPhotosHint) return;
    const currentId = entryId || acessoriosQuickEntryId;
    if (!currentId) {
        acessoriosQuickPhotosHint.textContent = 'Adicione fotos antes de salvar o item.';
        return;
    }
    const total = acessoriosPhotos[currentId]?.files?.length || 0;
    acessoriosQuickPhotosHint.textContent = total > 0
        ? `${total} foto(s) pronta(s) para salvar neste acessorio.`
        : 'Adicione fotos antes de salvar o item.';
}

function renderAcessoriosQuickPhotos() {
    if (!acessoriosQuickPhotosPreview) return;
    acessoriosQuickPhotosPreview.innerHTML = '';
    const entryId = acessoriosQuickEntryId;
    if (!entryId) {
        updateAcessoriosQuickPhotosHint();
        return;
    }
    ensureAcessorioFileInput(entryId);
    renderAcessoriosPhotos(entryId, acessoriosQuickPhotosPreview);
    updateAcessoriosQuickPhotosHint(entryId);
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
    if (isAcessoriosSemItensChecked()) {
        const item = document.createElement('div');
        item.className = 'list-group-item text-muted';
        item.textContent = 'Marcado como sem acessórios.';
        acessoriosList.appendChild(item);
        return;
    }

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
                    <button type="button" class="btn btn-outline-primary btn-sm btn-add-foto-camera" data-entry="${entry.id}"><i class="bi bi-camera-video"></i> Câmera</button>
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
    updateResumo();
}

function closeAcessoriosForm() {
    if (
        acessoriosEditing === null &&
        acessoriosQuickEntryId &&
        !acessoriosEntries.some(entry => entry.id === acessoriosQuickEntryId)
    ) {
        removeAcessorioFileInput(acessoriosQuickEntryId);
    }
    acessoriosQuickForm?.classList.add('d-none');
    acessoriosQuickFields.innerHTML = '';
    acessoriosEditing = null;
    acessoriosQuickEntryId = null;
    renderAcessoriosQuickPhotos();
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
            quickColorsDesktop.className = 'd-none d-md-flex flex-wrap gap-1 mt-2 w-100 os-acessorio-quick-colors';
            COMMON_ACCESSORY_COLORS.forEach(color => {
                const quickBtn = document.createElement('button');
                quickBtn.type = 'button';
                quickBtn.className = 'btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1 px-2 py-1 os-acessorio-quick-color-btn';
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
        acessoriosQuickEntryId = acessoriosEntries[index]?.id || null;
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
    } else {
        acessoriosEditing = null;
        acessoriosQuickEntryId = generateEntryId();
    }
    renderAcessoriosQuickPhotos();
    acessoriosQuickForm?.classList.remove('d-none');
}

function handleAcessoriosButtonClick(event) {
    if (isAcessoriosSemItensChecked()) return;
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
    if (isAcessoriosSemItensChecked()) return;
    const key = acessoriosCurrentKey;
    const config = acessoriosConfig[key];
    if (!config) return;
    const values = collectFormValues();
    const entryId = getCurrentAcessorioEntryId();
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
        acessoriosEntries.push({ id: entryId, text, values, key });
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
    closeImageModalIfOpen();
    acessoriosPhotoTarget = entryId;
    acessoriosPhotoInput.dataset.entryId = entryId;
    acessoriosPhotoInput?.click();
}

function openAcessorioCameraCapture(entryId) {
    if (!entryId) return;
    acessorioCropEntryId = entryId;
    acessorioCropQueue = [];
    openCameraCapture({ type: 'acessorio', entryId });
}

function readFileAsDataUrl(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = e => resolve(e.target.result);
        reader.onerror = reject;
        reader.readAsDataURL(file);
    });
}

async function processNextAcessorioCrop() {
    if (!acessorioCropEntryId) return;
    if (!acessorioCropQueue.length) {
        hideModalSafe(modalCrop, '#modalCropEquip');
        return;
    }
    const nextFile = acessorioCropQueue.shift();
    try {
        const source = await readFileAsDataUrl(nextFile);
        openCropper(source, { type: 'acessorio' });
    } catch (e) {
        processNextAcessorioCrop();
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
    acessorioCropEntryId = entryId;
    acessorioCropQueue = files.slice();
    processNextAcessorioCrop();
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
    if (entryId === acessoriosQuickEntryId) {
        renderAcessoriosQuickPhotos();
    }
    updateAcessoriosQuickPhotosHint(entryId);
}

document.querySelectorAll('[data-acessorio-key]').forEach(btn => {
    btn.addEventListener('click', handleAcessoriosButtonClick);
});
acessoriosSemItensCheckbox?.addEventListener('change', () => {
    const enableSemItens = Boolean(acessoriosSemItensCheckbox.checked);
    if (!enableSemItens) {
        refreshAcessoriosSemItensUi();
        renderAcessoriosList();
        syncAcessoriosInput();
        return;
    }

    if (!acessoriosEntries.length) {
        refreshAcessoriosSemItensUi();
        renderAcessoriosList();
        syncAcessoriosInput();
        return;
    }

    const applySemItens = () => {
        clearAllAcessorios();
        refreshAcessoriosSemItensUi();
        renderAcessoriosList();
        syncAcessoriosInput();
    };

    if (window.Swal && typeof window.Swal.fire === 'function') {
        Swal.fire({
            icon: 'warning',
            title: 'Marcar como sem acessórios?',
            text: 'Os acessórios já adicionados seráo removidos.',
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
            acessoriosSemItensCheckbox.checked = false;
            refreshAcessoriosSemItensUi();
        });
        return;
    }

    const confirmed = confirm('Marcar como sem acessórios vai remover os acessórios já adicionados. Deseja continuar?');
    if (confirmed) {
        applySemItens();
        return;
    }
    acessoriosSemItensCheckbox.checked = false;
    refreshAcessoriosSemItensUi();
});
acessoriosQuickSave?.addEventListener('click', handleAcessoriosSave);
acessoriosQuickCancel?.addEventListener('click', handleAcessoriosCancel);
acessoriosQuickClose?.addEventListener('click', handleAcessoriosCancel);
acessoriosQuickAddFoto?.addEventListener('click', () => {
    if (isAcessoriosSemItensChecked()) return;
    const entryId = getCurrentAcessorioEntryId();
    openAcessorioPhotoInput(entryId);
});
acessoriosQuickAddFotoCamera?.addEventListener('click', () => {
    if (isAcessoriosSemItensChecked()) return;
    const entryId = getCurrentAcessorioEntryId();
    openAcessorioCameraCapture(entryId);
});
document.addEventListener('click', event => {
    const removeBtn = event.target.closest('.btn-remove-acessorio');
    if (removeBtn) handleRemoveAcessorio({ currentTarget: removeBtn });
    const editBtn = event.target.closest('.btn-edit-acessorio');
    if (editBtn) handleEditAcessorio({ currentTarget: editBtn });
    const addPhotoBtn = event.target.closest('.btn-add-foto');
    if (addPhotoBtn) openAcessorioPhotoInput(addPhotoBtn.dataset.entry);
    const addPhotoCameraBtn = event.target.closest('.btn-add-foto-camera');
    if (addPhotoCameraBtn) openAcessorioCameraCapture(addPhotoCameraBtn.dataset.entry);
    const removePhotoBtn = event.target.closest('.btn-remove-foto-accessorio');
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
acessoriosPhotoInput?.addEventListener('change', handlePhotoInputChange);

const estadoFisicoConfig = {
    tela_trincada: {
        title: 'Tela trincada',
        fields: [{ name: 'detalhe', label: 'Detalhe (opcional)', placeholder: 'Ex: canto superior direito' }],
        format: values => composeAccessoryText('Tela trincada', values.detalhe)
    },
    arranhoes: {
        title: 'Arranhoes',
        fields: [{ name: 'detalhe', label: 'Detalhe (opcional)', placeholder: 'Ex: tampa e lateral' }],
        format: values => composeAccessoryText('Arranhoes', values.detalhe)
    },
    carcaca_quebrada: {
        title: 'Carcaca quebrada',
        fields: [{ name: 'detalhe', label: 'Detalhe (opcional)', placeholder: 'Ex: quina inferior' }],
        format: values => composeAccessoryText('Carcaca quebrada', values.detalhe)
    },
    vidro_traseiro_quebrado: {
        title: 'Vidro traseiro quebrado',
        fields: [{ name: 'detalhe', label: 'Detalhe (opcional)', placeholder: 'Ex: fissura central' }],
        format: values => composeAccessoryText('Vidro traseiro quebrado', values.detalhe)
    },
    amassado: {
        title: 'Amassado',
        fields: [{ name: 'detalhe', label: 'Detalhe (opcional)', placeholder: 'Ex: lateral esquerda' }],
        format: values => composeAccessoryText('Amassado', values.detalhe)
    },
    botao_quebrado: {
        title: 'Botao quebrado',
        fields: [{ name: 'detalhe', label: 'Qual botao?', placeholder: 'Ex: power' }],
        format: values => composeAccessoryText('Botao quebrado', values.detalhe)
    },
    outro: {
        title: 'Outro dano',
        fields: [{ name: 'descricao', label: 'Descricao', placeholder: 'Ex: camera traseira quebrada' }],
        format: values => values.descricao || 'Outro dano'
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
        estadoFisicoEntries.push({ id: `est_${Date.now()}_${Math.random().toString(36).slice(2)}`, text, key: 'outro' });
    });
}

function generateEstadoFisicoEntryId() {
    return `est_${Date.now()}_${Math.random().toString(36).substring(2, 8)}`;
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
        input.className = 'd-none';
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
            preview.style.cursor = 'zoom-in';
            preview.setAttribute('data-bs-toggle', 'modal');
            preview.setAttribute('data-bs-target', '#imageModal');
            preview.setAttribute('data-img-src', e.target.result);
            preview.innerHTML = `<img src="${e.target.result}" class="w-100 h-100 object-fit-cover">`;

            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'btn btn-sm btn-outline-light position-absolute top-0 end-0 m-1 btn-remove-foto-estado';
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
            <div class="d-flex justify-content-between align-items-center">
                <span class="fw-semibold">${entry.text}</span>
                <div class="d-flex gap-1">
                    <button type="button" class="btn btn-outline-info btn-sm btn-add-foto-estado" data-entry="${entry.id}"><i class="bi bi-camera"></i> Adicionar foto</button>
                    <button type="button" class="btn btn-outline-primary btn-sm btn-add-foto-camera-estado" data-entry="${entry.id}"><i class="bi bi-camera-video"></i> Câmera</button>
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
    estadoFisicoQuickForm?.classList.add('d-none');
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

    estadoFisicoQuickForm?.classList.remove('d-none');
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
        const source = await readFileAsDataUrl(nextFile);
        openCropper(source, { type: 'estado_fisico' });
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
        if (idx !== index) newDt.items.add(file);
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
refreshAcessoriosSemItensUi();
renderAcessoriosList();
syncAcessoriosInput();

function getTotalAcessoriosFotos() {
    return Object.keys(acessoriosPhotos).reduce((sum, id) => sum + (acessoriosPhotos[id]?.files?.length || 0), 0);
}

function getTotalEstadoFisicoFotos() {
    return Object.keys(estadoFisicoPhotos).reduce((sum, id) => sum + (estadoFisicoPhotos[id]?.files?.length || 0), 0);
}

function getTotalFotosEntradaResumo() {
    const fotosEntradaNovas = document.getElementById('fotosEntradaInput')?.files?.length || 0;
    const fotosEntradaExistentes = existingFotosCount || 0;
    return fotosEntradaNovas + fotosEntradaExistentes + getTotalAcessoriosFotos() + getTotalChecklistEntradaFotos();
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
    const acessoriosInp = document.querySelector('textarea[name="acessorios"]');
    const estadoFisicoInp = document.querySelector('textarea[name="estado_fisico"]');
    const formaPagamentoSel = document.querySelector('select[name="forma_pagamento"]');
    const checklistEntradaData = checklistEntradaDataInput?.value || '';

    return {
        savedAt: new Date().toISOString(),
        cliente_id: clienteSel?.value || '',
        equipamento_id: equipSel?.value || '',
        tecnico_id: tecnicoSel?.value || '',
        prioridade: prioridadeSel?.value || 'normal',
        status: statusSel?.value || 'triagem',
        data_entrada: entradaInp?.value || '',
        data_previsao: previsaoInp?.value || '',
        relato_cliente: relatoInp?.value || '',
        acessorios: acessoriosInp?.value || '',
        acessorios_sem_itens: acessoriosSemItensCheckbox?.checked ? '1' : '0',
        checklist_entrada_data: checklistEntradaData,
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
        data.acessorios?.trim() ||
        data.acessorios_sem_itens === '1' ||
        data.checklist_entrada_data?.trim() ||
        data.estado_fisico?.trim() ||
        data.estado_fisico_sem_avarias === '1' ||
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
    const relatoInp  = document.getElementById('relatoClienteInput') || document.querySelector('textarea[name="relato_cliente"]');
    const acessoriosInp = document.querySelector('textarea[name="acessorios"]');
    const estadoFisicoInp = document.querySelector('textarea[name="estado_fisico"]');
    const formaPagamentoSel = document.querySelector('select[name="forma_pagamento"]');
    pendingChecklistEntradaDraftPayload = parseChecklistDraftPayload(data.checklist_entrada_data || '');
    pendingChecklistEntradaDraftEquipId = String(data.equipamento_id || '');

    if (tecnicoSel) tecnicoSel.value = data.tecnico_id || '';
    if (prioridadeSel) prioridadeSel.value = data.prioridade || 'normal';
    if (statusSel) statusSel.value = data.status || 'triagem';
    if (entradaInp && data.data_entrada) entradaInp.value = data.data_entrada;
    if (previsaoInp) previsaoInp.value = data.data_previsao || '';
    if (relatoInp) relatoInp.value = data.relato_cliente || '';
    if (acessoriosInp) acessoriosInp.value = data.acessorios || '';
    if (estadoFisicoInp) estadoFisicoInp.value = data.estado_fisico || '';
    if (acessoriosSemItensCheckbox) {
        const semItens = String(data.acessorios_sem_itens || '') === '1'
            || String(data.acessorios || '').trim().toLowerCase() === ACCESSORIOS_SEM_ITENS_TEXT.toLowerCase();
        acessoriosSemItensCheckbox.checked = semItens;
        clearAllAcessorios();
        if (!semItens) {
            const draftAcessorios = String(data.acessorios || '').trim();
            if (draftAcessorios) {
                draftAcessorios.split(/\r?\n/).filter(Boolean).forEach(text => {
                    acessoriosEntries.push({ id: generateEntryId(), text, key: 'outro' });
                });
            }
        }
        refreshAcessoriosSemItensUi();
        renderAcessoriosList();
        syncAcessoriosInput();
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
    if (typeof renderDefeitosSelecionadosCard === 'function') {
        renderDefeitosSelecionadosCard();
    }
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
    const btnSubmitOs = document.getElementById('btnSubmitOs');
    const osSubmitLoading = document.getElementById('osSubmitLoading');
    const osTabsContent = document.getElementById('osTabsContent');
    const osFormActions = document.getElementById('osFormActions');

    // Defesa estrutural: garante que o rodape de acoes fique fora do tab-content e dentro do form.
    if (osFormActions) {
        const actionsInsideTabs = Boolean(osTabsContent && osTabsContent.contains(osFormActions));
        const actionsOutsideForm = !formOs.contains(osFormActions);
        const actionsParentIsForm = osFormActions.parentElement === formOs;

        if (actionsInsideTabs || actionsOutsideForm || !actionsParentIsForm) {
            if (osTabsContent && osTabsContent.parentElement === formOs) {
                osTabsContent.insertAdjacentElement('afterend', osFormActions);
            } else {
                formOs.appendChild(osFormActions);
            }
        }
    }

    const osActionControls = Array.from(document.querySelectorAll('#osFormActions .btn, #osFormActions a'));
    const defaultSubmitButtonHtml = btnSubmitOs ? btnSubmitOs.innerHTML : '';
    const submitLoadingText = btnSubmitOs?.dataset.loadingText || 'Salvando...';

    const toggleOsSubmitLoading = (isLoading) => {
        const loading = Boolean(isLoading);
        formOs.dataset.submitting = loading ? '1' : '0';
        formOs.setAttribute('aria-busy', loading ? 'true' : 'false');
        osSubmitLoading?.classList.toggle('show', loading);
        osSubmitLoading?.setAttribute('aria-hidden', loading ? 'false' : 'true');

        osActionControls.forEach((control) => {
            if (!control || control === btnSubmitOs) {
                return;
            }

            if ('disabled' in control) {
                control.disabled = loading;
            }

            if (control.tagName === 'A') {
                control.classList.toggle('disabled', loading);
                control.setAttribute('aria-disabled', loading ? 'true' : 'false');
                if (loading) {
                    control.dataset.originalTabindex = control.getAttribute('tabindex') || '';
                    control.setAttribute('tabindex', '-1');
                } else if (Object.prototype.hasOwnProperty.call(control.dataset, 'originalTabindex')) {
                    const originalTabindex = control.dataset.originalTabindex;
                    if (originalTabindex === '') {
                        control.removeAttribute('tabindex');
                    } else {
                        control.setAttribute('tabindex', originalTabindex);
                    }
                    delete control.dataset.originalTabindex;
                }
            }
        });

        if (!btnSubmitOs) {
            return;
        }

        btnSubmitOs.disabled = loading;
        btnSubmitOs.innerHTML = loading
            ? `<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span><span>${submitLoadingText}</span>`
            : defaultSubmitButtonHtml;
    };

    const finalizeOsSubmit = () => {
        if (formOs.dataset.submitting === '1') {
            return;
        }

        toggleOsSubmitLoading(true);
        formOs.dataset.bypassValidation = '1';
        localStorage.removeItem(DRAFT_KEY);
        _setResumoRascunho('Nao salvo');
        window.setTimeout(() => formOs.submit(), 0);
    };

    window.addEventListener('pageshow', () => {
        if (formOs.dataset.submitting === '1') {
            toggleOsSubmitLoading(false);
            formOs.dataset.bypassValidation = '0';
        }
    });

    formOs.addEventListener('submit', (e) => {
        if (formOs.dataset.submitting === '1' || formOs.dataset.bypassValidation === '1') {
            e.preventDefault();
            return;
        }
        e.preventDefault();
        clearValidationMarks();

        const goToField = (el, tabBtnId) => {
            const tabBtn = document.getElementById(tabBtnId);
            tabBtn?.click();
            el?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            el?.focus({ preventScroll: true });
        };

        const requiredFields = [
            { selector: '#clienteOsSelect', label: 'Cliente', tabBtnId: 'tab-cliente-btn' },
            { selector: '#equipamentoSelect', label: 'Equipamento', tabBtnId: 'tab-equipamento-btn' },
            { selector: 'input[name="data_entrada"]', label: 'Data de Entrada', tabBtnId: 'tab-relato-btn' },
            { selector: '#relatoClienteInput', label: 'Relato do Cliente', tabBtnId: 'tab-defeito-btn' },
        ];

        const optionalChecks = [
            { selector: 'input[name="data_previsao"]', label: 'Previsao de Entrega', tabBtnId: 'tab-relato-btn', isMissing: (el) => !el?.value },
            {
                selector: '#acessoriosSemItens',
                label: 'Acessorios/Componentes',
                tabBtnId: 'tab-equipamento-btn',
                isMissing: () => !isAcessoriosSemItensChecked() && !((acessoriosInput?.value || '').trim())
            },
            {
                selector: '#btnChecklistEntrada',
                label: 'Checklist de Entrada',
                tabBtnId: 'tab-equipamento-btn',
                isMissing: () => isChecklistEntradaRequiredAndMissing()
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
                    goToField(firstFocus, firstTabBtn?.id || 'tab-cliente-btn');
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

            if (isEdit) {
                markWarning(target);
                if (window.Swal && typeof window.Swal.fire === 'function') {
                    Swal.fire({
                        icon: 'info',
                        title: 'Salvando com pendencias opcionais',
                        text: labels + '.',
                        toast: true,
                        position: 'top-end',
                        timer: 2600,
                        showConfirmButton: false,
                        timerProgressBar: true,
                    });
                }
                finalizeOsSubmit();
                return;
            }

            const proceedWithoutOptional = () => finalizeOsSubmit();
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

        finalizeOsSubmit();
    });
}

const prazoEntregaSelect = document.getElementById('prazoEntregaSelect');

function syncPrazoEntregaSelectWithDates() {
    if (!prazoEntregaSelect) {
        return;
    }

    Array.from(prazoEntregaSelect.options).forEach((option) => {
        if (option?.dataset?.dynamicOption === '1') {
            option.remove();
        }
    });

    const entradaVal = document.querySelector('input[name="data_entrada"]')?.value || '';
    const previsaoVal = document.querySelector('input[name="data_previsao"]')?.value || '';
    if (!entradaVal || !previsaoVal) {
        prazoEntregaSelect.value = '';
        return;
    }

    const entradaDate = new Date(entradaVal);
    const previsaoDate = new Date(`${previsaoVal}T00:00:00`);
    if (Number.isNaN(entradaDate.getTime()) || Number.isNaN(previsaoDate.getTime())) {
        prazoEntregaSelect.value = '';
        return;
    }

    entradaDate.setHours(0, 0, 0, 0);
    previsaoDate.setHours(0, 0, 0, 0);

    const prazoDias = Math.round((previsaoDate.getTime() - entradaDate.getTime()) / 86400000);
    if (!Number.isFinite(prazoDias) || prazoDias <= 0) {
        prazoEntregaSelect.value = '';
        return;
    }

    const prazoValue = String(prazoDias);
    const existingOption = Array.from(prazoEntregaSelect.options).find((option) => option.value === prazoValue);
    if (existingOption) {
        prazoEntregaSelect.value = prazoValue;
        return;
    }

    const dynamicOption = new Option(
        prazoDias === 1 ? '1 dia' : `${prazoValue} dias`,
        prazoValue,
        true,
        true
    );
    dynamicOption.dataset.dynamicOption = '1';
    prazoEntregaSelect.add(dynamicOption);
    prazoEntregaSelect.value = prazoValue;
}

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
        syncPrazoEntregaSelectWithDates();
    }
});

// --- Carrega fotos do equipamento ---
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

// --- Select de cliente: carrega equipamentos ---
// Override com renderizacao reativa e anti-cache para fotos do equipamento.
let equipamentoFotosVersion = Date.now();
function bumpEquipamentoFotosVersion() {
    equipamentoFotosVersion = Date.now();
}

function withFotoVersion(url, version = equipamentoFotosVersion) {
    if (!url) return '';
    const value = String(url).trim();
    if (!value) return '';
    if (/^(data:|blob:)/i.test(value)) {
        return value;
    }

    const hashIndex = value.indexOf('#');
    const hash = hashIndex >= 0 ? value.slice(hashIndex) : '';
    const base = hashIndex >= 0 ? value.slice(0, hashIndex) : value;
    const separator = base.includes('?') ? '&' : '?';

    return `${base}${separator}v=${version}${hash}`;
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
        const corNome = equipData.cor || 'Cor nao informada';
        if (colorSwatch) colorSwatch.style.background = corHex;
        if (colorName) colorName.textContent = corNome;
        if (colorInfo) colorInfo.classList.remove('d-none');
    }

    minis.innerHTML = '';
    const lista = Array.isArray(fotos) ? fotos : [];
    if (!lista.length) {
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

    const principal = lista.find(f => Number(f.is_principal) === 1) || lista[0];
    const principalUrl = withFotoVersion(principal.url);
    img.src = principalUrl;
    document.getElementById('fotoPrincipalLink')?.setAttribute('data-img-src', principalUrl);
    mainBox.classList.remove('d-none');
    placeholder.classList.add('d-none');
    placeholder.classList.remove('d-flex');
    placeholder.style.background = 'rgba(255,255,255,0.04)';
    placeholder.style.color = '';

    lista.forEach((foto) => {
        const thumbUrl = withFotoVersion(foto.url);
        const isPrincipal = Number(foto.is_principal) === 1;
        const el = document.createElement('div');
        el.className = 'border rounded overflow-hidden shadow-sm hover-elevate cursor-pointer';
        el.style.cssText = `width:45px;height:45px;cursor:pointer;transition:all 0.2s;border-color:${isPrincipal ? 'var(--primary)' : 'rgba(255,255,255,0.1)'};`;
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
    fetch(`${BASE_URL}equipamentos/fotos/${equipId}?v=${Date.now()}`)
        .then(r => r.json())
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
    renderClienteInfoCard(clienteId);

    // Destroi Select2 do equipamento antes de popular (apenas se estiver inicializado)
    if (typeof $.fn.select2 !== 'undefined' && $('#equipamentoSelect').hasClass("select2-hidden-accessible")) {
        try { $('#equipamentoSelect').select2('destroy'); } catch(e) {}
    }

    equipamentoSelect.innerHTML = '<option value="">Carregando equipamentos...</option>';
    equipamentoSelect.disabled = true;
    hideSidebar();

    if (!clienteId) {
        pendingChecklistEntradaDraftPayload = null;
        pendingChecklistEntradaDraftEquipId = '';
        equipamentoSelect.innerHTML = '<option value="">Selecione o cliente primeiro...</option>';
        equipamentoSelect.disabled = false;
        loadChecklistEntradaMeta('', null);
        if (typeof setEquipamentoEditButtonState === 'function') setEquipamentoEditButtonState();
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
                const nome = (eq.marca_nome || '') + ' ' + (eq.modelo_nome || '') + ' (' + (eq.tipo_nome || eq.tipo || '') + ')';
                const opt  = new Option(nome, eq.id);
                opt.dataset.tipo      = eq.tipo_id || '';
                opt.dataset.marca     = eq.marca_nome || '';
                opt.dataset.modelo    = eq.modelo_nome || '';
                opt.dataset.serie     = eq.numero_serie || '';
                opt.dataset.imei      = eq.imei || '';
                opt.dataset.cor       = eq.cor || '';
                opt.dataset.cor_hex   = eq.cor_hex || '';
                opt.dataset.tipo_nome = eq.tipo_nome || '';
                opt.dataset.marca_id  = eq.marca_id || '';
                opt.dataset.modelo_id = eq.modelo_id || '';
                opt.dataset.cliente_id = eq.cliente_id || '';
                opt.dataset.senha_acesso = eq.senha_acesso || '';
                opt.dataset.estado_fisico = eq.estado_fisico || '';
                opt.dataset.acessorios = eq.acessorios || '';
                opt.dataset.foto_url = eq.foto_url || '';
                equipamentoSelect.appendChild(opt);
            });
        }
        equipamentoSelect.disabled = false;
        const targetId = pendingEquipId || autoSelectId;
        // Re-inicializa Select2 no equipamento
        if (typeof $.fn.select2 !== 'undefined') {
            initEquipamentoSelect2();
            if (targetId) {
                $('#equipamentoSelect').val(String(targetId)).trigger('change');
                pendingEquipId = null;
            }
        } else {
            if (targetId) {
                equipamentoSelect.value = String(targetId);
                _onEquipamentoChange(equipamentoSelect.value, equipamentoSelect.options[equipamentoSelect.selectedIndex]);
                pendingEquipId = null;
            }
        }
        if (!targetId) {
            loadChecklistEntradaMeta('', null);
        }
        if (typeof setEquipamentoEditButtonState === 'function') setEquipamentoEditButtonState();
        updateResumo();
        scheduleDraftSave();
    })
    .catch(() => {
        equipamentoSelect.innerHTML = '<option value="">Erro ao carregar.</option>';
        equipamentoSelect.disabled = false;
        loadChecklistEntradaMeta('', null);
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

// --- Handler de mudança de equipamento ---
function _onEquipamentoChange(id, opt) {
    const tipoId = opt ? opt.getAttribute('data-tipo') : null;
    carregarDefeitos(tipoId);
    loadChecklistEntradaMeta(id, opt);
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
    if (typeof setEquipamentoEditButtonState === 'function') setEquipamentoEditButtonState();
    updateResumo();
    scheduleDraftSave();
}

// --- Listener vanilla do equipamentoSelect (usado quando Select2 ainda não foi inicializado) ---
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
        _onEquipamentoChange(equipSelect.value, opt);
    }
}

// Atualiza resumo e rascunho conforme alterações no formulário
['input', 'change'].forEach(evt => {
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
            return;
        }
        syncPrazoEntregaSelectWithDates();
    });
    document.querySelector('input[name="data_previsao"]')?.addEventListener(evt, () => {
        updateResumo();
        scheduleDraftSave();
        syncPrazoEntregaSelectWithDates();
    });
    document.querySelector('select[name="forma_pagamento"]')?.addEventListener(evt, () => {
        updateResumo();
        scheduleDraftSave();
    });
});

syncPrazoEntregaSelectWithDates();

// --- Preview fotos de entrada ---
const csrfTokenName = '<?= csrf_token() ?>';
let csrfTokenValue = document.querySelector('input[name="<?= csrf_token() ?>"]')?.value || '<?= csrf_hash() ?>';
const currentOsId = <?= (int) ($os['id'] ?? 0) ?>;
const osBudgetSummaryUrl = <?= json_encode(!empty($os['id']) ? base_url('os/orcamento-resumo/' . (int) $os['id']) : '') ?>;
const osFotosDeleteUrlBase = <?= json_encode(base_url('os/fotos-entrada/excluir')) ?>;
const osFotosExistingData = <?= json_encode(array_map(static fn($f) => [
    'id' => (int) ($f['id'] ?? 0),
    'url' => (string) ($f['url'] ?? ''),
], $fotos_entrada ?? []), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
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

function appendCsrfToFormData(formData) {
    if (formData instanceof FormData && csrfTokenName && csrfTokenValue) {
        formData.append(csrfTokenName, csrfTokenValue);
    }
    return formData;
}

function syncCsrfHashFromPayload(payload) {
    const nextHash = typeof payload?.csrfHash === 'string' ? payload.csrfHash.trim() : '';
    if (!nextHash) {
        return;
    }

    csrfTokenValue = nextHash;
    document.querySelectorAll(`input[name="${csrfTokenName}"]`).forEach((input) => {
        input.value = nextHash;
    });
}

function syncFotosEntradaInput() {
    if (fotosEntradaInput) {
        fotosEntradaInput.files = osDataTransfer.files;
    }
}

function toggleFotosEntradaEmptyState() {
    if (!fotosEntradaEmptyState) return;
    const totalPhotos = osFotosExistingData.length + osDataTransfer.files.length;
    fotosEntradaEmptyState.style.display = totalPhotos > 0 ? 'none' : 'block';
}

function queueFotosEntradaFromFiles(files) {
    const incoming = Array.from(files || []).filter(file => file.type?.startsWith('image/'));
    if (!incoming.length) return;

    const disponivel = osFotosMaxFiles - getTotalEntradaFotos();
    if (disponivel <= 0) {
        showWarningDialog(`Voce pode enviar ate ${osFotosMaxFiles} fotos no total.`);
        return;
    }

    fotosEntradaCropQueue = incoming.slice(0, disponivel);
    if (incoming.length > disponivel) {
        showWarningDialog(`Somente ${disponivel} foto(s) cabem agora (limite de ${osFotosMaxFiles}).`);
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
        showWarningDialog(`Cada foto deve ter no maximo ${osFotoMaxSizeMb}MB.`);
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
        const fotoId = Number(foto?.id || 0);
        thumb.className = 'position-relative border rounded overflow-hidden cursor-pointer';
        thumb.style.cssText = 'width:110px; height:110px;';
        thumb.innerHTML = `
            <img src="${foto.url}" class="w-100 h-100 object-fit-cover">
            ${fotoId > 0 ? `
                <button
                    type="button"
                    class="btn btn-sm btn-outline-danger position-absolute top-0 end-0 m-1 btn-remover-foto-existente"
                    data-foto-id="${fotoId}"
                    aria-label="Excluir foto"
                >
                    <i class="bi bi-trash"></i>
                </button>
            ` : ''}
        `;
        thumb.setAttribute('data-bs-toggle', 'modal');
        thumb.setAttribute('data-bs-target', '#imageModal');
        thumb.setAttribute('data-img-src', foto.url);
        osFotosExisting.appendChild(thumb);
    });
}

async function deleteExistingEntradaPhoto(fotoId) {
    if (!fotoId) return;

    let confirmado = false;
    if (window.Swal && typeof window.Swal.fire === 'function') {
        const result = await Swal.fire({
            icon: 'warning',
            title: 'Excluir foto?',
            text: 'Essa foto de entrada sera removida da visualizacao e da pasta de uploads.',
            showCancelButton: true,
            confirmButtonText: 'Sim, excluir',
            cancelButtonText: 'Cancelar',
            reverseButtons: true,
            customClass: { popup: 'glass-card' }
        });
        confirmado = !!result.isConfirmed;
    } else {
        confirmado = window.confirm('Essa foto de entrada sera removida permanentemente. Deseja continuar?');
    }

    if (!confirmado) return;

    const previousFotos = Array.isArray(osFotosExistingData)
        ? osFotosExistingData.map((foto) => ({ ...foto }))
        : [];
    const remainingFotos = previousFotos.filter((foto) => Number(foto?.id || 0) !== Number(fotoId));
    osFotosExistingData.splice(0, osFotosExistingData.length, ...remainingFotos);
    renderExistingFotos();
    updatePhotoState();

    try {
        const formData = appendCsrfToFormData(new FormData());
        const response = await fetch(`${osFotosDeleteUrlBase}/${fotoId}`, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const payload = await response.json();
        syncCsrfHashFromPayload(payload);

        if (!response.ok || !payload || payload.success !== true) {
            throw new Error(payload?.message || 'Nao foi possivel excluir a foto de entrada.');
        }

        const refreshedFotos = Array.isArray(payload.fotos)
            ? payload.fotos.map((foto) => ({
                id: Number(foto?.id || 0),
                url: String(foto?.url || ''),
            }))
            : [];
        osFotosExistingData.splice(0, osFotosExistingData.length, ...refreshedFotos);
        renderExistingFotos();
        updatePhotoState();

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
        osFotosExistingData.splice(0, osFotosExistingData.length, ...previousFotos);
        renderExistingFotos();
        updatePhotoState();
        showWarningDialog(error?.message || 'Nao foi possivel excluir a foto.', 'Falha na exclusao');
    }
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
    toggleFotosEntradaEmptyState();
    updateResumo();
}

function clearNewFotos() {
    osDataTransfer.items.clear();
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
    const remover = event.target.closest('.btn-remover-foto-nova');
    if (!remover) return;
    const index = parseInt(remover.dataset.index, 10);
    const dt = new DataTransfer();
    Array.from(osDataTransfer.files).forEach((file, idx) => {
        if (idx !== index) dt.items.add(file);
    });
    osDataTransfer.items.clear();
    Array.from(dt.files).forEach(f => osDataTransfer.items.add(f));
    syncFotosEntradaInput();
    renderNewFotos();
    updatePhotoState();
    scheduleDraftSave();
});
osFotosExisting?.addEventListener('click', async function(event) {
    const remover = event.target.closest('.btn-remover-foto-existente');
    if (!remover) return;
    event.preventDefault();
    event.stopPropagation();
    const fotoId = parseInt(remover.dataset.fotoId || '', 10);
    if (!Number.isNaN(fotoId)) {
        await deleteExistingEntradaPhoto(fotoId);
    }
});
renderExistingFotos();
renderNewFotos();
updatePhotoState();

const osBudgetSummaryPanel = document.getElementById('osBudgetSummaryPanel');
const osBudgetEditorModalElement = document.getElementById('osBudgetEditorModal');
const osBudgetEditorModalFrame = document.getElementById('osBudgetEditorModalFrame');
const osBudgetEditorModalLoading = document.getElementById('osBudgetEditorModalLoading');
const osBudgetEditorModalTitle = document.getElementById('osBudgetEditorModalTitle');
const osBudgetEditorModalCloseBtn = document.getElementById('osBudgetEditorModalCloseBtn');
const osBudgetEditorModal = osBudgetEditorModalElement
    ? bootstrap.Modal.getOrCreateInstance(osBudgetEditorModalElement)
    : null;
let osBudgetEditorLoadTimeout = null;

function setOsBudgetEditorLoading(isLoading) {
    osBudgetEditorModalLoading?.classList.toggle('d-none', !isLoading);
}

function clearOsBudgetEditorTimeout() {
    if (!osBudgetEditorLoadTimeout) {
        return;
    }
    window.clearTimeout(osBudgetEditorLoadTimeout);
    osBudgetEditorLoadTimeout = null;
}

function openOsBudgetEditorModal(url, title) {
    if (!url || !osBudgetEditorModalFrame || !osBudgetEditorModal) {
        return;
    }

    clearOsBudgetEditorTimeout();
    setOsBudgetEditorLoading(true);
    if (osBudgetEditorModalTitle) {
        osBudgetEditorModalTitle.textContent = title || 'Orcamento da OS';
    }
    osBudgetEditorModalFrame.src = 'about:blank';
    osBudgetEditorModal.show();
    osBudgetEditorModalFrame.src = url;
    osBudgetEditorLoadTimeout = window.setTimeout(() => {
        setOsBudgetEditorLoading(false);
    }, 12000);
}

async function confirmCloseBudgetEditorModal() {
    if (window.Swal && typeof window.Swal.fire === 'function') {
        const result = await Swal.fire({
            icon: 'warning',
            title: 'Fechar orcamento?',
            text: 'Se houver alteracoes nao salvas no modal do orcamento, elas serao perdidas.',
            showCancelButton: true,
            confirmButtonText: 'Fechar mesmo assim',
            cancelButtonText: 'Continuar editando',
            reverseButtons: true,
            customClass: { popup: 'glass-card' }
        });
        return !!result.isConfirmed;
    }

    return window.confirm('Existem alteracoes nao salvas no orcamento. Deseja fechar mesmo assim?');
}

async function refreshOsBudgetSummary(options = {}) {
    if (!osBudgetSummaryPanel || !osBudgetSummaryUrl) {
        return;
    }

    osBudgetSummaryPanel.classList.add('opacity-50');
    try {
        const response = await fetch(osBudgetSummaryUrl, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const payload = await response.json();
        if (!response.ok || !payload || payload.ok !== true) {
            throw new Error(payload?.message || 'Nao foi possivel atualizar o resumo do orcamento.');
        }

        osBudgetSummaryPanel.innerHTML = payload.html || '';

        if (options.successMessage && window.Swal && typeof window.Swal.fire === 'function') {
            Swal.fire({
                icon: 'success',
                title: 'Orcamento atualizado',
                text: options.successMessage,
                timer: 1800,
                showConfirmButton: false,
                customClass: { popup: 'glass-card' }
            });
        }
    } finally {
        osBudgetSummaryPanel.classList.remove('opacity-50');
    }
}

document.addEventListener('click', function(event) {
    const modalTrigger = event.target.closest('[data-os-orcamento-modal-url]');
    if (!modalTrigger) {
        return;
    }

    event.preventDefault();
    openOsBudgetEditorModal(
        modalTrigger.getAttribute('data-os-orcamento-modal-url') || '',
        modalTrigger.getAttribute('data-os-orcamento-modal-title') || 'Orcamento da OS'
    );
});

osBudgetEditorModalFrame?.addEventListener('load', () => {
    clearOsBudgetEditorTimeout();
    setOsBudgetEditorLoading(false);
});

osBudgetEditorModalCloseBtn?.addEventListener('click', async (event) => {
    event.preventDefault();
    event.stopPropagation();

    const confirmed = await confirmCloseBudgetEditorModal();
    if (!confirmed) {
        return;
    }

    osBudgetEditorModal?.hide();
});

osBudgetEditorModalElement?.addEventListener('hidden.bs.modal', () => {
    clearOsBudgetEditorTimeout();
    setOsBudgetEditorLoading(false);
    if (osBudgetEditorModalFrame) {
        osBudgetEditorModalFrame.src = 'about:blank';
    }
});

window.addEventListener('message', async function(event) {
    if (event.origin !== window.location.origin) {
        return;
    }

    const payload = event.data || {};
    if (payload.type !== 'os:orcamento-updated') {
        return;
    }
    if (currentOsId > 0 && Number(payload.osId || 0) !== currentOsId) {
        return;
    }

    try {
        await refreshOsBudgetSummary({
            successMessage: String(payload.message || '').trim(),
        });
        osBudgetEditorModal?.hide();
    } catch (error) {
        showWarningDialog(error?.message || 'Nao foi possivel atualizar o resumo do orcamento.', 'Falha ao sincronizar');
    }
});

// --- Modal: Cadastrar Novo Equipamento ---
const osEquipamentosCache = window._osEquipamentosCache || (window._osEquipamentosCache = {});
const btnNovoEquip = document.getElementById('btnNovoEquipamento');
var btnEditarEquip = document.getElementById('btnEditarEquipamento');
var btnEditarEquipInline = document.getElementById('btnEditarEquipamentoInline');
const modalNovoEquipamentoEl = document.getElementById('modalNovoEquipamento');
const modalNovoEquipamento = modalNovoEquipamentoEl ? new bootstrap.Modal(modalNovoEquipamentoEl) : null;
const formNovoEquipAjax = document.getElementById('formNovoEquipAjax');
const novoEquipSenhaController = typeof window.initPatternPasswordField === 'function'
    ? window.initPatternPasswordField({ root: '#novoEquipSenhaBoxOS', defaultMode: 'desenho' })
    : null;
const labelModalNovoEquip = document.getElementById('labelModalNovoEquip');
const btnSalvarNovoEquip = document.getElementById('btnSalvarNovoEquip');
const modalEquipFotosExistentesWrap = document.getElementById('modalEquipFotosExistentesWrap');
const modalEquipFotosExistentes = document.getElementById('modalEquipFotosExistentes');
const novoEquipFotosNovasList = document.getElementById('novoEquipFotosNovasList');
let equipamentoModalMode = 'create';
let equipamentoEditId = null;
let modalEquipExistingFotos = [];
let modalEquipFotosVersion = Date.now();
const novoEquipFotosMaxFiles = 4;
const novoEquipFotosDataTransfer = new DataTransfer();
let novoEquipFotoCropQueue = [];

function bumpModalEquipFotosVersion() {
    modalEquipFotosVersion = Date.now();
}

function getTopModalStackZIndex(defaultZ = 2600) {
    const modalEls = Array.from(document.querySelectorAll('.modal.show'));
    const backdropEls = Array.from(document.querySelectorAll('.modal-backdrop.show, .modal-backdrop'));
    const zIndexValues = [...modalEls, ...backdropEls].map((el) => {
        const styleValue = window.getComputedStyle(el)?.zIndex || el.style?.zIndex || '';
        const parsed = Number.parseInt(String(styleValue), 10);
        return Number.isFinite(parsed) ? parsed : 0;
    });
    const maxActive = zIndexValues.length ? Math.max(...zIndexValues) : 0;
    return Math.max(defaultZ, maxActive + 40);
}

function showWarningDialog(message, title = 'Atenção') {
    if (window.Swal && typeof window.Swal.fire === 'function') {
        const dynamicZIndex = getTopModalStackZIndex(2600);
        Swal.fire({
            icon: 'warning',
            title,
            text: message,
            confirmButtonText: 'OK',
            customClass: { popup: 'glass-card' },
            zIndex: dynamicZIndex,
            didOpen: () => {
                const containers = Array.from(document.querySelectorAll('.swal2-container'));
                const container = containers[containers.length - 1] || null;
                if (container) {
                    container.style.zIndex = String(dynamicZIndex);
                }
            }
        });
        return;
    }
    alert(message);
}

function ensureModalEquipSelect2() {
    $('.select2-modal').select2({
        theme: 'bootstrap-5',
        dropdownParent: $('#modalNovoEquipamento'),
        width: '100%',
        placeholder: 'Escolha...'
    });
}

function ensureNovoEquipClienteInput(clienteId) {
    if (!formNovoEquipAjax) return;
    let hiddenInput = document.getElementById('novoEquipClienteId');
    if (!hiddenInput) {
        hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'cliente_id';
        hiddenInput.id = 'novoEquipClienteId';
        formNovoEquipAjax.appendChild(hiddenInput);
    }
    hiddenInput.value = clienteId || '';
}

function setEquipamentoEditButtonState() {
    const equipId = document.getElementById('equipamentoSelect')?.value || '';
    const hasEquipamento = Boolean(String(equipId).trim());
    const shouldHide = !hasEquipamento;
    if (btnEditarEquip) btnEditarEquip.classList.toggle('d-none', shouldHide);
    if (btnEditarEquipInline) btnEditarEquipInline.classList.toggle('d-none', shouldHide);
}

function syncNovoEquipFotosInput() {
    if (!novoEquipFoto) return;
    novoEquipFoto.files = novoEquipFotosDataTransfer.files;
}

function resetNovoEquipPreview() {
    novoEquipFotosDataTransfer.items.clear();
    novoEquipFotoCropQueue = [];
    if (novoEquipFoto) {
        novoEquipFoto.value = '';
        syncNovoEquipFotosInput();
    }
    renderNovoEquipFotosNovas();
    const fotoVazia = document.getElementById('fotoVaziaOS');
    if (fotoVazia) fotoVazia.style.display = (modalEquipExistingFotos.length || novoEquipFotosDataTransfer.files.length) ? 'none' : 'block';
}

function getTotalModalEquipFotos() {
    return (modalEquipExistingFotos?.length || 0) + (novoEquipFotosDataTransfer?.files?.length || 0);
}

function showNovoEquipModalTab(tabKey) {
    const tabMap = {
        info: 'm-info-tab',
        cor: 'm-cor-tab',
        foto: 'm-foto-tab',
        fotos: 'm-foto-tab',
    };

    const tabBtn = document.getElementById(tabMap[tabKey] || tabMap.info);
    if (!tabBtn || typeof bootstrap === 'undefined' || !bootstrap.Tab) {
        return;
    }

    bootstrap.Tab.getOrCreateInstance(tabBtn).show();
}

function clearNovoEquipModalValidationState() {
    const errors = document.getElementById('modalEquipErrors');
    if (errors) {
        errors.classList.add('d-none');
        errors.innerHTML = '';
    }

    document.getElementById('novoEquipTipo')?.classList.remove('is-invalid');
    document.getElementById('novoEquipModelo')?.classList.remove('is-invalid');
    $('#novoEquipMarca').removeClass('is-invalid');
    $('#novoEquipMarca').next('.select2-container').find('.select2-selection').removeClass('border-danger', 'border-2');
    $('#novoEquipModelo').next('.select2-container').find('.select2-selection').removeClass('border-danger', 'border-2');
    document.getElementById('colorPreviewBoxOS')?.classList.remove('border-danger', 'border-2');
    document.getElementById('corNomeInputOS')?.classList.remove('is-invalid');
    document.getElementById('fotoVaziaOS')?.classList.remove('border', 'border-danger', 'border-2', 'rounded-3');
    document.getElementById('btnAbrirGaleria')?.classList.remove('btn-danger', 'text-white');
    document.getElementById('btnAbrirCamera')?.classList.remove('btn-danger', 'text-white');
}

function markNovoEquipPendingState(tabKey) {
    if (tabKey === 'info') {
        const tipoField = document.getElementById('novoEquipTipo');
        const marcaField = document.getElementById('novoEquipMarca');
        const modeloField = document.getElementById('novoEquipModelo');

        if (!String(tipoField?.value || '').trim()) {
            tipoField?.classList.add('is-invalid');
        }
        if (!String(marcaField?.value || '').trim()) {
            $('#novoEquipMarca').next('.select2-container').find('.select2-selection').addClass('border-danger', 'border-2');
        }
        if (!String(modeloField?.value || '').trim()) {
            if (modeloField?.classList.contains('select2-hidden-accessible')) {
                $('#novoEquipModelo').next('.select2-container').find('.select2-selection').addClass('border-danger', 'border-2');
            } else {
                modeloField?.classList.add('is-invalid');
            }
        }
        return;
    }

    if (tabKey === 'cor') {
        document.getElementById('colorPreviewBoxOS')?.classList.add('border-danger', 'border-2');
        document.getElementById('corNomeInputOS')?.classList.add('is-invalid');
        return;
    }

    if (tabKey === 'foto' || tabKey === 'fotos') {
        document.getElementById('fotoVaziaOS')?.classList.add('border', 'border-danger', 'border-2', 'rounded-3');
        document.getElementById('btnAbrirGaleria')?.classList.add('btn-danger', 'text-white');
        document.getElementById('btnAbrirCamera')?.classList.add('btn-danger', 'text-white');
    }
}

function focusNovoEquipPendingField(tabKey) {
    const focusMap = {
        info: '#novoEquipTipo',
        cor: '#corNomeInputOS',
        foto: '#btnAbrirGaleria',
        fotos: '#btnAbrirGaleria',
    };

    const selector = focusMap[tabKey];
    if (!selector) {
        return;
    }

    window.setTimeout(() => {
        const element = document.querySelector(selector);
        if (element && typeof element.focus === 'function') {
            element.focus({ preventScroll: false });
        }
    }, 180);
}

function normalizeNovoEquipErrorText(message) {
    return String(message || '')
        .replace(/<br\s*\/?>/gi, '\n')
        .replace(/<[^>]+>/g, '')
        .trim();
}

function handleNovoEquipValidationFeedback(message, tabKey = 'info', showDialog = true) {
    const errors = document.getElementById('modalEquipErrors');
    if (errors && message) {
        errors.innerHTML = message;
        errors.classList.remove('d-none');
    }

    showNovoEquipModalTab(tabKey);
    markNovoEquipPendingState(tabKey);
    focusNovoEquipPendingField(tabKey);

    if (showDialog) {
        showWarningDialog(normalizeNovoEquipErrorText(message) || 'Existe uma pendencia obrigatoria no cadastro do equipamento.', 'Pendencia obrigatoria');
    }
}

function validateNovoEquipRequiredFields() {
    clearNovoEquipModalValidationState();

    const tipoId = String(document.getElementById('novoEquipTipo')?.value || '').trim();
    if (!tipoId) {
        handleNovoEquipValidationFeedback('Selecione o tipo do equipamento antes de salvar.', 'info');
        return false;
    }

    const marcaId = String($('#novoEquipMarca').val() || '').trim();
    if (!marcaId) {
        handleNovoEquipValidationFeedback('Selecione a marca do equipamento antes de salvar.', 'info');
        return false;
    }

    const modeloId = String($('#novoEquipModelo').val() || '').trim();
    if (!modeloId) {
        handleNovoEquipValidationFeedback('Selecione o modelo do equipamento antes de salvar.', 'info');
        return false;
    }

    const corHex = String(document.getElementById('corHexRealOS')?.value || '').trim();
    const corNome = String(document.getElementById('corNomeRealOS')?.value || '').trim();
    if (!corHex || !corNome) {
        handleNovoEquipValidationFeedback('Informe a cor correta do equipamento antes de salvar.', 'cor');
        return false;
    }

    if (getTotalModalEquipFotos() <= 0) {
        handleNovoEquipValidationFeedback('Adicione ao menos uma foto do equipamento antes de salvar.', 'foto');
        return false;
    }

    return true;
}

function renderNovoEquipFotosNovas() {
    if (!previewDiv || !novoEquipFotosNovasList) return;
    const files = Array.from(novoEquipFotosDataTransfer.files || []);
    novoEquipFotosNovasList.innerHTML = '';

    if (!files.length) {
        previewDiv.style.display = 'none';
        const fotoVazia = document.getElementById('fotoVaziaOS');
        if (fotoVazia) fotoVazia.style.display = modalEquipExistingFotos.length ? 'none' : 'block';
        return;
    }

    previewDiv.style.display = 'block';
    const fotoVazia = document.getElementById('fotoVaziaOS');
    if (fotoVazia) fotoVazia.style.display = 'none';
    if (getTotalModalEquipFotos() > 0) {
        document.getElementById('fotoVaziaOS')?.classList.remove('border', 'border-danger', 'border-2', 'rounded-3');
        document.getElementById('btnAbrirGaleria')?.classList.remove('btn-danger', 'text-white');
        document.getElementById('btnAbrirCamera')?.classList.remove('btn-danger', 'text-white');
    }

    files.forEach((file, index) => {
        const objectUrl = URL.createObjectURL(file);
        const isPrincipal = index === 0 && !modalEquipExistingFotos.some(f => Number(f.is_principal) === 1);
        const thumb = document.createElement('div');
        thumb.className = 'position-relative d-inline-block shadow rounded border p-1 bg-white';
        thumb.style.cssText = `width:96px;height:96px;border-color:${isPrincipal ? 'var(--primary)' : 'rgba(0,0,0,.1)'};`;
        thumb.innerHTML = `
            <img src="${objectUrl}" class="w-100 h-100" style="object-fit:cover; border-radius:4px;" alt="Nova foto do equipamento">
            ${isPrincipal ? '<span class="badge text-bg-primary position-absolute top-0 start-0 m-1" style="font-size:0.55rem;">Principal</span>' : ''}
            <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1 p-1 py-0 shadow btn-remover-foto-nova-equip" data-index="${index}" style="border-radius:50%;">
                <i class="bi bi-x"></i>
            </button>
        `;
        const img = thumb.querySelector('img');
        img?.addEventListener('load', () => URL.revokeObjectURL(objectUrl), { once: true });
        novoEquipFotosNovasList.appendChild(thumb);
    });
}

function processNextNovoEquipCrop() {
    if (!novoEquipFotoCropQueue.length) {
        hideModalSafe(modalCrop, '#modalCropEquip');
        return;
    }
    const nextFile = novoEquipFotoCropQueue.shift();
    const reader = new FileReader();
    reader.onload = e => openCropper(e.target.result, { type: 'equipamento' });
    reader.onerror = () => processNextNovoEquipCrop();
    reader.readAsDataURL(nextFile);
}

function queueNovoEquipFotosFromFiles(files) {
    const incoming = Array.from(files || []).filter(file => (file.type || '').startsWith('image/'));
    if (!incoming.length) return;

    const available = novoEquipFotosMaxFiles - getTotalModalEquipFotos();
    if (available <= 0) {
        showWarningDialog(`Voce pode manter ate ${novoEquipFotosMaxFiles} fotos por equipamento.`);
        return;
    }

    novoEquipFotoCropQueue = incoming.slice(0, available);
    if (incoming.length > available) {
        showWarningDialog(`Somente ${available} foto(s) cabem agora (limite de ${novoEquipFotosMaxFiles} por equipamento).`);
    }

    processNextNovoEquipCrop();
}

function renderModalEquipFotosExistentes(fotos = []) {
    if (!modalEquipFotosExistentesWrap || !modalEquipFotosExistentes) return;

    modalEquipFotosExistentes.innerHTML = '';
    const lista = Array.isArray(fotos) ? fotos : [];
    modalEquipExistingFotos = lista;
    if ((lista.length || novoEquipFotosDataTransfer.files.length) > 0) {
        document.getElementById('fotoVaziaOS')?.classList.remove('border', 'border-danger', 'border-2', 'rounded-3');
        document.getElementById('btnAbrirGaleria')?.classList.remove('btn-danger', 'text-white');
        document.getElementById('btnAbrirCamera')?.classList.remove('btn-danger', 'text-white');
    }
    if (equipamentoModalMode !== 'edit' || !lista.length) {
        modalEquipFotosExistentesWrap.classList.add('d-none');
        return;
    }

    modalEquipFotosExistentesWrap.classList.remove('d-none');
    lista.forEach((foto, index) => {
        const fotoUrl = withFotoVersion(foto.url || '', modalEquipFotosVersion);
        const isPrincipal = Number(foto.is_principal) === 1 || index === 0;
        const wrapper = document.createElement('div');
        wrapper.className = 'position-relative border rounded overflow-hidden';
        wrapper.style.cssText = `width:84px;height:84px;border-color:${isPrincipal ? 'var(--primary)' : 'rgba(255,255,255,0.15)'};`;

        const thumb = document.createElement('a');
        thumb.href = 'javascript:void(0)';
        thumb.className = 'd-block w-100 h-100';
        thumb.style.cssText = 'cursor:zoom-in;';
        thumb.setAttribute('data-bs-toggle', 'modal');
        thumb.setAttribute('data-bs-target', '#imageModal');
        thumb.setAttribute('data-img-src', fotoUrl);

        thumb.innerHTML = `
            <img src="${fotoUrl}" class="w-100 h-100 object-fit-cover" alt="Foto do equipamento">
            ${isPrincipal ? '<span class="badge text-bg-primary position-absolute top-0 start-0 m-1" style="font-size:0.55rem;">Principal</span>' : ''}
        `;

        wrapper.appendChild(thumb);

        const fotoId = Number(foto.id || 0);
        if (fotoId > 0) {
            if (!isPrincipal) {
                const btnPrincipal = document.createElement('button');
                btnPrincipal.type = 'button';
                btnPrincipal.className = 'btn btn-sm btn-primary position-absolute bottom-0 end-0 m-1 py-0 px-1 btn-definir-principal-foto-existente-equip';
                btnPrincipal.dataset.fotoId = String(fotoId);
                btnPrincipal.title = 'Definir como principal';
                btnPrincipal.innerHTML = '<i class="bi bi-star"></i>';
                wrapper.appendChild(btnPrincipal);
            }

            const btnDelete = document.createElement('button');
            btnDelete.type = 'button';
            btnDelete.className = 'btn btn-sm btn-danger position-absolute top-0 end-0 m-1 py-0 px-1 btn-remover-foto-existente-equip';
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
        const response = await fetch(`${BASE_URL}equipamentos/fotos/${equipamentoEditId}?v=${Date.now()}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const fotos = await response.json();
        bumpModalEquipFotosVersion();
        bumpEquipamentoFotosVersion();
        renderModalEquipFotosExistentes(fotos);
        renderNovoEquipFotosNovas();

        const selectedEq = getSelectedEquipamentoData();
        if (selectedEq && String(selectedEq.id || '') === String(equipamentoEditId)) {
            carregarFotosEquipamento(equipamentoEditId, {
                marca: selectedEq.marca_nome || selectedEq.marca || '',
                modelo: selectedEq.modelo_nome || selectedEq.modelo || '',
                serie: selectedEq.numero_serie || selectedEq.serie || '',
                tipo: selectedEq.tipo_nome || selectedEq.tipo || '',
                cor: selectedEq.cor || '',
                cor_hex: selectedEq.cor_hex || ''
            }, fotos);
        }
    } catch (_) {
        showWarningDialog('Nao foi possivel atualizar a lista de fotos do equipamento.', 'Falha ao atualizar');
    }
}

function setNovoEquipModalMode(mode) {
    equipamentoModalMode = mode === 'edit' ? 'edit' : 'create';
    if (equipamentoModalMode === 'edit') {
        if (labelModalNovoEquip) {
            labelModalNovoEquip.innerHTML = '<i class="bi bi-pencil-square text-primary me-2"></i>Editar Equipamento';
        }
        if (btnSalvarNovoEquip) {
        btnSalvarNovoEquip.innerHTML = '<i class="bi bi-check2-circle me-1"></i>Salvar Alterações';
        }
        return;
    }
    equipamentoEditId = null;
    if (labelModalNovoEquip) {
        labelModalNovoEquip.innerHTML = '<i class="bi bi-plus-circle text-warning me-2"></i>Cadastrar Novo Equipamento';
    }
    if (btnSalvarNovoEquip) {
        btnSalvarNovoEquip.innerHTML = '<i class="bi bi-check-lg me-1"></i>Cadastrar Equipamento';
    }
    renderModalEquipFotosExistentes([]);
}
setEquipamentoEditButtonState();

function resetNovoEquipModalForm() {
    if (!formNovoEquipAjax) return;
    formNovoEquipAjax.reset();
    showNovoEquipModalTab('info');
    $('#novoEquipModeloNomeExt').val('');
    $('#novoEquipModelo').html('<option value="">Modelo...</option>');
    $('#novoEquipMarca').val('').trigger('change');
    $('#novoEquipTipo').val('');
    novoEquipSenhaController?.clear();
    updateColorUIOS('', '');
    resetNovoEquipPreview();
    renderModalEquipFotosExistentes([]);
    clearNovoEquipModalValidationState();
}

function fillNovoEquipModalFromData(eq) {
    if (!eq || !formNovoEquipAjax) return;
    const clienteAtual = document.getElementById('clienteOsSelect')?.value || '';
    ensureNovoEquipClienteInput(eq.cliente_id || clienteAtual);

    $('#novoEquipTipo').val(eq.tipo_id ? String(eq.tipo_id) : '');
    $('#novoEquipMarca').val(eq.marca_id ? String(eq.marca_id) : '').trigger('change');
    initModeloSelect2();

    setTimeout(() => {
        const modeloSelect = $('#novoEquipModelo');
        const modeloId = eq.modelo_id ? String(eq.modelo_id) : '';
        const modeloNome = eq.modelo_nome || eq.modelo || '';
        if (modeloId) {
            if (!modeloSelect.find(`option[value="${modeloId}"]`).length) {
                modeloSelect.append(new Option(modeloNome || 'Modelo', modeloId, false, false));
            }
            modeloSelect.val(modeloId).trigger('change');
        } else if (modeloNome) {
            modeloSelect.val(modeloNome).trigger('change');
        }
    }, 120);

    const numeroSerie = formNovoEquipAjax.querySelector('input[name="numero_serie"]');
    const estadoFisico = formNovoEquipAjax.querySelector('textarea[name="estado_fisico"]');
    const acessoriosEquip = formNovoEquipAjax.querySelector('textarea[name="acessorios"]');

    if (numeroSerie) numeroSerie.value = eq.numero_serie || '';
    novoEquipSenhaController?.setValue(eq.senha_acesso || '');
    if (estadoFisico) estadoFisico.value = eq.estado_fisico || '';
    if (acessoriosEquip) acessoriosEquip.value = eq.acessorios || '';

    updateColorUIOS(eq.cor_hex || '#1A1A1A', eq.cor || 'Preto');
    resetNovoEquipPreview();

    fetch(`${BASE_URL}equipamentos/fotos/${eq.id}?v=${Date.now()}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(fotos => {
        bumpModalEquipFotosVersion();
        renderModalEquipFotosExistentes(fotos);
        renderNovoEquipFotosNovas();
        if (!Array.isArray(fotos) || !fotos.length) return;
        const principal = fotos.find(f => Number(f.is_principal) === 1) || fotos[0];
        if (!principal?.url) return;
        const fotoVazia = document.getElementById('fotoVaziaOS');
        if (fotoVazia) fotoVazia.style.display = 'none';
    })
    .catch(() => {
        renderModalEquipFotosExistentes([]);
        renderNovoEquipFotosNovas();
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
        marca_nome: opt.dataset.marca || '',
        modelo_nome: opt.dataset.modelo || '',
        tipo_nome: opt.dataset.tipo_nome || '',
        numero_serie: opt.dataset.serie || '',
        imei: opt.dataset.imei || '',
        cor: opt.dataset.cor || '',
        cor_hex: opt.dataset.cor_hex || '',
        foto_url: opt.dataset.foto_url || '',
        senha_acesso: opt.dataset.senha_acesso || '',
        estado_fisico: opt.dataset.estado_fisico || '',
        acessorios: opt.dataset.acessorios || ''
    };
}

function openNovoEquipamentoModal() {
    const clienteId = document.getElementById('clienteOsSelect')?.value || '';
    if (!clienteId) {
        showWarningDialog('Selecione um cliente primeiro para cadastrar o equipamento.');
        return;
    }
    setNovoEquipModalMode('create');
    resetNovoEquipModalForm();
    ensureNovoEquipClienteInput(clienteId);
    ensureModalEquipSelect2();
    initModeloSelect2();
    showNovoEquipModalTab('info');
    modalNovoEquipamento?.show();
}

function openEditarEquipamentoModal() {
    const selectedEq = getSelectedEquipamentoData();
    if (!selectedEq || !selectedEq.id) {
        showWarningDialog('Selecione um equipamento para editar.');
        return;
    }
    equipamentoEditId = selectedEq.id;
    setNovoEquipModalMode('edit');
    resetNovoEquipModalForm();
    ensureModalEquipSelect2();
    initModeloSelect2();
    fillNovoEquipModalFromData(selectedEq);
    showNovoEquipModalTab('info');
    modalNovoEquipamento?.show();
}

btnNovoEquip?.addEventListener('click', function(event) {
    event.preventDefault();
    event.stopPropagation();
    openNovoEquipamentoModal();
});
btnEditarEquip?.addEventListener('click', openEditarEquipamentoModal);
btnEditarEquipInline?.addEventListener('click', openEditarEquipamentoModal);
modalNovoEquipamentoEl?.addEventListener('hidden.bs.modal', () => {
    setNovoEquipModalMode('create');
    resetNovoEquipModalForm();
});

// --- Cadastro Rápido de Marcas e Modelos (Dentro da OS) ---
const modalNovaMarca = new bootstrap.Modal(document.getElementById('modalNovaMarcaOS'));
const modalNovoModelo = new bootstrap.Modal(document.getElementById('modalNovoModeloOS'));

document.getElementById('btnNovaMarcaOS')?.addEventListener('click', () => modalNovaMarca.show());
document.getElementById('btnNovoModeloOS')?.addEventListener('click', () => {
    const marcaId = $('#novoEquipMarca').val();
    if (!marcaId) { showWarningDialog('Selecione uma marca primeiro!'); return; }
    
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

// --- Autocomplete inteligente no modal "Novo Modelo" ---
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
            const icon = group.text.includes('Cadastrados') ? '✓' : '•';
            header.textContent = icon + ' ' + group.text.replace(/^[✓•]\s+/, '');
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
// ===========================================================
// SELETOR DE COR PROFISSIONAL (OS Modal)
// ===========================================================

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

function normalizeHexColorOS(hex) {
    const value = String(hex || '').trim().toUpperCase();
    return /^#[0-9A-F]{6}$/.test(value) ? value : '';
}

window.updateColorUIOS = function(hex, name) {
    const normalizedHex = normalizeHexColorOS(hex);
    const isEmpty = normalizedHex === '';
    const safeHex = isEmpty ? '#E9ECEF' : normalizedHex;
    const safeName = isEmpty ? 'Cor n?o selecionada' : name;
    
    const rgb = hexToRgbOS(safeHex);
    const rgbStr = rgb ? `${rgb.r},${rgb.g},${rgb.b}` : '';
    const textColor = isEmpty ? '#6c757d' : getTextColorOS(safeHex);

    $('#corHexRealOS').val(normalizedHex);
    $('#corRgbRealOS').val(normalizedHex ? rgbStr : '');
    $('#corNomeRealOS').val(name || '');

    $('#corHexPickerOS').val(isEmpty ? '#FFFFFF' : normalizedHex);
    $('#corNomeInputOS').val(name || '');

    const preview = document.getElementById('colorPreviewBoxOS');
    if (preview) {
        preview.style.background = isEmpty ? 'rgba(0,0,0,0.05)' : safeHex;
        const hexDisplay = document.getElementById('colorPreviewHexOS');
        const nameDisplay = document.getElementById('colorPreviewNameOS');
        
        if (hexDisplay) {
            hexDisplay.style.color = textColor;
            hexDisplay.textContent = isEmpty ? '---' : safeHex.toUpperCase();
        }
        if (nameDisplay) {
            nameDisplay.style.color = isEmpty ? '#6c757d' : (textColor === '#ffffff' ? 'rgba(255,255,255,0.7)' : 'rgba(0,0,0,0.5)');
            nameDisplay.textContent = safeName;
        }
    }

    if (!isEmpty) {
        document.getElementById('colorPreviewBoxOS')?.classList.remove('border-danger', 'border-2');
        document.getElementById('corNomeInputOS')?.classList.remove('is-invalid');
    }

    // Similar colors
    let all = [];
    PROFESSIONAL_COLORS_OS.forEach(cat => cat.colors.forEach(c => all.push({ ...c, d: colorDistanceOS(safeHex, c.hex) })));
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

// --- LÓGICA DE DETECÇÃO DE COR INTELIGENTE NA IMAGEM (OS Modal) ---
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

// --- LÓGICA DE ACESSÓRIOS (MODAL OS) ---
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

// --- Lógica de Câmera, Galeria e Cropper ---
const modalCameraEl  = document.getElementById('modalCamera');
const modalCropEl    = document.getElementById('modalCropEquip');
const CHECKLIST_MODAL_Z_INDEX = 2150;
const CAMERA_MODAL_Z_INDEX = 2250;
const CROP_MODAL_Z_INDEX = 2350;
const DEFEITOS_MODAL_BASE_Z_INDEX = 2100;

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

hoistModalToBody(modalCameraEl, CAMERA_MODAL_Z_INDEX);
hoistModalToBody(modalCropEl, CROP_MODAL_Z_INDEX);
hoistModalToBody(checklistEntradaModalEl, CHECKLIST_MODAL_Z_INDEX);

const modalCamera    = modalCameraEl ? bootstrap.Modal.getOrCreateInstance(modalCameraEl) : null;
const modalCrop      = modalCropEl ? bootstrap.Modal.getOrCreateInstance(modalCropEl) : null;
if (checklistEntradaModalEl) {
    checklistEntradaModal = bootstrap.Modal.getOrCreateInstance(checklistEntradaModalEl);
}
const modalCropTitle = document.getElementById('modalCropTitle');
const videoCamera    = document.getElementById('videoCamera');
const canvasCamera   = document.getElementById('canvasCamera');
const btnCapturar     = document.getElementById('btnCapturar');
const novoEquipFoto  = document.getElementById('novoEquipFoto');
const previewDiv     = document.getElementById('novoEquipFotoPreview');
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

function resetModalNodeState(modalEl) {
    if (!modalEl) return;
    modalEl.classList.remove('show');
    modalEl.style.display = 'none';
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
        console.error('[OS Nova] falha ao ocultar imageModal', err);
    }

    imageModalEl.classList.remove('show');
    imageModalEl.style.display = 'none';
    imageModalEl.setAttribute('aria-hidden', 'true');
    imageModalEl.removeAttribute('aria-modal');
    document.body.classList.remove('modal-open');
    document.body.style.removeProperty('padding-right');
    document.body.style.removeProperty('overflow');
    document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
    scheduleModalCleanup();
}

document.addEventListener('hidden.bs.modal', scheduleModalCleanup);

document.getElementById('btnAbrirGaleria')?.addEventListener('click', () => novoEquipFoto.click());

async function openCameraCapture(context = { type: 'equipamento', entryId: null }) {
    closeImageModalIfOpen();
    cameraCaptureContext = context;
    try {
        if (!navigator.mediaDevices?.getUserMedia) {
            console.error('[OS Nova] navigator.mediaDevices.getUserMedia indisponivel');
            showWarningDialog('Este dispositivo ou navegador nao permite acesso a camera.', 'Camera indisponivel');
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
                playPromise.catch(err => console.error('[OS Nova] falha ao iniciar preview da camera', err));
            }
        }

        hoistModalToBody(modalCameraEl, CAMERA_MODAL_Z_INDEX);
        if (modalCameraEl) {
            modalCameraEl.style.zIndex = String(CAMERA_MODAL_Z_INDEX);
        }
        resetModalNodeState(modalCameraEl);
        try {
            bootstrap.Modal.getInstance(modalCameraEl)?.dispose();
        } catch (error) {
            console.error('[OS Nova] falha ao descartar instancia anterior do modal da camera', error);
        }

        const cameraModalInstance = modalCameraEl ? new bootstrap.Modal(modalCameraEl) : null;
        cameraModalInstance?.show();
        window.setTimeout(() => elevateLatestBackdrop(CAMERA_MODAL_Z_INDEX - 10), 80);

        window.setTimeout(() => {
            if (!modalCameraEl) return;
            if (modalCameraEl.classList.contains('show') && window.getComputedStyle(modalCameraEl).display !== 'none') {
                return;
            }
            console.error('[OS Nova] modal da camera nao abriu corretamente', {
                context,
                display: modalCameraEl.style.display,
                computedDisplay: window.getComputedStyle(modalCameraEl).display,
                classes: modalCameraEl.className
            });
            showWarningDialog('Nao foi possivel abrir a interface da camera. Tente pela galeria enquanto ajustamos este fluxo.', 'Falha ao abrir camera');
        }, 1000);
    } catch (err) {
        console.error('[OS Nova] falha ao acessar camera', err);
        showWarningDialog('Nao foi possivel acessar a camera: ' + err.message, 'Camera indisponivel');
    }
}

document.getElementById('btnAbrirCamera')?.addEventListener('click', async () => {
    openCameraCapture({ type: 'equipamento', entryId: null });
});

modalCameraEl?.addEventListener('shown.bs.modal', () => {
    if (modalCameraEl) {
        modalCameraEl.style.zIndex = String(CAMERA_MODAL_Z_INDEX);
    }
    elevateLatestBackdrop(CAMERA_MODAL_Z_INDEX - 10);
    console.info('[OS Nova] modal da camera exibido com sucesso');
});

modalCameraEl?.addEventListener('hidden.bs.modal', () => {
    if (streamCamera) {
        streamCamera.getTracks().forEach(track => track.stop());
        streamCamera = null;
    }
    if (videoCamera) {
        videoCamera.srcObject = null;
    }
    if (cameraCaptureContext.type === 'acessorio' && cropContext.type !== 'acessorio') {
        acessorioCropEntryId = null;
        acessorioCropQueue = [];
    }
    if (cameraCaptureContext.type === 'estado_fisico' && cropContext.type !== 'estado_fisico') {
        estadoFisicoCropEntryId = null;
        estadoFisicoCropQueue = [];
    }
    if (cameraCaptureContext.type === 'checklist_entrada' && cropContext.type !== 'checklist_entrada') {
        checklistEntradaCropItemId = null;
        checklistEntradaCropQueue = [];
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
        if (cropContext.type === 'acessorio') {
            modalCropTitle.innerHTML = '<i class="bi bi-crop text-warning me-2"></i>Ajustar Foto do Acessorio';
        } else if (cropContext.type === 'estado_fisico') {
            modalCropTitle.innerHTML = '<i class="bi bi-crop text-warning me-2"></i>Ajustar Foto do Estado Fisico';
        } else if (cropContext.type === 'checklist_entrada') {
            modalCropTitle.innerHTML = '<i class="bi bi-crop text-warning me-2"></i>Ajustar Foto da Discrepancia';
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
        console.error('[OS Nova] blob vazio ao anexar foto', cropContext);
        showWarningDialog('Nao foi possivel gerar a imagem selecionada.');
        return;
    }

    if (cropContext.type === 'acessorio' && acessorioCropEntryId) {
        const entryId = acessorioCropEntryId;
        const dt = acessoriosPhotos[entryId] || new DataTransfer();
        const fileName = `acessorio_${Date.now()}_${Math.random().toString(36).slice(2, 6)}.jpg`;
        const file = new File([blob], fileName, { type: 'image/jpeg' });
        dt.items.add(file);
        acessoriosPhotos[entryId] = dt;
        ensureAcessorioFileInput(entryId);
        renderAcessoriosList();
        if (entryId === acessoriosQuickEntryId) {
            renderAcessoriosQuickPhotos();
        }
        scheduleDraftSave();

        if (acessorioCropQueue.length > 0) {
            processNextAcessorioCrop();
        } else {
            acessorioCropEntryId = null;
            hideModalSafe(modalCrop, '#modalCropEquip');
        }
        return;
    }

    if (cropContext.type === 'estado_fisico' && estadoFisicoCropEntryId) {
        const entryId = estadoFisicoCropEntryId;
        const dt = estadoFisicoPhotos[entryId] || new DataTransfer();
        const fileName = `estado_fisico_${Date.now()}_${Math.random().toString(36).slice(2, 6)}.jpg`;
        const file = new File([blob], fileName, { type: 'image/jpeg' });
        dt.items.add(file);
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

    if (cropContext.type === 'checklist_entrada' && checklistEntradaCropItemId) {
        const entryId = String(checklistEntradaCropItemId);
        const checklistItem = ensureChecklistDraftItem(entryId);
        if (!checklistItem) {
            checklistEntradaCropItemId = null;
            if (checklistEntradaCropQueue.length > 0) {
                processNextChecklistEntradaCrop();
            } else {
                hideModalSafe(modalCrop, '#modalCropEquip');
            }
            return;
        }

        const dt = ensureChecklistDraftTransfer(entryId);
        const fileName = `checklist_entrada_${Date.now()}_${Math.random().toString(36).slice(2, 6)}.jpg`;
        const file = new File([blob], fileName, { type: 'image/jpeg' });
        dt.items.add(file);
        if (checklistItem.status === 'nao_verificado') {
            checklistItem.status = 'discrepancia';
        }
        renderChecklistEntradaModal();
        scheduleDraftSave();

        if (checklistEntradaCropQueue.length > 0) {
            processNextChecklistEntradaCrop();
        } else {
            checklistEntradaCropItemId = null;
            hideModalSafe(modalCrop, '#modalCropEquip');
        }
        return;
    }

    if (cropContext.type === 'entrada') {
        if (osDataTransfer.files.length >= osFotosMaxFiles) {
            showWarningDialog(`Voce pode enviar ate ${osFotosMaxFiles} fotos no total.`);
            hideModalSafe(modalCrop, '#modalCropEquip');
            return;
        }

        const fileName = `entrada_${Date.now()}_${Math.random().toString(36).slice(2, 6)}.jpg`;
        const file = new File([blob], fileName, { type: 'image/jpeg' });
        osDataTransfer.items.add(file);
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

    if (getTotalModalEquipFotos() >= novoEquipFotosMaxFiles) {
        showWarningDialog(`Voce pode manter ate ${novoEquipFotosMaxFiles} fotos por equipamento.`);
        hideModalSafe(modalCrop, '#modalCropEquip');
        return;
    }

    const fileName = `equipamento_${Date.now()}_${Math.random().toString(36).slice(2, 6)}.jpg`;
    const file = new File([blob], fileName, { type: 'image/jpeg' });
    novoEquipFotosDataTransfer.items.add(file);
    syncNovoEquipFotosInput();
    detectDominantColorOS(canvas);
    renderNovoEquipFotosNovas();

    if (novoEquipFotoCropQueue.length > 0) {
        processNextNovoEquipCrop();
        return;
    }

    hideModalSafe(modalCrop, '#modalCropEquip');
}

function fallbackCropperFromSource(source, context, warnMessage = null) {
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
            console.error('[OS Nova] Canvas nao disponivel no fallback de imagem', context);
            showWarningDialog('Nao foi possivel processar a imagem selecionada.');
            hideModalSafe(modalCrop, '#modalCropEquip');
            return;
        }

        ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
        canvas.toBlob((blob) => appendBlobToCurrentPhotoContext(blob, canvas), 'image/jpeg', 0.9);
    };
    img.onerror = () => {
        console.error('[OS Nova] erro ao carregar imagem no fallback visual', context);
        showWarningDialog('Nao foi possivel carregar a imagem para envio.');
        hideModalSafe(modalCrop, '#modalCropEquip');
    };
    img.src = source;
}

function openCropper(source, context = { type: 'equipamento' }) {
    closeImageModalIfOpen();
    const cropToken = ++activeCropToken;
    if (!source) {
        console.error('[OS Nova] openCropper chamado sem source', context);
        return;
    }
    if (!imgToCrop || !modalCropEl) {
        console.error('[OS Nova] elementos do editor de corte indisponiveis', { hasImage: Boolean(imgToCrop), hasModal: Boolean(modalCropEl), context });
        fallbackCropperFromSource(source, context, 'Editor visual indisponivel no momento. A foto sera adicionada sem corte.');
        return;
    }
    if (typeof window.Cropper === 'undefined') {
        console.error('[OS Nova] Cropper nao disponivel, ativando fallback');
        if (!cropperUnavailableWarned) {
            cropperUnavailableWarned = true;
            showWarningDialog('Editor de corte indisponivel. A foto sera adicionada sem corte.');
        }
        fallbackCropperFromSource(source, context);
        return;
    }

    setCropContext(context);
    try {
        cropper?.destroy();
    } catch (error) {
        console.error('[OS Nova] falha ao destruir cropper anterior', error);
    }
    cropper = null;
    imgToCrop.onload = null;
    imgToCrop.onerror = null;
    imgToCrop.src = source;
    imgToCrop.dataset.cropToken = String(cropToken);

    hoistModalToBody(modalCropEl, CROP_MODAL_Z_INDEX);
    if (modalCropEl) {
        modalCropEl.style.zIndex = String(CROP_MODAL_Z_INDEX);
    }
    const cropModalInstance = bootstrap.Modal.getOrCreateInstance(modalCropEl);
    cropModalInstance.show();
    window.setTimeout(() => elevateLatestBackdrop(CROP_MODAL_Z_INDEX - 10), 80);

    window.setTimeout(() => {
        if (cropToken !== activeCropToken) return;
        if (cropper || isCropModalVisible()) return;
        console.error('[OS Nova] modal de crop nao abriu corretamente, ativando fallback', {
            context,
            display: modalCropEl.style.display,
            computedDisplay: window.getComputedStyle(modalCropEl).display,
            classes: modalCropEl.className
        });
        hideModalSafe(cropModalInstance, '#modalCropEquip');
        fallbackCropperFromSource(source, context, 'Editor visual indisponivel no momento. A foto sera adicionada sem corte.');
    }, 1200);
}

document.getElementById('modalCropEquip').addEventListener('shown.bs.modal', () => {
    if (modalCropEl) {
        modalCropEl.style.zIndex = String(CROP_MODAL_Z_INDEX);
    }
    elevateLatestBackdrop(CROP_MODAL_Z_INDEX - 10);
    if (typeof window.Cropper === 'undefined') {
        return;
    }

    const initCropperWhenReady = () => {
        try {
            createCropperInstance();
        } catch (error) {
            console.error('[OS Nova] falha ao inicializar cropper no modal visivel', error);
            hideModalSafe(bootstrap.Modal.getOrCreateInstance(modalCropEl), '#modalCropEquip');
            fallbackCropperFromSource(imgToCrop?.src || '', cropContext, 'Falha no editor visual. A foto sera adicionada sem corte.');
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
        console.error('[OS Nova] falha ao carregar imagem para o cropper', error);
        hideModalSafe(bootstrap.Modal.getOrCreateInstance(modalCropEl), '#modalCropEquip');
        fallbackCropperFromSource(imgToCrop?.src || '', cropContext, 'Falha ao carregar a imagem para corte. A foto sera adicionada sem corte.');
    };
});

document.getElementById('modalCropEquip').addEventListener('hidden.bs.modal', () => {
    if (cropper) {
        cropper.destroy();
        cropper = null;
    }
    if (cropContext.type === 'acessorio') {
        acessorioCropQueue = [];
        acessorioCropEntryId = null;
    }
    if (cropContext.type === 'estado_fisico') {
        estadoFisicoCropQueue = [];
        estadoFisicoCropEntryId = null;
    }
    if (cropContext.type === 'checklist_entrada') {
        checklistEntradaCropQueue = [];
        checklistEntradaCropItemId = null;
    }
    if (cropContext.type === 'entrada') {
        fotosEntradaCropQueue = [];
    }
    if (cropContext.type === 'equipamento') {
        novoEquipFotoCropQueue = [];
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
        console.error('[OS Nova] camera indisponivel para captura');
        showWarningDialog('Nao foi possivel capturar a foto pela camera.', 'Camera indisponivel');
        return;
    }
    canvasCamera.width  = videoCamera.videoWidth || 1280;
    canvasCamera.height = videoCamera.videoHeight || 720;
    context.drawImage(videoCamera, 0, 0, canvasCamera.width, canvasCamera.height);
    
    const dataUrl = canvasCamera.toDataURL('image/jpeg');
    hideModalSafe(bootstrap.Modal.getOrCreateInstance(modalCameraEl), '#modalCamera');
    if (cameraCaptureContext.type === 'acessorio' && cameraCaptureContext.entryId) {
        acessorioCropEntryId = cameraCaptureContext.entryId;
        acessorioCropQueue = [];
        openCropper(dataUrl, { type: 'acessorio' });
        return;
    }
    if (cameraCaptureContext.type === 'estado_fisico' && cameraCaptureContext.entryId) {
        estadoFisicoCropEntryId = cameraCaptureContext.entryId;
        estadoFisicoCropQueue = [];
        openCropper(dataUrl, { type: 'estado_fisico' });
        return;
    }
    if (cameraCaptureContext.type === 'checklist_entrada' && cameraCaptureContext.entryId) {
        checklistEntradaCropItemId = String(cameraCaptureContext.entryId);
        checklistEntradaCropQueue = [];
        openCropper(dataUrl, { type: 'checklist_entrada' });
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
        width: 1024, // Limita o tamanho para não sobrecarregar
        height: 1024,
        imageSmoothingEnabled: true,
        imageSmoothingQuality: 'high',
    });

    canvas.toBlob((blob) => {
        if (!blob) return;
        if (cropContext.type === 'acessorio' && acessorioCropEntryId) {
            const entryId = acessorioCropEntryId;
            const dt = acessoriosPhotos[entryId] || new DataTransfer();
            const fileName = `acessorio_${Date.now()}_${Math.random().toString(36).slice(2, 6)}.jpg`;
            const file = new File([blob], fileName, { type: 'image/jpeg' });
            dt.items.add(file);
            acessoriosPhotos[entryId] = dt;
            ensureAcessorioFileInput(entryId);
            renderAcessoriosList();
            if (entryId === acessoriosQuickEntryId) {
                renderAcessoriosQuickPhotos();
            }
            scheduleDraftSave();

            if (acessorioCropQueue.length > 0) {
                processNextAcessorioCrop();
            } else {
                acessorioCropEntryId = null;
                hideModalSafe(modalCrop, '#modalCropEquip');
            }
            return;
        }

        if (cropContext.type === 'estado_fisico' && estadoFisicoCropEntryId) {
            const entryId = estadoFisicoCropEntryId;
            const dt = estadoFisicoPhotos[entryId] || new DataTransfer();
            const fileName = `estado_fisico_${Date.now()}_${Math.random().toString(36).slice(2, 6)}.jpg`;
            const file = new File([blob], fileName, { type: 'image/jpeg' });
            dt.items.add(file);
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
            showWarningDialog(`Voce pode enviar ate ${osFotosMaxFiles} fotos no total.`);
            hideModalSafe(modalCrop, '#modalCropEquip');
            return;
        }

            const fileName = `entrada_${Date.now()}_${Math.random().toString(36).slice(2, 6)}.jpg`;
            const file = new File([blob], fileName, { type: 'image/jpeg' });
            osDataTransfer.items.add(file);
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

        if (getTotalModalEquipFotos() >= novoEquipFotosMaxFiles) {
            showWarningDialog(`Voce pode manter ate ${novoEquipFotosMaxFiles} fotos por equipamento.`);
            hideModalSafe(modalCrop, '#modalCropEquip');
            return;
        }

        const fileName = `equipamento_${Date.now()}_${Math.random().toString(36).slice(2, 6)}.jpg`;
        const file = new File([blob], fileName, { type: 'image/jpeg' });
        novoEquipFotosDataTransfer.items.add(file);
        syncNovoEquipFotosInput();
        
        detectDominantColorOS(canvas); // <--- Inicia a detecção de cor automática na OS

        // Preview Final
        renderNovoEquipFotosNovas();

        if (novoEquipFotoCropQueue.length > 0) {
            processNextNovoEquipCrop();
            return;
        }
        hideModalSafe(modalCrop, '#modalCropEquip');
    }, 'image/jpeg', 0.9);
});

const btnConfirmCropOriginal = document.getElementById('btnConfirmCrop');
if (btnConfirmCropOriginal && btnConfirmCropOriginal.parentNode) {
    const btnConfirmCropSafe = btnConfirmCropOriginal.cloneNode(true);
    btnConfirmCropOriginal.parentNode.replaceChild(btnConfirmCropSafe, btnConfirmCropOriginal);
    btnConfirmCropSafe.addEventListener('click', () => {
        if (!cropper) return;
        const canvas = cropper.getCroppedCanvas({
            width: 1024,
            height: 1024,
            imageSmoothingEnabled: true,
            imageSmoothingQuality: 'high',
        });

        if (!canvas) {
            console.error('[OS Nova] getCroppedCanvas retornou vazio', cropContext);
            showWarningDialog('Nao foi possivel preparar a imagem selecionada.');
            return;
        }

        canvas.toBlob((blob) => {
            appendBlobToCurrentPhotoContext(blob, canvas);
        }, 'image/jpeg', 0.9);
    });
}

novoEquipFoto?.addEventListener('change', function() {
    queueNovoEquipFotosFromFiles(this.files);
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
    if (semFoto.length && !semFoto.some(f => Number(f.is_principal) === 1)) {
        semFoto[0] = { ...semFoto[0], is_principal: 1 };
    }
    bumpModalEquipFotosVersion();
    renderModalEquipFotosExistentes(semFoto);
    renderNovoEquipFotosNovas();
    syncSidebarFotosFromModal(semFoto);

    const fd = new FormData();
    fd.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

    try {
        const response = await fetch(`${BASE_URL}equipamentos/deletar-foto/${fotoId}`, {
            method: 'POST',
            body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const res = await response.json();
        if (!res || res.success !== true) {
            throw new Error(res?.message || 'Nao foi possivel excluir a foto.');
        }

        if (Array.isArray(res.fotos)) {
            bumpModalEquipFotosVersion();
            bumpEquipamentoFotosVersion();
            renderModalEquipFotosExistentes(res.fotos);
            renderNovoEquipFotosNovas();
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
        renderNovoEquipFotosNovas();
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
        marca: selectedEq.marca_nome || selectedEq.marca || '',
        modelo: selectedEq.modelo_nome || selectedEq.modelo || '',
        serie: selectedEq.numero_serie || selectedEq.serie || '',
        tipo: selectedEq.tipo_nome || selectedEq.tipo || '',
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
        const res = await response.json();
        if (!res || res.success !== true) {
            throw new Error(res?.message || 'Nao foi possivel definir a foto principal.');
        }

        const fotos = Array.isArray(res.fotos) ? res.fotos : [];
        bumpModalEquipFotosVersion();
        bumpEquipamentoFotosVersion();
        renderModalEquipFotosExistentes(fotos);
        renderNovoEquipFotosNovas();
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

    const removeNovoEquipFotoBtn = event.target.closest('.btn-remover-foto-nova-equip');
    if (!removeNovoEquipFotoBtn) return;

    const index = parseInt(removeNovoEquipFotoBtn.dataset.index, 10);
    if (Number.isNaN(index)) return;

    const nextDt = new DataTransfer();
    Array.from(novoEquipFotosDataTransfer.files).forEach((file, fileIndex) => {
        if (fileIndex !== index) nextDt.items.add(file);
    });

    novoEquipFotosDataTransfer.items.clear();
    Array.from(nextDt.files).forEach(file => novoEquipFotosDataTransfer.items.add(file));
    syncNovoEquipFotosInput();
    renderNovoEquipFotosNovas();
});

// --- Select2 Híbrido: Modelos via API ---
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
            delay: 250,
            data: function (params) {
                var tipoNome = $('#novoEquipTipo option:selected').text().trim();
                return {
                    q:        params.term || '',
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
        minimumInputLength: 0,
        language: {
            inputTooShort: function (args) {
                var restante = args.minimum - args.input.length;
                return `Digite mais ${restante} caractere(s) para buscar...`;
            },
            searching: function() { return '<i class="bi bi-search me-1"></i> Buscando modelos...'; },
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
    clearNovoEquipModalValidationState();
    var marcaId = $(this).val();
    if (marcaId) {
        initModeloSelect2();
    } else {
        if ($('#novoEquipModelo').hasClass("select2-hidden-accessible")) {
            $('#novoEquipModelo').select2('destroy').html('<option value="">Selecione a marca primeiro...</option>');
        }
    }
});

$('#novoEquipTipo').on('change', function() {
    if (String(this.value || '').trim()) {
        this.classList.remove('is-invalid');
    }
});

$('#novoEquipModelo').on('change', function() {
    if (String($(this).val() || '').trim()) {
        this.classList.remove('is-invalid');
        $(this).next('.select2-container').find('.select2-selection').removeClass('border-danger', 'border-2');
    }
});

// Salvar equipamento via AJAX
document.getElementById('btnSalvarNovoEquip')?.addEventListener('click', function() {
    const form = document.getElementById('formNovoEquipAjax');
    const errors = document.getElementById('modalEquipErrors');
    if (!form || !errors) return;
    clearNovoEquipModalValidationState();

    if (!validateNovoEquipRequiredFields()) {
        return;
    }

    const formData = new FormData(form);

    const modeloId = $('#novoEquipModelo').val();
    if (modeloId && String(modeloId).startsWith('EXT|')) {
        formData.append('modelo_nome_ext', $('#novoEquipModelo option:selected').text());
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
    .then(r => r.json())
    .then(res => {
        if (res.status !== 'success') {
            const message = Object.values(res.errors || {}).join('<br>') || (res.message || 'Erro ao salvar equipamento.');
            if (res.focus_tab) {
                handleNovoEquipValidationFeedback(message, res.focus_tab, false);
            } else {
                errors.innerHTML = message;
                errors.classList.remove('d-none');
            }
            return;
        }

        const eq = res.equipamento || {};
        const eqId = String(eq.id || equipamentoEditId || '');
        if (!eqId) {
            throw new Error('Resposta sem identificador do equipamento.');
        }

        const nome = `${eq.marca_nome || ''} ${eq.modelo_nome || ''} (${eq.tipo_nome || ''})`.trim();
        const sel = document.getElementById('equipamentoSelect');
        if (!sel) return;

        let opt = Array.from(sel.options).find(o => String(o.value) === eqId);
        if (!opt) {
            opt = new Option(nome, eqId, true, true);
            sel.appendChild(opt);
        }
        opt.text = nome;
        opt.value = eqId;
        opt.dataset.tipo = eq.tipo_id || '';
        opt.dataset.marca = eq.marca_nome || '';
        opt.dataset.modelo = eq.modelo_nome || '';
        opt.dataset.serie = eq.numero_serie || '';
        opt.dataset.imei = eq.imei || '';
        opt.dataset.cor = eq.cor || '';
        opt.dataset.cor_hex = eq.cor_hex || '';
        opt.dataset.tipo_nome = eq.tipo_nome || '';
        opt.dataset.marca_id = eq.marca_id || '';
        opt.dataset.modelo_id = eq.modelo_id || '';
        opt.dataset.cliente_id = eq.cliente_id || '';
        opt.dataset.senha_acesso = eq.senha_acesso || '';
        opt.dataset.estado_fisico = eq.estado_fisico || '';
        opt.dataset.acessorios = eq.acessorios || '';
        opt.dataset.foto_url = res.foto_url || eq.foto_url || '';

        eq.foto_url = res.foto_url || eq.foto_url || '';
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
            renderNovoEquipFotosNovas();
        }

        carregarFotosEquipamento(eqId, {
            marca: eq.marca_nome,
            modelo: eq.modelo_nome,
            tipo: eq.tipo_nome,
            cor: eq.cor,
            cor_hex: eq.cor_hex
        }, fotosAtualizadas);

        if (eq.tipo_id) carregarDefeitos(eq.tipo_id);

        bootstrap.Modal.getInstance(document.getElementById('modalNovoEquipamento'))?.hide();

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
        errors.innerHTML = 'Erro inesperado. Tente novamente.';
        errors.classList.remove('d-none');
    });
});

// --- Defeitos comuns (card + modal) ---
var btnAdicionarDefeitosComuns = document.getElementById('btnAdicionarDefeitosComuns');
var modalDefeitosComunsEl = document.getElementById('modalDefeitosComuns');
var modalDefeitosComunsBody = document.getElementById('modalDefeitosComunsBody');
var modalDefeitosHint = document.getElementById('modalDefeitosHint');
var defeitosHelperText = document.getElementById('defeitosHelperText');

function syncDefeitosModalStack() {
    if (!modalDefeitosComunsEl) return;
    const modalZ = getTopModalStackZIndex(DEFEITOS_MODAL_BASE_Z_INDEX);
    modalDefeitosComunsEl.style.zIndex = String(modalZ);

    window.setTimeout(() => {
        const backdrops = Array.from(document.querySelectorAll('.modal-backdrop'));
        const latestBackdrop = backdrops[backdrops.length - 1];
        if (latestBackdrop) {
            latestBackdrop.style.zIndex = String(modalZ - 10);
        }
    }, 0);
}

if (modalDefeitosComunsEl) {
    hoistModalToBody(modalDefeitosComunsEl, DEFEITOS_MODAL_BASE_Z_INDEX);
    modalDefeitosComunsEl.addEventListener('show.bs.modal', syncDefeitosModalStack);
    modalDefeitosComunsEl.addEventListener('shown.bs.modal', syncDefeitosModalStack);
}

if (btnAdicionarDefeitosComuns && modalDefeitosComunsEl) {
    btnAdicionarDefeitosComuns.addEventListener('click', () => {
        syncDefeitosModalStack();
        const modalInstance = bootstrap.Modal.getOrCreateInstance(modalDefeitosComunsEl);
        modalInstance.show();
    });
}

function _escapeHtmlDefeito(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function _escapeRegExp(value) {
    return String(value).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

function _syncRelatoComDefeito(checkbox) {
    const relato = document.getElementById('relatoClienteInput') || document.querySelector('textarea[name="relato_cliente"]');
    if (!relato || !checkbox) return;

    const nome = checkbox.getAttribute('data-nome') || '';
    const desc = checkbox.getAttribute('data-desc') || '';
    const tag = `[DEFEITO: ${nome}]${desc ? ' - ' + desc : ''}`;

    if (checkbox.checked) {
        if (!relato.value.includes(tag)) {
            relato.value = relato.value.trim() ? `${relato.value.trimEnd()}\n${tag}` : tag;
        }
        return;
    }

    const regex = new RegExp(`(^|\\n)${_escapeRegExp(tag)}(?=\\n|$)`, 'g');
    relato.value = relato.value
        .replace(regex, '')
        .replace(/\n{2,}/g, '\n')
        .replace(/^\n+|\n+$/g, '');
}

function renderDefeitosSelecionadosCard() {
    const container = document.getElementById('defeitosContainer');
    if (!container) return;

    const checks = Array.from((modalDefeitosComunsBody || document).querySelectorAll('.chk-defeito-comum:checked'));
    const totalCatalogados = Array.from((modalDefeitosComunsBody || document).querySelectorAll('.chk-defeito-comum')).length;

    defeitosSelecionados = checks.map((chk) => parseInt(chk.value, 10)).filter((id) => Number.isInteger(id));

    if (!totalCatalogados) {
        container.innerHTML = '<span class="text-muted small">Selecione o equipamento para carregar os defeitos...</span>';
        if (defeitosHelperText) {
            defeitosHelperText.textContent = 'Selecione os defeitos que se aplicam ao diagnostico atual.';
        }
        return;
    }

    if (!checks.length) {
        container.innerHTML = '<div class="os-defeitos-empty">Nenhum defeito comum adicionado.</div>';
        if (defeitosHelperText) {
            defeitosHelperText.textContent = 'Nenhum defeito comum foi selecionado ate o momento.';
        }
        return;
    }

    container.innerHTML = checks.map((chk) => {
        const id = parseInt(chk.value, 10);
        const nomeRaw = chk.getAttribute('data-nome') || '';
        const descRaw = chk.getAttribute('data-desc') || '';
        const nome = _escapeHtmlDefeito(nomeRaw);
        const desc = _escapeHtmlDefeito(descRaw);

        return `
            <div class="os-defeito-item d-flex justify-content-between align-items-start gap-2" data-defeito-id="${id}">
                <div class="flex-grow-1">
                    <p class="os-defeito-item__title">${nome}</p>
                    ${descRaw ? `<p class="os-defeito-item__desc">${desc}</p>` : ''}
                </div>
                <span class="os-defeito-item__actions">
                    <button
                        type="button"
                        class="btn btn-sm btn-link p-0 text-warning btn-ver-procedimentos-os"
                        data-id="${id}"
                        data-nome="${nome}"
                        title="Ver Procedimentos">
                        <i class="bi bi-info-circle"></i>
                    </button>
                    <button
                        type="button"
                        class="btn btn-sm btn-link p-0 text-danger btn-remover-defeito-comum"
                        data-id="${id}"
                        title="Remover defeito">
                        <i class="bi bi-x-circle"></i>
                    </button>
                </span>
            </div>
        `;
    }).join('');

    if (defeitosHelperText) {
        const qtd = checks.length;
        defeitosHelperText.textContent = qtd === 1
            ? '1 defeito comum selecionado.'
            : `${qtd} defeitos comuns selecionados.`;
    }
}

const defeitosContainerEl = document.getElementById('defeitosContainer');
if (defeitosContainerEl && !defeitosContainerEl.dataset.boundDefeitosEvents) {
    defeitosContainerEl.dataset.boundDefeitosEvents = '1';
    defeitosContainerEl.addEventListener('click', (event) => {
        const removeBtn = event.target.closest('.btn-remover-defeito-comum');
        if (removeBtn) {
            event.preventDefault();
            const id = parseInt(removeBtn.dataset.id || '', 10);
            if (!Number.isInteger(id)) return;
            const targetChk = document.getElementById(`def_${id}`);
            if (!targetChk) return;
            targetChk.checked = false;
            _syncRelatoComDefeito(targetChk);
            renderDefeitosSelecionadosCard();
            updateResumo();
            scheduleDraftSave();
            return;
        }

        const procBtn = event.target.closest('.btn-ver-procedimentos-os');
        if (procBtn) {
            event.preventDefault();
            abrirProcedimentosViewOnly(procBtn.dataset.id, procBtn.dataset.nome);
        }
    });
}

if (modalDefeitosComunsBody && !modalDefeitosComunsBody.dataset.boundDefeitosEvents) {
    modalDefeitosComunsBody.dataset.boundDefeitosEvents = '1';
    modalDefeitosComunsBody.addEventListener('change', (event) => {
        const checkbox = event.target.closest('.chk-defeito-comum');
        if (!checkbox) return;
        _syncRelatoComDefeito(checkbox);
        renderDefeitosSelecionadosCard();
        updateResumo();
        scheduleDraftSave();
    });

    modalDefeitosComunsBody.addEventListener('click', (event) => {
        const procBtn = event.target.closest('.btn-ver-procedimentos-os');
        if (!procBtn) return;
        event.preventDefault();
        abrirProcedimentosViewOnly(procBtn.dataset.id, procBtn.dataset.nome);
    });
}

function _renderDefeitosModalList(defeitos, preSelecionadosIds) {
    if (!modalDefeitosComunsBody) return;

    const hw = defeitos.filter(d => String(d.classificacao || '').toLowerCase() === 'hardware');
    const sw = defeitos.filter(d => String(d.classificacao || '').toLowerCase() === 'software');
    const outros = defeitos.filter(d => !['hardware', 'software'].includes(String(d.classificacao || '').toLowerCase()));

    const grupos = [
        { list: hw, cls: 'text-danger', icon: 'bi-cpu', label: 'HARDWARE' },
        { list: sw, cls: 'text-primary', icon: 'bi-code-slash', label: 'SOFTWARE' },
        { list: outros, cls: 'text-secondary', icon: 'bi-grid', label: 'OUTROS' },
    ].filter(group => group.list.length);

    const selectedSet = new Set((preSelecionadosIds || []).map((id) => parseInt(id, 10)));

    let html = '<div class="row g-3">';
    grupos.forEach(({ list, cls, icon, label }) => {
        html += `
            <div class="col-12 col-xl-6">
                <div class="os-defeitos-modal-group h-100">
                    <p class="${cls} fw-bold mb-2 small"><i class="bi ${icon} me-1"></i>${label}</p>
        `;

        list.forEach((d) => {
            const id = parseInt(d.id, 10);
            const nome = _escapeHtmlDefeito(d.nome || '');
            const desc = _escapeHtmlDefeito(d.descricao || '');
            const checked = selectedSet.has(id) ? 'checked' : '';

            html += `
                <div class="form-check mb-2">
                    <input
                        class="form-check-input chk-defeito-comum"
                        type="checkbox"
                        name="defeitos[]"
                        value="${id}"
                        id="def_${id}"
                        ${checked}
                        data-nome="${nome}"
                        data-desc="${desc}">
                    <label class="form-check-label d-flex align-items-center" for="def_${id}">
                        <div class="flex-grow-1">
                            <strong style="font-size:0.85rem;">${nome}</strong>
                            ${desc ? `<br><small class="text-muted">${desc}</small>` : ''}
                        </div>
                        <button
                            type="button"
                            class="btn btn-sm btn-link p-0 text-warning ms-2 btn-ver-procedimentos-os"
                            data-id="${id}"
                            data-nome="${nome}"
                            title="Ver Procedimentos">
                            <i class="bi bi-info-circle"></i>
                        </button>
                    </label>
                </div>
            `;
        });

        html += `
                </div>
            </div>
        `;
    });
    html += '</div>';

    modalDefeitosComunsBody.innerHTML = html;
}

// --- carregarDefeitos ---
function carregarDefeitos(tipoId) {
    const section = document.getElementById('defeitosSection');
    const container = document.getElementById('defeitosContainer');
    if (!section || !container) return;

    if (!tipoId) {
        section.style.display = 'none';
        if (btnAdicionarDefeitosComuns) btnAdicionarDefeitosComuns.disabled = true;
        if (modalDefeitosComunsBody) {
            modalDefeitosComunsBody.innerHTML = '<span class="text-muted small">Selecione o equipamento para carregar os defeitos.</span>';
        }
        container.innerHTML = '<span class="text-muted small">Selecione o equipamento para carregar os defeitos...</span>';
        if (defeitosHelperText) {
            defeitosHelperText.textContent = 'Selecione os defeitos que se aplicam ao diagnostico atual.';
        }
        updateResumo();
        return;
    }

    section.style.display = '';
    container.innerHTML = '<div class="text-muted small"><i class="bi bi-hourglass-split me-1"></i>Carregando defeitos...</div>';
    if (btnAdicionarDefeitosComuns) btnAdicionarDefeitosComuns.disabled = true;

    const fd = new FormData();
    fd.append('tipo_id', tipoId);
    fd.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

    fetch(BASE_URL + 'equipamentosdefeitos/por-tipo', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(defeitos => {
        if (!Array.isArray(defeitos) || defeitos.length === 0) {
            container.innerHTML = `<span class="text-muted small"><i class="bi bi-info-circle me-1"></i>Nenhum defeito comum cadastrado para este tipo. <a href="${BASE_URL}equipamentosdefeitos" target="_blank">Cadastrar defeitos</a></span>`;
            if (modalDefeitosComunsBody) {
                modalDefeitosComunsBody.innerHTML = `<span class="text-muted small">Nenhum defeito comum cadastrado para este tipo. <a href="${BASE_URL}equipamentosdefeitos" target="_blank">Cadastrar defeitos</a></span>`;
            }
            if (modalDefeitosHint) {
                modalDefeitosHint.textContent = 'Nao existem defeitos comuns cadastrados para o tipo selecionado.';
            }
            if (btnAdicionarDefeitosComuns) btnAdicionarDefeitosComuns.disabled = true;
            updateResumo();
            return;
        }

        const currentCheckedIds = Array.from((modalDefeitosComunsBody || document).querySelectorAll('.chk-defeito-comum:checked'))
            .map((chk) => parseInt(chk.value, 10))
            .filter((id) => Number.isInteger(id));
        const preSelecionados = Array.from(new Set([
            ...(Array.isArray(defeitosSelecionados) ? defeitosSelecionados : []),
            ...currentCheckedIds,
        ]));

        _renderDefeitosModalList(defeitos, preSelecionados);
        if (btnAdicionarDefeitosComuns) btnAdicionarDefeitosComuns.disabled = false;
        if (modalDefeitosHint) {
            modalDefeitosHint.textContent = 'Marque os defeitos que devem aparecer no card da OS.';
        }

        _applyPendingDefeitos();
        renderDefeitosSelecionadosCard();
        updateResumo();
        scheduleDraftSave();
    })
    .catch(() => {
        container.innerHTML = '<span class="text-danger small">Erro ao carregar defeitos.</span>';
        if (modalDefeitosComunsBody) {
            modalDefeitosComunsBody.innerHTML = '<span class="text-danger small">Erro ao carregar defeitos.</span>';
        }
        if (btnAdicionarDefeitosComuns) btnAdicionarDefeitosComuns.disabled = true;
    });
}

// --- Modal de visualizacao de procedimentos ---
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

    hoistModalToBody(modalEl, null);

    const listDiv = modalEl.querySelector('#listProcOS');
    listDiv.innerHTML = '<div class="text-center py-3"><div class="spinner-border text-warning spinner-border-sm"></div></div>';

    const modalZ = getTopModalStackZIndex(DEFEITOS_MODAL_BASE_Z_INDEX + 40);
    modalEl.style.zIndex = String(modalZ);
    const procedimentosModal = bootstrap.Modal.getOrCreateInstance(modalEl);
    procedimentosModal.show();
    window.setTimeout(() => {
        const backdrops = Array.from(document.querySelectorAll('.modal-backdrop'));
        const latestBackdrop = backdrops[backdrops.length - 1];
        if (latestBackdrop) {
            latestBackdrop.style.zIndex = String(modalZ - 10);
        }
    }, 0);

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

// --- Modal de Visualização de Imagem (Lightbox) ---
updateResumo();
document.addEventListener('DOMContentLoaded', function() {
    const modalInnerHtml = `
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content bg-transparent border-0">
                <div class="modal-body text-center p-0 position-relative">
                    <div class="d-inline-block position-relative">
                        <button type="button" class="btn-close position-absolute" data-bs-dismiss="modal" aria-label="Close" style="top: 10px; right: 10px; z-index: 2055; filter: invert(1); opacity: 1; background-color: rgba(0,0,0,0.6); border-radius: 50%; padding: 0.8rem; box-shadow: 0 4px 12px rgba(0,0,0,0.5);"></button>
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
            console.error('[OS Nova] tentativa de abrir lightbox sem data-img-src');
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
