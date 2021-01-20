test:
	php "./vendor/codeception/codeception/codecept" run
test_coverage:
	XDEBUG_MODE=coverage php "./vendor/codeception/codeception/codecept" run --coverage --coverage-xml --coverage-html
reset:
	php bin/console doctrine:database:drop --force
	php bin/console doctrine:database:create
	php bin/console doctrine:schema:update --force
	php bin/console doctrine:fixtures:load --no-interaction
install_dev:
	composer install
	yarn install
	yarn build
	make reset
install_prod:
	composer install --no-dev
	yarn install
	yarn build
	php bin/console doctrine:database:create
	php bin/console doctrine:schema:update --force
docker:
	docker build docker -t votix
inside:
	docker-compose exec votix bash
inside_root:
	docker-compose exec -u root votix bash