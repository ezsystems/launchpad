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

MAJOR_VERSION=`echo $VERSION | cut -c 1-2`
if [[ "$MAJOR_VERSION" == "v2" || "$MAJOR_VERSION" == "2." ]]; then
    echo "*********************************************************************"
    echo "The (eventual) previous errors are NORMAL, 2.x is in BETA remember ;)"
    echo "eZ Launchpad will take care of you, composer update is coming."
    echo "*********************************************************************"
    #@todo: remove that when beta is over
    rm -rf var/cache
    $COMPOSER update
    rm -rf var/cache
else
    # Version 1.x
    $COMPOSER require ezsystems/ezplatform-http-cache
    # Add to the kernel if not loaded (anticipation here)
    if grep -q "EzSystemsPlatformHttpCacheBundle" app/AppKernel.php
    then
        echo "EzSystemsPlatformHttpCacheBundle is already loaded."
    else
        sed -i '/FOSHttpCacheBundle/a new EzSystems\\PlatformHttpCacheBundle\\EzSystemsPlatformHttpCacheBundle(),' app/AppKernel.php
        echo "EzSystemsPlatformHttpCacheBundle added to the Kernel."
    fi
fi

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

