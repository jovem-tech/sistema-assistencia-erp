# 2026-04-27 - OS: remocao dos cards de relato/diagnostico da secao equipamento

## Contexto

Foi solicitada a retirada dos cards detalhados de `Relato do cliente` e `Diagnostico tecnico` que haviam sido inseridos dentro da secao `Equipamento` do documento consolidado da OS.

## O que foi corrigido

Arquivo principal:

- `app/Views/os/print.php`

Documentacao sincronizada:

- `documentacao/06-modulos-do-sistema/ordens-de-servico.md`
- `documentacao/07-novas-implementacoes/historico-de-versoes.md`
- `documentacao/07-novas-implementacoes/2026-04-27-release-v2.16.13-limpeza-equipamento-documento-os.md`

Ajuste aplicado:

- remocao do bloco em `dual-table` com `Relato do cliente`, `Diagnostico tecnico`, `Solucao aplicada`, `Procedimentos executados` e observacoes dentro da secao `Equipamento`.

## Resultado esperado

- a secao `Equipamento` volta a mostrar apenas os dados tecnicos principais do aparelho;
- o documento consolidado fica mais limpo e menos carregado visualmente nessa area.

## Validacao local

- `php -l app/Views/os/print.php`
- `php -l app/Config/SystemRelease.php`
