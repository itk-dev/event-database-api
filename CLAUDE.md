# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this project is

API platform front-end for the Danish event database used by the municipality of Aarhus. This repo serves a
**read-only** REST/Hydra API (`/api/v2/...`) backed by **Elasticsearch** — data is *indexed* by a separate project,
[`itk-dev/event-database-imports`](https://github.com/itk-dev/event-database-imports). The MariaDB service in
`docker-compose.yml` is part of the standard ITK Dev Symfony image but is **not used for domain data** (the
`migrations/` and `src/Entity/` directories are empty).

Stack: PHP 8.3+, Symfony 7.4, API Platform 4.1, Elasticsearch 8.x. Runs entirely in Docker via
`itkdev/php8.3-fpm` + nginx.

## Common commands

All commands are wrapped in `Taskfile.yml` (run via [Task](https://taskfile.dev)). Most are just
`docker compose exec phpfpm …` underneath — useful to know when `task --dry <name>` to see the actual command.

### Setup / running

```shell
docker compose pull
docker compose up --detach --wait        # --wait is important: Elasticsearch is slow to become ready
docker compose exec phpfpm composer install
task fixtures:load                       # loads demo data from event-database-imports into Elasticsearch
```

The site is reachable at `http://$(docker compose port nginx 8080)`. Every API call needs an `X-Api-Key` header
matching one of the entries in `APP_API_KEYS` (JSON array in `.env.local`).

### Tests

```shell
task fixtures:load:test --yes            # loads tests/resources/*.json into Elasticsearch — REQUIRED before api:test
task api:test                            # phpunit
task api:test -- --filter EventsFilter   # single test class / pattern
```

Tests hit a real Elasticsearch (no mocking) — see `tests/ApiPlatform/AbstractApiTestCase.php`. The hardcoded test
API key is `test_api_key`. If a test run dies with "No alive nodes", run `docker compose up --detach --wait` and
reload fixtures.

### Lint / static analysis

```shell
task coding-standards:check              # markdown + php-cs-fixer + twig-cs-fixer + prettier (yaml)
task coding-standards:apply              # auto-fix all of the above
task code-analysis                       # PHPStan, level 6
```

CI (GitHub Actions `pr.yaml`) runs all of these — run them locally before opening a PR.

### API spec

`public/spec.yaml` is the committed OpenAPI export and is checked in CI. Regenerate after changing any API resource:

```shell
task api:spec:export
```

## Architecture

### Request flow

API Platform resources are **DTOs**, not Doctrine entities. Each resource follows the same pattern — `Event` is the
canonical example:

1. `src/Api/Dto/<Resource>.php` — `#[ApiResource]` class declaring operations, pagination, and `#[ApiFilter]`
   attributes. The `$id` property is identifier-only; real data is filled in by the provider.
2. `src/Api/State/<Resource>RepresentationProvider.php` — implements `ProviderInterface`. Receives the operation +
   context, asks `AbstractProvider::getFilters()` to compile the declared `#[ApiFilter]` attributes into
   Elasticsearch query fragments, then calls `IndexInterface::search()` and returns a `SearchResults` (collection) or
   array (single item).
3. `src/Service/ElasticSearch/ElasticSearchIndex.php` — the only `IndexInterface` implementation. Talks to the
   Elasticsearch cluster (`INDEX_URL` env var). Paginated results come back via `ElasticSearchPaginator`.

The seven indices are enumerated in `src/Model/IndexName.php` (`events`, `organizations`, `occurrences`,
`daily_occurrences`, `tags`, `vocabularies`, `locations`). Each index has a matching DTO and provider.

### Filters

Custom Elasticsearch filters live in `src/Api/Filter/ElasticSearch/` (`MatchFilter`, `IdFilter`, `BooleanFilter`,
`DateRangeFilter`, `DateFilter`, `TagFilter`). They implement API Platform's `FilterInterface` but `apply()` returns
ES query DSL rather than mutating a Doctrine queryBuilder. `AbstractProvider::getFilters()` separates filter clauses
from sort clauses via the `SortFilterInterface` marker.

When adding a filter to a resource, attach it with `#[ApiFilter(SomeFilter::class, properties: […])]` on the DTO —
the provider picks them up automatically through `api_platform.filter_locator` (injected via
`config/services.yaml`).

### Auth

Stateless. `src/Security/ApiKeyAuthenticator.php` reads `X-Api-Key`; `ApiUserProvider` validates against the
JSON-encoded `APP_API_KEYS` env var. `/api/v2/docs` is public; everything else under `/api` requires a valid key
(see `config/packages/security.yaml`). There is no role model — any valid key gets full read access.

### What lives where

- `src/Api/Dto/` + `src/Api/State/` + `src/Api/Filter/ElasticSearch/` — the API layer (see above).
- `src/Service/ElasticSearch/` — the only data access. If you find yourself adding a new persistence concern, this
  is where it goes.
- `src/Command/FixturesLoadCommand.php` — loads JSON dumps into Elasticsearch (used by `task fixtures:load*`).
- `src/Model/` — value objects / enums (`IndexName`, `FilterType`, `DateLimit`, `SearchResults`, `DateFilterConfig`).
- `config/reference.php` — auto-generated, excluded from PHP-CS-Fixer (see `.php-cs-fixer.dist.php`).
- `tests/resources/*.json` — fixtures used by the test suite; loaded via
  `--url=file:///app/tests/resources/<index>.json`.

## Conventions worth knowing

- Don't add Doctrine entities or migrations — domain data is owned by `event-database-imports`. New resources mean
  new DTOs + providers, not entities.
- `paginationMaximumItemsPerPage` on a resource caps client-requested page size.
  `AbstractProvider::MAX_PAGE_SIZE_FALLBACK = 20` is the safety net when the resource doesn't declare one.
- `failOnDeprecation`, `failOnNotice`, `failOnWarning` are all `true` in `phpunit.dist.xml` — a deprecation warning
  will fail the suite.
- PHPStan errors about `App\Api\Dto\*::$id` being unused are intentionally ignored (API Platform writes the property
  reflectively).
