#!/usr/bin/env bash

cd $PROJECTMAPPINGFOLDER/ezplatform

PHP="php"
COMPOSER="$PHP -d memory_limit=-1 /usr/local/bin/composer"
INIT_DATA=$1
CONSOLE="bin/console"

# Prefer install via composer alias (added in v2.2, required for eZ Commerce)
if $COMPOSER run-script -l | grep -q " $INIT_DATA "; then
   $COMPOSER run-script $INIT_DATA
else
    echo "php bin/console ibexa:install"
    $PHP $CONSOLE $INIT_DATA
fi
echo "php bin/console ibexa:graphql:generate-schema"
$PHP $CONSOLE ibexa:graphql:generate-schema
echo "composer run post-update-cmd"
$COMPOSER run-script post-install-cmd

echo "Database init OK"

