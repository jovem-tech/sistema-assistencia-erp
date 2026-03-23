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
