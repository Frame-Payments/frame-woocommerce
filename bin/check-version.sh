#!/usr/bin/env bash
# Verifies the three version strings agree with each other, and that the
# changelog documents the version.
#
# Usage:
#   bin/check-version.sh            # strings must agree with each other
#   bin/check-version.sh 1.2.0      # ...and must equal 1.2.0
#
# On a release build the expected version comes from the GitHub release, so
# the tag is authoritative and this asserts the stamp actually landed.
set -euo pipefail

cd "$(dirname "$0")/.."

PLUGIN_FILE="frame-payments-for-woocommerce.php"
README="readme.txt"

expected="${1:-}"
expected="${expected#v}"

header=$(sed -n 's/^ \* Version: *\([0-9][^ ]*\) *$/\1/p' "$PLUGIN_FILE" | head -1)
constant=$(sed -n "s/^define('FRAME_WC_VERSION', '\([^']*\)');.*/\1/p" "$PLUGIN_FILE" | head -1)
stable=$(sed -n 's/^Stable tag: *\([0-9][^ ]*\) *$/\1/p' "$README" | head -1)

if [ -z "$header" ] || [ -z "$constant" ] || [ -z "$stable" ]; then
  echo "error: could not parse all three version strings" >&2
  echo "  header=[${header}] constant=[${constant}] stable=[${stable}]" >&2
  exit 1
fi

echo "plugin header:    $header"
echo "FRAME_WC_VERSION: $constant"
echo "readme stable:    $stable"

if [ "$header" != "$constant" ] || [ "$header" != "$stable" ]; then
  echo "error: version strings disagree — all three must match" >&2
  echo "hint: run bin/set-version.sh <version> to stamp them" >&2
  exit 1
fi

if [ -n "$expected" ] && [ "$header" != "$expected" ]; then
  echo "error: expected version ${expected}, but source says ${header}" >&2
  exit 1
fi

# The changelog is written by hand; a release must not ship undocumented.
if ! grep -qF "= ${header} =" "$README"; then
  echo "error: readme.txt has no changelog entry for ${header}" >&2
  echo "hint: add a '= ${header} =' section under == Changelog ==" >&2
  exit 1
fi

echo "Version check passed."
