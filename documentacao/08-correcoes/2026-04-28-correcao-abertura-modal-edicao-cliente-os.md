# Correcao - Botao Editar Cliente sem Abrir Modal na OS

Data: 28/04/2026

## Problema

O botao `Editar` da aba `Cliente` podia permanecer visivel e habilitado na OS, mas nao abrir o modal para alguns usuarios.

Na pratica, isso acontecia porque o frontend aguardava a resposta de uma rota de leitura protegida por `clientes:visualizar` antes de mostrar o modal, mesmo em cenarios em que o operador possuia apenas `clientes:editar`.

## Correcao aplicada

- o clique passou a abrir o modal imediatamente, sem depender da conclusao do `fetch`;
- o formulario e preenchido primeiro com os dados ja disponiveis no Select2;
- foi adicionada a rota `GET /clientes/json-edicao/{id}` protegida por `clientes:editar`;
- a carga detalhada do cliente ocorre em segundo plano e, se falhar, o modal exibe aviso contextual sem travar o fluxo.

## Resultado esperado

- o operador percebe resposta imediata ao clicar em `Editar`;
- o modal abre mesmo quando a leitura detalhada posterior falha;
- perfis com permissao de edicao deixam de depender da permissao de visualizacao para usar a edicao rapida dentro da OS.
