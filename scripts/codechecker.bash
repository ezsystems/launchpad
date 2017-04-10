#!/usr/bin/env bash

BASEDIR=$(dirname $0)
PHP="env php"

source ${BASEDIR}/functions
PROJECTDIR="${BASEDIR}/../"

if [ -z "$1" ]; then
    SRC="src/"
else
    SRC="$1"
fi

cd ${PROJECTDIR}

echoTitle "******** Mess Detector ********"
$PHP ./vendor/bin/phpmd $SRC text .cs/md_ruleset.xml
$PHP ./vendor/bin/phpmd tests/Tests text .cs/md_ruleset.xml

echoTitle "******** CodeFixer ************"
$PHP ./vendor/bin/php-cs-fixer fix --config=.cs/.php_cs.php

echoTitle "******** CodeSniffer **********"
$PHP ./vendor/bin/phpcs --standard=.cs/cs_ruleset.xml --extensions=php $SRC
$PHP ./vendor/bin/phpcs --standard=.cs/cs_ruleset.xml --extensions=php tests/Tests

echoSuccess "Done."
exit 0;
