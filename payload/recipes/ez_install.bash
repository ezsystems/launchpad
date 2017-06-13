#!/usr/bin/env bash

cd $PROJECTMAPPINGFOLDER

PHP="php"
COMPOSER="$PHP -d memory_limit=-1 composer.phar"
REPO=$1
VERSION=$2
INIT_DATA=$3

echo "Installation eZ Platform ($REPO:$VERSION:$INIT_DATA) in the container"

# Install
$COMPOSER create-project --no-interaction $REPO ezplatform $VERSION
cp composer.phar ezplatform
cd ezplatform

# Install Tiny help for Platformsh (will be included soon in ezplatform)
$COMPOSER require platformsh/config-reader --no-interaction

# Do some cleaning
## Files
rm .env .platform.app.yaml Dockerfile .travis.yml
## Folder
rm -rf .platform bin/.ci bin/.travis

CONSOLE="bin/console"
if [ -f app/console ]; then
    CONSOLE="app/console"
fi

# Wait for the DB
while ! mysqladmin ping -h"$DATABASE_HOST" -u"$DATABASE_USER" -p"$DATABASE_PASSWORD" --silent; do
    echo -n "."
    sleep 1
done
echo ""

$PHP $CONSOLE doctrine:database:create
$PHP $CONSOLE ezplatform:install $INIT_DATA

echo "Installation OK"

