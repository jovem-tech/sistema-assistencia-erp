# 2026-03-22 - Dashboard responsivo com modal de OS

## Objetivo

Evoluir o dashboard (`/dashboard`) para um padrao mais profissional, com:

- graficos responsivos em todos os breakpoints
- card de `Equipamento entregue` no lugar de `Em triagem`
- card consolidado com totais de equipamentos, clientes e OS
- grafico principal anual de OS abertas (jan-dez)
- resumo financeiro em barras horizontais
- abertura de `Visualizar OS` e `Nova OS` em modal, sem redirecionar a pagina principal

## Arquivos alterados

- `app/Controllers/Admin.php`
- `app/Controllers/Os.php`
- `app/Views/admin/dashboard.php`
- `app/Views/os/form.php`
- `app/Views/os/show.php`
- `app/Views/layouts/embed.php` (novo)
- `public/assets/css/design-system/layouts/dashboard-layout.css`
- `documentacao/01-manual-do-usuario/dashboard.md`
- `documentacao/05-api/rotas.md`

## Ajustes tecnicos

### Backend (dashboard)

No `Admin::index`:

- contagem do card `Equipamento entregue` agora usa o status oficial por nome (`Equipamento Entregue`) quando tabela `os_status` existe.
- adicionados totais consolidados para:
  - equipamentos
  - clientes
  - OS
- exposto `status_entregue_codigo` para link de filtro.

No `Admin::stats`:

- incluido dataset `os_abertas_ano` (jan-dez) com zeros para meses sem dados.
- incluido `ano_referencia`.
- incluido `resumo_financeiro` para grafico horizontal.
- mantido `faturamento` para compatibilidade com outras telas/consumos legados.

### Modal de OS no dashboard

Criado fluxo com iframe em modal Bootstrap no dashboard:

- acao `Visualizar` abre `/os/visualizar/{id}?embed=1`
- acao `Nova OS` abre `/os/nova?embed=1`
- botao opcional `Abrir pagina` leva para rota completa sem embed.

### Modo embed de OS

No `Os` controller:

- adicionado helper interno `isEmbedRequest()`.
- `create`, `show` e `edit` passam `layout` dinamico (`layouts/embed` ou `layouts/main`).
- `store` e `update` preservam retorno com `?embed=1` quando o fluxo veio de modal.

Nova view de layout:

- `app/Views/layouts/embed.php` carrega apenas o conteudo da pagina (sem shell completo).

Views adaptadas:

- `os/form.php` e `os/show.php` aceitam layout dinamico.
- em embed, botoes de voltar da shell principal nao sao exibidos.
- formularios e links principais preservam `?embed=1`.

## Resultado esperado

- Dashboard mais legivel e consistente em mobile/tablet/desktop.
- Menor friccao operacional ao abrir OS recentes e criar nova OS.
- Melhor leitura de indicadores estrategicos no topo e no grafico principal anual.

## Ajuste complementar de responsividade (smartphones)

Ajustes adicionais aplicados para telas pequenas (principalmente 320px a 390px):

- refinado cabecalho do dashboard para quebrar linha sem cortar botao de ajuda;
- ajuste dinamico de legendas/ticks dos graficos por largura de tela;
- abreviacao de valores no eixo financeiro em telas pequenas;
- tabela de `Ultimas OS` convertida para layout em blocos no mobile (com `data-label`, sem truncamento lateral);
- re-render de graficos em `resize`, `orientationchange` e `visualViewport.resize` para evitar quebra ao trocar dispositivo no DevTools;
- reducao de alturas minimas de grafico em dispositivos muito estreitos.
- adicionado ajuste progressivo por faixa:
  - `<=430px`
  - `<=390px`
  - `<=360px`
  - `<=320px`
