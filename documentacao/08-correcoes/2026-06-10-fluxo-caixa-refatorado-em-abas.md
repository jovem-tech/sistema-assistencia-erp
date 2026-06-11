# Correcao - Fluxo de Caixa reorganizado em abas operacionais

Data: 10/06/2026
Migracao: nao houve

## Problema

A tela `Relatorios -> Fluxo de Caixa` concentrava todos os blocos em uma rolagem vertical unica. Isso deixava a consulta longa, reduzia a eficiencia operacional para alternar entre `grade diaria`, `movimentos`, `titulos` e `resumo`, e nao aproveitava bem o espaco para uma leitura gerencial mais rapida.

## Ajuste aplicado

- os `cards de resumo` permaneceram fixos acima da navegacao principal;
- o conteudo foi reorganizado em cinco abas Bootstrap:
  - `Grade diaria`
  - `Movimentos`
  - `Titulos previstos`
  - `Resumo`
  - `Dashboard`
- os resumos `Realizado por categoria` e `Previsto por categoria` deixaram de ocupar o topo da tela;
- os filtros por categoria foram movidos para dentro das abas `Movimentos` e `Titulos previstos`;
- cada aba agora filtra a propria tabela pelo seletor local, sem recarregar a pagina;
- a aba `Dashboard` passou a montar graficos e KPIs com os dados ja entregues pelo payload existente da view;
- a inicializacao dos graficos ficou lazy, executando apenas quando a aba `Dashboard` e aberta pela primeira vez;
- o detalhamento diario em modal foi preservado dentro da aba `Grade diaria`.

## Resultado operacional

- a consulta do fluxo ficou mais segmentada e rapida para o operador;
- a tela manteve o contexto de `resumo` e `detalhamento diario` sem criar novas rotas;
- os filtros por categoria ficaram mais proximos das tabelas que realmente usam esse refinamento;
- o `Dashboard` passou a apoiar leitura gerencial de curto prazo sem duplicar backend nem quebrar os filtros existentes.

## Arquivos impactados

- `app/Views/relatorios/view_fluxo_caixa.php`
- `documentacao/01-manual-do-usuario/relatorios.md`
- `documentacao/01-manual-do-usuario/financeiro.md`
- `documentacao/05-api/rotas.md`
- `documentacao/08-correcoes/2026-06-10-fluxo-caixa-refatorado-em-abas.md`

## Observacoes

- nao houve alteracao de schema, tabela, migration ou endpoint;
- nao foi necessario alterar `openDocPage`, porque a ajuda continua apontando para a documentacao financeira ja existente.
