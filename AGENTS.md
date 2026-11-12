# AGENTS.md

API Laravel 13 (PHP ^8.3) somente JSON, com autenticação Sanctum. Sem front-end: `resources/` e o Vite são sobras intocadas do esqueleto. A documentação e as mensagens de commit da equipe são em português.

## Propósito do projeto

Este repositório é o **back-end** do Habit Tracker, um sistema para gerenciar e acompanhar rotinas diárias, semanais e mensais, com gamificação inspirada em RPGs e bichinhos virtuais. O objetivo é construir a API REST que implementa a especificação de `docs/ESCOPO.md`: cadastro e acompanhamento de hábitos, cálculo de XP ponderado, nível vitalício, evolução do dragão, Santuário, estatísticas, onboarding e notificações. A API vai atender um front-end que fica fora deste repositório.

## Comandos

```bash
composer test                                # config:clear + php artisan test
php artisan test --filter=HabitoApiTest      # uma classe/método de teste
vendor/bin/pint --dirty                      # formata os arquivos PHP alterados
composer lint                                # confere o estilo (pint --test) e a análise estática (Larastan, nível 5)
php artisan serve                            # API em http://localhost:8000
composer dev                                 # php artisan dev (servidor + fila + logs + vite)
```

Rode `composer lint` e os testes antes de concluir uma tarefa; o workflow `.github/workflows/ci.yml` roda os dois em cada PR. O comando não altera arquivos: corrija o estilo com `vendor/bin/pint`. Repositories e services novos declaram o model no docblock (`/** @extends BaseRepository<Habito> */`, `/** @extends BaseService<Habito> */`).

Os testes são **PHPUnit, não Pest**: crie com `php artisan make:test Habits/HabitoApiTest --phpunit`. O trait `tests/Concerns/VerificaCrudDaApi` percorre o CRUD completo de um recurso.

## Cuidado com os testes

O `phpunit.xml` força SQLite em memória, então os testes nunca tocam no Postgres. Evite SQL exclusivo do Postgres nas migrations e queries, ou a suíte de testes quebra.

Se o PHP local não tiver o driver `pdo_sqlite` (erro `could not find driver`), rode os testes na imagem do projeto:

```bash
docker run --rm --user "$(id -u):$(id -g)" -v "$PWD":/app -w /app --entrypoint php habittracker-app:latest artisan test --compact
```

## Banco de dados

- O `.env.example` indica Postgres em `127.0.0.1:5433`, mas o `.env` local pode ter outros valores: confie no `.env`.
- O `docker-compose.yaml` tem o serviço `db-database` (Postgres 17, porta `DB_PORT`, padrão 5433) e o `app` (`Dockerfile` + nginx, porta `APP_PORT`, padrão 8080), ambos lendo as variáveis `DB_*` do `.env`. No desenvolvimento, suba só o banco: `docker compose up -d db-database`.
- `docker/postgres/data/` é uma sobra de um container antigo e está no `.gitignore`: ignore.

## Arquitetura

Fluxo da requisição: **rota → Form Request → DTO → controller → service → repository → model → banco de dados**, com as respostas formatadas por API Resources. Os controllers são finos (passam `$request->toDto()` para um service e devolvem um Resource, nunca um model). Os dados pessoais pertencem ao usuário: filtre as queries pelo usuário autenticado (`BaseService::listarDoUsuario()` e afins).

Os **DTOs** (`app/DTOs/<Modulo>`, `final readonly class` que estende `App\DTOs\DTO`) representam os dados vindos da requisição com os tipos do PHP (enums, `CarbonImmutable`, `int`, `float`, `bool`). Cada Form Request estende `App\Http\Requests\ApiRequest` e implementa `toDto()`, que chama `XxxDTO::fromArray($this->validated())`. O `toArray()` do DTO devolve as colunas do banco sem os campos `null`, o que permite PATCH parcial.

O `bootstrap/app.php` já renderiza todas as exceções (inclusive erros de validação) como JSON em `api/*`: não adicione formatação de erros nos controllers.

Cada regra de negócio fica no service do módulo dono dela (ex.: a dificuldade gerada pelo peso em `HabitoService`, o XP do dia em `XpDiarioService`); um service pode usar outro service ou repository para ler dados de outro módulo. Quando uma regra não pode ser validada só na Form Request (depende do registro salvo, ou o service é chamado sem request), lance `App\Exceptions\RegraDeNegocioException($campo, $mensagem, $status = 422)`: o `bootstrap/app.php` a converte no status da exceção (422 por padrão, ou outro como 409), no formato dos erros de validação. Regras de validação reutilizáveis ficam em `app/Rules`, e os parâmetros fixos do jogo em `config/` (ex.: `config/progression.php`, `teto_diario` = 25), não no `.env`.

O Swagger (Scramble) usa o docblock do método do controller como descrição do endpoint e o comentário `/** ... */` acima de cada regra da Form Request como descrição do campo.

## Regras de negócio implementadas

- **Peso e dificuldade:** o usuário envia o `peso` (inteiro de 1 a 20), e a `dificuldade` vem de `Dificuldade::doPeso()`: 1–5 `FACIL`, 6–10 `MEDIO`, 11–15 `DIFICIL`, 16–20 `MUITO_DIFICIL`. Uma `dificuldade` enviada é descartada.
- **Data de criação e tipo de meta:** `created_at`/`data_criacao` não podem ser enviados no hábito (422; vêm do timestamp). O `tipo_meta` só muda enquanto o hábito não tem check-ins (senão, 422 pelo `HabitoService`).
- **Exclusão de hábito:** o `DELETE /api/habits/{id}` só desativa (`ativo = false`, grava `inactivated_at`, mantém os check-ins, recalcula o XP de hoje) e só é permitido no dia em que o hábito foi criado (antes disso, 422). PATCH `ativo: false` grava o `inactivated_at`; `ativo: true` o limpa.
- **TODO:** o XP diário e o progresso das estatísticas ainda filtram por `ativo = true`; os TODOs no `HabitoRepository`, `XpDiarioService` e `StatisticService` pedem para usar o `inactivated_at`.
- **Frequência:** `habito.dia_frequencia` é `null` no `DIARIA`, `SEG`…`DOM` (`DiaSemana`) no `SEMANAL` e `1`…`31` no `MENSAL`. A `frequencia` e o `dia_frequencia` são sempre enviados juntos; a combinação é validada por `DiaFrequenciaValido` e `HabitoService::validarFrequencia()`.
- **Hábitos do dia:** `GET /api/habits/daily?data=AAAA-MM-DD` (`HabitoService::habitosDiarios()`) lista os hábitos ativos da data. Uma data inexistente devolve 422, e um hábito mensal dos dias 29 a 31 só aparece nos meses que têm esse dia.
- **Check-in:** só `GET` e `POST` em `/api/check-ins`. O POST recebe `checked` (`true` cria/atualiza pelo par `fk_id_habito` + `data`; `false` exclui, 204). Data sem dragão ativo, anterior ao nascimento do dragão ou futura → 422; anterior à criação do hábito → 409. Em qualquer data (inclusive hoje), `XpDiarioService::recalcularDia()` regrava o `xp_diario` do dia e soma a diferença no `xp_ciclo` do dragão e no `xp_vitalicio`.
- **Cumprimento do check-in:** `REALIZACAO` usa o `status` enviado (sem ele, `NAO_CONCLUIDO`); `TEMPO` e `QUANTIDADE` calculam status e percentual comparando `valor_realizado` com `habito.valor_meta` (nulo/zero → não concluído, meta atingida → concluído, parcial → em andamento).
- **XP do dia:** `XpDiarioService::xpDoDia()` calcula qualquer dia, só com hábitos ativos criados até a data que caem no dia (diários, semanais do dia da semana, mensais do dia do mês); não há pool semanal. `recalcularDia()` grava em `xp_diario` e soma a diferença no `xp_ciclo` do dragão ativo e no `xp_vitalicio`; `atualizarXpDeHoje($usuarioId)` faz isso para hoje e é chamado ao criar e ao desativar um hábito. O `XpDiarioService` usa o `HabitoRepository` (não o `HabitoService`) para evitar dependência circular.
- **Histórico de XP:** `GET /api/progression/daily-xp?data_inicio=&data_fim=` é só leitura, exige o intervalo (`DataValida`) e busca tudo no banco, inclusive hoje; não calcula nada.
- **Estágio do dragão:** sem tabelas `estagio` e `ovo`. O enum `EstagioDragao` (`OVO`, `FILHOTE` 120, `JOVEM` 1.000, `ADULTO` 3.500, `ADULTO_FINAL` 9.000 XP) fica em `dragao.estagio`, gerado pelo `xp_ciclo` (DragaoService e `somarXpCiclo()`); a `escolha_elemento.estagio` guarda o estágio na escolha.
- **Elemento do dragão:** enum `ElementoDragao` (`FOGO`, `TERRA`, `AGUA`, `AR`), sem tabela, em `dragao.elemento` e `escolha_elemento.elemento`. Obrigatório no PUT a partir dos 6.000 XP (422); a resposta traz `elemento` e `precisa_escolher_elemento`, e cada troca grava a trajetória em `escolha_elemento`. Cenário e aparência ficam com o front-end.
- **Dragão ativo:** só um dragão `ATIVO` por usuário, guardado em `users.fk_id_dragao` pelo `DragaoService` (422 em `status` ao tentar um segundo ativo).
- **XP vitalício:** `users.xp_vitalicio` (padrão 0) fica fora do `Fillable` do `User`.
- **Níveis:** não há tabela de níveis. O `NivelService` calcula sem banco: `curva()` gera os intervalos dos 30 níveis de `config/progression.php` (nível 1 em 0 XP, passos ~10% maiores, nível 30 em 9.000 XP) e `nivelDoXp($xp)` devolve o nível. `User::getNivel()` o usa com o `xp_vitalicio`.
- **Onboarding:** `users.onboarding_concluido` (padrão `false`) marca o primeiro acesso. `GET /api/onboarding` devolve a situação e `POST /api/onboarding` recebe de uma vez `ovo`, `nome_dragao`, `nome_usuario`, `objetivos` e `habitos`: o `OnboardingService` cria, numa transação, o dragão ativo, um hábito diário por modelo da biblioteca, o nome e os objetivos (`objetivo_usuario`). Segunda chamada: 409. O ovo é um texto livre em `dragao.ovo` (identificador do front-end), sem tabela nem enum.
- **Usuário logado:** `GET /api/user` (`Api/Auth/UsuarioController` + `UsuarioResource`) devolve o usuário com `xp_vitalicio`, `fk_id_dragao`, `onboarding_concluido` e o `nivel` anexado na resposta.

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
| Form Requests | `app/Http/Requests/<Modulo>` (uma por entidade, para POST e PUT/PATCH) | `Habits/HabitoRequest.php` |
| DTOs | `app/DTOs/<Modulo>` | `Habits/HabitoDTO.php` |
| Controllers | `app/Http/Controllers/Api/<Modulo>` | `Api/Habits/HabitoController.php` |
| Services | `app/Services` (estendem `BaseService`) | `HabitoService.php` |
| Repositories | `app/Repositories` (estendem `BaseRepository`) | `HabitoRepository.php` |
| Resources | `app/Http/Resources/<Modulo>` | `Habits/HabitoResource.php` |
| Models | `app/Models` (sem subpastas, compartilhados entre módulos) | `Habito.php` |
| Migrations, factories, seeders | pastas padrão de `database/` | `adicionar_dia_frequencia_em_habito` |
| Regras e exceções | `app/Rules`, `app/Exceptions` | `DiaFrequenciaValido.php` |
| Testes | `tests/Feature/<Modulo>`, `tests/Unit` | `Habits/HabitoApiTest.php` |

Regras:

- Todas as rotas ficam em `auth:sanctum`, exceto cadastro e login do MOD1.
- As pastas de módulo e as rotas seguem as convenções do Laravel, em inglês. As classes de domínio usam o nome da tabela do `docs/DER.md`, em português (`habito` → `Habito`, `HabitoService`, `HabitoController`). A exceção é o `usuario` do DER, que é a tabela `users` e o model `User`.
- O `routes/api.php` registra `Route::pattern('id', '[0-9]+')`, e as `apiResource` mapeiam o parâmetro com `->parameters(['habits' => 'id'])`: um `{id}` não numérico cai em 404 antes do controller.
- O `User` usa o atributo `#[Fillable]` (estilo Laravel 13) em vez da propriedade `$fillable`.
- O histórico de migrations cria e depois remove as tabelas `nivel`, `estagio`, `ovo` e `elemento`; o schema final não tem nenhuma delas (foram substituídas pelos enums de `app/Enums`). Não recrie essas tabelas.
- O MOD7 foge do padrão: `app/Services/Statistics/StatisticService.php` não estende `BaseService`, e as rotas são endpoints próprios, não `apiResource`.
- O MOD8 também usa `app/Notifications` e `app/Jobs`, com o agendamento em `routes/console.php`.

Estado atual: todas as tabelas do DER têm model, repository e service, e cada service tem um controller com CRUD (`apiResource`, parâmetro `{id}`) no prefixo do seu módulo, documentado no Swagger em `/docs/api`. As regras listadas em "Regras de negócio implementadas" (MOD3 a MOD6, histórico de XP e usuário logado) já existem; faltam as demais do escopo e a autenticação real: o `Api/AuthController.php` ainda é um **mock** (`POST /api/login` com credenciais fixas e `POST /api/refresh` com tokens estáticos, sem Sanctum), com as rotas definidas direto no `routes/api.php`. Ainda não existem `routes/api/auth.php` nem `routes/api/onboarding.php` (o MOD8 hoje só tem as notificações em `routes/api/notifications.php`). Ao implementar o MOD1, substitua o mock, mova-o para `Api/Auth/` e as rotas para `routes/api/auth.php`.

## Documentação

- `docs/ESCOPO.md`: especificação do produto (regras de XP, níveis, dragão, estatísticas, onboarding).
- `docs/DER.md`: modelo ER e enums.
- `docs/MODULOS.md`: divisão do back-end em módulos.
- `docs/RF-MODULOS.md` (local, fora do git): requisitos por módulo como caixas de seleção `MODn-TASKm`; marque `[x]` ao concluir. No Linear (time GER-PROJ), as mesmas tarefas são caixas de seleção nas issues "deck" de cada subgrupo (ex.: GER-14 · `MOD5-DECK2 · XP diário`); marque nos dois lugares.
- `docs/config/mcp/LINEAR.md`: como configurar o MCP do Linear.

## Convenções de git

- Branches: `Feature/`, `Fix/`, `Refactor/`, `Docs/` + descrição, a partir de uma `main` atualizada.
- Commits: prefixos `feature:`, `fix:`, `refactor:`, `docs:`, `test:`, `chore:` (em português).

## Ferramentas

- O `CLAUDE.md` tem uma descrição mais completa das mesmas convenções, se precisar de mais contexto.
- O `opencode.json` e o `.mcp.json` ativam o MCP do Laravel Boost (`php artisan boost:mcp`), com ferramentas de inspeção do Laravel (rotas, models, queries).
