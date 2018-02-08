<?php
include(__DIR__."/../../ezplatform/vendor/autoload.php");

use Platformsh\ConfigReader\Config as PlatformshConfig;
use Symfony\Component\Cache\Simple\RedisCache;

$config = new PlatformshConfig();

if (isset($config->relationships['cache'][0])) {
    $cache = $config->relationships['cache'][0];

    $conn = RedisCache::createConnection("redis://{$cache['host']}:{$cache['port']}");
    $client = new RedisCache($conn);
    if ($client->clear()) {
        echo "Redis Cleared.\n";
    }
}

