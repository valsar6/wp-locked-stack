#!/bin/sh
set -u

image=${1:-}
url=${STACK_URL:-http://localhost:8090}
backups=${STACK_BACKUPS:-backups}

log() {
    printf 'deploy: %s\n' "$1"
}

abort() {
    log "$1"
    exit 1
}

wp() {
    docker compose exec -T -u www-data wordpress wp --url="$url" "$@"
}

swap_to() {
    STACK_IMAGE=$1 docker compose up -d --no-build --no-deps --force-recreate --renew-anon-volumes wordpress >/dev/null 2>&1 || return 1
    attempts=0
    until docker compose exec -T wordpress test -f /var/www/html/wp-config.php 2>/dev/null; do
        attempts=$((attempts + 1))
        [ "$attempts" -lt 60 ] || return 1
        sleep 2
    done
}

smoke() {
    wp stack verify || return 1
    attempts=0
    until curl -fsS -o /dev/null "$url/"; do
        attempts=$((attempts + 1))
        [ "$attempts" -lt 5 ] || return 1
        sleep 2
    done
}

backup() {
    mkdir -p "$backups" || return 1
    backup_file="$backups/$(date -u +%Y%m%dT%H%M%SZ).sql"
    (umask 077 && docker compose exec -T db sh -c 'mariadb-dump -u"$MARIADB_USER" -p"$MARIADB_PASSWORD" "$MARIADB_DATABASE"' > "$backup_file") || return 1
    tail -n 1 "$backup_file" | grep -q 'Dump completed'
}

migrate() {
    wp core update-db || return 1
    if wp plugin is-active woocommerce >/dev/null 2>&1; then
        wp wc update || return 1
    fi
}

recover() {
    log "failed: $1"
    if [ -n "$changes" ]; then
        log 'plugin or core versions changed, so migrations may have run. Not rolling back automatically.'
        log "database backup: $backup_file"
        log "previous image: $previous_image"
        log 'decide: deploy a fix going forward, or redeploy the previous image and restore the backup, losing data written since.'
        exit 3
    fi

    log "no versions changed, rolling back to $previous_image"
    if swap_to "$previous_image" && smoke; then
        log 'rolled back'
        exit 2
    fi
    log 'rollback did not pass the smoke test'
    exit 3
}

[ -n "$image" ] || abort 'usage: deploy.sh <image>'
docker image inspect "$image" >/dev/null 2>&1 || abort "image not found: $image"

container=$(docker compose ps -q wordpress)
[ -n "$container" ] || abort 'no running wordpress container'
previous_image=$(docker inspect --format '{{.Image}}' "$container") || abort 'cannot read the running image'

changes=$(docker compose exec -T wordpress cat /opt/stack/manifest.json \
    | docker run -i --rm --entrypoint php "$image" /opt/stack/verify/bin/diff-manifests.php php://stdin /opt/stack/manifest.json) \
    || abort 'cannot compare manifests'

backup_file=''
if [ -n "$changes" ]; then
    log 'version changes:'
    printf '%s\n' "$changes"
    backup || abort 'backup failed, nothing was changed'
    log "database backup: $backup_file"
else
    log 'no version changes, skipping backup and migrations'
fi

swap_to "$image" || recover 'the new container did not start'

if [ -n "$changes" ]; then
    migrate || recover 'migrations failed'
fi

smoke || recover 'smoke test failed'

log "deployed $image"
