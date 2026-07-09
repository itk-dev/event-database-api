---
name: pr-readiness
description: Run all CI-equivalent checks locally before creating a PR
model: haiku
---

Mirror what `.github/workflows/*.yaml` runs on a PR. Every step executes inside Docker (the project
ships only its `phpfpm` / `prettier` / `markdownlint` services — there is no `node` service and no
JS/CSS to lint). Stop early if a critical check fails.

## Checks

1. **Composer validate**: `docker compose exec -T phpfpm composer validate --strict`
2. **Composer normalize (dry-run)**: `docker compose exec -T phpfpm composer normalize --dry-run`
3. **PHP coding standards**: `task coding-standards:php:check`
4. **Twig coding standards**: `task coding-standards:twig:check`
5. **YAML coding standards**: `task coding-standards:yaml:check`
6. **Markdown coding standards**: `task coding-standards:markdown:check`
7. **PHPStan (level 6)**: `task code-analysis:phpstan`
8. **Rector (dry-run)**: `task code-analysis:rector` — the `Rector` gate fails on any suggested change; run `task code-analysis:rector:apply` to fix.
9. **Test fixtures + API tests**: `task fixtures:load:test --yes && task api:test`. Tests hit a real Elasticsearch, so fixtures must be loaded first. If the load fails with "No alive nodes", run `docker compose up --detach --wait` and retry — see the `reload-fixtures` agent for the full recovery dance.
10. **API spec up to date** (mirrors `.github/workflows/api-spec.yml`):

- `task api:spec:export`
- `git diff --exit-code public/spec.yaml` — must be clean.
1. **CHANGELOG updated**: `git diff develop -- CHANGELOG.md` should show at least one entry under `## [Unreleased]`.

## Output

Report a summary table with columns: Check Name, Status (pass/fail), and error output for failures.
