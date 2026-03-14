<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// =====================================================
// ROTAS PÚBLICAS (sem autenticação)
// =====================================================
$routes->get('/', 'Auth::login');
$routes->get('login', 'Auth::login');
$routes->post('login', 'Auth::attemptLogin');
$routes->get('logout', 'Auth::logout');
$routes->get('esqueci-senha', 'Auth::forgotPassword');
$routes->post('esqueci-senha', 'Auth::sendResetLink');
$routes->get('redefinir-senha/(:any)', 'Auth::resetPassword/$1');
$routes->post('redefinir-senha/(:any)', 'Auth::updatePassword/$1');

// Aprovação de orçamento (link público)
$routes->get('orcamento/(:any)', 'Orcamento::visualizar/$1');
$routes->post('orcamento/aprovar/(:any)', 'Orcamento::aprovar/$1');
$routes->post('orcamento/recusar/(:any)', 'Orcamento::recusar/$1');

// =====================================================
// ROTAS PROTEGIDAS (requer autenticação + permissão RBAC)
// =====================================================
$routes->group('', ['filter' => 'auth'], function ($routes) {

    // ── Dashboard (todos os autenticados) ─────────────────────────────────
    $routes->get('dashboard',   'Admin::index', ['filter' => 'permission:dashboard:visualizar']);
    $routes->get('admin/stats', 'Admin::stats', ['filter' => 'permission:dashboard:visualizar']);

    // ── Perfil (próprio usuário) ──────────────────────────────────────────
    $routes->get('perfil',          'Perfil::index');
    $routes->post('perfil/salvar',  'Perfil::salvar');

    // ── Grupos de Acesso ──────────────────────────────────────────────────
    $routes->get('grupos',                             'Grupos::index',            ['filter' => 'permission:grupos:visualizar']);
    $routes->get('grupos/novo',                        'Grupos::create',           ['filter' => 'permission:grupos:criar']);
    $routes->post('grupos/salvar',                     'Grupos::store',            ['filter' => 'permission:grupos:criar']);
    $routes->get('grupos/editar/(:num)',               'Grupos::edit/$1',          ['filter' => 'permission:grupos:editar']);
    $routes->post('grupos/atualizar/(:num)',            'Grupos::update/$1',        ['filter' => 'permission:grupos:editar']);
    $routes->get('grupos/excluir/(:num)',               'Grupos::delete/$1',        ['filter' => 'permission:grupos:excluir']);
    $routes->get('grupos/(:num)/permissoes',           'Grupos::permissoes/$1',    ['filter' => 'permission:grupos:editar']);
    $routes->post('grupos/(:num)/permissoes/salvar',   'Grupos::salvarPermissoes/$1', ['filter' => 'permission:grupos:editar']);

    // ── Clientes ──────────────────────────────────────────────────────────
    $routes->get('clientes',                  'Clientes::index',            ['filter' => 'permission:clientes:visualizar']);
    $routes->get('clientes/novo',             'Clientes::create',           ['filter' => 'permission:clientes:criar']);
    $routes->post('clientes/salvar',          'Clientes::store',            ['filter' => 'permission:clientes:criar']);
    $routes->post('clientes/salvar_ajax',     'Clientes::salvar_ajax',      ['filter' => 'permission:clientes:criar']);
    $routes->get('clientes/editar/(:num)',    'Clientes::edit/$1',          ['filter' => 'permission:clientes:editar']);
    $routes->post('clientes/atualizar/(:num)','Clientes::update/$1',        ['filter' => 'permission:clientes:editar']);
    $routes->get('clientes/excluir/(:num)',   'Clientes::delete/$1',        ['filter' => 'permission:clientes:excluir']);
    $routes->get('clientes/visualizar/(:num)','Clientes::show/$1',          ['filter' => 'permission:clientes:visualizar']);
    $routes->get('clientes/buscar',           'Clientes::search',           ['filter' => 'permission:clientes:visualizar']);
    $routes->get('clientes/json/(:num)',      'Clientes::getJson/$1',       ['filter' => 'permission:clientes:visualizar']);
    $routes->post('clientes/importar',        'Clientes::importCsv',        ['filter' => 'permission:clientes:importar']);
    $routes->get('clientes/modelo-csv',       'Clientes::downloadCsvTemplate', ['filter' => 'permission:clientes:importar']);

    // ── Fornecedores ──────────────────────────────────────────────────────
    $routes->get('fornecedores',                  'Fornecedores::index',      ['filter' => 'permission:fornecedores:visualizar']);
    $routes->get('fornecedores/novo',             'Fornecedores::create',     ['filter' => 'permission:fornecedores:criar']);
    $routes->post('fornecedores/salvar',          'Fornecedores::store',      ['filter' => 'permission:fornecedores:criar']);
    $routes->get('fornecedores/editar/(:num)',    'Fornecedores::edit/$1',    ['filter' => 'permission:fornecedores:editar']);
    $routes->post('fornecedores/atualizar/(:num)','Fornecedores::update/$1',  ['filter' => 'permission:fornecedores:editar']);
    $routes->get('fornecedores/excluir/(:num)',   'Fornecedores::delete/$1',  ['filter' => 'permission:fornecedores:excluir']);

    // ── Funcionários ──────────────────────────────────────────────────────
    $routes->get('funcionarios',                  'Funcionarios::index',      ['filter' => 'permission:funcionarios:visualizar']);
    $routes->get('funcionarios/novo',             'Funcionarios::create',     ['filter' => 'permission:funcionarios:criar']);
    $routes->post('funcionarios/salvar',          'Funcionarios::store',      ['filter' => 'permission:funcionarios:criar']);
    $routes->get('funcionarios/editar/(:num)',    'Funcionarios::edit/$1',    ['filter' => 'permission:funcionarios:editar']);
    $routes->post('funcionarios/atualizar/(:num)','Funcionarios::update/$1',  ['filter' => 'permission:funcionarios:editar']);
    $routes->get('funcionarios/excluir/(:num)',   'Funcionarios::delete/$1',  ['filter' => 'permission:funcionarios:excluir']);

    // ── Equipamentos ──────────────────────────────────────────────────────
    $routes->get('equipamentos',                  'Equipamentos::index',      ['filter' => 'permission:equipamentos:visualizar']);
    $routes->get('equipamentos/novo',             'Equipamentos::create',     ['filter' => 'permission:equipamentos:criar']);
    $routes->post('equipamentos/salvar',          'Equipamentos::store',      ['filter' => 'permission:equipamentos:criar']);
    $routes->get('equipamentos/editar/(:num)',    'Equipamentos::edit/$1',    ['filter' => 'permission:equipamentos:editar']);
    $routes->post('equipamentos/atualizar/(:num)','Equipamentos::update/$1',  ['filter' => 'permission:equipamentos:editar']);
    $routes->post('equipamentos/deletar-foto/(:num)','Equipamentos::deleteFoto/$1',['filter' => 'permission:equipamentos:editar']);
    $routes->get('equipamentos/excluir/(:num)',   'Equipamentos::delete/$1',  ['filter' => 'permission:equipamentos:excluir']);
    $routes->get('equipamentos/visualizar/(:num)',   'Equipamentos::show/$1',  ['filter' => 'permission:equipamentos:visualizar']);
    $routes->post('equipamentos/vincular-cliente',   'Equipamentos::vincularCliente', ['filter' => 'permission:equipamentos:editar']);
    $routes->get('equipamentos/desvincular-cliente/(:num)/(:num)', 'Equipamentos::desvincularCliente/$1/$2', ['filter' => 'permission:equipamentos:editar']);
    $routes->get('equipamentos/por-cliente/(:num)','Equipamentos::byClient/$1',['filter' => 'permission:equipamentos:visualizar']);
    $routes->get('equipamentos/fotos/(:num)','Equipamentos::getFotos/$1',['filter' => 'permission:equipamentos:visualizar']);
    $routes->post('equipamentos/salvar-ajax','Equipamentos::storeAjax',['filter' => 'permission:equipamentos:criar']);

    // ── Equipamentos Tipos ────────────────────────────────────────────────
    $routes->get('equipamentostipos',              'EquipamentosTipos::index',  ['filter' => 'permission:equipamentos:visualizar']);
    $routes->post('equipamentostipos/salvar',      'EquipamentosTipos::store',  ['filter' => 'permission:equipamentos:criar']);
    $routes->get('equipamentostipos/excluir/(:num)','EquipamentosTipos::delete/$1',['filter' => 'permission:equipamentos:excluir']);

    // ── Equipamentos Marcas ───────────────────────────────────────────────
    $routes->get('equipamentosmarcas',              'EquipamentosMarcas::index',  ['filter' => 'permission:equipamentos:visualizar']);
    $routes->post('equipamentosmarcas/salvar',      'EquipamentosMarcas::store',  ['filter' => 'permission:equipamentos:criar']);
    $routes->post('equipamentosmarcas/salvar_ajax', 'EquipamentosMarcas::salvar_ajax',  ['filter' => 'permission:equipamentos:criar']);
    $routes->get('equipamentosmarcas/excluir/(:num)','EquipamentosMarcas::delete/$1',['filter' => 'permission:equipamentos:excluir']);
    $routes->post('equipamentosmarcas/importar',    'EquipamentosMarcas::importCsv',['filter' => 'permission:equipamentos:importar']);

    // ── Equipamentos Modelos ──────────────────────────────────────────────
    $routes->get('equipamentosmodelos',              'EquipamentosModelos::index',  ['filter' => 'permission:equipamentos:visualizar']);
    $routes->post('equipamentosmodelos/salvar',      'EquipamentosModelos::store',  ['filter' => 'permission:equipamentos:criar']);
    $routes->post('equipamentosmodelos/salvar_ajax', 'EquipamentosModelos::salvar_ajax',  ['filter' => 'permission:equipamentos:criar']);
    $routes->get('equipamentosmodelos/excluir/(:num)','EquipamentosModelos::delete/$1',['filter' => 'permission:equipamentos:excluir']);
    $routes->post('equipamentosmodelos/importar',    'EquipamentosModelos::importCsv',['filter' => 'permission:equipamentos:importar']);
    $routes->post('equipamentosmodelos/por-marca',   'EquipamentosModelos::porMarca', ['filter' => 'permission:equipamentos:visualizar']);
    $routes->get('api/modelos/buscar',               'ModeloBridge::buscar', ['filter' => 'permission:equipamentos:visualizar']);

    // ── Defeitos Comuns ───────────────────────────────────────────────────
    $routes->get('equipamentosdefeitos',                  'EquipamentosDefeitos::index',         ['filter' => 'permission:equipamentos:visualizar']);
    $routes->post('equipamentosdefeitos/salvar',          'EquipamentosDefeitos::store',         ['filter' => 'permission:equipamentos:criar']);
    $routes->get('equipamentosdefeitos/editar/(:num)',    'EquipamentosDefeitos::edit/$1',       ['filter' => 'permission:equipamentos:editar']);
    $routes->post('equipamentosdefeitos/atualizar/(:num)','EquipamentosDefeitos::update/$1',    ['filter' => 'permission:equipamentos:editar']);
    $routes->get('equipamentosdefeitos/excluir/(:num)',   'EquipamentosDefeitos::delete/$1',     ['filter' => 'permission:equipamentos:excluir']);
    $routes->post('equipamentosdefeitos/por-tipo',        'EquipamentosDefeitos::porTipo',       ['filter' => 'permission:equipamentos:visualizar']);
    $routes->post('equipamentosdefeitos/importar',        'EquipamentosDefeitos::importCsv',     ['filter' => 'permission:equipamentos:importar']);
    $routes->get('equipamentosdefeitos/modelo-csv',       'EquipamentosDefeitos::downloadTemplate',['filter' => 'permission:equipamentos:visualizar']);

    // ── Procedimentos de Defeitos (Base de Conhecimento) ──────────────────
    $routes->get('equipamentosdefeitos/procedimentos/(:num)', 'EquipamentosDefeitos::getProcedimentos/$1', ['filter' => 'permission:equipamentos:visualizar']);
    $routes->post('equipamentosdefeitos/procedimentos/salvar', 'EquipamentosDefeitos::salvarProcedimento', ['filter' => 'permission:equipamentos:editar']);
    $routes->post('equipamentosdefeitos/procedimentos/excluir/(:num)', 'EquipamentosDefeitos::excluirProcedimento/$1', ['filter' => 'permission:equipamentos:editar']);

    // ── Ordens de Serviço ─────────────────────────────────────────────────
    $routes->get('os',                    'Os::index',              ['filter' => 'permission:os:visualizar']);
    $routes->post('os/datatable',         'Os::datatable',          ['filter' => 'permission:os:visualizar']);
    $routes->get('os/nova',              'Os::create',             ['filter' => 'permission:os:criar']);
    $routes->post('os/salvar',            'Os::store',              ['filter' => 'permission:os:criar']);
    $routes->get('os/editar/(:num)',      'Os::edit/$1',            ['filter' => 'permission:os:editar']);
    $routes->post('os/atualizar/(:num)',  'Os::update/$1',          ['filter' => 'permission:os:editar']);
    $routes->get('os/visualizar/(:num)', 'Os::show/$1',            ['filter' => 'permission:os:visualizar']);
    $routes->post('os/status/(:num)',    'Os::updateStatus/$1',    ['filter' => 'permission:os:editar']);
    $routes->get('os/imprimir/(:num)',   'Os::print/$1',           ['filter' => 'permission:os:visualizar']);
    $routes->post('os/item/salvar',       'Os::addItem',            ['filter' => 'permission:os:editar']);
    $routes->get('os/item/excluir/(:num)','Os::removeItem/$1',     ['filter' => 'permission:os:editar']);

    // ── Serviços ──────────────────────────────────────────────────────────
    $routes->get('servicos',                  'Servicos::index',            ['filter' => 'permission:servicos:visualizar']);
    $routes->get('servicos/novo',             'Servicos::create',           ['filter' => 'permission:servicos:criar']);
    $routes->post('servicos/salvar',          'Servicos::store',            ['filter' => 'permission:servicos:criar']);
    $routes->get('servicos/editar/(:num)',    'Servicos::edit/$1',          ['filter' => 'permission:servicos:editar']);
    $routes->post('servicos/atualizar/(:num)','Servicos::update/$1',        ['filter' => 'permission:servicos:editar']);
    $routes->get('servicos/excluir/(:num)',   'Servicos::delete/$1',        ['filter' => 'permission:servicos:excluir']);
    $routes->post('servicos/encerrar/(:num)', 'Servicos::encerrar/$1',       ['filter' => 'permission:servicos:encerrar']);
    $routes->get('servicos/exportar',         'Servicos::exportCsv',        ['filter' => 'permission:servicos:exportar']);
    $routes->get('servicos/modelo-csv',       'Servicos::downloadCsvTemplate',['filter' => 'permission:servicos:importar']);
    $routes->post('servicos/importar',        'Servicos::importCsv',        ['filter' => 'permission:servicos:importar']);

    // ── Vendas ────────────────────────────────────────────────────────────
    $routes->get('vendas',                    'Vendas::index',              ['filter' => 'permission:vendas:visualizar']);

    // ── Estoque ───────────────────────────────────────────────────────────
    $routes->get('estoque',                    'Estoque::index',      ['filter' => 'permission:estoque:visualizar']);
    $routes->get('estoque/novo',              'Estoque::create',     ['filter' => 'permission:estoque:criar']);
    $routes->post('estoque/salvar',            'Estoque::store',      ['filter' => 'permission:estoque:criar']);
    $routes->get('estoque/editar/(:num)',      'Estoque::edit/$1',    ['filter' => 'permission:estoque:editar']);
    $routes->post('estoque/atualizar/(:num)',  'Estoque::update/$1',  ['filter' => 'permission:estoque:editar']);
    $routes->get('estoque/excluir/(:num)',     'Estoque::delete/$1',  ['filter' => 'permission:estoque:excluir']);
    $routes->post('estoque/movimentacao',      'Estoque::movement',   ['filter' => 'permission:estoque:editar']);
    $routes->get('estoque/exportar',           'Estoque::exportCsv',   ['filter' => 'permission:estoque:exportar']);
    $routes->get('estoque/modelo-csv',         'Estoque::downloadCsvTemplate', ['filter' => 'permission:estoque:importar']);
    $routes->post('estoque/importar',          'Estoque::importCsv',   ['filter' => 'permission:estoque:importar']);
    $routes->get('estoque/movimentacoes/(:num)','Estoque::movements/$1',['filter' => 'permission:estoque:visualizar']);
    $routes->get('estoque/buscar',             'Estoque::search',     ['filter' => 'permission:estoque:visualizar']);

    // ── Financeiro ────────────────────────────────────────────────────────
    $routes->get('financeiro',                  'Financeiro::index',    ['filter' => 'permission:financeiro:visualizar']);
    $routes->get('financeiro/novo',            'Financeiro::create',   ['filter' => 'permission:financeiro:criar']);
    $routes->post('financeiro/salvar',          'Financeiro::store',    ['filter' => 'permission:financeiro:criar']);
    $routes->get('financeiro/editar/(:num)',    'Financeiro::edit/$1',  ['filter' => 'permission:financeiro:editar']);
    $routes->post('financeiro/atualizar/(:num)','Financeiro::update/$1',['filter' => 'permission:financeiro:editar']);
    $routes->get('financeiro/excluir/(:num)',   'Financeiro::delete/$1',['filter' => 'permission:financeiro:excluir']);
    $routes->post('financeiro/baixar/(:num)',   'Financeiro::pay/$1',   ['filter' => 'permission:financeiro:editar']);

    // ── Relatórios ────────────────────────────────────────────────────────
    $routes->get('relatorios',             'Relatorios::index',      ['filter' => 'permission:relatorios:visualizar']);
    $routes->get('relatorios/os',          'Relatorios::osByPeriod', ['filter' => 'permission:relatorios:visualizar']);
    $routes->get('relatorios/financeiro',  'Relatorios::financial',  ['filter' => 'permission:relatorios:visualizar']);
    $routes->get('relatorios/estoque',     'Relatorios::stock',      ['filter' => 'permission:relatorios:visualizar']);
    $routes->get('relatorios/clientes',    'Relatorios::clients',    ['filter' => 'permission:relatorios:visualizar']);

    // ── Configurações ─────────────────────────────────────────────────────
    $routes->get('configuracoes',          'Configuracoes::index',   ['filter' => 'permission:configuracoes:visualizar']);
    $routes->post('configuracoes/salvar',  'Configuracoes::save',    ['filter' => 'permission:configuracoes:editar']);

    // ── Usuários ──────────────────────────────────────────────────────────
    $routes->get('usuarios',                  'Usuarios::index',    ['filter' => 'permission:usuarios:visualizar']);
    $routes->post('usuarios/datatable',       'Usuarios::datatable',['filter' => 'permission:usuarios:visualizar']);
    $routes->get('usuarios/novo',             'Usuarios::create',   ['filter' => 'permission:usuarios:criar']);
    $routes->post('usuarios/salvar',          'Usuarios::store',    ['filter' => 'permission:usuarios:criar']);
    $routes->get('usuarios/editar/(:num)',    'Usuarios::edit/$1',  ['filter' => 'permission:usuarios:editar']);
    $routes->post('usuarios/atualizar/(:num)','Usuarios::update/$1',['filter' => 'permission:usuarios:editar']);
    $routes->get('usuarios/excluir/(:num)',   'Usuarios::delete/$1',['filter' => 'permission:usuarios:excluir']);

    // ── Documentação (Central de Conhecimento / Wiki) ─────────────────────
    $routes->get('documentacao',          'Documentacao::index');
    $routes->get('documentacao/arquivo',  'Documentacao::arquivo');
    $routes->get('documentacao/buscar',   'Documentacao::buscar');
    $routes->get('documentacao/arvore',   'Documentacao::arvore');

    // ── Upload (apenas usuários autenticados) ─────────────────────────────
    $routes->post('upload/imagem',         'Upload::image');
    $routes->get('upload/excluir/(:num)',   'Upload::delete/$1');
});

