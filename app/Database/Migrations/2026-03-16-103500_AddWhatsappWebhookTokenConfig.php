<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddWhatsappWebhookTokenConfig extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('configuracoes')) {
            return;
        }

        $table = $this->db->table('configuracoes');
        $exists = $table->where('chave', 'whatsapp_webhook_token')->countAllResults();
        if ($exists > 0) {
            return;
        }

        $table->insert([
            'chave' => 'whatsapp_webhook_token',
            'valor' => '',
            'tipo' => 'texto',
        ]);
    }

    public function down()
    {
        if (! $this->db->tableExists('configuracoes')) {
            return;
        }

        $this->db->table('configuracoes')
            ->where('chave', 'whatsapp_webhook_token')
            ->delete();
    }
}
