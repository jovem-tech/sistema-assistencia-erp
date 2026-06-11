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
            'ExecuÃ§Ã£o',
            'execuÃ§Ã£o',
            'ServiÃ§o',
            'serviÃ§o',
            'OrÃ§amento',
            'orÃ§amento',
            'AprovaÃ§Ã£o',
            'aprovaÃ§Ã£o',
            'AnÃ¡lise',
            'anÃ¡lise',
            'PeÃ§a',
            'peÃ§a',
            'TÃ©cnico',
            'tÃ©cnico',
            'PendÃªncia',
            'pendÃªncia',
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
        'aguardando_analise' => 'Aguard. AnÃ¡lise',
        'aguardando_orcamento' => 'Aguard. OrÃ§amento',
        'aguardando_aprovacao' => 'Aguard. AprovaÃ§Ã£o',
        'aprovado' => 'Aprovado',
        'reprovado' => 'Reprovado',
        'em_reparo' => 'Em Reparo',
        'aguardando_peca' => 'Aguard. PeÃ§a',
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
 * Format date with pt-BR weekday label for operational daily grids.
 */
function formatDateWithWeekdayPtBr($date, $withYear = false)
{
    if (empty($date)) {
        return '-';
    }

    $timestamp = strtotime((string) $date);
    if ($timestamp === false) {
        return '-';
    }

    $weekdays = [
        'domingo',
        'segunda-feira',
        'terÃ§a-feira',
        'quarta-feira',
        'quinta-feira',
        'sexta-feira',
        'sÃ¡bado',
    ];

    $base = date($withYear ? 'd/m/Y' : 'd/m', $timestamp);
    $weekday = $weekdays[(int) date('w', $timestamp)] ?? '';

    return $weekday !== '' ? ($base . ' - ' . $weekday) : $base;
}

/**
 * Format competence date for DRE views.
 * By default, manual/monthly entries are shown as month/year.
 * Exact day is preserved in the UI only for automatic OS-origin entries.
 */
function formatCompetenceDate($date, $originType = null)
{
    if (empty($date)) {
        return '-';
    }

    $timestamp = strtotime((string) $date);
    if ($timestamp === false) {
        return '-';
    }

    $originType = trim((string) $originType);

    if ($originType === 'os') {
        return date('d/m/Y', $timestamp);
    }

    return date('m/Y', $timestamp);
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

if (!function_exists('equipamento_normalize_text')) {
    function equipamento_normalize_text($value): string
    {
        return trim((string) ($value ?? ''));
    }
}

if (!function_exists('equipamento_value_from_keys')) {
    /**
     * @param array<string,mixed> $data
     * @param array<int,string> $keys
     */
    function equipamento_value_from_keys(array $data, array $keys): string
    {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $data)) {
                continue;
            }

            $value = equipamento_normalize_text($data[$key]);
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }
}

if (!function_exists('equipamento_is_desktop_tipo')) {
    function equipamento_is_desktop_tipo($tipo): bool
    {
        $normalized = mb_strtolower(equipamento_normalize_text($tipo));
        return in_array($normalized, ['desktop', 'computador', 'pc'], true);
    }
}

if (!function_exists('equipamento_is_notebook_tipo')) {
    function equipamento_is_notebook_tipo($tipo): bool
    {
        $normalized = mb_strtolower(equipamento_normalize_text($tipo));
        return in_array($normalized, ['notebook', 'laptop'], true);
    }
}

if (!function_exists('equipamento_modalidade_label')) {
    function equipamento_modalidade_label($modalidade): string
    {
        $normalized = mb_strtolower(equipamento_normalize_text($modalidade));
        return match ($normalized) {
            'montado' => 'Desktop montado',
            'oem' => 'Desktop de marca/OEM',
            default => ucfirst($normalized),
        };
    }
}

if (!function_exists('equipamento_configuracao_status_label')) {
    function equipamento_configuracao_status_label($status): string
    {
        $normalized = mb_strtolower(equipamento_normalize_text($status));
        return match ($normalized) {
            'pendente_bancada' => 'Pendente de complemento na bancada',
            'manual' => 'Configuracao manual',
            'completo' => 'Configuracao completa',
            'sincronizado_agente' => 'Sincronizado pelo agente',
            default => ucfirst(str_replace('_', ' ', $normalized)),
        };
    }
}

if (!function_exists('equipamento_format_ram_label')) {
    function equipamento_format_ram_label($value): string
    {
        $raw = equipamento_normalize_text($value);
        if ($raw === '') {
            return '';
        }

        if (preg_match('/^\d+(?:[.,]\d+)?$/', $raw) === 1) {
            $number = (float) str_replace(',', '.', $raw);
            if (abs($number - round($number)) < 0.01) {
                return (string) ((int) round($number)) . ' GB';
            }

            return number_format($number, 2, ',', '.') . ' GB';
        }

        return $raw;
    }
}

if (!function_exists('equipamento_resumo_tecnico')) {
    /**
     * @param array<string,mixed> $data
     */
    function equipamento_resumo_tecnico(array $data): string
    {
        $summary = equipamento_value_from_keys($data, ['resumo_tecnico', 'equip_resumo_tecnico', 'technical_summary']);
        if ($summary !== '') {
            return $summary;
        }

        $gabinete = equipamento_value_from_keys($data, ['gabinete_tipo', 'equip_gabinete_tipo', 'chassis_type']);
        $chipset = equipamento_value_from_keys($data, ['chipset', 'equip_chipset']);
        $placaMae = equipamento_value_from_keys($data, ['placa_mae', 'equip_placa_mae', 'motherboard']);
        $processador = equipamento_value_from_keys($data, ['processador', 'equip_processador', 'cpu']);
        $memoria = equipamento_format_ram_label(
            equipamento_value_from_keys($data, ['memoria_ram', 'equip_memoria_ram', 'ram_gb'])
        );
        $armazenamento = equipamento_value_from_keys($data, ['armazenamento', 'equip_armazenamento', 'storage_summary']);
        $placaVideo = equipamento_value_from_keys($data, ['placa_video', 'equip_placa_video', 'gpu']);
        $fonte = equipamento_value_from_keys($data, ['fonte_alimentacao', 'equip_fonte_alimentacao']);

        $parts = [];
        foreach ([$gabinete, $chipset !== '' ? $chipset : $placaMae, $processador, $memoria, $armazenamento, $placaVideo, $fonte] as $part) {
            $part = equipamento_normalize_text($part);
            if ($part !== '' && !in_array($part, $parts, true)) {
                $parts[] = $part;
            }
        }

        return implode(' | ', $parts);
    }
}

if (!function_exists('equipamento_nome_exibicao')) {
    /**
     * @param array<string,mixed> $data
     */
    function equipamento_nome_exibicao(array $data): string
    {
        $modalidade = mb_strtolower(
            equipamento_value_from_keys($data, ['desktop_modalidade', 'equip_desktop_modalidade'])
        );
        $marca = equipamento_value_from_keys($data, ['marca_nome', 'equip_marca', 'marca', 'manufacturer']);
        $modelo = equipamento_value_from_keys($data, ['modelo_nome', 'equip_modelo', 'modelo', 'model']);
        $resumoTecnico = equipamento_resumo_tecnico($data);

        if ($modalidade === 'montado') {
            return $resumoTecnico !== '' ? $resumoTecnico : 'Desktop montado';
        }

        $catalogName = trim(implode(' ', array_values(array_filter([$marca, $modelo]))));
        if ($catalogName !== '') {
            return $catalogName;
        }

        return $resumoTecnico !== '' ? $resumoTecnico : 'Equipamento';
    }
}

if (!function_exists('equipamento_resumo_tecnico_essencial')) {
    /**
     * @param array<string,mixed> $data
     */
    function equipamento_resumo_tecnico_essencial(array $data): string
    {
        $gabinete = equipamento_value_from_keys($data, ['gabinete_tipo', 'equip_gabinete_tipo', 'chassis_type']);
        $chipset = equipamento_value_from_keys($data, ['chipset', 'equip_chipset']);
        $placaMae = equipamento_value_from_keys($data, ['placa_mae', 'equip_placa_mae', 'motherboard']);
        $processador = equipamento_value_from_keys($data, ['processador', 'equip_processador', 'cpu']);

        $parts = [];
        foreach ([$gabinete, $chipset !== '' ? $chipset : $placaMae, $processador] as $part) {
            $part = equipamento_normalize_text($part);
            if ($part !== '' && !in_array($part, $parts, true)) {
                $parts[] = $part;
            }
        }

        return implode(' | ', $parts);
    }
}

if (!function_exists('equipamento_rotulo_exibicao')) {
    /**
     * @param array<string,mixed> $data
     */
    function equipamento_rotulo_exibicao(array $data, bool $includeType = true): string
    {
        $type = equipamento_value_from_keys($data, ['tipo_nome', 'equip_tipo', 'tipo']);
        $displayName = equipamento_nome_exibicao($data);

        if (!$includeType || $type === '') {
            return $displayName;
        }

        return trim($type . ' - ' . $displayName, ' -');
    }
}

if (!function_exists('equipamento_status_operacional_label')) {
    function equipamento_status_operacional_label($status): string
    {
        $normalized = mb_strtolower(equipamento_normalize_text($status));

        return $normalized === 'encerrado' ? 'Encerrado' : 'Ativo';
    }
}

if (!function_exists('equipamento_motivo_encerramento_label')) {
    function equipamento_motivo_encerramento_label($motivo): string
    {
        $normalized = mb_strtolower(equipamento_normalize_text($motivo));
        $map = [
            'retirada_pecas' => 'Retirada de pecas',
            'irreparavel' => 'Problema irreparavel',
            'descartado' => 'Descartado',
            'pecas_vendidas' => 'Pecas vendidas para outros clientes',
            'outro' => 'Outro motivo',
        ];

        return $map[$normalized] ?? '';
    }
}

if (!function_exists('equipamento_esta_encerrado')) {
    /**
     * @param array<string,mixed> $data
     */
    function equipamento_esta_encerrado(array $data): bool
    {
        $status = mb_strtolower(equipamento_value_from_keys($data, ['status_operacional']));
        if ($status === 'encerrado') {
            return true;
        }

        return equipamento_value_from_keys($data, ['encerrado_em']) !== '';
    }
}

/**
 * Obter valor de configuraÃ§Ã£o
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
 * Retorna a versÃ£o de release do sistema com fallback seguro.
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
 * Retorna o tempo mÃ¡ximo de inatividade da sessÃ£o em minutos.
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
 * Retorna o tempo mÃ¡ximo de inatividade da sessÃ£o em segundos.
 */
function get_session_inactivity_seconds(int $defaultMinutes = 30): int
{
    return get_session_inactivity_minutes($defaultMinutes) * 60;
}

/**
 * Carrega e cacheia no session o mapa de permissÃµes do usuÃ¡rio logado.
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
 * Aborta a requisiÃ§Ã£o com erro 403 se o usuÃ¡rio nÃ£o tiver a permissÃ£o.
 */
function requirePermission(string $modulo, string $acao = 'visualizar'): void
{
    if (!can($modulo, $acao)) {
        session()->setFlashdata('error', 'Acesso negado. VocÃª nÃ£o tem permissÃ£o para esta aÃ§Ã£o.');
        header('Location: ' . base_url('dashboard'));
        exit;
    }
}
