<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-header">
    <div class="d-flex align-items-center gap-2">
        <h2><i class="bi bi-diagram-3 me-2"></i>Fluxo de Trabalho da OS</h2>
        <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('os-workflow')">
            <i class="bi bi-question-circle me-1"></i>Ajuda
        </button>
    </div>
</div>

<div class="row g-3">
    <div class="col-12">
        <div class="card glass-card">
            <div class="card-body">
                <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-3">
                    <div>
                        <h5 class="mb-1">Configuracao do fluxo operacional</h5>
                        <p class="text-muted mb-0">
                            Defina a ordem visual dos status e quais transicoes cada etapa pode executar para frente ou para retorno.
                        </p>
                    </div>
                    <div class="d-flex flex-wrap gap-2 align-items-start">
                        <span class="badge <?= !empty($hasConfiguredTransitions) ? 'bg-success' : 'bg-warning text-dark' ?>">
                            <?= !empty($hasConfiguredTransitions) ? 'Transicoes personalizadas ativas' : 'Usando fallback automatico por ordem' ?>
                        </span>
                    </div>
                </div>

                <?php if (empty($statuses)): ?>
                    <div class="alert alert-warning mb-0">
                        Nenhum status de OS foi encontrado. Verifique se as migrations da base pre-CRM foram executadas.
                    </div>
                <?php else: ?>
                    <form action="<?= base_url('osworkflow/salvar') ?>" method="post">
                        <?= csrf_field() ?>

                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>Status</th>
                                        <th>Macrofase</th>
                                        <th style="width: 120px;">Ordem</th>
                                        <th class="text-center" style="width: 110px;">Ativo</th>
                                        <th class="text-center" style="width: 120px;">Final</th>
                                        <th class="text-center" style="width: 120px;">Pausa</th>
                                        <th style="min-width: 280px;">Pode ir para</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($statuses as $status): ?>
                                        <?php
                                        $statusId = (int) ($status['id'] ?? 0);
                                        $selectedDestinations = $transitionIdMap[$statusId] ?? [];
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="fw-semibold"><?= esc((string) ($status['nome'] ?? '-')) ?></div>
                                                <div class="small text-muted"><?= esc((string) ($status['codigo'] ?? '-')) ?></div>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border">
                                                    <?= esc(ucwords(str_replace('_', ' ', (string) ($status['grupo_macro'] ?? 'outros')))) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <input
                                                    type="number"
                                                    name="status[<?= $statusId ?>][ordem_fluxo]"
                                                    class="form-control"
                                                    value="<?= esc((string) ($status['ordem_fluxo'] ?? 0)) ?>"
                                                    min="0"
                                                    step="1"
                                                >
                                            </td>
                                            <td class="text-center">
                                                <div class="form-check d-inline-flex justify-content-center">
                                                    <input
                                                        class="form-check-input"
                                                        type="checkbox"
                                                        name="status[<?= $statusId ?>][ativo]"
                                                        value="1"
                                                        <?= ((int) ($status['ativo'] ?? 1) === 1) ? 'checked' : '' ?>
                                                    >
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="form-check d-inline-flex justify-content-center">
                                                    <input
                                                        class="form-check-input"
                                                        type="checkbox"
                                                        name="status[<?= $statusId ?>][status_final]"
                                                        value="1"
                                                        <?= ((int) ($status['status_final'] ?? 0) === 1) ? 'checked' : '' ?>
                                                    >
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="form-check d-inline-flex justify-content-center">
                                                    <input
                                                        class="form-check-input"
                                                        type="checkbox"
                                                        name="status[<?= $statusId ?>][status_pausa]"
                                                        value="1"
                                                        <?= ((int) ($status['status_pausa'] ?? 0) === 1) ? 'checked' : '' ?>
                                                    >
                                                </div>
                                            </td>
                                            <td>
                                                <select
                                                    name="transitions[<?= $statusId ?>][]"
                                                    class="form-select js-os-workflow-select"
                                                    multiple
                                                    data-placeholder="Selecione os destinos permitidos"
                                                >
                                                    <?php foreach ($statuses as $destination): ?>
                                                        <?php $destinationId = (int) ($destination['id'] ?? 0); ?>
                                                        <?php if ($destinationId <= 0 || $destinationId === $statusId) continue; ?>
                                                        <option value="<?= $destinationId ?>" <?= in_array($destinationId, $selectedDestinations, true) ? 'selected' : '' ?>>
                                                            <?= esc((string) ($destination['nome'] ?? ('Status #' . $destinationId))) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <div class="form-text">
                                                    Se nenhuma transicao estiver configurada no sistema, o fallback usa o status anterior e o proximo pela ordem.
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-3">
                            <a href="<?= base_url('os') ?>" class="btn btn-outline-secondary">Voltar para OS</a>
                            <button type="submit" class="btn btn-glow">
                                <i class="bi bi-check2-circle me-1"></i>Salvar workflow
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.select2) {
        return;
    }

    window.jQuery('.js-os-workflow-select').each(function initWorkflowSelect() {
        const $element = window.jQuery(this);
        const placeholder = this.getAttribute('data-placeholder') || 'Selecionar destinos';

        if ($element.hasClass('select2-hidden-accessible')) {
            $element.select2('destroy');
        }

        $element.select2({
            theme: 'bootstrap-5',
            width: '100%',
            closeOnSelect: false,
            placeholder: placeholder,
        });
    });
});
</script>
<?= $this->endSection() ?>
