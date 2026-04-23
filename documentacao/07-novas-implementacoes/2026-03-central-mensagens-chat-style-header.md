# Interface Moderna da Central de Mensagens (Estilo WhatsApp/CRM)

**Data:** 19 de Março de 2026
**Status:** Concluído
**Tipo:** Refatoração de UI/UX

---

## 🎯 Objetivo
Migrar os botões de ações globais (**Sincronizar** e **Nova Conversa**) do topo da página para o header do thread de conversa. O objetivo é reduzir a poluição visual, otimizar o espaço e colocar as ações próximas do contexto onde são utilizadas, seguindo padrões modernos de ferramentas de chat (WhatsApp Web, Zendesk, Intercom).

## 🛠️ Mudanças Implementadas

### 1. Novo Design do Header
O header da conversa (`.card-header`) foi reestruturado para ser o centro de controle da thread ativa:
- **Esquerda**: Identificação do cliente/thread e botão de navegação mobile.
- **Direita** (`.cm-thread-tools`): Agrupamento de ícones de ação compactos.
  - 🔄 **Sincronizar**: Agora um ícone minimalista com tooltip (Título).
  - ➕ **Nova Conversa**: Botão de ação rápida para iniciar chats.
  - 📃 **Contexto**: Mantido o acesso lateral aos dados do cliente/OS.
  - 🏷️ **Status**: Badge de estado da conversa.

### 2. Responsividade Inteligente
Para telas menores (mobile), o layout se adapta automaticamente:
- Botões individuais são ocultados para não apertar o título.
- Um menu de contexto (**três pontos verticais**) é exibido, contendo as mesmas ações (`btnSyncInboundMobile` e `btnNovaConversaMobile`).
- Preservada a compatibilidade com todos os eventos de clique do Javascript legado.

### 3. CSS e Estética
- **Botões Circulares/Quadrados**: Definido tamanho fixo (32px) para uniformidade dos ícones.
- **Gap-2**: Alinhamento consistente entre os elementos.
- **Tooltips**: Uso do atributo `title` nativo para guiar o usuário sem texto fixo ocupando espaço.

## 📁 Arquivos Alterados
- `app/Views/central_mensagens/index.php`: Reestruturação do HTML do header e remoção do bloco `.cm-page-actions`. Inclusão de novas regras de media-query no bloco de estilo interno.

## ✅ Benefícios
- **Header mais limpo**: Eliminação de botões grandes que ocupavam espaço vertical precioso.
- **UX Contextual**: Sincronizar mensagens agora está no mesmo nível visual das próprias mensagens.
- **Foco no Thread**: Menos distração fora do card principal de conversa.

---
*Documentação gerada conforme as diretrizes de AGENTS.md.*
