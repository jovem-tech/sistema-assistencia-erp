<!-- Top Navbar -->
<!-- Top Navbar -->
<?php
$uri = service('uri');
$currentModule = $uri->getSegment(1);
$currentSubroute = $uri->getSegment(2);
$isOsListPage = $currentModule === 'os' && empty($currentSubroute);
?>
<nav class="top-navbar">
    <div class="navbar-left">
        <button class="btn btn-link mobile-toggle" id="mobileToggle">
            <i class="bi bi-list"></i>
        </button>
        <h4 class="page-title mb-0"><?= $title ?? '' ?></h4>
    </div>
    
    <!-- Busca Global -->
    <div class="navbar-search-wrapper">
        <div class="search-input-group">
            <div class="dropdown h-100">
                <button class="btn btn-link search-filter-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" data-bs-auto-close="outside">
                    <span class="filter-label d-none d-lg-inline">Tudo</span>
                    <i class="bi bi-funnel d-lg-none"></i>
                </button>
                <ul class="dropdown-menu search-filter-menu p-2">
                    <li>
                        <a class="dropdown-item filter-all active" href="javascript:void(0)" data-filter="all">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="all" id="filter-all" checked>
                                <label class="form-check-label w-100 cursor-pointer" for="filter-all">
                                    <i class="bi bi-grid-fill me-2"></i>Tudo
                                </label>
                            </div>
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item" href="javascript:void(0)" data-filter="os">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="os" id="filter-os">
                                <label class="form-check-label w-100 cursor-pointer" for="filter-os">
                                    <i class="bi bi-file-earmark-text me-2"></i>OS
                                </label>
                            </div>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="javascript:void(0)" data-filter="os_legado">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="os_legado" id="filter-os-legado">
                                <label class="form-check-label w-100 cursor-pointer" for="filter-os-legado">
                                    <i class="bi bi-clock-history me-2"></i>OS Legado (numero antigo)
                                </label>
                            </div>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="javascript:void(0)" data-filter="clientes">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="clientes" id="filter-clientes">
                                <label class="form-check-label w-100 cursor-pointer" for="filter-clientes">
                                    <i class="bi bi-people me-2"></i>Clientes
                                </label>
                            </div>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="javascript:void(0)" data-filter="whatsapp">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="whatsapp" id="filter-whatsapp">
                                <label class="form-check-label w-100 cursor-pointer" for="filter-whatsapp">
                                    <i class="bi bi-whatsapp me-2"></i>WhatsApp
                                </label>
                            </div>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="javascript:void(0)" data-filter="equipamentos">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="equipamentos" id="filter-equipamentos">
                                <label class="form-check-label w-100 cursor-pointer" for="filter-equipamentos">
                                    <i class="bi bi-laptop me-2"></i>Equipamentos
                                </label>
                            </div>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="javascript:void(0)" data-filter="servicos">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="servicos" id="filter-servicos">
                                <label class="form-check-label w-100 cursor-pointer" for="filter-servicos">
                                    <i class="bi bi-tools me-2"></i>Servi&ccedil;os
                                </label>
                            </div>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="javascript:void(0)" data-filter="pecas">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="pecas" id="filter-pecas">
                                <label class="form-check-label w-100 cursor-pointer" for="filter-pecas">
                                    <i class="bi bi-box-seam me-2"></i>Pe&ccedil;as
                                </label>
                            </div>
                        </a>
                    </li>
                </ul>
            </div>
            <i class="bi bi-search search-icon ms-2"></i>
            <input type="text" class="search-input" placeholder="O que voce procura? (inclui OS legado)" autocomplete="off">
            <div class="search-results-container shadow-lg">
                <div class="search-loading-state d-none">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Buscando...</span>
                    </div>
                </div>
                <div class="search-empty-state d-none">
                    <i class="bi bi-search"></i>
                    <p>Nenhum resultado encontrado.</p>
                </div>
                <div class="search-results-list"></div>
            </div>
        </div>
    </div>
    
    <div class="navbar-right">
        <!-- Quick Actions -->
        <div class="navbar-actions">
            <?php if (can('os', 'criar') && !$isOsListPage): ?>
            <a href="<?= base_url('os/nova') ?>" class="btn btn-glow btn-sm" title="Nova OS">
                <i class="bi bi-plus-lg me-1"></i><span class="nav-action-label">Nova OS</span>
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
                <li><a class="dropdown-item" href="<?= base_url('configuracoes') ?>"><i class="bi bi-gear me-2"></i>Configura&ccedil;&otilde;es</a></li>
                <?php endif; ?>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="<?= base_url('logout') ?>"><i class="bi bi-box-arrow-right me-2"></i>Sair</a></li>
                <li><a class="dropdown-item text-danger" href="<?= base_url('logout?forget=1') ?>" title="Sair do sistema e limpar credenciais preenchidas do navegador"><i class="bi bi-person-x me-2"></i>Sair e Esquecer Login</a></li>
            </ul>
        </div>
    </div>
</nav>

