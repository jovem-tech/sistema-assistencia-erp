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
$clienteNome = trim((string) ($orcamento['cliente_nome'] ?? ''));
if ($clienteNome === '') {
    $clienteNome = (string) ($orcamento['cliente_nome_avulso'] ?? 'Cliente eventual');
}
$defaultWhatsappMessage = (string) ($defaultWhatsappMessage ?? '');
$defaultEmailSubject = (string) ($defaultEmailSubject ?? '');
$lastPdfUrl = (string) ($lastPdfUrl ?? '');
$pacoteOfertaPrincipal = is_array($pacoteOfertaPrincipal ?? null) ? $pacoteOfertaPrincipal : null;
$pacotesOfertasHistorico = is_array($pacotesOfertasHistorico ?? null) ? $pacotesOfertasHistorico : [];
$tokenPublicoOrcamento = trim((string) ($orcamento['token_publico'] ?? ''));
$linkPublicoOrcamento = $tokenPublicoOrcamento !== '' ? base_url('orcamento/' . $tokenPublicoOrcamento) : '';
$tokenOfertaResumo = $pacoteOfertaPrincipal !== null
    ? trim((string) ($pacoteOfertaPrincipal['token_publico'] ?? ''))
    : '';
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
    'aplicado_orcamento' => 'Aplicado no orçamento',
    'expirado' => 'Expirado',
    'cancelado' => 'Cancelado',
    'erro_envio' => 'Erro no envio',
];
$dispatchBlocked = in_array($status, ['aprovado', 'pendente_abertura_os', 'pacote_aprovado', 'cancelado', 'convertido'], true);
$isEmbedded = !empty($isEmbedded);
$embedQuery = $isEmbedded ? '?embed=1' : '';
$canEditOrcamento = can('orcamentos', 'editar');
$isLockedStatus = in_array($status, ['aprovado', 'pendente_abertura_os', 'pacote_aprovado', 'convertido'], true);
$canOpenEditor = $canEditOrcamento && !$isLockedStatus;
$canCreateRevision = $canEditOrcamento && in_array($status, ['aprovado', 'pendente_abertura_os', 'pacote_aprovado', 'rejeitado', 'vencido'], true);
$showStatusUpdateForm = $canEditOrcamento && count($statusOptions) > 1;
$showSendActions = !$dispatchBlocked;
?>

<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h2 class="mb-0"><i class="bi bi-file-earmark-text me-2"></i>Orçamento <?= esc((string) ($orcamento['numero'] ?? '#')) ?></h2>
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
        <?php if ($canCreateRevision): ?>
            <form method="POST" action="<?= base_url('orcamentos/revisar/' . (int) $orcamento['id']) . $embedQuery ?>" class="d-inline-flex" data-orc-confirm data-confirm-title="Criar nova revisao?" data-confirm-text="Vamos duplicar este orcamento em uma nova versao editavel para submeter nova autorizacao ao cliente.">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-outline-primary">
                    <i class="bi bi-layers me-1"></i>Criar revisao
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-12 col-lg-8">
        <div class="card glass-card h-100">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <div class="small text-muted">Cliente</div>
                    <div class="fw-semibold"><?= esc($clienteNome) ?></div>
                    <div class="small text-muted mt-1">
                        <?= esc($tipoLabels[$tipoOrcamento] ?? ucfirst($tipoOrcamento)) ?>
                        | Versão <?= esc((string) ($orcamento['versao'] ?? 1)) ?>
                    </div>
                </div>
                <span class="badge <?= esc($statusClass) ?>"><?= esc($statusLabels[$status] ?? ucfirst($status)) ?></span>
            </div>
            <div class="card-body">
                <?php if ($tipoOrcamento === 'previo'): ?>
                    <div class="alert alert-info small">
                        Este registro esta tratado como <strong>orcamento previo</strong>: uma estimativa inicial para cliente que ainda pode depender da entrada do equipamento e de analise técnica presencial.
                    </div>
                <?php else: ?>
                    <div class="alert alert-light border small">
                        Este registro esta tratado como <strong>orcamento com equipamento na assistencia</strong> e deve refletir a OS/equipamento em atendimento.
                    </div>
                <?php endif; ?>

                <?php if (!empty($orcamento['orcamento_revisao_de_id'])): ?>
                    <div class="alert alert-warning small">
                        Revisao derivada do orcamento base
                        <strong><?= esc((string) ($orcamento['revisao_base_numero'] ?? ('#' . (int) $orcamento['orcamento_revisao_de_id']))) ?></strong>.
                        Use esta versao para ajustes e nova autorizacao do cliente.
                    </div>
                <?php endif; ?>

                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-4">
                        <div class="small text-muted">Telefone</div>
                        <div><?= esc((string) ($orcamento['telefone_contato'] ?? '-')) ?></div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="small text-muted">Email</div>
                        <div><?= esc((string) ($orcamento['email_contato'] ?? '-')) ?></div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="small text-muted">Validade</div>
                        <div><?= esc(formatDate($orcamento['validade_data'] ?? null)) ?></div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="small text-muted">Tipo</div>
                        <div><?= esc($tipoLabels[$tipoOrcamento] ?? ucfirst($tipoOrcamento)) ?></div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="small text-muted">Origem</div>
                        <div><?= esc(ucfirst(str_replace('_', ' ', (string) ($orcamento['origem'] ?? 'manual')))) ?></div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="small text-muted">OS vinculada</div>
                        <div><?= !empty($orcamento['numero_os']) ? esc((string) $orcamento['numero_os']) : '-' ?></div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="small text-muted">Conversa vinculada</div>
                        <div><?= !empty($orcamento['conversa_id']) ? '#' . esc((string) $orcamento['conversa_id']) : '-' ?></div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="small text-muted">Conversao</div>
                        <div>
                            <?php if (!empty($orcamento['convertido_tipo'])): ?>
                                <?= esc((string) $orcamento['convertido_tipo']) ?>
                                <?php if (!empty($orcamento['convertido_id'])): ?>
                                    #<?= esc((string) $orcamento['convertido_id']) ?>
                                <?php endif; ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <?php if (!empty($orcamento['prazo_execucao'])): ?>
                    <div class="mb-3">
                        <div class="small text-muted">Prazo de execução</div>
                        <div><?= esc((string) $orcamento['prazo_execucao']) ?></div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($orcamento['observacoes'])): ?>
                    <div class="mb-3">
                        <div class="small text-muted">Observações</div>
                        <div><?= nl2br(esc((string) $orcamento['observacoes'])) ?></div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($orcamento['condicoes'])): ?>
                    <div class="mb-3">
                        <div class="small text-muted">Condições</div>
                        <div><?= nl2br(esc((string) $orcamento['condicoes'])) ?></div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($orcamento['motivo_rejeicao'])): ?>
                    <div class="alert alert-danger small mb-0">
                        <strong>Motivo da rejeição:</strong> <?= esc((string) $orcamento['motivo_rejeicao']) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-4">
        <div class="card glass-card h-100">
            <div class="card-header">
                <h6 class="mb-0">Resumo financeiro</h6>
            </div>
            <div class="card-body">
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
                <div class="d-flex justify-content-between mb-3">
                    <span class="fw-semibold">Total</span>
                    <strong class="fs-5"><?= esc(formatMoney($orcamento['total'] ?? 0)) ?></strong>
                </div>

                <?php if (!empty($resumoPublicLink)): ?>
                    <div class="small text-muted mb-1"><?= esc($resumoPublicLinkLabel) ?></div>
                    <div class="input-group input-group-sm mb-3">
                        <input type="text" class="form-control" readonly id="orcamentoPublicLinkInput" value="<?= esc($resumoPublicLink) ?>" data-copy-success-text="<?= esc($resumoPublicLinkCopyText) ?>">
                        <button class="btn btn-outline-secondary" type="button" id="btnCopyPublicLink" title="Copiar link público">
                            <i class="bi bi-clipboard"></i>
                        </button>
                        <a class="btn btn-outline-secondary" href="<?= esc($resumoPublicLink) ?>" target="_blank" rel="noopener">
                            <i class="bi bi-box-arrow-up-right"></i>
                        </a>
                    </div>
                <?php endif; ?>

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
</div>

<?php $canConvert = can('orcamentos', 'editar') && in_array($status, ['aprovado', 'pendente_abertura_os', 'pacote_aprovado'], true); ?>
<?php if ($canConvert): ?>
<div class="card glass-card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="mb-0">Conversão do Orçamento</h6>
        <span class="small text-muted">Converta aprovado em execução operacional</span>
    </div>
    <div class="card-body">
        <?php if (in_array($status, ['pendente_abertura_os', 'pacote_aprovado'], true) && empty($orcamento['os_id'])): ?>
            <div class="alert alert-warning small">
                Este orçamento foi aprovado sem OS vinculada. Para iniciar a execução, use <strong>Converter para OS</strong>.
            </div>
        <?php endif; ?>
        <div class="d-flex gap-2 flex-wrap">
            <form method="POST" action="<?= base_url('orcamentos/converter/' . (int) $orcamento['id']) . $embedQuery ?>" data-orc-confirm data-confirm-title="Converter para OS?" data-confirm-text="Confirma a conversão deste orçamento para OS/execução?">
                <?= csrf_field() ?>
                <input type="hidden" name="tipo" value="os">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-clipboard-check me-1"></i>
                    <?= empty($orcamento['os_id']) ? 'Abrir OS e converter' : 'Converter em execução OS' ?>
                </button>
            </form>
            <form method="POST" action="<?= base_url('orcamentos/converter/' . (int) $orcamento['id']) . $embedQuery ?>" data-orc-confirm data-confirm-title="Converter para venda?" data-confirm-text="Confirma a conversão deste orçamento para venda manual?">
                <?= csrf_field() ?>
                <input type="hidden" name="tipo" value="venda">
                <button type="submit" class="btn btn-outline-primary">
                    <i class="bi bi-cart-check me-1"></i>Converter em venda
                </button>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
<?php if ($status === 'convertido'): ?>
<div class="alert alert-success mb-4">
    Orçamento convertido com sucesso.
    <?php if ((string) ($orcamento['convertido_tipo'] ?? '') === 'os' && (int) ($orcamento['convertido_id'] ?? 0) > 0): ?>
        <a href="<?= base_url('os/visualizar/' . (int) $orcamento['convertido_id']) ?>" class="alert-link ms-1" target="_blank" rel="noopener">Abrir OS convertida</a>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($showSendActions): ?>
<div class="card glass-card mb-4 orc-send-actions">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="mb-0">Envio do Orçamento</h6>
        <span class="small text-muted">WhatsApp, e-mail e PDF com trilha completa</span>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-12 col-lg-4">
                <div class="border rounded-3 p-3 h-100">
                    <div class="fw-semibold mb-2">PDF</div>
                    <p class="small text-muted mb-3">Gere uma nova versão do PDF ou abra a última versão disponível.</p>
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
                            <div class="alert alert-light border small mb-0">Sem permissão para gerar/baixar PDF.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-4">
                <div class="border rounded-3 p-3 h-100">
                    <div class="fw-semibold mb-2">Enviar por WhatsApp</div>
                    <?php if (!can('orcamentos', 'editar')): ?>
                        <div class="alert alert-light border small mb-0">Sem permissão para enviar orçamento.</div>
                    <?php else: ?>
                    <form method="POST" action="<?= base_url('orcamentos/whatsapp/' . (int) $orcamento['id'] . '/enviar') . $embedQuery ?>" data-orc-confirm data-confirm-title="Enviar por WhatsApp?" data-confirm-text="Confirma o envio deste orçamento para o WhatsApp informado?" data-submit-loading-title="Enviando WhatsApp..." data-submit-loading-text="Validando telefone, preparando mensagem e registrando rastreabilidade de envio." data-submit-loading-button="Enviando...">
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
                        <div class="alert alert-light border small mb-0">Sem permissão para enviar orçamento.</div>
                    <?php else: ?>
                    <form method="POST" action="<?= base_url('orcamentos/email/' . (int) $orcamento['id'] . '/enviar') . $embedQuery ?>" data-orc-confirm data-confirm-title="Enviar por e-mail?" data-confirm-text="Confirma o envio deste orçamento para o e-mail informado?" data-submit-loading-title="Enviando e-mail..." data-submit-loading-text="Validando destinatario, preparando anexo e disparando envio SMTP." data-submit-loading-button="Enviando...">
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
        <h6 class="mb-0">Envio do Or&ccedil;amento</h6>
    </div>
    <div class="card-body">
        <div class="alert alert-light border small mb-0">
            O envio manual foi bloqueado no status atual para manter o fluxo operacional consistente.
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($pacoteOfertaPrincipal !== null): ?>
<div class="card glass-card mb-4 orc-pacote-choice" id="orc-pacote-choice">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="mb-0">Oferta Dinamica de Pacote</h6>
        <span class="small text-muted">Fluxo oficial aplicado ao orcamento</span>
    </div>
    <div class="card-body">
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
        <div class="list-group orc-pacote-link-list">
            <div class="list-group-item">
                <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                    <div>
                        <div class="fw-semibold"><?= esc((string) ($pacoteOfertaPrincipal['pacote_nome'] ?? 'Pacote')) ?></div>
                        <div class="small text-muted">
                            <?= esc($pacoteOfertaStatusLabels[$statusOfertaPrincipal] ?? ucfirst($statusOfertaPrincipal)) ?>
                            <?php if (!empty($pacoteOfertaPrincipal['nivel_nome_exibicao'])): ?>
                                | Nivel: <?= esc((string) $pacoteOfertaPrincipal['nivel_nome_exibicao']) ?>
                            <?php endif; ?>
                            <?php if (!empty($pacoteOfertaPrincipal['valor_escolhido'])): ?>
                                | Valor: <?= esc(formatMoney($pacoteOfertaPrincipal['valor_escolhido'])) ?>
                            <?php endif; ?>
                        </div>
                        <div class="small text-muted">
                            Enviado em <?= esc(formatDate($pacoteOfertaPrincipal['enviado_em'] ?? null, true)) ?>
                            <?php if (!empty($pacoteOfertaPrincipal['escolhido_em'])): ?>
                                | Escolhido em <?= esc(formatDate($pacoteOfertaPrincipal['escolhido_em'] ?? null, true)) ?>
                            <?php endif; ?>
                            <?php if (!empty($pacoteOfertaPrincipal['aplicado_em'])): ?>
                                | Aplicado em <?= esc(formatDate($pacoteOfertaPrincipal['aplicado_em'] ?? null, true)) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <span class="badge <?= esc($badgeOfertaPrincipalClass) ?>"><?= esc($pacoteOfertaStatusLabels[$statusOfertaPrincipal] ?? ucfirst($statusOfertaPrincipal)) ?></span>
                </div>
                <?php if ($linkOfertaPrincipal !== ''): ?>
                    <div class="input-group input-group-sm mt-2">
                        <input type="text" class="form-control" readonly value="<?= esc($linkOfertaPrincipal) ?>">
                        <button class="btn btn-outline-secondary btn-copy-pacote-oferta-link" type="button" data-link="<?= esc($linkOfertaPrincipal) ?>">
                            <i class="bi bi-clipboard"></i>
                        </button>
                        <a class="btn btn-outline-secondary" href="<?= esc($linkOfertaPrincipal) ?>" target="_blank" rel="noopener">
                            <i class="bi bi-box-arrow-up-right"></i>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($pacotesOfertasHistorico)): ?>
            <details class="mt-3">
                <summary class="small text-muted">Mostrar histórico tecnico de ofertas anteriores (<?= count($pacotesOfertasHistorico) ?>)</summary>
                <div class="list-group orc-pacote-link-list mt-2">
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
</div>
<?php endif; ?>

<div class="card glass-card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h6 class="mb-0">Itens do Orçamento</h6>
        <span class="small text-muted"><?= count($itens ?? []) ?> item(ns)</span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover mb-0 orc-show-items">
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Descrição</th>
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
                                <td data-label="Descrição">
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

<div class="row g-4">
    <div class="col-12 col-lg-6">
        <div class="card glass-card h-100">
            <div class="card-header">
                <h6 class="mb-0">Histórico de status</h6>
            </div>
            <div class="card-body">
                <?php if (empty($histórico ?? [])): ?>
                    <p class="text-muted mb-0">Sem histórico de status.</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach (($histórico ?? []) as $evento): ?>
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
                <h6 class="mb-0">Rastreabilidade de envios/aprovacoes</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="small text-muted mb-1">Envios registrados</div>
                    <?php if (empty($envios ?? [])): ?>
                        <p class="text-muted mb-0">Nenhum envio registrado ainda.</p>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach (($envios ?? []) as $envio): ?>
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
                                    <?php if (!empty($envio['mensagem'])): ?>
                                        <?php
                                        $mensagemResumo = (string) $envio['mensagem'];
                                        if (strlen($mensagemResumo) > 180) {
                                            $mensagemResumo = substr($mensagemResumo, 0, 177) . '...';
                                        }
                                        ?>
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
                    <div class="small text-muted mb-1">Ações publicas</div>
                    <?php if (empty($aprovacoes ?? [])): ?>
                        <p class="text-muted mb-0">Nenhuma ação publica registrada.</p>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach (($aprovacoes ?? []) as $ap): ?>
                                <li class="list-group-item px-0 bg-transparent small">
                                    <strong><?= esc(ucfirst((string) ($ap['ação'] ?? '-'))) ?></strong> -
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
        const text = form.getAttribute('data-submit-loading-text') || 'Aguarde, estamos executando a operação no servidor.';

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
            const title = form.getAttribute('data-confirm-title') || 'Confirmar ação?';
            const text = form.getAttribute('data-confirm-text') || 'Deseja continuar com esta ação?';

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
                console.error('[Orçamentos] falha ao copiar link publico', error);
                if (window.Swal) {
                    window.Swal.fire({
                        icon: 'error',
                        title: 'Falha ao copiar',
                        text: 'Não foi possível copiar o link automaticamente.',
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
                console.error('[Orçamentos] falha ao copiar link da oferta de pacote', error);
                if (window.Swal) {
                    window.Swal.fire({
                        icon: 'error',
                        title: 'Falha ao copiar',
                        text: 'Não foi possível copiar o link da oferta automaticamente.',
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
    .orc-send-actions .border {
        padding: .7rem !important;
    }
    .orc-send-actions .btn {
        white-space: normal;
    }
    .orc-pacote-choice .list-group-item {
        padding: .65rem;
    }
}
@media (max-width: 360px) {
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
