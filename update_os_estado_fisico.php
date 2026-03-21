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
    if ($line === '' || strpos($line, '#') === 0) {
        continue;
    }
    if (!strpos($line, '=')) {
        continue;
    }
    [$key, $value] = array_map('trim', explode('=', $line, 2));
    $value = trim($value, " '\"\t");
    $env[$key] = $value;
}

$host = $env['database.default.hostname'] ?? '127.0.0.1';
$name = $env['database.default.database'] ?? 'assistencia_tecnica';
$user = $env['database.default.username'] ?? 'root';
$password = $env['database.default.password'] ?? '';
$port = $env['database.default.port'] ?? '3306';
$charset = $env['database.default.charset'] ?? 'utf8mb4';

$dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";

try {
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    echo "Erro ao conectar ao banco de dados: " . $e->getMessage() . "\n";
    exit(1);
}

echo "Conectado ao banco `{$name}` em {$host}:{$port}.\n";

$pdo->exec("
CREATE TABLE IF NOT EXISTS `estado_fisico_equipamento` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `os_id` INT NOT NULL,
    `descricao_dano` VARCHAR(255) NOT NULL,
    `tipo` VARCHAR(50) DEFAULT NULL,
    `valores` TEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL,
    INDEX (`os_id`),
    CONSTRAINT `fk_estado_fisico_os` FOREIGN KEY (`os_id`) REFERENCES `os`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE=utf8mb4_unicode_ci;
");

echo "Tabela `estado_fisico_equipamento` garantida.\n";

$pdo->exec("
CREATE TABLE IF NOT EXISTS `estado_fisico_fotos` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `estado_fisico_id` INT UNSIGNED NOT NULL,
    `arquivo` VARCHAR(255) NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL,
    INDEX (`estado_fisico_id`),
    CONSTRAINT `fk_estado_fisico_fotos_item` FOREIGN KEY (`estado_fisico_id`) REFERENCES `estado_fisico_equipamento`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE=utf8mb4_unicode_ci;
");

echo "Tabela `estado_fisico_fotos` garantida.\n";
echo "Atualizacao de estado fisico concluida.\n";
