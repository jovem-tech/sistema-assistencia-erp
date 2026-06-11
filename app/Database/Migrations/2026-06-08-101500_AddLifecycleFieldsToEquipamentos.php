<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddLifecycleFieldsToEquipamentos extends Migration
{
    private string $table = 'equipamentos';
    private string $idxStatus = 'idx_equipamentos_status_operacional';
    private string $idxStatusEncerrado = 'idx_equipamentos_status_encerrado_em';

    public function up()
    {
        if (! $this->db->tableExists($this->table)) {
            return;
        }

        $afterBase = $this->resolveAfterField([
            'legacy_id',
            'legacy_origem',
            'configuracao_detectada_em',
            'updated_at',
            'created_at',
            'observacoes',
        ]);

        $this->addColumnIfMissing('status_operacional', "VARCHAR(20) NOT NULL DEFAULT 'ativo'", $afterBase);
        $this->addColumnIfMissing('motivo_encerramento', 'VARCHAR(60) NULL', 'status_operacional');
        $this->addColumnIfMissing('observacao_encerramento', 'TEXT NULL', 'motivo_encerramento');
        $this->addColumnIfMissing('encerrado_em', 'DATETIME NULL', 'observacao_encerramento');

        if ($this->db->fieldExists('status_operacional', $this->table)) {
            $this->db->query(
                "UPDATE {$this->table}
                 SET status_operacional = 'ativo'
                 WHERE status_operacional IS NULL OR TRIM(status_operacional) = ''"
            );
        }

        $this->safeCreateIndex($this->idxStatus, '(status_operacional)');
        $this->safeCreateIndex($this->idxStatusEncerrado, '(status_operacional, encerrado_em)');
    }

    public function down()
    {
        if (! $this->db->tableExists($this->table)) {
            return;
        }

        $this->dropIndexIfExists($this->idxStatus);
        $this->dropIndexIfExists($this->idxStatusEncerrado);

        foreach (['encerrado_em', 'observacao_encerramento', 'motivo_encerramento', 'status_operacional'] as $field) {
            if ($this->db->fieldExists($field, $this->table)) {
                $this->forge->dropColumn($this->table, $field);
            }
        }
    }

    private function addColumnIfMissing(string $field, string $definition, string $afterField = ''): void
    {
        if ($this->db->fieldExists($field, $this->table)) {
            return;
        }

        $afterClause = '';
        if ($afterField !== '' && $this->db->fieldExists($afterField, $this->table)) {
            $afterClause = ' AFTER ' . $afterField;
        }

        $this->db->query("ALTER TABLE {$this->table} ADD COLUMN {$field} {$definition}{$afterClause}");
    }

    private function resolveAfterField(array $candidates): string
    {
        foreach ($candidates as $candidate) {
            if ($candidate !== '' && $this->db->fieldExists($candidate, $this->table)) {
                return $candidate;
            }
        }

        return '';
    }

    private function safeCreateIndex(string $name, string $columnsSql): void
    {
        $exists = $this->db->query("SHOW INDEX FROM {$this->table} WHERE Key_name = ?", [$name])->getRowArray();
        if ($exists) {
            return;
        }

        $this->db->query("CREATE INDEX {$name} ON {$this->table} {$columnsSql}");
    }

    private function dropIndexIfExists(string $name): void
    {
        $exists = $this->db->query("SHOW INDEX FROM {$this->table} WHERE Key_name = ?", [$name])->getRowArray();
        if (! $exists) {
            return;
        }

        $this->db->query("DROP INDEX {$name} ON {$this->table}");
    }
}
