<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddEquipamentoIdentityIndexes extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('equipamentos') && $this->db->fieldExists('numero_serie', 'equipamentos')) {
            $this->createIndex(
                'idx_equipamentos_numero_serie',
                'CREATE INDEX idx_equipamentos_numero_serie ON equipamentos (numero_serie)'
            );
        }

        if ($this->db->tableExists('equipamentos') && $this->db->fieldExists('imei', 'equipamentos')) {
            $this->createIndex(
                'idx_equipamentos_imei',
                'CREATE INDEX idx_equipamentos_imei ON equipamentos (imei)'
            );
        }

        if ($this->db->tableExists('equipamento_clientes')) {
            $this->removeDuplicateLinks();
            $this->createIndex(
                'ux_equipamento_clientes_relacao',
                'CREATE UNIQUE INDEX ux_equipamento_clientes_relacao ON equipamento_clientes (equipamento_id, cliente_id)'
            );
        }
    }

    public function down()
    {
        if ($this->db->tableExists('equipamentos')) {
            $this->dropIndex('idx_equipamentos_numero_serie', 'equipamentos');
            $this->dropIndex('idx_equipamentos_imei', 'equipamentos');
        }

        if ($this->db->tableExists('equipamento_clientes')) {
            $this->dropIndex('ux_equipamento_clientes_relacao', 'equipamento_clientes');
        }
    }

    private function removeDuplicateLinks(): void
    {
        try {
            $duplicates = $this->db->query(
                'SELECT equipamento_id, cliente_id, MIN(id) AS keep_id
                 FROM equipamento_clientes
                 GROUP BY equipamento_id, cliente_id
                 HAVING COUNT(*) > 1'
            )->getResultArray();

            foreach ($duplicates as $duplicate) {
                $equipamentoId = (int) ($duplicate['equipamento_id'] ?? 0);
                $clienteId = (int) ($duplicate['cliente_id'] ?? 0);
                $keepId = (int) ($duplicate['keep_id'] ?? 0);
                if ($equipamentoId <= 0 || $clienteId <= 0 || $keepId <= 0) {
                    continue;
                }

                $this->db->query(
                    'DELETE FROM equipamento_clientes WHERE equipamento_id = ? AND cliente_id = ? AND id != ?',
                    [$equipamentoId, $clienteId, $keepId]
                );
            }
        } catch (\Throwable $e) {
            // Ambiente sem dados compatÃ­veis ou estrutura ainda incompleta.
        }
    }

    private function createIndex(string $name, string $sql): void
    {
        try {
            $this->db->query($sql);
        } catch (\Throwable $e) {
            // Indice jÃ¡ existente ou estrutura ainda nao compativel neste ambiente.
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
