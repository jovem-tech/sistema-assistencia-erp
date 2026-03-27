<!DOCTYPE html>
<html lang="pt-BR" data-theme="<?= get_theme() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="base-url" content="<?= base_url() ?>">
    <title>Login - <?= esc(get_config('sistema_nome', 'Assistencia Tecnica')) ?></title>

    <?php $favicon = get_config('sistema_icone'); if($favicon && file_exists('uploads/sistema/'.$favicon)): ?>
    <link rel="icon" href="<?= base_url('uploads/sistema/'.$favicon) ?>">
    <?php else: ?>
    <link rel="icon" href="<?= base_url('favicon.ico') ?>">
    <?php endif; ?>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/estilo.css') ?>" rel="stylesheet">
</head>
<body class="login-body">
    <div class="login-wrapper">
        <div class="login-bg-effects">
            <div class="floating-shape shape-1"></div>
            <div class="floating-shape shape-2"></div>
            <div class="floating-shape shape-3"></div>
            <div class="floating-shape shape-4"></div>
        </div>

        <div class="login-container">
            <div class="login-card">
                <div class="login-header text-center mb-4">
                    <div class="login-logo d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 80px; height: 80px; border-radius: 20px; background: rgba(var(--primary-rgb), 0.1); color: var(--primary);">
                        <?php $logo = get_config('sistema_logo'); if($logo && file_exists('uploads/sistema/'.$logo)): ?>
                            <img src="<?= base_url('uploads/sistema/'.$logo) ?>" alt="Logo" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                        <?php else: ?>
                            <i class="bi bi-tools fs-1"></i>
                        <?php endif; ?>
                    </div>
                    <h1 class="login-title h3 font-weight-bold mb-1"><?= esc(get_config('sistema_nome', 'AssistTech')) ?></h1>
                    <p class="login-subtitle text-muted mb-1">Sistema de Assistencia Tecnica</p>
                    <p class="login-version mb-0">Versao <?= esc(get_system_version()) ?></p>
                </div>

                <?php if (session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <?= session()->getFlashdata('error') ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (session()->getFlashdata('success')): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <?= session()->getFlashdata('success') ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form action="<?= base_url('login') ?>" method="POST" class="login-form" autocomplete="off">
                    <input style="display:none" type="text" name="fakeusernameremembered" autocomplete="username"/>
                    <input style="display:none" type="password" name="fakepasswordremembered" autocomplete="current-password"/>

                    <div class="form-floating-custom">
                        <div class="input-icon-wrapper">
                            <i class="bi bi-envelope input-icon"></i>
                            <input type="email" class="form-control" id="email" name="email"
                                   placeholder="Email" required autofocus
                                   value="<?= old('email') ?>" autocomplete="off">
                        </div>
                    </div>

                    <div class="form-floating-custom">
                        <div class="input-icon-wrapper">
                            <i class="bi bi-lock input-icon"></i>
                            <input type="password" class="form-control" id="senha" name="senha"
                                   placeholder="Senha" required autocomplete="new-password">
                            <button type="button" class="btn-toggle-password" onclick="togglePassword()">
                                <i class="bi bi-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="lembrar" name="lembrar">
                            <label class="form-check-label" for="lembrar">Lembrar-me</label>
                        </div>
                        <a href="<?= base_url('esqueci-senha') ?>" class="forgot-link">Esqueci minha senha</a>
                    </div>

                    <button type="submit" class="btn btn-glow btn-login w-100">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Entrar
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword() {
            const input = document.getElementById('senha');
            const icon = document.getElementById('toggleIcon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'bi bi-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'bi bi-eye';
            }
        }

        if (window.location.search.includes('cleared=1')) {
            setTimeout(() => {
                document.getElementById('email').value = '';
                document.getElementById('senha').value = '';
                window.history.replaceState({}, document.title, window.location.pathname);
            }, 50);
        }
    </script>
</body>
</html>
