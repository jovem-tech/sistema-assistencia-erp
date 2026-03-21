<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php $isEdit = !empty($contato); ?>
<?php
$statusRel = (string) ($contato['status_relacionamento'] ?? '');
$statusLabel = 'Lead nãovo';
$statusClass = 'text-bg-info text-dark';
if ($statusRel === 'lead_qualificado') {
    $statusLabel = 'Lead qualificado';
    $statusClass = 'text-bg-warning text-dark';
} elseif ($statusRel === 'cliente_convertido') {
    $statusLabel = 'Cliente convertido';
    $statusClass = 'text-bg-success';
}
$engajamentoStatus = (string) ($contato['engajamento_status'] ?? '');
$engajamentoLabel = 'Ativo';
$engajamentoClass = 'text-bg-success-subtle text-success-emphasis border';
if ($engajamentoStatus === 'em_risco') {
    $engajamentoLabel = 'Em risco';
    $engajamentoClass = 'text-bg-warning text-dark';
} elseif ($engajamentoStatus === 'inativo') {
    $engajamentoLabel = 'Inativo';
    $engajamentoClass = 'text-bg-danger';
}
?>

<div class="page-header d-flex justify-content-between align-itemês-center mb-4">
    <div class="d-flex align-itemês-center gap-3">
        <h2 class="mb-0">
            <i class="bi bi-persãon-lines-fill me-2"></i><?= $isEdit ? 'Editar Contato' : 'Nãovo Contato' ?>
        </h2>
        <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('contatos')" title="Ajuda sãobre Contatos">
            <i class="bi bi-question-circle me-1"></i>Ajuda
        </button>
    </div>
    <a href="<?= base_url('contatos') ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Voltar
    </a>
</div>

<div class="card glass-card">
    <div class="card-body">
        <form method="post" action="<?= $isEdit ? base_url('contatos/atualizar/' . (int) $contato['id']) : base_url('contatos/salvar') ?>">
            <?= csrf_field() ?>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nãome</label>
                    <input
                        type="text"
                        name="nãome"
                        class="form-control"
                        maxlength="150"
                        value="<?= esc(old('nãome', (string) ($contato['nãome'] ?? ''))) ?>"
                        placeholder="Nãome completo do contato"
                    >
                </div>

                <div class="col-md-6">
                    <label class="form-label">Telefone *</label>
                    <input
                        type="text"
                        name="telefone"
                        class="form-control"
                        required
                        maxlength="30"
                        value="<?= esc(old('telefone', (string) ($contato['telefone'] ?? ''))) ?>"
                        placeholder="Ex.: 5522999999999"
                    >
                </div>

                <div class="col-md-6">
                    <label class="form-label">E-mail</label>
                    <input
                        type="email"
                        name="email"
                        class="form-control"
                        maxlength="120"
                        value="<?= esc(old('email', (string) ($contato['email'] ?? ''))) ?>"
                        placeholder="nãome@dominio.com"
                    >
                </div>

                <div class="col-md-6">
                    <label class="form-label">Origem</label>
                    <?php $origem = old('origem', (string) ($contato['origem'] ?? 'manual')); ?>
                    <select name="origem" class="form-select">
                        <option value="manual" <?= $origem === 'manual' ? 'selected' : '' ?>>Manual</option>
                        <option value="whatsapp" <?= $origem === 'whatsapp' ? 'selected' : '' ?>>WhatsApp</option>
                        <option value="importacao" <?= $origem === 'importacao' ? 'selected' : '' ?>>Importacao</option>
                        <option value="site" <?= $origem === 'site' ? 'selected' : '' ?>>Site</option>
                        <option value="indicacao" <?= $origem === 'indicacao' ? 'selected' : '' ?>>Indicacao</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Nãome de perfil não WhatsApp</label>
                    <input
                        type="text"
                        name="whatsapp_nãome_perfil"
                        class="form-control"
                        maxlength="140"
                        value="<?= esc(old('whatsapp_nãome_perfil', (string) ($contato['whatsapp_nãome_perfil'] ?? ''))) ?>"
                        placeholder="Nãome exibido não perfil do WhatsApp"
                    >
                </div>

                <div class="col-md-6">
                    <label class="form-label">Ultimo contato</label>
                    <input
                        type="datetime-local"
                        name="ultimo_contato_em"
                        class="form-control"
                        value="<?= esc(old('ultimo_contato_em', !empty($contato['ultimo_contato_em']) ? date('Y-m-d\TH:i', strtotime((string) $contato['ultimo_contato_em'])) : '')) ?>"
                    >
                </div>

                <div class="col-12">
                    <label class="form-label">Observacoes</label>
                    <textarea name="observacoes" class="form-control" rows="4" placeholder="Informacoes adicionais de relacionamento"><?= esc(old('observacoes', (string) ($contato['observacoes'] ?? ''))) ?></textarea>
                </div>
            </div>

            <?php if ($isEdit && (int) ($contato['cliente_id'] ?? 0) > 0): ?>
                <div class="alert alert-info mt-3 mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    Este contato ja esta vinculado ao cliente #<?= (int) $contato['cliente_id'] ?>.
                </div>
            <?php endif; ?>
            <?php if ($isEdit && !empty($supportsLifecycle)): ?>
                <div class="alert alert-light border mt-3 mb-0">
                    <div class="d-flex flex-wrap gap-2 align-itemês-center">
                        <span class="small text-muted">Etapa atual:</span>
                        <span class="badge <?= esc($statusClass) ?>"><?= esc($statusLabel) ?></span>
                        <?php if (!empty($contato['qualificado_em'])): ?>
                            <span class="small text-muted">Qualificado em: <?= esc(date('d/m/Y H:i', strtotime((string) $contato['qualificado_em']))) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($contato['convertido_em'])): ?>
                            <span class="small text-muted">Convertido em: <?= esc(date('d/m/Y H:i', strtotime((string) $contato['convertido_em']))) ?></span>
                        <?php endif; ?>
                        <?php if (isset($contato['engajamento_status'])): ?>
                            <span class="badge <?= esc($engajamentoClass) ?>"><?= esc($engajamentoLabel) ?></span>
                            <?php if (!empty($contato['engajamento_recalculado_em'])): ?>
                                <span class="small text-muted">Engajamento recalculado em: <?= esc(date('d/m/Y H:i', strtotime((string) $contato['engajamento_recalculado_em']))) ?></span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-glow">
                    <i class="bi bi-check-lg me-1"></i><?= $isEdit ? 'Salvar alteracoes' : 'Cadastrar contato' ?>
                </button>
                <a href="<?= base_url('contatos') ?>" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
