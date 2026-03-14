<?php

/**
 * Return HTML badge for OS status
 */
function getStatusBadge($status)
{
    $badges = [
        'aguardando_analise'   => '<span class="badge bg-warning text-dark">Aguard. Análise</span>',
        'aguardando_orcamento' => '<span class="badge bg-info">Aguard. Orçamento</span>',
        'aguardando_aprovacao' => '<span class="badge bg-purple">Aguard. Aprovação</span>',
        'aprovado'             => '<span class="badge bg-primary">Aprovado</span>',
        'reprovado'            => '<span class="badge bg-danger">Reprovado</span>',
        'em_reparo'            => '<span class="badge bg-indigo">Em Reparo</span>',
        'aguardando_peca'      => '<span class="badge bg-orange">Aguard. Peça</span>',
        'pronto'               => '<span class="badge bg-success">Pronto</span>',
        'entregue'             => '<span class="badge bg-teal">Entregue</span>',
        'cancelado'            => '<span class="badge bg-secondary">Cancelado</span>',
    ];

    return $badges[$status] ?? '<span class="badge bg-secondary">' . ucfirst(str_replace('_', ' ', $status)) . '</span>';
}

/**
 * Format BRL currency
 */
function formatMoney($value)
{
    return 'R$ ' . number_format((float)$value, 2, ',', '.');
}

/**
 * Format date to BR format
 */
function formatDate($date, $withTime = false)
{
    if (empty($date)) return '-';
    $format = $withTime ? 'd/m/Y H:i' : 'd/m/Y';
    return date($format, strtotime($date));
}

/**
 * Get priority badge
 */
function getPriorityBadge($priority)
{
    $badges = [
        'baixa'   => '<span class="badge bg-secondary">Baixa</span>',
        'normal'  => '<span class="badge bg-info">Normal</span>',
        'alta'    => '<span class="badge bg-warning text-dark">Alta</span>',
        'urgente' => '<span class="badge bg-danger">Urgente</span>',
    ];

    return $badges[$priority] ?? '<span class="badge bg-info">Normal</span>';
}

/**
 * Get equipment type label
 */
function getEquipTipo($tipo)
{
    $tipos = [
        'notebook'    => 'Notebook',
        'desktop'     => 'Desktop',
        'celular'     => 'Celular',
        'tablet'      => 'Tablet',
        'impressora'  => 'Impressora',
        'outros'      => 'Outros',
    ];

    return $tipos[$tipo] ?? ucfirst($tipo);
}

/**
 * Obter valor de configuração
 */
function get_config($chave, $default = null) {
    if(!function_exists('model')) return $default;
    try {
        $db = \Config\Database::connect();
        $builder = $db->table('configuracoes');
        $row = $builder->where('chave', $chave)->get()->getRow();
        return $row ? $row->valor : $default;
    } catch(\Exception $e) {
        return $default;
    }
}

/**
 * Retorna o tema atual configurado (light ou dark)
 */
function get_theme() {
    $theme = get_config('tema', 'dark');
    return $theme === 'light' ? 'light' : 'dark';
}

// ════════════════════════════════════════════════════════════════════════════
// RBAC — Sistema de controle de acesso baseado em grupos
// ════════════════════════════════════════════════════════════════════════════

/**
 * Carrega e cacheia no session o mapa de permissões do usuário logado.
 * Estrutura: ['clientes' => ['visualizar', 'criar', 'editar'], ...]
 */
function loadUserPermissions(): array
{
    $cached = session()->get('user_permissions');
    if (is_array($cached)) {
        return $cached;
    }

    $grupoId = session()->get('user_grupo_id');
    if (!$grupoId) {
        // Admin legado (perfil=admin sem grupo): acesso total
        if (session()->get('user_perfil') === 'admin') {
            return ['*' => ['*']]; // wildcard
        }
        return [];
    }

    try {
        $db = \Config\Database::connect();
        $rows = $db->table('grupo_permissoes gp')
            ->select('m.slug as modulo, p.slug as permissao')
            ->join('modulos m', 'm.id = gp.modulo_id')
            ->join('permissoes p', 'p.id = gp.permissao_id')
            ->where('gp.grupo_id', $grupoId)
            ->get()->getResultArray();

        $permissions = [];
        foreach ($rows as $r) {
            $permissions[$r['modulo']][] = $r['permissao'];
        }

        session()->set('user_permissions', $permissions);
        return $permissions;
    } catch (\Exception $e) {
        return [];
    }
}

/**
 * Verifica se o usuário logado pode executar uma ação em um módulo.
 * Uso: can('clientes', 'editar')
 */
function can(string $modulo, string $acao): bool
{
    $permissions = loadUserPermissions();

    // Wildcard admin
    if (isset($permissions['*'])) return true;

    return isset($permissions[$modulo]) && in_array($acao, $permissions[$modulo]);
}

/**
 * Verifica se o usuário pode visualizar (acessar) um módulo.
 * Uso: canModule('financeiro')
 */
function canModule(string $modulo): bool
{
    return can($modulo, 'visualizar');
}

/**
 * Força recarregamento do cache de permissões na sessão.
 * Chamar após alterar grup_permissoes de um usuário ativo.
 */
function refreshPermissions(): void
{
    session()->remove('user_permissions');
}

/**
 * Aborta a requisição com erro 403 se o usuário não tiver a permissão.
 * Uso em controllers: requirePermission('financeiro', 'excluir');
 */
function requirePermission(string $modulo, string $acao = 'visualizar'): void
{
    if (!can($modulo, $acao)) {
        session()->setFlashdata('error', 'Acesso negado. Você não tem permissão para esta ação.');
        header('Location: ' . base_url('dashboard'));
        exit;
    }
}
