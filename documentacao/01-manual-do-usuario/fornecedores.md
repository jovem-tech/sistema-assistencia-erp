# Manual do UsuÃ¡rio â€” Fornecedores

## ðŸ“‹ VisÃ£o Geral

O mÃ³dulo de Fornecedores centraliza parceiros e contatos comerciais usados no estoque de peÃ§as.

---

## ðŸ§­ NavegaÃ§Ã£o
**Caminho:** COMERCIAL â†’ Pessoas â†’ Fornecedores

---

## âž• Cadastrar Fornecedor

Campos principais:
- **Tipo de Pessoa:** JurÃ­dica (CNPJ) ou FÃ­sica (CPF)
- **Nome Fantasia / Apelido** (obrigatÃ³rio)
- **Telefone 1** (obrigatÃ³rio)
- **Email** e **EndereÃ§o** (opcionais)

Quando o cadastro estiver em **Pessoa JurÃ­dica**, ao informar um **CNPJ vÃ¡lido** o sistema tenta preencher automaticamente:
- razÃ£o social;
- nome fantasia;
- inscriÃ§Ã£o estadual, quando disponÃ­vel no provedor pÃºblico;
- e-mail;
- telefones;
- CEP e endereÃ§o.

Se algum dado nÃ£o for encontrado ou o provedor pÃºblico estiver indisponÃ­vel, o preenchimento manual continua liberado normalmente.

---

## âœï¸ Editar / Atualizar
Na listagem, clique em **Editar** para ajustar dados e manter o cadastro atualizado.

---

## ðŸ”’ Status
O fornecedor pode ser marcado como **Inativo** sem excluir o histÃ³rico.

Ao editar um fornecedor, o switch **Fornecedor Ativo no Sistema** salva corretamente os dois estados:
- ligado: fornecedor ativo;
- desligado: fornecedor inativo.

Isso permite retirar o fornecedor das operacoes futuras sem apagar o cadastro.

---

## ðŸ—‘ï¸ ExclusÃ£o
Use exclusÃ£o apenas quando necessÃ¡rio. Registros vinculados a histÃ³rico operacional devem preferir inativaÃ§Ã£o.
Ao clicar em **Excluir**, o sistema abre uma confirmacao visual antes de prosseguir. Esse fluxo agora respeita corretamente a rota do botao, evitando navegacao quebrada para enderecos como `/undefined`.
