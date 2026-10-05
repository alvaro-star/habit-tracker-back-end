# Habit Tracker — back-end

API do Habit Tracker em Laravel 13.

## Docker

> A configuração do Docker ainda será definida. Por enquanto, os comandos básicos:

```bash
docker compose up -d        # sobe os serviços em segundo plano
docker compose ps           # lista os serviços e o status
docker compose logs -f      # acompanha os logs
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
php artisan make:controller Api/HabitController --api --model=Habit
php artisan make:request StoreHabitRequest
php artisan make:resource HabitResource
php artisan make:test HabitTest --phpunit
php artisan test --compact                # roda os testes
php artisan tinker                        # console interativo
php artisan optimize:clear                # limpa os caches
```
