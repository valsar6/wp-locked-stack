#!/bin/sh
set -eu
. "$(dirname "$0")/lib.sh"

plugin_file=wp-content/plugins/fixed-term-access/fixed-term-access.php

wait_for_wordpress

expect_success 'e2e: clean image matches its manifest' wp stack verify

if ! wp core is-installed >/dev/null 2>&1; then
    wp core install --url="$url" --title='Locked Stack' --admin_user=admin \
        --admin_password=local-only --admin_email=admin@example.test --skip-email >/dev/null
fi

activation=$(wp plugin activate woocommerce fixed-term-access 2>&1)
case "$activation" in
    *Warning*|*Error*)
        printf '%s\n' "$activation" >&2
        fail 'e2e: plugin activation reported problems'
        ;;
esac
pass 'e2e: plugins activate without warnings'
wp theme activate stack-theme >/dev/null

expect_success 'e2e: activating plugins does not create drift' wp stack verify

curl -fsS "$url/" | grep -q 'data-theme="stack-theme"' || fail 'e2e: home page did not render the theme'
pass 'e2e: home page renders the theme with the plugin active'

[ "$(wp eval 'echo DISALLOW_FILE_MODS ? "on" : "off";')" = on ] || fail 'e2e: DISALLOW_FILE_MODS is not enabled'
pass 'e2e: file modifications are disabled'

[ "$(wp eval 'echo wp_is_writable(wp_upload_dir()["basedir"]) ? "yes" : "no";')" = yes ] || fail 'e2e: uploads are not writable'
pass 'e2e: uploads are writable'

as_root sh -c "echo '// hand edit' >> /var/www/html/$plugin_file"
expect_failure 'e2e: edited plugin is detected' 'content-mismatch: plugin/fixed-term-access' wp stack verify
as_root sed -i '$d' "/var/www/html/$plugin_file"
expect_success 'e2e: restoring the file clears the drift' wp stack verify

as_root mkdir /var/www/html/wp-content/plugins/sideloaded
as_root sh -c "echo '<?php' > /var/www/html/wp-content/plugins/sideloaded/sideloaded.php"
expect_failure 'e2e: sideloaded plugin is detected' 'unexpected: plugin/sideloaded' wp stack verify
as_root rm -rf /var/www/html/wp-content/plugins/sideloaded

as_root mv /var/www/html/wp-content/themes/stack-theme /tmp/stack-theme
expect_failure 'e2e: removed theme is detected' 'missing: theme/stack-theme' wp stack verify
as_root mv /tmp/stack-theme /var/www/html/wp-content/themes/stack-theme

expect_success 'e2e: stack is clean after all drills' wp stack verify
