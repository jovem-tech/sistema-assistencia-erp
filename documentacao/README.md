# Documentacao - Sistema de Assistencia Tecnica

> Jovem Tech  
> ERP atual: `2.15.2`  
> App mobile/PWA: `0.4.2`  
> Atualizado em `24/04/2026`

## Objetivo

Este diretorio concentra a documentacao funcional, tecnica e operacional do ERP da assistencia tecnica.

O indice abaixo foi revisado para refletir a release `2.15.2`, com destaque para o fluxo oficial de `desenvolvimento -> homologacao -> producao` entre `PC`, `notebook`, `VM` e `VPS`.

## Estrutura

| Pasta | Conteudo principal |
|---|---|
| `00-visao-geral` | contexto do produto e stack |
| `01-manual-do-usuario` | operacao diaria por modulo |
| `02-manual-administrador` | configuracao, permissoes e governanca |
| `03-arquitetura-tecnica` | estrutura do codigo e fluxos internos |
| `04-banco-de-dados` | tabelas, relacionamentos e regras de persistencia |
| `05-api` | rotas HTTP internas e publicas |
| `06-modulos-do-sistema` | visao tecnica por modulo |
| `07-novas-implementacoes` | releases e entregas funcionais |
| `08-correcoes` | historico de bugfixes e hotfixes |
| `09-roadmap` | planejamento futuro |
| `10-deploy` | instalacao, atualizacao e operacao em VPS |
| `11-padroes` | convencoes de projeto |
| `12-app-mobile-pwa` | documentacao dedicada do app mobile/PWA |

## Leitura rapida recomendada

### Operacao

- OS - manual do usuario: `01-manual-do-usuario/ordens-de-servico.md`
- Orcamentos - manual do usuario: `01-manual-do-usuario/orcamentos.md`
- Fluxo administrativo de OS: `02-manual-administrador/fluxo-de-trabalho-os.md`

### Tecnica

- Modulo OS: `06-modulos-do-sistema/ordens-de-servico.md`
- Modulo Orcamentos: `06-modulos-do-sistema/orcamentos.md`
- Arquitetura do modulo de orcamentos: `03-arquitetura-tecnica/modulo-orcamentos.md`
- Estrutura de pastas: `03-arquitetura-tecnica/estrutura-de-pastas.md`
- Fluxo Git multiambiente: `10-deploy/fluxo-git-multiambiente.md`

### Versao e release atual

- Historico oficial de versoes do ERP: `07-novas-implementacoes/historico-de-versoes.md`
- Release atual: `07-novas-implementacoes/2026-04-24-release-v2.15.2-fluxo-homolog-vm-e-governanca-deploy.md`
- Registro da release anterior na VPS: `10-deploy/2026-04-23-atualizacao-vps-release-v2.15.0.md`

### App mobile/PWA

- Hub oficial do app: `12-app-mobile-pwa/README.md`
- Politica de versoes do app: `12-app-mobile-pwa/09-versionamento-e-releases/politica-de-versoes.md`
- Historico do app: `12-app-mobile-pwa/09-versionamento-e-releases/historico-de-versoes.md`

## O que foi consolidado nesta atualizacao documental

- fluxo Git multiambiente atualizado para o modelo oficial `develop-desktop -> homolog-vm -> main -> VPS`;
- homologacao da `VM Ubuntu 24` formalizada como etapa obrigatoria antes da promocao para `main`;
- checklist de backup da `VPS` consolidado com codigo, banco e arquivos antes de cada deploy;
- indice principal sincronizado com a release `2.15.2` e com a nova nota tecnica de governanca operacional.

## Regra editorial

Sempre que houver nova release do ERP:

1. atualizar `app/Config/SystemRelease.php`;
2. revisar este indice;
3. atualizar o historico oficial em `07-novas-implementacoes/historico-de-versoes.md`;
4. publicar a nota tecnica da release;
5. registrar a atualizacao de VPS em `10-deploy/`, quando aplicavel.
