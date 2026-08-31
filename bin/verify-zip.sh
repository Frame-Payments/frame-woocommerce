#!/usr/bin/env bash
# Smoke-tests a built plugin zip before it ships.
#
# A merchant on 1.0.12 hit a fatal in process_payment (UriInterface not found)
# because the distributed zip's vendor bundle was missing psr/http-message.
# This asserts the bundle is actually complete and loadable.
set -euo pipefail

ZIP="${1:-dist/frame-payments-for-woocommerce.zip}"
SLUG="frame-payments-for-woocommerce"

[ -f "$ZIP" ] || { echo "error: $ZIP not found" >&2; exit 1; }

TMP=$(mktemp -d)
trap 'rm -rf "$TMP"' EXIT
unzip -q "$ZIP" -d "$TMP"

ROOT="$TMP/$SLUG"
[ -d "$ROOT" ] || { echo "error: zip does not contain a ${SLUG}/ directory" >&2; exit 1; }

fail=0

# Every runtime dependency composer resolved must be present in the zip.
missing=$(php -r '
$lock = json_decode(file_get_contents($argv[1] . "/composer.lock"), true);
$root = $argv[2];
foreach ($lock["packages"] as $p) {
    if (!is_dir($root . "/vendor/" . $p["name"])) {
        echo $p["name"], "\n";
    }
}
' "$(dirname "$0")/.." "$ROOT")

if [ -n "$missing" ]; then
  echo "error: vendor packages missing from the zip:" >&2
  echo "$missing" | sed 's/^/  - /' >&2
  fail=1
fi

# The specific class whose absence broke 1.0.12 in the field.
if [ ! -f "$ROOT/vendor/psr/http-message/src/UriInterface.php" ]; then
  echo "error: psr/http-message UriInterface.php is missing" >&2
  fail=1
fi

# The autoloader must actually resolve the SDK's transitive interfaces.
php -r '
require $argv[1] . "/vendor/autoload.php";
$required = ["Psr\\Http\\Message\\UriInterface", "Psr\\Http\\Message\\RequestInterface", "Psr\\Http\\Client\\ClientInterface"];
$bad = array_filter($required, fn($c) => !interface_exists($c));
if ($bad) { fwrite(STDERR, "error: not loadable: " . implode(", ", $bad) . "\n"); exit(1); }
' "$ROOT" || fail=1

# Dev cruft must not ship.
for unwanted in tests phpunit.xml .github bin; do
  if [ -e "$ROOT/$unwanted" ]; then
    echo "error: ${unwanted} should not be in the distributed zip" >&2
    fail=1
  fi
done

[ "$fail" -eq 0 ] || exit 1
echo "Zip verified: $(unzip -l "$ZIP" | tail -1)"
