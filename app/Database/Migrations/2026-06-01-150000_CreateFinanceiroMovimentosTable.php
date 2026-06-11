<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFinanceiroMovimentosTable extends Migration
{
    public function up()
    {
        $this->ensureStatusSupportsParcial();
        $this->createMovimentosTable();
        $this->backfillPaidTitles();
    }

    public function down()
    {
        if ($this->db->tableExists('financeiro_movimentos')) {
            $this->forge->dropTable('financeiro_movimentos', true);
        }
    }

    private function ensureStatusSupportsParcial(): void
    {
        if (! $this->db->tableExists('financeiro')) {
            return;
        }

        $column = $this->db->query("SHOW COLUMNS FROM financeiro LIKE 'status'")->getRowArray();
        $type = strtolower((string) ($column['Type'] ?? ''));

        if ($type === '' || str_contains($type, "'parcial'")) {
            return;
        }

        $this->db->query("ALTER TABLE financeiro MODIFY status ENUM('pendente','parcial','pago','cancelado') NOT NULL DEFAULT 'pendente'");
    }

    private function createMovimentosTable(): void
    {
        if ($this->db->tableExists('financeiro_movimentos')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'financeiro_id' => [
                'type' => 'INT',
            ],
            'tipo_movimento' => [
                'type' => 'ENUM',
                'constraint' => ['entrada', 'saida', 'estorno', 'transferencia'],
                'default' => 'entrada',
            ],
            'data_movimento' => [
                'type' => 'DATE',
            ],
            'valor_movimento' => [
                'type' => 'DECIMAL',
                'constraint' => '12,2',
                'default' => 0,
            ],
            'forma_pagamento' => [
                'type' => 'VARCHAR',
                'constraint' => 40,
                'null' => true,
            ],
            'documento_ref' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ],
            'observacoes' => [
                'type' => 'TEXT',
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
        $this->forge->addKey('financeiro_id');
        $this->forge->addKey('data_movimento');
        $this->forge->addForeignKey('financeiro_id', 'financeiro', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('financeiro_movimentos', true);
    }

    private function backfillPaidTitles(): void
    {
        if (! $this->db->tableExists('financeiro') || ! $this->db->tableExists('financeiro_movimentos')) {
            return;
        }

        $rows = $this->db->table('financeiro f')
            ->select('f.id, f.tipo, f.valor, f.data_pagamento, f.forma_pagamento, f.observacoes')
            ->join('financeiro_movimentos fm', 'fm.financeiro_id = f.id', 'left')
            ->where('f.status', 'pago')
            ->where('fm.id IS NULL', null, false)
            ->get()
            ->getResultArray();

        if ($rows === []) {
            return;
        }

        $now = date('Y-m-d H:i:s');

        foreach ($rows as $row) {
            $this->db->table('financeiro_movimentos')->insert([
                'financeiro_id' => (int) ($row['id'] ?? 0),
                'tipo_movimento' => (($row['tipo'] ?? '') === 'receber') ? 'entrada' : 'saida',
                'data_movimento' => ! empty($row['data_pagamento']) ? $row['data_pagamento'] : date('Y-m-d'),
                'valor_movimento' => round((float) ($row['valor'] ?? 0), 2),
                'forma_pagamento' => $row['forma_pagamento'] ?? null,
                'observacoes' => $row['observacoes'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
