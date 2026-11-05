#!/bin/sh
set -eu
. "$(dirname "$0")/lib.sh"

backups=$(mktemp -d)
export STACK_BACKUPS="$backups"
cleanup() {
    rm -rf "$backups"
    docker image rm -f wp-locked-stack:code-good wp-locked-stack:code-bad wp-locked-stack:upgrade-good wp-locked-stack:upgrade-bad >/dev/null 2>&1 || true
}
trap cleanup EXIT

theme=/usr/src/wordpress/wp-content/themes/stack-theme
plugin=/usr/src/wordpress/wp-content/plugins/woocommerce/woocommerce.php
lock=/opt/stack/composer.lock
rebuild="php /opt/stack/verify/bin/build-manifest.php $lock /usr/src/wordpress /opt/stack/manifest.json"

derive() {
    docker build -q -t "wp-locked-stack:$1" - >/dev/null <<EOF
FROM wp-locked-stack:local
RUN $2 && $rebuild
EOF
}

running_image() {
    docker inspect --format '{{.Image}}' "$(docker compose ps -q wordpress)"
}

image_id() {
    docker image inspect --format '{{.Id}}' "wp-locked-stack:$1"
}

assert_deploy() {
    label=$1
    expected_code=$2
    tag=$3
    code=0
    output=$(sh bin/deploy.sh "wp-locked-stack:$tag" 2>&1) || code=$?
    if [ "$code" -ne "$expected_code" ]; then
        printf '%s\n' "$output" >&2
        fail "$label: expected exit $expected_code, got $code"
    fi
    pass "$label"
}

backup_count() {
    find "$backups" -name '*.sql' | wc -l | tr -d ' '
}

upgrade_lock_to() {
    printf "sed -i '/\"name\": \"wp-plugin\/woocommerce\"/{n;s/11.2.2/%s/}' %s" "$1" "$lock"
}
break_home="echo '<?php http_response_code(500); exit;' > $theme/index.php"

derive code-good "echo '/* restyle */' >> $theme/style.css"
derive code-bad "$break_home"
derive upgrade-good "$(upgrade_lock_to 11.2.3) && echo '// upgraded' >> $plugin"
derive upgrade-bad "$(upgrade_lock_to 11.2.4) && $break_home"

baseline=$(running_image)

assert_deploy 'deploy: code-only release succeeds' 0 code-good
[ "$(running_image)" = "$(image_id code-good)" ] || fail 'deploy: new image is not running'
[ "$(backup_count)" = 0 ] || fail 'deploy: code-only release should not take a backup'
pass 'deploy: code-only release runs the new image without a backup'

current=$(running_image)
assert_deploy 'deploy: broken code-only release exits as rolled back' 2 code-bad
[ "$(running_image)" = "$current" ] || fail 'deploy: rollback did not restore the previous image'
curl -fsS -o /dev/null "$url/" || fail 'deploy: site is down after rollback'
pass 'deploy: broken code-only release restores the previous image'

assert_deploy 'deploy: plugin upgrade succeeds' 0 upgrade-good
[ "$(backup_count)" = 1 ] || fail 'deploy: plugin upgrade should take one backup'
newest=$(find "$backups" -name '*.sql')
grep -q 'CREATE TABLE' "$newest" || fail 'deploy: backup has no tables'
tail -n 1 "$newest" | grep -q 'Dump completed' || fail 'deploy: backup is incomplete'
pass 'deploy: plugin upgrade takes a complete backup first'

sleep 1
assert_deploy 'deploy: broken plugin upgrade stops for a human decision' 3 upgrade-bad
[ "$(running_image)" = "$(image_id upgrade-bad)" ] || fail 'deploy: broken upgrade must not roll back on its own'
[ "$(backup_count)" = 2 ] || fail 'deploy: broken upgrade should have taken a backup'
pass 'deploy: broken plugin upgrade is left in place with a backup'

assert_deploy 'deploy: redeploying the original image recovers the site' 0 local
[ "$(running_image)" = "$baseline" ] || fail 'deploy: original image is not running'
curl -fsS -o /dev/null "$url/" || fail 'deploy: site is down after recovery'
pass 'deploy: site serves the original image again'
