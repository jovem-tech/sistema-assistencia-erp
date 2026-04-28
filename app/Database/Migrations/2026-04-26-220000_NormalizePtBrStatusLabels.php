<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class NormalizePtBrStatusLabels extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('os_status')) {
            return;
        }

        $labels = [
            'triagem' => 'Triagem',
            'diagnostico' => 'Diagnóstico Técnico',
            'aguardando_avaliacao' => 'Aguardando Avaliação',
            'verificacao_garantia' => 'Verificação de Garantia',
            'aguardando_orcamento' => 'Aguardando Orçamento',
            'aguardando_autorizacao' => 'Aguardando Autorização',
            'aguardando_reparo' => 'Aguardando Reparo',
            'reparo_execucao' => 'Em Execução do Serviço',
            'cumprimento_garantia' => 'Cumprimento de Garantia',
            'retrabalho' => 'Retrabalho',
            'testes_operacionais' => 'Testes Operacionais',
            'testes_finais' => 'Testes Finais',
            'aguardando_peca' => 'Aguardando Peça',
            'pagamento_pendente' => 'Pagamento Pendente',
            'entregue_pagamento_pendente' => 'Entregue - Pendência Financeira',
            'reparo_concluido' => 'Reparo Concluído',
            'reparado_disponivel_loja' => 'Reparado, Disponível na Loja',
            'garantia_concluida' => 'Garantia Concluída',
            'irreparavel' => 'Irreparável',
            'irreparavel_disponivel_loja' => 'Irreparável, Disponível para Retirada',
            'reparo_recusado' => 'Reparo Recusado',
            'entregue_reparado' => 'Equipamento Entregue',
            'devolvido_sem_reparo' => 'Devolvido Sem Reparo',
            'descartado' => 'Equipamento Descartado',
            'cancelado' => 'Cancelado',
        ];

        $builder = $this->db->table('os_status');
        foreach ($labels as $codigo => $nome) {
            $builder->where('codigo', $codigo)->update(['nome' => $nome]);
        }
    }

    public function down()
    {
        if (!$this->db->tableExists('os_status')) {
            return;
        }

        $labels = [
            'triagem' => 'Triagem',
            'diagnostico' => 'Diagnostico Tecnico',
            'aguardando_avaliacao' => 'Aguardando Avaliacao',
            'verificacao_garantia' => 'Verificacao de Garantia',
            'aguardando_orcamento' => 'Aguardando Orcamento',
            'aguardando_autorizacao' => 'Aguardando Autorizacao',
            'aguardando_reparo' => 'Aguardando Reparo',
            'reparo_execucao' => 'Em Execucao do Servico',
            'cumprimento_garantia' => 'Cumprimento de Garantia',
            'retrabalho' => 'Retrabalho',
            'testes_operacionais' => 'Testes Operacionais',
            'testes_finais' => 'Testes Finais',
            'aguardando_peca' => 'Aguardando Peca',
            'pagamento_pendente' => 'Pagamento Pendente',
            'entregue_pagamento_pendente' => 'Entregue - Pendencia Financeira',
            'reparo_concluido' => 'Reparo Concluido',
            'reparado_disponivel_loja' => 'Reparado, Disponivel na Loja',
            'garantia_concluida' => 'Garantia Concluida',
            'irreparavel' => 'Irreparavel',
            'irreparavel_disponivel_loja' => 'Irreparavel, Disponivel para Retirada',
            'reparo_recusado' => 'Reparo Recusado',
            'entregue_reparado' => 'Equipamento Entregue',
            'devolvido_sem_reparo' => 'Devolvido Sem Reparo',
            'descartado' => 'Equipamento Descartado',
            'cancelado' => 'Cancelado',
        ];

        $builder = $this->db->table('os_status');
        foreach ($labels as $codigo => $nome) {
            $builder->where('codigo', $codigo)->update(['nome' => $nome]);
        }
    }
}
