<!-- Sidebar -->
<nav class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-brand d-flex align-items-center">
            <?php $logo = get_config('sistema_logo'); if($logo && file_exists('uploads/sistema/'.$logo)): ?>
                <img src="<?= base_url('uploads/sistema/'.$logo) ?>" alt="Logo" style="max-height: 32px; margin-right: 8px;">
            <?php else: ?>
                <i class="bi bi-tools brand-icon"></i>
            <?php endif; ?>
            <span class="brand-text text-truncate"><?= esc(get_config('sistema_nome', 'AssistTech')) ?></span>
        </div>
        <button class="sidebar-toggle" id="sidebarToggle" title="Recolher menu">
            <i class="bi bi-chevron-left"></i>
        </button>
    </div>
    
    <div class="sidebar-menu">
        <ul class="nav flex-column">

            <!-- ── VISÃO GERAL ────────────────────────── -->
            <?php if (canModule('dashboard')): ?>
            <li class="nav-section">VISÃO GERAL</li>
            <li class="nav-item">
                <a class="nav-link <?= uri_string() === 'dashboard' ? 'active' : '' ?>" href="<?= base_url('dashboard') ?>">
                    <i class="bi bi-grid-1x2-fill"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <?php endif; ?>

            <!-- ── OPERACIONAL ────────────────────────── -->
            <?php 
            $showOperacional = canModule('os') || canModule('servicos') || canModule('estoque') || canModule('equipamentos') || canModule('defeitos');
            ?>
            <?php if ($showOperacional): ?>
            <li class="nav-section">OPERACIONAL</li>
            
            <?php if (canModule('os')): ?>
            <li class="nav-item">
                <a class="nav-link <?= str_starts_with(uri_string(), 'os') ? 'active' : '' ?>" href="<?= base_url('os') ?>">
                    <i class="bi bi-clipboard-check-fill"></i>
                    <span>Ordens de Serviço</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if (canModule('servicos')): ?>
            <li class="nav-item">
                <a class="nav-link <?= str_starts_with(uri_string(), 'servicos') ? 'active' : '' ?>" href="<?= base_url('servicos') ?>">
                    <i class="bi bi-gear-wide-connected"></i>
                    <span>Serviços</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if (canModule('estoque')): ?>
            <li class="nav-item">
                <a class="nav-link <?= str_starts_with(uri_string(), 'estoque') ? 'active' : '' ?>" href="<?= base_url('estoque') ?>">
                    <i class="bi bi-box-seam-fill"></i>
                    <span>Estoque de Peças</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if (canModule('equipamentos')): ?>
            <?php 
            $isEquipMenuGlobalActive = str_starts_with(uri_string(), 'equipamentos') 
                || str_starts_with(uri_string(), 'equipamentostipos') 
                || str_starts_with(uri_string(), 'equipamentosmarcas') 
                || str_starts_with(uri_string(), 'equipamentosmodelos'); 
            ?>
            <li class="nav-item">
                <a class="nav-link <?= $isEquipMenuGlobalActive ? 'active' : 'collapsed' ?>" data-bs-toggle="collapse" href="#equipamentosSubmenu" role="button">
                    <i class="bi bi-laptop"></i>
                    <span class="d-flex justify-content-between w-100 align-items-center">
                        Aparelhos / Equip.
                        <i class="bi bi-chevron-down ms-1" style="font-size: 0.75rem;"></i>
                    </span>
                </a>
                <div class="collapse <?= $isEquipMenuGlobalActive ? 'show' : '' ?>" id="equipamentosSubmenu">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?= (uri_string() === 'equipamentos' || (str_starts_with(uri_string(), 'equipamentos/') && !str_contains(uri_string(), 'defeitos'))) ? 'active' : '' ?>" href="<?= base_url('equipamentos') ?>">
                                <i class="bi bi-list-ul"></i><span>Listar Todos</span>
                            </a>
                        </li>
                        <li class="nav-item"><a class="nav-link <?= str_starts_with(uri_string(), 'equipamentostipos') ? 'active' : '' ?> py-1" href="<?= base_url('equipamentostipos') ?>"><i class="bi bi-tag"></i><small>Tipos de Aparelho</small></a></li>
                        <li class="nav-item"><a class="nav-link <?= str_starts_with(uri_string(), 'equipamentosmarcas') ? 'active' : '' ?> py-1" href="<?= base_url('equipamentosmarcas') ?>"><i class="bi bi-patch-check"></i><small>Marcas</small></a></li>
                        <li class="nav-item"><a class="nav-link <?= str_starts_with(uri_string(), 'equipamentosmodelos') ? 'active' : '' ?> py-1" href="<?= base_url('equipamentosmodelos') ?>"><i class="bi bi-box"></i><small>Modelos</small></a></li>
                    </ul>
                </div>
            </li>
            <?php endif; ?>

            <?php if (canModule('defeitos')): ?>
            <li class="nav-item">
                <a class="nav-link <?= str_starts_with(uri_string(), 'equipamentosdefeitos') ? 'active' : '' ?>" href="<?= base_url('equipamentosdefeitos') ?>">
                    <i class="bi bi-bug-fill"></i>
                    <span>Base de Defeitos</span>
                </a>
            </li>
            <?php endif; ?>
            <?php endif; ?>

            <!-- ── COMERCIAL ──────────────────────────── -->
            <?php 
            $showComercial = canModule('clientes') || canModule('fornecedores') || canModule('funcionarios') || canModule('vendas');
            ?>
            <?php if ($showComercial): ?>
            <li class="nav-section">COMERCIAL</li>
            
            <!-- Submenu Pessoas -->
            <?php if (canModule('clientes') || canModule('fornecedores') || canModule('funcionarios')): ?>
            <?php 
            $isPessoasActive = str_starts_with(uri_string(), 'clientes') 
                || str_starts_with(uri_string(), 'fornecedores') 
                || str_starts_with(uri_string(), 'funcionarios'); 
            ?>
            <li class="nav-item">
                <a class="nav-link <?= $isPessoasActive ? 'active' : 'collapsed' ?>" data-bs-toggle="collapse" href="#pessoasSubmenu" role="button">
                    <i class="bi bi-people-fill"></i>
                    <span class="d-flex justify-content-between w-100 align-items-center">
                        Pessoas
                        <i class="bi bi-chevron-down" style="font-size: 0.75rem;"></i>
                    </span>
                </a>
                <div class="collapse <?= $isPessoasActive ? 'show' : '' ?>" id="pessoasSubmenu">
                    <ul class="nav flex-column">
                        <?php if (canModule('clientes')): ?>
                        <li class="nav-item"><a class="nav-link <?= str_starts_with(uri_string(), 'clientes') ? 'active' : '' ?>" href="<?= base_url('clientes') ?>"><i class="bi bi-person-badge"></i><span>Clientes</span></a></li>
                        <?php endif; ?>
                        
                        <?php if (canModule('funcionarios')): ?>
                        <li class="nav-item"><a class="nav-link <?= str_starts_with(uri_string(), 'funcionarios') ? 'active' : '' ?>" href="<?= base_url('funcionarios') ?>"><i class="bi bi-person-workspace"></i><span>Equipe Técnico</span></a></li>
                        <?php endif; ?>
                        <?php if (canModule('fornecedores')): ?>
                        <li class="nav-item"><a class="nav-link <?= str_starts_with(uri_string(), 'fornecedores') ? 'active' : '' ?>" href="<?= base_url('fornecedores') ?>"><i class="bi bi-truck"></i><span>Fornecedores</span></a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </li>
            <?php endif; ?>

            <?php if (canModule('vendas')): ?>
            <li class="nav-item">
                <a class="nav-link <?= str_starts_with(uri_string(), 'vendas') ? 'active' : '' ?>" href="<?= base_url('vendas') ?>">
                    <i class="bi bi-cart-check-fill"></i>
                    <span>Vendas</span>
                </a>
            </li>
            <?php endif; ?>
            <?php endif; ?>


            <!-- ── GESTÃO & RESULTADOS ──────────────────── -->
            <?php if (canModule('financeiro') || canModule('relatorios')): ?>
            <li class="nav-section">GESTÃO & RESULTADOS</li>
            
            <?php if (canModule('financeiro')): ?>
            <li class="nav-item">
                <a class="nav-link <?= uri_string() === 'financeiro' ? 'active' : '' ?>" href="<?= base_url('financeiro') ?>">
                    <i class="bi bi-cash-stack"></i>
                    <span>Financeiro</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if (canModule('relatorios')): ?>
            <li class="nav-item">
                <a class="nav-link <?= uri_string() === 'relatorios' ? 'active' : '' ?>" href="<?= base_url('relatorios') ?>">
                    <i class="bi bi-graph-up-arrow"></i>
                    <span>Relatórios</span>
                </a>
            </li>
            <?php endif; ?>
            <?php endif; ?>

            <!-- ── CONFIGURAÇÕES ──────────────────────── -->
            <?php if (canModule('configuracoes') || canModule('usuarios') || canModule('grupos')): ?>
            <li class="nav-section">CONFIGURAÇÕES</li>

            <?php if (canModule('configuracoes')): ?>
            <li class="nav-item">
                <a class="nav-link <?= uri_string() === 'configuracoes' ? 'active' : '' ?>" href="<?= base_url('configuracoes') ?>">
                    <i class="bi bi-shop"></i>
                    <span>Dados da Empresa</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if (canModule('usuarios') || canModule('grupos')): ?>
            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-toggle="collapse" href="#menuSeguranca" role="button">
                    <i class="bi bi-shield-lock-fill"></i>
                    <span class="d-flex justify-content-between w-100 align-items-center">
                        Segurança
                        <i class="bi bi-chevron-down ms-1" style="font-size: 0.75rem;"></i>
                    </span>
                </a>
                <div class="collapse" id="menuSeguranca">
                    <ul class="nav flex-column">
                        <?php if (canModule('usuarios')): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= base_url('usuarios') ?>">Usuários</a>
                        </li>
                        <?php endif; ?>
                        <?php if (canModule('grupos')): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= base_url('grupos') ?>">Níveis de Acesso</a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </li>
            <?php endif; ?>

            <?php if (session()->get('user_grupo_id') == 1 || can('configuracoes', 'visualizar')): ?>
            <li class="nav-item">
                <a class="nav-link <?= uri_string() === 'documentacao' ? 'active' : '' ?>" href="<?= base_url('documentacao') ?>">
                    <i class="bi bi-journal-richtext"></i>
                    <span>Documentação</span>
                </a>
            </li>
            <?php endif; ?>
            <?php endif; ?>
        </ul>
    </div>

    <div class="sidebar-footer">
        <div class="user-mini-profile">
            <div class="user-avatar overflow-hidden d-flex align-items-center justify-content-center">
                <a href="<?= base_url('perfil') ?>" class="text-white text-decoration-none" style="display: block;">
                    <?php if (session()->get('user_foto') && file_exists('uploads/usuarios/' . session()->get('user_foto'))): ?>
                        <img src="<?= base_url('uploads/usuarios/' . session()->get('user_foto')) ?>" alt="Avatar" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                    <?php else: ?>
                        <i class="bi bi-person-circle fs-3 align-middle"></i>
                    <?php endif; ?>
                </a>
            </div>
            <div class="user-info">
                <span class="user-name"><?= esc(session()->get('user_nome') ?? 'Usuário') ?></span>
                <span class="user-role"><?= esc(session()->get('user_grupo_nome') ?: ucfirst(session()->get('user_perfil') ?? '')) ?></span>
            </div>
        </div>
    </div>
</nav>

<!-- Sidebar Overlay (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>
