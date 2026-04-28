<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateOsPdfTemplatesTable extends Migration
{
    private string $table = 'os_pdf_templates';

    public function up()
    {
        if (!$this->db->tableExists($this->table)) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'codigo' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 80,
                    'null'       => false,
                ],
                'nome' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 140,
                    'null'       => false,
                ],
                'descricao' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'conteudo_html' => [
                    'type' => 'MEDIUMTEXT',
                    'null' => false,
                ],
                'ativo' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 1,
                ],
                'ordem' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'default'    => 0,
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
            $this->forge->addUniqueKey('codigo');
            $this->forge->addKey(['ativo', 'ordem']);
            $this->forge->createTable($this->table, true);
        }

        $this->seedDefaults();
    }

    public function down()
    {
        $this->forge->dropTable($this->table, true);
    }

    private function seedDefaults(): void
    {
        $table = $this->db->table($this->table);
        $existing = array_column($table->select('codigo')->get()->getResultArray(), 'codigo');
        $now = date('Y-m-d H:i:s');

        $templates = [
            [
                'codigo' => 'abertura',
                'nome' => 'Comprovante de abertura',
                'descricao' => 'Comprovante inicial da entrada do equipamento e do relato do cliente.',
                'ordem' => 10,
                'conteudo_html' => <<<'HTML'
<div class="highlight-box">Este documento confirma a abertura da ordem de serviço <strong>{{numero_os}}</strong> em {{data_abertura}}.</div>
<div class="section-title">Dados do cliente</div>
<table class="grid">
    <tr><td class="label">Cliente</td><td>{{cliente_nome}}</td><td class="label">Telefone</td><td>{{cliente_telefone}}</td></tr>
    <tr><td class="label">E-mail</td><td>{{cliente_email}}</td><td class="label">Prioridade</td><td>{{prioridade}}</td></tr>
</table>
<div class="section-title">Equipamento recebido</div>
<table class="grid">
    <tr><td class="label">Tipo</td><td>{{equipamento_tipo}}</td><td class="label">Marca</td><td>{{equipamento_marca}}</td></tr>
    <tr><td class="label">Modelo</td><td>{{equipamento_modelo}}</td><td class="label">Série</td><td>{{equipamento_serie}}</td></tr>
</table>
<div class="section-title">Relato do cliente</div>
<div class="highlight-box">{{relato_cliente}}</div>
<div class="section-title">Acessórios recebidos</div>
{{acessorios_html}}
<div class="section-title">Estado físico informado</div>
{{estado_fisico_html}}
<div class="footer-note">Guarde este comprovante para acompanhar a OS e validar os itens registrados na entrada.</div>
HTML,
            ],
            [
                'codigo' => 'laudo',
                'nome' => 'Laudo técnico',
                'descricao' => 'Documento técnico com diagnóstico, solução e procedimentos executados.',
                'ordem' => 20,
                'conteudo_html' => <<<'HTML'
<div class="section-title">Contexto da OS</div>
<table class="grid">
    <tr><td class="label">Número</td><td>{{numero_os}}</td><td class="label">Status atual</td><td>{{status_atual}}</td></tr>
    <tr><td class="label">Cliente</td><td>{{cliente_nome}}</td><td class="label">Equipamento</td><td>{{equipamento_resumo}}</td></tr>
    <tr><td class="label">Entrada</td><td>{{data_entrada}}</td><td class="label">Previsão</td><td>{{data_previsao}}</td></tr>
</table>
<div class="section-title">Diagnóstico técnico</div>
<div class="highlight-box">{{diagnostico}}</div>
<div class="section-title">Solução aplicada</div>
<div class="highlight-box">{{solucao_aplicada}}</div>
<div class="section-title">Procedimentos executados</div>
{{procedimentos_executados_html}}
<div class="section-title">Observações para o cliente</div>
<div class="highlight-box">{{observacoes_cliente}}</div>
HTML,
            ],
            [
                'codigo' => 'cobranca_manutencao',
                'nome' => 'Cobrança / manutenção',
                'descricao' => 'Resumo financeiro e técnico para cobrança ou manutenção autorizada.',
                'ordem' => 30,
                'conteudo_html' => <<<'HTML'
<div class="highlight-box">Resumo financeiro emitido para a OS <strong>{{numero_os}}</strong>, com base nos itens lançados e nos serviços realizados.</div>
<div class="section-title">Equipamento e atendimento</div>
<table class="grid">
    <tr><td class="label">Cliente</td><td>{{cliente_nome}}</td><td class="label">Equipamento</td><td>{{equipamento_resumo}}</td></tr>
    <tr><td class="label">Status</td><td>{{status_atual}}</td><td class="label">Forma de pagamento</td><td>{{forma_pagamento}}</td></tr>
</table>
<div class="section-title">Serviços</div>
{{servicos_html}}
<div class="section-title">Peças</div>
{{pecas_html}}
<div class="section-title">Resumo financeiro</div>
{{resumo_financeiro_html}}
HTML,
            ],
            [
                'codigo' => 'entrega',
                'nome' => 'Comprovante de entrega',
                'descricao' => 'Comprovante final de retirada ou entrega do equipamento.',
                'ordem' => 40,
                'conteudo_html' => <<<'HTML'
<div class="highlight-box">Este comprovante registra a entrega do equipamento vinculado à OS <strong>{{numero_os}}</strong>.</div>
<div class="section-title">Dados da entrega</div>
<table class="grid">
    <tr><td class="label">Cliente</td><td>{{cliente_nome}}</td><td class="label">Equipamento</td><td>{{equipamento_resumo}}</td></tr>
    <tr><td class="label">Status</td><td>{{status_atual}}</td><td class="label">Data de entrega</td><td>{{data_entrega}}</td></tr>
    <tr><td class="label">Forma de pagamento</td><td>{{forma_pagamento}}</td><td class="label">Valor final</td><td>{{valor_final}}</td></tr>
</table>
<div class="section-title">Solução aplicada</div>
<div class="highlight-box">{{solucao_aplicada}}</div>
<div class="section-title">Garantia e valores</div>
{{resumo_financeiro_html}}
HTML,
            ],
            [
                'codigo' => 'devolucao_sem_reparo',
                'nome' => 'Devolução sem reparo',
                'descricao' => 'Documento para devolução quando a OS é encerrada sem execução do reparo.',
                'ordem' => 50,
                'conteudo_html' => <<<'HTML'
<div class="highlight-box">Este documento formaliza a devolução do equipamento sem reparo executado na OS <strong>{{numero_os}}</strong>.</div>
<div class="section-title">Dados da OS</div>
<table class="grid">
    <tr><td class="label">Cliente</td><td>{{cliente_nome}}</td><td class="label">Equipamento</td><td>{{equipamento_resumo}}</td></tr>
    <tr><td class="label">Status atual</td><td>{{status_atual}}</td><td class="label">Entrada</td><td>{{data_entrada}}</td></tr>
</table>
<div class="section-title">Relato do cliente</div>
<div class="highlight-box">{{relato_cliente}}</div>
<div class="section-title">Diagnóstico / motivo</div>
<div class="highlight-box">{{diagnostico}}</div>
<div class="section-title">Observações</div>
<div class="highlight-box">{{observacoes_cliente}}</div>
HTML,
            ],
        ];

        foreach ($templates as $template) {
            if (in_array($template['codigo'], $existing, true)) {
                continue;
            }

            $table->insert([
                'codigo' => $template['codigo'],
                'nome' => $template['nome'],
                'descricao' => $template['descricao'],
                'conteudo_html' => $template['conteudo_html'],
                'ativo' => 1,
                'ordem' => $template['ordem'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
