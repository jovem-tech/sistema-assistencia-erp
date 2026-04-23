# Fluxo Git Multiambiente

Atualizado em `23/04/2026`.

## Objetivo

Padronizar o desenvolvimento do ERP em múltiplos ambientes sem perder sincronização entre:

- PC principal;
- notebook;
- VM Ubuntu 24 de validação;
- VPS Ubuntu 24 de produção;
- GitHub como origem central do código.

## Fluxo oficial

```text
PC / notebook -> GitHub (develop) -> VM Ubuntu 24 -> GitHub (main) -> VPS
```

## Papel de cada ambiente

- `PC` e `notebook`: desenvolvimento diário, correções, novas features e documentação.
- `GitHub/develop`: branch de integração e homologação.
- `VM Ubuntu 24`: validação técnica em ambiente próximo da produção.
- `GitHub/main`: branch estável pronta para produção.
- `VPS`: produção. Não deve ser usada como ambiente normal de desenvolvimento.

## Regras obrigatórias

1. Antes de iniciar trabalho em qualquer máquina, executar `git pull`.
2. Toda alteração funcional deve sair do PC ou notebook para o GitHub antes de chegar à VM ou VPS.
3. A VM pode receber ajustes durante testes, mas qualquer correção feita nela deve voltar para o GitHub antes do deploy.
4. A VPS só deve receber código vindo do GitHub.
5. Nunca usar a VPS como fonte permanente de desenvolvimento.

## Branches recomendadas

- `main`: produção estável.
- `develop`: homologação e preparação de release.
- `feature/*`: novas funcionalidades.
- `fix/*`: correções.
- `docs/*`: documentação e versionamento documental.

## Fluxo prático

### 1. Desenvolvimento no PC ou notebook

```bash
git pull
git checkout develop
git checkout -b feature/nome-da-tarefa
```

Depois do trabalho:

```bash
git add .
git commit -m "feat: descricao da alteracao"
git push -u origin feature/nome-da-tarefa
```

### 2. Integração em develop

Após revisão/organização da tarefa:

```bash
git checkout develop
git pull
git merge --no-ff feature/nome-da-tarefa
git push origin develop
```

### 3. Validação na VM Ubuntu 24

Na VM:

```bash
git checkout develop
git pull origin develop
php spark migrate
```

Validar no mínimo:

- login;
- ordens de serviço;
- orçamentos;
- uploads/fotos;
- geração de PDF;
- integrações de WhatsApp;
- permissões e rotas críticas.

### 4. Promoção para produção

Depois da validação na VM:

```bash
git checkout main
git pull
git merge --no-ff develop
git push origin main
```

### 5. Publicação na VPS

Na VPS:

```bash
git checkout main
git pull origin main
php spark migrate
```

Depois validar serviços e logs.

## O que não deve entrar em commit

- `.env`
- backups temporários (`*.utf8bak`, `*.pre_*`, `*.codex.bak_`)
- `node_modules/`
- builds temporários locais
- artefatos gerados de publicação local
- logs de execução

## Observação importante

No primeiro alinhamento entre ambientes, a VPS pode ser usada como fonte inicial de código para popular o GitHub. Depois desse ponto, o GitHub passa a ser a origem oficial do projeto.
