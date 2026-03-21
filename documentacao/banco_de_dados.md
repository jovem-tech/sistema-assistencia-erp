# Documentação do Banco de Dados - AssistTech

**Banco de Dados:** `assistencia_tecnica`
**Character Set:** `utf8mb4`
**Collation:** `utf8mb4_unicode_ci`

Esta documentação descreve a estrutura completa das tabelas do sistema de Assistência Técnica (AssistTech), incluindo relacionamentos, chaves primárias e estrangeiras, bem como as restrições e finalidades de cada tabela e campo.

> **Nota:** O schema evoluiu com RBAC e com a separação de Tipos/Marcas/Modelos.  
> Se você importou `database.sql`, execute `update_equip_db.php` e `setup_rbac.php`.  
> Para um resumo atualizado, consulte `documentacao/04-banco-de-dados/tabelas-principais.md`.
>
> **Atualizacao 20/03/2026 (modulo WhatsApp unificado):**
> - O legado Whaticket foi removido do ERP.
> - Chaves legadas `whatsapp_whaticket_*` foram removidas da tabela `configuracoes`.
> - Providers legados `whaticket` foram normalizados para `api_whats_local`.
> - Migration de limpeza: `2026-03-20-060000_RemoveWhaticketLegacyModule.php`.

---

## Índice das Tabelas
1. [usuarios](#1-usuarios)
2. [clientes](#2-clientes)
3. [equipamentos](#3-equipamentos)
4. [pecas](#4-pecas)
5. [os (Ordens de Serviço)](#5-os-ordens-de-serviço)
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

---

## 1. usuarios
Armazena as informações dos usuários que possuem acesso ao sistema (Atendentes, Técnicos e Administradores).

| Campo | Tipo | Restrições | Descrição |
|-------|------|------------|-----------|
| `id` | INT | PK, AUTO_INCREMENT | Identificador único do usuário. |
| `nome` | VARCHAR(100) | NOT NULL | Nome completo do usuário. |
| `email` | VARCHAR(100) | UNIQUE, NOT NULL | E-mail de acesso e comunicação. |
| `senha` | VARCHAR(255) | NOT NULL | Hash da senha de acesso (bcrypt). |
| `telefone` | VARCHAR(20) | NULL | Telefone de contato. |
| `perfil` | ENUM | DEFAULT 'atendente' | Nível de acesso: 'admin', 'tecnico', 'atendente'. |
| `foto` | VARCHAR(255) | NULL | Nome do arquivo de imagem salvo do lado do servidor correspondente à foto de perfil do usuário. |
| `ativo` | TINYINT(1) | DEFAULT 1 | Define se o usuário tem permissão para logar (1 = Ativo, 0 = Inativo). |
| `ultimo_acesso`| DATETIME | NULL | Data e hora em que o usuário logou pela última vez. |
| `token_recuperacao`| VARCHAR(255)| NULL | Token utilizado para recuperar senha esquecida. |
| `token_expiracao`| DATETIME| NULL | Expiração do token de recuperação de senha. |
| `created_at` | DATETIME | DEFAULT CURRENT_TIMESTAMP | Data e hora do registro da conta. |
| `updated_at` | DATETIME | ON UPDATE CURRENT_TIMESTAMP | Data e hora da última alteração de qualquer dado deste registro. |

---

## 2. clientes
Armazena informações das Pessoas Físicas e Jurídicas para o registro de equipamentos e Ordens de Serviço.

| Campo | Tipo | Restrições | Descrição |
|-------|------|------------|-----------|
| `id` | INT | PK, AUTO_INCREMENT | Identificador único do cliente. |
| `tipo_pessoa` | ENUM | DEFAULT 'fisica' | Fisíca (CPF) ou Jurídica (CNPJ). |
| `nome_razao` | VARCHAR(100) | NOT NULL | Nome do Cliente Pessoa Física ou Razão Social de Pessoa Jurídica. |
| `cpf_cnpj` | VARCHAR(20) | UNIQUE, NULL | CPF ou CNPJ (utilizado na validação e na emissão de NFs/Orçamentos). |
| `rg_ie` | VARCHAR(20) | NULL | Registro Geral (Pessoa Física) ou Inscrição Estadual (Pessoa Jurídica). |
| `email` | VARCHAR(100) | NULL | E-mail do cliente (para receber orçamentos/OS). |
| `telefone1` | VARCHAR(20) | NOT NULL | Contato principal (geralmente WhatsApp/Telefone). |
| `telefone2` | VARCHAR(20) | NULL | Contato secundário. |
| `cep` | VARCHAR(10) | NULL | CEP para preenchimento de endereço. |
| `endereco` | VARCHAR(100) | NULL | Logradouro / Rua. |
| `numero` | VARCHAR(10) | NULL | Número do estabelecimento / residência. |
| `complemento`| VARCHAR(50) | NULL | Complemento do endereço. |
| `bairro` | VARCHAR(50) | NULL | Bairro. |
| `cidade` | VARCHAR(50) | NULL | Cidade. |
| `uf` | CHAR(2) | NULL | Estado (UF). |
| `observacoes` | TEXT | NULL | Notas adicionais sobre o cliente (ex: restrições). |
| `created_at` | DATETIME | DEFAULT CURRENT_TIMESTAMP | Data de cadastro. |
| `updated_at` | DATETIME | ON UPDATE CURRENT_TIMESTAMP | Última atualização do cadastro. |

**Índices Secundários:** `idx_nome` (nome_razao), `idx_cpf` (cpf_cnpj), `idx_telefone` (telefone1)

---

## 3. equipamentos
Armazena a descrição detalhada dos itens (aparelhos/dispositivos) trazidos pelo cliente para análise/reparo.

| Campo | Tipo | Restrições | Descrição |
|-------|------|------------|-----------|
| `id` | INT | PK, AUTO_INCREMENT | Identificador único do equipamento. |
| `cliente_id` | INT | FK -> clientes(id) NOT NULL | Vincula o equipamento ao seu dono (cliente). ON DELETE CASCADE. |
| `tipo` | ENUM | NOT NULL | Tipo físico ('notebook', 'desktop', 'celular', 'tablet', 'impressora', 'outros'). |
| `marca` | VARCHAR(50) | NOT NULL | Fabricante do equipamento (Ex: Dell, Samsung, Apple). |
| `modelo` | VARCHAR(100) | NOT NULL | Número do Modelo do equipamento. |
| `numero_serie`| VARCHAR(100) | NULL | Número de Série do fabricante para validação e garantia. |
| `imei` | VARCHAR(20) | NULL | Identificação Internacional de Equipamento Móvel (Exclusivo para aparelhos com SIM). |
| `senha_acesso`| VARCHAR(255) | NULL | Senha ou Pin necessário para ligar e testar o equipamento. |
| `estado_fisico`| TEXT | NULL | Conservação detalhada na hora da entrada (Arranhões, telas quebradas, etc). |
| `acessorios` | TEXT | NULL | O que veio junto (Carregador, bateria, cabos, bolsa). |
| `observacoes` | TEXT | NULL | Observações complementares sobre o item. |
| `created_at` | DATETIME | DEFAULT CURRENT_TIMESTAMP | Data de inclusão. |
| `updated_at` | DATETIME | ON UPDATE CURRENT_TIMESTAMP | Última alteração. |

**Índices Secundários:** `idx_cliente` (cliente_id)

---

## 4. pecas
Representam os produtos vendidos e componentes consumidos usados na Assistência Técnica (Estoque).

| Campo | Tipo | Restrições | Descrição |
|-------|------|------------|-----------|
| `id` | INT | PK, AUTO_INCREMENT | Identificador da peça/produto de estoque. |
| `codigo` | VARCHAR(50) | UNIQUE, NULL | Código de barras ou código interno (SKU). |
| `codigo_fabricante`| VARCHAR(100)| NULL | Part Number correspondente com os catálogos do fabricante da peça. |
| `nome` | VARCHAR(100) | NOT NULL | Descrição da Peça de reposição ou produto. |
| `categoria` | VARCHAR(50) | NULL | Categoria da peça. |
| `modelos_compativeis`| TEXT | NULL | Relação de equipamentos compatíveis. |
| `fornecedor` | VARCHAR(100) | NULL | Nome do último fornecedor (opcional, ou link para futura tabela). |
| `localizacao` | VARCHAR(50) | NULL | Corredor / Prateleira / Gaveta de alocação de estoque físico na assistência. |
| `preco_custo` | DECIMAL(10,2)| DEFAULT 0 | Custo do material adquirido. |
| `preco_venda` | DECIMAL(10,2)| DEFAULT 0 | Preço oferecido em prateleira e Ordens de Serviço (Orçamento). |
| `quantidade_atual`| INT | DEFAULT 0 | Saldo da Peça no estoque atual. |
| `estoque_minimo`| INT | DEFAULT 1 | Momento recomendável em que um alerta visual e no sistema avisa a necessidade de reposição. |
| `estoque_maximo`| INT | NULL | Capacidade de lotação aceitável para compras. |
| `foto` | VARCHAR(255) | NULL | Imagem ilustrativa do produto/peça. |
| `observacoes` | TEXT | NULL | Observações extras. |
| `ativo` | TINYINT(1) | DEFAULT 1 | Define se a peça ainda é comercializada. |
| `created_at` | DATETIME | DEFAULT CURRENT_TIMESTAMP | Cadastro da Peça. |
| `updated_at` | DATETIME | ON UPDATE CURRENT_TIMESTAMP | Última vez editada. |

**Índices Secundários:** `idx_nome` (nome), `idx_categoria` (categoria)

---

## 5. os (Ordens de Serviço)
É a tabela principal da operação de negócios, atrelando um Cliente, seu respectivo Equipamento, a tratativa de reparo prestada por um Técnico com datas e valores orçamentários/faturados.

| Campo | Tipo | Restrições | Descrição |
|-------|------|------------|-----------|
| `id` | INT | PK, AUTO_INCREMENT | Identificador primário da Ordem de Serviço. |
| `numero_os` | VARCHAR(20) | UNIQUE, NOT NULL | Número legível gerado internamente (Ex: OS-2026-0001) usado para exibição e busca. |
| `cliente_id` | INT | FK -> clientes(id) NOT NULL| Cliente solicitante do conserto. |
| `equipamento_id`| INT | FK -> equipamentos(id) NOT NULL| Objeto a ser consertado no contexto do registro. |
| `tecnico_id` | INT | FK -> usuarios(id) NULL| Usuário do sistema (técnico) incumbido do diagnóstico e reparos. Pode ser deixado em NULL. |
| `status` | ENUM | DEFAULT 'aguardando_analise' | Status em que se encontra o andamento do Workflow ('aguardando_analise', 'aguardando_orcamento', 'aguardando_aprovacao', 'aprovado', 'reprovado', 'em_reparo', 'aguardando_peca', 'pronto', 'entregue', 'cancelado'). |
| `prioridade` | ENUM | DEFAULT 'normal' | Urgência (baixa, normal, alta, urgente). |
| `relato_cliente`| TEXT | NOT NULL | O que o cliente declarou como queixa, sintoma ou pedido inicial de serviço. |
| `diagnostico_tecnico`| TEXT| NULL | Conclusão oficial do profissional perante a análise. |
| `solucao_aplicada`| TEXT | NULL | Procedimento técnico que foi adotado ou será prestado. |
| `data_abertura`| DATETIME | DEFAULT CURRENT_TIMESTAMP | Quando a OS foi formulada no sistema. |
| `data_previsao`| DATE | NULL | Data promessa dada para o cliente sobre entrega do reparo ou diagnóstico. |
| `data_conclusao`| DATETIME | NULL | O momento exato em que a OS recebe status de 'pronto'. |
| `data_entrega` | DATETIME | NULL | Data de fechamento e devolução ao dono (status entregue). |
| `valor_mao_obra`| DECIMAL(10,2)| DEFAULT 0 | Capital monetário faturado de esforço (trabalho braçal/intelectual). |
| `valor_pecas` | DECIMAL(10,2)| DEFAULT 0 | Somatório de componentes investidos vindos da aba Item_OS consumindo material físico reposto. |
| `valor_total` | DECIMAL(10,2)| DEFAULT 0 | Total estritamente gerado de peças e serviços antes disto de Desconto Comercial ser operado. |
| `desconto` | DECIMAL(10,2)| DEFAULT 0 | Quantidade abatida no total bruto da OS por cortesia ou negociação com cliente. |
| `valor_final` | DECIMAL(10,2)| DEFAULT 0 | Resultado efetivo matemático `((valor_mao_obra + valor_pecas) - desconto)` a ser pago pelo cliente. |
| `orcamento_aprovado`| TINYINT(1)| DEFAULT 0 | Sinaliza que o Cliente liberou o aceite sobre preços e proposta do serviço. |
| `data_aprovacao`| DATETIME | NULL | Data referente liberação oficial da continuidade descrita acima. |
| `orcamento_pdf`| VARCHAR(255) | NULL | Identificador do PDF do comprovante impresso se o caso gerar. |
| `garantia_dias`| INT | DEFAULT 90 | Tempo fornecido de proteção e segurança após o fechamento técnico e devolução. |
| `garantia_validade`| DATE| NULL | Vencimento da isenção cobrável sobre o serviço computado automaticamente baseado nos dias e na Data_Entrega. |
| `observacoes_internas`|TEXT| NULL | Anotações secretas exclusivas do Time não listada em PDFs pro cliente ver, por integridade da bancada. |
| `observacoes_cliente`| TEXT| NULL | Mensagens que sairão destacadas pro consertante via comprovantes impressos. |
| `created_at` | DATETIME | DEFAULT CURRENT_TIMESTAMP | Registrado internamente. |
| `updated_at` | DATETIME | ON UPDATE CURRENT_TIMESTAMP | Momento final da última modificação estrutural. |

**Índices Secundários:** `idx_numero` (numero_os), `idx_status` (status), `idx_cliente` (cliente_id), `idx_tecnico` (tecnico_id)

---

## 6. os_itens
Lista com granularidade individual o somatório de intervenções e componentes lançados na Ordem de Serviço, construindo assim uma cesta de faturamento discriminada (serviços soltos vs produtos).

| Campo | Tipo | Restrições | Descrição |
|-------|------|------------|-----------|
| `id` | INT | PK, AUTO_INCREMENT | Identificador do item individual lançado. |
| `os_id` | INT | FK -> os(id) NOT NULL | Vinculo pai da Ordem. ON DELETE CASCADE destrói-se caso a OS for apagada. |
| `tipo` | ENUM | NOT NULL | Designa se o faturamento consiste em 'servico' ou 'peca'. |
| `descricao` | VARCHAR(255) | NOT NULL | Narração legível do conserto feito com preço em contrato anexado. |
| `quantidade` | INT | DEFAULT 1 | Multiplicador de consumo do item em questão. |
| `valor_unitario`| DECIMAL(10,2)| NOT NULL | Unidade base faturada/sugerida para calculo. |
| `valor_total` | DECIMAL(10,2)| NOT NULL | Subtração pré computada do valor vs quantidade pra cálculo da UI. |
| `peca_id` | INT | FK -> pecas(id) NULL | Vínculo físico real com o estoque se o Tipo não for serviço abstrato. ON DELETE SET NULL protege a rastreabilidade prévia de caixas operados retroativamente caso o produto mestre seja varrido do catálogo. |
| `created_at` | DATETIME | DEFAULT CURRENT_TIMESTAMP | Tempo logado de entrada em bancada. |

**Índices Secundários:** `idx_os` (os_id)

---

## 7. movimentacoes
Tabela de Kardex (Log de Movimento/Diário) que atesta o ciclo de vida rigoroso de saídas e entradas de caixas ou unidades em Peças de Estoque, criando um histórico audível de tudo que alterou seu volume com ou sem atrelar-se a uma Ordem.

| Campo | Tipo | Restrições | Descrição |
|-------|------|------------|-----------|
| `id` | INT | PK, AUTO_INCREMENT | Evento de Log Identificador. |
| `peca_id` | INT | FK -> pecas(id) NOT NULL | O SKU mestre balançado do fluxo. ON DELETE CASCADE. |
| `os_id` | INT | FK -> os(id) NULL | Quando justificada a movimentação decorrente e rastreada a Consumo Técnico. |
| `tipo` | ENUM | NOT NULL | Categoria da Alteração: 'entrada' (lote novo de compras), 'saida' (consumo externo e OS) ou 'ajuste' (erro contábil, furto documentado perdas e correção manual). |
| `quantidade` | INT | NOT NULL | Carga lançada somada ou subtraída sobre a reserva atual absoluta. |
| `motivo` | VARCHAR(255) | NULL | Justificativa legível exigida com frequência durante auditorias retroativas. |
| `responsavel_id`| INT | FK -> usuarios(id) NOT NULL| Integridade que traça quem foi o dedo logado ao efetuar o salvamento computado deste saldo. |
| `created_at` | DATETIME | DEFAULT CURRENT_TIMESTAMP | Tempo inalterável de ateste da entrada sistêmica provando tempo/local. |

**Índices Secundários:** `idx_peca` (peca_id)

---

## 8. financeiro
Guia mestra de Caixa de Entradas, devedores e lançamentos da saúde financeira da assistência. Contas a Receber, faturamentos oriundos de OS's fechadas ou Pagamentos do dia a dia da despesa comercial.

| Campo | Tipo | Restrições | Descrição |
|-------|------|------------|-----------|
| `id` | INT | PK, AUTO_INCREMENT | Lançamento Primário Identificador Contábil. |
| `os_id` | INT | FK -> os(id) NULL | Link dinâmico associado caso a Receita provenha genuinamente de Fechamento de Assistência final de Cliente sem destruir integridade. |
| `tipo` | ENUM | NOT NULL | Sentido da moeda ('receber', 'pagar'). |
| `categoria` | VARCHAR(50) | NOT NULL | Macro tag agrupada e hierarquizada (ex: Fornecedores, Impostos, Recebimento). |
| `descricao` | VARCHAR(255) | NOT NULL | Breve extrato descritivo manual preenchível justificando fatura. |
| `valor` | DECIMAL(10,2)| NOT NULL | Quantidade nominal monetária absoluta real de compromisso com impostos ou acertos brutos pré liquidação final do boleto/dinheiro. |
| `forma_pagamento`| ENUM | NULL | Operador final de transação em caso de resolução. ('dinheiro', 'cartao_credito', 'cartao_debito', 'pix', 'boleto', 'transferencia'). |
| `status` | ENUM | DEFAULT 'pendente' | Posição Contratual ou Contábil frente à prestadora confirmando caixa (pendente, pago, cancelado) que engaja/cancela a meta ou balanço mensal real. |
| `data_vencimento`| DATE | NOT NULL | Agenda contábil imposta no vencimento do evento. Usada pra cobrar inadimplentes ou gerir fluxo. |
| `data_pagamento`| DATE | NULL | Em que balanço do ano entrou ou saiu exatamente do cofre da oficina. |
| `observacoes` | TEXT | NULL | Assinaturas, chaves de transação externas pra provar remuneração de terceiros etc. |
| `created_at` | DATETIME | DEFAULT CURRENT_TIMESTAMP | Tempo de documentação. |
| `updated_at` | DATETIME | ON UPDATE CURRENT_TIMESTAMP | Tempo contendo a data da última operação de quitação que editou este recurso no cofre ou no painel de contas. |

**Índices Secundários:** `idx_tipo` (tipo), `idx_status` (status), `idx_vencimento` (data_vencimento)

---

## 9. fotos
Permite hospedar visualmente laudos ou provas tangíveis e fotográficas durante entrada das máquinas quebradas dos clientes na empresa e pós envio dos consertos efetuados à banco de entrega e segurança corporativa perante indenizações (estado antes vs depois).

| Campo | Tipo | Restrições | Descrição |
|-------|------|------------|-----------|
| `id` | INT | PK, AUTO_INCREMENT | Identificador da Imagem de Laudo enviada. |
| `os_id` | INT | FK -> os(id) NOT NULL| Anexo e aglutinação na OS específica sob investigação ou reparo providencialmente ligada de ponta a ponta que cai sumariamente quando Ordem some. |
| `tipo` | ENUM | NOT NULL | Sentido e momento da Fotografia documentária periciada na assistência ('recepcao' Entrada Bruta, 'reparo' Placa Aberta Durante Solução, 'conclusao' Verificação de Brilhos e montagem Fechamento Exito/Fracasso). |
| `arquivo` | VARCHAR(255) | NOT NULL | Nome e localização local ou absoluta de URI salva via FileStorage ou Blob local persistente apontado no diretório web seguro com nomes sanitizados criptográficos de diretório físico ou cloud do backend. |
| `descricao` | VARCHAR(255) | NULL | Breve extrato descritivo manual preenchível justificando fatura ou local fotocopiado do Equipamento. |
| `created_at` | DATETIME | DEFAULT CURRENT_TIMESTAMP | Tempo de envio. |

---

## 10. logs
Tabela de monitoração estrita que protege contra acidentes com usuários deletando dados sem rastreamento ou visualizando informações protegidas garantindo governança (Audit Trail ou Registros Globais).

| Campo | Tipo | Restrições | Descrição |
|-------|------|------------|-----------|
| `id` | INT | PK, AUTO_INCREMENT | Registrado Identificador. |
| `usuario_id` | INT | FK -> usuarios(id) NULL | Qual dos operadores de bancada fez a tratativa de Log registrada por requisição autenticada, que se mantém ON DELETE SET NULL a fim de não destruir pistas ou históricos se funcionário sai demitido. |
| `acao` | VARCHAR(50) | NOT NULL | Identificação em constante do sistema, como `login`, `logout`, `os_delete`. |
| `descricao` | TEXT | NULL | Humanizado com contexto dinâmico concatenando chaves de IDs da operação e contexto para debug simples em formato de visualização não restritivo humano (ex: "Criou Equipamento Apple Mac de Marcos"). |
| `ip` | VARCHAR(45) | NULL | Remetente Protocolar. IP de origem de acesso à sessão por onde o ato foi provado originário de ataque ou origem segura de login autoritário de ambiente fixo e documentado de Firewall. |
| `user_agent` | TEXT | NULL | Aparelho do Funcionário / Dispositivo que atestou Log (SO, Browser). |
| `created_at` | DATETIME | DEFAULT CURRENT_TIMESTAMP | Acontecimento documentado por data da requisição base do Framework CodeIgniter Global App Timestamp Database Injection no timezone providenciado pelo framework. |

**Índices Secundários:** `idx_usuario` (usuario_id), `idx_acao` (acao)

---

## 11. configuracoes
Tabela centralizada de chave-valor contendo flags dinâmicas, customização da filial operante (dados do boleto) e opções persistentes da empresa que o painel Front-End permite que administradores leigos alterem sem redeploys do Software de Produção.

| Campo | Tipo | Restrições | Descrição |
|-------|------|------------|-----------|
| `id` | INT | PK, AUTO_INCREMENT | Sequencial nativo. |
| `chave` | VARCHAR(100) | UNIQUE, NOT NULL | Namespace constante exigido das Query Builders (ex: `empresa_nome`, `smtp_host`, `whatsapp_token`, `tema`). |
| `valor` | TEXT | NULL | Expressão absoluta que serve o dado consumido internamente nos escopos e helpers de view dependentes dos modelos globais base do ecossistema. |
| `tipo` | VARCHAR(20) | DEFAULT 'texto' | Tratativa do dado na inserção do input no gerador e sanitizador Form dinâmico administrativo e do Core (`numero`, `texto`, `json`). |
| `created_at` | DATETIME | DEFAULT CURRENT_TIMESTAMP | Tempo de criação inicial com Setup Defaults Scripted Initial Release Base (Seeders). |
| `updated_at` | DATETIME | ON UPDATE CURRENT_TIMESTAMP | Sinal emitido informando que alguma Chave trocou seu `valor` atual desde último Setup garantidor na hora do salvamento manual. |

---

## 12. fornecedores
Cadastro de parceiros B2B e Pessoas Jurídicas/Físicas que fornecem suprimentos, ferramentas e insumos ou prestam serviços à assistência técnica.

| Campo | Tipo | Restrições | Descrição |
|-------|------|------------|-----------|
| `id` | INT | PK, AUTO_INCREMENT | Identificador principal do sistema. |
| `tipo_pessoa` | ENUM | DEFAULT 'juridica' | Define se é 'juridica' (padrão) ou 'fisica'. |
| `nome_fantasia`| VARCHAR(100)| NOT NULL | Nome curto comercial e amigável. |
| `razao_social`| VARCHAR(100)| NULL | Nome legal completo se for Pessoa Jurídica. |
| `cnpj_cpf` | VARCHAR(20) | UNIQUE, NULL | Cadastro de identificação federal e logístico para emissão de nota. |
| `ie_rg` | VARCHAR(20) | NULL | Inscrição Estadual ou RG. |
| `email` | VARCHAR(100)| NULL | E-mail de negociação com vendedor B2B ou NFs. |
| `telefone1` | VARCHAR(20) | NOT NULL | Telefone base. |
| `telefone2` | VARCHAR(20) | NULL | Aparelho secundário. |
| `cep` | VARCHAR(10) | NULL | Endereço Postal padrão do IBGE de faturamento de transportadora. |
| `endereco` | VARCHAR(100)| NULL | Rua / Avenida de faturamento / entrega. |
| `numero` | VARCHAR(10) | NULL | Número de porta. |
| `complemento` | VARCHAR(50) | NULL | Sala, andar, prédio. |
| `bairro` | VARCHAR(50) | NULL | Região/Distrito de despacho. |
| `cidade` | VARCHAR(50) | NULL | Cidade. |
| `uf` | CHAR(2) | NULL | Estado de Federação (SP, RJ, MG). |
| `observacoes` | TEXT | NULL | Catálogos de links, apontamentos informais de contatos. |
| `ativo` | TINYINT(1) | DEFAULT 1 | Marca fornecedores como inativos (sem destruir vendas/NFs antigas). |
| `created_at` | DATETIME | DEFAULT CURRENT_TIMESTAMP | Inserção. |
| `updated_at` | DATETIME | ON UPDATE CURRENT_TIMESTAMP | Último update. |

---

## 13. funcionarios
Cadastro individual de força de trabalho técnica e administrativa, com gestão de cargos, salários de operação e dats operacionais RH da empresa.

| Campo | Tipo | Restrições | Descrição |
|-------|------|------------|-----------|
| `id` | INT | PK, AUTO_INCREMENT | Identificador de contratação. |
| `nome` | VARCHAR(100)| NOT NULL | Nome humano limpo. |
| `cpf` | VARCHAR(20) | UNIQUE, NOT NULL| Cadastro de Pessoa Física obrigatório governamentalmente. |
| `rg` | VARCHAR(20) | NULL | Documento local Estadual emitente. |
| `data_nascimento`| DATE | NULL | Auxilio pra lembretes ou validação legal RH. |
| `cargo` | VARCHAR(50) | NULL | Função contratual (Técnico de Placa, Atendente Web, Gerente, Auxiliar). |
| `salario` | DECIMAL(10,2)| NULL | Registros de salário inicial Base ou fixo pra fechamento do mês (Despesas RH). |
| `data_admissao`| DATE | NULL | Início oficial do labor. |
| `data_demissao`| DATE | NULL | Termino histórico laborativo (bloqueia crachás/logins futuros). |
| `email` | VARCHAR(100)| NULL | Pessoal ou Empresarial de recebimento de PONTO Eletrônico/Notificação. |
| `telefone` | VARCHAR(20) | NOT NULL | Celular WhatsApp direto em caso de chamadas táticas in company. |
| `cep` | VARCHAR(10) | NULL | Endereçamento familiar para emergências ou correspondências RH. |
| `endereco` | VARCHAR(100)| NULL | Rua / Avenida do domicílio residencial. |
| `numero` | VARCHAR(10) | NULL | Número físico (Casa/Porta apt.). |
| `complemento` | VARCHAR(50) | NULL | Bloco, Apartamento etc. |
| `bairro` | VARCHAR(50) | NULL | Setor Habitacional de moradia. |
| `cidade` | VARCHAR(50) | NULL | Origem municipal. |
| `uf` | CHAR(2) | NULL | Estado/Federação base residencial. |
| `observacoes` | TEXT | NULL | Histórico informal sobre férias, acordos verbais prévios e atestados. |
| `ativo` | TINYINT(1) | DEFAULT 1 | Flag (1/0) de se funcionário ainda faz quadro atual. |
| `created_at` | DATETIME | DEFAULT CURRENT_TIMESTAMP | Input da ficha funcional. |
| `updated_at` | DATETIME | ON UPDATE CURRENT_TIMESTAMP | Atualização em alterações de salários/Endereços etc. |

---

## 14. equipamento_clientes
Tabela-pivô de relacionamento Muito-para-Muitos (N:N) projetada para associar um mesmo equipamento (que pertence a um único dono ou base empresarial primária da assistência) a outros múltiplos Clientes secundários no momento da formulação da OS (como um parente levando o celular, ou outro sócio da mesma infraestrutura que utiliza a máquina).

| Campo | Tipo | Restrições | Descrição |
|-------|------|------------|-----------|
| `id` | INT | PK, AUTO_INCREMENT | Identificador gerencial interno. |
| `equipamento_id` | INT | FK -> equipamentos(id) NOT NULL| Referência obrigatória ao equipamento rastreado. ON DELETE CASCADE. |
| `cliente_id` | INT | FK -> clientes(id) NOT NULL| Referência obrigatória do sub-cliente vinculado à máquina secundária. ON DELETE CASCADE. |
| `created_at` | DATETIME | DEFAULT CURRENT_TIMESTAMP | Tempo em que a relação foi aceita e vinculada no painel administrativo pela primeira vez. |

---

## 15. equipamentos_tipos
Tabela de categorias de equipamentos (ex: Celular, Notebook, Desktop).

| Campo | Tipo | Restrições | Descrição |
|-------|------|------------|-----------|
| `id` | INT | PK, AUTO_INCREMENT | Identificador do tipo. |
| `nome` | VARCHAR(100) | UNIQUE, NOT NULL | Nome do tipo de equipamento. |
| `ativo` | TINYINT(1) | DEFAULT 1 | Status de atividade. |
| `created_at` | DATETIME | NULL | Data de criação. |
| `updated_at` | DATETIME | NULL | Data de atualização. |

---

## 16. equipamentos_marcas
Tabela de fabricantes de equipamentos (ex: Samsung, Apple, Dell).

| Campo | Tipo | Restrições | Descrição |
|-------|------|------------|-----------|
| `id` | INT | PK, AUTO_INCREMENT | Identificador da marca. |
| `nome` | VARCHAR(100) | UNIQUE, NOT NULL | Nome do fabricante. |
| `ativo` | TINYINT(1) | DEFAULT 1 | Status de atividade. |
| `created_at` | DATETIME | NULL | Data de criação. |
| `updated_at` | DATETIME | NULL | Data de atualização. |

---

## 17. equipamentos_modelos
Tabela de modelos específicos vinculados a uma marca.

| Campo | Tipo | Restrições | Descrição |
|-------|------|------------|-----------|
| `id` | INT | PK, AUTO_INCREMENT | Identificador do modelo. |
| `marca_id` | INT | FK -> equipamentos_marcas(id) | Marca vinculada. |
| `nome` | VARCHAR(100) | NOT NULL | Nome do modelo. |
| `ativo` | TINYINT(1) | DEFAULT 1 | Status de atividade. |
| `created_at` | DATETIME | NULL | Data de criação. |
| `updated_at` | DATETIME | NULL | Data de atualização. |

---

## 18. equipamentos_defeitos
Catálogo de defeitos comuns por tipo de equipamento e classificação (Hardware/Software).

| Campo | Tipo | Restrições | Descrição |
|-------|------|------------|-----------|
| `id` | INT | PK, AUTO_INCREMENT | Identificador do defeito. |
| `nome` | VARCHAR(150) | NOT NULL | Título do defeito (ex: "Não liga"). |
| `tipo_id` | INT | FK -> equipamentos_tipos(id) | Tipo de equipamento associado. |
| `classificacao` | ENUM | NOT NULL | 'hardware' ou 'software'. |
| `descricao` | TEXT | NULL | Descrição detalhada do sintoma. |
| `ativo` | TINYINT(1) | DEFAULT 1 | Status de atividade. |
| `created_at` | DATETIME | NULL | Data de criação. |
| `updated_at` | DATETIME | NULL | Data de atualização. |

---

## 19. equipamento_defeito_procedimentos
Base de Conhecimento Técnica: Passos/Procedimentos operacionais para resolver cada defeito.

| Campo | Tipo | Restrições | Descrição |
|-------|------|------------|-----------|
| `id` | INT | PK, AUTO_INCREMENT | Identificador do procedimento. |
| `defeito_id` | INT | FK -> equipamentos_defeitos(id) | Defeito vinculado. |
| `descricao` | VARCHAR(255) | NOT NULL | Descrição do passo técnico. |
| `ordem` | INT(5) | NOT NULL | Ordem de execução do passo. |
| `created_at` | DATETIME | NULL | Data de criação. |
| `updated_at` | DATETIME | NULL | Data de atualização. |

---

## 20. os_defeitos
Tabela pivô que vincula defeitos comuns a uma Ordem de Serviço específica.

| Campo | Tipo | Restrições | Descrição |
|-------|------|------------|-----------|
| `id` | INT | PK, AUTO_INCREMENT | Identificador do vínculo. |
| `os_id` | INT | FK -> os(id) | Ordem de Serviço vinculada. |
| `defeito_id` | INT | FK -> equipamentos_defeitos(id) | Defeito vinculado. |
| `created_at` | DATETIME | NULL | Data de registro. |

---

## 21. acessorios_os
Armazena os acessórios específicos que acompanham o equipamento em uma determinada OS.

| Campo | Tipo | Restrições | Descrição |
|-------|------|------------|-----------|
| `id` | INT | PK, AUTO_INCREMENT | Identificador do acessório na OS. |
| `os_id` | INT | FK -> os(id) | OS vinculada. |
| `descricao` | VARCHAR(255) | NOT NULL | Descrição do acessório (ex: "Carregador original"). |
| `created_at` | DATETIME | NULL | Data de registro. |

---

## 22. fotos_acessorios
Fotos vinculadas a cada acessório recebido na OS.

| Campo | Tipo | Restrições | Descrição |
|-------|------|------------|-----------|
| `id` | INT | PK, AUTO_INCREMENT | Identificador da foto. |
| `acessorio_id` | INT | FK -> acessorios_os(id) | Acessório vinculado. |
| `arquivo` | VARCHAR(255) | NOT NULL | Caminho do arquivo da imagem. |
| `created_at` | DATETIME | NULL | Data de registro. |

---

## 23. estado_fisico_equipamento
Registro detalhado de anomalias ou estado físico do equipamento no momento da entrada.

| Campo | Tipo | Restrições | Descrição |
|-------|------|------------|-----------|
| `id` | INT | PK, AUTO_INCREMENT | Identificador do registro. |
| `os_id` | INT | FK -> os(id) | OS vinculada. |
| `descricao_dano` | VARCHAR(255) | NOT NULL | Descrição da avaria (ex: "Tela riscada"). |
| `created_at` | DATETIME | NULL | Data de registro. |

---

## 24. estado_fisico_fotos
Fotos que comprovam o estado físico relatado.

| Campo | Tipo | Restrições | Descrição |
|-------|------|------------|-----------|
| `id` | INT | PK, AUTO_INCREMENT | Identificador da foto. |
| `estado_fisico_id` | INT | FK -> estado_fisico_equipamento(id) | Registro de estado físico vinculado. |
| `arquivo` | VARCHAR(255) | NOT NULL | Caminho do arquivo da imagem. |
| `created_at` | DATETIME | NULL | Data de registro. |

---

## Otimização para Busca Global

A funcionalidade de **Busca Global** na navbar utiliza consultas `LIKE` em diversas tabelas. Para garantir o desempenho, o sistema depende dos seguintes índices:

- **Tabela `clientes`**: `idx_nome`, `idx_cpf`, `idx_telefone`.
- **Tabela `os`**: `idx_numero`, `idx_status`, `idx_cliente`.
- **Tabela `equipamentos`**: `numero_serie`, `imei` e vínculos com `clientes`.
- **Tabela `mensagens_whatsapp`**: Pesquisas por conteúdo de mensagem e telefone.

Recomenda--se monitorar o crescimento destas tabelas para avaliar a necessidade de índices `FULLTEXT` em campos de `texto` (como `os.relato_cliente` ou `mensagens_whatsapp.mensagem`) caso o volume de dados ultrapasse 1 milhão de registros.

---

*Gerado automaticamente pelo Assistente baseando-se no schema real e atualizado do sistema.*
