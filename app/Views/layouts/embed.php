<!DOCTYPE html>
<html lang="pt-BR" data-theme="<?= get_theme() ?>" data-bs-theme="<?= get_theme() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="base-url" content="<?= base_url() ?>">
    <?php helper('cookie'); ?>
    <meta name="session-timeout-minutes" content="<?= esc((string) get_session_inactivity_minutes(30)) ?>">
    <meta name="session-heartbeat-url" content="<?= base_url('sessao/heartbeat') ?>">
    <meta name="session-login-url" content="<?= base_url('login') ?>">
    <meta name="session-remember-active" content="<?= get_cookie('remember_login') ? '1' : '0' ?>">
    <title><?= $title ?? 'Sistema' ?> - <?= esc(get_config('sistema_nome', 'Assistencia Tecnica')) ?></title>

    <link rel="icon" href="<?= base_url('favicon.ico') ?>">
    <link href="<?= base_url('assets/vendor/bootstrap/css/bootstrap.min.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/vendor/bootstrap-icons/css/bootstrap-icons.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/vendor/datatables/css/dataTables.bootstrap5.min.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/vendor/select2/css/select2.min.css') ?>" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/vendor/select2-bootstrap-5-theme/css/select2-bootstrap-5-theme.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/vendor/sweetalert2/sweetalert2.min.css') ?>">
    <link href="<?= base_url('assets/css/estilo.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/css/design-system/index.css') ?>" rel="stylesheet">

    <style>
        body.ds-embed-shell {
            margin: 0;
            background: var(--bg-primary);
        }

        .embed-content {
            padding: 16px;
        }
    </style>
</head>
<body class="ds-embed-shell">
    <div class="embed-content">
        <?php if (session()->getFlashdata('success')): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i><?= session()->getFlashdata('success') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= session()->getFlashdata('error') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?= $this->renderSection('content') ?>
    </div>

    <script src="<?= base_url('assets/vendor/jquery/jquery-3.7.1.min.js') ?>"></script>
    <script src="<?= base_url('assets/vendor/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="<?= base_url('assets/vendor/datatables/js/jquery.dataTables.min.js') ?>"></script>
    <script src="<?= base_url('assets/vendor/datatables/js/dataTables.bootstrap5.min.js') ?>"></script>
    <script src="<?= base_url('assets/vendor/chart.js/chart.umd.min.js') ?>"></script>
    <script src="<?= base_url('assets/vendor/jquery-mask-plugin/jquery.mask.min.js') ?>"></script>
    <script src="<?= base_url('assets/vendor/select2/js/select2.min.js') ?>"></script>
    <script src="<?= base_url('assets/vendor/sweetalert2/sweetalert2.all.min.js') ?>"></script>
    <script src="<?= base_url('assets/js/scripts.js') ?>"></script>
    <?= $this->renderSection('scripts') ?>
</body>
</html>
