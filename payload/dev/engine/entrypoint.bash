#!/usr/bin/env bash

ORIGPASSWD=$(cat /etc/passwd | grep www-data)
ORIG_UID=$(echo "$ORIGPASSWD" | cut -f3 -d:)
ORIG_GID=$(echo "$ORIGPASSWD" | cut -f4 -d:)
ORIG_HOME=$(echo "$ORIGPASSWD" | cut -f6 -d:)
DEV_UID=${DEV_UID:=$ORIG_UID}
DEV_GID=${DEV_GID:=$ORIG_GID}

if [ "$DEV_UID" -ne "$ORIG_UID" ] || [ "$DEV_GID" -ne "$ORIG_GID" ]; then
    groupmod -g "$DEV_GID" www-data
    usermod -u "$DEV_UID" -g "$DEV_GID" www-data
fi

# Create .composer in advance and set the permissions
mkdir -p /var/www/.composer && chown www-data:www-data /var/www/.composer
chown www-data:www-data $PROJECTMAPPINGFOLDER

# give the good permissions to www-data in the container and remove the cache on start
# 2.x
if [ -d $PROJECTCMSROOT/var/cache ]; then
    rm -rf $PROJECTCMSROOT/var/cache
    rm -rf $SYMFONY_TMP_DIR/var/cache
    chown -R www-data:www-data $PROJECTCMSROOT/var/logs
    chown -R www-data:www-data $PROJECTCMSROOT/web
fi

if [ ! -f /usr/local/bin/composer ];
then
    echo "WARNING: you don't have the last image of the PHP ENGINE"
    echo "TO FIX RUN: ~/ez docker:update"
fi
/usr/local/bin/composer self-update --$COMPOSER_VERSION

if [ "1" = "${XDEBUG_ENABLED}" ]; then
    export PHP_INI_SCAN_DIR=:/usr/local/etc/php/enable-xdebug
fi

exec "$@"
