<?php

$conn = new mysqli('localhost', 'root', '', 'assistencia_tecnica');
if ($conn->connect_error) die("Conexão falhou: " . $conn->connect_error);

// Tabela de Defeitos Comuns
$sql1 = "CREATE TABLE IF NOT EXISTS equipamentos_defeitos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    tipo_id INT NOT NULL,
    classificacao ENUM('hardware', 'software') NOT NULL DEFAULT 'hardware',
    descricao TEXT NULL,
    ativo TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tipo_id) REFERENCES equipamentos_tipos(id) ON DELETE CASCADE,
    INDEX idx_tipo (tipo_id),
    INDEX idx_class (classificacao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($conn->query($sql1) === TRUE) echo "Tabela equipamentos_defeitos criada OK.\n";
else echo "Erro: " . $conn->error . "\n";

// Tabela pivot OS <-> Defeitos
$sql2 = "CREATE TABLE IF NOT EXISTS os_defeitos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    os_id INT NOT NULL,
    defeito_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (os_id) REFERENCES os(id) ON DELETE CASCADE,
    FOREIGN KEY (defeito_id) REFERENCES equipamentos_defeitos(id) ON DELETE CASCADE,
    UNIQUE KEY uq_os_defeito (os_id, defeito_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($conn->query($sql2) === TRUE) echo "Tabela os_defeitos criada OK.\n";
else echo "Erro: " . $conn->error . "\n";

$conn->close();
echo "Concluído!\n";
