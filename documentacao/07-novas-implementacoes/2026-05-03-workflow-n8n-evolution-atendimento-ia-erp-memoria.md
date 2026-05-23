# Workflow n8n Evolution + IA + ERP + Memoria

Data: 03/05/2026

## Objetivo

Criar uma segunda versao do workflow da Evolution, agora com memoria persistente no n8n, historico recente vindo do ERP e envio em blocos curtos para WhatsApp.

Essa versao foi desenhada para reduzir:

- respostas que parecem esquecer o que o cliente acabou de falar;
- repeticao de saudacoes e perguntas basicas;
- respostas longas e artificiais;
- uso do nome errado quando `pushName` do WhatsApp divergir do cadastro do ERP.

Refino adicional aplicado depois:

- a V2 passou a consultar tambem as ordens de servico do cliente antes do agente responder, mantendo a mesma arquitetura simples de memoria e sem depender de tool SQL acionada pela IA.

## Arquivo entregue

- `documentacao/10-deploy/n8n/evolution-whatsapp-atendimento-ia-erp-memoria.json`

## Estrategia da V2

Fluxo:

`Cliente -> WhatsApp -> Evolution -> n8n (memoria + IA) -> ERP -> resposta via Evolution`

Principais camadas:

1. Evolution como transporte oficial do WhatsApp.
2. ERP como historico oficial e fonte de contexto operacional.
3. n8n como orquestrador de memoria, prompt e envio automatizado.

## Diferencas em relacao a V1

### V1

- consulta cliente no ERP;
- consulta historico recente do ERP;
- usa chamada HTTP direta para `OpenAI Responses API`;
- responde em uma unica mensagem.

### V2 com memoria

- mantem a consulta do cliente no ERP;
- mantem o historico recente do ERP;
- adiciona consulta direta das ordens de servico do cliente no ERP;
- adiciona `Postgres Chat Memory` do n8n;
- usa `AI Agent` com memoria de sessao por conversa;
- divide a resposta final em blocos curtos;
- envia os blocos com pequeno intervalo.

## Ajuste importante aplicado apos a primeira homologacao

Durante a primeira validacao da V2, o node de historico do ERP retornava varias linhas e o contexto da IA estava sendo montado uma vez por linha, o que fazia o agente responder varias vezes para uma unica mensagem do cliente.

O workflow entregue foi ajustado para:

- agregar o historico em um unico contexto antes do agente;
- processar o node `Montar Contexto IA` em `runOnceForAllItems`;
- reduzir o historico recente de 12 para 8 mensagens;
- limitar a quebra final para no maximo 3 blocos curtos.

Com isso, a tendencia passa a ser:

- 1 mensagem inbound do cliente
- 1 execucao do agente
- 1 a 3 mensagens de resposta no maximo

## Desenho do workflow

1. `Entrada Evolution IA`
   - webhook `POST /evolution-whatsapp-atendimento-ia-memoria`.
2. `Normalizar Evolution e Credenciais`
   - normaliza o payload da Evolution;
   - aceita `messages.upsert` e normaliza para `MESSAGES_UPSERT`;
   - aceita tambem payload simplificado de teste manual;
   - gera `memorySessionKey` por `remoteJid`/telefone.
3. `Inbound Elegivel?`
   - filtra apenas inbound real elegivel.
4. `Espelhar Evento no ERP`
   - envia a mensagem para `POST /webhooks/whatsapp`.
5. `Buscar Cliente ERP`
   - consulta a tabela `clientes`.
6. `Buscar Historico Conversa ERP`
   - consulta `mensagens_whatsapp` e `conversas_whatsapp`.
7. `Buscar OS ERP`
   - consulta as OS mais recentes da tabela `os`, com `LEFT JOIN` em `equipamentos`.
8. `Montar Contexto IA`
   - monta o prompt contextual;
   - detecta conflito entre `pushName` e cadastro do ERP;
   - injeta o resumo das OS mais recentes no contexto do agente.
9. `Agente IA Humanizado`
   - usa um modelo OpenAI via node AI do n8n.
10. `Memoria Postgres`
   - persiste a memoria da conversa por sessao.
11. `Preparar Resposta Final`
   - limpa a resposta;
   - aplica fallback;
   - quebra em blocos curtos.
12. `Separar Blocos`
   - transforma o array de blocos em itens do fluxo.
13. `Loop Over Items`
   - envia os blocos um a um.
14. `Enviar Resposta Evolution`
   - usa `POST /message/sendText/{instance}`.
15. `Aguardar 2 Segundos`
   - cria espacamento entre blocos.

## Memoria persistente

Node usado:

- `@n8n/n8n-nodes-langchain.memoryPostgresChat`

Configuracao principal:

- `sessionIdType = customKey`
- `sessionKey = remoteJid ou normalizedPhone`
- `tableName = n8n_chat_memory_evolution_erp`
- `contextWindowLength = 40`

## O que voce precisa configurar no n8n

### Credenciais obrigatorias

1. Credencial MySQL
   - usada em:
     - `Buscar Cliente ERP`
     - `Buscar Historico Conversa ERP`
     - `Buscar OS ERP`
2. Credencial OpenAI
   - usada no node `OpenAI Chat Model`
3. Credencial Postgres
   - usada no node `Memoria Postgres`

### Placeholders no code node

No node `Normalizar Evolution e Credenciais`, preencher:

- `companyName`
- `erpWebhookUrl`
- `erpWebhookToken`
- `erpOrigin`
- `evolutionBaseUrl`
- `evolutionApiKey`
- `evolutionInstance`

## Regras de humanizacao aplicadas

- a IA recebe historico recente do ERP antes de responder;
- a IA recebe tambem o resumo das OS recentes do cliente antes de responder sobre conserto, status ou andamento;
- a memoria do n8n preserva continuidade entre mensagens da mesma sessao;
- o prompt instrui a nao reiniciar a conversa;
- o prompt proibe pedir novamente o que ja foi dito;
- respostas muito curtas do cliente devem ser interpretadas dentro do contexto;
- quando houver divergencia de nome entre perfil do WhatsApp e ERP, a IA deve evitar chamar o cliente com conviccao pelo nome errado;
- a resposta e quebrada em blocos menores, mais proximos da conversa humana em WhatsApp.

## Quando usar a V2

Use essa versao quando:

- a V1 estiver funcional tecnicamente;
- voce quiser mais naturalidade na conversa;
- quiser continuidade de contexto entre mensagens;
- estiver pronto para configurar uma credencial Postgres no n8n.

## Observacao operacional

Mesmo com memoria no n8n:

- o ERP continua sendo a fonte oficial do historico;
- a memoria do agente e complementar, nao substituta;
- respostas humanas no ERP ou no app oficial continuam devendo pausar a automacao da conversa.
