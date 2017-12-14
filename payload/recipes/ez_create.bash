#!/usr/bin/env bash

cd $PROJECTMAPPINGFOLDER

PHP="php"
COMPOSER="$PHP -d memory_limit=-1 composer.phar"
REPO=$1
VERSION=$2

if [ ! -d ezplatform ]; then
    echo "Not managed yet."
    exit
fi

CONSOLE="bin/console"
if [ -f ezplatform/app/console ]; then
    CONSOLE="app/console"
fi

# Install
cp composer.phar ezplatform
cd ezplatform
$COMPOSER install --no-interaction

# Wait for the DB
while ! mysqladmin ping -h"$DATABASE_HOST" -u"$DATABASE_USER" -p"$DATABASE_PASSWORD" --silent; do
    echo -n "."
    sleep 1
done
echo ""

$PHP $CONSOLE doctrine:database:create

echo "Installation OK"

