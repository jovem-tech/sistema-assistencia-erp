# Reestruturação de Formulários em Abas (Equipamentos)

Implementado em: 2026-03
Status: Ativo
Localização: `/equipamentos/novo`, `/equipamentos/editar` e modal de Novo Equipamento na OS.

## 1. Descrição
Para melhorar a usabilidade e organização das informações, os formulários de cadastro e edição de equipamentos foram transformados em um layout de **Abas (Tabs)**. Isso evita telas excessivamente longas e agrupa os dados de forma lógica.

## 2. Estrutura das Abas

### Aba 1: Informações
Focada nos dados básicos e de identificação.
- **Cliente, Marca, Modelo, Tipo**: Seleção via Select2.
- **Número de Série**: IMEI ou Serial Number.
- **Senha de Acesso**: Novo sistema de alternância entre **PIN (Numérico)** e **Alfanumérico (Texto)** com placeholders dinâmicos.
- **Estado Físico**: Campo para descrição visual.
- **Acessórios**: Campo de texto com **Botões de Atalho** (Carregador, Cabo USB, Capa, Chip, Cartão de Memória) para preenchimento rápido com um clique.

### Aba 2: Cor
Contém o novo **Seletor Profissional de Cor** (Veja documentação específica `2026-03-seletor-cor-profissional.md`).
- Organizado por **Accordion** de grupos tonais.
- Preview dinâmico.

### Aba 3: Fotos
Área dedicada à documentação visual.
- Suporte a até **4 fotos**.
- Sistema de **Captura via Câmera**, **Galeria** e **Recorte (Crop)** automático para padronização.
- Seleção automática da **Foto Principal**.

## 3. Regras de Negócio e UX
- **Consistência**: O mesmo layout de abas é replicado no modal de OS para garantir que o técnico tenha a mesma experiência independente de onde cadastre o aparelho.
- **Identificadores**: Os campos de acessórios agora adicionam valores sem duplicar se clicados múltiplas vezes.
- **Validação**: O formulário só permite o envio se os campos obrigatórios na Aba 1 estiverem preenchidos, mesmo que o usuário esteja em outra aba.
