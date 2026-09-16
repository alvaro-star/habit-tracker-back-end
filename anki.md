# Cartões de estudo

Este arquivo reúne conhecimentos duráveis aprendidos durante o desenvolvimento do projeto.

## Por que `DB_HOST=127.0.0.1` quando somente o PostgreSQL roda no Docker?

Porque o Laravel roda na máquina host e acessa a porta do PostgreSQL publicada pelo Docker; o nome do serviço só seria usado entre contêineres da mesma rede Compose.

Tags: #laravel #laravel::database #docker
Fonte: https://laravel.com/docs/13.x/database
<!-- anki-id: db-host-app-local-postgres-docker -->

---

## Qual é a diferença prática entre `docker compose stop` e `docker compose down`?

`stop` apenas para os contêineres; `down` também remove os contêineres e a rede criada pelo Compose, mas preserva volumes nomeados por padrão.

Tags: #docker #docker::compose
Fonte: https://docs.docker.com/reference/cli/docker/compose/down/
<!-- anki-id: docker-compose-stop-vs-down -->

---

## Quando usar `php artisan migrate:fresh`?

Somente quando for aceitável apagar todas as tabelas da conexão configurada e recriar o esquema, normalmente em desenvolvimento ou testes controlados.

Tags: #laravel #laravel::migrations
Fonte: https://laravel.com/docs/13.x/migrations#drop-all-tables-and-migrate
<!-- anki-id: migrate-fresh-apaga-tabelas -->
