# Workflow n8n de Atendimento com IA e Busca em Clientes

Data: 02/05/2026

## Objetivo

Adicionar um segundo workflow importavel no n8n, separado do fluxo atual, para:

- receber o inbound via webhook;
- espelhar o evento no ERP;
- localizar cliente na tabela `clientes` pelo telefone;
- montar contexto operacional com os dados encontrados;
- pedir a resposta para um modelo de IA;
- enviar a resposta ao cliente pelo canal oficial da Menuia.

## Arquivo entregue

- `documentacao/10-deploy/n8n/atendimento-clientes-whatsapp-ai-clientes.json`

## Regra de preservacao

Esse novo workflow foi criado em arquivo separado para nao alterar o fluxo atual ja homologado:

- fluxo atual: `documentacao/10-deploy/n8n/atendimento-clientes-whatsapp.json`
- fluxo novo com IA: `documentacao/10-deploy/n8n/atendimento-clientes-whatsapp-ai-clientes.json`

## Desenho do fluxo

1. `Entrada WhatsApp IA`
   - webhook `POST /whatsapp-atendimento-clientes-ai`.
2. `Normalizar Entrada e Credenciais`
   - normaliza telefone;
   - extrai texto;
   - monta SQL de busca do cliente;
   - prepara payload inbound do ERP;
   - centraliza placeholders de credenciais.
3. `Espelhar Inbound no ERP`
   - envia o inbound para `POST /webhooks/whatsapp`.
4. `Buscar Cliente no Banco`
   - consulta `clientes` por:
     - `telefone1`
     - `telefone2`
     - `telefone_contato`
5. `Montar Contexto IA`
   - resume o cadastro encontrado;
   - monta `instructions` e `input` para a IA.
6. `Agente IA OpenAI`
   - chama `POST https://api.openai.com/v1/responses`.
7. `Preparar Resposta Final`
   - extrai o texto final da resposta da IA;
   - aplica fallback seguro se a IA falhar.
8. `Enviar Resposta Menuia IA`
   - envia a resposta para a Menuia em `multipart/form-data`.

## Consulta ao banco

O workflow busca cliente por telefone normalizado (com e sem DDI `55`).

Campos usados na tabela `clientes`:

- `telefone1`
- `telefone2`
- `telefone_contato`
- `nome_razao`
- `email`
- `nome_contato`
- `cidade`
- `uf`
- `observacoes`

## Configuracoes que precisam ser preenchidas

No node `Normalizar Entrada e Credenciais`, ajustar:

- `companyName`
- `erpWebhookUrl`
- `erpWebhookToken`
- `erpOrigin`
- `menuiaAppKey`
- `menuiaAuthKey`
- `openaiApiKey`
- `openaiModel`

No node `Buscar Cliente no Banco`, vincular a credencial MySQL correta do ERP.

## Regras de resposta da IA

O prompt foi desenhado para:

- responder em pt-BR;
- usar o nome do cliente quando localizado;
- nao inventar status de OS, orcamentos, prazos ou valores;
- pedir nome completo quando nao localizar cadastro;
- informar que localizou o cadastro, mas ainda precisa consultar a OS quando a pessoa pedir status e o fluxo so tiver dados do cliente.

## Observacoes operacionais

- o fluxo atual continua intocado;
- o novo workflow deve ser homologado primeiro em ambiente de teste;
- como o arquivo fica versionado no projeto, as chaves devem ser preenchidas diretamente no n8n ou substituidas antes da importacao final, evitando persistir segredo em repositrio.
