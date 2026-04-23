<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePacotesOfertasDinamicas extends Migration
{
    private string $table = 'pacotes_ofertas';
    private string $uxToken = 'ux_pacotes_ofertas_token';
    private string $idxStatusExpira = 'idx_pacotes_ofertas_status_expira';
    private string $idxClienteStatus = 'idx_pacotes_ofertas_cliente_status';
    private string $idxContatoStatus = 'idx_pacotes_ofertas_contato_status';
    private string $idxTelefoneStatus = 'idx_pacotes_ofertas_telefone_status';
    private string $idxOrcamento = 'idx_pacotes_ofertas_orcamento';
    private string $idxPacote = 'idx_pacotes_ofertas_pacote';
    private string $idxItem = 'idx_pacotes_ofertas_orc_item';
    private string $fkPacote = 'fk_pacotes_ofertas_pacote';
    private string $fkCliente = 'fk_pacotes_ofertas_cliente';
    private string $fkContato = 'fk_pacotes_ofertas_contato';
    private string $fkOrcamento = 'fk_pacotes_ofertas_orcamento';
    private string $fkOrcItem = 'fk_pacotes_ofertas_orc_item';
    private string $fkOs = 'fk_pacotes_ofertas_os';
    private string $fkEquipamento = 'fk_pacotes_ofertas_equip';

    public function up()
    {
        if (!$this->db->tableExists($this->table)) {
            $this->db->query(
                "CREATE TABLE {$this->table} (
                    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                    pacote_servico_id INT(11) NULL,
                    cliente_id INT(11) NULL,
                    contato_id BIGINT(20) UNSIGNED NULL,
                    telefone_destino VARCHAR(20) NULL,
                    os_id INT(11) NULL,
                    equipamento_id INT(11) NULL,
                    origem_contexto VARCHAR(30) NULL DEFAULT 'manual',
                    token_publico VARCHAR(90) NOT NULL,
                    status VARCHAR(30) NOT NULL DEFAULT 'ativo',
                    destino_canal VARCHAR(20) NULL,
                    mensagem_enviada TEXT NULL,
                    expira_em DATETIME NULL,
                    enviado_em DATETIME NULL,
                    visualizado_em DATETIME NULL,
                    escolhido_em DATETIME NULL,
                    aplicado_em DATETIME NULL,
                    nivel_escolhido VARCHAR(20) NULL,
                    nivel_nome_exibicao VARCHAR(80) NULL,
                    valor_escolhido DECIMAL(12,2) NULL DEFAULT NULL,
                    garantia_dias INT(11) NULL DEFAULT NULL,
                    prazo_estimado VARCHAR(40) NULL,
                    itens_inclusos TEXT NULL,
                    argumento_venda TEXT NULL,
                    orcamento_id BIGINT(20) UNSIGNED NULL,
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
        $this->safeCreateIndex($this->table, $this->idxStatusExpira, '(status, expira_em)');
        $this->safeCreateIndex($this->table, $this->idxClienteStatus, '(cliente_id, status)');
        $this->safeCreateIndex($this->table, $this->idxContatoStatus, '(contato_id, status)');
        $this->safeCreateIndex($this->table, $this->idxTelefoneStatus, '(telefone_destino, status)');
        $this->safeCreateIndex($this->table, $this->idxOrcamento, '(orcamento_id)');
        $this->safeCreateIndex($this->table, $this->idxPacote, '(pacote_servico_id)');
        $this->safeCreateIndex($this->table, $this->idxItem, '(orcamento_item_id)');

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

        if ($this->db->tableExists('clientes')) {
            $this->safeAddForeignKey(
                $this->table,
                $this->fkCliente,
                'cliente_id',
                'clientes',
                'id',
                'SET NULL',
                'CASCADE'
            );
        }

        if ($this->db->tableExists('contatos')) {
            $this->safeAddForeignKey(
                $this->table,
                $this->fkContato,
                'contato_id',
                'contatos',
                'id',
                'SET NULL',
                'CASCADE'
            );
        }

        if ($this->db->tableExists('orcamentos')) {
            $this->safeAddForeignKey(
                $this->table,
                $this->fkOrcamento,
                'orcamento_id',
                'orcamentos',
                'id',
                'SET NULL',
                'CASCADE'
            );
        }

        if ($this->db->tableExists('orcamento_itens')) {
            $this->safeAddForeignKey(
                $this->table,
                $this->fkOrcItem,
                'orcamento_item_id',
                'orcamento_itens',
                'id',
                'SET NULL',
                'CASCADE'
            );
        }

        if ($this->db->tableExists('os')) {
            $this->safeAddForeignKey(
                $this->table,
                $this->fkOs,
                'os_id',
                'os',
                'id',
                'SET NULL',
                'CASCADE'
            );
        }

        if ($this->db->tableExists('equipamentos')) {
            $this->safeAddForeignKey(
                $this->table,
                $this->fkEquipamento,
                'equipamento_id',
                'equipamentos',
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

        $this->safeDropForeignKey($this->table, $this->fkEquipamento);
        $this->safeDropForeignKey($this->table, $this->fkOs);
        $this->safeDropForeignKey($this->table, $this->fkOrcItem);
        $this->safeDropForeignKey($this->table, $this->fkOrcamento);
        $this->safeDropForeignKey($this->table, $this->fkContato);
        $this->safeDropForeignKey($this->table, $this->fkCliente);
        $this->safeDropForeignKey($this->table, $this->fkPacote);

        $this->safeDropIndex($this->table, $this->uxToken);
        $this->safeDropIndex($this->table, $this->idxStatusExpira);
        $this->safeDropIndex($this->table, $this->idxClienteStatus);
        $this->safeDropIndex($this->table, $this->idxContatoStatus);
        $this->safeDropIndex($this->table, $this->idxTelefoneStatus);
        $this->safeDropIndex($this->table, $this->idxOrcamento);
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
            // Index ja existe.
        }
    }

    private function safeDropIndex(string $table, string $indexName): void
    {
        try {
            $this->db->query("DROP INDEX {$indexName} ON {$table}");
        } catch (\Throwable $e) {
            // Index inexistente.
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
            // FK indisponivel no ambiente atual.
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
            // FK inexistente.
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

