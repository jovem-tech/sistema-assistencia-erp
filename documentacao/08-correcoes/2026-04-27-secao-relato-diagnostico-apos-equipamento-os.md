# 2026-04-27 - OS: nova secao de relato e diagnostico apos equipamento

## Contexto

Foi solicitada a criacao de uma secao propria para `Relato do Cliente e Diagnostico Tecnico` logo apos `Equipamento`, podendo substituir a antiga secao `Tecnico Responsavel`.

## O que foi corrigido

Arquivo principal:

- `app/Views/os/print.php`

Documentacao sincronizada:

- `documentacao/06-modulos-do-sistema/ordens-de-servico.md`
- `documentacao/07-novas-implementacoes/historico-de-versoes.md`
- `documentacao/07-novas-implementacoes/2026-04-27-release-v2.16.16-secao-relato-diagnostico-os.md`

Ajuste aplicado:

- remocao da secao `Tecnico Responsavel`;
- criacao da secao `Relato do Cliente e Diagnostico Tecnico` imediatamente abaixo de `Equipamento`.

## Resultado esperado

- `Equipamento` volta a focar apenas os dados tecnicos;
- relato e diagnostico passam a ter uma secao propria, mais clara na hierarquia do documento.

## Validacao local

- `php -l app/Views/os/print.php`
- `php -l app/Config/SystemRelease.php`
