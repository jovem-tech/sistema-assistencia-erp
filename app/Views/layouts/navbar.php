<!-- Top Navbar -->
<!-- Top Navbar -->
<?php
$uri = service('uri');
$currentModule = $uri->getSegment(1);
$currentSubroute = $uri->getSegment(2);
$isOsListPage = $currentModule === 'os' && empty($currentSubroute);
$notificationFeedUrl = site_url('notificacoes/navbar-feed');
$notificationStreamUrl = site_url('notificacoes/stream');
$notificationReadBaseUrl = site_url('notificacoes/lida');
$notificationReadAllUrl = site_url('notificacoes/lidas');
$notificationClearReadUrl = site_url('notificacoes/limpar-lidas');
$notificationAppUrl = rtrim(site_url(), '/') . '/';
?>
<nav class="top-navbar<?= $isOsListPage ? ' os-list-navbar' : '' ?>">
    <div class="navbar-left">
        <button class="btn btn-link mobile-toggle" id="mobileToggle">
            <i class="bi bi-list"></i>
        </button>
        <h4 class="page-title mb-0"><?= $title ?? '' ?></h4>
    </div>

    <!-- Busca Global -->
    <?= view('layouts/partials/global_search', [
        'searchIdSuffix' => 'navbar',
        'searchWrapperClass' => 'navbar-search-desktop',
    ]) ?>

    <div class="navbar-right">
        <!-- Quick Actions -->
        <div class="navbar-actions">
            <?php if (can('os', 'criar') && !$isOsListPage): ?>
            <a href="<?= base_url('os/nova') ?>" class="btn btn-glow btn-sm" title="Nova OS">
                <i class="bi bi-plus-lg me-1"></i><span class="nav-action-label">Nova OS</span>
            </a>
            <?php endif; ?>
        </div>

        <div
            class="dropdown navbar-notifications"
            id="navbarNotifications"
            data-feed-url="<?= esc($notificationFeedUrl) ?>"
            data-stream-url="<?= esc($notificationStreamUrl) ?>"
            data-read-url-base="<?= esc($notificationReadBaseUrl) ?>"
            data-read-all-url="<?= esc($notificationReadAllUrl) ?>"
            data-clear-read-url="<?= esc($notificationClearReadUrl) ?>"
            data-base-url="<?= esc(base_url()) ?>"
            data-app-url="<?= esc($notificationAppUrl) ?>"
        >
            <button
                class="btn btn-link navbar-notification-toggle"
                type="button"
                data-bs-toggle="dropdown"
                aria-expanded="false"
                aria-label="Abrir NotificaÃ§Ãµes"
            >
                <i class="bi bi-bell"></i>
                <span class="navbar-notification-badge d-none" id="navbarNotificationCount">0</span>
            </button>
            <div class="dropdown-menu dropdown-menu-end navbar-notification-menu p-0">
                <div class="navbar-notification-header">
                    <div>
                        <strong>NotificaÃ§Ãµes</strong>
                        <small id="navbarNotificationMeta">Sincronizando...</small>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-link btn-sm navbar-notification-mark-all" id="navbarNotificationClearRead">
                            Limpar lidas
                        </button>
                        <button type="button" class="btn btn-link btn-sm navbar-notification-mark-all" id="navbarNotificationMarkAll">
                            Marcar todas
                        </button>
                    </div>
                </div>
                <div class="navbar-notification-list" id="navbarNotificationList">
                    <div class="navbar-notification-empty">Carregando notificacoes...</div>
                </div>
            </div>
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
                <span class="d-none d-md-inline"><?= esc(session()->get('user_nome') ?? 'UsuÃ¡rio') ?></span>
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
                <li><a class="dropdown-item" href="<?= base_url('configuracoes') ?>"><i class="bi bi-gear me-2"></i>Configura&ccedil;&otilde;es</a></li>
                <?php endif; ?>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="<?= base_url('logout') ?>"><i class="bi bi-box-arrow-right me-2"></i>Sair</a></li>
                <li><a class="dropdown-item text-danger" href="<?= base_url('logout?forget=1') ?>" title="Sair do sistema e limpar credenciais preenchidas do navegador"><i class="bi bi-person-x me-2"></i>Sair e Esquecer Login</a></li>
            </ul>
        </div>
    </div>
</nav>

