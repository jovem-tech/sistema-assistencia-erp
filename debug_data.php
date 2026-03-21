<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'assistencia_tecnica';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

echo "--- Companies ---\n";
$res = $conn->query("SELECT id, name, planId FROM Companies");
while($row = $res->fetch_assoc()) {
    print_r($row);
}

echo "--- Plans ---\n";
$res = $conn->query("SELECT id, name FROM Plans");
while($row = $res->fetch_assoc()) {
    print_r($row);
}

$conn->close();
?>
