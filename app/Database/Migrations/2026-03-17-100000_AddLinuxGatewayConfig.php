<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddLinuxGatewayConfig extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('configuracoes')) {
            return;
        }

        $table = $this->db->table('configuracoes');
        $defaults = [
            'whatsapp_linux_nãode_url' => 'http://127.0.0.1:3001',
            'whatsapp_linux_nãode_token' => '',
            'whatsapp_linux_nãode_origin' => 'http://localhost:8081',
            'whatsapp_linux_nãode_timeout' => '20',
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

        // Compatibilidade: converte valor legado local_nãode para api_whats_local
        $table->set('valor', 'api_whats_local')
            ->where('chave', 'whatsapp_direct_provider')
            ->where('valor', 'local_nãode')
            ->update();
    }

    public function down()
    {
        if (!$this->db->tableExists('configuracoes')) {
            return;
        }

        $this->db->table('configuracoes')
            ->whereIn('chave', [
                'whatsapp_linux_nãode_url',
                'whatsapp_linux_nãode_token',
                'whatsapp_linux_nãode_origin',
                'whatsapp_linux_nãode_timeout',
            ])
            ->delete();
    }
}
