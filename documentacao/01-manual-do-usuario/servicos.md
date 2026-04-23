# Manual do Usuario - Servicos

## Visao geral
O modulo `Servicos` centraliza o catalogo de mao de obra usado em OS e orcamentos.

Caminho: `Operacional > Servicos`

## Cadastro e edicao
Campos principais:
- `Nome` (obrigatorio)
- `Descricao` (opcional)
- `Tipo Equipamento` (categoria tecnica do servico)
- `Valor Padrao` (obrigatorio)
- `Status` (`ativo` ou inativo/encerrado)

Regra operacional:
- servicos encerrados/inativos nao aparecem no Select2 de itens da OS;
- no lancamento de itens da OS, a busca retorna apenas servicos `status = ativo` e `encerrado_em IS NULL`.

## Listagem
A grade de `Servicos` passou a exibir a coluna:
- `Tipo Equipamento`

Uso recomendado:
- preencha `Tipo Equipamento` com o tipo principal (ex.: `Smartphone`, `Notebook`, `Desktop`);
- use `Diverso` quando o servico puder ser aplicado em mais de um tipo.

## Importacao e exportacao CSV
- `Exportar CSV`: inclui `tipo_equipamento`.
- `Baixar Modelo CSV`: inclui `nome;descricao;tipo_equipamento;valor`.
- `Importar Lote`: aceita `tipo_equipamento` e `tipo equipamento` como cabecalho.

## Integracao com OS
Na aba `Itens / Servicos` da OS:
- o Select2 pode filtrar por `Tipo Equipamento` do aparelho da OS;
- os 10 servicos mais usados sao exibidos sem digitar;
- ao digitar, a busca continua por nome/descricao mantendo os filtros operacionais.
