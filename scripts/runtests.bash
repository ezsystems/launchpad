#!/usr/bin/env bash

BASEDIR=$(dirname $0)
PHP="env php "
source ${BASEDIR}/functions
PROJECTDIR="${BASEDIR}/../"

cd ${PROJECTDIR}

echoTitle "PHPUNIT Tests"
$PHP ./vendor/bin/phpunit -c tests/ --exclude-group behat

echo ""
echo "***************"
echo ""

echoTitle "BEHAT Test"
$PHP ./vendor/bin/behat -c tests/behat.yml

echoSuccess "Done."
exit 0;
