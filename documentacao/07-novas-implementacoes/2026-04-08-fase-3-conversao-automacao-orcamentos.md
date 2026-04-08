# Fase 3 - Orcamentos (08/04/2026)

## Escopo entregue
- conversao de orcamento aprovado para:
  - OS (com abertura automatica quando necessario);
  - venda manual.
- aprovacao publica com regra:
  - sem OS vinculada -> `pendente_abertura_os`;
  - com OS vinculada -> `aprovado`.
- automacao de ciclo:
  - vencimento automatico por validade;
  - follow-up CRM para aguardando, vencido e pendente de OS.
- painel de orcamentos no sidebar com submenu operacional.
- Central de Mensagens com acao inline `Gerar e enviar orcamento`.

## Arquivos principais
- `app/Controllers/Orcamentos.php`
- `app/Controllers/Orcamento.php`
- `app/Services/OrcamentoConversaoService.php`
- `app/Services/OrcamentoLifecycleService.php`
- `app/Commands/OrcamentosLifecycle.php`
- `app/Config/Routes.php`
- `app/Views/orcamentos/index.php`
- `app/Views/orcamentos/show.php`
- `app/Views/layouts/sidebar.php`
- `app/Controllers/CentralMensagens.php`
- `app/Views/central_mensagens/index.php`
- `public/assets/js/central-mensagens.js`
- `app/Database/Migrations/2026-04-08-120000_AddOrcamentoLifecycleIndexes.php`

## Rotas novas
- `POST /orcamentos/converter/{id}`
- `POST /orcamentos/automacao/executar`
- `POST /orcamentos/central-mensagens/gerar-enviar`

## Comando novo
- `php spark orcamentos:lifecycle`

## Observacoes operacionais
- conversao automatica para OS exige `cliente_id` e `equipamento_id` no orcamento;
- quando faltar algum vinculo tecnico, o sistema bloqueia conversao automatica e orienta ajuste no cadastro;
- follow-ups automaticos sao deduplicados por `origem_evento`.

