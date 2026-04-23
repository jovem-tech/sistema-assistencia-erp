-- =====================================================
-- SISTEMA DE ASSISTÊNCIA TÉCNICA - Database Schema
-- Banco: assistencia_tecnica
-- Collation: utf8mb4_unicode_ci
-- =====================================================

CREATE DATABASE IF NOT EXISTS assistencia_tecnica 
    CHARACTER SET utf8mb4 
    COLLATE utf8mb4_unicode_ci;

USE assistencia_tecnica;

-- 1. TABELA DE USUÁRIOS
CREATE TABLE IF NOT EXISTS usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    telefone VARCHAR(20),
    perfil ENUM('admin', 'tecnico', 'atendente') DEFAULT 'atendente',
    foto VARCHAR(255),
    ativo TINYINT(1) DEFAULT 1,
    ultimo_acesso DATETIME,
    token_recuperacao VARCHAR(255) NULL,
    token_expiracao DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. TABELA DE CLIENTES
CREATE TABLE IF NOT EXISTS clientes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tipo_pessoa ENUM('fisica', 'juridica') DEFAULT 'fisica',
    nome_razao VARCHAR(100) NOT NULL,
    cpf_cnpj VARCHAR(20) UNIQUE,
    rg_ie VARCHAR(20),
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
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_nome (nome_razao),
    INDEX idx_cpf (cpf_cnpj),
    INDEX idx_telefone (telefone1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. TABELA DE EQUIPAMENTOS
CREATE TABLE IF NOT EXISTS equipamentos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cliente_id INT NOT NULL,
    tipo ENUM('notebook', 'desktop', 'celular', 'tablet', 'impressora', 'outros') NOT NULL,
    marca VARCHAR(50) NOT NULL,
    modelo VARCHAR(100) NOT NULL,
    numero_serie VARCHAR(100),
    imei VARCHAR(20),
    senha_acesso VARCHAR(255),
    estado_fisico TEXT,
    acessorios TEXT,
    observacoes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_cliente (cliente_id),
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. TABELA DE PEÇAS (antes da OS para FK)
CREATE TABLE IF NOT EXISTS pecas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    codigo VARCHAR(50) UNIQUE,
    codigo_fabricante VARCHAR(100),
    nome VARCHAR(100) NOT NULL,
    categoria VARCHAR(50),
    modelos_compativeis TEXT,
    fornecedor VARCHAR(100),
    localizacao VARCHAR(50),
    preco_custo DECIMAL(10,2) NOT NULL DEFAULT 0,
    preco_venda DECIMAL(10,2) NOT NULL DEFAULT 0,
    quantidade_atual INT DEFAULT 0,
    estoque_minimo INT DEFAULT 1,
    estoque_maximo INT,
    foto VARCHAR(255),
    observacoes TEXT,
    ativo TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_nome (nome),
    INDEX idx_categoria (categoria)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. TABELA DE ORDENS DE SERVIÇO (OS)
CREATE TABLE IF NOT EXISTS os (
    id INT PRIMARY KEY AUTO_INCREMENT,
    numero_os VARCHAR(20) UNIQUE NOT NULL,
    cliente_id INT NOT NULL,
    equipamento_id INT NOT NULL,
    tecnico_id INT NULL,
    status ENUM(
        'aguardando_analise', 
        'aguardando_orcamento',
        'aguardando_aprovacao',
        'aprovado',
        'reprovado',
        'em_reparo',
        'aguardando_peca',
        'pronto',
        'entregue',
        'cancelado'
    ) DEFAULT 'aguardando_analise',
    prioridade ENUM('baixa', 'normal', 'alta', 'urgente') DEFAULT 'normal',
    relato_cliente TEXT NOT NULL,
    diagnostico_tecnico TEXT,
    solucao_aplicada TEXT,
    acessorios TEXT,
    forma_pagamento VARCHAR(30),
    data_abertura DATETIME DEFAULT CURRENT_TIMESTAMP,
    data_previsao DATE,
    data_conclusao DATETIME,
    data_entrega DATETIME,
    valor_mao_obra DECIMAL(10,2) DEFAULT 0,
    valor_pecas DECIMAL(10,2) DEFAULT 0,
    valor_total DECIMAL(10,2) DEFAULT 0,
    desconto DECIMAL(10,2) DEFAULT 0,
    valor_final DECIMAL(10,2) DEFAULT 0,
    orcamento_aprovado TINYINT(1) DEFAULT 0,
    data_aprovacao DATETIME,
    orcamento_pdf VARCHAR(255),
    garantia_dias INT DEFAULT 90,
    garantia_validade DATE,
    observacoes_internas TEXT,
    observacoes_cliente TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_numero (numero_os),
    INDEX idx_status (status),
    INDEX idx_cliente (cliente_id),
    INDEX idx_tecnico (tecnico_id),
    FOREIGN KEY (cliente_id) REFERENCES clientes(id),
    FOREIGN KEY (equipamento_id) REFERENCES equipamentos(id),
    FOREIGN KEY (tecnico_id) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. ITENS DA OS
CREATE TABLE IF NOT EXISTS os_itens (
    id INT PRIMARY KEY AUTO_INCREMENT,
    os_id INT NOT NULL,
    tipo ENUM('servico', 'peca') NOT NULL,
    descricao VARCHAR(255) NOT NULL,
    quantidade INT DEFAULT 1,
    valor_unitario DECIMAL(10,2) NOT NULL,
    valor_total DECIMAL(10,2) NOT NULL,
    peca_id INT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_os (os_id),
    FOREIGN KEY (os_id) REFERENCES os(id) ON DELETE CASCADE,
    FOREIGN KEY (peca_id) REFERENCES pecas(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. MOVIMENTAÇÃO DE ESTOQUE
CREATE TABLE IF NOT EXISTS movimentacoes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    peca_id INT NOT NULL,
    os_id INT NULL,
    tipo ENUM('entrada', 'saida', 'ajuste') NOT NULL,
    quantidade INT NOT NULL,
    motivo VARCHAR(255),
    responsavel_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_peca (peca_id),
    FOREIGN KEY (peca_id) REFERENCES pecas(id) ON DELETE CASCADE,
    FOREIGN KEY (os_id) REFERENCES os(id) ON DELETE SET NULL,
    FOREIGN KEY (responsavel_id) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. FINANCEIRO
CREATE TABLE IF NOT EXISTS financeiro (
    id INT PRIMARY KEY AUTO_INCREMENT,
    os_id INT NULL,
    tipo ENUM('receber', 'pagar') NOT NULL,
    categoria VARCHAR(50) NOT NULL,
    descricao VARCHAR(255) NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    forma_pagamento ENUM('dinheiro', 'cartao_credito', 'cartao_debito', 'pix', 'boleto', 'transferencia') NULL,
    status ENUM('pendente', 'pago', 'cancelado') DEFAULT 'pendente',
    data_vencimento DATE NOT NULL,
    data_pagamento DATE,
    observacoes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tipo (tipo),
    INDEX idx_status (status),
    INDEX idx_vencimento (data_vencimento),
    FOREIGN KEY (os_id) REFERENCES os(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. FOTOS DOS EQUIPAMENTOS
CREATE TABLE IF NOT EXISTS fotos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    os_id INT NOT NULL,
    tipo ENUM('recepcao', 'reparo', 'conclusao') NOT NULL,
    arquivo VARCHAR(255) NOT NULL,
    descricao VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (os_id) REFERENCES os(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. LOG DE ATIVIDADES
CREATE TABLE IF NOT EXISTS logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NULL,
    acao VARCHAR(50) NOT NULL,
    descricao TEXT,
    ip VARCHAR(45),
    user_agent TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_usuario (usuario_id),
    INDEX idx_acao (acao),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. CONFIGURAÇÕES DO SISTEMA
CREATE TABLE IF NOT EXISTS configuracoes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    chave VARCHAR(100) UNIQUE NOT NULL,
    valor TEXT,
    tipo VARCHAR(20) DEFAULT 'texto',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- DADOS INICIAIS
-- =====================================================

-- Usuário admin padrão (senha: admin123)
INSERT INTO usuarios (nome, email, senha, perfil, ativo) VALUES
('Administrador', 'admin@sistema.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1);

-- Configurações padrão
INSERT INTO configuracoes (chave, valor, tipo) VALUES
('empresa_nome', 'Minha Assistência Técnica', 'texto'),
('empresa_cnpj', '', 'texto'),
('empresa_telefone', '', 'texto'),
('empresa_email', '', 'texto'),
('empresa_endereco', '', 'texto'),
('empresa_logo', '', 'texto'),
('whatsapp_token', '', 'texto'),
('whatsapp_numero', '', 'texto'),
('whatsapp_api_url', '', 'texto'),
('smtp_host', '', 'texto'),
('smtp_user', '', 'texto'),
('smtp_pass', '', 'texto'),
('smtp_port', '587', 'numero'),
('garantia_padrao', '90', 'numero'),
('os_ultimo_numero', '0', 'numero'),
('os_prefixo', 'OS', 'texto'),
('os_ano', '2026', 'numero'),
('moeda', 'R$', 'texto'),
('tema', 'dark', 'texto');
