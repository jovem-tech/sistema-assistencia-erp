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

## Reaprovacao no proprio orcamento

### Regra operacional atual

Quando um orcamento que ja foi aprovado precisa ser ajustado, o sistema nao cria mais um novo registro de revisao.

O comportamento oficial agora e:

- a edicao continua no mesmo `orcamentos.id`;
- o historico das mudancas fica no proprio orcamento em `orcamento_status_historico`;
- ao alterar um orcamento aprovado e preparar uma nova rodada de autorizacao, o status passa para `reenviar_orcamento`;
- quando o cliente aprova novamente, a label do status passa a refletir a rodada atual, como `2ª aprovacao`, `3ª aprovacao` e assim por diante;
- a versao interna (`orcamentos.versao`) sobe apenas quando o registro sai de um estado aprovado para uma nova rodada de aprovacao.

### Status envolvidos

- `reenviar_orcamento`: usado quando o orcamento aprovado foi alterado e precisa ser reenviado ao cliente;
- `aguardando_resposta`: representa o estado visual `Aguardando aprovacao` depois que o envio ao cliente foi efetivamente realizado;
- `aprovado` ou `pendente_abertura_os`: continuam sendo os estados finais de aprovacao, mas com label dinamica por versao quando `versao > 1`;
- `rejeitado`: continua registrando a negativa do cliente no mesmo orcamento.

### Edicao de orcamento convertido na release 2.16.27

O status `convertido` deixou de ser tratado como bloqueio de edicao direta.

Com isso:

- `OrcamentoModel::isLockedStatus()` nao trava mais o formulario quando o registro estiver `convertido`;
- a tela `app/Views/orcamentos/show.php` continua exibindo o botao `Editar` nesse status;
- o painel embutido de orcamento dentro da OS tambem herda essa liberacao, porque deixa de receber `is_locked = true` para o mesmo caso;
- a edicao preserva o mesmo registro convertido, sem criar revisao automatica e sem forcar mudanca de status apenas por abrir o formulario.

### Reaprovacao de orcamento convertido na release 2.16.28

Depois da liberacao do formulario, a release `2.16.28` complementou o fluxo para que um orcamento `convertido` volte a exigir aprovacao quando sofrer mudancas relevantes.

Com isso:

- `OrcamentoModel::requiresReapprovalAfterEdit()` passou a incluir `STATUS_CONVERTIDO`;
- ao editar conteudo de um orcamento convertido, o `update()` muda o mesmo registro para `reenviar_orcamento`, sobe a `versao` e reseta as colunas da rodada comercial;
- `OrcamentoService::canTransition()` passou a aceitar a transicao `convertido -> reenviar_orcamento`, evitando bloqueio no salvamento;
- o comportamento fica alinhado ao que ja acontecia com `aprovado`, `pendente_abertura_os` e `pacote_aprovado`.

### Avanco automatico para aguardando aprovacao na release 2.16.29

Depois do ajuste de reenvio, a release `2.16.29` refinou o pos-disparo para evitar que o registro permanecesse visualmente em `reenviar_orcamento` mesmo depois de o documento ja ter sido enviado.

Com isso:

- `reenviar_orcamento` fica restrito ao estado preparatorio anterior ao disparo;
- `Orcamentos::markAsDispatched()` passou a mover tambem esse status para `aguardando_resposta`;
- `OrcamentoService::canTransition()` passou a aceitar manualmente `reenviar_orcamento -> aguardando_resposta` para manter coerencia com a automacao de envio;
- a label exibida para `aguardando_resposta` foi atualizada para `Aguardando aprovacao`;
- o historico e a sincronizacao com OS passam a refletir que o orcamento revisado ja foi reenviado e agora aguarda retorno do cliente.

### Historico salvo no mesmo registro

O timeline do orcamento agora pode armazenar:

- mudanca de status;
- abertura de nova rodada de aprovacao;
- alteracoes de conteudo como itens, total, validade, prazo, observacoes, condicoes e vinculos.

Na pratica, a observacao do historico passou a registrar o que mudou sem depender de criar um segundo orcamento apenas para manter rastreabilidade.

### Fluxo publico do cliente

O link publico em `app/Controllers/Orcamento.php` passou a aceitar tambem o status `reenviar_orcamento`.

Com isso:

- o mesmo link pode ser reutilizado para a nova aprovacao;
- a aprovacao/rejeicao da rodada revisada continua sendo gravada em `orcamento_aprovacoes`;
- a equipe recebe o reflexo no mesmo orcamento, sem espalhar a trilha entre multiplos registros.

### Reabertura de OS cancelada em nova rodada comercial

Na release `2.16.25`, a sincronizacao com OS passou a cobrir explicitamente um caso pratico que ainda escapava do fluxo teorico.

Regra atual:

- se a OS vinculada estiver `cancelado` e o orcamento voltar para uma etapa comercial ativa de resposta/aprovacao do cliente, a OS deve sair de `cancelado` e retornar para `aguardando_autorizacao`;
- isso vale para estados como `pendente_envio`, `enviado`, `aguardando_resposta`, `reenviar_orcamento`, `aguardando_pacote` e `pendente`, desde que o alvo operacional continue sendo nova autorizacao;
- na release `2.16.26`, a mesma regra passou a cobrir tambem a aprovacao final da nova rodada: se a OS ainda estiver cancelada quando o orcamento virar `aprovado` ou `convertido`, ela deixa `cancelado` e segue para `aguardando_reparo`;
- na release `2.16.28`, a edicao de um orcamento ja `convertido` passou a usar a mesma volta para `reenviar_orcamento`, reativando o ciclo de autorizacao no proprio registro quando houver alteracoes;
- o ajuste foi aplicado tanto em `app/Controllers/Orcamentos.php` quanto em `app/Controllers/Orcamento.php`, cobrindo envio interno e resposta pelo link publico.

### Estabilizacao da release 2.16.19

Durante a consolidacao desse fluxo, o metodo `Orcamentos::update()` recebeu uma correcao operacional para carregar o snapshot dos itens atuais no ponto certo da edicao.

Com isso:

- a comparacao entre itens antigos e novos volta a acontecer sem excecao de variavel indefinida;
- o fluxo de reaprovacao pode salvar alteracoes em orcamentos aprovados sem quebrar a tela;
- a abertura de nova rodada com `reenviar_orcamento` continua preservando o historico no mesmo registro.

### Atualizacao automatica do PDF na release 2.16.20

O fluxo de PDF do orcamento passou a validar se o arquivo mais recente ainda representa o estado atual do registro antes de reutiliza-lo.

Com isso:

- envio por WhatsApp e por e-mail deixam de anexar um PDF antigo quando o orcamento mudou de status, itens, total, validade, observacoes ou versao;
- a rota `GET /orcamentos/pdf/{id}` passa a regenerar o documento quando o orcamento foi atualizado depois da ultima geracao registrada;
- o atalho `Baixar ultimo arquivo` da tela de visualizacao so permanece disponivel quando aquele arquivo ainda corresponde ao estado atual do orcamento.

### Ajuste do painel de historico na release 2.16.21

A area `Historico de status` da tela `Visualizar Orcamento` recebeu uma correcao de mapeamento de variavel na view.

Com isso:

- o timeline salvo em `orcamento_status_historico` volta a aparecer corretamente no card lateral;
- a tela deixa de cair no estado `Sem historico de status` quando os registros existem no backend;
- a rastreabilidade das rodadas de reaprovacao permanece visivel no proprio orcamento, sem depender de consulta manual ao banco.

### Organizacao da visualizacao em abas na release 2.16.30

A view `app/Views/orcamentos/show.php` passou por reorganizacao estrutural para separar a leitura comercial em abas.

Abas implementadas:

- `Dados do cliente`
- `Dados do equipamento`
- `Dados operacionais`
- `Pacotes de servico`
- `Envio do orcamento`
- `Orcamento`
- `Financeiro do orcamento`

Impacto tecnico:

- o topo da tela ganhou um card de visao geral com status, total e metricas rapidas;
- o bloco financeiro deixou de disputar espaco com os dados operacionais e passou para aba propria;
- os fluxos de PDF, WhatsApp e e-mail passaram a ter aba propria em `Envio do orcamento`, enquanto `Orcamento` preserva itens, historico de status e rastreabilidade;
- `App\Controllers\Orcamentos::show()` passou a enviar `equipamentoView` consolidado para a view, reunindo tipo, marca, modelo, cor e foto principal do equipamento;
- a navegacao entre abas voltou a seguir o padrao `nav-tabs ds-tabs-scroll` do design system, mantendo linha unica e compatibilidade com `<= 430px`, `<= 390px`, `<= 360px` e `<= 320px`;
- na view `Visualizar Orcamento`, a barra de rolagem horizontal do componente foi ocultada localmente, preservando a interacao sem expor trilho visual fora do padrao da tela;
- os rotulos das abas passaram a usar variantes reduzidas por breakpoint, permitindo que todas permaneçam visiveis dentro da largura disponivel sem quebrar linha.

### Organizacao do formulario em abas nas releases 2.16.31 e 2.16.32

O formulario `app/Views/orcamentos/form.php` foi reorganizado para seguir a mesma logica de separacao por contexto.

Abas implementadas:

- `Dados do cliente`
- `Dados do equipamento`
- `Dados operacionais`
- `Pacotes de servico`
- `Orcamento e financeiro`

Impacto tecnico:

- os cards originais foram preservados e apenas redistribuidos em `tab-pane`, reduzindo risco de regressao no JavaScript existente;
- IDs como `orcamentoClienteLookup`, `orcamentoEquipamentoLookup`, `orcPacotebfertaCard`, `orcSecaoItens` e `orcSecaoFinanceiro` permanecem os mesmos;
- o fluxo de rascunho automatico, selecao de cliente, vinculo com OS, deteccao de pacote e recalculo de itens segue intacto;
- o card `orcSecaoFinanceiro` passou a compartilhar o mesmo `tab-pane` de `orcSecaoItens`, reduzindo troca de contexto durante a montagem do orcamento;
- a navegacao entre abas tambem usa `nav-pills` com scroll horizontal responsivo para telas compactas.

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
