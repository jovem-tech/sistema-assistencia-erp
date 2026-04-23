# Manual do Administrador — Restauração

## Objetivo
Restaurar o banco e os arquivos de upload a partir de backup.

---

## Restaurar Banco (Windows/XAMPP)
```bash
C:\xampp\mysql\bin\mysql.exe -u root assistencia_tecnica < backup_YYYYMMDD.sql
```

---

## Restaurar Uploads
1. Pare o Apache
2. Substitua a pasta:
```
public\uploads\
```
3. Inicie o Apache

---

## Pós-Restauração
- Teste login e acesso aos módulos
- Verifique logs em `writable\logs\`
