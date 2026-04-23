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

$menuiaUrl = trim((string) ($configs['whatsapp_menuia_url'] ?? 'https://chatbot.menuia.com/api'));
$menuiaAuth = trim((string) ($configs['whatsapp_menuia_authkey'] ?? ''));
$menuiaApp = trim((string) ($configs['whatsapp_menuia_appkey'] ?? ''));
$localNodeUrl = trim((string) ($configs['whatsapp_local_node_url'] ?? 'http://127.0.0.1:3001'));
$localNodeToken = trim((string) ($configs['whatsapp_local_node_token'] ?? ''));
$linuxNodeUrl = trim((string) ($configs['whatsapp_linux_node_url'] ?? 'http://127.0.0.1:3001'));
$linuxNodeToken = trim((string) ($configs['whatsapp_linux_node_token'] ?? ''));
$lastCheckProvider = trim((string) ($configs['whatsapp_last_check_provider'] ?? ''));
$lastCheckStatus = trim((string) ($configs['whatsapp_last_check_status'] ?? ''));
$lastCheckMessage = trim((string) ($configs['whatsapp_last_check_message'] ?? ''));
$lastCheckAt = trim((string) ($configs['whatsapp_last_check_at'] ?? ''));
$lastCheckSignature = trim((string) ($configs['whatsapp_last_check_signature'] ?? ''));
$menuiaCredentialSignature = ($menuiaUrl !== '' && $menuiaApp !== '' && $menuiaAuth !== '')
    ? strtolower($menuiaUrl) . '|' . $menuiaApp . '|' . $menuiaAuth
    : '';
$menuiaStatusMatchesCurrentCredentials = $menuiaCredentialSignature !== ''
    && $lastCheckSignature !== ''
    && hash_equals($lastCheckSignature, $menuiaCredentialSignature);

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

$whatsConfigBadgeClass = $statusOk ? 'bg-success' : 'bg-danger';
$whatsConfigBadgeText = $statusOk
    ? ($directProvider === 'menuia' ? 'Credenciais OK' : 'Configuração OK')
    : 'Incompleto';

$realtimeBadgeClass = 'bg-secondary';
$realtimeBadgeText = 'Não validado';
$realtimeBadgeTitle = 'Ainda não houve validação de conectividade para o provider atual.';
if (!$enabled) {
    $realtimeBadgeClass = 'bg-secondary';
    $realtimeBadgeText = 'Envio desabilitado';
    $realtimeBadgeTitle = 'O envio de WhatsApp está desabilitado nas configurações.';
} elseif (in_array($directProvider, ['api_whats_local', 'api_whats_linux'], true)) {
    $realtimeBadgeClass = 'bg-dark';
    $realtimeBadgeText = 'Provider local';
    $realtimeBadgeTitle = 'Clique para gerenciar o gateway local.';
} elseif ($directProvider === 'menuia' && $lastCheckProvider === 'menuia' && $menuiaStatusMatchesCurrentCredentials) {
    if ($lastCheckStatus === 'success') {
        $realtimeBadgeClass = 'bg-success';
        $realtimeBadgeText = 'Menuia conectada';
        $realtimeBadgeTitle = trim('Última validação: ' . $lastCheckAt . ' - ' . $lastCheckMessage);
    } elseif ($lastCheckStatus === 'error') {
        $realtimeBadgeClass = 'bg-danger';
        $realtimeBadgeText = 'Erro Menuia';
        $realtimeBadgeTitle = trim('Última validação: ' . $lastCheckAt . ' - ' . $lastCheckMessage);
    }
} elseif ($directProvider === 'menuia' && $statusOk) {
    $realtimeBadgeClass = 'bg-warning text-dark';
    $realtimeBadgeText = 'Menuia não validada';
    $realtimeBadgeTitle = 'As credenciais estão preenchidas, mas a conexão ainda não foi validada.';
} elseif ($directProvider === 'webhook') {
    $realtimeBadgeClass = 'bg-dark';
    $realtimeBadgeText = 'Provider externo';
    $realtimeBadgeTitle = 'Webhook externo selecionado.';
}

$precificacaoEncargos = (string) ($configs['precificacao_peca_encargos_percentual'] ?? '15');
$precificacaoMargem = (string) ($configs['precificacao_peca_margem_percentual'] ?? '45');
$precificacaoBase = strtolower(trim((string) ($configs['precificacao_peca_base'] ?? 'custo')));
if (!in_array($precificacaoBase, ['custo', 'venda'], true)) {
    $precificacaoBase = 'custo';
}
$precificacaoRespeitarVenda = (string) ($configs['precificacao_peca_respeitar_preco_venda'] ?? '1') !== '0';
?>

<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h1 class="h3 mb-0">Configurações</h1>
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
                        <i class="bi bi-palette me-2"></i>Aparência
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="empresa-tab" data-bs-toggle="tab" data-bs-target="#tab-empresa" type="button" role="tab">
                        <i class="bi bi-building me-2"></i>Dados da Empresa
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="sessao-tab" data-bs-toggle="tab" data-bs-target="#tab-sessao" type="button" role="tab">
                        <i class="bi bi-shield-lock me-2"></i>Sessão e Segurança
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="precificacao-tab" data-bs-toggle="tab" data-bs-target="#tab-precificacao" type="button" role="tab">
                        <i class="bi bi-calculator me-2"></i>Precificação
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
                <!-- Aba Aparência -->
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
                            <label class="form-label text-muted">Logo de Fundo dos Documentos PDF</label>
                            <input type="file" class="form-control" name="pdf_logo_fundo" accept="image/png">
                            <small class="text-muted d-block mt-1">
                                Use PNG sem fundo. O arquivo será salvo em <code>public/uploads/sistema</code> e usado como marca d'água em todos os PDFs.
                            </small>
                            <?php if (!empty($configs['pdf_logo_fundo'])): ?>
                                <small class="text-muted d-block mt-1">Logo PDF atual: <?= esc($configs['pdf_logo_fundo']) ?></small>
                                <div class="mt-2 p-2 border rounded bg-light d-inline-flex align-items-center justify-content-center" style="min-height: 90px; min-width: 180px;">
                                    <img
                                        src="<?= base_url('uploads/sistema/' . $configs['pdf_logo_fundo']) ?>"
                                        alt="Preview da logo de fundo PDF"
                                        style="max-height: 72px; max-width: 160px; object-fit: contain;"
                                    >
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Ícone da Aba do Navegador (Favicon)</label>
                            <input type="file" class="form-control" name="sistema_icone" accept="image/png, image/jpeg, image/ico, image/x-icon">
                            <?php if (!empty($configs['sistema_icone'])): ?>
                                <small class="text-muted d-block mt-1">Ícone atual: <?= esc($configs['sistema_icone']) ?></small>
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
                            <label class="form-label text-muted">Endereço</label>
                            <input type="text" class="form-control" name="empresa_endereco" value="<?= esc($configs['empresa_endereco'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <!-- Aba Sessão -->
                <div class="tab-pane fade" id="tab-sessao" role="tabpanel">
                    <h5 class="mb-3 border-bottom pb-2">Sessão e Segurança Operacional</h5>
                    <div class="row mb-4">
                        <div class="col-lg-5 col-md-6 mb-3">
                            <label class="form-label text-muted">Tempo máximo de inatividade (minutos)</label>
                            <input
                                type="number"
                                class="form-control"
                                name="sessao_inatividade_minutos"
                                min="5"
                                max="1440"
                                step="5"
                                value="<?= esc($configs['sessao_inatividade_minutos'] ?? '30') ?>"
                            >
                            <small class="text-muted d-block mt-2">
                                Após esse período sem atividade real, a sessão é encerrada e o sistema avisa claramente antes de o usuário perder tempo tentando salvar formulários.
                            </small>
                        </div>
                        <div class="col-lg-7 mb-3">
                            <div class="alert alert-info h-100 mb-0">
                                <div class="fw-semibold mb-2">
                                    <i class="bi bi-info-circle me-1"></i>Como o aviso funciona
                                </div>
                                <ul class="mb-0 ps-3">
                                    <li>O frontend acompanha digitação, cliques, foco e interação com a tela.</li>
                                    <li>Enquanto houver atividade, um heartbeat discreto mantém a sessão coerente com o timeout configurado.</li>
                                    <li>Quando a sessão expira, um SweetAlert2 explica o motivo e redireciona para o login.</li>
                                    <li>Se o usuário entrou com "Lembrar-me", a expiração por inatividade continua ignorada, como no fluxo atual.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-precificacao" role="tabpanel">
                    <h5 class="mb-3 border-bottom pb-2">Precificação de Peça Instalada</h5>
                    <div class="row mb-4">
                        <div class="col-lg-3 col-md-6 mb-3">
                            <label class="form-label text-muted">Encargos operacionais (%)</label>
                            <input
                                type="number"
                                class="form-control"
                                name="precificacao_peca_encargos_percentual"
                                min="0"
                                max="300"
                                step="0.01"
                                value="<?= esc($precificacaoEncargos) ?>"
                            >
                            <small class="text-muted d-block mt-2">
                                Ex.: mão de obra indireta, risco de garantia, testes e estrutura.
                            </small>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <label class="form-label text-muted">Margem alvo (%)</label>
                            <input
                                type="number"
                                class="form-control"
                                name="precificacao_peca_margem_percentual"
                                min="0"
                                max="300"
                                step="0.01"
                                value="<?= esc($precificacaoMargem) ?>"
                            >
                            <small class="text-muted d-block mt-2">
                                Margem comercial aplicada após os encargos.
                            </small>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <label class="form-label text-muted">Base de cálculo</label>
                            <select class="form-select" name="precificacao_peca_base">
                                <option value="custo" <?= $precificacaoBase === 'custo' ? 'selected' : '' ?>>Preço de custo da peça</option>
                                <option value="venda" <?= $precificacaoBase === 'venda' ? 'selected' : '' ?>>Preço de venda cadastrado</option>
                            </select>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <label class="form-label text-muted">Piso mínimo pelo preço de venda</label>
                            <select class="form-select" name="precificacao_peca_respeitar_preco_venda">
                                <option value="1" <?= $precificacaoRespeitarVenda ? 'selected' : '' ?>>Sim, nunca abaixo do preço de venda</option>
                                <option value="0" <?= !$precificacaoRespeitarVenda ? 'selected' : '' ?>>Não, usar somente a fórmula</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <div class="alert alert-info mb-0">
                                <div class="fw-semibold mb-2"><i class="bi bi-info-circle me-1"></i>Fórmula aplicada nos itens de peça</div>
                                <p class="mb-2">
                                    Valor recomendado = <strong>base</strong> + encargos + margem.
                                </p>
                                <p class="mb-2">
                                    Exemplo didático: base R$ 100, encargos 15% e margem 45% gera R$ 160,00.
                                </p>
                                <p class="mb-0">
                                    No salvamento de orçamento e OS, o ERP aplica esse valor como piso mínimo da peça instalada e registra os metadados para análise real de mix e margem.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Aba Integrações -->
                <div class="tab-pane fade" id="tab-integracoes" role="tabpanel">
                    <h5 class="mb-3 border-bottom pb-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <span>Configurações WhatsApp</span>
                        <div class="d-flex gap-2 align-items-center">
                            <span id="whatsRealtimeStatus" class="badge <?= esc($realtimeBadgeClass) ?>" style="cursor: pointer;" title="<?= esc($realtimeBadgeTitle) ?>"><?= esc($realtimeBadgeText) ?></span>
                            <span class="badge <?= esc($whatsConfigBadgeClass) ?>" id="whatsConfigBadge">
                                <?= esc($whatsConfigBadgeText) ?>
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
                                <option value="webhook" <?= $directProvider === 'webhook' ? 'selected' : '' ?>>Webhook Genérico</option>
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
                                <option value="0" <?= ($configs['whatsapp_enabled'] ?? '0') !== '1' ? 'selected' : '' ?>>Não</option>
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
                            <label class="form-label text-muted">Método</label>
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
                            <button type="button" class="btn btn-outline-primary" id="btnTestarConexaoWhats">Testar conexão</button>
                            <button type="button" class="btn btn-outline-success" id="btnEnviarTesteWhats">Enviar mensagem de teste</button>
                            <button type="button" class="btn btn-outline-warning" id="btnSelfCheckInboundWhats" title="Válida automaticamente status do gateway, token do webhook, URL inbound e alinhamento de origem ERP."><i class="bi bi-shield-check me-1"></i>Self-check inbound</button>
                        </div>
                        <div class="col-12">
                            <small class="text-muted d-block mt-1">
                                <i class="bi bi-info-circle me-1"></i>
                                O self-check inbound testa automaticamente a rota de entrada do WhatsApp (gateway -> webhook ERP), token e host/origem, sem usar console.
                            </small>
                        </div>
                    </div>

                    <hr class="my-4">

                    <h5 class="mb-3 border-bottom pb-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <span>Configurações de E-mail (SMTP)</span>
                        <span class="badge bg-secondary">Orçamentos + Recuperação de Senha</span>
                    </h5>

                    <div class="row mb-4">
                        <div class="col-md-4 mb-3">
                            <label class="form-label text-muted">Host SMTP</label>
                            <input type="text" class="form-control" name="smtp_host" id="smtp_host" value="<?= esc($configs['smtp_host'] ?? '') ?>" placeholder="smtp.seudominio.com">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label text-muted">Porta</label>
                            <input type="number" min="1" max="65535" class="form-control" name="smtp_port" id="smtp_port" value="<?= esc($configs['smtp_port'] ?? '587') ?>" placeholder="587">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label text-muted">Criptografia</label>
                            <select class="form-select" name="smtp_crypto" id="smtp_crypto">
                                <?php $smtpCrypto = strtolower((string) ($configs['smtp_crypto'] ?? 'auto')); ?>
                                <option value="auto" <?= $smtpCrypto === 'auto' ? 'selected' : '' ?>>Auto (porta)</option>
                                <option value="tls" <?= $smtpCrypto === 'tls' ? 'selected' : '' ?>>TLS / STARTTLS</option>
                                <option value="ssl" <?= $smtpCrypto === 'ssl' ? 'selected' : '' ?>>SSL</option>
                                <option value="none" <?= $smtpCrypto === 'none' ? 'selected' : '' ?>>Nenhuma</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label text-muted">Timeout (s)</label>
                            <input type="number" min="5" max="120" class="form-control" name="smtp_timeout" id="smtp_timeout" value="<?= esc($configs['smtp_timeout'] ?? '20') ?>">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Usuário SMTP</label>
                            <input type="text" class="form-control" name="smtp_user" id="smtp_user" value="<?= esc($configs['smtp_user'] ?? '') ?>" placeholder="usuario@dominio.com">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Senha SMTP</label>
                            <input type="password" class="form-control" name="smtp_pass" id="smtp_pass" value="<?= esc($configs['smtp_pass'] ?? '') ?>" placeholder="Senha ou token do provedor">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">E-mail remetente</label>
                            <input type="email" class="form-control" name="smtp_from_email" id="smtp_from_email" value="<?= esc($configs['smtp_from_email'] ?? '') ?>" placeholder="orcamentos@dominio.com">
                            <small class="text-muted d-block mt-1">Opcional. Se ficar vazio, o ERP tenta usar `Empresa -> Email` e depois `Usuário SMTP`.</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Nome do remetente</label>
                            <input type="text" class="form-control" name="smtp_from_name" id="smtp_from_name" value="<?= esc($configs['smtp_from_name'] ?? '') ?>" placeholder="Assistência Técnica">
                            <small class="text-muted d-block mt-1">Opcional. Se ficar vazio, o ERP usa `Empresa -> Nome da Empresa`.</small>
                        </div>

                        <div class="col-lg-7 mb-3">
                            <div class="alert alert-info mb-0 h-100">
                                <div class="fw-semibold mb-2"><i class="bi bi-envelope-check me-1"></i>Como o ERP usa este canal</div>
                                <ul class="mb-0 ps-3">
                                    <li>Envio do orçamento por e-mail direto na tela do orçamento.</li>
                                    <li>Recuperação de senha para usuários do sistema.</li>
                                    <li>Teste manual para validar host, porta, criptografia e remetente antes de operar.</li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-lg-5 mb-3">
                            <label class="form-label text-muted">E-mail para teste</label>
                            <input type="email" class="form-control mb-2" id="smtp_test_email" value="<?= esc($configs['empresa_email'] ?? '') ?>" placeholder="destino@dominio.com">
                            <button type="button" class="btn btn-outline-primary w-100" id="btnEnviarTesteEmail">
                                <i class="bi bi-send me-1"></i>Enviar e-mail de teste
                            </button>
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
                        <small class="text-muted">Última verificação: <span id="gatewayLastCheck">-</span></small>
                    </div>

                    <div class="row g-3 align-items-start">
                        <div class="col-lg-5 text-center">
                            <p class="small text-muted mb-2">QR Code para autenticação</p>
                            <div class="bg-light p-3 rounded border mx-auto" style="width:256px;height:256px;display:flex;align-items:center;justify-content:center;">
                                <img id="localQrImage" src="" alt="QR" class="img-fluid d-none">
                                <div id="localQrPlaceholder" class="text-muted small px-2">Aguardando QR Code...</div>
                            </div>
                            <small id="gatewayQrHint" class="text-muted d-block mt-2">Escaneie o QR no WhatsApp para manter sessão ativa.</small>
                        </div>
                        <div class="col-lg-7">
                            <div class="border rounded p-3 bg-light-subtle small">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Conta:</span>
                                    <strong id="gatewayAccountName">-</strong>
                                </div>
                                <div class="d-flex justify-content-between mt-1">
                                    <span class="text-muted">Número:</span>
                                    <span id="gatewayAccountNumber">-</span>
                                </div>
                                <div class="d-flex justify-content-between mt-1">
                                    <span class="text-muted">Plataforma:</span>
                                    <span id="gatewayAccountPlatform">-</span>
                                </div>
                                <hr class="my-2">
                                <div class="d-flex justify-content-between mt-1">
                                    <span class="text-muted">Último ready:</span>
                                    <span id="gatewayLastReady">-</span>
                                </div>
                                <div class="d-flex justify-content-between mt-1">
                                    <span class="text-muted">Último erro:</span>
                                    <span id="gatewayLastError">-</span>
                                </div>
                                <div class="d-flex justify-content-between mt-1">
                                    <span class="text-muted">Sessão:</span>
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
                    <i class="bi bi-person-x me-1"></i>Desconectar / Trocar Número
                </button>
                <button type="button" class="btn btn-success d-none" id="btnStartLocal">
                    <i class="bi bi-play-fill me-1"></i>Iniciar Servidor
                </button>
                <button type="button" class="btn btn-outline-warning" id="btnRestartLocal">
                    <i class="bi bi-bootstrap-reboot me-1"></i>Reiniciar Inicialização
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
    const btnSendEmailTest = document.getElementById('btnEnviarTesteEmail');
    const smtpTestEmailInput = document.getElementById('smtp_test_email');
    const modalEl = document.getElementById('modalLocalGateway');
    const btnRestart = document.getElementById('btnRestartLocal');
    const btnLogout = document.getElementById('btnLogoutLocal');
    const btnStart = document.getElementById('btnStartLocal');
    const btnRefresh = document.getElementById('btnRefreshLocal');
    const whatsRealtimeStatus = document.getElementById('whatsRealtimeStatus');
    const whatsConfigBadge = document.getElementById('whatsConfigBadge');
    const tabBadgeStatus = document.getElementById('tabBadgeStatus');
    const btnManageList = Array.from(document.querySelectorAll('.btn-gerenciar-gateway'));
    let currentGatewayProvider = 'api_whats_local';
    let pollInterval = null;
    let modalRef = null;
    const providerState = {
        enabled: <?= json_encode($enabled) ?>,
        provider: <?= json_encode($lastCheckProvider) ?>,
        status: <?= json_encode($lastCheckStatus) ?>,
        message: <?= json_encode($lastCheckMessage) ?>,
        checkedAt: <?= json_encode($lastCheckAt) ?>,
        signature: <?= json_encode($lastCheckSignature) ?>,
    };
    const emailTestUrl = '<?= base_url('configuracoes/email/enviar-teste') ?>';

    const fireSwal = (opts) => window.DSFeedback.fire(opts || {});
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
    const setSimpleBadge = (element, cssClass, text, title = '') => {
        if (!element) return;
        element.className = 'badge ' + cssClass;
        element.textContent = text;
        if (title) {
            element.setAttribute('title', title);
        } else {
            element.removeAttribute('title');
        }
    };

    const normalizeMenuiaUrl = (rawUrl) => {
        let normalized = String(rawUrl || '').trim().replace(/\/+$/, '');
        if (!normalized) {
            return 'https://chatbot.menuia.com/api';
        }

        try {
            const parsed = new URL(normalized);
            if (String(parsed.hostname || '').toLowerCase() === 'api.menuia.com') {
                return 'https://chatbot.menuia.com/api';
            }
        } catch (error) {
            // Mantém a tentativa original e deixa a validação do provider apontar o erro.
        }

        if (!/\/api$/i.test(normalized)) {
            normalized += '/api';
        }

        return normalized;
    };

    const buildMenuiaSignature = (rawUrl, rawAppKey, rawAuthKey) => {
        const normalizedUrl = normalizeMenuiaUrl(rawUrl);
        const appKey = String(rawAppKey || '').trim();
        const authKey = String(rawAuthKey || '').trim();
        if (!normalizedUrl || !appKey || !authKey) {
            return '';
        }

        return [normalizedUrl.toLowerCase(), appKey, authKey].join('|');
    };

    const refreshExternalProviderBadges = () => {
        const provider = selectProvider?.value || 'menuia';
        const menuiaUrl = (document.getElementById('whatsapp_menuia_url')?.value || '').trim();
        const menuiaApp = (document.getElementById('whatsapp_menuia_appkey')?.value || '').trim();
        const menuiaAuth = (document.getElementById('whatsapp_menuia_authkey')?.value || '').trim();
        const menuiaConfigOk = menuiaUrl !== '' && menuiaApp !== '' && menuiaAuth !== '';
        const currentMenuiaSignature = buildMenuiaSignature(menuiaUrl, menuiaApp, menuiaAuth);
        const menuiaValidatedForCurrentConfig = providerState.provider === 'menuia'
            && providerState.signature !== ''
            && currentMenuiaSignature !== ''
            && providerState.signature === currentMenuiaSignature;
        const localConfigOk = ((document.getElementById('whatsapp_local_node_url')?.value || '').trim() !== '')
            && ((document.getElementById('whatsapp_local_node_token')?.value || '').trim() !== '');
        const linuxConfigOk = ((document.getElementById('whatsapp_linux_node_url')?.value || '').trim() !== '')
            && ((document.getElementById('whatsapp_linux_node_token')?.value || '').trim() !== '');
        const webhookConfigOk = ((document.getElementById('whatsapp_webhook_url')?.value || '').trim() !== '');

        if (provider === 'menuia') {
            setSimpleBadge(whatsConfigBadge, menuiaConfigOk ? 'bg-success' : 'bg-danger', menuiaConfigOk ? 'Credenciais OK' : 'Incompleto');
            if (!providerState.enabled) {
                setSimpleBadge(whatsRealtimeStatus, 'bg-secondary', 'Envio desabilitado', 'O envio de WhatsApp está desabilitado nas configurações.');
                setSimpleBadge(tabBadgeStatus, 'bg-secondary', 'Envio desabilitado');
                return;
            }

            if (menuiaValidatedForCurrentConfig && providerState.status === 'success') {
                const title = [providerState.checkedAt ? `Última validação: ${providerState.checkedAt}` : '', providerState.message || ''].filter(Boolean).join(' - ');
                setSimpleBadge(whatsRealtimeStatus, 'bg-success', 'Menuia conectada', title);
                setSimpleBadge(tabBadgeStatus, 'bg-success', 'Menuia conectada');
                return;
            }

            if (menuiaValidatedForCurrentConfig && providerState.status === 'error') {
                const title = [providerState.checkedAt ? `Última validação: ${providerState.checkedAt}` : '', providerState.message || ''].filter(Boolean).join(' - ');
                setSimpleBadge(whatsRealtimeStatus, 'bg-danger', 'Erro Menuia', title);
                setSimpleBadge(tabBadgeStatus, 'bg-danger', 'Erro Menuia');
                return;
            }

            setSimpleBadge(
                whatsRealtimeStatus,
                menuiaConfigOk ? 'bg-warning text-dark' : 'bg-secondary',
                menuiaConfigOk ? 'Menuia não validada' : 'Menuia incompleta',
                menuiaConfigOk
                    ? 'As credenciais estão preenchidas, mas a conexão ainda não foi validada.'
                    : 'Preencha URL, Appkey e Authkey para testar a conexão.'
            );
            setSimpleBadge(tabBadgeStatus, menuiaConfigOk ? 'bg-warning text-dark' : 'bg-secondary', menuiaConfigOk ? 'Não validada' : 'Incompleta');
            return;
        }

        if (provider === 'webhook') {
            setSimpleBadge(whatsRealtimeStatus, 'bg-dark', 'Provider externo', 'Webhook externo selecionado.');
            setSimpleBadge(tabBadgeStatus, 'bg-dark', 'Provider externo');
            setSimpleBadge(whatsConfigBadge, webhookConfigOk ? 'bg-success' : 'bg-danger', webhookConfigOk ? 'Configuração OK' : 'Incompleto');
            return;
        }

        setSimpleBadge(whatsRealtimeStatus, 'bg-dark', 'Provider local', 'Clique para gerenciar o gateway local.');
        setSimpleBadge(tabBadgeStatus, 'bg-dark', 'Provider local');
        const currentLocalConfigOk = provider === 'api_whats_linux' ? linuxConfigOk : localConfigOk;
        setSimpleBadge(whatsConfigBadge, currentLocalConfigOk ? 'bg-success' : 'bg-danger', currentLocalConfigOk ? 'Configuração OK' : 'Incompleto');
    };

    selectProvider?.addEventListener('change', () => {
        toggleProviders();
        refreshExternalProviderBadges();
    });
    document.querySelectorAll('#whatsapp_enabled, #whatsapp_menuia_url, #whatsapp_menuia_appkey, #whatsapp_menuia_authkey, #whatsapp_local_node_url, #whatsapp_local_node_token, #whatsapp_linux_node_url, #whatsapp_linux_node_token, #whatsapp_webhook_url').forEach((element) => {
        element?.addEventListener('input', () => {
            providerState.enabled = (document.getElementsByName('whatsapp_enabled')[0]?.value || '0') === '1';
            refreshExternalProviderBadges();
        });
        element?.addEventListener('change', () => {
            providerState.enabled = (document.getElementsByName('whatsapp_enabled')[0]?.value || '0') === '1';
            refreshExternalProviderBadges();
        });
    });
    toggleProviders();
    refreshExternalProviderBadges();

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

    btnSendEmailTest?.addEventListener('click', async () => {
        const emailDestino = (smtpTestEmailInput?.value || '').trim();
        if (!emailDestino) {
            await fireSwal({
                icon: 'warning',
                title: 'E-mail de teste obrigatório',
                text: 'Informe um e-mail de destino para validar o canal SMTP.',
            });
            smtpTestEmailInput?.focus();
            return;
        }

        const originalHtml = btnSendEmailTest.innerHTML;
        try {
            btnSendEmailTest.disabled = true;
            btnSendEmailTest.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Enviando teste...';

            const data = await postJson(emailTestUrl, {
                email: emailDestino,
                smtp_host: document.getElementById('smtp_host')?.value || '',
                smtp_port: document.getElementById('smtp_port')?.value || '',
                smtp_user: document.getElementById('smtp_user')?.value || '',
                smtp_pass: document.getElementById('smtp_pass')?.value || '',
                smtp_crypto: document.getElementById('smtp_crypto')?.value || 'auto',
                smtp_timeout: document.getElementById('smtp_timeout')?.value || '20',
                smtp_from_email: document.getElementById('smtp_from_email')?.value || '',
                smtp_from_name: document.getElementById('smtp_from_name')?.value || '',
            });

            await fireSwal({
                icon: 'success',
                title: 'E-mail de teste enviado',
                text: data.message || 'O ERP conseguiu processar o envio de teste.',
            });
        } catch (error) {
            const detalhe = error?.payload?.error ? ` Detalhe técnico: ${error.payload.error}` : '';
            await fireSwal({
                icon: 'error',
                title: 'Falha no e-mail de teste',
                text: `${error.message || 'Não foi possível enviar o e-mail de teste.'}${detalhe}`,
            });
        } finally {
            btnSendEmailTest.disabled = false;
            btnSendEmailTest.innerHTML = originalHtml;
        }
    });

    btnTestConn?.addEventListener('click', async () => {
        const originalHtml = btnTestConn.innerHTML;
        try {
            btnTestConn.disabled = true;
            btnTestConn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Validando...';
            const data = await postJson('<?= base_url('configuracoes/whatsapp/testar-conexao') ?>', providerPayload());
            providerState.provider = selectProvider?.value || 'menuia';
            providerState.status = 'success';
            providerState.message = data.message || 'Conexão validada com sucesso.';
            providerState.checkedAt = new Date().toLocaleString('pt-BR');
            providerState.signature = buildMenuiaSignature(
                document.getElementById('whatsapp_menuia_url')?.value || '',
                document.getElementById('whatsapp_menuia_appkey')?.value || '',
                document.getElementById('whatsapp_menuia_authkey')?.value || ''
            );
            refreshExternalProviderBadges();
            await fireSwal({ icon: 'success', title: 'Conexão validada', text: data.message || 'OK' });
        } catch (error) {
            providerState.provider = selectProvider?.value || 'menuia';
            providerState.status = 'error';
            providerState.message = error.message || 'Falha na validação do provider.';
            providerState.checkedAt = new Date().toLocaleString('pt-BR');
            providerState.signature = buildMenuiaSignature(
                document.getElementById('whatsapp_menuia_url')?.value || '',
                document.getElementById('whatsapp_menuia_appkey')?.value || '',
                document.getElementById('whatsapp_menuia_authkey')?.value || ''
            );
            refreshExternalProviderBadges();
            await fireSwal({ icon: 'error', title: 'Falha na conexão', text: error.message || 'Erro' });
        } finally {
            btnTestConn.disabled = false;
            btnTestConn.innerHTML = originalHtml;
        }
    });

    btnSendTest?.addEventListener('click', async () => {
        const phone = (byId('whatsapp_test_phone')?.value || '').trim();
        if (!phone) {
            await fireSwal({ icon: 'warning', title: 'Telefone obrigatório', text: 'Informe o telefone de teste.' });
            return;
        }
        const dataPrompt = (window.Swal && typeof window.Swal.fire === 'function')
            ? await window.Swal.fire({ title: 'Mensagem de teste', input: 'textarea', showCancelButton: true, confirmButtonText: 'Enviar', cancelButtonText: 'Cancelar', inputValue: '[Teste de integração] Mensagem de teste enviada pelo ERP.' })
            : { isConfirmed: true, value: '[Teste de integração] Mensagem de teste enviada pelo ERP.' };
        
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
                title: 'Provider não compativel',
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
                const icon = ok ? '\u2705' : '\u274C';
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
                    const icon = ok ? '\u2705' : '\u274C';
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
                html: `<div>${error.message || 'Falha na validação inbound.'}</div>${detailsHtml}`,
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
            auth_failure: { cls: 'text-bg-danger', label: 'Falha de autenticação', icon: 'bi-exclamation-triangle' },
            gateway_unreachable: { cls: 'text-bg-danger', label: 'Offline / Inacessível', icon: 'bi-plug' },
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
            errorMsg = statusPayload?.message || 'Gateway inacessível';
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
            clearQr('Conectado. QR não necessário.');
            if (localQrImage) {
                localQrImage.src = '<?= base_url('assets/img/sistema/whatsapp_connected_success.png') ?>';
                localQrImage.classList.remove('d-none');
            }
            if (localQrPlaceholder) localQrPlaceholder.classList.add('d-none');
            if (gatewayQrHint) gatewayQrHint.textContent = 'Sessão ativa e pronta para envio.';
        } else {
            btnLogout?.classList.add('d-none');
            if (hasQr && localQrImage && localQrPlaceholder) {
                localQrImage.src = finalQr;
                localQrImage.classList.remove('d-none');
                localQrPlaceholder.classList.add('d-none');
                if (gatewayQrHint) gatewayQrHint.textContent = 'Escaneie o QR Code para autenticar o WhatsApp.';
            } else {
                clearQr('QR indisponível no momento. Aguarde ou reinicie.');
                if (gatewayQrHint) gatewayQrHint.textContent = 'Se o QR demorar, use Reiniciar Inicialização.';
            }
        }

        // Resetar estados de carregamento manuais se o status for estável
        const stableStatuses = ['connected', 'awaiting_qr', 'disconnected', 'gateway_unreachable', 'error', 'auth_failure'];
        if (stableStatuses.includes(status)) {
            if (btnRestart && btnRestart.disabled) {
                btnRestart.disabled = false;
                btnRestart.innerHTML = '<i class="bi bi-bootstrap-reboot me-1"></i>Reiniciar Inicialização';
            }
            if (btnLogout && btnLogout.disabled) {
                btnLogout.disabled = false;
                btnLogout.innerHTML = '<i class="bi bi-person-x me-1"></i>Desconectar / Trocar Número';
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
                clearQr('Servidor inacessível.');
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
                    setGatewayError(qrPayload?.message || statusPayload?.message || 'QR ainda não gerado.');
                }
            }
            applyGatewayData(statusPayload, qrPayload);
        } catch (e) {
            setPanelState();
            setGatewayStatusBadge('gateway_unreachable');
            setGatewayError(e?.message || 'Servidor do gateway inacessível.');
            clearQr('Servidor inacessível.');
            if (gatewayQrHint) gatewayQrHint.textContent = 'Confirme processo Node em execução e token correto.';
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
            text: 'Deseja apenas reiniciar o processo ou também zerar os arquivos de sessão (limpeza profunda)? Zerar a sessão exigirá uma nova leitura de QR Code.',
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
        if (conf.isConfirmed) cleanParam = true;  // Limpeza profunda
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
            await fireSwal({ icon: 'error', title: 'Falha no reinício', text: error.message || 'Erro ao reiniciar' });
            btnRestart.disabled = false;
            btnRestart.innerHTML = originalHtml;
        }
    });

    btnLogout?.addEventListener('click', async () => {
        const conf = await fireSwal({ title: 'Desconectar WhatsApp?', text: 'Isso irá encerrar a sessão atual e gerar um novo QR Code para vincular outro número. Tem certeza?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Sim, desconectar', cancelButtonText: 'Não' });
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

