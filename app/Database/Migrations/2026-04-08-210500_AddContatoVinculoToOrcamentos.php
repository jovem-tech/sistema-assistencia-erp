<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddContatoVinculoToOrcamentos extends Migration
{
    private string $foreignKeyName = 'fk_orcamentos_contato_id';
    private string $indexName = 'idx_orcamentos_contato_id';

    public function up()
    {
        if (!$this->db->tableExists('orcamentos')) {
            return;
        }

        if (!$this->db->fieldExists('contato_id', 'orcamentos')) {
            $this->db->query('ALTER TABLE orcamentos ADD COLUMN contato_id BIGINT(20) UNSIGNED NULL AFTER cliente_id');
        }

        $this->safeCreateIndex('orcamentos', $this->indexName, '(contato_id)');
        $this->safeAddForeignKey(
            'orcamentos',
            $this->foreignKeyName,
            'contato_id',
            'contatos',
            'id',
            'SET NULL',
            'CASCADE'
        );
    }

    public function down()
    {
        if (!$this->db->tableExists('orcamentos')) {
            return;
        }

        $this->safeDropForeignKey('orcamentos', $this->foreignKeyName);
        $this->safeDropIndex('orcamentos', $this->indexName);

        if ($this->db->fieldExists('contato_id', 'orcamentos')) {
            $this->db->query('ALTER TABLE orcamentos DROP COLUMN contato_id');
        }
    }

    private function safeCreateIndex(string $table, string $indexName, string $columnsSql): void
    {
        try {
            $this->db->query("CREATE INDEX {$indexName} ON {$table} {$columnsSql}");
        } catch (\Throwable $e) {
            // indice existente ou sem suporte.
        }
    }

    private function safeDropIndex(string $table, string $indexName): void
    {
        try {
            $this->db->query("DROP INDEX {$indexName} ON {$table}");
        } catch (\Throwable $e) {
            // indice nao existe.
        }
    }

    private function safeAddForeignKey(
        string $table,
        string $constraintName,
        string $column,
        string $referenceTable,
        string $referenceColumn,
        string $onDelete = 'SET NULL',
        string $onUpdate = 'CASCADE'
    ): void {
        if ($this->foreignKeyExists($table, $constraintName)) {
            return;
        }

        try {
            $this->db->query(
                "ALTER TABLE {$table} ADD CONSTRAINT {$constraintName} FOREIGN KEY ({$column}) REFERENCES {$referenceTable} ({$referenceColumn}) ON DELETE {$onDelete} ON UPDATE {$onUpdate}"
            );
        } catch (\Throwable $e) {
            // tabela/coluna de referencia indisponivel ou FK ja existente com outro nome.
        }
    }

    private function safeDropForeignKey(string $table, string $constraintName): void
    {
        if (!$this->foreignKeyExists($table, $constraintName)) {
            return;
        }

        try {
            $this->db->query("ALTER TABLE {$table} DROP FOREIGN KEY {$constraintName}");
        } catch (\Throwable $e) {
            // FK ja removida.
        }
    }

    private function foreignKeyExists(string $table, string $constraintName): bool
    {
        $row = $this->db->table('information_schema.TABLE_CONSTRAINTS')
            ->select('CONSTRAINT_NAME')
            ->where('TABLE_SCHEMA', $this->db->database)
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $constraintName)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->get()
            ->getRowArray();

        return !empty($row);
    }
}

