<!DOCTYPE html>
<html lang="pt-BR" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Senha - Assistência Técnica</title>
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
                        <i class="bi bi-shield-lock"></i>
                    </div>
                    <h1 class="login-title">Redefinir Senha</h1>
                    <p class="login-subtitle">Crie uma nova senha para sua conta</p>
                </div>
                
                <?php if (session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
                <?php endif; ?>
                
                <form action="<?= base_url('redefinir-senha/' . $token) ?>" method="POST" class="login-form">
                    <div class="form-floating-custom mb-3">
                        <div class="input-icon-wrapper">
                            <i class="bi bi-key input-icon"></i>
                            <input type="password" class="form-control" name="senha" placeholder="Nova Senha" minlength="6" required autofocus>
                        </div>
                    </div>
                    <div class="form-floating-custom">
                        <div class="input-icon-wrapper">
                            <i class="bi bi-check2-circle input-icon"></i>
                            <input type="password" class="form-control" name="senha_confirmar" placeholder="Confirme a Nova Senha" minlength="6" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-glow btn-login w-100 mb-3">
                        <i class="bi bi-check2-all me-2"></i>Salvar Nova Senha
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
