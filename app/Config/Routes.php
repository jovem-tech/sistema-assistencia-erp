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
$routes->post('webhooks/whatsapp', 'WhatsAppWebhook::receive');

// =====================================================
// ROTAS PROTEGIDAS (requer autenticação + permissão RBAC)
// =====================================================
$routes->group('', ['filter' => 'auth'], function ($routes) {

// ── Dashboard (todos os autenticados) ──────────────────────────────
    $routes->get('sessao/heartbeat', 'Sessao::heartbeat');
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

    // -- Clientes ----------------------------------------------------------
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
    $routes->get('fornecedores/editar/(:num)',    'Fornecedores::edit/$1',    ['filter' => 'permission:fornecedores:editar']);
    $routes->post('fornecedores/atualizar/(:num)','Fornecedores::update/$1',  ['filter' => 'permission:fornecedores:editar']);
    $routes->get('fornecedores/excluir/(:num)',   'Fornecedores::delete/$1',  ['filter' => 'permission:fornecedores:excluir']);

    // -- Funcionários ------------------------------------------------------
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
    $routes->get('equipamentos/excluir/(:num)',   'Equipamentos::delete/$1',  ['filter' => 'permission:equipamentos:excluir']);
    $routes->get('equipamentos/visualizar/(:num)',   'Equipamentos::show/$1',  ['filter' => 'permission:equipamentos:visualizar']);
    $routes->post('equipamentos/vincular-cliente',   'Equipamentos::vincularCliente', ['filter' => 'permission:equipamentos:editar']);
    $routes->get('equipamentos/desvincular-cliente/(:num)/(:num)', 'Equipamentos::desvincularCliente/$1/$2', ['filter' => 'permission:equipamentos:editar']);
    $routes->get('equipamentos/por-cliente/(:num)','Equipamentos::byClient/$1',['filter' => 'permission:equipamentos:visualizar']);
    $routes->get('equipamentos/fotos/(:num)','Equipamentos::getFotos/$1',['filter' => 'permission:equipamentos:visualizar']);
    $routes->post('equipamentos/salvar-ajax','Equipamentos::storeAjax',['filter' => 'permission:equipamentos:criar']);
    $routes->post('equipamentos/atualizar-ajax/(:num)','Equipamentos::updateAjax/$1',['filter' => 'permission:equipamentos:editar']);

    // -- Equipamentos Tipos ------------------------------------------------
    $routes->get('equipamentostipos',              'EquipamentosTipos::index',  ['filter' => 'permission:equipamentos:visualizar']);
    $routes->post('equipamentostipos/salvar',      'EquipamentosTipos::store',  ['filter' => 'permission:equipamentos:criar']);
    $routes->get('equipamentostipos/excluir/(:num)','EquipamentosTipos::delete/$1',['filter' => 'permission:equipamentos:excluir']);

    // -- Equipamentos Marcas -----------------------------------------------
    $routes->get('equipamentosmarcas',              'EquipamentosMarcas::index',  ['filter' => 'permission:equipamentos:visualizar']);
    $routes->post('equipamentosmarcas/salvar',      'EquipamentosMarcas::store',  ['filter' => 'permission:equipamentos:criar']);
    $routes->post('equipamentosmarcas/salvar_ajax', 'EquipamentosMarcas::salvar_ajax',  ['filter' => 'permission:equipamentos:criar']);
    $routes->get('equipamentosmarcas/excluir/(:num)','EquipamentosMarcas::delete/$1',['filter' => 'permission:equipamentos:excluir']);
    $routes->post('equipamentosmarcas/importar',    'EquipamentosMarcas::importCsv',['filter' => 'permission:equipamentos:importar']);

    // -- Equipamentos Modelos ----------------------------------------------
    $routes->get('equipamentosmodelos',              'EquipamentosModelos::index',  ['filter' => 'permission:equipamentos:visualizar']);
    $routes->post('equipamentosmodelos/salvar',      'EquipamentosModelos::store',  ['filter' => 'permission:equipamentos:criar']);
    $routes->post('equipamentosmodelos/salvar_ajax', 'EquipamentosModelos::salvar_ajax',  ['filter' => 'permission:equipamentos:criar']);
    $routes->get('equipamentosmodelos/excluir/(:num)','EquipamentosModelos::delete/$1',['filter' => 'permission:equipamentos:excluir']);
    $routes->post('equipamentosmodelos/importar',    'EquipamentosModelos::importCsv',['filter' => 'permission:equipamentos:importar']);
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
    $routes->get('crm/clientes',                 'Crm::clientes',               ['filter' => 'permission:clientes:visualizar']);
    $routes->get('crm/timeline',                 'Crm::timeline',               ['filter' => 'permission:clientes:visualizar']);
    $routes->get('crm/interacoes',               'Crm::interacoes',             ['filter' => 'permission:clientes:visualizar']);
    $routes->post('crm/interacoes/salvar',       'Crm::salvarInteracao',        ['filter' => 'permission:clientes:visualizar']);
    $routes->get('crm/followups',                'Crm::followups',              ['filter' => 'permission:clientes:visualizar']);
    $routes->post('crm/followups/salvar',        'Crm::salvarFollowup',         ['filter' => 'permission:clientes:visualizar']);
    $routes->post('crm/followups/(:num)/status', 'Crm::atualizarFollowupStatus/$1', ['filter' => 'permission:clientes:visualizar']);
    $routes->get('crm/pipeline',                 'Crm::pipeline',               ['filter' => 'permission:clientes:visualizar']);
    $routes->get('crm/campanhas',                'Crm::campanhas',              ['filter' => 'permission:clientes:visualizar']);
    $routes->get('crm/clientes-inativos',        'Crm::clientesInativos',       ['filter' => 'permission:clientes:visualizar']);
    $routes->post('crm/clientes-inativos/followup', 'Crm::criarFollowupInativo', ['filter' => 'permission:clientes:visualizar']);
    $routes->get('crm/metricas-marketing',       'Crm::metricasMarketing',      ['filter' => 'permission:clientes:visualizar']);
    $routes->post('crm/metricas-marketing/engajamento', 'Crm::salvarEngajamentoPeriodos', ['filter' => 'permission:clientes:editar']);

    // Central de Atendimento WhatsApp (rota canonica + alias legado)
    $routes->get('atendimento-whatsapp',                    'CentralMensagens::index',                       ['filter' => 'permission:clientes:visualizar']);
    $routes->get('atendimento-whatsapp/conversas',          'CentralMensagens::conversas',                   ['filter' => 'permission:clientes:visualizar']);
    $routes->get('atendimento-whatsapp/conversa/(:num)',    'CentralMensagens::conversa/$1',                 ['filter' => 'permission:clientes:visualizar']);
$routes->get('atendimento-whatsapp/conversa/(:num)/novas', 'CentralMensagens::conversaNovas/$1',         ['filter' => 'permission:clientes:visualizar']);
$routes->get('atendimento-whatsapp/conversa/(:num)/stream', 'CentralMensagens::conversaStream/$1',       ['filter' => 'permission:clientes:visualizar']);
$routes->post('atendimento-whatsapp/conversa/(:num)/cadastrar-contato', 'CentralMensagens::cadastrarContatoConversa/$1', ['filter' => 'permission:clientes:visualizar']);
$routes->post('atendimento-whatsapp/conversa/(:num)/cadastrar-cliente', 'CentralMensagens::cadastrarContatoConversa/$1', ['filter' => 'permission:clientes:visualizar']);
$routes->post('atendimento-whatsapp/enviar',            'CentralMensagens::enviar',                      ['filter' => 'permission:clientes:visualizar']);
$routes->post('atendimento-whatsapp/vincular-os',       'CentralMensagens::vincularOs',                  ['filter' => 'permission:clientes:visualizar']);
$routes->post('atendimento-whatsapp/atualizar-meta',    'CentralMensagens::atualizarMeta',               ['filter' => 'permission:clientes:visualizar']);
$routes->post('atendimento-whatsapp/sync-inbound',      'CentralMensagens::syncInbound',                 ['filter' => 'permission:clientes:visualizar']);
    $routes->get('atendimento-whatsapp/chatbot',            'CentralMensagens::chatbot',                     ['filter' => 'permission:clientes:visualizar']);
    $routes->post('atendimento-whatsapp/chatbot/intencao/salvar', 'CentralMensagens::salvarIntencao',        ['filter' => 'permission:clientes:editar']);
    $routes->post('atendimento-whatsapp/chatbot/intencao/toggle/(:num)', 'CentralMensagens::toggleIntencao/$1', ['filter' => 'permission:clientes:editar']);
    $routes->post('atendimento-whatsapp/chatbot/intencao/deletar/(:num)', 'CentralMensagens::deletarIntencao/$1', ['filter' => 'permission:clientes:editar']);
    $routes->post('atendimento-whatsapp/chatbot/regra/salvar', 'CentralMensagens::salvarRegraErp',          ['filter' => 'permission:clientes:editar']);
    $routes->post('atendimento-whatsapp/chatbot/regra/toggle/(:num)', 'CentralMensagens::toggleRegraErp/$1', ['filter' => 'permission:clientes:editar']);
    $routes->post('atendimento-whatsapp/chatbot/regra/deletar/(:num)', 'CentralMensagens::deletarRegraErp/$1', ['filter' => 'permission:clientes:editar']);
    $routes->get('atendimento-whatsapp/faq',                'CentralMensagens::faq',                         ['filter' => 'permission:clientes:visualizar']);
    $routes->post('atendimento-whatsapp/faq/salvar',        'CentralMensagens::salvarFaq',                   ['filter' => 'permission:clientes:editar']);
    $routes->post('atendimento-whatsapp/faq/toggle/(:num)', 'CentralMensagens::toggleFaq/$1',                ['filter' => 'permission:clientes:editar']);
    $routes->get('atendimento-whatsapp/respostas-rapidas',  'CentralMensagens::respostasRapidas',            ['filter' => 'permission:clientes:visualizar']);
    $routes->post('atendimento-whatsapp/respostas-rapidas/salvar', 'CentralMensagens::salvarRespostaRapida', ['filter' => 'permission:clientes:editar']);
    $routes->post('atendimento-whatsapp/respostas-rapidas/toggle/(:num)', 'CentralMensagens::toggleRespostaRapida/$1', ['filter' => 'permission:clientes:editar']);
    $routes->get('atendimento-whatsapp/fluxos',             'CentralMensagens::fluxos',                      ['filter' => 'permission:clientes:visualizar']);
    $routes->post('atendimento-whatsapp/fluxos/salvar',     'CentralMensagens::salvarFluxo',                 ['filter' => 'permission:clientes:editar']);
    $routes->post('atendimento-whatsapp/fluxos/toggle/(:num)', 'CentralMensagens::toggleFluxo/$1',           ['filter' => 'permission:clientes:editar']);
    $routes->get('atendimento-whatsapp/filas',              'CentralMensagens::filas',                       ['filter' => 'permission:clientes:visualizar']);
    $routes->post('atendimento-whatsapp/filas/atualizar',   'CentralMensagens::atualizarFila',               ['filter' => 'permission:clientes:editar']);
    $routes->get('atendimento-whatsapp/metricas',           'CentralMensagens::metricas',                    ['filter' => 'permission:clientes:visualizar']);
    $routes->post('atendimento-whatsapp/metricas/consolidar-diario', 'CentralMensagens::consolidarMetricasDiarias', ['filter' => 'permission:clientes:visualizar']);
    $routes->get('atendimento-whatsapp/configuracoes',      'CentralMensagens::configuracoes',               ['filter' => 'permission:clientes:visualizar']);
    $routes->post('atendimento-whatsapp/configuracoes/salvar', 'CentralMensagens::salvarConfiguracoes',      ['filter' => 'permission:clientes:editar']);

    // Alias legado para compatibilidade interna/links antigos
    $routes->get('central-mensagens',                       'CentralMensagens::index',                       ['filter' => 'permission:clientes:visualizar']);
    $routes->get('central-mensagens/conversas',             'CentralMensagens::conversas',                   ['filter' => 'permission:clientes:visualizar']);
    $routes->get('central-mensagens/conversa/(:num)',       'CentralMensagens::conversa/$1',                 ['filter' => 'permission:clientes:visualizar']);
$routes->get('central-mensagens/conversa/(:num)/novas', 'CentralMensagens::conversaNovas/$1',            ['filter' => 'permission:clientes:visualizar']);
$routes->get('central-mensagens/conversa/(:num)/stream', 'CentralMensagens::conversaStream/$1',          ['filter' => 'permission:clientes:visualizar']);
$routes->post('central-mensagens/conversa/(:num)/cadastrar-contato', 'CentralMensagens::cadastrarContatoConversa/$1', ['filter' => 'permission:clientes:visualizar']);
$routes->post('central-mensagens/conversa/(:num)/cadastrar-cliente', 'CentralMensagens::cadastrarContatoConversa/$1', ['filter' => 'permission:clientes:visualizar']);
$routes->post('central-mensagens/enviar',               'CentralMensagens::enviar',                      ['filter' => 'permission:clientes:visualizar']);
$routes->post('central-mensagens/vincular-os',          'CentralMensagens::vincularOs',                  ['filter' => 'permission:clientes:visualizar']);
$routes->post('central-mensagens/atualizar-meta',       'CentralMensagens::atualizarMeta',               ['filter' => 'permission:clientes:visualizar']);
    $routes->post('central-mensagens/sync-inbound',         'CentralMensagens::syncInbound',                 ['filter' => 'permission:clientes:visualizar']);
    $routes->get('central-mensagens/chatbot',               'CentralMensagens::chatbot',                     ['filter' => 'permission:clientes:visualizar']);
    $routes->post('central-mensagens/chatbot/intencao/salvar', 'CentralMensagens::salvarIntencao',           ['filter' => 'permission:clientes:editar']);
    $routes->post('central-mensagens/chatbot/intencao/toggle/(:num)', 'CentralMensagens::toggleIntencao/$1', ['filter' => 'permission:clientes:editar']);
    $routes->post('central-mensagens/chatbot/intencao/deletar/(:num)', 'CentralMensagens::deletarIntencao/$1', ['filter' => 'permission:clientes:editar']);
    $routes->post('central-mensagens/chatbot/regra/salvar', 'CentralMensagens::salvarRegraErp',             ['filter' => 'permission:clientes:editar']);
    $routes->post('central-mensagens/chatbot/regra/toggle/(:num)', 'CentralMensagens::toggleRegraErp/$1',   ['filter' => 'permission:clientes:editar']);
    $routes->post('central-mensagens/chatbot/regra/deletar/(:num)', 'CentralMensagens::deletarRegraErp/$1', ['filter' => 'permission:clientes:editar']);
    $routes->get('central-mensagens/faq',                   'CentralMensagens::faq',                         ['filter' => 'permission:clientes:visualizar']);
    $routes->post('central-mensagens/faq/salvar',           'CentralMensagens::salvarFaq',                   ['filter' => 'permission:clientes:editar']);
    $routes->post('central-mensagens/faq/toggle/(:num)',    'CentralMensagens::toggleFaq/$1',                ['filter' => 'permission:clientes:editar']);
    $routes->get('central-mensagens/respostas-rapidas',     'CentralMensagens::respostasRapidas',            ['filter' => 'permission:clientes:visualizar']);
    $routes->post('central-mensagens/respostas-rapidas/salvar', 'CentralMensagens::salvarRespostaRapida',    ['filter' => 'permission:clientes:editar']);
    $routes->post('central-mensagens/respostas-rapidas/toggle/(:num)', 'CentralMensagens::toggleRespostaRapida/$1', ['filter' => 'permission:clientes:editar']);
    $routes->get('central-mensagens/fluxos',                'CentralMensagens::fluxos',                      ['filter' => 'permission:clientes:visualizar']);
    $routes->post('central-mensagens/fluxos/salvar',        'CentralMensagens::salvarFluxo',                 ['filter' => 'permission:clientes:editar']);
    $routes->post('central-mensagens/fluxos/toggle/(:num)', 'CentralMensagens::toggleFluxo/$1',              ['filter' => 'permission:clientes:editar']);
    $routes->get('central-mensagens/filas',                 'CentralMensagens::filas',                       ['filter' => 'permission:clientes:visualizar']);
    $routes->post('central-mensagens/filas/atualizar',      'CentralMensagens::atualizarFila',               ['filter' => 'permission:clientes:editar']);
    $routes->get('central-mensagens/metricas',              'CentralMensagens::metricas',                    ['filter' => 'permission:clientes:visualizar']);
    $routes->post('central-mensagens/metricas/consolidar-diario', 'CentralMensagens::consolidarMetricasDiarias', ['filter' => 'permission:clientes:visualizar']);
    $routes->get('central-mensagens/configuracoes',         'CentralMensagens::configuracoes',               ['filter' => 'permission:clientes:visualizar']);
    $routes->post('central-mensagens/configuracoes/salvar', 'CentralMensagens::salvarConfiguracoes',         ['filter' => 'permission:clientes:editar']);

    // -- Ordens de Serviço -------------------------------------------------
    $routes->get('os',                    'Os::index',              ['filter' => 'permission:os:visualizar']);
    $routes->post('os/datatable',         'Os::datatable',          ['filter' => 'permission:os:visualizar']);
    $routes->get('os/fotos/(:num)',      'Os::photos/$1',          ['filter' => 'permission:os:visualizar']);
    $routes->get('os/nova',              'Os::create',             ['filter' => 'permission:os:criar']);
    $routes->post('os/salvar',            'Os::store',              ['filter' => 'permission:os:criar']);
    $routes->get('os/editar/(:num)',      'Os::edit/$1',            ['filter' => 'permission:os:editar']);
    $routes->post('os/atualizar/(:num)',  'Os::update/$1',          ['filter' => 'permission:os:editar']);
    $routes->get('os/visualizar/(:num)', 'Os::show/$1',            ['filter' => 'permission:os:visualizar']);
    $routes->get('os/status-meta/(:num)', 'Os::statusMeta/$1',      ['filter' => 'permission:os:visualizar']);
    $routes->post('os/status-ajax/(:num)','Os::updateStatusAjax/$1',['filter' => 'permission:os:editar']);
    $routes->post('os/status/(:num)',    'Os::updateStatus/$1',    ['filter' => 'permission:os:editar']);
    $routes->get('os/imprimir/(:num)',   'Os::print/$1',           ['filter' => 'permission:os:visualizar']);
    $routes->post('os/whatsapp/(:num)',  'Os::sendWhatsApp/$1',    ['filter' => 'permission:os:editar']);
    $routes->post('os/pdf/(:num)/gerar', 'Os::generatePdf/$1',     ['filter' => 'permission:os:visualizar']);
    $routes->post('os/item/salvar',       'Os::addItem',            ['filter' => 'permission:os:editar']);
    $routes->get('os/item/excluir/(:num)','Os::removeItem/$1',     ['filter' => 'permission:os:editar']);
    $routes->get('osworkflow',            'OsWorkflow::index',      ['filter' => 'permission:os:editar']);
    $routes->post('osworkflow/salvar',    'OsWorkflow::save',       ['filter' => 'permission:os:editar']);

    // -- Serviços ----------------------------------------------------------
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

    // -- Vendas ------------------------------------------------------------
    $routes->get('vendas',                    'Vendas::index',              ['filter' => 'permission:vendas:visualizar']);

    // -- Estoque -----------------------------------------------------------
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

    // -- Financeiro --------------------------------------------------------
    $routes->get('financeiro',                  'Financeiro::index',    ['filter' => 'permission:financeiro:visualizar']);
    $routes->get('financeiro/novo',            'Financeiro::create',   ['filter' => 'permission:financeiro:criar']);
    $routes->post('financeiro/salvar',          'Financeiro::store',    ['filter' => 'permission:financeiro:criar']);
    $routes->get('financeiro/editar/(:num)',    'Financeiro::edit/$1',  ['filter' => 'permission:financeiro:editar']);
    $routes->post('financeiro/atualizar/(:num)','Financeiro::update/$1',['filter' => 'permission:financeiro:editar']);
    $routes->get('financeiro/excluir/(:num)',   'Financeiro::delete/$1',['filter' => 'permission:financeiro:excluir']);
    $routes->post('financeiro/baixar/(:num)',   'Financeiro::pay/$1',   ['filter' => 'permission:financeiro:editar']);

    // -- Relatórios --------------------------------------------------------
    $routes->get('relatorios',             'Relatorios::index',      ['filter' => 'permission:relatorios:visualizar']);
    $routes->get('relatorios/os',          'Relatorios::osByPeriod', ['filter' => 'permission:relatorios:visualizar']);
    $routes->get('relatorios/financeiro',  'Relatorios::financial',  ['filter' => 'permission:relatorios:visualizar']);
    $routes->get('relatorios/estoque',     'Relatorios::stock',      ['filter' => 'permission:relatorios:visualizar']);
    $routes->get('relatorios/clientes',    'Relatorios::clients',    ['filter' => 'permission:relatorios:visualizar']);

    // -- Configurações -----------------------------------------------------
    $routes->get('configuracoes',          'Configuracoes::index',   ['filter' => 'permission:configuracoes:visualizar']);
    $routes->post('configuracoes/salvar',  'Configuracoes::save',    ['filter' => 'permission:configuracoes:editar']);
    $routes->post('configuracoes/whatsapp/testar-conexao', 'Configuracoes::testWhatsAppConnection', ['filter' => 'permission:configuracoes:editar']);
    $routes->post('configuracoes/whatsapp/enviar-teste', 'Configuracoes::sendWhatsAppTestMessage', ['filter' => 'permission:configuracoes:editar']);
    $routes->get('configuracoes/whatsapp/local-status', 'Configuracoes::whatsappLocalStatus', ['filter' => 'permission:configuracoes:visualizar']);
    $routes->get('configuracoes/whatsapp/local-qr', 'Configuracoes::whatsappLocalQr', ['filter' => 'permission:configuracoes:visualizar']);
    $routes->post('configuracoes/whatsapp/local-restart', 'Configuracoes::whatsappLocalRestart', ['filter' => 'permission:configuracoes:editar']);
    $routes->post('configuracoes/whatsapp/local-logout', 'Configuracoes::whatsappLocalLogout', ['filter' => 'permission:configuracoes:editar']);
    $routes->post('configuracoes/whatsapp/local-start', 'Configuracoes::whatsappLocalStart', ['filter' => 'permission:configuracoes:editar']);
    $routes->post('configuracoes/whatsapp/self-check-inbound', 'Configuracoes::whatsappInboundSelfCheck', ['filter' => 'permission:configuracoes:editar']);

    // -- Usuários ----------------------------------------------------------
    $routes->get('usuarios',                  'Usuarios::index',    ['filter' => 'permission:usuarios:visualizar']);
    $routes->post('usuarios/datatable',       'Usuarios::datatable',['filter' => 'permission:usuarios:visualizar']);
    $routes->get('usuarios/novo',             'Usuarios::create',   ['filter' => 'permission:usuarios:criar']);
    $routes->post('usuarios/salvar',          'Usuarios::store',    ['filter' => 'permission:usuarios:criar']);
    $routes->get('usuarios/editar/(:num)',    'Usuarios::edit/$1',  ['filter' => 'permission:usuarios:editar']);
    $routes->post('usuarios/atualizar/(:num)','Usuarios::update/$1',['filter' => 'permission:usuarios:editar']);
    $routes->get('usuarios/excluir/(:num)',   'Usuarios::delete/$1',['filter' => 'permission:usuarios:excluir']);

    // -- Documentação (Central de Conhecimento / Wiki) ---------------------
    $routes->get('design-system',         'DesignSystem::index', ['filter' => 'permission:configuracoes:visualizar']);
    $routes->get('documentacao',          'Documentacao::index');
    $routes->get('documentacao/arquivo',  'Documentacao::arquivo');
    $routes->get('documentacao/buscar',   'Documentacao::buscar');
    $routes->get('documentacao/arvore',   'Documentacao::arvore');

    // -- Upload (apenas usuários autenticados) -----------------------------
    $routes->post('upload/imagem',         'Upload::image');
    // ── Busca Global ─────────────────────────────────────────────────────
    $routes->get('api/busca-global', 'GlobalSearch::index');
    $routes->get('busca/resultados', 'GlobalSearch::results');
});
