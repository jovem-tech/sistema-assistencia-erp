# Historico de Versoes do Sistema

Atualizado em: 22/03/2026  
Versao atual oficial: `2.1.0`

## Politica de versionamento (SemVer)

Padrao adotado: `MAJOR.MINOR.PATCH`

- `MAJOR`: quebra de compatibilidade, mudanca estrutural relevante, migracao obrigatoria com impacto alto.
- `MINOR`: novas funcionalidades compativeis com versoes anteriores.
- `PATCH`: correcoes e ajustes sem quebra de compatibilidade.

## Regras obrigatorias para mudar versao

1. Definir o tipo de mudanca (MAJOR, MINOR ou PATCH) antes de publicar.
2. Atualizar `app/Config/SystemRelease.php`.
3. Atualizar este arquivo (`documentacao/07-novas-implementacoes/historico-de-versoes.md`).
4. Criar ou atualizar nota tecnica da release em `documentacao/07-novas-implementacoes/`.
5. Validar consistencia com override opcional em banco (`configuracoes.sistema_versao`), se utilizado.
6. Criar tag git no padrao `vMAJOR.MINOR.PATCH` quando autorizado.

## Linha do tempo oficial (consolidada)

> Observacao: releases antigas foram consolidadas retroativamente com base no historico tecnico e documental do projeto.

### v1.0.0 - Base ERP operacional
- Fundacao do ERP com autenticacao, permissoes e layout administrativo.
- Modulos base operacionais (OS, clientes, equipamentos, servicos, estoque e financeiro).
- Estrutura inicial de banco e dashboards base.

### v1.1.0 - OS + PDF + WhatsApp base
- Evolucao do fluxo de Ordem de Servico.
- Estruturacao inicial de envio de comunicacoes e documentos PDF.
- Fundacao tecnica para integracao de atendimento por WhatsApp.

### v1.2.0 - Busca e produtividade
- Busca global e melhorias de navegacao para rotinas de atendimento.
- Otimizacoes de selecao e cadastro em fluxos criticos.
- Reducao de atrito operacional em telas de cadastro/consulta.

### v1.3.0 - Padronizacao visual e UX
- Evolucao do design system com padronizacao global de componentes.
- Melhorias de consistencia visual entre modulos.
- Base para evolucoes SaaS-like de UI.

### v1.4.0 - Central de Mensagens unificada
- Remocao do legado Whaticket e consolidacao do modulo nativo.
- Central de atendimento integrada ao ERP.
- Inicio da fase de estabilizacao operacional do novo modulo.

### v1.5.0 - CRM + Contatos integrados
- Integracao entre CRM e Central de Mensagens.
- Introducao da agenda de contatos separada de clientes ERP.
- Regras de lifecycle comercial (lead e conversao) no contexto de atendimento.

### v1.6.0 - Automacao e governanca de atendimento
- Expansao de chatbot, respostas rapidas e templates.
- Melhorias em filas, atribuicao e contexto de conversa.
- Avancos em fluxo bot/humano e regras operacionais.

### v1.7.0 - Midias e fluxo de fotos estabilizados
- Correcoes de upload, preview e sincronizacao de fotos.
- Melhorias em crop/camera/galerias em OS e equipamentos.
- Ajustes para reduzir regressao visual em modais e telas densas.

### v1.8.0 - Observabilidade e diagnostico rapido
- Padronizacao de codigos de erro no backend da Central.
- Melhorias de observabilidade por endpoint e diagnostico operacional.
- Estabilizacao de polling, filtros e comportamento de conversa.

### v2.0.0 - Maturidade de plataforma
- Consolidacao de deploy/documentacao operacional de VPS.
- Hardening inicial de processos de publicacao e recuperacao.
- Padrao global de responsividade ultra compatibilidade aplicado como diretriz de sistema.

### v2.1.0 - Dashboard responsivo + modais de OS + versao no rodape
- Refatoracao do dashboard com foco mobile/tablet/desktop.
- KPI atualizado para "Equipamento Entregue".
- Grafico principal alterado para "OS abertas por mes".
- Resumo financeiro convertido para barras horizontais.
- "Ultimas OS" com visualizacao e nova OS em modal (sem redirecionamento).
- Controle de versao exibido no rodape, sincronizado via `SystemRelease`.

## Como decidir o proximo numero de versao

- Exemplo 1: adicionou funcionalidade nova sem quebrar fluxo existente -> sobe `MINOR` (`2.1.0` -> `2.2.0`).
- Exemplo 2: corrigiu bug sem alterar contrato funcional -> sobe `PATCH` (`2.1.0` -> `2.1.1`).
- Exemplo 3: alterou contrato/estrutura com impacto de compatibilidade -> sobe `MAJOR` (`2.1.0` -> `3.0.0`).
