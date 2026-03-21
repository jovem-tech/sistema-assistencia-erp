<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateOsFotosTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'auto_increment' => true],
            'os_id'      => ['type' => 'INT', 'null' => false],
            'tipo'       => ['type' => 'ENUM', 'constraint' => ['recepcao', 'diagnostico', 'entrega'], 'default' => 'recepcao'],
            'arquivo'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'created_at' => ['type' => 'TIMESTAMP', 'null' => true, 'default' => null],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('os_id');
        $this->forge->createTable('os_fotos', true);
    }

    public function down()
    {
        $this->forge->dropTable('os_fotos', true);
    }
}
