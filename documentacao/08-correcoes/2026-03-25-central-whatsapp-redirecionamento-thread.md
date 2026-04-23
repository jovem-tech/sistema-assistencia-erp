# Correcao: abertura direta de thread da Central WhatsApp

Data: 2026-03-25

## Problema

Ao acessar diretamente a URL `GET /atendimento-whatsapp/conversa/{id}` no navegador, o sistema exibia o JSON bruto da thread em vez da interface da `Central de Mensagens`.

## Causa

Essa rota era usada internamente como endpoint AJAX da thread, mas alguns pontos do sistema ainda a tratavam como URL de navegacao normal, inclusive a busca global.

## Correcao aplicada

- `CentralMensagens::conversa()` passou a detectar acesso sem AJAX
- nesse caso, a navegacao e redirecionada para a URL canonica da tela: `GET /atendimento-whatsapp?conversa_id={id}`
- a resposta JSON da thread foi preservada para as requisicoes AJAX da propria central
- `GlobalSearchService` foi ajustado para gerar links canonicos da central, evitando abrir a rota JSON diretamente

## Resultado esperado

- abrir uma conversa a partir da busca global leva para a `Central de Mensagens` com a thread correta selecionada
- navegar diretamente para `/atendimento-whatsapp/conversa/{id}` no browser nao mostra mais JSON cru
- a tela da central continua usando a mesma rota como backend AJAX para carregar a thread
