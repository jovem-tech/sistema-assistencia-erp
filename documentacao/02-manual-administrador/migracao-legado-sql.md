# Manual do Administrador - Migracao Legada SQL

Atualizado em 29/03/2026 para a release `2.9.1`.

## Objetivo
Importar dados do sistema legado para o ERP novo com:
- rastreabilidade
- reprocessamento seguro
- validacao previa
- preservacao do numero antigo da OS
- backfill dos detalhes operacionais da ordem

## Escopo atual da migracao

Importado:
- clientes
- equipamentos vinculados aos clientes
- ordens de servico vinculadas a cliente e equipamento
- datas operacionais principais
- relato do cliente
- diagnostico tecnico / laudo
- solucao aplicada
- observacoes internas compativeis
- observacoes do cliente quando existirem no legado
- valores financeiros principais
- composicao de servicos e pecas quando existir em tabelas detalhadas do legado
- historico estruturado de status mapeavel
- defeitos vinculados a OS quando existirem
- notas legadas operacionais preservadas

Fora do escopo desta fase:
- anexos binarios antigos
- fotos antigas
- PDFs antigos
- mensagens WhatsApp do sistema legado

## Regra da numeracao

O ERP novo continua gerando `numero_os` normalmente.

Cada OS migrada tambem grava:
- `numero_os_legado`
- `legacy_origem`
- `legacy_id`

Resultado operacional:
- a equipe usa o numero novo como oficial
- o numero antigo continua visivel e pesquisavel na listagem `/os`
- a conferencia pos-migracao pode ser feita rapidamente pelo seletor `Somente legado` na propria listagem `/os`
- a busca global da navbar tambem aceita `numero_os_legado`

## Deduplicacao segura na migracao

### Clientes
- quando houver `CPF/CNPJ` valido repetido no legado, o importador tenta consolidar esses aliases no mesmo cliente canonico
- a consolidacao fica auditada em `legacy_import_aliases`
- nao existe mesclagem heuristica por nome, email ou telefone
- se o documento colidir com um cliente local sem rastreabilidade legada segura, a linha e ignorada com `skipped_conflict`
- clientes sem `CPF/CNPJ` seguem como importacao independente, evitando falso positivo por documento ausente

### Equipamentos
- o banco `erp` nao possui um cadastro de equipamento por cliente compativel com o modelo novo
- por isso, a migracao deriva snapshots de equipamento a partir da propria tabela `os`
- quando houver identificador forte confiavel (`numero_serie` ou `IMEI`), o importador tenta convergir snapshots repetidos para um equipamento canonico
- sem identificador forte, o snapshot continua individual por OS para evitar mesclagem incorreta

### Detalhes da OS
- itens, historicos, defeitos e notas sao atualizados pelo par `legacy_origem + legacy_id`
- reprocessamentos nao duplicam registros
- filhos orfaos do legado entram como aviso, ficam no relatorio e nao contaminam a base nova

## Particularidade do banco legado `erp`

No banco `erp`, os clientes estao normalizados em `clientes`, mas boa parte dos dados tecnicos e financeiros da ordem esta distribuida entre varias tabelas.

Fontes principais usadas:
- `clientes`
- `os`
- `orcamentos`
- `orcamento_itens`
- `servicos_orc`
- `produtos_orc`
- `historico_status_os`
- `os_historico`
- `os_defeitos`
- `os_historicos`

Regras de extração:
- `clientes` sao importados diretamente de `clientes`
- `equipamentos` sao derivados de `os`
- `OS` usam `os` como cabecalho e `orcamentos` como complemento
- itens detalhados preferem `orcamento_itens`
- se a OS nao tiver `orcamento_itens`, o importador tenta `servicos_orc` e `produtos_orc`
- se ainda assim nao houver itens detalhados, o pipeline cria itens sinteticos seguros de totalizacao:
  - `os_totais_servico`
  - `os_totais_peca`
  - `os_totais_consolidado` quando o legado so possui subtotal/valor final sem separar servicos e pecas

Regra de rastreabilidade financeira:
- quando o legado trouxer apenas totais consolidados no cabecalho da OS, o importador registra a origem exata do valor no item sintetico:
  - `os.mao_obra`
  - `os.total_servicos`
  - `os.total_produtos`
  - `os.subtotal`
  - `os.valor + desconto` quando esse for o unico caminho seguro para recompor o total
- a aba `Valores` da visualizacao da OS mostra esse contexto em `Origem do valor legado`, evitando totais sem explicacao operacional

## Preparacao

### 1. Configurar a conexao do banco legado
No arquivo `.env`, preencher:

```ini
database.legacy.hostname = localhost
database.legacy.database = erp
database.legacy.username = usuario_legado
database.legacy.password = senha_legado
database.legacy.DBDriver = MySQLi
database.legacy.DBPrefix =
database.legacy.port = 3306
database.legacy.charset = utf8mb4
database.legacy.DBCollat = utf8mb4_unicode_ci
```

### 2. Revisar a configuracao da migracao
Arquivo:
- `app/Config/LegacyImport.php`

Pontos criticos:
- `sourceName`
- `batchSize`
- `allowCatalogAutoCreate`
- `writeInitialStatusHistory`
- queries-base de `clientes`, `equipamentos`, `os`, `os_itens`, `os_status_historico`, `os_defeitos` e `os_notas_legadas`
- aliases obrigatorios de cada query
- mapa explicito de status

## Sequencia operacional recomendada

### 1. Rodar migrations

```bash
php spark migrate --all
```

### 2. Executar preflight

```bash
php spark legacy:preflight
```

O preflight verifica:
- totais por entidade
- orfaos
- telefones invalidos
- documentos fora do padrao
- status sem mapeamento
- catalogos ausentes
- bloqueios de relacionamento

Telefones invalidos ou ausentes entram como aviso e nao bloqueiam a carga.

### 3. Corrigir bloqueios reais

Exemplos:
- status legado sem mapeamento
- query sem alias obrigatorio
- conexao legada mal configurada
- relacionamento estrutural quebrado no cabecalho da OS

### 4. Limpar a base de homologacao quando necessario

```bash
php spark legacy:prepare-target
php spark legacy:prepare-target --execute
```

Ou em um passo:

```bash
php spark legacy:import --execute --wipe-target
```

### 5. Executar a importacao

```bash
php spark legacy:import --execute
```

Comportamento:
- roda novo preflight antes da carga
- importa em ordem:
  1. clientes
  2. equipamentos
  3. OS
  4. itens/servicos/pecas
  5. historico estruturado de status
  6. defeitos da OS
  7. notas legadas
- atualiza registros ja migrados pelo par `legacy_origem + legacy_id`
- nao duplica registros reprocessados

### 6. Consolidar o relatorio

```bash
php spark legacy:report
php spark legacy:report --run_id=123
```

O relatorio consolida:
- importados
- atualizados
- ignorados
- erros por entidade e motivo

## Validacao recomendada apos a carga

Conferir:
- total de clientes, equipamentos e OS
- total de itens importados
- total de historicos estruturados importados
- total de defeitos importados
- total de notas legadas preservadas
- busca por `numero_os_legado`
- amostra manual de:
  - 10 clientes
  - 10 equipamentos
  - 20 OS

Na visualizacao da OS, validar:
- `Itens / Servicos`
- `Diagnostico`
- `Valores`
- bloco de numero legado/origem

## Comandos disponiveis

- `php spark legacy:preflight`
- `php spark legacy:prepare-target`
- `php spark legacy:import --execute`
- `php spark legacy:import --execute --wipe-target`
- `php spark legacy:report`

## Observacao operacional atual

No ambiente local, a migracao real do banco `erp` ja foi executada e passou a preencher:
- `os_itens`
- `os_status_historico` (linhas legadas mapeaveis)
- `os_defeitos`
- `os_notas_legadas`

Isso permite que a OS migrada deixe de ser apenas um cabecalho financeiro e passe a carregar o maximo possivel do contexto original, sem duplicacoes e sem inconsistencias estruturais.
