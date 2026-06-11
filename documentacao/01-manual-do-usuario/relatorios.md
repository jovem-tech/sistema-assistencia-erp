# Manual do Usuario - Relatorios

Atualizado em 10/06/2026 para refletir a organizacao atual do `Fluxo de Caixa`.

## Visao geral

O modulo `Relatorios` concentra as visoes gerenciais do ERP.

Com a implementacao do financeiro gerencial, o menu passou a separar com clareza:

- relatorio operacional
- resultado economico
- liquidez

Os atalhos de `DRE Gerencial` e `Fluxo de Caixa` tambem passaram a ficar disponiveis dentro do submenu `Financas` na sidebar.

## Relatorios disponiveis

### 1. Ordens de servico

**Caminho:** `Relatorios -> Ordens de servico`

Mostra as OS por periodo e status, com foco em:

- cliente
- equipamento
- tecnico
- datas
- status

### 2. Movimentacoes financeiras

**Caminho:** `Relatorios -> Movimentacoes financeiras`

Relatorio operacional do mes. Exibe:

- lista de lancamentos
- resumo de `entradas por categoria`
- resumo de `saidas por categoria`
- leitura de `valor total`, `quitado` e `saldo em aberto` por titulo
- `competencia`, exibida como `mes/ano` nos lancamentos manuais e `data cheia` apenas nas receitas automaticas de `OS`
- `data de vencimento`
- `ultima baixa`
- `origem automatica` do lancamento, exibida em badge na descricao
- `classificacao` com `grupo` e `subgrupo DRE`
- `entradas realizadas`
- `saidas realizadas`
- `resultado de caixa`

Use esse relatorio para conferencia diaria e conciliacao de titulos.

As categorias e classificacoes passam a respeitar o catalogo definido em `Financas -> Configuracoes`, inclusive para registros legados que precisem de leitura padronizada no relatorio.

Com a release `2.19.0`, o relatorio operacional tambem passou a refletir:

- `status parcial`
- quantidade de `baixas` por titulo
- categoria com leitura de `quitado` e `aberto`

Com a release `2.19.6`, a leitura de `competencia` ficou mais intuitiva:

- lancamentos manuais aparecem como `mes/ano`
- receitas automaticas de `OS` continuam exibindo a `data completa` de entrega
- isso evita confundir a equipe com dias tecnicos como `01/mm/aaaa` em despesas mensais

### 3. DRE gerencial

**Caminho:** `Relatorios -> DRE gerencial`

Exibe o resultado por `competencia`, com:

- `receita bruta`
- `descontos`
- `receita liquida`
- `custos diretos`
- `lucro bruto`
- `outras receitas`
- `despesas operacionais`
- `resultado liquido`

A DRE agora tambem considera automaticamente:

- despesas marcadas no Financeiro como `fixa mensal na DRE`
- repeticao mensal a partir da `data de competencia` do lancamento base

Os grupos e subgrupos que aparecem na DRE passam a ser governados em `Financas -> Configuracoes`.

Use essa visao para entender se a empresa teve `lucro ou prejuizo gerencial` no periodo.

### 4. Fluxo de caixa

**Caminho:** `Relatorios -> Fluxo de Caixa`

Exibe a liquidez do periodo com:

- `saldo inicial`
- `entradas realizadas`
- `saidas realizadas`
- `saldo final`
- `entradas previstas`
- `saidas previstas`
- `saldo projetado`
- `abas operacionais` para separar grade, movimentos, titulos, resumo e dashboard
- `grade diaria operacional do caixa`
- `filtros por categoria` dentro das abas de `Movimentos` e `Titulos previstos`
- `dashboard financeiro` com graficos de entradas x saidas, distribuicao por categoria e KPIs
- `composicao visual` das `entradas previstas` e `saidas previstas` no proprio resumo do periodo

Use essa visao para responder se o caixa comporta as obrigacoes do mes.

Assim como no relatorio operacional, a classificacao visivel no fluxo segue o catalogo de `categorias`, `grupos DRE` e `subgrupos DRE`.

Leitura atual:

- os `cards de resumo` permanecem sempre visiveis no topo da tela, acima das abas
- `Grade diaria operacional` mostra, por dia, `entradas`, `saidas`, `saldo do dia` e `acumulado do mes`
- cada linha diaria pode sinalizar tambem previsoes do mesmo dia sem misturar isso ao caixa ja realizado
- os lancamentos do dia nao ficam mais listados direto na grade; a coluna `Acoes` abre um modal com o detalhamento operacional quando houver atividade
- esse modal concentra `movimentos realizados` e `titulos previstos` do dia, evitando poluicao visual em datas com muitos registros
- a aba `Movimentos` agora traz um filtro local de `realizados por categoria`, sem repetir a listagem consolidada no topo
- quando a baixa realizada usa `cartao de credito` ou `cartao de debito`, a coluna `Referencia` passa a mostrar tambem a `taxa da operadora` aplicada naquele movimento
- a aba `Titulos previstos` agora traz um filtro local de `previstos por categoria`, tambem sem repetir a listagem consolidada no topo
- o card `Resumo do periodo` agora mostra quais titulos compoem `entradas previstas` e `saidas previstas`, com `vencimento`, `OS` quando houver vinculo, valor `ja quitado` e saldo `em aberto`
- `Movimentos realizados` mostram cada baixa efetiva do titulo
- `Titulos previstos` mostram apenas o `saldo em aberto`
- titulos `parciais` aparecem no previsto somente pelo valor que ainda falta entrar ou sair
- a aba `Dashboard` consolida a leitura gerencial com:
  - grafico de `Entradas x Saidas por dia`
  - rosca de `Entradas realizadas por categoria`
  - rosca de `Saidas realizadas por categoria`
  - KPIs de `ticket medio de OS`, `titulos em aberto`, `indice de inadimplencia` e `projecao de fechamento`
- os graficos da aba `Dashboard` agora usam altura visual controlada e recalculo logo apos a aba ficar visivel, evitando que o canvas estique a pagina em uma rolagem vertical desproporcional
- nos breakpoints `<= 430px`, `<= 390px`, `<= 360px` e `<= 320px`, os cards de grafico reduzem altura sem criar scroll horizontal extra

Com a revisao de `pt_BR` aplicada nesta tela:

- labels como `Mes`, `Saidas`, `Descricao`, `Referencia`, `Classificacao` e `Titulos` passaram a exibir acentuacao correta na interface do relatorio

### 5. Estoque

**Caminho:** `Relatorios -> Estoque`

Mostra a posicao atual do estoque e itens abaixo do nivel desejado.

## Como escolher o relatorio certo

| Situacao | Relatorio recomendado |
|---|---|
| conferir titulos e baixas | `Movimentacoes Financeiras` |
| medir lucro do periodo | `DRE Gerencial` |
| medir folego de caixa | `Fluxo de Caixa` |
| acompanhar produtividade de atendimento | `Ordens de servico` |
| acompanhar reposicao de pecas | `Estoque` |

## Observacao importante

`Movimentacoes Financeiras`, `DRE` e `Fluxo de Caixa` nao sao a mesma coisa.

- `Movimentacoes Financeiras` = controle operacional dos titulos
- `DRE` = resultado economico
- `Fluxo de Caixa` = dinheiro realizado e projetado
