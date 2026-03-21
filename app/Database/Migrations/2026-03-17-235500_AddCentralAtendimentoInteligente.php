<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCentralAtendimentoInteligente extends Migration
{
    public function up()
    {
        $this->patchConversasWhatsapp();
        $this->patchMensagensWhatsapp();
        $this->patchRespostasRapidasWhatsapp();

        $this->createChatbotIntencoes();
        $this->createChatbotFaq();
        $this->createChatbotFluxos();
        $this->createChatbotLogs();
        $this->createChatbotRegrasErp();
        $this->createMensageriaMetricasDiarias();

        $this->seedChatbotIntencoes();
        $this->seedChatbotFaq();
        $this->seedChatbotFluxos();
        $this->seedChatbotRegrasErp();
    }

    public function down()
    {
        $this->forge->dropTable('mensageria_metricas_diarias', true);
        $this->forge->dropTable('chatbot_regras_erp', true);
        $this->forge->dropTable('chatbot_logs', true);
        $this->forge->dropTable('chatbot_fluxos', true);
        $this->forge->dropTable('chatbot_faq', true);
        $this->forge->dropTable('chatbot_intencoes', true);

        $this->safeDropColumn('respostas_rapidas_whatsapp', 'categoria');

        $this->safeDropColumn('mensagens_whatsapp', 'mime_type');
        $this->safeDropColumn('mensagens_whatsapp', 'recebida_em');
        $this->safeDropColumn('mensagens_whatsapp', 'enviada_por_bot');
        $this->safeDropColumn('mensagens_whatsapp', 'enviada_por_usuario_id');

        $this->safeDropColumn('conversas_whatsapp', 'canal');
        $this->safeDropColumn('conversas_whatsapp', 'primeira_mensagem_em');
        $this->safeDropColumn('conversas_whatsapp', 'automacao_ativa');
        $this->safeDropColumn('conversas_whatsapp', 'aguardando_humanão');
        $this->safeDropColumn('conversas_whatsapp', 'prioridade');
    }

    private function patchConversasWhatsapp(): void
    {
        if (!$this->db->tableExists('conversas_whatsapp')) {
            return;
        }

        $this->safeAddColumn('conversas_whatsapp', 'canal', "VARCHAR(30) NOT NULL DEFAULT 'whatsapp' AFTER origem_provider");
        $this->safeAddColumn('conversas_whatsapp', 'primeira_mensagem_em', "DATETIME NULL AFTER ultima_mensagem_em");
        $this->safeAddColumn('conversas_whatsapp', 'automacao_ativa', "TINYINT(1) NOT NULL DEFAULT 1 AFTER nao_lidas");
        $this->safeAddColumn('conversas_whatsapp', 'aguardando_humanão', "TINYINT(1) NOT NULL DEFAULT 0 AFTER automacao_ativa");
        $this->safeAddColumn('conversas_whatsapp', 'prioridade', "VARCHAR(30) NOT NULL DEFAULT 'nãormal' AFTER aguardando_humanão");

        $this->safeCreateIndex('conversas_whatsapp', 'idx_conv_automacao_humanão', '(automacao_ativa, aguardando_humanão)');
        $this->safeCreateIndex('conversas_whatsapp', 'idx_conv_prioridade', '(prioridade, ultima_mensagem_em)');
    }

    private function patchMensagensWhatsapp(): void
    {
        if (!$this->db->tableExists('mensagens_whatsapp')) {
            return;
        }

        $this->safeAddColumn('mensagens_whatsapp', 'mime_type', 'VARCHAR(120) NULL AFTER tipo_conteudo');
        $this->safeAddColumn('mensagens_whatsapp', 'recebida_em', 'DATETIME NULL AFTER enviada_em');
        $this->safeAddColumn('mensagens_whatsapp', 'enviada_por_bot', 'TINYINT(1) NOT NULL DEFAULT 0 AFTER usuario_id');
        $this->safeAddColumn('mensagens_whatsapp', 'enviada_por_usuario_id', 'INT(11) NULL AFTER enviada_por_bot');

        $this->safeCreateIndex('mensagens_whatsapp', 'idx_mêsgw_conversa_direcao', '(conversa_id, direcao, created_at)');
        $this->safeCreateIndex('mensagens_whatsapp', 'idx_mêsgw_bot', '(enviada_por_bot, created_at)');
    }

    private function patchRespostasRapidasWhatsapp(): void
    {
        if (!$this->db->tableExists('respostas_rapidas_whatsapp')) {
            return;
        }

        $this->safeAddColumn('respostas_rapidas_whatsapp', 'categoria', 'VARCHAR(80) NULL AFTER titulo');
        $this->safeCreateIndex('respostas_rapidas_whatsapp', 'idx_rrw_categoria_ativo_ordem', '(categoria, ativo, ordem)');
    }

    private function createChatbotIntencoes(): void
    {
        if ($this->db->tableExists('chatbot_intencoes')) {
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
                'constraint' => 140,
                'null' => false,
            ],
            'descricao' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'gatilhos_jsãon' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'resposta_padrao' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'exige_consulta_erp' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
            'acao_sistema' => [
                'type' => 'VARCHAR',
                'constraint' => 80,
                'null' => true,
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
        $this->forge->addKey(['ativo', 'ordem']);
        $this->forge->createTable('chatbot_intencoes', true);
    }

    private function createChatbotFaq(): void
    {
        if ($this->db->tableExists('chatbot_faq')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'pergunta' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ],
            'resposta' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'categoria' => [
                'type' => 'VARCHAR',
                'constraint' => 80,
                'null' => true,
            ],
            'palavras_chave_jsãon' => [
                'type' => 'LONGTEXT',
                'null' => true,
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
        $this->forge->addKey(['categoria', 'ativo', 'ordem']);
        $this->forge->createTable('chatbot_faq', true);
    }

    private function createChatbotFluxos(): void
    {
        if ($this->db->tableExists('chatbot_fluxos')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
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
            'tipo_fluxo' => [
                'type' => 'VARCHAR',
                'constraint' => 60,
                'null' => false,
            ],
            'etapas_jsãon' => [
                'type' => 'LONGTEXT',
                'null' => true,
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
        $this->forge->addKey(['tipo_fluxo', 'ativo', 'ordem']);
        $this->forge->createTable('chatbot_fluxos', true);
    }

    private function createChatbotLogs(): void
    {
        if ($this->db->tableExists('chatbot_logs')) {
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
                'null' => true,
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
            'mensagem_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'mensagem_recebida' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'intencao_detectada' => [
                'type' => 'VARCHAR',
                'constraint' => 80,
                'null' => true,
            ],
            'confianca' => [
                'type' => 'DECIMAL',
                'constraint' => '5,2',
                'null' => true,
            ],
            'resposta_gerada' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'tipo_resposta' => [
                'type' => 'VARCHAR',
                'constraint' => 40,
                'default' => 'manual',
            ],
            'escalado_humanão' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
            'usuario_responsavel' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
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
        $this->forge->addKey(['created_at']);
        $this->forge->addKey(['conversa_id', 'created_at']);
        $this->forge->addKey(['intencao_detectada', 'created_at']);
        $this->forge->addKey(['escalado_humanão', 'created_at']);
        $this->forge->createTable('chatbot_logs', true);
    }

    private function createChatbotRegrasErp(): void
    {
        if ($this->db->tableExists('chatbot_regras_erp')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'nãome' => [
                'type' => 'VARCHAR',
                'constraint' => 180,
                'null' => false,
            ],
            'evento_origem' => [
                'type' => 'VARCHAR',
                'constraint' => 80,
                'null' => false,
            ],
            'condicao_jsãon' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'acao_jsãon' => [
                'type' => 'LONGTEXT',
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
        $this->forge->addKey(['evento_origem', 'ativo']);
        $this->forge->createTable('chatbot_regras_erp', true);
    }

    private function createMensageriaMetricasDiarias(): void
    {
        if ($this->db->tableExists('mensageria_metricas_diarias')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'data_referencia' => [
                'type' => 'DATE',
                'null' => false,
            ],
            'mensagens_recebidas' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ],
            'mensagens_enviadas' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ],
            'mensagens_automaticas' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ],
            'mensagens_humanas' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ],
            'conversas_abertas' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ],
            'conversas_finalizadas' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ],
            'tempo_medio_primeira_resposta' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'default' => 0,
            ],
            'tempo_medio_resposta_total' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'default' => 0,
            ],
            'taxa_resãolucao_automatica' => [
                'type' => 'DECIMAL',
                'constraint' => '6,2',
                'default' => 0,
            ],
            'taxa_escalonamento_humanão' => [
                'type' => 'DECIMAL',
                'constraint' => '6,2',
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
        $this->forge->addUniqueKey('data_referencia');
        $this->forge->createTable('mensageria_metricas_diarias', true);
    }

    private function seedChatbotIntencoes(): void
    {
        if (!$this->db->tableExists('chatbot_intencoes')) {
            return;
        }

        $rows = [
            ['consultar_status_os', 'Consultar status da OS', 'Perguntas sãobre andamento e status do reparo', ['status', 'andamento', 'ficou pronto', 'ja ficou pronto', 'já ficou pronto', 'como esta', 'como está', 'minha os'], true, 'consultar_os_status', 10],
            ['consultar_orcamento', 'Consultar orcamento', 'Perguntas sãobre valor e orcamento', ['orcamento', 'orçamento', 'valor', 'preco', 'preço', 'quanto ficou'], true, 'consultar_orcamento', 20],
            ['aprovar_orcamento', 'Aprovar orcamento', 'Cliente informa aprovacao do orcamento', ['aprovar', 'aprovado', 'autorizo', 'pode fazer', 'segue com reparo'], true, 'aprovar_orcamento', 30],
            ['recusar_orcamento', 'Recusar orcamento', 'Cliente informa recusa do orcamento', ['recusar', 'nao aprovo', 'não aprovo', 'cancelar reparo', 'nao quero', 'não quero'], true, 'recusar_orcamento', 40],
            ['consultar_previsao', 'Consultar previsao', 'Perguntas sãobre previsao de entrega', ['previsao', 'previsão', 'prazo', 'quando fica pronto', 'quando fica'], true, 'consultar_previsao', 50],
            ['horario_atendimento', 'Horario de atendimento', 'Perguntas sãobre horario da loja', ['horario', 'horário', 'abre', 'funcionamento', 'que horas'], false, 'faq_horario', 60],
            ['endereco_loja', 'Endereco da loja', 'Perguntas sãobre localizacao', ['endereco', 'endereço', 'localizacao', 'localização', 'onde fica', 'como chegar'], false, 'faq_endereco', 70],
            ['formas_pagamento', 'Formas de pagamento', 'Perguntas sãobre pagamento e parcelamento', ['pagamento', 'pix', 'cartao', 'cartão', 'parcelar', 'dinheiro'], false, 'faq_pagamento', 80],
            ['garantia', 'Garantia do servico', 'Perguntas sãobre garantia do reparo', ['garantia', 'retornão', 'garantido'], true, 'consultar_garantia', 90],
            ['falar_humanão', 'Falar com atendente', 'Cliente sãolicita atendimento humanão', ['atendente', 'humanão', 'suporte', 'falar com alguem', 'falar com alguém', 'vendedor'], false, 'escalar_humanão', 100],
        ];

        $table = $this->db->table('chatbot_intencoes');
        $nãow = date('Y-m-d H:i:s');
        foreach ($rows as [$codigo, $nãome, $descricao, $gatilhos, $exigeConsultaErp, $acaoSistema, $ordem]) {
            $exists = $table->where('codigo', $codigo)->countAllResults();
            if ($exists > 0) {
                continue;
            }

            $table->insert([
                'codigo' => $codigo,
                'nãome' => $nãome,
                'descricao' => $descricao,
                'gatilhos_jsãon' => jsãon_encode($gatilhos, JSON_UNESCAPED_UNICODE),
                'resposta_padrao' => null,
                'exige_consulta_erp' => $exigeConsultaErp ? 1 : 0,
                'acao_sistema' => $acaoSistema,
                'ordem' => $ordem,
                'ativo' => 1,
                'created_at' => $nãow,
                'updated_at' => $nãow,
            ]);
        }
    }

    private function seedChatbotFaq(): void
    {
        if (!$this->db->tableExists('chatbot_faq')) {
            return;
        }

        $rows = [
            ['Qual o horario de atendimento?', 'Nãossão horario de atendimento e de segunda a sexta das 08:00 as 18:00 e sabado das 08:00 as 12:00.', 'Atendimento', ['horario', 'abre', 'funcionamento', 'sábado'], 10],
            ['Qual o endereco da loja?', 'Vocêe encontra nãossa loja não endereco cadastrado não ERP. Se preferir, sãolicite um atendente para enviar a localizacao não mapa.', 'Atendimento', ['endereco', 'localizacao', 'onde fica'], 20],
            ['Quais formas de pagamento sao aceitas?', 'Aceitamos PIX, cartao de debito, cartao de credito e dinheiro. Parcelamento sujeito as regras da loja.', 'Financeiro', ['pagamento', 'pix', 'cartao', 'parcelar'], 30],
            ['Como funciona a garantia do reparo?', 'A garantia varia conforme o servico executado e pecas aplicadas. Possão consultar sua OS e informar o prazo exato.', 'Garantia', ['garantia', 'retornão', 'prazo de garantia'], 40],
        ];

        $table = $this->db->table('chatbot_faq');
        $nãow = date('Y-m-d H:i:s');
        foreach ($rows as [$pergunta, $resposta, $categoria, $palavrasChave, $ordem]) {
            $exists = $table->where('pergunta', $pergunta)->countAllResults();
            if ($exists > 0) {
                continue;
            }

            $table->insert([
                'pergunta' => $pergunta,
                'resposta' => $resposta,
                'categoria' => $categoria,
                'palavras_chave_jsãon' => jsãon_encode($palavrasChave, JSON_UNESCAPED_UNICODE),
                'ordem' => $ordem,
                'ativo' => 1,
                'created_at' => $nãow,
                'updated_at' => $nãow,
            ]);
        }
    }

    private function seedChatbotFluxos(): void
    {
        if (!$this->db->tableExists('chatbot_fluxos')) {
            return;
        }

        $rows = [
            [
                'nãome' => 'Acompanhamento de OS',
                'descricao' => 'Fluxo para respostas automaticas sãobre status, previsao e orientacao de retirada.',
                'tipo_fluxo' => 'operacional',
                'etapas_jsãon' => jsãon_encode(['recepcao', 'diagnãostico', 'orcamento', 'execucao', 'pronto_retirada', 'entregue'], JSON_UNESCAPED_UNICODE),
                'ordem' => 10,
            ],
            [
                'nãome' => 'Aprovacao de Orcamento',
                'descricao' => 'Fluxo para capturar aprovacao/recusa e acionar equipe humana quando necessario.',
                'tipo_fluxo' => 'orcamento',
                'etapas_jsãon' => jsãon_encode(['aguardando_autorizacao', 'resposta_cliente', 'confirmacao_humana'], JSON_UNESCAPED_UNICODE),
                'ordem' => 20,
            ],
            [
                'nãome' => 'Pos-atendimento',
                'descricao' => 'Fluxo de follow-up apos entrega para medir satisfacao e fidelizacao.',
                'tipo_fluxo' => 'relacionamento',
                'etapas_jsãon' => jsãon_encode(['entrega', 'pesquisa_satisfacao', 'fidelizacao'], JSON_UNESCAPED_UNICODE),
                'ordem' => 30,
            ],
        ];

        $table = $this->db->table('chatbot_fluxos');
        $nãow = date('Y-m-d H:i:s');
        foreach ($rows as $row) {
            $exists = $table->where('nãome', $row['nãome'])->countAllResults();
            if ($exists > 0) {
                continue;
            }
            $row['ativo'] = 1;
            $row['created_at'] = $nãow;
            $row['updated_at'] = $nãow;
            $table->insert($row);
        }
    }

    private function seedChatbotRegrasErp(): void
    {
        if (!$this->db->tableExists('chatbot_regras_erp')) {
            return;
        }

        $rows = [
            [
                'nãome' => 'Avisão de equipamento pronto para retirada',
                'evento_origem' => 'os_status_alterado',
                'condicao_jsãon' => jsãon_encode(['status' => 'reparado_disponivel_loja'], JSON_UNESCAPED_UNICODE),
                'acao_jsãon' => jsãon_encode(['tipo' => 'template', 'template' => 'pronto_retirada', 'pdf_tipo' => 'laudo'], JSON_UNESCAPED_UNICODE),
            ],
            [
                'nãome' => 'Avisão de orcamento aguardando autorizacao',
                'evento_origem' => 'os_status_alterado',
                'condicao_jsãon' => jsãon_encode(['status' => 'aguardando_autorizacao'], JSON_UNESCAPED_UNICODE),
                'acao_jsãon' => jsãon_encode(['tipo' => 'template', 'template' => 'aguardando_autorizacao'], JSON_UNESCAPED_UNICODE),
            ],
            [
                'nãome' => 'Pos-atendimento automatico apos entrega',
                'evento_origem' => 'os_status_alterado',
                'condicao_jsãon' => jsãon_encode(['status' => 'entregue_reparado'], JSON_UNESCAPED_UNICODE),
                'acao_jsãon' => jsãon_encode(['tipo' => 'followup', 'delay_days' => 7], JSON_UNESCAPED_UNICODE),
            ],
        ];

        $table = $this->db->table('chatbot_regras_erp');
        $nãow = date('Y-m-d H:i:s');
        foreach ($rows as $row) {
            $exists = $table->where('nãome', $row['nãome'])->countAllResults();
            if ($exists > 0) {
                continue;
            }

            $table->insert([
                'nãome' => $row['nãome'],
                'evento_origem' => $row['evento_origem'],
                'condicao_jsãon' => $row['condicao_jsãon'],
                'acao_jsãon' => $row['acao_jsãon'],
                'ativo' => 1,
                'created_at' => $nãow,
                'updated_at' => $nãow,
            ]);
        }
    }

    private function safeAddColumn(string $table, string $field, string $definition): void
    {
        if (!$this->db->tableExists($table)) {
            return;
        }
        if ($this->db->fieldExists($field, $table)) {
            return;
        }

        $this->db->query("ALTER TABLE {$table} ADD COLUMN {$field} {$definition}");
    }

    private function safeDropColumn(string $table, string $field): void
    {
        if (!$this->db->tableExists($table)) {
            return;
        }
        if (!$this->db->fieldExists($field, $table)) {
            return;
        }

        $this->db->query("ALTER TABLE {$table} DROP COLUMN {$field}");
    }

    private function safeCreateIndex(string $table, string $name, string $columnsSql): void
    {
        if (!$this->db->tableExists($table)) {
            return;
        }

        try {
            $this->db->query("CREATE INDEX {$name} ON {$table} {$columnsSql}");
        } catch (\Throwable $e) {
            // ignãore duplicated index or unsupported engine errors
        }
    }
}
