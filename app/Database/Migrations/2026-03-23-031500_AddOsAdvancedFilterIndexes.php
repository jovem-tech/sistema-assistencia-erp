<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddOsAdvancedFilterIndexes extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('os')) {
            $this->createIndex('idx_os_status', 'CREATE INDEX idx_os_status ON os (status)');
            $this->createIndex('idx_os_estado_fluxo', 'CREATE INDEX idx_os_estado_fluxo ON os (estado_fluxo)');
            $this->createIndex('idx_os_data_abertura', 'CREATE INDEX idx_os_data_abertura ON os (data_abertura)');
            $this->createIndex('idx_os_tecnico_id', 'CREATE INDEX idx_os_tecnico_id ON os (tecnico_id)');
            $this->createIndex('idx_os_valor_final', 'CREATE INDEX idx_os_valor_final ON os (valor_final)');
        }

        if ($this->db->tableExists('os_itens')) {
            $this->createIndex(
                'idx_os_itens_os_tipo_descricao',
                'CREATE INDEX idx_os_itens_os_tipo_descricao ON os_itens (os_id, tipo, descricao)'
            );
        }
    }

    public function down()
    {
        if ($this->db->tableExists('os')) {
            $this->dropIndex('idx_os_status', 'os');
            $this->dropIndex('idx_os_estado_fluxo', 'os');
            $this->dropIndex('idx_os_data_abertura', 'os');
            $this->dropIndex('idx_os_tecnico_id', 'os');
            $this->dropIndex('idx_os_valor_final', 'os');
        }

        if ($this->db->tableExists('os_itens')) {
            $this->dropIndex('idx_os_itens_os_tipo_descricao', 'os_itens');
        }
    }

    private function createIndex(string $name, string $sql): void
    {
        try {
            $this->db->query($sql);
        } catch (\Throwable $e) {
            // Indice ja existente ou incompatibilidade de estrutura.
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
