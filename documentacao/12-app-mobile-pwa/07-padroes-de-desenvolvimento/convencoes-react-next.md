# Convencoes React / Next

## Arquitetura de frontend

- App Router como padrao;
- separar tela, componentes e funcoes utilitarias;
- consumir API pelo wrapper central em `src/lib/api.ts`;
- manter auth em `src/lib/auth.ts`.

## Regras

- evitar logica pesada diretamente na view sem necessidade;
- consolidar estados por contexto de tela;
- manter nomes claros de handlers;
- preferir funcoes pequenas e reusaveis.

## Contratos de UX

- feedback imediato de sucesso e erro;
- evitar refresh manual;
- preservar contexto do usuario em modais e listas;
- toda alteracao de dados deve atualizar a interface na hora.

