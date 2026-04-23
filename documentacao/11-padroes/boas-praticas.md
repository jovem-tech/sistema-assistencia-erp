# Boas praticas do projeto

## PHP / Backend

### Faca
- Use `esc()` em dados vindos do banco.
- Use `csrf_field()` em formularios POST.
- Valide dados no Controller antes de salvar.
- Use `nullifyEmptyFields` para campos unicos opcionais.
- Registre acoes importantes com `LogModel::registrar()`.
- Verifique permissao com `requirePermission()` no `__construct()`.
- Use `can()` antes de renderizar botoes/links de acao.
- Em buscas de catalogo (pecas/servicos), retorne apenas registros operacionais ativos:
  - `pecas`: `ativo = 1`;
  - `servicos`: `status = 'ativo'` e `encerrado_em IS NULL`.
- Em consultas com `like` + `orLike`, agrupe as condicoes de texto antes do filtro de ativo para evitar vazamento de registros inativos.
- No fluxo de `os_itens`, saneie `peca_id`/`servico_id` para inteiro antes do insert para evitar violacao de FK.
- Permita incluir peca sem estoque no item da OS, mas registre status pendente e nao baixe estoque ate a reserva real.

### Nao faca
- Nao confiar em dados do POST sem validacao.
- Nao versionar `.env`.
- Nao usar SQL raw quando Query Builder resolve.
- Nao remover historico operacional fisicamente quando o fluxo pede encerramento logico.
- Nao commitar arquivos temporarios (`tmp_*.php`, `Thumbs.db`, `.DS_Store`).

---

## Views / Frontend

### Faca
- Use classes do design system (`glass-card`, `btn-glow`, `stat-card`).
- Marque campos opcionais com `text-muted`.
- Marque obrigatorios com `*`.
- Coloque JS especifico da pagina em `<?= $this->section('scripts') ?>`.
- Use IDs descritivos para facilitar JS e testes.
- Padronize botoes de voltar com `data-back-default`.
- Use `openDocPage()` para apontar ajuda para documentacao correta.
- Em operacoes CRUD, atualize imediatamente a UI afetada sem exigir refresh manual da pagina.
- Preserve contexto de modal, iframe embed, filtros, pagina atual e scroll util sempre que tecnicamente possivel.
- Sincronize componentes enriquecidos (`Select2`, `DataTables`, cards, badges, chips, galerias e resumos`) tanto no dado interno quanto no valor renderizado.
- Prefira AJAX, eventos customizados, callbacks e `postMessage` a recarga total da pagina.
- No lancamento de `Itens / Servicos` da OS, use Select2 com busca assincrona no catalogo para peca/servico em vez de campo textual simples.
- Configure Select2 de itens da OS com `minimumInputLength = 0` para exibir top 10 itens mais usados sem digitacao inicial.
- No template do Select2 de itens da OS, exiba metadados operacionais (estoque e valor) para acelerar decisao do tecnico.

### Nao faca
- Evite style inline fora de casos pontuais.
- Nao duplique logica que ja existe em `public/assets/js/scripts.js`.
- Evite `!important` fora de emergencia tecnica.
- Nao use `window.location.reload()` ou redirecionamento como mecanismo principal de sincronizacao de interface quando existir alternativa reativa viavel.
- Nao deixe `Select2`, listas, tabelas ou contadores com estado antigo depois de um `save`, `update` ou `delete`.

---

## Regra global de reatividade de interface (obrigatorio)

Esse padrao vale para todo o sistema e para qualquer nova implementacao.

Checklist obrigatorio:
1. Criou, editou, excluiu, vinculou ou mudou status: a interface deve refletir o novo estado imediatamente.
2. A tela nao pode depender de `F5`, reabertura de modal ou nova navegacao para exibir o valor correto.
3. Em modal, drawer, aba ou iframe embed, atualizar apenas os componentes afetados e preservar o restante do fluxo.
4. Quando houver componentes enriquecidos, sincronizar o valor interno e o texto renderizado.
5. Em fluxos embed, usar evento dedicado ou `postMessage` para atualizar a tela pai sem perder contexto.
6. Falhas de sincronizacao devem gerar `console.error` com contexto suficiente para diagnostico.

---

## Responsividade ultra compatibilidade (obrigatorio)

Esse padrao vale para todas as telas novas e alteradas.

Breakpoints minimos:
- `<= 430px`
- `<= 390px`
- `<= 360px`
- `<= 320px`

Checklist obrigatorio:
1. Nenhum corte horizontal de pagina.
2. Cards e titulos sem truncamento visual critico.
3. Tabelas:
   - stack mobile com `data-label`, ou
   - scroll horizontal controlado.
4. Formularios sem overflow lateral.
5. Modais usaveis em smartphone.
6. Graficos recalculando em troca de viewport/orientacao.
7. Validacao manual em 320px e 360px no DevTools.

Base tecnica oficial:
- CSS: `public/assets/css/design-system/layouts/responsive-layout.css`
- JS: `public/assets/js/scripts.js`

---

## Banco de dados

Convencoes:
- Tabelas e colunas em `snake_case`.
- FK no formato `tabela_id`.
- Indices no formato `idx_tabela_coluna`.

Campos de data:
```sql
created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
```

Campos unicos opcionais devem aceitar `NULL`.

---

## Seguranca

- Senhas com `password_hash()` (`PASSWORD_BCRYPT`).
- Invalidar sessao no logout.
- Validar upload por extensao e MIME.
- Usar CSRF em POST.
- Evitar concatenacao de SQL.
- Escapar saida HTML com `esc()`.

---

## Performance e manutencao

- Em listagens grandes, usar limite/paginacao.
- Evitar `SELECT *` em queries pesadas.
- Usar carga sob demanda via AJAX para blocos pesados.
- Limpar logs periodicamente em `writable/logs/`.
- Manter `documentacao/08-correcoes/` atualizada a cada bugfix.
- Atualizar roadmap quando um item for concluido.
