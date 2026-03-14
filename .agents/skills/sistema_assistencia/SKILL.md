---
name: sistema_assistencia
description: "Padrões, arquitetura e convenções do Sistema de Assistência Técnica (CodeIgniter 4 + Bootstrap 5)."
---

# Skill: Sistema de Assistência Técnica

Este documento define os padrões, a arquitetura e as convenções de desenvolvimento para o projeto **Sistema de Assistência Técnica**.

## Arquitetura e Tecnologias
- **Backend:** PHP 8+ com framework **CodeIgniter 4**.
- **Banco de Dados:** MySQL/MariaDB.
- **Frontend:** HTML5, CSS3, JavaScript (jQuery), **Bootstrap 5.3**.
- **Tema Visual:** Dark theme premium focado em **Glassmorphism**.
- **Ícones:** Bootstrap Icons.

## Estrutura de Diretórios Importante
- `app/Controllers/`: Controladores. Tudo herda de `BaseController`.
- `app/Models/`: Modelos do BD. Sempre use os recursos do CI4 (validações, timestamps).
- `app/Views/ layouts/`: Templates globais:
  - `main.php`: Template base.
  - `sidebar.php`: Menu lateral (já implementa lógica de exibição baseada em permissões e módulos).
  - `navbar.php`: Top navbar.
- `public/assets/css/estilo.css`: **Fonte da verdade para o Design System.** Modifica variáveis nativas do Bootstrap para um visual premium (dark/glass effect).

## Diretrizes de Frontend e Design System
Qualquer nova tela deve utilizar o design estabilizado presente em `estilo.css`:

1. **Painéis Principais:** Utilize a classe `.glass-card` em vez do tradicional `.card`. Ele fornece fundo semitransparente com blur nativo do CSS e comportamento visual de hover.
2. **Cards de Dashboard:** Utilize `.stat-card` acoplado com variantes (ex: `.stat-card-success`, `.stat-card-primary`). Ver estrutura no arquivo `estilo.css`.
3. **Botões Exclusivos:**
   - Botão de Ação Primária Absoluta: classe `.btn-glow` (possui animação gradiente premium e box-shadow brilhante).
   - Botões Secundários: use `.btn-outline-light` para adequação à paleta noturna.
4. **Tabelas:** Classes de DataTables e tabelas comuns já foram remapeadas no global. Sempre use `.table` e gerencie em visual escuro transparente `.table-hover`.
5. **Formulários:** As classes `.form-control` e `.form-select` estão ajustadas. Não reescreva inputs básicos na view.

> **Dica:** Há um laboratório de design vivo em `public/design-system.html` contendo todos os trechos e visualizações prontos das classes.

## Diretrizes de Backend
1. **Flashdata para Respostas:** Operacionais e fluxos de criação e edição não usam ajax complexo a menos que requisitado; eles fazem recarregamento da página enviando:
   - Sucesso: `session()->setFlashdata('success', 'Gravado com sucesso!');`
   - Erro: `session()->setFlashdata('error', 'Algo deu errado');`
   - Erros do Formulário (Validation): `session()->setFlashdata('errors', $validation->getErrors());` -- o HTML global captura e preenche.
2. **Autorizações de Visualização:** Novas páginas que não sejam públicas devem ter Filtros (Filters) nas rotas ou verificação condicional em seu core (se helper próprio criado), e suas opções de menu devem consultar `canModule('nome')`.
3. **Views:** Mantenha a injeção via block do CI4: estenda `layouts/main` (`<?= $this->extend('layouts/main') ?>`) e empurre a tela na tag `<?= $this->section('content') ?>`.
