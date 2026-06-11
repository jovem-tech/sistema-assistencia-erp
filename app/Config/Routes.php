<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// =====================================================
// ROTAS PUBLICAS (sem autenticacao)
// =====================================================
$routes->get('site', 'Home::index');
$routes->get('apresentacao', 'Home::index');
$routes->get('/', 'Auth::login');
$routes->get('login', 'Auth::login');
$routes->post('login', 'Auth::attemptLogin');
$routes->get('logout', 'Auth::logout');
$routes->get('esqueci-senha', 'Auth::forgotPassword');
$routes->post('esqueci-senha', 'Auth::sendResetLink');
$routes->get('redefinir-senha/(:any)', 'Auth::resetPassword/$1');
$routes->post('redefinir-senha/(:any)', 'Auth::updatePassword/$1');

// Aprovacao de orcamento (link publico)
$routes->get('orcamento/(:any)', 'Orcamento::visualizar/$1');
$routes->post('orcamento/aprovar/(:any)', 'Orcamento::aprovar/$1');
$routes->post('orcamento/recusar/(:any)', 'Orcamento::recusar/$1');
// Compatibilidade legado: links antigos de pacote nao escrevem mais no fluxo antigo.
$routes->get('orcamento/pacote/(:any)', 'Orcamento::visualizarPacote/$1');
$routes->post('orcamento/pacote/escolher/(:any)', 'Orcamento::escolherPacote/$1');
// Fluxo oficial atual: oferta dinamica de pacote.
$routes->get('pacote/oferta/(:any)', 'Orcamento::visualizarOfertaPacote/$1');
$routes->post('pacote/oferta/escolher/(:any)', 'Orcamento::escolherOfertaPacote/$1');
$routes->post('webhooks/whatsapp', 'WhatsAppWebhook::receive');
$routes->get('api/public/warranty/(:segment)', 'Api\\V1\\OrdersController::showWarrantyPublic/$1');

// =====================================================
// API MOBILE/PWA (v1)
// =====================================================
$routes->group('api/v1', static function ($routes) {
    $routes->post('auth/login', 'Api\\V1\\AuthController::login');
    $routes->post('agents/bootstrap-from-warranty', 'Api\\V1\\AgentsController::bootstrapFromWarranty');

    $routes->group('', ['filter' => 'apiToken'], static function ($routes) {
        $routes->get('auth/me', 'Api\\V1\\AuthController::me');
        $routes->post('auth/refresh', 'Api\\V1\\AuthController::refresh');
        $routes->post('auth/logout', 'Api\\V1\\AuthController::logout');
        $routes->post('agents/check-in', 'Api\\V1\\AgentsController::checkIn');

        $routes->get('users', 'Api\\V1\\UsersController::index');

        $routes->get('clients', 'Api\\V1\\ClientsController::index');
        $routes->get('clients/(:num)', 'Api\\V1\\ClientsController::show/$1');
        $routes->post('clients', 'Api\\V1\\ClientsController::create');
        $routes->put('clients/(:num)', 'Api\\V1\\ClientsController::update/$1');
        $routes->patch('clients/(:num)', 'Api\\V1\\ClientsController::update/$1');

        $routes->get('equipments/catalog', 'Api\\V1\\EquipmentsController::catalog');
        $routes->post('equipments/brands', 'Api\\V1\\EquipmentsController::createBrand');
        $routes->post('equipments/models', 'Api\\V1\\EquipmentsController::createModel');
        $routes->get('equipments/(:num)', 'Api\\V1\\EquipmentsController::show/$1');
        $routes->post('equipments', 'Api\\V1\\EquipmentsController::create');
        $routes->post('equipments/(:num)', 'Api\\V1\\EquipmentsController::update/$1');
        $routes->put('equipments/(:num)', 'Api\\V1\\EquipmentsController::update/$1');
        $routes->patch('equipments/(:num)', 'Api\\V1\\EquipmentsController::update/$1');

        $routes->get('orders', 'Api\\V1\\OrdersController::index');
        $routes->get('orders/meta', 'Api\\V1\\OrdersController::meta');
        $routes->get('orders/(:num)', 'Api\\V1\\OrdersController::show/$1');
        $routes->get('orders/by-number/(:segment)', 'Api\\V1\\OrdersController::showByNumber/$1');
        $routes->post('orders', 'Api\\V1\\OrdersController::create');
        $routes->put('orders/(:num)', 'Api\\V1\\OrdersController::update/$1');
        $routes->patch('orders/(:num)', 'Api\\V1\\OrdersController::update/$1');

        $routes->get('conversations', 'Api\\V1\\ConversationsController::index');
        $routes->get('conversations/(:num)', 'Api\\V1\\ConversationsController::show/$1');

        $routes->get('messages', 'Api\\V1\\MessagesController::index');
        $routes->post('messages', 'Api\\V1\\MessagesController::create');

        $routes->get('notifications', 'Api\\V1\\NotificationsController::index');
        $routes->post('notifications', 'Api\\V1\\NotificationsController::create');
        $routes->put('notifications/(:num)/read', 'Api\\V1\\NotificationsController::markAsRead/$1');
        $routes->patch('notifications/(:num)/read', 'Api\\V1\\NotificationsController::markAsRead/$1');
        $routes->put('notifications/read-all', 'Api\\V1\\NotificationsController::markAllRead');
        $routes->patch('notifications/read-all', 'Api\\V1\\NotificationsController::markAllRead');

        $routes->get('notifications/subscriptions', 'Api\\V1\\PushSubscriptionsController::index');
        $routes->post('notifications/subscriptions', 'Api\\V1\\PushSubscriptionsController::create');
        $routes->delete('notifications/subscriptions/(:num)', 'Api\\V1\\PushSubscriptionsController::delete/$1');

        $routes->get('realtime/stream', 'Api\\V1\\RealtimeController::stream');
    });
});

// =====================================================
// ROTAS PROTEGIDAS (requer autenticacao + permissao RBAC)
// =====================================================
$routes->group('', ['filter' => 'auth'], function ($routes) {
// -- Dashboard (todos os autenticados) -----------------------------------
    $routes->get('sessao/heartbeat', 'Sessao::heartbeat');
$routes->get('notificacoes/navbar-feed', 'Notificacoes::navbarFeed');
$routes->get('notificacoes/stream', 'Notificacoes::stream');
$routes->post('notificacoes/lida/(:num)', 'Notificacoes::markAsRead/$1');
$routes->post('notificacoes/lidas', 'Notificacoes::markAllRead');
$routes->post('notificacoes/limpar-lidas', 'Notificacoes::clearRead');
    $routes->get('dashboard',   'Admin::index', ['filter' => 'permission:dashboard:visualizar']);
    $routes->get('admin/stats', 'Admin::stats', ['filter' => 'permission:dashboard:visualizar']);
    // -- Perfil (proprio usuario) -----------------------------------------
    $routes->get('perfil',          'Perfil::index');
    $routes->post('perfil/salvar',  'Perfil::salvar');
    // -- Grupos de Acesso --------------------------------------------------
    $routes->get('grupos',                             'Grupos::index',            ['filter' => 'permission:grupos:visualizar']);
    $routes->get('grupos/novo',                        'Grupos::create',           ['filter' => 'permission:grupos:criar']);
    $routes->post('grupos/salvar',                     'Grupos::store',            ['filter' => 'permission:grupos:criar']);
    $routes->get('grupos/editar/(:num)',               'Grupos::edit/$1',          ['filter' => 'permission:grupos:editar']);
    $routes->post('grupos/atualizar/(:num)',            'Grupos::update/$1',        ['filter' => 'permission:grupos:editar']);
    $routes->get('grupos/excluir/(:num)',               'Grupos::delete/$1',        ['filter' => 'permission:grupos:excluir']);
    $routes->get('grupos/(:num)/permissoes',           'Grupos::permissoes/$1',    ['filter' => 'permission:grupos:editar']);
    $routes->post('grupos/(:num)/permissoes/salvar',   'Grupos::salvarPermissoes/$1', ['filter' => 'permission:grupos:editar']);

    // -- Clientes ----------------------------------------------------------
    $routes->get('clientes',                  'Clientes::index',            ['filter' => 'permission:clientes:visualizar']);
    $routes->get('clientes/novo',             'Clientes::create',           ['filter' => 'permission:clientes:criar']);
    $routes->post('clientes/salvar',          'Clientes::store',            ['filter' => 'permission:clientes:criar']);
    $routes->post('clientes/salvar_ajax',     'Clientes::salvar_ajax',      ['filter' => 'permission:clientes:criar']);
    $routes->post('clientes/atualizar_ajax/(:num)', 'Clientes::atualizar_ajax/$1', ['filter' => 'permission:clientes:editar']);
    $routes->get('clientes/editar/(:num)',    'Clientes::edit/$1',          ['filter' => 'permission:clientes:editar']);
    $routes->post('clientes/atualizar/(:num)','Clientes::update/$1',        ['filter' => 'permission:clientes:editar']);
    $routes->get('clientes/excluir/(:num)',   'Clientes::delete/$1',        ['filter' => 'permission:clientes:excluir']);
    $routes->get('clientes/visualizar/(:num)','Clientes::show/$1',          ['filter' => 'permission:clientes:visualizar']);
    $routes->get('clientes/buscar',           'Clientes::search',           ['filter' => 'permission:clientes:visualizar']);
    $routes->get('clientes/consultar-cnpj',   'Clientes::consultarCnpj',    ['filter' => 'permission:clientes:visualizar']);
    $routes->get('clientes/json/(:num)',      'Clientes::getJson/$1',       ['filter' => 'permission:clientes:visualizar']);
    $routes->get('clientes/json-edicao/(:num)', 'Clientes::getJson/$1',     ['filter' => 'permission:clientes:editar']);
    $routes->post('clientes/importar',        'Clientes::importCsv',        ['filter' => 'permission:clientes:importar']);
    $routes->get('clientes/modelo-csv',       'Clientes::downloadCsvTemplate', ['filter' => 'permission:clientes:importar']);

    // Contatos (agenda telefonica / pre-cliente)
    $routes->get('contatos',                  'Contatos::index',            ['filter' => 'permission:clientes:visualizar']);
    $routes->get('contatos/novo',             'Contatos::create',           ['filter' => 'permission:clientes:criar']);
    $routes->post('contatos/salvar',          'Contatos::store',            ['filter' => 'permission:clientes:criar']);
    $routes->get('contatos/editar/(:num)',    'Contatos::edit/$1',          ['filter' => 'permission:clientes:editar']);
    $routes->post('contatos/atualizar/(:num)','Contatos::update/$1',        ['filter' => 'permission:clientes:editar']);
    $routes->get('contatos/excluir/(:num)',   'Contatos::delete/$1',        ['filter' => 'permission:clientes:excluir']);

    // -- Fornecedores ------------------------------------------------------
    $routes->get('fornecedores',                  'Fornecedores::index',      ['filter' => 'permission:fornecedores:visualizar']);
    $routes->get('fornecedores/novo',             'Fornecedores::create',     ['filter' => 'permission:fornecedores:criar']);
    $routes->post('fornecedores/salvar',          'Fornecedores::store',      ['filter' => 'permission:fornecedores:criar']);
    $routes->get('fornecedores/consultar-cnpj',   'Fornecedores::consultarCnpj', ['filter' => 'permission:fornecedores:visualizar']);
    $routes->get('fornecedores/editar/(:num)',    'Fornecedores::edit/$1',    ['filter' => 'permission:fornecedores:editar']);
    $routes->post('fornecedores/atualizar/(:num)','Fornecedores::update/$1',  ['filter' => 'permission:fornecedores:editar']);
    $routes->get('fornecedores/excluir/(:num)',   'Fornecedores::delete/$1',  ['filter' => 'permission:fornecedores:excluir']);

    // -- FuncionÃ¡rios ------------------------------------------------------
    $routes->get('funcionarios',                  'Funcionarios::index',      ['filter' => 'permission:funcionarios:visualizar']);
    $routes->get('funcionarios/novo',             'Funcionarios::create',     ['filter' => 'permission:funcionarios:criar']);
    $routes->post('funcionarios/salvar',          'Funcionarios::store',      ['filter' => 'permission:funcionarios:criar']);
    $routes->get('funcionarios/editar/(:num)',    'Funcionarios::edit/$1',    ['filter' => 'permission:funcionarios:editar']);
    $routes->post('funcionarios/atualizar/(:num)','Funcionarios::update/$1',  ['filter' => 'permission:funcionarios:editar']);
    $routes->get('funcionarios/excluir/(:num)',   'Funcionarios::delete/$1',  ['filter' => 'permission:funcionarios:excluir']);

    // -- Equipamentos ------------------------------------------------------
    $routes->get('equipamentos',                  'Equipamentos::index',      ['filter' => 'permission:equipamentos:visualizar']);
    $routes->get('equipamentos/novo',             'Equipamentos::create',     ['filter' => 'permission:equipamentos:criar']);
    $routes->post('equipamentos/salvar',          'Equipamentos::store',      ['filter' => 'permission:equipamentos:criar']);
    $routes->get('equipamentos/editar/(:num)',    'Equipamentos::edit/$1',    ['filter' => 'permission:equipamentos:editar']);
    $routes->post('equipamentos/atualizar/(:num)','Equipamentos::update/$1',  ['filter' => 'permission:equipamentos:editar']);
    $routes->post('equipamentos/deletar-foto/(:num)','Equipamentos::deleteFoto/$1',['filter' => 'permission:equipamentos:editar']);
    $routes->post('equipamentos/foto-principal/(:num)','Equipamentos::setFotoPrincipal/$1',['filter' => 'permission:equipamentos:editar']);
    $routes->post('equipamentos/encerrar/(:num)', 'Equipamentos::encerrar/$1', ['filter' => 'permission:equipamentos:encerrar']);
    $routes->post('equipamentos/reativar/(:num)', 'Equipamentos::reativar/$1', ['filter' => 'permission:equipamentos:encerrar']);
    $routes->get('equipamentos/excluir/(:num)',   'Equipamentos::delete/$1',  ['filter' => 'permission:equipamentos:excluir']);
    $routes->get('equipamentos/visualizar/(:num)',   'Equipamentos::show/$1',  ['filter' => 'permission:equipamentos:visualizar']);
    $routes->post('equipamentos/vincular-cliente',   'Equipamentos::vincularCliente', ['filter' => 'permission:equipamentos:editar']);
    $routes->post('equipamentos/vincular-existente-ajax', 'Equipamentos::vincularExistenteAjax', ['filter' => 'permission:equipamentos:criar']);
    $routes->get('equipamentos/desvincular-cliente/(:num)/(:num)', 'Equipamentos::desvincularCliente/$1/$2', ['filter' => 'permission:equipamentos:editar']);
    $routes->get('equipamentos/por-cliente/(:num)','Equipamentos::byClient/$1',['filter' => 'permission:equipamentos:visualizar']);
    $routes->get('equipamentos/fotos/(:num)','Equipamentos::getFotos/$1',['filter' => 'permission:equipamentos:visualizar']);
    $routes->post('equipamentos/salvar-ajax','Equipamentos::storeAjax',['filter' => 'permission:equipamentos:criar']);
    $routes->post('equipamentos/atualizar-ajax/(:num)','Equipamentos::updateAjax/$1',['filter' => 'permission:equipamentos:editar']);
    $routes->get('equipamentos/bench-collector/snapshot-local', 'Equipamentos::benchCollectorSnapshotLocal', ['filter' => 'permission:equipamentos:editar']);
    $routes->get('equipamentos/bench-collector/coletar-local', 'Equipamentos::benchCollectorCollectLocal', ['filter' => 'permission:equipamentos:editar']);

    // -- Equipamentos Tipos ------------------------------------------------
    $routes->get('equipamentostipos',              'EquipamentosTipos::index',  ['filter' => 'permission:equipamentos:visualizar']);
    $routes->post('equipamentostipos/salvar',      'EquipamentosTipos::store',  ['filter' => 'permission:equipamentos:criar']);
    $routes->get('equipamentostipos/excluir/(:num)','EquipamentosTipos::delete/$1',['filter' => 'permission:equipamentos:excluir']);

    // -- Equipamentos Marcas -----------------------------------------------
    $routes->get('equipamentosmarcas',              'EquipamentosMarcas::index',  ['filter' => 'permission:equipamentos:visualizar']);
    $routes->post('equipamentosmarcas/salvar',      'EquipamentosMarcas::store',  ['filter' => 'permission:equipamentos:criar']);
    $routes->post('equipamentosmarcas/salvar_ajax', 'EquipamentosMarcas::salvar_ajax',  ['filter' => 'permission:equipamentos:criar']);
    $routes->post('equipamentosmarcas/atualizar_ajax/(:num)', 'EquipamentosMarcas::atualizar_ajax/$1', ['filter' => 'permission:equipamentos:editar']);
    $routes->get('equipamentosmarcas/excluir/(:num)','EquipamentosMarcas::delete/$1',['filter' => 'permission:equipamentos:excluir']);
    $routes->post('equipamentosmarcas/importar',    'EquipamentosMarcas::importCsv',['filter' => 'permission:equipamentos:importar']);

    // -- Equipamentos Modelos ----------------------------------------------
    $routes->get('equipamentosmodelos',              'EquipamentosModelos::index',  ['filter' => 'permission:equipamentos:visualizar']);
    $routes->post('equipamentosmodelos/salvar',      'EquipamentosModelos::store',  ['filter' => 'permission:equipamentos:criar']);
    $routes->post('equipamentosmodelos/salvar_ajax', 'EquipamentosModelos::salvar_ajax',  ['filter' => 'permission:equipamentos:criar']);
    $routes->post('equipamentosmodelos/atualizar_ajax/(:num)', 'EquipamentosModelos::atualizar_ajax/$1', ['filter' => 'permission:equipamentos:editar']);
    $routes->get('equipamentosmodelos/excluir/(:num)','EquipamentosModelos::delete/$1',['filter' => 'permission:equipamentos:excluir']);
    $routes->post('equipamentosmodelos/importar',    'EquipamentosModelos::importCsv',['filter' => 'permission:equipamentos:importar']);
    $routes->get('equipamentosmodelos/por-marca',    'EquipamentosModelos::porMarca', ['filter' => 'permission:equipamentos:visualizar']);
    $routes->post('equipamentosmodelos/por-marca',   'EquipamentosModelos::porMarca', ['filter' => 'permission:equipamentos:visualizar']);
    $routes->get('api/modelos/buscar',               'ModeloBridge::buscar', ['filter' => 'permission:equipamentos:visualizar']);

    // -- Defeitos Comuns ---------------------------------------------------
    $routes->get('equipamentosdefeitos',                  'EquipamentosDefeitos::index',         ['filter' => 'permission:equipamentos:visualizar']);
    $routes->post('equipamentosdefeitos/salvar',          'EquipamentosDefeitos::store',         ['filter' => 'permission:equipamentos:criar']);
    $routes->get('equipamentosdefeitos/editar/(:num)',    'EquipamentosDefeitos::edit/$1',       ['filter' => 'permission:equipamentos:editar']);
    $routes->post('equipamentosdefeitos/atualizar/(:num)','EquipamentosDefeitos::update/$1',    ['filter' => 'permission:equipamentos:editar']);
    $routes->get('equipamentosdefeitos/excluir/(:num)',   'EquipamentosDefeitos::delete/$1',     ['filter' => 'permission:equipamentos:excluir']);
    $routes->post('equipamentosdefeitos/por-tipo',        'EquipamentosDefeitos::porTipo',       ['filter' => 'permission:equipamentos:visualizar']);
    $routes->post('equipamentosdefeitos/importar',        'EquipamentosDefeitos::importCsv',     ['filter' => 'permission:equipamentos:importar']);
    $routes->get('equipamentosdefeitos/modelo-csv',       'EquipamentosDefeitos::downloadTemplate',['filter' => 'permission:equipamentos:visualizar']);

    // -- Procedimentos de Defeitos (Base de Conhecimento) ------------------
    $routes->get('equipamentosdefeitos/procedimentos/(:num)', 'EquipamentosDefeitos::getProcedimentos/$1', ['filter' => 'permission:equipamentos:visualizar']);
    $routes->post('equipamentosdefeitos/procedimentos/salvar', 'EquipamentosDefeitos::salvarProcedimento', ['filter' => 'permission:equipamentos:editar']);
    $routes->post('equipamentosdefeitos/procedimentos/excluir/(:num)', 'EquipamentosDefeitos::excluirProcedimento/$1', ['filter' => 'permission:equipamentos:editar']);

    // Defeitos Relatados (base de relatos do cliente)
    $routes->get('defeitosrelatados',                   'DefeitosRelatados::index',            ['filter' => 'permission:defeitos:visualizar']);
    $routes->get('defeitosrelatados/novo',              'DefeitosRelatados::create',           ['filter' => 'permission:defeitos:criar']);
    $routes->post('defeitosrelatados/salvar',           'DefeitosRelatados::store',            ['filter' => 'permission:defeitos:criar']);
    $routes->get('defeitosrelatados/editar/(:num)',     'DefeitosRelatados::edit/$1',          ['filter' => 'permission:defeitos:editar']);
    $routes->post('defeitosrelatados/atualizar/(:num)', 'DefeitosRelatados::update/$1',        ['filter' => 'permission:defeitos:editar']);
    $routes->post('defeitosrelatados/status/(:num)',    'DefeitosRelatados::toggleStatus/$1',  ['filter' => 'permission:defeitos:editar']);
    $routes->get('defeitosrelatados/excluir/(:num)',    'DefeitosRelatados::delete/$1',        ['filter' => 'permission:defeitos:excluir']);

    // CRM + Central de Mensagens
    $routes->get('crm/clientes',                 'Crm::clientes',               ['filter' => 'permission:crm:visualizar']);
    $routes->get('crm/timeline',                 'Crm::timeline',               ['filter' => 'permission:crm:visualizar']);
    $routes->get('crm/interacoes',               'Crm::interacoes',             ['filter' => 'permission:crm:visualizar']);
    $routes->post('crm/interacoes/salvar',       'Crm::salvarInteracao',        ['filter' => 'permission:crm:criar']);
    $routes->get('crm/followups',                'Crm::followups',              ['filter' => 'permission:crm:visualizar']);
    $routes->post('crm/followups/salvar',        'Crm::salvarFollowup',         ['filter' => 'permission:crm:criar']);
    $routes->post('crm/followups/(:num)/status', 'Crm::atualizarFollowupStatus/$1', ['filter' => 'permission:crm:editar']);
    $routes->get('crm/pipeline',                 'Crm::pipeline',               ['filter' => 'permission:crm:visualizar']);
    $routes->get('crm/campanhas',                'Crm::campanhas',              ['filter' => 'permission:crm:visualizar']);
    $routes->get('crm/clientes-inativos',        'Crm::clientesInativos',       ['filter' => 'permission:crm:visualizar']);
    $routes->post('crm/clientes-inativos/followup', 'Crm::criarFollowupInativo', ['filter' => 'permission:crm:criar']);
    $routes->get('crm/metricas-marketing',       'Crm::metricasMarketing',      ['filter' => 'permission:crm:visualizar']);
    $routes->post('crm/metricas-marketing/engajamento', 'Crm::salvarEngajamentoPeriodos', ['filter' => 'permission:crm:editar']);

    // Central de Atendimento WhatsApp (rota canonica + alias legado)
    $routes->get('atendimento-mobile',                      'AtendimentoMobile::index',                ['filter' => 'permission:atendimento_whatsapp:visualizar']);
    $routes->get('atendimento-whatsapp',                    'CentralMensagens::index',                       ['filter' => 'permission:atendimento_whatsapp:visualizar']);
    $routes->get('atendimento-whatsapp/conversas',          'CentralMensagens::conversas',                   ['filter' => 'permission:atendimento_whatsapp:visualizar']);
    $routes->get('atendimento-whatsapp/conversas/stream',   'CentralMensagens::conversasStream',             ['filter' => 'permission:atendimento_whatsapp:visualizar']);
    $routes->get('atendimento-whatsapp/conversa/(:num)',    'CentralMensagens::conversa/$1',                 ['filter' => 'permission:atendimento_whatsapp:visualizar']);
$routes->get('atendimento-whatsapp/conversa/(:num)/novas', 'CentralMensagens::conversaNovas/$1',         ['filter' => 'permission:atendimento_whatsapp:visualizar']);
$routes->get('atendimento-whatsapp/conversa/(:num)/stream', 'CentralMensagens::conversaStream/$1',       ['filter' => 'permission:atendimento_whatsapp:visualizar']);
$routes->post('atendimento-whatsapp/conversa/(:num)/cadastrar-contato', 'CentralMensagens::cadastrarContatoConversa/$1', ['filter' => 'permission:atendimento_whatsapp:editar']);
$routes->post('atendimento-whatsapp/conversa/(:num)/cadastrar-cliente', 'CentralMensagens::cadastrarContatoConversa/$1', ['filter' => 'permission:atendimento_whatsapp:editar']);
$routes->post('atendimento-whatsapp/enviar',            'CentralMensagens::enviar',                      ['filter' => 'permission:atendimento_whatsapp:editar']);
$routes->post('atendimento-whatsapp/vincular-os',       'CentralMensagens::vincularOs',                  ['filter' => 'permission:atendimento_whatsapp:editar']);
$routes->post('atendimento-whatsapp/atualizar-meta',    'CentralMensagens::atualizarMeta',               ['filter' => 'permission:atendimento_whatsapp:editar']);
$routes->post('atendimento-whatsapp/sync-inbound',      'CentralMensagens::syncInbound',                 ['filter' => 'permission:atendimento_whatsapp:editar']);
    $routes->get('atendimento-whatsapp/chatbot',            'CentralMensagens::chatbot',                     ['filter' => 'permission:atendimento_whatsapp:visualizar']);
    $routes->post('atendimento-whatsapp/chatbot/intencao/salvar', 'CentralMensagens::salvarIntencao',        ['filter' => 'permission:atendimento_whatsapp:editar']);
    $routes->post('atendimento-whatsapp/chatbot/intencao/toggle/(:num)', 'CentralMensagens::toggleIntencao/$1', ['filter' => 'permission:atendimento_whatsapp:editar']);
    $routes->post('atendimento-whatsapp/chatbot/intencao/deletar/(:num)', 'CentralMensagens::deletarIntencao/$1', ['filter' => 'permission:atendimento_whatsapp:editar']);
    $routes->post('atendimento-whatsapp/chatbot/regra/salvar', 'CentralMensagens::salvarRegraErp',          ['filter' => 'permission:atendimento_whatsapp:editar']);
    $routes->post('atendimento-whatsapp/chatbot/regra/toggle/(:num)', 'CentralMensagens::toggleRegraErp/$1', ['filter' => 'permission:atendimento_whatsapp:editar']);
    $routes->post('atendimento-whatsapp/chatbot/regra/deletar/(:num)', 'CentralMensagens::deletarRegraErp/$1', ['filter' => 'permission:atendimento_whatsapp:editar']);
    $routes->get('atendimento-whatsapp/faq',                'CentralMensagens::faq',                         ['filter' => 'permission:atendimento_whatsapp:visualizar']);
    $routes->post('atendimento-whatsapp/faq/salvar',        'CentralMensagens::salvarFaq',                   ['filter' => 'permission:atendimento_whatsapp:editar']);
    $routes->post('atendimento-whatsapp/faq/toggle/(:num)', 'CentralMensagens::toggleFaq/$1',                ['filter' => 'permission:atendimento_whatsapp:editar']);
    $routes->get('atendimento-whatsapp/respostas-rapidas',  'CentralMensagens::respostasRapidas',            ['filter' => 'permission:atendimento_whatsapp:visualizar']);
    $routes->post('atendimento-whatsapp/respostas-rapidas/salvar', 'CentralMensagens::salvarRespostaRapida', ['filter' => 'permission:atendimento_whatsapp:editar']);
    $routes->post('atendimento-whatsapp/respostas-rapidas/toggle/(:num)', 'CentralMensagens::toggleRespostaRapida/$1', ['filter' => 'permission:atendimento_whatsapp:editar']);
    $routes->get('atendimento-whatsapp/fluxos',             'CentralMensagens::fluxos',                      ['filter' => 'permission:atendimento_whatsapp:visualizar']);
    $routes->post('atendimento-whatsapp/fluxos/salvar',     'CentralMensagens::salvarFluxo',                 ['filter' => 'permission:atendimento_whatsapp:editar']);
    $routes->post('atendimento-whatsapp/fluxos/toggle/(:num)', 'CentralMensagens::toggleFluxo/$1',           ['filter' => 'permission:atendimento_whatsapp:editar']);
    $routes->get('atendimento-whatsapp/filas',              'CentralMensagens::filas',                       ['filter' => 'permission:atendimento_whatsapp:visualizar']);
    $routes->post('atendimento-whatsapp/filas/atualizar',   'CentralMensagens::atualizarFila',               ['filter' => 'permission:atendimento_whatsapp:editar']);
    $routes->get('atendimento-whatsapp/metricas',           'CentralMensagens::metricas',                    ['filter' => 'permission:atendimento_whatsapp:visualizar']);
    $routes->post('atendimento-whatsapp/metricas/consolidar-diario', 'CentralMensagens::consolidarMetricasDiarias', ['filter' => 'permission:atendimento_whatsapp:editar']);
    $routes->get('atendimento-whatsapp/configuracoes',      'CentralMensagens::configuracoes',               ['filter' => 'permission:atendimento_whatsapp:visualizar']);
    $routes->post('atendimento-whatsapp/configuracoes/salvar', 'CentralMensagens::salvarConfiguracoes',      ['filter' => 'permission:atendimento_whatsapp:editar']);

    // Alias legado para compatibilidade interna/links antigos
    $routes->get('central-mensagens',                       'CentralMensagens::index',                       ['filter' => 'permission:atendimento_whatsapp:visualizar']);
    $routes->get('central-mensagens/conversas',             'CentralMensagens::conversas',                   ['filter' => 'permission:atendimento_whatsapp:visualizar']);
    $routes->get('central-mensagens/conversas/stream',      'CentralMensagens::conversasStream',             ['filter' => 'permission:atendimento_whatsapp:visualizar']);
    $routes->get('central-mensagens/conversa/(:num)',       'CentralMensagens::conversa/$1',                 ['filter' => 'permission:atendimento_whatsapp:visualizar']);
$routes->get('central-mensagens/conversa/(:num)/novas', 'CentralMensagens::conversaNovas/$1',            ['filter' => 'permission:atendimento_whatsapp:visualizar']);
$routes->get('central-mensagens/conversa/(:num)/stream', 'CentralMensagens::conversaStream/$1',          ['filter' => 'permission:atendimento_whatsapp:visualizar']);
$routes->post('central-mensagens/conversa/(:num)/cadastrar-contato', 'CentralMensagens::cadastrarContatoConversa/$1', ['filter' => 'permission:atendimento_whatsapp:editar']);
$routes->post('central-mensagens/conversa/(:num)/cadastrar-cliente', 'CentralMensagens::cadastrarContatoConversa/$1', ['filter' => 'permission:atendimento_whatsapp:editar']);
$routes->post('central-mensagens/enviar',               'CentralMensagens::enviar',                      ['filter' => 'permission:atendimento_whatsapp:editar']);
$routes->post('central-mensagens/vincular-os',          'CentralMensagens::vincularOs',                  ['filter' => 'permission:atendimento_whatsapp:editar']);
$routes->post('central-mensagens/atualizar-meta',       'CentralMensagens::atualizarMeta',               ['filter' => 'permission:atendimento_whatsapp:editar']);
    $routes->post('central-mensagens/sync-inbound',         'CentralMensagens::syncInbound',                 ['filter' => 'permission:atendimento_whatsapp:editar']);
    $routes->get('central-mensagens/chatbot',               'CentralMensagens::chatbot',                     ['filter' => 'permission:atendimento_whatsapp:visualizar']);
    $routes->post('central-mensagens/chatbot/intencao/salvar', 'CentralMensagens::salvarIntencao',           ['filter' => 'permission:atendimento_whatsapp:editar']);
    $routes->post('central-mensagens/chatbot/intencao/toggle/(:num)', 'CentralMensagens::toggleIntencao/$1', ['filter' => 'permission:atendimento_whatsapp:editar']);
    $routes->post('central-mensagens/chatbot/intencao/deletar/(:num)', 'CentralMensagens::deletarIntencao/$1', ['filter' => 'permission:atendimento_whatsapp:editar']);
    $routes->post('central-mensagens/chatbot/regra/salvar', 'CentralMensagens::salvarRegraErp',             ['filter' => 'permission:atendimento_whatsapp:editar']);
    $routes->post('central-mensagens/chatbot/regra/toggle/(:num)', 'CentralMensagens::toggleRegraErp/$1',   ['filter' => 'permission:atendimento_whatsapp:editar']);
    $routes->post('central-mensagens/chatbot/regra/deletar/(:num)', 'CentralMensagens::deletarRegraErp/$1', ['filter' => 'permission:atendimento_whatsapp:editar']);
    $routes->get('central-mensagens/faq',                   'CentralMensagens::faq',                         ['filter' => 'permission:atendimento_whatsapp:visualizar']);
    $routes->post('central-mensagens/faq/salvar',           'CentralMensagens::salvarFaq',                   ['filter' => 'permission:atendimento_whatsapp:editar']);
    $routes->post('central-mensagens/faq/toggle/(:num)',    'CentralMensagens::toggleFaq/$1',                ['filter' => 'permission:atendimento_whatsapp:editar']);
    $routes->get('central-mensagens/respostas-rapidas',     'CentralMensagens::respostasRapidas',            ['filter' => 'permission:atendimento_whatsapp:visualizar']);
    $routes->post('central-mensagens/respostas-rapidas/salvar', 'CentralMensagens::salvarRespostaRapida',    ['filter' => 'permission:atendimento_whatsapp:editar']);
    $routes->post('central-mensagens/respostas-rapidas/toggle/(:num)', 'CentralMensagens::toggleRespostaRapida/$1', ['filter' => 'permission:atendimento_whatsapp:editar']);
    $routes->get('central-mensagens/fluxos',                'CentralMensagens::fluxos',                      ['filter' => 'permission:atendimento_whatsapp:visualizar']);
    $routes->post('central-mensagens/fluxos/salvar',        'CentralMensagens::salvarFluxo',                 ['filter' => 'permission:atendimento_whatsapp:editar']);
    $routes->post('central-mensagens/fluxos/toggle/(:num)', 'CentralMensagens::toggleFluxo/$1',              ['filter' => 'permission:atendimento_whatsapp:editar']);
    $routes->get('central-mensagens/filas',                 'CentralMensagens::filas',                       ['filter' => 'permission:atendimento_whatsapp:visualizar']);
    $routes->post('central-mensagens/filas/atualizar',      'CentralMensagens::atualizarFila',               ['filter' => 'permission:atendimento_whatsapp:editar']);
    $routes->get('central-mensagens/metricas',              'CentralMensagens::metricas',                    ['filter' => 'permission:atendimento_whatsapp:visualizar']);
    $routes->post('central-mensagens/metricas/consolidar-diario', 'CentralMensagens::consolidarMetricasDiarias', ['filter' => 'permission:atendimento_whatsapp:editar']);
    $routes->get('central-mensagens/configuracoes',         'CentralMensagens::configuracoes',               ['filter' => 'permission:atendimento_whatsapp:visualizar']);
    $routes->post('central-mensagens/configuracoes/salvar', 'CentralMensagens::salvarConfiguracoes',         ['filter' => 'permission:atendimento_whatsapp:editar']);

    // -- Ordens de ServiÃ§o -------------------------------------------------
    $routes->get('os',                    'Os::index',              ['filter' => 'permission:os:visualizar']);
    $routes->post('os/datatable',         'Os::datatable',          ['filter' => 'permission:os:visualizar']);
    $routes->get('os/fotos/(:num)',      'Os::photos/$1',          ['filter' => 'permission:os:visualizar']);
    $routes->post('os/fotos-entrada/excluir/(:num)', 'Os::deleteEntryPhoto/$1', ['filter' => 'permission:os:editar']);
    $routes->get('os/nova',              'Os::create',             ['filter' => 'permission:os:criar']);
    $routes->get('os/checklist-meta',    'Os::checklistMeta',      ['filter' => 'permission:os:visualizar']);
    $routes->post('os/salvar',            'Os::store',              ['filter' => 'permission:os:criar']);
    $routes->get('os/editar/(:num)',      'Os::edit/$1',            ['filter' => 'permission:os:editar']);
    $routes->post('os/atualizar/(:num)',  'Os::update/$1',          ['filter' => 'permission:os:editar']);
    $routes->get('os/visualizar/(:num)', 'Os::show/$1',            ['filter' => 'permission:os:visualizar']);
    $routes->get('os/status-meta/(:num)', 'Os::statusMeta/$1',      ['filter' => 'permission:os:visualizar']);
    $routes->get('os/encerramento-meta/(:num)', 'Os::encerramentoMeta/$1', ['filter' => 'permission:os:visualizar']);
    $routes->get('os/prazos-meta/(:num)', 'Os::datesMeta/$1',       ['filter' => 'permission:os:editar']);
    $routes->post('os/prazos-ajax/(:num)', 'Os::updateDatesAjax/$1',['filter' => 'permission:os:editar']);
    $routes->get('os/orcamento-meta/(:num)', 'Os::budgetMeta/$1',   ['filter' => 'permission:os:editar']);
    $routes->get('os/whatsapp-meta/(:num)', 'Os::whatsappMeta/$1',  ['filter' => 'permission:os:editar']);
    $routes->get('os/orcamento-resumo/(:num)', 'Os::budgetSummary/$1', ['filter' => 'permission:os:editar']);
    $routes->post('os/orcamento-ajax/(:num)', 'Os::budgetAjax/$1',  ['filter' => 'permission:os:editar']);
    $routes->post('os/status-ajax/(:num)','Os::updateStatusAjax/$1',['filter' => 'permission:os:editar']);
    $routes->post('os/encerrar-ajax/(:num)', 'Os::encerrarAjax/$1', ['filter' => 'permission:os:encerrar']);
    $routes->post('os/status/(:num)',    'Os::updateStatus/$1',    ['filter' => 'permission:os:editar']);
    $routes->get('os/imprimir/(:num)',   'Os::print/$1',           ['filter' => 'permission:os:visualizar']);
    $routes->post('os/whatsapp/(:num)',  'Os::sendWhatsApp/$1',    ['filter' => 'permission:os:editar']);
    $routes->post('os/email/(:num)/enviar', 'Os::sendEmail/$1',    ['filter' => 'permission:os:editar']);
    $routes->post('os/pdf/(:num)/gerar', 'Os::generatePdf/$1',     ['filter' => 'permission:os:visualizar']);
    $routes->post('os/item/salvar',       'Os::addItem',            ['filter' => 'permission:os:editar']);
    $routes->get('os/item/catalogo',      'Os::itemCatalogSearch',  ['filter' => 'permission:os:editar']);
    $routes->post('os/item/resolver-pendencia/(:num)', 'Os::resolveItemPendencia/$1', ['filter' => 'permission:os:editar']);
    $routes->get('os/item/excluir/(:num)','Os::removeItem/$1',     ['filter' => 'permission:os:editar']);
    $routes->get('osworkflow',            'OsWorkflow::index',      ['filter' => 'permission:os:editar']);
    $routes->post('osworkflow/salvar',    'OsWorkflow::save',       ['filter' => 'permission:os:editar']);
    $routes->get('checklists/entrada',                        'Checklists::entrada',            ['filter' => 'permission:os:visualizar']);
    $routes->post('checklists/entrada/salvar',                'Checklists::salvarEntrada',      ['filter' => 'permission:os:editar']);
    $routes->post('checklists/entrada/item/salvar',           'Checklists::salvarItemEntrada',  ['filter' => 'permission:os:editar']);
    $routes->post('checklists/entrada/item/remover/(:num)',   'Checklists::removerItemEntrada/$1', ['filter' => 'permission:os:editar']);
    $routes->get('checklists/manutencao',                     'Checklists::manutencao',         ['filter' => 'permission:os:visualizar']);
    $routes->get('checklists/controle-qualidade',             'Checklists::controleQualidade',  ['filter' => 'permission:os:visualizar']);
    $routes->get('checklists/saida',                          'Checklists::saida',              ['filter' => 'permission:os:visualizar']);
    $routes->get('conhecimento/modelos-pdf',                  'ConhecimentoTemplates::pdfs',    ['filter' => 'permission:os:editar']);
    $routes->post('conhecimento/modelos-pdf/salvar',          'ConhecimentoTemplates::savePdf', ['filter' => 'permission:os:editar']);
    $routes->post('conhecimento/modelos-pdf/toggle/(:num)',   'ConhecimentoTemplates::togglePdf/$1', ['filter' => 'permission:os:editar']);
    $routes->get('conhecimento/templates-whatsapp',           'ConhecimentoTemplates::whatsapp', ['filter' => 'permission:os:editar']);
    $routes->post('conhecimento/templates-whatsapp/salvar',   'ConhecimentoTemplates::saveWhatsapp', ['filter' => 'permission:os:editar']);
    $routes->post('conhecimento/templates-whatsapp/toggle/(:num)', 'ConhecimentoTemplates::toggleWhatsapp/$1', ['filter' => 'permission:os:editar']);

    // -- ServiÃ§os ----------------------------------------------------------
    $routes->get('servicos',                  'Servicos::index',            ['filter' => 'permission:servicos:visualizar']);
    $routes->get('servicos/novo',             'Servicos::create',           ['filter' => 'permission:servicos:criar']);
    $routes->post('servicos/salvar',          'Servicos::store',            ['filter' => 'permission:servicos:criar']);
    $routes->post('servicos/salvar_ajax',     'Servicos::salvar_ajax',      ['filter' => 'permission:servicos:criar']);
    $routes->get('servicos/editar/(:num)',    'Servicos::edit/$1',          ['filter' => 'permission:servicos:editar']);
    $routes->post('servicos/atualizar/(:num)','Servicos::update/$1',        ['filter' => 'permission:servicos:editar']);
    $routes->get('servicos/excluir/(:num)',   'Servicos::delete/$1',        ['filter' => 'permission:servicos:excluir']);
    $routes->post('servicos/encerrar/(:num)', 'Servicos::encerrar/$1',       ['filter' => 'permission:servicos:encerrar']);
    $routes->get('servicos/exportar',         'Servicos::exportCsv',        ['filter' => 'permission:servicos:exportar']);
    $routes->get('servicos/modelo-csv',       'Servicos::downloadCsvTemplate',['filter' => 'permission:servicos:importar']);
    $routes->post('servicos/importar',        'Servicos::importCsv',        ['filter' => 'permission:servicos:importar']);

    // -- Orcamentos --------------------------------------------------------
    $routes->get('orcamentos',                         'Orcamentos::index',        ['filter' => 'permission:orcamentos:visualizar']);
    $routes->get('orcamentos/novo',                    'Orcamentos::create',       ['filter' => 'permission:orcamentos:criar']);
    $routes->get('orcamentos/clientes/lookup',         'Orcamentos::lookupClienteContato', ['filter' => 'permission:orcamentos:visualizar']);
    $routes->get('orcamentos/equipamentos/cliente',    'Orcamentos::lookupEquipamentosCliente', ['filter' => 'permission:orcamentos:visualizar']);
    $routes->get('orcamentos/os-abertas/cliente',      'Orcamentos::lookupOsAbertasCliente', ['filter' => 'permission:orcamentos:visualizar']);
    $routes->get('orcamentos/item/catalogo',           'Orcamentos::itemCatalogSearch', ['filter' => 'permission:orcamentos:visualizar']);
    $routes->get('orcamentos/pacotes/oferta/detectar', 'Orcamentos::detectPacoteOferta', ['filter' => 'permission:orcamentos:visualizar']);
    $routes->post('orcamentos/pacotes/oferta/enviar',  'Orcamentos::sendPacoteOferta', ['filter' => 'permission:orcamentos:criar']);
    $routes->post('orcamentos/salvar',                 'Orcamentos::store',        ['filter' => 'permission:orcamentos:criar']);
    $routes->get('orcamentos/visualizar/(:num)',       'Orcamentos::show/$1',      ['filter' => 'permission:orcamentos:visualizar']);
    $routes->get('orcamentos/editar/(:num)',           'Orcamentos::edit/$1',      ['filter' => 'permission:orcamentos:editar']);
    $routes->post('orcamentos/atualizar/(:num)',       'Orcamentos::update/$1',    ['filter' => 'permission:orcamentos:editar']);
    $routes->post('orcamentos/revisar/(:num)',         'Orcamentos::createRevision/$1', ['filter' => 'permission:orcamentos:editar']);
    $routes->post('orcamentos/status/(:num)',          'Orcamentos::updateStatus/$1', ['filter' => 'permission:orcamentos:editar']);
    $routes->post('orcamentos/converter/(:num)',       'Orcamentos::convert/$1', ['filter' => 'permission:orcamentos:editar']);
    $routes->post('orcamentos/automacao/executar',     'Orcamentos::runAutomation', ['filter' => 'permission:orcamentos:editar']);
    $routes->post('orcamentos/pdf/(:num)/gerar',       'Orcamentos::generatePdf/$1', ['filter' => 'permission:orcamentos:visualizar']);
    $routes->get('orcamentos/pdf/(:num)',              'Orcamentos::downloadPdf/$1', ['filter' => 'permission:orcamentos:visualizar']);
$routes->post('orcamentos/whatsapp/(:num)/enviar', 'Orcamentos::sendWhatsApp/$1', ['filter' => 'permission:orcamentos:editar']);
$routes->post('orcamentos/email/(:num)/enviar',    'Orcamentos::sendEmail/$1', ['filter' => 'permission:orcamentos:editar']);
// Compatibilidade legado: endpoint antigo redireciona internamente para o fluxo dinamico de ofertas.
$routes->post('orcamentos/pacotes/gerar-enviar/(:num)', 'Orcamentos::sendPacoteLink/$1', ['filter' => 'permission:orcamentos:editar']);
$routes->post('orcamentos/central-mensagens/gerar-enviar', 'Orcamentos::quickCreateAndSendFromConversa', ['filter' => 'permission:orcamentos:criar']);
    $routes->get('orcamentos/excluir/(:num)',          'Orcamentos::delete/$1',    ['filter' => 'permission:orcamentos:excluir']);

    // -- Pacotes de Servicos -----------------------------------------------
    $routes->get('pacotes-servicos',                    'PacotesServicos::index',    ['filter' => 'permission:orcamentos:visualizar']);
    $routes->get('pacotes-servicos/novo',               'PacotesServicos::create',   ['filter' => 'permission:orcamentos:criar']);
    $routes->post('pacotes-servicos/salvar',            'PacotesServicos::store',    ['filter' => 'permission:orcamentos:criar']);
    $routes->get('pacotes-servicos/editar/(:num)',      'PacotesServicos::edit/$1',  ['filter' => 'permission:orcamentos:editar']);
    $routes->get('pacotes-servicos/preview/(:num)',     'PacotesServicos::preview/$1', ['filter' => 'permission:orcamentos:visualizar']);
    $routes->post('pacotes-servicos/atualizar/(:num)',  'PacotesServicos::update/$1',['filter' => 'permission:orcamentos:editar']);
    $routes->get('pacotes-servicos/excluir/(:num)',     'PacotesServicos::delete/$1',['filter' => 'permission:orcamentos:excluir']);
    $routes->get('precificacao',                        'Precificacao::index', ['filter' => 'permission:precificacao:visualizar']);
    $routes->get('precificacao/configuracao',           'Precificacao::configuracao', ['filter' => 'permission:precificacao:visualizar']);
    $routes->post('precificacao/configuracao/salvar',    'Precificacao::saveConfiguracao', ['filter' => 'permission:precificacao:editar']);
    $routes->get('precificacao/simulador',              'Precificacao::simulador', ['filter' => 'permission:precificacao:visualizar']);
    $routes->get('precificacao/categoria-encargos/(:num)', 'Precificacao::categoriaEncargos/$1', ['filter' => 'permission:precificacao:visualizar']);
    $routes->post('precificacao/categoria-encargos/(:num)', 'Precificacao::salvarCategoriaEncargos/$1', ['filter' => 'permission:precificacao:editar']);
    $routes->get('precificacao/categoria-override',       'Precificacao::categoriaOverride', ['filter' => 'permission:precificacao:visualizar']);
    $routes->post('precificacao/salvar',                'Precificacao::save', ['filter' => 'permission:precificacao:editar']);
    $routes->post('precificacao/simular-peca',          'Precificacao::simularPeca', ['filter' => 'permission:precificacao:visualizar']);
    $routes->post('precificacao/simular-servico',       'Precificacao::simularServico', ['filter' => 'permission:precificacao:visualizar']);

    // -- Vendas ------------------------------------------------------------
    $routes->get('vendas',                    'Vendas::index',              ['filter' => 'permission:vendas:visualizar']);

    // -- Estoque -----------------------------------------------------------
    $routes->get('estoque',                    'Estoque::index',      ['filter' => 'permission:estoque:visualizar']);
    $routes->get('estoque/novo',              'Estoque::create',     ['filter' => 'permission:estoque:criar']);
    $routes->post('estoque/salvar',            'Estoque::store',      ['filter' => 'permission:estoque:criar']);
    $routes->post('estoque/salvar_ajax',       'Estoque::salvar_ajax', ['filter' => 'permission:estoque:criar']);
    $routes->get('estoque/editar/(:num)',      'Estoque::edit/$1',    ['filter' => 'permission:estoque:editar']);
    $routes->post('estoque/atualizar/(:num)',  'Estoque::update/$1',  ['filter' => 'permission:estoque:editar']);
    $routes->get('estoque/excluir/(:num)',     'Estoque::delete/$1',  ['filter' => 'permission:estoque:excluir']);
    $routes->post('estoque/movimentacao',      'Estoque::movement',   ['filter' => 'permission:estoque:editar']);
    $routes->get('estoque/exportar',           'Estoque::exportCsv',   ['filter' => 'permission:estoque:exportar']);
    $routes->get('estoque/modelo-csv',         'Estoque::downloadCsvTemplate', ['filter' => 'permission:estoque:importar']);
    $routes->post('estoque/importar',          'Estoque::importCsv',   ['filter' => 'permission:estoque:importar']);
    $routes->get('estoque/movimentacoes/(:num)','Estoque::movements/$1',['filter' => 'permission:estoque:visualizar']);
    $routes->get('estoque/buscar',             'Estoque::search',     ['filter' => 'permission:estoque:visualizar']);

    // -- Financeiro --------------------------------------------------------
    $routes->get('financeiro',                  'Financeiro::index',    ['filter' => 'permission:financeiro:visualizar']);
    $routes->get('financeiro/configuracoes',    'FinanceiroConfiguracoes::index', ['filter' => 'permission:financeiro:visualizar']);
    $routes->get('financeiro/cartoes',          'FinanceiroCartoes::index', ['filter' => 'permission:financeiro:visualizar']);
    $routes->post('financeiro/cartoes/operadoras/salvar', 'FinanceiroCartoes::saveOperadora', ['filter' => 'permission:financeiro:editar']);
    $routes->post('financeiro/cartoes/operadoras/desativar/(:num)', 'FinanceiroCartoes::disableOperadora/$1', ['filter' => 'permission:financeiro:excluir']);
    $routes->post('financeiro/cartoes/bandeiras/salvar', 'FinanceiroCartoes::saveBandeira', ['filter' => 'permission:financeiro:editar']);
    $routes->post('financeiro/cartoes/bandeiras/desativar/(:num)', 'FinanceiroCartoes::disableBandeira/$1', ['filter' => 'permission:financeiro:excluir']);
    $routes->post('financeiro/cartoes/taxas/salvar', 'FinanceiroCartoes::saveTaxa', ['filter' => 'permission:financeiro:editar']);
    $routes->post('financeiro/cartoes/taxas/desativar/(:num)', 'FinanceiroCartoes::disableTaxa/$1', ['filter' => 'permission:financeiro:excluir']);
    $routes->post('financeiro/cartoes/simular', 'FinanceiroCartoes::simulate', ['filter' => 'permission:financeiro:visualizar']);
    $routes->post('financeiro/configuracoes/categorias/salvar', 'FinanceiroConfiguracoes::saveCategoria', ['filter' => 'permission:financeiro:editar']);
    $routes->post('financeiro/configuracoes/categorias/excluir/(:num)', 'FinanceiroConfiguracoes::deleteCategoria/$1', ['filter' => 'permission:financeiro:excluir']);
    $routes->post('financeiro/configuracoes/dre/grupos/salvar', 'FinanceiroConfiguracoes::saveGrupo', ['filter' => 'permission:financeiro:editar']);
    $routes->post('financeiro/configuracoes/dre/grupos/excluir/(:num)', 'FinanceiroConfiguracoes::deleteGrupo/$1', ['filter' => 'permission:financeiro:excluir']);
    $routes->post('financeiro/configuracoes/dre/subgrupos/salvar', 'FinanceiroConfiguracoes::saveSubgrupo', ['filter' => 'permission:financeiro:editar']);
    $routes->post('financeiro/configuracoes/dre/subgrupos/excluir/(:num)', 'FinanceiroConfiguracoes::deleteSubgrupo/$1', ['filter' => 'permission:financeiro:excluir']);
    $routes->get('financeiro/detalhes/(:num)', 'Financeiro::details/$1', ['filter' => 'permission:financeiro:visualizar']);
    $routes->get('financeiro/novo',            'Financeiro::create',   ['filter' => 'permission:financeiro:criar']);
    $routes->post('financeiro/salvar',          'Financeiro::store',    ['filter' => 'permission:financeiro:criar']);
    $routes->get('financeiro/editar/(:num)',    'Financeiro::edit/$1',  ['filter' => 'permission:financeiro:editar']);
    $routes->post('financeiro/atualizar/(:num)','Financeiro::update/$1',['filter' => 'permission:financeiro:editar']);
    $routes->get('financeiro/excluir/(:num)',   'Financeiro::delete/$1',['filter' => 'permission:financeiro:excluir']);
    $routes->post('financeiro/baixar/(:num)',   'Financeiro::pay/$1',   ['filter' => 'permission:financeiro:editar']);

    // -- RelatÃ³rios --------------------------------------------------------
    $routes->get('relatorios',             'Relatorios::index',      ['filter' => 'permission:relatorios:visualizar']);
    $routes->get('relatorios/os',          'Relatorios::osByPeriod', ['filter' => 'permission:relatorios:visualizar']);
    $routes->get('relatorios/financeiro',  'Relatorios::financial',  ['filter' => 'permission:relatorios:visualizar']);
    $routes->get('relatorios/dre',         'Relatorios::dre',        ['filter' => 'permission:relatorios:visualizar']);
    $routes->get('relatorios/fluxo-caixa', 'Relatorios::cashFlow',   ['filter' => 'permission:relatorios:visualizar']);
    $routes->get('relatorios/estoque',     'Relatorios::stock',      ['filter' => 'permission:relatorios:visualizar']);
    $routes->get('relatorios/clientes',    'Relatorios::clients',    ['filter' => 'permission:relatorios:visualizar']);

    // -- ConfiguraÃ§Ãµes -----------------------------------------------------
    $routes->get('configuracoes',          'Configuracoes::index',   ['filter' => 'permission:configuracoes:visualizar']);
    $routes->post('configuracoes/salvar',  'Configuracoes::save',    ['filter' => 'permission:configuracoes:editar']);
    $routes->post('configuracoes/email/enviar-teste', 'Configuracoes::sendEmailTest', ['filter' => 'permission:configuracoes:editar']);
    $routes->post('configuracoes/whatsapp/testar-conexao', 'Configuracoes::testWhatsAppConnection', ['filter' => 'permission:configuracoes:editar']);
    $routes->post('configuracoes/whatsapp/enviar-teste', 'Configuracoes::sendWhatsAppTestMessage', ['filter' => 'permission:configuracoes:editar']);
    $routes->get('configuracoes/whatsapp/local-status', 'Configuracoes::whatsappLocalStatus', ['filter' => 'permission:configuracoes:visualizar']);
    $routes->get('configuracoes/whatsapp/local-qr', 'Configuracoes::whatsappLocalQr', ['filter' => 'permission:configuracoes:visualizar']);
    $routes->post('configuracoes/whatsapp/local-restart', 'Configuracoes::whatsappLocalRestart', ['filter' => 'permission:configuracoes:editar']);
    $routes->post('configuracoes/whatsapp/local-logout', 'Configuracoes::whatsappLocalLogout', ['filter' => 'permission:configuracoes:editar']);
    $routes->post('configuracoes/whatsapp/local-start', 'Configuracoes::whatsappLocalStart', ['filter' => 'permission:configuracoes:editar']);
    $routes->post('configuracoes/whatsapp/self-check-inbound', 'Configuracoes::whatsappInboundSelfCheck', ['filter' => 'permission:configuracoes:editar']);

    // -- UsuÃ¡rios ----------------------------------------------------------
    $routes->get('usuarios',                  'Usuarios::index',    ['filter' => 'permission:usuarios:visualizar']);
    $routes->post('usuarios/datatable',       'Usuarios::datatable',['filter' => 'permission:usuarios:visualizar']);
    $routes->get('usuarios/novo',             'Usuarios::create',   ['filter' => 'permission:usuarios:criar']);
    $routes->post('usuarios/salvar',          'Usuarios::store',    ['filter' => 'permission:usuarios:criar']);
    $routes->get('usuarios/editar/(:num)',    'Usuarios::edit/$1',  ['filter' => 'permission:usuarios:editar']);
    $routes->post('usuarios/atualizar/(:num)','Usuarios::update/$1',['filter' => 'permission:usuarios:editar']);
    $routes->get('usuarios/excluir/(:num)',   'Usuarios::delete/$1',['filter' => 'permission:usuarios:excluir']);

    // -- DocumentaÃ§Ã£o (Central de Conhecimento / Wiki) ---------------------
    $routes->get('design-system',         'DesignSystem::index', ['filter' => 'permission:configuracoes:visualizar']);
    $routes->get('documentacao',          'Documentacao::index');
    $routes->get('documentacao/arquivo',  'Documentacao::arquivo');
    $routes->get('documentacao/buscar',   'Documentacao::buscar');
    $routes->get('documentacao/arvore',   'Documentacao::arvore');

    // -- Upload (apenas usuÃ¡rios autenticados) -----------------------------
    $routes->post('upload/imagem',         'Upload::image');
    // -- Busca Global ------------------------------------------------------
    $routes->get('api/busca-global', 'GlobalSearch::index');
    $routes->get('busca/resultados', 'GlobalSearch::results');
});
