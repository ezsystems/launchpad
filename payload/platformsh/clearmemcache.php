<?php
include(__DIR__."/../../ezplatform/vendor/autoload.php");

use Platformsh\ConfigReader\Config as PlatformshConfig;

$config = new PlatformshConfig();

if (isset($config->relationships['cache'][0])) {
    $m     = new Memcached();
    $cache = $config->relationships['cache'][0];
    $m->addServer($cache['host'], $cache['port']);
    if ($m->flush()) {
        echo "Memcache Cleared.\n";
    }
}

