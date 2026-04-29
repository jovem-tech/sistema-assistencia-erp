<?= $this->extend($layout ?? 'layouts/main') ?>

<?= $this->section('content') ?>
<?php
$statusLabels = $statusLabels ?? [];
$tipoLabels = $tipoLabels ?? [];
$status = (string) ($orcamento['status'] ?? 'rascunho');
$tipoOrcamento = (string) ($orcamento['tipo_orcamento'] ?? 'previo');
$statusClassMap = [
    'rascunho' => 'bg-secondary',
    'pendente_envio' => 'bg-secondary-subtle text-secondary-emphasis',
    'enviado' => 'bg-primary',
    'aguardando_resposta' => 'bg-info text-dark',
    'reenviar_orcamento' => 'bg-warning text-dark',
    'aguardando_pacote' => 'bg-primary-subtle text-primary-emphasis',
    'pacote_aprovado' => 'bg-success-subtle text-success-emphasis',
    'pendente' => 'bg-warning text-dark',
    'aprovado' => 'bg-success',
    'pendente_abertura_os' => 'bg-warning text-dark',
    'rejeitado' => 'bg-danger',
    'vencido' => 'bg-warning text-dark',
    'cancelado' => 'bg-dark',
    'convertido' => 'bg-success',
];
$statusClass = $statusClassMap[$status] ?? 'bg-secondary';
$statusDisplayLabel = (string) ($orcamento['status_label'] ?? ($statusLabels[$status] ?? ucfirst($status)));
$statusLabels[$status] = $statusDisplayLabel;
$tipoOrcamentoLabel = (string) ($tipoLabels[$tipoOrcamento] ?? ucfirst($tipoOrcamento));
$clienteNome = trim((string) ($orcamento['cliente_nome'] ?? ''));
if ($clienteNome === '') {
    $clienteNome = (string) ($orcamento['cliente_nome_avulso'] ?? 'Cliente eventual');
}

$defaultWhatsappMessage = (string) ($defaultWhatsappMessage ?? '');
$defaultEmailSubject = (string) ($defaultEmailSubject ?? '');
$lastPdfUrl = (string) ($lastPdfUrl ?? '');
$pacoteOfertaPrincipal = is_array($pacoteOfertaPrincipal ?? null) ? $pacoteOfertaPrincipal : null;
$pacotesOfertasHistorico = is_array($pacotesOfertasHistorico ?? null) ? $pacotesOfertasHistorico : [];
$historicoLista = is_array($historico ?? null) ? $historico : [];
$enviosLista = is_array($envios ?? null) ? $envios : [];
$aprovacoesLista = is_array($aprovacoes ?? null) ? $aprovacoes : [];
${"hist\xC3\x83\xC2\xB3rico"} = $historicoLista;
$histÃƒÂ³rico = $historicoLista;
$histÃƒÆ’Ã‚Â³rico = $historicoLista;

$tokenPublicoOrcamento = trim((string) ($orcamento['token_publico'] ?? ''));
$linkPublicoOrcamento = $tokenPublicoOrcamento !== '' ? base_url('orcamento/' . $tokenPublicoOrcamento) : '';
$tokenOfertaResumo = $pacoteOfertaPrincipal !== null ? trim((string) ($pacoteOfertaPrincipal['token_publico'] ?? '')) : '';
$linkOfertaResumo = $tokenOfertaResumo !== '' ? base_url('pacote/oferta/' . $tokenOfertaResumo) : '';
$resumoPublicLink = $linkOfertaResumo !== '' ? $linkOfertaResumo : $linkPublicoOrcamento;
$resumoPublicLinkLabel = $linkOfertaResumo !== '' ? 'Link da oferta de pacote' : 'Link publico';
$resumoPublicLinkCopyText = $linkOfertaResumo !== ''
    ? 'Link da oferta de pacote copiado para a area de transferencia.'
    : 'Link publico copiado para a area de transferencia.';

$statusOptions = is_array($statusOptions ?? null) ? $statusOptions : $statusLabels;
$pacoteOfertaStatusLabels = [
    'ativo' => 'Ativo',
    'enviado' => 'Enviado',
    'escolhido' => 'Escolhido',
    'aplicado_orcamento' => 'Aplicado no orcamento',
    'expirado' => 'Expirado',
    'cancelado' => 'Cancelado',
    'erro_envio' => 'Erro no envio',
];

$dispatchBlocked = in_array($status, ['aprovado', 'pendente_abertura_os', 'pacote_aprovado', 'cancelado', 'convertido'], true);
$isEmbedded = !empty($isEmbedded);
$embedQuery = $isEmbedded ? '?embed=1' : '';
$canEditOrcamento = can('orcamentos', 'editar');
$canOpenEditor = $canEditOrcamento;
$showStatusUpdateForm = $canEditOrcamento && count($statusOptions) > 1;
$showSendActions = !$dispatchBlocked;
$canConvert = $canEditOrcamento && in_array($status, ['aprovado', 'pendente_abertura_os', 'pacote_aprovado'], true);

$versaoAtual = (string) ($orcamento['versao'] ?? 1);
$telefoneContato = trim((string) ($orcamento['telefone_contato'] ?? $orcamento['contato_telefone'] ?? $orcamento['conversa_telefone'] ?? ''));
$telefoneContato = $telefoneContato !== '' ? $telefoneContato : '-';
$emailContato = trim((string) ($orcamento['email_contato'] ?? $orcamento['contato_email'] ?? ''));
$emailContato = $emailContato !== '' ? $emailContato : '-';
$contatoNome = trim((string) ($orcamento['contato_nome'] ?? ''));
$contatoTelefone = trim((string) ($orcamento['contato_telefone'] ?? ''));
$contatoEmail = trim((string) ($orcamento['contato_email'] ?? ''));
$validadeFormatada = formatDate($orcamento['validade_data'] ?? null);
$origemFormatada = ucfirst(str_replace('_', ' ', (string) ($orcamento['origem'] ?? 'manual')));
$numeroOsVinculada = !empty($orcamento['numero_os']) ? (string) $orcamento['numero_os'] : '-';
$conversaVinculada = !empty($orcamento['conversa_id']) ? ('#' . (string) $orcamento['conversa_id']) : '-';
$conversaTelefone = trim((string) ($orcamento['conversa_telefone'] ?? ''));
$conversaTelefone = $conversaTelefone !== '' ? $conversaTelefone : '-';
$prazoExecucao = trim((string) ($orcamento['prazo_execucao'] ?? ''));
$revisaoBaseNumero = (string) ($orcamento['revisao_base_numero'] ?? ('#' . (int) ($orcamento['orcamento_revisao_de_id'] ?? 0)));
$conversaoResumo = '-';
if (!empty($orcamento['convertido_tipo'])) {
    $conversaoResumo = (string) $orcamento['convertido_tipo'];
    if (!empty($orcamento['convertido_id'])) {
        $conversaoResumo .= ' #' . (string) $orcamento['convertido_id'];
    }
}

$equipamentoView = is_array($equipamentoView ?? null) ? $equipamentoView : [];
$equipamentoTipoNome = trim((string) ($equipamentoView['tipo_nome'] ?? ''));
$equipamentoMarcaNome = trim((string) ($equipamentoView['marca_nome'] ?? ''));
$equipamentoModeloNome = trim((string) ($equipamentoView['modelo_nome'] ?? ''));
$equipamentoMarcaModelo = trim($equipamentoMarcaNome . ' ' . $equipamentoModeloNome);
$equipamentoCorNome = trim((string) ($equipamentoView['cor'] ?? ''));
$equipamentoFotoUrl = trim((string) ($equipamentoView['foto_url'] ?? ''));
$equipamentoIdentificacao = trim((string) ($equipamentoView['identificacao'] ?? ''));
if ($equipamentoIdentificacao === '') {
    $equipamentoIdentificacao = $equipamentoMarcaModelo !== '' ? $equipamentoMarcaModelo : 'Sem equipamento vinculado';
}
$temEquipamentoVinculado = !empty($equipamentoView['equipamento_id'])
    || $equipamentoTipoNome !== ''
    || $equipamentoMarcaModelo !== ''
    || $equipamentoCorNome !== '';
$equipamentoResumoTopo = $equipamentoTipoNome !== '' ? $equipamentoTipoNome : $equipamentoIdentificacao;
$equipamentoCorStyle = '';
$equipamentoCorHex = trim((string) ($equipamentoView['cor_hex'] ?? ''));
$equipamentoCorRgb = trim((string) ($equipamentoView['cor_rgb'] ?? ''));
if ($equipamentoCorHex !== '') {
    $equipamentoCorStyle = 'background:' . esc($equipamentoCorHex) . ';';
} elseif ($equipamentoCorRgb !== '') {
    $equipamentoCorStyle = 'background:rgb(' . esc($equipamentoCorRgb) . ');';
}

$clienteCategoriaLabel = !empty($orcamento['cliente_id'])
    ? 'Cliente cadastrado no sistema.'
    : 'Cliente eventual informado diretamente no orcamento.';
$clienteTemContatoComplementar = $contatoNome !== ''
    || ($contatoTelefone !== '' && $contatoTelefone !== $telefoneContato)
    || ($contatoEmail !== '' && $contatoEmail !== $emailContato);
?>

<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h2 class="mb-0"><i class="bi bi-file-earmark-text me-2"></i>Orcamento <?= esc((string) ($orcamento['numero'] ?? '#')) ?></h2>
    <div class="d-flex gap-2 flex-wrap">
        <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('orcamentos')">
            <i class="bi bi-question-circle me-1"></i>Ajuda
        </button>
        <?php if (!$isEmbedded): ?>
            <a href="<?= base_url('orcamentos') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Voltar
            </a>
        <?php endif; ?>
        <?php if ($canOpenEditor): ?>
            <a href="<?= base_url('orcamentos/editar/' . (int) $orcamento['id']) . $embedQuery ?>" class="btn btn-primary btn-glow">
                <i class="bi bi-pencil me-1"></i>Editar
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="card glass-card mb-4 orc-show-overview">
    <div class="card-body p-3 p-lg-4">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
            <div>
                <div class="small text-uppercase text-muted fw-semibold mb-1">Visao geral do orcamento</div>
                <h3 class="mb-1"><?= esc($clienteNome) ?></h3>
                <div class="small text-muted"><?= esc($tipoOrcamentoLabel) ?> | Versao <?= esc($versaoAtual) ?></div>
            </div>
            <div class="orc-show-overview__aside text-lg-end">
                <span class="badge <?= esc($statusClass) ?> orc-status-pill"><?= esc($statusDisplayLabel) ?></span>
                <div class="small text-muted mt-2">Total consolidado</div>
                <div class="fs-4 fw-bold"><?= esc(formatMoney($orcamento['total'] ?? 0)) ?></div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-12 col-sm-6 col-xl-2">
                <div class="orc-overview-metric">
                    <span class="orc-overview-metric__label">OS vinculada</span>
                    <strong class="orc-overview-metric__value"><?= esc($numeroOsVinculada) ?></strong>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-2">
                <div class="orc-overview-metric">
                    <span class="orc-overview-metric__label">Equipamento</span>
                    <strong class="orc-overview-metric__value"><?= esc($equipamentoResumoTopo) ?></strong>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-2">
                <div class="orc-overview-metric">
                    <span class="orc-overview-metric__label">Validade</span>
                    <strong class="orc-overview-metric__value"><?= esc($validadeFormatada) ?></strong>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-2">
                <div class="orc-overview-metric">
                    <span class="orc-overview-metric__label">Origem</span>
                    <strong class="orc-overview-metric__value"><?= esc($origemFormatada) ?></strong>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-2">
                <div class="orc-overview-metric">
                    <span class="orc-overview-metric__label">Conversa</span>
                    <strong class="orc-overview-metric__value"><?= esc($conversaVinculada) ?></strong>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-2">
                <div class="orc-overview-metric">
                    <span class="orc-overview-metric__label">Prazo</span>
                    <strong class="orc-overview-metric__value"><?= esc($prazoExecucao !== '' ? $prazoExecucao : '-') ?></strong>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card glass-card orc-show-tab-card">
    <div class="card-header border-0 pb-0">
        <ul class="nav nav-tabs nav-fill ds-tabs-scroll orc-show-tab-nav" id="orcamentoShowTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="orc-tab-cliente-tab" data-bs-toggle="tab" data-bs-target="#orc-tab-cliente" type="button" role="tab" aria-controls="orc-tab-cliente" aria-selected="true" title="Dados do cliente">
                    <span class="orc-tab-label orc-tab-label--full">Dados do cliente</span>
                    <span class="orc-tab-label orc-tab-label--short">Cliente</span>
                    <span class="orc-tab-label orc-tab-label--micro">Cli.</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="orc-tab-equipamento-tab" data-bs-toggle="tab" data-bs-target="#orc-tab-equipamento" type="button" role="tab" aria-controls="orc-tab-equipamento" aria-selected="false" title="Dados do equipamento">
                    <span class="orc-tab-label orc-tab-label--full">Dados do equipamento</span>
                    <span class="orc-tab-label orc-tab-label--short">Equipamento</span>
                    <span class="orc-tab-label orc-tab-label--micro">Equip.</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="orc-tab-operacional-tab" data-bs-toggle="tab" data-bs-target="#orc-tab-operacional" type="button" role="tab" aria-controls="orc-tab-operacional" aria-selected="false" title="Dados operacionais">
                    <span class="orc-tab-label orc-tab-label--full">Dados operacionais</span>
                    <span class="orc-tab-label orc-tab-label--short">Operacional</span>
                    <span class="orc-tab-label orc-tab-label--micro">Oper.</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="orc-tab-pacotes-tab" data-bs-toggle="tab" data-bs-target="#orc-tab-pacotes" type="button" role="tab" aria-controls="orc-tab-pacotes" aria-selected="false" title="Pacotes de servico">
                    <span class="orc-tab-label orc-tab-label--full">Pacotes de servico</span>
                    <span class="orc-tab-label orc-tab-label--short">Pacotes</span>
                    <span class="orc-tab-label orc-tab-label--micro">Pcts.</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="orc-tab-envio-tab" data-bs-toggle="tab" data-bs-target="#orc-tab-envio" type="button" role="tab" aria-controls="orc-tab-envio" aria-selected="false" title="Envio do orcamento">
                    <span class="orc-tab-label orc-tab-label--full">Envio do orcamento</span>
                    <span class="orc-tab-label orc-tab-label--short">Envio</span>
                    <span class="orc-tab-label orc-tab-label--micro">Env.</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="orc-tab-orcamento-tab" data-bs-toggle="tab" data-bs-target="#orc-tab-orcamento" type="button" role="tab" aria-controls="orc-tab-orcamento" aria-selected="false" title="Orcamento">
                    <span class="orc-tab-label orc-tab-label--full">Orcamento</span>
                    <span class="orc-tab-label orc-tab-label--short">Orcamento</span>
                    <span class="orc-tab-label orc-tab-label--micro">Orc.</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="orc-tab-financeiro-tab" data-bs-toggle="tab" data-bs-target="#orc-tab-financeiro" type="button" role="tab" aria-controls="orc-tab-financeiro" aria-selected="false" title="Financeiro do orcamento">
                    <span class="orc-tab-label orc-tab-label--full">Financeiro do orcamento</span>
                    <span class="orc-tab-label orc-tab-label--short">Financeiro</span>
                    <span class="orc-tab-label orc-tab-label--micro">Fin.</span>
                </button>
            </li>
        </ul>
    </div>

    <div class="card-body pt-3 pt-lg-4">
        <div class="tab-content">
            <div class="tab-pane fade show active" id="orc-tab-cliente" role="tabpanel" aria-labelledby="orc-tab-cliente-tab" tabindex="0">
                <div class="orc-show-tab-pane">
                    <div class="row g-3">
                        <div class="col-12 col-xl-7">
                            <div class="border rounded-4 bg-white p-3 p-lg-4 h-100 orc-show-panel">
                                <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
                                    <div>
                                        <div class="small text-uppercase text-muted fw-semibold mb-1">Cliente principal</div>
                                        <h5 class="mb-1"><?= esc($clienteNome) ?></h5>
                                        <div class="small text-muted"><?= esc($clienteCategoriaLabel) ?></div>
                                    </div>
                                    <span class="badge rounded-pill text-bg-light border"><?= esc($tipoOrcamentoLabel) ?></span>
                                </div>

                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <span class="orc-field-label">Telefone principal</span>
                                        <div class="orc-field-value"><?= esc($telefoneContato) ?></div>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <span class="orc-field-label">E-mail principal</span>
                                        <div class="orc-field-value"><?= esc($emailContato) ?></div>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <span class="orc-field-label">OS vinculada</span>
                                        <div class="orc-field-value"><?= esc($numeroOsVinculada) ?></div>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <span class="orc-field-label">Conversa vinculada</span>
                                        <div class="orc-field-value"><?= esc($conversaVinculada) ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-xl-5">
                            <div class="border rounded-4 bg-white p-3 p-lg-4 h-100 orc-show-panel">
                                <div class="small text-uppercase text-muted fw-semibold mb-1">Contato de atendimento</div>
                                <?php if ($clienteTemContatoComplementar): ?>
                                    <h6 class="mb-3"><?= esc($contatoNome !== '' ? $contatoNome : $clienteNome) ?></h6>
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <span class="orc-field-label">Telefone do contato</span>
                                            <div class="orc-field-value"><?= esc($contatoTelefone !== '' ? $contatoTelefone : $telefoneContato) ?></div>
                                        </div>
                                        <div class="col-12">
                                            <span class="orc-field-label">E-mail do contato</span>
                                            <div class="orc-field-value"><?= esc($contatoEmail !== '' ? $contatoEmail : $emailContato) ?></div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="orc-empty-state orc-empty-state--compact">
                                        <i class="bi bi-person-lines-fill"></i>
                                        <p class="mb-0">Nao existe um contato complementar vinculado. O atendimento segue com os dados principais do cliente.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="border rounded-4 bg-white p-3 p-lg-4 orc-show-panel">
                                <div class="small text-uppercase text-muted fw-semibold mb-2">Canal de atendimento</div>
                                <div class="row g-3">
                                    <div class="col-12 col-lg-4">
                                        <span class="orc-field-label">Origem do orcamento</span>
                                        <div class="orc-field-value"><?= esc($origemFormatada) ?></div>
                                    </div>
                                    <div class="col-12 col-lg-4">
                                        <span class="orc-field-label">Telefone da conversa</span>
                                        <div class="orc-field-value"><?= esc($conversaTelefone) ?></div>
                                    </div>
                                    <div class="col-12 col-lg-4">
                                        <span class="orc-field-label">Versao atual</span>
                                        <div class="orc-field-value"><?= esc($versaoAtual) ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="orc-tab-equipamento" role="tabpanel" aria-labelledby="orc-tab-equipamento-tab" tabindex="0">
                <div class="orc-show-tab-pane">
                    <?php if (!$temEquipamentoVinculado): ?>
                        <div class="border rounded-4 bg-white p-4 p-lg-5 orc-show-panel">
                            <div class="orc-empty-state">
                                <i class="bi bi-phone"></i>
                                <h5 class="mb-2">Sem equipamento vinculado</h5>
                                <p class="mb-0">Este orcamento ainda nao possui um equipamento consolidado para exibir nesta aba.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="row g-3">
                            <div class="col-12 col-xl-4">
                                <div class="border rounded-4 bg-white p-3 p-lg-4 h-100 orc-show-panel">
                                    <div class="small text-uppercase text-muted fw-semibold mb-3">Foto principal</div>
                                    <?php if ($equipamentoFotoUrl !== ''): ?>
                                        <img src="<?= esc($equipamentoFotoUrl) ?>" alt="Foto principal do equipamento" class="orc-equipment-photo">
                                    <?php else: ?>
                                        <div class="orc-equipment-placeholder">
                                            <i class="bi bi-image"></i>
                                            <span>Nenhuma foto principal cadastrada para este equipamento.</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="col-12 col-xl-8">
                                <div class="border rounded-4 bg-white p-3 p-lg-4 h-100 orc-show-panel">
                                    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
                                        <div>
                                            <div class="small text-uppercase text-muted fw-semibold mb-1">Resumo do equipamento</div>
                                            <h5 class="mb-1"><?= esc($equipamentoIdentificacao) ?></h5>
                                            <div class="small text-muted">Informacoes capturadas no vinculo atual do orcamento.</div>
                                        </div>
                                        <?php if ($equipamentoCorNome !== ''): ?>
                                            <span class="badge rounded-pill text-bg-light border">Cor: <?= esc($equipamentoCorNome) ?></span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="row g-3">
                                        <div class="col-12 col-md-6">
                                            <span class="orc-field-label">Tipo</span>
                                            <div class="orc-field-value"><?= esc($equipamentoTipoNome !== '' ? $equipamentoTipoNome : '-') ?></div>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <span class="orc-field-label">Marca</span>
                                            <div class="orc-field-value"><?= esc($equipamentoMarcaNome !== '' ? $equipamentoMarcaNome : '-') ?></div>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <span class="orc-field-label">Modelo</span>
                                            <div class="orc-field-value"><?= esc($equipamentoModeloNome !== '' ? $equipamentoModeloNome : '-') ?></div>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <span class="orc-field-label">Cor</span>
                                            <div class="orc-field-value d-flex align-items-center gap-2 flex-wrap">
                                                <?php if ($equipamentoCorStyle !== ''): ?>
                                                    <span class="orc-color-chip" style="<?= $equipamentoCorStyle ?>"></span>
                                                <?php endif; ?>
                                                <span><?= esc($equipamentoCorNome !== '' ? $equipamentoCorNome : '-') ?></span>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <span class="orc-field-label">ID do equipamento</span>
                                            <div class="orc-field-value"><?= !empty($equipamentoView['equipamento_id']) ? '#' . esc((string) $equipamentoView['equipamento_id']) : '-' ?></div>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <span class="orc-field-label">OS vinculada</span>
                                            <div class="orc-field-value"><?= esc($numeroOsVinculada) ?></div>
                                        </div>
                                        <div class="col-12">
                                            <span class="orc-field-label">Identificacao consolidada</span>
                                            <div class="orc-field-value"><?= esc($equipamentoIdentificacao) ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="tab-pane fade" id="orc-tab-operacional" role="tabpanel" aria-labelledby="orc-tab-operacional-tab" tabindex="0">
                <div class="orc-show-tab-pane">
                    <div class="row g-3 mb-3">
                        <div class="col-12 col-xl-8">
                            <div class="border rounded-4 bg-white p-3 p-lg-4 h-100 orc-show-panel">
                                <div class="small text-uppercase text-muted fw-semibold mb-3">Contexto operacional</div>

                                <?php if ($tipoOrcamento === 'previo'): ?>
                                    <div class="alert alert-info small">
                                        Este registro esta tratado como <strong>orcamento previo</strong>: uma estimativa inicial para cliente que ainda pode depender da entrada do equipamento e de analise tecnica presencial.
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-light border small">
                                        Este registro esta tratado como <strong>orcamento com equipamento na assistencia</strong> e deve refletir a OS e o equipamento em atendimento.
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($orcamento['orcamento_revisao_de_id'])): ?>
                                    <div class="alert alert-warning small">
                                        Revisao derivada do orcamento base <strong><?= esc($revisaoBaseNumero) ?></strong>. Use esta versao para ajustes e nova autorizacao do cliente.
                                    </div>
                                <?php endif; ?>

                                <?php if (in_array($status, ['aprovado', 'pendente_abertura_os', 'pacote_aprovado', 'reenviar_orcamento', 'convertido'], true)): ?>
                                    <div class="alert alert-light border small">
                                        Alteracoes neste orcamento permanecem no mesmo registro. Quando houver nova rodada de aprovacao, o sistema salva o historico aqui e muda o status para <strong>Reenviar orcamento</strong>.
                                    </div>
                                <?php endif; ?>

                                <div class="row g-3">
                                    <div class="col-12 col-md-4">
                                        <span class="orc-field-label">Tipo</span>
                                        <div class="orc-field-value"><?= esc($tipoOrcamentoLabel) ?></div>
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <span class="orc-field-label">Origem</span>
                                        <div class="orc-field-value"><?= esc($origemFormatada) ?></div>
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <span class="orc-field-label">Validade</span>
                                        <div class="orc-field-value"><?= esc($validadeFormatada) ?></div>
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <span class="orc-field-label">OS vinculada</span>
                                        <div class="orc-field-value"><?= esc($numeroOsVinculada) ?></div>
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <span class="orc-field-label">Conversa vinculada</span>
                                        <div class="orc-field-value"><?= esc($conversaVinculada) ?></div>
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <span class="orc-field-label">Conversao</span>
                                        <div class="orc-field-value"><?= esc($conversaoResumo) ?></div>
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <span class="orc-field-label">Prazo de execucao</span>
                                        <div class="orc-field-value"><?= esc($prazoExecucao !== '' ? $prazoExecucao : '-') ?></div>
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <span class="orc-field-label">Status atual</span>
                                        <div class="orc-field-value"><?= esc($statusDisplayLabel) ?></div>
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <span class="orc-field-label">Versao</span>
                                        <div class="orc-field-value"><?= esc($versaoAtual) ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-xl-4">
                            <div class="border rounded-4 bg-white p-3 p-lg-4 h-100 orc-show-panel">
                                <div class="small text-uppercase text-muted fw-semibold mb-2">Controle do fluxo</div>
                                <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-3">
                                    <span class="badge <?= esc($statusClass) ?> orc-status-pill"><?= esc($statusDisplayLabel) ?></span>
                                    <span class="small text-muted">Versao <?= esc($versaoAtual) ?></span>
                                </div>

                                <?php if ($canEditOrcamento): ?>
                                    <?php if ($showStatusUpdateForm): ?>
                                        <form action="<?= base_url('orcamentos/status/' . (int) $orcamento['id']) . $embedQuery ?>" method="POST" class="d-flex gap-2 flex-wrap">
                                            <?= csrf_field() ?>
                                            <select name="status" class="form-select form-select-sm">
                                                <?php foreach ($statusOptions as $statusCode => $statusName): ?>
                                                    <option value="<?= esc($statusCode) ?>" <?= $statusCode === $status ? 'selected' : '' ?>><?= esc($statusName) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" class="btn btn-sm btn-outline-primary">Atualizar status</button>
                                        </form>
                                    <?php else: ?>
                                        <div class="small text-muted">Status controlado automaticamente pelo fluxo atual.</div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <?php if ($canConvert): ?>
                        <div class="border rounded-4 bg-white p-3 p-lg-4 orc-show-panel mb-3">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                                <div class="small text-uppercase text-muted fw-semibold">Conversao do orcamento</div>
                                <span class="small text-muted">Converta aprovado em execucao operacional</span>
                            </div>
                            <?php if (in_array($status, ['pendente_abertura_os', 'pacote_aprovado'], true) && empty($orcamento['os_id'])): ?>
                                <div class="alert alert-warning small">
                                    Este orcamento foi aprovado sem OS vinculada. Para iniciar a execucao, use <strong>Converter para OS</strong>.
                                </div>
                            <?php endif; ?>
                            <div class="d-flex gap-2 flex-wrap">
                                <form method="POST" action="<?= base_url('orcamentos/converter/' . (int) $orcamento['id']) . $embedQuery ?>" data-orc-confirm data-confirm-title="Converter para OS?" data-confirm-text="Confirma a conversao deste orcamento para OS/execucao?">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="tipo" value="os">
                                    <button type="submit" class="btn btn-success">
                                        <i class="bi bi-clipboard-check me-1"></i>
                                        <?= empty($orcamento['os_id']) ? 'Abrir OS e converter' : 'Converter em execucao OS' ?>
                                    </button>
                                </form>
                                <form method="POST" action="<?= base_url('orcamentos/converter/' . (int) $orcamento['id']) . $embedQuery ?>" data-orc-confirm data-confirm-title="Converter para venda?" data-confirm-text="Confirma a conversao deste orcamento para venda manual?">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="tipo" value="venda">
                                    <button type="submit" class="btn btn-outline-primary">
                                        <i class="bi bi-cart-check me-1"></i>Converter em venda
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($status === 'convertido'): ?>
                        <div class="alert alert-success mb-3">
                            Orcamento convertido com sucesso.
                            <?php if ((string) ($orcamento['convertido_tipo'] ?? '') === 'os' && (int) ($orcamento['convertido_id'] ?? 0) > 0): ?>
                                <a href="<?= base_url('os/visualizar/' . (int) $orcamento['convertido_id']) ?>" class="alert-link ms-1" target="_blank" rel="noopener">Abrir OS convertida</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="row g-3">
                        <div class="col-12 col-xl-6">
                            <div class="border rounded-4 bg-white p-3 p-lg-4 h-100 orc-show-panel">
                                <div class="small text-uppercase text-muted fw-semibold mb-2">Observacoes</div>
                                <?php if (!empty($orcamento['observacoes'])): ?>
                                    <div class="orc-text-block"><?= nl2br(esc((string) $orcamento['observacoes'])) ?></div>
                                <?php else: ?>
                                    <p class="text-muted mb-0">Sem observacoes registradas.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-12 col-xl-6">
                            <div class="border rounded-4 bg-white p-3 p-lg-4 h-100 orc-show-panel">
                                <div class="small text-uppercase text-muted fw-semibold mb-2">Condicoes e retorno do cliente</div>
                                <?php if (!empty($orcamento['condicoes'])): ?>
                                    <div class="orc-text-block mb-3"><?= nl2br(esc((string) $orcamento['condicoes'])) ?></div>
                                <?php else: ?>
                                    <p class="text-muted">Sem condicoes adicionais registradas.</p>
                                <?php endif; ?>

                                <?php if (!empty($orcamento['motivo_rejeicao'])): ?>
                                    <div class="alert alert-danger small mb-0">
                                        <strong>Motivo da rejeicao:</strong> <?= esc((string) $orcamento['motivo_rejeicao']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="orc-tab-pacotes" role="tabpanel" aria-labelledby="orc-tab-pacotes-tab" tabindex="0">
                <div class="orc-show-tab-pane">
                    <?php if ($pacoteOfertaPrincipal !== null): ?>
                        <?php
                        $tokenOfertaPrincipal = trim((string) ($pacoteOfertaPrincipal['token_publico'] ?? ''));
                        $linkOfertaPrincipal = $tokenOfertaPrincipal !== '' ? base_url('pacote/oferta/' . $tokenOfertaPrincipal) : '';
                        $statusOfertaPrincipal = trim((string) ($pacoteOfertaPrincipal['status'] ?? 'ativo'));
                        $badgeOfertaPrincipalClass = [
                            'ativo' => 'bg-primary',
                            'enviado' => 'bg-info text-dark',
                            'escolhido' => 'bg-success',
                            'aplicado_orcamento' => 'bg-primary-subtle text-primary-emphasis',
                            'expirado' => 'bg-warning text-dark',
                            'cancelado' => 'bg-secondary',
                            'erro_envio' => 'bg-danger',
                        ][$statusOfertaPrincipal] ?? 'bg-secondary';
                        ?>
                        <div class="border rounded-4 bg-white p-3 p-lg-4 orc-show-panel orc-pacote-choice" id="orc-pacote-choice">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                                <div>
                                    <div class="small text-uppercase text-muted fw-semibold mb-1">Oferta dinamica principal</div>
                                    <h5 class="mb-0"><?= esc((string) ($pacoteOfertaPrincipal['pacote_nome'] ?? 'Pacote')) ?></h5>
                                </div>
                                <span class="badge <?= esc($badgeOfertaPrincipalClass) ?>"><?= esc($pacoteOfertaStatusLabels[$statusOfertaPrincipal] ?? ucfirst($statusOfertaPrincipal)) ?></span>
                            </div>

                            <div class="small text-muted mb-3">
                                <?= esc($pacoteOfertaStatusLabels[$statusOfertaPrincipal] ?? ucfirst($statusOfertaPrincipal)) ?>
                                <?php if (!empty($pacoteOfertaPrincipal['nivel_nome_exibicao'])): ?>
                                    | Nivel: <?= esc((string) $pacoteOfertaPrincipal['nivel_nome_exibicao']) ?>
                                <?php endif; ?>
                                <?php if (!empty($pacoteOfertaPrincipal['valor_escolhido'])): ?>
                                    | Valor: <?= esc(formatMoney($pacoteOfertaPrincipal['valor_escolhido'])) ?>
                                <?php endif; ?>
                            </div>

                            <div class="small text-muted mb-3">
                                Enviado em <?= esc(formatDate($pacoteOfertaPrincipal['enviado_em'] ?? null, true)) ?>
                                <?php if (!empty($pacoteOfertaPrincipal['escolhido_em'])): ?>
                                    | Escolhido em <?= esc(formatDate($pacoteOfertaPrincipal['escolhido_em'] ?? null, true)) ?>
                                <?php endif; ?>
                                <?php if (!empty($pacoteOfertaPrincipal['aplicado_em'])): ?>
                                    | Aplicado em <?= esc(formatDate($pacoteOfertaPrincipal['aplicado_em'] ?? null, true)) ?>
                                <?php endif; ?>
                            </div>

                            <?php if ($linkOfertaPrincipal !== ''): ?>
                                <div class="input-group input-group-sm mb-3">
                                    <input type="text" class="form-control" readonly value="<?= esc($linkOfertaPrincipal) ?>">
                                    <button class="btn btn-outline-secondary btn-copy-pacote-oferta-link" type="button" data-link="<?= esc($linkOfertaPrincipal) ?>">
                                        <i class="bi bi-clipboard"></i>
                                    </button>
                                    <a class="btn btn-outline-secondary" href="<?= esc($linkOfertaPrincipal) ?>" target="_blank" rel="noopener">
                                        <i class="bi bi-box-arrow-up-right"></i>
                                    </a>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($pacotesOfertasHistorico)): ?>
                                <details>
                                    <summary class="small text-muted">Mostrar historico tecnico de ofertas anteriores (<?= count($pacotesOfertasHistorico) ?>)</summary>
                                    <div class="list-group orc-pacote-link-list mt-3">
                                        <?php foreach ($pacotesOfertasHistorico as $ofertaHist): ?>
                                            <?php
                                            $statusHist = trim((string) ($ofertaHist['status'] ?? 'ativo'));
                                            $badgeHistClass = [
                                                'ativo' => 'bg-primary',
                                                'enviado' => 'bg-info text-dark',
                                                'escolhido' => 'bg-success',
                                                'aplicado_orcamento' => 'bg-primary-subtle text-primary-emphasis',
                                                'expirado' => 'bg-warning text-dark',
                                                'cancelado' => 'bg-secondary',
                                                'erro_envio' => 'bg-danger',
                                            ][$statusHist] ?? 'bg-secondary';
                                            ?>
                                            <div class="list-group-item small d-flex justify-content-between align-items-center gap-2 flex-wrap">
                                                <div>
                                                    <strong><?= esc((string) ($ofertaHist['pacote_nome'] ?? 'Pacote')) ?></strong>
                                                    <span class="text-muted">#<?= (int) ($ofertaHist['id'] ?? 0) ?></span>
                                                    <div class="text-muted"><?= esc(formatDate($ofertaHist['updated_at'] ?? null, true)) ?></div>
                                                </div>
                                                <span class="badge <?= esc($badgeHistClass) ?>"><?= esc($pacoteOfertaStatusLabels[$statusHist] ?? ucfirst($statusHist)) ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </details>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="border rounded-4 bg-white p-4 p-lg-5 orc-show-panel">
                            <div class="orc-empty-state">
                                <i class="bi bi-box2-heart"></i>
                                <h5 class="mb-2">Nenhum pacote ativo no momento</h5>
                                <p class="mb-0">Quando houver uma oferta dinamica vinculada a este orcamento, ela sera exibida aqui com link publico e historico tecnico.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="tab-pane fade" id="orc-tab-orcamento" role="tabpanel" aria-labelledby="orc-tab-orcamento-tab" tabindex="0">
                <div class="orc-show-tab-pane">
                    <div class="card glass-card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <h6 class="mb-0">Itens do Orcamento</h6>
                            <span class="small text-muted"><?= count($itens ?? []) ?> item(ns)</span>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0 orc-show-items">
                                    <thead>
                                        <tr>
                                            <th>Tipo</th>
                                            <th>Descricao</th>
                                            <th>Qtd.</th>
                                            <th>Valor unit.</th>
                                            <th>Desconto</th>
                                            <th>Acrescimo</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($itens ?? [])): ?>
                                            <tr><td colspan="7" class="text-center text-muted py-3">Sem itens cadastrados.</td></tr>
                                        <?php else: ?>
                                            <?php foreach (($itens ?? []) as $item): ?>
                                                <tr>
                                                    <td data-label="Tipo"><?= esc(ucfirst((string) ($item['tipo_item'] ?? 'item'))) ?></td>
                                                    <td data-label="Descricao">
                                                        <div><?= esc((string) ($item['descricao'] ?? '-')) ?></div>
                                                        <?php if (!empty($item['observacoes'])): ?>
                                                            <small class="text-muted"><?= esc((string) $item['observacoes']) ?></small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td data-label="Qtd."><?= esc(number_format((float) ($item['quantidade'] ?? 0), 2, ',', '.')) ?></td>
                                                    <td data-label="Valor unit."><?= esc(formatMoney($item['valor_unitario'] ?? 0)) ?></td>
                                                    <td data-label="Desconto"><?= esc(formatMoney($item['desconto'] ?? 0)) ?></td>
                                                    <td data-label="Acrescimo"><?= esc(formatMoney($item['acrescimo'] ?? 0)) ?></td>
                                                    <td data-label="Total" class="fw-semibold"><?= esc(formatMoney($item['total'] ?? 0)) ?></td>
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

            <div class="tab-pane fade" id="orc-tab-envio" role="tabpanel" aria-labelledby="orc-tab-envio-tab" tabindex="0">
                <div class="orc-show-tab-pane">
                    <?php if ($showSendActions): ?>
                        <div class="card glass-card mb-4 orc-send-actions">
                            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <h6 class="mb-0">Envio do Orcamento</h6>
                                <span class="small text-muted">WhatsApp, e-mail e PDF com trilha completa</span>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-12 col-lg-4">
                                        <div class="border rounded-3 p-3 h-100">
                                            <div class="fw-semibold mb-2">PDF</div>
                                            <p class="small text-muted mb-3">Gere uma nova versao do PDF ou abra a ultima versao disponivel.</p>
                                            <div class="d-grid gap-2">
                                                <?php if (can('orcamentos', 'visualizar')): ?>
                                                    <form method="POST" action="<?= base_url('orcamentos/pdf/' . (int) $orcamento['id'] . '/gerar') . $embedQuery ?>">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="force_new" value="1">
                                                        <button type="submit" class="btn btn-outline-primary btn-sm w-100">
                                                            <i class="bi bi-file-earmark-pdf me-1"></i>Gerar novo PDF
                                                        </button>
                                                    </form>
                                                    <a href="<?= base_url('orcamentos/pdf/' . (int) $orcamento['id']) ?>" target="_blank" rel="noopener" class="btn btn-outline-secondary btn-sm w-100">
                                                        <i class="bi bi-eye me-1"></i>Abrir PDF atual
                                                    </a>
                                                    <?php if ($lastPdfUrl !== ''): ?>
                                                        <a href="<?= esc($lastPdfUrl) ?>" target="_blank" rel="noopener" class="btn btn-outline-dark btn-sm w-100">
                                                            <i class="bi bi-download me-1"></i>Baixar ultimo arquivo
                                                        </a>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <div class="alert alert-light border small mb-0">Sem permissao para gerar ou baixar PDF.</div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12 col-lg-4">
                                        <div class="border rounded-3 p-3 h-100">
                                            <div class="fw-semibold mb-2">Enviar por WhatsApp</div>
                                            <?php if (!can('orcamentos', 'editar')): ?>
                                                <div class="alert alert-light border small mb-0">Sem permissao para enviar orcamento.</div>
                                            <?php else: ?>
                                                <form method="POST" action="<?= base_url('orcamentos/whatsapp/' . (int) $orcamento['id'] . '/enviar') . $embedQuery ?>" data-orc-confirm data-confirm-title="Enviar por WhatsApp?" data-confirm-text="Confirma o envio deste orcamento para o WhatsApp informado?" data-submit-loading-title="Enviando WhatsApp..." data-submit-loading-text="Validando telefone, preparando mensagem e registrando rastreabilidade de envio." data-submit-loading-button="Enviando...">
                                                    <?= csrf_field() ?>
                                                    <div class="mb-2">
                                                        <label class="form-label form-label-sm mb-1">Telefone</label>
                                                        <input type="text" name="telefone_contato" class="form-control form-control-sm" value="<?= esc((string) ($orcamento['telefone_contato'] ?? $orcamento['conversa_telefone'] ?? '')) ?>" <?= $dispatchBlocked ? 'disabled' : '' ?>>
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label form-label-sm mb-1">Mensagem</label>
                                                        <textarea name="mensagem_whatsapp" class="form-control form-control-sm" rows="4" <?= $dispatchBlocked ? 'disabled' : '' ?>><?= esc($defaultWhatsappMessage) ?></textarea>
                                                    </div>
                                                    <div class="form-check mb-3">
                                                        <input class="form-check-input" type="checkbox" id="orcWhatsappPdf" name="incluir_pdf" value="1" checked <?= $dispatchBlocked ? 'disabled' : '' ?>>
                                                        <label class="form-check-label small" for="orcWhatsappPdf">Anexar PDF automaticamente</label>
                                                    </div>
                                                    <button type="submit" class="btn btn-success btn-sm w-100" <?= $dispatchBlocked ? 'disabled' : '' ?>>
                                                        <i class="bi bi-whatsapp me-1"></i>Enviar WhatsApp
                                                    </button>
                                                    <?php if ($dispatchBlocked): ?>
                                                        <div class="small text-muted mt-2">Envio bloqueado para status finalizado.</div>
                                                    <?php endif; ?>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="col-12 col-lg-4">
                                        <div class="border rounded-3 p-3 h-100">
                                            <div class="fw-semibold mb-2">Enviar por E-mail</div>
                                            <?php if (!can('orcamentos', 'editar')): ?>
                                                <div class="alert alert-light border small mb-0">Sem permissao para enviar orcamento.</div>
                                            <?php else: ?>
                                                <form method="POST" action="<?= base_url('orcamentos/email/' . (int) $orcamento['id'] . '/enviar') . $embedQuery ?>" data-orc-confirm data-confirm-title="Enviar por e-mail?" data-confirm-text="Confirma o envio deste orcamento para o e-mail informado?" data-submit-loading-title="Enviando e-mail..." data-submit-loading-text="Validando destinatario, preparando anexo e disparando envio SMTP." data-submit-loading-button="Enviando...">
                                                    <?= csrf_field() ?>
                                                    <div class="mb-2">
                                                        <label class="form-label form-label-sm mb-1">E-mail</label>
                                                        <input type="email" name="email_contato" class="form-control form-control-sm" value="<?= esc((string) ($orcamento['email_contato'] ?? '')) ?>" <?= $dispatchBlocked ? 'disabled' : '' ?>>
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label form-label-sm mb-1">Assunto</label>
                                                        <input type="text" name="assunto_email" class="form-control form-control-sm" value="<?= esc($defaultEmailSubject) ?>" <?= $dispatchBlocked ? 'disabled' : '' ?>>
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label form-label-sm mb-1">Mensagem adicional (opcional)</label>
                                                        <textarea name="mensagem_email" class="form-control form-control-sm" rows="3" placeholder="Ex.: Aguardamos sua aprovacao para seguir com o servico." <?= $dispatchBlocked ? 'disabled' : '' ?>></textarea>
                                                    </div>
                                                    <div class="form-check mb-3">
                                                        <input class="form-check-input" type="checkbox" id="orcEmailPdf" name="incluir_pdf" value="1" checked <?= $dispatchBlocked ? 'disabled' : '' ?>>
                                                        <label class="form-check-label small" for="orcEmailPdf">Anexar PDF automaticamente</label>
                                                    </div>
                                                    <button type="submit" class="btn btn-primary btn-sm w-100" <?= $dispatchBlocked ? 'disabled' : '' ?>>
                                                        <i class="bi bi-envelope me-1"></i>Enviar E-mail
                                                    </button>
                                                    <?php if ($dispatchBlocked): ?>
                                                        <div class="small text-muted mt-2">Envio bloqueado para status finalizado.</div>
                                                    <?php endif; ?>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="card glass-card mb-4">
                            <div class="card-header">
                                <h6 class="mb-0">Envio do Orcamento</h6>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-light border small mb-0">
                                    O envio manual foi bloqueado no status atual para manter o fluxo operacional consistente.
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="tab-pane fade" id="orc-tab-orcamento" role="tabpanel" aria-labelledby="orc-tab-orcamento-tab" tabindex="0">
                <div class="orc-show-tab-pane">
                    <div class="row g-4">
                        <div class="col-12 col-lg-6">
                            <div class="card glass-card h-100">
                                <div class="card-header">
                                    <h6 class="mb-0">Historico de status</h6>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($historicoLista)): ?>
                                        <p class="text-muted mb-0">Sem historico de status.</p>
                                    <?php else: ?>
                                        <ul class="list-group list-group-flush">
                                            <?php foreach ($historicoLista as $evento): ?>
                                                <li class="list-group-item px-0 bg-transparent">
                                                    <div class="d-flex justify-content-between flex-wrap gap-2">
                                                        <strong><?= esc($statusLabels[$evento['status_novo'] ?? ''] ?? ucfirst((string) ($evento['status_novo'] ?? 'status'))) ?></strong>
                                                        <span class="small text-muted"><?= esc(formatDate($evento['created_at'] ?? null, true)) ?></span>
                                                    </div>
                                                    <div class="small text-muted">
                                                        <?php if (!empty($evento['usuario_nome'])): ?>Por <?= esc((string) $evento['usuario_nome']) ?><?php else: ?>Sistema<?php endif; ?>
                                                        <?php if (!empty($evento['observacao'])): ?> - <?= esc((string) $evento['observacao']) ?><?php endif; ?>
                                                    </div>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-lg-6">
                            <div class="card glass-card h-100">
                                <div class="card-header">
                                    <h6 class="mb-0">Rastreabilidade de envios e aprovacoes</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <div class="small text-muted mb-1">Envios registrados</div>
                                        <?php if (empty($enviosLista)): ?>
                                            <p class="text-muted mb-0">Nenhum envio registrado ainda.</p>
                                        <?php else: ?>
                                            <ul class="list-group list-group-flush">
                                                <?php foreach ($enviosLista as $envio): ?>
                                                    <?php
                                                    $envioStatus = (string) ($envio['status'] ?? 'pendente');
                                                    $envioStatusClass = [
                                                        'pendente' => 'bg-secondary',
                                                        'gerado' => 'bg-info text-dark',
                                                        'enviado' => 'bg-success',
                                                        'duplicado' => 'bg-warning text-dark',
                                                        'erro' => 'bg-danger',
                                                    ][$envioStatus] ?? 'bg-secondary';
                                                    $documentoPath = trim((string) ($envio['documento_path'] ?? ''));
                                                    $mensagemResumo = (string) ($envio['mensagem'] ?? '');
                                                    if ($mensagemResumo !== '' && strlen($mensagemResumo) > 180) {
                                                        $mensagemResumo = substr($mensagemResumo, 0, 177) . '...';
                                                    }
                                                    ?>
                                                    <li class="list-group-item px-0 bg-transparent small">
                                                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                                                            <div>
                                                                <strong><?= esc(strtoupper((string) ($envio['canal'] ?? '-'))) ?></strong>
                                                                <span class="badge ms-1 <?= esc($envioStatusClass) ?>"><?= esc(ucfirst(str_replace('_', ' ', $envioStatus))) ?></span>
                                                            </div>
                                                            <span class="text-muted"><?= esc(formatDate(($envio['enviado_em'] ?? $envio['created_at'] ?? null), true)) ?></span>
                                                        </div>
                                                        <div class="text-muted mt-1">
                                                            Destino: <?= esc((string) ($envio['destino'] ?? '-')) ?>
                                                            <?php if (!empty($envio['provedor'])): ?>
                                                                | Provedor: <?= esc((string) $envio['provedor']) ?>
                                                            <?php endif; ?>
                                                            <?php if (!empty($envio['referencia_externa'])): ?>
                                                                | Ref: <?= esc((string) $envio['referencia_externa']) ?>
                                                            <?php endif; ?>
                                                        </div>
                                                        <?php if (!empty($envio['usuario_nome'])): ?>
                                                            <div class="text-muted">Operador: <?= esc((string) $envio['usuario_nome']) ?></div>
                                                        <?php endif; ?>
                                                        <?php if ($mensagemResumo !== ''): ?>
                                                            <div class="mt-1"><?= esc($mensagemResumo) ?></div>
                                                        <?php endif; ?>
                                                        <?php if ($documentoPath !== ''): ?>
                                                            <div class="mt-1">
                                                                <a href="<?= esc(base_url($documentoPath)) ?>" target="_blank" rel="noopener">Abrir documento</a>
                                                            </div>
                                                        <?php endif; ?>
                                                        <?php if (!empty($envio['erro_detalhe'])): ?>
                                                            <div class="text-danger mt-1"><?= esc((string) $envio['erro_detalhe']) ?></div>
                                                        <?php endif; ?>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                    </div>

                                    <div>
                                        <div class="small text-muted mb-1">Acoes publicas</div>
                                        <?php if (empty($aprovacoesLista)): ?>
                                            <p class="text-muted mb-0">Nenhuma acao publica registrada.</p>
                                        <?php else: ?>
                                            <ul class="list-group list-group-flush">
                                                <?php foreach ($aprovacoesLista as $ap): ?>
                                                    <?php $aprovacaoAcao = (string) ($ap['acao'] ?? ($ap['aÃ§Ã£o'] ?? '-')); ?>
                                                    <li class="list-group-item px-0 bg-transparent small">
                                                        <strong><?= esc(ucfirst($aprovacaoAcao)) ?></strong> -
                                                        <?= esc(formatDate($ap['created_at'] ?? null, true)) ?>
                                                        <?php if (!empty($ap['resposta_cliente'])): ?>
                                                            <br><span class="text-muted"><?= esc((string) $ap['resposta_cliente']) ?></span>
                                                        <?php endif; ?>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="orc-tab-financeiro" role="tabpanel" aria-labelledby="orc-tab-financeiro-tab" tabindex="0">
                <div class="orc-show-tab-pane">
                    <div class="row g-3">
                        <div class="col-12 col-xl-5">
                            <div class="border rounded-4 bg-white p-3 p-lg-4 h-100 orc-show-panel">
                                <div class="small text-uppercase text-muted fw-semibold mb-3">Resumo financeiro</div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Subtotal</span>
                                    <strong><?= esc(formatMoney($orcamento['subtotal'] ?? 0)) ?></strong>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Desconto</span>
                                    <strong class="text-danger">- <?= esc(formatMoney($orcamento['desconto'] ?? 0)) ?></strong>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Acrescimo</span>
                                    <strong class="text-success">+ <?= esc(formatMoney($orcamento['acrescimo'] ?? 0)) ?></strong>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between">
                                    <span class="fw-semibold">Total final</span>
                                    <strong class="fs-5"><?= esc(formatMoney($orcamento['total'] ?? 0)) ?></strong>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-xl-7">
                            <div class="border rounded-4 bg-white p-3 p-lg-4 h-100 orc-show-panel">
                                <div class="small text-uppercase text-muted fw-semibold mb-3">Link publico e referencia comercial</div>

                                <?php if ($resumoPublicLink !== ''): ?>
                                    <div class="small text-muted mb-1"><?= esc($resumoPublicLinkLabel) ?></div>
                                    <div class="input-group input-group-sm mb-3">
                                        <input type="text" class="form-control" readonly id="orcamentoPublicLinkInput" value="<?= esc($resumoPublicLink) ?>" data-copy-success-text="<?= esc($resumoPublicLinkCopyText) ?>">
                                        <button class="btn btn-outline-secondary" type="button" id="btnCopyPublicLink" title="Copiar link publico">
                                            <i class="bi bi-clipboard"></i>
                                        </button>
                                        <a class="btn btn-outline-secondary" href="<?= esc($resumoPublicLink) ?>" target="_blank" rel="noopener">
                                            <i class="bi bi-box-arrow-up-right"></i>
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-light border small">
                                        O link publico sera exibido aqui assim que o token de compartilhamento estiver disponivel.
                                    </div>
                                <?php endif; ?>

                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <span class="orc-field-label">Status comercial</span>
                                        <div class="orc-field-value"><?= esc($statusDisplayLabel) ?></div>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <span class="orc-field-label">Validade</span>
                                        <div class="orc-field-value"><?= esc($validadeFormatada) ?></div>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <span class="orc-field-label">Tipo de registro</span>
                                        <div class="orc-field-value"><?= esc($tipoOrcamentoLabel) ?></div>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <span class="orc-field-label">Versao atual</span>
                                        <div class="orc-field-value"><?= esc($versaoAtual) ?></div>
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

<script>
(function () {
    const shouldShowSubmitProgress = (form) => {
        return form.hasAttribute('data-submit-loading-title')
            || form.hasAttribute('data-submit-loading-text')
            || form.hasAttribute('data-submit-loading-button');
    };

    const setSubmittingState = (form, isSubmitting) => {
        if (!shouldShowSubmitProgress(form)) {
            return;
        }
        const submitControls = form.querySelectorAll('button[type="submit"], input[type="submit"]');
        submitControls.forEach((control) => {
            const btn = control;
            if (!isSubmitting) {
                btn.disabled = false;
                if (btn.tagName === 'BUTTON' && btn.dataset.originalHtml) {
                    btn.innerHTML = btn.dataset.originalHtml;
                    delete btn.dataset.originalHtml;
                }
                return;
            }

            btn.disabled = true;
            if (btn.tagName === 'BUTTON') {
                if (!btn.dataset.originalHtml) {
                    btn.dataset.originalHtml = btn.innerHTML;
                }
                const loadingLabel = form.getAttribute('data-submit-loading-button') || 'Processando...';
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>' + loadingLabel;
            }
        });
    };

    const showSubmitProgress = (form) => {
        if (!shouldShowSubmitProgress(form)) {
            return;
        }
        if (!window.Swal || typeof window.Swal.fire !== 'function') {
            return;
        }

        const title = form.getAttribute('data-submit-loading-title') || 'Processando envio...';
        const text = form.getAttribute('data-submit-loading-text') || 'Aguarde, estamos executando a operacao no servidor.';

        window.Swal.fire({
            icon: 'info',
            title,
            text,
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => {
                if (window.Swal && typeof window.Swal.showLoading === 'function') {
                    window.Swal.showLoading();
                }
            },
        });
    };

    const feedbackApi = (() => {
        if (window.DSFeedback && typeof window.DSFeedback.confirm === 'function') {
            return window.DSFeedback;
        }

        const fire = async (options = {}) => {
            if (window.Swal && typeof window.Swal.fire === 'function') {
                return window.Swal.fire(options);
            }

            const message = [options.title || '', options.text || ''].filter(Boolean).join('\n\n');

            if (options.showCancelButton) {
                const confirmed = window.confirm(message || 'Deseja continuar?');
                return {
                    isConfirmed: confirmed,
                    isDismissed: !confirmed,
                    value: confirmed,
                };
            }

            if (message) {
                window.alert(message);
            }

            return {
                isConfirmed: true,
                isDismissed: false,
                value: true,
            };
        };

        return {
            fire,
            confirm: (options = {}) => fire({
                showCancelButton: true,
                confirmButtonText: 'Confirmar',
                cancelButtonText: 'Cancelar',
                ...options,
            }).then((result) => Boolean(result && result.isConfirmed)),
        };
    })();

    const forms = document.querySelectorAll('form[data-orc-confirm]');
    forms.forEach((form) => {
        form.addEventListener('submit', async (event) => {
            if (form.dataset.confirmed === '1') {
                return;
            }

            event.preventDefault();
            const title = form.getAttribute('data-confirm-title') || 'Confirmar acao?';
            const text = form.getAttribute('data-confirm-text') || 'Deseja continuar com esta acao?';

            const confirmed = await feedbackApi.confirm({
                icon: 'question',
                title,
                text,
                showCancelButton: true,
                confirmButtonText: 'Confirmar',
                cancelButtonText: 'Cancelar',
            });

            if (!confirmed) {
                return;
            }

            form.dataset.confirmed = '1';
            setSubmittingState(form, true);
            showSubmitProgress(form);
            setTimeout(() => {
                form.submit();
            }, 80);
        });
    });

    const copyBtn = document.getElementById('btnCopyPublicLink');
    const linkInput = document.getElementById('orcamentoPublicLinkInput');
    if (copyBtn && linkInput) {
        copyBtn.addEventListener('click', async () => {
            const value = (linkInput.value || '').trim();
            const successText = (linkInput.getAttribute('data-copy-success-text') || '').trim() || 'Link publico copiado para a area de transferencia.';
            if (!value) {
                return;
            }

            try {
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    await navigator.clipboard.writeText(value);
                } else {
                    linkInput.select();
                    document.execCommand('copy');
                }

                if (window.Swal) {
                    window.Swal.fire({
                        icon: 'success',
                        title: 'Link copiado',
                        text: successText,
                        timer: 1500,
                        showConfirmButton: false,
                    });
                }
            } catch (error) {
                console.error('[Orcamentos] falha ao copiar link publico', error);
                if (window.Swal) {
                    window.Swal.fire({
                        icon: 'error',
                        title: 'Falha ao copiar',
                        text: 'Nao foi possivel copiar o link automaticamente.',
                    });
                }
            }
        });
    }

    const pacoteCopyButtons = document.querySelectorAll('.btn-copy-pacote-oferta-link');
    pacoteCopyButtons.forEach((button) => {
        button.addEventListener('click', async () => {
            const value = String(button.getAttribute('data-link') || '').trim();
            if (!value) {
                return;
            }

            try {
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    await navigator.clipboard.writeText(value);
                } else {
                    const tempInput = document.createElement('input');
                    tempInput.value = value;
                    document.body.appendChild(tempInput);
                    tempInput.select();
                    document.execCommand('copy');
                    tempInput.remove();
                }

                if (window.Swal) {
                    window.Swal.fire({
                        icon: 'success',
                        title: 'Link copiado',
                        text: 'Link da oferta de pacote copiado com sucesso.',
                        timer: 1500,
                        showConfirmButton: false,
                    });
                }
            } catch (error) {
                console.error('[Orcamentos] falha ao copiar link da oferta de pacote', error);
                if (window.Swal) {
                    window.Swal.fire({
                        icon: 'error',
                        title: 'Falha ao copiar',
                        text: 'Nao foi possivel copiar o link da oferta automaticamente.',
                    });
                }
            }
        });
    });

    if (<?= $isEmbedded ? 'true' : 'false' ?> && window.parent && window.parent !== window) {
        const flashPayload = window.__ERP_FLASH || {};
        const successMessage = String(flashPayload.success || '').trim();
        if (successMessage) {
            try {
                window.parent.postMessage({
                    type: 'os:orcamento-updated',
                    osId: Number(<?= (int) ($orcamento['os_id'] ?? 0) ?>),
                    orcamentoId: Number(<?= (int) ($orcamento['id'] ?? 0) ?>),
                    message: successMessage,
                }, window.location.origin);
            } catch (error) {
                console.error('[Orcamentos] Falha ao notificar a tela pai sobre atualizacao do orcamento.', error);
            }
        }
    }
})();
</script>

<style>
.orc-show-overview__aside {
    min-width: 220px;
}
.orc-status-pill {
    font-size: .85rem;
    padding: .55rem .8rem;
    border-radius: 999px;
}
.orc-overview-metric {
    height: 100%;
    padding: 1rem;
    border-radius: 1rem;
    border: 1px solid rgba(0, 0, 0, .08);
    background: linear-gradient(180deg, rgba(255,255,255,.92), rgba(247,248,255,.92));
}
.orc-overview-metric__label {
    display: block;
    font-size: .72rem;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: #6c757d;
    margin-bottom: .45rem;
}
.orc-overview-metric__value {
    display: block;
    font-size: .95rem;
    line-height: 1.35;
    color: #1f2937;
}
.orc-show-tab-card .card-body {
    padding-top: 1rem;
}
.orc-show-tab-nav {
    margin-bottom: .25rem;
}
.nav-tabs.ds-tabs-scroll.orc-show-tab-nav {
    gap: .375rem;
    overflow-x: hidden !important;
    padding-bottom: 0 !important;
    scrollbar-width: none !important;
    -ms-overflow-style: none;
}
.nav-tabs.ds-tabs-scroll.orc-show-tab-nav .nav-item {
    flex: 1 1 0 !important;
    min-width: 0;
}
.nav-tabs.ds-tabs-scroll.orc-show-tab-nav .nav-link {
    width: 100%;
    min-width: 0;
    padding: .95rem .85rem;
    font-size: .95rem;
    line-height: 1.15;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.nav-tabs.ds-tabs-scroll.orc-show-tab-nav::-webkit-scrollbar {
    width: 0 !important;
    height: 0 !important;
    display: none;
}
.orc-show-tab-nav .orc-tab-label {
    display: inline-block;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    vertical-align: middle;
}
.orc-show-tab-nav .orc-tab-label--short,
.orc-show-tab-nav .orc-tab-label--micro {
    display: none;
}
@media (max-width: 1500px) {
    .nav-tabs.ds-tabs-scroll.orc-show-tab-nav .nav-link {
        padding-inline: .65rem;
        font-size: .88rem;
    }
    .orc-show-tab-nav .orc-tab-label--full {
        display: none;
    }
    .orc-show-tab-nav .orc-tab-label--short {
        display: inline-block;
    }
}
@media (max-width: 1200px) {
    .nav-tabs.ds-tabs-scroll.orc-show-tab-nav {
        gap: .3rem;
    }
    .nav-tabs.ds-tabs-scroll.orc-show-tab-nav .nav-link {
        padding: .85rem .5rem;
        font-size: .8rem;
    }
}
@media (max-width: 768px) {
    .nav-tabs.ds-tabs-scroll.orc-show-tab-nav .nav-link {
        padding: .75rem .4rem;
        font-size: .74rem;
    }
}
@media (max-width: 640px) {
    .nav-tabs.ds-tabs-scroll.orc-show-tab-nav {
        gap: .2rem;
    }
    .nav-tabs.ds-tabs-scroll.orc-show-tab-nav .nav-link {
        padding: .7rem .3rem;
        font-size: .68rem;
    }
    .orc-show-tab-nav .orc-tab-label--short {
        display: none;
    }
    .orc-show-tab-nav .orc-tab-label--micro {
        display: inline-block;
    }
}
@media (max-width: 430px) {
    .nav-tabs.ds-tabs-scroll.orc-show-tab-nav .nav-link {
        padding: .62rem .2rem;
        font-size: .62rem;
    }
}
@media (max-width: 390px) {
    .nav-tabs.ds-tabs-scroll.orc-show-tab-nav .nav-link {
        padding: .58rem .16rem;
        font-size: .6rem;
    }
}
@media (max-width: 360px) {
    .nav-tabs.ds-tabs-scroll.orc-show-tab-nav .nav-link {
        padding: .56rem .14rem;
        font-size: .58rem;
    }
}
@media (max-width: 320px) {
    .nav-tabs.ds-tabs-scroll.orc-show-tab-nav .nav-link {
        padding: .5rem .12rem;
        font-size: .54rem;
    }
}
.orc-show-tab-pane {
    min-height: 320px;
}
.orc-show-panel {
    border-color: rgba(0, 0, 0, .07) !important;
    box-shadow: inset 0 1px 0 rgba(255,255,255,.7);
}
.orc-field-label {
    display: block;
    font-size: .74rem;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: #6c757d;
    margin-bottom: .25rem;
}
.orc-field-value {
    color: #1f2937;
    font-weight: 500;
    line-height: 1.45;
}
.orc-text-block {
    line-height: 1.6;
    color: #1f2937;
}
.orc-empty-state {
    min-height: 220px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    gap: .75rem;
    color: #667085;
}
.orc-empty-state i {
    font-size: 2rem;
    color: #7c8cff;
}
.orc-empty-state--compact {
    min-height: 100%;
    align-items: flex-start;
    justify-content: flex-start;
    text-align: left;
}
.orc-empty-state--compact i {
    font-size: 1.4rem;
}
.orc-equipment-photo {
    width: 100%;
    max-height: 360px;
    object-fit: cover;
    border-radius: 1rem;
    border: 1px solid rgba(0, 0, 0, .08);
    background: #f8f9fb;
}
.orc-equipment-placeholder {
    min-height: 260px;
    border: 1px dashed rgba(108, 99, 255, .28);
    border-radius: 1rem;
    background: rgba(108, 99, 255, .04);
    color: #667085;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: .65rem;
    text-align: center;
    padding: 1.5rem;
}
.orc-equipment-placeholder i {
    font-size: 1.8rem;
    color: #7c8cff;
}
.orc-color-chip {
    width: 16px;
    height: 16px;
    border-radius: 999px;
    border: 1px solid rgba(0, 0, 0, .1);
    flex: 0 0 auto;
}
.orc-send-actions .form-label-sm {
    font-size: .78rem;
}
.orc-send-actions textarea {
    resize: vertical;
    min-height: 88px;
}
.orc-pacote-link-list .list-group-item {
    border-radius: .7rem;
    margin-bottom: .5rem;
    border: 1px solid rgba(0, 0, 0, .08);
}
@media (max-width: 430px) {
    .orc-show-overview__aside {
        width: 100%;
        min-width: 0;
        text-align: left !important;
    }
    .orc-show-items thead {
        display: none;
    }
    .orc-pacote-choice .btn {
        width: 100%;
    }
    .orc-pacote-choice .input-group .btn {
        width: auto;
    }
    .orc-show-items tbody tr {
        display: block;
        margin-bottom: .75rem;
        border: 1px solid rgba(0, 0, 0, .08);
        border-radius: .75rem;
    }
    .orc-show-items tbody td {
        display: flex;
        justify-content: space-between;
        gap: .75rem;
        border: 0;
        border-bottom: 1px solid rgba(0, 0, 0, .06);
        padding: .6rem .75rem;
        white-space: normal;
    }
    .orc-show-items tbody td::before {
        content: attr(data-label);
        min-width: 84px;
        font-weight: 600;
        color: #6c757d;
        font-size: .8rem;
    }
    .orc-show-items tbody td:last-child {
        border-bottom: 0;
    }
}
@media (max-width: 390px) {
    .orc-send-actions .border,
    .orc-show-panel {
        padding: .8rem !important;
    }
    .orc-send-actions .btn {
        white-space: normal;
    }
    .orc-pacote-choice .list-group-item {
        padding: .65rem;
    }
}
@media (max-width: 360px) {
    .orc-show-overview .fs-4 {
        font-size: 1.45rem !important;
    }
    .orc-send-actions .btn {
        font-size: .82rem;
        padding: .45rem .55rem;
    }
    .orc-show-items tbody td {
        padding: .5rem .6rem;
    }
}
@media (max-width: 320px) {
    .orc-send-actions .form-control,
    .orc-send-actions .form-select {
        font-size: .78rem;
    }
    .orc-pacote-choice .form-control {
        font-size: .78rem;
    }
    .orc-show-items tbody td::before {
        min-width: 72px;
        font-size: .74rem;
    }
}
</style>
<?= $this->endSection() ?>
