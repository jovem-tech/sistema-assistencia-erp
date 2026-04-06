# Hotfix App Mobile/PWA v0.4.1 - service worker e cache em producao

Data: 04/04/2026

## Objetivo

Corrigir falhas observadas no app publicado em `app.jovemtech.eco.br`, onde o `sw.js` registrava erro ao tentar adicionar a raiz do app ao cache e podia devolver `503 Service Unavailable` em navegacoes interceptadas.

## Causa raiz

- o service worker tentava pre-cachear `./` na fase de install;
- no subdominio dedicado, a raiz do app responde com redirect, o que tornava o `cache.add` instavel;
- o `fetch` handler gravava em cache respostas nao saudaveis e nao excluia chamadas `/api/`.

## Correcao aplicada

- remocao do pre-cache da raiz do app;
- manutencao apenas de assets estaveis no `CORE_ASSETS`;
- pre-cache com `fetch` defensivo e armazenamento somente quando a resposta vier `200 OK`;
- exclusao de chamadas `/api/` da camada de cache do service worker;
- fallback offline ajustado para priorizar rota de login em cache, sem responder `503` artificial quando existir fallback local.

## Impacto funcional

- reduz erros de instalacao/ativacao do `sw.js`;
- evita poluicao de cache com respostas `503`;
- melhora a estabilidade do PWA publicado em subdominio dedicado;
- nao altera banco, uploads nem contratos da API.

## Compatibilidade

- App Mobile/PWA: `0.4.1`
- ERP minimo compativel: `2.11.4`
