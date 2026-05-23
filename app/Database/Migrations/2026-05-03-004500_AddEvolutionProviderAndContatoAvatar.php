<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddEvolutionProviderAndContatoAvatar extends Migration
{
    public function up()
    {
        $this->seedEvolutionConfigs();
        $this->patchContatosTable();
    }

    public function down()
    {
        if ($this->db->tableExists('configuracoes')) {
            $this->db->table('configuracoes')
                ->whereIn('chave', [
                    'whatsapp_evolution_url',
                    'whatsapp_evolution_apikey',
                    'whatsapp_evolution_instance',
                    'whatsapp_evolution_timeout',
                    'whatsapp_evolution_sync_avatar',
                ])
                ->delete();
        }

        if ($this->db->tableExists('contatos')) {
            foreach ([
                'whatsapp_avatar_synced_em',
                'whatsapp_avatar_url',
                'whatsapp_remote_jid',
            ] as $column) {
                if ($this->db->fieldExists($column, 'contatos')) {
                    $this->db->query('ALTER TABLE contatos DROP COLUMN ' . $column);
                }
            }

            try {
                $this->db->query('DROP INDEX idx_contatos_remote_jid ON contatos');
            } catch (\Throwable $e) {
                // indice pode nao existir
            }
        }
    }

    private function seedEvolutionConfigs(): void
    {
        if (!$this->db->tableExists('configuracoes')) {
            return;
        }

        $table = $this->db->table('configuracoes');
        $defaults = [
            'whatsapp_evolution_url' => 'http://127.0.0.1:8080',
            'whatsapp_evolution_apikey' => '',
            'whatsapp_evolution_instance' => '',
            'whatsapp_evolution_timeout' => '20',
            'whatsapp_evolution_sync_avatar' => '1',
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

    private function patchContatosTable(): void
    {
        if (!$this->db->tableExists('contatos')) {
            return;
        }

        if (!$this->db->fieldExists('whatsapp_remote_jid', 'contatos')) {
            $this->db->query("ALTER TABLE contatos ADD COLUMN whatsapp_remote_jid VARCHAR(191) NULL AFTER whatsapp_nome_perfil");
        }

        if (!$this->db->fieldExists('whatsapp_avatar_url', 'contatos')) {
            $this->db->query("ALTER TABLE contatos ADD COLUMN whatsapp_avatar_url VARCHAR(500) NULL AFTER whatsapp_remote_jid");
        }

        if (!$this->db->fieldExists('whatsapp_avatar_synced_em', 'contatos')) {
            $this->db->query("ALTER TABLE contatos ADD COLUMN whatsapp_avatar_synced_em DATETIME NULL AFTER whatsapp_avatar_url");
        }

        try {
            $this->db->query('CREATE INDEX idx_contatos_remote_jid ON contatos (whatsapp_remote_jid)');
        } catch (\Throwable $e) {
            // indice ja existe
        }
    }
}
