<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemoveWhaticketLegacyModule extends Migration
{
    public function up()
    {
        $this->cleanupLegacyConfigRows();
        $this->nãormalizeLegacyProviders();
    }

    public function down()
    {
        // Migracao intencionalmente irreversivel:
        // remove configuracoes legadas e nãormaliza providers antigos para api_whats_local.
    }

    private function cleanupLegacyConfigRows(): void
    {
        if (!$this->db->tableExists('configuracoes')) {
            return;
        }

        $legacyKeys = [
            'whatsapp_whaticket_url',
            'whatsapp_whaticket_token',
            'whatsapp_whaticket_origin',
            'whatsapp_whaticket_timeout',
            'whatsapp_whaticket_api_url',
            'whatsapp_whaticket_local_path',
            'whatsapp_whaticket_local_start_cmd',
            'whatsapp_whaticket_backend_local_path',
            'whatsapp_whaticket_backend_local_start_cmd',
            'whatsapp_whaticket_ssão_secret',
            'whatsapp_whaticket_ssão_path',
            'whatsapp_whaticket_iframe_path',
            'whaticket_url',
            'whaticket_token',
            'whaticket_origin',
            'whaticket_timeout',
            'whaticket_api_url',
            'whaticket_ssão_secret',
            'whaticket_ssão_path',
        ];

        $this->db->table('configuracoes')
            ->whereIn('chave', $legacyKeys)
            ->delete();

        $this->db->table('configuracoes')
            ->where('chave', 'whatsapp_direct_provider')
            ->where('valor', 'whaticket')
            ->update(['valor' => 'api_whats_local']);

        $this->db->table('configuracoes')
            ->where('chave', 'whatsapp_provider')
            ->where('valor', 'whaticket')
            ->update(['valor' => 'api_whats_local']);
    }

    private function nãormalizeLegacyProviders(): void
    {
        $map = [
            'conversas_whatsapp' => 'origem_provider',
            'mensagens_whatsapp' => 'provider',
            'crm_mensagens' => 'provider',
            'whatsapp_inbound' => 'provedor',
            'whatsapp_envios' => 'provedor',
            'whatsapp_mensagens' => 'provedor',
        ];

        foreach ($map as $table => $column) {
            if (!$this->db->tableExists($table) || !$this->db->fieldExists($column, $table)) {
                continue;
            }

            $this->db->table($table)
                ->where($column, 'whaticket')
                ->update([$column => 'api_whats_local']);
        }
    }
}

