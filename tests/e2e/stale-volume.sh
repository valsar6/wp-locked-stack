#!/bin/sh
set -eu
. "$(dirname "$0")/lib.sh"

next_image=wp-locked-stack:next
plugin=/usr/src/wordpress/wp-content/plugins/fixed-term-access/fixed-term-access.php

docker build -q -t "$next_image" - >/dev/null <<EOF
FROM wp-locked-stack:local
RUN echo '// next release' >> $plugin \
    && php /opt/stack/verify/bin/build-manifest.php /opt/stack/composer.lock /usr/src/wordpress /opt/stack/manifest.json
EOF

export STACK_IMAGE="$next_image"

docker compose up -d --no-build --force-recreate wordpress >/dev/null 2>&1
wait_for_wordpress
expect_failure 'stale volume: a new image over a reused volume is detected' \
    'content-mismatch: plugin/fixed-term-access' wp stack verify

docker compose up -d --no-build --force-recreate --renew-anon-volumes wordpress >/dev/null 2>&1
wait_for_wordpress
expect_success 'stale volume: renewing anonymous volumes serves the new image' wp stack verify

unset STACK_IMAGE
docker compose up -d --no-build --force-recreate --renew-anon-volumes wordpress >/dev/null 2>&1
wait_for_wordpress
docker image rm "$next_image" >/dev/null
