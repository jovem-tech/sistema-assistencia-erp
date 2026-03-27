# Correcao - OS: Inclusão de botão rápido para Editar Cliente

Data: 23/03/2026

## Solicitacao

Na tela de formulário da OS (`nova` e `editar`), o usuário solicitou a adição de um botão "Editar" posicionado ao lado do botão de cadastro de novo cliente (`+ Novo`). Este botão deve aparecer apenas quando um cliente estiver selecionado e permite modificar os dados rapidamente, sem a necessidade de sair ou perder o preenchimento da OS atual.

## Ajuste aplicado

Arquivos alterados:
- `app/Controllers/Clientes.php`
- `app/Views/os/form.php`
- `documentacao/01-manual-do-usuario/ordens-de-servico.md`

Mudancas implementadas:
1. Adicionado o botão "Editar" oculto inicialmente no frontend de `form.php`.
2. Incluída lógica em JS para remover a classe `d-none` (exibir o botão) sempre que um cliente for escolhido no combobox.
3. Adicionado o `<input type="hidden" name="id">` dentro do formulário já existente do modal `modalNovoCliente`.
4. Programado o click do botão "Editar" para realizar um fetch via API `clientes/getJson` que busca e autocompleta os campos do modal com os dados reais selecionados.
5. Em `Clientes.php`, o método `salvar_ajax` foi aprimorado para identificar quando recebe um `id` (caso do botão editar), efetivando um `update()` no banco em vez de um `insert()`, retornando a tag `{is_update: true}` no JSON de resposta.
6. A interface Javascript reage ao sucesso da atualização dinamicamente trocando o label no "select options" e garantindo a continuidade do fluxo sem refreshes na página (o que impactaria a criação via modal da página principal e embedded iFrames).
7. Manual do usuário atualizado para constar sobre esta nova ferramenta ágil.

## Resultado esperado

- Ao iniciar OS ou editar OS, selecionar um cliente mostra a nova opção "Editar" ao redor do botão respectivo.
- Clicar lá permite corrigir um telefone, endereço ou e-mail na hora.
- Modal com mesmo padrão já adotado.
- Redirecionamentos e perda da tela evitada por sucesso completo via AJAX.

## Atualizacao complementar - modo embed (23/03/2026)

Problema reportado:
- Dentro do modal `Nova Ordem de Servico` da pagina `/os`, o formulario embed ficou com a estrutura de abas duplicada.
- O botao `Editar` do cliente deixou de abrir modal e passou a deslocar o usuario para o formulario no fim da pagina.

Correcao aplicada:
1. A duplicacao acidental do bloco `nav-tabs` + `tab-content` em `app/Views/os/form.php` foi neutralizada.
2. O editor rapido de cliente voltou a usar o modal Bootstrap padrao, inclusive em `embed=1`.
3. O botao `Editar` volta a carregar os dados via AJAX e abrir o modal corretamente, sem deslocar a tela para o rodape.
4. O fluxo de salvar continua o mesmo (`clientes/salvar_ajax`), preservando a selecao do cliente e o contexto da OS.
5. Apos atualizar o cliente, o `select2` da OS e sincronizado imediatamente e a pagina pai `/os` recebe um `postMessage` para recarregar a listagem por AJAX sem fechar o modal.

## Ajuste complementar - sincronizacao instantanea do Select2 (23/03/2026)

Problema complementar reportado:
- Mesmo apos salvar a edicao do cliente, o texto visivel do `select2` e a lista de resultados ainda podiam manter o nome antigo ate um refresh manual.

Correcao aplicada:
1. O formulario da OS passou a reinicializar o `select2` do cliente logo apos o retorno do `salvar_ajax`.
2. O valor selecionado, o label renderizado e os resultados da busca ficam coerentes imediatamente com o nome atualizado.
3. O fluxo continua sem reload total da pagina, sem fechar o modal principal da `Nova OS` e sem perder o contexto da OS em edicao.

Arquivos atualizados neste ajuste complementar:
- `app/Views/os/form.php`
- `documentacao/01-manual-do-usuario/ordens-de-servico.md`
- `documentacao/06-modulos-do-sistema/ordens-de-servico.md`
- `documentacao/08-correcoes/2026-03-23-btn-editar-cliente-os.md`

Arquivos atualizados nesta correcao complementar:
- `app/Views/os/form.php`
- `public/assets/js/os-list-filters.js`
- `documentacao/01-manual-do-usuario/ordens-de-servico.md`
- `documentacao/06-modulos-do-sistema/ordens-de-servico.md`
