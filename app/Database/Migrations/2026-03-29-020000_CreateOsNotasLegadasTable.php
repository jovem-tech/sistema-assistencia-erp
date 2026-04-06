<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateOsNotasLegadasTable extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('os_notas_legadas')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'os_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => false,
            ],
            'legacy_origem' => [
                'type' => 'VARCHAR',
                'constraint' => 60,
                'null' => false,
            ],
            'legacy_tabela' => [
                'type' => 'VARCHAR',
                'constraint' => 60,
                'null' => false,
            ],
            'legacy_id' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
                'null' => false,
            ],
            'titulo' => [
                'type' => 'VARCHAR',
                'constraint' => 160,
                'null' => true,
            ],
            'conteudo' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('os_id');
        $this->forge->addUniqueKey(['legacy_origem', 'legacy_tabela', 'legacy_id'], 'ux_os_notas_legadas_legacy_ref');
        $this->forge->createTable('os_notas_legadas', true);
    }

    public function down()
    {
        if ($this->db->tableExists('os_notas_legadas')) {
            $this->forge->dropTable('os_notas_legadas', true);
        }
    }
}
