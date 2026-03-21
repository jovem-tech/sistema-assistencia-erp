<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$enabled = (string) ($configs['whatsapp_enabled'] ?? '0') === '1';
$directProvider = (string) ($configs['whatsapp_direct_provider'] ?? 'menuia');
if ($directProvider === 'local_node') {
    $directProvider = 'api_whats_local';
}
if (!in_array($directProvider, ['menuia', 'api_whats_local', 'api_whats_linux', 'webhook'], true)) {
    $directProvider = 'api_whats_local';
}

$menuiaUrl = trim((string) ($configs['whatsapp_menuia_url'] ?? 'https://api.menuia.com/api'));
$menuiaAuth = trim((string) ($configs['whatsapp_menuia_authkey'] ?? ''));
$menuiaApp = trim((string) ($configs['whatsapp_menuia_appkey'] ?? ''));
$localNodeUrl = trim((string) ($configs['whatsapp_local_node_url'] ?? 'http://127.0.0.1:3001'));
$localNodeToken = trim((string) ($configs['whatsapp_local_node_token'] ?? ''));
$linuxNodeUrl = trim((string) ($configs['whatsapp_linux_node_url'] ?? 'http://127.0.0.1:3001'));
$linuxNodeToken = trim((string) ($configs['whatsapp_linux_node_token'] ?? ''));

$statusOk = false;
if ($enabled && $directProvider === 'menuia') {
    $statusOk = $menuiaAuth !== '' && $menuiaApp !== '';
}
if ($enabled && $directProvider === 'api_whats_local') {
    $statusOk = $localNodeUrl !== '' && $localNodeToken !== '';
}
if ($enabled && $directProvider === 'api_whats_linux') {
    $statusOk = $linuxNodeUrl !== '' && $linuxNodeToken !== '';
}
?>

<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h1 class="h3 mb-0">Configuracoes</h1>
        <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('configuracoes')">
            <i class="bi bi-question-circle me-1"></i>Ajuda
        </button>
    </div>
</div>

<div class="card card-custom">
    <div class="card-body">
        <form id="form-configuracoes" action="<?= base_url('configuracoes/salvar') ?>" method="POST" enctype="multipart/form-data">
            <?= csrf_field() ?>

            <ul class="nav nav-tabs mb-4" id="configTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="aparencia-tab" data-bs-toggle="tab" data-bs-target="#tab-aparencia" type="button" role="tab">
                        <i class="bi bi-palette me-2"></i>Aparencia
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="empresa-tab" data-bs-toggle="tab" data-bs-target="#tab-empresa" type="button" role="tab">
                        <i class="bi bi-building me-2"></i>Dados da Empresa
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link d-flex align-items-center" id="integracoes-tab" data-bs-toggle="tab" data-bs-target="#tab-integracoes" type="button" role="tab">
                        <i class="bi bi-whatsapp me-2"></i>Integrações
                        <span id="tabBadgeStatus" class="ms-2 badge bg-secondary" style="font-size: 0.65rem;">...</span>
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="configTabsContent">
                <!-- Aba Aparencia -->
                <div class="tab-pane fade show active" id="tab-aparencia" role="tabpanel">
                    <h5 class="mb-3 border-bottom pb-2">Configurações Visuais</h5>
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Tema do Sistema <span class="text-danger">*</span></label>
                            <select class="form-select" name="tema" required>
                                <option value="dark" <?= ($configs['tema'] ?? 'dark') === 'dark' ? 'selected' : '' ?>>Escuro (Dark Theme)</option>
                                <option value="light" <?= ($configs['tema'] ?? 'dark') === 'light' ? 'selected' : '' ?>>Claro (Light Theme)</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Nome do Sistema na Tela de Login / Menu</label>
                            <input type="text" class="form-control" name="sistema_nome" value="<?= esc($configs['sistema_nome'] ?? 'AssistTech') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Logo do Sistema (Login/Menu)</label>
                            <input type="file" class="form-control" name="sistema_logo" accept="image/png, image/jpeg, image/gif, image/jpg">
                            <?php if (!empty($configs['sistema_logo'])): ?>
                                <small class="text-muted d-block mt-1">Logo atual: <?= esc($configs['sistema_logo']) ?></small>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Icone da Aba do Navegador (Favicon)</label>
                            <input type="file" class="form-control" name="sistema_icone" accept="image/png, image/jpeg, image/ico, image/x-icon">
                            <?php if (!empty($configs['sistema_icone'])): ?>
                                <small class="text-muted d-block mt-1">Icone atual: <?= esc($configs['sistema_icone']) ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Aba Empresa -->
                <div class="tab-pane fade" id="tab-empresa" role="tabpanel">
                    <h5 class="mb-3 border-bottom pb-2">Informações Jurídicas e Contato</h5>
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Nome da Empresa</label>
                            <input type="text" class="form-control" name="empresa_nome" value="<?= esc($configs['empresa_nome'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">CNPJ</label>
                            <input type="text" class="form-control cpf-cnpj" name="empresa_cnpj" value="<?= esc($configs['empresa_cnpj'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Telefone</label>
                            <input type="text" class="form-control telefone" name="empresa_telefone" value="<?= esc($configs['empresa_telefone'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Email</label>
                            <input type="email" class="form-control" name="empresa_email" value="<?= esc($configs['empresa_email'] ?? '') ?>">
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label text-muted">Endereco</label>
                            <input type="text" class="form-control" name="empresa_endereco" value="<?= esc($configs['empresa_endereco'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <!-- Aba Integracoes -->
                <div class="tab-pane fade" id="tab-integracoes" role="tabpanel">
                    <h5 class="mb-3 border-bottom pb-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <span>Configurações WhatsApp</span>
                        <div class="d-flex gap-2 align-items-center">
                            <span id="whatsRealtimeStatus" class="badge bg-secondary" style="cursor: pointer;" title="Clique para gerenciar">Verificando gateway...</span>
                            <span class="badge <?= $statusOk ? 'bg-success' : 'bg-danger' ?>" id="whatsConfigBadge">
                                <?= $statusOk ? 'Configuracao OK' : 'Incompleto' ?>
                            </span>
                        </div>
                    </h5>

                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <label class="form-label text-muted">Canal Direto</label>
                            <select class="form-select" name="whatsapp_direct_provider" id="whatsapp_direct_provider">
                                <option value="menuia" <?= $directProvider === 'menuia' ? 'selected' : '' ?>>Menuia</option>
                                <option value="api_whats_local" <?= $directProvider === 'api_whats_local' ? 'selected' : '' ?>>API Local (Windows)</option>
                                <option value="api_whats_linux" <?= $directProvider === 'api_whats_linux' ? 'selected' : '' ?>>API Linux (VPS)</option>
                                <option value="webhook" <?= $directProvider === 'webhook' ? 'selected' : '' ?>>Webhook Generico</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label text-muted">Canal Massa (futuro)</label>
                            <select class="form-select" name="whatsapp_bulk_provider">
                                <option value="meta_oficial" <?= ($configs['whatsapp_bulk_provider'] ?? 'meta_oficial') === 'meta_oficial' ? 'selected' : '' ?>>Meta Oficial (futuro)</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label text-muted">Envio habilitado</label>
                            <select class="form-select" name="whatsapp_enabled">
                                <option value="1" <?= ($configs['whatsapp_enabled'] ?? '0') === '1' ? 'selected' : '' ?>>Sim</option>
                                <option value="0" <?= ($configs['whatsapp_enabled'] ?? '0') !== '1' ? 'selected' : '' ?>>Nao</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label text-muted">Telefone de teste</label>
                            <input type="text" class="form-control" name="whatsapp_test_phone" id="whatsapp_test_phone" value="<?= esc($configs['whatsapp_test_phone'] ?? '') ?>" placeholder="5599999999999">
                        </div>

                        <div class="col-md-6 mb-3 config-menuia">
                            <label class="form-label text-muted">URL Menuia</label>
                            <input type="text" class="form-control" name="whatsapp_menuia_url" id="whatsapp_menuia_url" value="<?= esc($menuiaUrl) ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Webhook Token (inbound)</label>
                            <input type="text" class="form-control" name="whatsapp_webhook_token" value="<?= esc($configs['whatsapp_webhook_token'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3 config-menuia">
                            <label class="form-label text-muted">Appkey (Menuia)</label>
                            <input type="text" class="form-control" name="whatsapp_menuia_appkey" id="whatsapp_menuia_appkey" value="<?= esc($menuiaApp) ?>">
                        </div>
                        <div class="col-md-6 mb-3 config-menuia">
                            <label class="form-label text-muted">Authkey (Menuia)</label>
                            <input type="password" class="form-control" name="whatsapp_menuia_authkey" id="whatsapp_menuia_authkey" value="<?= esc($menuiaAuth) ?>">
                        </div>

                        <div class="col-12 mb-3 config-api_whats_local d-none">
                            <div class="row g-2">
                                <div class="col-md-5">
                                    <label class="form-label text-muted">URL API Local</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="whatsapp_local_node_url" id="whatsapp_local_node_url" value="<?= esc($configs['whatsapp_local_node_url'] ?? 'http://127.0.0.1:3001') ?>">
                                        <button class="btn btn-outline-primary btn-gerenciar-gateway" type="button" data-provider="api_whats_local">Gerenciar</button>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label text-muted">Token API</label>
                                    <input type="password" class="form-control" name="whatsapp_local_node_token" id="whatsapp_local_node_token" value="<?= esc($configs['whatsapp_local_node_token'] ?? '') ?>">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label text-muted">Origem ERP</label>
                                    <input type="text" class="form-control" name="whatsapp_local_node_origin" id="whatsapp_local_node_origin" value="<?= esc($configs['whatsapp_local_node_origin'] ?? base_url('/')) ?>">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label text-muted">Timeout (s)</label>
                                    <input type="number" min="5" max="90" class="form-control" name="whatsapp_local_node_timeout" id="whatsapp_local_node_timeout" value="<?= esc($configs['whatsapp_local_node_timeout'] ?? '20') ?>">
                                </div>
                            </div>
                        </div>

                        <div class="col-12 mb-3 config-api_whats_linux d-none">
                            <div class="row g-2">
                                <div class="col-md-5">
                                    <label class="form-label text-muted">URL API Linux</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="whatsapp_linux_node_url" id="whatsapp_linux_node_url" value="<?= esc($configs['whatsapp_linux_node_url'] ?? 'http://127.0.0.1:3001') ?>">
                                        <button class="btn btn-outline-primary btn-gerenciar-gateway" type="button" data-provider="api_whats_linux">Gerenciar</button>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label text-muted">Token API</label>
                                    <input type="password" class="form-control" name="whatsapp_linux_node_token" id="whatsapp_linux_node_token" value="<?= esc($configs['whatsapp_linux_node_token'] ?? '') ?>">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label text-muted">Origem ERP</label>
                                    <input type="text" class="form-control" name="whatsapp_linux_node_origin" id="whatsapp_linux_node_origin" value="<?= esc($configs['whatsapp_linux_node_origin'] ?? base_url('/')) ?>">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label text-muted">Timeout (s)</label>
                                    <input type="number" min="5" max="90" class="form-control" name="whatsapp_linux_node_timeout" id="whatsapp_linux_node_timeout" value="<?= esc($configs['whatsapp_linux_node_timeout'] ?? '20') ?>">
                                </div>
                            </div>
                        </div>

                        <div class="col-md-8 mb-3 config-webhook d-none">
                            <label class="form-label text-muted">URL Webhook</label>
                            <input type="text" class="form-control" name="whatsapp_webhook_url" id="whatsapp_webhook_url" value="<?= esc($configs['whatsapp_webhook_url'] ?? '') ?>">
                        </div>
                        <div class="col-md-4 mb-3 config-webhook d-none">
                            <label class="form-label text-muted">Metodo</label>
                            <select class="form-select" name="whatsapp_webhook_method" id="whatsapp_webhook_method">
                                <option value="POST" <?= ($configs['whatsapp_webhook_method'] ?? 'POST') === 'POST' ? 'selected' : '' ?>>POST</option>
                                <option value="GET" <?= ($configs['whatsapp_webhook_method'] ?? 'POST') === 'GET' ? 'selected' : '' ?>>GET</option>
                                <option value="PUT" <?= ($configs['whatsapp_webhook_method'] ?? 'POST') === 'PUT' ? 'selected' : '' ?>>PUT</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3 config-webhook d-none">
                            <label class="form-label text-muted">Headers (JSON)</label>
                            <textarea class="form-control" name="whatsapp_webhook_headers" id="whatsapp_webhook_headers" rows="2"><?= esc($configs['whatsapp_webhook_headers'] ?? '{}') ?></textarea>
                        </div>
                        <div class="col-md-6 mb-3 config-webhook d-none">
                            <label class="form-label text-muted">Payload Template (JSON)</label>
                            <textarea class="form-control" name="whatsapp_webhook_payload" id="whatsapp_webhook_payload" rows="2"><?= esc($configs['whatsapp_webhook_payload'] ?? '{"to":"{{phone}}","message":"{{message}}"}') ?></textarea>
                        </div>

                        <div class="col-12 d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-outline-primary" id="btnTestarConexaoWhats">Testar conexao</button>
                            <button type="button" class="btn btn-outline-success" id="btnEnviarTesteWhats">Enviar mensagem de teste</button>
                            <button type="button" class="btn btn-outline-warning" id="btnSelfCheckInboundWhats" title="Valida automaticamente status do gateway, token do webhook, URL inbound e alinhamento de origem ERP."><i class="bi bi-shield-check me-1"></i>Self-check inbound</button>
                        </div>
                        <div class="col-12">
                            <small class="text-muted d-block mt-1">
                                <i class="bi bi-info-circle me-1"></i>
                                O self-check inbound testa automaticamente a rota de entrada do WhatsApp (gateway -> webhook ERP), token e host/origem, sem usar console.
                            </small>
                        </div>
                    </div>
                </div>

            </div>

            <div class="text-end border-top pt-3">
                <button type="submit" class="btn btn-primary btn-glow px-5"><i class="bi bi-save me-2"></i>Salvar Tudo</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalLocalGateway" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white border-0">
                <h5 class="modal-title d-flex align-items-center gap-2">
                    <i class="bi bi-whatsapp"></i>
                    Gerenciar Gateway
                    <span id="gatewayProviderBadge" class="badge text-bg-secondary">API Local (Windows)</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div id="localGatewayLoading" class="py-4 text-center">
                    <div class="spinner-border text-primary"></div>
                    <p class="mt-3 text-muted mb-0">Consultando status do gateway...</p>
                </div>

                <div id="localGatewayPanel" class="d-none">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="small text-muted">Status:</span>
                            <span id="gatewayStatusBadge" class="badge text-bg-secondary">-</span>
                        </div>
                        <small class="text-muted">Ultima verificacao: <span id="gatewayLastCheck">-</span></small>
                    </div>

                    <div class="row g-3 align-items-start">
                        <div class="col-lg-5 text-center">
                            <p class="small text-muted mb-2">QR Code para autenticacao</p>
                            <div class="bg-light p-3 rounded border mx-auto" style="width:256px;height:256px;display:flex;align-items:center;justify-content:center;">
                                <img id="localQrImage" src="" alt="QR" class="img-fluid d-none">
                                <div id="localQrPlaceholder" class="text-muted small px-2">Aguardando QR Code...</div>
                            </div>
                            <small id="gatewayQrHint" class="text-muted d-block mt-2">Escaneie o QR no WhatsApp para manter sessao ativa.</small>
                        </div>
                        <div class="col-lg-7">
                            <div class="border rounded p-3 bg-light-subtle small">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Conta:</span>
                                    <strong id="gatewayAccountName">-</strong>
                                </div>
                                <div class="d-flex justify-content-between mt-1">
                                    <span class="text-muted">Numero:</span>
                                    <span id="gatewayAccountNumber">-</span>
                                </div>
                                <div class="d-flex justify-content-between mt-1">
                                    <span class="text-muted">Plataforma:</span>
                                    <span id="gatewayAccountPlatform">-</span>
                                </div>
                                <hr class="my-2">
                                <div class="d-flex justify-content-between mt-1">
                                    <span class="text-muted">Ultimo ready:</span>
                                    <span id="gatewayLastReady">-</span>
                                </div>
                                <div class="d-flex justify-content-between mt-1">
                                    <span class="text-muted">Ultimo erro:</span>
                                    <span id="gatewayLastError">-</span>
                                </div>
                                <div class="d-flex justify-content-between mt-1">
                                    <span class="text-muted">Sessao:</span>
                                    <span id="gatewaySessionPath">-</span>
                                </div>
                            </div>
                            <div id="localGatewayErrorDetails" class="alert alert-danger mt-3 py-2 px-3 d-none mb-0 small"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary" id="btnRefreshLocal">
                    <i class="bi bi-arrow-repeat me-1"></i>Atualizar status
                </button>
                <button type="button" class="btn btn-outline-danger d-none" id="btnLogoutLocal">
                    <i class="bi bi-person-x me-1"></i>Desconectar / Trocar Numero
                </button>
                <button type="button" class="btn btn-success d-none" id="btnStartLocal">
                    <i class="bi bi-play-fill me-1"></i>Iniciar Servidor
                </button>
                <button type="button" class="btn btn-outline-warning" id="btnRestartLocal">
                    <i class="bi bi-bootstrap-reboot me-1"></i>Reiniciar Inicializacao
                </button>
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(() => {
    const selectProvider = document.getElementById('whatsapp_direct_provider');
    const btnTestConn = document.getElementById('btnTestarConexaoWhats');
    const btnSendTest = document.getElementById('btnEnviarTesteWhats');
    const btnSelfCheckInbound = document.getElementById('btnSelfCheckInboundWhats');
    const modalEl = document.getElementById('modalLocalGateway');
    const btnRestart = document.getElementById('btnRestartLocal');
    const btnLogout = document.getElementById('btnLogoutLocal');
    const btnStart = document.getElementById('btnStartLocal');
    const btnRefresh = document.getElementById('btnRefreshLocal');
    const whatsRealtimeStatus = document.getElementById('whatsRealtimeStatus');
    const btnManageList = Array.from(document.querySelectorAll('.btn-gerenciar-gateway'));
    let currentGatewayProvider = 'api_whats_local';
    let pollInterval = null;
    let modalRef = null;

    const fireSwal = (opts) => (window.Swal && typeof window.Swal.fire === 'function') ? window.Swal.fire(opts) : Promise.resolve(alert(opts?.text || opts?.title || 'Acao concluida.'));
    const getCsrf = () => ({ name: 'csrf_test_name', value: (document.cookie.match(/(?:^|;\s*)csrf_cookie_name=([^;]+)/)?.[1] || '') });
    const byId = (id) => document.getElementById(id);

    const gatewayStatusBadge = byId('gatewayStatusBadge');
    const gatewayProviderBadge = byId('gatewayProviderBadge');
    const gatewayLastCheck = byId('gatewayLastCheck');
    const gatewayAccountName = byId('gatewayAccountName');
    const gatewayAccountNumber = byId('gatewayAccountNumber');
    const gatewayAccountPlatform = byId('gatewayAccountPlatform');
    const gatewayLastReady = byId('gatewayLastReady');
    const gatewayLastError = byId('gatewayLastError');
    const gatewaySessionPath = byId('gatewaySessionPath');
    const gatewayQrHint = byId('gatewayQrHint');
    const localGatewayErrorDetails = byId('localGatewayErrorDetails');
    const localGatewayLoading = byId('localGatewayLoading');
    const localGatewayPanel = byId('localGatewayPanel');
    const localQrImage = byId('localQrImage');
    const localQrPlaceholder = byId('localQrPlaceholder');

    const providerLabel = (provider) => {
        if (provider === 'api_whats_linux') return 'API Linux (VPS)';
        if (provider === 'api_whats_local') return 'API Local (Windows)';
        return provider || '-';
    };

    const formatDate = (value) => {
        if (!value) return '-';
        const dt = new Date(value);
        if (Number.isNaN(dt.getTime())) return String(value);
        return dt.toLocaleString('pt-BR');
    };

    const toggleProviders = () => {
        const provider = selectProvider?.value || 'menuia';
        document.querySelectorAll('.config-menuia').forEach((el) => el.classList.toggle('d-none', provider !== 'menuia'));
        document.querySelectorAll('.config-webhook').forEach((el) => el.classList.toggle('d-none', provider !== 'webhook'));
        document.querySelectorAll('.config-api_whats_local').forEach((el) => el.classList.toggle('d-none', provider !== 'api_whats_local'));
        document.querySelectorAll('.config-api_whats_linux').forEach((el) => el.classList.toggle('d-none', provider !== 'api_whats_linux'));
    };
    selectProvider?.addEventListener('change', toggleProviders);
    toggleProviders();

    const providerPayload = () => ({
        provider: selectProvider?.value || 'menuia',
        telefone: document.getElementById('whatsapp_test_phone')?.value || '',
        appkey: document.getElementById('whatsapp_menuia_appkey')?.value || '',
        authkey: document.getElementById('whatsapp_menuia_authkey')?.value || '',
        url: document.getElementById('whatsapp_menuia_url')?.value || '',
        webhook_url: document.getElementById('whatsapp_webhook_url')?.value || '',
        webhook_method: document.getElementById('whatsapp_webhook_method')?.value || 'POST',
        webhook_headers: document.getElementById('whatsapp_webhook_headers')?.value || '',
        webhook_payload: document.getElementById('whatsapp_webhook_payload')?.value || '',
        local_url: document.getElementById('whatsapp_local_node_url')?.value || '',
        local_token: document.getElementById('whatsapp_local_node_token')?.value || '',
        local_origin: document.getElementById('whatsapp_local_node_origin')?.value || '',
        local_timeout: document.getElementById('whatsapp_local_node_timeout')?.value || '',
        linux_url: document.getElementById('whatsapp_linux_node_url')?.value || '',
        linux_token: document.getElementById('whatsapp_linux_node_token')?.value || '',
        linux_origin: document.getElementById('whatsapp_linux_node_origin')?.value || '',
        linux_timeout: document.getElementById('whatsapp_linux_node_timeout')?.value || '',
    });

    const postJson = async (url, payload) => {
        const fd = new FormData();
        Object.entries(payload).forEach(([k, v]) => fd.append(k, v ?? ''));
        const csrf = getCsrf();
        if (csrf.value) fd.append(csrf.name, decodeURIComponent(csrf.value));
        const res = await fetch(url, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd });
        const data = await res.json().catch(() => ({}));
        if (!res.ok || !(data.ok || data.success)) {
            const err = new Error(data.message || 'Falha na requisicao');
            err.payload = data;
            throw err;
        }
        return data;
    };

    btnTestConn?.addEventListener('click', async () => {
        const originalHtml = btnTestConn.innerHTML;
        try {
            btnTestConn.disabled = true;
            btnTestConn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Validando...';
            const data = await postJson('<?= base_url('configuracoes/whatsapp/testar-conexao') ?>', providerPayload());
            await fireSwal({ icon: 'success', title: 'Conexao validada', text: data.message || 'OK' });
        } catch (error) {
            await fireSwal({ icon: 'error', title: 'Falha na conexao', text: error.message || 'Erro' });
        } finally {
            btnTestConn.disabled = false;
            btnTestConn.innerHTML = originalHtml;
        }
    });

    btnSendTest?.addEventListener('click', async () => {
        const phone = (byId('whatsapp_test_phone')?.value || '').trim();
        if (!phone) {
            await fireSwal({ icon: 'warning', title: 'Telefone obrigatorio', text: 'Informe o telefone de teste.' });
            return;
        }
        const dataPrompt = (window.Swal && typeof window.Swal.fire === 'function')
            ? await window.Swal.fire({ title: 'Mensagem de teste', input: 'textarea', showCancelButton: true, confirmButtonText: 'Enviar', cancelButtonText: 'Cancelar', inputValue: '[Teste de integracao] Mensagem de teste enviada pelo ERP.' })
            : { isConfirmed: true, value: '[Teste de integracao] Mensagem de teste enviada pelo ERP.' };
        
        if (!dataPrompt?.isConfirmed) return;

        const originalHtml = btnSendTest.innerHTML;
        try {
            btnSendTest.disabled = true;
            btnSendTest.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Enviando...';
            const data = await postJson('<?= base_url('configuracoes/whatsapp/enviar-teste') ?>', { ...providerPayload(), telefone: phone, mensagem: dataPrompt.value || '' });
            await fireSwal({ icon: 'success', title: 'Mensagem enviada', text: data.message || 'OK' });
        } catch (error) {
            await fireSwal({ icon: 'error', title: 'Falha no envio', text: error.message || 'Erro' });
        } finally {
            btnSendTest.disabled = false;
            btnSendTest.innerHTML = originalHtml;
        }
    });

    btnSelfCheckInbound?.addEventListener('click', async () => {
        const provider = selectProvider?.value || 'menuia';
        if (!['api_whats_local', 'api_whats_linux'].includes(provider)) {
            await fireSwal({
                icon: 'warning',
                title: 'Provider nao compativel',
                text: 'O self-check inbound exige API Local (Windows) ou API Linux (VPS).',
            });
            return;
        }

        const originalHtml = btnSelfCheckInbound.innerHTML;
        try {
            btnSelfCheckInbound.disabled = true;
            btnSelfCheckInbound.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Validando inbound...';

            const data = await postJson('<?= base_url('configuracoes/whatsapp/self-check-inbound') ?>', providerPayload());
            const checks = data?.checks || {};
            const checkLine = (label, check) => {
                const ok = !!check?.ok;
                const icon = ok ? '✅' : '❌';
                const msg = check?.message || '';
                const detail = check?.detail || '';
                const targetUrl = check?.target_url || check?.url || '';
                const detailHtml = detail ? `<div class="text-muted" style="font-size:0.78rem;">Detalhe: ${detail}</div>` : '';
                const targetHtml = targetUrl ? `<div class="text-muted" style="font-size:0.78rem;">URL: ${targetUrl}</div>` : '';
                return `<div class="mb-1">${icon} <strong>${label}</strong>${msg ? ` - ${msg}` : ''}${targetHtml}${detailHtml}</div>`;
            };

            const html = `
                <div class="text-start small">
                    ${checkLine('Gateway /status', checks.gateway_status)}
                    ${checkLine('Gateway -> ERP (/self-check-inbound)', checks.gateway_forward)}
                    ${checkLine('Webhook direto no ERP', checks.webhook_direct)}
                    ${checkLine('Alinhamento de origem (ERP_ORIGIN)', checks.origin_alignment)}
                </div>
            `;

            await fireSwal({
                icon: 'success',
                title: 'Inbound validado',
                html,
            });
        } catch (error) {
            let detailsHtml = '';
            const checks = error?.payload?.checks || {};
            if (Object.keys(checks).length > 0) {
                const checkLine = (label, check) => {
                    const ok = !!check?.ok;
                    const icon = ok ? '✅' : '❌';
                    const msg = check?.message || '';
                    const detail = check?.detail || '';
                    const targetUrl = check?.target_url || check?.url || '';
                    const detailHtml = detail ? `<div class="text-muted" style="font-size:0.78rem;">Detalhe: ${detail}</div>` : '';
                    const targetHtml = targetUrl ? `<div class="text-muted" style="font-size:0.78rem;">URL: ${targetUrl}</div>` : '';
                    return `<div class="mb-1">${icon} <strong>${label}</strong>${msg ? ` - ${msg}` : ''}${targetHtml}${detailHtml}</div>`;
                };
                detailsHtml = `
                    <div class="text-start small mt-2">
                        ${checkLine('Gateway /status', checks.gateway_status)}
                        ${checkLine('Gateway -> ERP (/self-check-inbound)', checks.gateway_forward)}
                        ${checkLine('Webhook direto no ERP', checks.webhook_direct)}
                        ${checkLine('Alinhamento de origem (ERP_ORIGIN)', checks.origin_alignment)}
                    </div>
                `;
            }

            await fireSwal({
                icon: 'error',
                title: 'Self-check inbound falhou',
                html: `<div>${error.message || 'Falha na validacao inbound.'}</div>${detailsHtml}`,
            });
        } finally {
            btnSelfCheckInbound.disabled = false;
            btnSelfCheckInbound.innerHTML = originalHtml;
        }
    });

    const setGatewayError = (message = '', isCritical = true) => {
        if (!localGatewayErrorDetails) return;
        if (!message) {
            localGatewayErrorDetails.classList.add('d-none');
            localGatewayErrorDetails.textContent = '';
            return;
        }
        localGatewayErrorDetails.textContent = message;
        localGatewayErrorDetails.classList.remove('d-none');
        localGatewayErrorDetails.className = 'alert mt-3 py-2 px-3 mb-0 small ' + (isCritical ? 'alert-danger' : 'alert-info');
    };

    const setGatewayStatusBadge = (status, isGlobal = false) => {
        const target = isGlobal ? whatsRealtimeStatus : gatewayStatusBadge;
        const navTabBadge = document.getElementById('tabBadgeStatus');
        if (!target) return;
        
        const key = String(status || 'unknown').toLowerCase();
        const map = {
            connected: { cls: 'text-bg-success', label: 'Conectado', icon: 'bi-check-circle-fill' },
            awaiting_qr: { cls: 'text-bg-warning', label: 'Aguardando QR', icon: 'bi-qr-code' },
            authenticated: { cls: 'text-bg-info', label: 'Autenticado', icon: 'bi-shield-check' },
            disconnected: { cls: 'text-bg-secondary', label: 'Desconectado', icon: 'bi-x-circle' },
            restarting: { cls: 'text-bg-primary', label: 'Reiniciando', icon: 'bi-arrow-repeat spin' },
            starting: { cls: 'text-bg-primary', label: 'Inicializando', icon: 'bi-hourglass-split' },
            auth_failure: { cls: 'text-bg-danger', label: 'Falha Auth', icon: 'bi-exclamation-triangle' },
            gateway_unreachable: { cls: 'text-bg-danger', label: 'Offline / Inacessivel', icon: 'bi-plug' },
            error: { cls: 'text-bg-danger', label: 'Erro', icon: 'bi-bug' },
        };
        const cfg = map[key] || { cls: 'text-bg-dark', label: status || 'desconhecido', icon: 'bi-question-circle' };
        
        if (isGlobal) {
            target.className = 'badge ' + (cfg.cls.replace('text-bg-', 'bg-'));
            if (navTabBadge) {
                navTabBadge.className = 'ms-2 badge ' + (cfg.cls.replace('text-bg-', 'bg-'));
                navTabBadge.textContent = cfg.label;
            }
        } else {
            target.className = 'badge ' + cfg.cls;
        }
        target.innerHTML = `<i class="bi ${cfg.icon} me-1"></i>${cfg.label}`;
    };

    const setLoadingState = () => {
        localGatewayLoading?.classList.remove('d-none');
        localGatewayPanel?.classList.add('d-none');
    };

    const setPanelState = () => {
        localGatewayLoading?.classList.add('d-none');
        localGatewayPanel?.classList.remove('d-none');
    };

    const clearQr = (placeholderText) => {
        if (localQrImage) {
            localQrImage.src = '';
            localQrImage.classList.add('d-none');
        }
        if (localQrPlaceholder) {
            localQrPlaceholder.textContent = placeholderText || 'Aguardando QR Code...';
            localQrPlaceholder.classList.remove('d-none');
        }
    };

    const applyGatewayData = (statusPayload, qrData = null) => {
        const status = statusPayload?.status || 'unknown';
        const data = statusPayload?.data || {};
        const account = data?.account || {};
        const success = !!statusPayload?.success;
        
        setPanelState();
        setGatewayStatusBadge(status);
        
        // Só mostramos erro se houver uma mensagem de erro real ou se a query falhou
        let errorMsg = data?.last_error_message || '';
        if (!errorMsg && !success) {
            errorMsg = statusPayload?.message || 'Gateway inacessivel';
        }
        setGatewayError(errorMsg, !success || !!data?.last_error_message);

        if (gatewayLastCheck) gatewayLastCheck.textContent = new Date().toLocaleString('pt-BR');
        if (gatewayAccountName) gatewayAccountName.textContent = account.pushname || '-';
        if (gatewayAccountNumber) gatewayAccountNumber.textContent = account.number || '-';
        if (gatewayAccountPlatform) gatewayAccountPlatform.textContent = account.platform || '-';
        if (gatewayLastReady) gatewayLastReady.textContent = formatDate(data?.last_ready_at);
        if (gatewayLastError) gatewayLastError.textContent = formatDate(data?.last_error_at);
        if (gatewaySessionPath) gatewaySessionPath.textContent = data?.session_path || '-';

        updateButtons(status, !!(qrData?.data?.qr || data?.qr), qrData?.data?.qr || data?.qr || '');
        setGatewayStatusBadge(status, true);
    };

    const updateButtons = (status, hasQr = false, finalQr = '') => {
        if (status === 'gateway_unreachable') {
            btnStart?.classList.remove('d-none');
        } else {
            btnStart?.classList.add('d-none');
        }

        if (status === 'connected') {
            btnLogout?.classList.remove('d-none');
            clearQr('Conectado. QR nao necessario.');
            if (localQrImage) {
                localQrImage.src = '<?= base_url('assets/img/sistema/whatsapp_connected_success.png') ?>';
                localQrImage.classList.remove('d-none');
            }
            if (localQrPlaceholder) localQrPlaceholder.classList.add('d-none');
            if (gatewayQrHint) gatewayQrHint.textContent = 'Sessao ativa e pronta para envio.';
        } else {
            btnLogout?.classList.add('d-none');
            if (hasQr && localQrImage && localQrPlaceholder) {
                localQrImage.src = finalQr;
                localQrImage.classList.remove('d-none');
                localQrPlaceholder.classList.add('d-none');
                if (gatewayQrHint) gatewayQrHint.textContent = 'Escaneie o QR Code para autenticar o WhatsApp.';
            } else {
                clearQr('QR indisponivel no momento. Aguarde ou reinicie.');
                if (gatewayQrHint) gatewayQrHint.textContent = 'Se o QR demorar, use Reiniciar Inicializacao.';
            }
        }

        // Resetar estados de carregamento manuais se o status for estável
        const stableStatuses = ['connected', 'awaiting_qr', 'disconnected', 'gateway_unreachable', 'error', 'auth_failure'];
        if (stableStatuses.includes(status)) {
            if (btnRestart && btnRestart.disabled) {
                btnRestart.disabled = false;
                btnRestart.innerHTML = '<i class="bi bi-bootstrap-reboot me-1"></i>Reiniciar Inicializacao';
            }
            if (btnLogout && btnLogout.disabled) {
                btnLogout.disabled = false;
                btnLogout.innerHTML = '<i class="bi bi-person-x me-1"></i>Desconectar / Trocar Numero';
            }
            if (btnStart && btnStart.disabled) {
                btnStart.disabled = false;
                btnStart.innerHTML = '<i class="bi bi-play-fill me-1"></i>Iniciar Servidor';
            }
        }
    };

    const statusUrl = () => '<?= base_url('configuracoes/whatsapp/local-status') ?>?provider=' + encodeURIComponent(currentGatewayProvider);
    const qrUrl = () => '<?= base_url('configuracoes/whatsapp/local-qr') ?>?provider=' + encodeURIComponent(currentGatewayProvider);

    const fetchStatus = async () => {
        try {
            const res = await fetch(statusUrl(), { cache: 'no-store' });
            const statusPayload = await res.json().catch(() => ({}));
            if (!res.ok || !statusPayload?.success) {
                setPanelState();
                setGatewayStatusBadge(statusPayload?.status || 'gateway_unreachable');
                setGatewayError(statusPayload?.message || 'Falha ao consultar status do gateway.');
                clearQr('Servidor inacessivel.');
                if (gatewayQrHint) gatewayQrHint.textContent = 'Verifique URL/token/origem do gateway.';
                updateButtons('gateway_unreachable');
                return;
            }

            let qrPayload = null;
            if (!statusPayload?.data?.qr && !statusPayload?.data?.ready) {
                const q = await fetch(qrUrl(), { cache: 'no-store' });
                qrPayload = await q.json().catch(() => null);
                if (q.ok && qrPayload?.success) {
                    setGatewayError('');
                } else if (statusPayload?.status === 'awaiting_qr') {
                    setGatewayError(qrPayload?.message || statusPayload?.message || 'QR ainda nao gerado.');
                }
            }
            applyGatewayData(statusPayload, qrPayload);
        } catch (e) {
            setPanelState();
            setGatewayStatusBadge('gateway_unreachable');
            setGatewayError(e?.message || 'Servidor do gateway inacessivel.');
            clearQr('Servidor inacessivel.');
            if (gatewayQrHint) gatewayQrHint.textContent = 'Confirme processo Node em execucao e token correto.';
            updateButtons('gateway_unreachable');
            setGatewayStatusBadge('gateway_unreachable', true);
        }
    };

    whatsRealtimeStatus?.addEventListener('click', () => {
        const provider = selectProvider?.value;
        if (provider && provider.includes('api_whats')) {
            const btn = document.querySelector(`.btn-gerenciar-gateway[data-provider="${provider}"]`);
            if (btn) btn.click();
        } else {
            fireSwal({ icon: 'info', title: 'Aviso', text: 'Selecione um provider local para gerenciar.' });
        }
    });

    if (selectProvider?.value.includes('api_whats')) {
        currentGatewayProvider = selectProvider.value;
        fetchStatus();
    } else {
        if (whatsRealtimeStatus) {
            whatsRealtimeStatus.className = 'badge bg-dark';
            whatsRealtimeStatus.innerHTML = '<i class="bi bi-info-circle me-1"></i>Provider Externo';
        }
    }

    btnManageList.forEach((btn) => {
        btn.addEventListener('click', () => {
            currentGatewayProvider = btn.dataset.provider || 'api_whats_local';
            if (gatewayProviderBadge) {
                gatewayProviderBadge.textContent = providerLabel(currentGatewayProvider);
            }
            if (!modalRef) {
                modalRef = new bootstrap.Modal(modalEl);
            }
            modalRef.show();
            setGatewayError('');
            setLoadingState();
            fetchStatus();
            if (pollInterval) clearInterval(pollInterval);
            pollInterval = setInterval(fetchStatus, 2500);
        });
    });

    modalEl?.addEventListener('hidden.bs.modal', () => {
        if (pollInterval) clearInterval(pollInterval);
        pollInterval = null;
        clearQr('Aguardando QR Code...');
        setGatewayError('');
        setLoadingState();
    });

    btnRefresh?.addEventListener('click', () => {
        setLoadingState();
        fetchStatus();
    });

    btnRestart?.addEventListener('click', async () => {
        const conf = await fireSwal({
            title: 'Reiniciar Gateway?',
            text: 'Deseja apenas reiniciar o processo ou tambem zerar os arquivos de sessao (limpeza profunda)? Zerar a sessao exigira uma nova leitura de QR Code.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Limpeza Profunda (Zerar)',
            cancelButtonText: 'Apenas Reiniciar',
            denyButtonText: 'Cancelar',
            showDenyButton: true,
            confirmButtonColor: '#dc3545', // vermelho para zerar
            cancelButtonColor: '#6c757d'   // cinza para apenas reiniciar
        });

        if (conf.isDenied) return; // Clicou no Cancelar (denyButton)

        const isClean = conf.isConfirmed; // isConfirmed = Limpeza profunda
        // Note: Swal fire results: isConfirmed (confirmButton), isDenied (denyButton), isDismissed (cancelButton)
        // Wait, default Swal logic: isConfirmed=true (confirm), isDismissed=true e dismiss='cancel' (cancel), isDenied=true (deny)
        // Re-adjusting for clarity:
        
        let cleanParam = false;
        if (conf.isConfirmed) cleanParam = true;  // Limpeeza profunda
        else if (conf.isDismissed && conf.dismiss === 'cancel') cleanParam = false; // Apenas reiniciar
        else return; // Fechou o modal sem escolher

        const originalHtml = btnRestart.innerHTML;
        try {
            btnRestart.disabled = true;
            btnRestart.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> ' + (cleanParam ? 'Limpando...' : 'Reiniciando...');
            await postJson('<?= base_url('configuracoes/whatsapp/local-restart') ?>', { provider: currentGatewayProvider, clean: cleanParam ? 'true' : 'false' });
            setLoadingState();
            setGatewayError('');
            setTimeout(fetchStatus, 3000);
        } catch (error) {
            await fireSwal({ icon: 'error', title: 'Falha no reinicio', text: error.message || 'Erro ao reiniciar' });
            btnRestart.disabled = false;
            btnRestart.innerHTML = originalHtml;
        }
    });

    btnLogout?.addEventListener('click', async () => {
        const conf = await fireSwal({ title: 'Desconectar WhatsApp?', text: 'Isso ira encerrar a sessao atual e gerar um novo QR Code para vincular outro numero. Tem certeza?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Sim, desconectar', cancelButtonText: 'Nao' });
        if (!conf.isConfirmed) return;
        
        const originalHtml = btnLogout.innerHTML;
        try {
            btnLogout.disabled = true;
            btnLogout.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Desconectando...';
            await postJson('<?= base_url('configuracoes/whatsapp/local-logout') ?>', { provider: currentGatewayProvider });
            setLoadingState();
            setGatewayError('');
            setTimeout(fetchStatus, 3000);
        } catch (error) {
            await fireSwal({ icon: 'error', title: 'Falha no logout', text: error.message || 'Erro ao deslogar' });
            btnLogout.disabled = false;
            btnLogout.innerHTML = originalHtml;
        }
    });

    btnStart?.addEventListener('click', async () => {
        const originalHtml = btnStart.innerHTML;
        try {
            btnStart.disabled = true;
            btnStart.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Iniciando...';
            const data = await postJson('<?= base_url('configuracoes/whatsapp/local-start') ?>', { provider: currentGatewayProvider });
            fireSwal({ icon: 'info', title: 'Comando enviado', text: data.message || 'Aguarde o servidor subir.' });
            setLoadingState();
            setTimeout(fetchStatus, 5000);
        } catch (error) {
            await fireSwal({ icon: 'error', title: 'Falha ao iniciar', text: error.message || 'Erro ao enviar comando de boot' });
            btnStart.disabled = false;
            btnStart.innerHTML = originalHtml;
        }
    });
})();
</script>
<?= $this->endSection() ?>
