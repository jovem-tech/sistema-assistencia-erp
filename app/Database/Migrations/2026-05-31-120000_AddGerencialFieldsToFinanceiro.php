<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddGerencialFieldsToFinanceiro extends Migration
{
    /**
     * @return array<string,array<string,mixed>>
     */
    private function fieldsDefinition(): array
    {
        return [
            'data_competencia' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'origem_tipo' => [
                'type' => 'VARCHAR',
                'constraint' => 40,
                'null' => true,
            ],
            'origem_id' => [
                'type' => 'INT',
                'null' => true,
            ],
            'grupo_dre' => [
                'type' => 'VARCHAR',
                'constraint' => 60,
                'null' => true,
            ],
            'subgrupo_dre' => [
                'type' => 'VARCHAR',
                'constraint' => 80,
                'null' => true,
            ],
            'impacta_dre' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
            ],
            'impacta_fluxo_caixa' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
            ],
        ];
    }

    public function up()
    {
        if (! $this->db->tableExists('financeiro')) {
            return;
        }

        foreach ($this->fieldsDefinition() as $field => $definition) {
            if ($this->db->fieldExists($field, 'financeiro')) {
                continue;
            }

            $this->forge->addColumn('financeiro', [$field => $definition]);
        }

        $this->backfillGerencialFields();
    }

    public function down()
    {
        if (! $this->db->tableExists('financeiro')) {
            return;
        }

        foreach (array_keys($this->fieldsDefinition()) as $field) {
            if (! $this->db->fieldExists($field, 'financeiro')) {
                continue;
            }

            $this->forge->dropColumn('financeiro', $field);
        }
    }

    private function backfillGerencialFields(): void
    {
        $osEntregaMap = [];
        if ($this->db->tableExists('os') && $this->db->fieldExists('data_entrega', 'os')) {
            foreach ($this->db->table('os')->select('id, data_entrega')->get()->getResultArray() as $row) {
                $osId = (int) ($row['id'] ?? 0);
                if ($osId <= 0) {
                    continue;
                }

                $dataEntrega = trim((string) ($row['data_entrega'] ?? ''));
                if ($dataEntrega === '') {
                    continue;
                }

                $osEntregaMap[$osId] = substr($dataEntrega, 0, 10);
            }
        }

        $rows = $this->db->table('financeiro')
            ->select('id, os_id, tipo, categoria, data_vencimento, data_pagamento')
            ->get()
            ->getResultArray();

        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            $tipo = strtolower(trim((string) ($row['tipo'] ?? '')));
            $categoria = trim((string) ($row['categoria'] ?? ''));
            $categoriaNorm = $this->normalize($categoria);
            $osId = (int) ($row['os_id'] ?? 0);

            $update = [
                'origem_id' => $osId > 0 ? $osId : null,
                'impacta_fluxo_caixa' => 1,
            ];

            if ($tipo === 'receber' && $osId > 0) {
                $update['origem_tipo'] = 'os';
                $update['grupo_dre'] = 'Receita Operacional';
                $update['subgrupo_dre'] = 'Servicos e pecas de OS';
                $update['impacta_dre'] = 1;
            } elseif ($tipo === 'pagar' && str_contains($categoriaNorm, 'compra') && str_contains($categoriaNorm, 'peca')) {
                $update['origem_tipo'] = $osId > 0 ? 'os_item_pendencia' : 'estoque';
                $update['grupo_dre'] = 'Custo Direto (OS)';
                $update['subgrupo_dre'] = 'Compra emergencial de pecas';
                $update['impacta_dre'] = 0;
            } elseif ($tipo === 'receber') {
                $update['origem_tipo'] = 'manual';
                $update['grupo_dre'] = 'Outras Receitas';
                $update['subgrupo_dre'] = $this->humanizeCategory($categoria, 'Receita avulsa');
                $update['impacta_dre'] = 1;
            } else {
                $update['origem_tipo'] = $osId > 0 ? 'financeiro_os' : 'manual';
                $update['grupo_dre'] = 'Despesas Operacionais';
                $update['subgrupo_dre'] = $this->resolveExpenseSubgroup($categoria);
                $update['impacta_dre'] = 1;
            }

            $dataCompetencia = null;
            if ($tipo === 'receber' && $osId > 0 && isset($osEntregaMap[$osId])) {
                $dataCompetencia = $osEntregaMap[$osId];
            }

            $dataCompetencia = $dataCompetencia
                ?? $this->normalizeDate($row['data_pagamento'] ?? null)
                ?? $this->normalizeDate($row['data_vencimento'] ?? null);

            $update['data_competencia'] = $dataCompetencia;

            $this->db->table('financeiro')->where('id', $id)->update($update);
        }
    }

    private function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value), 'UTF-8');
        $value = strtr($value, [
            'á' => 'a',
            'à' => 'a',
            'ã' => 'a',
            'â' => 'a',
            'é' => 'e',
            'ê' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ô' => 'o',
            'õ' => 'o',
            'ú' => 'u',
            'ç' => 'c',
        ]);

        if (function_exists('iconv')) {
            $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
            if ($ascii !== false) {
                $value = strtolower($ascii);
            }
        }

        return preg_replace('/\s+/', ' ', $value) ?? $value;
    }

    private function humanizeCategory(string $categoria, string $fallback): string
    {
        $categoria = trim($categoria);
        if ($categoria === '') {
            return $fallback;
        }

        return mb_convert_case($categoria, MB_CASE_TITLE, 'UTF-8');
    }

    private function resolveExpenseSubgroup(string $categoria): string
    {
        $normalized = $this->normalize($categoria);

        return match (true) {
            str_contains($normalized, 'aluguel') => 'Aluguel',
            str_contains($normalized, 'energia') => 'Energia',
            str_contains($normalized, 'agua') => 'Agua',
            str_contains($normalized, 'internet') => 'Internet',
            str_contains($normalized, 'telefone') => 'Telefonia',
            str_contains($normalized, 'salario'),
            str_contains($normalized, 'folha'),
            str_contains($normalized, 'pro labore') => 'Pessoal',
            str_contains($normalized, 'imposto'),
            str_contains($normalized, 'taxa'),
            str_contains($normalized, 'tarifa') => 'Taxas e impostos',
            default => $this->humanizeCategory($categoria, 'Despesa operacional'),
        };
    }

    private function normalizeDate(mixed $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        return substr($value, 0, 10);
    }
}
