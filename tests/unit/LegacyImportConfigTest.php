<?php

use CodeIgniter\Test\CIUnitTestCase;
use Config\LegacyImport;

final class LegacyImportConfigTest extends CIUnitTestCase
{
    public function testRequiredQueriesAndStatusMapAreDefined(): void
    {
        $config = new LegacyImport();

        $this->assertArrayHasKey('clientes', $config->queries);
        $this->assertArrayHasKey('equipamentos', $config->queries);
        $this->assertArrayHasKey('os', $config->queries);
        $this->assertArrayHasKey('os_itens', $config->queries);
        $this->assertArrayHasKey('os_status_historico', $config->queries);
        $this->assertArrayHasKey('os_defeitos', $config->queries);
        $this->assertArrayHasKey('os_notas_legadas', $config->queries);

        $this->assertArrayHasKey('triagem', $config->statusMap);
        $this->assertSame('triagem', $config->statusMap['aguardando_analise']);
        $this->assertSame('reparo_execucao', $config->statusMap['em_reparo']);
        $this->assertSame('entregue_reparado', $config->statusMap['entregue']);
        $this->assertContains('legacy_import_aliases', $config->targetCleanupTables);
    }
}
