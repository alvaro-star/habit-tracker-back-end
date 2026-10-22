# Módulos do Habit Tracker

O back-end está dividido em oito módulos. Cada um agrupa os requisitos de `RF-MODULOS.md`. A ordem sugerida de desenvolvimento segue a numeração, porque cada módulo depende dos anteriores.

## MOD1 · Conta e autenticação

Base de todo o sistema: cadastro, login e emissão de tokens com Laravel Sanctum, além do perfil do usuário (nome, e-mail, último acesso e status do onboarding). Os documentos não trazem RF específicos para este módulo, mas todos os outros dependem dele, já que os hábitos, o XP e o dragão pertencem a um usuário autenticado.

## MOD2 · Biblioteca de hábitos

Catálogo somente leitura com as categorias (Saúde, Estudos, Produtividade, Finanças, Desenvolvimento Pessoal e Outro) e os hábitos sugeridos da seção 6 de `ESCOPO.md`. Cada modelo já traz o tipo de meta, a meta padrão, a unidade e a dificuldade recomendada, que são copiados para o hábito quando o usuário o escolhe. Os dados entram no banco por seeders.

## MOD3 · Gestão de hábitos

Cadastro, edição, consulta e exclusão dos hábitos do usuário, com as regras de cadastro: nome obrigatório, categoria, tipo de meta, frequência e unidades customizadas. Também diferencia hábitos bons de hábitos ruins (com o início da abstinência) e define a dificuldade e o peso do hábito (5, 10, 15 ou 20), sugeridos automaticamente e ajustáveis pelo usuário.

## MOD4 · Registro diário (check-in)

Registra o que o usuário fez em cada hábito, em cada dia. Hábitos de realização recebem um status (Concluído, Em Andamento ou Não Concluído), hábitos de tempo recebem minutos por timer ou entrada manual, e hábitos de quantidade recebem uma contagem numérica. Cada registro guarda o percentual cumprido, que é a entrada do cálculo de XP.

## MOD5 · Progressão e XP

Calcula o XP do dia pela fórmula ponderada `(∑W cumpridos / ∑W total) × 25`, com os hábitos semanais apurados em um pool próprio ao fim da semana. O XP soma no nível vitalício da conta (30 níveis, 9.000 XP, crescimento de ~10% por nível), que nunca é zerado, e também no XP do ciclo do dragão atual.

## MOD6 · Dragão e Santuário

Controla o companheiro do usuário: a evolução do dragão por marcos de XP do ciclo (de Ovo a Adulto), a escolha do elemento (Fogo, Terra, Água ou Ar) que muda o cenário e a forma final, e o registro da trajetória de elementos. Ao concluir o ciclo, o dragão renasce em um novo ovo, o XP do ciclo é zerado e o dragão anterior vai para o Santuário, o álbum permanente com nome, arte, datas, trajetória e duração.

## MOD7 · Estatísticas

Módulo somente leitura que consolida os registros e o XP em relatórios: a matriz de consistência no estilo GitHub, os gráficos de barras empilhadas (realizado vs. não realizado e meta vs. real por categoria), o contador de abstinência dos hábitos ruins e o painel de foco com o tempo dedicado. Todos os relatórios aceitam filtros por período e consulta por calendário.

## MOD8 · Onboarding e Notificações

Bloco separado que corresponde à seção 7 de `ESCOPO.md`. O onboarding é o fluxo de 8 etapas do primeiro acesso e usa os outros módulos para escolher o ovo, nomear o dragão, definir objetivos e selecionar hábitos iniciais. As notificações enviam lembretes diários, avisos de streak em risco e mensagens de inatividade (3 e 7 dias), configuráveis pelo usuário, e dependem do scheduler e das filas do Laravel.

