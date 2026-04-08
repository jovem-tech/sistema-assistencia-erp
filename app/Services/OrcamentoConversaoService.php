<?php

namespace App\Services;

use App\Models\OsItemModel;
use App\Models\OsModel;
use App\Models\OsStatusHistoricoModel;

class OrcamentoConversaoService
{
    private OsModel $osModel;
    private OsItemModel $osItemModel;
    private OsStatusHistoricoModel $osHistoricoModel;
    private OsStatusFlowService $statusFlowService;
    private CentralMensagensService $centralMensagensService;
    private CrmService $crmService;

    public function __construct()
    {
        $this->osModel = new OsModel();
        $this->osItemModel = new OsItemModel();
        $this->osHistoricoModel = new OsStatusHistoricoModel();
        $this->statusFlowService = new OsStatusFlowService();
        $this->centralMensagensService = new CentralMensagensService();
        $this->crmService = new CrmService();
    }

    /**
     * @param array<string,mixed> $orcamento
     * @param array<int,array<string,mixed>> $itens
     * @return array<string,mixed>
     */
    public function convertToOs(array $orcamento, array $itens = [], ?int $usuarioId = null): array
    {
        $orcamentoId = (int) ($orcamento['id'] ?? 0);
        if ($orcamentoId <= 0) {
            return [
                'ok' => false,
                'message' => 'Orcamento invalido para conversao.',
            ];
        }

        $osIdExistente = (int) ($orcamento['os_id'] ?? 0);
        if ($osIdExistente > 0) {
            return [
                'ok' => true,
                'os_id' => $osIdExistente,
                'created' => false,
                'message' => 'OS vinculada reutilizada na conversao.',
            ];
        }

        $clienteId = (int) ($orcamento['cliente_id'] ?? 0);
        $equipamentoId = (int) ($orcamento['equipamento_id'] ?? 0);
        if ($clienteId <= 0 || $equipamentoId <= 0) {
            return [
                'ok' => false,
                'message' => 'Nao foi possivel abrir OS automaticamente. Vincule cliente e equipamento no orcamento.',
            ];
        }

        $numeroOrcamento = trim((string) ($orcamento['numero'] ?? ('#' . $orcamentoId)));
        $statusInicial = 'triagem';
        $estadoFluxo = $this->statusFlowService->resolveEstadoFluxo($statusInicial);
        $numeroOs = $this->osModel->generateNumeroOs();
        $total = round((float) ($orcamento['total'] ?? 0), 2);
        $agora = date('Y-m-d H:i:s');

        $payloadOs = [
            'numero_os' => $numeroOs,
            'cliente_id' => $clienteId,
            'equipamento_id' => $equipamentoId,
            'status' => $statusInicial,
            'estado_fluxo' => $estadoFluxo,
            'status_atualizado_em' => $agora,
            'legacy_origem' => 'orcamentos',
            'legacy_id' => $orcamentoId,
            'relato_cliente' => $this->buildRelatoCliente($orcamento, $itens),
            'diagnostico_tecnico' => trim((string) ($orcamento['observacoes'] ?? '')) ?: null,
            'observacoes_cliente' => 'OS aberta a partir do orcamento ' . $numeroOrcamento . '.',
            'data_abertura' => $agora,
            'valor_total' => $total,
            'valor_final' => $total,
            'orcamento_aprovado' => 1,
            'data_aprovacao' => trim((string) ($orcamento['aprovado_em'] ?? '')) !== ''
                ? (string) $orcamento['aprovado_em']
                : $agora,
        ];

        $this->osModel->db->transStart();
        $this->osModel->insert($payloadOs);
        $novoOsId = (int) $this->osModel->getInsertID();
        if ($novoOsId <= 0) {
            $this->osModel->db->transRollback();
            return [
                'ok' => false,
                'message' => 'Falha ao criar OS a partir do orcamento.',
            ];
        }

        $this->insertStatusHistorico($novoOsId, $statusInicial, $estadoFluxo, $usuarioId);
        $this->migrateItensToOs($novoOsId, $itens);
        $this->osModel->db->transComplete();

        if (!$this->osModel->db->transStatus()) {
            return [
                'ok' => false,
                'message' => 'Falha ao concluir conversao do orcamento para OS.',
            ];
        }

        $conversaId = (int) ($orcamento['conversa_id'] ?? 0);
        if ($conversaId > 0) {
            $this->centralMensagensService->bindOsToConversa($conversaId, $novoOsId, true);
        }

        $this->crmService->registerOsEvent(
            $novoOsId,
            'os_aberta_via_orcamento',
            'OS aberta via conversao de orcamento',
            'OS criada a partir do orcamento ' . $numeroOrcamento . '.',
            $usuarioId,
            [
                'orcamento_id' => $orcamentoId,
                'orcamento_numero' => $numeroOrcamento,
            ]
        );

        return [
            'ok' => true,
            'os_id' => $novoOsId,
            'numero_os' => $numeroOs,
            'created' => true,
            'message' => 'OS criada com sucesso a partir do orcamento.',
        ];
    }

    /**
     * @param array<int,array<string,mixed>> $itens
     */
    private function migrateItensToOs(int $osId, array $itens): void
    {
        if (!$this->osItemModel->db->tableExists('os_itens') || empty($itens)) {
            return;
        }

        foreach ($itens as $item) {
            $descricao = trim((string) ($item['descricao'] ?? ''));
            if ($descricao === '') {
                continue;
            }

            $tipo = trim((string) ($item['tipo_item'] ?? 'servico'));
            if (!in_array($tipo, ['servico', 'peca'], true)) {
                $tipo = 'servico';
            }

            $quantidade = max(0.01, (float) ($item['quantidade'] ?? 1));
            $valorUnitario = max(0, (float) ($item['valor_unitario'] ?? 0));
            $valorTotal = max(0, (float) ($item['total'] ?? ($quantidade * $valorUnitario)));
            $this->osItemModel->insert([
                'os_id' => $osId,
                'legacy_origem' => 'orcamento_itens',
                'legacy_tabela' => 'orcamento_itens',
                'legacy_id' => (int) ($item['id'] ?? 0) ?: null,
                'tipo' => $tipo,
                'descricao' => $descricao,
                'observacao' => trim((string) ($item['observacoes'] ?? '')) ?: null,
                'quantidade' => $quantidade,
                'valor_unitario' => round($valorUnitario, 2),
                'valor_total' => round($valorTotal, 2),
            ]);
        }
    }

    private function insertStatusHistorico(int $osId, string $status, string $estadoFluxo, ?int $usuarioId = null): void
    {
        if (!$this->osHistoricoModel->db->tableExists('os_status_historico')) {
            return;
        }

        $this->osHistoricoModel->insert([
            'os_id' => $osId,
            'status_anterior' => null,
            'status_novo' => $status,
            'estado_fluxo' => $estadoFluxo,
            'usuario_id' => $usuarioId > 0 ? $usuarioId : null,
            'observacao' => 'OS aberta via conversao de orcamento',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @param array<string,mixed> $orcamento
     * @param array<int,array<string,mixed>> $itens
     */
    private function buildRelatoCliente(array $orcamento, array $itens): string
    {
        $titulo = trim((string) ($orcamento['titulo'] ?? ''));
        if ($titulo !== '' && $this->stringLength($titulo) >= 5) {
            return $titulo;
        }

        $observacoes = trim((string) ($orcamento['observacoes'] ?? ''));
        if ($observacoes !== '' && $this->stringLength($observacoes) >= 5) {
            return $observacoes;
        }

        foreach ($itens as $item) {
            $descricao = trim((string) ($item['descricao'] ?? ''));
            if ($descricao !== '' && $this->stringLength($descricao) >= 5) {
                return 'Executar item aprovado: ' . $descricao;
            }
        }

        return 'Orcamento aprovado pelo cliente e convertido para execucao.';
    }

    private function stringLength(string $value): int
    {
        if (function_exists('mb_strlen')) {
            return (int) mb_strlen($value);
        }
        return strlen($value);
    }
}
