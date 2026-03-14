<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEquipamentoDefeitoProcedimentosTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'auto_increment' => true,
            ],
            'defeito_id' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'descricao' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
            ],
            'ordem' => [
                'type'       => 'INT',
                'constraint' => 5,
                'default'    => 0,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('defeito_id', 'equipamentos_defeitos', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('equipamento_defeito_procedimentos', true);
    }

    public function down()
    {
        $this->forge->dropTable('equipamento_defeito_procedimentos', true);
    }
}
