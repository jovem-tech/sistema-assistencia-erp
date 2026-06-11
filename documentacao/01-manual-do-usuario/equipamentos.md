# Manual do Usuario - Equipamentos

## Visao Geral

O cadastro de equipamentos e a base da recepcao tecnica. Cada equipamento fica vinculado a um cliente, pode ter fotos, senha de acesso, observacoes e agora tambem uma identificacao tecnica mais adequada para `Desktop montado`, `Desktop OEM`, equipamentos complementados por agente de bancada e deduplicacao por `numero de serie`, `MAC` ou `IMEI`.

---

## Cadastrar Novo Equipamento

**Caminho:** `Operacional > Aparelhos / Equip. > + Novo`

### Campos principais

| Campo | Obrigatorio | Descricao |
|---|---|---|
| Cliente | Sim | Cliente proprietario do equipamento |
| Tipo | Sim | Ex.: Smartphone, Notebook, Desktop, Tablet |
| Marca | Sim, exceto `Desktop montado` | Marca catalogada do equipamento |
| Modelo | Sim, exceto `Desktop montado` | Modelo catalogado do equipamento |
| Numero de serie | Nao | Serie ou IMEI visivel no balcao |
| Senha de acesso | Nao | Pode ser registrada em `DESENHO` ou `TEXTO` |
| Cor | Nao no cadastro completo | Pode ser definida manualmente ou por deteccao de foto |
| Estado fisico | Nao | Observacoes de entrada |
| Acessorios | Nao | Carregador, cabo, bolsa, capa etc. |
| Observacoes do equipamento | Nao | Peculiaridades, avarias visiveis e alertas tecnicos que a equipe quer destacar |

### Edicao rapida pela OS

Ao abrir ou editar uma ordem de servico, o botao `Editar` do equipamento reaproveita o mesmo cadastro dentro do modal da OS.

Nesse fluxo:

- o campo `Observacoes do equipamento` aparece na aba `Info`;
- o tecnico pode destacar detalhes do aparelho sem sair da OS;
- ao salvar, a observacao fica sincronizada com o cadastro principal do equipamento.

---

## Desktop OEM x Desktop Montado

Quando o tipo do equipamento for `Desktop`, o formulario passa a trabalhar com dois modos:

- `Desktop de marca/OEM`
- `Desktop montado`

### Desktop de marca/OEM

Use este modo quando o computador tem fabricante e modelo claros, como:

- Dell OptiPlex
- HP ProDesk
- Lenovo ThinkCentre

Nesse caso, o fluxo continua usando `Marca` e `Modelo` normalmente.

### Desktop montado

Use este modo quando o computador foi montado por pecas e nao possui um fabricante/modelo confiavel para o gabinete completo.

Nesse caso:

- `Marca` e `Modelo` deixam de ser a referencia principal do cadastro;
- o sistema salva automaticamente o catalogo compativel `Montado > Desktop montado`;
- a identificacao principal passa a ser o `Resumo tecnico`.

Campos tecnicos do desktop montado:

- `Tipo de gabinete`
- `Status da identificacao do gabinete`
- `Placa-mae / modelo`
- `Chipset`
- `Processador`
- `Memoria RAM`
- `Armazenamento`
- `Placa de video`
- `Fonte`
- `Observacao do gabinete`

Exemplo de resumo tecnico gerado:

`Desktop montado | Mid Tower | H510 | Intel Core i5-10400 | 16 GB | SSD 480 GB`

---

## Como identificar o gabinete

Ao lado do campo `Tipo de gabinete` existe o botao `Como identificar?`.

Ele abre um guia rapido com os perfis mais comuns:

- `Slim / SFF`
- `Mini Tower`
- `Mid Tower`
- `Full Tower`
- `Compacto / Cube`
- `Rack / Industrial`
- `Nao identificado / A confirmar`

Regra pratica:

- no balcao, marque o que for visivel com seguranca;
- se houver duvida, use `Nao identificado / A confirmar`;
- a bancada pode corrigir depois, manualmente ou com apoio do agente.

---

## Cadastro rapido no balcao

No atendimento de recepcao, o ideal e preencher primeiro o que estiver visivel:

- cliente
- tipo
- serie/IMEI, se houver
- fotos
- cor
- observacoes
- no caso de desktop montado: gabinete visivel e qualquer informacao tecnica impressa no gabinete ou etiqueta

Se o equipamento ainda nao ligar, nao e necessario bloquear o cadastro esperando dados completos de placa, memoria ou armazenamento.

---

## Duplicidade por serie, MAC e IMEI

Ao salvar um equipamento, o sistema agora compara os identificadores principais:

- `numero de serie`
- `MAC`, quando ele estiver registrado no campo de serie
- `IMEI`

Comportamento:

- se o identificador ja existir para o mesmo cliente, o ERP bloqueia o novo cadastro e orienta a usar o equipamento existente;
- se o identificador ja existir para outro cliente, o ERP alerta que o equipamento ja existe e oferece vincular o cliente atual ao mesmo cadastro;
- o objetivo e manter um historico unico do equipamento, evitando redundancia.

Exemplos praticos:

- a esposa traz o notebook hoje e o marido traz o mesmo notebook depois: o sistema nao cria outro equipamento; ele vincula os dois clientes ao mesmo cadastro;
- um desktop volta para manutencao com o mesmo `MAC` salvo como fallback de serie: o ERP reconhece o cadastro anterior e reaproveita o equipamento ja existente.

Observacao operacional:

- o equipamento continua com um `cliente principal`;
- clientes adicionais ficam como `clientes vinculados` ao mesmo equipamento.

---

## Encerrar equipamento

Quando um equipamento chega ao fim da vida util, a equipe pode usar o botao `Encerrar` na listagem de equipamentos ou na ficha detalhada do cadastro.

Casos mais comuns:

- retirada de pecas
- problema irreparavel
- descarte definitivo
- venda das pecas para outros clientes
- qualquer outro motivo que torne o equipamento inviavel para novas OS

Como funciona:

- o encerramento exige escolher um `motivo` e pode receber uma `observacao interna`;
- o equipamento continua visivel no historico do cliente e nas OS antigas;
- a ficha passa a mostrar badge `Encerrado`, motivo e data do encerramento;
- quando existir OS em andamento, a propria interface sinaliza `OS em andamento` e desabilita o botao `Encerrar` ate a conclusao ou cancelamento dessas ordens;
- o cadastro deixa de aceitar `Nova OS` e novos `vinculos operacionais` com outros clientes;
- o equipamento some das listas operacionais de selecao para abertura de OS e orcamentos, exceto quando uma OS historica ja usa esse equipamento e esta apenas sendo editada;
- se houver OS abertas ou em andamento para o equipamento, o ERP bloqueia o encerramento ate a conclusao/cancelamento dessas ordens.
- quando uma OS desse equipamento for finalizada como `descartado`, o sistema encerra o equipamento automaticamente usando o mesmo motivo;
- o equipamento passa a registrar um historico proprio do ciclo de vida, com cada encerramento, reativacao e encerramento automatico;
- quando o recurso foi ativado em um ambiente ja em uso, a timeline tambem reaproveita os eventos antigos que ja estavam gravados nos logs tecnicos;
- o quadro `Ordens de Servico Vinculadas` continua mostrando todo o historico de OS do equipamento mesmo depois do encerramento;

Leitura visual:

- na grade de `Equipamentos`, registros encerrados ficam destacados com badge `Encerrado`;
- quando houver OS em andamento, a grade exibe o aviso `OS em andamento` com o encerramento bloqueado;
- na ficha do cliente, o equipamento segue listado, mas com identificacao clara de que esta apenas em historico;
- na ficha detalhada do equipamento, a caixa `Historico do ciclo de vida` mostra cada encerramento e cada volta a operacao em ordem cronologica;
- na ficha do equipamento, o bloco `Ordens de Servico Vinculadas` passa a mostrar `Novas OS bloqueadas`.

---

## Reativar equipamento

Se um equipamento encerrado voltar a operar depois de ser recuperado, recondicionado ou reparado, a equipe pode usar o botao `Reativar` na listagem ou na ficha detalhada.

Como funciona:

- a reativacao pede um `motivo` e pode receber uma `observacao interna`;
- o equipamento volta para `ativo` e passa a aceitar novas OS e novos vinculos operacionais;
- os motivos mais comuns sao `Recuperado`, `Recondicionado`, `Reparado` e `Voltou a funcionar`;
- o registro anterior de encerramento continua preservado no historico do equipamento;
- se o equipamento tiver sido encerrado automaticamente por uma OS `descartado`, a reativacao continua disponivel do mesmo jeito.
- cada volta a operacao gera um novo evento no historico do equipamento, preservando a linha do tempo completa.

---

## Complemento tecnico na bancada

Quando o equipamento ligar na bancada, o cadastro pode ser enriquecido com:

- leitura manual dos campos tecnicos
- sincronizacao automatica pelo agente de inventario

O sistema passa a guardar:

- `status da configuracao`
- `origem da configuracao`
- `data da ultima deteccao`

Isso ajuda a diferenciar:

- cadastro feito so no balcao
- cadastro ajustado manualmente na bancada
- cadastro sincronizado por agente
- cadastro misto

---

## Agente de deteccao para desktop e notebook

Equipamentos do tipo `Desktop` e `Notebook` podem receber complemento automatico de inventario.

O fluxo principal agora usa o `Coletor de Bancada` portatil:

- pacote publicado em `public/assets/agents/JovemTechBenchCollector-win-x64.zip`
- executavel principal: `JovemTechBenchCollector.exe`
- nao exige instalacao na maquina do cliente
- executa coleta unica por padrao, ideal para bancada
- quando houver `OS`, grava o arquivo final no padrao `C:\JovemTechBenchCollector\inf_<numero_os>.json`
- quando nao houver `OS`, usa o fallback `C:\JovemTechBenchCollector\last-snapshot.json`
- o script `public/assets/agents/jovemtec-monitor-agent.ps1` continua como fallback tecnico

O agente coleta, quando a maquina liga:

- fabricante
- modelo
- tipo do dispositivo
- tipo de chassi
- placa-mae
- chipset
- BIOS
- processador
- GPU
- memoria RAM
- armazenamento
- versao do Windows

Fluxo recomendado:

1. o balcao faz o cadastro rapido;
2. a bancada liga a maquina;
3. a equipe baixa e executa o `JovemTechBenchCollector.exe`;
4. o coletor envia o inventario ao ERP;
5. o equipamento recebe automaticamente os dados tecnicos;
6. o resumo tecnico e atualizado para OS, PDFs e comunicacoes.

Uso pratico do coletor:

- abrir o `.exe` e preencher `ERP`, `email do usuario do ERP` e `numero da OS`;
- opcionalmente, informar um `InstallationId` proprio;
- deixar o coletor concluir o `bootstrap` e o `check-in`;
- quando quiser apenas testar a leitura local sem enviar ao ERP, usar `--dry-run`.

Fluxo alternativo:

- a ficha do equipamento continua oferecendo o comando de PowerShell como fallback tecnico.

Importacao direta no formulario:

- no painel tecnico de `Desktop` e `Notebook`, existe o botao `Buscar do agente (C:\)`;
- ao clicar, o ERP tenta copiar automaticamente `JovemTechBenchCollector.exe` para `C:\JovemTechBenchCollector\` quando ele ainda nao estiver presente;
- em seguida, o ERP executa uma coleta local nova em `dry-run` e reaproveita o snapshot salvo localmente;
- se houver `OS` no contexto, o arquivo final passa a ser `C:\JovemTechBenchCollector\inf_<numero_os>.json`;
- esse arquivo local agora funciona como uma `OS digital` de apoio, porque passa a registrar `data da coleta`, `data de gravacao`, `cliente`, `empresa` e os principais dados da ordem de servico quando a coleta parte do formulario da `OS`;
- o bloco `serviceOrder` do snapshot pode incluir `numero da OS`, `status`, `prioridade`, `tecnico`, `relato do cliente`, `datas principais`, `equipamento` e `link publico do selo`, quando houver contexto suficiente no ERP local;
- o bloco `company` registra os dados atuais da Jovem Tech configurados no ERP, incluindo `nome`, `telefone`, `email` e `endereco`;
- depois que o JSON final e montado, o ERP remove automaticamente o `JovemTechBenchCollector.exe` e o `README.md` da pasta local, deixando apenas o arquivo final de inventario/OS digital;
- o preenchimento cobre `serie`, `marca`, `modelo`, `placa-mae`, `chipset`, `processador`, `memoria`, `armazenamento` e `placa de video`;
- no campo catalogado `Modelo`, a importacao passa a priorizar o `chipset`; se o coletor nao trouxer `chipset`, o sistema usa o `modelo` detectado no inventario como fallback;
- quando o equipamento detectado for `Desktop` e houver `chassisType`, o sistema tambem tenta sugerir o `tipo de gabinete` e marca o status como `Detectado por agente`.

Regra da serie automatica:

- o coletor usa primeiro a `serie` vinda da `BIOS`;
- se a BIOS nao entregar uma serie valida, o sistema usa o `MAC` da placa de rede;
- isso vale para `Notebook` e `Desktop`.

Observacao importante:

- esse botao funciona quando o ERP estiver rodando na mesma maquina Windows que recebeu o coletor e o snapshot local;
- se o ERP estiver em servidor remoto/VPS, ele nao consegue ler o `C:\` do computador do cliente.

Importante:

- para `Notebook`, o painel tecnico local tambem fica disponivel e passa a preencher `marca` com base no inventario detectado; no campo catalogado `Modelo`, a importacao prioriza o `chipset` e usa o `modelo` detectado apenas como fallback quando o `chipset` nao vier preenchido;
- para `Desktop montado`, o agente ajuda principalmente em `chipset`, `processador`, `memoria`, `armazenamento` e `placa de video`.

---

## Marca e Modelo com cadastro contextual

Os campos `Marca` e `Modelo` continuam com botao `+ Adicionar` ao lado da label.

Comportamento:

- abre modal rapido sem sair do formulario;
- o item salvo entra imediatamente na selecao atual;
- no caso de `Desktop montado`, esses campos ficam fora do fluxo principal, porque o sistema usa o catalogo tecnico padrao automaticamente.

---

## Senha de Acesso

O campo de senha de acesso continua com dois modos:

- `DESENHO`
- `TEXTO`

No modo `DESENHO`, a equipe registra o padrao em grade 3x3.

---

## Foto de Perfil do Equipamento

1. Clique em `Tirar Foto` para abrir a camera.
2. Ou clique em `Galeria` para selecionar arquivos.
3. O editor de imagem abre para recorte e ajustes.
4. Confirme em `Finalizar Corte`.
5. O sistema usa fallback seguro se o cropper nao estiver disponivel.
6. O limite continua sendo de ate 4 fotos por equipamento.

As fotos seguem o padrao reativo do sistema:

- inseriu foto: miniaturas atualizam na hora;
- removeu foto: some na hora;
- alterou principal: card e visualizacao refletem imediatamente.

---

## Cor e deteccao inteligente

O seletor de cor continua com:

- preview grande
- catalogo por familias de tons
- identificacao automatica da cor mais proxima
- sugestao por foto

Isso vale tanto para o cadastro completo de equipamentos quanto para o modal rapido da OS.

---

## Visualizar Equipamento

Na tela de detalhes do equipamento voce encontra:

- foto principal e galeria
- identificacao principal do equipamento
- resumo tecnico, quando existir
- perfil do desktop (`OEM` ou `Montado`)
- configuracao tecnica detalhada
- status da configuracao
- data da ultima deteccao
- painel do agente, quando houver sincronizacao
- botao para baixar o `Coletor de Bancada (.zip)` quando o pacote estiver publicado no ambiente
- comando orientativo do coletor e comando PowerShell de fallback para `Desktop` e `Notebook` vinculados a uma OS
- no formulario de desktop, botao `Buscar do agente (C:\)` para importar o ultimo snapshot local do coletor

---

## Cadastro rapido pela Ordem de Servico

No modal rapido de equipamento aberto dentro da `Nova OS`:

- `Cor` continua obrigatoria;
- `ao menos uma foto` continua obrigatoria;
- `Marca` e `Modelo` deixam de ser obrigatorios apenas quando o tipo selecionado for `Desktop` em modo `montado`;
- o modal passa a exibir o painel tecnico local para `Desktop` e `Notebook`;
- no `Notebook`, o coletor pode preencher automaticamente `marca`, `modelo` e `serie`;
- no `Desktop`, o botao tambem pode provisionar o coletor local em `C:\JovemTechBenchCollector` antes da leitura;
- o seletor de equipamento da OS mostra melhor o nome tecnico do desktop montado para reduzir erro de escolha.

Em erro de validacao, o modal continua abrindo automaticamente a aba pendente.

---

## Vincular a cliente

Um equipamento pode ser vinculado a outros clientes na tela de detalhes, sem perder o proprietario principal.

Uso pratico:

- retirada por terceiro
- equipamento compartilhado
- mais de um usuario para a mesma maquina

---

## Categorias auxiliares

**Caminho:** `Operacional > Aparelhos / Equip. > submenu`

| Item | Funcao |
|---|---|
| Tipos | Gerencia os tipos de equipamento |
| Marcas | Lista e cadastra marcas |
| Modelos | Lista e cadastra modelos por marca |
| Base de Defeitos | Problemas comuns por tipo |

---

## Resumo operacional

Use esta regra simples:

- `Desktop OEM`: marca e modelo sao a referencia principal;
- `Desktop montado`: configuracao tecnica e a referencia principal;
- `Notebook`: continua com marca/modelo, mas pode receber complemento tecnico por agente;
- `Balcao`: registra o visivel;
- `Bancada`: complementa o tecnico;
- `Agente`: acelera memoria, chipset, processador e armazenamento quando a maquina liga.
