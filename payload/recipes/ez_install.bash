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
$COMPOSER create-project --no-interaction $REPO $PROJECTCMSROOT $VERSION
cd $PROJECTCMSROOT

cp -r doc/nginx/ $PROJECTMAPPINGFOLDER/$PROVISIONINGFOLDERNAME/dev/nginx/include

MAJOR_VERSION=`echo $VERSION | cut -c 1-2`

# Do some cleaning
## Folder
rm -rf bin/.ci bin/.travis

# Prefer install via composer alias (added in v2.2, required for eZ Commerce)
if $COMPOSER run-script -l | grep -q " $INIT_DATA "; then
   $COMPOSER run-script $INIT_DATA
else
    $PHP $CONSOLE_PATH ezplatform:install $INIT_DATA
fi



echo "Installation OK"

