<!-- Top Navbar -->
<nav class="top-navbar">
    <div class="navbar-left">
        <button class="btn btn-link mobile-toggle" id="mobileToggle">
            <i class="bi bi-list"></i>
        </button>
        <h4 class="page-title mb-0"><?= $title ?? '' ?></h4>
    </div>
    
    <div class="navbar-right">
        <!-- Quick Actions -->
        <div class="navbar-actions">
            <?php if (can('os', 'criar')): ?>
            <a href="<?= base_url('os/nova') ?>" class="btn btn-glow btn-sm" title="Nova OS">
                <i class="bi bi-plus-lg me-1"></i> Nova OS
            </a>
            <?php endif; ?>
        </div>
        
        <!-- User Menu -->
        <div class="dropdown">
            <button class="btn btn-link user-dropdown" type="button" data-bs-toggle="dropdown">
                <div class="user-avatar-sm">
                    <?php if (session()->get('user_foto') && file_exists('uploads/usuarios/' . session()->get('user_foto'))): ?>
                        <img src="<?= base_url('uploads/usuarios/' . session()->get('user_foto')) ?>" alt="Avatar" class="rounded-circle rounded border" style="width: 32px; height: 32px; object-fit: cover;">
                    <?php else: ?>
                        <i class="bi bi-person-circle fs-4"></i>
                    <?php endif; ?>
                </div>
                <span class="d-none d-md-inline"><?= esc(session()->get('user_nome') ?? 'Usuário') ?></span>
                <i class="bi bi-chevron-down ms-1"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li>
                    <span class="dropdown-header">
                        <strong><?= esc(session()->get('user_nome') ?? '') ?></strong><br>
                        <small class="text-muted"><?= esc(session()->get('user_email') ?? '') ?></small>
                    </span>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="<?= base_url('perfil') ?>"><i class="bi bi-person me-2"></i>Meu Perfil</a></li>
                <?php if (can('configuracoes', 'visualizar')): ?>
                <li><a class="dropdown-item" href="<?= base_url('configuracoes') ?>"><i class="bi bi-gear me-2"></i>Configurações</a></li>
                <?php endif; ?>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="<?= base_url('logout') ?>"><i class="bi bi-box-arrow-right me-2"></i>Sair</a></li>
                <li><a class="dropdown-item text-danger" href="<?= base_url('logout?forget=1') ?>" title="Sair do sistema e limpar credenciais preenchidas do navegador"><i class="bi bi-person-x me-2"></i>Sair e Esquecer Login</a></li>
            </ul>
        </div>
    </div>
</nav>
