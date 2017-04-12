#!/usr/bin/env bash
BASEDIR=$(dirname $0)
PHP="env php "
source ${BASEDIR}/functions
PROJECTDIR="${BASEDIR}/../"

cd ${PROJECTDIR}

echoTitle "******** Build it! ********"

if [ ! -f box.phar ]; then
    echoInfo "Install box.phar before..."
    curl -LSs https://box-project.github.io/box2/installer.php | php
    echoAction "Building now..."
fi

$PHP composer.phar install --no-dev > /dev/null 2>&1
$PHP -d "phar.readonly=false" box.phar build -vv
$PHP composer.phar install > /dev/null 2>&1
shasum ez.phar > ez.phar.version

mv ez.phar ez.phar.version ez.phar.pubkey ~/.ezlaunchpad/

echoSuccess "Done."
exit 0;
