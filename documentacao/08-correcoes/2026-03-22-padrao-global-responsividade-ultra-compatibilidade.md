# 2026-03-22 - Padrao global de responsividade ultra compatibilidade

## Objetivo

Aplicar um padrao unico de responsividade para todo o ERP, cobrindo todas as paginas com foco em smartphone (inclusive 320px e 360px), tablet e desktop.

## Escopo tecnico aplicado

### CSS global
Arquivo atualizado:
- `public/assets/css/design-system/layouts/responsive-layout.css`

Melhorias principais:
- bloqueio de overflow horizontal global;
- containers, rows e colunas com `min-width: 0` para evitar corte lateral;
- midias (`img`, `video`, `canvas`, `iframe`) limitadas a `max-width: 100%`;
- padrao de scroll horizontal controlado para tabelas;
- stack mobile para tabelas comuns (nao DataTables) com `data-label`;
- breakpoints agressivos:
  - `<= 430px`
  - `<= 390px`
  - `<= 360px`
  - `<= 320px`

### JS global
Arquivo atualizado:
- `public/assets/js/scripts.js`

Melhorias principais:
- rotina global que aplica `data-label` automaticamente nas tabelas;
- wrapper automatico em `.table-responsive` para tabelas comuns;
- observador (`MutationObserver`) para manter responsividade em conteudo dinamico;
- reflow de graficos Chart.js em resize/orientation/visualViewport.

## Governanca de implementacao futura

Padrao definido como obrigatorio em:
- `AGENTS.md`
- `.agents/skills/sistema_assistencia/SKILL.md`
- `documentacao/11-padroes/boas-praticas.md`

## Validacao recomendada

1. Testar telas criticas em 320x658 e 360x740.
2. Confirmar ausencia de corte horizontal.
3. Confirmar leitura de tabelas em card/stack no mobile.
4. Confirmar grafico sem quebra ao trocar dispositivo no DevTools.

## Hotfix aplicado (rolagem vertical)

Sintoma identificado:
- em alguns fluxos mobile, a pagina ficava sem rolagem vertical.

Correcao aplicada:
- mantido bloqueio horizontal global (`overflow-x: hidden`);
- restaurada rolagem vertical padrao com:
  - `body:not(.modal-open) { overflow-y: auto !important; }`
  - `overflow-y: visible` em `app-wrapper`, `main-content` e `page-content`.
- adicionado fallback JS global para limpar estado preso de modal Bootstrap (`modal-open` sem modal visivel), removendo backdrop residual.

Resultado:
- pagina volta a rolar ate o final;
- comportamento de modal Bootstrap permanece preservado quando aberto.
