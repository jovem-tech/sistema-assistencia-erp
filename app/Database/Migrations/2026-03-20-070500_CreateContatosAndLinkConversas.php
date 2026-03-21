<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateContatosAndLinkConversas extends Migration
{
    public function up()
    {
        $this->createContatosTable();
        $this->patchConversasTable();
        $this->backfillContatosFromConversas();
    }

    public function down()
    {
        if ($this->db->tableExists('conversas_whatsapp') && $this->db->fieldExists('contato_id', 'conversas_whatsapp')) {
            $this->db->query('ALTER TABLE conversas_whatsapp DROP COLUMN contato_id');
        }

        $this->forge->dropTable('contatos', true);
    }

    private function createContatosTable(): void
    {
        if ($this->db->tableExists('contatos')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'cliente_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'nome' => [
                'type' => 'VARCHAR',
                'constraint' => 150,
                'null' => true,
            ],
            'telefone' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'null' => false,
            ],
            'telefone_normalizado' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => false,
            ],
            'email' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
                'null' => true,
            ],
            'whatsapp_nome_perfil' => [
                'type' => 'VARCHAR',
                'constraint' => 140,
                'null' => true,
            ],
            'origem' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'default' => 'whatsapp',
            ],
            'observacoes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'ultimo_contato_em' => [
                'type' => 'DATETIME',
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
        $this->forge->addUniqueKey('telefone_normalizado');
        $this->forge->addKey('cliente_id');
        $this->forge->addKey(['origem', 'ultimo_contato_em']);
        $this->forge->createTable('contatos', true);
    }

    private function patchConversasTable(): void
    {
        if (!$this->db->tableExists('conversas_whatsapp')) {
            return;
        }

        if (!$this->db->fieldExists('contato_id', 'conversas_whatsapp')) {
            $this->db->query('ALTER TABLE conversas_whatsapp ADD COLUMN contato_id BIGINT(20) UNSIGNED NULL AFTER cliente_id');
        }

        try {
            $this->db->query('CREATE INDEX idx_conversas_whatsapp_contato ON conversas_whatsapp (contato_id)');
        } catch (\Throwable $e) {
            // ignora indice existente
        }
    }

    private function backfillContatosFromConversas(): void
    {
        if (
            !$this->db->tableExists('contatos')
            || !$this->db->tableExists('conversas_whatsapp')
        ) {
            return;
        }

        $rows = $this->db->table('conversas_whatsapp')
            ->select('id, cliente_id, telefone, nome_contato, created_at, updated_at, ultima_mensagem_em')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        if (empty($rows)) {
            return;
        }

        foreach ($rows as $row) {
            $telefone = trim((string) ($row['telefone'] ?? ''));
            $normalizado = $this->normalizePhone($telefone);
            if ($normalizado === '') {
                continue;
            }

            $nomeContato = trim((string) ($row['nome_contato'] ?? ''));
            if ($this->isLikelyPhoneValue($nomeContato)) {
                $nomeContato = '';
            }

            $contato = $this->db->table('contatos')
                ->where('telefone_normalizado', $normalizado)
                ->get()
                ->getRowArray();

            if (!$contato) {
                $this->db->table('contatos')->insert([
                    'cliente_id' => (int) ($row['cliente_id'] ?? 0) ?: null,
                    'nome' => $nomeContato !== '' ? $nomeContato : null,
                    'telefone' => $telefone !== '' ? $telefone : $normalizado,
                    'telefone_normalizado' => $normalizado,
                    'whatsapp_nome_perfil' => $nomeContato !== '' ? $nomeContato : null,
                    'origem' => 'whatsapp',
                    'ultimo_contato_em' => $row['ultima_mensagem_em'] ?? null,
                    'created_at' => $row['created_at'] ?? date('Y-m-d H:i:s'),
                    'updated_at' => $row['updated_at'] ?? date('Y-m-d H:i:s'),
                ]);

                $contatoId = (int) $this->db->insertID();
            } else {
                $contatoId = (int) ($contato['id'] ?? 0);
                $update = [];

                if ((int) ($contato['cliente_id'] ?? 0) <= 0 && (int) ($row['cliente_id'] ?? 0) > 0) {
                    $update['cliente_id'] = (int) $row['cliente_id'];
                }
                if (empty($contato['nome']) && $nomeContato !== '') {
                    $update['nome'] = $nomeContato;
                }
                if (empty($contato['whatsapp_nome_perfil']) && $nomeContato !== '') {
                    $update['whatsapp_nome_perfil'] = $nomeContato;
                }
                if (empty($contato['telefone']) && $telefone !== '') {
                    $update['telefone'] = $telefone;
                }
                if (!empty($row['ultima_mensagem_em'])) {
                    $update['ultimo_contato_em'] = $row['ultima_mensagem_em'];
                }
                if (!empty($update)) {
                    $update['updated_at'] = date('Y-m-d H:i:s');
                    $this->db->table('contatos')->where('id', $contatoId)->update($update);
                }
            }

            if ($contatoId > 0 && (int) ($row['id'] ?? 0) > 0) {
                $this->db->table('conversas_whatsapp')
                    ->where('id', (int) $row['id'])
                    ->update([
                        'contato_id' => $contatoId,
                    ]);
            }
        }
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?? '';
    }

    private function isLikelyPhoneValue(string $value): bool
    {
        $digits = $this->normalizePhone($value);
        if ($digits === '') {
            return false;
        }
        return strlen($digits) >= 8 && strlen(str_replace(['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'], '', $value)) <= 3;
    }
}

