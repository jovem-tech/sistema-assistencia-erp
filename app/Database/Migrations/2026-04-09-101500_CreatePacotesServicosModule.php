<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePacotesServicosModule extends Migration
{
    private string $pacotesTable = 'pacotes_servicos';
    private string $niveisTable = 'pacotes_servicos_niveis';
    private string $fkNiveisPacote = 'fk_pacotes_niveis_pacote';
    private string $fkPacoteServico = 'fk_pacotes_servico_referencia';
    private string $idxPacotesCategoria = 'idx_pacotes_categoria';
    private string $idxPacotesTipoEquip = 'idx_pacotes_tipo_equip';
    private string $idxPacotesAtivo = 'idx_pacotes_ativo';
    private string $idxNiveisPacote = 'idx_pacotes_niveis_pacote';
    private string $idxNiveisAtivo = 'idx_pacotes_niveis_ativo';
    private string $uxNiveisPacoteNivel = 'ux_pacotes_niveis_pacote_nivel';

    public function up()
    {
        $this->createPacotesTableIfNeeded();
        $this->createNiveisTableIfNeeded();

        $this->safeCreateIndex($this->pacotesTable, $this->idxPacotesCategoria, '(categoria)');
        $this->safeCreateIndex($this->pacotesTable, $this->idxPacotesTipoEquip, '(tipo_equipamento)');
        $this->safeCreateIndex($this->pacotesTable, $this->idxPacotesAtivo, '(ativo)');

        $this->safeCreateIndex($this->niveisTable, $this->idxNiveisPacote, '(pacote_servico_id)');
        $this->safeCreateIndex($this->niveisTable, $this->idxNiveisAtivo, '(ativo)');
        $this->safeCreateIndex($this->niveisTable, $this->uxNiveisPacoteNivel, '(pacote_servico_id, nivel)', true);

        $this->safeAddForeignKey($this->niveisTable, $this->fkNiveisPacote, 'pacote_servico_id', $this->pacotesTable, 'id', 'CASCADE');

        if ($this->db->tableExists('servicos')) {
            $this->safeAddForeignKey($this->pacotesTable, $this->fkPacoteServico, 'servico_referencia_id', 'servicos', 'id', 'SET NULL');
        }

        $this->seedDefaultPackages();
    }

    public function down()
    {
        if ($this->db->tableExists($this->niveisTable)) {
            $this->safeDropForeignKey($this->niveisTable, $this->fkNiveisPacote);
            $this->safeDropIndex($this->niveisTable, $this->uxNiveisPacoteNivel);
            $this->safeDropIndex($this->niveisTable, $this->idxNiveisPacote);
            $this->safeDropIndex($this->niveisTable, $this->idxNiveisAtivo);
            $this->db->query("DROP TABLE {$this->niveisTable}");
        }

        if ($this->db->tableExists($this->pacotesTable)) {
            $this->safeDropForeignKey($this->pacotesTable, $this->fkPacoteServico);
            $this->safeDropIndex($this->pacotesTable, $this->idxPacotesCategoria);
            $this->safeDropIndex($this->pacotesTable, $this->idxPacotesTipoEquip);
            $this->safeDropIndex($this->pacotesTable, $this->idxPacotesAtivo);
            $this->db->query("DROP TABLE {$this->pacotesTable}");
        }
    }

    private function createPacotesTableIfNeeded(): void
    {
        if ($this->db->tableExists($this->pacotesTable)) {
            return;
        }

        $this->db->query(
            "CREATE TABLE {$this->pacotesTable} (
                id INT(11) NOT NULL AUTO_INCREMENT,
                nome VARCHAR(150) NOT NULL,
                categoria VARCHAR(60) NULL,
                tipo_equipamento VARCHAR(100) NULL,
                servico_referencia_id INT(11) NULL,
                descricao TEXT NULL,
                metodologia_origem VARCHAR(120) NULL DEFAULT 'Passo 05 - 3 Pacotes',
                ordem_apresentacao INT(11) NULL DEFAULT 0,
                ativo TINYINT(1) NULL DEFAULT 1,
                created_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
        );
    }

    private function createNiveisTableIfNeeded(): void
    {
        if ($this->db->tableExists($this->niveisTable)) {
            return;
        }

        $this->db->query(
            "CREATE TABLE {$this->niveisTable} (
                id INT(11) NOT NULL AUTO_INCREMENT,
                pacote_servico_id INT(11) NOT NULL,
                nivel VARCHAR(20) NOT NULL,
                nome_exibicao VARCHAR(80) NOT NULL,
                cor_hex VARCHAR(7) NULL,
                preco_min DECIMAL(10,2) NULL DEFAULT 0.00,
                preco_recomendado DECIMAL(10,2) NULL DEFAULT 0.00,
                preco_max DECIMAL(10,2) NULL DEFAULT 0.00,
                prazo_estimado VARCHAR(40) NULL,
                garantia_dias INT(11) NULL DEFAULT 0,
                itens_inclusos TEXT NULL,
                argumento_venda TEXT NULL,
                destaque TINYINT(1) NULL DEFAULT 0,
                ordem TINYINT(2) NULL DEFAULT 0,
                ativo TINYINT(1) NULL DEFAULT 1,
                created_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
        );
    }

    private function seedDefaultPackages(): void
    {
        if (!$this->db->tableExists($this->pacotesTable) || !$this->db->tableExists($this->niveisTable)) {
            return;
        }

        $exists = (int) $this->db->table($this->pacotesTable)->countAllResults();
        if ($exists > 0) {
            return;
        }

        $defaults = $this->defaultPackagesPayload();
        foreach ($defaults as $package) {
            $row = [
                'nome' => $package['nome'],
                'categoria' => $package['categoria'],
                'tipo_equipamento' => $package['tipo_equipamento'],
                'servico_referencia_id' => null,
                'descricao' => $package['descricao'],
                'metodologia_origem' => 'Passo 05 - 3 Pacotes',
                'ordem_apresentacao' => (int) ($package['ordem_apresentacao'] ?? 0),
                'ativo' => 1,
            ];

            $this->db->table($this->pacotesTable)->insert($row);
            $pacoteId = (int) $this->db->insertID();
            if ($pacoteId <= 0) {
                continue;
            }

            foreach ((array) ($package['niveis'] ?? []) as $nivelCode => $nivelData) {
                $this->db->table($this->niveisTable)->insert([
                    'pacote_servico_id' => $pacoteId,
                    'nivel' => $nivelCode,
                    'nome_exibicao' => $nivelData['nome_exibicao'] ?? ucfirst($nivelCode),
                    'cor_hex' => $nivelData['cor_hex'] ?? null,
                    'preco_min' => (float) ($nivelData['preco_min'] ?? 0),
                    'preco_recomendado' => (float) ($nivelData['preco_recomendado'] ?? 0),
                    'preco_max' => (float) ($nivelData['preco_max'] ?? 0),
                    'prazo_estimado' => $nivelData['prazo_estimado'] ?? null,
                    'garantia_dias' => (int) ($nivelData['garantia_dias'] ?? 0),
                    'itens_inclusos' => $nivelData['itens_inclusos'] ?? null,
                    'argumento_venda' => $nivelData['argumento_venda'] ?? null,
                    'destaque' => !empty($nivelData['destaque']) ? 1 : 0,
                    'ordem' => (int) ($nivelData['ordem'] ?? 0),
                    'ativo' => 1,
                ]);
            }
        }
    }

    private function defaultPackagesPayload(): array
    {
        return [
            [
                'nome' => 'Formatacao e Otimizacao de Computador',
                'categoria' => 'computadores',
                'tipo_equipamento' => 'Notebook',
                'descricao' => 'Pacotes base para formatacao, limpeza e ganho de desempenho seguindo a metodologia dos 3 niveis.',
                'ordem_apresentacao' => 10,
                'niveis' => [
                    'basico' => [
                        'nome_exibicao' => 'Basico',
                        'cor_hex' => '#6B7280',
                        'preco_min' => 150,
                        'preco_recomendado' => 165,
                        'preco_max' => 180,
                        'prazo_estimado' => 'ate 48h',
                        'garantia_dias' => 15,
                        'itens_inclusos' => "Instalacao limpa do Windows\nDrivers essenciais\nProgramas basicos\nTeste de funcionamento",
                        'argumento_venda' => 'Resolve o essencial com menor investimento inicial.',
                        'ordem' => 1,
                    ],
                    'completo' => [
                        'nome_exibicao' => 'Completo',
                        'cor_hex' => '#D4AF37',
                        'preco_min' => 200,
                        'preco_recomendado' => 230,
                        'preco_max' => 260,
                        'prazo_estimado' => 'ate 24h',
                        'garantia_dias' => 30,
                        'itens_inclusos' => "Tudo do Basico\nLimpeza interna completa\nTroca de pasta termica\nOtimizacao de inicializacao\nRemocao de virus\nPacote de programas essenciais",
                        'argumento_venda' => 'Pacote principal: melhor custo-beneficio com resultado mais perceptivel.',
                        'destaque' => 1,
                        'ordem' => 2,
                    ],
                    'premium' => [
                        'nome_exibicao' => 'Premium',
                        'cor_hex' => '#7C3AED',
                        'preco_min' => 300,
                        'preco_recomendado' => 340,
                        'preco_max' => 380,
                        'prazo_estimado' => 'prioritario ate 24h',
                        'garantia_dias' => 90,
                        'itens_inclusos' => "Tudo do Completo\nMigracao completa de dados\nAntivirus configurado\nSuporte remoto por 15 dias\nRelatorio do servico",
                        'argumento_venda' => 'Para quem depende do equipamento e quer tranquilidade por mais tempo.',
                        'ordem' => 3,
                    ],
                ],
            ],
            [
                'nome' => 'Upgrade de SSD',
                'categoria' => 'computadores',
                'tipo_equipamento' => 'Notebook',
                'descricao' => 'Estrutura de pacotes para upgrades de armazenamento com foco em desempenho.',
                'ordem_apresentacao' => 20,
                'niveis' => [
                    'basico' => [
                        'nome_exibicao' => 'Basico',
                        'cor_hex' => '#6B7280',
                        'preco_min' => 190,
                        'preco_recomendado' => 205,
                        'preco_max' => 220,
                        'prazo_estimado' => 'ate 48h',
                        'garantia_dias' => 15,
                        'itens_inclusos' => "Substituicao HD por SSD\nCopia basica dos dados\nTeste final",
                        'argumento_venda' => 'Entrada para ganho rapido de desempenho.',
                        'ordem' => 1,
                    ],
                    'completo' => [
                        'nome_exibicao' => 'Completo',
                        'cor_hex' => '#D4AF37',
                        'preco_min' => 270,
                        'preco_recomendado' => 290,
                        'preco_max' => 310,
                        'prazo_estimado' => 'ate 24h',
                        'garantia_dias' => 30,
                        'itens_inclusos' => "Tudo do Basico\nMigracao completa de arquivos e programas\nFormatacao completa\nDrivers e otimizacao",
                        'argumento_venda' => 'Mais pedido para upgrade de SSD com migracao sem dor.',
                        'destaque' => 1,
                        'ordem' => 2,
                    ],
                    'premium' => [
                        'nome_exibicao' => 'Premium',
                        'cor_hex' => '#7C3AED',
                        'preco_min' => 340,
                        'preco_recomendado' => 360,
                        'preco_max' => 380,
                        'prazo_estimado' => 'prioritario ate 24h',
                        'garantia_dias' => 90,
                        'itens_inclusos' => "Tudo do Completo\nLimpeza interna\nPasta termica\nRelatorio de velocidade antes/depois",
                        'argumento_venda' => 'Upgrade completo com ganho comprovado e pos-entrega premium.',
                        'ordem' => 3,
                    ],
                ],
            ],
            [
                'nome' => 'Limpeza Preventiva',
                'categoria' => 'computadores',
                'tipo_equipamento' => 'Notebook',
                'descricao' => 'Pacotes preventivos para reduzir aquecimento e aumentar vida util.',
                'ordem_apresentacao' => 30,
                'niveis' => [
                    'basico' => [
                        'nome_exibicao' => 'Basico',
                        'cor_hex' => '#6B7280',
                        'preco_min' => 80,
                        'preco_recomendado' => 90,
                        'preco_max' => 100,
                        'prazo_estimado' => 'ate 48h',
                        'garantia_dias' => 15,
                        'itens_inclusos' => "Limpeza de poeira\nTroca de pasta termica",
                        'argumento_venda' => 'Manutencao essencial para evitar superaquecimento.',
                        'ordem' => 1,
                    ],
                    'completo' => [
                        'nome_exibicao' => 'Completo',
                        'cor_hex' => '#D4AF37',
                        'preco_min' => 120,
                        'preco_recomendado' => 135,
                        'preco_max' => 150,
                        'prazo_estimado' => 'ate 24h',
                        'garantia_dias' => 30,
                        'itens_inclusos' => "Tudo do Basico\nVerificacao termica em carga\nTeste de estresse",
                        'argumento_venda' => 'Pacote principal para prevencao com validacao tecnica real.',
                        'destaque' => 1,
                        'ordem' => 2,
                    ],
                    'premium' => [
                        'nome_exibicao' => 'Premium',
                        'cor_hex' => '#7C3AED',
                        'preco_min' => 180,
                        'preco_recomendado' => 200,
                        'preco_max' => 220,
                        'prazo_estimado' => 'prioritario ate 24h',
                        'garantia_dias' => 90,
                        'itens_inclusos' => "Tudo do Completo\nFormatacao preventiva\nOtimizacao de inicializacao\nAntivirus configurado",
                        'argumento_venda' => 'Prevencao completa para quem nao quer dor de cabeca recorrente.',
                        'ordem' => 3,
                    ],
                ],
            ],
            [
                'nome' => 'Remocao de Virus',
                'categoria' => 'computadores',
                'tipo_equipamento' => 'Notebook',
                'descricao' => 'Pacotes para descontaminacao e reforco de seguranca.',
                'ordem_apresentacao' => 40,
                'niveis' => [
                    'basico' => [
                        'nome_exibicao' => 'Basico',
                        'cor_hex' => '#6B7280',
                        'preco_min' => 80,
                        'preco_recomendado' => 90,
                        'preco_max' => 100,
                        'prazo_estimado' => 'ate 48h',
                        'garantia_dias' => 15,
                        'itens_inclusos' => "Remocao com Malwarebytes\nReset de navegadores",
                        'argumento_venda' => 'Remocao inicial de contaminacao com menor investimento.',
                        'ordem' => 1,
                    ],
                    'completo' => [
                        'nome_exibicao' => 'Completo',
                        'cor_hex' => '#D4AF37',
                        'preco_min' => 120,
                        'preco_recomendado' => 135,
                        'preco_max' => 150,
                        'prazo_estimado' => 'ate 24h',
                        'garantia_dias' => 30,
                        'itens_inclusos' => "Tudo do Basico\nVarredura com 2 ferramentas\nConfiguracao de seguranca do Windows\nProtecao basica instalada",
                        'argumento_venda' => 'Mais pedido para remover causa raiz e reduzir reincidencia.',
                        'destaque' => 1,
                        'ordem' => 2,
                    ],
                    'premium' => [
                        'nome_exibicao' => 'Premium',
                        'cor_hex' => '#7C3AED',
                        'preco_min' => 180,
                        'preco_recomendado' => 200,
                        'preco_max' => 220,
                        'prazo_estimado' => 'prioritario ate 24h',
                        'garantia_dias' => 90,
                        'itens_inclusos' => "Tudo do Completo\nFormatacao completa\nAntivirus por 1 ano\nGarantia estendida",
                        'argumento_venda' => 'Para quem quer eliminacao total e seguranca de longo prazo.',
                        'ordem' => 3,
                    ],
                ],
            ],
            [
                'nome' => 'Troca de Tela (Celular)',
                'categoria' => 'celulares',
                'tipo_equipamento' => 'Smartphone',
                'descricao' => 'Pacotes de troca de tela. Ajustar valor final conforme marca e modelo.',
                'ordem_apresentacao' => 50,
                'niveis' => [
                    'basico' => [
                        'nome_exibicao' => 'Basico',
                        'cor_hex' => '#6B7280',
                        'preco_min' => 120,
                        'preco_recomendado' => 220,
                        'preco_max' => 320,
                        'prazo_estimado' => '1h a 2h',
                        'garantia_dias' => 15,
                        'itens_inclusos' => "Troca simples da tela\nTeste de toque\nTeste de camera frontal e sensores\nLimpeza externa",
                        'argumento_venda' => 'Troca funcional para resolver tela quebrada com rapidez.',
                        'ordem' => 1,
                    ],
                    'completo' => [
                        'nome_exibicao' => 'Completo',
                        'cor_hex' => '#D4AF37',
                        'preco_min' => 170,
                        'preco_recomendado' => 300,
                        'preco_max' => 400,
                        'prazo_estimado' => '1h a 2h',
                        'garantia_dias' => 30,
                        'itens_inclusos' => "Tudo do Basico\nLimpeza interna\nVerificacao de bateria\nRevisao de conectores\nTeste completo de audio/camera/sensores",
                        'argumento_venda' => 'Pacote principal de tela com revisao tecnica completa do aparelho.',
                        'destaque' => 1,
                        'ordem' => 2,
                    ],
                    'premium' => [
                        'nome_exibicao' => 'Premium',
                        'cor_hex' => '#7C3AED',
                        'preco_min' => 240,
                        'preco_recomendado' => 380,
                        'preco_max' => 490,
                        'prazo_estimado' => 'prioritario',
                        'garantia_dias' => 60,
                        'itens_inclusos' => "Tudo do Completo\nPelicula instalada\nDiagnostico completo\nRelatorio fotografico antes/depois\nEntrega prioritaria",
                        'argumento_venda' => 'Maxima protecao para quem quer tranquilidade apos a troca.',
                        'ordem' => 3,
                    ],
                ],
            ],
            [
                'nome' => 'Troca de Bateria (Celular)',
                'categoria' => 'celulares',
                'tipo_equipamento' => 'Smartphone',
                'descricao' => 'Pacotes para substituicao de bateria com revisao progressiva.',
                'ordem_apresentacao' => 60,
                'niveis' => [
                    'basico' => [
                        'nome_exibicao' => 'Basico',
                        'cor_hex' => '#6B7280',
                        'preco_min' => 90,
                        'preco_recomendado' => 120,
                        'preco_max' => 170,
                        'prazo_estimado' => 'ate 24h',
                        'garantia_dias' => 15,
                        'itens_inclusos' => "Troca da bateria\nTeste de carga completa",
                        'argumento_venda' => 'Resolucao objetiva para autonomia baixa.',
                        'ordem' => 1,
                    ],
                    'completo' => [
                        'nome_exibicao' => 'Completo',
                        'cor_hex' => '#D4AF37',
                        'preco_min' => 140,
                        'preco_recomendado' => 165,
                        'preco_max' => 190,
                        'prazo_estimado' => 'ate 24h',
                        'garantia_dias' => 30,
                        'itens_inclusos' => "Tudo do Basico\nVerificacao de tela e conectores\nTeste de duracao\nLimpeza interna",
                        'argumento_venda' => 'Pacote principal com validacao tecnica para evitar retorno.',
                        'destaque' => 1,
                        'ordem' => 2,
                    ],
                    'premium' => [
                        'nome_exibicao' => 'Premium',
                        'cor_hex' => '#7C3AED',
                        'preco_min' => 185,
                        'preco_recomendado' => 215,
                        'preco_max' => 245,
                        'prazo_estimado' => 'prioritario',
                        'garantia_dias' => 60,
                        'itens_inclusos' => "Tudo do Completo\nDiagnostico completo\nRelatorio tecnico de entrega",
                        'argumento_venda' => 'Para cliente que quer confianca maxima apos a troca.',
                        'ordem' => 3,
                    ],
                ],
            ],
            [
                'nome' => 'Conector de Carga (Celular)',
                'categoria' => 'celulares',
                'tipo_equipamento' => 'Smartphone',
                'descricao' => 'Pacotes para reparo de conector USB-C/Lightning com garantia progressiva.',
                'ordem_apresentacao' => 70,
                'niveis' => [
                    'basico' => [
                        'nome_exibicao' => 'Basico',
                        'cor_hex' => '#6B7280',
                        'preco_min' => 100,
                        'preco_recomendado' => 120,
                        'preco_max' => 130,
                        'prazo_estimado' => 'ate 24h',
                        'garantia_dias' => 15,
                        'itens_inclusos' => "Troca do conector\nTeste de carga e dados",
                        'argumento_venda' => 'Reparo direto do ponto de carga com custo de entrada.',
                        'ordem' => 1,
                    ],
                    'completo' => [
                        'nome_exibicao' => 'Completo',
                        'cor_hex' => '#D4AF37',
                        'preco_min' => 140,
                        'preco_recomendado' => 160,
                        'preco_max' => 175,
                        'prazo_estimado' => 'ate 24h',
                        'garantia_dias' => 30,
                        'itens_inclusos' => "Tudo do Basico\nRevisao de conectores internos\nLimpeza tecnica da placa de carga",
                        'argumento_venda' => 'Pacote principal com maior seguranca eletrica e menor risco de retorno.',
                        'destaque' => 1,
                        'ordem' => 2,
                    ],
                    'premium' => [
                        'nome_exibicao' => 'Premium',
                        'cor_hex' => '#7C3AED',
                        'preco_min' => 185,
                        'preco_recomendado' => 205,
                        'preco_max' => 225,
                        'prazo_estimado' => 'prioritario',
                        'garantia_dias' => 60,
                        'itens_inclusos' => "Tudo do Completo\nDiagnostico completo\nRelatorio final de carga e estabilidade",
                        'argumento_venda' => 'Para cliente que precisa de confiabilidade maxima de carga no uso diario.',
                        'ordem' => 3,
                    ],
                ],
            ],
        ];
    }

    private function safeCreateIndex(string $table, string $indexName, string $columnsSql, bool $unique = false): void
    {
        $prefix = $unique ? 'CREATE UNIQUE INDEX' : 'CREATE INDEX';
        try {
            $this->db->query("{$prefix} {$indexName} ON {$table} {$columnsSql}");
        } catch (\Throwable $e) {
            // Index already exists.
        }
    }

    private function safeDropIndex(string $table, string $indexName): void
    {
        try {
            $this->db->query("DROP INDEX {$indexName} ON {$table}");
        } catch (\Throwable $e) {
            // Index not found.
        }
    }

    private function safeAddForeignKey(
        string $table,
        string $constraintName,
        string $column,
        string $referenceTable,
        string $referenceColumn,
        string $onDelete = 'CASCADE',
        string $onUpdate = 'CASCADE'
    ): void {
        if ($this->foreignKeyExists($table, $constraintName)) {
            return;
        }

        try {
            $this->db->query(
                "ALTER TABLE {$table}
                 ADD CONSTRAINT {$constraintName}
                 FOREIGN KEY ({$column})
                 REFERENCES {$referenceTable} ({$referenceColumn})
                 ON DELETE {$onDelete}
                 ON UPDATE {$onUpdate}"
            );
        } catch (\Throwable $e) {
            // FK already exists or dependency unavailable.
        }
    }

    private function safeDropForeignKey(string $table, string $constraintName): void
    {
        if (!$this->foreignKeyExists($table, $constraintName)) {
            return;
        }

        try {
            $this->db->query("ALTER TABLE {$table} DROP FOREIGN KEY {$constraintName}");
        } catch (\Throwable $e) {
            // FK not found.
        }
    }

    private function foreignKeyExists(string $table, string $constraintName): bool
    {
        $row = $this->db->table('information_schema.TABLE_CONSTRAINTS')
            ->select('CONSTRAINT_NAME')
            ->where('TABLE_SCHEMA', $this->db->database)
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $constraintName)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->get()
            ->getRowArray();

        return !empty($row);
    }
}

