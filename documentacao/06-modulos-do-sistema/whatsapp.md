# Modulo: Atendimento WhatsApp (Unificado)

Atualizado em 07/05/2026 (v2.16.41).

## Objetivo
Concentrar todo o atendimento WhatsApp dentro do ERP, sem iframe e sem modulo externo dedicado.

## Arquitetura oficial
Fluxo:
`Controller -> WhatsAppService -> MensageriaService -> Provider`

Providers diretos suportados:
- `menuia`
- `evolution`
- `api_whats_local` (gateway Node local Windows)
- `api_whats_linux` (gateway Node em VPS Linux)
- `webhook` (integracao custom)

Provider de massa (futuro):
- `meta_oficial`

## Modulo unico no ERP
- Inbox principal: `/atendimento-whatsapp` (alias de `/central-mensagens`)
- Sem embed externo, sem iframe e sem rota `/whaticket`
- Operacao e contexto CRM/OS no mesmo modulo interno

## Arquitetura omnichannel recomendada

Fluxo principal com Evolution API:

`Cliente -> WhatsApp -> Evolution API -> n8n (opcional) -> ERP / Central de Mensagens`

Saidas suportadas no mesmo historico:
- resposta automatica pelo bot/IA
- resposta humana pelo ERP
- resposta humana pelo app oficial do WhatsApp conectado na Evolution

Regras operacionais consolidadas:
- a Evolution passa a ser a camada oficial de transporte quando o provider direto for `evolution`;
- o ERP continua como fonte de verdade do historico em `conversas_whatsapp` e `mensagens_whatsapp`;
- mensagens outbound capturadas do app oficial entram como `outbound_externo` e pausam a automacao da conversa;
- a Central pode exibir `pushName`, `remoteJid` e avatar do contato quando a sincronizacao da Evolution estiver habilitada.

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
- `whatsapp_evolution_url`
- `whatsapp_evolution_apikey`
- `whatsapp_evolution_instance`
- `whatsapp_evolution_timeout`
- `whatsapp_evolution_sync_avatar`
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

## Evolution API

Quando o provider direto for `evolution`:
- a URL deve apontar para a base HTTP da Evolution API;
- a autenticacao usa `apikey`;
- a instancia configurada define qual sessao WhatsApp sera usada para envio e leitura;
- o ERP testa conectividade pelo estado da instancia e pode enviar mensagem de teste real;
- em ambiente local sem webhook publico, o ERP faz sync ativo da Evolution para popular a Central;
- a Central pode sincronizar automaticamente:
  - `whatsapp_nome_perfil`
  - `whatsapp_remote_jid`
  - `whatsapp_avatar_url`
  - `whatsapp_avatar_synced_em`

Campos novos de configuracao:
- `whatsapp_evolution_url`
- `whatsapp_evolution_apikey`
- `whatsapp_evolution_instance`
- `whatsapp_evolution_timeout`
- `whatsapp_evolution_sync_avatar`

Comportamento esperado:
- inbound da Evolution cria/atualiza contato por telefone;
- se houver `remoteJid`, ele e salvo no contato;
- se houver sincronismo de avatar habilitado, o ERP consulta a foto do perfil e atualiza a Central;
- mensagens enviadas fora do ERP, mas capturadas pela Evolution (`fromMe=true`), entram na thread da Central como saida humana e desativam o bot daquela conversa.

Comportamento adicional no ambiente local:
- se a Evolution estiver em VPS e o ERP rodar em `https://localhost:4433/...`, a Central nao depende exclusivamente de webhook para notar novidades;
- `CentralMensagensService::syncInboundQueue()` consulta os chats recentes da instancia Evolution e reconcilia o `lastMessage` com a thread local;
- a fila lateral, o snapshot de busca e as notificacoes do ERP passam a refletir novas mensagens mesmo quando o webhook da Evolution nao aponta para o `localhost`.

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

## Workflow n8n importavel para atendimento

Foi adicionado um workflow JSON de referencia para atendimento inicial de clientes via n8n, reaproveitando os contratos oficiais do ERP e do gateway:

- arquivo: `documentacao/10-deploy/n8n/atendimento-clientes-whatsapp.json`
- documentacao detalhada: `documentacao/07-novas-implementacoes/2026-05-02-workflow-n8n-atendimento-clientes.md`

Esse workflow:

- recebe mensagens em um webhook do n8n;
- espelha o inbound no ERP por `POST /webhooks/whatsapp`;
- classifica a intencao por palavras-chave;
- envia resposta automatica pelo gateway oficial em `POST /create-message`.

Uso recomendado:

- manter o ERP como fonte de verdade do historico;
- manter o gateway Node oficial como transporte de envio;
- editar no n8n os placeholders de URL, token e origem antes de ativar o fluxo.

## Workflow n8n com IA e busca em clientes

Foi adicionado um segundo workflow, separado do fluxo inicial, para responder com apoio de IA e contexto vindo da tabela `clientes`:

- arquivo: `documentacao/10-deploy/n8n/atendimento-clientes-whatsapp-ai-clientes.json`
- documentacao detalhada: `documentacao/07-novas-implementacoes/2026-05-02-workflow-n8n-atendimento-clientes-ai-db.md`

Esse novo fluxo:

- preserva o workflow atual ja homologado;
- consulta `clientes` por telefone antes de responder;
- monta contexto com nome, contato e observacoes do cadastro;
- pede a resposta a um modelo de IA;
- envia o texto final pela Menuia.

## Workflow n8n oficial para Evolution + IA + ERP

Foi adicionada uma terceira referencia de workflow, agora alinhada ao desenho recomendado com Evolution como transporte oficial do WhatsApp:

- arquivo: `documentacao/10-deploy/n8n/evolution-whatsapp-atendimento-ia-erp.json`
- documentacao detalhada: `documentacao/07-novas-implementacoes/2026-05-03-workflow-n8n-evolution-atendimento-ia-erp.md`

Fluxo recomendado:

## Workflow n8n por etapas para a Jovem Tech

Foi adicionada uma referencia separada para atendimento por etapas, indicada quando a equipe quiser controlar o funil conversacional com `Step-Index` em vez de deixar toda a logica concentrada em um unico agente:

- arquivo: `documentacao/10-deploy/n8n/jovem-tech-atendimento-por-etapas.json`
- documentacao detalhada: `documentacao/07-novas-implementacoes/2026-05-07-workflow-n8n-jovem-tech-etapas-atendimento.md`

Esse workflow:

- roteia a conversa por `switch` entre `8` etapas;
- usa memoria Mongo por `User.identifier`;
- responde sempre em JSON estruturado;
- cobre saudacao, triagem, coleta tecnica, orientacao inicial, agendamento, orcamento, status e pos-atendimento;
- ja vem com payload de teste adaptado para assistencia tecnica de celulares e informatica.

Uso recomendado:

- usar como base de prompt engineering e progressao de atendimento;
- conectar depois a um workflow maior com Evolution, ERP ou Central de Mensagens quando o projeto exigir automacao ponta a ponta;
- manter `Company`, `Persona` e `Dynamic Prompt` como camada configuravel por unidade, campanha ou tipo de atendimento.

`Cliente -> WhatsApp -> Evolution API -> n8n -> ERP/banco -> resposta ao cliente via Evolution`

Papel de cada camada:

- Evolution API:
  - recebe inbound real do WhatsApp;
  - envia outbound do bot e do humano;
  - continua refletindo o que foi respondido pelo app oficial.
- n8n:
  - normaliza o payload da Evolution;
  - espelha o inbound no ERP;
  - consulta a tabela `clientes`;
  - monta contexto de IA;
  - responde ao cliente quando o evento for inbound elegivel.
- ERP:
  - permanece como historico oficial em `conversas_whatsapp` e `mensagens_whatsapp`;
  - continua sendo a Central de Mensagens para operacao humana;
  - fornece o banco/contexto consultado pelo workflow.

Ponto operacional importante:

- a Central de Mensagens e o app oficial do WhatsApp passam a ser principalmente camadas de visualizacao e resposta humana;
- a automacao e a IA ficam centralizadas no n8n;
- o transporte continua unificado pela Evolution para evitar perda de historico.

Refino importante de UX conversacional:

- o workflow da Evolution com IA agora consulta tambem o historico recente da conversa no ERP antes de pedir a resposta ao modelo;
- isso reduz respostas que \"esquecem\" contexto ja informado pelo cliente;
- o prompt tambem trata divergencia entre `pushName` e nome do cadastro para evitar chamar o cliente pelo nome errado com excesso de confianca.

## Workflow n8n Evolution + IA + ERP + Memoria

Foi adicionada uma versao 2 do workflow da Evolution, agora com memoria persistente do n8n e envio em blocos curtos:

- arquivo: `documentacao/10-deploy/n8n/evolution-whatsapp-atendimento-ia-erp-memoria.json`
- documentacao detalhada: `documentacao/07-novas-implementacoes/2026-05-03-workflow-n8n-evolution-atendimento-ia-erp-memoria.md`

Diferencial da V2:

- usa `Postgres Chat Memory` do n8n;
- preserva contexto por sessao de conversa;
- continua consultando historico recente do ERP;
- consulta tambem as ordens de servico recentes do cliente antes da resposta da IA;
- divide a resposta final em blocos menores antes de enviar pela Evolution.
- agrega o historico do ERP em um unico contexto antes de acionar o agente;
- evita execucoes repetidas do agente por linha do historico;
- limita a saida automatica a no maximo 3 blocos curtos por resposta.

Uso recomendado:

- homologar primeiro a V1;
- depois homologar a V2 com credencial Postgres no n8n;
- migrar para a V2 quando a naturalidade da conversa for prioridade e o ambiente de memoria ja estiver pronto.

## Workflow n8n Evolution + IA + ERP + V3 Atendimento Seguro

Foi adicionada uma terceira referencia, mais completa, focada em seguranca operacional, contexto amplo do ERP e bases auxiliares:

- arquivo: `documentacao/10-deploy/n8n/evolution-whatsapp-atendimento-ia-erp-v3.json`
- subworkflow-tool: `documentacao/10-deploy/n8n/evolution-whatsapp-atendimento-ia-v3-tool-base-institucional-drive.json`
- documentacao detalhada: `documentacao/07-novas-implementacoes/2026-05-04-workflow-n8n-evolution-atendimento-ia-erp-v3.md`

Diferenciais da V3:

- valida identidade do cliente por telefone + primeiro nome antes de liberar dados sensiveis;
- adiciona um agente de triagem antes do agente principal para classificar a mensagem e decidir se a resposta deve pedir validacao de identidade;
- consulta no ERP, de forma separada e controlada:
  - conversa atual
  - historico recente
  - equipamentos
  - ordens de servico
  - orcamentos
- registra nome e telefone do contato do dia em Google Sheets;
- grava `data` e `hora` do lead em `America/Fortaleza`, evitando carimbo em UTC na planilha;
- consulta a base institucional da assistencia por uma tool real de Google Drive, em subworkflow dedicado;
- usa Brave Search como tool opcional do agente somente para perguntas tecnicas complexas;
- mantem memoria Postgres e limita a resposta a no maximo 3 blocos curtos.

Regra operacional da V3:

- dados institucionais da Jovem Tech devem sair da base oficial do Google Drive quando o agente precisar desse contexto;
- dados operacionais do cliente so devem ser expostos quando `identityStatus = confirmado` e a triagem nao exigir nova validacao;
- a segunda via/PDF nao e buscada automaticamente nessa revisao da V3;
- quando a conversa estiver pausada para humano, o workflow responde ao webhook sem disparar automacao.

Ajuste minimo esperado para subir a V3:

- credencial MySQL;
- credencial OpenAI;
- credencial Postgres;
- credencial Google Sheets;
- credencial Google Drive no subworkflow-tool;
- credencial Brave Search;
- preencher placeholders do node `Normalizar Entrada e Config V3`.

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
