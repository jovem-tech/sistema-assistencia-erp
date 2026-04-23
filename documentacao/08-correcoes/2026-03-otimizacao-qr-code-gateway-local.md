# Registro de Correcao/Melhoria: Otimizacao de Performance QR Code WhatsApp

**Data:** 16 de Marco de 2026
**Modulo:** Integracoes (WhatsApp Gateway Local)
**Objetivo:** Reduzir o tempo de espera para exibicao do QR Code no modal de gerenciamento.

## Problema Relatado
O usuário informou que o QR Code estava demorando para aparecer no modal "Gerenciar Gateway Local".

## Analise Tecnica
Identificamos dois pontos de latência na implementação anterior:
1. **Frequência de Polling**: O frontend verificava o status a cada 5 segundos, o que gerava um atraso perceptível.
2. **Requisições Sequenciais**: O frontend primeiro chamava `/status`, e somente se o status indicasse a disponibilidade de um QR, ele disparava uma segunda requisição para `/qr` para obter a imagem.

## Alterações Realizadas

### 1. Backend Node.js (whatsapp-api/server.js)
- O endpoint `/status` foi modificado para incluir o campo `qr` (DataURL da imagem) diretamente no payload de resposta sempre que um QR estiver disponível. Isso elimina a necessidade de uma segunda requisição HTTP.

### 2. Frontend PHP (app/Views/configuracoes/index.php)
- O intervalo de polling (`setInterval`) foi reduzido de **5.000ms** para **2.000ms**, tornando a interface mais reativa.
- A função `fetchStatus` agora processa o campo `qr` retornado pelo status, atualizando a imagem no modal imediatamente, sem esperar pela chamada de fallback `fetchQR`.

### 3. Documentação (documentacao/05-api/rotas.md)
- Atualizada a descrição do endpoint `/status` para informar a inclusão do QR no payload.

## Arquivos Afetados
- `whatsapp-api/server.js`
- `app/Views/configuracoes/index.php`
- `documentacao/05-api/rotas.md`

## Notas de Implementacao
As alterações visam melhorar a percepção de velocidade (UX). O tempo de inicialização do Puppeteer/WhatsApp Web permanece dependente dos recursos do servidor, mas a exibição assim que disponível agora é quase instantânea após a geração.

> **Importante:** Para que as mudanças no backend tenham efeito, o serviço `node server.js` deve ser reiniciado no servidor.
