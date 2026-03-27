<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddOsSearchLookupIndexes extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('funcionarios') && $this->db->fieldExists('nome', 'funcionarios')) {
            $this->createIndex(
                'idx_funcionarios_nome',
                'CREATE INDEX idx_funcionarios_nome ON funcionarios (nome)'
            );
        }

        if ($this->db->tableExists('equipamentos_modelos') && $this->db->fieldExists('nome', 'equipamentos_modelos')) {
            $this->createIndex(
                'idx_equipamentos_modelos_nome',
                'CREATE INDEX idx_equipamentos_modelos_nome ON equipamentos_modelos (nome)'
            );
        }

        if ($this->db->tableExists('equipamentos') && $this->db->fieldExists('marca_id', 'equipamentos')) {
            $this->createIndex(
                'idx_equipamentos_marca_id',
                'CREATE INDEX idx_equipamentos_marca_id ON equipamentos (marca_id)'
            );
        }

        if ($this->db->tableExists('equipamentos') && $this->db->fieldExists('modelo_id', 'equipamentos')) {
            $this->createIndex(
                'idx_equipamentos_modelo_id',
                'CREATE INDEX idx_equipamentos_modelo_id ON equipamentos (modelo_id)'
            );
        }
    }

    public function down()
    {
        if ($this->db->tableExists('funcionarios')) {
            $this->dropIndex('idx_funcionarios_nome', 'funcionarios');
        }

        if ($this->db->tableExists('equipamentos_modelos')) {
            $this->dropIndex('idx_equipamentos_modelos_nome', 'equipamentos_modelos');
        }

        if ($this->db->tableExists('equipamentos')) {
            $this->dropIndex('idx_equipamentos_marca_id', 'equipamentos');
            $this->dropIndex('idx_equipamentos_modelo_id', 'equipamentos');
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
