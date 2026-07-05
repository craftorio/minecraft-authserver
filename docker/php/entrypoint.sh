#!/bin/bash
set -e
set -x

mkdir -p /tmp/.composer
chmod 0755 /tmp/.composer || echo "Cant change permissions on /tmp/.composer"

cd /var/www/authserver
composer install -o

exec php-fpm --nodaemonize
