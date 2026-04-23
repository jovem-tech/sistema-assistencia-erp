<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateOrcamentoPacoteLinks extends Migration
{
    private string $table = 'orcamento_pacote_links';
    private string $fkOrcamento = 'fk_orc_pacote_link_orcamento';
    private string $fkPacote = 'fk_orc_pacote_link_pacote';
    private string $fkItem = 'fk_orc_pacote_link_item';
    private string $uxToken = 'ux_orc_pacote_link_token';
    private string $idxOrcamentoStatus = 'idx_orc_pacote_link_orcamento_status';
    private string $idxStatusExpira = 'idx_orc_pacote_link_status_expira';
    private string $idxPacote = 'idx_orc_pacote_link_pacote';
    private string $idxItem = 'idx_orc_pacote_link_item';

    public function up()
    {
        if (!$this->db->tableExists($this->table)) {
            $this->db->query(
                "CREATE TABLE {$this->table} (
                    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                    orcamento_id BIGINT(20) UNSIGNED NOT NULL,
                    pacote_servico_id INT(11) NULL,
                    token_publico VARCHAR(90) NOT NULL,
                    status VARCHAR(30) NOT NULL DEFAULT 'ativo',
                    destino_canal VARCHAR(20) NULL,
                    destino_contato VARCHAR(160) NULL,
                    mensagem_enviada TEXT NULL,
                    expira_em DATETIME NULL,
                    enviado_em DATETIME NULL,
                    escolhido_em DATETIME NULL,
                    nivel_escolhido VARCHAR(20) NULL,
                    nivel_nome_exibicao VARCHAR(80) NULL,
                    valor_escolhido DECIMAL(12,2) NULL DEFAULT NULL,
                    garantia_dias INT(11) NULL DEFAULT NULL,
                    prazo_estimado VARCHAR(40) NULL,
                    itens_inclusos TEXT NULL,
                    argumento_venda TEXT NULL,
                    orcamento_item_id BIGINT(20) UNSIGNED NULL,
                    ip_escolha VARCHAR(45) NULL,
                    user_agent_escolha VARCHAR(255) NULL,
                    created_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
            );
        }

        $this->safeCreateIndex($this->table, $this->uxToken, '(token_publico)', true);
        $this->safeCreateIndex($this->table, $this->idxOrcamentoStatus, '(orcamento_id, status)');
        $this->safeCreateIndex($this->table, $this->idxStatusExpira, '(status, expira_em)');
        $this->safeCreateIndex($this->table, $this->idxPacote, '(pacote_servico_id)');
        $this->safeCreateIndex($this->table, $this->idxItem, '(orcamento_item_id)');

        if ($this->db->tableExists('orcamentos')) {
            $this->safeAddForeignKey(
                $this->table,
                $this->fkOrcamento,
                'orcamento_id',
                'orcamentos',
                'id',
                'CASCADE',
                'CASCADE'
            );
        }

        if ($this->db->tableExists('pacotes_servicos')) {
            $this->safeAddForeignKey(
                $this->table,
                $this->fkPacote,
                'pacote_servico_id',
                'pacotes_servicos',
                'id',
                'SET NULL',
                'CASCADE'
            );
        }

        if ($this->db->tableExists('orcamento_itens')) {
            $this->safeAddForeignKey(
                $this->table,
                $this->fkItem,
                'orcamento_item_id',
                'orcamento_itens',
                'id',
                'SET NULL',
                'CASCADE'
            );
        }
    }

    public function down()
    {
        if (!$this->db->tableExists($this->table)) {
            return;
        }

        $this->safeDropForeignKey($this->table, $this->fkItem);
        $this->safeDropForeignKey($this->table, $this->fkPacote);
        $this->safeDropForeignKey($this->table, $this->fkOrcamento);

        $this->safeDropIndex($this->table, $this->uxToken);
        $this->safeDropIndex($this->table, $this->idxOrcamentoStatus);
        $this->safeDropIndex($this->table, $this->idxStatusExpira);
        $this->safeDropIndex($this->table, $this->idxPacote);
        $this->safeDropIndex($this->table, $this->idxItem);

        $this->db->query("DROP TABLE {$this->table}");
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
            // Foreign key already exists or dependency unavailable.
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
            // Foreign key not found.
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

