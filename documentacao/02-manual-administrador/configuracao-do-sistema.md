# Manual do Administrador - Configuracao do Sistema

Atualizado em 27/03/2026.

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

### Sessao e seguranca
- `sessao_inatividade_minutos`

Regras:
- valor minimo: `5` minutos
- valor maximo: `1440` minutos
- o timeout de inatividade do filtro de autenticacao passa a usar essa chave como fonte unica
- o frontend protegido recebe esse valor por meta tags para monitorar expiracao e manter heartbeat discreto enquanto houver atividade real
- se o usuario estiver com `Lembrar-me` ativo, a expiracao por inatividade continua ignorada no fluxo atual

### Comuns
- `whatsapp_enabled`
- `whatsapp_direct_provider`
- `whatsapp_bulk_provider`
- `whatsapp_test_phone`
- `whatsapp_webhook_token`
- `sistema_versao` (opcional, sobrescreve a versao padrao exibida no rodape e na tela de login)

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
2. validar o valor de `sessao_inatividade_minutos` em `Configuracoes -> Sessao e Seguranca`
3. deixar a tela ociosa acima do limite e confirmar o aviso claro de sessao expirada
4. manter um formulario aberto com digitacao/atividade e confirmar que o heartbeat evita expiracao indevida
5. salvar provider direto correto
6. testar conexao
7. testar envio de texto
8. testar envio de PDF
9. validar log em `mensagens_whatsapp`
10. validar modal do gateway (status/QR/restart)

## 6.1 Diagnostico rapido de erro na Central de Mensagens

Quando `POST /atendimento-whatsapp/enviar` falhar por indisponibilidade do provider:
- a Central mostra SweetAlert2 com mensagem operacional amigavel, sem expor erro cru de rede
- o backend responde `503` com `CM_ENVIO_PROVIDER_UNAVAILABLE`
- em ambiente Windows/local, o motivo mais comum e gateway Node parado em `http://127.0.0.1:3001`

Checklist rapido:
1. abrir `Configuracoes -> Integracoes WhatsApp`
2. validar provider direto ativo (`api_whats_local` ou `api_whats_linux`)
3. usar `Testar conexao`
4. se o gateway estiver inacessivel, usar `Iniciar Servidor` ou `Gerenciar -> restart`
5. revisar token, origem e URL salvos no ERP

## 7. Boas praticas em producao

- usar token forte no gateway (`API_TOKEN`)
- restringir origem (`ERP_ORIGIN`)
- rodar Node com PM2
- manter logs em `/whatsapp-api/logs`
- manter porta do gateway fechada para internet (uso interno)
- manter a versao da release atualizada no rodape e na tela de login:
  - padrao tecnico em `app/Config/SystemRelease.php`
  - override opcional por chave `sistema_versao` na tabela `configuracoes`

## 8. Fluxo de Trabalho da OS

Existe uma tela administrativa dedicada para controlar o workflow de status:

- caminho: `Gestao de Conhecimento -> Fluxo de Trabalho OS`
- manual dedicado: `documentacao/02-manual-administrador/fluxo-de-trabalho-os.md`

Uso recomendado:
- revisar a ordem dos status antes de liberar novos fluxos operacionais
- configurar explicitamente os retornos permitidos quando o time trabalha com reabertura ou retrocesso de etapa
- testar a troca de status diretamente na listagem `/os` depois de qualquer alteracao estrutural
