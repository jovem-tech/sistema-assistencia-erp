# Correcao - Edicao de Cliente em Modal na OS

Data: 28/04/2026

## Problema

O formulario da OS ja possuia base tecnica para editar cliente em modal, mas a acao nao ficava descoberta o suficiente e a atualizacao AJAX dependia do endpoint de criacao.

Na pratica, isso podia:

- esconder a possibilidade de editar o cliente durante o atendimento;
- deixar o botao ausente no fluxo visivel da OS;
- misturar criacao e atualizacao no mesmo endpoint AJAX, dificultando o respeito a permissao de edicao.

## Correcao aplicada

- mantido o botao `Editar` visivel ao lado do seletor de cliente, usando estado desabilitado quando nao houver selecao;
- criada a rota `POST /clientes/atualizar_ajax/{id}` para tratar a edicao rapida de cliente em modal;
- ajustado o JavaScript da OS para abrir o modal com os dados atuais do cliente e salvar pela rota correta conforme o modo do formulario.

## Resultado esperado

- a equipe consegue editar o cliente sem sair da OS;
- a edicao acontece no mesmo modal rapido ja conhecido do sistema;
- os dados atualizados voltam para o Select2 e para o resumo do cliente sem refresh manual.
