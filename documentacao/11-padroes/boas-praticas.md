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

### Nao faca
- Evite style inline fora de casos pontuais.
- Nao duplique logica que ja existe em `public/assets/js/scripts.js`.
- Evite `!important` fora de emergencia tecnica.

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

