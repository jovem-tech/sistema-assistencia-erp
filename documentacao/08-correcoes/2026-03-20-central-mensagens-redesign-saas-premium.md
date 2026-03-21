# Registro de Correcao: Central de Mensagens - Redesign SaaS Premium

**Data:** 20/03/2026  
**Modulo:** Central de Mensagens (`/atendimento-whatsapp`)

## Objetivo
Elevar a UX operacional da Central para padrao SaaS premium, com foco em produtividade do operador, leitura rapida e comportamento de tempo real.

## Escopo implementado

### 1) Layout e hierarquia visual
- refatoracao da view principal para reforcar a estrutura em 3 colunas (inbox, chat, contexto)
- novo topo com indicador de estado de atualizacao em tempo real
- cards da inbox com destaque de:
  - nome
  - preview
  - horario
  - badges de status/bot/prioridade
  - nao lidas
  - responsavel

### 2) Filtros operacionais
- botao `Limpar` na area de filtros
- feedback visual de filtros ativos
- resumo da fila com contagem total e total de nao lidas

### 3) Header do chat
- novas acoes de operador:
  - atualizar conversa
  - assumir conversa (usuario logado)
  - atribuir (abre/foca contexto)
  - encerrar conversa (status resolvida)
- status da conversa exibido em badge contextual

### 4) Thread de mensagens
- separadores por dia (`Hoje`, `Ontem`, data)
- status outbound em cada mensagem (`Enviada`, `Entregue`, `Lida`, `Falha`)
- animacao leve para mensagens novas recebidas
- estados vazios/erro/carregamento com padrao visual unificado

### 5) Composer
- menu de anexos mantido e refinado
- novo menu de emojis rapido
- area de digitacao com visual mais ergonomico

### 6) Painel de contexto
- reorganizado por secoes:
  - contato (agenda)
  - cliente ERP
  - gestao da conversa
  - vinculo de OS
  - follow-ups
- padrao visual de cards leves e leitura executiva

## Ajustes tecnicos
- `CentralMensagens::index()` agora envia `currentUserId` e `currentUserName` para suportar acao `Assumir conversa`.
- frontend passou a usar badge de estado de runtime:
  - `Tempo real` quando SSE ativo
  - `Polling` em fallback normal
  - `Instavel` em falha operacional

## Arquivos alterados
- `app/Controllers/CentralMensagens.php`
- `app/Views/central_mensagens/index.php`
- `public/assets/js/central-mensagens.js`
- `documentacao/06-modulos-do-sistema/central-de-mensagens.md`
- `documentacao/08-correcoes/2026-03-20-central-mensagens-redesign-saas-premium.md`

## Validacao recomendada
1. Abrir `/atendimento-whatsapp` em desktop, tablet e mobile.
2. Validar filtros com `Aplicar` e `Limpar`.
3. Abrir conversa com mensagens antigas e verificar separadores por data.
4. Enviar mensagem outbound e validar status visual na bolha.
5. Testar acoes do header (`Assumir`, `Atribuir`, `Encerrar`, `Atualizar`).
6. Simular indisponibilidade temporaria para validar badge `Instavel`.
