# CLAUDE.md

Este arquivo orienta o Claude Code (claude.ai/code) ao trabalhar com o código deste repositório.

## Idioma

A linguagem padrão do projeto é o **português**: documentação, mensagens de commit, descrições de PR e de issues, comentários no código e a comunicação com a equipe. Os nomes de pastas de módulo e de rotas seguem as convenções do Laravel em inglês (`Habits`, `/api/check-ins`). As classes de domínio usam o nome da tabela em português (`Habito`, `HabitoService`, `HabitoController`, `RegistroHabitoRequest`), e os métodos de controller seguem o Laravel (`index`, `store`…).

## Projeto

Back-end de um habit tracker personalizado: uma API somente JSON feita com Laravel 13 (PHP ^8.3) e autenticada com Laravel Sanctum. Não há front-end web neste repositório; `resources/` e o Vite são sobras intocadas do esqueleto.

**Estado atual:** os models, migrations, repositories e services de todas as tabelas do DER existem (MOD0), e cada service tem um controller com CRUD exposto em `apiResource` no prefixo do seu módulo. As estatísticas (MOD7) têm endpoints próprios. Já existem as primeiras regras de negócio do MOD3 e do MOD5 (veja "Regras de negócio implementadas"); faltam as demais (evolução do dragão, onboarding etc.) e a autenticação real. Todas as rotas estão documentadas no Swagger (Scramble) em `/docs/api`.

O `app/Http/Controllers/Api/AuthController.php` é um **mock temporário**: `POST /api/login` aceita só credenciais fixas no código e `POST /api/refresh` devolve tokens estáticos (`accessToken`/`refreshToken`), sem Sanctum nem banco. O `GET /api/user` (`Api/Auth/UsuarioController`) continua protegido por `auth:sanctum`, então os tokens do mock não funcionam nele. O MOD1 deve substituir esse mock por autenticação real.

## Objetivo do projeto na disciplina

É um projeto universitário, e a parte deste repositório é o **back-end**: construir a API REST que implementa a especificação de produto de `docs/ESCOPO.md` (versão 2.0, "Módulo 4 – Gamificação e Conteúdo"). A API precisa cobrir o ciclo completo do usuário: cadastrar hábitos, registrar o que fez no dia, ganhar XP ponderado, subir de nível, evoluir o dragão até o Santuário, consultar estatísticas, passar pelo onboarding e receber notificações. Tudo isso serve a um front-end que fica fora deste repositório.

Ao trabalhar aqui, siga como fontes de verdade:

- `docs/ESCOPO.md`: as regras de negócio (fórmula de XP, pesos, marcos do dragão, níveis).
- `docs/DER.md`: o modelo de dados e os enums.
- `docs/MODULOS.md`: a divisão em módulos. Os requisitos de cada módulo estão em `docs/RF-MODULOS.md`, um arquivo local fora do git que pode não existir em todas as máquinas. Nele, cada `MODn-TASKm` é uma caixa de seleção: marque `[x]` ao concluir a tarefa.

No Linear (time GER-PROJ), as tarefas ficam agrupadas em issues "deck" por subgrupo (ex.: GER-14 · `MOD5-DECK2 · XP diário`), com as `MODn-TASKm` como caixas de seleção na descrição. Ao concluir uma tarefa, marque a caixa no Linear e no `RF-MODULOS.md`.

## Comandos

```bash
composer setup                     # instala dependências, copia o .env, key:generate, migrate, npm install + build
composer dev                       # roda `php artisan dev` (servidor, fila, logs, vite)
docker compose up -d db-database   # sobe só o Postgres para desenvolvimento
php artisan serve                  # só a API, em http://localhost:8000
composer test                      # config:clear + php artisan test
php artisan test --compact
php artisan test --filter=HabitoApiTest                     # uma classe ou método de teste
php artisan test tests/Feature/Habits/HabitoApiTest.php     # um arquivo
vendor/bin/pint --dirty            # formata os arquivos PHP alterados (Laravel Pint)
php artisan route:list --path=api
php artisan migrate:fresh --seed
```

Os testes usam PHPUnit (não Pest). Crie-os com `php artisan make:test Habits/HabitoApiTest --phpunit`. O `phpunit.xml` roda os testes em SQLite em memória, então os testes não precisam do Postgres; evite SQL exclusivo do Postgres nas migrations e queries, ou os testes quebram. O trait `tests/Concerns/VerificaCrudDaApi` percorre o CRUD completo de um recurso (criar, listar, consultar, PATCH, excluir e 404).

Se o PHP da máquina não tiver o driver `pdo_sqlite` (erro `could not find driver`), rode os testes na imagem do projeto, que já o tem:

```bash
docker run --rm --user "$(id -u):$(id -g)" -v "$PWD":/app -w /app --entrypoint php habittracker-app:latest artisan test --compact
```

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
| Services | `app/Services` | Regras de negócio, cada uma no service do módulo dono dela (ex.: a dificuldade gerada pelo peso em `HabitoService`, o XP do dia em `XpDiarioService`). Um service pode usar outro service ou repository quando precisa dos dados de outro módulo. Estendem `BaseService`, que oferece o CRUD (`listar`, `buscar`, `criar`, `atualizar`, `excluir`) e as variantes restritas ao usuário (`listarDoUsuario`, `criarParaUsuario` etc.). Aceitam um DTO ou um array. |
| Repositories | `app/Repositories` | Queries Eloquent, uma classe por model, estendendo `BaseRepository`. O `consultaDoUsuario()` filtra os dados pessoais pela coluna `fk_id_usuario`; tabelas sem essa coluna (`registro_habito`, `escolha_elemento`) o sobrescrevem para chegar ao dono pelo relacionamento. |
| Models | `app/Models` | Models Eloquent com o nome da tabela em português (`habito` → `Habito`, `registro_habito` → `RegistroHabito`): relacionamentos, casts, scopes. Ficam todos juntos, sem subpastas, porque vários módulos usam os mesmos models. Os dados de hábitos pertencem ao usuário, então filtre as queries pelo usuário autenticado. |
| API Resources | `app/Http/Resources/<Modulo>` | Formato JSON das respostas (`make:resource Habits/HabitoResource`). Nunca devolva models diretamente. |
| Migrations / factories / seeders | `database/` | Mudanças de schema e dados de teste/desenvolvimento, nas pastas padrão, sem subpastas. Crie junto com o model via `make:model Habito -mfs`. |
| Validações e exceções | `app/Rules`, `app/Exceptions` | Regras de validação reutilizáveis (ex.: `DiaFrequenciaValido`) e exceções de domínio. |
| Testes | `tests/Feature/<Modulo>`, `tests/Unit` | Testes de feature de cada módulo e testes unitários de DTOs, enums e configuração. |

Comportamentos transversais:

- A migration `personal_access_tokens` do Sanctum já existe, e `User` é o dono dos tokens.
- O `bootstrap/app.php` força as exceções (inclusive erros de validação) a serem renderizadas como JSON nas rotas `api/*` e nas requisições que esperam JSON, então os controllers não precisam formatar erros.
- Regras que a Form Request não consegue validar sozinha (porque dependem do registro já salvo, ou porque o service é chamado sem passar por uma request) lançam `App\Exceptions\RegraDeNegocioException($campo, $mensagem, $status = 422)` no service. O `bootstrap/app.php` a converte no status da exceção (422 por padrão; ex.: 409 para conflito), no mesmo formato `{"message", "errors": {campo: [...]}}` dos erros de validação.
- Parâmetros fixos das regras do jogo ficam em `config/` (ex.: `config/progression.php` com o `teto_diario` de 25 XP), não no `.env`.
- O Swagger (Scramble) usa o docblock do método do controller como resumo e descrição do endpoint, e o comentário `/** ... */` acima de cada regra da Form Request como descrição do campo. Documente ali as regras que o front precisa conhecer.
- Dados pessoais (hábitos, check-ins, dragões, XP diário, escolhas de elemento) só são vistos pelo dono: um id de outro usuário devolve 404. Quando um registro aponta para outro dado pessoal (ex.: o `fk_id_habito` de um check-in), a request valida a posse com `Rule::exists(...)->where('fk_id_usuario', $this->user()?->id)` e devolve 422. As demais tabelas (categorias, modelos, insígnias, elementos, gatilhos) são catálogos, com CRUD liberado a qualquer usuário autenticado, porque ainda não existe papel de administrador.
- Resources de tabelas com uma coluna chamada `data` (`RegistroHabitoResource`, `XpDiarioResource`) declaram `public static bool $forceWrapping = true`, senão o Laravel confunde a coluna com o envelope `{"data": ...}` e não embrulha a resposta.

## Regras de negócio implementadas

- **Peso e dificuldade (MOD3):** o usuário envia o `peso` (inteiro de 1 a 20, `Dificuldade::PESO_MINIMO`/`PESO_MAXIMO`), e a `dificuldade` é gerada por `Dificuldade::doPeso()`: 1–5 `FACIL`, 6–10 `MEDIO`, 11–15 `DIFICIL`, 16–20 `MUITO_DIFICIL`. Uma `dificuldade` enviada é descartada.
- **Data de criação e tipo de meta (MOD3):** o `created_at` do hábito vem só do timestamp do banco; enviar `created_at` ou `data_criacao` no POST ou no PUT/PATCH devolve 422 (regra `prohibited` na `HabitoRequest`). O `tipo_meta` só pode mudar enquanto o hábito não tem check-ins; depois disso, `HabitoService` lança `RegraDeNegocioException` em `tipo_meta` (422). Reenviar o mesmo valor é permitido.
- **Exclusão de hábito (MOD3):** o `DELETE /api/habits/{id}` (`HabitoService::excluir()` / `excluirDoUsuario()`) não apaga o registro: faz `ativo = false`, grava `inactivated_at` e mantém os check-ins. Só é permitido no dia em que o hábito foi criado; um hábito criado antes de hoje devolve 422 em `id`. Ao desativar, o XP de hoje é recalculado (`atualizarXpDeHoje`). O PATCH com `ativo: false` também grava o `inactivated_at`, e `ativo: true` o limpa. O hábito inativo continua na listagem e no `GET` por id e sai dos hábitos do dia.
- **TODO de hábitos desativados:** o XP diário (`HabitoRepository::ativosDoUsuarioNoDia()`, `XpDiarioService::xpDoDia()`) e o progresso por categoria (`StatisticService`) ainda filtram por `ativo = true`, o que tira o hábito de todos os dias. Há TODOs nesses pontos para passar a usar o `inactivated_at` e desconsiderar o hábito só a partir da desativação.
- **Frequência (MOD3):** `habito.dia_frequencia` é `null` no `DIARIA`, um `DiaSemana` (`SEG`…`DOM`) no `SEMANAL` e um dia de `1` a `31` (guardado como texto) no `MENSAL`. A `frequencia` e o `dia_frequencia` são sempre enviados juntos (inclusive `dia_frequencia: null` no `DIARIA`). A combinação é validada na request (`DiaFrequenciaValido`, com a lógica em `Frequencia::aceitaDia()`) e no `HabitoService::validarFrequencia()`.
- **Hábitos do dia (MOD3):** `HabitoService::habitosDiarios()`, exposto em `GET /api/habits/daily?data=AAAA-MM-DD`, lista os hábitos ativos, já criados na data, que caem nela. A data é validada com Carbon (um dia inexistente, como `2026-04-31`, devolve 422), e um hábito mensal dos dias 29 a 31 só aparece nos meses que têm esse dia.
- **Check-in (MOD4):** só há `GET` (lista e por id) e `POST` em `/api/check-ins`; não há PUT, PATCH nem DELETE. O `POST` (`RegistroHabitoService::registrar()`) recebe `checked`: `true` cria ou atualiza o check-in do par `fk_id_habito` + `data` (índice composto `registro_habito_habito_data_index`; 201 ao criar, 200 ao atualizar só os campos enviados) e `false` exclui o check-in do dia (204). Antes de gravar, a data do DTO é validada: sem dragão ativo, anterior ao nascimento do dragão ativo ou futura → 422; anterior à criação do hábito → 409. Em qualquer data (passada ou hoje), `XpDiarioService::recalcularDia()` recalcula o XP daquele dia, grava em `xp_diario` e soma a diferença em relação ao `xp_ganho` já gravado (0 se não havia) no `xp_ciclo` do dragão ativo e no `xp_vitalicio`.
- **Cumprimento do check-in (MOD4):** o status e o percentual são definidos pelo `tipo_meta` do hábito (`RegistroHabitoService::comCumprimento()`). `REALIZACAO`: vale o `status` enviado (sem ele, `NAO_CONCLUIDO`); `CONCLUIDO` grava 100% e `NAO_CONCLUIDO`, 0%. `TEMPO` e `QUANTIDADE`: o status enviado é ignorado e o `valor_realizado` é comparado com o `habito.valor_meta`: nulo ou zero → `NAO_CONCLUIDO` (0%); igual ou acima → `CONCLUIDO` (100%); abaixo → `EM_ANDAMENTO`, com o percentual proporcional. Sem `valor_meta`, qualquer valor acima de zero conclui.
- **XP do dia (MOD5):** `XpDiarioService::xpDoDia()` calcula de forma dinâmica, para qualquer dia, `(∑W cumpridos / ∑W total) × teto_diario`, usando só hábitos ativos já criados na data (e só check-ins desses hábitos). O `pesoRealizadoDoDia()` soma o peso inteiro dos check-ins `CONCLUIDO`, o peso × percentual dos `EM_ANDAMENTO` e zero no resto, usando o check-in mais recente do dia. Entram todos os hábitos que caem no dia, seja qual for a frequência (os semanais no dia da semana do `dia_frequencia`, os mensais no dia do mês); não há pool semanal (a MOD5-TASK4 foi descartada). O `recalcularDia()` grava o resultado em `xp_diario` e soma a diferença em relação ao `xp_ganho` já gravado no `xp_ciclo` do dragão ativo e no `xp_vitalicio` (sem dragão ativo, não faz nada). O `atualizarXpDeHoje($usuarioId)` encapsula o recálculo de hoje e é chamado pelo `HabitoService` ao criar e ao desativar um hábito; o check-in chama o `recalcularDia()` do dia marcado. O `XpDiarioService` usa o `HabitoRepository`, e não o `HabitoService`, para evitar dependência circular.
- **Histórico de XP (MOD5):** `GET /api/progression/daily-xp?data_inicio=AAAA-MM-DD&data_fim=AAAA-MM-DD` é só leitura (não há POST, PUT, DELETE nem GET por id), exige o intervalo (validado com `App\Rules\DataValida`) e busca tudo no banco, inclusive o dia de hoje, do mais recente para o mais antigo. Um dia sem registro não aparece: a listagem não calcula nada.
- **Estágio do dragão (MOD6):** não há tabelas `estagio` nem `ovo`. O estágio é o enum `App\Enums\EstagioDragao` (`OVO` 0, `FILHOTE` 120, `JOVEM` 1.000, `ADULTO` 3.500, `ADULTO_FINAL` 9.000 XP do ciclo; `EstagioDragao::XP_ESCOLHA_ELEMENTO` = 6.000 é o momento de escolher o elemento, não um estágio), guardado em `dragao.estagio`. Ele não vem da requisição: o `DragaoService` o gera pelo `xp_ciclo` ao criar ou editar, e o `DragaoRepository::somarXpCiclo()` o atualiza quando o check-in muda o XP. A `escolha_elemento.estagio` guarda o estágio do dragão no momento da escolha (`EscolhaElementoService`). O dragão não registra mais de que ovo nasceu.
- **Dragão ativo (MOD6):** o usuário pode ter vários dragões, mas só um `ATIVO`, guardado em `users.fk_id_dragao` (fora do `Fillable`). O `DragaoService` devolve 422 em `status` ao criar ou ativar um segundo dragão ativo, aponta o `fk_id_dragao` para o dragão ao criá-lo ou ativá-lo e o limpa ao aposentá-lo (a FK limpa sozinha ao excluir). O XP de hoje usa esse dragão.
- **XP vitalício (MOD5):** `users.xp_vitalicio` (padrão 0) fica fora do `Fillable` do `User`; só as regras do MOD5 devem alterá-lo.
- **Níveis (MOD5-TASK6 e TASK7):** não há tabela de níveis. O `NivelService` (que não estende `BaseService`) calcula tudo sem banco: `curva()` gera os intervalos dos 30 níveis a partir de `config/progression.php` (`niveis`): nível 1 em 0 XP, cada um dos 29 passos seguintes ~10% maior, nível 30 em 9.000 XP; `nivelDoXp($xp)` devolve o nível de um XP. `User::getNivel()` usa o `nivelDoXp()` com o `xp_vitalicio`, calculado na hora (não é coluna).
- **Usuário logado:** `GET /api/user` usa `Api/Auth/UsuarioController` e devolve o `UsuarioResource`, com o `xp_vitalicio`, o `fk_id_dragao` (dragão ativo) e o `nivel` (número, de `getNivel()`) anexado na resposta.

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
- As tabelas de `docs/DER.md` estão em português, e os models usam os mesmos nomes (ex.: `habito` → `Habito`). A exceção é o `usuario` do DER, que corresponde à tabela `users` e ao model `User` do Laravel.
- O MOD7 foge um pouco do padrão: o `StatisticService` fica em `app/Services/Statistics/` e não estende `BaseService`, e as rotas são endpoints próprios (`matrix`, `completion`, `categories`), não `apiResource`.
- O MOD8 está separado dos demais (é a seção 7 do escopo) e ainda não tem issues no Linear. Além das pastas do módulo, ele usa `app/Notifications` e `app/Jobs`, com o agendamento em `routes/console.php`.
- A ordem de desenvolvimento segue a numeração, porque cada módulo depende dos anteriores.

## Estrutura do repositório além do padrão Laravel

- `docs/`: documentação do projeto (`ESCOPO.md`, `DER.md`, `MODULOS.md`).
- `docs/config/skills/`: definições de skills de agentes compartilhadas pela equipe (vazia por enquanto).
- `docs/config/mcp/`: configuração de servidores MCP para agentes (`LINEAR.md` explica como configurar o MCP do Linear). A configuração MCP ativa continua sendo o `.mcp.json` na raiz (Claude Code) e o `opencode.json` (OpenCode).

## Convenções de git (do README)

- Branches: `Feature/`, `Fix/`, `Refactor/`, `Docs/` seguido de uma descrição, ex.: `Feature/Nome-da-tarefa`. Crie a branch a partir de uma `main` atualizada.
- Prefixos de commit: `feature:`, `fix:`, `refactor:`, `docs:`, `test:`, `chore:`.
