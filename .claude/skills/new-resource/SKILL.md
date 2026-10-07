---
name: new-resource
description: Scaffold a new read-only API resource backed by an Elasticsearch index, following this repo's DTO + provider + filter pattern. Use when exposing a new index (or a new shape of an existing one) under /api/v2.
disable-model-invocation: true
---

Add a new API Platform resource. This repo's resources are **DTOs backed by Elasticsearch**, never Doctrine
entities — every resource follows the same three-part shape, with `Event` as the canonical template. Read
`src/Api/Dto/Event.php`, `src/Api/State/EventRepresentationProvider.php`, and `tests/ApiPlatform/EventsTest.php`
before starting, and mirror them.

## Steps

1. **Confirm the index.** The seven indices live in `src/Model/IndexName.php`. If the resource maps to an existing
   index, reuse that enum case. If it's a genuinely new index, add a case — and remember the mapping is owned by
   `event-database-imports` (see CLAUDE.md → "Works with event-database-imports"); coordinate the contract there.

2. **DTO** — `src/Api/Dto/<Resource>.php`: an `#[ApiResource]` class declaring operations, pagination
   (`paginationMaximumItemsPerPage`), and `#[ApiFilter(...)]` attributes. The `$id` property is identifier-only
   (PHPStan "unused" errors on it are intentionally ignored); real fields are filled by the provider. Attach
   filters from `src/Api/Filter/ElasticSearch/` (`MatchFilter`, `IdFilter`, `BooleanFilter`, `DateRangeFilter`,
   `DateFilter`, `TagFilter`) — do not invent a Doctrine filter.

3. **Provider** — `src/Api/State/<Resource>RepresentationProvider.php`: implement `ProviderInterface` by extending
   `AbstractProvider`. Use `getFilters()` to compile the declared `#[ApiFilter]` attributes into ES query
   fragments, call `IndexInterface::search()`, and return a `SearchResults` (collection op) or a single array
   (item op). Wire the DTO's `provider:` to this class. Providers are autoconfigured — only touch
   `config/services.yaml` if you need explicit arguments.

4. **Test** — `tests/ApiPlatform/<Resource>Test.php`: extend `AbstractApiTestCase`, `use` `GetEntitiesTestTrait`
   and `GetItemTestTrait`, and set the static props (`$requestPath`, `$resourceClass`, `$itemId`,
   `$unknownItemId`). Add resource-specific filter assertions in a `<Resource>FilterTest.php`. Add fixture rows to
   `tests/resources/<index>.json` so `$itemId` resolves (see `tests/resources/README.md`).

5. **Regenerate the OpenAPI spec** — run the `/update-api-spec` skill (or `task api:spec:export`). CI
   (`.github/workflows/api-spec.yml`) fails if `public/spec.yaml` is stale.

6. **Verify** — `task fixtures:load:test --yes` then `task api:test -- --filter <Resource>`. Run
   `task coding-standards:check` and `task code-analysis` before opening the PR.
