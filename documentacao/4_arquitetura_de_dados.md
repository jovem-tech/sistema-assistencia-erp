# Arquitetura e Engenharia de Dados

A fim de garantir total estabilidade e prevenir a lentidão no faturamento ou consultas que o **AssistTech** possa sofrer em um cenário real escalável (acima de milhares de Ordens de Serviço abertas ou listagens pesadas no banco), melhorias vitais de arquitetura foram impostas.

## 1. Processamento DataTables (Client-Side para Server-Side)
Na sua concepção inicial, o sistema se apoiava primariamente no design de manipulação local (*Client-Side Processing*) do HTML pelas tabelas em Javascript. Isso limitava o poder de navegação, pois sobrecarregava a memória do navegador enviando o banco de dados inteiro (limitado a `findAll`) por baixo dos panos na View.

Uma **Arquitetura Service Processing (Server-Side)** foi implementada aos moldes do Backend:
- **`BaseController::respondDatatable()`**: Uma camada injetada globalmente em todo o sistema. Ele padroniza, intercepta e processa pesquisas avançadas com clareza matemática usando os modais *OrLike*, com limite de paginação rigorosa (LIMIT/OFFSET), garantindo frações do que o usuário realmente vê no frontend.
- **Vantagem Adquirida**: Escalabilidade massiva. Isso significa que o volume do banco de dados (seja buscando usuários ou faturas com milhões de registros) jamais travará os cliques de pesquisa ou carregamento visual das views, porque apenas as 10 linhas vigentes vistas na tela viajam na rede encapsuladas num payload JSON.

## 2. Abstração e Armazenamento Inteligente (CORS e Offline First)
Havia bloqueios de política de rede por bibliotecas solicitarem arquivos remotamente sem as credenciais HTTPS corretas da aplicação inicial (`[data-theme]` CDNs de Idioma pt-br do DataTables). 

A abordagem de infraestrutura foi convertida com o formato **Offline First**:
- Criação de pastas autônomas no CodeIgniter para manter um cache vitalício local dos arquivos de configuração e tradução (exemplo `public/assets/json/pt-BR.json`). 
- **Compatibilidade Plena**: O sistema pode perfeitamente rodar em *Intranets Corporativas* severamente regradas por Proxy, sem depender da Internet Global, sem apresentar telas "404" ou poluir o console de aviso.

## 3. Gestão Centralizada de Variáveis de Roteamento Base
Foi acoplada, aos cabeçalhos dinâmicos do Bootstrap, a declaração explícita de `meta name="base-url"` utilizando o auxiliar do CodeIgniter (`<?= base_url() ?>`).

Anteriormente o escopo corria um engessamento (`sistema-assistencia/public`). Essa evolução de roteamento permite espelhar o aplicativo instantaneamente tanto numa pasta remota rodando NGINX ou Apache (XAMPP), quanto usando o CLI ágil embutido (`php spark serve`) em subdomínios, mantendo os caminhos lógicos da raiz incólumes para requisições AJAX.
