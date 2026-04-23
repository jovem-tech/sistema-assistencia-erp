# Registro de Correcao: Sincronizacao de respostas externas do WhatsApp na Central

**Data:** 18/03/2026  
**Modulo:** WhatsApp Gateway + Central de Mensagens

## Problema
- A Central de Mensagens mostrava corretamente mensagens enviadas pelo ERP e mensagens recebidas do cliente.
- Quando o tecnico respondia o cliente fora do ERP (app WhatsApp no celular), essa resposta nao aparecia na thread do ERP.

## Causa raiz
- O gateway Node encaminhava apenas evento inbound (`message` com `fromMe=false`).
- Mensagens outbound externas (`message_create` com `fromMe=true`) nao eram enviadas ao webhook ERP.

## Correcao aplicada

### 1) Gateway Node (`whatsapp-api/server.js`)
- Mantido handler inbound existente (`message`).
- Adicionado handler outbound externo:
  - evento `message_create`
  - filtro `fromMe=true`
  - filtro de conversa direta `@c.us`
  - envio do payload para o mesmo webhook ERP com `from_me=true`
- Provider no payload agora respeita ambiente:
  - `api_whats_local` (dev)
  - `api_whats_linux` (producao)

### 2) ERP - persistencia da Central (`CentralMensagensService`)
- `registerInboundFromPayload()` passou a tratar direcao dinamica:
  - `inbound` quando `from_me=false`
  - `outbound` quando `from_me=true`
- Ajustado status/data:
  - outbound externo entra como `status=enviada` + `enviada_em`
  - inbound permanece `status=recebida` + `recebida_em`
- Adicionada deduplicacao por `provider_message_id`.
- Adicionada reconciliacao de corrida:
  - se `message_create` chegar antes da atualizacao do envio interno, o sistema tenta casar com outbound pendente recente para evitar duplicata.

### 3) ERP - webhook (`WhatsAppWebhook`)
- Provedor agora pode ser lido do payload (`provider`) antes do fallback de configuracao, mantendo rastreabilidade correta.

## Resultado
- A thread da Central mostra respostas do tecnico feitas no celular.
- Conversa permanece consistente entre app WhatsApp e ERP.
- CRM continua recebendo eventos/interacoes com a direcao correta.
- Reducao de mensagens duplicadas em cenarios de concorrencia entre envio interno e evento externo.
- Indicador visual de origem evoluido para classificacao dinamica:
  - `sistema` -> remetente com nome do usuario interno + badge `via sistema`
  - `externo` -> remetente com numero autenticado do gateway + badge `via app externo`
  - `chatbot` -> remetente `Chatbot` + badge `chatbot`
  - cores outbound padronizadas por origem (azul/sistema, verde/externo, roxo/chatbot)

## Ajuste adicional de estabilidade (frontend)

### Sintoma
- Em alguns cenarios, ao abrir conversa, a tela exibiu erro `messagePayload is not defined`.

### Correcao
- Adicionado helper `messagePayload(msg)` em `public/assets/js/central-mensagens.js` para parse seguro de `payload`/`resposta_api`.
- Mantido fallback para objeto vazio quando nao houver JSON valido.
- Export defensivo `window.messagePayload` para compatibilidade com chamadas legadas.

### Efeito
- Conversa volta a carregar normalmente.
- Renderer de indicador externo passa a funcionar sem quebrar a thread.

## Correcao complementar: janela da thread trazendo mensagens antigas

### Sintoma observado
- Em conversas longas, mensagens recentes (inclusive `outbound_externo`) podiam nao aparecer ao abrir a thread.
- A tela carregava os registros mais antigos e exigia rolagem excessiva, passando a impressao de que a resposta externa nao existia.

### Causa raiz
- O carregamento inicial da thread (`MensagemWhatsappModel::byConversa`) usava:
  - `ORDER BY created_at ASC`
  - `LIMIT 500`
- Isso retorna os **500 primeiros** registros (mais antigos), e nao a janela mais recente.

### Correcao aplicada
- Alterado `MensagemWhatsappModel::byConversa` para:
  - buscar por `ORDER BY id DESC` com `LIMIT`
  - inverter em memoria (`array_reverse`) para renderizar no sentido cronologico no frontend
- Ajustado `CentralMensagensService::syncInboundQueue(...)` para aceitar modo forçado de sincronizacao de historico.
- `CentralMensagens::conversa($id)` e `CentralMensagens::syncInbound()` agora executam sync forçado, reduzindo risco de abrir thread sem as ultimas mensagens externas quando o webhook teve atraso pontual.

### Resultado pratico
- A abertura da conversa passa a mostrar sempre a janela mais recente.
- Mensagens enviadas fora do ERP (celular) aparecem imediatamente na thread.
- Menos rolagem cansativa para chegar no ponto atual da conversa.

## Arquivos alterados
- `whatsapp-api/server.js`
- `app/Services/CentralMensagensService.php`
- `app/Controllers/WhatsAppWebhook.php`
- `app/Models/MensagemWhatsappModel.php`
- `documentacao/06-modulos-do-sistema/central-de-mensagens.md`
- `documentacao/06-modulos-do-sistema/whatsapp.md`

---

## Complemento 19/03/2026 - estabilizacao de abertura e stream

### Sintomas adicionais
- Erro eventual ao abrir conversa: `messagePayload is not defined`.
- Avisos repetidos de stream no browser: `EventSource response MIME text/html`.
- Em mobile, botao de abrir painel de conversas/contexto sem resposta em alguns cenarios.

### Ajustes aplicados
1. **Renderer blindado no frontend**
   - `renderMensagem()` agora usa `payloadFn` defensivo (escopo local + fallback global).
   - Evita quebra da thread quando helper nao esta acessivel por cache/ordem de script.

2. **SSE com bloqueio temporario inteligente**
   - Frontend passou a ler `CM_CFG.enableSse`.
   - Quando handshake/stream retorna MIME invalido, o SSE fica temporariamente desativado na sessao e o modulo permanece em polling incremental.
   - Resultado: elimina loop de reconexao e poluicao de console.

3. **Reconciliacao incremental forcada em `conversa/novas`**
   - Se nao vier mensagem nova com `after_id`, o controller executa sync curto forcado com gateway e consulta novamente.
   - Aumenta consistencia de mensagens externas em cenarios de webhook intermitente.

4. **Fallback explicito de offcanvas mobile**
   - Triggers mobile de conversas/contexto passaram a abrir `Offcanvas` tambem por JS (`getOrCreateInstance(...).show()`), alem do `data-bs-toggle`.

5. **Envio com timeout defensivo**
   - Requisicao de envio no frontend ganhou timeout de 16s para evitar spinner preso por demora do gateway.

### Arquivos adicionais alterados neste complemento
- `app/Controllers/CentralMensagens.php`
- `app/Views/central_mensagens/index.php`
- `public/assets/js/central-mensagens.js`
- `documentacao/06-modulos-do-sistema/central-de-mensagens.md`
