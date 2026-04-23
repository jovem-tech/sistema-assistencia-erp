<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPrecificacaoFieldsToItensTables extends Migration
{
    /**
     * @return array<string,array<string,mixed>>
     */
    private function fieldsDefinition(): array
    {
        return [
            'preco_custo_referencia' => [
                'type' => 'DECIMAL',
                'constraint' => '12,2',
                'null' => true,
            ],
            'preco_venda_referencia' => [
                'type' => 'DECIMAL',
                'constraint' => '12,2',
                'null' => true,
            ],
            'preco_base' => [
                'type' => 'DECIMAL',
                'constraint' => '12,2',
                'null' => true,
            ],
            'percentual_encargos' => [
                'type' => 'DECIMAL',
                'constraint' => '7,2',
                'null' => true,
            ],
            'valor_encargos' => [
                'type' => 'DECIMAL',
                'constraint' => '12,2',
                'null' => true,
            ],
            'percentual_margem' => [
                'type' => 'DECIMAL',
                'constraint' => '7,2',
                'null' => true,
            ],
            'valor_margem' => [
                'type' => 'DECIMAL',
                'constraint' => '12,2',
                'null' => true,
            ],
            'valor_recomendado' => [
                'type' => 'DECIMAL',
                'constraint' => '12,2',
                'null' => true,
            ],
            'modo_precificacao' => [
                'type' => 'VARCHAR',
                'constraint' => 40,
                'null' => true,
            ],
        ];
    }

    public function up()
    {
        $definitions = $this->fieldsDefinition();
        $tables = ['orcamento_itens', 'os_itens'];

        foreach ($tables as $table) {
            if (!$this->db->tableExists($table)) {
                continue;
            }
            foreach ($definitions as $field => $definition) {
                if ($this->db->fieldExists($field, $table)) {
                    continue;
                }
                $this->forge->addColumn($table, [$field => $definition]);
            }
        }
    }

    public function down()
    {
        $fields = array_keys($this->fieldsDefinition());
        $tables = ['orcamento_itens', 'os_itens'];

        foreach ($tables as $table) {
            if (!$this->db->tableExists($table)) {
                continue;
            }
            foreach ($fields as $field) {
                if (!$this->db->fieldExists($field, $table)) {
                    continue;
                }
                $this->forge->dropColumn($table, $field);
            }
        }
    }
}
