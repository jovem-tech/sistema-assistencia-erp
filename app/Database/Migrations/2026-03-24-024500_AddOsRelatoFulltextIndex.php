<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddOsRelatoFulltextIndex extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('os') && $this->db->fieldExists('relato_cliente', 'os')) {
            $this->createIndex(
                'idx_os_relato_cliente_fulltext',
                'ALTER TABLE os ADD FULLTEXT idx_os_relato_cliente_fulltext (relato_cliente)'
            );
        }
    }

    public function down()
    {
        if ($this->db->tableExists('os')) {
            $this->dropIndex('idx_os_relato_cliente_fulltext', 'os');
        }
    }

    private function createIndex(string $name, string $sql): void
    {
        try {
            $this->db->query($sql);
        } catch (\Throwable $e) {
            // Indice ja existente ou engine sem suporte neste ambiente.
        }
    }

    private function dropIndex(string $name, string $table): void
    {
        try {
            $this->db->query("ALTER TABLE {$table} DROP INDEX {$name}");
        } catch (\Throwable $e) {
            // Indice nao existe.
        }
    }
}
