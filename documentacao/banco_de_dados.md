# DocumentaÃ§Ã£o do Banco de Dados - AssistTech

**Banco de Dados:** `assistencia_tecnica`
**Character Set:** `utf8mb4`
**Collation:** `utf8mb4_unicode_ci`

Esta documentaÃ§Ã£o descreve a estrutura completa das tabelas do sistema de AssistÃªncia TÃ©cnica (AssistTech), incluindo relacionamentos, chaves primÃ¡rias e estrangeiras, bem como as restriÃ§Ãµes e finalidades de cada tabela e campo.

> **Nota:** O schema evoluiu com RBAC e com a separaÃ§Ã£o de Tipos/Marcas/Modelos.
> Se vocÃª importou `database.sql`, execute `update_equip_db.php` e `setup_rbac.php`.
> Para um resumo atualizado, consulte `documentacao/04-banco-de-dados/tabelas-principais.md`.
>
> **Atualizacao 20/03/2026 (modulo WhatsApp unificado):**
> - O legado Whaticket foi removido do ERP.
> - Chaves legadas `whatsapp_whaticket_*` foram removidas da tabela `configuracoes`.
> - Providers legados `whaticket` foram normalizados para `api_whats_local`.
> - Migration de limpeza: `2026-03-20-060000_RemoveWhaticketLegacyModule.php`.
>
> **Atualizacao 31/05/2026 (financeiro gerencial):**
> - A tabela `financeiro` recebeu colunas de classificacao para `DRE` e `fluxo de caixa`.
> - Migration de referencia: `2026-05-31-120000_AddGerencialFieldsToFinanceiro.php`.
>
> **Atualizacao 01/06/2026 (despesa fixa mensal na DRE):**
> - A tabela `financeiro` recebeu a coluna `dre_fixo_mensal`.
> - Migration de referencia: `2026-06-01-090000_AddDreFixoMensalToFinanceiro.php`.
>
> **Atualizacao 01/06/2026 (catalogos financeiros configuraveis):**
> - Foram criadas as tabelas `financeiro_categorias`, `financeiro_dre_grupos` e `financeiro_dre_subgrupos`.
> - Migration de referencia: `2026-06-01-120000_CreateFinanceiroCatalogos.php`.
>
> **Atualizacao 01/06/2026 (baixa parcial e multiplos movimentos):**
> - Foi criada a tabela `financeiro_movimentos`.
> - O campo `financeiro.status` passou a aceitar `parcial`.
> - O `fluxo de caixa realizado` agora soma movimentos em vez de depender apenas do titulo.
> - Migration de referencia: `2026-06-01-150000_CreateFinanceiroMovimentosTable.php`.
>
> **Atualizacao 01/06/2026 (fornecedor em contas a pagar):**
> - A tabela `financeiro` recebeu a coluna `fornecedor_id`.
> - O vinculo aponta para `fornecedores.id` nas despesas quando a migration estiver aplicada.
> - Migration de referencia: `2026-06-01-163000_AddFornecedorToFinanceiro.php`.
>
> **Atualizacao 01/06/2026 (competencia manual por mes/ano):**
> - O formulario financeiro passou a capturar `mes/ano de competencia` para lancamentos manuais.
> - O backend converte esse valor para `data_competencia = 01/mm/aaaa`, mantendo compatibilidade com o tipo `DATE`.
> - Receitas automaticas de `OS` continuam preservando a data completa de entrega em `data_competencia`.
>
> **Atualizacao 01/06/2026 (checklist de entrada e PDF de abertura):**
> - A tabela `checklist_execucoes` recebeu a coluna `observacoes_estado`.
> - O campo guarda a observacao livre do estado na entrada e complementa as pendencias estruturadas do checklist.
> - Migration de referencia: `2026-06-01-190000_AddObservacoesEstadoToChecklistExecucoes.php`.
>
> **Atualizacao 04/06/2026 (desktop montado e inventario por agente):**
> - A tabela `equipamentos` recebeu colunas tecnicas para `Desktop montado` e para o resumo tecnico de bancada.
> - A tabela `monitor_agents` recebeu colunas de inventario para `device_type`, `chassis_type`, `chipset`, `gpu` e `storage_summary`.
> - Desktop e Notebook agora podem ser complementados automaticamente pelo agente quando a maquina ligar na bancada.
> - Migration de referencia: `2026-06-04-090000_AddDesktopProfilesAndAgentInventoryToEquipamentos.php`.
>
> **Atualizacao 05/06/2026 (deduplicacao por serie, MAC e IMEI):**
> - O ERP passou a comparar `numero_serie`, `IMEI` e `MAC` salvo no campo de serie antes de criar ou atualizar equipamentos.
> - A tabela `equipamento_clientes` passou a ser usada como relacionamento ativo para clientes diferentes que levam o mesmo equipamento fisico.
> - Foram adicionados indices em `equipamentos.numero_serie` e `equipamentos.imei`, alem de unicidade operacional em `equipamento_clientes (equipamento_id, cliente_id)`.
> - Migration de referencia: `2026-06-05-120000_AddEquipamentoIdentityIndexes.php`.

---

## Ãndice das Tabelas
1. [usuarios](#1-usuarios)
2. [clientes](#2-clientes)
3. [equipamentos](#3-equipamentos)
4. [pecas](#4-pecas)
5. [os (Ordens de ServiÃ§o)](#5-os-ordens-de-serviÃ§o)
6. [os_itens](#6-os_itens)
7. [movimentacoes](#7-movimentacoes)
8. [financeiro](#8-financeiro)
9. [fotos](#9-fotos)
10. [logs](#10-logs)
11. [configuracoes](#11-configuracoes)
12. [fornecedores](#12-fornecedores)
13. [funcionarios](#13-funcionarios)
14. [equipamento_clientes](#14-equipamento_clientes)
15. [equipamentos_tipos](#15-equipamentos_tipos)
16. [equipamentos_marcas](#16-equipamentos_marcas)
17. [equipamentos_modelos](#17-equipamentos_modelos)
18. [equipamentos_defeitos](#18-equipamentos_defeitos)
19. [equipamento_defeito_procedimentos](#19-equipamento_defeito_procedimentos)
20. [os_defeitos](#20-os_defeitos)
21. [acessorios_os](#21-acessorios_os)
22. [fotos_acessorios](#22-fotos_acessorios)
23. [estado_fisico_equipamento](#23-estado_fisico_equipamento)
24. [estado_fisico_fotos](#24-estado_fisico_fotos)
25. [monitor_agents](#25-monitor_agents)
26. [monitor_agent_snapshots](#26-monitor_agent_snapshots)

---

## 1. usuarios
Armazena as informaÃ§Ãµes dos usuÃ¡rios que possuem acesso ao sistema (Atendentes, TÃ©cnicos e Administradores).

| Campo | Tipo | RestriÃ§Ãµes | DescriÃ§Ã£o |
|-------|------|------------|-----------|
| `id` | INT | PK, AUTO_INCREMENT | Identificador Ãºnico do usuÃ¡rio. |
| `nome` | VARCHAR(100) | NOT NULL | Nome completo do usuÃ¡rio. |
| `email` | VARCHAR(100) | UNIQUE, NOT NULL | E-mail de acesso e comunicaÃ§Ã£o. |
| `senha` | VARCHAR(255) | NOT NULL | Hash da senha de acesso (bcrypt). |
| `telefone` | VARCHAR(20) | NULL | Telefone de contato. |
| `perfil` | ENUM | DEFAULT 'atendente' | NÃ­vel de acesso: 'admin', 'tecnico', 'atendente'. |
| `foto` | VARCHAR(255) | NULL | Nome do arquivo de imagem salvo do lado do servidor correspondente Ã  foto de perfil do usuÃ¡rio. |
| `ativo` | TINYINT(1) | DEFAULT 1 | Define se o usuÃ¡rio tem permissÃ£o para logar (1 = Ativo, 0 = Inativo). |
| `ultimo_acesso`| DATETIME | NULL | Data e hora em que o usuÃ¡rio logou pela Ãºltima vez. |
| `token_recuperacao`| VARCHAR(255)| NULL | Token utilizado para recuperar senha esquecida. |
| `token_expiracao`| DATETIME| NULL | ExpiraÃ§Ã£o do token de recuperaÃ§Ã£o de senha. |
| `created_at` | DATETIME | DEFAULT CURRENT_TIMESTAMP | Data e hora do registro da conta. |
| `updated_at` | DATETIME | ON UPDATE CURRENT_TIMESTAMP | Data e hora da Ãºltima alteraÃ§Ã£o de qualquer dado deste registro. |

---

## 2. clientes
Armazena informaÃ§Ãµes das Pessoas FÃ­sicas e JurÃ­dicas para o registro de equipamentos e Ordens de ServiÃ§o.

| Campo | Tipo | RestriÃ§Ãµes | DescriÃ§Ã£o |
|-------|------|------------|-----------|
| `id` | INT | PK, AUTO_INCREMENT | Identificador Ãºnico do cliente. |
| `tipo_pessoa` | ENUM | DEFAULT 'fisica' | FisÃ­ca (CPF) ou JurÃ­dica (CNPJ). |
| `nome_razao` | VARCHAR(100) | NOT NULL | Nome do Cliente Pessoa FÃ­sica ou RazÃ£o Social de Pessoa JurÃ­dica. |
| `cpf_cnpj` | VARCHAR(20) | UNIQUE, NULL | CPF ou CNPJ (utilizado na validaÃ§Ã£o e na emissÃ£o de NFs/OrÃ§amentos). |
| `rg_ie` | VARCHAR(20) | NULL | Registro Geral (Pessoa FÃ­sica) ou InscriÃ§Ã£o Estadual (Pessoa JurÃ­dica). |
| `email` | VARCHAR(100) | NULL | E-mail do cliente (para receber orÃ§amentos/OS). |
| `telefone1` | VARCHAR(20) | NOT NULL | Contato principal (geralmente WhatsApp/Telefone). |
| `telefone2` | VARCHAR(20) | NULL | Contato secundÃ¡rio. |
| `cep` | VARCHAR(10) | NULL | CEP para preenchimento de endereÃ§o. |
| `endereco` | VARCHAR(100) | NULL | Logradouro / Rua. |
| `numero` | VARCHAR(10) | NULL | NÃºmero do estabelecimento / residÃªncia. |
| `complemento`| VARCHAR(50) | NULL | Complemento do endereÃ§o. |
| `bairro` | VARCHAR(50) | NULL | Bairro. |
| `cidade` | VARCHAR(50) | NULL | Cidade. |
| `uf` | CHAR(2) | NULL | Estado (UF). |
| `observacoes` | TEXT | NULL | Notas adicionais sobre o cliente (ex: restriÃ§Ãµes). |
| `created_at` | DATETIME | DEFAULT CURRENT_TIMESTAMP | Data de cadastro. |
| `updated_at` | DATETIME | ON UPDATE CURRENT_TIMESTAMP | Ãšltima atualizaÃ§Ã£o do cadastro. |

**Ãndices SecundÃ¡rios:** `idx_nome` (nome_razao), `idx_cpf` (cpf_cnpj), `idx_telefone` (telefone1)

---

## 3. equipamentos
Nota de atualizacao 05/06/2026:

- o schema atual de equipamentos usa relacoes por `tipo_id`, `marca_id` e `modelo_id`;
- `Desktop` passa a aceitar `desktop_modalidade = oem|montado`;
- novos campos tecnicos: `gabinete_tipo`, `gabinete_identificacao_status`, `gabinete_observacao`, `placa_mae`, `chipset`, `processador`, `memoria_ram`, `armazenamento`, `placa_video`, `fonte_alimentacao` e `resumo_tecnico`;
- metadados de sincronizacao: `configuracao_status`, `configuracao_origem` e `configuracao_detectada_em`;
- `Desktop montado` continua usando `marca/modelo` padrao para compatibilidade interna, mas a exibicao principal passa a usar `resumo_tecnico`;
- `numero_serie` e `imei` ganharam indices dedicados para apoiar a deteccao de duplicidade;
- o sistema trata `MAC` salvo em `numero_serie` como identificador tecnico de fallback e evita criar novo cadastro quando esse valor ja existir;
- quando o mesmo equipamento volta por outro cliente, o ERP reutiliza o cadastro base e vincula as pessoas relacionadas por `equipamento_clientes`.

Armazena a descriÃ§Ã£o detalhada dos itens (aparelhos/dispositivos) trazidos pelo cliente para anÃ¡lise/reparo.

| Campo | Tipo | RestriÃ§Ãµes | DescriÃ§Ã£o |
|-------|------|------------|-----------|
| `id` | INT | PK, AUTO_INCREMENT | Identificador Ãºnico do equipamento. |
| `cliente_id` | INT | FK -> clientes(id) NOT NULL | Vincula o equipamento ao seu dono (cliente). ON DELETE CASCADE. |
| `tipo` | ENUM | NOT NULL | Tipo fÃ­sico ('notebook', 'desktop', 'celular', 'tablet', 'impressora', 'outros'). |
| `marca` | VARCHAR(50) | NOT NULL | Fabricante do equipamento (Ex: Dell, Samsung, Apple). |
| `modelo` | VARCHAR(100) | NOT NULL | NÃºmero do Modelo do equipamento. |
| `numero_serie`| VARCHAR(100) | NULL | NÃºmero de SÃ©rie do fabricante para validaÃ§Ã£o e garantia. |
| `imei` | VARCHAR(20) | NULL | IdentificaÃ§Ã£o Internacional de Equipamento MÃ³vel (Exclusivo para aparelhos com SIM). |
| `senha_acesso`| VARCHAR(255) | NULL | Senha ou Pin necessÃ¡rio para ligar e testar o equipamento. |
| `estado_fisico`| TEXT | NULL | ConservaÃ§Ã£o detalhada na hora da entrada (ArranhÃµes, telas quebradas, etc). |
| `acessorios` | TEXT | NULL | O que veio junto (Carregador, bateria, cabos, bolsa). |
| `observacoes` | TEXT | NULL | ObservaÃ§Ãµes complementares sobre o item. |
| `created_at` | DATETIME | DEFAULT CURRENT_TIMESTAMP | Data de inclusÃ£o. |
| `updated_at` | DATETIME | ON UPDATE CURRENT_TIMESTAMP | Ãšltima alteraÃ§Ã£o. |

**Ãndices SecundÃ¡rios:** `idx_cliente` (cliente_id), `idx_equipamentos_numero_serie` (numero_serie), `idx_equipamentos_imei` (imei)

---

## 4. pecas
Representam os produtos vendidos e componentes consumidos usados na AssistÃªncia TÃ©cnica (Estoque).

| Campo | Tipo | RestriÃ§Ãµes | DescriÃ§Ã£o |
|-------|------|------------|-----------|
| `id` | INT | PK, AUTO_INCREMENT | Identificador da peÃ§a/produto de estoque. |
| `codigo` | VARCHAR(50) | UNIQUE, NULL | CÃ³digo de barras ou cÃ³digo interno (SKU). |
| `codigo_fabricante`| VARCHAR(100)| NULL | Part Number correspondente com os catÃ¡logos do fabricante da peÃ§a. |
| `nome` | VARCHAR(100) | NOT NULL | DescriÃ§Ã£o da PeÃ§a de reposiÃ§Ã£o ou produto. |
| `categoria` | VARCHAR(50) | NULL | Categoria da peÃ§a. |
| `modelos_compativeis`| TEXT | NULL | RelaÃ§Ã£o de equipamentos compatÃ­veis. |
| `fornecedor` | VARCHAR(100) | NULL | Nome do Ãºltimo fornecedor (opcional, ou link para futura tabela). |
| `localizacao` | VARCHAR(50) | NULL | Corredor / Prateleira / Gaveta de alocaÃ§Ã£o de estoque fÃ­sico na assistÃªncia. |
| `preco_custo` | DECIMAL(10,2)| DEFAULT 0 | Custo do material adquirido. |
| `preco_venda` | DECIMAL(10,2)| DEFAULT 0 | PreÃ§o oferecido em prateleira e Ordens de ServiÃ§o (OrÃ§amento). |
| `quantidade_atual`| INT | DEFAULT 0 | Saldo da PeÃ§a no estoque atual. |
| `estoque_minimo`| INT | DEFAULT 1 | Momento recomendÃ¡vel em que um alerta visual e no sistema avisa a necessidade de reposiÃ§Ã£o. |
| `estoque_maximo`| INT | NULL | Capacidade de lotaÃ§Ã£o aceitÃ¡vel para compras. |
| `foto` | VARCHAR(255) | NULL | Imagem ilustrativa do produto/peÃ§a. |
| `observacoes` | TEXT | NULL | ObservaÃ§Ãµes extras. |
| `ativo` | TINYINT(1) | DEFAULT 1 | Define se a peÃ§a ainda Ã© comercializada. |
| `created_at` | DATETIME | DEFAULT CURRENT_TIMESTAMP | Cadastro da PeÃ§a. |
| `updated_at` | DATETIME | ON UPDATE CURRENT_TIMESTAMP | Ãšltima vez editada. |

**Ãndices SecundÃ¡rios:** `idx_nome` (nome), `idx_categoria` (categoria)

---

## 5. os (Ordens de ServiÃ§o)
Ã‰ a tabela principal da operaÃ§Ã£o de negÃ³cios, atrelando um Cliente, seu respectivo Equipamento, a tratativa de reparo prestada por um TÃ©cnico com datas e valores orÃ§amentÃ¡rios/faturados.

| Campo | Tipo | RestriÃ§Ãµes | DescriÃ§Ã£o |
|-------|------|------------|-----------|
| `id` | INT | PK, AUTO_INCREMENT | Identificador primÃ¡rio da Ordem de ServiÃ§o. |
| `numero_os` | VARCHAR(20) | UNIQUE, NOT NULL | NÃºmero legÃ­vel gerado internamente (Ex: OS-2026-0001) usado para exibiÃ§Ã£o e busca. |
| `cliente_id` | INT | FK -> clientes(id) NOT NULL| Cliente solicitante do conserto. |
| `equipamento_id`| INT | FK -> equipamentos(id) NOT NULL| Objeto a ser consertado no contexto do registro. |
| `tecnico_id` | INT | FK -> usuarios(id) NULL| UsuÃ¡rio do sistema (tÃ©cnico) incumbido do diagnÃ³stico e reparos. Pode ser deixado em NULL. |
| `status` | ENUM | DEFAULT 'aguardando_analise' | Status em que se encontra o andamento do Workflow ('aguardando_analise', 'aguardando_orcamento', 'aguardando_aprovacao', 'aprovado', 'reprovado', 'em_reparo', 'aguardando_peca', 'pronto', 'entregue', 'cancelado'). |
| `prioridade` | ENUM | DEFAULT 'normal' | UrgÃªncia (baixa, normal, alta, urgente). |
| `relato_cliente`| TEXT | NOT NULL | O que o cliente declarou como queixa, sintoma ou pedido inicial de serviÃ§o. |
| `diagnostico_tecnico`| TEXT| NULL | ConclusÃ£o oficial do profissional perante a anÃ¡lise. |
| `solucao_aplicada`| TEXT | NULL | Procedimento tÃ©cnico que foi adotado ou serÃ¡ prestado. |
| `procedimentos_executados`| TEXT | NULL | Lista textual incremental dos procedimentos executados no formato operacional (`[descricao - dd/mm/aa-HH:mm - tecnico: nome]`). |
| `data_abertura`| DATETIME | DEFAULT CURRENT_TIMESTAMP | Quando a OS foi formulada no sistema. |
| `data_previsao`| DATE | NULL | Data promessa dada para o cliente sobre entrega do reparo ou diagnÃ³stico. |
| `data_conclusao`| DATETIME | NULL | O momento exato em que a OS recebe status de 'pronto'. |
| `data_entrega` | DATETIME | NULL | Data de fechamento e devoluÃ§Ã£o ao dono (status entregue). |
| `valor_mao_obra`| DECIMAL(10,2)| DEFAULT 0 | Capital monetÃ¡rio faturado de esforÃ§o (trabalho braÃ§al/intelectual). |
| `valor_pecas` | DECIMAL(10,2)| DEFAULT 0 | SomatÃ³rio de componentes investidos vindos da aba Item_OS consumindo material fÃ­sico reposto. |
| `valor_total` | DECIMAL(10,2)| DEFAULT 0 | Total estritamente gerado de peÃ§as e serviÃ§os antes disto de Desconto Comercial ser operado. |
| `desconto` | DECIMAL(10,2)| DEFAULT 0 | Quantidade abatida no total bruto da OS por cortesia ou negociaÃ§Ã£o com cliente. |
| `valor_final` | DECIMAL(10,2)| DEFAULT 0 | Resultado efetivo matemÃ¡tico `((valor_mao_obra + valor_pecas) - desconto)` a ser pago pelo cliente. |
| `orcamento_aprovado`| TINYINT(1)| DEFAULT 0 | Sinaliza que o Cliente liberou o aceite sobre preÃ§os e proposta do serviÃ§o. |
| `data_aprovacao`| DATETIME | NULL | Data referente liberaÃ§Ã£o oficial da continuidade descrita acima. |
| `orcamento_pdf`| VARCHAR(255) | NULL | Identificador do PDF do comprovante impresso se o caso gerar. |
| `garantia_dias`| INT | DEFAULT 90 | Tempo fornecido de proteÃ§Ã£o e seguranÃ§a apÃ³s o fechamento tÃ©cnico e devoluÃ§Ã£o. |
| `garantia_validade`| DATE| NULL | Vencimento da isenÃ§Ã£o cobrÃ¡vel sobre o serviÃ§o computado automaticamente baseado nos dias e na Data_Entrega. |
| `observacoes_internas`|TEXT| NULL | AnotaÃ§Ãµes secretas exclusivas do Time nÃ£o listada em PDFs pro cliente ver, por integridade da bancada. |
| `observacoes_cliente`| TEXT| NULL | Mensagens que sairÃ£o destacadas pro consertante via comprovantes impressos. |
| `created_at` | DATETIME | DEFAULT CURRENT_TIMESTAMP | Registrado internamente. |
| `updated_at` | DATETIME | ON UPDATE CURRENT_TIMESTAMP | Momento final da Ãºltima modificaÃ§Ã£o estrutural. |

**Ãndices SecundÃ¡rios:** `idx_numero` (numero_os), `idx_status` (status), `idx_cliente` (cliente_id), `idx_tecnico` (tecnico_id)

---

## 6. os_itens
Lista com granularidade individual o somatÃ³rio de intervenÃ§Ãµes e componentes lanÃ§ados na Ordem de ServiÃ§o, construindo assim uma cesta de faturamento discriminada (serviÃ§os soltos vs produtos).

| Campo | Tipo | RestriÃ§Ãµes | DescriÃ§Ã£o |
|-------|------|------------|-----------|
| `id` | INT | PK, AUTO_INCREMENT | Identificador do item individual lanÃ§ado. |
| `os_id` | INT | FK -> os(id) NOT NULL | Vinculo pai da Ordem. ON DELETE CASCADE destrÃ³i-se caso a OS for apagada. |
| `tipo` | ENUM | NOT NULL | Designa se o faturamento consiste em 'servico' ou 'peca'. |
| `descricao` | VARCHAR(255) | NOT NULL | NarraÃ§Ã£o legÃ­vel do conserto feito com preÃ§o em contrato anexado. |
| `quantidade` | INT | DEFAULT 1 | Multiplicador de consumo do item em questÃ£o. |
| `valor_unitario`| DECIMAL(10,2)| NOT NULL | Unidade base faturada/sugerida para calculo. |
| `valor_total` | DECIMAL(10,2)| NOT NULL | SubtraÃ§Ã£o prÃ© computada do valor vs quantidade pra cÃ¡lculo da UI. |
| `peca_id` | INT | FK -> pecas(id) NULL | VÃ­nculo fÃ­sico real com o estoque se o Tipo nÃ£o for serviÃ§o abstrato. ON DELETE SET NULL protege a rastreabilidade prÃ©via de caixas operados retroativamente caso o produto mestre seja varrido do catÃ¡logo. |
| `created_at` | DATETIME | DEFAULT CURRENT_TIMESTAMP | Tempo logado de entrada em bancada. |

**Ãndices SecundÃ¡rios:** `idx_os` (os_id)

---

## 7. movimentacoes
Tabela de Kardex (Log de Movimento/DiÃ¡rio) que atesta o ciclo de vida rigoroso de saÃ­das e entradas de caixas ou unidades em PeÃ§as de Estoque, criando um histÃ³rico audÃ­vel de tudo que alterou seu volume com ou sem atrelar-se a uma Ordem.

| Campo | Tipo | RestriÃ§Ãµes | DescriÃ§Ã£o |
|-------|------|------------|-----------|
| `id` | INT | PK, AUTO_INCREMENT | Evento de Log Identificador. |
| `peca_id` | INT | FK -> pecas(id) NOT NULL | O SKU mestre balanÃ§ado do fluxo. ON DELETE CASCADE. |
| `os_id` | INT | FK -> os(id) NULL | Quando justificada a movimentaÃ§Ã£o decorrente e rastreada a Consumo TÃ©cnico. |
| `tipo` | ENUM | NOT NULL | Categoria da AlteraÃ§Ã£o: 'entrada' (lote novo de compras), 'saida' (consumo externo e OS) ou 'ajuste' (erro contÃ¡bil, furto documentado perdas e correÃ§Ã£o manual). |
| `quantidade` | INT | NOT NULL | Carga lanÃ§ada somada ou subtraÃ­da sobre a reserva atual absoluta. |
| `motivo` | VARCHAR(255) | NULL | Justificativa legÃ­vel exigida com frequÃªncia durante auditorias retroativas. |
| `responsavel_id`| INT | FK -> usuarios(id) NOT NULL| Integridade que traÃ§a quem foi o dedo logado ao efetuar o salvamento computado deste saldo. |
| `created_at` | DATETIME | DEFAULT CURRENT_TIMESTAMP | Tempo inalterÃ¡vel de ateste da entrada sistÃªmica provando tempo/local. |

**Ãndices SecundÃ¡rios:** `idx_peca` (peca_id)

---

## 8. financeiro
Nota da release `2.17.0`:
- a tabela `financeiro` passou a carregar classificacao para `DRE` e `fluxo de caixa`;
- migration: `2026-05-31-120000_AddGerencialFieldsToFinanceiro.php`.
- novos campos:
  - `data_competencia`
  - `origem_tipo`
  - `origem_id`
  - `grupo_dre`
  - `subgrupo_dre`
  - `impacta_dre`
  - `impacta_fluxo_caixa`
Nota da release `2.19.1`:
- a tabela `financeiro` recebeu `fornecedor_id` para vincular despesas ao cadastro oficial de `fornecedores`;
- migration: `2026-06-01-163000_AddFornecedorToFinanceiro.php`.
Nota de comportamento da release `2.19.5`:
- `origem_tipo` passou a ser definida automaticamente pelo backend com base em `tipo`, `os_id` e `categoria`;
- o fallback automatico de `data_competencia` passa a priorizar `data_vencimento`; em receitas de `OS`, a regra continua priorizando `os.data_entrega`.
Guia mestra de Caixa de Entradas, devedores e lanÃ§amentos da saÃºde financeira da assistÃªncia. Contas a Receber, faturamentos oriundos de OS's fechadas ou Pagamentos do dia a dia da despesa comercial.

| Campo | Tipo | RestriÃ§Ãµes | DescriÃ§Ã£o |
|-------|------|------------|-----------|
| `id` | INT | PK, AUTO_INCREMENT | LanÃ§amento PrimÃ¡rio Identificador ContÃ¡bil. |
| `os_id` | INT | FK -> os(id) NULL | Link dinÃ¢mico associado caso a Receita provenha genuinamente de Fechamento de AssistÃªncia final de Cliente sem destruir integridade. |
| `tipo` | ENUM | NOT NULL | Sentido da moeda ('receber', 'pagar'). |
| `categoria` | VARCHAR(50) | NOT NULL | Macro tag agrupada e hierarquizada (ex: Fornecedores, Impostos, Recebimento). |
| `descricao` | VARCHAR(255) | NOT NULL | Breve extrato descritivo manual preenchÃ­vel justificando fatura. |
| `valor` | DECIMAL(10,2)| NOT NULL | Quantidade nominal monetÃ¡ria absoluta real de compromisso com impostos ou acertos brutos prÃ© liquidaÃ§Ã£o final do boleto/dinheiro. |
| `forma_pagamento`| ENUM | NULL | Operador final de transaÃ§Ã£o em caso de resoluÃ§Ã£o. ('dinheiro', 'cartao_credito', 'cartao_debito', 'pix', 'boleto', 'transferencia'). |
| `status` | ENUM | DEFAULT 'pendente' | PosiÃ§Ã£o Contratual ou ContÃ¡bil frente Ã  prestadora confirmando caixa (pendente, pago, cancelado) que engaja/cancela a meta ou balanÃ§o mensal real. |
| `data_vencimento`| DATE | NOT NULL | Agenda contÃ¡bil imposta no vencimento do evento. Usada pra cobrar inadimplentes ou gerir fluxo. |
| `data_pagamento`| DATE | NULL | Em que balanÃ§o do ano entrou ou saiu exatamente do cofre da oficina. |
| `observacoes` | TEXT | NULL | Assinaturas, chaves de transaÃ§Ã£o externas pra provar remuneraÃ§Ã£o de terceiros etc. |
| `created_at` | DATETIME | DEFAULT CURRENT_TIMESTAMP | Tempo de documentaÃ§Ã£o. |
| `updated_at` | DATETIME | ON UPDATE CURRENT_TIMESTAMP | Tempo contendo a data da Ãºltima operaÃ§Ã£o de quitaÃ§Ã£o que editou este recurso no cofre ou no painel de contas. |

**Ãndices SecundÃ¡rios:** `idx_tipo` (tipo), `idx_status` (status), `idx_vencimento` (data_vencimento)

---

## 9. fotos
Permite hospedar visualmente laudos ou provas tangÃ­veis e fotogrÃ¡ficas durante entrada das mÃ¡quinas quebradas dos clientes na empresa e pÃ³s envio dos consertos efetuados Ã  banco de entrega e seguranÃ§a corporativa perante indenizaÃ§Ãµes (estado antes vs depois).

| Campo | Tipo | RestriÃ§Ãµes | DescriÃ§Ã£o |
|-------|------|------------|-----------|
| `id` | INT | PK, AUTO_INCREMENT | Identificador da Imagem de Laudo enviada. |
| `os_id` | INT | FK -> os(id) NOT NULL| Anexo e aglutinaÃ§Ã£o na OS especÃ­fica sob investigaÃ§Ã£o ou reparo providencialmente ligada de ponta a ponta que cai sumariamente quando Ordem some. |
| `tipo` | ENUM | NOT NULL | Sentido e momento da Fotografia documentÃ¡ria periciada na assistÃªncia ('recepcao' Entrada Bruta, 'reparo' Placa Aberta Durante SoluÃ§Ã£o, 'conclusao' VerificaÃ§Ã£o de Brilhos e montagem Fechamento Exito/Fracasso). |
| `arquivo` | VARCHAR(255) | NOT NULL | Nome e localizaÃ§Ã£o local ou absoluta de URI salva via FileStorage ou Blob local persistente apontado no diretÃ³rio web seguro com nomes sanitizados criptogrÃ¡ficos de diretÃ³rio fÃ­sico ou cloud do backend. |
| `descricao` | VARCHAR(255) | NULL | Breve extrato descritivo manual preenchÃ­vel justificando fatura ou local fotocopiado do Equipamento. |
| `created_at` | DATETIME | DEFAULT CURRENT_TIMESTAMP | Tempo de envio. |

---

## 10. logs
Tabela de monitoraÃ§Ã£o estrita que protege contra acidentes com usuÃ¡rios deletando dados sem rastreamento ou visualizando informaÃ§Ãµes protegidas garantindo governanÃ§a (Audit Trail ou Registros Globais).

| Campo | Tipo | RestriÃ§Ãµes | DescriÃ§Ã£o |
|-------|------|------------|-----------|
| `id` | INT | PK, AUTO_INCREMENT | Registrado Identificador. |
| `usuario_id` | INT | FK -> usuarios(id) NULL | Qual dos operadores de bancada fez a tratativa de Log registrada por requisiÃ§Ã£o autenticada, que se mantÃ©m ON DELETE SET NULL a fim de nÃ£o destruir pistas ou histÃ³ricos se funcionÃ¡rio sai demitido. |
| `acao` | VARCHAR(50) | NOT NULL | IdentificaÃ§Ã£o em constante do sistema, como `login`, `logout`, `os_delete`. |
| `descricao` | TEXT | NULL | Humanizado com contexto dinÃ¢mico concatenando chaves de IDs da operaÃ§Ã£o e contexto para debug simples em formato de visualizaÃ§Ã£o nÃ£o restritivo humano (ex: "Criou Equipamento Apple Mac de Marcos"). |
| `ip` | VARCHAR(45) | NULL | Remetente Protocolar. IP de origem de acesso Ã  sessÃ£o por onde o ato foi provado originÃ¡rio de ataque ou origem segura de login autoritÃ¡rio de ambiente fixo e documentado de Firewall. |
| `user_agent` | TEXT | NULL | Aparelho do FuncionÃ¡rio / Dispositivo que atestou Log (SO, Browser). |
| `created_at` | DATETIME | DEFAULT CURRENT_TIMESTAMP | Acontecimento documentado por data da requisiÃ§Ã£o base do Framework CodeIgniter Global App Timestamp Database Injection no timezone providenciado pelo framework. |

**Ãndices SecundÃ¡rios:** `idx_usuario` (usuario_id), `idx_acao` (acao)

---

## 11. configuracoes
Tabela centralizada de chave-valor contendo flags dinÃ¢micas, customizaÃ§Ã£o da filial operante (dados do boleto) e opÃ§Ãµes persistentes da empresa que o painel Front-End permite que administradores leigos alterem sem redeploys do Software de ProduÃ§Ã£o.

| Campo | Tipo | RestriÃ§Ãµes | DescriÃ§Ã£o |
|-------|------|------------|-----------|
| `id` | INT | PK, AUTO_INCREMENT | Sequencial nativo. |
| `chave` | VARCHAR(100) | UNIQUE, NOT NULL | Namespace constante exigido das Query Builders (ex: `empresa_nome`, `smtp_host`, `whatsapp_token`, `tema`). |
| `valor` | TEXT | NULL | ExpressÃ£o absoluta que serve o dado consumido internamente nos escopos e helpers de view dependentes dos modelos globais base do ecossistema. |
| `tipo` | VARCHAR(20) | DEFAULT 'texto' | Tratativa do dado na inserÃ§Ã£o do input no gerador e sanitizador Form dinÃ¢mico administrativo e do Core (`numero`, `texto`, `json`). |
| `created_at` | DATETIME | DEFAULT CURRENT_TIMESTAMP | Tempo de criaÃ§Ã£o inicial com Setup Defaults Scripted Initial Release Base (Seeders). |
| `updated_at` | DATETIME | ON UPDATE CURRENT_TIMESTAMP | Sinal emitido informando que alguma Chave trocou seu `valor` atual desde Ãºltimo Setup garantidor na hora do salvamento manual. |

---

## 12. fornecedores
Cadastro de parceiros B2B e Pessoas JurÃ­dicas/FÃ­sicas que fornecem suprimentos, ferramentas e insumos ou prestam serviÃ§os Ã  assistÃªncia tÃ©cnica.

| Campo | Tipo | RestriÃ§Ãµes | DescriÃ§Ã£o |
|-------|------|------------|-----------|
| `id` | INT | PK, AUTO_INCREMENT | Identificador principal do sistema. |
| `tipo_pessoa` | ENUM | DEFAULT 'juridica' | Define se Ã© 'juridica' (padrÃ£o) ou 'fisica'. |
| `nome_fantasia`| VARCHAR(100)| NOT NULL | Nome curto comercial e amigÃ¡vel. |
| `razao_social`| VARCHAR(100)| NULL | Nome legal completo se for Pessoa JurÃ­dica. |
| `cnpj_cpf` | VARCHAR(20) | UNIQUE, NULL | Cadastro de identificaÃ§Ã£o federal e logÃ­stico para emissÃ£o de nota. |
| `ie_rg` | VARCHAR(20) | NULL | InscriÃ§Ã£o Estadual ou RG. |
| `email` | VARCHAR(100)| NULL | E-mail de negociaÃ§Ã£o com vendedor B2B ou NFs. |
| `telefone1` | VARCHAR(20) | NOT NULL | Telefone base. |
| `telefone2` | VARCHAR(20) | NULL | Aparelho secundÃ¡rio. |
| `cep` | VARCHAR(10) | NULL | EndereÃ§o Postal padrÃ£o do IBGE de faturamento de transportadora. |
| `endereco` | VARCHAR(100)| NULL | Rua / Avenida de faturamento / entrega. |
| `numero` | VARCHAR(10) | NULL | NÃºmero de porta. |
| `complemento` | VARCHAR(50) | NULL | Sala, andar, prÃ©dio. |
| `bairro` | VARCHAR(50) | NULL | RegiÃ£o/Distrito de despacho. |
| `cidade` | VARCHAR(50) | NULL | Cidade. |
| `uf` | CHAR(2) | NULL | Estado de FederaÃ§Ã£o (SP, RJ, MG). |
| `observacoes` | TEXT | NULL | CatÃ¡logos de links, apontamentos informais de contatos. |
| `ativo` | TINYINT(1) | DEFAULT 1 | Marca fornecedores como inativos (sem destruir vendas/NFs antigas). |
| `created_at` | DATETIME | DEFAULT CURRENT_TIMESTAMP | InserÃ§Ã£o. |
| `updated_at` | DATETIME | ON UPDATE CURRENT_TIMESTAMP | Ãšltimo update. |

---

## 13. funcionarios
Cadastro individual de forÃ§a de trabalho tÃ©cnica e administrativa, com gestÃ£o de cargos, salÃ¡rios de operaÃ§Ã£o e dats operacionais RH da empresa.

| Campo | Tipo | RestriÃ§Ãµes | DescriÃ§Ã£o |
|-------|------|------------|-----------|
| `id` | INT | PK, AUTO_INCREMENT | Identificador de contrataÃ§Ã£o. |
| `nome` | VARCHAR(100)| NOT NULL | Nome humano limpo. |
| `cpf` | VARCHAR(20) | UNIQUE, NOT NULL| Cadastro de Pessoa FÃ­sica obrigatÃ³rio governamentalmente. |
| `rg` | VARCHAR(20) | NULL | Documento local Estadual emitente. |
| `data_nascimento`| DATE | NULL | Auxilio pra lembretes ou validaÃ§Ã£o legal RH. |
| `cargo` | VARCHAR(50) | NULL | FunÃ§Ã£o contratual (TÃ©cnico de Placa, Atendente Web, Gerente, Auxiliar). |
| `salario` | DECIMAL(10,2)| NULL | Registros de salÃ¡rio inicial Base ou fixo pra fechamento do mÃªs (Despesas RH). |
| `data_admissao`| DATE | NULL | InÃ­cio oficial do labor. |
| `data_demissao`| DATE | NULL | Termino histÃ³rico laborativo (bloqueia crachÃ¡s/logins futuros). |
| `email` | VARCHAR(100)| NULL | Pessoal ou Empresarial de recebimento de PONTO EletrÃ´nico/NotificaÃ§Ã£o. |
| `telefone` | VARCHAR(20) | NOT NULL | Celular WhatsApp direto em caso de chamadas tÃ¡ticas in company. |
| `cep` | VARCHAR(10) | NULL | EndereÃ§amento familiar para emergÃªncias ou correspondÃªncias RH. |
| `endereco` | VARCHAR(100)| NULL | Rua / Avenida do domicÃ­lio residencial. |
| `numero` | VARCHAR(10) | NULL | NÃºmero fÃ­sico (Casa/Porta apt.). |
| `complemento` | VARCHAR(50) | NULL | Bloco, Apartamento etc. |
| `bairro` | VARCHAR(50) | NULL | Setor Habitacional de moradia. |
| `cidade` | VARCHAR(50) | NULL | Origem municipal. |
| `uf` | CHAR(2) | NULL | Estado/FederaÃ§Ã£o base residencial. |
| `observacoes` | TEXT | NULL | HistÃ³rico informal sobre fÃ©rias, acordos verbais prÃ©vios e atestados. |
| `ativo` | TINYINT(1) | DEFAULT 1 | Flag (1/0) de se funcionÃ¡rio ainda faz quadro atual. |
| `created_at` | DATETIME | DEFAULT CURRENT_TIMESTAMP | Input da ficha funcional. |
| `updated_at` | DATETIME | ON UPDATE CURRENT_TIMESTAMP | AtualizaÃ§Ã£o em alteraÃ§Ãµes de salÃ¡rios/EndereÃ§os etc. |

---

## 14. equipamento_clientes
Tabela-pivÃ´ de relacionamento Muito-para-Muitos (N:N) projetada para associar um mesmo equipamento (que pertence a um Ãºnico dono ou base empresarial primÃ¡ria da assistÃªncia) a outros mÃºltiplos Clientes secundÃ¡rios no momento da formulaÃ§Ã£o da OS (como um parente levando o celular, ou outro sÃ³cio da mesma infraestrutura que utiliza a mÃ¡quina).

Atualizacao operacional 05/06/2026:

- o fluxo deixou de ser apenas "preparado para futuro" e passou a ser usado ativamente quando `numero de serie`, `MAC` ou `IMEI` identificam o mesmo equipamento para clientes diferentes;
- a relacao `(equipamento_id, cliente_id)` agora deve ser unica, impedindo duplicidade de vinculos para o mesmo equipamento.

| Campo | Tipo | RestriÃ§Ãµes | DescriÃ§Ã£o |
|-------|------|------------|-----------|
| `id` | INT | PK, AUTO_INCREMENT | Identificador gerencial interno. |
| `equipamento_id` | INT | FK -> equipamentos(id) NOT NULL| ReferÃªncia obrigatÃ³ria ao equipamento rastreado. ON DELETE CASCADE. |
| `cliente_id` | INT | FK -> clientes(id) NOT NULL| ReferÃªncia obrigatÃ³ria do sub-cliente vinculado Ã  mÃ¡quina secundÃ¡ria. ON DELETE CASCADE. |
| `created_at` | DATETIME | DEFAULT CURRENT_TIMESTAMP | Tempo em que a relaÃ§Ã£o foi aceita e vinculada no painel administrativo pela primeira vez. |

**Indice/Regra operacional:** `ux_equipamento_clientes_relacao` em `(equipamento_id, cliente_id)`

---

## 15. equipamentos_tipos
Tabela de categorias de equipamentos (ex: Celular, Notebook, Desktop).

| Campo | Tipo | RestriÃ§Ãµes | DescriÃ§Ã£o |
|-------|------|------------|-----------|
| `id` | INT | PK, AUTO_INCREMENT | Identificador do tipo. |
| `nome` | VARCHAR(100) | UNIQUE, NOT NULL | Nome do tipo de equipamento. |
| `ativo` | TINYINT(1) | DEFAULT 1 | Status de atividade. |
| `created_at` | DATETIME | NULL | Data de criaÃ§Ã£o. |
| `updated_at` | DATETIME | NULL | Data de atualizaÃ§Ã£o. |

---

## 16. equipamentos_marcas
Tabela de fabricantes de equipamentos (ex: Samsung, Apple, Dell).

| Campo | Tipo | RestriÃ§Ãµes | DescriÃ§Ã£o |
|-------|------|------------|-----------|
| `id` | INT | PK, AUTO_INCREMENT | Identificador da marca. |
| `nome` | VARCHAR(100) | UNIQUE, NOT NULL | Nome do fabricante. |
| `ativo` | TINYINT(1) | DEFAULT 1 | Status de atividade. |
| `created_at` | DATETIME | NULL | Data de criaÃ§Ã£o. |
| `updated_at` | DATETIME | NULL | Data de atualizaÃ§Ã£o. |

---

## 17. equipamentos_modelos
Tabela de modelos especÃ­ficos vinculados a uma marca.

| Campo | Tipo | RestriÃ§Ãµes | DescriÃ§Ã£o |
|-------|------|------------|-----------|
| `id` | INT | PK, AUTO_INCREMENT | Identificador do modelo. |
| `marca_id` | INT | FK -> equipamentos_marcas(id) | Marca vinculada. |
| `nome` | VARCHAR(100) | NOT NULL | Nome do modelo. |
| `ativo` | TINYINT(1) | DEFAULT 1 | Status de atividade. |
| `created_at` | DATETIME | NULL | Data de criaÃ§Ã£o. |
| `updated_at` | DATETIME | NULL | Data de atualizaÃ§Ã£o. |

---

## 18. equipamentos_defeitos
CatÃ¡logo de defeitos comuns por tipo de equipamento e classificaÃ§Ã£o (Hardware/Software).

| Campo | Tipo | RestriÃ§Ãµes | DescriÃ§Ã£o |
|-------|------|------------|-----------|
| `id` | INT | PK, AUTO_INCREMENT | Identificador do defeito. |
| `nome` | VARCHAR(150) | NOT NULL | TÃ­tulo do defeito (ex: "NÃ£o liga"). |
| `tipo_id` | INT | FK -> equipamentos_tipos(id) | Tipo de equipamento associado. |
| `classificacao` | ENUM | NOT NULL | 'hardware' ou 'software'. |
| `descricao` | TEXT | NULL | DescriÃ§Ã£o detalhada do sintoma. |
| `ativo` | TINYINT(1) | DEFAULT 1 | Status de atividade. |
| `created_at` | DATETIME | NULL | Data de criaÃ§Ã£o. |
| `updated_at` | DATETIME | NULL | Data de atualizaÃ§Ã£o. |

---

## 19. equipamento_defeito_procedimentos
Base de Conhecimento TÃ©cnica: Passos/Procedimentos operacionais para resolver cada defeito.

| Campo | Tipo | RestriÃ§Ãµes | DescriÃ§Ã£o |
|-------|------|------------|-----------|
| `id` | INT | PK, AUTO_INCREMENT | Identificador do procedimento. |
| `defeito_id` | INT | FK -> equipamentos_defeitos(id) | Defeito vinculado. |
| `descricao` | VARCHAR(255) | NOT NULL | DescriÃ§Ã£o do passo tÃ©cnico. |
| `ordem` | INT(5) | NOT NULL | Ordem de execuÃ§Ã£o do passo. |
| `created_at` | DATETIME | NULL | Data de criaÃ§Ã£o. |
| `updated_at` | DATETIME | NULL | Data de atualizaÃ§Ã£o. |

---

## 20. os_defeitos
Tabela pivÃ´ que vincula defeitos comuns a uma Ordem de ServiÃ§o especÃ­fica.

| Campo | Tipo | RestriÃ§Ãµes | DescriÃ§Ã£o |
|-------|------|------------|-----------|
| `id` | INT | PK, AUTO_INCREMENT | Identificador do vÃ­nculo. |
| `os_id` | INT | FK -> os(id) | Ordem de ServiÃ§o vinculada. |
| `defeito_id` | INT | FK -> equipamentos_defeitos(id) | Defeito vinculado. |
| `created_at` | DATETIME | NULL | Data de registro. |

---

## 21. acessorios_os
Armazena os acessÃ³rios especÃ­ficos que acompanham o equipamento em uma determinada OS.

| Campo | Tipo | RestriÃ§Ãµes | DescriÃ§Ã£o |
|-------|------|------------|-----------|
| `id` | INT | PK, AUTO_INCREMENT | Identificador do acessÃ³rio na OS. |
| `os_id` | INT | FK -> os(id) | OS vinculada. |
| `descricao` | VARCHAR(255) | NOT NULL | DescriÃ§Ã£o do acessÃ³rio (ex: "Carregador original"). |
| `created_at` | DATETIME | NULL | Data de registro. |

---

## 22. fotos_acessorios
Fotos vinculadas a cada acessÃ³rio recebido na OS.

| Campo | Tipo | RestriÃ§Ãµes | DescriÃ§Ã£o |
|-------|------|------------|-----------|
| `id` | INT | PK, AUTO_INCREMENT | Identificador da foto. |
| `acessorio_id` | INT | FK -> acessorios_os(id) | AcessÃ³rio vinculado. |
| `arquivo` | VARCHAR(255) | NOT NULL | Caminho do arquivo da imagem. |
| `created_at` | DATETIME | NULL | Data de registro. |

---

## 23. estado_fisico_equipamento
Registro detalhado de anomalias ou estado fÃ­sico do equipamento no momento da entrada.

| Campo | Tipo | RestriÃ§Ãµes | DescriÃ§Ã£o |
|-------|------|------------|-----------|
| `id` | INT | PK, AUTO_INCREMENT | Identificador do registro. |
| `os_id` | INT | FK -> os(id) | OS vinculada. |
| `descricao_dano` | VARCHAR(255) | NOT NULL | DescriÃ§Ã£o da avaria (ex: "Tela riscada"). |
| `created_at` | DATETIME | NULL | Data de registro. |

---

## 24. estado_fisico_fotos
Fotos que comprovam o estado fÃ­sico relatado.

| Campo | Tipo | RestriÃ§Ãµes | DescriÃ§Ã£o |
|-------|------|------------|-----------|
| `id` | INT | PK, AUTO_INCREMENT | Identificador da foto. |
| `estado_fisico_id` | INT | FK -> estado_fisico_equipamento(id) | Registro de estado fÃ­sico vinculado. |
| `arquivo` | VARCHAR(255) | NOT NULL | Caminho do arquivo da imagem. |
| `created_at` | DATETIME | NULL | Data de registro. |

---

## OtimizaÃ§Ã£o para Busca Global

A funcionalidade de **Busca Global** na navbar utiliza consultas `LIKE` em diversas tabelas. Para garantir o desempenho, o sistema depende dos seguintes Ã­ndices:

- **Tabela `clientes`**: `idx_nome`, `idx_cpf`, `idx_telefone`.
- **Tabela `os`**: `idx_numero`, `idx_status`, `idx_cliente`.
- **Tabela `equipamentos`**: `numero_serie`, `imei` e vÃ­nculos com `clientes`.
- **Tabela `mensagens_whatsapp`**: Pesquisas por conteÃºdo de mensagem e telefone.

Recomenda--se monitorar o crescimento destas tabelas para avaliar a necessidade de Ã­ndices `FULLTEXT` em campos de `texto` (como `os.relato_cliente` ou `mensagens_whatsapp.mensagem`) caso o volume de dados ultrapasse 1 milhÃ£o de registros.

---

## 25. monitor_agents
Tabela de estado atual do agente de bancada vinculado a uma OS/equipamento.

| Campo | Tipo | Restricoes | Descricao |
|-------|------|------------|-----------|
| `id` | INT | PK, AUTO_INCREMENT | Identificador interno do agente. |
| `agent_uuid` | VARCHAR | UNIQUE | Identificador logico do agente provisionado. |
| `installation_id` | VARCHAR | NOT NULL | Identificador local da instalacao na maquina. |
| `usuario_id` | INT | FK -> usuarios(id) | Usuario do ERP usado no provisionamento. |
| `cliente_id` | INT | FK -> clientes(id) | Cliente vinculado pela OS. |
| `equipamento_id` | INT | FK -> equipamentos(id) | Equipamento complementado pelo agente. |
| `os_id` | INT | FK -> os(id) | OS usada no bootstrap do agente. |
| `numero_os` | VARCHAR | NULL | Numero da OS para rastreabilidade. |
| `device_type` | VARCHAR | NULL | Classificacao geral (`desktop` ou `notebook`). |
| `chassis_type` | VARCHAR | NULL | Tipo de chassi/gabinete detectado. |
| `motherboard` | VARCHAR | NULL | Modelo da placa-mae. |
| `chipset` | VARCHAR | NULL | Chipset inferido ou detectado. |
| `cpu` | VARCHAR | NULL | Processador atual. |
| `gpu` | VARCHAR | NULL | GPU ou placa de video. |
| `storage_summary` | VARCHAR | NULL | Resumo do armazenamento detectado. |
| `ram_gb` | DECIMAL | NULL | Quantidade de RAM em GB. |
| `ultimo_checkin_em` | DATETIME | NULL | Ultimo check-in recebido. |
| `ultimo_snapshot_em` | DATETIME | NULL | Momento tecnico da ultima coleta. |

---

## 26. monitor_agent_snapshots
Historico bruto de payloads enviados pelo agente em cada check-in.

| Campo | Tipo | Restricoes | Descricao |
|-------|------|------------|-----------|
| `id` | INT | PK, AUTO_INCREMENT | Identificador do snapshot. |
| `agent_id` | INT | FK -> monitor_agents(id) | Agente origem do snapshot. |
| `payload_json` | LONGTEXT/TEXT | NOT NULL | Payload completo do inventario recebido. |
| `hostname` | VARCHAR | NULL | Hostname no momento da coleta. |
| `serial_number` | VARCHAR | NULL | Serie reportada no snapshot. |
| `collected_at` | DATETIME | NULL | Momento de coleta informado pela maquina. |
| `received_at` | DATETIME | NULL | Momento em que o ERP recebeu o payload. |

Uso operacional:
- o snapshot preserva o inventario bruto para auditoria;
- `monitor_agents` guarda o ultimo estado resumido;
- `equipamentos` recebe o reflexo tecnico util para OS, PDF e exibicao do cadastro.

---

*Gerado automaticamente pelo Assistente baseando-se no schema real e atualizado do sistema.*
