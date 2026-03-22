# Documentacao - Sistema de Assistencia Tecnica

> Jovem Tech - Atualizado em 20/03/2026

## Estrutura

| Pasta | Conteudo principal |
|---|---|
| `00-visao-geral` | contexto do produto e stack |
| `01-manual-do-usuario` | operacao por modulo |
| `02-manual-administrador` | configuracao e administracao |
| `03-arquitetura-tecnica` | organizacao do codigo e componentes |
| `04-banco-de-dados` | tabelas e modelagem |
| `05-api` | rotas HTTP internas |
| `06-modulos-do-sistema` | visao tecnica por modulo |
| `07-novas-implementacoes` | historico de entregas |
| `08-correcoes` | historico de bugfix |
| `09-roadmap` | planejamento futuro |
| `10-deploy` | instalacao e publicacao |
| `11-padroes` | convencoes de projeto |

## Leitura rapida recomendada
- Operacao de OS: `01-manual-do-usuario/ordens-de-servico.md`
- Operacao de Contatos: `01-manual-do-usuario/contatos.md`
- Configuracao pre-CRM: `02-manual-administrador/configuracao-do-sistema.md`
- Fluxo tecnico pre-CRM: `06-modulos-do-sistema/ordens-de-servico.md`
- Modulo WhatsApp: `06-modulos-do-sistema/whatsapp.md`
- Modulo CRM: `06-modulos-do-sistema/crm.md`
- Modulo Contatos (tecnico): `06-modulos-do-sistema/contatos.md`
- CRM - Metricas Marketing: `06-modulos-do-sistema/crm.md#metricas-marketing`
- Central de Mensagens: `06-modulos-do-sistema/central-de-mensagens.md`
- Central - Chatbot: `06-modulos-do-sistema/central-de-mensagens.md#chatbot`
- Central - Metricas: `06-modulos-do-sistema/central-de-mensagens.md#metricas`
- Central - Filas: `06-modulos-do-sistema/central-de-mensagens.md#filas`
- Central - FAQ: `06-modulos-do-sistema/central-de-mensagens.md#faq`
- Banco pre-CRM: `04-banco-de-dados/tabelas-principais.md`
- Rotas pre-CRM: `05-api/rotas.md`
- Correcao Central (erros + observabilidade + clientes novos + funil): `08-correcoes/2026-03-20-central-mensagens-codigos-erro-observabilidade-clientes-novos.md`
- Correcao CRM/Contatos (engajamento temporal configuravel): `08-correcoes/2026-03-20-engajamento-temporal-contatos-crm.md`
- Guia de atualizacao de VPS sem downtime (contingencia + Ubuntu novo): `08-correcoes/2026-03-22-guia-atualizacao-vps-sem-downtime.md`
- Entrega pre-CRM base: `07-novas-implementacoes/2026-03-pre-crm-foundation-os-whatsapp-pdf.md`
- Entrega CRM + Central: `07-novas-implementacoes/2026-03-crm-central-mensagens-integrados.md`
- Evolucao faseada da Central de Atendimento: `07-novas-implementacoes/2026-03-central-atendimento-inteligente-faseada.md`
- Gateway local WhatsApp (arquitetura): `07-novas-implementacoes/2026-03-como-foi-implementado-a-api-local-do-whatsapp.md`
- Gateway local WhatsApp (producao-ready): `07-novas-implementacoes/2026-03-gateway-local-whatsapp-producao.md`
- Deploy em VPS Linux: `10-deploy/linux-vps-deployment.md`
- Manual tecnico oficial VPS Ubuntu 24.04 (com troubleshooting real): `10-deploy/manual-tecnico-oficial-vps-ubuntu-24-ci4.md`
- Atualizacao de VPS sem downtime (Blue/Green + contingencia): `10-deploy/atualizacao-vps-sem-downtime.md`
- Script oficial de automacao de deploy: `10-deploy/scripts/install_erp.sh`
- Politica operacional do agente autonomo DevOps + Engenharia Fullstack: `10-deploy/agente-autonomo-devops-engenharia-fullstack.md`
- Roadmap: `09-roadmap/funcionalidades-planejadas.md`
