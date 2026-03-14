<?php
$conn = new mysqli('localhost', 'root', '', 'assistencia_tecnica');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql1 = "ALTER TABLE clientes ADD COLUMN nome_contato VARCHAR(100) NULL AFTER telefone2;";
$sql2 = "ALTER TABLE clientes ADD COLUMN telefone_contato VARCHAR(20) NULL AFTER nome_contato;";

$conn->query($sql1);
$conn->query($sql2);

echo "Columns added.";
$conn->close();
