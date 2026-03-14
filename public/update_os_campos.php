<?php

$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'assistencia_tecnica';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("A conexão falhou: " . $conn->connect_error);
}

function addColumnIfMissing(mysqli $conn, string $table, string $column, string $sql)
{
    $res = $conn->query("SHOW COLUMNS FROM {$table} LIKE '{$column}'");
    if ($res && $res->num_rows === 0) {
        if ($conn->query($sql) === TRUE) {
            echo "Coluna {$column} adicionada.\n";
        }
    } else {
        echo "Coluna {$column} já existe.\n";
    }
}

addColumnIfMissing(
    $conn,
    'os',
    'acessorios',
    "ALTER TABLE os ADD COLUMN acessorios TEXT NULL AFTER solucao_aplicada"
);

addColumnIfMissing(
    $conn,
    'os',
    'forma_pagamento',
    "ALTER TABLE os ADD COLUMN forma_pagamento VARCHAR(30) NULL AFTER acessorios"
);

echo "Atualização concluída.\n";
$conn->close();
