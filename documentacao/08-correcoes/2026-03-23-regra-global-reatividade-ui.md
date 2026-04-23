# Padrao global - reatividade imediata de interface

Data: 23/03/2026

## Objetivo

Formalizar como regra permanente do sistema que qualquer operacao que altere dados visiveis na interface deve refletir imediatamente no frontend, sem depender de refresh manual da pagina.

## Regra adotada

Esse padrao agora vale para todo o sistema e para novas implementacoes.

Diretrizes:
1. Criacao, edicao, exclusao, vinculacao e mudanca de status devem atualizar a UI imediatamente apos sucesso da operacao.
2. Fluxos em modal, iframe embed, drawer e abas devem preservar contexto e evitar redirecionamento desnecessario.
3. Componentes enriquecidos precisam sincronizar dado interno e renderizacao visivel:
   - `Select2`
   - `DataTables`
   - cards de resumo
   - badges
   - chips
   - listas e galerias
   - contadores
4. A sincronizacao deve preferir AJAX, eventos customizados, callbacks e `postMessage`.
5. `window.location.reload()` e refresh total deixam de ser a estrategia padrao quando houver alternativa reativa viavel.
6. Falhas de sincronizacao devem gerar log tecnico no console com contexto suficiente para diagnostico.

## Onde a regra foi registrada

- Skill do projeto: `.agents/skills/sistema_assistencia/SKILL.md`
- Guia de padroes: `documentacao/11-padroes/boas-praticas.md`

## Resultado esperado

- O usuario nao precisa atualizar manualmente a pagina para ver dados novos ou corrigidos.
- Modais e fluxos embed permanecem abertos e consistentes.
- Novas implementacoes passam a seguir o mesmo padrao de interface reativa em todo o ERP.
