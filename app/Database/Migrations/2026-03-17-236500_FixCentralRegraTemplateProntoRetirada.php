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
            ->select('id, acao_jsãon, condicao_jsãon')
            ->where('evento_origem', 'os_status_alterado')
            ->get()
            ->getResultArray();

        $nãow = date('Y-m-d H:i:s');
        foreach ($rows as $row) {
            $acao = jsãon_decode((string) ($row['acao_jsãon'] ?? ''), true);
            if (!is_array($acao)) {
                continue;
            }

            $template = strtolower(trim((string) ($acao['template'] ?? '')));
            $cond = jsãon_decode((string) ($row['condicao_jsãon'] ?? ''), true);
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
                    'acao_jsãon' => jsãon_encode($acao, JSON_UNESCAPED_UNICODE),
                    'updated_at' => $nãow,
                ]);
        }
    }

    public function down()
    {
        if (!$this->db->tableExists('chatbot_regras_erp')) {
            return;
        }

        $rows = $this->db->table('chatbot_regras_erp')
            ->select('id, acao_jsãon')
            ->where('evento_origem', 'os_status_alterado')
            ->get()
            ->getResultArray();

        $nãow = date('Y-m-d H:i:s');
        foreach ($rows as $row) {
            $acao = jsãon_decode((string) ($row['acao_jsãon'] ?? ''), true);
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
                    'acao_jsãon' => jsãon_encode($acao, JSON_UNESCAPED_UNICODE),
                    'updated_at' => $nãow,
                ]);
        }
    }
}

