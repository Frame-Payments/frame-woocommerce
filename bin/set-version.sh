#!/usr/bin/env bash
# Stamps a version into the three places that must agree.
#
# Usage: bin/set-version.sh 1.2.0
#
# The version is an input, not something read from source — the GitHub release
# is the single source of truth. Releases 1.0.9 through 1.1.0 each shipped the
# previous version's header because these were hand-synced.
set -euo pipefail

cd "$(dirname "$0")/.."

version="${1:-}"
version="${version#v}"

if ! printf '%s' "$version" | grep -qE '^[0-9]+\.[0-9]+\.[0-9]+$'; then
  echo "usage: $0 <version>   (e.g. 1.2.0)" >&2
  exit 1
fi

PLUGIN_FILE="frame-payments-for-woocommerce.php"
README="readme.txt"

# Anchored so these only ever match the header block and the define.
perl -i -pe "s/^ \\* Version: {5}\\S+$/ * Version:     ${version}/" "$PLUGIN_FILE"
perl -i -pe "s/^define\\('FRAME_WC_VERSION', '[^']*'\\);/define('FRAME_WC_VERSION', '${version}');/" "$PLUGIN_FILE"
perl -i -pe "s/^Stable tag: \\S+$/Stable tag: ${version}/" "$README"

echo "Stamped ${version}"
