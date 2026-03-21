<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCrmMensagensSeedTagsAutomacoes extends Migration
{
    public function up()
    {
        $this->createCrmMensagens();
        $this->seedCrmTags();
        $this->seedCrmAutomacoes();
    }

    public function down()
    {
        $this->forge->dropTable('crm_mensagens', true);

        if ($this->db->tableExists('crm_automacoes')) {
            $this->db->table('crm_automacoes')->whereIn('codigo', [
                'auto_followup_autorizacao',
                'auto_pos_atendimento_7d',
                'auto_cliente_inativo_180d',
            ])->delete();
        }

        if ($this->db->tableExists('crm_tags')) {
            $this->db->table('crm_tags')->whereIn('slug', [
                'novo',
                'recorrente',
                'vip',
                'inativo',
                'garantia',
                'empresarial',
                'residencial',
            ])->delete();
        }
    }

    private function createCrmMensagens(): void
    {
        if ($this->db->tableExists('crm_mensagens')) {
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
            'canal' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'default' => 'whatsapp',
            ],
            'provider' => [
                'type' => 'VARCHAR',
                'constraint' => 40,
                'null' => true,
            ],
            'direcao' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => false,
            ],
            'tipo_conteudo' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'default' => 'texto',
            ],
            'conteudo' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'arquivo' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'default' => 'registrada',
            ],
            'payload_json' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'usuario_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'data_mensagem' => [
                'type' => 'DATETIME',
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
        $this->forge->addKey(['cliente_id', 'data_mensagem']);
        $this->forge->addKey(['os_id', 'data_mensagem']);
        $this->forge->addKey(['conversa_id', 'data_mensagem']);
        $this->forge->addKey(['direcao', 'status']);
        $this->forge->createTable('crm_mensagens', true);
    }

    private function seedCrmTags(): void
    {
        if (!$this->db->tableExists('crm_tags')) {
            return;
        }

        $rows = [
            ['slug' => 'novo', 'nome' => 'Cliente Novo', 'cor' => '#3B82F6'],
            ['slug' => 'recorrente', 'nome' => 'Recorrente', 'cor' => '#10B981'],
            ['slug' => 'vip', 'nome' => 'VIP', 'cor' => '#8B5CF6'],
            ['slug' => 'inativo', 'nome' => 'Inativo', 'cor' => '#F59E0B'],
            ['slug' => 'garantia', 'nome' => 'Garantia', 'cor' => '#06B6D4'],
            ['slug' => 'empresarial', 'nome' => 'Empresarial', 'cor' => '#1F2937'],
            ['slug' => 'residencial', 'nome' => 'Residencial', 'cor' => '#EC4899'],
        ];

        $table = $this->db->table('crm_tags');
        $now = date('Y-m-d H:i:s');
        foreach ($rows as $row) {
            $exists = $table->where('slug', $row['slug'])->countAllResults();
            if ($exists > 0) {
                continue;
            }
            $table->insert([
                'slug' => $row['slug'],
                'nome' => $row['nome'],
                'cor' => $row['cor'],
                'ativo' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function seedCrmAutomacoes(): void
    {
        if (!$this->db->tableExists('crm_automacoes')) {
            return;
        }

        $rows = [
            [
                'codigo' => 'auto_followup_autorizacao',
                'nome' => 'Follow-up de autorizacao (2 dias)',
                'descricao' => 'Quando status da OS entrar em aguardando autorizacao, criar follow-up em 2 dias.',
                'gatilho' => 'status_aguardando_autorizacao',
                'config_json' => json_encode(['delay_days' => 2], JSON_UNESCAPED_UNICODE),
            ],
            [
                'codigo' => 'auto_pos_atendimento_7d',
                'nome' => 'Pos-atendimento em 7 dias',
                'descricao' => 'Quando OS for entregue, agendar retorno de satisfacao em 7 dias.',
                'gatilho' => 'status_entregue_reparado',
                'config_json' => json_encode(['delay_days' => 7], JSON_UNESCAPED_UNICODE),
            ],
            [
                'codigo' => 'auto_cliente_inativo_180d',
                'nome' => 'Reativacao de clientes inativos (180 dias)',
                'descricao' => 'Identificar clientes sem OS recente para campanha de reativacao.',
                'gatilho' => 'cliente_inativo_180d',
                'config_json' => json_encode(['days_without_os' => 180], JSON_UNESCAPED_UNICODE),
            ],
        ];

        $table = $this->db->table('crm_automacoes');
        $now = date('Y-m-d H:i:s');
        foreach ($rows as $row) {
            $exists = $table->where('codigo', $row['codigo'])->countAllResults();
            if ($exists > 0) {
                continue;
            }
            $table->insert([
                'codigo' => $row['codigo'],
                'nome' => $row['nome'],
                'descricao' => $row['descricao'],
                'gatilho' => $row['gatilho'],
                'ativo' => 1,
                'config_json' => $row['config_json'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
