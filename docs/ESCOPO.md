# **ESCOPO COMPLETO DE ESPECIFICAÇÃO DE PRODUTO**

**HABIT TRACKER - MÓDULO 4: GAMIFICAÇÃO E CONTEÚDO**

* **Projeto:** Habit Tracker (Sistema para Gerenciamento e Acompanhamento de Rotinas Diárias)
* **Versão:** 2.0 (Progressão Ponderada Diária / Gamificação Dinâmica com Santuário)
* **Módulo Responsável:** Módulo 4 – Gamificação e Conteúdo
* **Equipe:** Emilly, Luiza, Miguel, Karen, Sofia
* **Líder do Grupo:** Sofia

---

### **1. VISÃO GERAL DO SISTEMA**
O Habit Tracker combina a gestão e monitoramento de rotinas diárias, semanais e mensais com ferramentas de análise visual e mecânicas de gamificação inspiradas em RPGs e bichinhos virtuais. A evolução do companheiro do usuário (Dragão) reflete a consistência pessoal. O progresso da conta é vitalício, enquanto os ciclos individuais de cada Dragão progridem do estágio de Ovo até a forma adulta (aproximadamente 360 dias por ciclo), alimentando um histórico colecionável no Santuário.

---

### **2. FUNCIONALIDADES DO MVP E ESCOPO DE DESENVOLVIMENTO**

##### **2.1. Funcionalidades Incluídas**
* **Gestão Completa de Hábitos (CRUD):** Cadastro, edição, visualização e exclusão de hábitos.
* **Rastreamento de Hábitos Bons e Ruins:** Suporte a metas positivas e controle de tempo/dias sem hábitos indesejados.
* **Tipos de Metas:**
    * **Realização:** Check-in direto ou botões de status (Concluído, Em Andamento, Não Concluído).
    * **Tempo:** Timer ou inserção manual de minutos.
    * **Quantidade:** Contagem numérica e unidades customizadas.
* **Cálculo Ponderado de XP:** Sistema baseado na porcentagem ponderada das tarefas cumpridas em relação ao teto diário fixo.
* **Evolução do Dragão por Elementos e Cenários:** Quatro estágios visuais com transição e escolha do ambiente/bioma (Fogo, Terra, Água, Ar).
* **Santuário (Álbum de Dragões):** Histórico de dragões que concluíram o ciclo e chocaram novos ovos.
* **Estatísticas Avançadas:**
    * Matriz Visual de Consistência (estilo GitHub).
    * Gráficos de barras empilhadas (Realizado vs. Não Realizado / Meta vs. Real).
    * Filtros temporais: Diário, Semanal, Mensal e Calendário integrado.
* **Onboarding Fluido e Personalizado:** Fluxo inicial para configuração de metas, hábitos e escolha do ovo.
* **Sistema de Notificações e Gatilhos:** Reengajamento, avisos de streak em risco e notificações configuráveis.

---

### **3. PARÂMETROS, DIFICULDADE E CÁLCULO DE PROGRESSÃO**

##### **3.1. Parâmetros de Cadastro de Hábitos**
Cada hábito cadastrado deve conter:
* **Nome do Hábito:** Identificador textual.
* **Categoria:** Saúde, Estudos, Produtividade, Finanças, Desenvolvimento Pessoal, Outro.
* **Tipo de Meta:** Realização, Tempo ou Quantidade.
* **Frequência:** Diária, Semanal ou Mensal.
* **Dificuldade / Peso:** Definida automaticamente pelo sistema com permissão de ajuste manual.
* **Status do Hábito (no dia):** Concluído, Em Andamento, Não Concluído.

##### **3.2. Regras de Pesos por Dificuldade**
Os pesos determinam a representatividade e a contribuição de cada hábito no total do dia:

| Dificuldade | Peso | Diretriz de Aplicação |
| :--- | :--- | :--- |
| **Fácil** | 5 | Tarefas rápidas e simples (ex: tomar remédio, beber água). |
| **Médio** | 10 | Atividades de esforço moderado (ex: caminhada 30 min, arrumar o quarto). |
| **Difícil** | 15 | Tarefas de alta dedicação (ex: estudar 60 min, treino intenso). |
| **Muito Difícil** | 20 | Foco profundo prolongado ou hábitos de alta complexidade. |

##### **3.3. Algoritmo e Fórmula de Cálculo de XP Diário**
O XP ganho diariamente não depende da quantidade absoluta de hábitos, mas sim da porcentagem ponderada cumprida em relação a um teto fixo de XP diário.

1. **Parâmetros Base:**
    * Teto Diário de XP (TETO_DIÁRIO): 25 XP.
    * Peso Total do Dia (∑Wtotal): Soma dos pesos de todos os hábitos agendados para aquele dia.
    * Peso Realizado (∑Wcumpridos): Soma dos pesos dos hábitos concluídos ou do progresso proporcional dos hábitos em andamento.
2. **Fórmula:**
    * `XP do Dia = (∑Wcumpridos / ∑Wtotal) × TETO_DIÁRIO`.
3. **Pool Semanal:**
    * Hábitos com frequência semanal são calculados em um *pool* próprio ao término do ciclo semanal para não distorcer a média diária.

---

### **4. SISTEMA DE NÍVEIS, EVOLUÇÃO E SANTUÁRIO**

##### **4.1. Nível Vitalício do Usuário**
* O nível global da conta nunca é zerado.
* A progressão abrange 30 Níveis, exigindo 9.000 XP acumulados no total (com progressão escalonada em ~10% por nível).

##### **4.2. Estágios do Dragão e Escolha de Biomas/Elementos**
O dragão do ciclo atual passa por 5 estágios de crescimento acionados por marcos de XP. Ao atingir um marco, o usuário seleciona um elemento (Fogo, Terra, Água ou Ar), alterando o cenário de incubação e a forma final do estágio seguinte:

`[Ovo] --(120 XP)--> [Filhote] --(1.000 XP)--> [Jovem] --(3.500 XP)--> [Mudança de ambiente]--(6.000 XP)--> [Adulto] --(9.000 XP)--> [Novo Ciclo / Santuário]`

* **Marco 1 (120 XP / ~Dia 5):** Transição de ovo para filhote.
* **Marco 2 (1.000 XP / ~Dia 40):** Transição para a forma jovem.
* **Marco 3 (3.500 XP / ~Dia 140):** Transição para forma adulta.
* **Marco 4 (6.000 XP / ~Dia 240):** Mudança de ambiente do fundo (escolha do elemento).
* **Marco 5 (9.000 XP / ~Dia 360):** Transição para a forma adulta final (com elemento de acordo com ambiente escolhido).

##### **4.3. Ciclo de Renascimento e Álbum do Santuário**
* **Renascimento:** Ao atingir a forma adulta, o dragão aciona o choque de um novo Ovo. O XP do ciclo do dragão é zerado, mas o nível vitalício do usuário e suas insígnias permanecem intactas.
* **Santuário (Álbum de Fotos):** Coleção permanente que armazena os dragões anteriores. Cada entrada exibe:
    * Nome do dragão.
    * Foto/Arte do Dragão Adulto em seu bioma.
    * Período de vida (Data de nascimento e término).
    * Trajetória/Caminho de elementos escolhidos.
    * Tempo total exigido para a conclusão do ciclo.

---

### **5. ESTATÍSTICAS E ANALÍTICAS VISUAIS**
A seção de métricas fornece relatórios consolidados com suporte a filtros temporais (Diário, Semanal, Mensal, Anual e Calendário Interativo):
1. **Matriz Visual de Consistência:** Gráfico em grade (estilo GitHub) indicando a intensidade de cumprimento diário.
2. **Gráficos de Barras Empilhadas de Desempenho:**
    * Comparativo visual entre Quantidade Feita vs. Não Feita.
    * Comparativo de Planejado/Esperado vs. Realizado por categoria.
3. **Métrica de Hábitos Ruins (Contador de Abstinência):**
    * Indicador do tempo/dias em que o usuário permaneceu sem realizar um hábito nocivo cadastrado.
4. **Painel de Foco e Produtividade:** Consolidado de horas e minutos dedicados às metas baseadas em tempo.

---

### **6. BIBLIOTECA INICIAL DE HÁBITOS**

##### **6.1. Estrutura da Biblioteca Inicial de Hábitos**

| Categoria | Hábito Sugerido | Tipo de Meta | Exemplo de Meta Padronizada | Dificuldade Padrão | Prioridade MVP |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Saúde** | Beber água | Quantidade | 2 Litros | Fácil | Sim |
| **Saúde** | Caminhar / Fazer exercícios | Tempo | 30 minutos | Médio | Sim |
| **Saúde** | Dormir no horário | Realização | Concluir (Check-in) | Fácil | Sim |
| **Saúde** | Meditar / Alongamento / Vitaminas | Tempo / Realização | 10-15 minutos ou Concluir | Fácil | Não |
| **Estudos** | Estudar | Tempo | 60 minutos | Difícil | Sim |
| **Estudos** | Ler | Tempo / Quantidade | 30 minutos ou 20 páginas | Médio | Sim |
| **Estudos** | Revisar conteúdo / Fazer exercícios | Tempo / Quantidade | 30 minutos ou 10 exercícios | Médio | Sim |
| **Produtividade** | Planejar o dia / Organizar tarefas | Realização | Concluir (Check-in) | Fácil | Sim |
| **Produtividade** | Trabalhar / Fazer lista de tarefas | Tempo / Realização | 60 minutos ou Concluir | Médio | Não |
| **Finanças** | Registrar gastos / Conferir orçamento | Realização | Concluir (Check-in) | Fácil | Sim |
| **Finanças** | Economizar dinheiro | Quantidade | Valor definido pelo usuário | Médio | Sim |
| **Desenvolvimento Pessoal** | Escrever no diário / Ler | Tempo | 10 a 30 minutos | Fácil | Sim |
| **Desenvolvimento Pessoal** | Praticar hobby / Aprender algo novo | Tempo | 30 minutos | Médio | Sim |

---

### **7. ONBOARDING E REDAÇÃO DA INTERFACE (UX WRITING)**

##### **7.1. Fluxo de Onboarding em 8 Etapas**
1. **Boas-Vindas:** Apresentação da proposta do app.
2. **Escolha do Ovo:** Seleção do Ovo.
3. **Nome do Dragão:** Campo textual para batizar o companheiro.
4. **Nome do Usuário:** Personalização do perfil.
5. **Escolha de Objetivos:** Áreas prioritárias de foco.
6. **Seleção de Hábitos:** Escolha de hábitos iniciais na biblioteca.
7. **Apresentação da Jornada:** Explicação do ciclo de XP e evolução.
8. **Finalização:** Transição para a tela principal (Dashboard).

##### **7.2. Notificações e Gatilhos de Engajamento**

| Gatilho | Canal | Frequência | Texto Base |
| :--- | :--- | :--- | :--- |
| **Lembrete Diário** | Push / In-app | Horário agendado | *"Hora de [nome do hábito]! Seu dragão está contando com você."* |
| **Streak em Risco** | Push | Diário (noite) | *"Seu dragão sente sua falta! Complete um hábito hoje para manter sua sequência."* |
| **Inatividade (3 dias)** | Push | Evento | *"Seu dragão está com saudades! Volte e continue sua jornada."* |
| **Inatividade (7 dias)** | Push | Evento | *"Sua jornada está pausada. Que tal voltar com um hábito fácil hoje?"* |