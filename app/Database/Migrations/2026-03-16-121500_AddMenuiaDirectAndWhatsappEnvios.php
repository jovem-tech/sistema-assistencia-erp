<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMenuiaDirectAndWhatsappEnvios extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('whatsapp_envios')) {
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
                'telefone' => [
                    'type' => 'VARCHAR',
                    'constraint' => 30,
                    'null' => false,
                ],
                'tipo_envio' => [
                    'type' => 'VARCHAR',
                    'constraint' => 30,
                    'default' => 'direto',
                ],
                'tipo_conteudo' => [
                    'type' => 'VARCHAR',
                    'constraint' => 30,
                    'default' => 'texto',
                ],
                'template_codigo' => [
                    'type' => 'VARCHAR',
                    'constraint' => 80,
                    'null' => true,
                ],
                'mensagem' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'arquivo' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                ],
                'provedor' => [
                    'type' => 'VARCHAR',
                    'constraint' => 40,
                    'null' => true,
                ],
                'status' => [
                    'type' => 'VARCHAR',
                    'constraint' => 30,
                    'default' => 'pendente',
                ],
                'resposta_api' => [
                    'type' => 'LONGTEXT',
                    'null' => true,
                ],
                'usuario_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
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
            $this->forge->addKey(['os_id', 'created_at']);
            $this->forge->addKey(['cliente_id', 'created_at']);
            $this->forge->addKey('status');
            $this->forge->addForeignKey('os_id', 'os', 'id', 'SET NULL', 'CASCADE');
            $this->forge->addForeignKey('cliente_id', 'clientes', 'id', 'SET NULL', 'CASCADE');
            $this->forge->addForeignKey('usuario_id', 'usuarios', 'id', 'SET NULL', 'CASCADE');
            $this->forge->createTable('whatsapp_envios', true);
        }

        if ($this->db->tableExists('configuracoes')) {
            $table = $this->db->table('configuracoes');
            $defaults = [
                'whatsapp_direct_provider' => 'menuia',
                'whatsapp_bulk_provider' => 'meta_oficial',
                'whatsapp_menuia_url' => 'https://api.menuia.com/api',
                'whatsapp_menuia_authkey' => '',
                'whatsapp_menuia_appkey' => '',
                'whatsapp_test_phone' => '',
            ];

            foreach ($defaults as $chave => $valor) {
                $exists = $table->where('chave', $chave)->countAllResults();
                if ($exists > 0) {
                    continue;
                }
                $table->insert([
                    'chave' => $chave,
                    'valor' => $valor,
                    'tipo' => 'texto',
                ]);
            }
        }

        if ($this->db->tableExists('whatsapp_templates')) {
            $tplTable = $this->db->table('whatsapp_templates');
            $templates = [
                'laudo_concluido' => [
                    'nome' => 'Laudo concluido',
                    'evento' => 'status_reparado_disponivel_loja',
                    'conteudo' => 'O laudo da OS {{numero_os}} foi concluido. Segue o PDF em anexo.',
                ],
                'devolucao_sem_reparo' => [
                    'nome' => 'Devolucao sem reparo',
                    'evento' => 'status_devolvido_sem_reparo',
                    'conteudo' => 'A OS {{numero_os}} foi encerrada sem reparo. Segue o documento em anexo.',
                ],
            ];

            foreach ($templates as $codigo => $cfg) {
                $exists = $tplTable->where('codigo', $codigo)->countAllResults();
                if ($exists > 0) {
                    continue;
                }
                $tplTable->insert([
                    'codigo' => $codigo,
                    'nome' => $cfg['nome'],
                    'evento' => $cfg['evento'],
                    'conteudo' => $cfg['conteudo'],
                    'ativo' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }

    public function down()
    {
        if ($this->db->tableExists('whatsapp_envios')) {
            $this->forge->dropTable('whatsapp_envios', true);
        }

        if ($this->db->tableExists('configuracoes')) {
            $this->db->table('configuracoes')
                ->whereIn('chave', [
                    'whatsapp_direct_provider',
                    'whatsapp_bulk_provider',
                    'whatsapp_menuia_url',
                    'whatsapp_menuia_authkey',
                    'whatsapp_menuia_appkey',
                    'whatsapp_test_phone',
                ])->delete();
        }
    }
}
