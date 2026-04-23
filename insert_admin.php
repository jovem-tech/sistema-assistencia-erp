<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'assistencia_tecnica';
$conn = new mysqli($host, $user, $pass, $db);
$passwordHash = '$2a$08$Zpw7B5vR7Q2f8R5A5Y5A5e5B5C5D5E5F5G5H5I5J5K5L5M5N5O5P5Q';
$sql = "INSERT IGNORE INTO Users (id, name, email, passwordHash, profile, companyId, createdAt, updatedAt) 
        VALUES (1, 'Administrador', 'admin@admin.com', ?, 'admin', 1, NOW(), NOW())";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $passwordHash);
$stmt->execute();
echo "Usuário admin inserido.\n";
$conn->close();
