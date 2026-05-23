# Workflow n8n Evolution + IA + ERP + V3 Atendimento Seguro

Data: 04/05/2026

## Objetivo

Consolidar a referencia V3 do atendimento WhatsApp com:

- triagem por IA antes da resposta principal;
- memoria persistente no Postgres;
- consulta segura ao ERP;
- registro diario enxuto em Google Sheets;
- base institucional carregada por tool real de Google Drive;
- busca tecnica externa controlada com Brave Search.

## Arquivos entregues

- `documentacao/10-deploy/n8n/evolution-whatsapp-atendimento-ia-erp-v3.json`
- `documentacao/10-deploy/n8n/evolution-whatsapp-atendimento-ia-v3-tool-base-institucional-drive.json`

## Arquitetura final

Fluxo principal:

`Cliente -> WhatsApp -> Evolution -> n8n V3 -> ERP + triagem IA + memoria + tools -> Evolution -> cliente`

Papeis:

1. Evolution continua como transporte oficial do WhatsApp.
2. ERP continua como fonte oficial do historico e dos dados operacionais.
3. Um agente de triagem decide a classificacao da mensagem e se a resposta precisa validar identidade antes de falar sobre dados sensiveis.
4. O agente principal responde ao cliente com memoria, ERP e tools.
5. Google Sheets registra apenas nome, telefone e metadados diarios do contato.
6. Google Drive fornece a base institucional oficial via subworkflow-tool.
7. Brave Search e usado apenas para perguntas tecnicas gerais.

## Diferencas importantes da V3 revisada

- o Google Sheets deixa de receber o item inteiro do workflow e passa a receber apenas uma linha enxuta preparada por `Preparar Linha Lead Diario Sheets`;
- a data e a hora do lead passam a usar `America/Fortaleza`, evitando gravacao em UTC na planilha;
- a classificacao de `tecnica`, `institucional`, `os`, `orcamento`, `equipamento`, `pdf`, `humano` e `continuidade` deixa de ser decidida apenas por regex local e passa a ser refinada por um agente de triagem;
- a validacao de identidade deixa de ser tratada por um `IF` rigido e passa a ser gerenciada por esse agente de triagem, sem remover a regra base de seguranca por telefone + primeiro nome;
- a antiga leitura institucional por `HTTP Request` em URL exportada foi substituida por uma tool real de Google Drive;
- a busca automatica de PDF/segunda via foi removida desta versao para reduzir risco operacional.

## Desenho do workflow principal

1. `Entrada Evolution V3`
   - webhook `POST /evolution-whatsapp-atendimento-ia-v3`.
2. `Normalizar Entrada e Config V3`
   - normaliza o payload da Evolution;
   - aceita `messages.upsert`;
   - prepara placeholders centrais, inclusive:
     - `googleSheetsSpreadsheetId`
     - `googleSheetsSheetName`
     - `driveKnowledgeFileSource`
     - `driveToolWorkflowId`
3. `Inbound Elegivel?`
   - filtra inbound real, fora de grupo e com conteudo valido.
4. `Espelhar Evento no ERP`
   - envia o inbound ao webhook oficial `/webhooks/whatsapp`.
5. `Buscar Cliente ERP`
   - localiza o cliente por telefone.
6. `Identidade + Consultas + Lead Diario`
   - calcula o `identityStatus` base;
   - gera hints iniciais de intencao;
   - prepara queries de equipamentos, OS e orcamentos;
   - gera `lead_daily_key`, `lead_date` e `lead_time` em horario local.
7. `Preparar Linha Lead Diario Sheets`
   - reduz o ramo do Sheets aos 8 campos necessarios.
8. `Registrar Lead Diario Sheets`
   - grava ou atualiza a linha diaria do contato.
9. `Buscar Conversa ERP`
   - identifica o estado da conversa e se o humano ja assumiu.
10. `Humano Assumiu?`
    - interrompe a automacao quando `aguardando_humano = 1` ou `automacao_ativa = 0`.
11. `Buscar Historico Recente ERP`
    - traz o historico recente da thread.
12. `Buscar Equipamentos ERP`
    - traz equipamentos relacionados ao cliente.
13. `Buscar OS ERP`
    - traz ordens de servico abertas e recentes.
14. `Buscar Orcamentos ERP`
    - traz orcamentos recentes.
15. `Montar Prompt Triagem IA V3`
    - resume mensagem atual, historico e contexto minimo para o agente de triagem.
16. `Agente IA Triagem e Seguranca`
    - classifica a mensagem e devolve JSON puro com:
      - `intentCategory`
      - `isTechnical`
      - `needsSensitiveValidation`
      - `triageReason`
17. `OpenAI Chat Model Triagem`
    - modelo da triagem.
18. `Interpretar Triagem IA V3`
    - faz o parse defensivo do JSON da triagem;
    - aplica fallback local quando a IA nao responder no formato esperado;
    - calcula o estado final:
      - `intentCategory`
      - `technicalSearchEnabled`
      - `needsSensitiveValidation`
19. `Montar Contexto IA V3`
    - agrega historico, ERP, triagem e regras de seguranca para o agente principal.
20. `Agente IA Humanizado V3`
    - responde ao cliente;
    - pode chamar tools.
21. `OpenAI Chat Model`
    - modelo principal do agente.
22. `Memoria Postgres`
    - memoria persistente por conversa.
23. `Tool | Base Institucional Drive`
    - subworkflow-tool para ler a base institucional oficial do Google Drive.
24. `Brave Search`
    - tool para duvidas tecnicas gerais.
25. `Preparar Resposta Final V3`
    - limpa a resposta, aplica fallback seguro e limita a saida em ate 3 blocos.
26. `Separar Blocos`
    - separa os blocos de envio.
27. `Loop Over Items`
    - envia um bloco por vez.
28. `Enviar Resposta Evolution`
    - dispara o bloco pela Evolution.
29. `Aguardar 2 Segundos`
    - cria intervalo curto entre blocos.
30. `Responder Webhook com IA`
    - responde `200 OK` ao webhook quando houver atendimento automatico.
31. `Responder Webhook Ignorado` / `Responder Webhook Humano`
    - fecham o webhook sem envio quando a automacao nao deve responder.

## Tool do Google Drive

Arquivo:

- `documentacao/10-deploy/n8n/evolution-whatsapp-atendimento-ia-v3-tool-base-institucional-drive.json`

Nodes:

1. `Execute Sub-workflow Trigger`
2. `Baixar Base do Google Drive`
3. `Extrair Trechos da Base Institucional`

Funcionamento:

- o agente principal decide quando consultar a base institucional;
- a tool recebe:
  - `question`
  - `driveFileId`
  - `companyName`
- o node do Google Drive faz download real do arquivo Google Docs/Drive com conversao para `text/plain`;
- o code node extrai os blocos mais relevantes para a pergunta do cliente e devolve um `response` curto para o agente.

## Google Sheets

Colunas esperadas na aba `LeadsDia`:

- `lead_daily_key`
- `data`
- `hora`
- `nome_whatsapp`
- `telefone`
- `cliente_encontrado`
- `identity_status`
- `assunto`

O workflow usa `Append or Update Row` por `lead_daily_key`.

O node `Preparar Linha Lead Diario Sheets` envia somente estes campos:

- `lead_daily_key`
- `data`
- `hora`
- `nome_whatsapp`
- `telefone`
- `cliente_encontrado`
- `identity_status`
- `assunto`

## Ajustes minimos obrigatorios

### Credenciais no n8n

Workflow principal:

1. MySQL
   - `Buscar Cliente ERP`
   - `Buscar Conversa ERP`
   - `Buscar Historico Recente ERP`
   - `Buscar Equipamentos ERP`
   - `Buscar OS ERP`
   - `Buscar Orcamentos ERP`
2. OpenAI
   - `OpenAI Chat Model`
   - `OpenAI Chat Model Triagem`
3. Postgres
   - `Memoria Postgres`
4. Google Sheets
   - `Registrar Lead Diario Sheets`
5. Brave Search
   - `Brave Search`

Subworkflow-tool:

1. Google Drive OAuth2
   - `Baixar Base do Google Drive`

### Placeholders no node `Normalizar Entrada e Config V3`

Preencher:

- `erpWebhookToken`
- `evolutionApiKey`
- `evolutionInstance`
- `googleSheetsSpreadsheetId`
- `googleSheetsSheetName`
- `driveKnowledgeFileSource`
- `driveToolWorkflowId`
- `institutionPixKey`

Observacao:

- `driveKnowledgeFileSource` aceita o `fileId` puro do Google Drive ou a URL completa do Google Doc/Drive; o workflow extrai o `fileId` automaticamente.

### Ordem de importacao

1. Importar `evolution-whatsapp-atendimento-ia-v3-tool-base-institucional-drive.json`.
2. Copiar o `id` do subworkflow importado.
3. Importar `evolution-whatsapp-atendimento-ia-erp-v3.json`.
4. Colar o `id` do subworkflow no placeholder `driveToolWorkflowId`.
5. Configurar a credencial Google Drive no subworkflow-tool.
6. Configurar as credenciais MySQL, OpenAI, Postgres, Google Sheets e Brave Search no workflow principal.

## Regra operacional da V3 revisada

- dados operacionais do cliente so podem ser usados quando a identidade estiver confirmada e a triagem nao exigir nova validacao;
- perguntas institucionais devem ser respondidas a partir da tool oficial do Google Drive;
- Brave Search e reservado para explicacoes tecnicas gerais, nunca para dados internos do ERP;
- segunda via/PDF nao e mais buscada automaticamente nesta versao; o agente deve ser transparente e orientar o encaminhamento pela equipe quando esse assunto aparecer.
