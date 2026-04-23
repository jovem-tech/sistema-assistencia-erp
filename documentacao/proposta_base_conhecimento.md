# Proposta: Base de Conhecimento Técnica (Checklist de Diagnóstico)
**Status:** ✅ IMPLEMENTADO (Março/2026)
## 1. Visão Geral da Proposta

A proposta atual sugere uma evolução estrutural profunda no módulo de **Defeitos Comuns**.
Em vez de atuar apenas como um mero dicionário ou lista de tags estáticas ("Não carrega"), o sistema passaria a abrigar **Procedimentos Operacionais Padrão (POP)** voltados para a triagem e manutenção.

### Hierarquia Comparativa

**Modelo Atual (Dicionário):**
```text
Equipamento
 └── Categoria (Hardware / Software)
      └── Defeito (Título + Descrição Simples)
```

**Novo Modelo Proposto (Base de Conhecimento):**
```text
Equipamento
 └── Categoria
      └── Defeito
           └── Procedimentos de Triagem / Solução (Checklist)
                ├── Passo 1 (ex: Testar cabo)
                ├── Passo 2 (ex: Testar fonte e conectores)
                └── Passo 3 (ex: Medir tensão do terminal)
```

---

## 2. Por que essa melhoria "faria o sistema subir muito"?

Implementar esta funcionalidade eleva o *Sistema de Assistência* do status de "ferramenta de registro" para uma **Ferramenta de Gestão e Inteligência Técnica**. Os principais benefícios comerciais e operacionais incluem:

### 🌟 2.1. Padronização Absoluta da Qualidade
Oficinas e assistências técnicas sofrem com a divergência de diagnósticos entre técnicos diferentes (o erro humano ou esquecimento). Com uma base de conhecimento, **todo defeito guiado dita exatamente a mesma esteira de testes** obrigatórios antes de condenar uma placa ou fechar orçamento.

### 🚀 2.2. Facilidade Incomparável de Onboarding (Treinamento)
Técnicos novos, estagiários ou atendentes de balcão de Nível 1 não precisam adivinhar procedimentos. O sistema atua como o "Treinador". Se o atendente abrir a ficha de "Computador não dá vídeo", o sistema lista o que ele deve verificar antes mesmo de mandar para a bancada avançada.

### 💰 2.3. Agrega Valor Inestimável (Visão de Venda SaaS)
Se você pretende vender este sistema como um SaaS (Software as a Service), entregar **Processos Prontos** vende muito mais do que apenas telas de cadastro. Uma base pré-populada com os defeitos e procedimentos dos principais equipamentos do mercado torna o software indispensável.

---

## 3. Integração Perfeita com a Ordem de Serviço (OS)

O real poder dessa arquitetura brilharia na **Ordem de Serviço**.
Imagine a seguinte esteira:

1. O balcão cria a OS e seleciona o equipamento e o defeito relatado (*ex: Bateria não carrega*).
2. O técnico de bancada abre a OS para iniciar o diagnóstico técnico.
3. **Automagicamente**, a OS puxa o laudo baseando-se no modelo de procedimentos:
   - [ ] Conector desobstruído e limpo?
   - [ ] Cabo flat testado?
   - [ ] Bateria apresenta ciclo viciado pelo software de medição?
   - **Módulo de OS:** No futuro, ao selecionar um defeito na abertura da OS, o sistema poderia sugerir o checklist automaticamente na tela de execução.
4. O técnico vai "checando" os itens. O que falhar vira automaticamente a "Solução Aplicada" ou o item do "Orçamento".

---

## ✅ Conclusão da Implementação
A melhoria foi integrada com sucesso ao ecossistema AssistTech em 12/03/2026. O sistema agora atua não apenas como um gestor de fluxo, mas como um repositório central de inteligência técnica.

---

## 4. O que muda no Banco de Dados (Implementação Técnica)?

A mudança técnica não destrói o que fizemos, ela **expande**. A estrutura atual continuaria intacta, mas introduziríamos o conceito de Relacionamento (1:N One-to-Many).

**Nova Tabela:** `equipamentos_defeitos_procedimentos`
- `id` (INT, Primary Key)
- `defeito_id` (INT, Foreign Key -> referencia equipamentos_defeitos)
- `ordem` (INT, para definir o passo 1, 2, 3...)
- `descricao` (VARCHAR)
- `tipo` (ENUM: 'verificacao', 'aviso_risco', 'solucao')

---

## 5. Como ficaria a Interface de Usuário (UX/UI)?

1. **Os Cards Atuais:** Permanecem lindos como construídos, porém, ganharão um selo com um ícone de "*Listagem/Checklist*" informando a quantidade de passos vinculados. (ex: `[ 3 passos ]`).
2. **Ao Clicar no Defeito:** Em vez de só "Editar", pode haver um botão para "Gerenciar Procedimentos", abrindo um painel lateral (*Offcanvas*) ou uma tela expansiva tipo *Accordion*.
3. **Drag-and-Drop:** Uma interface dinâmica onde o administrador da assistência cadastra o "Passo a passo" arrastando a ordem das tarefas.

---

## Conclusão de Viabilidade

**Nível de Esforço Técnico:** Moderado.
**Retorno de Valor Sistêmico:** Altíssimo.

A transição desse módulo para uma **Base de Conhecimento Técnica** (ou Troubleshooting Guiado) é, sem dúvida, um ***Game-Changer***. Ela muda a premissa de que o "operador diz ao sistema o que aconteceu", para "o sistema orienta o operador no que fazer", criando workflows infalíveis de balcão e bancada.
