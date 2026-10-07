---
name: changelog-entry
description: Add or fix this repo's CHANGELOG.md entry for the current PR, in the exact house format. Use before opening a PR or when resolving a CHANGELOG merge conflict.
disable-model-invocation: true
---

Add a changelog line for the current work to `CHANGELOG.md`, following this repo's convention exactly (getting it wrong causes the `changelog` CI check in `.github/workflows/changelog.yaml` to fail and creates repeated merge conflicts).

## Format

Under the `## [Unreleased]` heading, entries are a **two-line wrapped** list item:

```
- [PR-N](https://github.com/itk-dev/event-database-api/pull/N)
  Short imperative description of the change
```

Rules:

- One entry per PR. Ordered by **descending PR number** (newest on top).
- The URL is always the `event-database-api` repo (not `event-database-imports`).
- **Keep it short and terse — match the surrounding entries.** The description is a single concise noun or
  imperative phrase naming *what* changed, not a sentence (or paragraph) explaining *how*. It fits on one wrapped
  line and almost always well under the 120-char limit (markdownlint MD013). If you're tempted to list mechanisms,
  reasons, or multiple clauses, cut them — that detail belongs in the PR description, not the changelog.
  - Good (real entries): `Symfony 7.3 and PHP 8.4` · `Re-lint YAML files` ·
    `Add Claude Code project setup (CLAUDE.md, agents, skills)`
  - Too long: `Adapt Claude Code setup from event-database-imports: fix the Edit/Write hook mechanism (read the
    file path from the tool payload via jq instead of the unset CLAUDE_FILE_PATH), guard the …`
- Some historical entries are plain bullets without a PR link (e.g. dependency bumps) — that's fine for changes
  without a PR, but PR-based work always gets the `[PR-N]` form.

## Two-step PR number

The PR number isn't known until the PR exists. So:

1. Add the entry now with a `PR-XX` placeholder and `pull/XX` URL.
2. After the PR is opened (`gh pr view --json number`), replace `XX` with the real number in a follow-up commit (`docs: set PR number in CHANGELOG entry`).

## Steps

1. `git fetch origin develop` and read the current `[Unreleased]` block so you insert in the right order and don't duplicate.
2. Insert the entry at the correct descending-number position.
3. Lint: `docker compose run --rm markdownlint markdownlint CHANGELOG.md`.
4. If a PR already exists, set the real number; otherwise leave `XX` and remind the user of step 2.
