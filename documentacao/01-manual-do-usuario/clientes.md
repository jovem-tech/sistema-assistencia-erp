# Manual do Usuário — Clientes

## 📋 Visão Geral

O módulo de Clientes centraliza o cadastro de pessoas físicas e jurídicas que utilizam os serviços da assistência técnica.

---

## 🔍 Listagem de Clientes

**Caminho:** COMERCIAL → Pessoas → Clientes

A tela exibe uma tabela com todos os clientes cadastrados contendo:
- Nome / Razão Social
- CPF/CNPJ
- Telefone
- Cidade/UF
- Total de OS abertas
- Ações (Visualizar, Editar, Excluir)

Use a **barra de busca** para filtrar por nome, CPF, telefone ou e-mail.

---

## ➕ Cadastrar Novo Cliente

**Caminho:** Clientes → botão `+ Novo Cliente`

### Campos Obrigatórios *(marcados com \*)*
| Campo | Descrição |
|-------|-----------|
| **Nome / Razão Social \*** | Nome completo ou razão social da empresa |
| **Telefone 1 \*** | Telefone principal de contato |

### Campos Opcionais
| Campo | Descrição |
|-------|-----------|
| Tipo de Pessoa | Física (padrão) ou Jurídica |
| CPF / CNPJ | Documento do cliente |
| RG / IE | Identidade ou inscrição estadual |
| Telefone 2 | Telefone alternativo |
| Email | Endereço de e-mail |

### Contato Adicional *(Opcional)*
| Campo | Descrição |
|-------|-----------|
| Nome do Contato | Nome de terceiro (Ex: Esposa, Filho, Vizinho) |
| Telefone do Contato | Telefone do contato alternativo |

### Endereço *(Opcional — preenchimento automático por CEP)*
| Campo | Descrição |
|-------|-----------|
| CEP | Ao digitar, preenche endereço automaticamente (ViaCEP) |
| Endereço | Rua / Avenida |
| Número | Número do imóvel |
| Complemento | Apto, Sala, etc. |
| Bairro | Bairro |
| Cidade | Cidade |
| UF | Estado (preenchido pelo CEP) |

> 💡 **Dica de CEP:** Ao digitar o CEP completo, o sistema consulta a API ViaCEP e preenche Endereço, Bairro, Cidade e UF automaticamente. O cursor vai direto para o campo **Número**.

---

## 🔎 Visualizar Detalhes do Cliente

Clique no ícone de olho 👁️ na listagem para ver o perfil completo do cliente:

- **Dados cadastrais** (tipo, CPF, telefones, endereço)
- **Contato adicional** (quando cadastrado)
- **Histórico de Ordens de Serviço** — Todas as OS do cliente
- **Equipamentos vinculados** — Aparelhos cadastrados

---

## 📋 Cadastro Rápido (durante abertura de OS)

Na tela de **Nova OS**, clique no botão `+ Novo` ao lado do campo **Cliente** para abrir um modal de cadastro rápido. Após salvar, o cliente já aparece selecionado no formulário da OS.

---

## 📥 Importação em Massa (CSV)

**Caminho:** Clientes → botão `Importar CSV`

1. Baixe o **Modelo CSV** clicando em "Baixar Modelo"
2. Preencha o arquivo seguindo os cabeçalhos
3. Envie o arquivo na tela de importação
4. O sistema valida e importa os registros válidos

> ⚠️ Linhas sem **Nome** ou **Telefone 1** serão ignoradas.

---

## ❌ Excluir Cliente

> **Atenção:** A exclusão é permanente. Clientes com OS vinculadas não devem ser excluídos — use observações para registrar situações especiais.
