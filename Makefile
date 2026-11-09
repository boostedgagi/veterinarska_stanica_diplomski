# Developer shortcuts. PHP runs on the host (there is no PHP container);
# Docker Compose only provides MySQL, phpMyAdmin and the mail catcher.

PHP      ?= php
CONSOLE  := $(PHP) bin/console
COMPOSER ?= composer

# docker-compose*.yml are local (git-ignored). If the local port override used
# during the Symfony upgrade exists, include it (see UPGRADE_PLAN.md, D3).
COMPOSE_FILES := -f docker-compose.yml -f docker-compose.override.yml \
                 $(if $(wildcard var/upgrade/compose.ports.yml),-f var/upgrade/compose.ports.yml)
COMPOSE  := docker compose $(COMPOSE_FILES)

.DEFAULT_GOAL := help
.PHONY: help install db-up db-down cache warmup lint test phpstan phpstan-baseline rector rector-check check

help: ## List targets
	@grep -E '^[a-zA-Z_-]+:.*?## ' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-14s\033[0m %s\n", $$1, $$2}'

install: ## composer install
	$(COMPOSER) install

db-up: ## Start the local database containers
	$(COMPOSE) up -d

db-down: ## Stop the local database containers
	$(COMPOSE) down

cache: ## Clear the dev cache
	$(CONSOLE) cache:clear

warmup: ## Warm the dev cache (refreshes the container XML Rector reads)
	$(CONSOLE) cache:warmup

lint: ## Lint container, YAML config and Twig templates
	$(CONSOLE) lint:container
	$(CONSOLE) lint:yaml config --parse-tags
	$(CONSOLE) lint:twig templates

test: ## Run the PHPUnit suite
	$(PHP) bin/phpunit

phpstan: ## Static analysis
	$(COMPOSER) phpstan

phpstan-baseline: ## Regenerate phpstan-baseline.neon (line-ending independent)
	vendor/bin/phpstan analyse --memory-limit=1G --generate-baseline phpstan-baseline.neon
	@# PHPDoc parse-error messages contain a column offset that differs between CRLF and
	@# LF checkouts (core.autocrlf), so match any offset.
	sed -i -E 's/at offset [0-9]+ on line/at offset \\d+ on line/' phpstan-baseline.neon

rector: warmup ## Apply Rector
	$(COMPOSER) rector

rector-check: warmup ## Rector dry run (fails if anything would change)
	$(COMPOSER) rector:check

check: lint phpstan rector-check test ## Everything CI runs
