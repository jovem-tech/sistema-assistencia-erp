<?php

namespace App\Commands;

use App\Services\LegacyImportService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class LegacyImport extends BaseCommand
{
    protected $group = 'Migracao';
    protected $name = 'legacy:import';
    protected $description = 'Executa a importacao de clientes, equipamentos e OS do banco legado.';
    protected $usage = 'legacy:import --execute [--wipe-target]';
    protected $options = [
        '--execute' => 'Obrigatorio. Sem esta flag o comando apenas avisa e nao importa nada.',
        '--wipe-target' => 'Apaga dados operacionais ficticios e uploads relacionados antes da importacao.',
    ];

    public function run(array $params)
    {
        if (! CLI::getOption('execute')) {
            CLI::write('Use --execute para confirmar a importacao. Nada foi alterado.', 'yellow');
            exit(1);
        }

        $service = new LegacyImportService();

        if (CLI::getOption('wipe-target')) {
            CLI::write('Limpando dados operacionais e uploads ficticios antes da migracao...', 'yellow');
            $cleanup = $service->prepareTarget(true);
            $this->renderCleanupSummary($cleanup);

            if (($cleanup['status'] ?? 'failed') !== 'ok') {
                CLI::write('Limpeza interrompida por erro. A importacao nao foi iniciada.', 'red');
                exit(1);
            }
        }

        $summary = $service->runImport(true);

        $this->renderSummary($summary);

        if (($summary['blocking_errors'] ?? 0) > 0 || ! empty($summary['import_aborted'])) {
            exit(1);
        }
    }

    private function renderCleanupSummary(array $summary): void
    {
        CLI::newLine();
        CLI::write('=== TARGET CLEANUP ===', 'yellow');
        CLI::write('Linhas previstas/removidas: ' . (int) ($summary['total_rows'] ?? 0));
        CLI::write('Arquivos previstos/removidos: ' . (int) ($summary['total_files'] ?? 0));
        CLI::write('Diretorios previstos/removidos: ' . (int) ($summary['total_directories'] ?? 0));

        if (! empty($summary['errors'])) {
            foreach ($summary['errors'] as $error) {
                CLI::write('  erro: ' . $error, 'red');
            }
        }
    }

    private function renderSummary(array $summary): void
    {
        CLI::newLine();
        CLI::write('=== LEGACY IMPORT ===', 'yellow');
        if (! empty($summary['run_id'])) {
            CLI::write('Run ID: ' . $summary['run_id']);
        }

        if (! empty($summary['import_aborted'])) {
            CLI::write('Importacao abortada por bloqueios encontrados no preflight.', 'red');
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
