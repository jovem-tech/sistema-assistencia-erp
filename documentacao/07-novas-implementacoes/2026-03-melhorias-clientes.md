# Novas Implementações — Março 2026

## 📅 13/03/2026 — Autopreenchimento de CEP (ViaCEP)

**Desenvolvedor:** Sistema / IA Antigravity

### Funcionalidade
Ao digitar o CEP em qualquer formulário de cadastro, o sistema consulta automaticamente a API ViaCEP e preenche os campos de endereço.

### Comportamento
- Gatilho ao completar 8 dígitos (mask `onComplete`) ou ao sair do campo (`blur`)
- Spinner de loading dentro do campo durante a consulta
- Foco automático no campo **Número** após preenchimento
- Mensagem `"CEP não encontrado"` + limpeza do campo se inválido
- Silencioso se API indisponível (permite preenchimento manual)

### Arquivos Modificados
- `public/assets/js/scripts.js` — Função `handleCepLookup()` centralizada
- `app/Views/clientes/form.php` — Classes `.js-logradouro`, `.js-bairro`, etc.
- `app/Views/os/form.php` — Modal de cadastro rápido atualizado
- `app/Views/fornecedores/form.php` — Removido script local, usa global

### Módulos com CEP Ativo
- ✅ Clientes (formulário completo)
- ✅ Fornecedores (formulário completo)
- ✅ Modal de Cadastro Rápido de Cliente (na OS)

### Reutilização Futura
Basta adicionar a classe `mask-cep` no input de CEP e as classes utilitárias nos campos destino:
- `.js-logradouro` → Endereço
- `.js-bairro` → Bairro
- `.js-cidade` → Cidade
- `.js-uf` → UF
- `.js-numero` → Número (foco automático)

---

## 📅 13/03/2026 — Campos de Contato Adicional no Cliente

### Funcionalidade
Permite registrar um contato alternativo no cadastro do cliente (esposa, filho, vizinho, etc.).

### Campos Adicionados
- `nome_contato` VARCHAR(100) — Nome do contato
- `telefone_contato` VARCHAR(20) — Telefone do contato

### Arquivos Modificados
- `app/Models/ClienteModel.php` — `allowedFields` atualizado
- `app/Views/clientes/form.php` — Nova seção "Contato Adicional"
- `app/Views/clientes/show.php` — Exibe contato se preenchido
- `app/Views/os/form.php` — Modal de cadastro rápido atualizado
- `tmp_update_clientes.php` — Script de migração DB (executado)

---

## 📅 13/03/2026 — Clareza de Campos Obrigatórios

### Problema
Não havia distinção visual clara entre campos obrigatórios e opcionais no formulário de clientes.

### Solução
- Labels de campos obrigatórios: texto normal + `*`
- Labels de campos opcionais: `text-muted` + texto `(Opcional)` quando necessário
- Apenas **Nome** e **Telefone 1** são obrigatórios

---

## 📅 13/03/2026 — Fix: Erro "CPF já existe" ao salvar cliente sem CPF

### Problema
O banco de dados tem `UNIQUE` em `cpf_cnpj`. Ao enviar o formulário com CPF vazio, a string `""` era salva, causando conflito no segundo cliente sem CPF.

### Solução
Hook `nullifyEmptyFields` no `ClienteModel` converte strings vazias para `NULL` antes de qualquer `INSERT` ou `UPDATE`. Como bancos de dados permitem múltiplos `NULL` em colunas UNIQUE, o conflito foi eliminado.

### Arquivo Modificado
- `app/Models/ClienteModel.php`
