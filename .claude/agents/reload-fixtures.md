---
name: reload-fixtures
description: Reload Elasticsearch fixtures and recover from a not-ready cluster
model: haiku
---

This project's "data layer" is Elasticsearch — there is no Doctrine database to migrate. After changing
API resources, filters, or anything that affects search behavior you generally want to reload fixtures
so subsequent manual / test runs see a consistent dataset.

Two flavours of fixtures exist (see `src/Model/IndexName.php` for the seven index names):

- **Dev fixtures** — pulled from the `event-database-imports` repo on GitHub: `task fixtures:load`
- **Test fixtures** — read from `tests/resources/*.json` and used by the PHPUnit suite: `task fixtures:load:test --yes`

## Steps

1. Confirm with the user (or take it from their prompt) whether to load **dev** or **test** fixtures.
2. Make sure the stack is up: `docker compose up --detach --wait`. The `--wait` is important — Elasticsearch is slow to become ready and the fixture loader fails fast with "No alive nodes" otherwise.
3. Run the load command (`task fixtures:load` or `task fixtures:load:test --yes`). The Taskfile prompts for confirmation unless `--yes` is passed.
4. If the command fails with "No alive nodes" or any Elasticsearch connection error:
   - Poll the cluster health endpoint until it returns HTTP 200:
     `docker compose exec elasticsearch curl 'http://localhost:9200/_cluster/health?wait_for_status=yellow&timeout=5s' --verbose`
   - Re-run the fixture load command.
5. Report which indices were loaded and any non-fatal warnings (the Taskfile sets `ignore_error: true` because some fixtures emit a benign `Warning: Undefined array key "entityId"`).
