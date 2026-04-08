<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateOrcamentosModuleInfrastructure extends Migration
{
    public function up()
    {
        $db = $this->db;

        if (!$db->tableExists('orcamentos')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'BIGINT',
                    'constraint'     => 20,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'numero' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 40,
                    'null'       => true,
                ],
                'versao' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'default'    => 1,
                ],
                'status' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 40,
                    'default'    => 'rascunho',
                ],
                'origem' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 40,
                    'default'    => 'manual',
                ],
                'cliente_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => true,
                ],
                'cliente_nome_avulso' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 160,
                    'null'       => true,
                ],
                'telefone_contato' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 30,
                    'null'       => true,
                ],
                'email_contato' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 120,
                    'null'       => true,
                ],
                'os_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => true,
                ],
                'equipamento_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => true,
                ],
                'conversa_id' => [
                    'type'       => 'BIGINT',
                    'constraint' => 20,
                    'null'       => true,
                ],
                'responsavel_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => true,
                ],
                'criado_por' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => true,
                ],
                'atualizado_por' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => true,
                ],
                'titulo' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 180,
                    'null'       => true,
                ],
                'validade_dias' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'default'    => 7,
                ],
                'validade_data' => [
                    'type' => 'DATE',
                    'null' => true,
                ],
                'subtotal' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '12,2',
                    'default'    => 0.00,
                ],
                'desconto' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '12,2',
                    'default'    => 0.00,
                ],
                'acrescimo' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '12,2',
                    'default'    => 0.00,
                ],
                'total' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '12,2',
                    'default'    => 0.00,
                ],
                'prazo_execucao' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 120,
                    'null'       => true,
                ],
                'observacoes' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'condicoes' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'token_publico' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 80,
                    'null'       => true,
                ],
                'token_expira_em' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'enviado_em' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'aprovado_em' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'rejeitado_em' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'cancelado_em' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'motivo_rejeicao' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'convertido_tipo' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 30,
                    'null'       => true,
                ],
                'convertido_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => true,
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
            $this->forge->addUniqueKey('numero');
            $this->forge->addUniqueKey('token_publico');
            $this->forge->addKey(['status', 'validade_data']);
            $this->forge->addKey(['cliente_id', 'created_at']);
            $this->forge->addKey(['os_id', 'created_at']);
            $this->forge->addKey(['conversa_id', 'created_at']);
            $this->forge->addForeignKey('cliente_id', 'clientes', 'id', 'SET NULL', 'CASCADE');
            $this->forge->addForeignKey('os_id', 'os', 'id', 'SET NULL', 'CASCADE');
            $this->forge->addForeignKey('equipamento_id', 'equipamentos', 'id', 'SET NULL', 'CASCADE');
            $this->forge->addForeignKey('responsavel_id', 'usuarios', 'id', 'SET NULL', 'CASCADE');
            $this->forge->addForeignKey('criado_por', 'usuarios', 'id', 'SET NULL', 'CASCADE');
            $this->forge->addForeignKey('atualizado_por', 'usuarios', 'id', 'SET NULL', 'CASCADE');
            $this->forge->createTable('orcamentos', true);
        }

        if (!$db->tableExists('orcamento_itens')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'BIGINT',
                    'constraint'     => 20,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'orcamento_id' => [
                    'type'       => 'BIGINT',
                    'constraint' => 20,
                    'unsigned'   => true,
                ],
                'tipo_item' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 30,
                    'default'    => 'servico',
                ],
                'referencia_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => true,
                ],
                'descricao' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => false,
                ],
                'quantidade' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,2',
                    'default'    => 1.00,
                ],
                'valor_unitario' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '12,2',
                    'default'    => 0.00,
                ],
                'desconto' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '12,2',
                    'default'    => 0.00,
                ],
                'acrescimo' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '12,2',
                    'default'    => 0.00,
                ],
                'total' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '12,2',
                    'default'    => 0.00,
                ],
                'ordem' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'default'    => 0,
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
            $this->forge->addKey(['orcamento_id', 'ordem']);
            $this->forge->addKey(['tipo_item', 'referencia_id']);
            $this->forge->addForeignKey('orcamento_id', 'orcamentos', 'id', 'CASCADE', 'CASCADE');
            $this->forge->createTable('orcamento_itens', true);
        }

        if (!$db->tableExists('orcamento_status_historico')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'BIGINT',
                    'constraint'     => 20,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'orcamento_id' => [
                    'type'       => 'BIGINT',
                    'constraint' => 20,
                    'unsigned'   => true,
                ],
                'status_anterior' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 40,
                    'null'       => true,
                ],
                'status_novo' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 40,
                ],
                'observacao' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'origem' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 30,
                    'default'    => 'sistema',
                ],
                'alterado_por' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => true,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);

            $this->forge->addKey('id', true);
            $this->forge->addKey(['orcamento_id', 'created_at']);
            $this->forge->addKey('status_novo');
            $this->forge->addForeignKey('orcamento_id', 'orcamentos', 'id', 'CASCADE', 'CASCADE');
            $this->forge->addForeignKey('alterado_por', 'usuarios', 'id', 'SET NULL', 'CASCADE');
            $this->forge->createTable('orcamento_status_historico', true);
        }

        if (!$db->tableExists('orcamento_envios')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'BIGINT',
                    'constraint'     => 20,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'orcamento_id' => [
                    'type'       => 'BIGINT',
                    'constraint' => 20,
                    'unsigned'   => true,
                ],
                'canal' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 30,
                ],
                'destino' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                ],
                'mensagem' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'documento_path' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                ],
                'status' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 30,
                    'default'    => 'pendente',
                ],
                'provedor' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 60,
                    'null'       => true,
                ],
                'referencia_externa' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 120,
                    'null'       => true,
                ],
                'erro_detalhe' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'enviado_por' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => true,
                ],
                'enviado_em' => [
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
            $this->forge->addKey(['orcamento_id', 'created_at']);
            $this->forge->addKey(['canal', 'status']);
            $this->forge->addForeignKey('orcamento_id', 'orcamentos', 'id', 'CASCADE', 'CASCADE');
            $this->forge->addForeignKey('enviado_por', 'usuarios', 'id', 'SET NULL', 'CASCADE');
            $this->forge->createTable('orcamento_envios', true);
        }

        if (!$db->tableExists('orcamento_aprovacoes')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'BIGINT',
                    'constraint'     => 20,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'orcamento_id' => [
                    'type'       => 'BIGINT',
                    'constraint' => 20,
                    'unsigned'   => true,
                ],
                'token_publico' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 80,
                    'null'       => false,
                ],
                'acao' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'null'       => false,
                ],
                'resposta_cliente' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'ip_origem' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 45,
                    'null'       => true,
                ],
                'user_agent' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);

            $this->forge->addKey('id', true);
            $this->forge->addKey(['orcamento_id', 'created_at']);
            $this->forge->addKey('token_publico');
            $this->forge->addKey('acao');
            $this->forge->addForeignKey('orcamento_id', 'orcamentos', 'id', 'CASCADE', 'CASCADE');
            $this->forge->createTable('orcamento_aprovacoes', true);
        }

        $this->seedModuloPermissoes();
    }

    public function down()
    {
        $moduloId = null;
        if ($this->db->tableExists('modulos')) {
            $module = $this->db->table('modulos')->select('id')->where('slug', 'orcamentos')->get()->getRowArray();
            $moduloId = (int) ($module['id'] ?? 0);
        }

        if ($moduloId > 0 && $this->db->tableExists('grupo_permissoes')) {
            $this->db->table('grupo_permissoes')->where('modulo_id', $moduloId)->delete();
        }

        if ($moduloId > 0 && $this->db->tableExists('modulos')) {
            $this->db->table('modulos')->where('id', $moduloId)->delete();
        }

        $this->forge->dropTable('orcamento_aprovacoes', true);
        $this->forge->dropTable('orcamento_envios', true);
        $this->forge->dropTable('orcamento_status_historico', true);
        $this->forge->dropTable('orcamento_itens', true);
        $this->forge->dropTable('orcamentos', true);
    }

    private function seedModuloPermissoes(): void
    {
        if (
            !$this->db->tableExists('modulos')
            || !$this->db->tableExists('permissoes')
            || !$this->db->tableExists('grupo_permissoes')
        ) {
            return;
        }

        $module = $this->db->table('modulos')->where('slug', 'orcamentos')->get()->getRowArray();
        if (!$module) {
            $this->db->table('modulos')->insert([
                'nome'       => 'Orcamentos',
                'slug'       => 'orcamentos',
                'icone'      => 'bi-receipt-cutoff',
                'ordem_menu' => 70,
                'ativo'      => 1,
            ]);
            $module = $this->db->table('modulos')->where('slug', 'orcamentos')->get()->getRowArray();
        }

        $moduloId = (int) ($module['id'] ?? 0);
        if ($moduloId <= 0) {
            return;
        }

        $permRows = $this->db->table('permissoes')
            ->select('id, slug')
            ->whereIn('slug', ['visualizar', 'criar', 'editar', 'excluir'])
            ->get()
            ->getResultArray();
        if (empty($permRows)) {
            return;
        }

        $permMap = [];
        foreach ($permRows as $permRow) {
            $permMap[(string) $permRow['slug']] = (int) $permRow['id'];
        }

        $eligibleGroups = $this->db->table('grupo_permissoes gp')
            ->distinct()
            ->select('gp.grupo_id')
            ->join('modulos m', 'm.id = gp.modulo_id')
            ->join('permissoes p', 'p.id = gp.permissao_id')
            ->where('p.slug', 'visualizar')
            ->whereIn('m.slug', ['clientes', 'os'])
            ->get()
            ->getResultArray();

        $groupIds = [];
        foreach ($eligibleGroups as $row) {
            $groupId = (int) ($row['grupo_id'] ?? 0);
            if ($groupId > 0 && !in_array($groupId, $groupIds, true)) {
                $groupIds[] = $groupId;
            }
        }

        if (!in_array(1, $groupIds, true)) {
            $groupIds[] = 1;
        }

        $defaultSlugs = ['visualizar', 'criar', 'editar'];
        foreach ($groupIds as $groupId) {
            foreach ($defaultSlugs as $slug) {
                if (!isset($permMap[$slug])) {
                    continue;
                }
                $this->ensureGroupPermission($groupId, $moduloId, $permMap[$slug]);
            }
        }

        if (isset($permMap['excluir'])) {
            $this->ensureGroupPermission(1, $moduloId, $permMap['excluir']);
        }
    }

    private function ensureGroupPermission(int $groupId, int $moduloId, int $permissionId): void
    {
        $exists = $this->db->table('grupo_permissoes')
            ->where('grupo_id', $groupId)
            ->where('modulo_id', $moduloId)
            ->where('permissao_id', $permissionId)
            ->countAllResults();

        if ($exists > 0) {
            return;
        }

        $this->db->table('grupo_permissoes')->insert([
            'grupo_id'     => $groupId,
            'modulo_id'    => $moduloId,
            'permissao_id' => $permissionId,
        ]);
    }
}
