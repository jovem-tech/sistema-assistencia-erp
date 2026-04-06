# Modulo: Atendimento WhatsApp (Unificado)

Atualizado em 02/04/2026 (v2.10.14).

## Objetivo
Concentrar todo o atendimento WhatsApp dentro do ERP, sem iframe e sem modulo externo dedicado.

## Arquitetura oficial
Fluxo:
`Controller -> WhatsAppService -> MensageriaService -> Provider`

Providers diretos suportados:
- `menuia`
- `api_whats_local` (gateway Node local Windows)
- `api_whats_linux` (gateway Node em VPS Linux)
- `webhook` (integracao custom)

Provider de massa (futuro):
- `meta_oficial`

## Modulo unico no ERP
- Inbox principal: `/atendimento-whatsapp` (alias de `/central-mensagens`)
- Sem embed externo, sem iframe e sem rota `/whaticket`
- Operacao e contexto CRM/OS no mesmo modulo interno

## Funcionalidades principais
- conversa em tempo real com polling/SSE e fallback seguro
- envio de texto, imagem, audio, video e PDF
- vinculacao de conversa com OS
- exibicao de contexto de cliente e OS na thread
- respostas rapidas
- chatbot/automacao (fluxos, FAQ, regras ERP)
- metricas operacionais diarias
- fila e atribuicao de responsavel

## Hotfix de estabilidade de envio + conflito Bootstrap (02/04/2026 - v2.10.14)

Ajustes aplicados para reduzir ruido de console e falhas intermitentes de envio:

- action bar da Central deixou de inicializar tooltip Bootstrap em elementos com `dropdown`, evitando conflito de instancia (`one instance per element`);
- timeout padrao do provider local foi ampliado de `20s` para `30s`:
  - `whatsapp_local_node_timeout` (Windows/local);
  - `whatsapp_linux_node_timeout` (Linux/VPS).

Observacao operacional:
- resposta `503` em `/atendimento-whatsapp/enviar` continua sendo status valido quando o gateway estiver realmente indisponivel; o hotfix reduz casos de timeout falso por latencia.

## Timeout resiliente entre polling e envio (02/04/2026 - v2.10.11)

Refino operacional da Central para reduzir timeout concorrente em ambiente VPS durante uso continuo:

- endpoints de leitura rapida da thread (`conversas`, `conversa/{id}`, `conversa/{id}/novas`) foram mantidos sem processamento auxiliar no caminho critico;
- endpoint `enviar` passou a liberar lock de sessao antes da chamada ao provider;
- frontend elevou timeout padrao de request para `30s` e envio para `max(25s, timeout global)`.

Resultado:
- menor chance de bloqueio entre polling e envio do mesmo operador;
- menor incidencia de erro `Tempo limite excedido` em picos de latencia.

## Inbound multimidia no historico (02/04/2026 - v2.10.9)

Para convergencia com o comportamento do WhatsApp Web em midias recebidas:

- o gateway em `whatsapp-api/server.js` passou a baixar anexos tambem na rota de historico (`GET /sync-chat-history`);
- o payload de historico agora inclui `media_base64`, `media_mime_type`, `media_filename` e `media_size_bytes` quando houver midia disponivel;
- o ERP consegue hidratar mensagens ja deduplicadas por `provider_message_id` quando a midia chega posteriormente;
- tipos de voz (`ptt`/`voice`) passam a ser tratados como `audio` no parser da Central.

Resultado operacional:
- audio, video, imagem e anexo inbound deixam de cair como mensagem textual vazia em cenarios de reconciliacao por historico.

## Estabilizacao de polling na Central (02/04/2026 - v2.10.10)

Para evitar timeout repetido no polling incremental da thread:

- o fluxo rapido de leitura (`conversas` e `conversa/{id}/novas`) passou a processar apenas fila local inbound;
- a sincronizacao de historico do gateway permaneceu no fluxo dedicado de sync, com lotes menores por ciclo para reduzir latencia;
- endpoints críticos da Central liberam lock de sessao antes de rodar sync pesado, reduzindo bloqueio concorrente entre requests AJAX.

## Configuracao
Caminho:
- `Configuracoes -> Integracoes`

Configuracoes usadas:
- `whatsapp_enabled`
- `whatsapp_direct_provider`
- `whatsapp_bulk_provider`
- `whatsapp_test_phone`
- `whatsapp_webhook_token`
- `whatsapp_menuia_url`
- `whatsapp_menuia_appkey`
- `whatsapp_menuia_authkey`
- `whatsapp_local_node_url`
- `whatsapp_local_node_token`
- `whatsapp_local_node_origin`
- `whatsapp_local_node_timeout`
- `whatsapp_linux_node_url`
- `whatsapp_linux_node_token`
- `whatsapp_linux_node_origin`
- `whatsapp_linux_node_timeout`
- `whatsapp_webhook_url`
- `whatsapp_webhook_method`
- `whatsapp_webhook_headers`
- `whatsapp_webhook_payload`

## Menuia

Quando o provider direto for `menuia`:
- a URL operacional canonica e `https://chatbot.menuia.com/api`
- a conexao e validada por um envio real controlado para o telefone de teste
- o ERP grava o resultado da ultima validacao em:
  - `whatsapp_last_check_provider`
  - `whatsapp_last_check_status`
  - `whatsapp_last_check_message`
  - `whatsapp_last_check_at`
  - `whatsapp_last_check_signature`

Regras de badge no painel:
- `Menuia conectada`: validacao bem-sucedida com as credenciais atuais
- `Erro Menuia`: validacao falhou com as credenciais atuais
- `Menuia nao validada`: credenciais preenchidas, mas ainda nao testadas ou trocadas desde o ultimo teste

Importante:
- trocar `URL`, `Appkey` ou `Authkey` invalida o status salvo anterior
- isso evita mostrar um estado verde de conexao usando credenciais antigas

## Gateway APIs reaproveitadas
Mantemos o gateway Node como servico de transporte, com integracao ao ERP:
- `GET /status`
- `GET /qr`
- `POST /restart`
- `POST /logout`
- `POST /create-message`
- `POST /self-check-inbound`

## Regra obrigatoria para VPS Linux

Em producao Linux/VPS, o canal direto deve permanecer em `api_whats_linux`.

Configuracao minima esperada no ERP:

- `whatsapp_direct_provider = api_whats_linux`
- `whatsapp_linux_node_url = http://127.0.0.1:3001`
- `whatsapp_linux_node_token = mesmo valor de API_TOKEN do Node`
- `whatsapp_linux_node_origin = URL publica do ERP na VPS`

Configuracao minima esperada no `whatsapp-api/.env`:

- `NODE_ENV=production`
- `HOST=127.0.0.1`
- `PORT=3001`
- `API_TOKEN` igual ao token salvo no ERP
- `ERP_ORIGIN` contendo a URL publica do ERP

Se o ERP estiver apontando para `api_whats_local` na VPS, o status pode subir como `internal_error` e o gateway pode recusar requests por origem incorreta.

## Inbound e seguranca
- webhook ERP: `POST /webhooks/whatsapp`
- token inbound via `X-Webhook-Token` (ou `?token=`)
- validacao de origem ERP + token no gateway
- logs de envio/erro no ERP e no gateway

## Anti-duplicacao operacional (01/04/2026)

Para reduzir duplicacoes de outbound na operacao da VPS (principalmente em cenarios de eco de provedor/webhook e clique repetido no frontend):

- frontend da Central de Mensagens agora aplica lock de envio (`state.sendingMessage`) para impedir requests concorrentes no composer;
- backend (`CentralMensagensService`) ganhou reconciliacao adicional de outbound recente antes de inserir nova linha em `mensagens_whatsapp`;
- quando uma mensagem equivalente ja existe na janela curta de tempo, o sistema atualiza status/payload da existente e nao cria duplicata;
- o mecanismo nao altera o fluxo de inbound legitimo e preserva rastreabilidade de `provider_message_id` quando disponivel.

## Protecao adicional contra clique duplo no envio (01/04/2026 - v2.9.9)

Foi adicionada uma segunda camada de idempotencia no `WhatsAppService` para cenarios de operacao em VPS:

- antes de enviar para o provider, o servico busca outbound recente equivalente em `mensagens_whatsapp`;
- se encontrar o mesmo payload operacional (conversa + telefone + conteudo/anexo + tipo da mensagem) em janela de 3 segundos, o segundo envio e ignorado;
- a resposta retorna `ok=true` com `duplicate=true`, preservando UX sem reenviar a mesma mensagem no gateway;
- objetivo: eliminar duplicacao por clique rapido/repetido mesmo quando o frontend ja esta com lock de envio.

## Sincronizacao inbound assistida na Central (01/04/2026 - v2.10.0)

Para reduzir atraso de visibilidade quando a mensagem chega por canal externo sem evento imediato no stream:

- a Central passou a executar sincronizacao inbound silenciosa em background (intervalo operacional controlado);
- no retorno de aba (`visibilitychange`), a tela dispara novo ciclo silencioso para convergencia rapida;
- o header exibe badge dedicada com estado da sincronizacao inbound (`ocioso`, `sincronizando`, `ok`, `falha`);
- o botao manual de sincronizacao continua disponivel para suporte, agora reaproveitando o mesmo fluxo robusto de sincronizacao.

## Continuidade operacional premium na Central (01/04/2026 - v2.10.1)

Para elevar previsibilidade de atendimento em ambiente real:

- a thread ativa agora mostra barra de conexao operacional com estados `online`, `sincronizando`, `instavel` e `offline`;
- o envio de mensagem passou a usar bolha otimista (`Enviando`) com reconciliacao posterior, mantendo feedback instantaneo ao operador;
- em caso de falha, a bolha outbound fica sinalizada como `Falha no envio`, preservando contexto da tentativa;
- o composer passou a persistir rascunho por conversa no navegador, restaurando automaticamente quando a thread for reaberta.

## Integracao com CRM e OS
- inbound/outbound geram eventos e interacoes CRM
- conversa pode ser vinculada a OS principal e multiplas OS relacionadas
- atualizacao de status da conversa, prioridade e responsavel
- rastreabilidade completa em:
  - `conversas_whatsapp`
  - `mensagens_whatsapp`
  - `crm_mensagens`
  - `crm_eventos`
  - `crm_interacoes`

## Observacao de descontinuidade
O legado WhaTicket/Whaticket foi removido do ERP:
- rotas removidas: `/whaticket`, `/whaticket/status`, `/configuracoes/whatsapp/whaticket-local-start`
- configuracoes legadas removidas por migration
- provider legado normalizado para `api_whats_local`

## Referencias
- [Central de Mensagens](central-de-mensagens.md)
- [Rotas da API](../05-api/rotas.md)
- [Configuracao do Sistema](../02-manual-administrador/configuracao-do-sistema.md)
- [Tabelas principais](../04-banco-de-dados/tabelas-principais.md)
