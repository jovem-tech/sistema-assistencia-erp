# Correcao: Central de Mensagens - eliminacao de 404 para midias orfas na VPS

Data: 2026-03-22  
Modulo: Central de Mensagens (`/atendimento-whatsapp`)

## Problema

Em ambiente VPS de homologacao/desenvolvimento, a base continha mensagens com referencias de anexos (video/audio/arquivo) sem o arquivo fisico correspondente em `public/uploads/central_mensagens/...`.

Isso gerava spam de erro no navegador, por exemplo:
- `GET /uploads/central_mensagens/.../gravacao_*.webm 404`
- `GET /uploads/central_mensagens/.../gravacao_*.ogg 404`

## Causa raiz

1. O frontend montava URL da midia diretamente a partir de `arquivo`/`anexo_path`.
2. O backend retornava a referencia legada do banco mesmo quando o arquivo nao existia no disco.

## Solucao aplicada

### 1) Backend defensivo no modelo de mensagens

Arquivo alterado: `app/Models/MensagemWhatsappModel.php`

- Adicionada validacao de existencia fisica da midia para cada mensagem.
- Quando o arquivo existe:
  - mantem payload normal
  - define `arquivo_disponivel = 1`
- Quando o arquivo nao existe:
  - define `arquivo_disponivel = 0`
  - preserva rastreabilidade em `arquivo_original` e `anexo_path_original`
  - retorna `arquivo = null` e `anexo_path = null` para impedir request 404.

### 2) Frontend com fallback visual

Arquivo alterado: `public/assets/js/central-mensagens.js`

- `renderMedia()` passou a respeitar `arquivo_disponivel`.
- Para `arquivo_disponivel = 0`, exibe aviso visual:
  - `Arquivo indisponivel no servidor`
- Nao tenta abrir/baixar/renderizar URL inexistente.

## Impacto esperado

- Eliminacao de erros 404 recorrentes no console da Central de Mensagens para anexos orfaos.
- Historico da conversa preservado (mensagem nao e removida).
- UX mais limpa e diagnostico mais claro para operador.

## Validacao pos-deploy

1. Abrir conversa com midias antigas nao sincronizadas.
2. Confirmar ausencia de `GET ... 404` para anexos dessa thread.
3. Confirmar exibicao do aviso `Arquivo indisponivel no servidor`.
4. Confirmar que midias validas continuam abrindo normalmente.

## Observacao operacional

Essa correcao evita erro de runtime no frontend, mas nao substitui governanca de storage.
Para producao definitiva, manter backup/sincronismo de `public/uploads` continua sendo recomendado.
