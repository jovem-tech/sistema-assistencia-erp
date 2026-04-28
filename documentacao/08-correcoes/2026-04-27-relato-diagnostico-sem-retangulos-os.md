# 2026-04-27 - OS: relato e diagnostico sem retangulos no documento

## Contexto

Foi solicitado manter `Relato do cliente` e `Diagnostico tecnico` no documento consolidado, mas sem os retangulos/cards internos que pesavam visualmente a secao `Equipamento`.

## O que foi corrigido

Arquivo principal:

- `app/Views/os/print.php`

Documentacao sincronizada:

- `documentacao/06-modulos-do-sistema/ordens-de-servico.md`
- `documentacao/07-novas-implementacoes/historico-de-versoes.md`
- `documentacao/07-novas-implementacoes/2026-04-27-release-v2.16.14-relato-diagnostico-sem-cards-os.md`

Ajuste aplicado:

- os textos de `Relato do cliente`, `Diagnostico tecnico`, `Solucao aplicada`, `Procedimentos executados` e observacoes passaram a ser renderizados em fluxo textual simples, sem `text-box`.

## Resultado esperado

- a secao `Equipamento` continua mostrando o contexto tecnico necessario;
- o layout fica mais limpo, sem os retangulos internos ao redor desses textos.

## Validacao local

- `php -l app/Views/os/print.php`
- `php -l app/Config/SystemRelease.php`
