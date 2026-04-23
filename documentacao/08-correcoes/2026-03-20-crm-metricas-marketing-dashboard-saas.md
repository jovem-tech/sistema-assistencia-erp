# Correcao: Upgrade da tela CRM > Metricas Marketing para dashboard SaaS

Data: 2026-03-20  
Escopo: `CRM -> Metricas Marketing` (`/crm/metricas-marketing`)

## Problema
A tela anterior usava uma apresentacao com barras em tabela, com baixo impacto visual e leitura lenta para decisao.

## Objetivo aplicado
Elevar a tela para padrao SaaS, com foco em:
- leitura rapida
- tendencia visual clara
- filtros operacionais
- apoio direto a decisao comercial/atendimento

## Implementacao tecnica

### 1) Backend (Controller)
Arquivo:
- `app/Controllers/Crm.php`

Melhorias:
- novo parser de periodo com presets:
  - `hoje`, `7d`, `30d`, `90d`, `mes_atual`, `mes_anterior`, `custom`
- suporte a filtros:
  - `canal`
  - `responsavel_id`
- ampliacao do payload de metricas:
  - `taxa_conversao_captados`
  - `mensagens_total`
  - `canalStats`
  - `rankingAtendimento`
  - `kpiDeltas` (comparativo 7d vs 7d)
  - `insights` automaticos
  - `serieResumoRows`
- calculo de tempo medio de primeira resposta com filtros de canal/responsavel.

Novos helpers internos:
- `resolveMarketingPeriodo(...)`
- `calculateSeriesDeltaPercent(...)`
- `sumSeriesWindow(...)`
- `buildMarketingInsights(...)`
- `formatCanalLabel(...)`

### 2) Frontend (View)
Arquivo:
- `app/Views/crm/metricas_marketing.php`

Tela reconstruida com:
- cards KPI em destaque
- deltas de tendencia
- grafico de linha real (Chart.js)
- funil visual e grafico de etapas
- grafico de origem de leads (doughnut)
- ranking por responsavel
- tabela diaria compacta
- insights automaticos
- filtros de cabecalho com chips de periodo
- configuracao de engajamento em bloco colapsavel.

Atualizacao posterior no mesmo dia:
- migracao completa dos graficos para `ApexCharts` (linha principal, donut de origem e barras de etapas do funil)
- estados vazios mais elegantes (`noData`) para recortes com baixo volume
- inclusao dos filtros premium `status` e `tag` na faixa de filtros
- preservacao de `status/tag` no POST de configuracao de engajamento (retorno sem perder contexto de filtro)
- upgrade visual das tabelas analiticas:
  - ranking com coluna de posicao, badges de volume e barra de taxa de resolucao
  - resumo diario com funil compacto `C/Q/V`, taxa de conversao do dia e barra de intensidade
  - padronizacao de estilo de tabela premium nos blocos de canais e tags
  - melhoria de responsividade das tabelas sem barra de rolagem nos blocos principais
  - reducao visual do bloco "Resumo diario (ultimos 14 pontos)" por breakpoint, com remocao de coluna opcional de intensidade em telas menores
  - fallback mobile em modo cards (`crm-table-stack`) para ranking e resumo diario, mantendo leitura sem overflow em telas pequenas

### 3) Backend adicional (filtros operacionais)
Arquivo:
- `app/Controllers/Crm.php`

Complementos aplicados:
- filtros `status` e `tag_id` incorporados na consolidacao do dashboard
- filtros propagados para:
  - conversas
  - mensagens inbound/outbound
  - tempo de primeira resposta
  - OS com origem em conversa
  - ranking por responsavel e stats por canal
- retorno de `statusOptions` e `tagOptions` para renderizacao de filtros dinamicos na view

## Compatibilidade
- Mantido o design system geral (cards, espacos, tipografia e componentes Bootstrap).
- Mantida a rota existente.
- Mantida a configuracao de engajamento temporal no mesmo endpoint.

## Resultado operacional
- tela deixa de parecer tabela com barras
- melhor leitura de tendencia e funil
- maior velocidade de diagnostico de queda/subida
- base pronta para evolucao de campanhas e performance de atendimento por responsavel
