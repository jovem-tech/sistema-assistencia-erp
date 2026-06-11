# Correcao - adiantamento e sinal nao alteram status da OS

Data: 09/06/2026
Versao: 2.23.21

## Problema

No modal `Baixa da OS`, os lancamentos classificados como `Adiantamento` ou `Sinal` podiam ser interpretados como parte da baixa operacional, o que confundia a leitura de status da ordem.

## Regra corrigida

- `Adiantamento` e `Sinal` sao apenas antecipacoes financeiras do cliente sobre o orcamento aprovado da OS.
- Esses valores continuam sendo registrados no titulo financeiro da OS, no `Fluxo de Caixa` e na `DRE`.
- Esses valores nao alteram `status`, `estado_fluxo`, `data_entrega`, `baixa_tecnica_em`, retorno pos-servico ou cobranca automatica.
- Somente `Recebimento da baixa` altera o status operacional da OS.

## Resultado esperado

- Se o operador registrar apenas `Adiantamento` ou `Sinal`, a resposta AJAX retorna `status_unchanged=true` e a OS permanece no status atual.
- Se o operador registrar `Recebimento da baixa` parcial, a OS passa para `entregue_pagamento_pendente` e continua aberta para cobranca.
- Se o operador registrar `Recebimento da baixa` integral, a OS muda para o status final selecionado na baixa.
- Em todos os cenarios com valor recebido, o movimento financeiro permanece registrado para relatorios de caixa e DRE.

## Arquivos impactados

- `app/Controllers/Os.php`
- `app/Views/os/index.php`
- `public/assets/js/os-closure-modal.js`
- `app/Config/SystemRelease.php`
- `documentacao/01-manual-do-usuario/ordens-de-servico.md`
- `documentacao/01-manual-do-usuario/financeiro.md`
- `documentacao/05-api/rotas.md`
- `documentacao/06-modulos-do-sistema/ordens-de-servico.md`
- `documentacao/07-novas-implementacoes/historico-de-versoes.md`
- `documentacao/README.md`
