<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */
$file = __DIR__.'/../vendor/autoload.php';
if (!file_exists($file)) {
    $file = __DIR__.'/../../../autoload.php';
}

if (!file_exists($file)) {
    echo 'You must set up the project dependencies, run the following commands:'.PHP_EOL.
         'make install'.PHP_EOL;
    exit(1);
}

include $file;

if ('' != @date_default_timezone_get()) {
    date_default_timezone_set(getDefaultTimeZone());
}
