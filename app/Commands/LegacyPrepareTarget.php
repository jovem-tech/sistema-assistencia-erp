<?php

namespace App\Commands;

use App\Services\LegacyImportService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class LegacyPrepareTarget extends BaseCommand
{
    protected $group = 'Migracao';
    protected $name = 'legacy:prepare-target';
    protected $description = 'Inspeciona ou limpa os dados operacionais ficticios do ERP atual antes da migracao legada.';
    protected $usage = 'legacy:prepare-target [--execute]';
    protected $options = [
        '--execute' => 'Executa a limpeza das tabelas operacionais e uploads mapeados. Sem a flag, roda apenas em modo preview.',
    ];

    public function run(array $params)
    {
        $execute = (bool) CLI::getOption('execute');

        $service = new LegacyImportService();
        $summary = $service->prepareTarget($execute);

        $this->renderSummary($summary);

        if (($summary['status'] ?? 'failed') !== 'ok') {
            exit(1);
        }
    }

    private function renderSummary(array $summary): void
    {
        CLI::newLine();
        CLI::write($summary['execute'] ? '=== TARGET CLEANUP EXECUTADO ===' : '=== TARGET CLEANUP PREVIEW ===', 'yellow');
        CLI::write('Linhas previstas: ' . (int) ($summary['total_rows'] ?? 0));
        CLI::write('Arquivos previstos: ' . (int) ($summary['total_files'] ?? 0));
        CLI::write('Diretorios previstos: ' . (int) ($summary['total_directories'] ?? 0));

        CLI::newLine();
        CLI::write('Tabelas', 'light_gray');
        foreach (($summary['tables'] ?? []) as $tableSummary) {
            CLI::write(sprintf(
                '  %s | existe=%s | linhas=%d | acao=%s',
                $tableSummary['table'] ?? '-',
                ! empty($tableSummary['exists']) ? 'sim' : 'nao',
                (int) ($tableSummary['rows'] ?? 0),
                $tableSummary['action'] ?? '-'
            ));
        }

        CLI::newLine();
        CLI::write('Diretorios', 'light_gray');
        foreach (($summary['paths'] ?? []) as $pathSummary) {
            CLI::write(sprintf(
                '  %s | existe=%s | arquivos=%d | diretorios=%d | acao=%s',
                $pathSummary['path'] ?? '-',
                ! empty($pathSummary['exists']) ? 'sim' : 'nao',
                (int) ($pathSummary['files'] ?? 0),
                (int) ($pathSummary['directories'] ?? 0),
                $pathSummary['action'] ?? '-'
            ));
        }

        if (! empty($summary['errors'])) {
            CLI::newLine();
            CLI::write('Erros', 'red');
            foreach ($summary['errors'] as $error) {
                CLI::write('  ' . $error, 'red');
            }
        }
    }
}
