# AGENTS.md

JSON-only Laravel 13 API (PHP ^8.3) with Sanctum auth. No frontend — `resources/` and Vite are untouched skeleton leftovers. Team docs and commit messages are in Portuguese.

## Commands

```bash
composer test                                # config:clear + php artisan test
php artisan test --filter=HabitTest          # single test class/method
vendor/bin/pint --dirty                      # format changed PHP files
php artisan serve                            # API at http://localhost:8000
composer dev                                 # php artisan dev (server + queue + logs + vite)
```

Tests are **PHPUnit, not Pest** — create with `php artisan make:test XTest --phpunit`.

## Testing gotcha

`phpunit.xml` forces in-memory SQLite, so tests never touch Postgres. Avoid Postgres-only SQL in migrations and queries or the test suite breaks.

## Database

- `.env.example` says Postgres at `127.0.0.1:5433`, but the actual `.env` uses **port 5455, database `public`, user `admin_system`** — trust `.env`.
- The Postgres service in `compose.yaml` is fully commented out; Docker setup is unfinished. `docker compose up` does nothing. Postgres must already be running locally.
- `docker/postgres/data/` is a root-owned leftover and causes a harmless git permission warning — ignore it.

## Architecture

Request flow: **route → Form Request → controller → service → model → database**, responses shaped by API Resources. Controllers live in `app/Http/Controllers/Api` and stay thin (validated input via `$request->validated()`, delegate to a service, return a Resource — never a model). `app/Services` does not exist yet; create it with the first service. Habit data is user-owned: scope queries to the authenticated user.

Current state: the habit domain is unbuilt. Only `User` exists; `routes/api.php` has just the default `GET /api/user`. Scaffold with `make:model Habit -mfs`, `make:controller Api/HabitController --api --model=Habit`, `make:request`, `make:resource`.

`bootstrap/app.php` already renders all exceptions (including validation errors) as JSON for `api/*` — don't add error formatting in controllers.

## Git conventions

- Branches: `Feature/`, `Fix/`, `Refactor/`, `Docs/` + description, off an up-to-date `main`.
- Commits: `feature:`, `fix:`, `refactor:`, `docs:`, `test:`, `chore:` prefixes (in Portuguese).

## Tooling

- Laravel Boost MCP server is configured (`opencode.json` / `.mcp.json`); use its `search-docs` for version-specific Laravel 13 APIs and `database-schema`/`database-query` to inspect the live DB.
- `CLAUDE.md` has a fuller description of the same conventions if more context is needed.
