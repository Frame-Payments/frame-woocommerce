#!/usr/bin/env bash
# Verifies a published release from the outside: downloads the attached zip
# and checks it is what merchants should receive.
#
# Usage: bin/check-release.sh 1.1.1
#
# This is the post-release counterpart to verify-zip.sh. That one runs inside
# CI on a zip it just built; this one checks what actually shipped — the
# release asset, the tag, and main — so a workflow that half-succeeded is
# visible rather than silent.
set -euo pipefail

version="${1:-}"
version="${version#v}"

if ! printf '%s' "$version" | grep -qE '^[0-9]+\.[0-9]+\.[0-9]+$'; then
  echo "usage: $0 <version>   (e.g. 1.1.1)" >&2
  exit 1
fi

cd "$(dirname "$0")/.."

SLUG="frame-payments-for-woocommerce"
fail=0
note() { printf '  %-38s %s\n' "$1" "$2"; }
bad()  { fail=1; note "$1" "FAIL — $2"; }

# The release may be tagged with or without a leading v.
tag=""
for candidate in "$version" "v$version"; do
  if gh release view "$candidate" >/dev/null 2>&1; then tag="$candidate"; break; fi
done
if [ -z "$tag" ]; then
  echo "error: no release found for ${version} (tried '${version}' and 'v${version}')" >&2
  exit 1
fi

echo "Release ${tag}"

echo
echo "Workflow run"
run=$(gh run list --workflow=Release --limit 20 \
        --json databaseId,headBranch,conclusion,status,displayTitle \
        --jq "[.[] | select(.conclusion != null)] | .[0]" 2>/dev/null || echo "")
if [ -z "$run" ] || [ "$run" = "null" ]; then
  note "latest Release run" "none found (check manually)"
else
  conclusion=$(printf '%s' "$run" | php -r 'echo json_decode(stream_get_contents(STDIN),true)["conclusion"] ?? "?";')
  runid=$(printf '%s' "$run" | php -r 'echo json_decode(stream_get_contents(STDIN),true)["databaseId"] ?? "?";')
  if [ "$conclusion" = "success" ]; then
    note "latest Release run" "success (run ${runid})"
  else
    bad "latest Release run" "${conclusion} (gh run view ${runid} --log-failed)"
  fi
fi

echo
echo "Release asset"
asset="${SLUG}.zip"
if ! gh release view "$tag" --json assets --jq '.assets[].name' 2>/dev/null | grep -qx "$asset"; then
  bad "$asset attached" "no zip on the release — the build step likely failed"
  echo
  echo "Result: FAILED"
  exit 1
fi
note "$asset attached" "yes"

tmp=$(mktemp -d)
trap 'rm -rf "$tmp"' EXIT
gh release download "$tag" --pattern "$asset" --dir "$tmp" --clobber >/dev/null 2>&1
unzip -q "$tmp/$asset" -d "$tmp/x"
root="$tmp/x/$SLUG"

if [ ! -d "$root" ]; then
  bad "zip layout" "no ${SLUG}/ directory inside the zip"
  echo; echo "Result: FAILED"; exit 1
fi
note "zip size" "$(du -h "$tmp/$asset" | cut -f1), $(find "$root" -type f | wc -l | tr -d ' ') files"

echo
echo "Version strings in the shipped zip"
h=$(sed -n 's/^ \* Version: *\([0-9][^ ]*\) *$/\1/p' "$root/$SLUG.php" | head -1)
c=$(sed -n "s/^define('FRAME_WC_VERSION', '\([^']*\)');.*/\1/p" "$root/$SLUG.php" | head -1)
s=$(sed -n 's/^Stable tag: *\([0-9][^ ]*\) *$/\1/p' "$root/readme.txt" | head -1)
for pair in "plugin header:$h" "FRAME_WC_VERSION:$c" "readme Stable tag:$s"; do
  label="${pair%%:*}"; got="${pair#*:}"
  if [ "$got" = "$version" ]; then note "$label" "$got"; else bad "$label" "reads '${got}', expected ${version}"; fi
done

if grep -qF "= ${version} =" "$root/readme.txt"; then
  note "changelog entry" "present"
else
  bad "changelog entry" "no '= ${version} =' section"
fi

echo
echo "Vendor bundle"
missing=$(php -r '
$lock = json_decode(file_get_contents($argv[1]), true);
foreach ($lock["packages"] as $p) {
    if (!is_dir($argv[2] . "/vendor/" . $p["name"])) echo $p["name"], "\n";
}
' "composer.lock" "$root")
if [ -n "$missing" ]; then
  bad "runtime packages" "missing: $(printf '%s' "$missing" | tr '\n' ' ')"
else
  note "runtime packages" "all present ($(php -r '
    echo count(json_decode(file_get_contents("composer.lock"), true)["packages"]);'))"
fi

# The specific failure a merchant hit on 1.0.12.
if php -r '
require $argv[1] . "/vendor/autoload.php";
$need = ["Psr\\Http\\Message\\UriInterface", "Psr\\Http\\Client\\ClientInterface", "Frame\\Client"];
foreach ($need as $x) {
    if (!interface_exists($x) && !class_exists($x)) { fwrite(STDERR, $x); exit(1); }
}
' "$root" 2>/dev/null; then
  note "autoloader resolves SDK + PSR" "yes"
else
  bad "autoloader resolves SDK + PSR" "a required class did not load"
fi

for unwanted in tests phpunit.xml .github bin; do
  [ -e "$root/$unwanted" ] && bad "dev files excluded" "${unwanted} shipped"
done
[ -e "$root/tests" ] || note "dev files excluded" "yes"

echo
echo "Tag and main"
git fetch --quiet origin "refs/tags/${tag}:refs/tags/${tag}" --force 2>/dev/null || true
git fetch --quiet origin main 2>/dev/null || true

tag_header=$(git show "${tag}:${SLUG}.php" 2>/dev/null | sed -n 's/^ \* Version: *\([0-9][^ ]*\) *$/\1/p' | head -1)
if [ "$tag_header" = "$version" ]; then
  note "tag tree header" "$tag_header"
else
  bad "tag tree header" "reads '${tag_header:-?}' — tag was not moved onto the stamp commit"
fi

main_header=$(git show "origin/main:${SLUG}.php" 2>/dev/null | sed -n 's/^ \* Version: *\([0-9][^ ]*\) *$/\1/p' | head -1)
if [ "$main_header" = "$version" ]; then
  note "origin/main header" "$main_header"
else
  bad "origin/main header" "reads '${main_header:-?}' — the stamp commit did not land on main"
fi

echo
if [ "$fail" -eq 0 ]; then
  echo "Result: PASSED — ${tag} shipped correctly."
else
  echo "Result: FAILED — see the FAIL lines above."
  exit 1
fi
