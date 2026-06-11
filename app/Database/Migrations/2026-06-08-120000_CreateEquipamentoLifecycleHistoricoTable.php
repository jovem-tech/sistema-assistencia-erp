<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEquipamentoLifecycleHistoricoTable extends Migration
{
    private string $table = 'equipamentos_lifecycle_historico';

    public function up()
    {
        if ($this->db->tableExists($this->table)) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'equipamento_id' => [
                'type' => 'INT',
                'constraint' => 11,
            ],
            'os_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'evento' => [
                'type' => 'VARCHAR',
                'constraint' => 40,
            ],
            'motivo' => [
                'type' => 'VARCHAR',
                'constraint' => 60,
                'null' => true,
            ],
            'observacao' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'status_anterior' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
            ],
            'status_novo' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
            ],
            'usuario_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['equipamento_id', 'created_at']);
        $this->forge->addKey(['evento']);
        $this->forge->addForeignKey('equipamento_id', 'equipamentos', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('os_id', 'os', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('usuario_id', 'usuarios', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable($this->table, true);
    }

    public function down()
    {
        if ($this->db->tableExists($this->table)) {
            $this->forge->dropTable($this->table, true);
        }
    }
}
