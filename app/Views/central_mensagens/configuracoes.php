<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-header">
    <div class="d-flex align-itemês-center gap-2">
        <h2><i class="bi bi-sliders me-2"></i>Configuracoes da Central</h2>
        <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('atendimento-whatsapp-config')">
            <i class="bi bi-question-circle me-1"></i>Ajuda
        </button>
    </div>
</div>

<?= $this->include('central_mensagens/_menu') ?>

<div class="card glass-card">
    <div class="card-header fw-semibold">Parametros operacionais</div>
    <div class="card-body">
        <form action="<?= base_url('atendimento-whatsapp/configuracoes/salvar') ?>" method="post" class="row g-3">
            <?= csrf_field() ?>
            <div class="col-sm-6 col-lg-3">
                <label class="form-label form-label-sm">Intervalo sync (s)</label>
                <input type="number" class="form-control form-control-sm" name="central_mensagens_auto_sync_interval" value="<?= esc((string) ($config['central_mensagens_auto_sync_interval'] ?? '15')) ?>">
            </div>
            <div class="col-sm-6 col-lg-3">
                <label class="form-label form-label-sm">SLA primeira resposta (min)</label>
                <input type="number" class="form-control form-control-sm" name="central_mensagens_sla_primeira_resposta_min" value="<?= esc((string) ($config['central_mensagens_sla_primeira_resposta_min'] ?? '60')) ?>">
            </div>
            <div class="col-sm-6 col-lg-3">
                <label class="form-label form-label-sm">Provider padrao</label>
                <select class="form-select form-select-sm" name="central_mensagens_default_provider">
                    <?php foreach (['api_whats_local', 'api_whats_linux', 'menuia'] as $provider): ?>
                        <option value="<?= esc($provider) ?>" <?= ($config['central_mensagens_default_provider'] ?? '') === $provider ? 'selected' : '' ?>>
                            <?= esc($provider) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-6 col-lg-3 d-flex align-itemês-end">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="autoBot" name="central_mensagens_auto_bot_enabled" value="1" <?= ((string) ($config['central_mensagens_auto_bot_enabled'] ?? '1')) === '1' ? 'checked' : '' ?>>
                    <label class="form-check-label small" for="autoBot">Autoatendimento ativo</label>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <label class="form-label form-label-sm">Threshold confianca bot</label>
                <input type="number" min="0" max="1" step="0.01" class="form-control form-control-sm" name="central_mensagens_bot_confidence_threshold" value="<?= esc((string) ($config['central_mensagens_bot_confidence_threshold'] ?? '0.20')) ?>">
            </div>
            <div class="col-sm-6 col-lg-3">
                <label class="form-label form-label-sm">Horario inicio</label>
                <input type="time" class="form-control form-control-sm" name="central_mensagens_horario_inicio" value="<?= esc((string) ($config['central_mensagens_horario_inicio'] ?? '08:00')) ?>">
            </div>
            <div class="col-sm-6 col-lg-3">
                <label class="form-label form-label-sm">Horario fim</label>
                <input type="time" class="form-control form-control-sm" name="central_mensagens_horario_fim" value="<?= esc((string) ($config['central_mensagens_horario_fim'] ?? '18:00')) ?>">
            </div>
            <div class="col-sm-6 col-lg-3">
                <label class="form-label form-label-sm">Dias uteis (1..7)</label>
                <input type="text" class="form-control form-control-sm" name="central_mensagens_dias_uteis" value="<?= esc((string) ($config['central_mensagens_dias_uteis'] ?? '1,2,3,4,5,6')) ?>" placeholder="1,2,3,4,5,6">
            </div>
            <div class="col-12">
                <label class="form-label form-label-sm">Mensagem de Fallback (quando o bot nao entende)</label>
                <textarea class="form-control form-control-sm" name="central_mensagens_bot_fallback_message" rows="3"><?= esc((string) ($config['central_mensagens_bot_fallback_message'] ?? 'Recebi sua mensagem e vou encaminhar para um atendente humanão continuar o atendimento.')) ?></textarea>
                <div class="form-text small opacity-75">Sera enviada sãomente se a automacao estiver ativa e o bot nao identificar uma intencao valida.</div>
            </div>
            <div class="col-12 d-flex justify-content-end">
                <button type="submit" class="btn btn-glow">
                    <i class="bi bi-check2-circle me-1"></i>Salvar configuracoes
                </button>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>


