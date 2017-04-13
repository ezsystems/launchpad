#!/usr/bin/env bash

BASEDIR=$(dirname $0)
PHP="env php "
source ${BASEDIR}/functions
PROJECTDIR="${BASEDIR}/../"

cd ${PROJECTDIR}

if [ $# -eq 0 ] || [ "$1" == "unit" ]; then
echoTitle "PHPUNIT Tests"
$PHP ./vendor/bin/phpunit -c tests/ --exclude-group behat
fi

if [ $# -eq 0 ]; then
    echo ""
    echo "***************"
    echo ""
fi

if [ $# -eq 0 ] || [ "$1" == "behat" ]; then
    echoTitle "BEHAT Test"
    $PHP ./vendor/bin/behat -c tests/behat.yml
fi

echoSuccess "Done."
exit 0;
