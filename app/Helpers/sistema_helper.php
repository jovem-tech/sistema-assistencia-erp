<?php

/**
 * Return HTML badge for OS status
 */
function getStatusBadge($status)
{
    static $dynamicCache = null;
    $normalizeStatusLabel = static function (string $label): string {
        $from = [
            'Execucao',
            'execucao',
            'Servico',
            'servico',
            'Orcamento',
            'orcamento',
            'Aprovacao',
            'aprovacao',
            'Analise',
            'analise',
            'Peca',
            'peca',
            'Tecnico',
            'tecnico',
            'Pendencia',
            'pendencia',
        ];
        $to = [
            'Execução',
            'execução',
            'Serviço',
            'serviço',
            'Orçamento',
            'orçamento',
            'Aprovação',
            'aprovação',
            'Análise',
            'análise',
            'Peça',
            'peça',
            'Técnico',
            'técnico',
            'Pendência',
            'pendência',
        ];
        return str_replace($from, $to, $label);
    };

    if ($dynamicCache === null) {
        $dynamicCache = [];
        try {
            $db = \Config\Database::connect();
            if ($db->tableExists('os_status')) {
                $rows = $db->table('os_status')
                    ->select('codigo, nome, cor, icone')
                    ->where('ativo', 1)
                    ->get()
                    ->getResultArray();
                foreach ($rows as $row) {
                    $dynamicCache[$row['codigo']] = $row;
                }
            }
        } catch (\Throwable $e) {
            $dynamicCache = [];
        }
    }

    if (isset($dynamicCache[$status])) {
        $row = $dynamicCache[$status];
        $rawColor = strtolower(trim((string) ($row['cor'] ?? 'secondary')) ?: 'secondary');
        $colorMap = [
            'indigo' => 'primary',
            'purple' => 'primary',
            'orange' => 'warning text-dark',
            'dark' => 'dark',
            'light' => 'light text-dark',
            'secondary' => 'secondary',
            'primary' => 'primary',
            'success' => 'success',
            'warning' => 'warning text-dark',
            'danger' => 'danger',
            'info' => 'info text-dark',
        ];
        $color = $colorMap[$rawColor] ?? 'secondary';
        $icon = trim((string) ($row['icone'] ?? ''));
        $iconHtml = $icon !== '' ? '<i class="bi ' . esc($icon) . ' me-1"></i>' : '';
        $colorClass = str_starts_with($color, 'bg-') ? $color : ('bg-' . $color);
        return '<span class="badge ' . esc($colorClass) . '">' . $iconHtml . esc($normalizeStatusLabel((string) ($row['nome'] ?? $status))) . '</span>';
    }

    $legacy = [
        'aguardando_analise' => 'Aguard. Análise',
        'aguardando_orcamento' => 'Aguard. Orçamento',
        'aguardando_aprovacao' => 'Aguard. Aprovação',
        'aprovado' => 'Aprovado',
        'reprovado' => 'Reprovado',
        'em_reparo' => 'Em Reparo',
        'aguardando_peca' => 'Aguard. Peça',
        'pronto' => 'Pronto',
        'entregue' => 'Entregue',
        'cancelado' => 'Cancelado',
    ];

    $label = $normalizeStatusLabel($legacy[$status] ?? ucfirst(str_replace('_', ' ', (string) $status)));
    return '<span class="badge bg-secondary">' . esc($label) . '</span>';
}

/**
 * Format BRL currency
 */
function formatMoney($value)
{
    return 'R$ ' . number_format((float) $value, 2, ',', '.');
}

/**
 * Format date to BR format
 */
function formatDate($date, $withTime = false)
{
    if (empty($date)) {
        return '-';
    }
    $format = $withTime ? 'd/m/Y H:i' : 'd/m/Y';
    return date($format, strtotime($date));
}

/**
 * Get priority badge
 */
function getPriorityBadge($priority)
{
    $badges = [
        'baixa' => '<span class="badge bg-secondary">Baixa</span>',
        'normal' => '<span class="badge bg-info">Normal</span>',
        'alta' => '<span class="badge bg-warning text-dark">Alta</span>',
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
        'notebook' => 'Notebook',
        'desktop' => 'Desktop',
        'celular' => 'Celular',
        'tablet' => 'Tablet',
        'impressora' => 'Impressora',
        'outros' => 'Outros',
    ];

    return $tipos[$tipo] ?? ucfirst((string) $tipo);
}

/**
 * Obter valor de configuração
 */
function get_config($chave, $default = null)
{
    if (!function_exists('model')) {
        return $default;
    }
    try {
        $db = \Config\Database::connect();
        $builder = $db->table('configuracoes');
        $row = $builder->where('chave', $chave)->get()->getRow();
        return $row ? $row->valor : $default;
    } catch (\Exception $e) {
        return $default;
    }
}

/**
 * Retorna a versão de release do sistema com fallback seguro.
 */
function get_system_version(): string
{
    $releaseConfig = config('SystemRelease');
    $defaultVersion = is_object($releaseConfig) && property_exists($releaseConfig, 'version')
        ? (string) $releaseConfig->version
        : '2.1.0';

    $configuredVersion = trim((string) get_config('sistema_versao', ''));
    if ($configuredVersion !== '') {
        return $configuredVersion;
    }

    return $defaultVersion;
}

/**
 * Retorna o tema atual configurado (light ou dark)
 */
function get_theme()
{
    $theme = get_config('tema', 'dark');
    return $theme === 'light' ? 'light' : 'dark';
}

/**
 * Retorna o tempo máximo de inatividade da sessão em minutos.
 */
function get_session_inactivity_minutes(int $default = 30): int
{
    $value = (int) get_config('sessao_inatividade_minutos', $default);

    if ($value < 5) {
        return 30;
    }

    return min($value, 1440);
}

/**
 * Retorna o tempo máximo de inatividade da sessão em segundos.
 */
function get_session_inactivity_seconds(int $defaultMinutes = 30): int
{
    return get_session_inactivity_minutes($defaultMinutes) * 60;
}

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
        if (session()->get('user_perfil') === 'admin') {
            return ['*' => ['*']];
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
 * Verifica se o usuario logado pode executar uma acao em um modulo.
 */
function can(string $modulo, string $acao): bool
{
    $permissions = loadUserPermissions();
    if (isset($permissions['*'])) {
        return true;
    }

    return isset($permissions[$modulo]) && in_array($acao, $permissions[$modulo], true);
}

/**
 * Verifica se o usuario pode visualizar (acessar) um modulo.
 */
function canModule(string $modulo): bool
{
    return can($modulo, 'visualizar');
}

/**
 * Forca recarregamento do cache de permissoes na sessao.
 */
function refreshPermissions(): void
{
    session()->remove('user_permissions');
}

/**
 * Aborta a requisição com erro 403 se o usuário não tiver a permissão.
 */
function requirePermission(string $modulo, string $acao = 'visualizar'): void
{
    if (!can($modulo, $acao)) {
        session()->setFlashdata('error', 'Acesso negado. Você não tem permissão para esta ação.');
        header('Location: ' . base_url('dashboard'));
        exit;
    }
}
