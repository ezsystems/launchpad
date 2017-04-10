<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */
if (php_uname('s') == 'Darwin') {
    // fix a TLS issue with php on Mac
    copy(__DIR__.'/../cacert.pem', sys_get_temp_dir().'/cacert.pem');
    putenv('SSL_CERT_FILE='.sys_get_temp_dir().'/cacert.pem');
}

$file = __DIR__.'/../vendor/autoload.php';
if (!file_exists($file)) {
    $file = __DIR__.'/../../../autoload.php';
}

if (!file_exists($file)) {
    echo 'You must set up the project dependencies, run the following commands:'.PHP_EOL.
         'curl -s http://getcomposer.org/installer | php'.PHP_EOL.
         'php composer.phar install'.PHP_EOL;
    exit(1);
}

include $file;

if (@date_default_timezone_get() != '') {
    date_default_timezone_set(getDefaultTimeZone());
}
