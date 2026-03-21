<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header">
    <h2><i class="bi bi-tools me-2"></i><?= esc($title) ?></h2>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('relatorios')" title="Ajuda sobre RelatÃ³rios">
            <i class="bi bi-question-circle me-1"></i>Ajuda
        </button>
        <a href="<?= base_url('relatorios') ?>" class="btn btn-outline-secondary" data-back-default="<?= base_url('relatorios') ?>">
            <i class="bi bi-arrow-left me-1"></i>Voltar
        </a>
    </div>
</div>

<div class="card glass-card mb-4">
    <div class="card-body">
        <form method="GET" action="<?= base_url('relatorios/osByPeriod') ?>" class="row g-3">
            <div class="col-md-3">
                <label for="data_inicial" class="form-label">Data Inicial</label>
                <input type="date" class="form-control" id="data_inicial" name="data_inicial" value="<?= esc($filtro_data_inicial) ?>">
            </div>
            <div class="col-md-3">
                <label for="data_final" class="form-label">Data Final</label>
                <input type="date" class="form-control" id="data_final" name="data_final" value="<?= esc($filtro_data_final) ?>">
            </div>
            <div class="col-md-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="todos" <?= $filtro_status === 'todos' ? 'selected' : '' ?>>Todos</option>
                    <option value="aberto" <?= $filtro_status === 'aberto' ? 'selected' : '' ?>>Aberto</option>
                    <option value="aguardando_analise" <?= $filtro_status === 'aguardando_analise' ? 'selected' : '' ?>>Aguardando Análise</option>
                    <option value="em_reparo" <?= $filtro_status === 'em_reparo' ? 'selected' : '' ?>>Em Reparo</option>
                    <option value="aguardando_aprovacao" <?= $filtro_status === 'aguardando_aprovacao' ? 'selected' : '' ?>>Aguardando Aprovação</option>
                    <option value="aguardando_peca" <?= $filtro_status === 'aguardando_peca' ? 'selected' : '' ?>>Aguardando Peça</option>
                    <option value="pronto" <?= $filtro_status === 'pronto' ? 'selected' : '' ?>>Pronto / Aguardando Retirada</option>
                    <option value="entregue" <?= $filtro_status === 'entregue' ? 'selected' : '' ?>>Entregue</option>
                    <option value="cancelado" <?= $filtro_status === 'cancelado' ? 'selected' : '' ?>>Cancelado / Sem Conserto</option>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-filter me-1"></i>Filtrar
                </button>
                <a href="<?= base_url('relatorios/osByPeriod?data_inicial='.$filtro_data_inicial.'&data_final='.$filtro_data_final.'&status='.$filtro_status.'&print=1') ?>" target="_blank" class="btn btn-success w-100">
                    <i class="bi bi-printer me-1"></i>Imprimir
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card glass-card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover datatable">
                <thead>
                    <tr>
                        <th>Nº OS</th>
                        <th>Cliente</th>
                        <th>Equipamento</th>
                        <th>Status</th>
                        <th>Entrada</th>
                        <th>Saída</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($ordens)): ?>
                        <?php foreach ($ordens as $os): ?>
                        <tr>
                            <td><?= $os['id'] ?></td>
                            <td><?= esc($os['cliente_nome']) ?></td>
                            <td><?= esc($os['equip_marca'] . ' ' . $os['equip_modelo']) ?></td>
                            <td>
                                <span class="badge bg-secondary"><?= esc(ucwords(str_replace('_', ' ', $os['status']))) ?></span>
                            </td>
                            <td><?= date('d/m/Y', strtotime($os['created_at'])) ?></td>
                            <td><?= !empty($os['data_saida']) ? date('d/m/Y', strtotime($os['data_saida'])) : '-' ?></td>
                            <td>R$ <?= number_format($os['valor_total'] ?? 0, 2, ',', '.') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">Nenhuma ordem de serviço encontrada com os filtros selecionados.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
