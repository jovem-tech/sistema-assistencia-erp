# Modulo: Contatos (Agenda de Relacionamento)

Atualizado em 20/03/2026.

## Objetivo tecnico
Separar **agenda de contatos** do cadastro formal de **clientes** para evitar conversao precoce de leads.

Princípio:
- entrada por WhatsApp gera/atualiza contato
- cliente so e consolidado quando existe vinculo operacional (principalmente OS)

## Rotas
- `GET /contatos`
- `GET /contatos/novo`
- `POST /contatos/salvar`
- `GET /contatos/editar/{id}`
- `POST /contatos/atualizar/{id}`
- `GET /contatos/excluir/{id}`

## Modelagem
Tabela principal: `contatos`

Campos relevantes:
- `id`
- `cliente_id` (nullable)
- `nome`
- `telefone`
- `telefone_normalizado` (unique)
- `email`
- `whatsapp_nome_perfil`
- `origem`
- `status_relacionamento` (`lead_novo`, `lead_qualificado`, `cliente_convertido`)
- `engajamento_status` (`ativo`, `em_risco`, `inativo`)
- `engajamento_recalculado_em`
- `qualificado_em`
- `convertido_em`
- `observacoes`
- `ultimo_contato_em`
- `created_at`, `updated_at`

Relacionamentos:
- `conversas_whatsapp.contato_id -> contatos.id`
- `contatos.cliente_id -> clientes.id` (quando houver conversao)

## Integracao com Central de Mensagens
Endpoint operacional:
- `POST /atendimento-whatsapp/conversa/{id}/cadastrar-contato`

Comportamento:
- valida permissao de escrita (`clientes:criar` ou `clientes:editar`)
- reaproveita contato existente por telefone
- atualiza `ultimo_contato_em`
- classifica etapa relacional do contato:
  - com cliente vinculado -> `cliente_convertido`
  - sem cliente, com nome completo confiavel -> `lead_qualificado`
  - sem cliente e sem nome completo -> `lead_novo`
- vincula `conversas_whatsapp.contato_id`
- so propaga `cliente_id` para conversa quando contato ja estiver vinculado

## Integracao com abertura de OS
Fluxo:
- Central gera link `Nova OS` com contexto (`origem_conversa_id`, `origem_contato_id`, `cliente_id`, `telefone`, `nome_hint`)
- formulario de OS mostra origem da Central e pre-selecao de cliente quando houver
- ao salvar OS, `Os::sincronizarOrigemWhatsappNaAbertura()` sincroniza:
  - `conversas_whatsapp.cliente_id`
  - `conversas_whatsapp.contato_id`
  - `contatos.cliente_id` (quando antes estava nulo) e etapa para `cliente_convertido`
  - vinculo em `conversa_os`

## Regras de lifecycle (contato -> cliente)

Implementacao centralizada em `ContatoModel`:
- `buildLeadPayload(...)`
- `buildClienteConvertidoPayload(...)`
- `supportsLifecycleFields()`

Regras preservadas:
- com cliente vinculado -> `cliente_convertido`
- sem cliente, com nome completo confiavel -> `lead_qualificado`
- sem cliente e sem nome completo -> `lead_novo`

## Engajamento temporal (layer separado do lifecycle)

Implementacao centralizada em `ContatoModel`:
- `supportsEngajamentoFields()`
- `normalizeEngajamentoPeriodos(...)`
- `recalculateEngajamentoBulk(...)`

Regra:
- usa `COALESCE(ultimo_contato_em, updated_at, created_at)` como base de recencia
- compara com periodos configuraveis
  - `crm_engajamento_ativo_dias` (padrao `30`)
  - `crm_engajamento_risco_dias` (padrao `90`)

Classificacao:
- ate `ativo_dias` -> `ativo`
- ate `risco_dias` -> `em_risco`
- acima disso -> `inativo`

Observacao:
- nao altera `status_relacionamento`; apenas adiciona leitura temporal para marketing/reativacao.

Aplicado por:
- `CentralMensagensService` (resolucao/sincronizacao por telefone)
- `CentralMensagens::cadastrarContatoConversa()`
- `ChatbotService::sincronizarNomeNoContato()` (qualificacao automatica)
- `Os::sincronizarOrigemWhatsappNaAbertura()` (conversao operacional)

## Beneficios operacionais
- menor poluicao da base de clientes
- triagem comercial mais precisa
- rastreabilidade de conversao contato -> cliente -> OS
- segmentacao mais consistente para CRM/marketing
- funil objetivo para metricas de qualificacao e conversao
