# Instalação Local (XAMPP)

## Pré-requisitos

| Software | Versão Mínima |
|----------|---------------|
| **XAMPP** | 8.2+ |
| **PHP** | 8.2+ |
| **MySQL / MariaDB** | 10.4+ |
| **Apache** | 2.4+ |

---

## Passo a Passo

### 1. Instalar XAMPP

Baixe em: https://www.apachefriends.org

Configure para usar a porta **8081** (ou 8080) para evitar conflito com outras aplicações:
- Abra `XAMPP Control Panel`
- Apache → Config → `httpd.conf`
- Altere `Listen 80` para `Listen 8081`

---

### 2. Clonar o Projeto

```bash
# Na pasta htdocs do XAMPP
cd C:\xampp\htdocs

# Clonar repositório (se usar Git)
git clone https://github.com/seu-usuario/sistema-assistencia.git

# Ou descompacte o ZIP na pasta sistema-assistencia
```

---

### 3. Importar o Banco de Dados

1. Abra o phpMyAdmin: `http://localhost/phpmyadmin`
2. Crie um banco chamado `assistencia_tecnica`
3. Importe o arquivo SQL: `database.sql`

Ou via linha de comando:
```bash
C:\xampp\mysql\bin\mysql.exe -u root assistencia_tecnica < database.sql
```

---

### 4. Executar scripts de atualizaÃ§Ã£o (apÃ³s importar o banco)

1. Acesse no navegador:
   - `http://localhost:8081/update_equip_db.php` (migra tipos/marcas/modelos)
   - `http://localhost:8081/update_os_campos.php` (adiciona campos de OS: acessórios e forma de pagamento)
   - `http://localhost:8081/setup_rbac.php` (cria RBAC e permissÃµes)
2. Verifique se as mensagens retornaram sucesso.

> **ObservaÃ§Ã£o:** esses scripts sÃ£o idempotentes e podem ser executados mais de uma vez com seguranÃ§a.

### 5. Configurar o `.env`

Copie o arquivo de exemplo:
```bash
cp env .env
```

Edite o `.env`:
```ini
CI_ENVIRONMENT = development

app.baseURL = 'http://localhost:8081/'

database.default.hostname = localhost
database.default.database = assistencia_tecnica
database.default.username = root
database.default.password = 
database.default.DBDriver = MySQLi
database.default.port     = 3306
```

---

### 6. Configurar Permissões de Pasta

```bash
# Windows — dar controle total
icacls "C:\xampp\htdocs\sistema-assistencia\writable" /grant Users:F /T
icacls "C:\xampp\htdocs\sistema-assistencia\public\uploads" /grant Users:F /T
```

---

### 7. Iniciar o Sistema

1. Abra o **XAMPP Control Panel**
2. Inicie **Apache** e **MySQL**
3. Acesse: `http://localhost:8081/`

---

### 8. Login Inicial

```
Email: admin@sistema.com
Senha: admin123
```

> ⚠️ **Troque a senha imediatamente após o primeiro acesso!**

---

## Solução de Problemas

| Problema | Solução |
|----------|---------|
| Página branca | Verifique `writable/logs/` para erros PHP |
| Erro 404 | Verifique se `mod_rewrite` está ativo no Apache |
| Erro de banco | Confirme credenciais no `.env` |
| Uploads não funcionam | Verifique permissões de `public/uploads/` |
| CEP não preenche | Verifique conexão com internet (API ViaCEP) |
