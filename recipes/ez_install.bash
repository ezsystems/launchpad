#!/usr/bin/env bash

cd /var/www/html/project

PHP="php"
COMPOSER="$PHP -d memory_limit=-1 composer.phar"
REPO=$1
VERSION=$2

echo "Installation eZ Platform ($REPO:$VERSION) in the container"
# Install
$COMPOSER create-project --no-dev --no-interaction $REPO ezplatform $VERSION
cp composer.phar ezplatform
cd ezplatform


# Wait for the DB
while ! mysqladmin ping -h"$DATABASE_HOST" -u"$DATABASE_USER" -p"$DATABASE_PASSWORD" --silent; do
    echo "."
    sleep 1
done
echo ""

$PHP app/console doctrine:database:create
$PHP app/console ezplatform:install clean

echo "Installation OK"

