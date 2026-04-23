<?php

namespace App\Services;

use Config\Database;

class MetricasMensageriaService
{
    /**
     * @return array<string, mixed>
     */
    public function gerarResumo(string $inicio, string $fim): array
    {
        $db = Database::connect();
        $hasMensagens = $db->tableExists('mensagens_whatsapp');
        $hasConversas = $db->tableExists('conversas_whatsapp');
        $hasChatbotLogs = $db->tableExists('chatbot_logs');

        if (!$hasMensagens || !$hasConversas) {
            return $this->emptyResumo($inicio, $fim);
        }

        $inicioDb = $inicio . ' 00:00:00';
        $fimDb = $fim . ' 23:59:59';

        $mensagensRecebidas = $this->countMensagensByDirecao($db, 'inbound', $inicioDb, $fimDb);
        $mensagensEnviadas = $this->countMensagensByDirecao($db, 'outbound', $inicioDb, $fimDb);
        $mensagensAutomaticas = $this->countMensagensAutomaticas($db, $inicioDb, $fimDb);
        $mensagensHumanas = max(0, $mensagensEnviadas - $mensagensAutomaticas);

        $conversasAbertas = (int) $db->table('conversas_whatsapp')
            ->whereIn('status', ['aberta', 'aguardando'])
            ->countAllResults();
        $conversasFinalizadas = (int) $db->table('conversas_whatsapp')
            ->whereIn('status', ['resolvida', 'arquivada'])
            ->countAllResults();
        $conversasAguardandoHumano = (int) $db->table('conversas_whatsapp')
            ->where('aguardando_humano', 1)
            ->countAllResults();

        $tempos = $this->calcularTemposMedios($db, $inicioDb, $fimDb);
        $slaEstourado = $this->countSlaEstourado($db, 60);
        $semResposta = $this->countInboundSemResposta($db, $inicioDb, $fimDb);

        $taxaAutomacao = 0.0;
        $taxaEscalonamento = 0.0;
        $topIntencoes = [];
        if ($hasChatbotLogs) {
            $chatRows = $db->table('chatbot_logs')
                ->select('
                    COUNT(*) as total_logs,
                    SUM(CASE WHEN tipo_resposta IN ("automatica", "faq", "escalada", "fallback_humano") THEN 1 ELSE 0 END) as total_automacao,
                    SUM(CASE WHEN escalado_humano = 1 THEN 1 ELSE 0 END) as total_escalado
                ')
                ->where('created_at >=', $inicioDb)
                ->where('created_at <=', $fimDb)
                ->get()
                ->getRowArray() ?? [];

            $totalLogs = (int) ($chatRows['total_logs'] ?? 0);
            $totalAutomacao = (int) ($chatRows['total_automacao'] ?? 0);
            $totalEscalado = (int) ($chatRows['total_escalado'] ?? 0);
            if ($totalLogs > 0) {
                $taxaAutomacao = round(($totalAutomacao / $totalLogs) * 100, 2);
                $taxaEscalonamento = round(($totalEscalado / $totalLogs) * 100, 2);
            }

            $topIntencoes = $db->table('chatbot_logs')
                ->select('intencao_detectada, COUNT(*) as total')
                ->where('created_at >=', $inicioDb)
                ->where('created_at <=', $fimDb)
                ->where('intencao_detectada IS NOT NULL', null, false)
                ->where('intencao_detectada !=', '')
                ->groupBy('intencao_detectada')
                ->orderBy('total', 'DESC')
                ->limit(10)
                ->get()
                ->getResultArray();
        }

        $porAtendente = $db->table('mensagens_whatsapp mw')
            ->select('mw.enviada_por_usuario_id, usuarios.nome as usuario_nome, COUNT(*) as total')
            ->join('usuarios', 'usuarios.id = mw.enviada_por_usuario_id', 'left')
            ->where('mw.direcao', 'outbound')
            ->where('mw.created_at >=', $inicioDb)
            ->where('mw.created_at <=', $fimDb)
            ->where('mw.enviada_por_bot', 0)
            ->groupBy('mw.enviada_por_usuario_id, usuarios.nome')
            ->orderBy('total', 'DESC')
            ->get()
            ->getResultArray();

        $porDia = $db->table('mensagens_whatsapp')
            ->select('DATE(created_at) as dia,
                SUM(CASE WHEN direcao = "inbound" THEN 1 ELSE 0 END) as recebidas,
                SUM(CASE WHEN direcao = "outbound" THEN 1 ELSE 0 END) as enviadas,
                SUM(CASE WHEN direcao = "outbound" AND enviada_por_bot = 1 THEN 1 ELSE 0 END) as automaticas')
            ->where('created_at >=', $inicioDb)
            ->where('created_at <=', $fimDb)
            ->groupBy('DATE(created_at)')
            ->orderBy('dia', 'ASC')
            ->get()
            ->getResultArray();

        return [
            'periodo' => ['inicio' => $inicio, 'fim' => $fim],
            'cards' => [
                'mensagens_recebidas' => $mensagensRecebidas,
                'mensagens_enviadas' => $mensagensEnviadas,
                'mensagens_automaticas' => $mensagensAutomaticas,
                'mensagens_humanas' => $mensagensHumanas,
                'conversas_abertas' => $conversasAbertas,
                'conversas_finalizadas' => $conversasFinalizadas,
                'conversas_aguardando_humano' => $conversasAguardandoHumano,
                'tempo_medio_primeira_resposta' => $tempos['primeira_resposta_min'],
                'tempo_medio_resposta_total' => $tempos['resposta_total_min'],
                'sla_estourado' => $slaEstourado,
                'mensagens_sem_resposta' => $semResposta,
                'taxa_automacao' => $taxaAutomacao,
                'taxa_escalonamento_humano' => $taxaEscalonamento,
            ],
            'por_atendente' => $porAtendente,
            'por_dia' => $porDia,
            'top_intencoes' => $topIntencoes,
        ];
    }

    public function atualizarAgregadoDiario(string $dataReferencia): void
    {
        $db = Database::connect();
        if (!$db->tableExists('mensageria_metricas_diarias')) {
            return;
        }

        $resumo = $this->gerarResumo($dataReferencia, $dataReferencia);
        $cards = $resumo['cards'] ?? [];

        $payload = [
            'data_referencia' => $dataReferencia,
            'mensagens_recebidas' => (int) ($cards['mensagens_recebidas'] ?? 0),
            'mensagens_enviadas' => (int) ($cards['mensagens_enviadas'] ?? 0),
            'mensagens_automaticas' => (int) ($cards['mensagens_automaticas'] ?? 0),
            'mensagens_humanas' => (int) ($cards['mensagens_humanas'] ?? 0),
            'conversas_abertas' => (int) ($cards['conversas_abertas'] ?? 0),
            'conversas_finalizadas' => (int) ($cards['conversas_finalizadas'] ?? 0),
            'tempo_medio_primeira_resposta' => (float) ($cards['tempo_medio_primeira_resposta'] ?? 0),
            'tempo_medio_resposta_total' => (float) ($cards['tempo_medio_resposta_total'] ?? 0),
            'taxa_resolucao_automatica' => (float) ($cards['taxa_automacao'] ?? 0),
            'taxa_escalonamento_humano' => (float) ($cards['taxa_escalonamento_humano'] ?? 0),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $exists = $db->table('mensageria_metricas_diarias')
            ->where('data_referencia', $dataReferencia)
            ->get()
            ->getRowArray();

        if ($exists) {
            $db->table('mensageria_metricas_diarias')->where('id', (int) $exists['id'])->update($payload);
            return;
        }

        $payload['created_at'] = date('Y-m-d H:i:s');
        $db->table('mensageria_metricas_diarias')->insert($payload);
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyResumo(string $inicio, string $fim): array
    {
        return [
            'periodo' => ['inicio' => $inicio, 'fim' => $fim],
            'cards' => [
                'mensagens_recebidas' => 0,
                'mensagens_enviadas' => 0,
                'mensagens_automaticas' => 0,
                'mensagens_humanas' => 0,
                'conversas_abertas' => 0,
                'conversas_finalizadas' => 0,
                'conversas_aguardando_humano' => 0,
                'tempo_medio_primeira_resposta' => 0,
                'tempo_medio_resposta_total' => 0,
                'sla_estourado' => 0,
                'mensagens_sem_resposta' => 0,
                'taxa_automacao' => 0,
                'taxa_escalonamento_humano' => 0,
            ],
            'por_atendente' => [],
            'por_dia' => [],
            'top_intencoes' => [],
        ];
    }

    private function countMensagensByDirecao($db, string $direcao, string $inicioDb, string $fimDb): int
    {
        return (int) $db->table('mensagens_whatsapp')
            ->where('direcao', $direcao)
            ->where('created_at >=', $inicioDb)
            ->where('created_at <=', $fimDb)
            ->countAllResults();
    }

    private function countMensagensAutomaticas($db, string $inicioDb, string $fimDb): int
    {
        return (int) $db->table('mensagens_whatsapp')
            ->where('direcao', 'outbound')
            ->where('enviada_por_bot', 1)
            ->where('created_at >=', $inicioDb)
            ->where('created_at <=', $fimDb)
            ->countAllResults();
    }

    /**
     * @return array{primeira_resposta_min: float, resposta_total_min: float}
     */
    private function calcularTemposMedios($db, string $inicioDb, string $fimDb): array
    {
        $sql = '
            SELECT
                AVG(TIMESTAMPDIFF(MINUTE, inbound.created_at, outbound_first.created_at)) AS media_primeira_resposta
            FROM mensagens_whatsapp inbound
            LEFT JOIN mensagens_whatsapp outbound_first
                ON outbound_first.id = (
                    SELECT mw2.id
                    FROM mensagens_whatsapp mw2
                    WHERE mw2.conversa_id = inbound.conversa_id
                      AND mw2.direcao = "outbound"
                      AND mw2.created_at > inbound.created_at
                    ORDER BY mw2.created_at ASC
                    LIMIT 1
                )
            WHERE inbound.direcao = "inbound"
              AND inbound.created_at BETWEEN ? AND ?
        ';
        $rowPrimeira = $db->query($sql, [$inicioDb, $fimDb])->getRowArray() ?? [];
        $primeira = (float) ($rowPrimeira['media_primeira_resposta'] ?? 0);

        $sqlTotal = '
            SELECT
                AVG(TIMESTAMPDIFF(MINUTE, inbound.created_at, outbound.created_at)) AS media_resposta_total
            FROM mensagens_whatsapp inbound
            JOIN mensagens_whatsapp outbound
              ON outbound.id = (
                  SELECT mw3.id
                  FROM mensagens_whatsapp mw3
                  WHERE mw3.conversa_id = inbound.conversa_id
                    AND mw3.direcao = "outbound"
                    AND mw3.created_at > inbound.created_at
                  ORDER BY mw3.created_at ASC
                  LIMIT 1
              )
            WHERE inbound.direcao = "inbound"
              AND inbound.created_at BETWEEN ? AND ?
        ';
        $rowTotal = $db->query($sqlTotal, [$inicioDb, $fimDb])->getRowArray() ?? [];
        $total = (float) ($rowTotal['media_resposta_total'] ?? 0);

        return [
            'primeira_resposta_min' => round($primeira, 2),
            'resposta_total_min' => round($total, 2),
        ];
    }

    private function countSlaEstourado($db, int $minutes): int
    {
        return (int) $db->table('conversas_whatsapp')
            ->whereIn('status', ['aberta', 'aguardando'])
            ->where('TIMESTAMPDIFF(MINUTE, IFNULL(ultima_mensagem_em, created_at), NOW()) >', $minutes, false)
            ->countAllResults();
    }

    private function countInboundSemResposta($db, string $inicioDb, string $fimDb): int
    {
        $sql = '
            SELECT COUNT(*) AS total
            FROM mensagens_whatsapp inbound
            WHERE inbound.direcao = "inbound"
              AND inbound.created_at BETWEEN ? AND ?
              AND NOT EXISTS (
                  SELECT 1
                  FROM mensagens_whatsapp outbound
                  WHERE outbound.conversa_id = inbound.conversa_id
                    AND outbound.direcao = "outbound"
                    AND outbound.created_at > inbound.created_at
              )
        ';
        $row = $db->query($sql, [$inicioDb, $fimDb])->getRowArray() ?? [];
        return (int) ($row['total'] ?? 0);
    }
}
