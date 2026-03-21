<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddContatoEngajamentoLifecycleWindow extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('contatos')) {
            return;
        }

        $this->addContatoEngajamentoColumns();
        $this->backfillContatoEngajamento();
        $this->ensureContatoEngajamentoIndex();
        $this->ensureDefaultConfig();
    }

    public function down()
    {
        if ($this->db->tableExists('contatos')) {
            try {
                $this->db->query('DROP INDEX idx_contatos_engajamento_status ON contatos');
            } catch (\Throwable $e) {
                // Indice inexistente.
            }

            if ($this->db->fieldExists('engajamento_recalculado_em', 'contatos')) {
                $this->db->query('ALTER TABLE contatos DROP COLUMN engajamento_recalculado_em');
            }
            if ($this->db->fieldExists('engajamento_status', 'contatos')) {
                $this->db->query("ALTER TABLE contatos DROP COLUMN engajamento_status");
            }
        }

        if ($this->db->tableExists('configuracoes')) {
            $this->db->table('configuracoes')
                ->whereIn('chave', [
                    'crm_engajamento_ativo_dias',
                    'crm_engajamento_risco_dias',
                ])
                ->delete();
        }
    }

    private function addContatoEngajamentoColumns(): void
    {
        if (!$this->db->fieldExists('engajamento_status', 'contatos')) {
            $afterField = $this->db->fieldExists('status_relacionamento', 'contatos')
                ? 'status_relacionamento'
                : 'origem';
            $this->db->query(
                "ALTER TABLE contatos ADD COLUMN engajamento_status VARCHAR(20) NOT NULL DEFAULT 'ativo' AFTER {$afterField}"
            );
        }

        if (!$this->db->fieldExists('engajamento_recalculado_em', 'contatos')) {
            $this->db->query('ALTER TABLE contatos ADD COLUMN engajamento_recalculado_em DATETIME NULL AFTER engajamento_status');
        }
    }

    private function backfillContatoEngajamento(): void
    {
        $this->db->query("
            UPDATE contatos
               SET engajamento_status = CASE
                    WHEN COALESCE(ultimo_contato_em, updated_at, created_at) IS NULL THEN 'ativo'
                    WHEN TIMESTAMPDIFF(DAY, COALESCE(ultimo_contato_em, updated_at, created_at), NOW()) <= 30 THEN 'ativo'
                    WHEN TIMESTAMPDIFF(DAY, COALESCE(ultimo_contato_em, updated_at, created_at), NOW()) <= 90 THEN 'em_risco'
                    ELSE 'inativo'
                END
             WHERE engajamento_status IS NULL
                OR TRIM(engajamento_status) = ''
                OR engajamento_status NOT IN ('ativo', 'em_risco', 'inativo')
        ");

        $this->db->query("
            UPDATE contatos
               SET engajamento_recalculado_em = COALESCE(engajamento_recalculado_em, NOW())
             WHERE engajamento_recalculado_em IS NULL
        ");
    }

    private function ensureContatoEngajamentoIndex(): void
    {
        try {
            $this->db->query('CREATE INDEX idx_contatos_engajamento_status ON contatos (engajamento_status)');
        } catch (\Throwable $e) {
            // Indice ja existente.
        }
    }

    private function ensureDefaultConfig(): void
    {
        if (!$this->db->tableExists('configuracoes')) {
            return;
        }

        $this->upsertConfig('crm_engajamento_ativo_dias', '30', 'numero');
        $this->upsertConfig('crm_engajamento_risco_dias', '90', 'numero');
    }

    private function upsertConfig(string $chave, string $valor, string $tipo): void
    {
        $row = $this->db->table('configuracoes')->where('chave', $chave)->get()->getRowArray();
        if ($row) {
            $this->db->table('configuracoes')
                ->where('id', (int) $row['id'])
                ->update([
                    'valor' => $valor,
                    'tipo' => $tipo,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            return;
        }

        $nãow = date('Y-m-d H:i:s');
        $this->db->table('configuracoes')->insert([
            'chave' => $chave,
            'valor' => $valor,
            'tipo' => $tipo,
            'created_at' => $nãow,
            'updated_at' => $nãow,
        ]);
    }
}

