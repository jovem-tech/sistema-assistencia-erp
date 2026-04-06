# Correcao - Central de Mensagens: sincronizacao inbound silenciosa

Data: 02/04/2026  
Release associada: `v2.10.7`

## Problema observado

A sincronizacao automatica em background estava usando a barra de conexao da thread para exibir `Sincronizando mensagens inbound...`, ocupando espaco visual dentro do chat e atrapalhando a leitura e a digitacao.

## Ajuste aplicado

- o `auto sync` deixou de promover a barra de conexao para o estado `is-syncing`;
- a barra `cm-connection-strip` passou a ficar oculta enquanto a conexao estiver normal;
- a exibicao da barra foi mantida apenas para sincronizacao manual e cenarios reais de `warn` ou `offline`;
- o feedback do background sync ficou concentrado no badge de inbound do topo da tela.

## Arquivos alterados

- `app/Views/central_mensagens/index.php`
- `public/assets/js/central-mensagens.js`
- `public/assets/css/design-system/layouts/central-mensagens.css`
- `app/Config/SystemRelease.php`
- `documentacao/06-modulos-do-sistema/central-de-mensagens.md`
- `documentacao/07-novas-implementacoes/historico-de-versoes.md`
- `documentacao/README.md`

## Resultado esperado

- conversa mais limpa durante uso continuo;
- digitacao e leitura sem interferencia visual do sincronismo automatico;
- feedback operacional preservado apenas quando realmente necessario.
