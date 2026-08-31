#!/usr/bin/env bash
# Verifies the three version strings agree, and — when building a tag —
# that they match the tag being released.
#
# The 1.0.9-1.1.0 releases each shipped the *previous* version's header
# because these are hand-synced at release time. This is the guard.
set -euo pipefail

cd "$(dirname "$0")/.."

PLUGIN_FILE="frame-payments-for-woocommerce.php"
README="readme.txt"

header=$(sed -n 's/^ \* Version: *\([0-9][^ ]*\) *$/\1/p' "$PLUGIN_FILE" | head -1)
constant=$(sed -n "s/^define('FRAME_WC_VERSION', '\([^']*\)');.*/\1/p" "$PLUGIN_FILE" | head -1)
stable=$(sed -n 's/^Stable tag: *\([0-9][^ ]*\) *$/\1/p' "$README" | head -1)

fail=0
for pair in "header:$header" "FRAME_WC_VERSION:$constant" "Stable tag:$stable"; do
  if [ -z "${pair#*:}" ]; then
    echo "error: could not parse ${pair%%:*} from source" >&2
    fail=1
  fi
done
[ "$fail" -eq 0 ] || exit 1

echo "plugin header:    $header"
echo "FRAME_WC_VERSION: $constant"
echo "readme stable:    $stable"

if [ "$header" != "$constant" ] || [ "$header" != "$stable" ]; then
  echo "error: version strings disagree — all three must match" >&2
  exit 1
fi

# On a tag build, the tag is the source of truth.
tag="${RELEASE_TAG:-}"
if [ -z "$tag" ] && [ "${GITHUB_REF_TYPE:-}" = "tag" ]; then
  tag="${GITHUB_REF_NAME:-}"
fi

if [ -n "$tag" ]; then
  if [ "${tag#v}" != "$header" ]; then
    echo "error: tag ${tag} does not match version ${header}" >&2
    exit 1
  fi
  echo "tag ${tag} matches."
fi

echo "Version check passed."
