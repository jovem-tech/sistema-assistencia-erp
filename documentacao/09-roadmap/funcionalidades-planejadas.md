# Roadmap — Funcionalidades Planejadas

> Atualizado: Março 2026

---

## 🟢 Concluído (Março 2026)

- [x] Sistema base de OS com fluxo completo
- [x] RBAC com permissões granulares por módulo
- [x] Importação/Exportação CSV em Clientes, Estoque, Serviços
- [x] Módulo de Serviços (catálogo de reparos padronizados)
- [x] Base de Defeitos com procedimentos de solução
- [x] Dashboard com KPIs e gráficos
- [x] Relatórios por OS, Financeiro, Estoque e Clientes
- [x] Upload e edição de fotos de equipamentos (Cropper.js)
- [x] Câmera nativa no cadastro de equipamentos
- [x] Autopreenchimento de endereço por CEP (ViaCEP)
- [x] Contato adicional no cadastro de cliente
- [x] Cadastro rápido de cliente/equipamento/marca/modelo (modais)
- [x] Link de aprovação de orçamento sem login
- [x] Design system Glassmorphism com Dark/Light mode

---

## 🔵 Em Desenvolvimento

- [ ] **Módulo de Vendas** — Balcão de vendas de peças e acessórios
- [ ] **Impressão de Etiqueta** — Etiqueta com QR Code da OS para colagem no equipamento
- [ ] **Notificações internas** — Alertas de OS com prazo vencido

---

## 🟡 Planejado (próximas versões)

### Comunicação
- [ ] **Integração WhatsApp (WhatsApp Business API)**
  - Notificação automática quando OS muda de status
  - Envio do link de orçamento diretamente pelo sistema
- [ ] **Envio de E-mail automático**
  - Aviso de OS pronta para retirada
  - Notificação de prazo de garantia

### Mobile
- [ ] **App PWA para Técnico**
  - Visualizar e atualizar OS pelo celular
  - Tirar fotos direto do celular e vincular à OS
  - Modo offline com sincronização posterior

### Financeiro
- [ ] **Integração com PIX**
  - Geração de QR Code para pagamento
  - Confirmação automática de pagamento via webhook
- [ ] **Emissão de NFS-e** (Nota Fiscal de Serviço eletrônica)
  - Integração com prefeitura via API

### Relatórios Avançados
- [ ] **Dashboard Gerencial** com indicadores de produtividade por técnico
- [ ] **Relatório de Tempo Médio de Reparo** por tipo de equipamento
- [ ] **Previsão de Demanda** baseada em histórico

### Clientes
- [ ] **Portal do Cliente** — Área onde o cliente acompanha suas OS com login simplificado
- [ ] **Programa de Fidelidade** — Pontuação por OS realizadas

### Infraestrutura
- [ ] **Multi-empresa** — Suporte a múltiplas filiais no mesmo sistema
- [ ] **API REST pública** — Para integração com outros sistemas
- [ ] **Backup automático** — Agendamento de backup do banco em nuvem

---

## ⚫ Backlog (sem data definida)

- [ ] Assinatura digital do cliente na OS (tablet/celular)
- [ ] Controle de acesso por IP/localização
- [ ] Integração fiscal (SEFAZ) para emissão de NF-e
- [ ] App Android nativo para técnicos de campo
