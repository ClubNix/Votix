<h1 align="center">
  <a href="#user-content-------votix--"><img src="https://raw.githubusercontent.com/ClubNix/Votix/master/public/logovotix.jpg" alt="Logo Votix" width="150" height="150"></a>
  <br>
  Votix by Club*Nix
  <br>
</h1>

<p align="center">
  <a href="https://www.clubnix.fr/" alt="Club*Nix"><img src="https://img.shields.io/badge/A%20project%20-Club%2ANix-7ef80b.svg" /></a>
  <a href="https://travis-ci.org/ClubNix/Votix" alt="Build Status"><img src="https://travis-ci.org/ClubNix/Votix.svg?branch=master" /></a>
  <a href="https://github.com/ClubNix/Votix/blob/master/LICENCE" alt="MIT"><img src="https://img.shields.io/github/license/ClubNix/Votix.svg" /></a>
</p>

<p align="center">
  <a href="https://secure.php.net/manual/en/intro-whatis.php" alt="PHP 7.2"><img src="https://img.shields.io/badge/PHP-^7.2-787cb4.svg" /></a>
  <a href="https://symfony.com/what-is-symfony" alt="Symfony 4.1"><img src="https://img.shields.io/badge/Symfony-4.2-7aba20.svg" /></a>
</p>

<p align="center"><b>Votix is an advanced and secure online voting platform.</b></p>

## License

Votix is released under the MIT license.

## Requirements

 * PHP 7.2 or greater
 * PHP extensions curl, openssl, sqlite

## Quickstart

```bash
# ArchLinux
pacman -S composer php-sqlite php-intl
yay -S symfony-cli
# enable extension=pdo_sqlite iconv in /etc/php/php.ini

composer install

# Initialize local database
make reset

yarn install
```

php bin/console assets:install public --symlink

# Start application
symfony server:start --no-tls
