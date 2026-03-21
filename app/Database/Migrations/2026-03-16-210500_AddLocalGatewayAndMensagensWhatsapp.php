<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddLocalGatewayAndMensagensWhatsapp extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('mensagens_whatsapp')) {
            $this->forge->addField([
                'id' => [
                    'type' => 'BIGINT',
                    'constraint' => 20,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'provider' => [
                    'type' => 'VARCHAR',
                    'constraint' => 40,
                    'default' => 'menuia',
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
                'tipo_mensagem' => [
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
                'status' => [
                    'type' => 'VARCHAR',
                    'constraint' => 30,
                    'default' => 'pendente',
                ],
                'resposta_api' => [
                    'type' => 'LONGTEXT',
                    'null' => true,
                ],
                'erro' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'payload' => [
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
            $this->forge->addKey(['status']);
            $this->forge->addForeignKey('os_id', 'os', 'id', 'SET NULL', 'CASCADE');
            $this->forge->addForeignKey('cliente_id', 'clientes', 'id', 'SET NULL', 'CASCADE');
            $this->forge->addForeignKey('usuario_id', 'usuarios', 'id', 'SET NULL', 'CASCADE');
            $this->forge->createTable('mensagens_whatsapp', true);
        }

        if ($this->db->tableExists('configuracoes')) {
            $table = $this->db->table('configuracoes');
            $defaults = [
                'whatsapp_local_nãode_url' => 'http://127.0.0.1:3001',
                'whatsapp_local_nãode_token' => '',
                'whatsapp_local_nãode_origin' => 'http://localhost:8081',
                'whatsapp_local_nãode_timeout' => '20',
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
    }

    public function down()
    {
        if ($this->db->tableExists('mensagens_whatsapp')) {
            $this->forge->dropTable('mensagens_whatsapp', true);
        }

        if ($this->db->tableExists('configuracoes')) {
            $this->db->table('configuracoes')
                ->whereIn('chave', [
                    'whatsapp_local_nãode_url',
                    'whatsapp_local_nãode_token',
                    'whatsapp_local_nãode_origin',
                    'whatsapp_local_nãode_timeout',
                ])->delete();
        }
    }
}

