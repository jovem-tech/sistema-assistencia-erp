# Stack Tecnológica

## Backend

| Tecnologia | Versão | Uso |
|------------|--------|-----|
| **PHP** | 8.2 | Linguagem principal |
| **CodeIgniter 4** | 4.x | Framework MVC |
| **MySQL / MariaDB** | 10.x+ | Banco de dados |

### Padrões CI4 Utilizados
- **Models** com `allowedFields`, `validationRules`, `beforeInsert/beforeUpdate`
- **Controllers** com `requirePermission()` e `can()` para RBAC
- **Filters**: `AuthFilter` (sessão) + `PermissionFilter` (RBAC granular)
- **Flashdata** para mensagens de sucesso/erro
- **Query Builder** para todas as consultas

---

## Frontend

| Tecnologia | Versão | Uso |
|------------|--------|-----|
| **HTML5** | — | Estrutura semântica |
| **Bootstrap** | 5.3.3 | Grid, componentes |
| **Bootstrap Icons** | 1.11.3 | Iconografia |
| **jQuery** | 3.7.1 | DOM, AJAX |
| **jQuery Mask Plugin** | 1.14.16 | Máscaras de input |
| **Select2** | 4.1.0 | Selects avançados |
| **DataTables** | 1.13.7 | Tabelas paginadas |
| **Chart.js** | 4.4.0 | Gráficos no dashboard |
| **Cropper.js** | 1.6.1 | Editor de imagens |

### Design System
- **Estilo:** Glassmorphism (fundos translúcidos com `backdrop-filter: blur`)
- **Tema:** Dark Mode nativo com suporte a Light Mode via `data-theme`
- **Tipografia:** Google Fonts — Inter (300, 400, 500, 600, 700, 800)
- **Cores primárias:** Baseadas em variáveis CSS (`--primary`, `--secondary`)

---

## Infraestrutura

| Item | Detalhe |
|------|---------|
| **Servidor Web** | Apache (XAMPP) |
| **Porta Local** | 8081 (8080 alternativa) |
| **Uploads** | `/public/uploads/` |
| **Assets** | `/public/assets/css/` e `/public/assets/js/` |

---

## APIs Externas

| API | Uso | Endpoint |
|-----|-----|----------|
| **ViaCEP** | Autopreenchimento de endereço | `https://viacep.com.br/ws/{cep}/json/` |
| **Google Suggest** | Autocomplete de modelos (proxy no backend) | `https://suggestqueries.google.com/complete/search` |
| **Google Fonts** | Tipografia | CDN |

---

## CDNs Utilizadas (via jsDelivr / cdnjs)

```html
Bootstrap CSS/JS, Bootstrap Icons, DataTables, 
Select2, Chart.js, jQuery, jQuery Mask, Cropper.js
```

> **Nota de Segurança:** O sistema foi projetado para funcionar offline removendo CDNs
> e servindo os assets localmente quando necessário.
