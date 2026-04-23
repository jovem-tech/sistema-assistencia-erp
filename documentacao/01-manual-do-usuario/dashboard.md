# Manual do Usuario - Dashboard

## Visao geral

O Dashboard e a tela inicial operacional do ERP. Ele concentra indicadores rapidos, graficos e atalhos para Ordens de Servico (OS), sem exigir navegacao para outros modulos.

## Cards principais

O topo do Dashboard exibe 4 cards:

1. `OS abertas`: total de OS ainda ativas.
2. `Faturamento mes`: total financeiro do mes corrente.
3. `Equipamento entregue`: quantidade de OS no status oficial **Equipamento Entregue**.
4. `Resumo geral`: total consolidado de:
   - equipamentos cadastrados
   - clientes cadastrados
   - total de OS registradas

### Origem do valor "Faturamento mes"

O card `Faturamento mes` soma o campo `os.valor_final` das OS entregues no mes/ano atual, usando `data_entrega` como referencia.

Regra atual do ERP:

- Em bases com coluna `os.estado_fluxo`: soma apenas OS com `status = 'entregue_reparado'`.
- Em bases legadas sem `estado_fluxo`: soma OS com `status = 'entregue'`.

Consulta SQL equivalente (base atual):

```sql
SELECT COALESCE(SUM(valor_final), 0) AS faturamento_mes
FROM os
WHERE status = 'entregue_reparado'
  AND MONTH(data_entrega) = MONTH(CURRENT_DATE())
  AND YEAR(data_entrega) = YEAR(CURRENT_DATE());
```

## Graficos

### 1) OS abertas por mes (principal)

- Grafico em destaque.
- Exibe janeiro a dezembro do ano selecionado.
- Mostra quantidade de OS abertas por mes.
- Mes sem movimento aparece com valor `0`.
- O topo do card possui seletor de `Ano` com todos os anos que possuem OS registradas.
- O ano corrente continua como padrao quando existir base no periodo.
- Ao trocar o ano, o grafico e atualizado sem recarregar a pagina.

### 2) OS por status

- Grafico de distribuicao (doughnut).
- Usa macrofases quando disponiveis na estrutura `os_status`.
- Mostra leitura rapida do estado operacional atual.

### 3) Resumo financeiro (barras horizontais)

- Grafico horizontal comparando:
  - receitas
  - despesas
  - lucro
  - pendentes
- Abaixo do grafico existe um mini resumo numerico com os mesmos valores.

## Ultimas Ordens de Servico

A grade de `Ultimas Ordens de Servico` agora abre em modal:

- Botao `Visualizar` abre a OS em modal sem recarregar o dashboard.
- Botao `Nova OS` abre o formulario de criacao em modal.
- O modal possui opcao `Abrir pagina` para abrir a tela completa em nova aba.

## Responsividade

O dashboard foi ajustado para:

- celular
- tablet
- notebook
- desktop

Comportamento responsivo aplicado:

- alturas de graficos adaptadas por breakpoint
- legendas e labels ajustados para leitura em telas menores
- cards reorganizados sem quebra de layout
- modal em fullscreen no mobile
- tabela de `Ultimas Ordens de Servico` convertida para layout em blocos no smartphone (sem zoom horizontal)
- ajuste de escala para telas estreitas (320px a 390px), incluindo Galaxy S9+/S8+, Pixel e iPhone
- troca de orientacao/dispositivo recalcula os graficos automaticamente para evitar corte
- navbar superior fixa em toda a navegacao, com conteudo compensado abaixo do cabecalho
- breakpoints de ajuste fino para smartphone:
  - ate `320px` (compacto extremo)
  - ate `360px` (smartphones pequenos)
  - ate `390px` (iPhones/Android compactos)
  - ate `430px` (smartphones grandes)

## Observacoes de uso

- Os dados dos graficos sao carregados via endpoint interno `GET /admin/stats`.
- O filtro de ano da serie mensal consome `GET /admin/stats?ano=YYYY`.
- Se houver erro de rede, o dashboard mantem os cards e exibe fallback nos graficos.
- A versao oficial do ERP aparece no rodape lateral do sistema (menu esquerdo), no formato `Versao x.y.z`.
