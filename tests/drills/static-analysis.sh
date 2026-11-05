#!/bin/sh
set -eu
. "$(dirname "$0")/lib.sh"

workspace=$(mktemp -d)
trap 'rm -rf "$workspace"' EXIT

cp -R packages "$workspace/packages"

analyse() {
    vendor/bin/phpstan analyse --no-progress --memory-limit=1G -c phpstan.neon \
        "$workspace/packages/fixed-term-access" "$workspace/packages/stack-theme"
}

expect_success 'static analysis: theme and plugin agree' analyse

php -r '
$file = $argv[1];
$source = file_get_contents($file);
$pattern = "/    public static function remainingDays\(int \\\$userId\): \?int\n    \{.*?\n    \}\n\n/s";
$edited = preg_replace($pattern, "", $source, 1, $count);
if ($count !== 1) {
    fwrite(STDERR, "Could not remove remainingDays\n");
    exit(1);
}
file_put_contents($file, $edited);
' "$workspace/packages/fixed-term-access/src/Access.php"

expect_failure 'static analysis: PHPStan rejects the theme when the plugin API is removed' \
    'remainingDays' analyse
