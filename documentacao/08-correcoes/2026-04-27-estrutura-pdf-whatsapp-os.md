# 2026-04-27 - OS: separacao estrutural do PDF final em relacao ao preview

## Contexto

Mesmo apos o primeiro ajuste de fidelidade, o PDF anexado no WhatsApp ainda podia sair com paginas vazias e distribuicao ruim de secoes por causa da estrutura paginada em tabela que funcionava bem no navegador, mas era frágil no `Dompdf`.

## O que foi corrigido

Arquivo principal:

- `app/Views/os/print.php`

Documentacao sincronizada:

- `documentacao/06-modulos-do-sistema/ordens-de-servico.md`
- `documentacao/07-novas-implementacoes/historico-de-versoes.md`
- `documentacao/07-novas-implementacoes/2026-04-27-release-v2.16.10-estrutura-pdf-os.md`

Ajustes aplicados:

- criacao da flag interna `usePreviewPageShell` para separar claramente preview do navegador e geracao final de PDF;
- manutencao da casca paginada em tabela apenas na pre-visualizacao;
- substituicao dessa casca por divisores de quebra dedicados no modo `render-mode-pdf`;
- preservacao dos mesmos blocos macro do documento:
  - resumo inicial da OS;
  - corpo tecnico-financeiro;
  - fotos anexadas.

## Resultado esperado

- o PDF final do WhatsApp deve reduzir paginas vazias e reposicionamentos bruscos;
- a ordem macro das secoes deve permanecer mais estavel no anexo final;
- o operador continua vendo a paginação rica no preview, mas o cliente recebe um PDF mais robusto para o motor de renderizacao.

## Validacao local

- `php -l app/Views/os/print.php`
- `php -l app/Config/SystemRelease.php`
