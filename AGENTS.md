# AGENTS.md

API Laravel 13 (PHP ^8.3) somente JSON, com autenticação Sanctum. Sem front-end: `resources/` e o Vite são sobras intocadas do esqueleto. A documentação e as mensagens de commit da equipe são em português.

## Propósito do projeto

Este repositório é o **back-end** do Habit Tracker, um sistema para gerenciar e acompanhar rotinas diárias, semanais e mensais, com gamificação inspirada em RPGs e bichinhos virtuais. O objetivo é construir a API REST que implementa a especificação de `docs/ESCOPO.md`: cadastro e acompanhamento de hábitos, cálculo de XP ponderado, nível vitalício, evolução do dragão, Santuário, estatísticas, onboarding e notificações. A API vai atender um front-end que fica fora deste repositório.

## Comandos

```bash
composer test                                # config:clear + php artisan test
php artisan test --filter=HabitTest          # uma classe/método de teste
vendor/bin/pint --dirty                      # formata os arquivos PHP alterados
php artisan serve                            # API em http://localhost:8000
composer dev                                 # php artisan dev (servidor + fila + logs + vite)
```

Os testes são **PHPUnit, não Pest**: crie com `php artisan make:test Habits/HabitTest --phpunit`.

## Cuidado com os testes

O `phpunit.xml` força SQLite em memória, então os testes nunca tocam no Postgres. Evite SQL exclusivo do Postgres nas migrations e queries, ou a suíte de testes quebra.

## Banco de dados

- O `.env.example` indica Postgres em `127.0.0.1:5433`, mas o `.env` real usa **porta 5455, banco `public`, usuário `admin_system`**: confie no `.env`.
- O serviço Postgres no `compose.yaml` está todo comentado; o setup com Docker não está pronto. `docker compose up` não faz nada. O Postgres precisa já estar rodando localmente.
- `docker/postgres/data/` é uma sobra de propriedade do root e causa um aviso de permissão inofensivo no git: ignore.

## Arquitetura

Fluxo da requisição: **rota → Form Request → controller → service → model → banco de dados**, com as respostas formatadas por API Resources. Os controllers são finos (entrada validada via `$request->validated()`, delegam para um service, devolvem um Resource, nunca um model). Os dados de hábitos pertencem ao usuário: filtre as queries pelo usuário autenticado.

O `bootstrap/app.php` já renderiza todas as exceções (inclusive erros de validação) como JSON em `api/*`: não adicione formatação de erros nos controllers.

## Módulos e estrutura de pastas

O back-end é dividido em 8 módulos (detalhes em `docs/MODULOS.md`, requisitos em `docs/RF-MODULOS.md`). Cada módulo tem uma subpasta própria em cada camada e um prefixo de rota:

| Módulo | Pasta (`<Modulo>`) | Prefixo da rota | Arquivo de rotas |
|---|---|---|---|
| MOD1 · Conta e autenticação | `Auth` | `/api/auth` | `routes/api/auth.php` |
| MOD2 · Biblioteca de hábitos | `Library` | `/api/library` | `routes/api/library.php` |
| MOD3 · Gestão de hábitos | `Habits` | `/api/habits` | `routes/api/habits.php` |
| MOD4 · Registro diário (check-in) | `CheckIns` | `/api/check-ins` | `routes/api/check-ins.php` |
| MOD5 · Progressão e XP | `Progression` | `/api/progression` | `routes/api/progression.php` |
| MOD6 · Dragão e Santuário | `Dragon` | `/api/dragon` e `/api/sanctuary` | `routes/api/dragon.php` |
| MOD7 · Estatísticas | `Statistics` | `/api/statistics` | `routes/api/statistics.php` |
| MOD8 · Onboarding e Notificações | `Onboarding` e `Notifications` | `/api/onboarding` e `/api/notifications` | `routes/api/onboarding.php` e `routes/api/notifications.php` |

Onde cada coisa fica:

| Camada | Pasta | Exemplo (MOD3) |
|---|---|---|
| Rotas | `routes/api/<modulo>.php`, carregado por `routes/api.php` | `routes/api/habits.php` |
| Form Requests | `app/Http/Requests/<Modulo>` | `Habits/StoreHabitRequest.php` |
| Controllers | `app/Http/Controllers/Api/<Modulo>` | `Api/Habits/HabitController.php` |
| Services | `app/Services/<Modulo>` | `Habits/HabitService.php` |
| Resources | `app/Http/Resources/<Modulo>` | `Habits/HabitResource.php` |
| Models | `app/Models` (sem subpastas, compartilhados entre módulos) | `Habit.php` |
| Migrations, factories, seeders | pastas padrão de `database/` | `create_habits_table` |
| Testes | `tests/Feature/<Modulo>` | `Habits/HabitTest.php` |

Regras:

- Todas as rotas ficam em `auth:sanctum`, exceto cadastro e login do MOD1.
- Os nomes de pastas, classes e rotas seguem as convenções do Laravel, em inglês. As tabelas de `docs/DER.md` estão em português e são mapeadas para esses models (ex.: `habito` → `Habit`).
- O MOD8 também usa `app/Notifications` e `app/Jobs`, com o agendamento em `routes/console.php`.

Estado atual: nenhum desses módulos foi construído. Só existe `User`, e `routes/api.php` tem apenas o `GET /api/user` padrão. As pastas por módulo ainda não existem: crie-as junto com o primeiro arquivo de cada uma, ex.: `make:model Habit -mfs`, `make:controller Api/Habits/HabitController --api --model=Habit`, `make:request Habits/StoreHabitRequest`, `make:resource Habits/HabitResource`.

## Documentação

- `docs/ESCOPO.md`: especificação do produto (regras de XP, níveis, dragão, estatísticas, onboarding).
- `docs/DER.md`: modelo ER e enums.
- `docs/MODULOS.md`: divisão do back-end em módulos.
- `docs/config/mcp/LINEAR.md`: como configurar o MCP do Linear.

## Convenções de git

- Branches: `Feature/`, `Fix/`, `Refactor/`, `Docs/` + descrição, a partir de uma `main` atualizada.
- Commits: prefixos `feature:`, `fix:`, `refactor:`, `docs:`, `test:`, `chore:` (em português).

## Ferramentas

- O `CLAUDE.md` tem uma descrição mais completa das mesmas convenções, se precisar de mais contexto.
