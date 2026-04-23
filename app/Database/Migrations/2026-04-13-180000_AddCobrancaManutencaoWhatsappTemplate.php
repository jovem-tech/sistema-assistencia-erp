<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCobrancaManutencaoWhatsappTemplate extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('whatsapp_templates')) {
            return;
        }

        $table = $this->db->table('whatsapp_templates');
        $exists = $table->where('codigo', 'cobranca_manutencao')->countAllResults();
        if ($exists > 0) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $table->insert([
            'codigo' => 'cobranca_manutencao',
            'nome' => 'Cobranca / manutencao',
            'evento' => 'followup_cobranca_manutencao',
            'conteudo' => 'Ola {{cliente}}, concluimos a manutencao da OS {{numero_os}} referente a {{equipamento}}. O valor final ficou em {{valor_final}}. Estou enviando em anexo o PDF com o resumo da manutencao, itens lancados e orientacoes finais. Se tiver duvidas, responda esta mensagem.',
            'ativo' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down()
    {
        if (! $this->db->tableExists('whatsapp_templates')) {
            return;
        }

        $this->db->table('whatsapp_templates')
            ->where('codigo', 'cobranca_manutencao')
            ->delete();
    }
}
