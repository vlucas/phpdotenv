install:
	@docker run -it -w /data -v ${PWD}:/data:delegated -v ~/.composer:/root/.composer:delegated --entrypoint composer --rm registry.gitlab.com/grahamcampbell/php:8.2-base update
	@docker run -it -w /data -v ${PWD}:/data:delegated -v ~/.composer:/root/.composer:delegated --entrypoint composer --rm registry.gitlab.com/grahamcampbell/php:8.2-base bin all update

phpunit:
	@docker run -it -w /data -v ${PWD}:/data:delegated --entrypoint vendor/bin/phpunit --rm registry.gitlab.com/grahamcampbell/php:8.2-cli

phpstan-analyze-src:
	@docker run -it -w /data -v ${PWD}:/data:delegated --entrypoint vendor/bin/phpstan --rm registry.gitlab.com/grahamcampbell/php:8.2-cli analyze src -c phpstan.src.neon.dist

phpstan-analyze-tests:
	@docker run -it -w /data -v ${PWD}:/data:delegated --entrypoint vendor/bin/phpstan --rm registry.gitlab.com/grahamcampbell/php:8.2-cli analyze tests -c phpstan.tests.neon.dist

psalm-analyze:
	@docker run -it -w /data -v ${PWD}:/data:delegated --entrypoint vendor/bin/psalm.phar --rm registry.gitlab.com/grahamcampbell/php:8.2-cli

psalm-baseline:
	@docker run -it -w /data -v ${PWD}:/data:delegated --entrypoint vendor/bin/psalm.phar --rm registry.gitlab.com/grahamcampbell/php:7.4-cli --set-baseline=psalm-baseline.xml

psalm-show-info:
	@docker run -it -w /data -v ${PWD}:/data:delegated --entrypoint vendor/bin/psalm.phar --rm registry.gitlab.com/grahamcampbell/php:8.2-cli --show-info=true

test: phpunit phpstan-analyze-src phpstan-analyze-tests psalm-analyze

clean:
	@rm -rf .phpunit.result.cache composer.lock vendor vendor-bin/*/composer.lock vendor-bin/*/vendor
