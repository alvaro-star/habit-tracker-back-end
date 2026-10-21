# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

Back-end for a custom habit tracker: a JSON-only API built with Laravel 13 (PHP ^8.3) and authenticated with Laravel Sanctum. There is no web front-end in this repo; `resources/` and Vite are untouched skeleton leftovers. It is a university project, and the team's docs and commit messages are in Portuguese.

**Current state:** `main` holds a fresh Laravel skeleton (merged in PR #3 from `criando-projeto`). The only domain model is `User`; no habit models, controllers, requests or resources exist yet, and `routes/api.php` only has the default `GET /api/user`. Expect to build the habit domain from scratch.

`origin/Feature/Inicializando-projeto` is an older, unmerged base that also carries Laravel Boost agent files (`.claude/skills`, `.agents/skills`, a Boost-generated `CLAUDE.md`/`AGENTS.md`). `boost.json` on `main` lists those skills, but they are not committed here. If Boost ever regenerates `CLAUDE.md`, it writes inside `<laravel-boost-guidelines>` tags, so keep hand-written content outside that block.

## Commands

```bash
composer setup                     # install deps, copy .env, key:generate, migrate, npm install + build
composer dev                       # runs `php artisan dev` (server, queue, logs, vite)
php artisan serve                  # API only, at http://localhost:8000
composer test                      # config:clear + php artisan test
php artisan test --compact
php artisan test --filter=HabitTest              # single test class or method
php artisan test tests/Feature/HabitTest.php     # single file
vendor/bin/pint --dirty            # format changed PHP files (Laravel Pint)
php artisan route:list --path=api
php artisan migrate:fresh --seed
```

Tests use PHPUnit (not Pest). Create them with `php artisan make:test XTest --phpunit`. `phpunit.xml` runs tests against in-memory SQLite, so tests do not need Postgres; avoid Postgres-only SQL in migrations and queries, or tests will break.

## Database

`.env.example` targets **PostgreSQL** at `127.0.0.1:5433`, with database `habit_tracker` and user `laravel`/`secret`. The matching Postgres 17 service in `compose.yaml` is fully commented out, so the Docker setup isn't finished. A local `docker/postgres/data/` directory (root-owned, causes a harmless git permission warning) is left over from an earlier container.

## Architecture

The API is layered. A request flows **route → Form Request → controller → service → model → database**, and the response goes back out through an API Resource. Keep each concern in its layer:

| Layer | Location | Responsibility |
|---|---|---|
| Routes | `routes/api.php` | Endpoint definitions, served under `/api`. Group protected routes under `auth:sanctum`; prefer `Route::apiResource()` for CRUD. |
| Form Requests | `app/Http/Requests` | Validation and authorization of input (`make:request StoreHabitRequest`). Controllers receive already-validated data via `$request->validated()`. |
| Controllers | `app/Http/Controllers/Api` | Thin HTTP layer: take the validated request, call a service, return a Resource. No business logic or queries here. Scaffold with `make:controller Api/HabitController --api --model=Habit`. |
| Services | `app/Services` | Business rules (e.g. checking a habit off for a day, computing streaks). Plain classes injected into controllers by the container. The folder doesn't exist yet; create it with the first service. |
| Models | `app/Models` | Eloquent models: relationships, casts, scopes. Habit data is user-owned, so scope queries to the authenticated user. |
| API Resources | `app/Http/Resources` | JSON shape of responses (`make:resource HabitResource`). Never return models directly. |
| Migrations / factories / seeders | `database/` | Schema changes and test/dev data. Create together with the model via `make:model Habit -mfs`. |

Cross-cutting behaviour:

- Sanctum's `personal_access_tokens` migration is present, and `User` is the token holder.
- `bootstrap/app.php` forces exceptions (including validation errors) to render as JSON for `api/*` routes and for requests that expect JSON, so controllers don't need to format errors.

## Repository layout beyond Laravel defaults

- `docs/`: project documentation.
- `docs/config/skills/`: agent skill definitions shared by the team (currently empty).
- `docs/config/mcp/`: MCP server configuration for agents (currently empty). The active MCP config is still `.mcp.json` at the repo root (Claude Code) and `opencode.json` (OpenCode).

Laravel Boost (dev dependency) provides an MCP server through `.mcp.json` (`php artisan boost:mcp`) with `search-docs`, `database-schema` and `database-query` tools. Use `search-docs` for version-specific Laravel 13 APIs.

## Git conventions (from README)

- Branches: `Feature/`, `Fix/`, `Refactor/`, `Docs/` followed by a description, e.g. `Feature/Nome-da-tarefa`. Branch off an up-to-date `main`.
- Commit prefixes: `feature:`, `fix:`, `refactor:`, `docs:`, `test:`, `chore:`.
