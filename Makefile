test:
	php vendor/bin/codecept run
reset:
	php bin/console doctrine:database:drop --force
	php bin/console doctrine:database:create
	php bin/console doctrine:schema:update --force
	php bin/console doctrine:fixtures:load --no-interaction
