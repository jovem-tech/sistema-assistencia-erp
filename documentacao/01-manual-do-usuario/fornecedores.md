# Manual do Usuário — Fornecedores

## 📋 Visão Geral

O módulo de Fornecedores centraliza parceiros e contatos comerciais usados no estoque de peças.

---

## 🧭 Navegação
**Caminho:** COMERCIAL â†’ Pessoas â†’ Fornecedores

---

## âž• Cadastrar Fornecedor

Campos principais:
- **Tipo de Pessoa:** Jurídica (CNPJ) ou Física (CPF)
- **Nome Fantasia / Apelido** (obrigatório)
- **Telefone 1** (obrigatório)
- **Email** e **Endereço** (opcionais)

Quando o cadastro estiver em **Pessoa Jurídica**, ao informar um **CNPJ válido** o sistema tenta preencher automaticamente:
- razão social;
- nome fantasia;
- inscrição estadual, quando disponível no provedor público;
- e-mail;
- telefones;
- CEP e endereço.

Se algum dado não for encontrado ou o provedor público estiver indisponível, o preenchimento manual continua liberado normalmente.

---

## âœï¸ Editar / Atualizar
Na listagem, clique em **Editar** para ajustar dados e manter o cadastro atualizado.

---

## ðŸ”’ Status
O fornecedor pode ser marcado como **Inativo** sem excluir o histórico.

Ao editar um fornecedor, o switch **Fornecedor Ativo no Sistema** salva corretamente os dois estados:
- ligado: fornecedor ativo;
- desligado: fornecedor inativo.

Isso permite retirar o fornecedor das operacoes futuras sem apagar o cadastro.

---

## 🗑️ Exclusão
Use exclusão apenas quando necessário. Registros vinculados a histórico operacional devem preferir inativação.
Ao clicar em **Excluir**, o sistema abre uma confirmacao visual antes de prosseguir. Esse fluxo agora respeita corretamente a rota do botao, evitando navegacao quebrada para enderecos como `/undefined`.
