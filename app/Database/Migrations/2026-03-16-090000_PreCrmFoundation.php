<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class PreCrmFoundation extends Migration
{
    public function up()
    {
        $db = $this->db;

        if ($db->tableExists('os')) {
            if ($db->fieldExists('status', 'os')) {
                $db->query("ALTER TABLE os MODIFY COLUMN status VARCHAR(80) NOT NULL DEFAULT 'triagem'");
            }

            if (! $db->fieldExists('estado_fluxo', 'os')) {
                $db->query("ALTER TABLE os ADD COLUMN estado_fluxo VARCHAR(40) NOT NULL DEFAULT 'em_atendimento' AFTER status");
            }

            if (! $db->fieldExists('status_atualizado_em', 'os')) {
                $db->query('ALTER TABLE os ADD COLUMN status_atualizado_em DATETIME NULL AFTER estado_fluxo');
            }
        }

        if (! $db->tableExists('os_status')) {
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
                'nãome' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 120,
                    'null'       => false,
                ],
                'grupo_macro' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 60,
                    'null'       => false,
                ],
                'icone' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 60,
                    'null'       => true,
                ],
                'cor' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 30,
                    'null'       => true,
                ],
                'ordem_fluxo' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'default'    => 0,
                ],
                'status_final' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 0,
                ],
                'status_pausa' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 0,
                ],
                'gera_evento_crm' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 1,
                ],
                'estado_fluxo_padrao' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 40,
                    'default'    => 'em_atendimento',
                ],
                'ativo' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 1,
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
            $this->forge->addKey(['grupo_macro', 'ordem_fluxo']);
            $this->forge->createTable('os_status', true);
        }

        if (! $db->tableExists('os_status_transicoes')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'status_origem_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'status_destinão_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'ativo' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 1,
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
            $this->forge->addUniqueKey(['status_origem_id', 'status_destinão_id']);
            $this->forge->addForeignKey('status_origem_id', 'os_status', 'id', 'CASCADE', 'CASCADE');
            $this->forge->addForeignKey('status_destinão_id', 'os_status', 'id', 'CASCADE', 'CASCADE');
            $this->forge->createTable('os_status_transicoes', true);
        }

        if (! $db->tableExists('os_status_historico')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'BIGINT',
                    'constraint'     => 20,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'os_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                ],
                'status_anterior' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 80,
                    'null'       => true,
                ],
                'status_nãovo' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 80,
                    'null'       => false,
                ],
                'estado_fluxo' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 40,
                    'null'       => true,
                ],
                'usuario_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => true,
                ],
                'observacao' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey(['os_id', 'created_at']);
            $this->forge->addKey(['status_nãovo']);
            $this->forge->addForeignKey('os_id', 'os', 'id', 'CASCADE', 'CASCADE');
            $this->forge->addForeignKey('usuario_id', 'usuarios', 'id', 'SET NULL', 'CASCADE');
            $this->forge->createTable('os_status_historico', true);
        }

        if (! $db->tableExists('whatsapp_templates')) {
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
                'nãome' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 140,
                    'null'       => false,
                ],
                'evento' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 80,
                    'null'       => true,
                ],
                'conteudo' => [
                    'type' => 'TEXT',
                    'null' => false,
                ],
                'ativo' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 1,
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
            $this->forge->createTable('whatsapp_templates', true);
        }

        if (! $db->tableExists('whatsapp_mensagens')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'BIGINT',
                    'constraint'     => 20,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'os_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => true,
                ],
                'cliente_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => true,
                ],
                'template_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                ],
                'provedor' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 40,
                    'default'    => 'menuia',
                ],
                'tipo_evento' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 80,
                    'null'       => true,
                ],
                'telefone' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 30,
                    'null'       => false,
                ],
                'conteudo' => [
                    'type' => 'TEXT',
                    'null' => false,
                ],
                'status_envio' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 30,
                    'default'    => 'pendente',
                ],
                'api_message_id' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 120,
                    'null'       => true,
                ],
                'api_response' => [
                    'type' => 'LONGTEXT',
                    'null' => true,
                ],
                'erro_detalhe' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'enviado_por' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => true,
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
            $this->forge->addKey(['os_id', 'created_at']);
            $this->forge->addKey(['status_envio']);
            $this->forge->addForeignKey('os_id', 'os', 'id', 'SET NULL', 'CASCADE');
            $this->forge->addForeignKey('cliente_id', 'clientes', 'id', 'SET NULL', 'CASCADE');
            $this->forge->addForeignKey('template_id', 'whatsapp_templates', 'id', 'SET NULL', 'CASCADE');
            $this->forge->addForeignKey('enviado_por', 'usuarios', 'id', 'SET NULL', 'CASCADE');
            $this->forge->createTable('whatsapp_mensagens', true);
        }

        if (! $db->tableExists('whatsapp_inbound')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'BIGINT',
                    'constraint'     => 20,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'provedor' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 40,
                    'default'    => 'menuia',
                ],
                'remetente' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 30,
                    'null'       => true,
                ],
                'conteudo' => [
                    'type' => 'LONGTEXT',
                    'null' => true,
                ],
                'payload' => [
                    'type' => 'LONGTEXT',
                    'null' => true,
                ],
                'processado' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
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
            $this->forge->addKey('processado');
            $this->forge->createTable('whatsapp_inbound', true);
        }

        if (! $db->tableExists('os_documentos')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'BIGINT',
                    'constraint'     => 20,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'os_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                ],
                'tipo_documento' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => false,
                ],
                'arquivo' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => false,
                ],
                'versao' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'default'    => 1,
                ],
                'hash_sha1' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 40,
                    'null'       => true,
                ],
                'gerado_por' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => true,
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
            $this->forge->addKey(['os_id', 'tipo_documento']);
            $this->forge->addForeignKey('os_id', 'os', 'id', 'CASCADE', 'CASCADE');
            $this->forge->addForeignKey('gerado_por', 'usuarios', 'id', 'SET NULL', 'CASCADE');
            $this->forge->createTable('os_documentos', true);
        }

        $this->seedOsStatus();
        $this->seedOsTransitions();
        $this->seedWhatsappTemplates();
        $this->seedConfigDefaults();
        $this->migrateLegacyOsStatusCodes();
    }

    public function down()
    {
        $this->forge->dropTable('os_documentos', true);
        $this->forge->dropTable('whatsapp_inbound', true);
        $this->forge->dropTable('whatsapp_mensagens', true);
        $this->forge->dropTable('whatsapp_templates', true);
        $this->forge->dropTable('os_status_historico', true);
        $this->forge->dropTable('os_status_transicoes', true);
        $this->forge->dropTable('os_status', true);
    }

    private function seedOsStatus(): void
    {
        $table = $this->db->table('os_status');
        $existing = array_column($table->select('codigo')->get()->getResultArray(), 'codigo');

        $statuses = [
            ['triagem', 'Triagem', 'recepcao', 'bi-search', 'secondary', 10, 0, 0, 1, 'em_atendimento'],
            ['diagnãostico', 'Diagnãostico Tecnico', 'diagnãostico', 'bi-heart-pulse', 'primary', 20, 0, 0, 1, 'em_atendimento'],
            ['aguardando_avaliacao', 'Aguardando Avaliacao', 'diagnãostico', 'bi-hourglass-split', 'info', 30, 0, 0, 1, 'em_atendimento'],
            ['verificacao_garantia', 'Verificacao de Garantia', 'diagnãostico', 'bi-shield-check', 'info', 40, 0, 0, 1, 'em_atendimento'],
            ['aguardando_orcamento', 'Aguardando Orcamento', 'orcamento', 'bi-cash-stack', 'indigo', 50, 0, 0, 1, 'em_atendimento'],
            ['aguardando_autorizacao', 'Aguardando Autorizacao', 'orcamento', 'bi-pencil-square', 'purple', 60, 0, 1, 1, 'pausado'],
            ['aguardando_reparo', 'Aguardando Reparo', 'execucao', 'bi-tools', 'warning', 70, 0, 0, 1, 'em_execucao'],
            ['reparo_execucao', 'Em Execucao do Servico', 'execucao', 'bi-wrench-adjustable', 'warning', 80, 0, 0, 1, 'em_execucao'],
            ['cumprimento_garantia', 'Cumprimento de Garantia', 'execucao', 'bi-ticket-perforated', 'warning', 90, 0, 0, 1, 'em_execucao'],
            ['retrabalho', 'Retrabalho', 'execucao', 'bi-arrow-repeat', 'warning', 100, 0, 0, 1, 'em_execucao'],
            ['testes_operacionais', 'Testes Operacionais', 'qualidade', 'bi-clipboard2-pulse', 'primary', 110, 0, 0, 1, 'em_execucao'],
            ['aguardando_peca', 'Aguardando Peca', 'interrupcao', 'bi-box-seam', 'orange', 120, 0, 1, 1, 'pausado'],
            ['pagamento_pendente', 'Pagamento Pendente', 'interrupcao', 'bi-cash-coin', 'orange', 130, 0, 1, 1, 'pausado'],
            ['entregue_pagamento_pendente', 'Entregue - Pendencia Financeira', 'interrupcao', 'bi-wallet2', 'orange', 140, 0, 1, 1, 'pausado'],
            ['testes_finais', 'Testes Finais', 'qualidade', 'bi-check2-square', 'primary', 150, 0, 0, 1, 'em_execucao'],
            ['reparo_concluido', 'Reparo Concluido', 'concluido', 'bi-check-circle', 'success', 160, 0, 0, 1, 'pronto'],
            ['reparado_disponivel_loja', 'Reparado, Disponivel na Loja', 'concluido', 'bi-shop', 'success', 170, 0, 0, 1, 'pronto'],
            ['garantia_concluida', 'Garantia Concluida', 'concluido', 'bi-patch-check', 'success', 180, 0, 0, 1, 'pronto'],
            ['irreparavel', 'Irreparavel', 'finalizado_sem_reparo', 'bi-x-octagon', 'danger', 190, 1, 0, 1, 'encerrado'],
            ['irreparavel_disponivel_loja', 'Irreparavel, Disponivel para Retirada', 'finalizado_sem_reparo', 'bi-shop-window', 'danger', 200, 1, 0, 1, 'pronto'],
            ['reparo_recusado', 'Reparo Recusado', 'finalizado_sem_reparo', 'bi-hand-thumbs-down', 'danger', 210, 1, 0, 1, 'encerrado'],
            ['entregue_reparado', 'Equipamento Entregue', 'encerrado', 'bi-box-arrow-right', 'dark', 220, 1, 0, 1, 'encerrado'],
            ['devolvido_sem_reparo', 'Devolvido Sem Reparo', 'encerrado', 'bi-arrow-return-left', 'dark', 230, 1, 0, 1, 'encerrado'],
            ['descartado', 'Equipamento Descartado', 'encerrado', 'bi-recycle', 'dark', 240, 1, 0, 1, 'encerrado'],
            ['cancelado', 'Cancelado', 'cancelado', 'bi-slash-circle', 'secondary', 250, 1, 0, 1, 'cancelado'],
        ];

        $nãow = date('Y-m-d H:i:s');
        foreach ($statuses as $row) {
            if (in_array($row[0], $existing, true)) {
                continue;
            }
            $table->insert([
                'codigo'              => $row[0],
                'nãome'                => $row[1],
                'grupo_macro'         => $row[2],
                'icone'               => $row[3],
                'cor'                 => $row[4],
                'ordem_fluxo'         => $row[5],
                'status_final'        => $row[6],
                'status_pausa'        => $row[7],
                'gera_evento_crm'     => $row[8],
                'estado_fluxo_padrao' => $row[9],
                'ativo'               => 1,
                'created_at'          => $nãow,
                'updated_at'          => $nãow,
            ]);
        }
    }

    private function seedOsTransitions(): void
    {
        $statusRows = $this->db->table('os_status')->select('id, codigo')->get()->getResultArray();
        if (empty($statusRows)) {
            return;
        }

        $map = [];
        foreach ($statusRows as $row) {
            $map[$row['codigo']] = (int) $row['id'];
        }

        $transitions = [
            ['triagem', 'diagnãostico'],
            ['diagnãostico', 'aguardando_avaliacao'],
            ['diagnãostico', 'verificacao_garantia'],
            ['diagnãostico', 'aguardando_orcamento'],
            ['aguardando_avaliacao', 'aguardando_orcamento'],
            ['verificacao_garantia', 'aguardando_orcamento'],
            ['aguardando_orcamento', 'aguardando_autorizacao'],
            ['aguardando_autorizacao', 'aguardando_reparo'],
            ['aguardando_autorizacao', 'reparo_recusado'],
            ['aguardando_reparo', 'reparo_execucao'],
            ['reparo_execucao', 'testes_operacionais'],
            ['testes_operacionais', 'testes_finais'],
            ['testes_finais', 'reparo_concluido'],
            ['reparo_concluido', 'reparado_disponivel_loja'],
            ['reparado_disponivel_loja', 'entregue_reparado'],
            ['aguardando_reparo', 'aguardando_peca'],
            ['reparo_execucao', 'aguardando_peca'],
            ['testes_operacionais', 'aguardando_peca'],
            ['aguardando_peca', 'reparo_execucao'],
            ['reparo_execucao', 'pagamento_pendente'],
            ['pagamento_pendente', 'entregue_pagamento_pendente'],
            ['pagamento_pendente', 'reparado_disponivel_loja'],
            ['reparo_execucao', 'retrabalho'],
            ['retrabalho', 'testes_operacionais'],
            ['reparo_execucao', 'irreparavel'],
            ['irreparavel', 'irreparavel_disponivel_loja'],
            ['irreparavel_disponivel_loja', 'devolvido_sem_reparo'],
            ['reparo_recusado', 'devolvido_sem_reparo'],
            ['triagem', 'cancelado'],
            ['diagnãostico', 'cancelado'],
            ['aguardando_orcamento', 'cancelado'],
            ['aguardando_autorizacao', 'cancelado'],
            ['reparo_execucao', 'cancelado'],
            ['aguardando_peca', 'cancelado'],
        ];

        $table = $this->db->table('os_status_transicoes');
        $nãow = date('Y-m-d H:i:s');
        foreach ($transitions as [$from, $to]) {
            if (!isset($map[$from], $map[$to])) {
                continue;
            }
            $exists = $table
                ->where('status_origem_id', $map[$from])
                ->where('status_destinão_id', $map[$to])
                ->countAllResults();
            if ($exists > 0) {
                continue;
            }
            $table->insert([
                'status_origem_id' => $map[$from],
                'status_destinão_id' => $map[$to],
                'ativo' => 1,
                'created_at' => $nãow,
                'updated_at' => $nãow,
            ]);
        }
    }

    private function seedWhatsappTemplates(): void
    {
        $table = $this->db->table('whatsapp_templates');
        $existing = array_column($table->select('codigo')->get()->getResultArray(), 'codigo');
        $nãow = date('Y-m-d H:i:s');

        $templates = [
            ['os_aberta', 'OS aberta', 'os_aberta', 'Sua OS {{numero_os}} foi aberta em {{data_abertura}}. Equipamento: {{equipamento}}.'],
            ['orcamento_enviado', 'Orcamento enviado', 'status_aguardando_autorizacao', 'Orcamento da OS {{numero_os}} enviado. Valor: {{valor_final}}. Responda para aprovar ou recusar.'],
            ['aguardando_autorizacao', 'Aguardando autorizacao', 'status_aguardando_autorizacao', 'A OS {{numero_os}} esta aguardando sua autorizacao para seguir com o reparo.'],
            ['aguardando_peca', 'Aguardando peca', 'status_aguardando_peca', 'A OS {{numero_os}} esta pausada aguardando chegada de peca.'],
            ['pronto_retirada', 'Pronto para retirada', 'status_reparado_disponivel_loja', 'Seu equipamento da OS {{numero_os}} esta pronto para retirada.'],
            ['entrega_concluida', 'Entrega concluida', 'status_entregue_reparado', 'A OS {{numero_os}} foi encerrada com entrega concluida. Obrigado pela confianca.'],
            ['cobranca_retirada', 'Cobranca de retirada', 'followup_retirada', 'A OS {{numero_os}} esta pronta e aguardando retirada na loja.'],
            ['pos_atendimento', 'Pos-atendimento', 'followup_satisfacao', 'Como foi sua experiencia com a OS {{numero_os}}? Sua opiniao e importante.'],
        ];

        foreach ($templates as [$codigo, $nãome, $evento, $conteudo]) {
            if (in_array($codigo, $existing, true)) {
                continue;
            }
            $table->insert([
                'codigo' => $codigo,
                'nãome' => $nãome,
                'evento' => $evento,
                'conteudo' => $conteudo,
                'ativo' => 1,
                'created_at' => $nãow,
                'updated_at' => $nãow,
            ]);
        }
    }

    private function seedConfigDefaults(): void
    {
        if (! $this->db->tableExists('configuracoes')) {
            return;
        }

        $table = $this->db->table('configuracoes');
        $rows = $table->select('chave')->whereIn('chave', [
            'whatsapp_provider',
            'whatsapp_menuia_url',
            'whatsapp_menuia_token',
            'whatsapp_menuia_instance',
            'whatsapp_enabled',
        ])->get()->getResultArray();
        $existing = array_column($rows, 'chave');

        $defaults = [
            'whatsapp_provider' => 'menuia',
            'whatsapp_menuia_url' => '',
            'whatsapp_menuia_token' => '',
            'whatsapp_menuia_instance' => '',
            'whatsapp_enabled' => '0',
        ];

        foreach ($defaults as $key => $value) {
            if (in_array($key, $existing, true)) {
                continue;
            }
            $table->insert([
                'chave' => $key,
                'valor' => $value,
                'tipo' => 'texto',
            ]);
        }
    }

    private function migrateLegacyOsStatusCodes(): void
    {
        if (! $this->db->tableExists('os')) {
            return;
        }

        $legacyMap = [
            'aguardando_analise' => 'triagem',
            'aguardando_aprovacao' => 'aguardando_autorizacao',
            'aprovado' => 'aguardando_reparo',
            'reprovado' => 'reparo_recusado',
            'em_reparo' => 'reparo_execucao',
            'pronto' => 'reparado_disponivel_loja',
            'entregue' => 'entregue_reparado',
        ];

        foreach ($legacyMap as $old => $new) {
            $this->db->table('os')->where('status', $old)->update(['status' => $new]);
        }

        $this->db->query("
            UPDATE os o
            LEFT JOIN os_status s ON s.codigo = o.status
            SET o.estado_fluxo = COALESCE(s.estado_fluxo_padrao, 'em_atendimento'),
                o.status_atualizado_em = COALESCE(o.status_atualizado_em, NOW())
        ");
    }
}
