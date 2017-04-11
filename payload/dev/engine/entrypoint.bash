#!/usr/bin/env bash

# Create .composer in advance and set the permissions
mkdir /var/www/.composer && chown www-data:www-data /var/www/.composer

# give the good permissions to www-data in the container
if [ -d /var/www/html/project/ezplatform/app/cache ]; then
    chown -R www-data:www-data /var/www/html/project/ezplatform/app/cache
    chown -R www-data:www-data /var/www/html/project/ezplatform/app/logs
    chown -R www-data:www-data /var/www/html/project/ezplatform/web
fi

exec "$@"
