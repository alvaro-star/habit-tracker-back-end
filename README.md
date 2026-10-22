# Habit Tracker — back-end

API do Habit Tracker em Laravel 13.

## Primeiros passos

Copie o `.env.example` para `.env`, defina as variáveis que achar adequadas e, em seguida, siga estes passos:

```bash
composer install                    # instala as dependências (antes de qualquer outro comando)
docker compose up -d db-database    # desenvolvimento: sobe só o banco de dados
php artisan key:generate            # gera a chave da aplicação
php artisan migrate                 # roda as migrations
php artisan serve                   # inicia o servidor
```

## Arquitetura do projeto

Uma requisição percorre as camadas nesta ordem: **rota → controlador → serviço → model → banco**.

| Parte | Pasta | O que é |
|---|---|---|
| Routes | `routes/api.php` e `routes/api/<modulo>.php` | Define os endpoints da API (prefixo `/api`) e qual controlador atende cada um. |
| Form Request | `app/Http/Requests/<Modulo>` | Valida os dados de entrada antes de chegar ao controlador. |
| Controlador | `app/Http/Controllers/Api/<Modulo>` | Recebe a requisição, chama o serviço e devolve a resposta em JSON. |
| Serviço | `app/Services/<Modulo>` | Concentra as regras de negócio (ex.: marcar um hábito como feito, calcular sequências). |
| Model | `app/Models` | Representa uma tabela do banco e seus relacionamentos (Eloquent). |
| Resource | `app/Http/Resources/<Modulo>` | Define o formato JSON das respostas. |
| Migration | `database/migrations` | Cria e altera as tabelas do banco, versionando o esquema. |

## Módulos

O back-end é dividido em 8 módulos (descritos em `docs/MODULOS.md`). Cada módulo tem a sua pasta dentro de cada camada e o seu prefixo de rota:

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

Estrutura de pastas, usando o MOD3 como exemplo:

```
app/
├── Http/
│   ├── Controllers/Api/Habits/HabitController.php
│   ├── Requests/Habits/StoreHabitRequest.php
│   └── Resources/Habits/HabitResource.php
├── Models/Habit.php              # os models ficam todos juntos, pois são usados por vários módulos
└── Services/Habits/HabitService.php
routes/
├── api.php                       # carrega os arquivos de routes/api/
└── api/habits.php                # rotas do módulo, com o prefixo /api/habits
tests/Feature/Habits/HabitTest.php
```

As migrations, factories e seeders ficam nas pastas padrão de `database/`, sem subpastas. O MOD8 também usa `app/Notifications` e `app/Jobs`, e o agendamento fica em `routes/console.php`.

Outras pastas:

- `tests/`: testes automatizados (PHPUnit).
- `docs/`: documentação do projeto; `docs/config/skills` e `docs/config/mcp` guardam as configurações de skills e de servidores MCP dos agentes de IA.

## Docker

> A configuração do Docker ainda será definida. Por enquanto, os comandos básicos:

```bash
docker compose up -d        # sobe os serviços em segundo plano (não rode)
docker compose ps           # lista os serviços e o status
docker compose stop         # para os serviços
docker compose down         # remove os contêineres
```

## Git

```bash
git switch main && git pull origin main     # atualiza a main
git switch -c Feature/Nome-da-tarefa        # cria uma branch para a tarefa
git status                                  # o que mudou
git add .                                   # adiciona as alterações
git commit -m "feature: descrição"          # commita
git push -u origin Feature/Nome-da-tarefa   # envia a branch
git merge main                              # atualiza a branch com a main
```

Padrão de branches: `Feature/`, `Fix/`, `Refactor/`, `Docs/`.
Padrão de commits: `feature:`, `fix:`, `refactor:`, `docs:`, `test:`, `chore:`.

## Artisan

```bash
php artisan key:generate                  # gera a chave da aplicação
php artisan serve                         # inicia o servidor
php artisan migrate                       # roda as migrations
php artisan migrate:fresh --seed          # recria o banco e roda os seeders
php artisan route:list --path=api         # lista as rotas da API
php artisan make:model Habit -mfs         # model + migration, factory e seeder
php artisan make:controller Api/Habits/HabitController --api --model=Habit
php artisan make:request Habits/StoreHabitRequest
php artisan make:resource Habits/HabitResource
php artisan make:test Habits/HabitTest --phpunit
php artisan test --compact                # roda os testes
php artisan tinker                        # console interativo
php artisan optimize:clear                # limpa os caches
```
