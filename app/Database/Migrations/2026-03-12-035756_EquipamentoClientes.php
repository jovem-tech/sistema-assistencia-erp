<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class EquipamentoClientes extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'equipamento_id' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'cliente_id' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'created_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('equipamento_id', 'equipamentos', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('cliente_id', 'clientes', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('equipamento_clientes', true);
    }

    public function down()
    {
        $this->forge->dropTable('equipamento_clientes', true);
    }
}
