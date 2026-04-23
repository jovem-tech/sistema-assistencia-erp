<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
$servicosMap = is_array($servicosMap ?? null) ? $servicosMap : [];
?>

<div class="page-header d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="bi bi-box-seam me-2"></i>Pacotes de Servicos</h2>
    <div class="d-flex gap-2">
        <button
            type="button"
            class="btn btn-sm btn-outline-info rounded-pill"
            onclick="window.openDocPage('pacotes-servicos')"
            title="Ajuda sobre Pacotes de Servicos"
        >
            <i class="bi bi-question-circle me-1"></i>Ajuda
        </button>
        <?php if (can('orcamentos', 'criar')): ?>
        <a href="<?= base_url('pacotes-servicos/novo') ?>" class="btn btn-primary btn-glow">
            <i class="bi bi-plus-lg me-1"></i>Novo Pacote
        </a>
        <?php endif; ?>
    </div>
</div>

<div class="card glass-card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover datatable" id="tabelaPacotesServicos">
                <thead>
                    <tr>
                        <th width="60">ID</th>
                        <th>Pacote</th>
                        <th>Categoria</th>
                        <th>Tipo Equip.</th>
                        <th>Servico Ref.</th>
                        <th>Niveis</th>
                        <th>Status</th>
                        <th width="210" class="text-center">Acoes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ((array) ($pacotes ?? []) as $pacote): ?>
                    <?php
                    $pacoteId = (int) ($pacote['id'] ?? 0);
                    $niveis = (array) ($pacote['niveis'] ?? []);
                    $servicoRefId = (int) ($pacote['servico_referencia_id'] ?? 0);
                    ?>
                    <tr>
                        <td data-label="ID"><?= $pacoteId ?></td>
                        <td data-label="Pacote">
                            <strong><?= esc((string) ($pacote['nome'] ?? '')) ?></strong>
                            <?php if (!empty($pacote['descricao'])): ?>
                            <div class="text-muted small mt-1"><?= esc((string) $pacote['descricao']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td data-label="Categoria"><?= esc(ucfirst((string) ($pacote['categoria'] ?? 'geral'))) ?></td>
                        <td data-label="Tipo Equip."><?= esc((string) ($pacote['tipo_equipamento'] ?? 'Diverso')) ?></td>
                        <td data-label="Servico Ref.">
                            <?php if ($servicoRefId > 0): ?>
                                <?= esc((string) ($servicosMap[$servicoRefId] ?? ('Servico #' . $servicoRefId))) ?>
                            <?php else: ?>
                                <span class="text-muted">Nao vinculado</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="Niveis">
                            <div class="d-flex flex-wrap gap-1">
                                <?php foreach (['basico' => 'Basico', 'completo' => 'Completo', 'premium' => 'Premium'] as $code => $label): ?>
                                    <?php $nivel = $niveis[$code] ?? null; ?>
                                    <?php if (is_array($nivel)): ?>
                                        <span class="badge bg-light text-dark border">
                                            <?= esc($label) ?>:
                                            <?= formatMoney((float) ($nivel['preco_recomendado'] ?? 0)) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-muted border"><?= esc($label) ?>: -</span>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </td>
                        <td data-label="Status">
                            <?php $ativo = ((int) ($pacote['ativo'] ?? 0)) === 1; ?>
                            <span class="badge <?= $ativo ? 'bg-success' : 'bg-secondary' ?>">
                                <?= $ativo ? 'Ativo' : 'Inativo' ?>
                            </span>
                        </td>
                        <td data-label="Acoes" class="text-center">
                            <div class="action-btns">
                                <?php if (can('orcamentos', 'visualizar')): ?>
                                <a
                                    href="<?= base_url('pacotes-servicos/preview/' . $pacoteId) ?>"
                                    class="btn btn-sm btn-outline-primary"
                                    title="Visualizar template do cliente"
                                    target="_blank"
                                    rel="noopener"
                                >
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php endif; ?>
                                <?php if (can('orcamentos', 'editar')): ?>
                                <a href="<?= base_url('pacotes-servicos/editar/' . $pacoteId) ?>" class="btn btn-sm btn-outline-secondary" title="Editar pacote">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php endif; ?>
                                <?php if (can('orcamentos', 'excluir')): ?>
                                <a
                                    href="<?= base_url('pacotes-servicos/excluir/' . $pacoteId) ?>"
                                    class="btn btn-sm btn-outline-danger btn-delete"
                                    data-nome="<?= esc((string) ($pacote['nome'] ?? '')) ?>"
                                    title="Excluir pacote"
                                >
                                    <i class="bi bi-trash"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
