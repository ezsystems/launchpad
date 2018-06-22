#!/usr/bin/env bash

cd $PROJECTMAPPINGFOLDER

PHP="php"
COMPOSER="$PHP -d memory_limit=-1 /usr/local/bin/composer"
REPO=$1
VERSION=$2

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

if [ ! -d ezplatform ]; then
    echo "Not managed yet."
    exit
fi

CONSOLE="bin/console"
if [ -f ezplatform/app/console ]; then
    CONSOLE="app/console"
fi

# Install
cd ezplatform

$COMPOSER install --no-interaction

echo "Installation OK"

