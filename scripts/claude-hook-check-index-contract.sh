#!/bin/sh
# Stop-hook helper: warn when the Elasticsearch index contract changed.
#
# event-database-imports WRITES the ES indices; this repo (event-database-api)
# READS them. The contract (index-name enum + document field names/types) is
# duplicated by hand across both repos with no compile-time link. This repo owns
# the index-name enum (src/Model/IndexName.php) and a production-parity COPY of
# the importer's mappings (tests/resources/mappings/) that the test harness
# applies. See CLAUDE.md -> "Works with event-database-imports".
set -u

cd "${CLAUDE_PROJECT_DIR:-.}" || exit 0

CHANGED="$(git status --porcelain 2>/dev/null | awk '{print $NF}')"

echo "$CHANGED" | grep -qE '^(src/Model/IndexName\.php|tests/resources/mappings/)' || exit 0

cat >&2 <<'MSG'
WARN: the Elasticsearch index contract changed (src/Model/IndexName.php or
      tests/resources/mappings/). event-database-imports owns the source of
      truth: index names in its src/Model/Indexing/IndexNames.php and mappings in
      src/Model/Indexing/Mappings/. A name or field/type that diverges from what
      the importer creates yields empty or failing API resources, and stale test
      mappings make the filter suite green for semantics production does not have.
      Reconcile against event-database-imports before deploying.
MSG

exit 0
