<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateOrcamentosValidadeDefaultToTen extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('orcamentos') || !$this->db->fieldExists('validade_dias', 'orcamentos')) {
            return;
        }

        $this->db->query('ALTER TABLE orcamentos MODIFY validade_dias INT(11) NOT NULL DEFAULT 10');
    }

    public function down()
    {
        if (!$this->db->tableExists('orcamentos') || !$this->db->fieldExists('validade_dias', 'orcamentos')) {
            return;
        }

        $this->db->query('ALTER TABLE orcamentos MODIFY validade_dias INT(11) NOT NULL DEFAULT 7');
    }
}

