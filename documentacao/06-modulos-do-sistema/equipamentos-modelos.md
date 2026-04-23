# Modulo: Modelos de Equipamento

## Finalidade
Cadastrar modelos vinculados a uma marca e suportar autocomplete inteligente para OS e Orcamentos.

## Tabelas Utilizadas
- `equipamentos_modelos`
- `equipamentos_catalogo_relacoes` (relacao estrutural `tipo + marca + modelo`)

## Rotas Principais
- `GET /equipamentosmodelos`
- `POST /equipamentosmodelos/salvar`
- `POST /equipamentosmodelos/salvar_ajax`
- `POST /equipamentosmodelos/atualizar_ajax/{id}`
- `POST /equipamentosmodelos/importar`
- `GET/POST /equipamentosmodelos/por-marca`

## Integracao com autocomplete
Endpoint proxy:
`GET /api/modelos/buscar?q={termo}&marca={nome}&marca_id={id}&tipo={tipo}&tipo_id={id_tipo}`

Regra de filtro local:
- se houver `marca_id + tipo_id`, a busca local prioriza modelos vinculados em `equipamentos_catalogo_relacoes`;
- se a relacao ainda nao existir para a combinacao, aplica fallback para modelos da marca.

## Sincronizacao de relacao estrutural
- ao salvar/editar modelo por AJAX com `tipo_id`, o backend registra `tipo + marca + modelo` em `equipamentos_catalogo_relacoes`;
- ao salvar equipamento (OS/cadastro completo), a relacao tambem e sincronizada;
- essa sincronizacao reduz casos de lista vazia em `Modelo` quando o operador seleciona `Tipo` e `Marca`.

## Permissoes
`equipamentos:visualizar`, `equipamentos:criar`, `equipamentos:editar`, `equipamentos:importar`, `equipamentos:excluir`
