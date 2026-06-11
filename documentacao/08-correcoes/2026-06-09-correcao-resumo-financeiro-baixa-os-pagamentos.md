# Correcao - resumo financeiro da baixa da OS com pagamentos reativos

Data: 09/06/2026
Versao: 2.23.23

## Problema

No modal `Baixa da OS`, os valores digitados nos lancamentos podiam nao aparecer imediatamente no painel `Resumo financeiro e lucro`.

Na pratica, a equipe preenchia `Valor recebido`, mas o resumo lateral ainda mostrava `R$ 0,00` ou mantinha totais desatualizados ate ocorrer `blur` ou outra mudanca que disparasse recalculo.

## Ajuste aplicado

- o resumo lateral passou a exibir explicitamente `Adiantamento ja recebido`, `Lancado nesta acao` e `Saldo projetado apos salvar`;
- a rotina JavaScript agora recalcula o resumo tambem durante a digitacao do campo `Valor recebido`;
- o `Saldo projetado apos salvar` recebe destaque coerente com o resultado da baixa, facilitando a leitura operacional do que ainda ficara pendente.

## Resultado operacional

- os valores do pagamento permanecem visiveis no `Resumo financeiro e lucro` enquanto a equipe registra a baixa;
- o operador consegue conferir imediatamente quanto ja entrou, quanto esta lancando agora e qual saldo ainda restara;
- a leitura financeira do modal fica alinhada com os cards de recebimentos e com o comportamento esperado do fechamento operacional.

## Arquivos impactados

- `app/Views/os/index.php`
- `public/assets/js/os-closure-modal.js`
- `app/Config/SystemRelease.php`
- `documentacao/README.md`
- `documentacao/01-manual-do-usuario/ordens-de-servico.md`
- `documentacao/06-modulos-do-sistema/ordens-de-servico.md`
- `documentacao/07-novas-implementacoes/historico-de-versoes.md`
- `documentacao/08-correcoes/2026-06-09-correcao-resumo-financeiro-baixa-os-pagamentos.md`