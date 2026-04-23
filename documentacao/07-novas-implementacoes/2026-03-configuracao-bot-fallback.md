# Nova Implementação: Mensagem de Fallback do Chatbot Editável (Março/2026)

## Descrição
Anteriormente, a mensagem enviada pelo chatbot quando não identificava uma intenção ("Recebi sua mensagem e vou encaminhar para um atendente humano...") era fixa no código-fonte. Esta melhoria moveu essa mensagem para o sistema de configurações operacionais da Central de Mensagens.

## Mudanças Técnicas
- **Controller**: `CentralMensagens::centralConfigKeys()` agora inclui `central_mensagens_bot_fallback_message`.
- **Service**: `ChatbotService::processarInbound()` consome a configuração via `get_config()`.
- **View**: Tela de Configurações da Central ganhou um campo `textarea` para edição.

## Como Usar
1. Acesse **Operacional > Central de Mensagens**.
2. Clique na aba **Configurações**.
3. Localize o campo **Mensagem de Fallback (quando o bot não entende)**.
4. Altere o texto conforme desejado e clique em **Salvar configurações**.

## Impacto
Permite que o administrador do sistema altere o tom de voz e as instruções de escalonamento para humano sem necessidade de intervenção técnica no código.
