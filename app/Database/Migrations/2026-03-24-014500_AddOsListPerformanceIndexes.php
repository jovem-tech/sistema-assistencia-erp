<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddOsListPerformanceIndexes extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('os')) {
            if ($this->db->fieldExists('data_abertura', 'os') && $this->db->fieldExists('id', 'os')) {
                $this->createIndex(
                    'idx_os_data_abertura_id',
                    'CREATE INDEX idx_os_data_abertura_id ON os (data_abertura, id)'
                );
            }

            if (
                $this->db->fieldExists('status', 'os')
                && $this->db->fieldExists('data_abertura', 'os')
                && $this->db->fieldExists('id', 'os')
            ) {
                $this->createIndex(
                    'idx_os_status_data_abertura_id',
                    'CREATE INDEX idx_os_status_data_abertura_id ON os (status, data_abertura, id)'
                );
            }

            if (
                $this->db->fieldExists('estado_fluxo', 'os')
                && $this->db->fieldExists('data_abertura', 'os')
                && $this->db->fieldExists('id', 'os')
            ) {
                $this->createIndex(
                    'idx_os_estado_fluxo_data_abertura_id',
                    'CREATE INDEX idx_os_estado_fluxo_data_abertura_id ON os (estado_fluxo, data_abertura, id)'
                );
            }

            if (
                $this->db->fieldExists('tecnico_id', 'os')
                && $this->db->fieldExists('data_abertura', 'os')
                && $this->db->fieldExists('id', 'os')
            ) {
                $this->createIndex(
                    'idx_os_tecnico_data_abertura_id',
                    'CREATE INDEX idx_os_tecnico_data_abertura_id ON os (tecnico_id, data_abertura, id)'
                );
            }

            if ($this->db->fieldExists('valor_final', 'os') && $this->db->fieldExists('id', 'os')) {
                $this->createIndex(
                    'idx_os_valor_final_id',
                    'CREATE INDEX idx_os_valor_final_id ON os (valor_final, id)'
                );
            }
        }

        if (
            $this->db->tableExists('os_itens')
            && $this->db->fieldExists('tipo', 'os_itens')
            && $this->db->fieldExists('descricao', 'os_itens')
            && $this->db->fieldExists('os_id', 'os_itens')
        ) {
            $this->createIndex(
                'idx_os_itens_tipo_descricao_os_id',
                'CREATE INDEX idx_os_itens_tipo_descricao_os_id ON os_itens (tipo, descricao, os_id)'
            );
        }
    }

    public function down()
    {
        if ($this->db->tableExists('os')) {
            $this->dropIndex('idx_os_data_abertura_id', 'os');
            $this->dropIndex('idx_os_status_data_abertura_id', 'os');
            $this->dropIndex('idx_os_estado_fluxo_data_abertura_id', 'os');
            $this->dropIndex('idx_os_tecnico_data_abertura_id', 'os');
            $this->dropIndex('idx_os_valor_final_id', 'os');
        }

        if ($this->db->tableExists('os_itens')) {
            $this->dropIndex('idx_os_itens_tipo_descricao_os_id', 'os_itens');
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
