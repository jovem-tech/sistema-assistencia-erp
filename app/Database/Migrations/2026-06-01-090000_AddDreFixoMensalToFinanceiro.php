<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDreFixoMensalToFinanceiro extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('financeiro') || $this->db->fieldExists('dre_fixo_mensal', 'financeiro')) {
            return;
        }

        $this->forge->addColumn('financeiro', [
            'dre_fixo_mensal' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
        ]);
    }

    public function down()
    {
        if (! $this->db->tableExists('financeiro') || ! $this->db->fieldExists('dre_fixo_mensal', 'financeiro')) {
            return;
        }

        $this->forge->dropColumn('financeiro', 'dre_fixo_mensal');
    }
}
