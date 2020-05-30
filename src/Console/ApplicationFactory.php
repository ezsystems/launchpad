<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

declare(strict_types=1);

namespace eZ\Launchpad\Console;

use eZ\Launchpad\Console;
use eZ\Launchpad\DependencyInjection\CommandPass;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\EventDispatcher\DependencyInjection\RegisterListenersPass;

class ApplicationFactory
{
    public static function create(
        bool $autoExit = true,
        string $env = 'prod',
        string $operatingSystem = PHP_OS
    ): Application {
        \define('EZ_HOME', getenv('HOME').'/.ezlaunchpad');
        \define('EZ_ON_OSX', 'Darwin' === $operatingSystem);
        $container = new ContainerBuilder();
        $container->addCompilerPass(new CommandPass($env));
        $container->addCompilerPass(new RegisterListenersPass());
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__));
        $loader->load(__DIR__.'/../../config/services.yml');
        $loader->load(__DIR__.'/../../config/commands.yml');
        $application = new Console\Application();
        $application->setContainer($container);
        $application->setEnv($env);
        $application->setName('eZ Launchpad');
        $application->setVersion('@package_version@'.(('prod' !== $env) ? '-dev' : ''));
        $application->setAutoExit($autoExit);

        self::createDockerEnvParams();

        return $application;
    }

    private static function createDockerEnvParams()
    {
        foreach ($_SERVER['argv'] as $index => $value) {
            $_SERVER['argv'][$index] = preg_replace(
                '/^--env=/',
                '--docker-env=',
                $_SERVER['argv'][$index]
            );
        }
    }
}
