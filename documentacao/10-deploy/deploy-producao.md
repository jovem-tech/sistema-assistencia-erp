# Deploy em Produção

## Requisitos do Servidor

| Item | Mínimo Recomendado |
|------|--------------------|
| PHP | 8.2+ com extensões: mysqli, gd, fileinfo, mbstring |
| MySQL | 8.0+ ou MariaDB 10.6+ |
| Apache | 2.4+ com `mod_rewrite` ativo |
| RAM | 1GB+ |
| Disco | 10GB+ (para uploads de fotos) |
| HTTPS | Obrigatório em produção |

---

## Configuração do `.env` para Produção

```ini
CI_ENVIRONMENT = production

app.baseURL = 'https://seudominio.com/'

database.default.hostname = localhost
database.default.database = nome_banco_producao
database.default.username = usuario_banco
database.default.password = senha_forte_aqui
database.default.DBDriver = MySQLi

# Segurança
encryption.key = SUA_CHAVE_ALEATORIA_64_CHARS_AQUI
```

---

## Configuração Apache (Virtual Host)

```apache
<VirtualHost *:443>
    ServerName seudominio.com
    DocumentRoot /var/www/html/sistema-assistencia/public

    <Directory /var/www/html/sistema-assistencia/public>
        AllowOverride All
        Require all granted
    </Directory>

    SSLEngine on
    SSLCertificateFile    /etc/ssl/certs/seudominio.crt
    SSLCertificateKeyFile /etc/ssl/private/seudominio.key
</VirtualHost>
```

---

## Checklist de Deploy

```
[ ] Copiar arquivos para servidor (excluir: .git, documentacao/, tmp_*.php)
[ ] Configurar .env com dados de produção
[ ] Importar banco de dados
[ ] Executar `update_equip_db.php`, `update_os_campos.php` e `setup_rbac.php` (e remover scripts após uso)
[ ] Permissões: chmod -R 755 . && chmod -R 777 writable/ public/uploads/
[ ] Testar login e acesso ao sistema
[ ] Trocar senha do admin padrão
[ ] Ativar HTTPS
[ ] Configurar backup automático do banco
[ ] Testar upload de fotos
[ ] Verificar logs em writable/logs/
```

---

## Backup

### Banco de Dados (manual)
```bash
mysqldump -u usuario -p assistencia_tecnica > backup_$(date +%Y%m%d).sql
```

### Arquivos de Upload
```bash
tar -czf uploads_$(date +%Y%m%d).tar.gz public/uploads/
```

### Automatizar (cron Linux)
```bash
# Backup diário às 2h da manhã
0 2 * * * mysqldump -u usuario -pSENHA assistencia_tecnica > /backups/db_$(date +\%Y\%m\%d).sql
0 2 * * * tar -czf /backups/uploads_$(date +\%Y\%m\%d).tar.gz /var/www/html/sistema-assistencia/public/uploads/
```
