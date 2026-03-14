<?php
$host = 'localhost';
$user = 'root'; // Padrão XAMPP
$pass = '';     // Padrão XAMPP
$dbname = 'assistencia_tecnica';

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sqlFornecedores = "
CREATE TABLE IF NOT EXISTS fornecedores (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tipo_pessoa ENUM('fisica', 'juridica') DEFAULT 'juridica',
    nome_fantasia VARCHAR(100) NOT NULL,
    razao_social VARCHAR(100),
    cnpj_cpf VARCHAR(20) UNIQUE,
    ie_rg VARCHAR(20),
    email VARCHAR(100),
    telefone1 VARCHAR(20) NOT NULL,
    telefone2 VARCHAR(20),
    cep VARCHAR(10),
    endereco VARCHAR(100),
    numero VARCHAR(10),
    complemento VARCHAR(50),
    bairro VARCHAR(50),
    cidade VARCHAR(50),
    uf CHAR(2),
    observacoes TEXT,
    ativo TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

$sqlFuncionarios = "
CREATE TABLE IF NOT EXISTS funcionarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    cpf VARCHAR(20) UNIQUE NOT NULL,
    rg VARCHAR(20),
    data_nascimento DATE,
    cargo VARCHAR(50),
    salario DECIMAL(10,2),
    data_admissao DATE,
    data_demissao DATE,
    email VARCHAR(100),
    telefone VARCHAR(20) NOT NULL,
    cep VARCHAR(10),
    endereco VARCHAR(100),
    numero VARCHAR(10),
    complemento VARCHAR(50),
    bairro VARCHAR(50),
    cidade VARCHAR(50),
    uf CHAR(2),
    observacoes TEXT,
    ativo TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

if ($conn->query($sqlFornecedores) === TRUE) {
    echo "Tabela fornecedores criada com sucesso.\n";
} else {
    echo "Erro ao criar tabela fornecedores: " . $conn->error . "\n";
}

if ($conn->query($sqlFuncionarios) === TRUE) {
    echo "Tabela funcionarios criada com sucesso.\n";
} else {
    echo "Erro ao criar tabela funcionarios: " . $conn->error . "\n";
}

$conn->close();
