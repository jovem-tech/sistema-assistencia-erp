# Arquitetura Geral

## Visao logica

`PWA mobile -> API /api/v1 -> Services/Models do ERP -> mesmo banco de dados`

## Camadas

- interface mobile em Next.js;
- camada de consumo de API em `src/lib`;
- API interna no CodeIgniter 4;
- regras de negocio e modelos do ERP;
- banco compartilhado.

## Principios

- nao duplicar logica do ERP sem necessidade;
- separar claramente frontend mobile e backend do ERP;
- manter contratos HTTP previsiveis;
- documentar toda extensao de API usada pelo app.

