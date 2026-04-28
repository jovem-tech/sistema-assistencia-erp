# 2026-04-27 - OS: remocao da secao de relato/diagnostico no PDF

## Contexto

Foi solicitada a retirada da secao `Relato do Cliente & Diagnostico Tecnico` do PDF consolidado da OS.

## O que foi corrigido

Arquivo principal:

- `app/Views/os/print.php`

Documentacao sincronizada:

- `documentacao/06-modulos-do-sistema/ordens-de-servico.md`
- `documentacao/07-novas-implementacoes/historico-de-versoes.md`
- `documentacao/07-novas-implementacoes/2026-04-27-release-v2.16.11-remocao-relato-pdf-os.md`

Ajuste aplicado:

- a secao foi encapsulada para nao ser renderizada quando `renderMode === 'pdf'`.

## Resultado esperado

- o PDF consolidado da OS deixa de mostrar a secao `Relato do Cliente & Diagnostico Tecnico`;
- o restante do documento continua sendo montado normalmente.

## Validacao local

- `php -l app/Views/os/print.php`
- `php -l app/Config/SystemRelease.php`
