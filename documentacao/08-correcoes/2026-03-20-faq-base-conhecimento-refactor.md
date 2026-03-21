# Log de Implementação: FAQ e Base de Conhecimento Dinâmica

**Data:** 20 de Março de 2026
**Status:** Concluído
**Módulo:** Central de Mensagens / FAQ

## Objetivos Alcançados

1.  **Refatoração de Layout**: O módulo de FAQ agora compartilha a mesma identidade visual e de usabilidade do módulo de Respostas Rápidas, com layout bilateral e design glassmorphism.
2.  **Edição em Modal**: Implementamos o modal de edição para garantir foco e evitar erros de navegação.
3.  **FAQ Dinâmico**: O chatbot agora processa tags dinâmicas dentro das respostas de FAQ.

## Mudanças Técnicas

### Frontend (`faq.php`)
- Implementação de modal Bootstrap 5.
- Seletor de Variáveis Dinâmicas (`{{cliente_nome}}`, `{{equipamento}}`, etc.) com inserção automática no cursor.
- Melhoria no preview da listagem com suporte a truncamento de texto e badges de categoria.

### Chatbot Engine (`ChatbotService.php`)
- No método `processInbound`, adicionamos o processamento de contexto para origens do tipo `faq`.
- Agora, se uma resposta de FAQ contiver a tag `{{numero_os}}`, o bot buscará a OS principal vinculada ao cliente e preencherá a informação antes de enviar a mensagem no WhatsApp.

## Exemplo de Aplicação
Se o bot detectar a intenção "horario" (via FAQ):
- Resposta Cadastrada: *"Olá {{cliente_nome}}, o status da sua OS {{numero_os}} é {{status}}. Nosso horário é das 08:00 às 18:00."*
- Resposta Enviada: *"Olá Carlos, o status da sua OS OS20260012 é Em Reparo. Nosso horário é das 08:00 às 18:00."*

## Arquivos Afetados
- `app/Views/central_mensagens/faq.php`
- `app/Services/ChatbotService.php`
- `documentacao/06-modulos-do-sistema/central-de-mensagens.md`
