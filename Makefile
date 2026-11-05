COMPOSER_IMAGE := $(shell awk '/^FROM composer:/ {print $$2}' Dockerfile)
RUNTIME_IMAGE := $(shell awk '/^FROM wordpress:/ {print $$2}' Dockerfile)
TOOLS := docker run --rm -v "$(CURDIR)":/app -w /app \
	--user $(shell id -u):$(shell id -g) \
	-e HOME=/tmp -e COMPOSER_HOME=/tmp/composer -e COMPOSER_CACHE_DIR=/app/.cache/composer \
	-e GIT_CONFIG_COUNT=1 -e GIT_CONFIG_KEY_0=safe.directory -e GIT_CONFIG_VALUE_0=/app \
	--entrypoint "" $(COMPOSER_IMAGE)
RUNTIME := docker run --rm -v "$(CURDIR)":/app -w /app \
	--user $(shell id -u):$(shell id -g) -e HOME=/tmp \
	--entrypoint "" $(RUNTIME_IMAGE)

.PHONY: install lock analyse test release-checks drills static image up e2e down ci

install:
	$(TOOLS) composer install --no-interaction --no-scripts

lock:
	$(TOOLS) composer update --no-interaction --no-scripts

analyse:
	$(TOOLS) vendor/bin/phpstan analyse --no-progress --memory-limit=1G

test:
	$(RUNTIME) php vendor/bin/phpunit

release-checks:
	$(TOOLS) php tools/release-checks/bin/check.php

drills:
	$(TOOLS) sh tests/drills/contract.sh
	$(TOOLS) sh tests/drills/static-analysis.sh
	$(TOOLS) sh tests/drills/release-checks.sh

static: install analyse test release-checks drills

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
