<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddObservacoesEstadoToChecklistExecucoes extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('checklist_execucoes')) {
            return;
        }

        if ($this->db->fieldExists('observacoes_estado', 'checklist_execucoes')) {
            return;
        }

        $this->forge->addColumn('checklist_execucoes', [
            'observacoes_estado' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'resumo_texto',
            ],
        ]);
    }

    public function down()
    {
        if (!$this->db->tableExists('checklist_execucoes')) {
            return;
        }

        if (!$this->db->fieldExists('observacoes_estado', 'checklist_execucoes')) {
            return;
        }

        $this->forge->dropColumn('checklist_execucoes', 'observacoes_estado');
    }
}
