<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header">
    <div class="d-flex align-items-center gap-3">
        <h2><i class="bi bi-clipboard-check me-2"></i>Ordens de Serviço</h2>
        <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('06-modulos-do-sistema/ordens-de-servico.md')" title="Ajuda sobre este módulo">
            <i class="bi bi-question-circle me-1"></i> Ajuda
        </button>
    </div>
    <?php if (can('os', 'criar')): ?>
    <a href="<?= base_url('os/nova') ?>" class="btn btn-glow">
        <i class="bi bi-plus-lg me-1"></i>Nova OS
    </a>
    <?php endif; ?>
</div>

<!-- Status Filters -->
<div class="mb-4">
    <div class="d-flex flex-wrap gap-2">
        <a href="<?= base_url('os') ?>" class="btn btn-sm <?= empty($filtro_status) || $filtro_status === 'todos' ? 'btn-glow' : 'btn-outline-secondary' ?>">Todas</a>
        <a href="<?= base_url('os?status=aguardando_analise') ?>" class="btn btn-sm <?= $filtro_status === 'aguardando_analise' ? 'btn-warning' : 'btn-outline-secondary' ?>">Aguard. Análise</a>
        <a href="<?= base_url('os?status=em_reparo') ?>" class="btn btn-sm <?= $filtro_status === 'em_reparo' ? 'btn-primary' : 'btn-outline-secondary' ?>">Em Reparo</a>
        <a href="<?= base_url('os?status=aguardando_aprovacao') ?>" class="btn btn-sm <?= $filtro_status === 'aguardando_aprovacao' ? 'btn-info' : 'btn-outline-secondary' ?>">Aguard. Aprovação</a>
        <a href="<?= base_url('os?status=pronto') ?>" class="btn btn-sm <?= $filtro_status === 'pronto' ? 'btn-success' : 'btn-outline-secondary' ?>">Prontas</a>
        <a href="<?= base_url('os?status=entregue') ?>" class="btn btn-sm <?= $filtro_status === 'entregue' ? 'btn-secondary' : 'btn-outline-secondary' ?>">Entregues</a>
    </div>
</div>

<div class="card glass-card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="osTable">
                <thead>
                    <tr>
                        <th>Nº OS</th>
                        <th>Cliente</th>
                        <th>Equipamento</th>
                        <th>Defeito</th>
                        <th>Data Abertura</th>
                        <th>Status</th>
                        <th>Valor Total</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Server-side processing -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(document).ready(function() {
    $('#osTable').DataTable({
        language: {
            url: '<?= base_url("assets/json/pt-BR.json") ?>'
        },
        processing: true,
        serverSide: true,
        ajax: {
            url: '<?= base_url("os/datatable") ?>',
            type: 'POST',
            data: function (d) {
                d.<?= csrf_token() ?> = '<?= csrf_hash() ?>'; // CSRF token if enabled
                d.status = '<?= $filtro_status ?? '' ?>'; // Pass the active status filter
            }
        },
        order: [[4, 'desc']], // Sort by date desc initially
    });
});
</script>
<?= $this->endSection() ?>

