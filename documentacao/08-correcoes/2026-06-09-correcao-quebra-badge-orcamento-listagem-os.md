# Correcao - quebra do badge de orcamento na listagem de OS

Data: 09/06/2026
Versao: 2.23.18

## Problema

Na listagem `/os`, a coluna `Status / Orcamento` podia exibir status comerciais longos de orcamento em uma unica linha.

Com textos como `Pendente de envio para aprovacao do cliente`, o badge avançava visualmente para a coluna `Valor`, prejudicando a leitura da grade operacional.

## Ajuste realizado

- Removido o comportamento de linha unica do badge de orcamento na listagem.
- Aplicada quebra interna com `white-space: normal`, `overflow-wrap: anywhere`, `word-break: break-word` e `max-width: 100%`.
- Reforçado `min-width: 0` no container do status para permitir que o flex respeite a largura real da celula.
- O ajuste fica restrito a `public/assets/css/design-system/layouts/os-list-layout.css`.

## Impacto operacional

Status comerciais grandes passam a ocupar duas ou mais linhas dentro da propria coluna `Status / Orcamento`, sem invadir `Valor` ou os botoes de acao.

## Arquivos alterados

- `public/assets/css/design-system/layouts/os-list-layout.css`
- `app/Config/SystemRelease.php`
- `documentacao/01-manual-do-usuario/ordens-de-servico.md`
- `documentacao/06-modulos-do-sistema/ordens-de-servico.md`
- `documentacao/07-novas-implementacoes/historico-de-versoes.md`
