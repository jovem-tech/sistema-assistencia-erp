<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DropRedundantEquipamentoMarcaSearchIndex extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('equipamentos_marcas')) {
            $this->dropIndex('idx_equipamentos_marcas_nome', 'equipamentos_marcas');
        }
    }

    public function down()
    {
        // Nao recriar: a tabela ja possui indice/constraint propria em `nome`.
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
