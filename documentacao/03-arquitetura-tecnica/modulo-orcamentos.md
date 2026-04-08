# Arquitetura Tecnica - Modulo Orcamentos

## Camadas
- Controller interno: `Orcamentos` (CRUD e gestao operacional + envio por canais).
- Controller publico: `Orcamento` (token publico para cliente).
- Services:
  - `OrcamentoService` (normalizacao de valores, token, numeracao e historico).
  - `OrcamentoPdfService` (geracao de PDF versionado via Dompdf).
  - `OrcamentoMailService` (envio de e-mail com SMTP configurado no ERP).
  - `OrcamentoConversaoService` (conversao aprovado -> OS e migracao de itens).
  - `OrcamentoLifecycleService` (vencimento automatico + follow-up CRM).
- Models dedicados para entidade principal, itens, historico, envios e aprovacoes.
- Views web para listagem, formulario, detalhe, tela publica e template PDF.
- Command CLI: `OrcamentosLifecycle` (`php spark orcamentos:lifecycle`).

## Integracoes de entrada
- OS (`app/Views/os/show.php`) cria orcamento com prefill de OS/cliente/equipamento.
- Central de Mensagens (`public/assets/js/central-mensagens.js`) cria orcamento com prefill de conversa/cliente/telefone.
- Central de Mensagens agora tambem oferece fluxo inline `Gerar e enviar orcamento` sem sair da conversa.

## Integracoes de saida (fase 2)
- WhatsApp: `WhatsAppService::sendRaw` com opcao de anexo PDF.
- E-mail: `OrcamentoMailService` com configuracoes `smtp_*` da tabela `configuracoes`.
- PDF: `OrcamentoPdfService` + view `app/Views/orcamentos/pdf/orcamento.php`.
- Conversao de aprovado:
  - para OS: `OrcamentoConversaoService` + `OsModel` + `OsItemModel` + `CentralMensagensService::bindOsToConversa`;
  - para venda manual: marca operacao em `orcamentos.convertido_tipo`.
- Follow-up CRM: `OrcamentoLifecycleService` usando `CrmService::createFollowup`.

## Regras estruturais
- Entidade principal independente da OS (OS e apenas vinculo opcional).
- Suporte a cliente eventual (`cliente_nome_avulso`) sem obrigar cadastro.
- Token publico expiravel para aprovacao externa.
- Historico de status separado em tabela propria para rastreabilidade.
- Trilha operacional de envios centralizada em `orcamento_envios`.
- Status especial de fase 3:
  - `pendente_abertura_os` para aprovado avulso (sem OS vinculada).

## Padroes de implementacao
- Seguimento do padrao CI4 existente (Controller/Model/View/Service).
- Rotas protegidas por `PermissionFilter`.
- Sidebar integrada em `layouts/sidebar.php`.
- Ajuda integrada por `openDocPage('orcamentos')`.
- Confirmacoes de envio na UI via `SweetAlert2`.
- Automacao com execucao dupla:
  - sob demanda pelo painel (`POST /orcamentos/automacao/executar`);
  - em background por comando CLI (`orcamentos:lifecycle`).
