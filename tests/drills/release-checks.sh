#!/bin/sh
set -eu
. "$(dirname "$0")/lib.sh"

workspace=$(mktemp -d)
trap 'rm -rf "$workspace"' EXIT

cp -R composer.json composer.lock Dockerfile packages tools "$workspace/"
cd "$workspace"
git init -q
git add .
git -c user.name=drill -c user.email=drill@example.test commit -q -m baseline
git tag fixed-term-access/v1.1.0
git tag stack-theme/v1.0.0

check() {
    php tools/release-checks/bin/check.php
}

set_lock_version() {
    php -r '
        [, $package, $version] = $argv;
        $lock = json_decode(file_get_contents("composer.lock"), true);
        foreach (["packages", "packages-dev"] as $section) {
            foreach ($lock[$section] as $index => $row) {
                if ($row["name"] === $package) {
                    $lock[$section][$index]["version"] = $version;
                }
            }
        }
        file_put_contents("composer.lock", json_encode($lock, JSON_PRETTY_PRINT));
    ' "$1" "$2"
}

plugin=packages/fixed-term-access

expect_success 'release checks: tagged tree is clean' check

echo '// change' >> "$plugin/src/Term.php"
expect_failure 'release checks: code change without a bump is rejected' 'bump the version' check

sed -i 's/"version": "1.1.0"/"version": "1.2.0"/' "$plugin/composer.json"
expect_failure 'release checks: bump without the header is rejected' 'header says 1.1.0' check

sed -i 's/Version:           1.1.0/Version:           1.2.0/' "$plugin/fixed-term-access.php"
expect_failure 'release checks: bump without the lockfile is rejected' 'composer.lock says 1.1.0' check

set_lock_version wp-locked-stack/fixed-term-access 1.2.0
expect_success 'release checks: complete bump is accepted' check

set_lock_version wp-plugin/woocommerce 11.3.0
expect_failure 'release checks: WooCommerce ahead of its stubs is rejected' 'analysed against php-stubs/woocommerce-stubs' check

set_lock_version wp-plugin/woocommerce 11.2.2
mv .git .git-hidden
expect_failure 'release checks: unreadable git history fails instead of passing' 'Cannot read git history' check
