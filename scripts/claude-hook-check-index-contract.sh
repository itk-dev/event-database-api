#!/bin/sh
# Stop-hook helper: warn when the Elasticsearch index contract changed.
#
# event-database-imports WRITES the ES indices; this repo (event-database-api)
# READS them. The contract (index-name enum + document field names/types) is
# duplicated by hand across both repos with no compile-time link. This repo owns
# only the index-name enum; the document mappings live solely in the importer.
# See CLAUDE.md -> "Works with event-database-imports".
set -u

cd "${CLAUDE_PROJECT_DIR:-.}" || exit 0

CHANGED="$(git status --porcelain 2>/dev/null | awk '{print $NF}')"

echo "$CHANGED" | grep -qE '^src/Model/IndexName\.php' || exit 0

cat >&2 <<'MSG'
WARN: the Elasticsearch index-name enum changed (src/Model/IndexName.php).
      event-database-imports writes these indices and duplicates the contract by
      hand in its src/Model/Indexing/IndexNames.php. An index name that does not
      match what the importer creates (and populates behind the queried alias)
      yields an empty or failing API resource. Coordinate the change with
      event-database-imports before deploying.
MSG

exit 0
