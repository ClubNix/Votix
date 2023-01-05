FROM php:8.0-apache-buster

ARG APCU_VERSION=5.1.19

RUN set -eux; \
    buildDeps=" \
        libicu-dev \
        zlib1g-dev \
        libzip-dev \
        libonig-dev \
    "; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        $buildDeps \
        ca-certificates \
        git \
        openssh-client \
        libzip4 \
        unzip \
        gnupg \
    ; \
    \
    docker-php-ext-install opcache mbstring intl zip; \
    \
    pecl install apcu-$APCU_VERSION; \
    pecl clear-cache; \
    docker-php-ext-enable apcu; \
    \
# install xdebug for debugging and coverage but we don't want it enabled by default
    pecl install xdebug; \
    \
    curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | apt-key add -; \
    echo "deb https://dl.yarnpkg.com/debian/ stable main" | tee /etc/apt/sources.list.d/yarn.list; \
    apt-get update; \
    apt-get install -y yarn nodejs; \
    \
    apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false -o APT::AutoRemove::SuggestsImportant=false $buildDeps; \
    apt-get clean; \
    rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*; \
    \
    mkdir -p /srv/public; \
    chown -R www-data:www-data /srv; \
    a2enmod rewrite; \
    \
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer; \
    \
# http://symfony.com/doc/current/performance.html
    { \
        echo "short_open_tag=off"; \
        echo "date.timezone=UTC"; \
        echo "opcache.memory_consumption=256"; \
        echo "opcache.max_accelerated_files=20000"; \
        echo "realpath_cache_size=4096K"; \
        echo "realpath_cache_ttl=600"; \
    } | tee /usr/local/etc/php/conf.d/symfony.ini; \
    { \
        echo "<VirtualHost *:8000>"; \
        echo "    DocumentRoot /srv/public"; \
        echo "    DirectoryIndex index.php"; \
        echo "    <Directory /srv/public>"; \
        echo "        AllowOverride All"; \
        echo "        Require all granted"; \
        echo "    </Directory>"; \
        echo "    ErrorLog /proc/self/fd/1"; \
        echo "    CustomLog /proc/self/fd/2 combined"; \
        echo "</VirtualHost>"; \
    } | tee /etc/apache2/sites-available/000-default.conf; \
    echo "Listen 8000" > /etc/apache2/ports.conf; \
    mkdir -p /mnt/cache_yarn /mnt/cache_composer; \
    echo "disable-self-update-check true" > /.yarnrc

EXPOSE 8000

WORKDIR /srv
