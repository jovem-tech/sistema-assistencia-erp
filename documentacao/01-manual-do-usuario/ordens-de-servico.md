# Manual do Usuário — Ordens de Serviço

## 📋 Visão Geral

A Ordem de Serviço (OS) é o **documento central** do sistema. Registra todo o ciclo de vida de um reparo: da entrada do equipamento à entrega ao cliente.

---

## 📊 Status das OS

| Status | Descrição |
|--------|-----------|
| 🟡 **Aguard. Análise** | OS aberta, aguardando técnico avaliar |
| 🟠 **Aguard. Orçamento** | Técnico diagnosticou, orçando peças/serviços |
| 🔵 **Aguard. Aprovação** | Orçamento enviado ao cliente aguardando resposta |
| 🟢 **Aprovado** | Cliente aprovou, reparo autorizado |
| 🔴 **Reprovado** | Cliente não aprovou o orçamento |
| 🔧 **Em Reparo** | Técnico executando o reparo |
| ⏳ **Aguard. Peça** | Reparo pausado aguardando peça |
| ✅ **Pronto** | Reparo concluído, aguardando retirada |
| 📦 **Entregue** | Equipamento devolvido ao cliente |
| ❌ **Cancelado** | OS cancelada |

---

## ➕ Abrir Nova OS

**Caminho:** Menu → Ordens de Serviço → `+ Nova OS`

### Passo a Passo

O cadastro na abertura está organizado em abas para facilitar o preenchimento:
- **Dados** (Cliente, Equipamento, Técnico, Prioridade, Datas, Status e Acessórios)
- **Relato e Defeitos**
- **Fotos**

**1. Selecionar Cliente**
- Busque pelo nome, CPF ou telefone no campo Select2
- Não encontrou? Clique em `+ Novo` para cadastrar rapidamente

**2. Selecionar Equipamento**
- Após selecionar o cliente, os equipamentos vinculados aparecem automaticamente
- Não tem equipamento? Clique em `+ Novo` para cadastrar.
- **Dica**: O novo modal de cadastro de equipamento dentro da OS agora é organizado por **Abas** e inclui o **Seletor Profissional de Cores** e os **Atalhos de Acessórios**, exatamente como no cadastro principal.

**3. Informações da OS**
| Campo | Obrigatório | Descrição |
|-------|-------------|-----------|
| Técnico Responsável | Não | Seleciona apenas funcionários ativos com cargo "Técnico" |
| Prioridade | Sim | Baixa / Normal / Alta / Urgente |
| Data de Entrada | Sim | Preenchida automaticamente |
| Previsão de Entrega | Não | Data prometida ao cliente (pode usar prazo em dias) |
| Status | Sim | Estado atual da OS |
| Relato do Cliente | Sim | O que o cliente descreveu do problema |
| Acessórios | Não | Itens recebidos junto ao equipamento |

### Registro guiado de Acessórios

O painel “Acessórios e Componentes (na entrada)” agora inclui **botões rápidos** para padronizar o registro de itens comuns. Cada clique abre um mini formulário e salva o texto com formato consistente (ex: “Chip final 123456”, “Capinha celular rosa”, “Cabo USB-C”). Os botões disponíveis são:

- `+ CHIP` (solicita os últimos seis dígitos)
- `+ CAPINHA CELULAR` (abre seletor de cor e campo de apoio)
- `+ CAPA`, `+ MOCHILA` e `+ BOLSA NOTEBOOK` (cadastram o objeto e registram cor quando informada)
- `+ CABO` (tipos rápidos: USB-C, Micro USB, Lightning, HDMI, Cabo de força e opção `Outro` com campo manual)
- `+ CARREGADOR` (define automaticamente o tipo de equipamento associado)
- `+ OUTRO ACESSÓRIO` (permite descrição livre para itens fora da lista)

Cada acessório aparece em uma lista abaixo com ações de editar, remover e adicionar fotos, além de mostrar miniaturas. Ao adicionar imagens são exibidos previews com botão de remoção e clique para ampliar em modal, exatamente como os uploads de equipamento.

Regras de escrita da cor:
- O sistema não usa mais “sem cor”.
- O texto não usa hífen para separar descrição e cor.
- Quando houver cor, é salvo e exibido apenas o **nome da cor** (sem código hexadecimal), por exemplo: `Capinha celular Azul Céu`.
- Quando não houver cor, o item fica apenas com a descrição base, por exemplo: `Capinha celular` ou `Mochila`.
- O seletor de cor oferece **atalhos rápidos** com 12 cores comuns: Preto, Marrom, Azul claro, Verde claro, Rosa, Vermelho, Laranja, Amarelo, Verde, Azul, Roxo/Violeta e Branco.
- Em telas maiores, os atalhos de cores ficam alinhados **lado a lado**.
- Em telas menores, os atalhos ficam agrupados no botão **Cores rápidas**.

Essa escolha gera:

- um `acessorio_data` estruturado no formulário para persistência
- registros nas novas tabelas `acessorios_os` e `fotos_acessorios`
- arquivos armazenados em `uploads/acessorios/OS_<Número da OS>/acessorio_<Número>_<Sequência>.jpg`

Esse histórico visual complementa o relato do cliente e evita dúvidas sobre o que foi entregue.

**4. Fotos de Entrada**
- Registre fotos do estado físico do equipamento ao recebê-lo
- O upload agora usa o mesmo modelo do cadastro de equipamentos (área central com drag/drop, botões de galeria/câmera e visualização direta das miniaturas).
- A seção de acessórios ganhou botões rápidos; ao clicar em cada botão o sistema abre campos complementares (chip, cor da capinha, tipo de cabo etc.) e lista os itens criados com controles de edição/remoção.
- Botões: `📷 Tirar Foto` (câmera) ou `🖼️ Galeria` (arquivo)
- Após escolher, um **editor de imagem** abre para recortar e ajustar a foto

**5. Defeitos Comuns**
- Após selecionar o equipamento, a **Base de Defeitos** carrega automaticamente
- Marque os defeitos reportados pelo cliente

**6. Prazo de Entrega**
- Use o menu de prazos (1, 3, 7 e 30 dias) para preencher automaticamente a data de previsão

**7. Peças e Orçamento**
- Esta etapa não aparece na abertura da OS.
- Peças, serviços e valores são lançados após a OS estar criada, na tela de visualização/edição.
- **Forma de Pagamento**: pode ser registrada na edição da OS para facilitar o faturamento.

---

## Recursos de Apoio na Abertura
- **Resumo da OS (lateral)**: mostra cliente, equipamento, técnico, prioridade, status, datas e contadores de fotos/defeitos.
- **Indicadores de preenchimento**: cada linha do resumo exibe ✔️ ou ❌ conforme o campo esteja completo.
- **Fotos do equipamento (lateral)**: exibem a foto principal e miniaturas assim que um equipamento é selecionado.
- **Miniaturas laterais clicáveis** abrem o zoom da mesma maneira que o card de fotos central.
- **Cor do equipamento**: quando não há foto, o quadro usa a cor do equipamento; com foto, a cor aparece abaixo.
- **Seleção inteligente**: quando existe apenas 1 equipamento do cliente, ele pode ser selecionado automaticamente.
- **Rascunho automático**: durante a criação, o sistema salva um rascunho localmente e permite restaurar ou descartar.
- **Limpar rascunho**: botão no rodapé do formulário remove o rascunho salvo.

## ✏️ Editar / Atualizar OS

**Campos adicionais disponíveis na edição:**
- **Diagnóstico Técnico** — O que o técnico encontrou
- **Solução Aplicada** — O que foi feito
- **Mão de Obra (R$)** — Valor do serviço
- **Peças (R$)** — Calculado automaticamente dos itens
- **Desconto (R$)** — Desconto concedido
- **Valor Final (R$)** — Total calculado automaticamente
- **Garantia (dias)** — Prazo de garantia do reparo
- **Observações Internas** — Notas para a equipe (não aparecem no orçamento)
- **Observações para o Cliente** — Aparece na impressão

---

## 🖨️ Imprimir OS / Orçamento

Na tela de visualização da OS, clique em **Imprimir** para gerar um documento PDF com:
- Dados do cliente e equipamento
- Relato do problema
- Diagnóstico e solução
- Valores
- Assinatura do cliente

---

## 🔗 Link de Aprovação de Orçamento

O sistema gera um link único que pode ser enviado ao cliente por WhatsApp ou e-mail. O cliente acessa, vê o orçamento e pode **Aprovar** ou **Recusar** sem precisar de login.

---

## 📊 Histórico de OS (Listagem)

Filtros disponíveis:
- Por técnico
- Por status
- Por período (data de entrada)
- Por cliente

Use o **DataTables** para busca rápida por número da OS, cliente ou equipamento.
