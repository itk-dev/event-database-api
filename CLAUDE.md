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

The test harness creates each index with a **production-parity mapping** (`dynamic: strict`) checked in at
`tests/resources/mappings/<index>.json` — a hand-kept copy of the importer's `src/Model/Indexing/Mappings/`.
`FixtureLoader::createIndex()` fails loudly if a mapping is missing, so the filter tests exercise real field
semantics (`keyword` = exact/case-sensitive; `text` = tokenised) rather than Elasticsearch dynamic-mapping
artefacts. When the importer changes a mapping, update the matching file here (the `Stop` hook warns on drift).

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

## Works with event-database-imports

This repo is the public **read-only** API; [`event-database-imports`](https://github.com/itk-dev/event-database-imports)
is the **write/admin** side. They are decoupled at runtime and communicate only through a **shared Elasticsearch
cluster** — no shared database, no HTTP call between them.

- **The importer writes; this repo reads.** The importer runs feed import → normalize → persist (MariaDB) → index
  into ES. This repo serves `/api/v2/…` by reading those same ES indices (`INDEX_URL`); it has **no domain
  database** and must never write to ES.
- **The importer owns the index lifecycle.** It builds a versioned index `<alias>_<timestamp>`, then atomically
  repoints the alias. This repo always queries the **alias** — so it never sees a half-built index, but a resource
  returns empty if the alias was never populated.
- **The contract is hand-duplicated, with no compile-time link:**
  - Index names — `src/Model/IndexName.php` here ↔ `src/Model/Indexing/IndexNames.php` in the importer
    (`events`, `organizations`, `occurrences`, `daily_occurrences`, `tags`, `vocabularies`, `locations`).
  - Document shape — mappings live **only in the importer** (`src/Model/Indexing/Mappings/`); this repo has none and
    trusts the fields/types the importer writes. A renamed or retyped field silently breaks the filters/providers
    here (they reference ES field names directly).
  - Keep both in sync when changing either. The `Stop` hook (below) warns when `src/Model/IndexName.php` changes.
- **Co-hosted by path prefix** in production: `/api/v2/` → this app, `/admin/` → the importer, via Traefik on the
  shared `frontend` network.

## Claude Code automation

`.claude/settings.json`, `.claude/agents/`, and `.claude/skills/` configure this repo's Claude Code setup. All hooks
run tooling **inside the `phpfpm` container**.

- **Hooks** — `SessionStart` boots the Docker stack and checks host prerequisites; `PostToolUse` auto-runs
  Rector (on `src`/`tests` PHP), php-cs-fixer, phpstan, twig-cs-fixer, `composer normalize`, prettier, and
  markdownlint on the file you just edited (so single-file changes don't need manual formatting); `PreToolUse`
  blocks edits to generated/locked/secret files
  (`config/reference.php`, lock files, `.env.local`, …); `Stop` validates the DI container (`lint:container`), warns
  on ES index-contract changes (`scripts/claude-hook-check-index-contract.sh`), and warns when a resource changed
  but `public/spec.yaml` was not regenerated (`scripts/claude-hook-check-spec-drift.sh`).
- **Prerequisite:** `jq` must be installed on the **host** — the Edit/Write hooks read the edited file path from the
  tool payload via `jq` and silently no-op without it (`brew install jq`; a `SessionStart` hook warns if missing).
- **Subagents** (`.claude/agents/`): `pr-readiness` (run all CI-equivalent checks), `reload-fixtures` (reload ES
  fixtures / recover a not-ready cluster), and `filter-provider-reviewer` (review ES filter/provider changes for
  query-DSL correctness and index-contract alignment).
- **Skills** (`.claude/skills/`, user-invocable): `/update-api-spec` (regenerate `public/spec.yaml` after changing
  an API resource), `/changelog-entry` (add the CHANGELOG entry in the CI-enforced format), and `/new-resource`
  (scaffold a new index-backed resource following the DTO + provider + filter pattern).
