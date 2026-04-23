<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddEquipamentoCatalogSnapshotToOrcamentos extends Migration
{
    private string $fkTipo = 'fk_orcamentos_equip_tipo_id';
    private string $fkMarca = 'fk_orcamentos_equip_marca_id';
    private string $fkModelo = 'fk_orcamentos_equip_modelo_id';
    private string $idxTipo = 'idx_orcamentos_equip_tipo_id';
    private string $idxMarca = 'idx_orcamentos_equip_marca_id';
    private string $idxModelo = 'idx_orcamentos_equip_modelo_id';

    public function up()
    {
        if (!$this->db->tableExists('orcamentos')) {
            return;
        }

        if (!$this->db->fieldExists('equipamento_tipo_id', 'orcamentos')) {
            $this->db->query('ALTER TABLE orcamentos ADD COLUMN equipamento_tipo_id INT(11) NULL AFTER equipamento_id');
        }
        if (!$this->db->fieldExists('equipamento_marca_id', 'orcamentos')) {
            $this->db->query('ALTER TABLE orcamentos ADD COLUMN equipamento_marca_id INT(11) NULL AFTER equipamento_tipo_id');
        }
        if (!$this->db->fieldExists('equipamento_modelo_id', 'orcamentos')) {
            $this->db->query('ALTER TABLE orcamentos ADD COLUMN equipamento_modelo_id INT(11) NULL AFTER equipamento_marca_id');
        }
        if (!$this->db->fieldExists('equipamento_cor', 'orcamentos')) {
            $this->db->query('ALTER TABLE orcamentos ADD COLUMN equipamento_cor VARCHAR(100) NULL AFTER equipamento_modelo_id');
        }
        if (!$this->db->fieldExists('equipamento_cor_hex', 'orcamentos')) {
            $this->db->query('ALTER TABLE orcamentos ADD COLUMN equipamento_cor_hex VARCHAR(7) NULL AFTER equipamento_cor');
        }
        if (!$this->db->fieldExists('equipamento_cor_rgb', 'orcamentos')) {
            $this->db->query('ALTER TABLE orcamentos ADD COLUMN equipamento_cor_rgb VARCHAR(32) NULL AFTER equipamento_cor_hex');
        }

        $this->safeCreateIndex('orcamentos', $this->idxTipo, '(equipamento_tipo_id)');
        $this->safeCreateIndex('orcamentos', $this->idxMarca, '(equipamento_marca_id)');
        $this->safeCreateIndex('orcamentos', $this->idxModelo, '(equipamento_modelo_id)');

        $this->safeAddForeignKey('orcamentos', $this->fkTipo, 'equipamento_tipo_id', 'equipamentos_tipos', 'id');
        $this->safeAddForeignKey('orcamentos', $this->fkMarca, 'equipamento_marca_id', 'equipamentos_marcas', 'id');
        $this->safeAddForeignKey('orcamentos', $this->fkModelo, 'equipamento_modelo_id', 'equipamentos_modelos', 'id');
    }

    public function down()
    {
        if (!$this->db->tableExists('orcamentos')) {
            return;
        }

        $this->safeDropForeignKey('orcamentos', $this->fkTipo);
        $this->safeDropForeignKey('orcamentos', $this->fkMarca);
        $this->safeDropForeignKey('orcamentos', $this->fkModelo);
        $this->safeDropIndex('orcamentos', $this->idxTipo);
        $this->safeDropIndex('orcamentos', $this->idxMarca);
        $this->safeDropIndex('orcamentos', $this->idxModelo);

        if ($this->db->fieldExists('equipamento_cor_rgb', 'orcamentos')) {
            $this->db->query('ALTER TABLE orcamentos DROP COLUMN equipamento_cor_rgb');
        }
        if ($this->db->fieldExists('equipamento_cor_hex', 'orcamentos')) {
            $this->db->query('ALTER TABLE orcamentos DROP COLUMN equipamento_cor_hex');
        }
        if ($this->db->fieldExists('equipamento_cor', 'orcamentos')) {
            $this->db->query('ALTER TABLE orcamentos DROP COLUMN equipamento_cor');
        }
        if ($this->db->fieldExists('equipamento_modelo_id', 'orcamentos')) {
            $this->db->query('ALTER TABLE orcamentos DROP COLUMN equipamento_modelo_id');
        }
        if ($this->db->fieldExists('equipamento_marca_id', 'orcamentos')) {
            $this->db->query('ALTER TABLE orcamentos DROP COLUMN equipamento_marca_id');
        }
        if ($this->db->fieldExists('equipamento_tipo_id', 'orcamentos')) {
            $this->db->query('ALTER TABLE orcamentos DROP COLUMN equipamento_tipo_id');
        }
    }

    private function safeCreateIndex(string $table, string $indexName, string $columnsSql): void
    {
        try {
            $this->db->query("CREATE INDEX {$indexName} ON {$table} {$columnsSql}");
        } catch (\Throwable $e) {
            // indice existente.
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
            // dependencia indisponivel ou fk ja existente com outro nome.
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
            // fk ja removida.
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

