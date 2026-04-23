<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddOsItensPendingWorkflowAndCatalogFilters extends Migration
{
    public function up()
    {
        $this->addTipoEquipamentoToPecas();
        $this->addTipoEquipamentoToServicos();
        $this->addPendingWorkflowToOsItens();
    }

    public function down()
    {
        if ($this->db->tableExists('os_itens')) {
            $this->dropForeignKeyIfExists('os_itens', 'fk_os_itens_servico');
            $this->dropIndexIfExists('os_itens', 'os_itens_servico_id_idx');
            $this->dropIndexIfExists('os_itens', 'os_itens_status_item_idx');

            $this->dropColumnIfExists('os_itens', [
                'servico_id',
                'status_item_estoque',
                'estoque_reservado',
                'pendencia_resolvida_em',
                'pendencia_observacao',
                'pendencia_fornecedor',
                'pendencia_valor_compra',
                'pendencia_data_entrada',
                'pendencia_tipo_aquisicao',
                'pendencia_destino_despesa',
            ]);
        }

        if ($this->db->tableExists('pecas')) {
            $this->dropIndexIfExists('pecas', 'idx_pecas_tipo_equipamento');
            $this->dropColumnIfExists('pecas', ['tipo_equipamento']);
        }

        if ($this->db->tableExists('servicos')) {
            $this->dropIndexIfExists('servicos', 'idx_servicos_tipo_equipamento');
            $this->dropColumnIfExists('servicos', ['tipo_equipamento']);
        }
    }

    private function addTipoEquipamentoToPecas(): void
    {
        if (! $this->db->tableExists('pecas')) {
            return;
        }

        if (! $this->db->fieldExists('tipo_equipamento', 'pecas')) {
            $this->forge->addColumn('pecas', [
                'tipo_equipamento' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                ],
            ]);
        }

        $this->createIndexIfMissing('pecas', 'idx_pecas_tipo_equipamento', ['tipo_equipamento']);
    }

    private function addTipoEquipamentoToServicos(): void
    {
        if (! $this->db->tableExists('servicos')) {
            return;
        }

        if (! $this->db->fieldExists('tipo_equipamento', 'servicos')) {
            $this->forge->addColumn('servicos', [
                'tipo_equipamento' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                ],
            ]);
        }

        $this->createIndexIfMissing('servicos', 'idx_servicos_tipo_equipamento', ['tipo_equipamento']);
    }

    private function addPendingWorkflowToOsItens(): void
    {
        if (! $this->db->tableExists('os_itens')) {
            return;
        }

        $fields = [];

        if (! $this->db->fieldExists('servico_id', 'os_itens')) {
            $fields['servico_id'] = [
                'type'     => 'INT',
                'null'     => true,
                'unsigned' => false,
            ];
        }

        if (! $this->db->fieldExists('status_item_estoque', 'os_itens')) {
            $fields['status_item_estoque'] = [
                'type'       => 'VARCHAR',
                'constraint' => 40,
                'null'       => true,
                'default'    => 'disponivel',
            ];
        }

        if (! $this->db->fieldExists('estoque_reservado', 'os_itens')) {
            $fields['estoque_reservado'] = [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'null'       => false,
                'default'    => 0,
                'unsigned'   => true,
            ];
        }

        if (! $this->db->fieldExists('pendencia_resolvida_em', 'os_itens')) {
            $fields['pendencia_resolvida_em'] = [
                'type' => 'DATETIME',
                'null' => true,
            ];
        }

        if (! $this->db->fieldExists('pendencia_observacao', 'os_itens')) {
            $fields['pendencia_observacao'] = [
                'type' => 'TEXT',
                'null' => true,
            ];
        }

        if (! $this->db->fieldExists('pendencia_fornecedor', 'os_itens')) {
            $fields['pendencia_fornecedor'] = [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'null'       => true,
            ];
        }

        if (! $this->db->fieldExists('pendencia_valor_compra', 'os_itens')) {
            $fields['pendencia_valor_compra'] = [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => true,
            ];
        }

        if (! $this->db->fieldExists('pendencia_data_entrada', 'os_itens')) {
            $fields['pendencia_data_entrada'] = [
                'type' => 'DATE',
                'null' => true,
            ];
        }

        if (! $this->db->fieldExists('pendencia_tipo_aquisicao', 'os_itens')) {
            $fields['pendencia_tipo_aquisicao'] = [
                'type'       => 'VARCHAR',
                'constraint' => 60,
                'null'       => true,
            ];
        }

        if (! $this->db->fieldExists('pendencia_destino_despesa', 'os_itens')) {
            $fields['pendencia_destino_despesa'] = [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ];
        }

        if ($fields !== []) {
            $this->forge->addColumn('os_itens', $fields);
        }

        $this->createIndexIfMissing('os_itens', 'os_itens_servico_id_idx', ['servico_id']);
        $this->createIndexIfMissing('os_itens', 'os_itens_status_item_idx', ['status_item_estoque']);

        if ($this->db->tableExists('servicos')) {
            $this->createForeignKeyIfMissing(
                'os_itens',
                'fk_os_itens_servico',
                'servico_id',
                'servicos',
                'id'
            );
        }

        if ($this->db->fieldExists('status_item_estoque', 'os_itens')) {
            $this->db->query(
                "UPDATE os_itens
                 SET status_item_estoque = 'reservada', estoque_reservado = 1
                 WHERE tipo = 'peca'
                   AND peca_id IS NOT NULL
                   AND (status_item_estoque IS NULL OR TRIM(status_item_estoque) = '')"
            );

            $this->db->query(
                "UPDATE os_itens
                 SET status_item_estoque = 'disponivel'
                 WHERE tipo = 'servico'
                   AND (status_item_estoque IS NULL OR TRIM(status_item_estoque) = '')"
            );
        }
    }

    private function createIndexIfMissing(string $table, string $indexName, array $columns): void
    {
        $existing = $this->db
            ->query('SHOW INDEX FROM `' . $table . '` WHERE Key_name = ?', [$indexName])
            ->getResultArray();

        if ($existing !== []) {
            return;
        }

        $quotedColumns = implode(', ', array_map(
            static fn (string $column): string => '`' . $column . '`',
            $columns
        ));

        $this->db->query('CREATE INDEX `' . $indexName . '` ON `' . $table . '` (' . $quotedColumns . ')');
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        $existing = $this->db
            ->query('SHOW INDEX FROM `' . $table . '` WHERE Key_name = ?', [$indexName])
            ->getResultArray();

        if ($existing === []) {
            return;
        }

        $this->db->query('DROP INDEX `' . $indexName . '` ON `' . $table . '`');
    }

    private function createForeignKeyIfMissing(
        string $table,
        string $constraintName,
        string $column,
        string $refTable,
        string $refColumn
    ): void {
        if (! $this->isForeignKeyMissing($table, $constraintName)) {
            return;
        }

        $sql = sprintf(
            'ALTER TABLE `%s` ADD CONSTRAINT `%s` FOREIGN KEY (`%s`) REFERENCES `%s` (`%s`) ON DELETE SET NULL ON UPDATE CASCADE',
            $table,
            $constraintName,
            $column,
            $refTable,
            $refColumn
        );

        $this->db->query($sql);
    }

    private function dropForeignKeyIfExists(string $table, string $constraintName): void
    {
        if ($this->isForeignKeyMissing($table, $constraintName)) {
            return;
        }

        $this->db->query(sprintf(
            'ALTER TABLE `%s` DROP FOREIGN KEY `%s`',
            $table,
            $constraintName
        ));
    }

    private function isForeignKeyMissing(string $table, string $constraintName): bool
    {
        $result = $this->db->query(
            "SELECT CONSTRAINT_NAME
             FROM information_schema.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND CONSTRAINT_NAME = ?
               AND CONSTRAINT_TYPE = 'FOREIGN KEY'
             LIMIT 1",
            [$table, $constraintName]
        )->getRowArray();

        return empty($result);
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

