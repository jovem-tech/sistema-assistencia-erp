<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEquipamentosCatalogoRelacoes extends Migration
{
    private string $table = 'equipamentos_catalogo_relacoes';
    private string $fkTipo = 'fk_equip_catalogo_rel_tipo';
    private string $fkMarca = 'fk_equip_catalogo_rel_marca';
    private string $fkModelo = 'fk_equip_catalogo_rel_modelo';
    private string $idxTipo = 'idx_equip_catalogo_rel_tipo';
    private string $idxMarca = 'idx_equip_catalogo_rel_marca';
    private string $idxModelo = 'idx_equip_catalogo_rel_modelo';
    private string $idxTipoMarca = 'idx_equip_catalogo_rel_tipo_marca';
    private string $uxTipoMarcaModelo = 'ux_equip_catalogo_rel_tipo_marca_modelo';

    public function up()
    {
        if (
            !$this->db->tableExists('equipamentos_tipos')
            || !$this->db->tableExists('equipamentos_marcas')
            || !$this->db->tableExists('equipamentos_modelos')
        ) {
            return;
        }

        if (!$this->db->tableExists($this->table)) {
            $this->db->query(
                "CREATE TABLE {$this->table} (
                    id INT(11) NOT NULL AUTO_INCREMENT,
                    tipo_id INT(11) NOT NULL,
                    marca_id INT(11) NOT NULL,
                    modelo_id INT(11) NOT NULL,
                    ativo TINYINT(1) NULL DEFAULT 1,
                    created_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
            );
        }

        $this->safeCreateIndex($this->table, $this->idxTipo, '(tipo_id)');
        $this->safeCreateIndex($this->table, $this->idxMarca, '(marca_id)');
        $this->safeCreateIndex($this->table, $this->idxModelo, '(modelo_id)');
        $this->safeCreateIndex($this->table, $this->idxTipoMarca, '(tipo_id, marca_id)');
        $this->safeCreateIndex($this->table, $this->uxTipoMarcaModelo, '(tipo_id, marca_id, modelo_id)', true);

        $this->safeAddForeignKey($this->table, $this->fkTipo, 'tipo_id', 'equipamentos_tipos', 'id', 'CASCADE');
        $this->safeAddForeignKey($this->table, $this->fkMarca, 'marca_id', 'equipamentos_marcas', 'id', 'CASCADE');
        $this->safeAddForeignKey($this->table, $this->fkModelo, 'modelo_id', 'equipamentos_modelos', 'id', 'CASCADE');

        $this->backfillFromEquipamentos();
    }

    public function down()
    {
        if (!$this->db->tableExists($this->table)) {
            return;
        }

        $this->safeDropForeignKey($this->table, $this->fkTipo);
        $this->safeDropForeignKey($this->table, $this->fkMarca);
        $this->safeDropForeignKey($this->table, $this->fkModelo);

        $this->safeDropIndex($this->table, $this->uxTipoMarcaModelo);
        $this->safeDropIndex($this->table, $this->idxTipoMarca);
        $this->safeDropIndex($this->table, $this->idxTipo);
        $this->safeDropIndex($this->table, $this->idxMarca);
        $this->safeDropIndex($this->table, $this->idxModelo);

        $this->db->query("DROP TABLE {$this->table}");
    }

    private function backfillFromEquipamentos(): void
    {
        if (!$this->db->tableExists('equipamentos')) {
            return;
        }

        try {
            // Mantem somente combinacoes validas (modelo pertence a marca).
            $this->db->query(
                "INSERT IGNORE INTO {$this->table} (tipo_id, marca_id, modelo_id, ativo, created_at, updated_at)
                 SELECT DISTINCT e.tipo_id, e.marca_id, e.modelo_id, 1, NOW(), NOW()
                   FROM equipamentos e
                   INNER JOIN equipamentos_modelos m ON m.id = e.modelo_id AND m.marca_id = e.marca_id
                  WHERE e.tipo_id > 0
                    AND e.marca_id > 0
                    AND e.modelo_id > 0"
            );
        } catch (\Throwable $e) {
            // Base antiga ou dados legados inconsistentes: nao interromper migracao.
        }
    }

    private function safeCreateIndex(string $table, string $indexName, string $columnsSql, bool $unique = false): void
    {
        $prefix = $unique ? 'CREATE UNIQUE INDEX' : 'CREATE INDEX';
        try {
            $this->db->query("{$prefix} {$indexName} ON {$table} {$columnsSql}");
        } catch (\Throwable $e) {
            // indice existente.
        }
    }

    private function safeDropIndex(string $table, string $indexName): void
    {
        try {
            $this->db->query("DROP INDEX {$indexName} ON {$table}");
        } catch (\Throwable $e) {
            // indice inexistente.
        }
    }

    private function safeAddForeignKey(
        string $table,
        string $constraintName,
        string $column,
        string $referenceTable,
        string $referenceColumn,
        string $onDelete = 'CASCADE',
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
            // fk ja existente com outro nome ou dependencia indisponivel.
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
            // fk inexistente.
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

