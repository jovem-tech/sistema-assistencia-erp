<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFornecedorToFinanceiro extends Migration
{
    private const INDEX_NAME = 'idx_financeiro_fornecedor_id';
    private const FK_NAME = 'fk_financeiro_fornecedor';

    public function up()
    {
        if (! $this->db->tableExists('financeiro') || ! $this->db->tableExists('fornecedores')) {
            return;
        }

        if (! $this->db->fieldExists('fornecedor_id', 'financeiro')) {
            $this->forge->addColumn('financeiro', [
                'fornecedor_id' => [
                    'type' => 'INT',
                    'null' => true,
                    'after' => 'os_id',
                ],
            ]);
        }

        if (! $this->indexExists('financeiro', self::INDEX_NAME)) {
            $this->db->query('ALTER TABLE financeiro ADD INDEX ' . self::INDEX_NAME . ' (fornecedor_id)');
        }

        if (! $this->foreignKeyExists('financeiro', self::FK_NAME)) {
            $this->db->query(
                'ALTER TABLE financeiro ADD CONSTRAINT ' . self::FK_NAME .
                ' FOREIGN KEY (fornecedor_id) REFERENCES fornecedores(id) ON DELETE SET NULL ON UPDATE CASCADE'
            );
        }
    }

    public function down()
    {
        if (! $this->db->tableExists('financeiro')) {
            return;
        }

        if ($this->foreignKeyExists('financeiro', self::FK_NAME)) {
            $this->db->query('ALTER TABLE financeiro DROP FOREIGN KEY ' . self::FK_NAME);
        }

        if ($this->indexExists('financeiro', self::INDEX_NAME)) {
            $this->db->query('ALTER TABLE financeiro DROP INDEX ' . self::INDEX_NAME);
        }

        if ($this->db->fieldExists('fornecedor_id', 'financeiro')) {
            $this->forge->dropColumn('financeiro', 'fornecedor_id');
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        return $this->db->query(
            'SHOW INDEX FROM ' . $table . ' WHERE Key_name = ' . $this->db->escape($indexName)
        )->getNumRows() > 0;
    }

    private function foreignKeyExists(string $table, string $constraintName): bool
    {
        $database = (string) $this->db->database;

        return $this->db->table('information_schema.TABLE_CONSTRAINTS')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $constraintName)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->countAllResults() > 0;
    }
}
