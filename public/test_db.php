<?php
define('ENVIRONMENT', 'development');
require __DIR__ . '/../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/bootstrap.php';
$db = \Config\Database::connect();
try {
    $res = $db->query('SELECT COUNT(*) as total FROM clientes')->getRow();
    echo 'CLIENTES: ' . ($res->total ?? 0) . "\n";
    $res2 = $db->query('SELECT COUNT(*) as total FROM crm_interacoes')->getRow();
    echo 'INTERACOES: ' . ($res2->total ?? 0) . "\n";
} catch (\Throwable $e) {
    echo 'ERROR: ' . $e->getMessage() . "\n";
}
