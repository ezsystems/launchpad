#!/usr/bin/env bash

cd $PROJECTMAPPINGFOLDER


mkdir -p data

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
    
    MYSQLDUMP="mysqldump -h${!DATABASE_HOST_VAR} -u${!DATABASE_USER_VAR} -p${!DATABASE_PASSWORD_VAR}"

    echo "Dumping ${!DATABASE_NAME_VAR} database."
    $MYSQLDUMP ${!DATABASE_NAME_VAR} > $DUMP_DIR/$DB_FILE_NAME.sql
    gzip -f $DUMP_DIR/$DB_FILE_NAME.sql
    echo "${!DATABASE_NAME_VAR} database dumped."
done


STORAGE_FILE_NAME="storage"

if [ "$2" != "" ]; then
    STORAGE_FILE_NAME="$2_storage"
fi

if [ -d $PROJECTMAPPINGFOLDER/ezplatform/web/var ]; then
    cd $PROJECTMAPPINGFOLDER/ezplatform/web
    tar czvf $DUMP_DIR/$STORAGE_FILE_NAME.tar.gz var/
    cd -
    echo "Storage dumped."
fi
