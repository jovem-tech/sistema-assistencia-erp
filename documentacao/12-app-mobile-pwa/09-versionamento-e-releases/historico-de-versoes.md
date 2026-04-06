# Historico de Versoes do App

Atualizado em 06/04/2026.

## Linha oficial

### `0.4.8`
- hotfix no fluxo de fotos de entrada em `/os/nova`: o modal de corte voltou a abrir de forma consistente apos selecionar imagem por camera ou galeria
- hardening do crop: limpeza completa de estado em falhas de inicializacao para evitar fila travada
- validacao de imagem reforcada com fallback por extensao quando o browser/dispositivo nao informar MIME corretamente
- fluxo de `Criar OS` passou a abrir um modal de revisao completa antes de salvar
- o modal mostra todos os campos da abertura (obrigatorios e opcionais), com estado `Preenchido/Pendente`
- pendencias obrigatorias agora bloqueiam a continuidade e redirecionam para o campo que precisa ser ajustado
- pendencias opcionais permitem escolha entre preencher ou prosseguir sem completar
- nova etapa final de confirmacao pergunta sobre notificacao ao cliente:
  - sem notificacao
  - mensagem de abertura
  - mensagem + PDF
- API de criacao de OS passou a aceitar preferencia de notificacao e tentar despacho imediato via WhatsApp na abertura
- modo `mensagem + PDF` agora faz fallback para mensagem sem PDF quando a geracao do documento falha, sem bloquear a OS
- tela `Avisos` passou a exibir o estado da conexao WhatsApp ativa no ERP (provedor, saude e mensagem tecnica)
- ERP minimo compativel: `2.11.5`

### `0.4.7`
- modal de cadastro/edicao de cliente na abertura de OS ganhou campos de contato adicional:
  - `nome_contato`
  - `telefone_contato`
- modal de cliente passou a incluir `CEP` com consulta automatica para preencher endereco, bairro, cidade e UF
- payload mobile de cliente foi alinhado com os campos ja suportados pela API do ERP, mantendo edicao manual apos autopreenchimento
- hardening operacional local: `dev:recover` passou a encerrar node preso na porta `3000`, limpar `.next` com retry e validar readiness via `http://localhost:3000/login`
- ERP minimo compativel: `2.11.5`

### `0.4.6`
- abertura de OS (`/os/nova`) ganhou card selecionado de cliente com nome + telefone no mesmo padrao visual do seletor de equipamento
- ao tocar no card do cliente, o seletor inteligente reabre para permitir troca rapida por nome ou telefone
- bloco `Dados operacionais` foi migrado para `collection-block`, alinhando hierarquia visual com `Acessorios`, `Checklist`, `Fotos de entrada` e `Observacoes`
- novo script de recuperacao local `npm run dev:recover` para tratar ambiente com `/_next/static/* 404` e chunks quebrados
- nova skill operacional `.agents/skills/mobile-pwa-next-chunk-guard/` para prevencao e diagnostico rapido desse erro
- ERP minimo compativel: `2.11.5`

### `0.4.5`
- abertura de OS (`/os/nova`) ganhou atalho `+` ao lado do campo `Relato do cliente`
- o novo modal de selecao carrega os itens ativos de `Defeitos Relatados` do ERP, agrupados por categoria
- ao selecionar um item, o texto e inserido no relato mantendo o conteudo ja digitado, no mesmo padrao de normalizacao do ERP web
- `GET /api/v1/orders/meta` passou a expor `reported_defects` para abastecer o fluxo mobile
- ERP minimo compativel: `2.11.5`

### `0.4.4`
- hotfix de ambiente local para eliminar erro `Cannot find module './161.js'` e 500 no redirecionamento apos salvar OS
- script `npm run dev` agora limpa `.next` automaticamente no inicio (pode ser desativado com `KEEP_NEXT_CACHE=1`)
- `favicon.ico` adicionado no app para remover 404 local recorrente
- ERP minimo compativel: `2.11.5`

### `0.4.3`
- ajuste no fluxo da tela `/os/nova` para o botao de checklist de entrada responder ao toque/clique de forma consistente
- o modal de checklist passa a abrir mesmo antes da selecao do equipamento, exibindo orientacao operacional na propria interface
- refinamento visual da abertura de OS: `Fotos de entrada` e `Observacoes` em cards padronizados e botoes operacionais com tamanho/hierarquia unificados
- hardening de ambiente local: desregistro de Service Worker fora de producao para evitar erro de chunks `/_next/static/*` apos salvar e redirecionar
- ERP minimo compativel: `2.11.5`

### `0.4.2`
- seletor rico de equipamento na abertura de OS do app mobile/PWA
- cada equipamento do cliente passa a exibir foto de perfil, `tipo - marca`, `modelo - cor` e `numero de serie/IMEI`
- busca do equipamento passou a considerar metadados tecnicos para diferenciar aparelhos iguais do mesmo cliente
- ERP minimo compativel: `2.11.5`

### `0.4.1`
- hotfix de producao no service worker do app mobile/PWA
- remocao do pre-cache da raiz do app em subdominio dedicado, evitando falha em `cache.add` quando a raiz responde com redirect
- cache restrito a assets estaveis e respostas `200 OK`, sem persistir respostas `503` ou chamadas `/api/`
- fallback offline ajustado para usar rota segura em cache, sem devolver `503` artificial para navegacoes quando existir fallback local
- ERP minimo compativel: `2.11.4`

### `0.1.0`
- fundacao do app mobile/PWA
- base de login, conversas, OS, notificacoes e manifest
- marco equivalente no ERP: `2.11.0`

### `0.2.0`
- push real em producao
- marco equivalente no ERP: `2.11.1`

### `0.3.0`
- abertura completa de OS no app
- marco equivalente no ERP: `2.11.2`

### `0.3.1`
- hardening do service worker
- marco equivalente no ERP: `2.11.3`

### `0.4.0`
- primeira consolidacao oficial publicada do app mobile/PWA
- refinamentos de UX, fotos, crop, acessorios, busca e listagem de OS
- documentacao exclusiva do app aprofundada com API detalhada, design system expandido e skills reais no repositorio
- versao do app explicitada no login e na navegacao autenticada
- marco equivalente no ERP: `2.11.4`
