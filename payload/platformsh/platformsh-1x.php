<?php
include(__DIR__."/../../../vendor/autoload.php");

use Platformsh\ConfigReader\Config as PlatformshConfig;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader;

$config = new PlatformshConfig();

if (!isset($config->project)) {
    return;
}

if (isset($config->relationships['database'][0])) {
    $database = $config->relationships['database'][0];
    $container->setParameter('database_driver', 'pdo_'.$database['scheme']);
    $container->setParameter('database_host', $database['host']);
    $container->setParameter('database_port', $database['port']);
    $container->setParameter('database_name', $database['path']);
    $container->setParameter('database_user', $database['username']);
    $container->setParameter('database_password', $database['password']);
    $container->setParameter('database_path', '');
}

if (isset($config->relationships['cache'][0])) {
    $pool  = 'singleredis';
    $cache = $config->relationships['cache'][0];
    $container->setParameter('cache_pool', $pool);
    $container->setParameter('cache_host', $cache['host']);
    $container->setParameter('cache_redis_port', $cache['port']);
    $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../cache_pool'));
    $loader->load($pool.'.yml');
}

// Disable PHPStormPass
$container->setParameter('ezdesign.phpstorm.enabled', false);


// Sessions are in redis via PHP Config
