<?php

namespace App\Services;

use App\Models\OsItemModel;
use App\Models\OsModel;
use App\Models\OsStatusHistoricoModel;
use App\Models\PecaModel;

class OrcamentoConversaoService
{
    private OsModel $osModel;
    private OsItemModel $osItemModel;
    private OsStatusHistoricoModel $osHistoricoModel;
    private OsStatusFlowService $statusFlowService;
    private CentralMensagensService $centralMensagensService;
    private CrmService $crmService;
    private PecaModel $pecaModel;
    private PecaPrecificacaoService $pecaPrecificacaoService;

    public function __construct()
    {
        $this->osModel = new OsModel();
        $this->osItemModel = new OsItemModel();
        $this->osHistoricoModel = new OsStatusHistoricoModel();
        $this->statusFlowService = new OsStatusFlowService();
        $this->centralMensagensService = new CentralMensagensService();
        $this->crmService = new CrmService();
        $this->pecaModel = new PecaModel();
        $this->pecaPrecificacaoService = new PecaPrecificacaoService();
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
            $osRow = $this->osModel->find($osIdExistente);
            if (!$osRow) {
                return [
                    'ok' => false,
                    'message' => 'OS vinculada nao encontrada para atualizacao.',
                ];
            }
            $this->osModel->db->transStart();
            if ($this->osItemModel->db->tableExists('os_itens')) {
                $this->osItemModel->where('os_id', $osIdExistente)->delete();
            }
            $totais = $this->migrateItensToOs($osIdExistente, $itens);
            $this->applyTotaisToOs($osIdExistente, $totais, $orcamento);
            $this->osModel->db->transComplete();
            if (!$this->osModel->db->transStatus()) {
                return [
                    'ok' => false,
                    'message' => 'Falha ao atualizar itens da OS vinculada.',
                ];
            }
            return [
                'ok' => true,
                'os_id' => $osIdExistente,
                'created' => false,
                'message' => 'OS vinculada atualizada com itens do orcamento.',
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
        $totais = $this->migrateItensToOs($novoOsId, $itens);
        $this->applyTotaisToOs($novoOsId, $totais, $orcamento);
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
    private function migrateItensToOs(int $osId, array $itens): array
    {
        $totais = [
            'servicos' => 0.0,
            'pecas' => 0.0,
            'total' => 0.0,
        ];
        if (!$this->osItemModel->db->tableExists('os_itens') || empty($itens)) {
            return $totais;
        }

        $hasPecaId = $this->osItemModel->db->fieldExists('peca_id', 'os_itens');
        $hasServicoId = $this->osItemModel->db->fieldExists('servico_id', 'os_itens');
        $precificacaoFieldMap = $this->osPrecificacaoFieldMap();
        $pecaCache = [];

        foreach ($itens as $item) {
            $descricao = trim((string) ($item['descricao'] ?? ''));
            if ($descricao === '') {
                continue;
            }

            $tipoItem = strtolower(trim((string) ($item['tipo_item'] ?? 'servico')));
            $tipo = 'servico';
            if ($tipoItem === 'peca') {
                $tipo = 'peca';
            }

            $quantidade = max(0.01, (float) ($item['quantidade'] ?? 1));
            $valorUnitario = max(0, (float) ($item['valor_unitario'] ?? 0));
            $valorTotal = max(0, (float) ($item['total'] ?? ($quantidade * $valorUnitario)));
            $payload = [
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
            ];
            $referenciaId = (int) ($item['referencia_id'] ?? 0);
            if ($hasPecaId && $tipo === 'peca') {
                $payload['peca_id'] = $referenciaId > 0 ? $referenciaId : null;
                $quote = $this->extractPrecificacaoFromItem($item);
                if (empty($quote) && $referenciaId > 0) {
                    if (!array_key_exists($referenciaId, $pecaCache)) {
                        $pecaCache[$referenciaId] = $this->pecaModel->find($referenciaId) ?? [];
                    }
                    $peca = (array) $pecaCache[$referenciaId];
                    if (!empty($peca)) {
                        $quote = $this->pecaPrecificacaoService->applyMinimumPrice($peca, $valorUnitario);
                    }
                }
                if (!empty($quote)) {
                    $payload = array_merge($payload, $this->buildPrecificacaoPayload($quote, $precificacaoFieldMap));
                }
            }
            if ($hasServicoId && $tipo === 'servico') {
                $payload['servico_id'] = $referenciaId > 0 ? $referenciaId : null;
            }
            $this->osItemModel->insert($payload);
            if ($tipo === 'peca') {
                $totais['pecas'] += $valorTotal;
            } else {
                $totais['servicos'] += $valorTotal;
            }
            $totais['total'] += $valorTotal;
        }
        $totais['servicos'] = round((float) $totais['servicos'], 2);
        $totais['pecas'] = round((float) $totais['pecas'], 2);
        $totais['total'] = round((float) $totais['total'], 2);
        return $totais;
    }

    /**
     * @return array<string,bool>
     */
    private function osPrecificacaoFieldMap(): array
    {
        $fields = [
            'preco_custo_referencia',
            'preco_venda_referencia',
            'preco_base',
            'percentual_encargos',
            'valor_encargos',
            'percentual_margem',
            'valor_margem',
            'valor_recomendado',
            'modo_precificacao',
        ];
        $map = [];
        foreach ($fields as $field) {
            $map[$field] = $this->osItemModel->db->fieldExists($field, 'os_itens');
        }
        return $map;
    }

    /**
     * @param array<string,mixed> $item
     * @return array<string,mixed>
     */
    private function extractPrecificacaoFromItem(array $item): array
    {
        $source = [
            'preco_custo_referencia' => (float) ($item['preco_custo_referencia'] ?? 0),
            'preco_venda_referencia' => (float) ($item['preco_venda_referencia'] ?? 0),
            'preco_base' => (float) ($item['preco_base'] ?? 0),
            'percentual_encargos' => (float) ($item['percentual_encargos'] ?? 0),
            'valor_encargos' => (float) ($item['valor_encargos'] ?? 0),
            'percentual_margem' => (float) ($item['percentual_margem'] ?? 0),
            'valor_margem' => (float) ($item['valor_margem'] ?? 0),
            'valor_recomendado' => (float) ($item['valor_recomendado'] ?? 0),
            'modo_precificacao' => trim((string) ($item['modo_precificacao'] ?? '')),
            'valor_aplicado' => (float) ($item['valor_unitario'] ?? 0),
        ];

        $hasAny = false;
        foreach ([
            'preco_base',
            'percentual_encargos',
            'valor_encargos',
            'percentual_margem',
            'valor_margem',
            'valor_recomendado',
        ] as $field) {
            if ((float) ($source[$field] ?? 0) > 0) {
                $hasAny = true;
                break;
            }
        }
        if (!$hasAny) {
            return [];
        }

        if ($source['modo_precificacao'] === '') {
            $source['modo_precificacao'] = 'peca_instalada_auto';
        }
        return $source;
    }

    /**
     * @param array<string,mixed> $quote
     * @param array<string,bool> $fieldMap
     * @return array<string,mixed>
     */
    private function buildPrecificacaoPayload(array $quote, array $fieldMap): array
    {
        $payload = [];
        $source = [
            'preco_custo_referencia' => round((float) ($quote['preco_custo_referencia'] ?? 0), 2),
            'preco_venda_referencia' => round((float) ($quote['preco_venda_referencia'] ?? 0), 2),
            'preco_base' => round((float) ($quote['preco_base'] ?? 0), 2),
            'percentual_encargos' => round((float) ($quote['percentual_encargos'] ?? 0), 2),
            'valor_encargos' => round((float) ($quote['valor_encargos'] ?? 0), 2),
            'percentual_margem' => round((float) ($quote['percentual_margem'] ?? 0), 2),
            'valor_margem' => round((float) ($quote['valor_margem'] ?? 0), 2),
            'valor_recomendado' => round((float) ($quote['valor_recomendado'] ?? 0), 2),
            'modo_precificacao' => trim((string) ($quote['modo_precificacao'] ?? 'peca_instalada_auto')),
        ];
        foreach ($source as $field => $value) {
            if (($fieldMap[$field] ?? false) !== true) {
                continue;
            }
            $payload[$field] = $value;
        }
        return $payload;
    }

    /**
     * @param array<string,float> $totais
     * @param array<string,mixed> $orcamento
     */
    private function applyTotaisToOs(int $osId, array $totais, array $orcamento): void
    {
        $desconto = max(0, (float) ($orcamento['desconto'] ?? 0));
        $acrescimo = max(0, (float) ($orcamento['acrescimo'] ?? 0));
        $totalItens = (float) ($totais['total'] ?? 0);
        $valorFinal = max(0, round($totalItens - $desconto + $acrescimo, 2));
        $aprovadoEm = trim((string) ($orcamento['aprovado_em'] ?? ''));
        if ($aprovadoEm === '') {
            $aprovadoEm = date('Y-m-d H:i:s');
        }
        $this->osModel->update($osId, [
            'valor_mao_obra' => (float) ($totais['servicos'] ?? 0),
            'valor_pecas' => (float) ($totais['pecas'] ?? 0),
            'valor_total' => $totalItens,
            'desconto' => $desconto,
            'valor_final' => $valorFinal,
            'orcamento_aprovado' => 1,
            'data_aprovacao' => $aprovadoEm,
        ]);
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
