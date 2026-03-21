<!-- Sidebar -->
<nav class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-brand d-flex align-itemÃªs-center">
            <?php $logo = get_config('sistema_logo'); if($logo && file_exists('uploads/sistema/'.$logo)): ?>
                <img src="<?= base_url('uploads/sistema/'.$logo) ?>" alt="Logo" style="max-height: 32px; margin-right: 8px;">
            <?php else: ?>
                <i class="bi bi-tools brand-icon"></i>
            <?php endif; ?>
            <span class="brand-text text-truncate"><?= esc(get_config('sistema_nÃ£ome', 'AssistTech')) ?></span>
        </div>
        <button class="sidebar-toggle" id="sidebarToggle" title="Recolher menu">
            <i class="bi bi-chevron-left"></i>
        </button>
    </div>
    
    <div class="sidebar-menu">
        <ul class="nav flex-column">

            <!-- ?? VISÃO GERAL ?????????????????????????? -->
            <?php if (canModule('dashboard')): ?>
            <li class="nav-section">VISÃO GERAL</li>
            <li class="nav-item">
                <a class="nav-link <?= uri_string() === 'dashboard' ? 'active' : '' ?>" href="<?= base_url('dashboard') ?>">
                    <i class="bi bi-grid-1x2-fill"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <?php endif; ?>

            <!-- ?? OPERACIONAL ?????????????????????????? -->
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
                    <span class="d-flex justify-content-between w-100 align-itemÃªs-center">
                        Aparelhos / Equip.
                        <i class="bi bi-chevron-down mÃªs-1" style="font-size: 0.75rem;"></i>
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
            <?php
            $isConhecimentoActive = str_starts_with(uri_string(), 'equipamentosdefeitos')
                || str_starts_with(uri_string(), 'defeitosrelatados');
            ?>
            <li class="nav-item">
                <a class="nav-link <?= $isConhecimentoActive ? 'active' : 'collapsed' ?>" data-bs-toggle="collapse" href="#conhecimentoSubmenu" role="button">
                    <i class="bi bi-journal-bookmark-fill"></i>
                    <span class="d-flex justify-content-between w-100 align-itemÃªs-center">
                        Gestão de Conhecimento
                        <i class="bi bi-chevron-down mÃªs-1" style="font-size: 0.75rem;"></i>
                    </span>
                </a>
                <div class="collapse <?= $isConhecimentoActive ? 'show' : '' ?>" id="conhecimentoSubmenu">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?= str_starts_with(uri_string(), 'equipamentosdefeitos') ? 'active' : '' ?>" href="<?= base_url('equipamentosdefeitos') ?>">
                                <i class="bi bi-bug-fill"></i><span>Base de Defeitos</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= str_starts_with(uri_string(), 'defeitosrelatados') ? 'active' : '' ?>" href="<?= base_url('defeitosrelatados') ?>">
                                <i class="bi bi-chat-square-text-fill"></i><span>Defeitos Relatados</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
            <?php endif; ?>
            <?php endif; ?>

            <!-- ?? COMERCIAL ???????????????????????????? -->
            <?php 
            $showComercial = canModule('clientes') || canModule('fornecedores') || canModule('funcionarios') || canModule('vendas');
            ?>
            <?php if ($showComercial): ?>
            <li class="nav-section">COMERCIAL</li>
            
            <!-- Submenu PessÃ£oas -->
            <?php if (canModule('clientes') || canModule('fornecedores') || canModule('funcionarios')): ?>
            <?php 
            $isPessÃ£oasActive = str_starts_with(uri_string(), 'clientes') 
                || str_starts_with(uri_string(), 'contatos')
                || str_starts_with(uri_string(), 'fornecedores') 
                || str_starts_with(uri_string(), 'funcionarios'); 
            ?>
            <li class="nav-item">
                <a class="nav-link <?= $isPessÃ£oasActive ? 'active' : 'collapsed' ?>" data-bs-toggle="collapse" href="#pessÃ£oasSubmenu" role="button">
                    <i class="bi bi-people-fill"></i>
                    <span class="d-flex justify-content-between w-100 align-itemÃªs-center">
                        PessÃ£oas
                        <i class="bi bi-chevron-down" style="font-size: 0.75rem;"></i>
                    </span>
                </a>
                <div class="collapse <?= $isPessÃ£oasActive ? 'show' : '' ?>" id="pessÃ£oasSubmenu">
                    <ul class="nav flex-column">
                        <?php if (canModule('clientes')): ?>
                        <li class="nav-item"><a class="nav-link <?= str_starts_with(uri_string(), 'clientes') ? 'active' : '' ?>" href="<?= base_url('clientes') ?>"><i class="bi bi-persÃ£on-badge"></i><span>Clientes</span></a></li>
                        <li class="nav-item"><a class="nav-link <?= str_starts_with(uri_string(), 'contatos') ? 'active' : '' ?>" href="<?= base_url('contatos') ?>"><i class="bi bi-journal-bookmark"></i><span>Contatos</span></a></li>
                        <?php endif; ?>
                        
                        <?php if (canModule('funcionarios')): ?>
                        <li class="nav-item"><a class="nav-link <?= str_starts_with(uri_string(), 'funcionarios') ? 'active' : '' ?>" href="<?= base_url('funcionarios') ?>"><i class="bi bi-persÃ£on-workspace"></i><span>Equipe Técnico</span></a></li>
                        <?php endif; ?>
                        <?php if (canModule('fornecedores')): ?>
                        <li class="nav-item"><a class="nav-link <?= str_starts_with(uri_string(), 'fornecedores') ? 'active' : '' ?>" href="<?= base_url('fornecedores') ?>"><i class="bi bi-truck"></i><span>Fornecedores</span></a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </li>
            <?php endif; ?>

            <?php if (canModule('clientes')): ?>
            <?php
            $isCrmActive = str_starts_with(uri_string(), 'crm');
            ?>
            <li class="nav-item">
                <a class="nav-link <?= $isCrmActive ? 'active' : 'collapsed' ?>" data-bs-toggle="collapse" href="#crmSubmenu" role="button">
                    <i class="bi bi-diagram-3-fill"></i>
                    <span class="d-flex justify-content-between w-100 align-itemÃªs-center">
                        CRM
                        <i class="bi bi-chevron-down mÃªs-1" style="font-size: 0.75rem;"></i>
                    </span>
                </a>
                <div class="collapse <?= $isCrmActive ? 'show' : '' ?>" id="crmSubmenu">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?= uri_string() === 'crm/clientes' ? 'active' : '' ?>" href="<?= base_url('crm/clientes') ?>">
                                <i class="bi bi-people"></i><span>Lista de Clientes</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= uri_string() === 'crm/timeline' ? 'active' : '' ?>" href="<?= base_url('crm/timeline') ?>">
                                <i class="bi bi-clock-history"></i><span>Timeline</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= uri_string() === 'crm/interacoes' ? 'active' : '' ?>" href="<?= base_url('crm/interacoes') ?>">
                                <i class="bi bi-chat-left-text"></i><span>Interacoes</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= uri_string() === 'crm/followups' ? 'active' : '' ?>" href="<?= base_url('crm/followups') ?>">
                                <i class="bi bi-calendar-check"></i><span>Follow-ups</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= uri_string() === 'crm/pipeline' ? 'active' : '' ?>" href="<?= base_url('crm/pipeline') ?>">
                                <i class="bi bi-kanban"></i><span>Pipeline</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= uri_string() === 'crm/campanhas' ? 'active' : '' ?>" href="<?= base_url('crm/campanhas') ?>">
                                <i class="bi bi-megaphone"></i><span>Campanhas</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= uri_string() === 'crm/metricas-marketing' ? 'active' : '' ?>" href="<?= base_url('crm/metricas-marketing') ?>">
                                <i class="bi bi-bar-chart-line"></i><span>Metricas Marketing</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= uri_string() === 'crm/clientes-inativos' ? 'active' : '' ?>" href="<?= base_url('crm/clientes-inativos') ?>">
                                <i class="bi bi-persÃ£on-x"></i><span>Clientes Inativos</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <?php
            $isCentralMensagensActive = str_starts_with(uri_string(), 'central-mensagens')
                || str_starts_with(uri_string(), 'atendimento-whatsapp');
            ?>
            <li class="nav-item">
                <a class="nav-link <?= $isCentralMensagensActive ? 'active' : 'collapsed' ?>" data-bs-toggle="collapse" href="#centralMensagensSubmenu" role="button">
                    <i class="bi bi-whatsapp"></i>
                    <span class="d-flex justify-content-between w-100 align-itemÃªs-center">
                        Atendimento WhatsApp
                        <i class="bi bi-chevron-down mÃªs-1" style="font-size: 0.75rem;"></i>
                    </span>
                </a>
                <div class="collapse <?= $isCentralMensagensActive ? 'show' : '' ?>" id="centralMensagensSubmenu">
                    <ul class="nav flex-column">
                        <li class="nav-item"><a class="nav-link <?= (uri_string() === 'atendimento-whatsapp' || uri_string() === 'central-mensagens') ? 'active' : '' ?>" href="<?= base_url('atendimento-whatsapp') ?>"><i class="bi bi-inboxes"></i><span>Inbox WhatsApp</span></a></li>
                        <li class="nav-item"><a class="nav-link <?= (uri_string() === 'atendimento-whatsapp/chatbot' || uri_string() === 'central-mensagens/chatbot') ? 'active' : '' ?>" href="<?= base_url('atendimento-whatsapp/chatbot') ?>"><i class="bi bi-robot"></i><span>Chatbot / Automacao</span></a></li>
                        <li class="nav-item"><a class="nav-link <?= (uri_string() === 'atendimento-whatsapp/metricas' || uri_string() === 'central-mensagens/metricas') ? 'active' : '' ?>" href="<?= base_url('atendimento-whatsapp/metricas') ?>"><i class="bi bi-graph-up"></i><span>Metricas</span></a></li>
                        <li class="nav-item"><a class="nav-link <?= (uri_string() === 'atendimento-whatsapp/respostas-rapidas' || uri_string() === 'central-mensagens/respostas-rapidas') ? 'active' : '' ?>" href="<?= base_url('atendimento-whatsapp/respostas-rapidas') ?>"><i class="bi bi-chat-dots"></i><span>Respostas Rapidas</span></a></li>
                        <li class="nav-item"><a class="nav-link <?= (uri_string() === 'atendimento-whatsapp/fluxos' || uri_string() === 'central-mensagens/fluxos') ? 'active' : '' ?>" href="<?= base_url('atendimento-whatsapp/fluxos') ?>"><i class="bi bi-diagram-2"></i><span>Fluxos de Atendimento</span></a></li>
                        <li class="nav-item"><a class="nav-link <?= (uri_string() === 'atendimento-whatsapp/faq' || uri_string() === 'central-mensagens/faq') ? 'active' : '' ?>" href="<?= base_url('atendimento-whatsapp/faq') ?>"><i class="bi bi-question-circle"></i><span>FAQ / Base de Conhecimento</span></a></li>
                        <li class="nav-item"><a class="nav-link <?= (uri_string() === 'atendimento-whatsapp/filas' || uri_string() === 'central-mensagens/filas') ? 'active' : '' ?>" href="<?= base_url('atendimento-whatsapp/filas') ?>"><i class="bi bi-people"></i><span>Filas e Responsaveis</span></a></li>
                        <li class="nav-item"><a class="nav-link <?= (uri_string() === 'atendimento-whatsapp/configuracoes' || uri_string() === 'central-mensagens/configuracoes') ? 'active' : '' ?>" href="<?= base_url('atendimento-whatsapp/configuracoes') ?>"><i class="bi bi-sliders"></i><span>Configuracoes</span></a></li>
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


            <!-- ?? GESTÃO & RESULTADOS ???????????????????? -->
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

            <!-- ?? CONFIGURAÇÕES ???????????????????????? -->
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
                    <span class="d-flex justify-content-between w-100 align-itemÃªs-center">
                        Segurança
                        <i class="bi bi-chevron-down mÃªs-1" style="font-size: 0.75rem;"></i>
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
                            <a class="nav-link" href="<?= base_url('grupos') ?>">Níveis de AcessÃ£o</a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </li>
            <?php endif; ?>

            <?php if (session()->get('user_grupo_id') == 1 || can('configuracoes', 'visualizar')): ?>
            <li class="nav-item">
                <a class="nav-link <?= uri_string() === 'design-system' ? 'active' : '' ?>" href="<?= base_url('design-system') ?>">
                    <i class="bi bi-palette2"></i>
                    <span>Design System</span>
                </a>
            </li>
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
        <a href="<?= base_url('logout') ?>" class="btn btn-outline-danger w-100 d-flex align-itemÃªs-center justify-content-center gap-2 py-2 logout-btn" title="Sair do Sistema">
            <i class="bi bi-box-arrow-left fs-5"></i>
            <span class="logout-text">Sair do Sistema</span>
        </a>
    </div>
</nav>

<!-- Sidebar Overlay (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>
