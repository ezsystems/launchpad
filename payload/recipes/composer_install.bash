#!/usr/bin/env bash

cd $PROJECTMAPPINGFOLDER

# As we are doing build and up we might run here before the end of the entrypoint
while true ; do
    if [ -d /var/www/.composer ]; then
        if ls -la /var/www/.composer | grep -q "www-data"; then
            break
        fi
    fi
    echo -n "."
    sleep 2
done
echo ""

if [ ! -f /usr/local/bin/composer ];
then
    echo "WARNING: you don't have the last image of the PHP ENGINE"
    echo "TO FIX RUN: ~/ez docker:update"
fi

/usr/local/bin/composer self-update

sleep 2
