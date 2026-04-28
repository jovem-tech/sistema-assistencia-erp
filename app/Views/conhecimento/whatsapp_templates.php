<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$templates = is_array($templates ?? null) ? $templates : [];
$editItem = is_array($editItem ?? null) ? $editItem : null;
$placeholders = is_array($placeholders ?? null) ? $placeholders : [];
?>
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h2 class="mb-0"><i class="bi bi-chat-square-dots me-2"></i>Templates de WhatsApp</h2>
    <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('templates-whatsapp-os')">
        <i class="bi bi-question-circle me-1"></i>Ajuda
    </button>
</div>

<div class="row g-4">
    <div class="col-12 col-xl-7">
        <div class="card glass-card h-100">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0">Templates cadastrados</h5>
                <span class="badge bg-light text-dark border"><?= esc((string) count($templates)) ?></span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Código</th>
                                <th>Evento</th>
                                <th>Status</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($templates)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">Nenhum template de WhatsApp cadastrado.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($templates as $template): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold"><?= esc((string) ($template['nome'] ?? 'Template')) ?></div>
                                            <div class="small text-muted text-truncate" style="max-width: 320px;"><?= esc((string) ($template['conteudo'] ?? '')) ?></div>
                                        </td>
                                        <td><code><?= esc((string) ($template['codigo'] ?? '')) ?></code></td>
                                        <td><?= esc((string) ($template['evento'] ?? '-')) ?></td>
                                        <td>
                                            <span class="badge <?= !empty($template['ativo']) ? 'bg-success' : 'bg-secondary' ?>">
                                                <?= !empty($template['ativo']) ? 'Ativo' : 'Inativo' ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-2">
                                                <a href="<?= base_url('conhecimento/templates-whatsapp?edit=' . (int) ($template['id'] ?? 0)) ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-pencil-square"></i>
                                                </a>
                                                <form action="<?= base_url('conhecimento/templates-whatsapp/toggle/' . (int) ($template['id'] ?? 0)) ?>" method="POST" class="d-inline">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" class="btn btn-sm btn-outline-secondary">
                                                        <i class="bi <?= !empty($template['ativo']) ? 'bi-toggle-on' : 'bi-toggle-off' ?>"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-5">
        <div class="card glass-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><?= $editItem ? 'Editar template' : 'Novo template' ?></h5>
                <?php if ($editItem): ?>
                    <a href="<?= base_url('conhecimento/templates-whatsapp') ?>" class="btn btn-sm btn-outline-secondary">Limpar</a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <form action="<?= base_url('conhecimento/templates-whatsapp/salvar') ?>" method="POST" class="d-flex flex-column gap-3">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= esc((string) ($editItem['id'] ?? '')) ?>">

                    <div>
                        <label class="form-label">Código *</label>
                        <input type="text" name="codigo" class="form-control" value="<?= esc((string) ($editItem['codigo'] ?? '')) ?>" placeholder="ex.: garantia_liberada" required>
                    </div>

                    <div>
                        <label class="form-label">Nome *</label>
                        <input type="text" name="nome" class="form-control" value="<?= esc((string) ($editItem['nome'] ?? '')) ?>" placeholder="Ex.: Garantia liberada" required>
                    </div>

                    <div>
                        <label class="form-label">Evento</label>
                        <input type="text" name="evento" class="form-control" value="<?= esc((string) ($editItem['evento'] ?? '')) ?>" placeholder="Ex.: status_reparo_concluido">
                    </div>

                    <div>
                        <label class="form-label">Mensagem *</label>
                        <textarea name="conteudo" class="form-control" rows="10" required><?= esc((string) ($editItem['conteudo'] ?? '')) ?></textarea>
                        <div class="form-text">Os placeholders abaixo podem ser usados livremente na composição da mensagem.</div>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="1" id="whatsappTemplateAtivo" name="ativo" <?= !isset($editItem['ativo']) || !empty($editItem['ativo']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="whatsappTemplateAtivo">Template ativo</label>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i><?= $editItem ? 'Atualizar template' : 'Criar template' ?>
                    </button>
                </form>
            </div>
        </div>

        <div class="card glass-card mt-4">
            <div class="card-header">
                <h6 class="mb-0">Placeholders disponíveis</h6>
            </div>
            <div class="card-body">
                <div class="d-flex flex-column gap-2 small">
                    <?php foreach ($placeholders as $placeholder): ?>
                        <div class="border rounded-3 p-2">
                            <code><?= esc((string) ($placeholder['token'] ?? '')) ?></code>
                            <div class="text-muted mt-1"><?= esc((string) ($placeholder['descricao'] ?? '')) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
