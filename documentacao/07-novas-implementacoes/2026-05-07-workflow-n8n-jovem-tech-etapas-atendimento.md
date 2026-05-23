# Workflow n8n Jovem Tech - Atendimento por Etapas

Data: 07/05/2026

## Objetivo

Adaptar o workflow de roteamento por etapas para o contexto real da `Jovem Tech`, substituindo o fluxo anterior por um atendimento focado em:

- triagem de informatica e celulares;
- coleta progressiva de dados do equipamento;
- orientacao inicial segura;
- agendamento ou entrada do aparelho;
- orcamento, aprovacao e acompanhamento;
- encerramento com pos-atendimento.

## Arquivo entregue

- `documentacao/10-deploy/n8n/jovem-tech-atendimento-por-etapas.json`

## Estrutura do workflow

Fluxo:

`Mensagem -> merge de contexto -> switch por Step-Index -> agente da etapa -> JSON estruturado`

Componentes principais:

1. `When chat message received`
   - entrada de teste pelo chat do n8n.
2. `preparar valores teste`
   - payload de exemplo ja alinhado com a Jovem Tech.
3. `exec prod`
   - trigger para uso como subworkflow com inputs nomeados.
4. `merge inicial`
   - consolida contexto vindo do teste local ou da execucao externa.
5. `Selecionar Agente da Etapa`
   - roteia o atendimento pelas etapas `0` a `7`.
6. `Memory Chat`
   - mantem contexto conversacional por `User.identifier`.
7. `Steps Model`
   - modelo principal usado por todos os agentes.
8. `Structured Output Parser`
   - garante retorno JSON consistente para todas as etapas.

## Etapas adaptadas

### Etapa 0 - Saudacao e Identificacao

- recebe o cliente;
- identifica se o contato e novo ou recorrente;
- coleta o minimo necessario para abrir o contexto.

### Etapa 1 - Triagem do Problema

- entende o defeito principal;
- mede urgencia;
- identifica riscos como liquido, cheiro de queimado ou bateria estufada.

### Etapa 2 - Coleta Tecnica

- coleta tipo de equipamento, marca, modelo e historico relevante;
- organiza o contexto sem transformar a conversa em formulario frio.

### Etapa 3 - Orientacao Inicial e Testes

- sugere somente testes simples e seguros;
- evita desmontagem, procedimentos arriscados e promessas de reparo remoto.

### Etapa 4 - Agendamento ou Entrada

- leva o cliente para o proximo passo operacional;
- informa endereco, horario e itens que devem ser levados para a loja.

### Etapa 5 - Orcamento e Aprovacao

- conduz a conversa de valor, taxa de diagnostico e prazo estimado;
- evita inventar preco e nao usa urgencia artificial.

### Etapa 6 - Status do Reparo

- atualiza andamento, peca, testes e retirada;
- nao inventa prazo ou status tecnico.

### Etapa 7 - Pos-Atendimento e Fidelizacao

- encerra a conversa com orientacoes e suporte pos-servico;
- abre espaco para retorno, garantia e indicacao sem exagero comercial.

## Melhorias aplicadas na adaptacao

- substituicao completa do contexto anterior por atendimento tecnico real;
- troca de `Product` por `Company` no contrato principal do workflow;
- parser estruturado unico e generico para todas as etapas;
- correcao do parser para a `Etapa 7`, que agora tambem participa do schema;
- payload de teste atualizado para um caso real de tela quebrada em iPhone;
- prompts reescritos para linguagem profissional, acolhedora e objetiva.

## Contrato de entrada esperado

Inputs do `Execute Workflow Trigger`:

- `Step-Index`
- `Query`
- `User`
- `Company`
- `Persona`
- `Dynamic Prompt`

Campos importantes:

- `Step-Index`: define qual agente sera acionado.
- `Query`: mensagem atual e metadados da conversa.
- `User.identifier`: chave de memoria.
- `Company`: dados operacionais da Jovem Tech.
- `Persona`: nome e estilo da atendente virtual.
- `Dynamic Prompt`: instrucoes por etapa e contexto de cliente novo/recorrente.

## Output padronizado

Todos os agentes retornam:

- `etapa_atual.indice`
- `etapa_atual.nome`
- `classificacao.intencao`
- `classificacao.prioridade`
- `classificacao.pode_avancar`
- `proxima_acao`
- `dados_extraidos`
- `response`

## Importacao e ajustes minimos

1. Importar `documentacao/10-deploy/n8n/jovem-tech-atendimento-por-etapas.json`.
2. Revisar a credencial `Felipe - OpenRouter`.
3. Revisar a conexao `MongoDB account`.
4. Ajustar dados de `Company` no node `preparar valores teste` se quiser padrao diferente.
5. Se o workflow for chamado como subworkflow, enviar os seis inputs do trigger com os nomes acima.

## Observacao operacional

Este workflow foi pensado como motor de conversa por etapa.

Ele pode ser usado:

- sozinho, para teste de prompts e progressao;
- como subworkflow de um fluxo maior com WhatsApp/Evolution;
- como base para acoplar consultas ao ERP em etapas especificas.
