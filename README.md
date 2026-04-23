# Sistema de Assistência Técnica ERP

Versão atual do ERP: `2.15.0`  
Data da atualização documental: `23/04/2026`

## Visão geral

O projeto é o ERP operacional da Jovem Tech para assistência técnica, atendimento comercial e relacionamento com o cliente. O sistema centraliza:

- ordens de serviço;
- orçamentos;
- pacotes de serviços;
- CRM e contatos;
- central de mensagens com WhatsApp;
- estoque, precificação e documentos PDF;
- app mobile/PWA integrado ao mesmo domínio operacional.

## Destaques da release atual

- visualização da OS reorganizada com foco em contexto, orçamento, fotos e valores;
- sincronização operacional entre OS e orçamento:
  - orçamento em andamento vinculado à OS leva a OS para `aguardando_autorizacao`;
  - orçamento `aprovado` ou `convertido` leva a OS para `aguardando_reparo`;
- listagem de OS passou a exibir contexto do orçamento vinculado na própria coluna de status;
- botão da OS respeita orçamento já vinculado:
  - cria novo somente quando não existe orçamento;
  - edita/visualiza o orçamento existente quando já houver vínculo;
- modal `Nova OS` da listagem agora abre em modo protegido:
  - não fecha ao clicar fora;
  - não fecha por `ESC`;
  - só fecha pelo `X`, com alerta de registro em andamento.

## Stack principal

- backend: `PHP 8.2+` com `CodeIgniter 4`;
- banco de dados: `MySQL/MariaDB`;
- frontend: `Bootstrap 5`, `Select2`, `SweetAlert2`, `DataTables`;
- integrações:
  - WhatsApp;
  - geração de PDF;
  - envio por e-mail;
  - app mobile/PWA;
- infraestrutura alvo: VPS Linux com publicação em `/var/www/sistema-hml`.

## Estrutura principal

```text
app/
  Config/
  Controllers/
  Models/
  Services/
  Views/
documentacao/
  01-manual-do-usuario/
  06-modulos-do-sistema/
  07-novas-implementacoes/
  08-correcoes/
  10-deploy/
mobile-app/
public/
tests/
```

## Documentação oficial

- índice da documentação: `documentacao/README.md`
- manual do usuário de OS: `documentacao/01-manual-do-usuario/ordens-de-servico.md`
- manual do usuário de orçamentos: `documentacao/01-manual-do-usuario/orcamentos.md`
- visão técnica de OS: `documentacao/06-modulos-do-sistema/ordens-de-servico.md`
- visão técnica de orçamentos: `documentacao/06-modulos-do-sistema/orcamentos.md`
- histórico oficial de versões: `documentacao/07-novas-implementacoes/historico-de-versoes.md`
- nota da release atual: `documentacao/07-novas-implementacoes/2026-04-23-release-v2.15.0-os-orcamentos-documentacao-e-versionamento.md`
- registro desta atualização na VPS: `documentacao/10-deploy/2026-04-23-atualizacao-vps-release-v2.15.0.md`

## Versionamento

O ERP segue `SemVer` no formato `MAJOR.MINOR.PATCH`.

- `MAJOR`: ruptura estrutural ou incompatibilidade relevante;
- `MINOR`: nova funcionalidade compatível;
- `PATCH`: correção, estabilização ou ajuste sem quebra.

Fonte oficial da versão:

- arquivo padrão: `app/Config/SystemRelease.php`;
- override opcional: configuração `sistema_versao` no banco, quando utilizada.

## Operação local

Exemplos usuais:

```bash
php spark serve
php spark migrate
php spark orcamentos:lifecycle
php spark cache:clear
```

Validações rápidas antes de publicar:

```bash
php -l app/Config/SystemRelease.php
php -l app/Controllers/Os.php
php -l app/Controllers/Orcamentos.php
php -l app/Controllers/Orcamento.php
```

## Publicação

Ambiente de produção atual:

- aplicação: `/var/www/sistema-hml`
- servidor: VPS Linux
- publicação seletiva, preservando:
  - `.env`
  - `writable/`
  - `public/uploads/`
  - banco de dados operacional

## Observação importante

O repositório nasceu sobre o app starter do CodeIgniter, mas a referência operacional oficial deste projeto passou a ser esta documentação da Jovem Tech. Para manutenção cotidiana, priorize sempre os documentos em `documentacao/`.
