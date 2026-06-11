<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFinanceiroCatalogos extends Migration
{
    public function up()
    {
        $this->createDreGruposTable();
        $this->createDreSubgruposTable();
        $this->createCategoriasTable();
        $this->seedCatalogos();
    }

    public function down()
    {
        if ($this->db->tableExists('financeiro_categorias')) {
            $this->forge->dropTable('financeiro_categorias', true);
        }

        if ($this->db->tableExists('financeiro_dre_subgrupos')) {
            $this->forge->dropTable('financeiro_dre_subgrupos', true);
        }

        if ($this->db->tableExists('financeiro_dre_grupos')) {
            $this->forge->dropTable('financeiro_dre_grupos', true);
        }
    }

    private function createDreGruposTable(): void
    {
        if ($this->db->tableExists('financeiro_dre_grupos')) {
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
            'descricao' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
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
        $this->forge->createTable('financeiro_dre_grupos', true);
        $this->db->query('CREATE UNIQUE INDEX ux_financeiro_dre_grupos_nome ON financeiro_dre_grupos (nome)');
    }

    private function createDreSubgruposTable(): void
    {
        if ($this->db->tableExists('financeiro_dre_subgrupos')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'grupo_id' => [
                'type' => 'INT',
                'unsigned' => true,
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
        $this->forge->addKey('grupo_id');
        $this->forge->addForeignKey('grupo_id', 'financeiro_dre_grupos', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('financeiro_dre_subgrupos', true);
        $this->db->query('CREATE UNIQUE INDEX ux_financeiro_dre_subgrupos_grupo_nome ON financeiro_dre_subgrupos (grupo_id, nome)');
    }

    private function createCategoriasTable(): void
    {
        if ($this->db->tableExists('financeiro_categorias')) {
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
            'tipo' => [
                'type' => 'ENUM',
                'constraint' => ['receber', 'pagar', 'ambos'],
                'default' => 'ambos',
            ],
            'dre_grupo_id' => [
                'type' => 'INT',
                'unsigned' => true,
                'null' => true,
            ],
            'dre_subgrupo_id' => [
                'type' => 'INT',
                'unsigned' => true,
                'null' => true,
            ],
            'impacta_dre_padrao' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
            ],
            'impacta_fluxo_caixa_padrao' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
            ],
            'dre_fixo_mensal_padrao' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
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
        $this->forge->addKey('dre_grupo_id');
        $this->forge->addKey('dre_subgrupo_id');
        $this->forge->addForeignKey('dre_grupo_id', 'financeiro_dre_grupos', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('dre_subgrupo_id', 'financeiro_dre_subgrupos', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('financeiro_categorias', true);
        $this->db->query('CREATE UNIQUE INDEX ux_financeiro_categorias_nome_tipo ON financeiro_categorias (nome, tipo)');
    }

    private function seedCatalogos(): void
    {
        $groupMap = $this->seedDreGrupos();
        $subgroupMap = $this->seedDreSubgrupos($groupMap);
        $this->seedCategoriasPadrao($groupMap, $subgroupMap);
        $this->backfillFromFinanceiro($groupMap, $subgroupMap);
    }

    /**
     * @return array<string,int>
     */
    private function seedDreGrupos(): array
    {
        $defaults = [
            ['nome' => 'Receita Operacional', 'descricao' => 'Receitas principais da operacao', 'ordem_exibicao' => 10],
            ['nome' => 'Outras Receitas', 'descricao' => 'Receitas nao recorrentes ou avulsas', 'ordem_exibicao' => 20],
            ['nome' => 'Despesas Operacionais', 'descricao' => 'Despesas administrativas e operacionais', 'ordem_exibicao' => 30],
            ['nome' => 'Custo Direto (OS)', 'descricao' => 'Custos diretamente ligados a ordens de servico', 'ordem_exibicao' => 40],
            ['nome' => 'Ajustes Gerenciais', 'descricao' => 'Ajustes ou movimentos gerenciais', 'ordem_exibicao' => 50],
        ];

        foreach ($defaults as $item) {
            $this->ensureByName('financeiro_dre_grupos', $item['nome'], $item);
        }

        $rows = $this->db->table('financeiro_dre_grupos')->select('id, nome')->get()->getResultArray();
        $map = [];
        foreach ($rows as $row) {
            $map[(string) $row['nome']] = (int) $row['id'];
        }

        return $map;
    }

    /**
     * @param array<string,int> $groupMap
     * @return array<string,int>
     */
    private function seedDreSubgrupos(array $groupMap): array
    {
        $defaults = [
            ['grupo' => 'Receita Operacional', 'nome' => 'Servicos e pecas de OS', 'ordem_exibicao' => 10],
            ['grupo' => 'Outras Receitas', 'nome' => 'Receita avulsa', 'ordem_exibicao' => 10],
            ['grupo' => 'Despesas Operacionais', 'nome' => 'Aluguel', 'ordem_exibicao' => 10],
            ['grupo' => 'Despesas Operacionais', 'nome' => 'Energia', 'ordem_exibicao' => 20],
            ['grupo' => 'Despesas Operacionais', 'nome' => 'Agua', 'ordem_exibicao' => 30],
            ['grupo' => 'Despesas Operacionais', 'nome' => 'Internet', 'ordem_exibicao' => 40],
            ['grupo' => 'Despesas Operacionais', 'nome' => 'Telefonia', 'ordem_exibicao' => 50],
            ['grupo' => 'Despesas Operacionais', 'nome' => 'Pessoal', 'ordem_exibicao' => 60],
            ['grupo' => 'Despesas Operacionais', 'nome' => 'Taxas e impostos', 'ordem_exibicao' => 70],
            ['grupo' => 'Despesas Operacionais', 'nome' => 'Despesa operacional', 'ordem_exibicao' => 80],
            ['grupo' => 'Custo Direto (OS)', 'nome' => 'Compra emergencial de pecas', 'ordem_exibicao' => 10],
            ['grupo' => 'Ajustes Gerenciais', 'nome' => 'Ajuste gerencial', 'ordem_exibicao' => 10],
        ];

        foreach ($defaults as $item) {
            $groupId = $groupMap[$item['grupo']] ?? 0;
            if ($groupId <= 0) {
                continue;
            }

            $this->ensureSubgrupo($groupId, $item['nome'], [
                'grupo_id' => $groupId,
                'nome' => $item['nome'],
                'ordem_exibicao' => $item['ordem_exibicao'],
                'ativo' => 1,
            ]);
        }

        $rows = $this->db->table('financeiro_dre_subgrupos s')
            ->select('s.id, s.nome, g.nome as grupo_nome')
            ->join('financeiro_dre_grupos g', 'g.id = s.grupo_id')
            ->get()
            ->getResultArray();

        $map = [];
        foreach ($rows as $row) {
            $key = (string) $row['grupo_nome'] . '|' . (string) $row['nome'];
            $map[$key] = (int) $row['id'];
        }

        return $map;
    }

    /**
     * @param array<string,int> $groupMap
     * @param array<string,int> $subgroupMap
     */
    private function seedCategoriasPadrao(array $groupMap, array $subgroupMap): void
    {
        $defaults = [
            ['nome' => 'Servico', 'tipo' => 'receber', 'grupo' => 'Receita Operacional', 'subgrupo' => 'Servicos e pecas de OS', 'impacta_dre' => 1, 'impacta_fluxo' => 1, 'fixo' => 0, 'ordem' => 10],
            ['nome' => 'Venda de pecas', 'tipo' => 'receber', 'grupo' => 'Receita Operacional', 'subgrupo' => 'Servicos e pecas de OS', 'impacta_dre' => 1, 'impacta_fluxo' => 1, 'fixo' => 0, 'ordem' => 20],
            ['nome' => 'Receita avulsa', 'tipo' => 'receber', 'grupo' => 'Outras Receitas', 'subgrupo' => 'Receita avulsa', 'impacta_dre' => 1, 'impacta_fluxo' => 1, 'fixo' => 0, 'ordem' => 30],
            ['nome' => 'Aluguel', 'tipo' => 'pagar', 'grupo' => 'Despesas Operacionais', 'subgrupo' => 'Aluguel', 'impacta_dre' => 1, 'impacta_fluxo' => 1, 'fixo' => 1, 'ordem' => 10],
            ['nome' => 'Energia', 'tipo' => 'pagar', 'grupo' => 'Despesas Operacionais', 'subgrupo' => 'Energia', 'impacta_dre' => 1, 'impacta_fluxo' => 1, 'fixo' => 1, 'ordem' => 20],
            ['nome' => 'Agua', 'tipo' => 'pagar', 'grupo' => 'Despesas Operacionais', 'subgrupo' => 'Agua', 'impacta_dre' => 1, 'impacta_fluxo' => 1, 'fixo' => 1, 'ordem' => 30],
            ['nome' => 'Internet', 'tipo' => 'pagar', 'grupo' => 'Despesas Operacionais', 'subgrupo' => 'Internet', 'impacta_dre' => 1, 'impacta_fluxo' => 1, 'fixo' => 1, 'ordem' => 40],
            ['nome' => 'Telefonia', 'tipo' => 'pagar', 'grupo' => 'Despesas Operacionais', 'subgrupo' => 'Telefonia', 'impacta_dre' => 1, 'impacta_fluxo' => 1, 'fixo' => 1, 'ordem' => 50],
            ['nome' => 'Compra de pecas', 'tipo' => 'pagar', 'grupo' => 'Custo Direto (OS)', 'subgrupo' => 'Compra emergencial de pecas', 'impacta_dre' => 0, 'impacta_fluxo' => 1, 'fixo' => 0, 'ordem' => 60],
            ['nome' => 'Impostos e taxas', 'tipo' => 'pagar', 'grupo' => 'Despesas Operacionais', 'subgrupo' => 'Taxas e impostos', 'impacta_dre' => 1, 'impacta_fluxo' => 1, 'fixo' => 0, 'ordem' => 70],
            ['nome' => 'Folha e pro-labore', 'tipo' => 'pagar', 'grupo' => 'Despesas Operacionais', 'subgrupo' => 'Pessoal', 'impacta_dre' => 1, 'impacta_fluxo' => 1, 'fixo' => 1, 'ordem' => 80],
            ['nome' => 'Despesa administrativa', 'tipo' => 'pagar', 'grupo' => 'Despesas Operacionais', 'subgrupo' => 'Despesa operacional', 'impacta_dre' => 1, 'impacta_fluxo' => 1, 'fixo' => 0, 'ordem' => 90],
        ];

        foreach ($defaults as $item) {
            $groupId = $groupMap[$item['grupo']] ?? null;
            $subgroupId = $subgroupMap[$item['grupo'] . '|' . $item['subgrupo']] ?? null;

            $this->ensureCategory($item['nome'], $item['tipo'], [
                'nome' => $item['nome'],
                'tipo' => $item['tipo'],
                'dre_grupo_id' => $groupId,
                'dre_subgrupo_id' => $subgroupId,
                'impacta_dre_padrao' => $item['impacta_dre'],
                'impacta_fluxo_caixa_padrao' => $item['impacta_fluxo'],
                'dre_fixo_mensal_padrao' => $item['fixo'],
                'ordem_exibicao' => $item['ordem'],
                'ativo' => 1,
            ]);
        }
    }

    /**
     * @param array<string,int> $groupMap
     * @param array<string,int> $subgroupMap
     */
    private function backfillFromFinanceiro(array &$groupMap, array &$subgroupMap): void
    {
        if (! $this->db->tableExists('financeiro')) {
            return;
        }

        $hasDreFixo = $this->db->fieldExists('dre_fixo_mensal', 'financeiro');

        $rows = $this->db->table('financeiro')
            ->select('tipo, categoria, grupo_dre, subgrupo_dre, impacta_dre, impacta_fluxo_caixa' . ($hasDreFixo ? ', dre_fixo_mensal' : ''))
            ->get()
            ->getResultArray();

        foreach ($rows as $row) {
            $grupoNome = trim((string) ($row['grupo_dre'] ?? ''));
            if ($grupoNome !== '' && ! isset($groupMap[$grupoNome])) {
                $this->ensureByName('financeiro_dre_grupos', $grupoNome, [
                    'nome' => $grupoNome,
                    'ordem_exibicao' => 999,
                    'ativo' => 1,
                ]);
                $groupMap = $this->seedDreGrupos();
            }

            $subgrupoNome = trim((string) ($row['subgrupo_dre'] ?? ''));
            if ($grupoNome !== '' && $subgrupoNome !== '') {
                $groupId = $groupMap[$grupoNome] ?? 0;
                if ($groupId > 0) {
                    $key = $grupoNome . '|' . $subgrupoNome;
                    if (! isset($subgroupMap[$key])) {
                        $this->ensureSubgrupo($groupId, $subgrupoNome, [
                            'grupo_id' => $groupId,
                            'nome' => $subgrupoNome,
                            'ordem_exibicao' => 999,
                            'ativo' => 1,
                        ]);
                        $subgroupMap = $this->seedDreSubgrupos($groupMap);
                    }
                }
            }

            $categoriaNome = trim((string) ($row['categoria'] ?? ''));
            $tipo = strtolower(trim((string) ($row['tipo'] ?? '')));
            if ($categoriaNome === '' || ! in_array($tipo, ['receber', 'pagar'], true)) {
                continue;
            }

            $this->ensureCategory($categoriaNome, $tipo, [
                'nome' => $categoriaNome,
                'tipo' => $tipo,
                'dre_grupo_id' => $groupMap[$grupoNome] ?? null,
                'dre_subgrupo_id' => ($grupoNome !== '' && $subgrupoNome !== '') ? ($subgroupMap[$grupoNome . '|' . $subgrupoNome] ?? null) : null,
                'impacta_dre_padrao' => (int) ($row['impacta_dre'] ?? 1),
                'impacta_fluxo_caixa_padrao' => (int) ($row['impacta_fluxo_caixa'] ?? 1),
                'dre_fixo_mensal_padrao' => $hasDreFixo ? (int) ($row['dre_fixo_mensal'] ?? 0) : 0,
                'ordem_exibicao' => 999,
                'ativo' => 1,
            ], true);
        }
    }

    /**
     * @param array<string,mixed> $payload
     */
    private function ensureByName(string $table, string $name, array $payload): void
    {
        $exists = $this->db->table($table)->where('nome', $name)->countAllResults();
        if ($exists > 0) {
            return;
        }

        $payload['created_at'] = date('Y-m-d H:i:s');
        $payload['updated_at'] = date('Y-m-d H:i:s');
        $this->db->table($table)->insert($payload);
    }

    /**
     * @param array<string,mixed> $payload
     */
    private function ensureSubgrupo(int $groupId, string $name, array $payload): void
    {
        $exists = $this->db->table('financeiro_dre_subgrupos')
            ->where('grupo_id', $groupId)
            ->where('nome', $name)
            ->countAllResults();

        if ($exists > 0) {
            return;
        }

        $payload['created_at'] = date('Y-m-d H:i:s');
        $payload['updated_at'] = date('Y-m-d H:i:s');
        $this->db->table('financeiro_dre_subgrupos')->insert($payload);
    }

    /**
     * @param array<string,mixed> $payload
     */
    private function ensureCategory(string $name, string $tipo, array $payload, bool $updateWhenEmpty = false): void
    {
        $builder = $this->db->table('financeiro_categorias')
            ->where('nome', $name)
            ->where('tipo', $tipo);

        $row = $builder->get()->getRowArray();
        if (! is_array($row)) {
            $payload['created_at'] = date('Y-m-d H:i:s');
            $payload['updated_at'] = date('Y-m-d H:i:s');
            $this->db->table('financeiro_categorias')->insert($payload);
            return;
        }

        if (! $updateWhenEmpty) {
            return;
        }

        $update = [];
        foreach (['dre_grupo_id', 'dre_subgrupo_id'] as $field) {
            if (empty($row[$field]) && ! empty($payload[$field])) {
                $update[$field] = $payload[$field];
            }
        }

        foreach (['impacta_dre_padrao', 'impacta_fluxo_caixa_padrao', 'dre_fixo_mensal_padrao'] as $field) {
            if (! array_key_exists($field, $row) || $row[$field] === null) {
                $update[$field] = $payload[$field];
            }
        }

        if ($update !== []) {
            $update['updated_at'] = date('Y-m-d H:i:s');
            $this->db->table('financeiro_categorias')->where('id', (int) $row['id'])->update($update);
        }
    }
}
