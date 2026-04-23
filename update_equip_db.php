<?php

$host = 'localhost';
$user = 'root';
$pass = ''; // Senha do XAMPP geralmente é vazia
$db   = 'assistencia_tecnica';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("A conexão falhou: " . $conn->connect_error);
}

// 1. Criar tabela de Tipos
$sqlTipos = "CREATE TABLE IF NOT EXISTS equipamentos_tipos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL UNIQUE,
    ativo TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
if($conn->query($sqlTipos) === TRUE) { echo "Tabela equipamentos_tipos OK.\n"; }

// 2. Criar tabela de Marcas
$sqlMarcas = "CREATE TABLE IF NOT EXISTS equipamentos_marcas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL UNIQUE,
    ativo TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
if($conn->query($sqlMarcas) === TRUE) { echo "Tabela equipamentos_marcas OK.\n"; }

// 3. Criar tabela de Modelos (linkada à Marca)
$sqlModelos = "CREATE TABLE IF NOT EXISTS equipamentos_modelos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    marca_id INT NOT NULL,
    nome VARCHAR(100) NOT NULL,
    ativo TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (marca_id) REFERENCES equipamentos_marcas(id) ON DELETE CASCADE,
    UNIQUE KEY idx_marca_modelo (marca_id, nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
if($conn->query($sqlModelos) === TRUE) { echo "Tabela equipamentos_modelos OK.\n"; }

// 4. Alterar a tabela de equipamentos atual para adicionar as chaves e limpar as antigas
// Adicionar as novas colunas
$conn->query("ALTER TABLE equipamentos ADD COLUMN tipo_id INT NULL AFTER cliente_id");
$conn->query("ALTER TABLE equipamentos ADD COLUMN marca_id INT NULL AFTER tipo_id");
$conn->query("ALTER TABLE equipamentos ADD COLUMN modelo_id INT NULL AFTER marca_id");

// Migração de dados: Tipos
$resTipos = $conn->query("SELECT DISTINCT tipo FROM equipamentos WHERE tipo IS NOT NULL AND tipo != ''");
if ($resTipos) {
    while($row = $resTipos->fetch_assoc()) {
        $tipoNome = $row['tipo'];
        $conn->query("INSERT IGNORE INTO equipamentos_tipos (nome) VALUES ('".$conn->real_escape_string($tipoNome)."')");
        // Update equipamentos
        $conn->query("UPDATE equipamentos SET tipo_id = (SELECT id FROM equipamentos_tipos WHERE nome = '".$conn->real_escape_string($tipoNome)."') WHERE tipo = '".$conn->real_escape_string($tipoNome)."'");
    }
}

// Migração de dados: Marcas
$resMarcas = $conn->query("SELECT DISTINCT marca FROM equipamentos WHERE marca IS NOT NULL AND marca != ''");
if ($resMarcas) {
    while($row = $resMarcas->fetch_assoc()) {
        $marcaNome = $row['marca'];
        $conn->query("INSERT IGNORE INTO equipamentos_marcas (nome) VALUES ('".$conn->real_escape_string($marcaNome)."')");
        // Update equipamentos
        $conn->query("UPDATE equipamentos SET marca_id = (SELECT id FROM equipamentos_marcas WHERE nome = '".$conn->real_escape_string($marcaNome)."') WHERE marca = '".$conn->real_escape_string($marcaNome)."'");
    }
}

// Migração de dados: Modelos
$resModelos = $conn->query("SELECT DISTINCT marca, modelo FROM equipamentos WHERE modelo IS NOT NULL AND modelo != ''");
if ($resModelos) {
    while($row = $resModelos->fetch_assoc()) {
        $marcaNome = $row['marca'];
        $modeloNome = $row['modelo'];
        
        // Obter marca_id
        $marcaRes = $conn->query("SELECT id FROM equipamentos_marcas WHERE nome = '".$conn->real_escape_string($marcaNome)."' LIMIT 1");
        if ($marcaRes && $marcaRow = $marcaRes->fetch_assoc()) {
            $marcaId = $marcaRow['id'];
            $conn->query("INSERT IGNORE INTO equipamentos_modelos (marca_id, nome) VALUES ($marcaId, '".$conn->real_escape_string($modeloNome)."')");
            // Update
            $conn->query("UPDATE equipamentos SET modelo_id = (SELECT id FROM equipamentos_modelos WHERE marca_id = $marcaId AND nome = '".$conn->real_escape_string($modeloNome)."') WHERE marca = '".$conn->real_escape_string($marcaNome)."' AND modelo = '".$conn->real_escape_string($modeloNome)."'");
        }
    }
}

// Finaliza a migração dropando as colunas antigas em texto ou transformando-as
// Pra manter segurança, vamos apenas tentar dropar. Se falhar, nao importa, os idx usarão os ID.
$conn->query("ALTER TABLE equipamentos DROP COLUMN tipo");
$conn->query("ALTER TABLE equipamentos DROP COLUMN marca");
$conn->query("ALTER TABLE equipamentos DROP COLUMN modelo");

// Adicionando contraints
$conn->query("ALTER TABLE equipamentos ADD CONSTRAINT fk_equip_tipo FOREIGN KEY (tipo_id) REFERENCES equipamentos_tipos(id) ON DELETE SET NULL");
$conn->query("ALTER TABLE equipamentos ADD CONSTRAINT fk_equip_marca FOREIGN KEY (marca_id) REFERENCES equipamentos_marcas(id) ON DELETE SET NULL");
$conn->query("ALTER TABLE equipamentos ADD CONSTRAINT fk_equip_modelo FOREIGN KEY (modelo_id) REFERENCES equipamentos_modelos(id) ON DELETE SET NULL");

echo "Migração e criação de tabelas terminada com Sucesso!";
$conn->close();
