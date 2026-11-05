FROM composer:2.9@sha256:b09bccd91a78fe8a9ab4b33d707b862e8fe54fec17782e32683ad2a69c46867d AS deps
WORKDIR /app
COPY composer.json composer.lock ./
COPY packages ./packages
RUN composer install --no-dev --no-scripts --no-interaction --no-autoloader --prefer-dist

FROM wordpress:7.1.4-php8.4-apache@sha256:418c95d1d7648ce672415f714b88df2cb1d358552e261771508f5d4ced912c01
ADD --checksum=sha256:ce34ddd838f7351d6759068d09793f26755463b4a4610a5a5c0a97b68220d85c \
    https://github.com/wp-cli/wp-cli/releases/download/v2.12.0/wp-cli-2.12.0.phar /usr/local/bin/wp
RUN chmod 0755 /usr/local/bin/wp \
    && rm -rf /usr/src/wordpress/wp-content/plugins/* /usr/src/wordpress/wp-content/themes/*

COPY --from=deps /app/build/wp-content/plugins/ /usr/src/wordpress/wp-content/plugins/
COPY --from=deps /app/build/wp-content/themes/ /usr/src/wordpress/wp-content/themes/

COPY tools/stack-verify /opt/stack/verify
COPY docker/wp-cli.yml /opt/stack/wp-cli.yml
COPY composer.lock /opt/stack/composer.lock
RUN php /opt/stack/verify/bin/build-manifest.php /opt/stack/composer.lock /usr/src/wordpress /opt/stack/manifest.json

ENV WP_CLI_CONFIG_PATH=/opt/stack/wp-cli.yml
ENV WORDPRESS_CONFIG_EXTRA="define('DISALLOW_FILE_MODS', true);"

COPY --chmod=0755 docker/entrypoint.sh /usr/local/bin/stack-entrypoint.sh
ENTRYPOINT ["stack-entrypoint.sh"]
CMD ["apache2-foreground"]
