# Atualizacao de VPS sem Downtime (Contingencia + Ubuntu novo)

Atualizado em 22/03/2026.

## Objetivo

Padronizar a atualizacao de infraestrutura (ex.: Ubuntu 24.04 para Ubuntu 26.xx) sem indisponibilidade relevante do ERP.

Regra operacional:

> nao atualizar o sistema operacional diretamente na VPS de producao ativa sem contingencia pronta.

---

## 1. Estrategia recomendada

Use modelo **Blue/Green** com duas VPS:

- **Blue**: producao atual (ex.: Contabo).
- **Green**: contingencia ativa (outro provedor ou outro datacenter).

Fluxo de atualizacao:

1. subir Green com versao nova de SO;
2. restaurar codigo, banco e arquivos;
3. validar tudo;
4. virar trafego para Green;
5. atualizar/recriar Blue com calma;
6. manter rollback pronto.

---

## 2. O que NAO fazer

- nao executar `do-release-upgrade` direto na VPS de producao sem plano de fallback;
- nao fazer deploy sem backup testado;
- nao migrar sem checklist funcional minimo (login, OS, WhatsApp, uploads, CRM);
- nao alterar DNS sem reduzir TTL antes da janela.

---

## 3. Pre-requisitos obrigatorios

1. Backup completo:
   - banco MySQL (`mysqldump`);
   - arquivos de aplicacao (principalmente `writable/` e `public/uploads/`);
   - arquivo `.env`.
2. Script de provisionamento validado:
   - `10-deploy/scripts/install_erp.sh`.
3. Manual base de deploy:
   - `10-deploy/manual-tecnico-oficial-vps-ubuntu-24-ci4.md`.
4. Token/chaves de acesso:
   - Git, SSH, DB, API WhatsApp, integrações externas.
5. DNS com TTL reduzido (ex.: 60s) antes da virada.

---

## 4. Fase A - Preparar VPS Green (contingencia)

## 4.1 Provisionar servidor novo

- Ubuntu alvo (quando disponivel, ex.: 26.xx).
- Atualizar pacotes e hardening basico (UFW, usuario operacional, SSH seguro).

## 4.2 Instalar stack e app

Executar instalador oficial:

```bash
cd /var/www/sistema-hml
sudo bash documentacao/10-deploy/scripts/install_erp.sh
```

Ou seguir manual completo:

- `10-deploy/manual-tecnico-oficial-vps-ubuntu-24-ci4.md`.

## 4.3 Restaurar dados

### Banco

```bash
mysql -u sistema_hml -p sistema_hml < /root/backup_sistema_hml.sql
```

### Arquivos (uploads/writable)

```bash
rsync -avz --delete /origem/public/uploads/ /var/www/sistema-hml/public/uploads/
rsync -avz --delete /origem/writable/ /var/www/sistema-hml/writable/
```

### Configuracao

- ajustar `.env` com host, baseURL, credenciais e chaves reais;
- limpar cache:

```bash
php spark cache:clear
```

---

## 5. Fase B - Validar Green antes de virar trafego

Checklist funcional minimo:

1. login e logout;
2. dashboard;
3. abrir/editar/visualizar OS;
4. central de mensagens e envio de teste;
5. CRM metricas;
6. upload e preview de imagens;
7. geracao de PDF.

Validacoes tecnicas:

```bash
nginx -t
systemctl status nginx --no-pager -l
systemctl status php8.3-fpm --no-pager -l
systemctl status mysql --no-pager -l
tail -n 100 /var/log/nginx/error.log
```

Somente avancar com todas as validacoes em verde.

---

## 6. Fase C - Virada de trafego sem downtime

Opcoes:

- DNS (mais simples): apontar dominio para IP Green;
- Proxy/LB (ideal): trocar upstream Blue -> Green.

Recomendacao:

1. reduzir TTL antes da janela;
2. virar trafego;
3. monitorar 30-60 minutos:
   - erro 5xx;
   - latencia;
   - logins ativos;
   - filas WhatsApp.

---

## 7. Fase D - Atualizar Contabo (Blue) com seguranca

Com trafego ja na Green:

1. recriar VPS Blue em Ubuntu novo (preferivel a upgrade in-place);
2. reprovisionar com mesmo script/manual;
3. restaurar backup;
4. validar;
5. escolher:
   - manter Green como principal e Blue como DR;
   - ou voltar principal para Blue.

---

## 8. Rollback rapido (obrigatorio)

Se Green apresentar falha apos virada:

1. voltar DNS/LB para Blue;
2. confirmar recuperacao de sessoes;
3. congelar deploy;
4. abrir analise de causa raiz.

Meta de rollback: ate 5 minutos.

---

## 9. Checklist de janela de atualizacao

Antes:
- [ ] backup DB + uploads + `.env`
- [ ] TTL reduzido
- [ ] Green validada
- [ ] responsaveis online

Durante:
- [ ] virada controlada
- [ ] monitoramento ativo
- [ ] registro de horario de corte

Depois:
- [ ] validacao funcional completa
- [ ] validacao de logs
- [ ] documentacao do que foi feito

---

## 10. Pontos criticos de consistencia dev x producao

Se funcionar local e falhar na VPS, validar nesta ordem:

1. `.env` (linhas nao comentadas, credenciais corretas);
2. permissoes (`www-data` em `writable` e `public/uploads`);
3. versao PHP/extensoes;
4. path/sensibilidade de maiusculas em Linux;
5. cache da aplicacao;
6. configuracao Nginx + socket PHP-FPM.

---

## 11. Evidencia minima para fechamento da mudanca

Salvar no changelog interno:

- data/hora da virada;
- ambiente origem e destino;
- commit/tag implantado;
- resultado dos testes;
- incidentes e acao corretiva;
- decisao final (onde ficou a producao principal).

