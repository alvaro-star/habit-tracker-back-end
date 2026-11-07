# CLAUDE.md

Este arquivo orienta o Claude Code (claude.ai/code) ao trabalhar com o código deste repositório.

## Idioma

A linguagem padrão do projeto é o **português**: documentação, mensagens de commit, descrições de PR e de issues, comentários no código e a comunicação com a equipe. A exceção são os identificadores do código (classes, pastas, rotas), que seguem as convenções do Laravel em inglês.

## Projeto

Back-end de um habit tracker personalizado: uma API somente JSON feita com Laravel 13 (PHP ^8.3) e autenticada com Laravel Sanctum. Não há front-end web neste repositório; `resources/` e o Vite são sobras intocadas do esqueleto.

**Estado atual:** os models, migrations, repositories e services de todas as tabelas do DER existem (MOD0), e cada service tem um controller com CRUD exposto em `apiResource` no prefixo do seu módulo. As estatísticas (MOD7) têm endpoints próprios. Ainda faltam as regras de negócio de cada módulo (cálculo de XP, evolução do dragão, onboarding etc.) e a autenticação real. Todas as rotas estão documentadas no Swagger (Scramble) em `/docs/api`.

O `app/Http/Controllers/Api/AuthController.php` é um **mock temporário**: `POST /api/login` aceita só credenciais fixas no código e `POST /api/refresh` devolve tokens estáticos (`accessToken`/`refreshToken`), sem Sanctum nem banco. O `GET /api/user` padrão continua protegido por `auth:sanctum`, então os tokens do mock não funcionam nele. O MOD1 deve substituir esse mock por autenticação real.

## Objetivo do projeto na disciplina

É um projeto universitário, e a parte deste repositório é o **back-end**: construir a API REST que implementa a especificação de produto de `docs/ESCOPO.md` (versão 2.0, "Módulo 4 – Gamificação e Conteúdo"). A API precisa cobrir o ciclo completo do usuário: cadastrar hábitos, registrar o que fez no dia, ganhar XP ponderado, subir de nível, evoluir o dragão até o Santuário, consultar estatísticas, passar pelo onboarding e receber notificações. Tudo isso serve a um front-end que fica fora deste repositório.

Ao trabalhar aqui, siga como fontes de verdade:

- `docs/ESCOPO.md`: as regras de negócio (fórmula de XP, pesos, marcos do dragão, níveis).
- `docs/DER.md`: o modelo de dados e os enums.
- `docs/MODULOS.md`: a divisão em módulos. Os requisitos de cada módulo estão em `docs/RF-MODULOS.md`, um arquivo local fora do git que pode não existir em todas as máquinas.

## Comandos

```bash
composer setup                     # instala dependências, copia o .env, key:generate, migrate, npm install + build
composer dev                       # roda `php artisan dev` (servidor, fila, logs, vite)
docker compose up -d db-database   # sobe só o Postgres para desenvolvimento
php artisan serve                  # só a API, em http://localhost:8000
composer test                      # config:clear + php artisan test
php artisan test --compact
php artisan test --filter=HabitTest                     # uma classe ou método de teste
php artisan test tests/Feature/Habits/HabitTest.php     # um arquivo
vendor/bin/pint --dirty            # formata os arquivos PHP alterados (Laravel Pint)
php artisan route:list --path=api
php artisan migrate:fresh --seed
```

Os testes usam PHPUnit (não Pest). Crie-os com `php artisan make:test Habits/HabitTest --phpunit`. O `phpunit.xml` roda os testes em SQLite em memória, então os testes não precisam do Postgres; evite SQL exclusivo do Postgres nas migrations e queries, ou os testes quebram.

## Banco de dados

O `.env.example` aponta para **PostgreSQL** em `127.0.0.1:5433`, com o banco `habit_tracker` e o usuário `laravel`/`secret`. O `docker-compose.yaml` tem dois serviços, que leem as variáveis `DB_*` do `.env`:

- `db-database`: Postgres 17, exposto na porta `DB_PORT` (padrão 5433), com os dados em um volume nomeado.
- `app`: a aplicação (`Dockerfile` + `nginx.conf`), exposta na porta `APP_PORT` (padrão 8080).

No desenvolvimento, suba só o banco com `docker compose up -d db-database` e rode a API com `php artisan serve`. O diretório `docker/postgres/data/` é uma sobra de um container antigo e está no `.gitignore`.

## Arquitetura

A API é dividida em camadas. Uma requisição percorre **rota → Form Request → DTO → controller → service → repository → model → banco de dados**, e a resposta volta por um API Resource. Mantenha cada responsabilidade na sua camada:

| Camada | Local | Responsabilidade |
|---|---|---|
| Rotas | `routes/api/<modulo>.php` | Definição dos endpoints, servidos sob `/api` com o prefixo do módulo. O `routes/api.php` só carrega esses arquivos. Agrupe as rotas protegidas em `auth:sanctum` e use `Route::apiResource()` com o parâmetro `{id}` (`->parameters(['habits' => 'id'])`), que o `Route::pattern` do `routes/api.php` restringe a números. |
| Form Requests | `app/Http/Requests/<Modulo>` | Validação da entrada. Há uma request por entidade (ex.: `Habits/HabitoRequest`), que estende `App\Http\Requests\ApiRequest`: o `$this->obrigatorio()` devolve `required` no POST e `sometimes` no PUT/PATCH, então a mesma request atende criação e edição parcial. Cada request implementa `toDto()`, que cria o DTO a partir de `$this->validated()`, e leva `@ignoreSchema` no docblock para o Swagger documentar POST e PUT separadamente. |
| DTOs | `app/DTOs/<Modulo>` | Objetos PHP imutáveis (`final readonly class`, estendem `App\DTOs\DTO`) que representam os dados vindos da requisição, já com os tipos do PHP: inteiros, `float`, `bool`, enums de `app/Enums` e datas em `CarbonImmutable`. São criados por `XxxDTO::fromArray()` (chamado pelo `toDto()` da request) e convertidos de volta para as colunas do banco por `toArray()`, que descarta os campos `null`. Por isso um PATCH não consegue limpar um campo opcional enviando `null`. |
| Controllers | `app/Http/Controllers/Api/<Modulo>` | Camada HTTP fina, um controller por service: recebe a request, passa `$request->toDto()` ao service e devolve um Resource. Sem regra de negócio nem queries aqui. |
| Services | `app/Services` | Regras de negócio (ex.: o peso padrão por dificuldade em `HabitoService`). Estendem `BaseService`, que oferece o CRUD (`listar`, `buscar`, `criar`, `atualizar`, `excluir`) e as variantes restritas ao usuário (`listarDoUsuario`, `criarParaUsuario` etc.). Aceitam um DTO ou um array. |
| Repositories | `app/Repositories` | Queries Eloquent, uma classe por model, estendendo `BaseRepository`. O `consultaDoUsuario()` filtra os dados pessoais pela coluna `fk_id_usuario`; tabelas sem essa coluna (`registro_habito`, `escolha_elemento`) o sobrescrevem para chegar ao dono pelo relacionamento. |
| Models | `app/Models` | Models Eloquent: relacionamentos, casts, scopes. Ficam todos juntos, sem subpastas, porque vários módulos usam os mesmos models. Os dados de hábitos pertencem ao usuário, então filtre as queries pelo usuário autenticado. |
| API Resources | `app/Http/Resources/<Modulo>` | Formato JSON das respostas (`make:resource Habits/HabitResource`). Nunca devolva models diretamente. |
| Migrations / factories / seeders | `database/` | Mudanças de schema e dados de teste/desenvolvimento, nas pastas padrão, sem subpastas. Crie junto com o model via `make:model Habit -mfs`. |
| Testes | `tests/Feature/<Modulo>` | Testes de feature de cada módulo. |

Comportamentos transversais:

- A migration `personal_access_tokens` do Sanctum já existe, e `User` é o dono dos tokens.
- O `bootstrap/app.php` força as exceções (inclusive erros de validação) a serem renderizadas como JSON nas rotas `api/*` e nas requisições que esperam JSON, então os controllers não precisam formatar erros.
- Dados pessoais (hábitos, check-ins, dragões, XP diário, escolhas de elemento) só são vistos pelo dono: um id de outro usuário devolve 404. Quando um registro aponta para outro dado pessoal (ex.: o `fk_id_habito` de um check-in), a request valida a posse com `Rule::exists(...)->where('fk_id_usuario', $this->user()?->id)` e devolve 422. As demais tabelas (categorias, modelos, níveis, insígnias, ovos, estágios, elementos, gatilhos) são catálogos, com CRUD liberado a qualquer usuário autenticado, porque ainda não existe papel de administrador.
- Resources de tabelas com uma coluna chamada `data` (`RegistroHabitoResource`, `XpDiarioResource`) declaram `public static bool $forceWrapping = true`, senão o Laravel confunde a coluna com o envelope `{"data": ...}` e não embrulha a resposta.

## Módulos

O código é organizado em 8 módulos. `<Modulo>` nas pastas acima é o nome da pasta do módulo:

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

- Todas as rotas ficam em `auth:sanctum`, exceto cadastro e login do MOD1.
- O `AuthController` mock ainda está fora dessa estrutura (`Api/AuthController.php`, rotas `/api/login` e `/api/refresh` sem prefixo). Ao implementar o MOD1, mova-o para `Api/Auth/` e as rotas para `routes/api/auth.php`, sob `/api/auth`.
- As tabelas de `docs/DER.md` estão em português e são mapeadas para models em inglês (ex.: `habito` → `Habit`, `registro_habito` → `CheckIn`). O `usuario` do DER corresponde ao `User` que já existe.
- O MOD8 está separado dos demais (é a seção 7 do escopo) e ainda não tem issues no Linear. Além das pastas do módulo, ele usa `app/Notifications` e `app/Jobs`, com o agendamento em `routes/console.php`.
- A ordem de desenvolvimento segue a numeração, porque cada módulo depende dos anteriores.

## Estrutura do repositório além do padrão Laravel

- `docs/`: documentação do projeto (`ESCOPO.md`, `DER.md`, `MODULOS.md`).
- `docs/config/skills/`: definições de skills de agentes compartilhadas pela equipe (vazia por enquanto).
- `docs/config/mcp/`: configuração de servidores MCP para agentes (`LINEAR.md` explica como configurar o MCP do Linear). A configuração MCP ativa continua sendo o `.mcp.json` na raiz (Claude Code) e o `opencode.json` (OpenCode).

## Convenções de git (do README)

- Branches: `Feature/`, `Fix/`, `Refactor/`, `Docs/` seguido de uma descrição, ex.: `Feature/Nome-da-tarefa`. Crie a branch a partir de uma `main` atualizada.
- Prefixos de commit: `feature:`, `fix:`, `refactor:`, `docs:`, `test:`, `chore:`.
