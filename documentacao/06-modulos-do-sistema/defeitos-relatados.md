# Módulo: Defeitos Relatados

## Finalidade
Base administrativa de relatos comuns informados por clientes na recepção da OS.

## Tabela Utilizada
- `defeitos_relatados`

## Rotas Principais
- `GET /defeitosrelatados`
- `GET /defeitosrelatados/novo`
- `POST /defeitosrelatados/salvar`
- `GET /defeitosrelatados/editar/{id}`
- `POST /defeitosrelatados/atualizar/{id}`
- `POST /defeitosrelatados/status/{id}`
- `GET /defeitosrelatados/excluir/{id}`

## Filtro na Listagem
- A tela de listagem possui filtro por categoria (`?categoria=`).
- O filtro é aplicado no backend e retorna somente os relatos da categoria selecionada.

## Cadastro de Categoria
- Na tela de novo/edição, o campo **Categoria** exibe:
  - sugestões padrão (datalist),
  - lista visual de **categorias já cadastradas**.
- Objetivo: evitar duplicidade de categoria com nomes diferentes.

## Normalização de Nomes
- Categorias legadas `Audio` e `Camera` agora são exibidas como `Áudio` e `Câmera` na interface.
- A normalização vale para listagem e para os botões rápidos consumidos em `/os/nova`.

## Uso na Abertura da OS
- A tela `/os/nova` consome essa base para montar os dropdowns de seleção rápida do campo **Relato do Cliente**.
- Ao selecionar um item, o texto é inserido no textarea em nova linha, sem prefixo redundante.

## Permissões
- Reusa o módulo de permissões `defeitos` (`visualizar`, `criar`, `editar`, `excluir`).
