<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTiposAndRevisionsToOrcamentos extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('orcamentos')) {
            return;
        }

        if (!$this->db->fieldExists('tipo_orcamento', 'orcamentos')) {
            $this->db->query("ALTER TABLE orcamentos ADD COLUMN tipo_orcamento VARCHAR(30) NOT NULL DEFAULT 'previo' AFTER versao");
        }

        if (!$this->db->fieldExists('orcamento_revisao_de_id', 'orcamentos')) {
            $this->db->query('ALTER TABLE orcamentos ADD COLUMN orcamento_revisao_de_id BIGINT(20) UNSIGNED NULL AFTER tipo_orcamento');
        }

        $this->db->query("UPDATE orcamentos SET origem = 'conversa' WHERE origem = 'conversa_rapida'");
        $this->db->query("UPDATE orcamentos SET tipo_orcamento = CASE WHEN COALESCE(os_id, 0) > 0 THEN 'assistencia' ELSE 'previo' END WHERE tipo_orcamento IS NULL OR TRIM(tipo_orcamento) = ''");

        $this->ensureIndex('orcamentos', 'idx_orcamentos_tipo_orcamento', 'tipo_orcamento');
        $this->ensureIndex('orcamentos', 'idx_orcamentos_revisao_base', 'orcamento_revisao_de_id');
        $this->ensureIndex('orcamentos', 'idx_orcamentos_tipo_status', 'tipo_orcamento,status');
    }

    public function down()
    {
        if (!$this->db->tableExists('orcamentos')) {
            return;
        }

        $this->dropIndexIfExists('orcamentos', 'idx_orcamentos_tipo_status');
        $this->dropIndexIfExists('orcamentos', 'idx_orcamentos_revisao_base');
        $this->dropIndexIfExists('orcamentos', 'idx_orcamentos_tipo_orcamento');

        if ($this->db->fieldExists('orcamento_revisao_de_id', 'orcamentos')) {
            $this->db->query('ALTER TABLE orcamentos DROP COLUMN orcamento_revisao_de_id');
        }

        if ($this->db->fieldExists('tipo_orcamento', 'orcamentos')) {
            $this->db->query('ALTER TABLE orcamentos DROP COLUMN tipo_orcamento');
        }
    }

    private function ensureIndex(string $table, string $indexName, string $columns): void
    {
        $indexes = $this->db->getIndexData($table);
        if (isset($indexes[$indexName])) {
            return;
        }

        $this->db->query(sprintf('CREATE INDEX %s ON %s (%s)', $indexName, $table, $columns));
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        $indexes = $this->db->getIndexData($table);
        if (!isset($indexes[$indexName])) {
            return;
        }

        $this->db->query(sprintf('DROP INDEX %s ON %s', $indexName, $table));
    }
}
