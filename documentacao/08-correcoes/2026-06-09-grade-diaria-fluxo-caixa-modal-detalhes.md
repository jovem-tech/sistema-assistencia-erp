# Correcao - grade diaria do fluxo de caixa com modal de detalhes

Data: 09/06/2026
Versao: 2.23.22

## Problema

A `Grade diaria operacional` do relatorio `Fluxo de Caixa` exibia detalhes diretamente na linha do dia. Em datas com muitas entradas, saidas ou previsoes, a leitura ficava densa e poluida.

## Ajuste aplicado

- os lancamentos deixaram de aparecer de forma direta na grade principal;
- a tabela ganhou a coluna `Acoes`;
- quando houver atividade no dia, o botao `Visualizar` abre um modal com o detalhamento operacional;
- o modal separa `movimentos realizados` e `titulos previstos`, preservando a leitura do caixa sem perder rastreabilidade.

## Resultado operacional

- a grade ficou mais compacta e dinamica;
- dias com muitos registros nao estouram visualmente a celula `Dia`;
- o operador continua conseguindo auditar o que compoe cada data, agora sob demanda.

## Arquivos impactados

- `app/Models/FinanceiroModel.php`
- `app/Views/relatorios/view_fluxo_caixa.php`
- `app/Config/SystemRelease.php`
- `documentacao/01-manual-do-usuario/relatorios.md`
- `documentacao/05-api/rotas.md`
- `documentacao/07-novas-implementacoes/historico-de-versoes.md`
- `documentacao/07-novas-implementacoes/2026-06-07-release-v2.23.2-composicao-previstos-fluxo-caixa.md`
- `documentacao/08-correcoes/2026-06-09-grade-diaria-fluxo-caixa-modal-detalhes.md`
- `documentacao/README.md`
