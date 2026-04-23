<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePrecificacaoServicoOverridesTable extends Migration
{
    private string $table = 'precificacao_servico_overrides';

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
            'servico_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
            ],
            'custo_hora_produtiva' => [
                'type' => 'DECIMAL',
                'constraint' => '14,4',
                'null' => false,
                'default' => 0,
            ],
            'custos_diretos_total' => [
                'type' => 'DECIMAL',
                'constraint' => '14,4',
                'null' => false,
                'default' => 0,
            ],
            'margem_percentual' => [
                'type' => 'DECIMAL',
                'constraint' => '8,4',
                'null' => false,
                'default' => 0,
            ],
            'taxa_recebimento_percentual' => [
                'type' => 'DECIMAL',
                'constraint' => '8,4',
                'null' => false,
                'default' => 0,
            ],
            'imposto_percentual' => [
                'type' => 'DECIMAL',
                'constraint' => '8,4',
                'null' => false,
                'default' => 0,
            ],
            'tempo_tecnico_horas' => [
                'type' => 'DECIMAL',
                'constraint' => '10,4',
                'null' => false,
                'default' => 0,
            ],
            'risco_percentual' => [
                'type' => 'DECIMAL',
                'constraint' => '8,4',
                'null' => false,
                'default' => 0,
            ],
            'preco_tabela_referencia' => [
                'type' => 'DECIMAL',
                'constraint' => '14,4',
                'null' => false,
                'default' => 0,
            ],
            'ativo' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'null' => false,
                'default' => 1,
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
        $this->forge->addUniqueKey('servico_id', 'ux_precificacao_servico_overrides_servico');
        $this->forge->addKey(['ativo'], false, false, 'idx_precificacao_servico_overrides_ativo');
        $this->forge->createTable($this->table, true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        if ($this->db->tableExists($this->table)) {
            $this->forge->dropTable($this->table, true);
        }
    }
}

