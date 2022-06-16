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

echo "Installation Ibexa ($REPO - $VERSION) in the container"

# Copy nginx conf
echo "Getting the NGINX config"
wget https://github.com/ibexa/docker/archive/main.zip
unzip main.zip
cp -r docker-main/templates/nginx/ $PROJECTMAPPINGFOLDER/$PROVISIONINGFOLDERNAME/dev/nginx/include
rm -rf docker-main
rm main.zip

# Install ibexa/website-skeleton
echo "Install $REPO-skeleton"
$COMPOSER create-project --no-interaction $REPO-skeleton $PROJECTCMSROOT $VERSION
cd $PROJECTCMSROOT

# Add .env.local to set database configuration
echo "Add .env.local to set database configuration"
echo "DATABASE_URL=\${DATABASE_PLATFORM}://\${DATABASE_USER}:\${DATABASE_PASSWORD}@\${DATABASE_HOST}:\${DATABASE_PORT}/\${DATABASE_NAME}?serverVersion=\${DATABASE_VERSION}" > ".env.local"

# Test the package version
if [ "$REPO" != "ibexa/oss" ]; then
    echo "configure auth updates.ibexa.co"
    $COMPOSER config repositories.ibexa composer https://updates.ibexa.co
fi

MAJOR_VERSION=`echo $VERSION | cut -c 1-2`

# Do some cleaning
## Folder
rm -rf bin/.ci bin/.travis

echo "Installation Ibexa $REPO OK"

# Prefer install via composer alias (added in v2.2, required for eZ Commerce)
if $COMPOSER run-script -l | grep -q " $INIT_DATA "; then
   $COMPOSER run-script $INIT_DATA
else
    echo "php $CONSOLE_PATH ibexa:install "
    $PHP $CONSOLE_PATH ibexa:install $INIT_DATA
fi
echo "php $CONSOLE_PATH ibexa:graphql:generate-schema"
$PHP $CONSOLE_PATH ibexa:graphql:generate-schema
echo "composer run post-update-cmd"
$COMPOSER run-script post-install-cmd

echo "Database init OK"
