<?php

namespace App\Commands;

use App\Services\OsSettlementService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class OsCobrancasLifecycle extends BaseCommand
{
    protected $group       = 'Ordens de Servico';
    protected $name        = 'os:cobrancas';
    protected $description = 'Processa a rÃ©gua automÃ¡tica de cobranÃ§a das OS entregues com pagamento pendente.';

    public function run(array $params)
    {
        helper('sistema');

        $summary = (new OsSettlementService())->processPendingChargeNotifications(null);

        CLI::write('RÃ©gua automÃ¡tica de cobranÃ§a das OS executada com sucesso.', 'green');
        CLI::write('- Agendamentos lidos: ' . (int) ($summary['agendamentos_lidos'] ?? 0));
        CLI::write('- Mensagens enviadas: ' . (int) ($summary['agendamentos_enviados'] ?? 0));
        CLI::write('- Agendamentos cancelados: ' . (int) ($summary['agendamentos_cancelados'] ?? 0));
        CLI::write('- Agendamentos com erro: ' . (int) ($summary['agendamentos_com_erro'] ?? 0));
    }
}
