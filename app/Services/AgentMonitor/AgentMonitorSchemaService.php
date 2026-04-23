<?php

namespace App\Services\AgentMonitor;

class AgentMonitorSchemaService
{
    private $db;
    private $forge;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->forge = \Config\Database::forge();
    }

    public function ensure(): void
    {
        $this->createMonitorAgents();
        $this->createMonitorAgentSnapshots();
    }

    private function createMonitorAgents(): void
    {
        if ($this->db->tableExists('monitor_agents')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'agent_uuid' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => false,
            ],
            'installation_id' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => false,
            ],
            'usuario_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
            ],
            'cliente_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
            ],
            'equipamento_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
            ],
            'os_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
            ],
            'numero_os' => [
                'type' => 'VARCHAR',
                'constraint' => 40,
                'null' => false,
            ],
            'label' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
                'null' => true,
            ],
            'api_token_hash' => [
                'type' => 'CHAR',
                'constraint' => 64,
                'null' => false,
            ],
            'api_token_name' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
                'null' => true,
            ],
            'api_token_expira_em' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'hostname' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
                'null' => true,
            ],
            'serial_number' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
                'null' => true,
            ],
            'manufacturer' => [
                'type' => 'VARCHAR',
                'constraint' => 160,
                'null' => true,
            ],
            'model' => [
                'type' => 'VARCHAR',
                'constraint' => 160,
                'null' => true,
            ],
            'motherboard' => [
                'type' => 'VARCHAR',
                'constraint' => 180,
                'null' => true,
            ],
            'bios_version' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
                'null' => true,
            ],
            'cpu' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'ram_gb' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => true,
            ],
            'windows_caption' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'windows_version' => [
                'type' => 'VARCHAR',
                'constraint' => 80,
                'null' => true,
            ],
            'windows_build' => [
                'type' => 'VARCHAR',
                'constraint' => 80,
                'null' => true,
            ],
            'ultimo_bootstrap_em' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'ultimo_checkin_em' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'ultimo_snapshot_em' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'ativo' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('agent_uuid', 'ux_monitor_agents_uuid');
        $this->forge->addUniqueKey('installation_id', 'ux_monitor_agents_installation');
        $this->forge->addKey('usuario_id', false, false, 'idx_monitor_agents_usuario');
        $this->forge->addKey('cliente_id', false, false, 'idx_monitor_agents_cliente');
        $this->forge->addKey('equipamento_id', false, false, 'idx_monitor_agents_equipamento');
        $this->forge->addKey('numero_os', false, false, 'idx_monitor_agents_numero_os');
        $this->forge->addKey('ultimo_checkin_em', false, false, 'idx_monitor_agents_last_checkin');
        $this->forge->createTable('monitor_agents', true);
    }

    private function createMonitorAgentSnapshots(): void
    {
        if ($this->db->tableExists('monitor_agent_snapshots')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'agent_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => false,
            ],
            'payload_json' => [
                'type' => 'LONGTEXT',
                'null' => false,
            ],
            'hostname' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
                'null' => true,
            ],
            'serial_number' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
                'null' => true,
            ],
            'collected_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'received_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('agent_id', false, false, 'idx_monitor_snapshots_agent');
        $this->forge->addKey('collected_at', false, false, 'idx_monitor_snapshots_collected');
        $this->forge->addKey('received_at', false, false, 'idx_monitor_snapshots_received');
        $this->forge->createTable('monitor_agent_snapshots', true);
    }
}