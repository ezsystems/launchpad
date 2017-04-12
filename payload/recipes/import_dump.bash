#!/usr/bin/env bash

cd /var/www/html/project

# Wait for the DB
while ! mysqladmin ping -h"$DATABASE_HOST" -u"$DATABASE_USER" -p"$DATABASE_PASSWORD" --silent; do
    echo "."
    sleep 1
done
echo ""

DUMP_DIR="$(pwd)/data"
if [ "$1" != "" ] && [ -d "$1" ]; then
    if [[ "$1" =~ ^/ ]]; then
        DUMP_DIR="$1"
    fi
fi

DB_FILE_NAME="$DATABASE_NAME"
STORAGE_FILE_NAME="storage"

if [ "$2" != "" ]; then
    DB_FILE_NAME="$2"
    STORAGE_FILE_NAME="$2_storage"
fi

DB_FILE_PATH="$DUMP_DIR/$DB_FILE_NAME.sql"
STORAGE_FILE_PATH="$DUMP_DIR/$STORAGE_FILE_NAME.tar.gz"

MYSQL="mysql -h$DATABASE_HOST -u$DATABASE_USER -p$DATABASE_PASSWORD"

zcat $DB_FILE_PATH | $MYSQL $DATABASE_NAME

echo "Database imported."

if [ -d "ezplatform/web/var" ]; then
    rm -rf ezplatform/web/var
fi

tar xvzf $STORAGE_FILE_PATH -C ezplatform/web/
echo "Storage imported."
