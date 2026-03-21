# Correção de Travamento: Gateway WhatsApp (Março 2026)

## Problema Relatado
O gateway de WhatsApp ficava preso no estado "Inicializando" indefinidamente, impossibilitando a conexão ou a exibição do QR Code.

## Causa Raiz
1.  **Zombie Processes**: Instâncias do Chromium (Puppeteer) ou Node.js ficavam órfãs em background após falhas de rede ou crashes.
2.  **Port Conflict**: O processo órfão mantinha a porta 3001 ocupada, impedindo que a nova instância do servidor Node iniciasse.
3.  **Deadlock de Estado**: A flag `isInitializing` no servidor não possuía um timeout de segurança, fazendo com que requisições de reinício fossem ignoradas se a falha ocorresse no meio do processo de boot da biblioteca `whatsapp-web.js`.
4.  **Permissões**: Processos iniciados com privilégios de Administrador não podiam ser encerrados pelo ERP ou pelo agente sem a mesma elevação.

## Mudanças Implementadas

### 1. Servidor Node (`whatsapp-api/server.js`)
-   **Timeout de Inicialização**: Adicionado um `Promise.race` com timeout de 120 segundos para a chamada `client.initialize()`. Se o navegador não responder, o estado é resetado para `error` e a trava de inicialização é liberada.
-   **Override de Reinício**: O comando de restart manual agora ignora a flag `isInitializing`, permitindo forçar um novo boot mesmo se o sistema achar que já está tentando iniciar.
-   **Limpeza Agressiva**: A função `forceKillChromium` agora tenta matar processos `msedge.exe` e `chrome.exe` por nome e uso de memória no Windows, além da busca por Command Line.
-   **Contexto de Log**: Melhorada a visibilidade dos logs de erro durante o boot, capturando a causa exata do timeout.

### 2. Documentação
-   Atualizado o manual técnico em `documentacao/07-novas-implementacoes/` com uma seção de **Troubleshooting**.

## Ações Necessárias (Manual)
Caso o sistema ainda apresente porta ocupada e o ERP não consiga limpar, execute em um terminal **como Administrador**:

```powershell
# Encontrar o processo na porta 3001
netstat -ano | findstr :3001

# Matar o processo (substitua <PID> pelo número retornado no comando acima)
taskkill /F /PID <PID> /T
```

## Arquivos Atualizados
- `whatsapp-api/server.js`
- `documentacao/07-novas-implementacoes/2026-03-como-foi-implementado-a-api-local-do-whatsapp.md`
- `documentacao/08-correcoes/2026-03-correcao-travamento-inicializacao-whatsapp.md` (Este arquivo)
