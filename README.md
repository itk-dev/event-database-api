# Danish event database (version 2.x)

[![Woodpecker](https://img.shields.io/badge/woodpecker-prod|stg-blue.svg?style=flat-square&logo=data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyMiIgaGVpZ2h0PSIyMiI+PHBhdGggZmlsbD0iI2ZmZiIgZD0iTTEuMjYzIDIuNzQ0QzIuNDEgMy44MzIgMi44NDUgNC45MzIgNC4xMTggNS4wOGwuMDM2LjAwN2MtLjU4OC42MDYtMS4wOSAxLjQwMi0xLjQ0MyAyLjQyMy0uMzggMS4wOTYtLjQ4OCAyLjI4NS0uNjE0IDMuNjU5LS4xOSAyLjA0Ni0uNDAxIDQuMzY0LTEuNTU2IDcuMjY5LTIuNDg2IDYuMjU4LTEuMTIgMTEuNjMuMzMyIDE3LjMxNy42NjQgMi42MDQgMS4zNDggNS4yOTcgMS42NDIgOC4xMDdhLjg1Ny44NTcgMCAwMC42MzMuNzQ0Ljg2Ljg2IDAgMDAuOTIyLS4zMjNjLjIyNy0uMzEzLjUyNC0uNzk3Ljg2LTEuNDI0Ljg0IDMuMzIzIDEuMzU1IDYuMTMgMS43ODMgOC42OTdhLjg2Ni44NjYgMCAwMDEuNTE3LjQxYzIuODgtMy40NjMgMy43NjMtOC42MzYgMi4xODQtMTIuNjc0LjQ1OS0yLjQzMyAxLjQwMi00LjQ1IDIuMzk4LTYuNTgzLjUzNi0xLjE1IDEuMDgtMi4zMTggMS41NS0zLjU2Ni4yMjgtLjA4NC41NjktLjMxNC43OS0uNDQxbDEuNzA3LS45ODEtLjI1NiAxLjA1MmEuODY0Ljg2NCAwIDAwMS42NzguNDA4bC42OC0yLjg1OCAxLjI4NS0yLjk1YS44NjMuODYzIDAgMTAtMS41ODEtLjY4N2wtMS4xNTIgMi42NjktMi4zODMgMS4zNzJhMTguOTcgMTguOTcgMCAwMC41MDgtMi45ODFjLjQzMi00Ljg2LS43MTgtOS4wNzQtMy4wNjYtMTEuMjY2LS4xNjMtLjE1Ny0uMjA4LS4yODEtLjI0Ny0uMjYuMDk1LS4xMi4yNDktLjI2LjM1OC0uMzc0IDIuMjgzLTEuNjkzIDYuMDQ3LS4xNDcgOC4zMTkuNzUuNTg5LjIzMi44NzYtLjMzNy4zMTYtLjY3LTEuOTUtMS4xNTMtNS45NDgtNC4xOTYtOC4xODgtNi4xOTMtLjMxMy0uMjc1LS41MjctLjYwNy0uODktLjkxM0M5LjgyNS41NTUgNC4wNzIgMy4wNTcgMS4zNTUgMi41NjljLS4xMDItLjAxOC0uMTY2LjEwMy0uMDkyLjE3NW0xMC45OCA1Ljg5OWMtLjA2IDEuMjQyLS42MDMgMS44LTEgMi4yMDgtLjIxNy4yMjQtLjQyNi40MzYtLjUyNC43MzgtLjIzNi43MTQuMDA4IDEuNTEuNjYgMi4xNDMgMS45NzQgMS44NCAyLjkyNSA1LjUyNyAyLjUzOCA5Ljg2LS4yOTEgMy4yODgtMS40NDggNS43NjMtMi42NzEgOC4zODUtMS4wMzEgMi4yMDctMi4wOTYgNC40ODktMi41NzcgNy4yNTlhLjg1My44NTMgMCAwMC4wNTYuNDhjMS4wMiAyLjQzNCAxLjEzNSA2LjE5Ny0uNjcyIDkuNDZhOTYuNTg2IDk2LjU4NiAwIDAwLTEuOTctOC43MTFjMS45NjQtNC40ODggNC4yMDMtMTEuNzUgMi45MTktMTcuNjY4LS4zMjUtMS40OTctMS4zMDQtMy4yNzYtMi4zODctNC4yMDctLjIwOC0uMTgtLjQwMi0uMjM3LS40OTUtLjE2Ny0uMDg0LjA2LS4xNTEuMjM4LS4wNjIuNDQ0LjU1IDEuMjY2Ljg3OSAyLjU5OSAxLjIyNiA0LjI3NiAxLjEyNSA1LjQ0My0uOTU2IDEyLjQ5LTIuODM1IDE2Ljc4MmwtLjExNi4yNTktLjQ1Ny45ODJjLS4zNTYtMi4wMTQtLjg1LTMuOTUtMS4zMy01Ljg0LTEuMzgtNS40MDYtMi42OC0xMC41MTUtLjQwMS0xNi4yNTQgMS4yNDctMy4xMzcgMS40ODMtNS42OTIgMS42NzItNy43NDYuMTE2LTEuMjYzLjIxNi0yLjM1NS41MjYtMy4yNTIuOTA1LTIuNjA1IDMuMDYyLTMuMTc4IDQuNzQ0LTIuODUyIDEuNjMyLjMxNiAzLjI0IDEuNTkzIDMuMTU2IDMuNDJ6bS0yLjg2OC42MmExLjE3NyAxLjE3NyAwIDEwLjczNi0yLjIzNiAxLjE3OCAxLjE3OCAwIDEwLS43MzYgMi4yMzd6Ii8+PC9zdmc+Cg==)](https://woodpecker.itkdev.dk/repos/13)
[![GitHub Release](https://img.shields.io/github/v/release/itk-dev/event-database-api?style=flat-square&logo=data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCA0NDggNTEyIj48IS0tIUZvbnQgQXdlc29tZSBGcmVlIDYuNy4yIGJ5IEBmb250YXdlc29tZSAtIGh0dHBzOi8vZm9udGF3ZXNvbWUuY29tIExpY2Vuc2UgLSBodHRwczovL2ZvbnRhd2Vzb21lLmNvbS9saWNlbnNlL2ZyZWUgQ29weXJpZ2h0IDIwMjUgRm9udGljb25zLCBJbmMuLS0+PHBhdGggZmlsbD0iI2ZmZiIgZD0iTTAgODBMMCAyMjkuNWMwIDE3IDYuNyAzMy4zIDE4LjcgNDUuM2wxNzYgMTc2YzI1IDI1IDY1LjUgMjUgOTAuNSAwTDQxOC43IDMxNy4zYzI1LTI1IDI1LTY1LjUgMC05MC41bC0xNzYtMTc2Yy0xMi0xMi0yOC4zLTE4LjctNDUuMy0xOC43TDQ4IDMyQzIxLjUgMzIgMCA1My41IDAgODB6bTExMiAzMmEzMiAzMiAwIDEgMSAwIDY0IDMyIDMyIDAgMSAxIDAtNjR6Ii8+PC9zdmc+)](https://github.com/itk-dev/event-database-api/releases)
[![GitHub Actions Workflow Status](https://img.shields.io/github/actions/workflow/status/itk-dev/event-database-api/pr.yaml?style=flat-square&logo=github)](https://github.com/itk-dev/event-database-api/actions/workflows/pr.yaml)
[![Codecov](https://img.shields.io/codecov/c/github/itk-dev/event-database-api?style=flat-square&logo=codecov)](https://codecov.io/gh/itk-dev/event-database-api)
[![GitHub last commit](https://img.shields.io/github/last-commit/itk-dev/event-database-api?style=flat-square)](https://github.com/itk-dev/event-database-api/commits/develop/)
[![GitHub License](https://img.shields.io/github/license/itk-dev/event-database-api?style=flat-square)](https://github.com/itk-dev/event-database-api/blob/develop/LICENSE)

This is the next iteration of [the event database](https://github.com/itk-event-database/event-database-api) used by the
municipality of Aarhus.

This repository contains the frontend API, if you are looking for the event imports, the code is located
[here](https://github.com/itk-dev/event-database-imports).

The event database is an API platform for event aggregation from the public vendors throughout the cites. It gets data
mainly from feeds (JSON/XML) or APIs provided by the vendors. It is highly configurable in doing custom feed mappings
and extendable to read data from APIs and map this data to event. It also has a user interface to allow manual entering
of events.

The data input is pulled/pushed from a range of differently formatted sources and normalized into an event format that
can be used across platforms.

For more detailed and technical documentation, see the
[docs](https://github.com/itk-dev/event-database-imports/tree/develop/docs) folder in this repository.

## Record Architecture Decisions

This project utilizes record architecture decisions documents which can be located in
[https://github.com/itk-dev/event-database-imports/tree/develop/docs](https://github.com/itk-dev/event-database-imports/tree/develop/docs)
in this repository.

## Installation

```shell
docker compose up --detach
docker compose exec phpfpm composer install
```

### Fixtures

The project comes with doctrine fixtures to help development on local machines. They can be loaded with the standard
doctrine fixture load command:

```shell
docker compose exec phpfpm bin/console app:fixtures:load <index>
```

`<index>` must be one of `events`, `organizations`, `occurrences`, `daily_occurrences`, `tags`, `vocabularies` or
`locations` (cf. [`src/Model/IndexName.php`](src/Model/IndexName.php)).

The fixtures are related to the backend where the fixtures are generated by using the `app:index:dump` command. The load
above command downloads the fixtures from
[GitHub](https://github.com/itk-dev/event-database-imports/tree/develop/src/DataFixtures/indexes) and loads them into
ElasticSearch.

> [!TIP]
> Use `task fixtures:load` to load all fixtures into Elasticsearch.

> [!CAUTION]
> If the `task fixtures:load` command (or any `bin/console app:fixtures:load` incantation) fails with an error like
>
> ``` shell
> No alive nodes. All the 1 nodes seem to be down.
> ```
>
> you must reset the Elasticsearch service to be ready for requests, e.g. by running
>
> ``` shell
> docker compose exec elasticsearch curl 'http://localhost:9200/_cluster/health?wait_for_status=yellow&timeout=5s' --verbose
> ```
>
> until it returns `HTTP/1.1 200 OK` (cf. [How to Implement Elasticsearch Health Check in Docker
> Compose](https://www.baeldung.com/ops/elasticsearch-docker-compose)).
>
> Alternatively, you can run `docker compose up --detach --wait` to recreate all services and
> (automatically) wait for Elasticsearch to be ready – it takes a while …

## Accessing the API

To access the API, a valid API key must be presented in the `X-Api-Key` header, e.g.

``` shell
curl --header "X-Api-Key: api_key_1" "http://$(docker compose port nginx 8080)/api/v2/events"
```

Valid API keys are defined via the `APP_API_KEYS` environment variable:

``` shell
# .env.local
APP_API_KEYS='[
  {"username": "user_1", "apikey": "api_key_1"},
  {"username": "user_2", "apikey": "api_key_2"}
]'
```

## Production

When installing composer and Symfony based application in production, you should not install development packages,
hence use this command:

```shell
docker compose exec phpfpm composer install --no-dev --optimize-autoloader
```

## API request examples

Get events with(out) public access:

``` shell
curl --silent --header "X-Api-Key: api_key_1" "http://$(docker compose port nginx 8080)/api/v2/events?publicAccess=true"  | docker run --rm --interactive ghcr.io/jqlang/jq:latest '.["hydra:member"]|length'
curl --silent --header "X-Api-Key: api_key_1" "http://$(docker compose port nginx 8080)/api/v2/events?publicAccess=false" | docker run --rm --interactive ghcr.io/jqlang/jq:latest '.["hydra:member"]|length'
```

## Test

``` shell
task fixtures:load --yes
task api:test --yes
```
