<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddContatoLifecycleMarketingFields extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('contatos')) {
            return;
        }

        if (!$this->db->fieldExists('status_relacionamento', 'contatos')) {
            $this->db->query("ALTER TABLE contatos ADD COLUMN status_relacionamento VARCHAR(30) NOT NULL DEFAULT 'lead_novo' AFTER origem");
        }

        if (!$this->db->fieldExists('qualificado_em', 'contatos')) {
            $this->db->query('ALTER TABLE contatos ADD COLUMN qualificado_em DATETIME NULL AFTER status_relacionamento');
        }

        if (!$this->db->fieldExists('convertido_em', 'contatos')) {
            $this->db->query('ALTER TABLE contatos ADD COLUMN convertido_em DATETIME NULL AFTER qualificado_em');
        }

        // Backfill inicial para manter consistencia do funil.
        $this->db->query("
            UPDATE contatos
               SET status_relacionamento = CASE
                    WHEN cliente_id IS NOT NULL AND cliente_id > 0 THEN 'cliente_convertido'
                    WHEN (
                        (nome IS NOT NULL AND TRIM(nome) <> '' AND LOCATE(' ', TRIM(nome)) > 0)
                        OR (whatsapp_nome_perfil IS NOT NULL AND TRIM(whatsapp_nome_perfil) <> '' AND LOCATE(' ', TRIM(whatsapp_nome_perfil)) > 0)
                    ) THEN 'lead_qualificado'
                    ELSE 'lead_novo'
                END
             WHERE status_relacionamento IS NULL
                OR TRIM(status_relacionamento) = ''
                OR status_relacionamento NOT IN ('lead_novo', 'lead_qualificado', 'cliente_convertido')
        ");

        $this->db->query("
            UPDATE contatos
               SET qualificado_em = COALESCE(qualificado_em, updated_at, created_at, NOW())
             WHERE status_relacionamento = 'lead_qualificado'
               AND qualificado_em IS NULL
        ");

        $this->db->query("
            UPDATE contatos
               SET convertido_em = COALESCE(convertido_em, updated_at, created_at, NOW())
             WHERE status_relacionamento = 'cliente_convertido'
               AND convertido_em IS NULL
        ");

        try {
            $this->db->query('CREATE INDEX idx_contatos_status_relacionamento ON contatos (status_relacionamento)');
        } catch (\Throwable $e) {
            // Indice ja existente.
        }
    }

    public function down()
    {
        if (!$this->db->tableExists('contatos')) {
            return;
        }

        try {
            $this->db->query('DROP INDEX idx_contatos_status_relacionamento ON contatos');
        } catch (\Throwable $e) {
            // Indice inexistente.
        }

        if ($this->db->fieldExists('convertido_em', 'contatos')) {
            $this->db->query('ALTER TABLE contatos DROP COLUMN convertido_em');
        }
        if ($this->db->fieldExists('qualificado_em', 'contatos')) {
            $this->db->query('ALTER TABLE contatos DROP COLUMN qualificado_em');
        }
        if ($this->db->fieldExists('status_relacionamento', 'contatos')) {
            $this->db->query('ALTER TABLE contatos DROP COLUMN status_relacionamento');
        }
    }
}

