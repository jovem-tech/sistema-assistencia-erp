<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMobilePwaInfrastructure extends Migration
{
    public function up()
    {
        $this->createMobileApiTokens();
        $this->createMobilePushSubscriptions();
        $this->createMobileNotifications();
        $this->createMobileNotificationTargets();
        $this->createMobileEventOutbox();
    }

    public function down()
    {
        if ($this->db->tableExists('mobile_event_outbox')) {
            $this->forge->dropTable('mobile_event_outbox', true);
        }

        if ($this->db->tableExists('mobile_notification_targets')) {
            $this->forge->dropTable('mobile_notification_targets', true);
        }

        if ($this->db->tableExists('mobile_notifications')) {
            $this->forge->dropTable('mobile_notifications', true);
        }

        if ($this->db->tableExists('mobile_push_subscriptions')) {
            $this->forge->dropTable('mobile_push_subscriptions', true);
        }

        if ($this->db->tableExists('mobile_api_tokens')) {
            $this->forge->dropTable('mobile_api_tokens', true);
        }
    }

    private function createMobileApiTokens(): void
    {
        if ($this->db->tableExists('mobile_api_tokens')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'usuario_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'token_hash' => [
                'type' => 'CHAR',
                'constraint' => 64,
                'null' => false,
            ],
            'token_name' => [
                'type' => 'VARCHAR',
                'constraint' => 80,
                'default' => 'mobile',
            ],
            'scope' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'ultimo_uso_em' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'expira_em' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'revogado_em' => [
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
        $this->forge->addUniqueKey('token_hash', 'ux_mobile_api_tokens_hash');
        $this->forge->addKey('usuario_id', false, false, 'idx_mobile_api_tokens_usuario');
        $this->forge->addKey('expira_em', false, false, 'idx_mobile_api_tokens_expira');
        $this->forge->addKey('revogado_em', false, false, 'idx_mobile_api_tokens_revogado');
        $this->forge->createTable('mobile_api_tokens', true);
    }

    private function createMobilePushSubscriptions(): void
    {
        if ($this->db->tableExists('mobile_push_subscriptions')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'usuario_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'endpoint_hash' => [
                'type' => 'CHAR',
                'constraint' => 64,
                'null' => false,
            ],
            'endpoint' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'chave_p256dh' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'chave_auth' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'user_agent' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'device_label' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
                'null' => true,
            ],
            'ativo' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
            ],
            'ultimo_ping_em' => [
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
        $this->forge->addUniqueKey('endpoint_hash', 'ux_mobile_push_endpoint_hash');
        $this->forge->addKey('usuario_id', false, false, 'idx_mobile_push_usuario');
        $this->forge->addKey('ativo', false, false, 'idx_mobile_push_ativo');
        $this->forge->createTable('mobile_push_subscriptions', true);
    }

    private function createMobileNotifications(): void
    {
        if ($this->db->tableExists('mobile_notifications')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'usuario_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
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
            'corpo' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'rota_destino' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'payload_json' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'lida_em' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'enviada_push_em' => [
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
        $this->forge->addKey('usuario_id', false, false, 'idx_mobile_notif_usuario');
        $this->forge->addKey('tipo_evento', false, false, 'idx_mobile_notif_tipo');
        $this->forge->addKey('lida_em', false, false, 'idx_mobile_notif_lida');
        $this->forge->addKey('created_at', false, false, 'idx_mobile_notif_created');
        $this->forge->createTable('mobile_notifications', true);
    }

    private function createMobileNotificationTargets(): void
    {
        if ($this->db->tableExists('mobile_notification_targets')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'notification_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => false,
            ],
            'tipo_alvo' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'null' => false,
            ],
            'alvo_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('notification_id', false, false, 'idx_mobile_ntarget_notification');
        $this->forge->addKey(['tipo_alvo', 'alvo_id'], false, false, 'idx_mobile_ntarget_alvo');
        $this->forge->createTable('mobile_notification_targets', true);
    }

    private function createMobileEventOutbox(): void
    {
        if ($this->db->tableExists('mobile_event_outbox')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'event_key' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
                'null' => false,
            ],
            'event_type' => [
                'type' => 'VARCHAR',
                'constraint' => 80,
                'null' => false,
            ],
            'aggregate_type' => [
                'type' => 'VARCHAR',
                'constraint' => 60,
                'null' => false,
            ],
            'aggregate_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'payload_json' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'default' => 'pending',
            ],
            'tentativas' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ],
            'disponivel_em' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'processado_em' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'ultimo_erro' => [
                'type' => 'TEXT',
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
        $this->forge->addUniqueKey('event_key', 'ux_mobile_outbox_event_key');
        $this->forge->addKey('event_type', false, false, 'idx_mobile_outbox_type');
        $this->forge->addKey('status', false, false, 'idx_mobile_outbox_status');
        $this->forge->addKey('disponivel_em', false, false, 'idx_mobile_outbox_disponivel');
        $this->forge->addKey('created_at', false, false, 'idx_mobile_outbox_created');
        $this->forge->createTable('mobile_event_outbox', true);
    }
}
