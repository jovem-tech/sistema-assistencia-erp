<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddOrcamentoLifecycleIndexes extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('crm_followups')) {
            $this->safeCreateIndex('crm_followups', 'idx_crm_followups_origem_evento', '(origem_evento)');
        }

        if ($this->db->tableExists('orcamentos')) {
            $this->safeCreateIndex('orcamentos', 'idx_orcamentos_status_os_validade', '(status, os_id, validade_data)');
        }
    }

    public function down()
    {
        if ($this->db->tableExists('crm_followups')) {
            $this->safeDropIndex('crm_followups', 'idx_crm_followups_origem_evento');
        }

        if ($this->db->tableExists('orcamentos')) {
            $this->safeDropIndex('orcamentos', 'idx_orcamentos_status_os_validade');
        }
    }

    private function safeCreateIndex(string $table, string $indexName, string $columnsSql): void
    {
        try {
            $this->db->query("CREATE INDEX {$indexName} ON {$table} {$columnsSql}");
        } catch (\Throwable $e) {
            // indice ja existe ou engine sem suporte; segue fluxo sem interrupcao.
        }
    }

    private function safeDropIndex(string $table, string $indexName): void
    {
        try {
            $this->db->query("DROP INDEX {$indexName} ON {$table}");
        } catch (\Throwable $e) {
            // indice inexistente; segue fluxo sem interrupcao.
        }
    }
}

