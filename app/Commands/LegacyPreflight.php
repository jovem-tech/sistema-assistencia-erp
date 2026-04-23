<?php

namespace App\Commands;

use App\Services\LegacyImportService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class LegacyPreflight extends BaseCommand
{
    protected $group = 'Migracao';
    protected $name = 'legacy:preflight';
    protected $description = 'Valida a origem legada e gera um relatorio de inconsistencias antes da importacao.';

    public function run(array $params)
    {
        $service = new LegacyImportService();
        $summary = $service->runPreflight(true);

        $this->renderSummary($summary);

        if (($summary['blocking_errors'] ?? 0) > 0) {
            exit(1);
        }
    }

    private function renderSummary(array $summary): void
    {
        CLI::newLine();
        CLI::write('=== LEGACY PREFLIGHT ===', 'yellow');
        if (! empty($summary['run_id'])) {
            CLI::write('Run ID: ' . $summary['run_id']);
        }

        foreach (($summary['entities'] ?? []) as $entity => $entitySummary) {
            CLI::write(strtoupper($entity), 'light_gray');
            CLI::write(sprintf(
                '  origem=%d | importados=%d | atualizados=%d | ignorados=%d | erros=%d | avisos=%d',
                (int) ($entitySummary['source_total'] ?? 0),
                (int) ($entitySummary['imported'] ?? 0),
                (int) ($entitySummary['updated'] ?? 0),
                (int) ($entitySummary['ignored'] ?? 0),
                (int) ($entitySummary['errors'] ?? 0),
                (int) ($entitySummary['warnings'] ?? 0)
            ));
        }

        CLI::newLine();
        CLI::write('Erros bloqueantes: ' . (int) ($summary['blocking_errors'] ?? 0), ($summary['blocking_errors'] ?? 0) > 0 ? 'red' : 'green');
        CLI::write('Avisos: ' . (int) ($summary['warnings'] ?? 0), ($summary['warnings'] ?? 0) > 0 ? 'yellow' : 'green');
    }
}
