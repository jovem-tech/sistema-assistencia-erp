# Manual do Administrador — Backup

## Objetivo
Garantir cópia segura do banco de dados e dos arquivos de upload.

---

## Backup do Banco (Windows/XAMPP)
```bash
C:\xampp\mysql\bin\mysqldump.exe -u root assistencia_tecnica > backup_YYYYMMDD.sql
```

---

## Backup de Uploads
Copie a pasta:
```
public\uploads\
```

---

## Boas Práticas
- Realize backups diários
- Guarde cópias fora do servidor principal
- Teste restauração periodicamente
