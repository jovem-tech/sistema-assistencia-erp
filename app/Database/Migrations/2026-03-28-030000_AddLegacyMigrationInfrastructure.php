<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddLegacyMigrationInfrastructure extends Migration
{
    public function up()
    {
        $this->addLegacyColumnsToClientes();
        $this->addLegacyColumnsToEquipamentos();
        $this->addLegacyColumnsToOs();
        $this->createLegacyImportRunsTable();
        $this->createLegacyImportEventsTable();
    }

    public function down()
    {
        if ($this->db->tableExists('legacy_import_events')) {
            $this->forge->dropTable('legacy_import_events', true);
        }

        if ($this->db->tableExists('legacy_import_runs')) {
            $this->forge->dropTable('legacy_import_runs', true);
        }

        if ($this->db->tableExists('os')) {
            $this->dropIndexIfExists('os', 'idx_os_numero_legado');
            $this->dropIndexIfExists('os', 'ux_os_legacy_source');
            $this->dropColumnIfExists('os', ['legacy_origem', 'legacy_id', 'numero_os_legado']);
        }

        if ($this->db->tableExists('equipamentos')) {
            $this->dropIndexIfExists('equipamentos', 'ux_equipamentos_legacy_source');
            $this->dropColumnIfExists('equipamentos', ['legacy_origem', 'legacy_id']);
        }

        if ($this->db->tableExists('clientes')) {
            $this->dropIndexIfExists('clientes', 'ux_clientes_legacy_source');
            $this->dropColumnIfExists('clientes', ['legacy_origem', 'legacy_id']);
        }
    }

    private function addLegacyColumnsToClientes(): void
    {
        if (! $this->db->tableExists('clientes')) {
            return;
        }

        $fields = [];
        if (! $this->db->fieldExists('legacy_origem', 'clientes')) {
            $fields['legacy_origem'] = [
                'type'       => 'VARCHAR',
                'constraint' => 60,
                'null'       => true,
                'after'      => 'observacoes',
            ];
        }

        if (! $this->db->fieldExists('legacy_id', 'clientes')) {
            $fields['legacy_id'] = [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'after'      => 'legacy_origem',
            ];
        }

        if ($fields !== []) {
            $this->forge->addColumn('clientes', $fields);
        }

        $this->createIndexIfMissing('clientes', 'ux_clientes_legacy_source', ['legacy_origem', 'legacy_id'], true);
    }

    private function addLegacyColumnsToEquipamentos(): void
    {
        if (! $this->db->tableExists('equipamentos')) {
            return;
        }

        $fields = [];
        if (! $this->db->fieldExists('legacy_origem', 'equipamentos')) {
            $fields['legacy_origem'] = [
                'type'       => 'VARCHAR',
                'constraint' => 60,
                'null'       => true,
                'after'      => 'observacoes',
            ];
        }

        if (! $this->db->fieldExists('legacy_id', 'equipamentos')) {
            $fields['legacy_id'] = [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'after'      => 'legacy_origem',
            ];
        }

        if ($fields !== []) {
            $this->forge->addColumn('equipamentos', $fields);
        }

        $this->createIndexIfMissing('equipamentos', 'ux_equipamentos_legacy_source', ['legacy_origem', 'legacy_id'], true);
    }

    private function addLegacyColumnsToOs(): void
    {
        if (! $this->db->tableExists('os')) {
            return;
        }

        $fields = [];
        if (! $this->db->fieldExists('legacy_origem', 'os')) {
            $fields['legacy_origem'] = [
                'type'       => 'VARCHAR',
                'constraint' => 60,
                'null'       => true,
                'after'      => 'numero_os',
            ];
        }

        if (! $this->db->fieldExists('legacy_id', 'os')) {
            $fields['legacy_id'] = [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'after'      => 'legacy_origem',
            ];
        }

        if (! $this->db->fieldExists('numero_os_legado', 'os')) {
            $fields['numero_os_legado'] = [
                'type'       => 'VARCHAR',
                'constraint' => 60,
                'null'       => true,
                'after'      => 'legacy_id',
            ];
        }

        if ($fields !== []) {
            $this->forge->addColumn('os', $fields);
        }

        $this->createIndexIfMissing('os', 'ux_os_legacy_source', ['legacy_origem', 'legacy_id'], true);
        $this->createIndexIfMissing('os', 'idx_os_numero_legado', ['numero_os_legado']);
    }

    private function createLegacyImportRunsTable(): void
    {
        if ($this->db->tableExists('legacy_import_runs')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'source_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 60,
            ],
            'mode' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'running',
            ],
            'started_at' => [
                'type' => 'DATETIME',
            ],
            'finished_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'summary_json' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['source_name', 'started_at'], false, false, 'idx_legacy_runs_source_started');
        $this->forge->createTable('legacy_import_runs', true);
    }

    private function createLegacyImportEventsTable(): void
    {
        if ($this->db->tableExists('legacy_import_events')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'run_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'entity' => [
                'type'       => 'VARCHAR',
                'constraint' => 40,
            ],
            'severity' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
            ],
            'action' => [
                'type'       => 'VARCHAR',
                'constraint' => 60,
            ],
            'legacy_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'message' => [
                'type' => 'TEXT',
            ],
            'details_json' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['run_id', 'entity'], false, false, 'idx_legacy_events_run_entity');
        $this->forge->addKey(['run_id', 'severity'], false, false, 'idx_legacy_events_run_severity');
        $this->forge->addForeignKey('run_id', 'legacy_import_runs', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('legacy_import_events', true);
    }

    private function createIndexIfMissing(string $table, string $indexName, array $columns, bool $unique = false): void
    {
        if ($this->indexExists($table, $indexName)) {
            return;
        }

        $columnSql = implode(', ', array_map(static fn (string $column): string => '`' . $column . '`', $columns));
        $sql = 'CREATE ' . ($unique ? 'UNIQUE ' : '') . "INDEX `{$indexName}` ON `{$table}` ({$columnSql})";
        $this->db->query($sql);
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if (! $this->indexExists($table, $indexName)) {
            return;
        }

        $this->db->query("DROP INDEX `{$indexName}` ON `{$table}`");
    }

    private function dropColumnIfExists(string $table, array $columns): void
    {
        foreach ($columns as $column) {
            if ($this->db->fieldExists($column, $table)) {
                $this->forge->dropColumn($table, $column);
            }
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $database = (string) $this->db->getDatabase();
        $builder = $this->db->table('information_schema.statistics');
        $row = $builder
            ->select('INDEX_NAME')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', $table)
            ->where('INDEX_NAME', $indexName)
            ->get()
            ->getRowArray();

        return $row !== null;
    }
}
