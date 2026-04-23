<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDetalhesToPrecificacaoServicoOverrides extends Migration
{
    private string $table = 'precificacao_servico_overrides';

    public function up()
    {
        if (! $this->db->tableExists($this->table)) {
            return;
        }

        $fields = [];

        if (! $this->db->fieldExists('custos_fixos_mensais', $this->table)) {
            $fields['custos_fixos_mensais'] = [
                'type' => 'DECIMAL',
                'constraint' => '14,4',
                'null' => false,
                'default' => 0,
                'after' => 'preco_tabela_referencia',
            ];
        }
        if (! $this->db->fieldExists('tecnicos_ativos', $this->table)) {
            $fields['tecnicos_ativos'] = [
                'type' => 'DECIMAL',
                'constraint' => '10,4',
                'null' => false,
                'default' => 1,
                'after' => 'custos_fixos_mensais',
            ];
        }
        if (! $this->db->fieldExists('horas_produtivas_dia', $this->table)) {
            $fields['horas_produtivas_dia'] = [
                'type' => 'DECIMAL',
                'constraint' => '10,4',
                'null' => false,
                'default' => 0,
                'after' => 'tecnicos_ativos',
            ];
        }
        if (! $this->db->fieldExists('dias_uteis_mes', $this->table)) {
            $fields['dias_uteis_mes'] = [
                'type' => 'DECIMAL',
                'constraint' => '10,4',
                'null' => false,
                'default' => 1,
                'after' => 'horas_produtivas_dia',
            ];
        }
        if (! $this->db->fieldExists('consumiveis_valor', $this->table)) {
            $fields['consumiveis_valor'] = [
                'type' => 'DECIMAL',
                'constraint' => '14,4',
                'null' => false,
                'default' => 0,
                'after' => 'dias_uteis_mes',
            ];
        }
        if (! $this->db->fieldExists('tempo_indireto_horas', $this->table)) {
            $fields['tempo_indireto_horas'] = [
                'type' => 'DECIMAL',
                'constraint' => '10,4',
                'null' => false,
                'default' => 0,
                'after' => 'consumiveis_valor',
            ];
        }
        if (! $this->db->fieldExists('reserva_garantia_valor', $this->table)) {
            $fields['reserva_garantia_valor'] = [
                'type' => 'DECIMAL',
                'constraint' => '14,4',
                'null' => false,
                'default' => 0,
                'after' => 'tempo_indireto_horas',
            ];
        }
        if (! $this->db->fieldExists('perdas_pequenas_valor', $this->table)) {
            $fields['perdas_pequenas_valor'] = [
                'type' => 'DECIMAL',
                'constraint' => '14,4',
                'null' => false,
                'default' => 0,
                'after' => 'reserva_garantia_valor',
            ];
        }
        if (! $this->db->fieldExists('tempo_desmontagem_min', $this->table)) {
            $fields['tempo_desmontagem_min'] = [
                'type' => 'DECIMAL',
                'constraint' => '10,4',
                'null' => false,
                'default' => 0,
                'after' => 'perdas_pequenas_valor',
            ];
        }
        if (! $this->db->fieldExists('tempo_substituicao_min', $this->table)) {
            $fields['tempo_substituicao_min'] = [
                'type' => 'DECIMAL',
                'constraint' => '10,4',
                'null' => false,
                'default' => 0,
                'after' => 'tempo_desmontagem_min',
            ];
        }
        if (! $this->db->fieldExists('tempo_montagem_min', $this->table)) {
            $fields['tempo_montagem_min'] = [
                'type' => 'DECIMAL',
                'constraint' => '10,4',
                'null' => false,
                'default' => 0,
                'after' => 'tempo_substituicao_min',
            ];
        }
        if (! $this->db->fieldExists('tempo_teste_final_min', $this->table)) {
            $fields['tempo_teste_final_min'] = [
                'type' => 'DECIMAL',
                'constraint' => '10,4',
                'null' => false,
                'default' => 0,
                'after' => 'tempo_montagem_min',
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

        $columns = [
            'custos_fixos_mensais',
            'tecnicos_ativos',
            'horas_produtivas_dia',
            'dias_uteis_mes',
            'consumiveis_valor',
            'tempo_indireto_horas',
            'reserva_garantia_valor',
            'perdas_pequenas_valor',
            'tempo_desmontagem_min',
            'tempo_substituicao_min',
            'tempo_montagem_min',
            'tempo_teste_final_min',
        ];

        foreach ($columns as $column) {
            if ($this->db->fieldExists($column, $this->table)) {
                $this->forge->dropColumn($this->table, $column);
            }
        }
    }
}
