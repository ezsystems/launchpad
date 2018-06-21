#!/usr/bin/env bash

cd $PROJECTMAPPINGFOLDER

PHP="php"
COMPOSER="$PHP -d memory_limit=-1 /usr/local/bin/composer"
REPO=$1
VERSION=$2
INIT_DATA=$3

DATABASE_PREFIXES=${DATABASE_PREFIXES:-DATABASE}
for prefix in $DATABASE_PREFIXES
do
    DATABASE_NAME_VAR=${prefix}_NAME
    DATABASE_HOST_VAR=${prefix}_HOST
    DATABASE_USER_VAR=${prefix}_USER
    DATABASE_PASSWORD_VAR=${prefix}_PASSWORD

    # Wait for the DB
    while ! mysqladmin ping -h"${!DATABASE_HOST_VAR}" -u"${!DATABASE_USER_VAR}" -p"${!DATABASE_PASSWORD_VAR}" --silent; do
        echo -n "."
        sleep 1
    done
    echo ""

    mysql -h"${!DATABASE_HOST_VAR}" -u"${!DATABASE_USER_VAR}" -p"${!DATABASE_PASSWORD_VAR}" -e "CREATE DATABASE ${!DATABASE_NAME_VAR}"
done

echo "Installation eZ Platform ($REPO:$VERSION:$INIT_DATA) in the container"

# Install
$COMPOSER create-project --no-interaction $REPO ezplatform $VERSION
cd ezplatform

MAJOR_VERSION=`echo $VERSION | cut -c 1-2`

# Do some cleaning
## Files
rm .env .platform.app.yaml Dockerfile .travis.yml
## Folder
rm -rf .platform bin/.ci bin/.travis

CONSOLE="bin/console"
if [ -f app/console ]; then
    CONSOLE="app/console"
fi

$PHP $CONSOLE ezplatform:install $INIT_DATA

echo "Installation OK"

