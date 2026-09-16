# Habit Tracker — back-end

API do Habit Tracker criada com Laravel 13. A aplicação roda localmente; o Docker é usado somente para o banco PostgreSQL.

## Tecnologias

- PHP 8.3 ou superior
- Laravel 13
- PostgreSQL 17
- Docker e Docker Compose
- Node.js e npm para os assets do Vite

## Pré-requisitos

Instale antes de começar:

- [Git](https://git-scm.com/downloads)
- [PHP 8.3+](https://www.php.net/downloads.php), com as extensões `pdo_pgsql`, `pgsql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json` e `fileinfo`
- [Composer 2](https://getcomposer.org/download/)
- [Node.js LTS e npm](https://nodejs.org/)
- [Docker Desktop](https://www.docker.com/products/docker-desktop/) ou Docker Engine com o plugin Compose

Confirme as instalações:

```bash
git --version
php -v
php -m
composer --version
node --version
npm --version
docker --version
docker compose version
```

> O comando moderno é `docker compose` (com espaço). `docker-compose` é a versão legada.

## Instalação a partir do repositório

### 1. Clonar e entrar na pasta

```bash
git clone https://github.com/alvaro-star/habit-tracker-back-end.git
cd habit-tracker-back-end
```

### 2. Criar o arquivo de ambiente

No macOS ou Linux:

```bash
cp .env.example .env
```

No Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

O `.env.example` já está preparado para acessar o PostgreSQL do Docker pela porta `5433`. O arquivo `.env` é local, contém configurações da sua máquina e não deve ser commitado.

### 3. Instalar as dependências PHP e JavaScript

```bash
composer install
npm install
```

Em integrações contínuas ou quando o `package-lock.json` já existir, prefira `npm ci`, pois ele instala exatamente as versões registradas no lockfile.

### 4. Gerar a chave da aplicação

```bash
php artisan key:generate
```

### 5. Iniciar o PostgreSQL

Abra o Docker Desktop, se estiver usando-o, e execute:

```bash
docker compose up -d
docker compose ps
```

O serviço deve aparecer como `healthy`. Para acompanhar a inicialização:

```bash
docker compose logs -f postgres
```

Use `Ctrl+C` para sair dos logs sem parar o banco.

### 6. Criar as tabelas

```bash
php artisan migrate
```

### 7. Rodar o projeto

A forma recomendada durante o desenvolvimento inicia o servidor Laravel, o worker de filas, os logs e o Vite em conjunto:

```bash
composer run dev
```

Acesse [http://localhost:8000](http://localhost:8000). Encerre os processos com `Ctrl+C`.

Também é possível rodar cada parte separadamente, em terminais diferentes:

```bash
php artisan serve
npm run dev
php artisan queue:work
php artisan pail
```

## Configuração do banco

As configurações de desenvolvimento estão no `.env`:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5433
DB_DATABASE=habit_tracker
DB_USERNAME=laravel
DB_PASSWORD=secret
```

`DB_HOST` é `127.0.0.1` porque o Laravel roda na máquina e apenas o PostgreSQL está em um contêiner. A porta externa padrão é `5433` para evitar conflito com uma instalação local do PostgreSQL; dentro do contêiner o banco continua ouvindo em `5432`. Para usar outra porta livre, altere `DB_PORT` no `.env`; o Compose a publicará automaticamente.

As credenciais acima são exclusivas para desenvolvimento local. Use segredos fortes e externos ao repositório em produção.

## Comandos básicos do Laravel

### Informações e ajuda

```bash
php artisan about                  # resumo da aplicação e do ambiente
php artisan list                   # lista todos os comandos
php artisan help migrate           # ajuda de um comando
php artisan route:list             # lista as rotas
php artisan config:show database   # mostra a configuração de banco resolvida
php artisan tinker                 # console interativo da aplicação
```

### Gerar arquivos

```bash
php artisan make:model Habit -mfsc
php artisan make:controller HabitController --resource
php artisan make:migration create_habits_table
php artisan make:request StoreHabitRequest
php artisan make:test HabitTest --phpunit
php artisan make:seeder HabitSeeder
```

Use `php artisan help make:model` para conhecer as opções. No exemplo, `-m` cria migration, `-f` factory, `-s` seeder e `-c` controller junto com o model.

### Banco de dados e migrations

```bash
php artisan migrate                 # aplica migrations pendentes
php artisan migrate:status          # mostra o estado das migrations
php artisan migrate:rollback        # desfaz o último lote
php artisan migrate:rollback --step=1
php artisan migrate:fresh --seed    # recria todas as tabelas e executa seeders
php artisan db:seed                  # executa os seeders
```

> `migrate:fresh` apaga todas as tabelas da conexão configurada. Use somente em um banco de desenvolvimento descartável.

### Cache, testes e qualidade

```bash
php artisan optimize:clear          # limpa caches de configuração, rota e views
composer test                       # executa os testes
php artisan test --compact          # alternativa direta pelo Artisan
vendor/bin/pint                     # formata o código PHP
npm run build                       # gera os assets de produção
```

## Comandos básicos do Docker Compose

Todos os comandos abaixo devem ser executados na raiz do projeto, onde está o `compose.yaml`.

```bash
docker compose up -d                 # cria e inicia o PostgreSQL em segundo plano
docker compose ps                    # mostra o estado dos serviços
docker compose logs postgres         # exibe os logs do banco
docker compose logs -f postgres      # acompanha os logs em tempo real
docker compose stop                  # para sem remover o contêiner
docker compose start                 # reinicia um contêiner parado
docker compose restart postgres      # reinicia apenas o banco
docker compose down                  # para e remove contêiner e rede
docker compose pull                  # baixa a versão mais recente da imagem configurada
docker compose exec postgres psql -U laravel -d habit_tracker
```

Dentro do `psql`, use `\dt` para listar tabelas e `\q` para sair.

Os dados ficam no volume nomeado `postgres_data` e sobrevivem a `stop`, `restart` e `down`. Para apagar também todos os dados do banco:

```bash
docker compose down -v
```

> Atenção: `down -v` remove permanentemente o volume e os dados locais do PostgreSQL.

## Atualizar dependências

Para instalar exatamente as versões travadas no repositório:

```bash
composer install
npm ci
```

Para atualizar dependências dentro das faixas aceitas pelos arquivos do projeto:

```bash
composer update
npm update
```

Depois de atualizar, execute:

```bash
composer test
npm run build
composer audit
npm audit
```

Revise e versione as alterações de `composer.lock` e `package-lock.json` junto com a atualização.

## Solução de problemas

### `could not find driver`

O PHP local não possui o driver do PostgreSQL habilitado. Confirme com:

```bash
php -m | grep -E 'pdo_pgsql|pgsql'
```

Instale ou habilite `pdo_pgsql` e `pgsql` na mesma instalação de PHP usada pelo terminal.

### `Connection refused` ao migrar

Confirme que o banco está saudável e confira os logs:

```bash
docker compose ps
docker compose logs postgres
```

Depois, valide se `DB_HOST`, `DB_PORT` e as credenciais do `.env` são iguais às usadas pelo Compose. Se alterou configurações depois de gerar cache, execute `php artisan optimize:clear`.

### A porta `5433` já está em uso

Troque `DB_PORT=5433` para outra porta livre no `.env`, como `DB_PORT=5434`, e recrie o serviço:

```bash
docker compose down
docker compose up -d
```

### Permissões em `storage` ou `bootstrap/cache`

No macOS ou Linux:

```bash
chmod -R ug+rwX storage bootstrap/cache
```

## Fluxo diário resumido

Ao começar:

```bash
docker compose up -d
composer run dev
```

Antes de enviar alterações:

```bash
vendor/bin/pint
composer test
npm run build
```

Ao encerrar:

```bash
docker compose stop
```

## Documentação oficial

- [Instalação do Laravel 13](https://laravel.com/docs/13.x/installation)
- [Configuração](https://laravel.com/docs/13.x/configuration)
- [Banco de dados](https://laravel.com/docs/13.x/database)
- [Migrations](https://laravel.com/docs/13.x/migrations)
- [Artisan](https://laravel.com/docs/13.x/artisan)
- [Testes](https://laravel.com/docs/13.x/testing)
- [Docker Compose](https://docs.docker.com/compose/)
- [Imagem oficial do PostgreSQL](https://hub.docker.com/_/postgres)
