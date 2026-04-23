<?php

namespace App\Commands;

use App\Services\LegacyImportService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class LegacyReport extends BaseCommand
{
    protected $group = 'Migracao';
    protected $name = 'legacy:report';
    protected $description = 'Consolida o ultimo run de migracao legada ou um run especifico.';
    protected $usage = 'legacy:report [--run_id=123]';
    protected $options = [
        '--run_id' => 'ID do run salvo em legacy_import_runs.',
    ];

    public function run(array $params)
    {
        $runId = CLI::getOption('run_id');
        $runId = $runId !== null ? (int) $runId : null;

        $service = new LegacyImportService();
        $report = $service->buildReport($runId);

        if (empty($report['run'])) {
            CLI::write('Nenhum run de migracao encontrado.', 'yellow');
            return;
        }

        $run = $report['run'];
        CLI::newLine();
        CLI::write('=== LEGACY REPORT ===', 'yellow');
        CLI::write('Run ID: ' . $run['id']);
        CLI::write('Origem: ' . ($run['source_name'] ?? '-'));
        CLI::write('Modo: ' . ($run['mode'] ?? '-'));
        CLI::write('Status: ' . ($run['status'] ?? '-'));
        CLI::write('Inicio: ' . ($run['started_at'] ?? '-'));
        CLI::write('Fim: ' . ($run['finished_at'] ?? '-'));

        $summary = $report['summary'] ?? [];
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
        CLI::write('Eventos agregados', 'yellow');
        foreach (($report['aggregates'] ?? []) as $aggregate) {
            CLI::write(sprintf(
                '  [%s] %s/%s => %d',
                strtoupper((string) ($aggregate['severity'] ?? 'info')),
                $aggregate['entity'] ?? '-',
                $aggregate['action'] ?? '-',
                (int) ($aggregate['total'] ?? 0)
            ));
        }
    }
}
