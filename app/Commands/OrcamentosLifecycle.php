<?php

namespace App\Commands;

use App\Services\OrcamentoLifecycleService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class OrcamentosLifecycle extends BaseCommand
{
    protected $group       = 'Orcamentos';
    protected $name        = 'orcamentos:lifecycle';
    protected $description = 'Executa vencimento automatico e follow-ups do modulo de orcamentos.';

    public function run(array $params)
    {
        helper('sistema');

        $service = new OrcamentoLifecycleService();
        $summary = $service->runAutomations(null);

        CLI::write('Automacao de orcamentos executada com sucesso.', 'green');
        CLI::write('- Orcamentos vencidos: ' . (int) ($summary['orcamentos_vencidos'] ?? 0));
        CLI::write('- Follow-ups aguardando aprovacao: ' . (int) ($summary['followups_aguardando'] ?? 0));
        CLI::write('- Follow-ups de vencidos: ' . (int) ($summary['followups_vencidos'] ?? 0));
        CLI::write('- Follow-ups pendente de OS: ' . (int) ($summary['followups_pendente_os'] ?? 0));
    }
}
