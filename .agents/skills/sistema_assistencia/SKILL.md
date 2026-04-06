---
name: sistema_assistencia
description: "Padroes, arquitetura e convencoes do Sistema de Assistencia Tecnica (CodeIgniter 4 + Bootstrap 5)."
---

# Skill: Sistema de Assistencia Tecnica

## Arquitetura e stack
- Backend: PHP 8+ com CodeIgniter 4.
- Banco: MySQL/MariaDB.
- Frontend: Bootstrap 5.3, jQuery e CSS proprio.
- Layout base: `app/Views/layouts/main.php`, `sidebar.php`, `navbar.php`.
- Design system principal: `public/assets/css/estilo.css` e `public/assets/css/design-system/`.

## Estrutura importante
- Controllers: `app/Controllers/`
- Models: `app/Models/`
- Views: `app/Views/`
- Helpers globais: `app/Helpers/`
- JS global: `public/assets/js/scripts.js`
- Responsividade global: `public/assets/css/design-system/layouts/responsive-layout.css`

## Regras tecnicas obrigatorias
1. Reutilizar estrutura existente antes de criar fluxo novo.
2. Manter separacao Controller/Service/Model/View.
3. Evitar duplicacao de CSS e JS por pagina quando o comportamento for global.
4. Respeitar padrao de permissao (`can`, `canModule`, `requirePermission`).
5. Atualizar documentacao em `documentacao/` em toda mudanca de codigo.
6. Toda alteracao de UI que criar, editar, remover ou reordenar dados deve refletir imediatamente na interface sem exigir refresh manual da pagina.
7. Sempre que a alteracao acontecer dentro de modal, iframe embed, drawer, aba ou fluxo AJAX, preservar o contexto atual do usuario e atualizar apenas os componentes afetados.
8. Select2, DataTables, cards-resumo, badges, listas, galerias, comboboxes dependentes e contadores devem ser sincronizados logo apos sucesso de operacoes CRUD.
9. Evitar redirecionamento ou reload completo como mecanismo principal de sincronizacao quando houver fluxo reativo viavel com AJAX, evento, callback ou `postMessage`.
10. Em novas implementacoes, padronizar esse comportamento como regra global do sistema, nao como excecao de modulo.

## Regra global de reatividade de interface (obrigatorio)

Esse padrao vale para todo o sistema e para qualquer nova implementacao.

Checklist obrigatorio:
1. Salvou, editou, excluiu ou vinculou dados: a tela deve refletir o novo estado imediatamente.
2. Nao depender de `F5`, recarga manual ou reabertura de modal para exibir o dado correto.
3. Manter modal pai, filtros, pagina atual da tabela, scroll util e contexto de preenchimento sempre que tecnicamente possivel.
4. Se houver componente enriquecido (`Select2`, `DataTables`, galerias, chips, resumo lateral, badges), sincronizar tanto o dado interno quanto o texto renderizado.
5. Quando existir iframe embed, notificar a janela pai com evento dedicado (`postMessage`) para atualizar apenas o necessario.
6. Em falha de sincronizacao reativa, registrar `console.error` com contexto suficiente para diagnostico.

## Padrao de responsividade ultra compatibilidade (obrigatorio)

Toda implementacao de UI deve funcionar sem quebra em:
- `<= 430px`
- `<= 390px`
- `<= 360px`
- `<= 320px`

Checklist obrigatorio:
1. Sem corte horizontal da pagina.
2. Cards, titulos e botoes legiveis em telas pequenas.
3. Tabelas:
   - stack mobile com `data-label` quando aplicavel; ou
   - scroll horizontal controlado com `.table-responsive`.
4. Formularios e modais sem estouro lateral.
5. Graficos com reflow ao trocar orientacao/dispositivo.
6. Validacao final em DevTools nos tamanhos 320px e 360px.

Implementacao padrao:
- CSS global no arquivo de responsividade.
- Ajustes de tabela e reflow de graficos no JS global.

## Controle de versao e release (obrigatorio)

Regra operacional para qualquer alteracao no sistema:
1. Trabalhar em branch dedicada com prefixo `codex/` (ou branch de feature definida pela equipe).
2. Nao fazer commit/push sem autorizacao explicita do usuario.
3. Seguir padrao de commit claro e rastreavel:
   - `feat(modulo): ...`
   - `fix(modulo): ...`
   - `refactor(modulo): ...`
   - `docs(modulo): ...`
4. Quando houver release funcional:
   - atualizar `app/Config/SystemRelease.php` com versao SemVer (`MAJOR.MINOR.PATCH`);
   - manter sincronia entre versao exibida no rodape e versao de release;
   - atualizar obrigatoriamente `documentacao/07-novas-implementacoes/historico-de-versoes.md`;
   - criar tag git no padrao `vMAJOR.MINOR.PATCH` quando autorizado.
5. Se existir override de versao em banco (`configuracoes.sistema_versao`), validar consistencia com a versao de codigo antes de publicar.
6. Antes de deploy:
   - revisar `git status`;
   - validar migracoes pendentes;
   - validar changelog/documentacao em `documentacao/`;
   - confirmar plano de rollback.
7. Depois de deploy:
   - validar dashboard, OS, atendimento WhatsApp e uploads;
   - registrar no historico de implementacao/correcao da documentacao.
