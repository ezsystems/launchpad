#!/usr/bin/env bash
BASEDIR=$(dirname $0)
PHP="env php "
source ${BASEDIR}/functions
PROJECTDIR="${BASEDIR}/../"

cd ${PROJECTDIR}

echoTitle "******** Build it! ********"
ulimit -Sn 4096

if [ ! -f composer.phar ]; then
    echoInfo "Install composer.phar before..."
    curl -s http://getcomposer.org/installer | $PHP
    echoAction "Building now..."
fi
if [ ! -f box.phar ]; then
    echoInfo "Install box.phar before..."
    curl -LSs https://box-project.github.io/box2/installer.php | $PHP
    echoAction "Building now..."
fi

$PHP composer.phar install --no-dev > /dev/null 2>&1
$PHP -d "phar.readonly=false" box.phar build -vvv
$PHP composer.phar install > /dev/null 2>&1

echoSuccess "Done."
exit 0;
