# === eZ Launchpad Helper ===

# Styles
YELLOW=$(shell echo "\033[00;33m")
RED=$(shell echo "\033[00;31m")
RESTORE=$(shell echo "\033[0m")

# Variables
PHP_BIN := php
COMPOSER_BIN := composer.phar
DOCKER_BIN := docker
SRCS := src
CURRENT_DIR := $(shell pwd)
SCRIPS_DIR := $(CURRENT_DIR)/scripts

.DEFAULT_GOAL := list

.PHONY: list
list:
	@echo "******************************"
	@echo "${YELLOW}eZ Launchpad available targets${RESTORE}:"
	@grep -E '^[a-zA-Z-]+:.*?## .*$$' Makefile | sort | awk 'BEGIN {FS = ":.*?## "}; {printf " ${YELLOW}%-15s${RESTORE} > %s\n", $$1, $$2}'
	@echo "${RED}==============================${RESTORE}"

.PHONY: install
install: ## Install the vendor
	@composer install

.PHONY: codeclean
codeclean: ## Run the codechecker
	bash $(SCRIPS_DIR)/codechecker.bash

.PHONY: tests
tests: ## Run the tests
	bash $(SCRIPS_DIR)/runtests.bash

.PHONY: behat
behat: ## Run the Behat tests only
	bash $(SCRIPS_DIR)/runtests.bash behat

.PHONY: unit
unit: ## Run the Unit tests only
	bash $(SCRIPS_DIR)/runtests.bash unit

.PHONY: convertpuml
convertpuml: ## Convert PUML diagram in images
	bash $(SCRIPS_DIR)/pumltoimages.bash

.PHONY: docs
docs: ## Generate the documentation
	$(PHP_BIN) bin/gendocs

.PHONY: phar
phar: ## Build the box locally (bypass the PROD)
	bash $(SCRIPS_DIR)/buildbox.bash

.PHONY: coverage
coverage: ## Generate the code coverage
	rm -rf tests/coverage
	$(DOCKER_BIN) run -t --rm -w /app -v $(CURRENT_DIR):/app phpunit/phpunit:5.7.12 -c tests/ --coverage-html /app/tests/coverage

.PHONY: clean
clean: ## Removes the vendors, and caches
	rm -rf tests/coverage
	rm -f .php_cs.cache
	rm -f .ezlaunchpad.yml
	rm -rf vendor
	rm -rf ezplatform
	rm -rf provisioning
	rm -rf provisioning2ouf
	rm -f ezinstall.bash


