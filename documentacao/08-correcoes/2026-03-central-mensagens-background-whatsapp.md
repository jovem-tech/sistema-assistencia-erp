# Melhoria de Layout: Fundo de Chat Estilo WhatsApp (Doodle)

Data: 19/03/2026
Tipo: UI/UX (Estilização)

## Alteração Realizada

A área de mensagens (`#threadMessages`) da Central de Mensagens agora possui um fundo com padrão de ícones doodle, simulando a experiência visual do WhatsApp.

## Motivação

Aumentar a imersão do usuário no ambiente de conversa e melhorar a estética do chat, tornando-o mais familiar para quem já utiliza aplicativos de mensagens.

## Mudanças Técnicas

1.  **Ativos**:
    *   Gerado e implementado o arquivo `public/assets/img/sistema/whatsapp_chat_bg.png`.
2.  **CSS**:
    *   Uso de pseudo-elemento `::before` na `.cm-msg-wrap` para aplicar o fundo sem interferir no scroll ou no conteúdo das mensagens.
    *   Configuração de opacidade sutil (`0.06`) para não prejudicar a legibilidade.
    *   **Suporte a Tema Escuro**: Adicionada regra `[data-bs-theme="dark"]` que inverte as cores do padrão e utiliza um fundo verde escuro profundo (`#0b141a`).
3.  **Z-Index**:
    *   Garantido que as linhas de mensagem (`.cm-msg-row`) e estados vazios fiquem em uma camada superior (`z-index: 2`).

## Arquivos Afetados

- `app/Views/central_mensagens/index.php`: Adição dos estilos de background.
- `public/assets/img/sistema/whatsapp_chat_bg.png`: Nova imagem de padrão.
- `documentacao/06-modulos-do-sistema/central-de-mensagens.md`: Atualização das especificações de UI.
