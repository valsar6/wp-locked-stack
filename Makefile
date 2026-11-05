COMPOSER_IMAGE := composer:2.9@sha256:b09bccd91a78fe8a9ab4b33d707b862e8fe54fec17782e32683ad2a69c46867d
TOOLS := docker run --rm -v "$(CURDIR)":/app -w /app \
	--user $(shell id -u):$(shell id -g) \
	-e HOME=/tmp -e COMPOSER_HOME=/tmp/composer -e COMPOSER_CACHE_DIR=/app/.cache/composer \
	--entrypoint "" $(COMPOSER_IMAGE)
RUNTIME_IMAGE := wordpress:7.1.4-php8.4-apache@sha256:418c95d1d7648ce672415f714b88df2cb1d358552e261771508f5d4ced912c01
RUNTIME := docker run --rm -v "$(CURDIR)":/app -w /app \
	--user $(shell id -u):$(shell id -g) -e HOME=/tmp \
	--entrypoint "" $(RUNTIME_IMAGE)

.PHONY: install lock analyse test drills static image up e2e down ci

install:
	$(TOOLS) composer install --no-interaction --no-scripts

lock:
	$(TOOLS) composer update --no-interaction --no-scripts

analyse:
	$(TOOLS) vendor/bin/phpstan analyse --no-progress --memory-limit=1G

test:
	$(RUNTIME) php vendor/bin/phpunit

drills:
	$(TOOLS) sh tests/drills/contract.sh
	$(TOOLS) sh tests/drills/static-analysis.sh

static: install analyse test drills

image:
	docker compose build wordpress

up:
	docker compose up -d --wait --renew-anon-volumes --force-recreate

e2e:
	sh tests/e2e/run.sh
	sh tests/e2e/stale-volume.sh

down:
	docker compose down --volumes

ci: static image up e2e down
