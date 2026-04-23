# Ponte de Modelos (Integracao com APIs Externas)

Esta funcionalidade reduz erro de digitacao e padroniza cadastro de modelos (celular, notebook, TV etc.) com logica hibrida.

## Como funciona

1. Camada local: consulta `equipamentos_modelos`.
2. Camada estrutural: quando informado `tipo_id`, cruza com `equipamentos_catalogo_relacoes` para priorizar a combinacao `tipo + marca + modelo`.
3. Camada externa: se necessario, consulta Google Suggest para sugestoes e auto-cadastro.

## Endpoint

`GET /api/modelos/buscar?q={termo}&marca={nome_marca}&marca_id={id_local}&tipo={tipo_nome}&tipo_id={id_tipo}`

## Regras locais

- Com `marca_id` preenchido, retorna modelos locais mesmo sem digitar termo.
- Com `marca_id + tipo_id`, prioriza os modelos relacionados na tabela estrutural.
- Se nao houver relacao para a combinacao, aplica fallback para modelos da marca (legado).

## Auto-cadastro

Quando o usuario seleciona sugestao externa (`EXT|...`), o backend cria o modelo local e passa a usar o ID real no sistema.

## Sincronizacao de catalogo

Ao salvar equipamento/modelo com `tipo_id`, o sistema registra automaticamente a relacao:

- `tipo_id`
- `marca_id`
- `modelo_id`

na tabela `equipamentos_catalogo_relacoes`, melhorando os filtros de `Tipo -> Marca -> Modelo` nos proximos atendimentos.

