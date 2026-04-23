<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPrecificacaoCamposServicos extends Migration
{
    private string $table = 'servicos';

    public function up()
    {
        if (! $this->db->tableExists($this->table)) {
            return;
        }

        $fields = [];

        if (! $this->db->fieldExists('tempo_padrao_horas', $this->table)) {
            $fields['tempo_padrao_horas'] = [
                'type' => 'DECIMAL',
                'constraint' => '6,2',
                'null' => false,
                'default' => 1.00,
                'after' => 'valor',
            ];
        }

        if (! $this->db->fieldExists('custo_direto_padrao', $this->table)) {
            $fields['custo_direto_padrao'] = [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => false,
                'default' => 0.00,
                'after' => 'tempo_padrao_horas',
            ];
        }

        if ($fields !== []) {
            $this->forge->addColumn($this->table, $fields);
        }
    }

    public function down()
    {
        if (! $this->db->tableExists($this->table)) {
            return;
        }

        if ($this->db->fieldExists('custo_direto_padrao', $this->table)) {
            $this->forge->dropColumn($this->table, 'custo_direto_padrao');
        }
        if ($this->db->fieldExists('tempo_padrao_horas', $this->table)) {
            $this->forge->dropColumn($this->table, 'tempo_padrao_horas');
        }
    }
}

