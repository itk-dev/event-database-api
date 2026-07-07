#!/bin/sh
# Stop-hook helper: warn when an API resource changed but public/spec.yaml did not.
#
# public/spec.yaml is the committed OpenAPI export and is checked in CI
# (.github/workflows/api-spec.yml). Editing an #[ApiResource] DTO without
# regenerating the spec fails that check. This warns locally, before CI does.
# See CLAUDE.md -> "Claude Code automation".
set -u

cd "${CLAUDE_PROJECT_DIR:-.}" || exit 0

CHANGED="$(git status --porcelain 2>/dev/null | awk '{print $NF}')"

# A DTO changed?
echo "$CHANGED" | grep -qE '^src/Api/Dto/.*\.php$' || exit 0
# ...but the spec did not.
echo "$CHANGED" | grep -qE '^public/spec\.yaml$' && exit 0

cat >&2 <<'MSG'
WARN: an API resource under src/Api/Dto/ changed but public/spec.yaml was not
      regenerated. CI (.github/workflows/api-spec.yml) checks the committed
      OpenAPI export and will fail on a stale spec. Regenerate it with:
        task api:spec:export      (or the /update-api-spec skill)
MSG

exit 0
