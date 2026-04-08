# Documentacao - Sistema de Assistencia Tecnica

> Jovem Tech - Atualizado em 08/04/2026

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
| `12-app-mobile-pwa` | documentacao exclusiva do app mobile/PWA |

## Leitura rapida recomendada
- Operacao de OS: `01-manual-do-usuario/ordens-de-servico.md`
- Workflow administrativo de OS: `02-manual-administrador/fluxo-de-trabalho-os.md`
- Operacao de Contatos: `01-manual-do-usuario/contatos.md`
- Operacao de Orcamentos: `01-manual-do-usuario/orcamentos.md`
- Configuracao pre-CRM: `02-manual-administrador/configuracao-do-sistema.md`
- Admin de Orcamentos e RBAC: `02-manual-administrador/orcamentos-e-permissoes.md`
- Migracao legada SQL (operacao): `02-manual-administrador/migracao-legado-sql.md`
- Fluxo tecnico pre-CRM: `06-modulos-do-sistema/ordens-de-servico.md`
- Modulo Orcamentos (tecnico): `06-modulos-do-sistema/orcamentos.md`
- Migracao legada SQL (arquitetura): `03-arquitetura-tecnica/migracao-legado-sql.md`
- Arquitetura do modulo Orcamentos: `03-arquitetura-tecnica/modulo-orcamentos.md`
- Modulo WhatsApp: `06-modulos-do-sistema/whatsapp.md`
- Modulo CRM: `06-modulos-do-sistema/crm.md`
- Modulo Contatos (tecnico): `06-modulos-do-sistema/contatos.md`
- CRM - Metricas Marketing: `06-modulos-do-sistema/crm.md#metricas-marketing`
- Central de Mensagens: `06-modulos-do-sistema/central-de-mensagens.md`
- Central Mobile PWA: `06-modulos-do-sistema/central-mobile-pwa.md`
- Hub exclusivo do App Mobile/PWA: `12-app-mobile-pwa/README.md`
- Politica de versoes do App Mobile/PWA: `12-app-mobile-pwa/09-versionamento-e-releases/politica-de-versoes.md`
- Historico de versoes do App Mobile/PWA: `12-app-mobile-pwa/09-versionamento-e-releases/historico-de-versoes.md`
- Design system do App Mobile/PWA: `12-app-mobile-pwa/06-design-system/fundamentos.md`
- Central - Chatbot: `06-modulos-do-sistema/central-de-mensagens.md#chatbot`
- Central - Metricas: `06-modulos-do-sistema/central-de-mensagens.md#metricas`
- Central - Filas: `06-modulos-do-sistema/central-de-mensagens.md#filas`
- Central - FAQ: `06-modulos-do-sistema/central-de-mensagens.md#faq`
- Banco pre-CRM: `04-banco-de-dados/tabelas-principais.md`
- Banco do modulo Orcamentos: `04-banco-de-dados/orcamentos.md`
- Rotas pre-CRM: `05-api/rotas.md`
- Rotas web do modulo Orcamentos: `05-api/orcamentos-web.md`
- Correcao Central (erros + observabilidade + clientes novos + funil): `08-correcoes/2026-03-20-central-mensagens-codigos-erro-observabilidade-clientes-novos.md`
- Correcao CRM/Contatos (engajamento temporal configuravel): `08-correcoes/2026-03-20-engajamento-temporal-contatos-crm.md`
- Correcao Dashboard (responsividade, modais de OS e graficos): `08-correcoes/2026-03-22-dashboard-refactor-responsivo-modal-os.md`
- Correcao Dashboard (alinhamento do "Ver ..." no rodape dos 4 cards): `08-correcoes/2026-03-23-dashboard-alinhamento-footer-cards.md`
- Correcao Dashboard (navbar fixa + origem oficial do faturamento do mes): `08-correcoes/2026-04-06-dashboard-navbar-fixa-e-origem-faturamento.md`
- Correcao OS (botao `+ Nova OS` abrindo modal sem redirecionamento): `08-correcoes/2026-03-23-os-nova-os-modal-sem-redirecionamento.md`
- Correcao OS (filtros avancados premium com AJAX, chips, persistencia e drawer mobile): `08-correcoes/2026-03-23-os-filtros-avancados-premium-ajax.md`
- Correcao padrao global de responsividade ultra compatibilidade: `08-correcoes/2026-03-22-padrao-global-responsividade-ultra-compatibilidade.md`
- Guia de atualizacao de VPS sem downtime (contingencia + Ubuntu novo): `08-correcoes/2026-03-22-guia-atualizacao-vps-sem-downtime.md`
- Entrega pre-CRM base: `07-novas-implementacoes/2026-03-pre-crm-foundation-os-whatsapp-pdf.md`
- Entrega CRM + Central: `07-novas-implementacoes/2026-03-crm-central-mensagens-integrados.md`
- Evolucao faseada da Central de Atendimento: `07-novas-implementacoes/2026-03-central-atendimento-inteligente-faseada.md`
- Historico oficial de versoes (1.0.0 -> 2.12.1): `07-novas-implementacoes/historico-de-versoes.md`
- Release 2.12.1 (hotfix de navbar fixa no ERP web): `07-novas-implementacoes/2026-04-08-release-v2.12.1-hotfix-navbar-fixa.md`
- Release 2.12.0 (modulo profissional de Orcamentos com envio, aprovacao publica, conversao e automacao): `07-novas-implementacoes/2026-04-08-release-v2.12.0-modulo-orcamentos-profissional.md`
- Entrega da fase 2 de Orcamentos (envio WhatsApp/e-mail/PDF): `07-novas-implementacoes/2026-04-07-fase-2-envio-orcamentos-whatsapp-email-pdf.md`
- Entrega da fase 1 de Orcamentos: `07-novas-implementacoes/2026-04-07-fase-1-modulo-orcamentos.md`
- Entrega da fase 3 de Orcamentos (conversao OS/venda + automacao + Central): `07-novas-implementacoes/2026-04-08-fase-3-conversao-automacao-orcamentos.md`
- Release 2.11.5 (selecao rica de equipamentos na OS web e no app mobile/PWA): `07-novas-implementacoes/2026-04-04-release-v2.11.5-selecao-rica-equipamentos-os-web-mobile.md`
- Release 2.11.4 (consolidacao oficial do app mobile/PWA, governanca e deploy seguro sem dados de teste): `07-novas-implementacoes/2026-04-04-release-v2.11.4-app-mobile-pwa-consolidacao.md`
- Release 2.11.3 (hardening do Service Worker para fallback de Response no PWA): `07-novas-implementacoes/2026-04-03-release-v2.11.3-mobile-pwa-sw-response-hardening.md`
- Release 2.11.2 (abertura completa de OS no PWA mobile): `07-novas-implementacoes/2026-04-03-release-v2.11.2-mobile-pwa-nova-os-completa.md`
- Release 2.11.1 (push mobile inbound com envio Web Push real): `07-novas-implementacoes/2026-04-03-release-v2.11.1-mobile-push-inbound-real.md`
- Release 2.11.0 (API mobile v1 + base PWA paralela): `07-novas-implementacoes/2026-04-03-release-v2.11.0-central-mobile-pwa-mvp-base.md`
- Release 2.10.17 (bot/atendimento humano movidos para o menu hamburguer): `07-novas-implementacoes/2026-04-02-release-v2.10.17-central-mensagens-modo-no-menu-hamburguer.md`
- Release 2.10.16 (status/prioridade explicitos no cabecalho + modo binario bot/humano): `07-novas-implementacoes/2026-04-02-release-v2.10.16-central-mensagens-header-status-prioridade-modo-binario.md`
- Release 2.10.15 (acoes da thread em menu hamburguer no cabecalho da coluna central): `07-novas-implementacoes/2026-04-02-release-v2.10.15-central-mensagens-menu-hamburguer-no-cabecalho.md`
- Release 2.10.14 (hotfix tooltip/dropdown + timeout padrao do gateway em 30s): `07-novas-implementacoes/2026-04-02-release-v2.10.14-central-mensagens-tooltip-dropdown-fix.md`
- Release 2.10.13 (modo unico de atendimento + acoes avancadas no menu da Central): `07-novas-implementacoes/2026-04-02-release-v2.10.13-central-mensagens-modo-unico-e-acoes-avancadas.md`
- Release 2.10.12 (action bar SaaS premium em 3 grupos na Central): `07-novas-implementacoes/2026-04-02-release-v2.10.12-central-mensagens-action-bar-saas.md`
- Correcao 2.10.11 (timeout resiliente entre polling e envio concorrente): `08-correcoes/2026-04-02-central-mensagens-timeout-polling-envio-concorrente.md`
- Release 2.10.11 (polling rapido + lock livre em envio + timeout dinamico): `07-novas-implementacoes/2026-04-02-release-v2.10.11-central-mensagens-timeout-polling-envio.md`
- Correcao 2.10.10 (timeout no polling incremental da Central): `08-correcoes/2026-04-02-central-mensagens-timeout-polling-incremental.md`
- Release 2.10.10 (polling resiliente com separacao de sync pesado): `07-novas-implementacoes/2026-04-02-release-v2.10.10-central-mensagens-polling-resiliente.md`
- Correcao 2.10.9 (inbound multimidia com hidratacao de anexos na Central): `08-correcoes/2026-04-02-central-mensagens-inbound-multimidia-hidratacao.md`
- Correcao 2.11.3 (Service Worker do PWA com fallback de Response offline): `08-correcoes/2026-04-03-mobile-pwa-sw-response-fallback.md`
- Release 2.10.9 (sync historico com midia + classificacao de voz + dedupe hidratavel): `07-novas-implementacoes/2026-04-02-release-v2.10.9-central-mensagens-inbound-multimidia-hidratacao.md`
- Correcao 2.10.8 (controles operacionais e sync sem flicker na Central): `08-correcoes/2026-04-02-central-mensagens-controles-operacionais-sem-flicker.md`
- Release 2.10.8 (status/atribuicao/prioridade e encerramento com concluir/arquivar): `07-novas-implementacoes/2026-04-02-release-v2.10.8-central-mensagens-controle-operacional-sem-flicker.md`
- Correcao 2.10.7 (sincronizacao inbound silenciosa no chat): `08-correcoes/2026-04-02-central-mensagens-sync-inbound-silencioso.md`
- Release 2.10.7 (sincronizacao inbound silenciosa no chat): `07-novas-implementacoes/2026-04-02-release-v2.10.7-central-mensagens-sync-inbound-silencioso.md`
- Correcao 2.10.6 (composer da Central com altura compacta forcada): `08-correcoes/2026-04-02-central-mensagens-composer-altura-estavel.md`
- Release 2.10.6 (composer da Central com altura compacta forcada): `07-novas-implementacoes/2026-04-02-release-v2.10.6-central-mensagens-composer-altura-forcada.md`
- Release 2.10.4 (fila da Central ordenada por movimentacao real): `07-novas-implementacoes/2026-04-02-release-v2.10.4-central-mensagens-ordenacao-movimentacao-real.md`
- Release 2.10.3 (filtros recolhidos, fila estavel e composer compacto): `07-novas-implementacoes/2026-04-01-release-v2.10.3-central-mensagens-filtros-cronologia-composer.md`
- Release 2.10.2 (sidebar recolhida e scroll explicito por coluna): `07-novas-implementacoes/2026-04-01-release-v2.10.2-central-mensagens-scroll-e-sidebar-auto.md`
- Release 2.10.1 (conexao operacional, envio otimista e rascunho por conversa): `07-novas-implementacoes/2026-04-01-release-v2.10.1-central-mensagens-conexao-otimista-rascunho.md`
- Release 2.10.0 (filtros rapidos + inbound assistido): `07-novas-implementacoes/2026-04-01-release-v2.10.0-central-mensagens-inbound-assistido.md`
- Release 2.9.4 (confirmacao visual ao salvar cliente pelo modal da OS): `07-novas-implementacoes/2026-03-30-release-v2.9.4-feedback-sucesso-cliente-os.md`
- Release 2.9.3 (hardening do heartbeat de sessao em modais e salvamentos AJAX): `07-novas-implementacoes/2026-03-30-release-v2.9.3-heartbeat-sessao-vps.md`
- Release 2.9.2 (filtro explicito de OS legado + correcao de acentuacao na navbar): `07-novas-implementacoes/2026-03-30-release-v2.9.2-navbar-busca-os-legado-e-acentuacao.md`
- Release 2.9.5 (gateway WhatsApp Linux alinhado na VPS + labels seguras na busca global): `07-novas-implementacoes/2026-03-30-release-v2.9.5-gateway-vps-e-busca-global.md`
- Release 2.9.6 (Menuia com validacao real, URL canonica e badge confiavel): `07-novas-implementacoes/2026-03-31-release-v2.9.6-menuia-validacao-e-badges-reais.md`
- Release 2.9.7 (deduplicacao visual de mensagens outbound na Central): `08-correcoes/2026-03-31-central-mensagens-deduplicacao-visual-outbound.md`
- Release 2.9.1 (origem explicita dos valores consolidados das OS legadas): `07-novas-implementacoes/2026-03-29-release-v2.9.1-origem-explicita-valores-os-legado.md`
- Release 2.9.0 (backfill completo dos detalhes das OS legadas): `07-novas-implementacoes/2026-03-29-release-v2.9.0-backfill-completo-detalhes-os-legado.md`
- Release 2.8.2 (remocao dos cards Cliente e Equipamento da aba Informacoes): `07-novas-implementacoes/2026-03-29-release-v2.8.2-remocao-cards-cliente-equipamento-informacoes-os.md`
- Release 2.8.1 (limpeza da aba Informacoes + numero legado empilhado na listagem): `07-novas-implementacoes/2026-03-29-release-v2.8.1-limpeza-informacoes-os-e-numero-legado-empilhado.md`
- Release 2.8.0 (gestao visual de OS legado + busca global por numero antigo): `07-novas-implementacoes/2026-03-28-release-v2.8.0-os-legado-filtro-e-busca-global.md`
- Release 2.7.5 (hardening da consolidacao por documento ausente): `07-novas-implementacoes/2026-03-28-release-v2.7.5-hardening-documento-ausente-legado.md`
- Release 2.7.4 (consolidacao segura de clientes duplicados por CPF/CNPJ no legado): `07-novas-implementacoes/2026-03-28-release-v2.7.4-consolidacao-clientes-duplicados-cpf-legado.md`
- Release 2.7.3 (importacao resiliente de clientes sem telefone no legado): `07-novas-implementacoes/2026-03-28-release-v2.7.3-importacao-clientes-sem-telefone-legado.md`
- Release 2.7.2 (anti-duplicacao segura de equipamentos na migracao legada): `07-novas-implementacoes/2026-03-28-release-v2.7.2-anti-duplicacao-equipamentos-legado.md`
- Release 2.6.1 (historico e progresso abaixo das fotos na OS): `07-novas-implementacoes/2026-03-27-release-v2.6.1-historico-progresso-lateral-os.md`
- Release 2.6.2 (acoes rapidas de status e remocao do Valor Final na OS): `07-novas-implementacoes/2026-03-27-release-v2.6.2-acoes-rapidas-status-os.md`
- Release 2.6.3 (modal de status da listagem alinhado com a visualizacao da OS): `07-novas-implementacoes/2026-03-27-release-v2.6.3-modal-status-listagem-os.md`
- Release 2.6.4 (scroll restaurado no modal de status da listagem): `07-novas-implementacoes/2026-03-27-release-v2.6.4-scroll-modal-status-listagem-os.md`
- Release 2.6.8 (indicacao explicita de atraso no prazo da OS): `07-novas-implementacoes/2026-03-28-release-v2.6.8-indicacao-atraso-prazo-os.md`
- Release 2.7.0 (migracao legada SQL com preflight, importacao e rastreabilidade): `07-novas-implementacoes/2026-03-28-release-v2.7.0-migracao-legado-sql.md`
- Release 2.7.1 (banco `erp` real + limpeza controlada da base atual): `07-novas-implementacoes/2026-03-28-release-v2.7.1-erp-real-e-limpeza-controlada.md`
- Release 2.6.7 (modal do cliente funcional e regras operacionais no modal de prazos da OS): `07-novas-implementacoes/2026-03-28-release-v2.6.7-modal-cliente-e-prazos-os.md`
- Release 2.6.6 (atalhos contextuais de cliente, equipamento, datas e orcamento na listagem de OS): `07-novas-implementacoes/2026-03-27-release-v2.6.6-atalhos-contextuais-listagem-os.md`
- Release 2.6.5 (modal de status da listagem com contexto de cliente e equipamento): `07-novas-implementacoes/2026-03-27-release-v2.6.5-contexto-cliente-equipamento-modal-status-os.md`
- Release 2.6.0 (hierarquia da visualizacao da OS + progresso vertical): `07-novas-implementacoes/2026-03-27-release-v2.6.0-hierarquia-visualizacao-os.md`
- Release 2.1.0 (dashboard + versao no rodape): `07-novas-implementacoes/2026-03-release-2.1.0-dashboard-versao-rodape.md`
- Gateway local WhatsApp (arquitetura): `07-novas-implementacoes/2026-03-como-foi-implementado-a-api-local-do-whatsapp.md`
- Gateway local WhatsApp (producao-ready): `07-novas-implementacoes/2026-03-gateway-local-whatsapp-producao.md`
- Deploy em VPS Linux: `10-deploy/linux-vps-deployment.md`
- Manual tecnico oficial VPS Ubuntu 24.04 (com troubleshooting real): `10-deploy/manual-tecnico-oficial-vps-ubuntu-24-ci4.md`
- Atualizacao de VPS sem downtime (Blue/Green + contingencia): `10-deploy/atualizacao-vps-sem-downtime.md`
- Registro de deploy da release v2.5.9 na VPS: `10-deploy/2026-03-27-atualizacao-vps-release-v2.5.9.md`
- Registro de deploy da release v2.9.2 na VPS (espelhamento completo de codigo, uploads e banco): `10-deploy/2026-03-30-atualizacao-vps-release-v2.9.2.md`
- Registro de deploy da release v2.11.5 na VPS (selecao rica de equipamentos no ERP web e no app mobile, sem banco nem uploads): `10-deploy/2026-04-04-atualizacao-vps-release-v2.11.5.md`
- Registro de deploy da release v2.11.4 na VPS (app mobile/PWA oficial, sem sincronizar dados ou fotos de teste): `10-deploy/2026-04-04-atualizacao-vps-release-v2.11.4.md`
- Registro de deploy da release v2.9.3 na VPS (hotfix do heartbeat de sessao com validacao autenticada): `10-deploy/2026-03-30-atualizacao-vps-release-v2.9.3.md`
- Registro de deploy da release v2.9.5 na VPS (gateway Linux realinhado + busca global normalizada): `10-deploy/2026-03-30-atualizacao-vps-release-v2.9.5.md`
- Registro de deploy da release v2.10.4 na VPS (patch seletivo, backlog sincronizado e gateway validado): `10-deploy/2026-04-02-atualizacao-vps-release-v2.10.4.md`
- Registro de deploy da release v2.10.6 na VPS (hotfix do composer da Central com patch seletivo): `10-deploy/2026-04-02-atualizacao-vps-release-v2.10.6.md`
- Registro de deploy da release v2.10.7 na VPS (sync inbound silencioso no chat com patch seletivo): `10-deploy/2026-04-02-atualizacao-vps-release-v2.10.7.md`
- Registro de deploy da release v2.10.8 na VPS (controles operacionais da Central + sync sem flicker): `10-deploy/2026-04-02-atualizacao-vps-release-v2.10.8.md`
- Registro de deploy da release v2.10.9 na VPS (inbound multimidia com hidratacao de anexos): `10-deploy/2026-04-02-atualizacao-vps-release-v2.10.9.md`
- Registro de deploy da release v2.10.10 na VPS (polling incremental resiliente sem timeout em cascata): `10-deploy/2026-04-02-atualizacao-vps-release-v2.10.10.md`
- Registro de deploy da release v2.10.11 na VPS (timeout resiliente entre polling e envio concorrente): `10-deploy/2026-04-02-atualizacao-vps-release-v2.10.11.md`
- Registro de deploy da release v2.11.0 na VPS (API mobile v1 + app PWA em PM2 + proxy `/atendimento-mobile-app`): `10-deploy/2026-04-03-atualizacao-vps-release-v2.11.0.md`
- Registro de deploy da release v2.11.2 na VPS (abertura completa de OS no PWA mobile): `10-deploy/2026-04-03-atualizacao-vps-release-v2.11.2.md`
- Registro de deploy da release v2.10.17 na VPS (menu hamburguer funcional e modo bot/humano dentro do menu): `10-deploy/2026-04-02-atualizacao-vps-release-v2.10.17.md`
- Observacao operacional de deploy: use `--exclude='/vendor/'` para nao remover `public/assets/vendor`
- Script oficial de automacao de deploy: `10-deploy/scripts/install_erp.sh`
- Politica operacional do agente autonomo DevOps + Engenharia Fullstack: `10-deploy/agente-autonomo-devops-engenharia-fullstack.md`
- Roadmap: `09-roadmap/funcionalidades-planejadas.md`
- Roadmap do modulo Orcamentos: `09-roadmap/orcamentos-roadmap.md`
