# Boas Práticas do Projeto

## PHP / Backend

### ✅ Faça
- Use `esc()` em **todos** os dados exibidos do banco na View
- Use `csrf_field()` em **todos** os formulários POST
- Valide no Controller antes de salvar
- Use `nullifyEmptyFields` para campos únicos opcionais
- Registre ações importantes com `LogModel::registrar()`
- Verifique permissão com `requirePermission()` no `__construct()` do Controller
- Use `can()` antes de exibir botões/links de ações nas Views

### ❌ Não faça
- Nunca confie em dados do POST sem validação
- Nunca versione o arquivo `.env`
- Nunca use SQL raw quando o Query Builder resolve
- Nunca delete registros com histórico operacional (use status `encerrado`)
- Nunca commite arquivos `tmp_*.php` ou scripts de migração temporários

---

## Views / Frontend

### ✅ Faça
- Use as classes do Design System: `glass-card`, `btn-glow`, `stat-card`
- Use `text-muted` para labels de campos opcionais
- Use `*` na label para campos obrigatórios
- Coloque scripts específicos de página na seção `<?= $this->section('scripts') ?>`
- Use IDs descritivos nos elementos interativos para facilitar testes e JS
- Padronize botões "Voltar" com `data-back-default` apontando para a rota do módulo (ex.: `<?= base_url('os') ?>`). O JS global usa `history.back()` quando possível e faz fallback para a rota padrão quando não há histórico.
- Quando abrir a documentação via Ajuda, passe `?from=<url>` para garantir retorno correto na Central de Documentação.

### ❌ Não faça
- Não coloque `<style>` inline em quantidade — use classes do `estilo.css`
- Não duplique lógica JS já existente no `scripts.js` global
- Evite `!important` em CSS — prefira especificidade adequada
- Não use `href` fixo como única forma de voltar em fluxos multi-etapas (isso quebra o histórico real do usuário)

---

## Banco de Dados

### Convenções de Nomes
- Tabelas: `snake_case` plural (`clientes`, `ordens_servico`)
- Colunas: `snake_case` (`nome_razao`, `created_at`)
- Chaves estrangeiras: `tabela_id` (`cliente_id`, `grupo_id`)
- Indexes: `idx_tabela_coluna` (`idx_clientes_email`)

### Campos de Data
Sempre usar:
```sql
created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
```

### Campos Únicos Opcionais
Sempre permitir `NULL`:
```sql
cpf_cnpj VARCHAR(20) NULL DEFAULT NULL UNIQUE
-- Múltiplos NULL são permitidos em colunas UNIQUE
```

---

## Segurança

| Item | Prática |
|------|---------|
| Senhas | Sempre `password_hash()` com `PASSWORD_BCRYPT` |
| Sessões | Invalidar completamente no logout (`session()->destroy()`) |
| Uploads | Validar extensão e tipo MIME; armazenar fora do root web |
| CSRF | `csrf_field()` em todos os forms POST |
| SQL Injection | Usar Query Builder ou prepared statements — nunca string concatenation |
| XSS | `esc()` em toda saída de dados do banco para HTML |

---

## Performance

- Use `findAll()` com `limit()` em listagens grandes
- Use `select()` específico em queries com JOIN — evite `SELECT *`
- Lazy-load via AJAX para dados pesados (equipamentos por cliente, fotos)
- Cache de configurações: `ConfiguracaoModel` usa cache interno

---

## Manutenção

- Limpe `writable/logs/` periodicamente (logs crescem em produção)
- Mantenha `documentacao/08-correcoes/` atualizado a cada bug fixado
- Atualize `09-roadmap/funcionalidades-planejadas.md` ao concluir itens
- Scripts temporários (`tmp_*.php`) devem ser removidos após execução
