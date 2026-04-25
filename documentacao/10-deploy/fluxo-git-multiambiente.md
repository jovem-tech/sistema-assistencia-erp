# Fluxo Git Multiambiente

Atualizado em `24/04/2026`.

## Objetivo

Padronizar o desenvolvimento, a homologacao e a publicacao do ERP em quatro ambientes:

- `PC` de desenvolvimento principal;
- `notebook` de desenvolvimento auxiliar;
- `VM Ubuntu 24` de homologacao final;
- `VPS Ubuntu 24` de producao.

O GitHub continua sendo a origem central do codigo, mas a branch `main` so deve ser promovida depois da validacao completa na `VM`.

## Fluxo oficial

```text
PC / notebook -> GitHub (develop-desktop) -> GitHub (homolog-vm) -> VM Ubuntu 24 -> GitHub (main) -> VPS
```

## Papel de cada ambiente

- `PC` e `notebook`: desenvolvimento diario, ajustes, correcoes, documentacao e validacao local.
- `GitHub/develop-desktop`: branch operacional de desenvolvimento continuo.
- `GitHub/homolog-vm`: branch oficial de homologacao, usada para validar exatamente o que sera candidato a producao.
- `VM Ubuntu 24`: ambiente de homologacao final, espelhando o comportamento esperado da VPS antes da promocao.
- `GitHub/main`: branch oficial de producao. So recebe codigo validado na `VM`.
- `VPS`: ambiente produtivo. Nunca deve ser usado como origem regular de desenvolvimento.

## Branches oficiais

- `develop-desktop`: desenvolvimento do dia a dia no `PC` e no `notebook`.
- `homolog-vm`: consolidacao de tudo que precisa ser homologado na `VM`.
- `main`: linha oficial de producao.

## Regras obrigatorias

1. Antes de iniciar qualquer trabalho em `PC` ou `notebook`, atualizar a branch `develop-desktop`.
2. Toda alteracao funcional deve sair do ambiente de desenvolvimento para `develop-desktop` antes de seguir para homologacao.
3. A `VM` deve testar sempre a branch `homolog-vm`, nunca uma branch solta de desenvolvimento.
4. A branch `main` so deve ser atualizada depois da validacao completa na `VM`.
5. A `VPS` so deve receber codigo vindo da `main`.
6. Antes de atualizar a `VPS`, fazer backup de codigo, banco e arquivos.
7. Nunca usar `git add .` na `VPS`.

## Fluxo pratico

### 1. Desenvolvimento no PC ou notebook

Antes de comecar:

```bash
git checkout develop-desktop
git pull origin develop-desktop
```

Depois do trabalho:

```bash
git status
git add .
git commit -m "mensagem clara da alteracao"
git push origin develop-desktop
```

### 2. Envio para homologacao

Quando a entrega estiver pronta para teste na `VM`:

```bash
git checkout homolog-vm
git pull origin homolog-vm
git merge develop-desktop
git push origin homolog-vm
```

### 3. Homologacao oficial na VM Ubuntu 24

Na `VM`:

```bash
cd /var/www/sistema-hml
git config --global --add safe.directory /var/www/sistema-hml
git checkout homolog-vm
git pull --ff-only origin homolog-vm
php spark migrate
php spark cache:clear
```

Validar no minimo:

- login;
- dashboard;
- ordens de servico;
- orcamentos;
- uploads e fotos;
- PDFs;
- integracoes de WhatsApp;
- rotas e acoes criticas.

### 4. Promocao da VM para producao

Depois da homologacao aprovada na `VM`, a propria `VM` promove o que foi testado para a `main`:

```bash
cd /var/www/sistema-hml
git checkout main
git pull origin main
git merge homolog-vm
git push origin main
```

### 5. Backup obrigatorio antes da VPS

Na `VPS`, antes de atualizar:

#### 5.1 Backup Git do codigo atual

```bash
cd /var/www/sistema-hml
git push backup HEAD:backup/vps-antes-update-AAAA-MM-DD
```

#### 5.2 Backup do banco

```bash
mysqldump --single-transaction --routines --triggers --no-tablespaces -u sistema_hml -p sistema_hml > /root/sistema_hml_db_pre_update_$(date +%F_%H%M).sql
```

#### 5.3 Backup dos arquivos

```bash
cd /var/www
sudo tar -czf /root/sistema-hml-pre-update-$(date +%F_%H%M).tar.gz sistema-hml
```

### 6. Atualizacao da VPS

Na `VPS`:

```bash
cd /var/www/sistema-hml
git fetch origin
git checkout main
git pull --ff-only origin main
php spark migrate
php spark cache:clear
sudo systemctl restart php8.3-fpm
sudo systemctl restart nginx
```

Depois validar:

- login;
- dashboard;
- ordens de servico;
- orcamentos;
- uploads e fotos;
- WhatsApp;
- logs do servidor.

## Resumo visual

```text
PC / notebook
    -> git push origin develop-desktop
    -> merge develop-desktop -> homolog-vm
    -> VM: git pull origin homolog-vm + testes
    -> VM aprovada: merge homolog-vm -> main
    -> VPS: backup completo
    -> VPS: git pull --ff-only origin main
```

## O que nao deve entrar em commit

- `.env`
- backups temporarios (`*.utf8bak`, `*.pre_*`, `*.codex.bak_`)
- `node_modules/`
- builds temporarios locais
- artefatos gerados de publicacao local
- logs de execucao

## Regra de ouro

- `PC` e `notebook` desenvolvem.
- `VM` homologa.
- `main` publica.
- `VPS` produz.

