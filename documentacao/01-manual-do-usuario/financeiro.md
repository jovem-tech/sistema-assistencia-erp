# Manual do Usuario - Financeiro

Atualizado em 10/06/2026 para refletir a organizacao atual do `Fluxo de Caixa`.

## Visao geral

O modulo `Financeiro` passou a reunir duas camadas:

- `operacional`: contas a receber, contas a pagar, vencimentos e baixas
- `gerencial`: classificacao para `DRE` e leitura correta de `fluxo de caixa`

O painel principal continua sendo a porta de entrada da rotina diaria, mas agora conversa com os novos relatorios executivos.

No menu lateral, a navegacao financeira passou a ficar concentrada em:

- `Financas -> Financeiro`
- `Financas -> DRE Gerencial`
- `Financas -> Fluxo de Caixa`
- `Financas -> Configuracoes`
- `Financas -> Cartoes e taxas`

## O que mudou na pratica

O modulo agora diferencia:

- `resultado de caixa`: entradas e saidas realmente pagas/recebidas
- `DRE`: resultado economico por `data de competencia`
- `fluxo de caixa`: leitura realizada e projetada dos titulos
- `fluxo de caixa`: prova visual da composicao dos valores previstos no proprio resumo
- `fluxo de caixa`: navegacao em abas para separar grade diaria, movimentos, titulos, resumo e dashboard

Por isso o card que antes aparecia como `Lucro` foi renomeado para `Resultado de caixa`.

## Painel Financeiro

Na tela `Financeiro`, o usuario encontra:

- cards com `entradas realizadas`, `saidas realizadas`, `resultado de caixa` e `recebimentos pendentes`
- filtros por `tipo`, `status` e `despesas fixas`
- listagem dos lancamentos com:
  - `descricao` com contexto ampliado de `categoria`, `OS`, `cliente`, `equipamento` e um resumo do servico ou da observacao principal
  - coluna `descricao` com distribuicao fixa de largura no desktop, evitando que o titulo do card fique espremido, quebre letra a letra ou empurre `Classificacao` e `Acoes` para um estado ilegivel
  - `competencia` exibida como `mes/ano` na maior parte dos lancamentos manuais
  - `vencimento`
  - `ultima baixa`
  - `valor total`, `ja quitado` e `saldo em aberto`
  - `grupo DRE`
  - indicadores de impacto em `DRE` e `Caixa`
- atalhos diretos para:
  - `DRE`
  - `Fluxo de Caixa`
  - `Configuracoes`

### Responsividade do painel

O cabeÃ§alho da tela `Financeiro` foi ajustado para manter os botoes de acao acessiveis mesmo em janelas mais estreitas:

- o atalho principal `+ Novo lancamento` passou a ficar mais compacto e continua visivel no topo da pagina;
- em telas pequenas, o botao de criar lancamento pode reduzir para a leitura apenas do `+`, preservando o acesso rapido sem apertar o restante da barra;
- a lista de lancamentos passou a fazer wrap dos botoes da coluna `Acoes`, evitando que `Visualizar`, `Registrar baixa`, `Editar`, `Encerrar` e `Excluir` fiquem cortados na lateral;
- a grade do desktop passou a reservar espaco proporcional para `Descricao`, `Classificacao` e `Acoes`, mantendo o chip `Ver detalhes` e o titulo do card alinhados sem esmagar o texto;
- a leitura da tabela continua funcionando em desktop, notebook e mobile sem depender de rolagem horizontal para localizar as acoes.

### Filtro de despesas fixas

Na barra superior da listagem existe agora o filtro:

- `Despesas fixas`

Quando ele e ativado, o sistema mostra apenas lancamentos:

- do tipo `A pagar`
- marcados com `Despesa fixa mensal na DRE`

Comportamento pratico:

- o filtro pode ser combinado com `Pendentes`, `Parciais`, `Pagos` e `Cancelados`
- ao clicar em `A receber`, o sistema limpa esse filtro automaticamente, porque receitas nao usam recorrencia de despesa fixa
- ao clicar em `Todos`, a grade volta ao estado geral sem filtros ativos

## Configuracoes financeiras

Em `Financas -> Configuracoes`, o sistema agora oferece um cadastro estruturado para:

- `categorias financeiras`
- `grupos DRE`
- `subgrupos DRE`

Objetivo:

- parar de depender de campo livre no lancamento;
- padronizar a classificacao do financeiro;
- alimentar dropdowns no formulario;
- organizar melhor a leitura da `DRE`;
- alimentar de forma consistente o `fluxo de caixa` e os `relatorios operacionais`.

## Cartoes e taxas

Em `Financas -> Cartoes e taxas`, o sistema agora concentra a configuracao do recebimento em maquininha.

### Organizacao em abas

A tela foi reorganizada em quatro abas visÃ­veis para reduzir rolagem e separar melhor os contextos operacionais:

- `Operadora de maquininha`
- `Bandeiras`
- `Taxa por parcela`
- `Simulador de faturamento liquido`

Uso pratico:

- cada aba concentra apenas o que o usuario precisa editar naquele momento;
- o cadastro de operadoras e bandeiras fica separado do simulador e da lista consolidada de taxas;
- em telas menores, as abas podem ser roladas horizontalmente sem quebrar o layout;
- as tabelas e formulÃ¡rios de cada aba ocupam toda a largura disponivel do painel, mantendo leitura clara em desktop, notebook, tablet e celular;
- dentro da aba `Taxa por parcela`, o formulario de cadastro e a lista `Taxas cadastradas` aparecem no mesmo painel, em colunas lado a lado no desktop e empilhados no mobile.
- a lista `Taxas cadastradas` ganhou botÃµes de filtro por operadora, permitindo localizar rapidamente as regras de uma marca especifica sem sair da aba.

Cadastros disponiveis:

- `operadoras de maquininha`
- `bandeiras`
- `taxas por modalidade`
- `faixas de parcelas`
- `prazo de recebimento`

Uso pratico:

- o cadastro define quanto a assistencia realmente recebe em `credito` e `debito`;
- a taxa pode combinar `percentual`, `taxa fixa`, `parcelas` e `bandeira`;
- o simulador da propria tela calcula `valor bruto`, `taxa`, `valor liquido` e `prazo previsto de recebimento`.

## Integracao com a baixa da OS

O modulo financeiro passou a receber eventos diretamente da baixa da ordem de servico.

Comportamento atual:

- quando a OS e entregue com recebimento parcial, o titulo financeiro continua aberto com saldo pendente;
- valores recebidos antes da entrega final podem ser lancados como `adiantamento` ou `sinal`;
- `adiantamento` e `sinal` sao apenas antecipacoes financeiras: entram no caixa e na DRE, mas nao alteram o status da OS;
- somente lancamentos classificados como `Recebimento da baixa` disparam a baixa operacional da OS;
- se o `Recebimento da baixa` for parcial, a OS fica em `entregue_pagamento_pendente`; se quitar o saldo, a OS muda para o status final selecionado;
- a aba `Valores` da OS agora mostra `adiantamento recebido`, `total recebido`, `saldo pendente` e o `historico de recebimentos`;
- o modal `Baixa da OS` passou a deixar explicito o card `Adiantamento ja recebido`;
- quando a OS ainda estiver com `valor_final = 0`, mas ja existir `orcamento aprovado` ou `convertido`, o modal e a aba `Valores` passam a usar esse total aprovado como base operacional;
- quando a baixa usa `cartao de credito` ou `cartao de debito`, o sistema registra a taxa estimada da operadora;
- essa taxa reduz o resultado liquido da venda;
- o custo da taxa tambem pode gerar despesa financeira automatica para leitura mais fiel do faturamento liquido;
- assim que o saldo da OS chega a zero, o sistema sincroniza automaticamente o encerramento financeiro definitivo da ordem.

## Visualizacao detalhada da receita ou despesa

Agora a propria listagem funciona como ponto de consulta detalhada.

O operador pode clicar em:

- `Receber` ou `Pagar` na coluna `Tipo`
- na propria `Descricao`
- no icone de `visualizacao` da linha

Ao abrir o modal de detalhamento, o sistema mostra:

- dados financeiros do titulo: `valor total`, `valor quitado`, `saldo em aberto`, `competencia`, `vencimento`, `ultima baixa`, `formas de pagamento`, `grupo/subgrupo DRE`, origem e flags de impacto
- `historico de baixas`, com cada movimento realizado no titulo
- observacoes registradas no titulo
- contexto da `OS`, quando houver vinculo

Quando o lancamento vier de uma `OS`, o modal passa a exibir tambem:

- `cliente` que pagou ou esta vinculado ao servico
- `equipamento` com tipo, marca, modelo, serie, IMEI, cor e senha de acesso quando cadastrada
- `relato do cliente`
- `diagnostico tecnico`
- `solucao aplicada`
- `procedimentos executados`
- `itens e servicos` registrados na ordem
- `defeitos relatados` e observacoes do equipamento, quando existirem

Quando for uma `conta a pagar` sem OS vinculada, o modal continua exibindo todo o cadastro financeiro disponivel para consulta rapida, sem obrigar o operador a entrar na tela de edicao.

Importante:

- o detalhamento continua funcionando mesmo quando o titulo nao possui `OS` vinculada;
- nesses casos, o modal abre direto com o contexto financeiro da conta, sem depender de dados de equipamento da ordem.

## Novo lancamento

Ao cadastrar ou editar um lancamento, o formulario agora aceita:

| Campo | Uso principal |
|---|---|
| `Tipo` | define se o titulo e `receber` ou `pagar` |
| `Categoria` | selecao por dropdown a partir das categorias cadastradas |
| `Descricao` | identificacao do titulo |
| `Fornecedor` | exibido apenas em lancamentos `A pagar` para vincular a despesa ao fornecedor |
| `Valor` | valor financeiro do lancamento |
| `Data vencimento` | data prevista do titulo |
| `Status` | `pendente`, `parcial`, `pago` ou `cancelado` |
| `Forma de pagamento` | meio base do titulo; com multiplas baixas, o historico passa a detalhar cada movimento |
| `Mes/ano de competencia` | alimenta a `DRE` pelo mes ao qual a receita ou despesa pertence |
| `Data de pagamento` | referencia visual do ultimo movimento registrado no titulo |
| `Origem automatica` | rastreabilidade definida pelo sistema, sem digitacao manual |
| `Grupo DRE` | selecao por dropdown a partir dos grupos cadastrados |
| `Subgrupo DRE` | selecao por dropdown a partir dos subgrupos cadastrados |
| `Impacta DRE` | inclui ou exclui o titulo do resultado |
| `Impacta fluxo de caixa` | inclui ou exclui o titulo do caixa |
| `Despesa fixa mensal na DRE` | repete automaticamente a despesa nos meses seguintes da DRE |
| `Observacoes` | anotacoes livres |

Na organizacao visual do formulario:

- `Data vencimento`
- `Mes/ano de competencia`
- `Data de pagamento`

agora aparecem lado a lado no desktop para acelerar a leitura operacional durante o lancamento.

### Como funciona o mes/ano de competencia

O formulario passou a tratar a competencia de forma mais intuitiva para a equipe:

- em lancamentos manuais, o campo agora e `Mes/ano de competencia`
- internamente, o sistema salva esse valor como o `primeiro dia do mes`, por exemplo `06/2026` -> `2026-06-01`
- na `DRE`, o que vale e o `mes/ano`, nao o dia tecnico salvo no banco
- apenas receitas automaticas vindas de `OS` continuam preservando a `data cheia` original de entrega

Exemplos:

- `internet de junho` -> competencia `06/2026`, salva internamente como `2026-06-01`
- `aluguel de julho` -> competencia `07/2026`, salva internamente como `2026-07-01`
- `OS entregue em 20/06/2026` -> competencia mantida como `20/06/2026`, porque a origem automatica e `OS`

### Despesa fixa mensal na DRE

Para contas como `aluguel`, `energia`, `internet`, `telefonia` e outras despesas fixas, o formulario agora oferece a opcao:

- `Despesa fixa mensal na DRE`

Regra pratica:

- a opcao so vale para lancamentos do tipo `A pagar`
- quando marcada, a despesa passa a entrar automaticamente na `DRE` de todos os meses a partir da `data de competencia`
- nao e preciso cadastrar o mesmo titulo manualmente mes a mes apenas para manter a DRE correta

Observacao:

- essa marcacao nao transforma o titulo em multiplo movimento financeiro; ela automatiza a leitura gerencial da `DRE`

## Como os dropdowns funcionam

O formulario de lancamento financeiro agora usa catalogos cadastrados em configuracao:

- `Categoria` passa a ser dropdown
- `Grupo DRE` passa a ser dropdown
- `Subgrupo DRE` passa a ser dropdown dependente do grupo selecionado

Regra de exibicao por tipo:

- em `A pagar`, o formulario passa a mostrar o campo `Fornecedor`
- em `A receber`, o campo fica oculto e o vinculo e limpo para evitar classificacao indevida de receita como despesa de fornecedor
- o dropdown reaproveita o cadastro oficial do modulo `Fornecedores`

Quando a categoria tiver defaults configurados, o sistema pode sugerir automaticamente:

- `grupo DRE`
- `subgrupo DRE`
- flags de impacto em `DRE` e `Caixa`
- recorrencia de `despesa fixa mensal na DRE`

## Classificacao automatica

Quando o usuario nao preencher a classificacao gerencial, o sistema tenta inferir automaticamente:

- `receitas ligadas a OS` -> `Receita Operacional`
- `compras emergenciais de pecas` -> `Custo Direto (OS)`
- `receitas manuais` -> `Outras Receitas`
- `despesas manuais` -> `Despesas Operacionais`

Agora a `origem` do lancamento tambem passa a ser sempre automatica:

- `receita com OS vinculada` -> `Ordem de servico`
- `despesa de compra de pecas com OS vinculada` -> `Compra para OS`
- `despesa de compra de pecas sem OS vinculada` -> `Compra para estoque`
- `despesa manual com OS vinculada` -> `Despesa vinculada a OS`
- `lancamento direto no Financeiro` -> `Lancamento manual`

## Baixa de lancamento

Na listagem, a acao `Registrar baixa`:

- aceita informar `valor da baixa`
- aceita `baixa parcial`
- registra `data` e `forma` do movimento
- recalcula automaticamente `status`, `ja quitado` e `saldo em aberto`
- faz cada movimento passar a compor o `caixa realizado`

Regras praticas:

- um mesmo titulo pode ter `multiplas baixas`
- o sistema nao permite baixar valor maior que o `saldo em aberto`
- quando o saldo chega a zero, o titulo muda para `pago`
- enquanto houver saldo residual, o titulo fica como `parcial`
- titulos com movimentacao registrada nao podem ser cancelados nem ter o valor reduzido abaixo do que ja foi quitado

### Baixa originada pela OS

Quando a baixa acontece a partir do modal da OS:

- o ERP pode registrar varios recebimentos em uma unica conclusao operacional;
- o operador tambem pode usar `Adicionar adiantamento` para registrar pagamentos antecipados sem perder o contexto da baixa;
- cada lancamento dessa area pode ficar classificado como `Recebimento da baixa`, `Adiantamento` ou `Sinal`;
- cada recebimento entra no historico de movimentos do titulo financeiro da OS;
- apenas `Recebimento da baixa` altera o status da OS; `Adiantamento` e `Sinal` preservam o status atual;
- pagamentos em cartao passam a guardar operadora, bandeira, parcelas, taxa aplicada e valor liquido previsto;
- se ainda restar saldo, a OS permanece em acompanhamento de cobranca ate a quitacao final.

## Relatorios conectados ao modulo

O modulo financeiro se conecta a tres relatorios:

1. `Movimentacoes Financeiras`
   - visao operacional do mes
   - mostra os titulos, o resultado de caixa, o valor quitado e o saldo em aberto por titulo
   - passou a resumir categoria com `total`, `quitado` e `aberto`
   - reaproveita os grupos e subgrupos definidos em `Financas -> Configuracoes`

2. `DRE Gerencial`
   - visao economica por competencia
   - destaca receita liquida, custos diretos, lucro bruto e resultado liquido
   - repete automaticamente as despesas marcadas como `fixa mensal na DRE`

3. `Fluxo de Caixa`
   - visao de liquidez
   - mostra saldo inicial, realizado, previsto e saldo projetado
   - organiza o conteudo em cinco abas: `Grade diaria`, `Movimentos`, `Titulos previstos`, `Resumo` e `Dashboard`
   - mantem os `cards de resumo` sempre visiveis acima das abas
   - agora inclui uma `grade diÃ¡ria operacional`, com `entradas`, `saÃ­das`, `saldo do dia` e `acumulado do mÃªs`
   - concentra os filtros por categoria dentro das abas `Movimentos` e `Titulos previstos`
   - o `realizado` agora usa cada movimento de baixa
   - movimentos com `cartao de credito` ou `cartao de debito` passam a exibir, na propria referencia da linha, a `taxa da operadora` vinculada ao recebimento
   - o `previsto` agora usa apenas o `saldo em aberto` dos titulos pendentes e parciais
   - reaproveita a mesma classificacao configurada no catalogo financeiro
   - passou a exibir a `composicao visual` de `entradas previstas` e `saidas previstas`, deixando claro quais titulos formam cada total
   - cada uma dessas abas possui filtro local por categoria e classificacao, sem repetir tabelas-resumo acima da navegacao
   - o `Dashboard` reune `Entradas x Saidas por dia`, distribuicao por categoria e KPIs de `ticket medio de OS`, `titulos em aberto`, `indice de inadimplencia` e `projecao de fechamento`
   - os graficos do `Dashboard` passaram a usar altura previsivel e recalculo ao abrir a aba, evitando que a tela fique artificialmente gigante em desktop ou mobile
   - os tamanhos dos cards do dashboard foram validados tambem nos breakpoints `<= 430px`, `<= 390px`, `<= 360px` e `<= 320px`, mantendo a leitura sem scroll horizontal indevido

## Observacoes importantes

- `Mes/ano de competencia` e diferente de `Data de pagamento`.
- se a equipe souber o `mes de referencia`, esse deve ser o valor da `competencia` na DRE.
- em lancamentos manuais, a equipe deve pensar em `mes/ano`, nao no dia.
- o sistema salva manualmente esse mes/ano como `01/mm/aaaa` apenas por necessidade tecnica do banco.
- se o campo `Mes/ano de competencia` ficar em branco, o sistema usa primeiro a `data de vencimento`; em receitas de `OS`, prioriza a `data de entrega`.
- Um lancamento pode impactar `DRE` sem impactar `Caixa`, e vice-versa.
- O modulo agora possui `baixa parcial` e `multiplos movimentos` por titulo.
- A base conceitual completa desta evolucao esta em `documentacao/09-roadmap/estudo-dre-fluxo-de-caixa.md`.
