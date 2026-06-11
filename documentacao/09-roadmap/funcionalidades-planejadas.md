# Roadmap - Funcionalidades Planejadas

Atualizado em 06/06/2026.

## Concluido (fundacao pre-CRM + CRM operacional inicial)
- fluxo de OS por macrofases com transicoes validas
- historico de status (`os_status_historico`)
- templates WhatsApp por evento
- envio WhatsApp com texto e PDF
- geracao/versionamento de PDFs da OS
- log operacional de mensageria
- gateway local WhatsApp hardenizado para producao
- financeiro gerencial baseline:
  - `DRE` por competencia
  - `fluxo de caixa realizado`
  - `fluxo de caixa projetado`
  - classificacao automatica no `financeiro`
  - `despesa fixa mensal na DRE`
  - catalogos configuraveis de `categorias financeiras`, `grupos DRE` e `subgrupos DRE`
  - leitura consistente por categoria no `fluxo de caixa` e nos `relatorios operacionais`
  - `baixa parcial`
  - `multiplos movimentos por titulo`
  - vinculo de `fornecedor` nas contas `A pagar`
- baixa tecnica profissional da OS:
  - modal `Baixa da OS` na listagem
  - conclusao com `pagamento pendente`
  - encerramento automatico apos quitacao total
  - follow-up opcional de retorno pos-servico
  - regua automatica de cobranca em `1`, `3` e `5` dias
- recebimento em cartao com leitura liquida:
  - submenu `Cartoes e taxas`
  - cadastro de `operadoras`, `bandeiras`, `taxas` e `parcelas`
  - simulador de venda liquida por maquininha
  - despesa automatica da `taxa de cartao`
- camada desacoplada de mensageria (`MensageriaService` + providers)
- base CRM integrada ao ERP:
  - timeline
  - interacoes
  - follow-ups
  - pipeline operacional
- central de mensagens integrada:
  - lista de conversas
  - thread
  - vinculo com cliente/OS
  - envio de PDF pela conversa
  - chatbot com intencoes e FAQ configuraveis
  - filas e responsaveis
  - metricas operacionais de atendimento
  - fluxos de atendimento e respostas rapidas
  - inbound com midia (imagem/PDF) persistido na thread
  - badges de operacao (SLA, prioridade, bot, aguardando humano)
  - motor de regras ERP dinamico para automacoes de mensagem/follow-up/evento
  - busca global inteligente (navbar): OS, Clientes, Equipamentos, WhatsApp, MÃ³dulos, ServiÃ§os e PeÃ§as

## Em andamento
- unificacao de consulta entre `mensagens_whatsapp` e analiticos de atendimento
- refinamento de templates por tipo de cliente/etapa
- evolucao do painel de contexto da conversa para multi-OS
- automacao por horario/SLA em camadas (regra + fila)
- extensao mobile/PWA paralela do ERP:
  - API interna v1 em CodeIgniter 4
  - auth mobile por token
  - conversa/OS/notificacoes no app mobile
  - base de push notifications com Service Worker

## Proxima etapa estrategica (CRM avancado)
- automacoes por SLA com fila de jobs
- roteamento de conversa por atendente/equipe
- tags e segmentacao comercial completa
- campanhas e reativacao com provider de massa oficial
- notificacoes em tempo real (websocket/push interno)
- dashboard mobile para tecnicos e atendentes
- modulo mobile de CRM (clientes e follow-ups)
- modulo mobile de financeiro e agenda
- evolucao do financeiro gerencial:
  - contas financeiras/bancos
  - conciliacao bancaria

## Backlog tecnico
- provider oficial Meta para massa/campanhas
- webhooks inbound completos (entrega/leitura/resposta)
- retries com politicas configuraveis
- observabilidade do gateway (metricas e alertas)
- worker de despacho push (Web Push/FCM) consumindo `mobile_event_outbox`
- politicas de retencao/limpeza para `mobile_notifications` e `mobile_api_tokens`
