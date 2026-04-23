<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddProcedimentosExecutadosToOs extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('os')) {
            return;
        }

        if ($this->db->fieldExists('procedimentos_executados', 'os')) {
            return;
        }

        $this->forge->addColumn('os', [
            'procedimentos_executados' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'solucao_aplicada',
            ],
        ]);
    }

    public function down()
    {
        if (!$this->db->tableExists('os')) {
            return;
        }

        if ($this->db->fieldExists('procedimentos_executados', 'os')) {
            $this->forge->dropColumn('os', 'procedimentos_executados');
        }
    }
}
