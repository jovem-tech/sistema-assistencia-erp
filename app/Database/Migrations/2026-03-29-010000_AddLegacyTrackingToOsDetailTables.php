<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddLegacyTrackingToOsDetailTables extends Migration
{
    public function up()
    {
        $this->updateOsItens();
        $this->updateOsStatusHistorico();
        $this->updateOsDefeitos();
    }

    public function down()
    {
        if ($this->db->tableExists('os_defeitos')) {
            $this->dropIndexIfExists('os_defeitos', 'ux_os_defeitos_legacy_ref');
            $this->dropColumnIfExists('os_defeitos', ['legacy_origem', 'legacy_tabela', 'legacy_id']);
        }

        if ($this->db->tableExists('os_status_historico')) {
            $this->dropIndexIfExists('os_status_historico', 'ux_os_status_hist_legacy_ref');
            $this->dropColumnIfExists('os_status_historico', ['legacy_origem', 'legacy_tabela', 'legacy_id']);
        }

        if ($this->db->tableExists('os_itens')) {
            $this->dropIndexIfExists('os_itens', 'ux_os_itens_legacy_ref');
            $this->dropColumnIfExists('os_itens', ['legacy_origem', 'legacy_tabela', 'legacy_id', 'observacao']);
        }
    }

    private function updateOsItens(): void
    {
        if (! $this->db->tableExists('os_itens')) {
            return;
        }

        $fields = [];

        if (! $this->db->fieldExists('observacao', 'os_itens')) {
            $fields['observacao'] = [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'descricao',
            ];
        }

        if (! $this->db->fieldExists('legacy_origem', 'os_itens')) {
            $fields['legacy_origem'] = [
                'type' => 'VARCHAR',
                'constraint' => 60,
                'null' => true,
                'after' => 'os_id',
            ];
        }

        if (! $this->db->fieldExists('legacy_tabela', 'os_itens')) {
            $fields['legacy_tabela'] = [
                'type' => 'VARCHAR',
                'constraint' => 60,
                'null' => true,
                'after' => 'legacy_origem',
            ];
        }

        if (! $this->db->fieldExists('legacy_id', 'os_itens')) {
            $fields['legacy_id'] = [
                'type' => 'VARCHAR',
                'constraint' => 120,
                'null' => true,
                'after' => 'legacy_tabela',
            ];
        }

        if ($fields !== []) {
            $this->forge->addColumn('os_itens', $fields);
        }

        $this->createIndexIfMissing('os_itens', 'ux_os_itens_legacy_ref', ['legacy_origem', 'legacy_tabela', 'legacy_id'], true);
    }

    private function updateOsStatusHistorico(): void
    {
        if (! $this->db->tableExists('os_status_historico')) {
            return;
        }

        $fields = [];

        if (! $this->db->fieldExists('legacy_origem', 'os_status_historico')) {
            $fields['legacy_origem'] = [
                'type' => 'VARCHAR',
                'constraint' => 60,
                'null' => true,
                'after' => 'os_id',
            ];
        }

        if (! $this->db->fieldExists('legacy_tabela', 'os_status_historico')) {
            $fields['legacy_tabela'] = [
                'type' => 'VARCHAR',
                'constraint' => 60,
                'null' => true,
                'after' => 'legacy_origem',
            ];
        }

        if (! $this->db->fieldExists('legacy_id', 'os_status_historico')) {
            $fields['legacy_id'] = [
                'type' => 'VARCHAR',
                'constraint' => 120,
                'null' => true,
                'after' => 'legacy_tabela',
            ];
        }

        if ($fields !== []) {
            $this->forge->addColumn('os_status_historico', $fields);
        }

        $this->createIndexIfMissing('os_status_historico', 'ux_os_status_hist_legacy_ref', ['legacy_origem', 'legacy_tabela', 'legacy_id'], true);
    }

    private function updateOsDefeitos(): void
    {
        if (! $this->db->tableExists('os_defeitos')) {
            return;
        }

        $fields = [];

        if (! $this->db->fieldExists('legacy_origem', 'os_defeitos')) {
            $fields['legacy_origem'] = [
                'type' => 'VARCHAR',
                'constraint' => 60,
                'null' => true,
                'after' => 'os_id',
            ];
        }

        if (! $this->db->fieldExists('legacy_tabela', 'os_defeitos')) {
            $fields['legacy_tabela'] = [
                'type' => 'VARCHAR',
                'constraint' => 60,
                'null' => true,
                'after' => 'legacy_origem',
            ];
        }

        if (! $this->db->fieldExists('legacy_id', 'os_defeitos')) {
            $fields['legacy_id'] = [
                'type' => 'VARCHAR',
                'constraint' => 120,
                'null' => true,
                'after' => 'legacy_tabela',
            ];
        }

        if ($fields !== []) {
            $this->forge->addColumn('os_defeitos', $fields);
        }

        $this->createIndexIfMissing('os_defeitos', 'ux_os_defeitos_legacy_ref', ['legacy_origem', 'legacy_tabela', 'legacy_id'], true);
    }

    private function createIndexIfMissing(string $table, string $indexName, array $columns, bool $unique = false): void
    {
        $existing = $this->db->query('SHOW INDEX FROM `' . $table . '` WHERE Key_name = ?', [$indexName])->getResultArray();
        if ($existing !== []) {
            return;
        }

        $quotedColumns = implode(', ', array_map(static fn (string $column): string => '`' . $column . '`', $columns));
        $sql = sprintf(
            'CREATE %s INDEX `%s` ON `%s` (%s)',
            $unique ? 'UNIQUE' : '',
            $indexName,
            $table,
            $quotedColumns
        );

        $this->db->query(trim($sql));
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        $existing = $this->db->query('SHOW INDEX FROM `' . $table . '` WHERE Key_name = ?', [$indexName])->getResultArray();
        if ($existing === []) {
            return;
        }

        $this->db->query('DROP INDEX `' . $indexName . '` ON `' . $table . '`');
    }

    /**
     * @param list<string> $columns
     */
    private function dropColumnIfExists(string $table, array $columns): void
    {
        foreach ($columns as $column) {
            if ($this->db->fieldExists($column, $table)) {
                $this->forge->dropColumn($table, $column);
            }
        }
    }
}
