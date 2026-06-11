<?php
$historicoLifecycle = $historicoLifecycle ?? [];
$historicoLifecycleCount = (int) ($historicoLifecycleCount ?? count($historicoLifecycle));
?>

<?php if (empty($historicoLifecycle)): ?>
    <div class="text-center p-4 text-body-secondary">
        <i class="bi bi-clock-history fs-1 mb-2"></i>
        <p class="mb-0">Nenhum evento de encerramento ou volta a operacao foi registrado para este equipamento.</p>
    </div>
<?php else: ?>
    <div class="list-group list-group-flush">
        <?php foreach ($historicoLifecycle as $evento): ?>
            <div class="list-group-item bg-transparent px-0 py-3">
                <div class="d-flex gap-3">
                    <div class="flex-shrink-0">
                        <span class="badge rounded-pill <?= esc($evento['evento_badge_class'] ?? 'text-bg-secondary') ?>">
                            <?= esc($evento['evento_label'] ?? 'Evento') ?>
                        </span>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-semibold text-body mb-1">
                            <?= esc($evento['titulo'] ?? 'Movimentacao de ciclo de vida') ?>
                        </div>
                        <div class="small text-body-secondary mb-1">
                            <?= esc($evento['data_label'] ?? '') ?>
                            <?php if (! empty($evento['usuario_nome'])): ?>
                                <span class="mx-1">•</span><?= esc($evento['usuario_nome']) ?>
                            <?php endif; ?>
                        </div>
                        <?php if (! empty($evento['status_transicao_label'])): ?>
                            <div class="small text-body-secondary mb-1">
                                <?= esc($evento['status_transicao_label']) ?>
                            </div>
                        <?php endif; ?>
                        <?php if (! empty($evento['motivo_label'])): ?>
                            <div class="small text-body-secondary mb-1">
                                <span class="fw-semibold">Motivo:</span> <?= esc($evento['motivo_label']) ?>
                            </div>
                        <?php endif; ?>
                        <?php if (! empty($evento['os_label'])): ?>
                            <div class="small text-body-secondary mb-1">
                                <span class="fw-semibold">Referencia:</span> <?= esc($evento['os_label']) ?>
                            </div>
                        <?php endif; ?>
                        <?php if (! empty($evento['observacao'])): ?>
                            <div class="small text-body-secondary">
                                <?= nl2br(esc($evento['observacao'])) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
