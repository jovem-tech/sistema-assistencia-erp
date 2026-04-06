<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateChecklistInfrastructure extends Migration
{
    public function up()
    {
        $this->createChecklistTipos();
        $this->createChecklistModelos();
        $this->createChecklistItens();
        $this->createChecklistExecucoes();
        $this->createChecklistRespostas();
        $this->createChecklistFotos();
        $this->seedChecklistTipos();
        $this->seedChecklistEntradaDefaults();
    }

    public function down()
    {
        if ($this->db->tableExists('checklist_fotos')) {
            $this->forge->dropTable('checklist_fotos', true);
        }

        if ($this->db->tableExists('checklist_respostas')) {
            $this->forge->dropTable('checklist_respostas', true);
        }

        if ($this->db->tableExists('checklist_execucoes')) {
            $this->forge->dropTable('checklist_execucoes', true);
        }

        if ($this->db->tableExists('checklist_itens')) {
            $this->forge->dropTable('checklist_itens', true);
        }

        if ($this->db->tableExists('checklist_modelos')) {
            $this->forge->dropTable('checklist_modelos', true);
        }

        if ($this->db->tableExists('checklist_tipos')) {
            $this->forge->dropTable('checklist_tipos', true);
        }
    }

    private function createChecklistTipos(): void
    {
        if ($this->db->tableExists('checklist_tipos')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'codigo' => [
                'type' => 'VARCHAR',
                'constraint' => 60,
            ],
            'nome' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
            ],
            'descricao' => [
                'type' => 'TEXT',
                'null' => true,
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
        $this->forge->addUniqueKey('codigo', 'ux_checklist_tipos_codigo');
        $this->forge->addKey('ativo', false, false, 'idx_checklist_tipos_ativo');
        $this->forge->createTable('checklist_tipos', true);
    }

    private function createChecklistModelos(): void
    {
        if ($this->db->tableExists('checklist_modelos')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'checklist_tipo_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'tipo_equipamento_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'nome' => [
                'type' => 'VARCHAR',
                'constraint' => 160,
            ],
            'descricao' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'ordem' => [
                'type' => 'INT',
                'constraint' => 11,
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
        $this->forge->addUniqueKey(['checklist_tipo_id', 'tipo_equipamento_id'], 'ux_checklist_modelos_tipo_equip');
        $this->forge->addKey('ativo', false, false, 'idx_checklist_modelos_ativo');
        $this->forge->addKey('tipo_equipamento_id', false, false, 'idx_checklist_modelos_tipo_equipamento');
        $this->forge->createTable('checklist_modelos', true);
    }

    private function createChecklistItens(): void
    {
        if ($this->db->tableExists('checklist_itens')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'checklist_modelo_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'descricao' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'ordem' => [
                'type' => 'INT',
                'constraint' => 11,
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
        $this->forge->addKey('checklist_modelo_id', false, false, 'idx_checklist_itens_modelo');
        $this->forge->addKey('ativo', false, false, 'idx_checklist_itens_ativo');
        $this->forge->createTable('checklist_itens', true);
    }

    private function createChecklistExecucoes(): void
    {
        if ($this->db->tableExists('checklist_execucoes')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'os_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'checklist_tipo_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'checklist_modelo_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
            ],
            'tipo_equipamento_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 40,
                'default' => 'rascunho',
            ],
            'total_itens' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ],
            'total_discrepancias' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ],
            'resumo_texto' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'concluido_em' => [
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
        $this->forge->addUniqueKey(['os_id', 'checklist_tipo_id'], 'ux_checklist_execucoes_os_tipo');
        $this->forge->addKey('checklist_modelo_id', false, false, 'idx_checklist_execucoes_modelo');
        $this->forge->addKey('tipo_equipamento_id', false, false, 'idx_checklist_execucoes_tipo_equip');
        $this->forge->addKey('status', false, false, 'idx_checklist_execucoes_status');
        $this->forge->createTable('checklist_execucoes', true);
    }

    private function createChecklistRespostas(): void
    {
        if ($this->db->tableExists('checklist_respostas')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'checklist_execucao_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
            ],
            'checklist_item_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'descricao_item' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'ordem' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'default' => 'ok',
            ],
            'observacao' => [
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
        $this->forge->addUniqueKey(['checklist_execucao_id', 'checklist_item_id'], 'ux_checklist_respostas_exec_item');
        $this->forge->addKey('status', false, false, 'idx_checklist_respostas_status');
        $this->forge->addKey('ordem', false, false, 'idx_checklist_respostas_ordem');
        $this->forge->createTable('checklist_respostas', true);
    }

    private function createChecklistFotos(): void
    {
        if ($this->db->tableExists('checklist_fotos')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'checklist_resposta_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
            ],
            'arquivo' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'arquivo_original' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'ordem' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
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
        $this->forge->addKey('checklist_resposta_id', false, false, 'idx_checklist_fotos_resposta');
        $this->forge->addKey('ordem', false, false, 'idx_checklist_fotos_ordem');
        $this->forge->createTable('checklist_fotos', true);
    }

    private function seedChecklistTipos(): void
    {
        $table = $this->db->table('checklist_tipos');
        $existing = $table->get()->getResultArray();
        if (!empty($existing)) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $rows = [
            [
                'codigo' => 'entrada',
                'nome' => 'Checklist de Entrada',
                'descricao' => 'Checklist de recepcao e conferencia inicial do equipamento.',
                'ativo' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'codigo' => 'manutencao',
                'nome' => 'Checklist de Manutencao',
                'descricao' => 'Estrutura preparada para conferencias durante o reparo.',
                'ativo' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'codigo' => 'controle_qualidade',
                'nome' => 'Checklist Controle da Qualidade',
                'descricao' => 'Estrutura preparada para homologacao tecnica antes da liberacao.',
                'ativo' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'codigo' => 'saida',
                'nome' => 'Checklist de Saida',
                'descricao' => 'Estrutura preparada para conferencia final de entrega.',
                'ativo' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        $table->insertBatch($rows);
    }

    private function seedChecklistEntradaDefaults(): void
    {
        if (!$this->db->tableExists('equipamentos_tipos')) {
            return;
        }

        $tipoEntrada = $this->db->table('checklist_tipos')
            ->select('id')
            ->where('codigo', 'entrada')
            ->get()
            ->getRowArray();

        if (empty($tipoEntrada['id'])) {
            return;
        }

        $tiposEquipamento = $this->db->table('equipamentos_tipos')
            ->select('id, nome')
            ->where('ativo', 1)
            ->orderBy('nome', 'ASC')
            ->get()
            ->getResultArray();

        if (empty($tiposEquipamento)) {
            return;
        }

        $modeloTable = $this->db->table('checklist_modelos');
        $itemTable = $this->db->table('checklist_itens');
        $now = date('Y-m-d H:i:s');

        foreach ($tiposEquipamento as $tipoEquipamento) {
            $exists = $modeloTable
                ->select('id')
                ->where('checklist_tipo_id', (int) $tipoEntrada['id'])
                ->where('tipo_equipamento_id', (int) ($tipoEquipamento['id'] ?? 0))
                ->get()
                ->getRowArray();

            if (!empty($exists['id'])) {
                continue;
            }

            $modeloTable->insert([
                'checklist_tipo_id' => (int) $tipoEntrada['id'],
                'tipo_equipamento_id' => (int) ($tipoEquipamento['id'] ?? 0),
                'nome' => 'Checklist de Entrada - ' . trim((string) ($tipoEquipamento['nome'] ?? 'Equipamento')),
                'descricao' => 'Modelo inicial gerado automaticamente para conferencia de entrada.',
                'ordem' => 0,
                'ativo' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $modeloId = (int) $this->db->insertID();
            if ($modeloId <= 0) {
                continue;
            }

            $items = $this->defaultEntryItemsForType((string) ($tipoEquipamento['nome'] ?? ''));
            $batch = [];
            foreach ($items as $index => $itemDescription) {
                $batch[] = [
                    'checklist_modelo_id' => $modeloId,
                    'descricao' => $itemDescription,
                    'ordem' => $index + 1,
                    'ativo' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (!empty($batch)) {
                $itemTable->insertBatch($batch);
            }
        }
    }

    /**
     * @return list<string>
     */
    private function defaultEntryItemsForType(string $tipoNome): array
    {
        $normalized = $this->normalizeKey($tipoNome);

        if (str_contains($normalized, 'smartphone') || str_contains($normalized, 'celular')) {
            return [
                'Tela e display sem trincas aparentes',
                'Carcaca e tampa traseira preservadas',
                'Botoes laterais e conectores externos inteiros',
                'Lentes, cameras e flash sem danos aparentes',
                'Bandeja, chip e compartimentos externos conferidos',
                'Selos e identificacao visual conferidos',
            ];
        }

        if (str_contains($normalized, 'tablet')) {
            return [
                'Tela e vidro frontal sem trincas aparentes',
                'Carcaca e tampa traseira preservadas',
                'Botoes, conectores e portas sem danos aparentes',
                'Cameras, sensores e alto-falantes conferidos',
                'Apoios, cantos e molduras sem amassados aparentes',
                'Selos e identificacao visual conferidos',
            ];
        }

        if (str_contains($normalized, 'desktop') || str_contains($normalized, 'computador')) {
            return [
                'Gabinete e paineis externos preservados',
                'Portas, conectores e cabos externos conferidos',
                'Tampa, lacres e parafusos visuais conferidos',
                'Perifericos aparentes e botoes externos conferidos',
                'Etiqueta patrimonial e identificacao visual conferidas',
                'Sinais aparentes de impacto ou oxidacao registrados',
            ];
        }

        if (str_contains($normalized, 'notebook')) {
            return [
                'Tela, tampa e moldura sem trincas aparentes',
                'Dobradiças, base e carcaça preservadas',
                'Teclado, touchpad e botoes externos conferidos',
                'Portas, conectores e fonte visualmente conferidos',
                'Etiqueta, numero de serie e identificacao visual conferidos',
                'Sinais aparentes de impacto ou oxidacao registrados',
            ];
        }

        return [
            'Estrutura externa e carcaça conferidas',
            'Telas, paineis ou superficies visiveis conferidos',
            'Conectores, portas e botoes externos conferidos',
            'Lacres, etiquetas e identificacao visual conferidos',
            'Sinais aparentes de impacto, quebra ou oxidacao registrados',
            'Acessorios externos e complementos visiveis conferidos',
        ];
    }

    private function normalizeKey(string $value): string
    {
        $normalized = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        $normalized = $normalized !== false ? $normalized : $value;
        $normalized = strtolower($normalized);
        $normalized = preg_replace('/[^a-z0-9]+/', '_', $normalized) ?? '';

        return trim($normalized, '_');
    }
}
