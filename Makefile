test:
	php "./vendor/codeception/codeception/codecept" run
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
docker:
	docker build docker -t votix