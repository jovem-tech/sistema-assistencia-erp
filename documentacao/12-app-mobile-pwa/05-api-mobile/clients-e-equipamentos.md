# API Mobile - Clients e Equipments

Atualizado em 05/04/2026.

## Clientes

### `GET /api/v1/clients`

Objetivo:

- listar clientes
- pesquisar por nome, telefone e e-mail

### `GET /api/v1/clients/{id}`

Objetivo:

- retornar detalhe do cliente

### `POST /api/v1/clients`

Objetivo:

- cadastrar cliente pelo mobile

Campos usados no app:

- `nome_razao` (obrigatorio)
- `telefone1` (obrigatorio)
- `telefone2`
- `email`
- `nome_contato`
- `telefone_contato`
- `cep`
- `endereco`
- `numero`
- `bairro`
- `cidade`
- `uf`
- `complemento`
- `observacoes`

Regra de UX no mobile:

- o campo `cep` aciona preenchimento automatico de endereco (logradouro, bairro, cidade e UF) antes do envio, com possibilidade de ajuste manual pelo operador.

### `PUT|PATCH /api/v1/clients/{id}`

Objetivo:

- editar cliente existente

## Equipamentos

Os fluxos mobile de abertura de OS e cadastro rapido usam uma representacao enriquecida de equipamento para reduzir selecao equivocada quando o cliente possui aparelhos semelhantes.

### `GET /api/v1/equipments/catalog`

Objetivo:

- abastecer tipo, marca e modelo
- respeitar encadeamento `tipo -> marca -> modelo`

Filtros:

- `tipo_id`
- `marca_id`

Uso:

- o frontend encadeia `tipo -> marca -> modelo`
- a busca de marca respeita o tipo selecionado
- a busca de modelo respeita tipo e marca ja escolhidos

### `POST /api/v1/equipments/brands`

Objetivo:

- criar marca nova ou reaproveitar marca existente por nome exato

### `POST /api/v1/equipments/models`

Objetivo:

- criar modelo novo ou reaproveitar modelo existente dentro da marca

### `GET /api/v1/equipments/{id}`

Objetivo:

- retornar detalhe do equipamento e fotos hidratadas

Campos de identificacao relevantes:

- `tipo_nome`
- `marca_nome`
- `modelo_nome`
- `cor`
- `numero_serie`
- `imei`
- `fotos`

### `POST /api/v1/equipments`

Objetivo:

- cadastrar equipamento completo

Campos principais:

- `cliente_id`
- `tipo_id`
- `marca_id` ou `marca_nome`
- `modelo_id` ou `modelo_nome`
- `cor`
- `cor_hex`
- `cor_rgb`
- `numero_serie`
- `imei`
- `senha_acesso`
- `estado_fisico`
- `acessorios`
- `observacoes`
- `fotos[]` ou `foto_perfil`

Validacoes:

- cliente obrigatorio
- tipo obrigatorio
- marca obrigatoria
- modelo obrigatorio
- cor obrigatoria
- ao menos uma foto
- maximo de 4 fotos
- formatos: JPG, PNG, WEBP
- limite atual: 2MB por foto

Persistencia de fotos:

- `public/uploads/equipamentos_perfil/...`

Metadados usados pelo seletor de equipamento da OS:

- `foto_url`
- `tipo_nome`
- `marca_nome`
- `modelo_nome`
- `cor`
- `numero_serie`
- `imei`

### `PUT|PATCH|POST /api/v1/equipments/{id}`

Objetivo:

- editar equipamento e anexar novas fotos
