# Changelog

![keep a changelog](https://img.shields.io/badge/Keep%20a%20Changelog-v1.1.0-brightgreen.svg?logo=data%3Aimage%2Fsvg%2Bxml%3Bbase64%2CPHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIGZpbGw9IiNmMTVkMzAiIHZpZXdCb3g9IjAgMCAxODcgMTg1Ij48cGF0aCBkPSJNNjIgN2MtMTUgMy0yOCAxMC0zNyAyMmExMjIgMTIyIDAgMDAtMTggOTEgNzQgNzQgMCAwMDE2IDM4YzYgOSAxNCAxNSAyNCAxOGE4OSA4OSAwIDAwMjQgNCA0NSA0NSAwIDAwNiAwbDMtMSAxMy0xYTE1OCAxNTggMCAwMDU1LTE3IDYzIDYzIDAgMDAzNS01MiAzNCAzNCAwIDAwLTEtNWMtMy0xOC05LTMzLTE5LTQ3LTEyLTE3LTI0LTI4LTM4LTM3QTg1IDg1IDAgMDA2MiA3em0zMCA4YzIwIDQgMzggMTQgNTMgMzEgMTcgMTggMjYgMzcgMjkgNTh2MTJjLTMgMTctMTMgMzAtMjggMzhhMTU1IDE1NSAwIDAxLTUzIDE2bC0xMyAyaC0xYTUxIDUxIDAgMDEtMTItMWwtMTctMmMtMTMtNC0yMy0xMi0yOS0yNy01LTEyLTgtMjQtOC0zOWExMzMgMTMzIDAgMDE4LTUwYzUtMTMgMTEtMjYgMjYtMzMgMTQtNyAyOS05IDQ1LTV6TTQwIDQ1YTk0IDk0IDAgMDAtMTcgNTQgNzUgNzUgMCAwMDYgMzJjOCAxOSAyMiAzMSA0MiAzMiAyMSAyIDQxLTIgNjAtMTRhNjAgNjAgMCAwMDIxLTE5IDUzIDUzIDAgMDA5LTI5YzAtMTYtOC0zMy0yMy01MWE0NyA0NyAwIDAwLTUtNWMtMjMtMjAtNDUtMjYtNjctMTgtMTIgNC0yMCA5LTI2IDE4em0xMDggNzZhNTAgNTAgMCAwMS0yMSAyMmMtMTcgOS0zMiAxMy00OCAxMy0xMSAwLTIxLTMtMzAtOS01LTMtOS05LTEzLTE2YTgxIDgxIDAgMDEtNi0zMiA5NCA5NCAwIDAxOC0zNSA5MCA5MCAwIDAxNi0xMmwxLTJjNS05IDEzLTEzIDIzLTE2IDE2LTUgMzItMyA1MCA5IDEzIDggMjMgMjAgMzAgMzYgNyAxNSA3IDI5IDAgNDJ6bS00My03M2MtMTctOC0zMy02LTQ2IDUtMTAgOC0xNiAyMC0xOSAzN2E1NCA1NCAwIDAwNSAzNGM3IDE1IDIwIDIzIDM3IDIyIDIyLTEgMzgtOSA0OC0yNGE0MSA0MSAwIDAwOC0yNCA0MyA0MyAwIDAwLTEtMTJjLTYtMTgtMTYtMzEtMzItMzh6bS0yMyA5MWgtMWMtNyAwLTE0LTItMjEtN2EyNyAyNyAwIDAxLTEwLTEzIDU3IDU3IDAgMDEtNC0yMCA2MyA2MyAwIDAxNi0yNWM1LTEyIDEyLTE5IDI0LTIxIDktMyAxOC0yIDI3IDIgMTQgNiAyMyAxOCAyNyAzM3MtMiAzMS0xNiA0MGMtMTEgOC0yMSAxMS0zMiAxMXptMS0zNHYxNGgtOFY2OGg4djI4bDEwLTEwaDExbC0xNCAxNSAxNyAxOEg5NnoiLz48L3N2Zz4K)

All notable changes to this project will be documented in this file.

See [keep a changelog] for information about writing changes to this log.

## [Unreleased]

- [PR-55](https://github.com/itk-dev/event-database-api/pull/55)
  Self-heal the API-spec "up to date" PR comment (resolve to ✅ once the spec matches, no stale 🛑)
- [PR-53](https://github.com/itk-dev/event-database-api/pull/53)
  Compare effective schemas in the oasdiff gate (flatten-allof) so allOf/$ref restructures are not miscounted
- [PR-52](https://github.com/itk-dev/event-database-api/pull/52)
  Pin the problem+json error contract: 401 served as RFC 7807, resource reads in problem+json yield 406
- [PR-51](https://github.com/itk-dev/event-database-api/pull/51)
  Show the API-spec diff summary inline in the PR comment (collapsed) alongside the oasdiff review link
- [PR-49](https://github.com/itk-dev/event-database-api/pull/49)
  Bump oasdiff-action to v0.1.5 in the API-spec workflow
- [PR-48](https://github.com/itk-dev/event-database-api/pull/48)
  Add an os2display consumer contract test suite exercising the endpoints and filters its feed helper depends on
- [PR-47](https://github.com/itk-dev/event-database-api/pull/47)
  Add an aarhusguiden consumer contract test suite exercising the endpoints and filters the site depends on
- [PR-46](https://github.com/itk-dev/event-database-api/pull/46)
  Upgrade api-platform/core 4.1 to 4.3. Runtime responses are unchanged except that 401/404 error bodies now use
  `hydra:title`/`hydra:description` instead of the unprefixed `title` (`status`, `type` and `detail` unchanged)
- [PR-45](https://github.com/itk-dev/event-database-api/pull/45)
  Check committed index mappings against event-database-imports@develop in CI
- [PR-44](https://github.com/itk-dev/event-database-api/pull/44)
  Validate deep payload schemas (nested objects, field types) for every resource
- [PR-43](https://github.com/itk-dev/event-database-api/pull/43)
  Assert filter identities (not counts), sort order, and pagination edge cases
- [PR-42](https://github.com/itk-dev/event-database-api/pull/42)
  Return HTTP 400 (not 500) for malformed date-range filter input; unskip FilterErrorTest
- [PR-41](https://github.com/itk-dev/event-database-api/pull/41)
  Run the Code Review workflow via docker compose directly (drop Task) with vendor caching and image pre-pull
- [PR-40](https://github.com/itk-dev/event-database-api/pull/40)
  Improve the API-spec workflow (path filter, permissions, vendor cache, oasdiff)
- [PR-39](https://github.com/itk-dev/event-database-api/pull/39)
  Update GitHub Actions to latest: actions/checkout v7 and go-task/setup-task v2
- [PR-38](https://github.com/itk-dev/event-database-api/pull/38)
  Extract SearchParamsBuilder from ElasticSearchIndex and unit-test the query DSL
- [PR-37](https://github.com/itk-dev/event-database-api/pull/37)
  Upload test coverage to Codecov in CI
- [PR-36](https://github.com/itk-dev/event-database-api/pull/36)
  Add unit tests pinning the Elasticsearch filters' query DSL and parameter descriptors
- [PR-35](https://github.com/itk-dev/event-database-api/pull/35)
  Test against production-parity Elasticsearch mappings (dynamic: strict) so filter tests exercise real field semantics
- [PR-34](https://github.com/itk-dev/event-database-api/pull/34)
  Document the item-as-collection quirk as a versioning TODO and fix the README fixtures wording (no Doctrine)
- [PR-33](https://github.com/itk-dev/event-database-api/pull/33)
  Mature the API test suite ahead of the API Platform upgrade (contract, filter, pagination and error tests)
- [PR-32](https://github.com/itk-dev/event-database-api/pull/32)
  Pay down the PHPStan baseline: fix the mechanical strict-rule findings (34 → 10)
- [PR-31](https://github.com/itk-dev/event-database-api/pull/31)
  Align dev tooling with event-database-imports: PHPStan level 8 + strict rules, PHP 8.4, twig-cs-fixer v4, PHPUnit 13
- [PR-30](https://github.com/itk-dev/event-database-api/pull/30)
  Update vulnerable dependencies (twig, symfony, guzzle) and audit the lock file in CI
- [PR-29](https://github.com/itk-dev/event-database-api/pull/29)
  Add Claude Code skills, filter/provider reviewer subagent, and spec-drift hook
- [PR-28](https://github.com/itk-dev/event-database-api/pull/28)
  Adapt Claude Code hooks from event-database-imports and guard the ES index contract
- [PR-27](https://github.com/itk-dev/event-database-api/pull/27)
  Add Claude Code project setup (CLAUDE.md, agents, skills)

## [1.2.2] - 2026-05-22

- [PR-26](https://github.com/itk-dev/event-database-api/pull/26)
  Symfony 7.4 and dependencies, CVE's on both Symfony and Twig

## [1.2.1] - 2026-03-06

- Update composer dependencies

## [1.2.0] - 2025-09-02

- [PR-25](https://github.com/itk-dev/event-database-api/pull/25)  
  - Lock api-platform to 4.1.* to avoid breaking API changes
  - Add github action to check and validate API changes
  - Clarify date filter format in api spec
  - Dependency updates
- [PR-24](https://github.com/itk-dev/event-database-api/pull/24)
  Symfony 7.3 and PHP 8.4
- [PR-23](https://github.com/itk-dev/event-database-api/pull/23)
  Re-lint YAML files
- [PR-21](https://github.com/itk-dev/event-database-api/pull/21)
  Linted YAML
- [PR-18](https://github.com/itk-dev/event-database-api/pull/18)
  - Updated docker compose setup
  - Added simple API tests and resolved some deprecations
  - Upgraded to API platform v4
  - Updated composer packages (security update) and recipes
  - Replaces [Psalm](https://psalm.dev/) with [PHPStan](https://phpstan.org/) (via
    <https://github.com/phpstan/phpstan-symfony>).

## [1.1.1] - 2025-03-28

- Fix date range filter error for updated field

## [1.1.0] - 2025-03-20

### Added

- Implemented and switched to date range filter, compatible with deprecated date filter

### Changed

- Deprecate date filter

## [1.0.1] - 2025-03-12

### Added

- Add labels to Woodpecker workflow
- Add stg Woodpecker workflow
- Add missing license

### Changed

- Updated GitHub workflow images.

## [1.0.0] - 2024-12-13

### Added

- Symfony core.
- Added fixture data loader service and command to load fixtures.
- Added basic index service.
- Basic events DTO added.
- Added tags filter
- Added pagination and match filter
- Added date filters
- Ensure combined filters are possible
- Added api endpoinst for occurrences, location, tags, vocabularies and filters
- Sort response
- Update composer dependencies
- Added api-key auth
- Added PethPrefix scope to traefik rules to allow co-hosting with legacy eventdb
- Added multi-value filtering for Lactions and Organizations

[keep a changelog]: https://keepachangelog.com/en/1.1.0/
[Unreleased]: https://github.com/itk-dev/event-database-api/compare/1.2.0...HEAD
[1.2.0]: https://github.com/itk-dev/event-database-api/compare/1.1.0...1.2.0
[1.1.0]: https://github.com/itk-dev/event-database-api/releases/tag/1.1.0
[1.0.1]: https://github.com/itk-dev/event-database-api/releases/tag/1.0.1
[1.0.0]: https://github.com/itk-dev/event-database-api/releases/tag/1.0.0
