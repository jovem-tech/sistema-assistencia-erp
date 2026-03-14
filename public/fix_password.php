<?php
// Quick script to set admin password
$db = new mysqli('localhost', 'root', '', 'assistencia_tecnica');
$hash = password_hash('admin123', PASSWORD_BCRYPT);
$db->query("UPDATE usuarios SET senha='$hash' WHERE email='admin@sistema.com'");
echo "Password updated. Hash: $hash\n";
echo "Rows affected: " . $db->affected_rows . "\n";
$db->close();
