# Implementação da API Local de WhatsApp (Node.js Gateway)

Este documento detalha o funcionamento técnico da integração de WhatsApp sem custos de terceiros, utilizando um gateway local.

## 1. Arquitetura Geral
O sistema utiliza uma arquitetura híbrida:
- **ERP (Frontend/Backend)**: Desenvolvido em PHP (CodeIgniter 4).
- **Gateway (Bridge)**: Serviço em Node.js rodando `whatsapp-web.js`.

### 1.1 A Base: O Gateway em Node.js (`whatsapp-api/`)
O coração da integração não está no PHP, mas em um serviço separado que criamos em **Node.js**. 
*   **Tecnologia**: Usamos a biblioteca `whatsapp-web.js` que controla uma instância do Chromium via protocolo DevTools.
*   **Sessão**: Autenticação via `LocalAuth`. As chaves de criptografia são salvas na pasta `.wwebjs_auth`, permitindo que o sistema permaneça conectado após reinicializações.
*   **Eventos**: O Node.js escuta eventos como `qr` (gera novo código), `ready` (conexão pronta) e `disconnected` (perda de sinal).

### 1.2 A Ponte de Comunicação (API REST)
O script Node.js expõe um servidor HTTP interno (Express) para que o PHP possa enviar comandos:
- `GET /status`: Retorna se o WhatsApp está pronto e dados da conta vinculada.
- `GET /qr`: Retorna a imagem Base64 do QR Code atual.
- `POST /create-message`: Recebe o número do destinatário, o texto e, opcionalmente, um arquivo Base64 (PDF/Imagem).
- `POST /restart`: Comando para destruir a instância atual do navegador e criar uma nova.
- `POST /logout`: Encerra a sessão ativa no WhatsApp, remove os arquivos de autenticação locais e força a geração de um novo QR Code.
- `GET /health`: Healthcheck simples para monitoramento de latência e atividade do processo.

## 2. Implementação no PHP (ERP)

### 2.1 Provedor de Mensageria (`App\Services\MensageriaService.php`)
O ERP utiliza o padrão **Strategy**. Quando o provedor é `api_whats_local` ou `api_whats_linux`:
1. O PHP lê o `API_TOKEN` e a `URL` do gateway.
2. Formata o payload (converte anexos físicos em strings Base64).
3. Dispara uma requisição CURL interna para o Node.js.

### 2.2 Gerenciamento via Interface
O painel de configurações utiliza um fluxo de **Polling** e indicadores em tempo real:
1. O usuário abre o modal de gerenciamento ou visualiza o badge de status.
2. Um script JavaScript inicia requisições periódicas para o backend PHP.
3. O PHP atua como **Proxy**, repassando a pergunta para o Node.js.

### 2.3 Melhorias de UX/UI (Março 2026)
- **Status Realtime**: Badge clicável no topo da seção de WhatsApp indica o estado da conexão (`Conectado`, `Aguardando QR`, `Offline`).
- **Estados de Carregamento**: Botões mostram spinners e ficam desabilitados durante o processamento para evitar cliques duplicados.
- **Feedback de Sucesso**: Quando conectado, uma imagem 3D de confirmação substitui o placeholder do QR Code.
- **Auto-Boot**: Capacidade de iniciar o servidor Node.js ou PM2 diretamente pelo painel através do botão "Iniciar Servidor".

## 3. Segurança
- **Token Estático**: Header `X-Api-Token` obrigatório em todas as chamadas.
- **Validação de Origem**: Verificação do header `X-ERP-Origin` (CORS).
- **Rate Limit**: Proteção contra flood de requisições.

## 4. Fluxo de Envio de PDF (OS)
1. O técnico clica em "Enviar WhatsApp".
2. O sistema gera o PDF da OS em memória.
3. O PDF é convertido em Base64 e enviado para o gateway.
4. O Node.js dispara o arquivo para o destinatário usando `MessageMedia`.

## 5. Resumo da Pilha Tecnológica
- **Motor**: Node.js v18+.
- **Bibliotecas Node**: `express`, `whatsapp-web.js`, `qrcode`, `puppeteer`.
- **Bibliotecas PHP**: `CURL`, `CodeIgniter\HTTP\CURLRequest`.
- **Persistência**: Filesystem local (Pasta `.wwebjs_auth`).

## 6. Solução de Problemas (Troubleshooting)

### 6.1 Gateway preso em "Inicializando"
Se o gateway permanecer no estado "Inicializando" por mais de 2 minutos:
1.  **Causa Comum**: O navegador Chromium/Edge travou em background ou não respondeu ao comando de inicialização.
2.  **Solução Automática**: O sistema possui um timeout de 120 segundos. Após esse tempo, o estado será resetado para "Erro" permitindo uma tentativa de reinício manual.
3.  **Reinício Manual**: No modal de gerenciamento, utilize **"Reiniciar Inicialização"** -> **"Limpeza Profunda"**. Isso matará processos órfãos e limpará arquivos temporários da sessão.

### 6.2 Erro "Port already in use" (EADDRINUSE)
Se o servidor Node não iniciar por conflito de porta (3001):
1.  Verifique se já existe um processo `node.exe` rodando.
2.  No Windows, se o processo estiver travado e não puder ser encerrado via painel, abra o Gerenciador de Tarefas e encerre todos os processos `node.exe` e `chrome.exe` / `msedge.exe` vinculados à pasta do sistema.
3.  Caso receba "Acesso Negado" ao tentar matar o processo, certifique-se de fechar todos os terminais abertos ou reiniciar o serviço de hospedagem (XAMPP/Apache).

### 6.3 Conflito de Processos e Bloqueio de Porta (Março/2026)
Em situações onde o processo `php spark serve` ou `node.exe` fica travado ("zumbi") em segundo plano e não pode ser encerrado por falta de privilégios (Access Denied):
1.  **Mudança de Porta ERP**: Se a porta padrão (ex: 8081) estiver inacessível, altere o `app.baseURL` no arquivo `.env` para uma porta livre (ex: 8084) e reinicie o servidor Spark na nova porta (`php spark serve --port 8084`).
2.  **Spoofing de Origem**: Se o gateway Node.js estiver configurado para aceitar apenas a origem antiga e não puder ser reiniciado para ler a nova configuração:
    *   No banco de dados (tabela `configuracoes`), mantenha a chave `whatsapp_local_node_origin` apontando para a porta que o gateway *espera* (ex: `http://localhost:8081`), mesmo que o ERP esteja rodando em outra.
    *   Isso fará com que o ERP envie o header `X-ERP-Origin` correto para o bypass de segurança do gateway.
3.  **Logs de Auditoria**: Sempre verifique o arquivo `writable/logs/log-YYYY-MM-DD.log` para encontrar erros de "Unknown column" ou "Undefined variable" que podem indicar migrações pendentes ou erros de sintaxe após atualizações.

## 7. Manual do Modal "Gerenciar Gateway"

O modal de gerenciamento é a ferramenta central de manutenção. Abaixo, o detalhamento de cada funcionalidade:

### 7.1 Botão: Atualizar Status
*   **Função**: Força uma consulta imediata ao servidor Node.js.
*   **Funcionamento**: Dispara uma requisição para o endpoint `/status`. Embora o modal tenha um *polling* automático (atualiza a cada 2.5 segundos), este botão serve para confirmar o estado caso a conexão pareça lenta ou travada.
*   **Destino**: Endpoint `configuracoes/whatsapp/local-status`.

### 7.2 Botão: Desconectar / Trocar Número
*   **Função**: Encerra a sessão ativa e desvincula o aparelho do ERP.
*   **Funcionamento**: Envia um comando para o navegador automatizado fechar a sessão do WhatsApp Web. Ele **não** apaga os arquivos de cache, mas limpa os tokens de autenticação atuais.
*   **Uso**: Quando você deseja trocar o celular que dispara as mensagens ou quando o aparelho foi roubado/perdido.
*   **Destino**: Endpoint `configuracoes/whatsapp/local-logout`.

### 7.3 Botão: Iniciar Servidor
*   **Função**: Tenta dar um "boot" inicial no processo Node.js.
*   **Funcionamento**: 
    *   No **Windows**: Executa um comando `cmd /c start /B node server.js` em background.
    *   No **Linux**: Executa `pm2 restart whatsapp-gateway`.
*   **Uso**: Deve ser usado quando o status aparece como **"Offline / Inacessível"**. Se o servidor já estiver rodando, este botão fica oculto.
*   **Destino**: Endpoint `configuracoes/whatsapp/local-start`.

### 7.4 Botão: Reiniciar Inicialização
Este é o botão de manutenção mais importante. Ele possui dois modos:

#### Apenas Reiniciar (Reinício Simples)
*   **Função**: Mata o navegador atual e abre um novo.
*   **Funcionamento**: Envia o parâmetro `clean=false` para o gateway. O serviço Node encerra a instância do Chromium e inicia uma nova.
*   **Vantagem**: Tenta reaproveitar o login já feito (não pede novo QR Code).
*   **Uso**: Use quando o status está "Initializing" há muito tempo ou "Connected" mas as mensagens não chegam ao destino.

#### Limpeza Profunda (Zerar Sessão)
*   **Função**: Formata a integração.
*   **Funcionamento**: Envia o parâmetro `clean=true`. O gateway deleta a pasta `.wwebjs_auth` e mata o navegador.
*   **Consequência**: **Exige uma nova leitura de QR Code**.
*   **Uso**: Use apenas se o Reinício Simples falhar ou se houver erros de "Session Integrity".

---
*Documentação atualizada em 17/03/2026 para refletir as melhorias de estabilidade do gateway local.*
