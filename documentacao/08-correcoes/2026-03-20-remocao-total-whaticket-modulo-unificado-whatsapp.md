# Registro de Correcao: Remocao Total do Whaticket e Modulo Unificado

**Data:** 20/03/2026  
**Escopo:** Frontend + Backend + Banco + Documentacao

## Objetivo
Eliminar completamente o legado Whaticket do ERP e consolidar o atendimento WhatsApp em modulo unico interno (`/atendimento-whatsapp`), com reaproveitamento das APIs gateway (`api_whats_local` e `api_whats_linux`).

## Alteracoes aplicadas

### Backend
- Removido controller legado:
  - `app/Controllers/Whaticket.php`
- Removida logica de start local e configuracao dedicada Whaticket em:
  - `app/Controllers/Configuracoes.php`
- Removidas referencias legadas de provider em:
  - `app/Services/MensageriaService.php`
  - `app/Services/CentralMensagensService.php`

### Frontend
- Removida view legada:
  - `app/Views/whaticket/index.php`
- Removido script legado:
  - `public/assets/js/whaticket.js`
- Atualizada tela de configuracoes:
  - removida aba `WhaTicket`
  - removidos campos `whatsapp_whaticket_*`
  - removido provider `whaticket`
  - mantido gerenciamento de gateway apenas para `api_whats_local` e `api_whats_linux`

### Rotas e navegacao
- Removidas rotas:
  - `/whaticket`
  - `/whaticket/status`
  - `/configuracoes/whatsapp/whaticket-local-start`
- Mantido e formalizado acesso principal:
  - `/atendimento-whatsapp` (alias de `/central-mensagens`)
- Consolidado prefixo canonico para navegacao:
  - `/atendimento-whatsapp/*` (chatbot, metricas, FAQ, fluxos, filas e configuracoes)
  - `/central-mensagens/*` permanece como alias de compatibilidade

### Banco de dados
- Nova migration:
  - `2026-03-20-060000_RemoveWhaticketLegacyModule.php`
- Efeitos:
  - remove chaves legadas `whatsapp_whaticket_*` e afins na tabela `configuracoes`
  - normaliza providers legados `whaticket` para `api_whats_local`
  - aplica normalizacao em tabelas de mensageria/CRM quando existirem

## Impacto funcional
- ERP passa a operar sem iframe externo.
- Atendimento WhatsApp fica centralizado em um unico modulo interno.
- Configuracao de WhatsApp fica mais simples e sem bifurcacao de arquitetura.
- Navegacao principal do modulo passa a usar somente `/atendimento-whatsapp` no menu.
- Links antigos continuam respondendo para evitar quebra de bookmark/integracao interna.

## Limpeza fisica de legado
- Removidos artefatos locais do projeto:
  - `migrate_whaticket.php`
  - `whaticket.manual.err.log`
  - `whaticket.manual.out.log`
  - logs de boot em `whaticket/`

## Checklist de pos-migracao
1. Executar `php spark migrate`.
2. Validar `whatsapp_direct_provider` em `Configuracoes -> Integracoes`.
3. Abrir `/atendimento-whatsapp` e validar carga de conversas.
4. Executar `Self-check inbound`.
5. Validar envio de mensagem de teste.
