<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePrecificacaoCategoriaEncargosTable extends Migration
{
    private string $table = 'precificacao_categoria_encargos';

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
                'categoria_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => false,
                ],
                'nome' => [
                    'type' => 'VARCHAR',
                    'constraint' => 140,
                    'null' => false,
                ],
                'percentual' => [
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
            $this->forge->addKey(['categoria_id', 'ativo'], false, false, 'idx_precificacao_cat_encargos_categoria_ativo');
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
