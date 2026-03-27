# Listagem de OS - Coluna foto e visualizador com abas

## Contexto
A listagem `/os` precisava exibir a foto principal do equipamento sem obrigar o usuario a abrir a OS apenas para conferir o perfil visual do item atendido.

## Correcao aplicada
- adicionada a coluna `Foto` no inicio da grade da listagem
- miniatura passou a abrir modal visualizador com duas galerias:
  - `Fotos do Equipamento`
  - `Fotos da Abertura`
- criado endpoint dedicado `GET /os/fotos/{id}` para alimentar o modal por AJAX
- mantida a estrategia responsiva existente, com migracao da coluna para o expansor quando a largura util da tabela nao comportar todas as colunas com conforto

## Impacto
- ganho de contexto visual direto na triagem e na consulta de OS
- reducao de cliques para conferir estado visual do equipamento
- separacao clara entre fotos permanentes do perfil do equipamento e fotos operacionais da abertura
