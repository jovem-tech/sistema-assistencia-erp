<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'assistencia_tecnica';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

echo "Corrigindo colunas faltantes em tabelas existentes...\n";

// Companies
$conn->query("ALTER TABLE Companies ADD COLUMN IF NOT EXISTS phone VARCHAR(255) AFTER name");
$conn->query("ALTER TABLE Companies ADD COLUMN IF NOT EXISTS email VARCHAR(255) AFTER phone");

// Whatsapps
$conn->query("ALTER TABLE Whatsapps ADD COLUMN IF NOT EXISTS integrationId INT AFTER promptId");
$conn->query("ALTER TABLE Whatsapps ADD COLUMN IF NOT EXISTS transferQueueId INT AFTER integrationId");
$conn->query("ALTER TABLE Whatsapps ADD COLUMN IF NOT EXISTS timeToTransfer INT AFTER transferQueueId");
$conn->query("ALTER TABLE Whatsapps ADD COLUMN IF NOT EXISTS maxUseBotQueues INT AFTER timeToTransfer");
$conn->query("ALTER TABLE Whatsapps ADD COLUMN IF NOT EXISTS timeUseBotQueues VARCHAR(255) AFTER maxUseBotQueues");
$conn->query("ALTER TABLE Whatsapps ADD COLUMN IF NOT EXISTS expiresTicket INT AFTER timeUseBotQueues");
$conn->query("ALTER TABLE Whatsapps ADD COLUMN IF NOT EXISTS expiresInactiveMessage VARCHAR(255) AFTER expiresTicket");

// Users
$conn->query("ALTER TABLE Users ADD COLUMN IF NOT EXISTS super TINYINT(1) DEFAULT 0 AFTER companyId");
$conn->query("ALTER TABLE Users ADD COLUMN IF NOT EXISTS allTicket VARCHAR(255) DEFAULT 'enabled' AFTER online");

// Plans
$conn->query("ALTER TABLE Plans ADD COLUMN IF NOT EXISTS useSchedules TINYINT(1) DEFAULT 1 AFTER value");
$conn->query("ALTER TABLE Plans ADD COLUMN IF NOT EXISTS useCampaigns TINYINT(1) DEFAULT 1 AFTER useSchedules");
$conn->query("ALTER TABLE Plans ADD COLUMN IF NOT EXISTS useInternalChat TINYINT(1) DEFAULT 1 AFTER useCampaigns");
$conn->query("ALTER TABLE Plans ADD COLUMN IF NOT EXISTS useExternalApi TINYINT(1) DEFAULT 1 AFTER useInternalChat");
$conn->query("ALTER TABLE Plans ADD COLUMN IF NOT EXISTS useKanban TINYINT(1) DEFAULT 1 AFTER useExternalApi");
$conn->query("ALTER TABLE Plans ADD COLUMN IF NOT EXISTS useOpenAi TINYINT(1) DEFAULT 1 AFTER useKanban");
$conn->query("ALTER TABLE Plans ADD COLUMN IF NOT EXISTS useIntegrations TINYINT(1) DEFAULT 1 AFTER useOpenAi");
$conn->query("ALTER TABLE Plans ADD COLUMN IF NOT EXISTS useInternal TINYINT(1) DEFAULT 1 AFTER useIntegrations");

// Invoices
$conn->query("ALTER TABLE Invoices ADD COLUMN IF NOT EXISTS detail TEXT AFTER id");

// Data fixes
$conn->query("INSERT IGNORE INTO Plans (id, name, users, connections, queues, value, createdAt, updatedAt) VALUES (1, 'Plano Pro', 99, 99, 99, 0, NOW(), NOW())");
$conn->query("UPDATE Companies SET planId = 1 WHERE planId IS NULL");

echo "Colunas e dados corrigidos com sucesso!\n";

$conn->close();
?>
