<!DOCTYPE html>
<html lang="pt-BR" data-theme="<?= get_theme() ?>" data-bs-theme="<?= get_theme() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="base-url" content="<?= base_url() ?>">
    <title><?= $title ?? 'Sistema' ?> - <?= esc(get_config('sistema_nãome', 'Assistência Técnica')) ?></title>
    
    <?php $favicon = get_config('sistema_icone'); if($favicon && file_exists('uploads/sistema/'.$favicon)): ?>
    <link rel="icon" href="<?= base_url('uploads/sistema/'.$favicon) ?>">
    <?php else: ?>
    <link rel="icon" href="<?= base_url('favicon.ico') ?>">
    <?php endif; ?>
    <!-- Bootstrap 5 CSS -->
    <link href="<?= base_url('assets/vendor/bootstrap/css/bootstrap.min.css') ?>" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="<?= base_url('assets/vendor/bootstrap-icons/css/bootstrap-icons.css') ?>" rel="stylesheet">
    
    <!-- DataTables CSS -->
    <link href="<?= base_url('assets/vendor/datatables/css/dataTables.bootstrap5.min.css') ?>" rel="stylesheet">
    
    <!-- Select2 CSS -->
    <link href="<?= base_url('assets/vendor/select2/css/select2.min.css') ?>" rel="stylesheet" />
    <link rel="stylesheet" href="<?= base_url('assets/vendor/select2-bootstrap-5-theme/css/select2-bootstrap-5-theme.min.css') ?>" />
    <link rel="stylesheet" href="<?= base_url('assets/vendor/sweetalert2/sweetalert2.min.css') ?>" />

    <!-- Custom CSS -->
    <link href="<?= base_url('assets/css/estilo.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/css/design-system/index.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/css/global-search.css') ?>" rel="stylesheet">
</head>
<body class="ds-app-shell">
    <div class="app-wrapper">
        <!-- Sidebar -->
        <?= $this->include('layouts/sidebar') ?>
        
        <!-- Main Content -->
        <div class="main-content" id="mainContent">
            <!-- Top Navbar -->
            <?= $this->include('layouts/navbar') ?>
            
            <!-- Page Content -->
            <div class="page-content">
                <!-- Flash Messages -->
                <?php if (session()->getFlashdata('success')): ?>
                    <div class="alert alert-success alert-dismissible fade show animate-slide-in" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <?= session()->getFlashdata('success') ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger alert-dismissible fade show animate-slide-in" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <?= session()->getFlashdata('error') ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (session()->getFlashdata('errors')): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <strong>Erros de validação:</strong>
                        <ul class="mb-0 mt-1">
                            <?php foreach (session()->getFlashdata('errors') as $error): ?>
                                <li><?= esc($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Page Content -->
                <?= $this->renderSection('content') ?>
            </div>
        </div>
    </div>
    
    <!-- jQuery -->
    <script src="<?= base_url('assets/vendor/jquery/jquery-3.7.1.min.js') ?>"></script>
    
    <!-- Bootstrap JS -->
    <script src="<?= base_url('assets/vendor/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
    
    <!-- DataTables JS -->
    <script src="<?= base_url('assets/vendor/datatables/js/jquery.dataTables.min.js') ?>"></script>
    <script src="<?= base_url('assets/vendor/datatables/js/dataTables.bootstrap5.min.js') ?>"></script>
    
    <!-- Chart.js -->
    <script src="<?= base_url('assets/vendor/chart.js/chart.umd.min.js') ?>"></script>
    
    <!-- jQuery Mask -->
    <script src="<?= base_url('assets/vendor/jquery-mask-plugin/jquery.mask.min.js') ?>"></script>
    
    <!-- Select2 JS -->
    <script src="<?= base_url('assets/vendor/select2/js/select2.min.js') ?>"></script>
    <script src="<?= base_url('assets/vendor/sweetalert2/sweetalert2.all.min.js') ?>"></script>
    
    <!-- Custom JS -->
    <script src="<?= base_url('assets/js/scripts.js') ?>"></script>
    <script src="<?= base_url('assets/js/global-search.js') ?>"></script>
    
    <?= $this->renderSection('scripts') ?>
</body>
</html>
