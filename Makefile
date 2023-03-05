.PHONY: cs@lint
cs@lint:
	PHP_CS_FIXER_IGNORE_ENV=true tools/php-cs-fixer fix --diff --dry-run

.PHONY: cs@fix
cs@fix:
	PHP_CS_FIXER_IGNORE_ENV=true tools/php-cs-fixer fix
