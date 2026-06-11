<?php

declare(strict_types=1);

use App\Controllers\Orcamentos;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class OrcamentosLookupTest extends CIUnitTestCase
{
    public function testFormatOsAbertaLookupResultHydratesMarcaEModeloSemErro(): void
    {
        helper('sistema');

        $reflection = new ReflectionClass(Orcamentos::class);
        $controller = $reflection->newInstanceWithoutConstructor();

        $statusLabelMap = $reflection->getProperty('osStatusLabelMap');
        $statusLabelMap->setAccessible(true);
        $statusLabelMap->setValue($controller, [
            'em_diagnostico' => 'Em diagnostico',
        ]);

        $method = $reflection->getMethod('formatOsAbertaLookupResult');
        $method->setAccessible(true);

        $result = $method->invoke($controller, [
            'id' => 3575,
            'numero_os' => '3575',
            'status' => 'em_diagnostico',
            'estado_fluxo' => 'aberta',
            'equipamento_id' => 3537,
            'equip_tipo' => 'Desktop',
            'equip_marca' => 'Dell',
            'equip_modelo' => 'OptiPlex 7010',
            'cor' => 'Preto',
            'foto_principal_arquivo' => '',
        ]);

        $this->assertIsArray($result);
        $this->assertSame('OS 3575 - Desktop | Dell OptiPlex 7010', $result['text']);
        $this->assertSame('Em diagnostico', $result['status_label']);
        $this->assertSame('Dell', $result['equipamento']['marca']);
        $this->assertSame('OptiPlex 7010', $result['equipamento']['modelo']);
        $this->assertStringContainsString('Dell', $result['search_text']);
        $this->assertStringContainsString('OptiPlex 7010', $result['search_text']);
    }
}
