<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddLegacyImportAliases extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('legacy_import_aliases')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'source_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 60,
            ],
            'source_entity' => [
                'type'       => 'VARCHAR',
                'constraint' => 40,
            ],
            'source_legacy_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'target_entity' => [
                'type'       => 'VARCHAR',
                'constraint' => 40,
            ],
            'target_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'match_key_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 40,
                'default'    => 'reference',
            ],
            'match_key_value' => [
                'type'       => 'VARCHAR',
                'constraint' => 190,
                'default'    => '',
            ],
            'resolution_strategy' => [
                'type'       => 'VARCHAR',
                'constraint' => 40,
                'default'    => 'legacy_id',
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
        $this->forge->addUniqueKey(['source_name', 'source_entity', 'source_legacy_id', 'match_key_type', 'match_key_value'], 'ux_legacy_alias_source');
        $this->forge->addKey(['source_name', 'source_entity', 'match_key_type', 'match_key_value'], false, false, 'idx_legacy_alias_match_key');
        $this->forge->addKey(['target_entity', 'target_id'], false, false, 'idx_legacy_alias_target');
        $this->forge->createTable('legacy_import_aliases', true);
    }

    public function down()
    {
        if ($this->db->tableExists('legacy_import_aliases')) {
            $this->forge->dropTable('legacy_import_aliases', true);
        }
    }
}
