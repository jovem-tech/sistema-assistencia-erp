<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateOsEncerramentoFinanceiroCartoes extends Migration
{
    public function up()
    {
        $this->ensureOsEncerramentoColumns();
        $this->createCartaoOperadorasTable();
        $this->createCartaoBandeirasTable();
        $this->createCartaoTaxasTable();
        $this->createMovimentosCartaoTable();
        $this->createOsCobrancaAgendamentosTable();
        $this->seedCartaoCatalogos();
        $this->seedFinanceiroCategoriaTaxaCartao();
    }

    public function down()
    {
        if ($this->db->tableExists('os_cobranca_agendamentos')) {
            $this->forge->dropTable('os_cobranca_agendamentos', true);
        }

        if ($this->db->tableExists('financeiro_movimentos_cartao')) {
            $this->forge->dropTable('financeiro_movimentos_cartao', true);
        }

        if ($this->db->tableExists('financeiro_cartao_taxas')) {
            $this->forge->dropTable('financeiro_cartao_taxas', true);
        }

        if ($this->db->tableExists('financeiro_cartao_bandeiras')) {
            $this->forge->dropTable('financeiro_cartao_bandeiras', true);
        }

        if ($this->db->tableExists('financeiro_cartao_operadoras')) {
            $this->forge->dropTable('financeiro_cartao_operadoras', true);
        }

        if ($this->db->tableExists('os')) {
            foreach (['status_final_pendente_pagamento', 'baixa_tecnica_em', 'baixa_tecnica_por'] as $column) {
                if ($this->db->fieldExists($column, 'os')) {
                    $this->forge->dropColumn('os', $column);
                }
            }
        }
    }

    private function ensureOsEncerramentoColumns(): void
    {
        if (! $this->db->tableExists('os')) {
            return;
        }

        $fields = [];

        if (! $this->db->fieldExists('status_final_pendente_pagamento', 'os')) {
            $fields['status_final_pendente_pagamento'] = [
                'type' => 'VARCHAR',
                'constraint' => 60,
                'null' => true,
                'after' => 'status',
            ];
        }

        if (! $this->db->fieldExists('baixa_tecnica_em', 'os')) {
            $fields['baixa_tecnica_em'] = [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'data_entrega',
            ];
        }

        if (! $this->db->fieldExists('baixa_tecnica_por', 'os')) {
            $fields['baixa_tecnica_por'] = [
                'type' => 'INT',
                'unsigned' => true,
                'null' => true,
                'after' => 'baixa_tecnica_em',
            ];
        }

        if ($fields !== []) {
            $this->forge->addColumn('os', $fields);
        }
    }

    private function createCartaoOperadorasTable(): void
    {
        if ($this->db->tableExists('financeiro_cartao_operadoras')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'nome' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'descricao' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'ordem_exibicao' => [
                'type' => 'INT',
                'default' => 0,
            ],
            'prazo_padrao_dias' => [
                'type' => 'INT',
                'default' => 30,
            ],
            'ativo' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
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
        $this->forge->createTable('financeiro_cartao_operadoras', true);
        $this->db->query('CREATE UNIQUE INDEX ux_financeiro_cartao_operadoras_nome ON financeiro_cartao_operadoras (nome)');
    }

    private function createCartaoBandeirasTable(): void
    {
        if ($this->db->tableExists('financeiro_cartao_bandeiras')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'nome' => [
                'type' => 'VARCHAR',
                'constraint' => 80,
            ],
            'ordem_exibicao' => [
                'type' => 'INT',
                'default' => 0,
            ],
            'ativo' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
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
        $this->forge->createTable('financeiro_cartao_bandeiras', true);
        $this->db->query('CREATE UNIQUE INDEX ux_financeiro_cartao_bandeiras_nome ON financeiro_cartao_bandeiras (nome)');
    }

    private function createCartaoTaxasTable(): void
    {
        if ($this->db->tableExists('financeiro_cartao_taxas')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'operadora_id' => [
                'type' => 'INT',
                'unsigned' => true,
            ],
            'bandeira_id' => [
                'type' => 'INT',
                'unsigned' => true,
                'null' => true,
            ],
            'modalidade' => [
                'type' => 'ENUM',
                'constraint' => ['credito', 'debito'],
                'default' => 'credito',
            ],
            'parcelas_inicial' => [
                'type' => 'INT',
                'default' => 1,
            ],
            'parcelas_final' => [
                'type' => 'INT',
                'default' => 1,
            ],
            'taxa_percentual' => [
                'type' => 'DECIMAL',
                'constraint' => '8,4',
                'default' => 0,
            ],
            'taxa_fixa' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'default' => 0,
            ],
            'prazo_recebimento_dias' => [
                'type' => 'INT',
                'default' => 30,
            ],
            'observacoes' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'ativo' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
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
        $this->forge->addKey('operadora_id');
        $this->forge->addKey('bandeira_id');
        $this->forge->addForeignKey('operadora_id', 'financeiro_cartao_operadoras', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('bandeira_id', 'financeiro_cartao_bandeiras', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('financeiro_cartao_taxas', true);
    }

    private function createMovimentosCartaoTable(): void
    {
        if ($this->db->tableExists('financeiro_movimentos_cartao')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'movimento_id' => [
                'type' => 'INT',
                'unsigned' => true,
            ],
            'operadora_id' => [
                'type' => 'INT',
                'unsigned' => true,
                'null' => true,
            ],
            'bandeira_id' => [
                'type' => 'INT',
                'unsigned' => true,
                'null' => true,
            ],
            'taxa_id' => [
                'type' => 'INT',
                'unsigned' => true,
                'null' => true,
            ],
            'modalidade' => [
                'type' => 'ENUM',
                'constraint' => ['credito', 'debito'],
                'default' => 'credito',
            ],
            'parcelas' => [
                'type' => 'INT',
                'default' => 1,
            ],
            'valor_bruto' => [
                'type' => 'DECIMAL',
                'constraint' => '12,2',
                'default' => 0,
            ],
            'taxa_percentual' => [
                'type' => 'DECIMAL',
                'constraint' => '8,4',
                'default' => 0,
            ],
            'taxa_fixa' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'default' => 0,
            ],
            'valor_taxa' => [
                'type' => 'DECIMAL',
                'constraint' => '12,2',
                'default' => 0,
            ],
            'valor_liquido' => [
                'type' => 'DECIMAL',
                'constraint' => '12,2',
                'default' => 0,
            ],
            'prazo_recebimento_dias' => [
                'type' => 'INT',
                'default' => 0,
            ],
            'data_prevista_recebimento' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'observacoes' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
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
        $this->forge->addKey('movimento_id');
        $this->forge->addKey('operadora_id');
        $this->forge->addKey('bandeira_id');
        $this->forge->addKey('taxa_id');
        $this->forge->addForeignKey('movimento_id', 'financeiro_movimentos', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('operadora_id', 'financeiro_cartao_operadoras', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('bandeira_id', 'financeiro_cartao_bandeiras', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('taxa_id', 'financeiro_cartao_taxas', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('financeiro_movimentos_cartao', true);
    }

    private function createOsCobrancaAgendamentosTable(): void
    {
        if ($this->db->tableExists('os_cobranca_agendamentos')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'os_id' => [
                'type' => 'INT',
            ],
            'financeiro_id' => [
                'type' => 'INT',
                'null' => true,
            ],
            'cliente_id' => [
                'type' => 'INT',
                'null' => true,
            ],
            'canal' => [
                'type' => 'ENUM',
                'constraint' => ['whatsapp'],
                'default' => 'whatsapp',
            ],
            'prazo_dias' => [
                'type' => 'INT',
                'default' => 1,
            ],
            'enviar_em' => [
                'type' => 'DATETIME',
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['pendente', 'enviado', 'cancelado', 'erro'],
                'default' => 'pendente',
            ],
            'ultima_tentativa_em' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'enviado_em' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'mensagem_enviada' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'retorno_payload' => [
                'type' => 'LONGTEXT',
                'null' => true,
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
        $this->forge->addKey('os_id');
        $this->forge->addKey('financeiro_id');
        $this->forge->addKey('cliente_id');
        $this->forge->addKey('status');
        $this->forge->addKey('enviar_em');
        $this->forge->addForeignKey('os_id', 'os', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('financeiro_id', 'financeiro', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('os_cobranca_agendamentos', true);
    }

    private function seedCartaoCatalogos(): void
    {
        if (! $this->db->tableExists('financeiro_cartao_operadoras')) {
            return;
        }

        $now = date('Y-m-d H:i:s');

        $operadoras = [
            ['nome' => 'Mercado Pago', 'descricao' => 'Operadora padrÃ£o para recebimentos em cartÃ£o.', 'ordem_exibicao' => 10, 'prazo_padrao_dias' => 30],
            ['nome' => 'Stone', 'descricao' => 'ConfiguraÃ§Ã£o padrÃ£o para maquininha Stone.', 'ordem_exibicao' => 20, 'prazo_padrao_dias' => 30],
            ['nome' => 'PagBank', 'descricao' => 'ConfiguraÃ§Ã£o padrÃ£o para maquininha PagBank.', 'ordem_exibicao' => 30, 'prazo_padrao_dias' => 30],
            ['nome' => 'Cielo', 'descricao' => 'ConfiguraÃ§Ã£o padrÃ£o para maquininha Cielo.', 'ordem_exibicao' => 40, 'prazo_padrao_dias' => 30],
        ];

        foreach ($operadoras as $item) {
            $exists = $this->db->table('financeiro_cartao_operadoras')
                ->where('nome', $item['nome'])
                ->get()
                ->getRowArray();

            if ($exists) {
                continue;
            }

            $this->db->table('financeiro_cartao_operadoras')->insert($item + [
                'ativo' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $bandeiras = [
            ['nome' => 'Visa', 'ordem_exibicao' => 10],
            ['nome' => 'Mastercard', 'ordem_exibicao' => 20],
            ['nome' => 'Elo', 'ordem_exibicao' => 30],
            ['nome' => 'Hipercard', 'ordem_exibicao' => 40],
            ['nome' => 'American Express', 'ordem_exibicao' => 50],
        ];

        foreach ($bandeiras as $item) {
            $exists = $this->db->table('financeiro_cartao_bandeiras')
                ->where('nome', $item['nome'])
                ->get()
                ->getRowArray();

            if ($exists) {
                continue;
            }

            $this->db->table('financeiro_cartao_bandeiras')->insert($item + [
                'ativo' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $operadora = $this->db->table('financeiro_cartao_operadoras')
            ->where('nome', 'Mercado Pago')
            ->get()
            ->getRowArray();

        if (! $operadora) {
            return;
        }

        $operadoraId = (int) ($operadora['id'] ?? 0);
        if ($operadoraId <= 0) {
            return;
        }

        $taxas = [
            [
                'operadora_id' => $operadoraId,
                'bandeira_id' => null,
                'modalidade' => 'debito',
                'parcelas_inicial' => 1,
                'parcelas_final' => 1,
                'taxa_percentual' => 1.9900,
                'taxa_fixa' => 0,
                'prazo_recebimento_dias' => 1,
                'observacoes' => 'Taxa inicial sugerida para dÃ©bito Ã  vista.',
            ],
            [
                'operadora_id' => $operadoraId,
                'bandeira_id' => null,
                'modalidade' => 'credito',
                'parcelas_inicial' => 1,
                'parcelas_final' => 1,
                'taxa_percentual' => 3.1900,
                'taxa_fixa' => 0,
                'prazo_recebimento_dias' => 30,
                'observacoes' => 'Taxa inicial sugerida para crÃ©dito Ã  vista.',
            ],
            [
                'operadora_id' => $operadoraId,
                'bandeira_id' => null,
                'modalidade' => 'credito',
                'parcelas_inicial' => 2,
                'parcelas_final' => 6,
                'taxa_percentual' => 3.7900,
                'taxa_fixa' => 0,
                'prazo_recebimento_dias' => 30,
                'observacoes' => 'Faixa inicial sugerida para crÃ©dito parcelado.',
            ],
            [
                'operadora_id' => $operadoraId,
                'bandeira_id' => null,
                'modalidade' => 'credito',
                'parcelas_inicial' => 7,
                'parcelas_final' => 12,
                'taxa_percentual' => 4.2900,
                'taxa_fixa' => 0,
                'prazo_recebimento_dias' => 30,
                'observacoes' => 'Faixa inicial sugerida para crÃ©dito parcelado longo.',
            ],
        ];

        foreach ($taxas as $item) {
            $exists = $this->db->table('financeiro_cartao_taxas')
                ->where('operadora_id', $item['operadora_id'])
                ->where('bandeira_id', $item['bandeira_id'])
                ->where('modalidade', $item['modalidade'])
                ->where('parcelas_inicial', $item['parcelas_inicial'])
                ->where('parcelas_final', $item['parcelas_final'])
                ->get()
                ->getRowArray();

            if ($exists) {
                continue;
            }

            $this->db->table('financeiro_cartao_taxas')->insert($item + [
                'ativo' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function seedFinanceiroCategoriaTaxaCartao(): void
    {
        if (! $this->db->tableExists('financeiro_categorias')) {
            return;
        }

        $categoria = $this->db->table('financeiro_categorias')
            ->where('nome', 'Taxa de cartÃ£o')
            ->whereIn('tipo', ['pagar', 'ambos'])
            ->get()
            ->getRowArray();

        if ($categoria) {
            return;
        }

        $grupoId = null;
        $subgrupoId = null;

        if ($this->db->tableExists('financeiro_dre_grupos')) {
            $grupo = $this->db->table('financeiro_dre_grupos')
                ->where('nome', 'Despesas Operacionais')
                ->get()
                ->getRowArray();
            $grupoId = $grupo['id'] ?? null;
        }

        if ($grupoId && $this->db->tableExists('financeiro_dre_subgrupos')) {
            $subgrupo = $this->db->table('financeiro_dre_subgrupos')
                ->where('grupo_id', $grupoId)
                ->where('nome', 'Taxas e impostos')
                ->get()
                ->getRowArray();
            $subgrupoId = $subgrupo['id'] ?? null;
        }

        $now = date('Y-m-d H:i:s');
        $this->db->table('financeiro_categorias')->insert([
            'nome' => 'Taxa de cartÃ£o',
            'tipo' => 'pagar',
            'dre_grupo_id' => $grupoId,
            'dre_subgrupo_id' => $subgrupoId,
            'impacta_dre_padrao' => 1,
            'impacta_fluxo_caixa_padrao' => 1,
            'dre_fixo_mensal_padrao' => 0,
            'ordem_exibicao' => 999,
            'ativo' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
