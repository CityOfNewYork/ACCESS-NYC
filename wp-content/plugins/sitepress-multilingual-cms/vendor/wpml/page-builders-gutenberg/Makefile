# Tutorial: http://www.cs.colby.edu/maxwell/courses/tutorials/maketutor/
# Docs: https://www.gnu.org/software/make/

# Help
.PHONY: help

help:
	$(info Run `make setup` to configure the Git Hooks and install the dependencies`)
	$(info Run `make install` to install the dependencies)
	$(info Run `make install-prod` to install the dependencies in production mode)
	$(info - Run `make composer-install` to only install Composer dependencies)
	$(info - Run `make composer-install-prod` to only install Composer dependencies in production mode)
	$(info - Run `make npm-install` to only install Node dependencies)
	$(info - Run `make npm-install-prod` to only install Node dependencies in production mode)
	$(info Run `make tests` to run all tests)
	$(info - Run `make jest` to run only Jest tests)
	$(info - Run `make phpunit` to run only PhpUnit tests)
	$(info Run `make dev` to bundle WebPack modules in development mode)
	$(info Run `make prod` to bundle WebPack modules in production mode)

# Setup
.PHONY: setup githooks

setup:: githooks
setup:: install

githooks:
ifdef CI
	$(info Skipping Git Hooks in CI)
else ifdef OS
	cp .githooks/* .git/hooks/
	$(info Looks like you are on Windows... files copied.)

else
	@find .git/hooks -type l -exec rm {} \;
	@find .githooks -type f -exec ln -sf ../../{} .git/hooks/ \;
	$(info Git Hooks installed)
endif

# Install
.PHONY: install

install: composer-install
install: npm-install

install-prod: composer-install-prod
install-prod: npm-install-prod

# Build
.PHONY: dev prod

dev prod: npm-install
	@npm run build:$@
	$(info WebPack modules bundled)

# Tests
.PHONY: tests

tests:: jest
tests:: phpunit

# Git Hooks
.PHONY: precommit

precommit:: validate-composer
precommit:: validate-npm
precommit:: dupes
precommit:: compatibility

# precommit
.PHONY: dupes compatibility validate-composer validate-npm

dupes: composer-install
	./.make/check-duplicates.sh

compatibility: composer-install
	./.make/check-compatibility.sh

validate-composer: composer-install
	./.make/check-composer.sh

validate-npm: npm-install
	./.make/check-npm.sh


# Dependency managers

## Composer
.PHONY: composer-install

composer.lock: composer-install
	@touch $@

vendor/autoload.php: composer-install
	@touch $@

composer-install:
	$(info Installing Composer dependencies)
	@composer install

composer-install-prod:
	$(info Installing Composer dependencies)
	@composer --no-dev install

## NPM
.PHONY: npm-install

package.json: npm-install
	@touch $@

package-lock.json: npm-install
	@touch $@

npm-install:
	$(info Installing Node dependencies)
	@npm install

npm-install-prod:
	$(info Installing Node dependencies)
	@npm --prod install

# Tests
.PHONY: jest phpunit

jest: npm-install
	$(info Running Jest)
	@npm run test

phpunit: composer-install
	$(info Running PhpUnit)
	@vendor/bin/phpunit --fail-on-warning
#	Use the following if the phpunit.xml is on a different location
#	@vendor/bin/phpunit --fail-on-warning --configuration tests/phpunit/phpunit.xml
