<!DOCTYPE html>
<html lang="pt-BR" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha - Assistência Técnica</title>
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
        </div>
        
        <div class="login-container">
            <div class="login-card">
                <div class="login-header">
                    <div class="login-logo">
                        <i class="bi bi-key"></i>
                    </div>
                    <h1 class="login-title">Recuperar Senha</h1>
                    <p class="login-subtitle">Informe seu email para redefinir a senha</p>
                </div>
                
                <?php if (session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
                <?php endif; ?>
                <?php if (session()->getFlashdata('success')): ?>
                    <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
                <?php endif; ?>
                
                <form action="<?= base_url('esqueci-senha') ?>" method="POST" class="login-form">
                    <div class="form-floating-custom">
                        <div class="input-icon-wrapper">
                            <i class="bi bi-envelope input-icon"></i>
                            <input type="email" class="form-control" name="email" placeholder="Email" required autofocus>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-glow btn-login w-100 mb-3">
                        <i class="bi bi-send me-2"></i>Enviar Link
                    </button>
                    <div class="text-center">
                        <a href="<?= base_url('login') ?>" class="forgot-link"><i class="bi bi-arrow-left me-1"></i>Voltar ao login</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
