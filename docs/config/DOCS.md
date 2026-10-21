### Seção 2: Funcionalidades do MVP

**Gestão de hábitos**
* **RF-01:** O sistema deve permitir ao usuário cadastrar um novo hábito.
* **RF-02:** O sistema deve permitir ao usuário editar um hábito existente.
* **RF-03:** O sistema deve permitir ao usuário visualizar seus hábitos cadastrados.
* **RF-04:** O sistema deve permitir ao usuário excluir um hábito.

**Hábitos bons e ruins**
* **RF-05:** O sistema deve permitir classificar um hábito como positivo ou negativo.
* **RF-06:** O sistema deve permitir cadastrar metas positivas para hábitos bons.
* **RF-07:** O sistema deve registrar o tempo ou os dias que o usuário passou sem praticar um hábito ruim.

**Tipos de meta**
* **RF-08:** O sistema deve oferecer o tipo de meta "Realização", com check-in direto.
* **RF-09:** O sistema deve permitir marcar o status de um hábito de realização como Concluído, Em Andamento ou Não Concluído.
* **RF-10:** O sistema deve oferecer o tipo de meta "Tempo", com registro por timer.
* **RF-11:** O sistema deve permitir inserir manualmente os minutos em metas de tempo.
* **RF-12:** O sistema deve oferecer o tipo de meta "Quantidade", com contagem numérica.
* **RF-13:** O sistema deve permitir que o usuário defina unidades customizadas em metas de quantidade.

**XP e Evolução**
* **RF-14:** O sistema deve calcular o XP diário com base na porcentagem ponderada das tarefas cumpridas em relação a um teto diário fixo.
* **RF-15:** O sistema deve exibir a evolução do dragão em quatro estágios visuais.
* **RF-16:** O sistema deve exibir uma transição visual entre os estágios do dragão.
* **RF-17:** O sistema deve permitir ao usuário escolher o elemento/bioma do dragão (Fogo, Terra, Água ou Ar).

**Santuário**
* **RF-18:** O sistema deve manter um histórico (Santuário) dos dragões que concluíram o ciclo.
* **RF-19:** O sistema deve gerar um novo ovo quando um dragão concluir seu ciclo.

**Estatísticas**
* **RF-20:** O sistema deve exibir uma matriz visual de consistência no estilo GitHub.
* **RF-21:** O sistema deve exibir um gráfico de barras empilhadas comparando Realizado e Não Realizado.
* **RF-22:** O sistema deve exibir um gráfico de barras empilhadas comparando Meta e Real.
* **RF-23:** O sistema deve permitir filtrar as estatísticas por período: diário, semanal e mensal.
* **RF-24:** O sistema deve disponibilizar um calendário integrado para consulta das estatísticas.

**Onboarding e Notificações**
* **RF-25:** O sistema deve apresentar um fluxo de onboarding no primeiro acesso.
* **RF-26:** O onboarding deve permitir configurar metas e hábitos iniciais.
* **RF-27:** O onboarding deve permitir a escolha do ovo.
* **RF-28:** O sistema deve enviar notificações de reengajamento.
* **RF-29:** O sistema deve enviar avisos quando a sequência (streak) do usuário estiver em risco.
* **RF-30:** O sistema deve permitir que o usuário configure suas notificações.

---

### Seção 3: Parâmetros, Dificuldade e Cálculo de Progressão

* **RF-01:** O sistema deve exigir um identificador textual (Nome do Hábito) em cada cadastro.
* **RF-02:** O sistema deve permitir a categorização do hábito (Saúde, Estudos, Produtividade, Finanças, Desenvolvimento Pessoal, Outro).
* **RF-03:** O sistema deve permitir a definição do Tipo de Meta (Realização, Tempo, Quantidade) e da Frequência (Diária, Semanal, Mensal).
* **RF-04:** O sistema deve gerar automaticamente a dificuldade/peso do hábito, com permissão para que o usuário faça o ajuste manual.
* **RF-05:** O sistema deve atribuir pesos por dificuldade variando em: 5 (Fácil), 10 (Médio), 15 (Difícil) e 20 (Muito Difícil).
* **RF-06:** O sistema deve limitar o progresso diário a um Teto Diário de XP fixo em 25 pontos.
* **RF-07:** O sistema deve calcular o XP do dia através da fórmula de porcentagem ponderada: (Peso Realizado / Peso Total do Dia) x Teto Diário.
* **RF-08:** O sistema deve calcular hábitos com frequência semanal em um pool próprio apenas ao término do ciclo semanal, evitando distorcer a média diária.

---

### Seção 4: Sistema de Níveis, Evolução e Santuário

**Nível vitalício**
* **RF-01:** O sistema deve manter um nível global da conta que nunca seja zerado.
* **RF-02:** O sistema deve oferecer 30 níveis de progressão, somando 9.000 XP acumulados no total.
* **RF-03:** O sistema deve aplicar um aumento de aproximadamente 10% no XP exigido a cada nível.
* **RF-04:** O sistema deve acumular o XP diário tanto no nível vitalício quanto no XP do ciclo atual do dragão.

**Estágios e elementos**
* **RF-05:** O sistema deve controlar o XP do ciclo do dragão separadamente do XP vitalício.
* **RF-06:** O sistema deve evoluir o dragão de Ovo para Filhote ao atingir 120 XP no ciclo.
* **RF-07:** O sistema deve evoluir o dragão para a forma Jovem ao atingir 1.000 XP no ciclo.
* **RF-08:** O sistema deve evoluir o dragão para a forma Adulta ao atingir 3.500 XP no ciclo.
* **RF-09:** Ao atingir 6.000 XP no ciclo, o sistema deve solicitar ao usuário a escolha de um elemento (Fogo, Terra, Água ou Ar).
* **RF-10:** O sistema deve alterar o cenário de fundo de acordo com o elemento escolhido.
* **RF-11:** O sistema deve evoluir o dragão para a forma adulta final ao atingir 9.000 XP no ciclo, com aparência correspondente ao elemento escolhido.
* **RF-12:** O sistema deve registrar a trajetória de elementos escolhidos durante o ciclo.

**Renascimento e Santuário**
* **RF-13:** Ao concluir a forma adulta final, o sistema deve gerar automaticamente um novo ovo.
* **RF-14:** No renascimento, o sistema deve zerar o XP do ciclo do dragão.
* **RF-15:** No renascimento, o sistema deve preservar o nível vitalício do usuário.
* **RF-16:** No renascimento, o sistema deve preservar as insígnias do usuário.
* **RF-17:** No renascimento, o sistema deve enviar o dragão concluído ao Santuário.
* **RF-18:** O sistema deve armazenar permanentemente os dragões concluídos no Santuário.
* **RF-19:** Cada entrada do Santuário deve exibir o nome do dragão.
* **RF-20:** Cada entrada do Santuário deve exibir a arte do dragão adulto em seu bioma.
* **RF-21:** Cada entrada do Santuário deve exibir a data de nascimento e a data de término do ciclo.
* **RF-22:** Cada entrada do Santuário deve exibir a trajetória de elementos escolhidos.
* **RF-23:** Cada entrada do Santuário deve exibir o tempo total que o ciclo levou para ser concluído.

---

### Seção 5: Estatísticas e Analíticas Visuais

* **RF-01:** O sistema deve exibir um gráfico em grade (estilo GitHub) demonstrando a intensidade do cumprimento diário de hábitos.
* **RF-02:** O sistema deve gerar gráficos de barras empilhadas comparando a quantidade de hábitos concluídos versus não concluídos.
* **RF-03:** O sistema deve apresentar visualmente o desempenho planejado/esperado versus o realizado, segmentado por categoria.
* **RF-04:** O sistema deve manter um indicador (Contador de Abstinência) do tempo e dias consecutivos em que o usuário permaneceu sem praticar um hábito nocivo.
* **RF-05:** O sistema deve consolidar o tempo total (horas e minutos) dedicado às metas baseadas em tempo no Painel de Foco e Produtividade.

---

### Seção 6: Biblioteca Inicial de Hábitos

* **RF-01:** O sistema deve prover uma biblioteca pré-configurada de sugestões de hábitos categorizada em: Saúde, Estudos, Produtividade, Finanças e Desenvolvimento Pessoal.
* **RF-02:** O sistema deve associar automaticamente configurações recomendadas (como Tipo de Meta e Dificuldade Padrão) aos hábitos selecionados da biblioteca inicial.
