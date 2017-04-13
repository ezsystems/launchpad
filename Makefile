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

.PHONY: list
list:
	@echo ""
	@echo "eZ Launchpad available targets:"
	@echo ""
	@echo "  $(YELLOW)phar$(RESTORE)         > build the box locally (bypass the PROD)"
	@echo ""
	@echo "  $(YELLOW)codeclean$(RESTORE)    > run the codechecker"
	@echo "  $(YELLOW)tests$(RESTORE)        > run the tests"
	@echo "  $(YELLOW)behat(RESTORE)         > run the Behat tests only"
	@echo "  $(YELLOW)unit(RESTORE)          > run the Unit tests only"
	@echo "  $(YELLOW)coverage$(RESTORE)     > generate the code coverage"
	@echo ""
	@echo "  $(YELLOW)docs(RESTORE)          > generate stuff for the documenation"
	@echo ""
	@echo "  $(YELLOW)install$(RESTORE)      > install vendors"
	@echo "  $(YELLOW)clean$(RESTORE)        > removes the vendors, and caches"


.PHONY: codeclean
codeclean:
	bash $(SCRIPS_DIR)/codechecker.bash

.PHONY: tests
tests:
	bash $(SCRIPS_DIR)/runtests.bash

.PHONY: behat
behat:
	bash $(SCRIPS_DIR)/runtests.bash behat

.PHONY: unit
unit:
	bash $(SCRIPS_DIR)/runtests.bash unit

.PHONY: docs
docs:
	bash $(SCRIPS_DIR)/pumltoimages.bash

.PHONY: install
install:
	curl -s http://getcomposer.org/installer | $(PHP_BIN)
	$(PHP_BIN) $(COMPOSER_BIN) install

.PHONY: phar
phar:
	bash $(SCRIPS_DIR)/buildbox.bash

.PHONY: coverage
coverage:
	rm -rf tests/coverage
	$(DOCKER_BIN) run -t --rm -w /app -v $(CURRENT_DIR):/app phpunit/phpunit:5.7.12 -c tests/ --coverage-html /app/tests/coverage

.PHONY: clean
clean:
	rm -rf tests/coverage
	rm -f .php_cs.cache
	rm -f .ezlaunchpad.yml
	rm -rf vendor
	rm -rf ezplatform
	rm -rf provisioning2ouf
	rm -f ezinstall.bash


