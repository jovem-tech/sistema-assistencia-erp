# Manual do Usuario - Orcamentos

## Visao geral

O modulo de `Orcamentos` organiza a proposta comercial do atendimento tecnico, seja em fluxo avulso, seja vinculado a uma OS em andamento.

O formulario atual cobre:

- identificacao do cliente;
- contexto do equipamento;
- itens e valores;
- envio por WhatsApp, email e PDF;
- conversao posterior para OS ou venda, quando aplicavel.

## Onde acessar

- listagem principal: menu `Orcamentos`
- novo cadastro: `/orcamentos/nova`
- edicao: `/orcamentos/editar/{id}`
- visualizacao: `/orcamentos/visualizar/{id}`

## Dados do Cliente

### Cliente cadastrado

O campo `Cliente cadastrado` usa busca inteligente por:

- nome do cliente;
- CPF/CNPJ;
- email;
- telefone principal;
- telefone do contato adicional;
- nome do contato adicional, quando existir no cadastro.

Ao selecionar um cliente ou contato, o formulario preenche o contexto automaticamente sem exigir recarga manual da pagina.

### Nome do cliente eventual

Use `Nome do cliente eventual` apenas quando ainda nao houver cadastro selecionado.

Se um cliente existente for escolhido, esse campo fica bloqueado para evitar mistura de identidades no mesmo orcamento.

### Telefone de contato

O campo `Telefone de contato` agora e opcional.

Regras praticas:

- pode ficar vazio sem bloquear o salvamento;
- quando informado, deve ser um celular WhatsApp com DDD;
- o numero preenchido pode ser reutilizado nos fluxos de envio do orcamento e de oferta baseada em pacote;
- se o cliente ja tiver telefone principal ou telefone de contato no cadastro, o sistema pode usar esses dados como fallback operacional em outras etapas.

### Contato adicional do cliente

Quando o cadastro do cliente possuir `nome_contato` e/ou `telefone_contato`, o card `Contato adicional do cliente` aparece logo abaixo da selecao principal.

Esse resumo ajuda a equipe a visualizar rapidamente:

- o nome do contato adicional;
- o telefone cadastrado para esse contato.

Importante:

- esse bloco e apenas informativo;
- ele nao substitui o `Telefone de contato` do orcamento;
- o orcamento pode ser salvo mesmo sem telefone principal no bloco de contato.

### Email de contato

O email continua opcional.

Quando informado, ele pode ser usado no envio do orcamento por email diretamente na tela de visualizacao.

## Dados do Equipamento

Depois de preencher o cliente, o fluxo segue para:

- vinculo com OS aberta, quando existir;
- vinculo com equipamento ja cadastrado;
- cadastro manual do equipamento para o orcamento atual, quando necessario.

## Itens e valores

O orcamento permite adicionar:

- servicos;
- pecas;
- observacoes por item;
- desconto e acrescimo no fechamento.

Os totais sao recalculados na propria tela conforme o usuario preenche a proposta.

## Resposta do cliente pelo link publico

Quando o orcamento e enviado ao cliente com link de aprovacao:

- a aprovacao publica muda o status comercial para `Aprovado` ou `Pendente de abertura de OS`, conforme o tipo do orcamento;
- a rejeicao publica muda o status para `Rejeitado`;
- a equipe do ERP recebe notificacao interna no sino da navbar, ao lado da foto do perfil;
- ao clicar nessa notificacao, o ERP abre a rota correta da listagem de OS ou da visualizacao do orcamento, conforme o contexto da proposta;
- se houver uma OS vinculada, a listagem `/os` atualiza automaticamente o badge comercial do orcamento, sem exigir recarga manual da pagina;

## Reuso do PDF oficial dentro da OS

Quando uma ordem de servico vinculada usa a aba `Documentos` para gerar ou enviar `Orcamento`:

- a OS nao gera um PDF paralelo;
- ela reaproveita o mesmo PDF oficial de `Orcamentos`;
- o envio por `WhatsApp` e `E-mail` segue as mesmas validacoes do modulo comercial;
- o documento continua levando o link de aprovacao publica do cliente no proprio PDF.
- o mesmo evento em tempo real tambem pode reidratar o modal `Alterar status da OS` quando ele estiver aberto para a ordem relacionada.

Tambem foi aplicada uma revisao complementar de pt-BR/UTF-8 nas telas de formulario e visualizacao do modulo `Orcamentos`, cobrindo avisos de vinculo com OS, blocos de resumo, labels de acompanhamento e mensagens de orientacao ao operador.

## Edicao de orcamento convertido

O status `Convertido` deixou de bloquear a edicao do orcamento.

Na pratica:

- o botao `Editar` continua disponivel na tela `Visualizar Orcamento` quando o registro estiver `Convertido`;
- o mesmo comportamento vale para o painel de orcamento embutido dentro da OS;
- os ajustes continuam sendo salvos no mesmo `orcamento`, sem criar uma revisao automatica so por causa desse status;
- quando a edicao alterar efetivamente o conteudo comercial do orcamento convertido, o sistema abre nova rodada de aprovacao no mesmo registro;
- nesse caso, o status sai de `Convertido` e volta para `Reenviar orcamento`, com nova versao e necessidade de reenvio ao cliente.

## Status apos reenvio

O status `Reenviar orcamento` agora funciona apenas como etapa preparatoria.

Na pratica:

- depois que o orcamento revisado e realmente enviado ao cliente por WhatsApp ou e-mail, ele deixa `Reenviar orcamento`;
- o registro passa automaticamente para `Aguardando aprovacao`;
- esse estado indica que o documento ja saiu para o cliente e esta pendente de retorno/aprovacao.

## Visualizacao em abas

A tela `Visualizar Orcamento` foi reorganizada em abas para reduzir rolagem longa e separar melhor os contextos comercial, operacional e financeiro.

As abas atuais sao:

- `Dados do cliente`: nome, telefones, email, OS vinculada, conversa e contato de atendimento;
- `Dados do equipamento`: foto principal, tipo, marca, modelo, cor e identificacao consolidada;
- `Dados operacionais`: contexto do tipo de orcamento, status, origem, versao, conversao, prazo, observacoes, condicoes e controle manual de status;
- `Pacotes de servico`: oferta dinamica principal, link publico da oferta e historico tecnico das ofertas anteriores;
- `Envio do orcamento`: PDF, envio por WhatsApp e envio por e-mail;
- `Orcamento`: itens lancados, historico de status e rastreabilidade;
- `Financeiro do orcamento`: subtotal, desconto, acrescimo, total final e link publico do documento.

Na pratica:

- o operador encontra cada bloco sem precisar alternar entre cards espalhados na pagina;
- o financeiro deixa de dividir espaco com o resumo operacional no topo;
- as abas seguem o padrao do design system em linha unica e ajustam o tamanho dos rotulos conforme a largura da tela, mantendo todas visiveis sem exibir barra de rolagem nessa view.

## Formulario em abas

As telas `Novo Orcamento` e `Editar Orcamento` tambem passaram a usar abas no formulario principal.

Abas atuais:

- `Dados do cliente`
- `Dados do equipamento`
- `Dados operacionais`
- `Pacotes de servico`
- `Orcamento e financeiro`

Na pratica:

- os campos foram mantidos nos mesmos blocos funcionais, mas agora separados por contexto;
- o fluxo de adicionar itens e recalculo financeiro continua o mesmo, agora concentrado em uma unica aba para evitar troca desnecessaria no fechamento da proposta;
- a navegacao horizontal das abas foi preparada para uso em telas pequenas, sem depender de rolagem vertical longa para chegar ao financeiro.

## Ajuda rapida

O botao `Ajuda`, no topo do formulario, abre esta pagina pelo atalho `openDocPage('orcamentos')`.

## Resumo operacional da release 2.15.1

Nesta release, o modulo recebeu tres ajustes centrais no bloco `Dados do Cliente`:

- telefone de contato opcional;
- exibicao reativa do contato adicional do cadastro;
- alinhamento entre validacao do navegador, JavaScript e backend PHP.
