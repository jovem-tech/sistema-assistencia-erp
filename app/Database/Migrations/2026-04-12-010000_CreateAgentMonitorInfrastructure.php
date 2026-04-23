<?php

namespace App\Database\Migrations;

use App\Services\AgentMonitor\AgentMonitorSchemaService;
use CodeIgniter\Database\Migration;

class CreateAgentMonitorInfrastructure extends Migration
{
    public function up()
    {
        (new AgentMonitorSchemaService())->ensure();
    }

    public function down()
    {
        if ($this->db->tableExists('monitor_agent_snapshots')) {
            $this->forge->dropTable('monitor_agent_snapshots', true);
        }

        if ($this->db->tableExists('monitor_agents')) {
            $this->forge->dropTable('monitor_agents', true);
        }
    }
}