#!/bin/sh
set -eu
. "$(dirname "$0")/../drills/lib.sh"

url=${STACK_URL:-http://localhost:8090}

wp() {
    docker compose exec -T -u www-data wordpress wp --url="$url" "$@"
}

as_root() {
    docker compose exec -T wordpress "$@"
}

wait_for_wordpress() {
    attempts=0
    until as_root test -f /var/www/html/wp-config.php 2>/dev/null; do
        attempts=$((attempts + 1))
        [ "$attempts" -lt 60 ] || fail 'e2e: WordPress did not become ready'
        sleep 2
    done
}
