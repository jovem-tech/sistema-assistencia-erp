# Manual do Usuario - Financeiro

## Visao geral

O modulo `Financeiro` atual e um controle operacional de `contas a receber` e `contas a pagar`.

Ele foi pensado para registrar:

- receitas
- despesas
- vencimentos
- baixas de pagamento e recebimento

## Escopo atual do modulo

Hoje a tela financeira atende principalmente o fluxo diario de lancamentos.

### O que o modulo faz hoje

- cadastrar lancamento manual
- classificar como `receber` ou `pagar`
- vincular lancamento a uma `OS`, quando aplicavel
- registrar `status` como `pendente`, `pago` ou `cancelado`
- informar `data de vencimento`
- registrar `data de pagamento` na baixa
- exibir resumo mensal de valores pagos

### O que o modulo ainda nao faz de forma formal

- `DRE` gerencial por competencia
- `fluxo de caixa realizado` por conta financeira
- `fluxo de caixa projetado` por vencimento futuro
- `baixa parcial` com multiplos movimentos no mesmo titulo

Para a evolucao planejada desse tema, consulte o
[estudo oficial de DRE e fluxo de caixa](../09-roadmap/estudo-dre-fluxo-de-caixa.md).

## Tipos de lancamento

| Tipo | Descricao |
|---|---|
| `Receber` | entrada financeira esperada ou realizada |
| `Pagar` | saida financeira esperada ou realizada |

## Novo lancamento

| Campo | Obrigatorio | Descricao |
|---|---|---|
| `Tipo` | Sim | define se o titulo e a receber ou a pagar |
| `Descricao` | Sim | texto de identificacao do lancamento |
| `Valor` | Sim | valor do titulo |
| `Data de vencimento` | Sim | data prevista para pagamento ou recebimento |
| `Categoria` | Nao | classificacao livre do lancamento |
| `OS vinculada` | Nao | relacao com ordem de servico |
| `Status` | Sim | `pendente`, `pago` ou `cancelado` |

## Baixar lancamento

Na listagem, use a acao de `Baixa` para marcar o titulo como pago ou recebido.

Na baixa, o sistema registra:

- `data de pagamento`
- `forma de pagamento`

## Resumo exibido hoje

O resumo do modulo mostra:

- `Receitas do mes`
- `Despesas do mes`
- `Lucro`
- `Pendentes`

Importante:

- esse resumo atual e calculado com base nos lancamentos `pagos`
- por isso ele representa melhor o `caixa realizado` do que uma `DRE`
