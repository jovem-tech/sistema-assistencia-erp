# API Mobile - Orders

Atualizado em 05/04/2026.

## `GET /api/v1/orders`

Objetivo:

- listar ordens de servico do app

Filtros:

- `q`
- `status`
- `page`
- `per_page`

Pesquisa cobre:

- numero da OS
- numero legado
- status
- estado de fluxo
- prioridade
- relato
- observacoes
- cliente
- telefone
- e-mail
- tipo
- marca
- modelo
- serie
- IMEI
- tecnico
- datas

Campos relevantes por item:

- `cliente_nome`
- `equip_tipo`
- `equip_marca`
- `equip_modelo`
- `equip_foto_url`
- `status_label`
- `prioridade`

## `GET /api/v1/orders/meta`

Objetivo:

- devolver metadados para abertura/edicao da OS

Retorna:

- `clients`
- `equipments`
- `technicians`
- `statuses_grouped`
- `statuses`
- `priorities`
- `reported_defects`
- `checklist_entrada`
- `defects`
- `selected`

Formato relevante de `reported_defects[]`:

- `categoria`
- `icone`
- `itens[]`
  - `id`
  - `texto_relato`
  - `categoria`

Formato relevante de `equipments[]`:

- `id`
- `cliente_id`
- `tipo_id`
- `tipo_nome`
- `marca_id`
- `marca_nome`
- `modelo_id`
- `modelo_nome`
- `cor`
- `numero_serie`
- `imei`
- `foto_url`
- `fotos[]`
  - `id`
  - `url`
  - `is_principal`
- `label`

Uso no app:

- abastecer o seletor rico de equipamento da tela `/os/nova`
- permitir busca por tipo, marca, modelo, cor, serie e IMEI
- diferenciar equipamentos iguais do mesmo cliente por foto e identificadores tecnicos
- permitir abrir um carrossel das fotos de perfil diretamente pela miniatura do equipamento, sem sair do fluxo de abertura da OS
- alimentar o modal rapido do campo `Relato do cliente` com os itens vindos de `Defeitos Relatados` do ERP

## `GET /api/v1/orders/{id}`

Objetivo:

- detalhe completo da OS

## `POST /api/v1/orders`

Objetivo:

- criar OS completa pelo app

Campos principais:

- cliente e equipamento
- relato
- status
- prioridade
- tecnico
- datas
- valores
- pagamento
- garantia
- observacoes
- defeitos
- acessorios
- checklist de entrada (itens, respostas e fotos por discrepancia)
- fotos de entrada
- preferencia de notificacao do cliente na abertura:
  - `notificar_cliente` (`0` ou `1`)
  - `notificacao_cliente_modo` (`message` ou `message_pdf`)

Persistencias especificas:

- fotos de entrada: `public/uploads/os_anormalidades`
- fotos de acessorios: `public/uploads/acessorios/OS_<slug>/`
- quando `notificar_cliente=1`, a API tenta envio imediato ao cliente via `WhatsAppService` no fechamento da criacao da OS
- em `notificacao_cliente_modo=message_pdf`, a API gera PDF de abertura via `OsPdfService` e envia junto quando o provedor suporta anexo
- se o PDF falhar, o fluxo faz fallback para mensagem de abertura sem PDF (sem impedir a criacao da OS)
- o retorno da criacao inclui `notificacao_cliente` com detalhes do despacho:
  - `ok`
  - `mode` (modo solicitado)
  - `effective_mode` (modo efetivamente usado no envio)
  - `method` (`template` ou `fallback_manual`)
  - `provider`
  - `message`
  - `pdf` (resultado da etapa de PDF quando aplicavel)
- alem do retorno HTTP, o backend cria aviso operacional no mobile:
  - `order.client_notification.sent`
  - `order.client_notification.failed`

## `PUT|PATCH /api/v1/orders/{id}`

Objetivo:

- atualizar OS existente

Campos suportados:

- status
- prioridade
- tecnico
- relato
- diagnostico
- solucao
- datas
- valores
- pagamento
- garantia
- observacoes
- defeitos
