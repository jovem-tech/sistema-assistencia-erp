# Correcao - OS: botao "Nova OS" em modal sem redirecionamento

Data: 23/03/2026

## Solicitacao

Na tela de listagem de OS (`/os`), o botao `+ Nova OS` deveria abrir o formulario em modal (mesmo padrao do dashboard), sem exibir botao de redirecionamento externo.

## Ajuste aplicado

Arquivo alterado:
- `app/Views/os/index.php`

Mudancas implementadas:
- Botao de cabecalho `+ Nova OS` alterado de link direto para trigger de modal (`data-os-modal-role="create"`).
- Inclusao de modal com `iframe` em modo embed (`/os/nova?embed=1`).
- Inclusao de loading state no corpo do modal durante carregamento da tela.
- Inicializacao JS dedicada para abrir/fechar modal, limpar `iframe` ao fechar e tratar timeout de carregamento.
- Nao foi incluido botao `Abrir pagina` no modal da tela `/os`.

## Resultado esperado

- Clique em `+ Nova OS` abre modal na propria pagina de listagem.
- Nao ocorre redirecionamento de rota ao abrir a criacao.
- Fluxo fica consistente com o padrao embed ja usado no dashboard.
- Fechamento do modal retorna imediatamente ao estado atual da listagem.
