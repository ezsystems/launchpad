#!/usr/bin/env bash

# As we are doing build and up we might run here before the end of the entrypoint
while true ; do
    if [ -d /var/www/.composer ]; then
        if ls -la /var/www/.composer | grep -q "www-data"; then
            break
        fi
    fi
    echo -n "."
    sleep 2
done
echo ""
sleep 2

VERSION=$1

cd /var/www/html/project
echo "Installation Composer in the container"
curl -sS https://getcomposer.org/installer | php

echo "Installation eZ Platform in the container"
php -d memory_limit=-1 composer.phar create-project --no-dev --no-interaction ezsystems/ezplatform ezplatform $VERSION
cp composer.phar ezplatform
cd ezplatform


# Wait for the DB
while ! mysqladmin ping -h"$DATABASE_HOST" -u"$DATABASE_USER" -p"$DATABASE_PASSWORD" --silent; do
    echo "."
    sleep 1
done
echo ""

php app/console doctrine:database:create
php app/console ezplatform:install clean

echo "Installation OK"

