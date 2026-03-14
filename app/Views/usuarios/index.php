<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0 text-gray-800">Gerenciar Usuários</h1>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-info rounded-pill" onclick="window.openDocPage('usuarios')" title="Ajuda sobre UsuÃ¡rios e PermissÃµes">
                    <i class="bi bi-question-circle me-1"></i>Ajuda
                </button>
            <?php if (can('usuarios', 'criar')): ?>
            <a href="<?= base_url('usuarios/novo') ?>" class="btn btn-primary btn-glow">
                <i class="bi bi-person-plus me-2"></i>Novo Usuário
            </a>
            <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card glass-card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="usuariosTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Perfil</th>
                        <th>Status</th>
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
    $('#usuariosTable').DataTable({
        language: {
            url: '<?= base_url("assets/json/pt-BR.json") ?>'
        },
        processing: true,
        serverSide: true,
        ajax: {
            url: '<?= base_url("usuarios/datatable") ?>',
            type: 'POST',
            data: function (d) {
                d.<?= csrf_token() ?> = '<?= csrf_hash() ?>'; // CSRF token if enabled
            }
        },
        order: [[1, 'asc']], // Order by nome by default
    });

    $(document).on('click', '.btn-delete', function() {
        const url = $(this).data('url');
        if (confirm('Tem certeza que deseja excluir este usuário? Esta ação não pode ser desfeita.')) {
            window.location.href = url;
        }
    });
});
</script>
<?= $this->endSection() ?>
