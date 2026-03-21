<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class FixCentralRegraTemplateProntoRetirada extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('chatbot_regras_erp')) {
            return;
        }

        $rows = $this->db->table('chatbot_regras_erp')
            ->select('id, acao_json, condicao_json')
            ->where('evento_origem', 'os_status_alterado')
            ->get()
            ->getResultArray();

        $now = date('Y-m-d H:i:s');
        foreach ($rows as $row) {
            $acao = json_decode((string) ($row['acao_json'] ?? ''), true);
            if (!is_array($acao)) {
                continue;
            }

            $template = strtolower(trim((string) ($acao['template'] ?? '')));
            $cond = json_decode((string) ($row['condicao_json'] ?? ''), true);
            $statusCond = strtolower(trim((string) ($cond['status'] ?? '')));
            if ($template !== 'equipamento_pronto' && $statusCond !== 'reparado_disponivel_loja') {
                continue;
            }

            if ($template === 'equipamento_pronto') {
                $acao['template'] = 'pronto_retirada';
            }
            if ($statusCond === 'reparado_disponivel_loja' && empty($acao['pdf_tipo'])) {
                $acao['pdf_tipo'] = 'laudo';
            }

            $this->db->table('chatbot_regras_erp')
                ->where('id', (int) $row['id'])
                ->update([
                    'acao_json' => json_encode($acao, JSON_UNESCAPED_UNICODE),
                    'updated_at' => $now,
                ]);
        }
    }

    public function down()
    {
        if (!$this->db->tableExists('chatbot_regras_erp')) {
            return;
        }

        $rows = $this->db->table('chatbot_regras_erp')
            ->select('id, acao_json')
            ->where('evento_origem', 'os_status_alterado')
            ->get()
            ->getResultArray();

        $now = date('Y-m-d H:i:s');
        foreach ($rows as $row) {
            $acao = json_decode((string) ($row['acao_json'] ?? ''), true);
            if (!is_array($acao)) {
                continue;
            }

            if (strtolower(trim((string) ($acao['template'] ?? ''))) !== 'pronto_retirada') {
                continue;
            }

            $acao['template'] = 'equipamento_pronto';
            unset($acao['pdf_tipo']);

            $this->db->table('chatbot_regras_erp')
                ->where('id', (int) $row['id'])
                ->update([
                    'acao_json' => json_encode($acao, JSON_UNESCAPED_UNICODE),
                    'updated_at' => $now,
                ]);
        }
    }
}

