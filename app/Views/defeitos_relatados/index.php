<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$categoriaSelecionada = $categoriaSelecionada ?? '';
$categorias = $categorias ?? [];
$categoriaLabelMap = [
    'Audio' => '&Aacute;udio',
    'audio' => '&Aacute;udio',
    'Camera' => 'C&acirc;mera',
    'camera' => 'C&acirc;mera',
];
$formatCategoria = static function (string $value) use ($categoriaLabelMap): string {
    $v = trim($value);
    if (isset($categoriaLabelMap[$v])) {
        return $categoriaLabelMap[$v];
    }
    return esc($v);
};
?>

<div class="page-header d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="bi bi-chat-square-text me-2"></i>Defeitos Relatados</h2>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('defeitos-relatados')" title="Ajuda sobre relatos do cliente">
            <i class="bi bi-question-circle me-1"></i>Ajuda
        </button>
        <?php if (can('defeitos', 'criar')): ?>
        <a href="<?= base_url('defeitosrelatados/novo') ?>" class="btn btn-primary btn-glow">
            <i class="bi bi-plus-lg me-1"></i>Novo Relato
        </a>
        <?php endif; ?>
    </div>
</div>

<div class="card glass-card">
    <div class="card-body">
        <form method="get" action="<?= base_url('defeitosrelatados') ?>" class="row g-2 align-items-end mb-3">
            <div class="col-md-4 col-lg-3">
                <label for="filtroCategoria" class="form-label mb-1">Filtrar por categoria</label>
                <select class="form-select form-select-sm" id="filtroCategoria" name="categoria">
                    <option value="">Todas</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?= esc($cat) ?>" <?= $categoriaSelecionada === $cat ? 'selected' : '' ?>>
                            <?= $formatCategoria($cat) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-primary">Filtrar</button>
            </div>
            <?php if ($categoriaSelecionada !== ''): ?>
            <div class="col-auto">
                <a href="<?= base_url('defeitosrelatados') ?>" class="btn btn-sm btn-outline-secondary">Limpar</a>
            </div>
            <?php endif; ?>
        </form>

        <?php if (empty($relatos)): ?>
            <div class="alert alert-info mb-0">
                Nenhum defeito relatado cadastrado para o filtro selecionado.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Categoria</th>
                            <th>Relato</th>
                            <th class="text-center">&Iacute;cone</th>
                            <th class="text-center">Ordem</th>
                            <th class="text-center">Status</th>
                            <th class="text-end">A&ccedil;&otilde;es</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($relatos as $relato): ?>
                        <tr>
                            <td><span class="badge text-bg-secondary"><?= $formatCategoria((string)$relato['categoria']) ?></span></td>
                            <td><?= esc($relato['texto_relato']) ?></td>
                            <td class="text-center"><?= esc($relato['icone'] ?: '📝') ?></td>
                            <td class="text-center"><?= (int)($relato['ordem_exibicao'] ?? 0) ?></td>
                            <td class="text-center">
                                <?php if (!empty($relato['ativo'])): ?>
                                    <span class="badge text-bg-success">Ativo</span>
                                <?php else: ?>
                                    <span class="badge text-bg-secondary">Inativo</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <?php if (can('defeitos', 'editar')): ?>
                                    <a href="<?= base_url('defeitosrelatados/editar/' . $relato['id']) ?>" class="btn btn-outline-primary" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="<?= base_url('defeitosrelatados/status/' . $relato['id']) ?>" method="post" class="d-inline">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-outline-warning" title="<?= !empty($relato['ativo']) ? 'Desativar' : 'Ativar' ?>">
                                            <i class="bi <?= !empty($relato['ativo']) ? 'bi-pause-circle' : 'bi-play-circle' ?>"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                    <?php if (can('defeitos', 'excluir')): ?>
                                    <a href="<?= base_url('defeitosrelatados/excluir/' . $relato['id']) ?>"
                                       class="btn btn-outline-danger"
                                       title="Excluir"
                                       onclick="return confirm('Confirma excluir este relato?');">
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
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>