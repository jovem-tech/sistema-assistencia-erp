# 2026-04-07 - Fase 1 do Modulo de Orcamentos

## Contexto
Implantacao da fase inicial do modulo de Orcamentos para permitir operacao gerenciavel no sidebar e criacao por diferentes origens (OS, conversa ou manual).

## Entregas tecnicas
- Migration da infraestrutura completa do modulo:
  - `orcamentos`
  - `orcamento_itens`
  - `orcamento_status_historico`
  - `orcamento_envios`
  - `orcamento_aprovacoes`
- Provisionamento RBAC do modulo `orcamentos` (modulos + grupo_permissoes).
- CRUD web do modulo:
  - listagem
  - criacao
  - edicao
  - visualizacao
  - exclusao com regras de status
  - alteracao de status
- Link publico para aprovacao/rejeicao por token:
  - `GET /orcamento/{token}`
  - `POST /orcamento/aprovar/{token}`
  - `POST /orcamento/recusar/{token}`
- Entrada no sidebar em `Comercial > Orcamentos`.
- Atalho em OS (`Gerar orcamento`).
- Atalho na Central de Mensagens (`Novo orcamento` no contexto da conversa).
- Inclusao do mapeamento `openDocPage('orcamentos')`.

## Arquivos principais
- `app/Database/Migrations/2026-04-07-230000_CreateOrcamentosModuleInfrastructure.php`
- `app/Controllers/Orcamentos.php`
- `app/Controllers/Orcamento.php`
- `app/Services/OrcamentoService.php`
- `app/Views/orcamentos/index.php`
- `app/Views/orcamentos/form.php`
- `app/Views/orcamentos/show.php`
- `app/Views/orcamentos/publico.php`
- `app/Views/layouts/sidebar.php`
- `app/Config/Routes.php`
- `public/assets/js/central-mensagens.js`
- `app/Views/central_mensagens/index.php`
- `app/Views/os/show.php`
- `public/assets/js/scripts.js`

## Resultado operacional
O ERP passa a ter um modulo de Orcamentos independente da OS, com rastreabilidade de status e base pronta para as proximas fases de envio automatizado e conversao.
