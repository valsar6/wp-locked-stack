#!/bin/sh
set -eu
. "$(dirname "$0")/lib.sh"

workspace=$(mktemp -d)
trap 'rm -rf "$workspace"' EXIT

cp composer.json composer.lock "$workspace/"
cp -R packages "$workspace/packages"

resolve() {
    composer update --working-dir="$workspace" --dry-run --no-dev --no-scripts --no-interaction 'wp-locked-stack/*'
}

expect_success 'contract: plugin 1.1.0 satisfies the theme' resolve

sed -i 's/"version": "1.1.0"/"version": "1.0.0"/' "$workspace/packages/fixed-term-access/composer.json"

expect_failure 'contract: Composer refuses plugin 1.0.0 because the theme requires ^1.1' \
    'wp-locked-stack/fixed-term-access' resolve
