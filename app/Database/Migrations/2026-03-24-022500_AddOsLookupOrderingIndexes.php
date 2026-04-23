<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddOsLookupOrderingIndexes extends Migration
{
    public function up()
    {
        if (
            $this->db->tableExists('os')
            && $this->db->fieldExists('cliente_id', 'os')
            && $this->db->fieldExists('data_abertura', 'os')
            && $this->db->fieldExists('id', 'os')
        ) {
            $this->createIndex(
                'idx_os_cliente_data_abertura_id',
                'CREATE INDEX idx_os_cliente_data_abertura_id ON os (cliente_id, data_abertura, id)'
            );
        }

        if (
            $this->db->tableExists('os')
            && $this->db->fieldExists('equipamento_id', 'os')
            && $this->db->fieldExists('data_abertura', 'os')
            && $this->db->fieldExists('id', 'os')
        ) {
            $this->createIndex(
                'idx_os_equipamento_data_abertura_id',
                'CREATE INDEX idx_os_equipamento_data_abertura_id ON os (equipamento_id, data_abertura, id)'
            );
        }
    }

    public function down()
    {
        if ($this->db->tableExists('os')) {
            $this->dropIndex('idx_os_cliente_data_abertura_id', 'os');
            $this->dropIndex('idx_os_equipamento_data_abertura_id', 'os');
        }
    }

    private function createIndex(string $name, string $sql): void
    {
        try {
            $this->db->query($sql);
        } catch (\Throwable $e) {
            // Indice ja existente ou estrutura ainda nao compativel neste ambiente.
        }
    }

    private function dropIndex(string $name, string $table): void
    {
        try {
            $this->db->query("DROP INDEX {$name} ON {$table}");
        } catch (\Throwable $e) {
            // Indice nao existe.
        }
    }
}
