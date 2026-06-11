# Estudo de Implementacao - DRE e Fluxo de Caixa

Atualizado em 01/06/2026.

Status atual:
- baseline implementada na release `2.17.0`;
- suporte a `despesa fixa mensal na DRE` implementado na release `2.17.2`;
- catalogos configuraveis de `categorias financeiras`, `grupos DRE` e `subgrupos DRE` implementados na release `2.18.0`;
- catalogos financeiros conectados ao `fluxo de caixa` e aos `relatorios operacionais` na release `2.18.1`;
- `baixa parcial` e `multiplos movimentos por titulo` implementados na release `2.19.0`;
- `fornecedor_id` implementado na release `2.19.1`, com exibicao condicional do campo `Fornecedor` apenas para contas `A pagar`;
- a captura manual de `competencia` foi refinada na release `2.19.6` para trabalhar como `mes/ano`, persistindo `01/mm/aaaa` por compatibilidade tecnica;
- este documento continua valendo como referencia para as proximas fases (`multiplas contas`, `conciliacao` e separacao futura entre `titulo` e `movimento`).

## Objetivo

Formalizar a evolucao do modulo financeiro do ERP para suportar:

- `DRE gerencial` por regime de competencia
- `Fluxo de caixa realizado` por data real de entrada e saida
- `Fluxo de caixa projetado` por vencimento futuro
- rastreabilidade por `OS`, compra de peca, despesa administrativa e lancamento manual

Este documento descreve o modelo funcional e tecnico recomendado para a implementacao.

## Estado atual do sistema

Hoje o sistema ja possui uma base financeira operacional, mas ainda sem a separacao formal entre `competencia` e `caixa`.

### O que existe hoje

- tabela `financeiro` com `tipo`, `categoria`, `status`, `data_vencimento` e `data_pagamento`
- modulo de `contas a receber/pagar` com baixa manual
- resumo mensal do financeiro calculado a partir de lancamentos `pagos`
- geracao automatica de `receita a receber` quando a OS e entregue
- geracao opcional de `despesa` quando uma peca e comprada para resolver pendencia de OS
- itens da OS com referencia de `custo`, `margem` e `precificacao`

### Limites atuais

- o resumo do financeiro atual mede `caixa realizado`, nao `DRE`
- o dashboard chama de `faturamento` a soma de `os.valor_final` por `data_entrega`
- nao existe separacao entre `titulo financeiro` e `movimento de caixa`
- ja existe suporte a `pagamento parcial` e `multiplos movimentos por titulo`, mas ainda sem `multiplas contas financeiras` nem `conciliacao`
- nao existe classificacao gerencial consistente para `grupo DRE`, `subgrupo` e `conta financeira`

## Conceitos que precisam ficar explicitos no ERP

| Visao | Pergunta que responde | Regra recomendada |
|---|---|---|
| `Faturamento operacional` | Quanto foi vendido/entregue no periodo? | usa `data_entrega` da OS |
| `DRE gerencial` | A empresa teve lucro ou prejuizo no periodo? | usa `data_competencia` |
| `Fluxo de caixa realizado` | Quanto dinheiro realmente entrou e saiu? | usa `data_movimento` |
| `Fluxo de caixa projetado` | Quanto deve entrar e sair nos proximos dias? | usa `data_vencimento` de titulos pendentes |

## Principios de implementacao

1. `Nao misturar faturamento com caixa`.
2. `Nao chamar resultado de caixa de lucro`.
3. `Separar titulo financeiro de movimento de caixa`.
4. `Manter rastreabilidade da origem` do lancamento.
5. `Evitar dupla contagem` entre OS, financeiro e relatorios.
6. `Preservar compatibilidade incremental` com a tabela `financeiro` atual.

## Arquitetura recomendada

### Visao geral

Recomendacao: manter `financeiro` como tabela mestre de titulos e adicionar tabelas auxiliares para movimentos, contas e categorias.

### 1. Tabela mestre de titulos: `financeiro`

Funcao: representar a obrigacao financeira ou direito a receber.

Campos atuais mantidos:

- `id`
- `os_id`
- `tipo`
- `categoria`
- `descricao`
- `valor`
- `forma_pagamento`
- `status`
- `data_vencimento`
- `data_pagamento`
- `observacoes`

Novos campos recomendados:

| Campo | Tipo sugerido | Finalidade |
|---|---|---|
| `cliente_id` | INT NULL | vinculo direto com cliente quando houver |
| `fornecedor_id` | INT NULL | vinculo com fornecedor em despesas |
| `categoria_id` | INT NULL | referencia para classificacao financeira padronizada |
| `origem_tipo` | VARCHAR(40) | ex.: `os`, `estoque`, `manual`, `fornecedor`, `ajuste` |
| `origem_id` | INT NULL | id do registro de origem |
| `data_competencia` | DATE | data usada pela DRE |
| `valor_bruto` | DECIMAL(12,2) | valor original do titulo |
| `valor_desconto` | DECIMAL(12,2) | desconto financeiro/comercial |
| `valor_acrescimo` | DECIMAL(12,2) | juros, multa ou acrescimo |
| `valor_liquido` | DECIMAL(12,2) | valor final esperado do titulo |
| `status_titulo` | VARCHAR(30) | `pendente`, `parcial`, `liquidado`, `cancelado` |
| `impacta_dre` | TINYINT(1) | define se entra na DRE |
| `impacta_fluxo_caixa` | TINYINT(1) | define se entra no fluxo de caixa |
| `dre_fixo_mensal` | TINYINT(1) | repete a despesa automaticamente na DRE dos meses seguintes |
| `grupo_dre` | VARCHAR(60) | grupo principal da demonstracao |
| `subgrupo_dre` | VARCHAR(80) | detalhamento gerencial |
| `centro_resultado` | VARCHAR(60) | ex.: assistencia, balcao, administrativo |

Observacao:

- no curto prazo, `status` pode continuar existindo por compatibilidade, mas o objetivo e migrar a leitura de relatorios para `status_titulo`
- no fluxo manual de cadastro, a UX deve priorizar `mes/ano de competencia`, salvando internamente o primeiro dia do mes; a `data cheia` deve ficar reservada para origens automaticas como `OS`

### 2. Tabela de movimentos realizados: `financeiro_movimentos`

Funcao: registrar cada entrada ou saida real de dinheiro.

| Campo | Tipo sugerido | Finalidade |
|---|---|---|
| `id` | INT PK | identificador |
| `financeiro_id` | INT FK | titulo de origem |
| `conta_financeira_id` | INT FK | caixa/banco onde o dinheiro entrou ou saiu |
| `tipo_movimento` | VARCHAR(20) | `entrada`, `saida`, `estorno`, `transferencia` |
| `data_movimento` | DATE | data real do caixa |
| `valor_movimento` | DECIMAL(12,2) | valor realizado |
| `forma_pagamento` | VARCHAR(30) | pix, dinheiro, cartao, transferencia, boleto |
| `documento_ref` | VARCHAR(100) NULL | NSU, TED, comprovante, chave de conciliacao |
| `observacoes` | TEXT NULL | contexto adicional |
| `created_at` | DATETIME | auditoria |

Beneficios:

- suporte a `baixa parcial`
- suporte a `uma conta para receber` e `outra para pagar`
- suporte a `mais de um movimento` para o mesmo titulo
- base correta para `fluxo de caixa realizado`

### 3. Tabela de contas financeiras: `financeiro_contas`

Funcao: separar o dinheiro por origem ou destino real.

Campos sugeridos:

- `id`
- `nome`
- `tipo_conta` (`caixa`, `banco`, `pix`, `cartao`, `carteira`, `ajuste`)
- `saldo_inicial`
- `saldo_inicial_em`
- `ativo`
- `observacoes`

### 4. Tabela de categorias padronizadas: `financeiro_categorias`

Funcao: organizar relatorios e evitar categoria livre demais.

Campos sugeridos:

- `id`
- `nome`
- `tipo` (`receber`, `pagar`, `ambos`)
- `grupo_dre`
- `subgrupo_dre`
- `impacta_dre`
- `impacta_fluxo_caixa`
- `ordem_exibicao`
- `ativo`

## Estrutura gerencial recomendada para a DRE

### Receita

- `Receita bruta de servicos`
- `Receita bruta de pecas e acessorios`
- `Outras receitas operacionais`

### Deducoes da receita

- `Descontos concedidos`
- `Cancelamentos`
- `Impostos sobre venda` quando houver controle no ERP

### Custos diretos

- `Custo de pecas consumidas`
- `Custo direto de servicos`

### Despesas operacionais

- `Despesas administrativas`
- `Despesas comerciais`
- `Despesas com pessoal`
- `Aluguel, agua, energia, internet`
- `Taxas de cartao e tarifas bancarias`

### Itens fora da DRE

Devem entrar no `fluxo de caixa`, mas nao na DRE:

- `aporte de socio`
- `retirada de socio`
- `transferencia entre contas`
- `movimento de estoque sem consumo`
- `emprestimos e amortizacoes` se o ERP passar a controlar isso

## Regras de negocio recomendadas

### 1. Receita de OS

Padrao recomendado:

- ao entregar a OS com valor final maior que zero, o sistema gera um `titulo a receber`
- `data_competencia` da receita: `data_entrega`
- `data_vencimento`: configuravel, com default no proprio dia da entrega
- `origem_tipo = os`
- `origem_id = os.id`
- `impacta_dre = 1`
- `impacta_fluxo_caixa = 1`

### 2. Custo de peca consumida na OS

Padrao recomendado:

- usar `os_itens.preco_custo_referencia`
- reconhecer custo apenas quando a peca for `consumida na OS`
- nao reconhecer reposicao geral de estoque como despesa imediata da DRE

### 3. Custo direto de servico

Padrao recomendado:

- usar snapshot de custo ja disponivel nos itens de OS e Orcamento
- quando nao houver snapshot confiavel, usar custo direto padrao do servico

### 4. Despesa de compra emergencial ligada a OS

Padrao recomendado:

- quando a peca for comprada especificamente para uma OS e ja seguir para consumo, pode gerar titulo classificado como `custo direto`
- quando a compra for para reposicao de estoque, deve ficar fora da DRE ate a peca ser consumida

### 5. Pagamentos parciais

Padrao recomendado:

- o titulo fica `parcial` enquanto o total recebido/pago for menor que `valor_liquido`
- cada parcela vira um registro em `financeiro_movimentos`
- a DRE continua olhando `data_competencia` do titulo
- o caixa olha os movimentos realizados

### 6. Estornos

Padrao recomendado:

- nunca apagar movimento liquidado para corrigir saldo
- registrar `movimento de estorno` ou `titulo de ajuste`

## Como os relatorios devem funcionar

### DRE gerencial

Filtros recomendados:

- periodo
- centro de resultado
- categoria
- origem (`os`, `estoque`, `manual`)
- cliente ou fornecedor

Calculo recomendado:

1. somar receitas por `data_competencia`
2. subtrair deducoes
3. subtrair custos diretos
4. obter `lucro bruto`
5. subtrair despesas operacionais
6. obter `resultado liquido`

Drill-down recomendado:

- linha da DRE -> categoria
- categoria -> titulos
- titulo -> OS, fornecedor ou origem manual

### Fluxo de caixa realizado

Filtros recomendados:

- periodo
- conta financeira
- forma de pagamento
- tipo de movimento

Calculo recomendado:

- `saldo inicial`
- `+ entradas realizadas`
- `- saidas realizadas`
- `= saldo final`

### Fluxo de caixa projetado

Filtros recomendados:

- horizonte de `7`, `15`, `30`, `60` e `90` dias
- conta financeira prevista
- titulos `pendentes` e `parciais`

Calculo recomendado:

- considerar titulos abertos por `data_vencimento`
- separar `entradas previstas`, `saidas previstas` e `saldo projetado`

## Mudancas recomendadas nas telas

### Modulo Financeiro

Estrutura sugerida:

- `Lancamentos`
- `DRE`
- `Fluxo de caixa`
- `Contas`
- `Categorias`

### Tela de lancamentos

Novos elementos recomendados:

- campo `data_competencia`
- campo `conta financeira`
- classificacao por `categoria padronizada`
- marcador visual `impacta DRE` e `impacta caixa`
- historico de `movimentos realizados` dentro do titulo

### Tela de DRE

Elementos recomendados:

- demonstracao vertical padronizada
- comparativo `mes atual x mes anterior`
- comparativo `ano atual x ano anterior`
- detalhamento expansivel
- exportacao para PDF e CSV

### Tela de fluxo de caixa

Elementos recomendados:

- alternancia entre `realizado` e `projetado`
- visao diaria, semanal e mensal
- saldo por conta
- destaque de dias com saldo negativo projetado

## Ajustes recomendados no dashboard

Para reduzir ambiguidade conceitual, o dashboard deve adotar nomes explicitos:

- `Faturamento do mes` -> continua ligado a `OS entregues`
- `Entradas realizadas no mes` -> caixa
- `Saidas realizadas no mes` -> caixa
- `Resultado de caixa do mes` -> substituir o rotulo atual `Lucro` quando a base for apenas movimento pago

Opcionalmente, em fase posterior:

- card de `Lucro operacional (DRE)` separado do caixa

## Plano de implementacao em fases

### Fase 0 - saneamento conceitual e nomenclatura

- revisar nomes de cards e relatorios atuais
- parar de tratar caixa como DRE
- definir categorias padrao e politicas de competencia

### Fase 1 - modelagem e migracao

- evoluir tabela `financeiro`
- criar `financeiro_movimentos`
- criar `financeiro_contas`
- criar `financeiro_categorias`
- popular defaults e backfill

### Fase 2 - automacoes de origem

- gerar titulos automaticos a partir de OS
- classificar compras emergenciais vs reposicao de estoque
- carregar custo direto dos itens para apuracao da DRE

### Fase 3 - relatorio DRE

- filtro por periodo
- agrupamento por categoria
- drill-down por titulo e origem
- exportacao

### Fase 4 - fluxo de caixa realizado e projetado

- movimentos por conta
- baixa parcial
- saldos
- previsao futura

### Fase 5 - fechamento e governanca

- travas de fechamento mensal
- estornos controlados
- conciliacao
- auditoria e log

## Migracao de dados legados

Recomendacao para backfill:

1. manter cada linha atual de `financeiro` como `titulo`
2. criar `1 movimento` em `financeiro_movimentos` para cada linha com `status = pago`
3. usar `data_pagamento` como `data_movimento`
4. usar `valor` atual como `valor_bruto` e `valor_liquido` inicial
5. definir `data_competencia` provisoria:
   - `os.data_entrega` quando a origem for OS entregue
   - `data_vencimento` para lancamentos sem referencia melhor
   - `data_pagamento` apenas como ultimo fallback
6. normalizar `categoria` livre para `financeiro_categorias`

## Decisoes de negocio pendentes

Se a equipe nao decidir agora, a recomendacao inicial e usar os defaults abaixo.

| Tema | Default recomendado |
|---|---|
| Data de competencia da receita da OS | `data_entrega` |
| Compra para reposicao de estoque | entra no caixa, nao entra na DRE ate consumo |
| Compra emergencial para OS | pode entrar como custo direto da OS |
| Origem do lancamento no Financeiro | calculada automaticamente pelo backend |
| Taxa de cartao | despesa operacional/financeira na data do recebimento |
| Aporte e retirada de socio | fora da DRE, apenas no fluxo de caixa |

## Criterios de aceite da implementacao

1. O mesmo periodo pode mostrar numeros diferentes em `DRE` e `fluxo de caixa`, de forma explicavel.
2. Um titulo pode receber mais de um movimento sem perder integridade.
3. O relatorio DRE consegue explicar o resultado ate o nivel do titulo e da OS.
4. O fluxo de caixa por conta fecha com `saldo inicial + entradas - saidas = saldo final`.
5. Compras de estoque e retiradas de socio nao poluem o lucro gerencial.

## Proximo passo recomendado

Depois deste estudo, a proxima entrega tecnica deve ser uma `especificacao de banco e migracoes`, com:

- nome final das tabelas e colunas
- regras de backfill
- endpoints e services necessarios
- impacto nas telas `Financeiro`, `Dashboard`, `Relatorios` e `OS`
