# Guia rapido - fluxo operacional de 4 ambientes

Atualizado em `24/04/2026`.

## Objetivo

Facilitar a memorizacao do fluxo oficial de desenvolvimento, homologacao e deploy do ERP entre:

- `desktop`
- `notebook`
- `VM Ubuntu 24`
- `VPS Ubuntu 24`

## Modelo mental

- `develop-desktop` = desenvolvimento
- `homolog-vm` = homologacao
- `main` = producao
- `VM` = ambiente que testa a `homolog-vm`
- `VPS` = ambiente que executa a `main`

## Fluxo oficial

```text
desktop/notebook -> develop-desktop
develop-desktop -> homolog-vm
homolog-vm -> VM
VM aprovada -> main
main -> VPS
backup da VPS -> antes do update
```

## Quem faz o que

| Ambiente / branch | Papel | O que faz | O que nao faz |
|---|---|---|---|
| `desktop` / `notebook` | desenvolvimento | cria, corrige, documenta e testa localmente | nao publica direto na `VPS` |
| `develop-desktop` | branch de desenvolvimento | recebe os commits do dia a dia | nao vira producao direto |
| `homolog-vm` | branch de homologacao | concentra o que vai para teste real | nao deve receber trabalho cru sem passar pelo desenvolvimento |
| `VM Ubuntu 24` | homologacao final | testa exatamente o que esta em `homolog-vm` | nao deve ser ambiente principal de programacao |
| `main` | branch de producao | recebe apenas o que foi aprovado na `VM` | nao deve receber codigo sem homologacao |
| `VPS` | producao | executa a `main` em ambiente real | nao deve ser usada para desenvolver |

## Passo a passo detalhado

### 1. Preparar o desenvolvimento no desktop ou notebook

```bash
git checkout develop-desktop
git pull origin develop-desktop
```

### 2. Implementar a alteracao

Executar no `desktop` ou `notebook`:

- alterar codigo;
- atualizar documentacao;
- ajustar a versao do sistema quando houver release;
- validar localmente.

### 3. Enviar a entrega para `develop-desktop`

```bash
git status
git add .
git commit -m "mensagem clara da alteracao"
git push origin develop-desktop
```

### 4. Promover o que sera homologado para `homolog-vm`

```bash
git checkout homolog-vm
git pull origin homolog-vm
git merge develop-desktop
git push origin homolog-vm
```

### 5. Atualizar e testar a VM

Na `VM Ubuntu 24`:

```bash
cd /var/www/sistema-hml
git config --global --add safe.directory /var/www/sistema-hml
git checkout homolog-vm
git pull --ff-only origin homolog-vm
php spark migrate
php spark cache:clear
```

### 6. Homologar a entrega na VM

Checklist minimo:

- login;
- dashboard;
- ordens de servico;
- orcamentos;
- uploads e fotos;
- PDFs;
- WhatsApp;
- rotas criticas.

### 7. Promover da VM para `main`

Depois da homologacao aprovada:

```bash
cd /var/www/sistema-hml
git checkout main
git pull origin main
git merge homolog-vm
git push origin main
```

### 8. Fazer backup antes de atualizar a VPS

Backup Git do codigo:

```bash
cd /var/www/sistema-hml
git push backup HEAD:backup/vps-antes-update-AAAA-MM-DD
```

Backup do banco:

```bash
mysqldump --single-transaction --routines --triggers --no-tablespaces -u sistema_hml -p sistema_hml > /root/sistema_hml_db_pre_update_$(date +%F_%H%M).sql
```

Backup dos arquivos:

```bash
cd /var/www
sudo tar -czf /root/sistema-hml-pre-update-$(date +%F_%H%M).tar.gz sistema-hml
```

### 9. Atualizar a VPS pela `main`

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

### 10. Validar a producao

```bash
git log -1 --oneline --decorate
sudo systemctl status php8.3-fpm --no-pager -l
sudo systemctl status nginx --no-pager -l
tail -n 50 /var/log/nginx/error.log
```

## Frase para memorizar

```text
Eu desenvolvo no desktop/notebook, envio para develop-desktop, promovo para homolog-vm, testo na VM, aprovo para main e so depois atualizo a VPS com backup antes.
```

