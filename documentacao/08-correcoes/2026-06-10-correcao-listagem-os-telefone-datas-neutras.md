# Correcao - listagem de OS com telefone mais discreto e datas finais neutras

Data: 10/06/2026
Migracao: nao houve alteracao de schema

## Problema

Na listagem `Ordens de Servico`, o telefone principal do cliente vinha com peso visual proximo demais ao nome, competindo pela atencao dentro da coluna `Cliente`. Ao mesmo tempo, a coluna `Datas` reutilizava o mesmo estilo de pill colorido em `Prazo`, `Conclusao` e `Entrega`, o que deixava as datas finais com destaque excessivo.

## Causa raiz

- o telefone do cliente herdava a mesma escala tipografica de apoio usada por outros blocos da grade, sem um rebaixo especifico para a linha de contato;
- `Conclusao` e `Entrega` reaproveitavam o mesmo componente `.os-date-indicator` pensado para sinalizar atraso e urgencia em `Prazo`;
- com isso, o destaque cromatico que fazia sentido para o SLA acabava poluindo tambem datas historicas de finalizacao e entrega.

## Ajuste aplicado

- o telefone da coluna `Cliente` passou a usar fonte menor e icone ligeiramente mais contido, mantendo cor, legibilidade e acao de WhatsApp;
- `Conclusao` e `Entrega` passaram a manter apenas texto neutro, sem fundo, borda ou pill colorido;
- o `Prazo` permaneceu com o comportamento visual existente, inclusive quando houver atraso;
- a correcao foi centralizada em `public/assets/css/design-system/layouts/os-list-layout.css`, sem criar componente paralelo.

## Validacao executada

- validacao visual desktop em fixture local com a mesma folha de estilos do sistema;
- validacao mobile nos breakpoints `430px`, `390px`, `360px` e `320px`;
- nos quatro breakpoints, `Conclusao` e `Entrega` permaneceram sem fundo colorido e o telefone manteve tamanho menor que o nome sem overflow horizontal na fixture mobile;
- `Prazo` continuou com o fundo colorido esperado.

## Arquivos impactados

- `public/assets/css/design-system/layouts/os-list-layout.css`
- `documentacao/01-manual-do-usuario/ordens-de-servico.md`
- `documentacao/06-modulos-do-sistema/ordens-de-servico.md`
- `documentacao/07-novas-implementacoes/historico-de-versoes.md`
- `documentacao/08-correcoes/2026-06-10-correcao-listagem-os-telefone-datas-neutras.md`

## Observacoes

- nao houve alteracao em banco de dados, rotas, DataTable server-side ou permissao;
- nao foi necessario alterar `openDocPage`, porque a ajuda da listagem continua apontando para o manual ja existente de `Ordens de Servico`.
