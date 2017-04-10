#!/usr/bin/env bash

# As we are doing build and up we might run here before the end of the entrypoint
while true ; do
    if ls -la /var/www | grep -q "www-data"; then
        break
    fi
    echo -n "."
    sleep 2
done
echo ""
sleep 2
cd /var/www/html/project
echo "Installation Composer in the container"
curl -sS https://getcomposer.org/installer | php

echo "Installation eZ Platform in the container"
php -d memory_limit=-1 composer.phar create-project --no-dev --no-interaction ezsystems/ezplatform
cp composer.phar ezplatform
cd ezplatform
php app/console doctrine:database:create
php app/console ezplatform:install clean

echo "Installation OK"
