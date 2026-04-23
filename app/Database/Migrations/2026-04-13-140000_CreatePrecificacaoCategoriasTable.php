<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePrecificacaoCategoriasTable extends Migration
{
    private string $table = 'precificacao_categorias';

    public function up()
    {
        if (! $this->db->tableExists($this->table)) {
            $this->forge->addField([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'tipo' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'null' => false,
                ],
                'categoria_nome' => [
                    'type' => 'VARCHAR',
                    'constraint' => 120,
                    'null' => false,
                ],
                'encargos_percentual' => [
                    'type' => 'DECIMAL',
                    'constraint' => '8,2',
                    'null' => false,
                    'default' => 0,
                ],
                'margem_percentual' => [
                    'type' => 'DECIMAL',
                    'constraint' => '8,2',
                    'null' => false,
                    'default' => 0,
                ],
                'ativo' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'null' => false,
                    'default' => 1,
                ],
                'ordem' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => false,
                    'default' => 0,
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
            $this->forge->addKey(['tipo', 'ativo'], false, false, 'idx_precificacao_categorias_tipo_ativo');
            $this->forge->addKey(['tipo', 'categoria_nome'], false, false, 'idx_precificacao_categorias_tipo_nome');
            $this->forge->createTable($this->table, true, ['ENGINE' => 'InnoDB']);
        }
    }

    public function down()
    {
        if ($this->db->tableExists($this->table)) {
            $this->forge->dropTable($this->table, true);
        }
    }
}
