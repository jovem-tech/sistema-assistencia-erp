# Documentação - Sistema de Assistência Técnica

> Jovem Tech  
> ERP atual: `2.15.0`  
> App mobile/PWA: `0.4.2`  
> Atualizado em `23/04/2026`

## Objetivo

Este diretório concentra a documentação funcional, técnica e operacional do ERP da assistência técnica. O conteúdo abaixo foi consolidado para refletir o estado real publicado na VPS, com foco especial nos módulos de `Ordens de Serviço` e `Orçamentos`.

## Estrutura

| Pasta | Conteúdo principal |
|---|---|
| `00-visao-geral` | contexto do produto e stack |
| `01-manual-do-usuario` | operação diária por módulo |
| `02-manual-administrador` | configuração, permissões e governança |
| `03-arquitetura-tecnica` | estrutura do código e fluxos internos |
| `04-banco-de-dados` | tabelas, relacionamentos e regras de persistência |
| `05-api` | rotas HTTP internas e públicas |
| `06-modulos-do-sistema` | visão técnica por módulo |
| `07-novas-implementacoes` | releases e entregas funcionais |
| `08-correcoes` | histórico de bugfixes e hotfixes |
| `09-roadmap` | planejamento futuro |
| `10-deploy` | instalação, atualização e operação em VPS |
| `11-padroes` | convenções de projeto |
| `12-app-mobile-pwa` | documentação dedicada do app mobile/PWA |

## Leitura rápida recomendada

### Operação

- OS - manual do usuário: `01-manual-do-usuario/ordens-de-servico.md`
- Orçamentos - manual do usuário: `01-manual-do-usuario/orcamentos.md`
- Fluxo administrativo de OS: `02-manual-administrador/fluxo-de-trabalho-os.md`
- Orçamentos e permissões: `02-manual-administrador/orcamentos-e-permissoes.md`

### Técnica

- Módulo OS: `06-modulos-do-sistema/ordens-de-servico.md`
- Módulo Orçamentos: `06-modulos-do-sistema/orcamentos.md`
- Arquitetura do módulo de orçamentos: `03-arquitetura-tecnica/modulo-orcamentos.md`
- Estrutura de pastas: `03-arquitetura-tecnica/estrutura-de-pastas.md`
- Rotas web de orçamentos: `05-api/orcamentos-web.md`

### Versão e release atual

- Histórico oficial de versões do ERP: `07-novas-implementacoes/historico-de-versoes.md`
- Release atual: `07-novas-implementacoes/2026-04-23-release-v2.15.0-os-orcamentos-documentacao-e-versionamento.md`
- Registro desta atualização na VPS: `10-deploy/2026-04-23-atualizacao-vps-release-v2.15.0.md`

### App mobile/PWA

- Hub oficial do app: `12-app-mobile-pwa/README.md`
- Política de versões do app: `12-app-mobile-pwa/09-versionamento-e-releases/politica-de-versoes.md`
- Histórico do app: `12-app-mobile-pwa/09-versionamento-e-releases/historico-de-versoes.md`

## O que foi consolidado nesta atualização documental

- nova leitura operacional da visualização da OS;
- sincronização oficial entre status de OS e status do orçamento vinculado;
- comportamento do botão de orçamento na OS;
- comportamento protegido do modal `Nova OS` na listagem;
- revisão e normalização UTF-8 dos documentos centrais de OS e Orçamentos;
- alinhamento entre documentação, release publicada e versão do sistema.

## Regra editorial

Sempre que houver nova release do ERP:

1. atualizar `app/Config/SystemRelease.php`;
2. revisar este índice;
3. atualizar o histórico oficial em `07-novas-implementacoes/historico-de-versoes.md`;
4. publicar a nota técnica da release;
5. registrar a atualização de VPS em `10-deploy/`, quando aplicável.
