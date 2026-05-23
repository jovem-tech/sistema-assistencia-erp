# Workflow n8n Evolution + IA + ERP

Data: 03/05/2026

## Objetivo

Definir o workflow oficial de atendimento automatico quando o transporte WhatsApp do sistema estiver centralizado na Evolution API.

Fluxo alvo:

`Cliente -> WhatsApp -> Evolution API -> n8n -> ERP/banco -> resposta ao cliente via Evolution`

Camadas operacionais:

- Evolution API: transporte de entrada e saida do WhatsApp;
- n8n: orquestracao, IA e regras;
- ERP: historico oficial, Central de Mensagens, clientes, OS e contexto operacional;
- app oficial do WhatsApp e Central do ERP: resposta humana e visualizacao da mesma conversa.

## Arquivo entregue

- `documentacao/10-deploy/n8n/evolution-whatsapp-atendimento-ia-erp.json`

## Papel da Central e do app oficial

A Central de Mensagens do ERP e o app oficial do WhatsApp nao precisam ser a camada de automacao.

Papel recomendado:

- Central do ERP:
  - visualizar conversas;
  - assumir atendimento humano;
  - responder clientes;
  - vincular cliente, OS e contexto;
  - manter historico operacional.
- app oficial do WhatsApp:
  - resposta humana paralela;
  - leitura operacional em campo;
  - continuidade do atendimento pela mesma instancia da Evolution.

Ponto obrigatorio:

- mesmo quando o atendente responder pelo app oficial, a Evolution deve refletir essa saida no ERP para manter a thread unificada.

## Desenho do workflow

1. `Entrada Evolution IA`
   - webhook `POST /evolution-whatsapp-atendimento-ia`.
   - recebe eventos da Evolution.
2. `Normalizar Evolution e Credenciais`
   - valida o envelope;
   - aceita apenas `MESSAGES_UPSERT`;
   - ignora grupos e `fromMe=true`;
   - extrai `remoteJid`, `pushName`, texto e tipo da mensagem;
   - normaliza telefone com e sem `55`;
   - centraliza URLs, tokens e placeholders do fluxo;
   - monta SQL dinamico para consulta na tabela `clientes`.
3. `Espelhar Evento no ERP`
   - envia o inbound para `POST /webhooks/whatsapp`;
   - preserva o ERP como historico oficial.
4. `Buscar Cliente ERP`
   - consulta `clientes` por:
     - `telefone1`
     - `telefone2`
     - `telefone_contato`
   - aceita telefone salvo com ou sem `55`.
5. `Buscar Historico Conversa ERP`
   - consulta `mensagens_whatsapp` e `conversas_whatsapp`;
   - carrega a janela mais recente da conversa para a IA nao responder como se fosse o primeiro contato;
   - diferencia historico de:
     - `cliente`
     - `bot`
     - `humano_erp`
     - `humano_app`
6. `Montar Contexto IA`
   - resume o cadastro encontrado;
   - prepara `instructions` e `input` para a IA;
   - inclui historico recente da conversa;
   - detecta divergencia entre `pushName` e nome do cadastro para evitar chamar a pessoa pelo nome errado;
   - deixa claro que a IA nao pode inventar status, valores ou prazos.
7. `Agente IA OpenAI`
   - chama `POST https://api.openai.com/v1/responses`.
8. `Preparar Resposta Final`
   - extrai o texto final da IA;
   - aplica fallback seguro quando a IA falhar ou voltar vazia;
   - usa saudacao neutra quando houver conflito entre nome do perfil e nome do cadastro.
9. `Deve Responder?`
   - so libera envio quando o evento for inbound elegivel.
10. `Enviar Resposta Evolution`
   - responde ao cliente usando:
   - `POST {evolutionBaseUrl}/message/sendText/{instance}`.
11. `Responder Webhook com IA` / `Responder Webhook sem Envio`
   - devolvem JSON tecnico de sucesso ao remetente do webhook.

## Payload esperado da Evolution

O workflow foi desenhado para payloads de mensagem como:

- `event = MESSAGES_UPSERT`
- `data.key.remoteJid`
- `data.key.fromMe`
- `data.pushName`
- `data.message.conversation`
- `data.message.extendedTextMessage.text`
- captions de imagem, video e documento

O fluxo ignora:

- mensagens de grupo (`@g.us`);
- mensagens enviadas pela propria instancia (`fromMe=true`);
- eventos que nao sejam `MESSAGES_UPSERT`.

O workflow tambem normaliza `messages.upsert` para `MESSAGES_UPSERT`, evitando falso negativo quando a Evolution usar ponto em vez de underscore no nome do evento.

## Configuracoes que precisam ser preenchidas

No node `Normalizar Evolution e Credenciais`:

- `companyName`
- `erpWebhookUrl`
- `erpWebhookToken`
- `erpOrigin`
- `evolutionBaseUrl`
- `evolutionApiKey`
- `evolutionInstance`
- `openaiApiKey`
- `openaiModel`

No node `Buscar Cliente ERP`:

- vincular a credencial MySQL correta do ERP.

## URL de producao do webhook no n8n

Depois de publicar o workflow:

- URL de producao:
  - `https://n8n.jovemtech.eco.br/webhook/evolution-whatsapp-atendimento-ia`
- URL de teste:
  - `https://n8n.jovemtech.eco.br/webhook-test/evolution-whatsapp-atendimento-ia`

## Configuracao recomendada na Evolution

Webhook de entrada:

- apontar o evento de mensagens da Evolution para a URL de producao do n8n;
- priorizar `MESSAGES_UPSERT` como evento principal de entrada.

Configuracao de instancia no workflow:

- usar o `name` da instancia na Evolution, nao o `token`.
- exemplo pratico observado no projeto:
  - `instance = chatwoot`

## Contratos reutilizados do ERP

Rotas reaproveitadas:

- `POST /webhooks/whatsapp`

Fonte de contexto:

- banco MySQL do ERP
- tabela `clientes`

Campos consultados:

- `telefone1`
- `telefone2`
- `telefone_contato`
- `nome_razao`
- `email`
- `nome_contato`
- `cidade`
- `uf`
- `observacoes`

Historico recente consultado:

- `mensagens_whatsapp.mensagem`
- `mensagens_whatsapp.direcao`
- `mensagens_whatsapp.tipo_mensagem`
- `mensagens_whatsapp.tipo_conteudo`
- `mensagens_whatsapp.enviada_por_bot`
- `mensagens_whatsapp.created_at`
- `conversas_whatsapp.telefone`

## Regras operacionais recomendadas

- se a conversa estiver em atendimento humano no ERP, o n8n nao deve responder automaticamente;
- se o app oficial ou o ERP responderem ao cliente, a conversa deve ficar com automacao pausada;
- o ERP continua sendo a trilha oficial de historico, mesmo quando a resposta automatica for enviada pelo n8n;
- a Evolution continua sendo a camada de transporte, tanto para o bot quanto para o humano.

## Regras de humanizacao aplicadas no workflow

- a IA passa a receber historico recente da conversa, em vez de responder somente com a mensagem atual;
- o prompt orienta a continuar a conversa de onde ela parou;
- o prompt proibe pedir novamente dados ja presentes no historico ou no cadastro;
- respostas curtas do cliente (`ok`, `sim`, `nao`, `testei`, `deu certo`) devem ser interpretadas no contexto da conversa, e nao como novo atendimento;
- quando `pushName` e nome do cadastro divergirem, a IA deve evitar tratar qualquer nome como verdade absoluta e preferir saudacao neutra.

## Observacoes finais

- esse workflow foi criado em arquivo separado para nao mexer no fluxo anterior baseado em Menuia;
- a importacao no n8n ainda exige preenchimento dos placeholders de segredo antes da homologacao final;
- segredos nao devem permanecer versionados no projeto apos a configuracao real no n8n.
