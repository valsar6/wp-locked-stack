#!/bin/sh
set -eu

install -d -o www-data -g www-data /var/www/html/wp-content/uploads

exec docker-entrypoint.sh "$@"
