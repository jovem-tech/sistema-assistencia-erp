<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDesktopProfilesAndAgentInventoryToEquipamentos extends Migration
{
    public function up()
    {
        $this->addEquipamentoColumns();
        $this->addMonitorAgentColumns();
    }

    public function down()
    {
        $this->dropMonitorAgentColumns();
        $this->dropEquipamentoColumns();
    }

    private function addEquipamentoColumns(): void
    {
        if (!$this->db->tableExists('equipamentos')) {
            return;
        }

        $fields = [];

        if (!$this->db->fieldExists('desktop_modalidade', 'equipamentos')) {
            $fields['desktop_modalidade'] = [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'null' => true,
                'after' => 'observacoes',
            ];
        }

        if (!$this->db->fieldExists('gabinete_tipo', 'equipamentos')) {
            $fields['gabinete_tipo'] = [
                'type' => 'VARCHAR',
                'constraint' => 80,
                'null' => true,
                'after' => 'desktop_modalidade',
            ];
        }

        if (!$this->db->fieldExists('gabinete_identificacao_status', 'equipamentos')) {
            $fields['gabinete_identificacao_status'] = [
                'type' => 'VARCHAR',
                'constraint' => 40,
                'null' => true,
                'after' => 'gabinete_tipo',
            ];
        }

        if (!$this->db->fieldExists('gabinete_observacao', 'equipamentos')) {
            $fields['gabinete_observacao'] = [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'after' => 'gabinete_identificacao_status',
            ];
        }

        if (!$this->db->fieldExists('placa_mae', 'equipamentos')) {
            $fields['placa_mae'] = [
                'type' => 'VARCHAR',
                'constraint' => 180,
                'null' => true,
                'after' => 'gabinete_observacao',
            ];
        }

        if (!$this->db->fieldExists('chipset', 'equipamentos')) {
            $fields['chipset'] = [
                'type' => 'VARCHAR',
                'constraint' => 120,
                'null' => true,
                'after' => 'placa_mae',
            ];
        }

        if (!$this->db->fieldExists('processador', 'equipamentos')) {
            $fields['processador'] = [
                'type' => 'VARCHAR',
                'constraint' => 180,
                'null' => true,
                'after' => 'chipset',
            ];
        }

        if (!$this->db->fieldExists('memoria_ram', 'equipamentos')) {
            $fields['memoria_ram'] = [
                'type' => 'VARCHAR',
                'constraint' => 120,
                'null' => true,
                'after' => 'processador',
            ];
        }

        if (!$this->db->fieldExists('armazenamento', 'equipamentos')) {
            $fields['armazenamento'] = [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'after' => 'memoria_ram',
            ];
        }

        if (!$this->db->fieldExists('placa_video', 'equipamentos')) {
            $fields['placa_video'] = [
                'type' => 'VARCHAR',
                'constraint' => 180,
                'null' => true,
                'after' => 'armazenamento',
            ];
        }

        if (!$this->db->fieldExists('fonte_alimentacao', 'equipamentos')) {
            $fields['fonte_alimentacao'] = [
                'type' => 'VARCHAR',
                'constraint' => 180,
                'null' => true,
                'after' => 'placa_video',
            ];
        }

        if (!$this->db->fieldExists('resumo_tecnico', 'equipamentos')) {
            $fields['resumo_tecnico'] = [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'after' => 'fonte_alimentacao',
            ];
        }

        if (!$this->db->fieldExists('configuracao_status', 'equipamentos')) {
            $fields['configuracao_status'] = [
                'type' => 'VARCHAR',
                'constraint' => 40,
                'null' => true,
                'after' => 'resumo_tecnico',
            ];
        }

        if (!$this->db->fieldExists('configuracao_origem', 'equipamentos')) {
            $fields['configuracao_origem'] = [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
                'after' => 'configuracao_status',
            ];
        }

        if (!$this->db->fieldExists('configuracao_detectada_em', 'equipamentos')) {
            $fields['configuracao_detectada_em'] = [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'configuracao_origem',
            ];
        }

        if (!empty($fields)) {
            $this->forge->addColumn('equipamentos', $fields);
        }
    }

    private function dropEquipamentoColumns(): void
    {
        if (!$this->db->tableExists('equipamentos')) {
            return;
        }

        foreach ([
            'desktop_modalidade',
            'gabinete_tipo',
            'gabinete_identificacao_status',
            'gabinete_observacao',
            'placa_mae',
            'chipset',
            'processador',
            'memoria_ram',
            'armazenamento',
            'placa_video',
            'fonte_alimentacao',
            'resumo_tecnico',
            'configuracao_status',
            'configuracao_origem',
            'configuracao_detectada_em',
        ] as $field) {
            if ($this->db->fieldExists($field, 'equipamentos')) {
                $this->forge->dropColumn('equipamentos', $field);
            }
        }
    }

    private function addMonitorAgentColumns(): void
    {
        if (!$this->db->tableExists('monitor_agents')) {
            return;
        }

        $fields = [];

        if (!$this->db->fieldExists('device_type', 'monitor_agents')) {
            $fields['device_type'] = [
                'type' => 'VARCHAR',
                'constraint' => 40,
                'null' => true,
                'after' => 'model',
            ];
        }

        if (!$this->db->fieldExists('chassis_type', 'monitor_agents')) {
            $fields['chassis_type'] = [
                'type' => 'VARCHAR',
                'constraint' => 80,
                'null' => true,
                'after' => 'device_type',
            ];
        }

        if (!$this->db->fieldExists('chipset', 'monitor_agents')) {
            $fields['chipset'] = [
                'type' => 'VARCHAR',
                'constraint' => 120,
                'null' => true,
                'after' => 'motherboard',
            ];
        }

        if (!$this->db->fieldExists('gpu', 'monitor_agents')) {
            $fields['gpu'] = [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'after' => 'cpu',
            ];
        }

        if (!$this->db->fieldExists('storage_summary', 'monitor_agents')) {
            $fields['storage_summary'] = [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'after' => 'gpu',
            ];
        }

        if (!empty($fields)) {
            $this->forge->addColumn('monitor_agents', $fields);
        }
    }

    private function dropMonitorAgentColumns(): void
    {
        if (!$this->db->tableExists('monitor_agents')) {
            return;
        }

        foreach (['device_type', 'chassis_type', 'chipset', 'gpu', 'storage_summary'] as $field) {
            if ($this->db->fieldExists($field, 'monitor_agents')) {
                $this->forge->dropColumn('monitor_agents', $field);
            }
        }
    }
}
