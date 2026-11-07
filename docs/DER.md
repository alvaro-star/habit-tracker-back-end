# Habit Tracker — Modelo ER

```mermaid
erDiagram
    insignia {
        int id PK "increment"
        varchar(60) nome "NOT NULL"
        varchar(255) descricao "NOT NULL"
        varchar(255) icone_url "NOT NULL"
    }
    usuario {
        int id PK "increment"
        int fk_id_dragao FK "→ dragao (dragão ativo)"
        varchar(80) nome "NOT NULL"
        varchar(120) email UK "NOT NULL"
        varchar(255) senha_hash "NOT NULL"
        int xp_vitalicio "NOT NULL"
        int streak_atual "NOT NULL"
        int maior_streak "NOT NULL"
        timestamp ultimo_acesso "NOT NULL"
        boolean onboarding_concluido "NOT NULL"
        timestamp created_at "NOT NULL"
    }
    config_notificacao {
        int fk_id_usuario PK, FK "→ usuario"
        int fk_id_gatilho PK, FK "→ gatilho_notificacao"
        boolean ativo "NOT NULL"
        time horario
    }
    gatilho_notificacao {
        int id PK "increment"
        varchar(40) tipo "NOT NULL"
        canal_notificacao canal "NOT NULL"
        varchar(40) frequencia "NOT NULL"
        varchar(255) texto_base "NOT NULL"
    }
    dragao {
        int id PK "increment"
        int fk_id_usuario FK "NOT NULL · → usuario"
        varchar(40) nome "NOT NULL"
        int xp_ciclo "NOT NULL"
        estagio_dragao estagio "NOT NULL · gerado pelo xp_ciclo"
        status_dragao status "NOT NULL"
        date data_nascimento "NOT NULL"
        date data_termino
        varchar(255) arte_final_url
        int tempo_total_dias
    }
    escolha_elemento {
        int id PK "increment"
        int fk_id_dragao FK "NOT NULL · → dragao"
        int fk_id_elemento FK "NOT NULL · → elemento"
        estagio_dragao estagio "NOT NULL · estágio do dragão na escolha"
        timestamp data_escolha "NOT NULL"
    }
    xp_diario {
        int id PK "increment"
        int fk_id_usuario FK "NOT NULL · → usuario"
        int fk_id_dragao FK "NOT NULL · → dragao"
        date data "NOT NULL"
        decimal percentual_ponderado "(5,2) · NOT NULL"
        int xp_ganho "NOT NULL"
    }
    elemento {
        int id PK "increment"
        varchar(20) nome UK "NOT NULL"
        varchar(255) cenario_url "NOT NULL"
    }
    habito_modelo {
        int id PK "increment"
        int fk_id_categoria FK "NOT NULL · → categoria"
        varchar(80) nome "NOT NULL"
        tipo_meta tipo_meta "NOT NULL"
        decimal meta_padrao "(10,2) · NOT NULL"
        varchar(20) unidade_padrao "NOT NULL"
        dificuldade dificuldade_padrao "NOT NULL"
        boolean prioridade_mvp "NOT NULL"
    }
    categoria {
        int id PK "increment"
        varchar(40) nome UK "NOT NULL"
    }
    objetivo_usuario {
        int fk_id_usuario PK, FK "→ usuario"
        int fk_id_categoria PK, FK "→ categoria"
    }
    registro_habito {
        int id PK "increment"
        int fk_id_habito FK "NOT NULL · → habito"
        date data "NOT NULL"
        status_registro status "NOT NULL"
        decimal valor_realizado "(10,2)"
        decimal percentual "(5,2) · NOT NULL"
    }
    usuario_insignia {
        int fk_id_usuario PK, FK "→ usuario"
        int fk_id_insignia PK, FK "→ insignia"
        timestamp data_conquista "NOT NULL"
    }
    habito {
        int id PK "increment"
        int fk_id_usuario FK "NOT NULL · → usuario"
        int fk_id_categoria FK "NOT NULL · → categoria"
        int fk_id_modelo FK "→ habito_modelo"
        varchar(80) nome "NOT NULL"
        polaridade polaridade "NOT NULL"
        tipo_meta tipo_meta "NOT NULL"
        frequencia frequencia "NOT NULL"
        varchar(3) dia_frequencia
        decimal valor_meta "(10,2)"
        varchar(20) unidade
        dificuldade dificuldade "NOT NULL"
        int peso "NOT NULL"
        time horario_lembrete
        timestamp inicio_abstinencia
        boolean ativo "NOT NULL"
        timestamp inactivated_at "quando foi desativado"
        timestamp created_at "NOT NULL"
    }

    usuario ||--o{ objetivo_usuario : "fk_id_usuario"
    categoria ||--o{ objetivo_usuario : "fk_id_categoria"
    categoria ||--o{ habito_modelo : "fk_id_categoria"
    usuario ||--o{ habito : "fk_id_usuario"
    categoria ||--o{ habito : "fk_id_categoria"
    habito_modelo |o--o{ habito : "fk_id_modelo"
    habito ||--o{ registro_habito : "fk_id_habito"
    usuario ||--o{ xp_diario : "fk_id_usuario"
    dragao ||--o{ xp_diario : "fk_id_dragao"
    dragao |o--o| usuario : "fk_id_dragao (ativo)"
    usuario ||--o{ dragao : "fk_id_usuario"
    dragao ||--o{ escolha_elemento : "fk_id_dragao"
    elemento ||--o{ escolha_elemento : "fk_id_elemento"
    usuario ||--o{ usuario_insignia : "fk_id_usuario"
    insignia ||--o{ usuario_insignia : "fk_id_insignia"
    usuario ||--o{ config_notificacao : "fk_id_usuario"
    gatilho_notificacao ||--o{ config_notificacao : "fk_id_gatilho"
```

## Enums

| Enum | Valores |
|---|---|
| `tipo_meta` | `REALIZACAO`, `TEMPO`, `QUANTIDADE` |
| `dificuldade` | `FACIL`, `MEDIO`, `DIFICIL`, `MUITO_DIFICIL` |
| `polaridade` | `BOM`, `RUIM` |
| `frequencia` | `DIARIA`, `SEMANAL`, `MENSAL` |
| `status_registro` | `CONCLUIDO`, `EM_ANDAMENTO`, `NAO_CONCLUIDO` |
| `status_dragao` | `ATIVO`, `SANTUARIO` |
| `estagio_dragao` | `OVO` (0 XP), `FILHOTE` (120), `JOVEM` (1.000), `ADULTO` (3.500), `ADULTO_FINAL` (9.000) |
| `canal_notificacao` | `PUSH`, `IN_APP` |
| `dia_semana` | `SEG`, `TER`, `QUA`, `QUI`, `SEX`, `SAB`, `DOM` |

O estágio de crescimento do dragão é o enum `estagio_dragao` (ESCOPO §4.2), guardado em `dragao.estagio` e atualizado sempre que o `xp_ciclo` muda; o marco de 6.000 XP (escolha do elemento) não é um estágio. Não há tabelas de estágio nem de ovo: o dragão não registra de que ovo nasceu.

Um usuário pode ter vários dragões (os do Santuário, com status `SANTUARIO`), mas só um `ATIVO`, apontado por `usuario.fk_id_dragao` (null quando não há dragão ativo).

A tabela `registro_habito` tem um índice composto em (`fk_id_habito`, `data`): o check-in de um hábito num dia é buscado por esse par, e o POST da API marca (cria ou atualiza) ou desmarca (exclui) esse registro.

A tabela `xp_diario` é o histórico do XP de cada dia (um registro por usuário e dia, com o dragão do ciclo). Ela é gravada pelo sistema, não pelo usuário: o registro do dia é recalculado a cada check-in marcado ou desmarcado e, no caso de hoje, também quando um hábito é criado ou desativado. A API só a lista.

O nível do usuário (1 a 30) não tem tabela nem coluna: é calculado pelo `NivelService` a partir do `usuario.xp_vitalicio`, com os intervalos gerados por `config/progression.php` (nível 1 em 0 XP, passos ~10% maiores e nível 30 em 9.000 XP).

O `habito.dia_frequencia` depende da `frequencia`: é `null` no `DIARIA`, um `dia_semana` no `SEMANAL` e um dia do mês de `1` a `31` no `MENSAL`. Um hábito mensal num dia que o mês não tem (ex.: 31 em abril) não aparece naquele mês. A API exige os dois campos juntos: quem envia a `frequencia` envia também o `dia_frequencia`, e vice-versa.
