# Modulo: Orcamentos

## Objetivo

O modulo de `Orcamentos` centraliza o ciclo comercial da assistencia tecnica:

- criacao de proposta;
- vinculacao com cliente, contato, OS e equipamento;
- envio multicanal;
- acompanhamento de status;
- aprovacao, rejeicao ou conversao.

## Nucleo tecnico

### Camada principal

- controller web principal: `app/Controllers/Orcamentos.php`
- controller legado/complementar: `app/Controllers/Orcamento.php`
- model de cabecalho: `app/Models/OrcamentoModel.php`
- model de itens: `app/Models/OrcamentoItemModel.php`
- models auxiliares: `OrcamentoEnvioModel`, `OrcamentoAprovacaoModel`, `OrcamentoStatusHistoricoModel`
- services: `OrcamentoService`, `OrcamentoPdfService`, `OrcamentoMailService`, `OrcamentoLifecycleService`, `OrcamentoConversaoService`
- views principais: `app/Views/orcamentos/index.php`, `form.php`, `show.php`, `publico.php` e `oferta_publica.php`

## Dados do Cliente no formulario

### Busca inteligente

O endpoint AJAX de selecao do cliente e contato fica em `GET /orcamentos/clientes/lookup`, atendido por `Orcamentos::lookupClienteContato()`.

Na release `2.15.1`, a busca passou a considerar tambem:

- `clientes.nome_contato`
- `clientes.telefone_contato`

Com isso, o Select2 consegue localizar melhor cadastros que dependem do contato adicional do cliente.

### Payload retornado ao frontend

Os resultados de cliente e contato agora podem carregar, alem dos campos principais:

- `contato_adicional_nome`
- `contato_adicional_telefone`

Esses dados sao usados na propria tela para renderizar o card informativo `Contato adicional do cliente` sem round-trip extra.

### Validacao do telefone

O campo `telefone_contato` deixou de ser obrigatorio no formulario de Orcamentos.

Regra tecnica atual:

- valor vazio e aceito;
- valor preenchido continua exigindo celular WhatsApp com DDD;
- o backend valida somente quando houver numero informado;
- o frontend mantem mascara e mensagem de erro apenas para numero parcial ou invalido.

## Estado inicial de edicao

O metodo `buildClienteLookupInitial()` passou a incluir o resumo do contato adicional no carregamento inicial da view.

Na pratica, isso evita divergencia entre:

- o primeiro render da pagina;
- a selecao posterior no Select2;
- o estado salvo de um orcamento ja existente.

## Impacto funcional da release 2.15.1

- o usuario nao precisa mais preencher telefone apenas para salvar a proposta;
- a equipe visualiza o contato adicional logo no bloco `Dados do Cliente`;
- o autocomplete de cliente e contato fica mais completo para operacao comercial.

## Resposta publica e notificacao interna

Na release `2.15.17`, o controller complementar `app/Controllers/Orcamento.php` passou a transformar a resposta publica do cliente em notificacao interna do ERP.

### Eventos cobertos

- `Orcamento::aprovar($token)`
- `Orcamento::recusar($token)`

### Efeito operacional

- o status comercial do orcamento e atualizado normalmente;
- a resposta do cliente e registrada em historico e em `orcamento_aprovacoes`;
- quando existir `os_id` vinculado, a OS continua passando pela sincronizacao comercial ja prevista;
- a equipe recebe notificacao interna no sino da navbar, ao lado da foto do perfil;
- a listagem `/os` recarrega automaticamente para refletir o badge comercial atualizado na coluna `Status`;
- o clique na notificacao passou a abrir a rota correta do ERP, mesmo quando o ambiente usa `index.php` ou publica a aplicacao abaixo da raiz do host.

## Normalizacao textual recente

As views `app/Views/orcamentos/form.php` e `app/Views/orcamentos/show.php` receberam uma revisao complementar de pt-BR/UTF-8 para reduzir labels legados sem acentuacao nos blocos:

- aviso de abertura embutida a partir da OS;
- resumo comercial e contextual do orcamento;
- mensagens de vinculacao com OS/equipamento;
- instrucoes de validacao e leitura do operador.

### Servicos e inbox reutilizados

O fluxo reutiliza:

- `App\Services\Mobile\MobileNotificationService`
- `mobile_notifications`
- `mobile_notification_targets`

Mesmo sendo um recurso visivel na web, a inbox continua unificada com a infraestrutura de notificacoes do app mobile/PWA.
