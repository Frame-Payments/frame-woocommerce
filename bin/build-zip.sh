#!/usr/bin/env bash
# Builds the distributable plugin zip into dist/.
# Assumes `composer install --no-dev` has already run.
set -euo pipefail

cd "$(dirname "$0")/.."

SLUG="frame-payments-for-woocommerce"
OUT="dist/${SLUG}.zip"

if [ ! -f vendor/autoload.php ]; then
  echo "error: vendor/autoload.php missing — run composer install --no-dev first" >&2
  exit 1
fi

rm -rf dist build
mkdir -p "build/${SLUG}" dist

# Ship the plugin runtime only: no tests, CI config, or dev tooling.
rsync -a \
  --exclude '.git' \
  --exclude '.github' \
  --exclude '.claude' \
  --exclude 'bin' \
  --exclude 'dist' \
  --exclude 'build' \
  --exclude 'tests' \
  --exclude 'phpunit.xml' \
  --exclude '.phpunit.cache' \
  --exclude '.phpunit.result.cache' \
  --exclude 'coverage' \
  --exclude '.gitignore' \
  --exclude '.DS_Store' \
  --exclude '*.swp' \
  ./ "build/${SLUG}/"

( cd build && zip -qr "../${OUT}" "${SLUG}" )
rm -rf build

echo "Built ${OUT}"
