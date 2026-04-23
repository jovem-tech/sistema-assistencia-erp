# Manual do Administrador - Configuracao do Sistema

Atualizado em 31/03/2026 para a release `2.9.6`.

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
- o heartbeat agora respeita requests same-origin em andamento, evitando competir com salvamentos AJAX e modais operacionais
- o endpoint `sessao/heartbeat` fecha a sessao logo apos a leitura dos metadados, reduzindo risco de lock no driver de sessao por arquivo
- o heartbeat do navegador aborta em `10` segundos quando a conexao nao responde, evitando fila indefinida de requests presos
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

Regras operacionais:
- a URL canonica deve ser `https://chatbot.menuia.com/api`
- se o operador informar `https://api.menuia.com/api`, o sistema normaliza automaticamente para a URL canonica antes de salvar
- sempre que `URL`, `Appkey` ou `Authkey` da Menuia mudarem, o ultimo status validado e invalidado para evitar badge verde antigo com credencial trocada
- o botao `Testar conexao` usa um envio real e unico para o telefone de teste, evitando falso negativo por duplicidade de mensagem
- quando a validacao passar, a interface mostra `Menuia conectada`
- quando a validacao falhar, a interface mostra `Erro Menuia`
- se as credenciais estiverem preenchidas, mas ainda nao testadas com o conjunto atual, a interface mostra `Menuia nao validada`

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

## 3.1 Migracao legada via banco SQL

A migracao do sistema antigo para o ERP novo usa uma conexao secundaria e nao depende da tela de configuracoes.

Configuracoes em `.env`:
- `database.legacy.hostname`
- `database.legacy.database`
- `database.legacy.username`
- `database.legacy.password`
- `database.legacy.DBDriver`
- `database.legacy.DBPrefix`
- `database.legacy.port`
- `database.legacy.charset`
- `database.legacy.DBCollat`

Configuracoes operacionais da carga:
- `legacyImport.sourceName`
- `legacyImport.batchSize`
- `legacyImport.allowCatalogAutoCreate`
- `legacyImport.writeInitialStatusHistory`

Regras:
- `sourceName` identifica a origem que sera gravada em `legacy_origem`
- `batchSize` controla a leitura em lotes do pipeline
- `allowCatalogAutoCreate` permite criar `tipo`, `marca` e `modelo` ausentes
- `writeInitialStatusHistory` grava a primeira linha de historico da OS migrada

Observacao operacional atual:
- no ambiente local, o banco legado real esta em `database.legacy.database = erp`
- para o legado `erp`, o importador deriva o snapshot de equipamento a partir da tabela `os`, porque nao existe entidade compativel de equipamento por cliente no schema antigo
- quando houver `numero_serie` ou `IMEI` valido, o importador tenta consolidar snapshots repetidos em um equipamento canonico sem perder a rastreabilidade legada
- essa consolidacao nao usa nome ou semelhanca textual; a relacao fica auditada em `legacy_import_aliases`
- a carga atual tambem le detalhes complementares da OS em `orcamentos`, `orcamento_itens`, `servicos_orc`, `produtos_orc`, `historico_status_os`, `os_historico`, `os_defeitos` e `os_historicos`

Referencia operacional:
- `documentacao/02-manual-administrador/migracao-legado-sql.md`

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
5. abrir um modal com salvamento AJAX (ex.: cliente/equipamento via OS) e confirmar que o heartbeat nao gera timeout concorrente
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

## 9. Operacao da migracao legada

Comandos disponiveis:
- `php spark legacy:preflight`
- `php spark legacy:prepare-target`
- `php spark legacy:import --execute`
- `php spark legacy:report`

Uso recomendado:
1. configurar `database.legacy.*` e revisar `app/Config/LegacyImport.php`
2. rodar `php spark legacy:preflight`
3. revisar os avisos de telefone e corrigir bloqueios reais de status, orfaos e catalogos
4. vistoriar a limpeza com `php spark legacy:prepare-target`
5. executar a limpeza controlada em homologacao com `php spark legacy:prepare-target --execute` ou `php spark legacy:import --execute --wipe-target`
6. executar a carga em homologacao limpa com `php spark legacy:import --execute`
7. validar contagens, amostras e busca por `numero_os_legado`

Observacoes:
- o numero oficial do novo ERP continua em `numero_os`
- o numero antigo fica preservado em `numero_os_legado`
- a importacao e idempotente por `legacy_origem + legacy_id`
- a visualizacao da OS passa a refletir laudos, observacoes, itens, pecas, historico importado e notas legadas preservadas
