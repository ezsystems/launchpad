#!/usr/bin/env bash

cd $PROJECTMAPPINGFOLDER

PHP="php"
COMPOSER="$PHP -d memory_limit=-1 /usr/local/bin/composer"
VERSION=$1
PACKAGE=$2
PROVISIONING=$3
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

echo "Installation eZ Platform ($PACKAGE - $VERSION) in the container"

# Install ibexa/website-skeleton
echo "Install ibexa/website-skeleton"
$COMPOSER create-project --no-interaction ibexa/website-skeleton ezplatform $VERSION
cd ezplatform
# Copy nginx conf
echo "Copy nginx conf"
cp -r ../$PROVISIONING/dev/nginx/doc .

# Add .env.local to set database configuration
echo "Add .env.local to set database configuration"
echo "DATABASE_URL=\${DATABASE_PLATFORM}://\${DATABASE_USER}:\${DATABASE_PASSWORD}@\${DATABASE_HOST}:\${DATABASE_PORT}/\${DATABASE_NAME}?serverVersion=\${DATABASE_VERSION}" > ".env.local"

# Test the package version
if [ $PACKAGE != "ibexa/oss" ]; then
echo "configure auth updates.ibexa.co"
$COMPOSER config repositories.ibexa composer https://updates.ibexa.co
fi
# Install package ( oss or content or commerce or experience )
echo "Install package $PACKAGE"
$COMPOSER require $PACKAGE

# recipes:install ( content or commerce or experience )
echo "recipes:install $PACKAGE"
$COMPOSER recipes:install $PACKAGE --force

MAJOR_VERSION=`echo $VERSION | cut -c 1-2`

# Do some cleaning
## Folder
rm -rf bin/.ci bin/.travis

echo "Installation Ibexa $PACKAGE OK"

