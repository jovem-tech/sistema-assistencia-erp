<?php
/**
 * RBAC Setup Script - Sistema de Controle de Acesso baseado em Grupos
 * Executa em: http://localhost:8081/setup_rbac.php
 */

$conn = new mysqli('localhost', 'root', '', 'assistencia_tecnica');
if ($conn->connect_error) die("Conexão falhou: " . $conn->connect_error);

$conn->set_charset('utf8mb4');
echo "<pre>";

// ─── 1. Tabela grupos ────────────────────────────────────────────────────────
$conn->query("CREATE TABLE IF NOT EXISTS grupos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(80) NOT NULL,
    descricao VARCHAR(200) NULL,
    sistema TINYINT(1) DEFAULT 0 COMMENT '1 = protegido, não pode ser excluído',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
echo "✓ Tabela grupos\n";

// ─── 2. Tabela modulos ───────────────────────────────────────────────────────
$conn->query("CREATE TABLE IF NOT EXISTS modulos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(80) NOT NULL,
    slug VARCHAR(80) NOT NULL UNIQUE COMMENT 'Chave de referência no código',
    icone VARCHAR(60) NULL,
    ordem_menu INT DEFAULT 0,
    ativo TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
echo "✓ Tabela modulos\n";

// ─── 3. Tabela permissoes ────────────────────────────────────────────────────
$conn->query("CREATE TABLE IF NOT EXISTS permissoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(80) NOT NULL,
    slug VARCHAR(80) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
echo "✓ Tabela permissoes\n";

// ─── 4. Tabela grupo_permissoes ──────────────────────────────────────────────
$conn->query("CREATE TABLE IF NOT EXISTS grupo_permissoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    grupo_id INT NOT NULL,
    modulo_id INT NOT NULL,
    permissao_id INT NOT NULL,
    FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE CASCADE,
    FOREIGN KEY (modulo_id) REFERENCES modulos(id) ON DELETE CASCADE,
    FOREIGN KEY (permissao_id) REFERENCES permissoes(id) ON DELETE CASCADE,
    UNIQUE KEY uq_grupo_modulo_perm (grupo_id, modulo_id, permissao_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
echo "✓ Tabela grupo_permissoes\n";

// ─── 5. Adiciona grupo_id nos usuários ───────────────────────────────────────
$col = $conn->query("SHOW COLUMNS FROM usuarios LIKE 'grupo_id'");
if ($col->num_rows === 0) {
    $conn->query("ALTER TABLE usuarios ADD COLUMN grupo_id INT NULL AFTER perfil,
                  ADD FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE SET NULL");
    echo "✓ Coluna grupo_id adicionada em usuarios\n";
} else {
    echo "→ Coluna grupo_id já existe em usuarios\n";
}

// ─── 6. Seed: Grupos padrão ──────────────────────────────────────────────────
$grupos = [
    ['nome' => 'Administrador', 'descricao' => 'Acesso total ao sistema. Não pode ser excluído.', 'sistema' => 1],
    ['nome' => 'Técnico',       'descricao' => 'Acesso a OS, Equipamentos e Estoque. Sem Financeiro.', 'sistema' => 1],
    ['nome' => 'Atendente',     'descricao' => 'Acesso a Clientes, OS, Equipamentos. Sem Administração.', 'sistema' => 0],
];
foreach ($grupos as $g) {
    $ex = $conn->query("SELECT id FROM grupos WHERE nome = '{$g['nome']}'")->num_rows;
    if (!$ex) {
        $conn->query("INSERT INTO grupos (nome, descricao, sistema) VALUES ('{$g['nome']}', '{$g['descricao']}', {$g['sistema']})");
    }
}
echo "✓ Grupos padrão inseridos\n";

// ─── 7. Seed: Módulos do sistema ─────────────────────────────────────────────
$modulos = [
    ['dashboard',          'Dashboard',           'bi-grid-1x2-fill', 1],
    ['clientes',           'Clientes',            'bi-person-badge', 10],
    ['fornecedores',       'Fornecedores',        'bi-truck', 11],
    ['funcionarios',       'Funcionários',        'bi-person-workspace', 12],
    ['usuarios',           'Usuários',            'bi-person-gear', 13],
    ['grupos',             'Grupos de Acesso',    'bi-shield-lock', 14],
    ['equipamentos',       'Equipamentos',        'bi-laptop', 20],
    ['os',                 'Ordens de Serviço',   'bi-clipboard-check', 30],
    ['estoque',            'Estoque',             'bi-box-seam-fill', 40],
    ['financeiro',         'Financeiro',          'bi-currency-dollar', 50],
    ['relatorios',         'Relatórios',          'bi-graph-up-arrow', 60],
    ['configuracoes',      'Configurações',       'bi-gear-fill', 70],
];
foreach ($modulos as [$slug, $nome, $icon, $order]) {
    $ex = $conn->query("SELECT id FROM modulos WHERE slug = '$slug'")->num_rows;
    if (!$ex) {
        $conn->query("INSERT INTO modulos (nome, slug, icone, ordem_menu) VALUES ('$nome','$slug','$icon',$order)");
    }
}
echo "✓ Módulos padrão inseridos\n";

// ─── 8. Seed: Permissões ─────────────────────────────────────────────────────
$permissoes = [
    ['Visualizar', 'visualizar'],
    ['Criar',      'criar'],
    ['Editar',     'editar'],
    ['Excluir',    'excluir'],
    ['Exportar',   'exportar'],
    ['Importar',   'importar'],
];
foreach ($permissoes as [$nome, $slug]) {
    $ex = $conn->query("SELECT id FROM permissoes WHERE slug = '$slug'")->num_rows;
    if (!$ex) {
        $conn->query("INSERT INTO permissoes (nome, slug) VALUES ('$nome','$slug')");
    }
}
echo "✓ Permissões padrão inseridas\n";

// ─── 9. Seed: Permissões do Grupo Administrador (acesso total) ───────────────
$adminGrupo = $conn->query("SELECT id FROM grupos WHERE nome = 'Administrador'")->fetch_assoc();
$todosModulos = $conn->query("SELECT id FROM modulos")->fetch_all(MYSQLI_ASSOC);
$todasPerms   = $conn->query("SELECT id FROM permissoes")->fetch_all(MYSQLI_ASSOC);

foreach ($todosModulos as $mod) {
    foreach ($todasPerms as $perm) {
        $conn->query("INSERT IGNORE INTO grupo_permissoes (grupo_id, modulo_id, permissao_id)
                      VALUES ({$adminGrupo['id']}, {$mod['id']}, {$perm['id']})");
    }
}
echo "✓ Administrador: acesso total configurado\n";

// ─── 10. Seed: Técnico — sem financeiro, sem administração, sem exclusão de OS ─
$tecnicoGrupo = $conn->query("SELECT id FROM grupos WHERE nome = 'Técnico'")->fetch_assoc();
$tecnicoModulos = ['dashboard', 'clientes', 'equipamentos', 'os', 'estoque'];
$tecnicoPerms   = ['visualizar', 'criar', 'editar'];

foreach ($tecnicoModulos as $slug) {
    $mod = $conn->query("SELECT id FROM modulos WHERE slug = '$slug'")->fetch_assoc();
    foreach ($tecnicoPerms as $permSlug) {
        $perm = $conn->query("SELECT id FROM permissoes WHERE slug = '$permSlug'")->fetch_assoc();
        if ($mod && $perm) {
            $conn->query("INSERT IGNORE INTO grupo_permissoes (grupo_id, modulo_id, permissao_id)
                          VALUES ({$tecnicoGrupo['id']}, {$mod['id']}, {$perm['id']})");
        }
    }
}
echo "✓ Técnico: permissões configuradas\n";

// ─── 11. Seed: Atendente — sem financeiro, sem administração ─────────────────
$atendenteGrupo = $conn->query("SELECT id FROM grupos WHERE nome = 'Atendente'")->fetch_assoc();
$atendenteModulos = ['dashboard', 'clientes', 'fornecedores', 'equipamentos', 'os', 'estoque'];
$atendentePerms   = ['visualizar', 'criar', 'editar'];

foreach ($atendenteModulos as $slug) {
    $mod = $conn->query("SELECT id FROM modulos WHERE slug = '$slug'")->fetch_assoc();
    foreach ($atendentePerms as $permSlug) {
        $perm = $conn->query("SELECT id FROM permissoes WHERE slug = '$permSlug'")->fetch_assoc();
        if ($mod && $perm) {
            $conn->query("INSERT IGNORE INTO grupo_permissoes (grupo_id, modulo_id, permissao_id)
                          VALUES ({$atendenteGrupo['id']}, {$mod['id']}, {$perm['id']})");
        }
    }
}
echo "✓ Atendente: permissões configuradas\n";

// ─── 12. Sincroniza usuarios existentes com grupo via perfil ─────────────────
$conn->query("UPDATE usuarios u
              JOIN grupos g ON g.nome = CASE u.perfil
                  WHEN 'admin'      THEN 'Administrador'
                  WHEN 'tecnico'    THEN 'Técnico'
                  WHEN 'atendente'  THEN 'Atendente'
              END
              SET u.grupo_id = g.id
              WHERE u.grupo_id IS NULL");
echo "✓ Usuários sincronizados com grupos\n";

$conn->close();
echo "\n✅ Setup RBAC concluído com sucesso!\n";
echo "</pre>";
