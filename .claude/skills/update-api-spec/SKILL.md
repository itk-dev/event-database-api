---
name: update-api-spec
description: Regenerate and stage the OpenAPI spec after API resource changes
user-invocable: true
---

The committed OpenAPI spec lives at `public/spec.yaml` (single YAML file — there is no separate JSON
export). `.github/workflows/api-spec.yml` fails the PR if it drifts from the resources defined under
`src/Api/`, and additionally diffs the spec against the base branch to flag breaking changes.

After touching anything under `src/Api/Dto/`, `src/Api/State/`, or `src/Api/Filter/`:

1. Regenerate the spec: `task api:spec:export`
   (This is shorthand for `bin/console api:openapi:export --yaml --output=public/spec.yaml --no-interaction`.)
2. Inspect changes: `git diff public/spec.yaml`
3. If the diff is non-empty, stage the file: `git add public/spec.yaml`
4. Summarise what changed — new/removed operations, modified parameters, schema diffs — so the human
   reviewer can sanity-check for unintended breaking changes before pushing.
