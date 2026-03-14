# Padrão de Commits

## Formato

```
<tipo>(<escopo>): <descrição curta>

[corpo opcional — o que e por que, não o como]

[rodapé opcional — referências, breaking changes]
```

---

## Tipos

| Tipo | Quando usar |
|------|-------------|
| `feat` | Nova funcionalidade |
| `fix` | Correção de bug |
| `refactor` | Melhoria de código sem mudar comportamento |
| `style` | Mudanças visuais/CSS sem lógica |
| `docs` | Documentação |
| `chore` | Tarefas de manutenção (configs, deps) |
| `perf` | Melhoria de performance |
| `test` | Adição ou correção de testes |

---

## Exemplos Reais do Projeto

```bash
feat(clientes): adicionar campos de contato adicional (nome e telefone)

fix(clientes): corrigir erro CPF duplicado ao salvar sem CPF
# Campos únicos agora são convertidos para NULL quando vazios

feat(cep): implementar autopreenchimento de endereço via ViaCEP
# Funciona em clientes, fornecedores e modal de OS

fix(show-cliente): corrigir chaves tipo_nome, marca_nome, modelo_nome

docs: estruturar documentação profissional em 12 seções

refactor(scripts): centralizar busca de CEP em handleCepLookup()

style(forms): destacar campos obrigatórios vs opcionais com text-muted

feat(equipamentos): integrar Cropper.js para edição de fotos
```

---

## Escopo (opcional mas recomendado)

Use o nome do módulo como escopo:
`clientes`, `os`, `equipamentos`, `estoque`, `financeiro`, `auth`, `api`, `ui`, `docs`, `db`

---

## Boas Práticas

- ✅ Use o imperativo: "adicionar", "corrigir", "remover" (não "adicionado")
- ✅ Seja objetivo: máximo 72 caracteres na primeira linha
- ✅ Referencie issues quando existir: `fix(os): corrigir status (#42)`
- ❌ Evite: "update", "changes", "misc", "wip" sem contexto
- ❌ Não commite arquivos temporários (`tmp_*.php`, `.DS_Store`, `Thumbs.db`)
