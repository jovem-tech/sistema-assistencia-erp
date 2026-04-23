<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePrecificacaoParametrosDetalhados extends Migration
{
    private string $table = 'precificacao_parametros';

    public function up()
    {
        if (! $this->db->tableExists($this->table)) {
            $this->forge->addField([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'categoria' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'null' => false,
                ],
                'secao' => [
                    'type' => 'VARCHAR',
                    'constraint' => 60,
                    'null' => false,
                ],
                'codigo' => [
                    'type' => 'VARCHAR',
                    'constraint' => 80,
                    'null' => false,
                ],
                'nome' => [
                    'type' => 'VARCHAR',
                    'constraint' => 140,
                    'null' => false,
                ],
                'descricao' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'unidade' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'null' => false,
                    'default' => 'R$',
                ],
                'tipo_dado' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'null' => false,
                    'default' => 'valor',
                    'comment' => 'valor|percentual|horas|minutos|quantidade',
                ],
                'tipo_entrada' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'null' => false,
                    'default' => 'manual',
                    'comment' => 'manual|calculado|automatico',
                ],
                'formula' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                ],
                'valor' => [
                    'type' => 'DECIMAL',
                    'constraint' => '14,4',
                    'null' => false,
                    'default' => 0,
                ],
                'valor_padrao' => [
                    'type' => 'DECIMAL',
                    'constraint' => '14,4',
                    'null' => false,
                    'default' => 0,
                ],
                'minimo' => [
                    'type' => 'DECIMAL',
                    'constraint' => '14,4',
                    'null' => true,
                ],
                'maximo' => [
                    'type' => 'DECIMAL',
                    'constraint' => '14,4',
                    'null' => true,
                ],
                'editavel' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'null' => false,
                    'default' => 1,
                ],
                'origem' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'null' => false,
                    'default' => 'manual',
                ],
                'ativo' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'null' => false,
                    'default' => 1,
                ],
                'ordem' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => false,
                    'default' => 0,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey('codigo', 'ux_precificacao_parametros_codigo');
            $this->forge->addKey(['categoria', 'secao', 'ativo'], false, false, 'idx_precificacao_parametros_categoria_secao_ativo');
            $this->forge->createTable($this->table, true, ['ENGINE' => 'InnoDB']);
        }

        $this->seedDefaults();
    }

    public function down()
    {
        if ($this->db->tableExists($this->table)) {
            $this->forge->dropTable($this->table, true);
        }
    }

    private function seedDefaults(): void
    {
        if (! $this->db->tableExists($this->table)) {
            return;
        }

        $rows = $this->defaultRows();
        $builder = $this->db->table($this->table);
        $now = date('Y-m-d H:i:s');

        foreach ($rows as $row) {
            $codigo = (string) ($row['codigo'] ?? '');
            if ($codigo === '') {
                continue;
            }

            $exists = $builder->select('id')->where('codigo', $codigo)->get()->getRowArray();
            if ($exists) {
                continue;
            }

            $payload = $row;
            $payload['created_at'] = $now;
            $payload['updated_at'] = $now;
            $builder->insert($payload);
        }
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function defaultRows(): array
    {
        return [
            // PECA - custo aquisicao
            $this->row('peca', 'custo_aquisicao', 'peca_preco_compra', 'Preco de compra', 'Valor pago ao fornecedor', 'R$', 'valor', 'manual', 'Direto da NF/pedido', 80, 80, 0, 999999, 1, 10),
            $this->row('peca', 'custo_aquisicao', 'peca_frete_compra_rateado', 'Frete de compra rateado', 'Parcela do frete alocada para esta peca', 'R$', 'valor', 'manual', 'Frete total / qtd de pecas do lote', 0, 0, 0, 999999, 1, 20),
            $this->row('peca', 'custo_aquisicao', 'peca_impostos_nao_recuperaveis', 'Impostos nao recuperaveis', 'Tributos sem credito fiscal', 'R$', 'valor', 'manual', 'Soma de impostos sem credito', 0, 0, 0, 999999, 1, 30),
            $this->row('peca', 'custo_aquisicao', 'peca_seguro_compra_rateado', 'Seguro de compra rateado', 'Parcela de seguro alocada para esta peca', 'R$', 'valor', 'manual', 'Seguro total / qtd de pecas', 0, 0, 0, 999999, 1, 40),
            $this->row('peca', 'custo_aquisicao', 'peca_perdas_entrada_valor', 'Perdas na entrada', 'Perdas de recebimento/manuseio inicial', 'R$', 'valor', 'manual', 'Valor tecnico definido na operacao', 0, 0, 0, 999999, 1, 50),
            $this->row('peca', 'custo_aquisicao', 'peca_descontos_fornecedor', 'Descontos do fornecedor', 'Abatimentos comerciais da compra', 'R$', 'valor', 'manual', 'Valor de desconto aplicado', 0, 0, 0, 999999, 1, 60),
            $this->row('peca', 'custo_aquisicao', 'peca_custo_fornecedor_liquido', 'Custo fornecedor liquido', 'Custo real da peca', 'R$', 'valor', 'calculado', 'PrecoCompra + Frete + Impostos + Seguro + Perdas - Descontos', 80, 80, 0, 999999, 0, 70),

            // PECA - encargos
            $this->row('peca', 'encargos', 'peca_triagem_teste_percentual', 'Triagem e teste', 'Componente de encargo para triagem tecnica', '%', 'percentual', 'manual', 'Percentual definido pela operacao', 4, 4, 0, 100, 1, 110),
            $this->row('peca', 'encargos', 'peca_risco_garantia_percentual', 'Risco de garantia da peca', 'Reserva para retorno de peca', '%', 'percentual', 'manual', 'Percentual definido pela operacao', 5, 5, 0, 100, 1, 120),
            $this->row('peca', 'encargos', 'peca_armazenagem_obsolescencia_percentual', 'Armazenagem e obsolescencia', 'Custo de estocagem/obsolescencia', '%', 'percentual', 'manual', 'Percentual definido pela operacao', 3, 3, 0, 100, 1, 130),
            $this->row('peca', 'encargos', 'peca_processo_compra_conferencia_percentual', 'Processo de compra e conferencia', 'Custo operacional de compra/conferencia', '%', 'percentual', 'manual', 'Percentual definido pela operacao', 3, 3, 0, 100, 1, 140),
            $this->row('peca', 'encargos', 'peca_encargos_total_percentual', 'Encargos totais da peca', 'Soma dos componentes de encargo da peca', '%', 'percentual', 'calculado', 'Triagem + Risco + Armazenagem + Processo', 15, 15, 0, 300, 0, 150),

            // PECA - margem e resultado
            $this->row('peca', 'margem', 'peca_margem_percentual', 'Margem da peca', 'Percentual alvo de margem para peca instalada', '%', 'percentual', 'manual', 'Percentual comercial definido pela empresa', 45, 45, 0, 300, 1, 210),
            $this->row('peca', 'resultado', 'peca_preco_instalada_recomendado', 'Preco recomendado da peca instalada', 'Preco final sugerido para uso em OS/Orcamentos', 'R$', 'valor', 'calculado', 'CustoLiquido + Encargos + Margem', 128, 128, 0, 999999, 0, 260),

            // SERVICO - tempo tecnico
            $this->row('servico', 'tempo_tecnico', 'servico_tempo_desmontagem_min', 'Tempo desmontagem', 'Tempo para abrir equipamento', 'min', 'minutos', 'manual', 'Direto da rotina tecnica', 20, 20, 0, 999, 1, 10),
            $this->row('servico', 'tempo_tecnico', 'servico_tempo_substituicao_min', 'Tempo substituicao', 'Tempo da troca principal', 'min', 'minutos', 'manual', 'Direto da rotina tecnica', 15, 15, 0, 999, 1, 20),
            $this->row('servico', 'tempo_tecnico', 'servico_tempo_montagem_min', 'Tempo montagem', 'Tempo de remontagem', 'min', 'minutos', 'manual', 'Direto da rotina tecnica', 10, 10, 0, 999, 1, 30),
            $this->row('servico', 'tempo_tecnico', 'servico_tempo_teste_final_min', 'Tempo teste final', 'Tempo de validacao final', 'min', 'minutos', 'manual', 'Direto da rotina tecnica', 15, 15, 0, 999, 1, 40),
            $this->row('servico', 'tempo_tecnico', 'servico_tempo_tecnico_horas', 'Tempo tecnico total', 'Tempo tecnico total em horas', 'h', 'horas', 'calculado', '(Desmontagem + Substituicao + Montagem + Teste) / 60', 1, 1, 0, 999, 0, 50),

            // SERVICO - capacidade e custo hora
            $this->row('servico', 'capacidade', 'servico_custos_fixos_mensais', 'Custos fixos mensais', 'Estrutura mensal da empresa', 'R$', 'valor', 'manual', 'Soma de aluguel, energia, internet, software etc', 3000, 3000, 0, 9999999, 1, 110),
            $this->row('servico', 'capacidade', 'servico_tecnicos_ativos', 'Tecnicos ativos', 'Quantidade de tecnicos produtivos', 'qtd', 'quantidade', 'manual', 'Dado operacional', 1, 1, 1, 1000, 1, 120),
            $this->row('servico', 'capacidade', 'servico_horas_produtivas_dia', 'Horas produtivas por dia', 'Horas reais de bancada por tecnico', 'h', 'horas', 'manual', 'Metrica real sem pausas/atendimento', 3.4, 3.4, 0.1, 24, 1, 130),
            $this->row('servico', 'capacidade', 'servico_dias_uteis_mes', 'Dias uteis no mes', 'Dias uteis de operacao no mes', 'dias', 'quantidade', 'manual', 'Calendario operacional', 22, 22, 1, 31, 1, 140),
            $this->row('servico', 'capacidade', 'servico_horas_produtivas_mensais', 'Horas produtivas mensais', 'Capacidade produtiva total no mes', 'h', 'horas', 'calculado', 'TecnicosAtivos * HorasDia * DiasUteis', 74.8, 74.8, 0, 99999, 0, 150),
            $this->row('servico', 'capacidade', 'servico_custo_hora_produtiva', 'Custo hora produtiva', 'Custo de uma hora real de producao', 'R$', 'valor', 'calculado', 'CustosFixosMensais / HorasProdutivasMensais', 40.1070, 40.1070, 0, 999999, 0, 160),

            // SERVICO - custos diretos
            $this->row('servico', 'custos_diretos', 'servico_consumiveis_valor', 'Consumiveis', 'Cola, fita, limpeza e insumos', 'R$', 'valor', 'manual', 'Soma dos consumiveis por servico', 6, 6, 0, 999999, 1, 210),
            $this->row('servico', 'custos_diretos', 'servico_tempo_indireto_horas', 'Tempo indireto rateado', 'Recepcao tecnica/checklist/comunicacao', 'h', 'horas', 'manual', 'Horas indiretas por atendimento', 0.2, 0.2, 0, 24, 1, 220),
            $this->row('servico', 'custos_diretos', 'servico_tempo_indireto_rateado_valor', 'Valor do tempo indireto', 'Conversao do tempo indireto em custo', 'R$', 'valor', 'calculado', 'TempoIndiretoHoras * CustoHoraProdutiva', 8.0214, 8.0214, 0, 999999, 0, 230),
            $this->row('servico', 'custos_diretos', 'servico_reserva_garantia_valor', 'Reserva de garantia', 'Reserva para retrabalho/garantia', 'R$', 'valor', 'manual', 'Valor tecnico definido por historico', 4, 4, 0, 999999, 1, 240),
            $this->row('servico', 'custos_diretos', 'servico_perdas_pequenas_valor', 'Perdas pequenas', 'Perdas pequenas de processo', 'R$', 'valor', 'manual', 'Valor tecnico medio', 2, 2, 0, 999999, 1, 250),
            $this->row('servico', 'custos_diretos', 'servico_custos_diretos_total', 'Custos diretos do servico', 'Custo operacional direto total do servico', 'R$', 'valor', 'calculado', 'Consumiveis + TempoIndireto + Reserva + Perdas', 20.0214, 20.0214, 0, 999999, 0, 260),
            $this->row('servico', 'custos_diretos', 'servico_risco_percentual', 'Risco percentual adicional', 'Risco tecnico percentual adicional (opcional)', '%', 'percentual', 'manual', 'Percentual de risco extra para servico', 0, 0, 0, 100, 1, 270),

            // SERVICO - margem/taxa
            $this->row('servico', 'margem_taxas', 'servico_margem_alvo_percentual', 'Margem alvo do servico', 'Margem comercial alvo para servicos', '%', 'percentual', 'manual', 'Percentual comercial definido pela empresa', 25, 25, 0, 300, 1, 310),
            $this->row('servico', 'margem_taxas', 'servico_taxa_recebimento_percentual', 'Taxa de recebimento', 'Taxa financeira de recebimento', '%', 'percentual', 'manual', 'Taxa media de cartao/recebimento', 3.5, 3.5, 0, 100, 1, 320),
            $this->row('servico', 'margem_taxas', 'servico_imposto_percentual', 'Imposto', 'Carga tributaria sobre venda de servico', '%', 'percentual', 'manual', 'Percentual fiscal efetivo', 0, 0, 0, 100, 1, 330),

            // SERVICO - resultado
            $this->row('servico', 'resultado', 'servico_custo_servico_total', 'Custo total do servico', 'Custo tecnico antes da venda', 'R$', 'valor', 'calculado', '(TempoTecnico * CustoHora) + CustosDiretos + RiscoPercentual', 60.1284, 60.1284, 0, 999999, 0, 410),
            $this->row('servico', 'resultado', 'servico_divisor_tecnico', 'Divisor tecnico', 'Fator de divisao para formacao do preco minimo', 'fator', 'valor', 'calculado', '1 - (Margem + Taxa + Imposto)/100', 0.7150, 0.7150, 0.01, 1, 0, 420),
            $this->row('servico', 'resultado', 'servico_preco_minimo_tecnico', 'Preco minimo tecnico', 'Menor preco sustentavel para o servico', 'R$', 'valor', 'calculado', 'CustoServicoTotal / DivisorTecnico', 84.0957, 84.0957, 0, 999999, 0, 430),
            $this->row('servico', 'resultado', 'servico_preco_tabela_referencia', 'Preco tabela de referencia', 'Preco comercial praticado para comparacao', 'R$', 'valor', 'manual', 'Politica comercial da empresa', 99, 99, 0, 999999, 1, 440),

            // PRODUTO - custo e margem
            $this->row('produto', 'custo_aquisicao', 'produto_preco_compra', 'Preco de compra do produto', 'Valor pago ao fornecedor', 'R$', 'valor', 'manual', 'Direto da NF/pedido', 50, 50, 0, 999999, 1, 10),
            $this->row('produto', 'custo_aquisicao', 'produto_frete_rateado', 'Frete rateado', 'Parcela de frete alocada ao produto', 'R$', 'valor', 'manual', 'Frete total / qtd do lote', 0, 0, 0, 999999, 1, 20),
            $this->row('produto', 'custo_aquisicao', 'produto_impostos_nao_recuperaveis', 'Impostos nao recuperaveis', 'Tributos sem credito fiscal', 'R$', 'valor', 'manual', 'Soma de impostos sem credito', 0, 0, 0, 999999, 1, 30),
            $this->row('produto', 'custo_aquisicao', 'produto_custo_liquido', 'Custo liquido do produto', 'Custo base para precificacao de venda', 'R$', 'valor', 'calculado', 'PrecoCompra + Frete + Impostos', 50, 50, 0, 999999, 0, 40),
            $this->row('produto', 'encargos', 'produto_perdas_operacionais_percentual', 'Perdas operacionais', 'Percentual para perdas operacionais', '%', 'percentual', 'manual', 'Percentual definido pela operacao', 2, 2, 0, 100, 1, 110),
            $this->row('produto', 'encargos', 'produto_encargos_operacionais_percentual', 'Encargos operacionais', 'Encargos da operacao comercial', '%', 'percentual', 'manual', 'Percentual definido pela operacao', 8, 8, 0, 100, 1, 120),
            $this->row('produto', 'margem', 'produto_margem_percentual', 'Margem do produto', 'Margem comercial de produto avulso', '%', 'percentual', 'manual', 'Percentual comercial definido pela empresa', 35, 35, 0, 300, 1, 210),
            $this->row('produto', 'resultado', 'produto_preco_sugerido', 'Preco sugerido do produto', 'Preco final sugerido para venda avulsa', 'R$', 'valor', 'calculado', 'CustoLiquido + Perdas + Encargos + Margem', 72.5, 72.5, 0, 999999, 0, 310),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function row(
        string $categoria,
        string $secao,
        string $codigo,
        string $nome,
        string $descricao,
        string $unidade,
        string $tipoDado,
        string $tipoEntrada,
        string $formula,
        float $valor,
        float $valorPadrao,
        ?float $minimo,
        ?float $maximo,
        int $editavel,
        int $ordem
    ): array {
        return [
            'categoria' => $categoria,
            'secao' => $secao,
            'codigo' => $codigo,
            'nome' => $nome,
            'descricao' => $descricao,
            'unidade' => $unidade,
            'tipo_dado' => $tipoDado,
            'tipo_entrada' => $tipoEntrada,
            'formula' => $formula,
            'valor' => round($valor, 4),
            'valor_padrao' => round($valorPadrao, 4),
            'minimo' => $minimo !== null ? round($minimo, 4) : null,
            'maximo' => $maximo !== null ? round($maximo, 4) : null,
            'editavel' => $editavel,
            'origem' => 'manual',
            'ativo' => 1,
            'ordem' => $ordem,
        ];
    }
}

