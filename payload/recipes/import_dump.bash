#!/usr/bin/env bash

cd $PROJECTMAPPINGFOLDER

DUMP_DIR="$(pwd)/data"
if [ "$1" != "" ] && [ -d "$1" ]; then
    if [[ "$1" =~ ^/ ]]; then
        DUMP_DIR="$1"
    fi
fi

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

    DB_FILE_NAME="${!DATABASE_NAME_VAR}"
    DB_FILE_PATH="$DUMP_DIR/$DB_FILE_NAME.sql"

    MYSQL="mysql -h${!DATABASE_HOST_VAR} -u${!DATABASE_USER_VAR} -p${!DATABASE_PASSWORD_VAR}"

    echo "Importing ${!DATABASE_NAME_VAR} database."
    zcat $DB_FILE_PATH | $MYSQL ${!DATABASE_NAME_VAR}
    echo "${!DATABASE_NAME_VAR} database imported."
done


STORAGE_FILE_NAME="storage"
if [ "$2" != "" ]; then
    STORAGE_FILE_NAME="$2_storage"
fi

STORAGE_FILE_PATH="$DUMP_DIR/$STORAGE_FILE_NAME.tar.gz"

if [ ! -d $PROJECTCMSROOT ]; then
    echo "Not managed yet."
    exit
fi

if [ -f $STORAGE_FILE_PATH ]; then
    if [ -d $PROJECTCMSROOT/web ]; then
        if [ -d "$PROJECTCMSROOT/web/var" ]; then
            rm -rf $PROJECTCMSROOT/web/var
        fi
        tar xvzf $STORAGE_FILE_PATH -C $PROJECTCMSROOT/web/
        echo "Storage imported to web/."
    fi
    if [ -d $PROJECTCMSROOT/public ]; then
        if [ -d "${PROJECTCMSROOT}/public/var" ]; then
            rm -rf $PROJECTCMSROOT/public/var
        fi
        tar xvzf $STORAGE_FILE_PATH -C $PROJECTCMSROOT/public/
        echo "Storage imported to public/."
    fi
fi
