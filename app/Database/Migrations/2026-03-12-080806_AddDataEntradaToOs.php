<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDataEntradaToOs extends Migration
{
    public function up()
    {
        $this->forge->addColumn('os', [
            'data_entrada' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => null,
                'after'   => 'data_abertura',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('os', 'data_entrada');
    }
}
