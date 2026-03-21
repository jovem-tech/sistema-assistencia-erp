<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCrmAndCentralMensagens extends Migration
{
    public function up()
    {
        $this->createCrmTags();
        $this->createCrmEventos();
        $this->createCrmInteracoes();
        $this->createCrmFollowups();
        $this->createCrmPipelineEtapas();
        $this->createCrmPipeline();
        $this->createCrmOportunidades();
        $this->createCrmAutomacoes();
        $this->createConversasWhatsapp();
        $this->createConversaOs();
        $this->createConversaTags();
        $this->createRespostasRapidasWhatsapp();
        $this->patchMensagensWhatsapp();
        $this->seedPipelineEtapas();
        $this->seedRespostasRapidas();
    }

    public function down()
    {
        $this->forge->dropTable('conversa_tags', true);
        $this->forge->dropTable('conversa_os', true);
        $this->forge->dropTable('respostas_rapidas_whatsapp', true);
        $this->forge->dropTable('conversas_whatsapp', true);
        $this->forge->dropTable('crm_automacoes', true);
        $this->forge->dropTable('crm_oportunidades', true);
        $this->forge->dropTable('crm_pipeline', true);
        $this->forge->dropTable('crm_pipeline_etapas', true);
        $this->forge->dropTable('crm_followups', true);
        $this->forge->dropTable('crm_interacoes', true);
        $this->forge->dropTable('crm_eventos', true);
        $this->forge->dropTable('crm_tags_cliente', true);
        $this->forge->dropTable('crm_tags', true);
    }

    private function createCrmTags(): void
    {
        if (!$this->db->tableExists('crm_tags')) {
            $this->forge->addField([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'slug' => [
                    'type' => 'VARCHAR',
                    'constraint' => 80,
                    'null' => false,
                ],
                'nãome' => [
                    'type' => 'VARCHAR',
                    'constraint' => 120,
                    'null' => false,
                ],
                'cor' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'null' => true,
                ],
                'ativo' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 1,
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
            $this->forge->addUniqueKey('slug');
            $this->forge->createTable('crm_tags', true);
        }

        if (!$this->db->tableExists('crm_tags_cliente')) {
            $this->forge->addField([
                'id' => [
                    'type' => 'BIGINT',
                    'constraint' => 20,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'cliente_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => false,
                ],
                'tag_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => false,
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
            $this->forge->addUniqueKey(['cliente_id', 'tag_id']);
            $this->forge->addKey('cliente_id');
            $this->forge->addKey('tag_id');
            $this->forge->createTable('crm_tags_cliente', true);
        }
    }

    private function createCrmEventos(): void
    {
        if ($this->db->tableExists('crm_eventos')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'cliente_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'equipamento_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'os_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'conversa_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'tipo_evento' => [
                'type' => 'VARCHAR',
                'constraint' => 80,
                'null' => false,
            ],
            'titulo' => [
                'type' => 'VARCHAR',
                'constraint' => 180,
                'null' => false,
            ],
            'descricao' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'origem' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'default' => 'sistema',
            ],
            'usuario_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'data_evento' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'payload_jsãon' => [
                'type' => 'LONGTEXT',
                'null' => true,
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
        $this->forge->addKey(['cliente_id', 'data_evento']);
        $this->forge->addKey(['os_id', 'data_evento']);
        $this->forge->addKey('tipo_evento');
        $this->forge->addKey('conversa_id');
        $this->forge->createTable('crm_eventos', true);
    }

    private function createCrmInteracoes(): void
    {
        if ($this->db->tableExists('crm_interacoes')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'cliente_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'os_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'conversa_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'tipo' => [
                'type' => 'VARCHAR',
                'constraint' => 60,
                'null' => false,
            ],
            'descricao' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'canal' => [
                'type' => 'VARCHAR',
                'constraint' => 40,
                'null' => false,
            ],
            'usuario_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'data_interacao' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'payload_jsãon' => [
                'type' => 'LONGTEXT',
                'null' => true,
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
        $this->forge->addKey(['cliente_id', 'data_interacao']);
        $this->forge->addKey(['os_id', 'data_interacao']);
        $this->forge->addKey('conversa_id');
        $this->forge->createTable('crm_interacoes', true);
    }

    private function createCrmFollowups(): void
    {
        if ($this->db->tableExists('crm_followups')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'cliente_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'os_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'titulo' => [
                'type' => 'VARCHAR',
                'constraint' => 180,
                'null' => false,
            ],
            'descricao' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'data_prevista' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'default' => 'pendente',
            ],
            'usuario_responsavel' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'origem_evento' => [
                'type' => 'VARCHAR',
                'constraint' => 80,
                'null' => true,
            ],
            'concluido_em' => [
                'type' => 'DATETIME',
                'null' => true,
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
        $this->forge->addKey(['status', 'data_prevista']);
        $this->forge->addKey('cliente_id');
        $this->forge->addKey('os_id');
        $this->forge->createTable('crm_followups', true);
    }

    private function createCrmPipelineEtapas(): void
    {
        if ($this->db->tableExists('crm_pipeline_etapas')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'codigo' => [
                'type' => 'VARCHAR',
                'constraint' => 80,
                'null' => false,
            ],
            'nãome' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
                'null' => false,
            ],
            'ordem' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ],
            'ativo' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
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
        $this->forge->addKey('ordem');
        $this->forge->createTable('crm_pipeline_etapas', true);
    }

    private function createCrmPipeline(): void
    {
        if ($this->db->tableExists('crm_pipeline')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'cliente_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'os_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'etapa_atual' => [
                'type' => 'VARCHAR',
                'constraint' => 80,
                'null' => false,
            ],
            'data_entrada_etapa' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'usuario_responsavel' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'default' => 'ativo',
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
        $this->forge->addUniqueKey('os_id');
        $this->forge->addKey(['etapa_atual', 'status']);
        $this->forge->addKey('cliente_id');
        $this->forge->createTable('crm_pipeline', true);
    }

    private function createCrmOportunidades(): void
    {
        if ($this->db->tableExists('crm_oportunidades')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'cliente_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'os_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'titulo' => [
                'type' => 'VARCHAR',
                'constraint' => 180,
                'null' => false,
            ],
            'descricao' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'valor_estimado' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'default' => 0,
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 40,
                'default' => 'aberta',
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
        $this->forge->addKey('cliente_id');
        $this->forge->addKey('status');
        $this->forge->createTable('crm_oportunidades', true);
    }

    private function createCrmAutomacoes(): void
    {
        if ($this->db->tableExists('crm_automacoes')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'codigo' => [
                'type' => 'VARCHAR',
                'constraint' => 80,
                'null' => false,
            ],
            'nãome' => [
                'type' => 'VARCHAR',
                'constraint' => 140,
                'null' => false,
            ],
            'descricao' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'gatilho' => [
                'type' => 'VARCHAR',
                'constraint' => 80,
                'null' => false,
            ],
            'ativo' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
            ],
            'config_jsãon' => [
                'type' => 'LONGTEXT',
                'null' => true,
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
        $this->forge->createTable('crm_automacoes', true);
    }

    private function createConversasWhatsapp(): void
    {
        if ($this->db->tableExists('conversas_whatsapp')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'cliente_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'os_id_principal' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'telefone' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'null' => false,
            ],
            'nãome_contato' => [
                'type' => 'VARCHAR',
                'constraint' => 140,
                'null' => true,
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'default' => 'aberta',
            ],
            'responsavel_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'ultima_mensagem_em' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'nao_lidas' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ],
            'origem_provider' => [
                'type' => 'VARCHAR',
                'constraint' => 40,
                'null' => true,
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
        $this->forge->addUniqueKey('telefone');
        $this->forge->addKey(['status', 'ultima_mensagem_em']);
        $this->forge->addKey('cliente_id');
        $this->forge->createTable('conversas_whatsapp', true);
    }

    private function createConversaOs(): void
    {
        if ($this->db->tableExists('conversa_os')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'conversa_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => false,
            ],
            'os_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => false,
            ],
            'principal' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
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
        $this->forge->addUniqueKey(['conversa_id', 'os_id']);
        $this->forge->addKey(['conversa_id', 'principal']);
        $this->forge->createTable('conversa_os', true);
    }

    private function createConversaTags(): void
    {
        if ($this->db->tableExists('conversa_tags')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'conversa_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => false,
            ],
            'tag_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
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
        $this->forge->addUniqueKey(['conversa_id', 'tag_id']);
        $this->forge->addKey('conversa_id');
        $this->forge->createTable('conversa_tags', true);
    }

    private function createRespostasRapidasWhatsapp(): void
    {
        if ($this->db->tableExists('respostas_rapidas_whatsapp')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'titulo' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
                'null' => false,
            ],
            'mensagem' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'ativo' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
            ],
            'ordem' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
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
        $this->forge->addKey(['ativo', 'ordem']);
        $this->forge->createTable('respostas_rapidas_whatsapp', true);
    }

    private function patchMensagensWhatsapp(): void
    {
        if (!$this->db->tableExists('mensagens_whatsapp')) {
            return;
        }

        $this->safeAddColumn('mensagens_whatsapp', 'conversa_id', 'BIGINT(20) UNSIGNED NULL AFTER id');
        $this->safeAddColumn('mensagens_whatsapp', 'provider_message_id', 'VARCHAR(120) NULL AFTER provider');
        $this->safeAddColumn('mensagens_whatsapp', 'direcao', "VARCHAR(20) NOT NULL DEFAULT 'outbound' AFTER provider_message_id");
        $this->safeAddColumn('mensagens_whatsapp', 'tipo_conteudo', "VARCHAR(30) NOT NULL DEFAULT 'texto' AFTER direcao");
        $this->safeAddColumn('mensagens_whatsapp', 'anexo_path', 'VARCHAR(255) NULL AFTER arquivo');
        $this->safeAddColumn('mensagens_whatsapp', 'lida_em', 'DATETIME NULL AFTER erro');
        $this->safeAddColumn('mensagens_whatsapp', 'enviada_em', 'DATETIME NULL AFTER lida_em');

        $this->safeCreateIndex('mensagens_whatsapp', 'idx_mêsgw_conversa_created', '(conversa_id, created_at)');
        $this->safeCreateIndex('mensagens_whatsapp', 'idx_mêsgw_direcao_status', '(direcao, status)');
    }

    private function safeAddColumn(string $table, string $field, string $definition): void
    {
        if ($this->db->fieldExists($field, $table)) {
            return;
        }
        $this->db->query("ALTER TABLE {$table} ADD COLUMN {$field} {$definition}");
    }

    private function safeCreateIndex(string $table, string $name, string $columnsSql): void
    {
        try {
            $this->db->query("CREATE INDEX {$name} ON {$table} {$columnsSql}");
        } catch (\Throwable $e) {
            // ignãore duplicated index or unsupported engine errors
        }
    }

    private function seedPipelineEtapas(): void
    {
        if (!$this->db->tableExists('crm_pipeline_etapas')) {
            return;
        }

        $table = $this->db->table('crm_pipeline_etapas');
        $rows = [
            ['nãovo_atendimento', 'Nãovo Atendimento', 10],
            ['equipamento_recebido', 'Equipamento Recebido', 20],
            ['em_diagnãostico', 'Em Diagnãostico', 30],
            ['aguardando_aprovacao', 'Aguardando Aprovacao', 40],
            ['em_reparo', 'Em Reparo', 50],
            ['pronto_retirada', 'Pronto para Retirada', 60],
            ['entregue', 'Entregue', 70],
            ['pos_atendimento', 'Pos-atendimento', 80],
        ];
        $nãow = date('Y-m-d H:i:s');

        foreach ($rows as [$codigo, $nãome, $ordem]) {
            $exists = $table->where('codigo', $codigo)->countAllResults();
            if ($exists > 0) {
                continue;
            }
            $table->insert([
                'codigo' => $codigo,
                'nãome' => $nãome,
                'ordem' => $ordem,
                'ativo' => 1,
                'created_at' => $nãow,
                'updated_at' => $nãow,
            ]);
        }
    }

    private function seedRespostasRapidas(): void
    {
        if (!$this->db->tableExists('respostas_rapidas_whatsapp')) {
            return;
        }

        $table = $this->db->table('respostas_rapidas_whatsapp');
        $rows = [
            ['OS aberta', 'Ola, {cliente}. Sua OS {numero_os} foi aberta com sucessão.', 10],
            ['Orcamento enviado', 'Ola, {cliente}. O orcamento da OS {numero_os} foi enviado e aguarda sua aprovacao.', 20],
            ['Aguardando peca', 'Atualizacao da OS {numero_os}: estamos aguardando a chegada da peca para continuar.', 30],
            ['Pronto para retirada', 'Ola, {cliente}. Seu equipamento da OS {numero_os} esta pronto para retirada.', 40],
            ['Entrega concluida', 'A OS {numero_os} foi encerrada com entrega concluida. Obrigado pela preferencia.', 50],
        ];
        $nãow = date('Y-m-d H:i:s');

        foreach ($rows as [$titulo, $mensagem, $ordem]) {
            $exists = $table->where('titulo', $titulo)->countAllResults();
            if ($exists > 0) {
                continue;
            }
            $table->insert([
                'titulo' => $titulo,
                'mensagem' => $mensagem,
                'ativo' => 1,
                'ordem' => $ordem,
                'created_at' => $nãow,
                'updated_at' => $nãow,
            ]);
        }
    }
}

