# Manual do Administrador - Configuracao do Sistema

Atualizado em 20/03/2026.

## 1. Dados da empresa
Caminho: `Configuracoes`

Campos principais:
- nome da empresa
- CNPJ
- telefone
- email
- endereco
- tema, logo e favicon

## 2. Integracoes WhatsApp
Caminho: `Configuracoes -> Integracoes WhatsApp`

### Estrategia de providers
- `whatsapp_direct_provider`: canal operacional 1:1 (OS, cliente, PDF)
- `whatsapp_bulk_provider`: reservado para massa/campanhas (futuro CRM)

Regra operacional:
- `menuia`, `api_whats_local`, `api_whats_linux` e `webhook` sao providers diretos
- campanhas em massa devem ficar no provider de massa (`meta_oficial`)
- o ERP opera com modulo unico de atendimento (`/atendimento-whatsapp`), sem aba e sem rotas de Whaticket.

## 3. Campos de configuracao (tabela `configuracoes`)

### Comuns
- `whatsapp_enabled`
- `whatsapp_direct_provider`
- `whatsapp_bulk_provider`
- `whatsapp_test_phone`
- `whatsapp_webhook_token`

### Menuia
- `whatsapp_menuia_url`
- `whatsapp_menuia_appkey`
- `whatsapp_menuia_authkey`

### API Local (Windows)
- `whatsapp_local_node_url`
- `whatsapp_local_node_token`
- `whatsapp_local_node_origin`
- `whatsapp_local_node_timeout`

### API Linux (VPS)
- `whatsapp_linux_node_url`
- `whatsapp_linux_node_token`
- `whatsapp_linux_node_origin`
- `whatsapp_linux_node_timeout`

### Webhook generico
- `whatsapp_webhook_url`
- `whatsapp_webhook_method`
- `whatsapp_webhook_headers`
- `whatsapp_webhook_payload`

## 4. Testes no painel

- `Testar conexao`: valida provider selecionado
- `Enviar mensagem de teste`: envia texto para telefone configurado
- `Self-check inbound`: valida automaticamente:
  - acesso ao `/status` do gateway com token/origem
  - encaminhamento `gateway -> /webhooks/whatsapp`
  - webhook/token direto no ERP sem usar console
  - exibe no proprio botao/tooltip que o teste valida host, token e rota inbound
- `Gerenciar`: abre modal do gateway (status/QR/restart)

Os avisos e erros devem usar `Swal.fire`.

## 5. Modal "Gerenciar Gateway"

Status esperados:
- `connected`
- `awaiting_qr`
- `disconnected`
- `auth_failure`
- `error`
- `gateway_unreachable`

Controles:
- polling de status
- leitura de QR dinamic
- reinicio de inicializacao (`/restart`)

Metadados:
- status atual
- conta conectada
- ultimo ready
- ultimo erro

## 6. Checklist de homologacao

1. executar migrations (`php spark migrate`)
2. salvar provider direto correto
3. testar conexao
4. testar envio de texto
5. testar envio de PDF
6. validar log em `mensagens_whatsapp`
7. validar modal do gateway (status/QR/restart)

## 7. Boas praticas em producao

- usar token forte no gateway (`API_TOKEN`)
- restringir origem (`ERP_ORIGIN`)
- rodar Node com PM2
- manter logs em `/whatsapp-api/logs`
- manter porta do gateway fechada para internet (uso interno)
