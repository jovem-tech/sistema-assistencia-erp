# Manual do Usuario - Contatos

Atualizado em 20/03/2026.

## Objetivo
O modulo **Contatos** funciona como agenda telefonica operacional do ERP.

Regra principal:
- nem todo contato vira cliente
- contato so vira cliente quando existir vinculo real de negocio (ex.: abertura de OS)

## Onde acessar
Menu: `COMERCIAL -> Pessoas -> Contatos`

Rota: `/contatos`

## Diferenca entre Contato e Cliente
- **Contato**: registro leve para atendimento, historico de conversa e triagem.
- **Cliente**: cadastro formal usado em OS, equipamentos, financeiro e CRM completo.

Um contato pode permanecer com selo **Cliente novo** ate ser vinculado a um cliente do ERP.

## O que a tela mostra
- nome do contato
- telefone
- origem (manual, whatsapp, importacao, site, indicacao)
- cliente vinculado (quando existir)
- etapa relacional (lead novo, lead qualificado, cliente convertido)
- engajamento temporal (ativo, em risco, inativo)
- ultimo contato

Filtros disponiveis:
- busca por nome, telefone, e-mail, nome de perfil WhatsApp ou cliente vinculado
- vinculo: todos / sem cadastro em clientes / vinculados a cliente
- etapa relacional: todas / lead novo / lead qualificado / cliente convertido
- engajamento: todos / ativo / em risco / inativo

## Cadastro manual
Use o botao `Novo Contato`.

Campos principais:
- `telefone` (obrigatorio)
- `nome` (opcional)
- `email` (opcional)
- `origem` (opcional, padrao `manual`)
- `whatsapp_nome_perfil` (opcional)
- `ultimo_contato_em` (opcional)
- `observacoes` (opcional)

Regra de validacao:
- telefone e normalizado para digitos
- nao permite duplicidade por telefone normalizado

## Fluxo vindo da Central WhatsApp
Na lista de conversas da Central:
- conversa sem cliente exibe selo **Cliente novo**
- operador pode usar `Salvar contato`
- o contato vai para a tabela `contatos` (nao vai direto para `clientes`)

## Funil relacional do contato
Cada contato pode passar por etapas:

- **Lead novo**: contato entrou na base, ainda sem qualificacao de nome completo.
- **Lead qualificado**: nome e sobrenome capturados com confianca (manual ou chatbot).
- **Cliente convertido**: contato recebeu vinculo com `clientes.id` apos contexto operacional (principalmente abertura de OS).

Essas etapas aparecem:
- na lista de contatos (coluna e filtro de etapa)
- na Central de Mensagens (badges de cliente novo e lead qualificado)
- nas metricas de marketing do CRM

## Engajamento temporal (nao substitui lifecycle)
O sistema tambem classifica contatos por **tempo sem interacao**, sem alterar a etapa relacional:

- **Ativo**: contato recente, dentro da janela de atividade.
- **Em risco**: contato sem interacao recente, perto de esfriar.
- **Inativo**: contato sem relacionamento por periodo maior.

Importante:
- um contato pode continuar como `cliente_convertido` no lifecycle e ficar `inativo` no engajamento.
- o engajamento e recalculado automaticamente conforme o tempo passa.

Onde configurar os periodos:
- menu `CRM -> Metricas Marketing`
- bloco `Configuracao de janelas de engajamento`
- campos:
  - `Ativo ate (dias)`
  - `Em risco ate (dias)`

## Dashboard Marketing (visao rapida SaaS)
Tela: `CRM -> Metricas Marketing`

O painel foi estruturado para leitura de decisao em poucos segundos:
- cards KPI no topo (captados, qualificados, convertidos, taxa e conversas)
- deltas automaticos comparando os ultimos 7 dias com os 7 anteriores (quando houver base)
- grafico principal de tendencia (linhas para captados, qualificados, convertidos e conversas)
- funil visual `captados -> qualificados -> convertidos`
- insights automaticos com alertas de queda/subida
- ranking de atendimento por responsavel

Filtros disponiveis na cabecalha:
- periodo rapido (`hoje`, `7d`, `30d`, `90d`, `mes atual`, `mes anterior`, `personalizado`)
- canal
- responsavel

## Quando o contato vira cliente
Durante a abertura de OS (rota `/os/nova`) iniciada pela Central:
- o contato pode ser vinculado ao cliente selecionado na OS
- a conversa e atualizada com `cliente_id`
- o contato passa para etapa `cliente_convertido`
- o selo **Cliente novo** deixa de aparecer quando houver vinculo

## Exclusao de contato
Um contato nao pode ser excluido quando:
- ja estiver vinculado a cliente
- estiver vinculado a conversa WhatsApp
