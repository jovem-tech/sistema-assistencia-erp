<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePrecificacaoComponentesTable extends Migration
{
    private string $table = 'precificacao_componentes';

    public function up()
    {
        if ($this->db->tableExists($this->table)) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'grupo' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false,
            ],
            'nome' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
                'null' => false,
            ],
            'tipo_valor' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => false,
                'default' => 'percentual',
                'comment' => 'percentual|valor',
            ],
            'valor' => [
                'type' => 'DECIMAL',
                'constraint' => '12,4',
                'null' => false,
                'default' => 0,
            ],
            'origem' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => false,
                'default' => 'manual',
                'comment' => 'manual|automatica',
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
        $this->forge->addKey(['grupo', 'ativo'], false, false, 'idx_precificacao_componentes_grupo_ativo');
        $this->forge->addKey(['grupo', 'tipo_valor'], false, false, 'idx_precificacao_componentes_grupo_tipo');
        $this->forge->createTable($this->table, true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        if ($this->db->tableExists($this->table)) {
            $this->forge->dropTable($this->table, true);
        }
    }
}

