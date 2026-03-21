<?php

$envFile = __DIR__ . '/.env';
if (!file_exists($envFile)) {
    echo "Arquivo .env nao encontrado.\n";
    exit(1);
}

$lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$env = [];
foreach ($lines as $line) {
    $line = trim($line);
    if ($line === '' || strpos($line, '#') === 0) continue;
    if (!strpos($line, '=')) continue;
    [$key, $value] = array_map('trim', explode('=', $line, 2));
    $env[$key] = trim($value, " '\"\t");
}

$host = $env['database.default.hostname'] ?? '127.0.0.1';
$name = $env['database.default.database'] ?? 'assistencia_tecnica';
$user = $env['database.default.username'] ?? 'root';
$pass = $env['database.default.password'] ?? '';
$port = $env['database.default.port'] ?? '3306';
$charset = $env['database.default.charset'] ?? 'utf8mb4';

$dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    echo "Erro ao conectar ao banco: " . $e->getMessage() . "\n";
    exit(1);
}

echo "Conectado ao banco `{$name}`.\n";

$pdo->exec("
CREATE TABLE IF NOT EXISTS `defeitos_relatados` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `categoria` VARCHAR(80) NOT NULL,
    `texto_relato` VARCHAR(255) NOT NULL,
    `icone` VARCHAR(20) DEFAULT NULL,
    `ordem_exibicao` INT NOT NULL DEFAULT 0,
    `ativo` TINYINT(1) NOT NULL DEFAULT 1,
    `slug` VARCHAR(120) DEFAULT NULL,
    `observacoes` TEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_defeitos_relatados_slug` (`slug`),
    KEY `idx_defeitos_relatados_categoria` (`categoria`),
    KEY `idx_defeitos_relatados_ativo` (`ativo`)
) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE=utf8mb4_unicode_ci;
");

echo "Tabela `defeitos_relatados` garantida.\n";

$count = (int)($pdo->query("SELECT COUNT(*) AS total FROM defeitos_relatados")->fetch()['total'] ?? 0);
if ($count > 0) {
    echo "Tabela ja possui registros. Seed inicial ignorado.\n";
    exit(0);
}

$seed = [
    ['Energia', 'Aparelho nao liga', '🔧', 10, 'energia-aparelho-nao-liga'],
    ['Energia', 'Nao da sinal de vida', '🔧', 20, 'energia-nao-da-sinal-de-vida'],
    ['Energia', 'Nao carrega ou carrega muito devagar', '🔧', 30, 'energia-nao-carrega'],
    ['Bateria', 'Bateria descarregando muito rapido', '🔋', 10, 'bateria-descarregando-rapido'],
    ['Bateria', 'Bateria estufada', '🔋', 20, 'bateria-estufada'],
    ['Tela', 'Tela quebrada / trincada', '📱', 10, 'tela-quebrada-trincada'],
    ['Tela', 'Touch nao funciona', '📱', 20, 'tela-touch-nao-funciona'],
    ['Audio', 'Sem som no alto-falante', '🔊', 10, 'audio-sem-som-alto-falante'],
    ['Camera', 'Camera nao abre', '📷', 10, 'camera-nao-abre'],
    ['Conectividade', 'Wi-Fi nao conecta', '📡', 10, 'conectividade-wifi-nao-conecta'],
    ['Sistema', 'Sistema travando muito', '💾', 10, 'sistema-travando-muito'],
    ['Danos', 'Caiu no chao', '💧', 10, 'danos-caiu-no-chao'],
    ['Conectores', 'Conector de carga frouxo', '🔌', 10, 'conectores-carga-frouxo'],
];

$stmt = $pdo->prepare("
INSERT INTO defeitos_relatados
(`categoria`, `texto_relato`, `icone`, `ordem_exibicao`, `ativo`, `slug`)
VALUES (?, ?, ?, ?, 1, ?)
");

foreach ($seed as $row) {
    $stmt->execute([$row[0], $row[1], $row[2], $row[3], $row[4]]);
}

echo "Seed inicial de defeitos relatados inserido.\n";
echo "Concluido.\n";
